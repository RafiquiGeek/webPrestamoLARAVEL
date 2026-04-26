<div>
    <div class="card">
        <div class="card-header">
            <div class="d-flex">
                <input type="text" wire:model.live="search" class="form-control" placeholder="Buscar por Nombre">
            </div>
        </div>
        <div class="card-body">

            @if ($tasas->count())
                <table id="" class="table table-bordered table-striped">
                    <thead class="text-center">
                        <tr>
                            <th>ID</th>
                            <th>Tipo de Tasa</th>
                            <th>Valor</th>
                            <th>Fecha de Creacion</th>
                            <th>Opciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        @foreach ($tasas as $tasa)
                        <tr>
                            <td>{{$tasa->id}}</td>
                            <td>{{$tasa->tipo_tasa}}</td>
                            <td>{{$tasa->valor}}</td>
                            <td>{{$tasa->created_at}}</td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                                        Acciones
                                    </button>
                                    <div class="dropdown-menu">
                                        <a href="{{ route('admin.tasas.edit', ['tasa' => $tasa->id ]) }}" class="dropdown-item"><i class="fas fa-pen-to-square mr-1"></i>Editar Tasa</a>
                                        <a href="#" class="dropdown-item"><i class="fas fa-trash mr-1"></i>Eliminar Tasa</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        
                        @endforeach
                    </tbody>
                </table>
                {{-- <div class="float-right mt-3">
                    {{$tasa->links()}}
                </div> --}}
            @else
                <div class="text-center">
                    <p class="font-weight-bold text-muted">No hemos encontrado algun registro coincidente</p>
                </div>
            @endif
            
        </div>
    </div>  
</div>
