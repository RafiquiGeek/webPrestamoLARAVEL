<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Aumenta el tamaño de la columna 'url' en las tablas de tracking
     * para evitar el error: "Data too long for column 'url'"
     */
    public function up(): void
    {
        // Aumentar tamaño de columna url en module_time_tracking
        if (Schema::hasTable('module_time_tracking')) {
            Schema::table('module_time_tracking', function (Blueprint $table) {
                if (Schema::hasColumn('module_time_tracking', 'url')) {
                    $table->text('url')->change(); // VARCHAR(255) -> TEXT
                }
            });
        }

        // Aumentar tamaño de columna url en user_activities
        if (Schema::hasTable('user_activities')) {
            Schema::table('user_activities', function (Blueprint $table) {
                if (Schema::hasColumn('user_activities', 'url')) {
                    $table->text('url')->change(); // VARCHAR(255) -> TEXT
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir a VARCHAR(255) si es necesario
        if (Schema::hasTable('module_time_tracking')) {
            Schema::table('module_time_tracking', function (Blueprint $table) {
                if (Schema::hasColumn('module_time_tracking', 'url')) {
                    $table->string('url', 255)->change(); // TEXT -> VARCHAR(255)
                }
            });
        }

        if (Schema::hasTable('user_activities')) {
            Schema::table('user_activities', function (Blueprint $table) {
                if (Schema::hasColumn('user_activities', 'url')) {
                    $table->string('url', 255)->change(); // TEXT -> VARCHAR(255)
                }
            });
        }
    }
};
