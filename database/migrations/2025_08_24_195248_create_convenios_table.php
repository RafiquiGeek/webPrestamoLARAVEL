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
        Schema::create('convenios', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('prestamo_id')->index();
            $table->decimal('monto_capital', 10, 2);
            $table->decimal('monto_moras', 10, 2);
            $table->decimal('descuento_moras', 10, 2)->default(0);
            $table->decimal('total_convenio', 10, 2);
            $table->integer('numero_cuotas');
            $table->decimal('valor_cuota', 10, 2);
            $table->date('fecha_inicio');
            $table->date('fecha_firma');
            $table->tinyInteger('estado')->default(0); // 0=Activo, 1=Cumplido, 2=Incumplido, 3=Cancelado
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('convenios');
    }
};
