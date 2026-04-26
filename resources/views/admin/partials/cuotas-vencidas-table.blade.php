<div class="table-responsive">
    <table class="table table-hover table-striped mb-0" id="tablaCuotasVencidas">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Préstamo</th>
                <th>Cuota N°</th>
                <th>Fecha Pago</th>
                <th>Días Vencidos</th>
                <th>Monto</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($cuotasVencidas) && $cuotasVencidas instanceof \Illuminate\Support\Collection)
                @forelse($cuotasVencidas as $cuota)
                    <tr class="{{ now()->diffInDays($cuota->fecha_pago) > 30 ? 'table-danger' : 'table-warning' }}">
                        <td>
                            {{ $cuota->prestamo->cliente->persona->nombres ?? 'N/A' }} 
                            {{ $cuota->prestamo->cliente->persona->ape_pat ?? '' }} 
                            {{ $cuota->prestamo->cliente->persona->ape_mat ?? '' }}
                        </td>
                        <td>{{ $cuota->prestamo->codigo ?? 'N/A' }}</td>
                        <td>{{ $cuota->numero }}</td>
                        <td>{{ $cuota->fecha_pago->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge badge-danger">
                                {{ now()->diffInDays($cuota->fecha_pago) }} días
                            </span>
                        </td>
                        <td>S/ {{ number_format($cuota->monto, 2) }}</td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.prestamos.show', $cuota->prestamo_id) }}" 
                                   class="btn btn-xs btn-info" title="Ver préstamo">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.gestiones.create', ['prestamo_id' => $cuota->prestamo_id]) }}" 
                                   class="btn btn-xs btn-warning" title="Registrar gestión">
                                    <i class="fas fa-phone-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-3">
                            <i class="far fa-check-circle text-success fa-2x mb-2"></i>
                            <p class="text-muted">No hay cuotas vencidas actualmente.</p>
                        </td>
                    </tr>
                @endforelse
            @else
                <tr>
                    <td colspan="7" class="text-center py-3">
                        <i class="far fa-check-circle text-success fa-2x mb-2"></i>
                        <p class="text-muted">No hay datos de cuotas vencidas disponibles.</p>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>