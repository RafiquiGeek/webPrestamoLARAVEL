@extends('layouts.admin')
@section('title', 'Monitoreo del Sistema')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-server mr-2"></i>Monitoreo del Sistema</h1>
       <div class="d-flex align-items-center gap-2">
           <div class="badge bg-{{ $estado_general['color'] }} p-2">
               <i class="fas fa-circle me-1"></i>
               {{ $estado_general['mensaje'] }}
           </div>
           <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleAutoRefresh()">
               <i class="fas fa-sync me-1"></i>Auto-refresh
               <span id="badge-refresh" class="badge bg-success ms-1" style="display: none;">ON</span>
           </button>
           <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalConfiguracion">
               <i class="fas fa-cog me-1"></i>Configurar
           </button>
           <ol class="breadcrumb float-sm-right mb-0">
               <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
               <li class="breadcrumb-item active">Monitoreo</li>
           </ol>
       </div>
   </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Alertas Activas -->
    @if(count($alertas) > 0)
    <div class="alert alert-{{ collect($alertas)->contains('nivel', 'critical') ? 'danger' : 'warning' }} alert-dismissible" id="alertas-container">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <h5><i class="fas fa-exclamation-triangle me-2"></i>Alertas del Sistema</h5>
        <div class="row">
            @foreach($alertas as $alerta)
                <div class="col-md-6 mb-2">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-{{ $alerta['nivel'] === 'critical' ? 'exclamation-circle text-danger' : 'exclamation-triangle text-warning' }} me-2"></i>
                        <span>{{ $alerta['mensaje'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Dashboard Principal de Métricas -->
    <div class="row mb-4" id="dashboard-metricas">
        <!-- CPU -->
        <div class="col-lg-3 col-6">
            <div class="metric-card" data-metric="cpu">
                <div class="metric-header">
                    <i class="fas fa-microchip text-primary"></i>
                    <span>CPU</span>
                </div>
                <div class="metric-value">
                    <span class="value" id="cpu-value">{{ number_format($metricas['cpu'] ?? 0, 1) }}%</span>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-primary" role="progressbar" 
                             style="width: {{ $metricas['cpu'] ?? 0 }}%"
                             id="cpu-progress"></div>
                    </div>
                </div>
                <small class="metric-info">{{ $umbrales['cpu'] }}% umbral</small>
            </div>
        </div>

        <!-- Memoria -->
        <div class="col-lg-3 col-6">
            <div class="metric-card" data-metric="memoria">
                <div class="metric-header">
                    <i class="fas fa-memory text-info"></i>
                    <span>Memoria</span>
                </div>
                <div class="metric-value">
                    <span class="value" id="memoria-value">{{ number_format($metricas['memoria']['porcentaje'] ?? 0, 1) }}%</span>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-info" role="progressbar" 
                             style="width: {{ $metricas['memoria']['porcentaje'] ?? 0 }}%"
                             id="memoria-progress"></div>
                    </div>
                </div>
                <small class="metric-info">
                    {{ number_format($metricas['memoria']['usado_mb'] ?? 0, 0) }}MB / 
                    {{ number_format($metricas['memoria']['total_mb'] ?? 0, 0) }}MB
                </small>
            </div>
        </div>

        <!-- Disco -->
        <div class="col-lg-3 col-6">
            <div class="metric-card" data-metric="disco">
                <div class="metric-header">
                    <i class="fas fa-hdd text-warning"></i>
                    <span>Disco</span>
                </div>
                <div class="metric-value">
                    <span class="value" id="disco-value">{{ number_format($metricas['disco']['porcentaje'] ?? 0, 1) }}%</span>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-warning" role="progressbar" 
                             style="width: {{ $metricas['disco']['porcentaje'] ?? 0 }}%"
                             id="disco-progress"></div>
                    </div>
                </div>
                <small class="metric-info">
                    {{ number_format($metricas['disco']['usado_gb'] ?? 0, 1) }}GB / 
                    {{ number_format($metricas['disco']['total_gb'] ?? 0, 1) }}GB
                </small>
            </div>
        </div>

        <!-- Base de Datos -->
        <div class="col-lg-3 col-6">
            <div class="metric-card" data-metric="database">
                <div class="metric-header">
                    <i class="fas fa-database text-success"></i>
                    <span>Base de Datos</span>
                </div>
                <div class="metric-value">
                    <span class="value" id="db-tiempo">{{ number_format($metricas['db_tiempo_respuesta'] ?? 0, 1) }}ms</span>
                    <div class="mt-2">
                        <span class="badge bg-light text-dark">
                            {{ $metricas['db_conexiones']['activas'] ?? 0 }} conexiones
                        </span>
                    </div>
                </div>
                <small class="metric-info">{{ number_format($metricas['db_tamaño'] ?? 0, 1) }}MB tamaño</small>
            </div>
        </div>
    </div>

    <!-- Gráficos de Tendencias -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="account-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line me-2"></i>Tendencias del Sistema</h3>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-sm active" onclick="cambiarPeriodo('1h')">1h</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="cambiarPeriodo('6h')">6h</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="cambiarPeriodo('24h')">24h</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="cambiarPeriodo('7d')">7d</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="chartTendencias" height="150"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="account-card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle me-2"></i>Información del Sistema</h3>
                </div>
                <div class="card-body">
                    <div class="system-info">
                        <div class="info-item">
                            <span class="label">Servidor Web:</span>
                            <span class="value">{{ $metricas['servidor_web']['tipo'] ?? 'N/A' }}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">PHP Memoria:</span>
                            <span class="value">{{ $metricas['php_memoria']['actual_mb'] ?? 0 }}MB</span>
                        </div>
                        <div class="info-item">
                            <span class="label">PHP Procesos:</span>
                            <span class="value">{{ $metricas['php_procesos'] ?? 0 }}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Sessions Activas:</span>
                            <span class="value">{{ $metricas['sesiones_activas'] ?? 0 }}</span>
                        </div>
                        <div class="info-item">
                            <span class="label">Errores (1h):</span>
                            <span class="value badge bg-{{ ($metricas['errores_recientes'] ?? 0) > 0 ? 'danger' : 'success' }}">
                                {{ $metricas['errores_recientes'] ?? 0 }}
                            </span>
                        </div>
                        @if(isset($metricas['carga_sistema']['1min']))
                        <div class="info-item">
                            <span class="label">Load Average:</span>
                            <span class="value">{{ $metricas['carga_sistema']['1min'] }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Métricas Detalladas -->
    <div class="row">
        <div class="col-lg-6">
            <div class="account-card">
                <div class="card-header">
                    <h3><i class="fas fa-tachometer-alt me-2"></i>Rendimiento</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="metric-detail">
                                <div class="metric-detail-label">Tiempo Resp. HTTP</div>
                                <div class="metric-detail-value">
                                    <span id="http-tiempo">{{ $metricas['tiempo_respuesta_http'] ?? 'N/A' }}</span>
                                    @if(isset($metricas['tiempo_respuesta_http']) && $metricas['tiempo_respuesta_http'] > 0)
                                        <small>ms</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="metric-detail">
                                <div class="metric-detail-label">Tiempo Resp. DB</div>
                                <div class="metric-detail-value">
                                    <span id="db-tiempo-detalle">{{ number_format($metricas['db_tiempo_respuesta'] ?? 0, 2) }}</span>
                                    <small>ms</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="account-card">
                <div class="card-header">
                    <h3><i class="fas fa-exclamation-triangle me-2"></i>Estado de Alertas</h3>
                </div>
                <div class="card-body">
                    <div class="alert-summary" id="resumen-alertas">
                        @if(count($alertas) === 0)
                            <div class="text-center text-success">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <p class="mb-0">Sin alertas activas</p>
                                <small class="text-muted">Todos los sistemas funcionan correctamente</small>
                            </div>
                        @else
                            <div class="alert-count">
                                <span class="badge bg-danger">{{ collect($alertas)->where('nivel', 'critical')->count() }}</span>
                                Críticas
                            </div>
                            <div class="alert-count">
                                <span class="badge bg-warning">{{ collect($alertas)->where('nivel', 'warning')->count() }}</span>
                                Advertencias
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Última actualización -->
    <div class="row mt-3">
        <div class="col-12">
            <small class="text-muted">
                <i class="fas fa-clock me-1"></i>
                Última actualización: <span id="ultima-actualizacion">{{ now()->format('d/m/Y H:i:s') }}</span>
                <span id="contador-refresh" class="ms-3" style="display: none;"></span>
            </small>
        </div>
    </div>
</div>

<!-- Modal de Configuración -->
<div class="modal fade" id="modalConfiguracion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cog text-primary me-2"></i>
                    Configuración de Umbrales
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formConfiguracion">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">CPU (%)</label>
                        <input type="number" name="cpu" class="form-control" min="1" max="100" 
                               value="{{ $umbrales['cpu'] }}" required>
                        <small class="form-text text-muted">Umbral de alerta para uso de CPU</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Memoria (%)</label>
                        <input type="number" name="memoria" class="form-control" min="1" max="100" 
                               value="{{ $umbrales['memoria'] }}" required>
                        <small class="form-text text-muted">Umbral de alerta para uso de memoria</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Disco (%)</label>
                        <input type="number" name="disco" class="form-control" min="1" max="100" 
                               value="{{ $umbrales['disco'] }}" required>
                        <small class="form-text text-muted">Umbral de alerta para uso de disco</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Conexiones DB</label>
                        <input type="number" name="conexiones_db" class="form-control" min="1" max="1000" 
                               value="{{ $umbrales['conexiones_db'] }}" required>
                        <small class="form-text text-muted">Número máximo de conexiones concurrentes</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tiempo de Respuesta (segundos)</label>
                        <input type="number" name="tiempo_respuesta" class="form-control" min="0.1" max="60" step="0.1"
                               value="{{ $umbrales['tiempo_respuesta'] }}" required>
                        <small class="form-text text-muted">Tiempo máximo de respuesta aceptable</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Guardar Configuración
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('css')
<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
/* Estilos consistentes con el sistema */
.account-card {
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.account-card .card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.account-card .card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0;
}

.account-card .card-body {
    padding: 1.5rem;
}

/* Tarjetas de métricas */
.metric-card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    padding: 1.5rem;
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
    margin-bottom: 1.5rem;
    border-left: 4px solid transparent;
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.metric-card[data-metric="cpu"] {
    border-left-color: #007bff;
}

.metric-card[data-metric="memoria"] {
    border-left-color: #17a2b8;
}

.metric-card[data-metric="disco"] {
    border-left-color: #ffc107;
}

.metric-card[data-metric="database"] {
    border-left-color: #28a745;
}

.metric-header {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    font-weight: 600;
    color: #495057;
}

.metric-header i {
    font-size: 1.5rem;
    margin-right: 0.5rem;
}

.metric-value .value {
    font-size: 2rem;
    font-weight: 700;
    color: #1a1a1a;
    display: block;
}

.metric-info {
    color: #6c757d;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

/* Información del sistema */
.system-info .info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.system-info .info-item:last-child {
    border-bottom: none;
}

.system-info .label {
    font-weight: 600;
    color: #495057;
}

.system-info .value {
    color: #1a1a1a;
    font-weight: 500;
}

/* Métricas detalladas */
.metric-detail {
    text-align: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.metric-detail-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.metric-detail-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a1a;
}

.metric-detail-value small {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: normal;
}

/* Resumen de alertas */
.alert-summary {
    text-align: center;
}

.alert-count {
    display: inline-block;
    margin: 0 1rem;
    font-weight: 600;
}

.alert-count .badge {
    font-size: 1rem;
    padding: 0.5rem;
    margin-right: 0.5rem;
}

/* Progress bars personalizadas */
.progress {
    height: 8px;
    border-radius: 4px;
    background-color: #e9ecef;
}

.progress-bar {
    border-radius: 4px;
}

/* Animaciones */
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.refreshing {
    animation: pulse 1.5s infinite;
}

/* Estado crítico */
.metric-card.critical {
    border-left-color: #dc3545 !important;
    background: #fff5f5;
}

.metric-card.warning {
    border-left-color: #ffc107 !important;
    background: #fffbf0;
}

/* Responsive */
@media (max-width: 768px) {
    .metric-card {
        margin-bottom: 1rem;
    }
    
    .metric-value .value {
        font-size: 1.5rem;
    }
    
    .metric-header i {
        font-size: 1.25rem;
    }
}
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let autoRefreshInterval = null;
let chartTendencias = null;
let contadorRefresh = 30;
let contadorInterval = null;

$(document).ready(function() {
    // Inicializar gráfico
    inicializarGrafico();
    
    // Configurar formulario
    $('#formConfiguracion').on('submit', function(e) {
        e.preventDefault();
        guardarConfiguracion();
    });
    
    // Marcar métricas críticas
    marcarMetricasCriticas();
});

function inicializarGrafico() {
    const ctx = document.getElementById('chartTendencias').getContext('2d');
    const historico = @json($historico);
    
    const labels = Object.keys(historico).slice(-24); // Últimas 24 muestras
    const dataCPU = labels.map(timestamp => historico[timestamp].cpu || 0);
    const dataMemoria = labels.map(timestamp => historico[timestamp].memoria?.porcentaje || 0);
    const dataDisco = labels.map(timestamp => historico[timestamp].disco?.porcentaje || 0);
    
    chartTendencias = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.map(ts => new Date(ts).toLocaleTimeString()),
            datasets: [{
                label: 'CPU %',
                data: dataCPU,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4,
                fill: false
            }, {
                label: 'Memoria %',
                data: dataMemoria,
                borderColor: '#17a2b8',
                backgroundColor: 'rgba(23, 162, 184, 0.1)',
                tension: 0.4,
                fill: false
            }, {
                label: 'Disco %',
                data: dataDisco,
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                tension: 0.4,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + '%';
                        }
                    }
                }
            }
        }
    });
}

function actualizarMetricas() {
    $.ajax({
        url: '{{ route("admin.monitoreo.index") }}',
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            // Actualizar valores de métricas
            $('#cpu-value').text(parseFloat(response.metricas.cpu || 0).toFixed(1) + '%');
            $('#cpu-progress').css('width', (response.metricas.cpu || 0) + '%');
            
            if (response.metricas.memoria) {
                $('#memoria-value').text(parseFloat(response.metricas.memoria.porcentaje || 0).toFixed(1) + '%');
                $('#memoria-progress').css('width', (response.metricas.memoria.porcentaje || 0) + '%');
            }
            
            if (response.metricas.disco) {
                $('#disco-value').text(parseFloat(response.metricas.disco.porcentaje || 0).toFixed(1) + '%');
                $('#disco-progress').css('width', (response.metricas.disco.porcentaje || 0) + '%');
            }
            
            $('#db-tiempo').text(parseFloat(response.metricas.db_tiempo_respuesta || 0).toFixed(1) + 'ms');
            $('#db-tiempo-detalle').text(parseFloat(response.metricas.db_tiempo_respuesta || 0).toFixed(2));
            
            if (response.metricas.tiempo_respuesta_http && response.metricas.tiempo_respuesta_http > 0) {
                $('#http-tiempo').text(response.metricas.tiempo_respuesta_http);
            } else {
                $('#http-tiempo').text('N/A');
            }
            
            // Actualizar alertas
            actualizarAlertas(response.alertas);
            
            // Actualizar estado general
            actualizarEstadoGeneral(response.estado_general);
            
            // Actualizar timestamp
            $('#ultima-actualizacion').text(response.timestamp);
            
            // Marcar métricas críticas
            marcarMetricasCriticas();
            
            console.log('Métricas actualizadas correctamente');
        },
        error: function(xhr, status, error) {
            console.error('Error actualizando métricas:', error);
            mostrarNotificacion('Error al actualizar métricas', 'danger');
        }
    });
}

function toggleAutoRefresh() {
    if (autoRefreshInterval) {
        // Detener auto-refresh
        clearInterval(autoRefreshInterval);
        clearInterval(contadorInterval);
        autoRefreshInterval = null;
        contadorInterval = null;
        $('#badge-refresh').hide();
        $('#contador-refresh').hide();
        $('body').removeClass('refreshing');
    } else {
        // Iniciar auto-refresh
        autoRefreshInterval = setInterval(actualizarMetricas, 30000); // 30 segundos
        $('#badge-refresh').show();
        $('body').addClass('refreshing');
        
        // Contador visual
        contadorRefresh = 30;
        $('#contador-refresh').show();
        contadorInterval = setInterval(function() {
            contadorRefresh--;
            $('#contador-refresh').text('Próxima actualización en ' + contadorRefresh + 's');
            if (contadorRefresh <= 0) {
                contadorRefresh = 30;
            }
        }, 1000);
    }
}

function cambiarPeriodo(periodo) {
    // Actualizar botones activos
    $('.btn-group button').removeClass('active');
    event.target.classList.add('active');
    
    // Cargar datos del período
    $.ajax({
        url: '{{ route("admin.monitoreo.metricas") }}',
        method: 'GET',
        data: { periodo: periodo },
        success: function(data) {
            actualizarGrafico(data);
        },
        error: function() {
            mostrarNotificacion('Error al cargar datos del período', 'danger');
        }
    });
}

function actualizarGrafico(data) {
    if (!chartTendencias || !data || data.length === 0) return;
    
    const labels = data.map(item => new Date(item.timestamp).toLocaleTimeString());
    const dataCPU = data.map(item => item.cpu || 0);
    const dataMemoria = data.map(item => item.memoria?.porcentaje || 0);
    const dataDisco = data.map(item => item.disco?.porcentaje || 0);
    
    chartTendencias.data.labels = labels;
    chartTendencias.data.datasets[0].data = dataCPU;
    chartTendencias.data.datasets[1].data = dataMemoria;
    chartTendencias.data.datasets[2].data = dataDisco;
    chartTendencias.update();
}

function actualizarAlertas(alertas) {
    const container = $('#alertas-container');
    const resumen = $('#resumen-alertas');
    
    if (alertas.length === 0) {
        container.hide();
        resumen.html(`
            <div class="text-center text-success">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <p class="mb-0">Sin alertas activas</p>
                <small class="text-muted">Todos los sistemas funcionan correctamente</small>
            </div>
        `);
    } else {
        const criticas = alertas.filter(a => a.nivel === 'critical').length;
        const warnings = alertas.filter(a => a.nivel === 'warning').length;
        
        resumen.html(`
            <div class="alert-count">
                <span class="badge bg-danger">${criticas}</span>
                Críticas
            </div>
            <div class="alert-count">
                <span class="badge bg-warning">${warnings}</span>
                Advertencias
            </div>
        `);
        
        // Mostrar container de alertas si estaba oculto
        if (!container.is(':visible')) {
            container.show();
        }
    }
}

function actualizarEstadoGeneral(estado) {
    const badge = $('.badge:contains("Sistema")').first();
    if (badge.length) {
        badge.removeClass('bg-success bg-warning bg-danger')
             .addClass('bg-' + estado.color)
             .text(estado.mensaje);
    }
}

function marcarMetricasCriticas() {
    const umbrales = @json($umbrales);
    
    // CPU
    const cpuValue = parseFloat($('#cpu-value').text());
    const cpuCard = $('.metric-card[data-metric="cpu"]');
    if (cpuValue > umbrales.cpu) {
        cpuCard.addClass('warning');
    } else {
        cpuCard.removeClass('warning critical');
    }
    
    // Memoria
    const memoriaValue = parseFloat($('#memoria-value').text());
    const memoriaCard = $('.metric-card[data-metric="memoria"]');
    if (memoriaValue > umbrales.memoria) {
        memoriaCard.addClass('warning');
    } else {
        memoriaCard.removeClass('warning critical');
    }
    
    // Disco
    const discoValue = parseFloat($('#disco-value').text());
    const discoCard = $('.metric-card[data-metric="disco"]');
    if (discoValue > umbrales.disco) {
        discoCard.addClass(discoValue > 95 ? 'critical' : 'warning');
    } else {
        discoCard.removeClass('warning critical');
    }
}

function guardarConfiguracion() {
    const formData = new FormData($('#formConfiguracion')[0]);
    
    $.ajax({
        url: '{{ route("admin.monitoreo.configuracion") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#modalConfiguracion').modal('hide');
                mostrarNotificacion(response.mensaje, 'success');
                setTimeout(() => location.reload(), 1500);
            }
        },
        error: function(xhr) {
            let mensaje = 'Error al guardar configuración';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                mensaje = xhr.responseJSON.message;
            }
            mostrarNotificacion(mensaje, 'danger');
        }
    });
}

function mostrarNotificacion(mensaje, tipo) {
    const toast = document.createElement('div');
    toast.className = `alert alert-${tipo} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `<i class="fas fa-${tipo === 'success' ? 'check' : 'times'} me-2"></i>${mensaje}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (document.body.contains(toast)) {
            document.body.removeChild(toast);
        }
    }, 4000);
}
</script>
@stop