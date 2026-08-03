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
use Modules\Rendicion\Models\ResultadoConcepto;
use Tests\TestCase;

/**
 * Verifica que el cierre de un intento sea atómico ante doble submit o
 * requests concurrentes (finalizar + finalizar), evitando dobles cálculos.
 */
class ConcurrenciaFinalizarTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private IntentoExamen $intento;
    private Concepto $concepto;
    private array $preguntas;

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
            'preguntas_por_intento' => 2,
            'activo' => true,
        ]);

        $this->concepto = Concepto::create([
            'nombre' => 'Álgebra',
            'descripcion' => 'Preguntas sobre álgebra',
        ]);

        $this->preguntas = [];
        for ($i = 1; $i <= 2; $i++) {
            $pregunta = Pregunta::create([
                'examen_id' => $examen->id,
                'concepto_id' => $this->concepto->id,
                'enunciado' => "Pregunta {$i}",
                'tipo' => 'opcion_multiple',
                'dificultad' => 'facil',
                'orden' => $i,
                'activa' => true,
            ]);
            Alternativa::create([
                'pregunta_id' => $pregunta->id,
                'texto' => 'Correcta',
                'es_correcta' => true,
                'orden' => 0,
            ]);
            Alternativa::create([
                'pregunta_id' => $pregunta->id,
                'texto' => 'Incorrecta',
                'es_correcta' => false,
                'orden' => 1,
            ]);
            $this->preguntas[] = $pregunta;
        }

        $this->usuario = User::factory()->create([
            'name' => 'Estudiante Test',
            'email' => 'estudiante@test.com',
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
                'preguntas_ids' => collect($this->preguntas)->pluck('id')->toArray(),
            ],
        ]);
    }

    private function urlFinalizar(int $intentoId): string
    {
        return route('examenes.finalizar', $intentoId);
    }

    /** @test */
    public function doble_finalizar_calcula_resultados_una_sola_vez(): void
    {
        $primeraPregunta = $this->preguntas[0];
        $alternativaCorrecta = $primeraPregunta->alternativas()->where('es_correcta', true)->first();

        \Modules\Rendicion\Models\RespuestaUsuario::create([
            'intento_id' => $this->intento->id,
            'pregunta_id' => $primeraPregunta->id,
            'alternativa_id_elegida' => $alternativaCorrecta->id,
            'es_correcta' => true,
        ]);

        $this->actingAs($this->usuario)
            ->post($this->urlFinalizar($this->intento->id))
            ->assertRedirect(route('resultados.show', $this->intento->id));

        $this->actingAs($this->usuario)
            ->post($this->urlFinalizar($this->intento->id))
            ->assertRedirect(route('resultados.show', $this->intento->id));

        $this->intento->refresh();
        $this->assertSame('completado', $this->intento->estado);
        $this->assertNotNull($this->intento->puntaje_total);

        $this->assertSame(
            1,
            ResultadoConcepto::where('intento_id', $this->intento->id)->count(),
            'El segundo finalizar no debe recalcular ni duplicar resultados por concepto'
        );
    }

    /** @test */
    public function finalizar_no_recalcula_un_intento_abandonado(): void
    {
        $this->intento->update(['estado' => 'abandonado']);

        $this->actingAs($this->usuario)
            ->post($this->urlFinalizar($this->intento->id))
            ->assertRedirect(route('resultados.show', $this->intento->id));

        $this->intento->refresh();
        $this->assertSame('abandonado', $this->intento->estado);
        $this->assertNull($this->intento->puntaje_total);
        $this->assertSame(0, ResultadoConcepto::where('intento_id', $this->intento->id)->count());
    }
}
