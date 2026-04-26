@extends('layouts.admin')
@section('title', 'Rendición de Cuentas')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-file-invoice-dollar me-2"></i>Rendición de Cuentas</h1>
       <div class="d-flex align-items-center gap-3">
           <a href="{{ route('admin.caja.historialRendiciones') }}" class="btn btn-outline-primary btn-sm">
               <i class="fas fa-history me-1"></i>Historial
           </a>
           <ol class="breadcrumb float-sm-right mb-0">
               <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
               <li class="breadcrumb-item active">Rendición de Cuentas</li>
           </ol>
       </div>
   </div>
@stop

@section('content')
<div class="container-fluid pt-2">
    <!-- Filtros -->
    <div class="account-card mb-4">
        <div class="card-header">
            <h3><i class="fas fa-filter me-2"></i>Filtros de Búsqueda</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Filtros de ubicación -->
                <div class="col-md-2 col-6">
                    <label class="form-label">
                        <i class="fas fa-building me-1 text-muted"></i>Sucursal
                    </label>
                    <select id="sucursal_id" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        @foreach ($sucursales as $sucursal)
                            <option value="{{ $sucursal->id }}" {{ request('sucursal_id') == $sucursal->id ? 'selected' : '' }}>
                                {{ $sucursal->sucursal }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">
                        <i class="fas fa-map-marker-alt me-1 text-muted"></i>Zona
                    </label>
                    <select id="zona_id" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        @foreach ($zonas as $zona)
                            <option value="{{ $zona->id }}" {{ request('zona_id') == $zona->id ? 'selected' : '' }}>
                                {{ $zona->nombre ?? $zona->id }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Filtros de personal 
                <div class="col-md-2 col-6">
                    <label class="form-label">
                        <i class="fas fa-user-tag me-1 text-muted"></i>Asesor
                    </label>
                    <select id="asesor_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach ($asesores as $asesor)
                            <option value="{{ $asesor->id }}" {{ request('asesor_id') == $asesor->id ? 'selected' : '' }}>
                                {{ $asesor->codigo }}
                            </option>
                        @endforeach
                    </select>
                </div>-->
                <div class="col-md-2 col-6">
                    <label class="form-label">
                        <i class="fas fa-user-tie me-1 text-muted"></i>JCC
                    </label>
                    <select id="jcc_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach ($jccs as $jcc)
                            <option value="{{ $jcc->id }}" {{ request('jcc_id') == $jcc->id ? 'selected' : '' }}>
                                {{ $jcc->codigo }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">
                        <i class="fas fa-users me-1 text-muted"></i>Usuarios
                    </label>
                    <select id="user_id" name="user_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach ($usuarios as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->codigo }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Otros filtros -->
                <div class="col-md-1 col-6">
                    <label class="form-label">
                        <i class="fas fa-chart-pie me-1 text-muted"></i>Estado
                    </label>
                    @php
                        $estados = ['0' => 'Por Rendir', '1' => 'Rendido'];
                    @endphp
                    <select id="estado_rendicion" class="form-select form-select-sm">
                        <option value="" {{ (request('estado_rendicion') === null || request('estado_rendicion') === '') ? 'selected' : '' }}>Todos</option>
                        @foreach ($estados as $value => $label)
                            <option value="{{ $value }}" {{ (string) request('estado_rendicion') === (string) $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 col-12">
                    <label class="form-label">
                        <i class="fas fa-calendar-alt me-1 text-muted"></i>Rango de Fechas
                    </label>
                    <div class="row g-2">
                        <div class="col">
                            <input type="date" id="start_date" class="form-control form-control-sm" 
                                   value="{{ request('start_date') ?? now()->subDays(7)->format('Y-m-d') }}">
                        </div>
                        <div class="col">
                            <input type="date" id="end_date" class="form-control form-control-sm" 
                                   value="{{ request('end_date') ?? now()->format('Y-m-d') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row mb-4" id="kpis-container">
        @include('admin.Caja.partials.kpis')
    </div>

    <!-- Resumen por Usuario -->
    <div class="row mb-4" id="charts-container">
        @include('admin.Caja.partials.charts')
    </div>

    <!-- Tabla de Operaciones -->
    <div class="row" id="table-container">
        @include('admin.Caja.partials.table')
    </div>
</div>
@stop

@section('css')
<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
/* Estilos basados en Prestamos/show.blade.php */
.account-card {
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.account-card .card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.account-card .card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0;
}
.form-control {
        height: 0!important;
}

.account-card .card-body {
    padding: 1.5rem;
}

.info-card {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 8px;
    margin-bottom: 0.75rem;
    width: 100%;
    transition: transform 0.2s, box-shadow 0.2s;
}
.text-muted{
    font-size: 8pt;
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.05);
}

.info-card .info-label {
    font-size: 0.75rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    font-weight: 600;
}

.info-card .info-value {
    font-size: 1rem;
    font-weight: 600;
    color: #1a1a1a;
}

.info-card .info-value.small {
    font-size: 0.875rem;
    font-weight: 400;
}

.btn-outline-primary {
    border-color: #005566;
    color: #005566;
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: all 0.2s;
}

.btn-outline-primary:hover {
    background-color: #005566;
    color: #ffffff;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.table th {
    font-weight: 600;
    color: #495057;
    font-size: 0.875rem;
}

.badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.35em 0.6em;
}

@media (max-width: 991px) {
    .account-card .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}

@media (max-width: 576px) {
    .account-card {
        margin: 10px;
    }
    .account-card .card-body {
        padding: 1rem;
    }
    .info-card .info-value {
        font-size: 0.875rem;
    }
}
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    let charts = {};

    function loadRecentHistorial() {
        $.ajax({
            url: '{{ route("admin.caja.getRecentRendiciones") }}',
            method: 'GET',
            success: function(response) {
                $('#historial-container').html(response.historial_html);
            },
            error: function(xhr) {
                $('#historial-container').html(
                    '<div class="text-center py-3">' +
                    '<i class="fas fa-exclamation-triangle text-warning"></i>' +
                    '<small class="text-muted d-block mt-2">Error al cargar historial</small>' +
                    '</div>'
                );
            }
        });
    }

    function updateData() {
        let filters = {
            sucursal_id: $('#sucursal_id').val(),
            zona_id: $('#zona_id').val(),
            asesor_id: $('#asesor_id').val(),
            jcc_id: $('#jcc_id').val(),
            user_id: $('#user_id').val(),
            estado_rendicion: $('#estado_rendicion').val(),
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val()
        };

        $.ajax({
            url: '{{ route("admin.caja.index") }}',
            method: 'GET',
            data: filters,
            success: function(response) {
                $('#kpis-container').html(response.kpis_html);
                $('#charts-container').html(response.charts_html);
                $('#table-container').html(response.table_html);
                if (typeof initUserCharts === 'function') {
                    initUserCharts(response.resumen_usuarios || []);
                }
            },
            error: function(xhr) {
                console.error('Error al actualizar datos:', xhr);
            }
        });
    }

    $('#sucursal_id, #zona_id, #asesor_id, #jcc_id, #estado_rendicion, #start_date, #end_date, #user_id').on('change', function() {
        updateData();
    });

    // Inicializar gráficos después de cargar la página
    setTimeout(function() {
        if (typeof initUserCharts === 'function') {
            initUserCharts(@json($resumenUsuarios));
        }
    }, 100);

    // Cargar historial reciente al iniciar
    loadRecentHistorial();

    // Función initCharts removida - ahora se usa initUserCharts del archivo charts.blade.php

    $('#rendir_asesor').on('click', function() {
        if ($('#asesor_id').val()) {
            window.location.href = '{{ url("admin/caja/rendir-por-asesor") }}/' + $('#asesor_id').val();
        }
    });

    $('#rendir_jcc').on('click', function() {
        if ($('#jcc_id').val()) {
            window.location.href = '{{ url("admin/caja/rendir-por-jcc") }}/' + $('#jcc_id').val();
        }
    });
});

$(document).ready(function() {
    $('#asesor_id, #jcc_id').on('change', function() {
        let asesorSelected = $('#asesor_id').val();
        let jccSelected = $('#jcc_id').val();
        $('#rendir_total').prop('disabled', !(asesorSelected || jccSelected));
    });

    $('#rendir_total').on('click', function() {
        let asesorId = $('#asesor_id').val();
        let jccId = $('#jcc_id').val();
        if (confirm('¿Está seguro de que desea rendir todas las operaciones pendientes?')) {
            if (asesorId) {
                window.location.href = '{{ url("admin/caja/rendir-por-asesor") }}/' + asesorId;
            } else if (jccId) {
                window.location.href = '{{ url("admin/caja/rendir-por-jcc") }}/' + jccId;
            }
        }
    });
    
    // Manejar rendición de todo el efectivo
    $(document).on('click', '#rendir_todo_efectivo', function() {
        if (confirm('¿Está seguro de rendir todo el efectivo pendiente?')) {
            // Crear un formulario temporal para hacer POST
            var form = $('<form>', {
                'method': 'POST',
                'action': '{{ route("admin.caja.cierreDiario") }}'
            });
            
            // Agregar token CSRF
            form.append($('<input>', {
                'type': 'hidden',
                'name': '_token',
                'value': '{{ csrf_token() }}'
            }));
            
            // Agregar al DOM y enviar
            $('body').append(form);
            form.submit();
        }
    });
});
</script>
@stop