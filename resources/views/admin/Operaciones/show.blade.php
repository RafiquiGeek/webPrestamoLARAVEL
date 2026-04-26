@extends('layouts.admin')

@section('title', 'Detalle de Operación')

@section('content_header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">
                        <i class="fas fa-file-invoice-dollar"></i> Detalle de Operación #{{ $operacion->id }}
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ url('/admin') }}">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.operaciones.index') }}">Operaciones</a></li>
                        <li class="breadcrumb-item active">Detalle #{{ $operacion->id }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <!-- Columna Principal -->
            <div class="col-lg-8">
                <!-- Información General -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-gradient-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle"></i> Información General
                            </h5>
                            @if(($operacion->estado ?? 'activo') === 'anulado')
                                <span class="badge badge-danger badge-lg">
                                    <i class="fas fa-ban"></i> OPERACIÓN ANULADA
                                </span>
                            @else
                                <span class="badge badge-success badge-lg">
                                    <i class="fas fa-check-circle"></i> ACTIVA
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="detail-group">
                                    <div class="detail-label">
                                        <i class="fas fa-hashtag text-primary"></i> ID Operación
                                    </div>
                                    <div class="detail-value">#{{ $operacion->id }}</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="detail-group">
                                    <div class="detail-label">
                                        <i class="fas fa-tag text-info"></i> Tipo de Operación
                                    </div>
                                    <div class="detail-value">{{ $operacion->tipo_operacion }}</div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="detail-group">
                                    <div class="detail-label">
                                        <i class="fas fa-calendar-alt text-success"></i> Fecha y Hora
                                    </div>
                                    <div class="detail-value">
                                        {{ $operacion->fecha->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="detail-group">
                                    <div class="detail-label">
                                        <i class="fas fa-money-bill-wave text-success"></i> Monto
                                    </div>
                                    <div class="detail-value text-success font-weight-bold">
                                        S/ {{ number_format($operacion->abono, 2) }}
                                    </div>
                                </div>
                            </div>

                            @if($operacion->observaciones)
                                <div class="col-12 mb-3">
                                    <div class="detail-group">
                                        <div class="detail-label">
                                            <i class="fas fa-comment-alt text-warning"></i> Observaciones
                                        </div>
                                        <div class="detail-value">
                                            <div class="alert alert-light border-left-warning mb-0">
                                                {{ $operacion->observaciones }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Información del Préstamo -->
                @if($operacion->prestamo)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-file-contract"></i> Información del Préstamo
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="detail-group">
                                    <div class="detail-label">ID Préstamo</div>
                                    <div class="detail-value">
                                        <a href="{{ route('admin.prestamos.show', $operacion->prestamo->id) }}" class="text-primary">
                                            #{{ $operacion->prestamo->id }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="detail-group">
                                    <div class="detail-label">Estado</div>
                                    <div class="detail-value">
                                        <span class="badge badge-{{ strtolower($operacion->prestamo->estado) == 'vigente' ? 'success' : (strtolower($operacion->prestamo->estado) == 'moroso' ? 'danger' : 'secondary') }}">
                                            {{ $operacion->prestamo->estado }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="detail-group">
                                    <div class="detail-label">Monto Préstamo</div>
                                    <div class="detail-value">S/ {{ number_format($operacion->prestamo->cantidad_solicitada, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Sub-operaciones -->
                @if($operacion->operacionesRelacionadas && $operacion->operacionesRelacionadas->count() > 0)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-layer-group"></i> Sub-operaciones
                            <span class="badge badge-primary ml-2">{{ $operacion->operacionesRelacionadas->count() }}</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Referencia</th>
                                        <th class="text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($operacion->operacionesRelacionadas as $relacionada)
                                        <tr>
                                            <td>
                                                <span class="badge badge-{{ $relacionada->tipo_operacion == 'Pago de cuota' ? 'info' : 'warning' }}">
                                                    {{ $relacionada->tipo_operacion }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($relacionada->cuotas && $relacionada->cuotas->isNotEmpty())
                                                    @foreach($relacionada->cuotas as $cuota)
                                                        <span class="badge badge-light">Cuota #{{ $cuota->numero }}</span>
                                                    @endforeach
                                                @endif
                                                @if($relacionada->morasCuota && $relacionada->morasCuota->isNotEmpty())
                                                    <span class="badge badge-warning">Mora</span>
                                                @endif
                                            </td>
                                            <td class="text-right font-weight-bold text-success">
                                                S/ {{ number_format($relacionada->abono, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="2" class="text-right font-weight-bold">Total:</td>
                                        <td class="text-right font-weight-bold text-success">
                                            S/ {{ number_format($operacion->abono, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Columna Lateral -->
            <div class="col-lg-4">
                <!-- Información del Cliente -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-gradient-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user"></i> Cliente
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($operacion->cliente && $operacion->cliente->persona)
                            <div class="text-center mb-3">
                                <div class="client-avatar">
                                    <i class="fas fa-user-circle fa-4x text-primary"></i>
                                </div>
                                <h5 class="mt-3 mb-1">
                                    {{ $operacion->cliente->persona->nombres }}
                                    {{ $operacion->cliente->persona->ape_pat }}
                                    {{ $operacion->cliente->persona->ape_mat }}
                                </h5>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-id-card"></i> {{ $operacion->cliente->persona->documento }}
                                </p>
                            </div>
                            <hr>
                            <div class="client-info">
                                <div class="info-item">
                                    <i class="fas fa-phone text-success"></i>
                                    <span>{{ $operacion->cliente->persona->telefono ?? 'No registrado' }}</span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-envelope text-info"></i>
                                    <span>{{ $operacion->cliente->persona->email ?? 'No registrado' }}</span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('admin.clientes.show', $operacion->cliente->id) }}" class="btn btn-primary btn-block">
                                    <i class="fas fa-user-circle"></i> Ver Perfil del Cliente
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                @if(($operacion->estado ?? 'activo') !== 'anulado')
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-cog"></i> Acciones
                        </h5>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('admin.pagos.edit', $operacion->id) }}" class="btn btn-warning btn-block mb-2">
                            <i class="fas fa-edit"></i> Editar Operación
                        </a>
                        <button onclick="anularOperacion()" class="btn btn-danger btn-block">
                            <i class="fas fa-ban"></i> Anular Operación
                        </button>
                    </div>
                </div>
                @endif

                <!-- Información Adicional -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-info"></i> Información Adicional
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <i class="fas fa-calendar-plus text-success"></i>
                                <div>
                                    <small class="text-muted">Fecha de Creación</small>
                                    <p class="mb-0">{{ $operacion->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                            @if($operacion->updated_at && $operacion->updated_at != $operacion->created_at)
                            <div class="timeline-item">
                                <i class="fas fa-edit text-warning"></i>
                                <div>
                                    <small class="text-muted">Última Modificación</small>
                                    <p class="mb-0">{{ $operacion->updated_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botón Volver -->
        <div class="row mt-4">
            <div class="col-12">
                <a href="{{ route('admin.operaciones.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al Listado
                </a>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .bg-gradient-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        }

        .badge-lg {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .detail-group {
            margin-bottom: 0.5rem;
        }

        .detail-label {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-size: 1rem;
            font-weight: 600;
            color: #212529;
        }

        .border-left-warning {
            border-left: 4px solid #ffc107 !important;
        }

        .client-avatar {
            width: 80px;
            height: 80px;
            margin: 0 auto;
        }

        .client-info .info-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f3f5;
        }

        .client-info .info-item:last-child {
            border-bottom: none;
        }

        .client-info .info-item i {
            width: 20px;
            text-align: center;
        }

        .timeline {
            position: relative;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f3f5;
        }

        .timeline-item:last-child {
            border-bottom: none;
        }

        .timeline-item i {
            width: 24px;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .timeline-item div {
            flex: 1;
        }

        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeIn 0.4s ease;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function anularOperacion() {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción anulará la operación #{{ $operacion->id }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, anular operación',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ route('admin.pagos.anular', $operacion->id) }}";
                }
            });
        }
    </script>
@stop
