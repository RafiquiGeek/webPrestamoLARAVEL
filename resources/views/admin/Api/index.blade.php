@extends('layouts.admin')

@section('title', 'Configuración de API')

@section('content_header')
    <div class="container d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-cogs mr-2"></i>Configuración de API</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
            <li class="breadcrumb-item active">API Config</li>
        </ol>
    </div>
@stop

@section('content')
    <div class="container pt-2">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Configuraciones de API</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.api-config.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Nueva Configuración
                    </a>
                    <a href="{{ route('admin.api-config.initialize') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-magic"></i> Inicializar Defecto
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($configs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Clave</th>
                                    <th>Valor</th>
                                    <th>Estado</th>
                                    <th>Descripción</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($configs as $config)
                                    <tr>
                                        <td>{{ $config->name }}</td>
                                        <td><code>{{ $config->key }}</code></td>
                                        <td>
                                            @if(strpos($config->key, 'token') !== false)
                                                <span class="text-muted">••••••••••••••••</span>
                                            @else
                                                <small>{{ Str::limit($config->value, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($config->is_active)
                                                <span class="badge badge-success">Activo</span>
                                            @else
                                                <span class="badge badge-danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>{{ $config->description ?? '-' }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.api-config.edit', $config->id) }}" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.api-config.destroy', $config->id) }}" 
                                                      method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('¿Está seguro de eliminar esta configuración?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-cogs fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay configuraciones de API</h5>
                        <p class="text-muted">Agregue una nueva configuración o inicialice los valores por defecto.</p>
                        <a href="{{ route('admin.api-config.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Agregar Primera Configuración
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sección de prueba de API DNI -->
        <div class="card card-outline card-success shadow-sm mt-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-flask mr-2"></i>Prueba de API DNI en Vivo</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if($configs->where('key', 'dni_api_url')->isEmpty())
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Configuración no encontrada:</strong> Debe configurar primero la URL de la API DNI.
                        <a href="{{ route('admin.api-config.initialize') }}" class="btn btn-sm btn-warning ml-2">
                            <i class="fas fa-magic"></i> Inicializar Ahora
                        </a>
                    </div>
                @else
                    <div class="row">
                        <!-- Formulario de prueba -->
                        <div class="col-md-4">
                            <form id="testDniForm">
                                @csrf
                                <div class="form-group">
                                    <label for="test_dni" class="font-weight-bold">
                                        <i class="fas fa-id-card mr-1"></i>DNI de Prueba
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="test_dni" name="dni" 
                                               placeholder="12345678" maxlength="8" pattern="[0-9]{8}" required>
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-success" id="testDniBtn">
                                                <i class="fas fa-search"></i> Consultar
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Ingrese un DNI válido de 8 dígitos para probar</small>
                                </div>
                            </form>

                            <!-- Botones de prueba rápida -->
                            <div class="mt-3">
                                <h6 class="font-weight-bold">Pruebas Rápidas:</h6>
                                <div class="btn-group-vertical w-100" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm quick-test" data-dni="12345678">
                                        <i class="fas fa-bolt"></i> Test DNI: 12345678
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm quick-test" data-dni="87654321">
                                        <i class="fas fa-bolt"></i> Test DNI: 87654321
                                    </button>
                                    <button type="button" class="btn btn-outline-info btn-sm" id="testRandomDni">
                                        <i class="fas fa-random"></i> DNI Aleatorio
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Área de resultados -->
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="font-weight-bold">
                                    <i class="fas fa-clipboard-list mr-1"></i>Resultado de la Consulta
                                </label>
                                <div id="testResult" class="border rounded p-3" style="min-height: 200px; background-color: #f8f9fa;">
                                    <div class="text-center text-muted">
                                        <i class="fas fa-search fa-3x mb-3"></i>
                                        <p>Los resultados de la consulta aparecerán aquí</p>
                                        <small>Ingrese un DNI y haga clic en "Consultar" para probar la API</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Información de configuración actual -->
                            <div class="mt-3">
                                <h6 class="font-weight-bold">Configuración Actual:</h6>
                                <div class="row">
                                    <div class="col-md-12">
                                        <table class="table table-sm table-bordered">
                                            <tr>
                                                <td><strong>URL:</strong></td>
                                                <td><small><code>{{ $configs->where('key', 'dni_api_url')->first()->value ?? 'No configurado' }}</code></small></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Método:</strong></td>
                                                <td><span class="badge badge-info">{{ $configs->where('key', 'dni_api_method')->first()->value ?? 'GET' }}</span></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Token:</strong></td>
                                                <td><small>{{ $configs->where('key', 'dni_api_token')->first() ? '••••••••••••••••' : 'No configurado' }}</small></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Función para realizar prueba de DNI
            function testDniApi(dni) {
                if (!/^\d{8}$/.test(dni)) {
                    $('#testResult').html(`
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> <strong>DNI inválido:</strong> Debe contener exactamente 8 dígitos numéricos.
                        </div>
                    `);
                    return;
                }
                
                const $btn = $('#testDniBtn');
                const originalText = $btn.html();
                
                $.ajax({
                    url: '{{ route("admin.api-config.test-dni") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        dni: dni
                    },
                    beforeSend: function() {
                        $btn.html('<i class="fas fa-spinner fa-spin"></i> Consultando...').prop('disabled', true);
                        $('#testResult').html(`
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Cargando...</span>
                                </div>
                                <p class="mt-2 mb-0">Consultando DNI: <strong>${dni}</strong></p>
                                <small class="text-muted">Esto puede tomar unos segundos...</small>
                            </div>
                        `);
                    },
                    success: function(response) {
                        if (response.success) {
                            let dataDisplay = '';
                            if (response.data) {
                                if (typeof response.data === 'object') {
                                    // Mostrar datos de forma organizada
                                    dataDisplay = '<div class="row mt-3">';
                                    if (response.data.nombres) {
                                        dataDisplay += `<div class="col-md-4"><strong>Nombres:</strong><br>${response.data.nombres}</div>`;
                                    }
                                    if (response.data.apellido_paterno) {
                                        dataDisplay += `<div class="col-md-4"><strong>Apellido Paterno:</strong><br>${response.data.apellido_paterno}</div>`;
                                    }
                                    if (response.data.apellido_materno) {
                                        dataDisplay += `<div class="col-md-4"><strong>Apellido Materno:</strong><br>${response.data.apellido_materno}</div>`;
                                    }
                                    dataDisplay += '</div>';
                                    
                                    // Mostrar respuesta completa en JSON
                                    dataDisplay += `
                                        <div class="mt-3">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse" data-target="#jsonResponse">
                                                <i class="fas fa-code"></i> Ver respuesta JSON completa
                                            </button>
                                            <div class="collapse mt-2" id="jsonResponse">
                                                <pre class="bg-light p-2 rounded"><code>${JSON.stringify(response.data, null, 2)}</code></pre>
                                            </div>
                                        </div>
                                    `;
                                } else {
                                    dataDisplay = `<pre class="bg-light p-2 rounded">${JSON.stringify(response.data, null, 2)}</pre>`;
                                }
                            }
                            
                            $('#testResult').html(`
                                <div class="alert alert-success">
                                    <h5><i class="fas fa-check-circle"></i> ${response.message}</h5>
                                    <p class="mb-1"><strong>DNI consultado:</strong> ${dni}</p>
                                    ${dataDisplay}
                                </div>
                            `);
                        } else {
                            $('#testResult').html(`
                                <div class="alert alert-danger">
                                    <h5><i class="fas fa-exclamation-triangle"></i> ${response.message}</h5>
                                    <p><strong>DNI consultado:</strong> ${dni}</p>
                                    ${response.error ? `<div class="mt-2"><strong>Detalle del error:</strong><br><pre class="bg-light p-2 rounded mt-1">${response.error}</pre></div>` : ''}
                                </div>
                            `);
                        }
                    },
                    error: function(xhr, status, error) {
                        let errorMsg = 'Error de conexión desconocido';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        } else if (xhr.statusText) {
                            errorMsg = `Error ${xhr.status}: ${xhr.statusText}`;
                        }
                        
                        $('#testResult').html(`
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-times-circle"></i> Error de Conexión</h5>
                                <p><strong>DNI consultado:</strong> ${dni}</p>
                                <p><strong>Error:</strong> ${errorMsg}</p>
                                <small class="text-muted">Verifique que la configuración de API esté correcta y que tenga conexión a internet.</small>
                            </div>
                        `);
                    },
                    complete: function() {
                        $btn.html(originalText).prop('disabled', false);
                    }
                });
            }
            
            // Envío del formulario principal
            $('#testDniForm').on('submit', function(e) {
                e.preventDefault();
                const dni = $('#test_dni').val().trim();
                testDniApi(dni);
            });
            
            // Botones de prueba rápida
            $('.quick-test').on('click', function() {
                const dni = $(this).data('dni');
                $('#test_dni').val(dni);
                testDniApi(dni);
            });
            
            // Generar DNI aleatorio para prueba
            $('#testRandomDni').on('click', function() {
                // Generar DNI aleatorio válido (solo para pruebas)
                const randomDni = Math.floor(10000000 + Math.random() * 90000000).toString();
                $('#test_dni').val(randomDni);
                testDniApi(randomDni);
            });
            
            // Validación en tiempo real del campo DNI
            $('#test_dni').on('input', function() {
                const dni = $(this).val();
                const $input = $(this);
                
                if (dni.length > 0) {
                    if (!/^\d+$/.test(dni)) {
                        $input.addClass('is-invalid').removeClass('is-valid');
                    } else if (dni.length === 8) {
                        $input.addClass('is-valid').removeClass('is-invalid');
                    } else {
                        $input.removeClass('is-valid is-invalid');
                    }
                } else {
                    $input.removeClass('is-valid is-invalid');
                }
            });
            
            // Solo permitir números en el campo DNI
            $('#test_dni').on('keypress', function(e) {
                if (!/[0-9]/.test(String.fromCharCode(e.which))) {
                    e.preventDefault();
                }
            });
        });
    </script>
@stop