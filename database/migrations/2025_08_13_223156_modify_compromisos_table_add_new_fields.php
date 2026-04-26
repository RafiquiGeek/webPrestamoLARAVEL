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
            // Campos para el nuevo sistema de compromisos
            $table->decimal('monto_original', 10, 2)->nullable()->after('monto')->comment('Monto inicial del compromiso');
            $table->decimal('monto_pendiente', 10, 2)->nullable()->after('monto_original')->comment('Saldo pendiente por cobrar');
            $table->date('fecha_original')->nullable()->after('fecha_compromiso_pago')->comment('Fecha inicial del compromiso');
            $table->text('motivo_postergacion')->nullable()->after('comentario')->comment('Razón de postergación');
            $table->integer('veces_postergado')->default(0)->after('motivo_postergacion')->comment('Contador de postergaciones');
            $table->bigInteger('compromiso_padre_id')->nullable()->after('veces_postergado')->comment('ID del compromiso original');

            // Actualizar constantes de estado (mantener compatibilidad)
            // 0: PENDIENTE, 1: COMPLETADO/PAGADO, 2: POSTERGADO
            $table->comment('Estados: 0=PENDIENTE, 1=PAGADO, 2=POSTERGADO');

            // Índices
            $table->foreign('compromiso_padre_id')->references('id')->on('compromisos')->onDelete('set null');
            $table->index('compromiso_padre_id');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compromisos', function (Blueprint $table) {
            $table->dropForeign(['compromiso_padre_id']);
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
};
