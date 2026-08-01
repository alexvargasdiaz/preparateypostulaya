<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Notificaciones in-app ─────────────────────────────────
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 50)->default('info'); // info, exito, advertencia, error
            $table->string('titulo', 255);
            $table->text('mensaje')->nullable();
            $table->json('data')->nullable();         // datos adicionales (url, id_relacionado, etc.)
            $table->string('icono', 10)->nullable();   // emoji opcional
            $table->boolean('leida')->default(false);
            $table->timestamp('leida_at')->nullable();
            $table->timestamps();

            $table->index(['usuario_id', 'leida']);
            $table->index(['usuario_id', 'created_at']);
        });

        // ─── Preferencias de notificación por usuario ────────────
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete()->unique();
            $table->boolean('email_resultados')->default(true);       // recibir resultados por email
            $table->boolean('whatsapp_resultados')->default(false);   // recibir resumen por WhatsApp
            $table->boolean('recordatorio_estudio')->default(true);   // recordatorios periódicos
            $table->boolean('novedades')->default(true);             // novedades de la plataforma
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
    }
};
