@extends('layouts.admin')

@section('title', 'Etiquetas de Clientes')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="font-weight-bold text-primary">
                    <i class="fas fa-tags mr-2"></i>Etiquetas de Clientes
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Etiquetas</li>
                </ol>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <!-- Filtros -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-gradient-dark text-white">
                <h5 class="mb-0">
                    <i class="fas fa-filter mr-2"></i>Filtros de Búsqueda
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.etiquetas.index') }}" class="form-inline">
                    <div class="row w-100">
                        <div class="col-md-4 mb-3">
                            <label for="search" class="form-label font-weight-bold">Buscar etiqueta:</label>
                            <input type="text" 
                                   name="search" 
                                   id="search" 
                                   class="form-control" 
                                   placeholder="Nombre de la etiqueta..."
                                   value="{{ request('search') }}">
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="estado" class="form-label font-weight-bold">Estado:</label>
                            <select name="estado" id="estado" class="form-control">
                                <option value="">Todos los estados</option>
                                <option value="1" {{ request('estado') === '1' ? 'selected' : '' }}>
                                    Activo
                                </option>
                                <option value="0" {{ request('estado') === '0' ? 'selected' : '' }}>
                                    Inactivo
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <div class="w-100">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search mr-1"></i>Buscar
                                </button>
                            </div>
                        </div>

                        <div class="col-md-2 mb-3 d-flex align-items-end">
                            <div class="w-100">
                                <a href="{{ route('admin.etiquetas.create') }}" class="btn btn-success btn-block">
                                    <i class="fas fa-plus mr-1"></i>Nueva
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="row mb-4">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $etiquetas->total() }}</h3>
                        <p>Total Etiquetas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-tags"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $etiquetas->where('estado', 1)->count() }}</h3>
                        <p>Etiquetas Activas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $etiquetas->where('estado', 0)->count() }}</h3>
                        <p>Etiquetas Inactivas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ \App\Models\EtiquetaCliente::distinct('prestamo_id')->count() }}</h3>
                        <p>Préstamos Etiquetados</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-list"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Etiquetas -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-gradient-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-table mr-2"></i>Lista de Etiquetas
                    </h5>
                    <span class="badge badge-light badge-lg">
                        {{ $etiquetas->total() }} registros
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                @if($etiquetas->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th width="8%">ID</th>
                                <th width="25%">Etiqueta</th>
                                <th width="15%">Color</th>
                                <th width="12%">Vista Previa</th>
                                <th width="12%">Estado</th>
                                <th width="12%">Asignaciones</th>
                                <th width="16%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($etiquetas as $etiqueta)
                            <tr>
                                <td class="align-middle">
                                    <span class="badge badge-secondary">#{{ $etiqueta->id }}</span>
                                </td>
                                <td class="align-middle">
                                    <span class="font-weight-bold">{{ $etiqueta->etiqueta }}</span>
                                </td>
                                <td class="align-middle">
                                    <code>{{ $etiqueta->color }}</code>
                                </td>
                                <td class="align-middle">
                                    <span class="badge px-3 py-2" style="background-color: {{ $etiqueta->color }}; color: white;">
                                        {{ $etiqueta->etiqueta }}
                                    </span>
                                </td>
                                <td class="align-middle">
                                    @if($etiqueta->estado)
                                        <span class="badge badge-success">
                                            <i class="fas fa-check mr-1"></i>Activa
                                        </span>
                                    @else
                                        <span class="badge badge-danger">
                                            <i class="fas fa-times mr-1"></i>Inactiva
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <span class="badge badge-info">
                                        {{ $etiqueta->etiquetasCliente->count() }} asignaciones
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.etiquetas.show', $etiqueta->id) }}" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <a href="{{ route('admin.etiquetas.edit', $etiqueta->id) }}" 
                                           class="btn btn-sm btn-outline-warning" 
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        @if($etiqueta->etiquetasCliente->count() == 0)
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                data-toggle="modal" 
                                                data-target="#modalEliminar{{ $etiqueta->id }}"
                                                title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <!-- Modal para eliminar -->
                            @if($etiqueta->etiquetasCliente->count() == 0)
                            <div class="modal fade" id="modalEliminar{{ $etiqueta->id }}" tabindex="-1" role="dialog">
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
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando {{ $etiquetas->firstItem() }} a {{ $etiquetas->lastItem() }} 
                            de {{ $etiquetas->total() }} registros
                        </div>
                        <div>
                            {{ $etiquetas->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No se encontraron etiquetas</h5>
                    <p class="text-muted">No hay etiquetas registradas con los filtros seleccionados.</p>
                    <a href="{{ route('admin.etiquetas.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i>Crear Primera Etiqueta
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    }
    
    .bg-gradient-dark {
        background: linear-gradient(135deg, #343a40 0%, #23272b 100%);
    }
    
    .card {
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .table th {
        border-top: none;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .table td {
        vertical-align: middle;
        font-size: 0.9rem;
    }
    
    .small-box {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .small-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .btn {
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn:hover {
        transform: translateY(-1px);
    }
    
    .btn-group .btn {
        border-radius: 6px;
        margin: 0 1px;
    }
    
    .form-control {
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    }
    
    .modal-content {
        border-radius: 12px;
        border: none;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    }
    
    .badge-lg {
        padding: 8px 16px;
        font-size: 14px;
    }
    
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.8rem;
        }
        
        .btn-group {
            flex-direction: column;
        }
        
        .btn-group .btn {
            margin: 1px 0;
        }
        
        .small-box .inner h3 {
            font-size: 1.5rem;
        }
    }
</style>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animación para las tarjetas estadísticas
    const smallBoxes = document.querySelectorAll('.small-box');
    smallBoxes.forEach((box, index) => {
        box.style.opacity = '0';
        box.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            box.style.transition = 'all 0.5s ease';
            box.style.opacity = '1';
            box.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Animación para las filas de la tabla
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateX(0)';
        }, 200 + (index * 50));
    });
    
    // Auto-submit del formulario de filtros cuando cambia el estado
    const estadoSelect = document.getElementById('estado');
    if (estadoSelect) {
        estadoSelect.addEventListener('change', function() {
            if (this.value !== '') {
                this.form.submit();
            }
        });
    }
});
</script>
@stop