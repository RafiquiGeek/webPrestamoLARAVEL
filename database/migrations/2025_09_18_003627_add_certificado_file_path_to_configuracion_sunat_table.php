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
            $table->string('certificado_file_path')->nullable()->after('certificado_clave')->comment('Ruta del archivo de certificado .pfx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_sunats', function (Blueprint $table) {
            $table->dropColumn('certificado_file_path');
        });
    }
};
