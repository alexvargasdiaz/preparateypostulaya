<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intentos_examen', function (Blueprint $table) {
            $table->foreignId('tipo_simulacro_id')->nullable()->constrained('tipos_simulacro')->nullOnDelete()->after('area_academica_id');
        });
    }

    public function down(): void
    {
        Schema::table('intentos_examen', function (Blueprint $table) {
            $table->dropForeign(['tipo_simulacro_id']);
            $table->dropColumn('tipo_simulacro_id');
        });
    }
};
