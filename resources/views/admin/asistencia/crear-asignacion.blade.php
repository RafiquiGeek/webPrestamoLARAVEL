@extends('layouts.admin')

@section('title', 'Crear Asignación')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-plus mr-1"></i>
                        Crear Nueva Asignación Empleado-Área
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.asistencia.asignaciones') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.asistencia.asignaciones.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="user_id">Empleado <span class="text-danger">*</span></label>
                                    <select class="form-control @error('user_id') is-invalid @enderror" 
                                            id="user_id" name="user_id" required>
                                        <option value="">Seleccionar empleado...</option>
                                        @foreach($usuarios as $usuario)
                                            <option value="{{ $usuario->id }}" {{ old('user_id') == $usuario->id ? 'selected' : '' }}>
                                                {{ $usuario->codigo ? '[' . $usuario->codigo . '] ' : '' }}{{ $usuario->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="area_laboral_id">Área Laboral <span class="text-danger">*</span></label>
                                    <select class="form-control @error('area_laboral_id') is-invalid @enderror" 
                                            id="area_laboral_id" name="area_laboral_id" required>
                                        <option value="">Seleccionar área...</option>
                                        @foreach($areas as $area)
                                            <option value="{{ $area->id }}" {{ old('area_laboral_id') == $area->id ? 'selected' : '' }}>
                                                {{ $area->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('area_laboral_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="horario_trabajo_id">Horario de Trabajo <span class="text-danger">*</span></label>
                                    <select class="form-control @error('horario_trabajo_id') is-invalid @enderror" 
                                            id="horario_trabajo_id" name="horario_trabajo_id" required>
                                        <option value="">Seleccionar horario...</option>
                                        @foreach($horarios as $horario)
                                            <option value="{{ $horario->id }}" {{ old('horario_trabajo_id') == $horario->id ? 'selected' : '' }}>
                                                {{ $horario->nombre }} ({{ $horario->hora_entrada }} - {{ $horario->hora_salida }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('horario_trabajo_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_inicio">Fecha de Inicio <span class="text-danger">*</span></label>
                                    <input type="date" 
                                           class="form-control @error('fecha_inicio') is-invalid @enderror" 
                                           id="fecha_inicio" 
                                           name="fecha_inicio" 
                                           value="{{ old('fecha_inicio', date('Y-m-d')) }}" 
                                           required>
                                    @error('fecha_inicio')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_fin">Fecha de Fin</label>
                                    <input type="date" 
                                           class="form-control @error('fecha_fin') is-invalid @enderror" 
                                           id="fecha_fin" 
                                           name="fecha_fin" 
                                           value="{{ old('fecha_fin') }}">
                                    <small class="form-text text-muted">Dejar vacío para asignación indefinida</small>
                                    @error('fecha_fin')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="activo" name="activo" value="1" {{ old('activo', true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="activo">Asignación activa</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Asignación
                            </button>
                            <a href="{{ route('admin.asistencia.asignaciones') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    .form-group label .text-danger {
        font-size: 0.9em;
    }
</style>
@endsection