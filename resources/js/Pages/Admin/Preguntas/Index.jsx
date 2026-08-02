import { Head, Link, router } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { motion } from 'motion/react';
import { useDialog } from '@/Components/DialogProvider';
import { Search, FileText, Layers, BookOpen, CircleDot, Image, Pencil, Trash2, Upload, Sparkles } from 'lucide-react';

export default function AdminPreguntasIndex({ preguntas, areas, conceptos, filtros }) {
    const { confirm } = useDialog();
    const [busqueda, setBusqueda] = useState(filtros?.busqueda || '');
    const [filtroArea, setFiltroArea] = useState(filtros?.area_academica_id || '');
    const [filtroConcepto, setFiltroConcepto] = useState(filtros?.concepto_id || '');
    const [filtroDificultad, setFiltroDificultad] = useState(filtros?.dificultad || '');

    const conceptosFiltrados = useMemo(() => {
        if (!filtroArea) return [];
        return (conceptos || []).filter((c) => c.area_academica_id === parseInt(filtroArea));
    }, [filtroArea, conceptos]);

    const aplicarFiltros = () => {
        router.get('/admin/preguntas', { busqueda, area_academica_id: filtroArea, concepto_id: filtroConcepto, dificultad: filtroDificultad }, { preserveState: true });
    };

    const eliminarPregunta = async (id) => {
        const ok = await confirm('¿Eliminar esta pregunta?', { title: 'Eliminar pregunta', confirmText: 'Eliminar', variant: 'danger' });
        if (!ok) return;
        router.delete(`/admin/preguntas/${id}`);
    };

    const dificultadClasses = {
        facil: 'bg-neon-green/10 text-neon-green border border-neon-green/30',
        media: 'bg-neon-yellow/10 text-neon-yellow border border-neon-yellow/30',
        dificil: 'bg-neon-magenta/10 text-neon-magenta border border-neon-magenta/30',
    };

    return (
        <>
            <Head title="Admin - Preguntas" />
            <div className="min-h-screen bg-cyber-dark">
                <div className="relative overflow-hidden bg-gradient-to-br from-neon-magenta/15 via-cyber-dark-100 to-cyber-dark py-8 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.08),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div className="cyber-badge cyber-badge-magenta rounded-lg px-4 py-1.5 text-sm font-bold inline-flex mb-3">
                                    <Sparkles className="h-4 w-4" /> Preguntas
                                </div>
                                <h1 className="text-2xl font-heading font-black text-text-primary">Gestión de Preguntas</h1>
                                <p className="mt-1 text-sm font-semibold text-text-muted">{preguntas?.total || 0} preguntas</p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Link href="/admin/baul-preguntas" className="cyber-btn rounded-xl px-4 py-2.5 text-sm font-bold border-cyber-dark-400"><Layers className="h-4 w-4" /> Baúl</Link>
                                <Link href="/admin/preguntas/importar" className="cyber-btn rounded-xl px-4 py-2.5 text-sm font-bold border-cyber-dark-400"><Upload className="h-4 w-4" /> Importar</Link>
                                <Link href="/admin/preguntas/create" className="cyber-btn cyber-btn-primary rounded-xl px-5 py-2.5 text-sm font-bold">+ Nueva pregunta</Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-4 pb-8">
                    <div className="cyber-card rounded-xl p-4 mb-6">
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="relative flex-1 min-w-[200px]">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted" />
                                <input type="text" placeholder="Buscar..." value={busqueda}
                                    onChange={(e) => setBusqueda(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && aplicarFiltros()}
                                    className="w-full cyber-input rounded-xl pl-9 pr-4 py-2.5 text-sm" />
                            </div>
                            <select value={filtroArea} onChange={(e) => { setFiltroArea(e.target.value); setFiltroConcepto(''); }}
                                className="cyber-input rounded-xl px-4 py-2.5 text-sm">
                                <option value="">Todas las áreas</option>
                                {areas?.map((a) => (<option key={a.id} value={a.id}>{a.nombre}</option>))}
                            </select>
                            <select value={filtroConcepto} onChange={(e) => setFiltroConcepto(e.target.value)} className="cyber-input rounded-xl px-4 py-2.5 text-sm">
                                <option value="">Todos los cursos</option>
                                {conceptosFiltrados.map((c) => (<option key={c.id} value={c.id}>{c.nombre}</option>))}
                            </select>
                            <select value={filtroDificultad} onChange={(e) => setFiltroDificultad(e.target.value)} className="cyber-input rounded-xl px-4 py-2.5 text-sm">
                                <option value="">Todas</option>
                                <option value="facil">Fácil</option><option value="media">Media</option><option value="dificil">Difícil</option>
                            </select>
                            <button onClick={aplicarFiltros} className="cyber-btn cyber-btn-primary rounded-xl px-5 py-2.5 text-sm font-bold">Filtrar</button>
                        </div>
                    </div>

                    {preguntas?.data?.length === 0 ? (
                        <motion.div initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }} className="cyber-card rounded-xl p-12 text-center">
                            <FileText className="mx-auto h-12 w-12 text-text-muted mb-4" />
                            <p className="text-lg font-heading font-bold text-text-primary">No hay preguntas</p>
                            <p className="text-sm font-semibold text-text-muted">Crea la primera pregunta.</p>
                        </motion.div>
                    ) : (
                        <div className="space-y-3">
                            {preguntas?.data?.map((p, index) => (
                                <motion.div key={p.id} initial={{ opacity: 0, y: 15 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: index * 0.03 }}
                                    className="cyber-card rounded-xl p-5 hover:border-neon-cyan/30 transition-all">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-2 text-xs font-bold text-text-muted mb-1">
                                                <Layers className="h-3.5 w-3.5 text-neon-cyan" /> {p.area_academica?.nombre || 'Sin área'}
                                                <span className="text-cyber-dark-400">—</span>
                                                <BookOpen className="h-3.5 w-3.5 text-neon-magenta" /> {p.concepto?.nombre || 'Sin curso'}
                                            </div>
                                            <p className="text-sm text-text-primary line-clamp-2">{p.enunciado}</p>
                                            <div className="mt-2 flex flex-wrap items-center gap-2">
                                                <span className={`inline-flex items-center rounded-lg px-2 py-0.5 text-xs font-bold ${dificultadClasses[p.dificultad] || 'bg-cyber-dark-300 text-text-muted'}`}>{p.dificultad}</span>
                                                {p.enunciado_imagen_url && <span className="inline-flex items-center gap-1 text-xs font-semibold text-text-muted"><Image className="h-3 w-3" /> imagen</span>}
                                                <span className="flex items-center gap-1 text-xs font-semibold text-text-muted"><CircleDot className="h-3 w-3" /> {p.alternativas?.length || 0} alternativas</span>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2 flex-shrink-0">
                                            <Link href={`/admin/preguntas/${p.id}/edit`}
                                                className="inline-flex items-center gap-1 rounded-lg bg-neon-cyan/10 border border-neon-cyan/30 px-3 py-1.5 text-xs font-bold text-neon-cyan hover:bg-neon-cyan/20"><Pencil className="h-3.5 w-3.5" /> Editar</Link>
                                            <button onClick={() => eliminarPregunta(p.id)}
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
