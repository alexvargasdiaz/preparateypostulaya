<?php

declare(strict_types=1);

namespace Modules\Catalogo\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalogo\App\Models\Categoria;
use Modules\Catalogo\App\Models\Examen;
use Modules\Catalogo\App\Models\Seccion;
use Modules\Catalogo\App\Models\Concepto;

class ExamenSeeder extends Seeder
{
    public function run(): void
    {
        // Crear un examen de ejemplo para algunas categorías
        $categorias = Categoria::whereIn('nombre', ['Ingeniería', 'Ciencias de la Salud', 'Administración y Negocios'])
            ->limit(3)
            ->get();

        foreach ($categorias as $categoria) {
            $examen = Examen::create([
                'categoria_id' => $categoria->id,
                'titulo' => "Simulacro de Admisión - {$categoria->nombre}",
                'descripcion' => "Examen de preparación para postulantes a {$categoria->nombre}. 50 preguntas tipo admisión.",
                'tiempo_limite_min' => 90,
                'intentos_permitidos' => 3,
                'puntaje_minimo' => null,
                'num_alternativas_default' => 5,
                'aleatorizar_preguntas' => true,
                'aleatorizar_alternativas' => true,
                'activo' => true,
            ]);

            // Secciones del examen
            $secciones = [
                ['nombre' => 'Razonamiento Verbal', 'orden' => 1],
                ['nombre' => 'Razonamiento Matemático', 'orden' => 2],
                ['nombre' => 'Matemática', 'orden' => 3],
                ['nombre' => 'Comunicación y Literatura', 'orden' => 4],
                ['nombre' => 'Ciencias Sociales', 'orden' => 5],
            ];

            foreach ($secciones as $sec) {
                $seccion = Seccion::create([
                    'examen_id' => $examen->id,
                    'nombre' => $sec['nombre'],
                    'instrucciones' => "Responde las siguientes preguntas de {$sec['nombre']}. Tienes {$examen->tiempo_limite_min} minutos en total.",
                    'orden' => $sec['orden'],
                ]);

                // Conceptos dentro de cada sección
                $conceptos = $this->getConceptosParaSeccion($sec['nombre']);
                foreach ($conceptos as $i => $conceptoNombre) {
                    Concepto::create([
                        'seccion_id' => $seccion->id,
                        'nombre' => $conceptoNombre,
                        'descripcion' => "Preguntas sobre {$conceptoNombre}",
                    ]);
                }
            }
        }
    }

    private function getConceptosParaSeccion(string $seccion): array
    {
        return match ($seccion) {
            'Razonamiento Verbal' => [
                'Comprensión lectora', 'Sinónimos y antónimos', 'Analogías',
                'Oraciones incompletas', 'Plan de redacción',
            ],
            'Razonamiento Matemático' => [
                'Sucesiones numéricas', 'Planteo de ecuaciones', 'Fracciones y porcentajes',
                'Regla de tres', 'Estadística básica',
            ],
            'Matemática' => [
                'Álgebra', 'Geometría plana', 'Trigonometría',
                'Aritmética', 'Geometría analítica',
            ],
            'Comunicación y Literatura' => [
                'Gramática y ortografía', 'Literatura universal', 'Literatura peruana',
                'Comprensión de textos literarios', 'Figuras literarias',
            ],
            'Ciencias Sociales' => [
                'Historia del Perú', 'Historia universal', 'Geografía',
                'Economía básica', 'Formación cívica',
            ],
            default => ['Concepto general'],
        };
    }
}
