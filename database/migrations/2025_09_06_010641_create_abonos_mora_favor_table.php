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
        Schema::create('abonos_mora_favor', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('cuota_id');
            $table->unsignedInteger('operacion_id');
            $table->decimal('monto_abonado', 10, 2);
            $table->decimal('monto_utilizado', 10, 2)->default(0);
            $table->decimal('saldo_favor', 10, 2)->default(0);
            $table->text('comentario')->nullable();
            $table->enum('estado', ['activo', 'utilizado', 'anulado'])->default('activo');
            $table->timestamp('fecha_abono');
            $table->timestamps();

            // Solo índices por ahora, foreign keys se agregarán después si es necesario
            $table->index(['cuota_id', 'estado']);
            $table->index('fecha_abono');
            $table->index('operacion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abonos_mora_favor');
    }
};
