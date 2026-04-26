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
        Schema::table('operaciones', function (Blueprint $table) {
            // Agregar columna estado si no existe
            if (! Schema::hasColumn('operaciones', 'estado')) {
                $table->string('estado', 20)->default('completado')->after('estado_rendicion')
                    ->comment('Estado de la operación: completado, anulado, pendiente');

                $table->text('justificacion_anulacion')->nullable()->after('estado')
                    ->comment('Justificación cuando se anula la operación');

                $table->unsignedInteger('anulado_por')->nullable()->after('justificacion_anulacion')
                    ->comment('ID del usuario que anuló la operación');

                $table->timestamp('anulado_en')->nullable()->after('anulado_por')
                    ->comment('Fecha y hora de anulación');

                // Agregar índices
                $table->index('estado', 'idx_operaciones_estado');
                $table->index('anulado_por', 'idx_operaciones_anulado_por');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            if (Schema::hasColumn('operaciones', 'estado')) {
                $table->dropIndex('idx_operaciones_estado');
                $table->dropIndex('idx_operaciones_anulado_por');
                $table->dropColumn([
                    'estado',
                    'justificacion_anulacion',
                    'anulado_por',
                    'anulado_en',
                ]);
            }
        });
    }
};
