<?php

declare(strict_types=1);

namespace Modules\Notificaciones\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Notificaciones\Models\PreferenciaNotificacion;
use Modules\Notificaciones\Services\NotificationService;
use Modules\Rendicion\Models\IntentoExamen;

class SendStudyReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:recordatorio
                            {--dias=3 : Días de inactividad mínimos para enviar recordatorio}
                            {--dry-run : Solo mostrar usuarios sin enviar notificaciones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía recordatorios de estudio a usuarios que no han practicado recientemente';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $diasMinimos = (int) $this->option('dias');
        $dryRun = (bool) $this->option('dry-run');

        if ($diasMinimos < 1) {
            $this->error('El número de días debe ser al menos 1.');
            return Command::INVALID;
        }

        $this->info("🔍 Buscando usuarios inactivos desde hace {$diasMinimos}+ días...");

        if (!PreferenciaNotificacion::where('recordatorio_estudio', true)->exists()) {
            $this->warn('No hay usuarios con recordatorios de estudio activados.');
            return Command::SUCCESS;
        }

        $enviados = 0;
        $saltados = 0;

        // Procesar en lotes para no cargar todas las preferencias en memoria
        PreferenciaNotificacion::query()
            ->where('recordatorio_estudio', true)
            ->with('usuario')
            ->orderBy('id')
            ->chunkById(500, function ($preferencias) use (&$enviados, &$saltados, $diasMinimos, $dryRun, $notificationService) {
                $usuarios = $preferencias->pluck('usuario')->filter();

                if ($usuarios->isEmpty()) {
                    $saltados += $preferencias->count();
                    return;
                }

                // Único query por lote: último intento completado de cada usuario
                $ultimosIntentos = IntentoExamen::query()
                    ->select('usuario_id', 'examen_id', 'created_at')
                    ->whereIn('usuario_id', $usuarios->pluck('id')->all())
                    ->where('estado', 'completado')
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->groupBy('usuario_id')
                    ->map->first();

                foreach ($preferencias as $pref) {
                    $usuario = $pref->usuario;

                    if (!$usuario || !$usuario->guardaHistorial()) {
                        $saltados++;
                        continue;
                    }

                    $ultimoIntento = $ultimosIntentos->get($usuario->id);

                    if (!$ultimoIntento) {
                        // Usuario nunca ha completado un examen
                        $dias = 999;
                        $ultimoExamen = null;
                    } else {
                        $dias = (int) $ultimoIntento->created_at->diffInDays(now());
                        $ultimoExamen = $ultimoIntento->examen?->titulo;
                    }

                    if ($dias < $diasMinimos) {
                        $saltados++;
                        continue;
                    }

                    if ($dryRun) {
                        $this->line("   [DRY-RUN] {$usuario->name} <{$usuario->email}> — {$dias} días sin practicar" . ($ultimoExamen ? " (último: {$ultimoExamen})" : ' (nunca ha rendido)'));
                        $enviados++;
                        continue;
                    }

                    // Enviar recordatorio
                    try {
                        $notificationService->recordatorioEstudio(
                            usuario: $usuario,
                            diasSinPracticar: $dias,
                            ultimoExamen: $ultimoExamen,
                        );

                        $this->line("   ✅ {$usuario->name} — {$dias} días sin practicar" . ($ultimoExamen ? " (último: {$ultimoExamen})" : ''));
                        $enviados++;
                    } catch (\Exception $e) {
                        $this->error("   ❌ Error al notificar a {$usuario->email}: {$e->getMessage()}");
                        report($e);
                    }
                }
            });

        $this->newLine();

        if ($dryRun) {
            $this->info("📋 DRY-RUN: {$enviados} usuarios recibirían recordatorio. {$saltados} saltados.");
        } else {
            $this->info("✅ Recordatorios enviados: {$enviados} usuarios. {$saltados} saltados.");
        }

        return Command::SUCCESS;
    }
}
