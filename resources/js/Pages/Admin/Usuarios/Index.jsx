import { Head, Link, router } from '@inertiajs/react';
import { useState, useRef } from 'react';
import { motion } from 'motion/react';
import { useDialog } from '@/Components/DialogProvider';
import {
    Search, Download, Upload, Clock, CheckCircle2, XCircle,
    Shield, Wrench, GraduationCap,    Pencil, Trash2, Users,
} from 'lucide-react';

const estadoEstilos = {
    pendiente: { bg: 'bg-neon-yellow/10 text-neon-yellow border border-neon-yellow/30', Icon: Clock },
    activo: { bg: 'bg-neon-green/10 text-neon-green border border-neon-green/30', Icon: CheckCircle2 },
    rechazado: { bg: 'bg-neon-magenta/10 text-neon-magenta border border-neon-magenta/30', Icon: XCircle },
};

const rolConfig = {
    super_admin: { label: 'Super Admin', Icon: Shield, bgClass: 'bg-neon-magenta/10 text-neon-magenta border border-neon-magenta/30' },
    admin: { label: 'Admin', Icon: Wrench, bgClass: 'bg-neon-cyan/10 text-neon-cyan border border-neon-cyan/30' },
    estudiante: { label: 'Estudiante', Icon: GraduationCap, bgClass: 'bg-neon-green/10 text-neon-green border border-neon-green/30' },
};

export default function AdminUsuariosIndex({ usuarios, filtros }) {
    const { confirm } = useDialog();
    const [busqueda, setBusqueda] = useState(filtros?.busqueda || '');
    const [filtroEstado, setFiltroEstado] = useState(filtros?.estado || '');
    const [filtroRol, setFiltroRol] = useState(filtros?.rol || '');
    const [showImportModal, setShowImportModal] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importing, setImporting] = useState(false);
    const fileInputRef = useRef(null);

    const aplicarFiltros = () => {
        router.get('/admin/usuarios', { busqueda, estado: filtroEstado, rol: filtroRol }, { preserveState: true });
    };

    const aprobar = async (id, nombre) => {
        const ok = await confirm(`¿Aprobar a "${nombre}"?`, { title: 'Aprobar usuario', confirmText: 'Aprobar', variant: 'success' });
        if (!ok) return;
        router.post(`/admin/usuarios/${id}/aprobar`);
    };

    const rechazar = async (id, nombre) => {
        const ok = await confirm(`¿Rechazar a "${nombre}"?`, { title: 'Rechazar usuario', confirmText: 'Rechazar', variant: 'warning' });
        if (!ok) return;
        router.post(`/admin/usuarios/${id}/rechazar`);
    };

    const eliminar = async (id, nombre) => {
        const ok = await confirm(`¿Eliminar a "${nombre}"?`, { title: 'Eliminar usuario', confirmText: 'Eliminar', variant: 'danger' });
        if (!ok) return;
        router.delete(`/admin/usuarios/${id}`);
    };

    const pendientes = usuarios?.data?.filter((u) => u.estado === 'pendiente').length || 0;

    return (
        <>
            <Head title="Admin - Usuarios" />
            <div className="min-h-screen bg-cyber-dark">
                <div className="relative overflow-hidden bg-gradient-to-br from-neon-cyan/15 via-cyber-dark-100 to-cyber-dark py-8 cyber-grid">
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(0,240,255,0.1),transparent_50%)]" />
                    <div className="relative mx-auto max-w-full px-5 sm:px-8 lg:px-10">
                        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }}
                            className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h1 className="text-2xl font-heading font-black text-text-primary">Gestión de Usuarios</h1>
                                <p className="mt-1 text-sm font-semibold text-text-muted">
                                    {usuarios?.total || 0} usuarios registrados
                                    {pendientes > 0 && (
                                        <span className="ml-2 inline-flex items-center gap-1 rounded-full bg-neon-yellow/10 border border-neon-yellow/30 px-2.5 py-0.5 text-xs font-bold text-neon-yellow">
                                            <Clock className="h-3 w-3" /> {pendientes} pendientes
                                        </span>
                                    )}
                                </p>
                            </div>
                            <div className="flex items-center gap-2">
                                <button onClick={() => setShowImportModal(true)}
                                    className="cyber-btn rounded-xl px-4 py-2.5 text-sm font-bold border-cyber-dark-400">
                                    <Download className="h-4 w-4" /> Importar
                                </button>
                                <a href={`/admin/usuarios/exportar?${new URLSearchParams({ ...(filtroEstado && { estado: filtroEstado }), ...(filtroRol && { rol: filtroRol }), ...(busqueda && { busqueda }) }).toString()}`}
                                    className="cyber-btn rounded-xl px-4 py-2.5 text-sm font-bold border-cyber-dark-400">
                                    <Upload className="h-4 w-4" /> Exportar
                                </a>
                                <Link href="/admin/usuarios/crear"
                                    className="cyber-btn cyber-btn-primary rounded-xl px-5 py-2.5 text-sm font-bold">
                                    + Nuevo usuario
                                </Link>
                            </div>
                        </motion.div>
                    </div>
                </div>

                <div className="mx-auto max-w-full px-5 sm:px-8 lg:px-10 mt-4 pb-8">
                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.1 }}
                        className="cyber-card rounded-xl p-4 mb-6">
                        <div className="flex flex-col gap-3 sm:flex-row">
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-text-muted" />
                                <input type="text" placeholder="Buscar por nombre o email..."
                                    value={busqueda} onChange={(e) => setBusqueda(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && aplicarFiltros()}
                                    className="w-full cyber-input rounded-xl pl-10 pr-4 py-2.5 text-sm" />
                            </div>
                            <select value={filtroEstado} onChange={(e) => setFiltroEstado(e.target.value)}
                                className="cyber-input rounded-xl px-4 py-2.5 text-sm">
                                <option value="">Todos los estados</option>
                                <option value="pendiente">Pendientes</option>
                                <option value="activo">Activos</option>
                                <option value="rechazado">Rechazados</option>
                            </select>
                            <select value={filtroRol} onChange={(e) => setFiltroRol(e.target.value)}
                                className="cyber-input rounded-xl px-4 py-2.5 text-sm">
                                <option value="">Todos los roles</option>
                                <option value="super_admin">Super Admin</option>
                                <option value="admin">Admin</option>
                                <option value="estudiante">Estudiante</option>
                                <option value="invitado">Invitado</option>
                            </select>
                            <button onClick={aplicarFiltros}
                                className="cyber-btn cyber-btn-primary rounded-xl px-5 py-2.5 text-sm font-bold">
                                Filtrar
                            </button>
                        </div>
                    </motion.div>

                    <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.2 }}>
                        {(!usuarios?.data || usuarios.data.length === 0) ? (
                            <div className="cyber-card rounded-xl p-12 text-center">
                                <Users className="mx-auto mb-4 h-14 w-14 text-text-muted" />
                                <p className="text-lg font-heading font-bold text-text-primary">No hay usuarios</p>
                                <p className="text-sm font-semibold text-text-muted">No se encontraron usuarios con los filtros actuales.</p>
                            </div>
                        ) : (
                            <div className="cyber-card rounded-xl overflow-hidden">
                                <div className="overflow-x-auto">
                                    <table className="w-full">
                                        <thead>
                                            <tr className="border-b border-cyber-dark-400/30 bg-cyber-dark-200">
                                                <th className="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-muted">Usuario</th>
                                                <th className="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-muted">Email</th>
                                                <th className="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-muted">Rol</th>
                                                <th className="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-muted">Estado</th>
                                                <th className="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-muted">Aprobado por</th>
                                                <th className="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-text-muted">Registro</th>
                                                <th className="px-5 py-3 text-right text-xs font-bold uppercase tracking-wider text-text-muted">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-cyber-dark-400/20">
                                            {usuarios.data.map((u, idx) => {
                                                const estilo = estadoEstilos[u.estado] || estadoEstilos.pendiente;
                                                const rol = rolConfig[u.rol] || rolConfig.estudiante;
                                                const StatusIcon = estilo.Icon;
                                                const RolIcon = rol.Icon;
                                                return (
                                                    <tr key={u.id} className="transition-all hover:bg-cyber-dark-300/30">
                                                        <td className="px-5 py-4">
                                                            <div className="flex items-center gap-3">
                                                                <div className={`flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl text-sm font-bold ${idx % 2 === 0 ? 'bg-neon-cyan/15 text-neon-cyan ring-1 ring-neon-cyan/40 shadow-neon-cyan' : 'bg-neon-magenta/15 text-neon-magenta ring-1 ring-neon-magenta/40 shadow-neon-magenta'}`}>
                                                                    {u.name?.charAt(0)?.toUpperCase() || '?'}
                                                                </div>
                                                                <span className="text-sm font-bold text-text-primary">{u.name}</span>
                                                            </div>
                                                        </td>
                                                        <td className="px-5 py-4 text-sm font-semibold text-text-muted">{u.email}</td>
                                                        <td className="px-5 py-4">
                                                            <span className={`inline-flex items-center gap-1 rounded-lg px-2.5 py-0.5 text-xs font-bold ${rol.bgClass}`}>
                                                                <RolIcon className="h-3 w-3" /> {rol.label}
                                                            </span>
                                                        </td>
                                                        <td className="px-5 py-4">
                                                            <span className={`inline-flex items-center gap-1 rounded-lg px-2.5 py-0.5 text-xs font-bold ${estilo.bg}`}>
                                                                <StatusIcon className="h-3 w-3" /> {u.estado}
                                                            </span>
                                                        </td>
                                                        <td className="px-5 py-4">
                                                            {u.aprobado_por ? (
                                                                <span className="text-xs font-bold text-text-secondary">{u.aprobado_por?.name || 'Admin'}</span>
                                                            ) : (
                                                                <span className="text-xs text-text-muted">—</span>
                                                            )}
                                                        </td>
                                                        <td className="px-5 py-4 text-sm font-semibold text-text-muted">
                                                            {new Date(u.created_at).toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' })}
                                                        </td>
                                                        <td className="px-5 py-4 text-right">
                                                            <div className="flex items-center justify-end gap-1.5">
                                                                {u.estado === 'pendiente' && (
                                                                    <>
                                                                        <button onClick={() => aprobar(u.id, u.name)}
                                                                            className="inline-flex items-center gap-1 rounded-lg bg-neon-green/10 border border-neon-green/30 px-2.5 py-1.5 text-xs font-bold text-neon-green hover:bg-neon-green/20 transition-all">
                                                                            <CheckCircle2 className="h-3.5 w-3.5" /> Aprobar
                                                                        </button>
                                                                        <button onClick={() => rechazar(u.id, u.name)}
                                                                            className="inline-flex items-center gap-1 rounded-lg bg-neon-magenta/10 border border-neon-magenta/30 px-2.5 py-1.5 text-xs font-bold text-neon-magenta hover:bg-neon-magenta/20 transition-all">
                                                                            <XCircle className="h-3.5 w-3.5" /> Rechazar
                                                                        </button>
                                                                    </>
                                                                )}
                                                                <Link href={`/admin/usuarios/${u.id}/editar`}
                                                                    className="inline-flex items-center gap-1 rounded-lg bg-neon-cyan/10 border border-neon-cyan/30 px-2.5 py-1.5 text-xs font-bold text-neon-cyan hover:bg-neon-cyan/20 transition-all">
                                                                    <Pencil className="h-3.5 w-3.5" /> Editar
                                                                </Link>
                                                                <button onClick={() => eliminar(u.id, u.name)}
                                                                    className="inline-flex items-center rounded-lg bg-neon-magenta/10 border border-neon-magenta/30 px-2.5 py-1.5 text-xs font-bold text-neon-magenta hover:bg-neon-magenta/20 transition-all">
                                                                    <Trash2 className="h-3.5 w-3.5" />
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                                {usuarios?.last_page > 1 && (
                                    <div className="flex items-center justify-between border-t border-cyber-dark-400/30 px-5 py-4 bg-cyber-dark-200">
                                        <p className="text-xs font-semibold text-text-muted">Página {usuarios.current_page} de {usuarios.last_page}</p>
                                        <div className="flex gap-2">
                                            {usuarios.prev_page_url && (
                                                <button onClick={() => router.get(usuarios.prev_page_url)}
                                                    className="cyber-btn rounded-lg px-3 py-1.5 text-xs font-bold border-cyber-dark-400">← Anterior</button>
                                            )}
                                            {usuarios.next_page_url && (
                                                <button onClick={() => router.get(usuarios.next_page_url)}
                                                    className="cyber-btn rounded-lg px-3 py-1.5 text-xs font-bold border-cyber-dark-400">Siguiente →</button>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </motion.div>
                </div>
            </div>

            {/* Import Modal */}
            {showImportModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div className="fixed inset-0 cursor-pointer bg-cyber-dark/80 backdrop-blur-sm"
                        onClick={() => { setShowImportModal(false); setImportFile(null); }} />
                    <motion.div initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }}
                        className="relative z-10 w-full max-w-md cyber-card rounded-xl p-6">
                        <h3 className="inline-flex items-center gap-2 text-lg font-heading font-bold text-text-primary">
                            <Download className="h-5 w-5 text-neon-cyan" /> Importar usuarios
                        </h3>
                        <p className="mt-1 text-sm font-semibold text-text-muted">Sube un archivo Excel (.xlsx, .xls) o CSV.</p>
                        <div className="mt-3 rounded-xl bg-neon-cyan/5 border border-neon-cyan/20 p-3 text-xs text-neon-cyan font-semibold">
                            <p className="font-bold mb-1">Columnas requeridas:</p>
                            <code className="text-xs">nombre, email, rol, password?, whatsapp?</code>
                        </div>
                        <div className="mt-4">
                            <label className="block text-sm font-bold text-text-secondary mb-1">Seleccionar archivo</label>
                            <input ref={fileInputRef} type="file" accept=".xlsx,.xls,.csv"
                                onChange={(e) => setImportFile(e.target.files[0])}
                                className="block w-full text-sm text-text-muted file:mr-3 file:rounded-lg file:border-0 file:bg-neon-cyan/10 file:px-3 file:py-2 file:text-xs file:font-bold file:text-neon-cyan hover:file:bg-neon-cyan/20" />
                        </div>
                        <div className="mt-6 flex items-center justify-between gap-3">
                            <button onClick={() => { setShowImportModal(false); setImportFile(null); }}
                                className="cyber-btn rounded-xl px-5 py-2.5 text-sm font-bold border-cyber-dark-400">Cancelar</button>
                            <button onClick={() => {
                                if (!importFile) return;
                                setImporting(true);
                                const formData = new FormData();
                                formData.append('archivo', importFile);
                                router.post('/admin/usuarios/importar', formData, {
                                    onFinish: () => { setImporting(false); setShowImportModal(false); setImportFile(null); },
                                });
                            }} disabled={!importFile || importing}
                                className="cyber-btn cyber-btn-primary rounded-xl px-5 py-2.5 text-sm font-bold disabled:opacity-60">
                                {importing ? 'Importando...' : 'Importar'}
                            </button>
                        </div>
                    </motion.div>
                </div>
            )}
        </>
    );
}
