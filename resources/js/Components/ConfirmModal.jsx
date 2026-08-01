import { useEffect, useRef } from 'react';
import { AlertTriangle, CheckCircle2, AlertCircle, X } from 'lucide-react';

export default function ConfirmModal({ open, onConfirm, onCancel, title, message, icon, confirmText, cancelText, confirmVariant = 'danger', answeredCount = 0, totalCount = 0 }) {
    const confirmRef = useRef(null);

    useEffect(() => {
        if (open) {
            setTimeout(() => confirmRef.current?.focus(), 100);
            const handleEsc = (e) => {
                if (e.key === 'Escape') onCancel?.();
            };
            document.addEventListener('keydown', handleEsc);
            document.body.style.overflow = 'hidden';
            return () => {
                document.removeEventListener('keydown', handleEsc);
                document.body.style.overflow = '';
            };
        }
    }, [open, onCancel]);

    if (!open) return null;

    const confirmColors = {
        danger: 'bg-primary-600 hover:bg-primary-700 focus:ring-primary-300',
        primary: 'bg-primary-600 hover:bg-primary-700 focus:ring-primary-300',
        warning: 'bg-primary-600 hover:bg-primary-700 focus:ring-primary-300',
        success: 'bg-primary-500 hover:bg-primary-600 focus:ring-primary-200',
    };

    const IconComponent = icon === '📝' ? CheckCircle2 :
                          icon === '⚠️' ? AlertTriangle :
                          icon === '❌' ? AlertCircle :
                          icon === '✅' ? CheckCircle2 :
                          CheckCircle2;

    return (
        <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div className="absolute inset-0 cursor-pointer bg-black/60 backdrop-blur-sm transition-opacity" onClick={onCancel} />

            <div className="relative w-full max-w-md rounded-3xl bg-white shadow-2xl shadow-black/20 transition-all" role="dialog" aria-modal="true">
                {icon && (
                    <div className="flex justify-center pt-8">
                        <div className="flex h-20 w-20 items-center justify-center rounded-full bg-primary-50 shadow-inner">
                            <IconComponent className="h-10 w-10 text-primary-600" />
                        </div>
                    </div>
                )}

                <div className="px-8 pb-6 pt-6 text-center">
                    <h3 className="text-xl font-bold text-secondary-900">
                        {title || '¿Estás seguro?'}
                    </h3>
                    {message && (
                        <p className="mt-3 text-sm leading-relaxed text-secondary-600">
                            {message}
                        </p>
                    )}

                    <div className="mt-5 rounded-2xl border border-secondary-200 bg-secondary-50 p-4">
                        <div className="flex items-start gap-3">
                            <AlertTriangle className="mt-0.5 h-5 w-5 flex-shrink-0 text-secondary-500" />
                            <div className="text-left">
                                <p className="text-sm font-semibold text-secondary-800">
                                    Las respuestas no guardadas se perderán
                                </p>
                                <p className="mt-0.5 text-xs text-secondary-600">
                                    Asegúrate de haber respondido todas las preguntas antes de finalizar.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="mt-5 grid grid-cols-3 gap-3 rounded-2xl bg-secondary-50 p-3">
                        <div className="rounded-xl bg-white p-2.5 text-center shadow-xs">
                            <p className="text-lg font-bold text-primary-600">{answeredCount}</p>
                            <p className="text-[10px] font-medium text-secondary-500 uppercase">Respondidas</p>
                        </div>
                        <div className="rounded-xl bg-white p-2.5 text-center shadow-xs">
                            <p className="text-lg font-bold text-primary-500">{totalCount - answeredCount}</p>
                            <p className="text-[10px] font-medium text-secondary-500 uppercase">Pendientes</p>
                        </div>
                        <div className="rounded-xl bg-white p-2.5 text-center shadow-xs">
                            <p className="text-lg font-bold text-primary-600">{totalCount}</p>
                            <p className="text-[10px] font-medium text-secondary-500 uppercase">Total</p>
                        </div>
                    </div>

                    <div className="mt-6 flex flex-col gap-2 sm:flex-row-reverse">
                        <button
                            ref={confirmRef}
                            onClick={onConfirm}
                            className={`flex-1 rounded-xl px-5 py-3 text-sm font-bold text-white shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 active:scale-[0.98] ${confirmColors[confirmVariant] || confirmColors.danger}`}
                        >
                            {confirmText || 'Sí, finalizar examen'}
                        </button>
                        <button
                            onClick={onCancel}
                            className="flex-1 rounded-xl border border-secondary-200 bg-white px-5 py-3 text-sm font-bold text-secondary-700 shadow-xs transition-all hover:bg-secondary-50 focus:outline-none focus:ring-2 focus:ring-secondary-300 focus:ring-offset-2 active:scale-[0.98]"
                        >
                            {cancelText || 'Cancelar'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
