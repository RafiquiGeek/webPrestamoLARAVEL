@extends('layouts.admin')

@section('title', 'Fondos Provisionales')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="font-weight-bold">
                    <i class="fas fa-piggy-bank mr-2 text-warning"></i>Fondos Provisionales
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Fondos Provisionales</li>
                </ol>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <!-- Estadísticas -->
        <div class="row mb-4 g-2 g-md-3">
            <div class="col-6 col-lg-3">
                <div class="card border-left-warning shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Entregados</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $fondos->where('estado', 'entregado')->count() }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-warning opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-left-success shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Rendidos</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $fondos->where('estado', 'rendido')->count() }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-success opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-left-info shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Total Fondos</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">S/ {{ number_format($fondos->sum('monto_fondo'), 2) }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dollar-sign fa-2x text-info opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card border-left-secondary shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Total Registros</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $fondos->count() }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-list fa-2x text-secondary opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-filter mr-1"></i> Filtros de Busqueda
                </h3>
                <div class="card-tools">
                    @if(request()->hasAny(['estado', 'fecha_desde', 'fecha_hasta']))
                        <a href="{{ route('admin.fondo-provisional.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times mr-1"></i>Limpiar
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body py-3">
                <form method="GET" action="{{ route('admin.fondo-provisional.index') }}">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-2">
                            <label for="estado" class="mb-1 text-sm font-weight-bold">Estado</label>
                            <select name="estado" id="estado" class="form-control form-control-sm">
                                <option value="">Todos los estados</option>
                                <option value="entregado" {{ request('estado') === 'entregado' ? 'selected' : '' }}>
                                    Entregado
                                </option>
                                <option value="rendido" {{ request('estado') === 'rendido' ? 'selected' : '' }}>
                                    Rendido
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="fecha_desde" class="mb-1 text-sm font-weight-bold">Desde</label>
                            <input type="date" name="fecha_desde" id="fecha_desde"
                                   class="form-control form-control-sm"
                                   value="{{ request('fecha_desde') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="fecha_hasta" class="mb-1 text-sm font-weight-bold">Hasta</label>
                            <input type="date" name="fecha_hasta" id="fecha_hasta"
                                   class="form-control form-control-sm"
                                   value="{{ request('fecha_hasta') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <button type="submit" class="btn btn-primary btn-sm btn-block">
                                <i class="fas fa-search mr-1"></i>Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-table mr-1"></i> Lista de Fondos Provisionales
                </h3>
                <div class="card-tools">
                    <span class="badge badge-primary px-3 py-1">
                        {{ $fondos->total() }} registros
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                @if($fondos->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th class="pl-3">ID</th>
                                <th>Cliente</th>
                                <th>Prestamo</th>
                                <th class="text-right">Capital</th>
                                <th class="text-right">Fondo (5%)</th>
                                <th>Usuario</th>
                                <th>F. Entrega</th>
                                <th>Metodo</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fondos as $fondo)
                            <tr>
                                <td class="align-middle pl-3">
                                    <span class="text-muted font-weight-bold">#{{ $fondo->id }}</span>
                                </td>
                                <td class="align-middle">
                                    @if($fondo->prestamo && $fondo->prestamo->cliente && $fondo->prestamo->cliente->persona)
                                        <span class="font-weight-bold d-block text-sm">
                                            {{ $fondo->prestamo->cliente->persona->nombres }}
                                        </span>
                                        <small class="text-muted">
                                            {{ $fondo->prestamo->cliente->persona->ape_pat }}
                                            {{ $fondo->prestamo->cliente->persona->ape_mat }}
                                        </small>
                                    @else
                                        <span class="text-muted">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>No disponible
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @if($fondo->prestamo)
                                    <a href="{{ route('admin.prestamos.show', $fondo->prestamo_id) }}"
                                       class="btn btn-xs btn-outline-primary" title="Ver prestamo">
                                        <i class="fas fa-external-link-alt mr-1"></i>#{{ $fondo->prestamo_id }}
                                    </a>
                                    @else
                                    <span class="text-muted">#{{ $fondo->prestamo_id }}</span>
                                    @endif
                                </td>
                                <td class="align-middle text-right">
                                    <span class="font-weight-bold">
                                        S/ {{ number_format($fondo->monto_capital, 2) }}
                                    </span>
                                </td>
                                <td class="align-middle text-right">
                                    <span class="font-weight-bold text-warning">
                                        S/ {{ number_format($fondo->monto_fondo, 2) }}
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <small class="font-weight-bold">
                                        {{ $fondo->user->name ?? $fondo->asesor->codigo ?? 'N/A' }}
                                    </small>
                                </td>
                                <td class="align-middle">
                                    <small>{{ $fondo->fecha_entrega->format('d/m/Y') }}</small>
                                </td>
                                <td class="align-middle">
                                    @if($fondo->operacion && $fondo->operacion->metodoDePago)
                                        @php
                                            $metodo = $fondo->operacion->metodoDePago->metodo_pago;
                                            $badgeClass = match($metodo) {
                                                'Efectivo' => 'success',
                                                'Yape' => 'purple',
                                                default => 'info',
                                            };
                                            $iconClass = match($metodo) {
                                                'Efectivo' => 'money-bill-wave',
                                                'Yape' => 'mobile-alt',
                                                default => 'credit-card',
                                            };
                                        @endphp
                                        <span class="badge badge-{{ $badgeClass }}">
                                            <i class="fas fa-{{ $iconClass }} mr-1"></i>{{ $metodo }}
                                        </span>
                                        @if($fondo->operacion->codigo)
                                            <br>
                                            <small class="text-muted">
                                                #{{ $fondo->operacion->codigo }}
                                            </small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="align-middle text-center">
                                    @if($fondo->estado === \App\Models\FondoProvisional::ESTADO_ENTREGADO)
                                        <span class="badge badge-warning px-2 py-1">
                                            <i class="fas fa-clock mr-1"></i>Entregado
                                        </span>
                                    @elseif($fondo->estado === \App\Models\FondoProvisional::ESTADO_RENDIDO)
                                        <span class="badge badge-success px-2 py-1">
                                            <i class="fas fa-check-circle mr-1"></i>Rendido
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.fondo-provisional.show', $fondo->id) }}"
                                           class="btn btn-outline-primary" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($fondo->puedeSerRendido())
                                        <button type="button"
                                                class="btn btn-outline-success"
                                                data-toggle="modal"
                                                data-target="#modalRendir{{ $fondo->id }}"
                                                title="Marcar como rendido">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <!-- Modal para marcar como rendido -->
                            @if($fondo->puedeSerRendido())
                            <div class="modal fade" id="modalRendir{{ $fondo->id }}" tabindex="-1" role="dialog">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success text-white">
                                            <h5 class="modal-title">
                                                <i class="fas fa-check mr-2"></i>Marcar como Rendido
                                            </h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">
                                                <span>&times;</span>
                                            </button>
                                        </div>
                                        <form action="{{ route('admin.fondo-provisional.marcar-rendido', $fondo->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="alert alert-light border mb-3">
                                                    <div class="row text-sm">
                                                        <div class="col-6">
                                                            <strong>Fondo:</strong><br>
                                                            <span class="text-warning font-weight-bold">S/ {{ number_format($fondo->monto_fondo, 2) }}</span>
                                                        </div>
                                                        <div class="col-6">
                                                            <strong>Cliente:</strong><br>
                                                            {{ $fondo->prestamo?->cliente?->persona?->nombres ?? 'N/A' }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group mb-0">
                                                    <label for="fecha_rendicion_{{ $fondo->id }}" class="font-weight-bold">
                                                        Fecha de Rendicion
                                                    </label>
                                                    <input type="date"
                                                           name="fecha_rendicion"
                                                           id="fecha_rendicion_{{ $fondo->id }}"
                                                           class="form-control"
                                                           value="{{ date('Y-m-d') }}"
                                                           required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-default" data-dismiss="modal">
                                                    Cancelar
                                                </button>
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-check mr-1"></i>Confirmar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginacion -->
                <div class="card-footer clearfix">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            Mostrando {{ $fondos->firstItem() }} a {{ $fondos->lastItem() }}
                            de {{ $fondos->total() }} registros
                        </small>
                        <div>
                            {{ $fondos->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-piggy-bank fa-3x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">No se encontraron fondos provisionales</h5>
                    <p class="text-muted mb-3">No hay registros con los filtros seleccionados.</p>
                    @if(request()->hasAny(['estado', 'fecha_desde', 'fecha_hasta']))
                        <a href="{{ route('admin.fondo-provisional.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times mr-1"></i>Limpiar filtros
                        </a>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .border-left-warning { border-left: 4px solid #ffc107 !important; }
    .border-left-success { border-left: 4px solid #28a745 !important; }
    .border-left-info { border-left: 4px solid #17a2b8 !important; }
    .border-left-secondary { border-left: 4px solid #6c757d !important; }

    .opacity-50 { opacity: 0.4; }

    .badge-purple {
        background-color: #7c3aed;
        color: #fff;
    }

    .table thead th {
        border-top: none;
        border-bottom: 2px solid #dee2e6;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        white-space: nowrap;
    }

    .table td {
        vertical-align: middle;
        font-size: 0.875rem;
    }

    .table tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.04);
    }

    .btn-xs {
        padding: 0.15rem 0.4rem;
        font-size: 0.75rem;
    }

    .card {
        border: none;
        border-radius: 0.5rem;
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #eee;
    }

    .modal-dialog-centered {
        display: flex;
        align-items: center;
        min-height: calc(100% - 1rem);
    }

    .pagination { margin-bottom: 0; }

    @media (max-width: 768px) {
        .table-responsive { font-size: 0.8rem; }
    }
</style>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const estadoSelect = document.getElementById('estado');
    if (estadoSelect) {
        estadoSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }

    const fechaDesde = document.getElementById('fecha_desde');
    const fechaHasta = document.getElementById('fecha_hasta');
    if (fechaDesde && fechaHasta) {
        fechaDesde.addEventListener('change', function() {
            fechaHasta.min = this.value;
        });
        fechaHasta.addEventListener('change', function() {
            fechaDesde.max = this.value;
        });
    }
});
</script>
@stop
