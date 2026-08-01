import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState, useRef, useMemo } from 'react';
import { motion } from 'motion/react';
import { useDialog } from '@/Components/DialogProvider';
import { Image, Upload, Loader2, CheckCircle, ArrowLeft, Sparkles } from 'lucide-react';

export default function AdminPreguntasCrear({ areas, conceptos, pregunta, editando }) {
    const { showAlert } = useDialog();
    const { errors, flash } = usePage().props;

    const [areaId, setAreaId] = useState(pregunta?.area_academica_id || '');
    const [conceptoId, setConceptoId] = useState(pregunta?.concepto_id || '');
    const [nivel, setNivel] = useState(pregunta?.nivel || '');
    const [enunciado, setEnunciado] = useState(pregunta?.enunciado || '');
    const [enunciadoImagen, setEnunciadoImagen] = useState(pregunta?.enunciado_imagen_url || '');
    const [subiendoEnunciado, setSubiendoEnunciado] = useState(false);
    const [dificultad, setDificultad] = useState(pregunta?.dificultad || 'media');
    const [alternativas, setAlternativas] = useState(
        pregunta?.alternativas?.map((a) => ({ id: a.id || null, texto: a.texto || '', imagen_url: a.imagen_url || '', es_correcta: a.es_correcta || false, orden: a.orden || 0 })) ||
        Array.from({ length: 5 }, (_, i) => ({ id: null, texto: '', imagen_url: '', es_correcta: false, orden: i }))
    );
    const [subiendoAlt, setSubiendoAlt] = useState({});
    const fileInputRef = useRef(null);
    const altFileInputs = useRef({});

    const conceptosFiltrados = useMemo(() => {
        if (!areaId) return [];
        return (conceptos || []).filter((c) => c.area_academica_id === parseInt(areaId));
    }, [areaId, conceptos]);

    const subirImagen = async (file, tipo) => {
        const formData = new FormData();
        formData.append('imagen', file); formData.append('tipo', tipo);
        const token = document.querySelector('meta[name=csrf-token]')?.content;
        try {
            const res = await fetch('/admin/preguntas/subir-imagen', { method: 'POST', headers: { 'X-CSRF-TOKEN': token }, body: formData });
            const data = await res.json();
            if (data.success) return data.url;
            await showAlert('Error al subir imagen: ' + (data.error || 'Desconocido'), { title: 'Error' });
            return null;
        } catch {
            await showAlert('Error de conexión.', { title: 'Error' });
            return null;
        }
    };

    const handleUploadEnunciado = async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        setSubiendoEnunciado(true);
        const url = await subirImagen(file, 'enunciado');
        if (url) setEnunciadoImagen(url);
        setSubiendoEnunciado(false);
        e.target.value = '';
    };

    const handleUploadAlt = async (e, index) => {
        const file = e.target.files?.[0];
        if (!file) return;
        setSubiendoAlt((prev) => ({ ...prev, [index]: true }));
        const url = await subirImagen(file, 'alternativa');
        if (url) setAlternativas(alternativas.map((a, i) => (i === index ? { ...a, imagen_url: url } : a)));
        setSubiendoAlt((prev) => ({ ...prev, [index]: false }));
        e.target.value = '';
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (alternativas.filter((a) => a.es_correcta).length !== 1) {
            showAlert('Debe seleccionar exactamente una alternativa como correcta.', { title: 'Validación' }); return;
        }
        if (alternativas.some((a) => !a.texto?.trim())) {
            showAlert('Todas las alternativas deben tener texto.', { title: 'Validación' }); return;
        }
        const data = {
            area_academica_id: areaId, concepto_id: conceptoId, nivel: nivel || null, enunciado, dificultad, tipo: 'opcion_multiple',
            enunciado_imagen_url: enunciadoImagen || null,
            alternativas: alternativas.map((a) => ({ id: a.id, texto: a.texto, imagen_url: a.imagen_url || null, es_correcta: a.es_correcta, orden: a.orden })),
        };
        if (editando) router.put(`/admin/preguntas/${pregunta.id}`, data);
        else router.post('/admin/preguntas', data);
    };

    const toggleCorrecta = (index) => setAlternativas(alternativas.map((a, i) => ({ ...a, es_correcta: i === index })));
    const letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

    return (
        <>
            <Head title={editando ? 'Editar Pregunta' : 'Nueva Pregunta'} />
            <div className="min-h-screen bg-cyber-dark">
                <div className="relative overflow-hidden bg-gradient-to-br from-neon-cyan/15 via-cyber-dark-100 to-cyber-dark py-8">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.1),transparent_50%)]" />
                    <div className="relative mx-auto max-w-4xl px-5 sm:px-8 lg:px-10">
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="cyber-badge cyber-badge-cyan rounded-lg px-4 py-1.5 text-sm font-bold inline-flex mb-3">
                                    <Sparkles className="h-4 w-4" /> {editando ? 'Editar' : 'Nueva'} Pregunta
                                </div>
                                <h1 className="text-2xl font-heading font-black text-text-primary">{editando ? 'Editar Pregunta' : 'Nueva Pregunta'}</h1>
                                <p className="mt-1 text-sm font-semibold text-text-muted">Crea preguntas para el banco por área y curso</p>
                            </div>
                            <Link href="/admin/preguntas" className="cyber-btn rounded-xl px-4 py-2 text-sm font-bold border-cyber-dark-400"><ArrowLeft className="h-4 w-4" /> Volver</Link>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-4xl px-5 sm:px-8 lg:px-10 mt-4 pb-8">
                    <form onSubmit={handleSubmit} className="space-y-4">
                        {flash?.success && (
                            <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                                className="flex items-center gap-2 rounded-xl border border-neon-green/30 bg-neon-green/5 p-4 text-sm font-bold text-neon-green">
                                <CheckCircle className="h-4 w-4" /> {flash.success}
                            </motion.div>
                        )}

                        <div className="cyber-card rounded-xl p-5">
                            <h2 className="text-lg font-heading font-bold text-text-primary mb-4 neon-text-cyan">Ubicación</h2>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-bold text-text-secondary mb-1">Área Académica</label>
                                    <select value={areaId} onChange={(e) => { setAreaId(e.target.value); setConceptoId(''); }} className="w-full cyber-input rounded-xl px-4 py-2.5 text-sm" required>
                                        <option value="">Seleccionar área</option>
                                        {areas?.map((a) => (<option key={a.id} value={a.id}>{a.nombre}</option>))}
                                    </select>
                                    {errors?.area_academica_id && <p className="mt-1 text-xs text-neon-cyan/80">{errors.area_academica_id}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-bold text-text-secondary mb-1">Curso / Concepto</label>
                                    <select value={conceptoId} onChange={(e) => setConceptoId(e.target.value)} className="w-full cyber-input rounded-xl px-4 py-2.5 text-sm" required>
                                        <option value="">Seleccionar curso</option>
                                        {conceptosFiltrados.map((c) => (<option key={c.id} value={c.id}>{c.nombre}</option>))}
                                    </select>
                                    {errors?.concepto_id && <p className="mt-1 text-xs text-neon-cyan/80">{errors.concepto_id}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-bold text-text-secondary mb-1">Nivel <span className="text-text-muted font-normal">(opcional)</span></label>
                                    <select value={nivel} onChange={(e) => setNivel(e.target.value)} className="w-full cyber-input rounded-xl px-4 py-2.5 text-sm">
                                        <option value="">Sin nivel</option>
                                        <option value="1">Nivel 1</option><option value="2">Nivel 2</option><option value="3">Nivel 3</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-bold text-text-secondary mb-1">Dificultad</label>
                                    <select value={dificultad} onChange={(e) => setDificultad(e.target.value)} className="w-full cyber-input rounded-xl px-4 py-2.5 text-sm">
                                        <option value="facil">Fácil</option><option value="media">Media</option><option value="dificil">Difícil</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div className="cyber-card rounded-xl p-5">
                            <label className="block text-sm font-bold text-text-secondary mb-1">Enunciado</label>
                            <textarea value={enunciado} onChange={(e) => setEnunciado(e.target.value)}
                                rows={4} className="w-full cyber-input rounded-xl px-4 py-3 text-sm resize-none" placeholder="Escribe el enunciado..." required />
                            {errors?.enunciado && <p className="mt-1 text-sm text-neon-magenta">{errors.enunciado}</p>}

                            <div className="mt-4">
                                <label className="flex items-center gap-1.5 text-sm font-bold text-text-secondary mb-1">
                                    <Image className="h-4 w-4 text-neon-cyan" /> Imagen de apoyo <span className="text-text-muted font-normal">(opcional)</span>
                                </label>
                                <div className="flex items-center gap-2">
                                    <input type="text" value={enunciadoImagen} onChange={(e) => setEnunciadoImagen(e.target.value)}
                                        className="flex-1 cyber-input rounded-xl px-4 py-2.5 text-sm" placeholder="https://ejemplo.com/imagen.jpg" />
                                    <div className="relative">
                                        <button type="button" onClick={() => fileInputRef.current?.click()} disabled={subiendoEnunciado}
                                            className="cyber-btn rounded-xl px-4 py-2.5 text-sm font-bold border-cyber-dark-400">
                                            {subiendoEnunciado ? <Loader2 className="h-4 w-4 animate-spin" /> : <Upload className="h-4 w-4" />}
                                        </button>
                                        <input ref={fileInputRef} type="file" accept="image/jpeg,image/png,image/gif,image/webp" onChange={handleUploadEnunciado} className="hidden" />
                                    </div>
                                </div>
                                {enunciadoImagen && (
                                    <div className="mt-3 rounded-xl border border-cyber-dark-400/30 bg-cyber-dark-300 overflow-hidden relative">
                                        <img src={enunciadoImagen} alt="Preview" className="max-h-40 w-full object-contain" onError={(e) => { e.target.style.display = 'none'; }} />
                                        <button type="button" onClick={() => setEnunciadoImagen('')} className="absolute top-2 right-2 rounded-full bg-neon-magenta/80 text-white p-1 hover:bg-neon-magenta shadow"><XIcon className="h-4 w-4" /></button>
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="cyber-card rounded-xl p-5">
                            <h2 className="text-lg font-heading font-bold text-text-primary mb-1 neon-text-cyan">Alternativas</h2>
                            <p className="text-sm font-semibold text-text-muted mb-4">Selecciona la alternativa correcta con el botón de verificación.</p>
                            {errors?.alternativas && <p className="mb-3 text-sm text-neon-magenta">{errors.alternativas}</p>}
                            <div className="space-y-3">
                                {alternativas.map((alt, i) => (
                                    <div key={i} className="rounded-xl border border-cyber-dark-400/30 bg-cyber-dark-200 p-3">
                                        <div className="flex items-start gap-3">
                                            <button type="button" onClick={() => toggleCorrecta(i)}
                                                className={`mt-2 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border-2 transition-all ${
                                                    alt.es_correcta ? 'border-neon-green bg-neon-green text-white shadow-[0_0_8px_rgba(0,255,0,0.3)]' : 'border-cyber-dark-400 hover:border-neon-cyan'
                                                }`}>
                                                {alt.es_correcta && <CheckIcon className="h-3.5 w-3.5" />}
                                            </button>
                                            <span className="mt-2 text-sm font-bold text-text-muted flex-shrink-0">{letras[i]}.</span>
                                            <div className="flex-1 min-w-0 space-y-2">
                                                <input type="text" value={alt.texto} onChange={(e) => setAlternativas(alternativas.map((a, j) => j === i ? { ...a, texto: e.target.value } : a))}
                                                    className="w-full cyber-input rounded-lg px-3 py-2 text-sm" placeholder={`Texto de la alternativa ${letras[i]}`} required />
                                                <div className="flex items-center gap-2">
                                                    <input type="text" value={alt.imagen_url || ''} onChange={(e) => setAlternativas(alternativas.map((a, j) => j === i ? { ...a, imagen_url: e.target.value } : a))}
                                                        className="flex-1 cyber-input rounded-lg px-3 py-1.5 text-xs" placeholder={`URL de imagen (opcional)`} />
                                                    <div className="relative flex-shrink-0">
                                                        <button type="button" onClick={() => altFileInputs.current[i]?.click()} disabled={subiendoAlt[i]}
                                                            className="cyber-btn rounded-lg px-3 py-1.5 text-xs font-bold border-cyber-dark-400">
                                                            {subiendoAlt[i] ? <Loader2 className="h-3.5 w-3.5 animate-spin" /> : <Upload className="h-3.5 w-3.5" />}
                                                        </button>
                                                        <input ref={(el) => { altFileInputs.current[i] = el; }} type="file" accept="image/jpeg,image/png,image/gif,image/webp"
                                                            onChange={(e) => handleUploadAlt(e, i)} className="hidden" />
                                                    </div>
                                                    {alt.imagen_url && (
                                                        <button type="button" onClick={() => setAlternativas(alternativas.map((a, j) => j === i ? { ...a, imagen_url: '' } : a))}
                                                            className="rounded-lg p-1.5 text-text-muted hover:text-white hover:bg-cyber-dark-300 flex-shrink-0"><XIcon className="h-4 w-4" /></button>
                                                    )}
                                                </div>
                                                {alt.imagen_url && <img src={alt.imagen_url} alt="" className="max-h-16 rounded-lg object-contain bg-cyber-dark-300" onError={(e) => { e.target.style.display = 'none'; }} />}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="flex items-center justify-between gap-4">
                            <Link href="/admin/preguntas" className="cyber-btn rounded-xl px-6 py-3 text-sm font-bold border-cyber-dark-400">Cancelar</Link>
                            <button type="submit" className="cyber-btn cyber-btn-primary rounded-xl px-8 py-3 text-sm font-bold">
                                {editando ? 'Guardar cambios' : 'Crear pregunta'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
function XIcon(props) { return <svg {...props} fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg> }
function CheckIcon(props) { return <svg {...props} fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" /></svg> }
