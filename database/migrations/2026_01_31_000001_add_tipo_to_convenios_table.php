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
        Schema::table('convenios', function (Blueprint $table) {
            // Agregar campo tipo después de prestamo_id
            $table->string('tipo', 20)->default('cuotas')->after('prestamo_id');

            // Hacer nullable campos que no aplican para tipo "flexible"
            $table->integer('numero_cuotas')->nullable()->change();
            $table->decimal('valor_cuota', 10, 2)->nullable()->change();
            $table->date('fecha_inicio')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('convenios', function (Blueprint $table) {
            $table->dropColumn('tipo');

            // Revertir los nullable (nota: puede causar problemas si hay datos con null)
            // Descomentar solo si es seguro
            // $table->integer('numero_cuotas')->nullable(false)->change();
            // $table->decimal('valor_cuota', 10, 2)->nullable(false)->change();
            // $table->date('fecha_inicio')->nullable(false)->change();
        });
    }
};
