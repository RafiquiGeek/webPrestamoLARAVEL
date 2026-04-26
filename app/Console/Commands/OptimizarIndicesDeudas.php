<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class OptimizarIndicesDeudas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deudas:optimizar-indices
                            {--dry-run : Mostrar qué índices se crearían sin ejecutarlos}
                            {--force : Forzar recreación de índices existentes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimiza los índices de la base de datos para el módulo de deudas';

    /**
     * Lista de índices a crear
     */
    protected $indices = [
        'cuotas' => [
            ['name' => 'idx_cuotas_prestamo_estado_fecha', 'columns' => ['prestamo_id', 'estado', 'fecha_pago']],
            ['name' => 'idx_cuotas_fecha_estado', 'columns' => ['fecha_pago', 'estado']],
        ],
        'mora_cuota' => [
            ['name' => 'idx_mora_cuota_estado', 'columns' => ['cuota_id', 'estado']],
            ['name' => 'idx_mora_estado_dias', 'columns' => ['estado', 'dias_mora']],
        ],
        'cartera_jcc' => [
            ['name' => 'idx_cartera_jcc_prestamo_estado', 'columns' => ['prestamo_id', 'estado', 'jcc_id']],
            ['name' => 'idx_cartera_jcc_lookup', 'columns' => ['jcc_id', 'estado']],
        ],
        'cartera_asesor' => [
            ['name' => 'idx_cartera_asesor_prestamo_estado', 'columns' => ['prestamo_id', 'estado', 'asesor_id']],
            ['name' => 'idx_cartera_asesor_lookup', 'columns' => ['asesor_id', 'estado']],
        ],
        'cartera_analista' => [
            ['name' => 'idx_cartera_analista_prestamo_estado', 'columns' => ['prestamo_id', 'estado', 'analista_id']],
            ['name' => 'idx_cartera_analista_lookup', 'columns' => ['analista_id', 'estado']],
        ],
        'direcciones' => [
            ['name' => 'idx_direcciones_persona_sucursal', 'columns' => ['persona_id', 'sucursal_id']],
        ],
        'gestiones' => [
            ['name' => 'idx_gestiones_prestamo_fecha', 'columns' => ['prestamo_id', 'fecha']],
        ],
        'compromisos' => [
            ['name' => 'idx_compromisos_prestamo_estado_fecha', 'columns' => ['prestamo_id', 'estado', 'fecha_compromiso_pago']],
        ],
        'convenios' => [
            ['name' => 'idx_convenios_prestamo_estado', 'columns' => ['prestamo_id', 'estado']],
        ],
        'cuota_convenio_models' => [
            ['name' => 'idx_cuota_convenio_lookup', 'columns' => ['convenio_id', 'estado', 'fecha_vencimiento']],
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando optimización de índices para el módulo de deudas...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('⚠️  Modo DRY RUN: No se ejecutarán cambios en la base de datos');
            $this->newLine();
        }

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($this->indices as $table => $indices) {
            $this->line("📊 Procesando tabla: <fg=cyan>{$table}</>");

            // Verificar si la tabla existe
            if (!$this->tableExists($table)) {
                $this->warn("   ⚠️  Tabla '{$table}' no existe. Omitiendo...");
                $this->newLine();
                continue;
            }

            foreach ($indices as $index) {
                $indexName = $index['name'];
                $columns = $index['columns'];

                if ($this->indexExists($table, $indexName)) {
                    if ($force && !$dryRun) {
                        $this->line("   🔄 Índice '{$indexName}' ya existe. Recreando...");
                        $this->dropIndex($table, $indexName);
                        if ($this->createIndex($table, $indexName, $columns, $dryRun)) {
                            $created++;
                            $this->info("   ✅ Índice '{$indexName}' recreado");
                        } else {
                            $errors++;
                            $this->error("   ❌ Error al recrear índice '{$indexName}'");
                        }
                    } else {
                        $skipped++;
                        $this->comment("   ⏭️  Índice '{$indexName}' ya existe. Omitiendo...");
                    }
                } else {
                    if ($this->createIndex($table, $indexName, $columns, $dryRun)) {
                        $created++;
                        $this->info("   ✅ Índice '{$indexName}' " . ($dryRun ? 'se crearía' : 'creado'));
                    } else {
                        $errors++;
                        $this->error("   ❌ Error al crear índice '{$indexName}'");
                    }
                }
            }

            $this->newLine();
        }

        // Crear índice FULLTEXT en personas (opcional)
        if ($this->tableExists('personas')) {
            $this->line("📊 Procesando índice FULLTEXT en tabla: <fg=cyan>personas</>");

            $fulltextExists = $this->hasFulltextIndex('personas');

            if (!$fulltextExists) {
                if (!$dryRun) {
                    try {
                        DB::statement('CREATE FULLTEXT INDEX idx_personas_search ON personas(nombres, ape_pat, ape_mat)');
                        $created++;
                        $this->info("   ✅ Índice FULLTEXT 'idx_personas_search' creado");
                    } catch (\Exception $e) {
                        $errors++;
                        $this->error("   ❌ Error al crear índice FULLTEXT: " . $e->getMessage());
                    }
                } else {
                    $this->info("   ✅ Índice FULLTEXT 'idx_personas_search' se crearía");
                }
            } else {
                $skipped++;
                $this->comment("   ⏭️  Índice FULLTEXT ya existe. Omitiendo...");
            }

            $this->newLine();
        }

        // Resumen
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 RESUMEN DE OPTIMIZACIÓN');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line("✅ Índices creados: <fg=green>{$created}</>");
        $this->line("⏭️  Índices omitidos: <fg=yellow>{$skipped}</>");
        $this->line("❌ Errores: <fg=red>{$errors}</>");
        $this->newLine();

        if (!$dryRun && $created > 0) {
            $this->info('🎉 Optimización completada exitosamente!');
            $this->newLine();
            $this->comment('💡 Recomendaciones:');
            $this->line('   1. Ejecuta: php artisan cache:clear');
            $this->line('   2. Ejecuta: php artisan view:clear');
            $this->line('   3. Prueba la vista /admin/deudas');
            $this->line('   4. Revisa los logs para ver mejoras de rendimiento');
        } elseif ($dryRun) {
            $this->info('Para aplicar los cambios, ejecuta el comando sin --dry-run');
        }

        // Mostrar estadísticas de tablas
        if (!$dryRun && $created > 0) {
            $this->newLine();
            if ($this->confirm('¿Deseas ver estadísticas de las tablas optimizadas?', false)) {
                $this->mostrarEstadisticas();
            }
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Verifica si una tabla existe
     */
    protected function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verifica si un índice existe
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verifica si existe un índice FULLTEXT en la tabla
     */
    protected function hasFulltextIndex(string $table): bool
    {
        try {
            $indexes = DB::select(
                "SELECT * FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = ?
                AND index_type = 'FULLTEXT'",
                [$table]
            );
            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Crea un índice
     */
    protected function createIndex(string $table, string $indexName, array $columns, bool $dryRun = false): bool
    {
        if ($dryRun) {
            return true;
        }

        try {
            $columnList = implode(', ', $columns);
            DB::statement("CREATE INDEX {$indexName} ON {$table} ({$columnList})");
            return true;
        } catch (\Exception $e) {
            $this->error("      Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un índice
     */
    protected function dropIndex(string $table, string $indexName): bool
    {
        try {
            DB::statement("DROP INDEX {$indexName} ON {$table}");
            return true;
        } catch (\Exception $e) {
            $this->error("      Error al eliminar índice: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Muestra estadísticas de las tablas
     */
    protected function mostrarEstadisticas(): void
    {
        $this->newLine();
        $this->info('📊 ESTADÍSTICAS DE TABLAS OPTIMIZADAS');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $tables = array_keys($this->indices);
        $tables[] = 'personas';

        $stats = DB::select("
            SELECT
                table_name AS 'Tabla',
                table_rows AS 'Filas',
                ROUND(data_length / 1024 / 1024, 2) AS 'Datos_MB',
                ROUND(index_length / 1024 / 1024, 2) AS 'Indices_MB',
                ROUND((data_length + index_length) / 1024 / 1024, 2) AS 'Total_MB'
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name IN ('" . implode("','", $tables) . "')
            ORDER BY table_rows DESC
        ");

        $this->table(
            ['Tabla', 'Filas Aprox.', 'Datos (MB)', 'Índices (MB)', 'Total (MB)'],
            array_map(function ($row) {
                return (array) $row;
            }, $stats)
        );
    }
}
