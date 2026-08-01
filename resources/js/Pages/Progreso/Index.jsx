import { Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import {
    BarChart3, Target, Trophy, Flame, TrendingUp,
    ArrowRight, CheckCircle2, Clock, BookOpen
} from 'lucide-react';

function LineChart({ data, height = 200 }) {
    if (!data || data.length < 2) return null;
    const values = data.map((d) => d.puntaje || 0);
    const max = Math.max(...values, 100);
    const min = Math.min(...values, 0);
    const range = max - min || 1;
    const width = Math.max(data.length * 50, 300);
    const padding = { top: 20, right: 20, bottom: 30, left: 40 };
    const chartW = width - padding.left - padding.right;
    const chartH = height - padding.top - padding.bottom;
    const xScale = (i) => padding.left + (i / (data.length - 1)) * chartW;
    const yScale = (v) => padding.top + chartH - ((v - min) / range) * chartH;
    const points = data.map((d, i) => `${xScale(i)},${yScale(d.puntaje)}`).join(' ');
    const gridLines = [0, 25, 50, 75, 100].filter((v) => v >= min && v <= max);
    return (
        <svg width="100%" height={height} viewBox={`0 0 ${width} ${height}`} className="overflow-visible">
            {gridLines.map((v) => (
                <g key={v}>
                    <line x1={padding.left} y1={yScale(v)} x2={width - padding.right} y2={yScale(v)} stroke="rgba(0,240,255,0.2)" strokeWidth={1} strokeDasharray="4 4" />
                    <text x={padding.left - 8} y={yScale(v) + 4} textAnchor="end" fill="#5c6477" fontSize="10">{v}%</text>
                </g>
            ))}
            <line x1={padding.left} y1={yScale(60)} x2={width - padding.right} y2={yScale(60)} stroke="#00f0ff" strokeWidth={1.5} strokeDasharray="6 3" opacity={0.6} />
            <text x={width - padding.right + 4} y={yScale(60) + 4} fill="#00f0ff" fontSize="9" opacity={0.7}>Aprobado</text>
            <path d={`M${xScale(0)},${yScale(values[0])} L${points} L${xScale(data.length - 1)},${yScale(0)} L${xScale(0)},${yScale(0)} Z`} fill="url(#gradProgreso)" opacity={0.15} />
            <defs>
                <linearGradient id="gradProgreso" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stopColor="#00f0ff" stopOpacity={0.4} />
                    <stop offset="100%" stopColor="#00f0ff" stopOpacity={0} />
                </linearGradient>
            </defs>
            <polyline points={points} fill="none" stroke="#00f0ff" strokeWidth={2.5} strokeLinecap="round" strokeLinejoin="round"
                style={{ filter: 'drop-shadow(0 0 6px rgba(0,240,255,0.3))' }} />
            {data.map((d, i) => (
                <g key={i}>
                    <circle cx={xScale(i)} cy={yScale(d.puntaje)} r={4} fill={d.aprobado !== false ? '#00f0ff' : 'rgba(255,0,255,0.5)'} stroke="#0b0f17" strokeWidth={2}>
                        <title>{d.examen}: {d.puntaje}% ({d.total}/{d.maximo}) — {d.fecha}</title>
                    </circle>
                    {i % 2 === 0 && <text x={xScale(i)} y={height - 8} textAnchor="middle" fill="#5c6477" fontSize="9">{d.fecha}</text>}
                </g>
            ))}
        </svg>
    );
}

function CircularProgress({ value, size = 100, strokeWidth = 7 }) {
    const radius = (size - strokeWidth) / 2;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (Math.min(value, 100) / 100) * circumference;
    return (
        <svg width={size} height={size} className="-rotate-90">
            <circle cx={size / 2} cy={size / 2} r={radius} fill="none" stroke="rgba(0,240,255,0.15)" strokeWidth={strokeWidth} />
            <circle cx={size / 2} cy={size / 2} r={radius} fill="none"
                stroke={value >= 60 ? '#00f0ff' : value >= 40 ? '#ffcc00' : '#ff00ff'}
                strokeWidth={strokeWidth} strokeDasharray={circumference} strokeDashoffset={offset}
                strokeLinecap="round" className="transition-all duration-700 ease-out"
                style={{ filter: value >= 60 ? 'drop-shadow(0 0 8px rgba(0,240,255,0.4))' : value >= 40 ? 'drop-shadow(0 0 8px rgba(255,204,0,0.3))' : 'drop-shadow(0 0 8px rgba(255,0,255,0.3))' }} />
        </svg>
    );
}

const containerVariants = { hidden: {}, visible: { transition: { staggerChildren: 0.08 } } };
const itemVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: { opacity: 1, y: 0, transition: { duration: 0.4 } },
};

export default function Progreso({ stats, evolucion, rendimiento_conceptos, recientes, tiene_datos }) {
    const statCards = [
        { label: 'Simulacros rendidos', value: stats.total_examenes, icon: BookOpen, color: 'neon-cyan' },
        { label: 'Tasa de aprobación', value: `${stats.tasa_aprobacion}%`, icon: Target, color: 'neon-magenta' },
        { label: 'Mejor puntaje', value: `${stats.mejor_puntaje}%`, icon: Trophy, color: 'neon-green' },
        { label: 'Precisión global', value: `${stats.precision_global}%`, icon: BarChart3, color: 'neon-cyan' },
        { label: 'Promedio general', value: `${stats.promedio_general}%`, icon: TrendingUp, color: 'neon-magenta' },
        { label: 'Mejor racha', value: stats.mejor_racha, icon: Flame, color: 'neon-yellow' },
    ];

    return (
        <>
            <Head title="Mi Progreso" />
            <div className="min-h-screen bg-cyber-dark">
                {/* Header */}
                <div className="relative overflow-hidden border-b border-cyber-dark-400/50 bg-cyber-dark-100 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.08),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10 py-8">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div className="cyber-badge cyber-badge-cyan rounded-lg px-4 py-1.5 mb-4 inline-flex">
                                    <BarChart3 className="h-4 w-4" />
                                    Tu progreso
                                </div>
                                <h1 className="text-3xl font-heading font-black text-text-primary sm:text-4xl lg:text-5xl">
                                    Evolución de{' '}
                                    <span className="neon-text-cyan">tu aprendizaje</span>
                                </h1>
                                <p className="mt-2 max-w-2xl text-sm text-text-secondary font-semibold sm:text-base">
                                    {tiene_datos
                                        ? `Has rendido ${stats.total_examenes} simulacros con ${stats.total_aprobados} aprobados. ¡Sigue así!`
                                        : 'Aún no has rendido ningún simulacro. Comienza ahora para ver tu progreso.'}
                                </p>
                            </div>
                            <div className="flex items-center gap-3">
                                <Link href="/dashboard" className="cyber-btn rounded-lg px-4 py-2.5 text-sm">Mi panel</Link>
                                <Link href="/examenes" className="cyber-btn cyber-btn-primary rounded-lg px-4 py-2.5 text-sm">
                                    Nuevo simulacro <ArrowRight className="h-4 w-4" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-6 pb-12">
                    {!tiene_datos ? (
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }}
                            className="cyber-card rounded-xl border-cyber-dark-400/40 p-12 text-center">
                            <BarChart3 className="mx-auto h-16 w-16 text-text-muted" />
                            <h3 className="mt-4 text-lg font-heading font-black text-text-primary">Aún no hay datos de progreso</h3>
                            <p className="mt-2 text-sm text-text-muted font-semibold">Rinde tu primer simulacro para empezar a ver tu evolución y estadísticas.</p>
                            <Link href="/examenes" className="mt-6 cyber-btn cyber-btn-primary rounded-xl px-6 py-3 text-sm inline-flex">
                                Explorar simulacros <ArrowRight className="h-4 w-4" />
                            </Link>
                        </motion.div>
                    ) : (
                        <>
                            <motion.div variants={containerVariants} initial="hidden" animate="visible" className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                                {statCards.map((stat) => (
                                    <motion.div key={stat.label} variants={itemVariants} whileHover={{ y: -4 }}
                                        className="cyber-card rounded-xl border-cyber-dark-400/40 p-4 hover:border-neon-cyan/20 transition-all">
                                        <div className={`inline-flex h-10 w-10 items-center justify-center rounded-xl border ${
                                            stat.color === 'neon-cyan' ? 'bg-neon-cyan/10 border-neon-cyan/30 text-neon-cyan' :
                                            stat.color === 'neon-magenta' ? 'bg-neon-magenta/10 border-neon-magenta/30 text-neon-magenta' :
                                            stat.color === 'neon-green' ? 'bg-neon-green/10 border-neon-green/30 text-neon-green' :
                                            'bg-neon-yellow/10 border-neon-yellow/30 text-neon-yellow'
                                        }`}>
                                            <stat.icon className="h-5 w-5" />
                                        </div>
                                        <p className="mt-3 text-xl font-heading font-black text-text-primary">{stat.value}</p>
                                        <p className="mt-0.5 text-xs font-semibold text-text-muted">{stat.label}</p>
                                    </motion.div>
                                ))}
                            </motion.div>

                            {evolucion.length >= 2 && (
                                <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }}
                                    className="mt-4 cyber-card rounded-xl border-cyber-dark-400/40 p-5">
                                    <div className="flex items-center justify-between mb-4">
                                        <div>
                                            <h3 className="font-heading font-bold text-text-primary">Evolución de puntajes</h3>
                                            <p className="text-xs text-text-muted font-semibold mt-0.5">{evolucion.length} simulacros · {stats.promedio_general}% de promedio</p>
                                        </div>
                                        <div className="flex items-center gap-3 text-xs text-text-muted font-semibold">
                                            <span className="flex items-center gap-1"><span className="inline-block h-3 w-3 rounded-full bg-neon-cyan shadow-neon-cyan" /> Aprobado</span>
                                            <span className="flex items-center gap-1"><span className="inline-block h-3 w-3 rounded-full bg-neon-magenta shadow-neon-magenta" /> No aprobado</span>
                                        </div>
                                    </div>
                                    <div className="w-full overflow-x-auto">
                                        <div className="min-w-[300px]"><LineChart data={evolucion} height={220} /></div>
                                    </div>
                                </motion.div>
                            )}

                            <div className="mt-4 grid gap-6 lg:grid-cols-3">
                                <div className="lg:col-span-2 cyber-card rounded-xl border-cyber-dark-400/40 p-5">
                                    <div className="flex items-center justify-between mb-4">
                                        <h3 className="font-heading font-bold text-text-primary">Rendimiento por tema</h3>
                                        <div className="flex items-center gap-2 text-xs text-text-muted font-semibold">
                                            <span className="inline-block h-2.5 w-2.5 rounded bg-neon-cyan shadow-neon-cyan" /> Fuerte
                                            <span className="inline-block h-2.5 w-2.5 rounded bg-neon-yellow" /> Regular
                                            <span className="inline-block h-2.5 w-2.5 rounded bg-neon-magenta shadow-neon-magenta" /> Débil
                                        </div>
                                    </div>
                                    <div className="space-y-4">
                                        {rendimiento_conceptos.map((c) => {
                                            const color = c.porcentaje >= 60 ? 'bg-gradient-to-r from-neon-cyan to-neon-green' : c.porcentaje >= 40 ? 'bg-gradient-to-r from-neon-yellow to-neon-orange' : 'bg-gradient-to-r from-neon-magenta to-neon-purple';
                                            const glow = c.porcentaje >= 60 ? '0 0 8px rgba(0,240,255,0.3)' : c.porcentaje >= 40 ? '0 0 8px rgba(255,204,0,0.3)' : '0 0 8px rgba(255,0,255,0.3)';
                                            return (
                                                <div key={c.nombre} className="group">
                                                    <div className="flex items-center justify-between mb-1">
                                                        <p className="text-sm font-semibold text-text-secondary group-hover:text-text-primary transition-colors truncate">{c.nombre}</p>
                                                        <span className="text-xs font-bold text-text-muted ml-2 flex-shrink-0">{c.correctas}/{c.total}</span>
                                                    </div>
                                                    <div className="h-2.5 w-full rounded-full bg-cyber-dark-300 overflow-hidden border border-cyber-dark-400/30">
                                                        <div className={`h-full rounded-full ${color} transition-all duration-500 ease-out`}
                                                            style={{ width: `${c.porcentaje}%`, boxShadow: glow }} />
                                                    </div>
                                                    <p className="text-xs text-text-muted mt-0.5 text-right font-semibold">{c.porcentaje}%</p>
                                                </div>
                                            );
                                        })}
                                    </div>
                                    {rendimiento_conceptos.length === 0 && <p className="text-sm text-text-muted text-center py-8 font-semibold">No hay datos de rendimiento por tema disponibles.</p>}
                                </div>

                                <div className="space-y-6">
                                    <div className="cyber-card rounded-xl border-cyber-dark-400/40 p-5 text-center">
                                        <h3 className="font-heading font-bold text-text-primary mb-4 text-sm">Precisión global</h3>
                                        <div className="flex justify-center"><CircularProgress value={stats.precision_global} size={130} strokeWidth={8} /></div>
                                        <div className="mt-4 grid grid-cols-2 gap-3">
                                            <div className="rounded-xl bg-neon-cyan/10 border border-neon-cyan/20 p-3">
                                                <p className="text-xs text-text-muted font-semibold">Correctas</p>
                                                <p className="text-lg font-heading font-black text-neon-cyan">{stats.total_correctas}</p>
                                            </div>
                                            <div className="rounded-xl bg-cyber-dark-300 border border-cyber-dark-400/50 p-3">
                                                <p className="text-xs text-text-muted font-semibold">Totales</p>
                                                <p className="text-lg font-heading font-black text-text-primary">{stats.total_preguntas}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="cyber-card rounded-xl border-cyber-dark-400/40 p-5">
                                        <h3 className="flex items-center gap-2 font-heading font-bold text-text-primary mb-3">
                                            <Flame className="h-5 w-5 text-neon-magenta" /> Rachas
                                        </h3>
                                        {stats.mejor_racha > 0 ? (
                                            <div className="text-center">
                                                <p className="text-3xl font-heading font-black text-neon-cyan neon-text">{stats.mejor_racha}</p>
                                                <p className="text-sm text-text-muted font-semibold mt-1">mejor racha de aprobados consecutivos</p>
                                            </div>
                                        ) : (
                                            <p className="text-sm text-text-muted text-center py-4 font-semibold">Aún no tienes rachas. ¡Aprueba exámenes consecutivos!</p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            <div className="mt-4 cyber-card rounded-xl border-cyber-dark-400/40 p-5">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="font-heading font-bold text-text-primary">Últimos simulacros</h3>
                                    <Link href="/historial" className="text-xs font-heading font-bold text-neon-cyan hover:text-white transition-colors flex items-center gap-1 neon-text">
                                        Ver historial <ArrowRight className="h-4 w-4" />
                                    </Link>
                                </div>
                                <div className="space-y-2">
                                    {recientes.map((r) => (
                                        <div key={r.id} className="flex items-center justify-between rounded-xl cyber-card border-cyber-dark-400/30 p-3 hover:border-neon-cyan/20 transition-all">
                                            <div className="flex items-center gap-3 min-w-0 flex-1">
                                                {r.aprobado
                                                    ? <CheckCircle2 className="h-5 w-5 text-neon-green flex-shrink-0" />
                                                    : <Clock className="h-5 w-5 text-neon-magenta flex-shrink-0" />
                                                }
                                                <div className="min-w-0">
                                                    <p className="text-sm font-semibold text-text-primary truncate">{r.examen}</p>
                                                    <p className="text-xs text-text-muted font-semibold">{r.institucion} · {r.fecha}</p>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-3 flex-shrink-0 ml-3">
                                                <div className="text-right">
                                                    <p className="text-sm font-heading font-bold text-neon-cyan">{r.porcentaje}%</p>
                                                    <p className="text-xs text-text-muted">{r.puntaje}/{r.maximo}</p>
                                                </div>
                                                <Link href={`/resultados/${r.id}`} className="cyber-btn cyber-btn-primary rounded-lg px-3 py-1.5 text-xs">Ver</Link>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </>
    );
}
