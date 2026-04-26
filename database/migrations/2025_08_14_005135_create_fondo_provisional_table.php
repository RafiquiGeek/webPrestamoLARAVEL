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
        Schema::create('fondo_provisional', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('prestamo_id')->comment('ID del préstamo');
            $table->unsignedBigInteger('asesor_id')->comment('ID del asesor que recibe el fondo');
            $table->unsignedInteger('operacion_id')->nullable()->comment('ID de la operación registrada');
            $table->decimal('monto_capital', 10, 2)->comment('Monto del capital del préstamo');
            $table->decimal('porcentaje', 5, 2)->default(5.00)->comment('Porcentaje del fondo provisional');
            $table->decimal('monto_fondo', 10, 2)->comment('Monto del fondo provisional (5% del capital)');
            $table->date('fecha_entrega')->comment('Fecha cuando el cliente entrega el fondo');
            $table->enum('estado', ['pendiente', 'entregado', 'rendido'])->default('entregado')->comment('Estado del fondo provisional');
            $table->text('observaciones')->nullable()->comment('Observaciones adicionales');
            $table->date('fecha_rendicion')->nullable()->comment('Fecha de rendición en caja');
            $table->unsignedBigInteger('rendido_por')->nullable()->comment('Usuario que rindió el fondo');
            $table->timestamps();

            // Foreign keys
            $table->foreign('prestamo_id')->references('id')->on('prestamos')->onDelete('cascade');
            $table->foreign('asesor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('operacion_id')->references('id')->on('operaciones')->onDelete('set null');
            $table->foreign('rendido_por')->references('id')->on('users')->onDelete('set null');

            // Índices
            $table->index(['prestamo_id', 'estado']);
            $table->index('asesor_id');
            $table->index('fecha_entrega');
            $table->index('estado');

            // Constraint: solo un fondo provisional por préstamo
            $table->unique('prestamo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fondo_provisional');
    }
};
