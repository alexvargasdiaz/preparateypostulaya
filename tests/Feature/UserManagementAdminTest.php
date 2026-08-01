<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'super@admin.com',
            'rol' => RolUsuario::SuperAdmin,
            'estado' => 'activo',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin Normal',
            'email' => 'admin@admin.com',
            'rol' => RolUsuario::Admin,
            'estado' => 'activo',
        ]);
    }

    /** @test */
    public function admin_puede_ver_lista_usuarios(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.usuarios'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Usuarios/Index')
            ->has('usuarios.data')
        );
    }

    /** @test */
    public function super_admin_puede_ver_lista_usuarios(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.usuarios'));

        $response->assertOk();
    }

    /** @test */
    public function estudiante_no_puede_ver_lista_usuarios(): void
    {
        $estudiante = User::factory()->create([
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($estudiante)
            ->get(route('admin.usuarios'));

        $response->assertStatus(403);
    }

    /** @test */
    public function lista_usuarios_excluye_estudiantes(): void
    {
        User::factory()->create([
            'name' => 'Alumno Excluido',
            'email' => 'alumno@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.usuarios'));

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Usuarios/Index')
            ->has('usuarios.data', 2)
        );

        $emails = collect($response->getOriginalContent()['page']['props']['usuarios']['data'])->pluck('email');
        $this->assertCount(2, $emails);
        $this->assertNotContains('alumno@test.com', $emails);
    }

    /** @test */
    public function admin_puede_crear_usuario(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.usuarios.store'), [
                'name' => 'Nuevo Admin',
                'email' => 'nuevoadmin@test.com',
                'password' => 'password123',
                'rol' => 'admin',
            ]);

        $response->assertRedirect(route('admin.usuarios'));

        $this->assertDatabaseHas('users', [
            'email' => 'nuevoadmin@test.com',
            'name' => 'Nuevo Admin',
            'rol' => 'admin',
            'estado' => 'activo',
        ]);
    }

    /** @test */
    public function crear_usuario_requiere_campos_obligatorios(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.usuarios.store'), []);

        $response->assertSessionHasErrors(['name', 'email', 'password', 'rol']);
    }

    /** @test */
    public function admin_puede_editar_usuario(): void
    {
        $usuario = User::factory()->create([
            'name' => 'Original',
            'rol' => RolUsuario::Admin,
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.usuarios.actualizar', $usuario->id), [
                'name' => 'Actualizado',
                'email' => $usuario->email,
                'rol' => 'admin',
            ]);

        $response->assertRedirect(route('admin.usuarios'));

        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'name' => 'Actualizado',
        ]);
    }

    /** @test */
    public function admin_puede_eliminar_usuario(): void
    {
        $usuario = User::factory()->create([
            'name' => 'A Eliminar',
            'rol' => RolUsuario::Admin,
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.usuarios.eliminar', $usuario->id));

        $response->assertRedirect(route('admin.usuarios'));

        $this->assertDatabaseMissing('users', ['id' => $usuario->id]);
    }

    /** @test */
    public function admin_no_puede_eliminarse_a_si_mismo(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.usuarios.eliminar', $this->admin->id));

        $response->assertSessionHas('error', 'No puedes eliminar tu propia cuenta.');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    /** @test */
    public function admin_puede_aprobar_usuario_pendiente(): void
    {
        $pendiente = User::factory()->create([
            'name' => 'Pendiente',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.usuarios.aprobar', $pendiente->id));

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $pendiente->id,
            'estado' => 'activo',
            'aprobado_por' => $this->admin->id,
        ]);
    }

    /** @test */
    public function aprobar_usuario_ya_activo_devuelve_error(): void
    {
        $activo = User::factory()->create([
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.usuarios.aprobar', $activo->id));

        $response->assertSessionHas('error');
    }

    /** @test */
    public function admin_puede_rechazar_usuario_pendiente(): void
    {
        $pendiente = User::factory()->create([
            'name' => 'A Rechazar',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.usuarios.rechazar', $pendiente->id));

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $pendiente->id,
            'estado' => 'rechazado',
        ]);
    }

    /** @test */
    public function requiere_autenticacion_para_admin(): void
    {
        $response = $this->get(route('admin.usuarios'));

        $response->assertRedirect('/login');
    }
}
