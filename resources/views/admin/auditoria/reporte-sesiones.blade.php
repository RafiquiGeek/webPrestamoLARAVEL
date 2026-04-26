@extends('layouts.admin')

@section('title', 'Reporte General de Sesiones')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>
                <i class="fas fa-chart-pie me-2"></i>
                Reporte General de Sesiones
            </h1>
            <p class="mb-0 text-muted">Estadísticas de ingreso y permanencia por usuario</p>
        </div>
        <div>
            <a href="{{ route('admin.auditoria.sesiones') }}" class="btn btn-secondary shadow-sm">
                <i class="fas fa-list"></i> Ver Sesiones
            </a>
        </div>
    </div>
@stop

@section('content')
<!-- Estadísticas generales -->
<div class="row mb-4">
    <div class="col-lg-3 col-6">
        <div class="card border-0 shadow-sm h-100 overflow-hidden stats-card">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                     <div>
                        <h3 class="mb-0 font-weight-bolder text-dark">{{ $usuarios->sum('total_sesiones') }}</h3>
                        <span class="text-muted text-uppercase small font-weight-bold">Total Sesiones</span>
                    </div>
                    <div class="p-2 rounded bg-light text-info">
                        <i class="fas fa-sign-in-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card border-0 shadow-sm h-100 overflow-hidden stats-card">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                     <div>
                        @php
                            $tiempoTotal = $usuarios->sum('tiempo_total');
                            $horas = floor($tiempoTotal / 3600);
                            $minutos = floor(($tiempoTotal % 3600) / 60);
                        @endphp
                        <h3 class="mb-0 font-weight-bolder text-dark">{{ $horas }}h {{ $minutos }}m</h3>
                         <span class="text-muted text-uppercase small font-weight-bold">Tiempo Total</span>
                    </div>
                    <div class="p-2 rounded bg-light text-success">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card border-0 shadow-sm h-100 overflow-hidden stats-card">
             <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                     <div>
                        <h3 class="mb-0 font-weight-bolder text-dark">{{ $usuarios->where('total_sesiones', '>', 0)->count() }}</h3>
                        <span class="text-muted text-uppercase small font-weight-bold">Usuarios Activos</span>
                    </div>
                    <div class="p-2 rounded bg-light text-warning">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
         <div class="card border-0 shadow-sm h-100 overflow-hidden stats-card">
             <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                     <div>
                        @php
                            $usuariosConSesiones = $usuarios->where('total_sesiones', '>', 0);
                            $promedioGeneral = $usuariosConSesiones->count() > 0 
                                ? $usuariosConSesiones->avg('tiempo_promedio') 
                                : 0;
                            $horasPromedio = floor($promedioGeneral / 3600);
                            $minutosPromedio = floor(($promedioGeneral % 3600) / 60);
                        @endphp
                        <h3 class="mb-0 font-weight-bolder text-dark">{{ $horasPromedio }}h {{ $minutosPromedio }}m</h3>
                        <span class="text-muted text-uppercase small font-weight-bold">Promedio/Sesión</span>
                    </div>
                    <div class="p-2 rounded bg-light text-danger">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-light">
                <h3 class="card-title text-muted"><i class="fas fa-filter mr-2"></i>Filtros de Búsqueda</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.auditoria.reporte-sesiones') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="fecha_inicio" class="text-muted small text-uppercase font-weight-bold">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="fecha_fin" class="text-muted small text-uppercase font-weight-bold">Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                        </div>
                        <div class="col-md-6">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <button type="submit" class="btn btn-primary mr-2 flex-grow-1">
                                    <i class="fas fa-filter mr-1"></i> Filtrar
                                </button>
                                <a href="{{ route('admin.auditoria.reporte-sesiones') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Contenido Principal con Tabs -->
<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline card-outline-tabs shadow border-0">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" id="report-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="listado-tab" data-toggle="pill" href="#listado" role="tab" aria-controls="listado" aria-selected="true">
                            <i class="fas fa-list mr-2"></i>Listado Detallado
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="graficos-tab" data-toggle="pill" href="#graficos" role="tab" aria-controls="graficos" aria-selected="false">
                            <i class="fas fa-chart-pie mr-2"></i>Gráficos y Métricas
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="report-tabs-content">
                    
                    <!-- Tab Listado -->
                    <div class="tab-pane fade show active" id="listado" role="tabpanel" aria-labelledby="listado-tab">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-top-0 border-bottom-0 pl-3">Usuario</th>
                                        <th class="border-top-0 border-bottom-0">Sesiones</th>
                                        <th class="border-top-0 border-bottom-0">Tiempo Total</th>
                                        <th class="border-top-0 border-bottom-0">Promedio</th>
                                        <th class="border-top-0 border-bottom-0">Última Actividad</th>
                                        <th class="border-top-0 border-bottom-0 text-right pr-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($usuarios->sortByDesc('total_sesiones') as $usuario)
                                        <tr>
                                            <td class="pl-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-light rounded-circle p-2 mr-2 text-primary">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <strong class="d-block text-dark">{{ $usuario->codigo }}</strong>
                                                        <small class="text-muted">{{ $usuario->email }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-light border text-dark">{{ $usuario->total_sesiones }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $horas = floor($usuario->tiempo_total / 3600);
                                                    $minutos = floor(($usuario->tiempo_total % 3600) / 60);
                                                @endphp
                                                @if($usuario->tiempo_total > 0)
                                                    <span class="text-dark font-weight-bold">{{ $horas }}h {{ $minutos }}m</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $horasPromedio = floor($usuario->tiempo_promedio / 3600);
                                                    $minutosPromedio = floor(($usuario->tiempo_promedio % 3600) / 60);
                                                @endphp
                                                @if($usuario->tiempo_promedio > 0)
                                                    <span class="text-muted small">{{ $horasPromedio }}h {{ $minutosPromedio }}m</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($usuario->ultima_sesion)
                                                    <span class="d-block">{{ $usuario->ultima_sesion->login_time->format('d/m/Y') }}</span>
                                                    <small class="text-muted">{{ $usuario->ultima_sesion->login_time->format('H:i') }}</small>
                                                    @if($usuario->ultima_sesion->isActive())
                                                        <span class="badge badge-success ml-1" style="font-size: 0.6em">ONLINE</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted font-italic">Sin registro</span>
                                                @endif
                                            </td>
                                            <td class="text-right pr-3">
                                                <a href="{{ route('admin.auditoria.sesiones-usuario', $usuario->id) }}" 
                                                   class="btn btn-sm btn-outline-primary shadow-sm" title="Ver historial completo">
                                                    <i class="fas fa-history mr-1"></i>
                                                </a>
                                                <a href="{{ route('admin.auditoria.tiempo-modulos', $usuario->id) }}" 
                                                   class="btn btn-sm btn-outline-info shadow-sm ml-1" title="Análisis de tiempo">
                                                    <i class="fas fa-chart-bar"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Gráficos -->
                    <div class="tab-pane fade" id="graficos" role="tabpanel" aria-labelledby="graficos-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card shadow-none border">
                                    <div class="card-header bg-light">
                                        <h3 class="card-title font-weight-bold text-dark">Top 10 Usuarios (Más Sesiones)</h3>
                                    </div>
                                    <div class="card-body">
                                        <div style="height: 300px;">
                                            <canvas id="topUsersChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card shadow-none border">
                                    <div class="card-header bg-light">
                                        <h3 class="card-title font-weight-bold text-dark">Distribución de Tiempo Total</h3>
                                    </div>
                                    <div class="card-body">
                                        <div style="height: 300px;">
                                            <canvas id="timeDistributionChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Configuración común
Chart.defaults.font.family = "'Source Sans Pro', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
Chart.defaults.color = '#666';

// Top usuarios más activos
const topUsers = {!! json_encode($usuarios->sortByDesc('total_sesiones')->take(10)->pluck('codigo')) !!};
const topUsersSessions = {!! json_encode($usuarios->sortByDesc('total_sesiones')->take(10)->pluck('total_sesiones')) !!};

const ctxTop = document.getElementById('topUsersChart').getContext('2d');
new Chart(ctxTop, {
    type: 'bar',
    data: {
        labels: topUsers,
        datasets: [{
            label: 'Total Sesiones',
            data: topUsersSessions,
            backgroundColor: 'rgba(60, 141, 188, 0.7)',
            borderColor: 'rgba(60, 141, 188, 1)',
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
            x: { grid: { display: false } }
        }
    }
});

// Distribución de tiempo
const timeUsers = {!! json_encode($usuarios->where('tiempo_total', '>', 0)->take(10)->pluck('codigo')) !!};
const timeData = {!! json_encode($usuarios->where('tiempo_total', '>', 0)->take(10)->pluck('tiempo_total')->map(function($time) { return round($time / 3600, 2); })) !!};

const ctxTime = document.getElementById('timeDistributionChart').getContext('2d');
new Chart(ctxTime, {
    type: 'doughnut',
    data: {
        labels: timeUsers,
        datasets: [{
            data: timeData,
            backgroundColor: [
                '#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc',
                '#d2d6de', '#605ca8', '#ff851b', '#39cccc', '#D81B60'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right' }
        },
        cutout: '60%'
    }
});
</script>
@stop

@section('css')
<style>
    .nav-tabs .nav-link {
        border-radius: 0.25rem 0.25rem 0 0;
        color: #6c757d;
        font-weight: 600;
    }
    .nav-tabs .nav-link.active {
        color: #495057;
        font-weight: 700;
        border-top: 3px solid #007bff;
    }
    .table th {
        border-top: none;
        font-size: 0.85rem;
        text-transform: uppercase;
        color: #6c757d;
        letter-spacing: 0.5px;
    }
</style>
@stop