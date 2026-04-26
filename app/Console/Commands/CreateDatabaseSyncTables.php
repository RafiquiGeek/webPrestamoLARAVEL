<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDatabaseSyncTables extends Command
{
    protected $signature = 'db:create-sync-tables 
                           {--connection= : Conexión específica para crear tablas}
                           {--force : Forzar creación incluso si las tablas existen}';

    protected $description = 'Crea las tablas necesarias en las bases de datos secundarias para sincronización';

    public function handle()
    {
        $connections = $this->option('connection')
            ? [$this->option('connection')]
            : config('database.sync_connections', []);

        if (empty($connections)) {
            $this->error('No hay conexiones de sincronización configuradas.');

            return 1;
        }

        $syncTables = config('database.sync_tables', []);
        $force = $this->option('force');

        foreach ($connections as $connection) {
            $this->info("Procesando conexión: {$connection}");

            try {
                $this->createJobsTable($connection);
                $this->createSyncTables($connection, $syncTables, $force);
                $this->info("✓ Tablas creadas exitosamente en {$connection}");
            } catch (Exception $e) {
                $this->error("✗ Error en {$connection}: ".$e->getMessage());
            }
        }

        return 0;
    }

    private function createJobsTable(string $connection): void
    {
        // Verificar si la tabla jobs existe
        if (! Schema::connection($connection)->hasTable('jobs')) {
            $this->info("Creando tabla 'jobs' en {$connection}...");

            Schema::connection($connection)->create('jobs', function ($table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (! Schema::connection($connection)->hasTable('failed_jobs')) {
            $this->info("Creando tabla 'failed_jobs' en {$connection}...");

            Schema::connection($connection)->create('failed_jobs', function ($table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }
    }

    private function createSyncTables(string $connection, array $tables, bool $force): void
    {
        $primaryConnection = config('database.default');

        foreach ($tables as $table) {
            try {
                // Verificar si la tabla existe en la conexión principal
                if (! Schema::connection($primaryConnection)->hasTable($table)) {
                    $this->warn("Tabla '{$table}' no existe en la base de datos principal. Saltando...");

                    continue;
                }

                // Verificar si la tabla ya existe en la conexión secundaria
                if (Schema::connection($connection)->hasTable($table) && ! $force) {
                    $this->info("Tabla '{$table}' ya existe en {$connection}. Saltando...");

                    continue;
                }

                $this->info("Creando/actualizando tabla '{$table}' en {$connection}...");

                // Obtener la estructura de la tabla de la conexión principal
                $createTableSql = $this->getCreateTableSql($primaryConnection, $table);

                if ($force && Schema::connection($connection)->hasTable($table)) {
                    DB::connection($connection)->statement("DROP TABLE IF EXISTS `{$table}`");
                }

                // Crear la tabla en la conexión secundaria
                DB::connection($connection)->statement($createTableSql);

                $this->info("✓ Tabla '{$table}' creada en {$connection}");

            } catch (Exception $e) {
                $this->error("✗ Error creando tabla '{$table}' en {$connection}: ".$e->getMessage());
            }
        }
    }

    private function getCreateTableSql(string $connection, string $table): string
    {
        $result = DB::connection($connection)->select("SHOW CREATE TABLE `{$table}`");

        if (empty($result)) {
            throw new Exception("No se pudo obtener la estructura de la tabla {$table}");
        }

        return $result[0]->{'Create Table'};
    }
}
