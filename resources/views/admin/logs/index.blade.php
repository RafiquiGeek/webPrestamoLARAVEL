@extends('layouts.admin')
@section('title', 'Registro de Incidencias del Sistema')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-exclamation-triangle mr-2"></i>Registro de Incidencias</h1>
   </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Dashboard de Estadísticas -->
    <div class="row mb-4" id="dashboard-estadisticas">
        <div class="col-lg-3 col-6">
            <div class="info-card text-center">
                <div class="info-label">
                    <i class="fas fa-exclamation-circle text-danger me-1"></i>
                    Total Incidencias
                </div>
                <div class="info-value display-6 text-danger">{{ $estadisticas['total'] }}</div>
                <small class="text-muted">Últimos 7 días</small>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-card text-center">
                <div class="info-label">
                    <i class="fas fa-times-circle text-danger me-1"></i>
                    Errores Críticos
                </div>
                <div class="info-value display-6 text-danger">{{ $estadisticas['errores'] }}</div>
                <small class="text-muted">Requieren atención inmediata</small>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-card text-center">
                <div class="info-label">
                    <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                    Advertencias
                </div>
                <div class="info-value display-6 text-warning">{{ $estadisticas['warnings'] }}</div>
                <small class="text-muted">Problemas menores</small>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-card text-center">
                <div class="info-label">
                    <i class="fas fa-clock text-info me-1"></i>
                    Últimas 24h
                </div>
                <div class="info-value display-6 text-info">{{ $estadisticas['ultimas_24h'] }}</div>
                <small class="text-muted">Incidencias recientes</small>
            </div>
        </div>
    </div>

    <!-- Gráfico de Tendencias -->
    <div class="account-card mb-4">
        <div class="card-header">
            <h3><i class="fas fa-chart-line me-2"></i>Tendencias de Incidencias</h3>
        </div>
        <div class="card-body">
            <canvas id="chartTendencias" height="100"></canvas>
        </div>
    </div>

    <!-- Filtros -->
    <div class="account-card mb-4">
        <div class="card-header">
            <h3><i class="fas fa-filter me-2"></i>Filtros de Búsqueda</h3>
        </div>
        <div class="card-body">
            <form id="formFiltros">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Nivel</label>
                        <select name="nivel" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            @foreach($niveles as $nivel)
                                <option value="{{ $nivel }}" {{ $filtros['nivel'] == $nivel ? 'selected' : '' }}>
                                    {{ $nivel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Categoría</label>
                        <select name="categoria" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            @foreach($categorias as $categoria)
                                <option value="{{ $categoria }}" {{ $filtros['categoria'] == $categoria ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $categoria)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Desde</label>
                        <input type="date" name="fecha_desde" class="form-control form-control-sm" 
                               value="{{ $filtros['fecha_desde'] }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Hasta</label>
                        <input type="date" name="fecha_hasta" class="form-control form-control-sm" 
                               value="{{ $filtros['fecha_hasta'] }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="buscar" class="form-control form-control-sm" 
                               placeholder="Mensaje, archivo, usuario..."
                               value="{{ $filtros['buscar'] }}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Resultados</label>
                        <select name="por_pagina" class="form-select form-select-sm">
                            <option value="25" {{ $filtros['por_pagina'] == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ $filtros['por_pagina'] == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $filtros['por_pagina'] == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search me-1"></i>Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltros()">
                            <i class="fas fa-eraser me-1"></i>Limpiar
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="actualizarAutomatico()">
                            <i class="fas fa-sync me-1"></i>Auto-actualizar
                            <span id="badge-auto" class="badge bg-success ms-1" style="display: none;">ON</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Distribución por Categorías -->
    <div class="account-card mb-4">
        <div class="card-header">
            <h3><i class="fas fa-chart-pie me-2"></i>Distribución por Categorías</h3>
        </div>
        <div class="card-body">
            <div class="row" id="categorias-container">
                @foreach($estadisticas['por_categoria'] as $categoria => $cantidad)
                    @php
                        $config = app('App\Http\Controllers\Admin\LogsController')->getCategoriaConfig($categoria);
                    @endphp
                    <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-3">
                        <div class="text-center">
                            <div class="badge bg-{{ $config['color'] }} mb-2 p-2">
                                <i class="fas {{ $config['icon'] }} fa-2x"></i>
                            </div>
                            <div class="fw-bold">{{ $cantidad }}</div>
                            <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $categoria)) }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Tabla de Logs -->
    <div class="account-card">
        <div class="card-header">
            <h3><i class="fas fa-list me-2"></i>Registro de Incidencias</h3>
            <div class="d-flex align-items-center">
                <small class="text-muted me-3" id="contador-resultados">
                    Mostrando {{ $logs->count() }} resultados
                </small>
                <div class="spinner-border spinner-border-sm text-primary" id="loading-spinner" style="display: none;"></div>
            </div>
            <div class="d-flex align-items-center gap-2">
           <button type="button" class="btn btn-outline-success btn-sm" onclick="exportarLogs()">
               <i class="fas fa-download me-1"></i>Exportar CSV
           </button>
           <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalLimpiar">
               <i class="fas fa-trash me-1"></i>Limpiar Logs
           </button>
       </div>
        </div>
        <div class="card-body p-0" id="tabla-logs-container">
            @include('admin.logs.partials.tabla-logs')
        </div>
    </div>
</div>

<!-- Modal para Limpiar Logs -->
<div class="modal fade" id="modalLimpiar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-trash text-danger me-2"></i>
                    Limpiar Logs Antiguos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.logs.limpiar') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Advertencia:</strong> Esta acción eliminará permanentemente los logs seleccionados.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Seleccione el tipo de limpieza:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_limpieza" id="limpiarActual" value="actual" checked>
                            <label class="form-check-label" for="limpiarActual">
                                <strong>Limpiar archivo actual (laravel.log)</strong>
                                <br><small class="text-muted">Vacía el contenido del archivo laravel.log actual</small>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_limpieza" id="limpiarAntiguos" value="antiguos">
                            <label class="form-check-label" for="limpiarAntiguos">
                                <strong>Eliminar logs antiguos</strong>
                                <br><small class="text-muted">Elimina archivos de log con fecha específica</small>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="opcionesAntiguos" style="display: none;">
                        <label class="form-label">Eliminar logs más antiguos que:</label>
                        <select name="dias_antiguedad" class="form-select">
                            <option value="30">30 días</option>
                            <option value="60">60 días</option>
                            <option value="90">90 días</option>
                            <option value="180">180 días</option>
                            <option value="365">1 año</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" name="confirmar" value="1" class="form-check-input" id="confirmarLimpieza" required>
                        <label class="form-check-label" for="confirmarLimpieza">
                            <strong>Confirmo que entiendo que esta acción no se puede deshacer</strong>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Eliminar Logs
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

.info-card {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 0.75rem;
    width: 100%;
    transition: transform 0.2s, box-shadow 0.2s;
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

.info-card .info-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.info-card .info-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a1a;
}

.display-6 {
    font-size: 2rem;
    font-weight: 700;
}

.table th {
    font-weight: 600;
    color: #495057;
    font-size: 0.875rem;
}

.log-nivel-ERROR {
    background-color: #fff5f5;
    border-left: 4px solid #dc3545;
}

.log-nivel-WARNING {
    background-color: #fffbf0;
    border-left: 4px solid #ffc107;
}

.log-nivel-CRITICAL {
    background-color: #fff0f0;
    border-left: 4px solid #8b0000;
}

.log-mensaje {
    max-width: 400px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.badge-categoria {
    font-size: 0.7rem;
    padding: 0.25em 0.5em;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.auto-actualizando {
    animation: pulse 2s infinite;
}
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let autoActualizarInterval = null;
let chartTendencias = null;

$(document).ready(function() {
    // Inicializar gráfico
    inicializarGrafico();
    
    // Manejar filtros
    $('#formFiltros').on('submit', function(e) {
        e.preventDefault();
        aplicarFiltros();
    });
    
    // Auto-aplicar filtros al cambiar
    $('#formFiltros select, #formFiltros input').on('change', function() {
        aplicarFiltros();
    });
    
    // Manejar cambio de tipo de limpieza
    $('input[name="tipo_limpieza"]').on('change', function() {
        if ($(this).val() === 'antiguos') {
            $('#opcionesAntiguos').show();
            $('select[name="dias_antiguedad"]').attr('required', true);
        } else {
            $('#opcionesAntiguos').hide();
            $('select[name="dias_antiguedad"]').attr('required', false);
        }
    });
});

function aplicarFiltros() {
    $('#loading-spinner').show();
    
    const filtros = {
        nivel: $('[name="nivel"]').val(),
        categoria: $('[name="categoria"]').val(),
        fecha_desde: $('[name="fecha_desde"]').val(),
        fecha_hasta: $('[name="fecha_hasta"]').val(),
        buscar: $('[name="buscar"]').val(),
        por_pagina: $('[name="por_pagina"]').val()
    };
    
    $.ajax({
        url: '{{ route("admin.logs.index") }}',
        method: 'GET',
        data: filtros,
        success: function(response) {
            $('#tabla-logs-container').html(response.logs_html);
            actualizarEstadisticas(response.estadisticas);
            actualizarGrafico(response.tendencias);
            $('#contador-resultados').text('Resultados actualizados');
        },
        error: function() {
            alert('Error al cargar los logs');
        },
        complete: function() {
            $('#loading-spinner').hide();
        }
    });
}

function inicializarGrafico() {
    const ctx = document.getElementById('chartTendencias').getContext('2d');
    const tendencias = @json($tendencias);
    
    const labels = Object.keys(tendencias);
    const dataErrores = labels.map(fecha => tendencias[fecha].errores || 0);
    const dataWarnings = labels.map(fecha => tendencias[fecha].warnings || 0);
    
    chartTendencias = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.map(fecha => new Date(fecha).toLocaleDateString()),
            datasets: [{
                label: 'Errores',
                data: dataErrores,
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.4
            }, {
                label: 'Advertencias',
                data: dataWarnings,
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                tension: 0.4
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
}

function actualizarGrafico(tendencias) {
    if (!chartTendencias) return;
    
    const labels = Object.keys(tendencias);
    const dataErrores = labels.map(fecha => tendencias[fecha].errores || 0);
    const dataWarnings = labels.map(fecha => tendencias[fecha].warnings || 0);
    
    chartTendencias.data.labels = labels.map(fecha => new Date(fecha).toLocaleDateString());
    chartTendencias.data.datasets[0].data = dataErrores;
    chartTendencias.data.datasets[1].data = dataWarnings;
    chartTendencias.update();
}

function actualizarEstadisticas(estadisticas) {
    // Actualizar contadores del dashboard
    $('.display-6').each(function(index) {
        const valores = [
            estadisticas.total,
            estadisticas.errores,
            estadisticas.warnings,
            estadisticas.ultimas_24h
        ];
        if (valores[index] !== undefined) {
            $(this).text(valores[index]);
        }
    });
}

function limpiarFiltros() {
    $('#formFiltros')[0].reset();
    $('[name="fecha_desde"]').val('{{ now()->subDays(7)->format("Y-m-d") }}');
    $('[name="fecha_hasta"]').val('{{ now()->format("Y-m-d") }}');
    aplicarFiltros();
}

function actualizarAutomatico() {
    if (autoActualizarInterval) {
        clearInterval(autoActualizarInterval);
        autoActualizarInterval = null;
        $('#badge-auto').hide();
        $('body').removeClass('auto-actualizando');
    } else {
        autoActualizarInterval = setInterval(aplicarFiltros, 30000); // 30 segundos
        $('#badge-auto').show();
        $('body').addClass('auto-actualizando');
    }
}

function exportarLogs() {
    const filtros = {
        nivel: $('[name="nivel"]').val(),
        categoria: $('[name="categoria"]').val(),
        fecha_desde: $('[name="fecha_desde"]').val(),
        fecha_hasta: $('[name="fecha_hasta"]').val(),
        buscar: $('[name="buscar"]').val()
    };
    
    const params = new URLSearchParams(filtros);
    window.open('{{ route("admin.logs.exportar") }}?' + params.toString());
}

function verDetalle(logId) {
    window.open('{{ route("admin.logs.detalle", "") }}/' + logId, '_blank');
}
</script>
@stop