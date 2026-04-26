@extends('layouts.admin')

@section('title', 'Asignar Metas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="font-weight-bold text-dark"><i class="fas fa-plus-circle text-primary mr-2"></i> Asignar Metas Mensuales</h1>
            <p class="text-muted mb-0">Define objetivos de préstamos para el equipo de asesores</p>
        </div>
        <a href="{{ route('admin.metas.index') }}" class="btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left"></i> Volver al Panel
        </a>
    </div>
@stop

@section('content')
<div class="row justify-content-center">
    <div class="col-md-12">
        <!-- Filtro de Período -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form action="{{ route('admin.metas.create') }}" method="GET" class="form-inline">
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
                        <i class="fas fa-calendar-check mr-1"></i> Cambiar Período
                    </button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 border-top-primary">
            <div class="card-header bg-white py-3">
                <h3 class="card-title font-weight-bold text-primary">Asignación de Metas - {{ Str::title(\Carbon\Carbon::create($anio, $mes)->translatedFormat('F Y')) }}</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.metas.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="anio" value="{{ $anio }}">
                    <input type="hidden" name="mes" value="{{ $mes }}">

                    <div class="table-responsive rounded shadow-xs">
                        <table class="table table-hover table-striped mb-0 table-valign-middle border">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th class="pl-4 py-3" style="width: 25%;">Asesor</th>
                                    <th class="py-3 text-center" style="width: 15%;">Rend. Mes Anterior</th>
                                    <th class="py-3" style="width: 380px;">Nueva Meta (Ctd. Préstamos)</th>
                                    <th class="pr-4 py-3">Comentarios Adicionales</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($asesores as $asesor)
                                    @php
                                        $metaExistente = $metasExistentes->get($asesor->id);
                                        $previo = $metasPrevio->get($asesor->id);
                                    @endphp
                                    <tr class="{{ $metaExistente ? 'table-info-light' : '' }}">
                                        <td class="pl-4">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-secondary-light rounded-circle h-10 w-10 d-flex align-items-center justify-content-center mr-3" style="width: 35px; height: 35px;">
                                                    <i class="fas fa-user text-secondary"></i>
                                                </div>
                                                <div>
                                                    <div class="font-weight-bold text-dark">{{ $asesor->codigo ?? $asesor->name }}</div>
                                                    <small class="text-muted d-block">{{ $asesor->name }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($previo)
                                                <div class="font-weight-bold text-dark h5 mb-0">{{ $previo->prestamos_originados }}</div>
                                                <small class="text-muted font-weight-bold">{{ number_format($previo->porcentaje_cumplimiento, 0) }}% cumpl.</small>
                                            @else
                                                <span class="text-muted italic small">Sin historial</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <button type="button" class="btn btn-outline-secondary btn-decrement" onclick="decrementValue('input-{{ $asesor->id }}')"><i class="fas fa-minus"></i></button>
                                                </div>
                                                <input type="number" id="input-{{ $asesor->id }}" 
                                                       name="metas[{{ $asesor->id }}][cantidad]" 
                                                       value="{{ $metaExistente?->cantidad_objetivo ?? 0 }}"
                                                       class="form-control text-center font-weight-bold" 
                                                       min="0" step="1">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-secondary btn-increment" onclick="incrementValue('input-{{ $asesor->id }}')"><i class="fas fa-plus"></i></button>
                                                    <span class="input-group-text bg-white font-weight-bold small">PRÉSTAMOS</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="pr-4">
                                            <input type="text" name="metas[{{ $asesor->id }}][observaciones]"
                                                   value="{{ $metaExistente?->observaciones ?? '' }}"
                                                   class="form-control" placeholder="Observaciones...">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="text-muted"><i class="fas fa-users-slash fa-3x mb-3 opacity-25"></i></div>
                                            <h5>No se encontraron asesores</h5>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="text-right mt-5 p-4 border-top bg-light rounded-bottom">
                        <div class="d-inline-block text-left mr-5 align-middle">
                            <label class="small text-muted mb-0 font-weight-bold">RESUMEN DE CARGA</label>
                            <div class="h5 mb-0 font-weight-bold text-dark">{{ $asesores->count() }} Asesores Listados</div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg px-5 shadow-lg border-0" style="border-radius: 50px;">
                            <i class="fas fa-save mr-2"></i> Guardar y Confirmar Metas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .border-top-primary { border-top: 4px solid #007bff !important; }
    .bg-light-warning { background-color: #fff9e6; }
    .bg-secondary-light { background-color: #f1f5f9; }
    .table-info-light { background-color: #f0f7ff !important; }
    .table-valign-middle td { vertical-align: middle !important; }
    .input-group .btn { border-color: #d1d5db; }
    .shadow-xs { box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
</style>
@stop

@section('js')
<script>
    function incrementValue(id) {
        var input = document.getElementById(id);
        input.value = parseInt(input.value) + 1;
    }
    
    function decrementValue(id) {
        var input = document.getElementById(id);
        if (parseInt(input.value) > 0) {
            input.value = parseInt(input.value) - 1;
        }
    }
</script>

<style>
    .input-group input[type="number"] {
        height: 45px;
        font-size: 1.2rem;
    }
    .btn-increment, .btn-decrement {
        width: 45px;
    }
    .table-valign-middle td {
        vertical-align: middle !important;
    }
</style>
@stop
