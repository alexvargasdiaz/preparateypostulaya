import { useState, useEffect, useRef, useCallback } from 'react';
import { Link, router } from '@inertiajs/react';
import ToastNotificacion from './ToastNotificacion';
import useNotificationSound from './useNotificationSound';
import { Bell, Check, Info, CheckCircle2, AlertTriangle, XCircle } from 'lucide-react';

export default function NotificationBell() {
    const [open, setOpen] = useState(false);
    const [notificaciones, setNotificaciones] = useState([]);
    const [noLeidas, setNoLeidas] = useState(0);
    const [loading, setLoading] = useState(false);
    const [toastNotif, setToastNotif] = useState(null);
    const dropdownRef = useRef(null);
    const prevIdsRef = useRef(new Set());
    const playSound = useNotificationSound();

    // ─── Cargar notificaciones ────────────────────────────────────
    const cargar = useCallback(async () => {
        try {
            const res = await fetch('/api/notificaciones/recientes', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            const nuevas = data.notificaciones || [];
            const nuevoNoLeidas = data.noLeidas || 0;

            // Detectar nuevas notificaciones no leídas
            const idsActuales = new Set(nuevas.map((n) => n.id));
            const nuevasNoLeidas = nuevas.filter(
                (n) => !n.leida && !prevIdsRef.current.has(n.id)
            );

            // Actualizar ref de IDs conocidos
            prevIdsRef.current = idsActuales;

            setNotificaciones(nuevas);
            setNoLeidas(nuevoNoLeidas);

            // Mostrar toast y sonido para la primera notificación nueva
            if (nuevasNoLeidas.length > 0) {
                playSound();
                // Mostrar toast para la más reciente
                setToastNotif(nuevasNoLeidas[0]);
            }
        } catch (e) {
            console.error('Error al cargar notificaciones:', e);
        }
    }, [playSound]);

    // ─── Cargar al montar (sin sonido inicial) ────────────────────
    useEffect(() => {
        // Primera carga: solo registrar IDs, sin sonido
        const inicializar = async () => {
            try {
                const res = await fetch('/api/notificaciones/recientes', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                const iniciales = data.notificaciones || [];
                setNotificaciones(iniciales);
                setNoLeidas(data.noLeidas || 0);
                prevIdsRef.current = new Set(iniciales.map((n) => n.id));
            } catch (e) {
                console.error('Error al cargar notificaciones:', e);
            }
        };

        inicializar();

        // Luego, polling cada 30 segundos
        const interval = setInterval(cargar, 30000);
        return () => clearInterval(interval);
    }, [cargar]);

    // ─── Cerrar al hacer clic fuera ────────────────────────────────
    useEffect(() => {
        const handleClickOutside = (e) => {
            if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // ─── Marcar una como leída ─────────────────────────────────────
    const marcarLeida = async (id, e) => {
        e.stopPropagation();
        try {
            await fetch(`/notificaciones/${id}/leer`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            setNoLeidas((prev) => Math.max(0, prev - 1));
            setNotificaciones((prev) =>
                prev.map((n) => (n.id === id ? { ...n, leida: true } : n))
            );
        } catch (e) {
            console.error('Error al marcar como leída:', e);
        }
    };

    // ─── Marcar todas como leídas ──────────────────────────────────
    const marcarTodasLeidas = async () => {
        try {
            await fetch('/notificaciones/leer-todas', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            setNoLeidas(0);
            setNotificaciones((prev) => prev.map((n) => ({ ...n, leida: true })));
        } catch (e) {
            console.error('Error al marcar todas:', e);
        }
    };

    // ─── Abrir/cerrar dropdown ─────────────────────────────────────
    const toggle = () => {
        if (!open) {
            cargar();
        }
        setOpen(!open);
    };

    const tiposColor = {
        info: 'bg-primary-100 text-primary-700',
        exito: 'bg-primary-100 text-primary-700',
        advertencia: 'bg-secondary-100 text-secondary-700',
        error: 'bg-secondary-100 text-secondary-700',
    };

    // ─── Cerrar toast ─────────────────────────────────────────────
    const dismissToast = useCallback(() => {
        setToastNotif(null);
    }, []);

    return (
        <>
            {/* Toast de notificación entrante */}
            <ToastNotificacion notificacion={toastNotif} onDismiss={dismissToast} />

            <div className="relative" ref={dropdownRef}>
                {/* Bell button */}
                <button
                    onClick={toggle}
                    className="relative rounded-lg p-2 text-secondary-500 transition-all hover:bg-secondary-100 hover:text-secondary-700"
                    title="Notificaciones"
                >
                    <Bell className="h-5 w-5" />

                    {noLeidas > 0 && (
                        <span className="absolute -right-0.5 -top-0.5 flex h-4.5 min-w-[18px] items-center justify-center rounded-full bg-secondary-500 px-1 text-[10px] font-bold text-white shadow-sm ring-2 ring-white">
                            {noLeidas > 99 ? '99+' : noLeidas}
                        </span>
                    )}

                    {/* Animación sutil cuando hay no leídas */}
                    {noLeidas > 0 && (
                        <span className="absolute inset-0 animate-ping rounded-lg bg-secondary-400/20" />
                    )}
                </button>

            {/* Dropdown */}
            {open && (
                <div className="absolute right-0 top-full z-50 mt-2 w-80 origin-top-right rounded-2xl border border-secondary-200 bg-white shadow-lg ring-1 ring-black/5">
                    {/* Header */}
                    <div className="flex items-center justify-between border-b border-secondary-100 px-4 py-3">
                        <h3 className="text-sm font-bold text-secondary-900">Notificaciones</h3>
                        {noLeidas > 0 && (
                            <button
                                onClick={marcarTodasLeidas}
                                className="text-xs font-medium text-primary-600 transition-all hover:text-primary-800"
                            >
                                Marcar todas leídas
                            </button>
                        )}
                    </div>

                    {/* List */}
                    <div className="max-h-80 overflow-y-auto">
                        {loading ? (
                            <div className="flex items-center justify-center py-8">
                                <div className="h-6 w-6 animate-spin rounded-full border-2 border-primary-600 border-t-transparent" />
                            </div>
                        ) : notificaciones.length === 0 ? (
                            <div className="px-4 py-8 text-center">
                                <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-secondary-100">
                                    <Bell className="h-6 w-6 text-secondary-300" />
                                </div>
                                <p className="mt-3 text-sm text-secondary-500">No tienes notificaciones</p>
                            </div>
                        ) : (
                            <div className="divide-y divide-secondary-50">
                                {notificaciones.map((notif) => (
                                    <div
                                        key={notif.id}
                                        className={`group relative px-4 py-3 transition-all hover:bg-secondary-50 ${
                                            !notif.leida ? 'bg-primary-50/40' : ''
                                        }`}
                                    >
                                        <div className="flex items-start gap-3">
                                            {/* Icon */}
                                            <span className="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-white shadow-sm">
                                                 {notif.tipo === 'exito' ? <CheckCircle2 className="h-4 w-4 text-primary-500" /> :
                                                 notif.tipo === 'advertencia' ? <AlertTriangle className="h-4 w-4 text-secondary-500" /> :
                                                 notif.tipo === 'error' ? <XCircle className="h-4 w-4 text-secondary-500" /> :
                                                 <Info className="h-4 w-4 text-primary-500" />}
                                            </span>

                                            {/* Content */}
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-start justify-between gap-2">
                                                    <p className={`text-sm leading-tight ${
                                                        !notif.leida ? 'font-semibold text-secondary-900' : 'font-medium text-secondary-700'
                                                    }`}>
                                                        {notif.titulo}
                                                    </p>
                                                    {!notif.leida && (
                                                        <button
                                                            onClick={(e) => marcarLeida(notif.id, e)}
                                                            className="mt-0.5 flex-shrink-0 rounded p-0.5 text-secondary-300 opacity-0 transition-all hover:text-primary-600 group-hover:opacity-100"
                                                            title="Marcar como leída"
                                                        >
                                                            <Check className="h-3.5 w-3.5" />
                                                        </button>
                                                    )}
                                                </div>
                                                {notif.mensaje && (
                                                    <p className="mt-0.5 text-xs text-secondary-500 line-clamp-2">
                                                        {notif.mensaje}
                                                    </p>
                                                )}
                                                <p className="mt-1 text-[10px] text-secondary-400">
                                                    {formatFecha(notif.created_at)}
                                                </p>
                                            </div>
                                        </div>

                                        {/* Link si existe */}
                                        {notif.data?.url && !notif.leida && (
                                            <Link
                                                href={notif.data.url}
                                                onClick={() => marcarLeida(notif.id, event)}
                                                className="mt-2 inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-800"
                                            >
                                                Ver detalles →
                                            </Link>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Footer */}
                    <div className="border-t border-secondary-100 px-4 py-2.5">
                        <Link
                            href="/notificaciones"
                            onClick={() => setOpen(false)}
                            className="block w-full rounded-lg bg-secondary-50 py-2 text-center text-xs font-semibold text-secondary-700 transition-all hover:bg-secondary-100"
                        >
                            Ver todas las notificaciones
                        </Link>
                    </div>
                </div>
            )}
        </div>
        </>
    );
}

function formatFecha(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffMin = Math.floor(diffMs / 60000);
    const diffHour = Math.floor(diffMs / 3600000);
    const diffDay = Math.floor(diffMs / 86400000);

    if (diffMin < 1) return 'Ahora';
    if (diffMin < 60) return `Hace ${diffMin} min`;
    if (diffHour < 24) return `Hace ${diffHour}h`;
    if (diffDay < 7) return `Hace ${diffDay}d`;
    return date.toLocaleDateString('es-PE', { day: 'numeric', month: 'short' });
}
