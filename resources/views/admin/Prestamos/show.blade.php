@extends('layouts.admin')

@section('title', 'Estado de Cuenta')

@php
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
@endphp

@section('content')
<div class="container-fluid">
    <div class="py-4">
        <!-- Información del Cliente y Préstamo -->
        <div class="account-card">
            <div class="row card-header d-flex">
                <div class="col-md-6">
                    <h3 style="margin-bottom:10px!important;">
                        <i class="fas fa-file-invoice-dollar me-2"></i> Prestamo N° {{ $prestamo->id }}
                    <span class="info-value">
                        @php
                            // Usar el estado CALCULADO para mostrar en la interfaz
                            // Esto refleja el estado real basado en cuotas y moras actuales
                            $estadoMostrado = $estadoCalculado ?? $prestamo->estado;
                            
                            // Ajustar para mostrar "CON CONVENIO" en mayúsculas si es el estado calculado
                            if ($estadoMostrado === 'Con Convenio') {
                                $estadoMostrado = 'CON CONVENIO';
                            }
                            
                            $estadoClass = match ($estadoMostrado) {
                                'Nueva Solicitud' => 'bg-nueva',
                                'Por Desembolsar' => 'bg-desembolsar',
                                'Vigente' => 'bg-vigente',
                                'Vigente con moras' => 'bg-vigente-moras',
                                'Moroso' => 'bg-moroso',
                                'CON CONVENIO' => 'bg-convenio',
                                'Liquidado' => 'bg-pagado',
                                'Pagado' => 'bg-pagado',
                                'Finalizado' => 'bg-pagado',
                                'Cancelado' => 'bg-cancelado',
                                'Desembolsado' => 'bg-vigente',
                                default => 'bg-vigente',
                            };
                            
                            // Mostrar indicador si hay diferencia entre BD y calculado
                            $hayDiferencia = isset($estadoBD) && $estadoBD !== $estadoCalculado;
                        @endphp
                <span class="badge-status {{ $estadoClass }}" 
                              @if($hayDiferencia) 
                                  title="Estado en BD: {{ $estadoBD }} | Estado calculado: {{ $estadoCalculado }}"
                                  style="cursor: help;"
                              @endif>
                            {{ $estadoMostrado }}
                            @if($hayDiferencia)
                                <i class="fas fa-exclamation-triangle text-warning ms-1" 
                                   title="El estado en BD ({{ $estadoBD }}) difiere del estado calculado ({{ $estadoCalculado }})"></i>
                            @endif
                        </span>
                    </span>
                    </h3>
                    <div class="d-flex align-items-center">
                        <span class="info-label me-2">Cliente:</span>
                        <span class="info-value" style="font-weight: bold; font-size: 1.1rem;">
                            {{ optional($prestamo->cliente->persona)->nombres }}
                            {{ optional($prestamo->cliente->persona)->ape_pat }}
                            {{ optional($prestamo->cliente->persona)->ape_mat ?? '' }}
                        </span>
                        <a href="#" class="small text-primary" style="text-decoration:underline;margin-left: 15px;" data-bs-toggle="modal" data-bs-target="#sidebarCliente" title="Ver Datos">
                          <i class="fas fa-eye me-1"></i> Ver Datos
                        </a>
                    </div>
                    <span class="info-label">DNI: </span>
                    <span class="info-value"style="font-weight: bold;">{{ optional($prestamo->cliente->persona)->documento ?? 'No disponible' }}</span>
                    <br>
                    <span class="info-label">Dirección:</span>
                    <span class="info-value" style="font-weight: bold;">
                        @php
                            $direccionObj = optional($prestamo->cliente->persona->direccion);
                            $direccion = $direccionObj ? trim(collect([
                                $direccionObj->direccion,
                                $direccionObj->numero
                            ])->filter()->implode(', ')) : null;
                            $referencia = $direccionObj && $direccionObj->referencia ? $direccionObj->referencia : null;
                        @endphp
                        {{ $direccion ?? 'No disponible' }}
                        @if($referencia)
                            <span class="badge bg-white text-dark ms-2" style="font-size: 8pt; border: 1px solid #e9ecef; border-radius: 8px;">
                                {{ $referencia }}
                            </span>
                        @endif
                    </span>

                      {{-- Información del Aval (se actualiza dinámicamente al asignar) --}}
                      <div id="assignedAvalInfo" class="mt-2">
                        @if(!empty($prestamo->aval_id) && $prestamo->aval)
                          @php $avalPersona = optional($prestamo->aval->persona); @endphp
                          <span class="info-label">Aval:</span>
                          <span class="info-value" id="assignedAvalName" style="font-weight:bold;">
                            <a href="{{ route('admin.personas.show', $avalPersona->id ?? '#') }}">{{ strtoupper(trim(($avalPersona->nombres ?? '') . ' ' . ($avalPersona->ape_pat ?? '') . ' ' . ($avalPersona->ape_mat ?? ''))) }}</a>
                          </span>
                        @else
                          <span id="assignedAvalName" style="display:none;"></span>
                        @endif
                      </div>
                </div>
                <div class="d-flex flex-wrap gap-2 col-md-6">
                    <!--button class="btn btn-outline-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#comprobanteModal"
                            data-prestamo-id="{{ $prestamo->id }}">
                        <i class="fas fa-file-invoice me-2"></i> Comprobante
                    </button-->


<button class="btn btn-outline-primary btn-sm"
                            onclick="openCronogramaPreview('{{ route('prestamos.estadoCuentaPreviewHtml', $prestamo->id) }}')">
                        <i class="fas fa-file-pdf me-2"></i> Cronograma
                    </button>

                    @php
                        // Preparar mensaje para copiar
                        $personaMsg = optional($prestamo->cliente)->persona;
                        $nombreClienteMsg = strtoupper(trim((optional($personaMsg)->nombres ?? '') . ' ' . (optional($personaMsg)->ape_pat ?? '') . ' ' . (optional($personaMsg)->ape_mat ?? '')));
                        $dniClienteMsg = optional($personaMsg)->documento ?? 'N/A';
                        $capitalMsg = number_format($prestamo->cantidad_solicitada, 2);
                        try {
                             $montoCuotaMsg = $cuotas->isNotEmpty() ? number_format($cuotas->first()->monto, 2) : '0.00';
                        } catch(\Exception $e) { $montoCuotaMsg = '0.00'; }
                        
                        $fondoMontoMsg = isset($fondo_provisional) && $fondo_provisional ? number_format($fondo_provisional->monto_fondo, 2) : '0.00';
                        $primerPagoMsg = $prestamo->fecha_primer_pago ? \Carbon\Carbon::parse($prestamo->fecha_primer_pago)->format('d/m/Y') : 'N/A';
                        
                        // Promotor
                        $promotorMsg = 'SIN ASIGNAR';
                        if ($prestamo->carterasAsesor->count() > 0) {
                            $asesor = $prestamo->carterasAsesor->first()->asesor;
                            $promotorMsg = $asesor ? ($asesor->codigo ?? ($asesor->name ?? 'N/A')) : 'N/A';
                        } elseif ($prestamo->carterasJcc->count() > 0) {
                            $jcc = $prestamo->carterasJcc->first()->jcc;
                            $promotorMsg = $jcc ? ($jcc->codigo ?? ($jcc->name ?? 'N/A')) : 'N/A';
                        }

                        // Autorizador
                        $autorizadorMsg = 'SIN ASIGNAR';
                         if ($prestamo->carterasAnalista->count() > 0) {
                            $analista = $prestamo->carterasAnalista->first()->analista;
                            $autorizadorMsg = $analista ? ($analista->codigo ?? ($analista->name ?? 'N/A')) : 'N/A';
                        }

                        // Sucursal
                        try {
                            $direcMsg = $prestamo->cliente->persona->direcciones()->with('sucursal.zonas')->first();
                            $sucursalNombreMsg = $direcMsg && $direcMsg->sucursal ? $direcMsg->sucursal->sucursal : 'N/A';
                            $zonasMsg = $direcMsg && $direcMsg->sucursal ? $direcMsg->sucursal->zonas : collect();
                            $zonaNombreMsg = $zonasMsg->count() > 0 ? $zonasMsg->first()->nombre : 'N/A';
                            $sucursalTextoMsg = "$zonaNombreMsg - $sucursalNombreMsg";
                        } catch(\Exception $e) { $sucursalTextoMsg = 'N/A'; }
                        
                        $moraMsg = '4'; // Valor fijo según contrato
                        $visitaMsg = '10'; // Valor fijo según requerimiento
                        
                        $fechaActualMsg = strtoupper(\Carbon\Carbon::now()->locale('es')->isoFormat('dddd D [DE] MMMM [DEL] YYYY'));

                        $mensajeCronograma = "📋 Cronograma Créditicio - Grupo Santiago\n\n" .
                            "👤 Cliente: $nombreClienteMsg\n" .
                            "🆔 DNI: $dniClienteMsg\n" .
                            "💰 Capital: S/ $capitalMsg\n" .
                            "💸 Monto Cuota: S/ $montoCuotaMsg\n" .
                            "📊 Fondo: S/ $fondoMontoMsg\n" .
                            "📅 Primer Pago: $primerPagoMsg\n" .
                            "👨‍💼 Promotor: $promotorMsg\n" .
                            "🧑‍💻 Autorizador: $autorizadorMsg\n" .
                            "🏢 Sucursal: $sucursalTextoMsg\n\n" .
                            "📋 Condiciones\n\n" .
                            "🗓 Plazo: $prestamo->plazo semanas\n" .
                            "⚠ Mora: S/ $moraMsg soles\n" .
                            "🚶 Visita Gestor: S/ $visitaMsg soles\n\n" .
                            "📝 Importante\n\n" .
                            "🚨 ¡Atención! Para validar y actualizar tus pagos, debes reportarlos siempre a este chat. Es el único medio oficial.\n\n" .
                            "$fechaActualMsg";
                    @endphp

                    {{-- Textarea oculto para copiar info --}}
                    <textarea id="cronogramaMessage" style="display:none;">{{ $mensajeCronograma }}</textarea>

                    {{-- Botón Liquidar Préstamo - Siempre visible --}}
                    <button class="btn btn-success btn-sm"
                            onclick="liquidarPrestamo({{ $prestamo->id }})"
                            title="Liquidar préstamo">
                        <i class="fas fa-check-circle me-2"></i> Liquidar Préstamo
                    </button>

                    @can('admin.prestamos.reset-payments')
                    <button class="btn btn-secondary btn-sm"
                            onclick="resetLoanPayments({{ $prestamo->id }})"
                            title="Resetear todos los pagos del préstamo">
                        <i class="fas fa-undo me-2"></i> Resetear Pagos
                    </button>
                    @endcan

                    {{-- Botón Verificar Moras --}}
                    @can('admin.prestamos.reset-payments')
                    <button class="btn btn-warning btn-sm"
                            onclick="verificarYGenerarMoras({{ $prestamo->id }})"
                            title="Verificar y generar moras faltantes">
                        <i class="fas fa-exclamation-triangle me-2"></i> Verificar Moras
                    </button>
                    @endcan

                    @php
                        // Si tiene convenio (cualquier estado), mostrar el más reciente
                        $convenio = $prestamo->convenios->sortByDesc('created_at')->first();
                        
                        // Verificar si está cancelado (Estado 3)
                        // Usamos el valor directo o el Enum si está disponible en la vista, 
                        // pero para seguridad usamos el valor entero 3 que corresponde a CANCELADO en ConvenioEstado
                        $esCancelado = $convenio && ($convenio->estado === \App\Enums\ConvenioEstado::CANCELADO || $convenio->estado->value === 3);
                        
                        $tieneConvenioActivo = $convenio && !$esCancelado;

                        $esMoroso = $prestamo->estado === 'Moroso';
                        $tieneMoras = $prestamo->estado === 'Vigente con moras';
                        $puedeCrearConvenio = ($esMoroso || $tieneMoras) && !$tieneConvenioActivo;
                    @endphp

                    @if($tieneConvenioActivo)
                        {{-- Si tiene convenio activo, mostrar botón para verlo --}}
                        <a href="{{ route('admin.convenios.show', $convenio->id) }}"
                          class="btn btn-info btn-sm"
                          target="_blank">
                            <i class="fas fa-handshake me-2"></i> Ver Convenio
                        </a>
                        <a href="{{ route('admin.convenios.estado-cuenta.preview', ['convenio' => $convenio->id]) }}"
                          class="btn btn-outline-info btn-sm"
                          target="_blank">
                            <i class="fas fa-file-pdf me-2"></i> Estado Cuenta Convenio
                        </a>
                    @elseif($puedeCrearConvenio)
                        {{-- Si es moroso y no tiene convenio activo (puede ser uno cancelado), mostrar botón para crear --}}
                        <div class="btn-group">
                            <a href="{{ route('admin.convenios.create', ['prestamo_id' => $prestamo->id]) }}"
                            class="btn btn-warning btn-sm"
                            target="_blank">
                                <i class="fas fa-handshake me-2"></i> Crear Convenio
                            </a>
                            @if($esCancelado)
                                <a href="{{ route('admin.convenios.show', $convenio->id) }}"
                                class="btn btn-outline-secondary btn-sm"
                                target="_blank" title="Ver convenio anulado anterior">
                                    <i class="fas fa-history"></i>
                                </a>
                            @endif
                        </div>
                    @else
                        {{-- Si no tiene convenio y no se puede crear --}}
                        <span class="btn btn-outline-secondary btn-sm disabled">
                            <i class="fas fa-handshake me-2"></i> Sin Convenio
                        </span>
                    @endif

                    @php
                        // Verificar si ya se emitieron comprobantes para este préstamo
                        $yaSeEmitieronComprobantes = DB::table('comprobantes')
                            ->where('prestamo_id', $prestamo->id)
                            ->where('estado', '!=', 'PENDIENTE')
                            ->exists();

                        // Solo se puede desactivar si no hay comprobantes emitidos
                        // Siempre se puede activar, independientemente de si hay comprobantes
                        $switchDeshabilitado = $yaSeEmitieronComprobantes && $prestamo->tiene_comprobante;
                    @endphp

                    <!-- Toggle para Comprobantes SUNAT por Préstamo -->
                    <div class="comprobantes-toggle-container">
                        <div class="toggle-wrapper">
                            <div class="toggle-label">
                                <i class="fas fa-receipt me-2"></i>
                                <span>Comprobantes SUNAT</span>
                            </div>

                            <div class="custom-switch-container">
                                <label class="custom-switch {{ $switchDeshabilitado ? 'disabled' : '' }}"
                                       title="{{ $switchDeshabilitado ? 'Ya se han emitido comprobantes - No se puede desactivar' : ($yaSeEmitieronComprobantes && !$prestamo->tiene_comprobante ? 'Se puede activar para generar comprobantes de cuotas futuras' : 'Activar/Desactivar emisión de comprobantes SUNAT') }}">
                                    <input type="checkbox"
                                           id="toggleComprobantes{{ $prestamo->id }}"
                                          {{ $prestamo->tiene_comprobante ? 'checked' : '' }}
                                           {{ $switchDeshabilitado ? 'disabled' : '' }}
                                           onchange="toggleComprobantePrestamo({{ $prestamo->id }}, this.checked)">
                                    <span class="slider">
                                        <span class="slider-text-off">OFF</span>
                                        <span class="slider-text-on">ON</span>
                                    </span>
                                </label>

                                <div class="status-indicator">
                                    @if($yaSeEmitieronComprobantes)
                                        <span class="status-badge emitidos">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Comprobantes Emitidos</span>
                                        </span>
                                    @endif

                                    @if($yaSeEmitieronComprobantes && !$prestamo->tiene_comprobante)
                                        <span class="status-badge activable" title="Puedes activar para generar comprobantes de pagos futuros">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Activable</span>
                                        </span>
                                    @endif
                                </div> 
                            </div>
                        </div>
                    </div>
                    {{-- Botón Asignar Aval (solo si no tiene aval asignado) --}}
                    @if(empty($prestamo->aval_id))
                      <div class="mt-2">
                        <a href="{{ route('admin.prestamos.mostrarAsignarAval', $prestamo->id) }}" class="btn btn-primary btn-sm">
                          <i class="fas fa-user-shield me-2"></i> Asignar Aval
                        </a>
                      </div>
                    @endif
                  </div>
                </div>

                <div class="card-body">
                <div class="row">
                    <div class="col-lg-9 col-md-9 vertical-divider">
                        <div class="prestamo-info">
                            <h5 class="section-title">
                                <i class="fas fa-money-bill-wave"></i> Información del Préstamo
                                @php
                                    // Obtener el usuario que registró el préstamo (primera operación o created_at)
                                    $usuarioRegistro = null;
                                    $fechaRegistro = null;

                                    // Intentar obtener de la primera operación de desembolso
                                    $operacionDesembolso = $prestamo->operaciones()
                                        ->where('tipo_operacion', 'Desembolso')
                                        ->with('user')
                                        ->orderBy('created_at', 'asc')
                                        ->first();

                                    if ($operacionDesembolso && $operacionDesembolso->user) {
                                        $usuarioRegistro = $operacionDesembolso->user;
                                        $fechaRegistro = $operacionDesembolso->created_at;
                                    } else {
                                        // Si no hay operación, usar la fecha de creación del préstamo
                                        $fechaRegistro = $prestamo->created_at;
                                    }
                                @endphp

                                @if($usuarioRegistro)
                                    <span class="usuario-reg ms-2" title="Registrado el {{ $fechaRegistro->format('d/m/Y H:i') }}">
                                        <i class="fas fa-user-plus me-1"></i>
                                        {{ $usuarioRegistro->codigo ?? $usuarioRegistro->name }}
                                    </span>
                                @elseif($fechaRegistro)
                                    <span class="usuario-reg ms-2" title="Registrado el {{ $fechaRegistro->format('d/m/Y H:i') }}">
                                        <i class="fas fa-calendar-plus me-1"></i>
                                        {{ $fechaRegistro->format('d/m/Y') }}
                                    </span>
                                @endif
                            </h5>
                            @php
                                $cantidadSolicitada = $prestamo->cantidad_solicitada;
                                $montoTotalCuotas = $cuotas->sum('monto');
                                $resta = $montoTotalCuotas - $cantidadSolicitada;
                            @endphp
                            <div class="row g-1 mb-2">
                                <div class="col-md-4">
                                    <div class="row g-1">
                                        <div class="col-6">
                                            <div class="info-card">
                                                <div class="info-label">Capital</div>
                                                <div class="info-value small d-flex justify-content-between">
                                                    <div class="info-value">S/. {{ number_format($prestamo->cantidad_solicitada, 2) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="info-card">
                                                <div class="info-label">Fondo provisional</div>
                                                <div class="info-value small d-flex justify-content-between">
                                                    <div class="info-value">
                                                        @if($fondo_provisional)
                                                            @if($fondo_provisional->estado === 'exonerado')
                                                                <span class="badge badge-secondary" 
                                                                      data-toggle="tooltip" 
                                                                      data-placement="top" 
                                                                      title="{{ $fondo_provisional->observaciones ?? 'Sin justificación registrada' }}"
                                                                      style="cursor: help;">
                                                                    <i class="fas fa-gift mr-1"></i> Exonerado
                                                                </span>
                                                            @else
                                                                S/. {{ number_format($fondo_provisional->monto_fondo, 2) }}
                                                            @endif
                                                        @else
                                                            <span class="text-muted">Sin fondo</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="row g-1">
                                        <div class="col-6">
                                            <div class="info-card">
                                                <div class="info-label">Total a Pagar</div>
                                                <div class="info-value small d-flex justify-content-between">
                                                    <div class="info-value">S/. {{ number_format($cuotas->sum('monto'), 2) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="info-card">
                                                <div class="info-label">Plazo</div>
                                                <div class="info-value small d-flex justify-content-between">
                                                    <div class="info-value"><i class="fas fa-list-ol me-1"></i> {{ $prestamo->plazo }} Semanas</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-card">
                                        <div class="info-label">Interés</div>
                                        <div class="info-value">S/. {{ number_format($resta, 2) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="row g-1">
                                        <div class="col-6">
                                            <div class="info-card">
                                                <div class="info-label">Día</div>
                                                <div class="info-value small d-flex justify-content-between">
                                                    @php
                                                        $fechaPrimerPago = $prestamo->fecha_primer_pago;
                                                        $diaSemana = $fechaPrimerPago ? \Carbon\Carbon::parse($fechaPrimerPago)->locale('es')->isoFormat('dddd') : 'N/A';
                                                    @endphp
                                                    <div><i class="far fa-calendar-check me-1"></i> {{ ucfirst($diaSemana) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="info-card">
                                                <div class="info-label">Cuenta Asignada
                                                    @role('Admin')
                                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1 ms-1" id="btnEditarCuenta" title="Editar cuenta" style="font-size: 8px; line-height: 1;">
                                                        <i class="fas fa-pencil-alt" style="font-size: 8px;"></i>
                                                    </button>
                                                    @endrole
                                                </div>
                                                <div class="info-value small d-flex justify-content-between">
                                                    <div id="cuentaDisplay"><i class="fas fa-credit-card me-1"></i> {{ $prestamo->cuenta->codigo ?? 'N/A' }}</div>
                                                    @role('Admin')
                                                    <div id="cuentaEdit" style="display: none; width: 100%;">
                                                        <select id="editCuenta" class="personal-select" style="width: 100%;" data-current="{{ $prestamo->cuenta_id }}">
                                                            <option value="">-- Seleccionar --</option>
                                                            @foreach(\App\Models\Cuenta::orderBy('codigo')->get() as $c)
                                                                <option value="{{ $c->id }}" {{ $c->id == $prestamo->cuenta_id ? 'selected' : '' }}>{{ $c->codigo }}</option>
                                                            @endforeach
                                                        </select>
                                                        <div class="d-flex gap-1 mt-1">
                                                            <button type="button" class="btn btn-sm btn-success py-0 px-1 flex-fill" id="btnGuardarCuenta" style="font-size: 10px;">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-secondary py-0 px-1 flex-fill" id="btnCancelarCuenta" style="font-size: 10px;">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    @endrole
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-card">
                                        <div class="info-label">Fechas</div>
                                        <div class="info-value small d-flex justify-content-between">
                                            <div><i class="far fa-calendar-alt me-1"></i> Inicio: {{ \Carbon\Carbon::parse($prestamo->fecha_primer_pago)->format('d-m-Y') }}</div>
                                            @if($prestamo->cuotas->isNotEmpty() && $prestamo->cuotas->sortByDesc('fecha_pago')->first())
                                                <div><i class="far fa-calendar-check me-1"></i> Fin: {{ \Carbon\Carbon::parse($prestamo->cuotas->sortByDesc('fecha_pago')->first()->fecha_pago)->format('d-m-Y') }}</div>
                                            @else
                                                <div><i class="far fa-calendar-check me-1"></i> Fin: <span class="text-muted">Pendiente</span></div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="row g-1">
                                        <div class="col-6">
                                            <div class="info-card">
                                                <div class="info-label">Fecha de desembolso
                                                  <div class="info-value small d-flex justify-content-between">
                                                      <!-- Toggle para Comprobantes SUNAT por Préstamo -->
                                                        @php
                                                            $desembolso = $prestamo->operaciones()->where('tipo_operacion', 'Desembolso')->first();
                                                        @endphp
                                                        
                                                        @if($desembolso)
                                                            {{ \Carbon\Carbon::parse($desembolso->fecha)->format('d-m-Y') }}
                                                        @else
                                                            Pendiente
                                                        @endif
                                                  </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="info-card">
                                                <div class="info-label">Tasa semanal</div>
                                                <div class="info-value small d-flex justify-content-between">
                                                    <div>
                                                        <i class="fas fa-percentage me-1"></i> 
                                                        @php
                                                            $tasaSemanal = 0;
                                                            if ($prestamo->plazo == 8) {
                                                                $tasaSemanal = 1.38; // 1.38% tasa fija para 8 semanas
                                                            } else {
                                                                // Calcular tasa semanal para otros plazos
                                                                $parametros = [
                                                                    12 => 1.0374899, // 103.74899% tasa total
                                                                    15 => 1.2505,    // 125.05% tasa total
                                                                    18 => 1.2729,    // 127.29% tasa total
                                                                    20 => 1.2844,    // 128.44% tasa total
                                                                ];
                                                                
                                                                if (isset($parametros[$prestamo->plazo])) {
                                                                    $tasaInteresTotal = $parametros[$prestamo->plazo];
                                                                    $tasaSemanal = round((pow(1 + $tasaInteresTotal, 1/$prestamo->plazo) - 1) * 100, 2);
                                                                }
                                                            }
                                                        @endphp
                                                        {{ number_format($tasaSemanal, 2) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-3 vertical-divider">
                        <div class="personal-info">
                            <h5 class="section-title">
                                <i class="fas fa-user-tie"></i> Personal Asignado
                                @role('Admin')
                                <button type="button" class="btn btn-sm btn-outline-secondary float-end py-0 px-1" id="btnEditarPersonal" title="Editar personal">
                                    <i class="fas fa-pencil-alt" style="font-size: 10px;"></i>
                                </button>
                                @endrole
                            </h5>

                            @php
                                $direccionPers = $prestamo->cliente->persona->direcciones()->with('sucursal.zonas')->first();
                                $zonasPers = $direccionPers && $direccionPers->sucursal ? $direccionPers->sucursal->zonas : collect();
                                $sucursalPers = $direccionPers && $direccionPers->sucursal ? $direccionPers->sucursal->sucursal : null;
                                $zonaActualId = $zonasPers->first()->id ?? '';
                                $sucursalActualId = $direccionPers->sucursal_id ?? '';
                                $analistaActualId = $prestamo->carterasAnalista->where('estado', 1)->first()?->analista_id ?? '';
                                $jccActualId = $prestamo->carterasJcc->where('estado', 1)->first()?->jcc_id ?? '';
                                $asesorActualId = $prestamo->carterasAsesor->where('estado', 1)->first()?->asesor_id ?? '';
                                $todasZonas = \App\Models\Zona::orderBy('nombre')->get();
                            @endphp

                            {{-- Modo lectura --}}
                            <div id="personalDisplay">
                                <div class="info-item">
                                    <span class="info-label">Analista:</span>
                                    <span class="info-value" id="displayAnalista">
                                        @php $analistaActivo = $prestamo->carterasAnalista->where('estado', 1)->first(); @endphp
                                        @if($analistaActivo)
                                            <span class="badge-assigned">{{ $analistaActivo->user->codigo ?? $analistaActivo->user->name ?? 'Sin asignar' }}</span>
                                        @else
                                            <span class="badge-assigned">Sin asignar</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">JCC:</span>
                                    <span class="info-value" id="displayJcc">
                                        @php $jccActivo = $prestamo->carterasJcc->where('estado', 1)->first(); @endphp
                                        @if($jccActivo)
                                            <span class="badge-assigned">{{ $jccActivo->user->codigo ?? $jccActivo->user->name ?? 'Sin asignar' }}</span>
                                        @else
                                            <span class="badge-assigned">Sin asignar</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Asesor:</span>
                                    <span class="info-value" id="displayAsesor">
                                        @php $asesorActivo = $prestamo->carterasAsesor->where('estado', 1)->first(); @endphp
                                        @if($asesorActivo)
                                            <span class="badge-assigned">{{ $asesorActivo->user->codigo ?? $asesorActivo->user->name ?? 'Sin asignar' }}</span>
                                        @else
                                            <span class="badge-assigned">Sin asignar</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Sucursal:</span>
                                    <span class="info-value">
                                        @if($sucursalPers)
                                            <span class="badge-assigned">{{ $sucursalPers }}</span>
                                        @else
                                            <span class="badge-unassigned">Sin asignar</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Zona:</span>
                                    <span class="info-value">
                                        @if($zonasPers->count() > 0)
                                            @foreach($zonasPers as $zona)
                                                <span class="badge-assigned">{{ $zona->nombre }}</span>
                                            @endforeach
                                        @else
                                            <span class="badge-unassigned">Sin asignar</span>
                                        @endif
                                    </span>
                                </div>
                            </div>

                            {{-- Modo edición (solo Admin) --}}
                            @role('Admin')
                            <div id="personalEdit" style="display: none;">
                                <div class="personal-edit-form">
                                    <div class="info-item">
                                        <span class="info-label">Analista:</span>
                                        <span class="info-value" style="flex: 1; text-align: right;">
                                            <select id="editAnalista" class="personal-select" data-current="{{ $analistaActualId }}">
                                                <option value="">-- Seleccionar --</option>
                                                @foreach(\App\Models\User::role('Analista')->where('status', 1)->orderBy('codigo')->get() as $u)
                                                    <option value="{{ $u->id }}" {{ $u->id == $analistaActualId ? 'selected' : '' }}>{{ $u->codigo ?? $u->name }}</option>
                                                @endforeach
                                            </select>
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">JCC:</span>
                                        <span class="info-value" style="flex: 1; text-align: right;">
                                            <select id="editJcc" class="personal-select" data-current="{{ $jccActualId }}">
                                                <option value="">-- Seleccionar --</option>
                                                @foreach(\App\Models\User::role('Jcc')->where('status', 1)->orderBy('codigo')->get() as $u)
                                                    <option value="{{ $u->id }}" {{ $u->id == $jccActualId ? 'selected' : '' }}>{{ $u->codigo ?? $u->name }}</option>
                                                @endforeach
                                            </select>
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Asesor:</span>
                                        <span class="info-value" style="flex: 1; text-align: right;">
                                            <select id="editAsesor" class="personal-select" data-current="{{ $asesorActualId }}">
                                                <option value="">-- Seleccionar --</option>
                                                @foreach(\App\Models\User::role('Asesor')->where('status', 1)->orderBy('codigo')->get() as $u)
                                                    <option value="{{ $u->id }}" {{ $u->id == $asesorActualId ? 'selected' : '' }}>{{ $u->codigo ?? $u->name }}</option>
                                                @endforeach
                                            </select>
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Zona:</span>
                                        <span class="info-value" style="flex: 1; text-align: right;">
                                            <select id="editZona" class="personal-select" data-current="{{ $zonaActualId }}">
                                                <option value="">-- Seleccionar --</option>
                                                @foreach($todasZonas as $z)
                                                    <option value="{{ $z->id }}" {{ $z->id == $zonaActualId ? 'selected' : '' }}>{{ $z->nombre }}</option>
                                                @endforeach
                                            </select>
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Sucursal:</span>
                                        <span class="info-value" style="flex: 1; text-align: right;">
                                            <select id="editSucursal" class="personal-select" data-current="{{ $sucursalActualId }}" data-initial="{{ $sucursalActualId }}">
                                                <option value="">-- Seleccionar zona primero --</option>
                                            </select>
                                        </span>
                                    </div>
                                    <div class="d-flex gap-2 mt-3">
                                        <button type="button" class="btn-personal-save flex-fill" id="btnGuardarPersonal">
                                            <i class="fas fa-check me-1"></i>Guardar
                                        </button>
                                        <button type="button" class="btn-personal-cancel flex-fill" id="btnCancelarPersonal">
                                            <i class="fas fa-times me-1"></i>Cancelar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endrole
                          </div>
                        </div>
                      </div>
                    </div>

        </div>

        <!-- Modal para previsualización del estado de cuenta -->
        <div class="modal fade" id="estadoCuentaModal" tabindex="-1" aria-labelledby="estadoCuentaModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" style="max-width: 970px;">
                <div class="modal-content border-0">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title" id="estadoCuentaModalLabel">
                            <i class="fas fa-file-invoice me-2 text-primary"></i>Previsualización Estado de Cuenta
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <iframe id="pdfPreview" src="" width="100%" height="600px"></iframe>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <!--a href="{{ route('admin.prestamos.estado-cuenta-download', $prestamo->id) }}" 
                        class="btn btn-success" target="_blank">
                            <i class="fas fa-download me-2"></i>Descargar PDF
                        </a-->
                        <button type="button" class="btn btn-primary" id="printBtn">
                            <i class="fas fa-print me-2"></i>Imprimir PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegación entre tablas -->
        <div class="card shadow-sm border-0 rounded-lg overflow-hidden">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs nav-fill" id="estadoCuentaTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="w-100 nav-link active px-4 py-3"
                               id="estado-cuenta-tab"
                               data-bs-toggle="tab"
                               data-bs-target="#estado-cuenta"
                               type="button"
                               role="tab"
                               aria-controls="estado-cuenta"
                               aria-selected="true">
                            <i class="fas fa-file-invoice-dollar me-2"></i> Estado de Cuenta
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="w-100 nav-link px-4 py-3"
                               id="transacciones-tab"
                               data-bs-toggle="tab"
                               data-bs-target="#transacciones"
                               type="button"
                               role="tab"
                               aria-controls="transacciones"
                               aria-selected="false">
                            <i class="fas fa-exchange-alt me-2"></i> Transacciones
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="w-100 nav-link px-4 py-3"
                               id="plan-pago-tab"
                               data-bs-toggle="tab"
                               data-bs-target="#plan-pago"
                               type="button"
                               role="tab"
                               aria-controls="plan-pago"
                               aria-selected="false">
                            <i class="fas fa-calendar-alt me-2"></i> Plan de Pago
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="w-100 nav-link px-4 py-3"
                               id="gestion-cobranza-tab"
                               data-bs-toggle="tab"
                               data-bs-target="#gestion-cobranza"
                               type="button"
                               role="tab"
                               aria-controls="gestion-cobranza"
                               aria-selected="false">
                            <i class="fas fa-tasks me-2"></i> Gestión de Cobranza
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body bg-white p-0">
                <div class="tab-content">
                    <!-- Estado de Cuenta -->
                    <div class="tab-pane fade show active p-3" id="estado-cuenta" role="tabpanel">
                        @include('admin.Prestamos.partials.estado-cuenta')
                    </div>

                    <!-- Transacciones -->
                    <div class="tab-pane fade p-3" id="transacciones" role="tabpanel">
                        @include('admin.Prestamos.partials.transacciones')
                    </div>

                    <!-- Plan de Pago -->
                    <div class="tab-pane fade p-3" id="plan-pago" role="tabpanel">
                        @include('admin.Prestamos.partials.plan-pago')
                    </div>

                    <!-- Gestión de Cobranza -->
                    <div class="tab-pane fade p-3" id="gestion-cobranza" role="tabpanel">
                        @include('admin.Prestamos.partials.gestion-cobranza')
                    </div>
                </div>
            </div>
        </div>

</div>

<!-- Modal para mostrar el recibo -->
<div class="modal fade" id="modalRecibo" tabindex="-1" role="dialog" aria-labelledby="modalReciboLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalReciboLabel">Comprobante de Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="reciboContent">
                <!-- El contenido del recibo se cargará aquí -->
            </div>
        </div>
    </div>
</div>


{{-- Incluir el sidebar del cliente --}}
@include('admin.Prestamos.partials.sidebar-cliente')
@stop

@section('css')
<style>
/* Right Modal Styles - Enforcing styles */
.modal.right .modal-dialog {
    position: fixed;
    margin: auto;
    width: 500px !important;
    height: 100%;
    -webkit-transform: translate3d(0%, 0, 0);
    -ms-transform: translate3d(0%, 0, 0);
    -o-transform: translate3d(0%, 0, 0);
    transform: translate3d(0%, 0, 0);
}

.modal.right .modal-content {
    height: 100%;
    overflow-y: auto;
    border-radius: 0 !important;
    margin-top: 69px;
}

.modal.right .modal-body {
    padding: 15px 15px 80px;
}

/* Right padding adjustment */
.modal-dialog-slideout {
    margin-right: 0;
    margin-left: auto;
}

/* Animation */
.modal.right.fade .modal-dialog {
    right: -500px; /* Start off-screen */
    -webkit-transition: opacity 0.3s linear, right 0.3s ease-out;
    -moz-transition: opacity 0.3s linear, right 0.3s ease-out;
    -o-transition: opacity 0.3s linear, right 0.3s ease-out;
    transition: opacity 0.3s linear, right 0.3s ease-out;
}

.modal.right.fade.show .modal-dialog {
    right: 0; /* Slide in */
}

/* Ensure backdrop covers correctly */
.modal-backdrop {
    z-index: 1040 !important;
}
.modal.right {
    z-index: 1050 !important;
}

/* Estilo para filas de moras pagadas */
.bg-warning-light {
    background-color: #fff3cd !important;
    border-left: 4px solid #ffc107 !important;
}
.bg-warning-light:hover {
    background-color: #ffeaa7 !important;
}
/* Estado de Cuenta Card */
.account-card {
    border-radius: var(--radius-lg);
    background: var(--bg-primary);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
    margin: 0 auto 1.5rem;
    border: 1px solid var(--border-primary);
}
.account-card .card-header {
    background: var(--bg-secondary);
    border-bottom: 1px solid var(--border-primary);
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.account-card .card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}
.account-card .card-header .btn {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    border-radius: var(--radius-md);
    transition: var(--transition);
}
.account-card .btn-outline-primary {
    border-color: var(--primary);
    color: var(--primary);
}
.account-card .btn-outline-primary:hover {
    background-color: var(--primary);
    color: #ffffff;
}
.account-card .dropdown-menu {
    border-radius: var(--radius-md);
    border: 1px solid var(--border-primary);
    box-shadow: var(--shadow-md);
    padding: 0.5rem 0;
    background: var(--bg-primary);
}
.account-card .dropdown-item {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    color: var(--text-primary);
}
.account-card .dropdown-item:hover {
    background-color: var(--bg-tertiary);
}
.account-card .card-body {
    padding: 1.5rem;
    background: var(--bg-primary);
    color: var(--text-primary);
}
.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
}
.btnver:hover {
    background-color: #eea509ff;
    color: #fff;
}
.section-title i {
    margin-right: 0.5rem;
    color: var(--primary);
}
.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}
.info-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-bottom: 0;
}
.info-value {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
}
.badge-assigned {
    background-color: var(--bg-secondary);
    color: var(--text-secondary);
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 600;
}
.badge-unassigned {
    background-color: var(--bg-tertiary);
    color: var(--text-primary);
    font-weight: 500;
}
.personal-edit-form {
    background: var(--bg-secondary);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-md);
    padding: 0.85rem;
}
.personal-select {
    width: 100%;
    max-width: 160px;
    margin-left: auto;
    padding: 0.3rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-primary);
    background-color: var(--bg-primary);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-sm);
    appearance: auto;
    cursor: pointer;
    transition: var(--transition);
}
.personal-select:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 2px rgba(var(--primary-rgb, 59, 130, 246), 0.15);
}
.btn-personal-save {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.35rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #fff;
    background-color: var(--primary);
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: var(--transition);
}
.btn-personal-save:hover {
    opacity: 0.9;
}
.btn-personal-cancel {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.35rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-secondary);
    background-color: var(--bg-tertiary);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: var(--transition);
}
.btn-personal-cancel:hover {
    background-color: var(--bg-secondary);
}
.info-card {
    background: var(--bg-secondary);
    padding: 0.75rem;
    border-radius: var(--radius-md);
    margin-bottom: 0.75rem;
    width: 100%;
    border: 1px solid var(--border-primary);
    transition: var(--transition);
}
.info-card .info-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-bottom: 0.25rem;
}
.info-card .info-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
}
.info-card .info-value.small {
    font-size: 0.875rem;
    font-weight: 400;
}
.vertical-divider {
    border-left: 1px solid #e9ecef;
}
@media (max-width: 991px) {
    .vertical-divider {
        border-left: none;
        border-top: 1px solid var(--border-primary);
        padding-top: 1.5rem;
        margin-top: 1.5rem;
    }
    .account-card .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    .account-card .card-header .d-flex {
        width: 100%;
        justify-content: flex-start;
    }
}
@media (max-width: 576px) {
    .account-card {
        margin: 10px;
    }
    .account-card .card-body {
        padding: 1rem;
    }
    .info-card .info-value {
        font-size: 0.875rem;
    }
}
    :root {
        --primary-color: #435ebe;
        --primary-light: #edf2ff;
        --secondary-color: #4fbe87;
        --secondary-light: #e6fff0;
        --warning-color: #ff9f43;
        --danger-color: #eb5757;
        --info-color: #56c2e6;
        --dark-color: #363c47;
        --light-color: #f9f9f9;
        --border-color: #e2e8f0;
    }
    /* Estilos generales */
    body {
        font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        color: var(--text-primary);
        background-color: var(--bg-secondary);
    }
    .info-item:hover {
        background-color: var(--bg-tertiary);
    }
    .info-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    /* Navegación por pestañas */
    .nav-tabs {
        border-bottom: none;
    }
    .nav-tabs .nav-link {
        border: none;
        color: var(--text-secondary);
        font-weight: 600;
        padding: 1rem 1.5rem;
        border-radius: 0;
        transition: var(--transition);
    }
    .nav-tabs .nav-link.active {
        color: var(--primary);
        background-color: var(--bg-primary);
        border-bottom: 3px solid var(--primary);
    }
    .nav-tabs .nav-link:hover:not(.active) {
        color: var(--text-primary);
        background-color: var(--bg-tertiary);
        border-bottom: 3px solid var(--border-primary);
    }
    /* Tablas mejoradas */
    .table {
        margin-bottom: 0;
    }
    .table thead th {
        background-color: var(--bg-secondary);
        font-weight: 600;
        border-top: none;
        color: var(--text-secondary);
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
    .table-striped tbody tr:nth-of-type(odd) {
        background-color: var(--bg-tertiary);
    }
    .table-hover tbody tr:hover {
        background-color: var(--bg-tertiary);
    }
    /* Estados con badges */
    .badge {
        font-weight: 600;
        letter-spacing: 0.5px;
        padding: 0.1em 0.3em;
        border-radius: 50rem;
    }
    /* Barras de progreso */
    .progress {
        height: 8px;
        background-color: var(--border-primary);
        border-radius: 50rem;
        overflow: hidden;
    }
    .progress-bar {
        transition: width 0.6s ease;
    }
    /* Collapse para detalles */
    .collapse .card-body {
        padding: 1.25rem;
    }
    /* Botones */
    .btn {
        font-weight: 600;
        border-radius: var(--radius-md);
        padding: 0.375rem 1rem;
        transition: var(--transition);
    }
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    .btn-outline-primary {
        color: var(--primary);
        border-color: var(--primary);
    }
    .btn-outline-primary:hover {
        background-color: var(--primary);
        color: #fff;
    }
    /* Dropdown personalizado */
    .dropdown-menu {
        border: 1px solid var(--border-primary);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-lg);
        padding: 0.5rem 0;
        background: var(--bg-primary);
    }
    .dropdown-item {
        padding: 0.5rem 1rem;
        font-weight: 500;
    }
    .dropdown-item:hover {
        background-color: var(--bg-tertiary);
    }
    .dropdown-item i {
        width: 1.25rem;
        text-align: center;
    }
    /* Modal personalizado */
    .modal-content {
        border: 1px solid var(--border-primary);
        border-radius: var(--radius-lg);
        overflow: hidden;
        background: var(--bg-primary);
    }
    .modal-header, .modal-footer {
        border: none;
    }
    .modal-dialog.modal-xl {
        max-width: 1140px;
    }
    #pdfPreview {
        width: 100%;
        height: 80vh;
        border: none;
    }
    /* Tarjeta vacía con mensaje centrado */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        color: #6c757d;
    }
    /* Estilos para el modo liquidación */
    .list-group-item {
        border: none;
        border-bottom: 1px solid #f0f0f0;
        padding: 0.75rem 1rem;
    }
    .list-group-item:last-child {
        border-bottom: none;
    }
    .form-check-label {
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .form-check-input:checked + .form-check-label {
        color: var(--primary-color);
        font-weight: 600;
    }
    .bg-gradient-amber-to-green {
      background: linear-gradient(to right, #ffc107, #28a745);
      color: #fff; /* texto oscuro para mejor legibilidad */
    }
    /* Responsive */
    @media (max-width: 767.98px) {
        .nav-tabs .nav-link {
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
        }
        
        .table thead th {
            font-size: 0.7rem;
        }
        
        .table td {
            font-size: 0.875rem;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        .info-item {
            flex-direction: column;
        }
        
        .info-value {
            margin-top: 0.25rem;
        }
    }
    .usuario-reg {
        padding: 3px 8px;
        border-radius: 12px;
        background-color: #dfdfdfff;
        color: #6e6e6eff;
        margin-left: 5px;
        font-size: 10pt;
    }

    /* Estilos para el toggle de comprobantes SUNAT - Versión delicada */
    .comprobantes-toggle-container {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 8px;
        background: rgba(248, 249, 250, 0.8);
        border-radius: 6px;
        border: 1px solid #e9ecef;
        transition: all 0.2s ease;
        font-size: 13px;
    }

    .comprobantes-toggle-container:hover {
        background: rgba(248, 249, 250, 1);
        border-color: #dee2e6;
    }

    .toggle-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .toggle-label {
        display: flex;
        align-items: center;
        font-weight: 500;
        color: #6c757d;
        font-size: 12px;
        white-space: nowrap;
    }

    .toggle-label i {
        color: #007bff;
        font-size: 12px;
        margin-right: 4px;
    }

    .custom-switch-container {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Switch personalizado - Más pequeño y delicado */
    .custom-switch {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 22px;
        cursor: pointer;
    }

    .custom-switch.disabled {
        cursor: not-allowed;
        opacity: 0.5;
    }

    .custom-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: #dc3545;
        transition: all 0.3s ease;
        border-radius: 22px;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 2px;
        bottom: 2px;
        background: white;
        transition: all 0.3s ease;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    .slider-text-off,
    .slider-text-on {
        position: absolute;
        font-size: 8px;
        font-weight: 600;
        color: white;
        top: 50%;
        transform: translateY(-50%);
        transition: opacity 0.2s ease;
    }

    .slider-text-off {
        right: 4px;
        opacity: 1;
    }

    .slider-text-on {
        left: 4px;
        opacity: 0;
    }

    /* Estado activado */
    input:checked + .slider {
        background: #28a745;
    }

    input:checked + .slider:before {
        transform: translateX(22px);
    }

    input:checked + .slider .slider-text-off {
        opacity: 0;
    }

    input:checked + .slider .slider-text-on {
        opacity: 1;
    }

    /* Estado deshabilitado */
    .custom-switch.disabled .slider {
        background: #6c757d;
    }

    /* Indicador de estado - Más pequeño */
    .status-indicator {
        display: flex;
        align-items: center;
    }

    .status-badge {
        display: flex;
        align-items: center;
        gap: 3px;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 500;
        background: #28a745;
        color: white;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .status-badge i {
        font-size: 10px;
    }

    .status-badge.activable {
        background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
        animation: pulseInfo 2s infinite;
    }

    /* Animación de pulso para el badge activable */
    @keyframes pulseInfo {
        0%, 100% {
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
        }
        50% {
            box-shadow: 0 4px 16px rgba(23, 162, 184, 0.5);
        }
    }

    /* Responsivo */
    @media (max-width: 768px) {
        .comprobantes-toggle-container {
            font-size: 12px;
            padding: 4px 6px;
        }

        .toggle-label {
            font-size: 11px;
        }

        .custom-switch {
            width: 40px;
            height: 20px;
        }

        .slider:before {
            height: 16px;
            width: 16px;
        }

        input:checked + .slider:before {
            transform: translateX(20px);
        }

        .status-badge {
            font-size: 9px;
            padding: 1px 4px;
        }
    }
    @media (max-width: 576px) {
        .badge {
            font-size: 0.7rem;
        }
        
        .btn-group .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        .modal-footer .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    }

    /* Estilos para badges de estado del préstamo */
    .badge-status {
        display: inline-block;
        padding: 0.35em 0.65em;
        font-size: 0.875em;
        font-weight: 600;
        line-height: 1;
        color: #fff;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
    }

    .bg-nueva {
        background-color: #6c757d !important; /* Gris para nueva solicitud */
    }

    .bg-desembolsar {
        background-color: #ffc107 !important; /* Amarillo para por desembolsar */
    }

    .bg-vigente {
        background-color: #28a745 !important; /* Verde para vigente */
    }

    .bg-vigente-moras {
        background: linear-gradient(135deg, #28a745 0%, #ffc107 100%) !important; /* Verde-amarillo para vigente con moras */
        box-shadow: 0 2px 6px rgba(255, 193, 7, 0.3);
    }

    .bg-moroso {
        background-color: #dc3545 !important; /* Rojo para moroso */
    }

    .bg-convenio {
        background-color: #17a2b8 !important; /* Azul para con convenio */
    }

    .bg-pagado {
        background-color: #20c997 !important; /* Verde turquesa para pagado/liquidado/finalizado */
    }

    .bg-cancelado {
        background-color: #6c757d !important; /* Gris para cancelado */
    }
</style>
@stop
@section('js')
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 5 y Popper.js -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
<!-- SweetAlert2 para notificaciones mejoradas -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // FUNCIÓN DE REGULARIZACIÓN ELIMINADA - Sistema ahora es 100% automático
  // El sistema actualiza automáticamente todos los estados sin intervención manual
  // Definir la función loadPreview como global
  window.loadPreview = function(url) {
    console.log("Cargando preview desde URL:", url);

    // Mostrar indicador de carga
    const iframe = document.getElementById('pdfPreview');
    iframe.src = '';

    Swal.fire({
      title: 'Cargando documento',
      text: 'El estado de cuenta se está generando...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    // Configurar evento para cerrar el loading cuando el iframe cargue
    iframe.onload = function() {
      Swal.close();
    };

    // Cargar el contenido
    iframe.src = url;
  };

  // Función para abrir cronograma en ventana con controles
window.openCronogramaPreview = function(url) {
    console.log("Abriendo cronograma en ventana:", url);

    // Mostrar indicador de carga breve
    Swal.fire({
      title: 'Abriendo cronograma',
      text: 'Preparando previsualización...',
      allowOutsideClick: false,
      timer: 800,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    // Dimensiones de la ventana
    const width = 970;
    const height = 700;
    const left = (screen.width - width) / 2;
    const top = (screen.height - height) / 2;

    // Abrir ventana con controles
    const previewWindow = window.open(
      '',
      'CronogramaPreview',
      `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes,status=yes,toolbar=no,menubar=no`
    );

    if (!previewWindow) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'No se pudo abrir la ventana. Por favor, permite las ventanas emergentes.'
      });
      return;
    }

    // Escribir contenido HTML con iframe y controles
    const htmlContent = `
      <!DOCTYPE html>
      <html lang="es">
      <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Previsualización Cronograma</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"><\/script>
        <style>
          body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
          }
          .toolbar {
            background: #fff;
            border-bottom: 2px solid #dee2e6;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
          }
          .toolbar h5 {
            margin: 0;
            color: #333;
            font-size: 16px;
          }
          .toolbar .btn-group {
            gap: 8px;
          }
          #previewFrame {
            width: 100%;
            height: calc(100vh - 60px);
            border: none;
            background: white;
          }
        </style>
      </head>
      <body>
        <div class="toolbar">
          <h5><i class="fas fa-file-invoice me-2"></i>Previsualización Cronograma</h5>
          <div class="btn-group">
            <button class="btn btn-info btn-sm text-white" onclick="copiarInfo()">
              <i class="fas fa-copy me-1"></i> Copiar Info
            </button>
            <button class="btn btn-primary btn-sm" onclick="imprimirPDF()">
              <i class="fas fa-print me-1"></i> Imprimir PDF
            </button>
            <button class="btn btn-success btn-sm" onclick="descargarImagen()">
              <i class="fas fa-image me-1"></i> Descargar Imagen
            </button>
            <button class="btn btn-secondary btn-sm" onclick="window.close()">
              <i class="fas fa-times me-1"></i> Cerrar
            </button>
          </div>
        </div>
        <iframe id="previewFrame" src="` + url + `"></iframe>

        <script>
          function copiarInfo() {
             try {
                 // Intentar obtener el mensaje desde la ventana padre (opener)
                 if (window.opener && window.opener.document) {
                     const mensajeElement = window.opener.document.getElementById('cronogramaMessage');
                     if (mensajeElement) {
                         const mensaje = mensajeElement.value;
                         copiarAlPortapapeles(mensaje);
                     } else {
                         alert('No se encontró el mensaje original.');
                     }
                 } else {
                     alert('No se detectó la ventana principal.');
                 }
             } catch (e) {
                 console.error('Error al acceder a ventana padre:', e);
                 alert('Error de acceso a la información.');
             }
          }

          function copiarAlPortapapeles(text) {
             // Método moderno
             if (navigator.clipboard) {
                 navigator.clipboard.writeText(text).then(function() {
                     alert('¡Información copiada al portapapeles!');
                 }, function(err) {
                     console.error('Fallo navigator.clipboard:', err);
                     copiarFallback(text);
                 });
             } else {
                 copiarFallback(text);
             }
          }

          function copiarFallback(text) {
             var textArea = document.createElement("textarea");
             textArea.value = text;
             textArea.style.position = "fixed";  // Evitar scroll
             document.body.appendChild(textArea);
             textArea.focus();
             textArea.select();
             try {
                 var successful = document.execCommand('copy');
                 var msg = successful ? '¡Información copiada al portapapeles!' : 'No se pudo copiar';
                 alert(msg);
             } catch (err) {
                 console.error('Fallback error:', err);
                 alert('Error al intentar copiar.');
             }
             document.body.removeChild(textArea);
          }

          function imprimirPDF() {
            const iframe = document.getElementById('previewFrame');
            iframe.contentWindow.print();
          }

          function descargarImagen() {
            const iframe = document.getElementById('previewFrame');
            const btn = event.target.closest('button');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Capturando...';
            btn.disabled = true;

            // Esperar a que el iframe esté completamente cargado
            setTimeout(() => {
              html2canvas(iframe.contentDocument.body, {
                scale: 2,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff'
              }).then(canvas => {
                canvas.toBlob(blob => {
                  const imgUrl = URL.createObjectURL(blob);
                  const a = document.createElement('a');
                  a.href = imgUrl;
                  a.download = 'cronograma-' + new Date().getTime() + '.png';
                  document.body.appendChild(a);
                  a.click();
                  document.body.removeChild(a);
                  URL.revokeObjectURL(imgUrl);

                  btn.innerHTML = '<i class="fas fa-image me-1"></i> Descargar Imagen';
                  btn.disabled = false;
                });
              }).catch(err => {
                console.error('Error al capturar imagen:', err);
                alert('Error al capturar la imagen. Por favor intenta nuevamente.');
                btn.innerHTML = '<i class="fas fa-image me-1"></i> Descargar Imagen';
                btn.disabled = false;
              });
            }, 1000);
          }
        <\/script>
      </body>
      </html>
    `;

    previewWindow.document.write(htmlContent);
    previewWindow.document.close();
    previewWindow.focus();
  };

  // Función global para imprimir PDF
  window.printPDF = function() {
    const iframe = document.getElementById('pdfPreview');
    if (iframe.src) {
      iframe.contentWindow.print();
    }
  };

  // Función global para liquidar préstamo
  window.liquidarPrestamo = function(prestamoId) {
      const url = `/admin/prestamos/${prestamoId}/liquidacion-ventana`;
      window.location.href = url; // Abrir en la misma pestaña
  };

  $(document).ready(function() {
    console.log('Documento listo');

    // ============================================
    // Edición inline de Cuenta Asignada
    // ============================================
    $('#btnEditarCuenta').on('click', function() {
      $('#cuentaDisplay').hide();
      $('#cuentaEdit').show();
    });

    $('#btnCancelarCuenta').on('click', function() {
      $('#editCuenta').val($('#editCuenta').data('current'));
      $('#cuentaEdit').hide();
      $('#cuentaDisplay').show();
    });

    $('#btnGuardarCuenta').on('click', function() {
      var $btn = $(this);
      var newVal = $('#editCuenta').val();
      var currentVal = $('#editCuenta').data('current');

      if (!newVal || newVal == currentVal) {
        $('#cuentaEdit').hide();
        $('#cuentaDisplay').show();
        return;
      }

      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

      $.ajax({
        url: '{{ route("admin.prestamos.actualizar-cuenta", $prestamo->id) }}',
        method: 'POST',
        data: { _token: '{{ csrf_token() }}', cuenta_id: newVal },
        success: function(resp) {
          $('#cuentaDisplay').html('<i class="fas fa-credit-card me-1"></i> ' + resp.codigo);
          $('#editCuenta').data('current', newVal);
        },
        error: function() {
          alert('Error al actualizar la cuenta');
          $('#editCuenta').val(currentVal);
        },
        complete: function() {
          $btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
          $('#cuentaEdit').hide();
          $('#cuentaDisplay').show();
        }
      });
    });

    // ============================================
    // Edición inline de Personal Asignado
    // ============================================
    function cargarSucursalesPorZona(zonaId, selectedSucursalId) {
      var $sucursalSelect = $('#editSucursal');
      if (!zonaId) {
        $sucursalSelect.html('<option value="">-- Seleccionar zona primero --</option>');
        return;
      }
      $sucursalSelect.html('<option value="">Cargando...</option>').prop('disabled', true);
      fetch('/zona/' + zonaId + '/sucursales')
        .then(function(resp) { return resp.json(); })
        .then(function(data) {
          var options = '<option value="">-- Seleccionar --</option>';
          data.forEach(function(s) {
            var sel = (s.id == selectedSucursalId) ? ' selected' : '';
            options += '<option value="' + s.id + '"' + sel + '>' + s.sucursal + '</option>';
          });
          $sucursalSelect.html(options).prop('disabled', false);
        })
        .catch(function() {
          $sucursalSelect.html('<option value="">Error al cargar</option>').prop('disabled', false);
        });
    }

    $('#editZona').on('change', function() {
      cargarSucursalesPorZona($(this).val(), null);
    });

    $('#btnEditarPersonal').on('click', function() {
      $('#personalDisplay').hide();
      $('#personalEdit').show();
      var zonaId = $('#editZona').val();
      if (zonaId) {
        cargarSucursalesPorZona(zonaId, $('#editSucursal').data('initial'));
      }
    });

    $('#btnCancelarPersonal').on('click', function() {
      $('#personalEdit').hide();
      $('#personalDisplay').show();
    });

    $('#btnGuardarPersonal').on('click', function() {
      var $btn = $(this);
      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Guardando...');

      var cambios = [];
      var tipos = {analista: '#editAnalista', jcc: '#editJcc', asesor: '#editAsesor'};

      $.each(tipos, function(tipo, selectId) {
        var $select = $(selectId);
        var newVal = $select.val();
        var currentVal = $select.data('current');
        if (newVal && newVal != currentVal) {
          cambios.push({ tipo: tipo, user_id: newVal });
        }
      });

      // Verificar cambios de zona/sucursal
      var newZona = $('#editZona').val();
      var currentZona = $('#editZona').data('current');
      var newSucursal = $('#editSucursal').val();
      var currentSucursal = $('#editSucursal').data('current');
      var zonaSucursalChanged = (newZona && newSucursal) && (newZona != currentZona || newSucursal != currentSucursal);

      if (cambios.length === 0 && !zonaSucursalChanged) {
        $('#personalEdit').hide();
        $('#personalDisplay').show();
        $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Guardar');
        return;
      }

      var totalOperaciones = cambios.length + (zonaSucursalChanged ? 1 : 0);
      var completados = 0;
      var errores = [];

      function verificarFin() {
        completados++;
        if (completados === totalOperaciones) {
          $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Guardar');
          $('#personalEdit').hide();
          $('#personalDisplay').show();
          if (errores.length > 0) {
            alert('Error al actualizar: ' + errores.join(', '));
          }
        }
      }

      // Guardar cambios de personal
      cambios.forEach(function(cambio) {
        $.ajax({
          url: '{{ route("admin.prestamos.actualizar-personal", $prestamo->id) }}',
          method: 'POST',
          data: { _token: '{{ csrf_token() }}', tipo: cambio.tipo, user_id: cambio.user_id },
          success: function(resp) {
            var displayId = '#display' + cambio.tipo.charAt(0).toUpperCase() + cambio.tipo.slice(1);
            $(displayId).html('<span class="badge-assigned">' + resp.codigo + '</span>');
            var $select = $('#edit' + cambio.tipo.charAt(0).toUpperCase() + cambio.tipo.slice(1));
            $select.data('current', cambio.user_id);
          },
          error: function() { errores.push(cambio.tipo); },
          complete: verificarFin
        });
      });

      // Guardar cambios de zona/sucursal
      if (zonaSucursalChanged) {
        $.ajax({
          url: '{{ route("admin.prestamos.actualizar-zona-sucursal", $prestamo->id) }}',
          method: 'POST',
          data: { _token: '{{ csrf_token() }}', zona_id: newZona, sucursal_id: newSucursal },
          success: function(resp) {
            $('#personalDisplay .info-item').each(function() {
              var label = $(this).find('.info-label').text().trim();
              if (label === 'Zona:') {
                $(this).find('.info-value').html('<span class="badge-assigned">' + resp.zona_nombre + '</span>');
              }
              if (label === 'Sucursal:') {
                $(this).find('.info-value').html('<span class="badge-assigned">' + resp.sucursal_nombre + '</span>');
              }
            });
            $('#editZona').data('current', newZona);
            $('#editSucursal').data('current', newSucursal);
            $('#editSucursal').data('initial', newSucursal);
          },
          error: function() { errores.push('zona/sucursal'); },
          complete: verificarFin
        });
      }
    });

    // Configurar botón de imprimir
    $('#printBtn').off('click').on('click', function() {
      console.log('Botón de imprimir clickeado');
      printPDF();
    });
    
    // Manejar clicks en botones de pago
    $(document).off('click', '.btn-registrar-pago').on('click', '.btn-registrar-pago', function(e) {
      e.preventDefault();
      e.stopPropagation();

      console.log('Botón de pago clickeado');

      const cuotaId = $(this).data('cuota-id');
      const prestamoId = {{ $prestamo->id }};

      if (cuotaId && prestamoId) {
        const url = `/admin/registrarpago/create/${prestamoId}?cuota_id=${cuotaId}`;
        console.log('Redirigiendo a:', url);
        window.location.href = url;
      } else {
        console.error('Datos faltantes:', { cuotaId, prestamoId });
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Faltan datos para procesar el pago'
        });
      }
    });

    // Manejar clicks en botones de emitir comprobante
    $(document).off('click', '.btn-emitir-comprobante').on('click', '.btn-emitir-comprobante', function(e) {
      e.preventDefault();
      e.stopPropagation();

      console.log('Botón de emitir comprobante clickeado');

      const cuotaId = $(this).data('cuota-id');
      const cuotaNumero = $(this).data('cuota-numero');
      const cuotaMonto = $(this).data('cuota-monto');

      // Mostrar modal de confirmación con opción de tipo de comprobante
      Swal.fire({
        title: 'Emitir Comprobante Electrónico',
        html: `
          <div class="text-start">
            <p><strong>Cuota #${cuotaNumero}</strong></p>
            <p><strong>Monto:</strong> S/. ${parseFloat(cuotaMonto).toFixed(2)}</p>
            <hr>
            <div class="mb-3">
              <label for="tipo_comprobante_modal" class="form-label"><strong>Tipo de Comprobante:</strong></label>
              <select id="tipo_comprobante_modal" class="form-select">
                <option value="03">Boleta de Venta</option>
                <option value="01">Factura</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="serie_comprobante_modal" class="form-label"><strong>Serie:</strong></label>
              <input type="text" id="serie_comprobante_modal" class="form-control" value="B001" maxlength="4">
            </div>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Emitir Comprobante',
        cancelButtonText: 'Cancelar',
        focusConfirm: false,
        didOpen: () => {
          // Cambiar serie según tipo de comprobante
          const tipoSelect = document.getElementById('tipo_comprobante_modal');
          const serieInput = document.getElementById('serie_comprobante_modal');

          tipoSelect.addEventListener('change', function() {
            if (this.value === '01') {
              serieInput.value = 'F001';
            } else {
              serieInput.value = 'B001';
            }
          });
        },
        preConfirm: () => {
          const tipo = document.getElementById('tipo_comprobante_modal').value;
          const serie = document.getElementById('serie_comprobante_modal').value;

          if (!tipo || !serie) {
            Swal.showValidationMessage('Todos los campos son obligatorios');
            return false;
          }

          return { tipo, serie };
        }
      }).then((result) => {
        if (result.isConfirmed) {
          const { tipo, serie } = result.value;

          // Mostrar indicador de carga
          Swal.fire({
            title: 'Emitiendo Comprobante',
            text: 'Por favor espere...',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          // Realizar petición para emitir comprobante
          fetch('/admin/comprobantes/emitir-cuota', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            body: JSON.stringify({
              cuota_id: cuotaId,
              tipo_comprobante: tipo,
              serie_comprobante: serie
            })
          })
          .then(response => response.json())
          .then(data => {
            Swal.close();

            if (data.success) {
              Swal.fire({
                icon: 'success',
                title: '¡Comprobante Emitido!',
                text: data.message || 'El comprobante se ha emitido correctamente',
                showConfirmButton: true
              }).then(() => {
                // Recargar la página para mostrar los cambios
                window.location.reload();
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudo emitir el comprobante'
              });
            }
          })
          .catch(error => {
            console.error('Error:', error);
            Swal.close();
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Ocurrió un error al emitir el comprobante'
            });
          });
        }
      });
    });

    // Función global para reenviar comprobante con error dentro de 48 horas
    window.reenviarComprobanteSunat = function(cuotaId, comprobanteId) {
      Swal.fire({
        title: '¿Reenviar Comprobante?',
        html: `
          <div class="text-start">
            <p>Se reenviará el <strong>mismo comprobante</strong> a SUNAT.</p>
            <p class="text-muted">Se mantiene el mismo número y serie.</p>
          </div>
        `,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#17a2b8',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, reenviar',
        cancelButtonText: 'Cancelar',
        focusConfirm: false
      }).then((result) => {
        if (result.isConfirmed) {
          // Mostrar indicador de carga
          Swal.fire({
            title: 'Reenviando Comprobante',
            text: 'Por favor espere...',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          // Usar el método reenviar existente
          fetch(`/admin/comprobantes/${comprobanteId}/reenviar`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
          })
          .then(response => response.json())
          .then(data => {
            Swal.close();

            if (data.success) {
              Swal.fire({
                icon: 'success',
                title: '¡Comprobante Reenviado!',
                text: data.message || 'El comprobante se ha reenviado correctamente',
                showConfirmButton: true
              }).then(() => {
                window.location.reload();
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudo reenviar el comprobante'
              });
            }
          })
          .catch(error => {
            console.error('Error:', error);
            Swal.close();
            Swal.fire({
              icon: 'error',
              title: 'Error de conexión',
              text: 'Ocurrió un error al reenviar el comprobante'
            });
          });
        }
      });
    };

    // Función global para regenerar comprobante - usa la misma lógica que generar
    window.regenerarComprobanteSunat = function(cuotaId, comprobanteId) {
      // Simplemente llama a generarComprobanteSunat con el mismo cuota_id
      // Esto abrirá la misma modal para generar un nuevo comprobante
      generarComprobanteSunat(cuotaId);
    };

    // Manejar clicks en botones de colapso (acordeón)
    $(document).off('click', '[data-bs-toggle="collapse"]').on('click', '[data-bs-toggle="collapse"]', function(e) {
      e.preventDefault();
      e.stopPropagation();

      const target = $(this).attr('data-bs-target');
      console.log('Botón de colapso clickeado para:', target);

      // Validar que el target no esté vacío y sea un selector válido
      if (target && target.trim() !== '' && target !== '#') {
        const collapseElement = $(target);
        if (collapseElement.length) {
          collapseElement.collapse('toggle');
        }
      }
    });
    
    // Manejar clicks en tabs (tanto principales como sub-tabs)
    $('button[data-bs-toggle="tab"], button[data-bs-toggle="pill"]').off('click').on('click', function(e) {
      e.preventDefault();
      console.log('Tab clickeado:', $(this).attr('data-bs-target'));
      
      // Activar usando Bootstrap
      const tab = new bootstrap.Tab(this);
      tab.show();
    });
    
    // Inicializar todas las pestañas con Bootstrap
    const allTabs = document.querySelectorAll('button[data-bs-toggle="tab"]');
    allTabs.forEach(tabEl => {
      tabEl.addEventListener('click', event => {
        event.preventDefault();
        const tab = new bootstrap.Tab(tabEl);
        tab.show();
      });
    });
    
    // Función para mostrar recibo
    window.mostrarRecibo = function(operacionId) {
      console.log('Mostrando recibo para operación:', operacionId);
      
      fetch(`/admin/operaciones/${operacionId}/recibo`)
        .then(response => response.text())
        .then(html => {
          $('#reciboContent').html(html);
          const modal = new bootstrap.Modal(document.getElementById('modalRecibo'));
          modal.show();
        })
        .catch(error => {
          console.error('Error cargando recibo:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo cargar el recibo'
          });
        });
    };
    
    // Función para mostrar voucher
    window.mostrarVoucher = function(voucherUrl) {
      console.log('Mostrando voucher:', voucherUrl);
      
      Swal.fire({
        title: 'Voucher de Pago',
        html: `<img src="${voucherUrl}" style="max-width: 100%; height: auto;" alt="Voucher">`,
        width: 'auto',
        showCloseButton: true,
        focusConfirm: false,
        confirmButtonText: 'Cerrar'
      });
    };
    
    // Función para eliminar operación
    window.eliminarOperacion = function(operacionId) {
      Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          // Crear formulario para eliminar
          const form = $('<form>', {
            method: 'POST',
            action: `/admin/operaciones/${operacionId}`
          });
          
          form.append($('<input>', {
            type: 'hidden',
            name: '_token',
            value: $('meta[name="csrf-token"]').attr('content')
          }));
          
          form.append($('<input>', {
            type: 'hidden',
            name: '_method',
            value: 'DELETE'
          }));
          
          $('body').append(form);
          form.submit();
        }
      });
    };

    // Función para resetear pagos del préstamo
    window.resetLoanPayments = function(prestamoId) {
      Swal.fire({
        title: '⚠️ ¿Resetear todos los pagos?',
        html: `
          <div class="text-start">
            <p><strong>Esta acción eliminará:</strong></p>
            <ul class="text-muted">
              <li>Todas las operaciones de pago de cuotas</li>
              <li>Todas las operaciones de pago de moras</li>
              <li>Los registros de moras pagadas</li>
              <li>Los abonos a favor existentes</li>
            </ul>
            <p><strong>También restablecerá:</strong></p>
            <ul class="text-muted">
              <li>Estado de cuotas a "no pagadas"</li>
              <li>Regeneración automática de moras</li>
            </ul>
            <div class="alert alert-warning mt-3">
              <i class="fas fa-exclamation-triangle me-2"></i>
              <strong>¡Esta acción no se puede deshacer!</strong>
            </div>
          </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-undo me-2"></i>Sí, resetear pagos',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        width: '500px',
        customClass: {
          confirmButton: 'btn btn-danger btn-lg',
          cancelButton: 'btn btn-secondary btn-lg'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          // Mostrar loading
          Swal.fire({
            title: 'Reseteando pagos...',
            html: 'Por favor espera mientras se procesan los cambios.',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          // Enviar petición AJAX
          fetch(`/admin/prestamos/${prestamoId}/reset-payments`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                icon: 'success',
                title: '¡Pagos reseteados!',
                text: data.message,
                confirmButtonText: 'Recargar página'
              }).then(() => {
                // Recargar la página para mostrar los cambios
                window.location.reload();
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudieron resetear los pagos'
              });
            }
          })
          .catch(error => {
            console.error('Error:', error);
            Swal.fire({
              icon: 'error',
              title: 'Error de conexión',
              text: 'No se pudo conectar con el servidor'
            });
          });
        }
      });
    };

    // Función para verificar y generar moras faltantes
    window.verificarYGenerarMoras = function(prestamoId) {
      Swal.fire({
        title: '¿Verificar y generar moras?',
        html: `
          <div class="text-start">
            <p><strong>Esta acción verificará:</strong></p>
            <ul class="text-muted">
              <li>Todas las cuotas del préstamo</li>
              <li>Fechas de vencimiento vs fecha actual</li>
              <li>Moras existentes vs moras que deberían existir</li>
            </ul>
            <p><strong>Y generará:</strong></p>
            <ul class="text-muted">
              <li>Moras faltantes para cuotas vencidas</li>
              <li>Registros de operaciones de mora</li>
            </ul>
            <div class="alert alert-info mt-3">
              <i class="fas fa-info-circle me-2"></i>
              <strong>Solo se crearán moras que no existan previamente.</strong>
            </div>
          </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-exclamation-triangle me-2"></i>Sí, verificar moras',
        cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
        width: '500px',
        customClass: {
          confirmButton: 'btn btn-warning btn-lg',
          cancelButton: 'btn btn-secondary btn-lg'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          // Mostrar loading
          Swal.fire({
            title: 'Verificando moras...',
            html: 'Por favor espera mientras se verifican las cuotas y se generan las moras faltantes.',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          // Enviar petición AJAX
          fetch(`/admin/prestamos/${prestamoId}/verificar-moras`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Mostrar mensaje de éxito con detalles
              let htmlContent = `
                <div class="text-start">
                  <p><strong>${data.message}</strong></p>
                  <hr>
                  <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Resumen de la operación:</strong>
                  </div>
                  <ul class="list-unstyled">
                    <li><strong>Cuotas verificadas:</strong> ${data.resumen.cuotas_verificadas}</li>
                    <li><strong>Moras GENERADAS Y GUARDADAS:</strong> <span class="badge bg-success">${data.resumen.total_moras_generadas}</span></li>
                    <li><strong>Cuotas con moras nuevas:</strong> ${data.resumen.cuotas_con_moras_generadas}</li>
              `;
              
              if (data.resumen.cuotas_omitidas_saldo_favor > 0) {
                htmlContent += `<li><strong>Cuotas omitidas (saldo a favor):</strong> ${data.resumen.cuotas_omitidas_saldo_favor}</li>`;
              }
              
              htmlContent += `</ul>`;
              
              // Mostrar detalles si hay
              if (data.detalles && data.detalles.length > 0) {
                htmlContent += `
                  <hr>
                  <div class="alert alert-light">
                    <strong>Detalles por cuota:</strong>
                    <table class="table table-sm mt-2 mb-0">
                      <thead>
                        <tr>
                          <th>Cuota</th>
                          <th>Acción</th>
                          <th>Detalles</th>
                        </tr>
                      </thead>
                      <tbody>
                `;
                
                data.detalles.forEach(detalle => {
                  let accion = detalle.accion;
                  let detalleStr = '';
                  
                  if (detalle.accion === 'moras_generadas') {
                    detalleStr = `✓ ${detalle.cantidad} moras generadas`;
                  } else if (detalle.accion === 'omitida_saldo_favor') {
                    detalleStr = `Saldo a favor: S/${detalle.saldo_favor.toFixed(2)}`;
                  } else if (detalle.accion === 'sin_cambios') {
                    detalleStr = detalle.motivo;
                  }
                  
                  htmlContent += `
                    <tr>
                      <td>#${detalle.cuota_numero}</td>
                      <td><span class="badge ${detalle.accion === 'moras_generadas' ? 'bg-success' : 'bg-secondary'}">${accion}</span></td>
                      <td>${detalleStr}</td>
                    </tr>
                  `;
                });
                
                htmlContent += `
                      </tbody>
                    </table>
                  </div>
                `;
              }
              
              htmlContent += `</div>`;
              
              Swal.fire({
                icon: 'success',
                title: '✓ Verificación completada exitosamente',
                html: htmlContent,
                confirmButtonText: 'Recargar página',
                width: '600px',
              }).then(() => {
                // Recargar la página para mostrar los cambios
                window.location.reload();
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error en la verificación',
                text: data.message || 'No se pudieron verificar las moras'
              });
            }
          })
          .catch(error => {
            console.error('Error:', error);
            Swal.fire({
              icon: 'error',
              title: 'Error de conexión',
              text: 'No se pudo conectar con el servidor'
            });
          });
        }
      });
    };

    console.log('JavaScript inicializado correctamente');
  });

  // Función para togglear comprobantes por préstamo (debe estar en scope global)
  function toggleComprobantePrestamo(prestamoId, activado) {
    Swal.fire({
      title: activado ? '¿Activar comprobantes SUNAT?' : '¿Desactivar comprobantes SUNAT?',
      text: activado ?
        'Se podrán emitir comprobantes electrónicos para los pagos de este préstamo.' :
        'No se podrán emitir comprobantes electrónicos para este préstamo.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: activado ? '#28a745' : '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: activado ? 'Sí, activar' : 'Sí, desactivar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        // Mostrar loading
        Swal.fire({
          title: 'Actualizando configuración...',
          text: 'Por favor espere',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        // Enviar petición AJAX
        console.log('Enviando petición para préstamo:', prestamoId, 'activado:', activado);

        fetch(`/admin/prestamos/${prestamoId}/toggle-comprobantes`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          body: JSON.stringify({
            activado: activado
          })
        })
        .then(response => {
          console.log('Respuesta recibida:', response.status, response.statusText);
          return response.json();
        })
        .then(data => {
          console.log('Datos recibidos:', data);
          Swal.close();

          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: '¡Configuración actualizada!',
              text: data.message,
              confirmButtonText: 'Aceptar'
            });
          } else {
            // Error - revertir el toggle
            const toggle = document.getElementById(`toggleComprobantes${prestamoId}`);
            toggle.checked = !activado;

            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'No se pudo actualizar la configuración'
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          Swal.close();

          // Error - revertir el toggle
          const toggle = document.getElementById(`toggleComprobantes${prestamoId}`);
          toggle.checked = !activado;

          Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudo conectar con el servidor'
          });
        });
      } else {
        // Cancelado - revertir el toggle
        const toggle = document.getElementById(`toggleComprobantes${prestamoId}`);
        toggle.checked = !activado;
      }
    });
  }


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
</script>

<!-- Modal de Diagnóstico SUNAT -->
<div class="modal fade" id="diagnosticoSunatModal" tabindex="-1" aria-labelledby="diagnosticoSunatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="diagnosticoSunatModalLabel">
                    <i class="fas fa-diagnostics me-2"></i>
                    Diagnóstico SUNAT
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="diagnostico-loading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Verificando...</span>
                    </div>
                    <p class="mt-2">Verificando configuración SUNAT...</p>
                </div>

                <div id="diagnostico-content" style="display: none;">
                    <!-- Estado General -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-server me-2"></i>
                                Estado General
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row" id="estado-general">
                                <!-- Se llena dinámicamente -->
                            </div>
                        </div>
                    </div>

                    <!-- Configuración -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-cogs me-2"></i>
                                Configuración SUNAT
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="configuracion-sunat">
                                <!-- Se llena dinámicamente -->
                            </div>
                        </div>
                    </div>

                    <!-- Certificados y Permisos -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-certificate me-2"></i>
                                Certificados y Permisos
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="certificados-permisos">
                                <!-- Se llena dinámicamente -->
                            </div>
                        </div>
                    </div>

                    <!-- Conectividad -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-wifi me-2"></i>
                                Conectividad
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="conectividad">
                                <!-- Se llena dinámicamente -->
                            </div>
                        </div>
                    </div>

                    <!-- Resumen y Recomendaciones -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-lightbulb me-2"></i>
                                Resumen y Recomendaciones
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="resumen-recomendaciones">
                                <!-- Se llena dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                            <!-- Modal Asignar Aval - COMENTADO: Ahora se usa una vista separada -->
                            <!--
                            <div class="modal fade" id="asignarAvalModal" tabindex="-1" aria-labelledby="asignarAvalModalLabel" aria-hidden="true">
                              <div class="modal-dialog">
                                <div class="modal-content">
                                  <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title" id="asignarAvalModalLabel"><i class="fas fa-user-shield me-2"></i> Asignar Aval</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                  </div>
                                  <div class="modal-body">
                                    <div class="mb-3">
                                      <label for="asignarDni" class="form-label">DNI del Aval</label>
                                      <input type="text" id="asignarDni" class="form-control" maxlength="8" placeholder="Ingrese DNI">
                                      <div class="invalid-feedback">El DNI debe contener 8 dígitos numéricos.</div>
                                    </div>

                                    <div class="mb-3">
                                      <label class="form-label">Nombre</label>
                                      <div class="form-control bg-white"><span id="asignar_nombreAval">---</span></div>
                                    </div>

                                    <div id="asignar_avalInfo" style="display:none;"></div>
                                  </div>
                                  <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="button" class="btn btn-outline-primary" id="btnValidarAval">Verificar</button>
                                    <button type="button" class="btn btn-primary" id="btnConfirmAsignarAval">Asignar Aval</button>
                                  </div>
                                </div>
                              </div>
                            </div>
                            -->
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="volverAVerificar()">
                    <i class="fas fa-sync-alt me-2"></i>
                    Verificar Nuevamente
                </button>
                <button type="button" class="btn btn-success" onclick="abrirConfiguracionSunat()">
                    <i class="fas fa-cogs me-2"></i>
                    Ir a Configuración
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Botón Flotante para Documentos -->
<div class="floating-documents-btn" id="floatingDocumentsBtn">
    <button type="button" class="btn-float" onclick="toggleDocumentsSidebar()" title="Gestionar Documentos">
        <i class="fas fa-folder-open"></i>
        <span class="btn-text">Documentos</span>
    </button>
</div>

<!-- Incluir el sidebar de documentos -->
@include('admin.Prestamos.partials.documentos')

<style>
/* Estilos para el botón flotante */
.floating-documents-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
    transition: all 0.3s ease;
}

.btn-float {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 12px 20px;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    min-height: 50px;
    white-space: nowrap;
}

.btn-float:hover {
    background: linear-gradient(135deg, #0056b3, #004085);
    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.6);
    transform: translateY(-2px) scale(1.05);
}

.btn-float:active {
    transform: translateY(0) scale(0.98);
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.4);
}

.btn-float i {
    font-size: 18px;
    min-width: 18px;
}

.btn-text {
    font-size: 13px;
    font-weight: 600;
}

/* Animación de entrada */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(100px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.floating-documents-btn {
    animation: slideInUp 0.5s ease-out;
}

/* Responsive */
@media (max-width: 768px) {
    .floating-documents-btn {
        bottom: 20px;
        right: 20px;
    }
    
    .btn-float {
        padding: 10px 16px;
        font-size: 13px;
        min-height: 45px;
    }
    
    .btn-float i {
        font-size: 16px;
    }
    
    .btn-text {
        font-size: 12px;
    }
}

/* Ocultar cuando el sidebar está abierto (opcional) */
.documents-sidebar.open ~ .floating-documents-btn {
    opacity: 0.7;
    transform: translateX(-10px);
}
</style>

<script>
// JavaScript para controlar el sidebar de documentos
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

// Event listeners adicionales
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

@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
  // COMENTADO: El botón ahora navega a una vista separada en lugar de abrir un modal
  /*
  const btnOpen = document.getElementById('btnOpenAsignarAval') || document.getElementById('btnOpenAsignarAval_top');
  const asignarModalEl = document.getElementById('asignarAvalModal');
  const asignarDni = document.getElementById('asignarDni');
  const nombreSpan = document.getElementById('asignar_nombreAval');
  const avalInfoDiv = document.getElementById('asignar_avalInfo');
  const btnValidar = document.getElementById('btnValidarAval');
  const btnConfirm = document.getElementById('btnConfirmAsignarAval');
  const csrfToken = '{{ csrf_token() }}';

  let bsModal = null;
  if (asignarModalEl) bsModal = new bootstrap.Modal(asignarModalEl);

  if (btnOpen && bsModal) {
    btnOpen.addEventListener('click', function(e) {
      if (e && e.preventDefault) e.preventDefault();
      nombreSpan.textContent = '---';
      avalInfoDiv.style.display = 'none';
      avalInfoDiv.innerHTML = '';
      asignarDni.value = '';
      asignarDni.classList.remove('is-invalid');
      bsModal.show();
    });
  }

  function mostrarErrorDni() {
    asignarDni.classList.add('is-invalid');
    nombreSpan.textContent = 'DNI inválido';
  }

  btnValidar && btnValidar.addEventListener('click', function() {
    const dni = asignarDni.value.trim();
    if (dni.length !== 8 || !/^\d+$/.test(dni)) { mostrarErrorDni(); return; }

    fetch("{{ route('admin.prestamos.validarAvalAntesDeAsignar') }}", {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
      body: JSON.stringify({ aval_id: dni })
    })
    .then(r => r.json())
    .then(data => {
      if (data.error) {
        nombreSpan.textContent = 'No encontrado';
        avalInfoDiv.style.display = 'none';
        avalInfoDiv.innerHTML = '';
        Swal.fire('Error', data.error, 'error');
      } else {
        nombreSpan.textContent = data.nombreAval || 'Sin nombre';
        let html = '';
        if (data.es_cliente) html += '<div class="alert alert-info">Es cliente registrado</div>';
        if (data.tieneDeuda) html += '<div class="alert alert-warning">Tiene cuotas vencidas</div>';
        if (!html) html = '<div class="alert alert-success">Sin observaciones</div>';
        avalInfoDiv.innerHTML = html;
        avalInfoDiv.style.display = 'block';
      }
    })
    .catch(err => {
      console.error(err);
      Swal.fire('Error', 'No se pudo verificar el aval', 'error');
    });
  });

  btnConfirm && btnConfirm.addEventListener('click', function() {
    const dni = asignarDni.value.trim();
    if (dni.length !== 8 || !/^\d+$/.test(dni)) { mostrarErrorDni(); return; }

    Swal.fire({
      title: 'Confirmar asignación',
      text: '¿Desea asignar este aval al préstamo?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, asignar',
    }).then(result => {
      if (!result.isConfirmed) return;

            fetch("{{ route('admin.prestamos.asignarAvalById', $prestamo->id) }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ aval_id: dni })
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          // Actualizar UI en lugar de recargar la página
          bsModal.hide();
          const assignedDiv = document.getElementById('assignedAvalInfo');
          const assignedName = document.getElementById('assignedAvalName');
          const btnTop = btnOpen;
          const label = data.nombreAval || data.nombre || data.message || dni;
          if (assignedName) {
            assignedName.style.display = 'inline';
            // Si el backend devuelve una URL de la persona, usarla
            const personaUrl = data.persona_show_url ? data.persona_show_url : '#';
            assignedName.innerHTML = personaUrl !== '#' ? `<a href="${personaUrl}">${label}</a>` : `${label}`;
          }
          if (assignedDiv) assignedDiv.style.display = 'block';
          if (btnTop) btnTop.style.display = 'none';
          Swal.fire('Asignado', data.message || 'Aval asignado correctamente', 'success');
        } else {
          Swal.fire('Error', data.error || 'No se pudo asignar el aval', 'error');
        }
      })
      .catch(err => {
        console.error(err);
        Swal.fire('Error', 'No se pudo asignar el aval', 'error');
      });
    });
  });
  */
});
</script>
