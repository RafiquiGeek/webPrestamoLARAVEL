@extends('layouts.admin')

@section('title', 'Resumen de Auditoría')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>
                <i class="fas fa-chart-bar me-2"></i>
                Resumen de Auditoría
            </h1>
            <p class="mb-0 text-muted">Estadísticas y métricas de actividad del sistema</p>
        </div>
        <div>
            <a href="{{ route('admin.auditoria.index') }}" class="btn btn-secondary">
                <i class="fas fa-list"></i> Ver Actividades
            </a>
        </div>
    </div>
@stop

@section('content')
<!-- Resumen del día -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $resumenHoy['total_actividades'] }}</h3>
                <p>Actividades Hoy</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $resumenHoy['usuarios_activos'] }}</h3>
                <p>Usuarios Activos Hoy</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $resumenAyer['total_actividades'] }}</h3>
                <p>Actividades Ayer</p>
            </div>
            <div class="icon">
                <i class="fas fa-history"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                @php
                    $incremento = $resumenAyer['total_actividades'] > 0 
                        ? round((($resumenHoy['total_actividades'] - $resumenAyer['total_actividades']) / $resumenAyer['total_actividades']) * 100) 
                        : 0;
                @endphp
                <h3>{{ $incremento > 0 ? '+' : '' }}{{ $incremento }}%</h3>
                <p>Cambio vs Ayer</p>
            </div>
            <div class="icon">
                <i class="fas fa-percentage"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Acciones de hoy -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Acciones de Hoy</h3>
            </div>
            <div class="card-body">
                @if($resumenHoy['acciones_por_tipo']->isNotEmpty())
                    <canvas id="accionesChart" width="400" height="200"></canvas>
                @else
                    <p class="text-center text-muted">No hay actividades registradas hoy</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Usuarios más activos -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Usuarios Más Activos (7 días)</h3>
            </div>
            <div class="card-body">
                @if($usuariosMasActivos->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th class="text-right">Actividades</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($usuariosMasActivos as $usuario)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.auditoria.usuario', $usuario->user_id) }}">
                                                {{ $usuario->user->codigo }}
                                            </a>
                                        </td>
                                        <td class="text-right">
                                            <span class="badge badge-primary">{{ $usuario->total_actividades }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center text-muted">No hay datos disponibles</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Acciones más comunes -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Acciones Más Comunes (7 días)</h3>
            </div>
            <div class="card-body">
                @if($accionesMasComunes->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Acción</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($accionesMasComunes as $accion)
                                    <tr>
                                        <td>
                                            @switch($accion->action)
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
                                                @default
                                                    <span class="badge badge-secondary">{{ $accion->action }}</span>
                                            @endswitch
                                        </td>
                                        <td class="text-right">{{ $accion->total }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center text-muted">No hay datos disponibles</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Recursos más accedidos -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recursos Más Accedidos (7 días)</h3>
            </div>
            <div class="card-body">
                @if($recursosMasAccedidos->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Recurso</th>
                                    <th class="text-right">Accesos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recursosMasAccedidos as $recurso)
                                    <tr>
                                        <td><code>{{ $recurso->resource }}</code></td>
                                        <td class="text-right">{{ $recurso->total }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center text-muted">No hay datos disponibles</p>
                @endif
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
@if($resumenHoy['acciones_por_tipo']->isNotEmpty())
    const ctx = document.getElementById('accionesChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($resumenHoy['acciones_por_tipo']->keys()) !!},
            datasets: [{
                data: {!! json_encode($resumenHoy['acciones_por_tipo']->values()) !!},
                backgroundColor: [
                    '#28a745', // CREATE - verde
                    '#ffc107', // UPDATE - amarillo
                    '#dc3545', // DELETE - rojo
                    '#17a2b8', // VIEW - azul
                    '#6c757d'  // ERROR - gris
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
@endif
</script>
@stop