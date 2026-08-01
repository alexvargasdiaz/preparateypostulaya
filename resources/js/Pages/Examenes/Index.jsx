import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { motion } from 'motion/react';
import {
    GraduationCap, Building2, BookOpen, FileText, ArrowRight,
    ChevronRight, MapPin, Clock, HelpCircle, ArrowLeft, Trash2,
    Sparkles
} from 'lucide-react';

const subtipoColores = {
    publica: 'cyber-badge-cyan',
    privada: 'cyber-badge-magenta',
};
const subtipoLabels = { publica: 'Pública', privada: 'Privada' };

const containerVariants = {
    hidden: {},
    visible: { transition: { staggerChildren: 0.08 } },
};

const itemVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: { opacity: 1, y: 0, transition: { duration: 0.4 } },
};

export default function ExplorarExamenes({
    auth,
    instituciones = [],
    categorias = [],
    examenes = [],
    totalCategorias = 0,
    totalExamenes = 0,
    institucionSel = null,
    categoriaSel = null,
    filtros = {},
}) {
    const [institucionId, setInstitucionId] = useState(filtros.institucion_id || '');
    const [categoriaId, setCategoriaId] = useState(filtros.categoria_id || '');
    const esEstudiante = auth.user?.rol === 'estudiante';

    const seleccionarInstitucion = (id) => {
        setInstitucionId(String(id));
        setCategoriaId('');
        router.get('/examenes', { institucion_id: id }, { preserveState: true, preserveScroll: true });
    };

    const seleccionarCategoria = (id) => {
        setCategoriaId(String(id));
        router.get('/examenes', { institucion_id: institucionId, categoria_id: id }, { preserveState: true, preserveScroll: true });
    };

    const limpiarTodo = () => {
        setInstitucionId('');
        setCategoriaId('');
        router.get('/examenes', {}, { preserveState: true, preserveScroll: true });
    };

    return (
        <>
            <Head title="Explorar Simulacros" />

            <div className="min-h-screen bg-cyber-dark cyber-grid">
                {/* Header */}
                <div className="relative overflow-hidden bg-gradient-to-br from-neon-cyan/15 via-cyber-dark-100 to-cyber-dark pb-14 pt-8">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.1),transparent_50%)]" />
                    <div className="relative mx-auto max-w-5xl px-5 sm:px-8 lg:px-10">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div className="cyber-badge cyber-badge-cyan rounded-lg px-4 py-1.5 text-sm font-bold inline-flex mb-4">
                                    <Sparkles className="h-4 w-4" />
                                    Elige tu examen de admisión
                                </div>
                                <h1 className="text-3xl font-heading font-black text-text-primary sm:text-4xl lg:text-5xl">
                                    Prepárate para tu{' '}
                                    <span className="neon-text-cyan">admisión</span>
                                </h1>
                                <p className="mt-2 max-w-2xl text-sm text-text-muted sm:text-base">
                                    Selecciona tu universidad y carrera, luego elige el examen que quieras rendir.
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

                        {(institucionSel || categoriaSel) && (
                            <div className="mt-6 flex items-center gap-2 text-sm text-text-secondary">
                                <button onClick={limpiarTodo} className="flex items-center gap-1 hover:text-neon-cyan transition-colors font-semibold">
                                    <Building2 className="h-4 w-4" />
                                    Universidades
                                </button>
                                {institucionSel && (
                                    <>
                                        <ChevronRight className="h-4 w-4 text-text-muted" />
                                        {categoriaSel ? (
                                            <button onClick={() => seleccionarInstitucion(institucionSel.id)} className="hover:text-neon-cyan transition-colors font-semibold">
                                                {institucionSel.nombre}
                                            </button>
                                        ) : (
                                            <span className="text-text-primary font-bold">{institucionSel.nombre}</span>
                                        )}
                                    </>
                                )}
                                {categoriaSel && (
                                    <>
                                        <ChevronRight className="h-4 w-4 text-text-muted" />
                                        <span className="text-text-primary font-bold">{categoriaSel.nombre}</span>
                                    </>
                                )}
                            </div>
                        )}
                    </div>
                </div>

                <div className="mx-auto max-w-5xl px-5 sm:px-8 lg:px-10 mt-4 pb-12">
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
                                            <span className={`cyber-badge rounded-lg px-2.5 py-0.5 text-xs font-bold ${subtipoColores[inst.subtipo] || 'cyber-badge-cyan'}`}>
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
                                                {inst.categorias_count} carreras disponibles
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

                    {/* Step 2: Select Career */}
                    {institucionId && !categoriaId && (
                        <div className="cyber-card rounded-xl p-4 sm:p-6">
                            <div className="mb-4 flex items-center justify-between">
                                <h2 className="flex min-w-0 items-center gap-2 text-lg font-heading font-bold text-text-primary">
                                    <GraduationCap className="h-5 w-5 flex-shrink-0 text-neon-cyan" />
                                    Carreras en <span className="truncate text-neon-cyan">{institucionSel?.nombre}</span>
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
                                className="flex flex-wrap gap-3"
                            >
                                {categorias.map((cat) => (
                                    <motion.button
                                        key={cat.id}
                                        variants={itemVariants}
                                        whileHover={{ y: -2 }}
                                        onClick={() => seleccionarCategoria(cat.id)}
                                        className="group flex-1 min-w-[200px] rounded-xl border border-cyber-dark-400/40 bg-cyber-dark-200 px-5 py-3 text-left transition-all hover:border-neon-cyan/40 hover:shadow-[0_0_12px_rgba(0,240,255,0.08)]"
                                    >
                                        <p className="font-heading font-bold text-text-primary group-hover:text-neon-cyan transition-colors">{cat.nombre}</p>
                                        <p className="mt-1 flex items-center gap-1 text-xs font-semibold text-text-muted">
                                            <FileText className="h-3 w-3" />
                                            {cat.examenes_count} exámenes
                                        </p>
                                        {cat.descripcion_corta && (
                                            <p className="mt-0.5 text-xs text-text-muted">{cat.descripcion_corta}</p>
                                        )}
                                    </motion.button>
                                ))}
                            </motion.div>

                            {categorias.length === 0 && (
                                <div className="rounded-xl border border-dashed border-cyber-dark-400/50 p-8 text-center">
                                    <p className="text-text-muted font-semibold">No hay carreras con exámenes disponibles en esta universidad.</p>
                                    <button onClick={limpiarTodo} className="mt-3 text-sm font-bold text-neon-cyan hover:underline">
                                        Elegir otra universidad
                                    </button>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Step 3: Choose Exam */}
                    {institucionId && categoriaId && (
                        <div className="cyber-card rounded-xl p-4 sm:p-6">
                            <div className="mb-4 flex items-center justify-between">
                                <div>
                                    <h2 className="flex items-center gap-2 text-lg font-heading font-bold text-text-primary">
                                        <FileText className="h-5 w-5 text-neon-cyan" />
                                        <span className="neon-text-cyan">Exámenes disponibles</span>
                                    </h2>
                                    <p className="mt-1 text-sm font-semibold text-text-muted">
                                        {institucionSel?.nombre} — {categoriaSel?.nombre}
                                    </p>
                                </div>
                                <button onClick={() => seleccionarInstitucion(institucionSel.id)} className="flex items-center gap-1 text-sm font-semibold text-text-muted hover:text-white transition-colors">
                                    <ArrowLeft className="h-4 w-4" />
                                    Cambiar carrera
                                </button>
                            </div>

                            <motion.div
                                variants={containerVariants}
                                initial="hidden"
                                animate="visible"
                                className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                {examenes.map((examen) => {
                                    const tienePreguntas = (examen.preguntas_count || 0) > 0;
                                    return (
                                        <motion.div
                                            key={examen.id}
                                            variants={itemVariants}
                                            whileHover={{ y: -4 }}
                                            className="cyber-card rounded-xl overflow-hidden group"
                                        >
                                            <div className="h-1.5 w-full bg-gradient-to-r from-neon-cyan to-neon-cyan/40" />
                                            <div className="p-5 sm:p-6">
                                                <h3 className="text-base font-heading font-bold text-text-primary">{examen.titulo}</h3>
                                                {examen.descripcion && (
                                                    <p className="mt-2 text-sm text-text-muted line-clamp-2">{examen.descripcion}</p>
                                                )}
                                                <div className="mt-4 flex flex-wrap items-center gap-3 text-xs font-semibold text-text-muted">
                                                    <span className="flex items-center gap-1">
                                                        <HelpCircle className="h-3 w-3 text-neon-cyan" />
                                                        {examen.preguntas_count} preguntas
                                                    </span>
                                                    {examen.tiempo_limite_min && (
                                                        <span className="flex items-center gap-1">
                                                            <Clock className="h-3 w-3 text-neon-cyan/70" />
                                                            {examen.tiempo_limite_min} min
                                                        </span>
                                                    )}
                                                </div>
                                                {tienePreguntas && esEstudiante && (
                                                    <Link
                                                        href={`/examenes/iniciar?examen_id=${examen.id}`}
                                                        className="mt-5 flex w-full items-center justify-center gap-2 cyber-btn cyber-btn-primary rounded-xl px-4 py-3 text-sm font-bold"
                                                    >
                                                        Comenzar simulacro
                                                        <ArrowRight className="h-4 w-4" />
                                                    </Link>
                                                )}
                                                {!tienePreguntas && (
                                                    <div className="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-cyber-dark-300 border border-cyber-dark-400/30 px-4 py-3 text-sm font-bold text-text-muted cursor-not-allowed">
                                                        Sin preguntas disponibles
                                                    </div>
                                                )}
                                            </div>
                                        </motion.div>
                                    );
                                })}
                            </motion.div>

                            {examenes.length === 0 && (
                                <div className="rounded-xl border border-dashed border-cyber-dark-400/50 p-8 text-center">
                                    <FileText className="mx-auto h-12 w-12 text-text-muted" />
                                    <p className="mt-3 text-text-primary font-bold">No hay exámenes disponibles para esta carrera</p>
                                    <p className="text-sm text-text-muted mt-1">Pronto agregaremos más contenido.</p>
                                    <button onClick={() => seleccionarInstitucion(institucionSel.id)} className="mt-4 text-sm font-bold text-neon-cyan hover:underline">
                                        Elegir otra carrera
                                    </button>
                                </div>
                            )}
                        </div>
                    )}

                    <div className="h-12" />
                </div>
            </div>
        </>
    );
}
