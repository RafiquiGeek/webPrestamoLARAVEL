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
            // Credenciales OAuth2 de la API SUNAT (obtenidas del Portal SOL)
            $table->string('sire_client_id')->nullable()->after('sire_key_path');
            $table->text('sire_client_secret')->nullable()->after('sire_client_id'); // Encriptado

            // Token OAuth2 (se genera automáticamente y se renueva cada hora)
            $table->text('sire_access_token')->nullable()->after('sire_client_secret');
            $table->timestamp('sire_token_expires_at')->nullable()->after('sire_access_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_sunats', function (Blueprint $table) {
            $table->dropColumn([
                'sire_client_id',
                'sire_client_secret',
                'sire_access_token',
                'sire_token_expires_at',
            ]);
        });
    }
};
