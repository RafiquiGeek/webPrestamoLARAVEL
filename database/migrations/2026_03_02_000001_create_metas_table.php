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
        Schema::create('metas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asesor_id')->constrained('users')->onDelete('cascade');
            $table->integer('anio');
            $table->integer('mes');
            $table->integer('cantidad_objetivo')->default(0);
            $table->string('estado')->default('Activo'); // Activo, Cerrado, Cancelado
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['asesor_id', 'anio', 'mes']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metas');
    }
};
