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
        Schema::table('operaciones_cuota', function (Blueprint $table) {
            // Agregar campos para mayor precisión en el tracking de pagos
            $table->decimal('monto_aplicado', 10, 2)->default(0.00)->after('operacion_id')
                ->comment('Monto específico de la operación aplicado a esta cuota');

            $table->string('concepto', 50)->default('pago_general')->after('monto_aplicado')
                ->comment('Concepto del pago: capital, interes, comision, igv, pago_general');

            $table->text('observaciones')->nullable()->after('concepto')
                ->comment('Observaciones específicas de la aplicación del pago a esta cuota');

            $table->timestamp('aplicado_en')->useCurrent()->after('observaciones')
                ->comment('Fecha y hora cuando se aplicó el pago a la cuota');

            // Índices para optimizar consultas
            $table->index(['cuota_id', 'concepto'], 'idx_operaciones_cuota_concepto');
            $table->index(['operacion_id', 'monto_aplicado'], 'idx_operaciones_cuota_monto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operaciones_cuota', function (Blueprint $table) {
            $table->dropIndex('idx_operaciones_cuota_concepto');
            $table->dropIndex('idx_operaciones_cuota_monto');
            $table->dropColumn([
                'monto_aplicado',
                'concepto',
                'observaciones',
                'aplicado_en',
            ]);
        });
    }
};
