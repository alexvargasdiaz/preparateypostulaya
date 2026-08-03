import { Link, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
    LayoutDashboard, FileText, BookOpen, BarChart3, Bell,
    Building2, GraduationCap, ClipboardList, Users, UserCog,
    LogOut, Menu, X, ChevronRight, UserCircle, Brain, Target, Layers, ChevronLeft,
    Activity
} from 'lucide-react';

const navigation = [
    { name: 'Mi Panel', href: '/dashboard', icon: LayoutDashboard },
    { name: 'Mi Perfil', href: '/mi-perfil', icon: UserCircle },
];

const studentNav = [
    { name: 'Diagnóstico', href: '/diagnostico', icon: Brain },
    { name: 'Áreas Académicas', href: '/examenes/areas-academicas', icon: Layers },
    { name: 'Universidades', href: '/examenes/universidades', icon: Building2 },
    { name: 'Historial', href: '/historial', icon: ClipboardList },
    { name: 'Mi Progreso', href: '/progreso', icon: BarChart3 },
];

const adminGroups = [
    {
        label: 'Contenido',
        items: [
            { name: 'Áreas Académicas', href: '/admin/areas-academicas', icon: Layers },
            { name: 'Diagnóstico', href: '/admin/diagnostico/configurar', icon: Brain },
            { name: 'Preguntas', href: '/admin/baul-preguntas', icon: BookOpen },
            { name: 'Exámenes', href: '/admin/examenes', icon: ClipboardList },
        ],
    },
    {
        label: 'Catálogo',
        items: [
            { name: 'Universidades', href: '/admin/instituciones', icon: Building2 },
            { name: 'Carreras', href: '/admin/categorias', icon: GraduationCap },
            { name: 'Requisitos', href: '/admin/requisitos-carrera', icon: Target },
        ],
    },
    {
        label: 'Usuarios',
        items: [
            { name: 'Alumnos', href: '/admin/alumnos', icon: Users },
            { name: 'Usuarios', href: '/admin/usuarios', icon: UserCog },
        ],
    },
    {
        label: 'Monitoreo',
        items: [
            { name: 'Bitácora de Procesos', href: '/admin/bitacora', icon: Activity },
        ],
    },
];

function NavItem({ item, isActive, onClick, collapsed }) {
    const Icon = item.icon;

    return (
        <Link
            href={item.href}
            onClick={onClick}
            className={`group flex items-center gap-3 rounded-xl transition-all duration-200 ${
                collapsed ? 'justify-center px-2 py-3' : 'px-3 py-2.5'
            } ${
                isActive
                    ? 'bg-neon-cyan/10 text-neon-cyan border border-neon-cyan/30 shadow-neon-cyan'
                    : 'text-text-muted hover:bg-neon-cyan/5 hover:text-white border border-transparent'
            }`}
            title={collapsed ? item.name : undefined}
        >
            <Icon className={`h-5 w-5 flex-shrink-0 ${isActive ? 'text-neon-cyan' : 'text-text-muted group-hover:text-white'}`} />
            {!collapsed && (
                <>
                    <span className="flex-1 text-sm font-bold">{item.name}</span>
                    {isActive && <ChevronRight className="h-4 w-4 text-neon-cyan" />}
                </>
            )}
        </Link>
    );
}

export default function AuthenticatedLayout({ children }) {
    const { url, props } = usePage();
    const { auth } = props;
    const user = auth?.user;

    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [collapsed, setCollapsed] = useState(false);
    const [mounted, setMounted] = useState(false);

    useEffect(() => { setSidebarOpen(false); }, [url]);
    useEffect(() => { setMounted(true); }, []);
    useEffect(() => {
        const handleEsc = (e) => { if (e.key === 'Escape') setSidebarOpen(false); };
        window.addEventListener('keydown', handleEsc);
        return () => window.removeEventListener('keydown', handleEsc);
    }, []);

    const isActiveNav = (href) => {
        const allItems = [
            ...navigation,
            { name: 'Notificaciones', href: '/notificaciones' },
            ...(user?.rol === 'estudiante' ? studentNav : []),
            ...(user?.rol === 'super_admin' || user?.rol === 'admin' ? adminGroups.flatMap(g => g.items) : []),
        ];
        const match = allItems
            .filter(item => url === item.href || url.startsWith(item.href + '/'))
            .sort((a, b) => b.href.length - a.href.length)[0];
        return match?.href === href;
    };

    return (
        <div className="min-h-screen bg-cyber-dark">
            {/* Mobile overlay */}
            <AnimatePresence>
                {sidebarOpen && (
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="fixed inset-0 z-40 bg-cyber-dark/80 backdrop-blur-sm lg:hidden"
                        onClick={() => setSidebarOpen(false)}
                    />
                )}
            </AnimatePresence>

            {/* Sidebar */}
            <aside
                className={`fixed inset-y-0 left-0 z-50 flex flex-col bg-cyber-dark/95 backdrop-blur-xl border-r border-neon-cyan/10 shadow-[4px_0_30px_rgba(0,240,255,0.1)] transition-all duration-300 ease-in-out ${
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                } lg:translate-x-0 ${collapsed ? 'w-20' : 'w-64'}`}
            >
                {/* Logo */}
                <div className={`flex items-center border-b border-neon-cyan/10 ${collapsed ? 'justify-center px-3' : 'px-5'} h-16`}>
                    <Link href="/dashboard" className={`flex items-center ${collapsed ? '' : 'gap-3'}`}>
                        <div className="cyber-logo-sidebar">
                            P
                        </div>
                        {!collapsed && (
                            <span className="text-sm font-heading font-bold neon-text-cyan leading-tight">
                                Prepárate y Postula<br />Ya
                            </span>
                        )}
                    </Link>
                    <button onClick={() => setSidebarOpen(false)} className="ml-auto rounded-lg p-1.5 text-text-muted hover:bg-surface-300 hover:text-text-primary lg:hidden">
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Navigation */}
                <nav className="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                    {navigation.map((item) => (
                        <NavItem key={item.href} item={item} isActive={isActiveNav(item.href)} onClick={() => setSidebarOpen(false)} collapsed={collapsed} />
                    ))}

                    <NavItem item={{ name: 'Notificaciones', href: '/notificaciones', icon: Bell }} isActive={isActiveNav('/notificaciones')} onClick={() => setSidebarOpen(false)} collapsed={collapsed} />

                    {user?.rol === 'estudiante' && (
                        <>
                            <div className="relative my-4">
                                <div className="absolute inset-0 flex items-center">
                                    <div className="w-full border-t border-cyber-dark-400/30" />
                                </div>
                                <div className="relative flex justify-center">
                                    <span className={`${collapsed ? '' : 'px-3'} text-[10px] font-bold uppercase tracking-widest text-neon-cyan bg-cyber-dark`}>
                                        {collapsed ? '•••' : 'Estudio'}
                                    </span>
                                </div>
                            </div>
                            {studentNav.map((item) => (
                                <NavItem key={item.href} item={item} isActive={isActiveNav(item.href)} onClick={() => setSidebarOpen(false)} collapsed={collapsed} />
                            ))}
                        </>
                    )}

                    {(user?.rol === 'super_admin' || user?.rol === 'admin') && (
                        <>
                            <div className="relative my-4">
                                <div className="absolute inset-0 flex items-center">
                                    <div className="w-full border-t border-cyber-dark-400/30" />
                                </div>
                                <div className="relative flex justify-center">
                                    <span className={`${collapsed ? '' : 'px-3'} text-[10px] font-bold uppercase tracking-widest text-neon-cyan bg-cyber-dark`}>
                                        {collapsed ? '•••' : 'Admin'}
                                    </span>
                                </div>
                            </div>
                            {adminGroups.map((group, gi) => (
                                <div key={group.label}>
                                    {gi > 0 && (
                                        <div className="my-3 border-t border-cyber-dark-400/20" />
                                    )}
                                    {group.items.map((item) => (
                                        <NavItem key={item.href} item={item} isActive={isActiveNav(item.href)} onClick={() => setSidebarOpen(false)} collapsed={collapsed} />
                                    ))}
                                </div>
                            ))}
                        </>
                    )}
                </nav>

                {/* Collapse toggle */}
                <button
                    onClick={() => setCollapsed(!collapsed)}
                    className="hidden lg:flex items-center justify-center border-t border-neon-cyan/10 py-3 text-text-muted hover:text-white hover:bg-neon-cyan/5 transition-all"
                >
                    <ChevronLeft className={`h-4 w-4 transition-transform ${collapsed ? 'rotate-180' : ''}`} />
                </button>

                {/* User info */}
                <div className="border-t border-neon-cyan/10 p-4 bg-neon-cyan/[0.02]">
                    <div className={`flex items-center ${collapsed ? 'justify-center' : 'gap-3'}`}>
                        {user?.foto ? (
                            <img src={user.foto} alt={user.name} className="h-9 w-9 flex-shrink-0 rounded-xl object-cover ring-2 ring-neon-cyan/30" />
                        ) : (
                            <div className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-neon-cyan/15 text-sm font-bold text-neon-cyan ring-1 ring-neon-cyan/40 shadow-neon-cyan">
                                {user?.name?.charAt(0)?.toUpperCase() || '?'}
                            </div>
                        )}
                        {!collapsed && (
                            <>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-heading font-bold text-text-primary">{user?.name || 'Usuario'}</p>
                                    <p className="truncate text-xs text-text-muted">{user?.email || ''}</p>
                                </div>
                                <Link href="/logout" method="post" as="button"
                                    className="flex-shrink-0 rounded-lg p-1.5 text-text-muted hover:text-white hover:bg-neon-cyan/10 transition-all"
                                    title="Cerrar sesión">
                                    <LogOut className="h-4 w-4" />
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </aside>

            {/* Main content */}
            <div className={`min-h-screen transition-all duration-300 ${mounted ? (collapsed ? 'lg:ml-20' : 'lg:ml-64') : ''}`}>
                {/* Top bar - mobile */}
                <header                    className="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-cyber-dark-400/50 cyber-header-glass px-4 lg:hidden">
                    <button onClick={() => setSidebarOpen(true)} className="cyber-btn-wallet-ghost rounded-lg p-2 border-0">
                        <Menu className="h-5 w-5 text-text-primary" />
                    </button>
                    <Link href="/dashboard" className="flex items-center gap-2 text-sm font-bold text-text-primary">
                        <div className="cyber-logo-sidebar" style={{ width: 28, height: 28, fontSize: '0.75rem' }}>P</div>
                        Prepárate y Postula Ya
                    </Link>
                </header>

                <main>{children}</main>
            </div>
        </div>
    );
}
