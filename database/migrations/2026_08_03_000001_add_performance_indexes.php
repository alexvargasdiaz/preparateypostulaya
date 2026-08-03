<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices de rendimiento para concurrencia.
 *
 * En PostgreSQL los foreignId()->constrained() no crean índices por defecto
 * (a diferencia de MySQL), así que las claves foráneas más calientes estaban
 * sin indexar y obligaban a seq scans a medida que crecen las tablas.
 */
return new class extends Migration
{
    private const INDICES_INTENTOS = [
        ['cols' => ['usuario_id', 'created_at'], 'name' => 'idx_intentos_usuario_created'],
        ['cols' => ['usuario_id', 'estado', 'created_at'], 'name' => 'idx_intentos_usuario_estado_created'],
        ['cols' => ['estado', 'created_at'], 'name' => 'idx_intentos_estado_created'],
        ['cols' => ['institucion_id', 'area_academica_id', 'estado'], 'name' => 'idx_intentos_inst_area_estado'],
        ['cols' => ['examen_id'], 'name' => 'idx_intentos_examen_id'],
        ['cols' => ['institucion_id'], 'name' => 'idx_intentos_institucion_id'],
        ['cols' => ['area_academica_id'], 'name' => 'idx_intentos_area_id'],
        ['cols' => ['tipo_simulacro_id'], 'name' => 'idx_intentos_tipo_simulacro_id'],
        ['cols' => ['categoria_id'], 'name' => 'idx_intentos_categoria_id'],
    ];

    private const INDICES_PREGUNTAS = [
        ['cols' => ['concepto_id', 'activa'], 'name' => 'idx_preguntas_concepto_activa'],
        ['cols' => ['examen_id', 'activa'], 'name' => 'idx_preguntas_examen_activa'],
        ['cols' => ['area_academica_id', 'institucion_id', 'activa'], 'name' => 'idx_preguntas_area_inst_activa'],
        ['cols' => ['seccion_id'], 'name' => 'idx_preguntas_seccion_id'],
    ];

    private const INDICES_VARIOS = [
        ['tabla' => 'alternativas', 'cols' => ['pregunta_id'], 'name' => 'idx_alternativas_pregunta_id'],
        ['tabla' => 'categorias', 'cols' => ['institucion_id', 'activo'], 'name' => 'idx_categorias_inst_activo'],
        ['tabla' => 'categorias', 'cols' => ['area_academica_id'], 'name' => 'idx_categorias_area_id'],
        ['tabla' => 'examenes', 'cols' => ['categoria_id'], 'name' => 'idx_examenes_categoria_id'],
        ['tabla' => 'examenes', 'cols' => ['area_academica_id'], 'name' => 'idx_examenes_area_id'],
        ['tabla' => 'secciones', 'cols' => ['examen_id'], 'name' => 'idx_secciones_examen_id'],
        ['tabla' => 'conceptos', 'cols' => ['seccion_id'], 'name' => 'idx_conceptos_seccion_id'],
        ['tabla' => 'conceptos', 'cols' => ['area_academica_id'], 'name' => 'idx_conceptos_area_id'],
        ['tabla' => 'instituciones', 'cols' => ['tipo_examen_id'], 'name' => 'idx_instituciones_tipo_examen_id'],
        ['tabla' => 'tipos_simulacro', 'cols' => ['area_academica_id', 'activo'], 'name' => 'idx_tipos_simulacro_area_activo'],
        ['tabla' => 'mensajes_ayuda', 'cols' => ['concepto_id'], 'name' => 'idx_mensajes_ayuda_concepto_id'],
        ['tabla' => 'users', 'cols' => ['aprobado_por'], 'name' => 'idx_users_aprobado_por'],
    ];

    public function up(): void
    {
        Schema::table('intentos_examen', function (Blueprint $table) {
            foreach (self::INDICES_INTENTOS as $index) {
                $table->index($index['cols'], $index['name']);
            }
        });

        Schema::table('preguntas', function (Blueprint $table) {
            foreach (self::INDICES_PREGUNTAS as $index) {
                $table->index($index['cols'], $index['name']);
            }
        });

        foreach (self::INDICES_VARIOS as $index) {
            Schema::table($index['tabla'], function (Blueprint $table) use ($index) {
                $table->index($index['cols'], $index['name']);
            });
        }
    }

    public function down(): void
    {
        foreach (array_merge(self::INDICES_VARIOS) as $index) {
            Schema::table($index['tabla'], function (Blueprint $table) use ($index) {
                $table->dropIndex($index['name']);
            });
        }

        Schema::table('preguntas', function (Blueprint $table) {
            foreach (self::INDICES_PREGUNTAS as $index) {
                $table->dropIndex($index['name']);
            }
        });

        Schema::table('intentos_examen', function (Blueprint $table) {
            foreach (self::INDICES_INTENTOS as $index) {
                $table->dropIndex($index['name']);
            }
        });
    }
};
