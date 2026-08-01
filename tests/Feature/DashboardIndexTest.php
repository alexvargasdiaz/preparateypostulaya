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

class DashboardIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $estudiante;
    private User $admin;
    private User $pendiente;
    private Examen $examen;

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

        // ─── Usuarios ───────────────────────────────────────
        $this->estudiante = User::factory()->create([
            'name' => 'Carlos Estudiante',
            'email' => 'estudiante@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'rol' => RolUsuario::Admin,
            'estado' => 'activo',
        ]);

        $this->pendiente = User::factory()->create([
            'name' => 'Pendiente Test',
            'email' => 'pendiente@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'pendiente',
        ]);

        // ─── 3 intentos para el estudiante ──────────────────
        for ($i = 0; $i < 3; $i++) {
            IntentoExamen::create([
                'usuario_id' => $this->estudiante->id,
                'examen_id' => $this->examen->id,
                'institucion_id' => $institucion->id,
                'carrera' => 'Ingeniería',
                'estado' => 'completado',
                'puntaje_total' => 5 + $i,
                'puntaje_maximo' => 10,
                'aprobado' => (5 + $i) >= 6,
                'fecha_inicio' => now()->subDays(10 - $i),
            ]);
        }
    }

    /** @test */
    public function estudiante_ve_dashboard_con_ultimos_intentos(): void
    {
        $response = $this->actingAs($this->estudiante)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('ultimosIntentos', 3)
        );
    }

    /** @test */
    public function estudiante_limita_a_5_intentos_recientes(): void
    {
        // Crear 8 intentos en total (3 existentes + 5 nuevos)
        for ($i = 0; $i < 5; $i++) {
            IntentoExamen::create([
                'usuario_id' => $this->estudiante->id,
                'examen_id' => $this->examen->id,
                'carrera' => 'Ingeniería',
                'estado' => 'completado',
                'puntaje_total' => 8,
                'puntaje_maximo' => 10,
                'aprobado' => true,
                'fecha_inicio' => now()->subHours($i),
            ]);
        }

        $response = $this->actingAs($this->estudiante)
            ->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('ultimosIntentos', 5)
        );
    }

    /** @test */
    public function estudiante_sin_intentos_ve_array_vacio(): void
    {
        $usuarioNuevo = User::factory()->create([
            'name' => 'Nuevo Estudiante',
            'email' => 'nuevo@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($usuarioNuevo)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('ultimosIntentos', 0)
        );
    }

    /** @test */
    public function admin_no_tiene_ultimos_intentos(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->missing('ultimosIntentos')
        );
    }

    /** @test */
    public function usuario_pendiente_es_redirigido_por_middleware(): void
    {
        $response = $this->actingAs($this->pendiente)
            ->get(route('dashboard'));

        // El middleware UsuarioActivoMiddleware redirige al usuario pendiente
        // a la ruta 'pendiente' antes de que llegue al controlador
        $response->assertRedirect(route('pendiente'));
    }

    /** @test */
    public function requiere_autenticacion(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function intentos_incluyen_relaciones_institucion_y_examen(): void
    {
        $response = $this->actingAs($this->estudiante)
            ->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('ultimosIntentos.0.institucion', fn ($i) => $i
                ->where('nombre', 'Universidad de Prueba')
                ->etc()
            )
            ->has('ultimosIntentos.0.examen', fn ($e) => $e
                ->where('titulo', 'Simulacro Admisión 2026')
                ->etc()
            )
        );
    }

    /** @test */
    public function intentos_estan_en_orden_descendente(): void
    {
        $response = $this->actingAs($this->estudiante)
            ->get(route('dashboard'));

        $intentos = collect($response->getOriginalContent()['page']['props']['ultimosIntentos']);
        $fechas = $intentos->pluck('created_at')->toArray();
        $this->assertEquals(
            $fechas,
            collect($fechas)->sortDesc()->values()->toArray(),
            'Los intentos deben estar ordenados por created_at descendente'
        );
    }
}
