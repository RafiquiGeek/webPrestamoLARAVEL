@extends('layouts.admin')

@section('title', 'Reporte de Clientes por Usuario')

@section('content_header')
    <h1>
        <i class="fas fa-users mr-2"></i>
        Reporte de Clientes por Usuario
    </h1>
    <div class="breadcrumb">
        Reportes / Clientes por Usuario
    </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Card de Filtros -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-filter mr-2"></i>
                Filtros de Búsqueda
            </h3>
        </div>
        <form action="{{ route('admin.reportes-clientes.index') }}" method="GET" id="filtrosForm">
            <div class="card-body">
                <div class="row">
                    <!-- Filtro: Usuario (JCC/Asesor/Analista) -->
                    <div class="col-md-4 mb-3">
                        <label for="usuario_id" class="form-label font-weight-bold">
                            <i class="fas fa-user-tie text-primary mr-1"></i>
                            Usuario Asignado
                        </label>
                        <select class="form-label form-control select2-multiple" id="usuario_id" name="usuario_id[]" multiple>
                            @foreach($usuarios as $usuario)
                                @php
                                    $nombreCompleto = $usuario->persona
                                        ? trim($usuario->persona->nombres . ' ' . $usuario->persona->ape_pat . ' ' . $usuario->persona->ape_mat)
                                        : $usuario->name;
                                    $rol = $usuario->roles->first()?->name ?? 'Usuario';
                                @endphp
                                <option value="{{ $usuario->id }}"
                                    {{ in_array($usuario->id, request('usuario_id', [])) ? 'selected' : '' }}>
                                    {{ $nombreCompleto }} ({{ $rol }})
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">JCC, Asesor o Analista asignado al préstamo</small>
                    </div>

                    <!-- Filtro: Zona del Usuario -->
                    <div class="col-md-4 mb-3">
                        <label for="zona_usuario_id" class="form-label font-weight-bold">
                            <i class="fas fa-map-marked-alt text-info mr-1"></i>
                            Zona del Usuario
                        </label>
                        <select class="form-control select2-multiple" id="zona_usuario_id" name="zona_usuario_id[]" multiple>
                            @foreach($usuarios as $usuario)
                                @if($usuario->zonas && $usuario->zonas->count() > 0)
                                    @php
                                        $nombreCompleto = $usuario->persona
                                            ? trim($usuario->persona->nombres . ' ' . $usuario->persona->ape_pat . ' ' . $usuario->persona->ape_mat)
                                            : $usuario->name;
                                    @endphp
                                    <option value="{{ $usuario->id }}"
                                        {{ in_array($usuario->id, request('zona_usuario_id', [])) ? 'selected' : '' }}>
                                        {{ $nombreCompleto }} - Zonas: {{ $usuario->zonas->pluck('nombre')->join(', ') }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Filtrar por zona asignada al usuario</small>
                    </div>

                    <!-- Filtro: Estado del Préstamo -->
                    <div class="col-md-4 mb-3">
                        <label for="estado_prestamo" class="form-label font-weight-bold">
                            <i class="fas fa-flag text-danger mr-1"></i>
                            Estado del Préstamo
                        </label>
                        <select class="form-control select2-multiple" id="estado_prestamo" name="estado_prestamo[]" multiple>
                            @foreach($estados as $estado)
                                <option value="{{ $estado }}"
                                    {{ in_array($estado, request('estado_prestamo', [])) ? 'selected' : '' }}>
                                    {{ $estado }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Filtrar por estado del préstamo</small>
                    </div>
                </div>

                <div class="row">
                    <!-- Filtro: Zona del Cliente -->
                    <div class="col-md-6 mb-3">
                        <label for="zona_cliente_id" class="form-label font-weight-bold">
                            <i class="fas fa-map-marker-alt text-success mr-1"></i>
                            Zona del Cliente
                        </label>
                        <select class="form-control" id="zona_cliente_id" name="zona_cliente_id">
                            <option value="">Todas las zonas</option>
                            @foreach($zonas as $zona)
                                <option value="{{ $zona->id }}"
                                    {{ request('zona_cliente_id') == $zona->id ? 'selected' : '' }}>
                                    {{ $zona->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Ubicación geográfica del cliente</small>
                    </div>

                    <!-- Filtro: Sucursal del Cliente -->
                    <div class="col-md-6 mb-3">
                        <label for="sucursal_cliente_id" class="form-label font-weight-bold">
                            <i class="fas fa-building text-warning mr-1"></i>
                            Sucursal del Cliente
                        </label>
                        <select class="form-control" id="sucursal_cliente_id" name="sucursal_cliente_id">
                            <option value="">Todas las sucursales</option>
                            @foreach($sucursales as $sucursal)
                                <option value="{{ $sucursal->id }}"
                                    {{ request('sucursal_cliente_id') == $sucursal->id ? 'selected' : '' }}>
                                    {{ $sucursal->sucursal }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Sucursal asociada al cliente</small>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light">
                <div class="row">
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-search mr-2"></i> Aplicar Filtros
                        </button>
                        <a href="{{ route('admin.reportes-clientes.index') }}" class="btn btn-outline-secondary btn-lg ml-2">
                            <i class="fas fa-eraser mr-2"></i> Limpiar
                        </a>
                    </div>
                    <div class="col-md-6 text-right">
                        <a href="{{ route('admin.reportes-clientes.exportar', request()->all()) }}"
                           class="btn btn-success btn-lg">
                            <i class="fas fa-file-excel mr-2"></i> Exportar Excel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Card de Resultados -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-table mr-2"></i>
                Resultados ({{ $clientes->total() }} clientes)
            </h3>
        </div>
        <div class="card-body p-0">
            @if($clientes->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>DNI</th>
                                <th>Nombre Completo</th>
                                <th>Teléfono</th>
                                <th>Zona</th>
                                <th>Sucursal</th>
                                <th>Dirección</th>
                                <th>JCC</th>
                                <th>Asesor</th>
                                <th>Analista</th>
                                <th class="text-center"># Préstamos</th>
                                <th class="text-right">Monto Total</th>
                                <th class="text-center">Estado Actual</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clientes as $cliente)
                                @php
                                    $direccion = $cliente->persona->direcciones->first();
                                    $prestamoReciente = $cliente->prestamos->first();
                                    $jcc = $prestamoReciente?->carterasJcc->first()?->jcc;
                                    $asesor = $prestamoReciente?->carterasAsesor->first()?->asesor;
                                    $analista = $prestamoReciente?->carterasAnalista->first()?->analista;
                                    $zona = $direccion?->sucursal?->zonas->first();
                                @endphp
                                <tr>
                                    <td>{{ $cliente->persona->documento ?? 'N/A' }}</td>
                                    <td>
                                        <strong>{{ $cliente->persona->nombres ?? '' }} {{ $cliente->persona->ape_pat ?? '' }}</strong><br>
                                        <small class="text-muted">{{ $cliente->persona->ape_mat ?? '' }}</small>
                                    </td>
                                    <td>
                                        @if($cliente->persona->telefonos && $cliente->persona->telefonos->count() > 0)
                                            {{ $cliente->persona->telefonos->first()->numero ?? 'N/A' }}
                                            @if($cliente->persona->telefonos->count() > 1)
                                                <br><small class="text-muted">{{ $cliente->persona->telefonos->skip(1)->first()->numero ?? '' }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>{{ $zona?->nombre ?? 'N/A' }}</td>
                                    <td>{{ $direccion?->sucursal?->sucursal ?? 'N/A' }}</td>
                                    <td>
                                        @if($direccion)
                                            <small>
                                                {{ $direccion->direccion ?? '' }}
                                                {{ $direccion->numero ? ' N° ' . $direccion->numero : '' }}
                                                {{ $direccion->referencia ? ' - ' . $direccion->referencia : '' }}
                                            </small>
                                        @else
                                            <small>N/A</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($jcc)
                                            <small>{{ $jcc->persona->nombres ?? '' }} {{ $jcc->persona->ape_pat ?? '' }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($asesor)
                                            <small>{{ $asesor->persona->nombres ?? '' }} {{ $asesor->persona->ape_pat ?? '' }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($analista)
                                            <small>{{ $analista->persona->nombres ?? '' }} {{ $analista->persona->ape_pat ?? '' }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $cliente->prestamos_count ?? 0 }}</span>
                                    </td>
                                    <td class="text-right">
                                        <strong>S/ {{ number_format($cliente->prestamos_sum_cantidad_solicitada ?? 0, 2) }}</strong>
                                    </td>
                                    <td class="text-center">
                                        @if($prestamoReciente)
                                            @php
                                                $badgeClass = match($prestamoReciente->estado) {
                                                    'Vigente' => 'success',
                                                    'Moroso' => 'danger',
                                                    'Con Convenio' => 'warning',
                                                    'Liquidado' => 'primary',
                                                    'Finalizado' => 'secondary',
                                                    'Cancelado' => 'dark',
                                                    default => 'info'
                                                };
                                            @endphp
                                            <span class="badge badge-{{ $badgeClass }}">
                                                {{ $prestamoReciente->estado }}
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No se encontraron clientes</h5>
                    <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                </div>
            @endif
        </div>
        @if($clientes->hasPages())
            <div class="card-footer">
                <div class="row">
                    <div class="col-sm-12 col-md-5">
                        <div class="dataTables_info">
                            Mostrando {{ $clientes->firstItem() }} a {{ $clientes->lastItem() }} de {{ $clientes->total() }} registros
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-7">
                        <div class="dataTables_paginate paging_simple_numbers float-right">
                            {{ $clientes->links() }}
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@stop

@section('css')
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.5.4/dist/select2-bootstrap4.min.css" rel="stylesheet" />

    <style>
        /* Select2 Styles */
        .select2-container {
            width: 100% !important;
        }
        .select2-container .select2-selection--multiple {
            border: 2px solid #ced4da !important;
            background-color: #fff;
        }
        .select2-container .select2-selection--single {
            border: 2px solid #ced4da !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #007bff;
            border-color: #006fe6;
            color: white;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
            font-size: 16px;
            font-weight: bold;
            margin-right: 3px;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #ff4444;
        }

        /* Form styles */
        .form-control {
            border: 2px solid #ced4da;
        }

        /* Table styles */
        .table td {
            vertical-align: middle;
        }
        .thead-light th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
    </style>
@stop

@section('js')
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializar Select2 en los select múltiples
            $('.select2-multiple').select2({
                theme: 'bootstrap4',
                placeholder: 'Seleccione una o más opciones',
                allowClear: true,
                closeOnSelect: false
            });

            // Filtro dependiente: Zona Cliente → Sucursal Cliente
            $('#zona_cliente_id').on('change', function() {
                const zonaId = $(this).val();
                const $sucursalSelect = $('#sucursal_cliente_id');

                if (zonaId) {
                    // Hacer petición AJAX para obtener sucursales de la zona
                    $.ajax({
                        url: `/admin/zonas/${zonaId}/sucursales`,
                        method: 'GET',
                        success: function(data) {
                            $sucursalSelect.html('<option value="">Todas las sucursales</option>');
                            data.forEach(sucursal => {
                                $sucursalSelect.append(
                                    `<option value="${sucursal.id}">${sucursal.sucursal}</option>`
                                );
                            });
                        },
                        error: function() {
                            // Si falla, mantener todas las sucursales
                            console.warn('No se pudieron cargar las sucursales de la zona');
                        }
                    });
                } else {
                    // Si no hay zona seleccionada, mantener todas las sucursales
                    // (ya están cargadas desde el servidor)
                }
            });
        });
    </script>
@stop
