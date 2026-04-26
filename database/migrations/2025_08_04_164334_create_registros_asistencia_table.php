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
        Schema::create('registros_asistencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('asignacion_id')->constrained('asignaciones_area_empleado')->onDelete('cascade');
            $table->date('fecha');
            $table->time('hora_entrada')->nullable();
            $table->time('hora_salida')->nullable();
            $table->time('inicio_refrigerio')->nullable();
            $table->time('fin_refrigerio')->nullable();
            $table->enum('estado_entrada', ['puntual', 'tardanza', 'falta'])->default('falta');
            $table->enum('estado_salida', ['puntual', 'temprano', 'tardio', 'pendiente'])->default('pendiente');
            $table->integer('minutos_tardanza')->default(0);
            $table->integer('minutos_refrigerio_extra')->default(0);
            $table->decimal('latitud_entrada', 10, 8)->nullable();
            $table->decimal('longitud_entrada', 11, 8)->nullable();
            $table->decimal('latitud_salida', 10, 8)->nullable();
            $table->decimal('longitud_salida', 11, 8)->nullable();
            $table->string('ip_entrada', 45)->nullable();
            $table->string('ip_salida', 45)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Evitar registros duplicados por día
            $table->unique(['user_id', 'fecha'], 'unique_daily_attendance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_asistencia');
    }
};
