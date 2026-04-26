@extends('layouts.admin')

@section('title', 'Editar Cliente')

@section('content_header')
    <div class="card-header" style="background: linear-gradient(135deg, #1A3C6D, #2E5A9A); color: #fff; padding: 1.5rem 2rem; position: relative;">
        <h1 class="mb-0" style="font-size: 1.5rem; font-weight: 500;">Editar Cliente: {{ $cliente->persona->nombres }} {{ $cliente->persona->ape_pat }}</h1>
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert {{ session('error_message') ? 'alert-danger' : 'alert-success' }}" role="alert">
            {{ session('status') }}{{ session('error_message') ? '. ' . session('error_message') . '.' : '.' }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <h6><i class="fas fa-exclamation-triangle"></i> Se encontraron errores de validación:</h6>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="container-fluid p-0">
        <div class="cliente-form">
            <div class="form-header">
                <h5>Editar Datos del Cliente</h5>
                <div id="step-progress"></div>
            </div>
            
            <div class="form-body">
                <form id="multi-step-form" action="{{ route('admin.clientes.update', $cliente) }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    @method('PUT')

                    <!-- Paso 1: Datos del Cliente -->
                    <div id="step-1" class="form-step" data-step-name="Cliente">
                        <div class="row justify-content-center mb-4">
                            <div class="col-12 text-center">
                                @php
                                    $imagenUrl = $cliente->persona->imagen 
                                        ? asset('img/clientes_img/' . $cliente->persona->imagen)
                                        : asset('storage/img/clientes_img/userDefaultPhoto.png');
                                @endphp
                                <img src="{{ $imagenUrl }}"
                                    class="rounded-circle mx-auto d-block shadow-sm" id="img" alt="userPhoto" 
                                    style="height: 130px; width: 130px; object-fit: cover; border: 4px solid #2E5A9A;">
                                <div class="form-group mt-3">
                                    <label for="file" class="btn btn-outline-primary btn-sm shadow-sm">
                                        <i class="fa fa-upload mr-1"></i> Cambiar Foto
                                    </label>
                                    <input id="file" type="file" name="file" style="display: none;" accept="image/*">
                                </div>
                            </div>
                        </div>

                        <h6 class="font-weight-bold text-muted mb-3">Datos Personales</h6>
                        <hr style="border-color: #2E5A9A; margin-bottom: 2rem;">
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nDocumento">Número de DNI <span class="text-danger">*</span></label>
                                    <div class="input-group" style="border-radius: 7px 0px 0px 7px!important;">
                                        <input class="form-control shadow-sm" name="nDocumento" id="nDocumento" 
                                               value="{{ old('nDocumento', $cliente->persona->documento) }}" required maxlength="8" pattern="[0-9]{8}">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-primary" type="button" id="buscar-dni">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nombres">Nombres <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control shadow-sm" name="nombres" id="nombres" 
                                           value="{{ old('nombres', $cliente->persona->nombres) }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="aPaterno">Apellido Paterno <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control shadow-sm" name="aPaterno" id="aPaterno" 
                                           value="{{ old('aPaterno', $cliente->persona->ape_pat) }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="aMaterno">Apellido Materno <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control shadow-sm" name="aMaterno" id="aMaterno" 
                                           value="{{ old('aMaterno', $cliente->persona->ape_mat) }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_nacimiento">Fecha de Nacimiento <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control shadow-sm" name="fecha_nacimiento" id="fecha_nacimiento" 
                                           value="{{ old('fecha_nacimiento', $cliente->persona->fecha_nacimiento) }}" required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="edad">Edad</label>
                                    <input type="number" class="form-control shadow-sm" name="edad" id="edad" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estado_civil">Estado Civil <span class="text-danger">*</span></label>
                                    @php
                                        // Determinar valor seleccionado: priorizar old() (post-redirect), luego valor en DB
                                        $selectedEstadoRaw = old('estado_civil') !== null ? old('estado_civil') : ($cliente->persona->estado_civil ?? '');
                                        $selectedEstadoNorm = trim(strtolower($selectedEstadoRaw));
                                    @endphp
                                    <select class="form-control shadow-sm" id="estado_civil" name="estado_civil" required>
                                        <option value="" {{ $selectedEstadoNorm === '' ? 'selected' : '' }}>SELECCIONA</option>
                                        <option value="Soltero" {{ $selectedEstadoNorm === 'soltero' ? 'selected' : '' }}>Soltero</option>
                                        <option value="Casado" {{ $selectedEstadoNorm === 'casado' ? 'selected' : '' }}>Casado</option>
                                        <option value="Conviviente" {{ $selectedEstadoNorm === 'conviviente' ? 'selected' : '' }}>Conviviente</option>
                                        <option value="Divorciado" {{ $selectedEstadoNorm === 'divorciado' ? 'selected' : '' }}>Divorciado</option>
                                        <option value="Viudo" {{ $selectedEstadoNorm === 'viudo' ? 'selected' : '' }}>Viudo</option>
                                    </select>
                                    {{-- placeholder for estado civil validation / info --}}
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control shadow-sm" name="email" id="email" 
                                           value="{{ old('email', $cliente->persona->email) }}">
                                </div>
                            </div>
                        </div>

                        <h6 class="font-weight-bold text-muted mt-4 mb-3">Teléfonos</h6>
                        <hr style="border-color: #2E5A9A; margin-bottom: 2rem;">
                        
                        <button type="button" class="btn btn-sm btn-primary shadow-sm mb-3" id="addPhoneButton">
                            <i class="fas fa-plus mr-1"></i> Agregar Teléfono
                        </button>
                        
                        <div id="phoneContainer" class="mb-2">
                            @foreach($cliente->persona->telefonos as $index => $telefono)
                                <div class="row d-flex align-items-center mb-3" data-phone-index="{{ $index }}">
                                    <input type="hidden" name="telefono_id[{{ $index }}]" value="{{ $telefono->id }}">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="telefono{{ $index }}">Teléfono {{ $index + 1 }}</label>
                                            <input type="tel" class="form-control shadow-sm" name="telefono[{{ $index }}]" 
                                                id="telefono{{ $index }}" value="{{ $telefono->numero }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Tipo</label>
                                            <div class="btn-group-toggle d-flex" data-toggle="buttons" style="margin-bottom: -10px !important;">
                                                <label class="btn btn-outline-primary {{ $telefono->tipo_telefono == 'celular' ? 'active' : '' }}">
                                                    <input type="radio" name="tipo[{{ $index }}]" value="celular" {{ $telefono->tipo_telefono == 'celular' ? 'checked' : '' }}> Celular
                                                </label>
                                                <label class="btn btn-outline-primary {{ $telefono->tipo_telefono == 'otro' ? 'active' : '' }}">
                                                    <input type="radio" name="tipo[{{ $index }}]" value="otro" {{ $telefono->tipo_telefono == 'otro' ? 'checked' : '' }}> Otro
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5" style="{{ $telefono->tipo_telefono == 'otro' ? '' : 'display: none;' }}">
                                        <div class="form-group">
                                            <label for="comentario[{{ $index }}]">Comentario</label>
                                            <input class="form-control shadow-sm" name="comentario[{{ $index }}]" 
                                                id="comentario{{ $index }}" value="{{ $telefono->comentario }}">
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger btn-sm mt-4 shadow-sm delete-phone-btn" style="margin-top: 20px !important;">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @php
                            $conyuge = $cliente->conyuge;
                            // Mostrar la sección de cónyuge si existe relación en DB o si el estado civil seleccionado indica pareja
                            $mostrarConyuge = (!empty($conyuge)) || in_array($selectedEstadoNorm, ['casado', 'conviviente']);
                        @endphp

                        <div id="seccion-conyuge" data-has-conyuge="{{ !empty($conyuge) ? '1' : '0' }}" style="{{ $mostrarConyuge ? '' : 'display: none;' }}">
                            <h6 class="font-weight-bold text-muted mt-4 mb-3">Datos Familiares / Cónyuge</h6>
                            <hr style="border-color: #2E5A9A; margin-bottom: 2rem;">
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="conyuge_dni">DNI</label>
                                    <div class="input-group">
                                        <input type="tel" class="form-control shadow-sm" name="conyuge_dni" id="conyuge_dni"
                                            value="{{ old('conyuge_dni', $conyuge ? $conyuge->persona->documento : '') }}"
                                            pattern="[0-9]{8}" maxlength="8">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary shadow-sm" id="consultar-dni-btn" onclick="consultarDNI()">
                                                <i class="fas fa-search"></i>
                                                <span id="dni-loading" class="spinner-border spinner-border-sm ml-2" style="display: none;"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="conyuge_nombre">Nombres</label>
                                        <input type="text" class="form-control shadow-sm" name="conyuge_nombre" id="conyuge_nombre" 
                                            value="{{ old('conyuge_nombre', $conyuge ? $conyuge->persona->nombres : '') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="conyuge_apellido_pat">Apellido Paterno</label>
                                        <input type="text" class="form-control shadow-sm" name="conyuge_apellido_pat" id="conyuge_apellido_pat" 
                                            value="{{ old('conyuge_apellido_pat', $conyuge ? $conyuge->persona->ape_pat : '') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="conyuge_apellido_mat">Apellido Materno</label>
                                        <input type="text" class="form-control shadow-sm" name="conyuge_apellido_mat" id="conyuge_apellido_mat" 
                                            value="{{ old('conyuge_apellido_mat', $conyuge ? $conyuge->persona->ape_mat : '') }}">
                                    </div>
                                </div>
                            
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="carga_familiar">Carga Familiar</label>
                                        <input type="number" class="form-control shadow-sm" name="carga_familiar" id="carga_familiar" 
                                            value="{{ old('carga_familiar', $cliente->carga_familiar ?? 0) }}" min="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="conyuge_actividad">Oficio / Profesión</label>
                                        <input type="text" class="form-control shadow-sm" name="conyuge_actividad" id="conyuge_actividad" 
                                            value="{{ old('conyuge_actividad', $conyuge ? $conyuge->oficio : '') }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="conyuge_telefono">Teléfono</label>
                                        <input type="tel" class="form-control shadow-sm" name="conyuge_telefono" id="conyuge_telefono" 
                                            value="{{ old('conyuge_telefono', $conyuge && $conyuge->persona->telefonos->first() ? $conyuge->persona->telefonos->first()->numero : '') }}">
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="conyuge_direccion_trabajo">Dirección de Trabajo</label>
                                        <input type="text" class="form-control shadow-sm" name="conyuge_direccion_trabajo" id="conyuge_direccion_trabajo" 
                                            value="{{ old('conyuge_direccion_trabajo', $conyuge ? $conyuge->direccion_trabajo : '') }}">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="ref_conyuge_direccion_trabajo">Referencia Dirección de Trabajo</label>
                                        <input type="text" class="form-control shadow-sm" name="ref_conyuge_direccion_trabajo" id="ref_conyuge_direccion_trabajo" 
                                            value="{{ old('ref_conyuge_direccion_trabajo', $conyuge ? $conyuge->referencia_direccion : '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row justify-content-end mt-4">
                            <button type="button" class="btn btn-lg btn-primary next-step shadow-sm">
                                Guardar y Continuar <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Paso 2: Datos de Residencia -->
                    <div id="step-2" class="form-step d-none" data-step-name="Residencia">
                        <!-- Direcciones de residencia -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="font-weight-bold text-muted mb-0">Direcciones de Residencia</h6>
                            <button id="btn-agregar-direccion" class="btn btn-sm btn-primary shadow-sm" type="button">
                                <i class="fas fa-plus mr-1"></i> Agregar Dirección
                            </button>
                        </div>
                        <hr style="border-color: #2E5A9A; margin-bottom: 2rem;">
                        
                        <div id="direccionesContainer">
                            @foreach($cliente->persona->direcciones as $index => $direccion)
                                <div class="card mb-3 direccion-row">
                                    <div class="card-header d-flex justify-content-between align-items-center bg-light">
                                        <div class="d-flex align-items-center">
                                            <h6 class="mb-0 mr-3">Dirección {{ $index + 1 }}</h6>
                                            <div class="form-group mb-0 mr-2" style="min-width: 150px;">
                                                <select class="form-control form-control-sm shadow-sm" name="tipo_direccion[{{ $index }}]" id="tipo_direccion{{ $index }}">
                                                    <option value="principal" {{ ($direccion->tipo_direccion ?? '') == 'principal' ? 'selected' : '' }}>Principal</option>
                                                    <option value="secundario" {{ ($direccion->tipo_direccion ?? '') == 'secundario' || !$direccion->tipo_direccion ? 'selected' : '' }}>Secundario</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-danger shadow-sm delete-direccion-btn" type="button">
                                            <i class="fa fa-trash"></i> Eliminar
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <input type="hidden" name="id_direccion[{{ $index }}]" value="{{ $direccion->id }}">
                                        <!-- Zona y Sucursal para esta dirección -->
                                        <div class="row mb-3" style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin: 0 0 15px 0;">
                                            <div class="col-md-6">
                                                <div class="form-group mb-0">
                                                    <label for="zona_direccion{{ $index }}"><i class="fas fa-map-marker-alt text-primary mr-1"></i>Zona <span class="text-danger">*</span></label>
                                                    <select class="form-control shadow-sm select-zona-direccion" id="zona_direccion{{ $index }}"
                                                            name="zona_direccion[{{ $index }}]" required>
                                                        <option value="">Selecciona una zona</option>
                                                        @foreach ($zonas as $z)
                                                            <option value="{{ $z->id }}" {{ $direccion->zona_id == $z->id ? 'selected' : '' }}>{{ $z->nombre }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-0">
                                                    <label for="sucursal_direccion{{ $index }}"><i class="fas fa-building text-primary mr-1"></i>Sucursal <span class="text-danger">*</span></label>
                                                    <select class="form-control shadow-sm select-sucursal-direccion" id="sucursal_direccion{{ $index }}" 
                                                            name="sucursal_direccion[{{ $index }}]" required data-initial="{{ $direccion->sucursal_id }}">
                                                        <option value="">Cargando sucursales...</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <hr style="border-color: #dee2e6; margin: 0 0 15px 0;">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="departamento{{ $index }}">Departamento <span class="text-danger">*</span></label>
                                                    <select class="form-control select-departamento shadow-sm" id="departamento{{ $index }}" 
                                                            name="departamento[{{ $index }}]" required>
                                                        <option value="">Selecciona</option>
                                                        @foreach($departamentos as $d)
                                                            <option value="{{ $d->id }}" {{ $direccion->distrito->provincia->departamento_id == $d->id ? 'selected' : '' }}>{{ $d->departamento }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="provincia{{ $index }}">Provincia <span class="text-danger">*</span></label>
                                                    <select class="form-control select-provincia shadow-sm" id="provincia{{ $index }}" 
                                                            name="provincia[{{ $index }}]" required 
                                                            data-initial="{{ $direccion->distrito->provincia_id }}">
                                                        <option value="">Selecciona</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="distrito{{ $index }}">Distrito <span class="text-danger">*</span></label>
                                                    <select class="form-control shadow-sm select-distrito" id="distrito{{ $index }}" 
                                                            name="distrito[{{ $index }}]" required
                                                            data-initial="{{ $direccion->distrito_id }}">
                                                        <option value="">Selecciona</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="direccion{{ $index }}">Dirección <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control shadow-sm" name="direccion[{{ $index }}]" 
                                                        id="direccion{{ $index }}" value="{{ $direccion->direccion }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="nLotes{{ $index }}">N° / Lote</label>
                                                    <input type="text" class="form-control shadow-sm" name="nLotes[{{ $index }}]" 
                                                        id="nLotes{{ $index }}" value="{{ $direccion->numero }}">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="referencia{{ $index }}">Referencia</label>
                                                    <input type="text" class="form-control shadow-sm" name="referencia[{{ $index }}]" 
                                                        id="referencia{{ $index }}" value="{{ $direccion->referencia }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="material_inmueble{{ $index }}">Material de Inmueble</label>
                                                    <select class="form-control shadow-sm" id="material_inmueble{{ $index }}" 
                                                            name="material_inmueble[{{ $index }}]">
                                                        <option value="material_noble" {{ $direccion->material_inmueble == 'material_noble' ? 'selected' : '' }}>Material Noble</option>
                                                        <option value="prefabricada" {{ $direccion->material_inmueble == 'prefabricada' ? 'selected' : '' }}>Prefabricada</option>
                                                        <option value="machimbrado" {{ $direccion->material_inmueble == 'machimbrado' ? 'selected' : '' }}>Machimbrado</option>
                                                        <option value="otros" {{ $direccion->material_inmueble == 'otros' ? 'selected' : '' }}>Otros</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="cantPisos{{ $index }}">Cantidad de Pisos <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control shadow-sm" name="cantPisos[{{ $index }}]" 
                                                        id="cantPisos{{ $index }}" min="1" max="10" required value="{{ $direccion->cant_pisos ?? 1 }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="tipo_residencia{{ $index }}">Titular Domicilio</label>
                                                    <select class="form-control shadow-sm select-tipo-residencia" id="tipo_residencia{{ $index }}" 
                                                            name="tipo_residencia[{{ $index }}]">
                                                        <option value="Propia" {{ $direccion->tipo_residencia == 'Propia' ? 'selected' : '' }}>Propia</option>
                                                        <option value="Familiar" {{ $direccion->tipo_residencia == 'Familiar' ? 'selected' : '' }}>Familiar</option>
                                                        <option value="Alquilada" {{ $direccion->tipo_residencia == 'Alquilada' ? 'selected' : '' }}>Alquilada</option>
                                                        <option value="Otros" {{ $direccion->tipo_residencia == 'Otros' ? 'selected' : '' }}>Otros</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="tiempo_residencia{{ $index }}">Tiempo de Residencia <span class="text-danger">*</span></label>
                                                <div class="form-group d-flex">
                                                    <input type="number" class="form-control shadow-sm mr-2" name="tiempo_residencia[{{ $index }}]" 
                                                        id="tiempo_residencia{{ $index }}" min="1" max="99" style="flex: 2;" 
                                                        required value="{{ $direccion->tiempo_residencia ?? 1 }}">
                                                    <select class="form-control shadow-sm" id="anios_meses{{ $index }}" 
                                                            name="anios_meses[{{ $index }}]" style="flex: 1;">
                                                        <option value="meses" {{ $direccion->anios_meses == 'meses' ? 'selected' : '' }}>Meses</option>
                                                        <option value="años" {{ $direccion->anios_meses == 'años' ? 'selected' : '' }}>Años</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4 propietario-fields" style="{{ $direccion->tipo_residencia == 'Alquilada' ? '' : 'display: none;' }}">
                                                <div class="form-group">
                                                    <label for="nombre_propietario{{ $index }}">Nombre del Propietario</label>
                                                    <input type="text" class="form-control shadow-sm" name="nombre_propietario[{{ $index }}]" 
                                                        id="nombre_propietario{{ $index }}" value="{{ $direccion->nombre_propietario }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4 propietario-fields" style="{{ $direccion->tipo_residencia == 'Alquilada' ? '' : 'display: none;' }}">
                                                <div class="form-group">
                                                    <label for="telefono_propietario{{ $index }}">Teléfono del Propietario</label>
                                                    <input type="tel" class="form-control shadow-sm" name="telefono_propietario[{{ $index }}]" 
                                                        id="telefono_propietario{{ $index }}" value="{{ $direccion->telefono_propietario }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="row justify-content-between mt-4">
                            <div class="col-auto">
                                <button type="button" class="btn btn-lg btn-secondary prev-step shadow-sm">
                                    <i class="fas fa-arrow-left mr-2"></i> Regresar
                                </button>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-lg btn-primary next-step shadow-sm">
                                    Guardar y Continuar <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3: Datos Bancarios -->
                    <div id="step-3" class="form-step d-none" data-step-name="Datos Bancarios">
                        <div class="col-12">
                            <h6 class="font-weight-bold text-muted">Finanzas</h6>
                            <hr style="border-color: #2E5A9A;">
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="tCuenta">Tipo de Cuenta</label>
                                <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                                    @foreach ($tiposCuenta as $tipoCuenta)
                                        @php
                                            $cuentaActual = $cliente->cuentasCliente->first();
                                            $tipoCuentaActual = $cuentaActual ? $cuentaActual->tipo_cuenta_id : null;
                                            // FORZAR efectivo (ID=1) por defecto al editar
                                            $isSelected = old('tCuenta', 1) == $tipoCuenta->id;
                                        @endphp
                                        <label class="btn btn-outline-primary flex-fill {{ $isSelected ? 'active' : '' }}">
                                            <input class="tCuenta" type="radio" name="tCuenta" value="{{ $tipoCuenta->id }}" 
                                                   id="tCuenta{{ $tipoCuenta->id }}" autocomplete="off" required
                                                   {{ $isSelected ? 'checked' : '' }}> {{ $tipoCuenta->tipo_cuenta }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('tCuenta') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        @php
                            $tieneFinanzas = $cliente->cuentasCliente->isNotEmpty() && $cliente->cuentasCliente->first()->tipo_cuenta_id > 1;
                        @endphp
                        
                        <div class="col-12 mt-2" id="finanzas-section" {{ $tieneFinanzas ? '' : 'hidden' }}>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 text-muted" id="titulo-cuentas">Cuentas</h6>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary mr-2" id="addBankAccountButton">
                                        <i class="fas fa-plus mr-1"></i><i class="fas fa-university mr-1"></i> Cuenta Bancaria
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="addDigitalWalletButton">
                                        <i class="fas fa-plus mr-1"></i><i class="fas fa-mobile-alt mr-1"></i> Billetera Digital
                                    </button>
                                </div>
                            </div>
                            <div id="cuentasContainer">
                                @foreach($cliente->cuentasCliente as $index => $cuenta)
                                    @if($cuenta->tipo_cuenta_id > 1)
                                        @php
                                            $esBancaria = !is_null($cuenta->entidad_bancaria_id);
                                            $tipoCuentaText = $cuenta->tipo_cuenta_id == 2 ? 'Propia' : 'de Terceros';
                                            $tipoText = $esBancaria ? 'Cuenta Bancaria' : 'Billetera Digital';
                                        @endphp
                                        <div class="cuenta-row card mb-3" data-tipo="{{ $esBancaria ? 'bancaria' : 'digital' }}">
                                            <div class="card-body">
                                                <input type="hidden" name="id_cuenta_cliente[{{ $index }}]" value="{{ $cuenta->id }}">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="card-title mb-0">{{ $tipoText }} {{ $tipoCuentaText }} {{ $index + 1 }}</h6>
                                                    <button type="button" class="btn btn-danger btn-sm remove-account">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                                <div class="row">
                                                    @if($esBancaria)
                                                        <!-- Cuenta Bancaria -->
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Entidad Financiera</label>
                                                                <select class="form-control" name="entidad_financiera[{{ $index }}]" required>
                                                                    <option value="">Selecciona</option>
                                                                    @foreach ($entBancarias as $entBancaria)
                                                                        @if (!in_array($entBancaria->banco, ['Yape', 'Plin', 'Dale', 'Tunki', 'Bim']))
                                                                            <option value="{{ $entBancaria->id }}" {{ $cuenta->entidad_bancaria_id == $entBancaria->id ? 'selected' : '' }}>
                                                                                {{ $entBancaria->banco }}
                                                                            </option>
                                                                        @endif
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Número de Cuenta</label>
                                                                <input type="text" class="form-control" name="f_nCuenta[{{ $index }}]" 
                                                                       placeholder="Ingrese el número de cuenta" value="{{ $cuenta->numero_cuenta }}" required>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <!-- Billetera Digital -->
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Tipo de Billetera</label>
                                                                <select class="form-control tipo-billetera" name="billetera_digital[{{ $index }}]" required>
                                                                    <option value="">Selecciona</option>
                                                                    @foreach ($billeterasDigitales as $billetera)
                                                                        <option value="{{ $billetera->id }}" {{ $cuenta->billetera_digital_id == $billetera->id ? 'selected' : '' }}>
                                                                            {{ $billetera->nombre }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="label-numero-billetera">Número de Teléfono</label>
                                                                <input type="text" class="form-control numero-billetera" name="f_nCuenta[{{ $index }}]"
                                                                       placeholder="Ingrese el número de teléfono" maxlength="9" pattern="9[0-9]{8}"
                                                                       value="{{ $cuenta->numero_cuenta }}" required>
                                                                <small class="form-text text-muted">Debe comenzar con 9 y tener 9 dígitos</small>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    
                                                    @if($cuenta->tipo_cuenta_id == 3)
                                                        <div class="col-md-12">
                                                            <div class="form-group">
                                                                <label>Titular de la {{ $esBancaria ? 'Cuenta' : 'Billetera' }}</label>
                                                                <input type="text" class="form-control" name="ct_Titular[{{ $index }}]" 
                                                                       placeholder="Nombre completo del titular" value="{{ $cuenta->titular_cuenta }}" required>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                                <input type="hidden" name="tCuenta[{{ $index }}]" value="{{ $cuenta->tipo_cuenta_id }}">
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <div class="row justify-content-between mt-4">
                            <div class="col-auto">
                                <button type="button" class="btn btn-lg btn-secondary prev-step shadow-sm">
                                    <i class="fas fa-arrow-left mr-2"></i> Regresar
                                </button>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-lg btn-primary next-step shadow-sm">
                                    Guardar y Continuar <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 4: Datos Laborales -->
                    <div id="step-4" class="form-step d-none" data-step-name="Laborales">
                        <div class="row d-flex justify-content-between align-items-center mb-3">
                            <h6 class="font-weight-bold text-muted">Datos Laborales</h6>
                            <button id="btn-agregar-laboral" class="btn btn-sm btn-primary shadow-sm" type="button">
                                <i class="fas fa-plus mr-1"></i> Agregar
                            </button>
                        </div>
                        <hr style="border-color: #2E5A9A; margin-bottom: 2rem;">
                        
                        <div id="laboralesContainer">
                            @foreach($cliente->laborales as $index => $laboral)
                                <div class="row mb-4 border-bottom pb-3">
                                    <input type="hidden" name="id_laboral[{{ $index }}]" value="{{ $laboral->id }}">
                                    <div class="col-12 text-right mb-2">
                                        <button class="btn btn-sm btn-danger shadow-sm delete-laboral-btn" type="button">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="actividad_economica{{ $index }}">Actividad Económica <span class="text-danger">*</span></label>
                                            <select class="form-control shadow-sm" id="actividad_economica{{ $index }}" 
                                                    name="actividad_economica[{{ $index }}]">
                                                <option value="">Selecciona</option>
                                                <option value="Dependiente" {{ $laboral->actividad_economica == 'Dependiente' ? 'selected' : '' }}>Dependiente</option>
                                                <option value="Independiente" {{ $laboral->actividad_economica == 'Independiente' ? 'selected' : '' }}>Independiente</option>
                                                <option value="Casa" {{ $laboral->actividad_economica == 'Casa' ? 'selected' : '' }}>Casa</option>
                                                <option value="Otros" {{ $laboral->actividad_economica == 'Otros' ? 'selected' : '' }}>Otros</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nombre_lugar_trabajo{{ $index }}">Nombre del Lugar de Trabajo</label>
                                            <input type="text" class="form-control shadow-sm" name="nombre_lugar_trabajo[{{ $index }}]" 
                                                id="nombre_lugar_trabajo{{ $index }}" value="{{ $laboral->nombre_lugar_trabajo }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cargo{{ $index }}">Desempeño o Cargo</label>
                                            <input type="text" class="form-control shadow-sm" name="cargo[{{ $index }}]" 
                                                id="cargo{{ $index }}" value="{{ $laboral->cargo }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="direccion_trabajo{{ $index }}">Dirección del Trabajo</label>
                                            <input type="text" class="form-control shadow-sm" name="direccion_trabajo[{{ $index }}]" 
                                                id="direccion_trabajo{{ $index }}" value="{{ $laboral->direccion }}">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="row justify-content-between mt-4">
                            <button type="button" class="btn btn-lg btn-secondary prev-step shadow-sm">
                                <i class="fas fa-arrow-left mr-2"></i> Regresar
                            </button>
                            <button type="button" class="btn btn-lg btn-primary next-step shadow-sm">
                                Guardar y Continuar <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Paso 5: Archivos -->
                    <div id="step-5" class="form-step d-none" data-step-name="Archivos">
                        <div class="row d-flex justify-content-between align-items-center mb-3">
                            <h6 class="font-weight-bold text-muted">Archivos</h6>
                            <button id="btn-agregar-archivo" class="btn btn-sm btn-primary shadow-sm" type="button">
                                <i class="fas fa-plus mr-1"></i> Agregar
                            </button>
                        </div>
                        <hr style="border-color: #2E5A9A; margin-bottom: 2rem;">
                        
                        <div id="archivosContainer">
                            @foreach($cliente->documentosCliente as $index => $documento)
                                <div class="row mb-3 border-bottom pb-3 archivo-existente">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Archivo Actual</label>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-file-alt mr-2"></i>
                                                <a href="{{ asset('files/client_files/' . $documento->ruta_archivo) }}" target="_blank" class="text-primary">
                                                    {{ $documento->ruta_archivo }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Descripción</label>
                                            <p class="form-control-plaintext">{{ $documento->tipo_documento }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <div>
                                                <input type="checkbox" name="uploaded_files[]" value="{{ $documento->id }}" checked 
                                                       id="keep_file_{{ $index }}" class="mr-2">
                                                <label for="keep_file_{{ $index }}" class="mb-0">Mantener</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div id="nuevos-archivos-container">
                            <!-- Aquí se añadirán los nuevos archivos -->
                        </div>
                        
                        <div class="row justify-content-between mt-4">
                            <button type="button" class="btn btn-lg btn-secondary prev-step shadow-sm">
                                <i class="fas fa-arrow-left mr-2"></i> Regresar
                            </button>
                            <button type="button" class="btn btn-lg btn-primary next-step shadow-sm">
                                Guardar y Continuar <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Paso 6: Avales del Cliente -->
                    <div id="step-6" class="form-step d-none" data-step-name="Avales">
                        <h6 class="font-weight-bold text-muted mb-3">Avales del Cliente</h6>
                        <hr style="border-color: #2E5A9A; margin-bottom: 2rem;">
                        
                        @php
                            // Obtener todos los avales del cliente a través de sus préstamos
                            $avalesCliente = \App\Models\Aval::whereHas('prestamo', function($query) use ($cliente) {
                                $query->where('cliente_id', $cliente->id);
                            })->with(['persona.telefonos', 'persona.cliente', 'prestamo'])->get();
                        @endphp
                        
                        @if($avalesCliente->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>DNI</th>
                                            <th>Nombre Completo</th>
                                            <th>Parentesco</th>
                                            <th>Teléfono</th>
                                            <th>Préstamo</th>
                                            <th>Estado Préstamo</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($avalesCliente as $aval)
                                            <tr>
                                                <td>{{ $aval->persona->documento ?? 'N/A' }}</td>
                                                <td>
                                                    <strong>{{ $aval->persona->nombres ?? '' }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $aval->persona->ape_pat ?? '' }} {{ $aval->persona->ape_mat ?? '' }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-info">{{ $aval->parentesco ?? 'No especificado' }}</span>
                                                </td>
                                                <td>
                                                    @if($aval->persona && $aval->persona->telefonos->count() > 0)
                                                        {{ $aval->persona->telefonos->first()->numero }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('admin.prestamos.show', $aval->prestamo_id) }}" class="text-primary">
                                                        #{{ $aval->prestamo_id }}
                                                    </a>
                                                    <br>
                                                    <small class="text-muted">S/ {{ number_format($aval->prestamo->monto ?? 0, 2) }}</small>
                                                </td>
                                                <td>
                                                    @php
                                                        $badgeClass = match($aval->prestamo->estado ?? '') {
                                                            'Vigente' => 'badge-primary',
                                                            'Vigente con Moras' => 'badge-danger',
                                                            'Moroso' => 'badge-danger',
                                                            'Finalizado', 'Liquidado' => 'badge-success',
                                                            'Cancelado', 'Anulado' => 'badge-secondary',
                                                            default => 'badge-light'
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }}">{{ $aval->prestamo->estado ?? 'N/A' }}</span>
                                                </td>
                                                <td class="text-center">
                                                    @if($aval->persona && $aval->persona->cliente)
                                                        <a href="{{ route('admin.clientes.edit', $aval->persona->cliente->id) }}" 
                                                           class="btn btn-sm btn-primary" title="Ver Cliente">
                                                            <i class="fas fa-user-edit"></i>
                                                        </a>
                                                    @else
                                                        <span class="text-muted" title="No es cliente registrado">
                                                            <i class="fas fa-user-slash"></i>
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Total de avales:</strong> {{ $avalesCliente->count() }} aval(es) registrado(s) para los préstamos de este cliente.
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-user-friends text-muted" style="font-size: 4rem;"></i>
                                <h5 class="text-muted mt-3">Sin Avales Registrados</h5>
                                <p class="text-muted">Este cliente no tiene avales asociados a sus préstamos.</p>
                            </div>
                        @endif
                        
                        @if($aval->observaciones ?? false)
                            <div class="card mt-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-sticky-note mr-2"></i>Observaciones</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0">{{ $aval->observaciones }}</p>
                                </div>
                            </div>
                        @endif
                        
                        <div class="row justify-content-between mt-4">
                            <button type="button" class="btn btn-lg btn-secondary prev-step shadow-sm">
                                <i class="fas fa-arrow-left mr-2"></i> Regresar
                            </button>
                            <button type="button" class="btn btn-lg btn-primary next-step shadow-sm">
                                Continuar <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Paso 7: Finalizar -->
                    <div id="step-7" class="form-step d-none" data-step-name="Finalizar">
                        <div class="row">
                            <div class="col-12 text-center">
                                <div class="mb-4">
                                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                                </div>
                                <h4 class="mb-4 text-primary">Resumen de Cambios</h4>
                                <p class="lead mb-4">Ha completado la edición del cliente {{ $cliente->persona->nombres }} {{ $cliente->persona->ape_pat }}.</p>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Presione "Actualizar Cliente" para guardar todos los cambios realizados.
                                </div>
                            </div>
                        </div>
                        
                        <div class="row justify-content-between mt-4">
                            <button type="button" class="btn btn-lg btn-secondary prev-step shadow-sm">
                                <i class="fas fa-arrow-left mr-2"></i> Regresar
                            </button>
                            <button type="submit" class="btn btn-lg btn-success shadow-sm">
                                <i class="fas fa-save mr-1"></i> Actualizar Cliente
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <style>
        /* Variables del formulario - Compatible con el layout existente */
        .cliente-form {
            --form-primary: #4f46e5;
            --form-primary-light: #6366f1;
            --form-primary-dark: #3730a3;
            --form-success: #10b981;
            --form-warning: #f59e0b;
            --form-danger: #ef4444;
            --form-gray-100: #f3f4f6;
            --form-gray-200: #e5e7eb;
            --form-gray-300: #d1d5db;
            --form-gray-500: #6b7280;
            --form-gray-600: #4b5563;
            --form-gray-700: #374151;
            --form-gray-900: #111827;
        }

        /* Contenedor principal del formulario */
        .cliente-form {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-primary);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            margin: 0;
        }

        /* Header del formulario */
        .cliente-form .form-header {
            background: linear-gradient(135deg, var(--form-primary), var(--form-primary-light));
            color: white;
            padding: 1.5rem 2rem;
            margin: 0;
            border-bottom: none;
        }

        .cliente-form .form-header h5 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0 0 1rem 0;
            color: white;
        }

        /* Barra de progreso */
        .cliente-form #step-progress { 
            position: relative; 
            width: 90%; 
            max-width: 100%; 
            margin: 0 auto; 
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cliente-form .step-item { 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            position: relative; 
            flex: 1; 
            z-index: 2; 
            cursor: pointer;
            
        }

        .cliente-form .step-item:hover .step-circle {
            transform: scale(1.1);
        }
        /* Estilos para las cards de cuentas múltiples */
        .bank-account-row .card,
        .digital-wallet-row .card {
            border-left: 4px solid var(--primary);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .bank-account-row .card-title,
        .digital-wallet-row .card-title {
            color: var(--primary);
            font-weight: 600;
        }
        
        .remove-bank-account,
        .remove-digital-wallet {
            transition: all 0.3s ease;
        }
        
        .remove-bank-account:hover,
        .remove-digital-wallet:hover {
            transform: scale(1.1);
        }

        .cliente-form .step-circle { 
            width: 35px; 
            height: 35px; 
            background-color: rgba(255, 255, 255, 0.2); 
            color: white; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: 600; 
             
            border: 2px solid transparent;
            font-size: 14px;
        }

        .cliente-form .step-item.active .step-circle { 
            background-color: var(--form-warning); 
            color: white; 
            border-color: white;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.3);
        }

        .cliente-form .step-label { 
            font-size: 0.8rem; 
            color: rgba(255, 255, 255, 0.9); 
            margin-top: 6px; 
            text-align: center; 
            font-weight: 500;
        }

        .cliente-form .step-progress-line { 
            position: absolute; 
            top: 17px; 
            left: 0; 
            width: 100%; 
            height: 2px; 
            background-color: rgba(255, 255, 255, 0.2); 
            z-index: 1; 
            border-radius: 1px;
        }

        .cliente-form .progress-bar { 
            height: 100%; 
            background: var(--form-warning); 
            transition: width 0.5s ease; 
            border-radius: 1px;
        }

        /* Body del formulario */
        .cliente-form .form-body {
            padding: 2rem;
            background: var(--bg-primary);
        }

        /* Form controls - Heredar del layout */
        .cliente-form .form-control {
            height: 44px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-primary);
            background: var(--bg-primary);
            color: var(--text-primary);
            
            font-size: 15px;
        }

        .cliente-form .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background: var(--bg-primary);
            color: var(--text-primary);
        }

        .cliente-form .form-control::placeholder {
            color: var(--text-tertiary);
        }

        /* Labels */
        .cliente-form .form-group label {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 14px;
        }

        .cliente-form .text-danger {
            color: var(--form-danger) !important;
        }

        /* Botones - Usar estilos del layout */
        .cliente-form .btn {
            border-radius: var(--radius-md);
            font-weight: 500;
            
            font-size: 15px;
            padding: var(--space-md) var(--space-xl);
        }

        .cliente-form .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-color: var(--primary);
            color: white;
        }

        .cliente-form .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .cliente-form .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
            background: transparent;
        }

        .cliente-form .btn-outline-primary:hover,
        .cliente-form .btn-outline-primary.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .cliente-form .btn-secondary {
            background: var(--text-secondary);
            border-color: var(--text-secondary);
            color: white;
        }

        .cliente-form .btn-danger {
            background: var(--form-danger);
            border-color: var(--form-danger);
            color: white;
        }

        .cliente-form .btn-success {
            background: var(--form-success);
            border-color: var(--form-success);
            color: white;
        }

        /* Estados de validación */
        .cliente-form .is-invalid {
            border-color: var(--form-danger) !important;
        }

        .cliente-form .is-valid {
            border-color: var(--form-success) !important;
        }

        .cliente-form .invalid-feedback {
            display: block;
            color: var(--form-danger);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .cliente-form .valid-feedback {
            display: block;
            color: var(--form-success);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* Ocultar flechas de input number */
        .cliente-form input[type="number"]::-webkit-outer-spin-button,
        .cliente-form input[type="number"]::-webkit-inner-spin-button,
        .cliente-form input[type="tel"]::-webkit-outer-spin-button,
        .cliente-form input[type="tel"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .cliente-form input[type="number"],
        .cliente-form input[type="tel"] {
            -moz-appearance: textfield;
        }

        /* Espaciado y layout */
        .cliente-form .form-step {
            animation: fadeIn 0.4s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .cliente-form .border-bottom {
            border-bottom: 1px solid var(--border-primary) !important;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        /* Button groups */
        .cliente-form .btn-group-toggle .btn {
            flex: 1;
            font-size: 14px;
            padding: 0.5rem 0.75rem;
        }

        .cliente-form .btn-group-toggle label.active {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
            color: white !important;
        }

        /* Input groups */
        .cliente-form .input-group-text {
            background: var(--bg-tertiary);
            border-color: var(--border-primary);
            color: var(--text-secondary);
        }

        /* Secciones */
        .cliente-form h6 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .cliente-form hr {
            border-color: var(--border-primary);
            margin: 1rem 0;
        }

        /* Select y otros elementos */
        .cliente-form select.form-control {
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .cliente-form .form-body {
                padding: 1rem;
            }

            .cliente-form .form-header {
                padding: 1rem;
            }

            .cliente-form #step-progress {
                width: 100%;
            }

            .cliente-form .step-circle {
                width: 28px;
                height: 28px;
                font-size: 12px;
            }

            .cliente-form .step-label {
                font-size: 0.7rem;
            }

            .cliente-form .step-progress-line {
                top: 14px;
            }

            .cliente-form .btn-group-toggle .btn {
                font-size: 0.8rem;
                padding: 0.4rem 0.5rem;
            }
        }

        /* Asegurar que el layout principal no se vea afectado */
        .cliente-form .row {
            margin-left: -0.75rem;
            margin-right: -0.75rem;
        }

        .cliente-form .row > [class*="col-"] {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        /* Alertas personalizadas para el formulario */
        .cliente-form .alert {
            border-radius: var(--radius-md);
            border: none;
            border-left: 4px solid;
            font-size: 15px;
            margin-bottom: 1.5rem;
        }

        .cliente-form .alert-info {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info);
            border-left-color: var(--info);
        }

        .cliente-form .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--form-success);
            border-left-color: var(--form-success);
        }

        .cliente-form .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--form-danger);
            border-left-color: var(--form-danger);
        }

        .cliente-form .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--form-warning);
            border-left-color: var(--form-warning);
        }

        /* Spinner de carga */
        .cliente-form .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }

        /* Mejorar contraste en modo oscuro */
        [data-theme="dark"] .cliente-form .step-circle {
            background-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .cliente-form .step-progress-line {
            background-color: rgba(255, 255, 255, 0.1);
        }
    </style>
@stop

@section('js')
    <script>
        // Variables globales
        let totalSteps = 0;
        let phoneCounter = {{ $cliente->persona->telefonos->count() }};
        let accountCounter = {{ $cliente->cuentasCliente->count() }};
        let addressCounter = {{ $cliente->persona->direcciones->count() }};
        let jobCounter = {{ $cliente->laborales->count() }};
        let tagCounter = {{ $cliente->etiquetasCliente->count() }};
        let fileCounter = 0;
        let currentTipoCuenta = {{ is_array(old('tCuenta')) ? 1 : (old('tCuenta') ?? 1) }}; // FORZAR efectivo (ID=1) por defecto

        // Datos para los selects
        const departamentos = {!! json_encode($departamentos) !!};
        const tiposCuenta = {!! json_encode($tiposCuenta) !!};
        const entBancarias = {!! json_encode($entBancarias) !!};
        const sucursales = {!! json_encode($sucursales) !!};
        const etiquetas = {!! json_encode($etiquetas) !!};
        const zonas = {!! json_encode($zonas) !!};

        $(document).ready(function() {
            console.log('Iniciando aplicación de edición...');
            
            // Asegurar visibilidad del sidebar
            $('.main-sidebar').show();

            // Función para mostrar alertas personalizadas
            window.showAlert = function(message, type = 'info') {
                const alertClass = type === 'success' ? 'alert-success' : 
                                 type === 'error' ? 'alert-danger' : 
                                 type === 'warning' ? 'alert-warning' : 'alert-info';
                
                const alertHtml = `
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                        ${message}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                `;
                
                $('body').append(alertHtml);
                
                // Auto-ocultar después de 3 segundos
                setTimeout(function() {
                    $('.alert').fadeOut();
                }, 3000);
            };
            
            // Inicializar edad
            calcularEdad($('#fecha_nacimiento').val());
            $('#fecha_nacimiento').on('change', function() { 
                calcularEdad($(this).val()); 
            });

            // Manejo de carga de imagen
            initImageUpload();
            
            // Configurar sistema de pasos
            initStepSystem();
            
            // Configurar eventos dinámicos
            initDynamicEvents();
            
            // Inicializar sistema de ubicaciones
            initLocationSystem();
            
            // Cargar provincias y distritos iniciales
            loadInitialLocationData();
            
            // Configurar validación del formulario
            initFormValidation();
            
            // DNI Search functionality
            $('#buscar-dni').click(function() {
                const dni = $('#nDocumento').val();
                if (dni.length !== 8) {
                    alert('Ingrese un DNI válido de 8 dígitos');
                    return;
                }
                
                $.ajax({
                    url: "{{ route('consultar.dni.edicion') }}",
                    method: 'POST',
                    data: {
                        nDocumento: dni,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        console.log('Respuesta de la API:', response);
                        if (response.valid) {
                            if (response.data) {
                                // Poblar datos básicos
                                $('#nombres').val(response.data.nombres || '');
                                $('#aPaterno').val(response.data.apellido_paterno || '');
                                $('#aMaterno').val(response.data.apellido_materno || '');
                                
                                if (response.data.fecha_nacimiento) {
                                    let fechaNacimiento = formatearFechaParaInput(response.data.fecha_nacimiento);
                                    $('#fecha_nacimiento').val(fechaNacimiento);
                                    calcularEdad(fechaNacimiento);
                                }

                                // Poblar datos de dirección si vienen de la API
                                if (response.data.direccion) {
                                    // Actualizar primer campo de dirección (índice 0)
                                    $('input[name="direccion[0]"]').val(response.data.direccion);
                                    
                                    // Si viene referencia o dirección completa
                                    if (response.data.direccion_completa) {
                                        $('input[name="referencia[0]"]').val('Dirección completa: ' + response.data.direccion_completa);
                                    }
                                    
                                    console.log('Dirección actualizada desde API:', response.data.direccion);
                                }

                                // Manejar ubigeo si está disponible
                                if (response.data.ubigeo) {
                                    console.log('Ubigeo recibido:', response.data.ubigeo);
                                }

                                // Mostrar datos adicionales en consola para verificación
                                if (response.data.departamento) {
                                    console.log('Departamento API:', response.data.departamento);
                                }
                                if (response.data.provincia) {
                                    console.log('Provincia API:', response.data.provincia);
                                }
                                if (response.data.distrito) {
                                    console.log('Distrito API:', response.data.distrito);
                                }

                                // Mostrar mensaje de éxito
                                showAlert('Datos actualizados desde la API exitosamente', 'success');
                            }
                        } else {
                            alert(response.error || 'Error al consultar DNI');
                        }
                    },
                    error: function(xhr) {
                        alert('Error en la consulta: ' + xhr.responseText);
                    }
                });
            });
            
            // Control de visibilidad de sección cónyuge
            function toggleSeccionConyuge() {
                // Si ya existe una relación de cónyuge en BD, mantener la sección visible
                const hasConyuge = $('#seccion-conyuge').data('has-conyuge') == 1;
                if (hasConyuge) {
                    $('#seccion-conyuge').show();
                    return;
                }

                const estadoCivil = $('#estado_civil').val();
                const wasHidden = $('#seccion-conyuge').is(':hidden');

                if (estadoCivil === 'Casado' || estadoCivil === 'Conviviente') {
                    $('#seccion-conyuge').show();
                } else {
                    $('#seccion-conyuge').hide();
                    // Limpiar campos de cónyuge solo si estaba visible y ahora se oculta
                    if (!wasHidden) {
                        $('#seccion-conyuge input').val('');
                    }
                }
            }
            
            $('#estado_civil').change(toggleSeccionConyuge);
            
            // Ejecutar al cargar la página para manejar old() values
            toggleSeccionConyuge();
            
            // Control de tipo de cuenta para mostrar botones de agregar cuentas
            $('input[name="tCuenta"]').on('change', function() {
                currentTipoCuenta = parseInt($(this).val());
                $('#finanzas-section').removeAttr('hidden');
                
                // Actualizar título según el tipo
                const tituloMap = {
                    1: 'Cuentas (Sin Cuentas)',
                    2: 'Cuentas Propias',
                    3: 'Cuentas de Terceros'
                };
                $('#titulo-cuentas').text(tituloMap[currentTipoCuenta] || 'Cuentas');
                
                // Mostrar/ocultar botones según el tipo
                if (currentTipoCuenta === 1) {
                    // Sin cuentas - ocultar botones
                    $('#addBankAccountButton, #addDigitalWalletButton').hide();
                    // Limpiar cuentas existentes
                    $('#cuentasContainer').empty();
                } else {
                    // Con cuentas - mostrar botones
                    $('#addBankAccountButton, #addDigitalWalletButton').show();
                }
            });
            
            // Inicializar contador de teléfonos para mostrar/ocultar botones
            updatePhoneDeleteButtons();
            
            console.log('Aplicación de edición inicializada correctamente');
        });

        // Función para formatear fecha para input[type="date"]
        function formatearFechaParaInput(fecha) {
            if (!fecha) return '';
            
            try {
                // Si ya está en formato YYYY-MM-DD, devolverlo tal como está
                if (/^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
                    return fecha;
                }
                
                // Si está en formato DD/MM/YYYY
                if (/^\d{2}\/\d{2}\/\d{4}$/.test(fecha)) {
                    let partes = fecha.split('/');
                    return `${partes[2]}-${partes[1].padStart(2, '0')}-${partes[0].padStart(2, '0')}`;
                }
                
                // Si está en formato DD-MM-YYYY
                if (/^\d{2}-\d{2}-\d{4}$/.test(fecha)) {
                    let partes = fecha.split('-');
                    return `${partes[2]}-${partes[1].padStart(2, '0')}-${partes[0].padStart(2, '0')}`;
                }
                
                // Intentar parsearlo como fecha y convertir
                fechaObj = new Date(fecha);
                if (isNaN(fechaObj.getTime())) {
                    console.warn('Formato de fecha no reconocido:', fecha);
                    return '';
                }
                
                // Convertir a formato YYYY-MM-DD
                let año = fechaObj.getFullYear();
                let mes = (fechaObj.getMonth() + 1).toString().padStart(2, '0');
                let dia = fechaObj.getDate().toString().padStart(2, '0');
                
                return `${año}-${mes}-${dia}`;
                
            } catch (error) {
                console.error('Error al formatear fecha:', error, fecha);
                return '';
            }
        }

        // Función para calcular edad
        function calcularEdad(fechaNacimiento) {
            if (!fechaNacimiento) return;
            
            const hoy = new Date();
            const nacimiento = new Date(fechaNacimiento);
            let edad = hoy.getFullYear() - nacimiento.getFullYear();
            const mes = hoy.getMonth() - nacimiento.getMonth();
            
            if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
                edad--;
            }
            
            $('#edad').val(edad);
        }

        // Inicializar carga de imagen
        function initImageUpload() {
            const defaultImage = "{{ asset('storage/img/clientes_img/userDefaultPhoto.png') }}";
            
            $('#file').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validar tipo de archivo
                    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!validTypes.includes(file.type)) {
                        Swal.fire({
                            title: 'Archivo inválido',
                            text: 'Por favor seleccione una imagen válida (JPG, PNG, GIF, WEBP).',
                            icon: 'error'
                        });
                        this.value = '';
                        return;
                    }
                    
                    // Validar tamaño (máximo 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        Swal.fire({
                            title: 'Archivo muy grande',
                            text: 'La imagen no debe superar los 5MB.',
                            icon: 'error'
                        });
                        this.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) { 
                        $('#img').attr('src', e.target.result); 
                    };
                    reader.readAsDataURL(file);
                } else {
                    $('#img').attr('src', defaultImage);
                }
            });
        }

        // Inicializar sistema de pasos
        function initStepSystem() {
            const formSteps = $('.form-step');
            totalSteps = formSteps.length;
            console.log('Total de pasos:', totalSteps);

            // Limpiar y crear indicadores de progreso
            $('#step-progress').empty();
            
            // Crear línea de progreso
            $('#step-progress').append('<div class="step-progress-line"><div class="progress-bar"></div></div>');
            
            // Crear indicadores de pasos
            formSteps.each(function(index) {
                const stepItem = $(`
                    <div class="step-item" data-step="${index + 1}">
                        <div class="step-circle">${index + 1}</div>
                        <div class="step-label">${$(this).data('step-name')}</div>
                    </div>
                `);
                
                stepItem.on('click', function() { 
                    const targetStep = parseInt($(this).data('step'));
                    goToStep(targetStep); 
                });
                
                $('#step-progress').append(stepItem);
            });
            
            // Activar primer paso
            updateStepProgress(1);
        }

        // Navegación entre pasos
        function goToStep(step) {
            if (step < 1 || step > totalSteps) return;
            
            // Deshabilitar validación de campos en pasos ocultos
            $('.form-step').each(function() {
                const stepNum = $(this).attr('id').split('-')[1];
                if (stepNum != step) {
                    $(this).find('input[required], select[required]').each(function() {
                        $(this).attr('data-required', 'true'); // Guardar estado original
                        $(this).prop('required', false);
                    });
                } else {
                    // Re-habilitar validación para el paso actual
                    $(this).find('input[data-required="true"], select[data-required="true"]').each(function() {
                        $(this).prop('required', true);
                    });
                }
            });
            
            $('.form-step').addClass('d-none');
            $(`#step-${step}`).removeClass('d-none');
            updateStepProgress(step);
        }

        function updateStepProgress(activeStep) {
            $('.step-item').removeClass('active')
                .filter(`[data-step="${activeStep}"]`)
                .addClass('active');
            
            const progressWidth = ((activeStep - 1) / (totalSteps - 1)) * 100;
            $('.progress-bar').css('width', `${progressWidth}%`);
        }

        // Validación de pasos
        function validateStep(step) {
            let valid = true;
            $('.is-invalid').removeClass('is-invalid');
            
            switch(step) {
                case 1: // Datos personales
                    valid = validateRequiredFields('#step-1 input[required], #step-1 select[required]');
                    break;
                case 2: // Residencia y datos administrativos
                    // Validar zona y sucursal primero
                    valid = validateRequiredFields('#step-2 select[name="zona"], #step-2 select[name="sucursal"]');
                    
                    // Validar que exista al menos una dirección
                    if ($('#direccionesContainer .direccion-row').length === 0) {
                        showValidationError('Debe tener al menos una dirección.');
                        valid = false;
                    } else {
                        // Validar campos requeridos de direcciones
                        const direccionValid = validateRequiredFields('#step-2 input[required], #step-2 select[required]:not([name="zona"]):not([name="sucursal"])');
                        valid = valid && direccionValid;
                    }
                    break;
                case 3: // Datos bancarios
                    // Validar que exista al menos una cuenta
                    if ($('#cuentasContainer .cuenta-row').length === 0) {
                        showValidationError('Debe tener al menos una cuenta bancaria o método de pago.');
                        valid = false;
                    } else {
                        valid = validateRequiredFields('#step-3 input[required], #step-3 select[required]');
                    }
                    break;
                case 4: // Datos laborales (opcional)
                    // Los datos laborales son opcionales, pero si existen deben estar completos
                    valid = validateRequiredFields('#step-4 input[required], #step-4 select[required]');
                    break;
                case 5: // Etiquetas (opcional)
                    valid = validateRequiredFields('#step-5 select[required]');
                    break;
                case 6: // Archivos (opcional)
                    // Los archivos son opcionales
                    valid = true;
                    break;
                case 7: // Finalizar
                    valid = true;
                    break;
            }
            
            return valid;
        }

        function validateRequiredFields(selector) {
            let valid = true;
            
            $(selector).each(function() {
                // Solo validar campos que están visibles
                if ($(this).is(':visible') || $(this).closest('.form-group').is(':visible')) {
                    if (!$(this).val() || $(this).val().trim() === '') {
                        $(this).addClass('is-invalid');
                        valid = false;
                        console.log('Campo inválido:', $(this).attr('name'), $(this).attr('id'));
                    } else {
                        $(this).removeClass('is-invalid').addClass('is-valid');
                    }
                }
            });
            
            if (!valid) {
                showValidationError('Por favor, complete todos los campos obligatorios marcados con (*).');
            }
            
            return valid;
        }

        function showValidationError(message) {
            Swal.fire({
                title: 'Error de validación',
                text: message,
                icon: 'error',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#1A3C6D'
            });
        }

        // Inicializar eventos dinámicos
        function initDynamicEvents() {
            // Navegación entre pasos
            $(document).on('click', '.next-step', function(e) {
                e.preventDefault();
                const current = parseInt($('.form-step:not(.d-none)').attr('id').split('-')[1]);
                
                if (validateStep(current)) {
                    if (current < totalSteps) goToStep(current + 1);
                }
            });
            
            $(document).on('click', '.prev-step', function(e) {
                e.preventDefault();
                const current = parseInt($('.form-step:not(.d-none)').attr('id').split('-')[1]);
                if (current > 1) goToStep(current - 1);
            });

            // Eventos para teléfonos
            $('#addPhoneButton').on('click', addPhone);
            $(document).on('click', '.delete-phone-btn', function() {
                const row = $(this).closest('.row');
                row.remove();
                updatePhoneDeleteButtons();
                updatePhoneLabels();
            });
            $(document).on('change', '#phoneContainer input[type="radio"]', function() {
                const row = $(this).closest('.row');
                const isOther = $(this).val() === 'otro';
                row.find('.col-md-5').toggle(isOther);
            });

            // Eventos para direcciones
            $('#btn-agregar-direccion').on('click', addAddress);
            $(document).on('click', '.delete-direccion-btn', function() {
                $(this).closest('.direccion-row').remove();
            });
            $(document).on('change', '.select-tipo-residencia', function() {
                titularDomicilioChange.call(this);
            });

            // Evento para carga dinámica de sucursales cuando cambia la zona
            $(document).on('change', '.select-zona-direccion', async function() {
                const zonaId = $(this).val();
                const addressCard = $(this).closest('.direccion-row');
                const sucursalSelect = addressCard.find('.select-sucursal-direccion');

                if (!zonaId) {
                    sucursalSelect.html('<option value="">Primero selecciona una zona</option>');
                    return;
                }

                try {
                    sucursalSelect.html('<option value="">Cargando...</option>').prop('disabled', true);
                    const response = await fetch(`/zona/${zonaId}/sucursales`);

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();
                    sucursalSelect.html('<option value="">Selecciona una sucursal</option>' +
                        data.map(s => `<option value="${s.id}">${s.sucursal}</option>`).join('')
                    ).prop('disabled', false);

                    // Seleccionar sucursal inicial si existe
                    const initialSucursal = sucursalSelect.data('initial');
                    if (initialSucursal) {
                        console.log('Seleccionando sucursal inicial:', initialSucursal);
                        sucursalSelect.val(initialSucursal);
                        // Remover el data-initial después de usarlo
                        sucursalSelect.removeData('initial');
                    }
                } catch (error) {
                    console.error('Error loading sucursales:', error);
                    sucursalSelect.html('<option value="">Error al cargar sucursales</option>').prop('disabled', false);
                }
            });

            // Cargar sucursales iniciales para direcciones existentes
            setTimeout(function() {
                $('.select-zona-direccion').each(function() {
                    const zonaId = $(this).val();
                    console.log('Inicializando zona:', zonaId);
                    if (zonaId) {
                        $(this).trigger('change');
                    }
                });
            }, 100);

            // Eventos para laborales
            $('#btn-agregar-laboral').on('click', addJob);
            $(document).on('click', '.delete-laboral-btn', function() {
                $(this).closest('.row').remove();
            });

            // Eventos para etiquetas
            $('#btn-agregar-etiqueta').on('click', addTag);
            $(document).on('click', '.delete-etiqueta-btn', function() {
                $(this).closest('.row').remove();
            });
            $(document).on('change', '.select-etiqueta', function() {
                const row = $(this).closest('.row');
                const colorInput = row.find('[id^="color_etiqueta"]');
                const selectedOption = $(this).find('option:selected');
                const defaultColor = selectedOption.data('attr') || '#000000';
                
                if (!colorInput.val() || colorInput.val() === '#000000') {
                    colorInput.val(defaultColor);
                }
                updateObservationBorder(row);
            });
            $(document).on('input', '[id^="color_etiqueta"]', function() {
                updateObservationBorder($(this).closest('.row'));
            });

            // Eventos para archivos
            $('#btn-agregar-archivo').on('click', addFile);
            $(document).on('click', '.delete-archivo-btn', function() {
                $(this).closest('.row').remove();
            });

            // Eventos para cuentas
            $('#addBankAccountButton').on('click', function() {
                const count = $('#cuentasContainer .cuenta-row[data-tipo="bancaria"]').length;
                createBankAccountRow(count);
            });

            $('#addDigitalWalletButton').on('click', function() {
                const count = $('#cuentasContainer .cuenta-row[data-tipo="digital"]').length;
                createDigitalWalletRow(count);
            });

            $(document).on('click', '.remove-account', function() {
                $(this).closest('.cuenta-row').remove();
                updateAccountIndexes();
            });
        }

        // Funciones para cuentas
        function createBankAccountRow(index) {
            const tipoCuentaText = currentTipoCuenta == 2 ? 'Propia' : 'de Terceros';
            const tipo = currentTipoCuenta == 2 ? 'propia' : 'terceros';
            
            const accountRow = $(
                `<div class="cuenta-row card mb-3" data-tipo="bancaria">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="card-title mb-0">Cuenta Bancaria ${tipoCuentaText} ${index + 1}</h6>
                            <button type="button" class="btn btn-danger btn-sm remove-account">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Entidad Financiera</label>
                                    <select class="form-control" name="entidad_financiera[${accountCounter}]" required>
                                        <option value="">Selecciona</option>
                                        @foreach ($entBancarias as $entBancaria)
                                            @if (!in_array($entBancaria->banco, ['Yape', 'Plin', 'Dale', 'Tunki', 'Bim']))
                                                <option value="{{ $entBancaria->id }}">{{ $entBancaria->banco }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Número de Cuenta</label>
                                    <input type="text" class="form-control" name="f_nCuenta[${accountCounter}]" placeholder="Ingrese el número de cuenta" required>
                                </div>
                            </div>
                            ${currentTipoCuenta == 3 ? `
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Titular de la Cuenta</label>
                                    <input type="text" class="form-control" name="ct_Titular[${accountCounter}]" placeholder="Nombre completo del titular" required>
                                </div>
                            </div>` : ''}
                        </div>
                        <input type="hidden" name="tCuenta[${accountCounter}]" value="${currentTipoCuenta}">
                    </div>
                </div>`
            );
            
            $('#cuentasContainer').append(accountRow);
            accountCounter++;
        }

        function createDigitalWalletRow(index) {
            const tipoCuentaText = currentTipoCuenta == 2 ? 'Propia' : 'de Terceros';
            
            const walletRow = $(
                `<div class="cuenta-row card mb-3" data-tipo="digital">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="card-title mb-0">Billetera Digital ${tipoCuentaText} ${index + 1}</h6>
                            <button type="button" class="btn btn-danger btn-sm remove-account">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tipo de Billetera</label>
                                    <select class="form-control tipo-billetera" name="billetera_digital[${accountCounter}]" required>
                                        <option value="">Selecciona</option>
                                        @foreach ($billeterasDigitales as $billetera)
                                            <option value="{{ $billetera->id }}">{{ $billetera->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="label-numero-billetera">Número de Teléfono</label>
                                    <input type="text" class="form-control numero-billetera" name="f_nCuenta[${accountCounter}]"
                                           placeholder="Ingrese el número de teléfono" maxlength="9" pattern="9[0-9]{8}" required>
                                    <small class="form-text text-muted">Debe comenzar con 9 y tener 9 dígitos</small>
                                </div>
                            </div>
                            ${currentTipoCuenta == 3 ? `
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Titular de la Billetera</label>
                                    <input type="text" class="form-control" name="ct_Titular[${accountCounter}]" placeholder="Nombre completo del titular" required>
                                </div>
                            </div>` : ''}
                        </div>
                        <input type="hidden" name="tCuenta[${accountCounter}]" value="${currentTipoCuenta}">
                    </div>
                </div>`
            );
            
            // Manejar cambio de tipo de billetera
            walletRow.find('.tipo-billetera').on('change', function() {
                const billeteraSeleccionada = $(this).find('option:selected').text();
                const labelBilletera = walletRow.find('.label-numero-billetera');
                
                if (billeteraSeleccionada === 'Yape') {
                    labelBilletera.text('Número de Yape');
                } else if (billeteraSeleccionada === 'Plin') {
                    labelBilletera.text('Número de Plin');
                } else {
                    labelBilletera.text('Número de Teléfono');
                }
            });
            
            // Validación de número de teléfono
            walletRow.find('.numero-billetera').on('input', function() {
                let value = $(this).val();
                value = value.replace(/[^0-9]/g, '');
                if (value.length > 0 && value.charAt(0) !== '9') {
                    value = '9' + value.substring(1);
                }
                if (value.length > 9) {
                    value = value.substring(0, 9);
                }
                $(this).val(value);
            });
            
            $('#cuentasContainer').append(walletRow);
            accountCounter++;
        }

        function updateAccountIndexes() {
            // Reindexar todas las cuentas después de eliminar alguna
            $('#cuentasContainer .cuenta-row').each(function(index) {
                const esBancaria = $(this).data('tipo') === 'bancaria';
                const tipoCuentaText = currentTipoCuenta == 2 ? 'Propia' : 'de Terceros';
                const tipoText = esBancaria ? 'Cuenta Bancaria' : 'Billetera Digital';
                
                $(this).find('.card-title').text(`${tipoText} ${tipoCuentaText} ${index + 1}`);
                
                // Actualizar nombres de campos
                $(this).find('select, input').each(function() {
                    const name = $(this).attr('name');
                    if (name) {
                        const newName = name.replace(/\[\d+\]/, `[${index}]`);
                        $(this).attr('name', newName);
                    }
                });
            });
        }

        // Funciones para agregar elementos dinámicos
        function addPhone() {
            const phoneRow = $(`
                <div class="row d-flex align-items-center mb-3" data-phone-index="${phoneCounter}">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="telefono${phoneCounter}">Teléfono ${phoneCounter + 1}</label>
                            <input type="tel" class="form-control shadow-sm" name="telefono[${phoneCounter}]" 
                                id="telefono${phoneCounter}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Tipo</label>
                            <div class="btn-group-toggle d-flex" data-toggle="buttons" style="margin-bottom: -10px !important;">
                                <label class="btn btn-outline-primary active">
                                    <input type="radio" name="tipo[${phoneCounter}]" value="celular" checked> Celular
                                </label>
                                <label class="btn btn-outline-primary">
                                    <input type="radio" name="tipo[${phoneCounter}]" value="otro"> Otro
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5" style="display: none;">
                        <div class="form-group">
                            <label for="comentario[${phoneCounter}]">Comentario</label>
                            <input class="form-control shadow-sm" name="comentario[${phoneCounter}]" 
                                id="comentario${phoneCounter}">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm mt-4 shadow-sm delete-phone-btn" style="margin-top: 20px !important;">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            `);
            $('#phoneContainer').append(phoneRow);
            phoneCounter++;
            updatePhoneDeleteButtons();
        }

        // Función para actualizar los botones de eliminar teléfonos
        function updatePhoneDeleteButtons() {
            const phoneRows = $('#phoneContainer .row');
            const phoneCount = phoneRows.length;
            
            if (phoneCount <= 1) {
                // Si solo hay un teléfono o ninguno, ocultar todos los botones
                phoneRows.find('.delete-phone-btn').hide();
            } else {
                // Si hay más de un teléfono, mostrar todos los botones
                phoneRows.find('.delete-phone-btn').show();
            }
        }

        // Función para actualizar las etiquetas de los teléfonos
        function updatePhoneLabels() {
            $('#phoneContainer .row').each(function(index) {
                $(this).find('label[for^="telefono"]').text(`Teléfono ${index + 1}`);
                $(this).attr('data-phone-index', index);
            });
        }

        function addAddress() {
            // Construir opciones de departamentos y zonas
            const departamentoOptions = departamentos.map(d => `<option value="${d.id}">${d.departamento}</option>`).join('');
            const zonaOptions = zonas.map(z => `<option value="${z.id}">${z.nombre}</option>`).join('');
            
            const addressRow = $(`
                <div class="card mb-3 direccion-row">
                    <div class="card-header d-flex justify-content-between align-items-center bg-light">
                        <div class="d-flex align-items-center">
                            <h6 class="mb-0 mr-3">Dirección ${addressCounter + 1}</h6>
                            <div class="form-group mb-0 mr-2" style="min-width: 150px;">
                                <select class="form-control form-control-sm shadow-sm" name="tipo_direccion[${addressCounter}]" id="tipo_direccion${addressCounter}">
                                    <option value="principal" ${addressCounter === 0 ? 'selected' : ''}>Principal</option>
                                    <option value="secundario" ${addressCounter > 0 ? 'selected' : ''}>Secundario</option>
                                </select>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-danger shadow-sm delete-direccion-btn" type="button">
                            <i class="fa fa-trash"></i> Eliminar
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Zona y Sucursal para esta dirección -->
                        <div class="row mb-3" style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin: 0 0 15px 0;">
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label for="zona_direccion${addressCounter}"><i class="fas fa-map-marker-alt text-primary mr-1"></i>Zona <span class="text-danger">*</span></label>
                                    <select class="form-control shadow-sm select-zona-direccion" id="zona_direccion${addressCounter}" 
                                            name="zona_direccion[${addressCounter}]" required>
                                        <option value="">Selecciona una zona</option>
                                        ${zonaOptions}
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label for="sucursal_direccion${addressCounter}"><i class="fas fa-building text-primary mr-1"></i>Sucursal <span class="text-danger">*</span></label>
                                    <select class="form-control shadow-sm select-sucursal-direccion" id="sucursal_direccion${addressCounter}" 
                                            name="sucursal_direccion[${addressCounter}]" required>
                                        <option value="">Primero selecciona una zona</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <hr style="border-color: #dee2e6; margin: 0 0 15px 0;">
                        <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="departamento${addressCounter}">Departamento <span class="text-danger">*</span></label>
                            <select class="form-control select-departamento shadow-sm" id="departamento${addressCounter}" 
                                    name="departamento[${addressCounter}]" required>
                                <option value="">Selecciona</option>
                                ${departamentoOptions}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="provincia${addressCounter}">Provincia <span class="text-danger">*</span></label>
                            <select class="form-control select-provincia shadow-sm" id="provincia${addressCounter}" 
                                    name="provincia[${addressCounter}]" required>
                                <option value="">Selecciona</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="distrito${addressCounter}">Distrito <span class="text-danger">*</span></label>
                            <select class="form-control shadow-sm select-distrito" id="distrito${addressCounter}" 
                                    name="distrito[${addressCounter}]" required>
                                <option value="">Selecciona</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="direccion${addressCounter}">Dirección <span class="text-danger">*</span></label>
                            <input type="text" class="form-control shadow-sm" name="direccion[${addressCounter}]" 
                                id="direccion${addressCounter}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="nLotes${addressCounter}">N° / Lote</label>
                            <input type="text" class="form-control shadow-sm" name="nLotes[${addressCounter}]" 
                                id="nLotes${addressCounter}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="referencia${addressCounter}">Referencia</label>
                            <input type="text" class="form-control shadow-sm" name="referencia[${addressCounter}]" 
                                id="referencia${addressCounter}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="material_inmueble${addressCounter}">Material de Inmueble</label>
                            <select class="form-control shadow-sm" id="material_inmueble${addressCounter}" 
                                    name="material_inmueble[${addressCounter}]">
                                <option value="material_noble">Material Noble</option>
                                <option value="prefabricada">Prefabricada</option>
                                <option value="machimbrado">Machimbrado</option>
                                <option value="otros">Otros</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="cantPisos${addressCounter}">Cantidad de Pisos <span class="text-danger">*</span></label>
                            <input type="number" class="form-control shadow-sm" name="cantPisos[${addressCounter}]" 
                                id="cantPisos${addressCounter}" min="1" max="10" required value="1">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="tipo_residencia${addressCounter}">Titular Domicilio</label>
                            <select class="form-control shadow-sm select-tipo-residencia" id="tipo_residencia${addressCounter}" 
                                    name="tipo_residencia[${addressCounter}]">
                                <option value="Propia">Propia</option>
                                <option value="Familiar">Familiar</option>
                                <option value="Alquilada">Alquilada</option>
                                <option value="Otros">Otros</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="tiempo_residencia${addressCounter}">Tiempo de Residencia <span class="text-danger">*</span></label>
                        <div class="form-group d-flex">
                            <input type="number" class="form-control shadow-sm mr-2" name="tiempo_residencia[${addressCounter}]" 
                                id="tiempo_residencia${addressCounter}" min="1" max="99" style="flex: 2;" required value="1">
                            <select class="form-control shadow-sm" id="anios_meses${addressCounter}" 
                                    name="anios_meses[${addressCounter}]" style="flex: 1;">
                                <option value="meses">Meses</option>
                                <option value="años">Años</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 propietario-fields" style="display: none;">
                        <div class="form-group">
                            <label for="nombre_propietario${addressCounter}">Nombre del Propietario</label>
                            <input type="text" class="form-control shadow-sm" name="nombre_propietario[${addressCounter}]" 
                                id="nombre_propietario${addressCounter}">
                        </div>
                    </div>
                    <div class="col-md-4 propietario-fields" style="display: none;">
                        <div class="form-group">
                            <label for="telefono_propietario${addressCounter}">Teléfono del Propietario</label>
                            <input type="tel" class="form-control shadow-sm" name="telefono_propietario[${addressCounter}]" 
                                id="telefono_propietario${addressCounter}">
                        </div>
                        </div>
                        </div>
                    </div>
                </div>
            `);
            
            // Event listener para cambio de zona (cargar sucursales)
            addressRow.find('.select-zona-direccion').on('change', async function() {
                const zonaId = $(this).val();
                const sucursalSelect = addressRow.find('.select-sucursal-direccion');
                
                if (!zonaId) {
                    sucursalSelect.html('<option value="">Primero selecciona una zona</option>');
                    return;
                }
                
                try {
                    sucursalSelect.html('<option value="">Cargando...</option>').prop('disabled', true);
                    const response = await fetch(`/zona/${zonaId}/sucursales`);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    sucursalSelect.html('<option value="">Selecciona una sucursal</option>' + 
                        data.map(s => `<option value="${s.id}">${s.sucursal}</option>`).join('')
                    ).prop('disabled', false);
                } catch (error) {
                    console.error('Error loading sucursales:', error);
                    sucursalSelect.html('<option value="">Error al cargar sucursales</option>').prop('disabled', false);
                }
            });
            
            // Event listener para cambio de tipo de residencia (mostrar/ocultar campos de propietario)
            addressRow.find('select[name*="tipo_residencia"]').on('change', function() {
                const propietarioFields = addressRow.find('.propietario-fields');
                if ($(this).val() === 'Alquilada') {
                    propietarioFields.show();
                } else {
                    propietarioFields.hide();
                }
            });
            
            $('#direccionesContainer').append(addressRow);
            addressCounter++;
        }

        function addJob() {
            const jobRow = $(`
                <div class="row mb-4 border-bottom pb-3">
                    <div class="col-12 text-right mb-2">
                        <button class="btn btn-sm btn-danger shadow-sm delete-laboral-btn" type="button">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="actividad_economica${jobCounter}">Actividad Económica <span class="text-danger">*</span></label>
                            <select class="form-control shadow-sm" id="actividad_economica${jobCounter}" 
                                    name="actividad_economica[${jobCounter}]">
                                <option value="">Selecciona</option>
                                <option value="Dependiente">Dependiente</option>
                                <option value="Independiente">Independiente</option>
                                <option value="Casa">Casa</option>
                                <option value="Otros">Otros</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nombre_lugar_trabajo${jobCounter}">Nombre del Lugar de Trabajo</label>
                            <input type="text" class="form-control shadow-sm" name="nombre_lugar_trabajo[${jobCounter}]" 
                                id="nombre_lugar_trabajo${jobCounter}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="cargo${jobCounter}">Desempeño o Cargo</label>
                            <input type="text" class="form-control shadow-sm" name="cargo[${jobCounter}]" 
                                id="cargo${jobCounter}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="direccion_trabajo${jobCounter}">Dirección del Trabajo</label>
                            <input type="text" class="form-control shadow-sm" name="direccion_trabajo[${jobCounter}]" 
                                id="direccion_trabajo${jobCounter}">
                        </div>
                    </div>
                </div>
            `);
            $('#laboralesContainer').append(jobRow);
            jobCounter++;
        }

        function addTag() {
            const tagRow = $(`
                <div class="row mb-3 border-bottom pb-3">
                    <div class="col-12 text-right mb-2">
                        <button class="btn btn-sm btn-danger shadow-sm delete-etiqueta-btn" type="button">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="etiqueta${tagCounter}">Etiqueta</label>
                            <select class="form-control shadow-sm select-etiqueta" id="etiqueta${tagCounter}" 
                                    name="etiqueta[${tagCounter}]" required>
                                <option value="">Selecciona</option>
                                ${etiquetas.map(e => `<option value="${e.id}" data-attr="${e.color}">${e.etiqueta}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="color_etiqueta${tagCounter}">Color</label>
                            <input type="color" class="form-control shadow-sm" id="color_etiqueta${tagCounter}" 
                                name="color_etiqueta[${tagCounter}]" value="#000000">
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="form-group">
                            <label for="observacion${tagCounter}">Observación</label>
                            <input class="form-control shadow-sm" name="observacion[${tagCounter}]" 
                                id="observacion${tagCounter}" style="border-left: 8px solid #000000;">
                        </div>
                    </div>
                </div>
            `);
            $('#etiquetasContainer').append(tagRow);
            tagCounter++;
        }

        function addFile() {
            const fileRow = $(`
                <div class="row mb-3 border-bottom pb-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="files_to_upload_${fileCounter}">Archivo</label>
                            <input type="file" class="form-control shadow-sm" name="files_to_upload[]" 
                                id="files_to_upload_${fileCounter}" 
                                accept=".png,.jpg,.jpeg,.gif,.bmp,.webp,.pdf,.doc,.docx">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="descripciones_${fileCounter}">Descripción</label>
                            <input type="text" class="form-control shadow-sm" name="descripciones[]" 
                                id="descripciones_${fileCounter}" placeholder="Describa el contenido del archivo">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-danger mt-4 shadow-sm delete-archivo-btn" type="button">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            `);
            $('#nuevos-archivos-container').append(fileRow);
            fileCounter++;
        }

        // Funciones auxiliares
        function titularDomicilioChange() {
            const row = $(this).closest('.card-body').find('.row');
            const tipoResidencia = $(this).val();
            const isAlquilada = tipoResidencia === 'Alquilada';
            
            row.find('.propietario-fields').toggle(isAlquilada);
        }

        function updateObservationBorder(row) {
            const color = row.find('[id^="color_etiqueta"]').val() || '#000000';
            row.find('[id^="observacion"]').css('border-left', `8px solid ${color}`);
        }

        // Sistema de ubicaciones geográficas
        function initLocationSystem() {
            console.log('Inicializando sistema de ubicaciones...');
            
            // Configurar eventos para departamentos y provincias existentes
            $(document).on('change', '.select-departamento', function() {
                departamentoChange.call(this);
            });
            
            $(document).on('change', '.select-provincia', function() {
                provinciaChange.call(this);
            });
            
            // Inicializar ubicaciones existentes al cargar la página
            $('.direccion-row').each(function(index) {
                const row = $(this);
                const departamentoSelect = row.find('.select-departamento');
                const provinciaSelect = row.find('.select-provincia');
                const distritoSelect = row.find('.select-distrito');
                
                if (departamentoSelect.val() && !provinciaSelect.children().length > 1) {
                    departamentoChange.call(departamentoSelect[0]);
                }
            });
        }

        function departamentoChange() {
            const row = $(this).closest('.row');
            const departamentoId = $(this).val();
            const provinciaSelect = row.find('.select-provincia');
            const distritoSelect = row.find('.select-distrito');
            const initialProvinciaId = provinciaSelect.data('initial');
            
            // Limpiar selects dependientes
            provinciaSelect.html('<option value="">Selecciona</option>');
            distritoSelect.html('<option value="">Selecciona</option>');
            
            if (!departamentoId) return;
            
            // Mostrar indicador de carga
            provinciaSelect.html('<option value="">Cargando...</option>').prop('disabled', true);
            
            // Cargar provincias
            const urls = [
                `/api/departamento/${departamentoId}/provincias`,
                `/admin/clientes/departamento/${departamentoId}/provincias`
            ];
            
            tryLoadData(urls)
                .then(data => {
                    let options = '<option value="">Selecciona</option>';
                    data.forEach(provincia => {
                        const selected = initialProvinciaId && provincia.id == initialProvinciaId ? 'selected' : '';
                        options += `<option value="${provincia.id}" ${selected}>${provincia.nombre ?? 'Sin nombre'}</option>`;
                    });
                    
                    provinciaSelect.html(options).prop('disabled', false);
                    
                    // Si hay una provincia seleccionada, cargar sus distritos
                    if (provinciaSelect.val()) {
                        provinciaChange.call(provinciaSelect[0]);
                    }
                })
                .catch(error => {
                    console.error('Error cargando provincias:', error);
                    provinciaSelect.html('<option value="">Error al cargar</option>').prop('disabled', false);
                });
        }

        function provinciaChange() {
            const row = $(this).closest('.row');
            const provinciaId = $(this).val();
            const distritoSelect = row.find('.select-distrito');
            const initialDistritoId = distritoSelect.data('initial');
            
            // Limpiar select de distritos
            distritoSelect.html('<option value="">Selecciona</option>');
            
            if (!provinciaId) return;
            
            // Mostrar indicador de carga
            distritoSelect.html('<option value="">Cargando...</option>').prop('disabled', true);
            
            // Cargar distritos
            const urls = [
                `/api/provincia/${provinciaId}/distritos`,
                `/admin/clientes/provincia/${provinciaId}/distritos`
            ];
            
            tryLoadData(urls)
                .then(data => {
                    let options = '<option value="">Selecciona</option>';
                    data.forEach(distrito => {
                        const selected = initialDistritoId && distrito.id == initialDistritoId ? 'selected' : '';
                        options += `<option value="${distrito.id}" ${selected}>${distrito.nombre ?? 'Sin nombre'}</option>`;
                    });
                    
                    distritoSelect.html(options).prop('disabled', false);
                })
                .catch(error => {
                    console.error('Error cargando distritos:', error);
                    distritoSelect.html('<option value="">Error al cargar</option>').prop('disabled', false);
                    
                    // Como fallback, agregar el distrito inicial si existe
                    if (initialDistritoId) {
                        const initialText = distritoSelect.data('initial-text') || `Distrito ID: ${initialDistritoId}`;
                        distritoSelect.append(`<option value="${initialDistritoId}" selected>${initialText}</option>`);
                    }
                });
        }

        async function tryLoadData(urls) {
            const token = $('meta[name="csrf-token"]').attr('content');
            
            for (const url of urls) {
                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    
                    const data = await response.json();
                    return data;
                } catch (error) {
                    console.warn(`Error con URL ${url}:`, error);
                }
            }
            
            throw new Error('Todas las URLs fallaron');
        }

        // Función para cargar datos iniciales de ubicaciones
        function loadInitialLocationData() {
            console.log('Cargando datos iniciales de ubicaciones...');
            
            // Cargar provincias y distritos para todas las direcciones existentes
            $('.direccion-row').each(function(index) {
                const row = $(this);
                const departamentoSelect = row.find('.select-departamento');
                const provinciaSelect = row.find('.select-provincia');
                const distritoSelect = row.find('.select-distrito');
                
                const departamentoId = departamentoSelect.val();
                const initialProvinciaId = provinciaSelect.data('initial');
                const initialDistritoId = distritoSelect.data('initial');
                
                console.log(`Dirección ${index}:`, {
                    departamento: departamentoId,
                    provinciaInicial: initialProvinciaId,
                    distritoInicial: initialDistritoId
                });
                
                if (departamentoId) {
                    // Cargar provincias
                    departamentoChange.call(departamentoSelect[0]);
                    
                    // Esperar un poco y luego cargar distritos si hay provincia inicial
                    setTimeout(() => {
                        if (initialProvinciaId && provinciaSelect.find(`option[value="${initialProvinciaId}"]`).length > 0) {
                            provinciaSelect.val(initialProvinciaId);
                            provinciaChange.call(provinciaSelect[0]);
                            
                            // Cargar distrito después de cargar provincias
                            setTimeout(() => {
                                if (initialDistritoId && distritoSelect.find(`option[value="${initialDistritoId}"]`).length > 0) {
                                    distritoSelect.val(initialDistritoId);
                                    console.log(`Distrito ${initialDistritoId} seleccionado para dirección ${index}`);
                                }
                            }, 300);
                        }
                    }, 500);
                }
            });
            
            console.log('Datos iniciales de ubicaciones cargados');
        }

        // Función para consultar DNI del cónyuge
        function consultarDNI() {
            const dni = $('#conyuge_dni').val();
            const clienteDni = $('#nDocumento').val();
            
            if (!dni) {
                showValidationError('Ingrese un número de DNI.');
                return;
            }
            
            if (dni.length !== 8) {
                showValidationError('El DNI debe tener 8 dígitos.');
                return;
            }
            
            if (dni === clienteDni) {
                showValidationError('El DNI del cónyuge no puede ser igual al del cliente.');
                return;
            }
            
            $('#dni-loading').show();
            $('#conyuge_dni').removeClass('is-valid is-invalid');
            
            $.ajax({
                url: "{{ route('consultar.dniconyuge') }}",
                method: "POST",
                headers: { 
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: { dni },
                success: function(data) {
                    if (data.valid) {
                        $('#conyuge_dni').addClass('is-valid');
                        $('#conyuge_nombre').val(data.data.nombres || '');
                        $('#conyuge_apellido_pat').val(data.data.apellido_paterno || '');
                        $('#conyuge_apellido_mat').val(data.data.apellido_materno || '');
                        
                        Swal.fire({
                            title: 'DNI encontrado',
                            text: 'Los datos del cónyuge se han cargado correctamente.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        $('#conyuge_dni').addClass('is-invalid');
                        showValidationError('DNI no encontrado o inválido.');
                    }
                },
                error: function(xhr) {
                    console.error('Error al consultar DNI:', xhr);
                    $('#conyuge_dni').addClass('is-invalid');
                    
                    let errorMessage = 'Error al consultar el DNI.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    showValidationError(errorMessage);
                },
                complete: function() {
                    $('#dni-loading').hide();
                }
            });
        }

        // NOTE: Global zona/sucursal handlers removed - each address now has its own zona/sucursal selectors

        // Validación y envío del formulario
        function initFormValidation() {
            $('#multi-step-form').on('submit', function(e) {
                e.preventDefault();
                
                // Rehabilitar todos los campos required antes de enviar
                $('.form-step input[data-required="true"], .form-step select[data-required="true"]').each(function() {
                    $(this).prop('required', true);
                });
                
                // Validar paso actual
                const currentStep = parseInt($('.form-step:not(.d-none)').attr('id').split('-')[1]);
                if (!validateStep(currentStep)) {
                    return false;
                }
                
                // Validaciones adicionales
                if (!validateFormCompleteness()) {
                    return false;
                }
                
                // Confirmar envío
                Swal.fire({
                    title: '¿Desea guardar los cambios?',
                    text: 'Se actualizarán todos los datos del cliente.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, actualizar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#1A3C6D',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Mostrar loading
                        Swal.fire({
                            title: 'Actualizando cliente',
                            text: 'Por favor espere...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // Enviar formulario
                        this.submit();
                    }
                });
            });
        }

        function validateFormCompleteness() {
            // Verificar que hay al menos un teléfono
            if ($('#phoneContainer .row').length === 0) {
                showValidationError('Debe tener al menos un teléfono.');
                goToStep(1);
                return false;
            }
            
            // Verificar que hay al menos una dirección
            if ($('#direccionesContainer .direccion-row').length === 0) {
                showValidationError('Debe tener al menos una dirección.');
                goToStep(2);
                return false;
            }
            
            return true;
        }

        // Función global para consulta DNI (llamada desde el HTML)
        window.consultarDNI = consultarDNI;

        // Manejo de redimensionamiento de ventana
        $(window).on('resize', function() {
            const currentStep = parseInt($('.form-step:not(.d-none)').attr('id').split('-')[1]);
            updateStepProgress(currentStep);
        });

        // Limpiar validaciones al cambiar valores
        $(document).on('input change', '.form-control, .form-select', function() {
            $(this).removeClass('is-invalid is-valid');
        });

        // Prevenir envío accidental del formulario
        $(document).on('keypress', 'input', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                
                // Si estamos en un paso que no es el último, ir al siguiente
                const currentStep = parseInt($('.form-step:not(.d-none)').attr('id').split('-')[1]);
                if (currentStep < totalSteps) {
                    $('.next-step').click();
                }
            }
        });

    </script>
@stop