@extends('layouts.admin')

@section('title', 'Registrar Pago - Convenio Flexible')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-1">
                <i class="fas fa-hand-holding-usd me-2 text-success"></i>Registrar Pago - Convenio Flexible #{{ $convenio->id }}
            </h5>
            <small class="text-muted">
                {{ $convenio->prestamo->cliente->persona->nombres }} {{ $convenio->prestamo->cliente->persona->ape_pat }}
            </small>
        </div>
        <a href="{{ route('admin.convenios.show', $convenio->id) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Volver
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <strong>Error:</strong> {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.convenios.flexible.pagar', $convenio->id) }}" method="POST" enctype="multipart/form-data" id="paymentForm">
        @csrf

        <div class="row">
            <!-- Columna Principal -->
            <div class="col-lg-8">
                <!-- Progreso -->
                <div class="card shadow-sm mb-3">
                    <div class="card-body p-3">
                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <div class="text-center">
                                    <small class="text-muted d-block">Total Convenio</small>
                                    <h5 class="mb-0">S/ {{ number_format($convenio->total_convenio, 2) }}</h5>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center">
                                    <small class="text-muted d-block">Pagado</small>
                                    <h5 class="mb-0 text-success">S/ {{ number_format($convenio->monto_total_pagado, 2) }}</h5>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center">
                                    <small class="text-muted d-block">Saldo Pendiente</small>
                                    <h5 class="mb-0 text-danger" id="saldoDisplay">S/ {{ number_format($saldoPendiente, 2) }}</h5>
                                </div>
                            </div>
                        </div>

                        <!-- Barra de progreso -->
                        <div class="position-relative" style="height: 40px; background: #e9ecef; border-radius: 8px; overflow: hidden;">
                            <div class="position-absolute h-100" style="width: {{ $convenio->porcentaje_avance }}%; background: linear-gradient(90deg, #28a745 0%, #20c997 100%);"></div>
                            <div class="position-absolute w-100 h-100 d-flex align-items-center justify-content-between px-3">
                                <span class="fw-bold text-white small" style="text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
                                    S/ {{ number_format($convenio->monto_total_pagado, 2) }}
                                </span>
                                <span class="fw-bold small" style="color: #6c757d;">
                                    Faltan: S/ {{ number_format($saldoPendiente, 2) }}
                                </span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">{{ $convenio->pagosFlexibles->count() }} pagos</small>
                            <small class="text-muted">{{ number_format($convenio->porcentaje_avance, 1) }}%</small>
                        </div>
                    </div>
                </div>

                <!-- Formulario -->
                <div class="card shadow-sm mb-3">
                    <div class="card-body p-3">
                        <!-- Monto con botones rápidos -->
                        <div class="mb-3">
                            <label for="monto" class="form-label fw-bold mb-2">Monto del Pago *</label>
                            <div class="row g-2 mb-2">
                                <div class="col-3">
                                    <button type="button" class="btn btn-sm btn-outline-success w-100" onclick="setMonto({{ min(50, $saldoPendiente) }})">
                                        S/ 50
                                    </button>
                                </div>
                                <div class="col-3">
                                    <button type="button" class="btn btn-sm btn-outline-success w-100" onclick="setMonto({{ round($saldoPendiente * 0.25, 2) }})">
                                        25%
                                    </button>
                                </div>
                                <div class="col-3">
                                    <button type="button" class="btn btn-sm btn-outline-success w-100" onclick="setMonto({{ round($saldoPendiente * 0.5, 2) }})">
                                        50%
                                    </button>
                                </div>
                                <div class="col-3">
                                    <button type="button" class="btn btn-sm btn-success w-100" onclick="setMonto({{ $saldoPendiente }})">
                                        Total
                                    </button>
                                </div>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-success text-white fw-bold">S/</span>
                                <input type="number" step="0.01" class="form-control" name="monto" id="monto"
                                       min="0.01" max="{{ $saldoPendiente }}" value="{{ old('monto', $saldoPendiente) }}"
                                       required oninput="updatePreview()" style="font-size: 1.2rem; font-weight: bold;">
                            </div>
                            <small class="text-muted">Máximo: S/ {{ number_format($saldoPendiente, 2) }}</small>
                        </div>

                        <!-- Fila de fecha y usuario -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label for="fecha_pago" class="form-label fw-bold small">
                                    <i class="fas fa-calendar me-1 text-primary"></i>Fecha del Pago *
                                </label>
                                <input type="date" class="form-control form-control-sm" name="fecha_pago" id="fecha_pago"
                                       value="{{ old('fecha_pago', date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label for="user_id" class="form-label fw-bold small">
                                    <i class="fas fa-user me-1 text-primary"></i>Usuario que Registra *
                                </label>
                                <select class="form-select selectform form-select-sm" name="user_id" id="user_id" required>
                                    @foreach($usuarios as $usuario)
                                        <option value="{{ $usuario->id }}"
                                            {{ (old('user_id') ? old('user_id') == $usuario->id : auth()->id() == $usuario->id) ? 'selected' : '' }}>
                                            {{ $usuario->codigo }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Método de pago -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small mb-2">
                                <i class="fas fa-credit-card me-1 text-success"></i>Método de Pago *
                            </label>
                            <div class="payment-methods-grid">
                                @foreach($metodosDePago as $metodo)
                                    <div class="payment-method-option">
                                        <input type="radio" id="metodo{{ $metodo->id }}" name="metodoPago"
                                               value="{{ $metodo->id }}" data-metodo="{{ $metodo->metodo_pago }}"
                                               {{ old('metodoPago') == $metodo->id ? 'checked' : '' }}
                                               required onchange="togglePaymentFields()">
                                        <label for="metodo{{ $metodo->id }}" class="payment-label">
                                            @switch($metodo->metodo_pago)
                                                @case('EFECTIVO')
                                                    <i class="fas fa-money-bill-wave text-success"></i>
                                                    @break
                                                @case('TRANSFERENCIA')
                                                    <i class="fas fa-exchange-alt text-primary"></i>
                                                    @break
                                                @case('TARJETA')
                                                    <i class="fas fa-credit-card text-info"></i>
                                                    @break
                                                @default
                                                    <i class="fas fa-wallet text-secondary"></i>
                                            @endswitch
                                            <span class="d-block small mt-1">{{ $metodo->metodo_pago }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Campos Adicionales para Transferencia/Tarjeta -->
                        <div id="payment-extra-fields" class="payment-extra-section" style="display:none;">
                            <h6 class="mb-3 small fw-bold" style="color: var(--text-primary);">
                                <i class="fas fa-info-circle me-2"></i>Datos de la Operación
                            </h6>
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <label for="entidad_bancaria" class="form-label small" style="color: var(--text-primary);">Entidad Bancaria</label>
                                    <select class="selectform form-select form-select-sm" style="border: 1px solid var(--border-primary);"
                                            id="entidad_bancaria"
                                            name="entidad_bancaria">
                                        <option value="">Seleccionar entidad</option>
                                        <option value="BCP">BCP</option>
                                        <option value="BBVA">BBVA</option>
                                        <option value="INTERBANK">Interbank</option>
                                        <option value="SCOTIABANK">Scotiabank</option>
                                        <option value="YAPE">Yape</option>
                                        <option value="PLIN">Plin</option>
                                        <option value="OTROS">Otros</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="nro_operacion" class="form-label small" style="color: var(--text-primary);">Número de Operación</label>
                                    <input type="text" class="form-control form-control-sm" style="border: 1px solid var(--border-primary);"
                                           id="nro_operacion"
                                           name="nro_operacion"
                                           value="{{ old('nro_operacion') }}"
                                           placeholder="Ingrese número">
                                </div>
                                <div class="col-md-4">
                                    <label for="fecha_operacion" class="form-label small" style="color: var(--text-primary);">Fecha de Operación</label>
                                    <input type="datetime-local" class="form-control form-control-sm" style="border: 1px solid var(--border-primary);"
                                           id="fecha_operacion"
                                           name="fecha_operacion"
                                           value="{{ old('fecha_operacion', date('Y-m-d\TH:i')) }}"
                                           max="{{ date('Y-m-d\TH:i') }}"
                                           title="No se permite seleccionar fechas futuras">
                                    <small class="form-text text-muted">No se pueden registrar pagos con fecha futura</small>
                                </div>
                                <div class="col-12">
                                    <label for="voucher" class="form-label small" style="color: var(--text-primary);">
                                        <i class="fas fa-image me-1 text-info"></i>Comprobante (Opcional)
                                    </label>
                                    <input type="file" class="form-control form-control-sm" style="border: 1px solid var(--border-primary);"
                                           id="voucher"
                                           name="voucher"
                                           accept="image/*,.pdf"
                                           onchange="previewVoucher(this)">
                                    <small class="text-muted">JPG, PNG o PDF (máx. 2MB)</small>
                                    <div id="voucherPreview" class="mt-2 text-center" style="display: none;">
                                        <img id="voucherImage" src="" alt="Preview" class="rounded border img-fluid"
                                             style="max-height: 100px; border: 1px solid var(--border-primary);">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Campos para Efectivo -->
                        <div id="cash-code-container" class="payment-extra-section" style="display:none;">
                            <h6 class="mb-3 small fw-bold" style="color: var(--text-primary);">
                                <i class="fas fa-money-bill-wave me-2"></i>Datos del Pago en Efectivo
                            </h6>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label for="codigo" class="form-label small" style="color: var(--text-primary);">Código de Operación</label>
                                    <input type="text" class="form-control form-control-sm" style="border: 1px solid var(--border-primary);"
                                        id="codigo"
                                        name="codigo"
                                        value="{{ old('codigo') }}"
                                        placeholder="Ingrese código de operación">
                                </div>
                                <div class="col-md-6">
                                    <label for="fecha_codigo" class="form-label small" style="color: var(--text-primary);">Fecha y Hora del Pago</label>
                                    <input type="datetime-local" class="form-control form-control-sm" style="border: 1px solid var(--border-primary);"
                                        id="fecha_codigo"
                                        name="fecha_codigo"
                                        value="{{ old('fecha_codigo', date('Y-m-d\TH:i')) }}"
                                        max="{{ date('Y-m-d\TH:i') }}"
                                        title="No se permite seleccionar fechas futuras">
                                    <small class="form-text text-muted">No se pueden registrar pagos con fecha futura</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label for="observaciones" class="form-label fw-bold small">Observaciones</label>
                            <textarea class="form-control form-control-sm" name="observaciones" id="observaciones" rows="2"
                                      placeholder="Observaciones...">{{ old('observaciones') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-success text-white py-2">
                        <h6 class="mb-0"><i class="fas fa-receipt me-2"></i>Resumen</h6>
                    </div>
                    <div class="card-body p-3">
                        <!-- Preview -->
                        <div class="text-center p-3 rounded mb-3" style="background: linear-gradient(135deg, rgba(40,167,69,0.1) 0%, rgba(40,167,69,0.05) 100%); border: 2px solid rgba(40,167,69,0.2);">
                            <small class="text-muted d-block">Monto a Pagar</small>
                            <h3 class="mb-0 fw-bold text-success" id="montoPreview">S/ {{ number_format($saldoPendiente, 2) }}</h3>
                            <hr class="my-2">
                            <small class="text-muted d-block">Saldo después</small>
                            <h5 class="mb-0 fw-bold text-danger" id="saldoDespuesPago">S/ 0.00</h5>
                        </div>

                        <div id="estadoParcial" class="alert alert-info py-2 mb-3">
                            <small><i class="fas fa-info-circle me-1"></i>Pago parcial</small>
                        </div>

                        <!-- Botones -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success" id="btnSubmit">
                                <i class="fas fa-check me-2"></i>Registrar Pago
                            </button>
                            <a href="{{ route('admin.convenios.show', $convenio->id) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
const saldoPendiente = {{ $saldoPendiente }};

function setMonto(valor) {
    document.getElementById('monto').value = valor.toFixed(2);
    updatePreview();
}

function updatePreview() {
    const monto = parseFloat(document.getElementById('monto').value) || 0;
    const saldoDespues = Math.max(0, saldoPendiente - monto);

    document.getElementById('montoPreview').textContent = 'S/ ' + monto.toFixed(2);
    document.getElementById('saldoDespuesPago').textContent = 'S/ ' + saldoDespues.toFixed(2);

    if (saldoDespues <= 0.01) {
        document.getElementById('saldoDespuesPago').style.color = '#28a745';
        document.getElementById('estadoCompleto').style.display = 'block';
        document.getElementById('estadoParcial').style.display = 'none';
        document.getElementById('btnSubmit').innerHTML = '<i class="fas fa-check-double me-2"></i>Completar Convenio';
    } else {
        document.getElementById('saldoDespuesPago').style.color = '#dc3545';
        document.getElementById('estadoCompleto').style.display = 'none';
        document.getElementById('estadoParcial').style.display = 'block';
        document.getElementById('btnSubmit').innerHTML = '<i class="fas fa-check me-2"></i>Registrar Pago';
    }
}

function togglePaymentFields() {
    const selected = document.querySelector('input[name="metodoPago"]:checked');

    if (!selected) return;

    const metodoPagoId = selected.value;
    console.log('Método de pago ID seleccionado:', metodoPagoId);

    // Ocultar todos los campos condicionales primero
    const paymentExtraFields = document.getElementById('payment-extra-fields');
    const cashCodeContainer = document.getElementById('cash-code-container');

    if (paymentExtraFields) paymentExtraFields.style.display = 'none';
    if (cashCodeContainer) cashCodeContainer.style.display = 'none';

    // Mostrar campos específicos según el ID del método de pago
    switch(metodoPagoId) {
        case '1': // EFECTIVO - NO mostrar campos adicionales
            console.log('EFECTIVO (ID: 1) - NO mostrar campos adicionales');
            // No se muestra nada, solo usa fecha_pago
            break;
        case '2': // TRANSFERENCIA
        case '3': // TARJETA/YAPE/PLIN
        case '4': // DEPOSITO u otros
        case '5': // Si hay más métodos
        case '6':
        case '7':
        case '8':
            console.log('Método con campos adicionales (ID: ' + metodoPagoId + ')');
            if (paymentExtraFields) paymentExtraFields.style.display = 'block';
            break;
        default:
            console.log('Método no reconocido (ID: ' + metodoPagoId + ')');
            break;
    }
}

function previewVoucher(input) {
    const preview = document.getElementById('voucherPreview');
    const previewImage = document.getElementById('voucherImage');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}

document.getElementById('paymentForm').addEventListener('submit', function(e) {
    const monto = parseFloat(document.getElementById('monto').value);

    if (monto <= 0 || monto > saldoPendiente) {
        e.preventDefault();
        alert('El monto debe ser mayor a 0 y no exceder el saldo pendiente.');
        return false;
    }

    const saldoDespues = saldoPendiente - monto;
    let mensaje = '¿Confirmar registro del pago?\n\n';
    mensaje += 'Monto: S/ ' + monto.toFixed(2) + '\n';
    mensaje += 'Saldo después: S/ ' + saldoDespues.toFixed(2);

    if (saldoDespues <= 0.01) {
        mensaje += '\n\n¡Completará el convenio!';
    }

    if (!confirm(mensaje)) {
        e.preventDefault();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    togglePaymentFields();
    updatePreview();
});
</script>

<style>
:root {
    --bg-primary: #f8f9fa;
    --text-primary: #212529;
    --border-primary: #dee2e6;
    --success: #28a745;
    --danger: #dc3545;
    --info: #17a2b8;
}

.card {
    border: none;
    border-radius: 8px;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--success);
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.btn-outline-success:hover {
    transform: translateY(-1px);
}
.selectform{
        height: 48px;
        border-radius: 8px;
        border: 1px solid var(--border-primary);
        width: 100%;
    }

/* Payment Methods Grid */
.payment-methods-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 10px;
    margin-bottom: 0;
}

.payment-method-option {
    position: relative;
}

.payment-method-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.payment-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 10px 8px;
    border: 1px solid var(--border-primary);
    border-radius: 8px;
    cursor: pointer;
    background-color: white;
    transition: all 0.3s ease;
    height: 70px;
    text-align: center;
}

.payment-label i {
    font-size: 20px;
    transition: all 0.3s ease;
}

.payment-method-option input[type="radio"]:checked + .payment-label {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
}

.payment-method-option input[type="radio"]:checked + .payment-label i {
    color: white !important;
}

.payment-label:hover {
    border-color: #0d6efd;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.15);
}
</style>

@endsection
