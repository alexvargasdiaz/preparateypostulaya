import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { motion } from 'motion/react';
import { useDialog } from '@/Components/DialogProvider';
import {
    Search, FileText, Pencil, Trash2, Layers, BookOpen,
    Clock, RotateCw, List, Building2, GraduationCap,
    CheckCircle, AlertCircle, Sparkles
} from 'lucide-react';

export default function AdminExamenesIndex({ examenes, areas, filtros }) {
    const { confirm } = useDialog();
    const { flash } = usePage().props;
    const [busqueda, setBusqueda] = useState(filtros?.busqueda || '');
    const [filtroArea, setFiltroArea] = useState(filtros?.area_academica_id || '');

    const aplicarFiltros = () => {
        router.get('/admin/examenes', { busqueda, area_academica_id: filtroArea }, { preserveState: true });
    };

    const eliminar = async (id, titulo) => {
        const ok = await confirm(`¿Eliminar el examen "${titulo}"?`, { title: 'Eliminar examen', confirmText: 'Eliminar', variant: 'danger' });
        if (!ok) return;
        router.delete(`/admin/examenes/${id}`);
    };

    return (
        <>
            <Head title="Admin - Exámenes" />
            <div className="min-h-screen bg-cyber-dark">
                <div className="relative overflow-hidden bg-gradient-to-br from-neon-cyan/15 via-cyber-dark-100 to-cyber-dark py-8 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.1),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div className="cyber-badge cyber-badge-magenta rounded-lg px-4 py-1.5 text-sm font-bold inline-flex mb-3">
                                    <Sparkles className="h-4 w-4" /> Exámenes
                                </div>
                                <h1 className="text-2xl font-heading font-black text-text-primary">Gestión de Exámenes</h1>
                                <p className="mt-1 text-sm font-semibold text-text-muted">{examenes?.total || 0} exámenes</p>
                            </div>
                            <Link href="/admin/examenes/crear" className="cyber-btn cyber-btn-primary rounded-xl px-5 py-2.5 text-sm font-bold">+ Nuevo examen</Link>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-4 pb-8">
                    {flash?.success && (
                        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                            className="mb-4 rounded-xl border border-neon-green/30 bg-neon-green/5 px-5 py-3 text-sm font-bold text-neon-green flex items-center gap-2">
                            <CheckCircle className="h-4 w-4" /> {flash.success}
                        </motion.div>
                    )}
                    {flash?.error && (
                        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                            className="mb-4 rounded-xl border border-neon-magenta/30 bg-neon-magenta/5 px-5 py-3 text-sm font-bold text-neon-cyan flex items-center gap-2">
                            <AlertCircle className="h-4 w-4" /> {flash.error}
                        </motion.div>
                    )}

                    <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }}
                        className="cyber-card rounded-xl p-4 mb-6">
                        <div className="flex flex-col gap-3 sm:flex-row">
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted" />
                                <input type="text" placeholder="Buscar..." value={busqueda}
                                    onChange={(e) => setBusqueda(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && aplicarFiltros()}
                                    className="w-full cyber-input rounded-xl py-2.5 pl-10 pr-4 text-sm" />
                            </div>
                            <select value={filtroArea} onChange={(e) => setFiltroArea(e.target.value)}
                                className="cyber-input rounded-xl px-4 py-2.5 text-sm">
                                <option value="">Todas las áreas</option>
                                {areas?.map((a) => (<option key={a.id} value={a.id}>{a.nombre}</option>))}
                            </select>
                            <button onClick={aplicarFiltros} className="cyber-btn cyber-btn-primary rounded-xl px-5 py-2.5 text-sm font-bold">Filtrar</button>
                        </div>
                    </motion.div>

                    {examenes?.data?.length === 0 ? (
                        <motion.div initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }} className="cyber-card rounded-xl p-12 text-center">
                            <FileText className="mx-auto h-12 w-12 text-text-muted mb-4" />
                            <p className="text-lg font-heading font-bold text-text-primary">No hay exámenes</p>
                            <p className="text-sm font-semibold text-text-muted">Crea el primer examen.</p>
                        </motion.div>
                    ) : (
                        <div className="space-y-3">
                            {examenes?.data?.map((ex, index) => (
                                <motion.div key={ex.id} initial={{ opacity: 0, y: 15 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: index * 0.05 }}
                                    className="cyber-card rounded-xl p-5 hover:border-neon-cyan/30 transition-all">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="flex-1 min-w-0">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="text-xs font-bold text-neon-cyan flex items-center gap-1.5">
                                                    <Layers className="h-3.5 w-3.5" /> {ex.area_academica?.nombre || 'Sin área'}
                                                </p>
                                                {ex.categoria && (
                                                    <span className="inline-flex items-center gap-1 rounded-lg bg-neon-magenta/10 border border-neon-magenta/30 px-2.5 py-0.5 text-[10px] font-bold text-neon-magenta">
                                                        <Building2 className="h-3 w-3" /> {ex.categoria?.institucion?.nombre} <GraduationCap className="h-3 w-3 ml-0.5" /> {ex.categoria.nombre}
                                                    </span>
                                                )}
                                            </div>
                                            <h3 className="mt-1 text-base font-heading font-bold text-text-primary">{ex.titulo}</h3>
                                            <div className="mt-2 flex items-center gap-3 text-xs font-semibold text-text-muted flex-wrap">
                                                <span className="flex items-center gap-1"><Clock className="h-3.5 w-3.5 text-neon-cyan" /> {ex.tiempo_limite_min} min</span>
                                                <span className="flex items-center gap-1"><BookOpen className="h-3.5 w-3.5 text-neon-magenta" /> {ex.conceptos_count || 0} cursos</span>
                                                <span className="flex items-center gap-1"><List className="h-3.5 w-3.5 text-neon-cyan" /> {ex.preguntas_por_intento || 0} preguntas</span>
                                                <span className="flex items-center gap-1"><RotateCw className="h-3.5 w-3.5 text-neon-magenta" /> {ex.intentos_permitidos} intentos</span>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2 flex-shrink-0">
                                            <Link href={`/admin/examenes/${ex.id}/editar`}
                                                className="inline-flex items-center gap-1 rounded-lg bg-neon-cyan/10 border border-neon-cyan/30 px-3 py-1.5 text-xs font-bold text-neon-cyan hover:bg-neon-cyan/20"><Pencil className="h-3.5 w-3.5" /> Editar</Link>
                                            <Link href={`/admin/preguntas?area_academica_id=${ex.area_academica_id}`}
                                                className="inline-flex items-center gap-1 rounded-lg bg-neon-magenta/10 border border-neon-magenta/30 px-3 py-1.5 text-xs font-bold text-neon-magenta hover:bg-neon-magenta/20"><FileText className="h-3.5 w-3.5" /> Preguntas</Link>
                                            <button onClick={() => eliminar(ex.id, ex.titulo)}
                                                className="inline-flex items-center rounded-lg bg-neon-magenta/10 border border-neon-magenta/30 px-2.5 py-1.5 text-xs font-bold text-neon-magenta hover:bg-neon-magenta/20"><Trash2 className="h-3.5 w-3.5" /></button>
                                        </div>
                                    </div>
                                </motion.div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
