@extends('layouts.admin')
@section('title', 'Consultar Comprobantes SUNAT')

@section('content')
<div class="container-fluid pt-2 p-0">
    <div class="card card-outline card-primary">
        <div class="card-header bg-gradient-primary text-white">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="card-title text-black mb-0">
                        <i class="fas fa-download me-2"></i>
                        Consultar y Traer Libros desde SUNAT
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
                <!-- Panel de consulta -->
                <div class="col-md-6 mb-4">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-search"></i> Consultar Comprobantes
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Consulte el estado de comprobantes enviados a SUNAT y descargue los CDR.
                            </p>

                            <form id="form-consultar-comprobante">
                                @csrf
                                <div class="form-group">
                                    <label for="tipo_comprobante_consulta">Tipo de Comprobante:</label>
                                    <select id="tipo_comprobante_consulta" name="tipo_comprobante" class="form-control" required>
                                        <option value="">-- Seleccione --</option>
                                        <option value="01">Factura (01)</option>
                                        <option value="03">Boleta (03)</option>
                                        <option value="07">Nota de Crédito (07)</option>
                                        <option value="08">Nota de Débito (08)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="serie_consulta">Serie:</label>
                                    <input type="text" id="serie_consulta" name="serie" class="form-control"
                                           placeholder="Ej: F001, B001" maxlength="4" required>
                                </div>

                                <div class="form-group">
                                    <label for="numero_consulta">Número:</label>
                                    <input type="number" id="numero_consulta" name="numero" class="form-control"
                                           placeholder="Ej: 123" min="1" required>
                                </div>

                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Consultar Estado
                                </button>
                            </form>

                            <!-- Resultado de la consulta -->
                            <div id="resultado-consulta" class="mt-3 d-none">
                                <hr>
                                <div class="alert" id="alert-resultado">
                                    <!-- Se llenará dinámicamente -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel de descarga de libros -->
                <div class="col-md-6 mb-4">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-book"></i> Descargar Libros Electrónicos
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Descargue los libros electrónicos (PLE) desde SUNAT para un período específico.
                            </p>

                            <form id="form-descargar-libros">
                                @csrf
                                <div class="form-group">
                                    <label for="tipo_libro">Tipo de Libro:</label>
                                    <select id="tipo_libro" name="tipo_libro" class="form-control" required>
                                        <option value="">-- Seleccione --</option>
                                        <option value="140100">Registro de Ventas e Ingresos</option>
                                        <option value="080100">Registro de Compras</option>
                                        <option value="050100">Libro Diario</option>
                                        <option value="060100">Libro Mayor</option>
                                        <option value="010100">Libro Caja y Bancos</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Seleccione el tipo de libro que desea descargar.
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="periodo">Período:</label>
                                    <input type="month" id="periodo" name="periodo" class="form-control"
                                           value="{{ date('Y-m') }}" required>
                                    <small class="form-text text-muted">
                                        Formato: AAAA-MM (Ej: 2024-12)
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="formato_descarga">Formato:</label>
                                    <select id="formato_descarga" name="formato" class="form-control" required>
                                        <option value="txt">Texto (TXT)</option>
                                        <option value="excel">Excel (XLSX)</option>
                                        <option value="pdf">PDF</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-download"></i> Descargar Libro
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel de sincronización automática -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-sync-alt"></i> Sincronización Automática
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <p class="text-muted mb-2">
                                        Configure la sincronización automática de comprobantes con SUNAT para mantener
                                        actualizado el estado de sus documentos.
                                    </p>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success"></i> Consulta automática de CDR pendientes</li>
                                        <li><i class="fas fa-check text-success"></i> Actualización de estados cada hora</li>
                                        <li><i class="fas fa-check text-success"></i> Notificaciones de documentos rechazados</li>
                                    </ul>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="form-group">
                                        <label class="d-block">Estado de Sincronización:</label>
                                        <div class="custom-control custom-switch custom-switch-lg">
                                            <input type="checkbox" class="custom-control-input" id="toggle-sincronizacion"
                                                   {{ $sincronizacion_activa ?? false ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="toggle-sincronizacion">
                                                <span id="texto-sincronizacion">
                                                    {{ $sincronizacion_activa ?? false ? 'Activa' : 'Inactiva' }}
                                                </span>
                                            </label>
                                        </div>
                                    </div>

                                    <button id="btn-sincronizar-ahora" class="btn btn-info btn-sm mt-2">
                                        <i class="fas fa-sync"></i> Sincronizar Ahora
                                    </button>

                                    <div id="ultima-sincronizacion" class="mt-3 text-muted small">
                                        <i class="far fa-clock"></i> Última sincronización:
                                        <span id="fecha-ultima-sync">{{ $ultima_sync ?? 'Nunca' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historial de consultas -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history"></i> Historial de Consultas Recientes
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tabla-historial" class="table table-bordered table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Serie-Número</th>
                                            <th>Estado SUNAT</th>
                                            <th>Mensaje</th>
                                            <th>Usuario</th>
                                        </tr>
                                    </thead>
                                    <tbody>
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
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Consultar comprobante
    $('#form-consultar-comprobante').submit(function(e) {
        e.preventDefault();

        const tipo = $('#tipo_comprobante_consulta').val();
        const serie = $('#serie_consulta').val().toUpperCase();
        const numero = $('#numero_consulta').val();

        if (!tipo || !serie || !numero) {
            toastr.error('Complete todos los campos');
            return;
        }

        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Consultando...');

        $.ajax({
            url: '{{ route("admin.sire.consultar-comprobante") }}',
            type: 'POST',
            data: {
                tipo_comprobante: tipo,
                serie: serie,
                numero: numero,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    mostrarResultadoConsulta(response.data);
                    toastr.success('Consulta realizada exitosamente');
                } else {
                    toastr.error(response.message || 'Error en la consulta');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Error al consultar el comprobante';
                toastr.error(message);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-search"></i> Consultar Estado');
            }
        });
    });

    function mostrarResultadoConsulta(data) {
        const estado = data.estado_sunat || 'desconocido';
        let alertClass = 'alert-info';
        let icono = 'fa-info-circle';

        if (estado === 'ACEPTADO' || estado === 'aceptado') {
            alertClass = 'alert-success';
            icono = 'fa-check-circle';
        } else if (estado === 'RECHAZADO' || estado === 'rechazado') {
            alertClass = 'alert-danger';
            icono = 'fa-times-circle';
        }

        let html = `
            <div class="${alertClass}">
                <h6><i class="fas ${icono}"></i> ${estado.toUpperCase()}</h6>
                <p class="mb-1"><strong>Mensaje SUNAT:</strong> ${data.mensaje_sunat || 'Sin mensaje'}</p>
                <p class="mb-1"><strong>Código:</strong> ${data.codigo_sunat || 'N/A'}</p>
                ${data.hash ? '<p class="mb-0"><strong>Hash:</strong> <code>' + data.hash + '</code></p>' : ''}
            </div>
        `;

        $('#alert-resultado').html(html);
        $('#resultado-consulta').removeClass('d-none');
    }

    // Descargar libros
    $('#form-descargar-libros').submit(function(e) {
        e.preventDefault();

        const tipoLibro = $('#tipo_libro').val();
        const periodo = $('#periodo').val();
        const formato = $('#formato_descarga').val();

        if (!tipoLibro || !periodo) {
            toastr.error('Complete todos los campos');
            return;
        }

        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Descargando...');

        // Construir URL de descarga
        const url = `{{ route("admin.sire.descargar-libro") }}?tipo=${tipoLibro}&periodo=${periodo}&formato=${formato}`;

        // Descargar archivo
        window.location.href = url;

        setTimeout(function() {
            btn.prop('disabled', false).html('<i class="fas fa-download"></i> Descargar Libro');
            toastr.success('Descarga iniciada');
        }, 2000);
    });

    // Toggle sincronización
    $('#toggle-sincronizacion').change(function() {
        const activo = $(this).is(':checked');

        $.ajax({
            url: '{{ route("admin.sire.toggle-sincronizacion") }}',
            type: 'POST',
            data: {
                activo: activo,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#texto-sincronizacion').text(activo ? 'Activa' : 'Inactiva');
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                    $('#toggle-sincronizacion').prop('checked', !activo);
                }
            },
            error: function() {
                toastr.error('Error al cambiar el estado de sincronización');
                $('#toggle-sincronizacion').prop('checked', !activo);
            }
        });
    });

    // Sincronizar ahora
    $('#btn-sincronizar-ahora').click(function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sincronizando...');

        $.ajax({
            url: '{{ route("admin.sire.sincronizar-ahora") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#fecha-ultima-sync').text(response.fecha);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('Error al sincronizar');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Sincronizar Ahora');
            }
        });
    });

    // Cargar historial
    function cargarHistorial() {
        $.get('{{ route("admin.sire.historial-consultas") }}', function(response) {
            if (response.success) {
                let html = '';
                response.data.forEach(function(item) {
                    const badge = item.estado === 'ACEPTADO' ? 'badge-success' :
                                  item.estado === 'RECHAZADO' ? 'badge-danger' : 'badge-secondary';
                    html += `
                        <tr>
                            <td>${item.fecha}</td>
                            <td>${item.tipo}</td>
                            <td>${item.serie}-${item.numero}</td>
                            <td><span class="badge ${badge}">${item.estado}</span></td>
                            <td>${item.mensaje}</td>
                            <td>${item.usuario}</td>
                        </tr>
                    `;
                });
                $('#tabla-historial tbody').html(html || '<tr><td colspan="6" class="text-center">No hay consultas recientes</td></tr>');
            }
        });
    }

    cargarHistorial();
});
</script>
@endsection
