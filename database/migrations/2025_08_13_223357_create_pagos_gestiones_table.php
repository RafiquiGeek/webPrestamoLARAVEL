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
        Schema::create('pagos_gestiones', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('gestion_id')->comment('ID de la gestión que registró el pago');
            $table->unsignedInteger('prestamo_id')->comment('ID del préstamo al cual se aplica el pago');
            $table->decimal('monto_pagado', 10, 2)->comment('Monto total pagado en esta gestión');
            $table->enum('tipo_pago', ['cuota', 'mora', 'mixto'])->comment('Tipo de deuda pagada');
            $table->json('detalle_cuotas')->nullable()->comment('JSON con IDs y montos de cuotas pagadas');
            $table->json('detalle_moras')->nullable()->comment('JSON con IDs y montos de moras pagadas');
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'deposito', 'otro'])->default('efectivo')->comment('Método de pago utilizado');
            $table->text('observaciones')->nullable()->comment('Observaciones del pago');
            $table->timestamps();

            // Relaciones
            $table->foreign('gestion_id')->references('id')->on('gestiones')->onDelete('cascade');
            $table->foreign('prestamo_id')->references('id')->on('prestamos')->onDelete('cascade');

            // Índices
            $table->index('gestion_id');
            $table->index('prestamo_id');
            $table->index('tipo_pago');
            $table->index('metodo_pago');
            $table->index(['prestamo_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_gestiones');
    }
};
