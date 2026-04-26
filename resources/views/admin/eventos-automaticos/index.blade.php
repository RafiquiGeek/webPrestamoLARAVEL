@extends('layouts.admin')

@section('title', 'Monitoreo de Eventos Automáticos')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-robot me-2"></i>
                        Sistema de Eventos Automáticos
                    </h3>
                    <div class="card-tools">
                        <span class="badge bg-success">
                            <i class="fas fa-circle"></i>
                            Automático Activo
                        </span>
                    </div>
                </div>
                
                <!-- Estadísticas -->
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $estadisticas['eventos_hoy'] ?? 0 }}</h3>
                                    <p>Eventos Hoy</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ $estadisticas['eventos_exitosos_hoy'] ?? 0 }}</h3>
                                    <p>Exitosos</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $estadisticas['eventos_fallidos_hoy'] ?? 0 }}</h3>
                                    <p>Fallidos</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <h3>{{ number_format($estadisticas['tiempo_promedio_procesamiento'] ?? 0, 2) }}ms</h3>
                                    <p>Tiempo Promedio</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-stopwatch"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-2">
                                <select name="categoria" class="form-control form-control-sm">
                                    <option value="">Todas las categorías</option>
                                    <option value="pagos" {{ request('categoria') == 'pagos' ? 'selected' : '' }}>Pagos</option>
                                    <option value="moras" {{ request('categoria') == 'moras' ? 'selected' : '' }}>Moras</option>
                                    <option value="estados" {{ request('categoria') == 'estados' ? 'selected' : '' }}>Estados</option>
                                    <option value="calculos" {{ request('categoria') == 'calculos' ? 'selected' : '' }}>Cálculos</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="resultado" class="form-control form-control-sm">
                                    <option value="">Todos los resultados</option>
                                    <option value="exitoso" {{ request('resultado') == 'exitoso' ? 'selected' : '' }}>Exitoso</option>
                                    <option value="fallido" {{ request('resultado') == 'fallido' ? 'selected' : '' }}>Fallido</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="prestamo_id" class="form-control form-control-sm" 
                                       placeholder="ID Préstamo" value="{{ request('prestamo_id') }}">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="fecha_desde" class="form-control form-control-sm" 
                                       value="{{ request('fecha_desde') }}">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="fecha_hasta" class="form-control form-control-sm" 
                                       value="{{ request('fecha_hasta') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                                <a href="{{ route('admin.eventos-automaticos.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Tabla de Eventos -->
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha/Hora</th>
                                    <th>Evento</th>
                                    <th>Categoría</th>
                                    <th>Préstamo</th>
                                    <th>Usuario</th>
                                    <th>Resultado</th>
                                    <th>Tiempo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($eventos as $evento)
                                <tr>
                                    <td>
                                        <small>{{ $evento->created_at->format('d/m/Y H:i:s') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $evento->evento }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $evento->categoria }}</span>
                                    </td>
                                    <td>
                                        @if($evento->prestamo)
                                            <a href="{{ route('admin.prestamos.show', $evento->prestamo->id) }}" 
                                               class="text-primary">
                                                #{{ $evento->prestamo->id }}
                                            </a>
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td>
                                        @if($evento->usuario)
                                            {{ $evento->usuario->name }}
                                        @else
                                            Sistema
                                        @endif
                                    </td>
                                    <td>
                                        @if($evento->resultado === 'exitoso')
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Exitoso
                                            </span>
                                        @elseif($evento->resultado === 'fallido')
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times"></i> Fallido
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock"></i> Procesando
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($evento->tiempo_procesamiento)
                                            <small>{{ number_format($evento->tiempo_procesamiento, 2) }}ms</small>
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.eventos-automaticos.show', $evento->id) }}" 
                                           class="btn btn-sm btn-outline-info" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-3">
                                        <i class="fas fa-robot fa-2x mb-2"></i>
                                        <br>
                                        No hay eventos automáticos registrados
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    @if($eventos->hasPages())
                        <div class="d-flex justify-content-center">
                            {{ $eventos->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-refresh cada 30 segundos si no hay filtros activos
@if(!request()->hasAny(['categoria', 'resultado', 'prestamo_id', 'fecha_desde', 'fecha_hasta']))
    setInterval(function() {
        window.location.reload();
    }, 30000);
@endif
</script>
@endpush

@endsection