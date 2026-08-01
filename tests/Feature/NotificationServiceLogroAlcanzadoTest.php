<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notificaciones\Models\Notificacion;
use Modules\Notificaciones\Services\NotificationService;
use Tests\TestCase;

class NotificationServiceLogroAlcanzadoTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;
    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(NotificationService::class);

        $this->usuario = User::factory()->create([
            'name' => 'Carlos Estudiante',
            'email' => 'carlos@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);
    }

    /** @test */
    public function crea_notificacion_con_url(): void
    {
        $notif = $this->service->logroAlcanzado(
            usuario: $this->usuario,
            titulo: '10 Simulacros Completados',
            mensaje: 'Has completado 10 simulacros. ¡Sigue así!',
            url: '/progreso',
        );

        $this->assertInstanceOf(Notificacion::class, $notif);
        $this->assertEquals($this->usuario->id, $notif->usuario_id);
        $this->assertEquals('exito', $notif->tipo);
        $this->assertEquals('🏆 10 Simulacros Completados', $notif->titulo);
        $this->assertEquals('Has completado 10 simulacros. ¡Sigue así!', $notif->mensaje);
        $this->assertEquals('🏆', $notif->icono);
        $this->assertEquals(['url' => '/progreso'], $notif->data);
        $this->assertFalse($notif->leida);
    }

    /** @test */
    public function crea_notificacion_sin_url(): void
    {
        $notif = $this->service->logroAlcanzado(
            usuario: $this->usuario,
            titulo: 'Primer Simulacro',
            mensaje: 'Completaste tu primer simulacro.',
        );

        $this->assertNull($notif->data);
        $this->assertEquals('exito', $notif->tipo);
        $this->assertEquals('🏆 Primer Simulacro', $notif->titulo);
        $this->assertEquals('🏆', $notif->icono);
    }

    /** @test */
    public function titulo_se_combina_con_trofeo(): void
    {
        $notif = $this->service->logroAlcanzado(
            usuario: $this->usuario,
            titulo: 'Racha de 5 Aprobados',
            mensaje: 'Lograste 5 simulacros aprobados consecutivamente.',
        );

        $this->assertStringStartsWith('🏆 ', $notif->titulo);
        $this->assertStringContainsString('Racha de 5 Aprobados', $notif->titulo);
    }

    /** @test */
    public function acepta_usuario_como_int(): void
    {
        $notif = $this->service->logroAlcanzado(
            usuario: $this->usuario->id,
            titulo: 'Logro',
            mensaje: 'Mensaje de prueba',
        );

        $this->assertEquals($this->usuario->id, $notif->usuario_id);
    }

    /** @test */
    public function guarda_notificacion_en_base_de_datos(): void
    {
        $this->service->logroAlcanzado(
            usuario: $this->usuario,
            titulo: 'Logro Persistente',
            mensaje: 'Este logro debe guardarse en BD.',
            url: '/resultados/1',
        );

        $this->assertDatabaseHas('notifications', [
            'usuario_id' => $this->usuario->id,
            'tipo' => 'exito',
            'titulo' => '🏆 Logro Persistente',
            'icono' => '🏆',
            'leida' => false,
        ]);

        $notif = Notificacion::where('usuario_id', $this->usuario->id)->first();
        $this->assertEquals(['url' => '/resultados/1'], $notif->data);
    }

    /** @test */
    public function mensaje_se_guarda_textualmente(): void
    {
        $mensajeLargo = 'Excelente trabajo. Has demostrado constancia y dedicación en tu preparación. ¡Sigue así y lograrás tu objetivo!';

        $notif = $this->service->logroAlcanzado(
            usuario: $this->usuario,
            titulo: 'Logro',
            mensaje: $mensajeLargo,
        );

        $this->assertEquals($mensajeLargo, $notif->mensaje);
    }

}

