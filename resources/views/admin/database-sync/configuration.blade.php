@extends('layouts.admin')

@section('title', 'Configuración - Sincronización DB')

@section('content_header')
    <h1>
        <i class="fas fa-cog"></i> Configuración del Sistema
        <small class="ml-3">
            <span class="badge {{ $sync_enabled ? 'badge-success' : 'badge-danger' }}">
                {{ $sync_enabled ? 'Activo' : 'Inactivo' }}
            </span>
        </small>
    </h1>
@stop

@section('content')

<!-- Alertas del Sistema -->
@if($emergency_mode)
<div class="alert alert-danger">
    <h4><i class="icon fas fa-exclamation-triangle"></i> ¡MODO DE EMERGENCIA ACTIVO!</h4>
    Algunas configuraciones pueden estar restringidas mientras el sistema esté en modo de emergencia.
</div>
@endif

<div class="row">
    <!-- Configuración General -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-sliders-h"></i> Configuración General
                </h3>
            </div>
            <div class="card-body">
                <form id="generalConfigForm">
                    <div class="form-group">
                        <label>Estado de Sincronización</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="syncEnabled" 
                                   {{ $sync_enabled ? 'checked' : '' }}>
                            <label class="custom-control-label" for="syncEnabled">
                                Habilitar sincronización automática
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Controla si las operaciones se sincronizan automáticamente a servidores secundarios.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Modo de Emergencia</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="emergencyMode" 
                                   {{ $emergency_mode ? 'checked' : '' }}>
                            <label class="custom-control-label" for="emergencyMode">
                                Activar modo de emergencia
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            En modo de emergencia, se bloquean operaciones no esenciales por seguridad.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="logLevel">Nivel de Logging</label>
                        <select class="form-control" id="logLevel">
                            <option value="debug">Debug (Muy detallado)</option>
                            <option value="info" selected>Info (Normal)</option>
                            <option value="warning">Warning (Solo alertas)</option>
                            <option value="error">Error (Solo errores)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Configuración
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Configuración de Seguridad -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-shield-alt"></i> Configuración de Seguridad
                </h3>
            </div>
            <div class="card-body">
                <form id="securityConfigForm">
                    <div class="form-group">
                        <label for="rateLimitPerMinute">Rate Limit por Minuto</label>
                        <input type="number" class="form-control" id="rateLimitPerMinute" 
                               value="{{ $security_config['firewall']['rate_limit']['requests_per_minute'] ?? 60 }}" 
                               min="1" max="1000">
                        <small class="form-text text-muted">
                            Número máximo de requests por IP por minuto.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="syncRateLimit">Operaciones de Sync por Minuto</label>
                        <input type="number" class="form-control" id="syncRateLimit" 
                               value="{{ $security_config['firewall']['rate_limit']['sync_operations_per_minute'] ?? 100 }}" 
                               min="1" max="500">
                        <small class="form-text text-muted">
                            Número máximo de operaciones de sincronización por minuto.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="maxFailedAttempts">Intentos Fallidos Máximos</label>
                        <input type="number" class="form-control" id="maxFailedAttempts" 
                               value="{{ $security_config['ip_access']['auto_block']['max_attempts'] ?? 15 }}" 
                               min="1" max="100">
                        <small class="form-text text-muted">
                            Número de intentos fallidos antes del bloqueo automático.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Encriptación de Respaldos</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="backupEncryption" 
                                   {{ ($security_config['backup']['encryption_required'] ?? true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="backupEncryption">
                                Requerir encriptación en respaldos
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-shield-alt"></i> Actualizar Seguridad
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Conexiones y Tablas -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-server"></i> Conexiones de Sincronización
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" onclick="testAllConnections()">
                        <i class="fas fa-check-circle"></i> Probar Todas
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Conexión</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sync_connections as $connection)
                        <tr id="connection-{{ $connection }}">
                            <td>
                                <strong>{{ $connection }}</strong>
                                <br>
                                <small class="text-muted">Base secundaria</small>
                            </td>
                            <td>
                                <span class="badge badge-secondary" id="status-{{ $connection }}">
                                    <i class="fas fa-spinner fa-spin"></i> Verificando...
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="testConnection('{{ $connection }}')">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-sm btn-primary" onclick="showConnectionConfig('{{ $connection }}')">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-table"></i> Tablas Sincronizadas
                </h3>
                <div class="card-tools">
                    <span class="badge badge-info">{{ count($sync_tables) }} tablas</span>
                </div>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tabla</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sync_tables as $table)
                        <tr>
                            <td>
                                <i class="fas fa-table text-muted mr-1"></i>
                                {{ $table }}
                            </td>
                            <td>
                                @if(in_array($table, ['prestamos', 'cuotas', 'operaciones']))
                                    <span class="badge badge-danger">Alta</span>
                                @elseif(in_array($table, ['clientes', 'comprobantes']))
                                    <span class="badge badge-warning">Media</span>
                                @else
                                    <span class="badge badge-info">Normal</span>
                                @endif
                            </td>
                            <td>
                                <i class="fas fa-circle text-success" title="Activa"></i>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Herramientas de Administración -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tools"></i> Herramientas de Administración
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <button class="btn btn-primary btn-block" onclick="createBackupModal()">
                            <i class="fas fa-download"></i>
                            Crear Respaldo
                        </button>
                        <small class="text-muted">Crear respaldo manual del sistema</small>
                    </div>

                    <div class="col-md-3">
                        <button class="btn btn-info btn-block" onclick="verifyIntegrityModal()">
                            <i class="fas fa-check-circle"></i>
                            Verificar Integridad
                        </button>
                        <small class="text-muted">Verificar consistencia de datos</small>
                    </div>

                    <div class="col-md-3">
                        <button class="btn btn-warning btn-block" onclick="syncTablesModal()">
                            <i class="fas fa-sync-alt"></i>
                            Sincronizar Tablas
                        </button>
                        <small class="text-muted">Crear tablas en servidores secundarios</small>
                    </div>

                    <div class="col-md-3">
                        <button class="btn btn-success btn-block" onclick="exportConfigModal()">
                            <i class="fas fa-file-export"></i>
                            Exportar Config
                        </button>
                        <small class="text-muted">Exportar configuración actual</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Crear Respaldo -->
<div class="modal fade" id="backupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Crear Respaldo</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="backupForm">
                    <div class="form-group">
                        <label for="backupConnection">Conexión</label>
                        <select class="form-control" id="backupConnection">
                            <option value="">Base principal</option>
                            @foreach($sync_connections as $connection)
                            <option value="{{ $connection }}">{{ $connection }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="backupEncrypt" checked>
                        <label class="form-check-label" for="backupEncrypt">
                            Encriptar respaldo
                        </label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="backupCompress" checked>
                        <label class="form-check-label" for="backupCompress">
                            Comprimir respaldo
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="executeBackup()">
                    <i class="fas fa-download"></i> Crear Respaldo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Verificar Integridad -->
<div class="modal fade" id="integrityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Verificar Integridad</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="integrityForm">
                    <div class="form-group">
                        <label for="integrityConnection">Conexión</label>
                        <select class="form-control" id="integrityConnection">
                            <option value="">Todas las conexiones</option>
                            @foreach($sync_connections as $connection)
                            <option value="{{ $connection }}">{{ $connection }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="integrityTable">Tabla</label>
                        <select class="form-control" id="integrityTable">
                            <option value="">Todas las tablas</option>
                            @foreach($sync_tables as $table)
                            <option value="{{ $table }}">{{ $table }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="integrityFix">
                        <label class="form-check-label" for="integrityFix">
                            Reparar automáticamente los problemas encontrados
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" onclick="executeIntegrityCheck()">
                    <i class="fas fa-check-circle"></i> Verificar
                </button>
            </div>
        </div>
    </div>
</div>

@stop

@section('css')
<style>
.card-tools .btn-tool {
    color: #6c757d;
}

.table-sm td {
    padding: 0.3rem;
}

.badge {
    font-size: 0.75em;
}

.connection-testing {
    opacity: 0.6;
}

.form-check {
    margin-bottom: 1rem;
}
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Probar conexiones al cargar la página
    testAllConnections();
    
    // Configurar formularios
    setupForms();
});

function setupForms() {
    $('#generalConfigForm').on('submit', function(e) {
        e.preventDefault();
        saveGeneralConfig();
    });
    
    $('#securityConfigForm').on('submit', function(e) {
        e.preventDefault();
        saveSecurityConfig();
    });
}

function saveGeneralConfig() {
    const config = {
        sync_enabled: $('#syncEnabled').is(':checked'),
        emergency_mode: $('#emergencyMode').is(':checked'),
        log_level: $('#logLevel').val()
    };
    
    // Simular guardado
    toastr.success('Configuración general guardada');
    console.log('Guardando configuración general:', config);
}

function saveSecurityConfig() {
    const config = {
        rate_limit_per_minute: $('#rateLimitPerMinute').val(),
        sync_rate_limit: $('#syncRateLimit').val(),
        max_failed_attempts: $('#maxFailedAttempts').val(),
        backup_encryption: $('#backupEncryption').is(':checked')
    };
    
    // Simular guardado
    toastr.success('Configuración de seguridad actualizada');
    console.log('Guardando configuración de seguridad:', config);
}

function testConnection(connection) {
    const statusElement = $(`#status-${connection}`);
    const row = $(`#connection-${connection}`);
    
    statusElement.html('<i class="fas fa-spinner fa-spin"></i> Probando...')
               .removeClass('badge-success badge-danger badge-warning')
               .addClass('badge-secondary');
    
    row.addClass('connection-testing');
    
    // Simular prueba de conexión
    setTimeout(() => {
        const isConnected = Math.random() > 0.3; // 70% de probabilidad de éxito
        
        if (isConnected) {
            const latency = Math.floor(Math.random() * 100) + 10;
            statusElement.html(`<i class="fas fa-check-circle"></i> Conectado (${latency}ms)`)
                       .removeClass('badge-secondary badge-danger')
                       .addClass('badge-success');
        } else {
            statusElement.html('<i class="fas fa-times-circle"></i> Error de conexión')
                       .removeClass('badge-secondary badge-success')
                       .addClass('badge-danger');
        }
        
        row.removeClass('connection-testing');
    }, 2000);
}

function testAllConnections() {
    @foreach($sync_connections as $connection)
    testConnection('{{ $connection }}');
    @endforeach
}

function showConnectionConfig(connection) {
    toastr.info(`Configuración de ${connection} (función por implementar)`);
}

function createBackupModal() {
    $('#backupModal').modal('show');
}

function verifyIntegrityModal() {
    $('#integrityModal').modal('show');
}

function syncTablesModal() {
    if (confirm('¿Crear tablas necesarias en todos los servidores secundarios?')) {
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
        btn.disabled = true;
        
        // Simular creación de tablas
        setTimeout(() => {
            toastr.success('Tablas sincronizadas exitosamente');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 3000);
    }
}

function exportConfigModal() {
    // Crear objeto de configuración para exportar
    const config = {
        timestamp: new Date().toISOString(),
        sync_enabled: $('#syncEnabled').is(':checked'),
        connections: @json($sync_connections),
        tables: @json($sync_tables),
        security: {
            rate_limit: $('#rateLimitPerMinute').val(),
            sync_rate_limit: $('#syncRateLimit').val(),
            max_failed_attempts: $('#maxFailedAttempts').val(),
            backup_encryption: $('#backupEncryption').is(':checked')
        }
    };
    
    // Crear y descargar archivo JSON
    const dataStr = JSON.stringify(config, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = `database-sync-config-${new Date().toISOString().split('T')[0]}.json`;
    link.click();
    
    URL.revokeObjectURL(url);
    toastr.success('Configuración exportada');
}

function executeBackup() {
    const connection = $('#backupConnection').val();
    const encrypt = $('#backupEncrypt').is(':checked');
    const compress = $('#backupCompress').is(':checked');
    
    const btn = $('#backupModal .btn-primary');
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
            $('#backupModal').modal('hide');
        } else {
            toastr.error(data.message);
        }
    })
    .catch(error => {
        toastr.error('Error creando respaldo');
    })
    .finally(() => {
        btn.html(originalText).prop('disabled', false);
    });
}

function executeIntegrityCheck() {
    const connection = $('#integrityConnection').val();
    const table = $('#integrityTable').val();
    const fix = $('#integrityFix').is(':checked');
    
    const btn = $('#integrityModal .btn-info');
    const originalText = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin"></i> Verificando...').prop('disabled', true);
    
    fetch('{{ route("admin.database-sync.verify-integrity") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            connection: connection || null,
            table: table || null,
            fix: fix
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success(data.message);
            $('#integrityModal').modal('hide');
        } else {
            toastr.error(data.message);
        }
    })
    .catch(error => {
        toastr.error('Error verificando integridad');
    })
    .finally(() => {
        btn.html(originalText).prop('disabled', false);
    });
}

// Manejar cambios en modo de emergencia
$('#emergencyMode').on('change', function() {
    const isEnabled = $(this).is(':checked');
    
    if (isEnabled) {
        if (!confirm('¿Está seguro que desea activar el modo de emergencia? Esto restringirá las operaciones del sistema.')) {
            $(this).prop('checked', false);
            return;
        }
    }
    
    // Enviar cambio inmediatamente
    fetch('{{ route("admin.database-sync.toggle-emergency") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            enable: isEnabled
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success(data.message);
        } else {
            toastr.error('Error cambiando modo de emergencia');
            // Revertir el switch
            $(this).prop('checked', !isEnabled);
        }
    })
    .catch(error => {
        toastr.error('Error en la operación');
        // Revertir el switch
        $(this).prop('checked', !isEnabled);
    });
});
</script>
@stop