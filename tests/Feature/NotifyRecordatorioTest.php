<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Catalogo\Models\Categoria;
use Modules\Catalogo\Models\Examen;
use Modules\Catalogo\Models\Institucion;
use Modules\Catalogo\Models\TipoExamen;
use Modules\Notificaciones\Models\Notificacion;
use Modules\Notificaciones\Models\PreferenciaNotificacion;
use Modules\Rendicion\Models\IntentoExamen;
use Tests\TestCase;

class NotifyRecordatorioTest extends TestCase
{
    use RefreshDatabase;

    private Examen $examen;
    private User $usuarioAntiguo;    // 10 días sin practicar → debe recibir recordatorio
    private User $usuarioReciente;    // intento hoy → debe ser saltado

    protected function setUp(): void
    {
        parent::setUp();

        // ─── Crear catálogo mínimo para la foreign key ────────────
        $tipo = TipoExamen::create([
            'slug' => 'test-tipo',
            'nombre' => 'Test Tipo',
            'activo' => true,
        ]);
        $institucion = Institucion::create([
            'tipo_examen_id' => $tipo->id,
            'nombre' => 'Test Universidad',
            'subtipo' => 'privada',
            'ciudad' => 'Lima',
            'activo' => true,
        ]);
        $categoria = Categoria::create([
            'institucion_id' => $institucion->id,
            'nombre' => 'Test Facultad',
            'descripcion_corta' => 'Test',
            'orden' => 1,
            'activo' => true,
        ]);

        // ─── Usuario con intento ANTIGUO (10 días atrás) ──────────
        $this->usuarioAntiguo = User::factory()->create([
            'name' => 'Test Estudiante Antiguo',
            'email' => 'test@antiguo.com',
            'rol' => RolUsuario::Estudiante,
        ]);
        PreferenciaNotificacion::create([
            'usuario_id' => $this->usuarioAntiguo->id,
            'recordatorio_estudio' => true,
        ]);
        // Crear un examen real para la foreign key
        $this->examen = Examen::create([
            'categoria_id' => $categoria->id,
            'titulo' => 'Test Examen',
            'descripcion' => 'Examen de prueba',
            'tiempo_limite_min' => 20,
            'intentos_permitidos' => 99,
            'num_alternativas_default' => 5,
            'aleatorizar_preguntas' => true,
            'aleatorizar_alternativas' => true,
            'activo' => true,
        ]);

        // Intento antiguo (10 días atrás) con created_at personalizado
        $intentoAntiguo = new IntentoExamen([
            'usuario_id' => $this->usuarioAntiguo->id,
            'examen_id' => $this->examen->id,
            'estado' => 'completado',
            'puntaje_total' => 7,
            'puntaje_maximo' => 10,
            'aprobado' => true,
            'fecha_inicio' => now()->subDays(10),
            'fecha_fin' => now()->subDays(10),
        ]);
        $intentoAntiguo->created_at = now()->subDays(10);
        $intentoAntiguo->updated_at = now()->subDays(10);
        $intentoAntiguo->save();

        // ─── Usuario con intento RECIENTE (hoy) → debe ser saltado ──
        $this->usuarioReciente = User::factory()->create([
            'name' => 'Test Estudiante Reciente',
            'email' => 'test@reciente.com',
            'rol' => RolUsuario::Estudiante,
        ]);
        PreferenciaNotificacion::create([
            'usuario_id' => $this->usuarioReciente->id,
            'recordatorio_estudio' => true,
        ]);
        IntentoExamen::create([
            'usuario_id' => $this->usuarioReciente->id,
            'examen_id' => $this->examen->id,
            'estado' => 'completado',
            'puntaje_total' => 5,
            'puntaje_maximo' => 10,
            'fecha_inicio' => now(),
            'fecha_fin' => now(),
        ]);

        // ─── Usuario con recordatorio DESACTIVADO → debe ser saltado ──
        $usuarioSinRecordatorio = User::factory()->create([
            'name' => 'Test Sin Recordatorio',
            'email' => 'test@sinrecordatorio.com',
            'rol' => RolUsuario::Estudiante,
        ]);
        PreferenciaNotificacion::create([
            'usuario_id' => $usuarioSinRecordatorio->id,
            'recordatorio_estudio' => false,
        ]);
    }

    /** @test */
    public function el_comando_dry_run_muestra_usuarios_inactivos(): void
    {
        $exitCode = Artisan::call('notify:recordatorio', [
            '--dias' => 3,
            '--dry-run' => true,
        ]);

        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('DRY-RUN', $output);
        $this->assertStringContainsString('Test Estudiante Antiguo', $output);
        $this->assertStringNotContainsString('Test Estudiante Reciente', $output);
        $this->assertStringNotContainsString('Test Sin Recordatorio', $output);
    }

    /** @test */
    public function el_comando_crea_notificaciones_para_usuarios_inactivos(): void
    {
        $exitCode = Artisan::call('notify:recordatorio', [
            '--dias' => 3,
        ]);

        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);

        // Verificar que se creó una notificación para el usuario antiguo
        $notificaciones = Notificacion::where('usuario_id', $this->usuarioAntiguo->id)->get();
        $this->assertCount(1, $notificaciones);
        $this->assertStringContainsString('10 días', $notificaciones->first()->mensaje);

        // Verificar que NO se creó notificación para usuario con intento reciente
        $notificacionesReciente = Notificacion::where('usuario_id', $this->usuarioReciente->id)->count();
        $this->assertEquals(0, $notificacionesReciente);
    }

    /** @test */
    public function el_comando_salta_usuarios_con_recordatorio_desactivado(): void
    {
        Artisan::call('notify:recordatorio', [
            '--dias' => 1,
            '--dry-run' => true,
        ]);

        $output = Artisan::output();

        $this->assertStringContainsString('Test Estudiante Antiguo', $output);
        $this->assertStringNotContainsString('Test Sin Recordatorio', $output);
    }

    /** @test */
    public function el_comando_validar_dias_minimos(): void
    {
        $exitCode = Artisan::call('notify:recordatorio', [
            '--dias' => 0,
        ]);

        $this->assertEquals(2, $exitCode); // Command::INVALID = 2
        $this->assertStringContainsString('al menos 1', Artisan::output());
    }

    /** @test */
    public function el_comando_funciona_sin_usuarios(): void
    {
        PreferenciaNotificacion::query()->delete();

        $exitCode = Artisan::call('notify:recordatorio', [
            '--dias' => 3,
            '--dry-run' => true,
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('No hay usuarios', Artisan::output());
    }

    /** @test */
    public function el_comando_envia_recordatorio_a_usuario_sin_intentos(): void
    {
        $usuarioNuevo = User::factory()->create([
            'name' => 'Test Sin Intentos',
            'email' => 'test@nuevo.com',
            'rol' => RolUsuario::Estudiante,
        ]);
        PreferenciaNotificacion::create([
            'usuario_id' => $usuarioNuevo->id,
            'recordatorio_estudio' => true,
        ]);

        Artisan::call('notify:recordatorio', [
            '--dias' => 1,
            '--dry-run' => true,
        ]);

        $output = Artisan::output();

        $this->assertStringContainsString('Test Sin Intentos', $output);
        $this->assertStringContainsString('999 días', $output);
        $this->assertStringContainsString('nunca ha rendido', $output);
    }
}
