<?php

declare(strict_types=1);

namespace Modules\Progreso\Services;

use Illuminate\Support\Collection;
use Modules\Rendicion\Models\IntentoExamen;

class ProgresoService
{
    /**
     * Los cuatro métodos públicos consultan el mismo set de intentos; se carga
     * una sola vez por petición para evitar repetir la query pesada 4 veces.
     */
    private ?Collection $intentosCache = null;

    public function obtenerEstadisticas(int $userId): array
    {
        $intentos = $this->obtenerIntentos($userId);

        $totalExamenes = $intentos->count();
        $totalAprobados = $intentos->where('aprobado', true)->count();
        $puntajes = $this->calcularPuntajes($intentos);

        return [
            'total_examenes' => $totalExamenes,
            'total_aprobados' => $totalAprobados,
            'tasa_aprobacion' => $totalExamenes > 0
                ? round(($totalAprobados / $totalExamenes) * 100)
                : 0,
            'promedio_general' => $puntajes->isNotEmpty() ? round($puntajes->avg('valor')) : 0,
            'mejor_puntaje' => (int) ($puntajes->isNotEmpty() ? $puntajes->max('valor') : 0),
            'total_preguntas' => $intentos->sum('puntaje_maximo'),
            'total_correctas' => $intentos->sum('puntaje_total'),
            'precision_global' => $this->calcularPrecisionGlobal($intentos),
            'mejor_racha' => $this->calcularMejorRacha($intentos),
        ];
    }

    public function obtenerEvolucion(int $userId): Collection
    {
        return $this->obtenerIntentos($userId)->map(fn ($i) => [
            'fecha' => $i->created_at->format('d/m'),
            'fecha_completa' => $i->created_at->format('Y-m-d'),
            'puntaje' => $i->puntaje_maximo > 0
                ? round(($i->puntaje_total / $i->puntaje_maximo) * 100)
                : 0,
            'total' => $i->puntaje_total,
            'maximo' => $i->puntaje_maximo,
            'examen' => $this->formatearTituloExamen($i),
            'aprobado' => $i->aprobado,
        ]);
    }

    public function obtenerRendimientoConceptos(int $userId): Collection
    {
        $intentos = $this->obtenerIntentos($userId);

        $conceptosData = [];
        foreach ($intentos as $intento) {
            foreach ($intento->resultadosConceptos as $rc) {
                $nombre = $rc->concepto?->nombre ?? 'Concepto';
                if (!isset($conceptosData[$nombre])) {
                    $conceptosData[$nombre] = ['nombre' => $nombre, 'total' => 0, 'correctas' => 0];
                }
                $conceptosData[$nombre]['total'] += $rc->preguntas_totales;
                $conceptosData[$nombre]['correctas'] += $rc->preguntas_correctas;
            }
        }

        return collect($conceptosData)
            ->map(fn ($c) => [
                'nombre' => $c['nombre'],
                'porcentaje' => $c['total'] > 0 ? round(($c['correctas'] / $c['total']) * 100) : 0,
                'correctas' => $c['correctas'],
                'total' => $c['total'],
            ])
            ->sortBy('porcentaje')
            ->values();
    }

    public function obtenerRecientes(int $userId, int $limite = 5): Collection
    {
        return $this->obtenerIntentos($userId)
            ->reverse()
            ->take($limite)
            ->map(fn ($i) => [
                'id' => $i->id,
                'examen' => $this->formatearTituloExamen($i),
                'institucion' => $i->examen?->categoria?->institucion?->nombre ?? $i->institucion?->nombre ?? '',
                'fecha' => $i->created_at->format('d/m/Y'),
                'puntaje' => $i->puntaje_total,
                'maximo' => $i->puntaje_maximo,
                'porcentaje' => $i->puntaje_maximo > 0
                    ? round(($i->puntaje_total / $i->puntaje_maximo) * 100)
                    : 0,
                'aprobado' => $i->aprobado,
            ]);
    }

    private function obtenerIntentos(int $userId): Collection
    {
        return $this->intentosCache ??= IntentoExamen::with([
            'examen.categoria.institucion',
            'institucion:id,nombre',
            'resultadosConceptos.concepto',
        ])
            ->where('usuario_id', $userId)
            ->where('estado', 'completado')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    private function calcularPuntajes(Collection $intentos): Collection
    {
        return $intentos->map(fn ($i) => [
            'valor' => $i->puntaje_maximo > 0
                ? round(($i->puntaje_total / $i->puntaje_maximo) * 100)
                : 0,
            'total' => $i->puntaje_total,
            'maximo' => $i->puntaje_maximo,
        ]);
    }

    private function calcularPrecisionGlobal(Collection $intentos): float
    {
        $totalPreguntas = $intentos->sum('puntaje_maximo');
        $totalCorrectas = $intentos->sum('puntaje_total');

        return $totalPreguntas > 0 ? round(($totalCorrectas / $totalPreguntas) * 100) : 0;
    }

    private function calcularMejorRacha(Collection $intentos): int
    {
        $mejorRacha = 0;
        $actual = 0;
        foreach ($intentos as $intento) {
            if ($intento->aprobado) {
                $actual++;
                if ($actual > $mejorRacha) $mejorRacha = $actual;
            } else {
                $actual = 0;
            }
        }
        return $mejorRacha;
    }

    private function formatearTituloExamen($intento): string
    {
        return $intento->examen?->titulo
            ?? ($intento->institucion?->nombre
                ? ($intento->carrera
                    ? $intento->carrera . ' - ' . $intento->institucion->nombre
                    : $intento->institucion->nombre)
                : 'Examen diagnóstico');
    }
}
