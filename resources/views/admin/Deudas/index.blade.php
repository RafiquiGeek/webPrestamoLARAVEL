@extends('layouts.admin')

@section('title', 'Reporte de cuotas y moras')

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
</style>
@endsection

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="m-0 text-dark font-weight-bold">Reporte de cuotas y moras</h1>
            <p class="text-muted"><i class="far fa-calendar-alt mr-1"></i> {{ now()->format('d/m/Y') }}</p>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <!-- Panel de Filtros Mejorados -->
            <form action="{{ route('admin.deudas.index') }}" method="GET" id="filter-form">
                @include('admin.Deudas.filtros_mejorados')
            </form>

            <!-- Tabla de Convenios Agrupados y Resumen -->
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

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                            <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                    @endif

                    <div class="table-responsive" id="tabla-deudas">
                        @if(isset($cuotasAgrupadas))
                            @include('admin.Deudas.table_grouped', ['cuotasAgrupadas' => $cuotasAgrupadas, 'totalMonto' => $totalMonto ?? 0, 'totalMora' => $totalMora ?? 0, 'totalDeuda' => $totalDeuda ?? 0])
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Cargando convenios...</h5>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Formulario oculto para exportar -->
    <form id="export-form" action="{{ route('admin.deudas.index') }}" method="GET" style="display: none;">
        <input type="hidden" name="search" id="exp-search">
        <input type="hidden" name="jcc_id" id="exp-jcc">
        <input type="hidden" name="asesor_id" id="exp-asesor">
        <input type="hidden" name="analista_id" id="exp-analista">
        <input type="hidden" name="sucursal_id" id="exp-sucursal">
        <input type="hidden" name="zona_id" id="exp-zona">
        <input type="hidden" name="dias_mora_min" id="exp-dias-mora-min">
        <input type="hidden" name="dias_mora_max" id="exp-dias-mora-max">
        <input type="hidden" name="cuotas_vencidas" id="exp-cuotas-vencidas">
        <input type="hidden" name="tiene_compromiso" id="exp-tiene-compromiso">
        <input type="hidden" name="tiene_gestion" id="exp-tiene-gestion">
        <input type="hidden" name="tipo" id="exp-tipo">
        <input type="hidden" name="vencimiento_desde" id="exp-vencimiento-desde">
        <input type="hidden" name="vencimiento_hasta" id="exp-vencimiento-hasta">
        <input type="hidden" name="tipo_deuda" id="exp-tipo-deuda">
        <input type="hidden" name="estado_prestamo" id="exp-estado-prestamo">
        <input type="hidden" name="origen" id="exp-origen">
        <input type="hidden" name="export" id="exp-format">
    </form>
@stop

@section('js')
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap (si es necesario) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Bootstrap Datepicker JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.es.min.js"></script>
<!-- deudas.js con versión para evitar caché -->
<script src="{{ asset('js/deudas.js') }}?v={{ time() }}"></script>
<script>
// Configurar rutas para el módulo JavaScript externo
window.DeudasConfig = {
    routes: {
        index: "{{ route('admin.deudas.index') }}",
        zonasBySucursal: "{{ route('admin.deudas.zonasBySucursal') }}",
        sucursalesByZona: "{{ route('admin.deudas.sucursalesByZona') }}",
        previsualizacion: "{{ route('admin.deudas.previsualizacion-estado-cobranza', '') }}",
        descargar: "{{ route('admin.deudas.descargar-estado-cobranza', '') }}"
    }
};
</script>
@stack('js')
@stop