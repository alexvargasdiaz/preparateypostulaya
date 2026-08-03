const tokenCSRF = () => document.querySelector('meta[name=csrf-token]')?.content || '';

const TIEMPO_MAXIMO_MS = 20000;

async function enviarLote(url, lote) {
    const controlador = new AbortController();
    const timeout = setTimeout(() => controlador.abort(), TIEMPO_MAXIMO_MS);
    try {
        const respuesta = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': tokenCSRF(),
            },
            body: JSON.stringify({ respuestas: lote }),
            signal: controlador.signal,
        });
        if (!respuesta.ok) {
            throw new Error(`HTTP ${respuesta.status}`);
        }
        return await respuesta.json();
    } finally {
        clearTimeout(timeout);
    }
}

/**
 * Envía las respuestas en lotes para no disparar una petición por pregunta.
 *
 * Con un diagnóstico de 200+ preguntas, el patrón anterior abría una
 * petición HTTP por respuesta y las esperaba todas antes de finalizar,
 * dejando la pantalla de resultados congelada durante minutos. Aquí se
 * agrupan en lotes (un request por lote), cada request con timeout para que
 * nunca quede colgado, y se reporta el progreso real en onProgreso(listo, total).
 */
export async function guardarRespuestasEnLotes({ url, respuestas, tamanoLote = 25, onProgreso }) {
    const total = respuestas.length;
    onProgreso?.(0, total);

    for (let i = 0; i < total; i += tamanoLote) {
        const lote = respuestas.slice(i, i + tamanoLote);
        await enviarLote(url, lote);
        onProgreso?.(Math.min(i + tamanoLote, total), total);
    }
}

/**
 * Función para reusar en el auto-save (cada 30s). Trocea en lotes para no
 * superar el límite por request del backend (max:200) en diagnósticos grandes.
 */
export async function guardarRespuestasBatch(url, respuestas) {
    if (respuestas.length === 0) return;
    await guardarRespuestasEnLotes({ url, respuestas });
}
