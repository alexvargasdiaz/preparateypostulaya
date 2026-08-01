<?php

declare(strict_types=1);

namespace Modules\Catalogo\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalogo\App\Models\TipoExamen;

class TipoExamenSeeder extends Seeder
{
    public function run(): void
    {
        TipoExamen::create([
            'nombre' => 'Admisión Universitaria',
            'slug' => 'admision-universitaria',
            'descripcion' => 'Simulacros de preparación para exámenes de admisión a universidades peruanas e internacionales.',
            'activo' => true,
        ]);

        // Placeholder para futuros tipos de examen
        // TipoExamen::create([
        //     'nombre' => 'Examen de Manejo',
        //     'slug' => 'examen-manejo',
        //     'descripcion' => 'Simulacros para licencias de conducir.',
        //     'activo' => false,
        // ]);
    }
}
