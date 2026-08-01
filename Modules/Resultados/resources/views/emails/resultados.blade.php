<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tus resultados del simulacro</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; background: #f3f4f6; }
        .container { max-width: 560px; margin: 0 auto; padding: 24px; }
        .header { background: linear-gradient(135deg, #1e40af, #1e3a8a); border-radius: 16px 16px 0 0; padding: 32px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 800; }
        .header p { color: #93c5fd; margin: 8px 0 0; font-size: 14px; }
        .badge { display: inline-block; background: rgba(255,255,255,0.2); border-radius: 20px; padding: 4px 14px; font-size: 13px; color: #fff; margin-bottom: 12px; font-weight: 600; }
        .body-card { background: #fff; border-radius: 0 0 16px 16px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .score-circle { width: 120px; height: 120px; border-radius: 50%; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; flex-direction: column; }
        .score-circle.passed { background: #dcfce7; border: 4px solid #22c55e; }
        .score-circle.failed { background: #fef3c7; border: 4px solid #f59e0b; }
        .score-number { font-size: 32px; font-weight: 800; color: #111827; line-height: 1; }
        .score-label { font-size: 12px; color: #6b7280; }
        .status { text-align: center; font-size: 16px; font-weight: 700; margin-bottom: 20px; }
        .status.passed { color: #16a34a; }
        .status.failed { color: #d97706; }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin: 20px 0; }
        .stat-box { background: #f9fafb; border-radius: 10px; padding: 12px; text-align: center; }
        .stat-label { font-size: 11px; color: #9ca3af; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
        .stat-value { font-size: 16px; font-weight: 700; color: #111827; margin-top: 4px; }
        .section-title { font-size: 15px; font-weight: 700; color: #111827; margin: 24px 0 12px; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; }
        .concept-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f3f4f6; }
        .concept-name { font-size: 13px; color: #374151; font-weight: 500; }
        .concept-pct { font-size: 13px; font-weight: 700; padding: 2px 10px; border-radius: 10px; }
        .concept-pct.good { background: #dcfce7; color: #16a34a; }
        .concept-pct.warning { background: #fef3c7; color: #d97706; }
        .concept-pct.bad { background: #fee2e2; color: #dc2626; }
        .help-message { background: #fefce8; border-left: 3px solid #f59e0b; padding: 10px 14px; border-radius: 8px; font-size: 12px; color: #92400e; margin-top: 6px; line-height: 1.5; }
        .footer { text-align: center; padding: 24px; color: #9ca3af; font-size: 12px; }
        .btn { display: inline-block; background: #2563eb; color: #fff !important; text-decoration: none; padding: 10px 24px; border-radius: 10px; font-weight: 700; font-size: 14px; margin-top: 16px; }
        .btn:hover { background: #1d4ed8; }
        .divider { height: 1px; background: #e5e7eb; margin: 20px 0; }
        @media only screen and (max-width: 480px) {
            .container { padding: 12px; }
            .header { padding: 20px; }
            .body-card { padding: 20px; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="badge">
                {{ $intento->aprobado ? '✅ Aprobado' : '📝 Completado' }}
            </div>
            <h1>{{ $intento->examen?->titulo ?? 'Simulacro de Admisión' }}</h1>
            <p>{{ $intento->examen?->categoria?->institucion?->nombre ?? 'Prepárate y Postula Ya' }}</p>
        </div>

        {{-- Cuerpo --}}
        <div class="body-card">
            {{-- Puntaje --}}
            @php
                $pct = $intento->puntaje_maximo > 0 ? round(($intento->puntaje_total / $intento->puntaje_maximo) * 100) : 0;
            @endphp
            <div class="score-circle {{ $intento->aprobado ? 'passed' : 'failed' }}">
                <span class="score-number">{{ $intento->puntaje_total }}</span>
                <span class="score-label">de {{ $intento->puntaje_maximo }}</span>
            </div>
            <div class="status {{ $intento->aprobado ? 'passed' : 'failed' }}">
                {{ $pct }}% de aciertos — {{ $intento->aprobado ? '¡Aprobado!' : 'Sigue practicando' }}
            </div>

            {{-- Stats --}}
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-label">Tiempo usado</div>
                    <div class="stat-value">
                        {{ $intento->tiempo_empleado_seg ? floor($intento->tiempo_empleado_seg / 60) . ' min' : '—' }}
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Preguntas</div>
                    <div class="stat-value">{{ $intento->respuestas->count() }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Fecha</div>
                    <div class="stat-value">{{ $intento->created_at?->format('d/m/Y') ?? '—' }}</div>
                </div>
            </div>

            <div class="divider"></div>

            {{-- Resultados por concepto --}}
            @if ($intento->resultadosConceptos->isNotEmpty())
                <div class="section-title">📊 Resultados por tema</div>
                @foreach ($intento->resultadosConceptos as $rc)
                    <div class="concept-item">
                        <div>
                            <div class="concept-name">{{ $rc->concepto?->nombre ?? 'Concepto' }}</div>
                            @php $ayuda = $rc->concepto?->mensajesAyuda->first(); @endphp
                            @if ($rc->porcentaje_acierto < 60 && $ayuda)
                                <div class="help-message">💡 {{ $ayuda->texto }}</div>
                            @elseif ($rc->porcentaje_acierto < 50)
                                <div class="help-message">💡 Debes reforzar este tema. Acertaste {{ $rc->preguntas_correctas }} de {{ $rc->preguntas_totales }} preguntas.</div>
                            @endif
                        </div>
                        <span class="concept-pct {{ $rc->porcentaje_acierto >= 60 ? 'good' : ($rc->porcentaje_acierto >= 40 ? 'warning' : 'bad') }}">
                            {{ $rc->porcentaje_acierto }}%
                        </span>
                    </div>
                @endforeach
            @endif

            {{-- CTA --}}
            <div style="text-align:center; margin-top: 24px;">
                <a href="{{ url('/resultados/' . $intento->id) }}" class="btn">
                    Ver resultados completos
                </a>
                <p style="font-size:12px; color:#9ca3af; margin-top:12px;">
                    Prepárate y Postula Ya — Simulacros gratuitos de admisión universitaria
                </p>
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>© {{ date('Y') }} Prepárate y Postula Ya. Todos los derechos reservados.</p>
            <p style="margin-top:4px;">Lima, Perú</p>
        </div>
    </div>
</body>
</html>
