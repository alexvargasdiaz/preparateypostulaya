<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preguntas', function (Blueprint $table) {
            $table->dropColumn('nivel');
        });

        Schema::table('conceptos', function (Blueprint $table) {
            $table->dropColumn('nivel');
        });
    }

    public function down(): void
    {
        Schema::table('preguntas', function (Blueprint $table) {
            $table->tinyInteger('nivel')->nullable()->after('area_academica_id');
        });

        Schema::table('conceptos', function (Blueprint $table) {
            $table->tinyInteger('nivel')->nullable()->after('area_academica_id');
        });
    }
};
