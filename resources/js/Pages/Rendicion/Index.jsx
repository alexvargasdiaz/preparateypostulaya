import { Head, Link } from '@inertiajs/react';
import { useState, useEffect, useCallback, useRef } from 'react';
import { router } from '@inertiajs/react';
import ConfirmModal from '@/Components/ConfirmModal';
import { Star, List, Square, ChevronLeft, ChevronRight, Check, Menu, Brain } from 'lucide-react';
import { guardarRespuestasEnLotes, guardarRespuestasBatch } from '#lib/guardarRespuestas';

// ─── Timer Component ───────────────────────────────────────────
function Timer({ seconds, onTimeUp }) {
    const [remaining, setRemaining] = useState(seconds);
    const intervalRef = useRef(null);

    useEffect(() => {
        intervalRef.current = setInterval(() => {
            setRemaining((prev) => {
                if (prev <= 1) {
                    clearInterval(intervalRef.current);
                    onTimeUp?.();
                    return 0;
                }
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
        <div className="relative flex items-center gap-3 rounded-xl bg-cyber-dark-300/80 px-4 py-2.5 backdrop-blur-sm border border-neon-cyan/20 shadow-[0_0_15px_rgba(0,240,255,0.1)]">
            <svg className="h-9 w-9 -rotate-90" viewBox="0 0 36 36">
                <circle cx="18" cy="18" r="15.5" fill="none" stroke="rgba(0,240,255,0.1)" strokeWidth="3" />
                <circle cx="18" cy="18" r="15.5" fill="none"
                    stroke={isCritical ? '#ff00ff' : isLow ? '#ffcc00' : '#00f0ff'}
                    strokeWidth="3" strokeDasharray={`${2 * Math.PI * 15.5}`}
                    strokeDashoffset={`${2 * Math.PI * 15.5 * (1 - pct / 100)}`}
                    strokeLinecap="round" className="transition-all duration-1000"
                    style={{ filter: `drop-shadow(0 0 4px ${isCritical ? '#ff00ff' : isLow ? '#ffcc00' : '#00f0ff'}66)` }} />
            </svg>
            <div className="flex flex-col">
                <span className={`font-mono text-lg font-heading font-bold leading-none tracking-tight ${
                    isCritical ? 'text-neon-magenta animate-pulse' : isLow ? 'text-neon-yellow' : 'text-text-primary'
                }`}>
                    {String(mins).padStart(2, '0')}:{String(secs).padStart(2, '0')}
                </span>
                <span className="text-[10px] font-bold text-text-muted uppercase tracking-wider">Restante</span>
            </div>
        </div>
    );
}

// ─── Progress Bar ──────────────────────────────────────────────
function ProgressBar({ answered, total }) {
    const pct = total > 0 ? (answered / total) * 100 : 0;
    return (
        <div className="flex items-center gap-3">
            <div className="h-2 w-28 overflow-hidden rounded-full bg-cyber-dark-300 border border-cyber-dark-400/30 sm:w-36">
                <div className="h-full rounded-full bg-gradient-to-r from-neon-cyan to-neon-magenta transition-all duration-500"
                    style={{ width: `${pct}%`, boxShadow: '0 0 8px rgba(0,240,255,0.4)' }} />
            </div>
            <span className="text-xs font-heading font-bold text-text-secondary">
                <span className="text-text-primary">{answered}</span>/{total}
            </span>
        </div>
    );
}

// ─── Question Navigator ────────────────────────────────────────
function QuestionNav({ preguntas, respuestas, currentIndex, onNavigate, markedForReview }) {
    const total = preguntas.length;
    const answeredCount = respuestas.filter(Boolean).length;
    const markedCount = markedForReview.size;

    return (
        <div className="cyber-card rounded-xl p-4">
            <div className="mb-4 grid grid-cols-3 gap-2">
                <div className="rounded-xl bg-neon-cyan/10 border border-neon-cyan/30 p-2.5 text-center">
                    <p className="text-lg font-heading font-bold text-neon-cyan">{answeredCount}</p>
                    <p className="text-[10px] font-bold text-neon-cyan/70 uppercase">Hechas</p>
                </div>
                <div className="rounded-xl bg-neon-yellow/10 border border-neon-yellow/30 p-2.5 text-center">
                    <p className="text-lg font-heading font-bold text-neon-yellow">{markedCount}</p>
                    <p className="text-[10px] font-bold text-neon-yellow/70 uppercase">Revisar</p>
                </div>
                <div className="rounded-xl bg-cyber-dark-300 border border-cyber-dark-400/30 p-2.5 text-center">
                    <p className="text-lg font-heading font-bold text-text-muted">{total - answeredCount}</p>
                    <p className="text-[10px] font-bold text-text-muted uppercase">Faltan</p>
                </div>
            </div>

            <div className="mb-3 flex items-center gap-3 text-[10px] font-bold text-text-muted">
                <span className="flex items-center gap-1.5">
                    <span className="inline-block h-2.5 w-2.5 rounded-full bg-neon-cyan shadow-[0_0_6px_rgba(0,240,255,0.5)]" /> Hecha
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="inline-block h-2.5 w-2.5 rounded-full bg-neon-yellow shadow-[0_0_6px_rgba(255,204,0,0.5)]" /> Revisar
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="inline-block h-2.5 w-2.5 rounded-full bg-cyber-dark-400 border border-cyber-dark-400/50" /> Pendiente
                </span>
            </div>

            <div className="grid grid-cols-5 gap-1.5 sm:grid-cols-5">
                {preguntas.map((_, i) => {
                    const isAnswered = !!respuestas[i];
                    const isMarked = markedForReview.has(i);
                    const isActive = i === currentIndex;
                    return (
                        <button key={i} onClick={() => onNavigate(i)}
                            className={`relative aspect-square rounded-xl text-xs font-heading font-bold transition-all duration-200
                                ${isActive
                                    ? 'ring-2 ring-neon-cyan shadow-[0_0_12px_rgba(0,240,255,0.3)] scale-110 z-10'
                                    : 'hover:scale-105'
                                }
                                ${isMarked
                                    ? 'bg-neon-yellow/20 text-neon-yellow border border-neon-yellow/40'
                                    : isAnswered
                                    ? 'bg-neon-cyan/20 text-neon-cyan border border-neon-cyan/40'
                                    : isActive
                                    ? 'bg-neon-cyan/30 text-white border border-neon-cyan/60'
                                    : 'bg-cyber-dark-300 text-text-muted border border-cyber-dark-400/30 hover:bg-cyber-dark-200'
                                }`}>
                            {i + 1}
                            {isMarked && (
                                <span className="absolute -top-1 -right-1"><Star className="h-2.5 w-2.5 fill-neon-yellow text-neon-yellow" /></span>
                            )}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

// ─── Alternative Button ────────────────────────────────────────
function AlternativeButton({ letra, alt, selected, onSelect, index }) {
    const isSelected = selected === alt.id;
    return (
        <button onClick={() => onSelect(alt.id)}
            className={`group relative flex w-full items-start gap-4 rounded-xl border-2 p-4 text-left transition-all duration-200
                ${isSelected
                    ? 'border-neon-cyan bg-neon-cyan/10 shadow-[0_0_15px_rgba(0,240,255,0.2)]'
                    : 'border-cyber-dark-400/40 bg-cyber-dark-200/90 hover:border-neon-cyan/40 hover:bg-neon-cyan/[0.04] hover:shadow-[0_0_12px_rgba(0,240,255,0.1)]'
                }`}>
            <div className={`relative flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl text-base font-heading font-bold transition-all duration-200
                ${isSelected
                    ? 'bg-gradient-to-br from-neon-cyan to-neon-magenta text-white shadow-[0_0_12px_rgba(0,240,255,0.4)] scale-110'
                    : 'bg-cyber-dark-300 text-text-muted border border-cyber-dark-400/50 group-hover:bg-neon-cyan/15 group-hover:text-white group-hover:border-neon-cyan/40 group-hover:scale-105'
                }`}>
                {letra}
                {isSelected && (
                    <span className="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-neon-cyan text-[8px] text-white shadow-[0_0_6px_rgba(0,240,255,0.5)]">
                        <Check className="h-3 w-3" />
                    </span>
                )}
            </div>

            <div className="flex-1 min-w-0 pt-1">
                {alt.texto && (
                    <p className={`text-sm leading-relaxed sm:text-base ${isSelected ? 'font-bold text-text-primary' : 'font-semibold text-text-secondary'}`}>
                        {alt.texto}
                    </p>
                )}
                {alt.imagen_url && (
                    <img src={alt.imagen_url} alt="" className="mt-2 max-h-28 rounded-xl object-contain bg-cyber-dark-300 border border-cyber-dark-400/30"
                        onError={(e) => { e.target.style.display = 'none'; }} />
                )}
            </div>

            <div className={`mt-1.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full border-2 transition-all duration-200
                ${isSelected
                    ? 'border-neon-cyan bg-neon-cyan shadow-[0_0_8px_rgba(0,240,255,0.4)]'
                    : 'border-cyber-dark-400 group-hover:border-neon-cyan/50'
                }`}>
                {isSelected && (
                    <Check className="h-3.5 w-3.5 text-white" />
                )}
            </div>
        </button>
    );
}

// ─── Question Card ─────────────────────────────────────────────
function QuestionCard({ pregunta, index, total, selected, onSelect, isMarked, onToggleMark }) {
    const letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    return (
        <div className="cyber-card rounded-xl overflow-hidden">
            <div className="flex items-center justify-between border-b border-cyber-dark-400/30 bg-cyber-dark-200 px-5 py-3.5 sm:px-6">
                <div className="flex items-center gap-3">
                    <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-neon-cyan to-neon-magenta text-sm font-heading font-bold text-white shadow-[0_0_10px_rgba(0,240,255,0.3)]">
                        {index + 1}
                    </span>
                    <div>
                        <span className="text-sm text-text-muted">
                            Pregunta <span className="font-bold text-text-primary">{index + 1}</span> de <span className="font-bold text-text-primary">{total}</span>
                        </span>
                        {pregunta.dificultad && (
                            <span className={`ml-2 inline-block rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ${
                                pregunta.dificultad === 'facil' ? 'cyber-badge cyber-badge-green' :
                                pregunta.dificultad === 'dificil' ? 'cyber-badge cyber-badge-magenta' :
                                'cyber-badge-cyan bg-neon-cyan/10 text-neon-cyan border border-neon-cyan/30'
                            }`}>
                                {pregunta.dificultad === 'facil' ? 'Fácil' : pregunta.dificultad === 'dificil' ? 'Difícil' : 'Media'}
                            </span>
                        )}
                    </div>
                </div>
                <button onClick={() => onToggleMark(index)}
                    className={`flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition-all duration-200 border ${
                        isMarked
                            ? 'bg-neon-yellow/15 text-neon-yellow border-neon-yellow/40'
                            : 'bg-cyber-dark-300 text-text-muted border-cyber-dark-400/50 hover:bg-neon-yellow/10 hover:text-neon-yellow hover:border-neon-yellow/30'
                    }`}>
                    <Star className={`h-3.5 w-3.5 ${isMarked ? 'fill-neon-yellow' : ''}`} />
                    {isMarked ? 'Quitar revisar' : 'Marcar'}
                </button>
            </div>

            <div className="p-5 sm:p-6 lg:p-8">
                <p className="text-base leading-relaxed text-text-primary sm:text-lg lg:text-xl font-semibold">
                    {pregunta.enunciado}
                </p>
                {pregunta.enunciado_imagen_url && (
                    <div className="mt-5 overflow-hidden rounded-xl border border-cyber-dark-400/30 bg-cyber-dark-300">
                        <img src={pregunta.enunciado_imagen_url} alt="Imagen de apoyo"
                            className="max-h-80 w-full object-contain"
                            onError={(e) => { e.target.style.display = 'none'; }} />
                    </div>
                )}
            </div>

            <div className="border-t border-cyber-dark-400/30 bg-cyber-dark-200/50 px-5 py-5 sm:px-6 lg:px-8">
                <p className="mb-4 text-xs font-bold uppercase tracking-wider text-neon-cyan">
                    <span className="neon-text">Selecciona la respuesta correcta</span>
                </p>
                <div className="grid gap-3">
                    {pregunta.alternativas?.map((alt, i) => (
                        <AlternativeButton
                            key={alt.id}
                            letra={letras[i]}
                            alt={alt}
                            selected={selected}
                            onSelect={(altId) => onSelect(pregunta.id, altId)}
                            index={i}
                        />
                    ))}
                </div>
            </div>
        </div>
    );
}

// ─── Nav Button ────────────────────────────────────────────────
function NavButton({ children, onClick, disabled, variant = 'default' }) {
    const variants = {
        default: 'cyber-btn border-cyber-dark-400',
        primary: 'cyber-btn cyber-btn-primary',
        success: 'cyber-btn border-neon-magenta/40 text-neon-magenta hover:bg-neon-magenta/10 hover:shadow-[0_0_12px_rgba(255,0,255,0.2)]',
    };
    return (
        <button onClick={onClick} disabled={disabled} className={`rounded-xl px-5 py-3 text-sm font-heading font-bold transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed ${variants[variant]}`}>
            {children}
        </button>
    );
}

// ─── Loading Screen ────────────────────────────────────────────
function LoadingScreen({ fase, listo, total }) {
    const [dots, setDots] = useState('');
    const pct = total > 0 ? Math.round((listo / total) * 100) : 100;
    useEffect(() => {
        const interval = setInterval(() => {
            setDots((prev) => (prev.length >= 3 ? '' : prev + '.'));
        }, 400);
        return () => clearInterval(interval);
    }, []);

    return (
        <div className="flex min-h-screen items-center justify-center bg-cyber-dark cyber-grid">
            <div className="w-full max-w-sm px-6 text-center">
                <div className="relative mx-auto mb-8 flex h-24 w-24 items-center justify-center">
                    <div className="absolute inset-0 animate-ping rounded-full bg-neon-cyan/20" />
                    <div className="absolute inset-2 animate-pulse rounded-full bg-neon-magenta/30" />
                    <div className="relative flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-neon-cyan to-neon-magenta shadow-[0_0_30px_rgba(0,240,255,0.4)]">
                        <Brain className="h-8 w-8 text-white" />
                    </div>
                </div>
                <p className="text-2xl font-heading font-black text-text-primary neon-text-cyan">
                    {fase === 'guardando' ? 'Guardando respuestas' : 'Calculando resultados'}
                    <span className="tabular-nums">{dots}</span>
                </p>
                <p className="mt-2 text-sm text-text-muted">
                    {fase === 'guardando' && total > 0
                        ? `${listo} de ${total} respuestas`
                        : 'Estamos procesando tus respuestas'}
                </p>
                <div className="mx-auto mt-8 h-2 w-full max-w-sm overflow-hidden rounded-full bg-cyber-dark-300 border border-cyber-dark-400/30">
                    <div className="h-full rounded-full bg-gradient-to-r from-neon-cyan to-neon-magenta transition-all duration-300"
                        style={{
                            width: `${fase === 'calculando' ? 100 : pct}%`,
                            boxShadow: '0 0 10px rgba(0,240,255,0.4)',
                        }} />
                </div>
                <p className="mt-3 font-heading text-sm font-black tabular-nums text-neon-cyan">
                    {fase === 'calculando' ? '100%' : `${pct}%`}
                </p>
            </div>
        </div>
    );
}

// ─── Main Exam Page ────────────────────────────────────────────
export default function RendirExamen({ intento, institucion, preguntas, tiempoRestante }) {
    const [currentIndex, setCurrentIndex] = useState(0);
    const [respuestas, setRespuestas] = useState(
        preguntas.map((p) => p.respuesta_guardada || null)
    );
    const [markedForReview, setMarkedForReview] = useState(new Set());
    const [showNav, setShowNav] = useState(false);
    const [finish, setFinish] = useState(null);
    const [showConfirmModal, setShowConfirmModal] = useState(false);
    const autoSaveTimer = useRef(null);
    const respuestasRef = useRef(respuestas);

    const currentPregunta = preguntas[currentIndex];
    const totalPreguntas = preguntas.length;
    const answeredCount = respuestas.filter(Boolean).length;

    const urlGuardarMasivo = `/examenes/intento/${intento.id}/guardar-masivo`;
    const urlResultados = `/resultados/${intento.id}`;

    const responderPreguntas = useCallback((snapshot) => preguntas
        .map((p, i) => ({ pregunta_id: p.id, alternativa_id_elegida: snapshot[i] ?? null }))
        .filter((r) => r.alternativa_id_elegida != null), [preguntas]);

    useEffect(() => {
        respuestasRef.current = respuestas;
    }, [respuestas]);

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
        } catch (e) {
            console.error('Error al guardar respuesta:', e);
        }
    }, [urlGuardarMasivo]);

    const handleSelect = (preguntaId, alternativaId) => {
        const newRespuestas = [...respuestas];
        newRespuestas[currentIndex] = alternativaId;
        setRespuestas(newRespuestas);
        guardarRespuesta(preguntaId, alternativaId);
    };

    const goTo = (index) => {
        setCurrentIndex(index);
        setShowNav(false);
    };

    const goNext = () => {
        if (currentIndex < totalPreguntas - 1) {
            setCurrentIndex(currentIndex + 1);
        }
    };

    const goPrev = () => {
        if (currentIndex > 0) {
            setCurrentIndex(currentIndex - 1);
        }
    };

    useEffect(() => {
        const handler = (e) => {
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') goNext();
            if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') goPrev();
        };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [currentIndex, totalPreguntas]);

    const toggleMark = (index) => {
        const newMarked = new Set(markedForReview);
        newMarked.has(index) ? newMarked.delete(index) : newMarked.add(index);
        setMarkedForReview(newMarked);
    };

    const openConfirmModal = () => {
        setShowConfirmModal(true);
    };

    const handleFinish = async () => {
        setShowConfirmModal(false);

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

        router.post(`/examenes/intento/${intento.id}/finalizar`, {}, {
            onError: () => {
                window.location.href = urlResultados;
            },
        });
        // Fallback: si Inertia no redirige en 60s, forzamos navegación
        setTimeout(() => {
            window.location.href = urlResultados;
        }, 60000);
    };

    const handleTimeUp = () => handleFinish();

    if (finish) return <LoadingScreen fase={finish.fase} listo={finish.listo} total={finish.total} />;

    return (
        <>
            <Head title={`${institucion?.nombre || 'Simulacro'} — ${intento.carrera || 'Rendir'}`} />

            <header className="fixed top-0 left-0 right-0 z-40 cyber-header-glass border-b border-neon-cyan/10 shadow-[0_4px_30px_rgba(0,240,255,0.1)]">
                <div className="mx-auto flex max-w-full items-center justify-between px-3 py-2.5 sm:px-6">
                    <div className="flex items-center gap-3 min-w-0">
                        <button onClick={() => setShowNav(!showNav)}
                            className="flex h-9 w-9 items-center justify-center rounded-xl bg-cyber-dark-300/50 text-text-primary hover:bg-cyber-dark-200 transition-all lg:hidden border border-cyber-dark-400/50">
                            <Menu className="h-5 w-5" />
                        </button>
                        <div className="min-w-0">
                            <h1 className="truncate text-sm font-heading font-bold text-text-primary sm:text-base neon-text-cyan">
                                {institucion?.nombre || 'Simulacro'}
                            </h1>
                            <p className="truncate text-[11px] text-text-muted">{intento.carrera || ''}</p>
                        </div>
                    </div>

                    <div className="hidden sm:block">
                        <ProgressBar answered={answeredCount} total={totalPreguntas} />
                    </div>

                    <div className="flex items-center gap-2 sm:gap-3">
                        <Timer seconds={tiempoRestante} onTimeUp={handleTimeUp} />
                    </div>
                </div>
            </header>

            {/* Mobile progress bar */}
            <div className="fixed top-[52px] left-0 right-0 z-30 bg-cyber-dark/90 backdrop-blur-sm px-3 py-1.5 sm:hidden border-b border-cyber-dark-400/30">
                <div className="flex items-center justify-between">
                    <span className="text-[10px] font-bold text-text-muted uppercase tracking-wider">Progreso</span>
                    <span className="text-xs font-heading font-bold text-text-primary">{answeredCount}/{totalPreguntas}</span>
                </div>
                <div className="mt-1 h-1 overflow-hidden rounded-full bg-cyber-dark-300 border border-cyber-dark-400/30">
                    <div className="h-full rounded-full bg-gradient-to-r from-neon-cyan to-neon-magenta transition-all duration-500"
                        style={{ width: `${totalPreguntas > 0 ? (answeredCount / totalPreguntas) * 100 : 0}%`, boxShadow: '0 0 6px rgba(0,240,255,0.4)' }} />
                </div>
            </div>

            {/* Mobile question navigator overlay */}
            {showNav && (
                <div className="fixed inset-0 z-50 cursor-pointer bg-cyber-dark/80 pt-16 backdrop-blur-sm" onClick={() => setShowNav(false)}>
                    <div className="mx-auto max-w-sm px-4 pt-4" onClick={(e) => e.stopPropagation()}>
                        <QuestionNav
                            preguntas={preguntas}
                            respuestas={respuestas}
                            currentIndex={currentIndex}
                            onNavigate={goTo}
                            markedForReview={markedForReview}
                        />
                        <button onClick={() => setShowNav(false)}
                            className="mt-4 w-full cyber-btn rounded-xl py-3 text-sm font-heading font-bold justify-center border-neon-cyan/40">
                            Cerrar navegador
                        </button>
                    </div>
                </div>
            )}

            {/* Main Content */}
            <main className="min-h-screen bg-cyber-dark">
                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 pt-20 pb-32 lg:pt-28">
                    {/* Exam info badges */}
                    <div className="mb-6 flex items-center gap-2 flex-wrap">
                        <span className="cyber-badge cyber-badge-cyan rounded-lg px-3 py-1 text-[10px] font-bold uppercase tracking-widest">
                            {institucion?.nombre || 'Simulacro'}
                        </span>
                        <span className="rounded-lg bg-neon-magenta/10 border border-neon-magenta/30 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-neon-magenta">
                            {intento.carrera || 'General'}
                        </span>
                        <span className="rounded-lg bg-cyber-dark-300 border border-cyber-dark-400/30 px-3 py-1 text-[10px] font-bold text-text-muted">
                            {totalPreguntas} preguntas
                        </span>
                    </div>

                    <div className="flex gap-8">
                        {/* Desktop sidebar */}
                        <div className="hidden w-72 flex-shrink-0 lg:block">
                            <div className="sticky top-24 space-y-4">
                                <QuestionNav
                                    preguntas={preguntas}
                                    respuestas={respuestas}
                                    currentIndex={currentIndex}
                                    onNavigate={goTo}
                                    markedForReview={markedForReview}
                                />

                                <div className="cyber-card rounded-xl p-4 border-neon-magenta/20 bg-gradient-to-br from-neon-magenta/[0.03] to-cyber-dark-200">
                                    <p className="text-xs font-bold text-text-muted">Progreso general</p>
                                    <p className="mt-1 text-2xl font-heading font-black text-text-primary">
                                        {totalPreguntas > 0 ? Math.round((answeredCount / totalPreguntas) * 100) : 0}%
                                    </p>
                                    <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-cyber-dark-300 border border-cyber-dark-400/30">
                                        <div className="h-full rounded-full bg-gradient-to-r from-neon-magenta to-neon-cyan transition-all duration-500"
                                            style={{ width: `${totalPreguntas > 0 ? (answeredCount / totalPreguntas) * 100 : 0}%`, boxShadow: '0 0 8px rgba(255,0,255,0.3)' }} />
                                    </div>
                                    <button onClick={openConfirmModal}
                                        className="mt-4 w-full cyber-btn rounded-xl py-2.5 text-xs font-heading font-bold justify-center border-neon-magenta/40 text-neon-magenta hover:bg-neon-magenta/10">
                                        <Square className="h-3.5 w-3.5" /> Finalizar examen
                                    </button>
                                </div>
                            </div>
                        </div>

                        {/* Question area */}
                        <div className="flex-1 min-w-0">
                            {currentPregunta && (
                                <QuestionCard
                                    pregunta={currentPregunta}
                                    index={currentIndex}
                                    total={totalPreguntas}
                                    selected={respuestas[currentIndex]}
                                    onSelect={handleSelect}
                                    isMarked={markedForReview.has(currentIndex)}
                                    onToggleMark={toggleMark}
                                />
                            )}

                            <div className="mt-5 flex items-center justify-between gap-3">
                                <NavButton onClick={goPrev} disabled={currentIndex === 0} variant="default">
                                    <ChevronLeft className="h-4 w-4" />
                                    Anterior
                                </NavButton>

                                <div className="flex items-center gap-2">
                                    <button onClick={() => setShowNav(true)}
                                        className="flex items-center gap-2 rounded-xl border border-cyber-dark-400/50 bg-cyber-dark-200 px-4 py-3 text-sm font-semibold text-text-muted shadow-sm hover:border-neon-cyan/30 transition-all lg:hidden">
                                        <List className="h-4 w-4" /> {currentIndex + 1}/{totalPreguntas}
                                    </button>
                                    <span className="hidden text-xs text-text-muted sm:block">
                                        ←  → teclas
                                    </span>
                                </div>

                                {currentIndex < totalPreguntas - 1 ? (
                                    <NavButton onClick={goNext} variant="primary">
                                        Siguiente
                                        <ChevronRight className="h-4 w-4" />
                                    </NavButton>
                                ) : (
                                    <NavButton onClick={openConfirmModal} variant="primary">
                                        Finalizar examen
                                        <Check className="h-4 w-4" />
                                    </NavButton>
                                )}
                            </div>

                            <div className="mt-3 text-center">
                                <p className="text-[10px] text-text-muted">
                                    Usa las teclas <kbd className="rounded-md bg-cyber-dark-300 px-1.5 py-0.5 font-mono text-[10px] font-bold text-text-secondary border border-cyber-dark-400/50">←</kbd>
                                    {' '}<kbd className="rounded-md bg-cyber-dark-300 px-1.5 py-0.5 font-mono text-[10px] font-bold text-text-secondary border border-cyber-dark-400/50">→</kbd>
                                    {' '}para navegar entre preguntas
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            {/* Mobile bottom bar */}
            <div className="fixed bottom-0 left-0 right-0 z-40 border-t border-cyber-dark-400/50 bg-cyber-dark/95 backdrop-blur-md px-3 py-2 shadow-2xl shadow-black/20 sm:hidden">
                <div className="flex items-center justify-between gap-2">
                    <button onClick={goPrev} disabled={currentIndex === 0}
                        className="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-cyber-dark-400/50 bg-cyber-dark-300 py-2.5 text-sm font-semibold text-text-secondary disabled:opacity-40 transition-all active:scale-95">
                        <ChevronLeft className="h-4 w-4" />
                        <span className="text-xs">Anterior</span>
                    </button>

                    <div className="flex flex-col items-center px-2">
                        <span className="text-sm font-heading font-bold text-neon-cyan">{answeredCount}/{totalPreguntas}</span>
                        <span className="text-[9px] text-text-muted uppercase">Respondidas</span>
                    </div>

                    {currentIndex < totalPreguntas - 1 ? (
                        <button onClick={goNext}
                            className="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-neon-cyan to-neon-magenta py-2.5 text-sm font-heading font-bold text-white shadow-[0_0_15px_rgba(0,240,255,0.3)] transition-all active:scale-95">
                            <span className="text-xs">Siguiente</span>
                            <ChevronRight className="h-4 w-4" />
                        </button>
                    ) : (
                        <button onClick={openConfirmModal}
                            className="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-neon-magenta to-neon-cyan py-2.5 text-sm font-heading font-bold text-white shadow-[0_0_15px_rgba(255,0,255,0.3)] transition-all active:scale-95">
                            <span className="text-xs">Finalizar</span>
                            <Check className="h-4 w-4" />
                        </button>
                    )}
                </div>
            </div>

            <ConfirmModal
                open={showConfirmModal}
                onConfirm={handleFinish}
                onCancel={() => setShowConfirmModal(false)}
                icon="📝"
                title="¿Estás seguro de que quieres finalizar el examen?"
                message="Una vez finalizado, no podrás modificar tus respuestas."
                confirmText="Sí, finalizar examen"
                cancelText="Seguir respondiendo"
                confirmVariant="danger"
                answeredCount={answeredCount}
                totalCount={totalPreguntas}
            />
        </>
    );
}
