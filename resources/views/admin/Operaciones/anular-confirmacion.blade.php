@extends('layouts.admin')

@section('content')
<div class="min-vh-100" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
    <div class="container-fluid py-2">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb bg-transparent p-0 mb-0 small">
                <li class="breadcrumb-item">
                    <a href="{{ route('admin.index') }}" class="text-decoration-none text-muted hover-text-primary">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ url('/admin/prestamos/' . $operacion->prestamo->id) }}" class="text-decoration-none text-muted hover-text-primary">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Préstamo #{{ $operacion->prestamo->id }}
                    </a>
                </li>
                <li class="breadcrumb-item active fw-medium">Anular Operación</li>
            </ol>
        </nav>

        <!-- Main Layout -->
        <div class="row g-2">
            <!-- Left Column - Operation Details -->
            <div class="col-xl-8">
                <!-- Header Card -->
                <div class="card border-0 shadow-sm mb-2" style="backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95);">
                    <div class="card-body p-2">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="d-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10" 
                                     style="width: 48px; height: 48px;">
                                    <i class="fas fa-ban text-danger"></i>
                                </div>
                            </div>
                            <div class="col">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h4 class="fw-bold text-dark mb-1">Anular Operación #{{ $operacion->id }}</h4>
                                        <div class="d-flex align-items-center text-muted mb-2 small">
                                            <span class="badge bg-dark bg-opacity-10 text-dark px-2 py-1 rounded-pill me-2">
                                                {{ $operacion->fecha->format('d/m/Y H:i') }}
                                            </span>
                                            <span>{{ optional($operacion->metodoDePago)->metodo_pago ?? 'N/A' }}</span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="text-muted me-2 small">Monto:</span>
                                            <span class="fs-5 fw-bold text-danger">S/. {{ number_format($operacion->abono, 2) }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <div class="small text-muted">Receptor</div>
                                        <div class="small fw-medium">{{ optional($operacion->user)->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Client & Warning -->
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100" style="backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95);">
                            <div class="card-body p-2">
                                <h6 class="fw-semibold text-secondary mb-2 small text-uppercase">Cliente</h6>
                                <h6 class="fw-bold text-dark mb-1">
                                    {{ optional($operacion->cliente->persona)->nombres ?? 'N/A' }} 
                                    {{ optional($operacion->cliente->persona)->apellidos ?? '' }}
                                </h6>
                                <p class="text-muted mb-0 small">DNI: {{ optional($operacion->cliente->persona)->documento ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-warning border-0 shadow-sm h-100 mb-0" style="backdrop-filter: blur(10px); background: rgba(255, 193, 7, 0.1);">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-exclamation-triangle text-warning me-2 mt-1"></i>
                                <div>
                                    <h6 class="fw-bold text-warning mb-1 small">¡Acción Irreversible!</h6>
                                    <p class="small text-warning mb-0">Se revertirán todos los pagos y estados asociados permanentemente.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Impact Analysis -->
                <div class="card border-0 shadow-sm" style="backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95);">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-info bg-opacity-10 p-1 me-2">
                                <i class="fas fa-analytics text-info small"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-0">Análisis de Impacto</h6>
                        </div>

                        @if($operacion->cuotas->isNotEmpty())
                        <!-- Cuotas Impact -->
                        <div class="mb-2">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-calendar-alt text-primary me-2"></i>
                                    <span class="fw-semibold small">Cuotas Afectadas ({{ $operacion->cuotas->count() }})</span>
                                </div>
                                <button class="btn btn-sm btn-link text-muted p-0" type="button" data-bs-toggle="collapse" data-bs-target="#cuotasCollapse">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>

                            <div class="mb-2">
                                @foreach($operacion->cuotas as $cuota)
                                    <span class="badge bg-light text-dark border me-1 mb-1 small">Cuota {{ $cuota->numero }}</span>
                                @endforeach
                            </div>

                            <div class="collapse" id="cuotasCollapse">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="fw-medium small">Cuota</th>
                                                <th class="fw-medium small">Estado Actual</th>
                                                <th class="fw-medium small">Pagado</th>
                                                <th class="fw-medium small">→ Nuevo</th>
                                                <th class="fw-medium small text-end">→ Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($operacion->cuotas as $cuota)
                                                @php
                                                    $nuevoMonto = max(0, $cuota->monto_pagado - $operacion->abono);
                                                    $nuevoEstado = $nuevoMonto == 0 
                                                        ? (now()->greaterThan($cuota->fecha_pago) ? 'VENCIDO' : 'PENDIENTE')
                                                        : ($nuevoMonto >= $cuota->monto ? 'PAGADO' : 'PARCIAL');
                                                @endphp
                                                <tr>
                                                    <td class="fw-medium">{{ $cuota->numero }}</td>
                                                    <td>
                                                        <span class="badge badge-sm bg-{{ $cuota->estado->value == 2 ? 'success' : ($cuota->estado->value == 1 ? 'warning' : 'danger') }}">
                                                            {{ $cuota->estado->value == 2 ? 'PAGADO' : ($cuota->estado->value == 1 ? 'PARCIAL' : ($cuota->estado->value == 3 ? 'VENCIDO' : 'PENDIENTE')) }}
                                                        </span>
                                                    </td>
                                                    <td class="small">S/. {{ number_format($cuota->monto_pagado, 2) }}</td>
                                                    <td>
                                                        <span class="badge badge-sm bg-{{ $nuevoEstado == 'PAGADO' ? 'success' : ($nuevoEstado == 'PARCIAL' ? 'warning' : 'secondary') }}">
                                                            {{ $nuevoEstado }}
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="fw-medium text-danger small">S/. {{ number_format($nuevoMonto, 2) }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($operacion->morasCuota->isNotEmpty())
                        <!-- Moras Impact -->
                        <div class="mb-2">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clock text-warning me-2"></i>
                                    <span class="fw-semibold small">Moras Afectadas ({{ $operacion->morasCuota->count() }})</span>
                                </div>
                                <button class="btn btn-sm btn-link text-muted p-0" type="button" data-bs-toggle="collapse" data-bs-target="#morasCollapse">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                            
                            <div class="small text-muted mb-2">
                                Total a revertir: <strong class="text-danger">S/. {{ number_format($operacion->morasCuota->sum('monto_pagado'), 2) }}</strong>
                            </div>

                            <div class="collapse" id="morasCollapse">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="fw-medium small">Cuota</th>
                                                <th class="fw-medium small">Días</th>
                                                <th class="fw-medium small">Actual</th>
                                                <th class="fw-medium small">→ Nuevo</th>
                                                <th class="fw-medium small text-end">→ Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($operacion->morasCuota as $mora)
                                                @php
                                                    $montoAplicado = min($operacion->abono, $mora->monto_pagado);
                                                    $nuevoMonto = max(0, $mora->monto_pagado - $montoAplicado);
                                                    $nuevoEstado = $nuevoMonto == 0 ? 'PENDIENTE' : ($nuevoMonto >= $mora->monto ? 'PAGADO' : 'PARCIAL');
                                                @endphp
                                                <tr>
                                                    <td class="fw-medium">{{ optional($mora->cuota)->numero ?? 'N/A' }}</td>
                                                    <td><span class="badge bg-light text-dark small">{{ $mora->dias_mora ?? 0 }}</span></td>
                                                    <td>
                                                        <span class="badge badge-sm bg-{{ $mora->estado->value == 2 ? 'success' : ($mora->estado->value == 1 ? 'warning' : 'secondary') }}">
                                                            {{ $mora->estado->value == 2 ? 'PAGADO' : ($mora->estado->value == 1 ? 'PARCIAL' : ($mora->estado->value == 3 ? 'REGULARIZADA' : 'PENDIENTE')) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-sm bg-{{ $nuevoEstado == 'PAGADO' ? 'success' : ($nuevoEstado == 'PARCIAL' ? 'warning' : 'secondary') }}">
                                                            {{ $nuevoEstado }}
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="fw-medium text-danger small">S/. {{ number_format($nuevoMonto, 2) }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($operacion->operacionesRelacionadas->isNotEmpty())
                        <!-- Related Operations -->
                        <div class="border-top pt-2">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-link text-secondary me-2"></i>
                                <span class="fw-semibold small">Operaciones Relacionadas ({{ $operacion->operacionesRelacionadas->count() }})</span>
                            </div>
                            <div>
                                @foreach($operacion->operacionesRelacionadas as $rel)
                                    <span class="badge bg-light text-dark border me-1 mb-1 small">Op. {{ $rel->id }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column - Action Panel -->
            <div class="col-xl-4">
                <div class="sticky-top" style="top: 1rem;">
                    <!-- Action Form -->
                    <div class="card border-0 shadow-sm mb-2" style="backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95);">
                        <div class="card-body p-2">
                            <div class="text-center mb-2">
                                <div class="rounded-circle bg-success bg-opacity-10 p-1 d-inline-flex align-items-center justify-content-center mb-1">
                                    <i class="fas fa-shield-check text-success small"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">Confirmación Requerida</h6>
                                <p class="text-muted small mb-0">Complete la información para proceder</p>
                            </div>

                            <form method="POST" action="{{ route('admin.operaciones.procesar-anulacion', $operacion->id) }}" onsubmit="return confirmarAnulacion()">
                                @csrf

                                <!-- Campos ocultos para redirigir a convenio si corresponde -->
                                @if(isset($returnTo) && $returnTo === 'convenio' && isset($convenioId))
                                    <input type="hidden" name="return_to" value="convenio">
                                    <input type="hidden" name="convenio_id" value="{{ $convenioId }}">
                                @endif

                                <div class="mb-2">
                                    <label for="justificacion" class="form-label fw-medium small">
                                        <i class="fas fa-edit me-2 text-primary"></i>Justificación *
                                    </label>
                                    <textarea 
                                        class="form-control form-control-sm border-0 bg-light" 
                                        id="justificacion" 
                                        name="justificacion" 
                                        rows="3" 
                                        placeholder="¿Por qué está anulando esta operación?"
                                        style="resize: none;"
                                        required
                                    ></textarea>
                                    <div class="form-text small text-muted">
                                        Se registrará permanentemente en el historial.
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <div class="form-check p-1 rounded bg-light">
                                        <input class="form-check-input" type="checkbox" id="confirmacion" name="confirmacion" required>
                                        <label class="form-check-label fw-medium text-dark small" for="confirmacion">
                                            He revisado el impacto y confirmo la anulación.
                                        </label>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-danger fw-semibold">
                                        <i class="fas fa-ban me-2"></i>
                                        Confirmar Anulación
                                    </button>
                                    <a href="{{ url('/admin/prestamos/' . $operacion->prestamo->id) }}" class="btn btn-light fw-medium border">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        Volver al Préstamo
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Summary Card -->
                    <div class="card border-0 shadow-sm" style="backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95);">
                        <div class="card-body p-2">
                            <h6 class="fw-bold text-dark mb-2">
                                <i class="fas fa-chart-pie me-2 text-primary"></i>Resumen de Impacto
                            </h6>
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="d-flex align-items-center justify-content-between p-1 rounded bg-light">
                                        <div>
                                            <div class="small text-muted">Monto Total</div>
                                            <div class="fw-bold text-danger">S/. {{ number_format($operacion->abono, 2) }}</div>
                                        </div>
                                        <div class="rounded-circle bg-danger bg-opacity-10 p-1">
                                            <i class="fas fa-dollar-sign text-danger small"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-1 rounded bg-light">
                                        <div class="fw-bold text-primary">{{ $operacion->cuotas->count() }}</div>
                                        <div class="small text-muted">Cuotas</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-1 rounded bg-light">
                                        <div class="fw-bold text-warning">{{ $operacion->morasCuota->count() }}</div>
                                        <div class="small text-muted">Moras</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarAnulacion() {
    const cuotasCount = {{ $operacion->cuotas->count() }};
    const morasCount = {{ $operacion->morasCuota->count() }};
    const monto = {{ $operacion->abono }};
    
    return Swal.fire({
        title: '¿Confirmar anulación?',
        html: `
            <div class="text-start">
                <p class="mb-2"><strong>Esta acción no se puede deshacer.</strong></p>
                <ul class="list-unstyled small">
                    <li class="mb-1"><i class="fas fa-dollar-sign text-danger me-2"></i>Monto: S/. ${monto.toFixed(2)}</li>
                    ${cuotasCount > 0 ? `<li class="mb-1"><i class="fas fa-calendar text-primary me-2"></i>${cuotasCount} cuota(s) afectada(s)</li>` : ''}
                    ${morasCount > 0 ? `<li class="mb-1"><i class="fas fa-clock text-warning me-2"></i>${morasCount} mora(s) afectada(s)</li>` : ''}
                </ul>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-ban me-2"></i> Sí, anular',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'rounded-3 shadow-lg',
            confirmButton: 'rounded-2',
            cancelButton: 'rounded-2'
        }
    }).then((result) => {
        return result.isConfirmed;
    });
}
</script>

<style>
.hover-text-primary:hover {
    color: #0d6efd !important;
}

.badge-sm {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
}

.card {
    border-radius: 12px !important;
    transition: all 0.2s ease;
}

.card:hover {
    transform: translateY(-1px);
}

.btn {
    border-radius: 8px !important;
    transition: all 0.15s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.bg-opacity-10 {
    --bs-bg-opacity: 0.1;
}

.sticky-top {
    z-index: 1020;
}

.table > :not(caption) > * > * {
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding: 0.5rem 0.75rem;
}

.form-control {
    border-radius: 8px !important;
    border: 1px solid transparent !important;
    background-color: #f8f9fa !important;
    transition: all 0.15s ease;
}

.form-control:focus {
    border-color: #0d6efd !important;
    background-color: #ffffff !important;
    box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.1) !important;
}

.alert {
    border-radius: 8px !important;
}

@media (max-width: 1199.98px) {
    .sticky-top {
        position: static !important;
    }
}
</style>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection