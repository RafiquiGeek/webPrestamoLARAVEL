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
        Schema::create('api_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Clave de configuración (ej: 'dni_api_url', 'dni_api_token')
            $table->string('name'); // Nombre descriptivo
            $table->text('value'); // Valor de la configuración
            $table->text('description')->nullable(); // Descripción de la configuración
            $table->boolean('is_active')->default(true); // Si está activo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_configs');
    }
};
