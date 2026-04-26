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
        Schema::create('feriados_horarios_especiales', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('tipo')->comment('feriado, medio_dia, especial');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->time('hora_entrada')->nullable()->comment('Para horarios especiales de medio día');
            $table->time('hora_salida')->nullable()->comment('Para horarios especiales de medio día');
            $table->time('inicio_refrigerio')->nullable();
            $table->time('fin_refrigerio')->nullable();
            $table->boolean('aplicar_todas_areas')->default(true)->comment('Si aplica a todas las áreas o solo específicas');
            $table->json('areas_laborales_ids')->nullable()->comment('IDs de áreas específicas si no aplica a todas');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique('fecha');
            $table->index(['fecha', 'activo']);
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feriados_horarios_especiales');
    }
};
