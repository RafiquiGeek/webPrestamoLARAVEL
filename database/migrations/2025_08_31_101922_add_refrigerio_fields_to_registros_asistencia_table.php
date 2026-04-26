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
        Schema::table('registros_asistencia', function (Blueprint $table) {
            // Campos para control de refrigerio
            $table->time('inicio_refrigerio')->nullable()->after('hora_salida');
            $table->time('fin_refrigerio')->nullable()->after('inicio_refrigerio');
            $table->integer('minutos_refrigerio')->nullable()->after('fin_refrigerio')->comment('Duración real del refrigerio en minutos');
            $table->enum('estado_refrigerio', ['normal', 'excedido'])->nullable()->after('minutos_refrigerio');

            // Coordenadas GPS para refrigerio
            $table->decimal('latitud_inicio_refrigerio', 10, 7)->nullable()->after('estado_refrigerio');
            $table->decimal('longitud_inicio_refrigerio', 10, 7)->nullable()->after('latitud_inicio_refrigerio');
            $table->decimal('latitud_fin_refrigerio', 10, 7)->nullable()->after('longitud_inicio_refrigerio');
            $table->decimal('longitud_fin_refrigerio', 10, 7)->nullable()->after('latitud_fin_refrigerio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registros_asistencia', function (Blueprint $table) {
            $table->dropColumn([
                'inicio_refrigerio',
                'fin_refrigerio',
                'minutos_refrigerio',
                'estado_refrigerio',
                'latitud_inicio_refrigerio',
                'longitud_inicio_refrigerio',
                'latitud_fin_refrigerio',
                'longitud_fin_refrigerio',
            ]);
        });
    }
};
