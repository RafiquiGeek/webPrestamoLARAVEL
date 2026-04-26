<!-- Monto de mora diario -->
<div class="mb-3">
    <label for="monto_mora_diaria" class="form-label fw-medium" style="color: var(--text-primary);">Monto de mora por día *</label>
    <div class="input-group">
        <span class="input-group-text" style="background: var(--primary); color: white; border-color: var(--primary);">S/.</span>
        <input type="number" step="0.01" class="form-control" name="monto_mora_diaria" id="monto_mora_diaria" value="0.00" min="0" required style="border-color: var(--border-primary);">
    </div>
    <small style="color: var(--text-secondary);">Ingrese el monto de mora que se aplicará por cada día de atraso.</small>
</div>
@extends('layouts.admin')

@section('title', 'Crear Convenio de Pago')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card" style="background: var(--bg-primary); border: 1px solid var(--border-primary); box-shadow: var(--shadow-md);">
                <div class="card-header" style="background: var(--bg-primary); border-bottom: 1px solid var(--border-primary);">
                    <h3 class="card-title mb-0" style="color: var(--text-primary);">
                        <i class="fas fa-handshake me-2" style="color: var(--primary);"></i>Crear Convenio de Pago
                    </h3>
                </div>

                <form action="{{ route('admin.convenios.store') }}" method="POST" id="convenioForm">
                    @csrf
                    
                    <div class="card-body" style="background: var(--bg-secondary);">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($prestamo)
                            <input type="hidden" name="prestamo_id" value="{{ $prestamo->id }}">
                            
                            <!-- Información del Cliente -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card" style="background: var(--bg-primary); border: 1px solid var(--border-primary); box-shadow: var(--shadow-sm);">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle p-2 me-3" style="background: var(--primary);">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                                <div>
                                                    <h5 class="mb-1" style="color: var(--text-primary);">{{ $prestamo->cliente->persona->nombres }} {{ $prestamo->cliente->persona->ape_pat }} {{ $prestamo->cliente->persona->ape_mat }}</h5>
                                                    <span style="color: var(--text-secondary);">Préstamo #{{ $prestamo->id }} | DNI: {{ $prestamo->cliente->persona->documento }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <!-- Opción: Convenio por Cuotas -->
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="tipo_convenio" id="tipo_cuotas"
                                            value="cuotas" checked onchange="cambiarTipoConvenio()">
                                    <label class="tipo-convenio-card" for="tipo_cuotas" id="label_tipo_cuotas">
                                        <div class="tipo-convenio-icon-wrapper" style="background: linear-gradient(135deg, var(--primary) 0%, #0056b3 100%);">
                                            <i class="fas fa-calendar-alt text-white"></i>
                                        </div>
                                        <div class="tipo-convenio-content">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h6 class="mb-0 fw-bold" style="color: var(--text-primary);">Convenio por Cuotas</h6>
                                            </div>
                                            <small class="text-muted d-block mt-1">Pagos semanales programados</small>
                                        </div>
                                    </label>
                                </div>

                                <!-- Opción: Convenio Flexible -->
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="tipo_convenio" id="tipo_flexible"
                                            value="flexible" onchange="cambiarTipoConvenio()">
                                    <label class="tipo-convenio-card" for="tipo_flexible" id="label_tipo_flexible">
                                        <div class="tipo-convenio-icon-wrapper" style="background: linear-gradient(135deg, var(--success) 0%, #28a745 100%);">
                                            <i class="fas fa-hand-holding-usd text-white"></i>
                                        </div>
                                        <div class="tipo-convenio-content">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h6 class="mb-0 fw-bold" style="color: var(--text-primary);">Convenio Flexible</h6>
                                            </div>
                                            <small class="text-muted d-block mt-1">Sin cuotas, sin fechas límite</small>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Contenedor para Convenio por Cuotas -->
                            <div id="contenedor_tipo_cuotas" style="display: block;">
                                <div class="row">
                                    <div class="col-md-6">
                                    <!-- Configuración del Convenio -->
                                    <div class="card" style="background: var(--bg-primary); border: 1px solid var(--border-primary); box-shadow: var(--shadow-sm); border-radius: 12px;">
                                        <div class="card-header" style="border-bottom: 1px solid var(--border-primary); background: linear-gradient(135deg, rgba(0,123,255,0.05) 0%, rgba(0,123,255,0.02) 100%);">
                                            <h6 class="mb-0 fw-bold" style="color: var(--text-primary);">
                                                <i class="fas fa-handshake me-2 text-primary"></i>Configuración del Convenio
                                            </h6>
                                        </div>
                                        <div class="card-body" style="padding: 1.5rem;">
                                            @php
                                                // Filtrar cuotas no pagadas (estado != 2)
                                                $cuotasPendientes = $prestamo->cuotas->filter(function($cuota) {
                                                    return $cuota->estado->value != 2; // NO PAGADO
                                                });

                                                $montoCapitalTotal = $cuotasPendientes->sum('monto');

                                                // Calcular saldo real de moras (monto - monto_pagado)
                                                    // Sumar solo moras pendientes de MoraCuota, sin considerar abonos_mora_favor
                                                    $morasPendientes = \App\Models\MoraCuota::whereHas('cuota', function($query) use ($prestamo) {
                                                        $query->where('prestamo_id', $prestamo->id);
                                                    })
                                                    ->whereIn('estado', [0, 1])
                                                    ->selectRaw('COALESCE(SUM(monto - COALESCE(monto_pagado, 0)), 0) as saldo_moras')
                                                    ->first();

                                                    $montoMorasTotal = $morasPendientes ? $morasPendientes->saldo_moras : 0;
                                            @endphp

                                            <!-- Resumen de Deuda -->
                                            <div class="row g-2 mb-4">
                                                <div class="col-3">
                                                    <div class="p-2 rounded-3 text-center" style="background: linear-gradient(135deg, rgba(108,117,125,0.08) 0%, rgba(108,117,125,0.03) 100%); border: 1px solid rgba(108,117,125,0.15);">
                                                        <small style="color: var(--text-secondary); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px;" class="d-block mb-1 fw-medium">Cuotas</small>
                                                        <div style="color: var(--text-primary); font-size: 1.1rem;" class="fw-bold">{{ $cuotasPendientes->count() }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="p-2 rounded-3 text-center" style="background: linear-gradient(135deg, rgba(0,123,255,0.08) 0%, rgba(0,123,255,0.03) 100%); border: 1px solid rgba(0,123,255,0.15);">
                                                        <small style="color: var(--text-secondary); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px;" class="d-block mb-1 fw-medium">Capital</small>
                                                        <div style="color: var(--primary); font-size: 0.85rem;" class="fw-bold">{{ number_format($montoCapitalTotal, 2) }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="p-2 rounded-3 text-center" style="background: linear-gradient(135deg, rgba(220,53,69,0.08) 0%, rgba(220,53,69,0.03) 100%); border: 1px solid rgba(220,53,69,0.15);">
                                                        <small style="color: var(--text-secondary); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px;" class="d-block mb-1 fw-medium">Moras</small>
                                                        <div style="color: #dc3545; font-size: 0.85rem;" class="fw-bold">{{ number_format($montoMorasTotal, 2) }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="p-2 rounded-3 text-center" style="background: linear-gradient(135deg, rgba(40,167,69,0.1) 0%, rgba(40,167,69,0.04) 100%); border: 1px solid rgba(40,167,69,0.2);">
                                                        <small style="color: var(--text-secondary); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px;" class="d-block mb-1 fw-medium">Total</small>
                                                        <div style="color: var(--success); font-size: 0.85rem;" class="fw-bold">{{ number_format($montoCapitalTotal + $montoMorasTotal, 2) }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Campos Ocultos -->
                                            <input type="hidden" name="monto_capital" id="monto_capital" value="{{ $montoCapitalTotal }}">
                                            <input type="hidden" name="monto_moras" id="monto_moras" value="{{ $montoMorasTotal }}">

                                            <!-- Descuento -->
                                            <div class="mb-4">
                                                <label for="descuento_moras" class="form-label fw-bold mb-2" style="color: var(--text-primary); font-size: 0.9rem;">
                                                    <i class="fas fa-percent me-2 text-primary" style="font-size: 0.85rem;"></i>
                                                    Descuento sobre Moras
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, var(--primary) 0%, #0056b3 100%); color: white; border: none; width: 50px; font-weight: 600;">S/.</span>
                                                    <input type="number" step="0.01" class="form-control"
                                                        name="descuento_moras" id="descuento_moras"
                                                        value="0" min="0" max="{{ $montoMorasTotal }}"
                                                        placeholder="0.00" onchange="actualizarTotal()"
                                                        style="border: 2px solid var(--border-primary); border-left: none; font-size: 1.1rem; font-weight: 500;height: 53px;">
                                                </div>
                                                @if($montoMorasTotal > 0)
                                                    <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        Máximo: <strong>S/. {{ number_format($montoMorasTotal, 2) }}</strong>
                                                    </small>
                                                @endif
                                                    <!-- Switch para aplicar moras a favor como descuento -->
                                                    @php
                                                        $morasFavor = \App\Models\AbonoMoraFavor::whereHas('cuota', function($query) use ($prestamo) {
                                                            $query->where('prestamo_id', $prestamo->id);
                                                        })
                                                        ->where('estado', 1)
                                                        ->selectRaw('COALESCE(SUM(saldo_favor), 0) as saldo_favor')
                                                        ->first();
                                                        $montoMorasFavor = $morasFavor ? $morasFavor->saldo_favor : 0;
                                                    @endphp
                                                    @if($montoMorasFavor > 0)
                                                    <div class="p-3 rounded-3 mt-2" style="background: linear-gradient(135deg, rgba(40,167,69,0.06) 0%, rgba(40,167,69,0.01) 100%); border: 1.5px solid rgba(40,167,69,0.2);">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <div class="d-flex align-items-center gap-3">
                                                                <div class="d-flex align-items-center justify-content-center rounded-2" style="width: 32px; height: 32px; background: rgba(40,167,69,0.12); flex-shrink: 0;">
                                                                    <i class="fas fa-gift text-success" style="font-size: 0.9rem;"></i>
                                                                </div>
                                                                <div class="flex-grow-1">
                                                                    <label class="fw-bold d-block mb-0" style="color: var(--text-primary); font-size: 0.9rem; cursor: pointer;" for="aplicarMorasFavor">Aplicar moras a favor</label>
                                                                    <small class="text-muted d-block" style="font-size: 0.72rem; margin-top: 2px;">
                                                                        Disponible: <strong class="text-success">S/. {{ number_format($montoMorasFavor, 2) }}</strong>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                            <div class="form-check form-switch mb-0" style="padding-left: 0;">
                                                                <input class="form-check-input" type="checkbox" id="aplicarMorasFavor" onchange="actualizarTotal()" role="switch" style="width: 2.5rem; height: 1.25rem; cursor: pointer; margin-left: 0;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" id="monto_moras_favor" value="{{ $montoMorasFavor }}">
                                                    @endif
                                            </div>

                                            <!-- Total del Convenio con Edición Inline -->
                                            <div class="mb-4">
                                                <label class="form-label fw-bold mb-2" style="color: var(--text-primary); font-size: 0.9rem;">
                                                    <i class="fas fa-money-bill-wave me-2 text-primary" style="font-size: 0.85rem;"></i>
                                                    Total del Convenio *
                                                </label>
                                                <div class="p-3 rounded-3 text-center position-relative" style="background: linear-gradient(135deg, var(--primary) 0%, #0056b3 100%); color: white; box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);">
                                                    <!-- Vista Display -->
                                                    <div id="total_display_view" style="cursor: pointer;" onclick="toggleTotalEdit(true)">
                                                        <small class="d-block opacity-75 mb-1" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Monto del Convenio</small>
                                                        <h3 class="mb-0 fw-bold" id="total_convenio_display" style="font-size: 1.75rem;">S/. {{ number_format($montoCapitalTotal + $montoMorasTotal, 2) }}</h3>
                                                        <div class="mt-2 d-flex align-items-center justify-content-center gap-2">
                                                            <i class="fas fa-edit" style="font-size: 0.8rem; opacity: 0.7;"></i>
                                                            <small style="opacity: 0.8; font-size: 0.7rem;">Click para editar</small>
                                                        </div>
                                                    </div>

                                                    <!-- Vista Edición -->
                                                    <div id="total_edit_view" style="display: none;">
                                                        <small class="d-block opacity-75 mb-2">Editar Monto del Convenio</small>
                                                        <div class="d-flex align-items-center justify-content-center">
                                                            <span class="me-2">S/.</span>
                                                            <input type="number" step="0.01"
                                                                id="total_convenio_editable"
                                                                value="{{ $montoCapitalTotal + $montoMorasTotal }}"
                                                                min="1" required
                                                                class="form-control text-center fw-bold"
                                                                style="background: rgba(255,255,255,0.9); border: 2px solid rgba(255,255,255,0.3); font-size: 1.2em; width: 150px; color: var(--text-primary);"
                                                                onchange="actualizarTotalEditable()"
                                                                onblur="toggleTotalEdit(false)"
                                                                onkeypress="if(event.key==='Enter') this.blur()">
                                                            <button type="button" class="btn btn-sm ms-2" onclick="toggleTotalEdit(false)"
                                                                    style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white;">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <!-- Campo oculto para enviar el total convenio -->
                                                    <input type="hidden" name="total_convenio" id="total_convenio_hidden" value="{{ $montoCapitalTotal + $montoMorasTotal }}">
                                                </div>
                                                <small style="color: var(--text-secondary);" class="d-block text-center mt-1">
                                                    Deuda original: S/. {{ number_format($montoCapitalTotal + $montoMorasTotal, 2) }}
                                                </small>
                                            </div>

                                            <!-- Selección de Plazo -->
                                            <div class="mb-4">
                                                <label for="numero_cuotas" class="form-label fw-bold mb-2" style="color: var(--text-primary); font-size: 0.9rem;">
                                                    <i class="fas fa-calculator me-2 text-primary" style="font-size: 0.85rem;"></i>
                                                    Número de Cuotas Semanales *
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, var(--primary) 0%, #0056b3 100%); color: white; border: none; width: 50px;">
                                                        <i class="fas fa-hashtag"></i>
                                                    </span>
                                                    <input type="number"
                                                        class="form-control text-center fw-bold"
                                                        name="numero_cuotas"
                                                        id="numero_cuotas"
                                                        min="1"
                                                        max="20"
                                                        value="4"
                                                        required
                                                        onchange="generarCronograma()"
                                                        oninput="validarCuotas(this)"
                                                        style="border: 2px solid var(--border-primary); border-left: none; border-right: none; font-size: 1.25rem;height: 53px;">
                                                    <span class="input-group-text d-flex align-items-center" style="background: rgba(0,123,255,0.08); color: var(--primary); border: 2px solid var(--border-primary); border-left: none; font-weight: 600; min-width: 80px; justify-content: center;">cuotas</span>
                                                </div>
                                                <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Entre 1 y 20 cuotas
                                                </small>
                                            </div>

                                                <!-- Switch para decidir si el convenio genera moras -->
                                                <div class="mb-4">
                                                    <div class="p-3 rounded-3" style="background: linear-gradient(135deg, rgba(220,53,69,0.06) 0%, rgba(220,53,69,0.01) 100%); border: 1.5px solid rgba(220,53,69,0.2);">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <div class="d-flex align-items-center gap-3">
                                                                <div class="d-flex align-items-center justify-content-center rounded-2" style="width: 32px; height: 32px; background: rgba(220,53,69,0.1); flex-shrink: 0;">
                                                                    <i class="fas fa-exclamation-triangle" style="color: #dc3545; font-size: 0.9rem;"></i>
                                                                </div>
                                                                <div class="flex-grow-1">
                                                                    <label class="form-label fw-bold mb-0" style="color: var(--text-primary); font-size: 0.9rem; cursor: pointer;" for="generaMoras">Generar moras por atraso</label>
                                                                </div>
                                                            </div>
                                                            <div class="form-check form-switch mb-0" style="padding-left: 0;">
                                                                <input class="form-check-input" type="checkbox" id="generaMoras" name="genera_moras" onchange="toggleMoraField()" role="switch" style="width: 2.5rem; height: 1.25rem; cursor: pointer; margin-left: -50px;margin-top: -10px;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Monto de mora diaria (solo visible si genera_moras está activo) -->
                                                <div class="mb-4" id="moraFieldContainer" style="display: none;">
                                                    <label for="monto_mora_diaria" class="form-label fw-bold mb-2" style="color: var(--text-primary); font-size: 0.9rem;">
                                                        <i class="fas fa-dollar-sign me-2" style="color: #dc3545; font-size: 0.85rem;"></i>
                                                        Monto de mora por día *
                                                    </label>
                                                    <div class="input-group" style="height: 50px;">
                                                        <span class="input-group-text d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border: none; width: 50px; font-weight: 600;">S/.</span>
                                                        <input type="number" step="0.01" class="form-control" name="monto_mora_diaria" id="monto_mora_diaria"
                                                            value="0.00" min="0" placeholder="0.00"
                                                            style="border: 2px solid var(--border-primary); border-left: none; font-size: 1.1rem; font-weight: 500;height: 53px;">
                                                    </div>
                                                    <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        Por cada día de atraso
                                                    </small>
                                                </div>
                                            </div>

                                            <!-- Fecha de Inicio -->
                                            <div class="mb-4">
                                                <label for="fecha_inicio" class="form-label fw-bold mb-2" style="color: var(--text-primary); font-size: 0.9rem;">
                                                    <i class="fas fa-calendar me-2 text-primary" style="font-size: 0.85rem;"></i>
                                                    Fecha del Primer Pago *
                                                </label>
                                                <input type="date" class="form-control"
                                                    name="fecha_inicio" id="fecha_inicio"
                                                    value="{{ date('Y-m-d', strtotime('+7 days')) }}"
                                                    required onchange="generarCronograma()"
                                                    style="border: 2px solid var(--border-primary); font-size: 1rem; height: 50px;">
                                                <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">
                                                    <i class="fas fa-lightbulb me-1"></i>
                                                    Sugerencia: {{ date('d/m/Y', strtotime('+7 days')) }}
                                                </small>
                                            </div>

                                            <!-- Observaciones -->
                                            <div class="mb-0">
                                                <label for="observaciones" class="form-label fw-bold mb-2" style="color: var(--text-primary); font-size: 0.9rem;">
                                                    <i class="fas fa-comment-alt me-2 text-primary" style="font-size: 0.85rem;"></i>
                                                    Observaciones
                                                </label>
                                                <textarea class="form-control" name="observaciones" id="observaciones"
                                                        rows="3" placeholder="Observaciones adicionales..."
                                                        style="border: 2px solid var(--border-primary); border-radius: 8px; font-size: 0.95rem;"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card h-100" style="background: var(--bg-primary); border: 1px solid var(--border-primary); box-shadow: var(--shadow-sm); border-radius: 12px;">
                                            <div class="card-header" style="border-bottom: 1px solid var(--border-primary); background: linear-gradient(135deg, rgba(40,167,69,0.05) 0%, rgba(40,167,69,0.02) 100%);">
                                                <h6 class="mb-0 fw-bold" style="color: var(--text-primary);">
                                                    <i class="fas fa-calendar me-2 text-success"></i>Cronograma de Pagos
                                                </h6>
                                            </div>
                                            <div class="card-body" style="padding: 1.5rem;">
                                                <!-- Placeholder inicial -->
                                                <div id="cronogramaPlaceholder" class="text-center py-5">
                                                    <div style="color: var(--text-tertiary);">
                                                        <i class="fas fa-calendar-alt fa-3x mb-3" style="opacity: 0.25;"></i>
                                                        <h6 style="color: var(--text-secondary);">Cronograma de Pagos</h6>
                                                        <p class="small mb-0">Seleccione el plazo para generar el cronograma</p>
                                                    </div>
                                                </div>
                                                
                                                <!-- Tabla del cronograma -->
                                                <div id="cronogramaContainer" style="display: none;">
                                                    <div class="table-responsive">
                                                        <table class="table table-sm">
                                                            <thead style="background: var(--bg-tertiary);">
                                                                <tr>
                                                                    <th class="py-2" style="color: var(--text-primary); border: 1px solid var(--border-primary);">N°</th>
                                                                    <th class="py-2" style="color: var(--text-primary); border: 1px solid var(--border-primary);">Fecha Vencimiento</th>
                                                                    <th class="text-end py-2" style="color: var(--text-primary); border: 1px solid var(--border-primary);">Monto</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="cronogramaBody">
                                                                <!-- Se genera dinámicamente -->
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    
                                                    <!-- Resumen -->
                                                    <div class="mt-3 p-3 rounded" style="background: var(--bg-tertiary);">
                                                        <div class="row text-center">
                                                            <div class="col-6">
                                                                <small style="color: var(--text-secondary);" class="d-block">Total Cuotas</small>
                                                                <span class="fw-bold" style="color: var(--text-primary);" id="resumenCuotas">-</span>
                                                            </div>
                                                            <div class="col-6">
                                                                <small style="color: var(--text-secondary);" class="d-block">Valor Semanal</small>
                                                                <span class="fw-bold" style="color: var(--success);" id="resumenValor">-</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>
                            <!-- Fin Contenedor tipo Cuotas -->

                            <!-- Contenedor para Convenio Flexible -->
                            <div id="contenedor_tipo_flexible" style="display: none;">
                                <div class="row">
                                    <!-- COLUMNA IZQUIERDA: Configuración Simple -->
                                    <div class="col-md-6">
                                        <div class="card" style="background: var(--bg-primary); border: 2px solid var(--success); box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);">
                                            <div class="card-header" style="background: linear-gradient(135deg, rgba(40,167,69,0.1) 0%, rgba(40,167,69,0.05) 100%); border-bottom: 2px solid var(--success);">
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle p-2 me-2" style="background: var(--success);">
                                                        <i class="fas fa-cog text-white"></i>
                                                    </div>
                                                    <h6 class="mb-0 fw-bold" style="color: var(--text-primary);">Configuración del Convenio Flexible</h6>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                @php
                                                    // Reutilizar cálculos existentes
                                                    $cuotasPendientes = $prestamo->cuotas->filter(function($cuota) {
                                                        return $cuota->estado->value != 2;
                                                    });
                                                    $montoCapitalTotal = $cuotasPendientes->sum('monto');
                                                    $morasPendientes = \App\Models\MoraCuota::whereHas('cuota', function($query) use ($prestamo) {
                                                        $query->where('prestamo_id', $prestamo->id);
                                                    })
                                                    ->whereIn('estado', [0, 1])
                                                    ->selectRaw('COALESCE(SUM(monto - COALESCE(monto_pagado, 0)), 0) as saldo_moras')
                                                    ->first();
                                                    $montoMorasTotal = $morasPendientes ? $morasPendientes->saldo_moras : 0;
                                                @endphp

                                                <!-- Resumen de Deuda -->
                                                <div class="row mb-4 text-center">
                                                    <div class="col-4">
                                                        <div class="p-2 rounded" style="background: var(--bg-tertiary); border: 1px solid var(--border-primary);">
                                                            <small style="color: var(--text-secondary);" class="d-block">Capital</small>
                                                            <h6 style="color: var(--text-primary);" class="mb-0 fw-bold">{{ number_format($montoCapitalTotal, 2) }}</h6>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="p-2 rounded" style="background: var(--bg-tertiary); border: 1px solid var(--border-primary);">
                                                            <small style="color: var(--text-secondary);" class="d-block">Moras</small>
                                                            <h6 style="color: var(--text-primary);" class="mb-0 fw-bold">{{ number_format($montoMorasTotal, 2) }}</h6>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="p-2 rounded" style="background: var(--bg-tertiary); border: 1px solid var(--border-primary);">
                                                            <small style="color: var(--text-secondary);" class="d-block">Total Deuda</small>
                                                            <h6 style="color: var(--success);" class="mb-0 fw-bold">{{ number_format($montoCapitalTotal + $montoMorasTotal, 2) }}</h6>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Campos Ocultos -->
                                                <input type="hidden" name="monto_capital_flexible" id="monto_capital_flexible" value="{{ $montoCapitalTotal }}">
                                                <input type="hidden" name="monto_moras_flexible" id="monto_moras_flexible" value="{{ $montoMorasTotal }}">

                                                <!-- Descuento -->
                                                <div class="mb-3">
                                                    <label for="descuento_moras_flexible" class="form-label fw-medium mb-2" style="color: var(--text-primary);">
                                                        <i class="fas fa-percent me-2"></i>Descuento sobre Moras
                                                    </label>
                                                    <div class="input-group input-group-lg">
                                                        <span class="input-group-text" style="height: 50px!important;background: linear-gradient(135deg, var(--primary) 0%, #0056b3 100%); color: white; border: none; font-weight: bold;">
                                                            S/.
                                                        </span>
                                                        <input type="number" step="0.01" class="form-control"
                                                               name="descuento_moras_flexible" id="descuento_moras_flexible"
                                                               value="0" min="0" max="{{ $montoMorasTotal }}"
                                                               placeholder="0.00" onchange="actualizarTotalFlexible()"
                                                               style="border: 2px solid var(--border-primary); border-left: none; font-size: 1.1rem; font-weight: 500; transition: all 0.3s ease; height:50px;"
                                                               onfocus="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 0 0 0.2rem rgba(0, 123, 255, 0.15)';"
                                                               onblur="this.style.borderColor='var(--border-primary)'; this.style.boxShadow='none';">
                                                    </div>
                                                    @if($montoMorasTotal > 0)
                                                        <small class="d-block mt-1" style="color: var(--text-secondary);">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            Máximo permitido: <strong>S/. {{ number_format($montoMorasTotal, 2) }}</strong>
                                                        </small>
                                                    @endif
                                                </div>

                                                <!-- Total del Convenio Flexible -->
                                                <div class="mb-3">
                                                    <label class="form-label fw-medium mb-3" style="color: var(--text-primary);">
                                                        <i class="fas fa-money-bill-wave me-2"></i>Monto Total a Pagar *
                                                    </label>
                                                    <div class="p-4 rounded-3 text-center position-relative" style="background: linear-gradient(135deg, var(--success) 0%, #28a745 100%); color: white; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);">
                                                        <div class="position-absolute top-0 end-0 m-2">
                                                            <span class="badge" style="background: rgba(255,255,255,0.2); color: white; font-size: 0.7rem;">
                                                                <i class="fas fa-infinity me-1"></i>Flexible
                                                            </span>
                                                        </div>
                                                        <small class="d-block opacity-75 mb-1">Total del Convenio Flexible</small>
                                                        <h2 class="mb-0 fw-bold" id="total_convenio_flexible_display" style="font-size: 2.5rem; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                                            S/. {{ number_format($montoCapitalTotal + $montoMorasTotal, 2) }}
                                                        </h2>
                                                        <input type="hidden" name="total_convenio_flexible" id="total_convenio_flexible_hidden"
                                                               value="{{ $montoCapitalTotal + $montoMorasTotal }}">
                                                        <div class="mt-2 pt-2" style="border-top: 1px solid rgba(255,255,255,0.3);">
                                                            <small class="opacity-75">
                                                                <i class="fas fa-calendar-alt me-1"></i>
                                                                Sin fecha límite de pago
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Observaciones -->
                                                <div class="mb-0">
                                                    <label for="observaciones_flexible" class="form-label fw-medium mb-2" style="color: var(--text-primary);">
                                                        <i class="fas fa-comment-alt me-2"></i>Observaciones
                                                    </label>
                                                    <textarea class="form-control" name="observaciones_flexible" id="observaciones_flexible"
                                                              rows="4" placeholder="Ej: Cliente acuerda pagar según sus ingresos semanales..."
                                                              style="border: 2px solid var(--border-primary); border-radius: 8px; transition: all 0.3s ease;"
                                                              onfocus="this.style.borderColor='var(--success)'; this.style.boxShadow='0 0 0 0.2rem rgba(40, 167, 69, 0.15)';"
                                                              onblur="this.style.borderColor='var(--border-primary)'; this.style.boxShadow='none';"></textarea>
                                                    <small class="text-muted d-block mt-1">
                                                        <i class="fas fa-lightbulb me-1"></i>
                                                        Puede agregar notas sobre el acuerdo con el cliente
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- COLUMNA DERECHA: Información del Convenio Flexible -->
                                    <div class="col-md-6">
                                        <div class="card h-100" style="background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%); border: 2px solid var(--success); box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);">
                                            <div class="card-header" style="background: linear-gradient(135deg, var(--success) 0%, #28a745 100%); border-bottom: none;">
                                                <div class="d-flex align-items-center text-white">
                                                    <div class="rounded-circle p-2 me-2" style="background: rgba(255,255,255,0.2);">
                                                        <i class="fas fa-info-circle"></i>
                                                    </div>
                                                    <h6 class="mb-0 fw-bold">Características del Convenio Flexible</h6>
                                                </div>
                                            </div>
                                            <div class="card-body p-4">
                                                <div class="rounded p-3 mb-3" style="background: var(--bg-tertiary); border: 1px solid var(--border-primary);">
                                                    <h6 class="mb-3 fw-bold" style="color: var(--text-primary);">
                                                        <i class="fas fa-clipboard-check me-2 text-success"></i>Resumen de Características
                                                    </h6>
                                                    <div class="row g-2">
                                                        <div class="col-6">
                                                            <div class="p-2 rounded text-center" style="background: rgba(40,167,69,0.1); border: 1px solid rgba(40,167,69,0.2);">
                                                                <i class="fas fa-check-circle text-success d-block mb-1"></i>
                                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Tipo</small>
                                                                <div class="fw-bold text-success" style="font-size: 0.85rem;">Flexible</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="p-2 rounded text-center" style="background: rgba(108,117,125,0.1); border: 1px solid rgba(108,117,125,0.2);">
                                                                <i class="fas fa-times-circle text-muted d-block mb-1"></i>
                                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Cuotas</small>
                                                                <div class="fw-bold text-muted" style="font-size: 0.85rem;">No</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="p-2 rounded text-center" style="background: rgba(23,162,184,0.1); border: 1px solid rgba(23,162,184,0.2);">
                                                                <i class="fas fa-shield-alt text-info d-block mb-1"></i>
                                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Moras</small>
                                                                <div class="fw-bold text-info" style="font-size: 0.85rem;">No</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="p-2 rounded text-center" style="background: rgba(13,110,253,0.1); border: 1px solid rgba(13,110,253,0.2);">
                                                                <i class="fas fa-infinity text-primary d-block mb-1"></i>
                                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Vencimiento</small>
                                                                <div class="fw-bold text-primary" style="font-size: 0.85rem;">Sin límite</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-0 p-3" style="background: linear-gradient(135deg, rgba(255,193,7,0.1) 0%, rgba(255,193,7,0.05) 100%); border: 1px solid rgba(255,193,7,0.3); border-left: 4px solid #ffc107;">
                                                    <div class="d-flex align-items-start">
                                                        <small style="color: var(--text-primary);">
                                                            <strong class="d-block mb-1">Importante:</strong>
                                                            Este tipo de convenio requiere <strong>seguimiento manual</strong>.
                                                            Se recomienda establecer <strong>recordatorios periódicos</strong> con el cliente para mantener el control de pagos.
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Fin Contenedor tipo Flexible -->

                        @else
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle"></i> Atención</h6>
                                Debe seleccionar un préstamo válido para crear un convenio de pago.
                            </div>
                        @endif
                    </div>

                    <div class="card-footer" style="background: var(--bg-primary); border-top: 1px solid var(--border-primary);">
                        <div class="text-center">
                            @if($prestamo)
                                <button type="submit" class="btn px-4 me-3" id="btnCrearConvenio" 
                                        style="background: var(--primary); color: white; border: none;">
                                    <i class="fas fa-check me-2"></i>Crear Convenio de Pago
                                </button>
                            @endif
                            <button type="button" class="btn px-4" onclick="window.close()"
                                    style="background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--border-primary);">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para botones de plazo */
.btn-check {
    display: none !important;
}

.btn-plazo {
    transition: all 0.3s ease !important;
    border-radius: 8px !important;
}

.btn-plazo:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
    border-color: var(--primary) !important;
}

.btn-check:checked + .btn-plazo {
    background: var(--primary) !important;
    border-color: var(--primary) !important;
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important;
}

.btn-check:checked + .btn-plazo .fw-bold {
    color: white !important;
}

.btn-check:checked + .btn-plazo small {
    color: rgba(255,255,255,0.9) !important;
}

/* ===== ESTILOS MEJORADOS PARA SELECTOR DE TIPO DE CONVENIO ===== */
.tipo-convenio-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    width: 100%;
    padding: 10px;
    background: var(--bg-primary);
    border: 2px solid var(--border-primary);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    
}

.tipo-convenio-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(0,123,255,0.05) 0%, rgba(0,123,255,0) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.tipo-convenio-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    border-color: var(--primary);
}

.tipo-convenio-card:hover::before {
    opacity: 1;
}

.btn-check:checked + .tipo-convenio-card {
    border-color: var(--primary);
    border-width: 2px;
    background: linear-gradient(135deg, rgba(0,123,255,0.08) 0%, rgba(0,123,255,0.02) 100%);
    box-shadow: 0 4px 20px rgba(0, 123, 255, 0.2);
}

#tipo_flexible:checked + .tipo-convenio-card {
    border-color: var(--success);
    background: linear-gradient(135deg, rgba(40,167,69,0.08) 0%, rgba(40,167,69,0.02) 100%);
    box-shadow: 0 4px 20px rgba(40, 167, 69, 0.2);
}

.tipo-convenio-icon-wrapper {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    transition: transform 0.3s ease;
}

.tipo-convenio-icon-wrapper i {
    font-size: 1.25rem;
}

.tipo-convenio-card:hover .tipo-convenio-icon-wrapper {
    transform: scale(1.08);
}

.tipo-convenio-content {
    flex: 1;
    min-width: 0;
}

.tipo-convenio-features {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.feature-item {
    display: flex;
    align-items: center;
    padding: 0.25rem 0;
    color: var(--text-secondary);
    font-size: 0.85rem;
}

.feature-item i {
    font-size: 0.75rem;
}

/* Animación de entrada para los contenedores */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeOutDown {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(20px);
    }
}

.fade-in-up {
    animation: fadeInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.fade-out-down {
    animation: fadeOutDown 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

/* Mejora visual del badge seleccionado */
.badge-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

/* Transición suave para contenedores */
#contenedor_tipo_cuotas,
#contenedor_tipo_flexible {
    transition: opacity 0.3s ease;
}

/* Estilos responsive */
@media (max-width: 768px) {
    .tipo-convenio-card {
        padding: 1.25rem;
    }

    .tipo-convenio-icon-wrapper {
        width: 50px;
        height: 50px;
    }
}
</style>

<script>
// Variables globales
let montoCapital = {{ $montoCapitalTotal ?? 0 }};
let montoMoras = {{ $montoMorasTotal ?? 0 }};

function actualizarTotal() {
    const descuento = parseFloat(document.getElementById('descuento_moras').value) || 0;
    const total = montoCapital + (montoMoras - descuento);

    // Actualizar el campo editable con el total calculado
    document.getElementById('total_convenio_editable').value = total.toFixed(2);
    document.getElementById('total_convenio_display').textContent = 'S/. ' + total.toFixed(2);
    document.getElementById('total_convenio_hidden').value = total.toFixed(2);

    generarCronograma();
    // Aplicar moras a favor si el switch está activo
    const morasFavorInput = document.getElementById('monto_moras_favor');
    const aplicarFavor = document.getElementById('aplicarMorasFavor');
    let totalFinal = total;
    if (aplicarFavor && aplicarFavor.checked && morasFavorInput) {
        const morasFavor = parseFloat(morasFavorInput.value) || 0;
        totalFinal = Math.max(0, total - morasFavor);
        document.getElementById('total_convenio_editable').value = totalFinal.toFixed(2);
        document.getElementById('total_convenio_display').textContent = 'S/. ' + totalFinal.toFixed(2);
        document.getElementById('total_convenio_hidden').value = totalFinal.toFixed(2);
    }
    generarCronograma();
}

function actualizarTotalEditable() {
    const total = parseFloat(document.getElementById('total_convenio_editable').value) || 0;

    // Actualizar la visualización y el campo oculto
    document.getElementById('total_convenio_display').textContent = 'S/. ' + total.toFixed(2);
    document.getElementById('total_convenio_hidden').value = total.toFixed(2);

    // Regenerar cronograma con el nuevo total
    generarCronograma();
}

function toggleTotalEdit(editMode) {
    const displayView = document.getElementById('total_display_view');
    const editView = document.getElementById('total_edit_view');
    const input = document.getElementById('total_convenio_editable');

    if (editMode) {
        displayView.style.display = 'none';
        editView.style.display = 'block';
        // Enfocar el input después de un pequeño delay para que se renderice
        setTimeout(() => {
            input.focus();
            input.select();
        }, 100);
    } else {
        displayView.style.display = 'block';
        editView.style.display = 'none';
    }
}

function toggleMoraField() {
    const generaMorasCheckbox = document.getElementById('generaMoras');
    const moraFieldContainer = document.getElementById('moraFieldContainer');
    const montoMoraInput = document.getElementById('monto_mora_diaria');

    if (generaMorasCheckbox.checked) {
        moraFieldContainer.style.display = 'block';
        montoMoraInput.setAttribute('required', 'required');
    } else {
        moraFieldContainer.style.display = 'none';
        montoMoraInput.removeAttribute('required');
        montoMoraInput.value = '0.00';
    }
}

function validarCuotas(input) {
    let valor = parseInt(input.value);
    
    // Forzar que esté entre 1 y 20
    if (valor < 1 || isNaN(valor)) {
        input.value = 1;
    } else if (valor > 20) {
        input.value = 20;
    }
    
    // Regenerar cronograma después de validar
    generarCronograma();
}

function generarCronograma() {
    // Obtener el valor del campo numérico
    const cuotasInput = document.getElementById('numero_cuotas');
    const fechaInput = document.getElementById('fecha_inicio');
    const placeholder = document.getElementById('cronogramaPlaceholder');
    const container = document.getElementById('cronogramaContainer');
    const tbody = document.getElementById('cronogramaBody');
    const btnCrear = document.getElementById('btnCrearConvenio');

    const numCuotas = cuotasInput ? parseInt(cuotasInput.value) : null;
    const fechaInicio = fechaInput.value;
    const total = parseFloat(document.getElementById('total_convenio_hidden').value) || 0;
    
    // Validar datos
    if (!numCuotas || numCuotas < 1 || numCuotas > 20 || !fechaInicio || total <= 0) {
        placeholder.style.display = 'block';
        container.style.display = 'none';
        btnCrear.disabled = true;
        return;
    }
    
    // Calcular valor por cuota
    const valorCuota = (total / numCuotas).toFixed(2);
    
    // Generar filas de la tabla
    let html = '';
    // Parsear la fecha correctamente para evitar problemas de zona horaria
    const [year, month, day] = fechaInicio.split('-').map(num => parseInt(num));
    const fecha = new Date(year, month - 1, day); // month - 1 porque JavaScript cuenta meses desde 0

    for (let i = 1; i <= numCuotas; i++) {
        const fechaCuota = new Date(fecha);
        fechaCuota.setDate(fecha.getDate() + ((i - 1) * 7));

        const fechaFormat = fechaCuota.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
        
        html += `
            <tr>
                <td class="text-center py-2" style="color: var(--text-primary); border: 1px solid var(--border-primary);">${i}</td>
                <td class="py-2" style="color: var(--text-primary); border: 1px solid var(--border-primary);">${fechaFormat}</td>
                <td class="text-end py-2 fw-bold" style="color: var(--success); border: 1px solid var(--border-primary);">S/. ${valorCuota}</td>
            </tr>
        `;
    }
    
    // Actualizar tabla
    tbody.innerHTML = html;
    
    // Actualizar resumen
    document.getElementById('resumenCuotas').textContent = numCuotas;
    document.getElementById('resumenValor').textContent = 'S/. ' + valorCuota;
    
    // Mostrar cronograma
    placeholder.style.display = 'none';
    container.style.display = 'block';
    btnCrear.disabled = false;
}

/**
 * Cambiar entre tipo de convenio con animaciones mejoradas
 */
function cambiarTipoConvenio() {
    const tipoCuotas = document.getElementById('tipo_cuotas').checked;
    const contenedorCuotas = document.getElementById('contenedor_tipo_cuotas');
    const contenedorFlexible = document.getElementById('contenedor_tipo_flexible');
    const btnCrear = document.getElementById('btnCrearConvenio');

    // Badges de selección
    const badgeCuotas = document.getElementById('badge_cuotas');
    const badgeFlexible = document.getElementById('badge_flexible');

    // Labels de las tarjetas
    const labelCuotas = document.getElementById('label_tipo_cuotas');
    const labelFlexible = document.getElementById('label_tipo_flexible');

    if (tipoCuotas) {
        // Transición suave: ocultar flexible, mostrar cuotas
        if (contenedorFlexible.style.display !== 'none') {
            contenedorFlexible.classList.add('fade-out-down');
            setTimeout(() => {
                contenedorFlexible.style.display = 'none';
                contenedorFlexible.classList.remove('fade-out-down');

                contenedorCuotas.style.display = 'block';
                contenedorCuotas.classList.add('fade-in-up');
                setTimeout(() => {
                    contenedorCuotas.classList.remove('fade-in-up');
                }, 400);
            }, 300);
        } else {
            contenedorCuotas.style.display = 'block';
        }

        // Actualizar badges
        badgeCuotas.style.display = 'inline-block';
        badgeCuotas.classList.add('badge-pulse');
        badgeFlexible.style.display = 'none';
        badgeFlexible.classList.remove('badge-pulse');

        // Actualizar botón
        btnCrear.innerHTML = '<i class="fas fa-check me-2"></i>Crear Convenio de Pago';
        btnCrear.style.background = 'var(--primary)';

        generarCronograma(); // Regenerar cronograma
    } else {
        // Transición suave: ocultar cuotas, mostrar flexible
        if (contenedorCuotas.style.display !== 'none') {
            contenedorCuotas.classList.add('fade-out-down');
            setTimeout(() => {
                contenedorCuotas.style.display = 'none';
                contenedorCuotas.classList.remove('fade-out-down');

                contenedorFlexible.style.display = 'block';
                contenedorFlexible.classList.add('fade-in-up');
                setTimeout(() => {
                    contenedorFlexible.classList.remove('fade-in-up');
                }, 400);
            }, 300);
        } else {
            contenedorFlexible.style.display = 'block';
        }

        // Actualizar badges
        badgeFlexible.style.display = 'inline-block';
        badgeFlexible.classList.add('badge-pulse');
        badgeCuotas.style.display = 'none';
        badgeCuotas.classList.remove('badge-pulse');

        // Actualizar botón
        btnCrear.innerHTML = '<i class="fas fa-check me-2"></i>Crear Convenio Flexible';
        btnCrear.style.background = 'var(--success)';
        btnCrear.disabled = false; // Siempre habilitado para flexible
    }

    // Scroll suave al contenedor de formulario
    setTimeout(() => {
        const activeContainer = tipoCuotas ? contenedorCuotas : contenedorFlexible;
        activeContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 100);
}

/**
 * Actualizar total para convenio flexible
 */
function actualizarTotalFlexible() {
    const descuento = parseFloat(document.getElementById('descuento_moras_flexible').value) || 0;
    const total = montoCapital + (montoMoras - descuento);

    document.getElementById('total_convenio_flexible_display').textContent = 'S/. ' + total.toFixed(2);
    document.getElementById('total_convenio_flexible_hidden').value = total.toFixed(2);
}

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    actualizarTotal();

    // Mostrar badge del tipo seleccionado por defecto (cuotas)
    const badgeCuotas = document.getElementById('badge_cuotas');
    if (badgeCuotas) {
        badgeCuotas.style.display = 'inline-block';
        badgeCuotas.classList.add('badge-pulse');
    }

    // Confirmación antes de enviar
    document.getElementById('convenioForm').addEventListener('submit', function(e) {
        const tipoCuotas = document.getElementById('tipo_cuotas').checked;
        const total = document.getElementById(tipoCuotas ? 'total_convenio_display' : 'total_convenio_flexible_display').textContent;

        let mensaje = `¿Confirmar creación del convenio?\n\nTotal: ${total}`;

        if (tipoCuotas) {
            const cuotasInput = document.getElementById('numero_cuotas');
            const cuotas = cuotasInput ? cuotasInput.value : '';
            const fecha = document.getElementById('fecha_inicio').value;
            mensaje += `\nCuotas: ${cuotas} pagos semanales\nInicia: ${fecha}`;
        } else {
            mensaje += `\nTipo: Convenio Flexible (sin cuotas)\nEl cliente puede pagar cuando lo desee`;
        }

        if (!confirm(mensaje)) {
            e.preventDefault();
        }
    });
});
</script>

@endsection