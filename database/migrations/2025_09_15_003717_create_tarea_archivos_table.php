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
        Schema::create('tarea_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_id')->constrained('tareas')->onDelete('cascade');
            $table->string('nombre_archivo');
            $table->string('ruta');
            $table->string('tipo_mime')->nullable();
            $table->integer('tamaño')->nullable();
            $table->enum('tipo', ['imagen', 'documento', 'otro'])->default('otro');
            $table->foreignId('subido_por')->constrained('users');
            $table->timestamps();

            $table->index('tarea_id');
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarea_archivos');
    }
};
