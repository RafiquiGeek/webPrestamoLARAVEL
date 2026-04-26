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
        Schema::create('cuotas_convenio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('convenio_id')->index();
            $table->integer('numero_cuota');
            $table->decimal('monto_cuota', 10, 2);
            $table->date('fecha_vencimiento');
            $table->date('fecha_pago')->nullable();
            $table->decimal('monto_pagado', 10, 2)->default(0);
            $table->tinyInteger('estado')->default(0); // 0=Pendiente, 1=Parcial, 2=Pagado, 3=Vencido
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuotas_convenio');
    }
};
