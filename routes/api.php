<?php

use App\Http\Controllers\API\AprobacionPrestamoController;
use App\Http\Controllers\API\AsistenciaController;
// Controladores de ubicación geográfica
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ClienteController;
use App\Http\Controllers\API\CompromisoController;
use App\Http\Controllers\API\DepartamentoController;
use App\Http\Controllers\API\DistritoController;
// Controladores del sistema financiero
use App\Http\Controllers\API\EstadoCuentaController;
use App\Http\Controllers\API\FondoProvisionalController;
use App\Http\Controllers\API\GestionController;
use App\Http\Controllers\API\MetodoPagoController;
use App\Http\Controllers\API\PagoController;
use App\Http\Controllers\API\PerfilController;
use App\Http\Controllers\API\PrestamoController;
use App\Http\Controllers\API\ProvinciaController;
use App\Http\Controllers\API\SireEmisionController;
use App\Http\Controllers\API\SolicitudPrestamoController;
use App\Http\Controllers\API\ZonaController;
use App\Http\Controllers\API\CuentasController;
use App\Http\Controllers\API\ZonificadorTramosController;
use App\Http\Controllers\API\ReportSaleController;
use App\Http\Controllers\API\ScoreSaleController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Sistema Financiero Móvil
|--------------------------------------------------------------------------
|
| Rutas API para la aplicación móvil del sistema de gestión financiera
| Todas las rutas autenticadas requieren token Sanctum
|
*/

// ================================================
// RUTAS PÚBLICAS (Sin autenticación)
// ================================================

// Autenticación
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// Consultas geográficas públicas
Route::prefix('ubicacion')->group(function () {
    Route::get('departamentos', [DepartamentoController::class, 'index']);
    Route::get('departamento/{departamento}/provincias', [DepartamentoController::class, 'provincias']);
    Route::get('provincia/{provincia}/distritos', [ProvinciaController::class, 'distritos']);
    Route::get('zonas', [ZonaController::class, 'index']);
    Route::get('distrito/{distrito}/zonas', [DistritoController::class, 'zonas']);
    Route::get('zona/{zona}/sucursales', [ZonaController::class, 'sucursales']);
    Route::get('sucursal/{sucursal}/location', [App\Http\Controllers\API\SucursalController::class, 'getLocation']);
});

// Documentos públicos (preview y download de PDFs)
Route::prefix('documentos')->group(function () {
    Route::get('contrato-mutuo/{id}/preview', [PrestamoController::class, 'previewContratoMutuo']);
    Route::get('contrato-mutuo/{id}/download', [PrestamoController::class, 'downloadContratoMutuo']);
});

// ================================================
// RUTAS PROTEGIDAS (Requieren autenticación)
// ================================================

Route::middleware('auth:sanctum')->group(function () {

    // ================ AUTENTICACIÓN ================
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::post('refresh-token', [AuthController::class, 'refreshToken']);
    });

    // ================ PERFIL DE USUARIO ================
    Route::prefix('perfil')->group(function () {
        Route::get('/', [PerfilController::class, 'perfil']);
        Route::put('/', [PerfilController::class, 'actualizarPerfil']);
        Route::post('foto', [PerfilController::class, 'subirFotoPerfil']);
        Route::delete('foto', [PerfilController::class, 'eliminarFotoPerfil']);
        Route::post('cambiar-contrasena', [PerfilController::class, 'cambiarContrasena']);
        Route::get('actividad', [PerfilController::class, 'resumenActividad']);

        // Configuraciones de notificaciones
        Route::get('notificaciones', [PerfilController::class, 'configuracionNotificaciones']);
        Route::put('notificaciones', [PerfilController::class, 'actualizarConfiguracionNotificaciones']);

        // Gestión de sesiones
        Route::get('sesiones', [PerfilController::class, 'sesionesActivas']);
        Route::delete('sesiones/{tokenId}', [PerfilController::class, 'revocarSesion']);
    });

    // ================ CLIENTES ================
    Route::prefix('clientes')->group(function () {
        Route::get('/', [ClienteController::class, 'index']);
        // Clientes con SOLICITUDES (Nueva solicitud, Aprobado)
        Route::get('/solicitudes', [ClienteController::class, 'indexSolicitudes']);

        // Clientes con PRÉSTAMOS (Por Desembolsar, Vigente, Moroso, Con Convenio, Liquidado, Finalizado)
        Route::get('/prestamos', [ClienteController::class, 'indexPrestamos']);
        Route::post('/', [ClienteController::class, 'store']);
        Route::get('/datos-formulario', [ClienteController::class, 'datosFormulario']);
        Route::post('/buscar-documento', [ClienteController::class, 'buscarPorDocumento']);
        Route::post('/consultar-dni', [ClienteController::class, 'consultarDNI']);
        Route::post('/buscar-crear-aval', [ClienteController::class, 'buscarOCrearPersonaAval']);
        // Rutas específicas ANTES de /{id}
        Route::get('/{id}/direcciones', [ClienteController::class, 'getDirecciones']);
        Route::post('/{id}/direcciones', [ClienteController::class, 'storeDireccion']);
        // Rutas genéricas al final
        Route::get('/{id}', [ClienteController::class, 'show']);
        // Route::post('/{id}', [ClienteController::class, 'update']);
        Route::match(['post', 'put'], '/{id}', [ClienteController::class, 'update']);
        //cuentas de cliente
        Route::get('/{id}/cuentas', [ClienteController::class, 'getCuentasCliente']);
    });

    Route::prefix('cuentas')->group(function () {
        Route::get('/bancos', [CuentasController::class, 'getBancos']);
        Route::get('/billeteras-digitales', [CuentasController::class, 'getBilleterasDigitales']);
    });

    // ================ SOLICITUDES DE PRÉSTAMOS ================
    Route::prefix('solicitudes')->group(function () {
        Route::get('/', [SolicitudPrestamoController::class, 'index']);
        Route::post('/', [SolicitudPrestamoController::class, 'store']);
        Route::get('/{id}', [SolicitudPrestamoController::class, 'show']);
        Route::put('/{id}', [SolicitudPrestamoController::class, 'update']);
        Route::post('/{id}/cancelar', [SolicitudPrestamoController::class, 'cancel']);
        Route::post('/calcular', [SolicitudPrestamoController::class, 'calculate']);
    });

    // ================ APROBACIÓN DE PRÉSTAMOS ================
    Route::prefix('aprobaciones')->group(function () {
        Route::get('pendientes', [AprobacionPrestamoController::class, 'pendientes']);
        Route::get('aprobados', [AprobacionPrestamoController::class, 'aprobados']);
        Route::get('historial', [AprobacionPrestamoController::class, 'historial']);
        Route::get('metodos-pago', [AprobacionPrestamoController::class, 'metodosPago']);
        Route::post('/{id}/aprobar', [AprobacionPrestamoController::class, 'aprobar']);
        Route::post('/{id}/rechazar', [AprobacionPrestamoController::class, 'rechazar']);
        Route::post('/{id}/desembolsar', [AprobacionPrestamoController::class, 'desembolsar']);
    });

    // ================ PRÉSTAMOS ================
    Route::prefix('prestamos')->group(function () {
        Route::get('/', [PrestamoController::class, 'index']);
        Route::get('/form-data', [PrestamoController::class, 'formData']);
        Route::post('/calcular-cuotas', [PrestamoController::class, 'calcularCuotas']);
        Route::post('/', [PrestamoController::class, 'store']);
        Route::get('/{id}', [PrestamoController::class, 'show']);
        Route::get('/{id}/cuotas', [PrestamoController::class, 'cuotas']);

        // Documentos / Contratos (check y generate requieren autenticación)
        Route::get('/{id}/check-contrato-mutuo', [PrestamoController::class, 'checkContratoMutuo']);
        Route::post('/{id}/generate-contrato-mutuo', [PrestamoController::class, 'generateContratoMutuo']);
        Route::post('/{id}/documentos', [PrestamoController::class, 'uploadDocumentos']);
        // preview y download están en rutas públicas para permitir abrir en navegador
    });

    // ================ GESTIÓN DE PAGOS ================
    Route::prefix('pagos')->group(function () {
        Route::post('/', [PagoController::class, 'registrarPago']);
        Route::get('/prestamo/{prestamoId}', [PagoController::class, 'listarPagos']);
        Route::get('/{operacionId}', [PagoController::class, 'detallePago']);
        Route::post('/{operacionId}/anular', [PagoController::class, 'anularPago']);
        Route::get('/prestamo/{prestamoId}/cuotas-pendientes', [PagoController::class, 'cuotasPendientes']);
    });

    // ================ MÉTODOS DE PAGO ================
    Route::get('metodos-pago', [MetodoPagoController::class, 'index']);

    // ================ ESTADOS DE CUENTA ================
    Route::prefix('estados-cuenta')->group(function () {
        Route::get('/prestamo/{prestamoId}', [EstadoCuentaController::class, 'estadoCuentaPrestamo']);
        Route::get('/cliente/{clienteId}/prestamos', [EstadoCuentaController::class, 'prestamosCliente']);
        Route::get('/prestamo/{prestamoId}/pdf', [EstadoCuentaController::class, 'generarPDF']);
        Route::get('/prestamo/{prestamoId}/compartir', [EstadoCuentaController::class, 'generarParaCompartir']);
        Route::get('/prestamo/{prestamoId}/cronograma-html', [EstadoCuentaController::class, 'obtenerHtmlCronograma']);
        Route::get('/resumen-cartera', [EstadoCuentaController::class, 'resumenCartera']);
        Route::get('/estado-cuenta/{id}', [EstadoCuentaController::class, 'descargar']);
    });

    // ================ GESTIONES ================
    Route::prefix('gestiones')->group(function () {
        Route::get('/', [GestionController::class, 'index']);
        Route::post('/', [GestionController::class, 'store']);
        Route::get('/{id}', [GestionController::class, 'show']);
        Route::put('/{id}', [GestionController::class, 'update']);
        Route::get('/prestamo/{prestamoId}', [GestionController::class, 'gestionesPrestamo']);
        Route::post('/{gestionId}/pago', [GestionController::class, 'registrarPago']);
        Route::get('/estados/disponibles', [GestionController::class, 'estadosGestion']);
        Route::get('/estadisticas/resumen', [GestionController::class, 'estadisticas']);
        Route::get('/agenda/programadas', [GestionController::class, 'agenda']);
    });

    // Ruta adicional para estados de gestión (compatibilidad con frontend)
    Route::get('estados-gestion', [GestionController::class, 'estadosGestion']);

    // ================ COMPROMISOS ================
    Route::prefix('compromisos')->group(function () {
        Route::get('/', [CompromisoController::class, 'index']);
        Route::post('/', [CompromisoController::class, 'store']);
        Route::get('/{id}', [CompromisoController::class, 'show']);
        Route::put('/{id}', [CompromisoController::class, 'update']);
        Route::post('/{id}/cumplir', [CompromisoController::class, 'cumplir']);
        Route::post('/{id}/incumplir', [CompromisoController::class, 'incumplir']);
        Route::get('/prestamo/{prestamoId}', [CompromisoController::class, 'compromisosPrestamo']);
        Route::get('/dashboard/resumen', [CompromisoController::class, 'dashboard']);
    });

    // ================ FONDOS PROVISIONALES ================
    Route::prefix('fondos-provisionales')->group(function () {
        Route::get('/', [FondoProvisionalController::class, 'index']);
        Route::post('/', [FondoProvisionalController::class, 'store']);
        Route::post('/registrar-fondo-prestamo', [FondoProvisionalController::class, 'registrarFondoProvisional']);
        Route::get('/prestamo/{prestamo_id}', [FondoProvisionalController::class, 'getByPrestamo']);
        Route::get('/{id}', [FondoProvisionalController::class, 'show']);
        Route::post('/{id}/aprobar', [FondoProvisionalController::class, 'aprobar']);
        Route::post('/{id}/rechazar', [FondoProvisionalController::class, 'rechazar']);
        Route::post('/{id}/entregar', [FondoProvisionalController::class, 'entregar']);
        Route::post('/{id}/liquidar', [FondoProvisionalController::class, 'liquidar']);
        Route::get('/resumen/estadisticas', [FondoProvisionalController::class, 'resumen']);
        Route::get('/mis-fondos/listado', [FondoProvisionalController::class, 'misFondos']);
        Route::get('metodos-pago', [App\Http\Controllers\API\FondoProvisionalController::class, 'getMetodosPago']);
    });

    // ================ ZONA-SUCURSAL ================
    Route::get('/zona/{zona_id}/sucursales', [ClienteController::class, 'getSucursalesByZona']);

    // ================ ASISTENCIA ================
    Route::prefix('asistencia')->group(function () {
        Route::post('/entrada', [AsistenciaController::class, 'registrarEntrada']);
        Route::post('/salida', [AsistenciaController::class, 'registrarSalida']);
        Route::post('/refrigerio-inicio', [AsistenciaController::class, 'registrarInicioRefrigerio']);
        Route::post('/refrigerio-fin', [AsistenciaController::class, 'registrarFinRefrigerio']);
        Route::get('/hoy', [AsistenciaController::class, 'asistenciaHoy']);
        Route::get('/historial', [AsistenciaController::class, 'historial']);
        Route::get('/estadisticas', [AsistenciaController::class, 'estadisticas']);
        Route::get('/areas-asignadas', [AsistenciaController::class, 'areasAsignadas']);
        Route::get('/estado', [AsistenciaController::class, 'estadoActual']);
    });

    // ================ INFORMACIÓN DEL USUARIO ================
    Route::get('/user', function (Request $request) {
        return $request->user()->load(['persona', 'sucursales', 'roles']);
    });

    // ================ SIRE - EMISIÓN DE COMPROBANTES ================
    Route::prefix('sire')->group(function () {
        Route::post('/emitir-cuota', [SireEmisionController::class, 'emitirCuota'])->name('api.sire.emitir-cuota');
        Route::post('/reenviar/{historial}', [SireEmisionController::class, 'reenviarComprobante'])->name('api.sire.reenviar');
        Route::get('/cuota/{cuota}/comprobantes', [SireEmisionController::class, 'obtenerComprobantesDelCuota'])->name('api.sire.cuota.comprobantes');
        Route::get('/comprobantes', [SireEmisionController::class, 'listarComprobantes'])->name('api.sire.comprobantes');
    });

    // Rutas para Zonificador de Tramos API
    Route::prefix('zonificador')->name('zonificador.')->group(function () {
        // Endpoints principales
        Route::get('/clientes', [ZonificadorTramosController::class, 'getClientes'])->name('clientes');
        Route::get('/zonas', [ZonificadorTramosController::class, 'getZonas'])->name('zonas');
        Route::get('/tramos', [ZonificadorTramosController::class, 'getTramos'])->name('tramos');
        Route::get('/buscar', [ZonificadorTramosController::class, 'buscarCliente'])->name('buscar');
    });

    // ================ Reporte Sale ================
    Route::prefix('report-sale')->group(function () {
        // Reportes básicos
        Route::get('/prestamos/total', [ReportSaleController::class, 'totalPrestamos']);
        Route::get('/prestamos/nuevos', [ReportSaleController::class, 'prestamosNuevos']);
        Route::get('/prestamos/renovaciones', [ReportSaleController::class, 'prestamosRenovaciones']);

        // Reportes por agrupación
        Route::get('/prestamos/por-usuario', [ReportSaleController::class, 'reportePorUsuario']);
        Route::get('/prestamos/por-zona', [ReportSaleController::class, 'reportePorZona']);
        Route::get('/prestamos/por-sucursal', [ReportSaleController::class, 'reportePorSucursal']);
        Route::get('/prestamos/por-mes', [ReportSaleController::class, 'reportePorMes']);
        Route::get('/prestamos/por-anio', [ReportSaleController::class, 'reportePorAnio']);

        // Reporte completo
        Route::get('/prestamos/completo', [ReportSaleController::class, 'reporteCompleto']);
    });

    //=============== Score de Venta ================
    Route::middleware(['auth', 'role:Admin|Supervisor'])->group(function () {
        // Score del mes actual o filtrado por zona/periodo
        Route::get('/score-sale', [ScoreSaleController::class, 'scoreSale']);

        // Endpoint detalle: Clientes por zona/usuario
        // Route::get('/clientes', [ScoreSaleController::class, 'detalleClientes']);
    });


    // ================ DEUDAS Y COBRANZA (API) ================
    // Rutas para exponer datos de deudas filtrados como servicio API
    // Route::prefix('deudas')->middleware('auth:sanctum')->group(function () {
    //     // Obtener datos filtrados de deudas (por tramos, estados, etc.)
    //     Route::post('/filtradas', [\App\Http\Controllers\API\DeudasApiController::class, 'getDeudasFiltradas'])
    //         ->name('api.deudas.filtradas');

    //     // Obtener todas las deudas con filtros
    //     Route::post('/todas', [\App\Http\Controllers\API\DeudasApiController::class, 'getDeudas'])
    //         ->name('api.deudas.todas');

    //     // Obtener solo estadísticas/resumen
    //     Route::post('/estadisticas', [\App\Http\Controllers\API\DeudasApiController::class, 'getEstadisticasDeudas'])
    //         ->name('api.deudas.estadisticas');

    //     // Exportar datos en diferentes formatos
    //     Route::post('/exportar', [\App\Http\Controllers\API\DeudasApiController::class, 'exportarDeudasFiltradas'])
    //         ->name('api.deudas.exportar');
    // });
});

Route::prefix('deudas')->group(function () {
    Route::post('/filtradas', [\App\Http\Controllers\API\DeudasApiController::class, 'getDeudasFiltradas'])
        ->name('api.deudas.filtradas');

    Route::post('/todas', [\App\Http\Controllers\API\DeudasApiController::class, 'getDeudas'])
        ->name('api.deudas.todas');

    Route::post('/estadisticas', [\App\Http\Controllers\API\DeudasApiController::class, 'getEstadisticasDeudas'])
        ->name('api.deudas.estadisticas');

    Route::post('/exportar', [\App\Http\Controllers\API\DeudasApiController::class, 'exportarDeudasFiltradas'])
        ->name('api.deudas.exportar');
});

Route::prefix('cuenta/prestamos')->group(function () {
    Route::get('/estado-cuenta/{id}', [EstadoCuentaController::class, 'descargar']);
});

// Route::prefix('cuenta/prestamos')->group(function () {
//     Route::get('/estado-cuenta/{id}', function(Request $request, $id) {
//         // Solo verificar que exista un token específico
//         if ($request->header('X-API-Key') !== 'mi-token-fijo-2024') {
//             return response()->json(['error' => 'No autorizado'], 401);
//         }
        
//         return app(EstadoCuentaController::class)->descargar($id);
//     });
// });


// ================================================
// RUTAS DE COMPATIBILIDAD (Mantener existentes)
// ================================================

// Rutas geográficas legacy
Route::get('departamento/{departamento}/provincias', [DepartamentoController::class, 'provincias'])->name('api.consulta.provincia');
Route::get('provincia/{provincia}/distritos', [ProvinciaController::class, 'distritos'])->name('api.consulta.distrito');
Route::get('distrito/{distrito}/zonas', [DistritoController::class, 'zonas'])->name('api.consulta.zonas');
Route::get('zona/{zona}/sucursales', [ZonaController::class, 'sucursales'])->name('api.consulta.sucursales');
Route::get('zonas-con-sucursales', [ZonaController::class, 'zonasConSucursales'])->name('api.consulta.zonasConSucursales');
Route::get('sucursal/{sucursal}/location', [App\Http\Controllers\API\SucursalController::class, 'getLocation'])->name('api.consulta.sucursal.location');
Route::get('listado-users', [App\Http\Controllers\API\AuthController::class, 'users'])->name('api.consulta.listado-users');

