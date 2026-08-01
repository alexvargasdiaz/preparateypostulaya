<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('estado', 20)->default('pendiente')->after('rol')
                ->comment('pendiente, activo, rechazado');
            $table->timestamp('fecha_aprobacion')->nullable()->after('estado');
            $table->foreignId('aprobado_por')->nullable()->after('fecha_aprobacion')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('aprobado_por');
            $table->dropColumn(['estado', 'fecha_aprobacion']);
        });
    }
};
