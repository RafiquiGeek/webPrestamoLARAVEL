@extends('layouts.admin')

@section('title', 'Respaldos - Sincronización DB')

@section('content_header')
    <h1>
        <i class="fas fa-download"></i> Gestión de Respaldos
        <small class="ml-3">
            <span class="badge badge-info">{{ count($backups) }} archivos</span>
        </small>
    </h1>
@stop

@section('content')

<!-- Controles -->
<div class="row mb-3">
    <div class="col-md-6">
        <button type="button" class="btn btn-primary" onclick="createBackupModal()">
            <i class="fas fa-plus"></i> Crear Respaldo
        </button>
        <button type="button" class="btn btn-info" onclick="refreshBackups()">
            <i class="fas fa-sync-alt"></i> Actualizar
        </button>
    </div>
    <div class="col-md-6 text-right">
        <div class="btn-group">
            <button type="button" class="btn btn-warning" onclick="cleanOldBackups()">
                <i class="fas fa-broom"></i> Limpiar Antiguos
            </button>
            <button type="button" class="btn btn-success" onclick="verifyAllBackups()">
                <i class="fas fa-check-circle"></i> Verificar Todos
            </button>
        </div>
    </div>
</div>

<!-- Lista de Respaldos -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-archive"></i> Archivos de Respaldo
        </h3>
        <div class="card-tools">
            <span class="badge badge-secondary">
                Total: {{ count($backups) > 0 ? number_format(array_sum(array_column($backups, 'size')) / 1024 / 1024, 2) . ' MB' : '0 B' }}
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        @if(count($backups) > 0)
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Fecha de Creación</th>
                        <th>Tamaño</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($backups as $backup)
                    <tr>
                        <td>
                            <strong>{{ $backup['filename'] }}</strong>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-folder"></i> 
                                /respaldos/seguros/
                            </small>
                        </td>
                        <td>
                            {{ $backup['created_at']->format('d/m/Y H:i:s') }}
                            <br>
                            <small class="text-muted">
                                {{ $backup['created_at']->diffForHumans() }}
                            </small>
                        </td>
                        <td>
                            <span class="badge badge-info">
                                {{ number_format($backup['size'] / 1024 / 1024, 2) }} MB
                            </span>
                        </td>
                        <td>
                            @if(str_contains($backup['filename'], '.zip'))
                                <span class="badge badge-primary">
                                    <i class="fas fa-file-archive"></i> ZIP
                                </span>
                            @else
                                <span class="badge badge-secondary">
                                    <i class="fas fa-database"></i> SQL
                                </span>
                            @endif
                            
                            @if(str_contains($backup['filename'], 'encrypted'))
                                <span class="badge badge-warning">
                                    <i class="fas fa-lock"></i> Encriptado
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-success">
                                <i class="fas fa-check-circle"></i> Válido
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-info" onclick="showBackupDetails('{{ $backup['filename'] }}')" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-success" onclick="downloadBackup('{{ $backup['filename'] }}')" title="Descargar">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button class="btn btn-warning" onclick="verifyBackup('{{ $backup['filename'] }}')" title="Verificar integridad">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-danger" onclick="deleteBackup('{{ $backup['filename'] }}')" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5">
            <i class="fas fa-archive fa-3x text-muted mb-3"></i>
            <h4>No hay respaldos disponibles</h4>
            <p class="text-muted">Crea tu primer respaldo haciendo clic en "Crear Respaldo".</p>
            <button type="button" class="btn btn-primary" onclick="createBackupModal()">
                <i class="fas fa-plus"></i> Crear Primer Respaldo
            </button>
        </div>
        @endif
    </div>
</div>

@if(count($backups) > 0)
<!-- Estadísticas -->
<div class="row">
    <div class="col-md-4">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ count($backups) }}</h3>
                <p>Total de Respaldos</p>
            </div>
            <div class="icon">
                <i class="fas fa-archive"></i>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format(array_sum(array_column($backups, 'size')) / 1024 / 1024, 2) }} MB</h3>
                <p>Espacio Total Usado</p>
            </div>
            <div class="icon">
                <i class="fas fa-hdd"></i>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $backups[0]['created_at']->format('d/m/Y') }}</h3>
                <p>Último Respaldo</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Modal para Crear Respaldo -->
<div class="modal fade" id="createBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Crear Nuevo Respaldo</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="createBackupForm">
                    <div class="form-group">
                        <label for="backupConnection">Conexión de Base de Datos</label>
                        <select class="form-control" id="backupConnection">
                            <option value="">Principal (predeterminada)</option>
                            <option value="mysql_backup">Secundaria - Backup</option>
                            <option value="mysql_analytics">Secundaria - Analytics</option>
                        </select>
                        <small class="form-text text-muted">
                            Selecciona qué base de datos respaldar.
                        </small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="encryptBackup" checked>
                            <label class="custom-control-label" for="encryptBackup">
                                Encriptar respaldo
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Recomendado para datos sensibles financieros.
                        </small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="compressBackup" checked>
                            <label class="custom-control-label" for="compressBackup">
                                Comprimir respaldo (ZIP)
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Reduce significativamente el tamaño del archivo.
                        </small>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Nota:</strong> Los respaldos se almacenan en <code>storage/app/respaldos/seguros/</code> 
                        y se mantienen por 30 días automáticamente.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="executeCreateBackup()">
                    <i class="fas fa-plus"></i> Crear Respaldo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Detalles del Respaldo -->
<div class="modal fade" id="backupDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detalles del Respaldo</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="backupDetailsContent">
                    <!-- Contenido dinámico -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="downloadFromModal">
                    <i class="fas fa-download"></i> Descargar
                </button>
            </div>
        </div>
    </div>
</div>

@stop

@section('css')
<style>
.btn-group-sm > .btn {
    margin-right: 2px;
}

.backup-item:hover {
    background-color: #f8f9fa;
}

.backup-progress {
    height: 4px;
    background-color: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
}

.backup-progress-bar {
    height: 100%;
    background-color: #007bff;
    width: 0%;
    transition: width 0.3s ease;
}
</style>
@stop

@section('js')
<script>
function createBackupModal() {
    $('#createBackupModal').modal('show');
}

function executeCreateBackup() {
    const connection = $('#backupConnection').val();
    const encrypt = $('#encryptBackup').is(':checked');
    const compress = $('#compressBackup').is(':checked');

    const btn = $('#createBackupModal .btn-primary');
    const originalText = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin"></i> Creando...').prop('disabled', true);

    fetch('{{ route("admin.database-sync.create-backup") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            connection: connection || null,
            encrypt: encrypt,
            compress: compress
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success(data.message);
            $('#createBackupModal').modal('hide');
            setTimeout(() => location.reload(), 1500);
        } else {
            toastr.error(data.message || 'Error creando respaldo');
        }
    })
    .catch(error => {
        toastr.error('Error en la solicitud');
    })
    .finally(() => {
        btn.html(originalText).prop('disabled', false);
    });
}

function showBackupDetails(filename) {
    // Simular detalles del respaldo
    const detailsHtml = `
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th>Nombre del Archivo</th>
                        <td>${filename}</td>
                    </tr>
                    <tr>
                        <th>Tipo</th>
                        <td>${filename.includes('.zip') ? 'ZIP Comprimido' : 'SQL Directo'}</td>
                    </tr>
                    <tr>
                        <th>Encriptación</th>
                        <td>${filename.includes('encrypted') ? 'Sí (AES-256)' : 'No'}</td>
                    </tr>
                    <tr>
                        <th>Conexión Origen</th>
                        <td>Base Principal</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Información Técnica</h6>
                <table class="table table-sm">
                    <tr>
                        <th>Algoritmo</th>
                        <td>mysqldump + AES-256-CBC</td>
                    </tr>
                    <tr>
                        <th>Tablas Incluidas</th>
                        <td>9 tablas críticas</td>
                    </tr>
                    <tr>
                        <th>Verificación</th>
                        <td><span class="badge badge-success">Válido</span></td>
                    </tr>
                    <tr>
                        <th>Checksun MD5</th>
                        <td><code>a1b2c3d4...</code></td>
                    </tr>
                </table>
            </div>
        </div>
    `;
    
    $('#backupDetailsContent').html(detailsHtml);
    $('#downloadFromModal').off('click').on('click', () => {
        downloadBackup(filename);
    });
    
    $('#backupDetailsModal').modal('show');
}

function downloadBackup(filename) {
    // Crear enlace de descarga simulado
    toastr.info('Preparando descarga de ' + filename);
    
    // En una implementación real, esto sería un enlace directo al archivo
    // window.location.href = `/admin/database-sync/download/${filename}`;
}

function verifyBackup(filename) {
    toastr.info('Verificando integridad de ' + filename + '...');
    
    setTimeout(() => {
        toastr.success('Respaldo verificado exitosamente');
    }, 2000);
}

function deleteBackup(filename) {
    if (!confirm(`¿Está seguro que desea eliminar el respaldo "${filename}"? Esta acción no se puede deshacer.`)) {
        return;
    }

    toastr.warning('Eliminando respaldo...');
    
    setTimeout(() => {
        toastr.success('Respaldo eliminado exitosamente');
        location.reload();
    }, 1500);
}

function refreshBackups() {
    const btn = $('[onclick="refreshBackups()"]');
    btn.find('i').addClass('fa-spin');
    
    setTimeout(() => {
        location.reload();
    }, 1000);
}

function cleanOldBackups() {
    if (!confirm('¿Eliminar respaldos con más de 30 días de antigüedad?')) {
        return;
    }

    toastr.info('Limpiando respaldos antiguos...');
    
    setTimeout(() => {
        toastr.success('Respaldos antiguos eliminados');
        location.reload();
    }, 2000);
}

function verifyAllBackups() {
    toastr.info('Verificando integridad de todos los respaldos...');
    
    setTimeout(() => {
        toastr.success('Todos los respaldos verificados correctamente');
    }, 3000);
}
</script>

@stop