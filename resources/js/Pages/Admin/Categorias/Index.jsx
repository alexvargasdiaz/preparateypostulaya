import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Search, Building2, Pencil, FileText, Trash2, GraduationCap, CheckCircle2, AlertTriangle, Sparkles } from 'lucide-react';
import { motion } from 'motion/react';
import { useDialog } from '@/Components/DialogProvider';

export default function AdminCategoriasIndex({ categorias, instituciones, filtros }) {
    const { confirm } = useDialog();
    const { flash } = usePage().props;
    const [busqueda, setBusqueda] = useState(filtros?.busqueda || '');
    const [filtroInstitucion, setFiltroInstitucion] = useState(filtros?.institucion_id || '');

    const aplicarFiltros = () => {
        router.get('/admin/categorias', { busqueda, institucion_id: filtroInstitucion }, { preserveState: true });
    };

    const eliminar = async (id, nombre) => {
        const ok = await confirm(`¿Eliminar la carrera "${nombre}"?`, { title: 'Eliminar carrera', confirmText: 'Eliminar', variant: 'danger' });
        if (!ok) return;
        router.delete(`/admin/categorias/${id}`);
    };

    return (
        <>
            <Head title="Admin - Carreras" />
            <div className="min-h-screen bg-cyber-dark">
                <div className="relative overflow-hidden bg-gradient-to-br from-neon-cyan/15 via-cyber-dark-100 to-cyber-dark py-8">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.1),transparent_50%)]" />
                    <div className="relative mx-auto max-w-6xl px-5 sm:px-8 lg:px-10">
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="cyber-badge cyber-badge-cyan rounded-lg px-4 py-1.5 text-sm font-bold inline-flex mb-3">
                                    <Sparkles className="h-4 w-4" /> Carreras
                                </div>
                                <h1 className="text-2xl font-heading font-black text-text-primary">Carreras</h1>
                                <p className="mt-1 text-sm font-semibold text-text-muted">{categorias?.total || 0} carreras</p>
                            </div>
                            <Link href="/admin/categorias/crear" className="cyber-btn cyber-btn-primary rounded-xl px-5 py-2.5 text-sm font-bold">+ Nueva carrera</Link>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-6xl px-5 sm:px-8 lg:px-10 mt-4 pb-8">
                    {flash?.success && (
                        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                            className="mb-4 flex items-center gap-2 rounded-xl border border-neon-green/30 bg-neon-green/5 px-5 py-3 text-sm font-bold text-neon-green">
                            <CheckCircle2 size={18} /> {flash.success}
                        </motion.div>
                    )}
                    {flash?.error && (
                        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                            className="mb-4 flex items-center gap-2 rounded-xl border border-neon-magenta/30 bg-neon-magenta/5 px-5 py-3 text-sm font-bold text-neon-cyan">
                            <AlertTriangle size={18} /> {flash.error}
                        </motion.div>
                    )}

                    <div className="cyber-card rounded-xl p-4 mb-6">
                        <div className="flex gap-3">
                            <div className="relative flex-1">
                                <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted" />
                                <input type="text" placeholder="Buscar carrera..." value={busqueda}
                                    onChange={(e) => setBusqueda(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && aplicarFiltros()}
                                    className="w-full cyber-input rounded-xl pl-9 pr-4 py-2.5 text-sm" />
                            </div>
                            <select value={filtroInstitucion} onChange={(e) => setFiltroInstitucion(e.target.value)}
                                className="cyber-input rounded-xl px-4 py-2.5 text-sm">
                                <option value="">Todas las U.</option>
                                {instituciones?.map((i) => (<option key={i.id} value={i.id}>{i.nombre}</option>))}
                            </select>
                            <button onClick={aplicarFiltros} className="cyber-btn cyber-btn-primary rounded-xl px-5 py-2.5 text-sm font-bold">Filtrar</button>
                        </div>
                    </div>

                    {categorias?.data?.length === 0 ? (
                        <motion.div initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }} className="cyber-card rounded-xl p-12 text-center">
                            <GraduationCap size={48} className="mx-auto mb-4 text-text-muted" />
                            <p className="text-lg font-heading font-bold text-text-primary">No hay carreras</p>
                            <p className="text-sm font-semibold text-text-muted">Crea la primera carrera para comenzar.</p>
                        </motion.div>
                    ) : (
                        <div className="space-y-3">
                            {categorias?.data?.map((cat, index) => (
                                <motion.div key={cat.id} initial={{ opacity: 0, y: 15 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: index * 0.05 }}
                                    className="cyber-card rounded-xl p-5 hover:border-neon-cyan/30 transition-all">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-2 mb-1">
                                                <Building2 size={14} className="text-neon-cyan" />
                                                <span className="text-xs font-bold text-neon-cyan">{cat.institucion?.nombre}</span>
                                            </div>
                                            <h3 className="text-base font-heading font-bold text-text-primary">{cat.nombre}</h3>
                                            {cat.descripcion_corta && (<p className="text-sm text-text-muted mt-1">{cat.descripcion_corta}</p>)}
                                            <p className="mt-2 text-xs font-semibold text-text-muted flex items-center gap-1"><FileText size={12} /> {cat.examenes_count || 0} exámenes</p>
                                        </div>
                                        <div className="flex items-center gap-2 flex-shrink-0">
                                            <Link href={`/admin/categorias/${cat.id}/editar`}
                                                className="inline-flex items-center gap-1 rounded-lg bg-neon-cyan/10 border border-neon-cyan/30 px-3 py-1.5 text-xs font-bold text-neon-cyan hover:bg-neon-cyan/20"><Pencil size={12} /> Editar</Link>
                                            <Link href={`/admin/examenes?categoria_id=${cat.id}`}
                                                className="inline-flex items-center gap-1 rounded-lg bg-neon-magenta/10 border border-neon-magenta/30 px-3 py-1.5 text-xs font-bold text-neon-magenta hover:bg-neon-magenta/20"><FileText size={12} /> Exámenes</Link>
                                            <button onClick={() => eliminar(cat.id, cat.nombre)}
                                                className="inline-flex items-center rounded-lg bg-neon-magenta/10 border border-neon-magenta/30 px-2.5 py-1.5 text-xs font-bold text-neon-magenta hover:bg-neon-magenta/20"><Trash2 size={14} /></button>
                                        </div>
                                    </div>
                                </motion.div>
                            ))}
                        </div>
                    )}

                    {categorias?.last_page > 1 && (
                        <div className="mt-6 flex justify-center gap-2">
                            {categorias.links?.map((link, i) => (
                                <button key={i} onClick={() => link.url && router.get(link.url)}
                                    className={`rounded-lg px-3 py-1.5 text-sm font-bold transition-all ${link.active ? 'cyber-btn cyber-btn-primary' : 'cyber-btn border-cyber-dark-400'}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }} />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
