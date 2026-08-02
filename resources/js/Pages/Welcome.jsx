import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import {
    GraduationCap, ArrowRight, ChevronRight, ChevronLeft, Play, BookOpen,
    Award, Target, TrendingUp, Gift, Star, Quote, Users,
    CheckCircle2, Menu, X, Sparkles, Brain, Compass, Route, BarChart3
} from 'lucide-react';
import VideoModal from '../Components/VideoModal';

export default function Welcome({ auth, flash, testimonios = [], facultades = [], instituciones = {}, examenesDestacados = [] }) {
    const [videoOpen, setVideoOpen] = useState(false);
    const [videoSrc, setVideoSrc] = useState('');
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const openVideo = (src) => { setVideoSrc(src); setVideoOpen(true); };
    const closeVideo = () => { setVideoOpen(false); setVideoSrc(''); };

    const stats = [
        { icon: GraduationCap, valor: '15+', label: 'Universidades' },
        { icon: BookOpen, valor: '50+', label: 'Simulacros' },
        { icon: Users, valor: '500+', label: 'Estudiantes' },
        { icon: Award, valor: '100%', label: 'Gratis' },
    ];

    const razones = [
        { icon: Award, titulo: 'Calidad Académica', desc: 'Simulacros con el mismo formato y nivel de dificultad que los exámenes reales de admisión.', stat: '15+ universidades', color: 'green' },
        { icon: Target, titulo: 'Retroalimentación', desc: 'Al terminar sabrás exactamente qué temas dominas y cuáles necesitas reforzar.', stat: 'Desglose por temas', color: 'cyan' },
        { icon: TrendingUp, titulo: 'Alta Efectividad', desc: '9 de cada 10 estudiantes mejoran su puntaje en el segundo intento.', stat: '90% mejora', color: 'magenta' },
        { icon: Gift, titulo: '100% Gratis', desc: 'Sin tarjetas, sin límites, sin anuncios. Todo completamente gratuito.', stat: 'Sin costo', color: 'yellow' },
    ];

    const carrerasList = ['Preguntas tipo admisión', 'Temporizador real', 'Resultados por tema'];

    const neonColor = (color) => {
        const map = { green: 'neon-green', cyan: 'neon-cyan', magenta: 'neon-magenta', yellow: 'neon-yellow' };
        return map[color] || 'neon-cyan';
    };

    return (
        <>
            <Head>
                <title>Prepárate y Postula Ya — Simulacros de Admisión Universitaria</title>
                <meta name="description" content="Simulacros gratuitos de preparación para exámenes de admisión universitaria. Retroalimentación inmediata, seguimiento de progreso y 100% gratis." />
            </Head>

            {/* Flash Messages */}
            {(flash?.error || flash?.success) && (
                <motion.div
                    initial={{ opacity: 0, y: -20 }}
                    animate={{ opacity: 1, y: 0 }}
                    className="fixed top-20 left-0 right-0 z-50 mx-auto max-w-xl px-4"
                >
                    <div className={`cyber-card rounded-xl px-6 py-4 ${flash.error ? 'border-neon-magenta/50' : 'border-neon-green/50'}`}>
                        <p className="font-bold text-text-primary text-sm">{flash.error || flash.success}</p>
                    </div>
                </motion.div>
            )}

            {/* ===== HEADER (estilo DeFi) ===== */}
            <header className="fixed top-0 left-0 right-0 z-50 cyber-header-glass">
                <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                    <div className="flex h-16 items-center justify-between">
                        <Link href="/" className="flex items-center gap-3 group">
                            <div className="cyber-logo">
                                P
                            </div>
                            <span className="font-heading font-bold text-sm sm:text-base tracking-tight neon-text-cyan group-hover:brightness-125 transition-all">
                            Prepárate y Postula Ya
                        </span>
                        </Link>

                        {/* Desktop nav */}
                        <nav className="hidden md:flex items-center gap-6 ml-auto">
                            {auth.user ? (
                                <>
                                    <Link href="/dashboard" className="cyber-btn-wallet rounded-lg px-5 py-2 text-sm">
                                        Mi panel
                                    </Link>
                                    <Link href="/logout" method="post" as="button" className="cyber-btn-wallet-ghost">
                                        Salir
                                    </Link>
                                </>
                            ) : (
                                <div className="flex items-center gap-3">
                                    <Link href="/login" className="cyber-btn-wallet-ghost">
                                        Iniciar sesión
                                    </Link>
                                    <a href="/register" className="cyber-btn-wallet">
                                        Registrarse
                                    </a>
                                </div>
                            )}
                        </nav>

                        {/* Mobile menu button */}
                        <button onClick={() => setMobileMenuOpen(!mobileMenuOpen)} className="md:hidden cyber-btn-wallet-ghost rounded-lg p-2 border-0">
                            {mobileMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
                        </button>
                    </div>                        {/* Mobile menu */}
                    {mobileMenuOpen && (
                        <motion.div initial={{ opacity: 0, y: -10 }} animate={{ opacity: 1, y: 0 }} className="md:hidden border-t border-white/5 mt-0 py-4 space-y-3">
                            {auth.user ? (
                                <>
                                    <Link href="/dashboard" className="cyber-btn-wallet inline-flex rounded-lg px-5 py-2 text-sm" onClick={() => setMobileMenuOpen(false)}>Mi panel</Link>
                                    <Link href="/logout" method="post" as="button" className="block text-sm font-semibold text-text-muted hover:text-white py-2">Salir</Link>
                                </>
                            ) : (
                                <div className="flex flex-col gap-2 pt-2">
                                    <Link href="/login" className="cyber-btn-wallet-ghost justify-center" onClick={() => setMobileMenuOpen(false)}>Iniciar sesión</Link>
                                    <a href="/register" className="cyber-btn-wallet justify-center" onClick={() => setMobileMenuOpen(false)}>Registrarse</a>
                                </div>
                            )}
                        </motion.div>
                    )}
                </div>
            </header>

            {/* ===== HERO SECTION ===== */}
            <section className="relative overflow-hidden bg-cyber-dark pt-24 sm:pt-28 cyber-grid">
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.1),transparent_50%)]" />
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_bottom_left,rgba(0,240,255,0.06),transparent_50%)]" />
                
                <div className="relative mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                    <div className="grid items-center gap-12 lg:grid-cols-2 min-h-[calc(100vh-8rem)] py-12 lg:py-0">
                        <motion.div
                            initial={{ opacity: 0, x: -30 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ duration: 0.5 }}
                        >
                            <div className="cyber-badge cyber-badge-green neon-breathe rounded-lg px-4 py-2 mb-6 inline-flex">
                                <Sparkles className="h-4 w-4" />
                                Nueva plataforma de preparación
                            </div>

                            <h1 className="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-heading font-black leading-[0.9] tracking-tight text-text-primary">
                                ¿Listo para{' '}
                                <span className="neon-text-cyan glitch-text inline-block px-2" style={{ fontFamily: 'var(--font-heading)' }}>
                                    POSTULAR?
                                </span>
                            </h1>

                            <p className="mt-4 max-w-xl text-base sm:text-lg leading-relaxed text-text-secondary font-sans">
                                Prepárate con simulacros realistas, mide tu avance y llega con confianza
                                a tu examen de admisión.{' '}
                                <strong className="text-neon-cyan">100% gratuito.</strong>
                            </p>

                            <div className="mt-8 flex flex-col sm:flex-row gap-4">
                                <a
                                    href="/register"
                                    className="cyber-btn cyber-btn-primary cyber-btn-pulse rounded-xl px-6 py-3 text-sm sm:text-base justify-center sm:justify-start"
                                >
                                    Empieza tu simulacro gratis
                                    <ArrowRight className="h-5 w-5" />
                                </a>
                                <Link
                                    href="#carreras"
                                    className="cyber-btn rounded-xl px-6 py-3 text-sm sm:text-base justify-center sm:justify-start border-cyber-dark-400"
                                >
                                    <BookOpen className="h-4 w-4 sm:h-5 sm:w-5" />
                                    Ver carreras
                                </Link>
                            </div>

                            {/* Stats row */}
                            <div className="mt-12 grid grid-cols-2 sm:grid-cols-4 gap-4">
                                {stats.map((stat) => {
                                    const Icon = stat.icon;
                                    return (
                                        <motion.div
                                            key={stat.label}
                                            initial={{ opacity: 0, y: 20 }}
                                            animate={{ opacity: 1, y: 0 }}
                                            transition={{ duration: 0.4, delay: 0.3 + stats.indexOf(stat) * 0.1 }}
                                            className="text-center cyber-card rounded-xl p-4"
                                        >
                                            <Icon className="mx-auto h-4 w-4 sm:h-5 sm:w-5 text-neon-cyan mb-1" />
                                            <p className="text-xl sm:text-2xl font-black text-text-primary">{stat.valor}</p>
                                            <p className="text-[10px] sm:text-xs font-semibold text-text-muted mt-0.5">{stat.label}</p>
                                        </motion.div>
                                    );
                                })}
                            </div>
                        </motion.div>

                        {/* Hero Image Card */}
                        <motion.div
                            initial={{ opacity: 0, x: 30 }}
                            animate={{ opacity: 1, x: 0 }}
                            transition={{ duration: 0.5, delay: 0.2 }}
                            className="relative hidden lg:block"
                        >
                            <div className="cyber-card cyber-card-animated rounded-2xl overflow-hidden">
                                <div className="relative">
                                    <img
                                        src="/images/university-campus.jpg"
                                        alt="Campus universitario - Preparación para admisión"
                                        className="w-full h-80 object-cover"
                                    />
                                    <div className="absolute inset-0 bg-gradient-to-t from-cyber-dark/80 via-transparent to-transparent" />
                                </div>
                                <div className="p-5 border-t border-cyber-dark-400">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="font-bold text-text-primary neon-text-cyan">Prepárate con nosotros</p>
                                            <p className="text-sm text-text-muted">Simulacros gratuitos</p>
                                        </div>
                                        <button
                                            onClick={() => openVideo('https://www.youtube.com/embed/BQwXERpkCYE')}
                                            className="video-play-upc"
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Floating badge */}
                            <div
                                className="absolute -top-4 -right-4 cyber-badge cyber-badge-magenta rounded-xl px-4 py-2 float-anim"
                            >
                                <Star className="h-4 w-4" />
                                Video
                            </div>
                        </motion.div>
                    </div>
                </div>
            </section>

            {/* ===== EXPLORA CARRERAS BANNER ===== */}
            <section className="bg-cyber-dark-200 border-y border-cyber-dark-400/50">
                <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10 py-10 text-center">                            <h2 className="text-2xl sm:text-3xl font-heading font-black text-text-primary leading-tight">
                                Explora nuestras{' '}
                        <span className="text-neon-yellow neon-text-yellow">
                            {facultades.length > 0
                                ? facultades.reduce((acc, f) => acc + (f.carreras?.length || 0), 0)
                                : '58'}{' '}
                            carreras
                        </span>{' '}
                        universitarias
                    </h2>
                    <p className="mx-auto mt-3 max-w-2xl text-text-secondary text-sm sm:text-base">
                        Tenemos simulacros para todas las áreas académicas. Encuentra la tuya.
                    </p>
                </div>
            </section>

            {/* ===== CARRERAS SECTION ===== */}
            <section id="carreras" className="bg-cyber-dark py-20 sm:py-24">
                <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                    <CategoriesGrid facultades={facultades} />
                </div>
            </section>

            {/* ===== CÓMO FUNCIONA (Info Section) ===== */}
            <section id="como-funciona" className="bg-cyber-dark-100 border-y border-cyber-dark-400/50 py-20 sm:py-24">
                <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                    <div className="grid items-center gap-16 lg:grid-cols-2">
                        <motion.div
                            initial={{ opacity: 0, x: -30 }}
                            whileInView={{ opacity: 1, x: 0 }}
                            viewport={{ once: true }}
                            transition={{ duration: 0.5 }}
                        >
                            <div className="cyber-badge cyber-badge-cyan rounded-lg px-4 py-2 mb-4 inline-flex">
                                <Sparkles className="h-4 w-4" />
                                Preparación inteligente
                            </div>
                            <h2 className="text-3xl sm:text-4xl font-heading font-black leading-tight text-text-primary">
                                La preparación inteligente <br />
                                <span className="neon-text-cyan inline-block">
                                    para tu futuro
                                </span>
                            </h2>
                            <p className="mt-4 text-base sm:text-lg text-text-secondary leading-relaxed">
                                Nuestros simulacros están diseñados por especialistas en admisión universitaria.
                                Cada examen replica fielmente la estructura, el nivel de dificultad y los tiempos
                                de las pruebas reales.
                            </p>
                            <div className="mt-8 space-y-4">
                                {carrerasList.map((item) => (
                                    <div key={item} className="flex items-center gap-3">
                                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-neon-green-100 border border-neon-green">
                                            <CheckCircle2 className="h-4 w-4 text-neon-green" />
                                        </div>
                                        <span className="font-semibold text-text-primary">{item}</span>
                                    </div>
                                ))}
                            </div>
                        </motion.div>

                        <motion.div
                            initial={{ opacity: 0, x: 30 }}
                            whileInView={{ opacity: 1, x: 0 }}
                            viewport={{ once: true }}
                            transition={{ duration: 0.5 }}
                            className="relative"
                        >
                            <div className="cyber-card rounded-2xl overflow-hidden cyber-card-green">
                                <img
                                    src="/images/university-campus.jpg"
                                    alt="Estudiante preparándose para la universidad"
                                    className="w-full h-72 object-cover"
                                />
                            </div>
                            <motion.div
                                animate={{ y: [0, -8, 0] }}
                                transition={{ duration: 3, repeat: Infinity, ease: "easeInOut", delay: 0.5 }}
                                className="absolute -bottom-4 -right-4 cyber-card cyber-card-green rounded-xl p-4"
                            >
                                <p className="text-3xl font-black text-neon-green neon-text-green">15+</p>
                                <p className="text-xs font-bold text-text-muted uppercase tracking-wider">Universidades</p>
                            </motion.div>
                        </motion.div>
                    </div>
                </div>
            </section>

            {/* ===== DIAGNÓSTICO VOCACIONAL ===== */}
            <section className="relative overflow-hidden bg-cyber-dark-100 border-y border-cyber-dark-400/50 py-20 sm:py-24">
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(0,240,255,0.06),transparent_70%)]" />
                <div className="absolute inset-0" style={{
                    backgroundImage: 'radial-gradient(circle at 25% 50%, rgba(0,240,255,0.04) 0%, transparent 50%), radial-gradient(circle at 75% 50%, rgba(0,240,255,0.04) 0%, transparent 50%)',
                }} />
                <div className="relative mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                    <div className="grid items-center gap-12 lg:grid-cols-5">
                        {/* Left content */}
                        <motion.div
                            initial={{ opacity: 0, x: -30 }}
                            whileInView={{ opacity: 1, x: 0 }}
                            viewport={{ once: true }}
                            transition={{ duration: 0.5 }}
                            className="lg:col-span-3"
                        >
                            <div className="cyber-badge cyber-badge-magenta rounded-lg px-4 py-2 mb-4 inline-flex">
                                <Brain className="h-4 w-4" />
                                Diagnóstico Vocacional
                            </div>

                            <h2 className="text-3xl sm:text-4xl font-heading font-black leading-tight text-text-primary">
                                ¿No sabes qué carrera elegir?{' '}
                                <span className="neon-text-cyan inline-block">
                                    Descúbrelo aquí
                                </span>
                            </h2>

                            <p className="mt-4 text-base sm:text-lg text-text-secondary leading-relaxed">
                                Nuestro <strong className="text-neon-cyan">examen diagnóstico gratuito</strong> evalúa
                                tus conocimientos en todas las áreas académicas y te muestra{' '}
                                <strong className="text-text-primary">qué carreras se alinean mejor con tu perfil</strong>.
                                Obtén resultados detallados por materia y descubre tu verdadero potencial.
                            </p>

                            <div className="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {[
                                    { icon: Brain, text: 'Evalúa todas las áreas académicas', color: 'magenta' },
                                    { icon: Compass, text: 'Descubre carreras compatibles con tu perfil', color: 'cyan' },
                                    { icon: Route, text: 'Identifica tus fortalezas y debilidades', color: 'green' },
                                    { icon: BarChart3, text: 'Resultados detallados por materia', color: 'magenta' },
                                ].map((item, i) => (
                                    <motion.div
                                        key={i}
                                        initial={{ opacity: 0, y: 20 }}
                                        whileInView={{ opacity: 1, y: 0 }}
                                        viewport={{ once: true }}
                                        transition={{ duration: 0.4, delay: 0.2 + i * 0.08 }}
                                        className="flex items-start gap-3"
                                    >
                                        <div className={`flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg border ${
                                            item.color === 'magenta'
                                                ? 'bg-neon-magenta/10 border-neon-magenta/30'
                                                : item.color === 'cyan'
                                                ? 'bg-neon-cyan/10 border-neon-cyan/30'
                                                : 'bg-neon-green/10 border-neon-green/30'
                                        }`}>
                                            <item.icon className={`h-4 w-4 ${
                                                item.color === 'magenta'
                                                    ? 'text-neon-cyan/80'
                                                    : item.color === 'cyan'
                                                    ? 'text-neon-cyan'
                                                    : 'text-neon-green'
                                            }`} />
                                        </div>
                                        <span className="text-sm font-semibold text-text-secondary pt-1">{item.text}</span>
                                    </motion.div>
                                ))}
                            </div>

                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{ once: true }}
                                transition={{ duration: 0.4, delay: 0.5 }}
                                className="mt-8"
                            >
                                {auth.user ? (
                                    <Link
                                        href="/diagnostico"
                                        className="cyber-btn cyber-btn-primary cyber-btn-pulse rounded-xl px-6 py-3 text-sm sm:text-base inline-flex"
                                    >
                                        <Brain className="h-5 w-5" />
                                        Iniciar diagnóstico gratis
                                        <ArrowRight className="h-5 w-5" />
                                    </Link>
                                ) : (
                                    <a
                                        href="/register"
                                        className="cyber-btn cyber-btn-primary cyber-btn-pulse rounded-xl px-6 py-3 text-sm sm:text-base inline-flex"
                                    >
                                        <Brain className="h-5 w-5" />
                                        Iniciar diagnóstico gratis
                                        <ArrowRight className="h-5 w-5" />
                                    </a>
                                )}
                            </motion.div>
                        </motion.div>

                        {/* Right visual card */}
                        <motion.div
                            initial={{ opacity: 0, x: 30 }}
                            whileInView={{ opacity: 1, x: 0 }}
                            viewport={{ once: true }}
                            transition={{ duration: 0.5, delay: 0.2 }}
                            className="lg:col-span-2 relative"
                        >
                            {/* Main card */}
                            <div className="cyber-card cyber-card-magenta rounded-2xl overflow-hidden">
                                <div className="p-6 sm:p-8">
                                    <div className="flex items-center gap-3 mb-6">
                                        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-neon-magenta/15 border border-neon-magenta/40">
                                            <Brain className="h-6 w-6 text-neon-magenta" />
                                        </div>
                                        <div>
                                            <p className="font-heading font-bold text-sm text-text-primary">Resultado del diagnóstico</p>
                                            <p className="text-[10px] text-text-muted font-semibold">Ejemplo ilustrativo</p>
                                        </div>
                                    </div>

                                    {/* Score circle */}
                                    <div className="flex justify-center mb-6">
                                        <div className="relative">
                                            <svg className="w-32 h-32 -rotate-90" viewBox="0 0 120 120">
                                                <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(255,0,255,0.1)" strokeWidth="8" />
                                                <circle cx="60" cy="60" r="52" fill="none"
                                                    stroke="url(#grad-diagnostico)" strokeWidth="8"
                                                    strokeDasharray={`${2 * Math.PI * 52}`}
                                                    strokeDashoffset={`${2 * Math.PI * 52 * 0.35}`}
                                                    strokeLinecap="round"
                                                    style={{ filter: 'drop-shadow(0 0 8px rgba(255,0,255,0.4))' }} />
                                                <defs>
                                                    <linearGradient id="grad-diagnostico" x1="0%" y1="0%" x2="100%" y2="0%">
                                                        <stop offset="0%" stopColor="#ff00ff" />
                                                        <stop offset="100%" stopColor="#00f0ff" />
                                                    </linearGradient>
                                                </defs>
                                            </svg>
                                            <div className="absolute inset-0 flex flex-col items-center justify-center">
                                                <span className="text-3xl font-heading font-black text-neon-cyan neon-text">65%</span>
                                                <span className="text-[10px] font-bold text-text-muted uppercase tracking-wider">General</span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Concept bars */}
                                    <div className="space-y-3">
                                        {[
                                            { nombre: 'Razonamiento Matemático', pct: 78, color: 'from-neon-cyan to-neon-magenta' },
                                            { nombre: 'Comprensión Lectora', pct: 82, color: 'from-neon-green to-neon-cyan' },
                                            { nombre: 'Ciencias Naturales', pct: 45, color: 'from-neon-magenta to-neon-purple' },
                                            { nombre: 'Ciencias Sociales', pct: 60, color: 'from-neon-yellow to-neon-orange' },
                                        ].map((item) => (
                                            <div key={item.nombre}>
                                                <div className="flex justify-between text-xs mb-1">
                                                    <span className="font-semibold text-text-secondary">{item.nombre}</span>
                                                    <span className="font-bold text-text-muted">{item.pct}%</span>
                                                </div>
                                                <div className="h-2 w-full rounded-full bg-cyber-dark-300 overflow-hidden border border-cyber-dark-400/30">
                                                    <motion.div
                                                        initial={{ width: 0 }}
                                                        whileInView={{ width: `${item.pct}%` }}
                                                        viewport={{ once: true }}
                                                        transition={{ duration: 1, delay: 0.5, ease: 'easeOut' }}
                                                        className={`h-full rounded-full bg-gradient-to-r ${item.color}`}
                                                        style={{
                                                            boxShadow: item.pct >= 70
                                                                ? '0 0 8px rgba(0,240,255,0.4)'
                                                                : item.pct >= 50
                                                                ? '0 0 8px rgba(255,204,0,0.3)'
                                                                : '0 0 8px rgba(255,0,255,0.3)',
                                                        }}
                                                    />
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {/* Compatible careers */}
                                    <div className="mt-6 pt-5 border-t border-cyber-dark-400/30">
                                        <p className="text-xs font-bold text-neon-cyan uppercase tracking-widest mb-3 neon-text">
                                            <Star className="inline h-3.5 w-3.5 mr-1" />
                                            Carreras compatibles
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            {['Ing. de Sistemas', 'Ing. Civil', 'Administración', 'Arquitectura'].map((carrera) => (
                                                <span key={carrera}
                                                    className="px-3 py-1 rounded-lg text-[10px] font-heading font-bold bg-neon-cyan/10 border border-neon-cyan/30 text-neon-cyan"
                                                >
                                                    {carrera}
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Floating badge */}
                            <motion.div
                                animate={{ y: [0, -8, 0] }}
                                transition={{ duration: 3, repeat: Infinity, ease: 'easeInOut', delay: 0.5 }}
                                className="absolute -top-4 -right-4 cyber-badge cyber-badge-cyan rounded-xl px-4 py-2"
                            >
                                <Sparkles className="h-4 w-4" />
                                Gratis
                            </motion.div>
                        </motion.div>
                    </div>
                </div>
            </section>

            {/* ===== FEATURES / WHY CHOOSE US ===== */}
            <section className="bg-cyber-dark py-20 sm:py-24">
                <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        whileInView={{ opacity: 1, y: 0 }}
                        viewport={{ once: true }}
                        transition={{ duration: 0.5 }}
                        className="mx-auto max-w-3xl text-center mb-16"
                    >
                        <div className="cyber-badge cyber-badge-magenta rounded-lg px-4 py-2 mb-4 inline-flex">
                            <Target className="h-4 w-4" />
                            ¿Por qué nosotros?
                        </div>
                        <h2 className="text-3xl sm:text-4xl font-heading font-black leading-tight text-text-primary">
                            Todo lo que necesitas para{' '}
                            <span className="neon-text-cyan">ingresar</span>
                        </h2>
                        <p className="mt-3 text-sm sm:text-base text-text-secondary">
                            Más que un simulacro, una herramienta completa con retroalimentación inteligente.
                        </p>
                    </motion.div>

                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {razones.map((r, i) => {
                            const nc = neonColor(r.color);
                            return (
                                <motion.div
                                    key={r.titulo}
                                    initial={{ opacity: 0, y: 30 }}
                                    whileInView={{ opacity: 1, y: 0 }}
                                    viewport={{ once: true }}
                                    transition={{ duration: 0.4, delay: i * 0.1 }}
                                    whileHover={{ y: -4 }}
                                    className={`cyber-card rounded-xl p-6 group cyber-card-${r.color === 'green' ? 'green' : r.color === 'cyan' ? 'cyan' : r.color === 'magenta' ? 'magenta' : 'cyan'}`}
                                >
                                    <div className={`mb-4 inline-flex h-14 w-14 items-center justify-center rounded-xl bg-${nc}-100 border border-${nc}`}>
                                        <r.icon className={`h-7 w-7 text-${nc}`} />
                                    </div>
                                    <h3 className="font-black text-xs sm:text-sm uppercase tracking-wider text-text-primary mb-2">
                                        {r.titulo}
                                    </h3>
                                    <p className="text-xs sm:text-sm text-text-secondary leading-relaxed">{r.desc}</p>
                                    <div className="mt-4 flex items-center gap-2 text-xs font-bold text-neon-cyan">
                                        <span className="inline-block h-2 w-2 rounded-full bg-neon-cyan shadow-neon-cyan" />
                                        {r.stat}
                                    </div>
                                </motion.div>
                            );
                        })}
                    </div>
                </div>
            </section>

            {/* ===== UNIVERSIDADES ===== */}
            <InstitutionsList instituciones={instituciones} />

            {/* ===== TESTIMONIALS ===== */}
            <section className="bg-cyber-dark-100 border-y border-cyber-dark-400/50 py-20 sm:py-24">
                <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                    <TestimonialsSection testimonios={testimonios} onPlayVideo={openVideo} />
                </div>
            </section>

            {/* ===== FINAL CTA ===== */}
            <section className="relative overflow-hidden bg-cyber-dark-200 py-20 cyber-grid">
                <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(0,240,255,0.1),transparent_70%)]" />
                <div className="relative mx-auto max-w-4xl px-5 sm:px-8 lg:px-10 text-center">
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        whileInView={{ opacity: 1, y: 0 }}
                        viewport={{ once: true }}
                        transition={{ duration: 0.5 }}
                    >
                        <div className="cyber-badge cyber-badge-magenta rounded-lg px-4 py-2 mb-4 inline-flex">
                            <Sparkles className="h-4 w-4" />
                            Comienza ahora
                        </div>
                        <h2 className="text-3xl sm:text-4xl font-heading font-black text-text-primary leading-tight">
                            ¿Listo para empezar tu preparación?
                        </h2>
                        <p className="mx-auto mt-3 max-w-2xl text-sm sm:text-base text-text-secondary">
                            Únete a cientos de estudiantes que ya están practicando. Es gratis, siempre.
                        </p>
                        <div className="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                            <a
                                href="/auth/google"
                                className="cyber-btn cyber-btn-primary rounded-xl px-6 py-3 text-sm sm:text-base"
                            >
                                Comienza ahora — es gratis
                                <ArrowRight className="h-4 w-4 sm:h-5 sm:w-5" />
                            </a>
                            <Link
                                href="#carreras"
                                className="cyber-btn rounded-xl px-6 py-3 text-sm sm:text-base border-cyber-dark-400"
                            >
                                <BookOpen className="h-4 w-4 sm:h-5 sm:w-5" />
                                Ver carreras
                            </Link>
                        </div>
                    </motion.div>
                </div>
            </section>

            {/* ===== DISCLAIMER ===== */}
            <div className="bg-cyber-dark border-b border-cyber-dark-400/50">
                <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10 py-4">
                    <div className="flex items-start gap-3 text-xs text-text-muted">
                        <span className="mt-0.5 flex-shrink-0 flex h-5 w-5 items-center justify-center rounded border border-neon-yellow/50 bg-neon-yellow-50">
                            <span className="text-neon-yellow font-bold text-[10px]">!</span>
                        </span>
                        <p>
                            <strong className="text-text-primary">Aviso importante:</strong> Estos exámenes son{' '}
                            <strong className="text-text-primary">simulacros de preparación</strong> y no constituyen procesos de
                            admisión oficiales. Los resultados son referenciales.
                        </p>
                    </div>
                </div>
            </div>

            {/* ===== FOOTER ===== */}
            <Footer />

            <VideoModal videoSrc={videoSrc} isOpen={videoOpen} onClose={closeVideo} />
        </>
    );
}

// ===== SUB-COMPONENTS (inline for convenience) =====

function CategoriesGrid({ facultades = [] }) {
    const [activeTab, setActiveTab] = useState(facultades[0]?.nombre || null);

    if (facultades.length === 0) {
        return (
            <div className="text-center py-12">
                <p className="text-text-secondary font-semibold">Próximamente más carreras disponibles.</p>
            </div>
        );
    }

    const facultadActiva = facultades.find((f) => f.nombre === activeTab) || facultades[0];

    return (
        <div className="grid grid-cols-1 gap-0 lg:grid-cols-12">
            {/* Sidebar */}
            <div className="lg:col-span-4 xl:col-span-3">
                <div className="cyber-card rounded-xl overflow-hidden">
                    <div className="bg-cyber-dark-300 px-5 py-3 border-b border-cyber-dark-400/50">
                        <p className="text-xs font-bold text-neon-cyan uppercase tracking-widest neon-text-cyan">Facultades</p>
                    </div>
                    <ul className="divide-y divide-cyber-dark-400/50">
                        {facultades.map((fac) => {
                            const isActive = activeTab === fac.nombre;
                            return (
                                <li key={fac.nombre}>
                                    <button
                                        onClick={() => setActiveTab(fac.nombre)}
                                        className={`w-full text-left px-5 py-4 transition-all flex items-center justify-between ${
                                            isActive ? 'bg-neon-magenta/20 text-neon-cyan font-bold border-l-2 border-neon-magenta' : 'text-text-secondary hover:bg-surface-300 font-semibold'
                                        }`}
                                    >
                                        <span className="text-sm">{fac.nombre}</span>
                                        <ChevronRight className={`h-4 w-4 transition-transform ${isActive ? 'rotate-90 text-neon-magenta' : ''}`} />
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            </div>

            {/* Content */}
            <div className="lg:col-span-8 xl:col-span-9 lg:pl-6 mt-6 lg:mt-0">
                <motion.div
                    key={activeTab}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ duration: 0.3 }}
                >
                    <div className="cyber-card rounded-xl p-6 sm:p-8">
                        <div className="flex items-center gap-4 mb-6">
                            <div className="flex h-14 w-14 items-center justify-center rounded-xl bg-neon-cyan/20 text-neon-cyan border border-neon-cyan/50">
                                <BookOpen className="h-7 w-7 text-neon-cyan" />
                            </div>
                            <div>                                    <h3 className="font-heading font-black text-xl text-neon-cyan sm:text-2xl">{facultadActiva.nombre}</h3>
                                <p className="text-sm text-text-muted font-semibold mt-0.5">
                                    {facultadActiva.instituciones_count || 0} universidades · {facultadActiva.examenes_count || 0} simulacros
                                </p>
                            </div>
                        </div>

                        <div className="border-t border-cyber-dark-400/50 pt-6">
                            <p className="mb-4 text-xs font-bold uppercase tracking-widest text-text-muted">Carreras disponibles</p>
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
                                                className="cyber-card rounded-xl p-4 flex items-center gap-3 group hover:border-neon-cyan/40 transition-all"
                                            >
                                                <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-neon-cyan/10 text-neon-cyan border border-neon-cyan/30 group-hover:bg-neon-cyan/20 transition-colors">
                                                    <BookOpen className="h-5 w-5" />
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <p className="font-bold text-sm text-text-primary truncate">{carrera}</p>
                                                    <p className="text-xs text-text-muted font-semibold mt-0.5">Ver simulacros →</p>
                                                </div>
                                            </Link>
                                        </motion.div>
                                    ))
                                ) : (
                                    <p className="text-text-muted text-sm col-span-full">Próximamente más carreras disponibles.</p>
                                )}
                            </div>
                        </div>
                    </div>
                </motion.div>

                <div className="mt-6 text-center">
                    <a
                        href="/auth/google"
                        className="cyber-btn cyber-btn-primary rounded-xl px-8 py-3 text-sm inline-flex"
                    >
                        Ver todos los simulacros
                        <ChevronRight className="h-4 w-4" />
                    </a>
                </div>
            </div>
        </div>
    );
}

function InstitutionsList({ instituciones = {} }) {
    const allItems = [
        ...(instituciones.publica || []),
        ...(instituciones.privada || []),
    ];

    if (allItems.length === 0) return null;

    const [scrollEl, setScrollEl] = useState(null);
    const [canScrollLeft, setCanScrollLeft] = useState(false);
    const [canScrollRight, setCanScrollRight] = useState(true);

    const checkScroll = () => {
        if (!scrollEl) return;
        setCanScrollLeft(scrollEl.scrollLeft > 10);
        setCanScrollRight(scrollEl.scrollLeft < scrollEl.scrollWidth - scrollEl.clientWidth - 10);
    };

    return (
        <section className="bg-cyber-dark py-20 sm:py-24 overflow-hidden">
            <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
                <div className="flex items-end justify-between mb-12">
                    <div>
                        <div className="cyber-badge cyber-badge-magenta rounded-lg px-4 py-2 mb-4 inline-flex">
                            <GraduationCap className="h-4 w-4" />
                            Universidades
                        </div>
                        <h2 className="text-3xl sm:text-4xl font-heading font-black text-text-primary leading-tight">
                            Universidades disponibles
                        </h2>
                        <p className="mt-3 text-sm sm:text-base text-text-secondary">
                            Simulacros para las principales universidades del Perú
                        </p>
                    </div>
                    <div className="hidden sm:flex items-center gap-2">
                        <button
                            onClick={() => scrollEl?.scrollBy({ left: -340, behavior: 'smooth' })}
                            disabled={!canScrollLeft}
                            className="cyber-btn rounded-lg p-2.5 disabled:opacity-30"
                        >
                            <ChevronLeft className="h-5 w-5" />
                        </button>
                        <button
                            onClick={() => scrollEl?.scrollBy({ left: 340, behavior: 'smooth' })}
                            disabled={!canScrollRight}
                            className="cyber-btn rounded-lg p-2.5 disabled:opacity-30"
                        >
                            <ChevronRight className="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>

            <div
                ref={(el) => { if (el && !scrollEl) { setScrollEl(el); setTimeout(checkScroll, 100); } }}
                onScroll={checkScroll}
                className="flex gap-6 overflow-x-auto px-4 sm:px-[max(1rem,calc((100vw-80rem)/2+1.5rem))] pb-4"
                style={{ scrollbarWidth: 'none' }}
            >
                {allItems.map((inst) => (
                    <div
                        key={inst.id}
                        className="flex-shrink-0 w-80 cyber-card rounded-xl overflow-hidden group hover:-translate-y-1 transition-all duration-300"
                    >
                        <div className="relative h-48 w-full overflow-hidden bg-cyber-dark-300">
                            {inst.logo_url ? (
                                <img src={inst.logo_url} alt={inst.nombre} className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />
                            ) : (
                                <div className="flex h-full w-full items-center justify-center bg-neon-magenta/10">
                                    <span className="font-black text-6xl text-neon-cyan/20">{inst.nombre.charAt(0)}</span>
                                </div>
                            )}
                            <div className="absolute inset-0 bg-gradient-to-t from-cyber-dark/80 via-transparent to-transparent" />
                            <span className={`absolute top-3 right-3 cyber-badge rounded-lg px-2.5 py-1 text-[10px] ${
                                inst.subtipo === 'publica' ? 'cyber-badge-magenta' : 'cyber-badge-cyan'
                            }`}>
                                {inst.subtipo === 'publica' ? 'Pública' : 'Privada'}
                            </span>
                        </div>
                        <div className="p-5">                                    <h3 className="font-heading font-black text-base text-text-primary group-hover:text-neon-cyan transition-colors">{inst.nombre}</h3>
                            {inst.ciudad && <p className="text-sm text-text-muted font-semibold mt-1">{inst.ciudad}</p>}
                            <div className="mt-4 flex items-center justify-between border-t border-cyber-dark-400/50 pt-4">
                                <span className="text-xs font-bold text-text-muted">{inst.categorias_count || 0} carreras</span>
                                <span className="text-xs font-bold text-neon-cyan group-hover:underline">Ver simulacros →</span>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            <div className="text-center mt-8 sm:hidden">
                <p className="text-sm font-bold text-text-muted">Desliza para ver más universidades</p>
            </div>
        </section>
    );
}

function TestimonialsSection({ testimonios = [] }) {
    const items = testimonios.length > 0 ? testimonios : [
        { nombre: 'Carlos Mendoza', carrera: 'Ingeniería de Sistemas', universidad: 'UPC', texto: 'Los simulacros me ayudaron a identificar mis puntos débiles. Ingresé en mi primer intento gracias a la retroalimentación por temas.', nota: '18/20' },
        { nombre: 'Valeria Torres', carrera: 'Medicina Humana', universidad: 'UNMSM', texto: 'La retroalimentación por concepto es lo mejor. Supe exactamente qué estudiar para cada área y optimicé mi tiempo de preparación.', nota: '16/20' },
        { nombre: 'Diego Paredes', carrera: 'Arquitectura', universidad: 'Universidad de Lima', texto: '100% recomendado. Los simulacros son muy parecidos al examen real. El temporizador me ayudó a manejar mi tiempo.', nota: '17/20' },
    ];

    return (
        <>
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5 }}
                className="mx-auto max-w-3xl text-center mb-16"
            >
                <div className="cyber-badge cyber-badge-green rounded-lg px-4 py-2 mb-4 inline-flex">
                    <Star className="h-4 w-4" />
                    Historias de éxito
                </div>                        <h2 className="text-3xl sm:text-4xl font-heading font-black leading-tight text-text-primary">
                                    Lo que dicen nuestros{' '}
                                    <span className="text-neon-cyan">estudiantes</span>
                </h2>
            </motion.div>

            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {items.map((t, i) => (
                    <motion.div
                        key={i}
                        initial={{ opacity: 0, y: 30 }}
                        whileInView={{ opacity: 1, y: 0 }}
                        viewport={{ once: true }}
                        transition={{ duration: 0.4, delay: i * 0.1 }}
                        whileHover={{ y: -4 }}
                        className="cyber-card rounded-xl p-6 group"
                    >
                        <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-neon-magenta/10 border border-neon-magenta/30">
                            <Quote className="h-5 w-5 text-neon-magenta" />
                        </div>
                        <p className="text-sm text-text-secondary leading-relaxed">{t.texto}</p>
                        <div className="mt-4 flex gap-1">
                            {[...Array(5)].map((_, j) => (
                                <Star key={j} className="h-4 w-4 fill-neon-yellow text-neon-yellow" />
                            ))}
                        </div>
                        <div className="mt-4 flex items-center justify-between border-t border-cyber-dark-400/50 pt-4">
                            <div>
                                <p className="font-bold text-sm text-text-primary">{t.nombre}</p>
                                <p className="text-xs text-text-muted">
                                    {t.carrera} — <span className="text-neon-cyan">{t.universidad}</span>
                                </p>
                            </div>
                            <div className="cyber-badge cyber-badge-green rounded-lg px-2.5 py-1 text-xs font-bold">
                                {t.nota}
                            </div>
                        </div>
                    </motion.div>
                ))}
            </div>
        </>
    );
}

function Footer() {
    const socialLinks = [
        {
            href: '#', label: 'Facebook', brandColor: '#1877F2',
            path: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
        },
        {
            href: '#', label: 'Instagram', brandColor: '#E4405F',
            path: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z',
        },
        {
            href: '#', label: 'Twitter / X', brandColor: '#000000',
            path: 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
        },
        {
            href: '#', label: 'LinkedIn', brandColor: '#0A66C2',
            path: 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z',
        },
        {
            href: '#', label: 'YouTube', brandColor: '#FF0000',
            path: 'M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
        },
    ];

    return (
        <footer className="relative bg-cyber-dark-200 border-t border-cyber-dark-400/50 overflow-hidden">
            {/* Top glow lines */}
            <div className="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-neon-cyan to-transparent opacity-50" />
            <div className="absolute top-0 left-1/4 right-1/4 h-px bg-gradient-to-r from-transparent via-neon-magenta to-transparent opacity-20 blur-sm" />

            {/* Background grid pattern */}
            <div className="absolute inset-0 opacity-[0.02]"
                style={{
                    backgroundImage: 'linear-gradient(rgba(0,240,255,0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(0,240,255,0.3) 1px, transparent 1px)',
                    backgroundSize: '60px 60px',
                }}
            />

            <div className="relative mx-auto max-w-7xl px-5 sm:px-8 lg:px-10 py-16">
                <div className="grid gap-12 sm:grid-cols-2 lg:grid-cols-12">
                    {/* Brand - 5 columns */}
                    <div className="lg:col-span-5">
                        <Link href="/" className="flex items-center gap-3 group mb-5">
                            <div className="cyber-logo">
                                P
                            </div>
                            <span className="font-heading font-bold text-sm sm:text-base tracking-tight neon-text-cyan group-hover:brightness-125 transition-all">
                            Prepárate y Postula Ya
                        </span>
                        </Link>
                        <p className="text-xs leading-relaxed text-text-muted max-w-sm">
                            Simulacros gratuitos de preparación para exámenes de admisión universitaria.
                            Retroalimentación inmediata, seguimiento de progreso y{' '}
                            <span className="text-neon-cyan font-bold">100% gratis</span>.
                        </p>

                        {/* Redes sociales - mejoradas */}
                        <div className="mt-6">
                            <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-text-muted mb-3">Síguenos</p>
                            <div className="flex items-center gap-2.5">
                                {socialLinks.map((social) => (
                                    <a
                                        key={social.label}
                                        href={social.href}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label={social.label}
                                        title={social.label}
                                        className="group/social relative flex h-11 w-11 items-center justify-center rounded-xl border border-cyber-dark-400/30 bg-cyber-dark-300 text-text-muted transition-all duration-300 hover:-translate-y-1"
                                        onMouseEnter={(e) => {
                                            const el = e.currentTarget;
                                            el.style.borderColor = social.brandColor;
                                            el.style.boxShadow = `0 0 15px ${social.brandColor}33, inset 0 0 20px ${social.brandColor}11`;
                                            el.style.backgroundColor = `${social.brandColor}11`;
                                            const svg = el.querySelector('svg');
                                            if (svg) {
                                                svg.style.color = social.brandColor;
                                                svg.style.filter = `drop-shadow(0 0 6px ${social.brandColor}66)`;
                                            }
                                        }}
                                        onMouseLeave={(e) => {
                                            const el = e.currentTarget;
                                            el.style.borderColor = '';
                                            el.style.boxShadow = '';
                                            el.style.backgroundColor = '';
                                            const svg = el.querySelector('svg');
                                            if (svg) {
                                                svg.style.color = '';
                                                svg.style.filter = '';
                                            }
                                        }}
                                    >
                                        <svg
                                            className="h-5 w-5 transition-all duration-300"
                                            fill="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path d={social.path} />
                                        </svg>
                                    </a>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Plataforma - 3 columns */}
                    <div className="lg:col-span-3 lg:col-start-7">
                        <h3 className="font-heading font-bold text-xs uppercase tracking-[0.15em] text-neon-cyan mb-5 flex items-center gap-2">
                            <span className="inline-block h-3 w-1 rounded-full bg-neon-cyan shadow-neon-cyan" />
                            Plataforma
                        </h3>
                        <ul className="space-y-3.5">
                            {[
                                { label: 'Carreras', href: '#carreras' },
                                { label: 'Simulacros', href: '/examenes' },
                                { label: 'Diagnóstico', href: '/diagnostico' },
                                { label: 'Mi Progreso', href: '/progreso' },
                            ].map((item) => (
                                <li key={item.label}>
                                    <Link
                                        href={item.href}
                                        className="group flex items-center gap-3 text-xs font-semibold text-text-muted hover:text-white transition-all duration-300"
                                    >
                                        <span className="inline-block h-px w-0 bg-gradient-to-r from-neon-cyan to-transparent transition-all duration-300 group-hover:w-5" />
                                        <span className="group-hover:translate-x-1 transition-transform duration-300">
                                            {item.label}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/* Soporte - 3 columns */}
                    <div className="lg:col-span-3 lg:col-start-10">
                        <h3 className="font-heading font-bold text-xs uppercase tracking-[0.15em] text-neon-cyan mb-5 flex items-center gap-2">
                            <span className="inline-block h-3 w-1 rounded-full bg-neon-cyan shadow-[0_0_6px_rgba(0,240,255,0.5)]" />
                            Soporte
                        </h3>
                        <ul className="space-y-3.5">
                            {[
                                { label: 'Soporte Técnico', href: '#' },
                                { label: 'Contacto', href: '/' },
                            ].map((item) => (
                                <li key={item.label}>
                                    <Link
                                        href={item.href}
                                        className="group flex items-center gap-3 text-xs font-semibold text-text-muted hover:text-white transition-all duration-300"
                                    >
                                        <span className="inline-block h-px w-0 bg-gradient-to-r from-neon-magenta to-transparent transition-all duration-300 group-hover:w-5" />
                                        <span className="group-hover:translate-x-1 transition-transform duration-300">
                                            {item.label}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>

                {/* Divider with glow */}
                <div className="relative my-10">
                    <div className="cyber-divider" />
                    <div className="absolute top-0 left-1/3 right-1/3 h-px bg-gradient-to-r from-transparent via-neon-magenta to-transparent opacity-30 blur-sm" />
                </div>

                {/* Bottom bar */}
                <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p className="text-xs text-text-muted">
                        &copy; {new Date().getFullYear()} Prepárate y Postula Ya. Todos los derechos reservados.
                    </p>
                    <div className="flex items-center gap-3">
                        <span className="hidden sm:inline-block h-3 w-px bg-cyber-dark-400" />
                        <p className="text-xs font-semibold text-text-muted flex items-center gap-1.5">
                            <span>Desarrollado por</span>
                            <a
                                href="https://alexiodigital.com"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="relative font-heading font-bold text-sm text-neon-cyan hover:text-white transition-all duration-300 group"
                            >
                                <span className="relative z-10 neon-text">alexiodigital.com</span>
                                <span className="absolute -bottom-0.5 left-0 right-0 h-px bg-gradient-to-r from-neon-cyan to-neon-magenta scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left" />
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    );
}
