<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seccion_id')->constrained('secciones')->cascadeOnDelete();
            $table->foreignId('concepto_id')->nullable()->constrained('conceptos')->nullOnDelete();
            $table->text('enunciado');
            $table->string('enunciado_imagen_url')->nullable()->comment('URL de imagen del enunciado');
            $table->string('tipo', 30)->default('opcion_multiple')->comment('opcion_multiple, verdadero_falso, emparejamiento');
            $table->string('dificultad', 20)->default('media')->comment('facil, media, dificil');
            $table->integer('orden')->default(0);
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preguntas');
    }
};
