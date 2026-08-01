<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            $table->string('imagen_url')->nullable();
            $table->integer('tiempo_limite_min')->default(60);
            $table->integer('intentos_permitidos')->default(3);
            $table->decimal('puntaje_minimo', 5, 2)->nullable()->comment('Puntaje mínimo para aprobar. Null = no definido');
            $table->integer('num_alternativas_default')->default(5)->comment('Número de alternativas por pregunta');
            $table->boolean('aleatorizar_preguntas')->default(true);
            $table->boolean('aleatorizar_alternativas')->default(true);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examenes');
    }
};
