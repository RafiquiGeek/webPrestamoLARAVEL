@extends('layouts.admin')

@section('title', 'Editar Cuota')

@section('content_header')
    <h1>
        <i class="fas fa-edit mr-2"></i>
        Editar Cuota
        <small class="ml-3">
            <span class="badge badge-primary">Cuota #{{ $cuota->numero }}</span>
            @switch($cuota->estado->value)
                @case(0)
                    <span class="badge badge-secondary">Pendiente</span>
                    @break
                @case(1)
                    <span class="badge badge-warning">Parcial</span>
                    @break
                @case(2)
                    <span class="badge badge-success">Pagada</span>
                    @break
                @case(3)
                    <span class="badge badge-danger">Vencida</span>
                    @break
                @default
                    <span class="badge badge-light">{{ $cuota->estado->value }}</span>
            @endswitch
        </small>
    </h1>
    <div class="breadcrumb">
        <a href="{{ route('admin.prestamos.show', $cuota->prestamo_id) }}">Préstamo #{{ $cuota->prestamo_id }}</a> / Editar Cuota
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Formulario de Edición -->
        <div class="col-md-8">
            <form action="{{ route('admin.cuotas.update', $cuota->id) }}" method="POST" id="cuotaForm">
                @csrf
                @method('PUT')

                <!-- Información de la Cuota -->
                <div class="card card-outline mb-4">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title">
                            <i class="fas fa-receipt mr-2"></i>
                            Datos de la Cuota
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Información del Préstamo (Solo lectura) -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label font-weight-bold">Cliente</label>
                                <div class="form-control bg-light">
                                    <i class="fas fa-user mr-2"></i>
                                    {{ $cuota->prestamo->cliente->persona->nombres }} {{ $cuota->prestamo->cliente->persona->apellidos }}
                                    <small class="text-muted ml-2">(DNI: {{ $cuota->prestamo->cliente->persona->documento }})</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-weight-bold">Préstamo</label>
                                <div class="form-control bg-light">
                                    <i class="fas fa-file-contract mr-2"></i>
                                    Préstamo #{{ $cuota->prestamo->id }}
                                    <small class="text-muted ml-2">(S/ {{ number_format($cuota->prestamo->cantidad_solicitada, 2) }})</small>
                                </div>
                            </div>
                        </div>

                        <!-- Fecha de Pago -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de Pago <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="fecha_pago" 
                                       value="{{ old('fecha_pago', $cuota->fecha_pago->format('Y-m-d')) }}" required>
                                <div class="invalid-feedback">Por favor selecciona una fecha válida.</div>
                                <small class="form-text text-muted">Fecha original: {{ $cuota->fecha_pago->format('d/m/Y') }}</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Número de Cuota</label>
                                <div class="form-control bg-light">
                                    <i class="fas fa-hashtag mr-2"></i>
                                    {{ $cuota->numero }} de {{ $cuota->prestamo->cuotas->count() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Componentes Financieros -->
                <div class="card card-outline mb-4">
                    <div class="card-header bg-success text-white">
                        <h3 class="card-title">
                            <i class="fas fa-calculator mr-2"></i>
                            Componentes Financieros
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Pago Capital -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pago Capital <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">S/</span>
                                    </div>
                                    <input type="number" class="form-control" name="pago_capital" 
                                           value="{{ old('pago_capital', $cuota->pago_capital) }}" 
                                           min="0" step="0.01" required>
                                </div>
                                <div class="invalid-feedback">Por favor ingresa un monto válido.</div>
                            </div>

                            <!-- Interés -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Interés <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">S/</span>
                                    </div>
                                    <input type="number" class="form-control" name="interes" 
                                           value="{{ old('interes', $cuota->interes) }}" 
                                           min="0" step="0.01" required>
                                </div>
                                <div class="invalid-feedback">Por favor ingresa un monto válido.</div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Comisión -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Comisión <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">S/</span>
                                    </div>
                                    <input type="number" class="form-control" name="comision" 
                                           value="{{ old('comision', $cuota->comision) }}" 
                                           min="0" step="0.01" required>
                                </div>
                                <div class="invalid-feedback">Por favor ingresa un monto válido.</div>
                            </div>

                            <!-- GAS -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">GAS</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">S/</span>
                                    </div>
                                    <input type="number" class="form-control" name="gas" 
                                           value="{{ old('gas', $cuota->gas) }}" 
                                           min="0" step="0.01">
                                </div>
                                <small class="form-text text-muted">Gastos administrativos (opcional)</small>
                            </div>
                        </div>

                        <div class="row">
                            <!-- IGV -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">IGV <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">S/</span>
                                    </div>
                                    <input type="number" class="form-control" name="igv" 
                                           value="{{ old('igv', $cuota->igv) }}" 
                                           min="0" step="0.01" required>
                                </div>
                                <div class="invalid-feedback">Por favor ingresa un monto válido.</div>
                                <small class="form-text text-muted">18% de (Interés + Comisión)</small>
                            </div>

                            <!-- Monto Total -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Monto Total <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-success text-white">S/</span>
                                    </div>
                                    <input type="number" class="form-control font-weight-bold" name="monto" 
                                           value="{{ old('monto', $cuota->monto) }}" 
                                           min="0.01" step="0.01" required readonly>
                                </div>
                                <div class="invalid-feedback">El monto total es obligatorio.</div>
                                <small class="form-text text-success">Se calcula automáticamente</small>
                            </div>
                        </div>

                        <!-- Alerta de verificación -->
                        <div class="alert alert-info">
                            <h6><i class="icon fas fa-info-circle"></i> Verificación Automática</h6>
                            El monto total se calculará automáticamente como la suma de todos los componentes.
                            El IGV debe ser el 18% de (Interés + Comisión).
                        </div>
                    </div>
                </div>

                <!-- Justificación -->
                <div class="card card-outline card-warning mb-4">
                    <div class="card-header bg-warning">
                        <h3 class="card-title">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Justificación de Edición
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <h6><i class="icon fas fa-exclamation-triangle"></i> ¡Obligatorio!</h6>
                            Debes proporcionar una justificación detallada para la edición de esta cuota.
                            Esta información quedará registrada en el historial de auditoría.
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label">Justificación <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="justificacion_edicion" rows="4" 
                                          placeholder="Describe detalladamente por qué estás editando esta cuota..." required>{{ old('justificacion_edicion') }}</textarea>
                                <div class="invalid-feedback">La justificación es obligatoria para editar una cuota.</div>
                                <small class="form-text text-muted">Mínimo 10 caracteres. Sé específico sobre los cambios realizados.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.prestamos.show', $cuota->prestamo_id) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i>
                                Volver al Préstamo
                            </a>
                            <button type="submit" class="btn btn-warning" id="btnSubmit">
                                <i class="fas fa-save mr-1"></i>
                                Actualizar Cuota
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Panel Lateral de Información -->
        <div class="col-md-4">
            <!-- Datos Originales -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle mr-2"></i>
                        Datos Originales
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Fecha Pago:</th>
                            <td>{{ $cuota->fecha_pago->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <th>Monto Total:</th>
                            <td>S/ {{ number_format($cuota->monto, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Capital:</th>
                            <td>S/ {{ number_format($cuota->pago_capital, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Interés:</th>
                            <td>S/ {{ number_format($cuota->interes, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Comisión:</th>
                            <td>S/ {{ number_format($cuota->comision, 2) }}</td>
                        </tr>
                        <tr>
                            <th>IGV:</th>
                            <td>S/ {{ number_format($cuota->igv, 2) }}</td>
                        </tr>
                        @if($cuota->gas > 0)
                        <tr>
                            <th>GAS:</th>
                            <td>S/ {{ number_format($cuota->gas, 2) }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Estado de Pagos -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie mr-2"></i>
                        Estado de Pagos
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Monto Pagado:</th>
                            <td>
                                <span class="font-weight-bold text-success">
                                    S/ {{ number_format($cuota->monto_pagado ?? 0, 2) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Saldo Pendiente:</th>
                            <td>
                                <span class="font-weight-bold text-warning">
                                    S/ {{ number_format($cuota->monto - ($cuota->monto_pagado ?? 0), 2) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Pagos Registrados:</th>
                            <td>{{ $cuota->operaciones->count() }}</td>
                        </tr>
                    </table>

                    @if($cuota->operaciones->count() > 0)
                    <div class="mt-3">
                        <h6 class="text-muted">Últimos Pagos:</h6>
                        @foreach($cuota->operaciones->take(3) as $operacion)
                        <div class="d-flex justify-content-between text-sm">
                            <span>{{ $operacion->fecha->format('d/m/Y') }}</span>
                            <span>S/ {{ number_format($operacion->abono, 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            <!-- Advertencias -->
            <div class="card border-warning">
                <div class="card-header bg-warning">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Advertencias
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0 text-sm">
                        <li>Los cambios afectarán los cálculos de moras futuras</li>
                        <li>Si la cuota tiene pagos, se recalculará su estado automáticamente</li>
                        <li>El IGV debe ser exactamente 18% de (Interés + Comisión)</li>
                        <li>La justificación será visible en el historial de auditoría</li>
                        <li>Esta acción será registrada en los logs del sistema</li>
                    </ul>
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
    
    .table-sm th {
        font-weight: 600;
        color: #6c757d;
    }
    
    .bg-success {
        background-color: #28a745 !important;
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Función para calcular IGV automáticamente
    function calcularIGV() {
        const interes = parseFloat($('input[name="interes"]').val()) || 0;
        const comision = parseFloat($('input[name="comision"]').val()) || 0;
        const igv = (interes + comision) * 0.18;
        $('input[name="igv"]').val(igv.toFixed(2));
        calcularMontoTotal();
    }

    // Función para calcular monto total
    function calcularMontoTotal() {
        const capital = parseFloat($('input[name="pago_capital"]').val()) || 0;
        const interes = parseFloat($('input[name="interes"]').val()) || 0;
        const comision = parseFloat($('input[name="comision"]').val()) || 0;
        const gas = parseFloat($('input[name="gas"]').val()) || 0;
        const igv = parseFloat($('input[name="igv"]').val()) || 0;
        
        const total = capital + interes + comision + gas + igv;
        $('input[name="monto"]').val(total.toFixed(2));
    }

    // Eventos para recalcular automáticamente
    $('input[name="interes"], input[name="comision"]').on('input', calcularIGV);
    $('input[name="pago_capital"], input[name="gas"], input[name="igv"]').on('input', calcularMontoTotal);

    // Validación de formulario
    $('#cuotaForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        // Verificar justificación
        const justificacion = $('textarea[name="justificacion_edicion"]').val().trim();
        if (justificacion.length < 10) {
            alert('La justificación debe tener al menos 10 caracteres.');
            $('textarea[name="justificacion_edicion"]').focus();
            return;
        }

        // Verificar que IGV sea correcto
        const interes = parseFloat($('input[name="interes"]').val()) || 0;
        const comision = parseFloat($('input[name="comision"]').val()) || 0;
        const igvCalculado = (interes + comision) * 0.18;
        const igvIngresado = parseFloat($('input[name="igv"]').val()) || 0;
        
        if (Math.abs(igvCalculado - igvIngresado) > 0.02) {
            alert(`El IGV debe ser S/ ${igvCalculado.toFixed(2)} (18% de Interés + Comisión)`);
            $('input[name="igv"]').focus();
            return;
        }

        // Confirmar edición
        if (confirm('¿Estás seguro de actualizar esta cuota? Los cambios quedarán registrados en el historial de auditoría.')) {
            const btn = $('#btnSubmit');
            btn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Actualizando...').prop('disabled', true);
            
            this.submit();
        }
    });

    // Calcular valores iniciales
    calcularMontoTotal();
});
</script>
@stop