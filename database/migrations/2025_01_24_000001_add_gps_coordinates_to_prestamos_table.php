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
        Schema::table('prestamos', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('observaciones')->comment('Latitud GPS al crear préstamo');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude')->comment('Longitud GPS al crear préstamo');
            $table->timestamp('gps_captured_at')->nullable()->after('longitude')->comment('Fecha y hora de captura GPS');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prestamos', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'gps_captured_at']);
        });
    }
};
