<?php

declare(strict_types=1);

namespace Modules\Notificaciones\Services;

use Twilio\Rest\Client as TwilioClient;
use Twilio\Rest\Api\V2010\Account\MessageList;

class WhatsAppService
{
    private ?TwilioClient $client;
    private string $fromNumber;
    private bool $enabled;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $this->fromNumber = config('services.twilio.whatsapp_from', '');
        $this->enabled = $sid && $token && $this->fromNumber;

        if ($this->enabled) {
            $this->client = new TwilioClient($sid, $token);
        } else {
            $this->client = null;
        }
    }

    /**
     * Envía un mensaje de WhatsApp al número del usuario.
     */
    public function enviar(string $numeroTelefono, string $mensaje): bool
    {
        if (!$this->enabled || !$this->client) {
            \Log::info('WhatsApp no configurado. Mensaje no enviado.', [
                'numero' => $numeroTelefono,
                'mensaje' => $mensaje,
            ]);
            return false;
        }

        $numeroLimpio = $this->limpiarNumero($numeroTelefono);

        if (!$numeroLimpio) {
            \Log::warning('Número de WhatsApp inválido', ['numero' => $numeroTelefono]);
            return false;
        }

        try {
            $mensajeCreado = $this->client->messages->create(
                "whatsapp:{$numeroLimpio}",
                [
                    'from' => $this->fromNumber,
                    'body' => $mensaje,
                ]
            );

            $estado = $mensajeCreado->status ?? 'created';
            $errorCode = $mensajeCreado->errorCode ?? null;
            $errorMessage = $mensajeCreado->errorMessage ?? null;

            if ($estado === 'failed' || $errorCode !== null) {
                \Log::error('WhatsApp rechazado por Twilio', [
                    'numero' => $numeroLimpio,
                    'status' => $estado,
                    'errorCode' => $errorCode,
                    'errorMessage' => $errorMessage,
                    'sid' => $mensajeCreado->sid ?? null,
                    'mensaje' => $mensaje,
                ]);

                return false;
            }

            \Log::info('WhatsApp enviado exitosamente', [
                'numero' => $numeroLimpio,
                'status' => $estado,
                'mensaje' => $mensaje,
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Error enviando WhatsApp', [
                'numero' => $numeroLimpio,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Notifica a un alumno que su cuenta fue aprobada.
     */
    public function notificarAprobacion(string $nombreAlumno, string $numeroTelefono): bool
    {
        $mensaje = "Hola {$nombreAlumno}! 🎉\n\n"
            . "Tu cuenta en Prepárate y Postula Ya ha sido APROBADA.\n\n"
            . "Ya puedes acceder a la plataforma y participar de todos los simulacros de admisión.\n\n"
            . "¡Empieza a practicar ahora!\n"
            . "🔗 " . config('app.url', 'http://localhost');

        return $this->enviar($numeroTelefono, $mensaje);
    }

    /**
     * Limpia y formatea un número de teléfono para Twilio WhatsApp.
     * Formato esperado: +51XXXXXXXXX (Perú)
     */
    private function limpiarNumero(string $numero): ?string
    {
        $numero = preg_replace('/[\s\-\(\)]+/', '', trim($numero));

        if (str_starts_with($numero, '+')) {
            return $numero;
        }

        if (str_starts_with($numero, '51')) {
            return "+{$numero}";
        }

        if (strlen($numero) === 9) {
            return "+51{$numero}";
        }

        return null;
    }
}
