<?php

declare(strict_types=1);

namespace Modules\Catalogo\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalogo\App\Models\Categoria;
use Modules\Catalogo\App\Models\Institucion;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        // Facultades genéricas que aplican a todas las universidades
        $facultades = [
            ['nombre' => 'Ingeniería', 'descripcion' => 'Carreras de ingeniería en todas sus especialidades'],
            ['nombre' => 'Ciencias de la Salud', 'descripcion' => 'Medicina, Enfermería, Odontología y afines'],
            ['nombre' => 'Derecho y Ciencias Políticas', 'descripcion' => 'Derecho, Ciencias Políticas y afines'],
            ['nombre' => 'Administración y Negocios', 'descripcion' => 'Administración, Contabilidad, Economía y afines'],
            ['nombre' => 'Ciencias de la Comunicación', 'descripcion' => 'Periodismo, Comunicaciones, Marketing'],
            ['nombre' => 'Arquitectura y Diseño', 'descripcion' => 'Arquitectura, Diseño Gráfico, Diseño de Interiores'],
            ['nombre' => 'Psicología', 'descripcion' => 'Psicología y afines'],
            ['nombre' => 'Ciencias e Informática', 'descripcion' => 'Computación, Sistemas, Informática, Data Science'],
            ['nombre' => 'Educación y Humanidades', 'descripcion' => 'Educación, Letras, Filosofía, Historia'],
            ['nombre' => 'Arte y Cultura', 'descripcion' => 'Artes escénicas, Música, Artes visuales'],
        ];

        $instituciones = Institucion::all();

        foreach ($instituciones as $institucion) {
            $orden = 0;
            foreach ($facultades as $facultad) {
                $orden++;
                Categoria::create([
                    'institucion_id' => $institucion->id,
                    'nombre' => $facultad['nombre'],
                    'descripcion_corta' => $facultad['descripcion'],
                    'orden' => $orden,
                    'activo' => true,
                ]);
            }
        }
    }
}
