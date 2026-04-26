@extends('layouts.admin')

@section('title', 'Categorías de Gastos')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Categorías de Gastos</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.gastos.index') }}">Gastos</a></li>
                        <li class="breadcrumb-item active">Categoría</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tags mr-1"></i>
                        Lista de Categorías
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.categorias-gastos.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus mr-1"></i> Nueva Categoría
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    @if($categorias->count() > 0)
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>Color</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th>Gastos</th>
                                    <th>Fecha Creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categorias as $categoria)
                                <tr>
                                    <td>
                                        <span class="badge" style="background-color: {{ $categoria->color }}; color: white; width: 30px; height: 20px;">
                                            &nbsp;
                                        </span>
                                    </td>
                                    <td>
                                        <strong>{{ $categoria->nombre }}</strong>
                                    </td>
                                    <td>
                                        <small>{{ $categoria->descripcion ?? 'Sin descripción' }}</small>
                                    </td>
                                    <td>
                                        @if($categoria->estado)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $categoria->gastos_count }}</span>
                                    </td>
                                    <td>
                                        <small>{{ $categoria->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.categorias-gastos.show', $categoria->id) }}" 
                                               class="btn btn-sm btn-info" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.categorias-gastos.edit', $categoria->id) }}" 
                                               class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($categoria->gastos_count == 0)
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="confirmarEliminacion({{ $categoria->id }})" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-sm btn-secondary" 
                                                        title="No se puede eliminar (tiene gastos asociados)" disabled>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay categorías registradas</p>
                            <a href="{{ route('admin.categorias-gastos.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i> Crear primera categoría
                            </a>
                        </div>
                    @endif
                </div>
                
                @if($categorias->count() > 0)
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando {{ $categorias->firstItem() ?? 0 }} a {{ $categorias->lastItem() ?? 0 }} 
                            de {{ $categorias->total() }} categorías
                        </div>
                        <div>
                            {{ $categorias->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </section>
</div>

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
                <p>¿Está seguro que desea eliminar esta categoría?</p>
                <p class="text-danger"><small>Esta acción no se puede deshacer.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form id="formEliminar" method="POST" style="display: inline;">
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
@endsection

@section('scripts')
<script>
function confirmarEliminacion(categoriaId) {
    const form = document.getElementById('formEliminar');
    form.action = `/admin/categorias-gastos/${categoriaId}`;
    $('#modalEliminar').modal('show');
}
</script>
@endsection