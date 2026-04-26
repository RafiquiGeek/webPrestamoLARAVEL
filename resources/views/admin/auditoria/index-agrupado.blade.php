@extends('layouts.admin')

@section('title', 'Auditoría del Sistema')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>
                <i class="fas fa-shield-alt me-2"></i>
                Auditoría del Sistema
            </h1>
            <p class="mb-0 text-muted">Actividades agrupadas por usuario</p>
        </div>
        <div>
            <a href="{{ route('admin.auditoria.resumen') }}" class="btn btn-info">
                <i class="fas fa-chart-bar"></i> Resumen
            </a>
            <a href="{{ route('admin.auditoria.index', ['detallado' => '1'] + request()->query()) }}" class="btn btn-secondary">
                <i class="fas fa-list"></i> Vista Detallada
            </a>
        </div>
    </div>
@stop

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Filtros de Búsqueda</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.auditoria.index') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="fecha_inicio">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio', now()->subDays(7)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="fecha_fin">Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin', now()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-6">
                            <label>&nbsp;</label>
                            <div class="input-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filtrar
                                </button>
                                <a href="{{ route('admin.auditoria.index') }}" class="btn btn-secondary ml-2">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas generales -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $usuariosConActividad->count() }}</h3>
                <p>Usuarios Activos</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $usuariosConActividad->sum('total_actividades') }}</h3>
                <p>Total Actividades</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $usuariosConActividad->sum('actividades_hoy') }}</h3>
                <p>Actividades Hoy</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                @php
                    $promedio = $usuariosConActividad->count() > 0 
                        ? round($usuariosConActividad->sum('total_actividades') / $usuariosConActividad->count()) 
                        : 0;
                @endphp
                <h3>{{ $promedio }}</h3>
                <p>Promedio por Usuario</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-bar"></i>
            </div>
        </div>
    </div>
</div>

<!-- Lista de usuarios con actividad -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Usuarios con Actividad</h3>
                <div class="card-tools">
                    <span class="badge badge-info">{{ $usuariosConActividad->count() }} usuarios</span>
                </div>
            </div>
            <div class="card-body p-0">
                @forelse($usuariosConActividad as $usuario)
                    <div class="user-activity-card">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar mr-3">
                                        <i class="fas fa-user-circle fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1">{{ $usuario->name }}</h5>
                                        <small class="text-muted">{{ $usuario->email }}</small>
                                    </div>
                                </div>
                                <div class="user-stats d-flex">
                                    <div class="stat-item text-center mr-4">
                                        <div class="stat-number text-primary font-weight-bold">{{ $usuario->total_actividades }}</div>
                                        <div class="stat-label text-muted small">Total</div>
                                    </div>
                                    <div class="stat-item text-center mr-4">
                                        <div class="stat-number text-success font-weight-bold">{{ $usuario->actividades_hoy }}</div>
                                        <div class="stat-label text-muted small">Hoy</div>
                                    </div>
                                    <div class="stat-item text-center">
                                        @if($usuario->ultima_actividad)
                                            <div class="stat-number text-info font-weight-bold">
                                                {{ $usuario->ultima_actividad->created_at->diffForHumans() }}
                                            </div>
                                            <div class="stat-label text-muted small">Última</div>
                                        @else
                                            <div class="stat-number text-muted">-</div>
                                            <div class="stat-label text-muted small">Sin actividad</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Acciones por tipo -->
                                <div class="col-md-4">
                                    <h6 class="text-muted mb-2">Acciones</h6>
                                    @if($usuario->acciones_por_tipo->count() > 0)
                                        @foreach($usuario->acciones_por_tipo as $accion => $total)
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="action-badge">
                                                    @switch($accion)
                                                        @case('CREATE')
                                                            <i class="fas fa-plus text-success"></i> Crear
                                                            @break
                                                        @case('UPDATE')
                                                            <i class="fas fa-edit text-warning"></i> Actualizar
                                                            @break
                                                        @case('DELETE')
                                                            <i class="fas fa-trash text-danger"></i> Eliminar
                                                            @break
                                                        @case('VIEW')
                                                            <i class="fas fa-eye text-info"></i> Ver
                                                            @break
                                                        @default
                                                            <i class="fas fa-question text-secondary"></i> {{ $accion }}
                                                    @endswitch
                                                </span>
                                                <span class="badge badge-primary">{{ $total }}</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <small class="text-muted">Sin acciones registradas</small>
                                    @endif
                                </div>

                                <!-- Recursos más usados -->
                                <div class="col-md-4">
                                    <h6 class="text-muted mb-2">Recursos Más Usados</h6>
                                    @if($usuario->recursos_mas_usados->count() > 0)
                                        @foreach($usuario->recursos_mas_usados as $recurso => $total)
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span><code>{{ $recurso }}</code></span>
                                                <span class="badge badge-secondary">{{ $total }}</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <small class="text-muted">Sin recursos registrados</small>
                                    @endif
                                </div>

                                <!-- Acciones rápidas -->
                                <div class="col-md-4">
                                    <h6 class="text-muted mb-2">Acciones</h6>
                                    <div class="btn-group-vertical w-100">
                                        <a href="{{ route('admin.auditoria.usuario', $usuario->id) }}" 
                                           class="btn btn-outline-primary btn-sm mb-1">
                                            <i class="fas fa-list"></i> Ver Actividades
                                        </a>
                                        <a href="{{ route('admin.auditoria.sesiones-usuario', $usuario->id) }}" 
                                           class="btn btn-outline-info btn-sm mb-1">
                                            <i class="fas fa-clock"></i> Ver Sesiones
                                        </a>
                                        <a href="{{ route('admin.auditoria.tiempo-modulos', $usuario->id) }}" 
                                           class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-chart-bar"></i> Tiempo por Módulos
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No se encontraron usuarios con actividad</h5>
                        <p class="text-muted">Ajusta los filtros de fecha para ver más resultados.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .user-activity-card {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        margin-bottom: 1rem;
        background: white;
    }
    
    .user-activity-card .card-header {
        border-bottom: 1px solid #dee2e6;
        padding: 1rem;
    }
    
    .user-activity-card .card-body {
        padding: 1rem;
    }
    
    .user-avatar {
        min-width: 40px;
    }
    
    .stat-item {
        min-width: 60px;
    }
    
    .stat-number {
        font-size: 1.2em;
        line-height: 1;
    }
    
    .stat-label {
        font-size: 0.75em;
        margin-top: 2px;
    }
    
    .action-badge {
        font-size: 0.875em;
    }
    
    .badge {
        font-size: 0.75em;
    }
    
    .btn-group-vertical .btn {
        border-radius: 0.25rem !important;
        margin-bottom: 0.25rem;
    }
    
    .btn-group-vertical .btn:last-child {
        margin-bottom: 0;
    }
</style>
@stop