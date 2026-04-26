@props(['prestamo'])

@php
    // Obtener todas las etiquetas del préstamo sin restricción de estado
    $etiquetasCliente = \App\Models\EtiquetaCliente::where('prestamo_id', $prestamo->id)
        ->with('etiqueta')
        ->get();
    
    // Estados que permiten gestión de etiquetas
    $estadosGestionables = ['Vigente', 'Moroso', 'Por Desembolsar'];
    $puedeGestionarEtiquetas = in_array($prestamo->estado, $estadosGestionables);
    
    $nombreCompleto = $prestamo->cliente->persona->nombres . ' ' . 
                      $prestamo->cliente->persona->ape_pat . ' ' . 
                      $prestamo->cliente->persona->ape_mat;
@endphp

<div class="etiquetas-container">
    @if($etiquetasCliente->count() > 0)
        <div class="d-flex flex-wrap gap-1 mb-1">
            @foreach($etiquetasCliente as $etiquetaCliente)
                <span class="badge rounded-pill px-2 py-1" 
                      style="background-color: {{ $etiquetaCliente->etiqueta->color }}; color: {{ $etiquetaCliente->etiqueta->color === '#FFFFFF' || $etiquetaCliente->etiqueta->color === '#ffffff' ? '#000000' : '#FFFFFF' }};"
                      title="Etiqueta: {{ $etiquetaCliente->etiqueta->etiqueta }}">
                    <i class="fas fa-tag me-1" style="font-size: 0.7rem;"></i>
                    {{ Str::limit($etiquetaCliente->etiqueta->etiqueta, 15) }}
                </span>
            @endforeach
        </div>
        
        @if($puedeGestionarEtiquetas)
            <button type="button" class="btn btn-outline-primary btn-xs" 
                    onclick="abrirModalEtiqueta({{ $prestamo->id }}, {{ $prestamo->cliente_id }}, '{{ addslashes($nombreCompleto) }}')"
                    title="Gestionar etiquetas">
                <i class="fas fa-edit" style="font-size: 0.7rem;"></i>
            </button>
        @endif
    @else
        @if($puedeGestionarEtiquetas)
            <button type="button" class="btn btn-outline-secondary btn-sm" 
                    onclick="abrirModalEtiqueta({{ $prestamo->id }}, {{ $prestamo->cliente_id }}, '{{ addslashes($nombreCompleto) }}')"
                    title="Asignar primera etiqueta">
                <i class="fas fa-tag me-1"></i>
                <span class="d-none d-lg-inline">Asignar</span>
            </button>
        @else
            <span class="text-muted small">
                <i class="fas fa-minus"></i>
            </span>
        @endif
    @endif
</div>