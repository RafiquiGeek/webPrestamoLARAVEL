@extends('layouts.admin')

@section('title', 'Detalle de Etiqueta')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-12 text-center">
                <h1 class="font-weight-bold text-info">
                    <i class="fas fa-tag mr-2"></i>Detalle de la Etiqueta
                </h1>
                <p class="text-muted">Información completa y asignaciones de la etiqueta</p>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <!-- COLUMNA IZQUIERDA: Información Principal -->
            <div class="col-lg-8">
                <!-- Información de la Etiqueta -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header" style="background: linear-gradient(135deg, {{ $etiqueta->color }} 0%, {{ $etiqueta->color }}99 100%); color: white;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-tag mr-2"></i>{{ $etiqueta->etiqueta }}
                            </h5>
                            <span class="badge badge-{{ $etiqueta->estado ? 'success' : 'danger' }} badge-lg">
                                {{ $etiqueta->estado ? 'Activa' : 'Inactiva' }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item d-flex justify-content-between mb-3">
                                    <div class="info-label font-weight-bold">ID:</div>
                                    <div class="info-value">#{{ $etiqueta->id }}</div>
                                </div>
                                <div class="info-item d-flex justify-content-between mb-3">
                                    <div class="info-label font-weight-bold">Nombre:</div>
                                    <div class="info-value">{{ $etiqueta->etiqueta }}</div>
                                </div>
                                <div class="info-item d-flex justify-content-between mb-3">
                                    <div class="info-label font-weight-bold">Color:</div>
                                    <div class="info-value">
                                        <code>{{ $etiqueta->color }}</code>
                                        <span class="badge px-3 py-2 ml-2" style="background-color: {{ $etiqueta->color }}; color: white;">
                                            Vista Previa
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item d-flex justify-content-between mb-3">
                                    <div class="info-label font-weight-bold">Estado:</div>
                                    <div class="info-value">
                                        @if($etiqueta->estado)
                                            <span class="badge badge-success">
                                                <i class="fas fa-check mr-1"></i>Activa
                                            </span>
                                        @else
                                            <span class="badge badge-danger">
                                                <i class="fas fa-times mr-1"></i>Inactiva
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="info-item d-flex justify-content-between mb-3">
                                    <div class="info-label font-weight-bold">Fecha Creación:</div>
                                    <div class="info-value">{{ $etiqueta->created_at->format('d/m/Y H:i') }}</div>
                                </div>
                                <div class="info-item d-flex justify-content-between mb-3">
                                    <div class="info-label font-weight-bold">Última Actualización:</div>
                                    <div class="info-value">{{ $etiqueta->updated_at->format('d/m/Y H:i') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-info">
                                <i class="fas fa-users"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Asignaciones</span>
                                <span class="info-box-number">{{ $etiqueta->etiquetasCliente->count() }}</span>
                                <span class="progress-description">
                                    Clientes que tienen esta etiqueta
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-success">
                                <i class="fas fa-list"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Préstamos Etiquetados</span>
                                <span class="info-box-number">{{ $etiqueta->etiquetasCliente->whereNotNull('prestamo_id')->count() }}</span>
                                <span class="progress-description">
                                    Préstamos con esta etiqueta
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Asignaciones -->
                @if($etiqueta->etiquetasCliente->count() > 0)
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-gradient-secondary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list mr-2"></i>Préstamos con esta Etiqueta
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Préstamo</th>
                                        <th>Estado Préstamo</th>
                                        <th>Observación</th>
                                        <th>Fecha Asignación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($etiqueta->etiquetasCliente as $asignacion)
                                    <tr>
                                        <td class="align-middle">
                                            <div class="d-flex flex-column">
                                                <span class="font-weight-bold">
                                                    {{ $asignacion->cliente->persona->nombres }}
                                                </span>
                                                <small class="text-muted">
                                                    {{ $asignacion->cliente->persona->ape_pat }} 
                                                    {{ $asignacion->cliente->persona->ape_mat }}
                                                </small>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            @if($asignacion->prestamo)
                                                <a href="{{ route('admin.prestamos.show', $asignacion->prestamo_id) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    #{{ $asignacion->prestamo_id }}
                                                </a>
                                            @else
                                                <span class="text-muted">Sin préstamo</span>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            @if($asignacion->prestamo)
                                                @php
                                                    $estado = $asignacion->prestamo->estado;
                                                    $badgeClass = match($estado) {
                                                        'Vigente' => 'success',
                                                        'Por Desembolsar' => 'warning',
                                                        'Cancelado' => 'info',
                                                        'Vencido' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                @endphp
                                                <span class="badge badge-{{ $badgeClass }}">
                                                    {{ $estado }}
                                                </span>
                                            @else
                                                <span class="badge badge-secondary">N/A</span>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            @if($asignacion->observacion)
                                                <span class="text-sm">{{ $asignacion->observacion }}</span>
                                            @else
                                                <span class="text-muted">Sin observación</span>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            <small>{{ $asignacion->created_at->format('d/m/Y') }}</small>
                                        </td>
                                        <td class="align-middle">
                                            <div class="btn-group">
                                                <a href="{{ route('admin.clientes.show', $asignacion->cliente_id) }}" 
                                                   class="btn btn-sm btn-outline-info" 
                                                   title="Ver cliente">
                                                    <i class="fas fa-user"></i>
                                                </a>
                                                @if($asignacion->prestamo)
                                                <a href="{{ route('admin.prestamos.show', $asignacion->prestamo_id) }}" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="Ver préstamo">
                                                    <i class="fas fa-list-alt"></i>
                                                </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @else
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Sin Asignaciones</h5>
                        <p class="text-muted">Esta etiqueta no ha sido asignada a ningún cliente aún.</p>
                    </div>
                </div>
                @endif
            </div>

            <!-- COLUMNA DERECHA: Acciones -->
            <div class="col-lg-4">
                <!-- Estado y Acciones -->
                <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                    <div class="card-header bg-gradient-dark text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-cogs mr-2"></i>Acciones
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Vista Previa Grande -->
                        <div class="text-center mb-4">
                            <span class="badge p-3" style="background-color: {{ $etiqueta->color }}; color: white; font-size: 18px;">
                                {{ $etiqueta->etiqueta }}
                            </span>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="actions-section">
                            <a href="{{ route('admin.etiquetas.edit', $etiqueta->id) }}" 
                               class="btn btn-warning btn-block mb-2">
                                <i class="fas fa-edit mr-2"></i>Editar Etiqueta
                            </a>

                            <a href="{{ route('admin.etiquetas.index') }}" 
                               class="btn btn-outline-secondary btn-block mb-2">
                                <i class="fas fa-list mr-2"></i>Todas las Etiquetas
                            </a>

                            @if($etiqueta->etiquetasCliente->count() == 0)
                            <hr>
                            <button type="button" 
                                    class="btn btn-danger btn-block" 
                                    data-toggle="modal" 
                                    data-target="#modalEliminar">
                                <i class="fas fa-trash mr-2"></i>Eliminar Etiqueta
                            </button>
                            @endif
                        </div>

                        <!-- Información Adicional -->
                        <div class="mt-4">
                            <div class="alert alert-info border-left-info">
                                <h6 class="font-weight-bold mb-2">
                                    <i class="fas fa-info-circle mr-1"></i>Información:
                                </h6>
                                <small>
                                    Las etiquetas permiten clasificar y organizar a los clientes según 
                                    criterios específicos de su comportamiento crediticio o características relevantes.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para eliminar -->
    @if($etiqueta->etiquetasCliente->count() == 0)
    <div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-trash mr-2"></i>Eliminar Etiqueta
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.etiquetas.destroy', $etiqueta->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body">
                        <p>¿Está seguro de eliminar esta etiqueta?</p>
                        <div class="alert alert-warning">
                            <strong>Etiqueta:</strong> 
                            <span class="badge px-3 py-2" style="background-color: {{ $etiqueta->color }}; color: white;">
                                {{ $etiqueta->etiqueta }}
                            </span>
                        </div>
                        <small class="text-muted">Esta acción no se puede deshacer.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash mr-1"></i>Eliminar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@stop

@section('css')
<style>
    .bg-gradient-secondary {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    }
    
    .bg-gradient-dark {
        background: linear-gradient(135deg, #343a40 0%, #23272b 100%);
    }
    
    .border-left-info {
        border-left: 4px solid #17a2b8 !important;
    }
    
    .card {
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .info-item {
        padding: 0.5rem 0;
        border-bottom: 1px dotted #eee;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        color: #555;
        font-size: 0.9rem;
    }
    
    .info-value {
        text-align: right;
        font-size: 0.9rem;
    }
    
    .info-box {
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    
    .badge-lg {
        padding: 8px 16px;
        font-size: 14px;
    }
    
    .btn {
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .sticky-top {
        position: sticky;
        z-index: 1020;
    }
    
    .table th {
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .table td {
        vertical-align: middle;
        font-size: 0.9rem;
    }
    
    @media (max-width: 992px) {
        .sticky-top {
            position: relative;
            top: auto !important;
        }
        
        .info-item {
            flex-direction: column;
            align-items: start !important;
        }
        
        .info-value {
            text-align: left;
            width: 100%;
            margin-top: 0.25rem;
        }
    }
</style>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animación para los elementos
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>
@stop