<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Catalogo\Models\Examen;
use Modules\Preguntas\Models\AreaAcademica;
use Modules\Preguntas\Models\Concepto;
use Modules\Preguntas\Models\Pregunta;
use Modules\Preguntas\Models\Alternativa;
use Modules\Preguntas\Models\MensajeAyuda;
use Modules\Rendicion\Models\IntentoExamen;
use Modules\Rendicion\Models\RespuestaUsuario;
use Modules\Rendicion\Models\ResultadoConcepto;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->crearUsuarios();
        $area = $this->crearAreaAcademica();
        $conceptos = $this->crearConceptos($area);
        $this->crearMensajesAyuda($conceptos);
        $examen = $this->crearExamen($area, $conceptos);
        $preguntas = $this->crearPreguntas($area, $conceptos);

        $this->command->info('✅ ' . $preguntas->count() . ' preguntas en total');

        $this->crearIntentoDemo(
            User::where('email', 'estudiante@demo.com')->first(),
            $examen,
            $preguntas
        );
    }

    private function crearUsuarios(): void
    {
        $usuarios = [
            ['name' => 'Admin General', 'email' => 'admin@preparateypostulaya.com', 'rol' => RolUsuario::SuperAdmin, 'whatsapp' => '+51999000001'],
            ['name' => 'Profesor Contenido', 'email' => 'profesor@demo.com', 'rol' => RolUsuario::Admin, 'whatsapp' => '+51999000002'],
            ['name' => 'Carlos Estudiante', 'email' => 'estudiante@demo.com', 'rol' => RolUsuario::Estudiante, 'whatsapp' => '+51999000003'],
            ['name' => 'María López', 'email' => 'maria@demo.com', 'rol' => RolUsuario::Estudiante, 'whatsapp' => '+51999000004'],
        ];

        foreach ($usuarios as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => bcrypt('password'),
                    'rol' => $u['rol'],
                    'whatsapp_numero' => $u['whatsapp'],
                    'estado' => 'activo',
                ]
            );
        }
        $this->command->info('✅ Usuarios creados/actualizados');
    }

    private function crearAreaAcademica(): AreaAcademica
    {
        $area = AreaAcademica::firstOrCreate(
            ['nombre' => 'Ciencias Básicas e Ingenierías'],
            [
                'descripcion' => 'Simulacros de admisión para carreras de ingeniería y ciencias básicas.',
                'num_preguntas' => 100,
                'duracion_min' => 120,
                'activo' => true,
            ]
        );
        $this->command->info('✅ Área académica: ' . $area->nombre);
        return $area;
    }

    private function crearConceptos(AreaAcademica $area): array
    {
        // Solo cursos de ciencias exactas. Las materias de letras/sociales
        // (Geografía, Historia, Literatura, Gramática, etc.) pertenecen a
        // otras áreas académicas y no deben duplicarse aquí.
        $nombresConceptos = [
            'Sucesiones numéricas',
            'Planteo de ecuaciones',
            'Porcentajes',
            'Álgebra',
            'Geometría plana',
            'Trigonometría',
        ];

        $conceptos = [];
        foreach ($nombresConceptos as $nombre) {
            $conceptos[$nombre] = Concepto::firstOrCreate(
                ['nombre' => $nombre, 'area_academica_id' => $area->id],
                [
                    'area_academica_id' => $area->id,
                    'descripcion' => "Preguntas sobre {$nombre}",
                ]
            );
        }

        // Limpieza de conceptos heredados de versiones anteriores del seeder
        // que no corresponden a un área de ciencias exactas (p.ej. Geografía,
        // Historia del Perú, Comprensión lectora). Sus preguntas y resultados
        // asociados se eliminan para evitar duplicados entre áreas.
        $conceptosObsoletos = Concepto::where('area_academica_id', $area->id)
            ->whereNotIn('nombre', $nombresConceptos)
            ->get();

        foreach ($conceptosObsoletos as $concepto) {
            Pregunta::where('concepto_id', $concepto->id)->delete();
            $concepto->delete();
        }

        if ($conceptosObsoletos->isNotEmpty()) {
            $this->command->warn('  → Eliminados de Ciencias Básicas: ' . $conceptosObsoletos->pluck('nombre')->implode(', '));
        }

        $this->command->info('✅ ' . count($conceptos) . ' conceptos vinculados al área');
        return $conceptos;
    }

    private function crearExamen(AreaAcademica $area, array $conceptos): Examen
    {
        $examen = Examen::updateOrCreate(
            ['titulo' => 'Simulacro de Admisión - Ciencias Básicas e Ingenierías'],
            [
                'area_academica_id' => $area->id,
                'descripcion' => 'Examen de preparación con preguntas distribuidas por curso.',
                'tiempo_limite_min' => 20,
                'intentos_permitidos' => 99,
                'num_alternativas_default' => 5,
                'preguntas_por_intento' => 30,
                'aleatorizar_preguntas' => true,
                'aleatorizar_alternativas' => true,
                'activo' => true,
            ]
        );

        // Asignar 2 preguntas por cada concepto (3 para los cursos principales)
        $conceptosData = [];
        foreach ($conceptos as $nombre => $concepto) {
            $numPreg = in_array($nombre, ['Álgebra', 'Geometría plana']) ? 3 : 2;
            $conceptosData[$concepto->id] = ['num_preguntas' => $numPreg];
        }
        $examen->conceptos()->sync($conceptosData);

        $this->command->info('✅ Examen creado con configuración por concepto');
        return $examen;
    }

    private function crearPreguntas(AreaAcademica $area, array $conceptos)
    {
        $banco = $this->bancoPreguntas();
        $preguntas = collect();

        foreach ($banco as $conceptoNombre => $pregs) {
            $concepto = $conceptos[$conceptoNombre] ?? null;
            if (!$concepto) continue;

            foreach ($pregs as $pData) {
                $pregunta = Pregunta::updateOrCreate(
                    [
                        'enunciado' => $pData['enunciado'],
                        'concepto_id' => $concepto->id,
                    ],
                    [
                        'area_academica_id' => $area->id,
                        'concepto_id' => $concepto->id,
                        'tipo' => 'opcion_multiple',
                        'dificultad' => $pData['dificultad'] ?? 'media',
                        'activa' => true,
                    ]
                );

                foreach ($pData['alternativas'] as $j => $texto) {
                    Alternativa::updateOrCreate(
                        ['pregunta_id' => $pregunta->id, 'orden' => $j],
                        ['texto' => $texto, 'es_correcta' => $j === $pData['correcta']]
                    );
                }

                $preguntas->push($pregunta);
            }
        }

        $this->command->info('  → Preguntas creadas por concepto');
        return $preguntas;
    }

    private function bancoPreguntas(): array
    {
        $banco = [];

        $banco['Sinónimos y antónimos'] = [
            ['enunciado' => '¿Cuál es el sinónimo de "efímero"?', 'alternativas' => ['Duradero', 'Pasajero', 'Eterno', 'Constante', 'Firme'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'El término "ósculo" significa:', 'alternativas' => ['Abrazo', 'Mirada', 'Beso', 'Palabra', 'Suspiro'], 'correcta' => 2, 'dificultad' => 'dificil'],
            ['enunciado' => 'Antónimo de "prolijo":', 'alternativas' => ['Ordenado', 'Descuidado', 'Detallista', 'Preciso', 'Minucioso'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Sinónimo de "cándido":', 'alternativas' => ['Astuto', 'Ingenuo', 'Sagaz', 'Suspicaz', 'Ladino'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Antónimo de "beligerante":', 'alternativas' => ['Agresivo', 'Pacífico', 'Combativo', 'Hostil', 'Guerrero'], 'correcta' => 1, 'dificultad' => 'dificil'],
            ['enunciado' => 'Sinónimo de "perenne":', 'alternativas' => ['Temporal', 'Eterno', 'Breve', 'Caduco', 'Fugaz'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => '¿Cuál es el antónimo de "austeridad"?', 'alternativas' => ['Sobriedad', 'Liberalidad', 'Mesura', 'Recato', 'Moderación'], 'correcta' => 1, 'dificultad' => 'dificil'],
            ['enunciado' => '"Paráfrasis" significa:', 'alternativas' => ['Repetición exacta', 'Explicación ampliada', 'Contradicción', 'Omisión', 'Resumen'], 'correcta' => 1, 'dificultad' => 'media'],
        ];

        $banco['Comprensión lectora'] = [
            ['enunciado' => 'En el texto "El sol brillaba intensamente mientras las aves cruzaban el cielo despejado", ¿qué elemento del paisaje se describe?', 'alternativas' => ['Un día nublado', 'Un día soleado', 'Una noche estrellada', 'Un atardecer', 'Una tormenta'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Si un texto menciona que "la minería informal contamina los ríos", ¿cuál es la idea principal?', 'alternativas' => ['La minería genera empleo', 'La minería informal daña el ambiente', 'Los ríos son navegables', 'La minería es rentable', 'El Estado apoya la minería'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'En el enunciado "El cambio climático es una realidad innegable que afecta a todos los continentes", la palabra "innegable" significa:', 'alternativas' => ['Discutible', 'Indudable', 'Posible', 'Negable', 'Cuestionable'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Si un texto dice "La deforestación avanza a un ritmo alarmante, poniendo en riesgo la biodiversidad", la consecuencia directa es:', 'alternativas' => ['Más empleos rurales', 'Pérdida de biodiversidad', 'Aumento de lluvias', 'Más áreas urbanas', 'Mayor producción'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Lee: "El ahorro es la base de la estabilidad financiera. Quien ahorra regularmente puede enfrentar imprevistos". La idea secundaria es:', 'alternativas' => ['El ahorro es importante', 'Ahorrar permite enfrentar imprevistos', 'La estabilidad financiera es clave', 'Hay que gastar menos', 'El ahorro genera riqueza'], 'correcta' => 1, 'dificultad' => 'dificil'],
        ];

        $banco['Analogías'] = [
            ['enunciado' => 'Médico es a hospital como profesor es a:', 'alternativas' => ['Pizarra', 'Escuela', 'Libro', 'Alumno', 'Tiza'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Perro es a ladrar como gato es a:', 'alternativas' => ['Ronronear', 'Maullar', 'Silbar', 'Gruñir', 'Aullar'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Ojo es a ver como oído es a:', 'alternativas' => ['Escuchar', 'Oler', 'Tocar', 'Saborear', 'Sentir'], 'correcta' => 0, 'dificultad' => 'facil'],
            ['enunciado' => 'Árbol es a bosque como persona es a:', 'alternativas' => ['Casa', 'Sociedad', 'Familia', 'Ciudad', 'País'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Llovizna es a tormenta como brisa es a:', 'alternativas' => ['Viento', 'Huracán', 'Aire', 'Tornado', 'Ventisca'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Cuchara es a sopa como tenedor es a:', 'alternativas' => ['Arroz', 'Fideos', 'Ensalada', 'Caldo', 'Crema'], 'correcta' => 2, 'dificultad' => 'facil'],
            ['enunciado' => 'Célula es a tejido como letra es a:', 'alternativas' => ['Oración', 'Palabra', 'Párrafo', 'Texto', 'Libro'], 'correcta' => 1, 'dificultad' => 'media'],
        ];

        $banco['Sucesiones numéricas'] = [
            ['enunciado' => '¿Qué número continúa? 2, 6, 12, 20, __', 'alternativas' => ['28', '30', '32', '24', '26'], 'correcta' => 0, 'dificultad' => 'media'],
            ['enunciado' => '¿Qué número sigue? 3, 9, 27, 81, __', 'alternativas' => ['162', '243', '324', '189', '108'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Continúa: 1, 4, 9, 16, 25, __', 'alternativas' => ['30', '36', '35', '49', '64'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => '¿Qué número falta? 1, 1, 2, 3, 5, 8, 13, __', 'alternativas' => ['18', '21', '20', '24', '19'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Completa: 100, 96, 91, 85, 78, __', 'alternativas' => ['70', '72', '68', '65', '74'], 'correcta' => 0, 'dificultad' => 'dificil'],
            ['enunciado' => 'Sigue la serie: 5, 10, 20, 40, 80, __', 'alternativas' => ['120', '160', '100', '140', '180'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => '¿Cuál continúa? 1, 8, 27, 64, 125, __', 'alternativas' => ['180', '216', '200', '250', '343'], 'correcta' => 1, 'dificultad' => 'dificil'],
            ['enunciado' => 'Completa: 0, 7, 26, 63, 124, __', 'alternativas' => ['215', '200', '180', '225', '250'], 'correcta' => 0, 'dificultad' => 'dificil'],
        ];

        $banco['Porcentajes'] = [
            ['enunciado' => 'Si el 15% de un número es 45, ¿cuál es el número?', 'alternativas' => ['200', '250', '300', '350', '400'], 'correcta' => 2, 'dificultad' => 'media'],
            ['enunciado' => 'Un producto cuesta S/80 y tiene un descuento del 25%. ¿Cuánto se paga?', 'alternativas' => ['S/55', 'S/60', 'S/65', 'S/50', 'S/70'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Si el 40% de los estudiantes son mujeres y hay 200 varones, ¿cuántos estudiantes hay en total?', 'alternativas' => ['300', '333', '350', '280', '320'], 'correcta' => 1, 'dificultad' => 'dificil'],
            ['enunciado' => 'Un artículo que costaba S/200 ahora cuesta S/170. ¿Qué porcentaje de descuento se aplicó?', 'alternativas' => ['10%', '15%', '20%', '25%', '30%'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'En un salón de 30 alumnos, 18 aprobaron. ¿Qué porcentaje aprobó?', 'alternativas' => ['50%', '60%', '65%', '70%', '55%'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Si inviertes S/1000 al 5% anual, ¿cuánto tendrás después de 2 años (interés simple)?', 'alternativas' => ['S/1050', 'S/1100', 'S/1150', 'S/1200', 'S/1250'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Un pantalón cuesta S/120 con el 30% de descuento. ¿Cuál era su precio original?', 'alternativas' => ['S/150', 'S/160', 'S/170', 'S/180', 'S/200'], 'correcta' => 1, 'dificultad' => 'dificil'],
        ];

        $banco['Álgebra'] = [
            ['enunciado' => 'Resuelve: 2x + 5 = 15. ¿Cuánto vale x?', 'alternativas' => ['3', '5', '7', '10', '12'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Si x² - 9 = 0, ¿cuáles son los valores de x?', 'alternativas' => ['±2', '±3', '±4', '±1', '±6'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Resuelve: 3(x - 4) = 2x + 6. Halla x.', 'alternativas' => ['12', '15', '18', '9', '21'], 'correcta' => 2, 'dificultad' => 'media'],
            ['enunciado' => 'Factoriza: x² - 16', 'alternativas' => ['(x-4)(x-4)', '(x-4)(x+4)', '(x+4)(x+4)', 'x(x-16)', '(x-8)(x+2)'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Si 5x - 3 = 2x + 12, ¿cuánto vale x?', 'alternativas' => ['3', '5', '4', '6', '7'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Resuelve: (x + 3)(x - 2) = 0. Las raíces son:', 'alternativas' => ['3 y -2', '-3 y 2', '3 y 2', '-3 y -2', '6 y -1'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Si x + y = 10 y x - y = 2, ¿cuánto vale x?', 'alternativas' => ['4', '5', '6', '7', '8'], 'correcta' => 2, 'dificultad' => 'media'],
            ['enunciado' => 'Simplifica: 2(3x - 1) - 5x + 4', 'alternativas' => ['x + 2', 'x - 2', 'x + 6', 'x - 6', '11x + 2'], 'correcta' => 0, 'dificultad' => 'facil'],
        ];

        $banco['Geometría plana'] = [
            ['enunciado' => 'Área de un triángulo con base 8cm y altura 5cm:', 'alternativas' => ['20 cm²', '30 cm²', '40 cm²', '13 cm²', '25 cm²'], 'correcta' => 0, 'dificultad' => 'facil'],
            ['enunciado' => '¿Cuál es el área de un círculo de radio 4cm? (π = 3.14)', 'alternativas' => ['50.24 cm²', '25.12 cm²', '12.56 cm²', '100.48 cm²', '75.36 cm²'], 'correcta' => 0, 'dificultad' => 'media'],
            ['enunciado' => 'Perímetro de un cuadrado de lado 7cm:', 'alternativas' => ['14 cm', '28 cm', '49 cm', '21 cm', '35 cm'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Volumen de un cubo de arista 3cm:', 'alternativas' => ['9 cm³', '18 cm³', '27 cm³', '36 cm³', '45 cm³'], 'correcta' => 2, 'dificultad' => 'facil'],
            ['enunciado' => 'Área de un rectángulo de 12cm × 5cm:', 'alternativas' => ['17 cm²', '34 cm²', '60 cm²', '24 cm²', '48 cm²'], 'correcta' => 2, 'dificultad' => 'facil'],
            ['enunciado' => 'Halla la hipotenusa de un triángulo rectángulo con catetos 6 y 8:', 'alternativas' => ['7', '9', '10', '12', '14'], 'correcta' => 2, 'dificultad' => 'media'],
            ['enunciado' => 'Área de un trapecio con bases 10cm y 6cm, altura 4cm:', 'alternativas' => ['24 cm²', '28 cm²', '32 cm²', '36 cm²', '40 cm²'], 'correcta' => 2, 'dificultad' => 'dificil'],
            ['enunciado' => '¿Cuántas caras tiene un cubo?', 'alternativas' => ['4', '6', '8', '10', '12'], 'correcta' => 1, 'dificultad' => 'facil'],
        ];

        $banco['Trigonometría'] = [
            ['enunciado' => '¿Cuánto vale sen(30°)?', 'alternativas' => ['0.5', '0.707', '1', '0.866', '0.25'], 'correcta' => 0, 'dificultad' => 'media'],
            ['enunciado' => '¿Cuánto vale cos(0°)?', 'alternativas' => ['0', '0.5', '1', '-1', '0.707'], 'correcta' => 2, 'dificultad' => 'facil'],
            ['enunciado' => 'tan(45°) es igual a:', 'alternativas' => ['0', '0.5', '1', '1.732', '0.866'], 'correcta' => 2, 'dificultad' => 'facil'],
            ['enunciado' => 'sen(90°) + cos(0°) =', 'alternativas' => ['0', '1', '2', '1.5', '0.5'], 'correcta' => 2, 'dificultad' => 'media'],
            ['enunciado' => 'Si sen(θ) = 0.6 y θ es agudo, ¿cuánto vale aproximadamente cos(θ)?', 'alternativas' => ['0.4', '0.6', '0.8', '0.5', '0.7'], 'correcta' => 2, 'dificultad' => 'dificil'],
            ['enunciado' => '¿Cuánto vale sen²(30°) + cos²(30°)?', 'alternativas' => ['0.5', '0.75', '1', '1.5', '0.25'], 'correcta' => 2, 'dificultad' => 'media'],
        ];

        $banco['Gramática'] = [
            ['enunciado' => '¿Cuál palabra está correctamente tildada?', 'alternativas' => ['Exámen', 'Examen', 'Exámenes', 'Examenes', 'Examén'], 'correcta' => 2, 'dificultad' => 'media'],
            ['enunciado' => '¿Cuál es el sujeto de "Los estudiantes resolvieron el examen"?', 'alternativas' => ['El examen', 'Los estudiantes', 'Resolvieron', 'Estudiantes', 'Examen'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Oración con error ortográfico:', 'alternativas' => ['Él llegó temprano', 'Tú eres mi amigo', 'El libro es interesante', 'Mi mamá me quiere', 'Él avión despegó'], 'correcta' => 4, 'dificultad' => 'media'],
            ['enunciado' => '¿Cuál es un adverbio de tiempo?', 'alternativas' => ['Rápidamente', 'Ayer', 'Bien', 'Lejos', 'Mucho'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La palabra "difícil" es aguda, grave o esdrújula?', 'alternativas' => ['Aguda', 'Grave', 'Esdrújula', 'Sobreesdrújula', 'Ninguna'], 'correcta' => 2, 'dificultad' => 'facil'],
            ['enunciado' => '¿Cuál de estas palabras es un adjetivo?', 'alternativas' => ['Correr', 'Hermoso', 'Rápidamente', 'León', 'Con'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => '¿Qué tipo de palabra es "pero"?', 'alternativas' => ['Preposición', 'Conjunción', 'Adverbio', 'Adjetivo', 'Sustantivo'], 'correcta' => 1, 'dificultad' => 'media'],
        ];

        $banco['Literatura peruana'] = [
            ['enunciado' => '"Los ríos profundos" fue escrito por:', 'alternativas' => ['Mario Vargas Llosa', 'José María Arguedas', 'César Vallejo', 'Alfredo Bryce Echenique', 'Julio Ramón Ribeyro'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Poeta peruano autor de "Los heraldos negros":', 'alternativas' => ['César Vallejo', 'Pablo Neruda', 'Octavio Paz', 'José Santos Chocano', 'Martín Adán'], 'correcta' => 0, 'dificultad' => 'media'],
            ['enunciado' => '"El mundo es ancho y ajeno" fue escrita por:', 'alternativas' => ['José María Arguedas', 'Ciro Alegría', 'Manuel Scorza', 'Mario Vargas Llosa', 'Alfredo Bryce Echenique'], 'correcta' => 1, 'dificultad' => 'dificil'],
            ['enunciado' => 'Novela de Mario Vargas Llosa:', 'alternativas' => ['La casa verde', 'Los ríos profundos', 'El mundo es ancho y ajeno', 'Yawar Fiesta', 'Todas las sangres'], 'correcta' => 0, 'dificultad' => 'media'],
            ['enunciado' => 'Obra cumbre de la literatura quechua anónima:', 'alternativas' => ['Popol Vuh', 'Ollantay', 'Los comentarios reales', 'El lazarillo de ciegos caminantes', 'Nueva corónica'], 'correcta' => 1, 'dificultad' => 'dificil'],
        ];

        $banco['Historia del Perú'] = [
            ['enunciado' => '¿En qué año ocurrió la independencia del Perú?', 'alternativas' => ['1810', '1821', '1824', '1820', '1815'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Cultura preincaica conocida como la "Ciudad Blanca" en la costa norte:', 'alternativas' => ['Inca', 'Moche', 'Chavín', 'Paracas', 'Nazca'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Ultimo inca del Tahuantinsuyo:', 'alternativas' => ['Huáscar', 'Atahualpa', 'Manco Inca', 'Túpac Amaru I', 'Túpac Amaru II'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Batalla que consolidó la independencia del Perú:', 'alternativas' => ['Junín', 'Ayacucho', 'Pichincha', 'Juncal', 'Maipú'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'Descubridor de Machu Picchu:', 'alternativas' => ['Hiram Bingham', 'Pedro de Candia', 'Francisco Pizarro', 'Hernando de Soto', 'Diego de Almagro'], 'correcta' => 0, 'dificultad' => 'facil'],
        ];

        $banco['Geografía'] = [
            ['enunciado' => 'Río más largo del Perú:', 'alternativas' => ['Amazonas', 'Marañón', 'Ucayali', 'Madre de Dios', 'Huallaga'], 'correcta' => 0, 'dificultad' => 'media'],
            ['enunciado' => 'Ciudad más poblada del Perú después de Lima:', 'alternativas' => ['Arequipa', 'Trujillo', 'Cusco', 'Piura', 'Chiclayo'], 'correcta' => 0, 'dificultad' => 'media'],
            ['enunciado' => 'Desierto más grande del Perú:', 'alternativas' => ['Sechura', 'Ica', 'Nazca', 'Paracas', 'Atacama'], 'correcta' => 0, 'dificultad' => 'facil'],
            ['enunciado' => 'Lago navegable más alto del mundo:', 'alternativas' => ['Titicaca', 'Junín', 'Parinacochas', 'Llanganuco', 'Sauce'], 'correcta' => 0, 'dificultad' => 'facil'],
            ['enunciado' => '¿En qué departamento se encuentra el Cañón del Colca?', 'alternativas' => ['Cusco', 'Arequipa', 'Puno', 'Ayacucho', 'Apurímac'], 'correcta' => 1, 'dificultad' => 'media'],
        ];

        $banco['Economía básica'] = [
            ['enunciado' => '¿Qué mide el PBI?', 'alternativas' => ['La inflación', 'La producción de bienes y servicios', 'El desempleo', 'Las exportaciones', 'La deuda externa'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => 'La inflación se refiere a:', 'alternativas' => ['Baja de precios', 'Aumento sostenido de precios', 'Aumento del empleo', 'Disminución de la producción', 'Aumento de exportaciones'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => '¿Qué significa oferta?', 'alternativas' => ['Cantidad que los consumidores quieren comprar', 'Cantidad que los productores quieren vender', 'El precio de los productos', 'El costo de producción', 'La demanda del mercado'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'La moneda oficial del Perú es:', 'alternativas' => ['Peso', 'Sol', 'Dólar', 'Euro', 'Bolívar'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'El impuesto general a las ventas (IGV) en Perú es:', 'alternativas' => ['10%', '16%', '18%', '21%', '15%'], 'correcta' => 2, 'dificultad' => 'media'],
            ['enunciado' => '¿Qué es la balanza comercial?', 'alternativas' => ['Diferencia entre exportaciones e importaciones', 'Total de ventas del país', 'El presupuesto nacional', 'La deuda con el extranjero', 'Las reservas del banco central'], 'correcta' => 0, 'dificultad' => 'media'],
            ['enunciado' => 'Política monetaria expansiva busca:', 'alternativas' => ['Reducir la inflación', 'Aumentar la actividad económica', 'Reducir el gasto público', 'Aumentar impuestos', 'Reducir salarios'], 'correcta' => 1, 'dificultad' => 'dificil'],
        ];

        $banco['Planteo de ecuaciones'] = [
            ['enunciado' => 'Juan tiene el doble de edad que Pedro. Si suman 36 años, ¿cuántos años tiene Juan?', 'alternativas' => ['12', '18', '24', '30', '36'], 'correcta' => 2, 'dificultad' => 'media'],
            ['enunciado' => 'Tres números consecutivos suman 48. ¿Cuál es el mayor?', 'alternativas' => ['15', '16', '17', '18', '19'], 'correcta' => 2, 'dificultad' => 'media'],
            ['enunciado' => 'Un padre tiene 40 años y su hijo 10. ¿En cuántos años el padre tendrá el doble de la edad del hijo?', 'alternativas' => ['10', '15', '20', '25', '30'], 'correcta' => 2, 'dificultad' => 'dificil'],
            ['enunciado' => 'La suma de dos números es 25 y su diferencia es 5. ¿Cuál es el mayor?', 'alternativas' => ['10', '12', '15', '18', '20'], 'correcta' => 2, 'dificultad' => 'media'],
            ['enunciado' => 'Si compro 5 lapiceros y me sobran S/4, o compro 7 lapiceros y me faltan S/8. ¿Cuánto cuesta cada lapicero?', 'alternativas' => ['S/4', 'S/5', 'S/6', 'S/7', 'S/8'], 'correcta' => 2, 'dificultad' => 'dificil'],
        ];

        $banco['Comprensión de textos'] = [
            ['enunciado' => 'En "El progreso tecnológico avanza sin pausa, transformando cada aspecto de nuestra vida cotidiana", la idea principal es que la tecnología:', 'alternativas' => ['Es costosa', 'Cambia constantemente nuestra vida', 'Debe regularse', 'Solo beneficia a algunos', 'Es peligrosa'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Del texto "La educación es la llave que abre puertas al futuro" se infiere que:', 'alternativas' => ['La educación es costosa', 'La educación permite mejores oportunidades', 'Solo hay puertas cerradas', 'Todos tienen educación', 'El futuro es incierto'], 'correcta' => 1, 'dificultad' => 'facil'],
            ['enunciado' => 'Texto: "La globalización ha acercado las economías del mundo, pero también ha profundizado las desigualdades". Se concluye que la globalización:', 'alternativas' => ['Es totalmente positiva', 'Tiene efectos contradictorios', 'Eliminó las diferencias', 'Solo afecta a los ricos', 'Es un proceso reciente'], 'correcta' => 1, 'dificultad' => 'media'],
            ['enunciado' => '"La ciencia no es solo un conjunto de conocimientos, sino una forma de pensar". Esta frase enfatiza:', 'alternativas' => ['El contenido científico', 'El método y la actitud científica', 'La complejidad de la ciencia', 'La utilidad de la ciencia', 'El avance tecnológico'], 'correcta' => 1, 'dificultad' => 'media'],
        ];

        // Preguntas extra
        $extras = [
            'Sinónimos y antónimos' => [
                ['enunciado' => 'Sinónimo de "afable":', 'alternativas' => ['Hostil', 'Amable', 'Grosero', 'Indiferente', 'Severo'], 'correcta' => 1, 'dificultad' => 'media'],
                ['enunciado' => 'Antónimo de "ocioso":', 'alternativas' => ['Perezoso', 'Trabajador', 'Vago', 'Inactivo', 'Descansado'], 'correcta' => 1, 'dificultad' => 'facil'],
                ['enunciado' => 'Sinónimo de "conciso":', 'alternativas' => ['Breve', 'Largo', 'Detallado', 'Extenso', 'Amplio'], 'correcta' => 0, 'dificultad' => 'facil'],
                ['enunciado' => 'Antónimo de "frágil":', 'alternativas' => ['Débil', 'Fuerte', 'Delicado', 'Quebradizo', 'Sutil'], 'correcta' => 1, 'dificultad' => 'facil'],
            ],
            'Sucesiones numéricas' => [
                ['enunciado' => 'Continúa: 2, 5, 10, 17, 26, __', 'alternativas' => ['35', '37', '39', '33', '41'], 'correcta' => 1, 'dificultad' => 'media'],
                ['enunciado' => 'Completa: 1, 2, 4, 8, 16, 32, __', 'alternativas' => ['48', '64', '56', '72', '40'], 'correcta' => 1, 'dificultad' => 'facil'],
                ['enunciado' => 'Sigue: 1, 3, 6, 10, 15, 21, __', 'alternativas' => ['25', '27', '28', '30', '26'], 'correcta' => 2, 'dificultad' => 'media'],
            ],
            'Geometría plana' => [
                ['enunciado' => 'Área de un cuadrado de diagonal 10cm:', 'alternativas' => ['25 cm²', '50 cm²', '75 cm²', '100 cm²', '125 cm²'], 'correcta' => 1, 'dificultad' => 'dificil'],
                ['enunciado' => 'Perímetro de un rectángulo de lados 8cm y 5cm:', 'alternativas' => ['13 cm', '26 cm', '40 cm', '20 cm', '36 cm'], 'correcta' => 1, 'dificultad' => 'facil'],
            ],
            'Comprensión de textos' => [
                ['enunciado' => '"El hábito del ahorro debe inculcarse desde la infancia". La palabra "inculcarse" puede reemplazarse por:', 'alternativas' => ['Olvidarse', 'Enseñarse', 'Comprarse', 'Aumentarse', 'Reducirse'], 'correcta' => 1, 'dificultad' => 'facil'],
                ['enunciado' => 'Texto: "Millones de personas carecen de acceso al agua potable, una crisis que requiere acción inmediata". El tema central es:', 'alternativas' => ['La contaminación del agua', 'La crisis del agua potable', 'El costo del agua', 'Las lluvias', 'Los océanos'], 'correcta' => 1, 'dificultad' => 'facil'],
            ],
            'Historia del Perú' => [
                ['enunciado' => 'Primer virrey del Perú:', 'alternativas' => ['Blasco Núñez Vela', 'Francisco Pizarro', 'Antonio de Mendoza', 'José de la Serna', 'Diego de Almagro'], 'correcta' => 0, 'dificultad' => 'dificil'],
                ['enunciado' => 'El Tahuantinsuyo se dividía en:', 'alternativas' => ['3 suyos', '4 suyos', '5 suyos', '2 suyos', '6 suyos'], 'correcta' => 1, 'dificultad' => 'facil'],
            ],
            'Planteo de ecuaciones' => [
                ['enunciado' => 'Un número más su triple es 48. ¿Cuál es el número?', 'alternativas' => ['10', '12', '14', '16', '18'], 'correcta' => 1, 'dificultad' => 'facil'],
                ['enunciado' => 'La mitad de un número más 10 es 25. ¿Cuál es el número?', 'alternativas' => ['20', '25', '30', '35', '40'], 'correcta' => 2, 'dificultad' => 'facil'],
            ],
        ];

        foreach ($extras as $concepto => $preguntas) {
            $banco[$concepto] = array_merge($banco[$concepto] ?? [], $preguntas);
        }

        return $banco;
    }

    private function crearMensajesAyuda(array $conceptos): void
    {
        $mensajes = [
            'Comprensión lectora' => 'Para mejorar en comprensión lectora, practica leyendo textos variados y resumiendo las ideas principales de cada párrafo.',
            'Sinónimos y antónimos' => 'Amplía tu vocabulario leyendo constantemente. Usa un diccionario de sinónimos.',
            'Analogías' => 'Identifica la relación entre las palabras: causa-efecto, parte-todo, género-especie, etc.',
            'Sucesiones numéricas' => 'Identifica el patrón de cambio entre términos: suma, resta, multiplicación o una combinación.',
            'Planteo de ecuaciones' => 'Traduce el lenguaje verbal a lenguaje algebraico. Identifica la incógnita y las relaciones.',
            'Porcentajes' => 'Recuerda: porcentaje = (parte / total) × 100. Usa regla de tres simple.',
            'Álgebra' => 'Repasa los principios básicos: despeje de ecuaciones, productos notables y factorización.',
            'Geometría plana' => 'Practica el cálculo de áreas y perímetros de figuras básicas. Aprende las fórmulas clave.',
            'Trigonometría' => 'Memoriza los valores de seno, coseno y tangente para ángulos notables: 0°, 30°, 45°, 60°, 90°.',
            'Gramática' => 'Repasa las reglas de tildación general, diacrítica y enfática.',
            'Literatura peruana' => 'Lee resúmenes de obras principales. Identifica autores y sus obras representativas.',
            'Comprensión de textos' => 'Identifica la idea principal, las ideas secundarias y el propósito del autor.',
            'Historia del Perú' => 'Crea una línea de tiempo con los hitos principales de la historia peruana.',
            'Geografía' => 'Estudia los mapas del Perú: regiones, ríos principales, departamentos y capitales.',
            'Economía básica' => 'Familiarízate con conceptos como oferta, demanda, inflación, PBI y balanza comercial.',
        ];

        foreach ($mensajes as $nombre => $texto) {
            $concepto = $conceptos[$nombre] ?? null;
            if ($concepto) {
                MensajeAyuda::updateOrCreate(
                    ['concepto_id' => $concepto->id],
                    ['texto' => $texto, 'umbral_porcentaje_acierto' => 60, 'activo' => true]
                );
            }
        }
        $this->command->info('✅ Mensajes de ayuda');
    }

    private function crearIntentoDemo(?User $usuario, ?Examen $examen, $preguntas): void
    {
        if (!$usuario || !$examen || $preguntas->isEmpty()) return;

        $intento = IntentoExamen::updateOrCreate(
            [
                'usuario_id' => $usuario->id,
                'examen_id' => $examen->id,
                'estado' => 'completado',
            ],
            [
                'area_academica_id' => $examen->area_academica_id,
                'carrera' => $examen->areaAcademica?->nombre,
                'fecha_inicio' => now()->subMinutes(15),
                'fecha_fin' => now()->subMinutes(5),
                'tiempo_empleado_seg' => 600,
            ]
        );

        $correctas = 0;
        foreach ($preguntas as $pregunta) {
            $correcta = $pregunta->alternativas()->where('es_correcta', true)->first();
            $eligeCorrectamente = rand(1, 100) <= 70;
            $elegida = $eligeCorrectamente
                ? $correcta
                : $pregunta->alternativas()->where('es_correcta', false)->inRandomOrder()->first();

            RespuestaUsuario::updateOrCreate(
                ['intento_id' => $intento->id, 'pregunta_id' => $pregunta->id],
                [
                    'alternativa_id_elegida' => $elegida?->id,
                    'es_correcta' => $eligeCorrectamente,
                    'tiempo_respuesta_seg' => rand(15, 90),
                ]
            );
            if ($eligeCorrectamente) $correctas++;
        }

        $total = count($preguntas);
        $intento->update([
            'puntaje_total' => $correctas,
            'puntaje_maximo' => $total,
            'aprobado' => $correctas >= ($total * 0.6),
        ]);

        $pregs = Pregunta::whereIn('id', $preguntas->pluck('id'))
            ->whereNotNull('concepto_id')
            ->get()
            ->keyBy('id');

        $conceptos = [];
        foreach ($intento->respuestas as $r) {
            $p = $pregs->get($r->pregunta_id);
            if (!$p?->concepto_id) continue;
            $conceptos[$p->concepto_id] ??= [
                'concepto_id' => $p->concepto_id,
                'preguntas_totales' => 0,
                'preguntas_correctas' => 0,
            ];
            $conceptos[$p->concepto_id]['preguntas_totales']++;
            if ($r->es_correcta) {
                $conceptos[$p->concepto_id]['preguntas_correctas']++;
            }
        }

        foreach ($conceptos as $d) {
            $pct = $d['preguntas_totales'] > 0
                ? round(($d['preguntas_correctas'] / $d['preguntas_totales']) * 100, 2)
                : 0;
            ResultadoConcepto::updateOrCreate(
                ['intento_id' => $intento->id, 'concepto_id' => $d['concepto_id']],
                [
                    'preguntas_totales' => $d['preguntas_totales'],
                    'preguntas_correctas' => $d['preguntas_correctas'],
                    'porcentaje_acierto' => $pct,
                ]
            );
        }

        $this->command->info("✅ Demo: {$usuario->name} — {$correctas}/{$total} correctas");
    }
}
