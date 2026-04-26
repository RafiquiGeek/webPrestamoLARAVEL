@extends('layouts.admin')

@section('title', 'Proceso de Préstamo - ' . $prestamo->cliente->persona->nombres)

@section('content')
<div class="container-fluid">
    <!-- Header compacto -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">Proceso de Préstamo #{{ $prestamo->id }}</h4>
                    <p class="text-muted small mb-0">
                        {{ $prestamo->cliente->persona->nombres }} {{ $prestamo->cliente->persona->ape_pat }} {{ $prestamo->cliente->persona->ape_mat }}
                        <span class="ms-2">DNI: {{ $prestamo->cliente->persona->documento }}</span>
                    </p>
                </div>
                <a href="{{ route('admin.prestamos.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>
    </div>

    <!-- Stepper compacto y limpio -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="proceso-stepper">
                <div class="proceso-step {{ $pasoAprobado ? 'completed' : 'active' }}">
                    <div class="step-indicator">
                        @if($pasoAprobado)
                            <i class="fas fa-check"></i>
                        @else
                            <span>1</span>
                        @endif
                    </div>
                    <div class="step-label">Aprobar</div>
                </div>

                @if(!$fondoExonerado)
                <div class="proceso-line {{ $pasoAprobado ? 'completed' : '' }}"></div>
                <div class="proceso-step {{ $pasoFondoProvisional ? 'completed' : ($pasoAprobado ? 'active' : 'pending') }}">
                    <div class="step-indicator">
                        @if($pasoFondoProvisional)
                            <i class="fas fa-check"></i>
                        @else
                            <span>2</span>
                        @endif
                    </div>
                    <div class="step-label">Fondo Provisional</div>
                </div>
                @endif

                <div class="proceso-line {{ $pasoFondoProvisional ? 'completed' : '' }}"></div>
                <div class="proceso-step {{ $pasoDesembolsado ? 'completed' : ($pasoFondoProvisional ? 'active' : 'pending') }}">
                    <div class="step-indicator">
                        @if($pasoDesembolsado)
                            <i class="fas fa-check"></i>
                        @else
                            <span>{{ $fondoExonerado ? '2' : '3' }}</span>
                        @endif
                    </div>
                    <div class="step-label">Desembolsar</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido del paso actual únicamente -->
    <div class="row">
        <div class="col-md-8 offset-md-2">
            @if(!$pasoAprobado)
                <!-- Paso 1: Aprobar -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row mb-4">
                            <div class="col-6">
                                <small class="text-muted d-block mb-1">Monto Solicitado</small>
                                <strong class="h5">S/. {{ number_format($prestamo->cantidad_solicitada, 2) }}</strong>
                            </div>
                        </div>

                        <form action="{{ route('admin.proceso-prestamo.aprobar', $prestamo->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check me-2"></i>Aprobar Préstamo
                            </button>
                        </form>
                    </div>
                </div>

            @elseif(!$pasoFondoProvisional)
                <!-- Paso 2: Fondo Provisional -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="mb-3">Paso 2: Registrar Fondo Provisional</h5>

                        <form action="{{ route('admin.fondo-provisional.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="prestamo_id" value="{{ $prestamo->id }}">
                            <input type="hidden" name="monto_capital" value="{{ $montoCapital }}">
                            <input type="hidden" name="monto_fondo" value="{{ $montoFondo }}">
                            <input type="hidden" name="desde_proceso" value="1">

                            <!-- Toggle de Exoneración -->
                            <div class="form-check form-switch mb-4">
                                <input type="checkbox" class="form-check-input" id="exonerar_fondo" name="exonerar_fondo" value="1">
                                <label class="form-check-label" for="exonerar_fondo">
                                    <strong>Exonerar Fondo Provisional</strong>
                                    <small class="d-block text-muted">El cliente no deberá entregar el fondo provisional</small>
                                </label>
                            </div>

                            <div id="campos_fondo">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <small class="text-muted d-block mb-1">Capital del Préstamo</small>
                                        <strong class="h6">S/. {{ number_format($montoCapital, 2) }}</strong>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted d-block mb-1">Fondo Provisional (5%)</small>
                                        <strong class="h6 text-warning">S/. {{ number_format($montoFondo, 2) }}</strong>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label small"><strong>Fecha de Entrega</strong> <span class="text-danger">*</span></label>
                                        <input type="date" name="fecha_entrega" id="fecha_entrega" class="form-control" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small"><strong>Monto</strong> <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">S/.</span>
                                            <input type="number" name="monto_personalizado" id="monto_personalizado" class="form-control" value="{{ $montoFondo }}" max="{{ $montoFondo }}" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small"><strong>Método de Pago</strong> <span class="text-danger">*</span></label>
                                        <select name="metodo_pago" id="metodo_pago" class="form-control form-select" required>
                                            <option value="">Seleccionar</option>
                                            <option value="efectivo">Efectivo</option>
                                            <option value="yape">Yape</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Campos Yape -->
                                <div id="campos_yape" style="display: none;">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label small"><strong>Nro. de Operación</strong></label>
                                            <input type="text" name="nro_operacion_yape" id="nro_operacion_yape" class="form-control" placeholder="Número de operación de Yape">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small"><strong>Imagen del Comprobante</strong></label>
                                            <input type="file" name="imagen_yape" id="imagen_yape" class="form-control" accept="image/*">
                                            <small class="text-muted">JPG, PNG, GIF. Máx: 2MB</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div class="mb-4">
                                <label class="form-label small"><strong>Observaciones</strong> <span id="obs-required" class="text-danger" style="display: none;">*</span></label>
                                <textarea name="observaciones" id="observaciones" class="form-control" rows="2" placeholder="Detalles adicionales..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Registrar Fondo Provisional
                            </button>
                        </form>
                    </div>
                </div>

            @elseif(!$pasoDesembolsado)
                <!-- Paso 3: Desembolsar -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="mb-3">Paso 3: Desembolsar</h5>

                        @if($fondoProvisional)
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="row small">
                                <div class="col-4">
                                    <span class="text-muted d-block mb-1">Fondo Provisional</span>
                                    <strong>S/. {{ number_format($fondoProvisional->monto ?? 0, 2) }}</strong>
                                </div>
                                <div class="col-4">
                                    <span class="text-muted d-block mb-1">Asesor</span>
                                    <strong>{{ $fondoProvisional->asesor->name ?? 'N/A' }}</strong>
                                </div>
                                <div class="col-4">
                                    <span class="text-muted d-block mb-1">Estado</span>
                                    <strong>{{ $fondoProvisional->estado }}</strong>
                                </div>
                            </div>
                        </div>
                        @endif

                        <form action="{{ route('admin.prestamos.desembolsar', $prestamo->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="desde_proceso" value="1">

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small"><strong>Fecha de Desembolso</strong> <span class="text-danger">*</span></label>
                                    <input type="date" name="fecha_desembolso" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small"><strong>Método de Pago</strong> <span class="text-danger">*</span></label>
                                    <select name="metodo_pago_id" class="form-select form-control" required>
                                        <option value="">Seleccionar método...</option>
                                        @foreach($metodosDePago as $metodo)
                                            <option value="{{ $metodo->id }}">{{ $metodo->metodo_pago }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small"><strong>Monto a Desembolsar</strong> <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">S/.</span>
                                    <input type="number" name="monto" class="form-control" value="{{ $prestamo->cantidad_solicitada }}" step="0.01" min="0" readonly>
                                </div>
                                <small class="text-muted">El monto coincide con el monto solicitado del préstamo</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small"><strong>Observaciones</strong></label>
                                <textarea name="observaciones" class="form-control" rows="2" placeholder="Observaciones adicionales..." maxlength="500"></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small"><strong>Voucher o Comprobante</strong></label>
                                <input type="file" name="voucher" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                <small class="text-muted">JPG, PNG, PDF (máximo 2MB)</small>
                            </div>

                            <button type="submit" class="btn btn-success" onclick="return confirm('¿Está seguro de realizar el desembolso de S/. {{ number_format($prestamo->cantidad_solicitada, 2) }}?')">
                                <i class="fas fa-sack-dollar me-2"></i>Registrar Desembolso
                            </button>
                        </form>
                    </div>
                </div>

            @else
                <!-- Proceso Completado -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="mb-2">Proceso Completado</h5>
                        <p class="text-muted mb-4">Todos los pasos del proceso de préstamo han sido completados exitosamente.</p>

                        <div class="d-flex gap-2 justify-content-center">
                            <a href="{{ route('admin.prestamos.show', $prestamo->id) }}" class="btn btn-primary">
                                <i class="fas fa-eye me-1"></i> Ver Detalles
                            </a>
                            <a href="{{ route('admin.prestamos.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-list me-1"></i> Lista de Préstamos
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
/* Stepper limpio y compacto */
.proceso-stepper {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.proceso-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
}

.step-indicator {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    background: #e9ecef;
    color: #6c757d;
    transition: all 0.3s ease;
}

.proceso-step.active .step-indicator {
    background: #0d6efd;
    color: white;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
}

.proceso-step.completed .step-indicator {
    background: #198754;
    color: white;
}

.proceso-step.pending .step-indicator {
    background: #f8f9fa;
    color: #adb5bd;
}

.step-label {
    font-size: 13px;
    margin-top: 8px;
    color: #6c757d;
    font-weight: 500;
    white-space: nowrap;
}

.proceso-step.active .step-label {
    color: #0d6efd;
    font-weight: 600;
}

.proceso-step.completed .step-label {
    color: #198754;
}

.proceso-line {
    width: 120px;
    height: 2px;
    background: #e9ecef;
    margin: 0 1rem;
    transition: all 0.3s ease;
}

.proceso-line.completed {
    background: #198754;
}

/* Cards limpios */
.card {
    border-radius: 8px;
}

.card-body h5 {
    color: #212529;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
    .proceso-stepper {
        flex-direction: column;
        padding: 1.5rem 1rem;
    }

    .proceso-line {
        width: 2px;
        height: 40px;
        margin: 0.5rem 0;
    }

    .step-label {
        white-space: normal;
        text-align: center;
    }

    .col-md-8.offset-md-2 {
        margin: 0 !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const exonerarCheckbox = document.getElementById('exonerar_fondo');
    const camposFondo = document.getElementById('campos_fondo');
    const observaciones = document.getElementById('observaciones');
    const obsRequired = document.getElementById('obs-required');
    const metodoPago = document.getElementById('metodo_pago');
    const camposYape = document.getElementById('campos_yape');

    // Manejar exoneración
    if (exonerarCheckbox) {
        exonerarCheckbox.addEventListener('change', function() {
            if (this.checked) {
                camposFondo.style.display = 'none';
                observaciones.setAttribute('required', 'required');
                obsRequired.style.display = 'inline';
                observaciones.placeholder = 'Motivo de la exoneración...';

                // Deshabilitar y remover required de campos ocultos
                document.querySelectorAll('#campos_fondo input, #campos_fondo select').forEach(input => {
                    input.removeAttribute('required');
                    input.setAttribute('disabled', 'disabled');
                });
            } else {
                camposFondo.style.display = 'block';
                observaciones.removeAttribute('required');
                obsRequired.style.display = 'none';
                observaciones.placeholder = 'Detalles adicionales...';

                // Habilitar y restaurar required
                document.querySelectorAll('#campos_fondo input, #campos_fondo select').forEach(input => {
                    input.removeAttribute('disabled');
                });
                document.getElementById('fecha_entrega').setAttribute('required', 'required');
                document.getElementById('monto_personalizado').setAttribute('required', 'required');
                document.getElementById('metodo_pago').setAttribute('required', 'required');
            }
        });
    }

    // Manejar método de pago
    if (metodoPago) {
        metodoPago.addEventListener('change', function() {
            // Ocultar campos de Yape por defecto
            camposYape.style.display = 'none';

            // Remover required de todos los campos condicionales de Yape
            document.querySelectorAll('#campos_yape [required]').forEach(input => {
                input.removeAttribute('required');
            });

            // Mostrar campos de Yape si se selecciona
            if (this.value === 'yape') {
                camposYape.style.display = 'block';
            }
        });
    }
});
</script>
@endsection
