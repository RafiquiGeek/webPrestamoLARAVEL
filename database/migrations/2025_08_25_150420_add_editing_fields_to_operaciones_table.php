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
            $table->text('justificacion_edicion')->nullable()->after('voucher_path');
            $table->unsignedInteger('editado_por')->nullable()->after('justificacion_edicion');
            $table->timestamp('editado_en')->nullable()->after('editado_por');

            $table->index('editado_por', 'idx_operaciones_editado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operaciones', function (Blueprint $table) {
            $table->dropIndex('idx_operaciones_editado_por');
            $table->dropColumn(['justificacion_edicion', 'editado_por', 'editado_en']);
        });
    }
};
