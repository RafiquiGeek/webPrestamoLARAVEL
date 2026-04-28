<!-- Estado de Cuenta - Lista de Cuotas con Acordeón -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Estado de Cuenta</h5>
    <button type="button" 
            class="btn btn-primary btn-sm" 
            onclick="imprimirEstadoCuenta({{ $prestamo->id }})"
            title="Imprimir Estado de Cuenta">
        <i class="fas fa-print me-2"></i>Imprimir PDF
    </button>
</div>
<div class="table-responsive">
    <table class="table table-hover table-striped align-middle">
        <thead class="bg-light text-dark">
            <tr>
                <th>Nro</th>
                <th>Fecha de Pago</th>
                <th>Saldo Capital</th>
                <th>Cuota</th>
                <th>Mora</th>
                <th>Mora Pend.</th>
                <th>Último Pago</th>
                <th>Estado</th>
                <th class="d-none d-lg-table-cell">Operación</th>
                <th class="d-none d-lg-table-cell">Método</th>
                <th class="comprobante-col">
                    Comprobante SUNAT
                    <button type="button" class="btn btn-link btn-sm p-0 ms-2"
                            onclick="verificarConfiguracionSunat()"
                            title="Verificar configuración SUNAT">
                        <i class="fas fa-cogs text-primary"></i>
                    </button>
                </th>
                <th>Acciones</th>
            </tr>
        </thead>
        @php
            $saldoPendiente = $cuotas->sum('monto');

            // Calculamos los totales fuera del bucle
            $totalCapital = $cuotas->sum('monto');
            // Sumar el monto_aplicado desde la tabla pivot operaciones_cuota para obtener el total correcto
            // EXCLUIR operaciones padre que tienen operaciones hijas (para evitar duplicación)
            $totalAbonos = $cuotas->sum(function($cuota) {
                return DB::table('operaciones_cuota')
                    ->join('operaciones', 'operaciones_cuota.operacion_id', '=', 'operaciones.id')
                    ->where('operaciones_cuota.cuota_id', $cuota->id)
                    ->where('operaciones.estado', '!=', 'anulado')
                    ->where(function($query) {
                        // Solo incluir operaciones SIN hijas, o que sean hijas ellas mismas
                        $query->whereNotNull('operaciones.operacion_general_id') // Es una operación hija
                              ->orWhereNotExists(function($subquery) {
                                  // O no tiene operaciones hijas
                                  $subquery->select(DB::raw(1))
                                           ->from('operaciones as ops_hijas')
                                           ->whereColumn('ops_hijas.operacion_general_id', 'operaciones.id');
                              });
                    })
                    ->sum('operaciones_cuota.monto_aplicado');
            });
            $totalInteres = $cuotas->sum('interes');
            $totalComision = $cuotas->sum('comision');
            $totalIgv = $cuotas->sum('igv');
            $totalCantidadMoras = $cuotas->sum('cantidad_mora');
            $totalMorasPagadas = $cuotas->sum(fn($cuota) => $cuota->monto_pagado_moras);
            
            // Calcular totales de abonos mora a favor
            $totalAbonosMoraFavor = $cuotas->sum(fn($cuota) => $cuota->totalAbonosMoraFavor ?? 0);
            $totalSaldoMoraFavor = $cuotas->sum(fn($cuota) => $cuota->saldoMoraFavor ?? 0);
        @endphp

        <tbody>
            @php
                // Precalcular el saldo capital inicial
                $saldoCapitalInicial = $cuotas->sum('monto');
                $capitalPagadoAcumulado = 0;
            @endphp
            @foreach ($cuotas as $index => $cuota)
            @php
                // Usar monto_pagado si existe, sino calcular desde operaciones no anuladas
                if (Schema::hasColumn('cuotas', 'monto_pagado')) {
                    $abonoTotal = $cuota->monto_pagado ?? 0;
                } else {
                    // Sumar el monto_aplicado desde la tabla pivot operaciones_cuota
                    // EXCLUIR operaciones padre que tienen operaciones hijas (para evitar duplicación)
                    $abonoTotal = DB::table('operaciones_cuota')
                        ->join('operaciones', 'operaciones_cuota.operacion_id', '=', 'operaciones.id')
                        ->where('operaciones_cuota.cuota_id', $cuota->id)
                        ->where('operaciones.estado', '!=', 'anulado')
                        ->where(function($query) {
                            // Solo incluir operaciones SIN hijas, o que sean hijas ellas mismas
                            $query->whereNotNull('operaciones.operacion_general_id') // Es una operación hija
                                  ->orWhereNotExists(function($subquery) {
                                      // O no tiene operaciones hijas
                                      $subquery->select(DB::raw(1))
                                               ->from('operaciones as ops_hijas')
                                               ->whereColumn('ops_hijas.operacion_general_id', 'operaciones.id');
                                  });
                        })
                        ->sum('operaciones_cuota.monto_aplicado');
                }

                $capitalPagadoAcumulado += $abonoTotal;
                $saldoCapital = max(0, $saldoCapitalInicial - $capitalPagadoAcumulado);

                // Obtener la última operación que abonó a esta cuota (la más reciente)
                $ultimaOperacion = $cuota->operaciones
                    ->where('estado', '!=', 'anulado')
                    ->sortByDesc('fecha')
                    ->first();

                // Calcular porcentaje de pago
                $porcentajePago = $cuota->monto > 0 ? ($abonoTotal / $cuota->monto) * 100 : 0;
                if ($porcentajePago > 100) $porcentajePago = 100;

                // Determinar color de la barra de progreso
                $colorBarra = $porcentajePago >= 100 ? 'bg-success' : 
                             ($porcentajePago >= 50 ? 'bg-info' : 'bg-warning');

                // Determinar el estado de la cuota
                if ($abonoTotal >= $cuota->monto) {
                    $estadoTexto = 'Pagado';
                    $estadoClass = 'success';
                } elseif ($abonoTotal > 0) {
                    $estadoTexto = 'Pago parcial';
                    $estadoClass = 'warning';
                } else {
                    $estadoTexto = 'Pendiente';
                    $estadoClass = 'danger';
                }

                // Calcular abonos de mora a favor para esta cuota
                $abonosMoraFavor = $cuota->totalAbonosMoraFavor ?? 0;
                $saldoMoraFavor = $cuota->saldoMoraFavor ?? 0;
            @endphp
            <tr>
                <td class="text-center fw-bold">{{ $cuota->numero }}</td>
                <td>{{ \Carbon\Carbon::parse($cuota->fecha_pago)->format('d-m-Y') }}</td>
                <td class="text-end">S/. {{ number_format($saldoCapital, 2) }}</td>
                <td>
                    {{-- Usar $abonoTotal que ya fue calculado correctamente al inicio del bucle --}}
                    <div>
                        <span class="text-dark">{{ number_format($abonoTotal, 2) }}</span>
                        /
                        <span class="text-primary">{{ number_format($cuota->monto, 2) }}</span>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar {{ $colorBarra }}"
                            role="progressbar"
                            style="width: {{ $porcentajePago }}%;"
                            aria-valuenow="{{ $porcentajePago }}"
                            aria-valuemin="0"
                            aria-valuemax="100"></div>
                    </div>
                </td>
                <!--td class="text-end">S/. {{ number_format($cuota->cantidad_mora, 2) }}</td>
                <td class="text-end text-danger">S/. {{ number_format($cuota->monto_pendiente_moras ?? 0, 2) }}</td-->

                <td class="text-end">
                    S/. {{ number_format($cuota->monto_pagado_moras_limitado, 2) }}
                </td>
                <td class="text-end text-danger">
                    @php
                        // Calcular el monto pendiente de mora considerando los abonos a favor
                        $moraPendiente = $cuota->monto_pendiente_moras ?? 0;
                        $abonoFavor = $saldoMoraFavor; // Este es el abono a favor acumulado
                        
                        // Restar el abono a favor de la mora pendiente
                        $moraPendienteCalculada = $moraPendiente - $abonoFavor;
                        
                        // Formatear el resultado (puede ser negativo)
                        $formattedMoraPendiente = number_format($moraPendienteCalculada, 2);
                    @endphp
                    S/. {{ $formattedMoraPendiente }}
                </td>
                
                <!--td class="text-end text-success">
                    @if($saldoMoraFavor > 0)
                        <i class="fas fa-piggy-bank me-1"></i>
                        S/. {{ number_format($saldoMoraFavor, 2) }}
                    @else
                        --
                    @endif
                </td-->
                <td>
                    @if($ultimaOperacion)
                        <div>{{ \Carbon\Carbon::parse($ultimaOperacion->fecha)->format('d/m/Y') }}</div>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($ultimaOperacion->fecha)->format('H:i') }}</small>
                    @else
                        --
                    @endif
                </td>
                <td style="color:#fff;">
                    @php
                        // Determinar el estado basado en los pagos
                        if ($abonoTotal >= $cuota->monto) {
                            $estadoTexto = 'Pagado';
                            $estadoClass = 'success';
                        } elseif ($abonoTotal > 0) {
                            $estadoTexto = 'Pago parcial';
                            $estadoClass = 'warning';
                        } else {
                            // Verificar si está vencida
                            $estaVencida = \Carbon\Carbon::parse($cuota->fecha_pago)->isPast();
                            if ($estaVencida) {
                                $estadoTexto = 'Vencida';
                                $estadoClass = 'danger';
                            } else {
                                $estadoTexto = 'Pendiente';
                                $estadoClass = 'secondary';
                            }
                        }
                    @endphp
                    <span class="badge border border-{{ $estadoClass }} text-{{ $estadoClass }}" style="border-color: #dc354561!important; border-radius: 25px; background: transparent; font-size: 8pt;">
                        {{ $estadoTexto }}
                    </span>
                </td>
                <td class="d-none d-lg-table-cell">
                    @if($ultimaOperacion)
                        #{{ $ultimaOperacion->id }}
                    @else
                        --
                    @endif
                </td>
                <td class="d-none d-lg-table-cell">
                    @if($ultimaOperacion)
                        <div>{{ \Carbon\Carbon::parse($ultimaOperacion->fecha)->format('d/m/Y') }}</div>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($ultimaOperacion->fecha)->format('H:i') }}</small>
                    @else
                        --
                    @endif
                </td>

                <!-- Columna de Comprobante SUNAT -->
                <td class="comprobante-col">
                    @php
                        // Buscar TODOS los comprobantes de esta cuota (orden más reciente primero)
                        $comprobantes = \App\Models\Comprobante::where('cuota_id', $cuota->id)
                            ->orderBy('created_at', 'desc')
                            ->get();

                        // El más reciente (actual)
                        $comprobante = $comprobantes->first();

                        // Si hay más de uno, significa que fue reemplazado
                        $tieneAnterior = $comprobantes->count() > 1;
                        $comprobanteAnterior = $tieneAnterior ? $comprobantes->get(1) : null;

                        // Determinar si la cuota está completamente pagada
                        $cuotaTotalmentePagada = $abonoTotal >= $cuota->monto;
                        $tieneComprobantesHabilitados = $prestamo->tiene_comprobante == 1;

                        // Determinar si el comprobante está anulado
                        $comprobanteAnulado = $comprobante && in_array(strtoupper($comprobante->estado), ['ANULADO', 'ELIMINADO', 'BAJA', 'INACTIVO']);

                        // Determinar si el comprobante está en error
                        $comprobanteEnError = $comprobante && strtoupper($comprobante->estado) === 'ERROR';

                        // Calcular tiempo transcurrido desde emisión y si está dentro de 48 horas
                        $dentroDeHoras48 = false;
                        $comprobanteVencido = false;
                        if ($comprobante && $comprobante->fecha_emision) {
                            $fechaEmision = \Carbon\Carbon::parse($comprobante->fecha_emision);
                            $hace48Horas = \Carbon\Carbon::now()->subHours(48);
                            $dentroDeHoras48 = $fechaEmision->gt($hace48Horas);

                            $estadoSunat = strtoupper($comprobante->estado);
                            // Solo contar como vencido si NO ha sido aceptado/enviado exitosamente
                            $noAceptado = !in_array($estadoSunat, ['ENVIADO', 'ACEPTADO', 'ACEPTADO CON OBSERVACIONES']);

                            if ($noAceptado && !$dentroDeHoras48) {
                                $comprobanteVencido = true;
                            }
                        }

                        // Determinar si mostrar botón Generar
                        $mostrarBotonGenerar = (!$comprobante || $comprobanteAnulado) && $cuotaTotalmentePagada && $tieneComprobantesHabilitados;

                        // Determinar si mostrar botón Reenviar (ERROR dentro de 48 horas)
                        $mostrarBotonReenviar = $comprobante && $comprobanteEnError && $dentroDeHoras48 &&
                                               $cuotaTotalmentePagada && $tieneComprobantesHabilitados;

                        // Determinar si mostrar botón Regenerar (ERROR vencido o vencido sin aceptación)
                        $mostrarBotonRegenerar = $comprobante && $cuotaTotalmentePagada && $tieneComprobantesHabilitados &&
                                                 ($comprobanteVencido || ($comprobanteEnError && !$dentroDeHoras48));
                    @endphp

                    @if($comprobante && !$comprobanteAnulado)
                        @php
                            $estadoSunat = strtoupper($comprobante->estado);
                            $statusData = match($estadoSunat) {
                                'ENVIADO', 'ACEPTADO' => ['success', 'check-circle', 'Emitido'],
                                'PENDIENTE' => ['warning', 'clock', 'Pendiente'],
                                'GENERADO_UBL', 'GENERADO_LOCAL' => ['info', 'file-alt', 'Generado'],
                                'ERROR' => ['danger', 'exclamation-triangle', 'Error'],
                                'ANULADO', 'ELIMINADO', 'BAJA', 'INACTIVO' => ['danger', 'ban', 'Anulado'],
                                default => ['secondary', 'question', 'Desconocido']
                            };
                        @endphp
                        <div class="comprobante-status">
                            <span class="badge bg-{{ $statusData[0] }} d-flex align-items-center gap-1">
                                <i class="fas fa-{{ $statusData[1] }}"></i>
                                {{ $statusData[2] }}
                            </span>

                            @if($comprobanteEnError || $comprobanteVencido)
                                <small class="text-muted d-block mt-1">
                                    @if($comprobanteEnError)
                                        <em>Error en la emisión</em>
                                    @elseif($comprobanteVencido)
                                        <em>Superó 48 horas</em>
                                    @endif
                                </small>
                            @endif

                            @if($comprobante->serie && $comprobante->numero)
                                <small class="text-muted d-block mt-1">
                                    <a href="{{ route('admin.comprobantes.show', $comprobante->id) }}"
                                    class="text-decoration-none"
                                    title="Ver detalles del comprobante">
                                        {{ $comprobante->serie }}-{{ str_pad($comprobante->numero, 6, '0', STR_PAD_LEFT) }}
                                        <i class="fas fa-external-link-alt fa-xs"></i>
                                    </a>
                                </small>
                            @endif

                            @if($mostrarBotonReenviar)
                                <div class="mt-2">
                                    <button type="button" class="btn btn-info btn-sm"
                                            onclick="reenviarComprobanteSunat({{ $cuota->id }}, {{ $comprobante->id }})"
                                            title="Reenviar el mismo comprobante a SUNAT">
                                        <i class="fas fa-share me-1"></i>
                                        Reenviar
                                    </button>
                                </div>
                            @elseif($mostrarBotonRegenerar)
                                <div class="mt-2">
                                    <button type="button" class="btn btn-warning btn-sm"
                                            onclick="regenerarComprobanteSunat({{ $cuota->id }}, {{ $comprobante->id }})"
                                            title="Generar nuevo comprobante hoy">
                                        <i class="fas fa-redo me-1"></i>
                                        Regenerar
                                    </button>
                                </div>
                            @endif

                            @if($tieneAnterior && $comprobanteAnterior)
                                <div class="mt-3 p-2 border border-danger-subtle bg-danger-subtle rounded">
                                    <small class="text-danger d-block mb-2">
                                        <strong><i class="fas fa-arrow-up me-1"></i>Reemplazado:</strong>
                                    </small>
                                    <small class="text-muted d-block">
                                        <a href="{{ route('admin.comprobantes.show', $comprobanteAnterior->id) }}"
                                        class="text-decoration-none text-danger">
                                            {{ $comprobanteAnterior->serie }}-{{ str_pad($comprobanteAnterior->numero, 6, '0', STR_PAD_LEFT) }}
                                            <i class="fas fa-external-link-alt fa-xs"></i>
                                        </a>
                                    </small>
                                    <small class="text-muted d-block">
                                        {{ \Carbon\Carbon::parse($comprobanteAnterior->created_at)->format('d/m/Y H:i') }}
                                    </small>
                                    @php
                                        $estadoAnterior = strtoupper($comprobanteAnterior->estado);
                                    @endphp
                                    <small class="d-block mt-1">
                                        <span class="badge bg-secondary">{{ $estadoAnterior }}</span>
                                    </small>
                                </div>
                            @endif
                        </div>
                    @elseif($mostrarBotonGenerar)
                        <!-- Mostrar botón Generar cuando no hay comprobante o está anulado -->
                        <button type="button" class="btn btn-primary btn-sm"
                                onclick="generarComprobanteSunat({{ $cuota->id }})"
                                title="Generar comprobante electrónico">
                            <i class="fas fa-plus me-1"></i>
                            Generar
                        </button>
                    @else
                        <!-- Mostrar mensajes cuando no se puede generar -->
                        @if(!$cuotaTotalmentePagada && $tieneComprobantesHabilitados)
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Cuota debe estar pagada
                            </small>
                        @elseif(!$tieneComprobantesHabilitados)
                            <small class="text-muted">
                                <i class="fas fa-times-circle me-1"></i>
                                Comprobantes desactivados
                            </small>
                        @elseif($comprobante && $comprobanteAnulado)
                            <span class="badge bg-danger">
                                <i class="fas fa-ban"></i> Anulado
                            </span>
                        @else
                            <span class="text-muted">--</span>
                        @endif
                    @endif
                </td>
                <td>
                    @php
                        // Verificar si hay operaciones, moras o detalles para mostrar
                        $tieneOperaciones = $cuota->operaciones ? $cuota->operaciones->isNotEmpty() : false;
                        
                        // Buscar moras de forma segura usando la relación correcta
                        $tieneMoras = false;
                        try {
                            if (method_exists($cuota, 'moraCuotas') && $cuota->moraCuotas) {
                                $tieneMoras = $cuota->moraCuotas->where('estado', '!=', \App\Enums\MoraCuotaEstado::REGULARIZADA->value)->isNotEmpty();
                            } else {
                                // Buscar moras directamente por cuota_id
                                $tieneMoras = \App\Models\MoraCuota::where('cuota_id', $cuota->id)
                                    ->where('estado', '!=', \App\Enums\MoraCuotaEstado::REGULARIZADA->value)
                                    ->exists();
                            }
                        } catch (\Exception $e) {
                            \Log::warning("Error checking moras for cuota {$cuota->id}: " . $e->getMessage());
                            $tieneMoras = false;
                        }
                        
                        $estaVencida = \Carbon\Carbon::parse($cuota->fecha_pago)->isPast();
                        $mostrarVerBtn = $tieneOperaciones || $tieneMoras || $estaVencida;
                        
                        // Lógica para mostrar u ocultar el botón de pago
                        // Solo mostrar en estados: Vigente, Vigente con moras, Moroso o Aprobado
                        $mostrarPagarBtn = false;
                        $estadosPermitidosParaPago = ['Vigente', 'Vigente con moras', 'Moroso', 'Aprobado'];

                        if (in_array($prestamo->estado, $estadosPermitidosParaPago)) {
                            // Verificar si hay moras pendientes de pago
                            $tieneMorasPendientes = false;
                            try {
                                if (method_exists($cuota, 'moraCuotas') && $cuota->moraCuotas) {
                                    $tieneMorasPendientes = $cuota->moraCuotas->whereIn('estado', [
                                        \App\Enums\MoraCuotaEstado::PENDIENTE->value,
                                        \App\Enums\MoraCuotaEstado::PARCIAL->value
                                    ])->isNotEmpty();
                                } else {
                                    // Buscar moras pendientes directamente por cuota_id
                                    $tieneMorasPendientes = \App\Models\MoraCuota::where('cuota_id', $cuota->id)
                                        ->whereIn('estado', [
                                            \App\Enums\MoraCuotaEstado::PENDIENTE->value,
                                            \App\Enums\MoraCuotaEstado::PARCIAL->value
                                        ])
                                        ->exists();
                                }
                            } catch (\Exception $e) {
                                \Log::warning("Error checking moras pendientes for cuota {$cuota->id}: " . $e->getMessage());
                                $tieneMorasPendientes = false;
                            }
                            
                            // Mostrar botón solo si:
                            // 1. Hay saldo pendiente de la cuota, O
                            // 2. Hay moras pendientes de pago (estado PENDIENTE o PARCIAL)
                            if ($abonoTotal < $cuota->monto || $tieneMorasPendientes) {
                                $mostrarPagarBtn = true;
                            }
                        }
                    @endphp
                    
                    <div class="btn-group" role="group">
                        @if($mostrarVerBtn)
                            <!-- Botón de Ver Detalles -->
                            <button class="btn btn-link btn-sm px-3 btnver" 
                                    data-bs-toggle="collapse"
                                    data-bs-target="#detallePago{{ $cuota->id }}" 
                                    aria-expanded="false"
                                    aria-controls="detallePago{{ $cuota->id }}"
                                    title="Ver detalles de cuota (pagos y moras)"> 
                                <i class="fas fa-eye text-info"></i>
                            </button>
                        @endif
                        
                        @if($mostrarPagarBtn)
                            <button
                                type="button"
                                class="btn btn-success btn-sm btn-registrar-pago"
                                data-cuota-id="{{ $cuota->id }}"
                                data-cuota-numero="{{ $cuota->numero }}"
                                data-cuota-monto="{{ $cuota->monto }}"
                                data-monto-pagado="{{ $abonoTotal }}"
                                data-saldo-pendiente="{{ $cuota->monto - $abonoTotal }}"
                                data-fecha-pago="{{ $cuota->fecha_pago }}"
                                title="Registrar pago"
                            >
                                Pagar
                            </button>
                        @endif

                    </div>
                </td>
            </tr>
            
            <!-- Detalles de Pagos y Moras (Acordeón) -->
            @if($mostrarVerBtn)
            <tr class="collapse" id="detallePago{{ $cuota->id }}">
                <td colspan="12" class="p-0">
                    <div class="bg-light border-start border-primary border-4" style="margin: 0 10px;">
                        <div class="p-4">
                            <!-- Encabezado mejorado -->
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h5 class="mb-0 d-flex align-items-center text-primary">
                                    <i class="fas fa-info-circle me-2 mr-2"></i>
                                    Detalles de Cuota #{{ $cuota->numero }}
                                </h5>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge px-3 py-2">
                                        <i class="fas fa-calendar me-1 mr-2"></i>
                                        {{ \Carbon\Carbon::parse($cuota->fecha_pago)->format('d/m/Y') }}
                                    </span>
                                    @if($estaVencida && $abonoTotal < $cuota->monto)
                                        <span class="badge bg-danger text-white px-3 py-2">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            VENCIDA
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Resumen financiero con el mismo estilo de la cabecera -->
                            <div class="row g-1 mb-2">
                                <div class="col-md-3">
                                    <div class="info-card bg-white">
                                        <div class="info-label">Monto Original</div>
                                        <div class="info-value small d-flex justify-content-between">
                                            <div class="info-value"><i class="fas fa-coins me-1 mr-2"></i>S/. {{ number_format($cuota->monto, 2) }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-card bg-white">
                                        <div class="info-label">Total Pagado</div>
                                        <div class="info-value small d-flex justify-content-between">
                                            <div class="info-value"><i class="fas fa-check-circle me-1 mr-2"></i>S/. {{ number_format($abonoTotal, 2) }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-card bg-white">
                                        <div class="info-label">Saldo Pendiente</div>
                                        <div class="info-value small d-flex justify-content-between">
                                            <div class="info-value"><i class="fas fa-clock me-1  mr-2"></i>S/. {{ number_format($cuota->monto - $abonoTotal, 2) }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-card bg-white">
                                        <div class="info-label">Progreso de Pago</div>
                                        <div class="info-value small d-flex justify-content-between">
                                            <div class="info-value">{{ number_format($porcentajePago, 1) }} <i class="fas fa-percentage me-1 mr-2"></i></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contenido en pestañas -->
                            <div class="card shadow-sm border-0 rounded-lg overflow-hidden">
                                <div class="card-header p-0 border-bottom-0">
                                    <ul class="nav nav-tabs nav-justified w-100" id="detalleTab{{ $cuota->id }}" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="w-100 nav-link active px-4 py-3" 
                                                   id="pagos-tab-{{ $cuota->id }}" 
                                                   data-bs-toggle="tab" 
                                                   data-bs-target="#pagos-{{ $cuota->id }}" 
                                                   type="button" 
                                                   role="tab"
                                                   aria-controls="pagos-{{ $cuota->id }}"
                                                   aria-selected="true">
                                                <i class="fas fa-money-bill-wave me-2"></i> Pagos ({{ $cuota->operaciones->count() }})
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="w-100 nav-link px-4 py-3" 
                                                   id="moras-tab-{{ $cuota->id }}" 
                                                   data-bs-toggle="tab" 
                                                   data-bs-target="#moras-{{ $cuota->id }}" 
                                                   type="button" 
                                                   role="tab"
                                                   aria-controls="moras-{{ $cuota->id }}"
                                                   aria-selected="false">
                                                @php
                                                    // Contar moras
                                                    $cantidadMoras = 0;
                                                    try {
                                                        if (method_exists($cuota, 'moraCuotas') && $cuota->moraCuotas) {
                                                            $cantidadMoras = $cuota->moraCuotas->where('estado', '!=', \App\Enums\MoraCuotaEstado::REGULARIZADA->value)->count();
                                                        } else {
                                                            $cantidadMoras = \App\Models\MoraCuota::where('cuota_id', $cuota->id)
                                                                ->where('estado', '!=', \App\Enums\MoraCuotaEstado::REGULARIZADA->value)
                                                                ->count();
                                                        }
                                                    } catch (\Exception $e) {
                                                        $cantidadMoras = 0;
                                                    }
                                                @endphp
                                                <i class="fas fa-exclamation-triangle me-2"></i> Moras ({{ $cantidadMoras }})
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="card-body bg-white p-0">
                                    <div class="tab-content" id="detalleTabContent{{ $cuota->id }}">
                                        <!-- Pestaña de Pagos -->
                                        <div class="tab-pane fade show active p-3" id="pagos-{{ $cuota->id }}" role="tabpanel">
                                        @if($cuota->operaciones->isNotEmpty())
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th><i class="fas fa-hashtag me-1 mr-2"></i>ID</th>
                                                            <th><i class="fas fa-calendar me-1 mr-2"></i>Fecha</th>
                                                            <th><i class="fas fa-calendar me-1 mr-2"></i>Nro. Operación</th>
                                                            <th><i class="fas fa-dollar-sign me-1 mr-2"></i>Monto</th>
                                                            <th><i class="fas fa-credit-card me-1 mr-2"></i>Método</th>
                                                            <th><i class="fas fa-user me-1 mr-2"></i>Usuario</th>
                                                            <th><i class="fas fa-file-alt me-1 mr-2"></i>Voucher</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($cuota->operaciones->where('estado', '!=', 'anulado') as $operacion)
                                                        <tr>
                                                            <td><span class="badge">#{{ $operacion->id }}</span></td>
                                                            <td>
                                                                <div>{{ \Carbon\Carbon::parse($operacion->fecha)->format('d/m/Y') }}</div>
                                                                <small class="text-muted">{{ \Carbon\Carbon::parse($operacion->fecha)->format('H:i') }}</small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-light">{{ $operacion->codigo ?? '#' . $operacion->id }}</span>
                                                            </td>
                                                            <td class="fw-bold text-success">S/. {{ number_format($operacion->abono, 2) }}</td>

                                                            <td>
                                                                <span class="badge text-white bg-info">{{ $operacion->metodoDePago->metodo_pago ?? 'N/A' }}</span>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    {{ optional($operacion->user)->codigo ?? 'N/A' }}
                                                                </div>
                                                            </td>
                                                            <td>
                                                                @if($operacion->voucher_path)
                                                                    <button class="btn btn-outline-primary btn-sm" onclick="mostrarVoucher('{{ asset('storage/' . $operacion->voucher_path) }}')">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                @else
                                                                    <span class="text-muted">N/A</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center py-4">
                                                <div class="mb-3">
                                                    <i class="fas fa-file-invoice text-muted" style="font-size: 3rem;"></i>
                                                </div>
                                                <h6 class="text-muted">No hay pagos registrados</h6>
                                                <p class="text-muted small mb-0">Esta cuota aún no tiene pagos asociados</p>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Pestaña de Moras -->
                                    <div class="tab-pane fade p-3" id="moras-{{ $cuota->id }}" role="tabpanel">
                                        @php
                                            // Buscar moras de forma segura
                                            $morasCuota = collect();
                                            try {
                                                if (method_exists($cuota, 'moraCuotas') && $cuota->moraCuotas) {
                                                    $morasCuota = $cuota->moraCuotas->where('estado', '!=', \App\Enums\MoraCuotaEstado::REGULARIZADA->value);
                                                } else {
                                                    // Buscar moras directamente por cuota_id
                                                    $morasCuota = \App\Models\MoraCuota::where('cuota_id', $cuota->id)
                                                        ->where('estado', '!=', \App\Enums\MoraCuotaEstado::REGULARIZADA->value)
                                                        ->get();
                                                }
                                            } catch (\Exception $e) {
                                                \Log::warning("Error getting moras for cuota {$cuota->id}: " . $e->getMessage());
                                                $morasCuota = collect();
                                            }
                                        @endphp
                                        @if($morasCuota->isNotEmpty())
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th><i class="fas fa-hashtag me-1 mr-2"></i>ID</th>
                                                            <th><i class="fas fa-calendar me-1 mr-2"></i>Generada</th>
                                                            <th><i class="fas fa-calendar me-1 mr-2"></i>Nro. Operación</th>
                                                            <th><i class="fas fa-clock me-1 mr-2"></i>Días</th>
                                                            <th><i class="fas fa-dollar-sign me-1 mr-2"></i>Monto</th>
                                                            <th><i class="fas fa-check-circle me-1 mr-2"></i>Pagado</th>
                                                            <th><i class="fas fa-info-circle me-1 mr-2"></i>Estado</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($morasCuota as $mora)
                                                        <tr>
                                                            <td><span class="badge text-dark">#{{ $mora->id }}</span></td>
                                                            <td>
                                                                <div>{{ \Carbon\Carbon::parse($mora->fecha)->format('d/m/Y') }}</div>
                                                                <small class="text-muted">{{ \Carbon\Carbon::parse($mora->fecha)->format('H:i') }}</small>
                                                            </td>
                                                            <td>
                                                                @php
                                                                    // Obtener operaciones que pagaron esta mora específica usando la tabla operacion_mora
                                                                    $operacionesMora = collect();
                                                                    
                                                                    try {
                                                                        // Buscar en la tabla operacion_mora las operaciones relacionadas a esta mora específica
                                                                        $operacionesIds = \DB::table('operacion_mora')
                                                                            ->where('mora_cuota_id', $mora->id)
                                                                            ->pluck('operacion_id')
                                                                            ->unique()
                                                                            ->sort();
                                                                        
                                                                        $operacionesMora = $operacionesMora->merge($operacionesIds);
                                                                        
                                                                    } catch (\Exception $e) {
                                                                        // En caso de error, registrar en log y mostrar mensaje genérico
                                                                        \Log::warning("Error getting operations for mora {$mora->id}: " . $e->getMessage());
                                                                        $operacionesMora = collect();
                                                                    }
                                                                @endphp
                                                                
                                                                @if($operacionesMora->isNotEmpty())
                                                                    @foreach($operacionesMora as $opId)
                                                                        <span class="badge bg-light me-1">#{{ $opId }}</span>
                                                                    @endforeach
                                                                    @if($operacionesMora->count() > 1)
                                                                        <br><small class="text-muted">{{ $operacionesMora->count() }} operaciones</small>
                                                                    @endif
                                                                @else
                                                                    <small class="text-muted">Sin pagos</small>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <span class="badge 
                                                                    @if($mora->dias_mora <= 7) bg-light 
                                                                    @elseif($mora->dias_mora <= 30) bg-danger 
                                                                    @else bg-dark @endif">
                                                                    {{ $mora->dias_mora }} días
                                                                </span>
                                                            </td>
                                                            <td class="fw-bold text-danger">S/. {{ number_format($mora->monto, 2) }}</td>
                                                            <td class="fw-bold text-success">S/. {{ number_format($mora->monto_pagado ?? 0, 2) }}</td>
                                                            <td>
                                                                @php
                                                                    // Estados hardcodeados según el enum MoraCuotaEstado
                                                                    // Obtener el valor entero del enum o directamente el valor
                                                                    $estadoValue = is_object($mora->estado) ? $mora->estado->value : (int)$mora->estado;
                                                                    
                                                                    $estadoMora = match($estadoValue) {
                                                                        0 => ['Pendiente', 'danger'], // PENDIENTE
                                                                        1 => ['Parcial', 'warning'],  // PARCIAL  
                                                                        2 => ['Pagado', 'success'],   // PAGADO
                                                                        3 => ['Regularizada', 'info'], // REGULARIZADA
                                                                        default => ['Estado ' . $estadoValue, 'secondary']
                                                                    };
                                                                @endphp
                                                                <span class="badge text-white bg-{{ $estadoMora[1] }}">{{ $estadoMora[0] }}</span>
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center py-4">
                                                <div class="mb-3">
                                                    <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                                                </div>
                                                <h6 class="text-success">¡Sin moras!</h6>
                                                <p class="text-muted small mb-0">Esta cuota no tiene moras pendientes</p>
                                            </div>
                                        @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>
        
        <tfoot class="bg-light">
            <!-- Fila de totales principales -->
            <tr class="fw-bold">
                <td class="text-end" colspan="2">TOTALES:</td>
                <td class="text-end">S/. {{ number_format($saldoCapitalInicial - $totalAbonos, 2) }}</td>
                <td>
                    <div>
                        <span class="text-dark">{{ number_format($totalAbonos, 2) }}</span>
                        /
                        <span class="text-primary">{{ number_format($totalCapital, 2) }}</span>
                    </div>
                    <div class="progress" style="height: 5px;">
                        @php
                            $porcentajeTotal = $totalCapital > 0 ? ($totalAbonos / $totalCapital) * 100 : 0;
                            $porcentajeTotal = $porcentajeTotal > 100 ? 100 : $porcentajeTotal;
                            $colorTotal = $porcentajeTotal >= 90 ? 'bg-success' : 
                                       ($porcentajeTotal >= 50 ? 'bg-info' : 'bg-warning');
                        @endphp
                        <div class="progress-bar {{ $colorTotal }}"
                            role="progressbar"
                            style="width: {{ $porcentajeTotal }}%;"
                            aria-valuenow="{{ $porcentajeTotal }}"
                            aria-valuemin="0"
                            aria-valuemax="100"></div>
                    </div>
                </td>
                <td>S/. {{ number_format($totalMorasPagadas, 2) }}</td>
                <!--td>S/. {{ number_format($cuotas->sum(fn($cuota) => $cuota->monto_pendiente_moras), 2) }}</td-->
                <td>
                    @php
                        $totalMoraPendiente = $cuotas->sum(function($cuota) {
                            $moraPendiente = $cuota->monto_pendiente_moras ?? 0;
                            $abonoFavor = $cuota->saldoMoraFavor ?? 0;
                            return $moraPendiente - $abonoFavor;
                        });
                    @endphp
                    S/. {{ number_format($totalMoraPendiente, 2) }}
                </td>
                <td class="text-success fw-bold">
                    @if($totalSaldoMoraFavor > 0)
                        <i class="fas fa-piggy-bank me-1"></i>
                        S/. {{ number_format($totalSaldoMoraFavor, 2) }}
                    @else
                        --
                    @endif
                </td>
            </tr>
            <!-- Fila de Abonos a Favor 
            @if($totalAbonosMoraFavor > 0)
            <tr class="bg-info text-white">
                <td colspan="5" class="text-end fw-bold">
                    <i class="fas fa-piggy-bank me-2"></i>
                    Abonos Mora a Favor:
                </td>
                <td colspan="2" class="text-center">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Total Abonado: S/. {{ number_format($totalAbonosMoraFavor, 2) }}</span>
                        <span class="fw-bold">Saldo Disponible: S/. {{ number_format($totalSaldoMoraFavor, 2) }}</span>
                    </div>
                </td>
                <td colspan="4"></td>
            </tr>
            @endif
            -->
        </tfoot>
    </table>
</div>
<script>
    function anularComprobante(comprobanteId) {
    Swal.fire({
        title: '¿Anular comprobante?',
        text: 'Esta acción generará una comunicación de baja ante SUNAT',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar',
        input: 'text',
        inputPlaceholder: 'Motivo de anulación (opcional)',
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Anulando comprobante...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar petición
            fetch(`/admin/comprobantes/${comprobanteId}/anular`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify({
                    motivo: result.value || 'Anulación solicitada por el usuario'
                })
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Comprobante anulado!',
                        text: data.message,
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo anular el comprobante'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor'
                });
            });
        }
    });
}

function generarComprobanteSunat(cuotaId) {
    // Primero obtener los datos de la cuota para mostrar previsualización
    Swal.fire({
        title: 'Cargando datos...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Obtener datos de la cuota para previsualización
    fetch(`/admin/comprobantes/preview-cuota/${cuotaId}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .then(response => response.json())
    .then(previewData => {
        Swal.close();

        if (!previewData.success) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: previewData.message || 'No se pudieron cargar los datos'
            });
            return;
        }

        const data = previewData.data;

        // Mostrar previsualización del comprobante
        Swal.fire({
            title: 'Previsualización del Comprobante',
            html: generarHTMLPreview(data),
            icon: null,
            width: '900px',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check"></i> Generar y Enviar a SUNAT',
            cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
            customClass: {
                popup: 'comprobante-preview-popup'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Proceder a generar el comprobante
                generarComprobanteConfirmado(cuotaId);
            }
        });
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudieron cargar los datos del comprobante'
        });
    });
}

function generarHTMLPreview(data) {
    // Calcular totales correctamente según la lógica del sistema
    // Capital + Interés = EXONERADO (no paga IGV)
    // Comisión = GRAVADA (paga IGV 18%)
    const capital = parseFloat(data.capital || 0);
    const interes = parseFloat(data.interes || 0);
    const comision = parseFloat(data.comision || 0);
    const igv = parseFloat(data.igv || 0);

    const totalExonerado = capital + interes; // Capital e Interés no pagan IGV
    const totalGravado = comision; // Solo la comisión paga IGV
    const montoTotal = parseFloat(data.monto_total);

    return `
        <div class="comprobante-preview" style="text-align: left; font-size: 14px;">
            <!-- Encabezado con logo y datos de la empresa -->
            <div style="border: 2px solid #333; padding: 15px; margin-bottom: 15px; background: #f8f9fa;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0; color: #333; font-weight: bold;">${data.empresa.razon_social}</h4>
                        <p style="margin: 5px 0; font-size: 12px;">
                            <strong>RUC:</strong> ${data.empresa.ruc}<br>
                            <strong>Dirección:</strong> ${data.empresa.direccion}<br>
                            ${data.empresa.telefono ? `<strong>Teléfono:</strong> ${data.empresa.telefono}<br>` : ''}
                            ${data.empresa.email ? `<strong>Email:</strong> ${data.empresa.email}` : ''}
                        </p>
                    </div>
                    <div style="border: 2px solid #dc3545; padding: 10px; text-align: center; background: white;">
                        <h5 style="margin: 0; color: #dc3545;">RUC ${data.empresa.ruc}</h5>
                        <h4 style="margin: 5px 0; color: #dc3545; font-weight: bold;">
                            ${data.cliente.tipo_documento === '6' ? 'FACTURA ELECTRÓNICA' : 'BOLETA DE VENTA ELECTRÓNICA'}
                        </h4>
                        <p style="margin: 0; font-size: 16px; font-weight: bold;">${data.serie}-${data.numero}</p>
                    </div>
                </div>
            </div>

            <!-- Datos del Cliente -->
            <div style="border: 1px solid #ddd; padding: 12px; margin-bottom: 15px; background: #fff;">
                <h5 style="margin: 0 0 10px 0; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
                    <i class="fas fa-user"></i> DATOS DEL CLIENTE
                </h5>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px;">
                    <div>
                        <strong>Documento:</strong> ${data.cliente.tipo_documento === '6' ? 'RUC' : 'DNI'} ${data.cliente.numero_documento}
                    </div>
                    <div>
                        <strong>Cliente:</strong> ${data.cliente.razon_social}
                    </div>
                    ${data.cliente.direccion ? `<div style="grid-column: 1 / -1;"><strong>Dirección:</strong> ${data.cliente.direccion}</div>` : ''}
                </div>
            </div>

            <!-- Detalles del Comprobante -->
            <div style="border: 1px solid #ddd; padding: 12px; margin-bottom: 15px; background: #fff;">
                <h5 style="margin: 0 0 10px 0; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
                    <i class="fas fa-info-circle"></i> INFORMACIÓN DEL COMPROBANTE
                </h5>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px;">
                    <div><strong>Fecha de Emisión:</strong> ${data.fecha_emision}</div>
                    <div><strong>Moneda:</strong> ${data.moneda === 'PEN' ? 'Soles (PEN)' : data.moneda}</div>
                    <div><strong>Préstamo:</strong> ${data.prestamo_codigo || 'N/A'}</div>
                    <div><strong>Cuota:</strong> #${data.cuota_numero}</div>
                </div>
            </div>

            <!-- Items / Conceptos -->
            <div style="margin-bottom: 15px;">
                <h5 style="margin: 0 0 10px 0; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
                    <i class="fas fa-list"></i> DETALLE DE CONCEPTOS
                </h5>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #343a40; color: white;">
                            <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Concepto</th>
                            <th style="padding: 8px; text-align: center; border: 1px solid #ddd;">Afectación IGV</th>
                            <th style="padding: 8px; text-align: right; border: 1px solid #ddd;">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${capital > 0 ? `
                        <tr>
                            <td style="padding: 8px; border: 1px solid #ddd;">
                                <strong>Capital</strong><br>
                                <small style="color: #666;">Pago a capital - Cuota #${data.cuota_numero}</small>
                            </td>
                            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                <span style="background: #17a2b8; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">
                                    EXONERADO
                                </span>
                            </td>
                            <td style="padding: 8px; border: 1px solid #ddd; text-align: right; font-weight: bold;">
                                S/ ${capital.toFixed(2)}
                            </td>
                        </tr>
                        ` : ''}
                        ${interes > 0 ? `
                        <tr>
                            <td style="padding: 8px; border: 1px solid #ddd;">
                                <strong>Interés</strong><br>
                                <small style="color: #666;">Interés financiero sobre saldo - Cuota #${data.cuota_numero}</small>
                            </td>
                            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                <span style="background: #17a2b8; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">
                                    EXONERADO
                                </span><br>
                                <small style="color: #666; font-size: 10px;">Ley IGV Art. 2</small>
                            </td>
                            <td style="padding: 8px; border: 1px solid #ddd; text-align: right; font-weight: bold;">
                                S/ ${interes.toFixed(2)}
                            </td>
                        </tr>
                        ` : ''}
                        ${comision > 0 ? `
                        <tr>
                            <td style="padding: 8px; border: 1px solid #ddd;">
                                <strong>Comisión por Gestión</strong><br>
                                <small style="color: #666;">Comisión administrativa - Cuota #${data.cuota_numero}</small>
                            </td>
                            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                <span style="background: #28a745; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">
                                    GRAVADO
                                </span><br>
                                <small style="color: #666; font-size: 10px;">Base: S/ ${comision.toFixed(2)}</small>
                            </td>
                            <td style="padding: 8px; border: 1px solid #ddd; text-align: right; font-weight: bold;">
                                S/ ${comision.toFixed(2)}
                            </td>
                        </tr>
                        ` : ''}
                        ${igv > 0 ? `
                        <tr style="background: #f8f9fa;">
                            <td style="padding: 8px; border: 1px solid #ddd;">
                                <strong>IGV (18%)</strong><br>
                                <small style="color: #666;">Impuesto sobre comisión</small>
                            </td>
                            <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                <span style="background: #6c757d; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">
                                    IMPUESTO
                                </span>
                            </td>
                            <td style="padding: 8px; border: 1px solid #ddd; text-align: right; font-weight: bold;">
                                S/ ${igv.toFixed(2)}
                            </td>
                        </tr>
                        ` : ''}
                    </tbody>
                </table>
            </div>

            <!-- Totales -->
            <div style="display: flex; justify-content: flex-end; margin-bottom: 15px;">
                <div style="min-width: 400px; border: 1px solid #ddd; padding: 12px; background: #f8f9fa;">
                    <table style="width: 100%; font-size: 13px;">
                        <tr>
                            <td style="padding: 5px;"><strong>OP. EXONERADAS:</strong></td>
                            <td style="padding: 5px; text-align: right;">S/ ${totalExonerado.toFixed(2)}</td>
                            <td style="padding: 5px; text-align: left;">
                                <small style="color: #666;">(Capital + Interés)</small>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 5px;"><strong>OP. GRAVADAS:</strong></td>
                            <td style="padding: 5px; text-align: right;">S/ ${totalGravado.toFixed(2)}</td>
                            <td style="padding: 5px; text-align: left;">
                                <small style="color: #666;">(Comisión)</small>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 5px;"><strong>IGV (18%):</strong></td>
                            <td style="padding: 5px; text-align: right;">S/ ${igv.toFixed(2)}</td>
                            <td style="padding: 5px; text-align: left;">
                                <small style="color: #666;">(Solo comisión)</small>
                            </td>
                        </tr>
                        <tr style="border-top: 2px solid #333; font-size: 16px; font-weight: bold;">
                            <td style="padding: 8px 5px;"><strong>IMPORTE TOTAL:</strong></td>
                            <td style="padding: 8px 5px; text-align: right;">S/ ${montoTotal.toFixed(2)}</td>
                            <td style="padding: 8px 5px;"></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Información Adicional -->
            <div style="border: 1px solid #ddd; padding: 10px; background: #fff3cd; font-size: 12px;">
                <p style="margin: 0;"><i class="fas fa-info-circle"></i> <strong>Importante:</strong></p>
                <ul style="margin: 5px 0; padding-left: 20px;">
                    <li>El comprobante se enviará automáticamente a SUNAT</li>
                    <li>Se generará el XML firmado digitalmente</li>
                    <li>Recibirá la Constancia de Recepción (CDR) de SUNAT</li>
                    <li>El número de serie y correlativo se asignarán automáticamente</li>
                </ul>
            </div>

            ${data.validaciones && data.validaciones.length > 0 ? `
                <div style="border: 1px solid #dc3545; padding: 10px; margin-top: 10px; background: #f8d7da; font-size: 12px;">
                    <p style="margin: 0; color: #721c24;"><i class="fas fa-exclamation-triangle"></i> <strong>Advertencias:</strong></p>
                    <ul style="margin: 5px 0; padding-left: 20px; color: #721c24;">
                        ${data.validaciones.map(v => `<li>${v}</li>`).join('')}
                    </ul>
                </div>
            ` : ''}
        </div>
    `;
}

function generarComprobanteConfirmado(cuotaId) {
    // Mostrar loading
    Swal.fire({
        title: 'Generando comprobante...',
        html: `
            <p>Generando y enviando a SUNAT</p>
            <div class="progress mt-3" style="height: 25px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%">
                    Procesando...
                </div>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false
    });

    // Enviar petición al endpoint correcto para comprobantes SUNAT
    fetch(`/admin/comprobantes/generar-comprobante-cuota`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        body: JSON.stringify({
            cuota_id: cuotaId,
            tipo_comprobante: '03'
        })
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Comprobante generado exitosamente!',
                html: `<p>${data.message}</p>
                       ${data.comprobante_numero ? `<div class="alert alert-success mt-2">
                         <strong>Comprobante: ${data.comprobante_numero}</strong>
                       </div>` : ''}`,
                confirmButtonText: 'Aceptar'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error al generar comprobante',
                text: data.message || 'No se pudo generar el comprobante'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor'
        });
    });
}

function descargarXmlComprobante(comprobanteId) {
    window.open(`/admin/comprobantes/${comprobanteId}/xml`, '_blank');
}

function descargarCdrComprobante(comprobanteId) {
    window.open(`/admin/comprobantes/${comprobanteId}/cdr`, '_blank');
}

function previsualizarComprobante(comprobanteId) {
    window.open(`/admin/comprobantes/${comprobanteId}/pdf`, '_blank');
}

function notaCreditoComprobante(comprobanteId) {
    Swal.fire({
        title: '¿Generar nota de crédito?',
        text: 'Se generará una nota de crédito asociada a este comprobante',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, generar',
        cancelButtonText: 'Cancelar',
        html: `
        <div class="text-left mt-3">
          <label class="form-label">Motivo:</label>
          <input id="motivo-credito" class="swal2-input" placeholder="Motivo de la nota de crédito">
          <label class="form-label">Monto:</label>
          <input id="monto-credito" class="swal2-input" type="number" step="0.01" placeholder="Monto de la nota de crédito">
        </div>
        `,
        preConfirm: () => {
            const motivo = document.getElementById('motivo-credito').value;
            const monto = document.getElementById('monto-credito').value;

            if (!motivo || !monto) {
                Swal.showValidationMessage('Debe completar todos los campos');
                return false;
            }

            return { motivo, monto };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Generando nota de crédito...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar petición
            fetch(`/admin/comprobantes/${comprobanteId}/nota-credito`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify(result.value)
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Nota de crédito generada!',
                        text: data.message,
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo generar la nota de crédito'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor'
                });
            });
        }
    });
}

function notaDebitoComprobante(comprobanteId) {
    Swal.fire({
        title: '¿Generar nota de débito?',
        text: 'Se generará una nota de débito asociada a este comprobante',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#17a2b8',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, generar',
        cancelButtonText: 'Cancelar',
        html: `
        <div class="text-left mt-3">
          <label class="form-label">Motivo:</label>
          <input id="motivo-debito" class="swal2-input" placeholder="Motivo de la nota de débito">
          <label class="form-label">Monto:</label>
          <input id="monto-debito" class="swal2-input" type="number" step="0.01" placeholder="Monto de la nota de débito">
        </div>
        `,
        preConfirm: () => {
            const motivo = document.getElementById('motivo-debito').value;
            const monto = document.getElementById('monto-debito').value;

            if (!motivo || !monto) {
                Swal.showValidationMessage('Debe completar todos los campos');
                return false;
            }

            return { motivo, monto };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Generando nota de débito...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar petición
            fetch(`/admin/comprobantes/${comprobanteId}/nota-debito`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify(result.value)
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Nota de débito generada!',
                        text: data.message,
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo generar la nota de débito'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor'
                });
            });
        }
    });
}

// Función alias para mantener compatibilidad
function generarComprobanteCuotaMejorado(cuotaId) {
    generarComprobanteCuota(cuotaId);
}

// Función para verificar configuración SUNAT
function verificarConfiguracionSunat() {
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('diagnosticoSunatModal'));
    modal.show();

    // Mostrar loading y ocultar contenido
    document.getElementById('diagnostico-loading').style.display = 'block';
    document.getElementById('diagnostico-content').style.display = 'none';

    // Realizar verificación
    fetch('/admin/sunat/diagnostico', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        // Ocultar loading y mostrar contenido
        document.getElementById('diagnostico-loading').style.display = 'none';
        document.getElementById('diagnostico-content').style.display = 'block';

        // Llenar el contenido
        llenarDiagnosticoSunat(data);
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('diagnostico-loading').innerHTML = `
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-triangle me-2"></i>
          Error al verificar la configuración SUNAT
        </div>
        `;
    });
}

function llenarDiagnosticoSunat(data) {
    // Estado General
    const estadoGeneral = document.getElementById('estado-general');
    estadoGeneral.innerHTML = `
        <div class="col-md-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-circle ${data.estado_general.activo ? 'text-success' : 'text-danger'} me-2"></i>
            <span>Estado: <strong>${data.estado_general.activo ? 'ACTIVO' : 'INACTIVO'}</strong></span>
        </div>
        </div>
        <div class="col-md-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-globe ${data.estado_general.ambiente === 'produccion' ? 'text-success' : 'text-warning'} me-2"></i>
            <span>Ambiente: <strong>${data.estado_general.ambiente?.toUpperCase() || 'NO CONFIGURADO'}</strong></span>
        </div>
        </div>
        <div class="col-md-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-id-card text-info me-2"></i>
            <span>RUC: <strong>${data.estado_general.ruc || 'NO CONFIGURADO'}</strong></span>
        </div>
        </div>
    `;

    // Configuración SUNAT
    const configuracionSunat = document.getElementById('configuracion-sunat');
    let configItems = '';

    data.configuracion.forEach(item => {
        const icon = item.estado ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
        configItems += `
        <div class="row mb-2">
            <div class="col-md-6">
            <i class="fas ${icon} me-2"></i>
            ${item.nombre}
            </div>
            <div class="col-md-6">
            <span class="badge bg-${item.estado ? 'success' : 'danger'}">
                ${item.valor || (item.estado ? 'Configurado' : 'No configurado')}
            </span>
            </div>
        </div>
        `;
    });
    configuracionSunat.innerHTML = configItems;

    // Certificados y Permisos
    const certificadosPermisos = document.getElementById('certificados-permisos');
    let certItems = '';

    data.certificados_permisos.forEach(item => {
        const icon = item.estado ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
        certItems += `
        <div class="row mb-2">
            <div class="col-md-6">
            <i class="fas ${icon} me-2"></i>
            ${item.nombre}
            </div>
            <div class="col-md-6">
            <span class="badge bg-${item.estado ? 'success' : 'danger'}">
                ${item.descripcion}
            </span>
            </div>
        </div>
        `;
    });
    certificadosPermisos.innerHTML = certItems;

    // Conectividad
    const conectividad = document.getElementById('conectividad');
    let connItems = '';

    data.conectividad.forEach(item => {
        const icon = item.estado ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';
        connItems += `
        <div class="row mb-2">
            <div class="col-md-6">
            <i class="fas ${icon} me-2"></i>
            ${item.nombre}
            </div>
            <div class="col-md-6">
            <span class="badge bg-${item.estado ? 'success' : 'danger'}">
                ${item.descripcion}
            </span>
            ${item.tiempo ? `<small class="text-muted d-block">${item.tiempo}ms</small>` : ''}
            </div>
        </div>
        `;
    });
    conectividad.innerHTML = connItems;

    // Resumen y Recomendaciones
    const resumenRecomendaciones = document.getElementById('resumen-recomendaciones');

    let alertClass = 'success';
    let alertIcon = 'fa-check-circle';

    if (data.resumen.criticos > 0) {
        alertClass = 'danger';
        alertIcon = 'fa-exclamation-triangle';
    } else if (data.resumen.advertencias > 0) {
        alertClass = 'warning';
        alertIcon = 'fa-exclamation-circle';
    }

    let recomendacionesHtml = '';
    if (data.recomendaciones.length > 0) {
        recomendacionesHtml = `
        <h6 class="mt-3">Recomendaciones:</h6>
        <ul class="list-group">
            ${data.recomendaciones.map(rec => `
            <li class="list-group-item d-flex align-items-start">
                <i class="fas fa-arrow-right text-primary me-2 mt-1"></i>
                <span>${rec}</span>
            </li>
            `).join('')}
        </ul>
        `;
    }

    resumenRecomendaciones.innerHTML = `
        <div class="alert alert-${alertClass}">
        <h6 class="alert-heading">
            <i class="fas ${alertIcon} me-2"></i>
            ${data.resumen.mensaje}
        </h6>
        <p class="mb-0">
            <strong>Estado:</strong> ${data.resumen.total_verificaciones} verificaciones realizadas -
            ${data.resumen.exitosas} exitosas, ${data.resumen.advertencias} advertencias, ${data.resumen.criticos} críticos
        </p>
        </div>
        ${recomendacionesHtml}
    `;
}

function volverAVerificar() {
    verificarConfiguracionSunat();
}

function abrirConfiguracionSunat() {
    window.open('/admin/configuracion-sunat', '_blank');
}

function imprimirEstadoCuenta(prestamoId) {
    // Abrir el PDF en una nueva ventana para imprimir
    window.open(`/admin/prestamos/${prestamoId}/estado-cuenta-pdf`, '_blank');
}

// Funciones para el sidebar de documentos
function toggleDocumentsSidebar() {
    const sidebar = document.getElementById('documentsSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar && overlay) {
        const isOpen = sidebar.classList.contains('open');
        
        if (isOpen) {
            // Cerrar sidebar
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
            overlay.style.display = 'none';
        } else {
            // Abrir sidebar
            sidebar.classList.add('open');
            overlay.classList.add('show');
            overlay.style.display = 'block';
        }
    }
}

// Función para cerrar el sidebar
function closeSidebar() {
    const sidebar = document.getElementById('documentsSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar && overlay) {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        overlay.style.display = 'none';
    }
}

// Inicialización cuando el documento está listo
document.addEventListener('DOMContentLoaded', function() {
    // Cerrar sidebar al hacer clic en el botón de cerrar
    const closeSidebarBtn = document.getElementById('closeSidebar');
    if (closeSidebarBtn) {
        closeSidebarBtn.addEventListener('click', closeSidebar);
    }
    
    // Cerrar sidebar al hacer clic en el overlay
    const overlay = document.getElementById('sidebarOverlay');
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    // Cerrar sidebar con tecla ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const sidebar = document.getElementById('documentsSidebar');
            if (sidebar && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        }
    });
});
</script>

