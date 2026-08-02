import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

export function normalizarWhatsApp(numero) {
    if (!numero) return null;
    const limpio = numero.replace(/[\s\-()\.]+/g, '').trim();
    if (!limpio) return null;

    if (limpio.startsWith('+51')) {
        const local = limpio.slice(3);
        return /^9\d{8}$/.test(local) ? `+51${local}` : null;
    }

    if (limpio.startsWith('51') && limpio.length === 11) {
        const local = limpio.slice(2);
        return /^9\d{8}$/.test(local) ? `+51${local}` : null;
    }

    if (/^9\d{8}$/.test(limpio)) {
        return `+51${limpio}`;
    }

    return null;
}
