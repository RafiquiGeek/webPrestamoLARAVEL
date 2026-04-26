@extends('layouts.admin')

@section('title', 'Auditoría - ' . $user->name)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>
                <i class="fas fa-user-shield me-2"></i>
                Auditoría - {{ $user->name }}
            </h1>
            <p class="mb-0 text-muted">Historial de actividades del usuario</p>
        </div>
        <div>
            <a href="{{ route('admin.auditoria.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
@stop

@section('content')
<!-- Información del usuario -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Información del Usuario</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Nombre:</strong> {{ $user->name }}
                    </div>
                    <div class="col-md-3">
                        <strong>Email:</strong> {{ $user->email }}
                    </div>
                    <div class="col-md-3">
                        <strong>Rol:</strong> 
                        @if($user->roles->isNotEmpty())
                            {{ $user->roles->pluck('name')->join(', ') }}
                        @else
                            Sin rol asignado
                        @endif
                    </div>
                    <div class="col-md-3">
                        <strong>Total Actividades:</strong> 
                        <span class="badge badge-info">{{ $activities->total() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Filtros</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.auditoria.usuario', $user->id) }}">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="fecha_inicio">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="fecha_fin">Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="accion">Acción</label>
                            <select name="accion" class="form-control">
                                <option value="">Todas las acciones</option>
                                <option value="CREATE" {{ request('accion') == 'CREATE' ? 'selected' : '' }}>Crear</option>
                                <option value="UPDATE" {{ request('accion') == 'UPDATE' ? 'selected' : '' }}>Actualizar</option>
                                <option value="DELETE" {{ request('accion') == 'DELETE' ? 'selected' : '' }}>Eliminar</option>
                                <option value="VIEW" {{ request('accion') == 'VIEW' ? 'selected' : '' }}>Ver</option>
                                <option value="ERROR" {{ request('accion') == 'ERROR' ? 'selected' : '' }}>Error</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="recurso">Recurso</label>
                            <input type="text" name="recurso" class="form-control" placeholder="Ej: prestamos, clientes..." value="{{ request('recurso') }}">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                            <a href="{{ route('admin.auditoria.usuario', $user->id) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Timeline de actividades -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Timeline de Actividades</h3>
            </div>
            <div class="card-body">
                @if($activities->count() > 0)
                    <div class="timeline">
                        @php $currentDate = null; @endphp
                        @foreach($activities as $activity)
                            @php $activityDate = $activity->created_at->format('Y-m-d'); @endphp
                            
                            @if($currentDate !== $activityDate)
                                @php $currentDate = $activityDate; @endphp
                                <div class="time-label">
                                    <span class="bg-blue">{{ $activity->created_at->format('d/m/Y') }}</span>
                                </div>
                            @endif

                            <div>
                                @switch($activity->action)
                                    @case('CREATE')
                                        <i class="fas fa-plus bg-success"></i>
                                        @break
                                    @case('UPDATE')
                                        <i class="fas fa-edit bg-warning"></i>
                                        @break
                                    @case('DELETE')
                                        <i class="fas fa-trash bg-danger"></i>
                                        @break
                                    @case('VIEW')
                                        <i class="fas fa-eye bg-info"></i>
                                        @break
                                    @default
                                        <i class="fas fa-question bg-secondary"></i>
                                @endswitch

                                <div class="timeline-item">
                                    <span class="time">
                                        <i class="fas fa-clock"></i> {{ $activity->created_at->format('H:i:s') }}
                                    </span>
                                    <h3 class="timeline-header">
                                        <strong>{{ $activity->action }}</strong> en 
                                        <code>{{ $activity->resource }}</code>
                                        @if($activity->resource_id)
                                            (ID: {{ $activity->resource_id }})
                                        @endif
                                    </h3>
                                    <div class="timeline-body">
                                        {{ $activity->description }}
                                        
                                        @if($activity->new_values)
                                            <div class="mt-2">
                                                <small class="text-muted">Datos enviados:</small>
                                                <div class="bg-light p-2 rounded">
                                                    <small>
                                                        @foreach($activity->new_values as $key => $value)
                                                            <strong>{{ $key }}:</strong> 
                                                            {{ is_array($value) ? json_encode($value) : $value }}
                                                            @if(!$loop->last)<br>@endif
                                                        @endforeach
                                                    </small>
                                                </div>
                                            </div>
                                        @endif
                                        
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-globe"></i> IP: {{ $activity->ip_address }} | 
                                                <i class="fas fa-link"></i> {{ $activity->method }} | 
                                                <i class="fas fa-external-link-alt"></i> 
                                                <a href="{{ $activity->url }}" target="_blank" class="text-muted">
                                                    {{ Str::limit($activity->url, 50) }}
                                                </a>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div>
                            <i class="fas fa-clock bg-gray"></i>
                        </div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No se encontraron actividades</h5>
                        <p class="text-muted">No hay actividades registradas para este usuario con los filtros aplicados.</p>
                    </div>
                @endif
            </div>
            
            @if($activities->hasPages())
                <div class="card-footer clearfix">
                    {{ $activities->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .timeline {
        position: relative;
        margin: 0 0 30px 0;
        padding: 0;
        list-style: none;
    }

    .timeline:before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        width: 4px;
        background: #ddd;
        left: 31px;
        margin: 0;
        border-radius: 2px;
    }

    .timeline > div {
        position: relative;
        margin: 0 0 15px 0;
        clear: both;
    }

    .timeline > div > .timeline-item {
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
        border-radius: 3px;
        margin-top: 0;
        background: #fff;
        color: #444;
        margin-left: 60px;
        margin-right: 15px;
        padding: 0;
        position: relative;
    }

    .timeline > div > .fas,
    .timeline > div > .far,
    .timeline > div > .fab {
        width: 30px;
        height: 30px;
        font-size: 15px;
        line-height: 30px;
        position: absolute;
        color: #666;
        background: #d2d6de;
        border-radius: 50%;
        text-align: center;
        left: 18px;
        top: 0;
    }

    .timeline > .time-label > span {
        font-weight: 600;
        color: #fff;
        border-radius: 4px;
        display: inline-block;
        padding: 5px;
    }

    .timeline-header {
        margin: 0;
        color: #555;
        border-bottom: 1px solid #f4f4f4;
        padding: 10px;
        font-weight: 600;
        font-size: 16px;
    }

    .timeline-body {
        padding: 10px;
    }

    .time {
        color: #999;
        float: right;
        padding: 10px;
        font-size: 12px;
    }
</style>
@stop