@extends('layouts.admin')

@section('title', 'Gestión de Gastos')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Gestión de Gastos</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Inicio</a></li>
                        <li class="breadcrumb-item active">Gastos</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Filtros -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-filter mr-1"></i>
                        Filtros de búsqueda
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.gastos.index') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="categoria_id">Categoria</label>
                                    <select class="form-control" name="categoria_id" id="categoria_id">
                                        <option value="">Todas las categorías</option>
                                        @foreach($categorias as $categoria)
                                            <option value="{{ $categoria->id }}" 
                                                {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>
                                                {{ $categoria->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="fecha_inicio">Fecha Inicio</label>
                                    <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio" 
                                           value="{{ request('fecha_inicio') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="fecha_fin">Fecha Fin</label>
                                    <input type="date" class="form-control" name="fecha_fin" id="fecha_fin" 
                                           value="{{ request('fecha_fin') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="tipo_documento">Tipo Doc.</label>
                                    <select class="form-control" name="tipo_documento" id="tipo_documento">
                                        <option value="">Todos</option>
                                        <option value="DNI" {{ request('tipo_documento') == 'DNI' ? 'selected' : '' }}>DNI</option>
                                        <option value="RUC" {{ request('tipo_documento') == 'RUC' ? 'selected' : '' }}>RUC</option>
                                        <option value="CE" {{ request('tipo_documento') == 'CE' ? 'selected' : '' }}>CE</option>
                                        <option value="PAS" {{ request('tipo_documento') == 'PAS' ? 'selected' : '' }}>PAS</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="beneficiario">Beneficiario</label>
                                    <input type="text" class="form-control" name="beneficiario" id="beneficiario" 
                                           placeholder="Buscar por nombre/razón social" value="{{ request('beneficiario') }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search mr-1"></i> Buscar
                                </button>
                                <a href="{{ route('admin.gastos.index') }}" class="btn btn-outline-secondary ml-2">
                                    <i class="fas fa-times mr-1"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Resultados -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-receipt mr-1"></i>
                        Lista de Gastos
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.gastos.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus mr-1"></i> Nuevo Gasto
                        </a>
                        <a href="{{ route('admin.categorias-gastos.index') }}" class="btn btn-info btn-sm ml-2">
                            <i class="fas fa-tags mr-1"></i> Categorías
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    @if($gastos->count() > 0)
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Categoría</th>
                                    <th>Concepto</th>
                                    <th>Beneficiario</th>
                                    <th>Documento</th>
                                    <th>Comprobante</th>
                                    <th>Monto</th>
                                    <th>Usuario</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($gastos as $gasto)
                                <tr>
                                    <td>{{ $gasto->fecha_gasto->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $gasto->categoria->color }}; color: white;">
                                            {{ $gasto->categoria->nombre }}
                                        </span>
                                    </td>
                                    <td>{{ $gasto->concepto }}</td>
                                    <td>{{ $gasto->beneficiario_completo }}</td>
                                    <td>
                                        <small>{{ $gasto->tipo_documento }}: {{ $gasto->documento_identidad }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $gasto->tipo_comprobante_texto }}<br>
                                        {{ $gasto->comprobante_completo }}</small>
                                    </td>
                                    <td class="text-right">
                                        <strong>S/ {{ number_format($gasto->monto, 2) }}</strong>
                                    </td>
                                    <td>
                                        <small>{{ $gasto->usuario->name ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.gastos.show', $gasto) }}" 
                                               class="btn btn-sm btn-info" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.gastos.edit', $gasto) }}" 
                                               class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="confirmarEliminacion({{ $gasto->id }})" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No se encontraron gastos con los filtros aplicados</p>
                            <a href="{{ route('admin.gastos.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i> Registrar primer gasto
                            </a>
                        </div>
                    @endif
                </div>
                
                @if($gastos->count() > 0)
                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">
                                    Total en página: <strong>S/ {{ number_format($totalPagina, 2) }}</strong>
                                </span>
                                <span class="text-primary">
                                    Total general: <strong>S/ {{ number_format($totalGeneral, 2) }}</strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="float-right">
                                {{ $gastos->links() }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </section>
</div>

<!-- Modal de confirmaci�n de eliminaci�n -->
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
                <p>¿Está seguro que desea eliminar este gasto?</p>
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
function confirmarEliminacion(gastoId) {
    const form = document.getElementById('formEliminar');
    form.action = `/admin/gastos/${gastoId}`;
    $('#modalEliminar').modal('show');
}

// Auto-submit form on date change
document.getElementById('fecha_inicio').addEventListener('change', function() {
    if (this.form.fecha_fin.value) {
        this.form.submit();
    }
});

document.getElementById('fecha_fin').addEventListener('change', function() {
    if (this.form.fecha_inicio.value) {
        this.form.submit();
    }
});
</script>
@endsection