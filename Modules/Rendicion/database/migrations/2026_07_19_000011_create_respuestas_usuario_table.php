<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respuestas_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intento_id')->constrained('intentos_examen')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('preguntas')->cascadeOnDelete();
            $table->unsignedBigInteger('alternativa_id_elegida')->nullable()->comment('ID de la alternativa elegida (sin FK para mantener modularidad)');
            $table->boolean('es_correcta')->nullable();
            $table->decimal('tiempo_respuesta_seg', 8, 2)->nullable();
            $table->timestamps();

            // Una única respuesta por pregunta en cada intento
            $table->unique(['intento_id', 'pregunta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas_usuario');
    }
};
