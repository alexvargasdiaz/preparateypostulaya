<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostico_concepto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concepto_id')->constrained('conceptos')->onDelete('cascade');
            $table->integer('preguntas_por_concepto')->default(10);
            $table->timestamps();
            $table->unique('concepto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostico_concepto');
    }
};
