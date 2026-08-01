<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogo\Models\Categoria;
use Modules\Preguntas\Models\Concepto;
use Modules\Catalogo\Models\Examen;
use Modules\Catalogo\Models\Institucion;
use Modules\Catalogo\Models\TipoExamen;
use Modules\Notificaciones\Models\Notificacion;
use Modules\Preguntas\Models\Alternativa;
use Modules\Preguntas\Models\Pregunta;
use Modules\Rendicion\Models\IntentoExamen;
use Modules\Rendicion\Models\RespuestaUsuario;
use Modules\Rendicion\Models\ResultadoConcepto;
use Tests\TestCase;

class RendicionFinalizarTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private User $otroUsuario;
    private Examen $examen;
    private IntentoExamen $intento;
    private array $preguntas;
    private Concepto $concepto;

    protected function setUp(): void
    {
        parent::setUp();

        // Deshabilitar CSRF para pruebas HTTP
        $this->withoutMiddleware(ValidateCsrfToken::class);

        // ─── Crear catálogo base ────────────────────────────
        $tipo = TipoExamen::create([
            'slug' => 'admision-universitaria',
            'nombre' => 'Admisión Universitaria',
            'activo' => true,
        ]);

        $institucion = Institucion::create([
            'tipo_examen_id' => $tipo->id,
            'nombre' => 'Universidad de Prueba',
            'subtipo' => 'privada',
            'ciudad' => 'Lima',
            'activo' => true,
        ]);

        $categoria = Categoria::create([
            'institucion_id' => $institucion->id,
            'nombre' => 'Ingeniería',
            'descripcion_corta' => 'Carreras de Ingeniería',
            'orden' => 1,
            'activo' => true,
        ]);

        $this->examen = Examen::create([
            'categoria_id' => $categoria->id,
            'titulo' => 'Simulacro de Admisión - Ingeniería',
            'descripcion' => 'Test',
            'tiempo_limite_min' => 20,
            'intentos_permitidos' => 99,
            'num_alternativas_default' => 5,
            'preguntas_por_intento' => 10,
            'aleatorizar_preguntas' => true,
            'aleatorizar_alternativas' => true,
            'activo' => true,
        ]);

        // ─── Concepto ───────────────────────────────────────
        $this->concepto = Concepto::create([
            'nombre' => 'Álgebra',
            'descripcion' => 'Preguntas sobre álgebra',
        ]);

        $segundoConcepto = Concepto::create([
            'nombre' => 'Comprensión lectora',
            'descripcion' => 'Comprensión de textos',
        ]);

        // ─── Preguntas con alternativas ─────────────────────
        $this->preguntas = [];

        // 6 preguntas de Álgebra (todas con respuestas correctas en índice 0)
        for ($i = 1; $i <= 6; $i++) {
            $pregunta = Pregunta::create([
                'examen_id' => $this->examen->id,
                'concepto_id' => $this->concepto->id,
                'enunciado' => "Pregunta de álgebra {$i}",
                'tipo' => 'opcion_multiple',
                'dificultad' => 'media',
                'orden' => $i,
                'activa' => true,
            ]);
            $this->crearAlternativas($pregunta);
            $this->preguntas[] = $pregunta;
        }

        // 4 preguntas de Comprensión lectora (todas con respuestas correctas en índice 0)
        for ($i = 1; $i <= 4; $i++) {
            $pregunta = Pregunta::create([
                'examen_id' => $this->examen->id,
                'concepto_id' => $segundoConcepto->id,
                'enunciado' => "Pregunta de comprensión {$i}",
                'tipo' => 'opcion_multiple',
                'dificultad' => 'media',
                'orden' => 6 + $i,
                'activa' => true,
            ]);
            $this->crearAlternativas($pregunta);
            $this->preguntas[] = $pregunta;
        }

        // ─── Usuarios ───────────────────────────────────────
        $this->usuario = User::factory()->create([
            'name' => 'Estudiante Test',
            'email' => 'estudiante@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);

        $this->otroUsuario = User::factory()->create([
            'name' => 'Otro Estudiante',
            'email' => 'otro@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);

        // ─── Intento de examen (en_curso) con 10 preguntas ──
        $idsPreguntas = collect($this->preguntas)->pluck('id')->toArray();

        $this->intento = IntentoExamen::create([
            'usuario_id' => $this->usuario->id,
            'examen_id' => $this->examen->id,
            'carrera' => 'Ingeniería',
            'estado' => 'en_curso',
            'fecha_inicio' => now()->subMinutes(10),
            'progreso_guardado' => [
                'preguntas_ids' => $idsPreguntas,
            ],
        ]);
    }

    /**
     * Crea 5 alternativas para una pregunta, donde la primera (índice 0) es correcta.
     */
    private function crearAlternativas(Pregunta $pregunta): void
    {
        for ($j = 0; $j < 5; $j++) {
            Alternativa::create([
                'pregunta_id' => $pregunta->id,
                'texto' => "Alternativa " . chr(65 + $j) . " para pregunta {$pregunta->id}",
                'es_correcta' => $j === 0,
                'orden' => $j,
            ]);
        }
    }

    /**
     * Crea respuestas para un intento con un número específico de aciertos.
     * Las alternativas correctas tienen id = alternativa_correcta_id si no se reasignan.
     */
    private function crearRespuestas(IntentoExamen $intento, int $correctas, ?array $sobreescribirCorrectas = null): void
    {
        foreach ($this->preguntas as $i => $pregunta) {
            $esCorrecta = $i < $correctas;

            // Si se especifican correctas a sobreescribir, aplicar eso
            if ($sobreescribirCorrectas !== null && isset($sobreescribirCorrectas[$pregunta->id])) {
                $esCorrecta = $sobreescribirCorrectas[$pregunta->id];
            }

            $alternativaCorrecta = Alternativa::where('pregunta_id', $pregunta->id)
                ->where('es_correcta', true)
                ->first();

            $alternativaElegida = $esCorrecta
                ? $alternativaCorrecta
                : Alternativa::where('pregunta_id', $pregunta->id)
                    ->where('es_correcta', false)
                    ->inRandomOrder()
                    ->first();

            RespuestaUsuario::create([
                'intento_id' => $intento->id,
                'pregunta_id' => $pregunta->id,
                'alternativa_id_elegida' => $alternativaElegida->id,
                'es_correcta' => $esCorrecta,
                'tiempo_respuesta_seg' => 30,
            ]);
        }
    }

    /** @test */
    public function finaliza_exitosamente_con_respuestas_correctas(): void
    {
        // 7 correctas de 10 = 70% → aprobado (mínimo 60%)
        $this->crearRespuestas($this->intento, 7);

        $response = $this->actingAs($this->usuario)
            ->post(route('examenes.finalizar', $this->intento->id));

        // Verificar redirect a resultados
        $response->assertRedirect(route('resultados.show', $this->intento->id));

        // Recargar intento desde BD
        $this->intento->refresh();

        $this->assertEquals('completado', $this->intento->estado);
        $this->assertEquals(7, $this->intento->puntaje_total);
        $this->assertEquals(10, $this->intento->puntaje_maximo);
        $this->assertTrue($this->intento->aprobado);
        $this->assertNotNull($this->intento->fecha_fin);
        $this->assertNotNull($this->intento->tiempo_empleado_seg);

        // Verificar resultados por concepto
        $resultados = ResultadoConcepto::where('intento_id', $this->intento->id)->get();
        $this->assertCount(2, $resultados);

        // Álgebra (6 preguntas): primeras 7 respuestas son correctas → todas las de álgebra
        $algebraResultado = $resultados->firstWhere('concepto_id', $this->concepto->id);
        $this->assertNotNull($algebraResultado);
        $this->assertEquals(6, $algebraResultado->preguntas_totales);
        $this->assertEquals(6, $algebraResultado->preguntas_correctas);
        $this->assertEquals(100.0, $algebraResultado->porcentaje_acierto);
    }

    /** @test */
    public function finaliza_y_no_aprueba_con_puntaje_bajo(): void
    {
        // 3 correctas de 10 = 30% → no aprobado (mínimo 60%)
        $this->crearRespuestas($this->intento, 3);

        $response = $this->actingAs($this->usuario)
            ->post(route('examenes.finalizar', $this->intento->id));

        $response->assertRedirect(route('resultados.show', $this->intento->id));

        $this->intento->refresh();

        $this->assertEquals('completado', $this->intento->estado);
        $this->assertEquals(3, $this->intento->puntaje_total);
        $this->assertEquals(10, $this->intento->puntaje_maximo);
        $this->assertFalse($this->intento->aprobado);
    }

    /** @test */
    public function finaliza_con_puntaje_exacto_en_el_limite(): void
    {
        // 6 correctas de 10 = 60% → justo en el límite, aprueba
        $this->crearRespuestas($this->intento, 6);

        $response = $this->actingAs($this->usuario)
            ->post(route('examenes.finalizar', $this->intento->id));

        $response->assertRedirect(route('resultados.show', $this->intento->id));

        $this->intento->refresh();

        $this->assertTrue($this->intento->aprobado, '60% debe ser aprobado');
        $this->assertEquals(6, $this->intento->puntaje_total);
    }

    /** @test */
    public function redirige_si_intento_ya_esta_completado(): void
    {
        $this->intento->update(['estado' => 'completado']);

        $response = $this->actingAs($this->usuario)
            ->post(route('examenes.finalizar', $this->intento->id));

        $response->assertRedirect(route('resultados.show', $this->intento->id));

        // Verificar que no se sobreescribió
        $this->intento->refresh();
        $this->assertEquals('completado', $this->intento->estado);
    }

    /** @test */
    public function deniega_acceso_a_intento_de_otro_usuario(): void
    {
        $this->crearRespuestas($this->intento, 5);

        $response = $this->actingAs($this->otroUsuario)
            ->post(route('examenes.finalizar', $this->intento->id));

        $response->assertStatus(403);

        // El intento no debe haber cambiado
        $this->intento->refresh();
        $this->assertEquals('en_curso', $this->intento->estado);
    }

    /** @test */
    public function crea_notificacion_al_finalizar(): void
    {
        $this->crearRespuestas($this->intento, 7);

        $this->actingAs($this->usuario)
            ->post(route('examenes.finalizar', $this->intento->id));

        // Debe haber una notificación para el usuario
        $notificaciones = Notificacion::where('usuario_id', $this->usuario->id)->get();
        $this->assertCount(1, $notificaciones);

        $notificacion = $notificaciones->first();
        $this->assertStringContainsString('Aprobaste', $notificacion->titulo);
        $this->assertEquals(7, $notificacion->data['puntaje']);
        $this->assertEquals(10, $notificacion->data['maximo']);
        $this->assertTrue($notificacion->data['aprobado']);
    }

    /** @test */
    public function crea_notificacion_de_no_aprobado(): void
    {
        $this->crearRespuestas($this->intento, 4);

        $this->actingAs($this->usuario)
            ->post(route('examenes.finalizar', $this->intento->id));

        $notificaciones = Notificacion::where('usuario_id', $this->usuario->id)->get();
        $this->assertCount(1, $notificaciones);

        $notificacion = $notificaciones->first();
        $this->assertStringContainsString('Completaste', $notificacion->titulo);
        $this->assertFalse($notificacion->data['aprobado']);
    }

    /** @test */
    public function funciona_sin_resultados_por_concepto_si_no_hay_concepto_asignado(): void
    {
        // Crear preguntas SIN concepto
        $idsPreguntas = [];
        for ($i = 1; $i <= 3; $i++) {
            $pregunta = Pregunta::create([
                'examen_id' => $this->examen->id,
                'concepto_id' => null,
                'enunciado' => "Pregunta sin concepto {$i}",
                'tipo' => 'opcion_multiple',
                'dificultad' => 'facil',
                'orden' => 100 + $i,
                'activa' => true,
            ]);
            $this->crearAlternativas($pregunta);
            $idsPreguntas[] = $pregunta->id;
        }

        $intentoSinConcepto = IntentoExamen::create([
            'usuario_id' => $this->usuario->id,
            'examen_id' => $this->examen->id,
            'carrera' => 'Ingeniería',
            'estado' => 'en_curso',
            'fecha_inicio' => now()->subMinutes(10),
            'progreso_guardado' => ['preguntas_ids' => $idsPreguntas],
        ]);

        // Responder todas correctamente
        foreach ($idsPreguntas as $pid) {
            $alt = Alternativa::where('pregunta_id', $pid)->where('es_correcta', true)->first();
            RespuestaUsuario::create([
                'intento_id' => $intentoSinConcepto->id,
                'pregunta_id' => $pid,
                'alternativa_id_elegida' => $alt->id,
                'es_correcta' => true,
            ]);
        }

        $response = $this->actingAs($this->usuario)
            ->post(route('examenes.finalizar', $intentoSinConcepto->id));

        $response->assertRedirect(route('resultados.show', $intentoSinConcepto->id));

        $intentoSinConcepto->refresh();
        $this->assertEquals(3, $intentoSinConcepto->puntaje_total);
        $this->assertTrue($intentoSinConcepto->aprobado);

        // No debe haber resultados por concepto
        $resultados = ResultadoConcepto::where('intento_id', $intentoSinConcepto->id)->count();
        $this->assertEquals(0, $resultados);
    }

    /** @test */
    public function requiere_autenticacion(): void
    {
        $response = $this->post(route('examenes.finalizar', $this->intento->id));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function requiere_rol_estudiante(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'rol' => RolUsuario::Admin,
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('examenes.finalizar', $this->intento->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function calcula_correctamente_resultados_por_concepto(): void
    {
        // Preguntas 0-5: Álgebra (6 preguntas)
        // Preguntas 6-9: Comprensión lectora (4 preguntas)
        // Configurar: 4 correctas de Álgebra, 3 correctas de Comprensión
        $sobreescribir = [];
        foreach ($this->preguntas as $i => $p) {
            $sobreescribir[$p->id] = $i < 4 || ($i >= 6 && $i < 9); // primeras 4 álgebra + primeras 3 comprensión
        }

        $this->crearRespuestas($this->intento, 0, $sobreescribir);

        $this->actingAs($this->usuario)
            ->post(route('examenes.finalizar', $this->intento->id));

        $resultados = ResultadoConcepto::where('intento_id', $this->intento->id)
            ->with('concepto')
            ->get();

        $this->assertCount(2, $resultados);

        $algebra = $resultados->firstWhere('concepto.nombre', 'Álgebra');
        $this->assertNotNull($algebra);
        $this->assertEquals(6, $algebra->preguntas_totales);
        $this->assertEquals(4, $algebra->preguntas_correctas);
        $this->assertEquals(round((4/6)*100, 2), $algebra->porcentaje_acierto);

        $comprension = $resultados->firstWhere('concepto.nombre', 'Comprensión lectora');
        $this->assertNotNull($comprension);
        $this->assertEquals(4, $comprension->preguntas_totales);
        $this->assertEquals(3, $comprension->preguntas_correctas);
        $this->assertEquals(75.0, $comprension->porcentaje_acierto);
    }

    /** @test */
    public function funciona_con_todas_las_respuestas_incorrectas(): void
    {
        $this->crearRespuestas($this->intento, 0); // 0 correctas

        $response = $this->actingAs($this->usuario)
            ->post(route('examenes.finalizar', $this->intento->id));

        $response->assertRedirect(route('resultados.show', $this->intento->id));

        $this->intento->refresh();
        $this->assertEquals(0, $this->intento->puntaje_total);
        $this->assertFalse($this->intento->aprobado);
    }

    /** @test */
    public function funciona_con_todas_las_respuestas_correctas(): void
    {
        $this->crearRespuestas($this->intento, 10); // todas correctas

        $response = $this->actingAs($this->usuario)
            ->post(route('examenes.finalizar', $this->intento->id));

        $response->assertRedirect(route('resultados.show', $this->intento->id));

        $this->intento->refresh();
        $this->assertEquals(10, $this->intento->puntaje_total);
        $this->assertTrue($this->intento->aprobado);
    }

    /** @test */
    public function no_modifica_intento_si_finalizar_lanza_exception_en_notificacion(): void
    {
        // El controlador tiene un try/catch alrededor de NotificationService,
        // así que incluso si falla, el intento debe guardarse
        $this->crearRespuestas($this->intento, 5);

        $response = $this->actingAs($this->usuario)
            ->post(route('examenes.finalizar', $this->intento->id));

        $response->assertRedirect(route('resultados.show', $this->intento->id));

        $this->intento->refresh();
        $this->assertEquals('completado', $this->intento->estado);
        $this->assertEquals(5, $this->intento->puntaje_total);
    }

    /** @test */
    public function finalizar_es_idempotente_para_multiples_solicitudes(): void
    {
        $this->crearRespuestas($this->intento, 6);

        // Primera solicitud
        $this->actingAs($this->usuario)
            ->post(route('examenes.finalizar', $this->intento->id));

        $this->intento->refresh();
        $this->assertEquals('completado', $this->intento->estado);
        $puntajeOriginal = $this->intento->puntaje_total;
        $finOriginal = $this->intento->fecha_fin;

        // Segunda solicitud (debe redirigir sin modificar)
        $response = $this->actingAs($this->usuario)
            ->post(route('examenes.finalizar', $this->intento->id));

        $response->assertRedirect(route('resultados.show', $this->intento->id));

        $this->intento->refresh();
        $this->assertEquals($puntajeOriginal, $this->intento->puntaje_total);
        $this->assertEquals($finOriginal, $this->intento->fecha_fin);

        // Solo debe haber UNA notificación (no duplicada)
        $this->assertEquals(1, Notificacion::where('usuario_id', $this->usuario->id)->count());
    }

    /** @test */
    public function maneja_intentos_sin_progreso_guardado_usando_preguntas_de_bd(): void
    {
        // Intento sin progreso_guardado
        $intentoSinProgreso = IntentoExamen::create([
            'usuario_id' => $this->usuario->id,
            'examen_id' => $this->examen->id,
            'carrera' => 'Ingeniería',
            'estado' => 'en_curso',
            'fecha_inicio' => now()->subMinutes(5),
            'progreso_guardado' => null,
        ]);

        // Responder algunas preguntas del examen
        $alt1 = Alternativa::where('pregunta_id', $this->preguntas[0]->id)->where('es_correcta', true)->first();
        RespuestaUsuario::create([
            'intento_id' => $intentoSinProgreso->id,
            'pregunta_id' => $this->preguntas[0]->id,
            'alternativa_id_elegida' => $alt1->id,
            'es_correcta' => true,
        ]);

        $response = $this->actingAs($this->usuario)
            ->post(route('examenes.finalizar', $intentoSinProgreso->id));

        $response->assertRedirect(route('resultados.show', $intentoSinProgreso->id));

        $intentoSinProgreso->refresh();
        $this->assertEquals('completado', $intentoSinProgreso->estado);
        $this->assertEquals(1, $intentoSinProgreso->puntaje_total);
        // puntaje_maximo debe ser total de preguntas activas del examen (10)
        $this->assertEquals(10, $intentoSinProgreso->puntaje_maximo);
    }
}
