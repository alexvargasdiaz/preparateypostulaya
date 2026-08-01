import { useState, useEffect, useRef, useCallback } from 'react';
import { Building2, School, ChevronLeft, ChevronRight } from 'lucide-react';

export default function InstitutionsList({ instituciones = {} }) {
    const allItems = [
        ...(instituciones.publica || []),
        ...(instituciones.privada || []),
    ];

    if (allItems.length === 0) return null;

    const scrollRef = useRef(null);
    const [canScrollLeft, setCanScrollLeft] = useState(false);
    const [canScrollRight, setCanScrollRight] = useState(true);

    const checkScroll = useCallback(() => {
        const el = scrollRef.current;
        if (!el) return;
        setCanScrollLeft(el.scrollLeft > 10);
        setCanScrollRight(el.scrollLeft < el.scrollWidth - el.clientWidth - 10);
    }, []);

    useEffect(() => {
        checkScroll();
        const el = scrollRef.current;
        if (!el) return;
        el.addEventListener('scroll', checkScroll, { passive: true });
        return () => el.removeEventListener('scroll', checkScroll);
    }, [checkScroll]);

    const scroll = (dir) => {
        const el = scrollRef.current;
        if (!el) return;
        el.scrollBy({ left: dir * 340, behavior: 'smooth' });
    };

    useEffect(() => {
        const el = scrollRef.current;
        if (!el || allItems.length <= 3) return;

        let paused = false;
        let animId;
        let lastTime = performance.now();
        const SPEED = 40;

        const tick = (now) => {
            if (!paused && el) {
                const delta = (now - lastTime) / 1000;
                el.scrollLeft += SPEED * delta;
                if (el.scrollLeft >= el.scrollWidth - el.clientWidth) {
                    el.scrollLeft = 0;
                }
            }
            lastTime = now;
            animId = requestAnimationFrame(tick);
        };

        animId = requestAnimationFrame(tick);

        const onEnter = () => (paused = true);
        const onLeave = () => { paused = false; lastTime = performance.now(); };
        el.addEventListener('mouseenter', onEnter);
        el.addEventListener('mouseleave', onLeave);

        return () => {
            cancelAnimationFrame(animId);
            el.removeEventListener('mouseenter', onEnter);
            el.removeEventListener('mouseleave', onLeave);
        };
    }, [allItems.length]);

    return (
        <section className="bg-white py-16 sm:py-20 overflow-hidden">
            <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                <div className="flex items-end justify-between mb-10">
                    <div>
                        <h2 className="font-sans font-bold text-3xl tracking-tight text-tertiary-800 sm:text-4xl">
                            Universidades disponibles
                        </h2>
                        <p className="mt-3 font-sans text-lg text-upc-grey">
                            Simulacros de preparación para las principales universidades del Perú
                        </p>
                    </div>
                    <div className="hidden sm:flex items-center gap-2">
                        <button
                            onClick={() => scroll(-1)}
                            disabled={!canScrollLeft}
                            className="rounded-sm border border-upc-red/20 bg-white p-2 text-upc-grey shadow-sm transition-all hover:bg-upc-red/5 disabled:opacity-30 disabled:cursor-not-allowed"
                        >
                            <ChevronLeft className="h-5 w-5" />
                        </button>
                        <button
                            onClick={() => scroll(1)}
                            disabled={!canScrollRight}
                            className="rounded-sm border border-upc-red/20 bg-white p-2 text-upc-grey shadow-sm transition-all hover:bg-upc-red/5 disabled:opacity-30 disabled:cursor-not-allowed"
                        >
                            <ChevronRight className="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>

            <div
                ref={scrollRef}
                className="flex gap-6 overflow-x-auto px-4 sm:px-[max(1rem,calc((100vw-80rem)/2+1.5rem))] pb-4"
                style={{ scrollbarWidth: 'none', msOverflowStyle: 'none', scrollBehavior: 'auto' }}
            >
                {allItems.map((inst) => (
                    <div
                        key={inst.id}
                        className="flex-shrink-0 w-80 rounded-sm border border-upc-red/10 bg-white shadow-sm transition-all duration-300 hover:border-upc-red/30 hover:shadow-xl hover:-translate-y-1.5 group overflow-hidden"
                    >
                        <div className="relative h-48 w-full overflow-hidden bg-upc-light">
                            {inst.logo_url ? (
                                <img
                                    src={inst.logo_url}
                                    alt={inst.nombre}
                                    className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-110"
                                />
                            ) : (
                                <div className="flex h-full w-full items-center justify-center bg-upc-red/5">
                                    <span className="font-sans font-bold text-6xl text-upc-red/20">
                                        {inst.nombre.charAt(0)}
                                    </span>
                                </div>
                            )}
                            <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent" />
                            <span className={`absolute top-3 right-3 inline-flex items-center gap-1 rounded-sm px-2.5 py-1 font-sgothic text-xs font-bold uppercase tracking-wider shadow-md ${
                                inst.subtipo === 'publica'
                                    ? 'bg-upc-red text-white'
                                    : 'bg-tertiary-100 text-tertiary-700'
                            }`}>
                                {inst.subtipo === 'publica' ? (
                                    <><Building2 size={12} /> Pública</>
                                ) : (
                                    <><School size={12} /> Privada</>
                                )}
                            </span>
                        </div>

                        <div className="p-5">
                            <h3 className="font-sans font-bold text-base text-tertiary-800 group-hover:text-upc-red transition-colors duration-300 leading-tight">
                                {inst.nombre}
                            </h3>
                            {inst.ciudad && (
                                <p className="mt-1.5 font-sans text-sm text-upc-grey">{inst.ciudad}</p>
                            )}
                            <div className="mt-3 flex items-center justify-between border-t border-upc-red/10 pt-3">
                                <span className="font-sans text-xs text-upc-grey">
                                    {inst.categorias_count || 0} {inst.categorias_count === 1 ? 'carrera' : 'carreras'}
                                </span>
                                <span className="font-sgothic text-xs font-bold text-upc-red group-hover:text-upc-red-hover transition-colors duration-300">
                                    Ver simulacros →
                                </span>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10 mt-8 text-center sm:hidden">
                <p className="font-sans text-sm text-upc-grey">Desliza para ver más universidades</p>
            </div>
        </section>
    );
}
