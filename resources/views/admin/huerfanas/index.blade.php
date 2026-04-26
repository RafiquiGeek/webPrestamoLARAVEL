@extends('layouts.admin')

@section('title', 'Registros Huérfanos')

@section('content_header')
    <h1 class="m-0">Registros Huérfanos</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <p class="text-muted">
                <i class="fas fa-info-circle"></i>
                Estos son los registros que apuntan a maestros que ya no existen. Puedes seleccionar y eliminarlos.
            </p>
        </div>
    </div>

    <form id="huerfanas-form" action="{{ route('admin.huerfanas.eliminar') }}" method="POST">
        @csrf

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-3" id="huerfanasTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="cuotas-prestamo-tab" data-toggle="tab" data-target="#cuotas-prestamo" type="button" role="tab" aria-controls="cuotas-prestamo" aria-selected="true">
                    <i class="fas fa-file-invoice"></i> Cuotas Préstamos
                    <span class="badge badge-primary">{{ count($cuotasPrestamo) }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="cuotas-convenio-tab" data-toggle="tab" data-target="#cuotas-convenio" type="button" role="tab" aria-controls="cuotas-convenio" aria-selected="false">
                    <i class="fas fa-handshake"></i> Cuotas Convenios
                    <span class="badge badge-primary">{{ count($cuotasConvenio) }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="moras-prestamo-tab" data-toggle="tab" data-target="#moras-prestamo" type="button" role="tab" aria-controls="moras-prestamo" aria-selected="false">
                    <i class="fas fa-exclamation-circle"></i> Moras Préstamos
                    <span class="badge badge-primary">{{ count($morasPrestamo) }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="moras-convenio-tab" data-toggle="tab" data-target="#moras-convenio" type="button" role="tab" aria-controls="moras-convenio" aria-selected="false">
                    <i class="fas fa-exclamation-circle"></i> Moras Convenios
                    <span class="badge badge-primary">{{ count($morasConvenio) }}</span>
                </button>
            </li>
        </ul>

        <!-- Tabs Content -->
        <div class="tab-content" id="huerfanasTabContent">
            <!-- CUOTAS PRÉSTAMOS -->
            <div class="tab-pane fade show active" id="cuotas-prestamo" role="tabpanel" aria-labelledby="cuotas-prestamo-tab">
                @if (count($cuotasPrestamo) > 0)
                    <div class="card card-sm">
                        <div class="card-body p-2">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" class="select-all-cuotas-prestamo">
                                        </th>
                                        <th>ID</th>
                                        <th>Préstamo ID (huérfano)</th>
                                        <th>Número</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                        <th>Fecha Pago</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cuotasPrestamo as $cuota)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="cuota-prestamo-checkbox" value="{{ $cuota->id }}">
                                            </td>
                                            <td><strong>{{ $cuota->id }}</strong></td>
                                            <td><span class="badge badge-danger">{{ $cuota->prestamo_id }}</span></td>
                                            <td>{{ $cuota->numero }}</td>
                                            <td>{{ number_format($cuota->monto, 2) }}</td>
                                            <td>{{ $cuota->estado }}</td>
                                            <td>{{ $cuota->fecha_pago ? \Carbon\Carbon::parse($cuota->fecha_pago)->format('d/m/Y') : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check"></i> No hay cuotas huérfanas de préstamos.
                    </div>
                @endif
            </div>

            <!-- CUOTAS CONVENIOS -->
            <div class="tab-pane fade" id="cuotas-convenio" role="tabpanel" aria-labelledby="cuotas-convenio-tab">
                @if (count($cuotasConvenio) > 0)
                    <div class="card card-sm">
                        <div class="card-body p-2">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" class="select-all-cuotas-convenio">
                                        </th>
                                        <th>ID</th>
                                        <th>Convenio ID (huérfano)</th>
                                        <th>Número Cuota</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                        <th>Fecha Vencimiento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cuotasConvenio as $cuota)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="cuota-convenio-checkbox" value="{{ $cuota->id }}">
                                            </td>
                                            <td><strong>{{ $cuota->id }}</strong></td>
                                            <td><span class="badge badge-danger">{{ $cuota->convenio_id }}</span></td>
                                            <td>{{ $cuota->numero_cuota }}</td>
                                            <td>{{ number_format($cuota->monto_cuota, 2) }}</td>
                                            <td>{{ $cuota->estado }}</td>
                                            <td>{{ $cuota->fecha_vencimiento ? \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y') : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check"></i> No hay cuotas huérfanas de convenios.
                    </div>
                @endif
            </div>

            <!-- MORAS PRÉSTAMOS -->
            <div class="tab-pane fade" id="moras-prestamo" role="tabpanel" aria-labelledby="moras-prestamo-tab">
                @if (count($morasPrestamo) > 0)
                    <div class="card card-sm">
                        <div class="card-body p-2">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" class="select-all-moras-prestamo">
                                        </th>
                                        <th>ID</th>
                                        <th>Cuota ID (huérfana)</th>
                                        <th>Fecha</th>
                                        <th>Monto</th>
                                        <th>Días Mora</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($morasPrestamo as $mora)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="mora-prestamo-checkbox" value="{{ $mora->id }}">
                                            </td>
                                            <td><strong>{{ $mora->id }}</strong></td>
                                            <td><span class="badge badge-danger">{{ $mora->cuota_id }}</span></td>
                                            <td>{{ $mora->fecha ? \Carbon\Carbon::parse($mora->fecha)->format('d/m/Y') : '-' }}</td>
                                            <td>{{ number_format($mora->monto, 2) }}</td>
                                            <td>{{ $mora->dias_mora }}</td>
                                            <td>{{ $mora->estado }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check"></i> No hay moras huérfanas de préstamos.
                    </div>
                @endif
            </div>

            <!-- MORAS CONVENIOS -->
            <div class="tab-pane fade" id="moras-convenio" role="tabpanel" aria-labelledby="moras-convenio-tab">
                @if (count($morasConvenio) > 0)
                    <div class="card card-sm">
                        <div class="card-body p-2">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" class="select-all-moras-convenio">
                                        </th>
                                        <th>ID</th>
                                        <th>Cuota Convenio ID (huérfana)</th>
                                        <th>Fecha</th>
                                        <th>Monto</th>
                                        <th>Días Mora</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($morasConvenio as $mora)
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="mora-convenio-checkbox" value="{{ $mora->id }}">
                                            </td>
                                            <td><strong>{{ $mora->id }}</strong></td>
                                            <td><span class="badge badge-danger">{{ $mora->cuota_convenio_id }}</span></td>
                                            <td>{{ $mora->fecha ? \Carbon\Carbon::parse($mora->fecha)->format('d/m/Y') : '-' }}</td>
                                            <td>{{ number_format($mora->monto, 2) }}</td>
                                            <td>{{ $mora->dias_mora }}</td>
                                            <td>{{ $mora->estado }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check"></i> No hay moras huérfanas de convenios.
                    </div>
                @endif
            </div>
        </div>

        <!-- Bottom Action Bar -->
        <div class="card mt-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <span id="selected-count" class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        Seleccionados: <strong>0</strong> registros
                    </span>
                </div>
                <button type="submit" id="delete-btn" class="btn btn-danger btn-sm" disabled>
                    <i class="fas fa-trash"></i> Eliminar Seleccionados
                </button>
            </div>
        </div>
    </form>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Select-all checkbox para CUOTAS PRÉSTAMOS
            $('.select-all-cuotas-prestamo').on('change', function() {
                $('.cuota-prestamo-checkbox').prop('checked', $(this).prop('checked'));
                updateSelectedCount();
            });

            // Select-all checkbox para CUOTAS CONVENIOS
            $('.select-all-cuotas-convenio').on('change', function() {
                $('.cuota-convenio-checkbox').prop('checked', $(this).prop('checked'));
                updateSelectedCount();
            });

            // Select-all checkbox para MORAS PRÉSTAMOS
            $('.select-all-moras-prestamo').on('change', function() {
                $('.mora-prestamo-checkbox').prop('checked', $(this).prop('checked'));
                updateSelectedCount();
            });

            // Select-all checkbox para MORAS CONVENIOS
            $('.select-all-moras-convenio').on('change', function() {
                $('.mora-convenio-checkbox').prop('checked', $(this).prop('checked'));
                updateSelectedCount();
            });

            // Individual checkboxes
            $(document).on('change', '.cuota-prestamo-checkbox, .cuota-convenio-checkbox, .mora-prestamo-checkbox, .mora-convenio-checkbox', function() {
                updateSelectedCount();
            });

            function updateSelectedCount() {
                const cuotasPrestamoSelected = $('.cuota-prestamo-checkbox:checked').length;
                const cuotasConvenioSelected = $('.cuota-convenio-checkbox:checked').length;
                const morasPrestamoSelected = $('.mora-prestamo-checkbox:checked').length;
                const morasConvenioSelected = $('.mora-convenio-checkbox:checked').length;

                const total = cuotasPrestamoSelected + cuotasConvenioSelected + morasPrestamoSelected + morasConvenioSelected;

                $('#selected-count strong').text(total);
                $('#delete-btn').prop('disabled', total === 0);

                // Actualizar hidden inputs
                const cuotasPrestamoIds = $('.cuota-prestamo-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();

                const cuotasConvenioIds = $('.cuota-convenio-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();

                const morasPrestamoIds = $('.mora-prestamo-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();

                const morasConvenioIds = $('.mora-convenio-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();

                // Limpiar inputs previos
                $('#huerfanas-form').find('input[type="hidden"][name^="cuotas_"], input[type="hidden"][name^="moras_"]').remove();

                // Agregar nuevos inputs
                cuotasPrestamoIds.forEach(function(id) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'cuotas_prestamo[]',
                        value: id
                    }).appendTo('#huerfanas-form');
                });

                cuotasConvenioIds.forEach(function(id) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'cuotas_convenio[]',
                        value: id
                    }).appendTo('#huerfanas-form');
                });

                morasPrestamoIds.forEach(function(id) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'moras_prestamo[]',
                        value: id
                    }).appendTo('#huerfanas-form');
                });

                morasConvenioIds.forEach(function(id) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'moras_convenio[]',
                        value: id
                    }).appendTo('#huerfanas-form');
                });
            }

            // Confirmación antes de eliminar
            $('#huerfanas-form').on('submit', function(e) {
                const count = parseInt($('#selected-count strong').text());
                if (count > 0) {
                    return confirm('¿Estás seguro de que deseas eliminar ' + count + ' registro(s) huérfano(s)? Esta acción no se puede deshacer.');
                }
                e.preventDefault();
            });
        });
    </script>
@stop
