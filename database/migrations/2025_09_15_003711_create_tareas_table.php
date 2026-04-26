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
        Schema::create('tareas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->foreignId('asignado_por')->constrained('users');
            $table->foreignId('asignado_a')->constrained('users');
            $table->foreignId('columna_id')->constrained('tablero_columnas');
            $table->enum('prioridad', ['baja', 'media', 'alta', 'urgente'])->default('media');
            $table->enum('estado', ['pendiente', 'en_progreso', 'pausado', 'completado', 'cancelado'])->default('pendiente');
            $table->datetime('fecha_asignacion');
            $table->datetime('fecha_inicio')->nullable();
            $table->datetime('fecha_vencimiento')->nullable();
            $table->datetime('fecha_completado')->nullable();
            $table->integer('tiempo_estimado')->nullable()->comment('Tiempo estimado en minutos');
            $table->integer('tiempo_real')->nullable()->comment('Tiempo real utilizado en minutos');
            $table->integer('orden')->default(0);
            $table->integer('progreso')->default(0);
            $table->timestamps();

            $table->index(['asignado_a', 'estado']);
            $table->index('columna_id');
            $table->index('fecha_vencimiento');
            $table->index('orden');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
