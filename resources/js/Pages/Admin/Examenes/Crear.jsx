import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState, useMemo, useEffect } from 'react';
import { motion } from 'motion/react';
import { Layers, BookOpen, HelpCircle, Building2, GraduationCap } from 'lucide-react';

export default function AdminExamenesCrear({ areas, conceptos, instituciones, categorias, examen, editando }) {
    const { errors, flash } = usePage().props;

    const [tipo, setTipo] = useState(examen?.categoria_id ? 'universidad' : 'area');
    const [areaId, setAreaId] = useState(examen?.area_academica_id || '');
    const [institucionId, setInstitucionId] = useState(examen?.categoria?.institucion_id || '');
    const [categoriaId, setCategoriaId] = useState(examen?.categoria_id || '');
    const [titulo, setTitulo] = useState(examen?.titulo || '');
    const [descripcion, setDescripcion] = useState(examen?.descripcion || '');
    const [tiempoLimite, setTiempoLimite] = useState(examen?.tiempo_limite_min || 20);
    const [intentos, setIntentos] = useState(examen?.intentos_permitidos || 99);

    const [conceptosConfig, setConceptosConfig] = useState(() => {
        if (examen?.conceptos) {
            return examen.conceptos.map((c) => ({
                id: c.id,
                num_preguntas: c.pivot?.num_preguntas || 5,
                activo: true,
            }));
        }
        return [];
    });

    const categoriasFiltradas = useMemo(() => {
        if (!institucionId) return [];
        return (categorias || []).filter((c) => c.institucion_id === parseInt(institucionId));
    }, [institucionId, categorias]);

    const categoriaSel = useMemo(() => {
        if (!categoriaId || tipo !== 'universidad') return null;
        return categoriasFiltradas.find((c) => c.id === parseInt(categoriaId));
    }, [categoriaId, categoriasFiltradas, tipo]);

    // Auto-fill areaId from selected career
    useEffect(() => {
        if (tipo === 'universidad' && categoriaSel?.area_academica_id) {
            setAreaId(String(categoriaSel.area_academica_id));
        }
    }, [categoriaSel, tipo]);

    const conceptosFiltrados = useMemo(() => {
        if (!areaId) return [];
        return (conceptos || []).filter((c) => c.area_academica_id === parseInt(areaId));
    }, [areaId, conceptos]);

    // Auto-activar todos los conceptos al seleccionar un área (100 preguntas distribuidas equitativamente)
    useEffect(() => {
        if (!areaId) return;
        const idsArea = new Set(conceptosFiltrados.map((c) => c.id));
        setConceptosConfig((prev) => {
            const currentIds = new Set(prev.map((c) => c.id));
            const nuevos = conceptosFiltrados.filter((c) => !currentIds.has(c.id));
            if (nuevos.length === 0) return prev;
            const base = Math.floor(100 / nuevos.length);
            const resto = 100 % nuevos.length;
            return [
                ...prev,
                ...nuevos.map((c, i) => ({
                    id: c.id,
                    num_preguntas: base + (i < resto ? 1 : 0),
                    activo: true,
                })),
            ];
        });
    }, [areaId, conceptosFiltrados]);

    const updateNumPreguntas = (conceptoId, val) => {
        setConceptosConfig((prev) =>
            prev.map((c) => (c.id === conceptoId ? { ...c, num_preguntas: Math.max(1, parseInt(val) || 1) } : c))
        );
    };

    const totalPreguntas = useMemo(() => {
        return conceptosConfig.reduce((sum, c) => sum + c.num_preguntas, 0);
    }, [conceptosConfig]);

    const handleSubmit = (e) => {
        e.preventDefault();
        const data = {
            area_academica_id: areaId,
            categoria_id: tipo === 'universidad' ? categoriaId : null,
            titulo,
            descripcion,
            tiempo_limite_min: parseInt(tiempoLimite),
            intentos_permitidos: parseInt(intentos),
            conceptos: conceptosConfig
                .filter((c) => c.num_preguntas > 0)
                .map((c) => ({
                    id: c.id,
                    num_preguntas: c.num_preguntas,
                })),
        };

        if (editando) {
            router.put(`/admin/examenes/${examen.id}`, data);
        } else {
            router.post('/admin/examenes', data);
        }
    };

    return (
        <>
            <Head title={editando ? 'Editar Examen' : 'Nuevo Examen'} />
            <div className="min-h-screen bg-[#0b0f17]">
                <div className="relative py-8 overflow-hidden" style={{ background: 'linear-gradient(135deg, rgba(0,240,255,0.15), rgba(255,0,255,0.08))' }}>
                    <div className="absolute inset-0 opacity-5 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]" />
                    <div className="absolute inset-0" style={{ background: 'radial-gradient(ellipse at 30% 50%, rgba(0,240,255,0.1) 0%, transparent 60%), radial-gradient(ellipse at 70% 50%, rgba(255,0,255,0.08) 0%, transparent 60%)' }} />
                    <div className="relative mx-auto max-w-3xl px-5 sm:px-8 lg:px-10">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold neon-text-strong-cyan">{editando ? 'Editar Examen' : 'Nuevo Examen'}</h1>
                                <p className="mt-1 text-sm text-neon-cyan/60">Configura las preguntas por curso para el simulacro</p>
                            </div>
                            <Link href="/admin/examenes" className="neubr-btn rounded-xl px-4 py-2 text-sm">← Volver</Link>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-3xl px-5 sm:px-8 lg:px-10 mt-4 pb-8">
                    {flash?.success && (
                        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                            className="mb-4 rounded-xl border border-neon-green/30 bg-neon-green/10 px-5 py-3 text-sm font-medium text-neon-green shadow-neon-green">
                            {flash.success}
                        </motion.div>
                    )}

                    {flash?.error && (
                        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                            className="mb-4 rounded-xl border border-neon-magenta/30 bg-neon-magenta/10 px-5 py-3 text-sm font-medium text-neon-magenta shadow-neon-magenta">
                            {flash.error}
                        </motion.div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-4">
                        {/* Tipo de examen */}
                        <motion.div initial={{ opacity: 0, y: 15 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.3 }}
                            className="rounded-2xl border border-neon-cyan/20 bg-[rgba(11,15,23,0.85)] p-5 shadow-neon-cyan backdrop-blur-sm">
                            <h2 className="text-lg font-bold neon-text-cyan mb-4">Tipo de examen</h2>
                            <div className="flex gap-3">
                                <button type="button" onClick={() => { setTipo('area'); setAreaId(''); setCategoriaId(''); setInstitucionId(''); }}
                                    className={`flex-1 rounded-xl border-2 p-3 text-sm font-bold transition-all ${
                                        tipo === 'area'
                                            ? 'border-neon-magenta/50 bg-neon-magenta/10 text-neon-magenta shadow-neon-magenta'
                                            : 'border-neon-cyan/20 text-neon-cyan/60 hover:border-neon-cyan/40 hover:text-white'
                                    }`}>
                                    <Layers className="mx-auto h-6 w-6 mb-1" />
                                    Área Académica
                                </button>
                                <button type="button" onClick={() => { setTipo('universidad'); setAreaId(''); setConceptosConfig([]); }}
                                    className={`flex-1 rounded-xl border-2 p-3 text-sm font-bold transition-all ${
                                        tipo === 'universidad'
                                            ? 'border-neon-magenta/50 bg-neon-magenta/10 text-neon-magenta shadow-neon-magenta'
                                            : 'border-neon-cyan/20 text-neon-cyan/60 hover:border-neon-cyan/40 hover:text-white'
                                    }`}>
                                    <Building2 className="mx-auto h-6 w-6 mb-1" />
                                    Universidad
                                </button>
                            </div>
                        </motion.div>

                        {/* Universidad + Carrera (solo si tipo universidad) */}
                        {tipo === 'universidad' && (
                            <motion.div initial={{ opacity: 0, y: 15 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.3 }}
                                className="rounded-2xl border border-neon-cyan/20 bg-[rgba(11,15,23,0.85)] p-5 shadow-neon-cyan backdrop-blur-sm">
                                <h2 className="text-lg font-bold neon-text-cyan mb-4">
                                    <Building2 className="inline h-5 w-5 mr-1" />
                                    Universidad y Carrera
                                </h2>
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-neon-cyan/70 mb-1">Universidad</label>
                                        <select value={institucionId} onChange={(e) => { setInstitucionId(e.target.value); setCategoriaId(''); }}
                                            className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm" required={tipo === 'universidad'}>
                                            <option value="" className="bg-[rgba(11,15,23,0.95)]">Seleccionar universidad</option>
                                            {instituciones?.map((i) => (<option key={i.id} value={i.id} className="bg-[rgba(11,15,23,0.95)]">{i.nombre}</option>))}
                                        </select>
                                        {errors?.institucion_id && <p className="mt-1 text-xs text-neon-cyan/80/70">{errors.institucion_id}</p>}
                                    </div>
                                    {institucionId && (
                                        <div>
                                            <label className="block text-sm font-medium text-neon-cyan/70 mb-1">Carrera</label>
                                            <select value={categoriaId} onChange={(e) => { setCategoriaId(e.target.value); setConceptosConfig([]); }}
                                                className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm" required={tipo === 'universidad'}>
                                                <option value="" className="bg-[rgba(11,15,23,0.95)]">Seleccionar carrera</option>
                                                {categoriasFiltradas?.map((c) => (<option key={c.id} value={c.id} className="bg-[rgba(11,15,23,0.95)]">{c.nombre}</option>))}
                                            </select>
                                            {errors?.categoria_id && <p className="mt-1 text-xs text-neon-cyan/80/70">{errors.categoria_id}</p>}
                                        </div>
                                    )}
                                    {categoriaSel?.area_academica_id && (
                                        <div className="flex items-center gap-2 rounded-lg border border-neon-magenta/30 bg-neon-magenta/10 px-3 py-2 text-xs text-neon-cyan/80">
                                            <Layers size={14} />
                                            Área: {areas?.find((a) => a.id === categoriaSel.area_academica_id)?.nombre || '—'}
                                        </div>
                                    )}
                                </div>
                            </motion.div>
                        )}

                        {/* Área Académica (oculta en modo universidad, se obtiene de la carrera) */}
                        {tipo !== 'universidad' && (
                            <motion.div initial={{ opacity: 0, y: 15 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.3 }}
                                className="rounded-2xl border border-neon-cyan/20 bg-[rgba(11,15,23,0.85)] p-5 shadow-neon-cyan backdrop-blur-sm">
                                <h2 className="text-lg font-bold neon-text-cyan mb-4">
                                    <Layers className="inline h-5 w-5 mr-1" />
                                    Área Académica
                                </h2>
                                <div>
                                    <label className="block text-sm font-medium text-neon-cyan/70 mb-1">Área</label>
                                    <select value={areaId} onChange={(e) => { setAreaId(e.target.value); setConceptosConfig([]); }}
                                        className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm" required={tipo !== 'universidad'}>
                                        <option value="" className="bg-[rgba(11,15,23,0.95)]">Seleccionar área</option>
                                        {areas?.map((a) => (<option key={a.id} value={a.id} className="bg-[rgba(11,15,23,0.95)]">{a.nombre}</option>))}
                                    </select>
                                    {errors?.area_academica_id && <p className="mt-1 text-xs text-neon-cyan/80/70">{errors.area_academica_id}</p>}
                                </div>
                            </motion.div>
                        )}

                        <motion.div initial={{ opacity: 0, y: 15 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.3, delay: 0.1 }}
                            className="rounded-2xl border border-neon-cyan/20 bg-[rgba(11,15,23,0.85)] p-5 shadow-neon-cyan backdrop-blur-sm">
                            <h2 className="text-lg font-bold neon-text-cyan mb-4">Datos del examen</h2>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-neon-cyan/70 mb-1">Título del examen</label>
                                    <input type="text" value={titulo} onChange={(e) => setTitulo(e.target.value)}
                                        className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm"
                                        placeholder="Ej: Simulacro de Admisión - Ciencias Básicas" required />
                                    {errors?.titulo && <p className="mt-1 text-xs text-neon-cyan/80/70">{errors.titulo}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-neon-cyan/70 mb-1">Descripción (opcional)</label>
                                    <textarea value={descripcion} onChange={(e) => setDescripcion(e.target.value)}
                                        className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm" rows={2}
                                        placeholder="Breve descripción del examen" />
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label className="block text-sm font-medium text-neon-cyan/70 mb-1">Tiempo límite (min)</label>
                                        <input type="number" value={tiempoLimite} onChange={(e) => setTiempoLimite(e.target.value)}
                                            className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm" min={1} max={180} required />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-neon-cyan/70 mb-1">Intentos permitidos</label>
                                        <input type="number" value={intentos} onChange={(e) => setIntentos(e.target.value)}
                                            className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm" min={1} max={99} required />
                                    </div>
                                </div>
                            </div>
                        </motion.div>

                        {areaId && (
                            <motion.div initial={{ opacity: 0, y: 15 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.3, delay: 0.15 }}
                                className="rounded-2xl border border-neon-cyan/20 bg-[rgba(11,15,23,0.85)] p-5 shadow-neon-cyan backdrop-blur-sm">
                                <div className="flex items-center justify-between mb-4">
                                    <h2 className="text-lg font-bold neon-text-cyan">Cursos y preguntas</h2>
                                    <span className="neubr-badge-emerald rounded-full px-3 py-1 text-sm font-bold">
                                        {totalPreguntas} preguntas en total
                                    </span>
                                </div>
                                <p className="text-sm text-neon-cyan/50 mb-4">Selecciona los cursos y define cuántas preguntas incluir de cada uno.</p>

                                {conceptosFiltrados.length === 0 ? (
                                    <p className="text-sm text-neon-cyan/40">No hay cursos disponibles para esta área. Crea cursos en "Áreas Académicas".</p>
                                ) : (
                                    <div className="space-y-1">
                                        {conceptosFiltrados.map((concepto) => {
                                            const config = conceptosConfig.find((c) => c.id === concepto.id);
                                            return (
                                                <div key={concepto.id}
                                                    className="flex items-center gap-3 rounded-xl border border-neon-magenta/40 bg-neon-magenta/10 p-3 transition-all"
                                                >
                                                    <BookOpen className="h-5 w-5 flex-shrink-0 text-neon-magenta drop-shadow-[0_0_6px_rgba(255,0,255,0.5)]" />
                                                    <div className="flex-1 min-w-0">
                                                        <span className="text-sm font-medium text-white">
                                                            {concepto.nombre}
                                                        </span>
                                                        <span className="ml-2 text-[10px] text-neon-cyan/40">
                                                            {concepto.area_academica?.nombre || ''}
                                                        </span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <label className="text-xs text-neon-cyan/60">Preguntas:</label>
                                                        <input type="number" value={config?.num_preguntas || 5}
                                                            onChange={(e) => updateNumPreguntas(concepto.id, e.target.value)}
                                                            className="cyber-input w-16 rounded-lg px-2 py-1 text-sm text-center"
                                                            min={0} max={200} required />
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}

                                {errors?.conceptos && <p className="mt-2 text-xs text-neon-cyan/80/70">{errors.conceptos}</p>}
                            </motion.div>
                        )}

                        <motion.div initial={{ opacity: 0, y: 15 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.3, delay: 0.2 }}
                            className="flex items-center justify-between gap-4">
                            <Link href="/admin/examenes"
                                className="neubr-btn rounded-xl px-6 py-2.5 text-sm">
                                Cancelar
                            </Link>
                            <button type="submit" disabled={conceptosConfig.every((c) => c.num_preguntas === 0)}
                                className="neubr-btn neubr-btn-red rounded-xl px-8 py-2.5 text-sm disabled:opacity-40 disabled:cursor-not-allowed">
                                {editando ? 'Guardar cambios' : 'Crear examen'}
                            </button>
                        </motion.div>
                    </form>
                </div>
            </div>
        </>
    );
}
