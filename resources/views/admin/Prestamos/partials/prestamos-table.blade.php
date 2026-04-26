@if ($prestamos->count())
    <table class="table table-bordered table-striped">
        <thead class="text-center">
            <tr>
                <th>ID</th>
                <th>Nombres</th>
                <th>DNI</th>
                <th>Estado</th>
                <th>Fecha de Primer Pago</th>
                <th>Opciones</th>
            </tr>
        </thead>
        <tbody class="text-center">
            @foreach ($prestamos as $prestamo)
                <tr>
                    <td>{{ $prestamo->id }}</td>
                    <td>{{ $prestamo->cliente->persona->nombres }} {{ $prestamo->cliente->persona->ape_pat }} {{ $prestamo->cliente->persona->ape_mat }}</td>
                    <td>{{ $prestamo->cliente->persona->documento }}</td>
                    <td><span class="badge badge-{{ $prestamo->estado_color }}">{{ $prestamo->estado }}</span></td>
                    <td>{{ $prestamo->fecha_atencion }}</td>
                    <td>
                        <div class="btn-group">
                            @if ($prestamo->estado !== 'Vigente')
                                <button class="btn btn-success btn-sm btn-desembolsar" data-prestamo-id="{{ $prestamo->id }}">Desembolsar</button>
                            @endif
                            <a href="{{ route('admin.prestamos.show', $prestamo->id) }}" class="btn btn-primary btn-sm">Estado de Cuenta</a>
                            <a href="{{ route('admin.registrarpago.create', ['prestamo_id' => $prestamo->id]) }}" class="btn btn-primary btn-sm">Registrar Pagos</a>
                            <a href="{{ route('admin.gestioncobranza.create', ['prestamo' => $prestamo->id]) }}" class="btn btn-warning btn-sm">Registrar Gestión</a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="float-right mt-3">
        {{ $prestamos->links() }}
    </div>
@else
    <div class="text-center">
        <p class="font-weight-bold text-muted">No hemos encontrado algún registro coincidente</p>
    </div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Seleccionar todos los botones de desembolso
        const buttons = document.querySelectorAll('.btn-desembolsar');

        buttons.forEach((button) => {
            button.addEventListener('click', function () {
                const prestamoId = button.getAttribute('data-prestamo-id');
                
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: `¡Confirma la operación! Vamos a desembolsar el préstamo ${prestamoId}`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, desembolsar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Emitir evento de Livewire
                        Livewire.emit('desembolsarPrestamo', prestamoId);
                    }
                });
            });
        });

        // Escuchar el evento desde el componente de Livewire
        Livewire.on('prestamoDesembolsado', (response) => {
            Swal.fire({
                icon: response.icon,
                title: response.title,
                text: response.text,
                showConfirmButton: true,
            });
        });
    });
</script>
