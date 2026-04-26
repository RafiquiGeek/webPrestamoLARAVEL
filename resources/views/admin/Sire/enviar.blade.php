@extends('layouts.admin')
@section('title', 'Enviar Comprobante a SUNAT')

@section('content')
<div class="container-fluid pt-2 p-0">
    <div class="card card-outline card-primary">
        <div class="card-header bg-gradient-primary text-white">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="card-title text-black mb-0">
                        <i class="fas fa-paper-plane me-2"></i>
                        Enviar Comprobantes a SUNAT
                    </h3>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('admin.sire.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                <!-- Opción 1: Enviar por cuota específica -->
                <div class="col-md-6 mb-4">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-file-invoice"></i> Enviar por Cuota
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Emitir comprobante electrónico para una cuota específica de un préstamo.
                            </p>

                            <form id="form-enviar-cuota">
                                @csrf
                                <div class="form-group">
                                    <label for="cuota_id">Seleccionar Cuota:</label>
                                    <select id="cuota_id" name="cuota_id" class="form-control select2" required>
                                        <option value="">-- Seleccione una cuota --</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Solo se muestran cuotas pagadas sin comprobante emitido.
                                    </small>
                                </div>

                                <div id="info-cuota" class="alert alert-info d-none">
                                    <strong>Información de la cuota:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li><strong>Préstamo:</strong> <span id="info-prestamo"></span></li>
                                        <li><strong>Cliente:</strong> <span id="info-cliente"></span></li>
                                        <li><strong>Documento:</strong> <span id="info-documento"></span></li>
                                        <li><strong>Monto:</strong> S/ <span id="info-monto"></span></li>
                                        <li><strong>Tipo Comprobante:</strong> <span id="info-tipo-comprobante"></span></li>
                                    </ul>
                                </div>

                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-paper-plane"></i> Emitir y Enviar Comprobante
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Opción 2: Envío masivo -->
                <div class="col-md-6 mb-4">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-list"></i> Envío Masivo
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Enviar comprobantes para múltiples cuotas de un préstamo o rango de fechas.
                            </p>

                            <form id="form-enviar-masivo">
                                @csrf
                                <div class="form-group">
                                    <label>Tipo de selección:</label>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="tipo_prestamo" name="tipo_seleccion" value="prestamo" class="custom-control-input" checked>
                                        <label class="custom-control-label" for="tipo_prestamo">Por Préstamo</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="tipo_fecha" name="tipo_seleccion" value="fecha" class="custom-control-input">
                                        <label class="custom-control-label" for="tipo_fecha">Por Rango de Fechas</label>
                                    </div>
                                </div>

                                <div id="seleccion-prestamo">
                                    <div class="form-group">
                                        <label for="prestamo_id">Préstamo:</label>
                                        <select id="prestamo_id" name="prestamo_id" class="form-control select2">
                                            <option value="">-- Seleccione un préstamo --</option>
                                        </select>
                                    </div>
                                </div>

                                <div id="seleccion-fecha" class="d-none">
                                    <div class="form-group">
                                        <label for="fecha_desde">Fecha Desde:</label>
                                        <input type="date" id="fecha_desde" name="fecha_desde" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="fecha_hasta">Fecha Hasta:</label>
                                        <input type="date" id="fecha_hasta" name="fecha_hasta" class="form-control">
                                    </div>
                                </div>

                                <div id="cuotas-disponibles" class="d-none">
                                    <div class="alert alert-info">
                                        <strong>Cuotas disponibles:</strong> <span id="cantidad-cuotas">0</span>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-paper-plane"></i> Enviar Comprobantes Masivo
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de resultados -->
            <div id="resultados-envio" class="d-none mt-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard-list"></i> Resultados del Envío
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Cuota</th>
                                        <th>Cliente</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                        <th>Mensaje</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-resultados">
                                    <!-- Se llenará dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Inicializar Select2
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });

    // Cargar cuotas disponibles
    function cargarCuotas() {
        $.get('{{ route("admin.sire.cuotas-disponibles") }}', function(response) {
            let options = '<option value="">-- Seleccione una cuota --</option>';
            response.data.forEach(function(cuota) {
                options += `<option value="${cuota.id}"
                    data-prestamo="${cuota.prestamo.numero_prestamo}"
                    data-cliente="${cuota.prestamo.cliente.nombre_completo}"
                    data-documento="${cuota.prestamo.cliente.numero_documento}"
                    data-tipo-doc="${cuota.prestamo.cliente.tipo_documento}"
                    data-monto="${cuota.monto_cuota}">
                    Cuota ${cuota.numero} - ${cuota.prestamo.cliente.nombre_completo} - S/ ${cuota.monto_cuota}
                </option>`;
            });
            $('#cuota_id').html(options);
        });
    }

    // Cargar préstamos
    function cargarPrestamos() {
        $.get('{{ route("admin.sire.prestamos-disponibles") }}', function(response) {
            let options = '<option value="">-- Seleccione un préstamo --</option>';
            response.data.forEach(function(prestamo) {
                options += `<option value="${prestamo.id}">
                    ${prestamo.numero_prestamo} - ${prestamo.cliente.nombre_completo}
                </option>`;
            });
            $('#prestamo_id').html(options);
        });
    }

    cargarCuotas();
    cargarPrestamos();

    // Mostrar información de cuota seleccionada
    $('#cuota_id').change(function() {
        const selected = $(this).find('option:selected');
        if (selected.val()) {
            $('#info-prestamo').text(selected.data('prestamo'));
            $('#info-cliente').text(selected.data('cliente'));
            $('#info-documento').text(selected.data('documento'));
            $('#info-monto').text(parseFloat(selected.data('monto')).toFixed(2));

            const tipoDoc = selected.data('tipo-doc');
            const tipoComprobante = tipoDoc === '6' ? 'Factura (RUC)' : 'Boleta (DNI)';
            $('#info-tipo-comprobante').text(tipoComprobante);

            $('#info-cuota').removeClass('d-none');
        } else {
            $('#info-cuota').addClass('d-none');
        }
    });

    // Cambiar tipo de selección en envío masivo
    $('input[name="tipo_seleccion"]').change(function() {
        if ($(this).val() === 'prestamo') {
            $('#seleccion-prestamo').removeClass('d-none');
            $('#seleccion-fecha').addClass('d-none');
        } else {
            $('#seleccion-prestamo').addClass('d-none');
            $('#seleccion-fecha').removeClass('d-none');
        }
    });

    // Enviar por cuota
    $('#form-enviar-cuota').submit(function(e) {
        e.preventDefault();

        const cuotaId = $('#cuota_id').val();
        if (!cuotaId) {
            toastr.error('Debe seleccionar una cuota');
            return;
        }

        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');

        $.ajax({
            url: '{{ route("api.sire.emitir-cuota") }}',
            type: 'POST',
            data: {
                cuota_id: cuotaId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Comprobante emitido y enviado exitosamente');
                    setTimeout(function() {
                        window.location.href = '{{ route("admin.sire.index") }}';
                    }, 1500);
                } else {
                    toastr.error(response.message || 'Error al enviar el comprobante');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Error al enviar el comprobante';
                toastr.error(message);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Emitir y Enviar Comprobante');
            }
        });
    });

    // Envío masivo
    $('#form-enviar-masivo').submit(function(e) {
        e.preventDefault();

        const tipoSeleccion = $('input[name="tipo_seleccion"]:checked').val();
        let data = {
            _token: '{{ csrf_token() }}',
            tipo: tipoSeleccion
        };

        if (tipoSeleccion === 'prestamo') {
            const prestamoId = $('#prestamo_id').val();
            if (!prestamoId) {
                toastr.error('Debe seleccionar un préstamo');
                return;
            }
            data.prestamo_id = prestamoId;
        } else {
            const fechaDesde = $('#fecha_desde').val();
            const fechaHasta = $('#fecha_hasta').val();
            if (!fechaDesde || !fechaHasta) {
                toastr.error('Debe seleccionar el rango de fechas');
                return;
            }
            data.fecha_desde = fechaDesde;
            data.fecha_hasta = fechaHasta;
        }

        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

        $.ajax({
            url: '{{ route("admin.sire.enviar-masivo") }}',
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    mostrarResultados(response.resultados);
                    toastr.success(`Procesados: ${response.total}. Exitosos: ${response.exitosos}. Fallidos: ${response.fallidos}`);
                } else {
                    toastr.error(response.message || 'Error en el envío masivo');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Error en el envío masivo';
                toastr.error(message);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Enviar Comprobantes Masivo');
            }
        });
    });

    function mostrarResultados(resultados) {
        let html = '';
        resultados.forEach(function(r) {
            const badge = r.success ? 'badge-success' : 'badge-danger';
            const icono = r.success ? 'fa-check' : 'fa-times';
            html += `
                <tr>
                    <td>${r.cuota}</td>
                    <td>${r.cliente}</td>
                    <td>S/ ${parseFloat(r.monto).toFixed(2)}</td>
                    <td><span class="badge ${badge}"><i class="fas ${icono}"></i></span></td>
                    <td>${r.mensaje}</td>
                </tr>
            `;
        });
        $('#tbody-resultados').html(html);
        $('#resultados-envio').removeClass('d-none');
    }
});
</script>
@endsection
