import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { motion, AnimatePresence } from 'motion/react';
import { BookOpen, ChevronRight, ChevronDown, ChevronUp } from 'lucide-react';

export default function CategoriesGrid({ facultades = [] }) {
    const [activeTab, setActiveTab] = useState(null);
    const [mobileOpen, setMobileOpen] = useState(false);

    if (facultades.length === 0) {
        return (
            <section id="carreras" className="bg-upc-light py-20 sm:py-28">
                <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10 text-center">
                    <p className="font-sans text-upc-grey">Próximamente más carreras disponibles.</p>
                </div>
            </section>
        );
    }

    const tabActual = activeTab || facultades[0]?.nombre;
    const facultadActiva = facultades.find((f) => f.nombre === tabActual) || facultades[0];

    const handleSelect = (nombre) => {
        setActiveTab(nombre);
        setMobileOpen(false);
    };

    return (
        <section id="carreras" className="py-16 sm:py-20">
            <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true, margin: '-50px' }}
                    transition={{ duration: 0.5 }}
                    className="mb-10"
                >
                    <span className="inline-block rounded-sm bg-upc-red px-4 py-1.5 font-sgothic text-xs font-bold uppercase tracking-widest text-white">
                        Facultades y Carreras
                    </span>
                    <h2 className="mt-4 font-sans font-bold text-3xl tracking-tight text-tertiary-800 sm:text-4xl">
                        Simulacros por{' '}
                        <span className="text-upc-red">área de estudio</span>
                    </h2>
                    <p className="mt-3 font-sans text-base text-upc-grey">
                        Encuentra los simulacros según tu área de interés.
                    </p>
                </motion.div>

                <div className="grid grid-cols-1 gap-0 lg:grid-cols-12">
                    <div className="lg:col-span-4 xl:col-span-3">
                        <div className="border border-tertiary-200 bg-white">
                            <ul className="m-0 list-none p-0">
                                {facultades.map((fac) => {
                                    const isActive = tabActual === fac.nombre;
                                    return (
                                        <li
                                            key={fac.nombre}
                                            onClick={() => handleSelect(fac.nombre)}
                                            className={`cursor-pointer border-b border-tertiary-100 transition-colors ${
                                                isActive ? 'bg-upc-red text-white' : 'text-tertiary-700 hover:bg-tertiary-100'
                                            }`}
                                        >
                                            <div className="flex items-center justify-between px-5 py-4">
                                                <h3 className={`font-sans text-base ${isActive ? 'text-white font-sans font-bold' : ''}`}>{fac.nombre}</h3>
                                                <ChevronRight className={`h-5 w-5 transition-transform ${isActive ? 'rotate-90 text-white' : 'text-tertiary-400'} hidden lg:block`} />
                                                {isActive ? (
                                                    <ChevronUp className="h-5 w-5 text-white lg:hidden" />
                                                ) : (
                                                    <ChevronDown className="h-5 w-5 text-tertiary-400 lg:hidden" />
                                                )}
                                            </div>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    </div>

                    <div className="border border-upc-red/10 bg-white lg:col-span-8 xl:col-span-9">
                        <AnimatePresence mode="wait">
                            <motion.div
                                key={tabActual}
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                exit={{ opacity: 0 }}
                                transition={{ duration: 0.3 }}
                            >
                                <div className="border-b border-upc-red/10 bg-upc-red/5 p-6 sm:p-8">
                                    <div className="flex items-center gap-4">
                                        <div className="flex h-14 w-14 items-center justify-center rounded-sm bg-upc-red text-white">
                                            <BookOpen className="h-7 w-7" />
                                        </div>
                                        <div>
                                            <h3 className="font-sans font-bold text-xl text-upc-red sm:text-2xl">
                                                {facultadActiva.nombre}
                                            </h3>
                                            <p className="mt-1 font-sans text-sm text-upc-grey">
                                                {facultadActiva.instituciones_count || 0} universidades · {facultadActiva.examenes_count || 0} simulacros
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div className="p-6 sm:p-8">
                                    <p className="mb-4 font-sgothic text-xs font-bold uppercase tracking-wider text-upc-grey">
                                        Carreras disponibles
                                    </p>
                                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                        {facultadActiva.carreras?.length > 0 ? (
                                            facultadActiva.carreras.map((carrera, i) => (
                                                <motion.div
                                                    key={carrera}
                                                    initial={{ opacity: 0, y: 10 }}
                                                    animate={{ opacity: 1, y: 0 }}
                                                    transition={{ duration: 0.3, delay: i * 0.05 }}
                                                >
                                                    <Link
                                                        href="/auth/google"
                                                        className="group flex items-center gap-3 rounded-sm border border-upc-red/10 bg-white p-4 transition-all hover:border-upc-red/30 hover:shadow-md"
                                                    >
                                                        <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-sm bg-upc-red/10 text-upc-red transition-colors group-hover:bg-upc-red group-hover:text-white">
                                                            <BookOpen className="h-5 w-5" />
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <p className="font-sans font-bold truncate text-sm text-tertiary-800 group-hover:text-upc-red">
                                                                {carrera}
                                                            </p>
                                                            <p className="mt-0.5 flex items-center gap-1 font-sans text-xs text-upc-grey group-hover:text-upc-red">
                                                                Ver simulacros <ChevronRight className="h-3 w-3" />
                                                            </p>
                                                        </div>
                                                    </Link>
                                                </motion.div>
                                            ))
                                        ) : (
                                            <p className="font-sans text-sm text-upc-grey col-span-full">
                                                Próximamente más carreras disponibles.
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </motion.div>
                        </AnimatePresence>
                    </div>
                </div>

                <div className="mt-6 text-center">
                    <Link
                        href="/auth/google"
                        className="group inline-flex items-center gap-2 rounded-sm bg-upc-red px-8 py-3 font-sgothic text-sm font-bold uppercase tracking-wider text-white shadow-lg transition-all hover:bg-upc-red-hover hover:shadow-xl"
                    >
                        Ver todos los simulacros disponibles
                        <ChevronRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                    </Link>
                </div>
            </div>
        </section>
    );
}
