@extends('layouts.admin')

@section('title', 'Gestión de Moras')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-percentage mr-2"></i>Gestión de Moras</h1>
        <a href="{{ route('admin.moras.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i>Nueva Mora
        </a>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card card-outline card-primary shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="pl-4">ID</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Última Actualización</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($moras as $mora)
                            <tr>
                                <td class="pl-4">{{ $mora->id }}</td>
                                <td>{{ $mora->monto_formateado }}</td>
                                <td>
                                    <span class="badge badge-{{ $mora->status == 'Activo' ? 'success' : 'danger' }}">
                                        {{ $mora->status }}
                                    </span>
                                </td>
                                <td>{{ $mora->updated_at->format('d/m/Y H:i') }}</td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.moras.edit', $mora) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('admin.moras.history', $mora) }}" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-history"></i>
                                        </a>
                                        <form action="{{ route('admin.moras.destroy', $mora) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar esta mora?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle mr-1"></i> No hay moras registradas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($moras->hasPages())
            <div class="card-footer">
                {{ $moras->links() }}
            </div>
        @endif
    </div>
@stop