@extends('layouts.admin')
@section('title', 'Constructor de Reportes')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-tools mr-2"></i>Constructor de Reportes</h1>
   </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Barra de Herramientas -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-tools text-primary mr-2"></i>
                            <span class="font-weight-bold">Herramientas del Constructor</span>
                        </div>
                        <div class="btn-toolbar" role="toolbar">
                            <div class="btn-group mr-2" role="group">
                                <button type="button" class="btn btn-outline-secondary" onclick="limpiarTodo()">
                                    <i class="fas fa-eraser mr-1"></i>Limpiar
                                </button>
                                <button type="button" class="btn btn-outline-info" onclick="previsualizarReporte()">
                                    <i class="fas fa-eye mr-1"></i>Vista Previa
                                </button>
                            </div>
                            <div class="btn-group mr-2" role="group">
                                <button type="button" class="btn btn-success" onclick="guardarReporte()">
                                    <i class="fas fa-save mr-1"></i>Guardar
                                </button>
                                <button type="button" class="btn btn-primary" onclick="generarReporte()">
                                    <i class="fas fa-download mr-1"></i>Generar
                                </button>
                            </div>
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.constructor-reportes.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left mr-1"></i>Volver
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Panel Izquierdo - Configuración -->
        <div class="col-lg-3">
            <!-- Selección de Tabla -->
            <div class="config-panel mb-3">
                <div class="config-header">
                    <h5><i class="fas fa-database mr-2"></i>Fuente de Datos</h5>
                </div>
                <div class="config-body">
                    <label class="form-label">Tabla Principal</label>
                    <select id="tabla-principal" class="form-control">
                        <option value="">Seleccionar tabla...</option>
                        @foreach($tablasDisponibles as $tabla => $config)
                            <option value="{{ $tabla }}" data-icon="{{ $config['icon'] }}">
                                {{ $config['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted mt-1" id="tabla-descripcion"></small>
                </div>
            </div>

            <!-- Campos Disponibles -->
            <div class="config-panel mb-3">
                <div class="config-header">
                    <h5><i class="fas fa-list mr-2"></i>Campos Disponibles</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="expandirTodosCampos()">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </button>
                </div>
                <div class="config-body">
                    <div id="campos-container">
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-arrow-up"></i>
                            <p class="mb-0">Selecciona una tabla principal</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="config-panel mb-3">
                <div class="config-header">
                    <h5><i class="fas fa-filter mr-2"></i>Filtros</h5>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="agregarFiltro()">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div class="config-body">
                    <div id="filtros-container">
                        <div class="text-muted text-center py-2">
                            <small>No hay filtros configurados</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Central - Constructor -->
        <div class="col-lg-6">
            <!-- Área de Construcción -->
            <div class="builder-area">
                <div class="builder-header">
                    <h5><i class="fas fa-hammer mr-2"></i>Área de Construcción</h5>
                    <div class="builder-stats">
                        <span class="badge bg-info" id="campos-count">0 campos</span>
                        <span class="badge bg-warning" id="filtros-count">0 filtros</span>
                    </div>
                </div>
                
                <!-- Campos Seleccionados -->
                <div class="builder-section">
                    <h6><i class="fas fa-columns mr-2"></i>Campos del Reporte</h6>
                    <div id="campos-seleccionados" class="drop-zone campos-zone">
                        <div class="drop-placeholder">
                            <i class="fas fa-mouse-pointer fa-2x mb-2"></i>
                            <p>Arrastra los campos aquí</p>
                            <small class="text-muted">Los campos aparecerán como columnas en tu reporte</small>
                        </div>
                    </div>
                </div>

                <!-- Agrupaciones -->
                <div class="builder-section">
                    <h6><i class="fas fa-layer-group mr-2"></i>Agrupar Por</h6>
                    <div id="agrupaciones" class="drop-zone agrupaciones-zone">
                        <div class="drop-placeholder">
                            <i class="fas fa-sitemap fa-2x mb-2"></i>
                            <p>Arrastra campos para agrupar</p>
                            <small class="text-muted">Agrupa datos similares juntos</small>
                        </div>
                    </div>
                </div>

                <!-- Ordenamiento -->
                <div class="builder-section">
                    <h6><i class="fas fa-sort mr-2"></i>Ordenar Por</h6>
                    <div id="ordenamiento" class="drop-zone ordenamiento-zone">
                        <div class="drop-placeholder">
                            <i class="fas fa-sort-amount-down fa-2x mb-2"></i>
                            <p>Arrastra campos para ordenar</p>
                            <small class="text-muted">Define el orden de los resultados</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Derecho - Configuración Avanzada -->
        <div class="col-lg-3">
            <!-- Configuración del Reporte -->
            <div class="config-panel mb-3">
                <div class="config-header">
                    <h5><i class="fas fa-cog mr-2"></i>Configuración</h5>
                </div>
                <div class="config-body">
                    <div class="mb-3">
                        <label class="form-label">Título del Reporte</label>
                        <input type="text" id="titulo-reporte" class="form-control" 
                               placeholder="Mi Reporte Personalizado" value="{{ $reporte['nombre'] ?? '' }}">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea id="descripcion-reporte" class="form-control" rows="2" 
                                  placeholder="Descripción del reporte...">{{ $reporte['descripcion'] ?? '' }}</textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Límite de Registros</label>
                        <select id="limite-registros" class="form-control">
                            <option value="100">100 registros</option>
                            <option value="500">500 registros</option>
                            <option value="1000" selected>1,000 registros</option>
                            <option value="5000">5,000 registros</option>
                            <option value="10000">10,000 registros</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Formato de Salida</label>
                        <select id="formato-salida" class="form-control">
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="csv">CSV</option>
                            <option value="json">JSON</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Vista Previa -->
            <div class="config-panel mb-3">
                <div class="config-header">
                    <h5><i class="fas fa-chart-bar mr-2"></i>Gráficos</h5>
                </div>
                <div class="config-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="incluir-graficos">
                        <label class="form-check-label" for="incluir-graficos">
                            Incluir gráficos automáticos
                        </label>
                    </div>
                    <small class="form-text text-muted">
                        Se generarán gráficos basados en los datos numéricos
                    </small>
                </div>
            </div>

            <!-- SQL Generado -->
            <div class="config-panel">
                <div class="config-header">
                    <h5><i class="fas fa-code mr-2"></i>SQL Generado</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copiarSQL()">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <div class="config-body">
                    <pre id="sql-preview" class="sql-code"><code>-- El SQL se generará automáticamente</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Vista Previa -->
<div class="modal fade" id="modalVistaPrevia" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye text-info mr-2"></i>Vista Previa del Reporte
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="vista-previa-content">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Generando vista previa...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="generarReporte()">
                    <i class="fas fa-download mr-1"></i>Generar Reporte
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Guardado -->
<div class="modal fade" id="modalGuardar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-save text-success mr-2"></i>Guardar Reporte
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-guardar">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Reporte</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <input type="text" name="categoria" class="form-control" 
                               placeholder="General" value="General">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="es_publico" id="es-publico">
                        <label class="form-check-label" for="es-publico">
                            Hacer público (otros usuarios pueden verlo)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.css" />
<style>
/* Estilos base */
.config-panel {
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.config-header {
    background: #f8f9fa;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.config-header h5 {
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0;
    color: #495057;
}

.config-body {
    padding: 1rem;
}

/* Builder Area */
.builder-area {
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    padding: 0;
    min-height: 700px;
}

.builder-header {
    background: #f8f9fa;
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.builder-header h5 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    color: #495057;
}

.builder-stats .badge {
    margin-left: 0.5rem;
}

.builder-section {
    padding: 1rem;
    border-bottom: 1px solid #f1f3f4;
}

.builder-section:last-child {
    border-bottom: none;
}

.builder-section h6 {
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.75rem;
}

/* Drop Zones */
.drop-zone {
    min-height: 100px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    transition: all 0.3s ease;
    position: relative;
}

.drop-zone.drag-over {
    border-color: #007bff;
    background-color: #f8f9ff;
    transform: scale(1.02);
}

.drop-placeholder {
    text-align: center;
    color: #6c757d;
    user-select: none;
}

.drop-placeholder i {
    color: #adb5bd;
    margin-bottom: 0.5rem;
}

.drop-placeholder p {
    margin-bottom: 0.25rem;
    font-weight: 500;
}

/* Campos */
.campo-item {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.5rem;
    cursor: grab;
    transition: all 0.2s ease;
    position: relative;
    user-select: none;
}

.campo-item:hover {
    background: #e9ecef;
    border-color: #007bff;
    transform: translateY(-1px);
}

.campo-item.dragging {
    opacity: 0.5;
    transform: rotate(5deg);
    cursor: grabbing;
}

.campo-item.selected {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.campo-nombre {
    font-weight: 500;
    font-size: 0.875rem;
}

.campo-tipo {
    font-size: 0.75rem;
    opacity: 0.8;
}

.campo-tabla {
    font-size: 0.75rem;
    opacity: 0.6;
}

/* Items en las drop zones */
.dropped-item {
    background: #007bff;
    color: white;
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    margin: 0.25rem;
    display: inline-flex;
    align-items: center;
    font-size: 0.875rem;
    cursor: move;
    transition: all 0.2s ease;
}

.dropped-item:hover {
    background: #0056b3;
    transform: scale(1.05);
}

.dropped-item .remove-btn {
    margin-left: 0.5rem;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 3px;
    color: white;
    padding: 0.1rem 0.3rem;
    font-size: 0.75rem;
    cursor: pointer;
}

.dropped-item .remove-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Grupos de campos */
.campos-grupo {
    margin-bottom: 1rem;
}

.campos-grupo-header {
    background: #e9ecef;
    padding: 0.5rem 0.75rem;
    border-radius: 6px 6px 0 0;
    font-weight: 600;
    font-size: 0.875rem;
    color: #495057;
    cursor: pointer;
    user-select: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.campos-grupo-header:hover {
    background: #dee2e6;
}

.campos-grupo-body {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 6px 6px;
    padding: 0.75rem;
    max-height: 200px;
    overflow-y: auto;
}

.campos-grupo-body.collapsed {
    display: none;
}

/* Filtros */
.filtro-item {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background: #f8f9fa;
}

.filtro-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.filtro-controls {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}

/* SQL Code */
.sql-code {
    background: #2d3748;
    color: #e2e8f0;
    border-radius: 6px;
    padding: 0.75rem;
    font-size: 0.75rem;
    line-height: 1.4;
    max-height: 200px;
    overflow-y: auto;
    margin: 0;
}

/* Vista previa */
.preview-table {
    font-size: 0.875rem;
}

.preview-table th {
    background: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

.preview-stats {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.preview-stats .row > div {
    text-align: center;
}

.preview-stats .stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #007bff;
}

.preview-stats .stat-label {
    font-size: 0.875rem;
    color: #6c757d;
}

/* Responsive */
@media (max-width: 992px) {
    .builder-area {
        margin-bottom: 2rem;
    }
    
    .drop-zone {
        min-height: 80px;
    }
    
    .config-panel {
        margin-bottom: 1rem;
    }
}

/* Scrollbars personalizados */
.campos-grupo-body::-webkit-scrollbar,
.sql-code::-webkit-scrollbar {
    width: 6px;
}

.campos-grupo-body::-webkit-scrollbar-track,
.sql-code::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.campos-grupo-body::-webkit-scrollbar-thumb,
.sql-code::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.campos-grupo-body::-webkit-scrollbar-thumb:hover,
.sql-code::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Animaciones */
@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropped-item {
    animation: slideInDown 0.3s ease;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.drag-over {
    animation: pulse 1s infinite;
}

/* Estados vacíos */
.empty-state {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
let esquemaTablas = @json($esquemaTablas);
let configuracionActual = @json($reporte['configuracion'] ?? null);
let sortables = {};

$(document).ready(function() {
    inicializarConstructor();
    
    // Si hay configuración previa, cargarla
    if (configuracionActual) {
        cargarConfiguracion(configuracionActual);
    }
});

function inicializarConstructor() {
    // Event listeners
    $('#tabla-principal').on('change', function() {
        const tabla = $(this).val();
        if (tabla) {
            cargarCamposTabla(tabla);
            actualizarDescripcionTabla(tabla);
        } else {
            limpiarCampos();
        }
    });

    // Inicializar sortables para las drop zones
    inicializarSortables();
    
    // Actualizar contadores
    actualizarContadores();
}

function inicializarSortables() {
    // Campos seleccionados
    sortables.campos = Sortable.create(document.getElementById('campos-seleccionados'), {
        group: 'campos',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        onAdd: function(evt) {
            const item = evt.item;
            convertirADroppedItem(item, 'campo');
            actualizarContadores();
            actualizarSQL();
        },
        onRemove: function(evt) {
            actualizarContadores();
            actualizarSQL();
        }
    });

    // Agrupaciones
    sortables.agrupaciones = Sortable.create(document.getElementById('agrupaciones'), {
        group: 'campos',
        animation: 150,
        ghostClass: 'sortable-ghost',
        onAdd: function(evt) {
            const item = evt.item;
            convertirADroppedItem(item, 'agrupacion');
            actualizarSQL();
        }
    });

    // Ordenamiento
    sortables.ordenamiento = Sortable.create(document.getElementById('ordenamiento'), {
        group: 'campos',
        animation: 150,
        ghostClass: 'sortable-ghost',
        onAdd: function(evt) {
            const item = evt.item;
            convertirADroppedItem(item, 'ordenamiento');
            actualizarSQL();
        }
    });
}

function cargarCamposTabla(tabla) {
    $.ajax({
        url: '{{ route("admin.constructor-reportes.campos") }}',
        method: 'GET',
        data: { tabla: tabla },
        success: function(response) {
            mostrarCampos(response.campos, response.camposRelacionados);
        },
        error: function() {
            mostrarNotificacion('Error al cargar los campos de la tabla', 'danger');
        }
    });
}

function mostrarCampos(campos, camposRelacionados) {
    const container = $('#campos-container');
    let html = '';

    // Campos de la tabla principal
    if (campos.length > 0) {
        const tablaPrincipal = $('#tabla-principal').val();
        const configuracionTabla = esquemaTablas[tablaPrincipal];
        
        html += `
            <div class="campos-grupo">
                <div class="campos-grupo-header" onclick="toggleGrupo(this)">
                    <span><i class="fas ${configuracionTabla.config.icon} mr-2"></i>${configuracionTabla.config.label}</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="campos-grupo-body">
        `;
        
        campos.forEach(campo => {
            html += crearCampoHTML(campo);
        });
        
        html += '</div></div>';
    }

    // Campos relacionados
    Object.keys(camposRelacionados).forEach(tabla => {
        const relacionados = camposRelacionados[tabla];
        html += `
            <div class="campos-grupo">
                <div class="campos-grupo-header" onclick="toggleGrupo(this)">
                    <span><i class="fas fa-link mr-2"></i>${relacionados.label}</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="campos-grupo-body collapsed">
        `;
        
        relacionados.campos.forEach(campo => {
            html += crearCampoHTML(campo);
        });
        
        html += '</div></div>';
    });

    container.html(html);
    
    // Hacer campos draggables
    $('.campo-item').each(function() {
        $(this).attr('draggable', true);
        $(this).on('dragstart', function(e) {
            $(this).addClass('dragging');
            e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify({
                nombre: $(this).data('nombre'),
                tabla: $(this).data('tabla'),
                tipo: $(this).data('tipo'),
                label: $(this).find('.campo-nombre').text()
            }));
        });
        
        $(this).on('dragend', function(e) {
            $(this).removeClass('dragging');
        });
    });

    // Configurar drop zones
    configurarDropZones();
}

function crearCampoHTML(campo) {
    const tipoIcon = getTipoIcon(campo.tipo);
    const tipoColor = getTipoColor(campo.tipo);
    
    return `
        <div class="campo-item" 
             data-nombre="${campo.nombre_completo}" 
             data-tabla="${campo.tabla}"
             data-tipo="${campo.tipo}"
             draggable="true">
            <div class="campo-nombre">
                <i class="fas ${tipoIcon} mr-1" style="color: ${tipoColor}"></i>
                ${campo.label}
            </div>
            <div class="campo-tipo">${campo.tipo.toUpperCase()}</div>
            <div class="campo-tabla">${campo.tabla}</div>
        </div>
    `;
}

function getTipoIcon(tipo) {
    const iconos = {
        'integer': 'fa-hashtag',
        'decimal': 'fa-calculator',
        'float': 'fa-calculator',
        'double': 'fa-calculator',
        'string': 'fa-font',
        'text': 'fa-align-left',
        'datetime': 'fa-calendar',
        'date': 'fa-calendar-day',
        'timestamp': 'fa-clock',
        'boolean': 'fa-toggle-on'
    };
    
    return iconos[tipo] || 'fa-question';
}

function getTipoColor(tipo) {
    const colores = {
        'integer': '#007bff',
        'decimal': '#28a745',
        'float': '#28a745',
        'double': '#28a745',
        'string': '#6f42c1',
        'text': '#6f42c1',
        'datetime': '#fd7e14',
        'date': '#fd7e14',
        'timestamp': '#fd7e14',
        'boolean': '#dc3545'
    };
    
    return colores[tipo] || '#6c757d';
}

function configurarDropZones() {
    $('.drop-zone').each(function() {
        $(this).on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });
        
        $(this).on('dragleave', function(e) {
            $(this).removeClass('drag-over');
        });
        
        $(this).on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');
            
            const data = JSON.parse(e.originalEvent.dataTransfer.getData('text/plain'));
            agregarCampoAZona($(this), data);
        });
    });
}

function agregarCampoAZona(zona, campo) {
    // Ocultar placeholder si existe
    zona.find('.drop-placeholder').hide();
    
    // Crear elemento dropped
    const tipoZona = zona.hasClass('campos-zone') ? 'campo' : 
                     zona.hasClass('agrupaciones-zone') ? 'agrupacion' : 'ordenamiento';
    
    const item = crearDroppedItem(campo, tipoZona);
    zona.append(item);
    
    actualizarContadores();
    actualizarSQL();
}

function crearDroppedItem(campo, tipo) {
    const item = $(`
        <div class="dropped-item" data-campo="${campo.nombre}" data-tipo="${tipo}">
            <span>${campo.label}</span>
            <button class="remove-btn" onclick="removerItem(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `);
    
    if (tipo === 'ordenamiento') {
        item.append(`
            <select class="form-control form-control-sm ml-2" style="width: auto; display: inline-block;">
                <option value="asc">ASC</option>
                <option value="desc">DESC</option>
            </select>
        `);
    }
    
    return item;
}

function convertirADroppedItem(elemento, tipo) {
    const $elemento = $(elemento);
    const nombre = $elemento.data('nombre') || $elemento.text();
    const label = $elemento.find('.campo-nombre').text() || $elemento.text();
    
    $elemento.removeClass('campo-item').addClass('dropped-item');
    $elemento.html(`
        <span>${label}</span>
        <button class="remove-btn" onclick="removerItem(this)">
            <i class="fas fa-times"></i>
        </button>
    `);
    
    if (tipo === 'ordenamiento') {
        $elemento.append(`
            <select class="form-control form-control-sm ml-2" style="width: auto; display: inline-block;">
                <option value="asc">ASC</option>
                <option value="desc">DESC</option>
            </select>
        `);
    }
    
    $elemento.attr('data-campo', nombre);
    $elemento.attr('data-tipo', tipo);
}

function removerItem(btn) {
    const item = $(btn).closest('.dropped-item');
    const zona = item.parent();
    
    item.remove();
    
    // Mostrar placeholder si no hay items
    if (zona.find('.dropped-item').length === 0) {
        zona.find('.drop-placeholder').show();
    }
    
    actualizarContadores();
    actualizarSQL();
}

function toggleGrupo(header) {
    const body = $(header).next('.campos-grupo-body');
    const icon = $(header).find('i:last-child');
    
    body.toggleClass('collapsed');
    icon.toggleClass('fa-chevron-down fa-chevron-up');
}

function expandirTodosCampos() {
    $('.campos-grupo-body').removeClass('collapsed');
    $('.campos-grupo-header i:last-child').removeClass('fa-chevron-up').addClass('fa-chevron-down');
}

function actualizarDescripcionTabla(tabla) {
    const config = esquemaTablas[tabla]?.config;
    if (config) {
        $('#tabla-descripcion').text(config.descripcion);
    }
}

function actualizarContadores() {
    const camposCount = $('#campos-seleccionados .dropped-item').length;
    const filtrosCount = $('#filtros-container .filtro-item').length;
    
    $('#campos-count').text(`${camposCount} campos`);
    $('#filtros-count').text(`${filtrosCount} filtros`);
}

function actualizarSQL() {
    const configuracion = obtenerConfiguracionActual();
    
    if (!configuracion.tabla_principal || configuracion.campos.length === 0) {
        $('#sql-preview code').text('-- Selecciona una tabla y campos para ver el SQL');
        return;
    }
    
    // Construir SQL básico
    let sql = `SELECT ${configuracion.campos.join(', ')}\nFROM ${configuracion.tabla_principal}`;
    
    // Agregar JOINs si hay campos relacionados
    const joinsNecesarios = determinarJoins(configuracion.campos, configuracion.tabla_principal);
    joinsNecesarios.forEach(join => {
        sql += `\nLEFT JOIN ${join}`;
    });
    
    // Agregar filtros
    if (configuracion.filtros.length > 0) {
        sql += '\nWHERE ';
        const condiciones = configuracion.filtros.map(filtro => 
            `${filtro.campo} ${filtro.operador} '${filtro.valor}'`
        );
        sql += condiciones.join(' AND ');
    }
    
    // Agregar agrupaciones
    if (configuracion.agrupaciones.length > 0) {
        sql += `\nGROUP BY ${configuracion.agrupaciones.join(', ')}`;
    }
    
    // Agregar ordenamiento
    if (configuracion.ordenamiento.length > 0) {
        const ordenamientos = configuracion.ordenamiento.map(orden => 
            `${orden.campo} ${orden.direccion}`
        );
        sql += `\nORDER BY ${ordenamientos.join(', ')}`;
    }
    
    // Agregar límite
    if (configuracion.limite) {
        sql += `\nLIMIT ${configuracion.limite}`;
    }
    
    $('#sql-preview code').text(sql);
}

function determinarJoins(campos, tablaPrincipal) {
    const joins = [];
    const esquema = esquemaTablas[tablaPrincipal];
    
    if (!esquema || !esquema.relaciones) return joins;
    
    campos.forEach(campo => {
        if (campo.includes('.')) {
            const tabla = campo.split('.')[0];
            if (tabla !== tablaPrincipal && esquema.relaciones[tabla]) {
                const condicion = esquema.relaciones[tabla];
                joins.push(`${tabla} ON ${condicion}`);
            }
        }
    });
    
    return [...new Set(joins)]; // Eliminar duplicados
}

function obtenerConfiguracionActual() {
    const campos = [];
    $('#campos-seleccionados .dropped-item').each(function() {
        const campo = $(this).data('campo') || $(this).attr('data-campo');
        if (campo) campos.push(campo);
    });
    
    const agrupaciones = [];
    $('#agrupaciones .dropped-item').each(function() {
        const campo = $(this).data('campo') || $(this).attr('data-campo');
        if (campo) agrupaciones.push(campo);
    });
    
    const ordenamiento = [];
    $('#ordenamiento .dropped-item').each(function() {
        const campo = $(this).data('campo') || $(this).attr('data-campo');
        const direccion = $(this).find('select, .orden-direccion').val() || 'asc';
        if (campo && campo.trim() && campo !== '\n') {
            ordenamiento.push({ campo: campo.trim(), direccion });
        }
    });
    
    const filtros = [];
    $('#filtros-container .filtro-item').each(function() {
        const campo = $(this).find('.filtro-campo').val();
        const operador = $(this).find('.filtro-operador').val();
        const valor = $(this).find('.filtro-valor').val();
        
        if (campo && operador && valor) {
            filtros.push({ campo, operador, valor });
        }
    });
    
    return {
        tabla_principal: $('#tabla-principal').val(),
        campos: campos,
        filtros: filtros,
        agrupaciones: agrupaciones,
        ordenamiento: ordenamiento,
        limite: parseInt($('#limite-registros').val()) || 1000,
        titulo: $('#titulo-reporte').val() || 'Reporte Personalizado',
        descripcion: $('#descripcion-reporte').val() || '',
        formato: $('#formato-salida').val() || 'pdf',
        incluir_graficos: false
    };
}

function previsualizarReporte() {
    const configuracion = obtenerConfiguracionActual();
    
    if (!configuracion.tabla_principal || configuracion.campos.length === 0) {
        mostrarNotificacion('Debes seleccionar una tabla y al menos un campo', 'warning');
        return;
    }
    
    $('#modalVistaPrevia').modal('show');
    
    $.ajax({
        url: '{{ route("admin.constructor-reportes.previsualizar") }}',
        method: 'POST',
        data: configuracion,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                mostrarVistaPrevia(response.datos, response.metadatos);
            } else {
                mostrarErrorVistaPrevia(response.error);
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.error || 'Error al generar vista previa';
            mostrarErrorVistaPrevia(error);
        }
    });
}

function mostrarVistaPrevia(datos, metadatos) {
    let html = `
        <div class="preview-stats">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-number">${metadatos.total_registros}</div>
                    <div class="stat-label">Registros</div>
                </div>
                <div class="col-md-3">
                    <div class="stat-number">${metadatos.campos_seleccionados}</div>
                    <div class="stat-label">Campos</div>
                </div>
                <div class="col-md-3">
                    <div class="stat-number">${metadatos.filtros_aplicados}</div>
                    <div class="stat-label">Filtros</div>
                </div>
                <div class="col-md-3">
                    <div class="stat-number">${metadatos.tiempo_ejecucion}</div>
                    <div class="stat-label">Tiempo</div>
                </div>
            </div>
        </div>
    `;
    
    if (datos.length > 0) {
        html += '<div class="table-responsive"><table class="table table-striped preview-table"><thead><tr>';
        
        // Encabezados
        Object.keys(datos[0]).forEach(campo => {
            html += `<th>${campo}</th>`;
        });
        html += '</tr></thead><tbody>';
        
        // Datos (máximo 10 filas para vista previa)
        datos.slice(0, 10).forEach(fila => {
            html += '<tr>';
            Object.values(fila).forEach(valor => {
                html += `<td>${valor || '-'}</td>`;
            });
            html += '</tr>';
        });
        
        html += '</tbody></table></div>';
        
        if (datos.length > 10) {
            html += `<p class="text-muted text-center">Mostrando 10 de ${datos.length} registros</p>`;
        }
    } else {
        html += '<div class="text-center text-muted py-4"><i class="fas fa-info-circle fa-2x mb-2"></i><p>No se encontraron datos con los filtros aplicados</p></div>';
    }
    
    $('#vista-previa-content').html(html);
}

function mostrarErrorVistaPrevia(error) {
    $('#vista-previa-content').html(`
        <div class="text-center text-danger py-4">
            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
            <p><strong>Error al generar vista previa:</strong></p>
            <p>${error}</p>
        </div>
    `);
}

function generarReporte() {
    const configuracion = obtenerConfiguracionActual();
    console.log('Configuración para generar reporte:', configuracion);
    
    if (!configuracion.tabla_principal || configuracion.campos.length === 0) {
        mostrarNotificacion('Debes seleccionar una tabla y al menos un campo', 'warning');
        return;
    }
    
    // Verificar campos requeridos
    if (!configuracion.formato) {
        mostrarNotificacion('Debes seleccionar un formato de salida', 'warning');  
        return;
    }
    
    // Validar configuración
    console.log('Validando configuración...');
    console.log('tabla_principal:', configuracion.tabla_principal);
    console.log('campos:', configuracion.campos);
    console.log('agrupaciones:', configuracion.agrupaciones);
    console.log('ordenamiento:', configuracion.ordenamiento);
    console.log('filtros:', configuracion.filtros);
    console.log('formato:', configuracion.formato);
    console.log('incluir_graficos:', configuracion.incluir_graficos, typeof configuracion.incluir_graficos);
    console.log('limite:', configuracion.limite, typeof configuracion.limite);
    console.log('Full config:', JSON.stringify(configuracion, null, 2));
    
    // Crear formulario dinámico para descarga
    const form = $('<form>', {
        method: 'POST',
        action: '{{ route("admin.constructor-reportes.generar") }}'
    });
    
    // Agregar token CSRF
    form.append($('<input>', {
        type: 'hidden',
        name: '_token',
        value: $('meta[name="csrf-token"]').attr('content')
    }));
    
    // Agregar configuración
    Object.keys(configuracion).forEach(key => {
        if (Array.isArray(configuracion[key])) {
            configuracion[key].forEach((item, index) => {
                if (typeof item === 'object') {
                    Object.keys(item).forEach(subKey => {
                        form.append($('<input>', {
                            type: 'hidden',
                            name: `${key}[${index}][${subKey}]`,
                            value: item[subKey]
                        }));
                    });
                } else {
                    form.append($('<input>', {
                        type: 'hidden',
                        name: `${key}[]`,
                        value: item
                    }));
                }
            });
        } else {
            form.append($('<input>', {
                type: 'hidden',
                name: key,
                value: configuracion[key]
            }));
        }
    });
    
    // Usar AJAX con mejor manejo de errores
    $.ajax({
        url: '{{ route("admin.constructor-reportes.generar") }}',
        method: 'POST',
        data: JSON.stringify(configuracion),
        contentType: 'application/json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function() {
            mostrarNotificacion('Generando reporte...', 'info');
        },
        success: function(response) {
            console.log('Respuesta del servidor:', response);
            if (response.success !== false) {
                mostrarNotificacion('Reporte generado correctamente', 'success');
                // Manejar descarga si aplica
            } else {
                mostrarNotificacion(response.error || 'Error desconocido', 'danger');
            }
        },
        error: function(xhr) {
            console.error('Error completo:', xhr);
            let mensaje = 'Error al generar el reporte';
            
            if (xhr.responseJSON && xhr.responseJSON.error) {
                mensaje = xhr.responseJSON.error;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errores = Object.values(xhr.responseJSON.errors).flat();
                mensaje = errores.join(', ');
            }
            
            mostrarNotificacion(mensaje, 'danger');
        }
    });
}

function guardarReporte() {
    const configuracion = obtenerConfiguracionActual();
    
    if (!configuracion.tabla_principal || configuracion.campos.length === 0) {
        mostrarNotificacion('Debes seleccionar una tabla y al menos un campo', 'warning');
        return;
    }
    
    // Llenar formulario con datos actuales
    $('#form-guardar input[name="nombre"]').val(configuracion.titulo || '');
    $('#form-guardar textarea[name="descripcion"]').val(configuracion.descripcion || '');
    
    $('#modalGuardar').modal('show');
}

$('#form-guardar').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const configuracion = obtenerConfiguracionActual();
    
    console.log('Configuración a enviar:', configuracion);
    
    formData.append('configuracion', JSON.stringify(configuracion));
    
    $.ajax({
        url: '{{ route("admin.constructor-reportes.guardar") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#modalGuardar').modal('hide');
                mostrarNotificacion(response.mensaje, 'success');
            }
        },
        error: function(xhr) {
            console.error('Error al guardar:', xhr.responseJSON);
            let error = 'Error al guardar el reporte';
            
            if (xhr.responseJSON) {
                if (xhr.responseJSON.message) {
                    error = xhr.responseJSON.message;
                } else if (xhr.responseJSON.errors) {
                    // Errores de validación
                    const errores = Object.values(xhr.responseJSON.errors).flat();
                    error = errores.join(', ');
                } else if (xhr.responseJSON.error) {
                    error = xhr.responseJSON.error;
                }
            }
            
            mostrarNotificacion(error, 'danger');
        }
    });
});

function agregarFiltro() {
    const configuracion = obtenerConfiguracionActual();
    
    if (!configuracion.tabla_principal) {
        mostrarNotificacion('Primero selecciona una tabla principal', 'warning');
        return;
    }
    
    const filtroId = 'filtro_' + Date.now();
    const html = `
        <div class="filtro-item" id="${filtroId}">
            <div class="filtro-header">
                <small class="text-muted">Filtro</small>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerFiltro('${filtroId}')">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="filtro-controls">
                <select class="form-control form-control-sm filtro-campo">
                    <option value="">Campo...</option>
                </select>
                <select class="form-control form-control-sm filtro-operador">
                    <option value="equals">Igual a</option>
                    <option value="not_equals">Diferente de</option>
                    <option value="contains">Contiene</option>
                    <option value="starts_with">Empieza con</option>
                    <option value="ends_with">Termina con</option>
                    <option value="greater_than">Mayor que</option>
                    <option value="less_than">Menor que</option>
                    <option value="between">Entre</option>
                    <option value="not_null">No vacío</option>
                    <option value="null">Vacío</option>
                </select>
            </div>
            <input type="text" class="form-control form-control-sm mt-2 filtro-valor" placeholder="Valor...">
        </div>
    `;
    
    const container = $('#filtros-container');
    if (container.find('.text-muted').length) {
        container.empty();
    }
    
    container.append(html);
    
    // Llenar opciones de campos
    llenarOpcionesCamposFiltro(filtroId);
    
    actualizarContadores();
}

function llenarOpcionesCamposFiltro(filtroId) {
    const tabla = $('#tabla-principal').val();
    
    $.ajax({
        url: '{{ route("admin.constructor-reportes.campos") }}',
        method: 'GET',
        data: { tabla: tabla },
        success: function(response) {
            const select = $(`#${filtroId} .filtro-campo`);
            
            // Campos principales
            response.campos.forEach(campo => {
                select.append(`<option value="${campo.nombre_completo}">${campo.label}</option>`);
            });
            
            // Campos relacionados
            Object.keys(response.camposRelacionados).forEach(tablaRel => {
                const relacionados = response.camposRelacionados[tablaRel];
                const optgroup = $(`<optgroup label="${relacionados.label}"></optgroup>`);
                
                relacionados.campos.forEach(campo => {
                    optgroup.append(`<option value="${campo.nombre_completo}">${campo.label}</option>`);
                });
                
                select.append(optgroup);
            });
        }
    });
}

function removerFiltro(filtroId) {
    $(`#${filtroId}`).remove();
    
    if ($('#filtros-container .filtro-item').length === 0) {
        $('#filtros-container').html('<div class="text-muted text-center py-2"><small>No hay filtros configurados</small></div>');
    }
    
    actualizarContadores();
    actualizarSQL();
}

function limpiarTodo() {
    if (confirm('¿Estás seguro de que deseas limpiar toda la configuración?')) {
        $('#tabla-principal').val('');
        $('#campos-container').html('<div class="text-center text-muted py-3"><i class="fas fa-arrow-up"></i><p class="mb-0">Selecciona una tabla principal</p></div>');
        $('#filtros-container').html('<div class="text-muted text-center py-2"><small>No hay filtros configurados</small></div>');
        
        // Limpiar drop zones
        $('.drop-zone').each(function() {
            $(this).find('.dropped-item').remove();
            $(this).find('.drop-placeholder').show();
        });
        
        // Limpiar configuración
        $('#titulo-reporte').val('');
        $('#descripcion-reporte').val('');
        $('#limite-registros').val('1000');
        $('#formato-salida').val('pdf');
        $('#incluir-graficos').prop('checked', false);
        
        actualizarContadores();
        $('#sql-preview code').text('-- El SQL se generará automáticamente');
        
        mostrarNotificacion('Configuración limpiada', 'info');
    }
}

function copiarSQL() {
    const sql = $('#sql-preview code').text();
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(sql).then(() => {
            mostrarNotificacion('SQL copiado al portapapeles', 'success');
        }).catch(() => {
            mostrarNotificacion('Error al copiar SQL', 'danger');
        });
    } else {
        // Fallback para navegadores sin soporte para clipboard
        const textArea = document.createElement('textarea');
        textArea.value = sql;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        mostrarNotificacion('SQL copiado al portapapeles', 'success');
    }
}

function cargarConfiguracion(config) {
    console.log('Cargando configuración:', config);
    
    // Cargar tabla principal
    $('#tabla-principal').val(config.tabla_principal).trigger('change');
    
    // Cargar configuración básica
    $('#titulo-reporte').val(config.titulo || '');
    $('#descripcion-reporte').val(config.descripcion || '');
    $('#limite-registros').val(config.limite || 1000);
    $('#formato-salida').val(config.formato || 'pdf');
    $('#incluir-graficos').prop('checked', Boolean(config.incluir_graficos));
    
    // Esperar a que se carguen los campos de la tabla y luego cargar la configuración completa
    setTimeout(() => {
        cargarConfiguracionCompleta(config);
    }, 1000);
}

function cargarConfiguracionCompleta(config) {
    try {
        console.log('Cargando configuración completa:', config);
        
        // Cargar campos seleccionados
        if (config.campos && Array.isArray(config.campos)) {
            const camposDropZone = document.getElementById('campos-seleccionados');
            console.log('Campos drop zone encontrada:', camposDropZone);
            if (camposDropZone) {
                // Limpiar zona antes de cargar
                camposDropZone.innerHTML = '';
                config.campos.forEach(campo => {
                    const item = crearDroppedItemSimple(campo, 'campo');
                    camposDropZone.appendChild(item);
                });
                console.log('Campos cargados:', config.campos.length);
            }
        }
        
        // Cargar agrupaciones
        if (config.agrupaciones && Array.isArray(config.agrupaciones)) {
            const agrupacionesDropZone = document.getElementById('agrupaciones');
            console.log('Agrupaciones drop zone encontrada:', agrupacionesDropZone);
            if (agrupacionesDropZone) {
                // Limpiar zona antes de cargar
                agrupacionesDropZone.innerHTML = '';
                config.agrupaciones.forEach(campo => {
                    const item = crearDroppedItemSimple(campo, 'agrupacion');
                    agrupacionesDropZone.appendChild(item);
                });
                console.log('Agrupaciones cargadas:', config.agrupaciones.length);
            }
        }
        
        // Cargar ordenamiento
        if (config.ordenamiento && Array.isArray(config.ordenamiento)) {
            const ordenamientoDropZone = document.getElementById('ordenamiento');
            console.log('Ordenamiento drop zone encontrada:', ordenamientoDropZone);
            if (ordenamientoDropZone) {
                // Limpiar zona antes de cargar
                ordenamientoDropZone.innerHTML = '';
                config.ordenamiento.forEach(orden => {
                    const campo = orden.campo || orden;
                    const direccion = orden.direccion || 'asc';
                    const item = crearDroppedItemConOrden(campo, direccion);
                    ordenamientoDropZone.appendChild(item);
                });
                console.log('Ordenamientos cargados:', config.ordenamiento.length);
            }
        }
        
        // Cargar filtros - versión simplificada
        if (config.filtros && Array.isArray(config.filtros)) {
            console.log('Filtros encontrados:', config.filtros);
        }
        
        mostrarNotificacion('Configuración cargada correctamente', 'success');
    } catch (error) {
        console.error('Error al cargar configuración completa:', error);
        mostrarNotificacion('Error al cargar algunos elementos de la configuración', 'warning');
    }
}

function crearDroppedItemSimple(campo, tipo) {
    const item = document.createElement('div');
    item.className = 'dropped-item';
    item.setAttribute('data-campo', campo);
    item.setAttribute('data-tipo', tipo);
    item.innerHTML = `
        <span>${campo}</span>
        <button class="remove-btn ml-2" onclick="this.parentElement.remove()">&times;</button>
    `;
    return item;
}

function crearDroppedItemConOrden(campo, direccion) {
    const item = document.createElement('div');
    item.className = 'dropped-item';
    item.setAttribute('data-campo', campo);
    item.setAttribute('data-tipo', 'ordenamiento');
    item.innerHTML = `
        <span>${campo}</span>
        <select class="orden-direccion ml-2" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 0.75rem;">
            <option value="asc" ${direccion === 'asc' ? 'selected' : ''}>↑ ASC</option>
            <option value="desc" ${direccion === 'desc' ? 'selected' : ''}>↓ DESC</option>
        </select>
        <button class="remove-btn ml-2" onclick="this.parentElement.remove()">&times;</button>
    `;
    return item;
}

function mostrarNotificacion(mensaje, tipo) {
    const toast = document.createElement('div');
    toast.className = `alert alert-${tipo} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `<i class="fas fa-${tipo === 'success' ? 'check' : tipo === 'danger' ? 'times' : 'info'} mr-2"></i>${mensaje}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (document.body.contains(toast)) {
            document.body.removeChild(toast);
        }
    }, 4000);
}
</script>
@stop