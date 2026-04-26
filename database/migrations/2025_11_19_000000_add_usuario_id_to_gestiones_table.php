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
        Schema::table('gestiones', function (Blueprint $table) {
            if (!Schema::hasColumn('gestiones', 'usuario_id')) {
                $table->unsignedBigInteger('usuario_id')->nullable()->after('estado_id')
                    ->comment('ID del usuario que realizó la gestión');

                // Agregar foreign key si la tabla users existe
                if (Schema::hasTable('users')) {
                    $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
                }

                // Índice para optimizar consultas
                $table->index('usuario_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gestiones', function (Blueprint $table) {
            if (Schema::hasColumn('gestiones', 'usuario_id')) {
                // Eliminar foreign key primero
                $table->dropForeign(['usuario_id']);
                $table->dropIndex(['usuario_id']);
                $table->dropColumn('usuario_id');
            }
        });
    }
};