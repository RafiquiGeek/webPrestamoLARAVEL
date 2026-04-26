<div>
    <input wire:model="search" type="text" placeholder="Buscar cliente..." />

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Préstamo</th>
                <th>Método de Pago</th>
                <th>Fecha</th>
                <th>Abono</th>
                <th>Tipo de Operación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($operaciones as $operacion)
                <tr>
                    <td>{{ $operacion->id }}</td>
                    <td>{{ $operacion->cliente->persona->nombres ?? 'N/A' }}</td>
                    <td>{{ $operacion->prestamo->id ?? 'N/A' }}</td>
                    <td>{{ $operacion->metodoDePago->nombre ?? 'N/A' }}</td>
                    <td>{{ $operacion->fecha }}</td>
                    <td>{{ $operacion->abono }}</td>
                    <td>{{ $operacion->tipo_operacion }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
