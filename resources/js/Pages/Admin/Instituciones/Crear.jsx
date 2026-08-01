import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState, useRef } from 'react';
import { motion } from 'motion/react';
import { Building2, School, CheckCircle2, Plus, X, Upload, ArrowLeft, Sparkles } from 'lucide-react';

export default function AdminInstitucionesCrear({ institucion, editando }) {
    const { errors, flash } = usePage().props;
    const [nombre, setNombre] = useState(institucion?.nombre || '');
    const [subtipo, setSubtipo] = useState(institucion?.subtipo || 'privada');
    const [ciudad, setCiudad] = useState(institucion?.ciudad || '');
    const [carreras, setCarreras] = useState(
        institucion?.categorias?.map((c) => c.nombre) || []
    );
    const [nuevaCarrera, setNuevaCarrera] = useState('');
    const [logoFile, setLogoFile] = useState(null);
    const [logoPreview, setLogoPreview] = useState(institucion?.logo_url || null);
    const logoInputRef = useRef(null);

    const agregarCarrera = () => {
        const nombre = nuevaCarrera.trim();
        if (nombre && !carreras.includes(nombre)) {
            setCarreras([...carreras, nombre]);
            setNuevaCarrera('');
        }
    };

    const eliminarCarrera = (index) => {
        setCarreras(carreras.filter((_, i) => i !== index));
    };

    const handleKeyDown = (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            agregarCarrera();
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const data = { nombre, subtipo, ciudad: ciudad || null, carreras };
        if (logoFile) data.logo = logoFile;

        if (editando) {
            router.post(`/admin/instituciones/${institucion.id}`, Object.assign(data, { _method: 'PUT' }), {
                forceFormData: true,
            });
        } else {
            router.post('/admin/instituciones', data, {
                forceFormData: true,
            });
        }
    };

    const handleLogoChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setLogoFile(file);
            setLogoPreview(URL.createObjectURL(file));
        }
    };

    const removeLogo = () => {
        setLogoFile(null);
        setLogoPreview(null);
        if (logoInputRef.current) logoInputRef.current.value = '';
    };

    return (
        <>
            <Head title={editando ? 'Editar Universidad' : 'Nueva Universidad'} />
            <div className="min-h-screen bg-cyber-dark">
                {/* Header */}
                <div className="relative overflow-hidden border-b border-cyber-dark-400/50 bg-cyber-dark-100 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.08),transparent_50%)]" />
                    <div className="relative mx-auto max-w-2xl px-5 sm:px-8 lg:px-10 py-8">
                        <div className="flex items-center justify-between">
                            <div>
                                <div className="inline-flex items-center gap-2 rounded-full bg-neon-cyan/10 border border-neon-cyan/30 px-4 py-1.5 text-sm font-bold text-neon-cyan mb-3">
                                    <Sparkles className="h-4 w-4" /> {editando ? 'Editar' : 'Nueva'} Universidad
                                </div>
                                <h1 className="text-2xl font-heading font-black text-text-primary">
                                    {editando ? 'Editar ' : 'Nueva '}<span className="neon-text-cyan">Universidad</span>
                                </h1>
                                <p className="mt-1 text-sm text-text-secondary font-semibold">
                                    {editando ? 'Modifica los datos de la universidad' : 'Registra una nueva universidad'}
                                </p>
                            </div>
                            <Link href="/admin/instituciones" className="cyber-btn rounded-xl px-4 py-2 text-sm">
                                <ArrowLeft className="h-4 w-4" /> Volver
                            </Link>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-2xl px-5 sm:px-8 lg:px-10 mt-6 pb-8">
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

                        {/* Datos de la universidad */}
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.35 }}
                            className="rounded-xl cyber-card border-neon-cyan/20 shadow-neon-cyan/10 p-6">
                            <div className="flex items-center gap-2 mb-5">
                                <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-neon-cyan/10 border border-neon-cyan/30">
                                    <Building2 className="h-5 w-5 text-neon-cyan" />
                                </div>
                                <h2 className="text-lg font-heading font-bold text-text-primary">Datos de la universidad</h2>
                            </div>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-semibold text-text-secondary mb-1.5">Nombre de la universidad</label>
                                    <input type="text" value={nombre} onChange={(e) => setNombre(e.target.value)}
                                        className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm"
                                        placeholder="Ej: Universidad Nacional Mayor de San Marcos" required />
                                    {errors?.nombre && <p className="mt-1 text-xs text-neon-cyan/80/70">{errors.nombre}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-text-secondary mb-1.5">Logo / Imagen (opcional)</label>
                                    <input ref={logoInputRef} type="file" accept="image/*" onChange={handleLogoChange} className="hidden" />
                                    {logoPreview ? (
                                        <div className="relative inline-block">
                                            <img src={logoPreview} alt="Preview" className="h-24 w-40 rounded-xl border border-cyber-dark-400/50 object-cover" />
                                            <button type="button" onClick={removeLogo}
                                                className="absolute -right-2 -top-2 rounded-full bg-neon-magenta p-1 text-white shadow-lg hover:bg-neon-magenta/80 transition-all">
                                                <X className="h-3 w-3" />
                                            </button>
                                        </div>
                                    ) : (
                                        <button type="button" onClick={() => logoInputRef.current?.click()}
                                            className="flex items-center gap-2 rounded-xl border-2 border-dashed border-cyber-dark-400/50 bg-cyber-dark-300/50 px-4 py-3 text-sm text-text-muted hover:border-neon-cyan/30 hover:text-white transition-all">
                                            <Upload className="h-4 w-4" />
                                            Subir imagen de la universidad
                                        </button>
                                    )}
                                    {errors?.logo && <p className="mt-1 text-xs text-neon-cyan/80/70">{errors.logo}</p>}
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label className="block text-sm font-semibold text-text-secondary mb-1.5">Tipo</label>
                                        <select value={subtipo} onChange={(e) => setSubtipo(e.target.value)}
                                            className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm" required>
                                            <option value="publica" className="bg-cyber-dark">Pública</option>
                                            <option value="privada" className="bg-cyber-dark">Privada</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-semibold text-text-secondary mb-1.5">Ciudad (opcional)</label>
                                        <input type="text" value={ciudad} onChange={(e) => setCiudad(e.target.value)}
                                            className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm"
                                            placeholder="Ej: Lima" />
                                    </div>
                                </div>
                            </div>
                        </motion.div>

                        {/* Carreras */}
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.35, delay: 0.1 }}
                            className="rounded-xl cyber-card border-neon-magenta/20 shadow-neon-magenta/10 p-6">
                            <div className="flex items-center gap-2 mb-1">
                                <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-neon-magenta/10 border border-neon-magenta/30">
                                    <School className="h-5 w-5 text-neon-magenta" />
                                </div>
                                <h2 className="text-lg font-heading font-bold text-text-primary">Carreras</h2>
                            </div>
                            <p className="text-xs text-text-muted mb-4 ml-[45px]">Agrega las carreras que se imparten en esta universidad</p>

                            <div className="flex gap-2">
                                <input
                                    type="text"
                                    value={nuevaCarrera}
                                    onChange={(e) => setNuevaCarrera(e.target.value)}
                                    onKeyDown={handleKeyDown}
                                    className="cyber-input flex-1 rounded-xl px-4 py-2.5 text-sm"
                                    placeholder="Ej: Ingeniería de Sistemas"
                                />
                                <button
                                    type="button"
                                    onClick={agregarCarrera}
                                    disabled={!nuevaCarrera.trim()}
                                    className="cyber-btn cyber-btn-primary rounded-xl px-4 py-2.5 text-sm disabled:opacity-40"
                                >
                                    <Plus className="h-4 w-4" /> Agregar
                                </button>
                            </div>

                            {carreras.length > 0 ? (
                                <div className="mt-4 flex flex-wrap gap-2">
                                    {carreras.map((carrera, index) => (
                                        <span
                                            key={index}
                                            className="inline-flex items-center gap-1.5 rounded-full border border-neon-cyan/30 bg-neon-cyan/10 px-3 py-1.5 text-sm font-semibold text-neon-cyan"
                                        >
                                            {carrera}
                                            <button
                                                type="button"
                                                onClick={() => eliminarCarrera(index)}
                                                className="rounded-full p-0.5 text-neon-cyan/60 hover:bg-neon-cyan/20 hover:text-white transition-all"
                                            >
                                                <X className="h-3.5 w-3.5" />
                                            </button>
                                        </span>
                                    ))}
                                </div>
                            ) : (
                                <p className="mt-4 text-center text-sm text-text-muted">
                                    No hay carreras agregadas aún
                                </p>
                            )}
                        </motion.div>

                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.35, delay: 0.2 }}
                            className="flex items-center justify-between gap-4">
                            <Link href="/admin/instituciones" className="cyber-btn rounded-xl px-6 py-2.5 text-sm">Cancelar</Link>
                            <button type="submit" className="cyber-btn cyber-btn-primary rounded-xl px-8 py-2.5 text-sm">
                                {editando ? 'Guardar cambios' : 'Crear universidad'}
                            </button>
                        </motion.div>
                    </form>
                </div>
            </div>
        </>
    );
}
