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
        Schema::table('cuentas_cliente', function (Blueprint $table) {
            $table->unsignedBigInteger('billetera_digital_id')->nullable()->after('entidad_bancaria_id');
            $table->foreign('billetera_digital_id')->references('id')->on('billeteras_digitales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuentas_cliente', function (Blueprint $table) {
            $table->dropForeign(['billetera_digital_id']);
            $table->dropColumn('billetera_digital_id');
        });
    }
};
