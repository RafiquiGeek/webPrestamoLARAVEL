@extends('adminlte::page')

@section('title', 'Validación de Operaciones')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>
            <i class="fas fa-check-double"></i>
            Validación de Operaciones
        </h1>
        <div>
            <button type="button" class="btn btn-success btn-sm" onclick="validarTodas()">
                <i class="fas fa-check-double"></i> Validar Todas Pendientes
            </button>
            <button type="button" class="btn btn-info btn-sm" onclick="actualizarEstadisticas()">
                <i class="fas fa-sync"></i> Actualizar
            </button>
        </div>
    </div>
@stop

@section('content')
<!-- Panel de Estadísticas -->
<div class="row" id="estadisticas-panel">
    <div class="col-md-3">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Por Validar</span>
                <span class="info-box-number" id="stat-por-validar">{{ $estadisticas['por_validar'] ?? 0 }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Validadas</span>
                <span class="info-box-number" id="stat-validadas">{{ $estadisticas['validadas'] ?? 0 }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-info">
            <span class="info-box-icon"><i class="fas fa-eye"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Observadas</span>
                <span class="info-box-number" id="stat-observadas">{{ $estadisticas['observadas'] ?? 0 }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-danger">
            <span class="info-box-icon"><i class="fas fa-times"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Anuladas</span>
                <span class="info-box-number" id="stat-anuladas">{{ $estadisticas['anuladas'] ?? 0 }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter"></i>
            Filtros de Búsqueda
        </h3>
    </div>
    <div class="card-body">
        <form method="GET" id="filtros-form">
            <div class="row">
                <div class="col-md-3">
                    <label>Buscar:</label>
                    <input type="text" name="buscar" class="form-control"
                           placeholder="Número de operación o cliente..."
                           value="{{ request('buscar') }}">
                </div>
                <div class="col-md-2">
                    <label>Estado:</label>
                    <select name="estado" class="form-control">
                        <option value="">Todos los estados</option>
                        <option value="por_validar" {{ request('estado') == 'por_validar' ? 'selected' : '' }}>
                            Por Validar
                        </option>
                        <option value="validado" {{ request('estado') == 'validado' ? 'selected' : '' }}>
                            Validado
                        </option>
                        <option value="observado" {{ request('estado') == 'observado' ? 'selected' : '' }}>
                            Observado
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Desde:</label>
                    <input type="date" name="fecha_desde" class="form-control"
                           value="{{ request('fecha_desde') }}">
                </div>
                <div class="col-md-2">
                    <label>Hasta:</label>
                    <input type="date" name="fecha_hasta" class="form-control"
                           value="{{ request('fecha_hasta') }}">
                </div>
                <div class="col-md-3">
                    <label>&nbsp;</label>
                    <div class="btn-group d-block">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="filtroRapido('hoy')">
                            Hoy
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="filtroRapido('semana')">
                            Esta Semana
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <a href="{{ route('admin.validacion-operaciones.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Operaciones -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-list"></i>
            Operaciones de Pago
        </h3>
        <div class="card-tools">
            <span class="badge badge-info">
                Total: {{ $operaciones->total() }} operaciones
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        @if($operaciones->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th width="100px">Nro. Op</th>
                            <th>Cliente</th>
                            <th>Método de Pago</th>
                            <th>Entidad Bancaria</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                            <th width="120px">Estado</th>
                            <th width="200px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($operaciones as $operacion)
                            <tr>
                                <td>
                                    @if($operacion->prestamo_id)
                                        <a href="{{ route('admin.prestamos.show', $operacion->prestamo_id) }}"
                                           class="text-primary font-weight-bold"
                                           title="Ver préstamo completo">
                                            {{ $operacion->codigo }}
                                        </a>
                                    @else
                                        <strong>{{ $operacion->codigo }}</strong>
                                    @endif
                                    @if($operacion->voucher_path)
                                        <br><small class="text-success">
                                            <i class="fas fa-paperclip"></i> Con voucher
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    @if($operacion->prestamo && $operacion->prestamo->cliente && $operacion->prestamo->cliente->persona)
                                        <strong>
                                            {{ $operacion->prestamo->cliente->persona->nombres }}
                                            {{ $operacion->prestamo->cliente->persona->apellidos }}
                                        </strong>
                                        <br>
                                        <small class="text-muted">
                                            DNI: {{ $operacion->prestamo->cliente->persona->documento }}
                                        </small>
                                    @else
                                        <span class="text-muted">Cliente no encontrado</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $operacion->metodoDePago->nombre ?? 'N/A' }}
                                </td>
                                <td>
                                    {{ $operacion->entidad_bancaria ?: '-' }}
                                </td>
                                <td>
                                    <strong>S/ {{ number_format($operacion->abono, 2) }}</strong>
                                </td>
                                <td>
                                    {{ $operacion->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    @if($operacion->estado_validacion === 'validado')
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Validado
                                        </span>
                                    @elseif($operacion->estado_validacion === 'observado')
                                        <span class="badge badge-warning">
                                            <i class="fas fa-eye"></i> Observado
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-clock"></i> Por Validar
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-info btn-sm"
                                                onclick="verDetalle({{ $operacion->id }})"
                                                title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        @if($operacion->estado_validacion !== 'validado')
                                            <button type="button" class="btn btn-success btn-sm"
                                                    onclick="validarOperacion({{ $operacion->id }})"
                                                    title="Validar">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        @endif

                                        <button type="button" class="btn btn-warning btn-sm"
                                                onclick="observarOperacion({{ $operacion->id }})"
                                                title="Observar">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </button>

                                        <button type="button" class="btn btn-danger btn-sm"
                                                onclick="anularOperacion({{ $operacion->id }})"
                                                title="Anular">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No se encontraron operaciones</h5>
                <p class="text-muted">Intenta ajustar los filtros de búsqueda.</p>
            </div>
        @endif
    </div>

    @if($operaciones->hasPages())
        <div class="card-footer">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <div class="dataTables_info">
                        Mostrando {{ $operaciones->firstItem() }} a {{ $operaciones->lastItem() }}
                        de {{ $operaciones->total() }} operaciones
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="dataTables_paginate paging_simple_numbers float-right">
                        {{ $operaciones->links() }}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Modal para Observar -->
<div class="modal fade" id="observarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    Observar Operación
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="observar-form">
                    <div class="form-group">
                        <label for="observaciones">Observaciones *</label>
                        <textarea class="form-control" id="observaciones" name="observaciones"
                                  rows="4" required maxlength="1000"
                                  placeholder="Describa el motivo de la observación..."></textarea>
                        <small class="form-text text-muted">
                            Máximo 1000 caracteres
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" onclick="confirmarObservacion()">
                    <i class="fas fa-exclamation-triangle"></i> Observar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Anular -->
<div class="modal fade" id="anularModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-times text-danger"></i>
                    Anular Operación
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>¡Atención!</strong> Esta acción anulará la operación permanentemente.
                </div>
                <form id="anular-form">
                    <div class="form-group">
                        <label for="justificacion">Justificación *</label>
                        <textarea class="form-control" id="justificacion" name="justificacion"
                                  rows="3" required maxlength="500"
                                  placeholder="Explique el motivo de la anulación..."></textarea>
                        <small class="form-text text-muted">
                            Máximo 500 caracteres
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="confirmarAnulacion()">
                    <i class="fas fa-times"></i> Anular Operación
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Detalle -->
<div class="modal fade" id="detalleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle"></i>
                    Detalle de la Operación
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detalle-content">
                <!-- El contenido se carga dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
.info-box-number {
    font-size: 1.8rem !important;
}

.table-responsive {
    border: none;
}

.dataTables_paginate {
    margin: 0;
}

.dataTables_paginate .pagination {
    margin: 0;
    padding: 0;
}

.dataTables_info {
    color: #6c757d;
    font-size: 0.875rem;
    padding-top: 0.5rem;
}

.pagination {
    justify-content: flex-end;
}

.btn-group-sm > .btn, .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.badge {
    font-size: 0.75rem;
}
</style>
@stop

@section('js')
<script>
let operacionActual = null;

$(document).ready(function() {
    // Cargar estadísticas al iniciar
    actualizarEstadisticas();
});

function filtroRapido(tipo) {
    const form = document.getElementById('filtros-form');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'filtro_rapido';
    input.value = tipo;
    form.appendChild(input);
    form.submit();
}

function actualizarEstadisticas() {
    $.get('{{ route("admin.validacion-operaciones.estadisticas") }}', function(data) {
        $('#stat-por-validar').text(data.por_validar || 0);
        $('#stat-validadas').text(data.validadas || 0);
        $('#stat-observadas').text(data.observadas || 0);
        $('#stat-anuladas').text(data.anuladas || 0);
    }).fail(function() {
        console.error('Error al cargar estadísticas');
    });
}

function validarOperacion(id) {
    if (!confirm('¿Está seguro de validar esta operación?')) {
        return;
    }

    $.ajax({
        url: '{{ route("admin.validacion-operaciones.validar", ":id") }}'.replace(':id', id),
        type: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                Swal.fire('Éxito', response.message, 'success');
                location.reload();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.message || 'Error desconocido';
            Swal.fire('Error', error, 'error');
        }
    });
}

function validarTodas() {
    if (!confirm('¿Está seguro de validar TODAS las operaciones pendientes? Esta acción no se puede deshacer.')) {
        return;
    }

    $.ajax({
        url: '{{ route("admin.validacion-operaciones.validar-todas") }}',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                Swal.fire('Éxito', response.message, 'success');
                location.reload();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.message || 'Error desconocido';
            Swal.fire('Error', error, 'error');
        }
    });
}

function observarOperacion(id) {
    operacionActual = id;
    $('#observaciones').val('');
    $('#observarModal').modal('show');
}

function confirmarObservacion() {
    const observaciones = $('#observaciones').val().trim();

    if (!observaciones) {
        Swal.fire('Error', 'Las observaciones son obligatorias', 'error');
        return;
    }

    $.ajax({
        url: '{{ route("admin.validacion-operaciones.observar", ":id") }}'.replace(':id', operacionActual),
        type: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
            observaciones: observaciones
        },
        success: function(response) {
            if (response.success) {
                $('#observarModal').modal('hide');
                Swal.fire('Éxito', response.message, 'success');
                location.reload();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.message || 'Error desconocido';
            Swal.fire('Error', error, 'error');
        }
    });
}

function anularOperacion(id) {
    operacionActual = id;
    $('#justificacion').val('');
    $('#anularModal').modal('show');
}

function confirmarAnulacion() {
    const justificacion = $('#justificacion').val().trim();

    if (!justificacion) {
        Swal.fire('Error', 'La justificación es obligatoria', 'error');
        return;
    }

    $.ajax({
        url: '{{ route("admin.validacion-operaciones.anular", ":id") }}'.replace(':id', operacionActual),
        type: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
            justificacion: justificacion
        },
        success: function(response) {
            if (response.success) {
                $('#anularModal').modal('hide');
                Swal.fire('Éxito', response.message, 'success');
                location.reload();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.message || 'Error desconocido';
            Swal.fire('Error', error, 'error');
        }
    });
}

function verDetalle(id) {
    $('#detalle-content').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>');
    $('#detalleModal').modal('show');

    $.get('{{ route("admin.validacion-operaciones.detalle", ":id") }}'.replace(':id', id))
        .done(function(data) {
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6><strong>Información de la Operación</strong></h6>
                        <table class="table table-sm">
                            <tr><td><strong>Número:</strong></td><td>${data.operacion.codigo}</td></tr>
                            <tr><td><strong>Tipo:</strong></td><td>${data.operacion.tipo_operacion}</td></tr>
                            <tr><td><strong>Monto:</strong></td><td>S/ ${parseFloat(data.operacion.abono).toFixed(2)}</td></tr>
                            <tr><td><strong>Método:</strong></td><td>${data.operacion.metodo_de_pago?.nombre || 'N/A'}</td></tr>
                            <tr><td><strong>Entidad:</strong></td><td>${data.operacion.entidad_bancaria || '-'}</td></tr>
                            <tr><td><strong>Fecha:</strong></td><td>${new Date(data.operacion.created_at).toLocaleString('es-PE')}</td></tr>
                            <tr><td><strong>Registrado por:</strong></td><td>${data.operacion.user?.name || 'N/A'}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6><strong>Información del Cliente</strong></h6>
                        <table class="table table-sm">
                            <tr><td><strong>Nombre:</strong></td><td>${data.operacion.prestamo?.cliente?.persona ?
                                data.operacion.prestamo.cliente.persona.nombres + ' ' + data.operacion.prestamo.cliente.persona.apellidos
                                : 'N/A'}</td></tr>
                            <tr><td><strong>Documento:</strong></td><td>${data.operacion.prestamo?.cliente?.persona?.documento || 'N/A'}</td></tr>
                        </table>

                        <h6><strong>Estado de Validación</strong></h6>
                        <table class="table table-sm">
                            <tr><td><strong>Estado:</strong></td><td>
                                ${data.operacion.estado_validacion === 'validado' ? '<span class="badge badge-success">Validado</span>' :
                                  data.operacion.estado_validacion === 'observado' ? '<span class="badge badge-warning">Observado</span>' :
                                  '<span class="badge badge-secondary">Por Validar</span>'}
                            </td></tr>
                            ${data.operacion.validado_por ? `<tr><td><strong>Validado por:</strong></td><td>${data.operacion.validado_por?.name || 'N/A'}</td></tr>` : ''}
                            ${data.operacion.observado_por ? `<tr><td><strong>Observado por:</strong></td><td>${data.operacion.observado_por?.name || 'N/A'}</td></tr>` : ''}
                            ${data.operacion.observaciones_validacion ? `<tr><td><strong>Observaciones:</strong></td><td>${data.operacion.observaciones_validacion}</td></tr>` : ''}
                        </table>
                    </div>
                </div>
            `;

            if (data.voucher_url && data.voucher_exists) {
                html += `
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6><strong>Voucher de Pago</strong></h6>
                            <div class="text-center">
                                <img src="${data.voucher_url}"
                                     class="img-fluid border rounded shadow-sm"
                                     style="max-height: 400px; cursor: pointer;"
                                     alt="Voucher de pago"
                                     onclick="window.open('${data.voucher_url}', '_blank')"
                                     onerror="this.parentElement.innerHTML='<div class=\"alert alert-warning\"><i class=\"fas fa-exclamation-triangle\"></i> No se pudo cargar la imagen del voucher</div>'">
                                <br><small class="text-muted mt-2 d-block">Haga clic en la imagen para verla en tamaño completo</small>
                            </div>
                        </div>
                    </div>
                `;
            } else if (data.operacion.voucher_path) {
                html += `
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6><strong>Voucher de Pago</strong></h6>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                El voucher está registrado pero el archivo no se encuentra disponible.
                                <br><small>Ruta: ${data.operacion.voucher_path}</small>
                            </div>
                        </div>
                    </div>
                `;
            }

            $('#detalle-content').html(html);
        })
        .fail(function() {
            $('#detalle-content').html('<div class="text-center text-danger">Error al cargar los detalles</div>');
        });
}
</script>
@stop