@extends('layouts.admin')


@section('title', 'Comprobantes Electrónicos')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-file-invoice mr-2"></i>
                        Comprobantes Electrónicos
                    </h3>
                </div>
                <div class="col-md-6 text-right">
                    <form id="export-cuotas-form" method="POST" action="{{ route('admin.comprobantes.exportar-cuotas') }}" style="display: inline;" target="_blank">
                        @csrf
                        <input type="hidden" name="prestamo_ids" id="selected-prestamo-ids">
                        <button type="button" id="export-cuotas-btn" class="btn btn-primary btn-sm">
                            <i class="fas fa-file-excel"></i> Exportar Cuotas Excel
                        </button>
                    </form>
                    <a href="{{ route('admin.comprobantes.exportar') }}?{{ http_build_query(request()->except('page')) }}"
                       class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </a>
                    <a href="{{ route('admin.sunat-status.index') }}" class="btn btn-info btn-sm">
                        <i class="fas fa-heartbeat"></i> Estado SUNAT
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            @endif

            {{-- Filtros --}}
            <div class="card mb-3 border-primary">
                <div class="card-header text-black">
                    <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filtros de Búsqueda</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.comprobantes.index') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label><i class="fas fa-search"></i> Buscar</label>
                                    <input type="text" class="form-control" name="buscar"
                                           value="{{ request('buscar') }}"
                                           placeholder="Serie, número o cliente">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label><i class="fas fa-file-alt"></i> Tipo</label>
                                    <select class="form-control" name="tipo">
                                        <option value="">Todos</option>
                                        <option value="01" {{ request('tipo') == '01' ? 'selected' : '' }}>Factura</option>
                                        <option value="03" {{ request('tipo') == '03' ? 'selected' : '' }}>Boleta</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label><i class="fas fa-flag"></i> Estado</label>
                                    <select class="form-control" name="estado">
                                        <option value="">Todos</option>
                                        <option value="ACEPTADO" {{ request('estado') == 'ACEPTADO' ? 'selected' : '' }}>Aceptado</option>
                                        <option value="ENVIADO" {{ request('estado') == 'ENVIADO' ? 'selected' : '' }}>Enviado</option>
                                        <option value="ERROR" {{ request('estado') == 'ERROR' ? 'selected' : '' }}>Error</option>
                                        <option value="PENDIENTE" {{ request('estado') == 'PENDIENTE' ? 'selected' : '' }}>Pendiente</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label><i class="fas fa-calendar"></i> Desde</label>
                                    <input type="date" class="form-control" name="fecha_desde"
                                           value="{{ request('fecha_desde') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label><i class="fas fa-calendar"></i> Hasta</label>
                                    <input type="date" class="form-control" name="fecha_hasta"
                                           value="{{ request('fecha_hasta') }}">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                @if(request()->hasAny(['buscar', 'tipo', 'estado', 'fecha_desde', 'fecha_hasta']))
                                    <a href="{{ route('admin.comprobantes.index') }}" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-times"></i> Limpiar Filtros
                                    </a>
                                @endif
                                <button type="button" onclick="reenviarTodosComprobantes()" class="btn btn-warning btn-sm">
                                    <i class="fas fa-redo"></i> Reenviar Todos con Error
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Información y paginación --}}
            <div class="row mb-3 align-items-center">
                <div class="col-md-6">
                    <form method="GET" action="{{ route('admin.comprobantes.index') }}" class="form-inline">
                        @foreach(request()->except(['page', 'per_page']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <label class="mr-2">Mostrar:</label>
                        <select name="per_page" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="10" {{ request('per_page', 20) == 10 ? 'selected' : '' }}>10</option>
                            <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                            <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100</option>
                        </select>
                        <label class="ml-2">registros</label>
                    </form>
                </div>
                <div class="col-md-6 text-right">
                    <strong>Total: {{ $comprobantes->total() }}</strong> comprobantes
                    @if($comprobantes->total() > 0)
                        ({{ $comprobantes->firstItem() }} - {{ $comprobantes->lastItem() }})
                    @endif
                </div>
            </div>

            {{-- Tabla mejorada --}}
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="select-all-checkbox">
                            </th>
                            <th width="60">ID</th>
                            <th width="120">Número</th>
                            <th width="80">Tipo</th>
                            <th>Cliente</th>
                            <th width="100">DNI/RUC</th>
                            <th width="120">NR. Préstamo</th>
                            <th width="140">Fecha Emisión</th>
                            <th width="90">Total</th>
                            <th width="100">Estado</th>
                            <th width="150">Nota de Crédito</th>
                            <th width="280">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($comprobantes as $comprobante)
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="prestamo-checkbox" value="{{ $comprobante->prestamo_id }}" data-prestamo-id="{{ $comprobante->prestamo_id }}">
                                </td>
                                <td class="text-center">{{ $comprobante->id }}</td>
                                <td><strong>{{ $comprobante->numero_completo }}</strong></td>
                                <td>
                                    <span class="badge badge-{{ $comprobante->tipo_comprobante == '01' ? 'primary' : 'info' }}">
                                        {{ $comprobante->tipo_comprobante_nombre }}
                                    </span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;"
                                         title="{{ $comprobante->cliente->persona->nombres }} {{ $comprobante->cliente->persona->ape_pat }} {{ $comprobante->cliente->persona->ape_mat }}">
                                        {{ $comprobante->cliente->persona->nombres }}
                                        {{ $comprobante->cliente->persona->ape_pat }}
                                    </div>
                                </td>
                                <td><code>{{ $comprobante->cliente->persona->documento }}</code></td>
                                <td class="text-center">
                                    @if($comprobante->prestamo)
                                        <code>{{ $comprobante->prestamo->numero_prestamo }}</code>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td><small>{{ $comprobante->fecha_emision->format('d/m/Y H:i') }}</small></td>
                                <td class="text-right"><strong>S/. {{ number_format($comprobante->total, 2) }}</strong></td>
                                <td>
                                    @if($comprobante->estado == 'ACEPTADO')
                                        <span class="badge badge-success">
                                            <i class="fas fa-check-circle"></i> {{ $comprobante->estado }}
                                        </span>
                                    @elseif($comprobante->estado == 'ERROR')
                                        <span class="badge badge-danger">
                                            <i class="fas fa-times-circle"></i> {{ $comprobante->estado }}
                                        </span>
                                    @elseif($comprobante->estado == 'PENDIENTE')
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock"></i> {{ $comprobante->estado }}
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">{{ $comprobante->estado }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($comprobante->notasCredito && $comprobante->notasCredito->count() > 0)
                                        @foreach($comprobante->notasCredito as $nota)
                                            <div class="mb-1">
                                                <a href="{{ route('admin.comprobantes.show', $nota) }}"
                                                   class="badge badge-warning px-2 py-1"
                                                   style="font-size: 0.85rem;"
                                                   title="Ver nota de crédito">
                                                    <i class="fas fa-file-invoice"></i>
                                                    {{ $nota->numero_completo }}
                                                    <br>
                                                    <small>S/. {{ number_format($nota->total, 2) }}</small>
                                                </a>
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="text-muted"><small>-</small></span>
                                    @endif
                                </td>
                                <td>
                                    {{-- Grupo 1: Ver y Descargas --}}
                                    <div class="btn-group btn-group-sm mb-1" role="group">
                                        <a href="{{ route('admin.comprobantes.show', $comprobante) }}"
                                           class="btn btn-info" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.comprobantes.pdf', $comprobante) }}"
                                           class="btn btn-danger" title="PDF" target="_blank">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <a href="{{ route('admin.comprobantes.xml', $comprobante) }}"
                                           class="btn btn-primary" title="XML"
                                           @if(!$comprobante->xml_content) disabled style="pointer-events: none; opacity: 0.5;" @endif>
                                            <i class="fas fa-file-code"></i>
                                        </a>
                                        <button type="button" class="btn btn-success"
                                                onclick="descargarCdrComprobante({{ $comprobante->id }})"
                                                title="CDR"
                                                @if(!$comprobante->cdr_zip) disabled @endif>
                                            <i class="fas fa-file-archive"></i>
                                        </button>
                                    </div>

                                    {{-- Grupo 2: Acciones SUNAT --}}
                                    <div class="btn-group btn-group-sm mb-1" role="group">
                                        <button type="button" class="btn btn-outline-primary"
                                                onclick="verRespuestaSunat({{ $comprobante->id }})"
                                                title="Ver Respuesta SUNAT"
                                                @if(!$comprobante->cdr_zip) disabled @endif>
                                            <i class="fas fa-file-contract"></i> Respuesta
                                        </button>
                                        <button type="button" class="btn btn-outline-info"
                                                onclick="consultarEstadoSunat({{ $comprobante->id }})"
                                                title="Verificar en SUNAT">
                                            <i class="fas fa-sync"></i> Verificar
                                        </button>
                                        <button type="button" class="btn btn-outline-warning"
                                                onclick="reenviarComprobante({{ $comprobante->id }})"
                                                title="Reenviar a SUNAT">
                                            <i class="fas fa-paper-plane"></i> Reenviar
                                        </button>
                                    </div>

                                    {{-- Grupo 3: Anular/Notas (solo si está aceptado) --}}
                                    @if(strtoupper($comprobante->estado) === 'ACEPTADO' && $comprobante->cdr_zip)
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-danger"
                                                    onclick="anularComprobante({{ $comprobante->id }})"
                                                    title="Anular">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-warning"
                                                    onclick="notaCreditoComprobante({{ $comprobante->id }}, {{ $comprobante->total }})"
                                                    title="Nota de Crédito">
                                                <i class="fas fa-minus-circle"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-info"
                                                    onclick="notaDebitoComprobante({{ $comprobante->id }})"
                                                    title="Nota de Débito">
                                                <i class="fas fa-plus-circle"></i>
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No hay comprobantes registrados</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paginación --}}
        <div class="card-footer bg-light">
            <div class="row align-items-center">
                <div class="col-md-5">
                    <div class="text-muted">
                        @if($comprobantes->total() > 0)
                            Mostrando <strong>{{ $comprobantes->firstItem() }}</strong> a
                            <strong>{{ $comprobantes->lastItem() }}</strong> de
                            <strong>{{ $comprobantes->total() }}</strong> registros
                        @else
                            No hay registros
                        @endif
                    </div>
                </div>
                <div class="col-md-7">
                    @if($comprobantes->hasPages())
                        <div class="float-right">
                            {{ $comprobantes->appends(request()->except('page'))->links('pagination::bootstrap-4') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .table th {
        background-color: #343a40;
        color: white;
        font-weight: 600;
        text-align: center;
        vertical-align: middle;
    }
    .table td {
        vertical-align: middle;
    }
    .btn-group-sm > .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
    }
    .badge {
        padding: 0.5em 0.75em;
        font-size: 0.85em;
    }
    .alert {
        border-left: 4px solid;
    }
    .alert-success {
        border-left-color: #28a745;
    }
    .alert-danger {
        border-left-color: #dc3545;
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Manejar checkbox "Seleccionar todos"
    $('#select-all-checkbox').on('change', function() {
        $('.prestamo-checkbox').prop('checked', $(this).prop('checked'));
        updateExportButton();
    });

    // Manejar checkboxes individuales
    $(document).on('change', '.prestamo-checkbox', function() {
        updateExportButton();
    });

    // Función para actualizar el estado del botón de exportar
    function updateExportButton() {
        const selectedCheckboxes = $('.prestamo-checkbox:checked');
        const selectedIds = selectedCheckboxes.map(function() {
            return $(this).val();
        }).get();

        // Remover duplicados
        const uniqueIds = [...new Set(selectedIds)];

        $('#selected-prestamo-ids').val(uniqueIds.join(','));
        $('#export-cuotas-btn').prop('disabled', uniqueIds.length === 0);

        // Actualizar texto del botón para mostrar cantidad seleccionada
        if (uniqueIds.length > 0) {
            $('#export-cuotas-btn').html(`<i class="fas fa-file-excel"></i> Exportar Cuotas Excel (${uniqueIds.length})`);
        } else {
            $('#export-cuotas-btn').html(`<i class="fas fa-file-excel"></i> Exportar Cuotas Excel`);
        }
    }

    // Manejar envío del formulario de exportar cuotas
    $('#export-cuotas-btn').on('click', function() {
        console.log('Export button clicked');
        let selectedIds = $('#selected-prestamo-ids').val();
        console.log('Selected IDs:', selectedIds);

        // Si no hay selección, preguntar si desea exportar TODO
        if (!selectedIds || selectedIds.trim() === '') {
            console.log('No selection, asking user...');
            Swal.fire({
                title: '¿Exportar reporte general?',
                text: 'No ha seleccionado ningún préstamo. Se exportarán TODAS las cuotas pagadas de préstamos con factura habilitada.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, exportar todo',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('User confirmed export all');
                    $('#export-cuotas-form').submit();
                }
            });
            return;
        }

        console.log('Submitting form with selection');
        $('#export-cuotas-form').submit();
    });
});

function descargarCdrComprobante(comprobanteId) {
    window.open(`/admin/comprobantes/${comprobanteId}/cdr`, '_blank');
}

function reenviarComprobante(comprobanteId) {
    Swal.fire({
        title: '¿Reenviar a SUNAT?',
        text: 'Este comprobante será reenviado a SUNAT',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, reenviar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Reenviando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(`/admin/comprobantes/${comprobanteId}/reenviar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Enviado!',
                        html: data.message,
                        confirmButtonText: 'Aceptar'
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: data.message
                    });
                }
            })
            .catch(error => {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor'
                });
            });
        }
    });
}

function consultarEstadoSunat(comprobanteId) {
    Swal.fire({
        title: 'Consultando SUNAT...',
        text: 'Verificando estado del comprobante',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(`/admin/comprobantes/${comprobanteId}/consultar-estado`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        if (data.success) {
            const icon = data.aceptado ? 'success' : 'info';
            let titulo = data.aceptado ? '¡Comprobante Aceptado!' : 'Estado del Comprobante';
            if (data.es_nota) {
                titulo = 'Estado del Comprobante Original';
            }

            Swal.fire({
                icon: icon,
                title: titulo,
                html: `
                    <div class="text-left">
                        ${data.es_nota && data.comprobante_consultado ? `<p class="alert alert-info"><i class="fas fa-info-circle"></i> <strong>Nota:</strong> Se consultó el comprobante original ${data.comprobante_consultado}</p>` : ''}
                        <p><strong>Estado:</strong> ${data.estado || 'No disponible'}</p>
                        ${data.codigo_respuesta ? `<p><strong>Código:</strong> ${data.codigo_respuesta}</p>` : ''}
                        ${data.descripcion ? `<p><strong>Descripción:</strong> ${data.descripcion}</p>` : ''}
                        ${data.mensaje_sunat ? `<p><strong>SUNAT:</strong> ${data.mensaje_sunat}</p>` : ''}
                        ${data.tiene_cdr ? '<p class="text-success"><i class="fas fa-check"></i> CDR recibido</p>' : '<p class="text-warning"><i class="fas fa-exclamation"></i> Sin CDR</p>'}
                    </div>
                `,
                confirmButtonText: 'Aceptar'
            }).then(() => {
                if (data.actualizado) location.reload();
            });
        } else {
            let htmlContent = `<div class="text-left">
                <p>${data.message || 'No se pudo consultar el estado en SUNAT'}</p>`;

            if (data.sugerencia) {
                htmlContent += `<div class="alert alert-warning mt-2">
                    <i class="fas fa-lightbulb"></i> <strong>Sugerencia:</strong><br>
                    ${data.sugerencia}
                </div>`;
            }

            if (data.comprobante_original) {
                htmlContent += `<p class="mt-2"><strong>Comprobante Original:</strong> ${data.comprobante_original}</p>`;
            }

            htmlContent += `<hr>
                <small class="text-muted">
                    <strong>Posibles causas:</strong><br>
                    • El comprobante no fue enviado correctamente a SUNAT<br>
                    • El comprobante aún no está registrado en SUNAT<br>
                    • Problemas de conexión con SUNAT
                </small>
            </div>`;

            Swal.fire({
                icon: 'error',
                title: 'Error al Consultar',
                html: htmlContent,
                confirmButtonText: 'Aceptar'
            });
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo consultar el estado'
        });
    });
}

function anularComprobante(comprobanteId) {
    Swal.fire({
        title: '¿Anular comprobante?',
        text: 'Generará una comunicación de baja ante SUNAT',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar',
        input: 'text',
        inputPlaceholder: 'Motivo de anulación',
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Anulando...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch(`/admin/comprobantes/${comprobanteId}/anular`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify({
                    motivo_anulacion: result.value || 'Anulación solicitada'
                })
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Anulado!',
                        text: data.message
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            });
        }
    });
}

function reenviarTodosComprobantes() {
    Swal.fire({
        title: 'Reenviar Todos los Errores',
        html: '¿Reenviar <strong>TODOS</strong> los comprobantes con error a SUNAT?<br><br>Esto puede tardar varios minutos.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, reenviar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Procesando...',
                html: 'Reenviando comprobantes...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route("admin.comprobantes.reenviar-todos") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    let html = `
                        <div class="text-left">
                            <p><strong>Total procesados:</strong> ${data.total}</p>
                            <p class="text-success"><strong>Exitosos:</strong> ${data.exitosos}</p>
                            <p class="text-danger"><strong>Fallidos:</strong> ${data.fallidos}</p>
                        </div>
                    `;

                    Swal.fire({
                        icon: data.exitosos > 0 ? 'success' : 'warning',
                        title: 'Proceso Completado',
                        html: html
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: data.message
                    });
                }
            });
        }
    });
}

function notaCreditoComprobante(comprobanteId, montoTotal) {
    Swal.fire({
        title: '¿Generar nota de crédito?',
        text: 'Se generará una nota de crédito asociada a este comprobante',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, generar',
        cancelButtonText: 'Cancelar',
        html: `
        <div class="text-left mt-3">
          <div class="alert alert-info mb-3">
            <strong>Monto total del comprobante:</strong> S/ ${parseFloat(montoTotal).toFixed(2)}
          </div>
          <label class="form-label">Motivo:</label>
          <select id="motivo-credito" class="swal2-select">
            <option value="01">Anulación de la Operación</option>
            <option value="02">Anulación por error en el RUC</option>
            <option value="03">Error en la Descripción</option>
            <option value="04">Descuento por Ítem</option>
            <option value="05">Devolución por Ítem</option>
            <option value="06">Descuento Global</option>
            <option value="07">Devolución Total</option>
            <option value="08">Ajustes en Montos y Fechas de pago</option>
          </select>
          <label class="form-label">Monto de la nota de crédito:</label>
          <input id="monto-credito" class="swal2-input" type="number" step="0.01" value="${parseFloat(montoTotal).toFixed(2)}" max="${montoTotal}">
          <small class="text-muted d-block mt-1">El monto no puede ser mayor al total del comprobante</small>
        </div>
        `,
        preConfirm: () => {
            const motivo = document.getElementById('motivo-credito').value;
            const monto = parseFloat(document.getElementById('monto-credito').value);

            if (!motivo || !monto) {
                Swal.showValidationMessage('Debe completar todos los campos');
                return false;
            }

            if (monto <= 0) {
                Swal.showValidationMessage('El monto debe ser mayor a 0');
                return false;
            }

            if (monto > montoTotal) {
                Swal.showValidationMessage(`El monto no puede ser mayor a S/ ${parseFloat(montoTotal).toFixed(2)}`);
                return false;
            }

            return { motivo, monto };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Generando nota de crédito...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar petición
            fetch(`/admin/comprobantes/${comprobanteId}/nota-credito`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify(result.value)
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Nota de crédito generada!',
                        text: data.message,
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo generar la nota de crédito'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor'
                });
            });
        }
    });
}

function notaDebitoComprobante(comprobanteId) {
    Swal.fire({
        title: '¿Generar nota de débito?',
        text: 'Se generará una nota de débito asociada a este comprobante',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#17a2b8',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, generar',
        cancelButtonText: 'Cancelar',
        html: `
        <div class="text-left mt-3">
          <label class="form-label">Motivo:</label>
          <select id="motivo-debito" class="swal2-select">
            <option value="01">Intereses por mora</option>
            <option value="02">Aumento en el valor</option>
            <option value="03">Penalidades</option>
          </select>
          <label class="form-label">Monto:</label>
          <input id="monto-debito" class="swal2-input" type="number" step="0.01" placeholder="Monto de la nota de débito">
        </div>
        `,
        preConfirm: () => {
            const motivo = document.getElementById('motivo-debito').value;
            const monto = document.getElementById('monto-debito').value;

            if (!motivo || !monto) {
                Swal.showValidationMessage('Debe completar todos los campos');
                return false;
            }

            return { motivo, monto };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Generando nota de débito...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar petición
            fetch(`/admin/comprobantes/${comprobanteId}/nota-debito`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify(result.value)
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Nota de débito generada!',
                        text: data.message,
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo generar la nota de débito'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor'
                });
            });
        }
    });
}

function verRespuestaSunat(comprobanteId) {
    // Mostrar loading
    Swal.fire({
        title: 'Cargando respuesta de SUNAT...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Obtener datos
    fetch(`/admin/comprobantes/${comprobanteId}/respuesta-sunat`)
        .then(response => response.json())
        .then(data => {
            Swal.close();

            if (data.success) {
                const respuesta = data.data;
                const comp = respuesta.comprobante;
                const cdr = respuesta.cdr;
                const logs = respuesta.logs_relacionados;
                const xml = respuesta.cdr_xml_raw;

                // Construir HTML del modal
                let htmlContent = `
                    <div class="text-left" style="max-height: 600px; overflow-y: auto;">
                        <h5 class="border-bottom pb-2 mb-3">📄 Comprobante ${comp.serie}-${comp.numero}</h5>

                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <strong>Información del Comprobante</strong>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-bordered mb-0">
                                    <tr><th width="150">ID:</th><td>${comp.id}</td></tr>
                                    <tr><th>Serie-Número:</th><td><strong>${comp.serie}-${comp.numero}</strong></td></tr>
                                    <tr><th>Tipo:</th><td>${comp.tipo}</td></tr>
                                    <tr><th>Estado Actual:</th><td><span class="badge badge-${comp.estado_actual === 'ACEPTADO' ? 'success' : (comp.estado_actual === 'RECHAZADO' ? 'danger' : 'warning')}">${comp.estado_actual}</span></td></tr>
                                    <tr><th>Fecha Emisión:</th><td>${comp.fecha_emision}</td></tr>
                                    <tr><th>Total:</th><td>S/ ${parseFloat(comp.total).toFixed(2)}</td></tr>
                                    <tr><th>Hash:</th><td><code style="font-size: 0.7rem;">${comp.hash || '-'}</code></td></tr>
                                    ${comp.codigo_error ? `<tr><th>Código Error:</th><td><span class="badge badge-warning">${comp.codigo_error}</span></td></tr>` : ''}
                                    ${comp.mensaje_error ? `<tr><th>Mensaje Error:</th><td><small>${comp.mensaje_error}</small></td></tr>` : ''}
                                </table>
                            </div>
                        </div>`;

                if (cdr && !cdr.error) {
                    htmlContent += `
                        <div class="card mb-3">
                            <div class="card-header bg-success text-white">
                                <strong>🎯 Respuesta de SUNAT (CDR)</strong>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-bordered mb-0">
                                    <tr><th width="180">Estado SUNAT:</th><td><span class="badge badge-${cdr.estado === 'ACEPTADO' ? 'success' : (cdr.estado === 'RECHAZADO' ? 'danger' : 'warning')}">${cdr.estado}</span></td></tr>
                                    <tr><th>Código Respuesta:</th><td><strong>${cdr.codigo_respuesta}</strong></td></tr>
                                    <tr><th>Mensaje:</th><td>${cdr.mensaje_respuesta}</td></tr>
                                    <tr><th>Fecha Respuesta:</th><td>${cdr.fecha_respuesta}</td></tr>
                                    <tr><th>Referencia ID:</th><td><code>${cdr.referencia_id}</code></td></tr>
                                    <tr><th>Document Reference:</th><td><code>${cdr.document_reference}</code></td></tr>
                                    <tr><th>Response Code (XML):</th><td>${cdr.response_code}</td></tr>
                                    <tr><th>Response Description:</th><td>${cdr.response_description}</td></tr>
                                    <tr><th>Tiene Firma Digital:</th><td>${cdr.tiene_firma_digital ? '<span class="badge badge-success">SÍ</span>' : '<span class="badge badge-secondary">NO</span>'}</td></tr>
                                </table>
                            </div>
                        </div>`;

                    if (xml) {
                        htmlContent += `
                            <div class="card mb-3">
                                <div class="card-header bg-info text-white">
                                    <strong>📋 CDR XML Completo (Respuesta SUNAT)</strong>
                                </div>
                                <div class="card-body p-0">
                                    <pre class="mb-0 p-3" style="max-height: 300px; overflow-y: auto; background-color: #f8f9fa; font-size: 0.75rem;"><code>${escapeHtml(xml)}</code></pre>
                                </div>
                            </div>`;
                    }
                } else if (cdr && cdr.error) {
                    htmlContent += `
                        <div class="alert alert-warning">
                            <strong>⚠️ CDR no disponible:</strong> ${cdr.error}
                        </div>`;
                } else {
                    htmlContent += `
                        <div class="alert alert-warning">
                            <strong>⚠️ Este comprobante no tiene CDR de SUNAT</strong>
                            <p class="mb-0">El CDR (Constancia de Recepción) es generado por SUNAT cuando el comprobante es aceptado.</p>
                        </div>`;
                }

                if (logs && logs.length > 0) {
                    htmlContent += `
                        <div class="card mb-3">
                            <div class="card-header bg-secondary text-white">
                                <strong>📝 Logs del Sistema (${logs.length} entradas)</strong>
                            </div>
                            <div class="card-body p-0">
                                <pre class="mb-0 p-3" style="max-height: 300px; overflow-y: auto; background-color: #2d2d2d; color: #f8f8f2; font-size: 0.7rem;"><code>${logs.map(log => escapeHtml(log)).join('\n')}</code></pre>
                            </div>
                        </div>`;
                } else {
                    htmlContent += `
                        <div class="alert alert-info">
                            <strong>ℹ️ No se encontraron logs relacionados</strong>
                        </div>`;
                }

                htmlContent += `</div>`;

                // Mostrar modal
                Swal.fire({
                    title: `Respuesta SUNAT - ${comp.serie}-${comp.numero}`,
                    html: htmlContent,
                    width: '90%',
                    confirmButtonText: 'Cerrar',
                    customClass: {
                        container: 'swal-wide'
                    }
                });

            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'No se pudo obtener la información'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor'
            });
        });
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
</script>
@stop
