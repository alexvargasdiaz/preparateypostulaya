import { Head, Link, router } from '@inertiajs/react';
import { motion } from 'motion/react';
import { ArrowLeft, Layers, Clock, HelpCircle, Play, FileText } from 'lucide-react';

export default function Tipos({ area, examenes, intentoActivo }) {
    const iniciarExamen = (examenId) => {
        router.post(`/examenes/areas-academicas/${area.id}/examenes/${examenId}/iniciar`);
    };

    return (
        <>
            <Head title={`${area.nombre} - Simulacros`} />

            <div className="min-h-screen bg-cyber-dark cyber-grid">
                <div className="relative overflow-hidden bg-gradient-to-br from-neon-cyan/15 via-cyber-dark-100 to-cyber-dark py-12">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(0,240,255,0.1),transparent_70%)]" />
                    <div className="mx-auto max-w-5xl px-5 sm:px-8 lg:px-10">
                        <Link href="/examenes/areas-academicas"
                            className="inline-flex items-center gap-1.5 text-sm font-semibold text-text-muted hover:text-white transition-colors mb-4">
                            <ArrowLeft className="h-4 w-4" /> Volver a Áreas
                        </Link>
                        <div className="flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-neon-cyan to-neon-cyan/60 shadow-[0_0_15px_rgba(0,240,255,0.3)]">
                                <Layers className="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-heading font-black text-text-primary neon-text-cyan">{area.nombre}</h1>
                                <p className="text-sm font-semibold text-text-muted">{area.total_preguntas} preguntas disponibles</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-5xl px-5 sm:px-8 lg:px-10 mt-6 pb-12">
                    {intentoActivo && (
                        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }}
                            className="mb-6 cyber-card rounded-xl p-5 border-neon-cyan/30">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-neon-cyan/15 border border-neon-cyan/30">
                                        <Play className="h-5 w-5 text-neon-cyan" />
                                    </div>
                                    <div>
                                        <p className="font-heading font-bold text-text-primary neon-text-cyan">Tienes un simulacro en curso</p>
                                        <p className="text-sm font-semibold text-text-muted">Continúa donde lo dejaste</p>
                                    </div>
                                </div>
                                <Link href={`/examenes/intento/${intentoActivo.id}`}
                                    className="cyber-btn cyber-btn-primary rounded-xl px-6 py-2.5 text-sm font-bold">
                                    Continuar
                                </Link>
                            </div>
                        </motion.div>
                    )}

                    {examenes?.length > 0 && (
                        <div className="mb-8">
                            <h2 className="text-lg font-heading font-bold text-text-primary mb-3">
                                <span className="neon-text-cyan">Exámenes disponibles</span>
                            </h2>
                            <div className="grid gap-4 sm:grid-cols-2">
                                {examenes.map((ex, idx) => (
                                    <motion.div key={ex.id} initial={{ opacity: 0, y: 15 }} animate={{ opacity: 1, y: 0 }}
                                        transition={{ delay: idx * 0.05 }}
                                        className="cyber-card rounded-xl p-5 hover:border-neon-cyan/30 transition-all">
                                        <h3 className="font-heading font-bold text-text-primary">{ex.titulo}</h3>
                                        {ex.descripcion && (
                                            <p className="mt-1 text-xs font-semibold text-text-muted line-clamp-2">{ex.descripcion}</p>
                                        )}
                                        <div className="mt-3 flex items-center gap-4 text-xs font-semibold text-text-muted">
                                            <span className="flex items-center gap-1">
                                                <HelpCircle className="h-3.5 w-3.5 text-neon-cyan" />
                                                {ex.preguntas_totales} preguntas
                                            </span>
                                            <span className="flex items-center gap-1">
                                                <Clock className="h-3.5 w-3.5 text-neon-cyan/70" />
                                                {ex.tiempo_limite_min} min
                                            </span>
                                        </div>
                                        <button onClick={() => iniciarExamen(ex.id)}
                                            className="mt-4 w-full cyber-btn cyber-btn-primary rounded-xl py-2.5 text-sm font-bold">
                                            Iniciar simulacro
                                        </button>
                                    </motion.div>
                                ))}
                            </div>
                        </div>
                    )}

                    {(!examenes || examenes.length === 0) && (
                        <motion.div initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }}
                            className="cyber-card rounded-xl p-12 text-center">
                            <FileText className="mx-auto h-12 w-12 text-text-muted mb-4" />
                            <p className="text-lg font-heading font-bold text-text-primary">Sin simulacros disponibles</p>
                            <p className="text-sm text-text-muted font-semibold">Esta área no tiene exámenes configurados aún.</p>
                        </motion.div>
                    )}
                </div>
            </div>
        </>
    );
}
