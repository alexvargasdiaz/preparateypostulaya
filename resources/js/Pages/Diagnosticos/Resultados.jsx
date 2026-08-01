import { Head, router } from '@inertiajs/react';
import { motion } from 'motion/react';
import {
    Brain, CheckCircle2, BarChart3, Target, GraduationCap,
    Building2, ArrowLeft, AlertTriangle, Trophy, Layers, BookOpen
} from 'lucide-react';

function RadarChart({ areas, size = 280 }) {
    if (!areas || areas.length === 0) return null;
    const center = size / 2;
    const radius = (size / 2) - 40;
    const n = areas.length;
    const angleStep = (2 * Math.PI) / n;
    const getPoint = (index, value) => {
        const angle = angleStep * index - Math.PI / 2;
        const r = (value / 100) * radius;
        return { x: center + r * Math.cos(angle), y: center + r * Math.sin(angle) };
    };
    const polygonPoints = areas.map((a, i) => {
        const p = getPoint(i, a.porcentaje);
        return `${p.x},${p.y}`;
    }).join(' ');

    return (
        <svg width="100%" height={size} viewBox={`0 0 ${size} ${size}`} className="mx-auto max-w-[300px]">
            {[20, 40, 60, 80, 100].map((v) => (
                <polygon key={v}
                    points={areas.map((_, i) => { const p = getPoint(i, v); return `${p.x},${p.y}`; }).join(' ')}
                    fill="none" stroke="rgba(0,240,255,0.2)" strokeWidth={1} opacity={v === 60 ? 0.8 : 0.4} />
            ))}
            {areas.map((_, i) => {
                const p = getPoint(i, 100);
                return <line key={i} x1={center} y1={center} x2={p.x} y2={p.y} stroke="rgba(0,240,255,0.2)" strokeWidth={1} />;
            })}
            <polygon points={polygonPoints} fill="rgba(0,240,255,0.15)" stroke="#00f0ff" strokeWidth={2.5}
                style={{ filter: 'drop-shadow(0 0 8px rgba(0,240,255,0.3))' }} />
            {areas.map((a, i) => {
                const p = getPoint(i, a.porcentaje);
                return <circle key={i} cx={p.x} cy={p.y} r={5} fill="#00f0ff" stroke="#0b0f17" strokeWidth={2} />;
            })}
            {areas.map((a, i) => {
                const p = getPoint(i, 115);
                const anchor = p.x < center - 10 ? 'end' : p.x > center + 10 ? 'start' : 'middle';
                return (
                    <text key={i} x={p.x} y={p.y} textAnchor={anchor} fill="#5c6477" fontSize="10" fontWeight="600">
                        {a.nombre.length > 10 ? a.nombre.substring(0, 9) + '...' : a.nombre}
                    </text>
                );
            })}
        </svg>
    );
}

export default function Resultados({ intento, puntajePorArea, puntajePorConcepto, puntajeTotalEstudiante, carrerasCompatibles, carrerasNoCompatibles }) {
    return (
        <>
            <Head title="Resultados del Diagnóstico" />

            <div className="min-h-screen bg-cyber-dark">
                {/* Header */}
                <div className="relative overflow-hidden border-b border-cyber-dark-400/50 bg-cyber-dark-100 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.08),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10 py-8">
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }}>
                            <div className="flex items-center gap-5">
                                <div className="flex h-16 w-16 items-center justify-center rounded-xl cyber-card border-neon-cyan/40 shadow-neon-cyan">
                                    <Brain className="h-8 w-8 text-neon-cyan" />
                                </div>
                                <div>
                                    <h1 className="text-2xl font-heading font-black text-text-primary">
                                        Resultados del{' '}
                                        <span className="neon-text-cyan">Diagnóstico</span>
                                    </h1>
                                    <p className="mt-1 text-sm text-text-secondary font-semibold">
                                        {new Date(intento.created_at).toLocaleDateString('es-PE', { day: 'numeric', month: 'long', year: 'numeric' })}
                                    </p>
                                </div>
                            </div>
                        </motion.div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-6 pb-12">
                    {/* Score summary */}
                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }}
                        className="cyber-card rounded-xl border-neon-cyan/20 p-5 mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-bold text-neon-cyan uppercase tracking-widest neon-text">Tu puntaje general</p>
                                <div className="flex items-baseline gap-3 mt-2">
                                    <span className="text-5xl font-heading font-black text-neon-cyan neon-text">{puntajeTotalEstudiante}%</span>
                                    <span className="text-sm font-semibold text-text-muted">({intento.puntaje_total}/{intento.puntaje_maximo} correctas)</span>
                                </div>
                            </div>
                            <div className={`flex h-16 w-16 items-center justify-center rounded-xl border ${
                                puntajeTotalEstudiante >= 60
                                    ? 'bg-neon-cyan/10 border-neon-cyan/30 shadow-neon-cyan'
                                    : 'bg-cyber-dark-300 border-cyber-dark-400/50'
                            }`}>
                                {puntajeTotalEstudiante >= 60
                                    ? <Trophy className="h-8 w-8 text-neon-cyan" />
                                    : <Target className="h-8 w-8 text-text-muted" />
                                }
                            </div>
                        </div>
                    </motion.div>

                    {/* Radar chart by area */}
                    {puntajePorArea.length > 0 && (
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.1 }}
                            className="cyber-card rounded-xl border-cyber-dark-400/40 p-5 mb-6">
                            <div className="flex items-center gap-2 mb-4">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-neon-cyan/10 border border-neon-cyan/30">
                                    <BarChart3 className="h-4 w-4 text-neon-cyan" />
                                </div>
                                <h2 className="text-sm font-heading font-bold text-text-primary">Rendimiento por área académica</h2>
                            </div>
                            <RadarChart areas={puntajePorArea} />

                            <div className="mt-6 space-y-4">
                                {puntajePorArea.map((area) => (
                                    <div key={area.area_id}>
                                        <div className="flex items-center gap-3 mb-1">
                                            <div className="flex items-center gap-1.5 min-w-0">
                                                <Layers className="h-4 w-4 flex-shrink-0 text-neon-cyan" />
                                                <span className="text-sm font-heading font-bold text-text-primary truncate">{area.nombre}</span>
                                            </div>
                                            <div className="flex-1 h-3 rounded-full bg-cyber-dark-300 overflow-hidden border border-cyber-dark-400/30">
                                                <div className={`h-full rounded-full transition-all duration-700 ${
                                                    area.porcentaje >= 60 ? 'bg-gradient-to-r from-neon-cyan to-neon-green' : 'bg-gradient-to-r from-neon-magenta to-neon-yellow'
                                                }`}
                                                    style={{ width: `${area.porcentaje}%`, boxShadow: area.porcentaje >= 60 ? '0 0 8px rgba(0,240,255,0.3)' : '0 0 8px rgba(255,0,255,0.3)' }} />
                                            </div>
                                            <span className="w-12 text-right text-sm font-heading font-bold text-neon-cyan">{area.porcentaje}%</span>
                                        </div>
                                        {area.conceptos?.length > 0 && (
                                            <div className="ml-7 flex flex-wrap gap-1.5 mb-2">
                                                {area.conceptos.map((c, i) => (
                                                    <span key={i} className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold border ${
                                                        c.porcentaje >= 60
                                                            ? 'bg-neon-cyan/10 border-neon-cyan/30 text-neon-cyan'
                                                            : 'bg-neon-magenta/10 border-neon-magenta/30 text-neon-cyan'
                                                    }`}>
                                                        <BookOpen className="h-2.5 w-2.5" />
                                                        {c.nombre} {c.porcentaje}%
                                                    </span>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </motion.div>
                    )}

                    {/* Compatible careers */}
                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }}
                        className="cyber-card rounded-xl border-neon-green/20 p-5 mb-6 shadow-neon-green/10">
                        <div className="flex items-center gap-2 mb-4">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-neon-green/10 border border-neon-green/30">
                                <CheckCircle2 className="h-4 w-4 text-neon-green" />
                            </div>
                            <h2 className="text-sm font-heading font-bold text-neon-green neon-text">
                                Carreras que alcanzas ({carrerasCompatibles.length})
                            </h2>
                        </div>
                        {carrerasCompatibles.length === 0 ? (
                            <p className="text-sm text-text-muted font-semibold">Ninguna carrera alcanza el puntaje mínimo requerido aún.</p>
                        ) : (
                            <div className="space-y-2">
                                {carrerasCompatibles.map((c) => (
                                    <div key={c.categoria_id} className="flex items-center justify-between rounded-xl cyber-card border-neon-green/15 p-4">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-neon-green/10 border border-neon-green/30">
                                                <GraduationCap className="h-5 w-5 text-neon-green" />
                                            </div>
                                            <div>
                                                <p className="text-sm font-heading font-bold text-text-primary">{c.nombre}</p>
                                                <p className="text-xs text-text-muted flex items-center gap-1">
                                                    <Building2 className="h-3 w-3" /> {c.institucion}
                                                    {c.area_carrera && (
                                                        <span className="ml-1 inline-flex items-center gap-0.5 rounded-full bg-neon-cyan/10 border border-neon-cyan/20 px-1.5 py-0.5 text-[9px] font-semibold text-neon-cyan">
                                                            <Layers className="h-2.5 w-2.5" /> {c.area_carrera}
                                                        </span>
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <span className="rounded-full bg-neon-green/10 border border-neon-green/30 px-3 py-1 text-xs font-heading font-bold text-neon-green">
                                                {c.puntaje_obtenido}% &ge; {c.puntaje_minimo}% mínimo
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </motion.div>

                    {/* Not compatible careers */}
                    {carrerasNoCompatibles.length > 0 && (
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3 }}
                            className="cyber-card rounded-xl border-neon-magenta/20 p-5 mb-6">
                            <div className="flex items-center gap-2 mb-4">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-neon-magenta/10 border border-neon-magenta/30">
                                    <AlertTriangle className="h-4 w-4 text-neon-magenta" />
                                </div>
                                <h2 className="text-sm font-heading font-bold text-neon-cyan neon-text">
                                    Carreras que aún no alcanzas ({carrerasNoCompatibles.length})
                                </h2>
                            </div>
                            <div className="space-y-3">
                                {carrerasNoCompatibles.map((c) => {
                                    const faltante = c.puntaje_minimo > 0 ? Math.max(0, c.puntaje_minimo - c.puntaje_obtenido) : 0;
                                    return (
                                        <div key={c.categoria_id} className="rounded-xl cyber-card border-cyber-dark-400/40 p-4">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-cyber-dark-300 border border-cyber-dark-400/50">
                                                        <GraduationCap className="h-5 w-5 text-text-muted" />
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-heading font-bold text-text-primary">{c.nombre}</p>
                                                        <p className="text-xs text-text-muted flex items-center gap-1">
                                                            <Building2 className="h-3 w-3" /> {c.institucion}
                                                            {c.area_carrera && (
                                                                <span className="ml-1 inline-flex items-center gap-0.5 rounded-full bg-cyber-dark-300 border border-cyber-dark-400/50 px-1.5 py-0.5 text-[9px] font-semibold text-text-muted">
                                                                    <Layers className="h-2.5 w-2.5" /> {c.area_carrera}
                                                                </span>
                                                            )}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <span className="rounded-full bg-neon-magenta/10 border border-neon-magenta/30 px-3 py-1 text-xs font-heading font-bold text-neon-cyan">
                                                        {c.puntaje_obtenido}% / {c.puntaje_minimo}% mínimo
                                                    </span>
                                                    {faltante > 0 && (
                                                        <p className="mt-1 text-[10px] font-semibold text-text-muted">
                                                            Te faltan {faltante.toFixed(1)} puntos
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                            {c.areas_faltantes && c.areas_faltantes.length > 0 && (
                                                <div className="mt-3 flex flex-wrap gap-2">
                                                    {c.areas_faltantes.map((af, i) => (
                                                        <span key={i} className="inline-flex items-center gap-1 rounded-full bg-neon-magenta/10 border border-neon-magenta/20 px-2.5 py-0.5 text-[10px] font-semibold text-neon-cyan">
                                                            {af.nombre}: obtuviste {af.obtenido}%, necesitas &ge;{af.requerido}%
                                                        </span>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </motion.div>
                    )}

                    {/* Back button */}
                    <div className="flex justify-center">
                        <button onClick={() => router.get('/diagnostico')}
                            className="cyber-btn rounded-xl px-6 py-3 text-sm">
                            <ArrowLeft className="h-4 w-4" /> Volver al diagnóstico
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
}
