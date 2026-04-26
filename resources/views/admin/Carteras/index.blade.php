@extends('layouts.admin')
@section('title', 'Gestión de Carteras')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-briefcase mr-2"></i>Gestión de Carteras</h1>
       <ol class="breadcrumb float-sm-right">
           <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
           <li class="breadcrumb-item active">Carteras</li>
       </ol>
   </div>
@stop

@section('content')
<div class="container-fluid pt-2">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Filtros -->
    <div class="card card-outline card-primary shadow-sm mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filtros de Búsqueda</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body bg-light">
            <form id="form-filtros" class="mb-0">
                <div class="row">
                    <div class="col-md-2 col-lg-2">
                        <div class="form-group">
                            <label class="small font-weight-bold">
                                <i class="fas fa-map-marker-alt mr-1 text-gray-600"></i> Zona
                            </label>
                            <select name="zona_id" id="zona_id" class="form-control select2 form-control-sm">
                                <option value="">Todas las Zonas</option>
                                @foreach($zonas as $zona)
                                    <option value="{{ $zona->id }}" {{ request('zona_id') == $zona->id ? 'selected' : '' }}>
                                        {{ $zona->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 col-lg-2">
                        <div class="form-group">
                            <label class="small font-weight-bold">
                                <i class="fas fa-building mr-1 text-gray-600"></i> Sucursal
                            </label>
                            <select name="sucursal_id" id="sucursal_id" class="form-control select2 form-control-sm">
                                <option value="">Todas las Sucursales</option>
                                @foreach($sucursales as $sucursal)
                                    <option value="{{ $sucursal->id }}" {{ request('sucursal_id') == $sucursal->id ? 'selected' : '' }}>
                                        {{ $sucursal->sucursal }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 col-lg-1">
                        <div class="form-group">
                            <label class="small font-weight-bold">
                                <i class="fas fa-user-tie mr-1 text-gray-600"></i> JCC
                            </label>
                            <select name="jcc_id" id="jcc_id" class="form-control select2 form-control-sm">
                                <option value="">Todos los JCC</option>
                                @foreach($jccs as $jcc)
                                    <option value="{{ $jcc->id }}" {{ request('jcc_id') == $jcc->id ? 'selected' : '' }}>
                                        {{ $jcc->codigo ?: $jcc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 col-lg-1">
                        <div class="form-group">
                            <label class="small font-weight-bold">
                                <i class="fas fa-user-tag mr-1 text-gray-600"></i> Asesor
                            </label>
                            <select name="asesor_id" id="asesor_id" class="form-control select2 form-control-sm">
                                <option value="">Todos los Asesores</option>
                                @foreach($asesores as $asesor)
                                    <option value="{{ $asesor->id }}" {{ request('asesor_id') == $asesor->id ? 'selected' : '' }}>
                                        {{ $asesor->codigo ?: $asesor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 col-lg-1">
                        <div class="form-group">
                            <label class="small font-weight-bold">
                                <i class="fas fa-user-check mr-1 text-gray-600"></i> Analista
                            </label>
                            <select name="analista_id" id="analista_id" class="form-control select2 form-control-sm">
                                <option value="">Todos los Analistas</option>
                                @foreach($analistas as $analista)
                                    <option value="{{ $analista->id }}" {{ request('analista_id') == $analista->id ? 'selected' : '' }}>
                                        {{ $analista->codigo ?: $analista->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 col-lg-2">
                        <div class="form-group">
                            <label class="small font-weight-bold">
                                <i class="fas fa-chart-pie mr-1 text-gray-600"></i> Estado
                            </label>
                            <select name="estado" id="estado" class="form-control select2 form-control-sm">
                                <option value="">Todos los Estados</option>
                                <option value="Nueva Solicitud" {{ request('estado') == 'Nueva Solicitud' ? 'selected' : '' }}>Nueva Solicitud</option>
                                <option value="Por Desembolsar" {{ request('estado') == 'Por Desembolsar' ? 'selected' : '' }}>Por Desembolsar</option>
                                <option value="Vigente" {{ request('estado') == 'Vigente' ? 'selected' : '' }}>Vigente</option>
                                <option value="Moroso" {{ request('estado') == 'Moroso' ? 'selected' : '' }}>Moroso</option>
                                <option value="Pagado" {{ request('estado') == 'Pagado' ? 'selected' : '' }}>Pagado</option>
                                <option value="Cancelado" {{ request('estado') == 'Cancelado' ? 'selected' : '' }}>Cancelado</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <div class="form-group">
                            <label class="small font-weight-bold">
                                <i class="fas fa-calendar-times mr-1 text-gray-600"></i> Cuotas Vencidas
                            </label>
                            <select name="nrocuota" id="nrocuota" class="form-control select2 form-control-sm">
                                <option value="">Seleccionar Cantidad</option>
                                @for($i = 1; $i <= 6; $i++)
                                    <option value="{{ $i }}" {{ request('nrocuota') == $i ? 'selected' : '' }}>
                                        {{ $i }} {{ $i == 1 ? 'cuota' : 'cuotas' }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 text-right">
                        <button type="button" id="btn-pdf" class="btn btn-danger btn-sm mr-2">
                            <i class="far fa-file-pdf mr-1"></i> Exportar a PDF
                        </button>
                        <button type="button" id="btn-limpiar" class="btn btn-default btn-sm">
                            <i class="fas fa-eraser mr-1"></i> Limpiar Filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="small-box bg-primary shadow-sm">
                <div class="inner">
                    <h3 id="total-carteras">{{ count($carteras) }}</h3>
                    <p>Carteras Encontradas</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-success shadow-sm">
                <div class="inner">
                    <h3 id="total-vigentes">{{ $carteras->where('estado_prestamo', 'Vigente')->count() }}</h3>
                    <p>Préstamos Vigentes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-warning shadow-sm">
                <div class="inner">
                    <h3 id="total-morosos">{{ $carteras->where('estado_prestamo', 'Moroso')->count() }}</h3>
                    <p>Préstamos Morosos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-info shadow-sm">
                <div class="inner">
                    <h3 id="total-solicitudes">{{ $carteras->where('estado_prestamo', 'Nueva Solicitud')->count() }}</h3>
                    <p>Nuevas Solicitudes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Carteras -->
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-briefcase mr-2"></i>Listado de Carteras
            </h3>
            <div class="card-tools">
                <div class="input-group input-group-sm" style="width: 200px;">
                    <input type="text" id="search-table" class="form-control" placeholder="Buscar cliente...">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" id="tabla-carteras">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-center pl-4"><i class="fas fa-map-marker-alt mr-1"></i>Zona</th>
                            <th class="text-center"><i class="fas fa-building mr-1"></i>Sucursal</th>
                            <th><i class="fas fa-user mr-1"></i>Cliente</th>
                            <th><i class="fas fa-user-tag mr-1"></i>Asesor</th>
                            <th><i class="fas fa-user-tie mr-1"></i>JCC</th>
                            <th><i class="fas fa-user-check mr-1"></i>Analista</th>
                            <th class="text-center"><i class="fas fa-chart-pie mr-1"></i>Estado</th>
                            <th class="text-center"><i class="fas fa-money-check-alt mr-1"></i>Última Cuota</th>
                            <th class="text-center"><i class="fas fa-calendar-times mr-1"></i>C. Vencidas</th>
                            <th class="text-center" width="80"><i class="fas fa-tools mr-1"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-body">
                        @forelse($carteras as $cartera)
                            <tr>
                                <td class="text-center pl-4">{{ $cartera['zona'] }}</td>
                                <td class="text-center">{{ $cartera['sucursal'] }}</td>
                                <td class="font-weight-medium">{{ $cartera['nombre_cliente'] }}</td>
                                <td>{{ $cartera['codigo_asesor'] ?: $cartera['nombre_asesor'] }}</td>
                                <td>{{ $cartera['codigo_jcc'] ?: $cartera['nombre_jcc'] }}</td>
                                <td>{{ $cartera['codigo_analista'] ?: $cartera['nombre_analista'] }}</td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $helpers['estadoBadge']($cartera['estado_prestamo']) }}">
                                        {{ $cartera['estado_prestamo'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $helpers['cuotaBadge']($cartera['estado_ultima_cuota']) }}">
                                        {{ $cartera['estado_ultima_cuota'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $cartera['cuotas_vencidas'] > 0 ? 'danger' : 'success' }}">
                                        {{ $cartera['cuotas_vencidas'] }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.carteras.estado-cuenta', $cartera['id']) }}" 
                                        class="btn btn-sm btn-outline-primary" 
                                        data-toggle="tooltip" 
                                        title="Estado de Cuenta">
                                            <i class="fas fa-file-invoice-dollar"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle mr-1"></i>No hay carteras registradas actualmente
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted">Mostrando <span id="registros-mostrados">{{ count($carteras) }}</span> registros</span>
                </div>
                <div>
                    <button class="btn btn-outline-primary btn-sm" id="btn-refresh">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .form-group label {
        color: #555;
    }
    .form-text.text-muted {
        font-size: 0.8rem;
    }
    .input-group-text {
        border-color: #ced4da;
    }
    .table th {
        font-weight: 600;
        color: #495057;
    }
    .badge {
        font-size: 90%;
        font-weight: 500;
        padding: 0.35em 0.6em;
    }
    .btn-group .btn {
        margin: 0 2px;
    }
    .table td {
        vertical-align: middle;
    }
    .small-box {
        border-radius: 0.25rem;
        margin-bottom: 1.25rem;
    }
    .small-box .icon {
        font-size: 70px;
        position: absolute;
        right: 15px;
        top: 15px;
        opacity: 0.3;
        transition: all 0.3s linear;
    }
    .small-box:hover .icon {
        font-size: 80px;
    }
    .small-box .inner {
        padding: 20px;
    }
    .small-box h3 {
        font-size: 2.2rem;
        font-weight: 700;
        margin: 0 0 10px 0;
        white-space: nowrap;
        padding: 0;
    }
    .small-box p {
        font-size: 1rem;
    }
    .select2-container--default .select2-selection--single {
        height: calc(1.8125rem + 2px);
        border-radius: 0.25rem;
        border: 1px solid #ced4da;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: calc(1.8125rem + 2px);
        padding-left: 0.375rem;
        font-size: 0.875rem;
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
    // Inicializar Select2
    $('.select2').select2({
        placeholder: "Seleccione...",
        allowClear: true,
        width: '100%'
    });
    
    // Activar tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Filtrado automático al cambiar cualquier selector
    $('select.select2').on('change', function() {
        actualizarTabla();
    });
    
    // Búsqueda en tabla
    $('#search-table').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $("#tabla-carteras tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
        actualizarContadores();
    });
    
    // Botón limpiar filtros
    $('#btn-limpiar').on('click', function() {
        $('.select2').val('').trigger('change');
    });
    
    // Botón refrescar
    $('#btn-refresh').on('click', function() {
        actualizarTabla();
    });
    
    // Botón exportar PDF
    $('#btn-pdf').on('click', function() {
        generarPDF();
    });
    
    function actualizarContadores() {
        // Contar filas visibles
        let totalVisibles = $('#tabla-body tr:visible').length;
        $('#registros-mostrados').text(totalVisibles);
        
        // Actualizar contadores por estado
        let vigentes = 0;
        let morosos = 0;
        let solicitudes = 0;
        
        $('#tabla-body tr:visible').each(function() {
            let estado = $(this).find('td:eq(6) span').text().trim();
            if (estado === 'Vigente') vigentes++;
            if (estado === 'Moroso') morosos++;
            if (estado === 'Nueva Solicitud') solicitudes++;
        });
        
        $('#total-vigentes').text(vigentes);
        $('#total-morosos').text(morosos);
        $('#total-solicitudes').text(solicitudes);
    }
    
    function getBadgeClassForEstado(estado) {
        switch (estado) {
            case 'Vigente': return 'success';
            case 'Moroso': return 'danger';
            case 'Nueva Solicitud': return 'info';
            case 'Por Desembolsar': return 'primary';
            case 'Pagado': return 'secondary';
            case 'Cancelado': return 'dark';
            default: return 'light';
        }
    }
    
    function getBadgeClassForCuota(estado) {
        switch (estado) {
            case 'Pagado': return 'success';
            case 'Parcial': return 'warning';
            case 'Pendiente': return 'danger';
            default: return 'secondary';
        }
    }
    
    function generarPDF() {
        // Recolectar todos los valores de los filtros
        let filtros = $('#form-filtros').serialize();
        
        // Abrir la URL en una nueva pestaña/ventana
        window.open("{{ route('admin.carteras.pdf') }}?" + filtros, '_blank');
    }
    
    function actualizarTabla() {
        $.ajax({
            url: "{{ route('admin.carteras.index') }}",
            type: "GET",
            data: $('#form-filtros').serialize(),
            dataType: "json",
            beforeSend: function() {
                // Mostrar indicador de carga
                $('#tabla-body').html('<tr><td colspan="10" class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Cargando datos...</p></td></tr>');
            },
            success: function(response) {
                // Limpiar tabla
                $('#tabla-body').empty();
                
                // Actualizar contador
                $('#total-carteras').text(response.count);
                $('#registros-mostrados').text(response.count);
                
                // Si no hay resultados
                if (response.carteras.length === 0) {
                    $('#tabla-body').html('<tr><td colspan="10" class="text-center py-4 text-muted"><i class="fas fa-info-circle mr-1"></i>No se encontraron carteras con los filtros aplicados.</td></tr>');
                    
                    // Actualizar contadores en tarjetas
                    $('#total-vigentes').text('0');
                    $('#total-morosos').text('0');
                    $('#total-solicitudes').text('0');
                    
                    return;
                }
                
                // Variables para contadores
                let vigentes = 0;
                let morosos = 0;
                let solicitudes = 0;
                
                // Generar filas con los resultados
                $.each(response.carteras, function(index, cartera) {
                    // Actualizar contadores
                    if (cartera.estado_prestamo === 'Vigente') vigentes++;
                    if (cartera.estado_prestamo === 'Moroso') morosos++;
                    if (cartera.estado_prestamo === 'Nueva Solicitud') solicitudes++;
                    
                    // Clases para los badges
                    let estadoClass = getBadgeClassForEstado(cartera.estado_prestamo);
                    let cuotaClass = getBadgeClassForCuota(cartera.estado_ultima_cuota);
                    let vencidasClass = cartera.cuotas_vencidas > 0 ? 'danger' : 'success';
                    
                    // Crear fila y añadirla a la tabla
                    let row = `
                        <tr>
                            <td class="text-center pl-4">${cartera.zona}</td>
                            <td class="text-center">${cartera.sucursal}</td>
                            <td class="font-weight-medium">${cartera.nombre_cliente}</td>
                            <td>${cartera.codigo_asesor || cartera.nombre_asesor}</td>
                            <td>${cartera.codigo_jcc || cartera.nombre_jcc}</td>
                            <td>${cartera.codigo_analista || cartera.nombre_analista}</td>
                            <td class="text-center">
                                <span class="badge badge-${estadoClass}">
                                    ${cartera.estado_prestamo}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-${cuotaClass}">
                                    ${cartera.estado_ultima_cuota}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-${vencidasClass}">
                                    ${cartera.cuotas_vencidas}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="/admin/carteras/estado-cuenta/${cartera.id}" 
                                    class="btn btn-sm btn-outline-primary" 
                                    data-toggle="tooltip" 
                                    title="Estado de Cuenta">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    `;
                    $('#tabla-body').append(row);
                });
                
                // Actualizar contadores en tarjetas
                $('#total-vigentes').text(vigentes);
                $('#total-morosos').text(morosos);
                $('#total-solicitudes').text(solicitudes);
                
                // Reinicializar tooltips
                $('[data-toggle="tooltip"]').tooltip();
            },
            error: function(xhr, status, error) {
                console.error("Error al cargar datos: " + error);
                $('#tabla-body').html('<tr><td colspan="10" class="text-center py-4 text-danger"><i class="fas fa-exclamation-circle fa-2x mb-3"></i><p>Error al cargar los datos. Intente nuevamente.</p></td></tr>');
            }
        });
    }
});
</script>
@stop