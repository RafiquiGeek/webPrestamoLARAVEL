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
                if (!Schema::hasColumn('configuracion_sunats', 'sire_api_url')) {
                    $table->string('sire_api_url')->nullable()
                        ->default('http://sire.test/api')
                        ->after('api_client_secret');
                }

                if (!Schema::hasColumn('configuracion_sunats', 'sire_api_token')) {
                    $table->string('sire_api_token')->nullable()->after('sire_api_url');
                }

                if (!Schema::hasColumn('configuracion_sunats', 'usar_sire')) {
                    $table->boolean('usar_sire')->default(false)->after('sire_api_token');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('configuracion_sunats')) {
            Schema::table('configuracion_sunats', function (Blueprint $table) {
                if (Schema::hasColumn('configuracion_sunats', 'usar_sire')) {
                    $table->dropColumn('usar_sire');
                }
                if (Schema::hasColumn('configuracion_sunats', 'sire_api_token')) {
                    $table->dropColumn('sire_api_token');
                }
                if (Schema::hasColumn('configuracion_sunats', 'sire_api_url')) {
                    $table->dropColumn('sire_api_url');
                }
            });
        }
    }
};
