@extends('layouts.admin')

@section('title', 'Editar Meta')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="font-weight-bold text-dark"><i class="fas fa-edit text-warning mr-2"></i> Editar Meta Individual</h1>
            <p class="text-muted mb-0">Modifica los parámetros específicos de la meta del asesor</p>
        </div>
        <a href="{{ route('admin.metas.index', ['anio' => $meta->anio, 'mes' => $meta->mes]) }}" class="btn btn-white border shadow-sm">
            <i class="fas fa-arrow-left mr-1"></i> Cancelar y Volver
        </a>
    </div>
@stop

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-lg border-0" style="border-radius: 15px;">
            <div class="card-body p-5">
                <!-- Encabezado del Perfil -->
                <div class="d-flex align-items-center mb-5 pb-4 border-bottom">
                    <div class="avatar-lg bg-warning-light rounded-circle shadow-sm d-flex align-items-center justify-content-center mr-4" style="width: 80px; height: 80px;">
                        <i class="fas fa-user-tie fa-2x text-warning"></i>
                    </div>
                    <div>
                        <h3 class="font-weight-bold text-dark mb-1">{{ $meta->asesor->name }}</h3>
                        <div class="badge badge-pill badge-light px-3 py-1 border text-muted">
                            <i class="fas fa-calendar-alt mr-1"></i> Período: 
                            <span class="text-dark font-weight-bold">{{ Str::title(\Carbon\Carbon::create($meta->anio, $meta->mes)->translatedFormat('F Y')) }}</span>
                        </div>
                    </div>
                </div>

                <form action="{{ route('admin.metas.update', $meta->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <div class="form-group">
                                <label class="font-weight-bold mb-2 text-dark">Meta Objetivo de Préstamos</label>
                                <div class="input-group input-group-lg shadow-xs">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white border-right-0"><i class="fas fa-bullseye text-primary"></i></span>
                                    </div>
                                    <input type="number" name="cantidad_objetivo" value="{{ $meta->cantidad_objetivo }}" class="form-control border-left-0 font-weight-bold text-primary" required min="0" step="1">
                                    <div class="input-group-append">
                                        <span class="input-group-text bg-light font-weight-bold" style="font-size: 0.8rem;">PRÉSTAMOS</span>
                                    </div>
                                </div>
                                <small class="text-muted mt-2 d-block">Establece la cantidad mínima de préstamos que el asesor debe liquidar en el mes.</small>
                            </div>
                        </div>

                        <div class="col-md-12 mb-4">
                            <div class="form-group">
                                <label class="font-weight-bold mb-2 text-dark text-muted small uppercase">Control de Estado</label>
                                <div class="p-3 border rounded bg-light-gray d-flex align-items-center justify-content-between">
                                    <div>
                                        <strong class="text-dark d-block">Estado del Registro</strong>
                                        <small class="text-muted">Determina si la meta se puede seguir editando</small>
                                    </div>
                                    <div class="btn-group btn-group-toggle shadow-xs" data-toggle="buttons">
                                        <label class="btn btn-white px-4 border shadow-none {{ $meta->estado == 'pendiente' ? 'active btn-success-fixed' : '' }}">
                                            <input type="radio" name="estado" value="pendiente" {{ $meta->estado == 'pendiente' ? 'checked' : '' }}> Abierta
                                        </label>
                                        <label class="btn btn-white px-4 border shadow-none {{ $meta->estado == 'cerrado' ? 'active btn-danger-fixed' : '' }}">
                                            <input type="radio" name="estado" value="cerrado" {{ $meta->estado == 'cerrado' ? 'checked' : '' }}> Cerrada
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mb-5">
                            <div class="form-group">
                                <label class="font-weight-bold mb-2 text-dark text-muted small uppercase">Observaciones y Notas</label>
                                <textarea name="observaciones" class="form-control shadow-xs" rows="4" placeholder="Alguna nota interna sobre el motivo de esta meta o incentivos especiales...">{{ $meta->observaciones }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row pt-4 border-top">
                        <div class="col-12 text-center text-md-right">
                            <button type="submit" class="btn btn-warning btn-lg px-5 shadow font-weight-bold py-3" style="border-radius: 50px;">
                                <i class="fas fa-sync-alt mr-2"></i> Guardar y Actualizar Meta
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .bg-warning-light { background-color: #fffbeb; }
    .bg-light-gray { background-color: #f9fafb; }
    .btn-white { background-color: #fff; color: #4b5563; border-color: #d1d5db; }
    .btn-white:hover { background-color: #f3f4f6; }
    .btn-success-fixed.active { background-color: #10b981 !important; color: #fff !important; border-color: #10b981 !important; }
    .btn-danger-fixed.active { background-color: #ef4444 !important; color: #fff !important; border-color: #ef4444 !important; }
    .shadow-xs { box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); }
    .uppercase { text-transform: uppercase; letter-spacing: 0.5px; }
    .avatar-lg { border: 4px solid #fff; }
</style>
@stop
