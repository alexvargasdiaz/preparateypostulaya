<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('diagnostico_concepto', function (Blueprint $table) {
            $table->integer('duracion_minutos')->nullable()->after('preguntas_por_concepto')
                ->comment('Duración en minutos para el diagnóstico (null = auto: 1 min por pregunta)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagnostico_concepto', function (Blueprint $table) {
            $table->dropColumn('duracion_minutos');
        });
    }
};
