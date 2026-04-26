@extends('layouts.admin')

@section('title', 'Historial de Operación #' . $operacion->id)

@section('content')
<div class="container-fluid">
    <!-- Header Mejorado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1">
                                <i class="fas fa-history me-2"></i>
                                Historial de Operación #{{ $operacion->id }}
                            </h3>
                            <p class="mb-0 opacity-75">
                                <i class="fas fa-user me-1"></i>
                                {{ $operacion->cliente->persona->nombres }} {{ $operacion->cliente->persona->ape_pat }}
                                <span class="mx-2">|</span>
                                <i class="fas fa-file-invoice me-1"></i>
                                Préstamo #{{ $operacion->prestamo->id }}
                            </p>
                        </div>
                        <div class="text-end">
                            <div class="display-6 mb-1">S/ {{ number_format($operacion->abono, 2) }}</div>
                            <span class="badge {{ $operacion->estado == 'completado' ? 'bg-success' : ($operacion->estado == 'anulado' ? 'bg-danger' : 'bg-warning') }} px-3 py-2">
                                {{ ucfirst($operacion->estado) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Columna Izquierda: Información y Resumen -->
        <div class="col-lg-4">
            <!-- Información Básica -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>Información Básica</h5>
                </div>
                <div class="card-body">
                    <div class="info-item mb-3">
                        <small class="text-muted d-block">ID Operación</small>
                        <strong class="h6">#{{ $operacion->id }}</strong>
                    </div>
                    <div class="info-item mb-3">
                        <small class="text-muted d-block">Tipo de Operación</small>
                        <span class="badge bg-info">{{ $operacion->tipo_operacion ?? 'General' }}</span>
                    </div>
                    <div class="info-item mb-3">
                        <small class="text-muted d-block">Método de Pago</small>
                        <strong>{{ $operacion->metodoDePago->metodo_pago ?? 'N/A' }}</strong>
                    </div>
                    <div class="info-item mb-3">
                        <small class="text-muted d-block">Código/Nro Operación</small>
                        <code class="bg-light px-2 py-1 rounded">{{ $operacion->codigo ?? 'Sin código' }}</code>
                    </div>
                    <div class="info-item mb-3">
                        <small class="text-muted d-block">Fecha de Operación</small>
                        <strong>{{ $operacion->fecha ? $operacion->fecha->format('d/m/Y H:i') : 'N/A' }}</strong>
                    </div>
                    <div class="info-item">
                        <small class="text-muted d-block">Registrado por</small>
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                <i class="fas fa-user"></i>
                            </div>
                            <strong>{{ $operacion->user->codigo ?? 'N/A' }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen de Cambios -->
            <div class="card shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2 text-success"></i>Resumen de Cambios</h5>
                </div>
                <div class="card-body">
                    @php
                        $totalEdiciones = $operacion->operacionesRelacionadas ? $operacion->operacionesRelacionadas->count() : 0;
                        $fueEditado = $operacion->editado_en ? true : false;
                        $fueAnulado = $operacion->anulado_en ? true : false;
                    @endphp

                    <div class="stat-item d-flex justify-content-between align-items-center mb-3 p-3 rounded" style="background: #f8f9fa;">
                        <div>
                            <i class="fas fa-edit text-warning me-2"></i>
                            <span class="text-muted">Ediciones</span>
                        </div>
                        <strong class="h5 mb-0">{{ $fueEditado ? '1' : '0' }}</strong>
                    </div>

                    <div class="stat-item d-flex justify-content-between align-items-center mb-3 p-3 rounded" style="background: #f8f9fa;">
                        <div>
                            <i class="fas fa-code-branch text-info me-2"></i>
                            <span class="text-muted">Operaciones Hijas</span>
                        </div>
                        <strong class="h5 mb-0">{{ $totalEdiciones }}</strong>
                    </div>

                    <div class="stat-item d-flex justify-content-between align-items-center p-3 rounded" style="background: {{ $fueAnulado ? '#fee' : '#f8f9fa' }};">
                        <div>
                            <i class="fas fa-ban text-danger me-2"></i>
                            <span class="text-muted">Anulaciones</span>
                        </div>
                        <strong class="h5 mb-0">{{ $fueAnulado ? '1' : '0' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Timeline de Eventos -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="fas fa-stream me-2 text-info"></i>Línea de Tiempo</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <!-- Evento: Creación -->
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary">
                                <i class="fas fa-plus text-white"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <h6 class="mb-1">
                                        <i class="fas fa-star text-primary me-2"></i>
                                        Operación Creada
                                    </h6>
                                    <small class="text-muted">
                                        <i class="far fa-clock me-1"></i>
                                        {{ $operacion->created_at ? $operacion->created_at->format('d/m/Y H:i:s') : 'N/A' }}
                                    </small>
                                </div>
                                <div class="timeline-body mt-2">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="detail-box p-3 rounded" style="background: #e3f2fd;">
                                                <small class="text-muted d-block">Monto Inicial</small>
                                                <strong class="h5 text-primary">S/ {{ number_format($operacion->abono, 2) }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="detail-box p-3 rounded" style="background: #f3e5f5;">
                                                <small class="text-muted d-block">Registrado por</small>
                                                <strong>{{ $operacion->user->codigo ?? 'N/A' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Evento: Edición -->
                        @if($operacion->editado_en)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning">
                                <i class="fas fa-edit text-white"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <h6 class="mb-1">
                                        <i class="fas fa-pen text-warning me-2"></i>
                                        Operación Editada
                                    </h6>
                                    <small class="text-muted">
                                        <i class="far fa-clock me-1"></i>
                                        {{ $operacion->editado_en ? \Carbon\Carbon::parse($operacion->editado_en)->format('d/m/Y H:i:s') : 'N/A' }}
                                    </small>
                                </div>
                                <div class="timeline-body mt-3">
                                    @if($operacion->justificacion_edicion)
                                    <div class="alert alert-warning mb-3">
                                        <strong><i class="fas fa-comment-dots me-2"></i>Justificación:</strong>
                                        <p class="mb-0 mt-1">{{ $operacion->justificacion_edicion }}</p>
                                    </div>
                                    @endif

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="detail-box p-3 rounded" style="background: #fff3cd;">
                                                <small class="text-muted d-block">Editado por</small>
                                                <div class="d-flex align-items-center mt-1">
                                                    <div class="avatar-sm bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <strong>{{ $operacion->editadoPor->codigo ?? 'N/A' }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="detail-box p-3 rounded" style="background: #fff3cd;">
                                                <small class="text-muted d-block">Operaciones Generadas</small>
                                                <strong class="h5">{{ $operacion->operacionesRelacionadas ? $operacion->operacionesRelacionadas->count() : 0 }}</strong>
                                            </div>
                                        </div>
                                    </div>

                                    @if($operacion->operacionesRelacionadas && $operacion->operacionesRelacionadas->count() > 0)
                                    <div class="mt-3">
                                        <h6 class="mb-2">
                                            <i class="fas fa-sitemap me-2 text-info"></i>
                                            Operaciones Hijas Generadas
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Tipo</th>
                                                        <th>Monto</th>
                                                        <th>Estado</th>
                                                        <th>Fecha</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($operacion->operacionesRelacionadas as $relacionada)
                                                    <tr>
                                                        <td><code>#{{ $relacionada->id }}</code></td>
                                                        <td>{{ $relacionada->tipo_operacion }}</td>
                                                        <td><strong>S/ {{ number_format($relacionada->abono, 2) }}</strong></td>
                                                        <td>
                                                            <span class="badge badge-sm {{ $relacionada->estado == 'completado' ? 'bg-success' : ($relacionada->estado == 'anulado' ? 'bg-danger' : 'bg-secondary') }}">
                                                                {{ $relacionada->estado }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $relacionada->created_at->format('d/m/Y H:i') }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Evento: Anulación -->
                        @if($operacion->anulado_en)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-danger">
                                <i class="fas fa-ban text-white"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <h6 class="mb-1">
                                        <i class="fas fa-times-circle text-danger me-2"></i>
                                        Operación Anulada
                                    </h6>
                                    <small class="text-muted">
                                        <i class="far fa-clock me-1"></i>
                                        {{ $operacion->anulado_en ? \Carbon\Carbon::parse($operacion->anulado_en)->format('d/m/Y H:i:s') : 'N/A' }}
                                    </small>
                                </div>
                                <div class="timeline-body mt-3">
                                    @if($operacion->justificacion_anulacion)
                                    <div class="alert alert-danger mb-3">
                                        <strong><i class="fas fa-exclamation-triangle me-2"></i>Justificación:</strong>
                                        <p class="mb-0 mt-1">{{ $operacion->justificacion_anulacion }}</p>
                                    </div>
                                    @endif

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="detail-box p-3 rounded" style="background: #ffebee;">
                                                <small class="text-muted d-block">Anulado por</small>
                                                <div class="d-flex align-items-center mt-1">
                                                    <div class="avatar-sm bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <strong>{{ $operacion->anuladoPor->name ?? 'N/A' }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Estado Final -->
                        <div class="timeline-item">
                            <div class="timeline-marker {{ $operacion->estado == 'completado' ? 'bg-success' : ($operacion->estado == 'anulado' ? 'bg-secondary' : 'bg-info') }}">
                                <i class="fas fa-flag-checkered text-white"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <h6 class="mb-1">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Estado Actual
                                    </h6>
                                    <small class="text-muted">
                                        <i class="far fa-clock me-1"></i>
                                        Actualizado: {{ $operacion->updated_at ? $operacion->updated_at->format('d/m/Y H:i:s') : 'N/A' }}
                                    </small>
                                </div>
                                <div class="timeline-body mt-2">
                                    <div style="border-radius:10px;padding: 10px;" class="alert-{{ $operacion->estado == 'completado' ? 'success' : ($operacion->estado == 'anulado' ? 'danger' : 'info') }} mb-0">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <strong>Estado:</strong>
                                                <span class="badge text-white bg-{{ $operacion->estado == 'completado' ? 'success' : ($operacion->estado == 'anulado' ? 'danger' : 'info') }} ms-2">
                                                    {{ ucfirst($operacion->estado) }}
                                                </span>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted d-block">Monto Final</small>
                                                <strong class="h4 mb-0">S/ {{ number_format($operacion->abono, 2) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje si no hay cambios -->
                        @if(!$operacion->editado_en && !$operacion->anulado_en)
                        <div class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-check-circle text-success me-2" style="font-size: 48px;"></i>
                                <p class="mt-3 mb-0">
                                    <strong>No hay modificaciones registradas</strong><br>
                                    <small>Esta operación se mantiene en su estado original</small>
                                </p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de Acción Mejorados -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('admin.prestamos.show', $operacion->prestamo_id) }}"
                           class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver al Préstamo
                        </a>

                        <div>
                            @if($operacion->estado != 'anulado')
                            <!--a href="{{ route('admin.operaciones.editar', $operacion->id) }}"
                               class="btn btn-warning me-2">
                                <i class="fas fa-edit me-2"></i>Editar Operación
                            </a-->
                            <a href="{{ route('admin.operaciones.anular', $operacion->id) }}"
                               class="btn btn-danger"
                               onclick="return confirm('¿Está seguro de que desea anular esta operación?')">
                                <i class="fas fa-ban me-2"></i>Anular Operación
                            </a>
                            @else
                            <span class="badge bg-secondary px-3 py-2">
                                <i class="fas fa-lock me-2"></i>Operación Anulada - No se puede modificar
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Timeline Styles */
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 30px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
}

.timeline-item {
    position: relative;
    padding-left: 70px;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: 15px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    z-index: 1;
}

.timeline-content {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border-left: 3px solid #667eea;
    transition: all 0.3s ease;
}

.timeline-content:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    transform: translateX(5px);
}

.timeline-header h6 {
    font-weight: 600;
    color: #2c3e50;
}

.detail-box {
    transition: all 0.3s ease;
}

.detail-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.info-item {
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.stat-item {
    transition: all 0.3s ease;
}

.stat-item:hover {
    transform: translateX(5px);
    background: #e8eaf6 !important;
}

/* Badges personalizados */
.badge-sm {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Avatar styles */
.avatar-sm {
    font-size: 12px;
}
</style>
@endsection
