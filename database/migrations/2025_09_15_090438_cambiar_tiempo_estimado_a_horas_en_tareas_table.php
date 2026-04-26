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
        Schema::table('tareas', function (Blueprint $table) {
            // Cambiar tiempo_estimado de integer a decimal (5,2) para permitir horas con decimales
            $table->decimal('tiempo_estimado', 5, 2)->nullable()->change()->comment('Tiempo estimado en horas');

            // Cambiar tiempo_real de integer a decimal (5,2) para permitir horas con decimales
            $table->decimal('tiempo_real', 5, 2)->nullable()->change()->comment('Tiempo real utilizado en horas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            // Revertir a integer (minutos)
            $table->integer('tiempo_estimado')->nullable()->change()->comment('Tiempo estimado en minutos');
            $table->integer('tiempo_real')->nullable()->change()->comment('Tiempo real utilizado en minutos');
        });
    }
};
