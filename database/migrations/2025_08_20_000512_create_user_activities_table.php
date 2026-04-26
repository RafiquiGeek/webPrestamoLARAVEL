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
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action'); // CREATE, UPDATE, DELETE, VIEW, LOGIN, LOGOUT
            $table->string('resource'); // prestamos, clientes, cuotas, etc.
            $table->string('resource_id')->nullable(); // ID del recurso afectado
            $table->string('description'); // Descripción legible de la acción
            $table->json('old_values')->nullable(); // Valores anteriores (para updates)
            $table->json('new_values')->nullable(); // Valores nuevos
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->string('url');
            $table->string('method'); // GET, POST, PUT, DELETE
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'resource']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};
