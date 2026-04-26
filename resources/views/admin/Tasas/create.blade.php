@extends('layouts.admin')
@section('title', 'Crear Tasa')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-plus-circle mr-2"></i>Crear Nueva Tasa</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.tasas.index') }}">Tasas</a></li>
            <li class="breadcrumb-item active">Crear</li>
        </ol>
    </div>
@stop

@section('content')
    <div class="container-fluid pt-2">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Información de la Tasa</h3>
            </div>
            
            <form action="{{ route('admin.tasas.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <!-- Tipo de Tasa -->
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="tipo_tasa" class="font-weight-bold">
                                    <i class="fas fa-tag mr-1 text-gray-600"></i>Tipo de Tasa
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light">
                                            <i class="fas fa-pencil-alt text-blue"></i>
                                        </span>
                                    </div>
                                    <input type="text" class="form-control @error('tipo_tasa') is-invalid @enderror" 
                                           name="tipo_tasa" id="tipo_tasa" placeholder="Ej. Tasa de interés anual" 
                                           value="{{ old('tipo_tasa') }}" required>
                                    @error('tipo_tasa')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">Ingrese un nombre descriptivo para la tasa</small>
                            </div>
                        </div>
                        
                        <!-- Valor -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="valor" class="font-weight-bold">
                                    <i class="fas fa-percentage mr-1 text-gray-600"></i>Valor de la Tasa (%)
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light">%</span>
                                    </div>
                                    <input type="number" step="any" class="form-control @error('valor') is-invalid @enderror" 
                                           name="valor" id="valor" placeholder="Ej. 15.5" 
                                           value="{{ old('valor') }}" required>
                                    @error('valor')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">Ingrese el valor porcentual de la tasa</small>
                            </div>
                        </div>
                        
                        <!-- Estado -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status" class="font-weight-bold">
                                    <i class="fas fa-toggle-on mr-1 text-gray-600"></i>Estado
                                    <span class="text-danger">*</span>
                                </label>
                                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                    <option value="" disabled selected>Seleccione un estado</option>
                                    <option value="1" {{ old('status') == '1' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Defina si la tasa estará disponible para usar</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información adicional -->
                    <div class="alert alert-info mt-3">
                        <div class="d-flex">
                            <div class="mr-3">
                                <i class="fas fa-info-circle fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="alert-heading font-weight-bold mb-1">Información importante</h5>
                                <p class="mb-0">Las tasas creadas podrán ser utilizadas en la configuración de plazos para préstamos.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.tasas.index') }}" class="btn btn-default">
                            <i class="fas fa-times mr-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Guardar Tasa
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    <style>
        .form-group label {
            color: #555;
        }
        .form-text.text-muted {
            font-size: 0.8rem;
        }
        .input-group-text {
            border-color: #ced4da;
        }
        .alert-info {
            background-color: #f8f9ff;
            border-color: #cfd4ff;
            color: #3f51b5;
        }
    </style>
@stop