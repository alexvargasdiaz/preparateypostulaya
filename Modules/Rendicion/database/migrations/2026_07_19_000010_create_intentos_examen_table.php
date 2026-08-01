<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intentos_examen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('NULL si el usuario es invitado (sin cuenta)');
            $table->foreignId('examen_id')->constrained('examenes')->cascadeOnDelete();
            $table->string('estado', 30)->default('pendiente')
                ->comment('pendiente, en_curso, completado, abandonado');
            $table->string('token_acceso', 100)->nullable()->unique()
                ->comment('Token único para acceso al examen sin login');
            $table->boolean('email_enviado')->default(false);
            $table->boolean('whatsapp_solicitado')->default(false);
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_fin')->nullable();
            $table->decimal('puntaje_total', 5, 2)->nullable();
            $table->decimal('puntaje_maximo', 5, 2)->nullable();
            $table->boolean('aprobado')->nullable();
            $table->decimal('tiempo_empleado_seg', 10, 2)->nullable();
            $table->json('progreso_guardado')->nullable()->comment('Snapshot del progreso parcial para auto-save');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intentos_examen');
    }
};
