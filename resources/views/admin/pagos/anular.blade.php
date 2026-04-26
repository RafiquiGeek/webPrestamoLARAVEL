@extends('layouts.admin')

@section('title', 'Anular Operación')

@section('content_header')
    <h1>
        <i class="fas fa-ban mr-2 text-danger"></i>
        Anular {{ $operacion->tipo_operacion === 'Desembolso' ? 'Desembolso' : 'Pago' }}
        <small class="ml-3">
            <span class="badge badge-primary">ID: {{ $operacion->id }}</span>
            <span class="badge badge-danger">ANULACIÓN</span>
        </small>
    </h1>
    <div class="breadcrumb">
        <a href="{{ route('admin.operaciones.index') }}">Operaciones</a> / Anular
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Alerta de Advertencia -->
            <div class="alert alert-danger">
                <h5><i class="icon fas fa-ban"></i> ¡ATENCIÓN!</h5>
                Estás a punto de anular {{ $operacion->tipo_operacion === 'Desembolso' ? 'un desembolso' : 'un pago' }} registrado. Esta acción:
                <ul class="mb-0 mt-2">
                    <li>Marcará {{ $operacion->tipo_operacion === 'Desembolso' ? 'el desembolso' : 'el pago' }} como anulado en el sistema</li>
                    @if($operacion->tipo_operacion === 'Desembolso')
                        <li><strong>Cambiará el estado del préstamo de "Desembolsado" a "Por Desembolsar"</strong></li>
                        <li>El préstamo quedará disponible para ser desembolsado nuevamente</li>
                    @else
                        <li>Recalculará automáticamente el estado de las cuotas afectadas</li>
                    @endif
                    <li>Generará un registro permanente en el historial de auditoría</li>
                    <li><strong>No podrá ser revertida una vez confirmada</strong></li>
                </ul>
            </div>

            <div class="row">
                <!-- Formulario de Anulación -->
                <div class="col-md-8">
                    <form action="{{ route('admin.pagos.anular', $operacion->id) }}" method="POST" id="anularForm">
                        @csrf
                        @method('DELETE')

                        <!-- Información del Pago a Anular -->
                        <div class="card card-outline card-danger mb-4">
                            <div class="card-header text-black">
                                <h3 class="card-title">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Información {{ $operacion->tipo_operacion === 'Desembolso' ? 'del Desembolso' : 'del Pago' }} a Anular
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-muted">Cliente</h6>
                                        <p class="h6">
                                            <i class="fas fa-user mr-2"></i>
                                            {{ $operacion->cliente->persona->nombres }} {{ $operacion->cliente->persona->apellidos }}
                                            <br><small class="text-muted">DNI: {{ $operacion->cliente->persona->documento }}</small>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-muted">Préstamo</h6>
                                        <p class="h6">
                                            <i class="fas fa-file-contract mr-2"></i>
                                            Préstamo #{{ $operacion->prestamo->id }}
                                            <br><small class="text-muted">S/ {{ number_format($operacion->prestamo->cantidad_solicitada, 2) }}</small>
                                        </p>
                                    </div>
                                </div>

                                <hr>

                                <div class="row">
                                    <div class="col-md-3">
                                        <h6 class="text-muted">Monto</h6>
                                        <p class="h5 text-danger">S/ {{ number_format($operacion->abono, 2) }}</p>
                                    </div>
                                    <div class="col-md-3">
                                        <h6 class="text-muted">Fecha</h6>
                                        <p>{{ $operacion->fecha->format('d/m/Y') }}</p>
                                    </div>
                                    <div class="col-md-3">
                                        <h6 class="text-muted">Método</h6>
                                        <p>{{ $operacion->metodoDePago->nombre ?? 'No especificado' }}</p>
                                    </div>
                                    <div class="col-md-3">
                                        <h6 class="text-muted">Registrado por</h6>
                                        <p>{{ $operacion->user->name ?? 'Sistema' }}</p>
                                    </div>
                                </div>

                                @if($operacion->nro_operacion)
                                <div class="row">
                                    <div class="col-md-12">
                                        <h6 class="text-muted">Número de Operación</h6>
                                        <p>{{ $operacion->nro_operacion }}</p>
                                    </div>
                                </div>
                                @endif

                                @if($operacion->comentario)
                                <div class="row">
                                    <div class="col-md-12">
                                        <h6 class="text-muted">Comentarios Originales</h6>
                                        <div class="bg-light p-2 rounded">{{ $operacion->comentario }}</div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Justificación de Anulación -->
                        <div class="card card-outline card-warning mb-4">
                            <div class="card-header text-black">
                                <h3 class="card-title">
                                    <i class="fas fa-edit mr-2"></i>
                                    Justificación de Anulación
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <h6><i class="icon fas fa-exclamation-triangle"></i> ¡Obligatorio!</h6>
                                    Debes proporcionar una justificación detallada y válida para anular este pago. 
                                    Esta información será permanente y visible en todas las auditorías.
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Motivo de Anulación <span class="text-danger">*</span></label>
                                        <select class="form-control" name="motivo_anulacion" required>
                                            <option value="">Selecciona un motivo</option>
                                            <option value="Error en el monto">Error en el monto registrado</option>
                                            <option value="Error en la fecha">Error en la fecha de pago</option>
                                            <option value="Pago duplicado">Pago duplicado</option>
                                            <option value="Cliente no realizó el pago">Cliente no realizó el pago</option>
                                            <option value="Error en método de pago">Error en método de pago</option>
                                            <option value="Solicitud del cliente">Solicitud expresa del cliente</option>
                                            <option value="Error administrativo">Error administrativo</option>
                                            <option value="Otro">Otro motivo</option>
                                        </select>
                                        <div class="invalid-feedback">Por favor selecciona un motivo válido.</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label">Justificación Detallada <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="justificacion_anulacion" rows="6" 
                                                  placeholder="Describe detalladamente por qué estás anulando este pago. Incluye toda la información relevante que justifique esta decisión..." required>{{ old('justificacion_anulacion') }}</textarea>
                                        <div class="invalid-feedback">La justificación detallada es obligatoria para anular un pago.</div>
                                        <small class="form-text text-muted">Mínimo 20 caracteres. Sé específico y claro en tu explicación.</small>
                                    </div>
                                </div>

                                <!-- Confirmación adicional -->
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="confirmarAnulacion" required>
                                            <label class="custom-control-label text-danger font-weight-bold" for="confirmarAnulacion">
                                                Confirmo que he verificado toda la información y entiendo que esta acción no puede ser revertida
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('admin.operaciones.index') }}" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-arrow-left mr-2"></i>
                                        Cancelar y Volver
                                    </a>
                                    <button type="submit" class="btn btn-danger btn-lg" id="btnAnular" disabled>
                                        <i class="fas fa-ban mr-2"></i>
                                        ANULAR {{ strtoupper($operacion->tipo_operacion === 'Desembolso' ? 'DESEMBOLSO' : 'PAGO') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Panel de Impacto -->
                <div class="col-md-4">
                    <!-- Impacto en Cuotas -->
                    <div class="card border-warning mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-calculator mr-2"></i>
                                Impacto en Cuotas
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-sm">Al anular este pago, las siguientes cuotas pueden verse afectadas:</p>
                            
                            @php
                                $cuotasAfectadas = $operacion->cuotas()->where('estado', '!=', 0)->get();
                            @endphp
                            
                            @if($cuotasAfectadas->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Cuota</th>
                                                <th>Estado Actual</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($cuotasAfectadas as $cuota)
                                            <tr>
                                                <td>{{ $cuota->numero_cuota }}</td>
                                                <td>
                                                    @switch($cuota->estado)
                                                        @case(1)
                                                            <span class="badge badge-warning">Parcial</span>
                                                            @break
                                                        @case(2)
                                                            <span class="badge badge-success">Pagado</span>
                                                            @break
                                                        @default
                                                            <span class="badge badge-secondary">Pendiente</span>
                                                    @endswitch
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-muted">Estas cuotas serán recalculadas automáticamente.</small>
                            @else
                                <p class="text-muted text-sm">No se han identificado cuotas directamente afectadas.</p>
                            @endif
                        </div>
                    </div>

                    <!-- Información de Auditoría -->
                    <div class="card border-info">
                        <div class="card-header text-black">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-shield-alt mr-2"></i>
                                Registro de Auditoría
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th>Fecha actual:</th>
                                    <td>{{ now()->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Usuario:</th>
                                    <td>{{ auth()->user()->name }}</td>
                                </tr>
                                <tr>
                                    <th>Acción:</th>
                                    <td><span class="badge badge-danger">ANULACIÓN</span></td>
                                </tr>
                                <tr>
                                    <th>IP:</th>
                                    <td>{{ request()->ip() }}</td>
                                </tr>
                            </table>
                            <small class="text-muted">
                                Esta información será registrada permanentemente en los logs del sistema.
                            </small>
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
    .form-label {
        font-weight: 600;
        color: #495057;
    }
    
    .card-header {
        border-bottom: 1px solid #dee2e6;
    }
    
    .text-danger {
        color: #dc3545 !important;
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Habilitar botón solo cuando se confirme
    $('#confirmarAnulacion').on('change', function() {
        $('#btnAnular').prop('disabled', !$(this).is(':checked'));
    });

    // Validación de formulario
    $('#anularForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        // Verificar justificación mínima
        const justificacion = $('textarea[name="justificacion_anulacion"]').val().trim();
        if (justificacion.length < 20) {
            alert('La justificación debe tener al menos 20 caracteres para una anulación.');
            $('textarea[name="justificacion_anulacion"]').focus();
            return;
        }

        // Confirmación final
        const confirmacion = confirm(
            '⚠️ CONFIRMACIÓN FINAL ⚠️\n\n' +
            'Estás a punto de ANULAR este pago.\n' +
            'Esta acción NO PUEDE SER REVERTIDA.\n\n' +
            '¿Estás completamente seguro de proceder?'
        );
        
        if (confirmacion) {
            const btn = $('#btnAnular');
            btn.html('<i class="fas fa-spinner fa-spin mr-2"></i>ANULANDO...').prop('disabled', true);
            
            this.submit();
        }
    });

    // Validación en tiempo real de justificación
    $('textarea[name="justificacion_anulacion"]').on('input', function() {
        const length = $(this).val().trim().length;
        const minLength = 20;
        
        if (length < minLength) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $('.invalid-feedback').text(`Mínimo ${minLength} caracteres. Actual: ${length}`);
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
        }
    });
});
</script>
@stop