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
            // Campos para validación de operaciones
            if (! Schema::hasColumn('operaciones', 'estado_validacion')) {
                $table->string('estado_validacion', 20)->nullable()->after('estado')
                    ->comment('Estado de validación: por_validar, validado, observado');
            }

            if (! Schema::hasColumn('operaciones', 'observaciones_validacion')) {
                $table->text('observaciones_validacion')->nullable()->after('estado_validacion')
                    ->comment('Observaciones del proceso de validación');
            }

            if (! Schema::hasColumn('operaciones', 'validado_por')) {
                $table->unsignedInteger('validado_por')->nullable()->after('observaciones_validacion')
                    ->comment('ID del usuario que validó la operación');
            }

            if (! Schema::hasColumn('operaciones', 'validado_en')) {
                $table->timestamp('validado_en')->nullable()->after('validado_por')
                    ->comment('Fecha y hora de validación');
            }

            if (! Schema::hasColumn('operaciones', 'observado_por')) {
                $table->unsignedInteger('observado_por')->nullable()->after('validado_en')
                    ->comment('ID del usuario que observó la operación');
            }

            if (! Schema::hasColumn('operaciones', 'observado_en')) {
                $table->timestamp('observado_en')->nullable()->after('observado_por')
                    ->comment('Fecha y hora de observación');
            }

            // Índices para mejorar el rendimiento
            $table->index('estado_validacion', 'idx_operaciones_estado_validacion');
            $table->index('validado_por', 'idx_operaciones_validado_por');
            $table->index('observado_por', 'idx_operaciones_observado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            // Eliminar índices primero
            if (Schema::hasColumn('operaciones', 'estado_validacion')) {
                $table->dropIndex('idx_operaciones_estado_validacion');
                $table->dropIndex('idx_operaciones_validado_por');
                $table->dropIndex('idx_operaciones_observado_por');
            }

            // Eliminar columnas
            $table->dropColumn([
                'estado_validacion',
                'observaciones_validacion',
                'validado_por',
                'validado_en',
                'observado_por',
                'observado_en',
            ]);
        });
    }
};
