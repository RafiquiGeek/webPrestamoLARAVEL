<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class CreateSecureBackup extends Command
{
    protected $signature = 'db:secure-backup 
                           {--connection= : Conexión específica para respaldar}
                           {--encrypt : Encriptar el archivo de respaldo}
                           {--compress : Comprimir el archivo de respaldo}
                           {--retention=30 : Días de retención de respaldos}';

    protected $description = 'Crea respaldos seguros y encriptados de las bases de datos';

    public function handle()
    {
        $connection = $this->option('connection') ?: config('database.default');
        $encrypt = $this->option('encrypt');
        $compress = $this->option('compress');
        $retention = (int) $this->option('retention');

        $this->info("🔒 Iniciando respaldo seguro de la conexión: {$connection}");

        try {
            $backupPath = $this->createBackup($connection, $encrypt, $compress);

            if ($backupPath) {
                $this->info("✅ Respaldo creado exitosamente: {$backupPath}");

                // Limpiar respaldos antiguos
                $this->cleanOldBackups($retention);

                // Verificar integridad del respaldo
                $this->verifyBackupIntegrity($backupPath);

                return 0;
            } else {
                $this->error('❌ Error creando el respaldo');

                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());
            Log::channel('database-sync')->error('Error en respaldo seguro', [
                'connection' => $connection,
                'error' => $e->getMessage(),
            ]);

            return 1;
        }
    }

    private function createBackup(string $connection, bool $encrypt, bool $compress): ?string
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "secure_backup_{$connection}_{$timestamp}";

        if ($compress) {
            $filename .= '.zip';
        } else {
            $filename .= '.sql';
        }

        $backupDir = storage_path('app/respaldos/seguros');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $backupPath = $backupDir.'/'.$filename;

        // Obtener configuración de la conexión
        $config = config("database.connections.{$connection}");

        if (! $config) {
            throw new \Exception("Configuración de conexión {$connection} no encontrada");
        }

        // Crear respaldo SQL
        $sqlContent = $this->generateSQLBackup($connection);

        if ($encrypt) {
            $sqlContent = $this->encryptContent($sqlContent);
        }

        if ($compress) {
            return $this->createCompressedBackup($sqlContent, $backupPath, $encrypt);
        } else {
            file_put_contents($backupPath, $sqlContent);

            return $backupPath;
        }
    }

    private function generateSQLBackup(string $connection): string
    {
        $config = config("database.connections.{$connection}");
        $tables = config('database.sync_tables', []);

        $sqlContent = "-- Respaldo Seguro de Base de Datos\n";
        $sqlContent .= "-- Conexión: {$connection}\n";
        $sqlContent .= '-- Fecha: '.Carbon::now()->toDateTimeString()."\n";
        $sqlContent .= "-- Generado por: Sistema de Respaldo Seguro\n\n";

        $sqlContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            try {
                $this->info("📄 Respaldando tabla: {$table}");

                // Estructura de la tabla
                $createTable = DB::connection($connection)
                    ->select("SHOW CREATE TABLE `{$table}`")[0];

                $sqlContent .= "-- Estructura de tabla {$table}\n";
                $sqlContent .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sqlContent .= $createTable->{'Create Table'}.";\n\n";

                // Datos de la tabla
                $records = DB::connection($connection)->table($table)->get();

                if ($records->count() > 0) {
                    $sqlContent .= "-- Datos de tabla {$table}\n";
                    $sqlContent .= "INSERT INTO `{$table}` VALUES\n";

                    $values = [];
                    foreach ($records as $record) {
                        $recordArray = (array) $record;
                        $escapedValues = array_map(function ($value) use ($connection) {
                            if ($value === null) {
                                return 'NULL';
                            }

                            return "'".DB::connection($connection)->getPdo()->quote($value)."'";
                        }, $recordArray);

                        $values[] = '('.implode(', ', $escapedValues).')';
                    }

                    $sqlContent .= implode(",\n", $values).";\n\n";
                }

            } catch (\Exception $e) {
                $this->warn("⚠️  Error respaldando tabla {$table}: ".$e->getMessage());

                continue;
            }
        }

        $sqlContent .= "SET FOREIGN_KEY_CHECKS=1;\n";
        $sqlContent .= "-- Fin del respaldo\n";

        return $sqlContent;
    }

    private function encryptContent(string $content): string
    {
        $key = config('app.key');

        if (! $key) {
            throw new \Exception('No se puede encriptar: APP_KEY no configurada');
        }

        // Generar IV aleatorio
        $iv = random_bytes(16);

        // Encriptar contenido
        $encrypted = openssl_encrypt($content, 'AES-256-CBC', $key, 0, $iv);

        if ($encrypted === false) {
            throw new \Exception('Error encriptando el respaldo');
        }

        // Combinar IV y contenido encriptado
        return base64_encode($iv.$encrypted);
    }

    private function createCompressedBackup(string $content, string $backupPath, bool $isEncrypted): string
    {
        $zip = new ZipArchive;

        if ($zip->open($backupPath, ZipArchive::CREATE) !== true) {
            throw new \Exception("No se puede crear archivo ZIP: {$backupPath}");
        }

        $filename = $isEncrypted ? 'backup.sql.encrypted' : 'backup.sql';
        $zip->addFromString($filename, $content);

        // Agregar archivo de metadatos
        $metadata = [
            'created_at' => Carbon::now()->toISOString(),
            'connection' => $this->option('connection'),
            'encrypted' => $isEncrypted,
            'tables_count' => count(config('database.sync_tables', [])),
            'system' => 'Laravel Database Sync',
            'version' => '1.0',
        ];

        $zip->addFromString('metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));

        $zip->close();

        return $backupPath;
    }

    private function cleanOldBackups(int $retentionDays): void
    {
        $this->info("🧹 Limpiando respaldos antiguos (>{$retentionDays} días)...");

        $backupDir = storage_path('app/respaldos/seguros');

        if (! is_dir($backupDir)) {
            return;
        }

        $cutoffDate = Carbon::now()->subDays($retentionDays);
        $deletedCount = 0;

        $files = glob($backupDir.'/secure_backup_*');

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffDate->timestamp) {
                unlink($file);
                $deletedCount++;
                $this->info('🗑️  Eliminado: '.basename($file));
            }
        }

        if ($deletedCount > 0) {
            $this->info("✅ Se eliminaron {$deletedCount} respaldos antiguos");
        } else {
            $this->info('ℹ️  No hay respaldos antiguos para eliminar');
        }

        Log::channel('database-sync')->info('Limpieza de respaldos completada', [
            'retention_days' => $retentionDays,
            'deleted_count' => $deletedCount,
        ]);
    }

    private function verifyBackupIntegrity(string $backupPath): void
    {
        $this->info('🔍 Verificando integridad del respaldo...');

        if (! file_exists($backupPath)) {
            throw new \Exception("Archivo de respaldo no encontrado: {$backupPath}");
        }

        $fileSize = filesize($backupPath);

        if ($fileSize === 0) {
            throw new \Exception('El archivo de respaldo está vacío');
        }

        // Verificar que es un archivo válido
        $extension = pathinfo($backupPath, PATHINFO_EXTENSION);

        if ($extension === 'zip') {
            $zip = new ZipArchive;
            if ($zip->open($backupPath) !== true) {
                throw new \Exception('El archivo ZIP está corrupto');
            }
            $zip->close();
        }

        $this->info('✅ Integridad del respaldo verificada');
        $this->info('📊 Tamaño del archivo: '.$this->formatBytes($fileSize));

        Log::channel('database-sync')->info('Respaldo verificado exitosamente', [
            'backup_path' => $backupPath,
            'file_size' => $fileSize,
            'extension' => $extension,
        ]);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2).' '.$units[$unitIndex];
    }
}
