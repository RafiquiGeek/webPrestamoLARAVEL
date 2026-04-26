<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\MonitoreoController;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RecolectarMetricas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoreo:recolectar 
                           {--alertas : Solo verificar alertas sin recolectar métricas}
                           {--limpiar : Limpiar métricas antiguas}
                           {--debug : Mostrar información detallada}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Recolecta métricas del sistema para el módulo de monitoreo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $inicio = microtime(true);

        if ($this->option('limpiar')) {
            return $this->limpiarMetricasAntiguas();
        }

        if ($this->option('alertas')) {
            return $this->verificarAlertas();
        }

        $this->info('🔄 Recolectando métricas del sistema...');

        try {
            // Crear una instancia del controlador para acceder a los métodos privados
            $monitoreo = new MonitoreoController;

            // Usar reflexión para acceder al método privado
            $reflection = new \ReflectionClass($monitoreo);
            $metodoObtenerMetricas = $reflection->getMethod('obtenerMetricasActuales');
            $metodoObtenerMetricas->setAccessible(true);

            // Obtener métricas
            $metricas = $metodoObtenerMetricas->invoke($monitoreo);

            if (isset($metricas['error'])) {
                $this->error('❌ Error obteniendo métricas: '.$metricas['error']);

                return 1;
            }

            // Guardar en histórico con timestamp más preciso
            $timestamp = now()->format('Y-m-d H:i:s');
            $historico = Cache::get('metricas_historico', []);
            $historico[$timestamp] = $metricas;

            // Mantener solo métricas de los últimos 7 días (2016 muestras con 5min de intervalo)
            $fechaLimite = now()->subDays(7);
            $historico = collect($historico)
                ->filter(function ($metricas, $timestamp) use ($fechaLimite) {
                    return Carbon::parse($timestamp)->gte($fechaLimite);
                })
                ->toArray();

            Cache::put('metricas_historico', $historico, now()->addWeek());

            // Verificar alertas
            $alertas = $this->verificarAlertasInterno($metricas);

            // Mostrar información si está en modo debug
            if ($this->option('debug')) {
                $this->mostrarInformacionDetallada($metricas, $alertas);
            }

            $duracion = round((microtime(true) - $inicio) * 1000, 2);
            $this->info("✅ Métricas recolectadas correctamente en {$duracion}ms");

            if (count($alertas) > 0) {
                $this->warn('⚠️  Se encontraron '.count($alertas).' alertas activas');
                foreach ($alertas as $alerta) {
                    $icon = $alerta['nivel'] === 'critical' ? '🔴' : '🟡';
                    $this->line("   {$icon} {$alerta['mensaje']}");
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error recolectando métricas: '.$e->getMessage());

            if ($this->option('debug')) {
                $this->error('Stack trace:');
                $this->error($e->getTraceAsString());
            }

            return 1;
        }
    }

    private function verificarAlertas()
    {
        $this->info('🔍 Verificando alertas del sistema...');

        try {
            $monitoreo = new MonitoreoController;
            $reflection = new \ReflectionClass($monitoreo);

            // Obtener métricas actuales
            $metodoObtenerMetricas = $reflection->getMethod('obtenerMetricasActuales');
            $metodoObtenerMetricas->setAccessible(true);
            $metricas = $metodoObtenerMetricas->invoke($monitoreo);

            // Verificar alertas
            $alertas = $this->verificarAlertasInterno($metricas);

            if (count($alertas) === 0) {
                $this->info('✅ No se encontraron alertas activas');

                return 0;
            }

            $this->warn('⚠️  Se encontraron '.count($alertas).' alertas:');

            foreach ($alertas as $alerta) {
                $icon = $alerta['nivel'] === 'critical' ? '🔴' : '🟡';
                $this->line("   {$icon} [{$alerta['tipo']}] {$alerta['mensaje']}");
            }

            // Guardar alertas en caché para mostrar en interfaz web
            Cache::put('alertas_sistema', $alertas, now()->addHour());

            return count($alertas) > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error('❌ Error verificando alertas: '.$e->getMessage());

            return 1;
        }
    }

    private function limpiarMetricasAntiguas()
    {
        $this->info('🧹 Limpiando métricas antiguas...');

        try {
            $historico = Cache::get('metricas_historico', []);
            $totalAntes = count($historico);

            // Eliminar métricas más antiguas de 7 días
            $fechaLimite = now()->subDays(7);
            $historicoLimpio = collect($historico)
                ->filter(function ($metricas, $timestamp) use ($fechaLimite) {
                    return Carbon::parse($timestamp)->gte($fechaLimite);
                })
                ->toArray();

            $eliminadas = $totalAntes - count($historicoLimpio);

            Cache::put('metricas_historico', $historicoLimpio, now()->addWeek());

            $this->info("✅ Limpieza completada: {$eliminadas} métricas eliminadas, ".count($historicoLimpio).' conservadas');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error limpiando métricas: '.$e->getMessage());

            return 1;
        }
    }

    private function verificarAlertasInterno($metricas)
    {
        $alertas = [];

        // Umbrales por defecto
        $umbrales = Cache::get('umbrales_monitoreo', [
            'cpu' => 80.0,
            'memoria' => 85.0,
            'disco' => 90.0,
            'conexiones_db' => 80,
            'tiempo_respuesta' => 2.0,
        ]);

        // Verificar CPU
        if (isset($metricas['cpu']) && $metricas['cpu'] > $umbrales['cpu']) {
            $alertas[] = [
                'tipo' => 'cpu',
                'nivel' => $metricas['cpu'] > 95 ? 'critical' : 'warning',
                'mensaje' => "Uso de CPU alto: {$metricas['cpu']}%",
                'valor' => $metricas['cpu'],
                'umbral' => $umbrales['cpu'],
            ];
        }

        // Verificar memoria
        if (isset($metricas['memoria']['porcentaje']) && $metricas['memoria']['porcentaje'] > $umbrales['memoria']) {
            $alertas[] = [
                'tipo' => 'memoria',
                'nivel' => $metricas['memoria']['porcentaje'] > 95 ? 'critical' : 'warning',
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

        // Verificar tiempo de respuesta de DB
        if (isset($metricas['db_tiempo_respuesta']) && $metricas['db_tiempo_respuesta'] > ($umbrales['tiempo_respuesta'] * 1000)) {
            $alertas[] = [
                'tipo' => 'db_tiempo',
                'nivel' => 'warning',
                'mensaje' => "Tiempo de respuesta DB alto: {$metricas['db_tiempo_respuesta']}ms",
                'valor' => $metricas['db_tiempo_respuesta'],
                'umbral' => $umbrales['tiempo_respuesta'] * 1000,
            ];
        }

        // Verificar errores recientes
        if (isset($metricas['errores_recientes']) && $metricas['errores_recientes'] > 10) {
            $alertas[] = [
                'tipo' => 'errores',
                'nivel' => $metricas['errores_recientes'] > 50 ? 'critical' : 'warning',
                'mensaje' => "Muchos errores recientes: {$metricas['errores_recientes']} en la última hora",
                'valor' => $metricas['errores_recientes'],
                'umbral' => 10,
            ];
        }

        return $alertas;
    }

    private function mostrarInformacionDetallada($metricas, $alertas)
    {
        $this->line('');
        $this->line('📊 <comment>Información detallada de métricas:</comment>');
        $this->line('================================');

        // CPU
        if (isset($metricas['cpu'])) {
            $this->line("💻 CPU: <info>{$metricas['cpu']}%</info>");
        }

        // Memoria
        if (isset($metricas['memoria'])) {
            $memoria = $metricas['memoria'];
            $this->line("🧠 Memoria: <info>{$memoria['porcentaje']}%</info> ({$memoria['usado_mb']}MB / {$memoria['total_mb']}MB)");
        }

        // Disco
        if (isset($metricas['disco'])) {
            $disco = $metricas['disco'];
            $this->line("💾 Disco: <info>{$disco['porcentaje']}%</info> ({$disco['usado_gb']}GB / {$disco['total_gb']}GB)");
        }

        // Base de datos
        if (isset($metricas['db_tiempo_respuesta'])) {
            $this->line("🗄️  DB Tiempo: <info>{$metricas['db_tiempo_respuesta']}ms</info>");
        }

        if (isset($metricas['db_conexiones'])) {
            $db = $metricas['db_conexiones'];
            $this->line("🔗 DB Conexiones: <info>{$db['activas']}/{$db['maximas']}</info> ({$db['porcentaje']}%)");
        }

        // PHP
        if (isset($metricas['php_memoria'])) {
            $php = $metricas['php_memoria'];
            $this->line("🐘 PHP Memoria: <info>{$php['actual_mb']}MB</info> (pico: {$php['pico_mb']}MB)");
        }

        // Errores
        if (isset($metricas['errores_recientes'])) {
            $color = $metricas['errores_recientes'] > 0 ? 'error' : 'info';
            $this->line("❌ Errores (1h): <{$color}>{$metricas['errores_recientes']}</{$color}>");
        }

        // Load average (solo Linux/Unix)
        if (isset($metricas['carga_sistema']['1min'])) {
            $carga = $metricas['carga_sistema'];
            $this->line("📈 Load Avg: <info>{$carga['1min']} {$carga['5min']} {$carga['15min']}</info>");
        }

        $this->line('');
    }
}
