@extends('layouts.admin')

@section('title', 'Dashboard - Sincronización de Bases de Datos')

@section('content_header')
    <h1>
        <i class="fas fa-database"></i> Sincronización de Bases de Datos
        <small class="ml-3">
            <span class="badge {{ $security_report['system_status'] === 'NORMAL' ? 'badge-success' : ($security_report['system_status'] === 'EMERGENCY' ? 'badge-danger' : 'badge-warning') }}">
                {{ $security_report['system_status'] }}
            </span>
        </small>
    </h1>
@stop

@section('content')

<!-- Alertas del Sistema -->
@if($security_report['emergency_mode'])
<div class="alert alert-danger">
    <h4><i class="icon fas fa-exclamation-triangle"></i> ¡MODO DE EMERGENCIA ACTIVO!</h4>
    El sistema está funcionando en modo de emergencia debido a alertas de seguridad.
    <button class="btn btn-sm btn-light ml-2" onclick="toggleEmergencyMode(false)">
        Desactivar Modo de Emergencia
    </button>
</div>
@endif

<!-- Métricas Principales -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $security_report['total_operations_today'] }}</h3>
                <p>Operaciones Hoy</p>
            </div>
            <div class="icon">
                <i class="fas fa-sync-alt"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ count($sync_status) }}</h3>
                <p>Conexiones Activas</p>
            </div>
            <div class="icon">
                <i class="fas fa-server"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box {{ $security_report['security_alerts_count'] > 0 ? 'bg-warning' : 'bg-success' }}">
            <div class="inner">
                <h3>{{ $security_report['security_alerts_count'] }}</h3>
                <p>Alertas de Seguridad</p>
            </div>
            <div class="icon">
                <i class="fas fa-shield-alt"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ $security_report['blocked_ips_count'] }}</h3>
                <p>IPs Bloqueadas</p>
            </div>
            <div class="icon">
                <i class="fas fa-ban"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Estado de Conexiones -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-network-wired"></i> Estado de Conexiones
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" onclick="refreshConnectionStatus()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="connection-status">
                    @foreach($connection_status as $connection => $status)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <strong>{{ $connection }}</strong>
                            @if($status['status'] === 'connected')
                                <span class="badge badge-success">Conectado</span>
                                <small class="text-muted">({{ $status['latency'] }})</small>
                            @else
                                <span class="badge badge-danger">Desconectado</span>
                            @endif
                        </div>
                        <div>
                            @if($status['status'] === 'connected')
                                <i class="fas fa-circle text-success"></i>
                            @else
                                <i class="fas fa-circle text-danger"></i>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Métricas del Sistema -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line"></i> Métricas del Sistema
                </h3>
            </div>
            <div class="card-body">
                <div class="progress-group">
                    Uso de Memoria
                    <span class="float-right"><b>{{ $system_metrics['memory_usage'] }}</b>/{{ $system_metrics['memory_peak'] }} MB</span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-primary" style="width: {{ min(($system_metrics['memory_usage'] / 512) * 100, 100) }}%"></div>
                    </div>
                </div>

                <div class="progress-group">
                    Espacio en Disco
                    <span class="float-right"><b>{{ $system_metrics['disk_free'] }}</b> GB libres</span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-success" style="width: 75%"></div>
                    </div>
                </div>

                @if($system_metrics['cpu_load'])
                <div class="progress-group">
                    Carga del CPU
                    <span class="float-right"><b>{{ round($system_metrics['cpu_load'], 2) }}</b></span>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-info" style="width: {{ min($system_metrics['cpu_load'] * 25, 100) }}%"></div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Alertas Recientes y Gráfico de Actividad -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-area"></i> Actividad de Sincronización (Últimas 24 horas)
                </h3>
            </div>
            <div class="card-body">
                <canvas id="syncActivityChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-triangle"></i> Alertas Recientes
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.database-sync.logs') }}" class="btn btn-tool">
                        <i class="fas fa-eye"></i> Ver Todos
                    </a>
                </div>
            </div>
            <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                @forelse($recent_alerts as $alert)
                <div class="d-flex px-3 py-2 border-bottom">
                    <div class="mr-3">
                        @if($alert['level'] === 'CRITICAL')
                            <i class="fas fa-exclamation-circle text-danger"></i>
                        @elseif($alert['level'] === 'ERROR')
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                        @else
                            <i class="fas fa-info-circle text-info"></i>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <small class="text-muted">{{ $alert['timestamp'] }}</small>
                        <div class="text-sm">{{ Str::limit($alert['message'], 80) }}</div>
                    </div>
                </div>
                @empty
                <div class="text-center py-3 text-muted">
                    <i class="fas fa-check-circle text-success"></i><br>
                    No hay alertas recientes
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Acciones Rápidas -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tools"></i> Acciones Rápidas
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <button class="btn btn-primary btn-block" onclick="createBackup()">
                            <i class="fas fa-download"></i> Crear Respaldo
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-info btn-block" onclick="verifyIntegrity()">
                            <i class="fas fa-check-circle"></i> Verificar Integridad
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('admin.database-sync.monitoring') }}" class="btn btn-success btn-block">
                            <i class="fas fa-eye"></i> Monitoreo en Vivo
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('admin.database-sync.configuration') }}" class="btn btn-warning btn-block">
                            <i class="fas fa-cog"></i> Configuración
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@stop

@section('css')
<style>
.small-box .icon {
    top: 10px;
}
.progress-group {
    margin-bottom: 15px;
}
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Variables globales
let syncChart;

// Inicializar dashboard
$(document).ready(function() {
    initSyncChart();
    startRealTimeUpdates();
});

// Gráfico de actividad de sincronización
function initSyncChart() {
    const ctx = document.getElementById('syncActivityChart').getContext('2d');
    
    syncChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Operaciones de Sincronización',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Cargar datos iniciales
    updateSyncChart();
}

// Actualizar gráfico con datos en tiempo real
function updateSyncChart() {
    // Simular datos de las últimas 24 horas
    const now = new Date();
    const labels = [];
    const data = [];
    
    for (let i = 23; i >= 0; i--) {
        const time = new Date(now.getTime() - (i * 60 * 60 * 1000));
        labels.push(time.getHours() + ':00');
        data.push(Math.floor(Math.random() * 100)); // Datos simulados
    }
    
    syncChart.data.labels = labels;
    syncChart.data.datasets[0].data = data;
    syncChart.update();
}

// Actualizaciones en tiempo real
function startRealTimeUpdates() {
    setInterval(() => {
        updateMetrics();
        refreshConnectionStatus();
    }, 30000); // Cada 30 segundos
}

// Actualizar métricas
function updateMetrics() {
    fetch('{{ route("admin.database-sync.api.metrics") }}')
        .then(response => response.json())
        .then(data => {
            // Actualizar contadores en tiempo real
            console.log('Métricas actualizadas:', data);
        })
        .catch(error => console.error('Error actualizando métricas:', error));
}

// Refrescar estado de conexiones
function refreshConnectionStatus() {
    // Simular actualización del estado
    $('#connection-status .fas.fa-circle').removeClass('text-success text-danger').addClass('text-warning');
    
    setTimeout(() => {
        $('#connection-status .fas.fa-circle').removeClass('text-warning').addClass('text-success');
    }, 1000);
}

// Crear respaldo
function createBackup() {
    if (confirm('¿Crear un respaldo completo del sistema?')) {
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
        btn.disabled = true;
        
        fetch('{{ route("admin.database-sync.create-backup") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                encrypt: true,
                compress: true
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
            } else {
                toastr.error(data.message);
            }
        })
        .catch(error => {
            toastr.error('Error creando respaldo');
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
}

// Verificar integridad
function verifyIntegrity() {
    if (confirm('¿Verificar la integridad de los datos sincronizados?')) {
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
        btn.disabled = true;
        
        fetch('{{ route("admin.database-sync.verify-integrity") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                fix: false
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
            } else {
                toastr.error(data.message);
            }
        })
        .catch(error => {
            toastr.error('Error verificando integridad');
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
}

// Toggle modo de emergencia
function toggleEmergencyMode(enable) {
    const action = enable ? 'activar' : 'desactivar';
    
    if (confirm(`¿Está seguro que desea ${action} el modo de emergencia?`)) {
        fetch('{{ route("admin.database-sync.toggle-emergency") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                enable: enable
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                toastr.error('Error cambiando modo de emergencia');
            }
        })
        .catch(error => {
            toastr.error('Error en la operación');
        });
    }
}
</script>
@stop