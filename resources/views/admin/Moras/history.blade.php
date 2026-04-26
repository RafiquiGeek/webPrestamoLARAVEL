@extends('layouts.admin')

@section('title', 'Historial de Mora')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-history mr-2"></i>Historial de Mora: {{ $mora->monto_formateado }}</h1>
        <a href="{{ route('admin.moras.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="pl-4">ID</th>
                            <th>Acción</th>
                            <th>Monto Anterior</th>
                            <th>Monto Nuevo</th>
                            <th>Estado Anterior</th>
                            <th>Estado Nuevo</th>
                            <th>Usuario</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historial as $registro)
                            <tr>
                                <td class="pl-4">{{ $registro->id }}</td>
                                <td>
                                    <span class="badge badge-{{ 
                                        $registro->accion == 'creado' ? 'success' : 
                                        ($registro->accion == 'actualizado' ? 'warning' : 'danger') 
                                    }}">
                                        {{ ucfirst($registro->accion) }}
                                    </span>
                                </td>
                                <td>{{ $registro->monto_anterior ? number_format($registro->monto_anterior, 2).'%' : '-' }}</td>
                                <td>{{ $registro->monto_nuevo ? number_format($registro->monto_nuevo, 2).'%' : '-' }}</td>
                                <td>
                                    @if($registro->status_anterior !== null)
                                        <span class="badge {{ $registro->status_anterior ? 'badge-success' : 'badge-danger' }}">
                                            {{ $registro->status_anterior ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($registro->status_nuevo !== null)
                                        <span class="badge {{ $registro->status_nuevo ? 'badge-success' : 'badge-danger' }}">
                                            {{ $registro->status_nuevo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $registro->user->name ?? 'Sistema' }}</td>
                                <td>{{ $registro->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle mr-1"></i> No hay registros históricos
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($historial->hasPages())
            <div class="card-footer">
                {{ $historial->links() }}
            </div>
        @endif
    </div>
@stop