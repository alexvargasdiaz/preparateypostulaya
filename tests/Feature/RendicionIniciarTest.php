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
use Tests\TestCase;

class RendicionIniciarTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Examen $examen;
    private array $preguntas;

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
            'descripcion' => 'Test de práctica',
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

        // ─── 10 preguntas activas con alternativas ──────────
        $this->preguntas = [];
        for ($i = 1; $i <= 10; $i++) {
            $pregunta = Pregunta::create([
                'examen_id' => $this->examen->id,
                'concepto_id' => $concepto->id,
                'enunciado' => "Pregunta {$i}",
                'tipo' => 'opcion_multiple',
                'dificultad' => 'media',
                'orden' => $i,
                'activa' => true,
            ]);

            for ($j = 0; $j < 5; $j++) {
                Alternativa::create([
                    'pregunta_id' => $pregunta->id,
                    'texto' => "Alternativa " . chr(65 + $j),
                    'es_correcta' => $j === 0,
                    'orden' => $j,
                ]);
            }

            $this->preguntas[] = $pregunta;
        }

        // ─── Usuario estudiante ─────────────────────────────
        $this->usuario = User::factory()->create([
            'name' => 'Estudiante Test',
            'email' => 'estudiante@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);
    }

    /** @test */
    public function crea_intento_exitosamente_con_preguntas_aleatorias(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get(route('examenes.iniciar', ['examen_id' => $this->examen->id]));

        // Debe redirigir a la pantalla de rendición
        $response->assertRedirect();

        // Obtener el intento creado
        $intento = IntentoExamen::where('usuario_id', $this->usuario->id)
            ->where('examen_id', $this->examen->id)
            ->first();

        $this->assertNotNull($intento, 'Debe haberse creado un intento');
        $this->assertEquals('en_curso', $intento->estado);
        $this->assertNotNull($intento->fecha_inicio);
        $this->assertEquals($this->examen->id, $intento->examen_id);
        $this->assertEquals('Ingeniería', $intento->carrera);
        $this->assertNotNull($intento->institucion_id);

        // Verificar que se guardaron las preguntas seleccionadas
        $this->assertNotNull($intento->progreso_guardado);
        $this->assertCount(10, $intento->progreso_guardado['preguntas_ids']);
        $this->assertEquals($this->examen->id, $intento->examen_id);

        // Verificar redirect a rendir con el intento creado
        $response->assertRedirect(route('examenes.rendir', ['intento' => $intento->id]));
    }

    /** @test */
    public function redirige_con_error_si_falta_examen_id(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get(route('examenes.iniciar'));

        $response->assertRedirect(route('examenes.index'));
        $response->assertSessionHas('error', 'Debes seleccionar un examen.');
    }

    /** @test */
    public function redirige_con_error_si_examen_no_esta_activo(): void
    {
        $this->examen->update(['activo' => false]);

        $response = $this->actingAs($this->usuario)
            ->get(route('examenes.iniciar', ['examen_id' => $this->examen->id]));

        $response->assertRedirect(route('examenes.index'));
        $response->assertSessionHas('error', 'Este examen no está disponible.');
    }

    /** @test */
    public function redirige_con_error_si_examen_no_tiene_preguntas(): void
    {
        // Crear un examen sin preguntas
        $categoria = Categoria::where('nombre', 'Ingeniería')->first();
        $examenSinPreguntas = Examen::create([
            'categoria_id' => $categoria->id,
            'titulo' => 'Examen Vacío',
            'descripcion' => 'Sin preguntas',
            'tiempo_limite_min' => 20,
            'intentos_permitidos' => 99,
            'num_alternativas_default' => 5,
            'preguntas_por_intento' => 10,
            'activo' => true,
        ]);

        $response = $this->actingAs($this->usuario)
            ->get(route('examenes.iniciar', ['examen_id' => $examenSinPreguntas->id]));

        $response->assertRedirect(route('examenes.index'));
        $response->assertSessionHas('error', 'Este examen aún no tiene preguntas.');
    }

    /** @test */
    public function selecciona_solo_preguntas_activas_ignorando_inactivas(): void
    {
        // Desactivar 5 de las 10 preguntas
        for ($i = 0; $i < 5; $i++) {
            $this->preguntas[$i]->update(['activa' => false]);
        }

        $response = $this->actingAs($this->usuario)
            ->get(route('examenes.iniciar', ['examen_id' => $this->examen->id]));

        $response->assertRedirect();

        $intento = IntentoExamen::where('usuario_id', $this->usuario->id)
            ->where('examen_id', $this->examen->id)
            ->first();

        $this->assertNotNull($intento);

        // Solo debe tener 5 preguntas (las activas) — preguntas_por_intento es 10
        // pero solo hay 5 activas, así que el límite debe ser min(10, 5) = 5
        $this->assertCount(5, $intento->progreso_guardado['preguntas_ids']);

        // Ninguna de las preguntas inactivas debe estar en la selección
        $idsActivos = collect($this->preguntas)->slice(5)->pluck('id')->toArray();
        foreach ($intento->progreso_guardado['preguntas_ids'] as $pid) {
            $this->assertContains($pid, $idsActivos, "No debe incluir preguntas inactivas");
        }
    }

    /** @test */
    public function respeta_preguntas_por_intento_menor_al_total(): void
    {
        // Configurar examen para que muestre solo 3 preguntas por intento
        $this->examen->update(['preguntas_por_intento' => 3]);

        $response = $this->actingAs($this->usuario)
            ->get(route('examenes.iniciar', ['examen_id' => $this->examen->id]));

        $response->assertRedirect();

        $intento = IntentoExamen::where('usuario_id', $this->usuario->id)
            ->where('examen_id', $this->examen->id)
            ->first();

        $this->assertNotNull($intento);
        $this->assertCount(3, $intento->progreso_guardado['preguntas_ids']);
    }

    /** @test */
    public function genera_subconjuntos_aleatorios_distintos_en_cada_intento(): void
    {
        $this->examen->update(['preguntas_por_intento' => 5]);

        // Crear 3 intentos consecutivos
        $idsPorIntento = [];
        for ($i = 0; $i < 3; $i++) {
            // Necesitamos un usuario nuevo cada vez porque el método crea el intento
            // pero no impide crear múltiples intentos — depende de la lógica del negocio
            $response = $this->actingAs($this->usuario)
                ->get(route('examenes.iniciar', ['examen_id' => $this->examen->id]));

            $response->assertRedirect();

            $intento = IntentoExamen::where('usuario_id', $this->usuario->id)
                ->where('examen_id', $this->examen->id)
                ->orderBy('id', 'desc')
                ->first();

            $idsPorIntento[] = $intento->progreso_guardado['preguntas_ids'];
        }

        // Verificar que al menos un par de intentos tenga preguntas diferentes
        // (Con 5 de 10 preguntas, la probabilidad de que dos sean idénticas es baja)
        $todasIguales = true;
        for ($i = 1; $i < count($idsPorIntento); $i++) {
            if ($idsPorIntento[$i] !== $idsPorIntento[0]) {
                $todasIguales = false;
                break;
            }
        }

        $this->assertFalse($todasIguales, 'Los subconjuntos deberían ser diferentes (aleatorios)');
    }

    /** @test */
    public function falla_si_examen_no_existe(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get(route('examenes.iniciar', ['examen_id' => 99999]));

        $response->assertStatus(404);
    }

    /** @test */
    public function requiere_autenticacion(): void
    {
        $response = $this->get(route('examenes.iniciar', ['examen_id' => $this->examen->id]));

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
            ->get(route('examenes.iniciar', ['examen_id' => $this->examen->id]));

        $response->assertStatus(403);
    }

    /** @test */
    public function no_crea_intento_si_hay_error_de_validacion(): void
    {
        // Falta examen_id
        $this->actingAs($this->usuario)
            ->get(route('examenes.iniciar'));

        $this->assertEquals(0, IntentoExamen::where('usuario_id', $this->usuario->id)->count());
    }

    /** @test */
    public function selecciona_todas_las_preguntas_si_hay_menos_que_preguntas_por_intento(): void
    {
        // Examen con preguntas_por_intento = 20 pero solo 3 preguntas activas
        $this->examen->update(['preguntas_por_intento' => 20]);

        // Desactivar 7 preguntas, dejando solo 3 activas
        for ($i = 0; $i < 7; $i++) {
            $this->preguntas[$i]->update(['activa' => false]);
        }

        $response = $this->actingAs($this->usuario)
            ->get(route('examenes.iniciar', ['examen_id' => $this->examen->id]));

        $response->assertRedirect();

        $intento = IntentoExamen::where('usuario_id', $this->usuario->id)
            ->where('examen_id', $this->examen->id)
            ->first();

        $this->assertNotNull($intento);

        // min(20, 3) = 3, debe seleccionar las 3 preguntas activas disponibles
        $this->assertCount(3, $intento->progreso_guardado['preguntas_ids']);
    }
}
