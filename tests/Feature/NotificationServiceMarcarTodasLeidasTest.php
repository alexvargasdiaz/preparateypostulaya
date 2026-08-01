<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notificaciones\Models\Notificacion;
use Modules\Notificaciones\Services\NotificationService;
use Tests\TestCase;

class NotificationServiceMarcarTodasLeidasTest extends TestCase
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
    public function marca_todas_las_no_leidas_como_leidas(): void
    {
        Notificacion::create([
            'usuario_id' => $this->usuario->id,
            'titulo' => 'Noti 1',
            'tipo' => 'info',
            'leida' => false,
        ]);
        Notificacion::create([
            'usuario_id' => $this->usuario->id,
            'titulo' => 'Noti 2',
            'tipo' => 'info',
            'leida' => false,
        ]);
        Notificacion::create([
            'usuario_id' => $this->usuario->id,
            'titulo' => 'Noti 3',
            'tipo' => 'info',
            'leida' => false,
        ]);

        $count = $this->service->marcarTodasLeidas($this->usuario->id);

        $this->assertSame(3, $count);

        $this->assertSame(
            0,
            Notificacion::where('usuario_id', $this->usuario->id)
                ->where('leida', false)
                ->count(),
        );
    }

    /** @test */
    public function retorna_0_si_no_hay_notificaciones(): void
    {
        $count = $this->service->marcarTodasLeidas($this->usuario->id);

        $this->assertSame(0, $count);
    }

    /** @test */
    public function retorna_0_si_todas_ya_estan_leidas(): void
    {
        Notificacion::create([
            'usuario_id' => $this->usuario->id,
            'titulo' => 'Ya leída 1',
            'tipo' => 'info',
            'leida' => true,
            'leida_at' => now()->subHour(),
        ]);
        Notificacion::create([
            'usuario_id' => $this->usuario->id,
            'titulo' => 'Ya leída 2',
            'tipo' => 'info',
            'leida' => true,
            'leida_at' => now()->subHour(),
        ]);

        $count = $this->service->marcarTodasLeidas($this->usuario->id);

        $this->assertSame(0, $count);
    }

    /** @test */
    public function solo_marca_notificaciones_del_usuario_actual(): void
    {
        Notificacion::create([
            'usuario_id' => $this->usuario->id,
            'titulo' => 'Mía no leída',
            'tipo' => 'info',
            'leida' => false,
        ]);
        Notificacion::create([
            'usuario_id' => $this->otroUsuario->id,
            'titulo' => 'De otro no leída',
            'tipo' => 'info',
            'leida' => false,
        ]);

        $count = $this->service->marcarTodasLeidas($this->usuario->id);

        $this->assertSame(1, $count);

        $notiOtro = Notificacion::where('usuario_id', $this->otroUsuario->id)->first();
        $this->assertFalse($notiOtro->leida);
    }

    /** @test */
    public function notificaciones_ya_leidas_mantienen_su_leida_at_original(): void
    {
        Notificacion::create([
            'usuario_id' => $this->usuario->id,
            'titulo' => 'No leída',
            'tipo' => 'info',
            'leida' => false,
        ]);

        $leidaAtOriginal = now()->subDay();
        $yaLeida = Notificacion::create([
            'usuario_id' => $this->usuario->id,
            'titulo' => 'Ya leída',
            'tipo' => 'info',
            'leida' => true,
            'leida_at' => $leidaAtOriginal,
        ]);

        $this->service->marcarTodasLeidas($this->usuario->id);

        $yaLeida->refresh();
        $this->assertEquals(
            $leidaAtOriginal->format('Y-m-d H:i:s'),
            $yaLeida->leida_at->format('Y-m-d H:i:s'),
        );
    }

    /** @test */
    public function mezcla_de_leidas_y_no_leidas_marca_solo_las_no_leidas(): void
    {
        Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => 'A', 'tipo' => 'info', 'leida' => false]);
        Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => 'B', 'tipo' => 'info', 'leida' => true, 'leida_at' => now()]);
        Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => 'C', 'tipo' => 'info', 'leida' => false]);
        Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => 'D', 'tipo' => 'info', 'leida' => true, 'leida_at' => now()]);

        $count = $this->service->marcarTodasLeidas($this->usuario->id);

        $this->assertSame(2, $count);
        $this->assertSame(
            0,
            Notificacion::where('usuario_id', $this->usuario->id)
                ->where('leida', false)
                ->count(),
        );
    }
}
