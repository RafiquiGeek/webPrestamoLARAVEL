@extends('layouts.admin')

@section('title', 'Convenios de Pago')

@section('content')
<div class="container-fluid">
    <!-- Header con estadísticas rápidas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-end">
                    <span class="badge bg-primary fs-6 px-3 py-2">
                        <i class="fas fa-list-ol me-1"></i>Total: {{ $convenios->total() }} convenios
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="row mb-4 g-2 g-md-3">
        <div class="col-6 col-lg-3">
            <div class="card border-left-success shadow h-100 conv-stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Convenios Activos</div>
                            <div class="h4 h2-xl mb-0 font-weight-bold text-gray-800">{{ $estadisticas['activos'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-play-circle fa-2x text-success conv-stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-left-primary shadow h-100 conv-stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Convenios Cumplidos</div>
                            <div class="h4 h2-xl mb-0 font-weight-bold text-gray-800">{{ $estadisticas['cumplidos'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-primary conv-stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-left-info shadow h-100 conv-stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Monto Total</div>
                            <div class="h4 h2-xl mb-0 font-weight-bold text-gray-800">S/. {{ number_format($estadisticas['totalMonto'], 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-info conv-stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-left-warning shadow h-100 conv-stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Progreso Promedio</div>
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="h4 h2-xl mb-0 mr-3 font-weight-bold text-gray-800">{{ number_format($estadisticas['promedioAvance'], 1) }}%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $estadisticas['promedioAvance'] }}%" aria-valuenow="{{ $estadisticas['promedioAvance'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto d-none d-md-block">
                            <i class="fas fa-clipboard-list fa-2x text-warning conv-stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y búsqueda -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body py-3">
                    <form method="GET" action="{{ route('admin.convenios.index') }}" id="filterForm">
                        <div class="row align-items-center g-2">
                            <div class="col-12 col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text"
                                           class="form-control"
                                           name="search"
                                           id="searchInput"
                                           value="{{ request('search') }}"
                                           placeholder="Buscar por cliente, DNI o ID...">
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <select class="form-select input-group-text" name="estado" id="estadoFilter" onchange="this.form.submit()">
                                    <option value="">Todos los estados</option>
                                    <option value="ACTIVO" {{ request('estado') == 'ACTIVO' ? 'selected' : '' }}>Activos</option>
                                    <option value="CUMPLIDO" {{ request('estado') == 'CUMPLIDO' ? 'selected' : '' }}>Cumplidos</option>
                                    <option value="INCUMPLIDO" {{ request('estado') == 'INCUMPLIDO' ? 'selected' : '' }}>Incumplidos</option>
                                    <option value="CANCELADO" {{ request('estado') == 'CANCELADO' ? 'selected' : '' }}>Cancelados</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <select class="form-select input-group-text" name="progreso" id="progresoFilter" onchange="this.form.submit()">
                                    <option value="">Todos los progresos</option>
                                    <option value="0-25" {{ request('progreso') == '0-25' ? 'selected' : '' }}>0% - 25%</option>
                                    <option value="26-50" {{ request('progreso') == '26-50' ? 'selected' : '' }}>26% - 50%</option>
                                    <option value="51-75" {{ request('progreso') == '51-75' ? 'selected' : '' }}>51% - 75%</option>
                                    <option value="76-100" {{ request('progreso') == '76-100' ? 'selected' : '' }}>76% - 100%</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <a href="{{ route('admin.convenios.index') }}" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-times me-1"></i>Limpiar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla principal -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <!--div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Listado de Convenios</h6>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-1"></i>Opciones
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="exportTable('excel')">
                                <i class="fas fa-file-excel me-2 text-success"></i>Exportar Excel
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="exportTable('pdf')">
                                <i class="fas fa-file-pdf me-2 text-danger"></i>Exportar PDF
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="refreshTable()">
                                <i class="fas fa-sync me-2"></i>Actualizar
                            </a></li>
                        </ul>
                    </div>
                </div-->

                <div class="card-body p-0">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($convenios->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0" id="conveniosTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center sortable" data-column="id">
                                            <i class="fas fa-hashtag me-1"></i>ID
                                            <i class="fas fa-sort sort-icon"></i>
                                        </th>
                                        <th class="sortable" data-column="cliente">
                                            <i class="fas fa-user me-1"></i>Cliente
                                            <i class="fas fa-sort sort-icon"></i>
                                        </th>
                                        <th class="text-center sortable d-none d-md-table-cell" data-column="prestamo">
                                            <i class="fas fa-link me-1"></i>Préstamo
                                            <i class="fas fa-sort sort-icon"></i>
                                        </th>
                                        <th class="text-center sortable d-none d-md-table-cell" data-column="total">
                                            <i class="fas fa-dollar-sign me-1"></i>Total
                                            <i class="fas fa-sort sort-icon"></i>
                                        </th>
                                        <th class="text-center d-none d-lg-table-cell">
                                            <i class="fas fa-tags me-1"></i>Tipo / Plan
                                        </th>
                                        <th class="text-center sortable" data-column="estado">
                                            <i class="fas fa-flag me-1"></i>Estado
                                            <i class="fas fa-sort sort-icon"></i>
                                        </th>
                                        <th class="text-center sortable d-none d-lg-table-cell" data-column="progreso">
                                            <i class="fas fa-chart-line me-1"></i>Progreso
                                            <i class="fas fa-sort sort-icon"></i>
                                        </th>
                                        <th class="text-center sortable d-none d-lg-table-cell" data-column="fecha">
                                            <i class="fas fa-calendar-check me-1"></i>Fecha
                                            <i class="fas fa-sort sort-icon"></i>
                                        </th>
                                        <th class="text-center">
                                            <i class="fas fa-cogs me-1"></i>Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($convenios as $convenio)
                                        <tr data-estado="{{ $convenio->estado->label() }}" data-progreso="{{ $convenio->porcentaje_avance }}">
                                            <td class="text-center fw-bold">
                                                <span class="badge">#{{ $convenio->id }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <div class="fw-bold text-dark">
                                                            <a href="{{ route('admin.convenios.show', $convenio->id) }}">
                                                            {{ $convenio->prestamo?->cliente?->persona?->nombres }}
                                                            {{ $convenio->prestamo?->cliente?->persona?->ape_pat }}
                                                            {{ $convenio->prestamo?->cliente?->persona?->ape_mat }}
                                                            </a>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="fas fa-id-card me-1 mr-2"></i>{{ $convenio->prestamo?->cliente?->persona?->documento }}
                                                        </small>
                                                        <!-- Info compacta visible solo en mobile -->
                                                        <div class="d-md-none mt-1">
                                                            <small class="fw-bold text-success">S/. {{ number_format($convenio->total_convenio, 2) }}</small>
                                                            <small class="text-muted ms-2">{{ number_format($convenio->porcentaje_avance, 0) }}%</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center d-none d-md-table-cell">
                                                <a href="{{ route('admin.prestamos.show', $convenio->prestamo_id) }}"
                                                   class="badge text-decoration-none">
                                                    <i class="fas fa-external-link-alt mr-2"></i>#{{ $convenio->prestamo_id }}
                                                </a>
                                            </td>
                                            <td class="text-center d-none d-md-table-cell">
                                                <div class="d-flex flex-column align-items-center">
                                                    <span class="fw-bold text-success h6 mb-1">S/. {{ number_format($convenio->total_convenio, 2) }}</span>
                                                    <div class="small text-muted">
                                                        <div>Capital: S/. {{ number_format($convenio->monto_capital, 2) }}</div>
                                                        @if($convenio->descuento_moras > 0)
                                                            <div class="text-primary">Desc: S/. {{ number_format($convenio->descuento_moras, 2) }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center d-none d-lg-table-cell">
                                                @if($convenio->esTipoFlexible())
                                                    <span class="badge bg-info text-white"><i class="fas fa-random me-1"></i>Flexible</span>
                                                @else
                                                    <div class="d-flex flex-column align-items-center">
                                                        <span class="badge bg-primary text-white mb-1" style="font-size: 0.65rem;"><i class="fas fa-list-ol me-1"></i>Cuotas</span>
                                                        <small class="text-muted">S/. {{ number_format($convenio->valor_cuota, 2) }}</small>
                                                        <span class="badge bg-light">{{ $convenio->numero_cuotas }} sem.</span>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge text-white bg-{{ $convenio->estado === \App\Enums\ConvenioEstado::ACTIVO ? 'success' :
                                                    ($convenio->estado === \App\Enums\ConvenioEstado::CUMPLIDO ? 'primary' :
                                                    ($convenio->estado === \App\Enums\ConvenioEstado::INCUMPLIDO ? 'danger' : 'secondary')) }}">
                                                    {{ $convenio->estado->label() }}
                                                </span>
                                            </td>
                                            <td class="text-center d-none d-lg-table-cell">
                                                @php
                                                    $porcentaje = $convenio->porcentaje_avance;
                                                    $colorBarra = $porcentaje >= 75 ? 'success' : ($porcentaje >= 50 ? 'warning' : ($porcentaje >= 25 ? 'info' : 'danger'));
                                                @endphp
                                                <div class="progress mb-2" style="height: 8px;">
                                                    <div class="progress-bar bg-{{ $colorBarra }}"
                                                         role="progressbar"
                                                         style="width: {{ $porcentaje }}%"
                                                         title="{{ number_format($porcentaje, 1) }}%">
                                                    </div>
                                                </div>
                                                <div class="small">
                                                    <div class="fw-bold">{{ number_format($porcentaje, 1) }}%</div>
                                                    <div class="text-muted">S/. {{ number_format($convenio->monto_total_pagado, 2) }}</div>
                                                </div>
                                            </td>
                                            <td class="text-center d-none d-lg-table-cell">
                                                <div class="fw-bold">{{ $convenio->fecha_firma->format('d/m/Y') }}</div>
                                                <small class="text-muted">{{ $convenio->fecha_firma->format('H:i') }}</small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.convenios.show', $convenio->id) }}"
                                                       class="btn btn-outline-primary btn-sm border-0"
                                                       title="Ver Detalle"
                                                       data-bs-toggle="tooltip">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    @if($convenio->estado === \App\Enums\ConvenioEstado::ACTIVO)
                                                        <a href="{{ route('admin.convenios.edit', $convenio->id) }}"
                                                           class="btn btn-outline-warning btn-sm"
                                                           title="Editar"
                                                           data-bs-toggle="tooltip">
                                                            <i class="fas fa-edit"></i>
                                                        </a>

                                                        <button type="button"
                                                                class="btn btn-outline-danger btn-sm"
                                                                title="Cancelar Convenio"
                                                                data-bs-toggle="tooltip"
                                                                onclick="cancelarConvenio({{ $convenio->id }}, '{{ route('admin.convenios.cancelar', $convenio->id) }}')">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    @else
                                                        <button class="btn btn-outline-secondary btn-sm" disabled title="No disponible">
                                                            <i class="fas fa-lock"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación mejorada -->
                        <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                            <div class="text-muted small">
                                Mostrando {{ $convenios->firstItem() }} - {{ $convenios->lastItem() }}
                                de {{ $convenios->total() }} convenios
                            </div>
                            <div>
                                {{ $convenios->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-handshake fa-4x text-muted opacity-50"></i>
                            </div>
                            <h4 class="text-muted mb-3">No hay convenios de pago registrados</h4>
                            <p class="text-muted mb-4">Los convenios aparecerán aquí una vez que sean creados desde los préstamos con cuotas vencidas.</p>
                            <a href="{{ route('admin.prestamos.index') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Ver Préstamos
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de carga -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <div class="mt-2">Procesando...</div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('css')
<style>
    .border-left-success {
        border-left: 0.25rem solid #1cc88a !important;
    }
    .border-left-primary {
        border-left: 0.25rem solid #4e73df !important;
    }
    .border-left-info {
        border-left: 0.25rem solid #36b9cc !important;
    }
    .border-left-warning {
        border-left: 0.25rem solid #f6c23e !important;
    }

    .progress-sm {
        height: 0.5rem;
    }

    .avatar-sm {
        width: 40px;
        height: 40px;
    }

    .sortable {
        cursor: pointer;
        user-select: none;
        position: relative;
        transition: background-color 0.2s;
    }

    .sortable:hover {
        background-color: #f8f9fa;
    }

    .sort-icon {
        opacity: 0.3;
        font-size: 0.75rem;
        margin-left: 5px;
    }

    .sortable.asc .sort-icon::before {
        content: "\f0de";
        opacity: 1;
        color: #007bff;
    }

    .sortable.desc .sort-icon::before {
        content: "\f0dd";
        opacity: 1;
        color: #007bff;
    }

    .table tbody tr {
        transition: all 0.2s;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .btn-group .btn {
        margin-right: 2px;
    }

    .btn-group .btn:last-child {
        margin-right: 0;
    }

    .card {
        transition: all 0.3s;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .progress {
        border-radius: 10px;
        overflow: hidden;
    }

    .badge {
        font-size: 0.75rem;
    }

    /* Stat cards responsive */
    .conv-stat-card {
        padding: 0.5rem;
    }

    .conv-stat-card .card-body {
        padding: 0.75rem;
    }

    @media (min-width: 1200px) {
        .conv-stat-card {
            padding: 1rem;
        }
        .conv-stat-card .card-body {
            padding: 1.25rem;
        }
    }

    @media (max-width: 767px) {
        .conv-stat-card .h4 {
            font-size: 1.1rem;
        }
        .conv-stat-icon {
            font-size: 1.25rem !important;
        }
    }

    /* Responsive improvements */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.85rem;
        }

        .btn-group {
            flex-direction: row;
            gap: 2px;
        }

        .btn-group .btn {
            padding: 0.2rem 0.4rem;
            font-size: 0.75rem;
        }
    }

    /* Loading states */
    .table.loading {
        opacity: 0.6;
        pointer-events: none;
    }

    /* Search highlight */
    .highlight {
        background-color: #fff3cd;
        padding: 1px 3px;
        border-radius: 3px;
    }

    /* Stats cards hover effect */
    .card.border-left-success:hover,
    .card.border-left-primary:hover,
    .card.border-left-info:hover,
    .card.border-left-warning:hover {
        border-left-width: 0.5rem !important;
    }
</style>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Configurar ordenamiento de tabla
    setupTableSorting();

    // Enviar formulario al presionar Enter en el campo de búsqueda
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('filterForm').submit();
        }
    });
});

// Configurar ordenamiento
function setupTableSorting() {
    const headers = document.querySelectorAll('.sortable');
    headers.forEach(header => {
        header.addEventListener('click', function() {
            sortTable(this);
        });
    });
}

// Ordenar tabla
function sortTable(header) {
    const table = document.getElementById('conveniosTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => row.style.display !== 'none');
    const column = header.getAttribute('data-column');
    const isAsc = header.classList.contains('asc');

    // Limpiar clases de ordenamiento anteriores
    document.querySelectorAll('.sortable').forEach(h => {
        h.classList.remove('asc', 'desc');
    });

    // Aplicar nueva clase de ordenamiento
    header.classList.add(isAsc ? 'desc' : 'asc');

    // Ordenar filas
    rows.sort((a, b) => {
        let aVal, bVal;

        switch(column) {
            case 'id':
                aVal = parseInt(a.cells[0].textContent.replace('#', ''));
                bVal = parseInt(b.cells[0].textContent.replace('#', ''));
                break;
            case 'cliente':
                aVal = a.cells[1].textContent.trim();
                bVal = b.cells[1].textContent.trim();
                break;
            case 'total':
                aVal = parseFloat(a.cells[3].textContent.replace(/[S\/\.,\s]/g, ''));
                bVal = parseFloat(b.cells[3].textContent.replace(/[S\/\.,\s]/g, ''));
                break;
            case 'progreso':
                aVal = parseFloat(a.getAttribute('data-progreso'));
                bVal = parseFloat(b.getAttribute('data-progreso'));
                break;
            case 'fecha':
                aVal = new Date(a.cells[7].textContent.split('/').reverse().join('-'));
                bVal = new Date(b.cells[7].textContent.split('/').reverse().join('-'));
                break;
            default:
                aVal = a.cells[3].textContent.trim();
                bVal = b.cells[3].textContent.trim();
        }

        if (typeof aVal === 'string') {
            return isAsc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
        } else {
            return isAsc ? aVal - bVal : bVal - aVal;
        }
    });

    // Reordenar DOM
    rows.forEach(row => tbody.appendChild(row));
}

// Cancelar convenio
function cancelarConvenio(convenioId, cancelarUrl) {
    if (confirm('¿Está seguro de cancelar este convenio de pago?\n\nEsta acción no se puede deshacer.')) {
        showLoading();

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = cancelarUrl;

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);

        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'PATCH';
        form.appendChild(methodField);

        document.body.appendChild(form);
        form.submit();
    }
}

// Exportar tabla
function exportTable(format) {
    showLoading();

    setTimeout(() => {
        hideLoading();
        alert('Función de exportación en desarrollo');
    }, 1000);
}

// Actualizar tabla
function refreshTable() {
    showLoading();
    location.reload();
}

// Mostrar loading
function showLoading() {
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
    loadingModal.show();
}

// Ocultar loading
function hideLoading() {
    const loadingModal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
    if (loadingModal) {
        loadingModal.hide();
    }
}

</script>
@endsection