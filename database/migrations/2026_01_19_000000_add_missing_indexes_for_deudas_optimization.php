<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega índices adicionales que faltan para optimizar la consulta de deudas
     * Complementa la migración 2025_12_22_000000_add_composite_indexes_for_deudas_performance.php
     */
    public function up(): void
    {
        // Índice para direccion_cobro_id en préstamos (nueva columna)
        Schema::table('prestamos', function (Blueprint $table) {
            // Solo agregar si no existe
            if (!$this->indexExists('prestamos', 'prestamos_direccion_cobro_id_index')) {
                $table->index('direccion_cobro_id');
            }

            // Índice para estado de préstamos (usado en filtros)
            if (!$this->indexExists('prestamos', 'idx_prestamos_estado')) {
                $table->index('estado', 'idx_prestamos_estado');
            }

            // Índice compuesto para cliente + estado (usado frecuentemente)
            if (!$this->indexExists('prestamos', 'idx_prestamos_cliente_estado')) {
                $table->index(['cliente_id', 'estado'], 'idx_prestamos_cliente_estado');
            }
        });

        // Índice para zona_id en direcciones (usado en filtros de ubicación)
        Schema::table('direcciones', function (Blueprint $table) {
            if (!$this->indexExists('direcciones', 'idx_direcciones_zona_id')) {
                $table->index('zona_id', 'idx_direcciones_zona_id');
            }

            // Índice compuesto para zona + sucursal (usado en filtros)
            if (!$this->indexExists('direcciones', 'idx_direcciones_zona_sucursal')) {
                $table->index(['zona_id', 'sucursal_id'], 'idx_direcciones_zona_sucursal');
            }
        });

        // Índice para documento en personas (usado en búsquedas)
        Schema::table('personas', function (Blueprint $table) {
            if (!$this->indexExists('personas', 'idx_personas_documento')) {
                $table->index('documento', 'idx_personas_documento');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            if ($this->indexExists('prestamos', 'prestamos_direccion_cobro_id_index')) {
                $table->dropIndex('prestamos_direccion_cobro_id_index');
            }
            if ($this->indexExists('prestamos', 'idx_prestamos_estado')) {
                $table->dropIndex('idx_prestamos_estado');
            }
            if ($this->indexExists('prestamos', 'idx_prestamos_cliente_estado')) {
                $table->dropIndex('idx_prestamos_cliente_estado');
            }
        });

        Schema::table('direcciones', function (Blueprint $table) {
            if ($this->indexExists('direcciones', 'idx_direcciones_zona_id')) {
                $table->dropIndex('idx_direcciones_zona_id');
            }
            if ($this->indexExists('direcciones', 'idx_direcciones_zona_sucursal')) {
                $table->dropIndex('idx_direcciones_zona_sucursal');
            }
        });

        Schema::table('personas', function (Blueprint $table) {
            if ($this->indexExists('personas', 'idx_personas_documento')) {
                $table->dropIndex('idx_personas_documento');
            }
        });
    }

    /**
     * Verifica si un índice existe en una tabla
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes($table);

        return array_key_exists($index, $indexes);
    }
};
