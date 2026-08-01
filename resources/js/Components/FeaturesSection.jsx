import { Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import { Award, Target, TrendingUp, Gift, ArrowRight, Play } from 'lucide-react';

const razones = [
    {
        icon: Award,
        titulo: 'CALIDAD ACADÉMICA CERTIFICADA',
        desc: 'Simulacros diseñados con el mismo formato y nivel de dificultad que los exámenes de admisión reales de las principales universidades.',
        stat: '15+ universidades',
    },
    {
        icon: Target,
        titulo: 'RETROALIMENTACIÓN INMEDIATA',
        desc: 'Al terminar, sabrás exactamente qué temas dominas y cuáles necesitas reforzar, con mensajes de ayuda personalizados por concepto.',
        stat: 'Desglose por temas',
    },
    {
        icon: TrendingUp,
        titulo: 'ALTA EFECTIVIDAD',
        desc: '9 de cada 10 estudiantes que usan nuestros simulacros mejoran su puntaje en el segundo intento. Seguimiento de tu progreso real.',
        stat: '90% mejora su puntaje',
    },
    {
        icon: Gift,
        titulo: '100% GRATIS Y SIN COMPROMISO',
        desc: 'Sin tarjetas, sin límites, sin anuncios. Todos los simulacros, resultados y herramientas son completamente gratuitos para todos.',
        stat: 'Sin costo alguno',
    },
];

const containerVariants = {
    hidden: {},
    visible: {
        transition: { staggerChildren: 0.15 },
    },
};

const itemVariants = {
    hidden: { opacity: 0, y: 30 },
    visible: { opacity: 1, y: 0, transition: { duration: 0.5 } },
};

export default function FeaturesSection({ onPlayVideo }) {
    return (
        <section className="bg-upc-light py-20 sm:py-28">
            <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                <div className="grid items-center gap-12 lg:grid-cols-2">
                    <motion.div
                        initial={{ opacity: 0, x: -30 }}
                        whileInView={{ opacity: 1, x: 0 }}
                        viewport={{ once: true, margin: '-50px' }}
                        transition={{ duration: 0.6 }}
                    >
                        <span className="inline-block rounded-sm bg-upc-red px-4 py-1.5 font-sgothic text-xs font-bold uppercase tracking-widest text-white">
                            ¿Por qué prepararte con nosotros?
                        </span>
                        <h2 className="mt-6 font-sans font-bold text-3xl leading-tight text-tertiary-800 sm:text-4xl lg:text-5xl">
                            Todo lo que necesitas para{' '}
                            <span className="inline-block px-1 text-upc-purple" style={{ backgroundColor: '#F7F27D' }}>ingresar</span> a la universidad
                        </h2>
                        <p className="mt-4 font-sans text-lg leading-relaxed text-upc-grey">
                            Más que un simulacro, una herramienta completa de preparación con
                            retroalimentación inteligente.
                        </p>

                        <div className="mt-8 flex flex-wrap gap-4">
                            {['Simulacros realistas', 'Resultados inmediatos', '100% gratuito'].map((tag) => (
                                <span
                                    key={tag}
                                    className="rounded-sm border border-upc-red/30 bg-white px-4 py-2 font-sgothic text-xs font-bold uppercase tracking-wider text-upc-red"
                                >
                                    {tag}
                                </span>
                            ))}
                        </div>

                        <Link
                            href="/auth/google"
                            className="group mt-10 inline-flex items-center gap-2 rounded-sm bg-upc-red px-8 py-3.5 font-sgothic text-sm font-bold uppercase tracking-wider text-white shadow-lg transition-all hover:bg-upc-red-hover hover:shadow-xl"
                        >
                            Comienza tu preparación ahora
                            <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </Link>
                    </motion.div>

                    <motion.div
                        initial={{ opacity: 0, x: 30 }}
                        whileInView={{ opacity: 1, x: 0 }}
                        viewport={{ once: true, margin: '-50px' }}
                        transition={{ duration: 0.6 }}
                        className="relative"
                    >
                        <div className="relative overflow-hidden rounded-sm shadow-2xl">
                            <img
                                src="/images/banner-compartiendo-ideas.png"
                                alt="Estudiantes UPC"
                                className="w-full object-cover"
                            />
                            <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
                            <button
                                onClick={() => onPlayVideo?.('https://www.youtube.com/embed/dQw4w9WgXcQ')}
                                className="video-play-upc"
                                aria-label="Reproducir video"
                            />
                            <div className="absolute bottom-0 left-0 right-0 p-6">
                                <p className="font-sgothic text-lg text-white">Mira cómo funciona</p>
                                <p className="font-sans text-sm text-white/70">Video testimonial de 2 min</p>
                            </div>
                        </div>
                    </motion.div>
                </div>

                <motion.div
                    variants={containerVariants}
                    initial="hidden"
                    whileInView="visible"
                    viewport={{ once: true, margin: '-50px' }}
                    className="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4"
                >
                    {razones.map((r) => (
                        <motion.div
                            key={r.titulo}
                            variants={itemVariants}
                            whileHover={{ y: -4, transition: { duration: 0.2 } }}
                            className="group rounded-sm border border-upc-red/10 bg-white p-6 shadow-sm transition-all hover:shadow-lg"
                        >
                            <div className="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-sm bg-upc-red shadow-md">
                                <r.icon className="h-7 w-7 text-white" />
                            </div>

                            <h3 className="font-sgothic text-sm font-bold uppercase tracking-wider text-tertiary-800">
                                {r.titulo}
                            </h3>
                            <p className="mt-3 font-sans text-sm leading-relaxed text-upc-grey">
                                {r.desc}
                            </p>

                            <div className="mt-4 flex items-center gap-2 font-sgothic text-xs font-bold text-upc-red">
                                <span className="inline-block h-1.5 w-1.5 rounded-full bg-upc-red" />
                                {r.stat}
                            </div>
                        </motion.div>
                    ))}
                </motion.div>
            </div>
        </section>
    );
}
