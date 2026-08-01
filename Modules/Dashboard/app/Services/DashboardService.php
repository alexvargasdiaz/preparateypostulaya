<?php

declare(strict_types=1);

namespace Modules\Dashboard\Services;

use Modules\Rendicion\Models\IntentoExamen;

class DashboardService
{
    public function obtenerUltimosIntentos(int $userId, int $limite = 5)
    {
        return IntentoExamen::with([
                'institucion:id,nombre',
                'examen:id,titulo',
            ])
            ->where('usuario_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limite)
            ->get();
    }

    public function agruparPorInstitucion(int $userId): array
    {
        $intentos = IntentoExamen::where('usuario_id', $userId)
            ->where('estado', 'completado')
            ->whereNotNull('institucion_id')
            ->with(['institucion:id,nombre', 'examen:id,titulo,categoria_id'])
            ->get();

        $grupos = [];

        foreach ($intentos as $intento) {
            $instId = $intento->institucion_id;
            $instNombre = $intento->institucion?->nombre ?? 'Sin universidad';
            $carrera = $intento->carrera ?? 'Sin carrera';

            if (!isset($grupos[$instId])) {
                $grupos[$instId] = [
                    'institucion_id' => $instId,
                    'institucion_nombre' => $instNombre,
                    'carreras' => [],
                ];
            }

            if (!isset($grupos[$instId]['carreras'][$carrera])) {
                $grupos[$instId]['carreras'][$carrera] = [
                    'nombre' => $carrera,
                    'intentos' => 0,
                    'aprobados' => 0,
                    'mejor_puntaje' => 0,
                    'promedio' => 0,
                    'puntajes' => [],
                ];
            }

            $carreraData = &$grupos[$instId]['carreras'][$carrera];
            $carreraData['intentos']++;

            if ($intento->aprobado) {
                $carreraData['aprobados']++;
            }

            $pct = $intento->puntaje_maximo > 0
                ? round(($intento->puntaje_total / $intento->puntaje_maximo) * 100)
                : 0;

            $carreraData['puntajes'][] = $pct;
            $carreraData['mejor_puntaje'] = max($carreraData['mejor_puntaje'], $pct);
        }

        foreach ($grupos as &$grupo) {
            foreach ($grupo['carreras'] as &$carrera) {
                $carrera['promedio'] = count($carrera['puntajes']) > 0
                    ? round(array_sum($carrera['puntajes']) / count($carrera['puntajes']))
                    : 0;
                unset($carrera['puntajes']);
            }
            unset($carrera);
        }
        unset($grupo);

        return array_values($grupos);
    }
}
