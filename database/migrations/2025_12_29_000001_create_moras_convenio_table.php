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
        Schema::create('moras_convenio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuota_convenio_id')->constrained('cuotas_convenio')->onDelete('cascade');
            $table->date('fecha');
            $table->integer('dias_mora')->default(1);
            $table->decimal('monto', 10, 2)->default(0);
            $table->decimal('monto_pagado', 10, 2)->default(0);
            $table->string('estado')->default('pendiente');
            $table->timestamps();

            // Índices para mejorar el rendimiento
            $table->index('cuota_convenio_id');
            $table->index('estado');
            $table->index('fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moras_convenio');
    }
};
