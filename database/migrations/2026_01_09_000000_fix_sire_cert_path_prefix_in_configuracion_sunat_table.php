<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Corrige las rutas de certificados SIRE que no tienen el prefijo 'keys/'
     */
    public function up(): void
    {
        // Actualizar todos los registros donde sire_cert_path no es NULL
        // y no comienza con 'keys/'
        DB::table('configuracion_sunats')
            ->whereNotNull('sire_cert_path')
            ->where('sire_cert_path', 'not like', 'keys/%')
            ->update([
                'sire_cert_path' => DB::raw("CONCAT('keys/', sire_cert_path)")
            ]);

        \Log::info('Rutas de certificados SIRE corregidas - se agregó el prefijo keys/ donde faltaba');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir los cambios quitando el prefijo 'keys/'
        DB::table('configuracion_sunats')
            ->whereNotNull('sire_cert_path')
            ->where('sire_cert_path', 'like', 'keys/%')
            ->update([
                'sire_cert_path' => DB::raw("SUBSTRING(sire_cert_path, 6)")
            ]);

        \Log::info('Rutas de certificados SIRE revertidas - se quitó el prefijo keys/');
    }
};
