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
        Schema::create('comprobante_reintentos', function (Blueprint $table) {
            $table->id();
            $table->integer('comprobante_id')->unsigned();
            $table->integer('intentos')->default(0);
            $table->integer('max_intentos')->default(5);
            $table->string('ultimo_error_code')->nullable();
            $table->text('ultimo_error_mensaje')->nullable();
            $table->timestamp('proximo_intento')->nullable();
            $table->enum('estado', ['pendiente', 'procesando', 'exitoso', 'fallido', 'cancelado'])->default('pendiente');
            $table->timestamp('procesado_at')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices para mejorar rendimiento
            $table->index('estado');
            $table->index('proximo_intento');
            $table->index(['estado', 'proximo_intento']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobante_reintentos');
    }
};
