@extends('layouts.admin')

@section('title', 'Listado de Compromisos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="m-0 text-dark font-weight-bold">Listado de Compromisos</h1>
            <p class="text-muted"><i class="far fa-calendar-alt mr-1"></i> {{ now()->format('d/m/Y') }}</p>
        </div>
        <div>
            <a href="{{ route('admin.compromisos.create') }}" class="btn btn-primary btn-lg shadow-sm">
                <i class="fas fa-plus-circle mr-2"></i> Nuevo Compromiso
            </a>
            <div class="btn-group ml-2">
                <button type="button" class="btn btn-success btn-lg dropdown-toggle shadow-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-file-export mr-2"></i> Exportar
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" id="export-excel">
                        <i class="fas fa-file-excel mr-2 text-success"></i> Exportar a Excel
                    </a>
                    <a class="dropdown-item" href="#" id="export-pdf">
                        <i class="fas fa-file-pdf mr-2 text-danger"></i> Exportar a PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <!-- Panel de Filtros -->
            <div class="card card-outline card-primary mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h3 class="card-title">
                        <i class="fas fa-filter text-primary mr-2"></i> Filtros
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtros de fecha -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6 class="text-uppercase font-weight-bold text-muted mb-3">Filtro por fechas</h6>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="fecha_desde" class="form-label small text-uppercase font-weight-bold text-muted">Desde</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white border-right-0"><i class="far fa-calendar-alt text-primary"></i></span>
                                    </div>
                                    <input type="date" name="fecha_desde" id="fecha_desde" class="form-control border-left-0 filtro-input" value="{{ request('fecha_desde') }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="fecha_hasta" class="form-label small text-uppercase font-weight-bold text-muted">Hasta</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white border-right-0"><i class="far fa-calendar-alt text-primary"></i></span>
                                    </div>
                                    <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control border-left-0 filtro-input" value="{{ request('fecha_hasta') }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-uppercase font-weight-bold text-muted">Periodos predefinidos</label>
                            <div class="btn-group btn-group-toggle d-flex" id="date-presets">
                                <button type="button" class="btn btn-outline-primary flex-fill text-center" data-period="today">Hoy</button>
                                <button type="button" class="btn btn-outline-primary flex-fill text-center" data-period="week">Esta Semana</button>
                                <button type="button" class="btn btn-outline-primary flex-fill text-center" data-period="month">Este Mes</button>
                                <button type="button" class="btn btn-outline-primary flex-fill text-center" data-period="all">Todo</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row align-items-end">
                        <!-- Buscador por Cliente -->
                        <div class="col-md-3 mb-3">
                            <label for="search" class="form-label small text-uppercase font-weight-bold text-muted">Buscar por Cliente</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-primary"></i></span>
                                </div>
                                <input type="text" name="search" id="search" class="form-control border-left-0 filtro-input" placeholder="Nombre o Apellido" value="{{ request('search') }}">
                            </div>
                        </div>

                        <!-- Filtro por JCC -->
                        <div class="col-md-1 mb-3">
                            <label for="jcc_id" class="form-label small text-uppercase font-weight-bold text-muted">JCC</label>
                            <select class="form-control filtro-input" id="jcc_id" name="jcc_id">
                                <option value="">Todos los JCC</option>
                                @foreach($jccs as $jcc)
                                    <option value="{{ $jcc->id }}" {{ request('jcc_id') == $jcc->id ? 'selected' : '' }}>
                                        {{ $jcc->persona && $jcc->persona->nombres ? $jcc->persona->nombres . ' ' . $jcc->persona->ape_pat : 'Sin nombre' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filtro por Asesor -->
                        <div class="col-md-1 mb-3">
                            <label for="asesor_id" class="form-label small text-uppercase font-weight-bold text-muted">Asesor</label>
                            <select class="form-control filtro-input" id="asesor_id" name="asesor_id">
                                <option value="">Todos los Asesores</option>
                                @forelse($asesores as $asesor)
                                    <option value="{{ $asesor->id }}" {{ request('asesor_id') == $asesor->id ? 'selected' : '' }}>
                                        {{ $asesor->persona && $asesor->persona->nombres ? $asesor->persona->nombres . ' ' . $asesor->persona->ape_pat : 'Sin nombre' }}
                                    </option>
                                @empty
                                    <!-- No se mostrarán más opciones si la colección está vacía -->
                                @endforelse
                            </select>
                        </div>

                        <!-- Filtro por Analista -->
                        <div class="col-md-1 mb-3">
                            <label for="analista_id" class="form-label small text-uppercase font-weight-bold text-muted">Analista</label>
                            <select class="form-control filtro-input" id="analista_id" name="analista_id">
                                <option value="">Todos los Analistas</option>
                                @foreach($analistas as $analista)
                                    <option value="{{ $analista->id }}" {{ request('analista_id') == $analista->id ? 'selected' : '' }}>
                                        {{ $analista->persona->nombres }} {{ $analista->persona->ape_pat }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filtro por Estado -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label small text-uppercase font-weight-bold text-muted">Estado</label>
                            <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                                <!-- Opción "Todos" marcada por defecto si no hay estado en la solicitud -->
                                <label class="btn btn-outline-primary flex-fill text-center {{ !request('estado') ? 'active' : '' }}">
                                    <input type="radio" name="estado" value="" class="filtro-input" {{ !request('estado') ? 'checked' : '' }}> Todos
                                </label>
                                
                                <!-- Opción "Pendiente" -->
                                <label class="btn btn-outline-primary flex-fill text-center {{ request('estado') === \App\Models\Compromiso::ESTADO_PENDIENTE ? 'active' : '' }}">
                                    <input type="radio" name="estado" value="{{ \App\Models\Compromiso::ESTADO_PENDIENTE }}" class="filtro-input" {{ request('estado') === \App\Models\Compromiso::ESTADO_PENDIENTE ? 'checked' : '' }}> Pendiente
                                </label>
                                
                                <!-- Opción "Pagado" -->
                                <label class="btn btn-outline-primary flex-fill text-center {{ request('estado') === \App\Models\Compromiso::ESTADO_PAGADO ? 'active' : '' }}">
                                    <input type="radio" name="estado" value="{{ \App\Models\Compromiso::ESTADO_PAGADO }}" class="filtro-input" {{ request('estado') === \App\Models\Compromiso::ESTADO_PAGADO ? 'checked' : '' }}> Pagado
                                </label>
                                
                                <!-- Opción "Postergado" -->
                                <label class="btn btn-outline-primary flex-fill text-center {{ request('estado') === \App\Models\Compromiso::ESTADO_POSTERGADO ? 'active' : '' }}">
                                    <input type="radio" name="estado" value="{{ \App\Models\Compromiso::ESTADO_POSTERGADO }}" class="filtro-input" {{ request('estado') === \App\Models\Compromiso::ESTADO_POSTERGADO ? 'checked' : '' }}> Postergado
                                </label>
                            </div>
                        </div>

                        <!-- Filtro por Días por Vencer -->
                        <div class="col-md-1 mb-3">
                            <label for="dias_por_vencer" class="form-label small text-uppercase font-weight-bold text-muted">Días por vencer</label>
                            <input type="number" name="dias_por_vencer" id="dias_por_vencer" class="form-control filtro-input" min="1" value="{{ request('dias_por_vencer') }}">
                        </div>
                        <!-- Filtro por Días Vencidos -->
                        <div class="col-md-1 mb-3">
                            <label for="dias_vencidos" class="form-label small text-uppercase font-weight-bold text-muted">Días vencidos</label>
                            <input type="number" name="dias_vencidos" id="dias_vencidos" class="form-control filtro-input" min="1" value="{{ request('dias_vencidos') }}">
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12 text-right">
                            <button id="clear-filters" class="btn btn-outline-secondary">
                                <i class="fas fa-broom mr-1"></i> Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Compromisos -->
            <div class="card shadow">
                <div class="card-body p-0">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                    @endif

                    <div class="table-responsive" id="tabla-compromisos">
                        @include('admin.Compromisos.partials.table', ['compromisos' => $compromisos])
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario oculto para exportar -->
    <form id="export-form" action="{{ route('admin.compromisos.index') }}" method="GET" style="display: none;">
        <input type="hidden" name="search" id="exp-search">
        <input type="hidden" name="estado" id="exp-estado">
        <input type="hidden" name="jcc_id" id="exp-jcc">
        <input type="hidden" name="asesor_id" id="exp-asesor">
        <input type="hidden" name="analista_id" id="exp-analista">
        <input type="hidden" name="dias_por_vencer" id="exp-por-vencer">
        <input type="hidden" name="dias_vencidos" id="exp-vencidos">
        <input type="hidden" name="fecha_desde" id="exp-fecha-desde">
        <input type="hidden" name="fecha_hasta" id="exp-fecha-hasta">
        <input type="hidden" name="export" id="exp-format">
    </form>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            let timeoutId;

            function performFilter() {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(function() {
                    const search = $('#search').val();
                    const estado = $('input[name="estado"]:checked').val() || '';
                    const jccId = $('#jcc_id').val();
                    const asesorId = $('#asesor_id').val();
                    const analistaId = $('#analista_id').val();
                    const diasPorVencer = $('#dias_por_vencer').val();
                    const diasVencidos = $('#dias_vencidos').val();
                    const fechaDesde = $('#fecha_desde').val();
                    const fechaHasta = $('#fecha_hasta').val();

                    $.ajax({
                        url: '{{ route("admin.compromisos.index") }}',
                        type: 'GET',
                        data: {
                            search: search,
                            estado: estado,
                            jcc_id: jccId,
                            asesor_id: asesorId,
                            analista_id: analistaId,
                            dias_por_vencer: diasPorVencer,
                            dias_vencidos: diasVencidos,
                            fecha_desde: fechaDesde,
                            fecha_hasta: fechaHasta,
                            ajax: true
                        },
                        success: function(response) {
                            $('#tabla-compromisos').html(response);
                        },
                        error: function(xhr) {
                            console.error('Error:', xhr);
                            alert('Ocurrió un error al filtrar los datos. Código: ' + xhr.status);
                        }
                    });
                }, 300);
            }

            $('.filtro-input').on('input change', performFilter);

            // Configurar periodos predefinidos
            $('#date-presets').on('click', 'button', function() {
                const period = $(this).data('period');
                const today = new Date();
                let startDate = '';
                let endDate = '';
                
                // Formato YYYY-MM-DD
                const formatDate = (date) => {
                    const d = new Date(date);
                    let month = '' + (d.getMonth() + 1);
                    let day = '' + d.getDate();
                    const year = d.getFullYear();
                    
                    if (month.length < 2) month = '0' + month;
                    if (day.length < 2) day = '0' + day;
                    
                    return [year, month, day].join('-');
                };
                
                switch(period) {
                    case 'today':
                        startDate = formatDate(today);
                        endDate = formatDate(today);
                        break;
                    case 'week':
                        const firstDayOfWeek = new Date(today.setDate(today.getDate() - today.getDay()));
                        const lastDayOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + 6));
                        startDate = formatDate(firstDayOfWeek);
                        endDate = formatDate(lastDayOfWeek);
                        break;
                    case 'month':
                        const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                        const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        startDate = formatDate(firstDayOfMonth);
                        endDate = formatDate(lastDayOfMonth);
                        break;
                    case 'all':
                        startDate = '';
                        endDate = '';
                        break;
                }
                
                $('#fecha_desde').val(startDate);
                $('#fecha_hasta').val(endDate);
                performFilter();
            });

            // Ejecutar filtro inicial si hay valores predefinidos
            if ($('#search').val() || $('input[name="estado"]:checked').val() || $('#jcc_id').val() || 
                $('#asesor_id').val() || $('#analista_id').val() || $('#dias_por_vencer').val() || 
                $('#dias_vencidos').val() || $('#fecha_desde').val() || $('#fecha_hasta').val()) {
                performFilter();
            }

            // Limpiar filtros
            $('#clear-filters').click(function(e) {
                e.preventDefault();
                $('#search').val('');
                $('input[name="estado"][value=""]').prop('checked', true);
                $('#jcc_id').val('');
                $('#asesor_id').val('');
                $('#analista_id').val('');
                $('#dias_por_vencer').val('');
                $('#dias_vencidos').val('');
                $('#fecha_desde').val('');
                $('#fecha_hasta').val('');
                
                // Trigger the filter after clearing
                performFilter();
            });

            // Función para establecer valores de exportación
            function setupExportForm(format) {
                $('#exp-search').val($('#search').val());
                $('#exp-estado').val($('input[name="estado"]:checked').val() || '');
                $('#exp-jcc').val($('#jcc_id').val());
                $('#exp-asesor').val($('#asesor_id').val());
                $('#exp-analista').val($('#analista_id').val());
                $('#exp-por-vencer').val($('#dias_por_vencer').val());
                $('#exp-vencidos').val($('#dias_vencidos').val());
                $('#exp-fecha-desde').val($('#fecha_desde').val());
                $('#exp-fecha-hasta').val($('#fecha_hasta').val());
                $('#exp-format').val(format);
                
                $('#export-form').submit();
            }

            // Exportar a Excel
            $('#export-excel').click(function(e) {
                e.preventDefault();
                setupExportForm('excel');
            });

            // Exportar a PDF
            $('#export-pdf').click(function(e) {
                e.preventDefault();
                setupExportForm('pdf');
            });
        });
    </script>
@stop

@section('css')
    <style>
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .card-outline {
            border-top: 4px solid #0056b3;
        }
        .btn-primary {
            background-color: #0056b3;
            border-color: #0056b3;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #003d82;
            border-color: #003d82;
        }
        .btn-success {
            transition: all 0.3s ease;
        }
        .btn-success:hover {
            background-color: #198754;
            border-color: #198754;
        }
        .btn-outline-primary {
            border-color: #0056b3;
            color: #0056b3;
            transition: all 0.3s ease;
        }
        .btn-outline-primary:hover, .btn-outline-primary.active {
            background-color: #0056b3;
            color: white;
        }
        .thead-light th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 12px;
        }
        .text-vencido {
            color: #a71d2a;
            font-weight: bold;
        }
        .text-hoy {
            color: #e0a800;
            font-weight: bold;
        }
        .text-por-vencer {
            color: #d95d00;
            font-weight: bold;
        }
        .text-en-plazo {
            color: #1a8754;
        }
        .badge {
            font-size: 0.9rem;
            padding: 0.5em 0.8em;
            border-radius: 12px;
        }
        .table td, .table th {
            vertical-align: middle;
        }
        .input-group-text {
            border-radius: 8px 0 0 8px;
        }
        .form-control {
            border-radius: 0 8px 8px 0;
            box-shadow: none;
        }
        select.form-control, input[type="date"].form-control {
            border-radius: 8px;
        }
        #date-presets .btn {
            font-size: 0.85rem;
            border-radius: 0;
        }
        #date-presets .btn:first-child {
            border-radius: 8px 0 0 8px;
        }
        #date-presets .btn:last-child {
            border-radius: 0 8px 8px 0;
        }
        #date-presets .btn.active {
            background-color: #0056b3;
            color: white;
        }
        .avatar-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
@stop