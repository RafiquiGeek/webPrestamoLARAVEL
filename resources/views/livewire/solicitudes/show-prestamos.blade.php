<div>
    <div class="card">
        <div class="card-header">
            <div class="d-flex w-full justify-content-around">
                <div class="row" style="margin-top: 20px; margin-bottom: 20px; margin-left: 10px;">
                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                        <label class="btn btn-outline-success m-2">
                            <input type="radio" name="options" id="option1" wire:click="updateSearch(1)" wire:loading.attr="disabled"> 
                            Aprobado <p>{{ $cant_aprobado }}</p>
                        </label>
                        <label class="btn btn-outline-info m-2">
                            <input type="radio" name="options" id="option2" wire:click="updateSearch(2)"> 
                            En Análisis <p>{{ $cant_analisis }}</p>
                        </label>
                        <label class="btn btn-outline-danger m-2">
                            <input type="radio" name="options" id="option4" wire:click="updateSearch(3)"> 
                            Finalizado <p>{{ $cant_finalizado }}</p>
                        </label>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-center">
                <a href="{{ route('admin.solicitudes.create') }}" class="btn btn-block btn-danger w-25 m-2">
                    <i class="fa-solid fa-hand-holding-dollar mr-1"></i> Nueva Solicitud
                </a>
            </div>
            <div class="d-flex">
                <input type="text" wire:model.live="search" class="form-control" placeholder="Buscar por DNI, Apellidos o Estado">
            </div>
        </div>

        <div class="card-body">
            @if ($prestamos->count())
                <table id="" class="table table-bordered table-striped">
                    <thead class="text-center">
                        <tr>
                            <th style="cursor: pointer;" wire:click="order('id')">ID 
                                @if ($sort == 'id')
                                    <i class="fa-solid fa-arrow-{{ $direction == 'asc' ? 'down' : 'up' }} float-right mt-1"></i>
                                @else
                                    <i class="fa-solid fa-sort float-right mt-1"></i>
                                @endif
                            </th>
                            <th style="cursor: pointer;" wire:click="order('nombre_cliente')">Nombres
                                @if ($sort == 'nombre_cliente')
                                    <i class="fa-solid fa-arrow-{{ $direction == 'asc' ? 'down' : 'up' }} float-right mt-1"></i>
                                @else
                                    <i class="fa-solid fa-sort float-right mt-1"></i>
                                @endif
                            </th>
                            <th>DNI</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Fecha de Atención</th>
                            <th>Opciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        @foreach ($prestamos as $prestamo)
                            <tr>
                                <td>{{ $prestamo->id }}</td>
                                <td>{{ $prestamo->cliente->persona->nombre }} {{ $prestamo->cliente->persona->apellido_paterno }}</td>
                                <td>{{ $prestamo->cliente->persona->documento }}</td>
                                <td>S/. {{ $prestamo->cantidad_solicitada }}</td>
                                <td>
                                    @if ($prestamo->estado == 'Aprobado')
                                        <span class="badge badge-success">Aprobado</span>
                                    @elseif ($prestamo->estado == 'En Análisis')
                                        <span class="badge badge-info">En Análisis</span>
                                    @elseif ($prestamo->estado == 'Finalizado')
                                        <span class="badge badge-danger">Finalizado</span>
                                    @endif
                                </td>
                                <td>{{ $prestamo->fecha_atencion }}</td>
                                <td>
                                    <div class="btn-group dropleft">
                                        <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                                            Acciones
                                        </button>
                                        <div class="dropdown-menu">
                                            <a href="{{ route('prestamos.edit', $prestamo->id) }}" class="dropdown-item">
                                                <i class="far fa-pen-to-square mr-1"></i> Editar
                                            </a>
                                            <form action="{{ route('prestamos.destroy', $prestamo->id) }}" method="POST" style="display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fas fa-trash mr-1"></i> Eliminar
                                                </button>
                                            </form>
                                        </div>
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
        </div>
    </div>
</div>
