<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examen_concepto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examen_id')->constrained('examenes')->cascadeOnDelete();
            $table->foreignId('concepto_id')->constrained('conceptos')->cascadeOnDelete();
            $table->integer('num_preguntas')->default(5);
            $table->timestamps();

            $table->unique(['examen_id', 'concepto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examen_concepto');
    }
};
