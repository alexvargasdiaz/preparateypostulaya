import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { motion } from 'motion/react';
import { CheckCircle2, AlertTriangle, UserPlus, ArrowLeft } from 'lucide-react';
import { normalizarWhatsApp } from '@/lib/utils';

export default function AdminAlumnosCrear({ alumno, editando }) {
    const { errors, flash } = usePage().props;

    const [name, setName] = useState(alumno?.name || '');
    const [email, setEmail] = useState(alumno?.email || '');
    const [password, setPassword] = useState('');
    const [whatsapp, setWhatsapp] = useState(alumno?.whatsapp_numero || '');
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        if (submitting) return;
        if (whatsapp && !normalizarWhatsApp(whatsapp)) return;
        setSubmitting(true);

        const data = { name, email, whatsapp_numero: normalizarWhatsApp(whatsapp) || null };

        if (!editando) {
            data.password = password;
        } else if (password) {
            data.password = password;
        }

        if (editando) {
            router.put(`/admin/alumnos/${alumno.id}`, data, {
                onFinish: () => setSubmitting(false),
                onError: () => setSubmitting(false),
            });
        } else {
            router.post('/admin/alumnos', data, {
                onFinish: () => setSubmitting(false),
                onError: () => setSubmitting(false),
            });
        }
    };

    return (
        <>
            <Head title={editando ? 'Editar Alumno' : 'Nuevo Alumno'} />

            <div className="min-h-screen bg-cyber-dark">
                {/* Header */}
                <div className="relative overflow-hidden border-b border-cyber-dark-400/50 bg-cyber-dark-100 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.08),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10 py-8">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-heading font-black text-text-primary">
                                    {editando ? 'Editar ' : 'Nuevo '}<span className="neon-text-cyan">Alumno</span>
                                </h1>
                                <p className="mt-1 text-sm text-text-secondary font-semibold">
                                    {editando
                                        ? `Modificando datos de ${alumno?.name}`
                                        : 'Registra un nuevo alumno — será aprobado automáticamente'}
                                </p>
                            </div>
                            <Link href="/admin/alumnos" className="cyber-btn rounded-xl px-4 py-2 text-sm">
                                <ArrowLeft className="h-4 w-4" /> Volver
                            </Link>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-6 pb-8">
                    <form onSubmit={handleSubmit} className="space-y-5">
                        {/* Flash Messages */}
                        {flash?.success && (
                            <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                                className="flex items-center gap-3 rounded-xl cyber-card border-neon-green/30 px-5 py-4 shadow-neon-green/10">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-neon-green/10 border border-neon-green/30">
                                    <CheckCircle2 className="h-4 w-4 text-neon-green" />
                                </div>
                                <p className="text-sm font-bold text-neon-green">{flash.success}</p>
                            </motion.div>
                        )}
                        {flash?.error && (
                            <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                                className="flex items-center gap-3 rounded-xl cyber-card border-neon-magenta/30 px-5 py-4 shadow-neon-magenta/10">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-neon-magenta/10 border border-neon-magenta/30">
                                    <AlertTriangle className="h-4 w-4 text-neon-magenta" />
                                </div>
                                <p className="text-sm font-bold text-neon-cyan">{flash.error}</p>
                            </motion.div>
                        )}

                        {/* Form Card */}
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.4 }}>
                            <div className="rounded-xl cyber-card border-neon-cyan/20 shadow-neon-cyan/10 p-6">
                                <h2 className="text-lg font-heading font-bold text-text-primary mb-5">
                                    <UserPlus className="inline h-5 w-5 mr-2 text-neon-cyan" />
                                    Datos del alumno
                                </h2>
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-semibold text-text-secondary mb-1.5">Nombre completo</label>
                                        <input type="text" value={name} onChange={(e) => setName(e.target.value)}
                                            className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm" placeholder="Ej: Juan Pérez" required />
                                        {errors?.name && <p className="mt-1 text-xs text-neon-cyan/80/70">{errors.name}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-text-secondary mb-1.5">Correo electrónico</label>
                                        <input type="email" value={email} onChange={(e) => setEmail(e.target.value)}
                                            className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm" placeholder="ejemplo@correo.com" required />
                                        {errors?.email && <p className="mt-1 text-xs text-neon-cyan/80/70">{errors.email}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-text-secondary mb-1.5">
                                            Contraseña {editando ? <span className="text-text-muted font-normal">(dejar vacío para mantener)</span> : ''}
                                        </label>
                                        <input type="password" value={password} onChange={(e) => setPassword(e.target.value)}
                                            {...(editando ? {} : { required: true })} minLength={8}
                                            className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm"
                                            placeholder={editando ? '•••••••• (dejar vacío)' : 'Mínimo 8 caracteres'} />
                                        {errors?.password && <p className="mt-1 text-xs text-neon-cyan/80/70">{errors.password}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-text-secondary mb-1.5">WhatsApp (opcional)</label>
                                        <input type="text" value={whatsapp} onChange={(e) => setWhatsapp(e.target.value)}
                                            className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm" placeholder="+51 999 888 777" />
                                    </div>
                                </div>
                            </div>
                        </motion.div>

                        {/* Auto-approval notice */}
                        {!editando && (
                            <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.4, delay: 0.1 }}>
                                <div className="rounded-xl cyber-card border-neon-green/20 shadow-neon-green/10 p-5">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-neon-green/10 border border-neon-green/30">
                                            <CheckCircle2 className="h-5 w-5 text-neon-green" />
                                        </div>
                                        <div>
                                            <p className="text-sm font-bold text-neon-green">Aprobación automática</p>
                                            <p className="text-xs text-text-muted">El alumno será creado con estado <strong className="text-neon-cyan">activo</strong> y aprobado por ti.</p>
                                        </div>
                                    </div>
                                </div>
                            </motion.div>
                        )}

                        {/* Buttons */}
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.4, delay: 0.2 }}
                            className="flex items-center justify-between gap-4">
                            <Link href="/admin/alumnos" className="cyber-btn rounded-xl px-6 py-2.5 text-sm">Cancelar</Link>
                            <button type="submit" disabled={submitting}
                                className="cyber-btn cyber-btn-primary rounded-xl px-8 py-2.5 text-sm disabled:opacity-40">
                                {submitting ? 'Guardando...' : editando ? 'Guardar cambios' : 'Crear alumno'}
                            </button>
                        </motion.div>
                    </form>
                </div>
            </div>
        </>
    );
}
