@extends('layouts.admin')

@section('title', 'Registrar Pago de Cuota de Convenio')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card" style="background: var(--bg-primary); border: 1px solid var(--border-primary); box-shadow: var(--shadow-sm);">
                <div class="card-header" style="background: var(--bg-secondary); border-bottom: 1px solid var(--border-primary);">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="card-title mb-0" style="color: var(--text-primary);">
                                <i class="fas fa-money-bill me-2" style="color: var(--text-secondary);"></i>Registrar Pago de Cuota
                            </h3>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb bg-transparent p-0 mb-0 small">
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('admin.convenios.index') }}" style="color: var(--text-secondary);">Convenios</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('admin.convenios.show', $cuotaConvenio->convenio->id) }}" style="color: var(--text-secondary);">
                                            Convenio #{{ $cuotaConvenio->convenio->id }}
                                        </a>
                                    </li>
                                    <li class="breadcrumb-item active" style="color: var(--text-secondary);" aria-current="page">Registrar Pago</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="{{ route('admin.convenios.show', $cuotaConvenio->convenio->id) }}" 
                               class="btn btn-sm" style="border: 1px solid var(--border-primary); color: var(--text-secondary); background: var(--bg-primary);">
                                <i class="fas fa-arrow-left me-2"></i>Volver
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body" style="background: var(--bg-secondary);">
                    <form action="{{ route('admin.convenios.cuotas.pagar', $cuotaConvenio->id) }}" method="POST" enctype="multipart/form-data" id="paymentForm">
                        @csrf
                        <input type="hidden" name="cuota_convenio_id" value="{{ $cuotaConvenio->id }}">
                        <input type="hidden" name="convenio_id" value="{{ $cuotaConvenio->convenio->id }}">

                        <div class="row">
                            <!-- Columna Izquierda: Información y Formulario -->
                            <div class="col-md-8">
                                <!-- Información del Cliente y Convenio -->
                                <div class="card mb-4" style="background: var(--bg-primary); border: 1px solid var(--border-primary); box-shadow: var(--shadow-sm);">
                                    <div class="card-header" style="background: var(--bg-primary); border-bottom: 1px solid var(--border-primary);">
                                        <h6 class="mb-0" style="color: var(--text-primary);">
                                            <i class="fas fa-user me-2" style="color: var(--text-secondary);"></i>Información del Cliente
                                        </h6>
                                    </div>
                                    <div class="card-body" style="background: var(--bg-primary);">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle p-2 me-3" style="background: var(--primary);">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1" style="color: var(--text-primary);">
                                                    {{ $cuotaConvenio->convenio->prestamo->cliente->persona->nombres }} 
                                                    {{ $cuotaConvenio->convenio->prestamo->cliente->persona->ape_pat }} 
                                                    {{ $cuotaConvenio->convenio->prestamo->cliente->persona->ape_mat }}
                                                </h6>
                                                <div>
                                                    <span class="badge me-1" style="background: var(--primary); color: white;">
                                                        Convenio #{{ $cuotaConvenio->convenio->id }}
                                                    </span>
                                                    <span class="badge me-1" style="background: var(--primary); color: white;">
                                                        Préstamo #{{ $cuotaConvenio->convenio->prestamo->id }}
                                                    </span>
                                                    <span class="badge" style="background: var(--gray-700); color: white;">
                                                        {{ $cuotaConvenio->convenio->estado->label() }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @php
                                    // Preparar datos de cuotas pendientes
                                    $cuotasPendientesData = $cuotaConvenio->convenio->cuotasConvenio()
                                        ->whereIn('estado', [0, 1, 3])
                                        ->orderBy('numero_cuota')
                                        ->get()
                                        ->map(function($cuota) {
                                            $hoy = \Carbon\Carbon::now();
                                            $fechaVenc = \Carbon\Carbon::parse($cuota->fecha_vencimiento);
                                            $diasVencimiento = $hoy->diffInDays($fechaVenc, false);
                                            $esCuotaActual = $cuota->id === request()->route('cuotaConvenio');

                                            return [
                                                'numero_cuota' => $cuota->numero_cuota,
                                                'monto_cuota' => $cuota->monto_cuota,
                                                'monto_pagado' => $cuota->monto_pagado ?? 0,
                                                'saldo_pendiente' => $cuota->monto_cuota - ($cuota->monto_pagado ?? 0),
                                                'estado' => $cuota->estado->label(),
                                                'fecha_vencimiento' => $cuota->fecha_vencimiento,
                                                'dias_vencimiento' => $diasVencimiento,
                                                'vencida' => $diasVencimiento < 0,
                                                'es_cuota_actual' => $esCuotaActual
                                            ];
                                        });

                                    // Obtener moras pendientes de la cuota actual (excluir regularizadas)
                                    $morasPendientes = $cuotaConvenio->moras()
                                        ->whereNotIn('estado', ['pagado', 'regularizada'])
                                        ->get();
                                    $totalMorasPendientes = $morasPendientes->sum(function($mora) {
                                        return max(0, $mora->monto - $mora->monto_pagado);
                                    });

                                    // Preparar datos de moras para JavaScript
                                    $morasPendientesJS = $morasPendientes->map(function($mora) {
                                        return [
                                            'id' => $mora->id,
                                            'fecha' => $mora->fecha->format('d/m/Y'),
                                            'dias_mora' => $mora->dias_mora,
                                            'monto' => $mora->monto,
                                            'monto_pagado' => $mora->monto_pagado ?? 0,
                                            'saldo' => max(0, $mora->monto - ($mora->monto_pagado ?? 0))
                                        ];
                                    });
                                @endphp

                                <!-- Detalles de Cuotas y Moras -->
                                <div class="card mb-4" style="background: var(--bg-primary); border: 1px solid var(--border-primary); box-shadow: var(--shadow-sm);">
                                    <div class="card-header" style="background: var(--bg-primary); border-bottom: 1px solid var(--border-primary);">
                                        <h6 class="mb-0" style="color: var(--text-primary);">
                                            <i class="fas fa-list-alt me-2" style="color: var(--text-secondary);"></i>Detalles Pendientes
                                        </h6>
                                    </div>
                                    <div class="card-body p-0" style="background: var(--bg-primary);">
                                        <!-- Nav Tabs -->
                                        <ul class="nav nav-tabs px-3 pt-3" style="border-bottom: 1px solid #dee2e6;">
                                            <li class="nav-item">
                                                <a class="nav-link active d-flex align-items-center" id="cuotas-tab" data-toggle="tab" href="#cuotas-content"
                                                   style="font-weight: 500; padding: 0.75rem 1.25rem; color: #495057;">
                                                    <i class="fas fa-file-invoice-dollar me-2"></i>
                                                    Cuotas
                                                    <span class="badge bg-primary ms-2">{{ $cuotasPendientesData->count() }}</span>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link d-flex align-items-center" id="moras-tab" data-toggle="tab" href="#moras-content"
                                                   style="font-weight: 500; padding: 0.75rem 1.25rem; color: #495057;">
                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                    Moras
                                                    <span class="badge bg-danger ms-2">{{ $morasPendientes->count() }}</span>
                                                </a>
                                            </li>
                                        </ul>

                                        <!-- Tab Content -->
                                        <div class="tab-content p-3">
                                            <!-- Contenido de Cuotas -->
                                            <div class="tab-pane fade show active" id="cuotas-content">
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-hover mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th class="small">#</th>
                                                                <th class="small">Vencimiento</th>
                                                                <th class="small">Monto</th>
                                                                <th class="small">Pagado</th>
                                                                <th class="small">Saldo</th>
                                                                <th class="small">Estado</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($cuotasPendientesData as $cuota)
                                                            <tr class="{{ $cuota['es_cuota_actual'] ? 'table-active' : '' }}"
                                                                style="{{ $cuota['es_cuota_actual'] ? 'background-color: #fff3cd; border-left: 4px solid #ffc107;' : '' }}">
                                                                <td class="small">
                                                                    <span class="badge {{ $cuota['es_cuota_actual'] ? 'bg-warning text-dark' : 'bg-secondary' }}">
                                                                        #{{ $cuota['numero_cuota'] }}
                                                                    </span>
                                                                    @if($cuota['es_cuota_actual'])
                                                                        <i class="fas fa-arrow-right text-warning ms-1" title="Cuota actual"></i>
                                                                    @endif
                                                                </td>
                                                                <td class="small">
                                                                    <div class="d-flex flex-column">
                                                                        <span class="fw-bold" style="color: {{ $cuota['vencida'] ? '#dc3545' : '#6c757d' }};">
                                                                            {{ \Carbon\Carbon::parse($cuota['fecha_vencimiento'])->format('d/m/Y') }}
                                                                        </span>
                                                                        @if($cuota['vencida'])
                                                                            <span class="badge bg-light" style="font-size: 0.65rem;">
                                                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                                                Vencida {{ abs($cuota['dias_vencimiento']) }} días
                                                                            </span>
                                                                        @elseif($cuota['dias_vencimiento'] <= 3)
                                                                            <span class="badge bg-warning text-dark" style="font-size: 0.65rem;color: #fff;">
                                                                                <i class="fas fa-clock me-1"></i>
                                                                                Vence en {{ $cuota['dias_vencimiento'] }} días
                                                                            </span>
                                                                        @else
                                                                            <span class="badge bg-success" style="font-size: 0.65rem;color: #fff;">
                                                                                <i class="fas fa-check me-1"></i>
                                                                                {{ $cuota['dias_vencimiento'] }} días
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                                <td class="small fw-bold" style="color: #495057;">S/ {{ number_format($cuota['monto_cuota'], 2) }}</td>
                                                                <td class="small" style="color: #28a745;">
                                                                    <i class="fas fa-check-circle me-1"></i>
                                                                    S/ {{ number_format($cuota['monto_pagado'], 2) }}
                                                                </td>
                                                                <td class="small fw-bold" style="color: #dc3545; font-size: 0.95rem;">
                                                                    S/ {{ number_format($cuota['saldo_pendiente'], 2) }}
                                                                </td>
                                                                <td class="small">
                                                                    @php
                                                                        $badgeClass = match($cuota['estado']) {
                                                                            'Pendiente' => 'bg-secondary',
                                                                            'Parcial' => 'bg-warning text-dark',
                                                                            'Vencido' => 'bg-danger text-light',
                                                                            'Pagado' => 'bg-success',
                                                                            default => 'bg-secondary'
                                                                        };
                                                                    @endphp
                                                                    <span class="badge {{ $badgeClass }}">{{ $cuota['estado'] }}</span>
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                        <tfoot class="table-light">
                                                            <tr>
                                                                <td class="small fw-bold" colspan="4">TOTAL PENDIENTE:</td>
                                                                <td class="small fw-bold text-danger" colspan="2">
                                                                    S/ {{ number_format($cuotasPendientesData->sum('saldo_pendiente'), 2) }}
                                                                </td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- Contenido de Moras -->
                                            <div class="tab-pane fade" id="moras-content">
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-hover mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th class="small">Fecha</th>
                                                                <th class="small">Días</th>
                                                                <th class="small">Monto</th>
                                                                <th class="small">Pagado</th>
                                                                <th class="small">Saldo</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @forelse($morasPendientes as $mora)
                                                            @php
                                                                $saldoMora = max(0, $mora->monto - $mora->monto_pagado);
                                                            @endphp
                                                            <tr>
                                                                <td class="small fw-bold">
                                                                    <i class="fas fa-calendar-alt me-1" style="color: #dc3545;"></i>
                                                                    {{ $mora->fecha->format('d/m/Y') }}
                                                                </td>
                                                                <td class="small">
                                                                    <span class="badge" style="border: 1px solid red">
                                                                        <i class="fas fa-clock me-1"></i>
                                                                        {{ $mora->dias_mora }} días
                                                                    </span>
                                                                </td>
                                                                <td class="small fw-bold" style="color: #495057;">S/ {{ number_format($mora->monto, 2) }}</td>
                                                                <td class="small" style="color: #28a745;">
                                                                    <i class="fas fa-check-circle me-1"></i>
                                                                    S/ {{ number_format($mora->monto_pagado ?? 0, 2) }}
                                                                </td>
                                                                <td class="small fw-bold" style="color: #dc3545; font-size: 0.95rem;">
                                                                    S/ {{ number_format($saldoMora, 2) }}
                                                                </td>
                                                            </tr>
                                                            @empty
                                                            <tr>
                                                                <td colspan="6" class="text-center py-4">
                                                                    <div class="text-muted">
                                                                        <i class="fas fa-check-circle" style="font-size: 2rem; color: #28a745;"></i>
                                                                        <p class="mt-2 mb-0">No hay moras pendientes</p>
                                                                        <small>Esta cuota no tiene moras asociadas</small>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            @endforelse
                                                        </tbody>
                                                        @if($morasPendientes->count() > 0)
                                                        <tfoot class="table-light">
                                                            <tr>
                                                                <td class="small fw-bold" colspan="4">TOTAL MORAS:</td>
                                                                <td class="small fw-bold text-danger" colspan="2">
                                                                    S/ {{ number_format($totalMorasPendientes, 2) }}
                                                                </td>
                                                            </tr>
                                                        </tfoot>
                                                        @endif
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Datos del Pago -->
                                <div class="card mb-4" style="background: var(--bg-primary); border: 1px solid var(--border-primary); box-shadow: var(--shadow-sm);">
                                    <div class="card-header" style="background: var(--bg-primary); border-bottom: 1px solid var(--border-primary);">
                                        <h6 class="mb-0" style="color: var(--text-primary);">
                                            <i class="fas fa-credit-card me-2" style="color: var(--text-secondary);"></i>Datos del Pago
                                        </h6>
                                    </div>
                                    <div class="card-body" style="background: var(--bg-primary);">
                                        <!-- Usuario que Registra -->
                                        <div class="mb-3">
                                            <label for="user_id" class="form-label fw-medium" style="color: var(--text-primary);">Usuario que Registra *</label>
                                            <select class="form-select @error('user_id') is-invalid @enderror" style="border: 1px solid var(--border-primary); background: var(--bg-primary); color: var(--text-primary);" 
                                                    id="user_id" name="user_id" required>
                                                <option value="">Seleccionar Usuario</option>
                                                @foreach($usuarios as $usuario)
                                                    <option value="{{ $usuario->id }}" 
                                                        {{ (old('user_id') ? old('user_id') == $usuario->id : auth()->id() == $usuario->id) ? 'selected' : '' }}>
                                                        {{ $usuario->codigo }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('user_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Métodos de Pago -->
                                        <div class="mb-3">
                                            <label class="form-label fw-medium" style="color: var(--text-primary);">Método de Pago *</label>
                                            <div class="payment-methods-grid">
                                                @foreach($metodosDePago as $metodo)
                                                    <div class="payment-method-option">
                                                        <input type="radio"
                                                               id="metodo{{ $metodo->id }}"
                                                               name="metodoPago"
                                                               value="{{ $metodo->id }}"
                                                               required>
                                                        <label for="metodo{{ $metodo->id }}" class="payment-label">
                                                            @switch($metodo->metodo_pago)
                                                                @case('EFECTIVO')
                                                                    <i class="fas fa-money-bill-wave text-success"></i>
                                                                    @break
                                                                @case('TRANSFERENCIA')
                                                                    <i class="fas fa-exchange-alt text-primary"></i>
                                                                    @break
                                                                @default
                                                                    <i class="fas fa-credit-card text-info"></i>
                                                            @endswitch
                                                            <span class="d-block small mt-1">{{ $metodo->metodo_pago }}</span>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <!-- Campos Adicionales para Transferencia/Tarjeta -->
                                        <div id="payment-extra-fields" class="payment-extra-section" style="display:none;">
                                            <h6 class="mb-3" style="color: var(--text-primary);">Datos de la Operación</h6>
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label for="entidad_bancaria" class="form-label small selectform" style="color: var(--text-primary);">Entidad Bancaria</label>
                                                    <select class="form-control form-control-sm" style="border: 1px solid var(--border-primary); background: var(--bg-primary); color: var(--text-primary);"
                                                            id="entidad_bancaria"
                                                            name="entidad_bancaria">
                                                        <option value="">Seleccionar entidad</option>
                                                        <!-- Las opciones se cargarán dinámicamente con JavaScript -->
                                                    </select>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="nro_operacion" class="form-label small" style="color: var(--text-primary);">Número de Operación</label>
                                                    <input type="text" class="form-control form-control-sm" style="border: 1px solid var(--border-primary); background: var(--bg-primary); color: var(--text-primary);"
                                                           id="nro_operacion"
                                                           name="nro_operacion"
                                                           placeholder="Ingrese número">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="fecha_operacion" class="form-label small" style="color: var(--text-primary);">Fecha de Operación</label>
                                                    <input type="datetime-local" class="form-control form-control-sm" style="border: 1px solid var(--border-primary); background: var(--bg-primary); color: var(--text-primary);"
                                                           id="fecha_operacion"
                                                           name="fecha_operacion"
                                                           value="{{ old('fecha_operacion', date('Y-m-d\TH:i')) }}"
                                                           max="{{ date('Y-m-d\TH:i') }}"
                                                           title="No se permite seleccionar fechas futuras">
                                                    <small class="form-text text-muted">No se pueden registrar pagos con fecha futura</small>
                                                </div>
                                                <div class="col-12">
                                                    <label for="voucher" class="form-label small" style="color: var(--text-primary);">Comprobante (Opcional)</label>
                                                    <div class="custom-file custom-file-sm">
                                                        <input type="file" class="custom-file-input" style="border: 1px solid var(--border-primary); background: var(--bg-primary); color: var(--text-primary);"
                                                               id="voucher"
                                                               name="voucher"
                                                               accept="image/*">
                                                        <label class="custom-file-label" for="voucher" style="border: 1px solid var(--border-primary); background: var(--bg-primary); color: var(--text-primary);">
                                                            Seleccionar archivo
                                                        </label>
                                                    </div>
                                                    <div id="voucher-preview-container" class="mt-2 text-center" style="display:none;">
                                                        <img id="voucher-preview"
                                                             class="img-fluid rounded max-height-100"
                                                             style="max-height: 100px; border: 1px solid var(--border-primary);"
                                                             alt="Vista previa">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Campos para Efectivo -->
                                        <div id="cash-code-container" class="payment-extra-section" style="display:none;">
                                            <h6 class="mb-3" style="color: var(--text-primary);">Datos del Pago en Efectivo</h6>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="codigo" class="form-label small" style="color: var(--text-primary);">Código de Operación</label>
                                                    <input type="text" class="form-control form-control-sm" style="border: 1px solid var(--border-primary); background: var(--bg-primary); color: var(--text-primary);"
                                                        id="codigo"
                                                        name="codigo"
                                                        placeholder="Ingrese código de operación">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="fecha_codigo" class="form-label small" style="color: var(--text-primary);">Fecha y Hora del Pago</label>
                                                    <input type="datetime-local" class="form-control form-control-sm" style="border: 1px solid var(--border-primary); background: var(--bg-primary); color: var(--text-primary);"
                                                        id="fecha_codigo"
                                                        name="fecha_codigo"
                                                        value="{{ old('fecha_codigo', date('Y-m-d\TH:i')) }}"
                                                        max="{{ date('Y-m-d\TH:i') }}"
                                                        title="No se permite seleccionar fechas futuras">
                                                    <small class="form-text text-muted">No se pueden registrar pagos con fecha futura</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna Derecha: Resumen del Pago -->
                            <div class="col-md-4">
                                <div class="card sticky-top" style="background: var(--bg-primary); border: 1px solid var(--border-primary); box-shadow: 0 2px 4px rgba(0,0,0,0.08); top: 20px;">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-calculator me-2"></i>Resumen del Pago
                                        </h6>
                                    </div>
                                    <div class="card-body" style="background: var(--bg-primary);">
                                        <!-- Montos de Pago -->
                                        <div class="mb-3">
                                            <label for="abono_cuotas" class="form-label fw-medium d-flex justify-content-between align-items-center">
                                                <span>
                                                    <i class="fas fa-money-check-alt me-2 text-primary"></i>
                                                    Monto en Cuotas
                                                </span>
                                                <a href="javascript:void(0)" onclick="showDetails('cuotas')" class="btn btn-sm btn-outline-primary" style="font-size: 0.7rem;">
                                                    <i class="fas fa-eye me-1"></i>Ver Detalles
                                                </a>
                                            </label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text bg-primary text-white fw-bold">S/</span>
                                                <input type="number"
                                                       step="0.01"
                                                       min="0"
                                                       class="form-control @error('abono_cuotas') is-invalid @enderror"
                                                       style="font-size: 1.2rem; font-weight: 600;"
                                                       id="abono_cuotas"
                                                       name="abono_cuotas"
                                                       value="{{ old('abono_cuotas', 0) }}"
                                                       oninput="calculateTotalPayment()">
                                            </div>
                                            @error('abono_cuotas')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="mt-2 p-2 rounded bg-light border">
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Total pendiente: <strong class="text-dark">S/ {{ number_format($cuotasPendientesData->sum('saldo_pendiente'), 2) }}</strong>
                                                </small>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="abono_moras" class="form-label fw-medium d-flex justify-content-between align-items-center">
                                                <span>
                                                    <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                                                    Monto en Moras
                                                </span>
                                                <a href="javascript:void(0)" onclick="showDetails('moras')" class="btn btn-sm btn-outline-danger" style="font-size: 0.7rem;">
                                                    <i class="fas fa-eye me-1"></i>Ver Detalles
                                                </a>
                                            </label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text bg-danger text-white fw-bold">S/</span>
                                                <input type="number"
                                                       step="0.01"
                                                       min="0"
                                                       class="form-control @error('abono_moras') is-invalid @enderror"
                                                       style="font-size: 1.2rem; font-weight: 600;"
                                                       id="abono_moras"
                                                       name="abono_moras"
                                                       value="{{ old('abono_moras', 0) }}">
                                            </div>
                                            @error('abono_moras')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            @if($totalMorasPendientes > 0)
                                                <div class="mt-2 p-2 rounded bg-light border border-danger">
                                                    <small class="text-danger">
                                                        <i class="fas fa-exclamation-circle me-1"></i>
                                                        Total pendiente: <strong>S/ {{ number_format($totalMorasPendientes, 2) }}</strong>
                                                    </small>
                                                </div>
                                            @else
                                                <div class="mt-2 p-2 rounded bg-light border border-success">
                                                    <small class="text-success">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        No hay moras pendientes
                                                    </small>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Resumen del Pago -->
                                        <div class="payment-summary p-3 rounded bg-light border">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="text-dark">
                                                    <i class="fas fa-receipt me-1"></i>
                                                    Subtotal Cuotas:
                                                </span>
                                                <span id="subtotal_cuotas" class="fw-bold text-primary">S/ 0.00</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-3">
                                                <span class="text-dark">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Subtotal Moras:
                                                </span>
                                                <span id="subtotal_moras" class="fw-bold text-danger">S/ 0.00</span>
                                            </div>
                                            <hr class="my-2">
                                            <div class="d-flex justify-content-between pt-2">
                                                <span class="fw-bold text-dark">
                                                    <i class="fas fa-dollar-sign me-1"></i>
                                                    TOTAL A PAGAR:
                                                </span>
                                                <span id="total_pago" class="fw-bold text-success" style="font-size: 1.5rem;">S/ 0.00</span>
                                            </div>
                                        </div>

                                        <!-- Observaciones -->
                                        <div class="mb-0">
                                            <label for="observaciones" class="form-label fw-medium" style="color: var(--text-primary);">Observaciones</label>
                                            <textarea class="form-control @error('observaciones') is-invalid @enderror" style="border: 1px solid var(--border-primary); background: var(--bg-primary); color: var(--text-primary);" 
                                                      id="observaciones" 
                                                      name="observaciones" 
                                                      rows="3" 
                                                      placeholder="Observaciones del pago (opcional)">{{ old('observaciones') }}</textarea>
                                            @error('observaciones')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- Botón de Pago -->
                                    <div class="card-footer text-center p-3" style="background: var(--bg-primary); border-top: 1px solid var(--border-primary);">
                                        <button type="submit" class="btn btn-success btn-lg w-100 mb-2" id="submitPayment">
                                            <i class="fas fa-check-circle me-2"></i>Registrar Pago
                                        </button>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Verifique todos los datos antes de confirmar
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Datos de las cuotas pendientes del convenio
const cuotasPendientes = @json($cuotasPendientesData);

// Datos de las moras pendientes de la cuota actual
const morasPendientesData = @json($morasPendientesJS ?? []);
const totalMorasPendientes = {{ $totalMorasPendientes ?? 0 }};

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewContainer = document.getElementById('voucher-preview-container');
            const previewImg = document.getElementById('voucher-preview');
            previewImg.src = e.target.result;
            previewContainer.style.display = 'block';

            // Actualizar el nombre del archivo en la etiqueta
            const fileName = input.files[0].name;
            const label = input.nextElementSibling;
            if (label && label.classList.contains('custom-file-label')) {
                label.textContent = fileName.length > 25 ? fileName.substring(0, 22) + '...' : fileName;
            }
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        const previewContainer = document.getElementById('voucher-preview-container');
        if (previewContainer) previewContainer.style.display = 'none';
        const label = input.nextElementSibling;
        if (label && label.classList.contains('custom-file-label')) {
            label.textContent = 'Seleccionar archivo';
        }
    }
}

// Cálculo de totales
function calculateTotalPayment() {
    const abonoCuotasInput = document.getElementById('abono_cuotas');
    const abonoMorasInput = document.getElementById('abono_moras');
    const subtotalCuotasElement = document.getElementById('subtotal_cuotas');
    const subtotalMorasElement = document.getElementById('subtotal_moras');
    const totalPagoElement = document.getElementById('total_pago');

    if (!abonoCuotasInput || !abonoMorasInput) return;

    const cuotasAmount = parseFloat(abonoCuotasInput.value) || 0;
    const morasAmount = parseFloat(abonoMorasInput.value) || 0;
    const totalPago = cuotasAmount + morasAmount;

    subtotalCuotasElement.textContent = `S/ ${cuotasAmount.toFixed(2)}`;
    subtotalMorasElement.textContent = `S/ ${morasAmount.toFixed(2)}`;
    totalPagoElement.textContent = `S/ ${totalPago.toFixed(2)}`;
}

// Mostrar detalles de cuotas o moras
function showDetails(type) {
    if (type === 'cuotas') {
        const cuotasTab = document.getElementById('cuotas-tab');
        const bsTab = new bootstrap.Tab(cuotasTab);
        bsTab.show();
    } else if (type === 'moras') {
        const morasTab = document.getElementById('moras-tab');
        const bsTab = new bootstrap.Tab(morasTab);
        bsTab.show();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Variables para totales
    const abonoCuotasInput = document.getElementById('abono_cuotas');
    const abonoMorasInput = document.getElementById('abono_moras');

    // Eventos para cálculo de totales
    if (abonoCuotasInput) abonoCuotasInput.addEventListener('input', calculateTotalPayment);
    if (abonoMorasInput) abonoMorasInput.addEventListener('input', calculateTotalPayment);

    // Inicializar totales
    calculateTotalPayment();

    // Configuración de entidades bancarias
    const entidadesBancarias = {
        transferencia: [
            'BCP', 'BBVA', 'Interbank', 'Scotiabank', 'BanBif',
            'MiBanco', 'Alfin', 'Banco de Comercio',
            'Banco Pichincha', 'Banco de la Nación'
        ],
        yape_plin: [
            'Yape', 'Plin', 'Dale', 'Tunki', 'Bim', 'Lukita', 'Agora Pay'
        ]
    };

    // Función para actualizar opciones de entidad bancaria
    function actualizarEntidadesBancarias(metodoPago) {
        const entidadSelect = document.getElementById('entidad_bancaria');
        if (!entidadSelect) return;

        // Limpiar opciones existentes
        entidadSelect.innerHTML = '<option value="">Seleccionar entidad</option>';

        let opciones = [];

        // Determinar qué opciones mostrar según el método de pago
        switch(metodoPago) {
            case '2': // TRANSFERENCIA/DEPÓSITO
                opciones = entidadesBancarias.transferencia;
                break;
            case '3': // YAPE/PLIN/BILLETERAS DIGITALES
                opciones = entidadesBancarias.yape_plin;
                break;
            case '4': // Si tienes otro método para billeteras digitales
                opciones = entidadesBancarias.yape_plin;
                break;
        }

        // Agregar las opciones al select
        opciones.forEach(entidad => {
            const option = document.createElement('option');
            option.value = entidad;
            option.textContent = entidad;
            entidadSelect.appendChild(option);
        });
    }

    // Manejo de métodos de pago
    const metodosRadio = document.querySelectorAll('input[name="metodoPago"]');
    const extraFields = document.getElementById('payment-extra-fields');
    const cashContainer = document.getElementById('cash-code-container');

    metodosRadio.forEach(function(radio) {
        radio.addEventListener('change', function() {
            // Ocultar todos los campos adicionales
            extraFields.style.display = 'none';
            cashContainer.style.display = 'none';

            // Mostrar campos específicos según el método de pago
            switch(this.value) {
                case '1': // EFECTIVO
                    cashContainer.style.display = 'block';
                    break;
                case '2': // TRANSFERENCIA
                case '3': // TARJETA/YAPE/PLIN
                case '4': // Si tienes otro ID para billeteras
                    extraFields.style.display = 'block';
                    actualizarEntidadesBancarias(this.value);
                    break;
            }
        });
    });

    // Calcular distribución inicial
    calcularDistribucionPago();

    // Gestión de archivo de voucher con custom-file
    const voucherInput = document.getElementById('voucher');
    if (voucherInput) {
        voucherInput.addEventListener('change', function(e) {
            previewImage(this);
        });
    }

    // Funcionalidad para mostrar la etiqueta del archivo seleccionado
    document.querySelectorAll('.custom-file-input').forEach(inputElement => {
        inputElement.addEventListener('change', function(e){
            const fileName = this.files[0]?.name;
            const nextSibling = this.nextElementSibling;
            if (fileName) {
                nextSibling.textContent = fileName.length > 25 ? fileName.substring(0, 22) + '...' : fileName;
            } else {
                nextSibling.textContent = 'Seleccionar archivo';
            }
        });
    });

    // Validación del formulario
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const montoPago = parseFloat(document.getElementById('monto_pago').value);

        // Validar monto
        if (montoPago <= 0) {
            alert('El monto a pagar debe ser mayor a 0');
            return false;
        }

        // Validar método de pago
        const selectedPaymentMethod = document.querySelector('input[name="metodoPago"]:checked');
        if (!selectedPaymentMethod) {
            alert('Debe seleccionar un método de pago');
            return false;
        }

        // Validar campos adicionales según el método de pago
        const paymentMethodValue = selectedPaymentMethod.value;

        // Validación para método de EFECTIVO
        if (paymentMethodValue === '1') {
            const fechaCodigoEfectivo = document.getElementById('fecha_codigo').value.trim();
            if (!fechaCodigoEfectivo) {
                alert('Debe ingresar la fecha y hora del pago en efectivo');
                return false;
            }
        }

        // Validación para métodos de transferencia o tarjeta
        if (['2', '3'].includes(paymentMethodValue)) {
            const nroOperacion = document.getElementById('nro_operacion').value.trim();
            const fechaOperacion = document.getElementById('fecha_operacion').value;

            if (!nroOperacion) {
                alert('Debe ingresar un número de operación');
                return false;
            }

            if (!fechaOperacion) {
                alert('Debe seleccionar una fecha de operación');
                return false;
            }

            // Validar que la fecha de operación no sea futura
            const fechaOperacionDate = new Date(fechaOperacion);
            const fechaActual = new Date();
            if (fechaOperacionDate > fechaActual) {
                alert('No se pueden registrar pagos con fecha futura. Seleccione una fecha actual o pasada.');
                return false;
            }
        }

        // Validar fecha del pago en efectivo (si está presente)
        const fechaCodigo = document.getElementById('fecha_codigo').value;
        if (fechaCodigo) {
            const fechaCodigoDate = new Date(fechaCodigo);
            const fechaActual = new Date();
            if (fechaCodigoDate > fechaActual) {
                alert('No se pueden registrar pagos con fecha futura. Seleccione una fecha actual o pasada.');
                return false;
            }
        }

        const cuotasAmount = parseFloat(document.getElementById('abono_cuotas').value) || 0;
        const morasAmount = parseFloat(document.getElementById('abono_moras').value) || 0;
        const totalPago = cuotasAmount + morasAmount;

        // Validar monto
        if (totalPago <= 0) {
            alert('Debe ingresar un monto mayor a cero en cuotas o moras');
            return false;
        }

        // Crear mensaje de confirmación detallado
        let mensaje = '¿Confirmar el registro de este pago?\n\n';
        mensaje += 'Total: S/ ' + totalPago.toFixed(2) + '\n';
        mensaje += '  - Cuotas: S/ ' + cuotasAmount.toFixed(2) + '\n';
        mensaje += '  - Moras: S/ ' + morasAmount.toFixed(2);

        if (confirm(mensaje)) {
            // Deshabilitar botón de submit para evitar doble envío
            const submitButton = document.getElementById('submitPayment');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
            }

            // Enviar formulario
            this.submit();
        }
    });
});
</script>

<style>
    /* Método de pago en grid */
    .payment-methods-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 10px;
        margin-bottom: 1rem;
    }

    .payment-method-option {
        position: relative;
    }

    .payment-method-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        cursor: pointer;
    }

    .payment-label {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 10px 8px;
        border: 1px solid var(--border-primary);
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        background-color: white;
        transition: all 0.3s ease;
        height: 70px;
        font-size: 0.8rem;
    }

    .payment-label i {
        font-size: 1.1rem;
        margin-bottom: 4px;
    }

    .payment-method-option input[type="radio"]:checked + .payment-label {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
        box-shadow: 0 2px 4px rgba(13,110,253,0.3);
    }

    /* Secciones de campos extra */
    .payment-extra-section {
        background-color: #f8f9fa;
        border: 1px solid var(--border-primary);
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }
    .selectform{
        height: 52px;
        border-radius: 8px;
        border: 1px solid var(--border-primary);
    }

    /* Form controls más compactos */
    .form-control-sm {
        height: calc(1.5em + 0.4rem + 2px);
        font-size: 0.8rem;
        padding: 0.2rem 0.5rem;
    }

    .custom-file-sm .custom-file-label {
        height: calc(1.5em + 0.4rem + 2px);
        padding: 0.2rem 0.5rem;
        font-size: 0.8rem;
        line-height: 1.5;
    }

    .custom-file-input:focus ~ .custom-file-label {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.15rem rgba(30, 136, 229, 0.15);
    }

    /* Vista previa de imagen más pequeña */
    .max-height-100 {
        max-height: 100px;
    }

    /* Form labels más pequeños */
    .form-label.small {
        font-size: 0.75rem;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        .payment-methods-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .payment-methods-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Mejoras adicionales para UX */
    .payment-method-option:hover .payment-label {
        border-color: #0d6efd;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Estilos para tabs mejorados */
    .nav-tabs .nav-link {
        color: #6c757d;
        transition: all 0.2s ease;
    }

    .nav-tabs .nav-link:hover {
        color: #495057;
    }

    .nav-tabs .nav-link.active {
        color: #0d6efd;
        font-weight: 500;
    }

    /* Efectos hover en tabla */
    .table-hover tbody tr:hover {
        background-color: rgba(0,0,0,0.02);
        transition: background-color 0.2s ease;
    }

    /* Sticky sidebar */
    @media (min-width: 768px) {
        .sticky-top {
            position: sticky;
            z-index: 1020;
        }
    }
</style>

@endsection