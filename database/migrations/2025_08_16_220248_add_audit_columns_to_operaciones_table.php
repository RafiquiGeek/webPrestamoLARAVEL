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
            // Campos para auditoría de ediciones
            $table->text('justificacion_edicion')->nullable();
            $table->unsignedBigInteger('editado_por')->nullable();
            $table->timestamp('editado_en')->nullable();

            // Campos para auditoría de anulaciones
            $table->text('justificacion_anulacion')->nullable();
            $table->unsignedBigInteger('anulado_por')->nullable();
            $table->timestamp('anulado_en')->nullable();

            // Estado para controlar pagos anulados
            $table->string('estado')->default('activo');

            // Claves foráneas
            $table->foreign('editado_por')->references('id')->on('users')->onDelete('set null');
            $table->foreign('anulado_por')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->dropForeign(['editado_por']);
            $table->dropForeign(['anulado_por']);

            $table->dropColumn([
                'justificacion_edicion',
                'editado_por',
                'editado_en',
                'justificacion_anulacion',
                'anulado_por',
                'anulado_en',
                'estado',
            ]);
        });
    }
};
