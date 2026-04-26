@extends('layouts.admin')
@section('title', 'Reportes de Asistencia')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-file-alt mr-2"></i>Reportes de Asistencia</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.index') }}">Asistencia</a></li>
            <li class="breadcrumb-item active">Reportes</li>
        </ol>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Filtros -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-filter mr-2"></i>Filtros de Búsqueda
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool btn-sm" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.asistencia.reportes') }}" id="form-filtros">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_inicio">Fecha Inicio</label>
                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                                           value="{{ $fechaInicio }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_fin">Fecha Fin</label>
                                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                                           value="{{ $fechaFin }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="area_id">Área Laboral</label>
                                    <select class="form-control" id="area_id" name="area_id">
                                        <option value="">Todas las áreas</option>
                                        @foreach($areas as $area)
                                            <option value="{{ $area->id }}" {{ $areaId == $area->id ? 'selected' : '' }}>
                                                {{ $area->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="user_id">Usuario</label>
                                    <select class="form-control" id="user_id" name="user_id">
                                        <option value="">Todos los usuarios</option>
                                        @foreach($usuarios as $usuario)
                                            <option value="{{ $usuario->id }}" {{ $userId == $usuario->id ? 'selected' : '' }}>
                                                {{ $usuario->codigo }} - {{ $usuario->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="btn-group" role="group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search mr-1"></i>Buscar
                                    </button>
                                    <a href="{{ route('admin.asistencia.reportes') }}" class="btn btn-secondary">
                                        <i class="fas fa-times mr-1"></i>Limpiar
                                    </a>
                                    <button type="button" class="btn btn-success" onclick="exportarReporte()">
                                        <i class="fas fa-file-excel mr-1"></i>Exportar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Resultados -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-table mr-2"></i>Registros de Asistencia
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-primary">{{ $registros->total() }} registros</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($registros->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Empleado</th>
                                        <th>Área</th>
                                        <th>Horario</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>Refrigerio</th>
                                        <th>Estado</th>
                                        <th>Horas</th>
                                        <th>Ubicación</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($registros as $registro)
                                        <tr>
                                            <td>
                                                <strong>{{ $registro->fecha->format('d/m/Y') }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $registro->fecha->locale('es')->isoFormat('dddd') }}</small>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>{{ $registro->usuario->name }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $registro->usuario->codigo }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge" style="background-color: {{ $registro->asignacion->areaLaboral->color }}; color: white;">
                                                    {{ $registro->asignacion->areaLaboral->nombre }}
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    {{ \Carbon\Carbon::parse($registro->asignacion->horarioTrabajo->hora_entrada)->format('H:i') }} - 
                                                    {{ \Carbon\Carbon::parse($registro->asignacion->horarioTrabajo->hora_salida)->format('H:i') }}
                                                </small>
                                            </td>
                                            <td>
                                                @if($registro->hora_entrada)
                                                    <span class="text-primary">
                                                        <i class="fas fa-sign-in-alt mr-1"></i>
                                                        {{ \Carbon\Carbon::parse($registro->hora_entrada)->format('H:i:s') }}
                                                    </span>
                                                    @if($registro->minutos_tardanza > 0)
                                                        <br><small class="text-warning">+{{ $registro->minutos_tardanza }}min</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">Sin registro</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($registro->hora_salida)
                                                    <span class="text-danger">
                                                        <i class="fas fa-sign-out-alt mr-1"></i>
                                                        {{ \Carbon\Carbon::parse($registro->hora_salida)->format('H:i:s') }}
                                                    </span>
                                                @else
                                                    <span class="badge badge-secondary">Pendiente</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($registro->inicio_refrigerio && $registro->fin_refrigerio)
                                                    <div>
                                                        <span class="text-info">
                                                            <i class="fas fa-coffee mr-1"></i>
                                                            {{ \Carbon\Carbon::parse($registro->inicio_refrigerio)->format('H:i') }} - 
                                                            {{ \Carbon\Carbon::parse($registro->fin_refrigerio)->format('H:i') }}
                                                        </span>
                                                        <br>
                                                        <small>
                                                            @php
                                                                $duracionRefrigerio = \Carbon\Carbon::parse($registro->inicio_refrigerio)->diffInMinutes(\Carbon\Carbon::parse($registro->fin_refrigerio));
                                                            @endphp
                                                            <span class="badge badge-{{ $registro->estado_refrigerio == 'excedido' ? 'warning' : 'info' }}">
                                                                {{ $duracionRefrigerio }} min
                                                            </span>
                                                            @if($registro->estado_refrigerio == 'excedido')
                                                                <i class="fas fa-exclamation-triangle text-warning" title="Refrigerio excedido"></i>
                                                            @endif
                                                        </small>
                                                    </div>
                                                @elseif($registro->inicio_refrigerio && !$registro->fin_refrigerio)
                                                    <div>
                                                        <span class="text-warning">
                                                            <i class="fas fa-coffee mr-1"></i>
                                                            {{ \Carbon\Carbon::parse($registro->inicio_refrigerio)->format('H:i') }} - 
                                                            <span class="badge badge-warning">En curso</span>
                                                        </span>
                                                    </div>
                                                @else
                                                    <span class="text-muted">
                                                        <i class="fas fa-minus"></i>
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div>
                                                    <span class="badge badge-{{ $registro->estado_entrada_color }}">
                                                        {{ ucfirst($registro->estado_entrada) }}
                                                    </span>
                                                    @if($registro->hora_salida)
                                                        <br>
                                                        <span class="badge badge-{{ $registro->estado_salida_color }}">
                                                            {{ ucfirst($registro->estado_salida) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($registro->tieneAsistenciaCompleta())
                                                    <strong>{{ $registro->calcularHorasTrabajadas() }}h</strong>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($registro->latitud_entrada && $registro->longitud_entrada)
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="mostrarUbicacion({{ $registro->latitud_entrada }}, {{ $registro->longitud_entrada }}, 'entrada', '{{ $registro->usuario->name }}', '{{ $registro->fecha->format('d/m/Y') }}')"
                                                            title="Ver ubicación de entrada">
                                                        <i class="fas fa-map-marker-alt"></i> Entrada
                                                    </button>
                                                @endif
                                                @if($registro->latitud_salida && $registro->longitud_salida)
                                                    <button type="button" class="btn btn-sm btn-outline-danger ml-1" 
                                                            onclick="mostrarUbicacion({{ $registro->latitud_salida }}, {{ $registro->longitud_salida }}, 'salida', '{{ $registro->usuario->name }}', '{{ $registro->fecha->format('d/m/Y') }}')"
                                                            title="Ver ubicación de salida">
                                                        <i class="fas fa-map-marker-alt"></i> Salida
                                                    </button>
                                                @endif
                                                @if($registro->latitud_inicio_refrigerio && $registro->longitud_inicio_refrigerio)
                                                    <button type="button" class="btn btn-sm btn-outline-info ml-1" 
                                                            onclick="mostrarUbicacion({{ $registro->latitud_inicio_refrigerio }}, {{ $registro->longitud_inicio_refrigerio }}, 'inicio_refrigerio', '{{ $registro->usuario->name }}', '{{ $registro->fecha->format('d/m/Y') }}')"
                                                            title="Ver ubicación de inicio de refrigerio">
                                                        <i class="fas fa-coffee"></i> Inicio
                                                    </button>
                                                @endif
                                                @if($registro->latitud_fin_refrigerio && $registro->longitud_fin_refrigerio)
                                                    <button type="button" class="btn btn-sm btn-outline-success ml-1" 
                                                            onclick="mostrarUbicacion({{ $registro->latitud_fin_refrigerio }}, {{ $registro->longitud_fin_refrigerio }}, 'fin_refrigerio', '{{ $registro->usuario->name }}', '{{ $registro->fecha->format('d/m/Y') }}')"
                                                            title="Ver ubicación de fin de refrigerio">
                                                        <i class="fas fa-coffee"></i> Fin
                                                    </button>
                                                @endif
                                                @php
                                                    $tieneUbicacion = $registro->latitud_entrada && $registro->longitud_entrada || 
                                                                     $registro->latitud_salida && $registro->longitud_salida ||
                                                                     $registro->latitud_inicio_refrigerio && $registro->longitud_inicio_refrigerio ||
                                                                     $registro->latitud_fin_refrigerio && $registro->longitud_fin_refrigerio;
                                                @endphp
                                                @if(!$tieneUbicacion)
                                                    <span class="text-muted">Sin ubicación</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($registro->observaciones)
                                                    <span class="text-info" title="{{ $registro->observaciones }}">
                                                        <i class="fas fa-comment"></i>
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        @if($registros->hasPages())
                            <div class="card-footer">
                                {{ $registros->withQueryString()->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No se encontraron registros</h4>
                            <p class="text-muted">
                                No hay registros de asistencia que coincidan con los filtros seleccionados.
                                <br>
                                Intenta modificar los criterios de búsqueda.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen estadístico -->
    @if($registros->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar mr-2"></i>Resumen Estadístico
                    </h3>
                </div>
                <div class="card-body">
                    @php
                        $totalRegistros = $registros->count();
                        $registrosCompletos = $registros->filter(function($r) { return $r->tieneAsistenciaCompleta(); })->count();
                        $totalTardanzas = $registros->where('estado_entrada', 'tardanza')->count();
                        $totalFaltas = $registros->where('estado_entrada', 'falta')->count();
                        $promedioHoras = $registros->filter(function($r) { return $r->tieneAsistenciaCompleta(); })->avg(function($r) { return $r->calcularHorasTrabajadas(); });
                        
                        // Estadísticas de refrigerio
                        $registrosConRefrigerio = $registros->filter(function($r) { return $r->inicio_refrigerio && $r->fin_refrigerio; })->count();
                        $refrigeriosExcedidos = $registros->where('estado_refrigerio', 'excedido')->count();
                        $promedioRefrigerio = $registros->filter(function($r) { 
                            return $r->inicio_refrigerio && $r->fin_refrigerio; 
                        })->avg(function($r) { 
                            return \Carbon\Carbon::parse($r->inicio_refrigerio)->diffInMinutes(\Carbon\Carbon::parse($r->fin_refrigerio)); 
                        });
                    @endphp
                    
                    <div class="row">
                        <div class="col-lg-2 col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-list"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total de Registros</span>
                                    <span class="info-box-number">{{ $totalRegistros }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-2 col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Asistencias Completas</span>
                                    <span class="info-box-number">{{ $registrosCompletos }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-2 col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-clock"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tardanzas</span>
                                    <span class="info-box-number">{{ $totalTardanzas }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-2 col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary">
                                    <i class="fas fa-hourglass-half"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Promedio Horas</span>
                                    <span class="info-box-number">{{ number_format($promedioHoras ?? 0, 1) }}h</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon" style="background-color: #17a2b8;">
                                    <i class="fas fa-coffee"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Con Refrigerio</span>
                                    <span class="info-box-number">{{ $registrosConRefrigerio }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Refrigerios Excedidos</span>
                                    <span class="info-box-number">{{ $refrigeriosExcedidos }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($registrosConRefrigerio > 0)
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Promedio de duración de refrigerio:</strong> {{ number_format($promedioRefrigerio ?? 0, 0) }} minutos
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Sidebar del mapa -->
<div class="map-sidebar" id="mapSidebar">
    <div class="map-sidebar-header">
        <h5 class="map-sidebar-title">
            <i class="fas fa-map-marker-alt mr-2"></i>
            <span id="mapTitle">Ubicación de Asistencia</span>
        </h5>
        <button type="button" class="btn btn-sm btn-outline-light" onclick="cerrarMapa()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="map-sidebar-body">
        <div id="mapContainer" style="height: 400px; width: 100%;"></div>
        <div class="mt-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Información del Registro</h6>
                    <div id="mapInfo">
                        <p><strong>Empleado:</strong> <span id="empleadoInfo">-</span></p>
                        <p><strong>Fecha:</strong> <span id="fechaInfo">-</span></p>
                        <p><strong>Tipo:</strong> <span id="tipoInfo">-</span></p>
                        <p><strong>Coordenadas:</strong> <span id="coordenadasInfo">-</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Overlay para cerrar el sidebar -->
<div class="map-overlay" id="mapOverlay" onclick="cerrarMapa()"></div>
@stop

@section('css')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
.card {
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
    font-size: 0.875rem;
}

.info-box {
    margin-bottom: 15px;
}

.btn-group .btn {
    margin-right: 5px;
}

.badge {
    font-size: 0.75rem;
}

/* Estilos del sidebar del mapa */
.map-sidebar {
    position: fixed;
    top: 0;
    right: -500px;
    width: 500px;
    height: 100vh;
    background: white;
    box-shadow: -2px 0 10px rgba(0,0,0,0.1);
    z-index: 9999;
    transition: right 0.3s ease;
    overflow-y: auto;
}

.map-sidebar.active {
    right: 0;
}

.map-sidebar-header {
    background: #007bff;
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.map-sidebar-title {
    margin: 0;
    flex-grow: 1;
}

.map-sidebar-body {
    padding: 20px;
}

.map-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.5);
    z-index: 9998;
    display: none;
}

.map-overlay.active {
    display: block;
}

/* Responsive */
@media (max-width: 768px) {
    .map-sidebar {
        width: 100vw;
        right: -100vw;
    }
}

/* Estilos para los botones de ubicación */
.btn-sm {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Mejoras para leaflet */
.leaflet-container {
    border-radius: 8px;
}

/* Custom marker colors */
.marker-entrada {
    background-color: #007bff;
    border: 2px solid white;
}

.marker-salida {
    background-color: #dc3545;
    border: 2px solid white;
}
</style>
@stop

@section('js')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
let map = null;
let currentMarker = null;

function exportarReporte() {
    // Obtener los valores actuales de los filtros
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;
    const areaId = document.getElementById('area_id').value;
    const userId = document.getElementById('user_id').value;
    
    // Construir la URL con los parámetros de los filtros
    const params = new URLSearchParams({
        export: 'excel',
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin
    });
    
    if (areaId) {
        params.append('area_id', areaId);
    }
    
    if (userId) {
        params.append('user_id', userId);
    }
    
    // Crear la URL completa
    const exportUrl = "{{ route('admin.asistencia.reportes') }}?" + params.toString();
    
    // Mostrar indicador de carga
    Swal.fire({
        title: 'Exportando...',
        text: 'Preparando el reporte de asistencia en Excel',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Crear un enlace temporal y hacer clic en él para descargar
    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = '';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Cerrar el indicador de carga después de un breve momento
    setTimeout(() => {
        Swal.fire({
            icon: 'success',
            title: 'Exportación completada',
            text: 'El reporte se ha descargado exitosamente',
            timer: 2000,
            showConfirmButton: false
        });
    }, 1000);
}

function mostrarUbicacion(lat, lng, tipo, empleado, fecha) {
    // Mostrar sidebar y overlay
    document.getElementById('mapSidebar').classList.add('active');
    document.getElementById('mapOverlay').classList.add('active');
    
    // Actualizar información
    document.getElementById('empleadoInfo').textContent = empleado;
    document.getElementById('fechaInfo').textContent = fecha;
    document.getElementById('tipoInfo').textContent = 
        tipo === 'entrada' ? 'Entrada' : 
        tipo === 'salida' ? 'Salida' :
        tipo === 'inicio_refrigerio' ? 'Inicio Refrigerio' :
        tipo === 'fin_refrigerio' ? 'Fin Refrigerio' : tipo;
    document.getElementById('coordenadasInfo').textContent = `${lat}, ${lng}`;
    
    // Inicializar o actualizar mapa
    setTimeout(function() {
        if (map) {
            map.remove();
        }
        
        // Crear nuevo mapa
        map = L.map('mapContainer').setView([lat, lng], 16);
        
        // Agregar capa de OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);
        
        // Crear icono personalizado según el tipo
        const iconColor = 
            tipo === 'entrada' ? '#007bff' : 
            tipo === 'salida' ? '#dc3545' :
            tipo === 'inicio_refrigerio' ? '#17a2b8' :
            tipo === 'fin_refrigerio' ? '#28a745' : '#6c757d';
        const iconHtml = `
            <div style="
                background-color: ${iconColor};
                width: 20px;
                height: 20px;
                border-radius: 50%;
                border: 3px solid white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 12px;
                font-weight: bold;
            ">
                ${tipo === 'entrada' ? 'E' : 
                  tipo === 'salida' ? 'S' :
                  tipo === 'inicio_refrigerio' ? 'R' :
                  tipo === 'fin_refrigerio' ? 'R' : '?'}
            </div>
        `;
        
        const customIcon = L.divIcon({
            html: iconHtml,
            className: 'custom-marker',
            iconSize: [26, 26],
            iconAnchor: [13, 13]
        });
        
        // Agregar marcador
        currentMarker = L.marker([lat, lng], { icon: customIcon }).addTo(map);
        
        const popupContent = `
            <div>
                <h6><strong>${empleado}</strong></h6>
                <p><strong>Fecha:</strong> ${fecha}</p>
                <p><strong>Tipo:</strong> ${
                    tipo === 'entrada' ? 'Entrada' : 
                    tipo === 'salida' ? 'Salida' :
                    tipo === 'inicio_refrigerio' ? 'Inicio Refrigerio' :
                    tipo === 'fin_refrigerio' ? 'Fin Refrigerio' : tipo
                }</p>
                <p><strong>Coordenadas:</strong><br>${lat}, ${lng}</p>
                <hr>
                <small>
                    <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" class="btn btn-sm btn-primary">
                        <i class="fas fa-external-link-alt"></i> Abrir en Google Maps
                    </a>
                </small>
            </div>
        `;
        
        currentMarker.bindPopup(popupContent).openPopup();
        
        // Agregar círculo de precisión
        L.circle([lat, lng], {
            color: iconColor,
            fillColor: iconColor,
            fillOpacity: 0.1,
            radius: 50
        }).addTo(map);
        
    }, 100); // Pequeño delay para asegurar que el contenedor esté visible
}

function cerrarMapa() {
    document.getElementById('mapSidebar').classList.remove('active');
    document.getElementById('mapOverlay').classList.remove('active');
    
    // Limpiar mapa
    if (map) {
        map.remove();
        map = null;
        currentMarker = null;
    }
}

$(document).ready(function() {
    // Auto-submit del formulario cuando cambien los filtros
    $('#area_id, #user_id').on('change', function() {
        $('#form-filtros').submit();
    });
    
    // Tooltips para las observaciones
    $('[title]').tooltip();
    
    // Cerrar mapa con tecla ESC
    $(document).keyup(function(e) {
        if (e.keyCode === 27) { // ESC key
            cerrarMapa();
        }
    });
    
    // Prevenir cierre accidental del mapa al hacer clic dentro del sidebar
    $('#mapSidebar').click(function(e) {
        e.stopPropagation();
    });
});
</script>
@stop