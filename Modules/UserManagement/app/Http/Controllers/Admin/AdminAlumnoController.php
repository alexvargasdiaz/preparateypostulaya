<?php

declare(strict_types=1);

namespace Modules\UserManagement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Modules\UserManagement\Exports\AlumnosExport;
use Modules\UserManagement\Imports\AlumnosImport;

class AdminAlumnoController extends Controller
{
    /**
     * Muestra la lista de estudiantes con filtros.
     */
    public function index(Request $request): Response
    {
        $query = User::query()
            ->with('aprobadoPor:id,name')
            ->where('rol', 'estudiante');

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Búsqueda por nombre o email
        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->where('name', 'like', "%{$busqueda}%")
                  ->orWhere('email', 'like', "%{$busqueda}%");
            });
        }

        $alumnos = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Alumnos/Index', [
            'alumnos' => $alumnos,
            'filtros' => [
                'estado' => $request->estado,
                'busqueda' => $request->busqueda,
            ],
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo estudiante.
     */
    public function crear(): Response
    {
        return Inertia::render('Admin/Alumnos/Crear');
    }

    /**
     * Almacena un nuevo estudiante creado por un administrador (auto-aprobado).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'whatsapp_numero' => ['nullable', 'string', 'max:20', new \App\Rules\WhatsappPeruRule],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'rol' => 'estudiante',
            'whatsapp_numero' => isset($validated['whatsapp_numero']) && $validated['whatsapp_numero'] !== null
                ? \App\Support\Telefono::normalizarWhatsApp($validated['whatsapp_numero'])
                : null,
            'estado' => 'activo',
            'fecha_aprobacion' => now(),
            'aprobado_por' => auth()->id(),
        ]);

        return redirect()->route('admin.alumnos')
            ->with('success', "Estudiante {$validated['name']} creado y aprobado correctamente.");
    }

    /**
     * Muestra el formulario para editar un estudiante.
     */
    public function editar(int $id): Response
    {
        $alumno = User::where('rol', 'estudiante')->findOrFail($id);

        return Inertia::render('Admin/Alumnos/Crear', [
            'alumno' => $alumno,
            'editando' => true,
        ]);
    }

    /**
     * Actualiza los datos de un estudiante.
     */
    public function actualizar(Request $request, int $id): RedirectResponse
    {
        $alumno = User::where('rol', 'estudiante')->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($id)],
            'password' => ['nullable', 'string', 'min:8'],
            'whatsapp_numero' => ['nullable', 'string', 'max:20', new \App\Rules\WhatsappPeruRule],
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'whatsapp_numero' => isset($validated['whatsapp_numero']) && $validated['whatsapp_numero'] !== null
                ? \App\Support\Telefono::normalizarWhatsApp($validated['whatsapp_numero'])
                : null,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = bcrypt($validated['password']);
        }

        $alumno->update($data);

        return redirect()->route('admin.alumnos')
            ->with('success', "Estudiante {$alumno->name} actualizado correctamente.");
    }

    /**
     * Aprueba un estudiante pendiente.
     */
    public function aprobar(int $id): RedirectResponse
    {
        $alumno = User::where('rol', 'estudiante')->findOrFail($id);

        if (!$alumno->estaPendiente()) {
            return back()->with('error', 'El estudiante no está en estado pendiente.');
        }

        $alumno->aprobar(auth()->id());

        // 1. Notificación in-app al alumno
        try {
            $notificationService = app(\Modules\Notificaciones\Services\NotificationService::class);
            $notificationService->crear(
                usuario: $alumno,
                tipo: 'exito',
                titulo: '¡Tu cuenta ha sido aprobada!',
                mensaje: 'Ya puedes acceder a todos los simulacros. ¡Empieza a practicar ahora!',
                data: ['url' => '/dashboard'],
                icono: 'exito',
                audiencia: 'alumno',
            );
        } catch (\Exception $e) {
            report($e);
        }

        // 2. Notificación in-app a otros admins
        try {
            $notificationService = app(\Modules\Notificaciones\Services\NotificationService::class);
            $admins = User::whereIn('rol', ['super_admin', 'admin'])
                ->where('id', '!=', auth()->id())
                ->get();
            foreach ($admins as $admin) {
                $notificationService->alumnoAprobado(
                    admin: $admin,
                    nombreAlumno: $alumno->name,
                    alumnoId: $alumno->id,
                );
            }
        } catch (\Exception $e) {
            report($e);
        }

        // 3. Email de aprobación
        try {
            \Mail::to($alumno->email)->send(
                new \Modules\Notificaciones\Mail\AlumnoAprobadoMail($alumno)
            );
        } catch (\Exception $e) {
            report($e);
        }

        // 4. WhatsApp de aprobación
        try {
            if ($alumno->whatsapp_numero) {
                $whatsappService = app(\Modules\Notificaciones\Services\WhatsAppService::class);
                $whatsappService->notificarAprobacion($alumno->name, $alumno->whatsapp_numero);
            }
        } catch (\Exception $e) {
            report($e);
        }

        return back()->with('success', "Estudiante {$alumno->name} aprobado correctamente.");
    }

    /**
     * Rechaza un estudiante pendiente.
     */
    public function rechazar(int $id): RedirectResponse
    {
        $alumno = User::where('rol', 'estudiante')->findOrFail($id);

        if (!$alumno->estaPendiente()) {
            return back()->with('error', 'El estudiante no está en estado pendiente.');
        }

        $nombreAlumno = $alumno->name;
        $alumno->rechazar();

        // Notificar a otros admins
        try {
            $notificationService = app(\Modules\Notificaciones\Services\NotificationService::class);
            $admins = User::whereIn('rol', ['super_admin', 'admin'])
                ->where('id', '!=', auth()->id())
                ->get();
            foreach ($admins as $admin) {
                $notificationService->alumnoRechazado(
                    admin: $admin,
                    nombreAlumno: $nombreAlumno,
                    alumnoId: $id,
                );
            }
        } catch (\Exception $e) {
            report($e);
        }

        return back()->with('success', "Estudiante {$nombreAlumno} rechazado.");
    }

    /**
     * Elimina un estudiante.
     */
    public function eliminar(int $id): RedirectResponse
    {
        $alumno = User::where('rol', 'estudiante')->findOrFail($id);

        if ($id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $name = $alumno->name;
        $alumno->delete();

        return redirect()->route('admin.alumnos')
            ->with('success', "Estudiante {$name} eliminado correctamente.");
    }

    /**
     * Exporta alumnos a Excel respetando los filtros actuales.
     */
    public function exportar(Request $request)
    {
        $fecha = now()->format('Y-m-d_H-i-s');

        $filtros = [
            'estado' => $request->query('estado'),
            'busqueda' => $request->query('busqueda'),
        ];

        return Excel::download(
            new AlumnosExport($filtros),
            "alumnos_preparateypostula_{$fecha}.xlsx"
        );
    }

    /**
     * Importa alumnos desde un archivo Excel/CSV.
     */
    public function importar(Request $request): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            Excel::import(new AlumnosImport(), $request->file('archivo'));

            return redirect()->route('admin.alumnos')
                ->with('success', 'Alumnos importados correctamente.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $errores = collect($e->failures())->map(function ($failure) {
                return "Fila {$failure->row()}: " . implode(', ', $failure->errors());
            })->implode('<br>');

            return back()->with('error', "Errores en la importación:<br>{$errores}");
        } catch (\Exception $e) {
            return back()->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }
}
