<?php

namespace App\Providers;

use App\Console\Commands\ProtectedDbWipe;
use App\Console\Commands\ProtectedMigrateFresh;
use App\Console\Commands\ProtectedMigrateRefresh;
use App\Console\Commands\ProtectedMigrateReset;
use App\Console\Commands\ProtectedMigrateRollback;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Console\WipeCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerProtectedDestructiveCommands();
    }

    /**
     * Reemplaza los comandos destructivos por versiones que exigen confirmación.
     */
    private function registerProtectedDestructiveCommands(): void
    {
        $this->app->extend(FreshCommand::class, fn ($command, $app) => $app->make(ProtectedMigrateFresh::class));
        $this->app->extend(WipeCommand::class, fn ($command, $app) => $app->make(ProtectedDbWipe::class));
        $this->app->extend(RefreshCommand::class, fn ($command, $app) => $app->make(ProtectedMigrateRefresh::class));
        $this->app->extend(ResetCommand::class, fn ($command, $app) => $app->make(ProtectedMigrateReset::class));
        $this->app->extend(RollbackCommand::class, fn ($command, $app) => $app->make(ProtectedMigrateRollback::class));
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email') ?: $request->ip());
        });

        RateLimiter::for('exam', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });
    }
}
