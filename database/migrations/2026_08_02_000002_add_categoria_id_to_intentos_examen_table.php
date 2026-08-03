<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registra la carrera (categoría) a la que postula el alumno
     * cuando inicia un simulacro por universidad + área académica.
     */
    public function up(): void
    {
        Schema::table('intentos_examen', function (Blueprint $table) {
            $table->foreignId('categoria_id')->nullable()
                ->after('tipo_simulacro_id')
                ->constrained('categorias')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('intentos_examen', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropColumn('categoria_id');
        });
    }
};
