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
        // Modificar el ENUM para agregar 'exonerado'
        DB::statement("ALTER TABLE `fondo_provisional` MODIFY COLUMN `estado` ENUM('pendiente', 'entregado', 'rendido', 'exonerado') DEFAULT 'entregado' COMMENT 'Estado del fondo provisional'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir el ENUM a los valores originales
        DB::statement("ALTER TABLE `fondo_provisional` MODIFY COLUMN `estado` ENUM('pendiente', 'entregado', 'rendido') DEFAULT 'entregado' COMMENT 'Estado del fondo provisional'");
    }
};
