import { motion } from 'motion/react';
import { Quote, Star } from 'lucide-react';

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

export default function TestimonialsSection({ testimonios = [] }) {
    const items = testimonios.length > 0 ? testimonios : [
        {
            nombre: 'Carlos Mendoza',
            carrera: 'Ingeniería de Sistemas',
            universidad: 'UPC',
            texto: 'Los simulacros me ayudaron a identificar mis puntos débiles. Ingresé en mi primer intento gracias a la retroalimentación por temas.',
            nota: '18/20',
        },
        {
            nombre: 'Valeria Torres',
            carrera: 'Medicina Humana',
            universidad: 'UNMSM',
            texto: 'La retroalimentación por concepto es lo mejor. Supe exactamente qué estudiar para cada área y optimicé mi tiempo de preparación.',
            nota: '16/20',
        },
        {
            nombre: 'Diego Paredes',
            carrera: 'Arquitectura',
            universidad: 'Universidad de Lima',
            texto: '100% recomendado. Los simulacros son muy parecidos al examen real. El temporizador me ayudó a manejar mi tiempo durante la prueba.',
            nota: '17/20',
        },
    ];

    return (
        <section className="bg-white py-20 sm:py-28">
            <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true, margin: '-50px' }}
                    transition={{ duration: 0.5 }}
                    className="mx-auto max-w-3xl text-center"
                >
                    <span className="inline-block rounded-sm bg-upc-red px-4 py-1.5 font-sgothic text-xs font-bold uppercase tracking-widest text-white">
                        Historias de éxito
                    </span>
                    <h2 className="mt-4 font-sans font-bold text-3xl tracking-tight text-tertiary-800 sm:text-4xl lg:text-5xl">
                        Lo que dicen nuestros{' '}
                        <span className="text-upc-red">estudiantes</span>
                    </h2>
                </motion.div>

                <motion.div
                    variants={containerVariants}
                    initial="hidden"
                    whileInView="visible"
                    viewport={{ once: true, margin: '-50px' }}
                    className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    {items.map((t, i) => (
                        <motion.div
                            key={i}
                            variants={itemVariants}
                            whileHover={{ y: -4, transition: { duration: 0.2 } }}
                            className="group rounded-sm border border-upc-red/10 bg-white p-6 shadow-sm transition-all hover:shadow-lg"
                        >
                            <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-sm bg-upc-red/10">
                                <Quote className="h-5 w-5 text-upc-red" />
                            </div>

                            <p className="font-sans text-sm leading-relaxed text-upc-grey">{t.texto}</p>

                            <div className="mt-4 flex gap-1">
                                {[...Array(5)].map((_, j) => (
                                    <Star key={j} className="h-4 w-4 fill-upc-red text-upc-red" />
                                ))}
                            </div>

                            <div className="mt-6 flex items-center gap-4 border-t border-upc-red/10 pt-4">
                                <div className="flex h-12 w-12 items-center justify-center rounded-sm bg-upc-red font-sgothic text-sm font-bold text-white shadow-md">
                                    {t.nombre.charAt(0)}
                                </div>
                                <div className="flex-1">
                                    <p className="font-sans font-bold text-sm text-tertiary-800">{t.nombre}</p>
                                    <p className="font-sans text-xs text-upc-grey">{t.carrera} — {t.universidad}</p>
                                </div>
                                <div className="rounded-sm bg-upc-red/10 px-2.5 py-1 font-sgothic text-xs font-bold text-upc-red">
                                    {t.nota}
                                </div>
                            </div>
                        </motion.div>
                    ))}
                </motion.div>
            </div>
        </section>
    );
}
