@php
    // $tipo === 'ventas' || 'compras'
    $title = $tipo === 'compras' ? 'Compras' : 'Ventas';
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>{{ $title }}</span>
        <div>
            <a href="#" class="btn btn-sm btn-primary">Nuevo</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm" id="comprobantes-table-{{ $tipo }}">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Serie-Número</th>
                        <th>Cliente / Proveedor</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Placeholder: server-side render or fetch via AJAX --}}
                    @forelse($comprobantes ?? [] as $c)
                        <tr>
                            <td>{{ $c->created_at ?? '-' }}</td>
                            <td>{{ $c->tipo_comprobante ?? '-' }}</td>
                            <td>{{ $c->serie ?? '' }}-{{ $c->numero ?? '' }}</td>
                            <td>{{ $c->nombre_cliente ?? $c->razon_social ?? '-' }}</td>
                            <td>{{ number_format($c->total ?? 0, 2) }}</td>
                            <td>{{ $c->estado ?? '-' }}</td>
                            <td>
                                <a href="#" class="btn btn-sm btn-outline-secondary">Ver</a>
                                <a href="#" class="btn btn-sm btn-outline-primary">Reenviar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No hay comprobantes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
