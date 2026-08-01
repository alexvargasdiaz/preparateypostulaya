<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Catalogo\Models\Categoria;
use Modules\Catalogo\Models\Institucion;
use Modules\Catalogo\Models\TipoExamen;
use Tests\TestCase;

class CatalogoAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private TipoExamen $tipoExamen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->admin = User::factory()->create([
            'name' => 'Admin Catalogo',
            'email' => 'admin@test.com',
            'rol' => RolUsuario::Admin,
            'estado' => 'activo',
        ]);

        $this->tipoExamen = TipoExamen::create([
            'slug' => 'admision-universitaria',
            'nombre' => 'Admisión Universitaria',
            'activo' => true,
        ]);
    }

    // ─── Instituciones ───────────────────────────────────────

    /** @test */
    public function admin_puede_ver_lista_instituciones(): void
    {
        Institucion::create([
            'tipo_examen_id' => $this->tipoExamen->id,
            'nombre' => 'Universidad Nacional Mayor de San Marcos',
            'subtipo' => 'publica',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.instituciones.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Instituciones/Index')
            ->has('instituciones.data', 1)
        );
    }

    /** @test */
    public function admin_puede_crear_institucion_con_carreras(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.instituciones.store'), [
                'nombre' => 'Universidad de Prueba',
                'subtipo' => 'privada',
                'ciudad' => 'Lima',
                'carreras' => ['Ingeniería', 'Derecho', 'Medicina'],
            ]);

        $response->assertRedirect(route('admin.instituciones.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('instituciones', [
            'nombre' => 'Universidad de Prueba',
            'subtipo' => 'privada',
            'ciudad' => 'Lima',
        ]);

        $institucion = Institucion::where('nombre', 'Universidad de Prueba')->first();
        $this->assertCount(3, $institucion->categorias);
    }

    /** @test */
    public function admin_puede_crear_institucion_con_logo(): void
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('universidad.png');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.instituciones.store'), [
                'nombre' => 'Universidad con Logo',
                'subtipo' => 'publica',
                'logo' => $logo,
            ]);

        $response->assertRedirect(route('admin.instituciones.index'));

        $institucion = Institucion::where('nombre', 'Universidad con Logo')->first();
        $this->assertNotNull($institucion->logo_url);
    }

    /** @test */
    public function crear_institucion_requiere_nombre_y_subtipo(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.instituciones.store'), []);

        $response->assertSessionHasErrors(['nombre', 'subtipo']);
    }

    /** @test */
    public function admin_puede_editar_institucion(): void
    {
        $institucion = Institucion::create([
            'tipo_examen_id' => $this->tipoExamen->id,
            'nombre' => 'Original',
            'subtipo' => 'privada',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.instituciones.edit', $institucion->id));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Instituciones/Crear')
            ->where('editando', true)
        );
    }

    /** @test */
    public function admin_puede_actualizar_institucion(): void
    {
        $institucion = Institucion::create([
            'tipo_examen_id' => $this->tipoExamen->id,
            'nombre' => 'Original',
            'subtipo' => 'privada',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.instituciones.update', $institucion->id), [
                'nombre' => 'Actualizada',
                'subtipo' => 'publica',
                'ciudad' => 'Arequipa',
            ]);

        $response->assertRedirect(route('admin.instituciones.index'));

        $this->assertDatabaseHas('instituciones', [
            'id' => $institucion->id,
            'nombre' => 'Actualizada',
            'subtipo' => 'publica',
            'ciudad' => 'Arequipa',
        ]);
    }

    /** @test */
    public function admin_puede_eliminar_institucion_sin_carreras(): void
    {
        $institucion = Institucion::create([
            'tipo_examen_id' => $this->tipoExamen->id,
            'nombre' => 'A Eliminar',
            'subtipo' => 'privada',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.instituciones.destroy', $institucion->id));

        $response->assertRedirect(route('admin.instituciones.index'));

        $this->assertDatabaseMissing('instituciones', ['id' => $institucion->id]);
    }

    /** @test */
    public function no_puede_eliminar_institucion_con_carreras(): void
    {
        $institucion = Institucion::create([
            'tipo_examen_id' => $this->tipoExamen->id,
            'nombre' => 'Con Carreras',
            'subtipo' => 'privada',
            'activo' => true,
        ]);
        Categoria::create([
            'institucion_id' => $institucion->id,
            'nombre' => 'Ingeniería',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.instituciones.destroy', $institucion->id));

        $response->assertSessionHas('error');

        $this->assertDatabaseHas('instituciones', ['id' => $institucion->id]);
    }

    // ─── Categorías (Carreras) ───────────────────────────────

    /** @test */
    public function admin_puede_ver_lista_categorias(): void
    {
        $institucion = Institucion::create([
            'tipo_examen_id' => $this->tipoExamen->id,
            'nombre' => 'UNI',
            'subtipo' => 'publica',
            'activo' => true,
        ]);
        Categoria::create([
            'institucion_id' => $institucion->id,
            'nombre' => 'Ingeniería Civil',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.categorias.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Categorias/Index')
            ->has('categorias.data', 1)
        );
    }

    /** @test */
    public function admin_puede_crear_categoria(): void
    {
        $institucion = Institucion::create([
            'tipo_examen_id' => $this->tipoExamen->id,
            'nombre' => 'PUCP',
            'subtipo' => 'privada',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.categorias.store'), [
                'institucion_id' => $institucion->id,
                'nombre' => 'Arquitectura',
                'descripcion_corta' => 'Carrera de Arquitectura',
            ]);

        $response->assertRedirect(route('admin.categorias.index'));

        $this->assertDatabaseHas('categorias', [
            'nombre' => 'Arquitectura',
            'institucion_id' => $institucion->id,
        ]);
    }

    /** @test */
    public function admin_puede_eliminar_categoria(): void
    {
        $institucion = Institucion::create([
            'tipo_examen_id' => $this->tipoExamen->id,
            'nombre' => 'UNMSM',
            'subtipo' => 'publica',
            'activo' => true,
        ]);
        $categoria = Categoria::create([
            'institucion_id' => $institucion->id,
            'nombre' => 'Medicina',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.categorias.destroy', $categoria->id));

        $response->assertRedirect(route('admin.categorias.index'));

        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
    }

    /** @test */
    public function estudiante_no_puede_acceder_a_admin_catalogo(): void
    {
        $estudiante = User::factory()->create([
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($estudiante)
            ->get(route('admin.instituciones.index'));

        $response->assertStatus(403);
    }
}
