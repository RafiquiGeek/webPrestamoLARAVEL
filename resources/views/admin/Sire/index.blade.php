@extends('layouts.admin')
@section('title', 'Comprobantes SIRE')

@section('content')
<div class="container-fluid pt-2 p-0">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header bg-gradient-primary text-white">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="card-title text-black mb-0">
                        <i class="fas fa-file-invoice me-2"></i>
                        Comprobantes Electrónicos SIRE
                    </h3>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('admin.sire.enviar') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-paper-plane"></i> Enviar Comprobante
                    </a>
                    <a href="{{ route('admin.sire.consultar') }}" class="btn btn-info btn-sm">
                        <i class="fas fa-download"></i> Consultar SUNAT
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Filtros -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="filtro_estado">Estado:</label>
                    <select id="filtro_estado" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        <option value="enviado">Enviado</option>
                        <option value="aceptado">Aceptado</option>
                        <option value="rechazado">Rechazado</option>
                        <option value="pendiente">Pendiente</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filtro_tipo">Tipo Comprobante:</label>
                    <select id="filtro_tipo" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        <option value="01">Factura</option>
                        <option value="03">Boleta</option>
                        <option value="07">Nota de Crédito</option>
                        <option value="08">Nota de Débito</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filtro_fecha_desde">Desde:</label>
                    <input type="date" id="filtro_fecha_desde" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label for="filtro_fecha_hasta">Hasta:</label>
                    <input type="date" id="filtro_fecha_hasta" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button id="btn_filtrar" class="btn btn-primary btn-sm btn-block">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </div>
            </div>

            <!-- Tabla de comprobantes -->
            <div class="table-responsive">
                <table id="tabla-comprobantes" class="table table-bordered table-striped table-hover table-sm">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Serie-Número</th>
                            <th>Fecha Emisión</th>
                            <th>Cliente</th>
                            <th>RUC/DNI</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Fecha Envío</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Se llenará via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles -->
<div class="modal fade" id="modalDetalles" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-file-alt"></i> Detalles del Comprobante
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="contenido-detalles">
                <!-- Se llenará via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    let table = $('#tabla-comprobantes').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.sire.data") }}',
            data: function(d) {
                d.estado = $('#filtro_estado').val();
                d.tipo_comprobante = $('#filtro_tipo').val();
                d.fecha_desde = $('#filtro_fecha_desde').val();
                d.fecha_hasta = $('#filtro_fecha_hasta').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            {
                data: 'tipo_comprobante',
                name: 'tipo_comprobante',
                render: function(data) {
                    const tipos = {
                        '01': '<span class="badge badge-primary">Factura</span>',
                        '03': '<span class="badge badge-info">Boleta</span>',
                        '07': '<span class="badge badge-warning">N. Crédito</span>',
                        '08': '<span class="badge badge-secondary">N. Débito</span>'
                    };
                    return tipos[data] || data;
                }
            },
            {
                data: null,
                render: function(data) {
                    return data.serie + '-' + data.numero;
                }
            },
            { data: 'fecha_emision', name: 'fecha_emision' },
            { data: 'cliente_razon_social', name: 'cliente_razon_social' },
            { data: 'cliente_numero_doc', name: 'cliente_numero_doc' },
            {
                data: 'total',
                render: function(data) {
                    return 'S/ ' + parseFloat(data).toFixed(2);
                }
            },
            {
                data: 'estado',
                name: 'estado',
                render: function(data) {
                    const estados = {
                        'enviado': '<span class="badge badge-primary">Enviado</span>',
                        'aceptado': '<span class="badge badge-success">Aceptado</span>',
                        'rechazado': '<span class="badge badge-danger">Rechazado</span>',
                        'pendiente': '<span class="badge badge-warning">Pendiente</span>'
                    };
                    return estados[data] || '<span class="badge badge-secondary">' + data + '</span>';
                }
            },
            { data: 'fecha_envio', name: 'fecha_envio' },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data) {
                    let botones = `
                        <button class="btn btn-info btn-xs ver-detalles" data-id="${data.id}" title="Ver detalles">
                            <i class="fas fa-eye"></i>
                        </button>
                    `;

                    if (data.xml_firmado) {
                        botones += `
                            <a href="{{ url('admin/sire/descargar-xml') }}/${data.id}" class="btn btn-success btn-xs" title="Descargar XML">
                                <i class="fas fa-download"></i>
                            </a>
                        `;
                    }

                    if (data.cdr_zip) {
                        botones += `
                            <a href="{{ url('admin/sire/descargar-cdr') }}/${data.id}" class="btn btn-primary btn-xs" title="Descargar CDR">
                                <i class="fas fa-file-archive"></i>
                            </a>
                        `;
                    }

                    if (data.estado !== 'aceptado') {
                        botones += `
                            <button class="btn btn-warning btn-xs reenviar" data-id="${data.id}" title="Reenviar">
                                <i class="fas fa-redo"></i>
                            </button>
                        `;
                    }

                    return botones;
                }
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        order: [[0, 'desc']],
        pageLength: 25
    });

    // Filtrar
    $('#btn_filtrar').click(function() {
        table.ajax.reload();
    });

    // Ver detalles
    $(document).on('click', '.ver-detalles', function() {
        const id = $(this).data('id');

        $('#contenido-detalles').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
        $('#modalDetalles').modal('show');

        $.get(`{{ url('admin/sire') }}/${id}/detalles`, function(response) {
            $('#contenido-detalles').html(response);
        }).fail(function() {
            $('#contenido-detalles').html('<div class="alert alert-danger">Error al cargar los detalles</div>');
        });
    });

    // Reenviar comprobante
    $(document).on('click', '.reenviar', function() {
        const id = $(this).data('id');

        if (confirm('¿Está seguro de reenviar este comprobante a SUNAT?')) {
            $.ajax({
                url: `{{ url('api/sire/reenviar') }}/${id}`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || 'Comprobante reenviado exitosamente');
                        table.ajax.reload();
                    } else {
                        toastr.error(response.message || 'Error al reenviar');
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Error al reenviar el comprobante';
                    toastr.error(message);
                }
            });
        }
    });
});
</script>
@endsection
