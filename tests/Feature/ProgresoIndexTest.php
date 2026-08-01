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
use Modules\Preguntas\Models\Alternativa;
use Modules\Preguntas\Models\Pregunta;
use Modules\Rendicion\Models\IntentoExamen;
use Modules\Rendicion\Models\ResultadoConcepto;
use Tests\TestCase;

class ProgresoIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private User $otroUsuario;
    private Institucion $institucion;
    private Examen $examen;
    private Concepto $algebra;
    private Concepto $comprension;

    protected function setUp(): void
    {
        parent::setUp();

        $tipo = TipoExamen::create([
            'slug' => 'admision-universitaria',
            'nombre' => 'Admisión Universitaria',
            'activo' => true,
        ]);

        $this->institucion = Institucion::create([
            'tipo_examen_id' => $tipo->id,
            'nombre' => 'Universidad de Prueba',
            'subtipo' => 'privada',
            'ciudad' => 'Lima',
            'activo' => true,
        ]);

        $categoria = Categoria::create([
            'institucion_id' => $this->institucion->id,
            'nombre' => 'Ingeniería',
            'descripcion_corta' => 'Carreras de Ingeniería',
            'orden' => 1,
            'activo' => true,
        ]);

        $this->examen = Examen::create([
            'categoria_id' => $categoria->id,
            'titulo' => 'Simulacro Admisión 2026',
            'descripcion' => 'Test',
            'tiempo_limite_min' => 20,
            'intentos_permitidos' => 99,
            'num_alternativas_default' => 5,
            'preguntas_por_intento' => 10,
            'activo' => true,
        ]);

        $this->algebra = Concepto::create(['nombre' => 'Álgebra', 'descripcion' => '']);
        $this->comprension = Concepto::create(['nombre' => 'Comprensión lectora', 'descripcion' => '']);

        $this->usuario = User::factory()->create([
            'name' => 'Carlos Estudiante',
            'email' => 'carlos@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);

        $this->otroUsuario = User::factory()->create([
            'name' => 'María Estudiante',
            'email' => 'maria@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);
    }

    /**
     * Crea un intento completado con resultados por concepto.
     */
    private function crearIntentoCompletado(
        User $usuario,
        int $puntaje,
        int $maximo,
        bool $aprobado,
        array $conceptosData = [],
        \DateTimeInterface $createdAt = null,
    ): IntentoExamen {
        $intento = IntentoExamen::create([
            'usuario_id' => $usuario->id,
            'examen_id' => $this->examen->id,
            'institucion_id' => $this->institucion->id,
            'carrera' => 'Ingeniería',
            'estado' => 'completado',
            'puntaje_total' => $puntaje,
            'puntaje_maximo' => $maximo,
            'aprobado' => $aprobado,
            'fecha_inicio' => now()->subDays(30),
        ]);

        if ($createdAt) {
            \Illuminate\Support\Facades\DB::table('intentos_examen')
                ->where('id', $intento->id)
                ->update(['created_at' => $createdAt]);
        }

        foreach ($conceptosData as $cd) {
            ResultadoConcepto::create([
                'intento_id' => $intento->id,
                'concepto_id' => $cd['concepto']->id,
                'preguntas_totales' => $cd['total'],
                'preguntas_correctas' => $cd['correctas'],
                'porcentaje_acierto' => $cd['total'] > 0
                    ? round(($cd['correctas'] / $cd['total']) * 100, 2)
                    : 0,
            ]);
        }

        return $intento;
    }

    /** @test */
    public function muestra_progreso_con_intentos_completados(): void
    {
        $this->crearIntentoCompletado($this->usuario, 7, 10, true, [
            ['concepto' => $this->algebra, 'total' => 6, 'correctas' => 5],
            ['concepto' => $this->comprension, 'total' => 4, 'correctas' => 2],
        ]);

        $response = $this->actingAs($this->usuario)
            ->get(route('progreso'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Progreso/Index')
            ->has('stats')
            ->has('evolucion')
            ->has('rendimiento_conceptos')
            ->has('recientes')
            ->where('tiene_datos', true)
        );
    }

    /** @test */
    public function sin_intentos_muestra_tiene_datos_false(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get(route('progreso'));

        $response->assertInertia(fn ($page) => $page
            ->component('Progreso/Index')
            ->where('tiene_datos', false)
            ->where('stats.total_examenes', 0)
            ->where('stats.total_aprobados', 0)
            ->where('stats.tasa_aprobacion', 0)
            ->where('stats.promedio_general', 0)
            ->where('stats.mejor_puntaje', 0)
            ->where('stats.total_preguntas', 0)
            ->where('stats.total_correctas', 0)
            ->where('stats.precision_global', 0)
            ->where('stats.mejor_racha', 0)
            ->has('evolucion', 0)
            ->has('rendimiento_conceptos', 0)
            ->has('recientes', 0)
        );
    }

    /** @test */
    public function calcula_stats_correctamente(): void
    {
        // 3 intentos: 2 aprobados (7/10=70%, 8/10=80%), 1 no aprobado (4/10=40%)
        $this->crearIntentoCompletado($this->usuario, 7, 10, true);
        $this->crearIntentoCompletado($this->usuario, 8, 10, true);
        $this->crearIntentoCompletado($this->usuario, 4, 10, false);

        $response = $this->actingAs($this->usuario)
            ->get(route('progreso'));

        $response->assertInertia(fn ($page) => $page
            ->component('Progreso/Index')
            ->where('stats.total_examenes', 3)
            ->where('stats.total_aprobados', 2)
            ->where('stats.tasa_aprobacion', 67)
            ->where('stats.promedio_general', 63)
            ->where('stats.mejor_puntaje', 80)
            ->where('stats.total_preguntas', 30)
            ->where('stats.total_correctas', 19)
            ->where('stats.precision_global', 63)
        );
    }

    /** @test */
    public function calcula_mejor_racha_de_aprobados(): void
    {
        // Secuencia: aprobado, aprobado, no aprobado, aprobado, aprobado, aprobado
        // Rachas: 2, luego 3 → mejor = 3
        $this->crearIntentoCompletado($this->usuario, 7, 10, true);
        $this->crearIntentoCompletado($this->usuario, 6, 10, true);
        $this->crearIntentoCompletado($this->usuario, 4, 10, false);
        $this->crearIntentoCompletado($this->usuario, 9, 10, true);
        $this->crearIntentoCompletado($this->usuario, 8, 10, true);
        $this->crearIntentoCompletado($this->usuario, 7, 10, true);

        $response = $this->actingAs($this->usuario)
            ->get(route('progreso'));

        $response->assertInertia(fn ($page) => $page
            ->component('Progreso/Index')
            ->where('stats.mejor_racha', 3)
        );
    }

    /** @test */
    public function evolucion_incluye_datos_ordenados_ascendente(): void
    {
        $this->crearIntentoCompletado(
            $this->usuario, 7, 10, true, [],
            now()->subDays(10),
        );
        $this->crearIntentoCompletado(
            $this->usuario, 9, 10, true, [],
            now()->subDays(5),
        );

        $response = $this->actingAs($this->usuario)
            ->get(route('progreso'));

        $evolucion = $response->getOriginalContent()['page']['props']['evolucion'];
        $this->assertCount(2, $evolucion);
        $this->assertEquals(70, $evolucion[0]['puntaje']); // 7/10 = 70%
        $this->assertEquals(90, $evolucion[1]['puntaje']); // 9/10 = 90%
    }

    /** @test */
    public function rendimiento_conceptos_agrega_datos_de_varios_intentos(): void
    {
        // Intento 1: Álgebra 4/6, Comprensión 2/4
        $this->crearIntentoCompletado($this->usuario, 6, 10, true, [
            ['concepto' => $this->algebra, 'total' => 6, 'correctas' => 4],
            ['concepto' => $this->comprension, 'total' => 4, 'correctas' => 2],
        ]);

        // Intento 2: Álgebra 5/6, Comprensión 1/4
        $this->crearIntentoCompletado($this->usuario, 6, 10, true, [
            ['concepto' => $this->algebra, 'total' => 6, 'correctas' => 5],
            ['concepto' => $this->comprension, 'total' => 4, 'correctas' => 1],
        ]);

        $response = $this->actingAs($this->usuario)
            ->get(route('progreso'));

        $conceptos = $response->getOriginalContent()['page']['props']['rendimiento_conceptos'];

        // Álgebra: (4+5)/(6+6) = 9/12 = 75%
        $alg = collect($conceptos)->firstWhere('nombre', 'Álgebra');
        $this->assertNotNull($alg);
        $this->assertEquals(12, $alg['total']);
        $this->assertEquals(9, $alg['correctas']);
        $this->assertEquals(75, $alg['porcentaje']);

        // Comprensión: (2+1)/(4+4) = 3/8 = 37.5% → round = 38
        $comp = collect($conceptos)->firstWhere('nombre', 'Comprensión lectora');
        $this->assertNotNull($comp);
        $this->assertEquals(8, $comp['total']);
        $this->assertEquals(3, $comp['correctas']);
        $this->assertEquals(round((3/8)*100), $comp['porcentaje']);
    }

    /** @test */
    public function recientes_muestra_ultimos_5_intentos(): void
    {
        for ($i = 0; $i < 7; $i++) {
            $this->crearIntentoCompletado($this->usuario, 5 + $i, 10, true);
        }

        $response = $this->actingAs($this->usuario)
            ->get(route('progreso'));

        $recientes = $response->getOriginalContent()['page']['props']['recientes'];
        $this->assertCount(5, $recientes);
    }

    /** @test */
    public function solo_incluye_intentos_completados_ignora_en_curso(): void
    {
        $this->crearIntentoCompletado($this->usuario, 7, 10, true);

        // Intento en curso (no debe aparecer)
        IntentoExamen::create([
            'usuario_id' => $this->usuario->id,
            'examen_id' => $this->examen->id,
            'carrera' => 'Ingeniería',
            'estado' => 'en_curso',
            'fecha_inicio' => now(),
        ]);

        $response = $this->actingAs($this->usuario)
            ->get(route('progreso'));

        $response->assertInertia(fn ($page) => $page
            ->component('Progreso/Index')
            ->where('stats.total_examenes', 1)
        );
    }

    /** @test */
    public function no_incluye_intentos_de_otros_usuarios(): void
    {
        $this->crearIntentoCompletado($this->usuario, 7, 10, true);
        $this->crearIntentoCompletado($this->otroUsuario, 9, 10, true);

        $response = $this->actingAs($this->usuario)
            ->get(route('progreso'));

        $response->assertInertia(fn ($page) => $page
            ->component('Progreso/Index')
            ->where('stats.total_examenes', 1)
        );
    }

    /** @test */
    public function requiere_autenticacion(): void
    {
        $response = $this->get(route('progreso'));
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
            ->get(route('progreso'));

        $response->assertStatus(403);
    }

    /** @test */
    public function recientes_tienen_formato_correcto(): void
    {
        $this->crearIntentoCompletado($this->usuario, 8, 10, true, [
            ['concepto' => $this->algebra, 'total' => 10, 'correctas' => 8],
        ]);

        $response = $this->actingAs($this->usuario)
            ->get(route('progreso'));

        $reciente = $response->getOriginalContent()['page']['props']['recientes'][0];
        $this->assertArrayHasKey('id', $reciente);
        $this->assertArrayHasKey('examen', $reciente);
        $this->assertArrayHasKey('institucion', $reciente);
        $this->assertArrayHasKey('fecha', $reciente);
        $this->assertArrayHasKey('puntaje', $reciente);
        $this->assertArrayHasKey('maximo', $reciente);
        $this->assertArrayHasKey('porcentaje', $reciente);
        $this->assertArrayHasKey('aprobado', $reciente);
        $this->assertEquals('Simulacro Admisión 2026', $reciente['examen']);
        $this->assertEquals('Universidad de Prueba', $reciente['institucion']);
        $this->assertEquals(80, $reciente['porcentaje']);
    }
}
