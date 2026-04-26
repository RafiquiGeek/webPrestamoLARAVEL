@extends('layouts.admin')
@section('title', 'Alertas del Sistema')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-bell mr-2"></i>Alertas del Sistema</h1>
       <div class="d-flex align-items-center gap-2">
           <button type="button" class="btn btn-outline-primary btn-sm" onclick="actualizarAlertas()">
               <i class="fas fa-sync me-1"></i>Actualizar
           </button>
           <a href="{{ route('admin.monitoreo.index') }}" class="btn btn-outline-secondary btn-sm">
               <i class="fas fa-arrow-left me-1"></i>Volver al Monitoreo
           </a>
           <ol class="breadcrumb float-sm-right mb-0">
               <li class="breadcrumb-item"><a href="{{ route('admin.monitoreo.index') }}">Monitoreo</a></li>
               <li class="breadcrumb-item active">Alertas</li>
           </ol>
       </div>
   </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Resumen de Alertas -->
    <div class="row mb-4">
        <div class="col-lg-3 col-6">
            <div class="alert-summary-card critical">
                <div class="alert-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="alert-info">
                    <div class="alert-count" id="count-critical">0</div>
                    <div class="alert-label">Críticas</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="alert-summary-card warning">
                <div class="alert-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="alert-info">
                    <div class="alert-count" id="count-warning">0</div>
                    <div class="alert-label">Advertencias</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="alert-summary-card info">
                <div class="alert-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="alert-info">
                    <div class="alert-count" id="count-total">0</div>
                    <div class="alert-label">Total Activas</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="alert-summary-card success">
                <div class="alert-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="alert-info">
                    <div class="alert-count" id="ultima-verificacion">--:--</div>
                    <div class="alert-label">Última Verificación</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas Activas -->
    <div class="account-card mb-4">
        <div class="card-header">
            <h3><i class="fas fa-bell me-2"></i>Alertas Activas</h3>
            <div class="d-flex align-items-center">
                <span class="badge bg-secondary me-2" id="badge-estado">Cargando...</span>
                <div class="spinner-border spinner-border-sm text-primary" id="loading-spinner" style="display: none;"></div>
            </div>
        </div>
        <div class="card-body" id="alertas-container">
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando alertas...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Histórico de Alertas -->
    <div class="account-card">
        <div class="card-header">
            <h3><i class="fas fa-history me-2"></i>Histórico de Alertas</h3>
            <small class="text-muted">Últimas 24 horas</small>
        </div>
        <div class="card-body" id="historico-container">
            <div class="text-center py-4">
                <div class="spinner-border text-secondary" role="status">
                    <span class="visually-hidden">Cargando histórico...</span>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
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

/* Tarjetas de resumen de alertas */
.alert-summary-card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    padding: 1.5rem;
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
    transition: transform 0.2s, box-shadow 0.2s;
    border-left: 4px solid;
}

.alert-summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.alert-summary-card.critical {
    border-left-color: #dc3545;
}

.alert-summary-card.warning {
    border-left-color: #ffc107;
}

.alert-summary-card.info {
    border-left-color: #17a2b8;
}

.alert-summary-card.success {
    border-left-color: #28a745;
}

.alert-icon {
    font-size: 2rem;
    margin-right: 1rem;
    opacity: 0.8;
}

.alert-summary-card.critical .alert-icon {
    color: #dc3545;
}

.alert-summary-card.warning .alert-icon {
    color: #ffc107;
}

.alert-summary-card.info .alert-icon {
    color: #17a2b8;
}

.alert-summary-card.success .alert-icon {
    color: #28a745;
}

.alert-info {
    flex: 1;
}

.alert-count {
    font-size: 2rem;
    font-weight: 700;
    color: #1a1a1a;
    line-height: 1;
}

.alert-label {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 600;
    margin-top: 0.25rem;
}

/* Tarjetas de alertas individuales */
.alert-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    border-left: 4px solid;
    transition: all 0.2s;
}

.alert-item:hover {
    background: #e9ecef;
}

.alert-item.critical {
    border-left-color: #dc3545;
    background: #fff5f5;
}

.alert-item.warning {
    border-left-color: #ffc107;
    background: #fffbf0;
}

.alert-item-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.alert-type {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.alert-type.critical {
    background: #dc3545;
    color: white;
}

.alert-type.warning {
    background: #ffc107;
    color: #212529;
}

.alert-message {
    font-size: 1rem;
    color: #495057;
    margin-bottom: 0.5rem;
}

.alert-details {
    font-size: 0.875rem;
    color: #6c757d;
}

.alert-time {
    font-size: 0.75rem;
    color: #6c757d;
    float: right;
}

/* Estado sin alertas */
.no-alerts {
    text-align: center;
    padding: 3rem 1rem;
    color: #28a745;
}

.no-alerts i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.7;
}

.no-alerts h4 {
    color: #28a745;
    margin-bottom: 0.5rem;
}

.no-alerts p {
    color: #6c757d;
    margin-bottom: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .alert-summary-card {
        margin-bottom: 1rem;
    }
    
    .alert-count {
        font-size: 1.5rem;
    }
    
    .alert-icon {
        font-size: 1.5rem;
    }
}
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    cargarAlertas();
    cargarHistorico();
    
    // Auto-actualizar cada 60 segundos
    setInterval(cargarAlertas, 60000);
});

function cargarAlertas() {
    $('#loading-spinner').show();
    $('#badge-estado').text('Verificando...');
    
    $.ajax({
        url: '{{ route("admin.monitoreo.alertas") }}',
        method: 'GET',
        success: function(response) {
            actualizarResumenAlertas(response.activas);
            mostrarAlertasActivas(response.activas);
            $('#ultima-verificacion').text(new Date().toLocaleTimeString());
        },
        error: function() {
            $('#badge-estado').text('Error').removeClass().addClass('badge bg-danger');
            mostrarError('Error al cargar alertas activas');
        },
        complete: function() {
            $('#loading-spinner').hide();
        }
    });
}

function cargarHistorico() {
    // Por ahora mostrar mensaje, se puede implementar más tarde
    $('#historico-container').html(`
        <div class="text-center text-muted py-4">
            <i class="fas fa-info-circle fa-2x mb-2"></i>
            <p>El histórico de alertas estará disponible próximamente</p>
        </div>
    `);
}

function actualizarResumenAlertas(alertas) {
    const criticas = alertas.filter(a => a.nivel === 'critical').length;
    const warnings = alertas.filter(a => a.nivel === 'warning').length;
    const total = alertas.length;
    
    $('#count-critical').text(criticas);
    $('#count-warning').text(warnings);
    $('#count-total').text(total);
    
    // Actualizar badge de estado
    if (total === 0) {
        $('#badge-estado').text('Sistema OK').removeClass().addClass('badge bg-success');
    } else if (criticas > 0) {
        $('#badge-estado').text('Alertas Críticas').removeClass().addClass('badge bg-danger');
    } else {
        $('#badge-estado').text('Advertencias').removeClass().addClass('badge bg-warning');
    }
}

function mostrarAlertasActivas(alertas) {
    const container = $('#alertas-container');
    
    if (alertas.length === 0) {
        container.html(`
            <div class="no-alerts">
                <i class="fas fa-check-circle"></i>
                <h4>¡Excelente!</h4>
                <p>No hay alertas activas en el sistema</p>
                <small class="text-muted">Todos los componentes funcionan dentro de los parámetros normales</small>
            </div>
        `);
        return;
    }
    
    let html = '';
    alertas.forEach(function(alerta) {
        const tipoClass = alerta.nivel === 'critical' ? 'critical' : 'warning';
        const icon = alerta.nivel === 'critical' ? 'fas fa-exclamation-circle' : 'fas fa-exclamation-triangle';
        const tipoTexto = alerta.nivel === 'critical' ? 'Crítica' : 'Advertencia';
        
        html += `
            <div class="alert-item ${tipoClass}">
                <div class="alert-item-header">
                    <span class="alert-type ${tipoClass}">
                        <i class="${icon} me-1"></i>
                        ${tipoTexto}
                    </span>
                    <span class="alert-time">Ahora</span>
                </div>
                <div class="alert-message">
                    <i class="${getIconoTipo(alerta.tipo)} me-2"></i>
                    ${alerta.mensaje}
                </div>
                <div class="alert-details">
                    <strong>Tipo:</strong> ${getTipoTexto(alerta.tipo)} | 
                    <strong>Valor actual:</strong> ${alerta.valor} | 
                    <strong>Umbral:</strong> ${alerta.umbral}
                </div>
            </div>
        `;
    });
    
    container.html(html);
}

function getIconoTipo(tipo) {
    const iconos = {
        'cpu': 'fas fa-microchip',
        'memoria': 'fas fa-memory',
        'disco': 'fas fa-hdd',
        'db_conexiones': 'fas fa-database',
        'db_tiempo': 'fas fa-clock',
        'errores': 'fas fa-bug'
    };
    
    return iconos[tipo] || 'fas fa-exclamation';
}

function getTipoTexto(tipo) {
    const textos = {
        'cpu': 'Uso de CPU',
        'memoria': 'Uso de Memoria',
        'disco': 'Uso de Disco',
        'db_conexiones': 'Conexiones DB',
        'db_tiempo': 'Tiempo Respuesta DB',
        'errores': 'Errores del Sistema'
    };
    
    return textos[tipo] || tipo;
}

function actualizarAlertas() {
    cargarAlertas();
    mostrarNotificacion('Alertas actualizadas', 'success');
}

function mostrarError(mensaje) {
    $('#alertas-container').html(`
        <div class="text-center text-danger py-4">
            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
            <p>${mensaje}</p>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="cargarAlertas()">
                <i class="fas fa-sync me-1"></i>Reintentar
            </button>
        </div>
    `);
}

function mostrarNotificacion(mensaje, tipo) {
    const toast = document.createElement('div');
    toast.className = `alert alert-${tipo} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 250px;';
    toast.innerHTML = `<i class="fas fa-${tipo === 'success' ? 'check' : 'times'} me-2"></i>${mensaje}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (document.body.contains(toast)) {
            document.body.removeChild(toast);
        }
    }, 3000);
}
</script>
@stop