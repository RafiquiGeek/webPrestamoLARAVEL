@extends('layouts.admin')

@section('title', 'Liquidar Convenio #' . $convenio->id)

@section('content')
<style>
    .liquidacion-wrapper {
        padding: 1.5rem;
        background: #f8f9fa;
    }

    .liquidacion-header {
        background: white;
        border-radius: 8px;
        padding: 1rem 1.5rem;
        margin-bottom: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .liquidacion-header h5 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: #495057;
    }

    .liquidacion-tabs {
        background: white;
        border-radius: 8px;
        margin-bottom: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .nav-tabs-compact {
        border-bottom: 2px solid #e9ecef;
        padding: 0;
        margin: 0;
    }

    .nav-tabs-compact .nav-link {
        border: none;
        border-bottom: 2px solid transparent;
        color: #6c757d;
        font-weight: 600;
        font-size: 0.875rem;
        padding: 0.75rem 1.5rem;
        margin-bottom: -2px;
        transition: all 0.2s;
    }

    .nav-tabs-compact .nav-link:hover {
        color: #495057;
        background: #f8f9fa;
    }

    .nav-tabs-compact .nav-link.active {
        color: #007bff;
        border-bottom-color: #007bff;
        background: transparent;
    }

    .tab-content-compact {
        padding: 1rem;
        max-height: 350px;
        overflow-y: auto;
    }

    /* Scrollbar compacto */
    .tab-content-compact::-webkit-scrollbar {
        width: 6px;
    }

    .tab-content-compact::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .tab-content-compact::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 3px;
    }

    /* Lista de items compacta */
    .item-list-compact {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .item-compact {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 0.75rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s;
    }

    .item-compact:hover {
        border-color: #007bff;
        background: white;
        box-shadow: 0 2px 4px rgba(0,123,255,0.1);
    }

    .item-compact-left {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .item-number {
        background: #007bff;
        color: white;
        width: 75px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .item-number.danger {
        background: #dc3545;
    }

    .item-info {
        display: flex;
        flex-direction: column;
    }

    .item-title {
        font-weight: 600;
        color: #495057;
        font-size: 0.875rem;
    }

    .item-subtitle {
        font-size: 0.75rem;
        color: #6c757d;
    }

    .item-compact-right {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .item-amount {
        font-weight: 700;
        font-size: 1rem;
        color: #007bff;
    }

    .item-amount.danger {
        color: #dc3545;
    }

    .item-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }

    /* Panel lateral compacto */
    .sidebar-compact {
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        height: fit-content;
        width: 100%;
    }

    .total-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 8px;
        padding: 1.25rem;
        text-align: center;
    }

    .total-box-label {
        font-size: 0.75rem;
        opacity: 0.9;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
    }

    .total-box-amount {
        font-size: 2rem;
        font-weight: 800;
        margin: 0;
    }

    .summary-grid {
        display: flex;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }

    .summary-item {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 0.75rem;
        text-align: center;
    }

    .summary-item-label {
        font-size: 0.7rem;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 0.25rem;
    }

    .summary-item-value {
        font-size: 0.95rem;
        font-weight: 700;
        color: #495057;
    }

    .summary-item-sub {
        font-size: 0.75rem;
        color: #28a745;
        margin-top: 0.125rem;
    }

    /* Sección compacta */
    .section-compact {
        margin-bottom: 1rem;
        border-radius: 15px;
        background: white;
        padding: 1rem;
    }

    .section-header {
        font-size: 0.8rem;
        font-weight: 700;
        color: #495057;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .section-header i {
        color: #007bff;
    }

    /* Descuentos compactos */
    .discount-group {
        border-radius: 6px;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
    }

    .discount-header {
        font-size: 0.75rem;
        font-weight: 600;
        color: #007bff;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .discount-controls {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .btn-group-compact {
        display: flex;
    }

    .btn-group-compact .btn {
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
        border-radius: 4px;
    }

    .btn-group-compact .btn:first-child {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .btn-group-compact .btn:last-child {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .input-compact {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
        border-radius: 4px;
    }

    .discount-total {
        font-size: 0.75rem;
        color: #6c757d;
        display: flex;
        justify-content: space-between;
    }

    /* Método de pago compacto */
    .payment-methods {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .payment-method {
        position: relative;
    }

    .payment-method input[type="radio"] {
        position: absolute;
        opacity: 0;
    }

    .payment-method label {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0.75rem 0.5rem;
        border: 2px solid #e9ecef;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
        background: white;
        margin: 0;
    }

    .payment-method label i {
        font-size: 1.5rem;
        margin-bottom: 0.25rem;
        color: #6c757d;
    }

    .payment-method label span {
        font-size: 0.7rem;
        font-weight: 600;
        color: #6c757d;
        text-align: center;
    }

    .payment-method input[type="radio"]:checked + label {
        border-color: #007bff;
        background: #e7f3ff;
    }

    .payment-method input[type="radio"]:checked + label i,
    .payment-method input[type="radio"]:checked + label span {
        color: #007bff;
    }

    /* Footer sticky */
    .liquidacion-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        border-top: 1px solid #e9ecef;
        padding: 1rem 1.5rem;
        z-index: 1030;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
    }

    .content-with-footer {
        padding-bottom: 80px;
    }

    /* Alert info */
    .alert-compact {
        background: #e7f3ff;
        border-left: 3px solid #007bff;
        padding: 0.75rem;
        border-radius: 6px;
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }

    .form-switch {
        padding-left: 2.5rem;
    }

    .form-switch .form-check-input {
        width: 2rem;
        height: 1rem;
        margin-left: -2.5rem;
    }

    .form-label-compact {
        font-size: 0.75rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.25rem;
    }

    .form-control-compact {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
    }

    .btn-action {
        font-size: 0.875rem;
        padding: 0.5rem 1.25rem;
        font-weight: 600;
        border-radius: 6px;
    }

    .extra-fields {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 0.75rem;
        margin-top: 0.75rem;
    }

    /* Nueva estructura de tres columnas */
    .columna-descuentos {
        background: white;
        border-radius: 8px;
        padding: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        height: fit-content;
    }

    /* Toggle Switch */
    .toggle-switch-wrapper {
        text-align: center;
    }

    .toggle-switch {
        position: relative;
        display: inline-flex;
        background: #f8f9fa;
        border-radius: 50px;
        padding: 4px;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #e9ecef;
        margin-bottom: 4px;
    }

    .toggle-option {
        display: none;
    }

    .toggle-label {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 28px;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
        font-size: 0.75rem;
        color: #6c757d;
        margin-bottom: 0!important;
    }

    .toggle-text {
        z-index: 2;
        transition: color 0.3s ease;
    }

    .toggle-slider {
        position: absolute;
        top: 4px;
        left: 4px;
        width: 40px;
        height: 28px;
        background: #007bff;
        border-radius: 50px;
        transition: all 0.3s ease;
        z-index: 1;
        box-shadow: 0 2px 4px rgba(0,123,255,0.2);
    }

    #montoCuotas:checked ~ .toggle-slider {
        transform: translateX(0);
        background: #007bff;
    }

    #porcentajeCuotas:checked ~ .toggle-slider {
        transform: translateX(100%);
        background: #007bff;
    }

    #montoCuotas:checked + .toggle-label,
    #porcentajeCuotas:checked + .toggle-label + .toggle-label {
        color: white;
    }

    .toggle-switch-warning #montoMoras:checked ~ .toggle-slider {
        transform: translateX(0);
        background: #ffc107;
    }

    .toggle-switch-warning #porcentajeMoras:checked ~ .toggle-slider {
        transform: translateX(100%);
        background: #ffc107;
    }

    .toggle-switch-warning #montoMoras:checked + .toggle-label,
    .toggle-switch-warning #porcentajeMoras:checked + .toggle-label {
        color: #212529;
    }

    .toggle-switch-labels {
        display: flex;
        justify-content: space-between;
        margin-top: 2px;
    }

    .toggle-label-text {
        font-size: 0.65rem;
        color: #6c757d;
        font-weight: 500;
        width: 40px;
        text-align: center;
    }

    .toggle-label:hover {
        color: #495057;
    }

    .toggle-switch:hover {
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.15);
    }

    .toggle-option:focus + .toggle-label {
        outline: 2px solid #007bff;
        outline-offset: 2px;
    }
</style>

<div class="liquidacion-wrapper content-with-footer">
    <div class="container-fluid">
        <!-- Header -->
        <div class="liquidacion-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5>
                        <i class="fas fa-handshake text-primary"></i>
                        Liquidar Convenio #{{ $convenio->id }}
                    </h5>
                    <small class="text-muted">
                        <i class="fas fa-user"></i> {{ $convenio->prestamo->cliente->persona->nombres ?? 'N/A' }} {{ $convenio->prestamo->cliente->persona->ape_pat ?? '' }}
                        <span class="mx-2">|</span>
                        <span class="text-white badge bg-{{ $convenio->estado === \App\Enums\ConvenioEstado::ACTIVO ? 'success' : 'secondary' }} badge-compact">{{ $convenio->estado->label() }}</span>
                    </small>
                </div>
                <div class="d-flex">
                    <div class="total-box">
                        <div class="total-box-label">Total a Liquidar</div>
                        <h2 class="total-box-amount" id="totalALiquidar">S/ 0.00</h2>
                    </div>

                    <!-- Summary -->
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="summary-item-label">Cuotas</div>
                            <div class="summary-item-value text-primary" id="totalCuotasSeleccionadas">S/ 0.00</div>
                            <div class="summary-item-sub" id="totalDescuentoCuotas">- S/ 0.00</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-item-label">Moras</div>
                            <div class="summary-item-value text-danger" id="totalMorasSeleccionadas">S/ 0.00</div>
                            <div class="summary-item-sub text-warning" id="totalDescuentoMoras">- S/ 0.00</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-item-label">Descuentos</div>
                            <div class="summary-item-value" id="totalDescuentos">S/ 0.00</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Columna 1: Cuotas y Moras -->
            <div class="col-md-5">
                <div class="liquidacion-tabs">
                    <ul class="nav nav-tabs nav-tabs-compact" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active"
                                    id="cuotas-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#cuotas"
                                    type="button"
                                    role="tab"
                                    aria-controls="cuotas"
                                    aria-selected="true">
                                <i class="fas fa-receipt me-1"></i> CUOTAS
                                <span class="badge bg-primary badge-compact ms-1 text-white">{{ $cuotasPendientes->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link"
                                    id="moras-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#moras"
                                    type="button"
                                    role="tab"
                                    aria-controls="moras"
                                    aria-selected="false">
                                <i class="fas fa-exclamation-triangle me-1"></i> MORAS
                                <span class="badge bg-danger badge-compact ms-1  text-white">{{ $morasPendientes }}</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content tab-content-compact">
                        <!-- Tab Cuotas -->
                        <div class="tab-pane fade show active" id="cuotas" role="tabpanel" aria-labelledby="cuotas-tab">
                            <div class="alert alert-info alert-compact mb-2" style="background: #e3f2fd; border-left: 3px solid #2196f3;">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-info-circle me-2 mt-1" style="color: #2196f3;"></i>
                                    <div style="flex: 1;">
                                        <strong>Liquidación de Convenio</strong>
                                        <p class="mb-0 mt-1" style="font-size: 0.8rem; line-height: 1.4;">
                                            Se liquidarán todas las cuotas pendientes del convenio.
                                            Las moras se calculan por separado y pueden tener descuentos.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="alert-compact mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><i class="fas fa-wallet me-1"></i> Total cuotas</strong>
                                    <span class="fw-bold">S/ {{ number_format($totalCuotas, 2) }}</span>
                                </div>
                            </div>

                            <div class="item-list-compact">
                                @forelse($cuotasPendientes as $cuota)
                                <div class="item-compact">
                                    <div class="item-compact-left">
                                        <div class="item-number">{{ $cuota->numero_cuota }}</div>
                                        <div class="item-info">
                                            <div class="item-title">Cuota #{{ $cuota->numero_cuota }}</div>
                                            <div class="item-subtitle">
                                                <i class="far fa-calendar me-1"></i>
                                                Venc: {{ $cuota->fecha_vencimiento->format('d/m/Y') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="item-compact-right">
                                        <div class="item-amount">S/ {{ number_format($cuota->saldo_pendiente, 2) }}</div>
                                        @php
                                            $estadoClass = match($cuota->estado) {
                                                \App\Enums\CuotaConvenio::PAGADO => 'success',
                                                \App\Enums\CuotaConvenio::PARCIAL => 'warning',
                                                \App\Enums\CuotaConvenio::VENCIDO => 'danger',
                                                default => 'danger'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $estadoClass }} badge-compact text-white">{{ $cuota->estado->label() }}</span>
                                    </div>
                                    <input type="hidden" class="cuota-obligatoria" value="{{ $cuota->id }}" data-monto="{{ $cuota->saldo_pendiente }}">
                                </div>
                                @empty
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                                    <p class="mb-0 small">No hay cuotas pendientes</p>
                                </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Tab Moras -->
                        <div class="tab-pane fade" id="moras" role="tabpanel" aria-labelledby="moras-tab">
                            <div class="alert-compact mb-2" style="background: #fff3cd; border-left-color: #ffc107;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><i class="fas fa-exclamation-triangle me-1"></i> Moras obligatorias</strong>
                                    <span class="fw-bold text-danger">S/ {{ number_format($totalMoras, 2) }}</span>
                                </div>
                            </div>

                            <div class="item-list-compact">
                                @forelse($todasLasMoras as $mora)
                                <div class="item-compact">
                                    <div class="item-compact-left">
                                        <div class="item-number danger">M{{ $mora->id }}</div>
                                        <div class="item-info">
                                            <div class="item-title">Mora #{{ $mora->id }}</div>
                                            <div class="item-subtitle">
                                                <i class="fas fa-link me-1"></i>
                                                Cuota #{{ $mora->cuotaConvenio->numero_cuota ?? 'N/A' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="item-compact-right">
                                        <div class="item-amount danger">S/ {{ number_format($mora->monto - $mora->monto_pagado, 2) }}</div>
                                        <span class="badge bg-warning badge-compact">Pendiente</span>
                                    </div>
                                    <input type="hidden" class="mora-obligatoria" value="{{ $mora->id }}" data-monto="{{ $mora->monto - $mora->monto_pagado }}">
                                </div>
                                @empty
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                                    <p class="mb-0 small">No hay moras pendientes</p>
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna 2: Descuentos -->
            <div class="col-md-3">
                <div class="section-compact">
                    <div class="section-header">
                        <i class="fas fa-percentage"></i> Descuentos
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <div class="discount-group">
                                <div class="discount-header"><i class="fas fa-receipt"></i> Cuotas</div>

                                <!-- Toggle Switch Mejorado para Cuotas -->
                                <div class="toggle-switch-wrapper mb-2">
                                    <div class="toggle-switch">
                                        <input type="radio" class="toggle-option" name="tipoCuotas" id="montoCuotas" value="monto" checked>
                                        <label for="montoCuotas" class="toggle-label">
                                            <span class="toggle-text">S/</span>
                                        </label>

                                        <input type="radio" class="toggle-option" name="tipoCuotas" id="porcentajeCuotas" value="porcentaje">
                                        <label for="porcentajeCuotas" class="toggle-label">
                                            <span class="toggle-text">%</span>
                                        </label>

                                        <span class="toggle-slider"></span>
                                    </div>
                                </div>

                                <input type="number" class="form-control form-control-sm input-compact" id="valorDescuentoCuotas"
                                    min="0" max="100" step="0.01" value="0" placeholder="0">
                                <div class="discount-total mt-1">
                                    <span>Total:</span>
                                    <span id="cuotasParaDescuento" class="fw-bold">S/ 0.00</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="discount-group">
                                <div class="discount-header text-warning"><i class="fas fa-exclamation-triangle"></i> Moras</div>

                                <!-- Toggle Switch Corregido para Moras -->
                                <div class="toggle-switch-wrapper mb-2">
                                    <div class="toggle-switch toggle-switch-warning">
                                        <input type="radio" class="toggle-option" name="tipoMoras" id="montoMoras" value="monto" checked>
                                        <label for="montoMoras" class="toggle-label">
                                            <span class="toggle-text">S/</span>
                                        </label>

                                        <input type="radio" class="toggle-option" name="tipoMoras" id="porcentajeMoras" value="porcentaje">
                                        <label for="porcentajeMoras" class="toggle-label">
                                            <span class="toggle-text">%</span>
                                        </label>
                                        <span class="toggle-slider"></span>
                                    </div>
                                </div>

                                <input type="number" class="form-control form-control-sm input-compact" id="valorDescuento"
                                    min="0" max="100" step="0.01" value="0" placeholder="0">
                                <div class="discount-total mt-1">
                                    <span>Total:</span>
                                    <span id="morasParaDescuento" class="fw-bold">S/ 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna 3: Método de Pago -->
            <div class="col-md-4">
                <div class="columna-descuentos">
                    <!-- Método de Pago -->
                    <div class="section-compact">
                        <div class="section-header">
                            <i class="fas fa-money-bill-wave"></i> Método de Pago
                        </div>

                        <div class="payment-methods">
                            @foreach($metodosDePago as $metodo)
                            <div class="payment-method">
                                <input type="radio" name="metodoPago" id="metodoPago{{ $metodo->id }}"
                                       value="{{ $metodo->id }}" class="metodo-pago-radio">
                                <label for="metodoPago{{ $metodo->id }}">
                                    <i class="fas fa-{{ $metodo->id == 1 ? 'money-bill' : ($metodo->id == 2 ? 'exchange-alt' : 'credit-card') }}"></i>
                                    <span>{{ $metodo->metodo_pago }}</span>
                                </label>
                            </div>
                            @endforeach
                        </div>

                        <!-- Campos extra -->
                        <div id="extra-fields" style="display: none;">
                            <div class="extra-fields">
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label-compact">Nro. Operación</label>
                                        <input type="text" class="form-control form-control-compact" id="nro_operacion" placeholder="000000">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label-compact">Fecha</label>
                                        <input type="datetime-local" class="form-control form-control-compact" id="fecha_operacion">
                                    </div>
                                </div>
                                <label class="form-label-compact">Voucher</label>
                                <input type="file" class="form-control form-control-compact" id="voucher" accept="image/*">
                            </div>
                        </div>

                        <div id="codigo-container" style="display: none;">
                            <div class="extra-fields">
                                <label class="form-label-compact">Fecha de Liquidación</label>
                                <input type="datetime-local" class="form-control form-control-compact" id="fecha_codigo">
                            </div>
                        </div>

                        <div class="mt-2">
                            <label class="form-label-compact">Comentario (Opcional)</label>
                            <textarea class="form-control form-control-compact" id="comentario" rows="2"
                                      placeholder="Comentario..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer Sticky -->
<div class="liquidacion-footer">
    <div class="d-flex justify-content-between align-items-center">
        <small class="text-white">.</small>
        <div class="d-flex justify-space-between">
            <a href="{{ route('admin.convenios.show', $convenio->id) }}" class="btn btn-secondary btn-action mr-3">
                <i class="fas fa-times"></i> Cancelar
            </a>
            <button type="button" class="btn btn-success btn-action" id="confirmarLiquidacion">
                <i class="fas fa-check-circle"></i> Procesar Liquidación
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let totalCuotasSeleccionadas = 0;
    let totalMorasSeleccionadas = 0;
    let descuentoCuotas = 0;
    let descuentoMoras = 0;

    // Inicializar tabs manualmente para asegurar compatibilidad
    const morasTabEl = document.getElementById('moras-tab');
    const cuotasTabEl = document.getElementById('cuotas-tab');

    if (morasTabEl) {
        morasTabEl.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });

            document.getElementById('moras').classList.add('show', 'active');
            this.classList.add('active');
        });
    }

    if (cuotasTabEl) {
        cuotasTabEl.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });

            document.getElementById('cuotas').classList.add('show', 'active');
            this.classList.add('active');
        });
    }

    document.querySelectorAll('.metodo-pago-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('extra-fields').style.display = 'none';
            document.getElementById('codigo-container').style.display = 'none';

            if (this.value === '1') {
                document.getElementById('codigo-container').style.display = 'block';
            } else if (this.value === '2' || this.value === '3') {
                document.getElementById('extra-fields').style.display = 'block';
            }
        });
    });

    function actualizarTotales() {
        totalCuotasSeleccionadas = 0;
        document.querySelectorAll('.cuota-obligatoria').forEach(input => {
            totalCuotasSeleccionadas += parseFloat(input.dataset.monto) || 0;
        });

        totalMorasSeleccionadas = 0;
        document.querySelectorAll('.mora-obligatoria').forEach(input => {
            totalMorasSeleccionadas += parseFloat(input.dataset.monto) || 0;
        });

        calcularDescuentoCuotas();
        calcularDescuentoMoras();

        const totalSinDescuentos = totalCuotasSeleccionadas + totalMorasSeleccionadas;
        const totalDescuentos = descuentoCuotas + descuentoMoras;
        const totalFinal = totalSinDescuentos - totalDescuentos;

        document.getElementById('totalCuotasSeleccionadas').textContent = `S/ ${totalCuotasSeleccionadas.toFixed(2)}`;
        document.getElementById('totalMorasSeleccionadas').textContent = `S/ ${totalMorasSeleccionadas.toFixed(2)}`;
        document.getElementById('totalDescuentoCuotas').textContent = `- S/ ${descuentoCuotas.toFixed(2)}`;
        document.getElementById('totalDescuentoMoras').textContent = `- S/ ${descuentoMoras.toFixed(2)}`;
        document.getElementById('totalDescuentos').textContent = `S/ ${totalDescuentos.toFixed(2)}`;
        document.getElementById('totalALiquidar').textContent = `S/ ${totalFinal.toFixed(2)}`;
        document.getElementById('morasParaDescuento').textContent = `S/ ${totalMorasSeleccionadas.toFixed(2)}`;
        document.getElementById('cuotasParaDescuento').textContent = `S/ ${totalCuotasSeleccionadas.toFixed(2)}`;
    }

    function calcularDescuentoCuotas() {
        const tipoDescuento = document.querySelector('input[name="tipoCuotas"]:checked')?.value || 'porcentaje';
        const valorDescuento = parseFloat(document.getElementById('valorDescuentoCuotas').value) || 0;

        if (totalCuotasSeleccionadas === 0) {
            descuentoCuotas = 0;
            return;
        }

        if (tipoDescuento === 'porcentaje') {
            descuentoCuotas = (totalCuotasSeleccionadas * valorDescuento) / 100;
            if (descuentoCuotas > totalCuotasSeleccionadas) descuentoCuotas = totalCuotasSeleccionadas;
        } else {
            descuentoCuotas = Math.min(valorDescuento, totalCuotasSeleccionadas);
        }
    }

    function calcularDescuentoMoras() {
        const tipoDescuento = document.querySelector('input[name="tipoMoras"]:checked')?.value || 'porcentaje';
        const valorDescuento = parseFloat(document.getElementById('valorDescuento').value) || 0;

        if (totalMorasSeleccionadas === 0) {
            descuentoMoras = 0;
            return;
        }

        if (tipoDescuento === 'porcentaje') {
            descuentoMoras = (totalMorasSeleccionadas * valorDescuento) / 100;
            if (descuentoMoras > totalMorasSeleccionadas) descuentoMoras = totalMorasSeleccionadas;
        } else {
            descuentoMoras = Math.min(valorDescuento, totalMorasSeleccionadas);
        }
    }

    document.querySelectorAll('input[name="tipoCuotas"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const valorInput = document.getElementById('valorDescuentoCuotas');
            valorInput.max = this.value === 'porcentaje' ? '100' : totalCuotasSeleccionadas.toFixed(2);
            valorInput.value = '0';
            actualizarTotales();
        });
    });

    document.getElementById('valorDescuentoCuotas').addEventListener('input', actualizarTotales);

    document.querySelectorAll('input[name="tipoMoras"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const valorInput = document.getElementById('valorDescuento');
            valorInput.max = this.value === 'porcentaje' ? '100' : totalMorasSeleccionadas.toFixed(2);
            valorInput.value = '0';
            actualizarTotales();
        });
    });

    document.getElementById('valorDescuento').addEventListener('input', actualizarTotales);

    actualizarTotales();

    document.getElementById('confirmarLiquidacion').addEventListener('click', function() {
        const metodoPagoSeleccionado = document.querySelector('.metodo-pago-radio:checked');
        if (!metodoPagoSeleccionado) {
            Swal.fire({ icon: 'warning', title: 'Método de pago requerido', text: 'Debe seleccionar un método de pago' });
            return;
        }

        if (metodoPagoSeleccionado.value === '1' && !document.getElementById('fecha_codigo').value) {
            Swal.fire({ icon: 'warning', title: 'Fecha requerida', text: 'Debe ingresar la fecha de liquidación' });
            return;
        }

        if ((metodoPagoSeleccionado.value === '2' || metodoPagoSeleccionado.value === '3') &&
            (!document.getElementById('nro_operacion').value.trim() || !document.getElementById('fecha_operacion').value)) {
            Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Complete número de operación y fecha' });
            return;
        }

        Swal.fire({
            title: '¿Confirmar liquidación?',
            html: `<p>Total: <strong>${document.getElementById('totalALiquidar').textContent}</strong></p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, liquidar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) procesarLiquidacion();
        });
    });

    function procesarLiquidacion() {
        const confirmarBtn = document.getElementById('confirmarLiquidacion');
        confirmarBtn.disabled = true;
        confirmarBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

        const datosLiquidacion = {
            convenio_id: {{ $convenio->id }},
            cuotas_seleccionadas: [],
            moras_seleccionadas: [],
            metodo_pago_id: document.querySelector('.metodo-pago-radio:checked').value,
            total_liquidar: parseFloat(document.getElementById('totalALiquidar').textContent.replace('S/ ', '').replace(',', '')),
            descuento_cuotas: descuentoCuotas,
            descuento_moras: descuentoMoras,
            comentario: document.getElementById('comentario').value.trim(),
            _token: '{{ csrf_token() }}'
        };

        document.querySelectorAll('.cuota-obligatoria').forEach(input => {
            datosLiquidacion.cuotas_seleccionadas.push(parseInt(input.value));
        });

        document.querySelectorAll('.mora-obligatoria').forEach(input => {
            datosLiquidacion.moras_seleccionadas.push(parseInt(input.value));
        });

        if (datosLiquidacion.metodo_pago_id === '1') {
            datosLiquidacion.fecha_codigo = document.getElementById('fecha_codigo').value;
        } else {
            datosLiquidacion.nro_operacion = document.getElementById('nro_operacion').value.trim();
            datosLiquidacion.fecha_operacion = document.getElementById('fecha_operacion').value;

            const voucherInput = document.getElementById('voucher');
            if (voucherInput.files.length > 0) {
                const formData = new FormData();
                Object.keys(datosLiquidacion).forEach(key => {
                    if (Array.isArray(datosLiquidacion[key])) {
                        datosLiquidacion[key].forEach((item, index) => {
                            formData.append(`${key}[${index}]`, item);
                        });
                    } else {
                        formData.append(key, datosLiquidacion[key]);
                    }
                });
                formData.append('voucher', voucherInput.files[0]);
                enviarLiquidacion(formData, true);
                return;
            }
        }

        enviarLiquidacion(datosLiquidacion, false);
    }

    function enviarLiquidacion(datos, esFormData) {
        fetch('/admin/convenios/{{ $convenio->id }}/liquidar', {
            method: 'POST',
            headers: esFormData ? {} : { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: esFormData ? datos : JSON.stringify(datos)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Liquidación Exitosa!',
                    text: data.message || 'El convenio ha sido liquidado',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '{{ route("admin.convenios.show", $convenio->id) }}';
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Error al procesar' });
            }
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo conectar con el servidor' });
        })
        .finally(() => {
            document.getElementById('confirmarLiquidacion').disabled = false;
            document.getElementById('confirmarLiquidacion').innerHTML = '<i class="fas fa-check-circle"></i> Procesar Liquidación';
        });
    }
});
</script>
@endsection
