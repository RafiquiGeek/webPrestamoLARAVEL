<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class RespaldosController extends Controller
{
    public function index()
    {
        // Obtener lista de respaldos existentes
        $respaldos = $this->obtenerRespaldos();

        // Información del sistema
        $info = [
            'base_datos' => config('database.connections.mysql.database'),
            'servidor' => config('database.connections.mysql.host'),
            'espacio_usado' => $this->calcularEspacioUsado(),
            'ultimo_respaldo' => $respaldos->first()['fecha'] ?? 'Nunca',
            'total_respaldos' => $respaldos->count(),
            'zip_disponible' => class_exists('ZipArchive') ? 'Sí' : 'No (archivos SQL sin comprimir)',
        ];

        return view('admin.respaldos.index', [
            'respaldos' => $respaldos,
            'info' => $info,
        ]);
    }

    public function crearRespaldo(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:completo,solo_datos,solo_estructura',
            'incluir_archivos' => 'boolean',
        ]);

        try {
            $timestamp = now()->format('Y-m-d_H-i-s');
            $nombreRespaldo = "respaldo_{$request->tipo}_{$timestamp}";

            // Crear directorio de respaldos si no existe
            if (! Storage::disk('local')->exists('respaldos')) {
                Storage::disk('local')->makeDirectory('respaldos');
            }

            $rutaRespaldo = storage_path("app/respaldos/{$nombreRespaldo}");

            // Crear respaldo de base de datos
            $this->crearRespaldoBaseDatos($request->tipo, $rutaRespaldo);

            // Incluir archivos si se solicita
            if ($request->incluir_archivos) {
                $this->incluirArchivos($rutaRespaldo);
            }

            // Comprimir respaldo
            $archivoZip = $this->comprimirRespaldo($rutaRespaldo, $nombreRespaldo);

            // Limpiar archivos temporales
            $this->limpiarTemporales($rutaRespaldo);

            return redirect()->back()->with('success',
                "Respaldo creado exitosamente: {$nombreRespaldo}.zip. ".
                "<a href='".route('admin.respaldos.descargar', basename($archivoZip))."' class='btn btn-sm btn-outline-primary ml-2'>".
                "<i class='fas fa-download'></i> Descargar</a>"
            );

        } catch (\Exception $e) {
            Log::error('Error creando respaldo: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error al crear respaldo: '.$e->getMessage());
        }
    }

    public function descargar($archivo)
    {
        $rutaArchivo = storage_path("app/respaldos/{$archivo}");

        if (! file_exists($rutaArchivo)) {
            return redirect()->back()->with('error', 'Archivo de respaldo no encontrado.');
        }

        return response()->download($rutaArchivo);
    }

    public function eliminar($archivo)
    {
        try {
            $rutaArchivo = storage_path("app/respaldos/{$archivo}");

            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);

                return redirect()->back()->with('success', 'Respaldo eliminado correctamente.');
            }

            return redirect()->back()->with('error', 'Archivo no encontrado.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al eliminar respaldo: '.$e->getMessage());
        }
    }

    public function restaurar(Request $request)
    {
        $request->validate([
            'archivo_respaldo' => 'required|file|mimes:zip,sql',
            'confirmar_restauracion' => 'required|accepted',
        ]);

        try {
            $archivo = $request->file('archivo_respaldo');
            $nombreTemporal = 'restauracion_'.time().'.'.$archivo->getClientOriginalExtension();
            $rutaTemporal = storage_path("app/temp/{$nombreTemporal}");

            // Crear directorio temporal
            if (! Storage::disk('local')->exists('temp')) {
                Storage::disk('local')->makeDirectory('temp');
            }

            // Guardar archivo temporal
            $archivo->move(storage_path('app/temp'), $nombreTemporal);

            // Procesar restauración según tipo de archivo
            if ($archivo->getClientOriginalExtension() === 'zip') {
                $this->restaurarDesdeZip($rutaTemporal);
            } else {
                $this->restaurarDesdeSQL($rutaTemporal);
            }

            // Limpiar archivo temporal
            unlink($rutaTemporal);

            return redirect()->back()->with('success', 'Base de datos restaurada exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error restaurando respaldo: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error al restaurar: '.$e->getMessage());
        }
    }

    public function programarRespaldo(Request $request)
    {
        $request->validate([
            'frecuencia' => 'required|in:diario,semanal,mensual',
            'hora' => 'required|date_format:H:i',
            'mantener_respaldos' => 'required|integer|min:1|max:30',
        ]);

        // Aquí podrías implementar la programación usando Laravel Scheduler
        // Por ahora, guardamos la configuración en un archivo
        $configuracion = [
            'frecuencia' => $request->frecuencia,
            'hora' => $request->hora,
            'mantener_respaldos' => $request->mantener_respaldos,
            'activo' => true,
            'ultimo_respaldo' => null,
        ];

        Storage::disk('local')->put('respaldos/configuracion.json', json_encode($configuracion));

        return redirect()->back()->with('success', 'Respaldos automáticos configurados correctamente.');
    }

    private function obtenerRespaldos()
    {
        $archivos = Storage::disk('local')->files('respaldos');
        $respaldos = collect();

        foreach ($archivos as $archivo) {
            $extension = pathinfo($archivo, PATHINFO_EXTENSION);
            if (in_array($extension, ['zip', 'sql'])) {
                $rutaCompleta = storage_path("app/{$archivo}");
                $respaldos->push([
                    'nombre' => basename($archivo),
                    'fecha' => Carbon::createFromTimestamp(filemtime($rutaCompleta)),
                    'tamaño' => $this->formatearTamaño(filesize($rutaCompleta)),
                    'tipo' => $this->detectarTipoRespaldo(basename($archivo)),
                    'formato' => strtoupper($extension),
                ]);
            }
        }

        return $respaldos->sortByDesc('fecha')->values();
    }

    private function crearRespaldoBaseDatos($tipo, $rutaRespaldo)
    {
        $baseDatos = config('database.connections.mysql.database');
        $usuario = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $puerto = config('database.connections.mysql.port', 3306);

        $archivoSQL = "{$rutaRespaldo}.sql";

        // Verificar conexión a la base de datos antes de continuar
        try {
            DB::connection()->getPdo();
            Log::info('Conexión a la base de datos verificada correctamente');
        } catch (\Exception $e) {
            throw new \Exception('Error de conexión a la base de datos: '.$e->getMessage());
        }

        // Detectar la ruta de mysqldump en Windows (Laragon)
        $mysqldumpPath = $this->detectarMysqldump();

        // Construir opciones básicas
        $opciones = '--single-transaction --routines --triggers --lock-tables=false';

        // Construir comando base
        $comandoBase = "\"{$mysqldumpPath}\" --host={$host} --port={$puerto} --user={$usuario}";

        // Agregar password si existe
        if ($password) {
            $comandoBase .= " --password={$password}";
        }

        switch ($tipo) {
            case 'completo':
                $comando = "{$comandoBase} {$opciones} {$baseDatos}";
                break;
            case 'solo_datos':
                $comando = "{$comandoBase} {$opciones} --no-create-info {$baseDatos}";
                break;
            case 'solo_estructura':
                $comando = "{$comandoBase} {$opciones} --no-data {$baseDatos}";
                break;
        }

        // Agregar redirección de salida con comillas para Windows
        $comando .= " > \"{$archivoSQL}\"";

        Log::info('Ejecutando comando mysqldump: '.str_replace($password, '***', $comando));

        exec($comando, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error("mysqldump falló. Return code: {$returnCode}, Output: ".implode("\n", $output));
            throw new \Exception("Error ejecutando mysqldump (código {$returnCode}): ".implode("\n", $output));
        }

        // Verificar que el archivo se creó correctamente
        if (! file_exists($archivoSQL) || filesize($archivoSQL) == 0) {
            throw new \Exception('El archivo de respaldo no se creó correctamente o está vacío.');
        }
    }

    private function incluirArchivos($rutaRespaldo)
    {
        $directorioArchivos = "{$rutaRespaldo}_archivos";
        mkdir($directorioArchivos, 0755, true);

        // Copiar archivos importantes
        $directoriosACopiar = [
            'storage/app/public/vouchers',
            'storage/app/public/depositos',
            'storage/app/public/rendiciones',
            'storage/app/public/prestamos',
        ];

        foreach ($directoriosACopiar as $directorio) {
            $origen = base_path($directorio);
            $destino = "{$directorioArchivos}/".basename($directorio);

            if (is_dir($origen)) {
                $this->copiarDirectorio($origen, $destino);
            }
        }
    }

    private function comprimirRespaldo($rutaRespaldo, $nombreRespaldo)
    {
        // Verificar si ZipArchive está disponible
        if (! class_exists('ZipArchive')) {
            Log::warning('ZipArchive no está disponible, guardando archivo SQL sin comprimir');

            // Si no hay ZipArchive, simplemente renombrar el archivo SQL
            $archivoSQL = "{$rutaRespaldo}.sql";
            $archivoFinal = storage_path("app/respaldos/{$nombreRespaldo}.sql");

            if (file_exists($archivoSQL)) {
                rename($archivoSQL, $archivoFinal);

                return $archivoFinal;
            }

            throw new \Exception('No se encontró el archivo SQL para mover');
        }

        $zip = new ZipArchive;
        $archivoZip = storage_path("app/respaldos/{$nombreRespaldo}.zip");

        if ($zip->open($archivoZip, ZipArchive::CREATE) !== true) {
            throw new \Exception('No se pudo crear el archivo ZIP');
        }

        // Agregar archivo SQL
        if (file_exists("{$rutaRespaldo}.sql")) {
            $zip->addFile("{$rutaRespaldo}.sql", 'database.sql');
        }

        // Agregar archivos si existen
        $directorioArchivos = "{$rutaRespaldo}_archivos";
        if (is_dir($directorioArchivos)) {
            $this->agregarDirectorioAlZip($zip, $directorioArchivos, 'archivos/');
        }

        $zip->close();

        return $archivoZip;
    }

    private function detectarMysqldump()
    {
        // Primero intentar detectar automáticamente la versión de MySQL en Laragon
        $laraonMysqlDir = 'C:\\laragon\\bin\\mysql\\';
        if (is_dir($laraonMysqlDir)) {
            $versiones = glob($laraonMysqlDir.'mysql-*');
            if (! empty($versiones)) {
                // Ordenar versiones y tomar la más reciente
                rsort($versiones);
                foreach ($versiones as $version) {
                    $mysqldumpPath = $version.'\\bin\\mysqldump.exe';
                    if (file_exists($mysqldumpPath)) {
                        Log::info("mysqldump encontrado en Laragon: {$mysqldumpPath}");

                        return $mysqldumpPath;
                    }
                }
            }
        }

        // Rutas comunes de mysqldump en otros entornos Windows
        $rutasPosibles = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.21\\bin\\mysqldump.exe',
            'mysqldump.exe', // Si está en PATH
            'mysqldump', // Linux/Mac
        ];

        foreach ($rutasPosibles as $ruta) {
            if (file_exists($ruta)) {
                Log::info("mysqldump encontrado en: {$ruta}");

                return $ruta;
            }
        }

        // Si no se encuentra, intentar con el comando en PATH
        $comando = PHP_OS_FAMILY === 'Windows' ? 'where mysqldump' : 'which mysqldump';
        exec($comando, $output, $returnCode);

        if ($returnCode === 0 && ! empty($output[0])) {
            return trim($output[0]);
        }

        throw new \Exception('No se pudo encontrar mysqldump. Asegúrate de que MySQL esté instalado y accesible.');
    }

    private function detectarMysql()
    {
        // Primero intentar detectar automáticamente la versión de MySQL en Laragon
        $laraonMysqlDir = 'C:\\laragon\\bin\\mysql\\';
        if (is_dir($laraonMysqlDir)) {
            $versiones = glob($laraonMysqlDir.'mysql-*');
            if (! empty($versiones)) {
                // Ordenar versiones y tomar la más reciente
                rsort($versiones);
                foreach ($versiones as $version) {
                    $mysqlPath = $version.'\\bin\\mysql.exe';
                    if (file_exists($mysqlPath)) {
                        Log::info("mysql encontrado en Laragon: {$mysqlPath}");

                        return $mysqlPath;
                    }
                }
            }
        }

        // Rutas comunes de mysql en otros entornos Windows
        $rutasPosibles = [
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.21\\bin\\mysql.exe',
            'mysql.exe', // Si está en PATH
            'mysql', // Linux/Mac
        ];

        foreach ($rutasPosibles as $ruta) {
            if (file_exists($ruta)) {
                Log::info("mysql encontrado en: {$ruta}");

                return $ruta;
            }
        }

        // Si no se encuentra, intentar con el comando en PATH
        $comando = PHP_OS_FAMILY === 'Windows' ? 'where mysql' : 'which mysql';
        exec($comando, $output, $returnCode);

        if ($returnCode === 0 && ! empty($output[0])) {
            return trim($output[0]);
        }

        throw new \Exception('No se pudo encontrar mysql. Asegúrate de que MySQL esté instalado y accesible.');
    }

    private function limpiarTemporales($rutaRespaldo)
    {
        // Eliminar archivo SQL temporal
        if (file_exists("{$rutaRespaldo}.sql")) {
            unlink("{$rutaRespaldo}.sql");
        }

        // Eliminar directorio de archivos temporal
        $directorioArchivos = "{$rutaRespaldo}_archivos";
        if (is_dir($directorioArchivos)) {
            $this->eliminarDirectorio($directorioArchivos);
        }
    }

    private function restaurarDesdeZip($rutaArchivo)
    {
        $zip = new ZipArchive;

        if ($zip->open($rutaArchivo) === true) {
            $directorioTemp = storage_path('app/temp/restauracion_'.time());
            $zip->extractTo($directorioTemp);
            $zip->close();

            // Restaurar base de datos
            $archivoSQL = "{$directorioTemp}/database.sql";
            if (file_exists($archivoSQL)) {
                $this->restaurarDesdeSQL($archivoSQL);
            }

            // Limpiar directorio temporal
            $this->eliminarDirectorio($directorioTemp);
        }
    }

    private function restaurarDesdeSQL($rutaArchivo)
    {
        $baseDatos = config('database.connections.mysql.database');
        $usuario = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $puerto = config('database.connections.mysql.port', 3306);

        // Detectar la ruta de mysql en Windows (Laragon)
        $mysqlPath = $this->detectarMysql();

        // Agregar password de forma segura si existe
        $passwordOption = $password ? " --password=\"{$password}\"" : '';

        $comando = "\"{$mysqlPath}\" --host={$host} --port={$puerto} --user={$usuario}{$passwordOption} {$baseDatos} < \"{$rutaArchivo}\" 2>&1";

        Log::info('Ejecutando comando mysql para restauración: '.str_replace($password, '***', $comando));

        exec($comando, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error("mysql restore falló. Return code: {$returnCode}, Output: ".implode("\n", $output));
            throw new \Exception("Error restaurando base de datos (código {$returnCode}): ".implode("\n", $output));
        }
    }

    private function calcularEspacioUsado()
    {
        $tamaño = 0;
        $directorios = ['storage/app/public', 'storage/logs'];

        foreach ($directorios as $directorio) {
            $ruta = base_path($directorio);
            if (is_dir($ruta)) {
                $tamaño += $this->obtenerTamañoDirectorio($ruta);
            }
        }

        return $this->formatearTamaño($tamaño);
    }

    private function obtenerTamañoDirectorio($directorio)
    {
        $tamaño = 0;

        try {
            if (! is_dir($directorio)) {
                return 0;
            }

            $archivos = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directorio, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($archivos as $archivo) {
                try {
                    if ($archivo->isFile()) {
                        $tamaño += $archivo->getSize();
                    }
                } catch (\Exception $e) {
                    // Ignorar archivos que no se pueden leer
                    Log::warning('No se pudo leer el archivo: '.$archivo->getPathname());

                    continue;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Error calculando tamaño del directorio {$directorio}: ".$e->getMessage());

            return 0;
        }

        return $tamaño;
    }

    private function formatearTamaño($bytes)
    {
        if ($bytes == 0) {
            return '0 B';
        }

        $unidades = ['B', 'KB', 'MB', 'GB'];
        $potencia = floor(log($bytes, 1024));

        // Asegurar que la potencia esté dentro del rango de unidades disponibles
        $potencia = min($potencia, count($unidades) - 1);
        $potencia = max($potencia, 0);

        return round($bytes / pow(1024, $potencia), 2).' '.$unidades[$potencia];
    }

    private function detectarTipoRespaldo($nombreArchivo)
    {
        if (strpos($nombreArchivo, 'completo') !== false) {
            return 'Completo';
        }
        if (strpos($nombreArchivo, 'solo_datos') !== false) {
            return 'Solo Datos';
        }
        if (strpos($nombreArchivo, 'solo_estructura') !== false) {
            return 'Solo Estructura';
        }

        return 'Desconocido';
    }

    private function copiarDirectorio($origen, $destino)
    {
        if (! is_dir($destino)) {
            mkdir($destino, 0755, true);
        }

        $archivos = scandir($origen);
        foreach ($archivos as $archivo) {
            if ($archivo != '.' && $archivo != '..') {
                $rutaOrigen = "{$origen}/{$archivo}";
                $rutaDestino = "{$destino}/{$archivo}";

                if (is_dir($rutaOrigen)) {
                    $this->copiarDirectorio($rutaOrigen, $rutaDestino);
                } else {
                    copy($rutaOrigen, $rutaDestino);
                }
            }
        }
    }

    private function eliminarDirectorio($directorio)
    {
        if (is_dir($directorio)) {
            $archivos = scandir($directorio);
            foreach ($archivos as $archivo) {
                if ($archivo != '.' && $archivo != '..') {
                    $ruta = "{$directorio}/{$archivo}";
                    if (is_dir($ruta)) {
                        $this->eliminarDirectorio($ruta);
                    } else {
                        unlink($ruta);
                    }
                }
            }
            rmdir($directorio);
        }
    }

    private function agregarDirectorioAlZip($zip, $directorio, $rutaLocal = '')
    {
        $archivos = scandir($directorio);
        foreach ($archivos as $archivo) {
            if ($archivo != '.' && $archivo != '..') {
                $rutaCompleta = "{$directorio}/{$archivo}";
                $rutaEnZip = $rutaLocal.$archivo;

                if (is_dir($rutaCompleta)) {
                    $zip->addEmptyDir($rutaEnZip);
                    $this->agregarDirectorioAlZip($zip, $rutaCompleta, $rutaEnZip.'/');
                } else {
                    $zip->addFile($rutaCompleta, $rutaEnZip);
                }
            }
        }
    }
}
