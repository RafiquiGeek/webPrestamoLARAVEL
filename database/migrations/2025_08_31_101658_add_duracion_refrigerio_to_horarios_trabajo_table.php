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
            // Agregar campo para la duración del refrigerio en minutos
            $table->integer('duracion_refrigerio_minutos')->nullable()->after('fin_refrigerio')->comment('Duración del refrigerio en minutos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horarios_trabajo', function (Blueprint $table) {
            $table->dropColumn('duracion_refrigerio_minutos');
        });
    }
};
