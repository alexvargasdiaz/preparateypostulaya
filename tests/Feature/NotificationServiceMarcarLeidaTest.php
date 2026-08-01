<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notificaciones\Models\Notificacion;
use Modules\Notificaciones\Services\NotificationService;
use Tests\TestCase;

class NotificationServiceMarcarLeidaTest extends TestCase
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
    public function marca_como_leida_exitosamente(): void
    {
        $notificacion = Notificacion::create([
            'usuario_id' => $this->usuario->id,
            'titulo' => 'Notificación de prueba',
            'tipo' => 'info',
            'leida' => false,
        ]);

        $result = $this->service->marcarLeida($notificacion->id, $this->usuario->id);

        $this->assertTrue($result);

        $notificacion->refresh();
        $this->assertTrue($notificacion->leida);
        $this->assertNotNull($notificacion->leida_at);
    }

    /** @test */
    public function retorna_false_si_notificacion_no_existe(): void
    {
        $result = $this->service->marcarLeida(99999, $this->usuario->id);

        $this->assertFalse($result);
    }

    /** @test */
    public function retorna_false_si_notificacion_es_de_otro_usuario(): void
    {
        $notificacion = Notificacion::create([
            'usuario_id' => $this->otroUsuario->id,
            'titulo' => 'Noti de otro usuario',
            'tipo' => 'info',
            'leida' => false,
        ]);

        $result = $this->service->marcarLeida($notificacion->id, $this->usuario->id);

        $this->assertFalse($result);
        $this->assertDatabaseHas('notifications', [
            'id' => $notificacion->id,
            'leida' => false,
        ]);
    }

    /** @test */
    public function notificacion_ya_leida_sigue_siendo_leida(): void
    {
        $notificacion = Notificacion::create([
            'usuario_id' => $this->usuario->id,
            'titulo' => 'Ya leída',
            'tipo' => 'info',
            'leida' => true,
            'leida_at' => now()->subDay(),
        ]);

        $leidaAtOriginal = $notificacion->leida_at;

        $result = $this->service->marcarLeida($notificacion->id, $this->usuario->id);

        $this->assertTrue($result);

        $notificacion->refresh();
        $this->assertTrue($notificacion->leida);
        $this->assertEquals(
            $leidaAtOriginal->format('Y-m-d H:i:s'),
            $notificacion->leida_at->format('Y-m-d H:i:s'),
        );
    }

    /** @test */
    public function no_modifica_notificaciones_de_otros_usuarios(): void
    {
        $notiPropia = Notificacion::create([
            'usuario_id' => $this->usuario->id,
            'titulo' => 'Propia',
            'tipo' => 'info',
            'leida' => false,
        ]);
        $notiOtro = Notificacion::create([
            'usuario_id' => $this->otroUsuario->id,
            'titulo' => 'De otro',
            'tipo' => 'info',
            'leida' => false,
        ]);

        $result = $this->service->marcarLeida($notiPropia->id, $this->usuario->id);

        $this->assertTrue($result);

        $notiPropia->refresh();
        $this->assertTrue($notiPropia->leida);

        $notiOtro->refresh();
        $this->assertFalse($notiOtro->leida);
    }
}
