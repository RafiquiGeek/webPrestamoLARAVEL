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
        Schema::create('meta_comisiones', function (Blueprint $table) {
            $table->id();
            $table->decimal('porcentaje_minimo', 8, 2);
            $table->decimal('porcentaje_maximo', 8, 2)->nullable();
            $table->decimal('monto_comision', 12, 2);
            $table->string('nivel'); // base, bronce, plata, oro
            $table->boolean('estado')->default(true);
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_comisiones');
    }
};
