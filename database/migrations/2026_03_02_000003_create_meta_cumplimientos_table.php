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
        Schema::create('meta_cumplimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meta_id')->constrained('metas')->onDelete('cascade');
            $table->foreignId('asesor_id')->constrained('users')->onDelete('cascade');
            $table->integer('anio');
            $table->integer('mes');
            $table->integer('prestamos_originados')->default(0);
            $table->integer('prestamos_vigentes')->default(0);
            $table->integer('prestamos_morosos')->default(0);
            $table->integer('renovaciones')->default(0);
            $table->decimal('porcentaje_cumplimiento', 8, 2)->default(0);
            $table->decimal('porcentaje_morosidad', 8, 2)->default(0);
            $table->string('nivel_calificacion')->nullable();
            $table->integer('meses_consecutivos')->default(0);
            $table->decimal('comision_base', 12, 2)->default(0);
            $table->decimal('comision_final', 12, 2)->default(0);
            $table->boolean('penalizado_morosidad')->default(false);
            $table->decimal('porcentaje_morosidad_umbral', 8, 2)->default(20);
            $table->timestamp('fecha_calculo')->nullable();
            $table->timestamps();

            $table->unique(['asesor_id', 'anio', 'mes']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_cumplimientos');
    }
};
