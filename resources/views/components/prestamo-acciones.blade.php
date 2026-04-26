@props(['prestamo'])

@php
    // Normalizar el estado para comparación (trim y uppercase para case-insensitive)
    $estadoNormalizado = strtoupper(trim($prestamo->estado));
@endphp

<style>
    .prestamo-actions {
        gap: 5px !important;
    }
    .prestamo-actions .btn {
        border-radius: 4px;
        font-size: 0.813rem;
        padding: 0.375rem 0.75rem;
        transition: all 0.2s ease;
    }
    .prestamo-actions .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>

@switch($estadoNormalizado)
    @case('NUEVA SOLICITUD')
        <div class="d-flex prestamo-actions justify-content-center align-items-center flex-wrap">
            <a href="{{ route('admin.proceso-prestamo.index', $prestamo->id) }}"
               class="btn btn-sm btn-primary">
                <i class="fas fa-play-circle me-1"></i>Iniciar Proceso
            </a>
            <button class="btn btn-sm btn-outline-danger" type="button"
                    onclick="abrirModalRechazar({{ $prestamo->id }})">
                <i class="fas fa-times-circle me-1"></i>Rechazar
            </button>
            <a class="btn btn-sm btn-outline-secondary"
               href="{{ route('admin.prestamos.show', $prestamo->id) }}">
                <i class="far fa-eye me-1"></i>Ver
            </a>
            @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('Admin')))
            <button class="btn btn-sm btn-outline-danger" type="button"
                    onclick="confirmarEliminar({{ $prestamo->id }})">
                <i class="fas fa-trash"></i>
            </button>
            @endif
        </div>
    @break

    @case('APROBADO')
        <div class="d-flex prestamo-actions justify-content-center align-items-center flex-wrap">
            <a href="{{ route('admin.proceso-prestamo.index', $prestamo->id) }}"
               class="btn btn-sm btn-primary">
                <i class="fas fa-tasks me-1"></i>Continuar
            </a>
            <a class="btn btn-sm btn-outline-secondary"
               href="{{ route('admin.prestamos.show', $prestamo->id) }}">
                <i class="far fa-eye me-1"></i>Ver
            </a>
            @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('Admin')))
            <button class="btn btn-sm btn-outline-danger" type="button"
                    onclick="confirmarEliminar({{ $prestamo->id }})">
                <i class="fas fa-trash"></i>
            </button>
            @endif
        </div>
    @break

    @case('POR DESEMBOLSAR')
        <div class="d-flex prestamo-actions justify-content-center align-items-center flex-wrap">
            <a class="btn btn-sm btn-success"
               href="{{ route('admin.proceso-prestamo.index', $prestamo->id) }}">
                <i class="fa-solid fa-sack-dollar me-1"></i>Desembolsar
            </a>
            <a class="btn btn-sm btn-outline-secondary"
               href="{{ route('admin.prestamos.show', $prestamo->id) }}">
                <i class="far fa-eye me-1"></i>Ver
            </a>
            @if($prestamo->convenios->isNotEmpty())
                @php
                    $ultimoConvenio = $prestamo->convenios->sortByDesc('created_at')->first();
                @endphp
                <a class="btn btn-sm btn-outline-primary"
                   href="{{ route('admin.convenios.show', $ultimoConvenio->id) }}">
                    <i class="fas fa-file-contract"></i>
                </a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('Admin')))
            <button class="btn btn-sm btn-outline-danger" type="button"
                    onclick="confirmarEliminar({{ $prestamo->id }})">
                <i class="fas fa-trash"></i>
            </button>
            @endif
        </div>
    @break

    @case('VIGENTE')
    @case('VIGENTE CON MORAS')
        <div class="d-flex prestamo-actions justify-content-center align-items-center flex-wrap">
            <a class="btn btn-sm btn-primary"
               href="{{ route('admin.prestamos.show', $prestamo->id) }}">
                <i class="far fa-eye me-1"></i>Ver Detalles
            </a>
            @if($prestamo->convenios->isNotEmpty())
                @php
                    $ultimoConvenio = $prestamo->convenios->sortByDesc('created_at')->first();
                @endphp
                <a class="btn btn-sm btn-outline-secondary"
                   href="{{ route('admin.convenios.show', $ultimoConvenio->id) }}">
                    <i class="fas fa-file-contract me-1"></i>Convenio
                </a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('Admin')))
            <button class="btn btn-sm btn-outline-danger" type="button"
                    onclick="confirmarEliminar({{ $prestamo->id }})">
                <i class="fas fa-trash"></i>
            </button>
            @endif
        </div>
    @break

    @case('MOROSO')
        <div class="d-flex prestamo-actions justify-content-center align-items-center flex-wrap">
            <a class="btn btn-sm btn-primary"
               href="{{ route('admin.prestamos.show', $prestamo->id) }}">
                <i class="far fa-eye me-1"></i>Ver Detalles
            </a>
            @if($prestamo->convenios->isNotEmpty())
                @php
                    $ultimoConvenio = $prestamo->convenios->sortByDesc('created_at')->first();
                @endphp
                <a class="btn btn-sm btn-outline-secondary"
                   href="{{ route('admin.convenios.show', $ultimoConvenio->id) }}">
                    <i class="fas fa-file-contract me-1"></i>Convenio
                </a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('Admin')))
            <button class="btn btn-sm btn-outline-danger" type="button"
                    onclick="confirmarEliminar({{ $prestamo->id }})">
                <i class="fas fa-trash"></i>
            </button>
            @endif
        </div>
    @break

    @case('CON CONVENIO')
        <div class="d-flex prestamo-actions justify-content-center align-items-center flex-wrap">
            <a class="btn btn-sm btn-primary"
               href="{{ route('admin.prestamos.show', $prestamo->id) }}">
                <i class="far fa-eye me-1"></i>Ver Detalles
            </a>
            @if($prestamo->convenios->isNotEmpty())
                @php
                    $ultimoConvenio = $prestamo->convenios->sortByDesc('created_at')->first();
                @endphp
                <a class="btn btn-sm btn-outline-secondary"
                   href="{{ route('admin.convenios.show', $ultimoConvenio->id) }}">
                    <i class="fas fa-file-contract me-1"></i>Convenio
                </a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('Admin')))
            <button class="btn btn-sm btn-outline-danger" type="button"
                    onclick="confirmarEliminar({{ $prestamo->id }})">
                <i class="fas fa-trash"></i>
            </button>
            @endif
        </div>
    @break

    @case('LIQUIDADO')
        <div class="d-flex prestamo-actions justify-content-center align-items-center flex-wrap">
            <a class="btn btn-sm btn-outline-secondary"
               href="{{ route('admin.prestamos.show', $prestamo->id) }}">
                <i class="far fa-eye me-1"></i>Ver
            </a>
            @if($prestamo->convenios->isNotEmpty())
                @php
                    $ultimoConvenio = $prestamo->convenios->sortByDesc('created_at')->first();
                @endphp
                <a class="btn btn-sm btn-outline-primary"
                   href="{{ route('admin.convenios.show', $ultimoConvenio->id) }}">
                    <i class="fas fa-file-contract"></i>
                </a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('Admin')))
            <button class="btn btn-sm btn-outline-danger" type="button"
                    onclick="confirmarEliminar({{ $prestamo->id }})">
                <i class="fas fa-trash"></i>
            </button>
            @endif
        </div>
    @break

    @case('RECHAZADO')
        <div class="d-flex prestamo-actions justify-content-center align-items-center flex-wrap">
            <a class="btn btn-sm btn-outline-secondary"
               href="{{ route('admin.prestamos.show', $prestamo->id) }}">
                <i class="far fa-eye me-1"></i>Ver
            </a>
            @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('Admin')))
            <button class="btn btn-sm btn-outline-danger" type="button"
                    onclick="confirmarEliminar({{ $prestamo->id }})">
                <i class="fas fa-trash"></i>
            </button>
            @endif
        </div>
    @break

    @case('CANCELADO')
    @case('FINALIZADO')
    @case('ANULADO')
        <div class="d-flex prestamo-actions justify-content-center align-items-center flex-wrap">
            <a class="btn btn-sm btn-outline-secondary"
               href="{{ route('admin.prestamos.show', $prestamo->id) }}">
                <i class="far fa-eye me-1"></i>Ver
            </a>
            @if($prestamo->convenios->isNotEmpty())
                @php
                    $ultimoConvenio = $prestamo->convenios->sortByDesc('created_at')->first();
                @endphp
                <a class="btn btn-sm btn-outline-primary"
                   href="{{ route('admin.convenios.show', $ultimoConvenio->id) }}">
                    <i class="fas fa-file-contract"></i>
                </a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('Admin')))
            <button class="btn btn-sm btn-outline-danger" type="button"
                    onclick="confirmarEliminar({{ $prestamo->id }})">
                <i class="fas fa-trash"></i>
            </button>
            @endif
        </div>
    @break

    @default
        <span class="text-muted small">Sin acciones</span>
@endswitch
