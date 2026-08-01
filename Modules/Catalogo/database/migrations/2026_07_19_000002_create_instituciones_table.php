<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instituciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_examen_id')->constrained('tipos_examen')->cascadeOnDelete();
            $table->string('nombre', 200);
            $table->string('subtipo', 60)->nullable()->comment('Ej: pública, privada, licenciada');
            $table->string('ciudad', 100)->nullable();
            $table->string('logo_url')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instituciones');
    }
};
