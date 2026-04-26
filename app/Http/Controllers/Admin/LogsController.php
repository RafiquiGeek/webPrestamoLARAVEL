<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LogsController extends Controller
{
    // Niveles de log que consideramos como incidencias
    private $nivelesIncidencias = ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY', 'WARNING'];

    // Categorías de errores del sistema
    private $categorias = [
        'database' => ['keywords' => ['database', 'mysql', 'connection', 'query', 'sql'], 'color' => 'danger', 'icon' => 'fa-database'],
        'authentication' => ['keywords' => ['auth', 'login', 'permission', 'unauthorized'], 'color' => 'warning', 'icon' => 'fa-user-shield'],
        'payment' => ['keywords' => ['pago', 'cuota', 'operacion', 'prestamo', 'monto'], 'color' => 'info', 'icon' => 'fa-money-bill'],
        'file_system' => ['keywords' => ['file', 'storage', 'pdf', 'upload', 'download'], 'color' => 'secondary', 'icon' => 'fa-file'],
        'external_api' => ['keywords' => ['api', 'curl', 'http', 'request', 'timeout'], 'color' => 'primary', 'icon' => 'fa-globe'],
        'backup' => ['keywords' => ['backup', 'respaldo', 'mysqldump', 'restore'], 'color' => 'success', 'icon' => 'fa-archive'],
        'system' => ['keywords' => ['system', 'memory', 'disk', 'cpu', 'server'], 'color' => 'dark', 'icon' => 'fa-server'],
    ];

    public function index(Request $request)
    {
        $filtros = [
            'nivel' => $request->get('nivel', ''),
            'categoria' => $request->get('categoria', ''),
            'fecha_desde' => $request->get('fecha_desde', now()->subDays(7)->format('Y-m-d')),
            'fecha_hasta' => $request->get('fecha_hasta', now()->format('Y-m-d')),
            'buscar' => $request->get('buscar', ''),
            'por_pagina' => $request->get('por_pagina', 50),
        ];

        // Obtener logs procesados
        $logs = $this->obtenerLogs($filtros);

        // Estadísticas para el dashboard
        $estadisticas = $this->generarEstadisticas($logs);

        // Tendencias por día
        $tendencias = $this->generarTendencias($logs);

        if ($request->ajax()) {
            return response()->json([
                'logs_html' => view('admin.logs.partials.tabla-logs', compact('logs'))->render(),
                'estadisticas' => $estadisticas,
                'tendencias' => $tendencias,
            ]);
        }

        return view('admin.logs.index', [
            'logs' => $logs,
            'filtros' => $filtros,
            'estadisticas' => $estadisticas,
            'tendencias' => $tendencias,
            'niveles' => $this->nivelesIncidencias,
            'categorias' => array_keys($this->categorias),
        ]);
    }

    public function detalle($id)
    {
        $logs = $this->obtenerTodosLogs();
        $log = $logs->firstWhere('id', $id);

        if (! $log) {
            return redirect()->back()->with('error', 'Log no encontrado');
        }

        // Buscar logs relacionados (mismo contexto/usuario)
        $logsRelacionados = $logs->where('context.userId', $log['context']['userId'] ?? null)
            ->where('id', '!=', $id)
            ->take(10);

        return view('admin.logs.detalle', [
            'log' => $log,
            'logsRelacionados' => $logsRelacionados,
        ]);
    }

    public function limpiarLogs(Request $request)
    {
        $request->validate([
            'tipo_limpieza' => 'required|in:actual,antiguos',
            'dias_antiguedad' => 'required_if:tipo_limpieza,antiguos|integer|min:1|max:365',
            'confirmar' => 'required|accepted',
        ]);

        try {
            $tipoLimpieza = $request->tipo_limpieza;

            if ($tipoLimpieza === 'actual') {
                // Limpiar el archivo laravel.log actual
                $archivoActual = storage_path('logs/laravel.log');

                if (File::exists($archivoActual)) {
                    // Contar registros antes de limpiar
                    $contenido = File::get($archivoActual);
                    $registrosEliminados = substr_count($contenido, '['.now()->format('Y')) +
                                         substr_count($contenido, 'local.') +
                                         substr_count($contenido, 'laravel.');

                    // Vaciar el contenido del archivo
                    File::put($archivoActual, '');

                    return redirect()->back()->with('success',
                        "Archivo laravel.log limpiado correctamente. Se eliminaron {$registrosEliminados} registros."
                    );
                } else {
                    return redirect()->back()->with('warning', 'El archivo laravel.log no existe.');
                }

            } else {
                // Eliminar archivos antiguos (funcionalidad original)
                $diasAntiguedad = $request->dias_antiguedad;
                $fechaLimite = now()->subDays($diasAntiguedad);

                $archivosLog = $this->obtenerArchivosLog();
                $archivosEliminados = 0;
                $registrosEliminados = 0;

                foreach ($archivosLog as $archivo) {
                    $fechaArchivo = $this->extraerFechaArchivo($archivo);

                    if ($fechaArchivo && $fechaArchivo->lt($fechaLimite)) {
                        if (File::exists($archivo)) {
                            // Contar registros antes de eliminar
                            $contenido = File::get($archivo);
                            $registrosEliminados += substr_count($contenido, 'local.') + substr_count($contenido, 'laravel.');

                            File::delete($archivo);
                            $archivosEliminados++;
                        }
                    }
                }

                return redirect()->back()->with('success',
                    "Limpieza completada: {$archivosEliminados} archivos eliminados, {$registrosEliminados} registros limpiados."
                );
            }

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al limpiar logs: '.$e->getMessage());
        }
    }

    public function exportar(Request $request)
    {
        $filtros = [
            'nivel' => $request->get('nivel', ''),
            'categoria' => $request->get('categoria', ''),
            'fecha_desde' => $request->get('fecha_desde', now()->subDays(7)->format('Y-m-d')),
            'fecha_hasta' => $request->get('fecha_hasta', now()->format('Y-m-d')),
            'buscar' => $request->get('buscar', ''),
        ];

        $logs = $this->obtenerLogs($filtros, 10000); // Máximo 10k registros para exportar

        $nombreArchivo = 'logs_incidencias_'.now()->format('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$nombreArchivo}\"",
        ];

        $callback = function () use ($logs) {
            $handle = fopen('php://output', 'w');

            // Encabezados CSV
            fputcsv($handle, [
                'Fecha/Hora',
                'Nivel',
                'Categoría',
                'Mensaje',
                'Archivo',
                'Línea',
                'Usuario ID',
                'Stack Trace',
            ]);

            // Datos
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log['fecha']->format('Y-m-d H:i:s'),
                    $log['nivel'],
                    $log['categoria'],
                    $log['mensaje'],
                    $log['archivo'] ?? '',
                    $log['linea'] ?? '',
                    $log['context']['userId'] ?? '',
                    Str::limit($log['stack_trace'] ?? '', 500),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function estadisticas(Request $request)
    {
        $filtros = [
            'fecha_desde' => $request->get('fecha_desde', now()->subDays(30)->format('Y-m-d')),
            'fecha_hasta' => $request->get('fecha_hasta', now()->format('Y-m-d')),
        ];

        $logs = $this->obtenerLogs($filtros, 50000);

        $estadisticas = [
            'total_incidencias' => $logs->count(),
            'por_nivel' => $logs->groupBy('nivel')->map->count(),
            'por_categoria' => $logs->groupBy('categoria')->map->count(),
            'por_dia' => $logs->groupBy(function ($log) {
                return $log['fecha']->format('Y-m-d');
            })->map->count(),
            'por_hora' => $logs->groupBy(function ($log) {
                return $log['fecha']->format('H');
            })->map->count(),
            'top_errores' => $logs->groupBy('mensaje')->map->count()->sortDesc()->take(10),
        ];

        return response()->json($estadisticas);
    }

    private function obtenerLogs($filtros, $limite = 1000)
    {
        $logs = collect();
        $archivosLog = $this->obtenerArchivosLog();

        foreach ($archivosLog as $archivo) {
            if ($logs->count() >= $limite) {
                break;
            }

            $fechaArchivo = $this->extraerFechaArchivo($archivo);

            // Filtrar por rango de fechas
            if ($fechaArchivo) {
                $fechaDesde = Carbon::parse($filtros['fecha_desde'])->startOfDay();
                $fechaHasta = Carbon::parse($filtros['fecha_hasta'])->endOfDay();

                if ($fechaArchivo->lt($fechaDesde) || $fechaArchivo->gt($fechaHasta)) {
                    continue;
                }
            }

            $logsArchivo = $this->procesarArchivoLog($archivo, $filtros, $limite - $logs->count());
            $logs = $logs->merge($logsArchivo);
        }

        return $logs->sortByDesc('fecha')->take($limite);
    }

    private function obtenerTodosLogs()
    {
        return $this->obtenerLogs([
            'nivel' => '',
            'categoria' => '',
            'fecha_desde' => now()->subDays(30)->format('Y-m-d'),
            'fecha_hasta' => now()->format('Y-m-d'),
            'buscar' => '',
        ], 50000);
    }

    private function procesarArchivoLog($rutaArchivo, $filtros, $limite)
    {
        $logs = collect();

        if (! File::exists($rutaArchivo)) {
            return $logs;
        }

        $contenido = File::get($rutaArchivo);
        $lineas = explode("\n", $contenido);

        foreach ($lineas as $index => $linea) {
            if ($logs->count() >= $limite) {
                break;
            }

            $logEntry = $this->parsearLineaLog($linea, $index);

            if ($logEntry && $this->cumpleFiltros($logEntry, $filtros)) {
                $logs->push($logEntry);
            }
        }

        return $logs;
    }

    private function parsearLineaLog($linea, $index)
    {
        // Patrón para logs de Laravel: [2025-08-01 23:11:51] laravel.ERROR: mensaje
        if (! preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (?:local|laravel)\.(\w+): (.+)/', $linea, $matches)) {
            return null;
        }

        $fecha = Carbon::parse($matches[1]);
        $nivel = $matches[2];
        $mensaje = $matches[3];

        // Solo procesar niveles de incidencias
        if (! in_array($nivel, $this->nivelesIncidencias)) {
            return null;
        }

        // Extraer contexto adicional si existe
        $context = $this->extraerContexto($mensaje);
        $categoria = $this->determinarCategoria($mensaje);
        $mensajeLimpio = $this->limpiarMensaje($mensaje);

        return [
            'id' => md5($linea.$index),
            'fecha' => $fecha,
            'nivel' => $nivel,
            'mensaje' => $mensajeLimpio,
            'mensaje_completo' => $mensaje,
            'categoria' => $categoria,
            'explicacion' => $this->generarExplicacion($mensajeLimpio, $nivel, $categoria),
            'archivo' => $context['archivo'] ?? null,
            'linea' => $context['linea'] ?? null,
            'stack_trace' => $context['stack_trace'] ?? null,
            'context' => $context,
        ];
    }

    private function extraerContexto($mensaje)
    {
        $context = [];

        // Buscar información de usuario
        if (preg_match('/"userId":(\d+)/', $mensaje, $matches)) {
            $context['userId'] = $matches[1];
        }

        // Buscar archivo y línea
        if (preg_match('/at (.+):(\d+)/', $mensaje, $matches)) {
            $context['archivo'] = basename($matches[1]);
            $context['linea'] = $matches[2];
        }

        // Buscar stack trace
        if (preg_match('/Stack trace:(.+)/', $mensaje, $matches)) {
            $context['stack_trace'] = trim($matches[1]);
        }

        return $context;
    }

    private function limpiarMensaje($mensaje)
    {
        // Remover información JSON y stack traces para mostrar mensaje limpio
        $mensaje = preg_replace('/\{"userId.*?\}/', '', $mensaje);
        $mensaje = preg_replace('/Stack trace:.*/', '', $mensaje);
        $mensaje = preg_replace('/\[object\].*/', '', $mensaje);

        return trim($mensaje);
    }

    private function determinarCategoria($mensaje)
    {
        $mensajeLower = strtolower($mensaje);

        foreach ($this->categorias as $categoria => $config) {
            foreach ($config['keywords'] as $keyword) {
                if (strpos($mensajeLower, $keyword) !== false) {
                    return $categoria;
                }
            }
        }

        return 'system'; // Categoría por defecto
    }

    private function cumpleFiltros($log, $filtros)
    {
        // Filtro por nivel
        if ($filtros['nivel'] && $log['nivel'] !== $filtros['nivel']) {
            return false;
        }

        // Filtro por categoría
        if ($filtros['categoria'] && $log['categoria'] !== $filtros['categoria']) {
            return false;
        }

        // Filtro por búsqueda
        if ($filtros['buscar']) {
            $buscar = strtolower($filtros['buscar']);
            $contenido = strtolower($log['mensaje'].' '.($log['archivo'] ?? ''));

            if (strpos($contenido, $buscar) === false) {
                return false;
            }
        }

        return true;
    }

    private function obtenerArchivosLog()
    {
        $rutaLogs = storage_path('logs');
        $archivos = File::glob($rutaLogs.'/laravel*.log');

        // Ordenar por fecha más reciente primero
        usort($archivos, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return $archivos;
    }

    private function extraerFechaArchivo($rutaArchivo)
    {
        $nombreArchivo = basename($rutaArchivo);

        // laravel-2025-08-01.log
        if (preg_match('/laravel-(\d{4}-\d{2}-\d{2})\.log/', $nombreArchivo, $matches)) {
            return Carbon::parse($matches[1]);
        }

        // laravel.log (archivo actual)
        if ($nombreArchivo === 'laravel.log') {
            return now();
        }

        return null;
    }

    private function generarEstadisticas($logs)
    {
        return [
            'total' => $logs->count(),
            'errores' => $logs->where('nivel', 'ERROR')->count(),
            'warnings' => $logs->where('nivel', 'WARNING')->count(),
            'critical' => $logs->where('nivel', 'CRITICAL')->count(),
            'por_categoria' => $logs->groupBy('categoria')->map->count(),
            'ultimas_24h' => $logs->where('fecha', '>', now()->subDay())->count(),
            'promedio_diario' => round($logs->count() / max(1, $logs->pluck('fecha')->map->format('Y-m-d')->unique()->count())),
        ];
    }

    private function generarTendencias($logs)
    {
        return $logs->groupBy(function ($log) {
            return $log['fecha']->format('Y-m-d');
        })->map(function ($logsDia) {
            return [
                'total' => $logsDia->count(),
                'errores' => $logsDia->where('nivel', 'ERROR')->count(),
                'warnings' => $logsDia->where('nivel', 'WARNING')->count(),
            ];
        })->sortKeys();
    }

    public function getCategoriaConfig($categoria)
    {
        return $this->categorias[$categoria] ?? $this->categorias['system'];
    }

    private function generarExplicacion($mensaje, $nivel, $categoria)
    {
        $mensaje = strtolower($mensaje);

        // Explicaciones específicas basadas en patrones comunes
        $explicaciones = [
            // Errores de Base de Datos
            'connection refused' => 'No se puede conectar a la base de datos. Verificar servicio MySQL.',
            'table doesn\'t exist' => 'Tabla de base de datos no existe. Ejecutar migraciones.',
            'duplicate entry' => 'Intento de insertar un registro duplicado en la base de datos.',
            'syntax error' => 'Error de sintaxis en consulta SQL o código PHP.',
            'access denied' => 'Usuario no tiene permisos para acceder a la base de datos.',

            // Errores de Autenticación
            'unauthorized' => 'Usuario no tiene permisos para realizar esta acción.',
            'unauthenticated' => 'Usuario debe iniciar sesión para acceder.',
            'invalid credentials' => 'Credenciales de acceso incorrectas.',
            'token expired' => 'Sesión ha expirado, debe iniciar sesión nuevamente.',

            // Errores de Archivos
            'file not found' => 'Archivo requerido no se encuentra en el servidor.',
            'permission denied' => 'Sin permisos para leer/escribir archivo.',
            'disk full' => 'Espacio en disco insuficiente para guardar archivos.',
            'upload failed' => 'Error al subir archivo. Verificar tamaño y formato.',

            // Errores de API Externa
            'timeout' => 'Servicio externo no responde. Verificar conexión a internet.',
            'curl error' => 'Error de comunicación con servicio externo.',
            'api limit' => 'Límite de consultas API alcanzado.',

            // Errores de Sistema
            'memory limit' => 'Memoria PHP insuficiente. Aumentar memory_limit.',
            'execution time' => 'Script tardó demasiado en ejecutarse.',
            'undefined variable' => 'Variable no definida en el código.',
            'class not found' => 'Clase PHP no encontrada. Verificar autoload.',

            // Errores de Pagos/Operaciones
            'monto' => 'Error relacionado con cálculo de montos o pagos.',
            'cuota' => 'Problema con el procesamiento de cuotas.',
            'prestamo' => 'Error en operaciones de préstamos.',
            'cliente' => 'Problema con datos de cliente.',
        ];

        // Buscar explicación específica
        foreach ($explicaciones as $patron => $explicacion) {
            if (strpos($mensaje, $patron) !== false) {
                return $explicacion;
            }
        }

        // Explicaciones genéricas por nivel y categoría
        $explicacionesGenericas = [
            'ERROR' => [
                'database' => 'Error en operación de base de datos.',
                'authentication' => 'Problema de autenticación o permisos.',
                'payment' => 'Error en procesamiento de pagos.',
                'file_system' => 'Error al manejar archivos del sistema.',
                'external_api' => 'Error de comunicación con servicio externo.',
                'system' => 'Error interno del sistema.',
            ],
            'WARNING' => [
                'database' => 'Advertencia en operación de base de datos.',
                'authentication' => 'Advertencia de seguridad o permisos.',
                'payment' => 'Advertencia en procesamiento de pagos.',
                'file_system' => 'Advertencia en manejo de archivos.',
                'external_api' => 'Advertencia en comunicación externa.',
                'system' => 'Advertencia del sistema.',
            ],
            'CRITICAL' => [
                'default' => 'Error crítico que requiere atención inmediata.',
            ],
            'EMERGENCY' => [
                'default' => 'Emergencia del sistema que requiere acción urgente.',
            ],
        ];

        return $explicacionesGenericas[$nivel][$categoria] ??
               $explicacionesGenericas[$nivel]['default'] ??
               'Incidencia del sistema que requiere revisión.';
    }
}
