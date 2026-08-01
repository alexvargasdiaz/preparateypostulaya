<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notificaciones\Models\Notificacion;
use Modules\Notificaciones\Services\NotificationService;
use Tests\TestCase;

class NotificationServiceContarNoLeidasTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;
    private User $usuario;
    private User $otroUsuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(NotificationService::class);

        $this->usuario = User::factory()->create([
            'name' => 'Carlos',
            'email' => 'carlos@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);

        $this->otroUsuario = User::factory()->create([
            'name' => 'María',
            'email' => 'maria@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);
    }

    /** @test */
    public function retorna_0_cuando_no_hay_notificaciones(): void
    {
        $count = $this->service->contarNoLeidas($this->usuario->id);

        $this->assertEquals(0, $count);
    }

    /** @test */
    public function retorna_0_cuando_todas_estan_leidas(): void
    {
        Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => 'T1', 'tipo' => 'info', 'leida' => true]);
        Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => 'T2', 'tipo' => 'info', 'leida' => true]);

        $count = $this->service->contarNoLeidas($this->usuario->id);

        $this->assertEquals(0, $count);
    }

    /** @test */
    public function retorna_conteo_correcto_con_no_leidas(): void
    {
        Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => 'No leída 1', 'tipo' => 'info', 'leida' => false]);
        Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => 'Leída', 'tipo' => 'info', 'leida' => true]);
        Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => 'No leída 2', 'tipo' => 'info', 'leida' => false]);

        $count = $this->service->contarNoLeidas($this->usuario->id);

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function no_cuenta_notificaciones_de_otros_usuarios(): void
    {
        Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => 'Mía no leída', 'tipo' => 'info', 'leida' => false]);
        Notificacion::create(['usuario_id' => $this->otroUsuario->id, 'titulo' => 'De otro', 'tipo' => 'info', 'leida' => false]);

        $count = $this->service->contarNoLeidas($this->usuario->id);

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function funciona_con_muchas_notificaciones(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => "NL{$i}", 'tipo' => 'info', 'leida' => false]);
        }
        for ($i = 0; $i < 3; $i++) {
            Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => "L{$i}", 'tipo' => 'info', 'leida' => true]);
        }

        $count = $this->service->contarNoLeidas($this->usuario->id);

        $this->assertEquals(5, $count);
    }
}
