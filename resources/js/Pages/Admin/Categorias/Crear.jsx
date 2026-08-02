import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Building2, CheckCircle2, ArrowLeft, GraduationCap, Sparkles } from 'lucide-react';
import { motion } from 'motion/react';

export default function AdminCategoriasCrear({ instituciones, areas, categoria, editando }) {
    const { errors, flash } = usePage().props;
    const [institucionId, setInstitucionId] = useState(categoria?.institucion_id || '');
    const [areaId, setAreaId] = useState(categoria?.area_academica_id || '');
    const [nombre, setNombre] = useState(categoria?.nombre || '');
    const [descripcion, setDescripcion] = useState(categoria?.descripcion_corta || '');

    const handleSubmit = (e) => {
        e.preventDefault();
        const data = {
            institucion_id: institucionId,
            area_academica_id: areaId || null,
            nombre,
            descripcion_corta: descripcion || null,
        };

        if (editando) {
            router.put(`/admin/categorias/${categoria.id}`, data);
        } else {
            router.post('/admin/categorias', data);
        }
    };

    const institucionSel = instituciones?.find((i) => i.id === parseInt(institucionId));

    return (
        <>
            <Head title={editando ? 'Editar Carrera' : 'Nueva Carrera'} />
            <div className="min-h-screen bg-cyber-dark">
                {/* Header */}
                <div className="relative overflow-hidden border-b border-cyber-dark-400/50 bg-cyber-dark-100 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.08),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10 py-8">
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="inline-flex items-center gap-2 rounded-full bg-neon-magenta/10 border border-neon-magenta/30 px-4 py-1.5 text-sm font-bold text-neon-cyan mb-3">
                                    <Sparkles className="h-4 w-4" /> {editando ? 'Editar' : 'Nueva'} Carrera
                                </div>
                                <h1 className="text-2xl font-heading font-black text-text-primary">
                                    {editando ? 'Editar ' : 'Nueva '}<span className="neon-text-cyan">Carrera</span>
                                </h1>
                                <p className="mt-1 text-sm text-text-secondary font-semibold">Registra una carrera dentro de una universidad</p>
                            </div>
                            <Link href="/admin/categorias" className="cyber-btn rounded-xl px-4 py-2 text-sm">
                                <ArrowLeft className="h-4 w-4" /> Volver
                            </Link>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-6 pb-8">
                    <form onSubmit={handleSubmit} className="space-y-5">
                        {flash?.success && (
                            <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                                className="flex items-center gap-3 rounded-xl cyber-card border-neon-green/30 px-5 py-4 shadow-neon-green/10">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-neon-green/10 border border-neon-green/30">
                                    <CheckCircle2 className="h-4 w-4 text-neon-green" />
                                </div>
                                <p className="text-sm font-bold text-neon-green">{flash.success}</p>
                            </motion.div>
                        )}

                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.35 }}
                            className="rounded-xl cyber-card border-neon-cyan/20 shadow-neon-cyan/10 p-6">
                            <h2 className="text-lg font-heading font-bold text-text-primary mb-5">
                                <GraduationCap className="inline h-5 w-5 mr-2 text-neon-cyan" />
                                Datos de la carrera
                            </h2>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-semibold text-text-secondary mb-1.5">Universidad</label>
                                    <select value={institucionId} onChange={(e) => setInstitucionId(e.target.value)}
                                        className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm" required>
                                        <option value="" className="bg-cyber-dark">Seleccionar universidad</option>
                                        {instituciones?.map((i) => (
                                            <option key={i.id} value={i.id} className="bg-cyber-dark">{i.nombre}</option>
                                        ))}
                                    </select>
                                    {errors?.institucion_id && <p className="mt-1 text-xs text-neon-cyan/80/70">{errors.institucion_id}</p>}
                                </div>

                                {institucionSel && (
                                    <div className="flex items-center gap-2 rounded-lg bg-neon-cyan/10 border border-neon-cyan/30 p-3 text-xs font-semibold text-neon-cyan">
                                        <Building2 size={14} /> {institucionSel.nombre}
                                    </div>
                                )}

                                <div>
                                    <label className="block text-sm font-semibold text-text-secondary mb-1.5">Área Académica</label>
                                    <select value={areaId} onChange={(e) => setAreaId(e.target.value)}
                                        className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm">
                                        <option value="" className="bg-cyber-dark">Seleccionar área académica</option>
                                        {areas?.map((a) => (
                                            <option key={a.id} value={a.id} className="bg-cyber-dark">{a.nombre}</option>
                                        ))}
                                    </select>
                                    {errors?.area_academica_id && <p className="mt-1 text-xs text-neon-cyan/80/70">{errors.area_academica_id}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-text-secondary mb-1.5">Nombre de la carrera</label>
                                    <input type="text" value={nombre} onChange={(e) => setNombre(e.target.value)}
                                        className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm"
                                        placeholder="Ej: Ingeniería de Sistemas, Medicina, Derecho..." required />
                                    {errors?.nombre && <p className="mt-1 text-xs text-neon-cyan/80/70">{errors.nombre}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-text-secondary mb-1.5">Descripción (opcional)</label>
                                    <textarea value={descripcion} onChange={(e) => setDescripcion(e.target.value)}
                                        className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm" rows={2}
                                        placeholder="Breve descripción de la carrera" />
                                </div>
                            </div>
                        </motion.div>

                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.35, delay: 0.1 }}
                            className="flex items-center justify-between gap-4">
                            <Link href="/admin/categorias" className="cyber-btn rounded-xl px-6 py-2.5 text-sm">Cancelar</Link>
                            <button type="submit" className="cyber-btn cyber-btn-primary rounded-xl px-8 py-2.5 text-sm">
                                {editando ? 'Guardar cambios' : 'Crear carrera'}
                            </button>
                        </motion.div>
                    </form>
                </div>
            </div>
        </>
    );
}
