@extends('layouts.admin')

@section('title', 'Operaciones')

@section('content_header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">
                        <i class="fas fa-exchange-alt"></i> Operaciones
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ url('/admin') }}">Inicio</a></li>
                        <li class="breadcrumb-item active">Operaciones</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@stop

@section('content')
    @if (session('info'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('info') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="container-fluid">
        <!-- Filtros en una línea -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('admin.operaciones.index') }}" id="filterForm">
                    <div class="row g-2 align-items-end">
                        <!-- Búsqueda por Cliente -->
                        <div class="col-md-3">
                            <label for="cliente_id" class="form-label small mb-1">
                                <i class="fas fa-user text-primary"></i> Cliente
                            </label>
                            <select name="cliente_id" id="cliente_id" class="form-control form-control-sm select2-cliente">
                                @if(request()->query('cliente_id'))
                                    @php
                                        $clienteSeleccionado = \App\Models\Cliente::find(request()->query('cliente_id'));
                                    @endphp
                                    @if($clienteSeleccionado)
                                        <option value="{{ $clienteSeleccionado->id }}" selected>
                                            {{ $clienteSeleccionado->persona->nombres . ' ' . $clienteSeleccionado->persona->ape_pat }} - {{ $clienteSeleccionado->persona->documento }}
                                        </option>
                                    @endif
                                @endif
                            </select>
                        </div>

                        <!-- Tipo Operación -->
                        <div class="col-md-2">
                            <label for="tipo_operacion" class="form-label small mb-1">
                                <i class="fas fa-list text-warning"></i> Tipo
                            </label>
                            <select name="tipo_operacion" id="tipo_operacion" class="form-control form-control-sm">
                                <option value="">Todos</option>
                                <option value="Desembolso" {{ request('tipo_operacion') == 'Desembolso' ? 'selected' : '' }}>Desembolso</option>
                                <option value="Pago de cuota" {{ request('tipo_operacion') == 'Pago de cuota' ? 'selected' : '' }}>Pago de cuota</option>
                                <option value="Fondo Provisional" {{ request('tipo_operacion') == 'Fondo Provisional' ? 'selected' : '' }}>Fondo Provisional</option>
                                <option value="Pago de mora" {{ request('tipo_operacion') == 'Pago de mora' ? 'selected' : '' }}>Pago de mora</option>
                                <option value="Abono a cuota" {{ request('tipo_operacion') == 'Abono a cuota' ? 'selected' : '' }}>Abono a cuota</option>
                                <option value="Abono a favor" {{ request('tipo_operacion') == 'Abono a favor' ? 'selected' : '' }}>Abono a favor</option>
                            </select>
                        </div>

                        <!-- Estado Préstamo -->
                        <div class="col-md-2">
                            <label for="estado_prestamo" class="form-label small mb-1">
                                <i class="fas fa-chart-line text-info"></i> Estado
                            </label>
                            <select name="estado_prestamo" id="estado_prestamo" class="form-control form-control-sm">
                                <option value="">Todos</option>
                                <option value="Vigente" {{ request('estado_prestamo') == 'Vigente' ? 'selected' : '' }}>Vigente</option>
                                <option value="Moroso" {{ request('estado_prestamo') == 'Moroso' ? 'selected' : '' }}>Moroso</option>
                                <option value="Finalizado" {{ request('estado_prestamo') == 'Finalizado' ? 'selected' : '' }}>Finalizado</option>
                                <option value="Liquidado" {{ request('estado_prestamo') == 'Liquidado' ? 'selected' : '' }}>Liquidado</option>
                            </select>
                        </div>

                        <!-- Desde -->
                        <div class="col-md-2">
                            <label for="fecha_inicio" class="form-label small mb-1">
                                <i class="fas fa-calendar-alt text-success"></i> Desde
                            </label>
                            <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control form-control-sm" value="{{ request('fecha_inicio') }}">
                        </div>

                        <!-- Hasta -->
                        <div class="col-md-2">
                            <label for="fecha_fin" class="form-label small mb-1">
                                <i class="fas fa-calendar-check text-success"></i> Hasta
                            </label>
                            <input type="date" name="fecha_fin" id="fecha_fin" class="form-control form-control-sm" value="{{ request('fecha_fin') }}">
                        </div>

                        <!-- Botones -->
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm btn-block">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <a href="{{ route('admin.operaciones.index') }}" class="btn btn-outline-secondary btn-sm btn-block mt-1">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Listado de Operaciones -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list-ul"></i> Listado de Operaciones
                    </h5>
                    <span class="badge badge-primary badge-pill px-3 py-2">
                        {{ number_format($operaciones->total()) }} resultados
                    </span>
                </div>
            </div>

            <div class="card-body p-0">
                @forelse($operaciones as $operacion)
                    <div class="operacion-card {{ $operacion->estado === 'anulado' ? 'anulada' : '' }}">
                        <div class="operacion-main">
                            <!-- Icono -->
                            <div class="operacion-icon">
                                @if($operacion->tipo_operacion == 'Pago de cuota')
                                    <i class="fas fa-receipt"></i>
                                @elseif($operacion->tipo_operacion == 'Desembolso')
                                    <i class="fas fa-money-bill-wave"></i>
                                @elseif($operacion->tipo_operacion == 'Fondo Provisional')
                                    <i class="fas fa-piggy-bank"></i>
                                @else
                                    <i class="fas fa-coins"></i>
                                @endif
                            </div>

                            <!-- Información -->
                            <div class="operacion-content">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="info-label">Cliente</div>
                                        <div class="info-value">{{ Str::limit($operacion->cliente->persona->nombres . ' ' . $operacion->cliente->persona->ape_pat, 30) }}</div>
                                        <div class="info-meta">DNI: {{ $operacion->cliente->persona->documento }}</div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="info-label">Fecha</div>
                                        <div class="info-value">{{ $operacion->fecha->format('d/m/Y') }}</div>
                                        <div class="info-meta">{{ $operacion->fecha->format('H:i') }}</div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="info-label">Préstamo</div>
                                        <div class="info-value">
                                            #{{ $operacion->prestamo->id ?? 'N/A' }}
                                            @if($operacion->prestamo)
                                                <span class="badge badge-estado badge-{{ strtolower($operacion->prestamo->estado) }}">
                                                    {{ $operacion->prestamo->estado }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="info-label">Tipo</div>
                                        <div class="info-value">
                                            {{ $operacion->tipo_operacion }}
                                            @if($operacion->tipo_operacion == 'Fondo Provisional')
                                                <span class="badge badge-warning badge-sm ml-1">
                                                    <i class="fas fa-piggy-bank"></i>
                                                </span>
                                            @endif
                                        </div>
                                        @if($operacion->operacionesRelacionadas->count() > 0)
                                            <div class="info-meta">
                                                <i class="fas fa-layer-group"></i> {{ $operacion->operacionesRelacionadas->count() }} sub-ops
                                            </div>
                                        @elseif($operacion->metodoDePago)
                                            <div class="info-meta">
                                                <i class="fas fa-{{ $operacion->metodoDePago->metodo_pago == 'Efectivo' ? 'money-bill-wave' : ($operacion->metodoDePago->metodo_pago == 'Yape' ? 'mobile-alt' : 'credit-card') }}"></i>
                                                {{ $operacion->metodoDePago->metodo_pago }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-2">
                                        <div class="info-label">Monto</div>
                                        <div class="info-value text-success">S/ {{ number_format($operacion->abono, 2) }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Acción -->
                            <div class="operacion-actions">
                                @if(($operacion->estado ?? 'activo') !== 'anulado')
                                    <button type="button"
                                            class="btn btn-sm btn-info rounded-pill"
                                            onclick="verDetalle({{ $operacion->id }})"
                                            data-toggle="tooltip"
                                            title="Ver detalles">
                                        <i class="fas fa-eye"></i> Ver
                                    </button>
                                @else
                                    <span class="badge badge-danger px-3 py-2">
                                        <i class="fas fa-ban"></i> Anulada
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No se encontraron operaciones</h5>
                        <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                    </div>
                @endforelse
            </div>

            <!-- Paginación -->
            @if($operaciones->hasPages() || $operaciones->total() > 0)
                <div class="card-footer bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <span class="text-muted">
                                Mostrando {{ $operaciones->firstItem() ?? 0 }} - {{ $operaciones->lastItem() ?? 0 }}
                                de {{ number_format($operaciones->total()) }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end">
                                {{ $operaciones->appends(request()->query())->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal de Detalle -->
    <div class="modal fade" id="detalleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-file-invoice-dollar"></i> Detalle de Operación
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="detalleContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Cargando...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css" rel="stylesheet" />

    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .operacion-card {
            border-bottom: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }

        .operacion-card:hover {
            background: #f8f9fa;
        }

        .operacion-card.anulada {
            opacity: 0.6;
            background: #fff5f5;
        }

        .operacion-main {
            display: flex;
            align-items: center;
            padding: 1rem;
            gap: 1rem;
        }

        .operacion-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .operacion-content {
            flex: 1;
        }

        .info-label {
            font-size: 0.7rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 0.2rem;
        }

        .info-value {
            font-size: 0.9rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 0.2rem;
        }

        .info-meta {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .badge-estado {
            font-size: 0.65rem;
            padding: 2px 6px;
            border-radius: 8px;
            font-weight: 600;
            margin-left: 4px;
        }

        .badge-vigente {
            background: #d4edda;
            color: #155724;
        }

        .badge-moroso {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-finalizado {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-liquidado {
            background: #e2e3e5;
            color: #383d41;
        }

        .operacion-actions {
            flex-shrink: 0;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        /* Modal Styles */
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f3f5;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
        }

        .detail-value {
            color: #212529;
        }

        @media (max-width: 768px) {
            .operacion-main {
                flex-direction: column;
                align-items: stretch;
            }

            .operacion-icon {
                align-self: center;
            }
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializar Select2 con búsqueda optimizada
            $('.select2-cliente').select2({
                theme: 'bootstrap4',
                language: 'es',
                placeholder: 'Buscar por nombre o DNI...',
                allowClear: true,
                width: '100%',
                minimumInputLength: 2,
                ajax: {
                    url: '{{ route("admin.operaciones.buscar-cliente") }}',
                    dataType: 'json',
                    delay: 300,
                    data: function (params) {
                        return {
                            q: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results,
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true
                },
                templateResult: function(cliente) {
                    if (cliente.loading) return cliente.text;
                    return $('<div><strong>' + cliente.nombre + '</strong><br><small class="text-muted">DNI: ' + cliente.dni + '</small></div>');
                },
                templateSelection: function(cliente) {
                    return cliente.nombre || cliente.text;
                }
            });

            // Tooltips
            $('[data-toggle="tooltip"]').tooltip();
        });

        // Ver detalle en modal
        function verDetalle(operacionId) {
            $('#detalleModal').modal('show');

            // Mostrar spinner mientras carga
            $('#detalleContent').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                </div>
            `);

            // Cargar contenido con AJAX
            $.ajax({
                url: '{{ route("admin.operaciones.index") }}/' + operacionId,
                method: 'GET',
                dataType: 'html',
                success: function(response) {
                    // Extraer solo el contenido del modal del HTML completo
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(response, 'text/html');
                    const content = doc.querySelector('.container-fluid');

                    if (content) {
                        $('#detalleContent').html(content.innerHTML);
                    } else {
                        $('#detalleContent').html('<div class="alert alert-warning">No se pudo cargar el contenido</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al cargar operación:', {xhr, status, error});
                    let errorMsg = 'Error al cargar los detalles';
                    if (xhr.status === 404) {
                        errorMsg = 'Operación no encontrada';
                    } else if (xhr.status === 500) {
                        errorMsg = 'Error del servidor';
                    }
                    $('#detalleContent').html(`<div class="alert alert-danger">${errorMsg}</div>`);
                }
            });
        }
    </script>
@stop
