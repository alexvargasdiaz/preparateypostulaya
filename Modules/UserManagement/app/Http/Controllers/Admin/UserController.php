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
use Modules\UserManagement\Exports\UsuariosExport;
use Modules\UserManagement\Imports\UsuariosImport;

class UserController extends Controller
{
    /**
     * Muestra la lista de usuarios con filtros y paginación.
     */
    public function index(Request $request): Response
    {
        $query = User::query()->with('aprobadoPor:id,name')
            ->where('rol', '!=', 'estudiante'); // Los alumnos tienen su propio módulo

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por rol
        if ($request->filled('rol')) {
            $query->where('rol', $request->rol);
        }

        // Búsqueda por nombre o email
        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->where('name', 'like', "%{$busqueda}%")
                  ->orWhere('email', 'like', "%{$busqueda}%");
            });
        }

        $usuarios = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Usuarios/Index', [
            'usuarios' => $usuarios,
            'filtros' => [
                'estado' => $request->estado,
                'busqueda' => $request->busqueda,
                'rol' => $request->rol,
            ],
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo usuario.
     */
    public function crear(): Response
    {
        return Inertia::render('Admin/Usuarios/Crear');
    }

    /**
     * Almacena un nuevo usuario creado por un administrador.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'rol' => ['required', 'string', 'in:super_admin,admin,estudiante'],
            'whatsapp_numero' => ['nullable', 'string', 'max:20'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'rol' => $validated['rol'],
            'whatsapp_numero' => $validated['whatsapp_numero'] ?? null,
            'estado' => 'activo',
            'fecha_aprobacion' => now(),
            'aprobado_por' => auth()->id(),
        ]);

        return redirect()->route('admin.usuarios')
            ->with('success', "Usuario {$validated['name']} creado correctamente.");
    }

    /**
     * Aprueba un usuario pendiente.
     */
    public function aprobar(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (!$user->estaPendiente()) {
            return back()->with('error', 'El usuario no está en estado pendiente.');
        }

        $user->aprobar(auth()->id());

        // Notificar al usuario
        try {
            $notificationService = app(\Modules\Notificaciones\Services\NotificationService::class);
            $notificationService->crear(
                usuario: $user,
                tipo: 'exito',
                titulo: '✅ ¡Tu cuenta ha sido aprobada!',
                mensaje: 'Ya puedes acceder a todos los simulacros. ¡Empieza a practicar ahora!',
                data: ['url' => '/dashboard'],
                icono: '🎉',
            );
        } catch (\Exception $e) {
            report($e);
        }

        return back()->with('success', "Usuario {$user->name} aprobado correctamente.");
    }

    /**
     * Rechaza un usuario pendiente.
     */
    public function rechazar(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (!$user->estaPendiente()) {
            return back()->with('error', 'El usuario no está en estado pendiente.');
        }

        $user->rechazar();

        return back()->with('success', "Usuario {$user->name} rechazado.");
    }

    /**
     * Muestra el formulario para editar un usuario.
     */
    public function editar(int $id): Response
    {
        $user = User::findOrFail($id);

        return Inertia::render('Admin/Usuarios/Crear', [
            'usuario' => $user,
            'editando' => true,
        ]);
    }

    /**
     * Actualiza los datos de un usuario.
     */
    public function actualizar(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($id)],
            'password' => ['nullable', 'string', 'min:8'],
            'rol' => ['required', 'string', 'in:super_admin,admin,estudiante'],
            'whatsapp_numero' => ['nullable', 'string', 'max:20'],
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'rol' => $validated['rol'],
            'whatsapp_numero' => $validated['whatsapp_numero'] ?? null,
        ];

        // Solo actualizar contraseña si se proporcionó una nueva
        if (!empty($validated['password'])) {
            $data['password'] = bcrypt($validated['password']);
        }

        $user->update($data);

        return redirect()->route('admin.usuarios')
            ->with('success', "Usuario {$user->name} actualizado correctamente.");
    }

    /**
     * Elimina un usuario del sistema.
     */
    public function eliminar(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        // No permitir eliminarse a sí mismo
        if ($id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.usuarios')
            ->with('success', "Usuario {$name} eliminado correctamente.");
    }

    /**
     * Exporta usuarios a un archivo Excel respetando los filtros actuales.
     */
    public function exportar(Request $request)
    {
        $fecha = now()->format('Y-m-d_H-i-s');

        $filtros = [
            'estado' => $request->query('estado'),
            'rol' => $request->query('rol'),
            'busqueda' => $request->query('busqueda'),
        ];

        return Excel::download(
            new UsuariosExport($filtros),
            "usuarios_preparateypostula_{$fecha}.xlsx"
        );
    }

    /**
     * Importa usuarios desde un archivo Excel/CSV.
     */
    public function importar(Request $request): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'], // max 5MB
        ]);

        try {
            Excel::import(new UsuariosImport(), $request->file('archivo'));

            return redirect()->route('admin.usuarios')
                ->with('success', 'Usuarios importados correctamente.');
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
