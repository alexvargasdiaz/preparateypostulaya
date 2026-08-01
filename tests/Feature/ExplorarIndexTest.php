<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogo\Models\Categoria;
use Modules\Preguntas\Models\Concepto;
use Modules\Catalogo\Models\Examen;
use Modules\Catalogo\Models\Institucion;
use Modules\Catalogo\Models\TipoExamen;
use Modules\Preguntas\Models\Alternativa;
use Modules\Preguntas\Models\Pregunta;
use Tests\TestCase;

class ExplorarIndexTest extends TestCase
{
    use RefreshDatabase;

    private TipoExamen $tipo;
    private Institucion $institucion1;
    private Institucion $institucion2;
    private Categoria $categoria1;
    private Categoria $categoria2;
    private Examen $examen1;
    private Examen $examen2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tipo = TipoExamen::create([
            'slug' => 'admision-universitaria',
            'nombre' => 'Admisión Universitaria',
            'activo' => true,
        ]);

        // ─── Institución 1 ──────────────────────────────────
        $this->institucion1 = Institucion::create([
            'tipo_examen_id' => $this->tipo->id,
            'nombre' => 'Universidad Nacional Mayor de San Marcos',
            'subtipo' => 'publica',
            'ciudad' => 'Lima',
            'activo' => true,
        ]);

        $this->categoria1 = Categoria::create([
            'institucion_id' => $this->institucion1->id,
            'nombre' => 'Ingeniería',
            'descripcion_corta' => 'Carreras de Ingeniería',
            'orden' => 1,
            'activo' => true,
        ]);

        $this->examen1 = Examen::create([
            'categoria_id' => $this->categoria1->id,
            'titulo' => 'Simulacro de Admisión 2026 - Ingeniería',
            'descripcion' => 'Primer simulacro',
            'tiempo_limite_min' => 20,
            'intentos_permitidos' => 99,
            'num_alternativas_default' => 5,
            'preguntas_por_intento' => 10,
            'activo' => true,
        ]);

        // Pregunta activa para examen1
        $concepto = Concepto::create(['nombre' => 'Álgebra', 'descripcion' => '']);
        $pregunta = Pregunta::create([
            'examen_id' => $this->examen1->id,
            'concepto_id' => $concepto->id,
            'enunciado' => 'Pregunta demo',
            'tipo' => 'opcion_multiple',
            'dificultad' => 'media',
            'orden' => 1,
            'activa' => true,
        ]);
        Alternativa::create([
            'pregunta_id' => $pregunta->id,
            'texto' => 'Alternativa A',
            'es_correcta' => true,
            'orden' => 0,
        ]);

        // ─── Institución 2 ──────────────────────────────────
        $this->institucion2 = Institucion::create([
            'tipo_examen_id' => $this->tipo->id,
            'nombre' => 'Universidad del Pacífico',
            'subtipo' => 'privada',
            'ciudad' => 'Lima',
            'activo' => true,
        ]);

        $this->categoria2 = Categoria::create([
            'institucion_id' => $this->institucion2->id,
            'nombre' => 'Administración',
            'descripcion_corta' => 'Carreras de Administración',
            'orden' => 1,
            'activo' => true,
        ]);

        $this->examen2 = Examen::create([
            'categoria_id' => $this->categoria2->id,
            'titulo' => 'Simulacro de Admisión 2026 - Administración',
            'descripcion' => 'Segundo simulacro',
            'tiempo_limite_min' => 20,
            'intentos_permitidos' => 99,
            'num_alternativas_default' => 5,
            'preguntas_por_intento' => 10,
            'activo' => true,
        ]);
    }

    /** @test */
    public function lista_todas_las_instituciones_sin_filtros(): void
    {
        $response = $this->get(route('examenes.universidades'));

        $response->assertOk();

        // Usar Inertia::assertInertia o Inertia::render
        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->has('instituciones', 2)
            ->has('categorias', 0)
            ->has('examenes', 0)
            ->where('institucionSel', null)
            ->where('categoriaSel', null)
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
            ->where('instituciones.0.nombre', 'Universidad Nacional Mayor de San Marcos')
            ->where('instituciones.1.nombre', 'Universidad del Pacífico')
        );
    }

    /** @test */
    public function lista_categorias_cuando_se_selecciona_institucion(): void
    {
        $response = $this->get(route('examenes.universidades', [
            'institucion_id' => $this->institucion1->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->has('instituciones', 2)
            ->has('categorias', 1)
            ->has('examenes', 0)
            ->where('categorias.0.nombre', 'Ingeniería')
            ->where('categorias.0.examenes_count', 1)
            ->where('institucionSel.id', $this->institucion1->id)
            ->where('institucionSel.nombre', 'Universidad Nacional Mayor de San Marcos')
            ->where('filtros.institucion_id', (string) $this->institucion1->id)
        );
    }

    /** @test */
    public function lista_examenes_cuando_se_seleccionan_institucion_y_categoria(): void
    {
        $response = $this->get(route('examenes.universidades', [
            'institucion_id' => $this->institucion1->id,
            'categoria_id' => $this->categoria1->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->has('instituciones', 2)
            ->has('categorias', 1)
            ->has('examenes', 1)
            ->where('examenes.0.titulo', 'Simulacro de Admisión 2026 - Ingeniería')
            ->where('examenes.0.preguntas_count', 1)
            ->where('categoriaSel.nombre', 'Ingeniería')
        );
    }

    /** @test */
    public function no_muestra_institucion_sin_categorias_con_examenes(): void
    {
        // Institución 1 sin categorías con exámenes activos
        $this->examen1->update(['activo' => false]);

        $response = $this->get(route('examenes.universidades'));

        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->has('instituciones', 1)
            ->where('instituciones.0.nombre', 'Universidad del Pacífico')
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
    public function no_muestra_categoria_sin_examenes_activos(): void
    {
        $this->examen2->update(['activo' => false]);

        // Institución 2 debe aparecer (puede tener otras categorías),
        // pero al seleccionarla no debe mostrar categorías sin exámenes activos
        $response = $this->get(route('examenes.universidades', [
            'institucion_id' => $this->institucion2->id,
        ]));

        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->has('instituciones', 1)
            ->has('categorias', 0)
            ->has('examenes', 0)
        );
    }

    /** @test */
    public function no_muestra_examen_inactivo(): void
    {
        $this->examen1->update(['activo' => false]);

        $response = $this->get(route('examenes.universidades', [
            'institucion_id' => $this->institucion1->id,
            'categoria_id' => $this->categoria1->id,
        ]));

        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->has('examenes', 0)
        );
    }

    /** @test */
    public function categorias_count_refleja_solo_categorias_con_examenes(): void
    {
        // Crear una segunda categoría para institución 1, SIN exámenes
        Categoria::create([
            'institucion_id' => $this->institucion1->id,
            'nombre' => 'Derecho',
            'descripcion_corta' => 'Carreras de Derecho',
            'orden' => 2,
            'activo' => true,
        ]);

        $response = $this->get(route('examenes.universidades'));

        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->where('instituciones.1.nombre', 'Universidad del Pacífico')
            // categorias_count = 1 porque Derecho no tiene exámenes activos
            ->where('instituciones.1.categorias_count', 1)
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
            ->where('categoriaSel', null)
            ->has('categorias', 0)
            ->has('examenes', 0)
        );
    }

    /** @test */
    public function examenes_se_listan_con_preguntas_count_correcto(): void
    {
        // Crear 3 preguntas activas más
        $concepto = Concepto::first();
        for ($i = 1; $i <= 3; $i++) {
            $p = Pregunta::create([
                'examen_id' => $this->examen1->id,
                'concepto_id' => $concepto->id,
                'enunciado' => "Pregunta extra {$i}",
                'tipo' => 'opcion_multiple',
                'dificultad' => 'media',
                'orden' => 1 + $i,
                'activa' => true,
            ]);
            Alternativa::create([
                'pregunta_id' => $p->id,
                'texto' => 'A',
                'es_correcta' => true,
                'orden' => 0,
            ]);
        }

        $response = $this->get(route('examenes.universidades', [
            'institucion_id' => $this->institucion1->id,
            'categoria_id' => $this->categoria1->id,
        ]));

        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->where('examenes.0.preguntas_count', 4) // 1 original + 3 nuevas
        );
    }

    /** @test */
    public function funcionamiento_sin_examenes_ni_preguntas_creadas(): void
    {
        // Vaciar todo excepto el catálogo base
        Examen::query()->delete();
        Categoria::query()->delete();
        Institucion::query()->delete();

        // La consulta simplemente no devuelve instituciones (whereHas falla)
        $response = $this->get(route('examenes.universidades'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Examenes/Universidades/Index')
            ->has('instituciones', 0)
            ->has('categorias', 0)
            ->has('examenes', 0)
            ->where('institucionSel', null)
            ->where('categoriaSel', null)
        );
    }
}
