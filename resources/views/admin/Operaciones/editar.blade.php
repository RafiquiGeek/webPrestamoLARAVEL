@extends('layouts.admin')

@section('title', 'Editar Operación #' . $operacion->id)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header text-dark">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Editar Operación #{{ $operacion->id }}
                    </h4>
                    <p class="mb-0 small">
                        Cliente: {{ $operacion->cliente->persona->nombres }} {{ $operacion->cliente->persona->ape_pat }}
                        | Préstamo: #{{ $operacion->prestamo->id }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.operaciones.actualizar', $operacion->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Campos ocultos para redirigir a convenio si corresponde -->
        @if(isset($returnTo) && $returnTo === 'convenio' && isset($convenioId))
            <input type="hidden" name="return_to" value="convenio">
            <input type="hidden" name="convenio_id" value="{{ $convenioId }}">
        @endif

        <div class="row">
            <!-- Información General -->
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header text-black">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2 mr-2"></i>Información General
                            <span class="info-tooltip ms-2" data-bs-toggle="tooltip" data-bs-placement="right"
                                  title="Modifique los datos de la operación. El monto total se calcula automáticamente según los conceptos asignados abajo.">
                                <i class="fas fa-question-circle text-primary"></i>
                            </span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Monto Original -->
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Monto Original</label>
                                <div class="input-group">
                                    <span class="input-group-text">S/.</span>
                                    <input type="text" class="form-control" value="{{ number_format($operacion->abono, 2) }}" readonly>
                                </div>
                                <small class="text-muted">Monto registrado originalmente</small>
                            </div>

                            <!-- Nuevo Monto (Calculado Automáticamente) -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label form-label-with-tooltip">
                                    Nuevo Monto Total (Calculado) <span class="text-danger">*</span>
                                    <span class="info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top"
                                          title="<b>Cálculo Automático</b><br>Este monto se actualiza automáticamente al modificar las cuotas o moras abajo. Refleja el monto real que se registrará en esta operación.">
                                        <i class="fas fa-question-circle text-info"></i>
                                    </span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">S/.</span>
                                    <input type="number" name="nuevo_abono" id="nuevo_abono"
                                        class="form-control @error('nuevo_abono') is-invalid @enderror"
                                        value="{{ old('nuevo_abono', $operacion->abono) }}"
                                        step="0.01" min="0" required readonly>
                                </div>
                                <small class="text-info">
                                    <i class="fas fa-calculator"></i>
                                    Se calcula sumando cuotas + moras + abonos
                                </small>
                                @error('nuevo_abono')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Fecha -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label form-label-with-tooltip">
                                    Fecha de Operación <span class="text-danger">*</span>
                                    <span class="info-tooltip" data-bs-toggle="tooltip" data-bs-placement="top"
                                          title="<b>Importante</b><br>Al cambiar la fecha, las moras se recalculan automáticamente según los días transcurridos hasta la nueva fecha. Las moras regularizadas se eliminan si quedan fuera del período.">
                                        <i class="fas fa-question-circle text-warning"></i>
                                    </span>
                                </label>
                                <input type="date" name="fecha"
                                    class="form-control @error('fecha') is-invalid @enderror has-tooltip-help"
                                    value="{{ old('fecha', $operacion->fecha ? $operacion->fecha->format('Y-m-d') : '') }}" required>
                                @error('fecha')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Método de Pago -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Método de Pago</label>
                                <select name="metodo_pago_id" class="form-control">
                                    @foreach($metodosPago as $metodo)
                                        <option value="{{ $metodo->id }}" 
                                                {{ $metodo->id == $operacion->metodo_pago_id ? 'selected' : '' }}>
                                            {{ $metodo->metodo_pago }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Código/Número -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Código/Nro Operación</label>
                                <input type="text" name="nro_operacion" 
                                    class="form-control" 
                                    value="{{ old('nro_operacion', $operacion->codigo) }}">
                            </div>
                        </div>

                        <div class="row">
                            
                            
                            <!-- Puedes agregar otro campo de 4 columnas aquí si necesitas -->
                            <div class="col-md-4 mb-3">
                                <!-- Espacio para otro campo si es necesario -->
                            </div>
                        </div>

                        <!-- Modo de Edición (oculto, siempre manual) -->
                        <input type="hidden" name="modo_edicion" value="manual">

                        <!-- Banner informativo sobre edición -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="alert alert-info alert-dismissible fade show" role="alert" style="border-left: 4px solid #3498db;">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-lightbulb me-3 mt-1" style="font-size: 24px;"></i>
                                        <div>
                                            <h6 class="alert-heading mb-2"><i class="fas fa-info-circle"></i> Guía de Edición</h6>
                                            <ul class="mb-0 small" style="line-height: 1.8;">
                                                <li><strong>Edite los montos</strong> de cuotas y moras abajo según necesite</li>
                                                <li><strong>El monto total</strong> se actualiza automáticamente al sumar todos los conceptos</li>
                                                <li><strong>Al cambiar la fecha</strong>, las moras se recalculan automáticamente</li>
                                                <li><strong>Si reduce el monto pagado</strong>, el sistema ajusta la deuda pendiente</li>
                                                <li><strong>Las ediciones anteriores</strong> se anulan automáticamente al guardar</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            </div>
                        </div>

                        <!-- Justificación -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Justificación de Edición <span class="text-danger">*</span></label>
                                <textarea name="justificacion_edicion" 
                                        class="form-control @error('justificacion_edicion') is-invalid @enderror" 
                                        rows="3" required placeholder="Explique el motivo de la edición...">{{ old('justificacion_edicion') }}</textarea>
                                @error('justificacion_edicion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalles de Afectación - DISEÑO MEJORADO -->
            <div class="col-lg-6">
                <!-- Información compacta de estado -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="alert-light border-0 py-2">
                            <div class="row text-center">
                                <div class="col-3">
                                    <small class="text-muted d-block">Cuotas</small>
                                    <strong class="text-success">{{ $cuotasAfectadas ? $cuotasAfectadas->count() : 0 }}</strong>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted d-block">Moras</small>
                                    <strong class="text-warning">{{ $morasAfectadas ? $morasAfectadas->count() : 0 }}</strong>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted d-block">Abonos</small>
                                    <strong class="text-info">{{ $abonosCuotaAfectados ? $abonosCuotaAfectados->count() : 0 }}</strong>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted d-block">Saldo Favor</small>
                                    <strong class="text-purple">S/. {{ number_format($saldoFavorDisponible ?? 0, 2) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- DISEÑO NUEVO: CUOTAS AGRUPADAS CON SUS MORAS -->
                @if(isset($cuotasConMoras) && $cuotasConMoras && $cuotasConMoras->count() > 0)
                    @foreach($cuotasConMoras as $cuota)
                    <div class="col-12 mb-3">
                        <div class="card shadow-sm border-success" data-cuota-id="{{ $cuota->id }}">
                            <!-- Header de Cuota -->
                            <div class="card-header text-black py-2">
                                <div class="row align-items-center">
                                    <div class="col-6">
                                        <h6 class="mb-0">
                                            <i class="fas fa-calendar-check me-2"></i>
                                            Cuota #{{ $cuota->numero ?? 'N/A' }}
                                            @if(isset($cuota->es_disponible) && $cuota->es_disponible)
                                                <small class="badge bg-light text-dark ms-2">Disponible</small>
                                            @endif
                                            <span class="info-tooltip ms-1" data-bs-toggle="tooltip" data-bs-placement="right"
                                                  title="<b>Editar Cuota</b><br>Ingrese el monto que desea aplicar a esta cuota. El monto se resta de la deuda de la cuota y se suma al total de la operación.">
                                                <i class="fas fa-question-circle text-success" style="font-size: 12px;"></i>
                                            </span>
                                        </h6>
                                        <small class="text-dark">
                                            Vence: {{ $cuota->fecha_pago ? \Carbon\Carbon::parse($cuota->fecha_pago)->format('d/m/Y') : 'N/A' }}
                                        </small>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-black">S/.</span>
                                            <input type="number" 
                                                   name="cuotas[{{ $cuota->id }}]" 
                                                   class="form-control concepto-input fw-bold" 
                                                   value="{{ $cuota->monto_aplicado ?? 0 }}" 
                                                   step="0.01" min="0"
                                                   data-concepto="cuota"
                                                   data-id="{{ $cuota->id }}"
                                                   placeholder="Monto cuota">
                                        </div>
                                        @if(isset($cuota->es_disponible) && $cuota->es_disponible)
                                            <small class="text-dark">Máx: S/. {{ number_format($cuota->monto - ($cuota->monto_pagado ?? 0), 2) }}</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Moras de esta cuota -->
                            @if($cuota->moras && $cuota->moras->count() > 0)
                            <div class="card-body p-2 bg-light">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                    <small class="text-muted fw-bold">
                                        Moras de Cuota #{{ $cuota->numero }} ({{ $cuota->moras->count() }})
                                        <span class="info-tooltip ms-1" data-bs-toggle="tooltip" data-bs-placement="right"
                                              title="<b>Moras Automáticas</b><br>Las moras se calculan automáticamente según los días de atraso. Al cambiar la fecha de la operación, las moras se recalculan. Máximo 7 moras por cuota.">
                                            <i class="fas fa-question-circle text-warning" style="font-size: 11px;"></i>
                                        </span>
                                    </small>
                                </div>
                                <div class="row g-2 moras-container">
                                    @foreach($cuota->moras as $mora)
                                    <div class="col-6">
                                        <div class="d-flex align-items-center mygit-1">
                                            <div class="me-2" style="min-width: 80px;">
                                                <small class="text-muted d-block">{{ $mora->fecha ? \Carbon\Carbon::parse($mora->fecha)->format('d/m') : 'N/A' }}</small>
                                                <!--@if(isset($mora->es_disponible) && $mora->es_disponible)
                                                    <small class="badge bg-warning">{{ $mora->estado == 0 ? 'Pend.' : 'Parc.' }}</small>
                                                @endif-->
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">S/.</span>
                                                    <input type="number" 
                                                           name="moras[{{ $mora->id }}]" 
                                                           class="form-control concepto-input {{ isset($mora->es_disponible) && $mora->es_disponible ? 'border-warning' : '' }}" 
                                                           value="{{ $mora->monto_aplicado ?? 0 }}" 
                                                           step="0.01" min="0"
                                                           max="{{ $mora->monto - ($mora->monto_pagado ?? 0) }}"
                                                           data-concepto="mora"
                                                           data-id="{{ $mora->id }}"
                                                           placeholder="@if(isset($mora->es_disponible) && $mora->es_disponible){{ number_format($mora->monto - ($mora->monto_pagado ?? 0), 2) }}@endif">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @else
                            <!-- Placeholder para moras cuando no hay ninguna inicialmente -->
                            <div class="card-body p-2 bg-light d-none" id="moras-placeholder-{{ $cuota->id }}">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                    <small class="text-muted fw-bold">Moras de Cuota #{{ $cuota->numero }} (<span class="moras-count">0</span>)</small>
                                </div>
                                <div class="row g-2 moras-container">
                                    <!-- Las moras se agregan dinámicamente aquí -->
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                @endif

                <!-- Abonos a Cuota - DISEÑO COMPACTO -->
                @if(isset($abonosCuotaAfectados) && $abonosCuotaAfectados && $abonosCuotaAfectados->count() > 0)
                <div class="col-12 mb-3">
                    <div class="card border-info">
                        <div class="card-header bg-info text-dark py-2">
                            <h6 class="mb-0"><i class="fas fa-coins me-2"></i>Abonos a Cuota ({{ $abonosCuotaAfectados->count() }})</h6>
                        </div>
                        <div class="card-body p-2">
                            <div class="row g-2">
                                @foreach($abonosCuotaAfectados as $abono)
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <small class="text-muted me-2" style="min-width: 60px;">Cuota #{{ $abono->cuota_numero ?? 'N/A' }}</small>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">S/.</span>
                                            <input type="number" 
                                                   name="abonos_cuota[{{ $abono->id }}]" 
                                                   class="form-control concepto-input" 
                                                   value="{{ $abono->monto_aplicado ?? 0 }}" 
                                                   step="0.01" min="0"
                                                   data-concepto="abono_cuota"
                                                   data-id="{{ $abono->id }}">
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Abonos a Favor - DISEÑO COMPACTO -->
                <div class="col-12 mb-3">
                    <div class="card border-purple">
                        <div class="card-header text-dark  py-2">
                            <div class="row align-items-center">
                                <div class="col-8">
                                    <h6 class="mb-0">
                                        <i class="fas fa-gift me-2"></i>
                                        Abono a Favor
                                        @if(isset($abonosFavorAfectados) && $abonosFavorAfectados && $abonosFavorAfectados->count() > 0)
                                            ({{ $abonosFavorAfectados->count() }})
                                        @endif
                                    </h6>
                                    <small class="text-dark">Disponible: S/. {{ number_format($saldoFavorDisponible ?? 0, 2) }}</small>
                                </div>
                                <div class="col-4">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white">S/.</span>
                                        <input type="number" 
                                               name="nuevo_abono_favor" 
                                               id="nuevo_abono_favor"
                                               class="form-control concepto-input fw-bold" 
                                               value="0.00" 
                                               step="0.01" 
                                               min="0"
                                               max="{{ $saldoFavorDisponible ?? 0 }}"
                                               data-concepto="nuevo_abono_favor"
                                               data-id="nuevo"
                                               placeholder="0.00">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        @if(isset($abonosFavorAfectados) && $abonosFavorAfectados && $abonosFavorAfectados->count() > 0)
                        <div class="card-body p-2 bg-light">
                            <small class="text-muted fw-bold d-block mb-2">
                                <i class="fas fa-history me-1"></i>Abonos Favor Existentes
                            </small>
                            <div class="row g-2">
                                @foreach($abonosFavorAfectados as $favor)
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <small class="text-muted me-2" style="min-width: 60px;">Cuota #{{ $favor->cuota_numero ?? 'N/A' }}</small>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">S/.</span>
                                            <input type="number" 
                                                   name="abonos_favor[{{ $favor->id }}]" 
                                                   class="form-control concepto-input" 
                                                   value="{{ $favor->monto_aplicado ?? 0 }}" 
                                                   step="0.01" min="0"
                                                   data-concepto="abono_favor"
                                                   data-id="{{ $favor->id }}">
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        <div class="card-footer p-2 text-center">
                            <button type="button" 
                                    class="btn btn-outline-purple btn-sm" 
                                    onclick="aplicarSaldoCompleto()"
                                    {{ ($saldoFavorDisponible ?? 0) <= 0 ? 'disabled' : '' }}>
                                <i class="fas fa-magic me-1"></i>
                                Aplicar Todo (S/. {{ number_format($saldoFavorDisponible ?? 0, 2) }})
                            </button>
                        </div>
                    </div>
                </div>

                    <!-- Mensaje si no hay conceptos principales (ya no incluye abonos favor porque ahora siempre se muestra) -->
                    @if((!isset($cuotasAfectadas) || !$cuotasAfectadas || $cuotasAfectadas->count() == 0) &&
                        (!isset($morasAfectadas) || !$morasAfectadas || $morasAfectadas->count() == 0) &&
                        (!isset($abonosCuotaAfectados) || !$abonosCuotaAfectados || $abonosCuotaAfectados->count() == 0))
                    <div class="col-12 mb-3">
                        @if($operacion->tipo_operacion === 'PAGO_CONVENIO')
                        <!-- Caso especial: Operaciones de Convenio -->
                        <div class="card shadow-sm border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-handshake me-2"></i>Operación de Convenio</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Esta operación corresponde a un <strong>Pago de Convenio</strong>.
                                    <br>
                                    <small>{{ $operacion->comentario }}</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Monto del Pago</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-primary text-white">S/.</span>
                                            <input type="number"
                                                   name="monto_convenio"
                                                   id="monto_convenio"
                                                   class="form-control concepto-input fw-bold"
                                                   value="{{ old('monto_convenio', $operacion->abono) }}"
                                                   step="0.01"
                                                   min="0"
                                                   data-concepto="monto_convenio"
                                                   data-id="convenio"
                                                   placeholder="Ingrese el nuevo monto">
                                        </div>
                                        <small class="text-muted">Monto original: S/. {{ number_format($operacion->abono, 2) }}</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Información</label>
                                        <div class="border rounded p-3 bg-light">
                                            <div class="mb-2">
                                                <small class="text-muted">Tipo:</small>
                                                <span class="badge bg-primary ms-2">{{ $operacion->tipo_operacion }}</span>
                                            </div>
                                            <div>
                                                <small class="text-muted">Método:</small>
                                                <strong>{{ $operacion->metodoDePago->metodo_pago ?? 'N/A' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <!-- Caso general: Sin conceptos específicos -->
                        <div class="card shadow-sm">
                            <div class="card-header text-dark">
                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Sin Conceptos Específicos</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-0">Esta operación no tiene conceptos específicos de pago de cuotas o moras. Podría ser un desembolso o pago general.</p>
                                <p class="text-muted small mb-0">Monto total de la operación: <strong>S/. {{ number_format($operacion->abono, 2) }}</strong></p>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Resumen de Cambios -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header text-black">
                        <h5 class="mb-0"><i class="fas fa-calculator me-2 mr-2"></i>Resumen de Cambios</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted">Total Original</h6>
                                        <h4 class="text-dark">S/. <span id="total-original">{{ number_format($operacion->abono, 2) }}</span></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted">Nuevo Total</h6>
                                        <h4 class="text-primary">S/. <span id="nuevo-total">{{ number_format($operacion->abono, 2) }}</span></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted">Diferencia</h6>
                                        <h4 id="diferencia" class="text-success">S/. 0.00</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted">Estado</h6>
                                        <h5 id="estado-diferencia" class="badge bg-success">Sin cambios</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.prestamos.show', $operacion->prestamo_id) }}" 
                       class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Préstamo
                    </a>
                    
                    <div>
                        <button type="button" class="btn btn-outline-warning me-2" id="btn-previa">
                            <i class="fas fa-eye me-2"></i>Vista Previa
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('css')
<style>
.bg-purple {
    background-color: #6f42c1 !important;
}
.text-purple {
    color: #6f42c1 !important;
}
.btn-outline-purple {
    color: #6f42c1;
    border-color: #6f42c1;
}
.btn-outline-purple:hover {
    color: #fff;
    background-color: #6f42c1;
    border-color: #6f42c1;
}
.border-purple {
    border-color: #6f42c1 !important;
}
.border-success {
    border-color: #198754 !important;
}
.border-info {
    border-color: #0dcaf0 !important;
}
.card-header.py-2 {
    padding-top: 0.5rem !important;
    padding-bottom: 0.5rem !important;
}
.input-group-sm .form-control {
    font-size: 0.875rem;
}
.concepto-input{
    height:50px!important;
}
.concepto-input:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 202, 240, 0.25);
}
.concepto-input.border-warning:focus {
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
}
.concepto-input.border-info:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 202, 240, 0.25);
}

/* Ocultar radio buttons */
.btn-check {
    display: none !important;
}

/* Estilo activo para los labels */
.btn-outline-primary:has(.btn-check:checked),
.btn-outline-primary.active,
input[type="radio"]:checked + .btn-outline-primary {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
    color: #fff !important;
    box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
    transform: translateY(-1px);
}

.btn-outline-success:has(.btn-check:checked),
.btn-outline-success.active,
input[type="radio"]:checked + .btn-outline-success {
    background-color: #198754 !important;
    border-color: #198754 !important;
    color: #fff !important;
    box-shadow: 0 4px 8px rgba(25, 135, 84, 0.3);
    transform: translateY(-1px);
}

/* Efectos hover mejorados */
.btn-outline-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
}

.btn-outline-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(25, 135, 84, 0.2);
}

/* Transiciones suaves */
.btn-outline-primary,
.btn-outline-success {
    transition: all 0.3s ease;
}
</style>
@endsection

@section('js')
<script>
// Variable global para el saldo a favor disponible
const saldoFavorDisponible = {{ $saldoFavorDisponible ?? 0 }};

// Función para aplicar todo el saldo disponible
function aplicarSaldoCompleto() {
    const nuevoAbonoFavorInput = document.getElementById('nuevo_abono_favor');
    if (nuevoAbonoFavorInput && saldoFavorDisponible > 0) {
        nuevoAbonoFavorInput.value = saldoFavorDisponible.toFixed(2);
        // Disparar evento para recalcular totales
        nuevoAbonoFavorInput.dispatchEvent(new Event('input'));
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const nuevoAbonoInput = document.getElementById('nuevo_abono');
    let conceptoInputs = document.querySelectorAll('.concepto-input');
    const fechaInput = document.querySelector('input[name="fecha"]');
    
    const totalOriginalSpan = document.getElementById('total-original');
    const nuevoTotalSpan = document.getElementById('nuevo-total');
    const diferenciaSpan = document.getElementById('diferencia');
    const estadoDiferenciaSpan = document.getElementById('estado-diferencia');
    
    const totalOriginal = {{ $operacion->abono }};
    const operacionId = {{ $operacion->id }};
    
    console.log('🔍 Inicializando edición de operación:', {
        operacionId,
        fechaInput: fechaInput ? fechaInput.value : 'NO ENCONTRADO',
        conceptoInputs: conceptoInputs.length
    });
    
    // Obtener IDs de cuotas afectadas
    function obtenerCuotasIds() {
        const cuotasIds = [];
        @if(isset($cuotasConMoras))
            @foreach($cuotasConMoras as $cuota)
                cuotasIds.push({{ $cuota->id }});
            @endforeach
        @endif
        return cuotasIds;
    }
    
    function calcularTotalConceptos() {
        let total = 0;
        conceptoInputs.forEach(input => {
            const valor = parseFloat(input.value) || 0;
            total += valor;
        });
        return total;
    }
    
    // Función para verificar si una cuota debe mostrar moras (está pagada o tiene monto > 0)
    function cuotaDeberaMostrarMoras(cuotaId) {
        const cuotaInput = document.querySelector(`input[name="cuotas[${cuotaId}]"]`);
        if (!cuotaInput) {
            console.log(`❌ No se encontró input para cuota ${cuotaId}`);
            return false;
        }
        
        const montoCuota = parseFloat(cuotaInput.value) || 0;
        
        console.log(`🔍 Verificando cuota ${cuotaId}:`, {
            montoCuota,
            inputName: cuotaInput.name,
            valor: cuotaInput.value
        });
        
        // Si tiene algún monto pagado, debe mostrar moras
        return montoCuota > 0;
    }
    
    // Función para obtener cuotas que deben mostrar moras
    function obtenerCuotasConMoras() {
        const cuotasIds = obtenerCuotasIds();
        const cuotasConMoras = [];
        
        cuotasIds.forEach(cuotaId => {
            if (cuotaDeberaMostrarMoras(cuotaId)) {
                cuotasConMoras.push(cuotaId);
            }
        });
        
        console.log('📊 Cuotas que deben mostrar moras:', cuotasConMoras);
        return cuotasConMoras;
    }
    
    // Función para recalcular moras según fecha y estado de pago de cuotas
    async function recalcularMoras() {
        console.log('🔄 INICIANDO recálculo de moras...');
        
        const fechaPago = fechaInput ? fechaInput.value : null;
        const cuotasConMoras = obtenerCuotasConMoras();
        
        console.log('📊 Estado actual:', {
            fechaPago,
            cuotasConMoras,
            totalCuotas: cuotasConMoras.length
        });
        
        if (!fechaPago) {
            console.log('❌ No hay fecha de pago');
            ocultarTodasLasMoras();
            return;
        }
        
        if (cuotasConMoras.length === 0) {
            console.log('❌ No hay cuotas con moras');
            // Solo ocultar si realmente no hay cuotas pagadas
            ocultarTodasLasMoras();
            return;
        }
        
        try {
            console.log('🚀 Enviando petición AJAX...');
            
            const requestData = {
                fecha_pago: fechaPago,
                cuotas_ids: cuotasConMoras
            };
            
            console.log('📤 Datos enviados:', requestData);
            
            const response = await fetch(`/admin/admin/operaciones/${operacionId}/calcular-moras-edicion`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(requestData)
            });
            
            const data = await response.json();
            console.log('📥 Respuesta recibida:', data);
            
            if (data.success) {
                console.log('✅ Moras calculadas exitosamente');
                actualizarMorasEnVista(data.moras_calculadas);
                mostrarInfoMoras(data);
            } else {
                console.error('❌ Error en cálculo de moras:', data);
            }
        } catch (error) {
            console.error('💥 Error en petición AJAX:', error);
        }
    }
    
    // Función para ocultar todas las moras cuando no corresponden
    function ocultarTodasLasMoras() {
        console.log('🙈 Ocultando todas las moras...');
        const cuotasIds = obtenerCuotasIds();
        cuotasIds.forEach(cuotaId => {
            const cuotaCard = document.querySelector(`[data-cuota-id="${cuotaId}"]`);
            if (cuotaCard) {
                // Ocultar sección de moras existente
                const morasSection = cuotaCard.querySelector('.card-body.bg-light');
                if (morasSection && !morasSection.classList.contains('d-none')) {
                    console.log(`🙈 Ocultando moras de cuota ${cuotaId}`);
                    morasSection.classList.add('d-none');
                }
                
                // Ocultar placeholder
                const placeholder = document.getElementById(`moras-placeholder-${cuotaId}`);
                if (placeholder && !placeholder.classList.contains('d-none')) {
                    console.log(`🙈 Ocultando placeholder de cuota ${cuotaId}`);
                    placeholder.classList.add('d-none');
                }
            }
        });
    }
    
    // Función para actualizar las moras en la vista
    function actualizarMorasEnVista(morasCalculadas) {
        console.log('🔄 Actualizando moras en vista:', morasCalculadas);
        
        // NO ocultar automáticamente - solo actualizar las que corresponden
        // ocultarTodasLasMoras();
        
        // Mostrar las moras calculadas
        Object.keys(morasCalculadas).forEach(cuotaId => {
            const dataCuota = morasCalculadas[cuotaId];
            const cuotaCard = document.querySelector(`[data-cuota-id="${cuotaId}"]`);
            
            if (cuotaCard && dataCuota.total_moras > 0) {
                let morasContainer = cuotaCard.querySelector('.moras-container');
                let morasSection = null;
                
                // Buscar sección de moras existente primero
                morasSection = cuotaCard.querySelector('.card-body.bg-light');
                
                // Si no existe, buscar el placeholder
                if (!morasSection || morasSection.classList.contains('d-none')) {
                    const placeholder = document.getElementById(`moras-placeholder-${cuotaId}`);
                    if (placeholder) {
                        placeholder.classList.remove('d-none');
                        morasContainer = placeholder.querySelector('.moras-container');
                        morasSection = placeholder;
                    }
                } else {
                    // Si existe, asegurar que esté visible
                    morasSection.classList.remove('d-none');
                    morasContainer = morasSection.querySelector('.moras-container');
                }
                
                if (morasContainer) {
                    actualizarMorasCuota(morasContainer, dataCuota.moras, cuotaId);
                    
                    // Actualizar contador de moras
                    const contadorMoras = morasSection.querySelector('.moras-count, small');
                    if (contadorMoras) {
                        if (contadorMoras.classList.contains('moras-count')) {
                            contadorMoras.textContent = dataCuota.total_moras;
                        } else {
                            // Actualizar el texto completo
                            contadorMoras.innerHTML = `Moras de Cuota #${dataCuota.cuota_numero} (${dataCuota.total_moras})`;
                        }
                    }
                }
            }
        });
        
        // Recalcular totales después de actualizar moras
        setTimeout(() => {
            actualizarConceptoInputs();
            actualizarCalculos();
        }, 100);
    }
    
    // Función para actualizar moras de una cuota específica
    function actualizarMorasCuota(container, moras, cuotaId) {
        // Crear nuevo HTML para las moras
        const morasHtml = moras.map(mora => `
            <div class="col-6">
                <div class="d-flex align-items-center">
                    <div class="me-2" style="min-width: 80px;">
                        <small class="text-muted d-block">${mora.fecha_display}</small>
                        <small class="badge bg-warning">${mora.estado_text}</small>
                    </div>
                    <div class="flex-grow-1">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">S/.</span>
                            <input type="number" 
                                   name="moras[${mora.id}]" 
                                   class="form-control concepto-input border-warning" 
                                   value="${mora.monto_aplicado}" 
                                   step="0.01" min="0"
                                   max="${mora.saldo_pendiente}"
                                   data-concepto="mora"
                                   data-id="${mora.id}"
                                   placeholder="${mora.saldo_pendiente.toFixed(2)}">
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
        
        // Reemplazar contenido
        container.innerHTML = morasHtml;
    }
    
    // Función para mostrar información de debug
    function mostrarInfoMoras(data) {
        const infoDiv = document.querySelector('.alert-light');
        if (infoDiv) {
            const morasCountElement = infoDiv.querySelector('.text-warning strong');
            if (morasCountElement) {
                morasCountElement.textContent = data.total_dias_mora || 0;
            }
        }
    }
    
    // Función para actualizar la lista de conceptoInputs
    function actualizarConceptoInputs() {
        const nuevosInputs = document.querySelectorAll('.concepto-input');
        // Quitar listeners anteriores y agregar nuevos
        nuevosInputs.forEach(input => {
            input.removeEventListener('input', actualizarCalculos);
            input.addEventListener('input', actualizarCalculos);
        });
    }
    
    function actualizarCalculos() {
        const totalConceptos = calcularTotalConceptos();
        const diferencia = totalConceptos - totalOriginal;
        
        // Actualizar el monto total calculado automáticamente
        nuevoAbonoInput.value = totalConceptos.toFixed(2);
        
        // Actualizar UI del resumen
        nuevoTotalSpan.textContent = totalConceptos.toFixed(2);
        diferenciaSpan.textContent = (diferencia >= 0 ? '+' : '') + diferencia.toFixed(2);
        
        // Actualizar estado y colores
        if (Math.abs(diferencia) < 0.01) {
            estadoDiferenciaSpan.textContent = 'Sin cambios';
            estadoDiferenciaSpan.className = 'badge bg-success';
            diferenciaSpan.className = 'text-success';
        } else if (diferencia > 0) {
            estadoDiferenciaSpan.textContent = 'Incremento';
            estadoDiferenciaSpan.className = 'badge bg-info';
            diferenciaSpan.className = 'text-info';
        } else {
            estadoDiferenciaSpan.textContent = 'Reducción';
            estadoDiferenciaSpan.className = 'badge bg-warning';
            diferenciaSpan.className = 'text-warning';
        }
        
        // Habilitar botón de envío
        const submitBtn = document.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Guardar Cambios';
        }
    }
    
    // Agregar eventos a todos los inputs de conceptos
    conceptoInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Asegurar que no sea menor que 0
            if (parseFloat(this.value) < 0) {
                this.value = '0.00';
            }
            
            // Si es input de cuota, verificar si debe recalcular moras
            if (this.getAttribute('data-concepto') === 'cuota') {
                console.log('💰 CUOTA MODIFICADA (input) - recalculando moras');
                setTimeout(recalcularMoras, 100); // Pequeño delay para que se actualice el valor
            }
            
            // Actualizar cálculos cada vez que cambie un concepto
            actualizarCalculos();
        });
        
        // También al perder el foco para formatear
        input.addEventListener('blur', function() {
            const valor = parseFloat(this.value) || 0;
            this.value = valor.toFixed(2);
            
            // Si es input de cuota, verificar si debe recalcular moras
            if (this.getAttribute('data-concepto') === 'cuota') {
                console.log('💰 CUOTA MODIFICADA (blur) - recalculando moras');
                setTimeout(recalcularMoras, 100); // Pequeño delay para que se actualice el valor
            }
            
            actualizarCalculos();
        });
    });
    
    // Escuchar cambios en la fecha para recalcular moras
    if (fechaInput) {
        console.log('✅ Agregando listener a fecha input');
        
        fechaInput.addEventListener('change', function() {
            console.log('📅 FECHA CAMBIADA a:', this.value);
            recalcularMoras();
        });
        
        // También escuchar input para cambios inmediatos
        fechaInput.addEventListener('input', function() {
            console.log('📅 FECHA INPUT a:', this.value);
            recalcularMoras();
        });
    } else {
        console.error('❌ No se encontró el input de fecha');
    }
    
    // Vista previa
    const btnPrevia = document.getElementById('btn-previa');
    if (btnPrevia) {
        btnPrevia.addEventListener('click', function() {
            const totalConceptos = calcularTotalConceptos();
            const diferencia = totalConceptos - totalOriginal;
            
            let resumen = `
                <h5>Vista Previa de Cambios</h5>
                <table class="table table-sm">
                    <tr><td><strong>Total Original:</strong></td><td>S/. ${totalOriginal.toFixed(2)}</td></tr>
                    <tr><td><strong>Nuevo Total:</strong></td><td>S/. ${totalConceptos.toFixed(2)}</td></tr>
                    <tr><td><strong>Diferencia:</strong></td><td class="${diferencia >= 0 ? 'text-info' : 'text-warning'}">S/. ${(diferencia >= 0 ? '+' : '') + diferencia.toFixed(2)}</td></tr>
                </table>
                <h6>Desglose por Conceptos:</h6>
                <ul class="list-group list-group-flush">
            `;
            
            conceptoInputs.forEach(input => {
                const concepto = input.dataset.concepto;
                const id = input.dataset.id;
                const valor = parseFloat(input.value) || 0;
                
                let icono = '';
                let tipo = '';
                
                switch(concepto) {
                    case 'cuota':
                        icono = 'fas fa-calendar-check';
                        tipo = 'Cuota';
                        break;
                    case 'mora':
                        icono = 'fas fa-exclamation-triangle';
                        tipo = 'Mora';
                        break;
                    case 'abono_cuota':
                        icono = 'fas fa-coins';
                        tipo = 'Abono a Cuota';
                        break;
                    case 'abono_favor':
                        icono = 'fas fa-gift';
                        tipo = 'Abono a Favor Existente';
                        break;
                    case 'nuevo_abono_favor':
                        icono = 'fas fa-plus-circle';
                        tipo = 'Nuevo Abono a Favor';
                        break;
                }
                
                if (valor > 0) {
                    resumen += `<li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="${icono} me-2"></i>${tipo} #${id}</span>
                        <span class="badge bg-primary">S/. ${valor.toFixed(2)}</span>
                    </li>`;
                }
            });
            
            resumen += '</ul>';
            
            // Mostrar en un modal o alert
            Swal.fire({
                title: 'Vista Previa',
                html: resumen,
                icon: 'info',
                confirmButtonText: 'Entendido',
                width: '600px'
            });
        });
    }
    
    // SISTEMA HÍBRIDO: Manejo de modos de edición
    const modoManualRadio = document.getElementById('modo_manual');
    const modoAutomaticoRadio = document.getElementById('modo_automatico');
    const descripcionManual = document.getElementById('descripcion_modo_manual');
    const descripcionAutomatico = document.getElementById('descripcion_modo_automatico');
    const cuotasInputs = document.querySelectorAll('.concepto-input[data-concepto="cuota"]');
    
    function cambiarModoEdicion(modo) {
        console.log('🔄 Cambiando a modo:', modo);
        
        if (modo === 'manual') {
            // MODO MANUAL: Habilitar edición individual
            descripcionManual.classList.remove('d-none');
            descripcionAutomatico.classList.add('d-none');
            
            // Habilitar todos los inputs de cuotas
            cuotasInputs.forEach(input => {
                input.removeAttribute('readonly');
                input.placeholder = 'Editar individualmente';
                input.classList.remove('bg-light');
            });
            
            // Mantener cálculo automático del total
            actualizarCalculos();
            
        } else {
            // MODO AUTOMÁTICO: Solo monto total
            descripcionManual.classList.add('d-none');
            descripcionAutomatico.classList.remove('d-none');
            
            // Deshabilitar edición individual (readonly)
            cuotasInputs.forEach(input => {
                input.setAttribute('readonly', 'readonly');
                input.placeholder = 'Se calculará automáticamente';
                input.classList.add('bg-light');
            });
            
            // En modo automático, el sistema redistribuirá
            Swal.fire({
                title: 'Modo Automático',
                text: 'El sistema redistribuirá automáticamente según las reglas de pagos secuenciales. ¿Continuar?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (!result.isConfirmed) {
                    // Volver a modo manual
                    modoManualRadio.checked = true;
                    cambiarModoEdicion('manual');
                }
            });
        }
    }
    
    // Función para actualizar estilos activos
    function actualizarEstilosActivos() {
        const labelManual = document.querySelector('label[for="modo_manual"]');
        const labelAutomatico = document.querySelector('label[for="modo_automatico"]');
        
        // Remover clases activas de ambos
        labelManual.classList.remove('active');
        labelAutomatico.classList.remove('active');
        
        // Agregar clase activa al seleccionado
        if (modoManualRadio.checked) {
            labelManual.classList.add('active');
        } else if (modoAutomaticoRadio.checked) {
            labelAutomatico.classList.add('active');
        }
    }

    // Event listeners para cambio de modo
    modoManualRadio.addEventListener('change', function() {
        if (this.checked) {
            cambiarModoEdicion('manual');
            actualizarEstilosActivos();
        }
    });
    
    modoAutomaticoRadio.addEventListener('change', function() {
        if (this.checked) {
            cambiarModoEdicion('automatico');
            actualizarEstilosActivos();
        }
    });
    
    // Event listeners para los labels (para funcionalidad de click)
    document.querySelector('label[for="modo_manual"]').addEventListener('click', function(e) {
        e.preventDefault();
        modoManualRadio.checked = true;
        cambiarModoEdicion('manual');
        actualizarEstilosActivos();
    });
    
    document.querySelector('label[for="modo_automatico"]').addEventListener('click', function(e) {
        e.preventDefault();
        modoAutomaticoRadio.checked = true;
        cambiarModoEdicion('automatico');
        actualizarEstilosActivos();
    });
    
    // Inicializar modo por defecto
    cambiarModoEdicion('manual');
    actualizarEstilosActivos();
    
    // Inicializar cálculos al cargar la página
    actualizarCalculos();
    
    // NO recalcular moras automáticamente al cargar - respetar moras existentes del servidor
    setTimeout(() => {
        console.log('🔍 Verificación inicial:', {
            fecha: fechaInput ? fechaInput.value : 'NO_FECHA',
            cuotasConMoras: obtenerCuotasConMoras(),
            morasExistentes: document.querySelectorAll('.moras-container').length
        });
        
        // Solo recalcular si no hay moras visibles ya cargadas
        const morasVisibles = document.querySelectorAll('.card-body.bg-light:not(.d-none)').length;
        
        if (morasVisibles === 0 && fechaInput.value && obtenerCuotasConMoras().length > 0) {
            console.log('📋 No hay moras visibles - recalculando...');
            recalcularMoras();
        } else {
            console.log('✅ Moras ya cargadas desde servidor - no recalculando');
        }
    }, 500);
});

// ============================================
// TOOLTIPS INFORMATIVOS
// ============================================
// Inicializar tooltips de Bootstrap
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl, {
        html: true,
        trigger: 'hover focus'
    });
});
</script>

<style>
/* Estilos para tooltips mejorados */
.info-tooltip {
    cursor: help;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.info-tooltip:hover {
    transform: scale(1.2);
}

.info-tooltip i {
    font-size: 14px;
}

/* Mejorar visibilidad de tooltips */
.tooltip {
    font-size: 13px;
}

.tooltip-inner {
    max-width: 300px;
    padding: 10px 15px;
    text-align: left;
    background-color: #2c3e50;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.tooltip-arrow {
    display: none;
}

/* Animación suave para labels con tooltips */
.form-label-with-tooltip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.form-label-with-tooltip .info-tooltip {
    opacity: 0.7;
}

.form-label-with-tooltip:hover .info-tooltip {
    opacity: 1;
}

/* Resaltar campos con ayuda disponible */
.has-tooltip-help {
    position: relative;
}

.has-tooltip-help::after {
    content: '';
    position: absolute;
    top: -2px;
    right: -2px;
    width: 8px;
    height: 8px;
    background-color: #3498db;
    border-radius: 50%;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.has-tooltip-help:hover::after {
    opacity: 0.6;
}
</style>

@endsection