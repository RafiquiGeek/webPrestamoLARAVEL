@extends('layouts.admin')

@section('title', 'Detalles de Categoría')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Detalles de Categoría</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.gastos.index') }}">Gastos</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.categorias-gastos.index') }}">Categorías</a></li>
                        <li class="breadcrumb-item active">{{ $categoriaGasto->nombre }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <!-- Información de la Categoría -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-tag mr-1"></i>
                                Información de la Categoría
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.categorias-gastos.edit', $categoriaGasto->id) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit mr-1"></i> Editar
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Nombre:</strong>
                                    <p class="text-muted">
                                        <span class="badge" style="background-color: {{ $categoriaGasto->color }}; color: white; font-size: 16px;">
                                            {{ $categoriaGasto->nombre }}
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Estado:</strong>
                                    <p class="text-muted">
                                        @if($categoriaGasto->estado)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-secondary">Inactivo</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Color:</strong>
                                    <p class="text-muted">
                                        <span class="badge" style="background-color: {{ $categoriaGasto->color }}; color: white; width: 40px; height: 25px;">
                                            &nbsp;
                                        </span>
                                        <code>{{ $categoriaGasto->color }}</code>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Total de Gastos:</strong>
                                    <p class="text-muted">
                                        <span class="badge badge-info">{{ $categoriaGasto->gastos_count }}</span>
                                    </p>
                                </div>
                            </div>

                            @if($categoriaGasto->descripcion)
                            <div class="row">
                                <div class="col-md-12">
                                    <strong>Descripción:</strong>
                                    <p class="text-muted">{{ $categoriaGasto->descripcion }}</p>
                                </div>
                            </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Fecha de Creación:</strong>
                                    <p class="text-muted">{{ $categoriaGasto->created_at->format('d/m/Y H:i:s') }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Última Actualización:</strong>
                                    <p class="text-muted">{{ $categoriaGasto->updated_at->format('d/m/Y H:i:s') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gastos Recientes -->
                    @if($gastosRecientes->count() > 0)
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-receipt mr-1"></i>
                                Gastos Recientes
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.gastos.index', ['categoria_id' => $categoriaGasto->id]) }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-list mr-1"></i> Ver Todos
                                </a>
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Concepto</th>
                                        <th>Beneficiario</th>
                                        <th>Monto</th>
                                        <th>Usuario</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($gastosRecientes as $gasto)
                                    <tr>
                                        <td>{{ $gasto->fecha_gasto->format('d/m/Y') }}</td>
                                        <td>{{ $gasto->concepto }}</td>
                                        <td>{{ $gasto->beneficiario_completo }}</td>
                                        <td class="text-right">
                                            <strong>S/ {{ number_format($gasto->monto, 2) }}</strong>
                                        </td>
                                        <td>
                                            <small>{{ $gasto->usuario->name ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.gastos.show', $gasto) }}" 
                                               class="btn btn-sm btn-info" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="col-md-4">
                    <!-- Estadísticas -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar mr-1"></i>
                                Estadísticas
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="info-box bg-light">
                                <span class="info-box-icon" style="background-color: {{ $categoriaGasto->color }};">
                                    <i class="fas fa-receipt"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total de Gastos</span>
                                    <span class="info-box-number">{{ $categoriaGasto->gastos_count }}</span>
                                </div>
                            </div>

                            @php
                                $totalMonto = $categoriaGasto->gastos()->sum('monto');
                                $gastoPromedio = $categoriaGasto->gastos_count > 0 ? $totalMonto / $categoriaGasto->gastos_count : 0;
                            @endphp

                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-dollar-sign"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Monto Total</span>
                                    <span class="info-box-number">S/ {{ number_format($totalMonto, 2) }}</span>
                                </div>
                            </div>

                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-calculator"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Gasto Promedio</span>
                                    <span class="info-box-number">S/ {{ number_format($gastoPromedio, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-cogs mr-1"></i>
                                Acciones
                            </h3>
                        </div>
                        <div class="card-body">
                            <a href="{{ route('admin.gastos.create') }}?categoria={{ $categoriaGasto->id }}" 
                               class="btn btn-success btn-block">
                                <i class="fas fa-plus mr-1"></i> Nuevo Gasto en esta Categoría
                            </a>
                            
                            <a href="{{ route('admin.categorias-gastos.edit', $categoriaGasto->id) }}" 
                               class="btn btn-warning btn-block">
                                <i class="fas fa-edit mr-1"></i> Editar Categoría
                            </a>
                            
                            <a href="{{ route('admin.gastos.index', ['categoria_id' => $categoriaGasto->id]) }}" 
                               class="btn btn-info btn-block">
                                <i class="fas fa-list mr-1"></i> Ver Todos los Gastos
                            </a>
                            
                            @if($categoriaGasto->gastos_count == 0)
                            <button type="button" class="btn btn-danger btn-block" 
                                    onclick="confirmarEliminacion({{ $categoriaGasto->id }})">
                                <i class="fas fa-trash mr-1"></i> Eliminar Categoría
                            </button>
                            @endif
                            
                            <a href="{{ route('admin.categorias-gastos.index') }}" 
                               class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-arrow-left mr-1"></i> Volver a Categorías
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@if($categoriaGasto->gastos_count == 0)
<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar la categoría <strong>{{ $categoriaGasto->nombre }}</strong>?</p>
                <p class="text-danger"><small>Esta acción no se puede deshacer.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form id="formEliminar" method="POST" action="{{ route('admin.categorias-gastos.destroy', $categoriaGasto->id) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash mr-1"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
function confirmarEliminacion(categoriaId) {
    $('#modalEliminar').modal('show');
}
</script>
@endsection