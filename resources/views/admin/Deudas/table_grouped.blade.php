{{-- resources/views/admin/Deudas/table_grouped.blade.php --}}
{{-- Elemento oculto para pasar el total de clientes al JavaScript --}}
<input type="hidden" id="total-clientes-servidor" value="{{ $totalClientes ?? $cuotasAgrupadas->total() ?? 0 }}">

<table class="table table-hover mb-0">
    <thead class="thead-light">
        <tr>
            <th width="4%">
                <button id="expand-all" class="btn btn-sm btn-outline-primary" title="Expandir/Contraer Todo">
                    <i class="fas fa-expand-arrows-alt"></i>
                </button>
            </th>
            <th width="16%">Clientes</th>
            <th width="10%">Ubicación</th>
            <th width="12%">Cartera</th>
            <th width="5%">Cuotas</th>
            <th width="8%">Monto</th>
            <th width="8%">Mora</th>
            <th width="9%">Deuda Total</th>
            <th width="5%">Días Mora</th>
            <th width="11%">Tramo</th>
            <th width="12%">Última Gestión/Compromiso</th>
        </tr>
    </thead>
    <tbody>
        @if($cuotasAgrupadas->isEmpty())
            <tr>
                <td colspan="11" class="text-center py-4">
                   <div class="d-flex flex-column align-items-center">
                       <i class="fas fa-search text-muted mb-2" style="font-size: 3rem;"></i>
                       <h5 class="text-muted">No se encontraron cuotas</h5>
                       <p class="text-muted">Intenta ajustar los filtros de búsqueda de cuotas</p>
                   </div>
               </td>
           </tr>
       @else
           @foreach($cuotasAgrupadas as $clienteId => $datos)
               {{-- Fila principal del cliente (acordeón header) --}}
               <tr class="cliente-header cursor-pointer" data-cliente="{{ $clienteId }}" style="background-color: #f8f9fa;" onclick="toggleClienteRow({{ $clienteId }})">
                   <td>
                       <button class="btn btn-sm btn-outline-secondary toggle-cliente" data-cliente="{{ $clienteId }}" onclick="event.stopPropagation(); toggleClienteRow({{ $clienteId }})">
                           <i class="fas fa-chevron-right"></i>
                       </button>
                   </td>
                   <td>
                       <div class="d-flex align-items-center">
                           <div class="avatar-circle mr-2 bg-primary text-white">
                               {{ strtoupper(substr($datos['nombre_completo'], 0, 1)) }}
                           </div>
                           <div>
                               <span class="font-weight-bold d-block">{{ $datos['nombre_completo'] }}</span>
                               @if(isset($datos['cliente']->persona->documento) && $datos['cliente']->persona->documento)
                                   <small class="text-muted d-block">DNI: {{ $datos['cliente']->persona->documento }}</small>
                               @endif
                               @php
                                   $primeraCuota = $datos['cuotas']->first();
                                   $prestamo = $primeraCuota ? $primeraCuota->prestamo : null;
                                   $esCuotaConvenio = $primeraCuota && isset($primeraCuota->es_cuota_convenio) && $primeraCuota->es_cuota_convenio;
                                   $convenioActivo = null;

                                   if ($esCuotaConvenio && isset($primeraCuota->convenio_id)) {
                                       $convenioActivo = $primeraCuota->convenio_id;
                                   } elseif ($prestamo) {
                                       $convenioActivo = $prestamo->convenios->where('estado', \App\Enums\ConvenioEstado::ACTIVO->value)->first();
                                   }
                               @endphp

                               @if($esCuotaConvenio)
                                   {{-- Es una cuota de convenio: mostrar CONVENIO primero --}}
                                   <small class="badge badge-warning">Convenio: #{{ $convenioActivo }}</small>
                                   <small class="text-muted d-block">(Préstamo: #{{ $prestamo->id ?? 'N/A' }})</small>
                               @elseif($convenioActivo)
                                   {{-- Es cuota de préstamo con convenio activo --}}
                                   <small class="badge badge-warning">Convenio: #{{ $convenioActivo->id }}</small>
                                   <small class="text-muted d-block">(Préstamo: #{{ $prestamo->id ?? 'N/A' }})</small>
                               @else
                                   {{-- Es cuota de préstamo normal --}}
                                   <small class="badge badge-info">Préstamo: #{{ $prestamo->id ?? 'N/A' }}</small>
                               @endif
                           </div>
                       </div>
                   </td>
                   <td>
                       @php
                           $tieneZona = !empty($datos['zona']);
                           $tieneSucursal = !empty($datos['sucursal']);

                           // DEBUG - ELIMINAR DESPUÉS
                           if ($loop->first) {
                               \Log::debug('DEBUG VISTA', [
                                   'datos_zona_existe' => isset($datos['zona']),
                                   'datos_zona_valor' => $datos['zona'] ?? 'NO EXISTE',
                                   'datos_sucursal_existe' => isset($datos['sucursal']),
                                   'datos_sucursal_valor' => $datos['sucursal'] ?? 'NO EXISTE',
                                   'tieneZona' => $tieneZona,
                                   'tieneSucursal' => $tieneSucursal
                               ]);
                           }
                       @endphp

                       @if($tieneZona || $tieneSucursal)
                           <small>
                               @if($tieneZona)
                                   {{ $datos['zona'] }}
                               @endif
                               @if($tieneZona && $tieneSucursal)
                                   ,
                               @endif
                               @if($tieneSucursal)
                                   {{ $datos['sucursal'] }}
                               @endif
                           </small>
                       @else
                           <span class="text-muted small">Sin ubicación</span>
                       @endif
                   </td>
                   <td>
                       {{-- CARTERAS A NIVEL DE CLIENTE --}}
                       <div class="cartera-info">
                           @if($datos['jcc_nombre'])
                               <div class="cartera-item">
                                   <span class="badge badge-secondary badge-xs">JCC</span>
                                   <small class="cartera-name">
                                       @if($datos['jcc_codigo'])
                                           <span class="text-muted">{{ $datos['jcc_codigo'] }}</span>
                                       @endif
                                   </small>
                               </div>
                           @endif

                           @if($datos['asesor_nombre'])
                               <div class="cartera-item">
                                   <span class="badge badge-info badge-xs">ASE</span>
                                   <small class="cartera-name">
                                       @if($datos['asesor_codigo'])
                                           <span class="text-muted">{{ $datos['asesor_codigo'] }}</span>
                                       @endif
                                   </small>
                               </div>
                           @endif

                           @if($datos['analista_nombre'])
                               <div class="cartera-item">
                                   <span class="badge badge-primary badge-xs">ANA</span>
                                   <small class="cartera-name">
                                       @if($datos['analista_codigo'])
                                           <span class="text-muted">{{ $datos['analista_codigo'] }}</span>
                                       @endif
                                   </small>
                               </div>
                           @endif

                           @if(!$datos['jcc_nombre'] && !$datos['asesor_nombre'] && !$datos['analista_nombre'] && $datos['cuotas']->isEmpty())
                               <span class="text-muted small">Sin cartera asignada</span>
                           @endif
                       </div>
                   </td>
                   <td>
                       <span class="badge badge-info badge-lg">
                           {{ $datos['total_cuotas'] }} cuota{{ $datos['total_cuotas'] > 1 ? 's' : '' }}
                       </span>
                   </td>
                   <td>
                       <span class="font-weight-bold">S/ {{ number_format($datos['monto_total'], 2) }}</span>
                   </td>
                   <td>
                       <span class="font-weight-bold text-mora">S/ {{ number_format($datos['mora_total'], 2) }}</span>
                   </td>
                   <td>
                       <span class="font-weight-bold" style="font-size: 1.1em;">S/ {{ number_format($datos['deuda_total'], 2) }}</span>
                   </td>
                   <td>
                       @if($datos['dias_mora_max'] > 0)
                           <span class="badge badge-danger">{{ $datos['dias_mora_max'] }} días</span>
                       @else
                           <span class="badge badge-secondary">0 días</span>
                       @endif
                   </td>
                   <td>
                       {{-- TRAMO: Calculado a partir de la primera cuota no pagada --}}
                       @php
                           $cuotasOrdenadas = $datos['cuotas']->sortBy('fecha_pago');
                           $primeraCuotaNoPagada = $cuotasOrdenadas->first(function ($cuota) {
                               $estado = $cuota->estado;
                               $estadoValor = $estado instanceof \BackedEnum ? $estado->value : (is_numeric($estado) ? (int) $estado : null);
                               return in_array($estadoValor, [0, 1, 3]);
                           });
                           
                           $tramoTexto = 'Sin atraso';
                           $tramoBadgeClass = 'badge-secondary';
                           $diasAtraso = 0;
                           
                           if ($primeraCuotaNoPagada) {
                               $fechaVencimiento = \Carbon\Carbon::parse($primeraCuotaNoPagada->fecha_pago);
                               $diasAtraso = $fechaVencimiento->diffInDays(now(), false);
                               
                               if ($diasAtraso >= 0) {
                                   if ($diasAtraso <= 6) {
                                       $numeroTramo = 0;
                                       $tramoBadgeClass = 'badge-success';
                                   } elseif ($diasAtraso <= 14) {
                                       $numeroTramo = 1;
                                       $tramoBadgeClass = 'badge-info';
                                   } elseif ($diasAtraso <= 21) {
                                       $numeroTramo = 2;
                                       $tramoBadgeClass = 'badge-warning';
                                   } elseif ($diasAtraso <= 30) {
                                       $numeroTramo = 3;
                                       $tramoBadgeClass = 'badge-warning text-dark';
                                   } else {
                                       $numeroTramo = 4;
                                       $tramoBadgeClass = 'badge-danger';
                                   }
                                   $tramoTexto = "Tramo {$numeroTramo}";
                               }
                           }
                       @endphp
                       <span class="badge {{ $tramoBadgeClass }}">{{ $tramoTexto }}</span>
                       @if($primeraCuotaNoPagada && $diasAtraso >= 0)
                           <br><small class="text-muted">{{ $diasAtraso }} días</small>
                       @endif
                   </td>
                   <td>
                       {{-- OPTIMIZACIÓN: Usar datos precalculados del controlador --}}
                       <div class="text-sm">
                           @if($datos['ultima_gestion'])
                               <div class="mb-1">
                                   <span class="badge badge-info">Gestión</span>
                                   <small class="d-block">{{ \Carbon\Carbon::parse($datos['ultima_gestion']->fecha)->format('d/m/Y') }}</small>
                               </div>
                           @endif
                           @if($datos['ultimo_compromiso'])
                               <div>
                                   <span class="badge badge-warning">Compromiso</span>
                                   <small class="d-block">{{ \Carbon\Carbon::parse($datos['ultimo_compromiso']->fecha_compromiso_pago)->format('d/m/Y') }}</small>
                               </div>
                           @endif
                           @if(!$datos['ultima_gestion'] && !$datos['ultimo_compromiso'])
                               <span class="text-muted">Sin actividad</span>
                           @endif
                       </div>
                   </td>
               </tr>

               {{-- Filas de detalles de cuotas (inicialmente ocultas) --}}
               @foreach($datos['cuotas'] as $cuota)
                   <tr class="cuota-detalle d-none" data-cliente="{{ $clienteId }}" style="background-color: #ffffff; border-left: 3px solid #0056b3;">
                       <td></td>
                       <td class="pl-4">
                           <div class="d-flex align-items-center">
                               <div class="cuota-number-badge mr-3">
                                   <span class="badge badge-danger badge-pill">#{{ $cuota->numero }}</span>
                               </div>
                               <div>
                                   <span class="font-weight-bold">Cuota #{{ $cuota->numero }}</span>
                                   <br><small class="text-muted">
                                       Préstamo #{{ $cuota->prestamo_id }}
                                       @if($cuota->estado == \App\Enums\CuotaEstado::PENDIENTE)
                                           • <span class="badge badge-warning badge-sm">Pendiente</span>
                                       @elseif($cuota->estado == \App\Enums\CuotaEstado::PARCIAL)
                                           • <span class="badge badge-info badge-sm">Parcial</span>
                                       @elseif($cuota->estado == \App\Enums\CuotaEstado::VENCIDO)
                                           • <span class="badge badge-danger badge-sm">Vencida</span>
                                       @elseif($cuota->estado == \App\Enums\CuotaEstado::PAGADO)
                                           • <span class="badge badge-success badge-sm">Pagada</span>
                                       @endif
                                   </small>
                               </div>
                           </div>
                       </td>
                       <td colspan="2">
                           <div class="d-flex align-items-center">
                               <i class="far fa-calendar-alt text-danger mr-2"></i>
                               <div>
                                   <span class="font-weight-bold text-black">
                                       Vencimiento: {{ \Carbon\Carbon::parse($cuota->fecha_pago)->format('d/m/Y') }}
                                   </span>
                                   @if($cuota->fecha_pago < now() && $cuota->estado != \App\Enums\CuotaEstado::PAGADO)
                                       <br><small class="text-danger">
                                           Vencida hace {{ now()->diffInDays($cuota->fecha_pago) }} días
                                       </small>
                                   @endif
                               </div>
                           </div>
                       </td>
                       <td>
                           <span class="font-weight-bold">S/ {{ number_format($cuota->monto, 2) }}</span>
                           @if($cuota->monto_pagado > 0)
                               <br><small class="text-success">Pagado: S/ {{ number_format($cuota->monto_pagado, 2) }}</small>
                           @endif
                       </td>
                       <td>
                           <span class="font-weight-bold text-mora">
                               S/ {{ number_format($cuota->cantidad_mora ?? 0, 2) }}
                           </span>
                           @if($cuota->moras && $cuota->moras->count() > 0)
                               <br><small>{{ $cuota->moras->count() }} mora{{ $cuota->moras->count() > 1 ? 's' : '' }}</small>
                           @endif
                       </td>
                       <td>
                           <span class="font-weight-bold" style="font-size: 1.1em;">
                               S/ {{ number_format(($cuota->monto - $cuota->monto_pagado) + ($cuota->cantidad_mora ?? 0), 2) }}
                           </span>
                           <br><small>Saldo pendiente</small>
                       </td>
                       <td class="text-center">
                           {{-- Acciones --}}
                           <div class="btn-group btn-group-sm" role="group">
                               <!-- Botón para ver detalles de la cuota -->
                               <button type="button" class="btn btn-outline-primary btn-xs"
                                       onclick="verPrevisualizacion({{ $cuota->id }})"
                                       title="Ver Estado de Cobranza">
                                   <i class="fas fa-eye"></i>
                               </button>
                               <a href="{{ route('admin.prestamos.show', $cuota->prestamo_id) }}"
                                  class="btn btn-outline-info btn-xs"
                                  title="Ver Préstamo">
                                   <i class="fas fa-info-circle"></i>
                               </a>
                           </div>
                       </td>
                   </tr>
               @endforeach
           @endforeach
       @endif
   </tbody>
   
   {{-- Pie de tabla con totales --}}
   @if($cuotasAgrupadas->isNotEmpty())
       <tfoot>
           <tr class="footer-total">
               <td colspan="5" class="text-right">
                   <strong>TOTALES ({{ $cuotasAgrupadas->count() }} clientes):</strong>
               </td>
               <td><strong>S/ {{ number_format($totalMonto, 2) }}</strong></td>
               <td><strong>S/ {{ number_format($totalMora, 2) }}</strong></td>
               <td><strong>S/ {{ number_format($totalDeuda, 2) }}</strong></td>
               <td colspan="3"></td>
           </tr>
       </tfoot>
   @endif
</table>

@if($cuotasAgrupadas->isNotEmpty())
   <div class="p-3 border-top">
       <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
           <div class="text-muted mb-2 mb-sm-0">
               Mostrando {{ $cuotasAgrupadas->count() }} de {{ $totalClientes ?? $cuotasAgrupadas->total() }} clientes
               <span class="mx-2">|</span>
               {{ $cuotasAgrupadas->sum('total_cuotas') }} cuotas
           </div>
           <div class="text-muted">
               Total deuda: <span class="font-weight-bold text-danger">S/ {{ number_format($totalDeuda, 2) }}</span>
           </div>
       </div>

       {{-- Paginación --}}
       @if($cuotasAgrupadas instanceof \Illuminate\Pagination\LengthAwarePaginator)
           <div class="d-flex justify-content-center">
               {{ $cuotasAgrupadas->links('pagination::bootstrap-4') }}
           </div>
       @endif
   </div>
@endif

<!-- MODAL DE PREVISUALIZACIÓN -->
<div class="modal fade" id="modalPrevisualizacion" tabindex="-1" role="dialog" aria-labelledby="modalPrevisualizacionLabel" aria-hidden="true">
   <div class="modal-dialog modal-lg" role="document" style="max-width: 1000px;">
       <div class="modal-content">
           <!-- Header del Modal -->
           <div class="modal-header bg-primary text-white">
               <h5 class="modal-title" id="modalPrevisualizacionLabel">
                   <i class="fas fa-file-pdf mr-2"></i>
                   Previsualización - Estado de Cobranza
               </h5>
               <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                   <span aria-hidden="true">&times;</span>
               </button>
           </div>
           
           <!-- Body del Modal -->
           <div class="modal-body p-0" style="height: 80vh; overflow-y: auto;">
               <!-- Loading -->
               <div id="loading-preview" class="text-center py-5">
                   <div class="spinner-border text-primary" role="status">
                       <span class="sr-only">Cargando...</span>
                   </div>
                   <p class="mt-3 text-muted">Generando previsualización...</p>
               </div>
               
               <!-- Contenido de la previsualización -->
               <div id="contenido-preview" style="display: none;">
                   <!-- El contenido se cargará aquí via AJAX -->
               </div>
               
               <!-- Error -->
               <div id="error-preview" style="display: none;" class="text-center py-5">
                   <div class="text-danger">
                       <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                       <h5>Error al cargar la previsualización</h5>
                       <p class="text-muted">Inténtalo nuevamente o descarga el PDF directamente.</p>
                   </div>
               </div>
           </div>
           
           <!-- Footer del Modal -->
           <div class="modal-footer">
               <button type="button" class="btn btn-secondary" data-dismiss="modal">
                   <i class="fas fa-times mr-1"></i> Cerrar
               </button>
               <button type="button" class="btn btn-success" id="btn-descargar-pdf" disabled>
                   <i class="fas fa-download mr-1"></i> Descargar PDF
               </button>
               <button type="button" class="btn btn-primary" id="btn-imprimir" disabled>
                   <i class="fas fa-print mr-1"></i> Imprimir
               </button>
           </div>
       </div>
   </div>
</div>

<script>
// Función de respaldo para toggle de clientes - funcionará incluso si deudas.js falla
function toggleClienteRow(clienteId) {
    console.log('=== TOGGLE CLIENTE ROW (FALLBACK) ===');
    console.log('Cliente ID:', clienteId);
    
    // Usar jQuery si está disponible, sino usar JavaScript nativo
    if (typeof $ !== 'undefined') {
        const detalles = $(`.cuota-detalle[data-cliente="${clienteId}"]`);
        const boton = $(`.toggle-cliente[data-cliente="${clienteId}"]`);
        const icono = boton.find('i');
        
        console.log('Usando jQuery - Detalles encontrados:', detalles.length);
        
        if (detalles.length > 0) {
            if (detalles.hasClass('d-none')) {
                detalles.removeClass('d-none');
                icono.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                boton.addClass('expanded');
                console.log('Detalles mostrados');
            } else {
                detalles.addClass('d-none');
                icono.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                boton.removeClass('expanded');
                console.log('Detalles ocultados');
            }
            return;
        }
    }
    
    // Fallback usando JavaScript nativo
    console.log('Usando JavaScript nativo');
    const detalles = document.querySelectorAll(`.cuota-detalle[data-cliente="${clienteId}"]`);
    const boton = document.querySelector(`.toggle-cliente[data-cliente="${clienteId}"]`);
    const icono = boton ? boton.querySelector('i') : null;
    
    console.log('JS Nativo - Detalles encontrados:', detalles.length);
    
    if (detalles.length > 0) {
        const primerDetalle = detalles[0];
        const estaOculto = primerDetalle.classList.contains('d-none');
        
        detalles.forEach(detalle => {
            if (estaOculto) {
                detalle.classList.remove('d-none');
            } else {
                detalle.classList.add('d-none');
            }
        });
        
        if (icono) {
            if (estaOculto) {
                icono.classList.remove('fa-chevron-right');
                icono.classList.add('fa-chevron-down');
                boton.classList.add('expanded');
                console.log('Detalles mostrados (JS nativo)');
            } else {
                icono.classList.remove('fa-chevron-down');
                icono.classList.add('fa-chevron-right');
                boton.classList.remove('expanded');
                console.log('Detalles ocultados (JS nativo)');
            }
        }
    } else {
        console.error('No se encontraron detalles para cliente:', clienteId);
    }
}

// Si hay un sistema global de toggle, exponer esta función
if (typeof window.toggleCliente === 'undefined') {
    window.toggleCliente = toggleClienteRow;
}
</script>

