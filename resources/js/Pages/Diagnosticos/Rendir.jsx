import { Head, router } from '@inertiajs/react';
import { useState, useEffect, useCallback, useRef } from 'react';
import { Brain, Star, BarChart3, ChevronLeft, ChevronRight, Check, Menu, ArrowRight, Sparkles, Clock, Layers } from 'lucide-react';
import { guardarRespuestasEnLotes, guardarRespuestasBatch } from '#lib/guardarRespuestas';

function Timer({ seconds, onTimeUp }) {
    const [remaining, setRemaining] = useState(seconds);
    const intervalRef = useRef(null);
    useEffect(() => {
        intervalRef.current = setInterval(() => {
            setRemaining((prev) => {
                if (prev <= 1) { clearInterval(intervalRef.current); onTimeUp?.(); return 0; }
                return prev - 1;
            });
        }, 1000);
        return () => clearInterval(intervalRef.current);
    }, [onTimeUp]);
    const mins = Math.floor(remaining / 60);
    const secs = remaining % 60;
    const pct = seconds > 0 ? (remaining / seconds) * 100 : 0;
    const isLow = remaining < 300;
    const isCritical = remaining < 60;
    return (
        <div className="relative flex items-center gap-3 rounded-xl cyber-card border-neon-cyan/30 px-4 py-2">
            <svg className="h-10 w-10 -rotate-90" viewBox="0 0 36 36">
                <circle cx="18" cy="18" r="15.5" fill="none" stroke="rgba(0,240,255,0.1)" strokeWidth="3" />
                <circle cx="18" cy="18" r="15.5" fill="none"
                    stroke={isCritical ? '#ff00ff' : isLow ? '#ffcc00' : '#00f0ff'}
                    strokeWidth="3" strokeDasharray={`${2 * Math.PI * 15.5}`}
                    strokeDashoffset={`${2 * Math.PI * 15.5 * (1 - pct / 100)}`}
                    strokeLinecap="round" className="transition-all duration-1000"
                    style={{ filter: isCritical ? 'drop-shadow(0 0 6px #ff00ff)' : isLow ? 'drop-shadow(0 0 6px #ffcc00)' : 'drop-shadow(0 0 6px #00f0ff)' }} />
            </svg>
            <div className="flex flex-col">
                <span className={`font-heading text-xl font-black leading-none tracking-wider ${
                    isCritical ? 'text-neon-magenta animate-pulse' : isLow ? 'text-neon-yellow' : 'text-neon-cyan'
                } neon-text`}>
                    {String(mins).padStart(2, '0')}:{String(secs).padStart(2, '0')}
                </span>
                <span className="text-[10px] font-bold text-text-muted uppercase tracking-widest">Restante</span>
            </div>
            {isCritical && (
                <span className="absolute -top-1 -right-1 flex h-3 w-3">
                    <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-neon-magenta opacity-75" />
                    <span className="relative inline-flex h-3 w-3 rounded-full bg-neon-magenta" />
                </span>
            )}
        </div>
    );
}

function AlternativeButton({ letra, alt, selected, onSelect }) {
    const isSelected = selected === alt.id;
    return (
        <button onClick={() => onSelect(alt.id)}
            className={`group relative flex w-full items-start gap-4 rounded-xl border-2 p-4 text-left transition-all duration-300 ${
                isSelected
                    ? 'border-neon-cyan bg-neon-cyan-100 shadow-neon-cyan'
                    : 'border-cyber-dark-400/50 cyber-card hover:border-neon-cyan/40 hover:shadow-neon-cyan/20'
            }`}>
            <div className={`relative flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl text-base font-heading font-bold transition-all duration-300 ${
                isSelected
                    ? 'bg-neon-cyan text-cyber-dark shadow-neon-cyan scale-110'
                    : 'bg-cyber-dark-300 text-text-secondary border border-cyber-dark-400/50 group-hover:bg-neon-cyan-100 group-hover:text-neon-cyan'
            }`}>
                {letra}
            </div>
            <div className="flex-1 min-w-0 pt-1">
                {alt.texto && (
                    <p className={`text-sm leading-relaxed sm:text-base ${
                        isSelected ? 'font-bold text-neon-cyan' : 'font-semibold text-text-primary'
                    }`}>
                        {alt.texto}
                    </p>
                )}
                {alt.imagen_url && (
                    <img src={alt.imagen_url} alt="" className="mt-2 max-h-28 rounded-xl object-contain bg-cyber-dark-300 border border-cyber-dark-400/50"
                        onError={(e) => { e.target.style.display = 'none'; }} />
                )}
            </div>
            <div className={`mt-1.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border-2 transition-all duration-300 ${
                isSelected
                    ? 'border-neon-cyan bg-neon-cyan'
                    : 'border-cyber-dark-400 group-hover:border-neon-cyan/50'
            }`}>
                {isSelected && (
                    <svg className="h-3.5 w-3.5 text-cyber-dark" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                    </svg>
                )}
            </div>
        </button>
    );
}

export default function RendirDiagnostico({ intento, preguntas, areas, tiempoRestante }) {
    const [currentIndex, setCurrentIndex] = useState(0);
    const [respuestas, setRespuestas] = useState(preguntas.map((p) => p.respuesta_guardada || null));
    const [showNav, setShowNav] = useState(false);
    const [finish, setFinish] = useState(null);
    const respuestasRef = useRef(respuestas);
    const autoSaveTimer = useRef(null);

    const currentPregunta = preguntas[currentIndex];
    const totalPreguntas = preguntas.length;
    const answeredCount = respuestas.filter(Boolean).length;

    const urlGuardarMasivo = `/diagnostico/rendir/${intento.id}/guardar-masivo`;
    const urlResultados = `/diagnostico/rendir/${intento.id}/resultados`;

    const responderPreguntas = useCallback((snapshot) => preguntas
        .map((p, i) => ({ pregunta_id: p.id, alternativa_id_elegida: snapshot[i] ?? null }))
        .filter((r) => r.alternativa_id_elegida != null), [preguntas]);

    useEffect(() => { respuestasRef.current = respuestas; }, [respuestas]);

    useEffect(() => {
        let enProceso = false;
        autoSaveTimer.current = setInterval(async () => {
            if (enProceso) return;
            const respondidas = responderPreguntas(respuestasRef.current);
            if (respondidas.length === 0) return;
            enProceso = true;
            try {
                await guardarRespuestasBatch(urlGuardarMasivo, respondidas);
            } catch (e) { console.error('Error en auto-guardado:', e); }
            enProceso = false;
        }, 30000);
        return () => clearInterval(autoSaveTimer.current);
    }, [urlGuardarMasivo, responderPreguntas]);

    const guardarRespuesta = useCallback(async (preguntaId, alternativaId) => {
        try {
            await guardarRespuestasBatch(urlGuardarMasivo, [{ pregunta_id: preguntaId, alternativa_id_elegida: alternativaId }]);
        } catch (e) { console.error('Error al guardar:', e); }
    }, [urlGuardarMasivo]);

    const handleSelect = (preguntaId, alternativaId) => {
        const newRespuestas = [...respuestas];
        newRespuestas[currentIndex] = alternativaId;
        setRespuestas(newRespuestas);
        guardarRespuesta(preguntaId, alternativaId);
    };

    const goTo = (i) => { setCurrentIndex(i); setShowNav(false); };
    const goNext = () => { if (currentIndex < totalPreguntas - 1) setCurrentIndex(currentIndex + 1); };
    const goPrev = () => { if (currentIndex > 0) setCurrentIndex(currentIndex - 1); };

    const handleFinish = async () => {
        const respondidas = responderPreguntas(respuestasRef.current);

        setFinish({ fase: 'guardando', listo: 0, total: respondidas.length });

        try {
            await guardarRespuestasEnLotes({
                url: urlGuardarMasivo,
                respuestas: respondidas,
                onProgreso: (listo, total) => setFinish({ fase: 'guardando', listo, total }),
            });
        } catch (e) {
            console.error('Error al guardar respuestas:', e);
        }

        setFinish({ fase: 'calculando', listo: respondidas.length, total: respondidas.length });

        router.post(`/diagnostico/rendir/${intento.id}/finalizar`, {}, {
            onError: () => { window.location.href = urlResultados; },
        });

        // Fallback: si Inertia no redirige en 60s, forzamos la navegación
        setTimeout(() => { window.location.href = urlResultados; }, 60000);
    };

    if (finish) {
        const pct = finish.total > 0 ? Math.round((finish.listo / finish.total) * 100) : 100;
        return (
            <div className="flex min-h-screen items-center justify-center bg-cyber-dark cyber-grid">
                <div className="w-full max-w-sm px-6 text-center">
                    <div className="relative mx-auto mb-8 flex h-24 w-24 items-center justify-center">
                        <div className="absolute inset-0 animate-ping rounded-full bg-neon-cyan/20" />
                        <div className="relative flex h-16 w-16 items-center justify-center rounded-2xl cyber-card border-neon-cyan/50 shadow-neon-cyan">
                            <Brain className="h-8 w-8 text-neon-cyan animate-bounce" />
                        </div>
                    </div>
                    <p className="text-2xl font-heading font-black text-neon-cyan neon-text">
                        {finish.fase === 'guardando' ? 'Guardando respuestas...' : 'Calculando resultados...'}
                    </p>
                    <p className="mt-2 text-sm text-text-muted">
                        {finish.fase === 'guardando' && finish.total > 0
                            ? `${finish.listo} de ${finish.total} respuestas`
                            : 'Estamos procesando tus respuestas'}
                    </p>
                    <div className="mt-6 h-2 w-full rounded-full bg-cyber-dark-400 overflow-hidden">
                        <div className="h-full rounded-full bg-gradient-to-r from-neon-cyan to-neon-magenta transition-all duration-300"
                            style={{ width: `${finish.fase === 'calculando' ? 100 : pct}%`, boxShadow: '0 0 10px rgba(0,240,255,0.4)' }} />
                    </div>
                    <p className="mt-3 font-heading text-sm font-black tabular-nums text-neon-cyan neon-text">
                        {finish.fase === 'calculando' ? '100%' : `${pct}%`}
                    </p>
                </div>
            </div>
        );
    }

    const letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

    return (
        <>
            <Head title="Diagnóstico — Rendir" />

            {/* Top bar */}
            <header className="fixed top-0 left-0 right-0 z-40 cyber-header-glass">
                <div className="mx-auto flex max-w-full items-center justify-between px-3 py-2.5 sm:px-6">
                    <div className="flex items-center gap-3">
                        <div className="flex h-9 w-9 items-center justify-center rounded-lg cyber-logo-sidebar">
                            <Brain className="h-5 w-5" />
                        </div>
                        <div className="hidden sm:block">
                            <p className="text-sm font-heading font-bold text-neon-cyan neon-text">Examen Diagnóstico</p>
                            <p className="text-[10px] font-semibold text-text-muted">Evaluación general</p>
                        </div>
                    </div>

                    <Timer seconds={tiempoRestante} onTimeUp={handleFinish} />

                    <div className="flex items-center gap-3">
                        <div className="flex items-center gap-2 rounded-lg cyber-card border-cyber-dark-400/50 px-3 py-1.5">
                            <BarChart3 className="h-3.5 w-3.5 text-neon-cyan" />
                            <span className="text-xs font-bold text-text-primary">
                                <span className="text-neon-cyan">{answeredCount}</span>
                                <span className="text-text-muted">/{totalPreguntas}</span>
                            </span>
                        </div>
                        <button onClick={() => setShowNav(!showNav)}
                            className="rounded-lg cyber-btn p-2 border-0 lg:hidden">
                            <Menu className="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </header>

            {/* Question navigator sidebar */}
            {showNav && (
                <div className="fixed inset-0 z-30 cursor-pointer lg:hidden" onClick={() => setShowNav(false)}>
                    <div className="absolute right-4 top-16 w-72 rounded-xl cyber-card border-neon-cyan/20 p-4 shadow-neon-cyan" onClick={(e) => e.stopPropagation()}>
                        <div className="mb-3 flex items-center justify-between">
                            <span className="text-xs font-bold text-neon-cyan uppercase tracking-widest">Navegación</span>
                            <span className="text-xs font-semibold text-text-muted">
                                <span className="text-neon-cyan">{answeredCount}</span>/{totalPreguntas}
                            </span>
                        </div>
                        <div className="grid grid-cols-5 gap-1.5">
                            {preguntas.map((p, i) => {
                                const isAnswered = !!respuestas[i];
                                const isActive = i === currentIndex;
                                return (
                                    <button key={i} onClick={() => goTo(i)}
                                        className={`aspect-square rounded-lg text-xs font-heading font-bold transition-all duration-200 ${
                                            isActive
                                                ? 'ring-2 ring-neon-cyan ring-offset-2 ring-offset-cyber-dark scale-110 shadow-neon-cyan'
                                                : ''
                                        } ${
                                            isAnswered
                                                ? 'bg-neon-cyan text-cyber-dark shadow-neon-cyan'
                                                : 'bg-cyber-dark-300 text-text-muted border border-cyber-dark-400/50 hover:border-neon-cyan/30 hover:text-text-primary'
                                        }`}>
                                        {i + 1}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                </div>
            )}

            {/* Sidebar desktop navigator */}
            <div className="fixed left-4 top-24 z-30 hidden lg:block">
                <div className="cyber-card rounded-xl border-neon-cyan/15 p-3 w-20">
                    <p className="mb-2 text-center text-[10px] font-bold text-neon-cyan uppercase tracking-widest neon-text">Preguntas</p>
                    <div className="grid grid-cols-3 gap-1.5">
                        {preguntas.map((p, i) => {
                            const isAnswered = !!respuestas[i];
                            const isActive = i === currentIndex;
                            return (
                                <button key={i} onClick={() => goTo(i)}
                                    className={`aspect-square rounded text-[10px] font-heading font-bold transition-all duration-200 ${
                                        isActive
                                            ? 'ring-1 ring-neon-cyan scale-110 shadow-neon-cyan'
                                            : ''
                                    } ${
                                        isAnswered
                                            ? 'bg-neon-cyan text-cyber-dark'
                                            : 'bg-cyber-dark-300 text-text-muted border border-cyber-dark-400/30 hover:border-neon-cyan/30'
                                    }`}>
                                    {i + 1}
                                </button>
                            );
                        })}
                    </div>
                    <div className="mt-2 pt-2 border-t border-cyber-dark-400/30 text-center">
                        <span className="text-[10px] font-semibold text-text-muted">
                            <span className="text-neon-cyan">{answeredCount}</span>/{totalPreguntas}
                        </span>
                    </div>
                </div>
            </div>

            {/* Main content */}
            <div className="pt-24 pb-32 px-3 sm:px-6 lg:pl-28 min-h-screen bg-cyber-dark cyber-grid">
                <div className="mx-auto max-w-full">
                    {/* Concept badge and progress */}
                    <div className="mb-6 flex items-center gap-3">
                        <div className="cyber-badge cyber-badge-magenta rounded-lg px-3 py-1.5">
                            <Brain className="h-3.5 w-3.5" />
                            <span className="text-xs">{currentPregunta.concepto?.nombre || 'General'}</span>
                        </div>
                        <span className="text-xs font-semibold text-text-muted">
                            Pregunta <span className="text-neon-cyan">{currentIndex + 1}</span> de <span className="text-text-primary">{totalPreguntas}</span>
                        </span>
                        {areas?.length > 0 && (
                            <span className="hidden sm:flex items-center gap-1 text-[10px] font-bold text-text-muted uppercase tracking-wider px-2 py-1 rounded-md bg-cyber-dark-300 border border-cyber-dark-400/30">
                                <Layers className="h-3 w-3 text-neon-magenta" />
                                {areas.length} áreas
                            </span>
                        )}
                    </div>

                    {/* Question card */}
                    <div className="cyber-card rounded-2xl border-neon-cyan/20 overflow-hidden">
                        <div className="p-5 sm:p-6 lg:p-8">
                            <p className="text-base leading-relaxed text-text-primary sm:text-lg lg:text-xl font-semibold">
                                {currentPregunta.enunciado}
                            </p>
                            {currentPregunta.enunciado_imagen_url && (
                                <div className="mt-5 overflow-hidden rounded-xl border border-cyber-dark-400/30 bg-cyber-dark-300">
                                    <img src={currentPregunta.enunciado_imagen_url} alt=""
                                        className="max-h-80 w-full object-contain"
                                        onError={(e) => { e.target.style.display = 'none'; }} />
                                </div>
                            )}
                        </div>
                        <div className="border-t border-cyber-dark-400/30 bg-cyber-dark-200/50 px-5 py-5 sm:px-6 lg:px-8">
                            <p className="mb-4 text-xs font-heading font-bold uppercase tracking-widest text-neon-cyan neon-text">
                                <Sparkles className="inline h-3.5 w-3.5 mr-1" />
                                Selecciona la respuesta correcta
                            </p>
                            <div className="grid gap-3">
                                {currentPregunta.alternativas?.map((alt, i) => (
                                    <AlternativeButton key={alt.id}
                                        letra={letras[i]}
                                        alt={alt}
                                        selected={respuestas[currentIndex]}
                                        onSelect={(altId) => handleSelect(currentPregunta.id, altId)} />
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Navigation buttons */}
                    <div className="mt-6 flex items-center justify-between">
                        <button onClick={goPrev} disabled={currentIndex === 0}
                            className="cyber-btn rounded-xl px-5 py-3 text-sm disabled:opacity-30 disabled:cursor-not-allowed">
                            <ChevronLeft className="h-4 w-4" /> Anterior
                        </button>

                        {currentIndex === totalPreguntas - 1 ? (
                            <button onClick={handleFinish}
                                className="cyber-btn cyber-btn-primary cyber-btn-pulse rounded-xl px-6 py-3 text-sm">
                                Finalizar simulacro <ArrowRight className="h-4 w-4" />
                            </button>
                        ) : (
                            <button onClick={goNext}
                                className="cyber-btn cyber-btn-primary rounded-xl px-6 py-3 text-sm">
                                Siguiente <ChevronRight className="h-4 w-4" />
                            </button>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
