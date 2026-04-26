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
            // Nuevos campos para gestiones mejoradas
            $table->enum('tipo_gestion', ['presencial', 'virtual'])->default('presencial')->after('observaciones')->comment('Tipo de gestión realizada');
            $table->decimal('monto_cobrado', 10, 2)->nullable()->after('tipo_gestion')->comment('Monto cobrado en esta gestión');
            $table->boolean('tiene_pago')->default(false)->after('monto_cobrado')->comment('Indica si se realizó pago en esta gestión');
            $table->boolean('tiene_adjuntos')->default(false)->after('tiene_pago')->comment('Indica si tiene fotos/documentos adjuntos');

            // Índices para optimizar consultas
            $table->index('tipo_gestion');
            $table->index('tiene_pago');
            $table->index(['prestamo_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gestiones', function (Blueprint $table) {
            $table->dropIndex(['prestamo_id', 'fecha']);
            $table->dropIndex(['tiene_pago']);
            $table->dropIndex(['tipo_gestion']);
            $table->dropColumn([
                'tipo_gestion',
                'monto_cobrado',
                'tiene_pago',
                'tiene_adjuntos',
            ]);
        });
    }
};
