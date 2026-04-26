<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminComprobantesController;
use App\Http\Controllers\Admin\AdminConfiguracionSunatController;
use App\Http\Controllers\Admin\SireController;
use App\Http\Controllers\Admin\ClientesController;
use App\Http\Controllers\Admin\ComprobantesController;
use App\Http\Controllers\Admin\ConyugesController;
use App\Http\Controllers\Auth\CustomAuthenticatedSessionController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Admin SIRE Routes
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/comprobantes', [AdminComprobantesController::class, 'index'])->name('admin.comprobantes.index');

    // Rutas del módulo SIRE - Comprobantes Electrónicos
    Route::prefix('sire')->name('admin.sire.')->group(function () {
        // Listado y datos
        Route::get('/', [SireController::class, 'index'])->name('index');
        Route::get('/data', [SireController::class, 'data'])->name('data');

        // Envío de comprobantes
        Route::get('/enviar', [SireController::class, 'enviar'])->name('enviar');
        Route::get('/cuotas-disponibles', [SireController::class, 'cuotasDisponibles'])->name('cuotas-disponibles');
        Route::get('/prestamos-disponibles', [SireController::class, 'prestamosDisponibles'])->name('prestamos-disponibles');
        Route::post('/enviar-masivo', [SireController::class, 'enviarMasivo'])->name('enviar-masivo');

        // Consulta SUNAT
        Route::get('/consultar', [SireController::class, 'consultar'])->name('consultar');
        Route::post('/consultar-comprobante', [SireController::class, 'consultarComprobante'])->name('consultar-comprobante');
        Route::get('/descargar-libro', [SireController::class, 'descargarLibro'])->name('descargar-libro');

        // Sincronización
        Route::post('/toggle-sincronizacion', [SireController::class, 'toggleSincronizacion'])->name('toggle-sincronizacion');
        Route::post('/sincronizar-ahora', [SireController::class, 'sincronizarAhora'])->name('sincronizar-ahora');
        Route::get('/historial-consultas', [SireController::class, 'historialConsultas'])->name('historial-consultas');

        // Detalles y descargas
        Route::get('/{id}/detalles', [SireController::class, 'detalles'])->name('detalles');
        Route::get('/{id}/descargar-xml', [SireController::class, 'descargarXml'])->name('descargar-xml');
        Route::get('/{id}/descargar-xml-firmado', [SireController::class, 'descargarXmlFirmado'])->name('descargar-xml-firmado');
        Route::get('/{id}/descargar-cdr', [SireController::class, 'descargarCdr'])->name('descargar-cdr');

        // Reenvío
        Route::post('/{id}/reenviar', [SireController::class, 'reenviar'])->name('reenviar');
    });
});

Route::get('/', function () {
    return redirect()->route('admin.index');
});

// Rutas de autenticación personalizadas con código de acceso
Route::middleware('guest')->group(function () {
    Route::get('/login', [CustomAuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login/submit-credentials', [CustomAuthenticatedSessionController::class, 'submitCredentials'])->name('login.submit-credentials');
    Route::post('/login', [CustomAuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [CustomAuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::post('/admin/consultar-dni', [ClientesController::class, 'consultarDNI'])->name('consultar.dni');
Route::post('/admin/consultar-dni-edicion', [ClientesController::class, 'consultarDNIParaEdicion'])->name('consultar.dni.edicion');
Route::post('/admin/consultar-dni-conyuge', [ConyugesController::class, 'consultarDNI'])->name('consultar.dniconyuge');

Route::get('/proxy-dni/{dni}', function ($dni) {
    // Obtener configuración de API desde la base de datos
    $url = \App\Models\ApiConfig::getValue('dni_api_url', 'https://api.factiliza.com/v1/dni/info/{dni}');
    $token = \App\Models\ApiConfig::getValue('dni_api_token', 'ece92d9d2a2bb54717373740c3180e722cf7a94b5501ed01a263e21e64a4b6a7');

    $finalUrl = str_replace('{dni}', $dni, $url);

    $response = Http::withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->get($finalUrl);

    return $response->body();
});

// Ruta simple para obtener sucursales por zona
Route::get('/zona/{zona_id}/sucursales', function ($zona_id) {
    $sucursales = DB::table('zona_sucursal')
        ->join('sucursales', 'zona_sucursal.sucursal_id', '=', 'sucursales.id')
        ->where('zona_sucursal.zona_id', $zona_id)
        ->select('sucursales.id', 'sucursales.sucursal')
        ->get();

    return response()->json($sucursales);
});

Route::get('/env-check', function () {
    return [
        'DB_CONNECTION' => env('DB_CONNECTION'),
        'DB_HOST' => env('DB_HOST'),
        'DB_PORT' => env('DB_PORT'),
        'DB_DATABASE' => env('DB_DATABASE'),
        'DB_USERNAME' => env('DB_USERNAME'),
        'DB_PASSWORD' => env('DB_PASSWORD'),
    ];
});

Route::get('/config-clear', function () {
    Artisan::call('config:clear');

    return 'Configuration cache cleared!';
});

Route::get('/cache-clear', function () {
    Artisan::call('cache:clear');

    return 'Application cache cleared!';
});

Route::get('/config-cache', function () {
    Artisan::call('config:cache');

    return 'Configuration cache cached!';
});

Route::get('/test-db-connection', function () {
    try {
        DB::connection()->getPdo();

        return 'Connection successful!';
    } catch (\Exception $e) {
        return 'Connection failed: '.$e->getMessage();
    }
});

// API Documentation (public access for external viewing)
Route::get('/api-docs', function () {
    $documentationPath = base_path('API_DOCUMENTATION.md');

    if (! file_exists($documentationPath)) {
        abort(404, 'Documentación no encontrada');
    }

    $content = file_get_contents($documentationPath);

    return response($content)
        ->header('Content-Type', 'text/plain; charset=utf-8')
        ->header('Content-Disposition', 'inline; filename="API_DOCUMENTATION.md"');
});

// Simple API test endpoint
Route::get('/api/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API está funcionando correctamente',
        'timestamp' => now()->toISOString(),
        'version' => config('api.version', '1.0'),
        'endpoints' => [
            'auth' => config('app.url').'/api/auth/login',
            'docs' => config('app.url').'/api-docs',
            'testing' => config('app.url').'/admin/api-docs/testing',
        ],
    ]);
});

Route::get('/admin/comprobantes/consultar-sunat', [ComprobantesController::class, 'consultarSunat'])
    ->name('admin.comprobantes.consultarSunat');

Route::get('/admin/comprobantes/{comprobante}/respuesta-sunat', [ComprobantesController::class, 'verRespuestaSunat'])
    ->name('admin.comprobantes.respuesta-sunat');
