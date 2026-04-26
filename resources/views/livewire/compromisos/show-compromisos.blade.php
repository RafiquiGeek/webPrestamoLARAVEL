<div>
    <div class="card">
        <div class="card-header">
            <div class="d-flex">
                <input type="text" wire:model.live="search" class="form-control" placeholder="Buscar por DNI, Apellidos o Estado">
            </div>
        </div>
        <div class="card-body">

            @if ($compromisos)
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
                            <th style="cursor: pointer;" wire:click="order('cliente')">Cliente
                            <!-- Sort -->
                            @if ($sort == 'cliente')
                                @if ($direction == 'asc')
                                    <i class="fa-solid fa-arrow-down-a-z float-right mt-1"></i>
                                @else
                                    <i class="fa-solid fa-arrow-down-z-a float-right mt-1"></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right mt-1"></i>
                            @endif</th>
                            <th style="cursor: pointer;" wire:click="order('dni')">DNI
                                <!-- Sort -->
                                @if ($sort == 'dni')
                                    @if ($direction == 'asc')
                                        <i class="fa-solid fa-arrow-down-1-9 float-right mt-1"></i>
                                    @else
                                        <i class="fa-solid fa-arrow-down-9-1 float-right mt-1"></i>
                                    @endif
                                @else
                                    <i class="fa-solid fa-sort float-right mt-1"></i>
                                @endif</th>
                            <th style="cursor: pointer;" wire:click="order('estado')">Estado
                            <!-- Sort -->
                            @if ($sort == 'estado')
                                @if ($direction == 'asc')
                                    <i class="fa-solid fa-arrow-down-1-9 float-right mt-1"></i>
                                @else
                                    <i class="fa-solid fa-arrow-down-9-1 float-right mt-1"></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right mt-1"></i>
                            @endif</th>
                            <th style="cursor: pointer;" wire:click="order('fecha_compromiso')">Fecha
                            <!-- Sort -->
                            @if ($sort == 'fecha_compromiso')
                                @if ($direction == 'asc')
                                    <i class="fa-solid fa-arrow-down-1-9 float-right mt-1"></i>
                                @else
                                    <i class="fa-solid fa-arrow-down-9-1 float-right mt-1"></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right mt-1"></i>
                            @endif</th>
                            <th style="cursor: pointer;" wire:click="order('hora')">Hora
                            <!-- Sort -->
                            @if ($sort == 'hora')
                                @if ($direction == 'asc')
                                    <i class="fa-solid fa-arrow-down-1-9 float-right mt-1"></i>
                                @else
                                    <i class="fa-solid fa-arrow-down-9-1 float-right mt-1"></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right mt-1"></i>
                            @endif</th>
                            <th style="cursor: pointer;" wire:click="order('monto_compromiso')">Monto
                            <!-- Sort -->
                            @if ($sort == 'monto_compromiso')
                                @if ($direction == 'asc')
                                    <i class="fa-solid fa-arrow-down-1-9 float-right mt-1"></i>
                                @else
                                    <i class="fa-solid fa-arrow-down-9-1 float-right mt-1"></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right mt-1"></i>
                            @endif</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        @foreach ($compromisos as $compromiso)
                        <tr>
                            <td>{{$compromiso->id}}</td>
                            <td>{{$compromiso->nombre_cliente}}</td>
                            <td>{{$compromiso->cliente->documento}}</td>
                            <td>{{$compromiso->estado}}</td>
                            <td>{{$compromiso->fecha_compromiso}}</td>
                            <td>{{$compromiso->hora}}</td>
                            <td>{{"S/ ".$compromiso->monto_compromiso}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-center">
                    <p class="font-weight-bold text-muted">No hemos encontrado algun registro coincidente</p>
                </div>
            @endif
            
        </div>
    </div>  
</div>
