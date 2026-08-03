<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Preguntas\Database\Seeders\AreaAcademicaSeeder;
use Modules\Preguntas\Database\Seeders\TipoSimulacroSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Área de Ingenierías + usuarios demo + examen + intento demo
            DemoDataSeeder::class,
            // Crea/actualiza las 4 áreas académicas (idempotente)
            AreaAcademicaSeeder::class,
            // Crea/actualiza los 8 tipos de simulacro por área (idempotente)
            TipoSimulacroSeeder::class,
            // Preguntas de las áreas de Salud, Económicas y Sociales (idempotente)
            PreguntasOtrasAreasSeeder::class,
        ]);
    }
}
