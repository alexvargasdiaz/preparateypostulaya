import { useCallback, useRef } from 'react';

/**
 * Hook que reproduce un sonido de notificación usando la Web Audio API.
 * No requiere archivos de audio externos — genera el sonido programáticamente.
 */
export default function useNotificationSound() {
    const audioCtxRef = useRef(null);

    const play = useCallback(() => {
        try {
            // Crear AudioContext solo cuando se necesite (requiere interacción del usuario)
            if (!audioCtxRef.current) {
                audioCtxRef.current = new (window.AudioContext || window.webkitAudioContext)();
            }

            const ctx = audioCtxRef.current;

            // Reanudar si está suspendido (política de autoplay de navegadores)
            if (ctx.state === 'suspended') {
                ctx.resume();
            }

            const now = ctx.currentTime;

            // ─── Campana (dos tonos armónicos) ────────────────────
            // Tono principal (nota ~523Hz ~ C5)
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(523.25, now);
            gain1.gain.setValueAtTime(0.3, now);
            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.6);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(now);
            osc1.stop(now + 0.6);

            // Tono secundario (nota ~659Hz ~ E5) — entra un poco después
            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(659.25, now + 0.1);
            gain2.gain.setValueAtTime(0.2, now + 0.1);
            gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.7);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(now + 0.1);
            osc2.stop(now + 0.7);

            // Armónico suave para calidez
            const osc3 = ctx.createOscillator();
            const gain3 = ctx.createGain();
            osc3.type = 'triangle';
            osc3.frequency.setValueAtTime(1046.5, now); // C6 (octava arriba)
            gain3.gain.setValueAtTime(0.08, now);
            gain3.gain.exponentialRampToValueAtTime(0.001, now + 0.4);
            osc3.connect(gain3);
            gain3.connect(ctx.destination);
            osc3.start(now);
            osc3.stop(now + 0.4);

        } catch (e) {
            // Si falla el audio (navegador no compatible), solo ignorar
            console.debug('🔔 Notificación sonora no disponible:', e.message);
        }
    }, []);

    return play;
}
