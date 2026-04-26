@extends('layouts.admin')

@section('title', 'Estados de Gestión')

@section('content_header')
    <h1>Estados de Gestión</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-3">
        <a href="{{ route('admin.estados_gestion.create') }}" class="btn btn-primary">Crear Estado</a>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Estado</th>
                <th>Clasificación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($estados as $estado)
                <tr>
                    <td>{{ $estado->id }}</td>
                    <td>{{ $estado->estado }}</td>
                    <td>{{ $estado->clasificacion }}</td>
                    <td>
                        <a href="{{ route('admin.estados_gestion.edit', $estado->id) }}" class="btn btn-sm btn-warning">Editar</a>
                        <form action="{{ route('admin.estados_gestion.destroy', $estado->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@stop
