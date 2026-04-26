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
        Schema::create('abonos_mora_favor_convenio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cuota_convenio_id');
            $table->unsignedBigInteger('operacion_id');
            $table->decimal('monto_abonado', 10, 2);
            $table->decimal('monto_utilizado', 10, 2)->default(0);
            $table->decimal('saldo_favor', 10, 2)->default(0);
            $table->text('comentario')->nullable();
            $table->enum('estado', ['activo', 'utilizado', 'anulado', 'reservado_caja'])->default('activo');
            $table->timestamp('fecha_abono');
            $table->timestamps();

            // Índices para mejorar el rendimiento
            $table->index(['cuota_convenio_id', 'estado']);
            $table->index('fecha_abono');
            $table->index('operacion_id');

            // Foreign keys
            $table->foreign('cuota_convenio_id')->references('id')->on('cuotas_convenio')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abonos_mora_favor_convenio');
    }
};
