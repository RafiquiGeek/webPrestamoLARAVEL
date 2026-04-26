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
            // URL del servidor SIRE API
            $table->string('sire_api_url')->nullable()
                ->default('http://sire.test/api')
                ->after('api_client_secret')
                ->comment('URL base de la API de SIRE');

            // Token de autenticación para SIRE
            $table->text('sire_api_token')->nullable()
                ->after('sire_api_url')
                ->comment('Token de autenticación Bearer para SIRE API');

            // Flag para habilitar/deshabilitar SIRE
            $table->boolean('usar_sire')->default(false)
                ->after('sire_api_token')
                ->comment('Usar SIRE para firma y envío de comprobantes');

            // Índice para búsquedas rápidas
            $table->index('usar_sire');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_sunats', function (Blueprint $table) {
            $table->dropIndex(['usar_sire']);
            $table->dropColumn([
                'sire_api_url',
                'sire_api_token',
                'usar_sire'
            ]);
        });
    }
};
