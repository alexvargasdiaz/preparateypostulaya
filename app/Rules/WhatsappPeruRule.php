<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\Telefono;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WhatsappPeruRule implements ValidationRule
{
    /**
     * Valida que el número sea un móvil peruano válido y lo normaliza a +51XXXXXXXXX.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || Telefono::normalizarWhatsApp($value) === null) {
            $fail('Ingresa un número de WhatsApp válido de 9 dígitos (ej: 999 888 777).');
        }
    }
}
