import { Head, Link } from '@inertiajs/react';
import { motion } from 'motion/react';
import {
    ClipboardList, FileText, BarChart3, Building2, GraduationCap,
    BookOpen, Users, UserCog, Brain,
    ArrowRight, CheckCircle2, Clock, LogOut, TrendingUp, Target, Layers,
    Sparkles, ChevronRight
} from 'lucide-react';

const containerVariants = { hidden: {}, visible: { transition: { staggerChildren: 0.08 } } };
const itemVariants = { hidden: { opacity: 0, y: 16 }, visible: { opacity: 1, y: 0, transition: { duration: 0.35 } } };

export default function Dashboard({ auth, ultimosIntentos = [], porInstitucion = [] }) {
    const esAdmin = auth.user?.rol === 'super_admin' || auth.user?.rol === 'admin';
    const esEstudiante = auth.user?.rol === 'estudiante';

    const studentCards = [
        { href: '/diagnostico', icon: Brain, title: 'Diagnóstico', desc: 'Evalúa tu nivel', border: 'border-neon-magenta/40', text: 'text-neon-magenta' },
        { href: '/examenes/areas-academicas', icon: Layers, title: 'Áreas Académicas', desc: 'Simulacro por área', border: 'border-neon-green/40', text: 'text-neon-green' },
        { href: '/historial', icon: ClipboardList, title: 'Mi historial', desc: 'Exámenes rendidos', border: 'border-neon-cyan/40', text: 'text-neon-cyan' },
        { href: '/examenes', icon: FileText, title: 'Nuevo examen', desc: 'Rinde un simulacro', border: 'border-neon-blue/40', text: 'text-neon-blue' },
        { href: '/progreso', icon: BarChart3, title: 'Mi progreso', desc: 'Evolución', border: 'border-neon-yellow/40', text: 'text-neon-yellow' },
    ];

    const adminCards = [
        { href: '/admin/instituciones', icon: Building2, title: 'Universidades', desc: 'Gestionar instituciones', border: 'border-neon-magenta/40', text: 'text-neon-magenta' },
        { href: '/admin/categorias', icon: GraduationCap, title: 'Carreras', desc: 'Gestionar carreras', border: 'border-neon-green/40', text: 'text-neon-green' },
        { href: '/admin/examenes', icon: ClipboardList, title: 'Exámenes', desc: 'Configurar simulacros', border: 'border-neon-cyan/40', text: 'text-neon-cyan' },
        { href: '/admin/preguntas', icon: BookOpen, title: 'Preguntas', desc: 'Banco de preguntas', border: 'border-neon-blue/40', text: 'text-neon-blue' },
        { href: '/admin/requisitos-carrera', icon: Target, title: 'Requisitos', desc: 'Mínimos por carrera', border: 'border-neon-yellow/40', text: 'text-neon-yellow' },
        { href: '/admin/alumnos', icon: Users, title: 'Alumnos', desc: 'Gestionar estudiantes', border: 'border-neon-purple/40', text: 'text-neon-purple' },
        { href: '/admin/usuarios', icon: UserCog, title: 'Usuarios', desc: 'Gestionar admins', border: 'border-neon-orange/40', text: 'text-neon-orange' },
    ];

    const cards = esEstudiante ? studentCards : adminCards;

    return (
        <>
            <Head title="Mi Panel" />

            {/* Header */}
            <div className="cyber-header-glass" style={{position:'relative'}}>
                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 py-8">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <div className="cyber-badge cyber-badge-magenta rounded-lg px-4 py-1.5 mb-3 inline-flex text-[10px]">
                                <Sparkles className="h-3 w-3" />
                                Panel de control
                            </div>
                            <h1 className="text-2xl sm:text-3xl font-heading font-black text-text-primary">
                                Bienvenido{esAdmin ? ' al panel' : ''}, {auth.user?.name}
                            </h1>
                            <p className="mt-1 text-sm text-text-muted font-semibold">{auth.user?.email}</p>
                        </div>
                        <div className="flex items-center gap-3">
                            {esEstudiante && (
                                <Link href="/examenes" className="cyber-btn-wallet rounded-xl px-5 py-3 text-sm">
                                    Nuevo simulacro
                                    <ArrowRight className="h-4 w-4" />
                                </Link>
                            )}
                            <Link href="/logout" method="post" as="button"
                                className="cyber-btn rounded-xl px-4 py-3 text-sm border-cyber-dark-400">
                                <LogOut className="h-4 w-4" />
                                Salir
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-6 pb-12">
                {/* Cards Grid */}
                <motion.div variants={containerVariants} initial="hidden" animate="visible"
                    className={`grid gap-4 ${esEstudiante ? 'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5' : 'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'}`}>
                    {cards.map((card) => (
                        <motion.div key={card.href} variants={itemVariants}>
                            <Link href={card.href} className="cyber-card rounded-xl p-5 flex items-center gap-4 group hover:-translate-y-1 transition-all duration-200">
                                <div className={`flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-xl bg-cyber-dark-300/50 ${card.border} ${card.text} backdrop-blur-sm border group-hover:scale-105 transition-transform`}>
                                    <card.icon className="h-7 w-7" />
                                </div>
                                <div>
                                    <p className={`font-black text-sm ${card.text}`}>{card.title}</p>
                                    <p className="text-xs text-text-muted font-semibold mt-0.5">{card.desc}</p>
                                </div>
                            </Link>
                        </motion.div>
                    ))}
                </motion.div>

                {/* Recent attempts */}
                {esEstudiante && (
                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.3 }} className="mt-6">
                        <div className="cyber-card rounded-xl p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h2 className="font-heading font-black text-lg text-text-primary">Últimos simulacros</h2>
                                <Link href="/historial" className="text-sm font-bold text-neon-cyan hover:underline flex items-center gap-1">
                                    Ver todos <ArrowRight className="h-4 w-4" />
                                </Link>
                            </div>

                            {(!ultimosIntentos || ultimosIntentos.length === 0) ? (
                                <div className="text-center py-10">
                                    <Clock className="mx-auto h-12 w-12 text-text-muted" />
                                    <p className="mt-3 text-text-secondary font-semibold">Aún no has rendido ningún simulacro.</p>
                                    <Link href="/examenes" className="cyber-btn-wallet rounded-xl px-5 py-2.5 text-sm mt-4 inline-flex">
                                        Comenzar ahora <ArrowRight className="h-4 w-4" />
                                    </Link>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {ultimosIntentos.map((intento) => (
                                        <div key={intento.id} className="cyber-card rounded-xl p-4 flex items-center justify-between hover:border-neon-cyan/30 transition-all">
                                            <div className="flex items-center gap-3 min-w-0 flex-1">
                                                {intento.aprobado ? (
                                                    <CheckCircle2 className="h-5 w-5 text-neon-green flex-shrink-0" />
                                                ) : (
                                                    <Clock className="h-5 w-5 text-text-muted flex-shrink-0" />
                                                )}
                                                <div className="min-w-0">
                                                    <p className="text-sm font-bold text-text-primary truncate">
                                                        {intento.examen?.titulo || (intento.institucion?.nombre ? `${intento.institucion.nombre} — ${intento.carrera || 'Simulacro'}` : (intento.carrera || 'Examen diagnóstico'))}
                                                    </p>
                                                    <p className="text-xs text-text-muted font-semibold">
                                                        {new Date(intento.created_at).toLocaleDateString('es-PE')}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-3 flex-shrink-0 ml-3">
                                                <span className="text-sm font-black text-text-primary">{intento.puntaje_total}/{intento.puntaje_maximo}</span>
                                                <Link href={`/resultados/${intento.id}`}
                                                    className="cyber-btn-wallet rounded-lg px-3 py-1.5 text-xs" style={{paddingTop:'0.375rem',paddingBottom:'0.375rem'}}>
                                                    Ver
                                                </Link>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </motion.div>
                )}

                {/* Performance by university */}
                {esEstudiante && porInstitucion.length > 0 && (
                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.4 }} className="mt-6">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-neon-magenta/20 text-neon-magenta border border-neon-magenta/30">
                                <TrendingUp className="h-5 w-5" />
                            </div>
                            <div>
                                <h2 className="font-heading font-black text-lg text-text-primary">Rendimiento por universidad</h2>
                                <p className="text-xs text-text-muted font-semibold">Desglose de simulacros por institución y carrera</p>
                            </div>
                        </div>
                        <div className="space-y-4">
                            {porInstitucion.map((grupo) => (
                                <UniversitySection key={grupo.institucion_id} grupo={grupo} />
                            ))}
                        </div>
                    </motion.div>
                )}

                {/* Empty state */}
                {esEstudiante && porInstitucion.length === 0 && ultimosIntentos.length > 0 && (
                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.4 }} className="mt-6">
                        <div className="cyber-card rounded-xl p-6">
                            <div className="flex items-center gap-2 mb-3">
                                <TrendingUp className="h-5 w-5 text-neon-cyan" />
                                <h2 className="font-black text-text-primary">Rendimiento por universidad</h2>
                            </div>
                            <div className="text-center py-8">
                                <Building2 className="mx-auto h-10 w-10 text-text-muted" />
                                <p className="mt-3 text-sm text-text-secondary font-semibold">Aún no tienes datos agrupados por universidad.</p>
                            </div>
                        </div>
                    </motion.div>
                )}
            </div>
        </>
    );
}

function BarChart({ items, height = 180 }) {
    if (!items || items.length === 0) return null;
    const maxVal = Math.max(...items.map((d) => d.promedio), 100);
    const barWidth = Math.min(60, Math.floor(500 / items.length));
    const gap = Math.max(12, Math.floor(200 / items.length));
    const totalW = items.length * (barWidth + gap) - gap;
    const width = Math.max(totalW + 80, 300);
    const padding = { top: 20, right: 20, bottom: 50, left: 45 };
    const chartW = width - padding.left - padding.right;
    const chartH = height - padding.top - padding.bottom;

    return (
        <svg width="100%" height={height} viewBox={`0 0 ${width} ${height}`} className="overflow-visible">
            {[0, 25, 50, 75, 100].map((v) => (
                <g key={v}>
                    <line x1={padding.left} y1={padding.top + chartH - (v / 100) * chartH} x2={width - padding.right} y2={padding.top + chartH - (v / 100) * chartH} stroke="rgba(0,240,255,0.1)" strokeWidth={1} strokeDasharray={v === 60 ? '0' : '4 4'} />
                    <text x={padding.left - 8} y={padding.top + chartH - (v / 100) * chartH + 4} textAnchor="end" className="fill-text-muted text-[10px] font-semibold">{v}%</text>
                </g>
            ))}
            <line x1={padding.left} y1={padding.top + chartH - (60 / 100) * chartH} x2={width - padding.right} y2={padding.top + chartH - (60 / 100) * chartH} stroke="rgba(0,240,255,0.5)" strokeWidth={1.5} strokeDasharray="6 3" opacity={0.8} />
            <text x={width - padding.right + 4} y={padding.top + chartH - (60 / 100) * chartH + 4} className="fill-neon-cyan text-[9px] font-bold" opacity={0.8}>Aprobado</text>
            {items.map((item, i) => {
                const barH = (item.promedio / 100) * chartH;
                const x = padding.left + i * (barWidth + gap) + (gap / 2);
                const y = padding.top + chartH - barH;
                const color = item.promedio >= 60 ? '#00f0ff' : item.promedio >= 40 ? '#4488ff' : '#5c6477';
                return (
                    <g key={i}>
                        <rect x={x} y={y} width={barWidth} height={barH} rx={6} fill={color} opacity={0.85} className="transition-all duration-500">
                            <title>{item.nombre}: {item.promedio}% ({item.intentos} exámenes)</title>
                        </rect>
                        <text x={x + barWidth / 2} y={y - 6} textAnchor="middle" className="fill-text-primary text-[11px] font-bold">{item.promedio}%</text>
                        <text x={x + barWidth / 2} y={padding.top + chartH + 16} textAnchor="middle" className="fill-text-muted text-[9px] font-semibold">
                            {item.nombre.length > 8 ? item.nombre.substring(0, 7) + '...' : item.nombre}
                        </text>
                    </g>
                );
            })}
        </svg>
    );
}

function UniversitySection({ grupo }) {
    const carreras = Object.values(grupo.carreras);
    const totalIntentos = carreras.reduce((s, c) => s + c.intentos, 0);
    const totalAprobados = carreras.reduce((s, c) => s + c.aprobados, 0);
    const promedioGeneral = carreras.length > 0 ? Math.round(carreras.reduce((s, c) => s + c.promedio, 0) / carreras.length) : 0;

    return (
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="cyber-card rounded-xl overflow-hidden">
            <div className="bg-cyber-dark-300 px-5 py-4 border-b border-cyber-dark-400/50">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-neon-magenta/20 text-neon-magenta border border-neon-magenta/30">
                            <Building2 className="h-5 w-5" />
                        </div>
                        <div>
                            <h3 className="font-heading font-bold text-text-primary">{grupo.institucion_nombre}</h3>
                            <p className="text-xs text-text-muted font-semibold">{carreras.length} carreras · {totalIntentos} simulacros</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-4 text-sm">
                        <div className="text-center">
                            <p className="text-lg font-black text-neon-cyan neon-text-cyan">{promedioGeneral}%</p>
                            <p className="text-[10px] text-text-muted uppercase font-bold">Promedio</p>
                        </div>
                        <div className="text-center">
                            <p className="text-lg font-black text-neon-green">{totalAprobados}/{totalIntentos}</p>
                            <p className="text-[10px] text-text-muted uppercase font-bold">Aprobados</p>
                        </div>
                    </div>
                </div>
            </div>

            <div className="p-5">
                <div className="w-full overflow-x-auto">
                    <div className="min-w-[300px]">
                        <BarChart items={carreras.map((c) => ({ nombre: c.nombre, promedio: c.promedio, intentos: c.intentos }))} height={180} />
                    </div>
                </div>

                <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {carreras.map((carrera) => (
                        <div key={carrera.nombre} className="cyber-card rounded-xl p-4">
                            <div className="flex items-center justify-between mb-2">                                    <p className="text-sm font-heading font-bold text-text-primary truncate">{carrera.nombre}</p>
                                <span className={`ml-2 flex-shrink-0 cyber-badge rounded-lg px-2 py-0.5 text-[10px] ${
                                    carrera.promedio >= 60 ? 'cyber-badge-green' : carrera.promedio >= 40 ? 'cyber-badge-cyan' : 'bg-cyber-dark-400 text-text-muted'
                                }`}>
                                    {carrera.promedio}%
                                </span>
                            </div>
                            <div className="h-2.5 w-full rounded-full bg-cyber-dark-300 border border-cyber-dark-400/50 overflow-hidden">
                                <div className={`h-full rounded-full transition-all duration-500 ${
                                    carrera.promedio >= 60 ? 'bg-neon-green' : carrera.promedio >= 40 ? 'bg-neon-cyan' : 'bg-text-muted'
                                }`} style={{ width: `${carrera.promedio}%` }} />
                            </div>
                            <div className="mt-2 flex items-center justify-between text-xs font-bold text-text-muted">
                                <span>{carrera.intentos} intento{carrera.intentos !== 1 ? 's' : ''}</span>
                                <span>Mejor: {carrera.mejor_puntaje}%</span>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </motion.div>
    );
}
