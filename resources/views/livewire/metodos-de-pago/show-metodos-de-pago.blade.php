<div>
    <div class="card">
        <div class="card-header">
            <div class="d-flex">
                <a href="{{ route('admin.metodosdepago.create') }}" class="btn btn-block btn-danger w-25 m-2"><i class="fa-solid fa-user-plus mr-1"></i>Nuevo Metodo de Pago</a>
                <button class="btn btn-block btn-success w-25 m-2" wire:click='export'><i class="fa fa-file-excel mr-1"></i>Descargar Padrón</button>
                <button class="btn btn-block btn-primary w-25 m-2" wire:click='export1'><i class="fa-solid fa-circle-plus mr-1"></i>Asignar otros conceptos</button>
                <a href="#" class="btn btn-block btn-dark w-25 m-2"><i class="fa-solid fa-file-pen mr-1"></i>Actualizar Status</a>
            </div>
            <div class="d-flex">
                <input type="text" wire:model.live="search" class="form-control" placeholder="Buscar por DNI, Apellidos o Estado">
            </div>
        </div>
        <div class="card-body">

            @if ($metodos->count())
                <table id="" class="table table-bordered table-striped">
                    <thead class="text-center">
                        <tr>
                            <th style="cursor: pointer;" wire:click="order('id')">ID
                            <!-- Sort -->
                            @if ($sort == 'id')
                                @if ($direction == 'asc')
                                    <i class="fa-solid fa-arrow-down-1-9 float-right mt-1"></i>
                                @else
                                    <i class="fa-solid fa-arrow-down-9-1 float-right mt-1"></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right mt-1"></i>
                            @endif</th>
                            <th style="cursor: pointer;" wire:click="order('metodo_pago')">Metodo de Pago
                            <!-- Sort -->
                            @if ($sort == 'metodo_pago')
                                @if ($direction == 'asc')
                                    <i class="fa-solid fa-arrow-down-a-z float-right mt-1"></i>
                                @else
                                    <i class="fa-solid fa-arrow-down-z-a float-right mt-1"></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right mt-1"></i>
                            @endif</th>
                            <th style="cursor: pointer;" wire:click="order('created_at')">Fecha Creacion
                            <!-- Sort -->
                            @if ($sort == 'created_at')
                                @if ($direction == 'asc')
                                    <i class="fa-solid fa-arrow-down-1-9 float-right mt-1"></i>
                                @else
                                    <i class="fa-solid fa-arrow-down-9-1 float-right mt-1"></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right mt-1"></i>
                            @endif</th>
                            <th>Opciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        @foreach ($metodos as $metodo)
                        <tr>
                            <td>{{$metodo->id}}</td>
                            <td>{{$metodo->metodo_pago}}</td>
                            <td>{{$metodo->created_at}}</td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                                        Acciones
                                    </button>
                                    <div class="dropdown-menu">
                                        <!-- PODRIAMOS PONER DISTINTOS COLORES DE TEXTO A LAS OPCIONES PARA UN ESTILO (TEXT-DANGER) -->
                                        <a href="{{ route('admin.metodosdepago.edit', [$metodo->id]) }}" class="dropdown-item"><i class="fas fa-pen-to-square mr-1"></i>Editar Metodo</a>
                                        <a href="#" class="dropdown-item"><i class="fas fa-trash mr-1"></i>Eliminar Metodo</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="float-right mt-3">
                    {{$metodos->links()}}
                </div>
            @else
                <div class="text-center">
                    <p class="font-weight-bold text-muted">No hemos encontrado algun registro coincidente</p>
                </div>
            @endif
            
        </div>
    </div>  
</div>
