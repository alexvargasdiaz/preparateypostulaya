<?php

declare(strict_types=1);

namespace Modules\Notificaciones\Console\Commands;

use Illuminate\Console\Command;
use Twilio\Rest\Client as TwilioClient;

class TestWhatsApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:test
                            {numero : Número de teléfono con prefijo (ej: +51999999999)}
                            {--mensaje= : Mensaje a enviar (por defecto mensaje de prueba)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía un mensaje de prueba por WhatsApp y consulta el estado real que Twilio reporta';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $numero = $this->argument('numero');
        $mensaje = $this->option('mensaje') ?: '🧪 Mensaje de prueba desde Prepárate y Postula Ya.';

        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.whatsapp_from', '');

        if (!$sid || !$token || !$from) {
            $this->error('Twilio no está configurado. Revisa TWILIO_SID, TWILIO_TOKEN y TWILIO_WHATSAPP_FROM en el .env.');
            return Command::FAILURE;
        }

        $this->line('Twilio configurado:');
        $this->line("  SID: {$sid}");
        $this->line("  From: {$from}");
        $this->line("Enviando a: {$numero}");

        $client = new TwilioClient($sid, $token);

        try {
            $created = $client->messages->create(
                "whatsapp:{$numero}",
                ['from' => $from, 'body' => $mensaje]
            );
        } catch (\Exception $e) {
            $this->error('❌ Error síncrono de Twilio: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->line("  Mensaje creado: {$created->sid} (status inicial: {$created->status})");
        $this->line('  Consultando estado real en Twilio...');

        $estadoFinal = null;
        for ($i = 0; $i < 10; $i++) {
            sleep(2);
            $message = $client->messages($created->sid)->fetch();
            $estadoFinal = $message->status;
            $errorCode = $message->errorCode;
            $errorMessage = $message->errorMessage;

            $this->line("    [{$i}] status: {$estadoFinal}" . ($errorCode ? " | errorCode: {$errorCode}" : ''));

            if (in_array($estadoFinal, ['sent', 'delivered', 'read', 'failed'], true)) {
                break;
            }
        }

        if ($estadoFinal === 'failed') {
            $this->error("❌ WhatsApp NO fue entregado. errorCode: {$errorCode} ({$errorMessage})");
            $this->line('');
            $this->line('Causa más común (error 63015): el número no está activo en el Sandbox de Twilio.');
            $this->line('El Sandbox expira cada 3 días. Desde el WhatsApp destino envía:');
            $this->line('    join <CODIGO>  al número  +1 415 523 8886');
            $this->line('El código está en: Twilio Console → Try WhatsApp → Sandbox for WhatsApp.');
            $this->line('Para producción, crea un número WhatsApp Business en Twilio.');

            return Command::FAILURE;
        }

        if (in_array($estadoFinal, ['sent', 'delivered', 'read'], true)) {
            $this->info("✅ WhatsApp entregado (status: {$estadoFinal}).");
            return Command::SUCCESS;
        }

        $this->warn("⚠️ Estado indeterminado: {$estadoFinal}. Vuelve a ejecutar el comando para confirmar.");
        return Command::FAILURE;
    }
}
