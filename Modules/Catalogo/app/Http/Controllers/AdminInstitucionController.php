<?php

declare(strict_types=1);

namespace Modules\Catalogo\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Catalogo\Models\Categoria;
use Modules\Catalogo\Models\Institucion;
use Modules\Catalogo\Models\TipoExamen;

class AdminInstitucionController extends Controller
{
    public function index(Request $request)
    {
        $query = Institucion::withCount('categorias');

        if ($request->filled('busqueda')) {
            $q = $request->busqueda;
            $query->where('nombre', 'like', "%{$q}%");
        }

        $instituciones = $query->orderBy('nombre')->paginate(15);

        return Inertia::render('Admin/Instituciones/Index', [
            'instituciones' => $instituciones,
            'filtros' => $request->only(['busqueda']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Instituciones/Crear');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:200',
            'subtipo' => 'required|in:publica,privada',
            'ciudad' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'carreras' => 'nullable|array',
            'carreras.*' => 'required|string|max:200',
        ]);

        $tipoExamen = TipoExamen::where('slug', 'admision-universitaria')->firstOrFail();

        $logoUrl = null;
        if (!empty($validated['logo'])) {
            $logoUrl = '/storage/' . $request->file('logo')->store('instituciones', 'public');
        }

        DB::transaction(function () use ($validated, $tipoExamen, $logoUrl) {
            $institucion = Institucion::create([
                'tipo_examen_id' => $tipoExamen->id,
                'nombre' => $validated['nombre'],
                'subtipo' => $validated['subtipo'],
                'ciudad' => $validated['ciudad'] ?? null,
                'logo_url' => $logoUrl,
                'activo' => true,
            ]);

            foreach ($validated['carreras'] ?? [] as $index => $carreraNombre) {
                Categoria::create([
                    'institucion_id' => $institucion->id,
                    'nombre' => $carreraNombre,
                    'orden' => $index + 1,
                    'activo' => true,
                ]);
            }
        });

        return redirect()->route('admin.instituciones.index')
            ->with('success', "Universidad '{$validated['nombre']}' creada correctamente.");
    }

    public function edit($id)
    {
        $institucion = Institucion::with('categorias:id,institucion_id,nombre')->findOrFail($id);

        return Inertia::render('Admin/Instituciones/Crear', [
            'institucion' => $institucion,
            'editando' => true,
        ]);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $institucion = Institucion::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:200',
            'subtipo' => 'required|in:publica,privada',
            'ciudad' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'carreras' => 'nullable|array',
            'carreras.*' => 'required|string|max:200',
        ]);

        $updateData = [
            'nombre' => $validated['nombre'],
            'subtipo' => $validated['subtipo'],
            'ciudad' => $validated['ciudad'] ?? null,
        ];

        if (!empty($validated['logo'])) {
            if ($institucion->logo_url && str_starts_with($institucion->logo_url, '/storage/instituciones/')) {
                $relativePath = substr($institucion->logo_url, 9);
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($relativePath)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($relativePath);
                }
            }
            $updateData['logo_url'] = '/storage/' . $request->file('logo')->store('instituciones', 'public');
        }

        DB::transaction(function () use ($institucion, $validated, $updateData) {
            $institucion->update($updateData);

            $nuevas = array_map('trim', $validated['carreras'] ?? []);

            $existentes = $institucion->categorias()->pluck('nombre')->toArray();

            foreach ($nuevas as $index => $nombre) {
                if (!in_array($nombre, $existentes)) {
                    $institucion->categorias()->create([
                        'nombre' => $nombre,
                        'orden' => $index + 1,
                        'activo' => true,
                    ]);
                }
            }

            foreach ($existentes as $nombre) {
                if (!in_array($nombre, $nuevas)) {
                    $institucion->categorias()->where('nombre', $nombre)->delete();
                }
            }
        });

        return redirect()->route('admin.instituciones.index')
            ->with('success', "Universidad '{$validated['nombre']}' actualizada.");
    }

    public function destroy($id): RedirectResponse
    {
        $institucion = Institucion::findOrFail($id);

        if ($institucion->categorias()->exists()) {
            return back()->with('error', 'No se puede eliminar una universidad con carreras asociadas.');
        }

        $nombre = $institucion->nombre;
        $institucion->delete();

        return redirect()->route('admin.instituciones.index')
            ->with('success', "Universidad '{$nombre}' eliminada.");
    }
}
