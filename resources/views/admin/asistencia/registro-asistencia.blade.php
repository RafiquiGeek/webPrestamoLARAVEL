@extends('layouts.admin')
@section('title', 'Registro de Asistencia')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-fingerprint mr-2"></i>Registro de Asistencia</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.index') }}">Asistencia</a></li>
            <li class="breadcrumb-item active">Registro</li>
        </ol>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Información del empleado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-clock mr-2"></i>Información del Empleado
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
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
                        <div class="col-md-6">
                            <table class="table table-borderless">
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
        </div>
    </div>

    <!-- Reloj y marcado -->
    <div class="row">
        <!-- Reloj Digital -->
        <div class="col-lg-6">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clock mr-2"></i>Hora Actual
                    </h3>
                </div>
                <div class="card-body text-center">
                    <div id="reloj-digital" class="display-4 font-weight-bold text-primary mb-3">
                        --:--:--
                    </div>
                    <div id="fecha-actual" class="h5 text-muted">
                        {{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de marcado -->
        <div class="col-lg-6">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-hand-point-up mr-2"></i>Marcado de Asistencia
                    </h3>
                </div>
                <div class="card-body text-center">
                    <div class="row">
                        <div class="col-6">
                            <button id="btn-marcar-entrada" 
                                    class="btn btn-success btn-lg btn-block mb-3"
                                    {{ $registroHoy && $registroHoy->hora_entrada ? 'disabled' : '' }}>
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                <br>Marcar Entrada
                            </button>
                            @if($registroHoy && $registroHoy->hora_entrada)
                                <small class="text-success">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Entrada: {{ \Carbon\Carbon::parse($registroHoy->hora_entrada)->format('H:i:s') }}
                                </small>
                            @endif
                        </div>
                        <div class="col-6">
                            <button id="btn-marcar-salida" 
                                    class="btn btn-danger btn-lg btn-block mb-3"
                                    {{ !$registroHoy || !$registroHoy->hora_entrada || $registroHoy->hora_salida ? 'disabled' : '' }}>
                                <i class="fas fa-sign-out-alt mr-2"></i>
                                <br>Marcar Salida
                            </button>
                            @if($registroHoy && $registroHoy->hora_salida)
                                <small class="text-danger">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Salida: {{ \Carbon\Carbon::parse($registroHoy->hora_salida)->format('H:i:s') }}
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estado del día -->
    @if($registroHoy)
    <div class="row">
        <div class="col-12">
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-day mr-2"></i>Estado del Día
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-{{ $registroHoy->estado_entrada_color }}">
                                    <i class="fas fa-sign-in-alt"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Estado Entrada</span>
                                    <span class="info-box-number">{{ ucfirst($registroHoy->estado_entrada) }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-{{ $registroHoy->estado_salida_color }}">
                                    <i class="fas fa-sign-out-alt"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Estado Salida</span>
                                    <span class="info-box-number">{{ ucfirst($registroHoy->estado_salida) }}</span>
                                </div>
                            </div>
                        </div>

                        @if($registroHoy->minutos_tardanza > 0)
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-clock"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tardanza</span>
                                    <span class="info-box-number">{{ $registroHoy->minutos_tardanza }} min</span>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($registroHoy->tieneAsistenciaCompleta())
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-hourglass-half"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Horas Trabajadas</span>
                                    <span class="info-box-number">{{ $registroHoy->calcularHorasTrabajadas() }}h</span>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modal de confirmación -->
<div class="modal fade" id="modalConfirmacion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Confirmación de Marcado</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="mensaje-confirmacion"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
#reloj-digital {
    font-family: 'Courier New', monospace;
    font-size: 3.5rem;
    letter-spacing: 2px;
}

.btn-lg {
    padding: 15px 25px;
    font-size: 1.1rem;
}

.info-box {
    margin-bottom: 15px;
}

.card {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 10px;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
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
        
        $('#reloj-digital').text(`${horas}:${minutos}:${segundos}`);
    }
    
    actualizarReloj();
    setInterval(actualizarReloj, 1000);

    // Función para obtener ubicación
    function obtenerUbicacion() {
        return new Promise((resolve, reject) => {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    position => resolve({
                        latitud: position.coords.latitude,
                        longitud: position.coords.longitude
                    }),
                    error => {
                        console.warn('No se pudo obtener la ubicación:', error);
                        resolve({ latitud: null, longitud: null });
                    }
                );
            } else {
                resolve({ latitud: null, longitud: null });
            }
        });
    }

    // Marcar entrada
    $('#btn-marcar-entrada').click(async function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Marcando...');
        
        try {
            const ubicacion = await obtenerUbicacion();
            
            const response = await fetch('{{ route("admin.asistencia.marcar-entrada") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(ubicacion)
            });
            
            const data = await response.json();
            
            if (data.success) {
                $('#mensaje-confirmacion').html(`
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i>
                        <strong>¡Entrada registrada exitosamente!</strong><br>
                        Hora: ${data.hora}<br>
                        Estado: ${data.estado}
                    </div>
                `);
                
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                $('#mensaje-confirmacion').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        ${data.error}
                    </div>
                `);
            }
            
            $('#modalConfirmacion').modal('show');
            
        } catch (error) {
            console.error('Error:', error);
            $('#mensaje-confirmacion').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Error al registrar la entrada.
                </div>
            `);
            $('#modalConfirmacion').modal('show');
        }
        
        btn.prop('disabled', false).html('<i class="fas fa-sign-in-alt mr-2"></i><br>Marcar Entrada');
    });

    // Marcar salida
    $('#btn-marcar-salida').click(async function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Marcando...');
        
        try {
            const ubicacion = await obtenerUbicacion();
            
            const response = await fetch('{{ route("admin.asistencia.marcar-salida") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(ubicacion)
            });
            
            const data = await response.json();
            
            if (data.success) {
                $('#mensaje-confirmacion').html(`
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i>
                        <strong>¡Salida registrada exitosamente!</strong><br>
                        Hora: ${data.hora}<br>
                        Estado: ${data.estado}
                    </div>
                `);
                
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                $('#mensaje-confirmacion').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        ${data.error}
                    </div>
                `);
            }
            
            $('#modalConfirmacion').modal('show');
            
        } catch (error) {
            console.error('Error:', error);
            $('#mensaje-confirmacion').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Error al registrar la salida.
                </div>
            `);
            $('#modalConfirmacion').modal('show');
        }
        
        btn.prop('disabled', false).html('<i class="fas fa-sign-out-alt mr-2"></i><br>Marcar Salida');
    });
});
</script>
@stop