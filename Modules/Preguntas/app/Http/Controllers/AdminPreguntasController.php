<?php

declare(strict_types=1);

namespace Modules\Preguntas\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Preguntas\Models\AreaAcademica;
use Modules\Preguntas\Models\Concepto;
use Modules\Preguntas\Models\Pregunta;
use Modules\Preguntas\Models\Alternativa;

class AdminPreguntasController extends Controller
{
    public function index(Request $request)
    {
        $query = Pregunta::with([
            'areaAcademica',
            'concepto',
            'alternativas',
        ]);

        if ($request->filled('area_academica_id')) {
            $query->where('area_academica_id', $request->area_academica_id);
        }
        if ($request->filled('concepto_id')) {
            $query->where('concepto_id', $request->concepto_id);
        }
        if ($request->filled('dificultad')) {
            $query->where('dificultad', $request->dificultad);
        }
        if ($request->filled('busqueda')) {
            $query->where('enunciado', 'like', "%{$request->busqueda}%");
        }

        $preguntas = $query->orderBy('created_at', 'desc')->paginate(15);

        $areas = AreaAcademica::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);
        $conceptos = Concepto::with('areaAcademica')->orderBy('nombre')->get(['id', 'nombre', 'area_academica_id']);

        return Inertia::render('Admin/Preguntas/Index', [
            'preguntas' => $preguntas,
            'areas' => $areas,
            'conceptos' => $conceptos,
            'filtros' => $request->only(['area_academica_id', 'concepto_id', 'dificultad', 'busqueda']),
        ]);
    }

    public function create()
    {
        $areas = AreaAcademica::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);
        $conceptos = Concepto::with('areaAcademica')->orderBy('nombre')->get(['id', 'nombre', 'area_academica_id']);

        return Inertia::render('Admin/Preguntas/Crear', [
            'areas' => $areas,
            'conceptos' => $conceptos,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'area_academica_id' => 'required|exists:areas_academicas,id',
            'concepto_id' => 'required|exists:conceptos,id',
            'nivel' => 'nullable|integer|min:1|max:3',
            'enunciado' => 'required|string',
            'dificultad' => 'required|in:facil,media,dificil',
            'tipo' => 'required|in:opcion_multiple',
            'enunciado_imagen_url' => 'nullable|string|max:500',
            'alternativas' => 'required|array|min:2|max:10',
            'alternativas.*.texto' => 'nullable|string',
            'alternativas.*.imagen_url' => 'nullable|string|max:500',
            'alternativas.*.es_correcta' => 'required|boolean',
            'alternativas.*.orden' => 'required|integer|min:0',
        ]);

        $correctas = collect($validated['alternativas'])->where('es_correcta', true)->count();
        if ($correctas !== 1) {
            return back()->withErrors(['alternativas' => 'Debe haber exactamente una alternativa correcta.'])->withInput();
        }

        $pregunta = Pregunta::create([
            'area_academica_id' => $validated['area_academica_id'],
            'concepto_id' => $validated['concepto_id'],
            'nivel' => $validated['nivel'] ?? null,
            'enunciado' => $validated['enunciado'],
            'enunciado_imagen_url' => $validated['enunciado_imagen_url'] ?? null,
            'dificultad' => $validated['dificultad'],
            'tipo' => $validated['tipo'],
            'activa' => true,
        ]);

        foreach ($validated['alternativas'] as $alt) {
            Alternativa::create([
                'pregunta_id' => $pregunta->id,
                'texto' => $alt['texto'],
                'imagen_url' => $alt['imagen_url'] ?? null,
                'es_correcta' => $alt['es_correcta'],
                'orden' => $alt['orden'],
            ]);
        }

        return redirect()->route('admin.preguntas.index')
            ->with('success', 'Pregunta creada exitosamente.');
    }

    public function edit($id)
    {
        $pregunta = Pregunta::with([
            'areaAcademica',
            'concepto',
            'alternativas' => fn ($q) => $q->orderBy('orden'),
        ])->findOrFail($id);

        $areas = AreaAcademica::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);
        $conceptos = Concepto::with('areaAcademica')->orderBy('nombre')->get(['id', 'nombre', 'area_academica_id']);

        return Inertia::render('Admin/Preguntas/Crear', [
            'pregunta' => $pregunta,
            'areas' => $areas,
            'conceptos' => $conceptos,
            'editando' => true,
        ]);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $pregunta = Pregunta::with('alternativas')->findOrFail($id);

        $validated = $request->validate([
            'area_academica_id' => 'required|exists:areas_academicas,id',
            'concepto_id' => 'required|exists:conceptos,id',
            'nivel' => 'nullable|integer|min:1|max:3',
            'enunciado' => 'required|string',
            'dificultad' => 'required|in:facil,media,dificil',
            'tipo' => 'required|in:opcion_multiple',
            'enunciado_imagen_url' => 'nullable|string|max:500',
            'alternativas' => 'required|array|min:2|max:10',
            'alternativas.*.id' => 'nullable|exists:alternativas,id',
            'alternativas.*.texto' => 'nullable|string',
            'alternativas.*.imagen_url' => 'nullable|string|max:500',
            'alternativas.*.es_correcta' => 'required|boolean',
            'alternativas.*.orden' => 'required|integer|min:0',
        ]);

        $correctas = collect($validated['alternativas'])->where('es_correcta', true)->count();
        if ($correctas !== 1) {
            return back()->withErrors(['alternativas' => 'Debe haber exactamente una alternativa correcta.'])->withInput();
        }

        $oldEnunciadoImg = $pregunta->enunciado_imagen_url;
        $newEnunciadoImg = $validated['enunciado_imagen_url'] ?? null;
        if ($oldEnunciadoImg && $oldEnunciadoImg !== $newEnunciadoImg) {
            $this->eliminarImagenLocal($oldEnunciadoImg);
        }

        $nuevosIds = collect($validated['alternativas'])->pluck('id')->filter()->toArray();
        $alternativasExistentes = $pregunta->alternativas->keyBy('id');

        foreach ($alternativasExistentes as $altExistente) {
            if (!in_array($altExistente->id, $nuevosIds)) {
                $this->eliminarImagenLocal($altExistente->imagen_url);
            }
        }

        foreach ($validated['alternativas'] as $alt) {
            if (!empty($alt['id']) && isset($alternativasExistentes[$alt['id']])) {
                $oldImg = $alternativasExistentes[$alt['id']]->imagen_url;
                $newImg = $alt['imagen_url'] ?? null;
                if ($oldImg && $oldImg !== $newImg) {
                    $this->eliminarImagenLocal($oldImg);
                }
            }
        }

        $pregunta->update([
            'area_academica_id' => $validated['area_academica_id'],
            'concepto_id' => $validated['concepto_id'],
            'nivel' => $validated['nivel'] ?? null,
            'enunciado' => $validated['enunciado'],
            'enunciado_imagen_url' => $newEnunciadoImg,
            'dificultad' => $validated['dificultad'],
            'tipo' => $validated['tipo'],
        ]);

        $pregunta->alternativas()->whereNotIn('id', $nuevosIds)->delete();

        foreach ($validated['alternativas'] as $alt) {
            $data = [
                'texto' => $alt['texto'],
                'imagen_url' => $alt['imagen_url'] ?? null,
                'es_correcta' => $alt['es_correcta'],
                'orden' => $alt['orden'],
            ];
            if (!empty($alt['id'])) {
                Alternativa::where('id', $alt['id'])->update($data);
            } else {
                $data['pregunta_id'] = $pregunta->id;
                Alternativa::create($data);
            }
        }

        return redirect()->route('admin.preguntas.index')
            ->with('success', 'Pregunta actualizada exitosamente.');
    }

    public function subirImagen(Request $request): JsonResponse
    {
        $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'tipo' => 'required|in:enunciado,alternativa',
        ]);

        $path = $request->file('imagen')->store('preguntas', 'public');

        if (!$path) {
            return response()->json(['error' => 'No se pudo subir la imagen.'], 500);
        }

        return response()->json([
            'success' => true,
            'url' => '/storage/' . $path,
            'path' => $path,
        ]);
    }

    public function importarForm()
    {
        $areas = AreaAcademica::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']);
        $conceptos = Concepto::with('areaAcademica')->orderBy('nombre')->get(['id', 'nombre', 'area_academica_id']);

        return Inertia::render('Admin/Preguntas/Importar', [
            'areas' => $areas,
            'conceptos' => $conceptos,
        ]);
    }

    public function subirImagenesMasivo(Request $request): JsonResponse
    {
        $request->validate([
            'imagenes' => 'required|array|min:1|max:100',
            'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $mapa = [];
        foreach ($request->file('imagenes') as $imagen) {
            $nombreOriginal = $imagen->getClientOriginalName();
            $path = $imagen->store('preguntas', 'public');
            $mapa[$nombreOriginal] = '/storage/' . $path;
        }

        return response()->json([
            'success' => true,
            'mapa' => $mapa,
            'count' => count($mapa),
        ]);
    }

    public function importar(Request $request): RedirectResponse
    {
        $request->validate([
            'area_academica_id' => 'required|exists:areas_academicas,id',
            'concepto_id' => 'nullable|exists:conceptos,id',
            'archivo' => 'required|file|mimes:csv,txt|max:10240',
            'imagenes_mapa' => 'nullable|array',
            'imagenes_mapa.*' => 'required|string',
        ]);

        $areaId = $request->area_academica_id;
        $conceptoId = $request->concepto_id;
        $imagenesMapa = $request->input('imagenes_mapa', []);

        $content = file_get_contents($request->file('archivo')->getRealPath());
        $lines = array_filter(array_map('trim', explode("\n", $content)));

        if (count($lines) < 1) {
            return back()->withErrors(['archivo' => 'El archivo está vacío.'])->withInput();
        }

        $creadas = 0;
        $errores = [];

        DB::transaction(function () use ($lines, $areaId, $conceptoId, $imagenesMapa, &$creadas, &$errores) {
            foreach ($lines as $index => $line) {
                $rowNum = $index + 1;

                $delimiter = str_contains($line, ';') ? ';' : ',';
                $fields = array_map('trim', explode($delimiter, $line));

                $conceptoNombre = null;
                $offset = 0;

                $fieldsSinConcepto = $fields;
                $fieldsConConcepto = array_slice($fields, 1);

                $conImagenesSinConcepto = count($fieldsSinConcepto) >= 6 && (count($fieldsSinConcepto) % 2 === 0);
                $conImagenesConConcepto = count($fieldsConConcepto) >= 6 && (count($fieldsConConcepto) % 2 === 0);

                $testSinConcepto = $this->testFormatoSimple($fieldsSinConcepto);
                $testConConcepto = $this->testFormatoSimple($fieldsConConcepto);

                if ($testConConcepto && !$testSinConcepto) {
                    $conceptoNombre = $fields[0];
                    $offset = 1;
                } elseif ($testConConcepto && $testSinConcepto) {
                    if (count($fields) % 2 !== 0) {
                        $conceptoNombre = $fields[0];
                        $offset = 1;
                    }
                }

                if ($conImagenesSinConcepto || $conImagenesConConcepto) {
                    $effectiveFields = $offset ? $fieldsConConcepto : $fieldsSinConcepto;
                    $enunciado = $effectiveFields[0] ?? '';
                    $imagenEnunciado = $this->resolverImagen($effectiveFields[1] ?? '', $imagenesMapa);

                    $alternativasData = [];
                    $altFields = array_slice($effectiveFields, 2, count($effectiveFields) - 4);
                    for ($i = 0; $i < count($altFields); $i += 2) {
                        $texto = $altFields[$i] ?? '';
                        $imagen = $this->resolverImagen($altFields[$i + 1] ?? '', $imagenesMapa);
                        if (!empty($texto)) {
                            $alternativasData[] = ['texto' => $texto, 'imagen_url' => $imagen];
                        }
                    }

                    $respuestaCorrecta = intval($effectiveFields[count($effectiveFields) - 2] ?? 0);
                    $dificultad = strtolower(trim($effectiveFields[count($effectiveFields) - 1] ?? 'media'));
                } else {
                    $effectiveFields = $offset ? $fieldsConConcepto : $fieldsSinConcepto;

                    if (count($effectiveFields) < 4) {
                        $errores[] = "Línea {$rowNum}: Mínimo 4 campos";
                        continue;
                    }

                    $enunciado = $effectiveFields[0] ?? '';
                    $imagenEnunciado = null;

                    $alternativasData = [];
                    $altTextos = array_slice($effectiveFields, 1, count($effectiveFields) - 3);
                    foreach ($altTextos as $texto) {
                        if (!empty($texto)) {
                            $alternativasData[] = ['texto' => $texto, 'imagen_url' => null];
                        }
                    }

                    $respuestaCorrecta = intval($effectiveFields[count($effectiveFields) - 2] ?? 0);
                    $dificultad = strtolower(trim($effectiveFields[count($effectiveFields) - 1] ?? 'media'));
                }

                if (empty($enunciado)) {
                    $errores[] = "Línea {$rowNum}: Enunciado vacío";
                    continue;
                }

                if (!in_array($dificultad, ['facil', 'media', 'dificil'])) {
                    $dificultad = 'media';
                }

                if (count($alternativasData) < 2) {
                    $errores[] = "Línea {$rowNum}: Mínimo 2 alternativas con texto";
                    continue;
                }

                if ($respuestaCorrecta < 1 || $respuestaCorrecta > count($alternativasData)) {
                    $errores[] = "Línea {$rowNum}: Respuesta correcta ({$respuestaCorrecta}) fuera de rango (1-" . count($alternativasData) . ")";
                    continue;
                }

                $conceptoFinalId = $conceptoId;
                if ($conceptoNombre) {
                    $concepto = Concepto::firstOrCreate(
                        ['nombre' => $conceptoNombre],
                        ['area_academica_id' => $areaId, 'descripcion' => $conceptoNombre]
                    );
                    $conceptoFinalId = $concepto->id;
                }

                if (!$conceptoFinalId) {
                    $errores[] = "Línea {$rowNum}: No se pudo determinar el concepto";
                    continue;
                }

                $pregunta = Pregunta::create([
                    'area_academica_id' => $areaId,
                    'concepto_id' => $conceptoFinalId,
                    'enunciado' => $enunciado,
                    'enunciado_imagen_url' => $imagenEnunciado,
                    'dificultad' => $dificultad,
                    'tipo' => 'opcion_multiple',
                    'activa' => true,
                ]);

                $orden = 0;
                foreach ($alternativasData as $altIndex => $alt) {
                    Alternativa::create([
                        'pregunta_id' => $pregunta->id,
                        'texto' => $alt['texto'],
                        'imagen_url' => $alt['imagen_url'],
                        'es_correcta' => ($altIndex + 1) === $respuestaCorrecta,
                        'orden' => $orden++,
                    ]);
                }

                $creadas++;
            }
        });

        $msg = "{$creadas} preguntas importadas correctamente.";
        if (!empty($errores)) {
            $msg .= ' ' . count($errores) . ' líneas con errores (verificar formato).';
        }

        return redirect()->route('admin.preguntas.index')
            ->with('success', $msg)
            ->with('errores_importacion', $errores);
    }

    private function resolverImagen(string $valor, array $imagenesMapa): ?string
    {
        $valor = trim($valor);
        if (empty($valor)) {
            return null;
        }
        if (str_starts_with($valor, 'http://') || str_starts_with($valor, 'https://') || str_starts_with($valor, '/storage/')) {
            return $valor;
        }
        return $imagenesMapa[$valor] ?? null;
    }

    private function testFormatoSimple(array $fields): bool
    {
        if (count($fields) < 4) {
            return false;
        }
        $respuesta = intval($fields[count($fields) - 2] ?? 0);
        $dificultad = strtolower(trim($fields[count($fields) - 1] ?? ''));
        return $respuesta >= 1 && in_array($dificultad, ['facil', 'media', 'dificil']);
    }

    public function destroy($id): RedirectResponse
    {
        $pregunta = Pregunta::with('alternativas')->findOrFail($id);

        $this->eliminarImagenLocal($pregunta->enunciado_imagen_url);
        foreach ($pregunta->alternativas as $alt) {
            $this->eliminarImagenLocal($alt->imagen_url);
        }

        $pregunta->alternativas()->delete();
        $pregunta->delete();

        return redirect()->route('admin.preguntas.index')
            ->with('success', 'Pregunta eliminada.');
    }

    private function eliminarImagenLocal(?string $url): void
    {
        if (!$url || !str_starts_with($url, '/storage/')) {
            return;
        }

        $relativePath = substr($url, 9);

        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
