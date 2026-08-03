import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
    GraduationCap, Building2, BookOpen, Layers, ArrowRight,
    ChevronRight, MapPin, Clock, HelpCircle, ArrowLeft, Trash2,
    X, Play
} from 'lucide-react';

const subtipoLabels = { publica: 'Pública', privada: 'Privada' };

const containerVariants = {
    hidden: {},
    visible: { transition: { staggerChildren: 0.08 } },
};

const itemVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: { opacity: 1, y: 0, transition: { duration: 0.4 } },
};

export default function ExplorarUniversidades({
    auth,
    instituciones = [],
    areas = [],
    tipos = [],
    carreras = [],
    institucionSel = null,
    areaSel = null,
    intentoActivo = null,
    filtros = {},
}) {
    const [institucionId, setInstitucionId] = useState(filtros.institucion_id || '');
    const [areaId, setAreaId] = useState(filtros.area_id || '');
    const [modalTipo, setModalTipo] = useState(null);
    const [categoriaId, setCategoriaId] = useState('');
    const [iniciando, setIniciando] = useState(false);
    const esEstudiante = auth.user?.rol === 'estudiante';

    const seleccionarInstitucion = (id) => {
        setInstitucionId(String(id));
        setAreaId('');
        router.get('/examenes/universidades', { institucion_id: id }, { preserveState: true, preserveScroll: true });
    };

    const seleccionarArea = (id) => {
        setAreaId(String(id));
        router.get('/examenes/universidades', { institucion_id: institucionId, area_id: id }, { preserveState: true, preserveScroll: true });
    };

    const limpiarTodo = () => {
        setInstitucionId('');
        setAreaId('');
        router.get('/examenes/universidades', {}, { preserveState: true, preserveScroll: true });
    };

    const abrirModal = (tipo) => {
        setCategoriaId('');
        setModalTipo(tipo);
    };

    const iniciarSimulacro = () => {
        if (!categoriaId || !modalTipo) return;
        setIniciando(true);
        router.post(
            `/examenes/universidades/${institucionId}/areas/${areaId}/tipos/${modalTipo.id}/iniciar`,
            { categoria_id: categoriaId },
            {
                onError: () => setIniciando(false),
                onFinish: () => setIniciando(false),
            }
        );
    };

    return (
        <>
            <Head title="Simulacros por Universidad" />

            <div className="min-h-screen bg-cyber-dark cyber-grid">
                {/* Header */}
                <div className="relative overflow-hidden bg-gradient-to-br from-neon-cyan/15 via-cyber-dark-100 to-cyber-dark pb-14 pt-8 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.1),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div className="inline-flex items-center gap-2 rounded-full bg-neon-cyan/10 border border-neon-cyan/30 px-4 py-1.5 text-sm font-bold text-neon-cyan mb-4">
                                    <BookOpen className="h-4 w-4" />
                                    Simulacros por Universidad
                                </div>
                                <h1 className="text-3xl font-heading font-black text-text-primary sm:text-4xl lg:text-5xl">
                                    Elige tu{' '}
                                    <span className="neon-text-cyan">universidad</span>
                                </h1>
                                <p className="mt-2 max-w-2xl text-sm text-text-muted sm:text-base">
                                    Selecciona tu universidad y área académica, registra la carrera a la que postulas y comienza tu simulacro.
                                </p>
                            </div>
                            <Link
                                href="/dashboard"
                                className="cyber-btn rounded-xl px-4 py-2.5 text-sm font-bold border-cyber-dark-400"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Mi panel
                            </Link>
                        </div>

                        {/* Breadcrumb */}
                        {(institucionSel || areaSel) && (
                            <div className="mt-6 flex items-center gap-2 text-sm text-text-secondary">
                                <button onClick={limpiarTodo} className="flex items-center gap-1 hover:text-neon-cyan transition-colors font-semibold">
                                    <Building2 className="h-4 w-4" />
                                    Universidades
                                </button>
                                {institucionSel && (
                                    <>
                                        <ChevronRight className="h-4 w-4 text-text-muted" />
                                        {areaSel ? (
                                            <button onClick={() => seleccionarInstitucion(institucionSel.id)} className="hover:text-neon-cyan transition-colors font-semibold">
                                                {institucionSel.nombre}
                                            </button>
                                        ) : (
                                            <span className="text-text-primary font-bold">{institucionSel.nombre}</span>
                                        )}
                                    </>
                                )}
                                {areaSel && (
                                    <>
                                        <ChevronRight className="h-4 w-4 text-text-muted" />
                                        <span className="text-text-primary font-bold">{areaSel.nombre}</span>
                                    </>
                                )}
                            </div>
                        )}
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-4 pb-12">
                    {/* Step 1: Select University */}
                    {!institucionId && (
                        <div className="cyber-card rounded-xl p-4 sm:p-6">
                            <h2 className="mb-4 flex items-center gap-2 text-lg font-heading font-bold text-text-primary">
                                <Building2 className="h-5 w-5 text-neon-cyan" />
                                <span className="neon-text-cyan">Selecciona tu universidad</span>
                            </h2>
                            <motion.div
                                variants={containerVariants}
                                initial="hidden"
                                animate="visible"
                                className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                {instituciones.map((inst) => (
                                    <motion.button
                                        key={inst.id}
                                        variants={itemVariants}
                                        whileHover={{ y: -4 }}
                                        onClick={() => seleccionarInstitucion(inst.id)}
                                        className="cyber-card rounded-xl p-5 text-left group hover:border-neon-cyan/40 transition-all"
                                    >
                                        <div className={`h-1.5 w-full rounded-t-lg bg-gradient-to-r ${
                                            inst.subtipo === 'publica'
                                                ? 'from-neon-cyan to-neon-cyan/40'
                                                : 'from-neon-cyan/40 to-neon-cyan'}`} />
                                        <div className="mt-4">
                                            <span className="inline-flex items-center gap-1 rounded-full border border-neon-cyan/30 bg-neon-cyan/10 px-2.5 py-0.5 text-xs font-bold text-neon-cyan">
                                                {subtipoLabels[inst.subtipo] || inst.subtipo}
                                            </span>
                                            <h3 className="mt-2 text-base font-heading font-bold text-text-primary group-hover:text-neon-cyan transition-colors">
                                                {inst.nombre}
                                            </h3>
                                            {inst.ciudad && (
                                                <p className="mt-1 flex items-center gap-1 text-xs text-text-muted">
                                                    <MapPin className="h-3 w-3" />
                                                    {inst.ciudad}
                                                </p>
                                            )}
                                            <p className="mt-3 text-sm font-semibold text-text-secondary">
                                                {inst.categorias_count} carreras registradas
                                            </p>
                                        </div>
                                    </motion.button>
                                ))}
                            </motion.div>

                            {instituciones.length === 0 && (
                                <div className="rounded-xl border border-dashed border-cyber-dark-400/50 p-8 text-center">
                                    <p className="text-text-muted font-semibold">No hay universidades disponibles por ahora.</p>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Step 2: Select Academic Area */}
                    {institucionId && !areaId && (
                        <div className="cyber-card rounded-xl p-4 sm:p-6">
                            <div className="mb-4 flex items-center justify-between">
                                <h2 className="flex min-w-0 items-center gap-2 text-lg font-heading font-bold text-text-primary">
                                    <Layers className="h-5 w-5 flex-shrink-0 text-neon-cyan" />
                                    Áreas en <span className="truncate text-neon-cyan">{institucionSel?.nombre}</span>
                                </h2>
                                <button onClick={limpiarTodo} className="flex items-center gap-1 text-sm font-semibold text-text-muted hover:text-white transition-colors">
                                    <Trash2 className="h-4 w-4" />
                                    Cambiar universidad
                                </button>
                            </div>

                            <motion.div
                                variants={containerVariants}
                                initial="hidden"
                                animate="visible"
                                className="grid gap-4 sm:grid-cols-2"
                            >
                                {areas.map((area) => (
                                    <motion.button
                                        key={area.id}
                                        variants={itemVariants}
                                        whileHover={{ y: -2 }}
                                        onClick={() => seleccionarArea(area.id)}
                                        className="group rounded-xl border border-cyber-dark-400/40 bg-cyber-dark-200 px-5 py-4 text-left transition-all hover:border-neon-cyan/40 hover:shadow-[0_0_12px_rgba(0,240,255,0.08)]"
                                    >
                                        <p className="font-heading font-bold text-text-primary group-hover:text-neon-cyan transition-colors">{area.nombre}</p>
                                        {area.descripcion && (
                                            <p className="mt-1 text-xs text-text-muted line-clamp-2">{area.descripcion}</p>
                                        )}
                                        <div className="mt-3 flex flex-wrap items-center gap-3 text-xs font-semibold text-text-muted">
                                            <span className="flex items-center gap-1">
                                                <HelpCircle className="h-3.5 w-3.5 text-neon-cyan" />
                                                {area.total_preguntas} preguntas
                                            </span>
                                            <span className="flex items-center gap-1">
                                                <BookOpen className="h-3.5 w-3.5 text-neon-cyan/70" />
                                                {area.tipos_count} simulacros
                                            </span>
                                            {area.usa_banco_propio ? (
                                                <span className="rounded-md bg-neon-green/10 border border-neon-green/30 px-2 py-0.5 text-[10px] font-bold text-neon-green">
                                                    Banco propio
                                                </span>
                                            ) : (
                                                <span className="rounded-md bg-neon-yellow/10 border border-neon-yellow/30 px-2 py-0.5 text-[10px] font-bold text-neon-yellow">
                                                    Banco global
                                                </span>
                                            )}
                                        </div>
                                    </motion.button>
                                ))}
                            </motion.div>

                            {areas.length === 0 && (
                                <div className="rounded-xl border border-dashed border-cyber-dark-400/50 p-8 text-center">
                                    <p className="text-text-muted font-semibold">Esta universidad aún no tiene áreas académicas disponibles.</p>
                                    <button onClick={limpiarTodo} className="mt-3 text-sm font-bold text-neon-cyan hover:underline">
                                        Elegir otra universidad
                                    </button>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Step 3: Choose Simulacro */}
                    {institucionId && areaId && (
                        <div className="cyber-card rounded-xl p-4 sm:p-6">
                            <div className="mb-4 flex items-center justify-between">
                                <div>
                                    <h2 className="flex items-center gap-2 text-lg font-heading font-bold text-text-primary">
                                        <BookOpen className="h-5 w-5 text-neon-cyan" />
                                        <span className="neon-text-cyan">Simulacros disponibles</span>
                                    </h2>
                                    <p className="mt-1 text-sm font-semibold text-text-muted">
                                        {institucionSel?.nombre} — {areaSel?.nombre}
                                    </p>
                                </div>
                                <button onClick={() => seleccionarInstitucion(institucionSel.id)} className="flex items-center gap-1 text-sm font-semibold text-text-muted hover:text-white transition-colors">
                                    <ArrowLeft className="h-4 w-4" />
                                    Cambiar área
                                </button>
                            </div>

                            {intentoActivo && (
                                <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                                    className="mb-6 cyber-card rounded-xl p-5 border-neon-cyan/30">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-neon-cyan/15 border border-neon-cyan/30">
                                                <Play className="h-5 w-5 text-neon-cyan" />
                                            </div>
                                            <div>
                                                <p className="font-heading font-bold text-text-primary neon-text-cyan">Tienes un simulacro en curso</p>
                                                <p className="text-sm font-semibold text-text-muted">Continúa donde lo dejaste</p>
                                            </div>
                                        </div>
                                        <Link href={`/examenes/intento/${intentoActivo.id}`}
                                            className="cyber-btn cyber-btn-primary rounded-xl px-6 py-2.5 text-sm font-bold">
                                            Continuar
                                        </Link>
                                    </div>
                                </motion.div>
                            )}

                            <motion.div
                                variants={containerVariants}
                                initial="hidden"
                                animate="visible"
                                className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                {tipos.map((tipo) => (
                                    <motion.div
                                        key={tipo.id}
                                        variants={itemVariants}
                                        whileHover={{ y: -4 }}
                                        className="cyber-card rounded-xl overflow-hidden group"
                                    >
                                        <div className="h-1.5 w-full bg-gradient-to-r from-neon-cyan to-neon-cyan/40" />
                                        <div className="p-5 sm:p-6">
                                            <h3 className="text-base font-heading font-bold text-text-primary">{tipo.nombre}</h3>
                                            {tipo.descripcion && (
                                                <p className="mt-2 text-sm text-text-muted line-clamp-2">{tipo.descripcion}</p>
                                            )}
                                            <div className="mt-4 flex flex-wrap items-center gap-3 text-xs font-semibold text-text-muted">
                                                <span className="flex items-center gap-1">
                                                    <HelpCircle className="h-3 w-3 text-neon-cyan" />
                                                    {tipo.num_preguntas} preguntas
                                                </span>
                                                <span className="flex items-center gap-1">
                                                    <Clock className="h-3 w-3 text-neon-cyan/70" />
                                                    {tipo.duracion_min} min
                                                </span>
                                            </div>
                                            {esEstudiante ? (
                                                <button
                                                    onClick={() => abrirModal(tipo)}
                                                    className="mt-5 flex w-full items-center justify-center gap-2 cyber-btn cyber-btn-primary rounded-xl px-4 py-3 text-sm font-bold"
                                                >
                                                    Comenzar simulacro
                                                    <ArrowRight className="h-4 w-4" />
                                                </button>
                                            ) : (
                                                <div className="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-cyber-dark-300 border border-cyber-dark-400/30 px-4 py-3 text-sm font-bold text-text-muted">
                                                    Inicia sesión para rendir
                                                </div>
                                            )}
                                        </div>
                                    </motion.div>
                                ))}
                            </motion.div>

                            {tipos.length === 0 && (
                                <div className="rounded-xl border border-dashed border-cyber-dark-400/50 p-8 text-center">
                                    <BookOpen className="mx-auto h-12 w-12 text-text-muted" />
                                    <p className="mt-3 text-text-primary font-bold">No hay simulacros para esta área</p>
                                    <p className="text-sm text-text-muted mt-1">Pronto agregaremos más contenido.</p>
                                    <button onClick={() => seleccionarInstitucion(institucionSel.id)} className="mt-4 text-sm font-bold text-neon-cyan hover:underline">
                                        Elegir otra área
                                    </button>
                                </div>
                            )}
                        </div>
                    )}

                    <div className="h-12" />
                </div>
            </div>

            {/* Modal: registrar carrera a la que postula */}
            <AnimatePresence>
                {modalTipo && (
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="fixed inset-0 z-50 flex items-center justify-center bg-cyber-dark/90 p-4 backdrop-blur-sm"
                        onClick={() => !iniciando && setModalTipo(null)}
                    >
                        <motion.div
                            initial={{ scale: 0.9, y: 20, opacity: 0 }}
                            animate={{ scale: 1, y: 0, opacity: 1 }}
                            exit={{ scale: 0.9, y: 20, opacity: 0 }}
                            transition={{ duration: 0.25 }}
                            onClick={(e) => e.stopPropagation()}
                            className="cyber-card w-full max-w-lg rounded-2xl overflow-hidden"
                        >
                            <div className="relative bg-gradient-to-br from-neon-cyan/20 via-cyber-dark-100 to-cyber-dark px-6 py-6">
                                <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.1),transparent_60%)]" />
                                <button
                                    onClick={() => !iniciando && setModalTipo(null)}
                                    className="absolute right-4 top-4 rounded-xl border border-cyber-dark-400/50 bg-cyber-dark-300/80 p-1.5 text-text-muted hover:text-white hover:border-neon-cyan/40 transition-all"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                                <div className="relative">
                                    <div className="inline-flex items-center gap-2 rounded-full bg-neon-cyan/10 border border-neon-cyan/30 px-3 py-1 text-xs font-bold text-neon-cyan mb-3">
                                        <GraduationCap className="h-3.5 w-3.5" />
                                        Postulación
                                    </div>
                                    <h2 className="text-xl font-heading font-black text-text-primary">
                                        ¿A qué carrera <span className="neon-text-cyan">postulas?</span>
                                    </h2>
                                    <p className="mt-2 text-sm text-text-muted font-semibold">
                                        Registra la carrera a la que piensas postular en {institucionSel?.nombre}. Al finalizar verás si tu puntaje alcanza para esa u otras carreras.
                                    </p>
                                </div>
                            </div>

                            <div className="px-6 py-5">
                                <label className="block text-sm font-semibold text-text-secondary mb-1.5">
                                    Carrera a la que postulas
                                </label>
                                <select
                                    value={categoriaId}
                                    onChange={(e) => setCategoriaId(e.target.value)}
                                    className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm"
                                >
                                    <option value="" className="bg-cyber-dark">Selecciona tu carrera</option>
                                    {carreras.map((c) => (
                                        <option key={c.id} value={c.id} className="bg-cyber-dark">
                                            {c.nombre}
                                            {c.area_nombre ? ` (${c.area_nombre})` : ''}
                                        </option>
                                    ))}
                                </select>

                                {carreras.length === 0 && (
                                    <p className="mt-2 rounded-lg border border-neon-yellow/30 bg-neon-yellow/5 p-3 text-xs font-semibold text-neon-yellow">
                                        Esta universidad aún no registra carreras. Contacta al administrador.
                                    </p>
                                )}

                                <div className="mt-5 flex items-center justify-between gap-3">
                                    <button
                                        onClick={() => setModalTipo(null)}
                                        disabled={iniciando}
                                        className="cyber-btn rounded-xl px-5 py-2.5 text-sm font-bold border-cyber-dark-400 disabled:opacity-40"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        onClick={iniciarSimulacro}
                                        disabled={!categoriaId || carreras.length === 0 || iniciando}
                                        className="cyber-btn cyber-btn-primary rounded-xl px-6 py-2.5 text-sm font-bold disabled:opacity-40 disabled:cursor-not-allowed inline-flex items-center gap-2"
                                    >
                                        {iniciando ? (
                                            <>
                                                <span className="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/30 border-t-white" />
                                                Iniciando...
                                            </>
                                        ) : (
                                            <>
                                                <Play className="h-4 w-4" />
                                                Comenzar simulacro
                                            </>
                                        )}
                                    </button>
                                </div>
                            </div>
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>
        </>
    );
}
