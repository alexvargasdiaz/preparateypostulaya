<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conceptos', function (Blueprint $table) {
            $table->foreignId('area_academica_id')->nullable()->after('seccion_id')->constrained('areas_academicas')->nullOnDelete();
            $table->tinyInteger('nivel')->nullable()->after('area_academica_id');
        });
    }

    public function down(): void
    {
        Schema::table('conceptos', function (Blueprint $table) {
            $table->dropForeign(['area_academica_id']);
            $table->dropColumn(['area_academica_id', 'nivel']);
        });
    }
};
