@extends('layouts.admin')

@section('title', 'Editar Estado de Gestión')

@section('content_header')
    <h1>Editar Estado de Gestión</h1>
@stop

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.estados_gestion.update', $estadoGestion->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="estado">Estado</label>
            <input type="text" name="estado" class="form-control" value="{{ $estadoGestion->estado }}" required>
        </div>
        <div class="form-group">
            <label for="clasificacion">Clasificación</label>
            <input type="text" name="clasificacion" class="form-control" value="{{ $estadoGestion->clasificacion }}">
        </div>
        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="{{ route('admin.estados_gestion.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
@stop
