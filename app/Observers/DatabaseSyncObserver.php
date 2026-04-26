<?php

namespace App\Observers;

use App\Jobs\SyncToSecondaryDatabase;
use App\Services\SecurityAuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class DatabaseSyncObserver
{
    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        $this->dispatchSyncJobs($model, 'upsert', 'created');
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        // Solo sincronizar si hubo cambios reales
        if ($model->wasChanged()) {
            $this->dispatchSyncJobs($model, 'upsert', 'updated');
        }
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->dispatchSyncJobs($model, 'delete', 'deleted');
    }

    /**
     * Handle the model "force deleted" event.
     */
    public function forceDeleted(Model $model): void
    {
        $this->dispatchSyncJobs($model, 'delete', 'force_deleted');
    }

    /**
     * Dispatch synchronization jobs to secondary databases
     */
    private function dispatchSyncJobs(Model $model, string $action, string $event): void
    {
        try {
            $tableName = $model->getTable();
            $connections = Config::get('database.sync_connections', []);
            $syncTables = Config::get('database.sync_tables', []);

            // Verificar si la tabla debe ser sincronizada
            if (! in_array($tableName, $syncTables)) {
                Log::channel('database-sync')->debug("Tabla {$tableName} no configurada para sincronización");

                return;
            }

            // Verificar si hay conexiones configuradas
            if (empty($connections)) {
                Log::channel('database-sync')->debug('No hay conexiones secundarias configuradas - sincronización omitida');

                return;
            }

            $modelData = $this->prepareModelData($model, $action);
            $primaryKey = $model->getKeyName();

            // Auditoría de seguridad
            $auditService = app(SecurityAuditService::class);
            $auditService->logSyncActivity($tableName, $event, [
                'primary_key' => $primaryKey,
                'primary_key_value' => $model->getKey(),
                'connections_to_sync' => count($connections),
                'model_class' => get_class($model),
            ]);

            // Log del evento principal
            Log::channel('database-sync')->info("Evento {$event} detectado", [
                'table' => $tableName,
                'primary_key' => $primaryKey,
                'primary_key_value' => $model->getKey(),
                'connections_to_sync' => count($connections),
            ]);

            // Dispatch jobs para cada conexión secundaria
            foreach ($connections as $connection) {
                try {
                    SyncToSecondaryDatabase::dispatch(
                        $tableName,
                        $modelData,
                        $connection,
                        $action,
                        $primaryKey
                    );

                    Log::channel('database-sync')->debug("Job despachado para {$connection}", [
                        'table' => $tableName,
                        'action' => $action,
                        'primary_key_value' => $model->getKey(),
                    ]);

                } catch (\Exception $e) {
                    Log::channel('database-sync')->error("Error al despachar job para {$connection}", [
                        'table' => $tableName,
                        'action' => $action,
                        'error' => $e->getMessage(),
                        'primary_key_value' => $model->getKey(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::channel('database-sync')->critical('Error crítico en DatabaseSyncObserver', [
                'event' => $event,
                'model' => get_class($model),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Prepare model data for synchronization
     */
    private function prepareModelData(Model $model, string $action): array
    {
        if ($action === 'delete') {
            // Para delete, solo necesitamos la clave primaria
            return [
                $model->getKeyName() => $model->getKey(),
            ];
        }

        // Para upsert, obtener todos los atributos
        $data = $model->toArray();

        // Manejar relaciones que podrían haberse cargado
        foreach ($model->getRelations() as $relation => $value) {
            unset($data[$relation]);
        }

        // Asegurar que las fechas estén en formato correcto
        foreach ($model->getDates() as $dateField) {
            if (isset($data[$dateField]) && $data[$dateField]) {
                $data[$dateField] = $model->getAttribute($dateField)?->format('Y-m-d H:i:s');
            }
        }

        // Manejar campos JSON
        foreach ($model->getCasts() as $field => $cast) {
            if (in_array($cast, ['array', 'json']) && isset($data[$field])) {
                $data[$field] = is_string($data[$field]) ? $data[$field] : json_encode($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Verificar si el modelo debe ser sincronizado
     */
    private function shouldSync(Model $model): bool
    {
        // Verificar si el modelo tiene un flag para evitar sincronización
        if (property_exists($model, 'skipSync') && $model->skipSync === true) {
            return false;
        }

        // Verificar si estamos en modo de mantenimiento
        if (app()->isDownForMaintenance()) {
            return false;
        }

        // Verificar si la sincronización está habilitada globalmente
        if (! Config::get('database.sync_enabled', true)) {
            return false;
        }

        return true;
    }
}
