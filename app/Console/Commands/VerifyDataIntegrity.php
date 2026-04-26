<?php

namespace App\Console\Commands;

use App\Services\EncryptionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerifyDataIntegrity extends Command
{
    protected $signature = 'db:verify-integrity 
                           {--connection= : Conexión específica para verificar}
                           {--table= : Tabla específica para verificar}
                           {--fix : Intentar reparar inconsistencias}
                           {--limit=1000 : Límite de registros a procesar}';

    protected $description = 'Verifica la integridad de datos entre la base principal y secundarias';

    private EncryptionService $encryptionService;

    public function handle()
    {
        $this->encryptionService = app(EncryptionService::class);

        $connections = $this->option('connection')
            ? [$this->option('connection')]
            : config('database.sync_connections', []);

        $tables = $this->option('table')
            ? [$this->option('table')]
            : config('database.sync_tables', []);

        $fix = $this->option('fix');
        $limit = (int) $this->option('limit');

        $this->info('🔍 Iniciando verificación de integridad de datos...');
        $this->info('Conexiones: '.implode(', ', $connections));
        $this->info('Tablas: '.implode(', ', $tables));

        $totalErrors = 0;
        $totalFixed = 0;

        foreach ($connections as $connection) {
            foreach ($tables as $table) {
                $result = $this->verifyTableIntegrity($connection, $table, $fix, $limit);
                $totalErrors += $result['errors'];
                $totalFixed += $result['fixed'];
            }
        }

        $this->displaySummary($totalErrors, $totalFixed);

        return $totalErrors > 0 ? 1 : 0;
    }

    private function verifyTableIntegrity(string $connection, string $table, bool $fix, int $limit): array
    {
        $this->info("\n📊 Verificando tabla: {$table} en conexión: {$connection}");

        $errors = 0;
        $fixed = 0;
        $primaryConnection = config('database.default');

        try {
            // Verificar que ambas tablas existan
            if (! $this->tableExists($primaryConnection, $table) || ! $this->tableExists($connection, $table)) {
                $this->error("❌ Tabla {$table} no existe en una de las conexiones");

                return ['errors' => 1, 'fixed' => 0];
            }

            // Verificar conteos generales
            $primaryCount = DB::connection($primaryConnection)->table($table)->count();
            $secondaryCount = DB::connection($connection)->table($table)->count();

            if ($primaryCount !== $secondaryCount) {
                $this->warn("⚠️  Diferencia en conteo: Principal={$primaryCount}, Secundaria={$secondaryCount}");
                $errors++;
            }

            // Verificar registros específicos
            $primaryRecords = DB::connection($primaryConnection)
                ->table($table)
                ->orderBy('id')
                ->limit($limit)
                ->get();

            $progressBar = $this->output->createProgressBar($primaryRecords->count());
            $progressBar->start();

            foreach ($primaryRecords as $primaryRecord) {
                $secondaryRecord = DB::connection($connection)
                    ->table($table)
                    ->where('id', $primaryRecord->id)
                    ->first();

                if (! $secondaryRecord) {
                    $this->newLine();
                    $this->error("❌ Registro ID {$primaryRecord->id} no existe en secundaria");
                    $errors++;

                    if ($fix) {
                        $this->fixMissingRecord($connection, $table, $primaryRecord);
                        $fixed++;
                    }
                } else {
                    // Verificar integridad de datos financieros
                    $integrityCheck = $this->verifyRecordIntegrity($primaryRecord, $secondaryRecord, $table);
                    if (! $integrityCheck['valid']) {
                        $this->newLine();
                        $this->error("❌ Integridad comprometida en ID {$primaryRecord->id}: {$integrityCheck['reason']}");
                        $errors++;

                        if ($fix) {
                            $this->fixCorruptedRecord($connection, $table, $primaryRecord);
                            $fixed++;
                        }
                    }
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

        } catch (\Exception $e) {
            $this->error("❌ Error verificando {$table}: ".$e->getMessage());
            Log::channel('database-sync')->error('Error en verificación de integridad', [
                'table' => $table,
                'connection' => $connection,
                'error' => $e->getMessage(),
            ]);
            $errors++;
        }

        return ['errors' => $errors, 'fixed' => $fixed];
    }

    private function tableExists(string $connection, string $table): bool
    {
        try {
            return DB::connection($connection)->getSchemaBuilder()->hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function verifyRecordIntegrity($primaryRecord, $secondaryRecord, string $table): array
    {
        // Convertir a arrays para comparación
        $primaryData = (array) $primaryRecord;
        $secondaryData = (array) $secondaryRecord;

        // Verificar campos financieros críticos
        $financialFields = ['monto', 'saldo', 'capital', 'interes', 'mora', 'importe', 'total'];

        foreach ($financialFields as $field) {
            if (isset($primaryData[$field]) && isset($secondaryData[$field])) {
                if (abs(floatval($primaryData[$field]) - floatval($secondaryData[$field])) > 0.01) {
                    return [
                        'valid' => false,
                        'reason' => "Diferencia en campo financiero: {$field}",
                    ];
                }
            }
        }

        // Verificar hash de integridad si existe
        if (isset($secondaryData['financial_hash'])) {
            $calculatedHash = $this->encryptionService->createFinancialHash($primaryData);
            if ($calculatedHash !== $secondaryData['financial_hash']) {
                return [
                    'valid' => false,
                    'reason' => 'Hash de integridad financiera no coincide',
                ];
            }
        }

        // Verificar timestamps críticos
        $timestampFields = ['created_at', 'updated_at'];
        foreach ($timestampFields as $field) {
            if (isset($primaryData[$field]) && isset($secondaryData[$field])) {
                $primaryTime = Carbon::parse($primaryData[$field]);
                $secondaryTime = Carbon::parse($secondaryData[$field]);

                if ($primaryTime->diffInSeconds($secondaryTime) > 60) {
                    return [
                        'valid' => false,
                        'reason' => "Diferencia significativa en timestamp: {$field}",
                    ];
                }
            }
        }

        return ['valid' => true, 'reason' => ''];
    }

    private function fixMissingRecord(string $connection, string $table, $record): void
    {
        try {
            $data = (array) $record;

            // Encriptar datos sensibles
            $encryptedData = $this->encryptionService->encryptSensitiveData($data, $table);

            DB::connection($connection)->table($table)->insert($encryptedData);

            $this->info("✅ Registro ID {$record->id} reparado en {$connection}");

            Log::channel('database-sync')->info('Registro faltante reparado', [
                'table' => $table,
                'connection' => $connection,
                'record_id' => $record->id,
            ]);

        } catch (\Exception $e) {
            $this->error("❌ Error reparando registro ID {$record->id}: ".$e->getMessage());
        }
    }

    private function fixCorruptedRecord(string $connection, string $table, $record): void
    {
        try {
            $data = (array) $record;

            // Encriptar datos sensibles
            $encryptedData = $this->encryptionService->encryptSensitiveData($data, $table);

            DB::connection($connection)
                ->table($table)
                ->where('id', $record->id)
                ->update($encryptedData);

            $this->info("✅ Registro ID {$record->id} corregido en {$connection}");

            Log::channel('database-sync')->info('Registro corrupto reparado', [
                'table' => $table,
                'connection' => $connection,
                'record_id' => $record->id,
            ]);

        } catch (\Exception $e) {
            $this->error("❌ Error corrigiendo registro ID {$record->id}: ".$e->getMessage());
        }
    }

    private function displaySummary(int $totalErrors, int $totalFixed): void
    {
        $this->newLine();
        $this->info('📋 RESUMEN DE VERIFICACIÓN');
        $this->info('========================');

        if ($totalErrors === 0) {
            $this->info('✅ No se encontraron problemas de integridad');
        } else {
            $this->error("❌ Se encontraron {$totalErrors} problemas de integridad");

            if ($totalFixed > 0) {
                $this->info("✅ Se repararon {$totalFixed} problemas");
            } else {
                $this->warn('💡 Ejecuta con --fix para intentar reparar automáticamente');
            }
        }

        Log::channel('database-sync')->info('Verificación de integridad completada', [
            'total_errors' => $totalErrors,
            'total_fixed' => $totalFixed,
            'timestamp' => Carbon::now(),
        ]);
    }
}
