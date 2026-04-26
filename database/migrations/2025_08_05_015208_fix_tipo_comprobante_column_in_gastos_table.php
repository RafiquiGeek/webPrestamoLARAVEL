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
        Schema::table('gastos', function (Blueprint $table) {
            // Drop the current enum and recreate it with proper values
            $table->dropColumn('tipo_comprobante');
        });

        Schema::table('gastos', function (Blueprint $table) {
            // Add the column back with correct enum values
            $table->enum('tipo_comprobante', [
                'factura',
                'boleta',
                'recibo_honorarios',
                'ticket',
                'sin_documento',
            ])->after('apellidos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropColumn('tipo_comprobante');
        });

        Schema::table('gastos', function (Blueprint $table) {
            $table->enum('tipo_comprobante', ['factura', 'boleta', 'recibo_honorarios', 'ticket', 'sin_documento'])
                ->after('apellidos');
        });
    }
};
