@extends('layouts.admin')

@section('title', 'Gestión de Conexiones de Base de Datos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0">
                <i class="fas fa-database mr-2"></i>
                Gestión de Conexiones de Base de Datos
            </h1>
            <p class="text-muted mb-0">Configure y administre las conexiones a bases de datos externas</p>
        </div>
    </div>
@stop

@section('content')
<div class="row">
    <!-- Lista de Conexiones -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list mr-2"></i>
                    Conexiones Configuradas
                </h3>
                <div class="card-tools">
                    <button class="btn btn-primary mr-2" id="newConnectionBtn">
                        <i class="fas fa-plus mr-1"></i>
                        Nueva Conexión
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="refreshConnections()">
                        <i class="fas fa-sync-alt mr-1"></i>
                        Actualizar
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0" id="connectionsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Servidor</th>
                                <th>Base de Datos</th>
                                <th>Estado</th>
                                <th>Sincronización</th>
                                <th>Última Sync</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($connections as $connection)
                                <tr id="connection-{{ $connection->id }}">
                                    <td>
                                        <strong>{{ $connection->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $connection->driver }}</small>
                                    </td>
                                    <td>{{ $connection->description ?? '-' }}</td>
                                    <td>
                                        {{ $connection->host }}:{{ $connection->port }}
                                        <br>
                                        <small class="text-muted">{{ $connection->username }}</small>
                                    </td>
                                    <td>{{ $connection->database }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <button class="btn btn-sm toggle-btn mr-2" 
                                                    onclick="toggleConnection({{ $connection->id }})"
                                                    data-active="{{ $connection->is_active }}">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                            <span class="status-badge" id="status-{{ $connection->id }}">
                                                @if($connection->is_active)
                                                    <span class="badge bg-success">Activa</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactiva</span>
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($connection->is_sync_enabled)
                                            <span class="badge bg-info">
                                                <i class="fas fa-sync mr-1"></i>
                                                Habilitada
                                            </span>
                                            @if($connection->sync_tables)
                                                <br>
                                                <small class="text-muted">
                                                    {{ count($connection->sync_tables) }} tablas
                                                </small>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">Deshabilitada</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($connection->last_sync_at)
                                            <span title="{{ $connection->last_sync_at->format('d/m/Y H:i:s') }}">
                                                {{ $connection->last_sync_at->diffForHumans() }}
                                            </span>
                                            @if($connection->sync_errors)
                                                <br>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                    Errores
                                                </span>
                                            @else
                                                <br>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check mr-1"></i>
                                                    Exitosa
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted">Nunca</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-info" 
                                                    onclick="testConnection({{ $connection->id }})"
                                                    title="Probar Conexión">
                                                <i class="fas fa-plug"></i>
                                            </button>
                                            <button class="btn btn-outline-primary" 
                                                    onclick="editConnection({{ $connection->id }})"
                                                    title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @if($connection->is_sync_enabled)
                                                <button class="btn btn-outline-success" 
                                                        onclick="syncTables({{ $connection->id }})"
                                                        title="Sincronizar Tablas">
                                                    <i class="fas fa-sync"></i>
                                                </button>
                                            @endif
                                            <button class="btn btn-outline-danger" 
                                                    onclick="deleteConnection({{ $connection->id }})"
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-database fa-3x mb-3"></i>
                                            <h5>No hay conexiones configuradas</h5>
                                            <p>Haga clic en "Nueva Conexión" para agregar su primera conexión de base de datos.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Conexión -->
<div class="modal fade" id="connectionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-database mr-2"></i>
                    <span id="modalTitle">Nueva Conexión de Base de Datos</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="connectionForm">
                <div class="modal-body">
                    <input type="hidden" id="connectionId" name="id">
                    
                    <div class="row">
                        <!-- Información Básica -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-info-circle mr-1"></i>
                                Información Básica
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre de la Conexión *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <div class="form-text">Nombre único para identificar esta conexión</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="driver" class="form-label">Tipo de Base de Datos *</label>
                                <select class="form-control" id="driver" name="driver" required>
                                    <option value="mysql">MySQL</option>
                                    <option value="pgsql">PostgreSQL</option>
                                    <option value="sqlite">SQLite</option>
                                    <option value="sqlsrv">SQL Server</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">Descripción</label>
                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                            </div>
                        </div>
                        
                        <!-- Configuración de Conexión -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-server mr-1"></i>
                                Configuración de Conexión
                            </h6>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="host" class="form-label">Servidor/Host *</label>
                                <input type="text" class="form-control" id="host" name="host" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="port" class="form-label">Puerto *</label>
                                <input type="number" class="form-control" id="port" name="port" value="3306" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="database" class="form-label">Base de Datos *</label>
                                <input type="text" class="form-control" id="database" name="database" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Usuario *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="passwordIcon"></i>
                                    </button>
                                </div>
                                <div class="form-text edit-mode" style="display: none;">
                                    Dejar vacío para mantener la contraseña actual
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="prefix" class="form-label">Prefijo de Tablas</label>
                                <input type="text" class="form-control" id="prefix" name="prefix">
                            </div>
                        </div>
                        
                        <!-- Configuración de Sincronización -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-sync mr-1"></i>
                                Configuración de Sincronización
                            </h6>
                        </div>
                        
                        <div class="col-12">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_sync_enabled" name="is_sync_enabled" value="1">
                                    <label class="form-check-label" for="is_sync_enabled">
                                        Habilitar sincronización de tablas
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12" id="syncTablesSection" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Tablas a Sincronizar</label>
                                <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                    @foreach($available_tables as $table)
                                        <div class="form-check">
                                            <input class="form-check-input sync-table" type="checkbox" 
                                                   id="table_{{ $table }}" name="sync_tables[]" value="{{ $table }}">
                                            <label class="form-check-label" for="table_{{ $table }}">
                                                {{ $table }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="form-text">
                                    Seleccione las tablas que desea sincronizar con esta conexión
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-auto" id="testConnectionBtn">
                        <i class="fas fa-plug mr-1"></i>
                        Probar Conexión
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>
                        Guardar Conexión
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Sincronización -->
<div class="modal fade" id="syncModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-sync mr-2"></i>
                    Sincronizar Tablas
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    Este proceso creará o actualizará las tablas seleccionadas en la base de datos de destino.
                </div>
                
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="forceSync">
                    <label class="form-check-label" for="forceSync">
                        Forzar sincronización (recrear tablas existentes)
                    </label>
                </div>
                
                <div id="syncOutput" class="border rounded p-3 bg-light" style="display: none; max-height: 200px; overflow-y: auto;">
                    <pre id="syncOutputText"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-success" id="startSyncBtn">
                    <i class="fas fa-sync mr-1"></i>
                    Iniciar Sincronización
                </button>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
    <style>
        .toggle-btn[data-active="true"] {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }
        
        .toggle-btn[data-active="false"] {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
        }
        
        .status-badge .badge {
            font-size: 0.75rem;
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .modal-header {
            background-color: #007bff;
            color: white;
        }
        
        .modal-header .btn-close {
            filter: invert(1);
        }
        
        .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
        }
        
        #syncOutput pre {
            font-size: 0.875rem;
            margin: 0;
            white-space: pre-wrap;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let currentConnectionId = null;
        
        $(document).ready(function() {
            // Debug: verificar que el botón existe
            console.log('Botón Nueva Conexión encontrado:', $('#newConnectionBtn').length);
            
            // Evento adicional para el botón de nueva conexión
            $('#newConnectionBtn').click(function() {
                console.log('Botón Nueva Conexión clickeado');
                resetForm();
                $('#connectionModal').modal('show');
            });
            
            // Toggle sync tables section
            $('#is_sync_enabled').change(function() {
                if ($(this).is(':checked')) {
                    $('#syncTablesSection').slideDown();
                } else {
                    $('#syncTablesSection').slideUp();
                    $('.sync-table').prop('checked', false);
                }
            });
            
            // Form submission
            $('#connectionForm').submit(function(e) {
                e.preventDefault();
                saveConnection();
            });
            
            // Test connection button in modal
            $('#testConnectionBtn').click(function() {
                testConnectionFromForm();
            });
            
            // Sync start button
            $('#startSyncBtn').click(function() {
                startSync();
            });
            
            // Reset modal on hide
            $('#connectionModal').on('hidden.bs.modal', function() {
                resetForm();
            });
        });
        
        function resetForm() {
            $('#connectionForm')[0].reset();
            $('#connectionId').val('');
            $('#modalTitle').text('Nueva Conexión de Base de Datos');
            $('#syncTablesSection').hide();
            $('.edit-mode').hide();
            currentConnectionId = null;
        }
        
        function editConnection(id) {
            currentConnectionId = id;
            $('#modalTitle').text('Editar Conexión de Base de Datos');
            $('.edit-mode').show();
            $('#password').prop('required', false);

            $.ajax({
                url: `/admin/database-sync/connections/${id}`,
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        const conn = response.connection;
                        $('#connectionId').val(conn.id);
                        $('#name').val(conn.name);
                        $('#description').val(conn.description || '');
                        $('#driver').val(conn.driver);
                        $('#host').val(conn.host);
                        $('#port').val(conn.port);
                        $('#database').val(conn.database);
                        $('#username').val(conn.username);
                        $('#prefix').val(conn.prefix || '');

                        if (conn.is_sync_enabled) {
                            $('#is_sync_enabled').prop('checked', true);
                            $('#syncTablesSection').show();
                            if (conn.sync_tables && conn.sync_tables.length) {
                                conn.sync_tables.forEach(function(table) {
                                    $(`#table_${table}`).prop('checked', true);
                                });
                            }
                        }

                        $('#connectionModal').modal('show');
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudieron cargar los datos de la conexión'
                    });
                }
            });
        }
        
        function saveConnection() {
            const formData = new FormData($('#connectionForm')[0]);
            const url = currentConnectionId ?
                `/admin/database-sync/connections/${currentConnectionId}` :
                '/admin/database-sync/connections';

            if (currentConnectionId) {
                formData.append('_method', 'PUT');
            }

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: response.message
                        });
                        $('#connectionModal').modal('hide');
                        location.reload(); // Recargar para ver cambios
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Error guardando la conexión'
                    });
                }
            });
        }
        
        function testConnection(id) {
            const url = `/admin/database-sync/connections/${id}/test`;
            
            Swal.fire({
                title: 'Probando conexión...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: url,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    Swal.fire({
                        icon: response.success ? 'success' : 'error',
                        title: response.success ? '¡Conexión exitosa!' : 'Error de conexión',
                        text: response.message
                    });
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Error probando la conexión'
                    });
                }
            });
        }
        
        function testConnectionFromForm() {
            const formData = new FormData($('#connectionForm')[0]);
            
            Swal.fire({
                title: 'Probando conexión...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: '/admin/database-sync/connections/test',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    Swal.fire({
                        icon: response.success ? 'success' : 'error',
                        title: response.success ? '¡Conexión exitosa!' : 'Error de conexión',
                        text: response.message
                    });
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Error probando la conexión'
                    });
                }
            });
        }
        
        function toggleConnection(id) {
            $.ajax({
                url: `/admin/database-sync/connections/${id}/toggle`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        const button = $(`.toggle-btn[onclick="toggleConnection(${id})"]`);
                        const statusSpan = $(`#status-${id} .badge`);
                        
                        button.attr('data-active', response.is_active);
                        
                        if (response.is_active) {
                            button.removeClass('btn-secondary').addClass('btn-success');
                            statusSpan.removeClass('bg-secondary').addClass('bg-success').text('Activa');
                        } else {
                            button.removeClass('btn-success').addClass('btn-secondary');
                            statusSpan.removeClass('bg-success').addClass('bg-secondary').text('Inactiva');
                        }
                        
                        showToast('success', response.message);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    showToast('error', response.message || 'Error cambiando estado');
                }
            });
        }
        
        function deleteConnection(id) {
            Swal.fire({
                title: '¿Está seguro?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/database-sync/connections/${id}`,
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                $(`#connection-${id}`).fadeOut(() => {
                                    $(`#connection-${id}`).remove();
                                });
                                showToast('success', response.message);
                            }
                        },
                        error: function(xhr) {
                            const response = xhr.responseJSON;
                            showToast('error', response.message || 'Error eliminando conexión');
                        }
                    });
                }
            });
        }
        
        function syncTables(id) {
            currentConnectionId = id;
            $('#syncModal').modal('show');
        }
        
        function startSync() {
            const force = $('#forceSync').is(':checked');
            
            $('#syncOutput').show();
            $('#syncOutputText').text('Iniciando sincronización...\n');
            $('#startSyncBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Sincronizando...');
            
            $.ajax({
                url: '/admin/database-sync/sync-tables',
                method: 'POST',
                data: {
                    connection_id: currentConnectionId,
                    force: force
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#syncOutputText').text(response.output || 'Sincronización completada exitosamente');
                    $('#startSyncBtn').prop('disabled', false).html('<i class="fas fa-check mr-1"></i>Completado');
                    
                    if (response.success) {
                        showToast('success', response.message);
                        // Actualizar la página después de un breve delay
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    $('#syncOutputText').text('Error: ' + (response.message || 'Error en la sincronización'));
                    $('#startSyncBtn').prop('disabled', false).html('<i class="fas fa-sync mr-1"></i>Reintentar');
                    showToast('error', response.message || 'Error en la sincronización');
                }
            });
        }
        
        function refreshConnections() {
            location.reload();
        }
        
        function togglePassword() {
            const passwordField = $('#password');
            const passwordIcon = $('#passwordIcon');
            
            if (passwordField.attr('type') === 'password') {
                passwordField.attr('type', 'text');
                passwordIcon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordField.attr('type', 'password');
                passwordIcon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        }
        
        function showToast(type, message) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            
            Toast.fire({
                icon: type,
                title: message
            });
        }
    </script>
@stop