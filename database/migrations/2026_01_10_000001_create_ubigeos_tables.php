<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Crear tablas de ubigeos
     */
    public function up(): void
    {
        // Departamentos
        Schema::create('departments', function (Blueprint $table) {
            $table->string('id', 2)->primary();
            $table->string('name', 100);
            $table->timestamps();
        });

        // Provincias
        Schema::create('provinces', function (Blueprint $table) {
            $table->string('id', 4)->primary();
            $table->string('name', 100);
            $table->string('department_id', 2);
            $table->timestamps();

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('cascade');
        });

        // Distritos
        Schema::create('districts', function (Blueprint $table) {
            $table->string('id', 6)->primary();
            $table->string('name', 100);
            $table->string('province_id', 4);
            $table->string('department_id', 2);
            $table->timestamps();

            $table->foreign('province_id')
                ->references('id')
                ->on('provinces')
                ->onDelete('cascade');

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('districts');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('departments');
    }
};
