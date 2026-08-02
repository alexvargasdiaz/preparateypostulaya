<?php

declare(strict_types=1);

namespace App\Support;

class Telefono
{
    /**
     * Normaliza un número de WhatsApp peruano a formato E.164 (+51XXXXXXXXX).
     * Acepta 9 dígitos locales (asume Perú), con o sin prefijo 51/+51.
     * Retorna null si el número no es un móvil peruano válido.
     */
    public static function normalizarWhatsApp(?string $numero): ?string
    {
        if ($numero === null || trim($numero) === '') {
            return null;
        }

        $numero = preg_replace('/[\s\-\(\)\.]+/', '', trim($numero));

        // Ya viene con +51
        if (str_starts_with($numero, '+51')) {
            $local = substr($numero, 3);
            return preg_match('/^9\d{8}$/', $local) ? '+51' . $local : null;
        }

        // Viene con 51 sin +
        if (str_starts_with($numero, '51') && strlen($numero) === 11) {
            $local = substr($numero, 2);
            return preg_match('/^9\d{8}$/', $local) ? '+51' . $local : null;
        }

        // 9 dígitos locales: asumimos Perú
        if (preg_match('/^9\d{8}$/', $numero)) {
            return '+51' . $numero;
        }

        return null;
    }
}
