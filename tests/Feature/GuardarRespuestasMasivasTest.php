<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogo\Models\Categoria;
use Modules\Catalogo\Models\Examen;
use Modules\Catalogo\Models\Institucion;
use Modules\Catalogo\Models\TipoExamen;
use Modules\Preguntas\Models\Alternativa;
use Modules\Preguntas\Models\Concepto;
use Modules\Preguntas\Models\Pregunta;
use Modules\Rendicion\Models\IntentoExamen;
use Modules\Rendicion\Models\RespuestaUsuario;
use Tests\TestCase;

class GuardarRespuestasMasivasTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private User $otroUsuario;
    private IntentoExamen $intento;
    private array $preguntas;
    private array $correctas;
    private array $incorrectas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $tipo = TipoExamen::create([
            'slug' => 'admision-universitaria',
            'nombre' => 'Admisión Universitaria',
            'activo' => true,
        ]);

        $institucion = Institucion::create([
            'tipo_examen_id' => $tipo->id,
            'nombre' => 'Universidad de Prueba',
            'activo' => true,
        ]);

        $categoria = Categoria::create([
            'institucion_id' => $institucion->id,
            'nombre' => 'Ingeniería',
            'activo' => true,
        ]);

        $examen = Examen::create([
            'categoria_id' => $categoria->id,
            'titulo' => 'Simulacro de Prueba',
            'tiempo_limite_min' => 20,
            'preguntas_por_intento' => 10,
            'activo' => true,
        ]);

        $concepto = Concepto::create([
            'nombre' => 'Álgebra',
            'descripcion' => 'Preguntas sobre álgebra',
        ]);

        // 3 preguntas, cada una con alternativa correcta e incorrecta
        for ($i = 1; $i <= 3; $i++) {
            $pregunta = Pregunta::create([
                'examen_id' => $examen->id,
                'concepto_id' => $concepto->id,
                'enunciado' => "Pregunta {$i}",
                'tipo' => 'opcion_multiple',
                'dificultad' => 'facil',
                'orden' => $i,
                'activa' => true,
            ]);

            $correcta = Alternativa::create([
                'pregunta_id' => $pregunta->id,
                'texto' => 'Correcta',
                'es_correcta' => true,
                'orden' => 0,
            ]);
            $incorrecta = Alternativa::create([
                'pregunta_id' => $pregunta->id,
                'texto' => 'Incorrecta',
                'es_correcta' => false,
                'orden' => 1,
            ]);

            $this->preguntas[$i] = $pregunta;
            $this->correctas[$i] = $correcta;
            $this->incorrectas[$i] = $incorrecta;
        }

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

        $this->intento = IntentoExamen::create([
            'usuario_id' => $this->usuario->id,
            'examen_id' => $examen->id,
            'carrera' => 'Ingeniería',
            'estado' => 'en_curso',
            'fecha_inicio' => now()->subMinutes(10),
            'progreso_guardado' => [
                'preguntas_ids' => array_keys($this->preguntas),
            ],
        ]);
    }

    private function urlMasivo(int $intentoId): string
    {
        return route('examenes.guardar-masivo', $intentoId);
    }

    private function urlMasivoDiagnostico(int $intentoId): string
    {
        return route('diagnostico.guardar-masivo', $intentoId);
    }

    /** @test */
    public function guarda_varias_respuestas_en_una_sola_operacion(): void
    {
        $response = $this->actingAs($this->usuario)
            ->postJson($this->urlMasivo($this->intento->id), [
                'respuestas' => [
                    ['pregunta_id' => $this->preguntas[1]->id, 'alternativa_id_elegida' => $this->correctas[1]->id],
                    ['pregunta_id' => $this->preguntas[2]->id, 'alternativa_id_elegida' => $this->incorrectas[2]->id],
                    ['pregunta_id' => $this->preguntas[3]->id, 'alternativa_id_elegida' => $this->correctas[3]->id],
                ],
            ]);

        $response->assertOk();
        $response->assertJson(['saved' => 3]);

        $guardadas = RespuestaUsuario::where('intento_id', $this->intento->id)->get();

        $this->assertCount(3, $guardadas);
        $this->assertTrue($guardadas->firstWhere('pregunta_id', $this->preguntas[1]->id)->es_correcta);
        $this->assertFalse($guardadas->firstWhere('pregunta_id', $this->preguntas[2]->id)->es_correcta);
        $this->assertTrue($guardadas->firstWhere('pregunta_id', $this->preguntas[3]->id)->es_correcta);
    }

    /** @test */
    public function sobrescribe_una_respuesta_ya_guardada(): void
    {
        RespuestaUsuario::create([
            'intento_id' => $this->intento->id,
            'pregunta_id' => $this->preguntas[1]->id,
            'alternativa_id_elegida' => $this->incorrectas[1]->id,
            'es_correcta' => false,
        ]);

        $this->actingAs($this->usuario)
            ->postJson($this->urlMasivo($this->intento->id), [
                'respuestas' => [
                    ['pregunta_id' => $this->preguntas[1]->id, 'alternativa_id_elegida' => $this->correctas[1]->id],
                ],
            ])
            ->assertOk();

        $this->assertSame(
            1,
            RespuestaUsuario::where('intento_id', $this->intento->id)
                ->where('pregunta_id', $this->preguntas[1]->id)
                ->count(),
            'No debe crear respuestas duplicadas'
        );

        $guardada = RespuestaUsuario::where('intento_id', $this->intento->id)
            ->where('pregunta_id', $this->preguntas[1]->id)
            ->first();

        $this->assertEquals($this->correctas[1]->id, $guardada->alternativa_id_elegida);
        $this->assertTrue($guardada->es_correcta);
    }

    /** @test */
    public function guarda_respuestas_de_un_diagnostico(): void
    {
        $response = $this->actingAs($this->usuario)
            ->postJson($this->urlMasivoDiagnostico($this->intento->id), [
                'respuestas' => [
                    ['pregunta_id' => $this->preguntas[2]->id, 'alternativa_id_elegida' => $this->correctas[2]->id],
                ],
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('respuestas_usuario', [
            'intento_id' => $this->intento->id,
            'pregunta_id' => $this->preguntas[2]->id,
            'alternativa_id_elegida' => $this->correctas[2]->id,
        ]);
    }

    /** @test */
    public function guarda_lote_con_respuesta_sin_responder(): void
    {
        $response = $this->actingAs($this->usuario)
            ->postJson($this->urlMasivo($this->intento->id), [
                'respuestas' => [
                    ['pregunta_id' => $this->preguntas[1]->id, 'alternativa_id_elegida' => null],
                ],
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('respuestas_usuario', [
            'intento_id' => $this->intento->id,
            'pregunta_id' => $this->preguntas[1]->id,
            'alternativa_id_elegida' => null,
            'es_correcta' => null,
        ]);
    }

    /** @test */
    public function rechaza_lote_con_mas_de_200_respuestas(): void
    {
        $lote = collect(range(1, 201))->map(fn ($i) => [
            'pregunta_id' => $this->preguntas[1]->id,
            'alternativa_id_elegida' => $this->correctas[1]->id,
        ])->all();

        $this->actingAs($this->usuario)
            ->postJson($this->urlMasivo($this->intento->id), ['respuestas' => $lote])
            ->assertStatus(422);
    }

    /** @test */
    public function rechaza_pregunta_inexistente(): void
    {
        $this->actingAs($this->usuario)
            ->postJson($this->urlMasivo($this->intento->id), [
                'respuestas' => [
                    ['pregunta_id' => 999999, 'alternativa_id_elegida' => null],
                ],
            ])
            ->assertStatus(422);
    }

    /** @test */
    public function deniega_acceso_a_intento_de_otro_usuario(): void
    {
        $this->actingAs($this->otroUsuario)
            ->postJson($this->urlMasivo($this->intento->id), [
                'respuestas' => [
                    ['pregunta_id' => $this->preguntas[1]->id, 'alternativa_id_elegida' => $this->correctas[1]->id],
                ],
            ])
            ->assertStatus(403);
    }

    /** @test */
    public function rechaza_intento_ya_completado(): void
    {
        $this->intento->update(['estado' => 'completado']);

        $this->actingAs($this->usuario)
            ->postJson($this->urlMasivo($this->intento->id), [
                'respuestas' => [
                    ['pregunta_id' => $this->preguntas[1]->id, 'alternativa_id_elegida' => $this->correctas[1]->id],
                ],
            ])
            ->assertStatus(409);
    }

    /** @test */
    public function requiere_autenticacion(): void
    {
        $this->postJson($this->urlMasivo($this->intento->id), [
            'respuestas' => [
                ['pregunta_id' => $this->preguntas[1]->id, 'alternativa_id_elegida' => $this->correctas[1]->id],
            ],
        ])->assertStatus(401);
    }
}
