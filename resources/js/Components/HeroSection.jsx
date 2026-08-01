import { Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import { GraduationCap, ArrowRight, BookOpen, Users, Award } from 'lucide-react';

const stats = [
    { icon: GraduationCap, valor: '15+', label: 'Universidades' },
    { icon: BookOpen, valor: '50+', label: 'Simulacros' },
    { icon: Users, valor: '500+', label: 'Estudiantes' },
    { icon: Award, valor: '100%', label: 'Gratis' },
];

export default function HeroSection() {
    return (
        <section className="relative overflow-hidden bg-white pt-24">
            <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                <div className="grid items-center gap-12 lg:grid-cols-2">
                    <motion.div
                        initial={{ opacity: 0, x: -30 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ duration: 0.5 }}
                        className="py-16 sm:py-24"
                    >
                        <div className="mx-auto mb-6 inline-flex items-center gap-2 rounded-sm border border-upc-red/20 bg-upc-red/5 px-4 py-2 text-sm font-sans text-upc-red">
                            <GraduationCap className="h-4 w-4" />
                            Plataforma gratuita de preparación
                        </div>

                        <h1 className="font-sans font-bold text-4xl leading-tight tracking-tight text-tertiary-800 sm:text-5xl md:text-6xl lg:text-7xl">
                            ¿Estás listo para{' '}
                            <span className="inline-block px-2 text-upc-purple" style={{ backgroundColor: '#F7F27D' }}>
                                postular
                            </span>
                            {' '}a la universidad?
                        </h1>

                        <p className="mx-auto mt-6 max-w-2xl font-sans text-lg leading-relaxed text-upc-grey sm:text-xl">
                            Prepárate con simulacros realistas, mide tu avance y llega con confianza
                            a tu examen de admisión. <strong className="text-tertiary-800 font-sans font-bold">100% gratuito.</strong>
                        </p>

                        <div className="mt-10 flex flex-col items-start gap-4 sm:flex-row">
                            <a
                                href="/register"
                                className="group inline-flex w-full items-center justify-center gap-3 rounded-sm bg-upc-red px-10 py-4 font-sgothic text-base font-bold uppercase tracking-wider text-white shadow-xl transition-all hover:bg-upc-red-hover hover:shadow-2xl sm:w-auto sm:text-lg"
                            >
                                Empieza tu simulacro gratis
                                <ArrowRight className="h-5 w-5 transition-transform group-hover:translate-x-1" />
                            </a>
                            <Link
                                href="#carreras"
                                className="inline-flex w-full items-center justify-center gap-2 rounded-sm border-2 border-tertiary-200 bg-white px-10 py-4 font-sgothic text-base font-bold uppercase tracking-wider text-tertiary-700 transition-all hover:bg-tertiary-100 sm:w-auto sm:text-lg"
                            >
                                Ver carreras
                            </Link>
                        </div>

                        <div className="mt-12 grid grid-cols-2 gap-6 border-t border-tertiary-100 pt-10 sm:grid-cols-4">
                            {stats.map((stat, i) => (
                                <motion.div
                                    key={stat.label}
                                    initial={{ opacity: 0, y: 20 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ duration: 0.4, delay: 0.3 + i * 0.1 }}
                                    className="text-center"
                                >
                                    <stat.icon className="mx-auto mb-2 h-6 w-6 text-upc-red" />
                                    <p className="font-sans font-bold text-3xl text-tertiary-800 sm:text-4xl">{stat.valor}</p>
                                    <p className="mt-1 font-sans text-sm text-upc-grey">{stat.label}</p>
                                </motion.div>
                            ))}
                        </div>
                    </motion.div>

                    <motion.div
                        initial={{ opacity: 0, x: 30 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ duration: 0.5, delay: 0.2 }}
                        className="relative hidden lg:block"
                    >
                        <div className="relative overflow-hidden rounded-sm shadow-2xl">
                            <img
                                src="/images/Erick_Hartmann-desktop.webp"
                                alt="Erick Hartmann - Estudiante UPC"
                                className="w-full object-cover"
                                loading="eager"
                            />
                            <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-white/80 to-transparent p-6">
                                <p className="font-sgothic text-lg text-tertiary-800">Prepárate con nosotros</p>
                                <p className="font-sans text-sm text-upc-grey">Simulacros gratuitos</p>
                            </div>
                        </div>
                    </motion.div>
                </div>
            </div>
        </section>
    );
}
