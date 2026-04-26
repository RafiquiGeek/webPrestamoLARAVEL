<?php

namespace App\Console\Commands;

use App\Models\Cuota;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruebaRendimientoDeudas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deudas:test-rendimiento
                            {--iteraciones=3 : Número de iteraciones para promediar resultados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba el rendimiento de las consultas de deudas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Iniciando prueba de rendimiento del módulo de deudas...');
        $this->newLine();

        $iteraciones = (int) $this->option('iteraciones');

        // Limpiar caché antes de empezar
        $this->comment('🧹 Limpiando caché...');
        \Artisan::call('cache:clear');
        $this->newLine();

        $tiempos = [];
        $queries = [];
        $memorias = [];

        $bar = $this->output->createProgressBar($iteraciones);
        $bar->start();

        for ($i = 1; $i <= $iteraciones; $i++) {
            // Limpiar caché entre iteraciones
            \Cache::flush();

            // Medir rendimiento
            $resultado = $this->medirRendimiento();

            $tiempos[] = $resultado['tiempo'];
            $queries[] = $resultado['queries'];
            $memorias[] = $resultado['memoria'];

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Calcular promedios
        $tiempoPromedio = array_sum($tiempos) / count($tiempos);
        $queriesPromedio = array_sum($queries) / count($queries);
        $memoriaPromedio = array_sum($memorias) / count($memorias);

        // Mostrar resultados
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 RESULTADOS DE RENDIMIENTO');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $this->table(
            ['Métrica', 'Promedio', 'Mín', 'Máx'],
            [
                [
                    'Tiempo (segundos)',
                    number_format($tiempoPromedio, 3),
                    number_format(min($tiempos), 3),
                    number_format(max($tiempos), 3)
                ],
                [
                    'Queries SQL',
                    number_format($queriesPromedio, 0),
                    number_format(min($queries), 0),
                    number_format(max($queries), 0)
                ],
                [
                    'Memoria (MB)',
                    number_format($memoriaPromedio, 2),
                    number_format(min($memorias), 2),
                    number_format(max($memorias), 2)
                ]
            ]
        );

        $this->newLine();

        // Evaluación de rendimiento
        $this->evaluarRendimiento($tiempoPromedio, $queriesPromedio);

        // Mostrar índices existentes
        $this->newLine();
        if ($this->confirm('¿Deseas ver los índices existentes en las tablas clave?', false)) {
            $this->mostrarIndices();
        }

        // Sugerencias
        $this->newLine();
        if ($tiempoPromedio > 3) {
            $this->warn('⚠️  El rendimiento puede mejorar. Considera ejecutar:');
            $this->line('   php artisan deudas:optimizar-indices');
        }

        return Command::SUCCESS;
    }

    /**
     * Mide el rendimiento de una consulta típica de deudas
     */
    protected function medirRendimiento(): array
    {
        // Habilitar registro de queries
        DB::enableQueryLog();

        // Medir memoria inicial
        $memoriaInicial = memory_get_usage(true) / 1024 / 1024; // MB

        // Iniciar timer
        $inicio = microtime(true);

        // Ejecutar consulta típica (simulando la vista de deudas)
        try {
            $query = Cuota::query()
                ->conDeuda()
                ->conRelacionesOptimizadas()
                ->whereHas('prestamo');

            // Aplicar algunos filtros típicos
            $cuotas = $query
                ->orderBy('fecha_pago', 'asc')
                ->limit(50) // Limitar para simular paginación
                ->get();

            $totalCuotas = $cuotas->count();

            // Agrupar por cliente (como hace el controlador)
            $agrupadas = $cuotas->groupBy(function ($cuota) {
                return $cuota->prestamo->cliente->id ?? 'sin-cliente';
            });

            $totalClientes = $agrupadas->count();

        } catch (\Exception $e) {
            $totalCuotas = 0;
            $totalClientes = 0;
        }

        // Medir tiempo
        $tiempo = microtime(true) - $inicio;

        // Medir memoria final
        $memoriaFinal = memory_get_usage(true) / 1024 / 1024; // MB
        $memoriaUsada = $memoriaFinal - $memoriaInicial;

        // Contar queries
        $queryLog = DB::getQueryLog();
        $totalQueries = count($queryLog);

        // Deshabilitar registro de queries
        DB::disableQueryLog();

        return [
            'tiempo' => $tiempo,
            'queries' => $totalQueries,
            'memoria' => $memoriaUsada,
            'cuotas' => $totalCuotas,
            'clientes' => $totalClientes
        ];
    }

    /**
     * Evalúa el rendimiento y da recomendaciones
     */
    protected function evaluarRendimiento(float $tiempo, int $queries): void
    {
        $this->info('📈 EVALUACIÓN DE RENDIMIENTO');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // Evaluación de tiempo
        if ($tiempo < 1) {
            $this->line('⚡ Tiempo: <fg=green>EXCELENTE</> (< 1s)');
        } elseif ($tiempo < 3) {
            $this->line('✅ Tiempo: <fg=green>BUENO</> (1-3s)');
        } elseif ($tiempo < 5) {
            $this->line('⚠️  Tiempo: <fg=yellow>ACEPTABLE</> (3-5s)');
        } else {
            $this->line('❌ Tiempo: <fg=red>NECESITA OPTIMIZACIÓN</> (> 5s)');
        }

        // Evaluación de queries
        if ($queries < 30) {
            $this->line('⚡ Queries: <fg=green>EXCELENTE</> (< 30 queries)');
        } elseif ($queries < 100) {
            $this->line('✅ Queries: <fg=green>BUENO</> (30-100 queries)');
        } elseif ($queries < 200) {
            $this->line('⚠️  Queries: <fg=yellow>ACEPTABLE</> (100-200 queries)');
        } else {
            $this->line('❌ Queries: <fg=red>NECESITA OPTIMIZACIÓN</> (> 200 queries)');
        }

        $this->newLine();

        // Comparación con valores de referencia
        $this->comment('📊 Valores de Referencia:');
        $this->line('   • Tiempo óptimo: < 3 segundos');
        $this->line('   • Queries óptimas: < 30 queries');
        $this->line('   • Memoria óptima: < 100 MB');
    }

    /**
     * Muestra los índices existentes en tablas clave
     */
    protected function mostrarIndices(): void
    {
        $this->newLine();
        $this->info('📑 ÍNDICES EN TABLAS CLAVE');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $tablas = ['cuotas', 'mora_cuota', 'cartera_jcc', 'cartera_asesor', 'cartera_analista'];

        foreach ($tablas as $tabla) {
            try {
                $indices = DB::select("
                    SELECT
                        index_name AS 'Índice',
                        GROUP_CONCAT(column_name ORDER BY seq_in_index) AS 'Columnas',
                        index_type AS 'Tipo'
                    FROM information_schema.statistics
                    WHERE table_schema = DATABASE()
                    AND table_name = ?
                    AND index_name LIKE 'idx_%'
                    GROUP BY index_name, index_type
                ", [$tabla]);

                if (count($indices) > 0) {
                    $this->line("<fg=cyan>{$tabla}</>:");
                    foreach ($indices as $index) {
                        $this->line("   ✓ {$index->Índice} ({$index->Columnas})");
                    }
                } else {
                    $this->line("<fg=yellow>{$tabla}</>: Sin índices optimizados");
                }

                $this->newLine();
            } catch (\Exception $e) {
                $this->error("Error al consultar tabla {$tabla}");
            }
        }
    }
}
