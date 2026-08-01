import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
    CheckCircle2, XCircle, Clock, HelpCircle, Calendar,
    Mail, MessageCircle, BarChart3, AlertTriangle, FileText,
    ChevronDown, ChevronUp, Eye, GraduationCap, Building2, Trophy,
    Star
} from 'lucide-react';

function CircularProgress({ value, size = 120, strokeWidth = 8 }) {
    const radius = (size - strokeWidth) / 2;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (value / 100) * circumference;
    const color = value >= 60 ? '#00f0ff' : value >= 40 ? '#ffcc00' : '#ff00ff';

    return (
        <svg width={size} height={size} className="-rotate-90">
            <circle cx={size / 2} cy={size / 2} r={radius} fill="none" stroke="rgba(0,240,255,0.08)" strokeWidth={strokeWidth} />
            <motion.circle
                cx={size / 2} cy={size / 2} r={radius}
                fill="none" stroke={color} strokeWidth={strokeWidth}
                strokeDasharray={circumference}
                initial={{ strokeDashoffset: circumference }}
                animate={{ strokeDashoffset: offset }}
                transition={{ duration: 1.5, ease: 'easeOut' }}
                strokeLinecap="round"
                style={{ filter: `drop-shadow(0 0 6px ${color}66)` }}
            />
        </svg>
    );
}

export default function ResultadoExamen({ intento, institucion, respuestas, resultadosConceptos, mensajesAyuda, userEmail, userWhatsapp, carrerasCompatibles = [], carrerasNoCompatibles = [] }) {
    const puntaje = intento?.puntaje_total ?? 0;
    const maximo = intento?.puntaje_maximo ?? 1;
    const porcentaje = Math.round((puntaje / maximo) * 100);
    const aprobado = intento?.aprobado ?? false;
    const esAreaAcademica = intento?.area_academica_id && !intento?.examen_id;

    const conceptosDebiles = resultadosConceptos?.filter(
        (rc) => rc.porcentaje_acierto < 60
    ) ?? [];

    const respuestasFalladas = respuestas?.filter((r) => !r.es_correcta) ?? [];
    const [expandedItem, setExpandedItem] = useState(null);
    const [filtro, setFiltro] = useState('todas');

    const toggleExpand = (idx) => {
        setExpandedItem(expandedItem === idx ? null : idx);
    };

    const respuestasFiltradas = filtro === 'todas'
        ? respuestas ?? []
        : filtro === 'correctas'
            ? (respuestas ?? []).filter((r) => r.es_correcta)
            : (respuestas ?? []).filter((r) => !r.es_correcta);

    const [enviandoEmail, setEnviandoEmail] = useState(false);
    const [emailEnviado, setEmailEnviado] = useState(intento?.email_enviado || false);
    const [emailMsg, setEmailMsg] = useState('');
    const [emailError, setEmailError] = useState('');

    const enviarPorEmail = async () => {
        setEnviandoEmail(true);
        setEmailMsg('');
        setEmailError('');
        try {
            const res = await fetch(`/resultados/${intento.id}/enviar-email`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                },
            });
            const data = await res.json();
            if (data.success) {
                setEmailEnviado(true);
                setEmailMsg(data.message);
            } else {
                setEmailError(data.error || 'Error al enviar');
            }
        } catch {
            setEmailError('Error de conexión. Intenta de nuevo.');
        } finally {
            setEnviandoEmail(false);
        }
    };

    const enviarWhatsApp = async () => {
        try {
            const res = await fetch(`/resultados/${intento.id}/whatsapp`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                },
            });
            const data = await res.json();
            if (data.success && data.whatsapp_link) {
                window.open(data.whatsapp_link, '_blank', 'noopener,noreferrer');
            }
        } catch {
            // Silent error
        }
    };

    return (
        <>
            <Head title="Resultados del Examen" />

            <div className="min-h-screen bg-cyber-dark cyber-grid">
                {/* Header */}
                <div className={`relative py-12 overflow-hidden ${
                    aprobado
                        ? 'bg-gradient-to-br from-neon-cyan/20 via-cyber-dark-100 to-cyber-dark'
                        : 'bg-gradient-to-br from-neon-magenta/10 via-cyber-dark-100 to-cyber-dark'}`}
                >
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(0,240,255,0.1),transparent_70%)]" />
                    <div className="relative mx-auto max-w-5xl px-5 sm:px-8 lg:px-10">
                        <div className={`inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-bold backdrop-blur-sm neon-text ${
                            aprobado ? 'cyber-badge cyber-badge-cyan' : 'cyber-badge cyber-badge-magenta'
                        }`}>
                            {aprobado ? <Star className="h-4 w-4" /> : <FileText className="h-4 w-4" />}
                            {aprobado ? '¡Aprobado!' : 'Completado'}
                        </div>
                        <h1 className="mt-4 text-2xl font-heading font-black text-text-primary sm:text-3xl neon-text-cyan">
                            {institucion?.nombre || 'Simulacro'}
                        </h1>
                        <p className="mt-2 text-text-muted text-sm sm:text-base font-semibold">
                            {intento?.examen?.titulo || intento?.carrera || ''}
                        </p>
                    </div>
                </div>

                {/* Content */}
                <div className="mx-auto max-w-5xl px-5 sm:px-8 lg:px-10 mt-4 pb-12">
                    {/* Score card */}
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.5 }}
                        className="cyber-card rounded-xl p-6 sm:p-8"
                    >
                        <div className="flex flex-col items-center gap-6 sm:flex-row">
                            <div className="relative flex-shrink-0">
                                <CircularProgress value={porcentaje} size={140} />
                                <div className="absolute inset-0 flex flex-col items-center justify-center">
                                    <span className="text-3xl font-heading font-black text-text-primary">{puntaje}</span>
                                    <span className="text-xs font-bold text-text-muted">/ {maximo}</span>
                                </div>
                            </div>

                            <div className="flex-1 text-center sm:text-left">
                                <p className="text-lg font-heading font-bold text-text-primary">
                                    {porcentaje}% de aciertos —{' '}
                                    <span className={aprobado ? 'text-neon-cyan neon-text' : 'text-neon-cyan'}>
                                        {aprobado ? '¡Aprobado!' : 'Sigue practicando'}
                                    </span>
                                </p>

                                <div className="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                    <div className="flex items-center gap-2 rounded-lg bg-cyber-dark-300 border border-cyber-dark-400/30 p-3">
                                        <Clock className="h-4 w-4 text-neon-cyan" />
                                        <div>
                                            <p className="text-[10px] font-bold text-text-muted uppercase tracking-wider">Tiempo</p>
                                            <p className="text-sm font-heading font-bold text-text-primary">
                                                {intento?.tiempo_empleado_seg
                                                    ? `${Math.floor(intento.tiempo_empleado_seg / 60)} min`
                                                    : '—'}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 rounded-lg bg-cyber-dark-300 border border-cyber-dark-400/30 p-3">
                                        <HelpCircle className="h-4 w-4 text-neon-magenta" />
                                        <div>
                                            <p className="text-[10px] font-bold text-text-muted uppercase tracking-wider">Preguntas</p>
                                            <p className="text-sm font-heading font-bold text-text-primary">{respuestas?.length ?? 0}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 rounded-lg bg-cyber-dark-300 border border-cyber-dark-400/30 p-3">
                                        <Calendar className="h-4 w-4 text-neon-cyan" />
                                        <div>
                                            <p className="text-[10px] font-bold text-text-muted uppercase tracking-wider">Fecha</p>
                                            <p className="text-sm font-heading font-bold text-text-primary">
                                                {intento?.created_at
                                                    ? new Date(intento.created_at).toLocaleDateString('es-PE')
                                                    : '—'}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </motion.div>

                    {/* Weak concepts */}
                    {conceptosDebiles.length > 0 && (
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.5, delay: 0.1 }}
                            className="mt-4 cyber-card rounded-xl p-5 border-neon-magenta/20"
                        >
                            <div className="flex items-center gap-2">
                                <AlertTriangle className="h-5 w-5 text-neon-magenta" />
                                <h3 className="font-heading font-bold text-text-primary neon-text-cyan">Áreas que necesitas reforzar</h3>
                            </div>
                            <div className="mt-4 space-y-4">
                                {conceptosDebiles.map((rc) => {
                                    const ayuda = mensajesAyuda?.[rc.concepto_id];
                                    return (
                                        <div key={rc.id} className="rounded-xl border border-cyber-dark-400/30 bg-cyber-dark-200 p-4">
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <p className="font-heading font-bold text-text-primary">{rc.concepto?.nombre || 'Concepto'}</p>
                                                    <p className="text-xs font-semibold text-text-muted">
                                                        {rc.preguntas_correctas} de {rc.preguntas_totales} correctas ({rc.porcentaje_acierto}%)
                                                    </p>
                                                </div>
                                                <span className="cyber-badge cyber-badge-magenta rounded-lg px-3 py-1 text-xs font-bold">
                                                    {rc.porcentaje_acierto}%
                                                </span>
                                            </div>
                                            {(ayuda || rc.porcentaje_acierto < 50) && (
                                                <p className="mt-3 rounded-lg border border-neon-magenta/20 bg-neon-magenta/5 p-3 text-xs leading-relaxed text-text-secondary">
                                                    {ayuda?.texto || `Debes reforzar "${rc.concepto?.nombre}": acertaste ${rc.preguntas_correctas} de ${rc.preguntas_totales} preguntas.`}
                                                </p>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </motion.div>
                    )}

                    {/* Breakdown by topic */}
                    {resultadosConceptos?.length > 0 && (
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.5, delay: 0.2 }}
                            className="mt-4 cyber-card rounded-xl p-5"
                        >
                            <h3 className="flex items-center gap-2 font-heading font-bold text-text-primary">
                                <BarChart3 className="h-5 w-5 text-neon-cyan" />
                                <span className="neon-text-cyan">Desglose por tema</span>
                            </h3>
                            <div className="mt-4 space-y-3">
                                {resultadosConceptos.map((rc) => {
                                    const pct = rc.porcentaje_acierto || 0;
                                    const barColor = pct >= 70 ? 'from-neon-cyan to-neon-cyan/50'
                                        : pct >= 40 ? 'from-neon-yellow to-neon-yellow/50'
                                        : 'from-neon-magenta to-neon-magenta/50';
                                    return (
                                        <div key={rc.id} className="flex items-center gap-4">
                                            <div className="flex-1">
                                                <p className="text-sm font-semibold text-text-primary">{rc.concepto?.nombre || 'Tema'}</p>
                                                <div className="mt-1 h-2 w-full rounded-full bg-cyber-dark-300 overflow-hidden border border-cyber-dark-400/30">
                                                    <motion.div
                                                        initial={{ width: 0 }}
                                                        animate={{ width: `${pct}%` }}
                                                        transition={{ duration: 1, delay: 0.3, ease: 'easeOut' }}
                                                        className={`h-full rounded-full bg-gradient-to-r ${barColor}`}
                                                        style={{ boxShadow: pct >= 70 ? '0 0 8px rgba(0,240,255,0.4)' : pct >= 40 ? '0 0 8px rgba(255,204,0,0.3)' : '0 0 8px rgba(255,0,255,0.3)' }}
                                                    />
                                                </div>
                                            </div>
                                            <span className="text-sm font-heading font-bold text-text-primary">
                                                {rc.preguntas_correctas}/{rc.preguntas_totales}
                                            </span>
                                        </div>
                                    );
                                })}
                            </div>
                        </motion.div>
                    )}

                    {/* All questions review */}
                    {respuestas?.length > 0 && (
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.5, delay: 0.3 }}
                            className="mt-4 cyber-card rounded-xl overflow-hidden"
                        >
                            <div className="border-b border-cyber-dark-400/30 bg-cyber-dark-200 px-5 py-4">
                                <h3 className="flex items-center gap-2 font-heading font-bold text-text-primary">
                                    <Eye className="h-5 w-5 text-neon-cyan" />
                                    Revisión de respuestas ({respuestas.length})
                                </h3>
                                <div className="mt-3 flex gap-2">
                                    {[
                                        { key: 'todas', label: 'Todas', count: respuestas.length },
                                        { key: 'correctas', label: 'Correctas', count: respuestas.filter((r) => r.es_correcta).length },
                                        { key: 'falladas', label: 'Falladas', count: respuestasFalladas.length },
                                    ].map((f) => (
                                        <button
                                            key={f.key}
                                            onClick={() => { setFiltro(f.key); setExpandedItem(null); }}
                                            className={`rounded-lg px-3 py-1 text-xs font-bold transition-all ${
                                                filtro === f.key
                                                    ? f.key === 'correctas'
                                                        ? 'bg-neon-cyan/15 text-neon-cyan border border-neon-cyan/40 shadow-neon-cyan'
                                                        : f.key === 'falladas'
                                                            ? 'bg-neon-magenta/15 text-neon-cyan border border-neon-cyan/40 shadow-[0_0_8px_rgba(0,240,255,0.15)]'
                                                            : 'bg-neon-cyan/10 text-neon-cyan border border-neon-cyan/30'
                                                    : 'border border-cyber-dark-400/50 text-text-muted hover:text-text-primary hover:border-neon-cyan/30'
                                            }`}
                                        >
                                            {f.label} ({f.count})
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div className="divide-y divide-cyber-dark-400/20">
                                {respuestasFiltradas.map((respuesta, idx) => {
                                    const pregunta = respuesta.pregunta;
                                    const alternativas = pregunta?.alternativas ?? [];
                                    const elegida = respuesta.alternativa_elegida || respuesta.alternativaElegida;
                                    const correcta = alternativas.find((a) => a.es_correcta);
                                    const isOpen = expandedItem === idx;
                                    const esCorrecta = respuesta.es_correcta;

                                    return (
                                        <div key={respuesta.id} className="transition-colors hover:bg-cyber-dark-300/30">
                                            <button
                                                onClick={() => toggleExpand(idx)}
                                                className="flex w-full items-center gap-4 px-5 py-4 text-left"
                                            >
                                                <span className={`flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold ${
                                                    esCorrecta
                                                        ? 'bg-neon-cyan/15 text-neon-cyan border border-neon-cyan/30'
                                                        : 'bg-neon-magenta/15 text-neon-cyan border border-neon-cyan/30'
                                                }`}>
                                                    {esCorrecta ? <CheckCircle2 size={16} /> : <XCircle size={16} />}
                                                </span>
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-semibold text-text-primary line-clamp-1">
                                                        <span className="text-xs font-bold text-text-muted mr-1">#{idx + 1}</span>
                                                        {pregunta?.enunciado || 'Sin enunciado'}
                                                    </p>
                                                </div>
                                                <div className="flex-shrink-0 text-text-muted">
                                                    {isOpen ? <ChevronUp size={18} /> : <ChevronDown size={18} />}
                                                </div>
                                            </button>

                                            <AnimatePresence>
                                                {isOpen && (
                                                    <motion.div
                                                        initial={{ height: 0, opacity: 0 }}
                                                        animate={{ height: 'auto', opacity: 1 }}
                                                        exit={{ height: 0, opacity: 0 }}
                                                        transition={{ duration: 0.2 }}
                                                        className="overflow-hidden"
                                                    >
                                                        <div className="border-t border-cyber-dark-400/20 bg-cyber-dark-300/50 px-5 py-4">
                                                            {pregunta?.enunciado_imagen_url && (
                                                                <img
                                                                    src={pregunta.enunciado_imagen_url}
                                                                    alt="Imagen de la pregunta"
                                                                    className="mb-3 max-h-48 rounded-xl border border-cyber-dark-400/30 object-contain bg-cyber-dark-200"
                                                                />
                                                            )}

                                                            {elegida ? (
                                                                <div
                                                                    className={`flex items-start gap-3 rounded-xl border p-3 ${
                                                                        esCorrecta
                                                                            ? 'border-neon-cyan/40 bg-neon-cyan/5'
                                                                            : 'border-neon-magenta/30 bg-neon-magenta/5'
                                                                    }`}
                                                                >
                                                                    <span className={`mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full text-[10px] font-bold ${
                                                                        esCorrecta
                                                                            ? 'bg-neon-cyan/20 text-neon-cyan'
                                                                            : 'bg-neon-magenta/20 text-neon-magenta'
                                                                    }`}>
                                                                        {esCorrecta ? <CheckCircle2 size={12} /> : <XCircle size={12} />}
                                                                    </span>
                                                                    <div className="flex-1 min-w-0">
                                                                        <p className="text-sm text-text-secondary">{elegida.texto}</p>
                                                                        {elegida.imagen_url && (
                                                                            <img src={elegida.imagen_url} alt="Tu respuesta" className="mt-2 max-h-24 rounded-xl border border-cyber-dark-400/30 object-contain" />
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            ) : (
                                                                <p className="text-sm text-text-muted italic">Sin respuesta</p>
                                                            )}
                                                        </div>
                                                    </motion.div>
                                                )}
                                            </AnimatePresence>
                                        </div>
                                    );
                                })}
                            </div>
                        </motion.div>
                    )}

                    {/* Share results */}
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.5, delay: 0.4 }}
                        className="mt-4 cyber-card rounded-xl p-5"
                    >
                        <h3 className="flex items-center gap-2 font-heading font-bold text-text-primary">
                            <Mail className="h-5 w-5 text-neon-cyan" />
                            <span className="neon-text-cyan">Compartir resultados</span>
                        </h3>
                        <p className="mt-1 text-xs font-semibold text-text-muted">
                            Guarda o comparte tus resultados para llevar un seguimiento de tu progreso.
                        </p>

                        <div className="mt-5 grid gap-3 sm:grid-cols-2">
                            {/* Email */}
                            <div className="rounded-xl border border-cyber-dark-400/30 bg-cyber-dark-200 p-4 transition-all hover:border-neon-cyan/40 hover:shadow-neon-cyan/10">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-neon-cyan/10 border border-neon-cyan/30">
                                        <Mail className="h-5 w-5 text-neon-cyan" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-heading font-bold text-text-primary">Enviar por correo</p>
                                        <p className="truncate text-xs font-semibold text-text-muted">
                                            {emailEnviado ? 'Enviado anteriormente' : (userEmail || 'Sin correo registrado')}
                                        </p>
                                    </div>
                                </div>
                                {emailMsg && <p className="mt-2 text-xs font-bold text-neon-cyan">{emailMsg}</p>}
                                {emailError && <p className="mt-2 text-xs font-bold text-neon-cyan/80">{emailError}</p>}
                                <button
                                    onClick={enviarPorEmail}
                                    disabled={enviandoEmail || !userEmail}
                                    className="mt-3 flex w-full items-center justify-center gap-2 rounded-lg cyber-btn cyber-btn-primary py-2.5 text-xs font-bold disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {enviandoEmail ? 'Enviando...' : emailEnviado ? 'Reenviar resultados' : 'Enviar a mi correo'}
                                </button>
                            </div>

                            {/* WhatsApp */}
                            <div className="rounded-xl border border-neon-magenta/30 bg-neon-magenta/5 p-4 transition-all hover:border-neon-magenta/50 hover:shadow-[0_0_12px_rgba(255,0,255,0.1)]">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-neon-magenta/10 border border-neon-magenta/30">
                                        <MessageCircle className="h-5 w-5 text-neon-magenta" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-heading font-bold text-text-primary">Compartir por WhatsApp</p>
                                        <p className="text-xs font-semibold text-text-muted">
                                            {userWhatsapp ? userWhatsapp : 'Abre WhatsApp con un resumen'}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    onClick={enviarWhatsApp}
                                    className="mt-3 flex w-full items-center justify-center gap-2 rounded-lg cyber-btn py-2.5 text-xs font-bold border-neon-cyan/40 text-neon-cyan hover:bg-neon-cyan/10 hover:shadow-[0_0_12px_rgba(0,240,255,0.15)]"
                                >
                                    <MessageCircle className="h-4 w-4" />
                                    Abrir WhatsApp
                                </button>
                            </div>
                        </div>
                    </motion.div>

                    {/* Carreras compatibles */}
                    {esAreaAcademica && carrerasCompatibles.length > 0 && (
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3 }}
                            className="rounded-xl cyber-card p-5 mb-4 border-neon-green/30 bg-neon-green/[0.03]">
                            <div className="flex items-center gap-2 mb-4">
                                <CheckCircle2 className="h-5 w-5 text-neon-green" />
                                <h2 className="text-lg font-heading font-bold text-neon-green neon-text-green">
                                    Carreras que alcanzas ({carrerasCompatibles.length})
                                </h2>
                            </div>
                            <div className="space-y-2">
                                {carrerasCompatibles.map((c) => (
                                    <div key={c.categoria_id} className="flex items-center justify-between rounded-xl bg-cyber-dark-200 border border-neon-green/20 p-4">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-neon-green/10 border border-neon-green/30">
                                                <GraduationCap className="h-5 w-5 text-neon-green" />
                                            </div>
                                            <div>
                                                <p className="text-sm font-heading font-bold text-text-primary">{c.nombre}</p>
                                                <p className="text-xs font-semibold text-text-muted flex items-center gap-1">
                                                    <Building2 className="h-3 w-3" /> {c.institucion}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <span className="cyber-badge cyber-badge-green rounded-lg px-3 py-1 text-xs font-bold">
                                                {c.puntaje_obtenido}% &ge; {c.puntaje_minimo}% mínimo
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </motion.div>
                    )}

                    {esAreaAcademica && carrerasNoCompatibles.length > 0 && (
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.4 }}
                            className="rounded-xl cyber-card p-5 mb-4 border-neon-magenta/20">
                            <div className="flex items-center gap-2 mb-4">
                                <AlertTriangle className="h-5 w-5 text-neon-magenta" />
                                <h2 className="text-lg font-heading font-bold text-text-primary">
                                    Carreras que aún no alcanzas ({carrerasNoCompatibles.length})
                                </h2>
                            </div>
                            <div className="space-y-3">
                                {carrerasNoCompatibles.map((c) => {
                                    const faltante = c.puntaje_minimo > 0 ? Math.max(0, c.puntaje_minimo - c.puntaje_obtenido) : 0;
                                    return (
                                        <div key={c.categoria_id} className="rounded-xl border border-cyber-dark-400/30 bg-cyber-dark-200 p-4">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-neon-magenta/10 border border-neon-magenta/20">
                                                        <GraduationCap className="h-5 w-5 text-neon-magenta" />
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-heading font-bold text-text-primary">{c.nombre}</p>
                                                        <p className="text-xs font-semibold text-text-muted flex items-center gap-1">
                                                            <Building2 className="h-3 w-3" /> {c.institucion}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div className="text-right">
                                                    <span className="cyber-badge cyber-badge-magenta rounded-lg px-3 py-1 text-xs font-bold">
                                                        {c.puntaje_obtenido}% / {c.puntaje_minimo}% mínimo
                                                    </span>
                                                    {faltante > 0 && (
                                                        <p className="mt-1 text-[10px] font-bold text-text-muted">
                                                            Te faltan {faltante.toFixed(1)} puntos
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                            {c.areas_faltantes && c.areas_faltantes.length > 0 && (
                                                <div className="mt-3 flex flex-wrap gap-2">
                                                    {c.areas_faltantes.map((af, i) => (
                                                        <span key={i} className="inline-flex items-center gap-1 rounded-lg bg-neon-magenta/5 border border-neon-magenta/20 px-2.5 py-0.5 text-[10px] font-bold text-neon-cyan/70">
                                                            {af.nombre}: obtuviste {af.obtenido}%, necesitas &ge;{af.requerido}%
                                                        </span>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </motion.div>
                    )}

                    {/* Actions */}
                    <div className="mt-4 flex flex-col gap-3 sm:flex-row">
                        <Link
                            href="/dashboard"
                            className="flex-1 cyber-btn cyber-btn-primary rounded-xl py-3 text-sm font-bold justify-center"
                        >
                            <Trophy className="h-4 w-4" />
                            Volver a mi panel
                        </Link>
                        <Link
                            href="/historial"
                            className="flex-1 cyber-btn rounded-xl py-3 text-sm font-bold justify-center border-cyber-dark-400"
                        >
                            Ver historial completo
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}
