@extends('layouts.admin')

@section('title', 'Liquidar Convenio Flexible #' . $convenio->id)

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

    .section-compact {
        margin-bottom: 1rem;
        border-radius: 15px;
        background: white;
        padding: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
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

    .extra-fields {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 0.75rem;
        margin-top: 0.75rem;
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

    .input-compact {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
        border-radius: 4px;
    }

    .alert-compact {
        background: #e7f3ff;
        border-left: 3px solid #007bff;
        padding: 0.75rem;
        border-radius: 6px;
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }

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

    .btn-action {
        font-size: 0.875rem;
        padding: 0.5rem 1.25rem;
        font-weight: 600;
        border-radius: 6px;
    }

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

    #montoDescuento:checked ~ .toggle-slider {
        transform: translateX(0);
    }

    #porcentajeDescuento:checked ~ .toggle-slider {
        transform: translateX(100%);
    }

    #montoDescuento:checked + .toggle-label,
    #porcentajeDescuento:checked + .toggle-label + .toggle-label {
        color: white;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-size: 0.8rem;
        color: #6c757d;
        font-weight: 500;
    }

    .info-value {
        font-size: 0.9rem;
        font-weight: 700;
        color: #495057;
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
                        Liquidar Convenio Flexible #{{ $convenio->id }}
                    </h5>
                    <small class="text-muted">
                        <i class="fas fa-user"></i> {{ $convenio->prestamo->cliente->persona->nombres ?? 'N/A' }} {{ $convenio->prestamo->cliente->persona->ape_pat ?? '' }}
                        <span class="mx-2">|</span>
                        <span class="badge bg-info text-white">Flexible</span>
                        <span class="badge bg-success text-white">{{ $convenio->estado->label() }}</span>
                    </small>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <div class="total-box">
                        <div class="total-box-label">Total a Liquidar</div>
                        <h2 class="total-box-amount" id="totalALiquidar">S/ {{ number_format($saldoPendiente, 2) }}</h2>
                    </div>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="summary-item-label">Saldo Pendiente</div>
                            <div class="summary-item-value text-primary">S/ {{ number_format($saldoPendiente, 2) }}</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-item-label">Ya Pagado</div>
                            <div class="summary-item-value text-success">S/ {{ number_format($totalPagado, 2) }}</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-item-label">Descuento</div>
                            <div class="summary-item-value text-warning" id="descuentoDisplay">S/ 0.00</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Columna 1: Resumen del convenio -->
            <div class="col-md-5">
                <div class="section-compact">
                    <div class="section-header">
                        <i class="fas fa-info-circle"></i> Detalle del Convenio
                    </div>

                    <div class="alert alert-info alert-compact mb-3" style="background: #e3f2fd; border-left: 3px solid #2196f3;">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle me-2 mt-1" style="color: #2196f3;"></i>
                            <div style="flex: 1;">
                                <strong>Liquidacion de Convenio Flexible</strong>
                                <p class="mb-0 mt-1" style="font-size: 0.8rem; line-height: 1.4;">
                                    Se liquidara el saldo pendiente del convenio. Puede aplicar un descuento por monto o porcentaje.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-file-invoice-dollar me-1"></i> Total Convenio</span>
                        <span class="info-value">S/ {{ number_format($convenio->total_convenio, 2) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-coins me-1 text-primary"></i> Capital</span>
                        <span class="info-value">S/ {{ number_format($convenio->monto_capital, 2) }}</span>
                    </div>
                    @if($convenio->monto_moras > 0)
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-exclamation-triangle me-1 text-warning"></i> Moras incluidas</span>
                        <span class="info-value text-warning">S/ {{ number_format($convenio->monto_moras, 2) }}</span>
                    </div>
                    @endif
                    @if($convenio->descuento_moras > 0)
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-tag me-1 text-success"></i> Descuento original</span>
                        <span class="info-value text-success">- S/ {{ number_format($convenio->descuento_moras, 2) }}</span>
                    </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-check-circle me-1 text-success"></i> Total Pagado</span>
                        <span class="info-value text-success">S/ {{ number_format($totalPagado, 2) }}</span>
                    </div>
                    <div class="info-row" style="border-top: 2px solid #007bff; padding-top: 0.75rem;">
                        <span class="info-label"><strong><i class="fas fa-wallet me-1 text-danger"></i> Saldo Pendiente</strong></span>
                        <span class="info-value text-danger" style="font-size: 1.1rem;">S/ {{ number_format($saldoPendiente, 2) }}</span>
                    </div>

                    @if($convenio->pagosFlexibles->count() > 0)
                    <div class="mt-3">
                        <div class="section-header">
                            <i class="fas fa-history"></i> Pagos Realizados ({{ $convenio->pagosFlexibles->count() }})
                        </div>
                        <div style="max-height: 200px; overflow-y: auto;">
                            @foreach($convenio->pagosFlexibles->sortByDesc('fecha_pago') as $pago)
                            <div class="d-flex justify-content-between align-items-center py-1 px-2" style="font-size: 0.8rem; border-bottom: 1px solid #f5f5f5;">
                                <span class="text-muted">{{ $pago->fecha_pago->format('d/m/Y') }}</span>
                                <span class="badge bg-success">S/ {{ number_format($pago->monto, 2) }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Columna 2: Descuento -->
            <div class="col-md-3">
                <div class="section-compact">
                    <div class="section-header">
                        <i class="fas fa-percentage"></i> Descuento
                    </div>

                    <div class="toggle-switch-wrapper mb-2">
                        <div class="toggle-switch">
                            <input type="radio" class="toggle-option" name="tipoDescuento" id="montoDescuento" value="monto" checked>
                            <label for="montoDescuento" class="toggle-label">
                                <span class="toggle-text">S/</span>
                            </label>

                            <input type="radio" class="toggle-option" name="tipoDescuento" id="porcentajeDescuento" value="porcentaje">
                            <label for="porcentajeDescuento" class="toggle-label">
                                <span class="toggle-text">%</span>
                            </label>

                            <span class="toggle-slider"></span>
                        </div>
                    </div>

                    <input type="number" class="form-control form-control-sm input-compact" id="valorDescuento"
                        min="0" step="0.01" value="0" placeholder="0">

                    <div class="mt-2 p-2 rounded" style="background: #f8f9fa;">
                        <div class="d-flex justify-content-between" style="font-size: 0.8rem;">
                            <span class="text-muted">Saldo original:</span>
                            <span class="fw-bold">S/ {{ number_format($saldoPendiente, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between" style="font-size: 0.8rem;">
                            <span class="text-muted">Descuento:</span>
                            <span class="fw-bold text-success" id="descuentoCalc">- S/ 0.00</span>
                        </div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between" style="font-size: 0.9rem;">
                            <span class="fw-bold">A pagar:</span>
                            <span class="fw-bold text-primary" id="totalAPagar">S/ {{ number_format($saldoPendiente, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna 3: Metodo de Pago -->
            <div class="col-md-4">
                <div class="section-compact">
                    <div class="section-header">
                        <i class="fas fa-money-bill-wave"></i> Metodo de Pago
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

                    <!-- Campos extra para transferencia/tarjeta -->
                    <div id="extra-fields" style="display: none;">
                        <div class="extra-fields">
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label-compact">Nro. Operacion</label>
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
                            <label class="form-label-compact">Fecha de Liquidacion</label>
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

<!-- Footer Sticky -->
<div class="liquidacion-footer">
    <div class="d-flex justify-content-between align-items-center">
        <small class="text-white">.</small>
        <div class="d-flex justify-space-between">
            <a href="{{ route('admin.convenios.show', $convenio->id) }}" class="btn btn-secondary btn-action mr-3">
                <i class="fas fa-times"></i> Cancelar
            </a>
            <button type="button" class="btn btn-success btn-action" id="confirmarLiquidacion">
                <i class="fas fa-check-circle"></i> Procesar Liquidacion
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const saldoPendiente = {{ $saldoPendiente }};
    let descuento = 0;

    // Toggle metodo de pago
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
        const tipoDescuento = document.querySelector('input[name="tipoDescuento"]:checked')?.value || 'monto';
        const valorDescuento = parseFloat(document.getElementById('valorDescuento').value) || 0;

        if (tipoDescuento === 'porcentaje') {
            descuento = (saldoPendiente * Math.min(valorDescuento, 100)) / 100;
        } else {
            descuento = Math.min(valorDescuento, saldoPendiente);
        }

        const totalFinal = saldoPendiente - descuento;

        document.getElementById('descuentoCalc').textContent = `- S/ ${descuento.toFixed(2)}`;
        document.getElementById('descuentoDisplay').textContent = `S/ ${descuento.toFixed(2)}`;
        document.getElementById('totalAPagar').textContent = `S/ ${totalFinal.toFixed(2)}`;
        document.getElementById('totalALiquidar').textContent = `S/ ${totalFinal.toFixed(2)}`;
    }

    // Listeners descuento
    document.querySelectorAll('input[name="tipoDescuento"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const valorInput = document.getElementById('valorDescuento');
            valorInput.max = this.value === 'porcentaje' ? '100' : saldoPendiente.toFixed(2);
            valorInput.value = '0';
            actualizarTotales();
        });
    });

    document.getElementById('valorDescuento').addEventListener('input', actualizarTotales);

    // Confirmar liquidacion
    document.getElementById('confirmarLiquidacion').addEventListener('click', function() {
        const metodoPagoSeleccionado = document.querySelector('.metodo-pago-radio:checked');
        if (!metodoPagoSeleccionado) {
            Swal.fire({ icon: 'warning', title: 'Metodo de pago requerido', text: 'Debe seleccionar un metodo de pago' });
            return;
        }

        if (metodoPagoSeleccionado.value === '1' && !document.getElementById('fecha_codigo').value) {
            Swal.fire({ icon: 'warning', title: 'Fecha requerida', text: 'Debe ingresar la fecha de liquidacion' });
            return;
        }

        if ((metodoPagoSeleccionado.value === '2' || metodoPagoSeleccionado.value === '3') &&
            (!document.getElementById('nro_operacion').value.trim() || !document.getElementById('fecha_operacion').value)) {
            Swal.fire({ icon: 'warning', title: 'Datos incompletos', text: 'Complete numero de operacion y fecha' });
            return;
        }

        const totalFinal = saldoPendiente - descuento;

        Swal.fire({
            title: 'Confirmar liquidacion',
            html: `
                <div class="text-start" style="font-size: 0.9rem;">
                    <p><strong>Saldo pendiente:</strong> S/ ${saldoPendiente.toFixed(2)}</p>
                    ${descuento > 0 ? `<p><strong>Descuento:</strong> <span class="text-success">- S/ ${descuento.toFixed(2)}</span></p>` : ''}
                    <hr>
                    <p style="font-size: 1.1rem;"><strong>Total a pagar: S/ ${totalFinal.toFixed(2)}</strong></p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Si, liquidar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#28a745'
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
            metodo_pago_id: document.querySelector('.metodo-pago-radio:checked').value,
            total_liquidar: saldoPendiente - descuento,
            descuento: descuento,
            comentario: document.getElementById('comentario').value.trim(),
            _token: '{{ csrf_token() }}'
        };

        const metodoPago = document.querySelector('.metodo-pago-radio:checked').value;
        if (metodoPago === '1') {
            datosLiquidacion.fecha_codigo = document.getElementById('fecha_codigo').value;
        } else {
            datosLiquidacion.nro_operacion = document.getElementById('nro_operacion').value.trim();
            datosLiquidacion.fecha_operacion = document.getElementById('fecha_operacion').value;

            const voucherInput = document.getElementById('voucher');
            if (voucherInput.files.length > 0) {
                const formData = new FormData();
                Object.keys(datosLiquidacion).forEach(key => {
                    formData.append(key, datosLiquidacion[key]);
                });
                formData.append('voucher', voucherInput.files[0]);
                enviarLiquidacion(formData, true);
                return;
            }
        }

        enviarLiquidacion(datosLiquidacion, false);
    }

    function enviarLiquidacion(datos, esFormData) {
        fetch('/admin/convenios/{{ $convenio->id }}/liquidar-flexible', {
            method: 'POST',
            headers: esFormData ? {} : { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: esFormData ? datos : JSON.stringify(datos)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Liquidacion Exitosa!',
                    text: data.message || 'El convenio flexible ha sido liquidado',
                    timer: 2500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '{{ route("admin.convenios.show", $convenio->id) }}';
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Error al procesar' });
            }
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Error de Conexion', text: 'No se pudo conectar con el servidor' });
        })
        .finally(() => {
            document.getElementById('confirmarLiquidacion').disabled = false;
            document.getElementById('confirmarLiquidacion').innerHTML = '<i class="fas fa-check-circle"></i> Procesar Liquidacion';
        });
    }
});
</script>
@endsection
