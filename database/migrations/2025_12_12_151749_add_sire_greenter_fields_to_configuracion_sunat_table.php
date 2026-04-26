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
            // Verificar si las columnas ya existen antes de agregarlas
            if (!Schema::hasColumn('configuracion_sunats', 'usar_sire')) {
                $table->boolean('usar_sire')->default(true)->comment('Activar integración directa con SUNAT vía Greenter');
            }

            // Certificado digital (.p12 o .pfx)
            if (!Schema::hasColumn('configuracion_sunats', 'sire_cert_path')) {
                $table->string('sire_cert_path')->nullable()->comment('Ruta del certificado digital .p12/.pfx');
            }
            if (!Schema::hasColumn('configuracion_sunats', 'sire_cert_password')) {
                $table->string('sire_cert_password')->nullable()->comment('Contraseña del certificado (encriptada)');
            }

            // Usuario SOL (usuario secundario SUNAT)
            if (!Schema::hasColumn('configuracion_sunats', 'sol_user')) {
                $table->string('sol_user', 50)->nullable()->comment('Usuario secundario SUNAT SOL');
            }
            if (!Schema::hasColumn('configuracion_sunats', 'sol_pass')) {
                $table->string('sol_pass')->nullable()->comment('Contraseña SOL (encriptada)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_sunats', function (Blueprint $table) {
            $columns = ['usar_sire', 'sire_cert_path', 'sire_cert_password', 'sol_user', 'sol_pass'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('configuracion_sunats', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
