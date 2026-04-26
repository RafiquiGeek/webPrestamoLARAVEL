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
        Schema::create('pagos_convenio_flexible', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convenio_id')->constrained('convenios')->onDelete('cascade');
            $table->foreignId('operacion_id')->nullable()->constrained('operaciones')->onDelete('set null');
            $table->decimal('monto', 10, 2);
            $table->date('fecha_pago');
            $table->unsignedBigInteger('user_id'); // Usuario que registró el pago
            $table->string('metodo_pago')->nullable(); // Efectivo, Transferencia, etc.
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices para mejorar el rendimiento
            $table->index('convenio_id');
            $table->index('fecha_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_convenio_flexible');
    }
};
