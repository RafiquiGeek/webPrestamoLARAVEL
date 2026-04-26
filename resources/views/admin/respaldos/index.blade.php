@extends('layouts.admin')
@section('title', 'Respaldos del Sistema')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-database mr-2"></i>Respaldos del Sistema</h1>
       <ol class="breadcrumb float-sm-right mb-0">
           <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
           <li class="breadcrumb-item active">Respaldos</li>
       </ol>
   </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Información del Sistema -->
    <div class="account-card mb-4">
        <div class="card-header">
            <h3><i class="fas fa-info-circle me-2"></i>Información del Sistema</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="info-card">
                        <div class="info-label">Base de Datos</div>
                        <div class="info-value">{{ $info['base_datos'] }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-card">
                        <div class="info-label">Servidor</div>
                        <div class="info-value">{{ $info['servidor'] }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-card">
                        <div class="info-label">Espacio Usado</div>
                        <div class="info-value">{{ $info['espacio_usado'] }}</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="info-card">
                        <div class="info-label">Total Respaldos</div>
                        <div class="info-value">{{ $info['total_respaldos'] }}</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="info-card">
                        <div class="info-label">Compresión ZIP</div>
                        <div class="info-value small">{{ $info['zip_disponible'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Crear Nuevo Respaldo -->
    <div class="account-card mb-4">
        <div class="card-header">
            <h3><i class="fas fa-plus-circle me-2"></i>Crear Nuevo Respaldo</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.respaldos.crear') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fas fa-cog me-1 text-muted"></i>Tipo de Respaldo
                        </label>
                        <select name="tipo" class="form-select form-select-sm" required>
                            <option value="completo">Respaldo Completo (Estructura + Datos)</option>
                            <option value="solo_datos">Solo Datos</option>
                            <option value="solo_estructura">Solo Estructura</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">
                            <i class="fas fa-folder me-1 text-muted"></i>Opciones
                        </label>
                        <div class="form-check">
                            <input type="checkbox" name="incluir_archivos" value="1" class="form-check-input" id="incluir_archivos">
                            <label class="form-check-label" for="incluir_archivos">
                                Incluir archivos (PDFs, imágenes, documentos)
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-download me-1"></i>Crear Respaldo
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Respaldos Programados -->
    <div class="account-card mb-4">
        <div class="card-header">
            <h3><i class="fas fa-clock me-2"></i>Respaldos Automáticos</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.respaldos.programar') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Frecuencia</label>
                        <select name="frecuencia" class="form-select form-select-sm">
                            <option value="diario">Diario</option>
                            <option value="semanal">Semanal</option>
                            <option value="mensual">Mensual</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hora</label>
                        <input type="time" name="hora" class="form-control form-control-sm" value="02:00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mantener Respaldos</label>
                        <input type="number" name="mantener_respaldos" class="form-control form-control-sm" value="7" min="1" max="30">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-save me-1"></i>Configurar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Restaurar Respaldo -->
    <div class="account-card mb-4">
        <div class="card-header">
            <h3><i class="fas fa-upload me-2"></i>Restaurar Respaldo</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>¡Atención!</strong> La restauración reemplazará completamente los datos actuales. 
                Asegúrate de crear un respaldo antes de proceder.
            </div>
            <form action="{{ route('admin.respaldos.restaurar') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Seleccionar Archivo de Respaldo</label>
                        <input type="file" name="archivo_respaldo" class="form-control form-control-sm" 
                               accept=".zip,.sql" required>
                        <small class="text-muted">Archivos permitidos: .zip, .sql</small>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="confirmar_restauracion" value="1" 
                                   class="form-check-input" id="confirmar_restauracion" required>
                            <label class="form-check-label" for="confirmar_restauracion">
                                <strong>Confirmo que entiendo que esta acción reemplazará todos los datos actuales</strong>
                            </label>
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm mt-2" 
                                onclick="return confirm('¿Está seguro de restaurar la base de datos? Esta acción no se puede deshacer.')">
                            <i class="fas fa-upload me-1"></i>Restaurar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Respaldos -->
    <div class="account-card">
        <div class="card-header">
            <h3><i class="fas fa-list me-2"></i>Respaldos Disponibles</h3>
        </div>
        <div class="card-body p-0">
            @if($respaldos->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Archivo</th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Tamaño</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($respaldos as $respaldo)
                                <tr>
                                    <td>
                                        <div class="info-label">{{ $respaldo['nombre'] }}</div>
                                        <small class="text-muted">
                                            @if($respaldo['formato'] ?? 'ZIP' === 'ZIP')
                                                <i class="fas fa-file-archive me-1"></i>Archivo ZIP
                                            @else
                                                <i class="fas fa-database me-1"></i>Archivo SQL
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        <div class="info-value small">
                                            {{ $respaldo['fecha']->format('d/m/Y') }}
                                        </div>
                                        <small class="text-muted">
                                            {{ $respaldo['fecha']->format('H:i:s') }}
                                        </small>
                                    </td>
                                    <td>
                                        @php
                                            $tipoConfig = [
                                                'Completo' => ['class' => 'success', 'icon' => 'fa-database'],
                                                'Solo Datos' => ['class' => 'info', 'icon' => 'fa-table'],
                                                'Solo Estructura' => ['class' => 'warning', 'icon' => 'fa-project-diagram']
                                            ];
                                            $config = $tipoConfig[$respaldo['tipo']] ?? ['class' => 'secondary', 'icon' => 'fa-question'];
                                        @endphp
                                        <span class="badge bg-{{ $config['class'] }}">
                                            <i class="fas {{ $config['icon'] }} me-1"></i>
                                            {{ $respaldo['tipo'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="info-value">{{ $respaldo['tamaño'] }}</div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.respaldos.descargar', $respaldo['nombre']) }}" 
                                               class="btn btn-outline-primary btn-sm"
                                               title="Descargar">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-sm"
                                                    onclick="eliminarRespaldo('{{ $respaldo['nombre'] }}')"
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Sin respaldos -->
                <div class="text-center py-5">
                    <i class="fas fa-database fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">Sin Respaldos</h4>
                    <p class="text-muted">No se encontraron respaldos en el sistema.</p>
                    <p class="text-muted">Crea tu primer respaldo para proteger los datos del sistema.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal de Confirmación para Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de eliminar este respaldo?</p>
                <p><strong id="nombreArchivo"></strong></p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    Esta acción no se puede deshacer.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formEliminar" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Eliminar
                    </button>
                </form>
            </div>
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
    padding: 0.75rem;
    border-radius: 8px;
    margin-bottom: 0.75rem;
    width: 100%;
}

.info-card .info-label {
    font-size: 0.75rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    font-weight: 600;
}

.info-card .info-value {
    font-size: 1rem;
    font-weight: 600;
    color: #1a1a1a;
}

.btn-primary {
    background-color: #005566;
    border-color: #005566;
}

.btn-primary:hover {
    background-color: #004455;
    border-color: #004455;
}

.btn-outline-primary {
    border-color: #005566;
    color: #005566;
}

.btn-outline-primary:hover {
    background-color: #005566;
    color: #ffffff;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.table th {
    font-weight: 600;
    color: #495057;
    font-size: 0.875rem;
}

.badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.35em 0.6em;
}

.btn-group .btn {
    padding: 0.375rem 0.75rem;
}
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
function eliminarRespaldo(nombreArchivo) {
    $('#nombreArchivo').text(nombreArchivo);
    $('#formEliminar').attr('action', '{{ route("admin.respaldos.eliminar", "") }}/' + nombreArchivo);
    
    var modal = new bootstrap.Modal(document.getElementById('modalEliminar'));
    modal.show();
}

$(document).ready(function() {
    // Confirmación adicional para restauración
    $('form[action*="restaurar"]').on('submit', function(e) {
        if (!$('#confirmar_restauracion').is(':checked')) {
            e.preventDefault();
            alert('Debe confirmar que entiende las consecuencias de la restauración.');
            return false;
        }
        
        return confirm('ÚLTIMA ADVERTENCIA: ¿Está completamente seguro de restaurar la base de datos? Todos los datos actuales se perderán permanentemente.');
    });
    
    // Mostrar/ocultar opciones avanzadas
    $('#incluir_archivos').on('change', function() {
        if ($(this).is(':checked')) {
            // Mostrar advertencia sobre el tamaño del respaldo
            if (!$(this).data('warned')) {
                alert('Incluir archivos incrementará significativamente el tamaño del respaldo.');
                $(this).data('warned', true);
            }
        }
    });
});
</script>
@stop