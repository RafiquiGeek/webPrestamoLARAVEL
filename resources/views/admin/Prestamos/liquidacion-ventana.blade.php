@extends('layouts.admin')

@section('title', 'Liquidar Préstamo #' . $prestamo->id)

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
        /*overflow: hidden;*/
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
        /*background: #f8f9fa;*/
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

    /* Abonos */
    .abonos-box {
        background: #f8f9fa;
        border: 2px dashed #28a745;
        border-radius: 6px;
        padding: 0.75rem;
    }

    .abonos-box.active {
        background: #d4edda;
        border-style: solid;
    }

    .abonos-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .abonos-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #28a745;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .abonos-list {
        max-height: 60px;
        overflow-y: auto;
        margin-bottom: 0.5rem;
    }

    .abono-item {
        display: flex;
        justify-content: space-between;
        padding: 0.25rem 0;
        font-size: 0.75rem;
        border-bottom: 1px solid #e9ecef;
    }

    .abono-item:last-child {
        border-bottom: none;
    }

    .abonos-total {
        display: flex;
        justify-content: space-between;
        font-weight: 700;
        font-size: 0.875rem;
        color: #28a745;
    }

    /* Badges */
    .badge-compact {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-weight: 600;
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
    /* Estilos para Toggle Switch Mejorado */
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

/* Estado activo para Cuotas - Monto seleccionado */
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

/* Estado activo para Moras - Monto seleccionado primero */
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

/* Labels descriptivos */
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

/* Efectos hover mejorados */
.toggle-label:hover {
    color: #495057;
}

.toggle-switch:hover {
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.15);
}

/* Estados de foco para accesibilidad */
.toggle-option:focus + .toggle-label {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

/* Toggle Switch para Abonos a Favor */
.toggle-switch-abonos {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 24px;
    cursor: pointer;
}

.toggle-switch-abonos input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider-abonos {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    border-radius: 24px;
    transition: all 0.3s ease;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);
}

.toggle-slider-abonos:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.toggle-switch-abonos input:checked + .toggle-slider-abonos {
    background-color: #28a745;
}

.toggle-switch-abonos input:checked + .toggle-slider-abonos:before {
    transform: translateX(24px);
}

.toggle-switch-abonos:hover .toggle-slider-abonos {
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.3), 0 0 8px rgba(40,167,69,0.3);
}

.toggle-switch-abonos input:focus + .toggle-slider-abonos {
    outline: 2px solid #28a745;
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
                        <i class="fas fa-hand-holding-usd text-primary"></i>
                        Liquidar Préstamo #{{ $prestamo->id }}
                    </h5>
                    <small class="text-muted">
                        <i class="fas fa-user"></i> {{ $prestamo->cliente->persona->nombres ?? 'N/A' }}
                        <span class="mx-2">|</span>
                        <span class="text-white badge bg-secondary badge-compact">{{ $prestamo->estado }}</span>
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
                            <div class="summary-item-label">Abonos</div>
                            <div class="summary-item-value text-success" id="totalDescuentoAbonos">- S/ 0.00</div>
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
                                <span class="badge bg-primary badge-compact ms-1 text-white">{{ count($cuotasPendientes) }}</span>
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
                                <span class="badge bg-danger badge-compact ms-1  text-white">{{ $morasPendientes->count() }}</span>
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
                                        <strong>Liquidación Anticipada</strong>
                                        <p class="mb-0 mt-1" style="font-size: 0.8rem; line-height: 1.4;">
                                            En una liquidación anticipada, solo se cobra el <strong>saldo de capital pendiente</strong>. 
                                            No se cobran intereses, comisiones ni IGV de cuotas no vencidas. 
                                            Las moras se calculan por separado y pueden tener descuentos.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert-compact mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><i class="fas fa-wallet me-1"></i> Capital pendiente</strong>
                                    <span class="fw-bold">S/ {{ number_format($totalCuotas, 2) }}</span>
                                </div>
                            </div>

                            <div class="item-list-compact">
                                @forelse($cuotasPendientes as $cuota)
                                <div class="item-compact">
                                    <div class="item-compact-left">
                                        <div class="item-number">{{ $cuota->numero }}</div>
                                        <div class="item-info">
                                            <div class="item-title">Cuota #{{ $cuota->numero }}</div>
                                            <div class="item-subtitle">
                                                <i class="far fa-calendar me-1"></i>
                                                Venc: {{ \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="item-compact-right">
                                        <div class="item-amount">S/ {{ number_format($cuota->saldo_pendiente, 2) }}</div>
                                        @php
                                            $estadoTexto = $cuota->estado ? $cuota->estado->value : 0;
                                            $estadoClass = match($estadoTexto) {
                                                2 => 'success',
                                                1 => 'warning',
                                                0 => 'danger',
                                                3 => 'danger',
                                                default => 'secondary'
                                            };
                                            $estadoNombre = match($estadoTexto) {
                                                2 => 'Pagado',
                                                1 => 'Parcial',
                                                0 => 'Pendiente',
                                                3 => 'Vencido',
                                                default => 'Sin estado'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $estadoClass }} badge-compact text-white">{{ $estadoNombre }}</span>
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
                                @forelse($morasPendientes as $mora)
                                @php
                                    $cuotaRelacionada = $cuotasPendientes->firstWhere('id', $mora->cuota_id);
                                @endphp
                                <div class="item-compact">
                                    <div class="item-compact-left">
                                        <div class="item-number danger">M{{ $mora->id }}</div>
                                        <div class="item-info">
                                            <div class="item-title">Mora #{{ $mora->id }}</div>
                                            <div class="item-subtitle">
                                                <i class="fas fa-link me-1"></i>
                                                Cuota #{{ $cuotaRelacionada->numero ?? $mora->cuota_id }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="item-compact-right">
                                        <div class="item-amount danger">S/ {{ number_format($mora->saldo, 2) }}</div>
                                        <span class="badge bg-warning badge-compact">Pendiente</span>
                                    </div>
                                    <input type="hidden" class="mora-obligatoria" value="{{ $mora->id }}" data-monto="{{ $mora->saldo }}">
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

            <!-- Columna 2: Totales y Summary -->
            <div class="col-md-3">
                <!-- Descuentos -->
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

                        <!-- Abonos -->
                        <div class="abonos-box" id="abonosFavorSection">
                            <div class="abonos-header">
                                <div class="abonos-label">
                                    <i class="fas fa-piggy-bank"></i> Abonos a Favor
                                </div>
                                <label class="toggle-switch-abonos">
                                    <input type="checkbox" id="aplicarAbonosFavor">
                                    <span class="toggle-slider-abonos"></span>
                                </label>
                            </div>
                            <div class="abonos-list">
                                <div class="text-muted text-center small py-1">
                                    <i class="fas fa-spinner fa-spin"></i> Cargando...
                                </div>
                            </div>
                            <div class="abonos-total">
                                <span>Total:</span>
                                <span id="totalAbonosFavor">S/ 0.00</span>
                            </div>
                        </div>
                    </div>
            </div>

            <!-- Columna 3: Descuentos y Método de Pago -->
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
                                <label class="form-label-compact">Código</label>
                                <input type="text" class="form-control form-control-compact" id="codigo" placeholder="Ingrese código">
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
        <small class="text-white">
            .
        </small>
        <div class="d-flex justify-space-between">
            <a href="{{ route('admin.prestamos.show', $prestamo->id) }}" class="btn btn-secondary btn-action mr-3">
                <i class="fas fa-times"></i> Cancelar
            </a>
            <button type="button" class="btn btn-success btn-action" id="confirmarLiquidacion">
                <i class="fas fa-check-circle"></i> Procesar Liquidación
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let totalCuotasSeleccionadas = 0;
    let totalMorasSeleccionadas = 0;
    let descuentoCuotas = 0;
    let descuentoMoras = 0;
    let descuentoAbonos = 0;

    // Inicializar tabs manualmente para asegurar compatibilidad
    const morasTabEl = document.getElementById('moras-tab');
    const cuotasTabEl = document.getElementById('cuotas-tab');

    if (morasTabEl) {
        morasTabEl.addEventListener('click', function(e) {
            e.preventDefault();
            // Ocultar todas las pestañas
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });

            // Mostrar pestaña de moras
            document.getElementById('moras').classList.add('show', 'active');
            this.classList.add('active');
        });
    }

    if (cuotasTabEl) {
        cuotasTabEl.addEventListener('click', function(e) {
            e.preventDefault();
            // Ocultar todas las pestañas
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });

            // Mostrar pestaña de cuotas
            document.getElementById('cuotas').classList.add('show', 'active');
            this.classList.add('active');
        });
    }

    cargarAbonosFavor();

    function cargarAbonosFavor() {
        fetch(`/admin/prestamos/{{ $prestamo->id }}/calcular-liquidacion`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.abonos_mora_favor && data.abonos_mora_favor.length > 0) {
                    mostrarAbonosFavor(data.abonos_mora_favor, data.total_abonos_mora_favor || 0);
                } else {
                    mostrarSinAbonosFavor();
                }
            })
            .catch(() => mostrarSinAbonosFavor());
    }

    function mostrarAbonosFavor(abonos, totalAbonos) {
        const container = document.querySelector('.abonos-list');
        let html = '';
        abonos.forEach(abono => {
            html += `<div class="abono-item">
                <span class="text-muted"><i class="fas fa-piggy-bank me-1"></i>Cuota #${abono.cuota_numero}</span>
                <span class="text-success fw-bold">S/ ${Number(abono.saldo_favor).toFixed(2)}</span>
            </div>`;
        });
        container.innerHTML = html;
        document.getElementById('totalAbonosFavor').textContent = `S/ ${Number(totalAbonos).toFixed(2)}`;
    }

    function mostrarSinAbonosFavor() {
        document.querySelector('.abonos-list').innerHTML = '<div class="text-muted text-center small py-1">No hay abonos disponibles</div>';
        document.getElementById('totalAbonosFavor').textContent = 'S/ 0.00';
    }

    document.getElementById('aplicarAbonosFavor').addEventListener('change', function() {
        const totalAbonosDisponibles = parseFloat(document.getElementById('totalAbonosFavor').textContent.replace('S/ ', '')) || 0;
        const abonosSection = document.getElementById('abonosFavorSection');

        if (this.checked && totalAbonosDisponibles > 0) {
            descuentoAbonos = totalAbonosDisponibles;
            document.getElementById('totalDescuentoAbonos').textContent = `- S/ ${totalAbonosDisponibles.toFixed(2)}`;
            abonosSection.classList.add('active');
        } else {
            descuentoAbonos = 0;
            document.getElementById('totalDescuentoAbonos').textContent = `- S/ 0.00`;
            abonosSection.classList.remove('active');
        }
        actualizarTotales();
    });

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
        const totalDescuentos = descuentoCuotas + descuentoMoras + descuentoAbonos;
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

    // Las moras ahora son obligatorias, no necesitan checkboxes ni evento de selección

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

        if (metodoPagoSeleccionado.value === '1' && !document.getElementById('codigo').value.trim()) {
            Swal.fire({ icon: 'warning', title: 'Código requerido', text: 'Debe ingresar un código' });
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
            prestamo_id: {{ $prestamo->id }},
            cuotas_seleccionadas: [],
            moras_seleccionadas: [],
            metodo_pago_id: document.querySelector('.metodo-pago-radio:checked').value,
            total_liquidar: parseFloat(document.getElementById('totalALiquidar').textContent.replace('S/ ', '').replace(',', '')),
            descuento_cuotas: descuentoCuotas,
            descuento_moras: descuentoMoras,
            aplicar_abonos_favor: document.getElementById('aplicarAbonosFavor').checked,
            monto_abonos_favor: descuentoAbonos,
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
            datosLiquidacion.codigo = document.getElementById('codigo').value.trim();
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
        fetch('/admin/prestamos/{{ $prestamo->id }}/liquidar', {
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
                    text: data.message || 'El préstamo ha sido liquidado',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '{{ route("admin.prestamos.show", $prestamo->id) }}';
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