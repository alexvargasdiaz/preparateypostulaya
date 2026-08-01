import { motion, AnimatePresence } from 'motion/react';
import { X } from 'lucide-react';

export default function VideoModal({ videoSrc, isOpen, onClose }) {
    return (
        <AnimatePresence>
            {isOpen && (
                <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="fixed inset-0 z-[999] flex items-center justify-center bg-black/80 p-4"
                    onClick={onClose}
                >
                    <motion.div
                        initial={{ scale: 0.9, opacity: 0 }}
                        animate={{ scale: 1, opacity: 1 }}
                        exit={{ scale: 0.9, opacity: 0 }}
                        transition={{ type: 'spring', duration: 0.4 }}
                        className="relative w-full max-w-4xl"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <button
                            onClick={onClose}
                            className="absolute -top-10 right-0 z-10 flex items-center gap-1 font-bold text-xs uppercase tracking-wider text-white/70 transition-colors hover:text-white"
                        >
                            <X className="h-5 w-5" /> Cerrar
                        </button>
                        <div className="relative aspect-video w-full bg-black rounded-xl overflow-hidden">
                            {videoSrc && (
                                <iframe
                                    key={videoSrc}
                                    className="absolute inset-0 h-full w-full"
                                    src={videoSrc + '?autoplay=1&rel=0'}
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowFullScreen
                                />
                            )}
                        </div>
                    </motion.div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}
