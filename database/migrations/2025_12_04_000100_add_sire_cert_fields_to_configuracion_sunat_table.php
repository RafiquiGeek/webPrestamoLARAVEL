<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('configuracion_sunats')) {
            Schema::table('configuracion_sunats', function (Blueprint $table) {
                if (!Schema::hasColumn('configuracion_sunats', 'sire_cert_path')) {
                    $table->string('sire_cert_path')->nullable()->after('sire_api_token');
                }
                if (!Schema::hasColumn('configuracion_sunats', 'sire_cert_password')) {
                    $table->string('sire_cert_password')->nullable()->after('sire_cert_path');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('configuracion_sunats')) {
            Schema::table('configuracion_sunats', function (Blueprint $table) {
                if (Schema::hasColumn('configuracion_sunats', 'sire_cert_password')) {
                    $table->dropColumn('sire_cert_password');
                }
                if (Schema::hasColumn('configuracion_sunats', 'sire_cert_path')) {
                    $table->dropColumn('sire_cert_path');
                }
            });
        }
    }
};
