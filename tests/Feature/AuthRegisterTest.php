<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notificaciones\Models\Notificacion;
use Tests\TestCase;

class AuthRegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /** @test */
    public function muestra_formulario_registro(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
        );
    }

    /** @test */
    public function registro_exitoso_crea_usuario_pendiente(): void
    {
        $response = $this->post('/register', [
            'name' => 'Nuevo Alumno',
            'email' => 'nuevo@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'whatsapp_numero' => '999888777',
        ]);

        $response->assertRedirect(route('pendiente'));

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@test.com',
            'name' => 'Nuevo Alumno',
            'rol' => 'estudiante',
            'estado' => 'pendiente',
            'whatsapp_numero' => '999888777',
        ]);

        $this->assertAuthenticated();
    }

    /** @test */
    public function registro_crea_usuario_sin_whatsapp_opcional(): void
    {
        $response = $this->post('/register', [
            'name' => 'Sin WhatsApp',
            'email' => 'sinwhatsapp@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('pendiente'));

        $this->assertDatabaseHas('users', [
            'email' => 'sinwhatsapp@test.com',
            'whatsapp_numero' => null,
        ]);
    }

    /** @test */
    public function registro_falla_si_email_ya_existe(): void
    {
        User::factory()->create(['email' => 'existente@test.com']);

        $response = $this->post('/register', [
            'name' => 'Duplicado',
            'email' => 'existente@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function registro_requiere_nombre_email_y_password(): void
    {
        $response = $this->post('/register', []);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    /** @test */
    public function registro_requiere_password_min_8_caracteres(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function registro_requiere_confirmacion_password(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function registro_notifica_a_admins(): void
    {
        $admin = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo',
        ]);

        $this->post('/register', [
            'name' => 'Nuevo Alumno',
            'email' => 'nuevo@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('notifications', [
            'usuario_id' => $admin->id,
            'tipo' => 'info',
        ]);
    }

    /** @test */
    public function registro_funciona_sin_admins_para_notificar(): void
    {
        $response = $this->post('/register', [
            'name' => 'Solo Alumno',
            'email' => 'solo@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('pendiente'));
        $this->assertAuthenticated();
    }
}
