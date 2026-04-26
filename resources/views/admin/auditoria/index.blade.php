@extends('layouts.admin')

@section('title', 'Auditoría del Sistema')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>
                <i class="fas fa-shield-alt me-2"></i>
                Auditoría del Sistema
            </h1>
            <p class="mb-0 text-muted">Registro de actividades de usuarios</p>
        </div>
        <div>
            <a href="{{ route('admin.auditoria.resumen') }}" class="btn btn-info">
                <i class="fas fa-chart-bar"></i> Resumen
            </a>
            <a href="{{ route('admin.auditoria.exportar', request()->query()) }}" class="btn btn-success">
                <i class="fas fa-download"></i> Exportar
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
                            <label for="usuario_id">Usuario</label>
                            <select name="usuario_id" class="form-control">
                                <option value="">Todos los usuarios</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('usuario_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="fecha_inicio">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="fecha_fin">Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                        </div>
                        <div class="col-md-2">
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
                            <label for="buscar">Buscar</label>
                            <div class="input-group">
                                <input type="text" name="buscar" class="form-control" placeholder="Buscar en descripción o URL..." value="{{ request('buscar') }}">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                            <a href="{{ route('admin.auditoria.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Actividades Registradas</h3>
                <div class="card-tools">
                    <span class="badge badge-info">{{ $activities->total() }} registros</span>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Recurso</th>
                            <th>Descripción</th>
                            <th>IP</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                            <tr>
                                <td>
                                    <small>{{ $activity->created_at->format('d/m/Y H:i:s') }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('admin.auditoria.usuario', $activity->user_id) }}" class="text-decoration-none">
                                        {{ $activity->user->name }}
                                    </a>
                                </td>
                                <td>
                                    @switch($activity->action)
                                        @case('CREATE')
                                            <span class="badge badge-success">Crear</span>
                                            @break
                                        @case('UPDATE')
                                            <span class="badge badge-warning">Actualizar</span>
                                            @break
                                        @case('DELETE')
                                            <span class="badge badge-danger">Eliminar</span>
                                            @break
                                        @case('VIEW')
                                            <span class="badge badge-info">Ver</span>
                                            @break
                                        @case('ERROR')
                                            <span class="badge badge-dark">Error</span>
                                            @break
                                        @default
                                            <span class="badge badge-secondary">{{ $activity->action }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    <code>{{ $activity->resource }}</code>
                                    @if($activity->resource_id)
                                        <small class="text-muted">({{ $activity->resource_id }})</small>
                                    @endif
                                </td>
                                <td>{{ $activity->description }}</td>
                                <td><small>{{ $activity->ip_address }}</small></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                            data-toggle="modal" data-target="#detailModal{{ $activity->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal de detalle -->
                            <div class="modal fade" id="detailModal{{ $activity->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h4 class="modal-title">Detalle de Actividad</h4>
                                            <button type="button" class="close" data-dismiss="modal">
                                                <span>&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong>Usuario:</strong> {{ $activity->user->name }}<br>
                                                    <strong>Fecha:</strong> {{ $activity->created_at->format('d/m/Y H:i:s') }}<br>
                                                    <strong>Acción:</strong> {{ $activity->action }}<br>
                                                    <strong>Recurso:</strong> {{ $activity->resource }}<br>
                                                    <strong>ID Recurso:</strong> {{ $activity->resource_id ?? 'N/A' }}
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>IP:</strong> {{ $activity->ip_address }}<br>
                                                    <strong>Método:</strong> {{ $activity->method }}<br>
                                                    <strong>URL:</strong> <small>{{ $activity->url }}</small><br>
                                                    <strong>User Agent:</strong> <small>{{ Str::limit($activity->user_agent, 50) }}</small>
                                                </div>
                                            </div>
                                            <hr>
                                            <strong>Descripción:</strong>
                                            <p>{{ $activity->description }}</p>
                                            
                                            @if($activity->new_values)
                                                <strong>Datos enviados:</strong>
                                                <pre class="bg-light p-2">{{ json_encode($activity->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            @endif
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No se encontraron actividades registradas</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
    .table th {
        border-top: none;
    }
    .badge {
        font-size: 0.75em;
    }
    pre {
        font-size: 0.8em;
        max-height: 200px;
        overflow-y: auto;
    }
</style>
@stop