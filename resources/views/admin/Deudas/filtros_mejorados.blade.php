{{-- FILTROS PARA REPORTE DE DEUDAS - DISEÑO MEJORADO --}}

<div class="card mb-4 shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-filter mr-2"></i> Filtros de Búsqueda
        </h5>
    </div>

    <div class="card-body">
        {{-- FILA 1: Búsqueda y Filtros Principales --}}
        <div class="row mb-3">
            {{-- Búsqueda de Cliente --}}
            <div class="col-md-4">
                <label class="font-weight-bold text-dark mb-2">
                    <i class="fas fa-search text-primary mr-1"></i> Buscar Cliente
                </label>
                <div class="input-group input-group-lg">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white">
                            <i class="fas fa-user-circle text-muted"></i>
                        </span>
                    </div>
                    <input type="text" name="search" id="search"
                           class="form-control form-control-lg"
                           placeholder="Nombre, DNI o código..."
                           value="{{ request('search') }}">
                </div>
            </div>

            {{-- Tramo de Atraso --}}
            <div class="col-md-5">
                <label class="font-weight-bold text-dark mb-2">
                    <i class="fas fa-layer-group text-warning mr-1"></i> Tramo de Atraso
                    <small class="text-muted font-weight-normal ml-1">(puede seleccionar varios)</small>
                </label>
                <div class="d-flex flex-wrap">
                    @php
                        $tramosSeleccionados = request('tramo', []);
                        if (!is_array($tramosSeleccionados)) {
                            $tramosSeleccionados = [$tramosSeleccionados];
                        }
                    @endphp
                    <label class="btn btn-outline-secondary btn-sm mr-2 mb-2 tramo-checkbox-btn" data-tramo="0">
                        <input type="checkbox" name="tramo[]" value="0" class="d-none"
                            {{ in_array('0', $tramosSeleccionados) ? 'checked' : '' }}>
                        <i class="fas fa-check mr-1" style="display: {{ in_array('0', $tramosSeleccionados) ? 'inline' : 'none' }};"></i>
                        T0 (0-6d)
                    </label>
                    <label class="btn btn-outline-success btn-sm mr-2 mb-2 tramo-checkbox-btn" data-tramo="1">
                        <input type="checkbox" name="tramo[]" value="1" class="d-none"
                            {{ in_array('1', $tramosSeleccionados) ? 'checked' : '' }}>
                        <i class="fas fa-check mr-1" style="display: {{ in_array('1', $tramosSeleccionados) ? 'inline' : 'none' }};"></i>
                        T1 (7-14d)
                    </label>
                    <label class="btn btn-outline-warning btn-sm mr-2 mb-2 tramo-checkbox-btn" data-tramo="2">
                        <input type="checkbox" name="tramo[]" value="2" class="d-none"
                            {{ in_array('2', $tramosSeleccionados) ? 'checked' : '' }}>
                        <i class="fas fa-check mr-1" style="display: {{ in_array('2', $tramosSeleccionados) ? 'inline' : 'none' }};"></i>
                        T2 (15-21d)
                    </label>
                    <label class="btn btn-outline-orange btn-sm mr-2 mb-2 tramo-checkbox-btn" data-tramo="3">
                        <input type="checkbox" name="tramo[]" value="3" class="d-none"
                            {{ in_array('3', $tramosSeleccionados) ? 'checked' : '' }}>
                        <i class="fas fa-check mr-1" style="display: {{ in_array('3', $tramosSeleccionados) ? 'inline' : 'none' }};"></i>
                        T3 (22-30d)
                    </label>
                    <label class="btn btn-outline-danger btn-sm mr-2 mb-2 tramo-checkbox-btn" data-tramo="4">
                        <input type="checkbox" name="tramo[]" value="4" class="d-none"
                            {{ in_array('4', $tramosSeleccionados) ? 'checked' : '' }}>
                        <i class="fas fa-check mr-1" style="display: {{ in_array('4', $tramosSeleccionados) ? 'inline' : 'none' }};"></i>
                        T4 (31+d)
                    </label>
                </div>
            </div>

            {{-- Origen --}}
            <div class="col-md-3">
                <label class="font-weight-bold text-dark mb-2">
                    <i class="fas fa-file-contract text-info mr-1"></i> Origen
                </label>
                <select class="form-control form-control-lg" name="tipo" id="tipo">
                    <option value="ambos" {{ request('tipo', 'ambos') == 'ambos' ? 'selected' : '' }}>Todos</option>
                    <option value="prestamos" {{ request('tipo') == 'prestamos' ? 'selected' : '' }}>Solo Préstamos</option>
                    <option value="convenios" {{ request('tipo') == 'convenios' ? 'selected' : '' }}>Solo Convenios</option>
                </select>
            </div>

            {{-- Estado del Préstamo --}}
            <div class="col-md-2">
                <label class="font-weight-bold text-dark mb-2">
                    <i class="fas fa-flag text-success mr-1"></i> Estado
                </label>
                <select class="form-control form-control-lg" name="estado_prestamo" id="estado_prestamo">
                    <option value="todos" {{ request('estado_prestamo', 'todos') == 'todos' ? 'selected' : '' }}>Todos</option>
                    <option value="vigente" {{ request('estado_prestamo') == 'vigente' ? 'selected' : '' }}>Vigente</option>
                    <option value="vencido" {{ request('estado_prestamo') == 'vencido' ? 'selected' : '' }}>Vencido</option>
                </select>
            </div>
        </div>

        <hr class="my-3">

        {{-- FILA 2: Filtros de Personal --}}
        <div class="row mb-3">
            <div class="col-12 mb-2">
                <h6 class="text-uppercase text-secondary font-weight-bold mb-0" style="font-size: 0.8rem;">
                    <i class="fas fa-users mr-1"></i> Personal Asignado
                </h6>
            </div>

            {{-- JCC --}}
            <div class="col-md-2">
                <label class="text-muted small mb-2">JCC</label>
                <select class="form-control select2" id="jcc_id" name="jcc_id">
                    <option value="">Todos</option>
                    @if(isset($jccs))
                        @foreach($jccs as $jcc)
                            <option value="{{ $jcc->id }}" {{ request('jcc_id') == $jcc->id ? 'selected' : '' }}>
                                {{ $jcc->codigo ?? 'Sin código' }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            {{-- Asesor --}}
            <div class="col-md-2">
                <label class="text-muted small mb-2">Asesor</label>
                <select class="form-control select2" id="asesor_id" name="asesor_id">
                    <option value="">Todos</option>
                    @if(isset($asesores))
                        @foreach($asesores as $asesor)
                            <option value="{{ $asesor->id }}" {{ request('asesor_id') == $asesor->id ? 'selected' : '' }}>
                                {{ $asesor->codigo ?? 'Sin código' }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            {{-- Analista --}}
            <div class="col-md-2">
                <label class="text-muted small mb-2">Analista</label>
                <select class="form-control select2" id="analista_id" name="analista_id">
                    <option value="">Todos</option>
                    @if(isset($analistas))
                        @foreach($analistas as $analista)
                            <option value="{{ $analista->id }}" {{ request('analista_id') == $analista->id ? 'selected' : '' }}>
                                {{ $analista->codigo ?? 'Sin código' }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            {{-- Gestiones --}}
            <div class="col-md-3">
                <label class="text-muted small mb-2">
                    <i class="fas fa-tasks mr-1"></i> Gestiones
                </label>
                <select class="form-control" id="tiene_gestion" name="tiene_gestion">
                    <option value="">Todos</option>
                    <option value="1" {{ request('tiene_gestion') === '1' ? 'selected' : '' }}>Con gestiones</option>
                    <option value="0" {{ request('tiene_gestion') === '0' ? 'selected' : '' }}>Sin gestiones</option>
                </select>
            </div>

            {{-- Compromisos --}}
            <div class="col-md-3">
                <label class="text-muted small mb-2">
                    <i class="fas fa-handshake mr-1"></i> Compromisos
                </label>
                <select class="form-control" id="tiene_compromiso" name="tiene_compromiso">
                    <option value="">Todos</option>
                    <option value="1" {{ request('tiene_compromiso') === '1' ? 'selected' : '' }}>Con compromisos</option>
                    <option value="0" {{ request('tiene_compromiso') === '0' ? 'selected' : '' }}>Sin compromisos</option>
                </select>
            </div>
        </div>

        <hr class="my-3">

        {{-- FILA 3: Filtros de Sucursales - CHECKBOXES --}}
        <div class="row mb-3">
            <div class="col-12 mb-2">
                <h6 class="text-uppercase text-secondary font-weight-bold mb-0" style="font-size: 0.8rem;">
                    <i class="fas fa-building mr-1"></i> Sucursales (seleccione una o varias)
                </h6>
            </div>

            <div class="col-12">
                <div class="border rounded p-3 bg-light">
                    @if(isset($zonas) && $zonas->count() > 0)
                        @foreach($zonas as $zona)
                            @if($zona->sucursales && $zona->sucursales->isNotEmpty())
                                <div class="mb-3">
                                    <span class="badge badge-primary badge-lg mb-2">{{ $zona->nombre }}</span>
                                    <div class="d-flex flex-wrap">
                                        @foreach($zona->sucursales as $sucursal)
                                            <label class="btn btn-outline-info btn-sm mr-2 mb-2 sucursal-checkbox-btn" style="min-width: 120px;">
                                                <input type="checkbox" name="sucursal_id[]" value="{{ $sucursal->id }}" class="d-none"
                                                    {{ in_array($sucursal->id, request('sucursal_id', [])) ? 'checked' : '' }}>
                                                <i class="fas fa-check mr-1" style="display: {{ in_array($sucursal->id, request('sucursal_id', [])) ? 'inline' : 'none' }};"></i>
                                                {{ $sucursal->sucursal }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @else
                        <p class="text-muted">No hay sucursales disponibles</p>
                    @endif
                </div>
                <small class="form-text text-muted mt-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    Haga clic en las sucursales que desea consultar
                </small>
            </div>
        </div>

        <hr class="my-3">

        {{-- FILA 4: Filtros de Fecha --}}
        <div class="row mb-3">
            <div class="col-12 mb-2">
                <h6 class="text-uppercase text-secondary font-weight-bold mb-0" style="font-size: 0.8rem;">
                    <i class="fas fa-calendar-alt mr-1"></i> Rango de Fechas
                </h6>
            </div>

            {{-- Tipo de Rango de Fecha --}}
            <div class="col-md-3">
                <label class="text-dark font-weight-bold mb-2">Tipo de Rango</label>
                <select class="form-control form-control-lg" id="tipo_rango_fecha" name="tipo_rango_fecha">
                    <option value="">Seleccione...</option>
                    <option value="dia" {{ request('tipo_rango_fecha') == 'dia' ? 'selected' : '' }}>Por día</option>
                    <option value="mes" {{ request('tipo_rango_fecha') == 'mes' ? 'selected' : '' }}>Por mes</option>
                    <option value="entre_fechas" {{ request('tipo_rango_fecha') == 'entre_fechas' ? 'selected' : '' }}>Entre fechas</option>
                </select>
            </div>

            {{-- Contenedor dinámico de fechas --}}
            <div class="col-md-9" id="contenedor_fechas" style="{{ request('tipo_rango_fecha') ? '' : 'display: none;' }}">
                {{-- Por día --}}
                <div id="fecha_dia" class="campo-fecha" style="{{ request('tipo_rango_fecha') == 'dia' ? '' : 'display: none;' }}">
                    <label class="text-dark font-weight-bold mb-2">Fecha</label>
                    <input type="text" class="form-control form-control-lg datepicker-dia" name="fecha_dia" placeholder="dd/mm/yyyy" autocomplete="off" value="{{ request('fecha_dia') }}">
                </div>

                {{-- Por mes --}}
                <div id="fecha_mes" class="campo-fecha" style="{{ request('tipo_rango_fecha') == 'mes' ? '' : 'display: none;' }}">
                    <label class="text-dark font-weight-bold mb-2">Mes</label>
                    <input type="text" class="form-control form-control-lg datepicker-mes" name="fecha_mes" placeholder="mm/yyyy" autocomplete="off" value="{{ request('fecha_mes') }}">
                </div>

                {{-- Entre fechas --}}
                <div id="fecha_entre" class="campo-fecha" style="{{ request('tipo_rango_fecha') == 'entre_fechas' ? '' : 'display: none;' }}">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="text-dark font-weight-bold mb-2">Fecha Desde</label>
                            <input type="text" class="form-control form-control-lg datepicker-desde" name="vencimiento_desde" placeholder="dd/mm/yyyy" autocomplete="off" value="{{ request('vencimiento_desde') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="text-dark font-weight-bold mb-2">Fecha Hasta</label>
                            <input type="text" class="form-control form-control-lg datepicker-hasta" name="vencimiento_hasta" placeholder="dd/mm/yyyy" autocomplete="off" value="{{ request('vencimiento_hasta') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4">

        {{-- FILA 5: Acciones y Exportación --}}
        <div class="row align-items-center">
            {{-- Botones de Acción --}}
            <div class="col-md-4">
                <button type="button" id="aplicar-filtros" class="btn btn-primary btn-lg px-5 shadow">
                    <i class="fas fa-search mr-2"></i> Buscar
                </button>
                <button type="button" id="limpiar-filtros" class="btn btn-outline-secondary btn-lg ml-2 shadow-sm">
                    <i class="fas fa-eraser mr-1"></i> Limpiar
                </button>
            </div>

            {{-- Contador de Resultados --}}
            <div class="col-md-4 text-center">
                <div class="py-2">
                    <p class="text-muted mb-1 small">Clientes encontrados</p>
                    <h2 class="mb-0 text-primary font-weight-bold" id="contador-clientes">{{ $totalClientes ?? 0 }}</h2>
                </div>
            </div>

            {{-- Botones de Exportación --}}
            <div class="col-md-4 text-right">
                <div class="btn-group shadow-sm" role="group">
                    <button type="button" class="btn btn-success btn-lg" id="export-excel" title="Exportar a Excel">
                        <i class="fas fa-file-excel mr-1"></i> Excel
                    </button>
                    <button type="button" class="btn btn-danger btn-lg" id="export-pdf" title="Exportar a PDF">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </button>
                    <button type="button" class="btn btn-info btn-lg" id="export-tramos" title="Exportar por Tramos">
                        <i class="fas fa-layer-group mr-1"></i> Tramos
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('css')
<style>
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

    /* Estilos para tramo checkbox-btn */
    .tramo-checkbox-btn {
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 100px;
        text-align: center;
    }
    .tramo-checkbox-btn.active .fa-check {
        display: inline !important;
    }
    .tramo-checkbox-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    .tramo-checkbox-btn[data-tramo="0"].active {
        background-color: #6c757d !important;
        color: white !important;
        border-color: #6c757d !important;
        font-weight: bold;
    }
    .tramo-checkbox-btn[data-tramo="1"].active {
        background-color: #28a745 !important;
        color: white !important;
        border-color: #28a745 !important;
        font-weight: bold;
    }
    .tramo-checkbox-btn[data-tramo="2"].active {
        background-color: #ffc107 !important;
        color: #212529 !important;
        border-color: #ffc107 !important;
        font-weight: bold;
    }
    .tramo-checkbox-btn[data-tramo="3"].active {
        background-color: #fd7e14 !important;
        color: white !important;
        border-color: #fd7e14 !important;
        font-weight: bold;
    }
    .tramo-checkbox-btn[data-tramo="4"].active {
        background-color: #dc3545 !important;
        color: white !important;
        border-color: #dc3545 !important;
        font-weight: bold;
    }
    .btn-outline-orange {
        color: #fd7e14;
        border-color: #fd7e14;
    }
    .btn-outline-orange:hover {
        color: white;
        background-color: #fd7e14;
    }
</style>
@endpush

@push('js')
<script>
$(document).ready(function() {
    console.log('Inicializando filtros mejorados...');

    // Inicializar Select2 SOLO para JCC, Asesor, Analista
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%',
            allowClear: true,
            placeholder: 'Seleccione una opción'
        });
    }

    // --- MANEJO DE TRAMOS CON CHECKBOX-BUTTONS ---
    $('.tramo-checkbox-btn').on('click', function(e) {
        e.preventDefault();

        const $checkbox = $(this).find('input[type="checkbox"]');
        const $btn = $(this);

        // Toggle checkbox
        $checkbox.prop('checked', !$checkbox.is(':checked'));

        // Toggle estilo visual
        if ($checkbox.is(':checked')) {
            $btn.addClass('active');
            $btn.find('.fa-check').show();
        } else {
            $btn.removeClass('active');
            $btn.find('.fa-check').hide();
        }

        // Debug
        const selected = $('input[name="tramo[]"]:checked').map(function(){
            return $(this).val();
        }).get();
        console.log('Tramos seleccionados:', selected);
    });

    // Marcar visualmente los tramos que ya vienen seleccionados
    $('input[name="tramo[]"]:checked').each(function() {
        $(this).closest('.tramo-checkbox-btn').addClass('active');
    });

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
            $btn.find('.fa-check').show();
        } else {
            $btn.removeClass('active');
            $btn.find('.fa-check').hide();
        }

        // Debug: Mostrar sucursales seleccionadas
        const selected = $('input[name="sucursal_id[]"]:checked').map(function(){
            return $(this).val();
        }).get();
        console.log('✅ Sucursales marcadas:', selected);
    });

    // Marcar visualmente los checkboxes que ya vienen seleccionados
    $('input[name="sucursal_id[]"]:checked').each(function() {
        $(this).closest('.sucursal-checkbox-btn').addClass('active');
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
        if (typeof $.fn.datepicker === 'undefined') {
            console.error('Bootstrap Datepicker no está disponible');
            return false;
        }

        // Destruir instancias previas si existen
        try {
            $('.datepicker-dia, .datepicker-mes, .datepicker-desde, .datepicker-hasta').datepicker('destroy');
        } catch(e) {
            // No hay datepickers previos
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
                console.log('Reintentando inicialización datepickers... (' + retries + '/' + maxRetries + ')');
                setTimeout(tryInit, 300);
            } else {
                console.error('No se pudo inicializar datepickers después de ' + maxRetries + ' intentos');
            }
        }
    };

    setTimeout(tryInit, 100);
});
</script>
@endpush
