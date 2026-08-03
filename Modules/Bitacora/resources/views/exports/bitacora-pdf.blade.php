<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bitácora de Procesos</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }
        .header {
            background: #0a0f1e;
            color: #fff;
            padding: 18px 24px;
            border-radius: 8px;
            margin-bottom: 14px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
        }
        .header p {
            margin: 4px 0 0;
            color: #00f0ff;
            font-size: 10px;
            font-weight: 600;
        }
        .kpi-grid {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: collapse;
        }
        .kpi-grid td {
            width: 25%;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
        }
        .kpi-grid .num {
            font-size: 16px;
            font-weight: 800;
            color: #0a0f1e;
        }
        .kpi-grid .lbl {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 700;
        }
        h2 {
            font-size: 13px;
            font-weight: 800;
            color: #0a0f1e;
            border-bottom: 2px solid #00f0ff;
            padding-bottom: 4px;
            margin: 18px 0 8px;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.data th {
            background: #0a0f1e;
            color: #fff;
            padding: 5px 7px;
            text-align: left;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        table.data td {
            border: 1px solid #e5e7eb;
            padding: 4px 7px;
            font-size: 9px;
        }
        table.data tr:nth-child(even) td {
            background: #f9fafb;
        }
        .section {
            page-break-inside: avoid;
        }
        .page-break {
            page-break-before: always;
        }
        .foot {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            color: #9ca3af;
            font-size: 8px;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: 700;
        }
        .b-green { background: #dcfce7; color: #15803d; }
        .b-yellow { background: #fef9c3; color: #a16207; }
        .b-red { background: #fee2e2; color: #b91c1c; }
        .b-cyan { background: #cffafe; color: #0e7490; }
    </style>
</head>
<body>

    @php
        $k = $bitacora['kpis'] ?? [];
        $reg = $bitacora['registro'] ?? [];
        $diag = $bitacora['diagnostico'] ?? [];
        $areas = $bitacora['areas'] ?? [];
        $unis = $bitacora['universidades'] ?? [];
        $res = $bitacora['resultados'] ?? [];
        $act = $bitacora['actividadReciente'] ?? [];
    @endphp

    @php
        $seccion = $seccion ?? null;
        $mostrar = fn (string $s) => $seccion === null || $seccion === $s;
        $tituloSeccion = [
            'resumen' => 'Resumen (KPIs)',
            'embudo' => 'Embudo de los procesos',
            'registro' => 'Registro y aprobación',
            'diagnostico' => 'Diagnóstico',
            'areas' => 'Simulacros por área académica',
            'universidades' => 'Simulacros por universidad y carrera',
            'resultados' => 'Resultados y envíos',
            'actividad' => 'Actividad reciente',
        ][$seccion] ?? null;
    @endphp

    @php
        $desde = $desde ?? null;
        $hasta = $hasta ?? null;
        $periodo = null;
        if ($desde && $hasta) {
            $periodo = "Del {$desde} al {$hasta}";
        } elseif ($desde) {
            $periodo = "Desde el {$desde}";
        } elseif ($hasta) {
            $periodo = "Hasta el {$hasta}";
        }
    @endphp

    <div class="header">
        <h1>Bitácora de Procesos{{ $tituloSeccion ? ' — ' . $tituloSeccion : '' }}</h1>
        <p>Prepárate y Postula Ya · Generado el {{ $bitacora['fechaActualizacion'] ?? now()->format('d/m/Y H:i') }}{{ $seccion ? ' · Solo sección: ' . $tituloSeccion : ' · Reporte completo' }}{{ $periodo ? ' · ' . $periodo : '' }}</p>
    </div>

    {{-- KPIs --}}
    @if ($mostrar('resumen'))
    <table class="kpi-grid">
        <tr>
            <td><div class="num">{{ $k['alumnos'] ?? 0 }}</div><div class="lbl">Alumnos registrados</div></td>
            <td><div class="num">{{ $k['alumnos_aprobados'] ?? 0 }}</div><div class="lbl">Aprobados</div></td>
            <td><div class="num">{{ $k['alumnos_pendientes'] ?? 0 }}</div><div class="lbl">Pendientes</div></td>
            <td><div class="num">{{ $k['alumnos_rechazados'] ?? 0 }}</div><div class="lbl">Rechazados</div></td>
        </tr>
        <tr>
            <td><div class="num">{{ $k['intentos_total'] ?? 0 }}</div><div class="lbl">Intentos totales</div></td>
            <td><div class="num">{{ $k['diagnosticos_completados'] ?? 0 }}</div><div class="lbl">Diagnósticos comp.</div></td>
            <td><div class="num">{{ $k['simulacros_completados'] ?? 0 }}</div><div class="lbl">Simulacros comp.</div></td>
            <td><div class="num">{{ $k['emails_enviados'] ?? 0 }} / {{ $k['whatsapp_solicitados'] ?? 0 }}</div><div class="lbl">Emails / WhatsApp</div></td>
        </tr>
    </table>
    @endif

    {{-- Embudo --}}
    @if ($mostrar('embudo'))
    <div class="section">
        <h2>1. Embudo de los procesos</h2>
        <table class="data">
            <thead><tr><th>Etapa</th><th>Alumnos</th><th>Conversión etapa previa</th><th>% del total</th></tr></thead>
            <tbody>
                @foreach ($bitacora['embudo'] ?? [] as $etapa)
                    <tr>
                        <td>{{ $etapa['label'] }}</td>
                        <td>{{ $etapa['valor'] }}</td>
                        <td>{{ $etapa['conversion'] }}%</td>
                        <td>{{ $etapa['conversion_total'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @endif

    {{-- Registro --}}
    @if ($mostrar('registro'))
    <div class="section">
        <h2>2. Registro y aprobación de alumnos</h2>
        <table class="data">
            <thead><tr><th>Estado</th><th>Total</th></tr></thead>
            <tbody>
                @foreach ($reg['por_estado'] ?? [] as $estado)
                    <tr><td>{{ $estado['label'] }}</td><td>{{ $estado['total'] }}</td></tr>
                @endforeach
                <tr><td><b>Tasa de aprobación</b></td><td><b>{{ $reg['tasa_aprobacion'] ?? 0 }}%</b></td></tr>
            </tbody>
        </table>

        <table class="data">
            <thead><tr><th>Últimos registros</th><th>Email</th><th>Estado</th><th>Fecha</th></tr></thead>
            <tbody>
                @forelse ($reg['ultimos'] ?? [] as $u)
                    <tr>
                        <td>{{ $u['name'] }}</td>
                        <td>{{ $u['email'] }}</td>
                        <td>{{ $u['estado'] }}</td>
                        <td>{{ $u['fecha'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Sin registros todavía</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @endif

    {{-- Diagnóstico --}}
    @if ($mostrar('diagnostico'))
    <div class="section">
        <h2>3. Diagnóstico</h2>
        <table class="data">
            <thead><tr><th>Conceptos configurados</th><th>Conceptos totales</th><th>Preguntas por concepto</th><th>Duración (min)</th></tr></thead>
            <tbody>
                <tr>
                    <td>{{ $diag['config']['conceptos_configurados'] ?? 0 }}</td>
                    <td>{{ $diag['config']['conceptos_totales'] ?? 0 }}</td>
                    <td>{{ $diag['config']['promedio_preguntas_por_concepto'] ?? '—' }}</td>
                    <td>{{ $diag['config']['duracion_minutos'] ?? 'Auto' }}</td>
                </tr>
            </tbody>
        </table>
        <table class="data">
            <thead><tr><th>Iniciados</th><th>En curso</th><th>Completados</th><th>Abandonados</th><th>Aprobados</th><th>Promedio %</th><th>Alumnos</th></tr></thead>
            <tbody>
                <tr>
                    <td>{{ $diag['intentos']['iniciados'] ?? 0 }}</td>
                    <td>{{ $diag['intentos']['en_curso'] ?? 0 }}</td>
                    <td>{{ $diag['intentos']['completados'] ?? 0 }}</td>
                    <td>{{ $diag['intentos']['abandonados'] ?? 0 }}</td>
                    <td>{{ $diag['intentos']['aprobados'] ?? 0 }}</td>
                    <td>{{ $diag['intentos']['promedio_puntaje'] ?? '—' }}</td>
                    <td>{{ $diag['alumnos_con_diagnostico'] ?? 0 }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @endif

    {{-- Áreas --}}
    @if ($mostrar('areas'))
    <div class="section">
        <h2>4. Simulacros por área académica</h2>
        <table class="data">
            <thead><tr><th>Área</th><th>Activa</th><th>Intentos</th><th>Completados</th><th>Aprobados</th><th>Promedio %</th></tr></thead>
            <tbody>
                @forelse ($areas as $area)
                    <tr>
                        <td>{{ $area['nombre'] }}</td>
                        <td>{{ $area['activo'] ? 'Sí' : 'No' }}</td>
                        <td>{{ $area['intentos'] }}</td>
                        <td>{{ $area['completados'] }}</td>
                        <td>{{ $area['aprobados'] }}</td>
                        <td>{{ $area['promedio'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No hay áreas académicas</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @endif

    {{-- Universidades --}}
    @if ($mostrar('universidades'))
    <div class="section">
        <h2>5. Simulacros por universidad y carrera</h2>
        <table class="data">
            <thead><tr><th>Universidad</th><th>Intentos</th><th>Completados</th><th>Aprobados</th><th>Carreras</th><th>Promedio %</th></tr></thead>
            <tbody>
                @forelse ($unis['por_institucion'] ?? [] as $inst)
                    <tr>
                        <td>{{ $inst['nombre'] }}</td>
                        <td>{{ $inst['intentos'] }}</td>
                        <td>{{ $inst['completados'] }}</td>
                        <td>{{ $inst['aprobados'] }}</td>
                        <td>{{ $inst['carreras'] }}</td>
                        <td>{{ $inst['promedio'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Sin simulacros por universidad</td></tr>
                @endforelse
            </tbody>
        </table>

        <table class="data">
            <thead><tr><th>Carrera</th><th>Intentos</th><th>Completados</th><th>Aprobados</th></tr></thead>
            <tbody>
                @forelse ($unis['top_carreras'] ?? [] as $carrera)
                    <tr>
                        <td>{{ $carrera['carrera'] }}</td>
                        <td>{{ $carrera['intentos'] }}</td>
                        <td>{{ $carrera['completados'] }}</td>
                        <td>{{ $carrera['aprobados'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Aún no hay carreras con simulacros</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @endif

    {{-- Resultados --}}
    @if ($mostrar('resultados'))
    <div class="section">
        <h2>6. Resultados y envíos</h2>
        <table class="data">
            <thead><tr><th>Completados</th><th>Aprobados</th><th>Desaprobados</th><th>Promedio global %</th></tr></thead>
            <tbody>
                <tr>
                    <td>{{ $res['completados'] ?? 0 }}</td>
                    <td>{{ $res['aprobados'] ?? 0 }}</td>
                    <td>{{ $res['desaprobados'] ?? 0 }}</td>
                    <td>{{ $res['promedio_global'] ?? '—' }}</td>
                </tr>
            </tbody>
        </table>
        <table class="data">
            <thead><tr><th>Registros por concepto</th><th>Intentos con resultados</th><th>Promedio de acierto %</th><th>Emails</th><th>WhatsApp</th></tr></thead>
            <tbody>
                <tr>
                    <td>{{ $res['resultados_concepto']['registros'] ?? 0 }}</td>
                    <td>{{ $res['resultados_concepto']['intentos_con_resultados'] ?? 0 }}</td>
                    <td>{{ $res['resultados_concepto']['promedio_acierto'] ?? 0 }}%</td>
                    <td>{{ $res['envios']['emails'] ?? 0 }}</td>
                    <td>{{ $res['envios']['whatsapp'] ?? 0 }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @endif

    {{-- Actividad reciente --}}
    @if ($mostrar('actividad'))
    <div class="section">
        <h2>7. Actividad reciente</h2>
        <table class="data">
            <thead><tr><th>Alumno</th><th>Referencia</th><th>Estado</th><th>Puntaje %</th><th>Fecha</th></tr></thead>
            <tbody>
                @forelse ($act['intentos'] ?? [] as $it)
                    <tr>
                        <td>{{ $it['usuario'] }}</td>
                        <td>{{ $it['referencia'] }} ({{ $it['tipo'] }})</td>
                        <td>{{ $it['estado'] }}{{ $it['aprobado'] ? ' ✓' : '' }}</td>
                        <td>{{ $it['puntaje'] ?? '—' }}</td>
                        <td>{{ $it['fecha'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Aún no hay intentos registrados</td></tr>
                @endforelse
            </tbody>
        </table>
        <table class="data">
            <thead><tr><th>Últimos alumnos registrados</th><th>Email</th><th>Estado</th><th>Fecha</th></tr></thead>
            <tbody>
                @forelse ($act['registros'] ?? [] as $u)
                    <tr>
                        <td>{{ $u['name'] }}</td>
                        <td>{{ $u['email'] }}</td>
                        <td>{{ $u['estado'] }}</td>
                        <td>{{ $u['fecha'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Sin registros recientes</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif

    <div class="foot">
        © {{ date('Y') }} Prepárate y Postula Ya · Documento generado automáticamente desde la Bitácora de Procesos
    </div>

</body>
</html>
