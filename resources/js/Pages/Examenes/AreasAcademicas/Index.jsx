import { Head, router } from '@inertiajs/react';
import { motion } from 'motion/react';
import { Layers, ArrowRight, BookOpen, Brain } from 'lucide-react';

export default function AreasAcademicas({ areas, intentosPorArea }) {
    return (
        <>
            <Head title="Áreas Académicas" />

            <div className="min-h-screen bg-cyber-dark cyber-grid">
                {/* Header */}
                <div className="relative overflow-hidden bg-gradient-to-br from-neon-cyan/10 via-cyber-dark-100 to-cyber-dark pb-12 pt-8">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,rgba(0,240,255,0.06),transparent_50%)]" />
                    <div className="relative mx-auto max-w-5xl px-5 sm:px-8 lg:px-10">
                        <div className="inline-flex items-center gap-2 rounded-full bg-neon-cyan/10 border border-neon-cyan/30 px-4 py-1.5 text-sm font-bold text-neon-cyan mb-4">
                            <Brain className="h-4 w-4" /> Simulacro por Área
                        </div>
                        <h1 className="text-3xl font-heading font-black text-text-primary sm:text-4xl">
                            Elige tu{' '}
                            <span className="neon-text-cyan">Área Académica</span>
                        </h1>
                        <p className="mt-2 max-w-2xl text-sm text-text-muted sm:text-base">
                            Selecciona el área para rendir un simulacro de admisión. El examen se arma automáticamente con preguntas del baúl.
                        </p>
                    </div>
                </div>

                <div className="mx-auto max-w-5xl px-5 sm:px-8 lg:px-10 mt-6 pb-12">
                    <div className="grid gap-5 sm:grid-cols-2">
                        {areas.map((area) => {
                            const intentos = intentosPorArea[area.id] || 0;
                            return (
                                <motion.div
                                    key={area.id}
                                    initial={{ opacity: 0, y: 20 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    className="cyber-card rounded-xl overflow-hidden group hover:-translate-y-1 transition-all"
                                >
                                    {/* Card header gradient - cyan with subtle accent */}
                                    <div className="bg-gradient-to-r from-neon-cyan/40 to-neon-cyan/10 px-5 py-5">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm border border-white/20">
                                                    <Layers className="h-6 w-6 text-white" />
                                                </div>
                                                <div>
                                                    <h3 className="font-heading font-bold text-white text-lg leading-tight">{area.nombre}</h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="p-5">
                                        {area.descripcion && (
                                            <p className="text-sm text-text-secondary mb-4 line-clamp-2">{area.descripcion}</p>
                                        )}

                                        <div className="grid grid-cols-3 gap-3 mb-4">
                                            <div className="rounded-xl bg-cyber-dark-300 border border-cyber-dark-400/30 p-3 text-center">
                                                <p className="text-lg font-heading font-bold text-neon-cyan">{area.total_preguntas}</p>
                                                <p className="text-[10px] font-bold text-text-muted uppercase">Preguntas</p>
                                            </div>
                                            <div className="rounded-xl bg-cyber-dark-300 border border-cyber-dark-400/30 p-3 text-center">
                                                <p className="text-lg font-heading font-bold text-neon-cyan">{area.duracion_min}</p>
                                                <p className="text-[10px] font-bold text-text-muted uppercase">Minutos</p>
                                            </div>
                                            <div className="rounded-xl bg-cyber-dark-300 border border-cyber-dark-400/30 p-3 text-center">
                                                <p className="text-lg font-heading font-bold text-neon-cyan">{intentos}</p>
                                                <p className="text-[10px] font-bold text-text-muted uppercase">Intentos</p>
                                            </div>
                                        </div>

                                        <button
                                            onClick={() => {
                                                router.get(`/examenes/areas-academicas/${area.id}/tipos`);
                                            }}
                                            disabled={area.total_preguntas === 0}
                                            className={`w-full inline-flex items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-heading font-bold transition-all ${
                                                area.total_preguntas > 0
                                                    ? 'cyber-btn cyber-btn-primary'
                                                    : 'bg-cyber-dark-300 text-text-muted cursor-not-allowed border border-cyber-dark-400/30'
                                            }`}
                                        >
                                            {area.total_preguntas > 0 ? (
                                                <>
                                                    Ver simulacros
                                                    <ArrowRight className="h-4 w-4" />
                                                </>
                                            ) : (
                                                <>
                                                    <BookOpen className="h-4 w-4" />
                                                    Sin preguntas disponibles
                                                </>
                                            )}
                                        </button>
                                    </div>
                                </motion.div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </>
    );
}
