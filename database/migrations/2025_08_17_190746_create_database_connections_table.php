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
        Schema::create('database_connections', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Nombre de la conexión
            $table->string('description')->nullable(); // Descripción
            $table->string('driver')->default('mysql'); // Tipo de base de datos
            $table->string('host');
            $table->integer('port')->default(3306);
            $table->string('database');
            $table->string('username');
            $table->text('password'); // Encriptado
            $table->string('charset')->default('utf8mb4');
            $table->string('collation')->default('utf8mb4_unicode_ci');
            $table->string('prefix')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sync_enabled')->default(false); // Si esta conexión se usa para sincronización
            $table->json('sync_tables')->nullable(); // Tablas a sincronizar
            $table->timestamp('last_sync_at')->nullable(); // Última sincronización
            $table->text('sync_errors')->nullable(); // Errores de sincronización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('database_connections');
    }
};
