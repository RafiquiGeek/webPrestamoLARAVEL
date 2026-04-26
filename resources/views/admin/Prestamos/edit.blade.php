@extends('layouts.admin')

@section('title', 'Editar Préstamo')

@section('content_header')
    <h1>
        <i class="fas fa-edit mr-2"></i>
        Editar Préstamo
        <small class="ml-3">
            <span class="badge badge-primary">ID: {{ $prestamo->id }}</span>
            @switch($prestamo->estado)
                @case('Nueva Solicitud')
                    <span class="badge badge-secondary">{{ $prestamo->estado }}</span>
                    @break
                @case('En Análisis')
                    <span class="badge badge-info">{{ $prestamo->estado }}</span>
                    @break
                @case('Por Desembolsar')
                    <span class="badge badge-warning">{{ $prestamo->estado }}</span>
                    @break
                @default
                    <span class="badge badge-light">{{ $prestamo->estado }}</span>
            @endswitch
        </small>
    </h1>
    <div class="breadcrumb">
        <a href="{{ route('admin.prestamos.index') }}">Préstamos</a> / Editar
    </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Sistema de alertas personalizado -->
    <div id="alert-overlay" class="position-fixed" style="top: 0; left: 0; width: 100vw; height: 100vh; z-index: 2147483647; display: none;">
        <!-- Backdrop -->
        <div class="position-absolute w-100 h-100" style="background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);"></div>
        
        <!-- Alerta centrada -->
        <div class="d-flex align-items-center justify-content-center h-100 position-relative" style="z-index: 2147483647;">
            <div id="custom-alert" class="bg-white rounded shadow-lg" style="min-width: 400px; max-width: 500px; box-shadow: 0 25px 50px rgba(0,0,0,0.8) !important;">
                <div id="alert-header" class="p-3 rounded-top">
                    <h5 id="alert-title" class="mb-0 text-white d-flex align-items-center">
                        <i id="alert-icon" class="mr-2"></i>
                        <span id="alert-title-text">Información</span>
                    </h5>
                </div>
                <div class="p-3">
                    <p id="alert-message" class="mb-3">Mensaje</p>
                    <div class="text-right">
                        <button type="button" class="btn btn-outline-secondary mr-2" id="alert-cancel" style="display: none;">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="alert-confirm">Aceptar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Formulario de Edición de Préstamo -->
        <div class="col-md-8">
            <form action="{{ route('admin.prestamos.update', $prestamo->id) }}" method="POST" enctype="multipart/form-data" id="prestamoForm" novalidate>
                @csrf
                @method('PUT')

                <!-- Información Principal -->
                <div class="card card-outline mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-clipboard-list mr-2"></i>
                            Información del Préstamo
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Estado y Cliente -->
                        <div class="row">
                            <!-- Estado -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Estado</label>
                                <div class="form-group">
                                    @switch($prestamo->estado)
                                        @case('Nueva Solicitud')
                                            <span class="badge badge-secondary p-2">{{ $prestamo->estado }}</span>
                                            @break
                                        @case('En Análisis')
                                            <span class="badge badge-info p-2">{{ $prestamo->estado }}</span>
                                            @break
                                        @case('Por Desembolsar')
                                            <span class="badge badge-warning p-2">{{ $prestamo->estado }}</span>
                                            @break
                                        @default
                                            <span class="badge badge-light p-2">{{ $prestamo->estado }}</span>
                                    @endswitch
                                </div>
                            </div>

                            <!-- Cliente (Solo lectura) -->
                            <div class="col-md-9 mb-3">
                                <label class="form-label">Cliente</label>
                                <div class="form-control bg-light">
                                    <i class="fas fa-user mr-2"></i>
                                    <strong>{{ $prestamo->cliente->persona->nombres }} {{ $prestamo->cliente->persona->apellidos }}</strong>
                                    <small class="text-muted ml-2">(DNI: {{ $prestamo->cliente->persona->documento }})</small>
                                </div>
                                <input type="hidden" name="cliente_id" value="{{ $prestamo->cliente_id }}">
                            </div>
                        </div>

                        <!-- Asignación de Personal -->
                        <div class="mt-4">
                            <h5 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-users mr-2"></i>
                                Asignación de Personal
                            </h5>
                            <div class="row">
                                <!-- Analista -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Analista <span class="text-danger">*</span></label>
                                    <select class="choices-select form-control" name="analista_id" required>
                                        <option value="" disabled>Selecciona un analista</option>
                                        @foreach ($analistas as $analista)
                                        <option value="{{ $analista->id }}" {{ old('analista_id', $analistaActual) == $analista->id ? 'selected' : '' }}>
                                            {{ $analista->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Por favor selecciona un analista.</div>
                                </div>

                                <!-- Asesor -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Asesor <span class="text-danger">*</span></label>
                                    <select class="choices-select form-control" name="asesor_id" required>
                                        <option value="" disabled>Selecciona un asesor</option>
                                        @foreach ($asesores as $asesor)
                                        <option value="{{ $asesor->id }}" {{ old('asesor_id', $asesorActual) == $asesor->id ? 'selected' : '' }}>
                                            {{ $asesor->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Por favor selecciona un asesor.</div>
                                </div>

                                <!-- JCC -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">JCC <span class="text-danger">*</span></label>
                                    <select class="choices-select form-control" name="jcc_id" required>
                                        <option value="" disabled>Selecciona un JCC</option>
                                        @foreach ($jccs as $jcc)
                                        <option value="{{ $jcc->id }}" {{ old('jcc_id', $jccActual) == $jcc->id ? 'selected' : '' }}>
                                            {{ $jcc->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Por favor selecciona un JCC.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Aval -->
                        <div class="mt-4">
                            <h5 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-user-shield mr-2"></i>
                                Información de Aval
                            </h5>
                            
                            @php
                                $tieneAval = $prestamo->aval ? 1 : 0;
                            @endphp
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">¿Tiene Aval?</label>
                                    <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                        <label class="btn btn-outline-primary {{ $tieneAval == 0 ? 'active' : '' }}">
                                            <input type="radio" name="tiene_aval" id="tieneAvalNo" value="0" {{ $tieneAval == 0 ? 'checked' : '' }}> No
                                        </label>
                                        <label class="btn btn-outline-primary {{ $tieneAval == 1 ? 'active' : '' }}">
                                            <input type="radio" name="tiene_aval" id="tieneAvalSi" value="1" {{ $tieneAval == 1 ? 'checked' : '' }}> Sí
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Contenedor de Aval -->
                            <div id="contenedorAval" style="{{ $tieneAval == 1 ? 'display: block;' : 'display: none;' }}" class="card bg-light mt-3">
                                <div class="card-body">
                                    <div class="row">
                                        <!-- DNI y botón Asignar -->
                                        <div class="col-md-4 mb-3">
                                            <label for="inputDni" class="form-label">DNI del Aval</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" placeholder="Ingresa el DNI" id="inputDni" name="aval_dni" maxlength="8" value="{{ $prestamo->aval ? $prestamo->aval->documento : '' }}">
                                                <div class="input-group-append">
                                                    <button class="btn btn-success" type="button" id="btnAsignar">
                                                        <i class="fas fa-check mr-1"></i>
                                                        Asignar
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="invalid-feedback">Por favor ingresa un DNI válido.</div>
                                        </div>

                                        <div class="col-md-8 mb-3">
                                            <label class="form-label">Nombre del Aval</label>
                                            <div class="form-control bg-white">
                                                <span id="nombreCliente" class="text-primary font-weight-bold">
                                                    {{ $prestamo->aval ? $prestamo->aval->nombres . ' ' . $prestamo->aval->apellidos : '---' }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label for="parentesco" class="form-label">Parentesco con el Cliente</label>
                                            <input type="text" class="form-control" id="parentesco" name="parentesco" placeholder="Ej. Hermano, Madre" 
                                                   value="{{ old('parentesco', $prestamo->aval->pivot->parentesco ?? '') }}">
                                        </div>

                                        <!-- Observaciones -->
                                        <div class="col-md-12">
                                            <label for="observaciones_aval" class="form-label">Observaciones</label>
                                            <textarea class="form-control" id="observaciones_aval" name="observaciones_aval" rows="2" 
                                                      placeholder="Detalles adicionales sobre el Aval">{{ old('observaciones_aval', $prestamo->aval->pivot->observaciones ?? '') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Campo oculto para almacenar el ID del aval -->
                            <input type="hidden" id="hiddenAvalId" name="aval_id" value="{{ $prestamo->aval->id ?? '' }}"/>
                        </div>
                    </div>
                </div>

                <!-- Configuración del Préstamo -->
                <div class="card card-outline mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cog mr-2"></i>
                            Configuración del Préstamo
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Cuenta, Fechas y Tipo de Solicitud -->
                        <div class="row">
                            <!-- Cuenta -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Cuenta <span class="text-danger">*</span></label>
                                <select class="choices-select form-control" name="cuenta_id" required>
                                    <option value="" disabled>Selecciona una cuenta</option>
                                    @foreach ($cuentas as $cuenta)
                                    <option value="{{ $cuenta->id }}" {{ old('cuenta_id', $prestamo->cuenta_id) == $cuenta->id ? 'selected' : '' }}>
                                        {{ $cuenta->numero_cuenta }} - {{ $cuenta->banco }}
                                    </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Por favor selecciona una cuenta.</div>
                            </div>

                            <!-- Fecha de Atención -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Fecha de Atención <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="fecha_atencion" 
                                       value="{{ old('fecha_atencion', $prestamo->fecha_atencion->format('Y-m-d')) }}" required>
                                <div class="invalid-feedback">Por favor selecciona una fecha de atención.</div>
                            </div>

                            <!-- Fecha Primer Pago -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Fecha Primer Pago <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="fecha_primer_pago" 
                                       value="{{ old('fecha_primer_pago', $prestamo->fecha_primer_pago->format('Y-m-d')) }}" required>
                                <div class="invalid-feedback">Por favor selecciona una fecha para el primer pago.</div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Tipo de Solicitud -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipo de Solicitud <span class="text-danger">*</span></label>
                                <select class="choices-select form-control" name="tipo_solicitud" required>
                                    <option value="" disabled>Selecciona el tipo</option>
                                    <option value="Nuevo" {{ old('tipo_solicitud', $prestamo->tipo_solicitud) == 'Nuevo' ? 'selected' : '' }}>Nuevo</option>
                                    <option value="Ampliación" {{ old('tipo_solicitud', $prestamo->tipo_solicitud) == 'Ampliación' ? 'selected' : '' }}>Ampliación</option>
                                    <option value="Renovación" {{ old('tipo_solicitud', $prestamo->tipo_solicitud) == 'Renovación' ? 'selected' : '' }}>Renovación</option>
                                </select>
                                <div class="invalid-feedback">Por favor selecciona el tipo de solicitud.</div>
                            </div>

                            <!-- Cuenta Cliente -->
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Cuenta Cliente</label>
                                <select name="cuenta_cliente_id" class="choices-select form-control" id="selectCuentaCliente">
                                    <option value="">Seleccione una cuenta (opcional)</option>
                                    @foreach ($cuentasCliente as $cuentaCliente)
                                    <option value="{{ $cuentaCliente->id }}" {{ old('cuenta_cliente_id', $prestamo->cuenta_cliente_id) == $cuentaCliente->id ? 'selected' : '' }}>
                                        {{ $cuentaCliente->numero_cuenta }} - {{ $cuentaCliente->banco }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Datos Financieros -->
                        <div class="mt-4">
                            <h5 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-calculator mr-2"></i>
                                Datos Financieros
                            </h5>
                            <div class="row">
                                <!-- Monto -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Monto Solicitado <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">S/</span>
                                        </div>
                                        <input type="number" class="form-control" name="cantidad_solicitada" 
                                               value="{{ old('cantidad_solicitada', $prestamo->cantidad_solicitada) }}" 
                                               min="100" step="50" required>
                                    </div>
                                    <div class="invalid-feedback">Por favor ingresa un monto válido.</div>
                                </div>

                                <!-- Plazo -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Plazo <span class="text-danger">*</span></label>
                                    <select class="choices-select form-control" name="plazo" required>
                                        <option value="" disabled>Selecciona el plazo</option>
                                        @foreach([8, 12, 15, 18, 20] as $plazoOption)
                                        <option value="{{ $plazoOption }}" {{ old('plazo', $prestamo->plazo) == $plazoOption ? 'selected' : '' }}>
                                            {{ $plazoOption }} semanas
                                        </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Por favor selecciona un plazo.</div>
                                </div>

                                <!-- Tasa de Interés -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tasa de Interés <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="tasa_interes" 
                                               value="{{ old('tasa_interes', $prestamo->tasa_interes) }}" 
                                               min="1" max="100" step="0.1" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback">Por favor ingresa una tasa válida.</div>
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Observaciones</label>
                                    <textarea class="form-control" name="observaciones" rows="3" 
                                              placeholder="Observaciones adicionales sobre el préstamo">{{ old('observaciones', $prestamo->observaciones) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.prestamos.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i>
                                Volver
                            </a>
                            <button type="submit" class="btn btn-primary" id="btnSubmit">
                                <i class="fas fa-save mr-1"></i>
                                Actualizar Préstamo
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Panel Lateral de Información -->
        <div class="col-md-4">
            <!-- Información del Cliente -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user mr-2"></i>
                        Información del Cliente
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-user-circle fa-3x text-primary"></i>
                        <h6 class="mt-2 mb-0">{{ $prestamo->cliente->persona->nombres }} {{ $prestamo->cliente->persona->apellidos }}</h6>
                        <small class="text-muted">DNI: {{ $prestamo->cliente->persona->documento }}</small>
                    </div>
                    
                    <div class="row text-sm">
                        <div class="col-6">
                            <strong>Teléfono:</strong>
                        </div>
                        <div class="col-6">
                            {{ $prestamo->cliente->persona->telefono ?? 'No registrado' }}
                        </div>
                        <div class="col-6">
                            <strong>Email:</strong>
                        </div>
                        <div class="col-6">
                            {{ $prestamo->cliente->persona->email ?? 'No registrado' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estado del Préstamo -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle mr-2"></i>
                        Estado del Préstamo
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h6><i class="icon fas fa-exclamation-triangle"></i> Importante!</h6>
                        <ul class="mb-0 text-sm">
                            <li>Se pueden editar préstamos en estado <strong>"Nueva Solicitud"</strong>, <strong>"En Análisis"</strong> o <strong>"Por Desembolsar"</strong></li>
                            <li>Si cambia el <strong>monto</strong>, <strong>plazo</strong> o <strong>tasa de interés</strong>, se recalcularán automáticamente las cuotas</li>
                            @switch($prestamo->estado)
                                @case('Nueva Solicitud')
                                    <li class="text-secondary"><strong>Estado Actual:</strong> Solicitud nueva, aún no enviada a análisis</li>
                                    @break
                                @case('En Análisis')
                                    <li class="text-info"><strong>Estado Actual:</strong> El préstamo está en análisis y aún no ha sido aprobado</li>
                                    @break
                                @case('Por Desembolsar')
                                    <li class="text-success"><strong>Estado Actual:</strong> El préstamo ya fue aprobado y está listo para desembolso</li>
                                    @break
                            @endswitch
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Datos Actuales -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line mr-2"></i>
                        Datos Actuales
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Monto:</th>
                            <td>S/ {{ number_format($prestamo->cantidad_solicitada, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Plazo:</th>
                            <td>{{ $prestamo->plazo }} semanas</td>
                        </tr>
                        <tr>
                            <th>Tasa:</th>
                            <td>{{ $prestamo->tasa_interes }}%</td>
                        </tr>
                        <tr>
                            <th>Cuotas:</th>
                            <td>{{ $prestamo->cuotas->count() }} generadas</td>
                        </tr>
                        <tr>
                            <th>Creado:</th>
                            <td>{{ $prestamo->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@stop

@section('css')
<link href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" rel="stylesheet" />
<style>
    .form-label {
        font-weight: 600;
        color: #495057;
    }
    
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    
    .text-danger {
        color: #dc3545 !important;
    }
    
    .btn-group-toggle .btn {
        border-radius: 0.25rem !important;
    }
    
    .choices {
        margin-bottom: 0;
    }
    
    .input-group-text {
        background-color: #e9ecef;
        border-color: #ced4da;
    }
    
    .alert-warning {
        border-left: 4px solid #ffc107;
    }
    
    #alert-overlay {
        backdrop-filter: blur(4px);
    }
    
    .sidebar-overlay {
        position: fixed;
        top: 0;
        right: -100%;
        width: 100%;
        height: 100vh;
        background: rgba(0,0,0,0.5);
        z-index: 1050;
        transition: right 0.3s ease;
    }
    
    .sidebar-overlay.show {
        right: 0;
    }
    
    .sidebar-content {
        position: absolute;
        right: 0;
        width: 500px;
        height: 100%;
        background: white;
        overflow-y: auto;
        box-shadow: -2px 0 10px rgba(0,0,0,0.1);
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
$(document).ready(function() {
    // Inicializar Choices.js
    const elements = document.querySelectorAll('.choices-select');
    elements.forEach(element => {
        new Choices(element, {
            searchEnabled: true,
            searchChoices: true,
            removeItemButton: false,
            placeholderValue: 'Selecciona una opción',
            noResultsText: 'No se encontraron resultados',
            noChoicesText: 'No hay opciones disponibles',
            itemSelectText: 'Presiona para seleccionar',
        });
    });

    // Manejo de aval
    $('input[name="tiene_aval"]').change(function() {
        if ($(this).val() === '1') {
            $('#contenedorAval').slideDown();
        } else {
            $('#contenedorAval').slideUp();
            $('#hiddenAvalId').val('');
            $('#nombreCliente').text('---');
            $('#inputDni').val('');
        }
    });

    // Buscar aval por DNI
    $('#btnAsignar').click(function() {
        const dni = $('#inputDni').val().trim();
        
        if (dni.length !== 8 || !/^\d+$/.test(dni)) {
            showCustomAlert('error', 'Error', 'El DNI debe tener 8 dígitos numéricos.');
            return;
        }

        const btn = $(this);
        const originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Buscando...').prop('disabled', true);

        // Simular búsqueda de aval
        setTimeout(() => {
            // Aquí iría la lógica real de búsqueda
            $('#nombreCliente').text('Persona encontrada');
            $('#hiddenAvalId').val('123'); // ID simulado
            
            btn.html(originalText).prop('disabled', false);
            showCustomAlert('success', 'Éxito', 'Aval asignado correctamente.');
        }, 1000);
    });

    // Validación de formulario
    $('#prestamoForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        // Verificar cambios que requieren recálculo
        const montoOriginal = {{ $prestamo->cantidad_solicitada }};
        const plazoOriginal = {{ $prestamo->plazo }};
        const tasaOriginal = {{ $prestamo->tasa_interes }};
        
        const montoNuevo = parseFloat($('input[name="cantidad_solicitada"]').val());
        const plazoNuevo = parseInt($('select[name="plazo"]').val());
        const tasaNueva = parseFloat($('input[name="tasa_interes"]').val());
        
        if (montoOriginal !== montoNuevo || plazoOriginal !== plazoNuevo || tasaOriginal !== tasaNueva) {
            showCustomAlert(
                'warning', 
                '¿Confirmar cambios?', 
                'Los cambios en monto, plazo o tasa de interés recalcularán automáticamente las cuotas del préstamo.',
                true,
                () => {
                    submitForm();
                }
            );
        } else {
            submitForm();
        }
    });

    function submitForm() {
        const btn = $('#btnSubmit');
        btn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Actualizando...').prop('disabled', true);
        
        // Enviar formulario
        document.getElementById('prestamoForm').submit();
    }

    // Sistema de alertas personalizado
    function showCustomAlert(type, title, message, showCancel = false, onConfirm = null) {
        const alertColors = {
            'success': { bg: '#28a745', icon: 'fas fa-check-circle' },
            'error': { bg: '#dc3545', icon: 'fas fa-times-circle' },
            'warning': { bg: '#ffc107', icon: 'fas fa-exclamation-triangle' },
            'info': { bg: '#17a2b8', icon: 'fas fa-info-circle' }
        };

        const config = alertColors[type] || alertColors['info'];
        
        $('#alert-header').css('background-color', config.bg);
        $('#alert-icon').attr('class', config.icon);
        $('#alert-title-text').text(title);
        $('#alert-message').text(message);
        
        if (showCancel) {
            $('#alert-cancel').show();
        } else {
            $('#alert-cancel').hide();
        }
        
        $('#alert-overlay').fadeIn(300);
        
        $('#alert-confirm').off('click').on('click', function() {
            $('#alert-overlay').fadeOut(300);
            if (onConfirm) onConfirm();
        });
        
        $('#alert-cancel').off('click').on('click', function() {
            $('#alert-overlay').fadeOut(300);
        });
    }

    // Cerrar alert al hacer clic en el backdrop
    $('#alert-overlay').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut(300);
        }
    });
});
</script>
@stop