import { createContext, useContext, useState, useCallback, useRef } from 'react';
import { createPortal } from 'react-dom';
import { AlertTriangle, AlertCircle, CheckCircle2, X, HelpCircle } from 'lucide-react';

const DialogContext = createContext(null);

export function useDialog() {
    const ctx = useContext(DialogContext);
    if (!ctx) throw new Error('useDialog must be used within <DialogProvider>');
    return ctx;
}

export default function DialogProvider({ children }) {
    const [confirmState, setConfirmState] = useState(null);
    const [alertState, setAlertState] = useState(null);
    const resolveRef = useRef(null);

    const confirm = useCallback((message, { title, confirmText, cancelText, variant } = {}) => {
        return new Promise((resolve) => {
            resolveRef.current = resolve;
            setConfirmState({
                title: title || 'Confirmar acción',
                message,
                confirmText: confirmText || 'Confirmar',
                cancelText: cancelText || 'Cancelar',
                variant: variant || 'danger',
            });
        });
    }, []);

    const handleConfirm = useCallback(() => {
        const resolve = resolveRef.current;
        setConfirmState(null);
        resolveRef.current = null;
        resolve?.(true);
    }, []);

    const handleCancel = useCallback(() => {
        const resolve = resolveRef.current;
        setConfirmState(null);
        resolveRef.current = null;
        resolve?.(false);
    }, []);

    const showAlert = useCallback((message, { title } = {}) => {
        return new Promise((resolve) => {
            resolveRef.current = resolve;
            setAlertState({
                title: title || 'Aviso',
                message,
            });
        });
    }, []);

    const handleAlertOk = useCallback(() => {
        const resolve = resolveRef.current;
        setAlertState(null);
        resolveRef.current = null;
        resolve?.(true);
    }, []);

    const variantConfig = {
        danger: {
            icon: AlertTriangle,
            iconColor: 'text-neon-magenta',
            iconBg: 'bg-neon-magenta/15',
            borderColor: 'border-neon-magenta/30',
            btnBg: 'bg-neon-magenta/15 hover:bg-neon-magenta/25',
            btnBorder: 'border-neon-magenta/40',
            btnText: 'text-neon-cyan',
            glow: 'shadow-neon-magenta',
        },
        warning: {
            icon: AlertTriangle,
            iconColor: 'text-neon-yellow',
            iconBg: 'bg-neon-yellow/15',
            borderColor: 'border-neon-yellow/30',
            btnBg: 'bg-neon-yellow/15 hover:bg-neon-yellow/25',
            btnBorder: 'border-neon-yellow/40',
            btnText: 'text-neon-yellow',
            glow: 'shadow-neon-yellow',
        },
        success: {
            icon: CheckCircle2,
            iconColor: 'text-neon-green',
            iconBg: 'bg-neon-green/15',
            borderColor: 'border-neon-green/30',
            btnBg: 'bg-neon-green/15 hover:bg-neon-green/25',
            btnBorder: 'border-neon-green/40',
            btnText: 'text-neon-green',
            glow: 'shadow-neon-green',
        },
        info: {
            icon: HelpCircle,
            iconColor: 'text-neon-cyan',
            iconBg: 'bg-neon-cyan/15',
            borderColor: 'border-neon-cyan/30',
            btnBg: 'bg-neon-cyan/15 hover:bg-neon-cyan/25',
            btnBorder: 'border-neon-cyan/40',
            btnText: 'text-neon-cyan',
            glow: 'shadow-neon-cyan',
        },
    };

    const renderConfirmModal = () => {
        if (!confirmState) return null;
        const v = variantConfig[confirmState.variant] || variantConfig.danger;
        const Icon = v.icon;

        return (
            <div className="fixed inset-0 z-[200] flex items-center justify-center p-4">
                <div
                    className="fixed inset-0 cursor-pointer bg-black/70 backdrop-blur-sm transition-opacity"
                    onClick={handleCancel}
                />
                <div
                    className={`relative w-full max-w-md rounded-3xl border ${v.borderColor} bg-[#0b0f17] shadow-2xl ${v.glow} transition-all`}
                    role="dialog"
                    aria-modal="true"
                >
                    {/* Icon */}
                    <div className="flex justify-center pt-8">
                        <div className={`flex h-20 w-20 items-center justify-center rounded-full ${v.iconBg} border ${v.borderColor}`}>
                            <Icon className={`h-10 w-10 ${v.iconColor}`} />
                        </div>
                    </div>

                    <div className="px-8 pb-6 pt-6 text-center">
                        <h3 className="text-xl font-bold text-white" style={{ fontFamily: 'var(--font-heading)' }}>
                            {confirmState.title}
                        </h3>
                        <p className="mt-3 text-sm leading-relaxed text-gray-400">
                            {confirmState.message}
                        </p>

                        {/* Decorative divider */}
                        <div className="mx-auto mt-5 h-px w-16" style={{ background: 'linear-gradient(90deg, transparent, rgba(0,240,255,0.3), transparent)' }} />

                        {/* Buttons */}
                        <div className="mt-6 flex flex-col gap-2 sm:flex-row-reverse">
                            <button
                                onClick={handleConfirm}
                                className={`flex-1 rounded-xl border ${v.btnBorder} ${v.btnBg} ${v.btnText} px-5 py-3 text-sm font-bold shadow-lg transition-all hover:-translate-y-0.5 active:scale-[0.98]`}
                                style={{ fontFamily: 'var(--font-heading)', textTransform: 'uppercase', letterSpacing: '0.05em' }}
                            >
                                {confirmState.confirmText}
                            </button>
                            <button
                                onClick={handleCancel}
                                className="flex-1 rounded-xl border border-gray-700/50 bg-white/5 px-5 py-3 text-sm font-bold text-gray-400 shadow-xs transition-all hover:bg-white/10 hover:text-gray-200 active:scale-[0.98]"
                            >
                                {confirmState.cancelText}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    const renderAlertModal = () => {
        if (!alertState) return null;

        return (
            <div className="fixed inset-0 z-[200] flex items-center justify-center p-4">
                <div
                    className="fixed inset-0 cursor-pointer bg-black/70 backdrop-blur-sm transition-opacity"
                    onClick={handleAlertOk}
                />
                <div
                    className="relative w-full max-w-sm rounded-3xl border border-neon-cyan/30 bg-[#0b0f17] shadow-2xl shadow-neon-cyan transition-all"
                    role="dialog"
                    aria-modal="true"
                >
                    <div className="flex justify-center pt-8">
                        <div className="flex h-20 w-20 items-center justify-center rounded-full bg-neon-cyan/15 border border-neon-cyan/30">
                            <AlertCircle className="h-10 w-10 text-neon-cyan" />
                        </div>
                    </div>

                    <div className="px-8 pb-6 pt-6 text-center">
                        <h3 className="text-xl font-bold text-white" style={{ fontFamily: 'var(--font-heading)' }}>
                            {alertState.title}
                        </h3>
                        <p className="mt-3 text-sm leading-relaxed text-gray-400">
                            {alertState.message}
                        </p>

                        <div className="mx-auto mt-5 h-px w-16" style={{ background: 'linear-gradient(90deg, transparent, rgba(0,240,255,0.3), transparent)' }} />

                        <button
                            onClick={handleAlertOk}
                            className="mt-6 w-full rounded-xl border border-neon-cyan/40 bg-neon-cyan/15 px-5 py-3 text-sm font-bold text-neon-cyan shadow-lg shadow-neon-cyan transition-all hover:-translate-y-0.5 hover:bg-neon-cyan/25 active:scale-[0.98]"
                            style={{ fontFamily: 'var(--font-heading)', textTransform: 'uppercase', letterSpacing: '0.05em' }}
                        >
                            Aceptar
                        </button>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <DialogContext.Provider value={{ confirm, showAlert }}>
            {children}
            {createPortal(renderConfirmModal(), document.body)}
            {createPortal(renderAlertModal(), document.body)}
        </DialogContext.Provider>
    );
}
