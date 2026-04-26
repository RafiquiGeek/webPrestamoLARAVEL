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
        Schema::create('horarios_trabajo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->time('hora_entrada');
            $table->time('hora_salida');
            $table->time('inicio_refrigerio')->nullable();
            $table->time('fin_refrigerio')->nullable();
            $table->integer('tolerancia_entrada')->default(15); // minutos de tolerancia
            $table->integer('tolerancia_salida')->default(15); // minutos de tolerancia
            $table->json('dias_laborales')->nullable(); // L,M,M,J,V = 1,2,3,4,5
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios_trabajo');
    }
};
