<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MonitoreoController extends Controller
{
    // Umbrales de alertas
    private $umbrales = [
        'cpu' => 80.0,           // % de uso de CPU
        'memoria' => 85.0,       // % de uso de memoria
        'disco' => 90.0,         // % de uso de disco
        'conexiones_db' => 80,   // Número de conexiones DB
        'tiempo_respuesta' => 2.0, // Segundos
    ];

    public function index(Request $request)
    {
        // Obtener métricas actuales del sistema
        $metricas = $this->obtenerMetricasActuales();

        // Obtener histórico para gráficos
        $historico = $this->obtenerHistorico();

        // Verificar alertas activas
        $alertas = $this->verificarAlertas($metricas);

        // Estado general del sistema
        $estadoGeneral = $this->calcularEstadoGeneral($metricas, $alertas);

        if ($request->ajax()) {
            return response()->json([
                'metricas' => $metricas,
                'alertas' => $alertas,
                'estado_general' => $estadoGeneral,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
        }

        return view('admin.monitoreo.index', [
            'metricas' => $metricas,
            'historico' => $historico,
            'alertas' => $alertas,
            'estado_general' => $estadoGeneral,
            'umbrales' => $this->umbrales,
        ]);
    }

    public function metricas(Request $request)
    {
        $periodo = $request->get('periodo', '24h');
        $metricas = $this->obtenerMetricasPorPeriodo($periodo);

        return response()->json($metricas);
    }

    public function alertas(Request $request)
    {
        $alertasActivas = Cache::get('alertas_sistema', []);
        $historicoAlertas = $this->obtenerHistoricoAlertas();

        return response()->json([
            'activas' => $alertasActivas,
            'historico' => $historicoAlertas,
        ]);
    }

    public function configuracion(Request $request)
    {
        if ($request->isMethod('post')) {
            $umbrales = $request->validate([
                'cpu' => 'required|numeric|min:1|max:100',
                'memoria' => 'required|numeric|min:1|max:100',
                'disco' => 'required|numeric|min:1|max:100',
                'conexiones_db' => 'required|integer|min:1|max:1000',
                'tiempo_respuesta' => 'required|numeric|min:0.1|max:60',
            ]);

            Cache::put('umbrales_monitoreo', $umbrales, now()->addYear());

            return response()->json([
                'success' => true,
                'mensaje' => 'Configuración actualizada correctamente',
            ]);
        }

        $umbralesActuales = Cache::get('umbrales_monitoreo', $this->umbrales);

        return response()->json($umbralesActuales);
    }

    private function obtenerMetricasActuales()
    {
        $metricas = [];

        try {
            // === MÉTRICAS DE SISTEMA ===

            // CPU - usando varios métodos según disponibilidad
            $metricas['cpu'] = $this->obtenerUsoCPU();

            // Memoria
            $metricas['memoria'] = $this->obtenerUsoMemoria();

            // Disco
            $metricas['disco'] = $this->obtenerUsoDisco();

            // Carga del sistema (load average)
            $metricas['carga_sistema'] = $this->obtenerCargaSistema();

            // === MÉTRICAS DE BASE DE DATOS ===

            // Conexiones activas
            $metricas['db_conexiones'] = $this->obtenerConexionesDB();

            // Tiempo de respuesta de DB
            $metricas['db_tiempo_respuesta'] = $this->medirTiempoRespuestaDB();

            // Tamaño de la base de datos
            $metricas['db_tamaño'] = $this->obtenerTamañoDB();

            // === MÉTRICAS DE PHP ===

            // Memoria PHP
            $metricas['php_memoria'] = $this->obtenerMemoriaPHP();

            // Procesos PHP-FPM (si está disponible)
            $metricas['php_procesos'] = $this->obtenerProcesosPHP();

            // === MÉTRICAS DE APLICACIÓN ===

            // Logs de errores recientes
            $metricas['errores_recientes'] = $this->contarErroresRecientes();

            // Cache hits/misses
            $metricas['cache_stats'] = $this->obtenerEstadisticasCache();

            // Sessions activas
            $metricas['sesiones_activas'] = $this->contarSesionesActivas();

            // === MÉTRICAS DE RED Y SERVIDOR WEB ===

            // Estado del servidor web
            $metricas['servidor_web'] = $this->verificarServidorWeb();

            // Tiempo de respuesta HTTP
            $metricas['tiempo_respuesta_http'] = $this->medirTiempoRespuestaHTTP();

        } catch (\Exception $e) {
            \Log::error('Error obteniendo métricas del sistema: '.$e->getMessage());
            $metricas['error'] = $e->getMessage();
        }

        // Timestamp
        $metricas['timestamp'] = now();

        // Guardar en caché para histórico
        $this->guardarMetricasHistorico($metricas);

        return $metricas;
    }

    private function obtenerUsoCPU()
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                // Windows - usar wmic
                $output = shell_exec('wmic cpu get loadpercentage /value 2>nul');
                if ($output && preg_match('/LoadPercentage=(\d+)/', $output, $matches)) {
                    return (float) $matches[1];
                }

                // Fallback para Windows - usar typeperf
                $output = shell_exec('typeperf "\\Processor(_Total)\\% Processor Time" -sc 1 2>nul');
                if ($output && preg_match('/(\d+\.\d+)/', $output, $matches)) {
                    return (float) $matches[1];
                }
            } else {
                // Linux/Unix - usar varios métodos

                // Método 1: /proc/loadavg
                if (file_exists('/proc/loadavg')) {
                    $loadavg = file_get_contents('/proc/loadavg');
                    $load = explode(' ', $loadavg);

                    return ((float) $load[0]) * 100 / $this->obtenerNumeroNucleos();
                }

                // Método 2: top command
                $output = shell_exec('top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk \'{print 100 - $1}\'');
                if ($output) {
                    return (float) trim($output);
                }
            }
        } catch (\Exception $e) {
            \Log::warning('No se pudo obtener uso de CPU: '.$e->getMessage());
        }

        return 0.0;
    }

    private function obtenerUsoMemoria()
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                // Windows
                $output = shell_exec('wmic OS get TotalVisibleMemorySize,FreePhysicalMemory /value 2>nul');
                if ($output) {
                    preg_match('/FreePhysicalMemory=(\d+)/', $output, $free);
                    preg_match('/TotalVisibleMemorySize=(\d+)/', $output, $total);

                    if (isset($free[1]) && isset($total[1])) {
                        $freeKB = (int) $free[1];
                        $totalKB = (int) $total[1];
                        $usedKB = $totalKB - $freeKB;

                        return [
                            'porcentaje' => round(($usedKB / $totalKB) * 100, 2),
                            'usado_mb' => round($usedKB / 1024, 2),
                            'total_mb' => round($totalKB / 1024, 2),
                            'libre_mb' => round($freeKB / 1024, 2),
                        ];
                    }
                }
            } else {
                // Linux/Unix
                if (file_exists('/proc/meminfo')) {
                    $meminfo = file_get_contents('/proc/meminfo');
                    preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
                    preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);

                    if (isset($total[1]) && isset($available[1])) {
                        $totalKB = (int) $total[1];
                        $availableKB = (int) $available[1];
                        $usedKB = $totalKB - $availableKB;

                        return [
                            'porcentaje' => round(($usedKB / $totalKB) * 100, 2),
                            'usado_mb' => round($usedKB / 1024, 2),
                            'total_mb' => round($totalKB / 1024, 2),
                            'libre_mb' => round($availableKB / 1024, 2),
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::warning('No se pudo obtener uso de memoria: '.$e->getMessage());
        }

        return [
            'porcentaje' => 0,
            'usado_mb' => 0,
            'total_mb' => 0,
            'libre_mb' => 0,
        ];
    }

    private function obtenerUsoDisco()
    {
        try {
            $path = base_path();
            $totalBytes = disk_total_space($path);
            $freeBytes = disk_free_space($path);
            $usedBytes = $totalBytes - $freeBytes;

            return [
                'porcentaje' => round(($usedBytes / $totalBytes) * 100, 2),
                'usado_gb' => round($usedBytes / (1024 ** 3), 2),
                'total_gb' => round($totalBytes / (1024 ** 3), 2),
                'libre_gb' => round($freeBytes / (1024 ** 3), 2),
            ];
        } catch (\Exception $e) {
            \Log::warning('No se pudo obtener uso de disco: '.$e->getMessage());

            return [
                'porcentaje' => 0,
                'usado_gb' => 0,
                'total_gb' => 0,
                'libre_gb' => 0,
            ];
        }
    }

    private function obtenerCargaSistema()
    {
        try {
            if (PHP_OS_FAMILY !== 'Windows' && function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();

                return [
                    '1min' => round($load[0], 2),
                    '5min' => round($load[1], 2),
                    '15min' => round($load[2], 2),
                ];
            }
        } catch (\Exception $e) {
            \Log::warning('No se pudo obtener carga del sistema: '.$e->getMessage());
        }

        return [
            '1min' => 0,
            '5min' => 0,
            '15min' => 0,
        ];
    }

    private function obtenerConexionesDB()
    {
        try {
            $conexiones = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            $maxConexiones = DB::select("SHOW VARIABLES LIKE 'max_connections'");

            $activas = isset($conexiones[0]) ? (int) $conexiones[0]->Value : 0;
            $maximas = isset($maxConexiones[0]) ? (int) $maxConexiones[0]->Value : 100;

            return [
                'activas' => $activas,
                'maximas' => $maximas,
                'porcentaje' => round(($activas / $maximas) * 100, 2),
            ];
        } catch (\Exception $e) {
            \Log::warning('No se pudo obtener conexiones DB: '.$e->getMessage());

            return [
                'activas' => 0,
                'maximas' => 100,
                'porcentaje' => 0,
            ];
        }
    }

    private function medirTiempoRespuestaDB()
    {
        try {
            $inicio = microtime(true);
            DB::select('SELECT 1');
            $fin = microtime(true);

            return round(($fin - $inicio) * 1000, 2); // en milisegundos
        } catch (\Exception $e) {
            \Log::warning('No se pudo medir tiempo de respuesta DB: '.$e->getMessage());

            return 0;
        }
    }

    private function obtenerTamañoDB()
    {
        try {
            $dbName = config('database.connections.mysql.database');
            $resultado = DB::select('
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = ?
            ', [$dbName]);

            return isset($resultado[0]) ? (float) $resultado[0]->size_mb : 0;
        } catch (\Exception $e) {
            \Log::warning('No se pudo obtener tamaño DB: '.$e->getMessage());

            return 0;
        }
    }

    private function obtenerMemoriaPHP()
    {
        return [
            'actual_mb' => round(memory_get_usage(true) / (1024 ** 2), 2),
            'pico_mb' => round(memory_get_peak_usage(true) / (1024 ** 2), 2),
            'limite_mb' => ini_get('memory_limit'),
        ];
    }

    private function obtenerProcesosPHP()
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $output = shell_exec('tasklist /FI "IMAGENAME eq php*" /FO CSV 2>nul');

                return $output ? substr_count($output, '"php') : 0;
            } else {
                $output = shell_exec('pgrep -c php 2>/dev/null');

                return $output ? (int) trim($output) : 0;
            }
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function contarErroresRecientes()
    {
        try {
            $rutaLogs = storage_path('logs/laravel.log');
            if (! File::exists($rutaLogs)) {
                return 0;
            }

            $contenido = File::get($rutaLogs);
            $lineas = explode("\n", $contenido);
            $erroresRecientes = 0;
            $hace1Hora = now()->subHour();

            foreach (array_reverse($lineas) as $linea) {
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*ERROR/', $linea, $matches)) {
                    $fechaLog = Carbon::parse($matches[1]);
                    if ($fechaLog->gte($hace1Hora)) {
                        $erroresRecientes++;
                    } else {
                        break; // Los logs están ordenados por fecha
                    }
                }
            }

            return $erroresRecientes;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function obtenerEstadisticasCache()
    {
        // Para cache de archivos, esto es básico
        // Se podría expandir para Redis/Memcached
        return [
            'tipo' => config('cache.default'),
            'estado' => 'activo',
        ];
    }

    private function contarSesionesActivas()
    {
        try {
            // Contar archivos de sesión si usa file driver
            if (config('session.driver') === 'file') {
                $sesionPath = storage_path('framework/sessions');
                if (File::isDirectory($sesionPath)) {
                    $archivos = File::files($sesionPath);
                    $activas = 0;
                    $tiempoExpiracion = config('session.lifetime') * 60; // en segundos

                    foreach ($archivos as $archivo) {
                        if (time() - $archivo->getMTime() < $tiempoExpiracion) {
                            $activas++;
                        }
                    }

                    return $activas;
                }
            }

            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function verificarServidorWeb()
    {
        return [
            'tipo' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
            'puerto' => $_SERVER['SERVER_PORT'] ?? 80,
            'protocolo' => $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1',
        ];
    }

    private function medirTiempoRespuestaHTTP()
    {
        try {
            $inicio = microtime(true);
            $url = url('/');
            $context = stream_context_create(['http' => ['timeout' => 5]]);
            $resultado = @file_get_contents($url, false, $context);
            $fin = microtime(true);

            return $resultado !== false ? round(($fin - $inicio) * 1000, 2) : -1;
        } catch (\Exception $e) {
            return -1;
        }
    }

    private function obtenerNumeroNucleos()
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                return (int) ($_SERVER['NUMBER_OF_PROCESSORS'] ?? 1);
            } else {
                $output = shell_exec('nproc 2>/dev/null');

                return $output ? (int) trim($output) : 1;
            }
        } catch (\Exception $e) {
            return 1;
        }
    }

    private function guardarMetricasHistorico($metricas)
    {
        try {
            $historico = Cache::get('metricas_historico', []);
            $historico[now()->format('Y-m-d H:i:s')] = $metricas;

            // Mantener solo últimas 288 entradas (24h con muestras cada 5min)
            if (count($historico) > 288) {
                $historico = array_slice($historico, -288, 288, true);
            }

            Cache::put('metricas_historico', $historico, now()->addDay());
        } catch (\Exception $e) {
            \Log::error('Error guardando métricas históricas: '.$e->getMessage());
        }
    }

    private function obtenerHistorico()
    {
        return Cache::get('metricas_historico', []);
    }

    private function verificarAlertas($metricas)
    {
        $alertas = [];
        $umbrales = Cache::get('umbrales_monitoreo', $this->umbrales);

        // Verificar CPU
        if (isset($metricas['cpu']) && $metricas['cpu'] > $umbrales['cpu']) {
            $alertas[] = [
                'tipo' => 'cpu',
                'nivel' => 'warning',
                'mensaje' => "Uso de CPU alto: {$metricas['cpu']}%",
                'valor' => $metricas['cpu'],
                'umbral' => $umbrales['cpu'],
            ];
        }

        // Verificar memoria
        if (isset($metricas['memoria']['porcentaje']) && $metricas['memoria']['porcentaje'] > $umbrales['memoria']) {
            $alertas[] = [
                'tipo' => 'memoria',
                'nivel' => 'warning',
                'mensaje' => "Uso de memoria alto: {$metricas['memoria']['porcentaje']}%",
                'valor' => $metricas['memoria']['porcentaje'],
                'umbral' => $umbrales['memoria'],
            ];
        }

        // Verificar disco
        if (isset($metricas['disco']['porcentaje']) && $metricas['disco']['porcentaje'] > $umbrales['disco']) {
            $alertas[] = [
                'tipo' => 'disco',
                'nivel' => 'critical',
                'mensaje' => "Uso de disco alto: {$metricas['disco']['porcentaje']}%",
                'valor' => $metricas['disco']['porcentaje'],
                'umbral' => $umbrales['disco'],
            ];
        }

        // Verificar conexiones DB
        if (isset($metricas['db_conexiones']['activas']) && $metricas['db_conexiones']['activas'] > $umbrales['conexiones_db']) {
            $alertas[] = [
                'tipo' => 'db_conexiones',
                'nivel' => 'warning',
                'mensaje' => "Muchas conexiones DB activas: {$metricas['db_conexiones']['activas']}",
                'valor' => $metricas['db_conexiones']['activas'],
                'umbral' => $umbrales['conexiones_db'],
            ];
        }

        // Verificar errores recientes
        if (isset($metricas['errores_recientes']) && $metricas['errores_recientes'] > 10) {
            $alertas[] = [
                'tipo' => 'errores',
                'nivel' => 'critical',
                'mensaje' => "Muchos errores recientes: {$metricas['errores_recientes']} en la última hora",
                'valor' => $metricas['errores_recientes'],
                'umbral' => 10,
            ];
        }

        return $alertas;
    }

    private function calcularEstadoGeneral($metricas, $alertas)
    {
        $alertasCriticas = collect($alertas)->where('nivel', 'critical')->count();
        $alertasWarning = collect($alertas)->where('nivel', 'warning')->count();

        if ($alertasCriticas > 0) {
            return [
                'estado' => 'critical',
                'mensaje' => 'Sistema en estado crítico',
                'color' => 'danger',
            ];
        } elseif ($alertasWarning > 2) {
            return [
                'estado' => 'warning',
                'mensaje' => 'Sistema con advertencias múltiples',
                'color' => 'warning',
            ];
        } elseif ($alertasWarning > 0) {
            return [
                'estado' => 'warning',
                'mensaje' => 'Sistema con advertencias',
                'color' => 'warning',
            ];
        } else {
            return [
                'estado' => 'ok',
                'mensaje' => 'Sistema funcionando correctamente',
                'color' => 'success',
            ];
        }
    }

    private function obtenerMetricasPorPeriodo($periodo)
    {
        $historico = Cache::get('metricas_historico', []);

        $fechaInicio = match ($periodo) {
            '1h' => now()->subHour(),
            '6h' => now()->subHours(6),
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            default => now()->subDay()
        };

        return collect($historico)
            ->filter(function ($metricas, $timestamp) use ($fechaInicio) {
                return Carbon::parse($timestamp)->gte($fechaInicio);
            })
            ->values();
    }

    private function obtenerHistoricoAlertas()
    {
        return Cache::get('historico_alertas', []);
    }
}
