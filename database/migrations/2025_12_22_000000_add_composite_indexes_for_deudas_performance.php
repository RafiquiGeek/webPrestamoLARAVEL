<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Helper: crear índice solo si no existe
     */
    private function addIndexIfNotExists(string $table, array $columns, string $indexName): void
    {
        $exists = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        if (empty($exists)) {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Índices compuestos para tabla cuotas
        $this->addIndexIfNotExists('cuotas', ['prestamo_id', 'estado', 'fecha_pago'], 'idx_cuotas_prestamo_estado_fecha');
        $this->addIndexIfNotExists('cuotas', ['fecha_pago', 'estado'], 'idx_cuotas_fecha_estado');

        // Índices para tabla mora_cuota
        $this->addIndexIfNotExists('mora_cuota', ['cuota_id', 'estado'], 'idx_mora_cuota_estado');
        $this->addIndexIfNotExists('mora_cuota', ['estado', 'dias_mora'], 'idx_mora_estado_dias');

        // Índices para carteras
        $this->addIndexIfNotExists('carteras_jcc', ['prestamo_id', 'estado', 'jcc_id'], 'idx_cartera_jcc_prestamo_estado');
        $this->addIndexIfNotExists('carteras_jcc', ['jcc_id', 'estado'], 'idx_cartera_jcc_lookup');

        $this->addIndexIfNotExists('carteras_asesor', ['prestamo_id', 'estado', 'asesor_id'], 'idx_cartera_asesor_prestamo_estado');
        $this->addIndexIfNotExists('carteras_asesor', ['asesor_id', 'estado'], 'idx_cartera_asesor_lookup');

        $this->addIndexIfNotExists('carteras_analista', ['prestamo_id', 'estado', 'analista_id'], 'idx_cartera_analista_prestamo_estado');
        $this->addIndexIfNotExists('carteras_analista', ['analista_id', 'estado'], 'idx_cartera_analista_lookup');

        // Índices para direcciones
        $this->addIndexIfNotExists('direcciones', ['persona_id', 'sucursal_id'], 'idx_direcciones_persona_sucursal');

        // Índice para zona_sucursal
        if (Schema::hasTable('zona_sucursal')) {
            $this->addIndexIfNotExists('zona_sucursal', ['sucursal_id', 'zona_id'], 'idx_sucursal_zona_lookup');
        }

        // Índices para gestiones y compromisos
        if (Schema::hasTable('gestiones')) {
            $this->addIndexIfNotExists('gestiones', ['prestamo_id', 'fecha'], 'idx_gestiones_prestamo_fecha');
        }

        if (Schema::hasTable('compromisos')) {
            $this->addIndexIfNotExists('compromisos', ['prestamo_id', 'estado', 'fecha_compromiso_pago'], 'idx_compromisos_prestamo_estado_fecha');
        }

        // Índices para convenios
        $this->addIndexIfNotExists('convenios', ['prestamo_id', 'estado'], 'idx_convenios_prestamo_estado');

        if (Schema::hasTable('cuotas_convenio')) {
            $this->addIndexIfNotExists('cuotas_convenio', ['convenio_id', 'estado', 'fecha_vencimiento'], 'idx_cuota_convenio_lookup');
        }

        // Índice para prestamos.estado (usado en JOIN)
        $this->addIndexIfNotExists('prestamos', ['estado'], 'idx_prestamos_estado');

        // Índice FULLTEXT para búsqueda de personas
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $exists = DB::select("SHOW INDEX FROM `personas` WHERE Key_name = ?", ['idx_personas_search']);
            if (empty($exists)) {
                DB::statement('CREATE FULLTEXT INDEX idx_personas_search ON personas(nombres, ape_pat, ape_mat)');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = [
            'cuotas' => ['idx_cuotas_prestamo_estado_fecha', 'idx_cuotas_fecha_estado'],
            'mora_cuota' => ['idx_mora_cuota_estado', 'idx_mora_estado_dias'],
            'carteras_jcc' => ['idx_cartera_jcc_prestamo_estado', 'idx_cartera_jcc_lookup'],
            'carteras_asesor' => ['idx_cartera_asesor_prestamo_estado', 'idx_cartera_asesor_lookup'],
            'carteras_analista' => ['idx_cartera_analista_prestamo_estado', 'idx_cartera_analista_lookup'],
            'direcciones' => ['idx_direcciones_persona_sucursal'],
            'gestiones' => ['idx_gestiones_prestamo_fecha'],
            'compromisos' => ['idx_compromisos_prestamo_estado_fecha'],
            'convenios' => ['idx_convenios_prestamo_estado'],
            'cuotas_convenio' => ['idx_cuota_convenio_lookup'],
            'prestamos' => ['idx_prestamos_estado'],
        ];

        foreach ($indexes as $table => $indexNames) {
            if (!Schema::hasTable($table)) continue;
            foreach ($indexNames as $indexName) {
                $exists = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
                if (!empty($exists)) {
                    Schema::table($table, function (Blueprint $tbl) use ($indexName) {
                        $tbl->dropIndex($indexName);
                    });
                }
            }
        }

        if (Schema::hasTable('zona_sucursal')) {
            $exists = DB::select("SHOW INDEX FROM `zona_sucursal` WHERE Key_name = ?", ['idx_sucursal_zona_lookup']);
            if (!empty($exists)) {
                Schema::table('zona_sucursal', function (Blueprint $table) {
                    $table->dropIndex('idx_sucursal_zona_lookup');
                });
            }
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $exists = DB::select("SHOW INDEX FROM `personas` WHERE Key_name = ?", ['idx_personas_search']);
            if (!empty($exists)) {
                DB::statement('DROP INDEX idx_personas_search ON personas');
            }
        }
    }
};
