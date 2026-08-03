<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogo\Models\Categoria;
use Modules\Catalogo\Models\Institucion;
use Modules\Catalogo\Models\TipoExamen;
use Modules\Preguntas\Models\Alternativa;
use Modules\Preguntas\Models\AreaAcademica;
use Modules\Preguntas\Models\Concepto;
use Modules\Preguntas\Models\Pregunta;
use Modules\Preguntas\Models\TipoSimulacro;
use Modules\Rendicion\Models\IntentoExamen;
use Tests\TestCase;

class UniversidadSimulacroIniciarTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Institucion $institucion;
    private AreaAcademica $area;
    private Categoria $categoria;
    private TipoSimulacro $tipo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $tipoExamen = TipoExamen::create([
            'slug' => 'admision-universitaria',
            'nombre' => 'Admisión Universitaria',
            'activo' => true,
        ]);

        $this->institucion = Institucion::create([
            'tipo_examen_id' => $tipoExamen->id,
            'nombre' => 'Universidad de Prueba',
            'subtipo' => 'privada',
            'ciudad' => 'Lima',
            'activo' => true,
        ]);

        $this->area = AreaAcademica::create([
            'nombre' => 'Ciencias e Ingenierías',
            'descripcion' => 'Área de ingenierías',
            'num_preguntas' => 30,
            'duracion_min' => 60,
            'activo' => true,
        ]);

        $concepto = Concepto::create([
            'area_academica_id' => $this->area->id,
            'nombre' => 'Álgebra',
            'descripcion' => '',
        ]);

        $this->categoria = Categoria::create([
            'institucion_id' => $this->institucion->id,
            'area_academica_id' => $this->area->id,
            'nombre' => 'Ingeniería de Sistemas',
            'activo' => true,
        ]);

        // Banco global del área (10 preguntas)
        for ($i = 1; $i <= 10; $i++) {
            $p = Pregunta::create([
                'area_academica_id' => $this->area->id,
                'concepto_id' => $concepto->id,
                'enunciado' => "Pregunta global {$i}",
                'tipo' => 'opcion_multiple',
                'dificultad' => 'media',
                'activa' => true,
            ]);
            Alternativa::create([
                'pregunta_id' => $p->id,
                'texto' => 'A',
                'es_correcta' => true,
                'orden' => 0,
            ]);
        }

        $this->tipo = TipoSimulacro::create([
            'area_academica_id' => $this->area->id,
            'nombre' => 'Simulacro General',
            'descripcion' => '',
            'num_preguntas' => 5,
            'duracion_min' => 60,
            'activo' => true,
        ]);

        $this->usuario = User::factory()->create([
            'name' => 'Estudiante Test',
            'email' => 'estudiante@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);
    }

    /** @test */
    public function crea_intento_con_carrera_postulada_usando_banco_global(): void
    {
        $response = $this->actingAs($this->usuario)
            ->post(route('examenes.universidad-tipo-iniciar', [
                'institucionId' => $this->institucion->id,
                'areaId' => $this->area->id,
                'tipoId' => $this->tipo->id,
            ]), [
                'categoria_id' => $this->categoria->id,
            ]);

        $response->assertRedirect();

        $intento = IntentoExamen::where('usuario_id', $this->usuario->id)->first();

        $this->assertNotNull($intento);
        $this->assertEquals('en_curso', $intento->estado);
        $this->assertEquals($this->institucion->id, $intento->institucion_id);
        $this->assertEquals($this->area->id, $intento->area_academica_id);
        $this->assertEquals($this->tipo->id, $intento->tipo_simulacro_id);
        $this->assertEquals($this->categoria->id, $intento->categoria_id);
        $this->assertEquals('Ingeniería de Sistemas', $intento->carrera);

        $this->assertNotNull($intento->progreso_guardado);
        $this->assertCount(5, $intento->progreso_guardado['preguntas_ids']);

        $response->assertRedirect(route('examenes.rendir', ['intento' => $intento->id]));
    }

    /** @test */
    public function prioriza_banco_propio_y_completa_con_banco_global(): void
    {
        $concepto = Concepto::where('area_academica_id', $this->area->id)->first();

        // 3 preguntas propias de la universidad (el tipo pide 5)
        for ($i = 1; $i <= 3; $i++) {
            $p = Pregunta::create([
                'institucion_id' => $this->institucion->id,
                'area_academica_id' => $this->area->id,
                'concepto_id' => $concepto->id,
                'enunciado' => "Pregunta propia {$i}",
                'tipo' => 'opcion_multiple',
                'dificultad' => 'media',
                'activa' => true,
            ]);
            Alternativa::create([
                'pregunta_id' => $p->id,
                'texto' => 'A',
                'es_correcta' => true,
                'orden' => 0,
            ]);
        }

        $response = $this->actingAs($this->usuario)
            ->post(route('examenes.universidad-tipo-iniciar', [
                'institucionId' => $this->institucion->id,
                'areaId' => $this->area->id,
                'tipoId' => $this->tipo->id,
            ]), [
                'categoria_id' => $this->categoria->id,
            ]);

        $response->assertRedirect();

        $intento = IntentoExamen::where('usuario_id', $this->usuario->id)->first();
        $ids = $intento->progreso_guardado['preguntas_ids'];

        // 5 en total: 3 propias + 2 del banco global
        $this->assertCount(5, $ids);

        $propias = 0;
        $globales = 0;
        foreach ($ids as $pid) {
            if (Pregunta::find($pid)->institucion_id === $this->institucion->id) {
                $propias++;
            } else {
                $globales++;
            }
        }
        $this->assertEquals(3, $propias, 'Debe priorizar las preguntas propias de la universidad');
        $this->assertEquals(2, $globales, 'Debe completar con el banco global del área');
    }

    /** @test */
    public function usa_solo_banco_propio_si_alcanza_para_el_total(): void
    {
        $concepto = Concepto::where('area_academica_id', $this->area->id)->first();

        // 5 preguntas propias de la universidad (alcanzan para el tipo)
        for ($i = 1; $i <= 5; $i++) {
            $p = Pregunta::create([
                'institucion_id' => $this->institucion->id,
                'area_academica_id' => $this->area->id,
                'concepto_id' => $concepto->id,
                'enunciado' => "Pregunta propia {$i}",
                'tipo' => 'opcion_multiple',
                'dificultad' => 'media',
                'activa' => true,
            ]);
            Alternativa::create([
                'pregunta_id' => $p->id,
                'texto' => 'A',
                'es_correcta' => true,
                'orden' => 0,
            ]);
        }

        $response = $this->actingAs($this->usuario)
            ->post(route('examenes.universidad-tipo-iniciar', [
                'institucionId' => $this->institucion->id,
                'areaId' => $this->area->id,
                'tipoId' => $this->tipo->id,
            ]), [
                'categoria_id' => $this->categoria->id,
            ]);

        $response->assertRedirect();

        $intento = IntentoExamen::where('usuario_id', $this->usuario->id)->first();
        $ids = $intento->progreso_guardado['preguntas_ids'];

        $this->assertCount(5, $ids);
        foreach ($ids as $pid) {
            $this->assertEquals($this->institucion->id, Pregunta::find($pid)->institucion_id);
        }
    }

    /** @test */
    public function requiere_carrera_postulada(): void
    {
        $response = $this->actingAs($this->usuario)
            ->post(route('examenes.universidad-tipo-iniciar', [
                'institucionId' => $this->institucion->id,
                'areaId' => $this->area->id,
                'tipoId' => $this->tipo->id,
            ]), []);

        $response->assertSessionHasErrors('categoria_id');
        $this->assertEquals(0, IntentoExamen::where('usuario_id', $this->usuario->id)->count());
    }

    /** @test */
    public function rechaza_carrera_de_otra_universidad(): void
    {
        $otraInstitucion = Institucion::create([
            'tipo_examen_id' => TipoExamen::first()->id,
            'nombre' => 'Otra Universidad',
            'subtipo' => 'privada',
            'activo' => true,
        ]);
        $otraCategoria = Categoria::create([
            'institucion_id' => $otraInstitucion->id,
            'nombre' => 'Derecho',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->usuario)
            ->post(route('examenes.universidad-tipo-iniciar', [
                'institucionId' => $this->institucion->id,
                'areaId' => $this->area->id,
                'tipoId' => $this->tipo->id,
            ]), [
                'categoria_id' => $otraCategoria->id,
            ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function no_crea_intento_duplicado_si_hay_uno_en_curso(): void
    {
        // Primer intento
        $this->actingAs($this->usuario)
            ->post(route('examenes.universidad-tipo-iniciar', [
                'institucionId' => $this->institucion->id,
                'areaId' => $this->area->id,
                'tipoId' => $this->tipo->id,
            ]), [
                'categoria_id' => $this->categoria->id,
            ]);

        // Segundo intento → debe redirigir al existente
        $response = $this->actingAs($this->usuario)
            ->post(route('examenes.universidad-tipo-iniciar', [
                'institucionId' => $this->institucion->id,
                'areaId' => $this->area->id,
                'tipoId' => $this->tipo->id,
            ]), [
                'categoria_id' => $this->categoria->id,
            ]);

        $intento = IntentoExamen::where('usuario_id', $this->usuario->id)->first();
        $response->assertRedirect(route('examenes.rendir', ['intento' => $intento->id]));
        $this->assertEquals(1, IntentoExamen::where('usuario_id', $this->usuario->id)->count());
    }

    /** @test */
    public function requiere_autenticacion(): void
    {
        $response = $this->post(route('examenes.universidad-tipo-iniciar', [
            'institucionId' => $this->institucion->id,
            'areaId' => $this->area->id,
            'tipoId' => $this->tipo->id,
        ]), [
            'categoria_id' => $this->categoria->id,
        ]);

        $response->assertRedirect('/login');
    }

    /** @test */
    public function requiere_rol_estudiante(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'rol' => RolUsuario::Admin,
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('examenes.universidad-tipo-iniciar', [
                'institucionId' => $this->institucion->id,
                'areaId' => $this->area->id,
                'tipoId' => $this->tipo->id,
            ]), [
                'categoria_id' => $this->categoria->id,
            ]);

        $response->assertStatus(403);
    }
}
