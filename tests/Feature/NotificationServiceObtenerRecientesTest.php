<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notificaciones\Models\Notificacion;
use Modules\Notificaciones\Services\NotificationService;
use Tests\TestCase;

class NotificationServiceObtenerRecientesTest extends TestCase
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
    public function retorna_array_vacio_sin_notificaciones(): void
    {
        $result = $this->service->obtenerRecientes($this->usuario->id);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    /** @test */
    public function retorna_notificaciones_del_usuario(): void
    {
        Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => 'T1', 'tipo' => 'info', 'leida' => false]);
        Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => 'T2', 'tipo' => 'info', 'leida' => true]);

        $result = $this->service->obtenerRecientes($this->usuario->id);

        $this->assertCount(2, $result);
    }

    /** @test */
    public function no_incluye_notificaciones_de_otros_usuarios(): void
    {
        Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => 'Mía', 'tipo' => 'info', 'leida' => false]);
        Notificacion::create(['usuario_id' => $this->otroUsuario->id, 'titulo' => 'De otro', 'tipo' => 'info', 'leida' => false]);

        $result = $this->service->obtenerRecientes($this->usuario->id);

        $this->assertCount(1, $result);
        $this->assertEquals('Mía', $result[0]['titulo']);
    }

    /** @test */
    public function respeta_limite_personalizado(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => "T{$i}", 'tipo' => 'info', 'leida' => false]);
        }

        $result = $this->service->obtenerRecientes($this->usuario->id, 3);

        $this->assertCount(3, $result);
    }

    /** @test */
    public function usa_limite_por_defecto_10(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => "T{$i}", 'tipo' => 'info', 'leida' => false]);
        }

        $result = $this->service->obtenerRecientes($this->usuario->id);

        $this->assertCount(10, $result);
    }

    /** @test */
    public function ordena_por_created_at_descendente(): void
    {
        $n1 = Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => 'Primera', 'tipo' => 'info', 'leida' => false]);
        $n1->update(['created_at' => now()->subDays(2)]);

        $n2 = Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => 'Segunda', 'tipo' => 'info', 'leida' => false]);
        $n2->update(['created_at' => now()->subDay()]);

        $n3 = Notificacion::create(['usuario_id' => $this->usuario->id, 'titulo' => 'Tercera', 'tipo' => 'info', 'leida' => false]);
        $n3->update(['created_at' => now()]);

        $result = $this->service->obtenerRecientes($this->usuario->id);

        $this->assertCount(3, $result);
        $this->assertEquals('Tercera', $result[0]['titulo']);
        $this->assertEquals('Segunda', $result[1]['titulo']);
        $this->assertEquals('Primera', $result[2]['titulo']);
    }

    /** @test */
    public function retorna_array_con_estructura_correcta(): void
    {
        Notificacion::create([
            'usuario_id' => $this->usuario->id,
            'tipo' => 'exito',
            'titulo' => 'Noti de prueba',
            'mensaje' => 'Mensaje de prueba',
            'data' => ['url' => '/test'],
            'icono' => '✅',
            'leida' => false,
        ]);

        $result = $this->service->obtenerRecientes($this->usuario->id);

        $this->assertCount(1, $result);
        $item = $result[0];

        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('usuario_id', $item);
        $this->assertArrayHasKey('tipo', $item);
        $this->assertArrayHasKey('titulo', $item);
        $this->assertArrayHasKey('mensaje', $item);
        $this->assertArrayHasKey('data', $item);
        $this->assertArrayHasKey('icono', $item);
        $this->assertArrayHasKey('leida', $item);
        $this->assertArrayHasKey('created_at', $item);
        $this->assertEquals('Noti de prueba', $item['titulo']);
        $this->assertEquals('Mensaje de prueba', $item['mensaje']);
        $this->assertEquals(['url' => '/test'], $item['data']);
    }
}
