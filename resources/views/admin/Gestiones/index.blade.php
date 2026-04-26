@extends('layouts.admin')

@section('title', 'Gestiones de Cobranza')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="m-0 text-dark font-weight-bold">Gestiones de Cobranza</h1>
            <p class="text-muted"><i class="far fa-calendar-alt mr-1"></i> {{ now()->format('d/m/Y') }}</p>
        </div>
        <a href="{{ route('admin.prestamos.index') }}" class="btn btn-primary btn-lg shadow-sm">
            <i class="fas fa-plus-circle mr-2"></i> Nueva Gestión
        </a>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <!-- Panel de Filtros -->
            <div class="card card-outline card-primary mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h3 class="card-title">
                        <i class="fas fa-filter text-primary mr-2"></i> Filtros Avanzados
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form id="filtros-form" class="filtro-dinamico">
                        <div class="row">
                            <div class="col-md-2 mb-3">
                                <label for="cliente" class="form-label small text-uppercase font-weight-bold text-muted">Cliente</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white border-right-0"><i class="fas fa-user text-primary"></i></span>
                                    </div>
                                    <input type="text" name="cliente" id="cliente" class="form-control border-left-0 filtro-input" 
                                           value="{{ request('cliente') }}" placeholder="Nombre del cliente">
                                </div>
                            </div>
                            
                            <div class="col-md-2 mb-3">
                                <label for="estado_id" class="form-label small text-uppercase font-weight-bold text-muted">Estado de Gestión</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white border-right-0"><i class="fas fa-tag text-primary"></i></span>
                                    </div>
                                    <select name="estado_id" id="estado_id" class="form-control border-left-0 select2-simple filtro-input">
                                        <option value="">Todos los estados</option>
                                        @foreach($estados as $estado)
                                            <option value="{{ $estado->id }}" {{ request('estado_id') == $estado->id ? 'selected' : '' }}>
                                                {{ $estado->estado }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-2 mb-3">
                                <label for="asesor_id" class="form-label small text-uppercase font-weight-bold text-muted">Registrado por</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white border-right-0"><i class="fas fa-user-tie text-primary"></i></span>
                                    </div>
                                    <select name="asesor_id" id="asesor_id" class="form-control border-left-0 select2-simple filtro-input">
                                        <option value="">Todos los usuarios</option>
                                        @foreach($asesores as $asesor)
                                            <option value="{{ $asesor->id }}" {{ request('asesor_id') == $asesor->id ? 'selected' : '' }}>
                                                {{ $asesor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-2 mb-3">
                                <label for="tiene_compromiso" class="form-label small text-uppercase font-weight-bold text-muted">Compromiso</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white border-right-0"><i class="fas fa-handshake text-primary"></i></span>
                                    </div>
                                    <select name="tiene_compromiso" id="tiene_compromiso" class="form-control border-left-0 filtro-input">
                                        <option value="">Todos</option>
                                        <option value="1" {{ request('tiene_compromiso') === '1' ? 'selected' : '' }}>Con compromiso</option>
                                        <option value="0" {{ request('tiene_compromiso') === '0' ? 'selected' : '' }}>Sin compromiso</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label for="fecha_desde" class="form-label small text-uppercase font-weight-bold text-muted">Fecha desde</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white border-right-0"><i class="fas fa-calendar-day text-primary"></i></span>
                                    </div>
                                    <input type="date" name="fecha_desde" id="fecha_desde" class="form-control border-left-0 filtro-input" 
                                           value="{{ request('fecha_desde') }}">
                                </div>
                            </div>
                            
                            <div class="col-md-2 mb-2">
                                <label for="fecha_hasta" class="form-label small text-uppercase font-weight-bold text-muted">Fecha hasta</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white border-right-0"><i class="fas fa-calendar-day text-primary"></i></span>
                                    </div>
                                    <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control border-left-0 filtro-input" 
                                           value="{{ request('fecha_hasta') }}">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                <div class="d-flex w-100">
                                    <button type="button" id="limpiar-filtros" class="btn btn-outline-secondary mr-2">
                                        <i class="fas fa-eraser mr-1"></i> Limpiar filtros
                                    </button>
                                    <div class="input-group ml-auto" style="max-width: 210px;">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white border-right-0"><i class="fas fa-list-ol text-primary"></i></span>
                                        </div>
                                        <select id="per-page" class="form-control border-left-0 filtro-input">
                                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 por página</option>
                                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 por página</option>
                                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 por página</option>
                                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 por página</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                    <span class="text-muted small" id="contador-resultados">
                        <i class="fas fa-chart-bar mr-1"></i> Mostrando {{ $gestiones->firstItem() ?? 0 }} - {{ $gestiones->lastItem() ?? 0 }} de {{ $gestiones->total() ?? 0 }} gestiones
                    </span>
                    <!--button type="button" id="btn-exportar-excel" class="btn btn-success shadow-sm">
                        <i class="fas fa-file-excel mr-1"></i> Exportar a Excel
                    </button-->
                </div>
            </div>

            <!-- Tabla de Resultados -->
            <div class="card shadow">
                <div class="card-body p-0">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="table-responsive" id="resultados-container">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th class="pl-3">#</th>
                                    <th>Cliente</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Observaciones</th>
                                    <th class="text-center">Compromiso</th>
                                    <th>Registrado por</th>
                                    <th class="text-center">Ubicación</th>
                                    <th class="text-right pr-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($gestiones as $gestion)
                                    <tr>
                                        <td class="pl-3 font-weight-bold">{{ $gestion->id }}</td>
                                        <td>
                                            @if($gestion->prestamo && $gestion->prestamo->cliente && $gestion->prestamo->cliente->persona)
                                                <span class="d-block">{{ $gestion->prestamo->cliente->persona->nombres }} 
                                                {{ $gestion->prestamo->cliente->persona->ape_pat }}</span>
                                                <small class="text-muted">Préstamo #{{ $gestion->prestamo->id }}</small>
                                            @else
                                                <span class="text-muted">No disponible</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($gestion->estadoGestion)
                                                <span class="badge badge-pill badge-primary px-3 py-2">{{ $gestion->estadoGestion->estado }}</span>
                                            @else
                                                <span class="badge badge-pill badge-secondary px-3 py-2">Sin estado</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="d-block">{{ $gestion->fecha ? \Carbon\Carbon::parse($gestion->fecha)->format('d/m/Y') : 'No registrada' }}</span>
                                            <small class="text-muted">{{ $gestion->updated_at ? \Carbon\Carbon::parse($gestion->updated_at)->format('H:i') : 'No registrada' }}</small>
                                        </td>
                                        <td>
                                            <div class="d-inline-block text-truncate" style="max-width: 150px;" data-toggle="tooltip" data-placement="top" title="{{ $gestion->observaciones }}">
                                                {{ $gestion->observaciones }}
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($gestion->compromiso)
                                                @php
                                                    $badgeClass = 'badge-secondary';
                                                    $estadoText = 'Desconocido';
                                                    
                                                    if($gestion->compromiso->estado == \App\Models\Compromiso::ESTADO_PENDIENTE) {
                                                        $badgeClass = 'badge-warning';
                                                        $estadoText = 'Pendiente';
                                                    } elseif($gestion->compromiso->estado == \App\Models\Compromiso::ESTADO_PAGADO) {
                                                        $badgeClass = 'badge-success';
                                                        $estadoText = 'Pagado';
                                                    } elseif($gestion->compromiso->estado == \App\Models\Compromiso::ESTADO_POSTERGADO) {
                                                        $badgeClass = 'badge-danger';
                                                        $estadoText = 'Postergado';
                                                    }
                                                @endphp
                                                <span class="badge badge-pill {{ $badgeClass }} px-3 py-2">{{ $estadoText }}</span>
                                                <div class="mt-1">
                                                    <span class="font-weight-bold">S/. {{ number_format($gestion->compromiso->monto, 2) }}</span>
                                                </div>
                                            @else
                                                <span class="badge badge-pill badge-light px-3 py-2">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($gestion->asesor)
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle bg-primary text-white mr-2">
                                                        {{ substr($gestion->asesor->name, 0, 1) }}
                                                    </div>
                                                    <span>{{ $gestion->asesor->name }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted">No registrado</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($gestion->latitud && $gestion->longitud)
                                                <button type="button" class="btn btn-sm btn-info rounded-circle btn-icon ver-mapa shadow-sm" 
                                                        data-toggle="modal" 
                                                        data-target="#mapaModal" 
                                                        data-lat="{{ $gestion->latitud }}" 
                                                        data-lng="{{ $gestion->longitud }}"
                                                        data-id="{{ $gestion->id }}"
                                                        data-cliente="{{ $gestion->prestamo && $gestion->prestamo->cliente && $gestion->prestamo->cliente->persona ? $gestion->prestamo->cliente->persona->nombres . ' ' . $gestion->prestamo->cliente->persona->ape_pat : 'Cliente' }}"
                                                        data-asesor="{{ $gestion->asesor ? $gestion->asesor->name : 'No registrado' }}"
                                                        data-fecha="{{ $gestion->fecha ? $gestion->fecha->format('d/m/Y H:i') : 'No registrada' }}">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                </button>
                                            @else
                                                <span class="badge badge-pill badge-light px-3 py-2">No</span>
                                            @endif
                                        </td>
                                        <td class="text-right pr-3">
                                            <div class="btn-group" role="group">
                                                <!-- Ver detalles -->
                                                <a href="{{ route('admin.gestiones.show', $gestion->id) }}" class="btn btn-sm btn-primary shadow-sm" data-toggle="tooltip" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <!-- Crear nueva gestión para el mismo préstamo -->
                                                <a href="{{ route('admin.gestiones.create', ['prestamo_id' => $gestion->prestamo_id]) }}" 
                                                   class="btn btn-sm btn-success shadow-sm" data-toggle="tooltip" title="Nueva gestión del préstamo">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                                
                                                <!-- Si tiene compromiso, crear gestión de seguimiento -->
                                                @if($gestion->compromiso)
                                                    <a href="{{ route('admin.gestiones.create', ['compromiso_id' => $gestion->compromiso->id]) }}" 
                                                       class="btn btn-sm btn-warning shadow-sm" data-toggle="tooltip" title="Seguimiento del compromiso">
                                                        <i class="fas fa-search-plus"></i>
                                                    </a>
                                                @endif
                                                
                                                <!-- Eliminar -->
                                                <form action="{{ route('admin.gestiones.destroy', $gestion->id) }}" method="POST" class="d-inline" 
                                                    onsubmit="return confirm('¿Está seguro de eliminar esta gestión?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger shadow-sm" data-toggle="tooltip" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="empty-state">
                                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                                <h5>No hay gestiones que coincidan con los filtros</h5>
                                                <p class="text-muted">Intente con otros criterios de búsqueda</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination-container p-3 border-top">
                        {{ $gestiones->appends(request()->except('page'))->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para mostrar el mapa -->
    <div class="modal fade" id="mapaModal" tabindex="-1" role="dialog" aria-labelledby="mapaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="mapaModalLabel">
                        <i class="fas fa-map-marked-alt mr-2"></i> Ubicación de Gestión
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="card card-body bg-light mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><i class="fas fa-hashtag text-primary mr-2"></i> <strong>Gestión:</strong> <span id="gestion-id"></span></p>
                                <p class="mb-1"><i class="fas fa-user text-primary mr-2"></i> <strong>Cliente:</strong> <span id="gestion-cliente"></span></p>
                                <p class="mb-1"><i class="fas fa-user-tie text-primary mr-2"></i> <strong>Registrado por:</strong> <span id="gestion-asesor"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><i class="fas fa-map-pin text-primary mr-2"></i> <strong>Coordenadas:</strong> <span id="gestion-coordenadas"></span></p>
                                <p class="mb-1"><i class="fas fa-calendar-alt text-primary mr-2"></i> <strong>Fecha:</strong> <span id="gestion-fecha"></span></p>
                            </div>
                        </div>
                    </div>
                    <div id="mapa" style="width: 100%; height: 400px;" class="rounded shadow"></div>
                </div>
                <div class="modal-footer">
                    <a id="ver-osm" href="#" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt mr-1"></i> Ver en OpenStreetMap
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <!-- Incluir CSS de Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <style>
        /* Variables de colores corporativos */
        :root {
            --primary-color: #0056b3;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        /* Estilos generales y modernos */
        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        /* Header mejorado */
        .content-header {
            padding-bottom: 0;
        }
        
        /* Mejorar aspecto de tarjetas */
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05) !important;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1) !important;
        }
        
        .card-outline {
            border-top: 3px solid var(--primary-color);
        }
        
        /* Animaciones */
        .btn {
            transition: all 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        /* Mejorar aspecto de inputs */
        .form-control {
            border-radius: 4px;
            padding: 0.6rem 0.75rem;
            height: auto;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 86, 179, 0.15);
        }
        
        .input-group-text {
            border-radius: 4px;
        }
        
        /* Mejorar aspecto de tablas */
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            border-top: none;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            color: var(--secondary-color);
        }
        
        .table tbody tr {
            transition: all 0.2s;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0, 86, 179, 0.03);
        }
        
        /* Badges modernos */
        .badge {
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        .badge-pill {
            border-radius: 50rem;
        }
        
        /* Botones más modernos */
        .btn-sm {
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
        }
        
        .btn-icon {
            width: 36px;
            height: 36px;
            padding: 0;
            line-height: 36px;
            text-align: center;
        }
        
        /* Estilos para Leaflet */
        #mapa {
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .leaflet-popup-content {
            margin: 12px;
            text-align: center;
        }
        
        .leaflet-popup-content h6 {
            font-weight: 600;
            margin: 0 0 5px 0;
            font-size: 1rem;
        }
        
        .leaflet-popup-content p {
            margin: 3px 0;
        }
        
        /* Avatar circular */
        .avatar-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* Estado vacío personalizado */
        .empty-state {
            padding: 2rem 0;
            text-align: center;
        }
        
        /* Paginación mejorada */
        .pagination-container {
            background-color: #f9fafb;
        }
        
        .page-link {
            border-radius: 4px;
            margin: 0 2px;
            color: var(--primary-color);
        }
        
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        /* Tooltips */
        .tooltip .tooltip-inner {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        /* Select2 estilizado */
        .select2-container--default .select2-selection--single {
            height: calc(1.5em + 1.2rem + 2px) !important;
            padding: 0.6rem 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
            padding-left: 0;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + 1.2rem + 2px);
        }
        
        /* Loader para AJAX */
        .loader {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top: 4px solid var(--primary-color);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        /* Estilo para el contador de resultados */
        #contador-resultados {
            font-weight: 500;
        }
        
        /* Estilo para las etiquetas */
        .form-label {
            font-weight: 600;
            font-size: 0.75rem;
            letter-spacing: 0.7px;
        }
    </style>
@stop

@section('js')
    <!-- Incluir JS de Leaflet -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        let map;
        let marker;
        let timeoutId;
        
        $(document).ready(function() {
            // Inicializar tooltips
            $('[data-toggle="tooltip"]').tooltip();
            
            // Inicializar select2 para todos los selects
            if ($.fn.select2) {
                $('.select2-simple').select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    minimumResultsForSearch: 10 // Mostrar búsqueda solo si hay muchos elementos
                });
            }
            
            // Filtrado en tiempo real
            $('.filtro-input').on('change keyup', function() {
                // Cancelar cualquier solicitud pendiente
                clearTimeout(timeoutId);
                
                // Mostrar indicador de carga
                showLoading();
                
                // Establecer un retraso para evitar muchas solicitudes
                timeoutId = setTimeout(function() {
                    // Obtener los valores de todos los filtros
                    const formData = new FormData(document.getElementById('filtros-form'));
                    
                    // Crear la URL con los parámetros de filtro
                    const params = new URLSearchParams();
                    for (const [key, value] of formData.entries()) {
                        if (value) {
                            params.append(key, value);
                        }
                    }
                    
                    // Añadir per_page si está definido
                    const perPage = $('#per-page').val();
                    if (perPage) {
                        params.append('per_page', perPage);
                    }
                    
                    // Construir la URL final
                    const url = `${window.location.pathname}?${params.toString()}`;
                    
                    // Realizar la solicitud AJAX
                    $.ajax({
                        url: url,
                        type: 'GET',
                        dataType: 'html',
                        success: function(response) {
                            // Extraer el contenido de la tabla y la paginación del HTML recibido
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = response;
                            
                            // Actualizar la tabla
                            $('#resultados-container').html($(tempDiv).find('#resultados-container').html());
                            
                            // Actualizar la paginación
                            $('.pagination-container').html($(tempDiv).find('.pagination-container').html());
                            
                            // Actualizar el contador de resultados
                            $('#contador-resultados').html($(tempDiv).find('#contador-resultados').html());
                            
                            // Actualizar la URL del navegador sin recargar la página
                            window.history.pushState({ path: url }, '', url);
                            
                            // Reinicializar tooltips
                            $('[data-toggle="tooltip"]').tooltip();
                            
                            // Ocultar indicador de carga
                            hideLoading();
                        },
                        error: function(xhr, status, error) {
                            console.error('Error en la solicitud AJAX:', error);
                            hideLoading();
                            
                            // Mostrar mensaje de error
                            const errorMsg = $('<div class="alert alert-danger m-3" role="alert">')
                                .text('Error al cargar los resultados. Intente nuevamente.');
                            $('#resultados-container').html(errorMsg);
                        }
                    });
                }, 300); // 300ms de retraso para evitar demasiadas solicitudes
            });
            
            // Función para mostrar indicador de carga
            function showLoading() {
                // Añadir un overlay con un spinner
                const loadingOverlay = $('<div id="loading-overlay" class="position-absolute bg-white bg-opacity-75" style="top:0;left:0;right:0;bottom:0;z-index:9;"></div>');
                const spinner = $('<div class="loader"></div>');
                loadingOverlay.append(spinner);
                
                // Añadir al contenedor de resultados si no existe ya
                if ($('#loading-overlay').length === 0) {
                    $('#resultados-container').css('position', 'relative').append(loadingOverlay);
                }
            }
            
            // Función para ocultar indicador de carga
            function hideLoading() {
                $('#loading-overlay').fadeOut(200, function() {
                    $(this).remove();
                });
            }
            
            // Manejar cambio en elementos por página
            $('#per-page').change(function() {
                // Disparar el evento change en cualquier filtro para activar la búsqueda
                $('.filtro-input').first().trigger('change');
            });
            
            // Botón para limpiar filtros
            $('#limpiar-filtros').click(function() {
                // Resetear todos los campos del formulario
                $('#filtros-form')[0].reset();
                
                // Resetear select2 si existe
                if ($.fn.select2) {
                    $('.select2-simple').val(null).trigger('change');
                }
                
                // Activar la búsqueda con filtros limpios
                $('.filtro-input').first().trigger('change');
            });
            
            // Exportar a Excel
            $('#btn-exportar-excel').click(function() {
                // Obtener los valores actuales de los filtros
                const formData = new FormData(document.getElementById('filtros-form'));
                
                // Crear la URL con los parámetros de filtro
                const params = new URLSearchParams();
                for (const [key, value] of formData.entries()) {
                    if (value) {
                        params.append(key, value);
                    }
                }
                
                // Añadir el parámetro de exportación
                params.append('export', 'excel');
                
                // Redirigir para descargar
                window.location.href = `${window.location.pathname}?${params.toString()}`;
            });
            
            // Manejar el clic en el botón "Ver mapa"
            $('.ver-mapa').click(function() {
                const lat = parseFloat($(this).data('lat'));
                const lng = parseFloat($(this).data('lng'));
                const id = $(this).data('id');
                const cliente = $(this).data('cliente');
                const asesor = $(this).data('asesor');
                const fecha = $(this).data('fecha');
                
                // Actualizar información en el modal
                $('#gestion-id').text(id);
                $('#gestion-cliente').text(cliente);
                $('#gestion-asesor').text(asesor);
                $('#gestion-fecha').text(fecha);
                $('#gestion-coordenadas').text(`${lat}, ${lng}`);
                
                // Actualizar enlace a OpenStreetMap
                $('#ver-osm').attr('href', `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}&zoom=17`);
                
                // Crear el mapa cuando se abre el modal
                $('#mapaModal').on('shown.bs.modal', function() {
                    // Crear nueva instancia del mapa con Leaflet
                    if (map) {
                        map.remove(); // Limpiar mapa anterior si existe
                    }
                    
                    // Inicializar mapa en el contenedor
                    map = L.map('mapa').setView([lat, lng], 16);
                    
                    // Añadir capa de mosaicos de OpenStreetMap
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 19
                    }).addTo(map);
                    
                    // Añadir marcador en la ubicación con estilo personalizado
                    const customIcon = L.divIcon({
                        className: 'custom-div-icon',
                        html: `<div class="marker-pin bg-primary shadow-lg"></div>`,
                        iconSize: [30, 42],
                        iconAnchor: [15, 42]
                    });
                    
                    marker = L.marker([lat, lng], { icon: customIcon }).addTo(map);
                    
                    // Añadir popup al marcador
                    marker.bindPopup(`
                        <div class="text-center">
                            <h6>${cliente}</h6>
                            <p class="mb-1">Gestión #${id}</p>
                            <hr class="my-2">
                            <p class="small m-0 text-muted">
                                Registrado por: ${asesor}<br>
                                Fecha: ${fecha}
                            </p>
                        </div>
                    `).openPopup();
                    
                    // Asegurar que el mapa se renderice correctamente
                    setTimeout(function() {
                        map.invalidateSize();
                    }, 100);
                });
                
                // Limpiar el mapa cuando se cierra el modal
                $('#mapaModal').on('hidden.bs.modal', function() {
                    if (map) {
                        map.remove();
                        map = null;
                        marker = null;
                    }
                });
            });
            
            // Inicializar eventos para botones de mapa después de cargar contenido por AJAX
            $(document).on('click', '.ver-mapa', function() {
                const lat = parseFloat($(this).data('lat'));
                const lng = parseFloat($(this).data('lng'));
                const id = $(this).data('id');
                const cliente = $(this).data('cliente');
                const asesor = $(this).data('asesor');
                const fecha = $(this).data('fecha');
                
                // Actualizar información en el modal
                $('#gestion-id').text(id);
                $('#gestion-cliente').text(cliente);
                $('#gestion-asesor').text(asesor);
                $('#gestion-fecha').text(fecha);
                $('#gestion-coordenadas').text(`${lat}, ${lng}`);
                
                // Actualizar enlace a OpenStreetMap
                $('#ver-osm').attr('href', `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}&zoom=17`);
            });
        });
    </script>
    
    <!-- Estilos adicionales para el marcador personalizado -->
    <style>
        .marker-pin {
            width: 30px;
            height: 30px;
            border-radius: 50% 50% 50% 0;
            background: var(--primary-color);
            position: absolute;
            transform: rotate(-45deg);
            left: 50%;
            top: 50%;
            margin: -15px 0 0 -15px;
            border: 2px solid white;
        }
        
        .marker-pin::after {
            content: '';
            width: 14px;
            height: 14px;
            margin: 8px 0 0 8px;
            background: white;
            position: absolute;
            border-radius: 50%;
        }
    </style>
@stop