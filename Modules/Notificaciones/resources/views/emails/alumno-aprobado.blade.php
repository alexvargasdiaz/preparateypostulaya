<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu cuenta ha sido aprobada</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 0; background: #f3f4f6; }
        .container { max-width: 560px; margin: 0 auto; padding: 24px; }
        .header { background: linear-gradient(135deg, #1B3A5C, #152d47); border-radius: 16px 16px 0 0; padding: 32px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 800; }
        .header p { color: #93c5fd; margin: 8px 0 0; font-size: 14px; }
        .badge { display: inline-block; background: rgba(201, 168, 76, 0.3); border: 1px solid rgba(201, 168, 76, 0.5); border-radius: 20px; padding: 4px 14px; font-size: 13px; color: #C9A84C; margin-bottom: 12px; font-weight: 600; }
        .body-card { background: #fff; border-radius: 0 0 16px 16px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .check-circle { width: 80px; height: 80px; border-radius: 50%; background: #dcfce7; border: 4px solid #22c55e; margin: 0 auto 16px; }
        .check-icon { width: 80px; height: 80px; display: block; }
        .message { text-align: center; font-size: 16px; color: #374151; line-height: 1.6; margin: 20px 0; }
        .message strong { color: #1B3A5C; }
        .highlight-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 20px; margin: 24px 0; text-align: center; }
        .highlight-box h3 { margin: 0 0 8px; color: #16a34a; font-size: 16px; }
        .highlight-box p { margin: 0; color: #374151; font-size: 14px; }
        .features { margin: 24px 0; }
        .feature-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f3f4f6; }
        .feature-icon { font-size: 20px; }
        .feature-text { font-size: 14px; color: #374151; }
        .btn { display: inline-block; background: #1B3A5C; color: #fff !important; text-decoration: none; padding: 12px 28px; border-radius: 10px; font-weight: 700; font-size: 14px; margin-top: 16px; }
        .btn:hover { background: #152d47; }
        .footer { text-align: center; padding: 24px; color: #9ca3af; font-size: 12px; }
        @media only screen and (max-width: 480px) {
            .container { padding: 12px; }
            .header { padding: 20px; }
            .body-card { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="badge">Cuenta Aprobada</div>
            <h1>¡Bienvenido a Prepárate y Postula Ya!</h1>
            <p>Tu cuenta ha sido aprobada exitosamente</p>
        </div>

        {{-- Cuerpo --}}
        <div class="body-card">
            <div class="check-circle">
                <svg class="check-icon" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="40" cy="40" r="38" fill="#dcfce7" stroke="#22c55e" stroke-width="4"/>
                    <path d="M24 40l10 10 22-22" stroke="#16a34a" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <div class="message">
                Hola <strong>{{ $alumno->name }}</strong>,<br><br>
                Tu cuenta ha sido <strong>aprobada</strong> por un administrador.
                Desde este momento ya puedes acceder a la plataforma y participar de todos los simulacros de admisión.
            </div>

            <div class="highlight-box">
                <h3>Ya puedes empezar a practicar</h3>
                <p>Tienes acceso completo a todos los simulacros disponibles.</p>
            </div>

            <div class="features">
                <div class="feature-item">
                    <span class="feature-icon">📝</span>
                    <span class="feature-text">Simulacros de admisión reales</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">📊</span>
                    <span class="feature-text">Resultados detallados por tema</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">📈</span>
                    <span class="feature-text">Seguimiento de tu progreso</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🏆</span>
                    <span class="feature-text">Historial de intentos</span>
                </div>
            </div>

            <div style="text-align:center; margin-top: 24px;">
                <a href="{{ url('/examenes') }}" class="btn">
                    Ver simulacros disponibles
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
