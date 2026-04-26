@extends('layouts.admin')
@section('title', 'Áreas Laborales')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-building mr-2"></i>Áreas Laborales</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.index') }}">Asistencia</a></li>
            <li class="breadcrumb-item active">Áreas Laborales</li>
        </ol>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Botones de acción -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <a href="{{ route('admin.asistencia.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i>Volver
                            </a>
                        </div>
                        <div>
                            <a href="{{ route('admin.asistencia.areas-laborales.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i>Nueva Área
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de áreas -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list mr-2"></i>Lista de Áreas Laborales
                    </h3>
                </div>
                <div class="card-body">
                    @if($areas->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th width="50">ID</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th width="80">Color</th>
                                        <th width="100">Estado</th>
                                        <th width="150">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($areas as $area)
                                        <tr>
                                            <td>{{ $area->id }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="color-indicator me-2" 
                                                         style="width: 20px; height: 20px; background-color: {{ $area->color }}; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px #ccc;">
                                                    </div>
                                                    <strong>{{ $area->nombre }}</strong>
                                                </div>
                                            </td>
                                            <td>{{ $area->descripcion ?? 'Sin descripción' }}</td>
                                            <td>
                                                <span class="badge" style="background-color: {{ $area->color }}; color: white;">
                                                    {{ $area->color }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $area->activo ? 'success' : 'secondary' }}">
                                                    {{ $area->activo ? 'Activa' : 'Inactiva' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.asistencia.areas-laborales.edit', $area) }}" 
                                                       class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <form action="{{ route('admin.asistencia.areas-laborales.toggle', $area) }}" 
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-{{ $area->activo ? 'secondary' : 'success' }}"
                                                                onclick="return confirm('¿Estás seguro de {{ $area->activo ? 'desactivar' : 'activar' }} esta área?')">
                                                            <i class="fas fa-{{ $area->activo ? 'eye-slash' : 'eye' }}"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        @if($areas->hasPages())
                            <div class="mt-3">
                                {{ $areas->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-building fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No hay áreas laborales registradas</h4>
                            <p class="text-muted">Comienza creando tu primera área laboral.</p>
                            <a href="{{ route('admin.asistencia.areas-laborales.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i>Crear Primera Área
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
.color-indicator {
    display: inline-block;
    margin-right: 8px;
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}
</style>
@stop