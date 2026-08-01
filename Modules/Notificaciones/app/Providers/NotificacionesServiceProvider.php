<?php

namespace Modules\Notificaciones\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Notificaciones\Console\Commands\SendStudyReminder;
use Nwidart\Modules\Support\ModuleServiceProvider;

class NotificacionesServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Notificaciones';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'notificaciones';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Registrar comandos manualmente para garantizar su disponibilidad
        if ($this->app->runningInConsole()) {
            $this->commands([SendStudyReminder::class]);
        }
    }

    /**
     * Define module schedules.
     */
    protected function configureSchedules(Schedule $schedule): void
    {
        // Recordatorios de estudio: todos los días a las 10:00 AM
        $schedule->command('notify:recordatorio', ['--dias' => 3])
            ->dailyAt('10:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/recordatorios.log'));
    }
}
