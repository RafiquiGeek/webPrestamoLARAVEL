@props(['modelo'])

{{-- Componente para mostrar etiquetas de EDITADA/ANULADA --}}
@if(method_exists($modelo, 'getEtiquetasEstado'))
    @foreach($modelo->getEtiquetasEstado() as $etiqueta)
        <span {{ $attributes->merge(['class' => $etiqueta['clase']]) }} 
              title="{{ $etiqueta['titulo'] }}">
            {{ $etiqueta['texto'] }}
        </span>
    @endforeach
@endif