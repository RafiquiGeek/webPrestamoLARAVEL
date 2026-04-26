@extends('layouts.admin')

@section('title', 'Historial de Tasa')

@section('content_header')
    <div class="container">
        <h1>Historial de Cambios - {{ $tasa->tipo_tasa }}</h1>
    </div>
@endsection

@section('content')
    <div class="container card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Acción</th>
                        <th>Valor Anterior</th>
                        <th>Valor Nuevo</th>
                        <th>Estado Anterior</th>
                        <th>Estado Nuevo</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history as $entry)
                        <tr>
                            <td>{{ $entry->created_at }}</td>
                            <td>{{ $entry->accion }}</td>
                            <td>{{ $entry->valor_anterior ? number_format($entry->valor_anterior, 2) . '%' : '-' }}</td>
                            <td>{{ $entry->valor_nuevo ? number_format($entry->valor_nuevo, 2) . '%' : '-' }}</td>
                            <td>{{ $entry->status_anterior !== null ? ($entry->status_anterior ? 'Activo' : 'Inactivo') : '-' }}</td>
                            <td>{{ $entry->status_nuevo !== null ? ($entry->status_nuevo ? 'Activo' : 'Inactivo') : '-' }}</td>
                            <td>{{ $entry->user->name ?? 'Sistema' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection