<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notificaciones\Models\Notificacion;
use Modules\Notificaciones\Services\NotificationService;
use Tests\TestCase;

class NotificationServiceExamenCompletadoTest extends TestCase
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
    public function crea_notificacion_de_aprobado(): void
    {
        $notif = $this->service->examenCompletado(
            usuario: $this->usuario,
            examenTitulo: 'Simulacro Admisión 2026 - Ingeniería',
            puntaje: 8,
            maximo: 10,
            aprobado: true,
            intentoId: 42,
        );

        $this->assertInstanceOf(Notificacion::class, $notif);
        $this->assertEquals($this->usuario->id, $notif->usuario_id);
        $this->assertEquals('exito', $notif->tipo);
        $this->assertStringContainsString('Aprobaste', $notif->titulo);
        $this->assertStringContainsString('Simulacro Admisión 2026', $notif->titulo);
        $this->assertStringContainsString('Sigue así', $notif->mensaje);
        $this->assertEquals('🎉', $notif->icono);
        $this->assertFalse($notif->leida);
        $this->assertNull($notif->leida_at);
    }

    /** @test */
    public function crea_notificacion_de_no_aprobado(): void
    {
        $notif = $this->service->examenCompletado(
            usuario: $this->usuario,
            examenTitulo: 'Simulacro Admisión 2026',
            puntaje: 4,
            maximo: 10,
            aprobado: false,
            intentoId: 7,
        );

        $this->assertEquals('info', $notif->tipo);
        $this->assertStringContainsString('Completaste', $notif->titulo);
        $this->assertStringContainsString('reforzar', $notif->mensaje);
        $this->assertEquals('📝', $notif->icono);
    }

    /** @test */
    public function calcula_porcentaje_correctamente(): void
    {
        $notif = $this->service->examenCompletado(
            usuario: $this->usuario,
            examenTitulo: 'Test',
            puntaje: 7,
            maximo: 10,
            aprobado: true,
            intentoId: 1,
        );

        $this->assertEquals(70, $notif->data['porcentaje']);
        $this->assertStringContainsString('70%', $notif->mensaje);
    }

    /** @test */
    public function porcentaje_es_0_si_maximo_es_0(): void
    {
        $notif = $this->service->examenCompletado(
            usuario: $this->usuario,
            examenTitulo: 'Test',
            puntaje: 0,
            maximo: 0,
            aprobado: false,
            intentoId: 1,
        );

        $this->assertEquals(0, $notif->data['porcentaje']);
    }

    /** @test */
    public function data_contiene_estructura_completa(): void
    {
        $notif = $this->service->examenCompletado(
            usuario: $this->usuario,
            examenTitulo: 'Mi Examen',
            puntaje: 9,
            maximo: 10,
            aprobado: true,
            intentoId: 15,
        );

        $expectedData = [
            'url' => '/resultados/15',
            'intento_id' => 15,
            'puntaje' => 9,
            'maximo' => 10,
            'porcentaje' => 90,
            'aprobado' => true,
        ];

        $this->assertEquals($expectedData, $notif->data);
    }

    /** @test */
    public function acepta_usuario_como_int(): void
    {
        $notif = $this->service->examenCompletado(
            usuario: $this->usuario->id,
            examenTitulo: 'Test',
            puntaje: 6,
            maximo: 10,
            aprobado: true,
            intentoId: 1,
        );

        $this->assertEquals($this->usuario->id, $notif->usuario_id);
    }

    /** @test */
    public function guarda_notificacion_en_base_de_datos(): void
    {
        $this->service->examenCompletado(
            usuario: $this->usuario,
            examenTitulo: 'Simulacro Final',
            puntaje: 8,
            maximo: 10,
            aprobado: true,
            intentoId: 99,
        );

        $this->assertDatabaseHas('notifications', [
            'usuario_id' => $this->usuario->id,
            'tipo' => 'exito',
            'leida' => false,
        ]);

        $this->assertEquals(1, Notificacion::where('usuario_id', $this->usuario->id)->count());
    }

    /** @test */
    public function maneja_titulo_con_caracteres_especiales(): void
    {
        $notif = $this->service->examenCompletado(
            usuario: $this->usuario,
            examenTitulo: 'Simulacro de Admisión 2026 - UNMSM (Ciencias)',
            puntaje: 5,
            maximo: 10,
            aprobado: false,
            intentoId: 1,
        );

        $this->assertStringContainsString('UNMSM', $notif->titulo);
        $this->assertStringContainsString('Ciencias', $notif->titulo);
    }

    /** @test */
    public function datos_se_persisten_correctamente_en_la_bd(): void
    {
        $this->service->examenCompletado(
            usuario: $this->usuario,
            examenTitulo: 'Examen Persistencia',
            puntaje: 6,
            maximo: 10,
            aprobado: true,
            intentoId: 100,
        );

        $notif = Notificacion::where('usuario_id', $this->usuario->id)->first();

        $this->assertNotNull($notif);
        $this->assertEquals('/resultados/100', $notif->data['url']);
        $this->assertEquals(100, $notif->data['intento_id']);
        $this->assertEquals(6, $notif->data['puntaje']);
        $this->assertEquals(10, $notif->data['maximo']);
        $this->assertEquals(60, $notif->data['porcentaje']);
        $this->assertTrue($notif->data['aprobado']);
        $this->assertEquals('¡Aprobaste Examen Persistencia!', $notif->titulo);
        $this->assertEquals('Obtuviste 6/10 (60%). ¡Sigue así!', $notif->mensaje);
    }
}
