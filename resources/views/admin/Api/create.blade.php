@extends('layouts.admin')

@section('title', 'Nueva Configuración API')

@section('content_header')
    <div class="container d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-plus-circle mr-2"></i>Nueva Configuración de API</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.api-config.index') }}">API Config</a></li>
            <li class="breadcrumb-item active">Crear</li>
        </ol>
    </div>
@stop

@section('content')
    <div class="container pt-2">
        <form action="{{ route('admin.api-config.store') }}" method="post" class="needs-validation" novalidate>
            @csrf
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">Información de la Configuración</h3>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <!-- Selector de tipo de configuración -->
                        <div class="col-12 mb-3">
                            <label class="font-weight-bold">Seleccione el tipo de configuración:</label>
                            <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                <label class="btn btn-outline-primary config-type-btn" data-type="url">
                                    <input type="radio" name="config_type" id="config_url" value="url">
                                    <i class="fas fa-link mr-1"></i> URL API DNI
                                </label>
                                <label class="btn btn-outline-success config-type-btn" data-type="token">
                                    <input type="radio" name="config_type" id="config_token" value="token">
                                    <i class="fas fa-key mr-1"></i> Token API DNI
                                </label>
                                <label class="btn btn-outline-info config-type-btn" data-type="method">
                                    <input type="radio" name="config_type" id="config_method" value="method">
                                    <i class="fas fa-cog mr-1"></i> Método HTTP
                                </label>
                            </div>
                        </div>

                        <!-- Clave -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="key" class="font-weight-bold">
                                    Clave <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('key') is-invalid @enderror" 
                                       name="key" id="key" required value="{{ old('key') }}" readonly>
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
                                       name="name" id="name" required value="{{ old('name') }}" readonly>
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
                                          name="value" id="value" required rows="3">{{ old('value') }}</textarea>
                                @error('value')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted" id="value-help">Valor de la configuración</small>
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
                                          placeholder="Descripción opcional de la configuración">{{ old('description') }}</textarea>
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
                                           name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold" for="is_active">
                                        Configuración Activa
                                    </label>
                                </div>
                                <small class="form-text text-muted">Solo las configuraciones activas serán utilizadas por el sistema</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configuraciones requeridas para API DNI -->
                    <div class="mt-3">
                        <div class="d-flex">
                            <div class="mr-3">
                                <i class="fas fa-exclamation-triangle fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="alert-heading font-weight-bold mb-2">Configuraciones requeridas para API DNI</h5>
                                <p class="mb-2">Para que el sistema funcione correctamente, debe crear estas 3 configuraciones:</p>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>1. URL de la API:</strong><br>
                                        <small><strong>Clave:</strong> dni_api_url</small><br>
                                        <small><strong>Nombre:</strong> URL API DNI</small><br>
                                        <small><strong>Valor:</strong> https://api.factiliza.com/v1/dni/info/{dni}</small>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>2. Token de autorización:</strong><br>
                                        <small><strong>Clave:</strong> dni_api_token</small><br>
                                        <small><strong>Nombre:</strong> Token API DNI</small><br>
                                        <small><strong>Valor:</strong> Su token de Factiliza</small>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>3. Método HTTP:</strong><br>
                                        <small><strong>Clave:</strong> dni_api_method</small><br>
                                        <small><strong>Nombre:</strong> Método HTTP API DNI</small><br>
                                        <small><strong>Valor:</strong> GET</small>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <strong>Nota:</strong> Use el botón "Inicializar Defecto" en la página principal para crear automáticamente estas configuraciones.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.api-config.index') }}" class="btn btn-default">
                            <i class="fas fa-times mr-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Guardar Configuración
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Configuraciones predefinidas para API Factiliza
            const apiConfigs = {
                url: {
                    key: 'dni_api_url',
                    name: 'URL API DNI',
                    value: 'https://api.factiliza.com/v1/dni/info/{dni}',
                    description: 'URL base para consultas de DNI en Factiliza. Use {dni} como placeholder para el número de DNI.',
                    help: 'URL que se usará para consultar los datos del DNI. El {dni} será reemplazado automáticamente por el número consultado.'
                },
                token: {
                    key: 'dni_api_token',
                    name: 'Token API DNI',
                    value: 'ece92d9d2a2bb54717373740c3180e722cf7a94b5501ed01a263e21e64a4b6a7',
                    description: 'Token de autorización para la API de DNI de Factiliza',
                    help: 'Token proporcionado por Factiliza para autenticar las consultas a su API.'
                },
                method: {
                    key: 'dni_api_method',
                    name: 'Método HTTP API DNI',
                    value: 'GET',
                    description: 'Método HTTP para las consultas de DNI (GET según documentación de Factiliza)',
                    help: 'Método HTTP que se usará para hacer las consultas. Factiliza requiere GET.'
                }
            };

            // Manejar selección de tipo de configuración
            $('.config-type-btn').on('click', function() {
                const type = $(this).data('type');
                const config = apiConfigs[type];
                
                if (config) {
                    // Llenar campos automáticamente
                    $('#key').val(config.key);
                    $('#name').val(config.name);
                    $('#value').val(config.value);
                    $('#description').val(config.description);
                    $('#value-help').text(config.help);
                    
                    // Marcar como activo
                    $('#is_active').prop('checked', true);
                    
                    // Remover readonly del valor para permitir edición
                    $('#value').removeAttr('readonly');
                    $('#description').removeAttr('readonly');
                }
            });

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
        });
    </script>
@stop