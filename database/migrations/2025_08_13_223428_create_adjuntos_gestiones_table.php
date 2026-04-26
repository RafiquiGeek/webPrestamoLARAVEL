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
        Schema::create('adjuntos_gestiones', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('gestion_id')->comment('ID de la gestión a la que pertenece el adjunto');
            $table->string('nombre_archivo')->comment('Nombre original del archivo');
            $table->string('nombre_archivo_sistema')->comment('Nombre del archivo en el sistema');
            $table->string('ruta_archivo')->comment('Ruta completa del archivo');
            $table->enum('tipo_archivo', ['foto', 'documento', 'audio', 'video'])->comment('Tipo de archivo adjunto');
            $table->string('extension', 10)->comment('Extensión del archivo');
            $table->unsignedBigInteger('tamaño')->comment('Tamaño del archivo en bytes');
            $table->text('descripcion')->nullable()->comment('Descripción del adjunto');
            $table->unsignedBigInteger('subido_por')->comment('ID del usuario que subió el archivo');
            $table->timestamps();

            // Relaciones
            $table->foreign('gestion_id')->references('id')->on('gestiones')->onDelete('cascade');
            $table->foreign('subido_por')->references('id')->on('users')->onDelete('cascade');

            // Índices
            $table->index('gestion_id');
            $table->index('tipo_archivo');
            $table->index('subido_por');
            $table->index(['gestion_id', 'tipo_archivo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adjuntos_gestiones');
    }
};
