import { Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import { ClipboardList, CheckCircle2, Clock, XCircle, FileText, ArrowRight, Timer } from 'lucide-react';

const statusConfig = {
    completado: { color: 'bg-neon-green/10 border-neon-green/30 text-neon-green', icon: CheckCircle2, label: 'Completado' },
    en_curso: { color: 'bg-neon-cyan/10 border-neon-cyan/30 text-neon-cyan', icon: Clock, label: 'En curso' },
    pendiente: { color: 'bg-cyber-dark-300 border-cyber-dark-400/50 text-text-muted', icon: Clock, label: 'Pendiente' },
    abandonado: { color: 'bg-neon-magenta/10 border-neon-magenta/30 text-neon-cyan', icon: XCircle, label: 'Abandonado' },
};

const containerVariants = {
    hidden: {},
    visible: { transition: { staggerChildren: 0.1 } },
};

const itemVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: { opacity: 1, y: 0, transition: { duration: 0.4 } },
};

export default function Historial({ intentos }) {
    return (
        <>
            <Head title="Mi Historial" />

            <div className="min-h-screen bg-cyber-dark">
                {/* Header */}
                <div className="relative overflow-hidden border-b border-cyber-dark-400/50 bg-cyber-dark-100 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.08),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10 py-8">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-heading font-black text-text-primary sm:text-3xl">
                                    Mi{' '}
                                    <span className="neon-text-cyan">historial</span>
                                </h1>
                                <p className="mt-1 text-sm text-text-secondary font-semibold">
                                    Todos tus simulacros realizados
                                </p>
                            </div>
                            <Link
                                href="/examenes"
                                className="cyber-btn cyber-btn-primary rounded-xl px-4 py-2 text-sm"
                            >
                                Nuevo simulacro
                                <ArrowRight className="h-4 w-4" />
                            </Link>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-6 pb-12">
                    {(!intentos || intentos.length === 0) ? (
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            className="cyber-card rounded-xl border-cyber-dark-400/40 p-12 text-center"
                        >
                            <div className="flex justify-center mb-6">
                                <div className="flex h-20 w-20 items-center justify-center rounded-xl bg-cyber-dark-300 border border-cyber-dark-400/50">
                                    <ClipboardList className="h-10 w-10 text-text-muted" />
                                </div>
                            </div>
                            <h3 className="text-lg font-heading font-black text-text-primary">Aún no has rendido ningún examen</h3>
                            <p className="mt-2 text-sm text-text-muted font-semibold max-w-md mx-auto">
                                Cuando completes un simulacro, aparecerá aquí con tus resultados y retroalimentación.
                            </p>
                            <Link
                                href="/examenes"
                                className="mt-6 cyber-btn cyber-btn-primary rounded-xl px-6 py-3 text-sm inline-flex"
                            >
                                Explorar simulacros
                                <ArrowRight className="h-4 w-4" />
                            </Link>
                        </motion.div>
                    ) : (
                        <motion.div
                            variants={containerVariants}
                            initial="hidden"
                            animate="visible"
                            className="space-y-4"
                        >
                            {intentos.map((intento) => {
                                const pct = intento.puntaje_maximo > 0
                                    ? Math.round((intento.puntaje_total / intento.puntaje_maximo) * 100)
                                    : 0;
                                const status = statusConfig[intento.estado] || statusConfig.pendiente;
                                const StatusIcon = status.icon;

                                return (
                                    <motion.div
                                        key={intento.id}
                                        variants={itemVariants}
                                        whileHover={{ y: -2 }}
                                        className="cyber-card rounded-xl border-cyber-dark-400/40 p-5 hover:border-neon-cyan/20 transition-all sm:p-6"
                                    >
                                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                            <div className="flex items-start gap-4">
                                                <div className={`flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl border ${
                                                    intento.aprobado
                                                        ? 'bg-neon-green/10 border-neon-green/30'
                                                        : 'bg-cyber-dark-300 border-cyber-dark-400/50'
                                                }`}>
                                                    {intento.aprobado ? (
                                                        <CheckCircle2 className="h-6 w-6 text-neon-green" />
                                                    ) : (
                                                        <FileText className="h-6 w-6 text-text-muted" />
                                                    )}
                                                </div>
                                                <div>
                                                    <h3 className="font-heading font-bold text-text-primary transition-colors">
                                                        {intento.examen?.titulo || (intento.institucion?.nombre ? `${intento.institucion.nombre} — ${intento.carrera || 'Simulacro'}` : (intento.carrera || 'Examen diagnóstico'))}
                                                    </h3>
                                                    <p className="text-sm text-text-muted font-semibold">
                                                        {intento.carrera || ''}
                                                        {intento.examen?.titulo && intento.institucion?.nombre && (
                                                            <> · {intento.institucion.nombre}</>
                                                        )}
                                                    </p>
                                                    <div className="mt-1 flex flex-wrap items-center gap-2">
                                                        <span className={`inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[10px] font-heading font-bold ${status.color}`}>
                                                            <StatusIcon className="h-3 w-3" />
                                                            {status.label}
                                                        </span>
                                                        <span className="text-xs text-text-muted font-semibold">
                                                             {new Date(intento.created_at).toLocaleDateString('es-PE', {
                                                                 year: 'numeric', month: 'long', day: 'numeric',
                                                             })}
                                                         </span>
                                                         {intento.tiempo_empleado_seg && (
                                                             <span className="inline-flex items-center gap-1 text-xs text-text-muted font-semibold">
                                                                 <Timer className="h-3 w-3" />
                                                                 {Math.floor(intento.tiempo_empleado_seg / 60)} min
                                                             </span>
                                                         )}
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-4 sm:flex-col sm:items-end">
                                                <div className="text-right">
                                                    <p className="text-lg font-heading font-black text-text-primary">
                                                        {intento.puntaje_total ?? '—'} / {intento.puntaje_maximo ?? '—'}
                                                    </p>
                                                    <p className={`text-sm font-heading font-bold ${intento.aprobado ? 'text-neon-green neon-text' : 'text-neon-cyan'}`}>
                                                        {intento.aprobado ? 'Aprobado' : 'No aprobado'} · {pct}%
                                                    </p>
                                                </div>
                                                <Link
                                                    href={`/resultados/${intento.id}`}
                                                    className="cyber-btn cyber-btn-primary rounded-lg px-4 py-2 text-xs"
                                                >
                                                    Ver resultados
                                                </Link>
                                            </div>
                                        </div>

                                        {/* Progress bar */}
                                        <div className="mt-4 h-1.5 w-full rounded-full bg-cyber-dark-300 overflow-hidden border border-cyber-dark-400/30">
                                            <div
                                                className={`h-full rounded-full transition-all duration-700 ${
                                                    pct >= 60 ? 'bg-gradient-to-r from-neon-cyan to-neon-green' : 'bg-gradient-to-r from-neon-magenta to-neon-yellow'
                                                }`}
                                                style={{ width: `${pct}%`, boxShadow: pct >= 60 ? '0 0 8px rgba(0,240,255,0.3)' : '0 0 8px rgba(255,0,255,0.3)' }}
                                            />
                                        </div>
                                    </motion.div>
                                );
                            })}
                        </motion.div>
                    )}
                </div>
            </div>
        </>
    );
}
