@extends('layouts.admin')

@section('title', 'Editar Préstamo')

@section('content')
<div class="container-fluid pt-4">
    <div class="row">
        <!-- Formulario de Edición de Préstamo -->
        <div class="col-md-8">
        <form action="{{ route('admin.prestamos.update', ['prestamo' => $prestamo->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
                <!-- Información Principal -->
                <div class="card shadow border-0 mb-4">
                    <div class="card-body">
                        <h4 class="m-0 text-primary font-weight-bold mb-3">Editar Préstamo</h4>
                        <div class="row">
                            <!-- Estado -->
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label class="text-muted">Estado</label>
                                    <select class="form-control" name="estado">
                                        <option value="En Análisis" {{ $prestamo->estado == 'En Análisis' ? 'selected' : '' }}>En Análisis</option>
                                        <option value="Aprobado" {{ $prestamo->estado == 'Aprobado' ? 'selected' : '' }}>Aprobado</option>
                                        <option value="Rechazado" {{ $prestamo->estado == 'Rechazado' ? 'selected' : '' }}>Rechazado</option>
                                        <!-- Agrega más opciones si es necesario -->
                                    </select>
                                </div>
                            </div>

                            <!-- Cliente -->
                            <div class="col-md-7">
                                <div class="form-group">
                                    <label class="text-muted">Cliente</label>
                                    <div class="input-group d-flex flex-wrap">
                                        <select class="custom-select select2" name="cliente_id" id="selectCliente" style="width: 70%;" required>
                                            <option value="" disabled>Selecciona un cliente</option>
                                            @foreach($clientes as $cliente)
                                            <option value="{{ $cliente->id }}" {{ $prestamo->cliente_id == $cliente->id ? 'selected' : '' }}>
                                                {{ $cliente->persona->nombres }} {{ $cliente->persona->ape_pat }} {{ $cliente->persona->ape_mat }}
                                            </option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-append" style="width: 30%;">
                                            <button type="button" id="btnConsultarPrestamos" class="btn btn-primary">Consultar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dirección de Cobro -->
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="text-muted">Dirección de Cobro</label>
                                    <select class="custom-select select2" name="direccion_cobro_id" id="selectDireccionCobro" required>
                                        <option value="" selected disabled>Selecciona una dirección</option>
                                        <!-- Las direcciones se cargarán con AJAX y se preseleccionarán -->
                                    </select>
                                </div>
                            </div>

                            <!-- Cuenta Cliente -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-muted">Cta Cliente (Op)</label>
                                    <select name="cuenta_cliente_id" class="form-control" id="selectCuentaCliente">
                                        <option value="">Seleccione una cuenta</option>
                                        <!-- Las cuentas se cargarán con AJAX y se preseleccionarán -->
                                    </select>
                                </div>
                            </div>

                            <!-- Tabla para mostrar los préstamos del cliente o cónyuge -->
                            <div class="col-md-12 mt-3">
                                <div id="messageContainer" style="display: none;">
                                    <p id="message" class="text-danger"></p>
                                </div>
                                <div id="tableContainer" style="display: none;">
                                    <table class="table table-bordered" id="tablaPrestamos">
                                        <thead>
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Cliente/Cónyuge</th>
                                                <th>Estado del Préstamo</th>
                                                <th>Fecha de Solicitud</th>
                                                <th>Fecha de Primer Pago</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>

                        </div>

                        <!-- Analista, Asesor y JCC -->
                        <div class="row">
                            <!-- Analista -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-muted">Analista</label>
                                    <select class="form-control select2" name="analista_id" required>
                                        @foreach ($analistas as $analista)
                                        <option value="{{ $analista->id }}" {{ $prestamo->analista_id == $analista->id ? 'selected' : '' }}>
                                            {{ $analista->codigo }} - {{ $analista->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Asesor -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-muted">Asesor</label>
                                    <select class="form-control select2" name="asesor_id" required>
                                        @foreach ($asesores as $asesor)
                                        <option value="{{ $asesor->id }}" {{ $prestamo->asesor_id == $asesor->id ? 'selected' : '' }}>
                                            {{ $asesor->codigo }} - {{ $asesor->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- JCC -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-muted">JCC</label>
                                    <select class="form-control select2" name="jcc_id" required>
                                        @foreach ($jccs as $jcc)
                                        <option value="{{ $jcc->id }}" {{ $prestamo->jcc_id == $jcc->id ? 'selected' : '' }}>
                                            {{ $jcc->codigo }} - {{ $jcc->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Aval -->
                        <h4 class="m-0 text-primary font-weight-bold mb-3">Aval</h4>
                        <div class="">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="inputDni" class="form-label text-muted">DNI</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="Ingresa el DNI del Aval" id="inputDni">
                                            <button class="btn btn-outline-success" type="button" id="btnAsignar">
                                                <i class="fas fa-user-plus me-1"></i> Asignar
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label text-muted">Nombre del Aval</label>
                                        <p id="nombreCliente" class="fw-bold fs-6">---</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row" id="infoAvalContainer" style="display: none;">
                                <div class="col-md-12 mb-2">
                                    <button type="button" id="btnCerrarInfoAval" class="btn btn-sm btn-secondary float-right">Cerrar</button>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <h5 class="text-secondary mb-3">Información del Aval</h5>
                                    <table class="table table-bordered table-striped table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Es Cliente</th>
                                                <th>Estado Préstamo</th>
                                                <th>Etiquetas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td id="esCliente">---</td>
                                                <td id="estadoPrestamo">---</td>
                                                <td id="etiquetasCliente">---</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <h5 class="text-secondary mb-3">Aval de los siguientes clientes</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nombre</th>
                                                    <th>Estado Préstamo</th>
                                                    <th>Etiquetas</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaAvalados">
                                                <tr>
                                                    <td class="nombreAvalado">---</td>
                                                    <td class="estadoPrestamoAvalado">---</td>
                                                    <td class="etiquetasAvalado">---</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Detalles de Cuentas y Fechas -->
                <div class="card shadow border-0 mb-4">
                    <div class="card-body">
                        <h4 class="m-0 text-primary font-weight-bold mb-3">Cuentas / Fechas</h4>
                        <div class="container">
                            <div class="row d-flex flex-wrap">
                                <!-- Cuenta Asignada -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="text-muted">Cuenta Asignada</label>
                                        <select class="form-control select2" name="cuenta_id" required>
                                            <option value="" disabled>Selecciona</option>
                                            @foreach($cuentas as $cuenta)
                                            <option value="{{ $cuenta->id }}" {{ $prestamo->cuenta_id == $cuenta->id ? 'selected' : '' }}>
                                                {{ $cuenta->codigo }} - {{ $cuenta->entidadBancaria->banco }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <!-- Fecha Atención -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="text-muted">F. Atención</label>
                                        <input type="date" class="form-control" id="fecha_atencion" name="fecha_atencion" value="{{ $prestamo->fecha_atencion->format('Y-m-d') }}" required>
                                    </div>
                                </div>
                                <!-- Fecha Primer Pago -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="text-muted">F. Primer Pago</label>
                                        <input type="date" class="form-control" id="fecha_primer_pago" name="fecha_primer_pago" value="{{ $prestamo->fecha_primer_pago->format('Y-m-d') }}" required>
                                    </div>
                                </div>
                                <!-- Tipo Solicitud -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="text-muted">Tipo Solicitud</label>
                                        <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                                            <label class="btn btn-outline-primary {{ $prestamo->tipo_solicitud == 'Nueva' ? 'active' : '' }}">
                                                <input type="radio" name="tipo_solicitud" value="Nueva" {{ $prestamo->tipo_solicitud == 'Nueva' ? 'checked' : '' }}> Nueva
                                            </label>
                                            <label class="btn btn-outline-primary {{ $prestamo->tipo_solicitud == 'Renovación' ? 'active' : '' }}">
                                                <input type="radio" name="tipo_solicitud" value="Renovación" {{ $prestamo->tipo_solicitud == 'Renovación' ? 'checked' : '' }}> Renovación
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Plazo -->
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="text-muted">Plazo</label>
                                        <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                                            @foreach([8, 12, 15, 18, 20] as $plazo)
                                            <label class="btn btn-outline-success {{ $prestamo->plazo == $plazo ? 'active' : '' }}">
                                                <input type="radio" name="plazo" value="{{ $plazo }}" {{ $prestamo->plazo == $plazo ? 'checked' : '' }}> {{ $plazo }} semanas
                                            </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Frecuencia de Pago y Cantidades -->
                        <div class="row mb-12">
                            <!-- Cantidad Solicitada -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="text-muted">Cantidad Solicitada</label>
                                    <input type="number" class="form-control" placeholder="0.00" id="cantidad_solicitada" name="cantidad_solicitada" value="{{ $prestamo->cantidad_solicitada }}" min="0" step="0.01" required>
                                </div>
                            </div>
                            <!-- Tasa de Interés -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="text-muted">Tasa de Interés (%)</label>
                                    <input type="number" class="form-control" name="tasa_interes" id="tasa_interes" value="{{ $prestamo->tasa_interes }}" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-12">
                            <!-- Mora -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="text-muted">Mora</label>
                                    <input type="number" class="form-control" name="mora" id="mora" value="{{ $prestamo->mora }}" step="0.01">
                                </div>
                            </div>
                            <!-- Frecuencia de pago (Oculto) -->
                            <input type="hidden" name="frecuencia_pago" value="semanal">
                        </div>

                        <!-- Observaciones -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="text-muted">Observaciones</label>
                                    <textarea class="form-control" rows="3" name="observaciones">{{ $prestamo->observaciones }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="d-flex justify-content-between mt-3">
                            <a href="{{ route('admin.prestamos.index') }}" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-times-circle mr-1"></i> Cancelar
                            </a>

                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save mr-1"></i> Guardar
                            </button>
                        </div>

                        <!-- Mostrar errores de validación -->
                        @if ($errors->any())
                        <div class="alert alert-danger w-100 mt-2">
                            <ul>
                                @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                    </div>
                </div>
            </form>
        </div>

        <!-- Tabla de Cuotas -->
        <div class="col-md-4">
            <div class="card shadow border-0">
                <div class="card-body">
                    <h4 class="m-0 text-primary font-weight-bold mb-3">Detalle de Cuotas</h4>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Cuota</th>
                                <th>Fecha de Pago</th>
                                <th>Monto</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-calculo-body">
                            <!-- Las cuotas se insertarán aquí -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2">Total</th>
                                <th id="total-cuotas">S/. 0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<!-- Incluir el CSS de Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Estilos personalizados */
    .form-group label {
        font-weight: bold;
    }

    .select2-container--default .select2-selection--single {
        height: 40px;
        padding: 8px;
        font-size: 16px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 24px;
    }

    .btn {
        border-radius: 8px;
    }

    .table td,
    .table th {
        padding: 12px 15px !important;
    }

    .btn-group-toggle .btn {
        border-radius: 8px;
    }

    .card {
        background-color: #f9f9f9;
    }

    .form-label {
        font-weight: 600;
    }

    #messageContainer {
        padding: 10px;
        background-color: #e2f1f9;
        border-radius: 10px;
        border: 1px solid #abd5ec;
    }

    #nombreCliente {
        margin-top: 0.5rem;
    }

    .table thead th {
        vertical-align: middle;
        text-align: center;
    }

    .table tbody td {
        vertical-align: middle;
    }

    /* Ajustar la altura de Select2 */
    .select2-container .select2-selection--single {
        height: auto !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5 !important;
    }
</style>
@stop

@section('js')
<!-- Incluir JS de Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Inicializar Select2
        $('.select2').select2();

        /**
         * Función para calcular las cuotas.
         */
        function calcularCuotas() {
            let cantidad = parseFloat($('#cantidad_solicitada').val());
            let plazo = $('input[name="plazo"]:checked').val();
            let fechaPrimerPago = $('#fecha_primer_pago').val();
            let tasaInteres = parseFloat($('#tasa_interes').val() || 10); // Usar un valor por defecto si está oculto

            if (!cantidad || !plazo || !fechaPrimerPago) {
                // Limpiar la tabla si no se han llenado todos los campos
                $('#tabla-calculo-body').html('');
                $('#total-cuotas').text('S/. 0.00');
                return;
            }

            $.ajax({
                url: "{{ route('admin.calcularCuotas') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    cantidad: cantidad,
                    plazo: plazo,
                    fechaPrimerPago: fechaPrimerPago,
                    tasaInteres: tasaInteres
                },
                success: function(response) {
                    // Actualizar la tabla de cuotas
                    let cuotasHtml = '';
                    response.cuotas.forEach(function(cuota) {
                        cuotasHtml += `<tr>
                        <td>${cuota.numero}</td>
                        <td>${cuota.fecha_pago}</td>
                        <td>S/. ${cuota.monto}</td>
                    </tr>`;
                    });
                    $('#tabla-calculo-body').html(cuotasHtml);
                    // Actualizar el total
                    $('#total-cuotas').text(`S/. ${response.total}`);
                },
                error: function(xhr) {
                    console.error(xhr);
                    alert('Error al calcular las cuotas. Por favor, verifica los datos ingresados.');
                    // Limpiar la tabla en caso de error
                    $('#tabla-calculo-body').html('');
                    $('#total-cuotas').text('S/. 0.00');
                }
            });
        }

        // Disparar el cálculo cuando cambien los campos relevantes
        $('input[name="plazo"], #cantidad_solicitada, #fecha_primer_pago').on('change input', calcularCuotas);

        /**
         * Función para actualizar Fecha Primer Pago.
         */
        function actualizarFechaPrimerPago() {
            let fechaAtencion = $('#fecha_atencion').val();
            if (fechaAtencion) {
                let fecha = new Date(fechaAtencion);
                fecha.setDate(fecha.getDate() + 7); // Añadir 7 días

                // Formatear la fecha a YYYY-MM-DD
                let año = fecha.getFullYear();
                let mes = ('0' + (fecha.getMonth() + 1)).slice(-2);
                let dia = ('0' + fecha.getDate()).slice(-2);
                let fechaPrimerPago = `${año}-${mes}-${dia}`;

                $('#fecha_primer_pago').val(fechaPrimerPago);
            } else {
                $('#fecha_primer_pago').val('');
            }
        }

        // Actualizar Fecha Primer Pago cuando cambie Fecha Atención
        $('#fecha_atencion').on('change', actualizarFechaPrimerPago);

        // Inicializar Fecha Primer Pago al cargar la página
        actualizarFechaPrimerPago();

        // Llamar a calcularCuotas al cargar la página para mostrar las cuotas existentes
        calcularCuotas();
    });
</script>

<!-- JavaScript para manejar la asignación de avales -->
<script>
    $(document).ready(function() {
        $('#btnAsignar').on('click', function() {
            let dniInput = $('#inputDni').val().trim();
            if (dniInput === '') {
                alert('Por favor, ingresa un DNI válido.');
                return;
            }

            $.ajax({
                url: "{{ route('admin.prestamos.consultarAval') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    nDocumento: dniInput
                },
                success: function(response) {
                    $('#esCliente').text('---');
                    $('#estadoPrestamo').text('---');
                    $('#etiquetasCliente').text('---');
                    $('#tablaAvalados').html(`
                    <tr>
                        <td class="nombreAvalado">---</td>
                        <td class="estadoPrestamoAvalado">---</td>
                        <td class="etiquetasAvalado">---</td>
                    </tr>
                `);

                    if (response.persona) {
                        $('#nombreCliente').text(response.persona.nombres + ' ' + response.persona.ape_pat + ' ' + response.persona.ape_mat);
                        $('#infoAvalContainer').show();
                    } else {
                        $('#nombreCliente').text('---');
                        alert(response.message || 'Persona no encontrada.');
                        $('#infoAvalContainer').hide();
                        return;
                    }

                    if (response.prestamos && response.prestamos.length > 0) {
                        $('#esCliente').text('Sí');
                        let estadosPrestamo = response.prestamos.map(function(prestamo) {
                            return prestamo.estado;
                        }).join(', ');
                        $('#estadoPrestamo').text(estadosPrestamo || 'Sin préstamos');
                    } else {
                        $('#esCliente').text('No');
                    }

                    if (response.avales && response.avales.length > 0) {
                        let filasAvalados = '';
                        response.avales.forEach(function(aval) {
                            filasAvalados += `
                            <tr>
                                <td>${aval.nombre}</td>
                                <td>${aval.estado_prestamo}</td>
                                <td>${aval.etiquetas.join(', ')}</td>
                            </tr>
                        `;
                        });
                        $('#tablaAvalados').html(filasAvalados);
                    } else {
                        $('#tablaAvalados').html(`
                        <tr>
                            <td colspan="3" class="text-center">No ha sido aval de ningún préstamo.</td>
                        </tr>
                    `);
                    }
                },
                error: function(xhr) {
                    console.error(xhr);
                    if (xhr.status === 422) {
                        alert(xhr.responseJSON.message || 'Datos inválidos. Por favor, revisa el formulario.');
                    } else if (xhr.status === 404) {
                        alert('Aval no encontrado.');
                    } else {
                        alert('Error al buscar el DNI. Asegúrate de que el DNI exista.');
                    }

                    $('#infoAvalContainer').hide();
                }
            });
        });

        $('#btnCerrarInfoAval').on('click', function() {
            $('#infoAvalContainer').hide();
        });
    });
</script>

<!-- Verificar préstamos del cliente -->
<script>
    document.getElementById('btnConsultarPrestamos').addEventListener('click', function() {
        const clienteId = document.getElementById('selectCliente').value;
        const messageContainer = document.getElementById('messageContainer');
        const tableContainer = document.getElementById('tableContainer');
        const message = document.getElementById('message');
        const tableBody = document.querySelector('#tablaPrestamos tbody');

        message.innerHTML = '';
        messageContainer.style.display = 'none';
        tableContainer.style.display = 'none';
        tableBody.innerHTML = '';

        if (clienteId) {
            fetch(`/consultar-prestamos/${clienteId}`)
                .then(response => {
                    if (!response.ok) {
                        if (response.status === 404) {
                            message.innerHTML = 'Cliente no encontrado.';
                            messageContainer.style.display = 'block';
                            return;
                        }
                        throw new Error('Error en la consulta: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data || data.message) {
                        message.innerHTML = 'No existen datos, posiblemente este cliente es nuevo, continúe con la solicitud';
                        messageContainer.style.display = 'block';
                    } else {
                        data.forEach(prestamo => {
                            const row = `<tr>
                            <td>${prestamo.tipo}</td>
                            <td>${prestamo.nombre}</td>
                            <td>${prestamo.estado}</td>
                            <td>${prestamo.fecha_solicitud}</td>
                            <td>${prestamo.fecha_primer_pago}</td>
                        </tr>`;
                            tableBody.insertAdjacentHTML('beforeend', row);
                        });
                        tableContainer.style.display = 'block';
                    }
                })
                .catch(error => {
                    message.innerHTML = 'Error: ' + error.message;
                    messageContainer.style.display = 'block';
                });
        } else {
            message.innerHTML = 'Por favor, selecciona un cliente';
            messageContainer.style.display = 'block';
        }
    });
</script>

<!-- Mostrar Dirección y cuentas de cliente -->
<script>
    $(document).ready(function() {
        function cargarDireccionesYCuentaCliente(clienteId) {
            $('#selectDireccionCobro').html('<option value="" selected disabled>Selecciona una dirección</option>');
            $('#selectCuentaCliente').html('<option value="">Seleccione una cuenta</option>');

            if (clienteId) {
                var urlDirecciones = "{{ route('admin.clientes.direcciones', ['clienteId' => '__clienteId__']) }}";
                urlDirecciones = urlDirecciones.replace('__clienteId__', clienteId);

                var urlCuentas = "{{ route('admin.clientes.cuentas', ['clienteId' => '__clienteId__']) }}";
                urlCuentas = urlCuentas.replace('__clienteId__', clienteId);

                $.ajax({
                    url: urlDirecciones,
                    type: 'GET',
                    success: function(response) {
                        if (response.direcciones && response.direcciones.length > 0) {
                            response.direcciones.forEach(function(direccion) {
                                $('#selectDireccionCobro').append(`<option value="${direccion.id}">${direccion.direccion}</option>`);
                            });
                            // Seleccionar la dirección actual si existe
                            $('#selectDireccionCobro').val('{{ $prestamo->direccion_cobro_id }}').trigger('change');
                        } else {
                            $('#selectDireccionCobro').html('<option value="" selected disabled>No hay direcciones disponibles</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr);
                        alert('Error al cargar las direcciones.');
                    }
                });

                $.ajax({
                    url: urlCuentas,
                    type: 'GET',
                    success: function(response) {
                        if (response.cuentas && response.cuentas.length > 0) {
                            response.cuentas.forEach(function(cuenta) {
                                let label = '';

                                // Verificar si tiene entidad bancaria (banco)
                                if (cuenta.entidad_bancaria && cuenta.entidad_bancaria.banco) {
                                    label = `${cuenta.entidad_bancaria.banco}: ${cuenta.numero_cuenta || 'S/N'}`;
                                }
                                // Verificar si tiene billetera digital
                                else if (cuenta.billetera_digital && cuenta.billetera_digital.nombre) {
                                    label = `${cuenta.billetera_digital.nombre}: ${cuenta.numero_cuenta || 'S/N'}`;
                                }
                                // Verificar si tiene tipo de cuenta
                                else if (cuenta.tipo_cuenta && cuenta.tipo_cuenta.tipo_cuenta) {
                                    const tipoCuenta = cuenta.tipo_cuenta.tipo_cuenta.toUpperCase();
                                    label = tipoCuenta === 'EFECTIVO' ? 'EFECTIVO' : (cuenta.numero_cuenta ? `${tipoCuenta}: ${cuenta.numero_cuenta}` : tipoCuenta);
                                }
                                else {
                                    label = cuenta.numero_cuenta || 'Cuenta sin identificar';
                                }

                                $('#selectCuentaCliente').append(`<option value="${cuenta.id}">${label}</option>`);
                            });
                            // Seleccionar la cuenta actual si existe
                            $('#selectCuentaCliente').val('{{ $prestamo->cuenta_cliente_id }}').trigger('change');
                        } else {
                            $('#selectCuentaCliente').html('<option value="" selected disabled>No hay cuentas disponibles</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr);
                        alert('Error al cargar las cuentas.');
                    }
                });
            }
        }

        // Cuando se carga la página, cargar las direcciones y cuentas del cliente seleccionado
        var clienteIdInicial = $('#selectCliente').val();
        cargarDireccionesYCuentaCliente(clienteIdInicial);

        // Cuando se cambia el cliente, actualizar las direcciones y cuentas
        $('#selectCliente').change(function() {
            var clienteId = $(this).val();
            cargarDireccionesYCuentaCliente(clienteId);
        });
    });
</script>

@stop
