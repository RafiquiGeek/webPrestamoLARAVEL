@extends('layouts.admin')

@section('title', 'Editar Pago')

@section('content_header')
    <h1>
        <i class="fas fa-edit mr-2"></i>
        Editar Pago
        <small class="ml-3">
            <span class="badge badge-primary">ID: {{ $operacion->id }}</span>
            <span class="badge badge-info">{{ $operacion->tipo_operacion }}</span>
        </small>
    </h1>
    <div class="breadcrumb">
        <a href="{{ route('admin.operaciones.index') }}">Operaciones</a> / Editar
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Formulario de Edición ---->
        <div class="col-md-8">
            <form action="{{ route('admin.pagos.update', $operacion->id) }}" method="POST" enctype="multipart/form-data" id="pagoForm">
                @csrf
                @method('PUT')

                <!-- Información del Pago ---->
                <div class="card card-outline mb-4">
                    <div class="card-header text-black">
                        <h3 class="card-title">
                            <i class="fas fa-dollar-sign mr-2"></i>
                            Información del Pago
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Cliente y Préstamo (Solo lectura) ---->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label font-weight-bold">Cliente</label>
                                <div class="form-control bg-light">
                                    <i class="fas fa-user mr-2"></i>
                                    {{ $operacion->cliente->persona->nombres }} {{ $operacion->cliente->persona->apellidos }}
                                    <small class="text-muted ml-2">(DNI: {{ $operacion->cliente->persona->documento }})</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label font-weight-bold">Préstamo</label>
                                <div class="form-control bg-light">
                                    <i class="fas fa-file-contract mr-2"></i>
                                    Préstamo #{{ $operacion->prestamo->id }}
                                    <small class="text-muted ml-2">(S/ {{ number_format($operacion->prestamo->cantidad_solicitada, 2) }})</small>
                                </div>
                            </div>
                        </div>

                        <!-- Datos del Pago ---->
                        <div class="row">
                            <!-- Monto ---->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Monto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">S/</span>
                                    </div>
                                    <input type="number" class="form-control" name="monto" 
                                           value="{{ old('monto', $operacion->abono) }}" 
                                           min="0.01" step="0.01" required>
                                </div>
                                <div class="invalid-feedback">Por favor ingresa un monto válido.</div>
                            </div>

                            <!-- Fecha ---->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de Pago <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="fecha" 
                                       value="{{ old('fecha', $operacion->fecha->format('Y-m-d')) }}" required>
                                <div class="invalid-feedback">Por favor selecciona una fecha válida.</div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Método de Pago ---->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Método de Pago <span class="text-danger">*</span></label>
                                <select class="form-control" name="metodo_pago_id" required style="display: block !important; visibility: visible !important;">
                                    <option value="">Selecciona un método</option>
                                    @if(isset($metodosPago) && $metodosPago->count() > 0)
                                        @foreach ($metodosPago as $metodo)
                                        <option value="{{ $metodo->id }}" {{ old('metodo_pago_id', $operacion->metodo_pago_id) == $metodo->id ? 'selected' : '' }}>
                                            {{ $metodo->metodo_pago ?? $metodo->nombre }}
                                        </option>
                                        @endforeach
                                    @else
                                        <option value="" disabled>No hay métodos de pago disponibles</option>
                                    @endif
                                </select>
                                @if(!isset($metodosPago) || $metodosPago->count() === 0)
                                    <small class="text-warning">⚠️ No se encontraron métodos de pago</small>
                                @endif
                                <div class="invalid-feedback">Por favor selecciona un método de pago.</div>
                            </div>

                            <!-- Cuenta ---->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cuenta</label>
                                <select class="form-control" name="cuenta_id" style="display: block !important; visibility: visible !important;">
                                    <option value="">Selecciona una cuenta (opcional)</option>
                                    @if(isset($cuentas) && $cuentas->count() > 0)
                                        @foreach ($cuentas as $cuenta)
                                        <option value="{{ $cuenta->id }}" {{ old('cuenta_id', $operacion->cuenta_id) == $cuenta->id ? 'selected' : '' }}>
                                            {{ $cuenta->numero_cuenta ?? $cuenta->codigo }}
                                        </option>
                                        @endforeach
                                    @else
                                        <option value="" disabled>No hay cuentas disponibles</option>
                                    @endif
                                </select>
                                @if(!isset($cuentas) || $cuentas->count() === 0)
                                    <small class="text-warning">⚠️ No se encontraron cuentas</small>
                                @endif
                            </div>
                        </div>

                        <!-- Número de Operación ---->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Número de Operación</label>
                                <input type="text" class="form-control" name="numero_operacion" 
                                       value="{{ old('numero_operacion', $operacion->nro_operacion) }}" 
                                       placeholder="Número de operación o referencia">
                            </div>
                        </div>

                        <!-- Comentarios ---->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Comentarios</label>
                                <textarea class="form-control" name="comentario" rows="3" 
                                          placeholder="Comentarios adicionales sobre el pago">{{ old('comentario', $operacion->comentario) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Justificación de Edición ---->
                <div class="card card-outline card-warning mb-4">
                    <div class="card-header">
                        <h3 class="card-title text-black">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Justificación de Edición
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <h6><i class="icon fas fa-exclamation-triangle"></i> ¡Importante!</h6>
                            Es obligatorio proporcionar una justificación detallada para la edición de este pago. 
                            Esta información quedará registrada en el historial de auditoría.
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label">Justificación <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="justificacion_edicion" rows="4" 
                                          placeholder="Describe detalladamente la razón por la cual estás editando este pago..." required>{{ old('justificacion_edicion') }}</textarea>
                                <div class="invalid-feedback">La justificación es obligatoria para editar un pago.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción ---->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.operaciones.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i>
                                Volver
                            </a>
                            <button type="submit" class="btn btn-warning" id="btnSubmit">
                                <i class="fas fa-save mr-1"></i>
                                Actualizar Pago
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Panel Lateral de Información ---->
        <div class="col-md-4">
            <!-- Datos Originales del Pago ---->
            <div class="card mb-4">
                <div class="card-header text-black">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle mr-2"></i>
                        Datos Originales
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Monto Original:</th>
                            <td>S/ {{ number_format($operacion->abono, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Fecha Original:</th>
                            <td>{{ $operacion->fecha->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <th>Método:</th>
                            <td>{{ $operacion->metodoDePago->nombre ?? 'No especificado' }}</td>
                        </tr>
                        <tr>
                            <th>Registrado por:</th>
                            <td>{{ $operacion->user->name ?? 'Sistema' }}</td>
                        </tr>
                        <tr>
                            <th>Fecha registro:</th>
                            <td>{{ $operacion->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @if($operacion->editado_por)
                        <tr class="table-warning">
                            <th>Editado por:</th>
                            <td>{{ $operacion->editadoPor->name }}</td>
                        </tr>
                        <tr class="table-warning">
                            <th>Fecha edición:</th>
                            <td>{{ $operacion->editado_en ? \Carbon\Carbon::parse($operacion->editado_en)->format('d/m/Y H:i') : '-' }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Información del Préstamo ---->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-contract mr-2"></i>
                        Información del Préstamo
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Estado:</th>
                            <td>
                                @switch($operacion->prestamo->estado)
                                    @case('Desembolsado')
                                        <span class="badge badge-success">{{ $operacion->prestamo->estado }}</span>
                                        @break
                                    @case('En Cobranza')
                                        <span class="badge badge-warning">{{ $operacion->prestamo->estado }}</span>
                                        @break
                                    @case('Cancelado')
                                        <span class="badge badge-info">{{ $operacion->prestamo->estado }}</span>
                                        @break
                                    @default
                                        <span class="badge badge-secondary">{{ $operacion->prestamo->estado }}</span>
                                @endswitch
                            </td>
                        </tr>
                        <tr>
                            <th>Monto:</th>
                            <td>S/ {{ number_format($operacion->prestamo->cantidad_solicitada, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Plazo:</th>
                            <td>{{ $operacion->prestamo->plazo }} semanas</td>
                        </tr>
                        <tr>
                            <th>Cuotas Pagadas:</th>
                            <td>{{ $operacion->prestamo->cuotas->where('estado', 2)->count() }} de {{ $operacion->prestamo->cuotas->count() }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Advertencias ---->
            <div class="card border-warning">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Advertencias
                    </h5>
                </div>
                <div class="card-body p-4">
                    <ul class="mb-0 text-sm">
                        <li>Los cambios en el monto pueden afectar el estado de las cuotas asociadas</li>
                        <li>La fecha de pago no puede ser posterior a la fecha actual</li>
                        <li>La justificación será visible en el historial de auditoría</li>
                        <li>Esta acción generará un registro en los logs del sistema</li>
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
    
    .table-warning {
        background-color: #fff3cd;
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Validación de formulario
    $('#pagoForm').on('submit', function(e) {
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

        // Confirmar edición
        if (confirm('¿Estás seguro de actualizar este pago? Los cambios quedarán registrados en el historial de auditoría.')) {
            const btn = $('#btnSubmit');
            btn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Actualizando...').prop('disabled', true);
            
            this.submit();
        }
    });

    // Validación de fecha no posterior a hoy
    $('input[name="fecha"]').on('change', function() {
        const fechaSeleccionada = new Date($(this).val());
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        
        if (fechaSeleccionada > hoy) {
            alert('La fecha de pago no puede ser posterior a la fecha actual.');
            $(this).val('{{ $operacion->fecha->format("Y-m-d") }}');
        }
    });
});
</script>
@stop