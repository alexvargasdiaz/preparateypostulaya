import { Head, router, usePage } from '@inertiajs/react';
import { motion } from 'motion/react';
import {
    Target, Play, Clock, CheckCircle2, ArrowRight, Brain,
    Layers, Calendar, Award, AlertTriangle, BookOpen, ChevronRight, HelpCircle
} from 'lucide-react';

export default function Index({ intentos, areas, totalPreguntas, duracionMinutos }) {
    const { flash } = usePage().props;

    const iniciarDiagnostico = () => {
        router.post('/diagnostico/iniciar');
    };

    const verResultados = (intentoId) => {
        router.get(`/diagnostico/rendir/${intentoId}/resultados`);
    };

    return (
        <>
            <Head title="Examen Diagnóstico" />

            <div className="min-h-screen bg-cyber-dark">
                {/* Header */}
                <div className="relative overflow-hidden border-b border-cyber-dark-400/50 bg-cyber-dark-100 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.08),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10 py-8">
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5 }}>
                            <div className="flex items-center gap-5">
                                <div className="flex h-16 w-16 items-center justify-center rounded-xl cyber-card border-neon-magenta/40 shadow-neon-magenta">
                                    <Brain className="h-8 w-8 text-neon-magenta" />
                                </div>
                                <div>
                                    <h1 className="text-2xl font-heading font-black text-text-primary">
                                        Examen{' '}
                                        <span className="neon-text-cyan">Diagnóstico</span>
                                    </h1>
                                    <p className="mt-1 text-sm text-text-secondary font-semibold">
                                        Descubre tus fortalezas y a qué carreras puedes aspirar
                                    </p>
                                </div>
                            </div>
                        </motion.div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-6 pb-12">
                    {flash?.success && (
                        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                            className="mb-6 flex items-center gap-3 rounded-xl cyber-card border-neon-green/30 px-5 py-4 shadow-neon-green/10">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-neon-green/10 border border-neon-green/30">
                                <CheckCircle2 className="h-4 w-4 text-neon-green" />
                            </div>
                            <p className="text-sm font-bold text-neon-green">{flash.success}</p>
                        </motion.div>
                    )}

                    {flash?.error && (
                        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                            className="mb-6 flex items-center gap-3 rounded-xl cyber-card border-neon-magenta/30 px-5 py-4 shadow-neon-magenta/10">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-neon-magenta/10 border border-neon-magenta/30">
                                <AlertTriangle className="h-4 w-4 text-neon-magenta" />
                            </div>
                            <p className="text-sm font-bold text-neon-magenta">{flash.error}</p>
                        </motion.div>
                    )}

                    {/* Info card */}
                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5 }}
                        className="cyber-card rounded-xl border-cyber-dark-400/40 p-5">
                        <div className="flex items-center gap-2 mb-4">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-neon-cyan/10 border border-neon-cyan/30">
                                <Target className="h-4 w-4 text-neon-cyan" />
                            </div>
                            <h2 className="text-sm font-heading font-bold text-text-primary">¿Qué es el diagnóstico?</h2>
                        </div>
                        <p className="text-sm text-text-secondary leading-relaxed font-semibold">
                            Un examen general que evalúa tu conocimiento en <strong className="text-neon-cyan">todas las áreas</strong> de las diferentes carreras.
                            Al finalizar, sabrás exactamente <strong className="text-text-primary">qué carreras alcanzas</strong> según tu nivel actual.
                        </p>

                        {areas?.some(a => a.conceptos_count > 0) && (
                            <div className="mt-4">
                                <h3 className="text-sm font-heading font-bold text-neon-cyan mb-3 flex items-center gap-1 neon-text">
                                    <Layers className="h-4 w-4" /> Áreas y cursos evaluados
                                </h3>
                                <div className="grid gap-2 sm:grid-cols-2">
                                    {areas.map((area) => (
                                        <div key={area.id} className="rounded-xl cyber-card border-cyber-dark-400/30 p-3">
                                            <div className="flex items-center justify-between">
                                                <p className="text-sm font-heading font-bold text-text-primary">{area.nombre}</p>
                                                <span className="text-[10px] font-bold text-neon-cyan">{area.conceptos_count} cursos</span>
                                            </div>
                                            {area.conceptos?.length > 0 && (
                                                <div className="mt-2 flex flex-wrap gap-1">
                                                    {area.conceptos.slice(0, 4).map((c) => (
                                                        <span key={c.id} className="inline-flex items-center gap-1 rounded-full bg-cyber-dark-300 border border-cyber-dark-400/50 px-2 py-0.5 text-[10px] font-semibold text-text-muted">
                                                            <BookOpen className="h-2.5 w-2.5" />
                                                            {c.nombre}
                                                            <span className="text-text-muted/50">({c.preguntas_count})</span>
                                                        </span>
                                                    ))}
                                                    {area.conceptos.length > 4 && (
                                                        <span className="text-[10px] text-text-muted py-0.5">+{area.conceptos.length - 4} más</span>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </motion.div>

                    {/* Start button */}
                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5, delay: 0.1 }}
                        className="mt-4">
                        <button onClick={iniciarDiagnostico}
                            className="w-full cyber-card cyber-card-magenta rounded-xl p-6 border-neon-magenta/30 hover:shadow-neon-magenta transition-all">
                            <div className="flex items-center justify-center gap-4">
                                <div className="flex h-14 w-14 items-center justify-center rounded-xl bg-neon-magenta/15 border border-neon-magenta/30">
                                    <Play className="h-7 w-7 text-neon-magenta" />
                                </div>
                                <div className="text-left">
                                    <p className="text-xl font-heading font-black text-text-primary">Iniciar diagnóstico</p>
                                    <p className="text-sm text-text-muted font-semibold">Toma tu primer examen diagnóstico gratuito</p>
                                </div>
                                <ChevronRight className="ml-auto h-6 w-6 text-neon-magenta" />
                            </div>
                        </button>
                        {duracionMinutos > 0 && (
                            <div className="mt-3 flex flex-wrap items-center justify-center gap-3">
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-cyber-dark-300 border border-cyber-dark-400/50 px-3 py-1.5 text-xs font-bold text-text-muted">
                                    <Clock className="h-3.5 w-3.5 text-neon-magenta" /> Duración: {duracionMinutos} min
                                </span>
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-cyber-dark-300 border border-cyber-dark-400/50 px-3 py-1.5 text-xs font-bold text-text-muted">
                                    <HelpCircle className="h-3.5 w-3.5 text-neon-cyan" /> {totalPreguntas} preguntas
                                </span>
                            </div>
                        )}
                    </motion.div>

                    {/* Historial */}
                    {intentos && intentos.length > 0 && (
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5, delay: 0.2 }}
                            className="mt-6">
                            <div className="flex items-center justify-between mb-4">
                                <h2 className="text-sm font-heading font-bold text-text-primary">Mis diagnósticos anteriores</h2>
                                <span className="cyber-badge cyber-badge-cyan rounded-lg px-3 py-1 text-xs">{intentos.length} realizados</span>
                            </div>
                            <div className="space-y-3">
                                {intentos.map((intento, index) => {
                                    const pct = intento.puntaje_maximo > 0
                                        ? Math.round((intento.puntaje_total / intento.puntaje_maximo) * 100)
                                        : 0;
                                    const fecha = new Date(intento.created_at);
                                    const fechaStr = fecha.toLocaleDateString('es-PE', { day: 'numeric', month: 'long', year: 'numeric' });
                                    const horaStr = fecha.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
                                    const tiempoEmpleado = intento.tiempo_empleado_seg
                                        ? `${Math.floor(intento.tiempo_empleado_seg / 60)} min`
                                        : null;
                                    const num = intentos.length - index;

                                    return (
                                        <div key={intento.id}
                                            className="cyber-card rounded-xl border-cyber-dark-400/40 overflow-hidden hover:border-neon-cyan/20 transition-all">
                                            <div className="flex items-center justify-between p-4">
                                                <div className="flex items-center gap-4">
                                                    <div className={`flex h-12 w-12 items-center justify-center rounded-xl font-heading font-bold text-sm border ${
                                                        pct >= 60
                                                            ? 'bg-neon-cyan/10 border-neon-cyan/30 text-neon-cyan'
                                                            : 'bg-cyber-dark-300 border-cyber-dark-400/50 text-text-muted'
                                                    }`}>
                                                        #{num}
                                                    </div>
                                                    <div>
                                                        <div className="flex items-center gap-2">
                                                            <p className="text-sm font-heading font-bold text-text-primary">Diagnóstico #{num}</p>
                                                            {intento.aprobado ? (
                                                                <span className="inline-flex items-center gap-1 rounded-full bg-neon-green/10 border border-neon-green/30 px-2 py-0.5 text-[10px] font-heading font-bold text-neon-green">
                                                                    <Award className="h-3 w-3" /> Aprobado
                                                                </span>
                                                            ) : (
                                                                <span className="inline-flex items-center gap-1 rounded-full bg-neon-magenta/10 border border-neon-magenta/30 px-2 py-0.5 text-[10px] font-heading font-bold text-neon-magenta">
                                                                    <AlertTriangle className="h-3 w-3" /> No aprobado
                                                                </span>
                                                            )}
                                                        </div>
                                                        <div className="mt-1 flex items-center gap-3 text-xs text-text-muted font-semibold">
                                                            <span className="flex items-center gap-1">
                                                                <Calendar className="h-3 w-3" /> {fechaStr}
                                                            </span>
                                                            <span className="flex items-center gap-1">
                                                                <Clock className="h-3 w-3" /> {horaStr}
                                                            </span>
                                                            {tiempoEmpleado && (
                                                                <span className="text-text-muted/70">Duración: {tiempoEmpleado}</span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-4">
                                                    <div className="text-right">
                                                        <div className="flex items-baseline gap-1">
                                                            <span className={`text-2xl font-heading font-black ${
                                                                pct >= 60 ? 'text-neon-cyan neon-text' : 'text-neon-magenta'
                                                            }`}>
                                                                {pct}
                                                            </span>
                                                            <span className="text-sm font-semibold text-text-muted">%</span>
                                                        </div>
                                                        <p className="text-xs text-text-muted">{intento.puntaje_total}/{intento.puntaje_maximo} correctas</p>
                                                    </div>
                                                    <button onClick={() => verResultados(intento.id)}
                                                        className="cyber-btn cyber-btn-primary rounded-lg px-4 py-2 text-xs">
                                                        Ver resultados
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </motion.div>
                    )}
                </div>
            </div>
        </>
    );
}
