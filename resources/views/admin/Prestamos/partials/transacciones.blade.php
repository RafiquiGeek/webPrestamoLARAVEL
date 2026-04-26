<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 text-primary">
        <i class="fas fa-exchange-alt me-2"></i> Operaciones Generales
    </h5>
    <a href="{{ route('operaciones.generar-pdf', $prestamo->id) }}" class="btn btn-sm btn-primary">
        <i class="fas fa-file-pdf me-1"></i> Generar PDF de Operaciones
    </a>
</div>
<div class="table-responsive">
    <table class="table table-hover border-0">
        <thead class="bg-light">
            <tr>
                <th># Operación</th>
                <th>Fecha</th>
                <th>Cuota(s)</th>
                <th>Monto Total</th>
                <th>Método de Pago</th>
                <th class="d-none d-md-table-cell">Receptor</th>
                <th class="d-none d-lg-table-cell">Comentario</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        @foreach($operacionesGenerales as $operacion)
            <tr class="bg-light">
                <td class="fw-bold">#{{ $operacion->id }}</td>
                <td>{{ \Carbon\Carbon::parse($operacion->fecha)->format('d-m-Y') }}</td>
                <td>
                    @forelse($operacion->operacionesRelacionadas as $relacionada)
                            <!-- Verificamos si la operación relacionada tiene cuotas -->
                            @if($relacionada->cuotas->isNotEmpty())
                                @foreach($relacionada->cuotas as $cuota)
                                    <span class="badge bg-info text-white me-1 mb-1">
                                        {{ $cuota->numero }}
                                    </span>
                                @endforeach
                            @endif
                        @empty
                            <span class="badge bg-secondary">-</span>
                    @endforelse
                    
                </td>
                <td class="text-success fw-bold">S/. {{ number_format($operacion->abono, 2) }}</td>
                <td>
                    <span class="badge bg-light text-dark border px-2">
                        {{ optional($operacion->metodoDePago)->metodo_pago ?? 'N/A' }}
                    </span>
                </td>
                <td class="d-none d-md-table-cell">{{ optional($operacion->user)->codigo ?? 'N/A' }}</td>
                <td class="d-none d-lg-table-cell text-muted small">{{ $operacion->comentario }}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-info me-1"
                                data-bs-toggle="collapse"
                                data-bs-target="#detalleOperacion{{ $operacion->id }}"
                                aria-expanded="false"
                                title="Ver Detalles de Pagos">
                            <i class="fas fa-eye"></i>
                        </button>
                        {{-- Enlaces simples para operación principal --}}
                        @if(($operacion->estado ?? 'activo') !== 'anulado')
                            <a href="{{ route('admin.operaciones.editar', $operacion->id) }}" 
                               class="btn btn-sm btn-outline-warning me-1"
                               title="Editar Operación Principal">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="{{ route('admin.operaciones.anular', $operacion->id) }}" 
                               class="btn btn-sm btn-outline-danger me-1"
                               title="Anular Operación Principal">
                                <i class="fas fa-ban"></i>
                            </a>
                        @endif
                        @if($operacion->editado_en || $operacion->anulado_en)
                            <a href="{{ route('admin.operaciones.historial', $operacion->id) }}" 
                               class="btn btn-sm btn-outline-secondary me-1"
                               title="Ver Historial de Cambios">
                                <i class="fas fa-history"></i>
                            </a>
                        @endif
                        @if($operacion->operacionesRelacionadas->isNotEmpty())
                            @foreach($operacion->operacionesRelacionadas as $relacionada)
                                @if($relacionada->cuotas->whereIn('estado', ['pendiente', 'pagado'])->isNotEmpty())
                                    <a href="{{ route('admin.registrarpago.create', ['prestamo_id' => $prestamo->id]) }}?cuota_id={{ $relacionada->cuotas->first()->id }}"
                                    class="btn btn-sm btn-outline-success"
                                    title="Registrar Pago">
                                        <i class="fas fa-money-check-alt"></i>
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </td>
            </tr>

            <!-- Detalle de operaciones relacionadas (Pago de Cuotas) -->
            <tr class="collapse" id="detalleOperacion{{ $operacion->id }}">
                <td colspan="7" class="p-0">
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-header text-black py-2">
                            <h6 class="mb-0">
                                <i class="fas fa-list me-2"></i> Operaciones Relacionadas (Pago de Cuotas)
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th># Operación</th>
                                            <th># Cuota</th>
                                            <th>Fecha registro</th>
                                            <th>Fecha abono</th>
                                            <th>Monto</th>
                                            <th>Cuenta asignada</th>
                                            <th class="d-none d-md-table-cell">Método de Pago</th>
                                            <th>Nro Operación</th>
                                            <th class="d-none d-md-table-cell">Receptor</th>
                                            <th class="d-none d-lg-table-cell">Comentario</th>
                                            <th>Recibo</th>
                                            <th>PDF</th>
                                            <!--th class="text-center">Acciones</th-->
                                        </tr>
                                    </thead>
                                    <tbody>

                                        
                                        @forelse($operacion->operacionesRelacionadas as $relacionada)
                                            <!-- Verificamos si la operación relacionada tiene cuotas -->
                                            @if($relacionada->cuotas->isNotEmpty())
                                                @foreach($relacionada->cuotas as $cuota)
                                                    <tr>
                                                        <td>{{ $relacionada->id }}</td>
                                                        <td>
                                                            <span class="badge bg-info text-white">
                                                                {{ $cuota->numero }}
                                                            </span>
                                                        </td>
                                                        <td>{{ \Carbon\Carbon::parse($relacionada->created_at)->format('d-m-Y') }}</td>
                                                        <td>{{ \Carbon\Carbon::parse($relacionada->fecha)->format('d-m-Y') }}</td>
                                                        <td class="text-success fw-bold">S/. {{ number_format($relacionada->abono, 2) }}</td>
                                                        <td>{{ optional($relacionada->prestamo->cuenta)->codigo ?? 'N/A' }}</td>
                                                        <td class="d-none d-md-table-cell">{{ optional($relacionada->metodoDePago)->metodo_pago ?? 'N/A' }}</td>
                                                        <td>{{ $relacionada->codigo ?? 'N/A' }}</td>
                                                        <td class="d-none d-md-table-cell">{{ optional($relacionada->user)->name ?? 'N/A' }}</td>
                                                        <td class="d-none d-lg-table-cell small text-muted">{{ $relacionada->comentario }}</td>
                                                        <td>
                                                            @if($relacionada->voucher_path && in_array($relacionada->metodoDePago->id ?? 0, [2, 3, 4, 5]))
                                                                <button type="button"
                                                                    class="btn btn-sm btn-success ver-recibo"
                                                                    data-toggle="modal"
                                                                    data-target="#modalRecibo"
                                                                    data-title="Comprobante #{{ $relacionada->id }}"
                                                                    data-ruta="{{ asset('storage/' . $relacionada->voucher_path) }}"
                                                                    data-extension="{{ pathinfo($relacionada->voucher_path, PATHINFO_EXTENSION) }}"
                                                                    title="Ver comprobante">
                                                                    <i class="fas fa-receipt"></i>
                                                                </button>
                                                            @else
                                                                <span class="badge bg-secondary">N/A</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('operaciones.generar-pdf-individual', [$prestamo->id, $relacionada->id]) }}"
                                                            class="btn btn-sm btn-primary"
                                                            target="_blank"
                                                            title="Generar PDF de la operación">
                                                                <i class="fas fa-file-pdf"></i>
                                                            </a>
                                                        </td>
                                                        {{-- Acciones simples para sub-operación de cuota --}}
                                                        <!--td class="text-center">
                                                            <div class="btn-group btn-group-sm">
                                                                @if(($relacionada->estado ?? 'activo') !== 'anulado')
                                                                    <a href="{{ route('admin.operaciones.editar', $relacionada->id) }}" 
                                                                       class="btn btn-sm btn-outline-warning"
                                                                       title="Editar Pago de Cuota">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                    <a href="{{ route('admin.operaciones.anular', $relacionada->id) }}" 
                                                                       class="btn btn-sm btn-outline-danger"
                                                                       title="Anular Pago de Cuota">
                                                                        <i class="fas fa-ban"></i>
                                                                    </a>
                                                                @endif
                                                                @if($relacionada->editado_en || $relacionada->anulado_en)
                                                                    <a href="{{ route('admin.operaciones.historial', $relacionada->id) }}" 
                                                                       class="btn btn-sm btn-outline-secondary"
                                                                       title="Ver Historial">
                                                                        <i class="fas fa-history"></i>
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        </td-->
                                                    </tr>
                                                @endforeach
                                            @endif
                                            <!-- Verificar si tiene moras pagadas (excluir regularizadas) -->
                                            @php
                                                $morasPagadasVisibles = $relacionada->morasCuota->where('estado', '!=', \App\Enums\MoraCuotaEstado::REGULARIZADA->value);
                                            @endphp
                                            @if($morasPagadasVisibles->isNotEmpty())
                                                @foreach($morasPagadasVisibles as $moraPagada)
                                                    <tr class="bg-warning-light">
                                                        <td>{{ $relacionada->id }}</td>
                                                        <td>
                                                            <span class="badge bg-warning text-dark">
                                                                Cuota {{ optional($moraPagada->cuota)->numero ?? 'N/A' }}
                                                            </span>
                                                            <br>
                                                            <small class="text-warning">
                                                                <i class="fas fa-clock me-1"></i>MORA
                                                            </small>
                                                        </td>
                                                        <td>{{ \Carbon\Carbon::parse($relacionada->created_at)->format('d-m-Y') }}</td>
                                                        <td>{{ \Carbon\Carbon::parse($relacionada->fecha)->format('d-m-Y') }}</td>
                                                        <td class="text-warning fw-bold">
                                                            S/. {{ number_format($moraPagada->monto, 2) }}
                                                            <br>
                                                            <small class="text-muted">
                                                                Días: {{ $moraPagada->dias_mora ?? 0 }}
                                                            </small>
                                                        </td>
                                                        <td>{{ optional($relacionada->prestamo->cuenta)->codigo ?? 'N/A' }}</td>
                                                        <td class="d-none d-md-table-cell">{{ optional($relacionada->metodoDePago)->metodo_pago ?? 'N/A' }}</td>
                                                        <td>{{ $relacionada->codigo ?? 'N/A' }}</td>
                                                        <td class="d-none d-md-table-cell">{{ optional($relacionada->user)->name ?? 'N/A' }}</td>
                                                        <td class="d-none d-lg-table-cell small text-muted">
                                                            Pago de mora - Cuota {{ optional($moraPagada->cuota)->numero ?? 'N/A' }}
                                                            @if($moraPagada->fecha)
                                                                <br><small>Fecha mora: {{ \Carbon\Carbon::parse($moraPagada->fecha)->format('d-m-Y') }}</small>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($relacionada->voucher_path && in_array($relacionada->metodoDePago->id ?? 0, [2, 3, 4, 5]))
                                                                <button type="button"
                                                                    class="btn btn-sm btn-success ver-recibo"
                                                                    data-toggle="modal"
                                                                    data-target="#modalRecibo"
                                                                    data-title="Comprobante #{{ $relacionada->id }}"
                                                                    data-ruta="{{ asset('storage/' . $relacionada->voucher_path) }}"
                                                                    data-extension="{{ pathinfo($relacionada->voucher_path, PATHINFO_EXTENSION) }}"
                                                                    title="Ver comprobante">
                                                                    <i class="fas fa-receipt"></i>
                                                                </button>
                                                            @else
                                                                <span class="badge bg-secondary">N/A</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('operaciones.generar-pdf-individual', [$prestamo->id, $relacionada->id]) }}"
                                                            class="btn btn-sm btn-primary"
                                                            target="_blank"
                                                            title="Generar PDF de la operación">
                                                                <i class="fas fa-file-pdf"></i>
                                                            </a>
                                                        </td>
                                                        {{-- Acciones simples para sub-operación de mora --}}
                                                        <!--td class="text-center">
                                                            <div class="btn-group btn-group-sm">
                                                                @if(($relacionada->estado ?? 'activo') !== 'anulado')
                                                                    <a href="{{ route('admin.moras.edit', $moraPagada->id) }}" 
                                                                       class="btn btn-sm btn-outline-warning"
                                                                       title="Editar Pago de Mora">
                                                                        <i class="fas fa-edit"></i>
                                                                    </a>
                                                                    <a href="{{ route('admin.moras.anular.show', $moraPagada->id) }}" 
                                                                       class="btn btn-sm btn-outline-danger"
                                                                       title="Anular Pago de Mora">
                                                                        <i class="fas fa-ban"></i>
                                                                    </a>
                                                                @endif
                                                                <a href="{{ route('admin.moras.historial', $moraPagada->id) }}" 
                                                                   class="btn btn-sm btn-outline-secondary"
                                                                   title="Ver Historial">
                                                                    <i class="fas fa-history"></i>
                                                                </a>
                                                            </div>
                                                        </td-->
                                                    </tr>
                                                @endforeach
                                            @elseif($relacionada->cuotas->isEmpty())
                                                <!-- Si no tiene cuotas ni moras - Operación sin detalle específico -->
                                                <tr>
                                                    <td>{{ $relacionada->id }}</td>
                                                    <td><span class="badge bg-secondary">-</span></td>
                                                    <td>{{ \Carbon\Carbon::parse($relacionada->created_at)->format('d-m-Y') }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($relacionada->fecha)->format('d-m-Y') }}</td>
                                                    <td class="text-success fw-bold">S/. {{ number_format($relacionada->abono, 2) }}</td>
                                                    <td>{{ optional($relacionada->prestamo->cuenta)->codigo ?? 'N/A' }}</td>
                                                    <td class="d-none d-md-table-cell">{{ optional($relacionada->metodoDePago)->metodo_pago ?? 'N/A' }}</td>
                                                    <td>{{ $relacionada->codigo ?? 'N/A' }}</td>
                                                    <td class="d-none d-md-table-cell">{{ optional($relacionada->user)->name ?? 'N/A' }}</td>
                                                    <td class="d-none d-lg-table-cell small text-muted">{{ $relacionada->comentario }}</td>
                                                    <td>
                                                        @if($relacionada->voucher_path && in_array($relacionada->metodoDePago->id ?? 0, [2, 3, 4, 5]))
                                                            <button type="button"
                                                                class="btn btn-sm btn-success ver-recibo"
                                                                data-toggle="modal"
                                                                data-target="#modalRecibo"
                                                                data-title="Comprobante #{{ $relacionada->id }}"
                                                                data-ruta="{{ asset('storage/' . $relacionada->voucher_path) }}"
                                                                data-extension="{{ pathinfo($relacionada->voucher_path, PATHINFO_EXTENSION) }}"
                                                                title="Ver comprobante">
                                                                <i class="fas fa-receipt"></i>
                                                            </button>
                                                        @else
                                                            <span class="badge bg-secondary">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('operaciones.generar-pdf-individual', [$prestamo->id, $relacionada->id]) }}"
                                                        class="btn btn-sm btn-primary"
                                                        target="_blank"
                                                        title="Generar PDF de la operación">
                                                            <i class="fas fa-file-pdf"></i>
                                                        </a>
                                                    </td>
                                                    {{-- Columna vacía para operaciones sin cuotas ni moras --}}
                                                    <td class="text-center">
                                                        <span class="text-muted small">-</span>
                                                    </td>
                                                </tr>
                                            @endif
                                        @empty
                                            {{-- No hay operaciones relacionadas, pero verificar si la operación general tiene moras --}}
                                        @endforelse
                                        
                                        {{-- Mostrar moras de la operación general si existen (excluir regularizadas) --}}
                                        @php
                                            $morasOperacionVisibles = $operacion->morasCuota->where('estado', '!=', \App\Enums\MoraCuotaEstado::REGULARIZADA->value);
                                        @endphp
                                        @if($morasOperacionVisibles->isNotEmpty())
                                            @foreach($morasOperacionVisibles as $moraPagada)
                                                <tr class="bg-warning-light">
                                                    <td>{{ $operacion->id }}</td>
                                                    <td>
                                                        <span class="badge bg-warning text-dark">
                                                            Cuota {{ optional($moraPagada->cuota)->numero ?? 'N/A' }}
                                                        </span>
                                                        <br>
                                                        <small class="text-warning">
                                                            <i class="fas fa-clock me-1"></i>MORA
                                                        </small>
                                                    </td>
                                                    <td>{{ \Carbon\Carbon::parse($operacion->created_at)->format('d-m-Y') }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($operacion->fecha)->format('d-m-Y') }}</td>
                                                    <td class="text-warning fw-bold">S/. {{ number_format($moraPagada->monto_pagado, 2) }}</td>
                                                    <td>{{ optional($operacion->prestamo->cuenta)->codigo ?? 'N/A' }}</td>
                                                    <td class="d-none d-md-table-cell">{{ optional($operacion->metodoDePago)->metodo_pago ?? 'N/A' }}</td>
                                                    <td>{{ $operacion->codigo ?? 'N/A' }}</td>
                                                    <td class="d-none d-md-table-cell">{{ optional($operacion->user)->name ?? 'N/A' }}</td>
                                                    <td class="d-none d-lg-table-cell small text-muted">
                                                        Mora: {{ $moraPagada->dias ?? 'N/A' }} días - 
                                                        @php
                                                            $estadoMora = is_object($moraPagada->estado) ? $moraPagada->estado->value : $moraPagada->estado;
                                                        @endphp
                                                        Estado: {{ $estadoMora == 1 ? 'PARCIAL' : ($estadoMora == 2 ? 'PAGADO' : ($estadoMora == 3 ? 'REGULARIZADA' : 'PENDIENTE')) }}
                                                    </td>
                                                    <td>
                                                        @if($operacion->voucher_path && in_array($operacion->metodoDePago->id ?? 0, [2, 3, 4, 5]))
                                                            <button type="button"
                                                                class="btn btn-sm btn-success ver-recibo"
                                                                data-toggle="modal"
                                                                data-target="#modalRecibo"
                                                                data-title="Comprobante #{{ $operacion->id }}"
                                                                data-ruta="{{ asset('storage/' . $operacion->voucher_path) }}"
                                                                data-extension="{{ pathinfo($operacion->voucher_path, PATHINFO_EXTENSION) }}"
                                                                title="Ver comprobante">
                                                                <i class="fas fa-receipt"></i>
                                                            </button>
                                                        @else
                                                            <span class="badge bg-secondary">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('operaciones.generar-pdf-individual', [$prestamo->id, $operacion->id]) }}"
                                                        class="btn btn-sm btn-primary"
                                                        target="_blank"
                                                        title="Generar PDF de la operación">
                                                            <i class="fas fa-file-pdf"></i>
                                                        </a>
                                                    </td>
                                                    {{-- Nueva columna: Acciones específicas para la MORA --}}
                                                    <!--td class="text-center">
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ route('admin.moras.edit', $moraPagada->id) }}" 
                                                               class="btn btn-sm btn-outline-warning me-1"
                                                               title="Editar Mora ID: {{ $moraPagada->id }}">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="{{ route('admin.moras.anular.show', $moraPagada->id) }}" 
                                                               class="btn btn-sm btn-outline-danger"
                                                               title="Anular Mora ID: {{ $moraPagada->id }}">
                                                                <i class="fas fa-ban"></i>
                                                            </a>
                                                        </div>
                                                    </td-->
                                                </tr>
                                            @endforeach
                                        @endif
                                        
                                        {{-- Mostrar mensaje si no hay operaciones relacionadas ni moras --}}
                                        @if($operacion->operacionesRelacionadas->isEmpty() && $operacion->morasCuota->isEmpty())
                                            <tr>
                                                <td colspan="13" class="text-center py-3">
                                                    <i class="fas fa-info-circle text-muted me-2"></i>
                                                    No hay operaciones relacionadas
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>

        @endforeach

        @if($operacionesGenerales->isEmpty())
        <tr>
            <td colspan="7" class="text-center py-4">
                <div class="empty-state">
                    <i class="fas fa-file-invoice-dollar text-muted" style="font-size: 48px;"></i>
                    <p class="mt-2 mb-0">No hay operaciones registradas</p>
                </div>
            </td>
        </tr>
        @endif
        </tbody>
    </table>
</div>