@extends('layouts.admin')

@section('title', 'Editar Gestión')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-12 text-center">
                <h1 class="font-weight-bold text-primary">Editar Gestión de Cobranza</h1>
                <p class="text-muted">Modifique los campos para actualizar la información de la gestión</p>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-body p-3">
                <form action="{{ route('admin.gestiones.update', $gestion->id) }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="prestamo_id" value="{{ $gestion->prestamo_id }}">
                    <input type="hidden" name="latitud" id="latitud" value="{{ $gestion->latitud }}">
                    <input type="hidden" name="longitud" id="longitud" value="{{ $gestion->longitud }}">

                    <!-- Información del Cliente y Préstamo -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm mb-0">
                                <div class="card-header bg-white py-2 border-primary border-left-0 border-right-0 border-top-0 border-bottom">
                                    <h6 class="mb-0 font-weight-bold text-primary">
                                        <i class="fas fa-info-circle mr-2"></i>Información General
                                    </h6>
                                </div>
                                <div class="card-body py-3">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="cliente" class="form-label small font-weight-bold">Cliente</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text bg-white text-primary border-right-0">
                                                        <i class="fas fa-user"></i>
                                                    </span>
                                                </div>
                                                <input type="text" class="form-control border-left-0" 
                                                    value="{{ optional($gestion->prestamo->cliente->persona)->nombres }} {{ optional($gestion->prestamo->cliente->persona)->ape_pat }} {{ optional($gestion->prestamo->cliente->persona)->ape_mat }}" 
                                                    disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="fecha" class="form-label small font-weight-bold">Fecha de Gestión <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text bg-white text-primary border-right-0">
                                                        <i class="far fa-calendar-alt"></i>
                                                    </span>
                                                </div>
                                                <input type="date" name="fecha" id="fecha" class="form-control border-left-0 @error('fecha') is-invalid @enderror" 
                                                    value="{{ old('fecha', $gestion->fecha) }}" required>
                                            </div>
                                            @error('fecha')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalles de la Gestión -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm mb-0">
                                <div class="card-header bg-white py-2 border-primary border-left-0 border-right-0 border-top-0 border-bottom">
                                    <h6 class="mb-0 font-weight-bold text-primary">
                                        <i class="fas fa-clipboard-list mr-2"></i>Detalles de la Gestión
                                    </h6>
                                </div>
                                <div class="card-body py-3">
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <label class="form-label small font-weight-bold">Estado de Gestión <span class="text-danger">*</span></label>
                                            <div class="btn-group-toggle d-flex flex-wrap" data-toggle="buttons">
                                                @foreach($estados as $estado)
                                                    <label class="btn btn-outline-primary btn-sm mr-2 mb-2 flex-grow-0 {{ old('estado_id', $gestion->estado_id) == $estado->id ? 'active' : '' }}">
                                                        <input type="radio" name="estado_id" id="estado_id_{{ $estado->id }}" 
                                                            value="{{ $estado->id }}" 
                                                            {{ old('estado_id', $gestion->estado_id) == $estado->id ? 'checked' : '' }} 
                                                            required> {{ $estado->estado }}
                                                    </label>
                                                @endforeach
                                            </div>
                                            <div class="invalid-feedback d-block">
                                                @error('estado_id'){{ $message }}@enderror
                                            </div>
                                            <div class="selected-state mt-2 small text-primary">
                                                <i class="fas fa-check-circle mr-1"></i>Estado seleccionado: <span id="selected-state-text">{{ $estados->where('id', old('estado_id', $gestion->estado_id))->first()->estado }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <label for="observaciones" class="form-label small font-weight-bold">Observaciones <span class="text-danger">*</span></label>
                                            <textarea name="observaciones" id="observaciones" class="form-control @error('observaciones') is-invalid @enderror" rows="3" 
                                                placeholder="Detalles de la gestión..." required>{{ old('observaciones', $gestion->observaciones) }}</textarea>
                                            @error('observaciones')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Compromiso de Pago (Opcional) -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm mb-0">
                                <div class="card-header bg-white py-2 border-primary border-left-0 border-right-0 border-top-0 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 font-weight-bold text-primary">
                                            <i class="fas fa-handshake mr-2"></i>Compromiso de Pago
                                        </h6>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" name="compromisoPago" id="compromisoPago" 
                                                value="1" {{ old('compromisoPago', $gestion->compromiso ? 1 : 0) ? 'checked' : '' }}>
                                            <label class="custom-control-label small" for="compromisoPago">Activar compromiso</label>
                                        </div>
                                    </div>
                                </div>
                                <div id="compromisoFields" class="card-body py-3" style="display: {{ old('compromisoPago', $gestion->compromiso ? 1 : 0) ? 'block' : 'none' }};">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label small font-weight-bold">Estado Compromiso</label>
                                            <div class="btn-group-toggle estado-compromiso-group d-flex flex-wrap" data-toggle="buttons">
                                                <label class="btn btn-outline-primary btn-sm mr-1 mb-1 flex-grow-1 {{ old('estado', $gestion->compromiso ? $gestion->compromiso->estado : \App\Models\Compromiso::ESTADO_PENDIENTE) == \App\Models\Compromiso::ESTADO_PENDIENTE ? 'active' : '' }}">
                                                    <input type="radio" name="estado" id="estado_pendiente" 
                                                        value="{{ \App\Models\Compromiso::ESTADO_PENDIENTE }}" 
                                                        {{ old('estado', $gestion->compromiso ? $gestion->compromiso->estado : \App\Models\Compromiso::ESTADO_PENDIENTE) == \App\Models\Compromiso::ESTADO_PENDIENTE ? 'checked' : '' }}>
                                                    Pendiente
                                                </label>
                                                <label class="btn btn-outline-success btn-sm mr-1 mb-1 flex-grow-1 {{ old('estado', $gestion->compromiso ? $gestion->compromiso->estado : '') == \App\Models\Compromiso::ESTADO_PAGADO ? 'active' : '' }}">
                                                    <input type="radio" name="estado" id="estado_completado" 
                                                        value="{{ \App\Models\Compromiso::ESTADO_PAGADO }}" 
                                                        {{ old('estado', $gestion->compromiso ? $gestion->compromiso->estado : '') == \App\Models\Compromiso::ESTADO_PAGADO ? 'checked' : '' }}>
                                                    Pagado
                                                </label>
                                                <label class="btn btn-outline-danger btn-sm mb-1 flex-grow-1 {{ old('estado', $gestion->compromiso ? $gestion->compromiso->estado : '') == \App\Models\Compromiso::ESTADO_POSTERGADO ? 'active' : '' }}">
                                                    <input type="radio" name="estado" id="estado_cancelado" 
                                                        value="{{ \App\Models\Compromiso::ESTADO_POSTERGADO }}" 
                                                        {{ old('estado', $gestion->compromiso ? $gestion->compromiso->estado : '') == \App\Models\Compromiso::ESTADO_POSTERGADO ? 'checked' : '' }}>
                                                    Postergado
                                                </label>
                                            </div>
                                            @error('estado')
                                                <div class="invalid-feedback d-block small">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label for="fecha_compromiso" class="form-label small font-weight-bold">Fecha Compromiso</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text bg-white text-primary border-right-0">
                                                        <i class="far fa-calendar-alt"></i>
                                                    </span>
                                                </div>
                                                <input type="date" name="fecha_compromiso" id="fecha_compromiso" class="form-control border-left-0 @error('fecha_compromiso') is-invalid @enderror" 
                                                    value="{{ old('fecha_compromiso', $gestion->compromiso ? $gestion->compromiso->fecha_compromiso_pago : date('Y-m-d')) }}">
                                            </div>
                                            @error('fecha_compromiso')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label for="hora_hh" class="form-label small font-weight-bold">Hora Compromiso</label>
                                            <div class="d-flex">
                                                <select name="hora_hh" id="hora_hh" class="form-control mr-1 @error('hora_hh') is-invalid @enderror">
                                                    <option value="" disabled>HH</option>
                                                    @for($i = 8; $i < 20; $i++)
                                                        @php
                                                            $hora_formateada = str_pad($i, 2, '0', STR_PAD_LEFT);
                                                        @endphp
                                                        <option value="{{ $hora_formateada }}" 
                                                            {{ old('hora_hh', $gestion->compromiso ? substr($gestion->compromiso->hora, 0, 2) : date('H')) == $hora_formateada ? 'selected' : '' }}>
                                                            {{ $hora_formateada }}
                                                        </option>
                                                    @endfor
                                                </select>
                                                <span class="mt-2">:</span>
                                                <select name="hora_mm" id="hora_mm" class="form-control ml-1 @error('hora_mm') is-invalid @enderror">
                                                    <option value="" disabled>MM</option>
                                                    @foreach(['00', '15', '30', '45'] as $minuto)
                                                        @php
                                                            $minuto_actual = $gestion->compromiso ? substr($gestion->compromiso->hora, 3, 2) : round(date('i') / 15) * 15;
                                                            if ($minuto_actual >= 60) $minuto_actual = 45;
                                                            $minuto_actual = str_pad($minuto_actual, 2, '0', STR_PAD_LEFT);
                                                        @endphp
                                                        <option value="{{ $minuto }}" 
                                                            {{ old('hora_mm', $gestion->compromiso ? substr($gestion->compromiso->hora, 3, 2) : $minuto_actual) == $minuto ? 'selected' : '' }}>
                                                            {{ $minuto }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('hora_hh')
                                                <div class="invalid-feedback d-block small">{{ $message }}</div>
                                            @enderror
                                            @error('hora_mm')
                                                <div class="invalid-feedback d-block small">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label for="monto" class="form-label small font-weight-bold">Monto Compromiso</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text bg-white text-primary border-right-0">S/.</span>
                                                </div>
                                                <input type="number" name="monto" id="monto" class="form-control border-left-0 @error('monto') is-invalid @enderror" 
                                                    value="{{ old('monto', $gestion->compromiso ? $gestion->compromiso->monto : '') }}" step="0.01" min="0" placeholder="0.00">
                                            </div>
                                            @error('monto')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <label for="comentario" class="form-label small font-weight-bold">Comentario del Compromiso</label>
                                            <textarea name="comentario" id="comentario" class="form-control @error('comentario') is-invalid @enderror" rows="2" 
                                                placeholder="Detalles adicionales sobre el compromiso de pago...">{{ old('comentario', $gestion->compromiso ? $gestion->compromiso->comentario : '') }}</textarea>
                                            @error('comentario')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('admin.prestamos.show', $gestion->prestamo_id) }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left mr-1"></i> Volver
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> Actualizar Gestión
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('css')
        <style>
            /* Estilos base - modo claro siempre */
            body {
                background-color: #f8f9fa !important;
                color: #212529 !important;
            }
            
            .card {
                transition: all 0.2s ease;
                border-radius: 0.25rem;
                background-color: #ffffff !important;
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
            }
            
            .card:hover {
                box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1) !important;
            }
            
            .card-header {
                background-color: #ffffff !important;
                border-width: 3px !important;
                padding: 0.75rem 1.25rem !important;
            }
            
            .border-primary {
                border-color: #3c8dbc !important;
            }
            
            .text-primary {
                color: #3c8dbc !important;
            }
            
            .btn-primary {
                background-color: #3c8dbc !important;
                border-color: #367fa9 !important;
            }
            
            .btn-primary:hover {
                background-color: #367fa9 !important;
                border-color: #2e6da4 !important;
            }
            
            .bg-primary {
                background-color: #3c8dbc !important;
            }
            
            .form-control, .custom-select {
                border-radius: 0.25rem !important;
                font-size: 0.9rem !important;
                padding: 0.375rem 0.75rem !important;
                border-color: #d2d6de !important;
            }
            
            .form-control:focus, .custom-select:focus {
                border-color: #80bdff !important;
                box-shadow: 0 0 0 0.2rem rgba(60, 141, 188, 0.25) !important;
            }
            
            .input-group-text {
                border-color: #d2d6de !important;
                color: #555 !important;
            }
            
            .custom-control-input:checked ~ .custom-control-label::before {
                background-color: #3c8dbc !important;
                border-color: #367fa9 !important;
            }
            
            label {
                color: #444 !important;
            }
            
            .text-muted {
                color: #6c757d !important;
            }
            
            .shadow-sm {
                box-shadow: 0 .125rem .25rem rgba(0,0,0,.075) !important;
            }

            /* Estilos para botones de estado */
            .btn-group-toggle .btn.active {
                color: #ffffff !important;
                background-color: #3c8dbc !important;
                border-color: #367fa9 !important;
            }

            .btn-outline-primary {
                border-color: #3c8dbc !important;
                color: #3c8dbc !important;
            }

            .btn-outline-primary:hover {
                background-color: rgba(60, 141, 188, 0.1) !important;
            }

            .btn-outline-primary.active:hover {
                background-color: #367fa9 !important;
                color: #ffffff !important;
            }
            
            /* Estilos para botones de estado de compromiso */
            .estado-compromiso-group .btn.active {
                color: #ffffff !important;
            }

            .btn-outline-success.active {
                background-color: #28a745 !important;
                border-color: #218838 !important;
            }

            .btn-outline-danger.active {
                background-color: #dc3545 !important;
                border-color: #c82333 !important;
            }

            .btn-outline-success {
                border-color: #28a745 !important;
                color: #28a745 !important;
            }

            .btn-outline-danger {
                border-color: #dc3545 !important;
                color: #dc3545 !important;
            }

            /* Compactación y estilo bancario */
            .form-control, .custom-select {
                height: calc(2.25rem + 2px) !important;
            }
            
            .input-group-text {
                height: calc(2.25rem + 2px) !important;
                display: flex;
                align-items: center;
            }
            
            .form-label {
                margin-bottom: 0.25rem !important;
            }
            
            .small {
                font-size: 85% !important;
            }
            
            .btn {
                padding: 0.375rem 1rem !important;
            }
            
            textarea.form-control {
                min-height: calc(2.25rem + 2px) !important;
            }

            /* Modo responsivo */
            @media (max-width: 767.98px) {
                .card-body {
                    padding: 0.75rem !important;
                }
                
                .container-fluid {
                    padding-left: 0.5rem !important;
                    padding-right: 0.5rem !important;
                }
                
                .btn {
                    width: 100%;
                    margin-bottom: 0.5rem;
                }
                
                .d-flex.justify-content-between {
                    flex-direction: column;
                }
                
                .d-flex.justify-content-between a,
                .d-flex.justify-content-between button {
                    width: 100%;
                    margin-bottom: 0.5rem;
                    text-align: center;
                }
            }
        </style>
    @endpush

    @push('js')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Mostrar/Ocultar campos de compromiso
        const compromisoPagoCheckbox = document.getElementById('compromisoPago');
        const compromisoFields = document.getElementById('compromisoFields');

        function toggleCompromisoFields() {
            if (compromisoPagoCheckbox.checked) {
                compromisoFields.style.display = 'block';
                
                // Si está activado, permite que los campos sean requeridos para validación
                if (document.getElementById('estado_pendiente')) {
                    document.querySelector('input[name="estado"]:checked') || document.getElementById('estado_pendiente').setAttribute('required', '');
                }
                if (document.getElementById('fecha_compromiso')) {
                    document.getElementById('fecha_compromiso').setAttribute('required', '');
                }
                if (document.getElementById('hora_hh')) {
                    document.getElementById('hora_hh').setAttribute('required', '');
                }
                if (document.getElementById('hora_mm')) {
                    document.getElementById('hora_mm').setAttribute('required', '');
                }
                if (document.getElementById('monto')) {
                    document.getElementById('monto').setAttribute('required', '');
                }
            } else {
                compromisoFields.style.display = 'none';
                
                // Si está desactivado, elimina la validación de requerido
                if (document.querySelector('input[name="estado"]')) {
                    document.querySelectorAll('input[name="estado"]').forEach(el => el.removeAttribute('required'));
                }
                if (document.getElementById('fecha_compromiso')) {
                    document.getElementById('fecha_compromiso').removeAttribute('required');
                }
                if (document.getElementById('hora_hh')) {
                    document.getElementById('hora_hh').removeAttribute('required');
                }
                if (document.getElementById('hora_mm')) {
                    document.getElementById('hora_mm').removeAttribute('required');
                }
                if (document.getElementById('monto')) {
                    document.getElementById('monto').removeAttribute('required');
                }
            }
        }

        toggleCompromisoFields();
        compromisoPagoCheckbox.addEventListener('change', toggleCompromisoFields);

        // Validación del formulario
        const form = document.querySelector('.needs-validation');
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });

        // Gestión de selección de estados con botones
        const estadoBtns = document.querySelectorAll('input[name="estado_id"]');
        const selectedStateText = document.getElementById('selected-state-text');
        const selectedStateDiv = document.querySelector('.selected-state');

        estadoBtns.forEach(btn => {
            btn.addEventListener('change', function() {
                // Actualizar texto de estado seleccionado
                if (this.checked) {
                    const estadoLabel = this.parentElement.textContent.trim();
                    selectedStateText.textContent = estadoLabel;
                    selectedStateDiv.style.display = 'block';
                    
                    // Marcar este botón como activo y los demás como inactivos
                    estadoBtns.forEach(otherBtn => {
                        if (otherBtn !== this) {
                            otherBtn.parentElement.classList.remove('active');
                        } else {
                            otherBtn.parentElement.classList.add('active');
                        }
                    });
                }
            });
        });

        // Gestión de selección de estados de compromiso con botones
        const estadoCompromisoBtns = document.querySelectorAll('input[name="estado"]');

        estadoCompromisoBtns.forEach(btn => {
            btn.addEventListener('change', function() {
                // Marcar este botón como activo y los demás como inactivos
                estadoCompromisoBtns.forEach(otherBtn => {
                    if (otherBtn !== this) {
                        otherBtn.parentElement.classList.remove('active');
                    } else {
                        otherBtn.parentElement.classList.add('active');
                    }
                });
            });
        });

        // Inicializar Select2 si está disponible
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
            $('.select2').select2({
                theme: 'bootstrap4',
                placeholder: 'Seleccione una opción',
                width: '100%'
            });
        }
    });
    </script>
    @endpush
@stop