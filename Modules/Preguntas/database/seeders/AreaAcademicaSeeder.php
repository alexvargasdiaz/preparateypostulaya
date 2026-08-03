<?php

declare(strict_types=1);

namespace Modules\Preguntas\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Preguntas\Models\AreaAcademica;

class AreaAcademicaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            [
                'nombre' => 'Ciencias Económicas, Administrativas y de Gestión',
                'descripcion' => 'Área que abarca disciplinas relacionadas con la economía, administración de empresas, contabilidad, finanzas, marketing y gestión organizacional.',
                'num_preguntas' => 100,
                'duracion_min' => 120,
            ],
            [
                'nombre' => 'Ciencias de la Salud, Farmacia y Bioquímica',
                'descripcion' => 'Área que comprende las ciencias de la salud, medicina, enfermería, farmacia, bioquímica y disciplinas afines al cuidado de la salud.',
                'num_preguntas' => 100,
                'duracion_min' => 120,
            ],
            [
                'nombre' => 'Ciencias Básicas e Ingenierías',
                'descripcion' => 'Área que incluye matemáticas, física, química, ciencias de la computación, ingeniería civil, industrial, de sistemas y demás ramas de ingeniería.',
                'num_preguntas' => 100,
                'duracion_min' => 120,
            ],
            [
                'nombre' => 'Ciencias Sociales, Derecho y de Humanidades',
                'descripcion' => 'Área que engloba derecho, ciencias políticas, sociología, psicología, filosofía, historia, letteras y ciencias sociales.',
                'num_preguntas' => 100,
                'duracion_min' => 120,
            ],
        ];

        foreach ($areas as $area) {
            AreaAcademica::updateOrCreate(
                ['nombre' => $area['nombre']],
                $area
            );
        }
    }
}
