@extends('layouts.admin')

@section('title', 'Detalle de Gestión')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-12 text-center">
                <h1 class="font-weight-bold text-primary">Detalle de Gestión</h1>
                <p class="text-muted">Información completa de la gestión seleccionada</p>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-body p-3">
                <!-- Información del Cliente y Préstamo -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm mb-0">
                            <div class="card-header bg-white py-2 border-primary border-left-0 border-right-0 border-top-0 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 font-weight-bold text-primary">
                                        <i class="fas fa-info-circle mr-2"></i>Información General
                                    </h6>
                                    <span class="badge badge-primary">Gestión #{{ $gestion->id }}</span>
                                </div>
                            </div>
                            <div class="card-body py-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-item d-flex justify-content-between mb-2">
                                            <div class="info-label font-weight-bold">ID de Gestión:</div>
                                            <div class="info-value">{{ $gestion->id }}</div>
                                        </div>
                                        <div class="info-item d-flex justify-content-between mb-2">
                                            <div class="info-label font-weight-bold">Préstamo ID:</div>
                                            <div class="info-value">
                                                <a href="{{ route('admin.prestamos.show', $gestion->prestamo->id) }}" class="text-primary">
                                                    {{ $gestion->prestamo->id }}
                                                </a>
                                            </div>
                                        </div>
                                        <div class="info-item d-flex justify-content-between mb-2">
                                            <div class="info-label font-weight-bold">Cliente:</div>
                                            <div class="info-value">
                                                {{ optional($gestion->prestamo->cliente->persona)->nombres ?? 'N/A' }} 
                                                {{ optional($gestion->prestamo->cliente->persona)->ape_pat ?? '' }} 
                                                {{ optional($gestion->prestamo->cliente->persona)->ape_mat ?? '' }}
                                            </div>
                                        </div>
                                        <div class="info-item d-flex justify-content-between mb-2">
                                            <div class="info-label font-weight-bold">Estado:</div>
                                            <div class="info-value">
                                                <span class="badge badge-primary">
                                                    {{ $gestion->estadoGestion->estado ?? 'N/A' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="info-item d-flex justify-content-between mb-2">
                                            <div class="info-label font-weight-bold">Registrado por:</div>
                                            <div class="info-value">
                                                @if($gestion->asesor)
                                                    <span class="badge badge-info">{{ $gestion->asesor->name }}</span>
                                                @else
                                                    <span class="badge badge-secondary">No registrado</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-item d-flex justify-content-between mb-2">
                                            <div class="info-label font-weight-bold">Fecha de Gestión:</div>
                                            <div class="info-value">
                                                {{ \Carbon\Carbon::parse($gestion->fecha)->format('d/m/Y') }}
                                            </div>
                                        </div>
                                        <div class="info-item d-flex justify-content-between mb-2">
                                            <div class="info-label font-weight-bold">Fecha de Creación:</div>
                                            <div class="info-value">
                                                {{ \Carbon\Carbon::parse($gestion->created_at)->format('d/m/Y H:i') }}
                                            </div>
                                        </div>
                                        <div class="info-item d-flex justify-content-between mb-2">
                                            <div class="info-label font-weight-bold">Última Actualización:</div>
                                            <div class="info-value">
                                                {{ \Carbon\Carbon::parse($gestion->updated_at)->format('d/m/Y H:i') }}
                                            </div>
                                        </div>
                                        @if($gestion->latitud && $gestion->longitud)
                                        <div class="info-item d-flex justify-content-between mb-2">
                                            <div class="info-label font-weight-bold">Ubicación:</div>
                                            <div class="info-value small">
                                                <i class="fas fa-map-marker-alt text-danger mr-1"></i>
                                                {{ $gestion->latitud }}, {{ $gestion->longitud }}
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="info-label font-weight-bold mb-1">Observaciones:</div>
                                        <div class="p-2 bg-light rounded border">
                                            {{ $gestion->observaciones ?? 'Sin observaciones' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Compromiso -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm mb-0">
                            <div class="card-header bg-white py-2 border-primary border-left-0 border-right-0 border-top-0 border-bottom">
                                <h6 class="mb-0 font-weight-bold text-primary">
                                    <i class="fas fa-handshake mr-2"></i>Compromiso de Pago
                                </h6>
                            </div>
                            <div class="card-body py-3">
                                @if ($gestion->compromiso)
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="info-item d-flex justify-content-between mb-2">
                                                <div class="info-label font-weight-bold">Fecha de Compromiso:</div>
                                                <div class="info-value">
                                                    {{ \Carbon\Carbon::parse($gestion->compromiso->fecha_compromiso_pago)->format('d/m/Y') }}
                                                </div>
                                            </div>
                                            <div class="info-item d-flex justify-content-between mb-2">
                                                <div class="info-label font-weight-bold">Hora:</div>
                                                <div class="info-value">{{ $gestion->compromiso->hora }}</div>
                                            </div>
                                            <div class="info-item d-flex justify-content-between mb-2">
                                                <div class="info-label font-weight-bold">Monto:</div>
                                                <div class="info-value font-weight-bold text-success">
                                                    S/ {{ number_format($gestion->compromiso->monto, 2) }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-item d-flex justify-content-between mb-2">
                                                <div class="info-label font-weight-bold">Estado:</div>
                                                <div class="info-value">
                                                    @if($gestion->compromiso->estado == 0)
                                                        <span class="badge badge-warning">Pendiente</span>
                                                    @elseif($gestion->compromiso->estado == 1)
                                                        <span class="badge badge-success">Pagado</span>
                                                    @elseif($gestion->compromiso->estado == 2)
                                                        <span class="badge badge-danger">Cancelado</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="info-item d-flex justify-content-between mb-2">
                                                <div class="info-label font-weight-bold">Fecha Registro:</div>
                                                <div class="info-value">
                                                    {{ $gestion->compromiso->fecha_registro ? \Carbon\Carbon::parse($gestion->compromiso->fecha_registro)->format('d/m/Y') : 'N/A' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @if($gestion->compromiso->comentario)
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="info-label font-weight-bold mb-1">Comentario:</div>
                                            <div class="p-2 bg-light rounded border">
                                                {{ $gestion->compromiso->comentario }}
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                @else
                                    <div class="text-center py-3">
                                        <i class="fas fa-info-circle text-muted mb-2" style="font-size: 2rem;"></i>
                                        <p class="mb-0">No hay compromiso registrado para esta gestión.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historial de Actualizaciones (si tienes una tabla de logs o auditoría) -->
                @if (method_exists($gestion, 'audits') && $gestion->audits->count())
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm mb-0">
                            <div class="card-header bg-white py-2 border-primary border-left-0 border-right-0 border-top-0 border-bottom">
                                <h6 class="mb-0 font-weight-bold text-primary">
                                    <i class="fas fa-history mr-2"></i>Historial de Cambios
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="border-0">Fecha</th>
                                                <th class="border-0">Usuario</th>
                                                <th class="border-0">Cambio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($gestion->audits as $audit)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($audit->created_at)->format('d/m/Y H:i') }}</td>
                                                    <td>{{ $audit->user ? $audit->user->name : 'Sistema' }}</td>
                                                    <td>
                                                        @foreach ($audit->getModified() as $attribute => $values)
                                                            <div class="small">
                                                                <strong>{{ $attribute }}:</strong> 
                                                                {{ $values['old'] ?? 'N/A' }} → {{ $values['new'] ?? 'N/A' }}
                                                            </div>
                                                        @endforeach
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Historial del Préstamo -->
                @if($historialPrestamo->count() > 0)
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm mb-0">
                            <div class="card-header bg-white py-2 border-primary border-left-0 border-right-0 border-top-0 border-bottom">
                                <h6 class="mb-0 font-weight-bold text-primary">
                                    <i class="fas fa-history mr-2"></i>Historial de Gestiones del Préstamo
                                    <span class="badge badge-info ml-2">{{ $historialPrestamo->count() }}</span>
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="timeline">
                                    @foreach($historialPrestamo as $gestionHistorial)
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-{{ $gestionHistorial->compromiso ? 'warning' : 'info' }}">
                                                <i class="fas fa-{{ $gestionHistorial->pago ? 'dollar-sign' : ($gestionHistorial->compromiso ? 'handshake' : 'clipboard') }}"></i>
                                            </div>
                                            <div class="timeline-content p-3 border-bottom">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h6 class="mb-1 font-weight-bold">
                                                            Gestión #{{ $gestionHistorial->id }}
                                                            @if($gestionHistorial->estadoGestion)
                                                                <span class="badge badge-primary ml-2">{{ $gestionHistorial->estadoGestion->estado }}</span>
                                                            @endif
                                                        </h6>
                                                        <small class="text-muted">
                                                            <i class="far fa-calendar mr-1"></i>{{ $gestionHistorial->fecha->format('d/m/Y H:i') }}
                                                            @if($gestionHistorial->asesor)
                                                                | <i class="fas fa-user mr-1"></i>{{ $gestionHistorial->asesor->name }}
                                                            @endif
                                                        </small>
                                                    </div>
                                                    <a href="{{ route('admin.gestiones.show', $gestionHistorial->id) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                                
                                                <p class="mb-2 text-muted">{{ Str::limit($gestionHistorial->observaciones, 150) }}</p>
                                                
                                                @if($gestionHistorial->compromiso)
                                                    <div class="alert alert-warning py-2 mb-2">
                                                        <small>
                                                            <i class="fas fa-handshake mr-1"></i>
                                                            <strong>Compromiso:</strong> S/ {{ number_format($gestionHistorial->compromiso->monto, 2) }} 
                                                            para el {{ $gestionHistorial->compromiso->fecha_compromiso_pago->format('d/m/Y') }}
                                                        </small>
                                                    </div>
                                                @endif
                                                
                                                @if($gestionHistorial->pago)
                                                    <div class="alert alert-success py-2 mb-0">
                                                        <small>
                                                            <i class="fas fa-dollar-sign mr-1"></i>
                                                            <strong>Pago registrado:</strong> S/ {{ number_format($gestionHistorial->pago->monto_pagado, 2) }}
                                                            ({{ $gestionHistorial->pago->tipo_pago_texto }})
                                                        </small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Historial del Compromiso (si aplica) -->
                @if($gestion->compromiso && $historialCompromiso->count() > 0)
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm mb-0">
                            <div class="card-header bg-white py-2 border-warning border-left-0 border-right-0 border-top-0 border-bottom">
                                <h6 class="mb-0 font-weight-bold text-warning">
                                    <i class="fas fa-search-plus mr-2"></i>Seguimiento del Compromiso #{{ $gestion->compromiso->id }}
                                    <span class="badge badge-warning ml-2">{{ $historialCompromiso->count() }}</span>
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="timeline">
                                    @foreach($historialCompromiso as $seguimiento)
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-warning">
                                                <i class="fas fa-search"></i>
                                            </div>
                                            <div class="timeline-content p-3 border-bottom">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h6 class="mb-1 font-weight-bold">
                                                            Seguimiento #{{ $seguimiento->id }}
                                                            @if($seguimiento->estadoGestion)
                                                                <span class="badge badge-warning ml-2">{{ $seguimiento->estadoGestion->estado }}</span>
                                                            @endif
                                                        </h6>
                                                        <small class="text-muted">
                                                            <i class="far fa-calendar mr-1"></i>{{ $seguimiento->fecha->format('d/m/Y H:i') }}
                                                            @if($seguimiento->asesor)
                                                                | <i class="fas fa-user mr-1"></i>{{ $seguimiento->asesor->name }}
                                                            @endif
                                                        </small>
                                                    </div>
                                                    <a href="{{ route('admin.gestiones.show', $seguimiento->id) }}" class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                                
                                                <p class="mb-2 text-muted">{{ Str::limit($seguimiento->observaciones, 150) }}</p>
                                                
                                                @if($seguimiento->pago)
                                                    <div class="alert alert-success py-2 mb-0">
                                                        <small>
                                                            <i class="fas fa-dollar-sign mr-1"></i>
                                                            <strong>Pago registrado:</strong> S/ {{ number_format($seguimiento->pago->monto_pagado, 2) }}
                                                            ({{ $seguimiento->pago->tipo_pago_texto }})
                                                        </small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Compromisos Relacionados al Préstamo -->
                @if($compromisosRelacionados->count() > 0)
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm mb-0">
                            <div class="card-header bg-white py-2 border-success border-left-0 border-right-0 border-top-0 border-bottom">
                                <h6 class="mb-0 font-weight-bold text-success">
                                    <i class="fas fa-handshake mr-2"></i>Todos los Compromisos del Préstamo
                                    <span class="badge badge-success ml-2">{{ $compromisosRelacionados->count() }}</span>
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Fecha/Hora</th>
                                                <th>Monto</th>
                                                <th>Estado</th>
                                                <th>Gestiones</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($compromisosRelacionados as $compromiso)
                                                <tr class="{{ $compromiso->id == ($gestion->compromiso->id ?? 0) ? 'table-warning' : '' }}">
                                                    <td>
                                                        #{{ $compromiso->id }}
                                                        @if($compromiso->id == ($gestion->compromiso->id ?? 0))
                                                            <span class="badge badge-warning badge-sm ml-1">ACTUAL</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ $compromiso->fecha_compromiso_pago?->format('d/m/Y') ?? 'Sin fecha' }}<br>
                                                        <small class="text-muted">{{ $compromiso->hora ? \Carbon\Carbon::parse($compromiso->hora)->format('H:i') : 'Sin hora' }}</small>
                                                    </td>
                                                    <td>S/ {{ number_format($compromiso->monto, 2) }}</td>
                                                    <td>
                                                        @php
                                                            $badgeClass = match($compromiso->estado) {
                                                                0 => 'badge-warning',
                                                                1 => 'badge-success', 
                                                                2 => 'badge-danger',
                                                                default => 'badge-secondary'
                                                            };
                                                        @endphp
                                                        <span class="badge {{ $badgeClass }}">{{ $compromiso->estado_texto }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info">{{ $compromiso->gestiones->count() }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="{{ route('admin.gestiones.create', ['compromiso_id' => $compromiso->id]) }}" 
                                                               class="btn btn-outline-warning btn-sm" title="Crear seguimiento">
                                                                <i class="fas fa-search-plus"></i>
                                                            </a>
                                                            <a href="{{ route('admin.compromisos.edit', $compromiso->id) }}" 
                                                               class="btn btn-outline-primary btn-sm" title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Acciones Rápidas -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm mb-0">
                            <div class="card-header bg-white py-2 border-success border-left-0 border-right-0 border-top-0 border-bottom">
                                <h6 class="mb-0 font-weight-bold text-success">
                                    <i class="fas fa-plus-circle mr-2"></i>Acciones Rápidas
                                </h6>
                            </div>
                            <div class="card-body py-3">
                                <div class="row">
                                    <!-- Crear Nuevo Compromiso -->
                                    <div class="col-lg-4 mb-3">
                                        <div class="quick-action-card">
                                            <div class="text-center">
                                                <div class="quick-action-icon bg-warning text-white mb-2">
                                                    <i class="fas fa-handshake"></i>
                                                </div>
                                                <h6 class="font-weight-bold">Nuevo Compromiso</h6>
                                                <p class="text-muted small mb-3">Crear un compromiso de pago para este préstamo</p>
                                                <a href="{{ route('admin.compromisos.create', ['prestamo_id' => $gestion->prestamo_id]) }}" 
                                                   class="btn btn-warning btn-sm btn-block">
                                                    <i class="fas fa-plus mr-1"></i> Crear Compromiso
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Crear Gestión de Seguimiento -->
                                    <div class="col-lg-4 mb-3">
                                        <div class="quick-action-card">
                                            <div class="text-center">
                                                <div class="quick-action-icon bg-info text-white mb-2">
                                                    <i class="fas fa-search-plus"></i>
                                                </div>
                                                <h6 class="font-weight-bold">Gestión de Seguimiento</h6>
                                                <p class="text-muted small mb-3">Crear seguimiento para este préstamo</p>
                                                <a href="{{ route('admin.gestiones.create', ['prestamo_id' => $gestion->prestamo_id]) }}" 
                                                   class="btn btn-info btn-sm btn-block">
                                                    <i class="fas fa-plus mr-1"></i> Nueva Gestión
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Registrar Pago -->
                                    <div class="col-lg-4 mb-3">
                                        <div class="quick-action-card">
                                            <div class="text-center">
                                                <div class="quick-action-icon bg-success text-white mb-2">
                                                    <i class="fas fa-dollar-sign"></i>
                                                </div>
                                                <h6 class="font-weight-bold">Registrar Pago</h6>
                                                <p class="text-muted small mb-3">Registrar un pago para este préstamo</p>
                                                <a href="{{ route('admin.registrarpago.create', $gestion->prestamo_id) }}" 
                                                   class="btn btn-success btn-sm btn-block">
                                                    <i class="fas fa-plus mr-1"></i> Registrar Pago
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if($gestion->compromiso)
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="alert alert-info border-left-info mb-0">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-info-circle text-info mr-3"></i>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 font-weight-bold">Compromiso Activo</h6>
                                                    <small>Esta gestión tiene un compromiso asociado. Puedes crear seguimientos específicos para este compromiso.</small>
                                                </div>
                                                <div>
                                                    <a href="{{ route('admin.gestiones.create', ['compromiso_id' => $gestion->compromiso_id]) }}" 
                                                       class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-search-plus mr-1"></i> Seguimiento del Compromiso
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.prestamos.show', $gestion->prestamo_id) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Volver al Préstamo
                            </a>
                            <div>
                                <a href="{{ route('admin.gestiones.edit', $gestion->id) }}" class="btn btn-primary mr-2">
                                    <i class="fas fa-edit mr-1"></i> Editar
                                </a>
                                <form action="{{ route('admin.gestiones.destroy', $gestion->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar esta gestión?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash-alt mr-1"></i> Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('css')
        <style>
            /* Estilos base - modo claro siempre */
            body {
                background-color: #f8f9fa !important;
                color: #212529 !important;
            }
            
            .card {
                transition: all 0.2s ease;
                border-radius: 0.25rem;
                background-color: #ffffff !important;
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
            }
            
            .card:hover {
                box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1) !important;
            }
            
            .card-header {
                background-color: #ffffff !important;
                border-width: 3px !important;
                padding: 0.75rem 1.25rem !important;
            }
            
            .border-primary {
                border-color: #3c8dbc !important;
            }
            
            .text-primary {
                color: #3c8dbc !important;
            }
            
            .btn-primary {
                background-color: #3c8dbc !important;
                border-color: #367fa9 !important;
            }
            
            .btn-primary:hover {
                background-color: #367fa9 !important;
                border-color: #2e6da4 !important;
            }
            
            .badge-primary {
                background-color: #3c8dbc !important;
            }
            
            .badge-info {
                background-color: #17a2b8 !important;
            }
            
            .badge-success {
                background-color: #28a745 !important;
            }
            
            .badge-warning {
                background-color: #ffc107 !important;
                color: #212529 !important;
            }
            
            .badge-danger {
                background-color: #dc3545 !important;
            }
            
            .text-success {
                color: #28a745 !important;
            }
            
            .bg-light {
                background-color: #f8f9fa !important;
            }
            
            .table {
                color: #333 !important;
            }
            
            .table thead th {
                background-color: #f8f9fa !important;
                color: #333 !important;
                font-weight: 600 !important;
                border-top: none !important;
                border-bottom: 2px solid #dee2e6 !important;
            }
            
            .info-item {
                padding: 0.25rem 0;
                border-bottom: 1px dotted #eee;
            }
            
            .info-label {
                color: #555 !important;
                font-size: 0.875rem;
            }
            
            .info-value {
                text-align: right;
                font-size: 0.875rem;
            }

            /* Estilos para Timeline */
            .timeline {
                position: relative;
                padding-left: 0;
            }

            .timeline::before {
                content: '';
                position: absolute;
                left: 20px;
                top: 0;
                bottom: 0;
                width: 2px;
                background: linear-gradient(to bottom, #dee2e6, #f8f9fa);
            }

            .timeline-item {
                position: relative;
                margin-bottom: 0;
                padding-left: 50px;
            }

            .timeline-marker {
                position: absolute;
                left: 10px;
                top: 20px;
                width: 22px;
                height: 22px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 10px;
                border: 2px solid #fff;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                z-index: 2;
            }

            .timeline-marker.bg-info {
                background-color: #17a2b8;
                color: white;
            }

            .timeline-marker.bg-warning {
                background-color: #ffc107;
                color: #212529;
            }

            .timeline-marker.bg-success {
                background-color: #28a745;
                color: white;
            }

            .timeline-content {
                background: #fff;
                border-left: 3px solid #e9ecef;
                margin-bottom: 1rem;
                transition: all 0.3s ease;
                border-radius: 0 8px 8px 0;
            }

            .timeline-content:hover {
                border-left-color: #007bff;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }

            .timeline-item:last-child .timeline-content {
                margin-bottom: 0;
            }

            /* Colores específicos para el timeline */
            .border-warning {
                border-color: #ffc107 !important;
            }

            .border-success {
                border-color: #28a745 !important;
            }

            .text-warning {
                color: #856404 !important;
            }

            /* Mejoras visuales para alertas en timeline */
            .timeline-content .alert {
                margin-bottom: 0.5rem;
                padding: 0.5rem 0.75rem;
                border-radius: 6px;
                border: none;
                font-size: 0.875rem;
            }

            .alert-warning {
                background-color: #fff3cd;
                color: #856404;
                border-left: 3px solid #ffc107;
            }

            .alert-success {
                background-color: #d4edda;
                color: #155724;
                border-left: 3px solid #28a745;
            }

            /* Responsivo para timeline */
            @media (max-width: 767.98px) {
                .timeline::before {
                    left: 15px;
                }

                .timeline-item {
                    padding-left: 40px;
                }

                .timeline-marker {
                    left: 5px;
                    width: 20px;
                    height: 20px;
                    font-size: 9px;
                }
            }

            /* Estilos para Acciones Rápidas */
            .quick-action-card {
                background: #fff;
                border: 1px solid #e9ecef;
                border-radius: 10px;
                padding: 1.5rem;
                height: 100%;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            .quick-action-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 4px;
                background: linear-gradient(90deg, #007bff, #28a745, #ffc107);
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .quick-action-card:hover {
                border-color: #007bff;
                box-shadow: 0 4px 15px rgba(0,123,255,0.1);
                transform: translateY(-2px);
            }

            .quick-action-card:hover::before {
                transform: translateX(0);
            }

            .quick-action-icon {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto;
                font-size: 20px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                transition: all 0.3s ease;
            }

            .quick-action-card:hover .quick-action-icon {
                transform: scale(1.1);
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            }

            .border-left-info {
                border-left: 4px solid #17a2b8 !important;
            }
            
            /* Modo responsivo general */
            @media (max-width: 767.98px) {
                .info-item {
                    flex-direction: column;
                    align-items: start !important;
                    border-bottom: none;
                    margin-bottom: 0.75rem !important;
                }
                
                .info-value {
                    text-align: left;
                    width: 100%;
                    margin-top: 0.25rem;
                }
                
                .card-body {
                    padding: 0.75rem !important;
                }
                
                .container-fluid {
                    padding-left: 0.5rem !important;
                    padding-right: 0.5rem !important;
                }
                
                .btn {
                    width: 100%;
                    margin-bottom: 0.5rem;
                    margin-right: 0 !important;
                }
                
                .d-flex.justify-content-between {
                    flex-direction: column;
                }
                
                .d-flex.justify-content-between > div {
                    width: 100%;
                }
                
                form.d-inline {
                    display: block !important;
                    width: 100%;
                }
            }
        </style>
    @endpush
@stop