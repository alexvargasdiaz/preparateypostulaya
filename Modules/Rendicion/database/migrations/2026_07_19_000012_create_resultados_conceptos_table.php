<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados_conceptos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intento_id')->constrained('intentos_examen')->cascadeOnDelete();
            $table->foreignId('concepto_id')->constrained('conceptos')->cascadeOnDelete();
            $table->integer('preguntas_totales')->default(0);
            $table->integer('preguntas_correctas')->default(0);
            $table->decimal('porcentaje_acierto', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['intento_id', 'concepto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_conceptos');
    }
};
