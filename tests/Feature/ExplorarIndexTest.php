<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogo\Models\Categoria;
use Modules\Catalogo\Models\Institucion;
use Modules\Catalogo\Models\TipoExamen;
use Modules\Preguntas\Models\Alternativa;
use Modules\Preguntas\Models\AreaAcademica;
use Modules\Preguntas\Models\Concepto;
use Modules\Preguntas\Models\Pregunta;
use Modules\Preguntas\Models\TipoSimulacro;
use Tests\TestCase;

class ExplorarIndexTest extends TestCase
{
    use RefreshDatabase;

    private TipoExamen $tipo;
    private Institucion $institucion1;
    private Institucion $institucion2;
    private AreaAcademica $area;
    private Categoria $categoria1;
    private TipoSimulacro $tipoSimulacro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tipo = TipoExamen::create([
            'slug' => 'admision-universitaria',
            'nombre' => 'Admisión Universitaria',
            'activo' => true,
        ]);

        // ─── Área académica ───────────────────────────────────
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
            'descripcion' => 'Preguntas de álgebra',
        ]);

        // Pregunta del banco global del área
        $preguntaGlobal = Pregunta::create([
            'area_academica_id' => $this->area->id,
            'concepto_id' => $concepto->id,
            'enunciado' => 'Pregunta global',
            'tipo' => 'opcion_multiple',
            'dificultad' => 'media',
            'activa' => true,
        ]);
        Alternativa::create([
            'pregunta_id' => $preguntaGlobal->id,
            'texto' => 'Alternativa A',
            'es_correcta' => true,
            'orden' => 0,
        ]);

        $this->tipoSimulacro = TipoSimulacro::create([
            'area_academica_id' => $this->area->id,
            'nombre' => 'Simulacro General',
            'descripcion' => 'Simulacro de práctica',
            'num_preguntas' => 10,
            'duracion_min' => 60,
            'activo' => true,
        ]);

        // ─── Institución 1 ──────────────────────────────────────
        $this->institucion1 = Institucion::create([
            'tipo_examen_id' => $this->tipo->id,
            'nombre' => 'Universidad Nacional Mayor de San Marcos',
            'subtipo' => 'publica',
            'ciudad' => 'Lima',
            'activo' => true,
        ]);

        $this->categoria1 = Categoria::create([
            'institucion_id' => $this->institucion1->id,
            'area_academica_id' => $this->area->id,
            'nombre' => 'Ingeniería de Sistemas',
            'descripcion_corta' => 'Carrera de Ingeniería',
            'orden' => 1,
            'activo' => true,
        ]);

        // Pregunta propia de la universidad 1
        $preguntaPropia = Pregunta::create([
            'institucion_id' => $this->institucion1->id,
            'area_academica_id' => $this->area->id,
            'concepto_id' => $concepto->id,
            'enunciado' => 'Pregunta propia UNMSM',
            'tipo' => 'opcion_multiple',
            'dificultad' => 'media',
            'activa' => true,
        ]);
        Alternativa::create([
            'pregunta_id' => $preguntaPropia->id,
            'texto' => 'Alternativa A',
            'es_correcta' => true,
            'orden' => 0,
        ]);

        // ─── Institución 2 ──────────────────────────────────────
        $this->institucion2 = Institucion::create([
            'tipo_examen_id' => $this->tipo->id,
            'nombre' => 'Universidad del Pacífico',
            'subtipo' => 'privada',
            'ciudad' => 'Lima',
            'activo' => true,
        ]);

        Categoria::create([
            'institucion_id' => $this->institucion2->id,
            'area_academica_id' => $this->area->id,
            'nombre' => 'Administración',
            'descripcion_corta' => 'Carreras de Administración',
            'orden' => 1,
            'activo' => true,
        ]);
    }

    /** @test */
    public function lista_todas_las_instituciones_sin_filtros(): void
    {
        $response = $this->get(route('examenes.universidades'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->has('instituciones', 2)
            ->has('areas', 0)
            ->has('tipos', 0)
            ->where('institucionSel', null)
            ->where('areaSel', null)
            ->where('filtros', [])
        );
    }

    /** @test */
    public function lista_instituciones_ordenadas_por_nombre(): void
    {
        $response = $this->get(route('examenes.universidades'));

        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->has('instituciones', 2)
            ->where('instituciones.0.nombre', 'Universidad del Pacífico')
            ->where('instituciones.1.nombre', 'Universidad Nacional Mayor de San Marcos')
        );
    }

    /** @test */
    public function lista_areas_cuando_se_selecciona_institucion(): void
    {
        $response = $this->get(route('examenes.universidades', [
            'institucion_id' => $this->institucion1->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->has('instituciones', 2)
            ->has('areas', 1)
            ->has('tipos', 0)
            ->where('areas.0.nombre', 'Ciencias e Ingenierías')
            ->where('areas.0.usa_banco_propio', true)
            ->where('areas.0.total_preguntas', 1)
            ->where('institucionSel.id', $this->institucion1->id)
            ->where('institucionSel.nombre', 'Universidad Nacional Mayor de San Marcos')
            ->where('filtros.institucion_id', (string) $this->institucion1->id)
        );
    }

    /** @test */
    public function lista_carreras_de_la_universidad_seleccionada(): void
    {
        $response = $this->get(route('examenes.universidades', [
            'institucion_id' => $this->institucion1->id,
        ]));

        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->has('carreras', 1)
            ->where('carreras.0.nombre', 'Ingeniería de Sistemas')
            ->where('carreras.0.area_nombre', 'Ciencias e Ingenierías')
        );
    }

    /** @test */
    public function lista_tipos_cuando_se_seleccionan_institucion_y_area(): void
    {
        $response = $this->get(route('examenes.universidades', [
            'institucion_id' => $this->institucion1->id,
            'area_id' => $this->area->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->has('instituciones', 2)
            ->has('areas', 1)
            ->has('tipos', 1)
            ->where('tipos.0.nombre', 'Simulacro General')
            ->where('tipos.0.num_preguntas', 10)
            ->where('areaSel.nombre', 'Ciencias e Ingenierías')
        );
    }

    /** @test */
    public function no_muestra_institucion_inactiva(): void
    {
        $this->institucion2->update(['activo' => false]);

        $response = $this->get(route('examenes.universidades'));

        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->has('instituciones', 1)
            ->where('instituciones.0.nombre', 'Universidad Nacional Mayor de San Marcos')
        );
    }

    /** @test */
    public function usa_banco_global_si_la_universidad_no_tiene_preguntas_propias(): void
    {
        // Eliminar la pregunta propia de la universidad 1
        Pregunta::where('institucion_id', $this->institucion1->id)->delete();

        $response = $this->get(route('examenes.universidades', [
            'institucion_id' => $this->institucion1->id,
        ]));

        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->has('areas', 1)
            ->where('areas.0.usa_banco_propio', false)
            ->where('areas.0.total_preguntas', 1)
        );
    }

    /** @test */
    public function devuelve_null_en_institucion_si_id_no_existe(): void
    {
        $response = $this->get(route('examenes.universidades', [
            'institucion_id' => 99999,
        ]));

        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->where('institucionSel', null)
            ->where('areaSel', null)
            ->has('areas', 0)
            ->has('tipos', 0)
        );
    }

    /** @test */
    public function funcionamiento_sin_universidades_ni_preguntas_creadas(): void
    {
        Pregunta::query()->delete();
        Categoria::query()->delete();
        Institucion::query()->delete();

        $response = $this->get(route('examenes.universidades'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->has('instituciones', 0)
            ->has('areas', 0)
            ->has('tipos', 0)
            ->where('institucionSel', null)
            ->where('areaSel', null)
        );
    }
}
