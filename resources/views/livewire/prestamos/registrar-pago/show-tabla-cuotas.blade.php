<div>
    <div class="card card-secondary">
        <div class="card-header">
            <div class="card-title">
                Pago de Cuotas
            </div>
        </div>
        <div class="card-body">
            <table id="" class="table table-sm">
                <thead>
                    <tr>
                        <th class="d-none">ID de Cuota</th>
                        <th>N° Cuota</th>
                        <th>Fecha de Vencimiento</th>
                        <th>Interes</th>
                        <th>Cuota</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $index => $row)
                        <tr>
                            <td class="d-none">
                                <input type="text" class="form-control" value="{{ $row['column1'] }}" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control" value="{{ $row['column5'] }}" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($row['column3'])->format('d-m-Y') }}" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control" value="{{"S/ ".$row['column2']}}" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control" value="{{"S/ ".$row['column4']}}" readonly>
                            </td>
                            @if($row['column1'] != $baseRowId && $isLastRow && $loop->last)
                                <td><button wire:click.prevent="eliminarCuota({{ $row['column1'] }})" class="btn btn-danger"><i class="fa-solid fa-xmark"></i></button></td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td class="text-primary font-weight-bold align-middle pl-3" id="subTotal">S/  {{$totalInteres}}</td>
                        <td class="text-primary font-weight-bold align-middle pl-3" id="subTotal">S/ {{$subtotal}}</td>
                    </tr>
                    
                </tfoot>
            </table>
        </div>
        <div class="card-footer">
            <div class="row">
                <div class="col-1">
                    <input type="number" class="form-control" value="1">
                </div>
                <div class="col-2">
                    <button wire:click.prevent='agregarCuota' class="btn btn-success">Agregar Cuotas</button>
                </div>
            </div>
        </div>
    </div>
</div>