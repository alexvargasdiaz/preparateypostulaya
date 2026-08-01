<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PerfilTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->usuario = User::factory()->create([
            'name' => 'Carlos Alumno',
            'email' => 'carlos@test.com',
            'password' => bcrypt('currentpassword'),
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);
    }

    /** @test */
    public function muestra_perfil(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get(route('perfil.show'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('MiPerfil/Index')
            ->has('usuario')
        );
    }

    /** @test */
    public function actualiza_datos_basicos(): void
    {
        $response = $this->actingAs($this->usuario)
            ->put(route('perfil.update'), [
                'name' => 'Carlos Actualizado',
                'email' => $this->usuario->email,
                'whatsapp_numero' => '999111222',
            ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $this->usuario->id,
            'name' => 'Carlos Actualizado',
            'whatsapp_numero' => '999111222',
        ]);
    }

    /** @test */
    public function actualiza_password_con_current_password_correcto(): void
    {
        $response = $this->actingAs($this->usuario)
            ->put(route('perfil.update'), [
                'name' => $this->usuario->name,
                'email' => $this->usuario->email,
                'current_password' => 'currentpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response->assertSessionHas('success');
    }

    /** @test */
    public function falla_cambio_password_si_current_password_incorrecto(): void
    {
        $response = $this->actingAs($this->usuario)
            ->put(route('perfil.update'), [
                'name' => $this->usuario->name,
                'email' => $this->usuario->email,
                'current_password' => 'wrongpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response->assertSessionHasErrors('current_password');
    }

    /** @test */
    public function falla_cambio_password_sin_current_password(): void
    {
        $response = $this->actingAs($this->usuario)
            ->put(route('perfil.update'), [
                'name' => $this->usuario->name,
                'email' => $this->usuario->email,
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response->assertSessionHasErrors('current_password');
    }

    /** @test */
    public function sube_foto_perfil(): void
    {
        Storage::fake('public');

        $foto = UploadedFile::fake()->image('perfil.jpg');

        $response = $this->actingAs($this->usuario)
            ->post(route('perfil.update-foto'), [
                'foto' => $foto,
            ]);

        $response->assertSessionHas('success');

        $this->assertNotNull($this->usuario->fresh()->foto);
        Storage::disk('public')->assertExists($this->usuario->fresh()->foto);
    }

    /** @test */
    public function elimina_foto_perfil(): void
    {
        Storage::fake('public');

        $foto = UploadedFile::fake()->image('perfil.jpg');
        $path = $foto->store('perfiles', 'public');
        $this->usuario->update(['foto' => $path]);

        $response = $this->actingAs($this->usuario)
            ->delete(route('perfil.destroy-foto'));

        $response->assertSessionHas('success');
        $this->assertNull($this->usuario->fresh()->foto);
        Storage::disk('public')->assertMissing($path);
    }

    /** @test */
    public function requiere_autenticacion(): void
    {
        $response = $this->get(route('perfil.show'));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function estudiante_pendiente_redirigido_a_pendiente(): void
    {
        $pendiente = User::factory()->create([
            'estado' => 'pendiente',
            'rol' => RolUsuario::Estudiante,
        ]);

        $response = $this->actingAs($pendiente)
            ->get(route('perfil.show'));

        $response->assertRedirect(route('pendiente'));
    }
}
