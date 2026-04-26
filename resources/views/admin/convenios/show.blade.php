@extends('layouts.admin')

@section('title', 'Convenio de Pago #' . $convenio->id)

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">
                        <i class="fas fa-handshake me-2 text-primary"></i>
                        Convenio N° {{ $convenio->id }}
                        <span class="badge bg-{{ $convenio->estado === \App\Enums\ConvenioEstado::ACTIVO ? 'success' :
                            ($convenio->estado === \App\Enums\ConvenioEstado::CUMPLIDO ? 'primary' : 'warning') }}">
                            {{ $convenio->estado->label() }}
                        </span>
                    </h4>
                    <div class="text-muted">
                        <strong>Cliente:</strong> {{ $convenio->prestamo->cliente->persona->nombres }}
                        {{ $convenio->prestamo->cliente->persona->ape_pat }}
                        {{ $convenio->prestamo->cliente->persona->ape_mat }}
                        <span class="ms-3"><strong>DNI:</strong> {{ $convenio->prestamo->cliente->persona->documento }}</span>
                    </div>
                    <div class="text-muted">
                        <strong>Dirección:</strong>
                        @php
                            $direccionObj = optional($convenio->prestamo->cliente->persona->direccion ?? null);
                            $direccion = $direccionObj ? trim(collect([
                                $direccionObj->direccion ?? null,
                                $direccionObj->numero ?? null
                            ])->filter()->implode(', ')) : null;
                            $referencia = $direccionObj && isset($direccionObj->referencia) ? $direccionObj->referencia : null;
                        @endphp
                        {{ $direccion ?? 'No especificada' }}
                        @if($referencia)
                            <span class="badge bg-light text-dark ms-2" style="font-size: 8pt; border: 1px solid #e9ecef; border-radius: 8px;">
                                {{ $referencia }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="text-end">
                    <a href="{{ route('admin.convenios.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                    <a href="{{ route('admin.convenios.estado-cuenta.preview', $convenio->id) }}" 
                       class="btn btn-outline-primary me-2"
                       target="_blank">
                        <i class="fas fa-file-pdf me-1"></i> Estado de Cuenta
                    </a>
                    @if($convenio->estado === \App\Enums\ConvenioEstado::ACTIVO)
                        @if($convenio->esTipoFlexible())
                            @if($convenio->saldo_pendiente > 0)
                                <a href="{{ route('admin.convenios.flexible.liquidar.form', $convenio->id) }}" class="btn btn-success me-2">
                                    <i class="fas fa-hand-holding-usd me-1"></i>Liquidar
                                </a>
                            @endif
                        @else
                            @php
                                $cuotasPendientes = $convenio->cuotasConvenio->whereIn('estado', [\App\Enums\CuotaConvenio::PENDIENTE, \App\Enums\CuotaConvenio::PARCIAL, \App\Enums\CuotaConvenio::VENCIDO]);
                            @endphp
                            @if($cuotasPendientes->count() > 0)
                                <a href="{{ route('admin.convenios.liquidar.form', $convenio->id) }}" class="btn btn-success me-2">
                                    <i class="fas fa-hand-holding-usd me-1"></i>Liquidar
                                </a>
                            @endif
                        @endif
                        <a href="{{ route('admin.convenios.edit', $convenio->id) }}" class="btn btn-outline-primary me-2">
                            <i class="fas fa-edit me-1"></i>Editar
                        </a>
                        <button type="button" class="btn btn-outline-danger" onclick="cancelarConvenio({{ $convenio->id }})">
                            <i class="fas fa-ban me-1"></i>Cancelar Convenio
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade-show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Main Information Cards -->
    <div class="row mb-3">
        <!-- Left Column - Loan Information -->
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-header py-2">
                    <h6 class="mb-0 small d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-info-circle me-2 text-primary"></i>Información del Convenio
                        </span>
                        <a href="{{ route('admin.prestamos.show', $convenio->prestamo_id) }}"
                           class="btn btn-sm btn-outline-primary"
                           target="_blank"
                           title="Ver préstamo completo">
                            <i class="fas fa-file-invoice-dollar me-1"></i>Ver Préstamo N° {{ $convenio->prestamo_id }}
                        </a>
                    </h6>
                </div>
                <div class="card-body py-3">
                    @php
                        // Usar el accessor que maneja tanto cuotas como flexible
                        $totalPagado = $convenio->monto_total_pagado;
                        $totalConvenio = $convenio->total_convenio;
                        $saldoPendiente = $totalConvenio - $totalPagado;
                    @endphp

                    <!-- Primera Fila -->
                    <div class="row g-2 mb-3">
                        <!--div class="col-md-3">
                            <div class="border rounded p-2 text-center">
                                <small class="text-muted d-block">Capital del Convenio</small>
                                <div class="fw-bold text-primary">S/ {{ number_format($convenio->monto_capital, 2) }}</div>
                            </div>
                        </div-->
                        <div class="col-md-3">
                            <div class="border rounded p-2 text-center">
                                <small class="text-muted d-block">Capital Original (Préstamo)</small>
                                <div class="fw-bold text-info">S/ {{ number_format($convenio->prestamo->cantidad_solicitada ?? 0, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-2 text-center">
                                <small class="text-muted d-block">Total del Convenio</small>
                                <div class="fw-bold text-success">S/ {{ number_format($convenio->total_convenio, 2) }}</div>
                            </div>
                        </div>
                        @if($convenio->esTipoCuotas())
                            <div class="col-md-3">
                                <div class="border rounded p-2 text-center">
                                    <small class="text-muted d-block">Plazo</small>
                                    <div class="fw-bold"><i class="fas fa-calendar me-1"></i>{{ $convenio->numero_cuotas }} Semanas</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-2 text-center">
                                    <small class="text-muted d-block">Valor por Cuota</small>
                                    <div class="fw-bold text-info">S/ {{ number_format($convenio->valor_cuota, 2) }}</div>
                                </div>
                            </div>
                        @else
                            <div class="col-md-3">
                                <div class="border rounded p-2 text-center">
                                    <small class="text-muted d-block">Tipo</small>
                                    <div class="fw-bold text-primary">
                                        <i class="fas fa-hand-holding-usd me-1"></i>Flexible
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Segunda Fila 
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <div class="border rounded p-2 text-center">
                                <small class="text-muted d-block">Total Convenio</small>
                                <div class="fw-bold text-warning">S/ {{ number_format($convenio->monto_moras, 2) }}</div>
                            </div>
                        </div>
                        
                        
                        <div class="col-md-3">
                            <div class="border rounded p-2 text-center">
                                <small class="text-muted d-block">Saldo Pendiente</small>
                                <div class="fw-bold {{ $saldoPendiente > 0 ? 'text-danger' : 'text-success' }}">S/ {{ number_format($saldoPendiente, 2) }}</div>
                            </div>
                        </div>
                    </div>-->

                    <!-- Tercera Fila -->
                    <div class="row g-2 mb-3">
                        @if($convenio->esTipoCuotas() && $convenio->fecha_inicio)
                            <div class="col-md-3">
                                <div class="border rounded p-2 text-center">
                                    <small class="text-muted d-block">Fecha de Inicio</small>
                                    <div class="small"><i class="fas fa-play text-success me-1"></i>{{ $convenio->fecha_inicio->format('d/m/Y') }}</div>
                                </div>
                            </div>
                        @endif
                        @if($convenio->fecha_firma)
                            <div class="col-md-3">
                                <div class="border rounded p-2 text-center">
                                    <small class="text-muted d-block">Fecha de Firma</small>
                                    <div class="small"><i class="fas fa-calendar text-primary me-1"></i>{{ $convenio->fecha_firma->format('d/m/Y') }}</div>
                                </div>
                            </div>
                        @endif
                        <!--div class="col-md-3">
                            <div class="border rounded p-2 text-center">
                                <small class="text-muted d-block">Estado del Préstamo</small>
                                @php
                                    $estadoClass = match ($convenio->prestamo->estado ?? 'Desconocido') {
                                        'Vigente' => 'success',
                                        'Moroso' => 'danger',
                                        'Con Convenio', 'CON CONVENIO' => 'info',
                                        'Finalizado', 'Liquidado', 'Pagado' => 'success',
                                        'Cancelado' => 'secondary',
                                        default => 'warning'
                                    };
                                @endphp
                                <div><span class="badge bg-{{ $estadoClass }} small">{{ $convenio->prestamo->estado ?? 'No definido' }}</span></div>
                            </div>
                        </div-->
                        <div class="col-md-3">
                            <div class="border rounded p-2 text-center">
                                <small class="text-muted d-block">Total Pagado</small>
                                <div class="fw-bold text-success">
                                    S/ {{ number_format($totalPagado, 2) }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-2 text-center">
                                <small class="text-muted d-block">Progreso</small>
                                <div class="fw-bold text-success">{{ number_format($convenio->porcentaje_avance, 1) }}%</div>
                                @if($saldoPendiente <= 0)
                                    <div><span class="badge bg-success small">Completado</span></div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Progreso</span>
                            <span class="small fw-bold">S/ {{ number_format($convenio->monto_total_pagado, 2) }} de S/ {{ number_format($convenio->total_convenio, 2) }}</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success"
                                 role="progressbar"
                                 style="width: {{ $convenio->porcentaje_avance }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Personal Asignado -->
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-header py-2">
                    <h6 class="mb-0 small">
                        <i class="fas fa-user-tie me-2 text-success"></i>Personal Asignado
                    </h6>
                </div>
                <div class="card-body py-2">
                    <div class="">
                        <small class="text-muted d-block">Analista:</small>
                        <span class="small fw-semibold">
                            @if($convenio->prestamo->carterasAnalista && $convenio->prestamo->carterasAnalista->count() > 0)
                                @foreach ($convenio->prestamo->carterasAnalista as $carteraAnalista)
                                    {{ $carteraAnalista->user->codigo ?? $carteraAnalista->user->name ?? 'Sin asignar' }}
                                @endforeach
                            @else
                                Sin asignar
                            @endif
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted d-block">JCC:</small>
                        <span class="small fw-semibold">
                            @if($convenio->prestamo->carterasJcc && $convenio->prestamo->carterasJcc->count() > 0)
                                @foreach ($convenio->prestamo->carterasJcc as $carteraJcc)
                                    {{ $carteraJcc->user->codigo ?? $carteraJcc->user->name ?? 'Sin asignar' }}
                                @endforeach
                            @else
                                Sin asignar
                            @endif
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted d-block">Asesor:</small>
                        <span class="small fw-semibold">
                            @if($convenio->prestamo->carterasAsesor && $convenio->prestamo->carterasAsesor->count() > 0)
                                @foreach ($convenio->prestamo->carterasAsesor as $carteraAsesor)
                                    {{ $carteraAsesor->user->codigo ?? $carteraAsesor->user->name ?? 'Sin asignar' }}
                                @endforeach
                            @else
                                Sin asignar
                            @endif
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted d-block">Sucursal:</small>
                        <span class="small fw-semibold">
                            @if($convenio->prestamo->cliente->sucursal)
                                {{ $convenio->prestamo->cliente->sucursal->codigo ?? $convenio->prestamo->cliente->sucursal->nombre ?? 'Sin nombre' }}
                            @else
                                No asignada
                            @endif
                        </span>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block">Zona:</small>
                        <span class="small fw-semibold">
                            @if($convenio->prestamo->cliente->zona)
                                {{ $convenio->prestamo->cliente->zona->codigo ?? $convenio->prestamo->cliente->zona->nombre ?? 'Sin nombre' }}
                            @else
                                No asignada
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($convenio->esTipoCuotas())
        <!-- Vista para Convenio por Cuotas -->
        <!-- Pestañas: Cronograma de Cuotas y Operaciones -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header py-2">
                        <ul class="nav nav-tabs card-header-tabs" id="convenioTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="cuotas-tab" data-bs-toggle="tab" data-bs-target="#cuotas" type="button" role="tab">
                                    <i class="fas fa-list-ol me-2"></i>Cronograma de Cuotas
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="operaciones-tab" data-bs-toggle="tab" data-bs-target="#operaciones" type="button" role="tab">
                                    <i class="fas fa-money-bill-wave me-2"></i>Historial de Operaciones
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-0">
                        <div class="tab-content" id="convenioTabsContent">
                            <!-- Tab 1: Cronograma de Cuotas -->
                            <div class="tab-pane fade show active" id="cuotas" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="small">Cuota</th>
                                                <th class="small">Fecha Vencimiento</th>
                                                <th class="small">Monto Cuota</th>
                                                <th class="small">Fecha Pago</th>
                                                <th class="small">Monto Pagado</th>
                                                <th class="small">Saldo</th>
                                                <th class="small text-center">Moras</th>
                                                <th class="small text-center">Mora a Favor</th>
                                                <th class="small">Estado</th>
                                                <th class="small">Acciones</th>
                                            </tr>
                                        </thead>
                                    <tbody>
                                        @php
                                            $totalMontoCuota = 0;
                                            $totalMontoPagado = 0;
                                            $totalSaldoPendiente = 0;
                                            $totalMoras = 0;
                                            $totalMorasPendientes = 0;
                                        @endphp
                                        @foreach($convenio->cuotasConvenio as $cuota)
                                            @php
                                                $totalMontoCuota += $cuota->monto_cuota;
                                                $totalMontoPagado += $cuota->monto_pagado;
                                                $totalSaldoPendiente += $cuota->saldo_pendiente;
                                            @endphp
                                            @php
                                                // Calcular moras de la cuota ANTES de renderizar la fila
                                                // EXCLUIR moras regularizadas y anuladas de los cálculos
                                                $moras = $cuota->moras ?? collect();
                                                $morasActivas = $moras->filter(function($mora) {
                                                    $estado = is_string($mora->estado) ? $mora->estado : $mora->estado->value;
                                                    return !in_array($estado, ['regularizada', 'anulado']);
                                                });
                                                $cantidadMoras = $morasActivas->count();
                                                $montoTotalMoras = $morasActivas->sum('monto');
                                                // Calcular moras pendientes: suma de todos los saldos pendientes de las moras activas
                                                $montoMorasPendientes = $morasActivas->sum(function($mora) {
                                                    return max(0, $mora->monto - $mora->monto_pagado);
                                                });

                                                $totalMoras += $montoTotalMoras;
                                                $totalMorasPendientes += $montoMorasPendientes;

                                                // Calcular abonos a favor de la cuota
                                                $abonosFavorCuota = $cuota->abonosMoraFavor()->activos()->conSaldo()->get();
                                                $totalSaldoFavorCuota = $abonosFavorCuota->sum('saldo_favor');
                                            @endphp
                                            <tr>
                                                <td class="fw-semibold small">{{ $cuota->numero_cuota }}</td>
                                                <td class="small">{{ $cuota->fecha_vencimiento->format('d/m/Y') }}</td>
                                                <td class="small">S/ {{ number_format($cuota->monto_cuota, 2) }}</td>
                                                <td class="small">
                                                    @php
                                                        // Obtener la última operación de pago de convenio para esta cuota
                                                        $ultimaOperacionConvenio = \App\Models\Operacion::where('prestamo_id', $convenio->prestamo_id)
                                                            ->where('tipo_operacion', 'PAGO_CONVENIO')
                                                            ->where('estado', '!=', 'anulado')
                                                            ->where(function($query) use ($cuota) {
                                                                $query->where('comentario', 'LIKE', '%cuota #' . $cuota->numero_cuota . ' %')
                                                                      ->orWhere('comentario', 'LIKE', '%cuota #' . $cuota->numero_cuota . ')%');
                                                            })
                                                            ->orderBy('updated_at', 'desc')
                                                            ->orderBy('fecha', 'desc')
                                                            ->first();
                                                    @endphp
                                                    @if($ultimaOperacionConvenio)
                                                        <div style="line-height: 1.1;">{{ \Carbon\Carbon::parse($ultimaOperacionConvenio->fecha)->format('d/m/Y') }}</div>
                                                        <small class="text-muted" style="line-height: 1; font-size: 0.65rem;">{{ \Carbon\Carbon::parse($ultimaOperacionConvenio->fecha)->format('H:i') }}</small>
                                                    @elseif($cuota->fecha_pago)
                                                        {{ $cuota->fecha_pago->format('d/m/Y') }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="small">
                                                    @if($cuota->monto_pagado > 0)
                                                        <span class="text-success fw-semibold">S/ {{ number_format($cuota->monto_pagado, 2) }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="small">
                                                    @if($cuota->saldo_pendiente > 0)
                                                        <span class="text-danger fw-semibold">S/ {{ number_format($cuota->saldo_pendiente, 2) }}</span>
                                                    @else
                                                        <span class="text-muted">S/ 0.00</span>
                                                    @endif
                                                </td>
                                                <td class="small text-center">
                                                    @if($cantidadMoras > 0)
                                                        <button class="btn btn-outline-secondary btn-sm"
                                                                type="button"
                                                                data-bs-toggle="collapse"
                                                                data-bs-target="#morasCuota{{ $cuota->id }}"
                                                                aria-expanded="false"
                                                                aria-controls="morasCuota{{ $cuota->id }}"
                                                                title="Ver moras de la cuota">
                                                            <i class="fas fa-file-invoice"></i>
                                                            <span class="badge bg-secondary ms-1 text-white">{{ $cantidadMoras }}</span>
                                                            <small class="d-block text-danger">S/ {{ number_format($montoMorasPendientes, 2) }}</small>
                                                        </button>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="small text-center">
                                                    @if($totalSaldoFavorCuota > 0)
                                                        <span class="badge">
                                                            S/ -{{ number_format($totalSaldoFavorCuota, 2) }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="small text-center">
                                                    @php
                                                        // Calcular estado real basado en pagos
                                                        $montoCuota = $cuota->monto_cuota;
                                                        $montoPagado = $cuota->monto_pagado ?? 0;
                                                        $fechaVencimiento = \Carbon\Carbon::parse($cuota->fecha_vencimiento);
                                                        $hoy = \Carbon\Carbon::now();

                                                        if ($montoPagado >= $montoCuota) {
                                                            // Completamente pagado
                                                            $estadoBadge = ['class' => 'bg-success', 'text' => 'Pagado'];
                                                        } elseif ($montoPagado > 0) {
                                                            // Parcialmente pagado
                                                            $estadoBadge = ['class' => 'bg-info', 'text' => 'Parcial'];
                                                        } elseif ($hoy->gt($fechaVencimiento)) {
                                                            // Vencido y sin pagar
                                                            $estadoBadge = ['class' => 'bg-danger', 'text' => 'Vencido'];
                                                        } else {
                                                            // Pendiente
                                                            $estadoBadge = ['class' => 'bg-warning', 'text' => 'Pendiente'];
                                                        }
                                                    @endphp
                                                    <span class="badge {{ $estadoBadge['class'] }} text-white">
                                                        {{ $estadoBadge['text'] }}
                                                    </span>
                                                </td>
                                                <td class="small">
                                                    <div class="btn-group" role="group">
                                                        @php
                                                            // Obtener operaciones de esta cuota de convenio
                                                            // Buscar por tipo_operacion y comentario que menciona la cuota
                                                            $operacionesCuota = \App\Models\Operacion::where('prestamo_id', $convenio->prestamo_id)
                                                                ->where('tipo_operacion', 'PAGO_CONVENIO')
                                                                ->where('estado', '!=', 'anulado')
                                                                ->where(function($query) use ($cuota) {
                                                                    $query->where('comentario', 'LIKE', '%cuota #' . $cuota->numero_cuota . ' %')
                                                                          ->orWhere('comentario', 'LIKE', '%cuota #' . $cuota->numero_cuota . ')%');
                                                                })
                                                                ->with('metodoDePago', 'user')
                                                                ->orderBy('fecha', 'desc')
                                                                ->get();
                                                            $tieneOperaciones = $operacionesCuota->isNotEmpty();
                                                        @endphp

                                                        @if($tieneOperaciones)
                                                            <button class="btn btn-info btn-sm"
                                                                    type="button"
                                                                    data-bs-toggle="collapse"
                                                                    data-bs-target="#detalleConvenio{{ $cuota->id }}"
                                                                    aria-expanded="false"
                                                                    aria-controls="detalleConvenio{{ $cuota->id }}"
                                                                    title="Ver detalles de operaciones ({{ $operacionesCuota->count() }})">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        @endif

                                                        @if(($cuota->saldo_pendiente > 0 || $montoMorasPendientes > 0) && $convenio->estado === \App\Enums\ConvenioEstado::ACTIVO)
                                                            <a href="{{ route('admin.convenios.cuotas.pagar.form', $cuota->id) }}"
                                                               class="btn btn-success btn-sm"
                                                               title="Registrar Pago">
                                                                <i class="fas fa-money-bill"></i> Pagar
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>

                                            @if($tieneOperaciones)
                                            <!-- Detalles de Operaciones (Acordeón) -->
                                            <tr class="collapse" id="detalleConvenio{{ $cuota->id }}">
                                                <td colspan="10" class="p-0">
                                                    <div class="bg-light border-start border-primary border-4" style="margin: 0 10px;">
                                                        <div class="p-3">
                                                            <h6 class="mb-3 text-primary">
                                                                <i class="fas fa-file-invoice-dollar me-2"></i>
                                                                Operaciones de Pago - Cuota #{{ $cuota->numero_cuota }}
                                                            </h6>

                                                            <div class="table-responsive">
                                                                <table class="table table-sm table-hover mb-0">
                                                                    <thead class="table-light">
                                                                        <tr>
                                                                            <th>ID</th>
                                                                            <th>Fecha</th>
                                                                            <th>Nro. Operación</th>
                                                                            <th>Monto</th>
                                                                            <th>Método</th>
                                                                            <th>Usuario</th>
                                                                            <th>Voucher</th>
                                                                            <th>Acciones</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($operacionesCuota as $operacion)
                                                                        <tr>
                                                                            <td><span class="badge bg-secondary">#{{ $operacion->id }}</span></td>
                                                                            <td>
                                                                                <div>{{ \Carbon\Carbon::parse($operacion->fecha)->format('d/m/Y') }}</div>
                                                                                <small class="text-muted">{{ \Carbon\Carbon::parse($operacion->fecha)->format('H:i') }}</small>
                                                                            </td>
                                                                            <td>
                                                                                <span class="badge bg-light text-dark">{{ $operacion->codigo ?? '#' . $operacion->id }}</span>
                                                                            </td>
                                                                            <td class="fw-bold text-success">S/ {{ number_format($operacion->abono, 2) }}</td>
                                                                            <td>
                                                                                <span class="badge bg-info text-white">{{ $operacion->metodoDePago->metodo_pago ?? 'N/A' }}</span>
                                                                            </td>
                                                                            <td>{{ optional($operacion->user)->codigo ?? 'N/A' }}</td>
                                                                            <td>
                                                                                @if($operacion->voucher_path)
                                                                                    <button class="btn btn-outline-primary btn-sm"
                                                                                            onclick="mostrarVoucher('{{ asset('storage/' . $operacion->voucher_path) }}')">
                                                                                        <i class="fas fa-eye"></i>
                                                                                    </button>
                                                                                @else
                                                                                    <span class="text-muted">-</span>
                                                                                @endif
                                                                            </td>
                                                                            <td>
                                                                                <div class="btn-group btn-group-sm" role="group">
                                                                                    <a href="{{ route('admin.operaciones.editar', $operacion->id) }}?return_to=convenio&convenio_id={{ $convenio->id }}"
                                                                                       class="btn btn-warning btn-sm"
                                                                                       title="Editar operación">
                                                                                        <i class="fas fa-edit"></i>
                                                                                    </a>
                                                                                    <button type="button"
                                                                                            class="btn btn-danger btn-sm"
                                                                                            onclick="anularOperacion({{ $operacion->id }})"
                                                                                            title="Anular operación">
                                                                                        <i class="fas fa-ban"></i>
                                                                                    </button>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endif

                                            <!-- Acordeón de Moras -->
                                            @if($cantidadMoras > 0)
                                            <tr class="collapse" id="morasCuota{{ $cuota->id }}">
                                                <td colspan="10" class="p-0">
                                                    <div class="bg-light border-start border-secondary border-3" style="margin: 0 10px;">
                                                        <div class="p-3">
                                                            @php
                                                                // Obtener abonos a favor de la cuota
                                                                $abonosFavor = $cuota->abonosMoraFavor()->activos()->conSaldo()->get();
                                                                $totalSaldoFavor = $abonosFavor->sum('saldo_favor');
                                                            @endphp

                                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                                <h6 class="mb-0 text-secondary">
                                                                    <i class="fas fa-file-invoice me-2"></i>
                                                                    Moras de la Cuota #{{ $cuota->numero_cuota }}
                                                                    <span class="badge bg-secondary text-white ms-2">{{ $cantidadMoras }}</span>
                                                                </h6>
                                                                @if($totalSaldoFavor > 0)
                                                                    <div class="badge bg-success text-white">
                                                                        <i class="fas fa-piggy-bank me-1"></i>
                                                                        Saldo a Favor: S/ {{ number_format($totalSaldoFavor, 2) }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div class="table-responsive">
                                                                <table class="table table-sm table-bordered mb-0">
                                                                    <thead class="table-light">
                                                                        <tr>
                                                                            <th class="small">#</th>
                                                                            <th class="small">Fecha</th>
                                                                            <th class="small">Días Mora</th>
                                                                            <th class="small">Monto</th>
                                                                            <th class="small">Pagado</th>
                                                                            <th class="small">Saldo</th>
                                                                            <th class="small">Estado</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($morasActivas as $mora)
                                                                        <tr>
                                                                            <td class="small">{{ $mora->id }}</td>
                                                                            <td class="small">
                                                                                <span class="badge bg-secondary text-white">
                                                                                    {{ $mora->fecha ? \Carbon\Carbon::parse($mora->fecha)->format('d/m/Y') : '-' }}
                                                                                </span>
                                                                            </td>
                                                                            <td class="small text-center">{{ $mora->dias_mora ?? '-' }}</td>
                                                                            <td class="small text-end">
                                                                                <span class="text-danger fw-semibold">S/ {{ number_format($mora->monto, 2) }}</span>
                                                                            </td>
                                                                            <td class="small text-end">
                                                                                @if($mora->monto_pagado > 0)
                                                                                    <span class="text-success">S/ {{ number_format($mora->monto_pagado, 2) }}</span>
                                                                                @else
                                                                                    <span class="text-muted">S/ 0.00</span>
                                                                                @endif
                                                                            </td>
                                                                            <td class="small text-end">
                                                                                @php
                                                                                    $saldoMora = $mora->monto - $mora->monto_pagado;
                                                                                @endphp
                                                                                @if($saldoMora > 0)
                                                                                    <span class="text-danger fw-semibold">S/ {{ number_format($saldoMora, 2) }}</span>
                                                                                @else
                                                                                    <span class="text-muted">S/ 0.00</span>
                                                                                @endif
                                                                            </td>
                                                                            <td class="small text-center">
                                                                                <span class="badge bg-{{ $mora->estado_class ?? 'secondary' }} small text-white">
                                                                                    {{ $mora->estado_nombre ?? $mora->estado }}
                                                                                </span>
                                                                            </td>
                                                                        </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                    <tfoot class="table-secondary">
                                                                        <tr>
                                                                            <td colspan="3" class="small fw-bold text-end">TOTAL (Activas):</td>
                                                                            <td class="small fw-bold text-end text-danger">
                                                                                S/ {{ number_format($montoTotalMoras, 2) }}
                                                                            </td>
                                                                            <td class="small fw-bold text-end text-success">
                                                                                S/ {{ number_format($morasActivas->sum('monto_pagado'), 2) }}
                                                                            </td>
                                                                            <td class="small fw-bold text-end text-danger">
                                                                                S/ {{ number_format($montoMorasPendientes, 2) }}
                                                                            </td>
                                                                            <td class="small">-</td>
                                                                        </tr>
                                                                    </tfoot>
                                                                </table>
                                                            </div>

                                                            <!-- Sección de Abonos a Favor -->
                                                            @if($abonosFavor->count() > 0)
                                                            <div class="mt-3 pt-3 border-top">
                                                                <h6 class="mb-3 text-success">
                                                                    Abonos a Favor Disponibles
                                                                </h6>
                                                                <div class="table-responsive">
                                                                    <table class="table table-sm table-bordered mb-0">
                                                                        <thead class="table-success">
                                                                            <tr>
                                                                                <th class="small">Fecha Abono</th>
                                                                                <th class="small">Monto Abonado</th>
                                                                                <th class="small">Monto Utilizado</th>
                                                                                <th class="small">Saldo Disponible</th>
                                                                                <th class="small">Estado</th>
                                                                                <th class="small">Comentario</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach($abonosFavor as $abono)
                                                                            <tr>
                                                                                <td class="small">
                                                                                    <span class="badge bg-secondary text-white">
                                                                                        {{ \Carbon\Carbon::parse($abono->fecha_abono)->format('d/m/Y') }}
                                                                                    </span>
                                                                                </td>
                                                                                <td class="small text-end">
                                                                                    <span class="text-primary fw-semibold">S/ {{ number_format($abono->monto_abonado, 2) }}</span>
                                                                                </td>
                                                                                <td class="small text-end">
                                                                                    @if($abono->monto_utilizado > 0)
                                                                                        <span class="text-muted">S/ {{ number_format($abono->monto_utilizado, 2) }}</span>
                                                                                    @else
                                                                                        <span class="text-muted">S/ 0.00</span>
                                                                                    @endif
                                                                                </td>
                                                                                <td class="small text-end">
                                                                                    <span class="text-success fw-bold">S/ {{ number_format($abono->saldo_favor, 2) }}</span>
                                                                                </td>
                                                                                <td class="small text-center">
                                                                                    <span class="badge bg-{{ $abono->estado === 'activo' ? 'success' : 'secondary' }} small text-white">
                                                                                        {{ ucfirst($abono->estado) }}
                                                                                    </span>
                                                                                </td>
                                                                                <td class="small">
                                                                                    {{ $abono->comentario ?? '-' }}
                                                                                </td>
                                                                            </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                        <tfoot class="table-success">
                                                                            <tr>
                                                                                <td class="small fw-bold text-end">TOTAL DISPONIBLE:</td>
                                                                                <td class="small fw-bold text-end text-primary">
                                                                                    S/ {{ number_format($abonosFavor->sum('monto_abonado'), 2) }}
                                                                                </td>
                                                                                <td class="small fw-bold text-end text-muted">
                                                                                    S/ {{ number_format($abonosFavor->sum('monto_utilizado'), 2) }}
                                                                                </td>
                                                                                <td class="small fw-bold text-end text-success">
                                                                                    S/ {{ number_format($totalSaldoFavor, 2) }}
                                                                                </td>
                                                                                <td colspan="2" class="small">-</td>
                                                                            </tr>
                                                                        </tfoot>
                                                                    </table>
                                                                </div>

                                                                <div class="alert alert-info mt-3 mb-0 small">
                                                                    <i class="fas fa-info-circle me-2"></i>
                                                                    <strong>Nota:</strong> Este saldo a favor se aplicará automáticamente cuando se generen nuevas moras en esta cuota.
                                                                </div>
                                                            </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endif
                                        @endforeach
                                        <!-- Fila de totales -->
                                        <tr class="table-secondary">
                                            <td class="fw-bold small">TOTALES</td>
                                            <td class="small">-</td>
                                            <td class="fw-bold small text-primary">S/ {{ number_format($totalMontoCuota, 2) }}</td>
                                            <td class="small">-</td>
                                            <td class="fw-bold small text-success">S/ {{ number_format($totalMontoPagado, 2) }}</td>
                                            <td class="fw-bold small text-danger">S/ {{ number_format($totalSaldoPendiente, 2) }}</td>
                                            <td class="fw-bold small text-center">
                                                @if($totalMorasPendientes > 0)
                                                    <i class="fas fa-file-invoice text-muted"></i>
                                                    <span class="text-danger">S/ {{ number_format($totalMorasPendientes, 2) }}</span>
                                                @else
                                                    <span class="text-muted">S/ 0.00</span>
                                                @endif
                                            </td>
                                            <td class="small text-center">
                                                @php
                                                    $totalSaldoFavor = $convenio->cuotasConvenio->sum(function($cuota) {
                                                        return $cuota->abonosMoraFavor()->activos()->conSaldo()->sum('saldo_favor');
                                                    });
                                                @endphp
                                                @if($totalSaldoFavor > 0)
                                                    <span class="badge">
                                                        S/ {{ number_format($totalSaldoFavor, 2) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="small">-</td>
                                            <td class="small">-</td>
                                        </tr>

                                        <!-- Fila adicional con resumen de moras -->
                                        @if($totalMoras > 0)
                                        <tr class="table-light border-top border-2">
                                            <td colspan="6" class="fw-bold small text-end text-muted">TOTAL DEUDA DE MORA:</td>
                                            <td class="fw-bold small text-center">
                                                <div class="d-flex flex-column">
                                                    <span class="text-danger">
                                                        <i class="fas fa-file-invoice me-1"></i>
                                                        Pendiente: S/ {{ number_format($totalMorasPendientes, 2) }}
                                                    </span>
                                                    <small class="text-muted">
                                                        Total generado: S/ {{ number_format($totalMoras, 2) }}
                                                    </small>
                                                </div>
                                            </td>
                                            <td colspan="3" class="small">-</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab 2: Historial de Operaciones -->
                        <div class="tab-pane fade" id="operaciones" role="tabpanel">
                            @php
                                // Obtener todas las operaciones generales (padre) de PAGO_CONVENIO
                                // Filtrar por operaciones que tienen operacion_general_id NULL
                                // Y que su comentario contenga "Pago de convenio #" (formato de operación padre)
                                $operacionesGenerales = \App\Models\Operacion::where('prestamo_id', $convenio->prestamo_id)
                                    ->where('tipo_operacion', 'PAGO_CONVENIO')
                                    ->whereNull('operacion_general_id')
                                    ->where('comentario', 'LIKE', 'Pago de convenio #%')
                                    ->where('estado', '!=', 'anulado')
                                    ->with(['operacionesRelacionadas', 'metodoDePago', 'user'])
                                    ->orderBy('fecha', 'desc')
                                    ->get();
                            @endphp

                            <div class="p-3">
                                @if($operacionesGenerales->isEmpty())
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No se han registrado operaciones de pago para este convenio.
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="small">ID</th>
                                                    <th class="small">Fecha</th>
                                                    <th class="small">Nro. Operación</th>
                                                    <th class="small">Monto Total</th>
                                                    <th class="small">Método</th>
                                                    <th class="small">Usuario</th>
                                                    <th class="small">Cuotas Cubiertas</th>
                                                    <th class="small">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($operacionesGenerales as $operacionGeneral)
                                                    <tr>
                                                        <td><span class="badge bg-primary">#{{ $operacionGeneral->id }}</span></td>
                                                        <td class="small">
                                                            <div>{{ \Carbon\Carbon::parse($operacionGeneral->fecha)->format('d/m/Y') }}</div>
                                                            <small class="text-muted">{{ \Carbon\Carbon::parse($operacionGeneral->fecha)->format('H:i') }}</small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-dark">{{ $operacionGeneral->codigo ?? '#' . $operacionGeneral->id }}</span>
                                                        </td>
                                                        <td class="fw-bold text-success">S/ {{ number_format($operacionGeneral->abono, 2) }}</td>
                                                        <td>
                                                            <span class="badge bg-info text-white">{{ $operacionGeneral->metodoDePago->metodo_pago ?? 'N/A' }}</span>
                                                        </td>
                                                        <td class="small">{{ optional($operacionGeneral->user)->codigo ?? 'N/A' }}</td>
                                                        <td class="text-center">
                                                            <span class="badge bg-secondary">{{ $operacionGeneral->operacionesRelacionadas->count() }}</span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm" role="group">
                                                                <button class="btn btn-info btn-sm"
                                                                        type="button"
                                                                        onclick="toggleOperacionDetalle({{ $operacionGeneral->id }})"
                                                                        title="Ver detalles">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                                <!--a href="{{ route('admin.operaciones.editar', $operacionGeneral->id) }}?return_to=convenio&convenio_id={{ $convenio->id }}"
                                                                   class="btn btn-warning btn-sm"
                                                                   title="Editar operación">
                                                                    <i class="fas fa-edit"></i>
                                                                </a-->
                                                                <button type="button"
                                                                        class="btn btn-danger btn-sm"
                                                                        onclick="anularOperacion({{ $operacionGeneral->id }})"
                                                                        title="Anular operación">
                                                                    <i class="fas fa-ban"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>

                                                    <!-- Fila expandible con detalles de operaciones hijas -->
                                                    <tr class="collapse" id="operacionDetalle{{ $operacionGeneral->id }}">
                                                        <td colspan="8" class="p-0">
                                                            <div class="bg-light border-start border-success border-4" style="margin: 0 10px;">
                                                                <div class="p-3">
                                                                    <h6 class="mb-3 text-success">
                                                                        <i class="fas fa-list-ul me-2"></i>
                                                                        Detalle de Cuotas Cubiertas - Operación #{{ $operacionGeneral->id }}
                                                                    </h6>

                                                                    @if($operacionGeneral->voucher_path)
                                                                        <div class="mb-3">
                                                                            <button class="btn btn-outline-primary btn-sm"
                                                                                    onclick="mostrarVoucher('{{ asset('storage/' . $operacionGeneral->voucher_path) }}')">
                                                                                <i class="fas fa-file-image me-1"></i>Ver Voucher
                                                                            </button>
                                                                        </div>
                                                                    @endif

                                                                    <div class="table-responsive">
                                                                        <table class="table table-sm table-bordered mb-0">
                                                                            <thead class="table-light">
                                                                                <tr>
                                                                                    <th class="small">Cuota</th>
                                                                                    <th class="small">Monto Aplicado</th>
                                                                                    <th class="small">Comentario</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @php
                                                                                    $totalAplicado = 0;
                                                                                @endphp
                                                                                @foreach($operacionGeneral->operacionesRelacionadas as $operacionHija)
                                                                                    @php
                                                                                        $totalAplicado += $operacionHija->abono;
                                                                                        // Extraer el número de cuota del comentario
                                                                                        preg_match('/cuota #(\d+)/', $operacionHija->comentario, $matches);
                                                                                        $numeroCuota = $matches[1] ?? 'N/A';
                                                                                    @endphp
                                                                                    <tr>
                                                                                        <td class="text-center fw-semibold">
                                                                                            <span class="badge bg-light text-dark">Cuota #{{ $numeroCuota }}</span>
                                                                                        </td>
                                                                                        <td class="text-success fw-bold">S/ {{ number_format($operacionHija->abono, 2) }}</td>
                                                                                        <td class="small text-muted">{{ $operacionHija->comentario }}</td>
                                                                                    </tr>
                                                                                @endforeach
                                                                                <tr class="table-success">
                                                                                    <td class="fw-bold small">TOTAL DISTRIBUIDO</td>
                                                                                    <td class="fw-bold text-success">S/ {{ number_format($totalAplicado, 2) }}</td>
                                                                                    <td></td>
                                                                                </tr>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>

                                                                    @if($operacionGeneral->comentario)
                                                                        <div class="alert alert-info mt-3 mb-0">
                                                                            <small><strong>Comentario:</strong> {{ $operacionGeneral->comentario }}</small>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Observaciones (si existen) -->
    @if($convenio->observaciones)
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-sticky-note me-2 text-warning"></i>Observaciones
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <p class="mb-0">{{ $convenio->observaciones }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Inicializar collapse manualmente (para evitar conflictos con otros scripts)
document.addEventListener('DOMContentLoaded', function() {
    console.log('Convenio show.blade.php cargado');

    // ============================================
    // MANEJO MANUAL DE PESTAÑAS (TABS)
    // ============================================
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    console.log(`Se encontraron ${tabButtons.length} botones de tabs`);

    tabButtons.forEach((tabButton) => {
        tabButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const targetId = tabButton.getAttribute('data-bs-target');
            console.log('Tab clickeado:', targetId);

            // Remover active de todos los tabs y tab-panes
            document.querySelectorAll('.nav-link').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });

            // Activar el tab clickeado
            tabButton.classList.add('active');

            // Activar el contenido correspondiente
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
                console.log('Tab activado:', targetId);
            } else {
                console.error('No se encontró el tab-pane:', targetId);
            }
        });
    });

    // ============================================
    // MANEJO MANUAL DE COLLAPSE
    // ============================================
    const collapseButtons = document.querySelectorAll('[data-bs-toggle="collapse"]');
    console.log(`Se encontraron ${collapseButtons.length} botones de collapse`);

    collapseButtons.forEach((button, index) => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const targetId = button.getAttribute('data-bs-target');
            const targetElement = document.querySelector(targetId);

            console.log(`Botón ${index + 1} clickeado - Target: ${targetId}`, targetElement);

            if (targetElement) {
                // Toggle manual de la clase 'show' y visibility
                if (targetElement.classList.contains('show')) {
                    targetElement.classList.remove('show');
                    targetElement.style.display = 'none';
                    button.setAttribute('aria-expanded', 'false');
                } else {
                    targetElement.classList.add('show');
                    targetElement.style.display = 'table-row';
                    button.setAttribute('aria-expanded', 'true');
                }
            } else {
                console.error('No se encontró el elemento target:', targetId);
            }
        });
    });
});

function cancelarConvenio(convenioId) {
    const cancelarUrl = '{{ route("admin.convenios.cancelar", $convenio->id) }}';

    Swal.fire({
        title: '¿Cancelar Convenio?',
        html: 'Esta acción <strong>no se puede deshacer</strong>.<br>El convenio quedará cancelado y el préstamo será liberado.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74a3b',
        cancelButtonColor: '#858796',
        confirmButtonText: '<i class="fas fa-ban me-1"></i> Sí, cancelar',
        cancelButtonText: 'No, volver',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
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
    });
}

function toggleOperacionDetalle(operacionId) {
    const targetId = `#operacionDetalle${operacionId}`;
    const targetElement = document.querySelector(targetId);

    if (targetElement) {
        // Toggle manual de la clase 'show' y visibility
        if (targetElement.classList.contains('show')) {
            targetElement.classList.remove('show');
            targetElement.style.display = 'none';
        } else {
            targetElement.classList.add('show');
            targetElement.style.display = 'table-row';
        }
    } else {
        console.error('No se encontró el elemento target:', targetId);
    }
}

function anularOperacion(operacionId) {
    // Redirigir a la página de confirmación de anulación
    // Agregamos parámetro para indicar que venimos de convenio
    const convenioId = {{ $convenio->id }};
    window.location.href = `/admin/admin/operaciones/${operacionId}/anular?return_to=convenio&convenio_id=${convenioId}`;
}

function mostrarVoucher(voucherUrl) {
    console.log('Mostrando voucher:', voucherUrl);

    // Detectar si es PDF o imagen
    const extension = voucherUrl.split('.').pop().toLowerCase();
    const isPDF = extension === 'pdf';

    if (isPDF) {
        // Para PDFs, mostrar en un iframe
        Swal.fire({
            title: 'Voucher de Pago',
            html: `
                <iframe src="${voucherUrl}" style="width:100%; height:500px; border:none;"></iframe>
                <div class="mt-3">
                    <a href="${voucherUrl}" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt"></i> Abrir en nueva pestaña
                    </a>
                </div>
            `,
            width: 900,
            showCloseButton: true,
            showConfirmButton: false
        });
    } else {
        // Para imágenes
        Swal.fire({
            title: 'Voucher de Pago',
            imageUrl: voucherUrl,
            imageAlt: 'Voucher',
            width: 800,
            showCloseButton: true,
            showConfirmButton: false,
            html: `
                <div class="mt-3">
                    <a href="${voucherUrl}" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt"></i> Abrir en nueva pestaña
                    </a>
                </div>
            `
        });
    }
}

function openConvenioPreview(url) {
    // Abrir el PDF en una nueva ventana
    const width = 900;
    const height = 700;
    const left = (screen.width - width) / 2;
    const top = (screen.height - height) / 2;

    window.open(
        url,
        'Estado de Cuenta - Convenio',
        `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`
    );
}
</script>

    @else
        <!-- Vista para Convenio Flexible -->
        @include('admin.convenios.partials.show-flexible')
    @endif

@endsection

@section('css')
<style>
    /* Clean design improvements */
    .card {
        border: 1px solid #e3e6f0;
        border-radius: 0.5rem;
    }

    .card-header {
        background-color: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
        padding: 0.75rem 1rem;
    }

    .card-header h6 {
        font-weight: 600;
        color: #5a5c69;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }

    .table th {
        font-weight: 600;
        color: #5a5c69;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05rem;
        padding: 0.4rem 0.6rem;
    }

    .table td {
        padding: 0.35rem 0.6rem;
        vertical-align: middle;
        line-height: 1.3;
    }

    .table-sm th,
    .table-sm td {
        padding: 0.3rem 0.5rem;
    }

    .badge {
        font-size: 0.7rem;
        font-weight: 500;
        padding: 0.25em 0.6em;
    }

    .badge-sm {
        font-size: 0.65rem;
        padding: 0.2em 0.5em;
    }

    .table td .btn-sm {
        padding: 0.2rem 0.5rem;
        font-size: 0.75rem;
        line-height: 1.2;
    }

    .table td .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }

    .table td .btn-outline-secondary {
        padding: 0.3rem 0.5rem;
        font-size: 0.7rem;
        line-height: 1.2;
    }

    .table td .btn-outline-secondary small {
        font-size: 0.65rem;
        line-height: 1;
        margin-top: 2px;
    }

    .table td div,
    .table td small {
        margin: 0;
        padding: 0;
        line-height: 1.2;
    }

    .table td .text-muted {
        font-size: 0.7rem;
    }

    .table .small,
    .table td.small {
        font-size: 0.8rem;
    }

    .table td .fw-semibold {
        font-weight: 600;
    }

    .progress {
        background-color: #e9ecef;
        border-radius: 0.5rem;
    }

    .border {
        border-color: #e3e6f0 !important;
    }

    .btn {
        border-radius: 0.35rem;
        font-weight: 500;
    }

    .text-primary { color: #4e73df !important; }
    .text-success { color: #1cc88a !important; }
    .text-info { color: #36b9cc !important; }
    .text-warning { color: #f6c23e !important; }
    .text-danger { color: #e74a3b !important; }

    .bg-primary { background-color: #4e73df !important; }
    .bg-success { background-color: #1cc88a !important; }
    .bg-info { background-color: #36b9cc !important; }
    .bg-warning { background-color: #f6c23e !important; }
    .bg-danger { background-color: #e74a3b !important; }

    /* Icon colors */
    .fas.text-primary { color: #4e73df !important; }
    .fas.text-success { color: #1cc88a !important; }
    .fas.text-info { color: #36b9cc !important; }
    .fas.text-warning { color: #f6c23e !important; }

    /* Compact spacing */
    .mb-3 { margin-bottom: 1rem !important; }
    .mb-2 { margin-bottom: 0.5rem !important; }
    .mb-1 { margin-bottom: 0.25rem !important; }
    .py-2 { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; }
    .py-3 { padding-top: 1rem !important; padding-bottom: 1rem !important; }
    .p-2 { padding: 0.5rem !important; }
    .p-3 { padding: 1rem !important; }

    /* Compact cards */
    .card-body {
        padding: 1rem;
    }

    .small {
        font-size: 0.875rem;
    }
</style>
@endsection