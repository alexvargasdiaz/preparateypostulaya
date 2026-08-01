<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisitos_carrera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->foreignId('concepto_id')->constrained('conceptos')->cascadeOnDelete();
            $table->decimal('puntaje_minimo', 5, 2)->default(60.00)->comment('Porcentaje mínimo requerido');
            $table->timestamps();

            $table->unique(['categoria_id', 'concepto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisitos_carrera');
    }
};
