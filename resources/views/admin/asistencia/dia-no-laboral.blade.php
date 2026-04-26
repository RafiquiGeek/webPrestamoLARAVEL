@extends('layouts.admin')
@section('title', 'Día No Laboral')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-calendar-times mr-2"></i>Día No Laboral</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.index') }}">Asistencia</a></li>
            <li class="breadcrumb-item active">Día No Laboral</li>
        </ol>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle mr-2"></i>Información del Día
                    </h3>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-bed fa-5x text-info mb-3"></i>
                        <h3>Hoy es día de descanso</h3>
                    </div>
                    
                    <div class="alert alert-info" role="alert">
                        <h5><i class="fas fa-calendar-check mr-2"></i>¡Disfruta tu día libre!</h5>
                        <p class="mb-0">
                            Según tu horario de trabajo, hoy no es un día laboral. 
                            No necesitas registrar asistencia.
                        </p>
                    </div>

                    <!-- Información del empleado y horario -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6><i class="fas fa-user-tie mr-2"></i>Tu Información</h6>
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td><strong>Empleado:</strong></td>
                                            <td>{{ auth()->user()->name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Código:</strong></td>
                                            <td>{{ auth()->user()->codigo }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Área:</strong></td>
                                            <td>
                                                <span class="badge" style="background-color: {{ $asignacionActiva->areaLaboral->color }}; color: white;">
                                                    {{ $asignacionActiva->areaLaboral->nombre }}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6><i class="fas fa-clock mr-2"></i>Tu Horario</h6>
                                    <table class="table table-borderless table-sm">
                                        <tr>
                                            <td><strong>Horario:</strong></td>
                                            <td>{{ $asignacionActiva->horarioTrabajo->nombre }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Entrada:</strong></td>
                                            <td>{{ \Carbon\Carbon::parse($asignacionActiva->horarioTrabajo->hora_entrada)->format('H:i') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Salida:</strong></td>
                                            <td>{{ \Carbon\Carbon::parse($asignacionActiva->horarioTrabajo->hora_salida)->format('H:i') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Días laborales -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6><i class="fas fa-calendar-week mr-2"></i>Tus Días Laborales</h6>
                                    <p class="mb-2"><strong>{{ $asignacionActiva->horarioTrabajo->dias_laborales_text }}</strong></p>
                                    
                                    <div class="row text-center mt-3">
                                        @php
                                            $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                                            $hoy = \Carbon\Carbon::now()->dayOfWeek;
                                            $diasLaborales = $asignacionActiva->horarioTrabajo->dias_laborales ?? [];
                                        @endphp
                                        @foreach($diasSemana as $index => $dia)
                                            <div class="col">
                                                <div class="badge badge-{{ in_array($index, $diasLaborales) ? 'success' : 'secondary' }} {{ $index == $hoy ? 'badge-lg' : '' }}">
                                                    {{ substr($dia, 0, 3) }}
                                                    @if($index == $hoy)
                                                        <br><small>HOY</small>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fecha y hora actual -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-calendar-day"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Fecha Actual</span>
                                    <span class="info-box-number">{{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd') }}</span>
                                    <small>{{ \Carbon\Carbon::now()->format('d/m/Y') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-clock"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Hora Actual</span>
                                    <span class="info-box-number" id="hora-actual">{{ \Carbon\Carbon::now()->format('H:i:s') }}</span>
                                    <small>Tiempo real</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Próximo día laboral -->
                    <div class="mt-4">
                        @php
                            $proximoDiaLaboral = null;
                            $diasLaborales = $asignacionActiva->horarioTrabajo->dias_laborales ?? [];
                            
                            for ($i = 1; $i <= 7; $i++) {
                                $siguienteDia = \Carbon\Carbon::now()->addDays($i);
                                if (in_array($siguienteDia->dayOfWeek, $diasLaborales)) {
                                    $proximoDiaLaboral = $siguienteDia;
                                    break;
                                }
                            }
                        @endphp
                        
                        @if($proximoDiaLaboral)
                            <div class="alert alert-success" role="alert">
                                <h6><i class="fas fa-arrow-right mr-2"></i>Próximo Día Laboral</h6>
                                <p class="mb-0">
                                    <strong>{{ $proximoDiaLaboral->locale('es')->isoFormat('dddd, D [de] MMMM') }}</strong>
                                    <br>
                                    <small>Recuerda llegar a las {{ \Carbon\Carbon::parse($asignacionActiva->horarioTrabajo->hora_entrada)->format('H:i') }}</small>
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="mt-4">
                        <div class="btn-group" role="group">
                            <a href="{{ route('admin.asistencia.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i>Volver al Módulo
                            </a>
                            <a href="{{ route('admin.index') }}" class="btn btn-primary">
                                <i class="fas fa-home mr-1"></i>Ir al Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
.info-box {
    margin-bottom: 15px;
}

.card {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 10px;
}

.btn-group .btn {
    margin: 0 2px;
}

.fa-5x {
    font-size: 5em;
}

.badge-lg {
    font-size: 1rem;
    padding: 0.5rem 0.75rem;
}

.table-borderless td {
    border: none !important;
    padding: 0.25rem 0.5rem;
}
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Actualizar hora cada segundo
    function actualizarHora() {
        const ahora = new Date();
        const horas = ahora.getHours().toString().padStart(2, '0');
        const minutos = ahora.getMinutes().toString().padStart(2, '0');
        const segundos = ahora.getSeconds().toString().padStart(2, '0');
        
        $('#hora-actual').text(`${horas}:${minutos}:${segundos}`);
    }
    
    actualizarHora();
    setInterval(actualizarHora, 1000);
});
</script>
@stop