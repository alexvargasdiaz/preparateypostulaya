<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogo\Models\Categoria;
use Modules\Catalogo\Models\Examen;
use Modules\Catalogo\Models\Institucion;
use Modules\Catalogo\Models\TipoExamen;
use Modules\Preguntas\Models\AreaAcademica;
use Modules\Preguntas\Models\Concepto;
use Modules\Preguntas\Models\DiagnosticoConcepto;
use Modules\Preguntas\Models\TipoSimulacro;
use Modules\Rendicion\Models\IntentoExamen;
use Tests\TestCase;

class BitacoraAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->admin = User::factory()->create([
            'name' => 'Admin Bitácora',
            'email' => 'admin-bitacora@test.com',
            'rol' => RolUsuario::Admin,
            'estado' => 'activo',
        ]);
    }

    private function crearAlumno(string $estado = 'activo', ?string $createdAt = null): User
    {
        $user = User::factory()->create([
            'rol' => RolUsuario::Estudiante,
            'estado' => $estado,
        ]);

        if ($createdAt) {
            $user->forceFill(['created_at' => $createdAt])->save();
        }

        return $user;
    }

    private function crearIntento(array $data, string $createdAt): IntentoExamen
    {
        $intento = IntentoExamen::create($data);
        $intento->forceFill(['created_at' => $createdAt])->save();

        return $intento;
    }

    /** @test */
    public function admin_puede_ver_la_bitacora(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.bitacora'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Bitacora/Index')
            ->has('bitacora.kpis')
            ->has('bitacora.embudo', 5)
            ->has('bitacora.registro')
            ->has('bitacora.diagnostico')
            ->has('bitacora.areas')
            ->has('bitacora.universidades')
            ->has('bitacora.resultados')
            ->has('bitacora.actividadReciente')
            ->has('bitacora.fechaActualizacion')
        );
    }

    /** @test */
    public function bitacora_calcula_los_datos_de_los_procesos(): void
    {
        $alumno = $this->crearAlumno('activo', '2026-07-01 09:00:00');
        $this->crearAlumno('pendiente', '2026-08-01 09:00:00');

        $area = AreaAcademica::create([
            'nombre' => 'Ciencias Básicas e Ingenierías',
            'num_preguntas' => 100,
            'duracion_min' => 120,
            'activo' => true,
        ]);
        $concepto = Concepto::create([
            'area_academica_id' => $area->id,
            'nombre' => 'Matemática',
        ]);
        DiagnosticoConcepto::create([
            'concepto_id' => $concepto->id,
            'preguntas_por_concepto' => 5,
            'duracion_minutos' => 60,
        ]);

        $examen = Examen::create([
            'area_academica_id' => $area->id,
            'tipo' => 'especifico',
            'titulo' => 'Examen de Ciencias',
            'tiempo_limite_min' => 120,
            'preguntas_por_intento' => 10,
            'activo' => true,
        ]);

        $tipoExamen = TipoExamen::create([
            'nombre' => 'Admisión',
            'slug' => 'admision',
            'activo' => true,
        ]);
        $institucion = Institucion::create([
            'tipo_examen_id' => $tipoExamen->id,
            'nombre' => 'Universidad Nacional de Ingeniería',
            'activo' => true,
        ]);
        $categoria = Categoria::create([
            'institucion_id' => $institucion->id,
            'area_academica_id' => $area->id,
            'nombre' => 'Ingeniería Civil',
            'activo' => true,
        ]);
        $tipo = TipoSimulacro::create([
            'area_academica_id' => $area->id,
            'nombre' => 'Simulacro de admisión',
            'num_preguntas' => 10,
            'duracion_min' => 60,
            'activo' => true,
        ]);

        // 1) Diagnóstico completado
        $this->crearIntento([
            'usuario_id' => $alumno->id,
            'examen_id' => null,
            'estado' => 'completado',
            'fecha_inicio' => now()->subHour(),
            'fecha_fin' => now(),
            'puntaje_total' => 8,
            'puntaje_maximo' => 10,
            'aprobado' => true,
        ], '2026-08-01 10:00:00');

        // 2) Simulacro por área completado
        $this->crearIntento([
            'usuario_id' => $alumno->id,
            'examen_id' => $examen->id,
            'area_academica_id' => $area->id,
            'carrera' => $area->nombre,
            'estado' => 'completado',
            'fecha_inicio' => now()->subMinutes(40),
            'fecha_fin' => now(),
            'puntaje_total' => 6,
            'puntaje_maximo' => 10,
            'aprobado' => true,
        ], '2026-08-01 11:00:00');

        // 3) Simulacro por universidad completado
        $this->crearIntento([
            'usuario_id' => $alumno->id,
            'institucion_id' => $institucion->id,
            'categoria_id' => $categoria->id,
            'area_academica_id' => $area->id,
            'tipo_simulacro_id' => $tipo->id,
            'carrera' => $categoria->nombre,
            'estado' => 'completado',
            'fecha_inicio' => now()->subMinutes(20),
            'fecha_fin' => now(),
            'puntaje_total' => 7,
            'puntaje_maximo' => 10,
            'aprobado' => true,
        ], '2026-08-01 12:00:00');

        $response = $this->actingAs($this->admin)->get(route('admin.bitacora'));

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Bitacora/Index')
            // ── KPIs ──
            ->where('bitacora.kpis.alumnos', 2)
            ->where('bitacora.kpis.alumnos_aprobados', 1)
            ->where('bitacora.kpis.alumnos_pendientes', 1)
            ->where('bitacora.kpis.intentos_total', 3)
            ->where('bitacora.kpis.intentos_completados', 3)
            ->where('bitacora.kpis.diagnosticos_completados', 1)
            ->where('bitacora.kpis.simulacros_completados', 2)
            // ── Embudo (registrados=2, aprobados=1, diag iniciado=1, diag completado=1, simulacro=1) ──
            ->where('bitacora.embudo.0.valor', 2)
            ->where('bitacora.embudo.1.valor', 1)
            ->where('bitacora.embudo.2.valor', 1)
            ->where('bitacora.embudo.3.valor', 1)
            ->where('bitacora.embudo.4.valor', 1)
            ->where('bitacora.embudo.4.conversion', 100)
            // ── Proceso 1: Registro ──
            ->where('bitacora.registro.tasa_aprobacion', 50)
            ->where('bitacora.registro.por_estado.0.estado', 'pendiente')
            ->where('bitacora.registro.por_estado.1.estado', 'activo')
            // ── Proceso 2: Diagnóstico ──
            ->where('bitacora.diagnostico.config.conceptos_configurados', 1)
            ->where('bitacora.diagnostico.config.conceptos_totales', 1)
            ->where('bitacora.diagnostico.intentos.completados', 1)
            ->where('bitacora.diagnostico.intentos.aprobados', 1)
            ->where('bitacora.diagnostico.alumnos_con_diagnostico', 1)
            // ── Proceso 3: Áreas (2 intentos en el área: uno de área + uno de universidad) ──
            ->where('bitacora.areas.0.nombre', 'Ciencias Básicas e Ingenierías')
            ->where('bitacora.areas.0.intentos', 2)
            ->where('bitacora.areas.0.completados', 2)
            ->where('bitacora.areas.0.aprobados', 2)
            // ── Proceso 4: Universidades ──
            ->where('bitacora.universidades.por_institucion.0.nombre', 'Universidad Nacional de Ingeniería')
            ->where('bitacora.universidades.por_institucion.0.intentos', 1)
            ->where('bitacora.universidades.por_institucion.0.aprobados', 1)
            ->where('bitacora.universidades.top_carreras.0.carrera', 'Ciencias Básicas e Ingenierías')
            ->where('bitacora.universidades.top_carreras.1.carrera', 'Ingeniería Civil')
            // ── Proceso 5: Resultados ──
            ->where('bitacora.resultados.completados', 3)
            ->where('bitacora.resultados.aprobados', 3)
            ->where('bitacora.resultados.desaprobados', 0)
            // ── Actividad reciente (el más reciente es el simulacro por universidad) ──
            ->where('bitacora.actividadReciente.intentos.0.tipo', 'universidad')
            ->where('bitacora.actividadReciente.intentos.1.tipo', 'area')
            ->where('bitacora.actividadReciente.intentos.2.tipo', 'diagnostico')
            ->where('bitacora.actividadReciente.registros.0.estado', 'pendiente')
        );
    }

    /** @test */
    public function estudiante_no_puede_acceder_a_la_bitacora(): void
    {
        $estudiante = $this->crearAlumno();

        $response = $this->actingAs($estudiante)->get(route('admin.bitacora'));

        $response->assertStatus(403);
    }

    /** @test */
    public function requiere_autenticacion(): void
    {
        $response = $this->get(route('admin.bitacora'));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function admin_puede_exportar_la_bitacora_a_excel(): void
    {
        $this->crearAlumno('activo');

        $response = $this->actingAs($this->admin)->get(route('admin.bitacora.exportar-excel'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('bitacora_procesos_', $response->headers->get('content-disposition'));

        // El archivo debe contener las hojas esperadas (firma OOXML ZIP)
        $contenido = $response->streamedContent();
        $this->assertStringStartsWith('PK', $contenido);
    }

    /** @test */
    public function admin_puede_exportar_la_bitacora_a_pdf(): void
    {
        $this->crearAlumno('activo');

        $response = $this->actingAs($this->admin)->get(route('admin.bitacora.exportar-pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('.pdf', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('bitacora_procesos_', $response->headers->get('content-disposition'));

        // Firma del PDF: %PDF
        $contenido = $response->getContent();
        $this->assertStringStartsWith('%PDF', $contenido);
    }

    /** @test */
    public function estudiante_no_puede_exportar_la_bitacora(): void
    {
        $estudiante = $this->crearAlumno();

        $this->actingAs($estudiante)->get(route('admin.bitacora.exportar-excel'))->assertStatus(403);
        $this->actingAs($estudiante)->get(route('admin.bitacora.exportar-pdf'))->assertStatus(403);
    }

    /** @test */
    public function excel_puede_exportar_una_seccion_especifica(): void
    {
        $datos = app(\Modules\Bitacora\Services\BitacoraService::class)->obtenerDatos();

        $completo = new \Modules\Bitacora\Exports\BitacoraExport($datos);
        $this->assertCount(8, $completo->sheets(), 'El export completo debe tener 8 hojas.');

        $embudo = new \Modules\Bitacora\Exports\BitacoraExport($datos, 'embudo');
        $this->assertCount(1, $embudo->sheets());
        $this->assertSame('Embudo', $embudo->sheets()[0]->title());

        $universidades = new \Modules\Bitacora\Exports\BitacoraExport($datos, 'universidades');
        $this->assertCount(1, $universidades->sheets());
        $this->assertSame('Universidades', $universidades->sheets()[0]->title());

        // Sección inválida → se comporta como export completo
        $invalida = new \Modules\Bitacora\Exports\BitacoraExport($datos, 'no-existe');
        $this->assertCount(8, $invalida->sheets());
    }

    /** @test */
    public function admin_puede_exportar_excel_de_una_seccion_por_http(): void
    {
        $this->crearAlumno('activo');

        $response = $this->actingAs($this->admin)->get(route('admin.bitacora.exportar-excel', ['seccion' => 'embudo']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('bitacora_procesos_embudo_', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('PK', $response->streamedContent());
    }

    /** @test */
    public function la_vista_pdf_filtra_por_seccion(): void
    {
        $datos = app(\Modules\Bitacora\Services\BitacoraService::class)->obtenerDatos();

        $html = view('bitacora::exports.bitacora-pdf', ['bitacora' => $datos, 'seccion' => 'universidades'])->render();
        $this->assertStringContainsString('Simulacros por universidad y carrera', $html);
        $this->assertStringNotContainsString('Actividad reciente', $html);
        $this->assertStringNotContainsString('Embudo de los procesos', $html);

        $htmlEmbudo = view('bitacora::exports.bitacora-pdf', ['bitacora' => $datos, 'seccion' => 'embudo'])->render();
        $this->assertStringContainsString('Embudo de los procesos', $htmlEmbudo);
        $this->assertStringNotContainsString('Diagnóstico', $htmlEmbudo);

        // Sin sección → reporte completo
        $htmlCompleto = view('bitacora::exports.bitacora-pdf', ['bitacora' => $datos, 'seccion' => null])->render();
        $this->assertStringContainsString('Actividad reciente', $htmlCompleto);
        $this->assertStringContainsString('Embudo de los procesos', $htmlCompleto);
    }

    /** @test */
    public function admin_puede_exportar_pdf_de_una_seccion_por_http(): void
    {
        $this->crearAlumno('activo');

        $response = $this->actingAs($this->admin)->get(route('admin.bitacora.exportar-pdf', ['seccion' => 'areas']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('.pdf', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('bitacora_procesos_areas_', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /** @test */
    public function bitacora_filtra_la_actividad_por_rango_de_fechas(): void
    {
        // Alumno registrado en julio y alumno registrado en agosto
        $alumnoJulio = $this->crearAlumno('activo', '2026-07-01 09:00:00');
        $this->crearAlumno('activo', '2026-08-15 09:00:00');

        // Intento de julio y intento de agosto
        $this->crearIntento([
            'usuario_id' => $alumnoJulio->id,
            'examen_id' => null,
            'estado' => 'completado',
            'puntaje_total' => 8,
            'puntaje_maximo' => 10,
            'aprobado' => true,
        ], '2026-07-10 10:00:00');
        $this->crearIntento([
            'usuario_id' => $alumnoJulio->id,
            'examen_id' => null,
            'estado' => 'completado',
            'puntaje_total' => 6,
            'puntaje_maximo' => 10,
            'aprobado' => true,
        ], '2026-08-20 10:00:00');

        // Filtro: solo julio
        $response = $this->actingAs($this->admin)->get(route('admin.bitacora', [
            'desde' => '2026-07-01',
            'hasta' => '2026-07-31',
        ]));

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Bitacora/Index')
            ->where('filtros.desde', '2026-07-01')
            ->where('filtros.hasta', '2026-07-31')
            ->where('bitacora.kpis.alumnos', 1)
            ->where('bitacora.kpis.intentos_total', 1)
            ->where('bitacora.kpis.diagnosticos_completados', 1)
            ->where('bitacora.registro.por_estado.1.total', 1)
        );

        // Filtro: solo agosto
        $responseAgosto = $this->actingAs($this->admin)->get(route('admin.bitacora', [
            'desde' => '2026-08-01',
            'hasta' => '2026-08-31',
        ]));

        $responseAgosto->assertInertia(fn ($page) => $page
            ->where('bitacora.kpis.alumnos', 1)
            ->where('bitacora.kpis.intentos_total', 1)
            ->where('bitacora.registro.por_estado.1.total', 1)
        );
    }

    /** @test */
    public function bitacora_ignora_fechas_invalidas(): void
    {
        $this->crearAlumno('activo', '2026-07-01 09:00:00');

        $response = $this->actingAs($this->admin)->get(route('admin.bitacora', [
            'desde' => '2026-02-30', // fecha imposible
            'hasta' => 'no-es-fecha',
        ]));

        $response->assertInertia(fn ($page) => $page
            ->where('filtros.desde', null)
            ->where('filtros.hasta', null)
            ->where('bitacora.kpis.alumnos', 1)
        );
    }

    /** @test */
    public function el_rango_de_fechas_se_incorpora_a_las_exportaciones(): void
    {
        $this->crearAlumno('activo', '2026-07-01 09:00:00');

        // Excel
        $excel = $this->actingAs($this->admin)->get(route('admin.bitacora.exportar-excel', [
            'desde' => '2026-07-01',
            'hasta' => '2026-07-31',
            'seccion' => 'embudo',
        ]));
        $excel->assertOk();
        $this->assertStringContainsString('bitacora_procesos_embudo_2026-07-01_a_2026-07-31_', $excel->headers->get('content-disposition'));

        // PDF: el header incluye el período
        $pdf = $this->actingAs($this->admin)->get(route('admin.bitacora.exportar-pdf', [
            'desde' => '2026-07-01',
            'hasta' => '2026-07-31',
        ]));
        $pdf->assertOk();
        $this->assertStringContainsString('bitacora_procesos_2026-07-01_a_2026-07-31_', $pdf->headers->get('content-disposition'));

        // El texto del período se verifica en la vista (el PDF binario comprime su contenido)
        $html = view('bitacora::exports.bitacora-pdf', [
            'bitacora' => app(\Modules\Bitacora\Services\BitacoraService::class)->obtenerDatos('2026-07-01', '2026-07-31'),
            'seccion' => null,
            'desde' => '2026-07-01',
            'hasta' => '2026-07-31',
        ])->render();
        $this->assertStringContainsString('Del 2026-07-01 al 2026-07-31', $html);
    }
}
