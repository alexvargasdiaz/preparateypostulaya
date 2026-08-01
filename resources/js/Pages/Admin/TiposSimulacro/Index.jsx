import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { useDialog } from '@/Components/DialogProvider';
import {
    Layers, Plus, Save, Trash2, Edit3, Clock, HelpCircle,
    CheckCircle2, AlertTriangle, X, ArrowLeft, List, Sparkles
} from 'lucide-react';

export default function TiposSimulacro({ area, tipos }) {
    const { confirm } = useDialog();
    const { flash } = usePage().props;
    const [editingId, setEditingId] = useState(null);
    const [showCreate, setShowCreate] = useState(false);

    const createForm = useForm({
        area_academica_id: area.id,
        nombre: '',
        descripcion: '',
        num_preguntas: 100,
        duracion_min: 120,
    });

    const editForm = useForm({
        nombre: '',
        descripcion: '',
        num_preguntas: 100,
        duracion_min: 120,
        activo: true,
    });

    const handleCreate = (e) => {
        e.preventDefault();
        createForm.post('/admin/tipos-simulacro', {
            onSuccess: () => {
                setShowCreate(false);
                createForm.reset();
            },
        });
    };

    const startEdit = (tipo) => {
        setEditingId(tipo.id);
        editForm.setData({
            nombre: tipo.nombre,
            descripcion: tipo.descripcion || '',
            num_preguntas: tipo.num_preguntas,
            duracion_min: tipo.duracion_min,
            activo: tipo.activo,
        });
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        editForm.put(`/admin/tipos-simulacro/${editingId}`, {
            onSuccess: () => setEditingId(null),
        });
    };

    const handleDelete = async (id) => {
        const ok = await confirm('¿Eliminar este tipo de simulacro?', { title: 'Eliminar tipo', confirmText: 'Eliminar', variant: 'danger' });
        if (ok) router.delete(`/admin/tipos-simulacro/${id}`);
    };

    return (
        <>
            <Head title={`Tipos de Simulacro - ${area.nombre}`} />

            <div className="min-h-screen bg-cyber-dark">
                {/* Header */}
                <div className="relative overflow-hidden border-b border-cyber-dark-400/50 bg-cyber-dark-100 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.08),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10 py-8">
                        <div className="flex items-center justify-between">
                            <div>
                                <button onClick={() => router.get('/admin/areas-academicas')}
                                    className="flex items-center gap-1.5 text-xs font-semibold text-text-muted hover:text-white transition-colors mb-3">
                                    <ArrowLeft className="h-3.5 w-3.5" /> Volver a Áreas
                                </button>
                                <div className="inline-flex items-center gap-2 rounded-full bg-neon-cyan/10 border border-neon-cyan/30 px-4 py-1.5 text-sm font-bold text-neon-cyan mb-3">
                                    <Sparkles className="h-4 w-4" /> Tipos de Simulacro
                                </div>
                                <h1 className="text-2xl font-heading font-black text-text-primary">
                                    {area.nombre}
                                </h1>
                                <p className="mt-1 text-sm text-text-secondary font-semibold">
                                    Configura los tipos de simulacro disponibles para esta área
                                </p>
                            </div>
                            <button
                                onClick={() => setShowCreate(true)}
                                className="cyber-btn cyber-btn-primary rounded-xl px-5 py-2.5 text-sm"
                            >
                                <Plus className="h-4 w-4" /> Nuevo tipo
                            </button>
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

                    {/* Create form */}
                    <AnimatePresence>
                        {showCreate && (
                            <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: 'auto' }} exit={{ opacity: 0, height: 0 }}
                                className="mb-6 overflow-hidden rounded-xl cyber-card border-neon-cyan/20 shadow-neon-cyan/10">
                                <div className="flex items-center justify-between border-b border-cyber-dark-400/30 px-6 py-4 bg-cyber-dark-200/50">
                                    <div className="flex items-center gap-2">
                                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-neon-magenta/10 border border-neon-magenta/30">
                                            <Plus className="h-4 w-4 text-neon-magenta" />
                                        </div>
                                        <h3 className="font-heading font-bold text-text-primary">Nuevo Tipo de Simulacro</h3>
                                    </div>
                                    <button onClick={() => setShowCreate(false)}
                                        className="flex h-8 w-8 items-center justify-center rounded-lg text-text-muted hover:text-white hover:bg-neon-cyan/10 transition-all">
                                        <X className="h-4 w-4" />
                                    </button>
                                </div>
                                <form onSubmit={handleCreate} className="p-6">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="sm:col-span-2">
                                            <label className="block text-sm font-semibold text-text-secondary mb-1.5">Nombre</label>
                                            <input type="text" value={createForm.data.nombre} onChange={(e) => createForm.setData('nombre', e.target.value)}
                                                className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm"
                                                placeholder="Ej: Simulacro UNI - Ciencias Básicas" required />
                                            {createForm.errors?.nombre && <p className="mt-1 text-xs text-neon-cyan/80/70">{createForm.errors.nombre}</p>}
                                        </div>
                                        <div className="sm:col-span-2">
                                            <label className="block text-sm font-semibold text-text-secondary mb-1.5">Descripción</label>
                                            <textarea value={createForm.data.descripcion} onChange={(e) => createForm.setData('descripcion', e.target.value)} rows={2}
                                                className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm"
                                                placeholder="Descripción del tipo de simulacro..." />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-semibold text-text-secondary mb-1.5">
                                                <HelpCircle className="inline h-3.5 w-3.5 mr-1 text-neon-cyan" /> Preguntas
                                            </label>
                                            <input type="number" value={createForm.data.num_preguntas} onChange={(e) => createForm.setData('num_preguntas', parseInt(e.target.value) || 100)}
                                                min="10" max="200"
                                                className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm" />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-semibold text-text-secondary mb-1.5">
                                                <Clock className="inline h-3.5 w-3.5 mr-1 text-neon-magenta" /> Duración (minutos)
                                            </label>
                                            <input type="number" value={createForm.data.duracion_min} onChange={(e) => createForm.setData('duracion_min', parseInt(e.target.value) || 120)}
                                                min="30" max="300"
                                                className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm" />
                                        </div>
                                    </div>
                                    <div className="mt-6 flex justify-end gap-3">
                                        <button type="button" onClick={() => setShowCreate(false)}
                                            className="cyber-btn rounded-xl px-4 py-2 text-sm">Cancelar</button>
                                        <button type="submit" disabled={createForm.processing}
                                            className="cyber-btn cyber-btn-primary rounded-xl px-5 py-2 text-sm disabled:opacity-40">
                                            <Save className="h-4 w-4" /> {createForm.processing ? 'Guardando...' : 'Crear tipo'}
                                        </button>
                                    </div>
                                </form>
                            </motion.div>
                        )}
                    </AnimatePresence>

                    {/* Tipos grid */}
                    {tipos.length === 0 ? (
                        <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }}
                            className="rounded-xl cyber-card border-dashed border-cyber-dark-400/50 p-12 text-center">
                            <List className="mx-auto h-12 w-12 text-text-muted" />
                            <p className="mt-3 text-sm font-bold text-text-muted">No hay tipos de simulacro configurados</p>
                            <p className="mt-1 text-xs text-text-muted/70">Crea el primer tipo para esta área</p>
                        </motion.div>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2">
                            {tipos.map((tipo, idx) => (
                                <motion.div key={tipo.id}
                                    initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }}
                                    transition={{ delay: idx * 0.05 }}
                                    className="rounded-xl cyber-card border-cyber-dark-400/40 overflow-hidden">
                                    {/* Header */}
                                    <div className="relative overflow-hidden bg-gradient-to-r from-neon-magenta/20 to-neon-cyan/10 border-b border-cyber-dark-400/30 px-5 py-4">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-cyber-dark-300/50 border border-neon-magenta/30">
                                                    <List className="h-5 w-5 text-neon-magenta" />
                                                </div>
                                                <h3 className="font-heading font-bold text-text-primary">{tipo.nombre}</h3>
                                            </div>
                                            <span className={`rounded-full px-2.5 py-0.5 text-[10px] font-heading font-bold border ${
                                                tipo.activo
                                                    ? 'border-neon-green/30 bg-neon-green/10 text-neon-green'
                                                    : 'border-cyber-dark-400 bg-cyber-dark-300 text-text-muted'
                                            }`}>
                                                {tipo.activo ? 'Activo' : 'Inactivo'}
                                            </span>
                                        </div>
                                    </div>

                                    {editingId === tipo.id ? (
                                        <form onSubmit={handleUpdate} className="p-5 space-y-3">
                                            <div>
                                                <label className="block text-xs font-semibold text-text-secondary mb-1">Nombre</label>
                                                <input type="text" value={editForm.data.nombre} onChange={(e) => editForm.setData('nombre', e.target.value)}
                                                    className="cyber-input w-full rounded-xl px-3 py-2 text-sm" required />
                                            </div>
                                            <div>
                                                <label className="block text-xs font-semibold text-text-secondary mb-1">Descripción</label>
                                                <textarea value={editForm.data.descripcion} onChange={(e) => editForm.setData('descripcion', e.target.value)} rows={2}
                                                    className="cyber-input w-full rounded-xl px-3 py-2 text-sm" />
                                            </div>
                                            <div className="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label className="block text-xs font-semibold text-text-secondary mb-1">Preguntas</label>
                                                    <input type="number" value={editForm.data.num_preguntas} onChange={(e) => editForm.setData('num_preguntas', parseInt(e.target.value) || 100)}
                                                        min="10" max="200" className="cyber-input w-full rounded-xl px-3 py-2 text-sm" />
                                                </div>
                                                <div>
                                                    <label className="block text-xs font-semibold text-text-secondary mb-1">Duración (min)</label>
                                                    <input type="number" value={editForm.data.duracion_min} onChange={(e) => editForm.setData('duracion_min', parseInt(e.target.value) || 120)}
                                                        min="30" max="300" className="cyber-input w-full rounded-xl px-3 py-2 text-sm" />
                                                </div>
                                            </div>
                                            <label className="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" checked={editForm.data.activo} onChange={(e) => editForm.setData('activo', e.target.checked)}
                                                    className="h-4 w-4 rounded border-cyber-dark-400/50 text-neon-magenta bg-cyber-dark-300" />
                                                <span className="text-sm text-text-secondary">Activo</span>
                                            </label>
                                            <div className="flex justify-end gap-2 pt-2">
                                                <button type="button" onClick={() => setEditingId(null)}
                                                    className="cyber-btn rounded-xl px-3 py-1.5 text-xs">Cancelar</button>
                                                <button type="submit" disabled={editForm.processing}
                                                    className="cyber-btn cyber-btn-primary rounded-xl px-4 py-1.5 text-xs disabled:opacity-40">
                                                    <Save className="h-3.5 w-3.5" /> Guardar
                                                </button>
                                            </div>
                                        </form>
                                    ) : (
                                        <div className="p-5">
                                            {tipo.descripcion && <p className="text-sm text-text-secondary mb-4">{tipo.descripcion}</p>}
                                            <div className="grid grid-cols-3 gap-3 mb-4">
                                                <div className="rounded-xl bg-cyber-dark-300/50 border border-cyber-dark-400/30 p-3 text-center">
                                                    <p className="text-lg font-heading font-black text-neon-cyan neon-text">{tipo.num_preguntas ?? 0}</p>
                                                    <p className="text-[10px] text-text-muted font-bold uppercase tracking-wider">Preguntas</p>
                                                </div>
                                                <div className="rounded-xl bg-cyber-dark-300/50 border border-cyber-dark-400/30 p-3 text-center">
                                                    <p className="text-lg font-heading font-black text-neon-cyan neon-text">{tipo.num_preguntas}</p>
                                                    <p className="text-[10px] text-text-muted font-bold uppercase tracking-wider">Por examen</p>
                                                </div>
                                                <div className="rounded-xl bg-cyber-dark-300/50 border border-cyber-dark-400/30 p-3 text-center">
                                                    <p className="text-lg font-heading font-black text-neon-magenta neon-text">{tipo.duracion_min}min</p>
                                                    <p className="text-[10px] text-text-muted font-bold uppercase tracking-wider">Duración</p>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <button onClick={() => startEdit(tipo)}
                                                    className="inline-flex items-center gap-1.5 rounded-lg border border-cyber-dark-400/50 bg-cyber-dark-300 px-3 py-1.5 text-xs font-bold text-text-secondary hover:text-neon-cyan hover:border-neon-cyan/30 transition-all">
                                                    <Edit3 className="h-3.5 w-3.5" /> Editar
                                                </button>
                                                <button onClick={() => handleDelete(tipo.id)}
                                                    className="inline-flex items-center gap-1.5 rounded-lg border border-cyber-dark-400/50 bg-cyber-dark-300 px-3 py-1.5 text-xs font-bold text-text-muted hover:text-white hover:border-neon-magenta/30 transition-all">
                                                    <Trash2 className="h-3.5 w-3.5" /> Eliminar
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </motion.div>
                            ))}
                        </div>
                    )}
                </div>
                <div className="h-12" />
            </div>
        </>
    );
}
