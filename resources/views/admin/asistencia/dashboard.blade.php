@extends('layouts.admin')
@section('title', 'Dashboard de Asistencia')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-chart-line mr-2"></i>Dashboard de Asistencia</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.index') }}">Asistencia</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Estadísticas del día -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalEmpleados }}</h3>
                    <p>Total Empleados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $presentesHoy }}</h3>
                    <p>Presentes Hoy</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $tardanzasHoy }}</h3>
                    <p>Tardanzas</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $faltasHoy }}</h3>
                    <p>Faltas</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-times"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Últimos registros -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list mr-2"></i>Últimos Registros de Hoy
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-primary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($ultimosRegistros->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Área</th>
                                        <th>Entrada</th>
                                        <th>Estado</th>
                                        <th>Salida</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($ultimosRegistros as $registro)
                                        <tr>
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
                                                <span class="badge badge-{{ $registro->estado_entrada_color }}">
                                                    {{ ucfirst($registro->estado_entrada) }}
                                                </span>
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
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay registros de asistencia hoy</h5>
                            <p class="text-muted">Los registros aparecerán cuando los empleados marquen su asistencia.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Información adicional -->
        <div class="col-md-4">
            <!-- Reloj -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clock mr-2"></i>Hora Actual
                    </h3>
                </div>
                <div class="card-body text-center">
                    <div id="reloj-dashboard" class="display-4 font-weight-bold text-primary">
                        --:--:--
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">{{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</small>
                    </div>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bolt mr-2"></i>Acciones Rápidas
                    </h3>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.asistencia.registro') }}" class="btn btn-success btn-block">
                            <i class="fas fa-fingerprint mr-1"></i>Mi Asistencia
                        </a>
                        <a href="{{ route('admin.asistencia.reportes') }}" class="btn btn-info btn-block">
                            <i class="fas fa-file-alt mr-1"></i>Ver Reportes
                        </a>
                        @can('admin.usuarios.index')
                        <a href="{{ route('admin.asistencia.asignaciones') }}" class="btn btn-warning btn-block">
                            <i class="fas fa-user-tie mr-1"></i>Gestionar Asignaciones
                        </a>
                        @endcan
                    </div>
                </div>
            </div>

            <!-- Resumen porcentual -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-pie mr-2"></i>Resumen del Día
                    </h3>
                </div>
                <div class="card-body">
                    @php
                        $porcentajeAsistencia = $totalEmpleados > 0 ? round(($presentesHoy / $totalEmpleados) * 100, 1) : 0;
                        $porcentajeTardanzas = $presentesHoy > 0 ? round(($tardanzasHoy / $presentesHoy) * 100, 1) : 0;
                    @endphp
                    
                    <div class="progress-group">
                        <span class="progress-text">Asistencia General</span>
                        <span class="float-right"><b>{{ $presentesHoy }}</b>/{{ $totalEmpleados }}</span>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-success" style="width: {{ $porcentajeAsistencia }}%"></div>
                        </div>
                        <small class="text-muted">{{ $porcentajeAsistencia }}% de empleados presentes</small>
                    </div>

                    @if($presentesHoy > 0)
                    <div class="progress-group mt-3">
                        <span class="progress-text">Tardanzas</span>
                        <span class="float-right"><b>{{ $tardanzasHoy }}</b>/{{ $presentesHoy }}</span>
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-warning" style="width: {{ $porcentajeTardanzas }}%"></div>
                        </div>
                        <small class="text-muted">{{ $porcentajeTardanzas }}% de empleados con tardanza</small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
#reloj-dashboard {
    font-family: 'Courier New', monospace;
    font-size: 2.5rem;
    letter-spacing: 2px;
}

.small-box {
    border-radius: 10px;
}

.card {
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.progress-group {
    margin-bottom: 20px;
}

.btn-block {
    margin-bottom: 10px;
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
}
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Actualizar reloj cada segundo
    function actualizarReloj() {
        const ahora = new Date();
        const horas = ahora.getHours().toString().padStart(2, '0');
        const minutos = ahora.getMinutes().toString().padStart(2, '0');
        const segundos = ahora.getSeconds().toString().padStart(2, '0');
        
        $('#reloj-dashboard').text(`${horas}:${minutos}:${segundos}`);
    }
    
    actualizarReloj();
    setInterval(actualizarReloj, 1000);

    // Auto-actualizar la página cada 5 minutos
    setTimeout(() => {
        location.reload();
    }, 300000); // 5 minutos
});
</script>
@stop