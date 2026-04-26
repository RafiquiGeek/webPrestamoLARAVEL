<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega el campo sire_key_path para almacenar la ruta del archivo
     * de clave privada en formato PEM (extraído del .pfx)
     */
    public function up(): void
    {
        Schema::table('configuracion_sunats', function (Blueprint $table) {
            if (!Schema::hasColumn('configuracion_sunats', 'sire_key_path')) {
                $table->string('sire_key_path')->nullable()->after('sire_cert_path')
                    ->comment('Ruta de la clave privada en formato PEM extraída del certificado');
            }
        });

        \Log::info('Campo sire_key_path agregado a la tabla configuracion_sunats');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_sunats', function (Blueprint $table) {
            if (Schema::hasColumn('configuracion_sunats', 'sire_key_path')) {
                $table->dropColumn('sire_key_path');
            }
        });

        \Log::info('Campo sire_key_path eliminado de la tabla configuracion_sunats');
    }
};
