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
        Schema::table('etiquetas_cliente', function (Blueprint $table) {
            $table->unsignedInteger('prestamo_id')->nullable()->after('cliente_id');
            $table->foreign('prestamo_id')->references('id')->on('prestamos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etiquetas_cliente', function (Blueprint $table) {
            $table->dropForeign(['prestamo_id']);
            $table->dropColumn('prestamo_id');
        });
    }
};
