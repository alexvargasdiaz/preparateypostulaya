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
use Modules\Preguntas\Models\Alternativa;
use Modules\Preguntas\Models\Pregunta;
use Modules\Rendicion\Models\IntentoExamen;
use Modules\Rendicion\Models\RespuestaUsuario;
use Tests\TestCase;

class RendicionGuardarRespuestaTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private User $otroUsuario;
    private Examen $examen;
    private IntentoExamen $intento;
    private Pregunta $pregunta;
    private Alternativa $alternativaCorrecta;
    private Alternativa $alternativaIncorrecta;

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

        $concepto = Concepto::create([
            'nombre' => 'Álgebra',
            'descripcion' => 'Preguntas sobre álgebra',
        ]);

        // ─── Una pregunta con 5 alternativas ────────────────
        $this->pregunta = Pregunta::create([
            'examen_id' => $this->examen->id,
            'concepto_id' => $concepto->id,
            'enunciado' => '¿Cuánto es 2 + 2?',
            'tipo' => 'opcion_multiple',
            'dificultad' => 'facil',
            'orden' => 1,
            'activa' => true,
        ]);

        for ($j = 0; $j < 5; $j++) {
            $alt = Alternativa::create([
                'pregunta_id' => $this->pregunta->id,
                'texto' => "Alternativa " . chr(65 + $j),
                'es_correcta' => $j === 0,
                'orden' => $j,
            ]);
            if ($j === 0) {
                $this->alternativaCorrecta = $alt;
            } elseif ($j === 1) {
                $this->alternativaIncorrecta = $alt;
            }
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

        // ─── Intento de examen en curso ─────────────────────
        $this->intento = IntentoExamen::create([
            'usuario_id' => $this->usuario->id,
            'examen_id' => $this->examen->id,
            'carrera' => 'Ingeniería',
            'estado' => 'en_curso',
            'fecha_inicio' => now()->subMinutes(10),
            'progreso_guardado' => [
                'preguntas_ids' => [$this->pregunta->id],
            ],
        ]);
    }

    private function guardarUrl(int $intentoId): string
    {
        return route('examenes.guardar', $intentoId);
    }

    // ─── Happy path ─────────────────────────────────────────

    /** @test */
    public function guarda_respuesta_correcta_exitosamente(): void
    {
        $response = $this->actingAs($this->usuario)
            ->postJson($this->guardarUrl($this->intento->id), [
                'pregunta_id' => $this->pregunta->id,
                'alternativa_id_elegida' => $this->alternativaCorrecta->id,
            ]);

        $response->assertOk();
        $response->assertJson(['saved' => true]);

        // Verificar que se creó la respuesta en BD
        $respuesta = RespuestaUsuario::where('intento_id', $this->intento->id)
            ->where('pregunta_id', $this->pregunta->id)
            ->first();

        $this->assertNotNull($respuesta);
        $this->assertEquals($this->alternativaCorrecta->id, $respuesta->alternativa_id_elegida);
        $this->assertTrue($respuesta->es_correcta);
    }

    /** @test */
    public function guarda_respuesta_incorrecta_exitosamente(): void
    {
        $response = $this->actingAs($this->usuario)
            ->postJson($this->guardarUrl($this->intento->id), [
                'pregunta_id' => $this->pregunta->id,
                'alternativa_id_elegida' => $this->alternativaIncorrecta->id,
            ]);

        $response->assertOk();
        $response->assertJson(['saved' => true]);

        $respuesta = RespuestaUsuario::where('intento_id', $this->intento->id)
            ->where('pregunta_id', $this->pregunta->id)
            ->first();

        $this->assertNotNull($respuesta);
        $this->assertFalse($respuesta->es_correcta);
    }

    /** @test */
    public function guarda_respuesta_con_alternativa_nula_desmarcando(): void
    {
        // Primero guardar una respuesta
        $this->actingAs($this->usuario)
            ->postJson($this->guardarUrl($this->intento->id), [
                'pregunta_id' => $this->pregunta->id,
                'alternativa_id_elegida' => $this->alternativaCorrecta->id,
            ]);

        // Luego desmarcar (alternativa_id_elegida = null)
        $response = $this->actingAs($this->usuario)
            ->postJson($this->guardarUrl($this->intento->id), [
                'pregunta_id' => $this->pregunta->id,
                'alternativa_id_elegida' => null,
            ]);

        $response->assertOk();
        $response->assertJson(['saved' => true]);

        $respuesta = RespuestaUsuario::where('intento_id', $this->intento->id)
            ->where('pregunta_id', $this->pregunta->id)
            ->first();

        $this->assertNotNull($respuesta);
        $this->assertNull($respuesta->alternativa_id_elegida);
        $this->assertNull($respuesta->es_correcta);
    }

    /** @test */
    public function sobrescribe_respuesta_existente(): void
    {
        // Guardar respuesta incorrecta primero
        $this->actingAs($this->usuario)
            ->postJson($this->guardarUrl($this->intento->id), [
                'pregunta_id' => $this->pregunta->id,
                'alternativa_id_elegida' => $this->alternativaIncorrecta->id,
            ]);

        // Sobrescribir con respuesta correcta
        $response = $this->actingAs($this->usuario)
            ->postJson($this->guardarUrl($this->intento->id), [
                'pregunta_id' => $this->pregunta->id,
                'alternativa_id_elegida' => $this->alternativaCorrecta->id,
            ]);

        $response->assertOk();
        $response->assertJson(['saved' => true]);

        // Solo debe haber UNA respuesta para esta pregunta (updateOrCreate)
        $this->assertEquals(1, RespuestaUsuario::where('intento_id', $this->intento->id)
            ->where('pregunta_id', $this->pregunta->id)
            ->count());

        $respuesta = RespuestaUsuario::where('intento_id', $this->intento->id)
            ->where('pregunta_id', $this->pregunta->id)
            ->first();

        $this->assertTrue($respuesta->es_correcta);
        $this->assertEquals($this->alternativaCorrecta->id, $respuesta->alternativa_id_elegida);
    }

    // ─── Validación ────────────────────────────────────────

    /** @test */
    public function falla_validacion_si_falta_pregunta_id(): void
    {
        $response = $this->actingAs($this->usuario)
            ->postJson($this->guardarUrl($this->intento->id), [
                'alternativa_id_elegida' => $this->alternativaCorrecta->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('pregunta_id');
    }

    /** @test */
    public function falla_validacion_si_pregunta_id_no_existe(): void
    {
        $response = $this->actingAs($this->usuario)
            ->postJson($this->guardarUrl($this->intento->id), [
                'pregunta_id' => 99999,
                'alternativa_id_elegida' => $this->alternativaCorrecta->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('pregunta_id');
    }

    /** @test */
    public function falla_validacion_si_alternativa_id_no_existe(): void
    {
        $response = $this->actingAs($this->usuario)
            ->postJson($this->guardarUrl($this->intento->id), [
                'pregunta_id' => $this->pregunta->id,
                'alternativa_id_elegida' => 99999,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('alternativa_id_elegida');
    }

    // ─── Estado del intento ────────────────────────────────

    /** @test */
    public function rechaza_si_intento_esta_completado(): void
    {
        $this->intento->update(['estado' => 'completado']);

        $response = $this->actingAs($this->usuario)
            ->postJson($this->guardarUrl($this->intento->id), [
                'pregunta_id' => $this->pregunta->id,
                'alternativa_id_elegida' => $this->alternativaCorrecta->id,
            ]);

        $response->assertStatus(409);
        $response->assertJson(['error' => 'Este intento ya fue finalizado']);

        // No debe haber respuesta guardada
        $this->assertEquals(0, RespuestaUsuario::where('intento_id', $this->intento->id)->count());
    }

    /** @test */
    public function rechaza_si_intento_esta_abandonado(): void
    {
        $this->intento->update(['estado' => 'abandonado']);

        $response = $this->actingAs($this->usuario)
            ->postJson($this->guardarUrl($this->intento->id), [
                'pregunta_id' => $this->pregunta->id,
                'alternativa_id_elegida' => $this->alternativaCorrecta->id,
            ]);

        $response->assertStatus(409);
        $response->assertJson(['error' => 'Este intento ya fue finalizado']);

        $this->assertEquals(0, RespuestaUsuario::where('intento_id', $this->intento->id)->count());
    }

    // ─── Permisos ──────────────────────────────────────────

    /** @test */
    public function deniega_acceso_a_intento_de_otro_usuario(): void
    {
        $response = $this->actingAs($this->otroUsuario)
            ->postJson($this->guardarUrl($this->intento->id), [
                'pregunta_id' => $this->pregunta->id,
                'alternativa_id_elegida' => $this->alternativaCorrecta->id,
            ]);

        $response->assertStatus(403);

        // No debe haber respuesta guardada
        $this->assertEquals(0, RespuestaUsuario::where('intento_id', $this->intento->id)->count());
    }

    /** @test */
    public function requiere_autenticacion(): void
    {
        $response = $this->postJson($this->guardarUrl($this->intento->id), [
            'pregunta_id' => $this->pregunta->id,
            'alternativa_id_elegida' => $this->alternativaCorrecta->id,
        ]);

        $response->assertStatus(401);
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
            ->postJson($this->guardarUrl($this->intento->id), [
                'pregunta_id' => $this->pregunta->id,
                'alternativa_id_elegida' => $this->alternativaCorrecta->id,
            ]);

        $response->assertStatus(403);
    }

    // ─── Estado inalterado ─────────────────────────────────

    /** @test */
    public function no_modifica_estado_del_intento(): void
    {
        $this->actingAs($this->usuario)
            ->postJson($this->guardarUrl($this->intento->id), [
                'pregunta_id' => $this->pregunta->id,
                'alternativa_id_elegida' => $this->alternativaCorrecta->id,
            ]);

        $this->intento->refresh();
        $this->assertEquals('en_curso', $this->intento->estado);
        $this->assertNull($this->intento->fecha_fin);
        $this->assertNull($this->intento->puntaje_total);
    }

    /**
     * @test
     * @note El controlador NO valida que la pregunta pertenezca al examen del intento.
     *       Esto es un comportamiento actual que podría mejorarse agregando
     *       una validación de pertenencia en el futuro.
     */
    public function permite_guardar_respuesta_de_otro_examen_sin_validacion_pertenencia(): void
    {
        // Crear pregunta de otro examen (válida en BD pero no debería estar en este intento)
        $otroExamen = Examen::create([
            'categoria_id' => $this->examen->categoria_id,
            'titulo' => 'Otro examen',
            'descripcion' => 'Test',
            'tiempo_limite_min' => 20,
            'intentos_permitidos' => 99,
            'num_alternativas_default' => 5,
            'preguntas_por_intento' => 10,
            'aleatorizar_preguntas' => true,
            'aleatorizar_alternativas' => true,
            'activo' => true,
        ]);

        $preguntaOtroExamen = Pregunta::create([
            'examen_id' => $otroExamen->id,
            'enunciado' => 'Pregunta de otro examen',
            'tipo' => 'opcion_multiple',
            'dificultad' => 'media',
            'orden' => 1,
            'activa' => true,
        ]);

        $alt = Alternativa::create([
            'pregunta_id' => $preguntaOtroExamen->id,
            'texto' => 'Alternativa A',
            'es_correcta' => true,
            'orden' => 0,
        ]);

        // La validación solo checkea exists:preguntas,id — así que pasa
        // Pero la pregunta no pertenece a este examen — el controlador no valida eso explícitamente,
        // solo crea la respuesta. Esto documenta el comportamiento actual.
        $response = $this->actingAs($this->usuario)
            ->postJson($this->guardarUrl($this->intento->id), [
                'pregunta_id' => $preguntaOtroExamen->id,
                'alternativa_id_elegida' => $alt->id,
            ]);

        // El controlador no valida pertenencia de la pregunta al examen,
        // así que efectivamente guarda. Esto es un comportamiento actual.
        $response->assertOk();

        $respuesta = RespuestaUsuario::where('intento_id', $this->intento->id)
            ->where('pregunta_id', $preguntaOtroExamen->id)
            ->first();

        $this->assertNotNull($respuesta);
        $this->assertTrue($respuesta->es_correcta);
    }
}
