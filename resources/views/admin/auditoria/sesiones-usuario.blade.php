@extends('layouts.admin')

@section('title', 'Sesiones - ' . $user->name)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>
                <i class="fas fa-user-clock me-2"></i>
                Sesiones - {{ $user->name }}
            </h1>
            <p class="mb-0 text-muted">Historial detallado de sesiones del usuario</p>
        </div>
        <div>
            <a href="{{ route('admin.auditoria.sesiones') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="{{ route('admin.auditoria.tiempo-modulos', $user->id) }}" class="btn btn-info">
                <i class="fas fa-chart-bar"></i> Tiempo por Módulos
            </a>
        </div>
    </div>
@stop

@section('content')
<!-- Estadísticas del usuario -->
<!-- Estadísticas del usuario -->
<div class="row mb-4">
    <div class="col-lg-3 col-6">
        <div class="card border-0 shadow-sm h-100 overflow-hidden stats-card">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                     <div>
                        <h3 class="mb-0 font-weight-bolder text-dark">{{ $estadisticas['total_sesiones'] }}</h3>
                        <span class="text-muted text-uppercase small font-weight-bold">Total Sesiones</span>
                    </div>
                    <div class="p-2 rounded bg-light text-info">
                        <i class="fas fa-sign-in-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card border-0 shadow-sm h-100 overflow-hidden stats-card">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                     <div>
                        @php
                            $horas = floor($estadisticas['tiempo_total'] / 3600);
                            $minutos = floor(($estadisticas['tiempo_total'] % 3600) / 60);
                        @endphp
                        <h3 class="mb-0 font-weight-bolder text-dark">{{ $horas }}h {{ $minutos }}m</h3>
                         <span class="text-muted text-uppercase small font-weight-bold">Tiempo Total</span>
                    </div>
                    <div class="p-2 rounded bg-light text-success">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="card border-0 shadow-sm h-100 overflow-hidden stats-card">
             <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                     <div>
                        <h3 class="mb-0 font-weight-bolder text-dark">{{ $estadisticas['sesiones_hoy'] }}</h3>
                        <span class="text-muted text-uppercase small font-weight-bold">Sesiones Hoy</span>
                    </div>
                    <div class="p-2 rounded bg-light text-warning">
                        <i class="fas fa-calendar-day fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
         <div class="card border-0 shadow-sm h-100 overflow-hidden stats-card">
             <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                     <div>
                        @if($estadisticas['sesion_mas_larga'])
                            @php
                                $horasMax = floor($estadisticas['sesion_mas_larga']->total_duration / 3600);
                                $minutosMax = floor(($estadisticas['sesion_mas_larga']->total_duration % 3600) / 60);
                            @endphp
                            <h3 class="mb-0 font-weight-bolder text-dark">{{ $horasMax }}h {{ $minutosMax }}m</h3>
                        @else
                            <h3 class="mb-0 font-weight-bolder text-dark">-</h3>
                        @endif
                        <span class="text-muted text-uppercase small font-weight-bold">Sesión Más Larga</span>
                    </div>
                    <div class="p-2 rounded bg-light text-danger">
                        <i class="fas fa-stopwatch fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-light">
                <h3 class="card-title text-muted"><i class="fas fa-filter mr-2"></i>Filtros de Búsqueda</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.auditoria.sesiones-usuario', $user->id) }}">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="fecha_inicio" class="text-muted small text-uppercase font-weight-bold">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_fin" class="text-muted small text-uppercase font-weight-bold">Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                        </div>
                        <div class="col-md-4">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <button type="submit" class="btn btn-primary mr-2 flex-grow-1">
                                    <i class="fas fa-filter mr-1"></i> Filtrar
                                </button>
                                <a href="{{ route('admin.auditoria.sesiones-usuario', $user->id) }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Módulos más usados -->
@if($estadisticas['modulos_mas_usados']->count() > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h3 class="card-title font-weight-bold text-dark">Módulos Más Utilizados</h3>
            </div>
            <div class="card-body pt-0">
                <div class="row">
                    @foreach($estadisticas['modulos_mas_usados'] as $modulo)
                        @php
                            $horas = floor($modulo->total_duration / 3600);
                            $minutos = floor(($modulo->total_duration % 3600) / 60);
                            $porcentaje = $estadisticas['tiempo_total'] > 0 ? ($modulo->total_duration / $estadisticas['tiempo_total']) * 100 : 0;
                        @endphp
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="d-flex justify-content-between mb-2">
                                     <span class="font-weight-bold text-primary">{{ $modulo->module_name }}</span>
                                     <span class="badge badge-light">{{ $horas }}h {{ $minutos }}m</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: {{ $porcentaje }}%"></div>
                                </div>
                                <small class="text-muted mt-2 d-block">{{ number_format($porcentaje, 1) }}%</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Lista de sesiones -->
<div class="row">
    <div class="col-12">
        <div class="card shadow border-0">
            <div class="card-header bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-history mr-2 text-primary"></i>Historial de Sesiones</h3>
                    <span class="badge badge-pill badge-light border">{{ $sessions->total() }} registros</span>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-top-0 pl-4">Inicio</th>
                            <th class="border-top-0">Fin</th>
                            <th class="border-top-0">Duración</th>
                            <th class="border-top-0">Estado</th>
                            <th class="border-top-0">IP / Info</th>
                            <th class="border-top-0 text-right pr-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sessions as $session)
                            <tr>
                                <td class="pl-4">
                                    <span class="font-weight-bold text-dark d-block">{{ $session->login_time->format('d/m/Y') }}</span>
                                    <small class="text-muted">{{ $session->login_time->format('H:i:s') }}</small>
                                </td>
                                <td>
                                    @if($session->logout_time)
                                        <span class="d-block">{{ $session->logout_time->format('d/m/Y') }}</span>
                                        <small class="text-muted">{{ $session->logout_time->format('H:i:s') }}</small>
                                    @else
                                        <span class="badge badge-soft-success text-success bg-light-success">En curso</span>
                                    @endif
                                </td>
                                <td>
                                    @if($session->total_duration > 0)
                                        <span class="font-mono small font-weight-bold">{{ $session->duration_formatted }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($session->isActive())
                                        <span class="badge badge-success">Activa</span>
                                    @else
                                        <span class="badge badge-secondary">Finalizada</span>
                                    @endif
                                    @if($session->forced_logout)
                                        <span class="badge badge-danger ml-1" title="Cierre Forzado">!</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <small><i class="fas fa-network-wired text-muted mr-1"></i> {{ $session->ip_address }}</small>
                                        @php
                                            $modulosCount = $session->moduleTimeTracking->pluck('module_name')->unique()->count();
                                        @endphp
                                        <small class="text-muted"><i class="fas fa-cube mr-1"></i> {{ $modulosCount }} módulos</small>
                                    </div>
                                </td>
                                <td class="text-right pr-4">
                                    <button type="button" class="btn btn-sm btn-light text-primary shadow-sm" 
                                            data-toggle="modal" data-target="#sessionModal{{ $session->id }}">
                                        <i class="fas fa-eye"></i> Detalles
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal usando Partial -->
                            @include('admin.auditoria.partials.session_modal', ['session' => $session])

                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p>No se encontraron sesiones para este usuario en el periodo seleccionado.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($sessions->hasPages())
                <div class="card-footer bg-white border-top-0">
                    <div class="d-flex justify-content-center">
                        {{ $sessions->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .table th {
        border-top: none;
    }
    .badge {
        font-size: 0.75em;
    }
    .table-sm td, .table-sm th {
        padding: 0.3rem;
    }
    .info-box {
        margin-bottom: 1rem;
    }
    .progress {
        height: 4px;
    }
</style>
@stop