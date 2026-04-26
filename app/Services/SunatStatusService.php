<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SunatStatusService
{
    protected $cacheKey = 'sunat_status';
    protected $cacheMinutes = 5; // Cachear por 5 minutos

    /**
     * Verificar estado general de SUNAT
     */
    public function verificarEstado()
    {
        return Cache::remember($this->cacheKey, now()->addMinutes($this->cacheMinutes), function () {
            $resultado = [
                'timestamp' => now()->toIso8601String(),
                'disponible' => false,
                'servicios' => [],
                'latencia_ms' => null,
                'estado_general' => 'desconocido',
                'mensaje' => '',
            ];

            try {
                // Intentar verificar diferentes endpoints de SUNAT
                $servicios = [
                    'produccion' => 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService',
                    'beta' => 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem/billService',
                    'consulta' => 'https://e-factura.sunat.gob.pe/ol-it-wsconscpegem/billConsultService',
                ];

                $todosDisponibles = true;
                $algunoDisponible = false;

                foreach ($servicios as $nombre => $url) {
                    $estadoServicio = $this->verificarServicio($url);
                    $resultado['servicios'][$nombre] = $estadoServicio;

                    if ($estadoServicio['disponible']) {
                        $algunoDisponible = true;
                    } else {
                        $todosDisponibles = false;
                    }
                }

                // Determinar estado general
                if ($todosDisponibles) {
                    $resultado['estado_general'] = 'operativo';
                    $resultado['disponible'] = true;
                    $resultado['mensaje'] = 'Todos los servicios de SUNAT están operativos';
                } elseif ($algunoDisponible) {
                    $resultado['estado_general'] = 'parcial';
                    $resultado['disponible'] = true;
                    $resultado['mensaje'] = 'Algunos servicios de SUNAT están con problemas';
                } else {
                    $resultado['estado_general'] = 'no_disponible';
                    $resultado['disponible'] = false;
                    $resultado['mensaje'] = 'Los servicios de SUNAT no están disponibles';
                }

                // Calcular latencia promedio
                $latencias = array_column($resultado['servicios'], 'latencia_ms');
                $latenciasValidas = array_filter($latencias, fn($l) => $l !== null);
                if (count($latenciasValidas) > 0) {
                    $resultado['latencia_ms'] = round(array_sum($latenciasValidas) / count($latenciasValidas), 2);
                }

            } catch (\Exception $e) {
                Log::error('Error al verificar estado de SUNAT', [
                    'error' => $e->getMessage(),
                ]);
                $resultado['mensaje'] = 'Error al verificar estado: ' . $e->getMessage();
            }

            return $resultado;
        });
    }

    /**
     * Verificar un servicio específico de SUNAT
     */
    protected function verificarServicio($url)
    {
        $resultado = [
            'url' => $url,
            'disponible' => false,
            'codigo_http' => null,
            'latencia_ms' => null,
            'error' => null,
        ];

        try {
            $inicio = microtime(true);

            // Intentar una conexión HEAD (más rápida) con timeout corto
            $response = Http::timeout(10)
                ->withOptions([
                    'verify' => false, // Desactivar verificación SSL para evitar problemas
                    'allow_redirects' => false,
                ])
                ->head($url);

            $fin = microtime(true);
            $resultado['latencia_ms'] = round(($fin - $inicio) * 1000, 2);
            $resultado['codigo_http'] = $response->status();

            // Considerar disponible si responde (aunque sea con error 404, 405, etc.)
            // Lo importante es que el servidor responde
            $resultado['disponible'] = in_array($response->status(), [200, 301, 302, 401, 403, 404, 405, 500]);

        } catch (\Exception $e) {
            $resultado['error'] = $e->getMessage();
            $resultado['disponible'] = false;
        }

        return $resultado;
    }

    /**
     * Obtener historial de estado de SUNAT (últimas 24 horas)
     */
    public function obtenerHistorial()
    {
        $key = 'sunat_status_historial';
        $historial = Cache::get($key, []);

        // Agregar el estado actual al historial
        $estadoActual = $this->verificarEstado();
        $historial[] = [
            'timestamp' => now()->toIso8601String(),
            'disponible' => $estadoActual['disponible'],
            'estado_general' => $estadoActual['estado_general'],
            'latencia_ms' => $estadoActual['latencia_ms'],
        ];

        // Mantener solo las últimas 24 horas (288 puntos con mediciones cada 5 minutos)
        $historial = array_slice($historial, -288);

        Cache::put($key, $historial, now()->addHours(25));

        return $historial;
    }

    /**
     * Calcular estadísticas de disponibilidad
     */
    public function obtenerEstadisticas()
    {
        $historial = $this->obtenerHistorial();

        if (empty($historial)) {
            return [
                'disponibilidad_24h' => 100,
                'latencia_promedio' => null,
                'incidentes_24h' => 0,
                'ultimo_incidente' => null,
            ];
        }

        $totalPuntos = count($historial);
        $puntosDisponibles = count(array_filter($historial, fn($p) => $p['disponible']));
        $disponibilidad = ($puntosDisponibles / $totalPuntos) * 100;

        // Calcular latencia promedio
        $latencias = array_column($historial, 'latencia_ms');
        $latenciasValidas = array_filter($latencias, fn($l) => $l !== null);
        $latenciaPromedio = count($latenciasValidas) > 0
            ? round(array_sum($latenciasValidas) / count($latenciasValidas), 2)
            : null;

        // Contar incidentes (cambios de disponible a no disponible)
        $incidentes = 0;
        $ultimoIncidente = null;
        for ($i = 1; $i < count($historial); $i++) {
            if ($historial[$i-1]['disponible'] && !$historial[$i]['disponible']) {
                $incidentes++;
                $ultimoIncidente = $historial[$i]['timestamp'];
            }
        }

        return [
            'disponibilidad_24h' => round($disponibilidad, 2),
            'latencia_promedio' => $latenciaPromedio,
            'incidentes_24h' => $incidentes,
            'ultimo_incidente' => $ultimoIncidente,
            'puntos_monitoreados' => $totalPuntos,
        ];
    }

    /**
     * Limpiar caché de estado
     */
    public function limpiarCache()
    {
        Cache::forget($this->cacheKey);
        return true;
    }
}
