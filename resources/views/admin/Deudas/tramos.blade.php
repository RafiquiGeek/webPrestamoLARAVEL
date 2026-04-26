@extends('layouts.admin')

@section('title', 'Reporte de Préstamos por Tramos')

@section('css')
{{-- Bootstrap Datepicker CSS --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<style>
    /* Estilo del popup del calendario */
    .datepicker {
        z-index: 9999 !important;
        padding: 8px !important;
        font-size: 12px !important;
        max-width: 280px !important;
    }

    .datepicker table {
        font-size: 12px !important;
    }

    .datepicker table tr td,
    .datepicker table tr th {
        width: 30px !important;
        height: 30px !important;
        padding: 4px !important;
        font-size: 11px !important;
    }
    .table-condensed{
        width: 100% !important;
    }

    .datepicker .datepicker-switch,
    .datepicker .prev,
    .datepicker .next,
    .datepicker tfoot tr th {
        font-size: 12px !important;
        padding: 6px 8px !important;
    }

    .datepicker table tr td.active,
    .datepicker table tr td.active:hover,
    .datepicker table tr td.active.disabled,
    .datepicker table tr td.active.disabled:hover {
        background-color: #007bff;
        background-image: none;
    }

    /* Asegurar que los inputs de fecha sean visibles */
    #dynamic_date_inputs > div {
        min-height: 38px;
    }

    /* Ancho de los inputs de fecha */
    .datepicker-dia,
    .datepicker-mes {
        max-width: 200px;
    }
    .datepicker-desde,
    .datepicker-hasta {
        max-width: 100%;
    }
    #fecha_dia .form-group,
    #fecha_mes .form-group {
        max-width: 250px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #007bff;
        border-color: #006fe6;
        color: #fff;
    }
    .btn-group-toggle .btn.active {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }

    /* Estilos para sucursales checkbox-btn */
    .sucursal-checkbox-btn {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .sucursal-checkbox-btn.active {
        background-color: #17a2b8 !important;
        color: white !important;
        border-color: #17a2b8 !important;
        font-weight: bold;
    }
    .sucursal-checkbox-btn.active .fa-check {
        display: inline !important;
    }
    .sucursal-checkbox-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    
    /* Estilos para dropdown con checkboxes */
    .dropdown-menu {
        padding: 0.5rem;
    }
    .dropdown-menu .custom-control {
        padding: 0.25rem 0;
    }
    .dropdown-menu .custom-control-label {
        cursor: pointer;
        font-size: 0.9rem;
    }

    /* Overlay de carga global que cubre toda la página */
    #globalLoadingOverlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }

    #globalLoadingOverlay.show {
        display: flex !important;
    }

    .loading-content {
        text-align: center;
        background: white;
        padding: 30px 50px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .loading-content i {
        color: #007bff;
        margin-bottom: 15px;
    }

    .loading-content p {
        margin: 0;
        color: #333;
        font-size: 16px;
        font-weight: 500;
    }

</style>
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark"><i class="fas fa-chart-line mr-2"></i> Reporte de Tramos</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.deudas.index') }}">Deudas</a></li>
                <li class="breadcrumb-item active">Tramos</li>
            </ol>
        </div>
    </div>
@stop

@section('content')
<div class="row">
    <div class="col-12">
        
        {{-- CARD DE FILTROS (Estilo filtros_mejorados) --}}
        <div class="card mb-4">
            <div class="card-body">
                <form id="filterForm">

                    {{-- SECCIÓN PRINCIPAL: Búsqueda y Fechas --}}
                    <div class="row mb-4">
                        <div class="col-12 mb-3">
                            <h6 class="text-uppercase text-secondary font-weight-bold mb-0" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="fas fa-filter mr-1"></i> Filtros de Tramos
                            </h6>
                        </div>

                        {{-- Tipo de consulta --}}
                        <div class="col-md-2">
                            <div class="form-group mb-0">
                                <label class="text-muted small mb-2">Tipo de consulta</label>
                                <select class="form-control" id="tipo_consulta" name="tipo_consulta">
                                    <option value="ambos">Ambos</option>
                                    <option value="prestamos">Solo Préstamos</option>
                                    <option value="convenios">Solo Convenios</option>
                                </select>
                            </div>
                        </div>

                        {{-- Buscador --}}
                        <div class="col-md-2">
                            <div class="form-group mb-0">
                                <label class="text-muted small mb-2">Buscar Cliente</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white border-right-0">
                                            <i class="fas fa-search text-muted"></i>
                                        </span>
                                    </div>
                                    <input type="text" class="form-control border-left-0" id="busqueda_general" placeholder="Nombre, DNI...">
                                </div>
                            </div>
                        </div>

                        {{-- Tipo de Rango de Fecha --}}
                        <div class="col-md-2">
                            <div class="form-group mb-0">
                                <label class="text-muted small mb-2">Rango de Fecha</label>
                                <select class="form-control form-control-lg" id="tipo_rango_fecha" name="tipo_rango_fecha" style="font-size: 1.1rem;">
                                    <option value="">Seleccione...</option>
                                    <option value="dia">Por día</option>
                                    <option value="mes">Por mes</option>
                                    <option value="entre_fechas">Entre fechas</option>
                                </select>
                            </div>
                        </div>

                        {{-- Contenedor dinámico de fechas --}}
                        <div class="col-md-4" id="contenedor_fechas" style="display: none;">
                            <div id="fecha_dia" class="campo-fecha" style="display: none;">
                                <div class="form-group mb-0">
                                    <label class="text-muted small mb-2">Fecha</label>
                                    <input type="text" class="form-control datepicker-dia" name="fecha_dia" placeholder="dd/mm/yyyy" autocomplete="off">
                                </div>
                            </div>

                            <div id="fecha_mes" class="campo-fecha" style="display: none;">
                                <div class="form-group mb-0">
                                    <label class="text-muted small mb-2">Mes</label>
                                    <input type="text" class="form-control datepicker-mes" name="fecha_mes" placeholder="mm/yyyy" autocomplete="off">
                                </div>
                            </div>

                            <div id="fecha_entre" class="campo-fecha" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label class="text-muted small mb-2">Fecha Desde</label>
                                            <input type="text" class="form-control datepicker-desde" name="fecha_desde" placeholder="dd/mm/yyyy" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label class="text-muted small mb-2">Fecha Hasta</label>
                                            <input type="text" class="form-control datepicker-hasta" name="fecha_hasta" placeholder="dd/mm/yyyy" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- SECCIÓN: TRAMOS --}}
                        <div class="row mb-4 col-md-4">
                            <div class="col-12">
                                <div class="form-group mb-0">
                                    <label class="text-muted small mb-2">Tramos (seleccione uno o más)</label>
                                    <div class="btn-group-toggle d-flex flex-wrap">
                                        @foreach(range(0, 4) as $t)
                                        <label class="btn btn-outline-primary mr-2 mb-2 flex-fill tramo-btn" style="min-width: 60px;">
                                            <input type="checkbox" name="tramo[]" value="{{ $t }}"> T{{ $t }}
                                        </label>
                                        @endforeach
                                        <label class="btn btn-outline-warning mr-2 mb-2 flex-fill tramo-btn" style="min-width: 100px;">
                                            <input type="checkbox" name="tramo[]" value="5"> Mora
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- SECCIÓN: SUCURSALES - CHECKBOXES SIMPLES SIEMPRE VISIBLES --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <label class="text-muted small mb-2 d-block">
                                <i class="fas fa-building mr-2"></i>Sucursales (seleccione una o varias)
                            </label>
                            <div class="border rounded p-3 bg-light">
                                @foreach($zonas as $zona)
                                    @if($zona->sucursales->isNotEmpty())
                                        {{-- Título de la zona --}}
                                        <div class="mb-3">
                                            <span class="badge badge-primary badge-lg mb-2">{{ $zona->nombre }}</span>
                                            <div class="d-flex flex-wrap">
                                                @foreach($zona->sucursales as $sucursal)
                                                    <label class="btn btn-outline-info btn-sm mr-2 mb-2 sucursal-checkbox-btn" style="min-width: 120px;">
                                                        <input type="checkbox" name="sucursal_id[]" value="{{ $sucursal->id }}" class="d-none">
                                                        <i class="fas fa-check mr-1" style="display: none;"></i>
                                                        {{ $sucursal->sucursal }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            <small class="form-text text-muted mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Haga clic en las sucursales que desea consultar. Puede seleccionar múltiples.
                            </small>
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- SECCIÓN: PERSONAL --}}
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label class="text-muted small mb-2">JCC (puede seleccionar varios)</label>
                                <select class="form-control select2-multiple" name="jcc_id[]" id="jcc_id" multiple="multiple">
                                    @foreach($jccs as $jcc)
                                        <option value="{{ $jcc->id }}">{{ $jcc->codigo ?? ($jcc->persona->nombres ?? 'N/A') }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label class="text-muted small mb-2">Asesor (puede seleccionar varios)</label>
                                <select class="form-control select2-multiple" name="asesor_id[]" id="asesor_id" multiple="multiple">
                                    @foreach($asesores as $asesor)
                                        <option value="{{ $asesor->id }}">{{ $asesor->codigo ?? ($asesor->persona->nombres ?? 'N/A') }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label class="text-muted small mb-2">Analista (puede seleccionar varios)</label>
                                <select class="form-control select2-multiple" name="analista_id[]" id="analista_id" multiple="multiple">
                                    @foreach($analistas as $analista)
                                        <option value="{{ $analista->id }}">{{ $analista->codigo ?? ($analista->persona->nombres ?? 'N/A') }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- SECCIÓN: ESTADOS CREDITICIOS --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="form-group mb-0">
                                <label class="text-muted small mb-2">Estados Crediticios (seleccione uno o más)</label>
                                <div class="btn-group-toggle d-flex flex-wrap">
                                    <label class="btn btn-outline-success mr-2 mb-2 estado-btn" style="min-width: 120px;">
                                        <input type="checkbox" name="estado[]" value="ACTIVO"> ACTIVO
                                    </label>
                                    <label class="btn btn-outline-secondary mr-2 mb-2 estado-btn" style="min-width: 120px;">
                                        <input type="checkbox" name="estado[]" value="INACTIVO"> INACTIVO
                                    </label>
                                    <label class="btn btn-outline-warning mr-2 mb-2 estado-btn" style="min-width: 180px;">
                                        <input type="checkbox" name="estado[]" value="EN MORA/ACTIVA"> EN MORA/ACTIVA
                                    </label>
                                    <label class="btn btn-outline-warning mr-2 mb-2 estado-btn" style="min-width: 180px;">
                                        <input type="checkbox" name="estado[]" value="EN MORA/INACTIVA"> EN MORA/INACTIVA
                                    </label>
                                    <label class="btn btn-outline-danger mr-2 mb-2 estado-btn" style="min-width: 220px;">
                                        <input type="checkbox" name="estado[]" value="CREDITO VENCIDO/ACTIVO"> CREDITO VENCIDO/ACTIVO
                                    </label>
                                    <label class="btn btn-outline-danger mr-2 mb-2 estado-btn" style="min-width: 220px;">
                                        <input type="checkbox" name="estado[]" value="CREDITO VENCIDO/INACTIVO"> CREDITO VENCIDO/INACTIVO
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- FILTROS ACTIVOS --}}
                    <div id="activeFiltersBadge" class="mt-2 d-none">
                        <small class="text-muted mr-2">Filtros:</small>
                        <div id="badgeContainer" class="d-inline-block"></div>
                    </div>

                    <hr class="my-4">

                    {{-- SECCIÓN DE ACCIONES --}}
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <button type="button" id="btnBuscar" class="btn btn-primary btn-lg px-4 mr-2">
                                <i class="fas fa-search mr-2"></i> Buscar
                            </button>
                            <button type="reset" id="btnReset" class="btn btn-outline-secondary btn-lg px-4">
                                <i class="fas fa-eraser mr-2"></i> Limpiar
                            </button>
                        </div>

                        <div class="col-md-4 text-center">
                            <div class="py-2">
                                <span class="text-muted">Registros encontrados</span>
                                <h3 class="mb-0 text-primary font-weight-bold" id="contador-registros">0</h3>
                            </div>
                        </div>

                        <div class="col-md-4 text-right">
                            <button type="button" class="btn btn-outline-success btn-lg" id="exportExcel">
                                <i class="fas fa-file-excel mr-1"></i> Excel
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-lg ml-2" id="exportPDF">
                                <i class="fas fa-file-pdf mr-1"></i> PDF
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>

        {{-- INFO BOXES (Resumen) --}}
        <div class="row" id="statsRow" style="display: none;">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-file-invoice"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Registros</span>
                        <span class="info-box-number" id="statsCount">0</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-money-bill-wave"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Crédito Total</span>
                        <span class="info-box-number" id="statsCredito">S/ 0.00</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-2">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-hand-holding-usd"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Deuda Total</span>
                        <span class="info-box-number" id="statsDeuda">S/ 0.00</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-2">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-exclamation-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Mora Total</span>
                        <span class="info-box-number" id="statsMora">S/ 0.00</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-2">
                 <div class="info-box mb-3">
                    <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Promedio Atraso</span>
                        <span class="info-box-number" id="statsAtraso">0 días</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABLA DE RESULTADOS --}}
        <div class="card shadow-sm">
            <div class="card-header border-0">
                <h3 class="card-title text-muted">Resultados de la Búsqueda</h3>
                <div class="card-tools">
                   <span class="badge badge-light border" id="resultCountBadge">0 registros</span>
                </div>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-striped table-hover table-valign-middle" id="resultsTable">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 5%">#</th>
                            <th style="width: 10%">DNI</th>
                            <th>Cliente</th>
                            <th style="width: 8%">Tipo</th>
                            <th>Zona / Sucursal</th>
                            <th class="text-right">Crédito Total</th>
                            <th class="text-right">Cuota Sem.</th>
                            <th class="text-center">Cuotas Pagadas</th>
                            <th class="text-center">Cuotas Vencidas</th>
                            <th class="text-center">F. Ini. Atraso</th>
                            <th class="text-center">Últ. Pago Cuota</th>
                            <th class="text-center">Últ. Pago Mora</th>
                            <th class="text-right">Deuda Total</th>
                            <th>Estado crediticio</th>
                            <th>Tramo</th>
                            <th class="text-right">Mora Total</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                         <tr>
                            <td colspan="16" class="text-center py-5 text-muted">
                                <i class="fas fa-search fa-3x mb-3 text-gray-300"></i>
                                <p class="mb-0">Utilice los filtros para buscar información</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- CONTROLES DE PAGINACIÓN --}}
            <div class="card-footer" id="paginationControls" style="display: none;">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <span id="paginationInfo" class="text-muted"></span>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary" id="btnPrevPage">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </button>
                            <button type="button" class="btn btn-outline-primary disabled" id="btnCurrentPage">
                                Página 1
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="btnNextPage">
                                Siguiente <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <select id="perPageSelect" class="form-control" style="width: auto; display: inline-block;">
                            <option value="25">25 por página</option>
                            <option value="50" selected>50 por página</option>
                            <option value="100">100 por página</option>
                            <option value="200">200 por página</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Overlay de carga global --}}
<div id="globalLoadingOverlay">
    <div class="loading-content">
        <i class="fas fa-spinner fa-spin fa-3x"></i>
        <p class="mt-3">Cargando datos, por favor espere...</p>
    </div>
</div>

@stop

@section('js')
{{-- SheetJS - Librería para exportar a Excel --}}
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>

{{-- Bootstrap Datepicker JS --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.es.min.js"></script>

<script>
    // --- DATA FROM API ---
    let currentData = [];
    let currentPage = 1;
    let totalPages = 1;
    let totalRecords = 0;

    $(document).ready(function() {
        console.log('Página cargada, inicializando componentes...');

        // --- INICIALIZAR SELECT2 SOLO PARA JCC, ASESOR, ANALISTA ---
        if (typeof $.fn.select2 !== 'undefined') {
            $('#jcc_id, #asesor_id, #analista_id').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: 'Seleccione uno o varios...',
                allowClear: true,
                closeOnSelect: false
            });
        }

        // --- MANEJO DE SUCURSALES CON CHECKBOX-BUTTONS ---
        $('.sucursal-checkbox-btn').on('click', function(e) {
            e.preventDefault();

            const $checkbox = $(this).find('input[type="checkbox"]');
            const $btn = $(this);

            // Toggle checkbox
            $checkbox.prop('checked', !$checkbox.is(':checked'));

            // Toggle estilo visual
            if ($checkbox.is(':checked')) {
                $btn.addClass('active');
            } else {
                $btn.removeClass('active');
            }

            // Debug: Mostrar sucursales seleccionadas
            const selected = $('input[name="sucursal_id[]"]:checked').map(function(){
                return $(this).val();
            }).get();
            console.log('✅ Sucursales marcadas:', selected);
        });

        // --- MANEJO DE TIPO DE RANGO DE FECHA ---
        $('#tipo_rango_fecha').on('change', function() {
            const tipoRango = $(this).val();
            $('.campo-fecha').hide();

            if (tipoRango) {
                $('#contenedor_fechas').show();

                if (tipoRango === 'dia') {
                    $('#fecha_dia').show();
                } else if (tipoRango === 'mes') {
                    $('#fecha_mes').show();
                } else if (tipoRango === 'entre_fechas') {
                    $('#fecha_entre').show();
                }
            } else {
                $('#contenedor_fechas').hide();
            }
        });

        // --- INICIALIZAR DATEPICKERS ---
        function initDatepickers() {
            console.log('Intentando inicializar datepickers...');
            console.log('jQuery disponible:', typeof $ !== 'undefined');
            console.log('Datepicker disponible:', typeof $.fn.datepicker !== 'undefined');

            if (typeof $.fn.datepicker === 'undefined') {
                console.error('Bootstrap Datepicker no está disponible - esperando...');
                return false;
            }

            // Destruir instancias previas si existen
            try {
                $('.datepicker-dia, .datepicker-mes, .datepicker-desde, .datepicker-hasta').datepicker('destroy');
            } catch(e) {
                console.log('No hay datepickers previos para destruir');
            }

            // Datepicker Por Día
            $('.datepicker-dia').datepicker({
                format: 'dd/mm/yyyy',
                language: 'es',
                autoclose: true,
                todayHighlight: true,
                clearBtn: true,
                orientation: 'bottom auto'
            });

            // Datepicker Por Mes
            $('.datepicker-mes').datepicker({
                format: 'mm/yyyy',
                language: 'es',
                autoclose: true,
                todayHighlight: true,
                clearBtn: true,
                minViewMode: 'months',
                orientation: 'bottom auto'
            });

            // Datepicker Fecha Desde
            $('.datepicker-desde').datepicker({
                format: 'dd/mm/yyyy',
                language: 'es',
                autoclose: true,
                todayHighlight: true,
                clearBtn: true,
                orientation: 'bottom auto'
            }).on('changeDate', function(e) {
                $('.datepicker-hasta').datepicker('setStartDate', e.date);
            });

            // Datepicker Fecha Hasta
            $('.datepicker-hasta').datepicker({
                format: 'dd/mm/yyyy',
                language: 'es',
                autoclose: true,
                todayHighlight: true,
                clearBtn: true,
                orientation: 'bottom auto'
            }).on('changeDate', function(e) {
                $('.datepicker-desde').datepicker('setEndDate', e.date);
            });

            console.log('Datepickers inicializados correctamente');
            return true;
        }

        // Intentar inicializar datepickers con retry
        let retries = 0;
        const maxRetries = 5;
        const tryInit = function() {
            if (initDatepickers()) {
                console.log('Datepickers listos!');
            } else {
                retries++;
                if (retries < maxRetries) {
                    console.log('Reintentando inicialización... (' + retries + '/' + maxRetries + ')');
                    setTimeout(tryInit, 300);
                } else {
                    console.error('No se pudo inicializar datepickers después de ' + maxRetries + ' intentos');
                }
            }
        };

        setTimeout(tryInit, 100);

        // --- MANEJO DE BOTONES DE TRAMOS (SELECCIÓN MÚLTIPLE) ---
        $('.tramo-btn').on('click', function(e) {
            e.preventDefault(); // Prevenir comportamiento por defecto

            const $checkbox = $(this).find('input[type="checkbox"]');
            const $label = $(this);

            // Toggle del checkbox manualmente
            $checkbox.prop('checked', !$checkbox.is(':checked'));

            // Toggle de la clase active
            if ($checkbox.is(':checked')) {
                $label.addClass('active');
            } else {
                $label.removeClass('active');
            }

            // NO ejecutar búsqueda automáticamente - solo actualizar estado visual
        });

        // --- MANEJO DE BOTONES DE ESTADOS (SELECCIÓN MÚLTIPLE) ---
        $('.estado-btn').on('click', function(e) {
            e.preventDefault(); // Prevenir comportamiento por defecto

            const $checkbox = $(this).find('input[type="checkbox"]');
            const $label = $(this);

            // Toggle del checkbox manualmente
            $checkbox.prop('checked', !$checkbox.is(':checked'));

            // Toggle de la clase active
            if ($checkbox.is(':checked')) {
                $label.addClass('active');
            } else {
                $label.removeClass('active');
            }

            // NO ejecutar búsqueda automáticamente - solo actualizar estado visual
        });

        // Sincronizar estado visual si el checkbox cambia programáticamente
        $('.btn-group-toggle input[type="checkbox"]').on('change', function() {
            const $label = $(this).parent('label');
            if ($(this).is(':checked')) {
                $label.addClass('active');
            } else {
                $label.removeClass('active');
            }
        });

        // --- NO CARGAR DATOS AL INICIO ---
        // Usuario debe seleccionar filtros y hacer clic en "Buscar"

        // --- BOTÓN BUSCAR ---
        $('#btnBuscar').on('click', function() {
            currentPage = 1; // Resetear a página 1 al buscar
            applyFilters();
        });

        // --- SUBMIT ---
        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            currentPage = 1;
            applyFilters();
        });

        // --- PAGINACIÓN ---
        $('#btnPrevPage').on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                applyFilters();
            }
        });

        $('#btnNextPage').on('click', function() {
            if (currentPage < totalPages) {
                currentPage++;
                applyFilters();
            }
        });

        $('#perPageSelect').on('change', function() {
            currentPage = 1; // Resetear a página 1 al cambiar items por página
            applyFilters();
        });

        // --- RESET ---
        $('#btnReset').click(function(e) {
            e.preventDefault();

            // Reset tipo de consulta
            $('#tipo_consulta').val('ambos');

            // Reset Select2 multiselect (JCC, Asesor, Analista)
            $('#jcc_id, #asesor_id, #analista_id').val(null);
            if (typeof $.fn.select2 !== 'undefined') {
                $('#jcc_id, #asesor_id, #analista_id').trigger('change.select2');
            }

            // Reset Sucursales checkboxes
            $('input[name="sucursal_id[]"]').prop('checked', false);
            $('.sucursal-checkbox-btn').removeClass('active');

            // Reset Tramos Buttons - desmarcar todos
            $('.tramo-btn').removeClass('active');
            $('.tramo-btn input[type="checkbox"]').prop('checked', false);

            // Reset Estados Buttons - desmarcar todos
            $('.estado-btn').removeClass('active');
            $('.estado-btn input[type="checkbox"]').prop('checked', false);

            // Reset Tipo de Rango de Fecha
            $('#tipo_rango_fecha').val('');
            $('.campo-fecha').hide();
            $('#contenedor_fechas').hide();

            // Reset Datepickers
            $('.datepicker-dia, .datepicker-mes, .datepicker-desde, .datepicker-hasta').val('').datepicker('clearDates');

            // Reset búsqueda general
            $('#busqueda_general').val('');

            // Limpiar badges
            $('#activeFiltersBadge').addClass('d-none');

            // Limpiar tabla y stats
            $('#tableBody').html('<tr><td colspan="16" class="text-center py-5 text-muted"><i class="fas fa-search fa-3x mb-3 text-gray-300"></i><p class="mb-0">Utilice los filtros para buscar información</p></td></tr>');
            $('#statsRow').hide();
            $('#paginationControls').hide();
            $('#resultCountBadge').text('0 registros');
            $('#contador-registros').text('0');
            currentPage = 1;
            currentData = [];
        });

        // --- EXPORT ---
        $('#exportExcel').click(function() {
            if (totalRecords === 0) {
                alert('No hay datos para exportar. Por favor, realice una consulta primero.');
                return;
            }

            // Mostrar overlay de carga
            $('#globalLoadingOverlay').addClass('show');

            // Recopilar los mismos filtros que se usaron en la búsqueda
            const filters = {};

            // Tipo de consulta (Préstamos/Convenios/Ambos)
            const tipoConsulta = $('#tipo_consulta').val();
            if (tipoConsulta) {
                filters.tipo = tipoConsulta;
            }

            // Búsqueda general
            if ($('#busqueda_general').val()) {
                filters.search = $('#busqueda_general').val();
            }

            // Tramos seleccionados
            const tramos = $('input[name="tramo[]"]:checked').map(function(){ return parseInt($(this).val()) }).get();
            if (tramos.length > 0) {
                filters.tramos = tramos;
            }

            // Sucursales seleccionadas desde checkboxes
            const sucursales = $('input[name="sucursal_id[]"]:checked').map(function(){
                return parseInt($(this).val());
            }).get();

            if (sucursales.length > 0) {
                filters.sucursal_id = sucursales;
                console.log('🏢 Sucursales enviadas al servidor:', sucursales);
            }

            // Fechas Desde/Hasta
            if ($('[name="fecha_desde"]').val()) {
                filters.fecha_desde = $('[name="fecha_desde"]').val();
            }
            if ($('[name="fecha_hasta"]').val()) {
                filters.fecha_hasta = $('[name="fecha_hasta"]').val();
            }

            // JCC, Asesor, Analista
            const jccIds = $('#jcc_id').val();
            if (jccIds && jccIds.length > 0) {
                filters.jcc_id = jccIds;
            }

            const asesorIds = $('#asesor_id').val();
            if (asesorIds && asesorIds.length > 0) {
                filters.asesor_id = asesorIds;
            }

            const analistaIds = $('#analista_id').val();
            if (analistaIds && analistaIds.length > 0) {
                filters.analista_id = analistaIds;
            }

            // Estados crediticios seleccionados
            const estados = $('input[name="estado[]"]:checked').map(function(){ return $(this).val() }).get();
            if (estados.length > 0) {
                filters.estado = estados;
            }

            // IMPORTANTE: NO enviar parámetros de paginación para obtener todos los registros
            filters.export_all = true;

            // Hacer llamada Ajax para obtener TODOS los datos filtrados
            $.ajax({
                url: '{{ route("admin.deudas.tramos.data") }}',
                type: 'POST',
                data: filters,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        // --- Información de cabecera ---
                        const now = new Date();
                        const fechaHora = now.toLocaleDateString('es-PE') + ' ' + now.toLocaleTimeString('es-PE');
                        const usuario = @json(auth()->user() ? (auth()->user()->codigo ?? '') . ' - ' . trim((auth()->user()->full_name ?? auth()->user()->name ?? '')) : 'N/A');

                        // Tramos seleccionados
                        const tramosTexto = {0:'Tramo 0 (0-6 dias)',1:'Tramo 1 (7-14 dias)',2:'Tramo 2 (15-21 dias)',3:'Tramo 3 (22-30 dias)',4:'Tramo 4 (31+ dias)',5:'Solo mora'};
                        const tramosSelArr = $('input[name="tramo[]"]:checked').map(function(){ return parseInt($(this).val()); }).get();
                        const tramosInfo = tramosSelArr.length > 0 ? tramosSelArr.map(t => tramosTexto[t] || 'Tramo '+t).join(', ') : 'Todos';

                        // Sucursales y zonas seleccionadas
                        const sucursalesInfo = [];
                        const zonasInfo = [];
                        $('input[name="sucursal_id[]"]:checked').each(function() {
                            sucursalesInfo.push($(this).closest('.sucursal-checkbox-btn').text().trim());
                        });
                        $('input[name="sucursal_id[]"]:checked').each(function() {
                            const zonaNombre = $(this).closest('.mb-3').find('.badge-primary').text().trim();
                            if (zonaNombre && zonasInfo.indexOf(zonaNombre) === -1) {
                                zonasInfo.push(zonaNombre);
                            }
                        });

                        // Filas de cabecera
                        const headerRows = [
                            ['REPORTE DE TRAMOS'],
                            [],
                            ['Usuario:', usuario],
                            ['Fecha y Hora:', fechaHora],
                            ['Tramos:', tramosInfo],
                            ['Zona:', zonasInfo.length > 0 ? zonasInfo.join(', ') : 'Todas', '', 'Sucursal:', sucursalesInfo.length > 0 ? sucursalesInfo.join(', ') : 'Todas'],
                            [],
                        ];

                        // Crear tabla de datos
                        const exportData = response.data.map(item => ([
                            item.numero,
                            item.dni || 'N/A',
                            item.nombre || item.cliente_nombre || 'N/A',
                            item.zona || 'N/A',
                            item.sucursal || 'N/A',
                            parseFloat(item.creditoTotal || 0),
                            parseFloat(item.montoCuota || 0),
                            `${item.cuotasPagadas || 0}/${item.cuotasTotal || 0}`,
                            item.numerosCuotasVencidas && item.numerosCuotasVencidas.length > 0
                                ? item.numerosCuotasVencidas.join(', ')
                                : '0',
                            item.primeraCuotaVencidaNumero
                                ? `Cuota #${item.primeraCuotaVencidaNumero} (${item.primeraCuotaVencidaFecha})`
                                : '-',
                            item.fechaUltimoPagoCuota || '-',
                            item.fechaUltimoPagoMora || '-',
                            parseFloat(item.deudaReal || 0),
                            item.estado || 'N/A',
                            item.tramo === 5 ? 'Solo mora' : (item.tramo !== null ? `T${item.tramo}` : 'N/A'),
                            parseFloat(item.moraAcumulada || 0)
                        ]));

                        // Encabezados de columna
                        const colHeaders = ['#', 'DNI', 'Cliente', 'Zona', 'Sucursal', 'Credito Total', 'Cuota Sem.', 'Cuotas Pagadas', 'Cuotas Vencidas', 'F. Ini. Atraso', 'Últ. Pago Cuota', 'Últ. Pago Mora', 'Deuda Total', 'Estado crediticio', 'Tramo', 'Mora Total'];

                        // Combinar todo: cabecera + encabezados + datos
                        const allRows = [...headerRows, colHeaders, ...exportData];

                        // Crear worksheet desde array
                        const ws = XLSX.utils.aoa_to_sheet(allRows);

                        // Ajustar ancho de columnas
                        ws['!cols'] = [
                            { wch: 5 },   // #
                            { wch: 12 },  // DNI
                            { wch: 30 },  // Cliente
                            { wch: 20 },  // Zona
                            { wch: 20 },  // Sucursal
                            { wch: 14 },  // Credito
                            { wch: 12 },  // Cuota Sem.
                            { wch: 14 },  // Pagadas
                            { wch: 15 },  // Vencidas
                            { wch: 25 },  // 1ra No Pagada
                            { wch: 14 },  // Últ. Pago Cuota
                            { wch: 14 },  // Últ. Pago Mora
                            { wch: 14 },  // Deuda Total
                            { wch: 20 },  // Estado
                            { wch: 12 },  // Tramo
                            { wch: 14 }   // Mora Total
                        ];

                        // Merge para el titulo
                        ws['!merges'] = [
                            { s: { r: 0, c: 0 }, e: { r: 0, c: 15 } }
                        ];

                        const wb = XLSX.utils.book_new();
                        XLSX.utils.book_append_sheet(wb, ws, "Tramos");

                        // Generar archivo
                        XLSX.writeFile(wb, 'Reporte_Tramos_' + new Date().toISOString().slice(0,10) + '.xlsx');

                        console.log('Excel exportado con ' + response.data.length + ' registros');
                    } else {
                        alert('No se pudieron obtener los datos para exportar.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al exportar:', error);
                    alert('Error al exportar los datos. Por favor, intente nuevamente.');
                },
                complete: function() {
                    $('#globalLoadingOverlay').removeClass('show');
                }
            });
        });

        $('#exportPDF').click(function() {
            if (currentData.length === 0) {
                alert('No hay datos para exportar. Por favor, realice una consulta primero.');
                return;
            }
            alert('Funcionalidad PDF en desarrollo. Use Excel por ahora.');
        });

        function applyFilters() {
            // Mostrar overlay de carga
            $('#globalLoadingOverlay').addClass('show');

            // Recopilar todos los filtros del formulario
            const formData = new FormData($('#filterForm')[0]);

            // Convertir FormData a objeto JSON
            const filters = {};

            // Tipo de consulta (Préstamos/Convenios/Ambos)
            const tipoConsulta = $('#tipo_consulta').val();
            if (tipoConsulta) {
                filters.tipo = tipoConsulta;
            }

            // Búsqueda general
            if ($('#busqueda_general').val()) {
                filters.search = $('#busqueda_general').val();
            }

            // Tramos seleccionados
            const tramos = $('input[name="tramo[]"]:checked').map(function(){ return parseInt($(this).val()) }).get();
            if (tramos.length > 0) {
                filters.tramos = tramos;
            }

            // Sucursales seleccionadas desde checkboxes
            const sucursales = $('input[name="sucursal_id[]"]:checked').map(function(){
                return parseInt($(this).val());
            }).get();

            if (sucursales.length > 0) {
                filters.sucursal_id = sucursales;
                // Debug: Mostrar qué sucursales se están enviando
                console.log('🏢 Sucursales seleccionadas:', filters.sucursal_id);
            }

            // Fechas Desde/Hasta
            if ($('[name="fecha_desde"]').val()) {
                filters.fecha_desde = $('[name="fecha_desde"]').val();
            }
            if ($('[name="fecha_hasta"]').val()) {
                filters.fecha_hasta = $('[name="fecha_hasta"]').val();
            }

            // JCC, Asesor, Analista (múltiples selecciones)
            const jccIds = $('#jcc_id').val();
            if (jccIds && jccIds.length > 0) {
                filters.jcc_id = jccIds; // Ya es un array
            }

            const asesorIds = $('#asesor_id').val();
            if (asesorIds && asesorIds.length > 0) {
                filters.asesor_id = asesorIds;
            }

            const analistaIds = $('#analista_id').val();
            if (analistaIds && analistaIds.length > 0) {
                filters.analista_id = analistaIds;
            }

            // Estados crediticios seleccionados
            const estados = $('input[name="estado[]"]:checked').map(function(){ return $(this).val() }).get();
            if (estados.length > 0) {
                filters.estado = estados;
            }

            // Agregar parámetros de paginación
            filters.page = currentPage;
            filters.per_page = parseInt($('#perPageSelect').val()) || 50;

            // Hacer llamada Ajax
            $.ajax({
                url: '{{ route("admin.deudas.tramos.data") }}',
                type: 'POST',
                data: filters,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        currentData = response.data;

                        // Actualizar variables de paginación
                        if (response.pagination) {
                            currentPage = response.pagination.current_page;
                            totalPages = response.pagination.last_page;
                            totalRecords = response.pagination.total;
                            updatePaginationControls(response.pagination);
                        }

                        renderTable(currentData);
                        updateStats(response.totales);
                        updateBadges();
                        $('#statsRow').slideDown();

                        // Mostrar rendimiento en consola
                        if (response.performance) {
                            console.log('⏱️ Tiempo de ejecución: ' + response.performance.execution_time + 's');
                            console.log('📊 Cuotas procesadas: ' + response.performance.cuotas_procesadas);
                            console.log('👥 Total de registros: ' + response.totales.count);
                            console.log('📄 Página actual: ' + currentPage + '/' + totalPages);
                        }
                    } else {
                        alert('Error al obtener datos: ' + (response.error || 'Error desconocido'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error en la solicitud:', error);
                    alert('Error al cargar los datos. Por favor, intente nuevamente.');
                    $('#tableBody').html('<tr><td colspan="16" class="text-center py-4 text-danger">Error al cargar los datos</td></tr>');
                },
                complete: function() {
                    // Ocultar overlay de carga siempre
                    $('#globalLoadingOverlay').removeClass('show');
                }
            });
        }

        function renderTable(data) {
            const tbody = $('#tableBody');

            if(data.length === 0) {
                tbody.html('<tr><td colspan="16" class="text-center py-4">No se encontraron registros</td></tr>');
                $('#resultCountBadge').text('0 registros');
                $('#contador-registros').text('0');
                return;
            }

            let totalCredito = 0;
            let totalMora = 0;
            let totalDeuda = 0;

            // Construir TODO el HTML como string y luego insertar una sola vez (evita DOM reflow por fila)
            const rows = [];

            data.forEach((item, index) => {
                totalCredito += item.creditoTotal;
                totalMora += item.moraAcumulada;

                const deudaTotal = item.deudaReal || 0;
                totalDeuda += deudaTotal;

                let badgeClass = 'secondary';
                let tramoTexto = `Tramo ${item.tramo}`;

                if(item.tramo === 5) {
                    badgeClass = 'warning';
                    tramoTexto = 'Solo mora';
                } else {
                    if(item.tramo === 0) badgeClass = 'success';
                    if(item.tramo >= 1 && item.tramo <= 2) badgeClass = 'warning';
                    if(item.tramo >= 3) badgeClass = 'danger';
                }

                const tipoBadgeClass = item.tipo === 'Convenio' ? 'badge-warning' : 'badge-primary';

                rows.push(`<tr>
                    <td>${index + 1}</td>
                    <td class="font-weight-bold">${item.dni}</td>
                    <td>${item.nombre}</td>
                    <td class="text-center"><span class="badge ${tipoBadgeClass}">${item.tipo || 'Préstamo'}</span></td>
                    <td><span class="badge badge-light border">${item.zona}</span><span class="d-block small text-muted">${item.sucursal}</span></td>
                    <td class="text-right">S/ ${item.creditoTotal.toLocaleString('es-PE', {minimumFractionDigits: 2})}</td>
                    <td class="text-right">S/ ${item.montoCuota.toLocaleString('es-PE', {minimumFractionDigits: 2})}</td>
                    <td class="text-center">${item.cuotasPagadas}/${item.cuotasTotal}</td>
                    <td class="text-center">${item.numerosCuotasVencidas && item.numerosCuotasVencidas.length > 0
                        ? `<span class="badge badge-danger">${item.numerosCuotasVencidas.join(', ')}</span>`
                        : `<span class="badge badge-success">0</span>`}</td>
                    <td class="text-center">${item.primeraCuotaVencidaNumero
                        ? `<strong class="text-danger">Cuota #${item.primeraCuotaVencidaNumero}</strong><br><small class="text-muted">${item.primeraCuotaVencidaFecha}</small>`
                        : `<span class="text-muted">-</span>`}</td>
                    <td class="text-center"><small>${item.fechaUltimoPagoCuota || '<span class="text-muted">-</span>'}</small></td>
                    <td class="text-center"><small>${item.fechaUltimoPagoMora || '<span class="text-muted">-</span>'}</small></td>
                    <td class="text-right font-weight-bold text-primary">S/ ${deudaTotal.toLocaleString('es-PE', {minimumFractionDigits: 2})}</td>
                    <td><span class="badge badge-info">${item.estado}</span><div class="mt-1" title="Fecha último pago"><small class="text-muted"><i class="fas fa-calendar-check mr-1"></i>${item.fechaUltimoPago || 'Sin info'}</small></div></td>
                    <td><span class="badge badge-${badgeClass}">${tramoTexto}</span></td>
                    <td class="text-right text-danger font-weight-bold">S/ ${item.moraAcumulada.toLocaleString('es-PE', {minimumFractionDigits: 2})}</td>
                </tr>`);
            });

            // Insertar todo el HTML de una sola vez
            tbody.html(rows.join(''));

            $('#statsCount').text(data.length);
            $('#statsCredito').text('S/ ' + totalCredito.toLocaleString('es-PE', {minimumFractionDigits: 2}));
            $('#statsDeuda').text('S/ ' + totalDeuda.toLocaleString('es-PE', {minimumFractionDigits: 2}));
            $('#statsMora').text('S/ ' + totalMora.toLocaleString('es-PE', {minimumFractionDigits: 2}));
            $('#statsAtraso').text(Math.round(data.reduce((acc, item) => acc + (item.diasAtraso || 0), 0) / data.length || 0) + ' días');
            $('#resultCountBadge').text(data.length + ' registros');
            $('#contador-registros').text(data.length);
        }

        function updateStats(totales) {
            $('#statsCount').text(totales.count);
            $('#statsCredito').text('S/ ' + totales.credito.toLocaleString('es-PE', {minimumFractionDigits: 2}));
            $('#statsDeuda').text('S/ ' + (totales.deuda || totales.credito).toLocaleString('es-PE', {minimumFractionDigits: 2}));
            $('#statsMora').text('S/ ' + totales.mora.toLocaleString('es-PE', {minimumFractionDigits: 2}));
            $('#statsAtraso').text(Math.round(totales.atraso_promedio || 0) + ' días');
            $('#resultCountBadge').text(totales.count + ' registros');
            $('#contador-registros').text(totales.count);
        }

        function updateBadges() {
            const container = $('#badgeContainer');
            container.empty();
            let badges = [];

            const search = $('#busqueda_general').val();
            if(search) badges.push({label: search, color: 'primary'});

            const tramos = $('input[name="tramo[]"]:checked').length;
            if(tramos > 0 && tramos < 5) badges.push({label: tramos + ' Tramos', color: 'warning'});

            // Contar sucursales seleccionadas
            const sucursalesCount = $('input[name="sucursal_id[]"]:checked').length;
            if(sucursalesCount > 0) badges.push({label: sucursalesCount + ' Sucursal(es)', color: 'info'});

            if(badges.length > 0) {
                $('#activeFiltersBadge').removeClass('d-none');
                badges.forEach(b => {
                    container.append(`<span class="badge badge-${b.color} mr-1 p-1">${b.label}</span>`);
                });
            } else {
                 $('#activeFiltersBadge').addClass('d-none');
            }
        }

        function updatePaginationControls(pagination) {
            if (pagination.total > 0) {
                $('#paginationControls').show();

                // Actualizar info
                $('#paginationInfo').text(`Mostrando ${pagination.from} - ${pagination.to} de ${pagination.total} registros`);
                $('#btnCurrentPage').text(`Página ${pagination.current_page} de ${pagination.last_page}`);

                // Habilitar/deshabilitar botones
                $('#btnPrevPage').prop('disabled', pagination.current_page <= 1);
                $('#btnNextPage').prop('disabled', pagination.current_page >= pagination.last_page);
            } else {
                $('#paginationControls').hide();
            }
        }
    });
</script>
@stop
