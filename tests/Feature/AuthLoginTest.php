<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->usuario = User::factory()->create([
            'name' => 'Estudiente Activo',
            'email' => 'activo@test.com',
            'password' => bcrypt('password123'),
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);
    }

    /** @test */
    public function muestra_formulario_login(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Login')
        );
    }

    /** @test */
    public function login_exitoso_con_credenciales_correctas(): void
    {
        $response = $this->post('/login', [
            'email' => 'activo@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->usuario);
    }

    /** @test */
    public function login_falla_con_credenciales_incorrectas(): void
    {
        $response = $this->post('/login', [
            'email' => 'activo@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function login_falla_con_email_inexistente(): void
    {
        $response = $this->post('/login', [
            'email' => 'noexiste@test.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function login_requiere_email_y_password(): void
    {
        $response = $this->post('/login', []);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    /** @test */
    public function login_con_remember_crea_sesion_persistente(): void
    {
        $response = $this->post('/login', [
            'email' => 'activo@test.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $this->assertNotNull($this->usuario->fresh()->remember_token);
    }

    /** @test */
    public function logout_cierra_sesion_y_redirige(): void
    {
        $response = $this->actingAs($this->usuario)
            ->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /** @test */
    public function logout_requiere_autenticacion(): void
    {
        $response = $this->post(route('logout'), [
            'X-Inertia' => 'true',
        ]);

        $response->assertRedirect('/login');
    }
}
