<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogo\Models\Categoria;
use Modules\Catalogo\Models\Examen;
use Modules\Catalogo\Models\Institucion;
use Modules\Catalogo\Models\TipoExamen;
use Modules\Rendicion\Models\IntentoExamen;
use Tests\TestCase;

class HistorialIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private User $otroUsuario;
    private Institucion $institucion;
    private Examen $examen;
    private IntentoExamen $intentoReciente;
    private IntentoExamen $intentoAntiguo;

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
            'nombre' => 'Universidad Nacional Mayor de San Marcos',
            'subtipo' => 'publica',
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
            'titulo' => 'Simulacro Admisión 2026 - Ingeniería',
            'descripcion' => 'Test',
            'tiempo_limite_min' => 20,
            'intentos_permitidos' => 99,
            'num_alternativas_default' => 5,
            'preguntas_por_intento' => 10,
            'activo' => true,
        ]);

        // ─── Usuarios ───────────────────────────────────────
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

        // ─── Intentos para el usuario principal ─────────────
        $this->intentoAntiguo = IntentoExamen::create([
            'usuario_id' => $this->usuario->id,
            'examen_id' => $this->examen->id,
            'institucion_id' => $this->institucion->id,
            'carrera' => 'Ingeniería',
            'estado' => 'completado',
            'puntaje_total' => 7,
            'puntaje_maximo' => 10,
            'aprobado' => true,
            'fecha_inicio' => now()->subDays(30),
        ]);
        // Forzar created_at antiguo via query builder para evitar serialización Carbon
        \Illuminate\Support\Facades\DB::table('intentos_examen')
            ->where('id', $this->intentoAntiguo->id)
            ->update(['created_at' => now()->subDays(30)]);

        $this->intentoReciente = IntentoExamen::create([
            'usuario_id' => $this->usuario->id,
            'examen_id' => $this->examen->id,
            'institucion_id' => $this->institucion->id,
            'carrera' => 'Ingeniería',
            'estado' => 'completado',
            'puntaje_total' => 9,
            'puntaje_maximo' => 10,
            'aprobado' => true,
            'fecha_inicio' => now()->subDays(1),
        ]);
        \Illuminate\Support\Facades\DB::table('intentos_examen')
            ->where('id', $this->intentoReciente->id)
            ->update(['created_at' => now()->subDays(1)]);

        // ─── Intento de otro usuario (no debe aparecer) ─────
        $otroIntento = IntentoExamen::create([
            'usuario_id' => $this->otroUsuario->id,
            'examen_id' => $this->examen->id,
            'institucion_id' => $this->institucion->id,
            'carrera' => 'Ingeniería',
            'estado' => 'completado',
            'puntaje_total' => 5,
            'puntaje_maximo' => 10,
            'aprobado' => false,
            'fecha_inicio' => now()->subDays(15),
        ]);
        \Illuminate\Support\Facades\DB::table('intentos_examen')
            ->where('id', $otroIntento->id)
            ->update(['created_at' => now()->subDays(15)]);
    }

    /** @test */
    public function lista_intentos_del_usuario(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get(route('historial'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Historial/Index')
            ->has('intentos', 2)
        );
    }

    /** @test */
    public function lista_intentos_en_orden_descendente_por_created_at(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get(route('historial'));

        $response->assertInertia(fn ($page) => $page
            ->component('Historial/Index')
            ->has('intentos', 2)
        );

        // Verificar orden descendente via props
        $props = $response->getOriginalContent()['page']['props']['intentos'];
        $this->assertCount(2, $props);
        $fechas = collect($props)->pluck('created_at')->toArray();
        $this->assertEquals(
            $fechas,
            collect($fechas)->sortDesc()->values()->toArray(),
            'Los intentos deben estar ordenados por created_at descendente'
        );
    }

    /** @test */
    public function no_incluye_intentos_de_otros_usuarios(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get(route('historial'));

        $response->assertInertia(fn ($page) => $page
            ->component('Historial/Index')
            ->has('intentos', 2)
        );

        // Solo los intentos del usuario (no el de otroUsuario)
        $ids = collect($response->getOriginalContent()['page']['props']['intentos'])
            ->pluck('usuario_id')
            ->unique()
            ->toArray();
        $this->assertCount(1, $ids);
        $this->assertContains($this->usuario->id, $ids);
        $this->assertNotContains($this->otroUsuario->id, $ids);
    }

    /** @test */
    public function muestra_intentos_vacio_si_no_hay_ninguno(): void
    {
        // Usuario sin intentos
        $usuarioNuevo = User::factory()->create([
            'name' => 'Nuevo',
            'email' => 'nuevo@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($usuarioNuevo)
            ->get(route('historial'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Historial/Index')
            ->has('intentos', 0)
        );
    }

    /** @test */
    public function incluye_relacion_institucion(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get(route('historial'));

        $response->assertInertia(fn ($page) => $page
            ->component('Historial/Index')
            ->has('intentos.0.institucion', fn ($i) => $i
                ->where('nombre', 'Universidad Nacional Mayor de San Marcos')
                ->etc()
            )
        );
    }

    /** @test */
    public function incluye_relacion_examen(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get(route('historial'));

        $response->assertInertia(fn ($page) => $page
            ->component('Historial/Index')
            ->has('intentos.0.examen', fn ($e) => $e
                ->where('titulo', 'Simulacro Admisión 2026 - Ingeniería')
                ->etc()
            )
        );
    }

    /** @test */
    public function intentos_incluyen_estado_puntaje_y_aprobado(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get(route('historial'));

        $response->assertInertia(fn ($page) => $page
            ->component('Historial/Index')
            ->has('intentos', 2)
        );

        // Verificar datos sin depender del orden
        $intentos = collect($response->getOriginalContent()['page']['props']['intentos']);

        $reciente = $intentos->firstWhere('id', $this->intentoReciente->id);
        $this->assertNotNull($reciente);
        $this->assertEquals('completado', $reciente['estado']);
        $this->assertEquals(9, $reciente['puntaje_total']);
        $this->assertEquals(10, $reciente['puntaje_maximo']);
        $this->assertTrue($reciente['aprobado']);

        $antiguo = $intentos->firstWhere('id', $this->intentoAntiguo->id);
        $this->assertNotNull($antiguo);
        $this->assertEquals(7, $antiguo['puntaje_total']);
        $this->assertTrue($antiguo['aprobado']);
    }

    /** @test */
    public function requiere_autenticacion(): void
    {
        $response = $this->get(route('historial'));

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
            ->get(route('historial'));

        $response->assertStatus(403);
    }
}
