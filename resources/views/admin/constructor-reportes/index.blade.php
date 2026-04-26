@extends('layouts.admin')
@section('title', 'Constructor de Reportes')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-chart-line me-2"></i>Constructor de Reportes</h1>
       <div class="d-flex align-items-center gap-2">
           <button type="button" class="btn btn-primary" onclick="nuevoReporte()">
               <i class="fas fa-plus mr-1"></i>Nuevo Reporte
           </button>
           <button type="button" class="btn btn-outline-info" onclick="verEstadisticas()">
               <i class="fas fa-chart-bar mr-1"></i>Estadísticas
           </button>
           <ol class="breadcrumb float-sm-right mb-0">
               <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
               <li class="breadcrumb-item active">Constructor de Reportes</li>
           </ol>
       </div>
   </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Tabs de navegación -->
    <ul class="nav nav-tabs mb-4" id="reporteTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="mis-reportes-tab" data-toggle="tab" 
                    href="#mis-reportes" role="tab" aria-controls="mis-reportes" aria-selected="true">
                <i class="fas fa-folder-user mr-1"></i>Mis Reportes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="plantillas-tab" data-toggle="tab" 
                    href="#plantillas" role="tab" aria-controls="plantillas" aria-selected="false">
                <i class="fas fa-template mr-1"></i>Plantillas
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="publicos-tab" data-toggle="tab" 
                    href="#publicos" role="tab" aria-controls="publicos" aria-selected="false">
                <i class="fas fa-share-alt mr-1"></i>Reportes Públicos
            </a>
        </li>
    </ul>

    <!-- Contenido de tabs -->
    <div class="tab-content" id="reporteTabsContent">
        <!-- Mis Reportes -->
        <div class="tab-pane fade show active" id="mis-reportes" role="tabpanel">
            <div class="row">
                <!-- Nuevo Reporte Card -->
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-4">
                    <div class="report-card new-report" onclick="nuevoReporte()">
                        <div class="report-icon">
                            <i class="fas fa-plus fa-3x"></i>
                        </div>
                        <div class="report-info">
                            <h5>Crear Nuevo Reporte</h5>
                            <p class="text-muted">Constructor de reportes personalizado</p>
                        </div>
                    </div>
                </div>

                <!-- Reportes guardados -->
                <div id="mis-reportes-container">
                    @foreach($reportesGuardados as $reporte)
                        @if($reporte['usuario_id'] === auth()->id())
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-4">
                            <div class="report-card" data-id="{{ $reporte['id'] }}">
                                <div class="report-header">
                                    <div class="report-icon">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div class="report-actions">
                                        <div class="custom-dropdown">
                                            <button class="dropdown-btn btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-content">
                                                <a href="#" onclick="editarReporte('{{ $reporte['id'] }}')">
                                                    <i class="fas fa-edit me-2"></i>Editar
                                                </a>
                                                <a href="#" onclick="duplicarReporte('{{ $reporte['id'] }}')">
                                                    <i class="fas fa-copy me-2"></i>Duplicar
                                                </a>
                                                <a href="#" onclick="generarReporte('{{ $reporte['id'] }}')">
                                                    <i class="fas fa-download me-2"></i>Generar
                                                </a>
                                                <div class="divider"></div>
                                                <a href="#" onclick="eliminarReporte('{{ $reporte['id'] }}')" class="text-danger">
                                                    <i class="fas fa-trash me-2"></i>Eliminar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="report-info">
                                    <h5>{{ $reporte['nombre'] }}</h5>
                                    <p class="text-muted">{{ $reporte['descripcion'] }}</p>
                                    <div class="report-meta">
                                        <span class="badge bg-primary">{{ $reporte['categoria'] }}</span>
                                        <small class="text-muted">
                                            Usado {{ $reporte['veces_usado'] ?? 0 }} veces
                                        </small>
                                    </div>
                                    <div class="report-date">
                                        <small class="text-muted">
                                            Modificado: {{ \Carbon\Carbon::parse($reporte['fecha_modificacion'])->format('d/m/Y') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Plantillas Predefinidas -->
        <div class="tab-pane fade" id="plantillas" role="tabpanel">
            <div class="row">
                @foreach($plantillasPredefinidas as $plantilla)
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-4">
                    <div class="report-card template-card" data-template="{{ $plantilla['id'] }}">
                        <div class="report-icon template-icon">
                            <i class="fas {{ $plantilla['icono'] }} fa-2x"></i>
                        </div>
                        <div class="report-info">
                            <h5>{{ $plantilla['nombre'] }}</h5>
                            <p class="text-muted">{{ $plantilla['descripcion'] }}</p>
                            <div class="report-meta">
                                <span class="badge bg-success">{{ $plantilla['categoria'] }}</span>
                            </div>
                            <div class="template-actions mt-3">
                                <button type="button" class="btn btn-primary btn-sm" 
                                        onclick="usarPlantilla('{{ $plantilla['id'] }}')">
                                    <i class="fas fa-magic mr-1"></i>Usar Plantilla
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Reportes Públicos -->
        <div class="tab-pane fade" id="publicos" role="tabpanel">
            <div class="row" id="reportes-publicos-container">
                @foreach($reportesGuardados as $reporte)
                    @if($reporte['es_publico'])
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-4">
                        <div class="report-card public-card" data-id="{{ $reporte['id'] }}">
                            <div class="report-header">
                                <div class="report-icon">
                                    <i class="fas fa-share-alt"></i>
                                </div>
                                <div class="report-actions">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="duplicarReporte('{{ $reporte['id'] }}')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="report-info">
                                <h5>{{ $reporte['nombre'] }}</h5>
                                <p class="text-muted">{{ $reporte['descripcion'] }}</p>
                                <div class="report-meta">
                                    <span class="badge bg-info">{{ $reporte['categoria'] }}</span>
                                    <small class="text-muted">Por: {{ $reporte['usuario_nombre'] }}</small>
                                </div>
                                <div class="report-date">
                                    <small class="text-muted">
                                        Creado: {{ \Carbon\Carbon::parse($reporte['fecha_creacion'])->format('d/m/Y') }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Guía Rápida -->
    <div class="account-card mt-4">
        <div class="card-header">
            <h3><i class="fas fa-lightbulb mr-2"></i>Guía Rápida</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="guide-step">
                        <div class="step-number">1</div>
                        <h6>Seleccionar Datos</h6>
                        <p>Elige la tabla principal y los campos que quieres incluir en tu reporte.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="guide-step">
                        <div class="step-number">2</div>
                        <h6>Aplicar Filtros</h6>
                        <p>Define filtros para mostrar solo los datos que necesitas.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="guide-step">
                        <div class="step-number">3</div>
                        <h6>Configurar Formato</h6>
                        <p>Personaliza el diseño, ordenamiento y agrupaciones.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="guide-step">
                        <div class="step-number">4</div>
                        <h6>Generar y Guardar</h6>
                        <p>Exporta en PDF, Excel o CSV y guarda tu reporte para uso futuro.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Estadísticas -->
<div class="modal fade" id="modalEstadisticas" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chart-bar text-info mr-2"></i>
                    Estadísticas de Reportes
                </h5>
                <button type="button" class="close" data-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="estadisticas-content">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando estadísticas...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
/* Estilos del sistema */
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

/* Tarjetas de reportes */
.report-card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    padding: 1.5rem;
    height: 280px;
    min-height: 280px;
    width: 100%;
    min-width: 250px;
    transition: all 0.3s ease;
    cursor: pointer;
    border: 2px solid transparent;
    position: relative;
    overflow: visible;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.report-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border-color: #005566;
}

.report-card.new-report {
    border: 2px dashed #dee2e6;
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    min-height: 200px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.report-card.new-report:hover {
    border-color: #007bff;
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
}

.report-card.new-report .report-icon i {
    color: #6c757d;
    transition: color 0.3s ease;
}

.report-card.new-report:hover .report-icon i {
    color: #007bff;
}

.report-card.template-card {
    border-left: 4px solid #28a745;
}

.report-card.public-card {
    border-left: 4px solid #17a2b8;
}

.report-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.report-icon {
    font-size: 1.5rem;
    color: #495057;
    margin-bottom: 1rem;
}

.template-icon {
    color: #28a745;
}

.report-info h5 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 0.5rem;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.report-info p {
    font-size: 0.875rem;
    line-height: 1.4;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
}

.report-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.report-meta .badge {
    font-size: 0.75rem;
}

.report-date {
    margin-top: 0.5rem;
}

.report-actions .dropdown-toggle {
    border: none;
    background: transparent;
}

.report-actions .dropdown-toggle:focus {
    box-shadow: none;
}

/* Dropdown personalizado simple */
.custom-dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background-color: white;
    min-width: 140px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    border: 1px solid #ddd;
    border-radius: 4px;
    z-index: 1000;
    padding: 5px 0;
}

.dropdown-content a {
    color: #333;
    padding: 8px 12px;
    text-decoration: none;
    display: block;
    font-size: 14px;
}

.dropdown-content a:hover {
    background-color: #f1f1f1;
    color: #333;
    text-decoration: none;
}

.dropdown-content a.text-danger:hover {
    background-color: #f8d7da;
    color: #721c24;
}

.divider {
    height: 1px;
    background-color: #ddd;
    margin: 5px 0;
}

.custom-dropdown.show .dropdown-content {
    display: block;
}

/* Guía rápida */
.guide-step {
    text-align: center;
    padding: 1rem;
}

.step-number {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #005566 0%, #003d47 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: bold;
    margin: 0 auto 1rem auto;
}

.guide-step h6 {
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 0.5rem;
}

.guide-step p {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0;
}

/* Tabs personalizados */
.nav-tabs .nav-link {
    border: none;
    background: transparent;
    color: #6c757d;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    border-radius: 8px 8px 0 0;
    margin-right: 0.5rem;
}

.nav-tabs .nav-link.active {
    background: #005566;
    color: white;
    border: none;
}

.nav-tabs .nav-link:hover {
    background: #e9ecef;
    color: #495057;
}

.nav-tabs .nav-link.active:hover {
    background: #003d47;
    color: white;
}

/* Estadísticas */
.stat-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    margin-bottom: 1rem;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #005566;
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 500;
    margin-top: 0.5rem;
}

/* Responsive */
@media (max-width: 1200px) {
    .report-card {
        min-width: 200px;
    }
}

@media (max-width: 768px) {
    .report-card {
        margin-bottom: 1rem;
        height: 250px;
        min-height: 250px;
        min-width: 100%;
    }
    
    .guide-step {
        margin-bottom: 2rem;
    }
    
    .step-number {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
}

@media (max-width: 576px) {
    .report-card {
        height: 220px;
        min-height: 220px;
        padding: 1rem;
    }
    
    .report-info h5 {
        font-size: 1rem;
    }
    
    .report-info p {
        font-size: 0.8rem;
    }
}

/* Animaciones */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.report-card {
    animation: fadeInUp 0.3s ease forwards;
}

/* Estados de carga */
.loading-skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}
</style>
@stop

@section('js')
<script>
function nuevoReporte() {
    window.location.href = '{{ route("admin.constructor-reportes.constructor") }}';
}

function editarReporte(id) {
    window.location.href = '{{ route("admin.constructor-reportes.editar", "") }}/' + id;
}

function duplicarReporte(id) {
    if (confirm('¿Deseas crear una copia de este reporte?')) {
        // Implementar duplicación
        mostrarNotificacion('Funcionalidad de duplicación en desarrollo', 'info');
    }
}

function generarReporte(id) {
    // Para generar, vamos a editar el reporte con un parámetro para indicar que es para generar
    window.location.href = '{{ route("admin.constructor-reportes.editar", "") }}/' + id + '?action=generate';
}

function eliminarReporte(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este reporte? Esta acción no se puede deshacer.')) {
        $.ajax({
            url: '{{ route("admin.constructor-reportes.eliminar", "") }}/' + id,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    mostrarNotificacion(response.mensaje, 'success');
                    location.reload();
                }
            },
            error: function(xhr) {
                let mensaje = 'Error al eliminar el reporte';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    mensaje = xhr.responseJSON.error;
                }
                mostrarNotificacion(mensaje, 'danger');
            }
        });
    }
}

function usarPlantilla(templateId) {
    mostrarNotificacion('Cargando plantilla...', 'info');
    // Implementar carga de plantilla predefinida
    setTimeout(() => {
        nuevoReporte();
    }, 1000);
}

function verEstadisticas() {
    new bootstrap.Modal(document.getElementById('modalEstadisticas')).show();
    
    $.ajax({
        url: '{{ route("admin.constructor-reportes.estadisticas") }}',
        method: 'GET',
        success: function(data) {
            mostrarEstadisticas(data);
        },
        error: function() {
            $('#estadisticas-content').html(`
                <div class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>Error al cargar las estadísticas</p>
                </div>
            `);
        }
    });
}

function mostrarEstadisticas(data) {
    let html = `
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">${data.total_reportes}</div>
                    <div class="stat-label">Total Reportes</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">${data.reportes_usuario}</div>
                    <div class="stat-label">Mis Reportes</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">${data.reportes_publicos}</div>
                    <div class="stat-label">Públicos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">${Object.keys(data.por_categoria).length}</div>
                    <div class="stat-label">Categorías</div>
                </div>
            </div>
        </div>
    `;

    if (data.mas_usados.length > 0) {
        html += `
            <h6 class="mb-3"><i class="fas fa-star text-warning me-2"></i>Reportes Más Usados</h6>
            <div class="list-group mb-4">
        `;
        
        data.mas_usados.forEach(reporte => {
            html += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${reporte.nombre}</strong>
                        <br><small class="text-muted">${reporte.descripcion}</small>
                    </div>
                    <span class="badge bg-primary rounded-pill">${reporte.veces_usado} usos</span>
                </div>
            `;
        });
        
        html += '</div>';
    }

    if (data.recientes.length > 0) {
        html += `
            <h6 class="mb-3"><i class="fas fa-clock text-info me-2"></i>Reportes Recientes</h6>
            <div class="list-group">
        `;
        
        data.recientes.forEach(reporte => {
            const fecha = new Date(reporte.fecha_modificacion).toLocaleDateString();
            html += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${reporte.nombre}</strong>
                        <br><small class="text-muted">Por: ${reporte.usuario_nombre}</small>
                    </div>
                    <small class="text-muted">${fecha}</small>
                </div>
            `;
        });
        
        html += '</div>';
    }

    $('#estadisticas-content').html(html);
}

function mostrarNotificacion(mensaje, tipo) {
    const toast = document.createElement('div');
    toast.className = `alert alert-${tipo} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `<i class="fas fa-${tipo === 'success' ? 'check' : tipo === 'danger' ? 'times' : 'info'} me-2"></i>${mensaje}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (document.body.contains(toast)) {
            document.body.removeChild(toast);
        }
    }, 4000);
}


// Dropdown con jQuery simple
$(document).ready(function() {
    // Abrir/cerrar dropdown
    $('.dropdown-btn').click(function(e) {
        e.stopPropagation();
        $('.custom-dropdown').removeClass('show');
        $(this).parent('.custom-dropdown').toggleClass('show');
    });
    
    // Cerrar dropdown al hacer clic fuera
    $(document).click(function() {
        $('.custom-dropdown').removeClass('show');
    });
    
    // Evitar que el dropdown se cierre al hacer clic en él
    $('.dropdown-content').click(function(e) {
        e.stopPropagation();
    });
    
    // Animación de entrada para las tarjetas
    $('.report-card').each(function(index) {
        $(this).css('animation-delay', (index * 0.1) + 's');
    });
});
</script>
@stop