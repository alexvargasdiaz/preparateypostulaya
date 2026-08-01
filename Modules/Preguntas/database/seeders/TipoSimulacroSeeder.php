<?php

declare(strict_types=1);

namespace Modules\Preguntas\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Preguntas\Models\AreaAcademica;
use Modules\Preguntas\Models\TipoSimulacro;

class TipoSimulacroSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            'Ciencias Económicas, Administrativas y de Gestión' => [
                ['nombre' => 'Simulacro UNI - Ciencias Económicas', 'descripcion' => 'Examen de admisión UNI enfocado en razonamiento lógico-matemático y verbal para áreas económicas.', 'num_preguntas' => 100, 'duracion_min' => 120],
                ['nombre' => 'Simulacro Genérico - Administración', 'descripcion' => 'Simulacro general para aspirantes a carreras de administración y gestión.', 'num_preguntas' => 80, 'duracion_min' => 90],
            ],
            'Ciencias de la Salud, Farmacia y Bioquímica' => [
                ['nombre' => 'Simulacro UNMSM - Ciencias de la Salud', 'descripcion' => 'Examen de admisión UNMSM para facultades de ciencias de la salud.', 'num_preguntas' => 100, 'duracion_min' => 120],
                ['nombre' => 'Simulacro Genérico - Farmacia', 'descripcion' => 'Simulacro general para aspirantes a farmacia y bioquímica.', 'num_preguntas' => 80, 'duracion_min' => 90],
            ],
            'Ciencias Básicas e Ingenierías' => [
                ['nombre' => 'Simulacro UNI - Ingenierías', 'descripcion' => 'Examen de admisión UNI para facultades de ingeniería y ciencias básicas.', 'num_preguntas' => 100, 'duracion_min' => 120],
                ['nombre' => 'Simulacro Genérico - Ingeniería', 'descripcion' => 'Simulacro general para aspirantes a ingeniería.', 'num_preguntas' => 80, 'duracion_min' => 90],
            ],
            'Ciencias Sociales, Derecho y de Humanidades' => [
                ['nombre' => 'Simulacro UNMSM - Humanidades', 'descripcion' => 'Examen de admisión UNMSM para facultades de ciencias sociales y humanidades.', 'num_preguntas' => 100, 'duracion_min' => 120],
                ['nombre' => 'Simulacro Genérico - Derecho', 'descripcion' => 'Simulacro general para aspirantes a derecho y ciencias políticas.', 'num_preguntas' => 80, 'duracion_min' => 90],
            ],
        ];

        foreach ($tipos as $areaNombre => $simulacros) {
            $area = AreaAcademica::where('nombre', $areaNombre)->first();
            if (!$area) continue;

            foreach ($simulacros as $tipo) {
                TipoSimulacro::updateOrCreate(
                    ['area_academica_id' => $area->id, 'nombre' => $tipo['nombre']],
                    $tipo
                );
            }
        }
    }
}
