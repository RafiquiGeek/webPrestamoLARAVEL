@props(['estado', 'tieneConvenio' => false, 'motivoRechazo' => null])

@if($tieneConvenio)
    <span class="badge bg-info text-white rounded-pill px-2 py-1">
        <i class="fas fa-handshake me-1"></i> Con Convenio
    </span>
@else
    @switch($estado)
        @case('Nueva Solicitud')
            <span class="badge bg-primary text-white rounded-pill px-2 py-1">
                <i class="fas fa-file-invoice me-1"></i> Nueva Solicitud
            </span>
        @break
        @case('Aprobado')
            <span class="badge bg-warning text-white rounded-pill px-2 py-1">
                <i class="fas fa-thumbs-up me-1"></i> Aprobado
            </span>
        @break
        @case('Por Desembolsar')
            <span class="badge bg-success text-white rounded-pill px-2 py-1">
                <i class="fas fa-money-check me-1"></i> Por Desembolsar
            </span>
        @break
        @case('Vigente')
            <span class="badge bg-info text-white rounded-pill px-2 py-1">
                <i class="fas fa-check-circle me-1"></i> Vigente
            </span>
        @break
        @case('Moroso')
            <span class="badge bg-warning text-dark rounded-pill px-2 py-1">
                <i class="fas fa-exclamation-triangle me-1"></i> Moroso
            </span>
        @break
        @case('Liquidado')
            <span class="badge bg-secondary text-white rounded-pill px-2 py-1">
                <i class="fas fa-money-bill-wave me-1"></i> Liquidado
            </span>
        @break
        @case('Finalizado')
            <span class="badge bg-dark text-white rounded-pill px-2 py-1">
                <i class="fas fa-flag-checkered me-1"></i> Finalizado
            </span>
        @break
        @case('Rechazado')
            <div class="d-inline-block position-relative">
                <span class="badge bg-danger text-white rounded-pill px-2 py-1">
                    <i class="fas fa-times-circle me-1"></i> Rechazado
                </span>
                @if($motivoRechazo)
                    <span class="motivo-tooltip">
                        <i class="fas fa-info-circle ms-1" style="cursor: help;"></i>
                        <span class="tooltip-content">{{ $motivoRechazo }}</span>
                    </span>
                @endif
            </div>
        @break
        @case('Cancelado')
            <span class="badge bg-danger text-white rounded-pill px-2 py-1">
                <i class="fas fa-ban me-1"></i> Anulado
            </span>
        @break
        @case('Con Convenio')
            <span class="badge bg-info text-white rounded-pill px-2 py-1">
                <i class="fas fa-handshake me-1"></i> Con Convenio
            </span>
        @break
        @default
            <span class="badge bg-light text-dark rounded-pill px-2 py-1">
                {{ $estado ?? 'Estado no definido' }}
            </span>
    @endswitch
@endif