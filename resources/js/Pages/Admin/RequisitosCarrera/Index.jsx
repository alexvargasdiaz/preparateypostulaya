import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { motion } from 'motion/react';
import { useDialog } from '@/Components/DialogProvider';
import {
    GraduationCap, Building2, Target, Trash2, Save, CheckCircle2,
    AlertTriangle, Plus, Search, Settings, X, Trophy
} from 'lucide-react';

export default function Index({ categorias, instituciones, conceptos, filtros, flash }) {
    const { confirm } = useDialog();
    const [institucionId, setInstitucionId] = useState(filtros?.institucion_id || '');
    const [busqueda, setBusqueda] = useState(filtros?.busqueda || '');
    const [editando, setEditando] = useState(null);
    const [requisitos, setRequisitos] = useState([]);
    const [puntajeMinimo, setPuntajeMinimo] = useState('');
    const [guardando, setGuardando] = useState(false);

    const filtrar = () => {
        router.get('/admin/requisitos-carrera', { institucion_id: institucionId, busqueda }, { preserveState: true });
    };

    const abrirEditor = (categoria) => {
        setEditando(categoria);
        setPuntajeMinimo(categoria.puntaje_minimo_total ?? '');
        const existentes = categoria.requisitos.map((r) => ({
            concepto_id: r.concepto_id,
            concepto_nombre: r.concepto?.nombre || '',
            puntaje_minimo: r.puntaje_minimo,
        }));
        setRequisitos(existentes);
    };

    const cerrarEditor = () => {
        setEditando(null);
        setRequisitos([]);
        setPuntajeMinimo('');
    };

    const guardarPuntajeMinimoTotal = () => {
        if (!editando) return;
        setGuardando(true);
        router.post('/admin/requisitos-carrera/puntaje-minimo', {
            categoria_id: editando.id,
            puntaje_minimo_total: parseFloat(puntajeMinimo) || 0,
        }, {
            preserveState: true,
            onFinish: () => setGuardando(false),
        });
    };

    const agregarRequisito = () => {
        const conceptosDisponibles = conceptos.filter(
            (c) => !requisitos.find((r) => r.concepto_id === c.id)
        );
        if (conceptosDisponibles.length === 0) return;
        const primero = conceptosDisponibles[0];
        setRequisitos([...requisitos, { concepto_id: primero.id, concepto_nombre: primero.nombre, puntaje_minimo: 60 }]);
    };

    const actualizarRequisito = (index, campo, valor) => {
        const copia = [...requisitos];
        copia[index] = { ...copia[index], [campo]: valor };
        if (campo === 'concepto_id') {
            const concepto = conceptos.find((c) => c.id === parseInt(valor));
            copia[index].concepto_nombre = concepto?.nombre || '';
        }
        setRequisitos(copia);
    };

    const eliminarRequisito = (index) => {
        setRequisitos(requisitos.filter((_, i) => i !== index));
    };

    const guardarRequisitos = () => {
        if (!editando || requisitos.length === 0) return;
        setGuardando(true);

        router.post('/admin/requisitos-carrera', {
            categoria_id: editando.id,
            requisitos: requisitos.map((r) => ({
                concepto_id: r.concepto_id,
                puntaje_minimo: r.puntaje_minimo,
            })),
        }, {
            preserveState: true,
            onFinish: () => {
                setGuardando(false);
                cerrarEditor();
            },
        });
    };

    const eliminarTodos = async (categoriaId) => {
        const ok = await confirm('¿Eliminar todos los requisitos de esta carrera?', { title: 'Eliminar requisitos', confirmText: 'Eliminar todo', variant: 'danger' });
        if (!ok) return;
        router.delete(`/admin/requisitos-carrera/categoria/${categoriaId}`, { preserveState: true });
    };

    const categoriasFiltradas = categorias.filter((c) => {
        if (institucionId && c.institucion_id !== parseInt(institucionId)) return false;
        if (busqueda && !c.nombre.toLowerCase().includes(busqueda.toLowerCase())) return false;
        return true;
    });

    const conceptosDisponibles = conceptos.filter(
        (c) => !requisitos.find((r) => r.concepto_id === c.id)
    );

    return (
        <>
            <Head title="Requisitos por Carrera" />

            <div className="min-h-screen bg-[#0b0f17]">
                <div className="relative py-8 overflow-hidden" style={{ background: 'linear-gradient(135deg, rgba(0,240,255,0.15), rgba(255,0,255,0.08))' }}>
                    <div className="absolute inset-0 opacity-5" style={{ backgroundImage: 'url(https://www.transparenttextures.com/patterns/cubes.png)' }} />
                    <div className="absolute inset-0" style={{ background: 'radial-gradient(ellipse at 30% 50%, rgba(0,240,255,0.1) 0%, transparent 60%), radial-gradient(ellipse at 70% 50%, rgba(255,0,255,0.08) 0%, transparent 60%)' }} />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10">
                        <div className="flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl border border-neon-cyan/30 bg-neon-cyan/10 shadow-neon-cyan">
                                <Target className="h-6 w-6 text-neon-cyan" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold neon-text-strong-cyan">Requisitos por Carrera</h1>
                                <p className="text-sm text-neon-cyan/60">Configura los puntajes mínimos para que los estudiantes alcancen cada carrera</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-4 pb-8">
                    {flash?.success && (
                        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                            className="mb-4 flex items-center gap-2 rounded-xl border border-neon-green/30 bg-neon-green/10 px-5 py-4 text-sm font-medium text-neon-green shadow-neon-green">
                            <CheckCircle2 className="h-4 w-4" /> {flash.success}
                        </motion.div>
                    )}

                    {/* Filtros */}
                    <div className="mb-6 flex flex-wrap items-end gap-4 rounded-2xl border border-neon-cyan/20 bg-[rgba(11,15,23,0.85)] p-5 shadow-neon-cyan backdrop-blur-sm">
                        <div className="flex-1 min-w-[200px]">
                            <label className="block text-sm font-medium text-neon-cyan/70 mb-1">Institución</label>
                            <select value={institucionId} onChange={(e) => { setInstitucionId(e.target.value); }}
                                className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm">
                                <option value="" className="bg-[rgba(11,15,23,0.95)]">Todas las instituciones</option>
                                {instituciones.map((i) => (
                                    <option key={i.id} value={i.id} className="bg-[rgba(11,15,23,0.95)]">{i.nombre}</option>
                                ))}
                            </select>
                        </div>
                        <div className="flex-1 min-w-[200px]">
                            <label className="block text-sm font-medium text-neon-cyan/70 mb-1">Buscar carrera</label>
                            <div className="relative">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neon-cyan/50 drop-shadow-[0_0_4px_rgba(0,240,255,0.3)]" />
                                <input type="text" value={busqueda} onChange={(e) => setBusqueda(e.target.value)}
                                    placeholder="Nombre de la carrera..."
                                    className="cyber-input w-full rounded-xl py-2.5 pl-10 pr-4 text-sm" />
                            </div>
                        </div>
                        <button onClick={filtrar}
                            className="neubr-btn neubr-btn-red rounded-xl px-6 py-2.5 text-sm">
                            Filtrar
                        </button>
                    </div>

                    {/* Lista de carreras */}
                    <div className="space-y-3">
                        {categoriasFiltradas.length === 0 ? (
                            <div className="rounded-2xl border border-neon-cyan/10 bg-[rgba(11,15,23,0.85)] p-12 text-center shadow-neon-cyan">
                                <GraduationCap className="mx-auto h-12 w-12 text-neon-cyan/30" />
                                <p className="mt-3 text-neon-cyan/50">No se encontraron carreras con los filtros seleccionados.</p>
                            </div>
                        ) : (
                            categoriasFiltradas.map((cat) => (
                                <motion.div key={cat.id} initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }}
                                    className="rounded-2xl border border-neon-cyan/20 bg-[rgba(11,15,23,0.85)] shadow-neon-cyan overflow-hidden backdrop-blur-sm">
                                    <div className="flex items-center justify-between p-5">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-neon-cyan/10 text-neon-cyan shadow-neon-cyan">
                                                <GraduationCap className="h-5 w-5" />
                                            </div>
                                            <div>
                                                <p className="font-bold text-text-primary">{cat.nombre}</p>
                                                <p className="text-xs text-text-muted">{cat.institucion?.nombre || '—'}</p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            {cat.puntaje_minimo_total != null && (
                                                <span className="inline-flex items-center gap-1 rounded-full border border-neon-green/30 bg-neon-green/10 px-3 py-1 text-xs font-bold text-neon-green">
                                                    <Trophy className="h-3 w-3" /> ≥{cat.puntaje_minimo_total} pts
                                                </span>
                                            )}
                                            {cat.requisitos.length > 0 && (
                                                <span className="rounded-full border border-neon-cyan/30 bg-neon-cyan/10 px-3 py-1 text-xs font-bold text-neon-cyan">
                                                    {cat.requisitos.length} {cat.requisitos.length === 1 ? 'área' : 'áreas'}
                                                </span>
                                            )}
                                            {cat.requisitos.length > 0 && (
                                                <button onClick={() => eliminarTodos(cat.id)}
                                                    className="rounded-lg p-2 text-text-muted hover:bg-neon-magenta/10 hover:text-white transition-colors"
                                                    title="Eliminar todos">
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            )}
                                            <button onClick={() => abrirEditor(cat)}
                                                className="neubr-btn rounded-xl px-4 py-2 text-sm">
                                                <Settings className="h-4 w-4" />
                                                Configurar
                                            </button>
                                        </div>
                                    </div>

                                    {/* Preview */}
                                    {(cat.puntaje_minimo_total != null || cat.requisitos.length > 0) && editando?.id !== cat.id && (
                                        <div className="border-t border-neon-cyan/10 bg-neon-cyan/[0.03] px-5 py-3">
                                            <div className="flex flex-wrap gap-2">
                                                {cat.puntaje_minimo_total != null && (
                                                    <span className="inline-flex items-center gap-1 rounded-full border border-neon-green/20 bg-neon-green/10 px-3 py-1 text-xs font-semibold text-neon-green">
                                                        Mínimo total: {cat.puntaje_minimo_total} puntos
                                                    </span>
                                                )}
                                                {cat.requisitos.map((r) => (
                                                    <span key={r.id} className="inline-flex items-center gap-1 rounded-full border border-neon-cyan/20 bg-neon-cyan/5 px-3 py-1 text-xs font-medium text-neon-cyan/80">
                                                        {r.concepto?.nombre}: <span className="font-bold text-neon-magenta">≥{r.puntaje_minimo}%</span>
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </motion.div>
                            ))
                        )}
                    </div>
                </div>
            </div>

            {/* Modal editor */}
            {editando && (
                <div className="fixed inset-0 z-50 flex cursor-pointer items-center justify-center bg-black/60 backdrop-blur-sm p-4" onClick={cerrarEditor}>
                    <motion.div initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }}
                        className="w-full max-w-2xl rounded-2xl border border-neon-cyan/20 bg-[rgba(11,15,23,0.95)] shadow-neon-cyan backdrop-blur-xl" onClick={(e) => e.stopPropagation()}>
                        <div className="flex items-center justify-between border-b border-neon-cyan/10 px-6 py-4">
                            <div>
                                <h2 className="text-lg font-bold neon-text-cyan">Requisitos: {editando.nombre}</h2>
                                <p className="text-sm text-neon-cyan/50">{editando.institucion?.nombre}</p>
                            </div>
                            <button onClick={cerrarEditor} className="rounded-lg p-2 text-text-muted hover:bg-neon-cyan/10 hover:text-white">
                                <X className="h-5 w-5" />
                            </button>
                        </div>

                        <div className="px-6 py-5 max-h-[60vh] overflow-y-auto space-y-6">
                            {/* Puntaje mínimo total */}
                            <div className="rounded-xl border border-neon-cyan/20 bg-neon-cyan/5 p-5 shadow-neon-cyan">
                                <div className="flex items-center gap-2 mb-3">
                                    <Trophy className="h-5 w-5 text-neon-green" />
                                    <h3 className="text-sm font-bold neon-text-green">Puntaje mínimo total</h3>
                                </div>
                                <p className="text-xs text-neon-cyan/50 mb-3">
                                    Puntaje mínimo que un estudiante necesita para alcanzar esta carrera.
                                </p>
                                <div className="flex items-center gap-3">
                                    <div className="relative flex-1">
                                        <input type="number" min="0" max="100" step="1"
                                            value={puntajeMinimo}
                                            onChange={(e) => setPuntajeMinimo(e.target.value)}
                                            placeholder="Ej: 70"
                                            className="cyber-input w-full rounded-xl px-4 py-2.5 pr-20 text-sm font-bold" />
                                        <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm font-medium text-neon-cyan/50">puntos</span>
                                    </div>
                                    <button onClick={guardarPuntajeMinimoTotal}
                                        className="neubr-btn neubr-btn-red rounded-xl px-5 py-2.5 text-sm">
                                        <Save className="h-4 w-4 inline" />
                                    </button>
                                </div>
                            </div>

                            {/* Requisitos por área */}
                            <div>
                                <div className="flex items-center gap-2 mb-3">
                                    <Target className="h-5 w-5 text-neon-magenta" />
                                    <h3 className="text-sm font-bold neon-text-cyan">Requisitos por área (opcional)</h3>
                                </div>
                                <p className="text-xs text-neon-cyan/50 mb-3">
                                    Define mínimos por materia. Estos se muestran como información adicional en los resultados del diagnóstico.
                                </p>

                                {requisitos.length === 0 ? (
                                    <div className="rounded-xl border-2 border-dashed border-neon-cyan/20 p-8 text-center">
                                        <Target className="mx-auto h-10 w-10 text-neon-cyan/30" />
                                        <p className="mt-2 text-sm text-neon-cyan/50">Sin requisitos por área configurados.</p>
                                    </div>
                                ) : (
                                    <div className="space-y-3">
                                        {requisitos.map((req, index) => (
                                            <div key={index} className="flex items-center gap-3 rounded-xl border border-neon-cyan/15 bg-neon-cyan/[0.03] p-4">
                                                <div className="flex-1">
                                                    <select value={req.concepto_id}
                                                        onChange={(e) => actualizarRequisito(index, 'concepto_id', parseInt(e.target.value))}
                                                        className="cyber-input w-full rounded-lg px-3 py-2 text-sm">
                                                        {conceptos.map((c) => (
                                                            <option key={c.id} value={c.id} className="bg-[rgba(11,15,23,0.95)]">{c.nombre}</option>
                                                        ))}
                                                    </select>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <span className="text-sm text-neon-cyan/60">≥</span>
                                                    <input type="number" min="0" max="100" step="5"
                                                        value={req.puntaje_minimo}
                                                        onChange={(e) => actualizarRequisito(index, 'puntaje_minimo', parseFloat(e.target.value) || 0)}
                                                        className="cyber-input w-20 rounded-lg px-3 py-2 text-center text-sm font-bold" />
                                                    <span className="text-sm text-neon-cyan/60">%</span>
                                                </div>
                                                <button onClick={() => eliminarRequisito(index)}
                                                    className="rounded-lg p-2 text-text-muted hover:bg-neon-magenta/10 hover:text-white">
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                {conceptosDisponibles.length > 0 && (
                                    <button onClick={agregarRequisito}
                                        className="mt-4 inline-flex items-center gap-1.5 rounded-xl border border-dashed border-neon-cyan/30 px-4 py-2 text-sm font-medium text-neon-cyan transition-all hover:bg-neon-cyan/10">
                                        <Plus className="h-4 w-4" />
                                        Agregar área
                                    </button>
                                )}
                            </div>
                        </div>

                        <div className="flex items-center justify-end gap-3 border-t border-neon-cyan/10 px-6 py-4">
                            <button onClick={cerrarEditor}
                                className="neubr-btn rounded-xl px-5 py-2.5 text-sm">
                                Cerrar
                            </button>
                            <button onClick={guardarRequisitos} disabled={guardando || requisitos.length === 0}
                                className="neubr-btn neubr-btn-red rounded-xl px-6 py-2.5 text-sm disabled:opacity-40 disabled:cursor-not-allowed">
                                <Save className="h-4 w-4" />
                                {guardando ? 'Guardando...' : 'Guardar áreas'}
                            </button>
                        </div>
                    </motion.div>
                </div>
            )}
        </>
    );
}
