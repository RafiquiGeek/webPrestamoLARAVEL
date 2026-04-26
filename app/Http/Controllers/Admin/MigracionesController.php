<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class MigracionesController extends Controller
{
    /**
     * Display a listing of migrations.
     */
    public function index()
    {
        try {
            // Obtener estado de migraciones usando Artisan
            Artisan::call('migrate:status');
            $output = Artisan::output();

            // Parsear la salida
            $migrations = $this->parseMigrationStatus($output);

            // Obtener archivos de migración del directorio
            $migrationFiles = $this->getMigrationFiles();

            // Combinar información
            $migrationsData = $this->combineMigrationData($migrations, $migrationFiles);

            return view('admin.migraciones.index', compact('migrationsData'));

        } catch (\Exception $e) {
            Log::error('Error al obtener migraciones: '.$e->getMessage());

            return view('admin.migraciones.index', [
                'migrationsData' => [],
                'error' => 'Error al cargar las migraciones: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Execute a specific migration
     */
    public function ejecutar(Request $request)
    {
        $request->validate([
            'migration' => 'required|string',
        ]);

        try {
            $migration = $request->migration;

            // Ejecutar la migración específica
            Artisan::call('migrate', [
                '--path' => 'database/migrations/'.$migration,
                '--force' => true,
            ]);

            $output = Artisan::output();

            Log::info("Migración ejecutada: {$migration} por usuario: ".auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Migración ejecutada correctamente.',
                'output' => $output,
            ]);

        } catch (\Exception $e) {
            Log::error("Error al ejecutar migración {$migration}: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar la migración: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rollback the last batch of migrations
     */
    public function rollback(Request $request)
    {
        try {
            $steps = $request->input('steps', 1);

            Artisan::call('migrate:rollback', [
                '--step' => $steps,
                '--force' => true,
            ]);

            $output = Artisan::output();

            Log::info("Rollback ejecutado ({$steps} pasos) por usuario: ".auth()->id());

            return response()->json([
                'success' => true,
                'message' => "Rollback ejecutado correctamente ({$steps} pasos).",
                'output' => $output,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al hacer rollback: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al hacer rollback: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute all pending migrations
     */
    public function ejecutarTodas(Request $request)
    {
        try {
            Artisan::call('migrate', ['--force' => true]);

            $output = Artisan::output();

            Log::info('Todas las migraciones ejecutadas por usuario: '.auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Todas las migraciones pendientes han sido ejecutadas.',
                'output' => $output,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al ejecutar todas las migraciones: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar las migraciones: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fresh migration (reset and migrate)
     */
    public function fresh(Request $request)
    {
        try {
            // PELIGROSO: Solo para desarrollo
            if (config('app.env') === 'production') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta operación no está permitida en producción.',
                ], 403);
            }

            Artisan::call('migrate:fresh', ['--force' => true]);

            $output = Artisan::output();

            Log::warning('Fresh migration ejecutado por usuario: '.auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Base de datos reiniciada y migraciones ejecutadas.',
                'output' => $output,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al hacer fresh migration: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al reiniciar la base de datos: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get database status
     */
    public function estado()
    {
        try {
            $stats = [
                'total_migraciones' => $this->getTotalMigrations(),
                'ejecutadas' => $this->getExecutedMigrations(),
                'pendientes' => $this->getPendingMigrations(),
                'ultimo_batch' => $this->getLastBatch(),
                'conexion_bd' => $this->checkDatabaseConnection(),
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener estado: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse migration status output
     */
    private function parseMigrationStatus($output)
    {
        $lines = explode("\n", $output);
        $migrations = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and header line
            if (empty($line) || strpos($line, 'Migration name') !== false) {
                continue;
            }

            // Parse lines that contain migration information
            // Format: "  migration_name [dots] [batch_number] Status"
            if (preg_match('/^\s*(\w+_\w+_\w+_\w+_.*?)\s+[.\s]+(?:\[(\d+)\])?\s*(Pending|Ran)\s*$/', $line, $matches)) {
                $migrationName = $matches[1];
                $batch = isset($matches[2]) ? $matches[2] : null;
                $status = $matches[3];

                $migrations[] = [
                    'name' => $migrationName,
                    'status' => $status === 'Ran' ? 'ejecutada' : 'pendiente',
                    'batch' => $batch,
                ];
            }
        }

        return $migrations;
    }

    /**
     * Get migration files from directory
     */
    private function getMigrationFiles()
    {
        $path = database_path('migrations');
        $files = File::files($path);
        $migrations = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();
            if (pathinfo($filename, PATHINFO_EXTENSION) === 'php') {
                $migrations[$filename] = [
                    'filename' => $filename,
                    'path' => $file->getRealPath(),
                    'size' => $file->getSize(),
                    'modified' => Carbon::createFromTimestamp($file->getMTime()),
                    'created' => Carbon::createFromTimestamp($file->getCTime()),
                ];
            }
        }

        return $migrations;
    }

    /**
     * Combine migration status with file information
     */
    private function combineMigrationData($migrations, $files)
    {
        $combined = [];

        // Agregar migraciones del estado
        foreach ($migrations as $migration) {
            $filename = $migration['name'].'.php';
            $fileInfo = $files[$filename] ?? null;

            $combined[] = [
                'name' => $migration['name'],
                'filename' => $filename,
                'status' => $migration['status'],
                'batch' => $migration['batch'],
                'file_exists' => $fileInfo !== null,
                'size' => $fileInfo['size'] ?? null,
                'modified' => $fileInfo['modified'] ?? null,
                'created' => $fileInfo['created'] ?? null,
            ];
        }

        // Agregar archivos que no están en el estado (nuevos)
        foreach ($files as $filename => $fileInfo) {
            $migrationName = pathinfo($filename, PATHINFO_FILENAME);

            // Verificar si ya existe
            $exists = false;
            foreach ($combined as $item) {
                if ($item['name'] === $migrationName) {
                    $exists = true;
                    break;
                }
            }

            if (! $exists) {
                $combined[] = [
                    'name' => $migrationName,
                    'filename' => $filename,
                    'status' => 'pendiente',
                    'batch' => null,
                    'file_exists' => true,
                    'size' => $fileInfo['size'],
                    'modified' => $fileInfo['modified'],
                    'created' => $fileInfo['created'],
                ];
            }
        }

        // Ordenar por nombre (que incluye timestamp)
        usort($combined, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $combined;
    }

    /**
     * Get total number of migrations
     */
    private function getTotalMigrations()
    {
        return count(File::files(database_path('migrations')));
    }

    /**
     * Get number of executed migrations
     */
    private function getExecutedMigrations()
    {
        try {
            return DB::table('migrations')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get number of pending migrations
     */
    private function getPendingMigrations()
    {
        return $this->getTotalMigrations() - $this->getExecutedMigrations();
    }

    /**
     * Get last batch number
     */
    private function getLastBatch()
    {
        try {
            return DB::table('migrations')->max('batch') ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check database connection
     */
    private function checkDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();

            return 'conectada';
        } catch (\Exception $e) {
            return 'desconectada';
        }
    }
}
