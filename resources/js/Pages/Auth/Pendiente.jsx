import { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { motion } from 'motion/react';
import { GraduationCap, Clock, Phone, Send, Sparkles, CheckCircle, AlertTriangle } from 'lucide-react';
import { normalizarWhatsApp } from '@/lib/utils';

export default function Pendiente({ userEmail, userName, whatsappNumero }) {
    const { flash } = usePage().props;
    const { data, setData, post, processing, errors, transform } = useForm({
        whatsapp_numero: whatsappNumero || '',
    });
    const [clienteInvalido, setClienteInvalido] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        if (!normalizarWhatsApp(data.whatsapp_numero)) {
            setClienteInvalido(true);
            return;
        }
        setClienteInvalido(false);
        transform((values) => ({
            ...values,
            whatsapp_numero: normalizarWhatsApp(values.whatsapp_numero),
        }));
        post('/pendiente/whatsapp');
    };

    return (
        <>
            <Head title="Cuenta Pendiente" />
            <div className="min-h-screen bg-cyber-dark flex items-center justify-center px-5 py-12 cyber-grid">
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="w-full max-w-lg"
                >
                    <div className="cyber-card rounded-2xl p-8 sm:p-10 text-center">
                        <div className="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-neon-yellow-50 border border-neon-yellow/50 shadow-neon-cyan cyber-card-animated">
                            <Clock className="h-10 w-10 text-neon-yellow" />
                        </div>

                        <div className="cyber-badge cyber-badge-cyan rounded-lg px-4 py-2 mb-4 inline-flex">
                            <Sparkles className="h-4 w-4" />
                            Pendiente de aprobación
                        </div>

                        {/* Flash messages */}
                        {flash?.success && (
                            <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                                className="mb-4 flex items-center gap-3 rounded-xl border border-neon-green/30 bg-neon-green/10 px-4 py-3 text-left">
                                <CheckCircle className="h-5 w-5 flex-shrink-0 text-neon-green" />
                                <p className="text-sm font-bold text-neon-green">{flash.success}</p>
                            </motion.div>
                        )}
                        {flash?.error && (
                            <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                                className="mb-4 flex items-center gap-3 rounded-xl border border-neon-magenta/30 bg-neon-magenta/10 px-4 py-3 text-left">
                                <AlertTriangle className="h-5 w-5 flex-shrink-0 text-neon-magenta" />
                                <p className="text-sm font-bold text-neon-cyan">{flash.error}</p>
                            </motion.div>
                        )}

                        <h1 className="text-3xl font-heading font-black text-text-primary neon-text-cyan">
                            ¡Casi listo!
                        </h1>

                        {userName && (
                            <p className="mt-2 text-text-secondary font-semibold">
                                Bienvenido, <span className="text-text-primary">{userName}</span>
                            </p>
                        )}

                        {userEmail && (
                            <div className="mt-4 inline-flex items-center gap-2 rounded-xl bg-surface border border-cyber-dark-400/50 px-5 py-3">
                                <span className="text-sm text-text-muted">Registrado como:</span>
                                <span className="text-sm font-bold text-text-primary">{userEmail}</span>
                            </div>
                        )}

                        <p className="mt-4 text-text-secondary leading-relaxed">
                            Tu registro ha sido recibido. Un administrador revisará tu cuenta y la activará para que puedas acceder a los simulacros.
                        </p>

                        {/* WhatsApp section: ocultar formulario si ya está registrado */}
                        <div className="mt-8 border-t border-cyber-dark-400/50 pt-6">
                            {whatsappNumero ? (
                                <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }}
                                    className="flex items-center justify-center gap-3 rounded-xl border border-neon-green/30 bg-neon-green/10 px-5 py-4">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-neon-green/20">
                                        <CheckCircle className="h-5 w-5 text-neon-green" />
                                    </div>
                                    <div className="text-left">
                                        <p className="text-sm font-bold text-neon-green">WhatsApp registrado</p>
                                        <p className="text-xs font-semibold text-text-muted">{whatsappNumero}</p>
                                    </div>
                                </motion.div>
                            ) : (
                                <>
                                    <div className="mb-4 flex items-center justify-center gap-2 text-sm text-text-secondary">
                                        <Phone className="h-4 w-4 text-neon-green" />
                                        <span className="font-semibold">Agrega tu WhatsApp para enviarte tu aprobación</span>
                                    </div>

                                    <form onSubmit={submit} className="flex flex-col gap-3">
                                        <div className="relative cyber-input-wrapper">
                                            <Phone className="cyber-input-icon" />
                                            <input type="text" value={data.whatsapp_numero} onChange={(e) => setData('whatsapp_numero', e.target.value)}
                                                placeholder="+51 999 888 777"
                                                className="cyber-input block w-full rounded-xl py-3 pl-10 pr-4 text-sm text-text-primary placeholder-text-muted" />
                                            <span className="neon-cursor">|</span>
                                        </div>
                                        {errors?.whatsapp_numero && (
                                            <p className="mt-1 text-xs font-bold text-neon-cyan/80 text-left">{errors.whatsapp_numero}</p>
                                        )}
                                        {clienteInvalido && (
                                            <p className="mt-1 text-xs font-bold text-neon-cyan/80 text-left">Ingresa un número de WhatsApp válido de 9 dígitos (ej: 999 888 777).</p>
                                        )}
                                        <button type="submit" disabled={processing || !data.whatsapp_numero}
                                            className="cyber-btn-wallet rounded-xl py-3 text-sm justify-center disabled:opacity-50">
                                            <Send className="h-4 w-4" />
                                            {processing ? 'Enviando...' : 'Enviar número de WhatsApp'}
                                        </button>
                                    </form>
                                </>
                            )}
                        </div>

                        <div className="mt-6">
                            <Link href="/logout" method="post" as="button"
                                className="cyber-btn-wallet-ghost rounded-xl px-5 py-2 text-sm">
                                Cerrar sesión
                            </Link>
                        </div>
                    </div>
                    <p className="mt-6 text-xs text-text-muted text-center">
                        <span className="flex items-center justify-center gap-1 font-semibold">
                            <GraduationCap className="h-3 w-3" />
                            Prepárate y Postula Ya — Simulacros gratuitos
                        </span>
                    </p>
                </motion.div>
            </div>
        </>
    );
}
