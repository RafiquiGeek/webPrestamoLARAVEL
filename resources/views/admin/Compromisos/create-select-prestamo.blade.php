@extends('layouts.admin')

@section('title', 'Seleccionar Préstamo para Compromiso')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="font-weight-bold text-primary">Crear Nuevo Compromiso</h1>
            <p class="text-muted">Seleccione el préstamo para el cual desea crear un compromiso de pago</p>
        </div>
        <a href="{{ route('admin.compromisos.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i>Volver
        </a>
    </div>
@stop

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-search mr-2"></i>Seleccionar Préstamo
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Buscador -->
                    <div class="form-group mb-4">
                        <div class="input-group input-group-lg">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white border-right-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                            </div>
                            <input type="text" id="search-prestamo" class="form-control form-control-lg border-left-0" 
                                placeholder="Buscar por cliente, ID de préstamo...">
                        </div>
                    </div>

                    <!-- Lista de préstamos -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                    <th width="120">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="prestamos-list">
                                @forelse($prestamos as $prestamo)
                                    <tr class="prestamo-row" data-search="{{ strtolower($prestamo->id . ' ' . 
                                        $prestamo->cliente->persona->nombres . ' ' . 
                                        $prestamo->cliente->persona->ape_pat . ' ' . 
                                        $prestamo->cliente->persona->ape_mat) }}">
                                        <td>
                                            <span class="badge badge-info badge-lg">
                                                {{ $prestamo->id }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="client-avatar mr-3">
                                                    <i class="fas fa-user-circle fa-2x text-primary"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 font-weight-bold">
                                                        {{ $prestamo->cliente->persona->nombres }} 
                                                        {{ $prestamo->cliente->persona->ape_pat }} 
                                                        {{ $prestamo->cliente->persona->ape_mat }}
                                                    </h6>
                                                    <small class="text-muted">
                                                        DNI: {{ $prestamo->cliente->persona->nro_doc }}
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong class="text-success">
                                                    S/ {{ number_format($prestamo->cantidad_solicitada, 2) }}
                                                </strong>
                                                <br>
                                                <small class="text-muted">
                                                    {{ $prestamo->plazo_meses }} meses
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            @if($prestamo->estado == 1)
                                                <span class="badge badge-success">Activo</span>
                                            @else
                                                <span class="badge badge-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.compromisos.create', ['prestamo_id' => $prestamo->id]) }}" 
                                                class="btn btn-primary btn-sm">
                                                <i class="fas fa-plus mr-1"></i>Crear Compromiso
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                                <p class="mb-0">No hay préstamos disponibles</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Información adicional -->
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Información:</strong> 
                        Solo se muestran préstamos activos con información completa del cliente.
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.card {
    border-radius: 12px;
    transition: all 0.3s ease;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

.prestamo-row {
    transition: all 0.3s ease;
}

.prestamo-row.hidden {
    display: none;
}

.client-avatar i {
    color: #007bff;
}

.badge-lg {
    padding: 8px 12px;
    font-size: 14px;
}

.btn {
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.input-group-text {
    border-radius: 8px 0 0 8px;
}

.form-control.border-left-0 {
    border-left: none;
    border-radius: 0 8px 8px 0;
}
</style>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Funcionalidad de búsqueda
    const searchInput = document.getElementById('search-prestamo');
    const prestamoRows = document.querySelectorAll('.prestamo-row');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        prestamoRows.forEach(row => {
            const searchData = row.getAttribute('data-search');
            
            if (searchData.includes(searchTerm)) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });
        
        // Mostrar mensaje si no hay resultados
        const visibleRows = document.querySelectorAll('.prestamo-row:not(.hidden)');
        if (visibleRows.length === 0 && searchTerm !== '') {
            // Aquí podrías mostrar un mensaje de "no hay resultados"
        }
    });
    
    // Limpiar búsqueda al hacer clic en el campo
    searchInput.addEventListener('focus', function() {
        if (this.value === '') {
            prestamoRows.forEach(row => {
                row.classList.remove('hidden');
            });
        }
    });
});
</script>
@stop