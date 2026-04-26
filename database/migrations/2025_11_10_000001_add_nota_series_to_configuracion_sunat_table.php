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
        Schema::table('configuracion_sunat', function (Blueprint $table) {
            $table->string('serie_nota_credito')->nullable()->default('FC01');
            $table->string('numero_inicial_nota_credito')->nullable()->default('1');
            $table->string('serie_nota_debito')->nullable()->default('FD01');
            $table->string('numero_inicial_nota_debito')->nullable()->default('1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_sunat', function (Blueprint $table) {
            $table->dropColumn([
                'serie_nota_credito',
                'numero_inicial_nota_credito',
                'serie_nota_debito',
                'numero_inicial_nota_debito'
            ]);
        });
    }
};