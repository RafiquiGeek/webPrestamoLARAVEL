@extends('layouts.admin')

@section('title', 'Reporte de Accesos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-chart-line mr-2"></i>Reporte de Accesos</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.index') }}">Asistencia</a></li>
            <li class="breadcrumb-item active">Reporte de Accesos</li>
        </ol>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Filtros -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-filter mr-2"></i>Filtros de Búsqueda
                    </h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.asistencia.accesos') }}" id="filterForm">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_desde">Fecha Desde</label>
                                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="{{ request('fecha_desde') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_hasta">Fecha Hasta</label>
                                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="{{ request('fecha_hasta') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="usuario">Usuario (Email)</label>
                                    <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Buscar por email" value="{{ request('usuario') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estado">Estado</label>
                                    <select class="form-control" id="estado" name="estado">
                                        <option value="">Todos los estados</option>
                                        <option value="pending" {{ request('estado') == 'pending' ? 'selected' : '' }}>Pendiente</option>
                                        <option value="approved" {{ request('estado') == 'approved' ? 'selected' : '' }}>Aprobado</option>
                                        <option value="denied" {{ request('estado') == 'denied' ? 'selected' : '' }}>Denegado</option>
                                        <option value="used" {{ request('estado') == 'used' ? 'selected' : '' }}>Usado</option>
                                        <option value="expired" {{ request('estado') == 'expired' ? 'selected' : '' }}>Expirado</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search mr-2"></i>Buscar
                                </button>
                                <a href="{{ route('admin.asistencia.accesos') }}" class="btn btn-secondary ml-2">
                                    <i class="fas fa-times mr-2"></i>Limpiar Filtros
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Accesos -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-table mr-2"></i>Historial de Accesos
                        <span class="badge badge-info ml-2">{{ $accesos->total() }} registros</span>
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Usuario</th>
                                    <th>Cdigo</th>
                                    <th>IP Address</th>
                                    <th>Estado</th>
                                    <th>Procesado por</th>
                                    <th>Fecha/Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($accesos as $acceso)
                                    <tr>
                                        <td>{{ $accesos->firstItem() + $loop->index }}</td>
                                        <td>
                                            <div class="user-info">
                                                <strong>{{ $acceso->user_name }}</strong><br>
                                                <small class="text-muted">{{ $acceso->email }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <code class="badge badge-dark p-2 copy-code" data-code="{{ $acceso->access_code }}" style="cursor: pointer;" title="Clic para copiar">
                                                {{ $acceso->access_code }}
                                            </code>
                                        </td>
                                        <td>
                                            <code class="badge badge-secondary">{{ $acceso->ip_address }}</code>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $acceso->status_color }}">
                                                {{ $acceso->status_text }}
                                            </span>
                                            @if($acceso->status == 'used')
                                                <br><small class="text-success">
                                                    <i class="fas fa-check mr-1"></i>{{ $acceso->used_at->format('H:i:s') }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($acceso->approvedBy)
                                                <strong>{{ $acceso->approvedBy->name }}</strong><br>
                                                <small class="text-muted">{{ $acceso->approved_at->format('H:i:s') }}</small>
                                            @else
                                                <span class="text-muted"></span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $acceso->created_at->format('d/m/Y H:i:s') }}</strong><br>
                                            <small class="text-muted">{{ $acceso->created_at->diffForHumans() }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="fas fa-search fa-2x text-muted mb-3"></i>
                                            <h5>No se encontraron registros</h5>
                                            <p class="text-muted">Intenta ajustar los filtros de b�squeda.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    {{ $accesos->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@push('scripts')
<script>
    // Copiar c�digo al portapapeles
    $(document).on('click', '.copy-code', function() {
        const code = $(this).data('code');
        navigator.clipboard.writeText(code).then(function() {
            toastr.success(`C�digo ${code} copiado al portapapeles`);
        });
    });
</script>
@endpush