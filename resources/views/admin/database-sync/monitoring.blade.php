@extends('layouts.admin')

@section('title', 'Monitoreo en Tiempo Real - Sincronización DB')

@section('content_header')
    <h1>
        <i class="fas fa-eye"></i> Monitoreo en Tiempo Real
        <small class="ml-3">
            <span id="status-indicator" class="badge badge-success">
                <i class="fas fa-circle"></i> En Línea
            </span>
        </small>
    </h1>
@stop

@section('content')

<!-- Controles de Monitoreo -->
<div class="row mb-3">
    <div class="col-md-6">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-success" id="startMonitoring">
                <i class="fas fa-play"></i> Iniciar
            </button>
            <button type="button" class="btn btn-warning" id="pauseMonitoring">
                <i class="fas fa-pause"></i> Pausar
            </button>
            <button type="button" class="btn btn-info" id="refreshMonitoring">
                <i class="fas fa-sync-alt"></i> Actualizar
            </button>
        </div>
    </div>
    <div class="col-md-6 text-right">
        <span class="text-muted">
            Última actualización: <span id="lastUpdate">--:--:--</span>
        </span>
    </div>
</div>

<!-- Métricas en Tiempo Real -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="currentMinuteOps">0</h3>
                <p>Ops/Minuto Actual</p>
            </div>
            <div class="icon">
                <i class="fas fa-tachometer-alt"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3 id="currentHourOps">0</h3>
                <p>Ops/Hora Actual</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="securityAlerts">0</h3>
                <p>Alertas Activas</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3 id="blockedIPs">0</h3>
                <p>IPs Bloqueadas</p>
            </div>
            <div class="icon">
                <i class="fas fa-ban"></i>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos en Tiempo Real -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line"></i> Operaciones de Sincronización (Tiempo Real)
                </h3>
                <div class="card-tools">
                    <span class="badge badge-primary" id="chartStatus">Activo</span>
                </div>
            </div>
            <div class="card-body">
                <canvas id="realTimeChart" style="height: 400px;"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-server"></i> Estado de Conexiones
                </h3>
            </div>
            <div class="card-body p-0">
                <div id="connectionsList" style="max-height: 400px; overflow-y: auto;">
                    <!-- Conexiones se cargarán dinámicamente -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alertas y Log en Vivo -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bell"></i> Alertas en Tiempo Real
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" onclick="clearAlerts()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="realTimeAlerts" style="height: 300px; overflow-y: auto;">
                    <div class="text-center p-4 text-muted">
                        <i class="fas fa-bell-slash fa-2x"></i><br>
                        No hay alertas recientes
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i> Actividad Reciente
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" onclick="clearActivity()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="recentActivity" style="height: 300px; overflow-y: auto;">
                    <div class="text-center p-4 text-muted">
                        <i class="fas fa-clock fa-2x"></i><br>
                        Esperando actividad...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalles de Conexión -->
<div class="modal fade" id="connectionDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detalles de Conexión</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="connectionDetails">
                    <!-- Contenido dinámico -->
                </div>
            </div>
        </div>
    </div>
</div>

@stop

@section('css')
<style>
.connection-item {
    border-bottom: 1px solid #dee2e6;
    padding: 15px;
}

.connection-item:last-child {
    border-bottom: none;
}

.status-connected {
    color: #28a745;
}

.status-disconnected {
    color: #dc3545;
}

.status-warning {
    color: #ffc107;
}

.alert-item {
    border-bottom: 1px solid #dee2e6;
    padding: 10px 15px;
    font-size: 0.9em;
}

.alert-item:last-child {
    border-bottom: none;
}

.alert-critical {
    background-color: #f8d7da;
}

.alert-warning {
    background-color: #fff3cd;
}

.alert-info {
    background-color: #d1ecf1;
}

#realTimeChart {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.8; }
    100% { opacity: 1; }
}

.monitoring-paused #realTimeChart {
    animation: none;
    opacity: 0.6;
}
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Variables globales
let monitoringActive = false;
let monitoringInterval;
let realTimeChart;
let maxDataPoints = 30;
let connectionStatuses = {};

// Inicializar monitoreo
$(document).ready(function() {
    initRealTimeChart();
    setupEventHandlers();
    startMonitoring();
});

// Configurar manejadores de eventos
function setupEventHandlers() {
    $('#startMonitoring').click(() => startMonitoring());
    $('#pauseMonitoring').click(() => pauseMonitoring());
    $('#refreshMonitoring').click(() => refreshData());
    
    // Auto-scroll para alertas y actividad
    $('#realTimeAlerts, #recentActivity').on('DOMNodeInserted', function() {
        $(this).scrollTop($(this)[0].scrollHeight);
    });
}

// Inicializar gráfico en tiempo real
function initRealTimeChart() {
    const ctx = document.getElementById('realTimeChart').getContext('2d');
    
    realTimeChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Operaciones/Min',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1,
                fill: true
            }, {
                label: 'Alertas/Min',
                data: [],
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.1,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 500
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Tiempo'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Cantidad'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true
                }
            }
        }
    });
}

// Iniciar monitoreo
function startMonitoring() {
    if (monitoringActive) return;
    
    monitoringActive = true;
    updateStatusIndicator('En Línea', 'success');
    $('#chartStatus').text('Activo').removeClass('badge-secondary').addClass('badge-primary');
    $('body').removeClass('monitoring-paused');
    
    // Actualizar cada 5 segundos
    monitoringInterval = setInterval(refreshData, 5000);
    
    // Primera actualización inmediata
    refreshData();
    
    toastr.success('Monitoreo iniciado');
}

// Pausar monitoreo
function pauseMonitoring() {
    if (!monitoringActive) return;
    
    monitoringActive = false;
    clearInterval(monitoringInterval);
    
    updateStatusIndicator('Pausado', 'warning');
    $('#chartStatus').text('Pausado').removeClass('badge-primary').addClass('badge-secondary');
    $('body').addClass('monitoring-paused');
    
    toastr.warning('Monitoreo pausado');
}

// Actualizar datos
function refreshData() {
    fetch('{{ route("admin.database-sync.api.metrics") }}')
        .then(response => response.json())
        .then(data => {
            updateMetrics(data);
            updateChart(data);
            updateConnections(data.connections);
            updateLastUpdateTime();
            
            // Simular nuevas alertas y actividad
            if (Math.random() > 0.7) {
                addRandomAlert();
            }
            if (Math.random() > 0.6) {
                addRandomActivity();
            }
        })
        .catch(error => {
            console.error('Error actualizando datos:', error);
            updateStatusIndicator('Error', 'danger');
            
            addAlert('ERROR', 'Error conectando con el servidor de métricas');
        });
}

// Actualizar métricas
function updateMetrics(data) {
    $('#currentMinuteOps').text(data.sync_operations.current_minute);
    $('#currentHourOps').text(data.sync_operations.current_hour);
    $('#securityAlerts').text(data.security.alerts_count);
    $('#blockedIPs').text(data.security.blocked_ips_count);
    
    // Actualizar colores según estado
    updateMetricBoxColor('#securityAlerts', data.security.alerts_count);
    updateMetricBoxColor('#blockedIPs', data.security.blocked_ips_count);
    
    // Verificar modo de emergencia
    if (data.security.emergency_mode) {
        updateStatusIndicator('EMERGENCIA', 'danger');
        addAlert('CRITICAL', 'Sistema en modo de emergencia');
    }
}

// Actualizar gráfico
function updateChart(data) {
    const now = new Date();
    const timeLabel = now.toLocaleTimeString();
    
    // Agregar nuevos datos
    realTimeChart.data.labels.push(timeLabel);
    realTimeChart.data.datasets[0].data.push(data.sync_operations.current_minute);
    realTimeChart.data.datasets[1].data.push(data.security.alerts_count);
    
    // Mantener solo los últimos puntos
    if (realTimeChart.data.labels.length > maxDataPoints) {
        realTimeChart.data.labels.shift();
        realTimeChart.data.datasets[0].data.shift();
        realTimeChart.data.datasets[1].data.shift();
    }
    
    realTimeChart.update('none');
}

// Actualizar conexiones
function updateConnections(connections) {
    const container = $('#connectionsList');
    container.empty();
    
    Object.entries(connections).forEach(([name, status]) => {
        const statusClass = status.status === 'connected' ? 'status-connected' : 'status-disconnected';
        const iconClass = status.status === 'connected' ? 'fas fa-check-circle' : 'fas fa-times-circle';
        
        const connectionHtml = `
            <div class="connection-item" onclick="showConnectionDetails('${name}', ${JSON.stringify(status).replace(/"/g, '&quot;')})">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${name}</strong>
                        <br>
                        <small class="${statusClass}">
                            <i class="${iconClass}"></i> ${status.status}
                            ${status.latency ? `(${status.latency})` : ''}
                        </small>
                    </div>
                    <div>
                        <i class="fas fa-info-circle text-muted"></i>
                    </div>
                </div>
            </div>
        `;
        
        container.append(connectionHtml);
        
        // Detectar cambios de estado
        if (connectionStatuses[name] && connectionStatuses[name] !== status.status) {
            const message = status.status === 'connected' 
                ? `Conexión ${name} restaurada` 
                : `Conexión ${name} perdida`;
            
            addActivity(message);
            
            if (status.status === 'disconnected') {
                addAlert('ERROR', `Conexión ${name} desconectada`);
            }
        }
        
        connectionStatuses[name] = status.status;
    });
}

// Mostrar detalles de conexión
function showConnectionDetails(name, status) {
    const details = `
        <table class="table table-bordered">
            <tr>
                <th>Conexión</th>
                <td>${name}</td>
            </tr>
            <tr>
                <th>Estado</th>
                <td>
                    <span class="${status.status === 'connected' ? 'text-success' : 'text-danger'}">
                        ${status.status}
                    </span>
                </td>
            </tr>
            ${status.latency ? `
                <tr>
                    <th>Latencia</th>
                    <td>${status.latency}</td>
                </tr>
            ` : ''}
            ${status.error ? `
                <tr>
                    <th>Error</th>
                    <td class="text-danger">${status.error}</td>
                </tr>
            ` : ''}
            <tr>
                <th>Última verificación</th>
                <td>${new Date().toLocaleString()}</td>
            </tr>
        </table>
    `;
    
    $('#connectionDetails').html(details);
    $('#connectionDetailsModal').modal('show');
}

// Agregar alerta
function addAlert(level, message) {
    const timestamp = new Date().toLocaleTimeString();
    const alertClass = level === 'CRITICAL' ? 'alert-critical' : 
                     level === 'ERROR' ? 'alert-warning' : 'alert-info';
    
    const iconClass = level === 'CRITICAL' ? 'fas fa-exclamation-triangle' :
                     level === 'ERROR' ? 'fas fa-exclamation-circle' : 'fas fa-info-circle';
    
    const alertHtml = `
        <div class="alert-item ${alertClass}">
            <div class="d-flex">
                <div class="mr-2">
                    <i class="${iconClass}"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <strong>${level}</strong>
                        <small>${timestamp}</small>
                    </div>
                    <div class="mt-1">${message}</div>
                </div>
            </div>
        </div>
    `;
    
    const container = $('#realTimeAlerts');
    
    // Remover mensaje de "no hay alertas" si existe
    if (container.find('.text-muted').length) {
        container.empty();
    }
    
    container.append(alertHtml);
    
    // Mantener solo las últimas 50 alertas
    const alerts = container.find('.alert-item');
    if (alerts.length > 50) {
        alerts.first().remove();
    }
    
    // Auto scroll
    container.scrollTop(container[0].scrollHeight);
    
    // Notificación toast para alertas críticas
    if (level === 'CRITICAL') {
        toastr.error(message, 'Alerta Crítica');
    }
}

// Agregar actividad
function addActivity(message) {
    const timestamp = new Date().toLocaleTimeString();
    
    const activityHtml = `
        <div class="alert-item">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-circle text-primary mr-2" style="font-size: 0.5em;"></i>
                    ${message}
                </div>
                <small class="text-muted">${timestamp}</small>
            </div>
        </div>
    `;
    
    const container = $('#recentActivity');
    
    // Remover mensaje de "esperando actividad" si existe
    if (container.find('.text-muted').length) {
        container.empty();
    }
    
    container.append(activityHtml);
    
    // Mantener solo las últimas 50 actividades
    const activities = container.find('.alert-item');
    if (activities.length > 50) {
        activities.first().remove();
    }
    
    // Auto scroll
    container.scrollTop(container[0].scrollHeight);
}

// Funciones de utilidad
function updateStatusIndicator(text, type) {
    const indicator = $('#status-indicator');
    indicator.removeClass('badge-success badge-warning badge-danger')
            .addClass(`badge-${type}`)
            .html(`<i class="fas fa-circle"></i> ${text}`);
}

function updateLastUpdateTime() {
    $('#lastUpdate').text(new Date().toLocaleTimeString());
}

function updateMetricBoxColor(selector, value) {
    const box = $(selector).closest('.small-box');
    box.removeClass('bg-success bg-warning bg-danger');
    
    if (value == 0) {
        box.addClass('bg-success');
    } else if (value < 10) {
        box.addClass('bg-warning');
    } else {
        box.addClass('bg-danger');
    }
}

function clearAlerts() {
    $('#realTimeAlerts').html(`
        <div class="text-center p-4 text-muted">
            <i class="fas fa-bell-slash fa-2x"></i><br>
            No hay alertas recientes
        </div>
    `);
}

function clearActivity() {
    $('#recentActivity').html(`
        <div class="text-center p-4 text-muted">
            <i class="fas fa-clock fa-2x"></i><br>
            Esperando actividad...
        </div>
    `);
}

// Simular alertas aleatorias para demo
function addRandomAlert() {
    const alerts = [
        { level: 'INFO', message: 'Sincronización completada exitosamente' },
        { level: 'ERROR', message: 'Error temporal en conexión secundaria' },
        { level: 'CRITICAL', message: 'Intento de acceso no autorizado detectado' },
        { level: 'INFO', message: 'Respaldo automático creado' },
        { level: 'ERROR', message: 'Verificación de integridad falló' }
    ];
    
    const randomAlert = alerts[Math.floor(Math.random() * alerts.length)];
    addAlert(randomAlert.level, randomAlert.message);
}

// Simular actividad aleatoria para demo
function addRandomActivity() {
    const activities = [
        'Usuario admin inició sesión',
        'Tabla clientes sincronizada',
        'Verificación de integridad ejecutada',
        'Respaldo creado manualmente',
        'IP 192.168.1.100 bloqueada',
        'Tabla prestamos actualizada',
        'Conexión analytics restaurada'
    ];
    
    const randomActivity = activities[Math.floor(Math.random() * activities.length)];
    addActivity(randomActivity);
}

// Limpiar al salir de la página
$(window).on('beforeunload', function() {
    if (monitoringInterval) {
        clearInterval(monitoringInterval);
    }
});
</script>
@stop