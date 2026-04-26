<?php

namespace App\Jobs;

use App\Services\EncryptionService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncToSecondaryDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    public $timeout = 60;

    public function __construct(
        public string $table,
        public array $data,
        public string $dbConnection,
        public string $action = 'upsert',
        public ?string $primaryKey = 'id'
    ) {
        $this->onQueue('database-sync');
    }

    public function handle(): void
    {
        try {
            $this->logSyncAttempt();

            // Verificar seguridad antes de proceder
            $this->performSecurityChecks();

            match ($this->action) {
                'upsert' => $this->performUpsert(),
                'delete' => $this->performDelete(),
                default => throw new Exception("Acción no válida: {$this->action}")
            };

            $this->logSyncSuccess();

        } catch (Exception $e) {
            $this->logSyncError($e);

            // Re-throw para activar el sistema de retry
            throw $e;
        }
    }

    private function performUpsert(): void
    {
        // Verificar que la tabla está en la lista de tablas críticas
        if (! in_array($this->table, config('database.sync_tables', []))) {
            Log::channel('database-sync')->warning("Tabla {$this->table} no está en la lista de sincronización");

            return;
        }

        $result = DB::connection($this->dbConnection)
            ->table($this->table)
            ->updateOrInsert(
                [$this->primaryKey => $this->data[$this->primaryKey] ?? null],
                $this->sanitizeData()
            );

        if (! $result) {
            throw new Exception("Error al realizar upsert en {$this->dbConnection}.{$this->table}");
        }
    }

    private function performDelete(): void
    {
        if (! isset($this->data[$this->primaryKey])) {
            throw new Exception("No se puede eliminar sin {$this->primaryKey}");
        }

        $result = DB::connection($this->dbConnection)
            ->table($this->table)
            ->where($this->primaryKey, $this->data[$this->primaryKey])
            ->delete();

        // Para delete, result puede ser 0 si el registro ya no existe
        // Esto no es necesariamente un error
    }

    private function sanitizeData(): array
    {
        $encryptionService = app(EncryptionService::class);

        // Sanitizar y encriptar datos sensibles
        $sanitized = $encryptionService->sanitizeData($this->data, $this->table);
        $encrypted = $encryptionService->encryptSensitiveData($sanitized, $this->table);

        // Remover campos que no deberían sincronizarse
        $excludeFields = ['remember_token', 'email_verified_at', 'password'];

        $filtered = array_filter($encrypted, function ($key) use ($excludeFields) {
            return ! in_array($key, $excludeFields);
        }, ARRAY_FILTER_USE_KEY);

        // Convertir timestamps a formato MySQL si es necesario
        foreach (['created_at', 'updated_at', 'deleted_at'] as $timestampField) {
            if (isset($filtered[$timestampField]) && $filtered[$timestampField]) {
                $filtered[$timestampField] = date('Y-m-d H:i:s', strtotime($filtered[$timestampField]));
            }
        }

        return $filtered;
    }

    private function performSecurityChecks(): void
    {
        // Verificar que la conexión está autorizada
        if (! $this->isConnectionAuthorized()) {
            throw new Exception("Conexión no autorizada: {$this->dbConnection}");
        }

        // Verificar límites de rate limiting
        if (! $this->checkRateLimit()) {
            throw new Exception("Rate limit excedido para {$this->dbConnection}");
        }

        // Verificar integridad de datos si es una operación financiera
        if ($this->isFinancialTable() && ! $this->verifyDataIntegrity()) {
            throw new Exception("Verificación de integridad falló para {$this->table}");
        }
    }

    private function isConnectionAuthorized(): bool
    {
        $allowedConnections = config('database.sync_connections', []);

        return in_array($this->dbConnection, $allowedConnections);
    }

    private function checkRateLimit(): bool
    {
        $cacheKey = "sync_rate_limit:{$this->dbConnection}:".floor(time() / 60);
        $attempts = cache()->get($cacheKey, 0);

        // Límite de 100 operaciones por minuto por conexión
        if ($attempts >= 100) {
            return false;
        }

        cache()->put($cacheKey, $attempts + 1, 120);

        return true;
    }

    private function isFinancialTable(): bool
    {
        $financialTables = ['prestamos', 'cuotas', 'operaciones', 'moras', 'comprobantes'];

        return in_array($this->table, $financialTables);
    }

    private function verifyDataIntegrity(): bool
    {
        if ($this->action === 'delete') {
            return true; // No hay datos que verificar en delete
        }

        $encryptionService = app(EncryptionService::class);

        return $encryptionService->verifyFinancialIntegrity($this->data);
    }

    private function logSyncAttempt(): void
    {
        Log::channel('database-sync')->info('Iniciando sincronización', [
            'job_id' => $this->job ? $this->job->getJobId() : 'sync-job',
            'table' => $this->table,
            'connection' => $this->dbConnection,
            'action' => $this->action,
            'attempt' => $this->job ? $this->attempts() : 1,
            'primary_key_value' => $this->data[$this->primaryKey] ?? 'N/A',
        ]);
    }

    private function logSyncSuccess(): void
    {
        Log::channel('database-sync')->info('Sincronización exitosa', [
            'job_id' => $this->job ? $this->job->getJobId() : 'sync-job',
            'table' => $this->table,
            'connection' => $this->dbConnection,
            'action' => $this->action,
            'primary_key_value' => $this->data[$this->primaryKey] ?? 'N/A',
        ]);
    }

    private function logSyncError(Exception $e): void
    {
        Log::channel('database-sync')->error('Error en sincronización', [
            'job_id' => $this->job ? $this->job->getJobId() : 'sync-job',
            'table' => $this->table,
            'connection' => $this->dbConnection,
            'action' => $this->action,
            'attempt' => $this->job ? $this->attempts() : 1,
            'max_attempts' => $this->tries,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'primary_key_value' => $this->data[$this->primaryKey] ?? 'N/A',
        ]);
    }

    public function failed(Exception $exception): void
    {
        Log::channel('database-sync')->critical('Job de sincronización falló definitivamente', [
            'job_id' => $this->job ? $this->job->getJobId() : 'sync-job',
            'table' => $this->table,
            'connection' => $this->dbConnection,
            'action' => $this->action,
            'final_error' => $exception->getMessage(),
            'primary_key_value' => $this->data[$this->primaryKey] ?? 'N/A',
        ]);

        // Opcional: Enviar alerta crítica al equipo de TI
        // $this->sendCriticalAlert($exception);
    }

    /**
     * Determinar el retraso antes del siguiente intento
     */
    public function retryAfter(): array
    {
        return $this->backoff;
    }
}
