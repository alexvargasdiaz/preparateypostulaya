import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState, useRef, useMemo } from 'react';
import { motion } from 'motion/react';
import { Upload, Download, FileText, AlertTriangle, CheckCircle2, Image, X, ArrowLeft, Sparkles } from 'lucide-react';

export default function AdminPreguntasImportar({ areas, conceptos }) {
    const { errors, flash } = usePage().props;
    const [areaId, setAreaId] = useState('');
    const [conceptoId, setConceptoId] = useState('');
    const [archivo, setArchivo] = useState(null);
    const [imagenes, setImagenes] = useState([]);
    const [imagenesMapa, setImagenesMapa] = useState({});
    const [subiendo, setSubiendo] = useState(false);
    const [enviando, setEnviando] = useState(false);
    const [dragOver, setDragOver] = useState(false);
    const imagenesInputRef = useRef(null);

    const conceptosFiltrados = useMemo(() => {
        if (!areaId) return [];
        return (conceptos || []).filter((c) => c.area_academica_id === parseInt(areaId));
    }, [areaId, conceptos]);

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!archivo || !areaId) return;
        setEnviando(true);
        const data = new FormData();
        data.append('area_academica_id', areaId);
        data.append('concepto_id', conceptoId || '');
        data.append('archivo', archivo);
        data.append('imagenes_mapa', JSON.stringify(imagenesMapa));
        router.post('/admin/preguntas/importar', data, { onFinish: () => setEnviando(false) });
    };

    const subirImagenes = async (files) => {
        if (!files || files.length === 0) return;
        setSubiendo(true);
        const formData = new FormData();
        for (let i = 0; i < files.length; i++) formData.append('imagenes[]', files[i]);
        try {
            const res = await fetch('/admin/preguntas/subir-imagenes-masivo', {
                method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content, 'X-Requested-With': 'XMLHttpRequest' }, body: formData,
            });
            const data = await res.json();
            if (data.success) { setImagenesMapa((prev) => ({ ...prev, ...data.mapa })); setImagenes((prev) => [...prev, ...Array.from(files).map(f => f.name)]); }
        } catch (e) { console.error('Error:', e); } finally { setSubiendo(false); }
    };

    const descargarTemplate = () => {
        const csv = ['concepto;enunciado;imagen_enunciado;alternativa_1;imagen_alt_1;alternativa_2;imagen_alt_2;alternativa_3;imagen_alt_3;alternativa_4;imagen_alt_4;alternativa_5;imagen_alt_5;respuesta_correcta;dificultad',
            'Matemática;¿Capital de Perú?;;Lima;;Bogotá;;Santiago;;Quito;;Caracas;;1;facil',
            'Lógica;2+2 es igual a...;;3;;4;;5;;6;;7;;2;facil',
        ].join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a'); a.href = url; a.download = 'template_preguntas.csv'; a.click();
        URL.revokeObjectURL(url);
    };

    return (
        <>
            <Head title="Importar Preguntas Masivo" />
            <div className="min-h-screen bg-cyber-dark">
                <div className="relative overflow-hidden bg-gradient-to-br from-neon-magenta/15 via-cyber-dark-100 to-cyber-dark py-8 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.08),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10">
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="cyber-badge cyber-badge-magenta rounded-lg px-4 py-1.5 text-sm font-bold inline-flex mb-3"><Upload className="h-4 w-4" /> Importar Masivo</div>
                                <h1 className="text-2xl font-heading font-black text-text-primary">Importar Preguntas Masivo</h1>
                                <p className="mt-1 text-sm font-semibold text-text-muted">Sube un archivo CSV con múltiples preguntas e imágenes</p>
                            </div>
                            <Link href="/admin/preguntas" className="cyber-btn rounded-xl px-4 py-2 text-sm font-bold border-cyber-dark-400"><ArrowLeft className="h-4 w-4" /> Volver</Link>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-4 pb-8 space-y-4">
                    {flash?.success && <div className="animate-slide-down flex items-center gap-2 rounded-xl border border-neon-green/30 bg-neon-green/5 px-5 py-3 text-sm font-bold text-neon-green"><CheckCircle2 size={18} /> {flash.success}</div>}
                    {flash?.errores_importacion?.length > 0 && (
                        <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} className="cyber-card rounded-xl p-5 border-neon-magenta/20">
                            <div className="flex items-center gap-2 mb-2"><AlertTriangle className="h-5 w-5 text-neon-magenta" /><p className="text-sm font-bold text-neon-cyan">Líneas con errores</p></div>
                            <ul className="space-y-1">{flash.errores_importacion.map((err, i) => (<li key={i} className="text-xs text-text-muted">{err}</li>))}</ul>
                        </motion.div>
                    )}

                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="cyber-card rounded-xl p-5">
                        <h2 className="text-base font-heading font-bold text-text-primary mb-3 flex items-center gap-2"><FileText className="h-5 w-5 text-neon-cyan" /> Formato del archivo CSV</h2>
                        <p className="text-sm text-text-muted mb-3">Separador <code className="rounded bg-cyber-dark-300 px-1.5 py-0.5 text-xs font-mono text-neon-cyan">;</code></p>
                        <div className="rounded-xl bg-cyber-dark-300 border border-cyber-dark-400/30 p-4 text-xs font-mono text-text-secondary overflow-x-auto">
                            <div className="text-text-muted mb-1"># concepto;enunciado;img;alt1;img1;alt2;img2;alt3;img3;alt4;img4;alt5;img5;resp;dificultad</div>
                            <div>Matemática;¿Capital de Perú?;;Lima;;Bogotá;;Santiago;;Quito;;Caracas;;1;facil</div>
                        </div>
                        <button onClick={descargarTemplate} className="mt-4 cyber-btn rounded-xl px-4 py-2 text-sm font-bold border-neon-cyan/40"><Download className="h-4 w-4" /> Descargar template</button>
                    </motion.div>

                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.05 }} className="cyber-card rounded-xl p-5">
                        <h2 className="text-base font-heading font-bold text-text-primary mb-1 flex items-center gap-2"><Image className="h-5 w-5 text-neon-magenta" /> Imágenes (opcional)</h2>
                        <p className="text-xs text-text-muted mb-4">Sube imágenes y refencialas por su nombre de archivo en el CSV.</p>
                        <div onDrop={(e) => { e.preventDefault(); subirImagenes(e.dataTransfer.files); }} onDragOver={(e) => { e.preventDefault(); }} onDragLeave={() => { }}
                            className="rounded-xl border-2 border-dashed border-cyber-dark-400/50 bg-cyber-dark-300/50 p-6 text-center hover:border-neon-cyan/40 hover:bg-neon-cyan/[0.02] transition-all">
                            <input ref={imagenesInputRef} type="file" multiple accept="image/*" onChange={(e) => subirImagenes(e.target.files)} className="hidden" />
                            <Image className="mx-auto h-8 w-8 text-text-muted mb-2" />
                            <p className="text-sm font-bold text-text-secondary">Arrastra imágenes aquí</p>
                            <button type="button" onClick={() => imagenesInputRef.current?.click()} disabled={subiendo}
                                className="mt-2 cyber-btn rounded-lg px-3 py-1.5 text-xs font-bold border-neon-cyan/40">{subiendo ? 'Subiendo...' : 'Seleccionar imágenes'}</button>
                        </div>
                        {Object.keys(imagenesMapa).length > 0 && (
                            <div className="mt-4 space-y-2">
                                <p className="text-xs font-bold text-neon-cyan">{Object.keys(imagenesMapa).length} imágenes subidas:</p>
                                <div className="flex flex-wrap gap-2">
                                    {Object.entries(imagenesMapa).map(([nombre, url]) => (
                                        <span key={nombre} className="inline-flex items-center gap-2 rounded-lg border border-neon-cyan/30 bg-neon-cyan/5 px-3 py-1.5 text-xs font-bold text-neon-cyan">
                                            <img src={url} alt="" className="h-5 w-5 rounded object-cover" /> {nombre}
                                            <button onClick={() => { setImagenesMapa((prev) => { const n = { ...prev }; delete n[nombre]; return n; }); setImagenes((prev) => prev.filter((n) => n !== nombre)); }}
                                                className="rounded-full p-0.5 text-neon-cyan/60 hover:text-neon-cyan"><X className="h-3 w-3" /></button>
                                        </span>
                                    ))}
                                </div>
                            </div>
                        )}
                    </motion.div>

                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.1 }} className="cyber-card rounded-xl p-5">
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-bold text-text-secondary mb-1">Área Académica</label>
                                    <select value={areaId} onChange={(e) => { setAreaId(e.target.value); setConceptoId(''); }} className="w-full cyber-input rounded-xl px-4 py-2.5 text-sm" required>
                                        <option value="">Seleccionar área</option>
                                        {areas?.map((a) => (<option key={a.id} value={a.id}>{a.nombre}</option>))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-bold text-text-secondary mb-1">Curso (opcional)</label>
                                    <select value={conceptoId} onChange={(e) => setConceptoId(e.target.value)} className="w-full cyber-input rounded-xl px-4 py-2.5 text-sm">
                                        <option value="">Detectar desde CSV</option>
                                        {conceptosFiltrados.map((c) => (<option key={c.id} value={c.id}>{c.nombre}</option>))}
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-bold text-text-secondary mb-1">Archivo CSV</label>
                                <div onDrop={(e) => { e.preventDefault(); setArchivo(e.dataTransfer.files[0]); }} onDragOver={(e) => { e.preventDefault(); setDragOver(true); }} onDragLeave={() => setDragOver(false)}
                                    className={`relative rounded-xl border-2 border-dashed p-8 text-center transition-all ${dragOver ? 'border-neon-cyan bg-neon-cyan/5' : archivo ? 'border-neon-green/50 bg-neon-green/5' : 'border-cyber-dark-400/50 bg-cyber-dark-300/50 hover:border-neon-cyan/40'}`}>
                                    <input type="file" accept=".csv,.txt" onChange={(e) => setArchivo(e.target.files[0])} className="absolute inset-0 cursor-pointer opacity-0" />
                                    {archivo ? <><CheckCircle2 className="mx-auto h-8 w-8 text-neon-green mb-2" /><p className="text-sm font-bold text-neon-green">{archivo.name}</p></>
                                        : <><Upload className="mx-auto h-8 w-8 text-text-muted mb-2" /><p className="text-sm font-bold text-text-secondary">Arrastra un archivo CSV aquí</p></>}
                                </div>
                                {errors?.archivo && <p className="mt-1 text-xs text-neon-cyan/80">{errors.archivo}</p>}
                            </div>
                            <div className="flex items-center justify-between gap-4 pt-2">
                                <Link href="/admin/preguntas" className="cyber-btn rounded-xl px-6 py-2.5 text-sm font-bold border-cyber-dark-400">Cancelar</Link>
                                <button type="submit" disabled={!archivo || !areaId || enviando}
                                    className="cyber-btn cyber-btn-primary rounded-xl px-8 py-2.5 text-sm font-bold disabled:opacity-50">
                                    <Upload className="h-4 w-4" /> {enviando ? 'Importando...' : 'Importar preguntas'}
                                </button>
                            </div>
                        </form>
                    </motion.div>
                </div>
            </div>
        </>
    );
}
