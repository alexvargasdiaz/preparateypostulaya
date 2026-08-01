import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { motion } from 'motion/react';
import { useDialog } from '@/Components/DialogProvider';
import {
    Search,
    Building2,
    School,
    MapPin,
    Pencil,
    GraduationCap,
    Trash2,
    CheckCircle2,
    AlertTriangle,
    Sparkles,
    Plus
} from 'lucide-react';

export default function AdminInstitucionesIndex({ instituciones, filtros }) {
    const { confirm } = useDialog();
    const { flash } = usePage().props;
    const [busqueda, setBusqueda] = useState(filtros?.busqueda || '');

    const aplicarFiltros = () => {
        router.get('/admin/instituciones', { busqueda }, { preserveState: true });
    };

    const eliminar = async (id, nombre) => {
        const ok = await confirm(`¿Eliminar "${nombre}"?`, { title: 'Eliminar universidad', confirmText: 'Eliminar', variant: 'danger' });
        if (!ok) return;
        router.delete(`/admin/instituciones/${id}`);
    };

    return (
        <>
            <Head title="Admin - Universidades" />
            <div className="min-h-screen bg-cyber-dark">
                {/* Header */}
                <div className="relative overflow-hidden border-b border-cyber-dark-400/50 bg-cyber-dark-100 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.08),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10 py-8">
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="inline-flex items-center gap-2 rounded-full bg-neon-cyan/10 border border-neon-cyan/30 px-4 py-1.5 text-sm font-bold text-neon-cyan mb-3">
                                    <Sparkles className="h-4 w-4" /> Gestión de Universidades
                                </div>
                                <h1 className="text-2xl font-heading font-black text-text-primary">
                                    <span className="neon-text-cyan">Universidades</span>
                                </h1>
                                <p className="mt-1 text-sm text-text-secondary font-semibold">
                                    {instituciones?.total || 0} universidades registradas
                                </p>
                            </div>
                            <Link href="/admin/instituciones/crear"
                                className="cyber-btn cyber-btn-primary rounded-xl px-5 py-2.5 text-sm">
                                <Plus className="h-4 w-4" /> Nueva universidad
                            </Link>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-6 pb-8">
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
                    {flash?.error && (
                        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                            className="mb-6 flex items-center gap-3 rounded-xl cyber-card border-neon-magenta/30 px-5 py-4 shadow-neon-magenta/10">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-neon-magenta/10 border border-neon-magenta/30">
                                <AlertTriangle className="h-4 w-4 text-neon-magenta" />
                            </div>
                            <p className="text-sm font-bold text-neon-cyan">{flash.error}</p>
                        </motion.div>
                    )}

                    {/* Search */}
                    <div className="mb-6 rounded-xl cyber-card border-cyber-dark-400/40 p-4">
                        <div className="flex gap-3">
                            <div className="relative flex-1">
                                <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted" />
                                <input type="text" placeholder="Buscar universidad..." value={busqueda}
                                    onChange={(e) => setBusqueda(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && aplicarFiltros()}
                                    className="cyber-input w-full rounded-xl pl-9 pr-4 py-2.5 text-sm" />
                            </div>
                            <button onClick={aplicarFiltros}
                                className="cyber-btn cyber-btn-primary rounded-xl px-5 py-2.5 text-sm">
                                Filtrar
                            </button>
                        </div>
                    </div>

                    {/* Grid */}
                    {instituciones?.data?.length === 0 ? (
                        <div className="rounded-xl cyber-card border-dashed border-cyber-dark-400/50 p-12 text-center">
                            <div className="mb-4 flex justify-center">
                                <Building2 size={48} className="text-text-muted" />
                            </div>
                            <p className="text-lg font-heading font-bold text-text-muted">No hay universidades</p>
                            <p className="text-sm text-text-muted/70">Crea la primera universidad para comenzar.</p>
                        </div>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {instituciones?.data?.map((inst, index) => (
                                <motion.div
                                    key={inst.id}
                                    initial={{ opacity: 0, y: 20 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ duration: 0.3, delay: index * 0.07 }}
                                    className="rounded-xl cyber-card border-cyber-dark-400/40 hover:border-neon-cyan/30 transition-all group"
                                >
                                    {/* Top accent bar */}
                                    <div className={`h-1.5 w-full rounded-t-lg bg-gradient-to-r ${
                                        inst.subtipo === 'publica' ? 'from-neon-cyan to-neon-magenta' : 'from-neon-magenta to-neon-cyan'
                                    }`} />
                                    
                                    <div className="p-5">
                                        <div className="flex items-start gap-3">
                                            {inst.logo_url && (
                                                <img src={inst.logo_url} alt={inst.nombre}
                                                    className="h-12 w-12 rounded-xl object-cover border border-cyber-dark-400/50 flex-shrink-0" />
                                            )}
                                            <div className="flex-1 min-w-0">
                                                <h3 className="font-heading font-bold text-text-primary group-hover:text-neon-cyan transition-colors truncate">{inst.nombre}</h3>
                                                <span className={`inline-flex items-center gap-1 mt-1 rounded-full px-2.5 py-0.5 text-xs font-bold border ${
                                                    inst.subtipo === 'publica'
                                                        ? 'border-neon-cyan/30 bg-neon-cyan/10 text-neon-cyan'
                                                        : 'border-neon-magenta/30 bg-neon-magenta/10 text-neon-magenta'
                                                }`}>
                                                    {inst.subtipo === 'publica' ? <Building2 size={12} /> : <School size={12} />}
                                                    {inst.subtipo === 'publica' ? 'Pública' : 'Privada'}
                                                </span>
                                                {inst.ciudad && (
                                                    <p className="mt-1 flex items-center gap-1 text-xs text-text-muted">
                                                        <MapPin size={12} /> {inst.ciudad}
                                                    </p>
                                                )}
                                                <p className="mt-2 text-xs text-text-muted">{inst.categorias_count || 0} carreras</p>
                                            </div>
                                        </div>
                                        <div className="mt-4 flex items-center gap-2">
                                            <Link href={`/admin/instituciones/${inst.id}/editar`}
                                                className="inline-flex items-center gap-1 rounded-lg border border-neon-cyan/30 bg-neon-cyan/10 px-3 py-1.5 text-xs font-bold text-neon-cyan hover:bg-neon-cyan/20 transition-all">
                                                <Pencil size={12} /> Editar
                                            </Link>
                                            <Link href={`/admin/categorias?institucion_id=${inst.id}`}
                                                className="inline-flex items-center gap-1 rounded-lg border border-cyber-dark-400/50 bg-cyber-dark-300 px-3 py-1.5 text-xs font-bold text-text-secondary hover:text-neon-cyan hover:border-neon-cyan/30 transition-all">
                                                <GraduationCap size={12} /> Carreras
                                            </Link>
                                            <button onClick={() => eliminar(inst.id, inst.nombre)}
                                                className="inline-flex items-center rounded-lg border border-cyber-dark-400/50 bg-cyber-dark-300 px-2.5 py-1.5 text-xs font-bold text-text-muted hover:text-white hover:border-neon-magenta/30 transition-all">
                                                <Trash2 size={12} />
                                            </button>
                                        </div>
                                    </div>
                                </motion.div>
                            ))}
                        </div>
                    )}

                    {/* Pagination */}
                    {instituciones?.last_page > 1 && (
                        <div className="mt-6 flex justify-center gap-2">
                            {instituciones.links?.map((link, i) => (
                                <button key={i} onClick={() => link.url && router.get(link.url)}
                                    className={`rounded-lg px-3 py-1.5 text-sm font-bold transition-all ${
                                        link.active
                                            ? 'bg-neon-cyan/20 border border-neon-cyan/40 text-neon-cyan shadow-neon-cyan'
                                            : 'border border-cyber-dark-400/50 bg-cyber-dark-300 text-text-muted hover:text-text-primary hover:border-cyber-dark-500'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }} />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
