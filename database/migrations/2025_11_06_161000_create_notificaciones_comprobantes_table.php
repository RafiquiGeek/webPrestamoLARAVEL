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
        Schema::create('notificaciones_comprobantes', function (Blueprint $table) {
            $table->id();
            $table->integer('comprobante_id')->unsigned();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('tipo', ['exito', 'fallo', 'advertencia', 'info'])->default('info');
            $table->string('titulo');
            $table->text('mensaje');
            $table->json('datos_adicionales')->nullable();
            $table->boolean('leido')->default(false);
            $table->timestamp('leido_at')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['user_id', 'leido']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones_comprobantes');
    }
};
