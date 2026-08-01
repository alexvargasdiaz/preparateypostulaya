import { useState, useEffect, useCallback } from 'react';
import { Link } from '@inertiajs/react';
import { createPortal } from 'react-dom';
import { Bell, X, CheckCircle2, AlertTriangle, XCircle, Info } from 'lucide-react';

/**
 * Toast de notificación que aparece en la esquina superior derecha.
 * Auto-dismiss después de 6 segundos con barra de progreso.
 */
export default function ToastNotificacion({ notificacion, onDismiss }) {
    const [exiting, setExiting] = useState(false);
    const [progress, setProgress] = useState(100);
    const duration = 6000; // 6 segundos

    // ─── Auto-dismiss con barra de progreso ──────────────────────
    useEffect(() => {
        if (!notificacion) return;

        const start = Date.now();
        const interval = setInterval(() => {
            const elapsed = Date.now() - start;
            const remaining = Math.max(0, 100 - (elapsed / duration) * 100);
            setProgress(remaining);

            if (elapsed >= duration) {
                clearInterval(interval);
                dismiss();
            }
        }, 16); // ~60fps

        return () => clearInterval(interval);
    }, [notificacion?.id]);

    const dismiss = useCallback(() => {
        setExiting(true);
        setTimeout(() => onDismiss?.(), 300);
    }, [onDismiss]);

    if (!notificacion) return null;

    const tipoEstilos = {
        info: { border: 'border-l-primary-500', bg: 'bg-primary-50', iconBg: 'bg-primary-100', progress: 'bg-primary-500' },
        exito: { border: 'border-l-primary-500', bg: 'bg-primary-50', iconBg: 'bg-primary-100', progress: 'bg-primary-500' },
        advertencia: { border: 'border-l-secondary-500', bg: 'bg-secondary-50', iconBg: 'bg-secondary-100', progress: 'bg-secondary-500' },
        error: { border: 'border-l-secondary-500', bg: 'bg-secondary-50', iconBg: 'bg-secondary-100', progress: 'bg-secondary-500' },
    };

    const estilos = tipoEstilos[notificacion.tipo] || tipoEstilos.info;

    const toast = (
        <div className="fixed right-4 top-4 z-[100] max-w-sm sm:right-6 sm:top-6">
            <div
                className={`relative overflow-hidden rounded-2xl border border-secondary-200 bg-white shadow-2xl ${estilos.border} border-l-4 transition-all duration-300 ${
                    exiting
                        ? 'translate-x-full opacity-0'
                        : 'translate-x-0 opacity-100 animate-slide-in-right'
                }`}
            >
                {/* Content */}
                <div className="flex items-start gap-3 p-4">
                    {/* Icon */}
                    <span className={`flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl ${estilos.iconBg} shadow-sm`}>
                        {notificacion.tipo === 'exito' ? <CheckCircle2 className="h-5 w-5 text-primary-600" /> :
                         notificacion.tipo === 'advertencia' ? <AlertTriangle className="h-5 w-5 text-secondary-600" /> :
                         notificacion.tipo === 'error' ? <XCircle className="h-5 w-5 text-secondary-600" /> :
                         <Info className="h-5 w-5 text-primary-600" />}
                    </span>

                    {/* Text */}
                    <div className="flex-1 min-w-0">
                        <div className="flex items-start justify-between gap-2">
                            <p className="text-sm font-bold text-secondary-900 leading-tight">
                                {notificacion.titulo}
                            </p>
                            <button
                                onClick={dismiss}
                                className="flex-shrink-0 rounded-lg p-0.5 text-secondary-300 transition-all hover:bg-secondary-100 hover:text-secondary-600"
                            >
                                <X className="h-4 w-4" />
                            </button>
                        </div>
                        {notificacion.mensaje && (
                            <p className="mt-0.5 text-xs text-secondary-500 line-clamp-2 leading-relaxed">
                                {notificacion.mensaje}
                            </p>
                        )}

                        {/* Acción */}
                        {notificacion.data?.url && (
                            <Link
                                href={notificacion.data.url}
                                onClick={dismiss}
                                className="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-primary-600 transition-all hover:text-primary-800"
                            >
                                Ver detalles →
                            </Link>
                        )}
                    </div>
                </div>

                {/* Progress bar */}
                <div className="h-1 w-full bg-secondary-100">
                    <div
                        className={`h-full ${estilos.progress} transition-all duration-100 ease-linear rounded-r-full`}
                        style={{ width: `${progress}%` }}
                    />
                </div>
            </div>
        </div>
    );

    return createPortal(toast, document.body);
}
