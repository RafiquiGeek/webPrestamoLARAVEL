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
            // Verificar si la columna no existe antes de crearla
            if (! Schema::hasColumn('gestiones', 'compromiso_seguimiento_id')) {
                $table->unsignedBigInteger('compromiso_seguimiento_id')->nullable()->after('compromiso_id')
                    ->comment('ID del compromiso al que da seguimiento esta gestión');

                // Agregar foreign key si la tabla compromisos existe
                if (Schema::hasTable('compromisos')) {
                    $table->foreign('compromiso_seguimiento_id')->references('id')->on('compromisos')->onDelete('set null');
                }

                // Índice para optimizar consultas
                $table->index('compromiso_seguimiento_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gestiones', function (Blueprint $table) {
            if (Schema::hasColumn('gestiones', 'compromiso_seguimiento_id')) {
                // Eliminar foreign key primero
                $table->dropForeign(['compromiso_seguimiento_id']);
                $table->dropIndex(['compromiso_seguimiento_id']);
                $table->dropColumn('compromiso_seguimiento_id');
            }
        });
    }
};
