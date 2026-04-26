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
            $table->string('certificado_pem_path')->nullable()->after('certificado_file_path');
            $table->string('clave_privada_pem_path')->nullable()->after('certificado_pem_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_sunat', function (Blueprint $table) {
            $table->dropColumn(['certificado_pem_path', 'clave_privada_pem_path']);
        });
    }
};
