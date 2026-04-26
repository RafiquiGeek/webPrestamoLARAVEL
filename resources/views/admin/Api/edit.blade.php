@extends('layouts.admin')

@section('title', 'Editar Configuración API')

@section('content_header')
    <div class="container d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-edit mr-2"></i>Editar Configuración de API</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.api-config.index') }}">API Config</a></li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </div>
@stop

@section('content')
    <div class="container pt-2">
        <form action="{{ route('admin.api-config.update', $config->id) }}" method="post" class="needs-validation" novalidate>
            @csrf
            @method('PUT')
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">Información de la Configuración</h3>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <!-- Clave -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="key" class="font-weight-bold">
                                    Clave <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('key') is-invalid @enderror" 
                                       name="key" id="key" required value="{{ old('key', $config->key) }}"
                                       placeholder="ej: dni_api_url">
                                @error('key')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Identificador único de la configuración</small>
                            </div>
                        </div>
                        
                        <!-- Nombre -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name" class="font-weight-bold">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       name="name" id="name" required value="{{ old('name', $config->name) }}"
                                       placeholder="ej: URL API DNI">
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Nombre descriptivo para la configuración</small>
                            </div>
                        </div>
                        
                        <!-- Valor -->
                        <div class="col-12">
                            <div class="form-group">
                                <label for="value" class="font-weight-bold">
                                    Valor <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control @error('value') is-invalid @enderror" 
                                          name="value" id="value" required rows="3" 
                                          placeholder="Valor de la configuración">{{ old('value', $config->value) }}</textarea>
                                @error('value')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Valor de la configuración</small>
                            </div>
                        </div>
                        
                        <!-- Descripción -->
                        <div class="col-12">
                            <div class="form-group">
                                <label for="description" class="font-weight-bold">
                                    Descripción
                                </label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          name="description" id="description" rows="2" 
                                          placeholder="Descripción opcional de la configuración">{{ old('description', $config->description) }}</textarea>
                                @error('description')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Descripción opcional para documentar el propósito</small>
                            </div>
                        </div>
                        
                        <!-- Estado -->
                        <div class="col-12">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="is_active" 
                                           name="is_active" {{ old('is_active', $config->is_active) ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold" for="is_active">
                                        Configuración Activa
                                    </label>
                                </div>
                                <small class="form-text text-muted">Solo las configuraciones activas serán utilizadas por el sistema</small>
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
                                <h5 class="alert-heading font-weight-bold mb-1">Información de la configuración</h5>
                                <ul class="mb-1">
                                    <li><strong>Creado:</strong> {{ $config->created_at->format('d/m/Y H:i') }}</li>
                                    <li><strong>Última actualización:</strong> {{ $config->updated_at->format('d/m/Y H:i') }}</li>
                                </ul>
                                <p class="mb-0"><strong>Estado actual:</strong> 
                                    @if($config->is_active)
                                        <span class="badge badge-success">Activo</span>
                                    @else
                                        <span class="badge badge-danger">Inactivo</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Configuraciones requeridas para API DNI -->
                    @if(in_array($config->key, ['dni_api_url', 'dni_api_token', 'dni_api_method']))
                    <div class="alert alert-warning mt-3">
                        <div class="d-flex">
                            <div class="mr-3">
                                <i class="fas fa-exclamation-triangle fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="alert-heading font-weight-bold mb-2">Valores esperados para API DNI</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>dni_api_url:</strong><br>
                                        <small>https://api.factiliza.com/v1/dni/info/{dni}</small>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>dni_api_token:</strong><br>
                                        <small>Su token de autorización de Factiliza</small>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>dni_api_method:</strong><br>
                                        <small>GET</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                
                <!-- Botones de acción -->
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.api-config.index') }}" class="btn btn-default">
                            <i class="fas fa-times mr-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Actualizar Configuración
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@stop

@section('js')
    <script>
        // Validación del formulario
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
@stop