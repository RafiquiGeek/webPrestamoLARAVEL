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
        Schema::table('mora_cuota', function (Blueprint $table) {
            // Agregar campo monto_pagado para manejar pagos parciales de moras
            $table->decimal('monto_pagado', 10, 2)->default(0.00)->after('monto')
                ->comment('Monto pagado de la mora (puede ser parcial)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mora_cuota', function (Blueprint $table) {
            $table->dropColumn('monto_pagado');
        });
    }
};
