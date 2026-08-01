<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogo\Models\Categoria;
use Modules\Preguntas\Models\Concepto;
use Modules\Catalogo\Models\Examen;
use Modules\Catalogo\Models\Institucion;
use Modules\Catalogo\Models\TipoExamen;
use Modules\Preguntas\Models\Alternativa;
use Modules\Preguntas\Models\Pregunta;
use Modules\Rendicion\Models\IntentoExamen;
use Modules\Rendicion\Models\RespuestaUsuario;
use Tests\TestCase;

class RendicionRendirTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private User $otroUsuario;
    private Examen $examen;
    private Institucion $institucion;
    private IntentoExamen $intento;
    private Pregunta $pregunta1;
    private Pregunta $pregunta2;
    private Pregunta $pregunta3;
    private array $preguntas;

    protected function setUp(): void
    {
        parent::setUp();

        $tipo = TipoExamen::create([
            'slug' => 'admision-universitaria',
            'nombre' => 'Admisión Universitaria',
            'activo' => true,
        ]);

        $this->institucion = Institucion::create([
            'tipo_examen_id' => $tipo->id,
            'nombre' => 'Universidad de Prueba',
            'subtipo' => 'privada',
            'ciudad' => 'Lima',
            'activo' => true,
        ]);

        $categoria = Categoria::create([
            'institucion_id' => $this->institucion->id,
            'nombre' => 'Ingeniería',
            'descripcion_corta' => 'Carreras de Ingeniería',
            'orden' => 1,
            'activo' => true,
        ]);

        $this->examen = Examen::create([
            'categoria_id' => $categoria->id,
            'titulo' => 'Simulacro de Admisión - Ingeniería',
            'descripcion' => 'Test de práctica',
            'tiempo_limite_min' => 20,
            'intentos_permitidos' => 99,
            'num_alternativas_default' => 5,
            'preguntas_por_intento' => 10,
            'activo' => true,
        ]);

        $concepto = Concepto::create([
            'nombre' => 'Álgebra',
            'descripcion' => 'Preguntas sobre álgebra',
        ]);

        // ─── 3 preguntas activas con 5 alternativas cada una ─
        $this->pregunta1 = $this->crearPregunta($this->examen, $concepto, 'Pregunta 1', 1);
        $this->pregunta2 = $this->crearPregunta($this->examen, $concepto, 'Pregunta 2', 2);
        $this->pregunta3 = $this->crearPregunta($this->examen, $concepto, 'Pregunta 3', 3);
        $this->preguntas = [$this->pregunta1, $this->pregunta2, $this->pregunta3];

        // ─── Usuarios ───────────────────────────────────────
        $this->usuario = User::factory()->create([
            'name' => 'Estudiante Test',
            'email' => 'estudiante@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);

        $this->otroUsuario = User::factory()->create([
            'name' => 'Otro Estudiante',
            'email' => 'otro@test.com',
            'rol' => RolUsuario::Estudiante,
            'estado' => 'activo',
        ]);

        // ─── Intento en curso ───────────────────────────────
        $idsPreguntas = collect($this->preguntas)->pluck('id')->toArray();
        $this->intento = IntentoExamen::create([
            'usuario_id' => $this->usuario->id,
            'examen_id' => $this->examen->id,
            'carrera' => 'Ingeniería',
            'institucion_id' => $this->institucion->id,
            'estado' => 'en_curso',
            'fecha_inicio' => now()->subMinutes(10),
            'progreso_guardado' => ['preguntas_ids' => $idsPreguntas],
        ]);
    }

    private function crearPregunta(Examen $examen, Concepto $concepto, string $enunciado, int $orden): Pregunta
    {
        $pregunta = Pregunta::create([
            'examen_id' => $examen->id,
            'concepto_id' => $concepto->id,
            'enunciado' => $enunciado,
            'tipo' => 'opcion_multiple',
            'dificultad' => 'media',
            'orden' => $orden,
            'activa' => true,
        ]);

        for ($j = 0; $j < 5; $j++) {
            Alternativa::create([
                'pregunta_id' => $pregunta->id,
                'texto' => "Alt " . chr(65 + $j),
                'es_correcta' => $j === 0,
                'orden' => $j,
            ]);
        }

        return $pregunta;
    }

    private function rendirUrl(int $intentoId): string
    {
        return route('examenes.rendir', ['intento' => $intentoId]);
    }

    /** @test */
    public function muestra_pantalla_de_rendicion_con_preguntas(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get($this->rendirUrl($this->intento->id));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Rendicion/Index')
            ->has('intento')
            ->has('preguntas', 3)
            ->has('institucion')
            ->has('tiempoRestante')
            ->where('intento.id', $this->intento->id)
            ->where('intento.estado', 'en_curso')
            ->where('tiempoRestante', 1200)
        );
    }

    /** @test */
    public function muestra_institucion_en_respuesta(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get($this->rendirUrl($this->intento->id));

        $response->assertInertia(fn ($page) => $page
            ->component('Rendicion/Index')
            ->has('institucion', fn ($i) => $i
                ->has('id')
                ->has('nombre')
                ->has('subtipo')
                ->where('nombre', 'Universidad de Prueba')
            )
        );
    }

    /** @test */
    public function alternativas_no_incluyen_es_correcta(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get($this->rendirUrl($this->intento->id));

        $response->assertInertia(fn ($page) => $page
            ->component('Rendicion/Index')
            ->has('preguntas.0.alternativas.0', fn ($alt) => $alt
                ->where('texto', 'Alt A')
                ->missing('es_correcta')
                ->etc()
            )
            ->has('preguntas.1.alternativas.0', fn ($alt) => $alt
                ->missing('es_correcta')
                ->etc()
            )
            ->has('preguntas.2.alternativas.0', fn ($alt) => $alt
                ->missing('es_correcta')
                ->etc()
            )
        );
    }

    /** @test */
    public function respuesta_guardada_es_null_cuando_no_hay_respuestas(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get($this->rendirUrl($this->intento->id));

        $response->assertInertia(fn ($page) => $page
            ->component('Rendicion/Index')
            ->where('preguntas.0.respuesta_guardada', null)
        );
    }

    /** @test */
    public function incluye_respuesta_guardada_cuando_existe(): void
    {
        $altCorrecta = Alternativa::where('pregunta_id', $this->pregunta1->id)
            ->where('es_correcta', true)
            ->first();

        RespuestaUsuario::create([
            'intento_id' => $this->intento->id,
            'pregunta_id' => $this->pregunta1->id,
            'alternativa_id_elegida' => $altCorrecta->id,
            'es_correcta' => true,
        ]);

        $response = $this->actingAs($this->usuario)
            ->get($this->rendirUrl($this->intento->id));

        // Buscar en todas las preguntas que alguna tenga respuesta_guardada
        $response->assertInertia(fn ($page) => $page
            ->component('Rendicion/Index')
            ->has('preguntas', 3)
        );

        // Verificar que el id de la pregunta con respuesta esté presente
        $preguntasProps = $response->getOriginalContent()['page']['props']['preguntas'];
        $idsConRespuesta = collect($preguntasProps)
            ->filter(fn ($p) => $p['respuesta_guardada'] !== null)
            ->pluck('id')
            ->toArray();
        $this->assertContains($this->pregunta1->id, $idsConRespuesta);
    }

    /** @test */
    public function funciona_con_intento_sin_progreso_guardado(): void
    {
        $intentoSinProgreso = IntentoExamen::create([
            'usuario_id' => $this->usuario->id,
            'examen_id' => $this->examen->id,
            'carrera' => 'Ingeniería',
            'institucion_id' => $this->institucion->id,
            'estado' => 'en_curso',
            'fecha_inicio' => now()->subMinutes(5),
            'progreso_guardado' => null,
        ]);

        $response = $this->actingAs($this->usuario)
            ->get($this->rendirUrl($intentoSinProgreso->id));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Rendicion/Index')
            ->has('preguntas', 3)
        );
    }

    /** @test */
    public function solo_muestra_preguntas_activas(): void
    {
        $this->pregunta1->update(['activa' => false]);

        $response = $this->actingAs($this->usuario)
            ->get($this->rendirUrl($this->intento->id));

        $response->assertInertia(fn ($page) => $page
            ->component('Rendicion/Index')
            ->has('preguntas', 2)
        );

        // Verificar que la pregunta inactiva no esté en la lista
        $idsPreguntas = collect($response->getOriginalContent()['page']['props']['preguntas'])->pluck('id');
        $this->assertNotContains($this->pregunta1->id, $idsPreguntas->toArray());
    }

    /** @test */
    public function falla_si_intento_no_existe(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get($this->rendirUrl(99999));

        $response->assertStatus(404);
    }

    /** @test */
    public function deniega_acceso_a_intento_de_otro_usuario(): void
    {
        $response = $this->actingAs($this->otroUsuario)
            ->get($this->rendirUrl($this->intento->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function requiere_autenticacion(): void
    {
        $response = $this->get($this->rendirUrl($this->intento->id));

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
            ->get($this->rendirUrl($this->intento->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function muestra_pantalla_aun_si_intento_esta_completado(): void
    {
        $this->intento->update(['estado' => 'completado']);

        $response = $this->actingAs($this->usuario)
            ->get($this->rendirUrl($this->intento->id));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Rendicion/Index')
            ->where('intento.estado', 'completado')
        );
    }

    /** @test */
    public function tiempo_restante_refleja_tiempo_limite_del_examen(): void
    {
        $this->examen->update(['tiempo_limite_min' => 45]);

        $ids = collect($this->preguntas)->pluck('id')->toArray();
        $intento45 = IntentoExamen::create([
            'usuario_id' => $this->usuario->id,
            'examen_id' => $this->examen->id,
            'carrera' => 'Ingeniería',
            'institucion_id' => $this->institucion->id,
            'estado' => 'en_curso',
            'fecha_inicio' => now(),
            'progreso_guardado' => ['preguntas_ids' => $ids],
        ]);

        $response = $this->actingAs($this->usuario)
            ->get($this->rendirUrl($intento45->id));

        $response->assertInertia(fn ($page) => $page
            ->component('Rendicion/Index')
            ->where('tiempoRestante', 2700)
        );
    }

    /** @test */
    public function usa_30_segundos_cuando_tiempo_limite_es_30_minutos(): void
    {
        $this->examen->update(['tiempo_limite_min' => 30]);

        $ids = collect($this->preguntas)->pluck('id')->toArray();
        $intento30 = IntentoExamen::create([
            'usuario_id' => $this->usuario->id,
            'examen_id' => $this->examen->id,
            'carrera' => 'Ingeniería',
            'institucion_id' => $this->institucion->id,
            'estado' => 'en_curso',
            'fecha_inicio' => now(),
            'progreso_guardado' => ['preguntas_ids' => $ids],
        ]);

        $response = $this->actingAs($this->usuario)
            ->get($this->rendirUrl($intento30->id));

        $response->assertInertia(fn ($page) => $page
            ->component('Rendicion/Index')
            ->where('tiempoRestante', 1800)
        );
    }

    /** @test */
    public function cada_pregunta_tiene_5_alternativas(): void
    {
        $response = $this->actingAs($this->usuario)
            ->get($this->rendirUrl($this->intento->id));

        $response->assertInertia(fn ($page) => $page
            ->component('Rendicion/Index')
            ->has('preguntas.0.alternativas', 5)
            ->has('preguntas.1.alternativas', 5)
            ->has('preguntas.2.alternativas', 5)
        );
    }
}
