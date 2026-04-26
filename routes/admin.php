<?php

use App\Http\Controllers\Admin\AccessCodeController;
use App\Http\Controllers\Admin\ApiConfigController;
use App\Http\Controllers\Admin\ApiDocsController;
use App\Http\Controllers\Admin\AsistenciaController;
use App\Http\Controllers\Admin\BancoController;
use App\Http\Controllers\Admin\BilleterasDigitalesController;
use App\Http\Controllers\Admin\CajaController;
use App\Http\Controllers\Admin\ClientesController;
use App\Http\Controllers\Admin\ComprobanteController;
use App\Http\Controllers\Admin\CompromisosController;
use App\Http\Controllers\Admin\CuentasController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DatabaseSyncController;
use App\Http\Controllers\Admin\DeudasController;
use App\Http\Controllers\Admin\EstadoGestionController;
use App\Http\Controllers\Admin\EventosAutomaticosController;
use App\Http\Controllers\Admin\GestionCobranzaController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\HuerfanasController;
use App\Http\Controllers\Admin\LoginRequestController;
use App\Http\Controllers\Admin\MetodosDePagoController;
use App\Http\Controllers\Admin\MigracionesController;
use App\Http\Controllers\Admin\MoraController;
use App\Http\Controllers\Admin\OperacionController;
use App\Http\Controllers\Admin\OperacionesController;
use App\Http\Controllers\Admin\PrestamosController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RegistrarPagoController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SucursalesController;
use App\Http\Controllers\Admin\TareasController;
use App\Http\Controllers\Admin\TasasController;
use App\Http\Controllers\Admin\UsuariosController;
use App\Http\Controllers\Admin\ValidacionOperacionesController;
use App\Http\Controllers\Admin\ZonasController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\Admin\PrestamoMoraController;
use App\Http\Controllers\Admin\ZonificadorTramosController;
use App\Http\Controllers\Admin\MetasController;
use Illuminate\Support\Facades\Route;

// Home
Route::get('', [HomeController::class, 'index'])->name('admin.index');

// Clientes - rutas específicas PRIMERO (antes del resource)
Route::get('clientes/importar', [ClientesController::class, 'mostrarImportar'])->name('admin.clientes.importar');
Route::get('clientes/importar/plantilla', [ClientesController::class, 'descargarPlantilla'])->name('admin.clientes.importar.plantilla');
Route::post('clientes/importar/procesar', [ClientesController::class, 'procesarImportacion'])->name('admin.clientes.importar.procesar');
Route::get('clientes/create', [ClientesController::class, 'create'])->name('admin.clientes.create');
Route::get('clientes/create-embedded', [ClientesController::class, 'createEmbedded'])->name('admin.clientes.create-embedded');
Route::post('clientes/consultar-dni', [ClientesController::class, 'consultarDNI'])->name('admin.clientes.consultarDNI');
Route::get('clientes/buscar-prestamo', [ClientesController::class, 'buscarClientesParaPrestamo'])->name('admin.clientes.buscar-prestamo');

// Clientes resource routes (DESPUÉS de las rutas específicas)
Route::get('clientes', [ClientesController::class, 'index'])->name('admin.clientes.index');
Route::resource('clientes', ClientesController::class)->names('admin.clientes');

// routes/web.php
Route::post('logout', [\Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'destroy'])->name('admin.logout');

// Rutas para el Dashboard (actualización en tiempo real)
Route::middleware(['auth'])->group(function () {
    // Actualizar secciones del dashboard
    Route::post('dashboard/refresh-section', [DashboardController::class, 'refreshSection'])
        ->name('admin.dashboard.refresh-section');

    // Verificar actualizaciones sin recargar la página
    Route::get('dashboard/check-updates', [DashboardController::class, 'checkUpdates'])
        ->name('admin.dashboard.check-updates');

    // Obtener estadísticas para el dashboard
    Route::get('dashboard/statistics', [DashboardController::class, 'getStatistics'])
        ->name('admin.dashboard.statistics');
});

// Clientes additional routes
Route::get('clientes/{clienteId}/direcciones', [ClientesController::class, 'obtenerDirecciones'])->name('admin.clientes.direcciones');
Route::get('clientes/{cliente}/etiquetas', [ClientesController::class, 'obtenerEtiquetas'])->name('admin.clientes.etiquetas');
Route::get('clientes-duplicado', [ClientesController::class, 'index'])->name('admin.clientes.index.duplicate');

//Calculo de cuotas
Route::post('calcular-cuotas', [PrestamosController::class, 'calcularCuotas'])->name('admin.calcularCuotas');

// Asignación Avales
Route::post('prestamos/storeAval', [PrestamosController::class, 'storeAval'])->name('admin.prestamos.storeAval');
Route::post('prestamos/asignar-aval', [PrestamosController::class, 'asignarAval'])->name('admin.prestamos.asignarAval');
Route::post('registrar-nuevo-aval', [PrestamosController::class, 'registrarNuevoAval'])->name('admin.prestamos.registrarNuevoAval');
// Solicitudes
Route::get('solicitudes', [PrestamosController::class, 'index'])->name('admin.solicitudes.index');
Route::get('solicitudes/create', [PrestamosController::class, 'create'])->name('admin.solicitudes.create');
Route::post('solicitudes', [PrestamosController::class, 'store'])->name('admin.solicitudes.store');
Route::get('solicitudes/edit', [PrestamosController::class, 'edit'])->name('admin.solicitudes.edit');
Route::get('solicitudes/update', [PrestamosController::class, 'update'])->name('admin.solicitudes.update');
Route::post('validar-aval-antes-de-asignar', [PrestamosController::class, 'validarAvalAntesDeAsignar'])->name('admin.prestamos.validarAvalAntesDeAsignar');
Route::get('validar-prestamo-cliente/{clienteId}', [PrestamosController::class, 'validarPrestamoCliente']);
Route::get('consultar-prestamos/{clienteId}', [PrestamosController::class, 'consultarPrestamos']);

//Monto de desembolso
Route::get('/admin/prestamos/{prestamo}/monto', [PrestamosController::class, 'getMonto'])->name('admin.prestamos.monto');
Route::post('/admin/operaciones/desembolsar', [OperacionesController::class, 'desembolsar'])->name('admin.operaciones.desembolsar');

//Traer direcciones
Route::get('admin/clientes/{clienteId}/direcciones', [ClientesController::class, 'getDirecciones'])->name('admin.clientes.direcciones');
// Traer cuentas
Route::get('/clientes/{clienteId}/cuentas', [ClientesController::class, 'getCuentasCliente'])->name('admin.clientes.cuentas');

// Prestamos
Route::get('prestamos', [PrestamosController::class, 'index'])->name('admin.prestamos.index');
// Prestamos store
Route::post('/admin/prestamos', [PrestamosController::class, 'store'])->name('admin.prestamos.store');
// Avales
Route::post('admin/prestamos/consultar-aval', [PrestamosController::class, 'consultarAval'])->name('admin.prestamos.consultarAval');
// Consultar préstamos de un cliente
Route::get('admin/prestamos/consultar-prestamos/{clienteId}', [PrestamosController::class, 'consultarPrestamos'])->name('admin.prestamos.consultarPrestamos');
Route::get('consultar-prestamos/{clienteId}', [PrestamosController::class, 'consultarPrestamos'])->name('admin.prestamos.consultarPrestamos');
// Show
Route::get('/admin/prestamos/{prestamo}', [PrestamosController::class, 'show'])->name('admin.prestamos.show');
// Regularización de moras individual
Route::post('prestamos/{prestamo}/regularizar-moras', [PrestamosController::class, 'regularizarMoras'])->name('admin.prestamos.regularizar-moras');

// Resetear pagos de préstamo
Route::post('prestamos/{prestamo}/reset-payments', [PrestamosController::class, 'resetPayments'])->name('admin.prestamos.reset-payments');

// Toggle comprobantes SUNAT por préstamo
Route::post('prestamos/{prestamo}/toggle-comprobantes', [PrestamosController::class, 'toggleComprobantes'])->name('admin.prestamos.toggle-comprobantes');

// Verificar y generar moras para préstamo
Route::post('prestamos/{prestamo}/verificar-moras', [PrestamoMoraController::class, 'verificarYGenerarMoras'])->name('admin.prestamos.verificar-moras');

// Desembolso y aprobación de préstamos
Route::get('prestamos/{prestamo}/desembolsar', [PrestamosController::class, 'mostrarDesembolso'])->name('admin.prestamos.desembolsar.show');
Route::post('prestamos/{prestamo}/desembolsar', [PrestamosController::class, 'desembolsar'])->name('admin.prestamos.desembolsar');
Route::post('prestamos/{prestamo}/aprobar', [PrestamosController::class, 'aprobar'])->name('admin.prestamos.aprobar');

// Asignar aval por ID en URL (compatible con modal en show.blade)
Route::post('prestamos/{prestamo}/asignar-aval', [PrestamosController::class, 'asignarAval'])->name('admin.prestamos.asignarAvalById');
// Mostrar formulario separado para asignar aval
Route::get('prestamos/{prestamo}/asignar-aval', [PrestamosController::class, 'mostrarAsignarAval'])->name('admin.prestamos.mostrarAsignarAval');

// Actualizar personal asignado (inline edit)
Route::post('prestamos/{prestamo}/actualizar-personal', [PrestamosController::class, 'actualizarPersonal'])->name('admin.prestamos.actualizar-personal');
Route::post('prestamos/{prestamo}/actualizar-zona-sucursal', [PrestamosController::class, 'actualizarZonaSucursal'])->name('admin.prestamos.actualizar-zona-sucursal');
Route::post('prestamos/{prestamo}/actualizar-cuenta', [PrestamosController::class, 'actualizarCuenta'])->name('admin.prestamos.actualizar-cuenta');

// Proceso de préstamo (3 pasos: Aprobar -> Fondo Provisional -> Desembolsar)
Route::get('proceso-prestamo/{prestamo}', [\App\Http\Controllers\Admin\ProcesoPrestamoController::class, 'index'])->name('admin.proceso-prestamo.index');
Route::post('proceso-prestamo/{prestamo}/aprobar', [\App\Http\Controllers\Admin\ProcesoPrestamoController::class, 'aprobar'])->name('admin.proceso-prestamo.aprobar');

//Direcciones clientes
Route::get('/admin/clientes/{clienteId}/direcciones', [ClientesController::class, 'getDirecciones'])->name('admin.clientes.direcciones');
Route::get('admin/prestamos/{id}', [App\Http\Controllers\Admin\PrestamosController::class, 'show'])->name('prestamos.show');

Route::get('/prestamos/{id}', [PrestamosController::class, 'show']);

Route::get('prestamos/{prestamo}/edit', [PrestamosController::class, 'edit'])->name('admin.prestamos.edit');
Route::get('admin/prestamos/search', [PrestamosController::class, 'search'])->name('admin.prestamos.search');
Route::get('admin/prestamos/export', [PrestamosController::class, 'export'])->name('admin.prestamos.export');
Route::post('admin/prestamos/calcular-interes', [PrestamosController::class, 'calcularInteres'])->name('admin.prestamos.calcularInteres');
Route::post('admin/prestamos/recalcular-comisiones', [PrestamosController::class, 'recalcularComisiones'])->name('admin.prestamos.recalcular-comisiones');

Route::get('/admin/prestamos/{prestamo_id}/comprobante-electronico/{cuota_id}', [ComprobanteController::class, 'generarElectronico'])->name('generar.electronico');

// Operaciones - Rutas específicas primero para evitar conflictos
Route::get('/admin/operaciones', [OperacionesController::class, 'index'])->name('admin.operaciones.index');
Route::get('/admin/operaciones/buscar-cliente', [OperacionesController::class, 'buscarCliente'])->name('admin.operaciones.buscar-cliente');
Route::get('/admin/operaciones/{operacion}/editar', [OperacionesController::class, 'editar'])->name('admin.operaciones.editar');
Route::put('/admin/operaciones/{operacion}', [OperacionesController::class, 'actualizar'])->name('admin.operaciones.actualizar');
Route::post('/admin/operaciones/{operacion}/calcular-moras-edicion', [OperacionesController::class, 'calcularMorasEdicion'])->name('admin.operaciones.calcular-moras-edicion');
Route::get('/admin/operaciones/{operacion}/anular', [OperacionesController::class, 'anular'])->name('admin.operaciones.anular');
Route::get('/admin/operaciones/{operacion}', [OperacionesController::class, 'show'])->name('admin.operaciones.show');
Route::post('/admin/operaciones/{operacion}/procesar-anulacion', [OperacionesController::class, 'procesarAnulacion'])->name('admin.operaciones.procesar-anulacion');
Route::get('/admin/operaciones/{operacion}/historial', [OperacionesController::class, 'historial'])->name('admin.operaciones.historial');

//Bancos
Route::resource('bancos', BancoController::class)->except(['show'])->names('admin.bancos');

//Usuarios y Prestamos resources
Route::get('usuarios/stats', [UsuariosController::class, 'stats'])->middleware('role:Admin')->name('admin.usuarios.stats');
// Buscar persona/cliente por DNI para registro de usuario
Route::get('personas/buscar/{dni}', [UsuariosController::class, 'buscarPorDni'])->name('admin.personas.buscar.dni');
Route::resource('usuarios', UsuariosController::class)->middleware('role:Admin')->names('admin.usuarios');

// Vincular prestamos - DEBE IR ANTES del resource para evitar conflictos
Route::get('vincular-prestamos', [PrestamosController::class, 'vincularPrestamos'])->name('admin.prestamos.vincular.index');
Route::put('vincular-prestamos', [PrestamosController::class, 'vincular'])->name('admin.prestamos.vincular');
Route::get('clientes/buscar', [ClientesController::class, 'buscar'])->name('admin.clientes.buscar');
Route::get('personas/buscar', [ClientesController::class, 'buscarPersonas'])->name('admin.personas.buscar');

// Ruta para recalcular estado de préstamo (controlador centralizado)
Route::post('prestamos/{id}/recalcular-estado', [\App\Http\Controllers\Admin\EstadoPrestamoController::class, 'recalcularEstadoManual'])
    ->name('admin.prestamos.recalcular-estado');

Route::resource('prestamos', PrestamosController::class)->names('admin.prestamos');

//pagos
Route::get('registrarpago/create/{prestamo}', [RegistrarPagoController::class, 'create'])->name('admin.registrarpago.create');
Route::post('registrarpago/store', [RegistrarPagoController::class, 'store'])->name('admin.registrarpago.store');
Route::get('pagos/{operacion}/edit', [RegistrarPagoController::class, 'edit'])->name('admin.pagos.edit');
Route::put('pagos/{operacion}', [RegistrarPagoController::class, 'update'])->name('admin.pagos.update');
Route::get('pagos/{operacion}/anular', [RegistrarPagoController::class, 'showAnular'])->name('admin.pagos.anular.show');
Route::delete('pagos/{operacion}/anular', [RegistrarPagoController::class, 'anular'])->name('admin.pagos.anular');

// Rutas para validación de operaciones
Route::get('validacion-operaciones', [ValidacionOperacionesController::class, 'index'])->name('admin.validacion-operaciones.index');
Route::patch('validacion-operaciones/{operacion}/validar', [ValidacionOperacionesController::class, 'validar'])->name('admin.validacion-operaciones.validar');
Route::patch('validacion-operaciones/{operacion}/observar', [ValidacionOperacionesController::class, 'observar'])->name('admin.validacion-operaciones.observar');
Route::patch('validacion-operaciones/{operacion}/anular', [ValidacionOperacionesController::class, 'anular'])->name('admin.validacion-operaciones.anular');
Route::post('validacion-operaciones/validar-todas', [ValidacionOperacionesController::class, 'validarTodas'])->name('admin.validacion-operaciones.validar-todas');
Route::get('validacion-operaciones/{operacion}/detalle', [ValidacionOperacionesController::class, 'show'])->name('admin.validacion-operaciones.detalle');
Route::get('validacion-operaciones/estadisticas', [ValidacionOperacionesController::class, 'estadisticas'])->name('admin.validacion-operaciones.estadisticas');

// Rutas para edición de cuotas
Route::get('cuotas/{cuota}/edit', [App\Http\Controllers\Admin\CuotasController::class, 'edit'])->name('admin.cuotas.edit');
Route::put('cuotas/{cuota}', [App\Http\Controllers\Admin\CuotasController::class, 'update'])->name('admin.cuotas.update');

// Convenios de Pago
Route::resource('convenios', App\Http\Controllers\Admin\ConvenioController::class)->names('admin.convenios');
Route::patch('convenios/{convenio}/cancelar', [App\Http\Controllers\Admin\ConvenioController::class, 'cancelar'])->name('admin.convenios.cancelar');
Route::post('convenios/calcular', [App\Http\Controllers\Admin\ConvenioController::class, 'calcularConvenio'])->name('admin.convenios.calcular');

// Pagos de Cuotas de Convenio
Route::get('convenios/cuotas/{cuotaConvenio}/pagar', [App\Http\Controllers\Admin\ConvenioController::class, 'mostrarFormularioPago'])->name('admin.convenios.cuotas.pagar.form');
Route::post('convenios/cuotas/{cuotaConvenio}/pagar', [App\Http\Controllers\Admin\ConvenioController::class, 'procesarPago'])->name('admin.convenios.cuotas.pagar');

// Liquidación de Convenios
Route::get('convenios/{convenio}/liquidar', [App\Http\Controllers\Admin\ConvenioController::class, 'mostrarFormularioLiquidacion'])->name('admin.convenios.liquidar.form');
Route::post('convenios/{convenio}/liquidar', [App\Http\Controllers\Admin\ConvenioController::class, 'ejecutarLiquidacion'])->name('admin.convenios.liquidar');

// PDF Estado de Cuenta de Convenio
Route::get('convenios/{convenio}/estado-cuenta-pdf', [App\Http\Controllers\Admin\ConvenioController::class, 'estadoCuentaPDF'])->name('admin.convenios.estado-cuenta.pdf');
Route::get('convenios/{convenio}/estado-cuenta-preview', [App\Http\Controllers\Admin\ConvenioController::class, 'estadoCuentaPreview'])->name('admin.convenios.estado-cuenta.preview');
Route::get('convenios/{convenio}/estado-cuenta-descargar', [App\Http\Controllers\Admin\ConvenioController::class, 'descargarEstadoCuenta'])->name('admin.convenios.estado-cuenta.descargar');

// Rutas para convenios flexibles
Route::get('convenios/{convenio}/pagar-flexible', [App\Http\Controllers\Admin\ConvenioController::class, 'mostrarFormularioPagoFlexible'])->name('admin.convenios.flexible.pagar.form');
Route::post('convenios/{convenio}/pagar-flexible', [App\Http\Controllers\Admin\ConvenioController::class, 'procesarPagoFlexible'])->name('admin.convenios.flexible.pagar');
Route::get('convenios/{convenio}/liquidar-flexible', [App\Http\Controllers\Admin\ConvenioController::class, 'mostrarFormularioLiquidacionFlexible'])->name('admin.convenios.flexible.liquidar.form');
Route::post('convenios/{convenio}/liquidar-flexible', [App\Http\Controllers\Admin\ConvenioController::class, 'ejecutarLiquidacionFlexible'])->name('admin.convenios.flexible.liquidar');

//sucursales
Route::prefix('admin/sucursales')->group(function () {
    Route::get('/admin/sucursales', [SucursalesController::class, 'index'])->name('admin.sucursales.index');
    Route::get('/create', [SucursalesController::class, 'create'])->name('admin.sucursales.create');
    Route::post('/store', [SucursalesController::class, 'store'])->name('admin.sucursales.store');
    Route::get('/{sucursal}/edit', [SucursalesController::class, 'edit'])->name('admin.sucursales.edit');
    Route::put('/admin/sucursales/{sucursal}', [SucursalesController::class, 'update'])->name('admin.sucursales.update');
    Route::delete('/{sucursal}', [SucursalesController::class, 'destroy'])->name('admin.sucursales.destroy');
});

// Ruta para cargar provincias dinámicamente
Route::get('/api/departamento/{departamento_id}/provincias', [SucursalesController::class, 'getProvincias']);

// API para autocompletado de clientes
Route::get('/api/buscar-clientes', [ClientesController::class, 'buscarClientesAutocompletado'])->name('admin.api.buscar-clientes');

//Cuentas
Route::resource('cuentas', CuentasController::class)->names('admin.cuentas');
//Cuentas por cliente
Route::get('admin/clientes/{clienteId}/cuentas', [ClientesController::class, 'getCuentasCliente'])->name('admin.clientes.cuentas');

//Roles
Route::resource('roles', RoleController::class)->middleware('role:Admin')->names('admin.roles');
Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])->middleware('role:Admin')->name('admin.roles.permissions');
Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->middleware('role:Admin')->name('admin.roles.updatePermissions');
// Roles additional routes (already handled by resource above)

// Sucursales
Route::get('/sucursales/index', [SucursalesController::class, 'index'])->name('admin.sucursales.index');
Route::get('/sucursales/create', [SucursalesController::class, 'create'])->name('admin.sucursales.create');
Route::get('/sucursales/{sucursal}/edit', [SucursalesController::class, 'edit'])->name('sucursales.edit');
Route::get('/sucursales/destroy', [SucursalesController::class, 'destroy'])->name('admin.sucursales.destroy');

// Métodos de pago
Route::resource('metodosdepago', MetodosDePagoController::class)->names('admin.metodosdepago');
Route::get('admin/operacion/comprobante/{operacion_id}', [OperacionController::class, 'generarComprobante'])->name('admin.operacion.comprobante');

//Pagos cuotas
Route::get('registrarpago/create/{prestamo_id}', [RegistrarPagoController::class, 'create'])->name('admin.registrarpago.create');

//Comprobantes
Route::get('/generar-comprobante', [ComprobanteController::class, 'generarComprobante'])->name('admin.comprobante.generar');
Route::get('admin/operacion/{operacion_id}/comprobante', 'OperacionController@generarComprobante')->name('admin.operacion.comprobante');
Route::get('admin/operacion/{operacion_id}/comprobante', [OperacionController::class, 'generarComprobante'])->name('admin.operacion.comprobante');

// Moras
Route::prefix('moras')->group(function () {
    Route::post('generar', [MoraController::class, 'generarMoras'])->name('moras.generar');
    Route::post('pagar/{moraId}', [MoraController::class, 'pagarMora'])->name('moras.pagar');
    Route::get('pendientes', [MoraController::class, 'verMorasPendientes'])->name('moras.pendientes');
    Route::post('generar-mora/{cuota}', [MoraController::class, 'generarMoraIndividual'])->name('generar.mora');
});

// Actualizar Préstamo
Route::post('prestamos/{prestamo}/actualizar', [MoraController::class, 'actualizarPrestamo'])->name('prestamos.actualizar');

// routes/web.php
Route::middleware('auth')->group(function () {
    Route::resource('tasas', TasasController::class)->names('admin.tasas');
    Route::get('tasas/{tasa}/history', [TasasController::class, 'history'])->name('admin.tasas.history');
});

//Gestiones
Route::resource('gestiones', GestionCobranzaController::class)->names('admin.gestiones');

// Estados gestión
Route::resource('estadosgestion', EstadoGestionController::class)->names('admin.estadosgestion');

//Compromisos
Route::resource('compromisos', CompromisosController::class)->names('admin.compromisos');

//Zonas
Route::resource('zonas', ZonasController::class)->names('admin.zonas');
Route::post('/validar-zona', [ZonasController::class, 'validarZona'])->name('validar-zona');
Route::post('/validar-tipozona', [ZonasController::class, 'validarTipoZona'])->name('validar-tipozona');
Route::get('comprobantes/operacion', function () {
    return view('admin.Comprobantes.operacion');
})->name('admin.comprobantes.operacion');

Route::post('/admin/home/filter', [HomeController::class, 'filter'])->name('admin.home.filter');
//PDF
Route::get('/operaciones/{prestamo_id}/generar-pdf', [OperacionController::class, 'generarPDF'])
    ->name('operaciones.generar-pdf');

Route::get('/operaciones/{prestamo_id}/{operacion_id}/generar-pdf', [OperacionController::class, 'generarPDFIndividual'])
    ->name('operaciones.generar-pdf-individual');

Route::get('/admin/prestamos/{id}/estado-cuenta/pdf', [PrestamosController::class, 'generarEstadoCuentaPDF'])
    ->name('prestamos.generarEstadoCuentaPDF');

//Liquidaciones - Controlador especializado
Route::get('/prestamos/{id}/calcular-liquidacion', [\App\Http\Controllers\Admin\LiquidacionController::class, 'calcular'])
    ->name('prestamos.calcularLiquidacion');

Route::post('/prestamos/{id}/liquidar', [\App\Http\Controllers\Admin\LiquidacionController::class, 'ejecutar'])
    ->name('prestamos.liquidar');

Route::get('/prestamos/{prestamo}/liquidacion-modal', [PrestamosController::class, 'mostrarModalLiquidacion'])
    ->name('admin.prestamos.liquidacion-modal');

Route::get('/prestamos/{prestamo}/liquidacion-ventana', [PrestamosController::class, 'mostrarVentanaLiquidacion'])
    ->name('admin.prestamos.liquidacion-ventana');

// Metodos de pago
Route::get('/metodos-pago', [PrestamosController::class, 'getMetodosPago']);

//Carteras
Route::middleware(['auth', 'role:Admin|Supervisor'])->group(function () {
    // Rutas para la gestión de carteras
    Route::get('carteras', [App\Http\Controllers\Admin\CarteraController::class, 'index'])->name('admin.carteras.index');
    Route::get('carteras/pdf', [App\Http\Controllers\Admin\CarteraController::class, 'generarPDF'])->name('admin.carteras.pdf');

    // Rutas para Sincronización de Bases de Datos
    Route::prefix('database-sync')->name('admin.database-sync.')->middleware('database.firewall')->group(function () {
        Route::get('/', [DatabaseSyncController::class, 'dashboard'])->name('dashboard');
        Route::get('/configuration', [DatabaseSyncController::class, 'configuration'])->name('configuration');
        Route::get('/logs', [DatabaseSyncController::class, 'logs'])->name('logs');
        Route::get('/monitoring', [DatabaseSyncController::class, 'monitoring'])->name('monitoring');
        Route::get('/blocked-ips', [DatabaseSyncController::class, 'blockedIPs'])->name('blocked-ips');
        Route::get('/backups', [DatabaseSyncController::class, 'backups'])->name('backups');

        // Centro de sincronización
        Route::get('/syncbd', function () {
            $activeConnections = \App\Models\DatabaseConnection::active()->count();
            $syncEnabledCount = \App\Models\DatabaseConnection::syncEnabled()->count();
            $lastSync = \App\Models\DatabaseConnection::whereNotNull('last_sync_at')
                ->orderBy('last_sync_at', 'desc')
                ->first();
            $syncErrors = \App\Models\DatabaseConnection::whereNotNull('sync_errors')->count();

            return view('admin.database-sync.syncbd', [
                'activeConnections' => $activeConnections,
                'syncEnabledCount' => $syncEnabledCount,
                'lastSyncTime' => $lastSync ? $lastSync->last_sync_at->diffForHumans() : 'Nunca',
                'syncErrors' => $syncErrors,
            ]);
        })->name('syncbd');

        // Gestión de conexiones de bases de datos
        Route::get('/connections', [DatabaseSyncController::class, 'connections'])->name('connections');
        Route::get('/connections/{connection}', [DatabaseSyncController::class, 'showConnection'])->name('connections.show');
        Route::post('/connections', [DatabaseSyncController::class, 'createConnection'])->name('connections.create');
        Route::put('/connections/{connection}', [DatabaseSyncController::class, 'updateConnection'])->name('connections.update');
        Route::delete('/connections/{connection}', [DatabaseSyncController::class, 'deleteConnection'])->name('connections.delete');
        Route::post('/connections/{connection}/test', [DatabaseSyncController::class, 'testConnection'])->name('connections.test');
        Route::post('/connections/test', [DatabaseSyncController::class, 'testConnection'])->name('connections.test-new');
        Route::post('/connections/{connection}/toggle', [DatabaseSyncController::class, 'toggleConnection'])->name('connections.toggle');
        Route::post('/sync-tables', [DatabaseSyncController::class, 'syncTables'])->name('sync-tables');

        // API endpoints
        Route::get('/api/metrics', [DatabaseSyncController::class, 'apiMetrics'])->name('api.metrics');

        // Actions
        Route::post('/blocked-ips', [DatabaseSyncController::class, 'blockedIPs'])->name('blocked-ips.action');
        Route::post('/create-backup', [DatabaseSyncController::class, 'createBackup'])->name('create-backup');
        Route::post('/verify-integrity', [DatabaseSyncController::class, 'verifyIntegrity'])->name('verify-integrity');
        Route::post('/toggle-emergency', [DatabaseSyncController::class, 'toggleEmergencyMode'])->name('toggle-emergency');
    });
    Route::get('carteras/estado-cuenta/{prestamo_id}', [App\Http\Controllers\Admin\CarteraController::class, 'estadoCuenta'])->name('admin.carteras.estado-cuenta');
});

//PREVISUALIZACIÓN ESTADO DE CUENTA
Route::get('prestamos/{id}/estado-cuenta-preview', [PrestamosController::class, 'estadoCuentaPreviewHtml'])->name('prestamos.estadoCuentaPreviewHtml');

//PDF ESTADO DE CUENTA PARA IMPRIMIR
Route::get('prestamos/{id}/estado-cuenta-pdf', [PrestamosController::class, 'estadoCuentaPDF'])->name('prestamos.estadoCuentaPDF');

//Caja
Route::get('caja', [CajaController::class, 'index'])->name('admin.caja.index');
Route::post('caja/filter', [CajaController::class, 'filterAjax'])->name('admin.caja.filterAjax');
Route::post('caja/{operacion_id}/update-estado', [CajaController::class, 'updateEstado'])->name('admin.caja.updateEstado');
Route::post('caja/cierre-diario', [CajaController::class, 'cierreDiario'])->name('admin.caja.cierreDiario');
// Nuevas rutas para rendición completa por usuario
Route::get('caja/usuario/{user_id}/rendicion', [CajaController::class, 'mostrarRendicionUsuario'])->name('admin.caja.mostrarRendicionUsuario');
Route::post('caja/rendicion-parcial', [CajaController::class, 'procesarRendicionParcial'])->name('admin.caja.procesarRendicionParcial');
Route::get('caja/rendir-todo-usuario/{user_id}', [CajaController::class, 'rendirTodoUsuario'])->name('admin.caja.rendirTodoUsuario');
Route::get('caja/historial-rendiciones', [CajaController::class, 'historialRendiciones'])->name('admin.caja.historialRendiciones');
Route::get('caja/recent-rendiciones', [CajaController::class, 'getRecentRendiciones'])->name('admin.caja.getRecentRendiciones');

//pdf rendicion
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/caja/rendir-por-asesor/{user_id}', [CajaController::class, 'rendirPorAsesor'])->name('caja.rendirPorAsesor');
    Route::get('/caja/rendir-por-jcc/{user_id}', [CajaController::class, 'rendirPorJcc'])->name('caja.rendirPorJcc');
});

// tabla de compromisos
Route::get('compromisos', [CompromisosController::class, 'index'])->name('admin.compromisos.index');

//Plazos
Route::resource('plazos', App\Http\Controllers\Admin\PlazosController::class)->names('admin.plazos');

//Moras
//Moras
Route::middleware('auth')->group(function () {
    Route::resource('moras', MoraController::class)->names('admin.moras');
    Route::get('moras/{mora}/history', [MoraController::class, 'history'])->name('admin.moras.history');
    Route::get('moras/{mora}/historial', [MoraController::class, 'history'])->name('admin.moras.historial');
    Route::put('moras/{mora}/editar', [MoraController::class, 'editarMora'])->name('admin.moras.editar');
    Route::get('moras/{mora}/anular', [MoraController::class, 'showAnularMora'])->name('admin.moras.anular.show');
    Route::delete('moras/{mora}/anular', [MoraController::class, 'anularMora'])->name('admin.moras.anular');
});

// Ruta para el reporte de deudas y moras
// Rutas para el módulo de deudas
Route::get('deudas/tramos', [DeudasController::class, 'tramos'])->name('admin.deudas.tramos');
Route::post('deudas/tramos/data', [DeudasController::class, 'getTramosData'])->name('admin.deudas.tramos.data');
Route::get('deudas', [DeudasController::class, 'index'])->name('admin.deudas.index');
Route::get('deudas/zonas-by-sucursal', [DeudasController::class, 'getZonasBySucursal'])->name('admin.deudas.zonasBySucursal');
Route::get('deudas/sucursales-by-zona', [DeudasController::class, 'getSucursalesByZona'])->name('admin.deudas.sucursalesByZona');

// Si tienes las rutas para PDF individual
Route::get('deudas/estado-cobranza/{cuota}', [DeudasController::class, 'descargarEstadoCobranza'])->name('admin.deudas.estadoCobranza');
Route::get('deudas/preview-estado-cobranza/{cuota}', [DeudasController::class, 'previsualizacionEstadoCobranza'])->name('admin.deudas.previewEstadoCobranza');
Route::get('deudas/debug-pdf', [DeudasController::class, 'debugPdf'])->name('admin.deudas.debugPdf');
//Consulta dni conyuge
Route::post('/consultar-dni-conyuge', [ClientesController::class, 'consultarDNIConyuge'])->name('consultar.dniconyuge');

// Rutas para estado de cuenta PDF
Route::get('/admin/prestamos/{id}/estado-cuenta-preview', [PrestamosController::class, 'estadoCuentaPreview'])
    ->name('admin.prestamos.estado-cuenta-preview');

Route::get('/admin/prestamos/{id}/estado-cuenta-download', [PrestamosController::class, 'descargarEstadoCuenta'])
    ->name('admin.prestamos.estado-cuenta-download');

Route::get('/admin/deudas/estado-cobranza/{cuota}', [DeudasController::class, 'descargarEstadoCobranza'])
    ->name('admin.deudas.estado-cobranza');

Route::get('/admin/deudas/estado-cobranza-preview/{cuota}', [App\Http\Controllers\Admin\DeudasController::class, 'previsualizacionEstadoCobranza'])
    ->name('admin.deudas.estado-cobranza-preview');

Route::get('/admin/deudas/previsualizacion-estado-cobranza/{cuota}', [DeudasController::class, 'previsualizacionEstadoCobranza'])
    ->name('admin.deudas.previsualizacion-estado-cobranza');

Route::get('/admin/deudas/descargar-estado-cobranza/{cuota}', [DeudasController::class, 'descargarEstadoCobranza'])
    ->name('admin.deudas.descargar-estado-cobranza');

Route::get('/admin/deudas/zonas-by-sucursal', [DeudasController::class, 'getZonasBySucursal'])->name('admin.deudas.zonasBySucursal');
Route::get('/admin/deudas/sucursales-by-zona', [DeudasController::class, 'getSucursalesByZona'])->name('admin.deudas.sucursalesByZona');

// Comprobantes Electrónicos
Route::middleware('auth')->group(function () {
    Route::get('comprobantes/exportar', [\App\Http\Controllers\Admin\ComprobantesController::class, 'exportar'])->name('admin.comprobantes.exportar');
    Route::post('comprobantes/exportar-cuotas', [\App\Http\Controllers\Admin\ComprobantesController::class, 'exportarCuotas'])->name('admin.comprobantes.exportar-cuotas');
    Route::resource('comprobantes', \App\Http\Controllers\Admin\ComprobantesController::class)->names('admin.comprobantes');
    Route::post('comprobantes/{comprobante}/reenviar', [\App\Http\Controllers\Admin\ComprobantesController::class, 'reenviar'])->name('admin.comprobantes.reenviar');
    Route::post("comprobantes/reenviar-todos", [\App\Http\Controllers\Admin\ComprobantesController::class, "reenviarTodos"])->name("admin.comprobantes.reenviar-todos");
    Route::post('comprobantes/{comprobante}/consultar-estado', [\App\Http\Controllers\Admin\ComprobantesController::class, 'consultarEstado'])->name('admin.comprobantes.consultar-estado');
    Route::post('comprobantes/{comprobante}/regularizar', [\App\Http\Controllers\Admin\ComprobantesController::class, 'regularizar'])->name('admin.comprobantes.regularizar');
    Route::get('comprobantes/{comprobante}/pdf', [\App\Http\Controllers\Admin\ComprobantesController::class, 'descargarPdf'])->name('admin.comprobantes.pdf');
    Route::get('comprobantes/{comprobante}/xml', [\App\Http\Controllers\Admin\ComprobantesController::class, 'descargarXml'])->name('admin.comprobantes.xml');
    Route::get('comprobantes/{comprobante}/cdr', [\App\Http\Controllers\Admin\ComprobantesController::class, 'descargarCdr'])->name('admin.comprobantes.cdr');
    Route::post('comprobantes/emitir-cuota', [\App\Http\Controllers\Admin\ComprobantesController::class, 'emitirCuota'])->name('admin.comprobantes.emitir-cuota');
    Route::get('comprobantes/preview-cuota/{cuotaId}', [\App\Http\Controllers\Admin\ComprobantesController::class, 'previewCuota'])->name('admin.comprobantes.preview-cuota');
    Route::post('comprobantes/generar-comprobante-cuota', [\App\Http\Controllers\Admin\ComprobantesController::class, 'generarComprobanteCuota'])->name('admin.comprobantes.generar-comprobante-cuota');
    Route::get('comprobantes/siguiente-numero', [\App\Http\Controllers\Admin\ComprobantesController::class, 'obtenerSiguienteNumero'])->name('admin.comprobantes.siguiente-numero');
    Route::post('comprobantes/{comprobante}/anular', [\App\Http\Controllers\Admin\ComprobantesController::class, 'anular'])->name('admin.comprobantes.anular');

    // Notas de Crédito y Débito
    Route::post('comprobantes/{comprobante}/nota-credito', [\App\Http\Controllers\Admin\ComprobantesController::class, 'generarNotaCredito'])->name('admin.comprobantes.nota-credito');
    Route::post('comprobantes/{comprobante}/nota-debito', [\App\Http\Controllers\Admin\ComprobantesController::class, 'generarNotaDebito'])->name('admin.comprobantes.nota-debito');

    // Comprobantes Declarados (Listado con XMLs y CDRs)
    Route::get('comprobantes-declarados', [\App\Http\Controllers\Admin\ComprobantesDeclaradosController::class, 'index'])->name('admin.comprobantes.declarados');
    Route::get('comprobantes-declarados/{id}', [\App\Http\Controllers\Admin\ComprobantesDeclaradosController::class, 'show']);
    Route::get('comprobantes-declarados/{id}/descargar-xml', [\App\Http\Controllers\Admin\ComprobantesDeclaradosController::class, 'descargarXml'])->name('admin.comprobantes.descargar-xml');
    Route::get('comprobantes-declarados/{id}/descargar-cdr', [\App\Http\Controllers\Admin\ComprobantesDeclaradosController::class, 'descargarCdr'])->name('admin.comprobantes.descargar-cdr');
    Route::post('comprobantes-declarados/{id}/reenviar', [\App\Http\Controllers\Admin\ComprobantesDeclaradosController::class, 'reenviar']);
    Route::get('comprobantes-declarados-exportar', [\App\Http\Controllers\Admin\ComprobantesDeclaradosController::class, 'exportar'])->name('admin.comprobantes.exportar');
});

// Estado y Monitoreo de SUNAT
Route::middleware('auth')->group(function () {
    Route::get('sunat-status', [\App\Http\Controllers\Admin\SunatStatusController::class, 'index'])->name('admin.sunat-status.index');
    Route::get('sunat-status/estado', [\App\Http\Controllers\Admin\SunatStatusController::class, 'estado'])->name('admin.sunat-status.estado');
    Route::get('sunat-status/historial', [\App\Http\Controllers\Admin\SunatStatusController::class, 'historial'])->name('admin.sunat-status.historial');
    Route::get('sunat-status/estadisticas', [\App\Http\Controllers\Admin\SunatStatusController::class, 'estadisticas'])->name('admin.sunat-status.estadisticas');
    Route::post('sunat-status/refrescar', [\App\Http\Controllers\Admin\SunatStatusController::class, 'refrescar'])->name('admin.sunat-status.refrescar');
});

// Configuración SUNAT
Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::resource('configuracion-sunat', \App\Http\Controllers\Admin\ConfiguracionSunatController::class)->names('admin.configuracion-sunat');
    Route::post('configuracion-sunat/{configuracionSunat}/activar', [\App\Http\Controllers\Admin\ConfiguracionSunatController::class, 'activar'])->name('admin.configuracion-sunat.activar');
    Route::post('configuracion-sunat/{configuracionSunat}/test-conexion', [\App\Http\Controllers\Admin\ConfiguracionSunatController::class, 'testConexion'])->name('admin.configuracion-sunat.test-conexion');
});

// Diagnóstico SUNAT (accesible para más roles)
Route::middleware('auth')->group(function () {
    Route::match(['GET', 'POST'], 'sunat/diagnostico', [\App\Http\Controllers\Admin\ConfiguracionSunatController::class, 'diagnostico'])->name('admin.sunat.diagnostico');
    Route::get('sunat/buscar-comprobante', [\App\Http\Controllers\Admin\ConfiguracionSunatController::class, 'buscarComprobante'])->name('admin.configuracion-sunat.buscar-comprobante');
    Route::post('sunat/consultar-comprobante', [\App\Http\Controllers\Admin\ConfiguracionSunatController::class, 'consultarComprobanteSunat'])->name('admin.configuracion-sunat.consultar-comprobante');
    Route::get('sunat/api-config', [\App\Http\Controllers\Admin\ConfiguracionSunatController::class, 'apiConfig'])->name('admin.configuracion-sunat.api-config');
    Route::post('sunat/api-config', [\App\Http\Controllers\Admin\ConfiguracionSunatController::class, 'apiConfigSave'])->name('admin.configuracion-sunat.api-config.save');

    // Configuración SIRE/Greenter
    Route::get('sunat/sire-config', [\App\Http\Controllers\Admin\ConfiguracionSunatController::class, 'sireConfig'])->name('admin.sire-config.index');
    Route::post('sunat/sire-config', [\App\Http\Controllers\Admin\ConfiguracionSunatController::class, 'sireConfigSave'])->name('admin.sire-config.save');
    Route::post('sunat/sire-config/test-connection', [\App\Http\Controllers\Admin\ConfiguracionSunatController::class, 'testSireConnection'])->name('admin.sire-config.test-connection');

    Route::get('sunat/archivos-info', [\App\Http\Controllers\Admin\ConfiguracionSunatController::class, 'informacionArchivos'])->name('admin.sunat.archivos-info');
});

// Dashboard de Facturación
Route::middleware(['auth', 'role:Admin|Contador'])->group(function () {
    Route::get('facturacion/dashboard', [\App\Http\Controllers\Admin\FacturacionDashboardController::class, 'index'])->name('admin.facturacion.dashboard');
    Route::post('facturacion/reintentar/{comprobanteId}', [\App\Http\Controllers\Admin\FacturacionDashboardController::class, 'forzarReintento'])->name('admin.facturacion.reintentar');
    Route::post('facturacion/cancelar-reintento/{reintentoId}', [\App\Http\Controllers\Admin\FacturacionDashboardController::class, 'cancelarReintento'])->name('admin.facturacion.cancelar-reintento');
});

// Configuración de Marca
Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('brand', [\App\Http\Controllers\Admin\BrandController::class, 'index'])->name('admin.brand.index');
    Route::put('brand', [\App\Http\Controllers\Admin\BrandController::class, 'update'])->name('admin.brand.update');
});

// Gastos
Route::middleware('auth')->group(function () {
    Route::resource('gastos', \App\Http\Controllers\Admin\GastosController::class)->names('admin.gastos');
    Route::resource('categorias-gastos', \App\Http\Controllers\Admin\CategoriasGastosController::class)->names('admin.categorias-gastos');
    Route::post('gastos/consultar-documento', [\App\Http\Controllers\Admin\GastosController::class, 'consultarDocumento'])->name('admin.gastos.consultarDocumento');
});

// Profile and Security Configuration
Route::middleware('auth')->group(function () {
    Route::get('profile/edit', [ProfileController::class, 'edit'])->name('admin.profile.edit');
    Route::put('profile/update', [ProfileController::class, 'update'])->name('admin.profile.update');
    Route::get('password/change', [\App\Http\Controllers\Admin\PasswordController::class, 'edit'])->name('admin.password.change');
    Route::put('password/update', [\App\Http\Controllers\Admin\PasswordController::class, 'update'])->name('admin.password.update');
});

// Prestamo Documents
Route::middleware('auth')->group(function () {
    Route::post('prestamos/documents/upload', [\App\Http\Controllers\Admin\PrestamoDocumentController::class, 'upload'])->name('admin.prestamos.documents.upload');
    Route::get('prestamos/{prestamo}/documents', [\App\Http\Controllers\Admin\PrestamoDocumentController::class, 'list'])->name('admin.prestamos.documents.list');
    Route::get('prestamos/documents/{document}/preview', [\App\Http\Controllers\Admin\PrestamoDocumentController::class, 'preview'])->name('admin.prestamos.documents.preview');
    Route::get('prestamos/documents/{document}/download', [\App\Http\Controllers\Admin\PrestamoDocumentController::class, 'download'])->name('admin.prestamos.documents.download');
    Route::delete('prestamos/documents/{document}', [\App\Http\Controllers\Admin\PrestamoDocumentController::class, 'delete'])->name('admin.prestamos.documents.delete');
    // Debug route
    Route::get('prestamos/documents/{document}/debug', [\App\Http\Controllers\Admin\PrestamoDocumentController::class, 'debug'])->name('admin.prestamos.documents.debug');

    // Generate PDF documents
    Route::get('prestamos/{prestamo}/generate-carta-no-adeudo', [\App\Http\Controllers\Admin\PrestamoDocumentController::class, 'generateCartaNoAdeudo'])->name('admin.prestamos.generate.carta-no-adeudo');
    Route::get('prestamos/{prestamo}/preview-carta-no-adeudo', [\App\Http\Controllers\Admin\PrestamoDocumentController::class, 'previewCartaNoAdeudo'])->name('admin.prestamos.preview.carta-no-adeudo');
    Route::get('prestamos/{prestamo}/download-carta-no-adeudo', [\App\Http\Controllers\Admin\PrestamoDocumentController::class, 'downloadCartaNoAdeudo'])->name('admin.prestamos.download.carta-no-adeudo');
    Route::get('prestamos/{prestamo}/check-carta-no-adeudo', [\App\Http\Controllers\Admin\PrestamoDocumentController::class, 'checkCartaNoAdeudo'])->name('admin.prestamos.check.carta-no-adeudo');

    // Contrato de Mutuo routes
    Route::get('prestamos/{prestamo}/generate-contrato-mutuo', [\App\Http\Controllers\Admin\PrestamoDocumentController::class, 'generateContratoMutuo'])->name('admin.prestamos.generate.contrato-mutuo');
    Route::get('prestamos/{prestamo}/preview-contrato-mutuo', [\App\Http\Controllers\Admin\PrestamoDocumentController::class, 'previewContratoMutuo'])->name('admin.prestamos.preview.contrato-mutuo');
    Route::get('prestamos/{prestamo}/download-contrato-mutuo', [\App\Http\Controllers\Admin\PrestamoDocumentController::class, 'downloadContratoMutuo'])->name('admin.prestamos.download.contrato-mutuo');
    Route::get('prestamos/{prestamo}/check-contrato-mutuo', [\App\Http\Controllers\Admin\PrestamoDocumentController::class, 'checkContratoMutuo'])->name('admin.prestamos.check.contrato-mutuo');
});

// Template Editor
Route::middleware('auth')->group(function () {
    Route::get('template-editor', [\App\Http\Controllers\Admin\TemplateEditorController::class, 'index'])->name('admin.template-editor.index');
    Route::get('template-editor/variables', [\App\Http\Controllers\Admin\TemplateEditorController::class, 'variables'])->name('admin.template-editor.variables');
    Route::post('template-editor/update', [\App\Http\Controllers\Admin\TemplateEditorController::class, 'update'])->name('admin.template-editor.update');
    Route::post('template-editor/preview', [\App\Http\Controllers\Admin\TemplateEditorController::class, 'preview'])->name('admin.template-editor.preview');
    Route::get('template-editor/backups', [\App\Http\Controllers\Admin\TemplateEditorController::class, 'getBackups'])->name('admin.template-editor.backups');
    Route::post('template-editor/restore/{backupId}', [\App\Http\Controllers\Admin\TemplateEditorController::class, 'restore'])->name('admin.template-editor.restore');

    // New routes for variables functionality
    Route::get('template-editor/prestamos', [\App\Http\Controllers\Admin\TemplateEditorController::class, 'getPrestamos'])->name('admin.template-editor.prestamos');
    Route::post('template-editor/load-data', [\App\Http\Controllers\Admin\TemplateEditorController::class, 'loadPrestamoData'])->name('admin.template-editor.load-data');
    Route::post('template-editor/save-variables', [\App\Http\Controllers\Admin\TemplateEditorController::class, 'saveVariables'])->name('admin.template-editor.save-variables');
    Route::post('template-editor/preview-variables', [\App\Http\Controllers\Admin\TemplateEditorController::class, 'previewWithVariables'])->name('admin.template-editor.preview-variables');
});

// Respaldos del Sistema
Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('respaldos', [\App\Http\Controllers\Admin\RespaldosController::class, 'index'])->name('admin.respaldos.index');
    Route::post('respaldos/crear', [\App\Http\Controllers\Admin\RespaldosController::class, 'crearRespaldo'])->name('admin.respaldos.crear');
    Route::get('respaldos/descargar/{archivo}', [\App\Http\Controllers\Admin\RespaldosController::class, 'descargar'])->name('admin.respaldos.descargar');
    Route::delete('respaldos/eliminar/{archivo}', [\App\Http\Controllers\Admin\RespaldosController::class, 'eliminar'])->name('admin.respaldos.eliminar');
    Route::post('respaldos/restaurar', [\App\Http\Controllers\Admin\RespaldosController::class, 'restaurar'])->name('admin.respaldos.restaurar');
    Route::post('respaldos/programar', [\App\Http\Controllers\Admin\RespaldosController::class, 'programarRespaldo'])->name('admin.respaldos.programar');
});

// Migraciones de Base de Datos
Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('migraciones', [MigracionesController::class, 'index'])->name('admin.migraciones.index');
    Route::post('migraciones/ejecutar', [MigracionesController::class, 'ejecutar'])->name('admin.migraciones.ejecutar');
    Route::post('migraciones/ejecutar-todas', [MigracionesController::class, 'ejecutarTodas'])->name('admin.migraciones.ejecutar-todas');
    Route::post('migraciones/rollback', [MigracionesController::class, 'rollback'])->name('admin.migraciones.rollback');
    Route::post('migraciones/fresh', [MigracionesController::class, 'fresh'])->name('admin.migraciones.fresh');
    Route::get('migraciones/estado', [MigracionesController::class, 'estado'])->name('admin.migraciones.estado');
});

// Logs del Sistema
Route::middleware(['auth', 'role:Admin|Supervisor'])->group(function () {
    Route::get('logs', [\App\Http\Controllers\Admin\LogsController::class, 'index'])->name('admin.logs.index');
    Route::get('logs/detalle/{id}', [\App\Http\Controllers\Admin\LogsController::class, 'detalle'])->name('admin.logs.detalle');
    Route::get('logs/exportar', [\App\Http\Controllers\Admin\LogsController::class, 'exportar'])->name('admin.logs.exportar');
    Route::get('logs/estadisticas', [\App\Http\Controllers\Admin\LogsController::class, 'estadisticas'])->name('admin.logs.estadisticas');
    Route::post('logs/limpiar', [\App\Http\Controllers\Admin\LogsController::class, 'limpiarLogs'])->name('admin.logs.limpiar');
});

// Monitoreo del Sistema
Route::middleware(['auth', 'role:Admin|Supervisor'])->group(function () {
    Route::get('monitoreo', [\App\Http\Controllers\Admin\MonitoreoController::class, 'index'])->name('admin.monitoreo.index');
    Route::get('monitoreo/metricas', [\App\Http\Controllers\Admin\MonitoreoController::class, 'metricas'])->name('admin.monitoreo.metricas');
    Route::get('monitoreo/alertas', [\App\Http\Controllers\Admin\MonitoreoController::class, 'alertas'])->name('admin.monitoreo.alertas');
    Route::get('monitoreo/configuracion', [\App\Http\Controllers\Admin\MonitoreoController::class, 'configuracion'])->name('admin.monitoreo.configuracion');
    Route::post('monitoreo/configuracion', [\App\Http\Controllers\Admin\MonitoreoController::class, 'configuracion'])->name('admin.monitoreo.configuracion.store');
});

// Constructor de Reportes
Route::middleware(['auth', 'role:Admin|Supervisor|JCC'])->group(function () {
    Route::get('constructor-reportes', [\App\Http\Controllers\Admin\ConstructorReportesController::class, 'index'])->name('admin.constructor-reportes.index');
    Route::get('constructor-reportes/nuevo', [\App\Http\Controllers\Admin\ConstructorReportesController::class, 'constructor'])->name('admin.constructor-reportes.constructor');
    Route::get('constructor-reportes/editar/{id}', [\App\Http\Controllers\Admin\ConstructorReportesController::class, 'constructor'])->name('admin.constructor-reportes.editar');
    Route::get('constructor-reportes/campos', [\App\Http\Controllers\Admin\ConstructorReportesController::class, 'obtenerCampos'])->name('admin.constructor-reportes.campos');
    Route::post('constructor-reportes/previsualizar', [\App\Http\Controllers\Admin\ConstructorReportesController::class, 'previsualizar'])->name('admin.constructor-reportes.previsualizar');
    Route::post('constructor-reportes/generar', [\App\Http\Controllers\Admin\ConstructorReportesController::class, 'generar'])->name('admin.constructor-reportes.generar');
    Route::post('constructor-reportes/guardar', [\App\Http\Controllers\Admin\ConstructorReportesController::class, 'guardar'])->name('admin.constructor-reportes.guardar');
    Route::delete('constructor-reportes/eliminar/{id}', [\App\Http\Controllers\Admin\ConstructorReportesController::class, 'eliminarReporte'])->name('admin.constructor-reportes.eliminar');
    Route::get('constructor-reportes/estadisticas', [\App\Http\Controllers\Admin\ConstructorReportesController::class, 'estadisticas'])->name('admin.constructor-reportes.estadisticas');
});

// ============================================================================
// MÓDULO DE ASISTENCIA
// ============================================================================

Route::middleware('auth')->prefix('asistencia')->name('admin.asistencia.')->group(function () {
    // Dashboard principal
    Route::get('/', [AsistenciaController::class, 'index'])->name('index');
    Route::get('/dashboard', [AsistenciaController::class, 'dashboardAsistencia'])->name('dashboard');

    // Registro de asistencia (todos los usuarios)
    Route::get('/registro', [AsistenciaController::class, 'registroAsistencia'])->name('registro');
    Route::post('/marcar-entrada', [AsistenciaController::class, 'marcarEntrada'])->name('marcar-entrada');
    Route::post('/marcar-salida', [AsistenciaController::class, 'marcarSalida'])->name('marcar-salida');
    Route::post('/marcar-inicio-refrigerio', [AsistenciaController::class, 'marcarInicioRefrigerio'])->name('marcar-inicio-refrigerio');
    Route::post('/marcar-fin-refrigerio', [AsistenciaController::class, 'marcarFinRefrigerio'])->name('marcar-fin-refrigerio');

    // API para sistema flotante de asistencia
    Route::post('/marcar', [AsistenciaController::class, 'marcarAsistenciaFlotante'])->name('marcar');
    Route::get('/estado-actual', [AsistenciaController::class, 'obtenerEstadoActual'])->name('estado-actual');
    Route::get('/dia-no-laboral', [AsistenciaController::class, 'mostrarDiaNoLaboral'])->name('dia-no-laboral');

    // Reportes de asistencia
    Route::get('/reportes', [AsistenciaController::class, 'reporteAsistencia'])->name('reportes');

    // Configuración (Solo Admin y Supervisor)
    Route::middleware('role:Admin|Supervisor')->group(function () {
        // Áreas laborales
        Route::get('/areas-laborales', [AsistenciaController::class, 'areasLaborales'])->name('areas-laborales');
        Route::get('/areas-laborales/create', [AsistenciaController::class, 'crearAreaLaboral'])->name('areas-laborales.create');
        Route::post('/areas-laborales', [AsistenciaController::class, 'storeAreaLaboral'])->name('areas-laborales.store');
        Route::get('/areas-laborales/{area}/edit', [AsistenciaController::class, 'editarAreaLaboral'])->name('areas-laborales.edit');
        Route::put('/areas-laborales/{area}', [AsistenciaController::class, 'updateAreaLaboral'])->name('areas-laborales.update');
        Route::patch('/areas-laborales/{area}/toggle', [AsistenciaController::class, 'toggleAreaLaboral'])->name('areas-laborales.toggle');

        // Horarios de trabajo
        Route::get('/horarios-trabajo', [AsistenciaController::class, 'horariosTrabajo'])->name('horarios-trabajo');
        Route::get('/horarios-trabajo/create', [AsistenciaController::class, 'crearHorarioTrabajo'])->name('horarios-trabajo.create');
        Route::post('/horarios-trabajo', [AsistenciaController::class, 'storeHorarioTrabajo'])->name('horarios-trabajo.store');
        Route::get('/horarios-trabajo/{horario}/edit', [AsistenciaController::class, 'editarHorarioTrabajo'])->name('horarios-trabajo.edit');
        Route::put('/horarios-trabajo/{horario}', [AsistenciaController::class, 'updateHorarioTrabajo'])->name('horarios-trabajo.update');
        Route::patch('/horarios-trabajo/{horario}/toggle', [AsistenciaController::class, 'toggleHorarioTrabajo'])->name('horarios-trabajo.toggle');

        // Feriados y Horarios Especiales
        Route::get('/feriados-especiales', [AsistenciaController::class, 'feriadosEspeciales'])->name('feriados-especiales.index');
        Route::post('/feriados-especiales', [AsistenciaController::class, 'storeFeriadoEspecial'])->name('feriados-especiales.store');
        Route::delete('/feriados-especiales/{feriado}', [AsistenciaController::class, 'destroyFeriadoEspecial'])->name('feriados-especiales.destroy');
        Route::post('/feriados-especiales/importar-feriados', [AsistenciaController::class, 'importarFeriadosNacionales'])->name('feriados-especiales.importar');

        // Asignaciones empleados-áreas
        Route::get('/asignaciones', [AsistenciaController::class, 'asignaciones'])->name('asignaciones');
        Route::get('/asignaciones/create', [AsistenciaController::class, 'crearAsignacion'])->name('asignaciones.create');
        Route::post('/asignaciones', [AsistenciaController::class, 'storeAsignacion'])->name('asignaciones.store');
        Route::get('/asignaciones/{asignacion}/edit', [AsistenciaController::class, 'editarAsignacion'])->name('asignaciones.edit');
        Route::put('/asignaciones/{asignacion}', [AsistenciaController::class, 'updateAsignacion'])->name('asignaciones.update');
        Route::patch('/asignaciones/{asignacion}/toggle', [AsistenciaController::class, 'toggleAsignacion'])->name('asignaciones.toggle');

        // Códigos de acceso (Solo Admin)
        Route::middleware('role:Admin')->group(function () {
            Route::get('/codigos', [AccessCodeController::class, 'index'])->name('codigos');
            Route::post('/codigos', [AccessCodeController::class, 'store'])->name('codigos.store');
            Route::get('/codigos/{code}', [AccessCodeController::class, 'show'])->name('codigos.show');
            Route::put('/codigos/{code}', [AccessCodeController::class, 'update'])->name('codigos.update');
            Route::delete('/codigos/{code}', [AccessCodeController::class, 'destroy'])->name('codigos.destroy');
            Route::patch('/codigos/{code}/toggle', [AccessCodeController::class, 'toggle'])->name('codigos.toggle');

            // Solicitudes de acceso
            Route::get('/solicitudes', [LoginRequestController::class, 'solicitudes'])->name('solicitudes');
            Route::get('/accesos', [LoginRequestController::class, 'accesos'])->name('accesos');
            Route::get('/solicitudes/stats', [LoginRequestController::class, 'getStats'])->name('solicitudes.stats');
            Route::post('/solicitudes/{loginRequest}/approve', [LoginRequestController::class, 'approve'])->name('solicitudes.approve');
            Route::post('/solicitudes/{loginRequest}/deny', [LoginRequestController::class, 'deny'])->name('solicitudes.deny');
        });

    });
});

// ============================================================================
// CONFIGURACIÓN DE API
// ============================================================================

Route::middleware(['auth', 'role:Admin'])->prefix('api-config')->name('admin.api-config.')->group(function () {
    Route::get('/', [ApiConfigController::class, 'index'])->name('index');
    Route::get('/create', [ApiConfigController::class, 'create'])->name('create');
    Route::post('/', [ApiConfigController::class, 'store'])->name('store');
    Route::get('/{config}/edit', [ApiConfigController::class, 'edit'])->name('edit');
    Route::put('/{config}', [ApiConfigController::class, 'update'])->name('update');
    Route::delete('/{config}', [ApiConfigController::class, 'destroy'])->name('destroy');
    Route::post('/initialize-defaults', [ApiConfigController::class, 'initializeDefaults'])->name('initialize');
    Route::post('/test-dni-api', [ApiConfigController::class, 'testDniApi'])->name('test-dni');
});

// Grupo con autenticación para recursos administrativos
Route::middleware('auth')->group(function () {
    // Billeteras Digitales
    Route::resource('billeteras-digitales', BilleterasDigitalesController::class)->names('admin.billeteras-digitales');

    // Fondo Provisional
    Route::resource('fondo-provisional', \App\Http\Controllers\Admin\FondoProvisionalController::class)->names('admin.fondo-provisional');
    Route::post('fondo-provisional/{id}/marcar-rendido', [\App\Http\Controllers\Admin\FondoProvisionalController::class, 'marcarRendido'])->name('admin.fondo-provisional.marcar-rendido');

    // Etiquetas
    Route::resource('etiquetas', \App\Http\Controllers\Admin\EtiquetasController::class)->names('admin.etiquetas');
    Route::post('etiquetas/asignar', [\App\Http\Controllers\Admin\EtiquetasController::class, 'asignar'])->name('admin.etiquetas.asignar');
    Route::post('etiquetas/remover', [\App\Http\Controllers\Admin\EtiquetasController::class, 'remover'])->name('admin.etiquetas.remover');

    // Tareas y Tablero Kanban
    Route::get('tareas', [TareasController::class, 'index'])->name('admin.tareas.index');
    Route::post('tareas/actualizar-orden', [TareasController::class, 'actualizarOrden'])->name('admin.tareas.actualizar-orden');
    Route::post('tareas/subir-archivo', [TareasController::class, 'subirArchivo'])->name('admin.tareas.subir-archivo');
    Route::delete('tareas/archivo/{id}', [TareasController::class, 'eliminarArchivo'])->name('admin.tareas.eliminar-archivo');
    Route::post('tareas/columna', [TareasController::class, 'crearColumna'])->name('admin.tareas.crear-columna');
    Route::put('tareas/columna/{id}', [TareasController::class, 'actualizarColumna'])->name('admin.tareas.actualizar-columna');
    Route::delete('tareas/columna/{id}', [TareasController::class, 'eliminarColumna'])->name('admin.tareas.eliminar-columna');

    // API Documentation y Testing
    Route::get('api-docs', [ApiDocsController::class, 'index'])->name('admin.api-docs.index');
    Route::get('api-docs/testing', [ApiDocsController::class, 'testing'])->name('admin.api-docs.testing');

    // Auditoría
    Route::prefix('auditoria')->group(function () {
        Route::get('/', [AuditoriaController::class, 'index'])->name('admin.auditoria.index');
        Route::get('/resumen', [AuditoriaController::class, 'resumen'])->name('admin.auditoria.resumen');
        Route::get('/usuario/{userId}', [AuditoriaController::class, 'usuario'])->name('admin.auditoria.usuario');
        Route::get('/exportar', [AuditoriaController::class, 'exportar'])->name('admin.auditoria.exportar');

        // Nuevas rutas para sesiones y tiempo
        Route::get('/sesiones', [AuditoriaController::class, 'sesiones'])->name('admin.auditoria.sesiones');
        Route::get('/sesiones-usuario/{userId}', [AuditoriaController::class, 'sesionesUsuario'])->name('admin.auditoria.sesiones-usuario');
        Route::get('/tiempo-modulos/{userId}', [AuditoriaController::class, 'tiempoModulos'])->name('admin.auditoria.tiempo-modulos');
        Route::get('/reporte-sesiones', [AuditoriaController::class, 'reporteSesiones'])->name('admin.auditoria.reporte-sesiones');
        Route::post('/sesiones/cerrar-abandonadas', [AuditoriaController::class, 'cerrarSesionesAbandonadas'])->name('admin.auditoria.cerrar-sesiones-abandonadas');
    });

    // 🤖 EVENTOS AUTOMÁTICOS - Sistema de monitoreo en tiempo real
    Route::prefix('eventos-automaticos')->name('admin.eventos-automaticos.')->group(function () {
        Route::get('/', [EventosAutomaticosController::class, 'index'])->name('index');
        Route::get('/show/{id}', [EventosAutomaticosController::class, 'show'])->name('show');
        Route::get('/api', [EventosAutomaticosController::class, 'api'])->name('api');
        Route::get('/estadisticas', [EventosAutomaticosController::class, 'estadisticas'])->name('estadisticas');
        Route::post('/limpiar', [EventosAutomaticosController::class, 'limpiar'])->name('limpiar');
    });

    // Metas y Comisiones
    Route::prefix('metas')->group(function () {
        Route::get('/', [MetasController::class, 'index'])->name('admin.metas.index');
        Route::get('/create', [MetasController::class, 'create'])->name('admin.metas.create');
        Route::post('/store', [MetasController::class, 'store'])->name('admin.metas.store');
        Route::get('/comisiones', [MetasController::class, 'comisiones'])->name('admin.metas.comisiones');
        Route::post('/comisiones', [MetasController::class, 'guardarComisiones'])->name('admin.metas.comisiones.store');
        Route::post('/configuracion', [MetasController::class, 'guardarConfiguracion'])->name('admin.metas.configuracion.store');
        Route::post('/recalcular', [MetasController::class, 'recalcular'])->name('admin.metas.recalcular');
        Route::get('/cartera/{asesor}/exportar', [MetasController::class, 'exportarCartera'])->name('admin.metas.cartera.exportar');
        Route::get('/{meta}', [MetasController::class, 'show'])->name('admin.metas.show');
        Route::get('/{meta}/edit', [MetasController::class, 'edit'])->name('admin.metas.edit');
        Route::put('/{meta}', [MetasController::class, 'update'])->name('admin.metas.update');
    });
});

Route::middleware(['auth', 'role:Admin|Supervisor'])->group(function () {
    Route::prefix('zonificador-tramos')->name('admin.zonificador-tramos.')->group(function () {
        Route::get('/', [ZonificadorTramosController::class, 'index'])->name('index');
        Route::post('/data', [ZonificadorTramosController::class, 'getData'])->name('data');
        Route::get('/create', [ZonificadorTramosController::class, 'create'])->name('create');
        Route::post('/', [ZonificadorTramosController::class, 'store'])->name('store');
        Route::get('/{tramo}/edit', [ZonificadorTramosController::class, 'edit'])->name('edit');
        Route::put('/{tramo}', [ZonificadorTramosController::class, 'update'])->name('update');
        Route::delete('/{tramo}', [ZonificadorTramosController::class, 'destroy'])->name('destroy');
    });
});

// Reporte de Ventas
Route::view('reportes-sale', 'admin.reportes-sale.index')->name('admin.reportes-sale.index');

// Reporte de Clientes por Usuario
Route::middleware(['auth', 'role:Admin|Supervisor|JCC'])->group(function () {
    Route::get('reportes-clientes', [App\Http\Controllers\Admin\ReporteClientesController::class, 'index'])
        ->name('admin.reportes-clientes.index');
    Route::get('reportes-clientes/exportar', [App\Http\Controllers\Admin\ReporteClientesController::class, 'exportar'])
        ->name('admin.reportes-clientes.exportar');
    Route::get('zonas/{zona}/sucursales', [App\Http\Controllers\Admin\ReporteClientesController::class, 'getSucursalesByZona'])
        ->name('admin.zonas.sucursales');
});

// Registros Huérfanos
Route::middleware(['auth', 'role:Admin'])->group(function () {
    Route::get('huerfanas', [HuerfanasController::class, 'index'])->name('admin.huerfanas.index');
    Route::post('huerfanas/eliminar', [HuerfanasController::class, 'eliminar'])->name('admin.huerfanas.eliminar');
});