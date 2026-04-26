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
        Schema::create('auditoria_operaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('operacion_id')->index();
            $table->unsignedInteger('prestamo_id')->index();
            $table->string('tipo_operacion');
            $table->string('accion'); // 'creado', 'editado', 'anulado'
            $table->unsignedBigInteger('usuario_id');
            $table->string('usuario_nombre');
            $table->json('valores_anteriores')->nullable(); // Estado antes del cambio
            $table->json('valores_nuevos'); // Estado después del cambio
            $table->json('operaciones_hijas_afectadas')->nullable(); // IDs de operaciones hijas que cambiaron
            $table->text('justificacion')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index(['operacion_id', 'created_at']);
            $table->index(['prestamo_id', 'created_at']);
            $table->index(['usuario_id', 'created_at']);

            // Relaciones
            $table->foreign('operacion_id')->references('id')->on('operaciones')->onDelete('cascade');
            $table->foreign('prestamo_id')->references('id')->on('prestamos')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria_operaciones');
    }
};
