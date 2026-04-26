@extends('layouts.admin')

@section('title', 'Centro de Sincronizaci�n de Bases de Datos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0">
                <i class="fas fa-sync-alt me-2"></i>
                Centro de Sincronizaci�n de Bases de Datos
            </h1>
            <p class="text-muted mb-0">Administre y configure conexiones de bases de datos externas</p>
        </div>
        <div>
            <a href="{{ route('admin.database-sync.connections') }}" class="btn btn-primary">
                <i class="fas fa-database me-1"></i>
                Gestionar Conexiones
            </a>
        </div>
    </div>
@stop

@section('content')
<div class="row">
    <!-- Panel de Estado General -->
    <div class="col-12 mb-4">
        <div class="row">
            <!-- Conexiones Activas -->
            <div class="col-lg-3 col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3 class="card-title mb-1" id="activeConnections">{{ $activeConnections ?? 0 }}</h3>
                                <p class="card-text">Conexiones Activas</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-plug fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sincronizaci�n Habilitada -->
            <div class="col-lg-3 col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3 class="card-title mb-1" id="syncEnabledCount">{{ $syncEnabledCount ?? 0 }}</h3>
                                <p class="card-text">Sync Habilitado</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-sync fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- �ltima Sincronizaci�n -->
            <div class="col-lg-3 col-md-6">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title mb-1">{{ $lastSyncTime ?? 'Nunca' }}</h6>
                                <p class="card-text">�ltima Sincronizaci�n</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Errores de Sync -->
            <div class="col-lg-3 col-md-6">
                <div class="card bg-{{ ($syncErrors ?? 0) > 0 ? 'danger' : 'secondary' }} text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3 class="card-title mb-1" id="syncErrors">{{ $syncErrors ?? 0 }}</h3>
                                <p class="card-text">Errores de Sync</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones R�pidas -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt me-2"></i>
                    Acciones R�pidas
                </h3>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a href="{{ route('admin.database-sync.connections') }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-database me-2 text-primary"></i>
                                <strong>Gestionar Conexiones</strong>
                                <br>
                                <small class="text-muted">Crear, editar y configurar conexiones de base de datos</small>
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>

                    <button class="list-group-item list-group-item-action" onclick="testAllConnections()">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-plug me-2 text-info"></i>
                                <strong>Probar Todas las Conexiones</strong>
                                <br>
                                <small class="text-muted">Verificar el estado de todas las conexiones configuradas</small>
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </button>

                    <button class="list-group-item list-group-item-action" onclick="syncAllTables()">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-sync me-2 text-success"></i>
                                <strong>Sincronizar Todas las Tablas</strong>
                                <br>
                                <small class="text-muted">Ejecutar sincronizaci�n en todas las conexiones habilitadas</small>
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </button>

                    <a href="{{ route('admin.database-sync.logs') }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file-alt me-2 text-warning"></i>
                                <strong>Ver Logs de Sincronizaci�n</strong>
                                <br>
                                <small class="text-muted">Revisar registros de actividad y errores</small>
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Estado de Conexiones -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-heartbeat me-2"></i>
                    Estado de Conexiones
                </h3>
                <div class="card-tools">
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshConnectionStatus()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="connectionStatusList">
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                        <p>Cargando estado de conexiones...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actividad Reciente -->
    <div class="col-12 mt-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history me-2"></i>
                    Actividad Reciente de Sincronizaci�n
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Conexi�n</th>
                                <th>Acci�n</th>
                                <th>Estado</th>
                                <th>Detalles</th>
                            </tr>
                        </thead>
                        <tbody id="recentActivity">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    <i class="fas fa-clock me-2"></i>
                                    No hay actividad reciente
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Resultados de Prueba -->
<div class="modal fade" id="testResultsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plug me-2"></i>
                    Resultados de Prueba de Conexiones
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="testResults">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                        <p>Probando conexiones...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Sincronizaci�n Masiva -->
<div class="modal fade" id="syncAllModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-sync me-2"></i>
                    Sincronizaci�n Masiva de Tablas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Advertencia:</strong> Esta acci�n sincronizar� todas las tablas en todas las conexiones habilitadas. 
                    Este proceso puede tomar varios minutos.
                </div>
                
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="forceSyncAll">
                    <label class="form-check-label" for="forceSyncAll">
                        Forzar sincronizaci�n (recrear tablas existentes)
                    </label>
                </div>
                
                <div id="syncAllProgress" style="display: none;">
                    <div class="progress mb-3">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%"></div>
                    </div>
                    <div id="syncAllLog" class="border rounded p-3 bg-light" style="max-height: 300px; overflow-y: auto;">
                        <pre id="syncAllLogText"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-success" id="startSyncAllBtn">
                    <i class="fas fa-sync me-1"></i>
                    Iniciar Sincronizaci�n
                </button>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
    <style>
        .card {
            transition: transform 0.2s ease-in-out;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .list-group-item-action:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
            transition: transform 0.2s ease-in-out;
        }
        
        .connection-status-item {
            border-left: 4px solid #dee2e6;
            padding-left: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .connection-status-item.online {
            border-left-color: #28a745;
        }
        
        .connection-status-item.offline {
            border-left-color: #dc3545;
        }
        
        .connection-status-item.warning {
            border-left-color: #ffc107;
        }
        
        .progress-bar-animated {
            animation: progress-bar-stripes 1s linear infinite;
        }
        
        @keyframes progress-bar-stripes {
            0% { background-position: 1rem 0; }
            100% { background-position: 0 0; }
        }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            loadConnectionStatus();
            loadRecentActivity();
            
            // Actualizar cada 30 segundos
            setInterval(function() {
                loadConnectionStatus();
                loadRecentActivity();
            }, 30000);
        });
        
        function testAllConnections() {
            $('#testResultsModal').modal('show');
            
            $.ajax({
                url: '/admin/database-sync/api/test-all-connections',
                method: 'GET',
                success: function(response) {
                    displayTestResults(response);
                },
                error: function() {
                    $('#testResults').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error al probar las conexiones
                        </div>
                    `);
                }
            });
        }
        
        function displayTestResults(results) {
            let html = '<div class="list-group list-group-flush">';
            
            results.forEach(function(result) {
                const statusClass = result.success ? 'success' : 'danger';
                const icon = result.success ? 'check-circle' : 'times-circle';
                
                html += `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">
                                    <i class="fas fa-${icon} text-${statusClass} me-2"></i>
                                    ${result.name}
                                </h6>
                                <p class="mb-1">${result.message}</p>
                                <small class="text-muted">${result.host}:${result.port}</small>
                            </div>
                            <span class="badge bg-${statusClass}">${result.success ? 'Conectado' : 'Error'}</span>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            $('#testResults').html(html);
        }
        
        function syncAllTables() {
            $('#syncAllModal').modal('show');
        }
        
        function loadConnectionStatus() {
            $.ajax({
                url: '/admin/database-sync/api/connection-status',
                method: 'GET',
                success: function(response) {
                    displayConnectionStatus(response);
                },
                error: function() {
                    $('#connectionStatusList').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error al cargar el estado de conexiones
                        </div>
                    `);
                }
            });
        }
        
        function displayConnectionStatus(connections) {
            if (connections.length === 0) {
                $('#connectionStatusList').html(`
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-database fa-2x mb-3"></i>
                        <p>No hay conexiones configuradas</p>
                    </div>
                `);
                return;
            }
            
            let html = '';
            connections.forEach(function(connection) {
                const statusClass = connection.status === 'connected' ? 'online' : 'offline';
                const statusIcon = connection.status === 'connected' ? 'check-circle' : 'times-circle';
                const statusColor = connection.status === 'connected' ? 'success' : 'danger';
                
                html += `
                    <div class="connection-status-item ${statusClass}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${connection.name}</strong>
                                <br>
                                <small class="text-muted">${connection.host}:${connection.port}</small>
                            </div>
                            <div class="text-end">
                                <i class="fas fa-${statusIcon} text-${statusColor}"></i>
                                ${connection.latency ? `<br><small>${connection.latency}</small>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            $('#connectionStatusList').html(html);
        }
        
        function loadRecentActivity() {
            // Simulaci�n de actividad reciente - en una implementaci�n real esto vendr�a del servidor
            const activities = [
                {
                    timestamp: '2025-08-17 19:15:00',
                    connection: 'Sucursal Lima',
                    action: 'Sincronizaci�n de tablas',
                    status: 'success',
                    details: '15 tablas sincronizadas'
                },
                {
                    timestamp: '2025-08-17 19:10:00',
                    connection: 'Sucursal Arequipa',
                    action: 'Prueba de conexi�n',
                    status: 'success',
                    details: 'Latencia: 45ms'
                },
                {
                    timestamp: '2025-08-17 19:05:00',
                    connection: 'Backup Server',
                    action: 'Sincronizaci�n autom�tica',
                    status: 'error',
                    details: 'Error de timeout'
                }
            ];
            
            if (activities.length === 0) {
                return;
            }
            
            let html = '';
            activities.forEach(function(activity) {
                const statusClass = activity.status === 'success' ? 'success' : 'danger';
                const statusIcon = activity.status === 'success' ? 'check' : 'times';
                
                html += `
                    <tr>
                        <td><small>${activity.timestamp}</small></td>
                        <td>${activity.connection}</td>
                        <td>${activity.action}</td>
                        <td>
                            <span class="badge bg-${statusClass}">
                                <i class="fas fa-${statusIcon} me-1"></i>
                                ${activity.status === 'success' ? '�xito' : 'Error'}
                            </span>
                        </td>
                        <td><small>${activity.details}</small></td>
                    </tr>
                `;
            });
            
            $('#recentActivity').html(html);
        }
        
        function refreshConnectionStatus() {
            loadConnectionStatus();
            showToast('info', 'Estado de conexiones actualizado');
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
        
        // Manejo del modal de sincronizaci�n masiva
        $('#startSyncAllBtn').click(function() {
            const force = $('#forceSyncAll').is(':checked');
            startMassiveSync(force);
        });
        
        function startMassiveSync(force) {
            $('#syncAllProgress').show();
            $('#startSyncAllBtn').prop('disabled', true);
            
            // Simulaci�n del proceso - en implementaci�n real esto ser�a una llamada AJAX
            let progress = 0;
            const interval = setInterval(function() {
                progress += Math.random() * 20;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                    $('#startSyncAllBtn').prop('disabled', false).html('<i class="fas fa-check me-1"></i>Completado');
                }
                
                $('.progress-bar').css('width', progress + '%');
                $('#syncAllLogText').append(`Procesando... ${Math.round(progress)}% completado\n`);
                $('#syncAllLog').scrollTop($('#syncAllLog')[0].scrollHeight);
            }, 1000);
        }
    </script>
@stop