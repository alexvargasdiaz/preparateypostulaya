import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { motion } from 'motion/react';
import {
    Shield,
    Wrench,
    GraduationCap,
    CheckCircle2,
    AlertTriangle,
    ArrowLeft,
    Sparkles,
    UserPlus
} from 'lucide-react';
import { normalizarWhatsApp } from '@/lib/utils';

const rolIcons = {
    estudiante: GraduationCap,
    admin: Wrench,
    super_admin: Shield,
};

export default function AdminUsuariosCrear({ usuario, editando }) {
    const { errors, flash } = usePage().props;

    const [name, setName] = useState(usuario?.name || '');
    const [email, setEmail] = useState(usuario?.email || '');
    const [password, setPassword] = useState('');
    const [rol, setRol] = useState(usuario?.rol || 'estudiante');
    const [whatsapp, setWhatsapp] = useState(usuario?.whatsapp_numero || '');
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        if (whatsapp && !normalizarWhatsApp(whatsapp)) return;
        setSubmitting(true);

        const data = {
            name,
            email,
            rol,
            whatsapp_numero: normalizarWhatsApp(whatsapp) || null,
        };

        if (!editando) {
            data.password = password;
        } else if (password) {
            data.password = password;
        }

        if (editando) {
            router.put(`/admin/usuarios/${usuario.id}`, data, {
                onFinish: () => setSubmitting(false),
            });
        } else {
            router.post('/admin/usuarios', data, {
                onFinish: () => setSubmitting(false),
            });
        }
    };

    const roles = [
        { value: 'estudiante', label: 'Estudiante', desc: 'Acceso a simulacros, historial y progreso' },
        { value: 'admin', label: 'Administrador', desc: 'Gestión de preguntas y contenido' },
        { value: 'super_admin', label: 'Super Admin', desc: 'Acceso total al sistema' },
    ];

    return (
        <>
            <Head title={editando ? 'Editar Usuario' : 'Crear Usuario'} />

            <div className="min-h-screen bg-cyber-dark">
                {/* Header */}
                <div className="relative overflow-hidden border-b border-cyber-dark-400/50 bg-cyber-dark-100 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.08),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10 py-8">
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5 }}
                            className="flex items-center justify-between">
                            <div>
                                <div className="inline-flex items-center gap-2 rounded-full bg-neon-magenta/10 border border-neon-magenta/30 px-4 py-1.5 text-sm font-bold text-neon-cyan mb-3">
                                    <Sparkles className="h-4 w-4" /> {editando ? 'Editar' : 'Crear'} Usuario
                                </div>
                                <h1 className="text-2xl font-heading font-black text-text-primary">
                                    {editando ? 'Editar ' : 'Crear '}<span className="neon-text-cyan">Usuario</span>
                                </h1>
                                <p className="mt-1 text-sm text-text-secondary font-semibold">
                                    {editando
                                        ? `Modificando datos de ${usuario?.name}`
                                        : 'Registra un nuevo usuario con el rol que corresponda'}
                                </p>
                            </div>
                            <Link href="/admin/usuarios"
                                className="cyber-btn rounded-xl px-4 py-2 text-sm">
                                <ArrowLeft className="h-4 w-4" /> Volver
                            </Link>
                        </motion.div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-6 pb-8">
                    <form onSubmit={handleSubmit} className="space-y-5">
                        {/* Flash Messages */}
                        {flash?.success && (
                            <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.3 }}
                                className="flex items-center gap-3 rounded-xl cyber-card border-neon-green/30 px-5 py-4 shadow-neon-green/10">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-neon-green/10 border border-neon-green/30">
                                    <CheckCircle2 className="h-4 w-4 text-neon-green" />
                                </div>
                                <p className="text-sm font-bold text-neon-green">{flash.success}</p>
                            </motion.div>
                        )}
                        {flash?.error && (
                            <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.3 }}
                                className="flex items-center gap-3 rounded-xl cyber-card border-neon-magenta/30 px-5 py-4 shadow-neon-magenta/10">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-neon-magenta/10 border border-neon-magenta/30">
                                    <AlertTriangle className="h-4 w-4 text-neon-magenta" />
                                </div>
                                <p className="text-sm font-bold text-neon-cyan">{flash.error}</p>
                            </motion.div>
                        )}

                        {/* Datos del usuario */}
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5, delay: 0.1 }}
                            className="rounded-xl cyber-card border-neon-cyan/20 shadow-neon-cyan/10 p-6">
                            <h2 className="text-lg font-heading font-bold text-text-primary mb-5">
                                <UserPlus className="inline h-5 w-5 mr-2 text-neon-cyan" />
                                Datos del usuario
                            </h2>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-semibold text-text-secondary mb-1.5">Nombre completo</label>
                                    <input type="text" value={name} onChange={(e) => setName(e.target.value)} required
                                        className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm"
                                        placeholder="Ej: Juan Pérez" />
                                    {errors?.name && <p className="mt-1 text-xs text-neon-cyan/80/70">{errors.name}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-text-secondary mb-1.5">Correo electrónico</label>
                                    <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required
                                        className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm"
                                        placeholder="ejemplo@correo.com" />
                                    {errors?.email && <p className="mt-1 text-xs text-neon-cyan/80/70">{errors.email}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-text-secondary mb-1.5">
                                        Contraseña {editando ? <span className="text-text-muted font-normal">(dejar vacío para mantener)</span> : ''}
                                    </label>
                                    <input type="password" value={password} onChange={(e) => setPassword(e.target.value)}
                                        {...(editando ? {} : { required: true })} minLength={8}
                                        className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm"
                                        placeholder={editando ? '•••••••• (dejar vacío si no cambia)' : 'Mínimo 8 caracteres'} />
                                    {errors?.password && <p className="mt-1 text-xs text-neon-cyan/80/70">{errors.password}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-semibold text-text-secondary mb-1.5">WhatsApp (opcional)</label>
                                    <input type="text" value={whatsapp} onChange={(e) => setWhatsapp(e.target.value)}
                                        className="cyber-input w-full rounded-xl px-4 py-2.5 text-sm"
                                        placeholder="+51 999 888 777" />
                                </div>
                            </div>
                        </motion.div>

                        {/* Rol del usuario */}
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5, delay: 0.2 }}
                            className="rounded-xl cyber-card border-neon-magenta/20 shadow-neon-magenta/10 p-6">
                            <h2 className="text-lg font-heading font-bold text-text-primary mb-4">
                                <Shield className="inline h-5 w-5 mr-2 text-neon-magenta" />
                                Rol del usuario
                            </h2>
                            <div className="grid gap-3 sm:grid-cols-3">
                                {roles.map((r) => {
                                    const Icon = rolIcons[r.value];
                                    return (
                                        <button
                                            key={r.value}
                                            type="button"
                                            onClick={() => setRol(r.value)}
                                            className={`rounded-xl border-2 p-4 text-left transition-all ${
                                                rol === r.value
                                                    ? 'border-neon-cyan/40 bg-neon-cyan/10 shadow-neon-cyan'
                                                    : 'border-cyber-dark-400/50 bg-cyber-dark-300 hover:border-cyber-dark-500 hover:bg-cyber-dark-300/80'
                                            }`}
                                        >
                                            <div className="flex items-center gap-2">
                                                <Icon className={`h-4 w-4 ${rol === r.value ? 'text-neon-cyan' : 'text-text-muted'}`} />
                                                <p className={`text-sm font-heading font-bold ${rol === r.value ? 'text-neon-cyan' : 'text-text-primary'}`}>{r.label}</p>
                                            </div>
                                            <p className="mt-1 text-xs text-text-muted">{r.desc}</p>
                                        </button>
                                    );
                                })}
                            </div>
                            {errors?.rol && <p className="mt-2 text-xs text-neon-cyan/80/70">{errors.rol}</p>}
                        </motion.div>

                        {/* Buttons */}
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5, delay: 0.3 }}
                            className="flex items-center justify-between gap-4">
                            <Link href="/admin/usuarios" className="cyber-btn rounded-xl px-6 py-2.5 text-sm">Cancelar</Link>
                            <button type="submit" disabled={submitting}
                                className="cyber-btn cyber-btn-primary rounded-xl px-8 py-2.5 text-sm disabled:opacity-40">
                                {submitting ? 'Guardando...' : editando ? 'Guardar cambios' : 'Crear usuario'}
                            </button>
                        </motion.div>
                    </form>
                </div>
            </div>
        </>
    );
}
