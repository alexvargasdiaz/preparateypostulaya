<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogo\Models\Categoria;
use Modules\Preguntas\Models\Concepto;
use Modules\Catalogo\Models\Examen;
use Modules\Catalogo\Models\Institucion;
use Modules\Catalogo\Models\TipoExamen;
use Modules\Examenes\Services\ExamenService;
use Modules\Preguntas\Models\Alternativa;
use Modules\Preguntas\Models\Pregunta;
use Modules\Rendicion\Models\IntentoExamen;
use Modules\Rendicion\Models\RespuestaUsuario;
use Modules\Rendicion\Models\ResultadoConcepto;
use Tests\TestCase;

class RendicionCalcularResultadosTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private IntentoExamen $intento;
    private Concepto $algebra;
    private Concepto $comprension;
    private Pregunta $pregAlgebra1;
    private Pregunta $pregAlgebra2;
    private Pregunta $pregAlgebra3;
    private Pregunta $pregComprension1;
    private Pregunta $pregComprension2;

    protected function setUp(): void
    {
        parent::setUp();

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

        $examen = Examen::create([
            'categoria_id' => $categoria->id,
            'titulo' => 'Simulacro de Admisión - Ingeniería',
            'descripcion' => 'Test',
            'tiempo_limite_min' => 20,
            'intentos_permitidos' => 99,
            'num_alternativas_default' => 5,
            'preguntas_por_intento' => 10,
            'activo' => true,
        ]);

        // ─── Conceptos ───────────────────────────────────────
        $this->algebra = Concepto::create(['nombre' => 'Álgebra', 'descripcion' => '']);
        $this->comprension = Concepto::create(['nombre' => 'Comprensión lectora', 'descripcion' => '']);

        // ─── 3 preguntas de Álgebra ──────────────────────────
        $this->pregAlgebra1 = $this->crearPregunta($examen, $this->algebra, 'Álgebra 1');
        $this->pregAlgebra2 = $this->crearPregunta($examen, $this->algebra, 'Álgebra 2');
        $this->pregAlgebra3 = $this->crearPregunta($examen, $this->algebra, 'Álgebra 3');

        // ─── 2 preguntas de Comprensión ──────────────────────
        $this->pregComprension1 = $this->crearPregunta($examen, $this->comprension, 'Comprensión 1');
        $this->pregComprension2 = $this->crearPregunta($examen, $this->comprension, 'Comprensión 2');

        // ─── Usuario ──────────────────────────────────────────
        $this->usuario = User::factory()->create([
            'name' => 'Estudiante Test',
            'email' => 'estudiante@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);

        // ─── Intento de examen ───────────────────────────────
        $this->intento = IntentoExamen::create([
            'usuario_id' => $this->usuario->id,
            'examen_id' => $examen->id,
            'carrera' => 'Ingeniería',
            'estado' => 'completado',
            'fecha_inicio' => now()->subMinutes(10),
        ]);
    }

    private function crearPregunta(Examen $examen, Concepto $concepto, string $enunciado): Pregunta
    {
        $pregunta = Pregunta::create([
            'examen_id' => $examen->id,
            'concepto_id' => $concepto->id,
            'enunciado' => $enunciado,
            'tipo' => 'opcion_multiple',
            'dificultad' => 'media',
            'orden' => 1,
            'activa' => true,
        ]);

        for ($j = 0; $j < 5; $j++) {
            Alternativa::create([
                'pregunta_id' => $pregunta->id,
                'texto' => "Alt " . chr(65 + $j),
                'es_correcta' => $j === 0,
                'orden' => $j,
            ]);
        }

        return $pregunta;
    }

    /**
     * Invoca el método privado calcularResultadosPorConcepto via reflection.
     */
    private function invocarCalcularResultados(IntentoExamen $intento): void
    {
        $service = app(ExamenService::class);
        $refMethod = new \ReflectionMethod($service, 'calcularResultadosPorConcepto');
        $refMethod->setAccessible(true);
        $refMethod->invoke($service, $intento);
    }

    /** @test */
    public function calcula_resultados_mezcla_de_aciertos_y_errores(): void
    {
        // Álgebra (3 preguntas): 2 correctas, 1 incorrecta → 66.67%
        $this->crearRespuesta($this->pregAlgebra1, true);
        $this->crearRespuesta($this->pregAlgebra2, true);
        $this->crearRespuesta($this->pregAlgebra3, false);

        // Comprensión (2 preguntas): 1 correcta, 1 incorrecta → 50%
        $this->crearRespuesta($this->pregComprension1, true);
        $this->crearRespuesta($this->pregComprension2, false);

        $this->invocarCalcularResultados($this->intento);

        $resultados = ResultadoConcepto::where('intento_id', $this->intento->id)
            ->with('concepto')
            ->get();

        $this->assertCount(2, $resultados);

        $algebraR = $resultados->firstWhere('concepto_id', $this->algebra->id);
        $this->assertNotNull($algebraR);
        $this->assertEquals(3, $algebraR->preguntas_totales);
        $this->assertEquals(2, $algebraR->preguntas_correctas);
        $this->assertEquals(round((2/3)*100, 2), (float) $algebraR->porcentaje_acierto);

        $comprensionR = $resultados->firstWhere('concepto_id', $this->comprension->id);
        $this->assertNotNull($comprensionR);
        $this->assertEquals(2, $comprensionR->preguntas_totales);
        $this->assertEquals(1, $comprensionR->preguntas_correctas);
        $this->assertEquals(50.0, (float) $comprensionR->porcentaje_acierto);
    }

    /** @test */
    public function calcula_100_porciento_cuando_todas_correctas(): void
    {
        $this->crearRespuesta($this->pregAlgebra1, true);
        $this->crearRespuesta($this->pregAlgebra2, true);
        $this->crearRespuesta($this->pregAlgebra3, true);

        $this->invocarCalcularResultados($this->intento);

        $resultado = ResultadoConcepto::where('intento_id', $this->intento->id)
            ->where('concepto_id', $this->algebra->id)
            ->first();

        $this->assertNotNull($resultado);
        $this->assertEquals(3, $resultado->preguntas_totales);
        $this->assertEquals(3, $resultado->preguntas_correctas);
        $this->assertEquals(100.0, (float) $resultado->porcentaje_acierto);
    }

    /** @test */
    public function calcula_0_porciento_cuando_todas_incorrectas(): void
    {
        $this->crearRespuesta($this->pregAlgebra1, false);
        $this->crearRespuesta($this->pregAlgebra2, false);

        $this->invocarCalcularResultados($this->intento);

        $resultado = ResultadoConcepto::where('intento_id', $this->intento->id)
            ->where('concepto_id', $this->algebra->id)
            ->first();

        $this->assertNotNull($resultado);
        $this->assertEquals(2, $resultado->preguntas_totales);
        $this->assertEquals(0, $resultado->preguntas_correctas);
        $this->assertEquals(0.0, (float) $resultado->porcentaje_acierto);
    }

    /** @test */
    public function ignora_preguntas_sin_concepto(): void
    {
        $examen = Examen::first();
        $pregSinConcepto = Pregunta::create([
            'examen_id' => $examen->id,
            'concepto_id' => null,
            'enunciado' => 'Pregunta sin concepto',
            'tipo' => 'opcion_multiple',
            'dificultad' => 'media',
            'orden' => 99,
            'activa' => true,
        ]);
        Alternativa::create([
            'pregunta_id' => $pregSinConcepto->id,
            'texto' => 'A',
            'es_correcta' => true,
            'orden' => 0,
        ]);

        $this->crearRespuesta($this->pregAlgebra1, true);
        $this->crearRespuesta($this->pregAlgebra2, true);
        $this->crearRespuesta($pregSinConcepto, true);

        $this->invocarCalcularResultados($this->intento);

        // Solo debe haber resultado para Álgebra (la preg sin concepto se ignora)
        $resultados = ResultadoConcepto::where('intento_id', $this->intento->id)->get();
        $this->assertCount(1, $resultados);

        $this->assertEquals(2, $resultados->first()->preguntas_totales);
    }

    /** @test */
    public function no_crea_resultados_si_no_hay_respuestas(): void
    {
        $this->invocarCalcularResultados($this->intento);

        $count = ResultadoConcepto::where('intento_id', $this->intento->id)->count();
        $this->assertEquals(0, $count);
    }

    /** @test */
    public function es_idempotente_puede_ejecutarse_varias_veces(): void
    {
        $this->crearRespuesta($this->pregAlgebra1, true);
        $this->crearRespuesta($this->pregAlgebra2, true);
        $this->crearRespuesta($this->pregAlgebra3, false);

        // Primera ejecución
        $this->invocarCalcularResultados($this->intento);
        $this->assertEquals(1, ResultadoConcepto::where('intento_id', $this->intento->id)->count());

        // Segunda ejecución (updateOrCreate, no debe duplicar)
        $this->invocarCalcularResultados($this->intento);
        $this->assertEquals(1, ResultadoConcepto::where('intento_id', $this->intento->id)->count());

        $resultado = ResultadoConcepto::where('intento_id', $this->intento->id)
            ->where('concepto_id', $this->algebra->id)
            ->first();
        $this->assertEquals(3, $resultado->preguntas_totales);
        $this->assertEquals(2, $resultado->preguntas_correctas);
    }

    /** @test */
    public function maneja_respuestas_con_dos_conceptos_correctas_e_incorrectas(): void
    {
        // Álgebra: 2 correctas, 0 incorrectas
        $this->crearRespuesta($this->pregAlgebra1, true);
        $this->crearRespuesta($this->pregAlgebra2, true);
        // Comprensión: 0 correctas, 2 incorrectas
        $this->crearRespuesta($this->pregComprension1, false);
        $this->crearRespuesta($this->pregComprension2, false);

        $this->invocarCalcularResultados($this->intento);

        $resultados = ResultadoConcepto::where('intento_id', $this->intento->id)
            ->orderBy('concepto_id')
            ->get();

        $this->assertCount(2, $resultados);

        $this->assertEquals(2, $resultados[0]->preguntas_totales);
        $this->assertEquals(2, $resultados[0]->preguntas_correctas);
        $this->assertEquals(100.0, (float) $resultados[0]->porcentaje_acierto);

        $this->assertEquals(2, $resultados[1]->preguntas_totales);
        $this->assertEquals(0, $resultados[1]->preguntas_correctas);
        $this->assertEquals(0.0, (float) $resultados[1]->porcentaje_acierto);
    }

    /** @test */
    public function redondea_porcentaje_a_dos_decimales(): void
    {
        // 1 correcta de 3 = 33.333...% → debe redondear a 33.33
        $this->crearRespuesta($this->pregAlgebra1, true);
        $this->crearRespuesta($this->pregAlgebra2, false);
        $this->crearRespuesta($this->pregAlgebra3, false);

        $this->invocarCalcularResultados($this->intento);

        $resultado = ResultadoConcepto::where('intento_id', $this->intento->id)
            ->where('concepto_id', $this->algebra->id)
            ->first();

        $this->assertNotNull($resultado);
        $this->assertEquals(33.33, (float) $resultado->porcentaje_acierto);
    }

    /** @test */
    public function carga_relacion_concepto_en_preguntas(): void
    {
        // El método usa ->with('concepto') en la consulta de preguntas
        $this->crearRespuesta($this->pregAlgebra1, true);

        $this->invocarCalcularResultados($this->intento);

        $resultado = ResultadoConcepto::where('intento_id', $this->intento->id)
            ->with('concepto')
            ->first();

        $this->assertNotNull($resultado->concepto);
        $this->assertEquals('Álgebra', $resultado->concepto->nombre);
    }

    private function crearRespuesta(Pregunta $pregunta, bool $esCorrecta): void
    {
        $altCorrecta = Alternativa::where('pregunta_id', $pregunta->id)
            ->where('es_correcta', true)
            ->first();

        $altElegida = $esCorrecta
            ? $altCorrecta
            : Alternativa::where('pregunta_id', $pregunta->id)
                ->where('es_correcta', false)
                ->first();

        RespuestaUsuario::create([
            'intento_id' => $this->intento->id,
            'pregunta_id' => $pregunta->id,
            'alternativa_id_elegida' => $altElegida->id,
            'es_correcta' => $esCorrecta,
        ]);
    }
}
