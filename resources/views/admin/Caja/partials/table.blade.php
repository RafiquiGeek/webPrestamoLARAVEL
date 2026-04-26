<!-- Resumen de Usuarios con Pendientes 
@if(isset($resumenUsuarios) && count($resumenUsuarios) > 0)
<div class="account-card mb-3">
    <div class="card-header">
        <h3><i class="fas fa-users-cog me-2"></i>Acciones Rápidas de Rendición</h3>
    </div>
    <div class="card-body">
        <div class="row g-2">
            @foreach($resumenUsuarios->where('efectivo_por_rendir', '>', 0) as $usuario)
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="d-flex justify-content-between align-items-center info-card">
                        <div>
                            <div class="info-label">{{ $usuario['codigo'] }}</div>
                            <small class="text-warning">S/ {{ number_format($usuario['efectivo_por_rendir'], 2) }}</small>
                        </div>
                        <a href="{{ route('admin.caja.mostrarRendicionUsuario', $usuario['user_id']) }}" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-hand-holding-usd"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif-->

<!-- Tabla de Operaciones -->
<div class="account-card">
    <div class="card-header">
        <h3><i class="fas fa-table me-2"></i>Detalle de Operaciones</h3>
        @if(isset($resumenUsuarios))
            @php
                $totalPendiente = $resumenUsuarios->sum('efectivo_por_rendir');
            @endphp
            @if($totalPendiente > 0)
                <button type="button" class="btn btn-outline-primary btn-sm" id="rendir_todo_efectivo">
                    <i class="fas fa-hand-holding-usd me-1"></i>
                    Rendir Todo (S/ {{ number_format($totalPendiente, 2) }})
                </button>
            @endif
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Método de Pago</th>
                        <th>Fecha</th>
                        <th class="text-end">Monto</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($operaciones as $op)
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark">{{ $op->id }}</span>
                            </td>
                            <td>
                                <div class="info-label">{{ optional($op->user)->codigo ?? 'N/A' }}</div>
                                <small class="text-muted">{{ optional($op->user)->name ?? 'Sin nombre' }}</small>
                            </td>
                            <td>
                                @php
                                    $metodoBadgeClass = [
                                        1 => 'success', // Efectivo
                                        2 => 'info',    // Transferencia
                                        3 => 'info',    // Depósito
                                        4 => 'secondary', // Yape
                                        5 => 'secondary'  // Plin
                                    ][$op->metodo_pago_id] ?? 'light';
                                @endphp
                                <span class="badge bg-{{ $metodoBadgeClass }}">
                                    {{ optional($op->metodoDePago)->metodo_pago ?? 'N/A' }}
                                </span>
                            </td>
                            <td>
                                <div class="info-value small">{{ \Carbon\Carbon::parse($op->fecha)->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ \Carbon\Carbon::parse($op->fecha)->format('H:i') }}</small>
                            </td>
                            <td class="text-end">
                                <div class="info-value">S/ {{ number_format($op->abono, 2) }}</div>
                            </td>
                            <td class="text-center">
                                @switch($op->metodo_pago_id)
                                    @case(1)
                                        @if($op->estado_rendicion == 1)
                                            <span class="badge bg-success">Rendido</span>
                                        @else
                                            <span class="badge bg-warning">Pendiente</span>
                                        @endif
                                        @break
                                    @default
                                        <span class="badge bg-secondary">No Aplica</span>
                                @endswitch
                            </td>
                            <td class="text-center">
                                @if($op->metodo_pago_id == 1 && $op->estado_rendicion == 0)
                                    <form action="{{ route('admin.caja.updateEstado', $op->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="estado_rendicion" value="1">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                @elseif($op->metodo_pago_id == 1 && $op->estado_rendicion == 1)
                                    <i class="fas fa-check-circle text-success"></i>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div>
                                    <i class="fas fa-search fa-2x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No se encontraron operaciones</p>
                                    <small class="text-muted">Intenta ajustar los filtros de búsqueda</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($operaciones->count() > 0)
                <tfoot class="table-light">
                    <tr>
                        <th colspan="4" class="text-end">Total Filtrado:</th>
                        <th class="text-end">
                            <div class="info-value">S/ {{ number_format($total_abono ?? 0, 2) }}</div>
                        </th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        
        @if($operaciones->hasPages())
        <div class="card-footer">
            {{ $operaciones->appends(request()->input())->links('pagination.bootstrap-5') }}
        </div>
        @endif
    </div>
</div>