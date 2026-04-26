<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SecurityAuditService
{
    /**
     * Registra actividad de sincronización para auditoría
     */
    public function logSyncActivity(string $table, string $action, array $metadata = []): void
    {
        $auditData = [
            'timestamp' => Carbon::now(),
            'table' => $table,
            'action' => $action,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'metadata' => $metadata,
        ];

        // Log inmediato
        Log::channel('database-sync')->info('Actividad de sincronización registrada', $auditData);

        // Almacenar en cache para análisis en tiempo real
        $this->storeSyncMetrics($auditData);

        // Detectar patrones sospechosos
        $this->detectSuspiciousActivity($auditData);
    }

    /**
     * Almacena métricas de sincronización en cache
     */
    private function storeSyncMetrics(array $auditData): void
    {
        $minute = floor(time() / 60);
        $hour = floor(time() / 3600);
        $day = floor(time() / 86400);

        // Contadores por minuto, hora y día
        Cache::increment("sync_metrics:minute:{$minute}:total", 1, 120);
        Cache::increment("sync_metrics:hour:{$hour}:total", 1, 3720);
        Cache::increment("sync_metrics:day:{$day}:total", 1, 90000);

        // Contadores por tabla
        Cache::increment("sync_metrics:minute:{$minute}:table:{$auditData['table']}", 1, 120);

        // Contadores por IP
        Cache::increment("sync_metrics:minute:{$minute}:ip:".md5($auditData['ip_address']), 1, 120);
    }

    /**
     * Detecta actividad sospechosa en tiempo real
     */
    private function detectSuspiciousActivity(array $auditData): void
    {
        $alerts = [];

        // 1. Verificar volumen anormal de operaciones
        if ($this->checkVolumeAnomaly($auditData)) {
            $alerts[] = 'Volumen anormal de operaciones detectado';
        }

        // 2. Verificar acceso desde IPs sospechosas
        if ($this->checkSuspiciousIP($auditData['ip_address'])) {
            $alerts[] = 'Acceso desde IP sospechosa: '.$auditData['ip_address'];
        }

        // 3. Verificar patrones de tiempo sospechosos
        if ($this->checkTimePatternAnomaly($auditData)) {
            $alerts[] = 'Patrón de tiempo sospechoso detectado';
        }

        // 4. Verificar intentos de acceso a tablas sensibles
        if ($this->checkSensitiveTableAccess($auditData)) {
            $alerts[] = 'Acceso inusual a tabla sensible: '.$auditData['table'];
        }

        // Enviar alertas si se detectó algo sospechoso
        if (! empty($alerts)) {
            $this->sendSecurityAlert($alerts, $auditData);
        }
    }

    /**
     * Verifica anomalías de volumen
     */
    private function checkVolumeAnomaly(array $auditData): bool
    {
        $minute = floor(time() / 60);
        $currentVolume = Cache::get("sync_metrics:minute:{$minute}:total", 0);

        // Alerta si hay más de 200 operaciones por minuto
        return $currentVolume > 200;
    }

    /**
     * Verifica IPs sospechosas
     */
    private function checkSuspiciousIP(string $ip): bool
    {
        // Lista de IPs bloqueadas (deberían estar en base de datos)
        $blockedIPs = Cache::get('blocked_ips', []);

        if (in_array($ip, $blockedIPs)) {
            return true;
        }

        // Verificar si la IP ha tenido muchos intentos fallidos
        $failedAttempts = Cache::get('failed_attempts:ip:'.md5($ip), 0);

        return $failedAttempts > 10;
    }

    /**
     * Verifica patrones de tiempo anómalos
     */
    private function checkTimePatternAnomaly(array $auditData): bool
    {
        $hour = (int) Carbon::now()->format('H');

        // Alerta si hay actividad entre 2 AM y 5 AM (horario inusual)
        if ($hour >= 2 && $hour <= 5) {
            $hourlyVolume = Cache::get('sync_metrics:hour:'.floor(time() / 3600).':total', 0);

            // Alerta si hay más de 50 operaciones en horario nocturno
            return $hourlyVolume > 50;
        }

        return false;
    }

    /**
     * Verifica acceso a tablas sensibles
     */
    private function checkSensitiveTableAccess(array $auditData): bool
    {
        $sensitiveTables = ['users', 'personal_access_tokens', 'sessions'];

        if (in_array($auditData['table'], $sensitiveTables)) {
            return true;
        }

        // Verificar si hay muchos accesos consecutivos a la misma tabla
        $minute = floor(time() / 60);
        $tableAccesses = Cache::get("sync_metrics:minute:{$minute}:table:{$auditData['table']}", 0);

        return $tableAccesses > 100;
    }

    /**
     * Envía alerta de seguridad
     */
    private function sendSecurityAlert(array $alerts, array $auditData): void
    {
        $alertData = [
            'severity' => 'HIGH',
            'timestamp' => Carbon::now(),
            'alerts' => $alerts,
            'audit_data' => $auditData,
            'system' => 'Database Sync Security Monitor',
        ];

        // Log crítico
        Log::channel('database-sync-critical')->critical('ALERTA DE SEGURIDAD', $alertData);

        // Incrementar contador de alertas
        Cache::increment('security_alerts_count', 1, 3600);

        // Si hay muchas alertas, bloquear temporalmente
        $alertCount = Cache::get('security_alerts_count', 0);
        if ($alertCount > 20) {
            $this->enableEmergencyMode();
        }
    }

    /**
     * Activa modo de emergencia
     */
    private function enableEmergencyMode(): void
    {
        Cache::put('emergency_mode', true, 1800); // 30 minutos

        Log::channel('database-sync-critical')->emergency('MODO DE EMERGENCIA ACTIVADO', [
            'reason' => 'Demasiadas alertas de seguridad',
            'timestamp' => Carbon::now(),
            'duration' => '30 minutos',
        ]);

        // Opcional: Enviar notificación inmediata al equipo
        // $this->sendEmergencyNotification();
    }

    /**
     * Verifica si el sistema está en modo de emergencia
     */
    public function isEmergencyMode(): bool
    {
        return Cache::get('emergency_mode', false);
    }

    /**
     * Genera reporte de seguridad
     */
    public function generateSecurityReport(): array
    {
        $currentTime = time();
        $day = floor($currentTime / 86400);
        $hour = floor($currentTime / 3600);

        return [
            'period' => Carbon::now()->format('Y-m-d H:i:s'),
            'total_operations_today' => Cache::get("sync_metrics:day:{$day}:total", 0),
            'total_operations_this_hour' => Cache::get("sync_metrics:hour:{$hour}:total", 0),
            'security_alerts_count' => Cache::get('security_alerts_count', 0),
            'emergency_mode' => $this->isEmergencyMode(),
            'blocked_ips_count' => count(Cache::get('blocked_ips', [])),
            'system_status' => $this->getSystemStatus(),
        ];
    }

    /**
     * Obtiene estado del sistema
     */
    private function getSystemStatus(): string
    {
        if ($this->isEmergencyMode()) {
            return 'EMERGENCY';
        }

        $alertCount = Cache::get('security_alerts_count', 0);

        if ($alertCount > 10) {
            return 'HIGH_ALERT';
        } elseif ($alertCount > 5) {
            return 'MODERATE_ALERT';
        }

        return 'NORMAL';
    }

    /**
     * Bloquea una IP sospechosa
     */
    public function blockIP(string $ip, string $reason = ''): void
    {
        $blockedIPs = Cache::get('blocked_ips', []);

        if (! in_array($ip, $blockedIPs)) {
            $blockedIPs[] = $ip;
            Cache::put('blocked_ips', $blockedIPs, 86400); // 24 horas

            Log::channel('database-sync')->warning('IP bloqueada', [
                'ip' => $ip,
                'reason' => $reason,
                'timestamp' => Carbon::now(),
            ]);
        }
    }

    /**
     * Registra intento fallido
     */
    public function recordFailedAttempt(string $ip): void
    {
        $key = 'failed_attempts:ip:'.md5($ip);
        $attempts = Cache::increment($key, 1, 3600);

        // Auto-bloquear después de 15 intentos fallidos
        if ($attempts >= 15) {
            $this->blockIP($ip, 'Demasiados intentos fallidos');
        }
    }
}
