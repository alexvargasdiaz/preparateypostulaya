import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { motion } from 'motion/react';
import {
    Package, Search, Filter, Layers, BookOpen, CheckCircle2,
    Edit3, Save, X, Sparkles, Upload, Plus
} from 'lucide-react';

export default function BaulPreguntas({ preguntas, areas, conceptos, filtros }) {
    const { flash } = usePage().props;
    const [busqueda, setBusqueda] = useState(filtros.busqueda || '');
    const [areaFilter, setAreaFilter] = useState(filtros.area_academica_id || '');
    const [dificultadFilter, setDificultadFilter] = useState(filtros.dificultad || '');
    const [editingPregunta, setEditingPregunta] = useState(null);
    const [editArea, setEditArea] = useState('');
    const [selectedIds, setSelectedIds] = useState([]);
    const [bulkArea, setBulkArea] = useState('');

    const applyFilters = () => {
        router.get('/admin/baul-preguntas', {
            busqueda: busqueda || undefined, area_academica_id: areaFilter || undefined,
            dificultad: dificultadFilter || undefined,
        }, { preserveState: true, replace: true });
    };

    const clearFilters = () => {
        setBusqueda(''); setAreaFilter(''); setDificultadFilter('');
        router.get('/admin/baul-preguntas', {}, { preserveState: true, replace: true });
    };

    const handleQuickEdit = (pregunta) => {
        setEditingPregunta(pregunta.id);
        setEditArea(pregunta.area_academica_id || '');
    };

    const saveQuickEdit = (id) => {
        fetch('/admin/baul-preguntas/actualizar-area', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ pregunta_id: id, area_academica_id: editArea || null }),
        }).then(() => { setEditingPregunta(null); router.reload({ preserveState: true }); });
    };

    const toggleSelect = (id) => {
        setSelectedIds((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]);
    };

    const selectAll = () => {
        if (selectedIds.length === preguntas.data.length) setSelectedIds([]);
        else setSelectedIds(preguntas.data.map((p) => p.id));
    };

    const applyBulk = () => {
        if (selectedIds.length === 0 || !bulkArea) return;
        fetch('/admin/baul-preguntas/actualizar-masivo', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ preguntas_ids: selectedIds, area_academica_id: bulkArea || null }),
        }).then(() => { setSelectedIds([]); setBulkArea(''); router.reload({ preserveState: true }); });
    };

    const dificultadLabels = { facil: 'Fácil', media: 'Media', dificil: 'Difícil' };
    const dificultadColors = {
        facil: 'bg-neon-green/10 text-neon-green border-neon-green/30',
        media: 'bg-neon-yellow/10 text-neon-yellow border-neon-yellow/30',
        dificil: 'bg-neon-magenta/10 text-neon-magenta border-neon-magenta/30',
    };

    return (
        <>
            <Head title="Baúl de Preguntas" />
            <div className="min-h-screen bg-cyber-dark">
                <div className="relative overflow-hidden bg-gradient-to-br from-neon-cyan/15 via-cyber-dark-100 to-cyber-dark py-8 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.1),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10">
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="cyber-badge cyber-badge-magenta rounded-lg px-4 py-1.5 text-sm font-bold inline-flex mb-3">
                                    <Package className="h-4 w-4" /> Banco de Preguntas
                                </div>
                                <h1 className="text-2xl font-heading font-black text-text-primary">Baúl de Preguntas</h1>
                                <p className="mt-1 text-sm font-semibold text-text-muted">Gestiona el banco de preguntas por área académica y dificultad</p>
                            </div>
                            <div className="flex items-center gap-2">
                                <button onClick={() => router.get('/admin/preguntas/importar')}
                                    className="cyber-btn rounded-xl px-4 py-2 text-sm font-bold border-cyber-dark-400">
                                    <Upload className="h-4 w-4" /> Importar CSV
                                </button>
                                <button onClick={() => router.get('/admin/preguntas/create')}
                                    className="cyber-btn cyber-btn-primary rounded-xl px-4 py-2 text-sm font-bold">
                                    <Plus className="h-4 w-4" /> Nueva pregunta
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-4 pb-8">
                    {flash?.success && (
                        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                            className="mb-4 flex items-center gap-2 rounded-xl border border-neon-green/30 bg-neon-green/5 px-5 py-3 text-sm font-bold text-neon-green">
                            <CheckCircle2 className="h-4 w-4" /> {flash.success}
                        </motion.div>
                    )}

                    {/* Area stats */}
                    <div className="grid gap-3 mb-5 sm:grid-cols-2 lg:grid-cols-4">
                        {areas.map((area, idx) => (
                            <div key={area.id}
                                className={`cyber-card rounded-xl p-4 cursor-pointer transition-all hover:-translate-y-0.5 hover:shadow-neon-cyan/20 ${
                                    areaFilter == area.id ? 'border-neon-cyan shadow-[0_0_12px_rgba(0,240,255,0.2)]' : ''
                                }`}
                                onClick={() => { setAreaFilter(areaFilter == area.id ? '' : String(area.id)); }}
                            >
                                <p className="text-sm font-heading font-bold text-text-primary truncate">{area.nombre}</p>
                                <div className="mt-2 flex items-center gap-3 text-xs font-bold text-text-muted">
                                    <span className="text-neon-cyan">{area.total_preguntas} total</span>
                                    <span className="text-neon-green">Fácil:{area.preguntas_facil}</span>
                                    <span className="text-neon-yellow">Media:{area.preguntas_media}</span>
                                    <span className="text-neon-magenta">Difícil:{area.preguntas_dificil}</span>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Filters */}
                    <div className="cyber-card rounded-xl p-4 mb-4">
                        <div className="flex flex-wrap items-end gap-3">
                            <div className="flex-1 min-w-[200px]">
                                <label className="block text-xs font-bold text-text-muted mb-1">Buscar</label>
                                <div className="relative">
                                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted" />
                                    <input type="text" value={busqueda} onChange={(e) => setBusqueda(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                        placeholder="Buscar enunciado..."
                                        className="w-full cyber-input rounded-xl py-2 pl-10 pr-4 text-sm" />
                                </div>
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-text-muted mb-1">Área</label>
                                <select value={areaFilter} onChange={(e) => setAreaFilter(e.target.value)}
                                    className="cyber-input rounded-xl px-3 py-2 text-sm">
                                    <option value="">Todas</option>
                                    {areas.map((a) => <option key={a.id} value={a.id}>{a.nombre.substring(0, 40)}</option>)}
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-text-muted mb-1">Dificultad</label>
                                <select value={dificultadFilter} onChange={(e) => setDificultadFilter(e.target.value)}
                                    className="cyber-input rounded-xl px-3 py-2 text-sm">
                                    <option value="">Todas</option>
                                    <option value="facil">Fácil</option>
                                    <option value="media">Media</option>
                                    <option value="dificil">Difícil</option>
                                </select>
                            </div>
                            <div className="flex gap-2">
                                <button onClick={applyFilters} className="cyber-btn cyber-btn-primary rounded-xl px-4 py-2 text-sm font-bold">
                                    <Filter className="h-4 w-4" /> Filtrar
                                </button>
                                <button onClick={clearFilters} className="cyber-btn rounded-xl px-3 py-2 text-sm font-bold border-cyber-dark-400">Limpiar</button>
                            </div>
                        </div>
                    </div>

                    {/* Bulk actions */}
                    {selectedIds.length > 0 && (
                        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                            className="mb-4 flex flex-wrap items-center gap-3 rounded-xl border border-neon-cyan/30 bg-neon-cyan/5 p-4">
                            <span className="text-sm font-bold text-neon-cyan">{selectedIds.length} seleccionadas</span>
                            <select value={bulkArea} onChange={(e) => setBulkArea(e.target.value)} className="cyber-input rounded-xl px-3 py-1.5 text-sm">
                                <option value="">Sin cambios</option>
                                {areas.map((a) => <option key={a.id} value={a.id}>{a.nombre}</option>)}
                            </select>
                            <button onClick={applyBulk} className="cyber-btn cyber-btn-primary rounded-xl px-4 py-1.5 text-sm font-bold">
                                <Save className="h-4 w-4" /> Aplicar
                            </button>
                            <button onClick={() => setSelectedIds([])} className="text-sm text-text-muted hover:text-white"><X className="h-4 w-4" /></button>
                        </motion.div>
                    )}

                    {/* Questions list */}
                    <div className="cyber-card rounded-xl overflow-hidden">
                        <div className="flex items-center justify-between border-b border-cyber-dark-400/30 px-5 py-3 bg-cyber-dark-200">
                            <div className="flex items-center gap-3">
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" checked={selectedIds.length === preguntas.data.length && preguntas.data.length > 0}
                                        onChange={selectAll}
                                        className="checkbox-neon" />
                                    <span className="text-sm font-bold text-text-secondary">Todas</span>
                                </label>
                            </div>
                            <span className="text-xs font-semibold text-text-muted">{preguntas.total} preguntas</span>
                        </div>

                        {preguntas.data.length === 0 ? (
                            <div className="px-5 py-16 text-center">
                                <Package className="mx-auto h-12 w-12 text-text-muted" />
                                <p className="mt-3 font-semibold text-text-muted">No se encontraron preguntas con estos filtros.</p>
                            </div>
                        ) : (
                            <div className="divide-y divide-cyber-dark-400/20">
                                {preguntas.data.map((p) => (
                                    <div key={p.id} className="group px-5 py-3 hover:bg-cyber-dark-300/30 transition-all">
                                        <div className="flex items-start gap-3">
                                            <input type="checkbox" checked={selectedIds.includes(p.id)} onChange={() => toggleSelect(p.id)}
                                                className="checkbox-neon mt-1" />

                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm text-text-primary line-clamp-2">{p.enunciado}</p>
                                                <div className="mt-1.5 flex flex-wrap items-center gap-2">
                                                    <span className={`inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-bold border ${
                                                        p.area_academica_id ? 'bg-neon-cyan/10 text-neon-cyan border-neon-cyan/30' : 'bg-cyber-dark-300 text-text-muted border-cyber-dark-400/30'
                                                    }`}>
                                                        <Layers className="h-3 w-3 mr-1" /> {p.area_academica?.nombre?.substring(0, 30) || 'Sin área'}
                                                    </span>
                                                    <span className={`inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-bold border ${
                                                        dificultadColors[p.dificultad] || 'bg-cyber-dark-300 text-text-muted border-cyber-dark-400/30'
                                                    }`}>
                                                        {dificultadLabels[p.dificultad] || p.dificultad}
                                                    </span>
                                                    <span className={`inline-flex items-center rounded-lg px-2 py-0.5 text-[10px] font-bold border ${
                                                        p.activa ? 'bg-neon-green/10 text-neon-green border-neon-green/30' : 'bg-neon-magenta/10 text-neon-magenta border-neon-magenta/30'
                                                    }`}>
                                                        <BookOpen className="h-3 w-3 mr-1" /> {p.activa ? 'Activa' : 'Inactiva'}
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="flex-shrink-0">
                                                {editingPregunta === p.id ? (
                                                    <div className="flex items-center gap-2">
                                                        <select value={editArea} onChange={(e) => setEditArea(e.target.value)} className="cyber-input rounded-lg px-2 py-1 text-xs">
                                                            <option value="">Sin área</option>
                                                            {areas.map((a) => <option key={a.id} value={a.id}>{a.nombre.substring(0, 30)}</option>)}
                                                        </select>
                                                        <button onClick={() => saveQuickEdit(p.id)} className="rounded-lg bg-neon-cyan p-1 text-white hover:bg-neon-cyan/80"><Save className="h-3.5 w-3.5" /></button>
                                                        <button onClick={() => setEditingPregunta(null)} className="rounded-lg border border-cyber-dark-400 p-1 text-text-muted hover:bg-cyber-dark-300"><X className="h-3.5 w-3.5" /></button>
                                                    </div>
                                                ) : (
                                                    <div className="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-all">
                                                        <button onClick={() => router.get(`/admin/preguntas/${p.id}/edit`)}
                                                            className="inline-flex items-center gap-1 rounded-lg border border-neon-cyan/30 bg-neon-cyan/10 px-2 py-1 text-xs font-bold text-neon-cyan hover:bg-neon-cyan/20 transition-all">
                                                            <Edit3 className="h-3 w-3" /> Editar
                                                        </button>
                                                        <button onClick={() => handleQuickEdit(p)}
                                                            className="inline-flex items-center gap-1 rounded-lg border border-cyber-dark-400/50 bg-cyber-dark-300 px-2 py-1 text-xs font-bold text-text-muted hover:text-white transition-all">
                                                            <Layers className="h-3 w-3" /> Área
                                                        </button>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {preguntas.last_page > 1 && (
                            <div className="flex items-center justify-between border-t border-cyber-dark-400/30 px-5 py-3 bg-cyber-dark-200">
                                <p className="text-xs font-semibold text-text-muted">Página {preguntas.current_page} de {preguntas.last_page}</p>
                                <div className="flex gap-2">
                                    {preguntas.prev_page_url && (
                                        <a href={preguntas.prev_page_url} className="cyber-btn rounded-lg px-3 py-1.5 text-xs font-bold border-cyber-dark-400">← Anterior</a>
                                    )}
                                    {preguntas.next_page_url && (
                                        <a href={preguntas.next_page_url} className="cyber-btn rounded-lg px-3 py-1.5 text-xs font-bold border-cyber-dark-400">Siguiente →</a>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
