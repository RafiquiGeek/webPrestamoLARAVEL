@extends('layouts.admin')

@section('title', 'Asignaciones Empleado-Área')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users-cog mr-1"></i>
                        Asignaciones Empleado-Área
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.asistencia.asignaciones.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Asignación
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($asignaciones->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Código</th>
                                        <th>Área Laboral</th>
                                        <th>Horario</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($asignaciones as $asignacion)
                                        <tr>
                                            <td>{{ $asignacion->usuario->name }}</td>
                                            <td>{{ $asignacion->usuario->codigo ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge badge-pill" style="background-color: {{ $asignacion->areaLaboral->color }}; color: white;">
                                                    {{ $asignacion->areaLaboral->nombre }}
                                                </span>
                                            </td>
                                            <td>{{ $asignacion->horarioTrabajo->nombre }}</td>
                                            <td>{{ $asignacion->fecha_inicio->format('d/m/Y') }}</td>
                                            <td>{{ $asignacion->fecha_fin ? $asignacion->fecha_fin->format('d/m/Y') : 'Indefinida' }}</td>
                                            <td>
                                                @if($asignacion->activo)
                                                    <span class="badge badge-success">Activa</span>
                                                @else
                                                    <span class="badge badge-secondary">Inactiva</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.asistencia.asignaciones.edit', $asignacion) }}" 
                                                       class="btn btn-sm btn-info" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <form action="{{ route('admin.asistencia.asignaciones.toggle', $asignacion) }}" 
                                                          method="POST" style="display: inline-block;">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                                class="btn btn-sm {{ $asignacion->activo ? 'btn-warning' : 'btn-success' }}" 
                                                                title="{{ $asignacion->activo ? 'Desactivar' : 'Activar' }}"
                                                                onclick="return confirm('¿Estás seguro de {{ $asignacion->activo ? 'desactivar' : 'activar' }} esta asignación?')">
                                                            <i class="fas {{ $asignacion->activo ? 'fa-pause' : 'fa-play' }}"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center">
                            {{ $asignaciones->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <img src="{{ asset('img/no-data.png') }}" alt="Sin datos" class="mb-3" style="max-width: 200px;">
                            <h5 class="text-muted">No hay asignaciones registradas</h5>
                            <p class="text-muted">Comienza creando una nueva asignación.</p>
                            <a href="{{ route('admin.asistencia.asignaciones.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Crear Primera Asignación
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    .btn-group .btn {
        margin-right: 2px;
    }
    .badge-pill {
        font-size: 0.9em;
    }
</style>
@endsection