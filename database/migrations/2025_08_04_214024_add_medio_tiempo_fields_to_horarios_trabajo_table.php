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
            $table->boolean('es_medio_tiempo')->default(false)->after('tolerancia_salida')
                ->comment('Indica si es un horario de medio tiempo');
            $table->json('horarios_por_dia')->nullable()->after('es_medio_tiempo')
                ->comment('Horarios específicos por día para horarios flexibles');
            $table->string('tipo_horario')->default('completo')->after('horarios_por_dia')
                ->comment('completo, medio_tiempo, flexible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horarios_trabajo', function (Blueprint $table) {
            $table->dropColumn(['es_medio_tiempo', 'horarios_por_dia', 'tipo_horario']);
        });
    }
};
