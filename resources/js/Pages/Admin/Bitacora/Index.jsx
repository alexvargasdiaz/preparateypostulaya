import { Head, router } from '@inertiajs/react';
import { motion } from 'motion/react';
import { useState } from 'react';
import {
    Activity, ArrowRight, BarChart3, Brain, Building2, CheckCircle2,
    ClipboardList, Clock, Download, Eraser, FileSpreadsheet, FileText, Filter,
    GraduationCap, Layers, Mail, MessageCircle, Sparkles, Target,
    TrendingUp, UserPlus, Users, XCircle
} from 'lucide-react';

const COLORS = {
    cyan: '#00f0ff',
    magenta: '#ff00ff',
    green: '#00ff88',
    yellow: '#ffcc00',
    red: '#ff4d6d',
};

const ESTADO_STYLE = {
    completado: { label: 'Completado', cls: 'border-neon-green/30 bg-neon-green/10 text-neon-green' },
    en_curso: { label: 'En curso', cls: 'border-neon-cyan/30 bg-neon-cyan/10 text-neon-cyan' },
    pendiente: { label: 'Pendiente', cls: 'border-neon-yellow/30 bg-neon-yellow/10 text-neon-yellow' },
    activo: { label: 'Aprobado', cls: 'border-neon-green/30 bg-neon-green/10 text-neon-green' },
    rechazado: { label: 'Rechazado', cls: 'border-neon-magenta/30 bg-neon-magenta/10 text-neon-magenta' },
    abandonado: { label: 'Abandonado', cls: 'border-neon-magenta/30 bg-neon-magenta/10 text-neon-magenta' },
};

const TIPO_STYLE = {
    diagnostico: { label: 'Diagnóstico', cls: 'border-neon-magenta/30 bg-neon-magenta/10 text-neon-magenta' },
    universidad: { label: 'Universidad', cls: 'border-neon-cyan/30 bg-neon-cyan/10 text-neon-cyan' },
    area: { label: 'Área', cls: 'border-neon-yellow/30 bg-neon-yellow/10 text-neon-yellow' },
};

/* Secciones exportables (coinciden con BitacoraExport::SECCIONES) */
const SECCIONES_EXPORT = [
    { value: '', label: 'Toda la bitácora' },
    { value: 'resumen', label: 'Resumen (KPIs)' },
    { value: 'embudo', label: 'Embudo de procesos' },
    { value: 'registro', label: 'Registro y aprobación' },
    { value: 'diagnostico', label: 'Diagnóstico' },
    { value: 'areas', label: 'Áreas académicas' },
    { value: 'universidades', label: 'Universidades y carreras' },
    { value: 'resultados', label: 'Resultados y envíos' },
    { value: 'actividad', label: 'Actividad reciente' },
];

/* ────────────────────────── Utilidades ────────────────────────── */

function Chip({ style, children }) {
    return (
        <span className={`inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-bold ${style?.cls || ''}`}>
            {children}
        </span>
    );
}

function Badge({ children, color = COLORS.cyan }) {
    return (
        <span className="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[11px] font-bold"
            style={{ borderColor: `${color}40`, background: `${color}12`, color }}>
            {children}
        </span>
    );
}

function StatCard({ icon: Icon, label, value, color = COLORS.cyan, sub }) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            className="rounded-xl cyber-card border-cyber-dark-400/40 p-4 transition-all duration-200 hover:border-cyber-dark-400/70 hover:-translate-y-0.5"
        >
            <div className="flex h-9 w-9 items-center justify-center rounded-lg border"
                style={{ borderColor: `${color}40`, background: `${color}14` }}>
                <Icon className="h-5 w-5" style={{ color }} />
            </div>
            <p className="mt-3 text-2xl font-heading font-black" style={{ color }}>{value ?? 0}</p>
            <p className="mt-0.5 text-[10px] font-bold uppercase tracking-wider text-text-muted">{label}</p>
            {sub && <p className="mt-1 text-[10px] font-semibold text-text-secondary">{sub}</p>}
        </motion.div>
    );
}

function Section({ step, icon: Icon, title, subtitle, color = COLORS.cyan, actions, children }) {
    return (
        <motion.section
            initial={{ opacity: 0, y: 16 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: '-40px' }}
            className="overflow-hidden rounded-2xl cyber-card border-cyber-dark-400/40"
        >
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-cyber-dark-400/30 bg-cyber-dark-200/50 px-6 py-4">
                <div className="flex items-center gap-3">
                    {step != null && (
                        <div className="flex h-9 w-9 items-center justify-center rounded-lg border font-heading font-black text-sm"
                            style={{ borderColor: `${color}45`, background: `${color}14`, color }}>
                            {step}
                        </div>
                    )}
                    <div className="flex h-9 w-9 items-center justify-center rounded-lg border"
                        style={{ borderColor: `${color}30`, background: `${color}10` }}>
                        <Icon className="h-5 w-5" style={{ color }} />
                    </div>
                    <div>
                        <h2 className="font-heading font-bold text-text-primary">{title}</h2>
                        {subtitle && <p className="text-xs font-semibold text-text-muted">{subtitle}</p>}
                    </div>
                </div>
                {actions}
            </div>
            <div className="p-6">{children}</div>
        </motion.section>
    );
}

function EmptyState({ msg = 'Sin datos todavía', icon: Icon = BarChart3 }) {
    return (
        <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-cyber-dark-400/50 py-10 text-center">
            <Icon className="h-8 w-8 text-text-muted" />
            <p className="mt-2 text-sm font-bold text-text-muted">{msg}</p>
        </div>
    );
}

/* ────────────────────────── Diagramas ────────────────────────── */

function Funnel({ stages }) {
    const max = Math.max(...stages.map((s) => s.valor), 1);
    const stageColors = [COLORS.cyan, COLORS.green, COLORS.yellow, COLORS.magenta, COLORS.cyan];

    return (
        <div className="space-y-1">
            {stages.map((s, i) => {
                const color = stageColors[i % stageColors.length];
                const pct = s.valor > 0 ? Math.max((s.valor / max) * 100, 5) : 0;
                return (
                    <div key={s.id}>
                        {i > 0 && (
                            <div className="flex items-center gap-2 py-1 pl-2">
                                <span className="inline-flex items-center gap-1 rounded-full border border-neon-cyan/20 bg-neon-cyan/5 px-2 py-0.5 text-[10px] font-bold text-neon-cyan">
                                    <ArrowRight className="h-3 w-3" /> {s.conversion}% respecto a la etapa anterior
                                </span>
                            </div>
                        )}
                        <div className="flex items-center gap-3">
                            <div className="w-44 flex-shrink-0">
                                <p className="truncate text-right text-xs font-bold text-text-primary" title={s.label}>{s.label}</p>
                            </div>
                            <div className="relative h-10 flex-1 overflow-hidden rounded-lg border border-cyber-dark-400/40 bg-cyber-dark-300/40">
                                <motion.div
                                    initial={{ width: 0 }}
                                    animate={{ width: `${pct}%` }}
                                    transition={{ duration: 0.7, delay: i * 0.1, ease: 'easeOut' }}
                                    className="absolute inset-y-0 left-0 rounded-lg"
                                    style={{
                                        background: `linear-gradient(90deg, ${color}22, ${color}88)`,
                                        boxShadow: `0 0 18px ${color}55`,
                                    }}
                                />
                                <span className="absolute inset-y-0 right-3 flex items-center text-sm font-heading font-black text-white">{s.valor}</span>
                            </div>
                            <div className="w-16 flex-shrink-0 text-left">
                                <span className="text-[10px] font-bold text-text-muted">{s.conversion_total}% del total</span>
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

function Donut({ items, size = 150, thickness = 20, centerValue, centerLabel }) {
    const total = items.reduce((a, b) => a + (b.value ?? 0), 0) || 1;
    let acc = 0;
    const segments = items.map((it) => {
        const from = (acc / total) * 360;
        acc += it.value ?? 0;
        const to = (acc / total) * 360;
        return `${it.color} ${from}deg ${to}deg`;
    });

    return (
        <div className="flex flex-wrap items-center gap-6">
            <div
                className="relative flex-shrink-0 rounded-full"
                style={{
                    width: size,
                    height: size,
                    background: `conic-gradient(${segments.join(', ')})`,
                    WebkitMask: `radial-gradient(farthest-side, transparent calc(100% - ${thickness}px), #000 calc(100% - ${thickness - 1}px))`,
                    mask: `radial-gradient(farthest-side, transparent calc(100% - ${thickness}px), #000 calc(100% - ${thickness - 1}px))`,
                }}
            >
                <div className="absolute inset-0 flex flex-col items-center justify-center">
                    <span className="text-2xl font-heading font-black text-text-primary">{centerValue ?? total}</span>
                    {centerLabel && <span className="text-[9px] font-bold uppercase tracking-widest text-text-muted">{centerLabel}</span>}
                </div>
            </div>
            <div className="min-w-0 space-y-1.5">
                {items.map((it) => (
                    <div key={it.label} className="flex items-center gap-2">
                        <span className="h-2.5 w-2.5 flex-shrink-0 rounded-sm" style={{ background: it.color, boxShadow: `0 0 8px ${it.color}66` }} />
                        <span className="text-xs text-text-secondary">{it.label}</span>
                        <span className="pl-3 text-xs font-bold text-text-primary">{it.value ?? 0}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

function BarChart({ items, height = 210, color = COLORS.cyan }) {
    if (!items.length) return <EmptyState msg="Sin datos todavía" icon={Layers} />;

    const max = Math.max(...items.map((i) => i.intentos ?? 0), 1);
    const pad = { top: 26, right: 12, bottom: 58, left: 12 };
    const W = 640;
    const H = height;
    const innerW = W - pad.left - pad.right;
    const innerH = H - pad.top - pad.bottom;
    const n = items.length;
    const barW = Math.min(46, (innerW / n) * 0.55);
    const rotate = n > 4;

    return (
        <svg viewBox={`0 0 ${W} ${H}`} className="w-full">
            {[0, 25, 50, 75, 100].map((g) => {
                const y = pad.top + innerH - (g / 100) * innerH;
                return (
                    <g key={g}>
                        <line x1={pad.left} y1={y} x2={W - pad.right} y2={y} stroke="rgba(0,240,255,0.07)" strokeWidth={1} strokeDasharray="4 4" />
                        <text x={pad.left - 6} y={y + 3} textAnchor="end" className="fill-text-muted text-[9px] font-semibold">{g}</text>
                    </g>
                );
            })}
            {items.map((it, i) => {
                const v = it.intentos ?? 0;
                const h = (v / max) * innerH;
                const x = pad.left + (i + 0.5) * (innerW / n) - barW / 2;
                const y = pad.top + innerH - h;
                const barColor = it.color ?? color;
                return (
                    <g key={it.area_id ?? i}>
                        <rect x={x} y={y} width={barW} height={Math.max(h, 2)} rx={4} fill={barColor} opacity={0.85}
                            style={{ filter: `drop-shadow(0 0 8px ${barColor}55)` }}>
                            <title>{`${it.nombre}: ${v} intentos`}</title>
                        </rect>
                        <text x={x + barW / 2} y={y - 7} textAnchor="middle" className="fill-text-primary text-[10px] font-bold">{v}</text>
                        {rotate ? (
                            <text x={x + barW / 2} y={H - 14} textAnchor="end" className="fill-text-muted text-[9px] font-semibold"
                                transform={`rotate(-28 ${x + barW / 2} ${H - 14})`}>{it.nombre}</text>
                        ) : (
                            <text x={x + barW / 2} y={H - 14} textAnchor="middle" className="fill-text-muted text-[9px] font-semibold">{it.nombre}</text>
                        )}
                    </g>
                );
            })}
        </svg>
    );
}

function HBarList({ items, color = COLORS.cyan, icon: Icon = Building2 }) {
    if (!items.length) return <EmptyState msg="Sin simulacros por universidad" icon={Icon} />;

    const max = Math.max(...items.map((i) => i.intentos), 1);

    return (
        <div className="space-y-3.5">
            {items.map((it, i) => (
                <div key={it.institucion_id ?? i}>
                    <div className="flex items-center justify-between gap-2 text-xs">
                        <span className="truncate font-bold text-text-primary" title={it.nombre}>{it.nombre}</span>
                        <span className="flex flex-shrink-0 items-center gap-3 text-text-muted">
                            <span><b className="text-text-primary">{it.intentos}</b> intentos</span>
                            <span className="text-neon-green"><b>{it.aprobados}</b> aprob.</span>
                            {it.promedio != null && <span className="text-neon-cyan"><b>{it.promedio}%</b></span>}
                        </span>
                    </div>
                    <div className="mt-1.5 h-2.5 overflow-hidden rounded-full border border-cyber-dark-400/30 bg-cyber-dark-300/50">
                        <motion.div
                            initial={{ width: 0 }}
                            animate={{ width: `${(it.intentos / max) * 100}%` }}
                            transition={{ duration: 0.6, delay: i * 0.05 }}
                            className="h-full rounded-full"
                            style={{ background: `linear-gradient(90deg, ${color}55, ${color})`, boxShadow: `0 0 10px ${color}66` }}
                        />
                    </div>
                </div>
            ))}
        </div>
    );
}

/* ────────────────────────── Tablas ────────────────────────── */

function Th({ children, className = '' }) {
    return <th className={`whitespace-nowrap px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-text-muted ${className}`}>{children}</th>;
}

function Td({ children, className = '' }) {
    return <td className={`px-4 py-2.5 text-sm text-text-secondary ${className}`}>{children}</td>;
}

/* ────────────────────────── Página ────────────────────────── */

export default function Bitacora({ bitacora, filtros = {} }) {
    const [seccionExport, setSeccionExport] = useState('');
    const [desde, setDesde] = useState(filtros.desde || '');
    const [hasta, setHasta] = useState(filtros.hasta || '');

    const aplicarFiltros = () => {
        router.get('/admin/bitacora', { desde, hasta }, {
            preserveState: true,
            preserveScroll: true,
            only: ['bitacora', 'filtros'],
        });
    };

    const limpiarFiltros = () => {
        setDesde('');
        setHasta('');
        router.get('/admin/bitacora', {}, {
            preserveState: true,
            preserveScroll: true,
            only: ['bitacora', 'filtros'],
        });
    };

    const urlExport = (formato) => {
        const params = new URLSearchParams();
        if (seccionExport) params.set('seccion', seccionExport);
        if (desde) params.set('desde', desde);
        if (hasta) params.set('hasta', hasta);
        const qs = params.toString();
        return `/admin/bitacora/exportar/${formato}${qs ? `?${qs}` : ''}`;
    };

    const hayRango = Boolean(desde || hasta);

    const k = bitacora.kpis ?? {};
    const reg = bitacora.registro ?? {};
    const diag = bitacora.diagnostico ?? {};
    const areas = bitacora.areas ?? [];
    const unis = bitacora.universidades ?? {};
    const res = bitacora.resultados ?? {};
    const act = bitacora.actividadReciente ?? {};

    const kpis = [
        { icon: Users, label: 'Alumnos registrados', value: k.alumnos, color: COLORS.cyan },
        { icon: CheckCircle2, label: 'Aprobados', value: k.alumnos_aprobados, color: COLORS.green },
        { icon: Clock, label: 'Pendientes', value: k.alumnos_pendientes, color: COLORS.yellow },
        { icon: XCircle, label: 'Rechazados', value: k.alumnos_rechazados, color: COLORS.red },
        { icon: ClipboardList, label: 'Intentos totales', value: k.intentos_total, color: COLORS.cyan },
        { icon: CheckCircle2, label: 'Intentos completados', value: k.intentos_completados, color: COLORS.green },
        { icon: Brain, label: 'Diagnósticos completados', value: k.diagnosticos_completados, color: COLORS.magenta },
        { icon: GraduationCap, label: 'Simulacros completados', value: k.simulacros_completados, color: COLORS.yellow },
    ];

    const areaColors = [COLORS.cyan, COLORS.magenta, COLORS.yellow, COLORS.green];
    const areasConColor = areas.map((a, i) => ({ ...a, color: areaColors[i % areaColors.length] }));

    const diagIntentos = diag.intentos ?? {};
    const donutDiag = [
        { label: 'Completados', value: diagIntentos.completados ?? 0, color: COLORS.green },
        { label: 'En curso', value: diagIntentos.en_curso ?? 0, color: COLORS.cyan },
        { label: 'Abandonados', value: diagIntentos.abandonados ?? 0, color: COLORS.red },
    ];

    const donutRegistro = (reg.por_estado ?? []).map((e) => ({
        label: e.label,
        value: e.total ?? 0,
        color: e.estado === 'activo' ? COLORS.green : e.estado === 'pendiente' ? COLORS.yellow : COLORS.red,
    }));

    const donutResultados = [
        { label: 'Aprobados', value: res.aprobados ?? 0, color: COLORS.green },
        { label: 'Desaprobados', value: res.desaprobados ?? 0, color: COLORS.red },
    ];

    const promedioPorConcepto = diag.config?.promedio_preguntas_por_concepto ?? null;

    return (
        <>
            <Head title="Bitácora de Procesos" />

            <div className="min-h-screen bg-cyber-dark">
                {/* Header */}
                <div className="cyber-grid relative overflow-hidden border-b border-cyber-dark-400/50 bg-cyber-dark-100">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.08),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 py-8 sm:px-8 lg:px-10">
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <div className="inline-flex items-center gap-2 rounded-full border border-neon-cyan/30 bg-neon-cyan/10 px-4 py-1.5 text-sm font-bold text-neon-cyan">
                                    <Activity className="h-4 w-4" /> Monitoreo de procesos
                                </div>
                                <h1 className="mt-3 text-2xl font-heading font-black text-text-primary">
                                    Bitácora <span className="neon-text-cyan">de Procesos</span>
                                </h1>
                                <p className="mt-1 text-sm font-semibold text-text-secondary">
                                    Diagramas en vivo de cada proceso para que el administrador detecte y gestione los datos correctos
                                </p>
                            </div>
                            <div className="flex flex-wrap items-center gap-3">
                                {/* Filtro por rango de fechas */}
                                <div className="flex flex-wrap items-center gap-2 rounded-xl border border-cyber-dark-400/40 bg-cyber-dark-200/40 px-3 py-2">
                                    <Filter className="h-4 w-4 text-neon-cyan" />
                                    <label className="flex items-center gap-1.5 text-[11px] font-bold text-text-muted">
                                        Desde
                                        <input
                                            type="date"
                                            value={desde}
                                            onChange={(e) => setDesde(e.target.value)}
                                            className="cyber-input rounded-lg px-2 py-1.5 text-xs font-bold"
                                        />
                                    </label>
                                    <label className="flex items-center gap-1.5 text-[11px] font-bold text-text-muted">
                                        Hasta
                                        <input
                                            type="date"
                                            value={hasta}
                                            onChange={(e) => setHasta(e.target.value)}
                                            className="cyber-input rounded-lg px-2 py-1.5 text-xs font-bold"
                                        />
                                    </label>
                                    <button
                                        onClick={aplicarFiltros}
                                        className="cyber-btn rounded-lg px-3 py-1.5 text-xs font-bold border-neon-cyan/40 text-neon-cyan"
                                    >
                                        Aplicar
                                    </button>
                                    {hayRango && (
                                        <button
                                            onClick={limpiarFiltros}
                                            className="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-bold text-text-muted transition-colors hover:text-neon-magenta"
                                            title="Quitar el rango de fechas"
                                        >
                                            <Eraser className="h-3.5 w-3.5" /> Limpiar
                                        </button>
                                    )}
                                </div>
                                {/* Sección a exportar */}
                                <div className="flex items-center gap-2">
                                    <select
                                        value={seccionExport}
                                        onChange={(e) => setSeccionExport(e.target.value)}
                                        className="cyber-input rounded-xl px-4 py-2.5 text-sm font-bold"
                                        title="Elige qué sección exportar"
                                    >
                                        {SECCIONES_EXPORT.map((s) => (
                                            <option key={s.value} value={s.value}>{s.label}</option>
                                        ))}
                                    </select>
                                </div>
                                <a
                                    href={urlExport('excel')}
                                    className="cyber-btn inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold border-cyber-dark-400 transition-colors hover:border-neon-green/50 hover:text-neon-green"
                                    title={seccionExport ? `Exportar ${SECCIONES_EXPORT.find((s) => s.value === seccionExport)?.label} en Excel` : 'Descargar la bitácora completa en Excel (una hoja por proceso)'}
                                >
                                    <FileSpreadsheet className="h-4 w-4 text-neon-green" /> Exportar Excel
                                </a>
                                <a
                                    href={urlExport('pdf')}
                                    className="cyber-btn inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold border-cyber-dark-400 transition-colors hover:border-neon-magenta/50 hover:text-neon-magenta"
                                    title={seccionExport ? `Exportar ${SECCIONES_EXPORT.find((s) => s.value === seccionExport)?.label} en PDF` : 'Descargar la bitácora completa en PDF'}
                                >
                                    <Download className="h-4 w-4 text-neon-magenta" /> Exportar PDF
                                </a>
                                <Badge>
                                    <Clock className="h-3.5 w-3.5" />
                                    {hayRango
                                        ? `Período: ${desde || 'inicio'} → ${hasta || 'hoy'}`
                                        : `Actualizado: ${bitacora.fechaActualizacion}`}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 pb-8 pt-6 sm:px-8 lg:px-10">
                    {/* KPIs */}
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {kpis.map((item) => (
                            <StatCard key={item.label} {...item} />
                        ))}
                    </div>

                    {/* Embudo de procesos */}
                    <div className="mt-8">
                        <Section
                            icon={Target}
                            title="Embudo de los procesos"
                            subtitle="Alumnos únicos que avanzan por cada etapa: registro → aprobación → diagnóstico → simulacro"
                            color={COLORS.cyan}
                            actions={
                                <Badge color={COLORS.green}>
                                    <TrendingUp className="h-3.5 w-3.5" /> Conversión entre etapas
                                </Badge>
                            }
                        >
                            <Funnel stages={bitacora.embudo ?? []} />
                            <p className="mt-4 text-xs font-semibold text-text-muted">
                                💡 Cada barra muestra cuántos alumnos alcanzaron esa etapa. La caída entre barras revela dónde se
                                pierden alumnos y si faltan datos (por ejemplo: muchos aprobados pero pocos diagnósticos iniciados).
                            </p>
                        </Section>
                    </div>

                    {/* Proceso 1: Registro y aprobación */}
                    <div className="mt-8">
                        <Section
                            step={1}
                            icon={UserPlus}
                            title="Registro y aprobación de alumnos"
                            subtitle="Distribución de alumnos por estado y últimos registros"
                            color={COLORS.green}
                            actions={
                                <Badge color={COLORS.green}>
                                    <CheckCircle2 className="h-3.5 w-3.5" /> Tasa de aprobación: {reg.tasa_aprobacion ?? 0}%
                                </Badge>
                            }
                        >
                            <div className="grid gap-8 lg:grid-cols-2">
                                <div className="flex items-center">
                                    <Donut
                                        items={donutRegistro}
                                        centerValue={k.alumnos ?? 0}
                                        centerLabel="Alumnos"
                                    />
                                </div>
                                <div>
                                    <p className="mb-3 text-xs font-bold uppercase tracking-wider text-text-muted">Últimos registros</p>
                                    {reg.ultimos?.length ? (
                                        <div className="space-y-2">
                                            {reg.ultimos.map((u) => (
                                                <div key={u.id}
                                                    className="flex items-center justify-between gap-3 rounded-lg border border-cyber-dark-400/30 bg-cyber-dark-300/40 px-4 py-2.5 transition-colors hover:border-neon-cyan/30">
                                                    <div className="min-w-0">
                                                        <p className="truncate text-sm font-bold text-text-primary">{u.name}</p>
                                                        <p className="truncate text-xs text-text-muted">{u.email}</p>
                                                    </div>
                                                    <div className="flex flex-shrink-0 items-center gap-3">
                                                        <Chip style={ESTADO_STYLE[u.estado]}>{ESTADO_STYLE[u.estado]?.label ?? u.estado}</Chip>
                                                        <span className="hidden text-[10px] font-semibold text-text-muted sm:block">{u.fecha}</span>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <EmptyState msg="Aún no hay alumnos registrados" icon={Users} />
                                    )}
                                </div>
                            </div>
                        </Section>
                    </div>

                    {/* Proceso 2: Diagnóstico */}
                    <div className="mt-8">
                        <Section
                            step={2}
                            icon={Brain}
                            title="Diagnóstico"
                            subtitle="Configuración del diagnóstico y uso por parte de los alumnos"
                            color={COLORS.magenta}
                            actions={
                                diag.config?.conceptos_configurados > 0
                                    ? <Badge color={COLORS.magenta}><FileText className="h-3.5 w-3.5" /> {diag.config.conceptos_configurados} de {diag.config.conceptos_totales} conceptos configurados</Badge>
                                    : <Badge color={COLORS.red}><XCircle className="h-3.5 w-3.5" /> Sin configuración</Badge>
                            }
                        >
                            <div className="grid gap-8 lg:grid-cols-2">
                                <div>
                                    <div className="mb-4 grid grid-cols-2 gap-3">
                                        <div className="rounded-xl border border-cyber-dark-400/30 bg-cyber-dark-300/50 p-4 text-center">
                                            <p className="text-xl font-heading font-black text-neon-magenta neon-text">{diag.config?.conceptos_configurados ?? 0}</p>
                                            <p className="mt-1 text-[10px] font-bold uppercase tracking-wider text-text-muted">Conceptos configurados</p>
                                        </div>
                                        <div className="rounded-xl border border-cyber-dark-400/30 bg-cyber-dark-300/50 p-4 text-center">
                                            <p className="text-xl font-heading font-black text-neon-cyan neon-text">{promedioPorConcepto ?? '—'}</p>
                                            <p className="mt-1 text-[10px] font-bold uppercase tracking-wider text-text-muted">Preguntas por concepto</p>
                                        </div>
                                        <div className="rounded-xl border border-cyber-dark-400/30 bg-cyber-dark-300/50 p-4 text-center">
                                            <p className="text-xl font-heading font-black text-neon-yellow neon-text">{diag.config?.duracion_minutos ?? 'Auto'}</p>
                                            <p className="mt-1 text-[10px] font-bold uppercase tracking-wider text-text-muted">Duración (min)</p>
                                        </div>
                                        <div className="rounded-xl border border-cyber-dark-400/30 bg-cyber-dark-300/50 p-4 text-center">
                                            <p className="text-xl font-heading font-black text-neon-green neon-text">{diagIntentos.promedio_puntaje ?? '—'}</p>
                                            <p className="mt-1 text-[10px] font-bold uppercase tracking-wider text-text-muted">Promedio % puntaje</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg border border-neon-magenta/30 bg-neon-magenta/10">
                                            <Users className="h-5 w-5 text-neon-magenta" />
                                        </div>
                                        <div>
                                            <p className="text-lg font-heading font-black text-text-primary">{diag.alumnos_con_diagnostico ?? 0}</p>
                                            <p className="text-[10px] font-bold uppercase tracking-wider text-text-muted">Alumnos que iniciaron diagnóstico</p>
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-center">
                                    <Donut
                                        items={donutDiag}
                                        centerValue={diagIntentos.iniciados ?? 0}
                                        centerLabel="Intentos"
                                    />
                                </div>
                            </div>
                        </Section>
                    </div>

                    {/* Proceso 3: Simulacros por área académica */}
                    <div className="mt-8">
                        <Section
                            step={3}
                            icon={Layers}
                            title="Simulacros por área académica"
                            subtitle="Intentos, completados, aprobados y promedio por área"
                            color={COLORS.yellow}
                        >
                            <div className="grid gap-8 lg:grid-cols-5">
                                <div className="lg:col-span-3">
                                    <p className="mb-3 text-xs font-bold uppercase tracking-wider text-text-muted">Intentos por área</p>
                                    <BarChart items={areasConColor} color={COLORS.yellow} />
                                </div>
                                <div className="lg:col-span-2">
                                    <p className="mb-3 text-xs font-bold uppercase tracking-wider text-text-muted">Detalle por área</p>
                                    <div className="overflow-hidden rounded-xl border border-cyber-dark-400/30">
                                        <table className="w-full">
                                            <thead className="bg-cyber-dark-200/60">
                                                <tr>
                                                    <Th>Área</Th>
                                                    <Th className="text-center">Comp.</Th>
                                                    <Th className="text-center">Aprob.</Th>
                                                    <Th className="text-center">Prom.</Th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {areas.length ? areas.map((a, i) => (
                                                    <tr key={a.area_id} className="border-t border-cyber-dark-400/20 transition-colors hover:bg-neon-cyan/5">
                                                        <Td className="max-w-[180px]">
                                                            <span className="block truncate font-semibold text-text-primary" title={a.nombre}>{a.nombre}</span>
                                                            {!a.activo && <span className="text-[10px] text-text-muted">inactiva</span>}
                                                        </Td>
                                                        <Td className="text-center text-neon-cyan">{a.completados}</Td>
                                                        <Td className="text-center text-neon-green">{a.aprobados}</Td>
                                                        <Td className="text-center text-text-primary">{a.promedio != null ? `${a.promedio}%` : '—'}</Td>
                                                    </tr>
                                                )) : (
                                                    <tr><Td colSpan={4}><EmptyState msg="No hay áreas académicas" /></Td></tr>
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </Section>
                    </div>

                    {/* Proceso 4: Simulacros por universidad / carrera */}
                    <div className="mt-8">
                        <Section
                            step={4}
                            icon={Building2}
                            title="Simulacros por universidad y carrera postulada"
                            subtitle="Actividad por universidad y carreras más simuladas"
                            color={COLORS.cyan}
                        >
                            <div className="grid gap-8 lg:grid-cols-2">
                                <div>
                                    <p className="mb-3 text-xs font-bold uppercase tracking-wider text-text-muted">Por universidad</p>
                                    <HBarList items={unis.por_institucion ?? []} color={COLORS.cyan} />
                                </div>
                                <div>
                                    <p className="mb-3 text-xs font-bold uppercase tracking-wider text-text-muted">Carreras más simuladas</p>
                                    <div className="overflow-hidden rounded-xl border border-cyber-dark-400/30">
                                        <table className="w-full">
                                            <thead className="bg-cyber-dark-200/60">
                                                <tr>
                                                    <Th>Carrera</Th>
                                                    <Th className="text-center">Intentos</Th>
                                                    <Th className="text-center">Completados</Th>
                                                    <Th className="text-center">Aprobados</Th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {(unis.top_carreras ?? []).length ? unis.top_carreras.map((c, i) => (
                                                    <tr key={i} className="border-t border-cyber-dark-400/20 transition-colors hover:bg-neon-cyan/5">
                                                        <Td className="max-w-[200px]">
                                                            <span className="block truncate font-semibold text-text-primary" title={c.carrera}>{c.carrera}</span>
                                                        </Td>
                                                        <Td className="text-center text-text-primary">{c.intentos}</Td>
                                                        <Td className="text-center text-neon-cyan">{c.completados}</Td>
                                                        <Td className="text-center text-neon-green">{c.aprobados}</Td>
                                                    </tr>
                                                )) : (
                                                    <tr><Td colSpan={4}><EmptyState msg="Aún no hay carreras con simulacros" icon={GraduationCap} /></Td></tr>
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </Section>
                    </div>

                    {/* Proceso 5: Resultados y envíos */}
                    <div className="mt-8">
                        <Section
                            step={5}
                            icon={BarChart3}
                            title="Resultados y envíos"
                            subtitle="Resultados obtenidos, aciertos por concepto y envíos generados"
                            color={COLORS.green}
                            actions={
                                <Badge color={COLORS.green}>
                                    <TrendingUp className="h-3.5 w-3.5" /> Promedio global: {res.promedio_global != null ? `${res.promedio_global}%` : '—'}
                                </Badge>
                            }
                        >
                            <div className="grid gap-8 lg:grid-cols-3">
                                <div className="flex items-center">
                                    <Donut
                                        items={donutResultados}
                                        centerValue={res.completados ?? 0}
                                        centerLabel="Intentos completados"
                                    />
                                </div>
                                <div className="space-y-3">
                                    <p className="text-xs font-bold uppercase tracking-wider text-text-muted">Resultados por concepto</p>
                                    <div className="grid grid-cols-3 gap-3">
                                        <div className="rounded-xl border border-cyber-dark-400/30 bg-cyber-dark-300/50 p-3 text-center">
                                            <p className="text-lg font-heading font-black text-neon-cyan">{res.resultados_concepto?.registros ?? 0}</p>
                                            <p className="text-[9px] font-bold uppercase tracking-wider text-text-muted">Registros</p>
                                        </div>
                                        <div className="rounded-xl border border-cyber-dark-400/30 bg-cyber-dark-300/50 p-3 text-center">
                                            <p className="text-lg font-heading font-black text-neon-magenta">{res.resultados_concepto?.intentos_con_resultados ?? 0}</p>
                                            <p className="text-[9px] font-bold uppercase tracking-wider text-text-muted">Intentos</p>
                                        </div>
                                        <div className="rounded-xl border border-cyber-dark-400/30 bg-cyber-dark-300/50 p-3 text-center">
                                            <p className="text-lg font-heading font-black text-neon-green">{res.resultados_concepto?.promedio_acierto ?? 0}%</p>
                                            <p className="text-[9px] font-bold uppercase tracking-wider text-text-muted">Acierto</p>
                                        </div>
                                    </div>
                                    <div className="rounded-xl border border-cyber-dark-400/30 bg-cyber-dark-300/50 p-4">
                                        <p className="mb-2 text-xs font-bold uppercase tracking-wider text-text-muted">Envíos generados</p>
                                        <div className="flex gap-4">
                                            <div className="flex items-center gap-2">
                                                <div className="flex h-9 w-9 items-center justify-center rounded-lg border border-neon-cyan/30 bg-neon-cyan/10">
                                                    <Mail className="h-4 w-4 text-neon-cyan" />
                                                </div>
                                                <div>
                                                    <p className="text-sm font-heading font-black text-text-primary">{res.envios?.emails ?? 0}</p>
                                                    <p className="text-[9px] font-bold uppercase tracking-wider text-text-muted">Emails</p>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <div className="flex h-9 w-9 items-center justify-center rounded-lg border border-neon-green/30 bg-neon-green/10">
                                                    <MessageCircle className="h-4 w-4 text-neon-green" />
                                                </div>
                                                <div>
                                                    <p className="text-sm font-heading font-black text-text-primary">{res.envios?.whatsapp ?? 0}</p>
                                                    <p className="text-[9px] font-bold uppercase tracking-wider text-text-muted">WhatsApp</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <p className="mb-3 text-xs font-bold uppercase tracking-wider text-text-muted">Tabla de conversión de aciertos</p>
                                    <div className="space-y-3">
                                        {[
                                            { label: 'Completados', value: res.completados ?? 0, color: COLORS.cyan },
                                            { label: 'Aprobados (≥60%)', value: res.aprobados ?? 0, color: COLORS.green },
                                            { label: 'Desaprobados', value: res.desaprobados ?? 0, color: COLORS.red },
                                        ].map((row, i) => {
                                            const pct = (res.completados ?? 1) > 0 ? Math.round(((row.value) / (res.completados ?? 1)) * 100) : 0;
                                            return (
                                                <div key={row.label}>
                                                    <div className="flex items-center justify-between text-xs">
                                                        <span className="font-bold text-text-secondary">{row.label}</span>
                                                        <span className="font-bold" style={{ color: row.color }}>{row.value} ({pct}%)</span>
                                                    </div>
                                                    <div className="mt-1 h-2.5 overflow-hidden rounded-full border border-cyber-dark-400/30 bg-cyber-dark-300/50">
                                                        <motion.div initial={{ width: 0 }} animate={{ width: `${pct}%` }} transition={{ duration: 0.6, delay: i * 0.08 }}
                                                            className="h-full rounded-full"
                                                            style={{ background: `linear-gradient(90deg, ${row.color}55, ${row.color})`, boxShadow: `0 0 10px ${row.color}66` }} />
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            </div>
                        </Section>
                    </div>

                    {/* Actividad reciente */}
                    <div className="mt-8">
                        <Section
                            icon={Activity}
                            title="Actividad reciente"
                            subtitle="Últimos intentos de examen y registros de alumnos (bitácora)"
                            color={COLORS.cyan}
                        >
                            <div className="grid gap-8 lg:grid-cols-2">
                                <div>
                                    <p className="mb-3 text-xs font-bold uppercase tracking-wider text-text-muted">Últimos intentos</p>
                                    <div className="overflow-hidden rounded-xl border border-cyber-dark-400/30">
                                        <table className="w-full">
                                            <thead className="bg-cyber-dark-200/60">
                                                <tr>
                                                    <Th>Alumno</Th>
                                                    <Th>Tipo</Th>
                                                    <Th>Estado</Th>
                                                    <Th className="text-center">Puntaje</Th>
                                                    <Th>Fecha</Th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {act.intentos?.length ? act.intentos.map((it) => (
                                                    <tr key={it.id} className="border-t border-cyber-dark-400/20 transition-colors hover:bg-neon-cyan/5">
                                                        <Td className="max-w-[130px]">
                                                            <span className="block truncate font-semibold text-text-primary" title={it.usuario}>{it.usuario}</span>
                                                            <span className="block max-w-[130px] truncate text-[10px] text-text-muted" title={it.referencia}>{it.referencia}</span>
                                                        </Td>
                                                        <Td><Chip style={TIPO_STYLE[it.tipo]}>{TIPO_STYLE[it.tipo]?.label ?? it.tipo}</Chip></Td>
                                                        <Td>
                                                            <Chip style={ESTADO_STYLE[it.estado]}>{ESTADO_STYLE[it.estado]?.label ?? it.estado}</Chip>
                                                            {it.aprobado && <span className="ml-1.5 text-[10px] font-bold text-neon-green">✓</span>}
                                                        </Td>
                                                        <Td className={`text-center font-bold ${it.puntaje != null && it.puntaje >= 60 ? 'text-neon-green' : 'text-text-primary'}`}>
                                                            {it.puntaje != null ? `${it.puntaje}%` : '—'}
                                                        </Td>
                                                        <Td className="whitespace-nowrap text-xs text-text-muted">{it.fecha}</Td>
                                                    </tr>
                                                )) : (
                                                    <tr><Td colSpan={5}><EmptyState msg="Aún no hay intentos registrados" icon={ClipboardList} /></Td></tr>
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div>
                                    <p className="mb-3 text-xs font-bold uppercase tracking-wider text-text-muted">Últimos registros de alumnos</p>
                                    <div className="space-y-2">
                                        {act.registros?.length ? act.registros.map((u) => (
                                            <div key={u.id}
                                                className="flex items-center justify-between gap-3 rounded-lg border border-cyber-dark-400/30 bg-cyber-dark-300/40 px-4 py-2.5 transition-colors hover:border-neon-cyan/30">
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-bold text-text-primary">{u.name}</p>
                                                    <p className="truncate text-xs text-text-muted">{u.email}</p>
                                                </div>
                                                <div className="flex flex-shrink-0 items-center gap-3">
                                                    <Chip style={ESTADO_STYLE[u.estado]}>{ESTADO_STYLE[u.estado]?.label ?? u.estado}</Chip>
                                                    <span className="text-[10px] font-semibold text-text-muted">{u.fecha}</span>
                                                </div>
                                            </div>
                                        )) : (
                                            <EmptyState msg="Sin registros recientes" icon={Users} />
                                        )}
                                    </div>
                                </div>
                            </div>
                        </Section>
                    </div>

                    {/* Nota de gestión */}
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        transition={{ delay: 0.4 }}
                        className="mt-8 flex items-start gap-3 rounded-xl border border-neon-yellow/25 bg-neon-yellow/5 px-5 py-4"
                    >
                        <Sparkles className="mt-0.5 h-5 w-5 flex-shrink-0 text-neon-yellow" />
                        <div className="text-sm">
                            <p className="font-bold text-neon-yellow">¿Cómo usar esta bitácora para gestionar datos correctos?</p>
                            <ul className="mt-1.5 space-y-1 text-xs font-semibold text-text-secondary">
                                <li>• Si el <b>embudo</b> muestra caídas bruscas, revisa si la configuración del diagnóstico o los exámenes tienen datos incompletos.</li>
                                <li>• Si una <b>área o universidad</b> no aparece con intentos, verifica que tenga preguntas asignadas y exámenes activos.</li>
                                <li>• Los <b>conceptos sin configuración</b> en el diagnóstico bloquean el inicio del examen: configúralos en <b>Admin → Diagnóstico</b>.</li>
                            </ul>
                        </div>
                    </motion.div>
                </div>
                <div className="h-12" />
            </div>
        </>
    );
}
