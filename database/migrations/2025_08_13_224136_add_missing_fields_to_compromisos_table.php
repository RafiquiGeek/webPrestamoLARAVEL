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
        Schema::table('compromisos', function (Blueprint $table) {
            // Verificar y agregar campos solo si no existen
            if (! Schema::hasColumn('compromisos', 'monto_original')) {
                $table->decimal('monto_original', 10, 2)->nullable()->after('monto')->comment('Monto inicial del compromiso');
            }

            if (! Schema::hasColumn('compromisos', 'monto_pendiente')) {
                $table->decimal('monto_pendiente', 10, 2)->nullable()->after('monto')->comment('Saldo pendiente por cobrar');
            }

            if (! Schema::hasColumn('compromisos', 'fecha_original')) {
                $table->date('fecha_original')->nullable()->after('fecha_compromiso_pago')->comment('Fecha inicial del compromiso');
            }

            if (! Schema::hasColumn('compromisos', 'motivo_postergacion')) {
                $table->text('motivo_postergacion')->nullable()->after('comentario')->comment('Razón de postergación');
            }

            if (! Schema::hasColumn('compromisos', 'veces_postergado')) {
                $table->integer('veces_postergado')->default(0)->after('comentario')->comment('Contador de postergaciones');
            }

            if (! Schema::hasColumn('compromisos', 'compromiso_padre_id')) {
                $table->integer('compromiso_padre_id')->nullable()->after('comentario')->comment('ID del compromiso original');
            }
        });

        // Agregar índices en una segunda operación
        Schema::table('compromisos', function (Blueprint $table) {
            if (! $this->hasIndex('compromisos', 'compromiso_padre_id')) {
                $table->index('compromiso_padre_id');
            }
            if (! $this->hasIndex('compromisos', 'estado')) {
                $table->index('estado');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compromisos', function (Blueprint $table) {
            $table->dropIndex(['compromiso_padre_id']);
            $table->dropIndex(['estado']);
            $table->dropColumn([
                'monto_original',
                'monto_pendiente',
                'fecha_original',
                'motivo_postergacion',
                'veces_postergado',
                'compromiso_padre_id',
            ]);
        });
    }

    /**
     * Verifica si un índice existe en una tabla
     */
    private function hasIndex($table, $index)
    {
        $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($table);

        return isset($indexes[$table.'_'.$index.'_index']);
    }
};
