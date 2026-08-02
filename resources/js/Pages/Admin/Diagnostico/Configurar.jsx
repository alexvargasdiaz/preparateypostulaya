import { Head, router, usePage } from '@inertiajs/react';
import { motion } from 'motion/react';
import { useState, useMemo } from 'react';
import {
    CheckCircle2, Layers, BookOpen, Save, Settings2, Clock,
    Plus, Minus, Sparkles, AlertTriangle, ChevronDown, ChevronUp,
    Brain, BarChart3, Timer
} from 'lucide-react';

function NumberStepper({ value, onChange, min = 1, max = 100, step = 1, compact = false }) {
    const decrement = () => {
        const newVal = Math.max(min, (parseInt(value) || min) - step);
        onChange(newVal);
    };
    const increment = () => {
        const newVal = Math.min(max, (parseInt(value) || min) + step);
        onChange(newVal);
    };
    const handleChange = (e) => {
        const raw = e.target.value;
        if (raw === '') { onChange(min); return; }
        const num = parseInt(raw);
        if (!isNaN(num)) {
            onChange(Math.max(min, Math.min(max, num)));
        }
    };

    return (
        <div className={`inline-flex items-center gap-0 ${compact ? 'scale-90 origin-right' : ''}`}>
            <button
                type="button"
                onClick={decrement}
                disabled={parseInt(value) <= min}
                className="flex h-8 w-8 items-center justify-center rounded-l-lg border border-cyber-dark-400/50 bg-cyber-dark-300 text-text-muted hover:text-white hover:border-neon-cyan/40 transition-all disabled:opacity-30 disabled:cursor-not-allowed"
            >
                <Minus className="h-3.5 w-3.5" />
            </button>
            <input
                type="number"
                min={min}
                max={max}
                value={value}
                onChange={handleChange}
                className="h-8 w-14 border-y border-cyber-dark-400/50 bg-cyber-dark text-center text-sm font-heading font-bold text-neon-cyan outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
            />
            <button
                type="button"
                onClick={increment}
                disabled={parseInt(value) >= max}
                className="flex h-8 w-8 items-center justify-center rounded-r-lg border border-cyber-dark-400/50 bg-cyber-dark-300 text-text-muted hover:text-white hover:border-neon-cyan/40 transition-all disabled:opacity-30 disabled:cursor-not-allowed"
            >
                <Plus className="h-3.5 w-3.5" />
            </button>
        </div>
    );
}

function TimeSlider({ value, onChange, totalPreguntas }) {
    const autoMode = value === null || value === undefined || value === 0;
    const autoMinutes = Math.max(5, Math.ceil(totalPreguntas / 2)); // ~30 seg por pregunta en automático

    const displayValue = autoMode ? autoMinutes : value;

    return (
        <div className="cyber-card rounded-xl border-neon-cyan/20 p-5">
            <div className="flex items-center justify-between mb-4">
                <div className="flex items-center gap-2">
                    <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-neon-magenta/10 border border-neon-magenta/30">
                        <Timer className="h-5 w-5 text-neon-magenta" />
                    </div>
                    <div>
                        <h3 className="text-sm font-heading font-bold text-text-primary">Duración del diagnóstico</h3>
                        <p className="text-[10px] text-text-muted font-semibold">Tiempo límite para completar</p>
                    </div>
                </div>
                <div className="text-right">
                    <span className="text-2xl font-heading font-black text-neon-magenta neon-text">
                        {displayValue}
                    </span>
                    <span className="ml-1 text-xs font-bold text-text-muted">min</span>
                </div>
            </div>

            {/* Mode toggle */}
            <div className="flex gap-2 mb-4">
                <button
                    onClick={() => onChange(null)}
                    className={`flex-1 rounded-lg py-2 text-xs font-heading font-bold transition-all ${
                        autoMode
                            ? 'bg-neon-cyan/15 border border-neon-cyan/40 text-neon-cyan shadow-neon-cyan'
                            : 'bg-cyber-dark-300 border border-cyber-dark-400/50 text-text-muted hover:border-cyber-dark-500'
                    }`}
                >
                    <Sparkles className="inline h-3.5 w-3.5 mr-1" />
                    Auto ({autoMinutes} min)
                </button>
                <button
                    onClick={() => onChange(60)}
                    className={`flex-1 rounded-lg py-2 text-xs font-heading font-bold transition-all ${
                        !autoMode
                            ? 'bg-neon-magenta/15 border border-neon-magenta/40 text-neon-magenta shadow-neon-magenta'
                            : 'bg-cyber-dark-300 border border-cyber-dark-400/50 text-text-muted hover:border-cyber-dark-500'
                    }`}
                >
                    <Clock className="inline h-3.5 w-3.5 mr-1" />
                    Personalizado
                </button>
            </div>

            {/* Slider */}
            {!autoMode && (
                <div className="space-y-3">
                    <div className="relative">
                        <input
                            type="range"
                            min="5"
                            max="300"
                            step="5"
                            value={value}
                            onChange={(e) => onChange(parseInt(e.target.value))}
                            className="w-full h-2 rounded-full appearance-none cursor-pointer
                                bg-cyber-dark-300
                                [&::-webkit-slider-thumb]:appearance-none
                                [&::-webkit-slider-thumb]:h-5
                                [&::-webkit-slider-thumb]:w-5
                                [&::-webkit-slider-thumb]:rounded-full
                                [&::-webkit-slider-thumb]:bg-neon-magenta
                                [&::-webkit-slider-thumb]:shadow-neon-magenta
                                [&::-webkit-slider-thumb]:cursor-pointer
                                [&::-webkit-slider-thumb]:transition-all
                                [&::-webkit-slider-thumb]:hover:scale-110
                                [&::-moz-range-thumb]:h-5
                                [&::-moz-range-thumb]:w-5
                                [&::-moz-range-thumb]:rounded-full
                                [&::-moz-range-thumb]:bg-neon-magenta
                                [&::-moz-range-thumb]:border-0
                                [&::-moz-range-thumb]:shadow-neon-magenta
                                [&::-moz-range-thumb]:cursor-pointer"
                            style={{
                                background: `linear-gradient(to right, #ff00ff ${(value / 300) * 100}%, rgba(26,34,53,1) ${(value / 300) * 100}%)`,
                            }}
                        />
                    </div>
                    <div className="flex justify-between text-[10px] font-semibold text-text-muted">
                        <span>5 min</span>
                        <span>150 min</span>
                        <span>300 min</span>
                    </div>

                    {/* Quick presets */}
                    <div className="flex gap-2 flex-wrap">
                        {[15, 30, 60, 90, 120].map((preset) => (
                            <button
                                key={preset}
                                onClick={() => onChange(preset)}
                                className={`px-3 py-1 rounded-md text-[10px] font-heading font-bold transition-all ${
                                    value === preset
                                        ? 'bg-neon-magenta/20 border border-neon-magenta/40 text-neon-magenta'
                                        : 'bg-cyber-dark-300 border border-cyber-dark-400/30 text-text-muted hover:border-cyber-dark-500'
                                }`}
                            >
                                {preset} min
                            </button>
                        ))}
                    </div>
                </div>
            )}

            {/* Info */}
            <div className="mt-4 flex items-start gap-2 text-[10px] text-text-muted bg-cyber-dark-300/50 rounded-lg p-3 border border-cyber-dark-400/30">
                <AlertTriangle className="h-3.5 w-3.5 text-neon-magenta flex-shrink-0 mt-0.5" />
                <p className="leading-relaxed">
                    <strong className="text-text-secondary">Auto:</strong> ~30 segundos por pregunta.
                    <strong className="text-text-secondary ml-1">Personalizado:</strong> define un límite fijo.
                    El tiempo se descuenta desde que inicia el diagnóstico.
                </p>
            </div>
        </div>
    );
}

export default function Configurar({ areas, configurados, duracionMinutos }) {
    const { flash } = usePage().props;

    const [conceptos, setConceptos] = useState(() => {
        const map = {};
        areas.forEach(a => {
            a.conceptos.forEach(c => {
                const cfg = configurados[c.id];
                map[c.id] = {
                    id: c.id,
                    incluido: cfg !== undefined,
                    preguntas_por_concepto: cfg ?? 10,
                };
            });
        });
        return map;
    });

    const [duracion, setDuracion] = useState(duracionMinutos ?? null);
    const [saving, setSaving] = useState(false);
    const [areasExpanded, setAreasExpanded] = useState(() => {
        const map = {};
        areas.forEach(a => {
            const hasActive = a.conceptos.some(c => configurados[c.id] !== undefined);
            map[a.id] = hasActive;
        });
        return map;
    });

    const toggleConcepto = (id) => {
        setConceptos(prev => ({
            ...prev,
            [id]: { ...prev[id], incluido: !prev[id].incluido }
        }));
    };

    const setPreguntas = (id, val) => {
        const num = Math.max(1, Math.min(100, parseInt(val) || 1));
        setConceptos(prev => ({
            ...prev,
            [id]: { ...prev[id], preguntas_por_concepto: num }
        }));
    };

    const toggleArea = (areaId) => {
        setAreasExpanded(prev => ({
            ...prev,
            [areaId]: !prev[areaId]
        }));
    };

    const selectAllInArea = (areaId, selected) => {
        const area = areas.find(a => a.id === areaId);
        if (!area) return;
        setConceptos(prev => {
            const next = { ...prev };
            area.conceptos.forEach(c => {
                if (next[c.id]) {
                    next[c.id] = { ...next[c.id], incluido: selected };
                }
            });
            return next;
        });
        if (selected) {
            setAreasExpanded(prev => ({ ...prev, [areaId]: true }));
        }
    };

    const guardar = () => {
        setSaving(true);
        router.put('/admin/diagnostico/configurar', {
            conceptos: Object.values(conceptos),
            duracion_minutos: duracion,
        }, {
            onFinish: () => setSaving(false),
        });
    };

    const stats = useMemo(() => {
        const incluidos = Object.values(conceptos).filter(c => c.incluido);
        const totalPreg = incluidos.reduce((sum, c) => sum + c.preguntas_por_concepto, 0);
        return {
            totalConceptos: incluidos.length,
            totalPreguntas: totalPreg,
            areasActivas: areas.filter(a =>
                a.conceptos.some(c => conceptos[c.id]?.incluido)
            ).length,
            tiempoEstimado: duracion
                ? duracion
                : Math.max(5, Math.ceil(totalPreg / 2)),
        };
    }, [conceptos, duracion, areas]);

    return (
        <>
            <Head title="Configurar Diagnóstico" />

            <div className="min-h-screen bg-cyber-dark">
                {/* Header */}
                <div className="relative overflow-hidden border-b border-cyber-dark-400/50 bg-cyber-dark-100 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.08),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10 py-8">
                        <div className="flex items-center gap-5">
                            <div className="flex h-16 w-16 items-center justify-center rounded-xl cyber-card border-neon-magenta/40 shadow-neon-magenta">
                                <Settings2 className="h-8 w-8 text-neon-magenta" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-heading font-black text-text-primary">
                                    Configurar{' '}
                                    <span className="neon-text-cyan">Diagnóstico</span>
                                </h1>
                                <p className="mt-1 text-sm text-text-secondary font-semibold">
                                    Selecciona los cursos, define preguntas y ajusta el tiempo límite
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-6 pb-12">
                    {/* Flash Messages */}
                    {flash?.success && (
                        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                            className="mb-6 flex items-center gap-3 rounded-xl cyber-card border-neon-green/30 px-5 py-4 shadow-neon-green/10">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-neon-green/10 border border-neon-green/30">
                                <CheckCircle2 className="h-4 w-4 text-neon-green" />
                            </div>
                            <p className="text-sm font-bold text-neon-green">{flash.success}</p>
                        </motion.div>
                    )}

                    {/* Summary Dashboard */}
                    <div className="cyber-card rounded-xl border-neon-cyan/20 p-5 mb-6">
                        <div className="flex items-center justify-between flex-wrap gap-4">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-neon-cyan/10 border border-neon-cyan/30">
                                    <BarChart3 className="h-5 w-5 text-neon-cyan" />
                                </div>
                                <div>
                                    <h2 className="text-sm font-heading font-bold text-text-primary">Resumen</h2>
                                    <p className="text-[10px] text-text-muted font-semibold">Configuración actual</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-4 sm:gap-6">
                                <div className="text-center">
                                    <p className="text-lg font-heading font-black text-neon-cyan neon-text">{stats.areasActivas}</p>
                                    <p className="text-[10px] font-bold text-text-muted uppercase tracking-wider">Áreas</p>
                                </div>
                                <div className="h-8 w-px bg-cyber-dark-400/50" />
                                <div className="text-center">
                                    <p className="text-lg font-heading font-black text-neon-cyan neon-text">{stats.totalConceptos}</p>
                                    <p className="text-[10px] font-bold text-text-muted uppercase tracking-wider">Cursos</p>
                                </div>
                                <div className="h-8 w-px bg-cyber-dark-400/50" />
                                <div className="text-center">
                                    <p className="text-lg font-heading font-black text-neon-cyan neon-text">{stats.totalPreguntas}</p>
                                    <p className="text-[10px] font-bold text-text-muted uppercase tracking-wider">Preguntas</p>
                                </div>
                                <div className="h-8 w-px bg-cyber-dark-400/50" />
                                <div className="text-center">
                                    <p className="text-lg font-heading font-black text-neon-magenta neon-text">{stats.tiempoEstimado}</p>
                                    <p className="text-[10px] font-bold text-text-muted uppercase tracking-wider">Minutos</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="grid gap-6 lg:grid-cols-3">
                        {/* Left column: Areas & Concepts */}
                        <div className="lg:col-span-2 space-y-4">
                            {areas.map((area) => {
                                const areaConceptos = area.conceptos.filter(c => conceptos[c.id]?.incluido);
                                const areaPreguntas = areaConceptos.reduce((s, c) => s + (conceptos[c.id]?.preguntas_por_concepto ?? 0), 0);
                                const areaTotalDisp = area.conceptos.reduce((s, c) => s + (c.preguntas_count || 0), 0);
                                const isExpanded = areasExpanded[area.id] ?? true;
                                const allSelected = area.conceptos.every(c => conceptos[c.id]?.incluido);
                                const someSelected = area.conceptos.some(c => conceptos[c.id]?.incluido);

                                return (
                                    <motion.div
                                        key={area.id}
                                        layout
                                        className="cyber-card rounded-xl border-cyber-dark-400/40 overflow-hidden"
                                    >
                                        {/* Area header */}
                                        <button
                                            onClick={() => toggleArea(area.id)}
                                            className="w-full flex items-center justify-between p-4 bg-cyber-dark-200/50 hover:bg-cyber-dark-200/80 transition-all border-b border-cyber-dark-400/30"
                                        >
                                            <div className="flex items-center gap-3">
                                                <div className={`flex h-9 w-9 items-center justify-center rounded-lg border transition-all ${
                                                    someSelected
                                                        ? 'bg-neon-cyan/10 border-neon-cyan/30'
                                                        : 'bg-cyber-dark-300 border-cyber-dark-400/50'
                                                }`}>
                                                    <Layers className={`h-4 w-4 ${someSelected ? 'text-neon-cyan' : 'text-text-muted'}`} />
                                                </div>
                                                <div className="text-left">
                                                    <h3 className="text-sm font-heading font-bold text-text-primary">{area.nombre}</h3>
                                                    <p className="text-[10px] text-text-muted font-semibold">
                                                        {area.conceptos.length} cursos · {areaTotalDisp} preguntas disponibles
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-3">
                                                {areaConceptos.length > 0 && (
                                                    <span className="text-xs font-heading font-bold text-neon-cyan neon-text">
                                                        {areaPreguntas} preg
                                                    </span>
                                                )}
                                                <div className="flex items-center gap-1">
                                                    {/* Select All / Deselect All */}
                                                    <button
                                                        onClick={(e) => { e.stopPropagation(); selectAllInArea(area.id, !allSelected); }}
                                                        className={`text-[10px] font-heading font-bold px-2 py-1 rounded-md transition-all ${
                                                            allSelected
                                                                ? 'bg-neon-magenta/10 text-neon-magenta border border-neon-magenta/30'
                                                                : 'text-text-muted hover:text-text-primary border border-transparent hover:border-cyber-dark-400/50'
                                                        }`}
                                                    >
                                                        {allSelected ? 'Deselec.' : 'Selec.'}
                                                    </button>
                                                </div>
                                                {isExpanded ? <ChevronUp className="h-4 w-4 text-text-muted" /> : <ChevronDown className="h-4 w-4 text-text-muted" />}
                                            </div>
                                        </button>

                                        {/* Concepts */}
                                        <motion.div
                                            initial={false}
                                            animate={{
                                                height: isExpanded ? 'auto' : 0,
                                                opacity: isExpanded ? 1 : 0,
                                            }}
                                            transition={{ duration: 0.25, ease: 'easeInOut' }}
                                            className="overflow-hidden divide-y divide-cyber-dark-400/20"
                                        >
                                                {area.conceptos.map((c) => {
                                                    const cfg = conceptos[c.id];
                                                    if (!cfg) return null;
                                                    const disp = c.preguntas_count || 0;

                                                    return (
                                                        <div key={c.id}
                                                            className={`flex items-center gap-3 px-4 py-3 transition-all ${
                                                                cfg.incluido
                                                                    ? 'bg-neon-cyan/[0.02]'
                                                                    : 'opacity-50 hover:opacity-70'
                                                            }`}>
                                                            {/* Custom toggle */}
                                                            <button
                                                                onClick={() => toggleConcepto(c.id)}
                                                                className={`relative flex h-6 w-11 flex-shrink-0 rounded-full border-2 transition-all duration-300 ${
                                                                    cfg.incluido
                                                                        ? 'border-neon-cyan bg-neon-cyan/20 shadow-neon-cyan'
                                                                        : 'border-cyber-dark-400 bg-cyber-dark-300'
                                                                }`}
                                                            >
                                                                <span className={`absolute top-0.5 left-0.5 h-4 w-4 rounded-full transition-all duration-300 ${
                                                                    cfg.incluido
                                                                        ? 'translate-x-5 bg-neon-cyan shadow-neon-cyan'
                                                                        : 'translate-x-0 bg-text-muted'
                                                                }`} />
                                                            </button>

                                                            {/* Concept info */}
                                                            <div className="flex items-center gap-2 min-w-0 flex-1">
                                                                <BookOpen className={`h-4 w-4 flex-shrink-0 ${
                                                                    cfg.incluido ? 'text-neon-cyan' : 'text-text-muted'
                                                                }`} />
                                                                <span className={`text-sm font-semibold truncate ${
                                                                    cfg.incluido ? 'text-text-primary' : 'text-text-muted'
                                                                }`}>
                                                                    {c.nombre}
                                                                </span>
                                                                <span className="text-[10px] font-medium text-text-muted flex-shrink-0">
                                                                    ({disp} disp.)
                                                                </span>
                                                            </div>

                                                            {/* Question count stepper */}
                                                            {cfg.incluido && (
                                                                <div className="flex items-center gap-2 flex-shrink-0">
                                                                    <span className="text-[10px] font-bold text-text-muted uppercase tracking-wider">Preg:</span>
                                                                    <NumberStepper
                                                                        value={cfg.preguntas_por_concepto}
                                                                        onChange={(val) => setPreguntas(c.id, val)}
                                                                        min={1}
                                                                        max={disp || 100}
                                                                        compact={true}
                                                                    />
                                                                </div>
                                                            )}
                                                        </div>
                                                    );
                                                })}
                                            </motion.div>
                                    </motion.div>
                                );
                            })}

                            {areas.length === 0 && (
                                <div className="cyber-card rounded-xl border-cyber-dark-400/40 p-10 text-center">
                                    <Brain className="h-12 w-12 mx-auto text-text-muted mb-4" />
                                    <p className="text-text-muted font-semibold">No hay áreas académicas disponibles.</p>
                                    <p className="text-sm text-text-muted mt-1">Crea áreas académicas primero para configurar el diagnóstico.</p>
                                </div>
                            )}
                        </div>

                        {/* Right column: Time + Save */}
                        <div className="space-y-4">
                            {/* Time Configuration */}
                            <TimeSlider
                                value={duracion}
                                onChange={setDuracion}
                                totalPreguntas={stats.totalPreguntas}
                            />

                            {/* Quick stats card */}
                            <div className="cyber-card rounded-xl border-cyber-dark-400/40 p-4">
                                <h3 className="text-xs font-heading font-bold text-neon-cyan uppercase tracking-widest mb-3 neon-text">
                                    <Sparkles className="inline h-3.5 w-3.5 mr-1" />
                                    Distribución
                                </h3>
                                <div className="space-y-2">
                                    {areas.filter(a => a.conceptos.some(c => conceptos[c.id]?.incluido)).map(area => {
                                        const areaPregs = area.conceptos
                                            .filter(c => conceptos[c.id]?.incluido)
                                            .reduce((s, c) => s + (conceptos[c.id]?.preguntas_por_concepto ?? 0), 0);
                                        const pct = stats.totalPreguntas > 0 ? (areaPregs / stats.totalPreguntas) * 100 : 0;
                                        return (
                                            <div key={area.id}>
                                                <div className="flex justify-between text-xs mb-1">
                                                    <span className="font-semibold text-text-secondary truncate">{area.nombre}</span>
                                                    <span className="font-bold text-text-muted">{areaPregs} preg</span>
                                                </div>
                                                <div className="h-1.5 w-full rounded-full bg-cyber-dark-300 overflow-hidden border border-cyber-dark-400/30">
                                                    <div
                                                        className="h-full rounded-full bg-gradient-to-r from-neon-cyan to-neon-magenta transition-all duration-500"
                                                        style={{ width: `${pct}%` }}
                                                    />
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* Save button */}
                            <button
                                onClick={guardar}
                                disabled={saving || stats.totalConceptos === 0}
                                className="w-full cyber-btn cyber-btn-primary rounded-xl py-4 text-sm disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                                {saving ? (
                                    <>
                                        <div className="h-4 w-4 animate-spin rounded-full border-2 border-cyber-dark border-t-neon-cyan" />
                                        Guardando...
                                    </>
                                ) : (
                                    <>
                                        <Save className="h-4 w-4" />
                                        Guardar configuración
                                    </>
                                )}
                            </button>

                            {stats.totalConceptos === 0 && (
                                <p className="text-[10px] text-text-muted text-center font-semibold">
                                    Selecciona al menos un curso para guardar
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
