@props(['fondoExistente', 'prestamoId'])

@if(!$fondoExistente)
    <a class="dropdown-item" href="{{ route('admin.fondo-provisional.create', ['prestamo_id' => $prestamoId]) }}">
        <i class="fas fa-piggy-bank text-warning me-2"></i>Crear Fondo
    </a>
@else
    <a class="dropdown-item" href="{{ route('admin.fondo-provisional.show', $fondoExistente->id) }}">
        <i class="fas fa-check-circle text-success me-2"></i>Ver Fondo
    </a>
@endif