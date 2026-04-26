<div>
    <div class="card">
        <div class="card-header">
            <div class="d-flex w-full justify-content-around">
                <div class="row " style="margin-top: 20px;margin-bottom: 20px;margin-left: 10px;">

                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                        <label class="btn btn-outline-success m-2">
                            <input type="radio" name="options" id="option1" wire:click="updateSearch('Aprobado')" wire:loading.attr="disabled"> Aprobado <p>{{$cant_aprobado}}</p>
                        </label>                          
                        <label class="btn btn-outline-info m-2">
                          <input type="radio" name="options" id="option2" wire:click="updateSearch('En Analisis')"> En Analisis <p>{{$cant_analisis}}</p>
                        </label>
                        <!--label class="btn btn-outline-warning m-2">
                          <input type="radio" name="options" id="option3" wire:click="updateSearch('En Espera')"> En Espera <p>{{$cant_espera}}</p>
                        </!--label-->
                        <label class="btn btn-outline-danger m-2">
                            <input type="radio" name="options" id="option4" wire:click="updateSearch('Finalizado')"> Finalizado <p>{{$cant_finalizado}}</p>
                        </label>
                    </div>

                </div>
            </div>
            <div class="d-flex justify-content-center">
                <a href="{{ route('admin.solicitudes.create') }}" class="btn btn-block btn-danger w-25 m-2"><i class="fa-solid fa-hand-holding-dollar mr-1"></i>Nueva Solicitud</a>
                <button class="btn btn-block btn-success w-25 m-2" wire:click='export'><i class="fa fa-file-excel mr-1"></i>Descargar Padrón</button>
            </div>
            <div class="d-flex">
                <input type="text" wire:model.live="search" class="form-control" placeholder="Buscar por DNI, Apellidos o Estado">
            </div>
        </div>
        <div class="card-body">

            @if ($solicitudes->count())
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
                            <th style="cursor: pointer;" wire:click="order('nombre_cliente')">Nombres
                            <!-- Sort -->
                            @if ($sort == 'nombre_cliente')
                                @if ($direction == 'asc')
                                    <i class="fa-solid fa-arrow-down-a-z float-right mt-1"></i>
                                @else
                                    <i class="fa-solid fa-arrow-down-z-a float-right mt-1"></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right mt-1"></i>
                            @endif</th>
                            <th style="cursor: pointer;" wire:click="order('')">DNI
                            <!-- Sort -->
                            @if ($sort == '')
                                @if ($direction == 'asc')
                                    <i class="fa-solid fa-arrow-down-1-9 float-right mt-1"></i>
                                @else
                                    <i class="fa-solid fa-arrow-down-9-1 float-right mt-1"></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right mt-1"></i>
                            @endif</th>
                            <th style="cursor: pointer;" wire:click="order('mon_sol')">Monto
                            <!-- Sort -->
                            @if ($sort == 'mon_sol')
                                @if ($direction == 'asc')
                                    <i class="fa-solid fa-arrow-down-1-9 float-right mt-1"></i>
                                @else
                                    <i class="fa-solid fa-arrow-down-9-1 float-right mt-1"></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right mt-1"></i>
                            @endif</th>
                            <th>Estado</th>
                            <th style="cursor: pointer;" wire:click="order('mon_sol')">Fondo Provicional
                                <!-- Sort -->
                                @if ($sort == 'mon_sol')
                                    @if ($direction == 'asc')
                                        <i class="fa-solid fa-arrow-down-1-9 float-right mt-1"></i>
                                    @else
                                        <i class="fa-solid fa-arrow-down-9-1 float-right mt-1"></i>
                                    @endif
                                @else
                                    <i class="fa-solid fa-sort float-right mt-1"></i>
                                @endif</th>
                            <th style="cursor: pointer;" wire:click="order('created_at')">Fecha de Creacion
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
                        @foreach ($solicitudes as $solicitud)
                        <tr>
                            <td>{{$solicitud->id}}</td>
                            <td>{{$solicitud->nombre_cliente}}</td>
                            <td>{{$solicitud->cliente->documento}}</td>
                            <td>S/. {{$solicitud->mon_sol}}</td>
                            <td>
                                @if ($solicitud->estado == 'Aprobado')
                                    <span class="badge badge-success">Aprobado</span>
                                @elseif ($solicitud->estado == 'En Análisis')
                                    <span class="badge badge-info">En Analisis</span>
                                @elseif ($solicitud->estado == 'En Espera')
                                    <span class="badge badge-warning">En Espera</span>
                                @else
                                    <span class="badge badge-danger">Finalizado</span>
                                @endif 
                            </td>
                            <td>
                                @if($solicitud->fondo_provi === 1)
                                    <a href="{{ route('admin.fondosprovicionales.show', ['fondo_provicional' => $solicitud->id]) }}" class="btn btn-success btn-sm"><i class="fas fa-file-pdf mr-1"></i>Fondo</a>
                                @elseif ($solicitud->fondo_provi === 0)
                                    
                                @endif
                            </td>
                            <td>{{$solicitud->fech_ate}}</td>
                            <td>
                                <div class="btn-group dropleft">
                                    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                                        Acciones
                                    </button>
                                    <div class="dropdown-menu">
                                        <a href="{{ route('admin.solicitudes.edit', ['solicitude' => $solicitud->id ]) }}" class="dropdown-item"><i class="far fa-pen-to-square mr-1"></i>Editar</a>
                                        <a href="{{ route('admin.solicitudes.show', ['solicitude' => $solicitud->id ]) }}" class="dropdown-item"><i class="fas fa-file-pdf mr-1"></i>Ver Cronograma</a>
                                        <a href="#" class="dropdown-item"><i class="fas fa-trash mr-1"></i>Eliminar</a>
                                        @if($solicitud->fondo_provi === 0)
                                            <a href="{{ route('admin.fondosprovicionales.create', ['solicitud_id' => $solicitud->id]) }}" class="dropdown-item"><i class="fas fa-money-bills mr-1"></i>Fondo Provicional</a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="float-right mt-3">
                    {{$solicitudes->links()}}
                </div>
            @else
                <div class="text-center">
                    <p class="font-weight-bold text-muted">No hemos encontrado algun registro coincidente</p>
                </div>
            @endif
            
        </div>

        <script>

            // Obtén todos los botones
            var buttons = document.querySelectorAll('.btn-group-toggle .btn');

            // Añade un controlador de eventos a cada botón
            buttons.forEach(function(button) {
                button.addEventListener('click', function() {
                    // Elimina la clase 'btn-transparent' y añade la clase 'btn-secondary' al botón clicado
                    this.classList.remove('btn-transparent');
                    this.classList.add('btn-secondary');

                    // Para los demás botones, elimina la clase 'btn-secondary' y añade la clase 'btn-transparent'
                    buttons.forEach(function(otherButton) {
                        if (otherButton !== this) {
                            otherButton.classList.remove('btn-secondary');
                            otherButton.classList.add('btn-transparent');
                        }
                    }, this);
                });
            });

        </script>

    </div>  
</div>
