<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatabaseConnection;
use App\Services\EncryptionService;
use App\Services\SecurityAuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseSyncController extends Controller
{
    private SecurityAuditService $securityAudit;

    private EncryptionService $encryption;

    public function __construct(SecurityAuditService $securityAudit, EncryptionService $encryption)
    {
        $this->middleware(['auth', 'role:Admin|Supervisor']);
        $this->securityAudit = $securityAudit;
        $this->encryption = $encryption;
    }

    /**
     * Dashboard principal de sincronización
     */
    public function dashboard()
    {
        $data = [
            'security_report' => $this->securityAudit->generateSecurityReport(),
            'sync_status' => $this->getSyncStatus(),
            'recent_alerts' => $this->getRecentAlerts(),
            'connection_status' => $this->getConnectionStatus(),
            'system_metrics' => $this->getSystemMetrics(),
        ];

        return view('admin.database-sync.dashboard', $data);
    }

    /**
     * Configuración del sistema
     */
    public function configuration()
    {
        $data = [
            'sync_connections' => config('database.sync_connections', []),
            'sync_tables' => config('database.sync_tables', []),
            'sync_enabled' => config('database.sync_enabled', true),
            'emergency_mode' => $this->securityAudit->isEmergencyMode(),
            'security_config' => config('security'),
        ];

        return view('admin.database-sync.configuration', $data);
    }

    /**
     * Logs del sistema
     */
    public function logs(Request $request)
    {
        $level = $request->get('level', 'all');
        $date = $request->get('date', Carbon::now()->format('Y-m-d'));
        $search = $request->get('search', '');

        $logs = $this->parseLogs($level, $date, $search);

        return view('admin.database-sync.logs', [
            'logs' => $logs,
            'level' => $level,
            'date' => $date,
            'search' => $search,
            'available_dates' => $this->getAvailableLogDates(),
        ]);
    }

    /**
     * Monitoreo en tiempo real
     */
    public function monitoring()
    {
        return view('admin.database-sync.monitoring');
    }

    /**
     * API para datos en tiempo real
     */
    public function apiMetrics()
    {
        $currentTime = time();
        $minute = floor($currentTime / 60);
        $hour = floor($currentTime / 3600);

        return response()->json([
            'sync_operations' => [
                'current_minute' => Cache::get("sync_metrics:minute:{$minute}:total", 0),
                'current_hour' => Cache::get("sync_metrics:hour:{$hour}:total", 0),
            ],
            'security' => [
                'alerts_count' => Cache::get('security_alerts_count', 0),
                'blocked_ips_count' => count(Cache::get('blocked_ips', [])),
                'emergency_mode' => $this->securityAudit->isEmergencyMode(),
            ],
            'connections' => $this->getConnectionStatus(),
            'timestamp' => $currentTime,
        ]);
    }

    /**
     * Gestión de IPs bloqueadas
     */
    public function blockedIPs(Request $request)
    {
        if ($request->isMethod('post')) {
            $action = $request->get('action');
            $ip = $request->get('ip');

            if ($action === 'block' && $ip) {
                $this->securityAudit->blockIP($ip, 'Bloqueado manualmente por administrador');

                return response()->json(['success' => true, 'message' => "IP {$ip} bloqueada"]);
            }

            if ($action === 'unblock' && $ip) {
                $this->unblockIP($ip);

                return response()->json(['success' => true, 'message' => "IP {$ip} desbloqueada"]);
            }
        }

        $blockedIPs = Cache::get('blocked_ips', []);
        $ipDetails = [];

        foreach ($blockedIPs as $ip) {
            $ipDetails[] = [
                'ip' => $ip,
                'blocked_at' => Cache::get("ip_blocked_at:{$ip}", 'Desconocido'),
                'reason' => Cache::get("ip_block_reason:{$ip}", 'No especificado'),
                'failed_attempts' => Cache::get('failed_attempts:ip:'.md5($ip), 0),
            ];
        }

        return view('admin.database-sync.blocked-ips', [
            'blocked_ips' => $ipDetails,
        ]);
    }

    /**
     * Gestión de respaldos
     */
    public function backups()
    {
        $backupsPath = storage_path('app/respaldos/seguros');
        $backups = [];

        if (is_dir($backupsPath)) {
            $files = glob($backupsPath.'/secure_backup_*');

            foreach ($files as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'size' => filesize($file),
                    'created_at' => Carbon::createFromTimestamp(filemtime($file)),
                    'path' => $file,
                ];
            }

            // Ordenar por fecha (más recientes primero)
            usort($backups, function ($a, $b) {
                return $b['created_at']->timestamp - $a['created_at']->timestamp;
            });
        }

        return view('admin.database-sync.backups', [
            'backups' => $backups,
        ]);
    }

    /**
     * Crear respaldo manual
     */
    public function createBackup(Request $request)
    {
        try {
            $connection = $request->get('connection', config('database.default'));
            $encrypt = $request->boolean('encrypt', true);
            $compress = $request->boolean('compress', true);

            // Ejecutar comando de respaldo
            Artisan::call('db:secure-backup', [
                '--connection' => $connection,
                '--encrypt' => $encrypt,
                '--compress' => $compress,
            ]);

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Respaldo creado exitosamente',
                'output' => $output,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creando respaldo: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verificar integridad de datos
     */
    public function verifyIntegrity(Request $request)
    {
        try {
            $connection = $request->get('connection');
            $table = $request->get('table');
            $fix = $request->boolean('fix', false);

            $params = array_filter([
                '--connection' => $connection,
                '--table' => $table,
                '--fix' => $fix,
            ]);

            Artisan::call('db:verify-integrity', $params);

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Verificación completada',
                'output' => $output,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en verificación: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle modo de emergencia
     */
    public function toggleEmergencyMode(Request $request)
    {
        $enable = $request->boolean('enable');

        if ($enable) {
            Cache::put('emergency_mode', true, 1800); // 30 minutos
            $message = 'Modo de emergencia activado';
        } else {
            Cache::forget('emergency_mode');
            $message = 'Modo de emergencia desactivado';
        }

        Log::channel('database-sync')->warning('Modo de emergencia cambiado manualmente', [
            'enabled' => $enable,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
            'emergency_mode' => $enable,
        ]);
    }

    // Métodos privados de apoyo

    private function getSyncStatus(): array
    {
        $connections = config('database.sync_connections', []);
        $status = [];

        foreach ($connections as $connection) {
            try {
                DB::connection($connection)->getPdo();
                $status[$connection] = 'online';
            } catch (\Exception $e) {
                $status[$connection] = 'offline';
            }
        }

        return $status;
    }

    private function getRecentAlerts(): array
    {
        // Obtener alertas recientes de los logs
        $logFile = storage_path('logs/database-sync.log');
        $alerts = [];

        if (file_exists($logFile)) {
            $lines = file($logFile);
            $recentLines = array_slice($lines, -100); // Últimas 100 líneas

            foreach ($recentLines as $line) {
                if (str_contains(strtolower($line), 'error') || str_contains(strtolower($line), 'critical')) {
                    $alerts[] = [
                        'timestamp' => $this->extractTimestamp($line),
                        'level' => $this->extractLevel($line),
                        'message' => $this->extractMessage($line),
                    ];
                }
            }
        }

        return array_slice($alerts, -20); // Últimas 20 alertas
    }

    private function getConnectionStatus(): array
    {
        $connections = config('database.sync_connections', []);
        $status = [];

        foreach ($connections as $connection) {
            try {
                $start = microtime(true);
                DB::connection($connection)->select('SELECT 1');
                $latency = round((microtime(true) - $start) * 1000, 2);

                $status[$connection] = [
                    'status' => 'connected',
                    'latency' => $latency.'ms',
                ];
            } catch (\Exception $e) {
                $status[$connection] = [
                    'status' => 'disconnected',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $status;
    }

    private function getSystemMetrics(): array
    {
        return [
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'cpu_load' => function_exists('sys_getloadavg') ? sys_getloadavg()[0] : null,
            'disk_free' => round(disk_free_space(storage_path()) / 1024 / 1024 / 1024, 2),
        ];
    }

    private function parseLogs(string $level, string $date, string $search): array
    {
        $logFile = storage_path("logs/database-sync-{$date}.log");

        if (! file_exists($logFile)) {
            $logFile = storage_path('logs/database-sync.log');
        }

        $logs = [];

        if (file_exists($logFile)) {
            $lines = file($logFile);

            foreach ($lines as $line) {
                if (empty(trim($line))) {
                    continue;
                }

                $logLevel = $this->extractLevel($line);

                if ($level !== 'all' && strtolower($logLevel) !== strtolower($level)) {
                    continue;
                }

                if (! empty($search) && ! str_contains(strtolower($line), strtolower($search))) {
                    continue;
                }

                $logs[] = [
                    'timestamp' => $this->extractTimestamp($line),
                    'level' => $logLevel,
                    'message' => $this->extractMessage($line),
                    'context' => $this->extractContext($line),
                ];
            }
        }

        return array_reverse(array_slice($logs, -500)); // Últimas 500 entradas
    }

    private function getAvailableLogDates(): array
    {
        $logsPath = storage_path('logs');
        $dates = [];

        $files = glob($logsPath.'/database-sync-*.log');

        foreach ($files as $file) {
            if (preg_match('/database-sync-(\d{4}-\d{2}-\d{2})\.log$/', $file, $matches)) {
                $dates[] = $matches[1];
            }
        }

        rsort($dates);

        return $dates;
    }

    private function unblockIP(string $ip): void
    {
        $blockedIPs = Cache::get('blocked_ips', []);
        $blockedIPs = array_filter($blockedIPs, fn ($blockedIP) => $blockedIP !== $ip);

        Cache::put('blocked_ips', array_values($blockedIPs), 86400);
        Cache::forget('failed_attempts:ip:'.md5($ip));
        Cache::forget("ip_blocked_at:{$ip}");
        Cache::forget("ip_block_reason:{$ip}");
    }

    private function extractTimestamp(string $line): string
    {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function extractLevel(string $line): string
    {
        if (preg_match('/\]\s+(\w+):/', $line, $matches)) {
            return strtoupper($matches[1]);
        }

        return 'INFO';
    }

    private function extractMessage(string $line): string
    {
        if (preg_match('/\]\s+\w+:\s+(.+?)(\s+\{|$)/', $line, $matches)) {
            return trim($matches[1]);
        }

        return trim($line);
    }

    private function extractContext(string $line): ?array
    {
        if (preg_match('/\{(.+)\}$/', $line, $matches)) {
            try {
                return json_decode('{'.$matches[1].'}', true);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Gestión de conexiones de bases de datos
     */
    public function connections()
    {
        $connections = DatabaseConnection::orderBy('name')->get();

        return view('admin.database-sync.connections', [
            'connections' => $connections,
            'available_tables' => $this->getAvailableTables(),
        ]);
    }

    /**
     * Obtener datos de una conexión para edición
     */
    public function showConnection(DatabaseConnection $connection)
    {
        return response()->json([
            'success' => true,
            'connection' => $connection->makeHidden('password')->toArray(),
        ]);
    }

    /**
     * Crear nueva conexión
     */
    public function createConnection(Request $request)
    {
        $request->merge([
            'is_sync_enabled' => filter_var($request->input('is_sync_enabled'), FILTER_VALIDATE_BOOLEAN),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:database_connections,name',
            'description' => 'nullable|string|max:500',
            'driver' => 'required|in:mysql,pgsql,sqlite,sqlsrv',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'database' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'charset' => 'nullable|string|max:50',
            'collation' => 'nullable|string|max:50',
            'prefix' => 'nullable|string|max:50',
            'is_sync_enabled' => 'boolean',
            'sync_tables' => 'nullable|array',
            'sync_tables.*' => 'string',
        ]);

        try {
            $connection = DatabaseConnection::create($validated);

            // Probar la conexión
            $testResult = $connection->testConnection();

            if (! $testResult['success']) {
                $connection->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'Error de conexión: '.$testResult['message'],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Conexión creada exitosamente',
                'connection' => $connection,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creando conexión: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar conexión existente
     */
    public function updateConnection(Request $request, DatabaseConnection $connection)
    {
        $request->merge([
            'is_sync_enabled' => filter_var($request->input('is_sync_enabled'), FILTER_VALIDATE_BOOLEAN),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:database_connections,name,'.$connection->id,
            'description' => 'nullable|string|max:500',
            'driver' => 'required|in:mysql,pgsql,sqlite,sqlsrv',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'database' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string',
            'charset' => 'nullable|string|max:50',
            'collation' => 'nullable|string|max:50',
            'prefix' => 'nullable|string|max:50',
            'is_sync_enabled' => 'boolean',
            'sync_tables' => 'nullable|array',
            'sync_tables.*' => 'string',
        ]);

        try {
            // Si no se proporciona password, mantener el actual
            if (empty($validated['password'])) {
                unset($validated['password']);
            }

            $connection->update($validated);

            // Probar la conexión actualizada
            $testResult = $connection->testConnection();

            if (! $testResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuración guardada pero hay problemas de conexión: '.$testResult['message'],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Conexión actualizada exitosamente',
                'connection' => $connection,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error actualizando conexión: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Probar conexión
     */
    public function testConnection(Request $request, ?DatabaseConnection $connection = null)
    {
        try {
            if ($connection) {
                // Probar conexión existente
                $result = $connection->testConnection();
            } else {
                // Probar conexión con datos temporales
                $validated = $request->validate([
                    'driver' => 'required|in:mysql,pgsql,sqlite,sqlsrv',
                    'host' => 'required|string',
                    'port' => 'required|integer',
                    'database' => 'required|string',
                    'username' => 'required|string',
                    'password' => 'required|string',
                ]);

                $tempConnection = new DatabaseConnection($validated);
                $result = $tempConnection->testConnection();
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error probando conexión: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar conexión
     */
    public function deleteConnection(DatabaseConnection $connection)
    {
        try {
            $connection->delete();

            return response()->json([
                'success' => true,
                'message' => 'Conexión eliminada exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error eliminando conexión: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sincronizar tablas manualmente
     */
    public function syncTables(Request $request)
    {
        $validated = $request->validate([
            'connection_id' => 'required|exists:database_connections,id',
            'tables' => 'nullable|array',
            'tables.*' => 'string',
            'force' => 'boolean',
        ]);

        try {
            $connection = DatabaseConnection::findOrFail($validated['connection_id']);

            $tables = $validated['tables'] ?? $connection->sync_tables ?? [];
            $force = $validated['force'] ?? false;

            // Ejecutar comando de sincronización
            Artisan::call('db:create-sync-tables', [
                '--connection' => $connection->name,
                '--force' => $force,
            ]);

            $output = Artisan::output();

            // Actualizar timestamp de sincronización
            $connection->update([
                'last_sync_at' => now(),
                'sync_errors' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sincronización completada exitosamente',
                'output' => $output,
            ]);

        } catch (\Exception $e) {
            // Guardar error de sincronización
            if (isset($connection)) {
                $connection->update([
                    'sync_errors' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error en sincronización: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener tablas disponibles en la base de datos principal
     */
    private function getAvailableTables(): array
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $tableNames = [];

            foreach ($tables as $table) {
                $tableArray = (array) $table;
                $tableNames[] = reset($tableArray);
            }

            return $tableNames;

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Alternar estado de conexión
     */
    public function toggleConnection(DatabaseConnection $connection)
    {
        try {
            $connection->update([
                'is_active' => ! $connection->is_active,
            ]);

            $status = $connection->is_active ? 'activada' : 'desactivada';

            return response()->json([
                'success' => true,
                'message' => "Conexión {$status} exitosamente",
                'is_active' => $connection->is_active,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cambiando estado: '.$e->getMessage(),
            ], 500);
        }
    }
}
