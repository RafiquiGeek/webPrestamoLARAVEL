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
            // Hacer que los campos base sean nullable para horarios personalizados
            $table->time('hora_entrada')->nullable()->change();
            $table->time('hora_salida')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horarios_trabajo', function (Blueprint $table) {
            // Revertir los campos a NOT NULL
            $table->time('hora_entrada')->nullable(false)->change();
            $table->time('hora_salida')->nullable(false)->change();
        });
    }
};
