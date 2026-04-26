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
        Schema::create('billeteras_digitales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Nombre de la billetera (Yape, Plin, Dale, Tunki, Bim, etc.)
            $table->boolean('status')->default(1); // Estado activo/inactivo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billeteras_digitales');
    }
};
