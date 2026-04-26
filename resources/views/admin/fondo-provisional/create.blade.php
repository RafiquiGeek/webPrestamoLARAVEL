@extends('layouts.admin')

@section('title', 'Registrar Fondo Provisional')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-12 text-center">
                <h1 class="font-weight-bold text-warning">
                    <i class="fas fa-piggy-bank mr-2"></i>Registrar Fondo Provisional
                </h1>
                <p class="text-muted">Registrar el fondo provisional entregado por el cliente (5% del capital)</p>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <form action="{{ route('admin.fondo-provisional.store') }}" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
            @csrf

            <div class="row">
                <!-- COLUMNA IZQUIERDA: Información Principal -->
                <div class="col-lg-8 order-1">
                    <!-- Información del Cliente y Préstamo -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user-check mr-2"></i>Información del Préstamo
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item mb-3">
                                        <label class="font-weight-bold text-muted">Cliente:</label>
                                        <div class="h5 text-primary">
                                            {{ $prestamo->cliente->persona->nombres }} 
                                            {{ $prestamo->cliente->persona->ape_pat }} 
                                            {{ $prestamo->cliente->persona->ape_mat }}
                                        </div>
                                    </div>
                                    <div class="info-item mb-3">
                                        <label class="font-weight-bold text-muted">Préstamo ID:</label>
                                        <div class="h6"># {{ $prestamo->id }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item mb-3">
                                        <label class="font-weight-bold text-muted">Capital del Préstamo:</label>
                                        <div class="h4 text-success">S/ {{ number_format($montoCapital, 2) }}</div>
                                    </div>
                                    <div class="info-item mb-3">
                                        <label class="font-weight-bold text-muted">Fondo Provisional (5%):</label>
                                        <div class="h4 text-warning">S/ {{ number_format($montoFondo, 2) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario de Registro -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-gradient-warning text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-piggy-bank mr-2"></i>Datos del Fondo Provisional
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Campos ocultos -->
                            <input type="hidden" name="prestamo_id" value="{{ $prestamo->id }}">
                            <input type="hidden" name="monto_capital" value="{{ $montoCapital }}">
                            <input type="hidden" name="monto_fondo" value="{{ $montoFondo }}">

                            <!-- Toggle de Exoneración -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="custom-control custom-switch custom-control-lg">
                                        <input type="checkbox" 
                                               class="custom-control-input" 
                                               id="exonerar_fondo" 
                                               name="exonerar_fondo"
                                               value="1"
                                               {{ old('exonerar_fondo') ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-bold" for="exonerar_fondo">
                                            <i class="fas fa-gift mr-2 text-success"></i>
                                            Exonerar Fondo Provisional
                                            <small class="d-block text-muted font-weight-normal mt-1">
                                                Si activa esta opción, el cliente no deberá entregar el fondo provisional
                                            </small>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div id="campos_fondo" class="campos-condicionales">
                                <div class="row">
                                    <!-- Fecha de Entrega -->
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_entrega" class="form-label font-weight-bold">
                                            <i class="far fa-calendar-alt mr-1 text-warning"></i>
                                            Fecha de Entrega <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" 
                                               name="fecha_entrega" 
                                               id="fecha_entrega" 
                                               class="form-control @error('fecha_entrega') is-invalid @enderror" 
                                               value="{{ old('fecha_entrega', date('Y-m-d')) }}" 
                                               required>
                                        @error('fecha_entrega')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Monto Personalizado -->
                                    <div class="col-md-4 mb-3">
                                        <label for="monto_personalizado" class="form-label font-weight-bold">
                                            <i class="fas fa-money-bill-wave mr-1 text-success"></i>
                                            Monto Personalizado <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">S/</span>
                                            </div>
                                            <input type="number" 
                                                name="monto_personalizado" 
                                                id="monto_personalizado" 
                                                class="form-control @error('monto_personalizado') is-invalid @enderror" 
                                                value="{{ old('monto_personalizado', $montoFondo) }}"
                                                min="0"
                                                max="{{ $montoFondo }}"
                                                step="0.01"
                                                required>
                                        </div>
                                        <small class="text-muted">Máximo: S/ {{ number_format($montoFondo, 2) }}</small>
                                        @error('monto_personalizado')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>    

                                    <!-- Resumen Compacto -->
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label font-weight-bold">
                                            <i class="fas fa-calculator mr-1 text-info"></i>
                                            Resumen
                                        </label>
                                        <div class="p-2 bg-light rounded border">
                                            <div class="small mb-1">
                                                <span>Capital:</span>
                                                <strong class="float-right">S/ {{ number_format($montoCapital, 2) }}</strong>
                                            </div>
                                            <div class="small mb-1">
                                                <span>Máximo (5%):</span>
                                                <strong class="float-right text-muted">S/ {{ number_format($montoFondo, 2) }}</strong>
                                            </div>
                                            <hr class="my-1">
                                            <div class="small">
                                                <span class="text-warning font-weight-bold">A Registrar:</span>
                                                <strong class="float-right text-warning" id="montoFinal">S/ {{ number_format($montoFondo, 2) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Método de Pago -->
                                <div class="form-group mb-3">
                                <label for="metodo_pago" class="form-label font-weight-bold">
                                    <i class="fas fa-credit-card mr-1 text-primary"></i>
                                    Método de Pago <span class="text-danger">*</span>
                                </label>
                                <select name="metodo_pago"
                                        id="metodo_pago"
                                        class="form-control @error('metodo_pago') is-invalid @enderror"
                                        required>
                                    <option value="">Seleccionar método de pago</option>
                                    <option value="efectivo" {{ old('metodo_pago') == 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                                    <option value="yape" {{ old('metodo_pago') == 'yape' ? 'selected' : '' }}>Yape</option>
                                </select>
                                @error('metodo_pago')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                </div>

                                <!-- Campos específicos para Yape -->
                                <div id="campos_yape" class="row" style="display: none;">
                                    <div class="col-md-6 mb-3">
                                        <label for="nro_operacion_yape" class="form-label font-weight-bold">
                                            <i class="fas fa-hashtag mr-1 text-purple"></i>
                                            Nro. de Operación
                                        </label>
                                        <input type="text"
                                            name="nro_operacion_yape"
                                            id="nro_operacion_yape"
                                            class="form-control @error('nro_operacion_yape') is-invalid @enderror"
                                            placeholder="Ingrese el número de operación"
                                            value="{{ old('nro_operacion_yape') }}">
                                        @error('nro_operacion_yape')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="imagen_yape" class="form-label font-weight-bold">
                                            <i class="fas fa-image mr-1 text-purple"></i>
                                            Imagen del Comprobante
                                        </label>
                                        <input type="file"
                                            name="imagen_yape"
                                            id="imagen_yape"
                                            class="form-control-file @error('imagen_yape') is-invalid @enderror"
                                            accept="image/*">
                                        <small class="text-muted">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB</small>
                                        @error('imagen_yape')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div> <!-- Cierre de campos_fondo -->

                            <!-- Observaciones (siempre visible) -->
                            <div class="form-group mb-3">
                                <label for="observaciones" class="form-label font-weight-bold">
                                    <i class="fas fa-comment-alt mr-1 text-muted"></i>
                                    Observaciones <span id="obs-exoneracion" style="display: none;">(Requerido para exoneración <span class="text-danger">*</span>)</span>
                                </label>
                                <textarea name="observaciones"
                                        id="observaciones"
                                        class="form-control @error('observaciones') is-invalid @enderror"
                                        rows="3"
                                        placeholder="Detalles adicionales sobre la entrega del fondo provisional...">{{ old('observaciones') }}</textarea>
                                <small class="text-muted" id="obs-hint" style="display: none;">Por favor, indique el motivo de la exoneración</small>
                                @error('observaciones')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Mensaje de Exoneración -->
                            <div id="mensaje_exoneracion" class="alert alert-success border-0 mb-3" style="display: none;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success mr-3 fa-2x"></i>
                                    <div>
                                        <h6 class="font-weight-bold mb-1">Fondo Provisional Exonerado</h6>
                                        <small class="text-muted">
                                            El cliente no deberá entregar el fondo provisional. Esta acción quedará registrada en el sistema.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: Información Adicional -->
                <div class="col-lg-4 order-2">
                    <!-- Información del Sistema -->
                    <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                        <div class="card-header bg-gradient-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle mr-2"></i>Información del Sistema
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info border-0 mb-3" style="background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-info-circle text-info mr-2"></i>
                                    <h6 class="font-weight-bold mb-0">Información</h6>
                                </div>
                                <small class="text-muted">
                                    Fondo provisional: hasta 5% del capital que entrega el cliente al asesor. 
                                    Se registra automáticamente y queda pendiente de rendición.
                                </small>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                                <span class="small font-weight-bold text-muted">Estado:</span>
                                <span class="badge badge-warning">
                                    <i class="fas fa-clock mr-1"></i>Por Registrar
                                </span>
                            </div>

                            <!-- Botones de Acción -->
                            <div class="mt-3">
                                <button type="submit" class="btn btn-warning btn-block mb-2">
                                    <i class="fas fa-save mr-2"></i>Registrar Fondo
                                </button>
                                
                                <a href="{{ route('admin.prestamos.show', $prestamo->id) }}" 
                                   class="btn btn-outline-secondary btn-block btn-sm">
                                    <i class="fas fa-arrow-left mr-1"></i>Volver
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@stop

@section('css')
<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    }
    
    .bg-gradient-warning {
        background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
    }
    
    .bg-gradient-info {
        background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
    }
    
    .border-left-info {
        border-left: 4px solid #17a2b8 !important;
    }
    
    .card {
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .info-item {
        padding-bottom: 0.5rem;
    }
    
    .form-control {
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: #ffc107;
        box-shadow: 0 0 0 0.2rem rgba(255,193,7,0.25);
    }
    
    .btn {
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .sticky-top {
        position: sticky;
        z-index: 1020;
    }

    .text-purple {
        color: #6f42c1 !important;
    }

    .form-control-file:focus {
        color: #495057;
        background-color: #fff;
        border-color: #ffc107;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(255,193,7,0.25);
    }

    .custom-control-lg .custom-control-label {
        padding-top: 0.25rem;
        padding-left: 0.5rem;
        font-size: 1.1rem;
    }

    .custom-control-lg .custom-control-label::before,
    .custom-control-lg .custom-control-label::after {
        width: 3rem;
        height: 1.5rem;
        border-radius: 3rem;
    }

    .custom-control-lg .custom-control-label::after {
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 50%;
    }

    .custom-switch.custom-control-lg .custom-control-label::after {
        top: calc(0.25rem + 2px);
        left: calc(-2.25rem + 2px);
    }

    @media (max-width: 992px) {
        .sticky-top {
            position: relative;
            top: auto !important;
        }
    }
</style>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.needs-validation');
    const montoPersonalizado = document.getElementById('monto_personalizado');
    const montoFinal = document.getElementById('montoFinal');
    const montoMaximo = {{ $montoFondo }};
    const exonerarCheckbox = document.getElementById('exonerar_fondo');
    const camposFondo = document.getElementById('campos_fondo');
    const mensajeExoneracion = document.getElementById('mensaje_exoneracion');
    const observaciones = document.getElementById('observaciones');
    const obsExoneracion = document.getElementById('obs-exoneracion');
    const obsHint = document.getElementById('obs-hint');
    
    // Manejar el toggle de exoneración
    if (exonerarCheckbox) {
        exonerarCheckbox.addEventListener('change', function() {
            if (this.checked) {
                // Ocultar campos del fondo
                camposFondo.style.display = 'none';
                mensajeExoneracion.style.display = 'block';
                
                // Establecer monto en 0 cuando está exonerado
                if (montoPersonalizado) {
                    montoPersonalizado.value = '0';
                    if (montoFinal) {
                        montoFinal.textContent = 'S/ 0.00';
                    }
                }
                
                // Remover required de los campos ocultos
                const inputsRequeridos = camposFondo.querySelectorAll('[required]');
                inputsRequeridos.forEach(input => {
                    input.removeAttribute('required');
                    input.classList.remove('is-invalid');
                });
                
                // Hacer observaciones requeridas para exoneración
                observaciones.setAttribute('required', 'required');
                obsExoneracion.style.display = 'inline';
                obsHint.style.display = 'block';
                observaciones.placeholder = 'Motivo de la exoneración del fondo provisional...';
            } else {
                // Mostrar campos del fondo
                camposFondo.style.display = 'block';
                mensajeExoneracion.style.display = 'none';
                
                // Restaurar monto al valor por defecto
                if (montoPersonalizado) {
                    montoPersonalizado.value = montoMaximo;
                    if (montoFinal) {
                        montoFinal.textContent = 'S/ ' + montoMaximo.toFixed(2);
                    }
                }
                
                // Restaurar required a los campos necesarios
                document.getElementById('fecha_entrega').setAttribute('required', 'required');
                document.getElementById('monto_personalizado').setAttribute('required', 'required');
                document.getElementById('metodo_pago').setAttribute('required', 'required');
                
                // Trigger para restaurar campos del método de pago seleccionado
                const metodoPago = document.getElementById('metodo_pago');
                if (metodoPago.value) {
                    metodoPago.dispatchEvent(new Event('change'));
                }
                
                // Hacer observaciones opcionales
                observaciones.removeAttribute('required');
                obsExoneracion.style.display = 'none';
                obsHint.style.display = 'none';
                observaciones.placeholder = 'Detalles adicionales sobre la entrega del fondo provisional...';
            }
        });
        
        // Trigger inicial si viene marcado por old()
        if (exonerarCheckbox.checked) {
            exonerarCheckbox.dispatchEvent(new Event('change'));
        }
    }
    
    // Actualizar el resumen cuando cambia el monto personalizado
    if (montoPersonalizado) {
        montoPersonalizado.addEventListener('input', function() {
            // Si está exonerado, no validar
            if (exonerarCheckbox && exonerarCheckbox.checked) {
                return;
            }
            
            const valor = parseFloat(this.value) || 0;
            
            if (valor > montoMaximo) {
                this.value = montoMaximo;
                this.classList.add('is-invalid');
                showAlert('El monto no puede exceder S/ ' + montoMaximo.toFixed(2), 'warning');
            } else {
                this.classList.remove('is-invalid');
            }
            
            // Actualizar resumen
            if (montoFinal) {
                montoFinal.textContent = 'S/ ' + (parseFloat(this.value) || 0).toFixed(2);
            }
        });
        
        // Validar al salir del campo
        montoPersonalizado.addEventListener('blur', function() {
            // Si está exonerado, no validar
            if (exonerarCheckbox && exonerarCheckbox.checked) {
                return;
            }
            
            const valor = parseFloat(this.value) || 0;
            if (valor <= 0) {
                this.classList.add('is-invalid');
                showAlert('El monto debe ser mayor a 0', 'warning');
            } else if (valor > montoMaximo) {
                this.value = montoMaximo;
                this.classList.add('is-invalid');
                showAlert('El monto no puede exceder S/ ' + montoMaximo.toFixed(2), 'warning');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }
    
    // Funcionalidad del método de pago
    const metodoPago = document.getElementById('metodo_pago');
    const camposYape = document.getElementById('campos_yape');

    if (metodoPago) {
        metodoPago.addEventListener('change', function() {
            const valor = this.value;

            // Ocultar campos de Yape por defecto
            camposYape.style.display = 'none';

            // Limpiar validaciones anteriores de Yape
            const camposYapeInputs = camposYape.querySelectorAll('input');
            camposYapeInputs.forEach(input => {
                input.removeAttribute('required');
                input.classList.remove('is-invalid');
            });

            // Mostrar campos de Yape si se selecciona
            if (valor === 'yape') {
                camposYape.style.display = 'block';
            }
        });

        // Trigger inicial para mantener estado después de errores de validación
        if (metodoPago.value) {
            metodoPago.dispatchEvent(new Event('change'));
        }
    }

    // Validación del formulario
    form.addEventListener('submit', function(event) {
        // Si está exonerado, solo validar observaciones
        if (exonerarCheckbox && exonerarCheckbox.checked) {
            if (!observaciones.value.trim()) {
                event.preventDefault();
                event.stopPropagation();
                observaciones.classList.add('is-invalid');
                showAlert('Por favor indique el motivo de la exoneración', 'error');
                return false;
            }
            form.classList.add('was-validated');
            return true; // Permitir submit
        }
        
        // Validación normal (no exonerado)
        const montoValor = parseFloat(montoPersonalizado.value) || 0;
        const metodoPagoValor = metodoPago.value;

        if (montoValor <= 0 || montoValor > montoMaximo) {
            event.preventDefault();
            event.stopPropagation();
            montoPersonalizado.classList.add('is-invalid');
            showAlert('Por favor ingrese un monto válido entre S/ 0.01 y S/ ' + montoMaximo.toFixed(2), 'error');
            return false;
        }

        // No hay campos adicionales requeridos para el método de pago
        // La fecha de entrega se usa para ambos métodos

        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        form.classList.add('was-validated');
    });
    
    // Auto-resize para textarea
    const textarea = document.getElementById('observaciones');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    }
    
    // Función para mostrar alertas
    function showAlert(message, type) {
        const alertClass = type === 'error' ? 'alert-danger' : 'alert-warning';
        const icon = type === 'error' ? 'fas fa-exclamation-triangle' : 'fas fa-exclamation-circle';
        
        const existingAlert = document.querySelector('.custom-alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        const alert = document.createElement('div');
        alert.className = `alert ${alertClass} alert-dismissible fade show custom-alert`;
        alert.innerHTML = `
            <i class="${icon} mr-2"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;
        
        const container = document.querySelector('.container-fluid');
        container.insertBefore(alert, container.firstChild);
        
        setTimeout(() => {
            if (alert && alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
});
</script>
@stop