<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Usar SQL raw para modificar el ENUM
        DB::statement("ALTER TABLE tareas MODIFY COLUMN estado ENUM('pendiente', 'en_progreso', 'en_revision', 'pausado', 'completado', 'cancelado') NOT NULL DEFAULT 'pendiente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir el enum al estado anterior
        DB::statement("ALTER TABLE tareas MODIFY COLUMN estado ENUM('pendiente', 'en_progreso', 'pausado', 'completado', 'cancelado') NOT NULL DEFAULT 'pendiente'");
    }
};
