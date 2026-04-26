@extends('adminlte::page')

@section('title', 'Gestión de Migraciones')

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1>
            <i class="fas fa-database"></i>
            Gestión de Migraciones de Base de Datos
        </h1>
        <div>
            <button type="button" class="btn btn-info btn-sm" onclick="cargarEstado()">
                <i class="fas fa-sync"></i> Actualizar
            </button>
        </div>
    </div>
@stop

@section('content')
@if(isset($error))
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        {{ $error }}
    </div>
@endif

<!-- Panel de Estadísticas -->
<div class="row" id="estadisticas-panel">
    <div class="col-md-3">
        <div class="info-box bg-info">
            <span class="info-box-icon"><i class="fas fa-list"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Migraciones</span>
                <span class="info-box-number" id="total-migraciones">-</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Ejecutadas</span>
                <span class="info-box-number" id="ejecutadas">-</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pendientes</span>
                <span class="info-box-number" id="pendientes">-</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-gradient-primary"><i class="fas fa-database"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Conexión BD</span>
                <span class="info-box-number" id="conexion-bd">-</span>
            </div>
        </div>
    </div>
</div>

<!-- Botones de Acción Masiva -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-tools"></i>
            Acciones Masivas
        </h3>
    </div>
    <div class="card-body">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-success" onclick="ejecutarTodas()">
                <i class="fas fa-play"></i> Ejecutar Todas las Pendientes
            </button>
            <button type="button" class="btn btn-warning" onclick="hacerRollback()">
                <i class="fas fa-undo"></i> Rollback (1 paso)
            </button>
            @if(config('app.env') !== 'production')
                <button type="button" class="btn btn-danger" onclick="freshMigration()">
                    <i class="fas fa-refresh"></i> Fresh (PELIGROSO)
                </button>
            @endif
        </div>
        <small class="form-text text-muted mt-2">
            <i class="fas fa-info-circle"></i>
            <strong>Ejecutar Todas:</strong> Ejecuta todas las migraciones pendientes.
            <strong>Rollback:</strong> Revierte la última migración ejecutada.
            @if(config('app.env') !== 'production')
                <strong>Fresh:</strong> Borra toda la base de datos y ejecuta todas las migraciones desde cero.
            @endif
        </small>
    </div>
</div>

<!-- Lista de Migraciones -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-list"></i>
            Lista de Migraciones
        </h3>
        <div class="card-tools">
            <div class="btn-group">
                <button type="button" class="btn btn-default dropdown-toggle btn-sm" data-toggle="dropdown">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item filter-btn" href="#" data-filter="all">Todas</a>
                    <a class="dropdown-item filter-btn" href="#" data-filter="ejecutada">Ejecutadas</a>
                    <a class="dropdown-item filter-btn" href="#" data-filter="pendiente">Pendientes</a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        @if(!empty($migrationsData))
            <table class="table table-hover text-nowrap" id="migrations-table">
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th>Nombre de Migración</th>
                        <th>Batch</th>
                        <th>Tamaño</th>
                        <th>Modificado</th>
                        <th width="120px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($migrationsData as $migration)
                        <tr class="migration-row" data-status="{{ $migration['status'] }}">
                            <td>
                                @if($migration['status'] === 'ejecutada')
                                    <span class="badge badge-success">
                                        <i class="fas fa-check"></i> Ejecutada
                                    </span>
                                @else
                                    <span class="badge badge-warning">
                                        <i class="fas fa-clock"></i> Pendiente
                                    </span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $migration['name'] }}</strong>
                                @if(!$migration['file_exists'])
                                    <br><small class="text-danger">
                                        <i class="fas fa-exclamation-triangle"></i> Archivo no encontrado
                                    </small>
                                @endif
                            </td>
                            <td>
                                @if($migration['batch'])
                                    <span class="badge badge-secondary">{{ $migration['batch'] }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($migration['size'])
                                    {{ number_format($migration['size'] / 1024, 1) }} KB
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($migration['modified'])
                                    <small>{{ $migration['modified']->format('d/m/Y H:i') }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($migration['status'] === 'pendiente' && $migration['file_exists'])
                                    <button type="button" class="btn btn-sm btn-success"
                                            onclick="ejecutarMigracion('{{ $migration['filename'] }}')"
                                            title="Ejecutar migración">
                                        <i class="fas fa-play"></i>
                                    </button>
                                @endif
                                @if($migration['file_exists'])
                                    <button type="button" class="btn btn-sm btn-info"
                                            onclick="verMigracion('{{ $migration['filename'] }}')"
                                            title="Ver contenido">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-4">
                <i class="fas fa-database fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No se pudieron cargar las migraciones</h5>
                <p class="text-muted">Verifique la conexión a la base de datos y los permisos.</p>
                <button type="button" class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-refresh"></i> Intentar de nuevo
                </button>
            </div>
        @endif
    </div>
</div>

<!-- Modal para mostrar output/logs -->
<div class="modal fade" id="outputModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-terminal"></i>
                    Resultado de la Operación
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <pre id="output-content" class="bg-dark text-light p-3" style="font-size: 0.85rem; max-height: 400px; overflow-y: auto;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Rollback con opciones -->
<div class="modal fade" id="rollbackModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-undo text-warning"></i>
                    Rollback de Migraciones
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>¡Cuidado!</strong> Esta acción revertirá las migraciones seleccionadas y puede afectar la estructura de la base de datos.
                </div>
                <div class="form-group">
                    <label for="rollback-steps">Número de pasos a retroceder:</label>
                    <input type="number" class="form-control" id="rollback-steps" value="1" min="1" max="10">
                    <small class="form-text text-muted">Número de migraciones a revertir desde la más reciente.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" onclick="confirmarRollback()">
                    <i class="fas fa-undo"></i> Ejecutar Rollback
                </button>
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
.migration-row.filtered-out {
    display: none;
}
pre {
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    cargarEstado();

    // Filtros
    $('.filter-btn').on('click', function(e) {
        e.preventDefault();
        const filter = $(this).data('filter');
        filtrarMigraciones(filter);
    });
});

function cargarEstado() {
    $.get('{{ route("admin.migraciones.estado") }}', function(data) {
        $('#total-migraciones').text(data.total_migraciones || '-');
        $('#ejecutadas').text(data.ejecutadas || '-');
        $('#pendientes').text(data.pendientes || '-');
        $('#conexion-bd').text(data.conexion_bd || '-')
            .parent().removeClass('bg-success bg-danger')
            .addClass(data.conexion_bd === 'conectada' ? 'bg-success' : 'bg-danger');
    }).fail(function() {
        console.error('Error al cargar estadísticas');
    });
}

function ejecutarMigracion(filename) {
    if (!confirm(`¿Ejecutar la migración ${filename}?`)) {
        return;
    }

    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    $.post('{{ route("admin.migraciones.ejecutar") }}', {
        migration: filename.replace('.php', ''),
        '_token': '{{ csrf_token() }}'
    })
    .done(function(response) {
        mostrarOutput(response.message, response.output);
        setTimeout(() => location.reload(), 1500);
    })
    .fail(function(xhr) {
        const error = xhr.responseJSON?.message || 'Error desconocido';
        Swal.fire('Error', error, 'error');
    })
    .always(function() {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });
}

function ejecutarTodas() {
    if (!confirm('¿Ejecutar todas las migraciones pendientes?')) {
        return;
    }

    $.post('{{ route("admin.migraciones.ejecutar-todas") }}', {
        '_token': '{{ csrf_token() }}'
    })
    .done(function(response) {
        mostrarOutput(response.message, response.output);
        setTimeout(() => location.reload(), 2000);
    })
    .fail(function(xhr) {
        const error = xhr.responseJSON?.message || 'Error desconocido';
        Swal.fire('Error', error, 'error');
    });
}

function hacerRollback() {
    $('#rollbackModal').modal('show');
}

function confirmarRollback() {
    const steps = parseInt($('#rollback-steps').val()) || 1;

    $('#rollbackModal').modal('hide');

    $.post('{{ route("admin.migraciones.rollback") }}', {
        steps: steps,
        '_token': '{{ csrf_token() }}'
    })
    .done(function(response) {
        mostrarOutput(response.message, response.output);
        setTimeout(() => location.reload(), 2000);
    })
    .fail(function(xhr) {
        const error = xhr.responseJSON?.message || 'Error desconocido';
        Swal.fire('Error', error, 'error');
    });
}

@if(config('app.env') !== 'production')
function freshMigration() {
    Swal.fire({
        title: '¡PELIGRO!',
        text: 'Esta acción eliminará TODA la base de datos y ejecutará las migraciones desde cero. ¿Está completamente seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, ejecutar Fresh',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('{{ route("admin.migraciones.fresh") }}', {
                '_token': '{{ csrf_token() }}'
            })
            .done(function(response) {
                mostrarOutput(response.message, response.output);
                setTimeout(() => location.reload(), 3000);
            })
            .fail(function(xhr) {
                const error = xhr.responseJSON?.message || 'Error desconocido';
                Swal.fire('Error', error, 'error');
            });
        }
    });
}
@endif

function verMigracion(filename) {
    // Esta función podría implementarse para mostrar el contenido del archivo
    Swal.fire('Información', 'Funcionalidad de vista previa no implementada', 'info');
}

function mostrarOutput(message, output) {
    $('#output-content').text(output || 'Sin output disponible');
    $('#outputModal .modal-title').html('<i class="fas fa-terminal"></i> ' + message);
    $('#outputModal').modal('show');
}

function filtrarMigraciones(filter) {
    const rows = $('.migration-row');

    if (filter === 'all') {
        rows.removeClass('filtered-out');
    } else {
        rows.each(function() {
            const status = $(this).data('status');
            if (status === filter) {
                $(this).removeClass('filtered-out');
            } else {
                $(this).addClass('filtered-out');
            }
        });
    }
}
</script>
@stop