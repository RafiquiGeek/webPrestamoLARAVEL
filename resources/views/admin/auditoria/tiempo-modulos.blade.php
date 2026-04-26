@extends('layouts.admin')

@section('title', 'Tiempo por Módulos - ' . $user->name)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>
                <i class="fas fa-chart-bar me-2"></i>
                Tiempo por Módulos - {{ $user->name }}
            </h1>
            <p class="mb-0 text-muted">Análisis de tiempo de permanencia por módulo del sistema</p>
        </div>
        <div>
            <a href="{{ route('admin.auditoria.sesiones-usuario', $user->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Sesiones
            </a>
        </div>
    </div>
@stop

@section('content')
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
                <form method="GET" action="{{ route('admin.auditoria.tiempo-modulos', $user->id) }}">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="fecha_inicio">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="fecha_fin">Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                        </div>
                        <div class="col-md-6">
                            <label>&nbsp;</label>
                            <div class="input-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filtrar
                                </button>
                                <a href="{{ route('admin.auditoria.tiempo-modulos', $user->id) }}" class="btn btn-secondary ml-2">
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
                @php
                    $tiempoTotal = $tiemposPorModulo->sum('total_duration');
                    $horas = floor($tiempoTotal / 3600);
                    $minutos = floor(($tiempoTotal % 3600) / 60);
                @endphp
                <h3>{{ $horas }}h {{ $minutos }}m</h3>
                <p>Tiempo Total</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $tiemposPorModulo->count() }}</h3>
                <p>Módulos Visitados</p>
            </div>
            <div class="icon">
                <i class="fas fa-th"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $tiemposPorDia->count() }}</h3>
                <p>Días Activos</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                @php
                    $promedioTiempo = $tiemposPorDia->count() > 0 ? $tiempoTotal / $tiemposPorDia->count() : 0;
                    $horasPromedio = floor($promedioTiempo / 3600);
                    $minutosPromedio = floor(($promedioTiempo % 3600) / 60);
                @endphp
                <h3>{{ $horasPromedio }}h {{ $minutosPromedio }}m</h3>
                <p>Promedio Diario</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tiempo por módulos -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tiempo por Módulos</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>Módulo</th>
                            <th>Tiempo Total</th>
                            <th>Porcentaje</th>
                            <th>Progreso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tiemposPorModulo as $tiempo)
                            @php
                                $horas = floor($tiempo->total_duration / 3600);
                                $minutos = floor(($tiempo->total_duration % 3600) / 60);
                                $segundos = $tiempo->total_duration % 60;
                                $porcentaje = $tiempoTotal > 0 ? ($tiempo->total_duration / $tiempoTotal) * 100 : 0;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $tiempo->module_name }}</strong>
                                </td>
                                <td>
                                    @if($horas > 0)
                                        {{ $horas }}h {{ $minutos }}m {{ $segundos }}s
                                    @else
                                        {{ $minutos }}m {{ $segundos }}s
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-primary">{{ number_format($porcentaje, 1) }}%</span>
                                </td>
                                <td>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-primary" style="width: {{ $porcentaje }}%"></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        
                        @if($tiemposPorModulo->isEmpty())
                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    No se encontraron datos de tiempo por módulos
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Gráfico circular -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Distribución de Tiempo</h3>
            </div>
            <div class="card-body">
                @if($tiemposPorModulo->count() > 0)
                    <canvas id="moduleDistributionChart" width="300" height="300"></canvas>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-chart-pie fa-3x mb-3"></i>
                        <p>No hay datos para mostrar</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Tiempo por días -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tiempo por Días</h3>
            </div>
            <div class="card-body">
                @if($tiemposPorDia->count() > 0)
                    <div style="position: relative; height: 300px;">
                        <canvas id="dailyTimeChart"></canvas>
                    </div>
                    
                    <div class="table-responsive mt-3">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tiempo Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tiemposPorDia as $dia)
                                    @php
                                        $horas = floor($dia->total_duration / 3600);
                                        $minutos = floor(($dia->total_duration % 3600) / 60);
                                    @endphp
                                    <tr>
                                        <td>{{ Carbon\Carbon::parse($dia->date)->format('d/m/Y') }}</td>
                                        <td>{{ $horas }}h {{ $minutos }}m</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-calendar fa-3x mb-3"></i>
                        <p>No hay datos de tiempo por días</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
@if($tiemposPorModulo->count() > 0)
// Gráfico de distribución por módulos
const moduleLabels = {!! json_encode($tiemposPorModulo->pluck('module_name')) !!};
const moduleData = {!! json_encode($tiemposPorModulo->pluck('total_duration')->map(function($time) { return round($time / 60, 2); })) !!};

const ctxModule = document.getElementById('moduleDistributionChart').getContext('2d');
new Chart(ctxModule, {
    type: 'doughnut',
    data: {
        labels: moduleLabels,
        datasets: [{
            data: moduleData,
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    generateLabels: function(chart) {
                        const data = chart.data;
                        if (data.labels.length && data.datasets.length) {
                            return data.labels.map((label, i) => {
                                const value = data.datasets[0].data[i];
                                return {
                                    text: `${label}: ${value}m`,
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                };
                            });
                        }
                        return [];
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.parsed + ' minutos';
                    }
                }
            }
        }
    }
});
@endif

@if($tiemposPorDia->count() > 0)
// Gráfico de tiempo diario
const dailyLabels = {!! json_encode($tiemposPorDia->pluck('date')->map(function($date) { return Carbon\Carbon::parse($date)->format('d/m'); })) !!};
const dailyData = {!! json_encode($tiemposPorDia->pluck('total_duration')->map(function($time) { return round($time / 3600, 2); })) !!};

const ctxDaily = document.getElementById('dailyTimeChart').getContext('2d');
new Chart(ctxDaily, {
    type: 'line',
    data: {
        labels: dailyLabels,
        datasets: [{
            label: 'Horas por día',
            data: dailyData,
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false,
            mode: 'index'
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Horas'
                },
                ticks: {
                    maxTicksLimit: 8
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Fecha'
                },
                ticks: {
                    maxTicksLimit: 10
                }
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Tiempo: ' + context.parsed.y + ' horas';
                    }
                }
            }
        },
        elements: {
            point: {
                radius: 4,
                hoverRadius: 6
            }
        }
    }
});
@endif
</script>
@stop

@section('css')
<style>
    .table th {
        border-top: none;
    }
    .progress {
        height: 10px;
    }
    .badge {
        font-size: 0.75em;
    }
    .table-sm td, .table-sm th {
        padding: 0.3rem;
    }
</style>
@stop