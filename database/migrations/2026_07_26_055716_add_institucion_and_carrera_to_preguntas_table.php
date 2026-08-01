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
        Schema::table('preguntas', function (Blueprint $table) {
            $table->foreignId('institucion_id')->nullable()->after('id')->constrained('instituciones')->nullOnDelete();
            $table->string('carrera', 200)->nullable()->after('institucion_id');

            // Hacer seccion_id opcional (para compatibilidad)
            $table->foreignId('seccion_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('preguntas', function (Blueprint $table) {
            $table->dropForeign(['institucion_id']);
            $table->dropColumn(['institucion_id', 'carrera']);
        });
    }
};
