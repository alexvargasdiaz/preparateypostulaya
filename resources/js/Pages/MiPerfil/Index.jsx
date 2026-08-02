import { Head, router, usePage } from '@inertiajs/react';
import { useState, useRef, useEffect } from 'react';
import { motion } from 'motion/react';
import {
    User, Mail, Phone, Lock, Save, CheckCircle2, AlertTriangle,
    Shield, Calendar, Eye, EyeOff, Camera, X, Image
} from 'lucide-react';
import { normalizarWhatsApp } from '@/lib/utils';

export default function MiPerfil({ usuario }) {
    const { errors, flash } = usePage().props;
    const [name, setName] = useState(usuario?.name || '');
    const [email, setEmail] = useState(usuario?.email || '');
    const [whatsapp, setWhatsapp] = useState(usuario?.whatsapp_numero || '');
    const [currentPassword, setCurrentPassword] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [newPasswordConfirmation, setNewPasswordConfirmation] = useState('');
    const [showCurrentPassword, setShowCurrentPassword] = useState(false);
    const [showNewPassword, setShowNewPassword] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [fotoPreview, setFotoPreview] = useState(usuario?.foto || null);
    const [fotoFile, setFotoFile] = useState(null);
    const [uploadingFoto, setUploadingFoto] = useState(false);
    const fileInputRef = useRef(null);

    useEffect(() => { setFotoPreview(usuario?.foto || null); }, [usuario?.foto]);

    const handleFotoSelect = (e) => {
        const file = e.target.files[0];
        if (!file || !file.type.startsWith('image/')) return;
        setFotoFile(file);
        const reader = new FileReader();
        reader.onload = (ev) => setFotoPreview(ev.target.result);
        reader.readAsDataURL(file);
    };

    const handleFotoUpload = () => {
        if (!fotoFile) return;
        setUploadingFoto(true);
        const formData = new FormData();
        formData.append('foto', fotoFile);
        router.post('/mi-perfil/foto', formData, {
            forceFormData: true, preserveState: true,
            onFinish: () => { setUploadingFoto(false); setFotoFile(null); },
        });
    };

    const handleFotoRemove = () => {
        router.delete('/mi-perfil/foto', {
            preserveState: true,
            onSuccess: () => { setFotoPreview(null); setFotoFile(null); },
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (whatsapp && !normalizarWhatsApp(whatsapp)) return;
        setSubmitting(true);
        router.put('/mi-perfil', {
            name, email, whatsapp_numero: normalizarWhatsApp(whatsapp) || null,
            current_password: currentPassword || null,
            new_password: newPassword || null,
            new_password_confirmation: newPasswordConfirmation || null,
        }, {
            onFinish: () => {
                setSubmitting(false);
                setCurrentPassword(''); setNewPassword(''); setNewPasswordConfirmation('');
            },
        });
    };

    const fechaRegistro = usuario?.created_at
        ? new Date(usuario.created_at).toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' })
        : '—';

    return (
        <>
            <Head title="Mi Perfil" />
            <div className="min-h-screen bg-cyber-dark">
                {/* Header */}
                <div className="relative overflow-hidden border-b border-cyber-dark-400/50 bg-cyber-dark-100 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.08),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10 py-8">
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5 }}>
                            <div className="flex items-center gap-5">
                                <div className="flex h-16 w-16 items-center justify-center rounded-xl cyber-card border-neon-cyan/40 shadow-neon-cyan text-2xl font-heading font-black text-neon-cyan">
                                    {usuario?.name?.charAt(0)?.toUpperCase() || '?'}
                                </div>
                                <div>
                                    <h1 className="text-2xl font-heading font-black text-text-primary">
                                        Mi{' '}
                                        <span className="neon-text-cyan">Perfil</span>
                                    </h1>
                                    <p className="mt-1 text-sm text-text-secondary font-semibold">Gestiona tu información personal</p>
                                </div>
                            </div>
                        </motion.div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-6 pb-12">
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
                            className="mb-6 flex items-center gap-3 rounded-xl cyber-card border-neon-magenta/30 px-5 py-4">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-neon-magenta/10 border border-neon-magenta/30">
                                <AlertTriangle className="h-4 w-4 text-neon-magenta" />
                            </div>
                            <p className="text-sm font-bold text-neon-cyan">{flash.error}</p>
                        </motion.div>
                    )}

                    {/* Foto */}
                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5 }}
                        className="cyber-card rounded-xl border-cyber-dark-400/40 p-6">
                        <div className="flex items-center gap-2 mb-5">
                            <Image className="h-5 w-5 text-neon-cyan" />
                            <h2 className="text-sm font-heading font-bold text-text-primary">Foto de perfil</h2>
                        </div>
                        <div className="flex items-center gap-6">
                            <div className="relative flex-shrink-0">
                                {fotoPreview ? (
                                    <img src={fotoPreview} alt="Foto de perfil"
                                        className="h-24 w-24 rounded-xl object-cover ring-2 ring-neon-cyan/30 shadow-neon-cyan" />
                                ) : (
                                    <div className="flex h-24 w-24 items-center justify-center rounded-xl bg-neon-cyan/10 border-2 border-neon-cyan/30 text-3xl font-heading font-black text-neon-cyan shadow-neon-cyan">
                                        {usuario?.name?.charAt(0)?.toUpperCase() || '?'}
                                    </div>
                                )}
                            </div>
                            <div className="flex flex-col gap-2">
                                <input ref={fileInputRef} type="file" accept="image/jpeg,image/png,image/gif,image/webp"
                                    onChange={handleFotoSelect} className="hidden" />
                                <button type="button" onClick={() => fileInputRef.current?.click()}
                                    className="cyber-btn rounded-xl px-4 py-2 text-sm justify-center">
                                    <Camera className="h-4 w-4" /> {fotoPreview ? 'Cambiar foto' : 'Subir foto'}
                                </button>
                                {fotoFile && (
                                    <button type="button" onClick={handleFotoUpload} disabled={uploadingFoto}
                                        className="cyber-btn cyber-btn-primary rounded-xl px-4 py-2 text-sm justify-center disabled:opacity-50">
                                        {uploadingFoto ? 'Subiendo...' : 'Guardar foto'}
                                    </button>
                                )}
                                {usuario?.foto && !fotoFile && (
                                    <button type="button" onClick={handleFotoRemove}
                                        className="cyber-btn rounded-xl px-4 py-2 text-sm justify-center">
                                        <X className="h-4 w-4" /> Eliminar foto
                                    </button>
                                )}
                            </div>
                        </div>
                        <p className="mt-3 text-xs text-text-muted font-semibold">Formatos: JPG, PNG, GIF o WebP. Máximo 5 MB.</p>
                    </motion.div>

                    <form onSubmit={handleSubmit} className="space-y-6 mt-6">
                        {/* Info personal */}
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5, delay: 0.1 }}
                            className="cyber-card rounded-xl border-cyber-dark-400/40 p-6">
                            <div className="flex items-center gap-2 mb-5">
                                <User className="h-5 w-5 text-neon-cyan" />
                                <h2 className="text-sm font-heading font-bold text-text-primary">Información personal</h2>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <label className="block text-xs font-bold text-text-secondary mb-1 uppercase tracking-wider">Nombre completo</label>
                                    <div className="cyber-input-wrapper relative">
                                        <User className="cyber-input-icon" />
                                        <input type="text" value={name} onChange={(e) => setName(e.target.value)} required
                                            className="cyber-input w-full rounded-xl py-2.5 pl-10 pr-4 text-sm"
                                            placeholder="Tu nombre completo" />
                                    </div>
                                    {errors?.name && <p className="mt-1 text-xs text-neon-cyan/80">{errors.name}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-text-secondary mb-1 uppercase tracking-wider">Correo electrónico</label>
                                    <div className="cyber-input-wrapper relative">
                                        <Mail className="cyber-input-icon" />
                                        <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required
                                            className="cyber-input w-full rounded-xl py-2.5 pl-10 pr-4 text-sm"
                                            placeholder="tu@email.com" />
                                    </div>
                                    {errors?.email && <p className="mt-1 text-xs text-neon-cyan/80">{errors.email}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-text-secondary mb-1 uppercase tracking-wider">WhatsApp</label>
                                    <div className="cyber-input-wrapper relative">
                                        <Phone className="cyber-input-icon" />
                                        <input type="text" value={whatsapp} onChange={(e) => setWhatsapp(e.target.value)}
                                            className="cyber-input w-full rounded-xl py-2.5 pl-10 pr-4 text-sm"
                                            placeholder="+51 999 888 777" />
                                    </div>
                                    <p className="mt-1 text-xs text-text-muted font-semibold">Para recibir resultados por WhatsApp</p>
                                </div>
                            </div>
                        </motion.div>

                        {/* Datos cuenta */}
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5, delay: 0.15 }}
                            className="cyber-card rounded-xl border-cyber-dark-400/40 p-6">
                            <div className="flex items-center gap-2 mb-5">
                                <Shield className="h-5 w-5 text-neon-magenta" />
                                <h2 className="text-sm font-heading font-bold text-text-primary">Datos de la cuenta</h2>
                            </div>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="rounded-xl bg-cyber-dark-300 border border-cyber-dark-400/50 p-4">
                                    <p className="text-[10px] font-bold text-neon-cyan uppercase tracking-widest">Rol</p>
                                    <p className="mt-1 text-sm font-heading font-bold text-text-primary">{usuario?.rol_label || '—'}</p>
                                </div>
                                <div className="rounded-xl bg-cyber-dark-300 border border-cyber-dark-400/50 p-4">
                                    <p className="text-[10px] font-bold text-neon-green uppercase tracking-widest">Estado</p>
                                    <div className="mt-1 flex items-center gap-1.5">
                                        <span className="h-2 w-2 rounded-full bg-neon-green shadow-neon-green" />
                                        <p className="text-sm font-heading font-bold text-text-primary">Activo</p>
                                    </div>
                                </div>
                                <div className="rounded-xl bg-cyber-dark-300 border border-cyber-dark-400/50 p-4 sm:col-span-2">
                                    <p className="text-[10px] font-bold text-neon-cyan uppercase tracking-widest">Miembro desde</p>
                                    <div className="mt-1 flex items-center gap-1.5">
                                        <Calendar className="h-4 w-4 text-neon-cyan" />
                                        <p className="text-sm font-heading font-bold text-text-primary">{fechaRegistro}</p>
                                    </div>
                                </div>
                            </div>
                        </motion.div>

                        {/* Contraseña */}
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5, delay: 0.2 }}
                            className="cyber-card rounded-xl border-cyber-dark-400/40 p-6">
                            <div className="flex items-center gap-2 mb-5">
                                <Lock className="h-5 w-5 text-neon-magenta" />
                                <h2 className="text-sm font-heading font-bold text-text-primary">Cambiar contraseña</h2>
                            </div>
                            <p className="mb-4 text-xs text-text-muted font-semibold">
                                Deja estos campos vacíos si no deseas cambiar tu contraseña.
                            </p>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-xs font-bold text-text-secondary mb-1 uppercase tracking-wider">Contraseña actual</label>
                                    <div className="cyber-input-wrapper relative">
                                        <Lock className="cyber-input-icon" />
                                        <input type={showCurrentPassword ? 'text' : 'password'}
                                            value={currentPassword} onChange={(e) => setCurrentPassword(e.target.value)}
                                            className="cyber-input w-full rounded-xl py-2.5 pl-10 pr-10 text-sm"
                                            placeholder="••••••••" />
                                        <button type="button" onClick={() => setShowCurrentPassword(!showCurrentPassword)}
                                            className="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-white transition-colors">
                                            {showCurrentPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                        </button>
                                    </div>
                                    {errors?.current_password && <p className="mt-1 text-xs text-neon-cyan/80">{errors.current_password}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-text-secondary mb-1 uppercase tracking-wider">Nueva contraseña</label>
                                    <div className="cyber-input-wrapper relative">
                                        <Lock className="cyber-input-icon" />
                                        <input type={showNewPassword ? 'text' : 'password'}
                                            value={newPassword} onChange={(e) => setNewPassword(e.target.value)} minLength={8}
                                            className="cyber-input w-full rounded-xl py-2.5 pl-10 pr-10 text-sm"
                                            placeholder="Mínimo 8 caracteres" />
                                        <button type="button" onClick={() => setShowNewPassword(!showNewPassword)}
                                            className="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-white transition-colors">
                                            {showNewPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                        </button>
                                    </div>
                                    {errors?.new_password && <p className="mt-1 text-xs text-neon-cyan/80">{errors.new_password}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-text-secondary mb-1 uppercase tracking-wider">Confirmar nueva contraseña</label>
                                    <div className="cyber-input-wrapper relative">
                                        <Lock className="cyber-input-icon" />
                                        <input type={showNewPassword ? 'text' : 'password'}
                                            value={newPasswordConfirmation} onChange={(e) => setNewPasswordConfirmation(e.target.value)} minLength={8}
                                            className="cyber-input w-full rounded-xl py-2.5 pl-10 pr-4 text-sm"
                                            placeholder="Repite tu nueva contraseña" />
                                    </div>
                                </div>
                            </div>
                        </motion.div>

                        {/* Save */}
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5, delay: 0.25 }}
                            className="flex justify-end">
                            <button type="submit" disabled={submitting}
                                className="cyber-btn cyber-btn-primary rounded-xl px-8 py-3 text-sm disabled:opacity-60">
                                <Save className="h-4 w-4" />
                                {submitting ? 'Guardando...' : 'Guardar cambios'}
                            </button>
                        </motion.div>
                    </form>
                </div>
            </div>
        </>
    );
}
