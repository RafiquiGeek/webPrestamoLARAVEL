@extends('layouts.admin')

@section('title', 'Gestión de Cobranza')

@section('content_header')
    <h1 class="m-0 text-dark">Gestión de Cobranza</h1>
@stop

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.gestioncobranza.store') }}" method="POST">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="row">

                    <!-- Inputs ocultos para IDs -->
                    <input type="hidden" name="prestamo_id" value="{{ $prestamo->id }}">
                    <input type="hidden" name="cliente_id" value="{{ $prestamo->cliente_id }}">

                    <!-- Nombre del Cliente -->
                    <div class="col-12">
                        <div class="form-group">
                            <label for="cliente">Cliente</label>
                            <input type="text" class="form-control" name="cliente" id="cliente" value="{{ $prestamo->cliente->persona->nombres }} {{ $prestamo->cliente->persona->ape_pat }} {{ $prestamo->cliente->persona->ape_mat }}" readonly>
                        </div>
                    </div>

                    <!-- Fecha de Operación -->
                    <div class="col-6">
                        <div class="form-group">
                            <label for="fechaOperacion">Fecha de operación</label>
                            <input type="date" class="form-control" name="fechaOperacion" id="fechaOperacion" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="col-6">
                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select class="form-control select2" id="estado" name="estado" required>
                                <option value="" disabled selected>Selecciona o agrega un estado</option>
                                @foreach ($estados as $estado)
                                    <option value="{{ $estado->id }}">{{ $estado->estado }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>


                    <!-- Capital -->
                    <div class="col-6">
                        <div class="form-group">
                            <label for="capital">Capital</label>
                            <input type="number" step="0.01" class="form-control" name="capital" id="capital" value="{{ $prestamo->cantidad_solicitada }}" readonly>
                        </div>
                    </div>

                    <!-- Saldo Préstamo -->
                    <div class="col-6">
                        <div class="form-group">
                            <label for="saldoPrestamo">Saldo Préstamo</label>
                            <input type="number" step="0.01" class="form-control" name="saldoPrestamo" id="saldoPrestamo" value="{{ $saldo_prestamo }}" readonly>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="col-12">
                        <div class="form-group">
                            <label for="observaciones">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                        </div>
                    </div>

                    <!-- Compromiso de Pago -->
<div class="col-12">
    <div class="form-group">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="compromisoPago" name="compromisoPago" value="1">
            <label class="custom-control-label" for="compromisoPago">Compromiso de Pago?</label>
        </div>
    </div>
</div>

<!-- Sección de Compromiso de Pago -->
<div class="col-12" id="compromiso-pago-section" hidden>
    <div class="row">
        <div class="col-4">
            <div class="form-group">
                <label for="fecha">Fecha</label>
                <input type="date" class="form-control" name="fecha" id="fecha" value="{{ date('Y-m-d') }}">
            </div>
        </div>
        <div class="col-4">
            <div class="form-group">
                <label for="hora">Hora</label>
                <input type="time" class="form-control" name="hora" id="hora" value="{{ date('H:i') }}">
            </div>
        </div>
        <div class="col-4">
            <div class="form-group">
                <label for="monto">Monto</label>
                <input type="number" step="0.01" class="form-control" name="monto" id="monto">
            </div>
        </div>
    </div>
</div>

                </div>    

                <!-- Botones -->
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-between mb-4">
                            <a href="{{ route('admin.prestamos.index') }}" class="btn btn-lg btn-dark mr-4">
                                <i class="fa-solid fa-right-from-bracket mr-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-lg btn-success">
                                <i class="fa-solid fa-floppy-disk mr-1"></i>Enviar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#estado').select2({
                tags: true,
                placeholder: "Selecciona o agrega un estado",
                allowClear: true
            });
        });

        document.getElementById('compromisoPago').addEventListener('change', function() {
            var compromisoSection = document.getElementById('compromiso-pago-section');
            if (this.checked) {
                compromisoSection.removeAttribute('hidden');
            } else {
                compromisoSection.setAttribute('hidden', '');
            }
        });

        window.onload = function() {
            var hora = document.getElementById('hora');
            setInterval(function() {
                var now = new Date();
                var hours = now.getHours().toString().padStart(2, '0');
                var minutes = now.getMinutes().toString().padStart(2, '0');
                hora.value = hours + ':' + minutes;
            }, 1000);
        };
    </script>
@stop

