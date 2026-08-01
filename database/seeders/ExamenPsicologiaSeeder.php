<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalogo\Models\TipoExamen;
use Modules\Catalogo\Models\Institucion;
use Modules\Catalogo\Models\Categoria;
use Modules\Catalogo\Models\Examen;
use Modules\Preguntas\Models\Pregunta;
use Modules\Preguntas\Models\Alternativa;

class ExamenPsicologiaSeeder extends Seeder
{
    public function run(): void
    {
        $tipo = TipoExamen::firstOrCreate(
            ['slug' => 'admision-universitaria'],
            ['nombre' => 'Admisión Universitaria', 'descripcion' => 'Simulacros de admisión.', 'activo' => true]
        );

        $institucion = Institucion::firstOrCreate(
            ['nombre' => 'Pontificia Universidad Católica del Perú'],
            [
                'tipo_examen_id' => $tipo->id,
                'subtipo' => 'privada',
                'ciudad' => 'Lima',
                'activo' => true,
            ]
        );

        $categoria = Categoria::firstOrCreate(
            ['institucion_id' => $institucion->id, 'nombre' => 'Psicología'],
            [
                'descripcion_corta' => 'Carrera de Psicología',
                'orden' => 6,
                'activo' => true,
            ]
        );

        $examen = Examen::firstOrCreate(
            ['categoria_id' => $categoria->id, 'titulo' => 'Simulacro de Admisión - Psicología'],
            [
                'descripcion' => 'Examen de 10 preguntas de admisión para la carrera de Psicología en la PUCP.',
                'tiempo_limite_min' => 20,
                'intentos_permitidos' => 99,
                'num_alternativas_default' => 5,
                'preguntas_por_intento' => 10,
                'aleatorizar_preguntas' => true,
                'aleatorizar_alternativas' => true,
                'activo' => true,
            ]
        );

        $institucionId = $institucion->id;
        $carrera = 'Psicología';

        $preguntas = [
            [
                'enunciado' => '¿Quién es considerado el padre del psicoanálisis?',
                'alternativas' => ['Carl Jung', 'Sigmund Freud', 'B.F. Skinner', 'Jean Piaget', 'Carl Rogers'],
                'correcta' => 1,
                'dificultad' => 'facil',
            ],
            [
                'enunciado' => '¿Cuál es el término que describe la adaptación del organismo a las condiciones del medio?',
                'alternativas' => ['Condicionamiento', 'Aprendizaje', 'Acomodación', 'Socialización', 'Motivación'],
                'correcta' => 2,
                'dificultad' => 'media',
            ],
            [
                'enunciado' => 'Según la teoría de Piaget, ¿en qué etapa se desarrolla el pensamiento abstracto?',
                'alternativas' => ['Etapa sensoriomotriz', 'Etapa preoperacional', 'Etapa de operaciones concretas', 'Etapa de operaciones formales', 'Etapa de latencia'],
                'correcta' => 3,
                'dificultad' => 'media',
            ],
            [
                'enunciado' => '¿Qué tipo de refuerzo consiste en eliminar un estímulo aversivo para aumentar una conducta?',
                'alternativas' => ['Refuerzo positivo', 'Refuerzo negativo', 'Castigo positivo', 'Castigo negativo', 'Extinción'],
                'correcta' => 1,
                'dificultad' => 'dificil',
            ],
            [
                'enunciado' => 'La escuela de psicología que estudia el comportamiento observable y sus relaciones con el ambiente es:',
                'alternativas' => ['Psicoanálisis', 'Humanismo', 'Conductismo', 'Gestalt', 'Cognitivismo'],
                'correcta' => 2,
                'dificultad' => 'facil',
            ],
            [
                'enunciado' => '¿Cuál de las siguientes opciones describe la "proyección" como mecanismo de defensa?',
                'alternativas' => [
                    'Rechazar pensamientos inaceptables',
                    'Atribuir a otros los propios sentimientos o impulsos negativos',
                    'Regresar a conductas de etapas anteriores del desarrollo',
                    'Cambiar un impulso inaceptable por uno aceptable',
                    'Justificar un comportamiento con razones lógicas',
                ],
                'correcta' => 1,
                'dificultad' => 'media',
            ],
            [
                'enunciado' => '¿Qué autor propuso la jerarquía de necesidades humanas?',
                'alternativas' => ['Sigmund Freud', 'Carl Rogers', 'Abraham Maslow', 'Erik Erikson', 'Albert Bandura'],
                'correcta' => 2,
                'dificultad' => 'facil',
            ],
            [
                'enunciado' => 'En el condicionamiento clásico, el estímulo condicionado (EC) se identifica porque:',
                'alternativas' => [
                    'Provoca una respuesta natural e innata',
                    'Es originalmente neutral y adquiere poder evocador por asociación',
                    'Siempre precede al estímulo incondicionado',
                    'Produce una respuesta refleja sin aprendizaje',
                    'Se presenta solo después de la respuesta',
                ],
                'correcta' => 1,
                'dificultad' => 'media',
            ],
            [
                'enunciado' => 'El concepto de "inteligencia emocional" fue popularizado por:',
                'alternativas' => ['Howard Gardner', 'Daniel Goleman', 'Robert Sternberg', 'Charles Spearman', 'Raymond Cattell'],
                'correcta' => 1,
                'dificultad' => 'facil',
            ],
            [
                'enunciado' => '¿Qué trastorno se caracteriza por episodios recurrentes de angustia intensa y miedo a morir o a "volverse loco"?',
                'alternativas' => ['Fobia específica', 'Trastorno obsesivo-compulsivo', 'Trastorno de pánico', 'Trastorno de ansiedad social', 'Trastorno de estrés postraumático'],
                'correcta' => 2,
                'dificultad' => 'dificil',
            ],
        ];

        $orden = 0;
        foreach ($preguntas as $pData) {
            $pregunta = Pregunta::updateOrCreate(
                ['examen_id' => $examen->id, 'enunciado' => $pData['enunciado']],
                [
                    'institucion_id' => $institucionId,
                    'carrera' => $carrera,
                    'tipo' => 'opcion_multiple',
                    'dificultad' => $pData['dificultad'],
                    'orden' => ++$orden,
                    'activa' => true,
                ]
            );

            foreach ($pData['alternativas'] as $j => $texto) {
                Alternativa::updateOrCreate(
                    ['pregunta_id' => $pregunta->id, 'orden' => $j],
                    ['texto' => $texto, 'es_correcta' => $j === $pData['correcta']]
                );
            }
        }

        $this->command->info('✅ Examen de Psicología PUCP creado: 10 preguntas');
    }
}
