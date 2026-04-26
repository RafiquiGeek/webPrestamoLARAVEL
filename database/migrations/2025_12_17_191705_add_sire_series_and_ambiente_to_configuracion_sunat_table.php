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
        Schema::table('configuracion_sunats', function (Blueprint $table) {
            // Ambiente SUNAT (0 = Testing/Beta, 1 = Producción)
            $table->boolean('modo_produccion')->default(false)->after('usar_sire')
                ->comment('0 = Testing/Beta, 1 = Producción');

            // Series para ambiente de TESTING
            $table->string('sire_serie_boleta_test', 10)->nullable()->after('modo_produccion')
                ->comment('Serie para boletas en ambiente de pruebas (ej: T001)');
            $table->integer('sire_numero_boleta_test')->default(1)->after('sire_serie_boleta_test')
                ->comment('Numeración actual para boletas en testing');

            $table->string('sire_serie_factura_test', 10)->nullable()->after('sire_numero_boleta_test')
                ->comment('Serie para facturas en ambiente de pruebas (ej: T001)');
            $table->integer('sire_numero_factura_test')->default(1)->after('sire_serie_factura_test')
                ->comment('Numeración actual para facturas en testing');

            // Series para ambiente de PRODUCCIÓN
            $table->string('sire_serie_boleta_prod', 10)->nullable()->after('sire_numero_factura_test')
                ->comment('Serie para boletas en producción (ej: B001)');
            $table->integer('sire_numero_boleta_prod')->default(1)->after('sire_serie_boleta_prod')
                ->comment('Numeración actual para boletas en producción');

            $table->string('sire_serie_factura_prod', 10)->nullable()->after('sire_numero_boleta_prod')
                ->comment('Serie para facturas en producción (ej: F001)');
            $table->integer('sire_numero_factura_prod')->default(1)->after('sire_serie_factura_prod')
                ->comment('Numeración actual para facturas en producción');

            // Series para notas de crédito y débito
            $table->string('sire_serie_nota_credito', 10)->nullable()->after('sire_numero_factura_prod')
                ->comment('Serie para notas de crédito');
            $table->integer('sire_numero_nota_credito')->default(1)->after('sire_serie_nota_credito');

            $table->string('sire_serie_nota_debito', 10)->nullable()->after('sire_numero_nota_credito')
                ->comment('Serie para notas de débito');
            $table->integer('sire_numero_nota_debito')->default(1)->after('sire_serie_nota_debito');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_sunats', function (Blueprint $table) {
            $table->dropColumn([
                'modo_produccion',
                'sire_serie_boleta_test',
                'sire_numero_boleta_test',
                'sire_serie_factura_test',
                'sire_numero_factura_test',
                'sire_serie_boleta_prod',
                'sire_numero_boleta_prod',
                'sire_serie_factura_prod',
                'sire_numero_factura_prod',
                'sire_serie_nota_credito',
                'sire_numero_nota_credito',
                'sire_serie_nota_debito',
                'sire_numero_nota_debito',
            ]);
        });
    }
};
