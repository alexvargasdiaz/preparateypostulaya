import { Head, Link, useForm } from '@inertiajs/react';
import { motion } from 'motion/react';
import { GraduationCap, Mail, Lock, User, Phone, BookOpen, BarChart3, Gift, Sparkles, ArrowRight } from 'lucide-react';
import { normalizarWhatsApp } from '@/lib/utils';

export default function Register({ errors, flash }) {
    const { data, setData, post, processing, transform } = useForm({
        name: '', email: '', password: '', password_confirmation: '', whatsapp_numero: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        if (data.whatsapp_numero && !normalizarWhatsApp(data.whatsapp_numero)) return;
        transform((values) => ({
            ...values,
            whatsapp_numero: normalizarWhatsApp(values.whatsapp_numero),
        }));
        post('/register');
    };

    const features = [
        { icon: BookOpen, title: 'Simulacros ilimitados', desc: 'Practica todas las veces que necesites' },
        { icon: BarChart3, title: 'Resultados al instante', desc: 'Conoce tu puntaje y áreas de mejora' },
        { icon: Gift, title: '100% gratuito', desc: 'Siempre gratis, sin suscripciones' },
    ];

    return (
        <>
            <Head title="Crear Cuenta" />
            <div className="min-h-screen bg-cyber-dark flex cyber-grid">
                {/* Left - Brand Panel */}
                <div className="hidden lg:flex lg:w-1/2 relative bg-cyber-dark-100 items-center justify-center p-12 overflow-hidden">
                    <div className="absolute inset-0 opacity-10" style={{ backgroundImage: 'radial-gradient(circle at 25% 50%, rgba(0,240,255,0.3) 0%, transparent 50%), radial-gradient(circle at 75% 50%, rgba(0,240,255,0.2) 0%, transparent 50%)' }} />
                    <div className="relative max-w-md">
                        <Link href="/" className="inline-flex items-center gap-3 mb-10 group">
                            <div className="cyber-logo" style={{width:44,height:44,fontSize:'1.3rem'}}>P</div>
                            <span className="text-xl font-heading font-bold text-text-primary neon-text-cyan">Prepárate y Postula Ya</span>
                        </Link>
                        <div className="cyber-badge cyber-badge-magenta rounded-lg px-4 py-2 mb-6 inline-flex">
                            <GraduationCap className="h-4 w-4" />
                            Plataforma educativa
                        </div>
                        <h1 className="text-5xl font-heading font-black text-text-primary leading-[0.9] mb-6">Tu futuro<br />comienza <span className="text-neon-magenta neon-text-cyan">aquí</span></h1>
                        <p className="text-lg text-text-secondary mb-10">Prepárate con simulacros gratuitos y asegura tu ingreso a la universidad.</p>
                        <div className="space-y-5">
                            {features.map((f) => (
                                <div key={f.title} className="flex items-start gap-4 group">
                                    <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-neon-cyan/10 border border-neon-cyan/20 group-hover:bg-neon-cyan/20 transition-all">
                                        <f.icon className="h-5 w-5 text-neon-cyan" />
                                    </div>
                                    <div>
                                        <p className="font-bold text-text-primary">{f.title}</p>
                                        <p className="text-sm text-text-muted">{f.desc}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                        <p className="mt-16 text-sm text-text-muted">© {new Date().getFullYear()} Prepárate y Postula Ya</p>
                    </div>
                </div>

                {/* Right - Form Panel */}
                <div className="flex-1 flex items-center justify-center px-8 py-12">
                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="w-full max-w-md">
                        <div className="text-center mb-8 lg:hidden">
                            <Link href="/" className="inline-flex items-center gap-2 font-heading font-bold text-xl">
                                <div className="cyber-logo" style={{width:32,height:32,fontSize:'0.85rem'}}>P</div>
                                <span className="neon-text-cyan">Prepárate y Postula Ya</span>
                            </Link>
                            <p className="mt-2 text-sm text-text-secondary">Crea tu cuenta para empezar</p>
                        </div>

                        <div className="cyber-card rounded-2xl p-8">
                            <div className="flex items-center gap-2 mb-1">
                                <Sparkles className="h-5 w-5 text-neon-cyan" />
                                <h2 className="text-2xl font-heading font-black text-text-primary">Crear cuenta</h2>
                            </div>
                            <p className="mt-1 text-sm text-text-secondary">Regístrate para acceder a los simulacros</p>

                            {flash?.error && (
                                <div className="mt-4 cyber-card rounded-xl border-neon-magenta/50 p-4 text-sm font-semibold text-neon-magenta">{flash.error}</div>
                            )}

                            <form onSubmit={handleSubmit} className="mt-6 space-y-4">
                                <div>
                                    <label className="block text-sm font-bold text-text-primary mb-1.5">Nombre completo</label>
                                    <div className="relative cyber-input-wrapper">
                                        <User className="cyber-input-icon" />
                                        <input type="text" value={data.name} onChange={(e) => setData('name', e.target.value)} required autoFocus
                                            className="cyber-input block w-full rounded-xl py-3 pl-10 pr-4 text-sm text-text-primary placeholder-text-muted"
                                            placeholder="Tu nombre" />
                                        <span className="neon-cursor">|</span>
                                    </div>
                                    {errors?.name && <p className="mt-1.5 text-xs font-bold text-neon-magenta">{errors.name}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-bold text-text-primary mb-1.5">Correo electrónico</label>
                                    <div className="relative cyber-input-wrapper">
                                        <Mail className="cyber-input-icon" />
                                        <input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required
                                            className="cyber-input block w-full rounded-xl py-3 pl-10 pr-4 text-sm text-text-primary placeholder-text-muted"
                                            placeholder="tu@correo.com" />
                                        <span className="neon-cursor">|</span>
                                    </div>
                                    {errors?.email && <p className="mt-1.5 text-xs font-bold text-neon-magenta">{errors.email}</p>}
                                </div>
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <label className="block text-sm font-bold text-text-primary mb-1.5">Contraseña</label>                                            <div className="relative cyber-input-wrapper">
                                            <Lock className="cyber-input-icon" />
                                            <input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} required
                                                className="cyber-input block w-full rounded-xl py-3 pl-10 pr-4 text-sm text-text-primary placeholder-text-muted"
                                                placeholder="Mín. 8 carac." />
                                            <span className="neon-cursor">|</span>
                                        </div>
                                        {errors?.password && <p className="mt-1.5 text-xs font-bold text-neon-magenta">{errors.password}</p>}
                                    </div>
                                    <div>
                                        <label className="block text-sm font-bold text-text-primary mb-1.5">Confirmar</label>                                            <div className="relative cyber-input-wrapper">
                                            <Lock className="cyber-input-icon" />
                                            <input type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} required
                                                className="cyber-input block w-full rounded-xl py-3 pl-10 pr-4 text-sm text-text-primary placeholder-text-muted"
                                                placeholder="Repite" />
                                            <span className="neon-cursor">|</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label className="block text-sm font-bold text-text-primary mb-1.5">WhatsApp <span className="text-text-muted font-normal">(opcional)</span></label>
                                    <div className="relative cyber-input-wrapper">
                                        <Phone className="cyber-input-icon" />
                                        <input type="text" value={data.whatsapp_numero} onChange={(e) => setData('whatsapp_numero', e.target.value)}
                                            className="cyber-input block w-full rounded-xl py-3 pl-10 pr-4 text-sm text-text-primary placeholder-text-muted"
                                            placeholder="+51 999 888 777" />
                                        <span className="neon-cursor">|</span>
                                    </div>
                                    {errors?.whatsapp_numero && <p className="mt-1.5 text-xs font-bold text-neon-magenta">{errors.whatsapp_numero}</p>}
                                </div>
                                <button type="submit" disabled={processing}
                                    className="cyber-btn-wallet rounded-xl w-full py-3 text-sm justify-center disabled:opacity-50">
                                    {processing ? 'Creando cuenta...' : 'Crear cuenta'}
                                    <ArrowRight className="h-4 w-4" />
                                </button>
                            </form>

                            <div className="relative my-6">
                                <div className="absolute inset-0 flex items-center"><div className="w-full border-t border-cyber-dark-400/50" /></div>
                                <div className="relative flex justify-center"><span className="bg-surface px-4 text-xs font-bold uppercase text-text-muted">O regístrate con</span></div>
                            </div>

                            <a href="/auth/google"
                                className="cyber-btn-wallet rounded-xl w-full py-3 justify-center text-sm gap-3" style={{borderColor:'rgba(0,240,255,0.25)'}}>
                                <svg className="h-5 w-5" viewBox="0 0 24 24">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4" />
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                                </svg>
                                <span>Ingresar con Google</span>
                            </a>

                            <p className="mt-6 text-center text-sm text-text-secondary">
                                ¿Ya tienes cuenta?{' '}
                                <Link href="/login" className="font-bold text-neon-cyan hover:underline neon-text-cyan cyber-nav-link inline-flex">Iniciar sesión</Link>
                            </p>
                        </div>
                    </motion.div>
                </div>
            </div>
        </>
    );
}
