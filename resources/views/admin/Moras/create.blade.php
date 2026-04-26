@extends('layouts.admin')

@section('title', 'Crear Nueva Mora')

@section('content_header')
    <h1><i class="fas fa-plus-circle mr-2"></i>Crear Nueva Mora</h1>
@stop

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-body">
            <form action="{{ route('admin.moras.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="monto">Monto de Mora (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="monto" name="monto" 
                                       min="0" max="100" step="0.01" value="{{ old('monto') }}" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            @error('monto')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">Estado</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="1" selected>Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <a href="{{ route('admin.moras.index') }}" class="btn btn-secondary mr-2">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Guardar Mora
                    </button>
                </div>
            </form>
        </div>
    </div>
@stop