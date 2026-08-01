<?php

declare(strict_types=1);

namespace Modules\Catalogo\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalogo\App\Models\Institucion;

class InstitucionSeeder extends Seeder
{
    public function run(): void
    {
        $tipoAdmision = 1; // Admisión Universitaria

        $universidades = [
            // Privadas
            ['nombre' => 'Pontificia Universidad Católica del Perú', 'slug' => 'pucp', 'subtipo' => 'privada', 'ciudad' => 'Lima'],
            ['nombre' => 'Universidad del Pacífico', 'slug' => 'upacifico', 'subtipo' => 'privada', 'ciudad' => 'Lima'],
            ['nombre' => 'Universidad San Ignacio de Loyola', 'slug' => 'usil', 'subtipo' => 'privada', 'ciudad' => 'Lima'],
            ['nombre' => 'Universidad Privada del Norte', 'slug' => 'upn', 'subtipo' => 'privada', 'ciudad' => 'Lima'],
            ['nombre' => 'Universidad Tecnológica del Perú', 'slug' => 'utp', 'subtipo' => 'privada', 'ciudad' => 'Lima'],
            ['nombre' => 'Universidad Ricardo Palma', 'slug' => 'urp', 'subtipo' => 'privada', 'ciudad' => 'Lima'],
            ['nombre' => 'Universidad de San Martín de Porres', 'slug' => 'usmp', 'subtipo' => 'privada', 'ciudad' => 'Lima'],

            // Públicas
            ['nombre' => 'Universidad Nacional Mayor de San Marcos', 'slug' => 'unmsm', 'subtipo' => 'publica', 'ciudad' => 'Lima'],
            ['nombre' => 'Universidad Nacional de Ingeniería', 'slug' => 'uni', 'subtipo' => 'publica', 'ciudad' => 'Lima'],
            ['nombre' => 'Universidad Nacional Agraria La Molina', 'slug' => 'unalm', 'subtipo' => 'publica', 'ciudad' => 'Lima'],
            ['nombre' => 'Universidad Nacional Federico Villarreal', 'slug' => 'unfv', 'subtipo' => 'publica', 'ciudad' => 'Lima'],
            ['nombre' => 'Universidad Nacional de San Agustín', 'slug' => 'unsa', 'subtipo' => 'publica', 'ciudad' => 'Arequipa'],
        ];

        foreach ($universidades as $uni) {
            Institucion::create([
                'tipo_examen_id' => $tipoAdmision,
                'nombre' => $uni['nombre'],
                'subtipo' => $uni['subtipo'],
                'ciudad' => $uni['ciudad'],
                'activo' => true,
            ]);
        }
    }
}
