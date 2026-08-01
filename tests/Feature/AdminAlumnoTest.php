<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notificaciones\Models\Notificacion;
use Tests\TestCase;

class AdminAlumnoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->admin = User::factory()->create([
            'name' => 'Admin Alumnos',
            'email' => 'admin@test.com',
            'rol' => RolUsuario::Admin,
            'estado' => 'activo',
        ]);
    }

    /** @test */
    public function admin_puede_ver_lista_alumnos(): void
    {
        User::factory()->create([
            'name' => 'Alumno Test',
            'email' => 'alumno@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.alumnos'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Alumnos/Index')
            ->has('alumnos.data', 1)
        );
    }

    /** @test */
    public function admin_puede_aprobar_alumno_pendiente(): void
    {
        $pendiente = User::factory()->create([
            'name' => 'Pendiente Aprobar',
            'email' => 'pendiente@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.alumnos.aprobar', $pendiente->id));

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $pendiente->id,
            'estado' => 'activo',
            'aprobado_por' => $this->admin->id,
        ]);
    }

    /** @test */
    public function aprobar_alumno_crea_notificacion(): void
    {
        $pendiente = User::factory()->create([
            'rol' => RolUsuario::Estudiante,
            'estado' => 'pendiente',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.alumnos.aprobar', $pendiente->id));

        $this->assertDatabaseHas('notifications', [
            'usuario_id' => $pendiente->id,
            'tipo' => 'exito',
        ]);
    }

    /** @test */
    public function admin_puede_rechazar_alumno_pendiente(): void
    {
        $pendiente = User::factory()->create([
            'name' => 'A Rechazar',
            'email' => 'rechazar@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.alumnos.rechazar', $pendiente->id));

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $pendiente->id,
            'estado' => 'rechazado',
        ]);
    }

    /** @test */
    public function admin_puede_crear_alumno(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.alumnos.store'), [
                'name' => 'Alumno Nuevo',
                'email' => 'nuevo@test.com',
                'password' => 'password123',
            ]);

        $response->assertRedirect(route('admin.alumnos'));

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@test.com',
            'name' => 'Alumno Nuevo',
            'rol' => 'estudiante',
            'estado' => 'activo',
        ]);
    }

    /** @test */
    public function admin_puede_eliminar_alumno(): void
    {
        $alumno = User::factory()->create([
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.alumnos.eliminar', $alumno->id));

        $response->assertRedirect(route('admin.alumnos'));

        $this->assertDatabaseMissing('users', ['id' => $alumno->id]);
    }

    /** @test */
    public function estudiante_no_puede_acceder_a_admin_alumnos(): void
    {
        $estudiante = User::factory()->create([
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($estudiante)
            ->get(route('admin.alumnos'));

        $response->assertStatus(403);
    }

    /** @test */
    public function requiere_autenticacion(): void
    {
        $response = $this->get(route('admin.alumnos'));

        $response->assertRedirect('/login');
    }
}
