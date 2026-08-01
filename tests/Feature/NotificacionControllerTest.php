<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notificaciones\Models\Notificacion;
use Modules\Notificaciones\Models\PreferenciaNotificacion;
use Tests\TestCase;

class NotificacionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private User $otroUsuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->usuario = User::factory()->create([
            'name' => 'Carlos Estudiante',
            'email' => 'carlos@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);

        $this->otroUsuario = User::factory()->create([
            'name' => 'María Estudiante',
            'email' => 'maria@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);
    }

    private function crearNotificacion(User $usuario, bool $leida = false, string $tipo = 'info'): Notificacion
    {
        return Notificacion::create([
            'usuario_id' => $usuario->id,
            'tipo' => $tipo,
            'titulo' => 'Notificación de prueba',
            'mensaje' => 'Mensaje de prueba',
            'data' => ['url' => '/test', 'key' => 'value'],
            'icono' => 'ℹ️',
            'leida' => $leida,
        ]);
    }

    // ─── index() ───────────────────────────────────────────

    /** @test */
    public function muestra_pagina_de_notificaciones(): void
    {
        $this->crearNotificacion($this->usuario);
        $this->crearNotificacion($this->usuario);

        $response = $this->actingAs($this->usuario)
            ->get(route('notificaciones.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Notificaciones/Index')
            ->has('notificaciones.data', 2)
            ->has('preferencias')
            ->has('noLeidas')
        );
    }

    /** @test */
    public function pagina_notificaciones_muestra_no_leidas(): void
    {
        $this->crearNotificacion($this->usuario, leida: false);  // no leída
        $this->crearNotificacion($this->usuario, leida: true);   // leída
        $this->crearNotificacion($this->usuario, leida: false);  // no leída

        $response = $this->actingAs($this->usuario)
            ->get(route('notificaciones.index'));

        $response->assertInertia(fn ($page) => $page
            ->component('Notificaciones/Index')
            ->has('notificaciones.data', 3)
            ->where('noLeidas', 0)
        );
    }

    /** @test */
    public function pagina_notificaciones_crea_preferencias_por_defecto(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get(route('notificaciones.index'));

        $response->assertInertia(fn ($page) => $page
            ->component('Notificaciones/Index')
            ->has('preferencias', fn ($p) => $p
                ->where('email_resultados', true)
                ->where('whatsapp_resultados', false)
                ->where('recordatorio_estudio', true)
                ->where('novedades', true)
                ->etc()
            )
        );
    }

    /** @test */
    public function pagina_notificaciones_solo_muestra_del_usuario_actual(): void
    {
        $this->crearNotificacion($this->usuario);
        $this->crearNotificacion($this->otroUsuario);

        $response = $this->actingAs($this->usuario)
            ->get(route('notificaciones.index'));

        $response->assertInertia(fn ($page) => $page
            ->component('Notificaciones/Index')
            ->has('notificaciones.data', 1)
        );
    }

    /** @test */
    public function pagina_notificaciones_requiere_autenticacion(): void
    {
        $response = $this->get(route('notificaciones.index'));
        $response->assertRedirect('/login');
    }

    // ─── obtenerRecientes() ─────────────────────────────────

    /** @test */
    public function obtener_recientes_retorna_json(): void
    {
        $this->crearNotificacion($this->usuario, leida: false);
        $this->crearNotificacion($this->usuario, leida: true);

        $response = $this->actingAs($this->usuario)
            ->getJson(route('notificaciones.api.recientes'));

        $response->assertOk();
        $response->assertJsonStructure([
            'notificaciones',
            'noLeidas',
        ]);
        $response->assertJsonCount(2, 'notificaciones');
        $response->assertJson(['noLeidas' => 1]);
    }

    /** @test */
    public function recientes_sin_notificaciones_retorna_vacio(): void
    {
        $response = $this->actingAs($this->usuario)
            ->getJson(route('notificaciones.api.recientes'));

        $response->assertOk();
        $response->assertJsonCount(0, 'notificaciones');
        $response->assertJson(['noLeidas' => 0]);
    }

    /** @test */
    public function recientes_limita_a_5(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->crearNotificacion($this->usuario);
        }

        $response = $this->actingAs($this->usuario)
            ->getJson(route('notificaciones.api.recientes'));

        $response->assertJsonCount(5, 'notificaciones');
    }

    /** @test */
    public function recientes_requiere_autenticacion(): void
    {
        $response = $this->getJson(route('notificaciones.api.recientes'));
        $response->assertStatus(401);
    }

    // ─── marcarLeida() ──────────────────────────────────────

    /** @test */
    public function marcar_leida_exitosamente(): void
    {
        $notif = $this->crearNotificacion($this->usuario, leida: false);

        $response = $this->actingAs($this->usuario)
            ->postJson(route('notificaciones.leer', $notif->id));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $notif->refresh();
        $this->assertTrue($notif->leida);
        $this->assertNotNull($notif->leida_at);
    }

    /** @test */
    public function marcar_leida_notificacion_inexistente_retorna_404(): void
    {
        $response = $this->actingAs($this->usuario)
            ->postJson(route('notificaciones.leer', 99999));

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Notificación no encontrada']);
    }

    /** @test */
    public function marcar_leida_notificacion_de_otro_usuario_retorna_404(): void
    {
        $notif = $this->crearNotificacion($this->otroUsuario, leida: false);

        $response = $this->actingAs($this->usuario)
            ->postJson(route('notificaciones.leer', $notif->id));

        $response->assertStatus(404);
    }

    /** @test */
    public function marcar_leida_notificacion_ya_leida_no_actualiza_leida_at(): void
    {
        $notif = $this->crearNotificacion($this->usuario, leida: true);
        $leidaAtOriginal = $notif->leida_at;

        $response = $this->actingAs($this->usuario)
            ->postJson(route('notificaciones.leer', $notif->id));

        $response->assertOk();

        $notif->refresh();
        $this->assertEquals($leidaAtOriginal, $notif->leida_at);
    }

    /** @test */
    public function marcar_leida_requiere_autenticacion(): void
    {
        $response = $this->postJson(route('notificaciones.leer', 1));
        $response->assertStatus(401);
    }

    // ─── marcarTodasLeidas() ────────────────────────────────

    /** @test */
    public function marcar_todas_leidas_exitosamente(): void
    {
        $this->crearNotificacion($this->usuario, leida: false);
        $this->crearNotificacion($this->usuario, leida: false);
        $this->crearNotificacion($this->usuario, leida: false);

        $response = $this->actingAs($this->usuario)
            ->postJson(route('notificaciones.leer-todas'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'marcadas' => 3,
        ]);

        $noLeidas = Notificacion::where('usuario_id', $this->usuario->id)
            ->where('leida', false)
            ->count();
        $this->assertEquals(0, $noLeidas);
    }

    /** @test */
    public function marcar_todas_leidas_sin_no_leidas_retorna_0(): void
    {
        $this->crearNotificacion($this->usuario, leida: true);

        $response = $this->actingAs($this->usuario)
            ->postJson(route('notificaciones.leer-todas'));

        $response->assertJson(['marcadas' => 0]);
    }

    /** @test */
    public function marcar_todas_leidas_solo_afecta_al_usuario_actual(): void
    {
        $this->crearNotificacion($this->usuario, leida: false);
        $this->crearNotificacion($this->otroUsuario, leida: false);

        $this->actingAs($this->usuario)
            ->postJson(route('notificaciones.leer-todas'));

        // La del otro usuario debe seguir sin leer
        $otrasNoLeidas = Notificacion::where('usuario_id', $this->otroUsuario->id)
            ->where('leida', false)
            ->count();
        $this->assertEquals(1, $otrasNoLeidas);
    }

    /** @test */
    public function marcar_todas_leidas_requiere_autenticacion(): void
    {
        $response = $this->postJson(route('notificaciones.leer-todas'));
        $response->assertStatus(401);
    }

    // ─── actualizarPreferencias() ──────────────────────────

    /** @test */
    public function actualizar_preferencias_exitosamente(): void
    {
        // Primero acceder para crear preferencias por defecto
        $this->actingAs($this->usuario)->get(route('notificaciones.index'));

        $response = $this->actingAs($this->usuario)
            ->postJson(route('notificaciones.preferencias'), [
                'email_resultados' => false,
                'whatsapp_resultados' => true,
                'recordatorio_estudio' => false,
                'novedades' => true,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'preferencias' => [
                'email_resultados' => false,
                'whatsapp_resultados' => true,
                'recordatorio_estudio' => false,
                'novedades' => true,
            ],
        ]);
    }

    /** @test */
    public function actualizar_preferencias_con_valores_parciales(): void
    {
        $this->actingAs($this->usuario)->get(route('notificaciones.index'));

        $response = $this->actingAs($this->usuario)
            ->postJson(route('notificaciones.preferencias'), [
                'email_resultados' => false,
            ]);

        $response->assertOk();

        $prefs = PreferenciaNotificacion::paraUsuario($this->usuario->id);
        $this->assertFalse($prefs->email_resultados);
        $this->assertTrue($prefs->recordatorio_estudio); // valor por defecto no alterado
    }

    /** @test */
    public function actualizar_preferencias_falla_validacion(): void
    {
        $this->actingAs($this->usuario)->get(route('notificaciones.index'));

        $response = $this->actingAs($this->usuario)
            ->postJson(route('notificaciones.preferencias'), [
                'email_resultados' => 'no_es_booleano',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function actualizar_preferencias_requiere_autenticacion(): void
    {
        $response = $this->postJson(route('notificaciones.preferencias'));
        $response->assertStatus(401);
    }

    /** @test */
    public function actualizar_preferencias_funciona_sin_visita_previa(): void
    {
        // Sin llamar a index() primero — el firstOrCreate debe crear las preferencias
        $response = $this->actingAs($this->usuario)
            ->postJson(route('notificaciones.preferencias'), [
                'email_resultados' => false,
                'recordatorio_estudio' => true,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('notification_preferences', [
            'usuario_id' => $this->usuario->id,
            'email_resultados' => false,
            'recordatorio_estudio' => true,
        ]);
    }
}
