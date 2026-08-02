import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { motion } from 'motion/react';
import {
    Bell, Settings, Mail, MessageCircle, Clock, Megaphone,
    Info, CheckCircle2, AlertTriangle, XCircle, ArrowLeft, Eye,
    Users, UserPlus, FileText, Shield
} from 'lucide-react';

function TipoBadge({ tipo }) {
    const config = {
        info: { bg: 'bg-neon-cyan/15 text-neon-cyan', icon: Info },
        exito: { bg: 'bg-neon-green/15 text-neon-green', icon: CheckCircle2 },
        advertencia: { bg: 'bg-yellow-500/15 text-yellow-400', icon: AlertTriangle },
        error: { bg: 'bg-neon-magenta/15 text-neon-cyan', icon: XCircle },
    };
    const { bg, icon: Icon } = config[tipo] || config.info;
    return (
        <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ${bg}`}>
            <Icon className="h-3 w-3" />
            {tipo}
        </span>
    );
}

function AdminNotifIcon({ icono }) {
    if (icono === '👤') return <UserPlus className="h-5 w-5 text-neon-cyan" />;
    if (icono === '✅') return <CheckCircle2 className="h-5 w-5 text-neon-green" />;
    if (icono === '🚫') return <XCircle className="h-5 w-5 text-neon-magenta" />;
    if (icono === '📋') return <FileText className="h-5 w-5 text-neon-cyan" />;
    if (icono === '⚠️') return <AlertTriangle className="h-5 w-5 text-yellow-400" />;
    return <Shield className="h-5 w-5 text-neon-cyan/50" />;
}

export default function NotificacionesPage({ notificaciones, preferencias, noLeidas, audiencia }) {
    const isAdmin = audiencia === 'admin';

    const [prefs, setPrefs] = useState({
        email_resultados: preferencias?.email_resultados ?? true,
        whatsapp_resultados: preferencias?.whatsapp_resultados ?? false,
        recordatorio_estudio: preferencias?.recordatorio_estudio ?? true,
        novedades: preferencias?.novedades ?? true,
    });
    const [saving, setSaving] = useState(false);
    const [saved, setSaved] = useState(false);

    const actualizarPreferencia = async (key, value) => {
        setPrefs((prev) => ({ ...prev, [key]: value }));
        setSaving(true);
        setSaved(false);

        try {
            const res = await fetch('/notificaciones/preferencias', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ [key]: value }),
            });
            if (res.ok) {
                setSaved(true);
                setTimeout(() => setSaved(false), 2000);
            }
        } catch (e) {
            console.error('Error al guardar preferencias:', e);
        } finally {
            setSaving(false);
        }
    };

    return (
        <>
            <Head title={isAdmin ? 'Notificaciones Admin' : 'Notificaciones'} />

            <div className="min-h-screen bg-cyber-dark">
                {/* Header */}
                <div className="relative overflow-hidden bg-gradient-to-r from-[#0a0e1a] via-[#0f1525] to-[#0a0e1a] py-8 cyber-grid">
                    <div className="absolute inset-0" style={{ backgroundImage: 'url(https://www.transparenttextures.com/patterns/cubes.png)', opacity: 0.05 }} />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div className="inline-flex items-center gap-2 rounded-full border border-neon-cyan/20 bg-neon-cyan/5 px-4 py-1.5 text-sm font-medium text-neon-cyan backdrop-blur-sm">
                                    {isAdmin ? <Shield className="h-4 w-4" /> : <Bell className="h-4 w-4" />}
                                    {noLeidas > 0 ? `${noLeidas} sin leer` : 'Todo al día'}
                                </div>
                                <h1 className="mt-3 text-2xl font-bold neon-text-cyan">
                                    {isAdmin ? 'Notificaciones del Panel' : 'Notificaciones'}
                                </h1>
                                <p className="mt-1 text-sm text-neon-cyan/50">
                                    {isAdmin
                                        ? 'Gestión de alumnos, exámenes y sistema'
                                        : 'Tus simulacros, resultados y recordatorios'}
                                </p>
                            </div>
                            <Link
                                href={isAdmin ? '/admin/alumnos' : '/dashboard'}
                                className="neubr-btn rounded-xl px-4 py-2 text-sm"
                            >
                                <ArrowLeft className="h-4 w-4" /> Volver
                            </Link>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-4 pb-8">
                    <div className="grid gap-6 lg:grid-cols-3">
                        {/* Lista de notificaciones */}
                        <div className="lg:col-span-2">
                            <div className="rounded-2xl border border-neon-cyan/15 bg-[rgba(11,15,23,0.9)] backdrop-blur-xl shadow-neon-cyan/20">
                                <div className="flex items-center justify-between border-b border-neon-cyan/10 px-5 py-4">
                                    <h2 className="text-sm font-bold neon-text-cyan">
                                        {isAdmin ? 'Actividad reciente' : 'Historial'}
                                        <span className="ml-2 text-xs font-normal text-neon-cyan/50">
                                            ({notificaciones?.total || 0} notificaciones)
                                        </span>
                                    </h2>
                                    {noLeidas > 0 && (
                                        <button
                                            onClick={async () => {
                                                await fetch('/notificaciones/leer-todas', {
                                                    method: 'POST',
                                                    headers: {
                                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                                                        'X-Requested-With': 'XMLHttpRequest',
                                                    },
                                                });
                                                router.reload();
                                            }}
                                            className="inline-flex items-center gap-1.5 text-xs font-medium text-neon-cyan transition-all hover:text-white"
                                        >
                                            <Eye className="h-3.5 w-3.5" />
                                            Marcar todas leídas
                                        </button>
                                    )}
                                </div>

                                {(!notificaciones?.data || notificaciones.data.length === 0) ? (
                                    <div className="px-5 py-16 text-center">
                                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-neon-cyan/5 border border-neon-cyan/20">
                                            {isAdmin
                                                ? <Shield className="h-8 w-8 text-neon-cyan/40" />
                                                : <Bell className="h-8 w-8 text-neon-cyan/40" />}
                                        </div>
                                        <p className="mt-4 text-sm font-medium text-neon-cyan">
                                            {isAdmin ? 'No hay notificaciones' : 'No tienes notificaciones'}
                                        </p>
                                        <p className="mt-1 text-xs text-neon-cyan/50">
                                            {isAdmin
                                                ? 'Las notificaciones de actividad del sistema aparecerán aquí.'
                                                : 'Aquí aparecerán notificaciones cuando completes simulacros o recibas novedades.'}
                                        </p>
                                        {!isAdmin && (
                                            <Link
                                                href="/examenes"
                                                className="neubr-btn neubr-btn-red rounded-xl mt-5 px-6 py-3 text-sm"
                                            >
                                                Rinde tu primer simulacro
                                            </Link>
                                        )}
                                    </div>
                                ) : (
                                    <motion.div
                                        initial={{ opacity: 0 }}
                                        animate={{ opacity: 1 }}
                                        className="divide-y divide-neon-cyan/5"
                                    >
                                        {notificaciones.data.map((notif, idx) => (
                                            <motion.div
                                                key={notif.id}
                                                initial={{ opacity: 0, x: -10 }}
                                                animate={{ opacity: 1, x: 0 }}
                                                transition={{ delay: idx * 0.04 }}
                                                className={`group px-5 py-4 transition-all hover:bg-neon-cyan/[0.03] ${
                                                    !notif.leida ? 'bg-neon-cyan/[0.04]' : ''
                                                }`}
                                            >
                                                <div className="flex items-start gap-4">
                                                    <span className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-[rgba(11,15,23,0.9)] border border-neon-cyan/15">
                                                        {isAdmin ? (
                                                            <AdminNotifIcon icono={notif.icono} />
                                                        ) : (
                                                            <>
                                                                {notif.icono === '📊' ? <CheckCircle2 className="h-5 w-5 text-neon-green" /> :
                                                                 notif.icono === '⏰' ? <Clock className="h-5 w-5 text-neon-cyan" /> :
                                                                 notif.icono === '📢' ? <Megaphone className="h-5 w-5 text-neon-magenta" /> :
                                                                 <Info className="h-5 w-5 text-neon-cyan/50" />}
                                                            </>
                                                        )}
                                                    </span>

                                                    <div className="flex-1 min-w-0">
                                                        <div className="flex items-start justify-between gap-3">
                                                            <div>
                                                                <p className={`text-sm ${
                                                                    !notif.leida ? 'font-bold text-neon-cyan' : 'font-medium text-text-muted'
                                                                }`}>
                                                                    {notif.titulo}
                                                                </p>
                                                                {notif.mensaje && (
                                                                    <p className="mt-0.5 text-sm text-text-muted">{notif.mensaje}</p>
                                                                )}
                                                            </div>
                                                            <div className="flex items-center gap-2 flex-shrink-0">
                                                                <TipoBadge tipo={notif.tipo} />
                                                                {!notif.leida && (
                                                                    <span className="h-2 w-2 rounded-full bg-neon-cyan" />
                                                                )}
                                                            </div>
                                                        </div>

                                                        <div className="mt-2 flex items-center gap-3">
                                                            <p className="text-xs text-text-muted">
                                                                {new Date(notif.created_at).toLocaleDateString('es-PE', {
                                                                    day: 'numeric',
                                                                    month: 'long',
                                                                    hour: '2-digit',
                                                                    minute: '2-digit',
                                                                })}
                                                            </p>
                                                            {notif.data?.url && (
                                                                <Link
                                                                    href={notif.data.url}
                                                                    className="text-xs font-medium text-neon-cyan transition-all hover:text-white"
                                                                >
                                                                    {isAdmin ? 'Ver detalle' : 'Ver resultados'}
                                                                </Link>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            </motion.div>
                                        ))}
                                    </motion.div>
                                )}

                                {/* Paginación */}
                                {notificaciones?.last_page > 1 && (
                                    <div className="flex items-center justify-between border-t border-neon-cyan/10 px-5 py-4">
                                        <p className="text-xs text-text-muted">
                                            Página {notificaciones.current_page} de {notificaciones.last_page}
                                        </p>
                                        <div className="flex gap-2">
                                            {notificaciones.prev_page_url && (
                                                <Link
                                                    href={notificaciones.prev_page_url}
                                                    className="neubr-btn rounded-lg px-3 py-1.5 text-xs"
                                                >
                                                    Anterior
                                                </Link>
                                            )}
                                            {notificaciones.next_page_url && (
                                                <Link
                                                    href={notificaciones.next_page_url}
                                                    className="neubr-btn rounded-lg px-3 py-1.5 text-xs"
                                                >
                                                    Siguiente
                                                </Link>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Preferencias */}
                        <div className="lg:col-span-1">
                            <div className="rounded-2xl border border-neon-cyan/15 bg-[rgba(11,15,23,0.9)] backdrop-blur-xl p-5 shadow-neon-cyan/20">
                                <div className="flex items-center gap-2 mb-4">
                                    <Settings className="h-5 w-5 text-neon-cyan" />
                                    <h2 className="text-sm font-bold neon-text-cyan">
                                        {isAdmin ? 'Preferencias del Panel' : 'Preferencias'}
                                    </h2>
                                    {saving && <span className="text-xs text-text-muted animate-pulse ml-auto">Guardando...</span>}
                                    {saved && <span className="text-xs text-neon-green ml-auto">Guardado</span>}
                                </div>
                                <p className="text-xs text-text-muted mb-5">
                                    {isAdmin
                                        ? 'Configura las alertas que deseas recibir como administrador.'
                                        : 'Elige qué tipo de notificaciones quieres recibir y por qué canal.'}
                                </p>

                                <div className="space-y-4">
                                    {isAdmin ? (
                                        <>
                                            <label className="flex items-start gap-3 cursor-pointer group">
                                                <input
                                                    type="checkbox"
                                                    checked={prefs.novedades}
                                                    onChange={(e) => actualizarPreferencia('novedades', e.target.checked)}
                                                    className="checkbox-neon mt-0.5"
                                                />
                                                <div>
                                                    <p className="text-sm font-medium text-text-primary group-hover:text-neon-cyan transition-colors flex items-center gap-1.5">
                                                        <UserPlus className="h-3.5 w-3.5 text-neon-cyan" />
                                                        Nuevos registros
                                                    </p>
                                                    <p className="text-xs text-text-muted">
                                                        Notificar cuando un nuevo alumno se registre
                                                    </p>
                                                </div>
                                            </label>

                                            <label className="flex items-start gap-3 cursor-pointer group">
                                                <input
                                                    type="checkbox"
                                                    checked={prefs.email_resultados}
                                                    onChange={(e) => actualizarPreferencia('email_resultados', e.target.checked)}
                                                    className="checkbox-neon mt-0.5"
                                                />
                                                <div>
                                                    <p className="text-sm font-medium text-text-primary group-hover:text-neon-cyan transition-colors flex items-center gap-1.5">
                                                        <Mail className="h-3.5 w-3.5 text-neon-cyan" />
                                                        Reportes por email
                                                    </p>
                                                    <p className="text-xs text-text-muted">
                                                        Recibe resúmenes de actividad por correo
                                                    </p>
                                                </div>
                                            </label>

                                            <label className="flex items-start gap-3 cursor-pointer group">
                                                <input
                                                    type="checkbox"
                                                    checked={prefs.recordatorio_estudio}
                                                    onChange={(e) => actualizarPreferencia('recordatorio_estudio', e.target.checked)}
                                                    className="checkbox-neon mt-0.5"
                                                />
                                                <div>
                                                    <p className="text-sm font-medium text-text-primary group-hover:text-neon-cyan transition-colors flex items-center gap-1.5">
                                                        <AlertTriangle className="h-3.5 w-3.5 text-yellow-400" />
                                                        Alertas del sistema
                                                    </p>
                                                    <p className="text-xs text-text-muted">
                                                        Notificaciones de errores o problemas del sistema
                                                    </p>
                                                </div>
                                            </label>
                                        </>
                                    ) : (
                                        <>
                                            <label className="flex items-start gap-3 cursor-pointer group">
                                                <input
                                                    type="checkbox"
                                                    checked={prefs.email_resultados}
                                                    onChange={(e) => actualizarPreferencia('email_resultados', e.target.checked)}
                                                    className="checkbox-neon mt-0.5"
                                                />
                                                <div>
                                                    <p className="text-sm font-medium text-text-primary group-hover:text-neon-cyan transition-colors">
                                                        Resultados por email
                                                    </p>
                                                    <p className="text-xs text-text-muted">
                                                        Recibe tus resultados de simulacros en tu correo electrónico
                                                    </p>
                                                </div>
                                            </label>

                                            <label className="flex items-start gap-3 cursor-pointer group">
                                                <input
                                                    type="checkbox"
                                                    checked={prefs.whatsapp_resultados}
                                                    onChange={(e) => actualizarPreferencia('whatsapp_resultados', e.target.checked)}
                                                    className="checkbox-neon mt-0.5"
                                                />
                                                <div>
                                                    <p className="text-sm font-medium text-text-primary group-hover:text-neon-cyan transition-colors flex items-center gap-1.5">
                                                        <MessageCircle className="h-3.5 w-3.5 text-neon-cyan" />
                                                        Resumen por WhatsApp
                                                    </p>
                                                    <p className="text-xs text-text-muted">
                                                        Recibe un resumen rápido de tus resultados vía WhatsApp
                                                    </p>
                                                </div>
                                            </label>

                                            <label className="flex items-start gap-3 cursor-pointer group">
                                                <input
                                                    type="checkbox"
                                                    checked={prefs.recordatorio_estudio}
                                                    onChange={(e) => actualizarPreferencia('recordatorio_estudio', e.target.checked)}
                                                    className="checkbox-neon mt-0.5"
                                                />
                                                <div>
                                                    <p className="text-sm font-medium text-text-primary group-hover:text-neon-cyan transition-colors flex items-center gap-1.5">
                                                        <Clock className="h-3.5 w-3.5 text-neon-cyan" />
                                                        Recordatorios de estudio
                                                    </p>
                                                    <p className="text-xs text-text-muted">
                                                        Te enviaremos recordatorios periódicos para que sigas practicando
                                                    </p>
                                                </div>
                                            </label>

                                            <label className="flex items-start gap-3 cursor-pointer group">
                                                <input
                                                    type="checkbox"
                                                    checked={prefs.novedades}
                                                    onChange={(e) => actualizarPreferencia('novedades', e.target.checked)}
                                                    className="checkbox-neon mt-0.5"
                                                />
                                                <div>
                                                    <p className="text-sm font-medium text-text-primary group-hover:text-neon-cyan transition-colors flex items-center gap-1.5">
                                                        <Megaphone className="h-3.5 w-3.5 text-neon-magenta" />
                                                        Novedades de la plataforma
                                                    </p>
                                                    <p className="text-xs text-text-muted">
                                                        Entérate de nuevos exámenes, funciones y mejoras
                                                    </p>
                                                </div>
                                            </label>
                                        </>
                                    )}
                                </div>

                                <div className="mt-6 rounded-xl border border-neon-cyan/10 bg-neon-cyan/[0.03] p-4">
                                    <p className="text-xs leading-relaxed text-text-muted">
                                        {isAdmin
                                            ? 'Las notificaciones in-app del panel siempre están activas. Desde aquí controlas los canales adicionales.'
                                            : 'Las notificaciones in-app siempre están activas. Desde aquí controlas los canales adicionales (email y WhatsApp).'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="h-12" />
            </div>
        </>
    );
}
