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
        // Índices para tabla cuotas
        Schema::table('cuotas', function (Blueprint $table) {
            $table->index('estado', 'idx_deudas_cuotas_estado');
            $table->index('fecha_pago', 'idx_deudas_cuotas_fecha_pago');
            $table->index('prestamo_id', 'idx_deudas_cuotas_prestamo_id');
        });

        // Índices para tabla moras (mora_cuota)
        Schema::table('mora_cuota', function (Blueprint $table) {
            $table->index('cuota_id', 'idx_deudas_mora_cuota_cuota_id');
            $table->index('estado', 'idx_deudas_mora_cuota_estado');
        });

        // Índices para tabla prestamos
        Schema::table('prestamos', function (Blueprint $table) {
            $table->index('cliente_id', 'idx_deudas_prestamos_cliente_id');
        });

        // Índices para tabla personas
        Schema::table('personas', function (Blueprint $table) {
            $table->index('documento', 'idx_deudas_personas_documento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices en caso de rollback
        Schema::table('cuotas', function (Blueprint $table) {
            $table->dropIndex('idx_deudas_cuotas_estado');
            $table->dropIndex('idx_deudas_cuotas_fecha_pago');
            $table->dropIndex('idx_deudas_cuotas_prestamo_id');
        });

        Schema::table('mora_cuota', function (Blueprint $table) {
            $table->dropIndex('idx_deudas_mora_cuota_cuota_id');
            $table->dropIndex('idx_deudas_mora_cuota_estado');
        });

        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropIndex('idx_deudas_prestamos_cliente_id');
        });

        Schema::table('personas', function (Blueprint $table) {
            $table->dropIndex('idx_deudas_personas_documento');
        });
    }
};