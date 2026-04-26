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
        Schema::table('horarios_trabajo', function (Blueprint $table) {
            // Agregar columna para horarios flexibles por día de la semana
            $table->json('horarios_semanales')->nullable()->after('horarios_por_dia');

            // Columna para indicar si es horario personalizado por días
            $table->boolean('es_horario_personalizado')->default(false)->after('es_medio_tiempo');

            // Descripción del horario para mostrar en las listas
            $table->text('descripcion_horario')->nullable()->after('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horarios_trabajo', function (Blueprint $table) {
            $table->dropColumn(['horarios_semanales', 'es_horario_personalizado', 'descripcion_horario']);
        });
    }
};
