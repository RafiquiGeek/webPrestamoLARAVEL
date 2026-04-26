<div>
    <div class="card-header p-0 m-0">
        <ul class="nav nav-pills nav-justified m-3" style="background-color: rgb(230, 230, 230)">
            <li class="nav-item">
                <a class="nav-link {{ $activeButton == '1' ? 'active' : '' }}" style="cursor: pointer" wire:click.prevent="mostrarTabla('1')">Estado de Cuenta</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeButton == '2' ? 'active' : '' }}" style="cursor: pointer" wire:click.prevent="mostrarTabla('2')">Lista de Transacciones</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeButton == '3' ? 'active' : '' }}" style="cursor: pointer" wire:click.prevent="mostrarTabla('3')">Plan de Pago</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeButton == '4' ? 'active' : '' }}" style="cursor: pointer" wire:click.prevent="mostrarTabla('4')">Gestión de Cobranza</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        @switch($tabla)
            @case('1')
                <table class="table table-bordered table-striped">
                    <thead class="text-center">
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Abono</th>
                            <th>Capital</th>
                            <th>Cuotas</th>
                            <th>Interés</th>
                            <th>Comisión</th>
                            <th>IGV</th>
                            <th>Moras</th>
                            <th>Fecha de Abono</th>
                            <th>N° Op.</th>
                            <th>Recepción</th>
                            <th>Moras P.</th>
                            <th>Pago</th>
                            <th>Comprobante</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-center" style="font-size: 1vw">
    @foreach ($cuotas as $cuota)
    <tr>
        <td>{{ $cuota->numero }}</td>
        <td>
            @if ($cuota->fecha_pago)
                {{ \Carbon\Carbon::parse($cuota->fecha_pago)->format('d-m-Y') }}
            @else
                Fecha inválida
            @endif
        </td>
        <td>{{ $cuota->abono ?? '--' }}</td>
        <td>{{ "S/ " . number_format($prestamo->cantidad_solicitada, 2) }}</td>
        <td>{{ "S/ " . number_format($cuota->monto, 2) }}</td>
        <td>{{ "S/ " . number_format($cuota->interes, 2) }}</td>
        <td>{{ "S/ " . number_format($cuota->comision, 2) }}</td>
        <td>{{ "S/ " . number_format($cuota->igv, 2) }}</td>
        <td>{{ $cuota->cantidad_mora ?? '--' }}</td>
        <td>{{ $cuota->fecha_abono ? \Carbon\Carbon::parse($cuota->fecha_abono)->format('d-m-Y') : '--' }}</td>
        <td>{{ $cuota->numero_operacion ?? '--' }}</td>
        <td>{{ $cuota->recepcion ?? '--' }}</td>
        <td>{{ $cuota->moras_pendientes ?? '--' }}</td>
        @switch($cuota->estado)
            @case(2)
                <td class="text-center">
                    <a href="{{ route('admin.registrarpago.create', ['prestamo' => $prestamo->id, 'cuota_id' => $cuota->id]) }}" class="btn btn-warning">
                        <i class="fa-solid fa-coins"></i>
                    </a>
                </td>
            @break
            @case(1)
                <td class="text-center">
                    <a data-toggle="modal" data-target="#mostrarBoleta-modal" class="btn btn-success">
                        <i class="fa-solid fa-check"></i>
                    </a>
                </td>
            @break
            @default
                <td class="text-center">
                    <a href="{{ route('admin.registrarpago.create', ['prestamo' => $prestamo->id, 'cuota_id' => $cuota->id]) }}" class="btn btn-danger">
                        <i class="fa-solid fa-sack-dollar"></i>
                    </a>
                </td>
        @endswitch
        <td class="text-center">
            <a href="{{ route('generar.electronico', ['prestamo_id' => $prestamo->id, 'cuota_id' => $cuota->id]) }}" class="btn btn-primary">
                Boleta
            </a>
        </td>
        <td class="text-center">
            @php
                // Debug: Mostrar información de la cuota
                $debugInfo = "ID: {$cuota->id}, Estado: {$cuota->estado}, MontoPagado: " . ($cuota->monto_pagado ?? 'null');
                
                // Verificar si la cuota se puede editar - TEMPORALMENTE PERMITIR TODAS
                $puedeEditar = true; // Cambiado temporalmente para debug
                $mensajeBloqueo = '';
                
                // COMENTADO TEMPORALMENTE PARA DEBUG
                /*
                // No permitir editar cuotas completamente pagadas
                if ($cuota->estado == 2) {
                    $puedeEditar = false;
                    $mensajeBloqueo = 'Cuota completamente pagada';
                }
                // No permitir editar si tiene pagos parciales
                elseif ($cuota->estado == 1 && ($cuota->monto_pagado ?? 0) > 0) {
                    $puedeEditar = false;
                    $mensajeBloqueo = 'Tiene pagos parciales';
                }
                // Verificar si está muy vencida (más de 90 días)
                elseif (\Carbon\Carbon::now()->diffInDays($cuota->fecha_pago, false) < -90) {
                    $puedeEditar = false;
                    $mensajeBloqueo = 'Muy vencida (>90 días)';
                }
                */
            @endphp
            
            <!-- Debug: Mostrar siempre información -->
            <small class="text-muted d-block">{{ $debugInfo }}</small>
            
            @if($puedeEditar)
                <div class="btn-group" role="group">
                    <button type="button" 
                            class="btn btn-sm btn-warning" 
                            title="Editar cuota"
                            onclick="editarCuota({{ $cuota->id }})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <a href="{{ route('admin.cuotas.edit', $cuota->id) }}" 
                       class="btn btn-sm btn-outline-warning" 
                       title="Editar (nueva ventana)"
                       target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            @else
                <span class="text-muted" 
                      title="{{ $mensajeBloqueo }}"
                      data-toggle="tooltip">
                    <i class="fas fa-lock"></i>
                </span>
                <small class="text-danger d-block">{{ $mensajeBloqueo }}</small>
            @endif
        </td>
    </tr>
    @endforeach
</tbody>
                </table>
                <!-- Modal para mostrar boleta -->
                <div id="mostrarBoleta-modal" class="modal fade" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form action="#" id="mostrarBoleta-form">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Boleta de Pago</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <!-- Contenido del modal -->
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-dark" data-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-success">Imprimir</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @break

            @case('2')
                <table class="table table-bordered table-striped">
                    <thead class="text-center">
                        <tr>
                            <th>Fecha de Creación</th>
                            <th>Fecha Operación</th>
                            <th>Nro Operación</th>
                            <th>Descripción</th>
                            <th>Recepción</th>
                            <th>Balance</th>
                            <th>IGV</th>
                            <th>Saldo Capital</th>
                        </tr>
                    </thead>
                </table>
            @break

            @case('3')
                <table class="table table-bordered table-striped">
                    <thead class="text-center">
                        <tr>
                            <th>Nro.</th>
                            <th>Vencimiento</th>
                            <th>Cuota</th>
                            <th>Interés</th>
                            <th>Capital</th>
                            <th>Comisión</th>
                            <th>IGV</th>
                            <th>Saldo Capital</th>
                        </tr>
                    </thead>
                </table>
            @break

            @case('4')
                <table class="table table-bordered table-striped">
                    <thead class="text-center">
                        <tr>
                            <th>Nro.</th>
                            <th>Estado</th>
                            <th>Observación</th>
                            <th>Fecha Creación</th>
                            <th>Usuario</th>
                            <th>Ubicación</th>
                        </tr>
                    </thead>
                    <tbody class="text-center" style="font-size: 1vw">
                        @foreach($gestiones as $gestion)
                            <tr>
                                <td>{{ $gestion->id }}</td>
                                <td>{{ $gestion->estado }}</td>
                                <td>{{ $gestion->observaciones }}</td>
                                <td>{{ $gestion->fecha_operacion }}</td>
                                <td>{{ $gestion->nombre_cliente }}</td>
                                <td>{{ $gestion->cliente->sucursal->sucursal }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @break

            @default     
        @endswitch
    </div>
</div>

<script>
function editarCuota(cuotaId) {
    if (cuotaId) {
        console.log('Editando cuota:', cuotaId);
        
        // Intentar con window.location
        try {
            const url = "{{ url('admin/cuotas') }}" + "/" + cuotaId + "/edit";
            console.log('URL de edición:', url);
            window.location.href = url;
        } catch (error) {
            console.error('Error al navegar:', error);
            
            // Fallback: abrir en nueva ventana
            const url = "{{ url('admin/cuotas') }}" + "/" + cuotaId + "/edit";
            window.open(url, '_blank');
        }
    } else {
        console.error('ID de cuota no válido:', cuotaId);
    }
}

// Función alternativa para casos de conflicto con Livewire
function editarCuotaDirecto(cuotaId) {
    if (cuotaId) {
        const url = "{{ url('admin/cuotas') }}" + "/" + cuotaId + "/edit";
        
        // Forzar navegación rompiendo el contexto Livewire
        if (typeof Livewire !== 'undefined') {
            Livewire.stop();
            setTimeout(() => {
                window.location.href = url;
            }, 100);
        } else {
            window.location.href = url;
        }
    }
}

// Inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando tooltips');
    if (typeof $ !== 'undefined' && $.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }
});

// Reinicializar tooltips después de updates de Livewire
if (typeof Livewire !== 'undefined') {
    document.addEventListener('livewire:load', function () {
        console.log('Livewire cargado');
        Livewire.hook('message.processed', (message, component) => {
            if (typeof $ !== 'undefined' && $.fn.tooltip) {
                $('[data-toggle="tooltip"]').tooltip();
            }
        });
    });
}

// Debug: mostrar cuando se hace clic en botones
document.addEventListener('click', function(e) {
    if (e.target.closest('.editar-cuota-btn, .btn-warning')) {
        console.log('Click detectado en botón de editar cuota');
        console.log('Target:', e.target);
        console.log('Closest button:', e.target.closest('.btn'));
    }
});
</script>
