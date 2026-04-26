@extends('layouts.admin')

@section('title', 'Metas y Comisiones')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="font-weight-bold text-dark"><i class="fas fa-bullseye text-primary mr-2"></i> Metas y Comisiones</h1>
            <p class="text-muted mb-0">Panel de control de rendimiento y bonificaciones de asesores</p>
        </div>
        <div class="btn-group shadow-sm">
            <a href="{{ route('admin.metas.create') }}" class="btn btn-primary px-3">
                <i class="fas fa-plus-circle mr-1"></i> Asignar Metas
            </a>
            <a href="{{ route('admin.metas.comisiones') }}" class="btn btn-white border px-3">
                <i class="fas fa-cog mr-1"></i> Configuración
            </a>
        </div>
    </div>
@stop

@section('content')
<div class="row mb-4">
    <!-- Filtros Mejorados -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <form action="{{ route('admin.metas.index') }}" method="GET" class="form-inline">
                            <div class="input-group mr-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white border-right-0"><i class="fas fa-calendar-alt text-muted"></i></span>
                                </div>
                                <select name="anio" class="form-control border-left-0 font-weight-bold">
                                    @for($y = date('Y'); $y >= 2024; $y--)
                                        <option value="{{ $y }}" {{ $anio == $y ? 'selected' : '' }}>Año {{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            
                            <div class="input-group mr-3">
                                <select name="mes" class="form-control font-weight-bold">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ $mes == $m ? 'selected' : '' }}>
                                            {{ Str::title(\Carbon\Carbon::create(null, $m)->translatedFormat('F')) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-info px-4 shadow-sm">
                                <i class="fas fa-filter mr-1"></i> Filtrar
                            </button>
                        </form>
                    </div>
                    <div class="col-md-4 text-right">
                        <form action="{{ route('admin.metas.recalcular') }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="anio" value="{{ $anio }}">
                            <input type="hidden" name="mes" value="{{ $mes }}">
                            <button type="submit" class="btn btn-success px-4 shadow-sm">
                                <i class="fas fa-sync-alt mr-1"></i> Sincronizar Rendimiento
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $totalMetas = $metas->count();
    $comisionesProyectadas = $metas->sum(function($m) use ($config) {
        return $m->porcentaje_mora > ($config->umbral_morosidad ?? 20) ? 0 : $m->comision_calculada;
    });
    $promedioCumplimiento = $totalMetas > 0 ? $metas->avg('porcentaje_cumplimiento') : 0;
    $fueraPorMora = $metas->where('porcentaje_mora', '>', $config->umbral_morosidad ?? 20)->count();
@endphp

<!-- KPIs Modernos -->
<div class="row">
    <div class="col-md-3">
        <div class="kpi-card-modern bg-white shadow-sm border-left-primary">
            <div class="kpi-icon-modern text-primary"><i class="fas fa-user-tie"></i></div>
            <div class="kpi-data-modern">
                <span class="kpi-value-modern">{{ $totalMetas }}</span>
                <span class="kpi-label-modern">Asesores con Meta</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card-modern bg-white shadow-sm border-left-success">
            <div class="kpi-icon-modern text-success"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="kpi-data-modern">
                <span class="kpi-value-modern">S/ {{ number_format($comisionesProyectadas, 2) }}</span>
                <span class="kpi-label-modern">Bonos Proyectados</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card-modern bg-white shadow-sm border-left-warning">
            <div class="kpi-icon-modern text-warning"><i class="fas fa-chart-line"></i></div>
            <div class="kpi-data-modern">
                <span class="kpi-value-modern">{{ number_format($promedioCumplimiento, 1) }}%</span>
                <span class="kpi-label-modern">Efectividad General</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card-modern bg-white shadow-sm border-left-danger">
            <div class="kpi-icon-modern text-danger"><i class="fas fa-user-times"></i></div>
            <div class="kpi-data-modern">
                <span class="kpi-value-modern">{{ $fueraPorMora }}</span>
                <span class="kpi-label-modern">Bloqueos por Mora</span>
            </div>
        </div>
    </div>
</div>

<!-- Listado Principal -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="card-title font-weight-bold">
                Seguimiento de Asesores <span class="text-muted font-weight-normal">| {{ \Carbon\Carbon::create($anio, $mes)->translatedFormat('F Y') }}</span>
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="maximize"><i class="fas fa-expand"></i></button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 valign-middle">
                <thead>
                    <tr class="bg-light text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        <th class="pl-4">Asesor / Perfil</th>
                        <th>Nivel Actual</th>
                        <th>Objetivo</th>
                        <th>Realizado</th>
                        <th style="width: 15%">Progreso</th>
                        <th>Mora</th>
                        <th>Bono Est.</th>
                        <th>Estado</th>
                        <th class="pr-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($metas as $meta)
                    <tr>
                        <td class="pl-4 border-top-0">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm mr-3 bg-light rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user text-muted"></i>
                                </div>
                                <div>
                                    <div class="font-weight-bold text-dark">{{ $meta->asesor->persona?->full_name ?? $meta->asesor->name }}</div>
                                    <small class="text-muted">{{ $meta->asesor->codigo }}</small>
                                </div>
                            </div>
                        </td>
                        <td class="border-top-0">
                            <span class="badge badge-pill badge-{{ $meta->nivel_calificacion->color() }} px-3 py-1">
                                <i class="{{ $meta->nivel_calificacion->icono() }} mr-1"></i> {{ $meta->nivel_calificacion->label() }}
                            </span>
                        </td>
                        <td class="border-top-0 font-weight-bold">{{ $meta->cantidad_objetivo }} <small class="text-muted">prs.</small></td>
                        <td class="border-top-0 font-weight-bold text-primary">{{ $meta->cantidad_lograda }}</td>
                        <td class="border-top-0">
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 shadow-xs" style="height: 6px; border-radius: 10px;">
                                    <div class="progress-bar bg-{{ $meta->porcentaje_cumplimiento >= 100 ? 'success' : ($meta->porcentaje_cumplimiento >= 80 ? 'info' : ($meta->porcentaje_cumplimiento >= 50 ? 'warning' : 'danger')) }}" 
                                         role="progressbar" style="width: {{ min($meta->porcentaje_cumplimiento, 100) }}%"></div>
                                </div>
                                <span class="ml-2 font-weight-bold" style="font-size: 0.85rem;">{{ number_format($meta->porcentaje_cumplimiento, 0) }}%</span>
                            </div>
                        </td>
                        <td class="border-top-0">
                            <span class="font-weight-bold {{ $meta->porcentaje_mora > ($config->umbral_morosidad ?? 20) ? 'text-danger' : 'text-success' }}">
                                {{ number_format($meta->porcentaje_mora, 2) }}%
                                @if($meta->porcentaje_mora > ($config->umbral_morosidad ?? 20))
                                    <i class="fas fa-exclamation-triangle ml-1" title="Excede umbral de mora"></i>
                                @endif
                            </span>
                        </td>
                        <td class="border-top-0">
                            @if($meta->porcentaje_mora > ($config->umbral_morosidad ?? 20))
                                <span class="text-muted text-decoration-line-through">S/ 0.00</span>
                                <badge class="badge badge-danger ml-1" title="Morosidad excedida">Penalizado</badge>
                            @else
                                <span class="font-weight-bold text-dark">S/ {{ number_format($meta->comision_calculada, 2) }}</span>
                            @endif
                        </td>
                        <td class="border-top-0">
                            <span class="badge {{ $meta->estado == 'pendiente' ? 'badge-light text-muted' : 'badge-success-light' }} text-uppercase" style="font-size: 0.7rem;">
                                {{ $meta->estado }}
                            </span>
                        </td>
                        <td class="pr-4 border-top-0 text-right">
                            <div class="btn-group btn-group-sm rounded shadow-sm">
                                <a href="{{ route('admin.metas.show', $meta->id) }}" class="btn btn-white border-right" title="Ver Detalle Rendimiento">
                                    <i class="fas fa-chart-pie text-info"></i>
                                </a>
                                <a href="{{ route('admin.metas.edit', $meta->id) }}" class="btn btn-white" title="Editar Meta">
                                    <i class="fas fa-edit text-warning"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 bg-white">
                            <div class="mb-3">
                                <i class="fas fa-folder-open text-light" style="font-size: 4rem;"></i>
                            </div>
                            <h5 class="text-dark font-weight-bold">Sin registros encontrados</h5>
                            <p class="text-muted">No se han asignado metas para el período seleccionado.</p>
                            <a href="{{ route('admin.metas.create') }}" class="btn btn-primary px-4 mt-2 shadow-sm">
                                <i class="fas fa-plus-circle mr-1"></i> Asignar Metas Ahora
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($metas instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="card-footer bg-white border-0 py-3">
        {{ $metas->links() }}
    </div>
    @endif
</div>
@stop

@section('css')
<style>
    .valign-middle td { vertical-align: middle !important; }
    .kpi-card-modern {
        padding: 1.5rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        border-left-width: 5px !important;
    }
    .kpi-icon-modern {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-right: 1.25rem;
    }
    .kpi-data-modern {
        display: flex;
        flex-direction: column;
    }
    .kpi-value-modern {
        font-size: 1.5rem;
        font-weight: 800;
        line-height: 1.2;
        color: #1e293b;
    }
    .kpi-label-modern {
        font-size: 0.8rem;
        color: #64748b;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .border-left-primary { border-left-color: #4e73df !important; }
    .border-left-success { border-left-color: #1cc88a !important; }
    .border-left-warning { border-left-color: #f6c23e !important; }
    .border-left-danger { border-left-color: #e74a3b !important; }
    
    .badge-success-light {
        color: #155724;
        background-color: #d4edda;
    }
    .avatar-sm {
        width: 36px;
        height: 36px;
        font-size: 0.9rem;
    }
    .shadow-xs { box-shadow: inset 0 1px 2px rgba(0,0,0,.075) !important; }
    .btn-white { background-color: #fff; border: 1px solid #e2e8f0; }
    .btn-white:hover { background-color: #f8fafc; }
    .text-decoration-line-through { text-decoration: line-through; }
</style>
@stop
