@extends('layouts.admin')

@section('title', 'Crear Cliente')

@section('content_header')
    <div class="card-header" style="background: linear-gradient(135deg, #1A3C6D, #2E5A9A); color: #fff; padding: 1.5rem 2rem; position: relative;">
        <h1 class="mb-0" style="font-size: 1.5rem; font-weight: 500;">Crear Cliente</h1>
    </div>
@stop

@section('content')
    @if (session('status'))
        <div class="alert {{ session('error_message') ? 'alert-danger' : 'alert-success' }}" role="alert">
            {{ session('status') }}{{ session('error_message') ? '. ' . session('error_message') . '.' : '.' }}
        </div>
    @endif

    <div class="container-fluid p-0">
        <div class="cliente-form">
            <div class="form-header">
                <!--1h5>Datos del Cliente</h5-->
                <div id="step-progress"></div>
            </div>
            
            <div class="form-body">
                <form id="multi-step-form" action="{{ route('admin.clientes.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @if ($persona)
                        <input type="hidden" name="persona_id" value="{{ $persona->id }}">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            Convirtiendo persona existente en cliente: <strong>{{ $persona->nombres }} {{ $persona->ape_pat }} {{ $persona->ape_mat }}</strong>
                        </div>
                    @endif

                    <!-- Paso 1: Datos del Cliente -->
                    <div id="step-1" class="form-step" data-step-name="Cliente">
                        <!--div class="row justify-content-center mb-4">
                            <div class="col-12 text-center">
                                <img src="{{ asset('storage/img/clientes_img/userDefaultPhoto.png') }}"
                                    class="rounded-circle mx-auto d-block shadow-sm" id="img" alt="userPhoto" 
                                    style="height: 130px; width: 130px; object-fit: cover; border: 4px solid #2E5A9A;">
                                <div class="form-group mt-3">
                                    <label for="file" class="btn btn-outline-primary btn-sm shadow-sm">
                                        <i class="fa fa-upload mr-1"></i> Subir Foto
                                    </label>
                                    <input id="file" type="file" name="file" style="display: none;" accept="image/*">
                                </div>
                            </div>
                        </div-->

                        <!--h6 class="font-weight-bold text-muted mb-3">Datos Personales</h6-->
                        <hr style="border-color: #2E5A9A; margin-bottom: 2rem;">
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nDocumento">Número de DNI <span class="text-danger">*</span></label>
                                    <div class="input-group" style="border-radius: 7px 0px 0px 7px!important;">
                                        <input class="form-control shadow-sm" name="nDocumento" id="nDocumento" 
                                               value="{{ old('nDocumento', $persona->documento ?? '') }}" required maxlength="8" pattern="[0-9]{8}"
                                               {{ $persona ? 'readonly' : '' }}>
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
                                           value="{{ old('nombres', $persona->nombres ?? '') }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="aPaterno">Apellido Paterno <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control shadow-sm" name="aPaterno" id="aPaterno" 
                                           value="{{ old('aPaterno', $persona->ape_pat ?? '') }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="aMaterno">Apellido Materno <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control shadow-sm" name="aMaterno" id="aMaterno" 
                                           value="{{ old('aMaterno', $persona->ape_mat ?? '') }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_nacimiento">Fecha de Nacimiento <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control shadow-sm" name="fecha_nacimiento" id="fecha_nacimiento" 
                                           value="{{ old('fecha_nacimiento', $persona->fecha_nacimiento ?? '') }}" required>
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
                                    <select class="form-control shadow-sm" id="estado_civil" name="estado_civil" required>
                                        <option value="Soltero" {{ old('estado_civil') == 'Soltero' ? 'selected' : '' }}>Soltero</option>
                                        <option value="Casado" {{ old('estado_civil') == 'Casado' ? 'selected' : '' }}>Casado</option>
                                        <option value="Conviviente" {{ old('estado_civil') == 'Conviviente' ? 'selected' : '' }}>Conviviente</option>
                                        <option value="Divorciado" {{ old('estado_civil') == 'Divorciado' ? 'selected' : '' }}>Divorciado</option>
                                        <option value="Viudo" {{ old('estado_civil') == 'Viudo' ? 'selected' : '' }}>Viudo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control shadow-sm" name="email" id="email" 
                                           value="{{ old('email') }}">
                                </div>
                            </div>
                        </div>

                        <h6 class="font-weight-bold text-muted mt-4 mb-3">Teléfonos</h6>
                        <hr style="border-color: #2E5A9A; margin-bottom: 2rem;">
                        
                        <button type="button" class="btn btn-sm btn-primary shadow-sm mb-3" id="addPhoneButton">
                            <i class="fas fa-plus mr-1"></i> Agregar Teléfono
                        </button>
                        
                        <div id="phoneContainer" class="mb-2">
                            {{-- Campo telefónico por defecto --}}
                            <div class="row d-flex align-items-center mb-3" data-phone-index="0">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="telefono0">Teléfono 1</label>
                                        <input type="tel" class="form-control shadow-sm" name="telefono[0]" 
                                            id="telefono0" value="{{ old('telefono.0') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Tipo</label>
                                        <div class="btn-group-toggle d-flex" data-toggle="buttons" style="margin-bottom: -10px !important;">
                                            <label class="btn btn-outline-primary {{ old('tipo.0') == 'celular' || old('tipo.0') == null ? 'active' : '' }}">
                                                <input type="radio" name="tipo[0]" value="celular" {{ old('tipo.0') == 'celular' || old('tipo.0') == null ? 'checked' : '' }}> Celular
                                            </label>
                                            <label class="btn btn-outline-primary {{ old('tipo.0') == 'otro' ? 'active' : '' }}">
                                                <input type="radio" name="tipo[0]" value="otro" {{ old('tipo.0') == 'otro' ? 'checked' : '' }}> Otro
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5" style="{{ old('tipo.0') == 'otro' ? '' : 'display: none;' }}">
                                    <div class="form-group">
                                        <label for="descripcion0">Descripción</label>
                                        <input type="text" class="form-control shadow-sm" name="descripcion[0]" 
                                            id="descripcion0" value="{{ old('descripcion.0') }}" 
                                            placeholder="Ej: Casa, Trabajo, etc.">
                                    </div>
                                </div>
                                <div class="col-md-1 d-flex justify-content-center">
                                    <button type="button" class="btn btn-danger btn-sm removePhone" title="Eliminar teléfono">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            {{-- Teléfonos adicionales se agregarán dinámicamente con JavaScript --}}
                        </div>

                        <div id="seccion-conyuge" style="display: none;">
                            <h6 class="font-weight-bold text-muted mt-4 mb-3">Datos Familiares / Cónyuge</h6>
                            <hr style="border-color: #2E5A9A; margin-bottom: 2rem;">
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="conyuge_dni">DNI</label>
                                    <div class="input-group">
                                        <input type="tel" class="form-control shadow-sm" name="conyuge_dni" id="conyuge_dni"
                                            value="{{ old('conyuge_dni') }}"
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
                                            value="{{ old('conyuge_nombre') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="conyuge_apellido_pat">Apellido Paterno</label>
                                        <input type="text" class="form-control shadow-sm" name="conyuge_apellido_pat" id="conyuge_apellido_pat" 
                                            value="{{ old('conyuge_apellido_pat') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="conyuge_apellido_mat">Apellido Materno</label>
                                        <input type="text" class="form-control shadow-sm" name="conyuge_apellido_mat" id="conyuge_apellido_mat" 
                                            value="{{ old('conyuge_apellido_mat') }}">
                                    </div>
                                </div>
                            
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="carga_familiar">Carga Familiar</label>
                                        <input type="number" class="form-control shadow-sm" name="carga_familiar" id="carga_familiar" 
                                            value="{{ old('carga_familiar', 0) }}" min="0">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="conyuge_actividad">Oficio / Profesión</label>
                                        <input type="text" class="form-control shadow-sm" name="conyuge_actividad" id="conyuge_actividad" 
                                            value="{{ old('conyuge_actividad') }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="conyuge_telefono">Teléfono</label>
                                        <input type="tel" class="form-control shadow-sm phone-input" name="conyuge_telefono" id="conyuge_telefono" 
                                            maxlength="9" pattern="[0-9]{9}" placeholder="Ej: 987654321" 
                                            title="Ingrese exactamente 9 dígitos numéricos" value="{{ old('conyuge_telefono') }}">
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="conyuge_direccion_trabajo">Dirección de Trabajo</label>
                                        <input type="text" class="form-control shadow-sm" name="conyuge_direccion_trabajo" id="conyuge_direccion_trabajo" 
                                            value="{{ old('conyuge_direccion_trabajo') }}">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="ref_conyuge_direccion_trabajo">Referencia Dirección de Trabajo</label>
                                        <input type="text" class="form-control shadow-sm" name="ref_conyuge_direccion_trabajo" id="ref_conyuge_direccion_trabajo" 
                                            value="{{ old('ref_conyuge_direccion_trabajo') }}">
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
                            {{-- Las direcciones se crearán dinámicamente con JavaScript --}}
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
                                        <label class="btn btn-outline-primary flex-fill">
                                            <input class="tCuenta" type="radio" name="tCuenta" value="{{ $tipoCuenta->id }}" id="tCuenta{{ $tipoCuenta->id }}" autocomplete="off" required> {{ $tipoCuenta->tipo_cuenta }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('tCuenta') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-12 mt-2" id="finanzas-section" hidden>
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
                            <div id="cuentasContainer"></div>
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
                            {{-- Los datos laborales se agregarán dinámicamente --}}
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

                    <!-- Paso 5: Etiquetas 
                    <div id="step-5" class="form-step d-none" data-step-name="Etiquetas">
                        <div class="row d-flex justify-content-between align-items-center mb-3">
                            <h6 class="font-weight-bold text-muted">Etiquetas</h6>
                            <button id="btn-agregar-etiqueta" class="btn btn-sm btn-primary shadow-sm" type="button">
                                <i class="fas fa-plus mr-1"></i> Agregar
                            </button>
                        </div>
                        <hr style="border-color: #2E5A9A; margin-bottom: 2rem;">
                        
                        <div id="etiquetasContainer">
                            {{-- Las etiquetas se agregarán dinámicamente --}}
                        </div>
                        
                        <div class="row justify-content-between mt-4">
                            <button type="button" class="btn btn-lg btn-secondary prev-step shadow-sm">
                                <i class="fas fa-arrow-left mr-2"></i> Regresar
                            </button>
                            <button type="button" class="btn btn-lg btn-primary next-step shadow-sm">
                                Guardar y Continuar <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>-->

                    <!-- Paso 6: Archivos -->
                    <div id="step-5" class="form-step d-none" data-step-name="Archivos">
                        <div class="row d-flex justify-content-between align-items-center mb-3">
                            <h6 class="font-weight-bold text-muted">Archivos</h6>
                            <button id="btn-agregar-archivo" class="btn btn-sm btn-primary shadow-sm" type="button">
                                <i class="fas fa-plus mr-1"></i> Agregar
                            </button>
                        </div>
                        <hr style="border-color: #2E5A9A; margin-bottom: 2rem;">
                        
                        <div id="archivosContainer">
                            {{-- Los documentos se agregarán dinámicamente --}}
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

                    <!-- Paso 7: Finalizar -->
                    <div id="step-6" class="form-step d-none" data-step-name="Finalizar">
                        <div class="row">
                            <div class="col-12 text-center">
                                <div class="mb-4">
                                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                                </div>
                                <h4 class="mb-4 text-primary">Resumen del Cliente</h4>
                                <p class="lead mb-4">Ha completado todos los pasos para crear el nuevo cliente.</p>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Presione "Guardar y Finalizar" para crear el nuevo cliente.
                                </div>
                            </div>
                        </div>
                        
                        <div class="row justify-content-between mt-4">
                            <button type="button" class="btn btn-lg btn-secondary prev-step shadow-sm">
                                <i class="fas fa-arrow-left mr-2"></i> Regresar
                            </button>
                            <button type="submit" class="btn btn-lg btn-success shadow-sm">
                                <i class="fas fa-save mr-1"></i> Guardar y Finalizar
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
            padding: 0.9rem 1rem;
            padding-top: 1.5rem;
            margin-top: 0;
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
        let phoneCounter = 0;
        let accountCounter = 0;
        let addressCounter = 0;
        let jobCounter = 0;
        let tagCounter = 0;
        let fileCounter = 0;
        let currentTipoCuenta = null;

        // Datos para los selects
        const departamentos = {!! json_encode($departamentos) !!};
        const tiposCuenta = {!! json_encode($tiposCuenta) !!};
        const entBancarias = {!! json_encode($entBancarias) !!};
        const sucursales = {!! json_encode($sucursales) !!};
        const etiquetas = {!! json_encode($etiquetas) !!};
        const zonas = {!! json_encode($zonas) !!};

        $(document).ready(function() {
            console.log('Iniciando aplicación...');
            
            // Asegurar visibilidad del sidebar
            $('.main-sidebar').show();
            
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

            // Inicializar contador de teléfonos según los campos existentes (evita índices duplicados)
            phoneCounter = $('#phoneContainer .row').length || 0;
            // Asegurar que los botones y etiquetas estén correctos al cargar
            if (typeof updatePhoneDeleteButtons === 'function') updatePhoneDeleteButtons();
            if (typeof updatePhoneLabels === 'function') updatePhoneLabels();
            
            // Inicializar sistema de ubicaciones
            initLocationSystem();
            
            // Configurar validación del formulario
            initFormValidation();
            
            // Configurar validación de teléfonos
            initPhoneValidation();
            
            // DNI Search functionality
            $('#buscar-dni').click(function() {
                const dni = $('#nDocumento').val();
                if (dni.length !== 8) {
                    alert('Ingrese un DNI válido de 8 dígitos');
                    return;
                }
                
                // Mostrar indicador de carga (sin limpiar campos)
                console.log('Iniciando consulta DNI para:', dni);
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Consultando...');
                
                $.ajax({
                    url: "{{ route('admin.clientes.consultarDNI') }}",
                    method: 'POST',
                    data: {
                        nDocumento: dni,
                        _token: "{{ csrf_token() }}",
                        _timestamp: Date.now() // Evitar cache
                    },
                    cache: false, // Deshabilitar cache del navegador
                    success: function(response) {
                        console.log('Respuesta de la API:', response); // Debug
                        if (response.valid) {
                            if (response.data) {
                                const data = response.data;

                                // Llenar campos básicos
                                $('#nombres').val(data.nombres || '');
                                $('#aPaterno').val(data.apellido_paterno || '');
                                $('#aMaterno').val(data.apellido_materno || '');

                                // Fecha de nacimiento
                                if (data.fecha_nacimiento) {
                                    let fechaNacimiento = formatearFechaParaInput(data.fecha_nacimiento);
                                    $('#fecha_nacimiento').val(fechaNacimiento);
                                    calcularEdad(fechaNacimiento);
                                }

                                // Dirección
                                if (data.direccion || data.direccion_completa) {
                                    crearDireccionDesdeAPI(data);
                                }

                                // Mostrar notificación según el origen de los datos
                                if (data.persona_existe === true) {
                                    // La persona ya existe en la base de datos
                                    Swal.fire({
                                        icon: 'info',
                                        title: 'DNI ya registrado',
                                        html: `<p>Este DNI ya se encuentra registrado en el sistema:</p>
                                               <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 10px;">
                                                   <strong style="font-size: 1.1em; color: #2E5A9A;">${data.nombres} ${data.apellido_paterno} ${data.apellido_materno}</strong><br>
                                                   <span style="color: #6c757d;">DNI: ${dni}</span>
                                               </div>
                                               <p style="margin-top: 15px; color: #28a745;">✓ Los datos han sido cargados automáticamente</p>`,
                                        confirmButtonText: 'Continuar',
                                        confirmButtonColor: '#2E5A9A'
                                    });
                                } else {
                                    // Datos obtenidos de RENIEC
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'DNI encontrado en RENIEC',
                                        html: `<div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 10px;">
                                                   <strong style="font-size: 1.1em; color: #2E5A9A;">${data.nombres} ${data.apellido_paterno} ${data.apellido_materno}</strong><br>
                                                   <span style="color: #6c757d;">DNI: ${dni}</span>
                                               </div>
                                               <p style="margin-top: 15px; color: #28a745;">✓ Los datos han sido cargados automáticamente</p>`,
                                        confirmButtonText: 'Continuar',
                                        confirmButtonColor: '#28a745',
                                        timer: 3000
                                    });
                                }

                                // También mostrar burbuja para futuro
                                window.dniDataTemp = response.data;
                                mostrarBurbujaDNI(response.data);
                            }
                        } else {
                            // Verificar si ya es cliente
                            if (response.error === 'already_registered') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Cliente ya registrado',
                                    text: 'Este DNI ya está registrado como cliente en el sistema.',
                                    confirmButtonText: 'Entendido',
                                    confirmButtonColor: '#dc3545'
                                });
                            } else {
                                // NO BLOQUEAR - Solo mostrar advertencia informativa
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'DNI no encontrado',
                                    text: 'No se encontró información en RENIEC ni en la base de datos. Podrás ingresar los datos manualmente.',
                                    confirmButtonText: 'Entendido',
                                    confirmButtonColor: '#3085d6'
                                });
                            }
                        }
                    },
                    error: function(xhr) {
                        alert('Error en la consulta: ' + xhr.responseText);
                    },
                    complete: function() {
                        // Restaurar botón después de la consulta (exitosa o fallida)
                        $('#buscar-dni').prop('disabled', false).html('<i class="fas fa-search"></i>');
                    }
                });
            });
            
            // Control de visibilidad de sección cónyuge
            function toggleSeccionConyuge() {
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
                
                // Limpiar cuentas existentes al cambiar tipo
                $('#cuentasContainer').empty();
                
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
                } else {
                    // Con cuentas - mostrar botones
                    $('#addBankAccountButton, #addDigitalWalletButton').show();
                }
            });
            
            console.log('Aplicación inicializada correctamente');
        });

        // Función para crear dirección desde datos de la API
        function crearDireccionDesdeAPI(data) {
            try {
                console.log('Intentando crear dirección desde API con datos:', data);
                
                // Verificar si ya existe una dirección para evitar duplicados
                const direccionesExistentes = $('#direccionesContainer .direccion-row');
                if (direccionesExistentes.length > 0) {
                    console.log('Ya existe una dirección, actualizando la primera...');
                    // Actualizar la dirección existente en lugar de no hacer nada
                    const direccionActual = direccionesExistentes.first();
                    actualizarDireccionExistente(direccionActual, data);
                    return;
                }
                
                console.log('No existe dirección, creando nueva...');
                
                const direccionTexto = data.direccion_completa || data.direccion || '';
                console.log('Dirección a procesar:', direccionTexto);
                
                if (!direccionTexto) {
                    console.log('No hay datos de dirección para procesar');
                    return;
                }
                
                // Parsear la dirección completa
                const direccionParseada = parsearDireccionCompleta(direccionTexto);
                console.log('Dirección parseada:', direccionParseada);
                
                if (!direccionParseada) {
                    console.log('No se pudo parsear la dirección');
                    return;
                }
                
                // Crear una nueva dirección usando la función existente
                addAddress();
                
                // Obtener la última dirección creada
                const ultimaDireccion = $('#direccionesContainer .direccion-row').last();
                
                if (ultimaDireccion.length > 0) {
                    console.log('Llenando campos de la nueva dirección...');
                    // Llenar los campos con la dirección parseada
                    ultimaDireccion.find('input[name*="direccion"]').val(direccionParseada.direccion);
                    ultimaDireccion.find('input[name*="nLotes"]').val(direccionParseada.numeroLote);
                    // Poblar referencia con información de ubicación detectada
                    ultimaDireccion.find('input[name*="referencia"]').val(direccionParseada.referencia || '');
                    
                    // Si tenemos ubigeo o nombres de ubicación, intentar llenarlos
                    if (data.departamento) {
                        // Buscar departamento por nombre
                        const departamentoSelect = ultimaDireccion.find('select[name*="departamento"]');
                        departamentoSelect.find('option').each(function() {
                            if ($(this).text().toLowerCase().includes(data.departamento.toLowerCase())) {
                                departamentoSelect.val($(this).val()).trigger('change');
                                return false; // break
                            }
                        });
                        
                        // Esperar un momento para que se carguen las provincias, luego buscar
                        setTimeout(() => {
                            if (data.provincia) {
                                const provinciaSelect = ultimaDireccion.find('select[name*="provincia"]');
                                provinciaSelect.find('option').each(function() {
                                    if ($(this).text().toLowerCase().includes(data.provincia.toLowerCase())) {
                                        provinciaSelect.val($(this).val()).trigger('change');
                                        return false;
                                    }
                                });
                                
                                // Esperar para cargar distritos
                                setTimeout(() => {
                                    if (data.distrito) {
                                        const distritoSelect = ultimaDireccion.find('select[name*="distrito"]');
                                        distritoSelect.find('option').each(function() {
                                            if ($(this).text().toLowerCase().includes(data.distrito.toLowerCase())) {
                                                distritoSelect.val($(this).val());
                                                return false;
                                            }
                                        });
                                    }
                                }, 500);
                            }
                        }, 500);
                    }
                    
                    // Si tenemos ubigeo, intentar usarlo para obtener la ubicación exacta
                    if (data.ubigeo && data.ubigeo.length === 6) {
                        console.log('Procesando ubigeo:', data.ubigeo);
                        // El ubigeo peruano: 2 dígitos departamento + 2 provincia + 2 distrito
                        const depCod = data.ubigeo.substring(0, 2);
                        const provCod = data.ubigeo.substring(0, 4);
                        const distCod = data.ubigeo;
                        
                        // Aquí podrías implementar lógica para buscar por ubigeo si tu base de datos lo soporta
                        console.log('Códigos extraídos - Dep:', depCod, 'Prov:', provCod, 'Dist:', distCod);
                    }
                    
                    console.log('Dirección creada exitosamente desde la API con datos parseados:', {
                        direccion: direccionParseada.direccion,
                        numeroLote: direccionParseada.numeroLote,
                        ubicacion: direccionParseada.ubicacionGeografica
                    });
                } else {
                    console.warn('No se pudo crear la dirección automáticamente');
                }
                
            } catch (error) {
                console.error('Error al crear dirección desde API:', error);
            }
        }

        // Función para parsear direcciones completas y separarlas en componentes
        function parsearDireccionCompleta(direccionCompleta) {
            if (!direccionCompleta) return null;
            
            // Limpiar la dirección
            let direccion = direccionCompleta.trim();
            
            // Remover información de ubicación geográfica al final (ej: "LIMA - HUAURA - VEGUETA")
            // Buscar el patrón de ubicación con guiones (puede tener comas dobles)
            const patronUbicacion = /,+\s*([A-ZÁÉÍÓÚÑ\s]+\s*-\s*[A-ZÁÉÍÓÚÑ\s]+\s*-\s*[A-ZÁÉÍÓÚÑ\s]+)\s*$/i;
            const matchUbicacion = direccion.match(patronUbicacion);
            
            if (matchUbicacion) {
                // Remover la ubicación geográfica de la dirección
                direccion = direccion.replace(patronUbicacion, '').trim();
                // Remover comas al final si quedaron
                direccion = direccion.replace(/,+$/, '').trim();
            }
            
            let numeroLote = '';
            let direccionLimpia = direccion;
            
            // Buscar S/N específicamente (puede aparecer al final o en el medio)
            const matchSN = direccion.match(/\b(S\/N|SN)\b/gi);
            if (matchSN) {
                numeroLote = 'S/N';
                // Remover S/N de la dirección principal
                direccionLimpia = direccion.replace(/\b(S\/N|SN)\b/gi, '').trim();
                // Limpiar espacios múltiples y comas consecutivas
                direccionLimpia = direccionLimpia.replace(/\s+/g, ' ').replace(/,+/g, ',').replace(/,\s*,/g, ',');
                direccionLimpia = direccionLimpia.replace(/^,+|,+$/g, '').trim();
            } else {
                // Buscar patrones complejos de número/lote (como Q-7-A, MZ A LT 5, etc.)
                const patronesNumeroComplejos = [
                    // Patrones complejos con guiones y letras (Q-7-A, B-12-C, etc.)
                    /\b([A-Z]+-\d+-[A-Z]+)\b/gi,
                    /\b([A-Z]+-\d+)\b/gi,
                    // Manzana y lote juntos
                    /\b(MZ\.?\s*[A-Z0-9]+\s+LT\.?\s*[A-Z0-9]+)\b/gi,
                    // Patrones individuales
                    /\b(MZ\.?\s*[A-Z0-9]+)\b/gi,
                    /\b(LT\.?\s*[A-Z0-9]+)\b/gi,
                    /\b(NRO\.?\s*[A-Z0-9]+)\b/gi,
                    // Códigos alfanuméricos al final (como último recurso)
                    /\b([A-Z]\d+[A-Z]?)\b(?=\s+[A-Z]{2,}|\s*$)/gi,
                    // Números simples al final (como último recurso)
                    /\b(\d+)\b(?=\s+[A-Z]{2,}|\s*$)/g
                ];
                
                for (const patron of patronesNumeroComplejos) {
                    const matches = direccion.match(patron);
                    if (matches && matches.length > 0) {
                        // Tomar el match más largo (más específico)
                        const matchMasLargo = matches.reduce((a, b) => a.length > b.length ? a : b);
                        numeroLote = matchMasLargo;
                        
                        // Remover el patrón encontrado de la dirección
                        direccionLimpia = direccion.replace(new RegExp('\\b' + matchMasLargo.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'gi'), '').trim();
                        
                        // Limpiar espacios múltiples y comas consecutivas
                        direccionLimpia = direccionLimpia.replace(/\s+/g, ' ').replace(/,+/g, ',').replace(/,\s*,/g, ',');
                        direccionLimpia = direccionLimpia.replace(/^,+|,+$/g, '').trim();
                        
                        break;
                    }
                }
            }
            
            // Después de extraer el número/lote, separar la dirección principal de la referencia
            let referencia = '';
            
            // Buscar patrones que indican referencias/ubicación adicional
            const patronesReferencia = [
                /\b(BARRIO\s+[A-ZÁÉÍÓÚÑ\s]+)$/gi,
                /\b(SECTOR\s+[A-ZÁÉÍÓÚÑ\s]+)$/gi,
                /\b(URB\.\s+[A-ZÁÉÍÓÚÑ\s]+)$/gi,
                /\b(URBANIZACIÓN\s+[A-ZÁÉÍÓÚÑ\s]+)$/gi,
                /\b(PUEBLO\s+JOVEN\s+[A-ZÁÉÍÓÚÑ\s]+)$/gi,
                /\b(ASENTAMIENTO\s+HUMANO\s+[A-ZÁÉÍÓÚÑ\s]+)$/gi,
                /\b(AA\.HH\.\s+[A-ZÁÉÍÓÚÑ\s]+)$/gi,
                /\b(COOPERATIVA\s+[A-ZÁÉÍÓÚÑ\s]+)$/gi,
                /\b(RESIDENCIAL\s+[A-ZÁÉÍÓÚÑ\s]+)$/gi
            ];
            
            for (const patronRef of patronesReferencia) {
                const matchRef = direccionLimpia.match(patronRef);
                if (matchRef) {
                    referencia = matchRef[0].trim();
                    direccionLimpia = direccionLimpia.replace(patronRef, '').trim();
                    direccionLimpia = direccionLimpia.replace(/\s+/g, ' ').replace(/,+$/, '').trim();
                    break;
                }
            }
            
            console.log('Dirección parseada:', {
                original: direccionCompleta,
                direccion: direccionLimpia,
                numeroLote: numeroLote,
                referencia: referencia,
                ubicacion: matchUbicacion ? matchUbicacion[1] : null
            });
            
            return {
                direccion: direccionLimpia,
                numeroLote: numeroLote,
                referencia: referencia, // Auto-detectada pero el asesor puede modificarla
                ubicacionGeografica: matchUbicacion ? matchUbicacion[1] : null
            };
        }

        // Función para actualizar dirección existente
        function actualizarDireccionExistente(direccionExistente, data) {
            try {
                console.log('Actualizando dirección existente con datos:', data);
                
                const direccionTexto = data.direccion_completa || data.direccion || '';
                if (!direccionTexto) {
                    console.log('No hay datos de dirección para actualizar');
                    return;
                }
                
                // Parsear la dirección completa
                const direccionParseada = parsearDireccionCompleta(direccionTexto);
                if (!direccionParseada) {
                    console.log('No se pudo parsear la dirección para actualizar');
                    return;
                }
                
                // Actualizar los campos con la dirección parseada
                direccionExistente.find('input[name*="direccion"]').val(direccionParseada.direccion);
                direccionExistente.find('input[name*="nLotes"]').val(direccionParseada.numeroLote);
                direccionExistente.find('input[name*="referencia"]').val(direccionParseada.referencia || '');
                
                console.log('Dirección existente actualizada correctamente');
                
            } catch (error) {
                console.error('Error al actualizar dirección existente:', error);
            }
        }

        // Función para formatear fecha para input[type="date"]
        function formatearFechaParaInput(fecha) {
            if (!fecha) return '';
            
            try {
                // Convertir a string si viene como número o objeto
                fecha = String(fecha).trim();
                
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
                let fechaObj = new Date(fecha);
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
                    // Validar teléfonos
                    if (valid && $('#phoneContainer .row').length === 0) {
                        showValidationError('Debe agregar al menos un teléfono.');
                        valid = false;
                    }
                    // Validar formato de teléfonos
                    if (valid) {
                        valid = validatePhoneFields('#step-1');
                    }
                    break;
                case 2: // Datos de residencia
                    // Validar direcciones
                    if ($('#direccionesContainer .direccion-row').length === 0) {
                        showValidationError('Debe agregar al menos una dirección.');
                        valid = false;
                    }
                    
                    // Validar zona y sucursal en cada dirección
                    if (valid) {
                        $('#direccionesContainer .direccion-row').each(function(index) {
                            const zonaSelect = $(this).find('.select-zona-direccion');
                            const sucursalSelect = $(this).find('.select-sucursal-direccion');
                            
                            if (!zonaSelect.val() || zonaSelect.val() === '') {
                                zonaSelect.addClass('is-invalid');
                                showValidationError(`Debe seleccionar una zona para la Dirección ${index + 1}.`);
                                valid = false;
                                return false; // break
                            }
                            
                            if (!sucursalSelect.val() || sucursalSelect.val() === '') {
                                sucursalSelect.addClass('is-invalid');
                                showValidationError(`Debe seleccionar una sucursal para la Dirección ${index + 1}.`);
                                valid = false;
                                return false; // break
                            }
                        });
                    }
                    
                    // Validar campos requeridos dentro de las direcciones
                    if (valid) {
                        valid = validateRequiredFields('#step-2 .direccion-row input[required], #step-2 .direccion-row select[required]');
                    }
                    
                    // Validar formato de teléfonos en paso 2
                    if (valid) {
                        valid = validatePhoneFields('#step-2');
                    }
                    break;
                case 3: // Datos bancarios
                    // Validar tipo de cuenta
                    const tiposCuentaSeleccionados = $('input[name="tCuenta"]:checked');
                    if (tiposCuentaSeleccionados.length === 0) {
                        showValidationError('Debe seleccionar un tipo de cuenta.');
                        valid = false;
                    } else {
                        const tipoCuentaSeleccionado = parseInt(tiposCuentaSeleccionados.val());
                        
                        // Si es tipo 2 (propias) o 3 (de terceros), validar que tenga cuentas
                        if ((tipoCuentaSeleccionado === 2 || tipoCuentaSeleccionado === 3) && 
                            $('#cuentasContainer .cuenta-row').length === 0) {
                            const tipoCuentaTexto = tipoCuentaSeleccionado === 2 ? 'propias' : 'de terceros';
                            showValidationError(`Debe agregar al menos una cuenta bancaria o billetera digital para cuentas ${tipoCuentaTexto}.`);
                            valid = false;
                        }
                    }
                    
                    if (valid) {
                        valid = validateRequiredFields('#step-3 input[required], #step-3 select[required]');
                    }
                    break;
                /*case 4: // Datos laborales - validar que exista al menos uno
                    if ($('#laboralesContainer .row').length === 0) {
                        showValidationError('Debe agregar al menos un dato laboral.');
                        valid = false;
                    } else {
                        valid = validateRequiredFields('#step-4 input[required], #step-4 select[required]');
                    }
                    break;*/
                case 5: // Etiquetas
                    valid = validateRequiredFields('#step-5 select[required]');
                    break;
            }
            
            return valid;
        }

        function validateRequiredFields(selector) {
            let valid = true;
            
            $(selector).each(function() {
                if (!$(this).val() || $(this).val().trim() === '') {
                    $(this).addClass('is-invalid');
                    valid = false;
                } else {
                    $(this).removeClass('is-invalid').addClass('is-valid');
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
                
                // Si no quedan teléfonos, resetear el contador
                if ($('#phoneContainer .row').length === 0) {
                    phoneCounter = 0;
                }
                
                updatePhoneDeleteButtons();
                updatePhoneLabels();
            });
            $(document).on('change', '#phoneContainer input[type="radio"]', function() {
                const row = $(this).closest('.row');
                const isOther = $(this).val() === 'otro';
                row.find('.col-md-5').toggle(isOther);
            });

            // Eventos para cuentas bancarias
            $('#btn-agregar-cuenta').on('click', addAccount);
            $(document).on('click', '.delete-cuenta-btn', function() {
                $(this).closest('.row').remove();
            });
            $(document).on('change', '#cuentasContainer input[type="radio"]', function() {
                tipoCuentaChange.call(this);
            });

            // Eventos para direcciones
            $('#btn-agregar-direccion').on('click', addAddress);
            $(document).on('click', '.delete-direccion-btn', function() {
                $(this).closest('.row').remove();
            });
            $(document).on('change', '.select-tipo-residencia', function() {
                titularDomicilioChange.call(this);
            });

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
        }

        // Función para crear cuenta bancaria
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
                                        <select class="form-control" name="cuentas[${tipo}][bancarias][${index}][entidad_id]" required>
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
                                        <input type="text" class="form-control" name="cuentas[${tipo}][bancarias][${index}][numero_cuenta]" placeholder="Ingrese el número de cuenta" required>
                                    </div>
                                </div>
                                ${currentTipoCuenta == 3 ? `
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Titular de la Cuenta</label>
                                        <input type="text" class="form-control" name="cuentas[${tipo}][bancarias][${index}][titular]" placeholder="Nombre completo del titular" required>
                                    </div>
                                </div>` : ''}
                            </div>
                            <input type="hidden" name="cuentas[${tipo}][bancarias][${index}][tipo_cuenta_id]" value="${currentTipoCuenta}">
                        </div>
                    </div>`
                );
                
                accountRow.find('.remove-account').on('click', function() {
                    accountRow.remove();
                    updateAccountIndexes();
                });
                
                $('#cuentasContainer').append(accountRow);
            }

            // Función para crear billetera digital
            function createDigitalWalletRow(index) {
                const tipoCuentaText = currentTipoCuenta == 2 ? 'Propia' : 'de Terceros';
                const tipo = currentTipoCuenta == 2 ? 'propia' : 'terceros';
                
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
                                        <select class="form-control tipo-billetera" name="cuentas[${tipo}][digitales][${index}][billetera_id]" required>
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
                                        <input type="text" class="form-control numero-billetera" name="cuentas[${tipo}][digitales][${index}][numero_telefono]" 
                                               placeholder="Ingrese el número de teléfono" maxlength="9" pattern="9[0-9]{8}" required>
                                        <small class="form-text text-muted">Debe comenzar con 9 y tener 9 dígitos</small>
                                    </div>
                                </div>
                                ${currentTipoCuenta == 3 ? `
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Titular de la Billetera</label>
                                        <input type="text" class="form-control" name="cuentas[${tipo}][digitales][${index}][titular]" placeholder="Nombre completo del titular" required>
                                    </div>
                                </div>` : ''}
                            </div>
                            <input type="hidden" name="cuentas[${tipo}][digitales][${index}][tipo_cuenta_id]" value="${currentTipoCuenta}">
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
                
                walletRow.find('.remove-account').on('click', function() {
                    walletRow.remove();
                    updateAccountIndexes();
                });
                
                $('#cuentasContainer').append(walletRow);
            }

            // Función para actualizar índices
            function updateAccountIndexes() {
                const tipo = currentTipoCuenta == 2 ? 'propia' : 'terceros';
                const tipoCuentaText = currentTipoCuenta == 2 ? 'Propia' : 'de Terceros';
                
                let bancariasCount = 0;
                let digitalesCount = 0;
                
                $('#cuentasContainer .cuenta-row').each(function() {
                    const esBancaria = $(this).data('tipo') === 'bancaria';
                    const index = esBancaria ? bancariasCount : digitalesCount;
                    const subTipo = esBancaria ? 'bancarias' : 'digitales';
                    const tipoText = esBancaria ? 'Cuenta Bancaria' : 'Billetera Digital';
                    
                    $(this).find('.card-title').text(`${tipoText} ${tipoCuentaText} ${index + 1}`);
                    
                    // Actualizar nombres de campos
                    // Actualizar nombres para cuentas bancarias
                    $(this).find('select[name*="entidad_id"]').attr('name', `cuentas[${tipo}][${subTipo}][${index}][entidad_id]`);
                    // Actualizar nombres para billeteras digitales
                    $(this).find('select[name*="billetera_id"]').attr('name', `cuentas[${tipo}][${subTipo}][${index}][billetera_id]`);
                    $(this).find('input[name*="numero_cuenta"], input[name*="numero_telefono"]').attr('name', `cuentas[${tipo}][${subTipo}][${index}][${esBancaria ? 'numero_cuenta' : 'numero_telefono'}]`);
                    $(this).find('input[name*="titular"]').attr('name', `cuentas[${tipo}][${subTipo}][${index}][titular]`);
                    $(this).find('input[type="hidden"]').attr('name', `cuentas[${tipo}][${subTipo}][${index}][tipo_cuenta_id]`);
                    
                    if (esBancaria) bancariasCount++;
                    else digitalesCount++;
                });
            }

            // Event listeners para botones de agregar cuentas
            $('#addBankAccountButton').on('click', function() {
                const count = $('#cuentasContainer .cuenta-row[data-tipo="bancaria"]').length;
                createBankAccountRow(count);
            });

            $('#addDigitalWalletButton').on('click', function() {
                const count = $('#cuentasContainer .cuenta-row[data-tipo="digital"]').length;
                createDigitalWalletRow(count);
            });

            

            // Funciones para agregar elementos dinámicos
            function addPhone() {
                const phoneRow = $(`
                    <div class="row d-flex align-items-center mb-3" data-phone-index="${phoneCounter}">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="telefono${phoneCounter}">Teléfono ${phoneCounter + 1}</label>
                                <input type="tel" class="form-control shadow-sm phone-input" name="telefono[${phoneCounter}]" 
                                    id="telefono${phoneCounter}" maxlength="9" pattern="[0-9]{9}" 
                                    placeholder="Ej: 987654321" title="Ingrese exactamente 9 dígitos numéricos" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tipo</label>
                                <div class="btn-group-toggle d-flex" data-toggle="buttons" style="margin-bottom: -10px !important;">
                                    <!--label class="btn btn-outline-primary">
                                        <input type="radio" name="tipo[${phoneCounter}]" value="casa"> Casa
                                    </label-->
                                    <label class="btn btn-outline-primary active">
                                        <input type="radio" name="tipo[${phoneCounter}]" value="celular" checked> Celular
                                    </label>
                                    <!--label class="btn btn-outline-primary">
                                        <input type="radio" name="tipo[${phoneCounter}]" value="trabajo"> Trabajo
                                    </label-->
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
                
                // Ocultar todos los botones primero
                phoneRows.find('.delete-phone-btn').hide();
                
                // Encontrar y mostrar solo el botón del último teléfono agregado
                const maxIndex = Math.max(...phoneRows.map(function() { return parseInt($(this).data('phone-index')); }).get());
                phoneRows.each(function() {
                    const currentIndex = parseInt($(this).data('phone-index'));
                    if (currentIndex === maxIndex) {
                        $(this).find('.delete-phone-btn').show();
                    }
                });
            }

            // Función para actualizar las etiquetas de los teléfonos
            function updatePhoneLabels() {
                $('#phoneContainer .row').each(function(index) {
                    $(this).find('label[for^="telefono"]').text(`Teléfono ${index + 1}`);
                });
            }

            function addAccount() {
                const accountRow = $(`
                    <div class="row mb-3">
                        <div class="col-12 text-right">
                            <button class="btn btn-sm btn-danger shadow-sm delete-cuenta-btn" type="button">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="tCuenta[${accountCounter}]">Tipo de Cuenta</label>
                                <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                                    ${tiposCuenta.map((tipo, i) => `
                                        <label class="btn btn-outline-primary shadow-sm flex-fill ${i === 0 ? 'active' : ''}">
                                            <input type="radio" name="tCuenta[${accountCounter}]" value="${tipo.id}" 
                                                id="tCuenta${tipo.id}_${accountCounter}" ${i === 0 ? 'checked' : ''}>
                                            ${tipo.tipo_cuenta}
                                        </label>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6" style="display: none;">
                            <div class="form-group">
                                <label for="entidad_financiera_${accountCounter}">Entidad Financiera</label>
                                <select class="form-control shadow-sm" id="entidad_financiera_${accountCounter}" 
                                        name="entidad_financiera[${accountCounter}]">
                                    <option value="">Selecciona</option>
                                    ${entBancarias.map(ent => `<option value="${ent.id}">${ent.banco}</option>`).join('')}
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6" style="display: none;">
                            <div class="form-group">
                                <label for="f_nCuenta${accountCounter}">Nro. de Cuenta</label>
                                <input type="text" class="form-control shadow-sm" name="f_nCuenta[${accountCounter}]" 
                                    id="f_nCuenta${accountCounter}">
                            </div>
                        </div>
                        <div class="col-12" style="display: none;">
                            <div class="form-group">
                                <label for="ct_Titular${accountCounter}">Titular</label>
                                <input type="text" class="form-control shadow-sm" name="ct_Titular[${accountCounter}]" 
                                    id="ct_Titular${accountCounter}">
                            </div>
                        </div>
                    </div>
                `);
                $('#cuentasContainer').append(accountRow);
                accountCounter++;
            }

            function addAddress() {
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
                                            ${zonas.map(z => `<option value="${z.id}">${z.nombre}</option>`).join('')}
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
                                    ${departamentos.map(d => `<option value="${d.id}">${d.departamento}</option>`).join('')}
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
                                    <option>Seleccionar</option>
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
                                    id="cantPisos${addressCounter}" min="0" max="10" required value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tipo_residencia${addressCounter}">Titular Domicilio</label>
                                <select class="form-control shadow-sm select-tipo-residencia" id="tipo_residencia${addressCounter}" 
                                        name="tipo_residencia[${addressCounter}]">
                                    <option>Seleccionar</option>
                                    <option value="Propia">Propia</option>
                                    <option value="Familiar">Familiar</option>
                                    <option value="Alquilada">Alquilada</option>
                                    <option value="Otros">Otros</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="tiempo_residencia${addressCounter}">Tiempo de Residencia</label>
                            <div class="form-group d-flex">
                                <input type="number" class="form-control shadow-sm mr-2" name="tiempo_residencia[${addressCounter}]" 
                                    id="tiempo_residencia${addressCounter}" min="0" max="99" style="flex: 2;" required value="0">
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
                                <input type="tel" class="form-control shadow-sm phone-input" name="telefono_propietario[${addressCounter}]" 
                                    id="telefono_propietario${addressCounter}" maxlength="9" pattern="[0-9]{9}" 
                                    placeholder="Ej: 987654321" title="Ingrese exactamente 9 dígitos numéricos">
                            </div>
                            </div>
                            </div>
                        </div>
                    </div>
                `);
                
                // Agregar event listeners
                addressRow.find('.delete-direccion-btn').on('click', function() {
                    addressRow.remove();
                });
                
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
            function tipoCuentaChange() {
                const row = $(this).closest('.row');
                const val = $(this).val();
                const isEffective = val == 1; // Asumiendo que 1 es "Efectivo"
                const isTerceros = val == 3; // Asumiendo que 3 es "Terceros"
                
                row.find('.col-md-6').toggle(!isEffective);
                row.find('.col-12').last().toggle(isTerceros);
            }

            function titularDomicilioChange() {
                const row = $(this).closest('.row');
                const tipoResidencia = $(this).val();
                const isPropia = tipoResidencia === 'Propia';
                
                row.find('.propietario-fields').toggle(!isPropia);
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
                
                // Inicializar ubicaciones existentes
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
                            options += `<option value="${provincia.id}" ${selected}>${provincia.nombre}</option>`;
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
                            options += `<option value="${distrito.id}" ${selected}>${distrito.nombre}</option>`;
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
        

        // Departamentos, provincias y distritos (maintained for dynamic address location selectors)
        // NOTE: The dynamic loading for each address is handled via initLocationSystem() which uses delegated events


        // Validación y envío del formulario
        function initFormValidation() {
            $('#multi-step-form').on('submit', function(e) {
                e.preventDefault();
                
                // Validar todos los pasos antes de enviar
                let allStepsValid = true;
                for (let step = 1; step <= 5; step++) {
                    if (!validateStep(step)) {
                        allStepsValid = false;
                        break; // Detener en el primer paso inválido
                    }
                }
                
                if (!allStepsValid) {
                    return false;
                }
                
                // Validaciones adicionales de integridad completa
                if (!validateFormCompleteness()) {
                    return false;
                }
                
                // Confirmar envío
                Swal.fire({
                    title: '¿Desea guardar los cambios?',
                    text: 'Se actualizarán todos los datos del cliente.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, guardar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#1A3C6D',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Mostrar loading
                        Swal.fire({
                            title: 'Guardando cambios',
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
            const errors = [];
            let firstErrorStep = null;
            
            // Verificar que hay al menos un teléfono
            if ($('#phoneContainer .row').length === 0) {
                errors.push('• Debe agregar al menos un teléfono');
                if (!firstErrorStep) firstErrorStep = 1;
            }
            
            // Validar formato de todos los teléfonos del formulario
            let hasInvalidPhones = false;
            $('.phone-input').each(function() {
                const phoneField = $(this);
                const value = phoneField.val();
                if (value.length > 0 && value.length !== 9) {
                    hasInvalidPhones = true;
                    return false; // break out of each loop
                }
            });
            
            if (hasInvalidPhones) {
                errors.push('• Todos los teléfonos deben tener exactamente 9 dígitos numéricos');
                if (!firstErrorStep) firstErrorStep = 1;
            }
            
            // Verificar que hay al menos una dirección
            if ($('#direccionesContainer .direccion-row').length === 0) {
                errors.push('• Debe agregar al menos una dirección');
                if (!firstErrorStep) firstErrorStep = 2;
            } else {
                // Verificar que cada dirección tenga zona y sucursal
                let missingZonaSucursal = false;
                $('#direccionesContainer .direccion-row').each(function(index) {
                    const zonaSelect = $(this).find('.select-zona-direccion');
                    const sucursalSelect = $(this).find('.select-sucursal-direccion');
                    
                    if (!zonaSelect.val() || zonaSelect.val() === '') {
                        errors.push(`• Debe seleccionar una zona para la Dirección ${index + 1}`);
                        missingZonaSucursal = true;
                        if (!firstErrorStep) firstErrorStep = 2;
                    }
                    
                    if (!sucursalSelect.val() || sucursalSelect.val() === '') {
                        errors.push(`• Debe seleccionar una sucursal para la Dirección ${index + 1}`);
                        missingZonaSucursal = true;
                        if (!firstErrorStep) firstErrorStep = 2;
                    }
                });
            }
            
            // Verificar campos requeridos faltantes
            const missingRequiredFields = [];
            
            // Campos requeridos del paso 1
            $('#step-1 input[required], #step-1 select[required]').each(function() {
                if (!$(this).val() || $(this).val().trim() === '') {
                    const label = $(this).closest('.form-group').find('label').text().replace('*', '').trim();
                    if (label && !missingRequiredFields.includes(label)) {
                        missingRequiredFields.push(label);
                        if (!firstErrorStep) firstErrorStep = 1;
                    }
                }
            });
            
            // Campos requeridos del paso 2
            $('#step-2 input[required], #step-2 select[required]').each(function() {
                if (!$(this).val() || $(this).val().trim() === '') {
                    const label = $(this).closest('.form-group').find('label').text().replace('*', '').trim();
                    if (label && !missingRequiredFields.includes(label)) {
                        missingRequiredFields.push(label);
                        if (!firstErrorStep) firstErrorStep = 2;
                    }
                }
            });
            
            if (missingRequiredFields.length > 0) {
                errors.push('• Campos obligatorios sin completar: ' + missingRequiredFields.join(', '));
            }
            
            // Verificar cuentas bancarias (solo si no es "Sin Cuentas" - tipo 1)
            const tiposCuentaSeleccionados = $('input[name="tCuenta"]:checked');
            if (tiposCuentaSeleccionados.length === 0) {
                errors.push('• Debe seleccionar un tipo de cuenta');
                if (!firstErrorStep) firstErrorStep = 3;
            } else {
                const tipoCuentaSeleccionado = parseInt(tiposCuentaSeleccionados.val());
                
                // Si el tipo de cuenta es 2 (Propias) o 3 (de Terceros), debe tener al menos una cuenta
                if (tipoCuentaSeleccionado === 2 || tipoCuentaSeleccionado === 3) {
                    const cuentasAgregadas = $('#cuentasContainer .cuenta-row').length;
                    if (cuentasAgregadas === 0) {
                        const tipoCuentaTexto = tipoCuentaSeleccionado === 2 ? 'propias' : 'de terceros';
                        errors.push(`• Debe agregar al menos una cuenta bancaria o billetera digital para cuentas ${tipoCuentaTexto}`);
                        if (!firstErrorStep) firstErrorStep = 3;
                    }
                }
            }
            
            // Si hay errores, mostrar la alerta detallada
            if (errors.length > 0) {
                const errorMessage = `Para completar el registro del cliente, debe corregir lo siguiente:\n\n${errors.join('\n')}`;
                
                Swal.fire({
                    icon: 'warning',
                    title: '¡Formulario incompleto!',
                    html: errorMessage.replace(/\n/g, '<br>'),
                    confirmButtonText: 'Ir a corregir',
                    confirmButtonColor: '#dc3545',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => {
                    if (firstErrorStep) {
                        goToStep(firstErrorStep);
                    }
                });
                
                return false;
            }
            
            return true;
        }

        // Validación en tiempo real para campos de teléfono
        function initPhoneValidation() {
            // Validación para campos de teléfono existentes y futuros
            $(document).on('input', '.phone-input', function() {
                const phoneField = $(this);
                let value = phoneField.val();
                
                // Remover cualquier carácter que no sea número
                value = value.replace(/\D/g, '');
                
                // Limitar a 9 dígitos
                if (value.length > 9) {
                    value = value.substring(0, 9);
                }
                
                // Actualizar el valor del campo
                phoneField.val(value);
                
                // Validar longitud y mostrar feedback visual
                if (value.length === 0) {
                    phoneField.removeClass('is-valid is-invalid');
                } else if (value.length === 9) {
                    phoneField.removeClass('is-invalid').addClass('is-valid');
                } else {
                    phoneField.removeClass('is-valid').addClass('is-invalid');
                }
            });
            
            // Validación al perder el foco
            $(document).on('blur', '.phone-input', function() {
                const phoneField = $(this);
                const value = phoneField.val();
                
                if (value.length > 0 && value.length !== 9) {
                    phoneField.addClass('is-invalid');
                    // Mostrar mensaje de error si existe un contenedor para ello
                    const feedbackElement = phoneField.next('.invalid-feedback');
                    if (feedbackElement.length === 0) {
                        phoneField.after('<div class="invalid-feedback">El teléfono debe tener exactamente 9 dígitos.</div>');
                    }
                } else if (value.length === 9) {
                    phoneField.removeClass('is-invalid').addClass('is-valid');
                    phoneField.next('.invalid-feedback').remove();
                }
            });
            
            // Prevenir pegado de contenido no numérico
            $(document).on('paste', '.phone-input', function(e) {
                const phoneField = $(this);
                
                setTimeout(() => {
                    let value = phoneField.val();
                    value = value.replace(/\D/g, '');
                    
                    if (value.length > 9) {
                        value = value.substring(0, 9);
                    }
                    
                    phoneField.val(value);
                    phoneField.trigger('input'); // Disparar validación
                }, 0);
            });
        }
        
        // Validación específica para teléfonos en la función validateStep
        function validatePhoneFields(stepSelector) {
            let valid = true;
            const invalidPhones = [];
            
            $(stepSelector + ' .phone-input').each(function() {
                const phoneField = $(this);
                const value = phoneField.val();
                const label = phoneField.closest('.form-group').find('label').text().replace('*', '').trim();
                
                if (phoneField.prop('required') && value.length === 0) {
                    phoneField.addClass('is-invalid');
                    if (label) invalidPhones.push(`${label} (campo vacío)`);
                    valid = false;
                } else if (value.length > 0 && value.length !== 9) {
                    phoneField.addClass('is-invalid');
                    if (label) invalidPhones.push(`${label} (debe tener 9 dígitos)`);
                    valid = false;
                } else if (value.length > 0) {
                    phoneField.removeClass('is-invalid').addClass('is-valid');
                }
            });
            
            if (!valid && invalidPhones.length > 0) {
                const errorMessage = 'Corrija los siguientes teléfonos:\n• ' + invalidPhones.join('\n• ');
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Teléfonos inválidos',
                    html: errorMessage.replace(/\n/g, '<br>'),
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#dc3545'
                });
            }
            
            return valid;
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

        // Función para mostrar burbuja de confirmación DNI
        function mostrarBurbujaDNI(data) {
            console.log('🔍 Mostrando burbuja con datos:', data);
            
            // Construir nombre completo - solo mostrar esto
            let nombreCompleto = data.nombre_completo || '';
            if (!nombreCompleto) {
                nombreCompleto = [
                    data.nombres || '',
                    data.apellido_paterno || '',
                    data.apellido_materno || ''
                ].filter(Boolean).join(' ');
            }
            console.log('📝 Nombre completo construido:', nombreCompleto);
            $('#bubble-nombre').text(nombreCompleto.trim() || 'No disponible');
            
            // Posicionar burbuja cerca del campo DNI
            const dniField = $('#nDocumento');
            const offset = dniField.offset();
            const bubble = $('#dniBubble');
            
            bubble.css({
                'top': offset.top + dniField.outerHeight() + 10,
                'left': offset.left,
                'display': 'block'
            });
            
            console.log('✅ Burbuja mostrada correctamente');
        }

        // Función para cerrar burbuja DNI
        function closeDniBubble() {
            $('#dniBubble').hide();
            window.dniDataTemp = null;
        }

        // Debug inicial
        console.log('🔧 Registrando event listener para botón cargar datos');
        
        // Manejar confirmación de carga de datos - usando delegación de eventos
        $(document).on('click', '#confirmLoadData', function() {
            console.log('🚀 BOTÓN CARGAR DATOS CLICKEADO');
            console.log('📊 window.dniDataTemp:', window.dniDataTemp);
            
            if (window.dniDataTemp) {
                const data = window.dniDataTemp;
                console.log('✅ Datos disponibles, iniciando carga:', data);
                
                // Llenar campos del formulario
                console.log('📝 Llenando campos básicos...');
                $('#nombres').val(data.nombres || '');
                $('#aPaterno').val(data.apellido_paterno || '');
                $('#aMaterno').val(data.apellido_materno || '');
                console.log('✅ Campos básicos llenados');
                
                // Agregar fecha de nacimiento
                if (data.fecha_nacimiento) {
                    console.log('📅 Procesando fecha de nacimiento:', data.fecha_nacimiento);
                    let fechaNacimiento = formatearFechaParaInput(data.fecha_nacimiento);
                    console.log('📅 Fecha formateada:', fechaNacimiento);
                    $('#fecha_nacimiento').val(fechaNacimiento);
                    if (fechaNacimiento) {
                        calcularEdad(fechaNacimiento);
                        console.log('✅ Fecha de nacimiento y edad asignadas');
                    }
                } else {
                    console.log('⚠️ No hay fecha de nacimiento');
                }
                
                // Agregar dirección
                if (data.direccion || data.direccion_completa) {
                    console.log('🏠 Creando dirección con datos:', {
                        direccion: data.direccion,
                        direccion_completa: data.direccion_completa
                    });
                    crearDireccionDesdeAPI(data);
                    console.log('✅ Dirección procesada');
                } else {
                    console.log('⚠️ No hay datos de dirección');
                }
                
                // Cerrar burbuja
                console.log('🔒 Cerrando burbuja...');
                closeDniBubble();
                
                // Mostrar notificación de éxito
                console.log('🎉 Mostrando notificación de éxito');
                Swal.fire({
                    icon: 'success',
                    title: '¡Datos Cargados!',
                    text: 'Información del cliente cargada correctamente.',
                    timer: 1500,
                    showConfirmButton: false,
                    position: 'top-end',
                    toast: true
                });
                
                console.log('✅ PROCESO DE CARGA COMPLETADO');
            } else {
                console.error('❌ NO HAY DATOS TEMPORALES DISPONIBLES');
                console.log('🔍 Verificando window:', window);
            }
        });

        // Cargar sucursales si hay una zona preseleccionada (cuando hay errores de validación y se usa old())
        @if(old('zona'))
            const oldZonaId = "{{ old('zona') }}";
            const oldSucursalId = "{{ old('sucursal') }}";

            if (oldZonaId) {
                fetch(`/api/zona/${oldZonaId}/sucursales`)
                    .then(response => response.json())
                    .then(data => {
                        let optionsHtml = '<option value="">Selecciona una sucursal</option>';
                        data.forEach(s => {
                            const selected = s.id == oldSucursalId ? 'selected' : '';
                            optionsHtml += `<option value="${s.id}" ${selected}>${s.sucursal}</option>`;
                        });
                        $('#sucursal').html(optionsHtml);
                    })
                    .catch(error => console.error('Error loading sucursales:', error));
            }
        @endif
    </script>

    <!-- Burbuja de Confirmación DNI -->
    <div id="dniBubble" class="dni-confirmation-bubble" style="display: none;">
        <div class="bubble-content">
            <div class="bubble-header">
                <i class="fas fa-user-check text-success mr-2"></i>
                <strong>Cliente encontrado</strong>
                <button type="button" class="btn-close-bubble" onclick="closeDniBubble()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="bubble-body">
                <div class="client-info">
                    <div class="text-center">
                        <strong id="bubble-nombre" class="client-name"></strong>
                        <p class="text-muted mb-0 mt-1">¿Es este el cliente correcto?</p>
                    </div>
                </div>
            </div>
            <div class="bubble-actions">
                <button type="button" class="btn-bubble btn-secondary" onclick="closeDniBubble()">
                    <i class="fas fa-times mr-1"></i>Cancelar
                </button>
                <button type="button" class="btn-bubble btn-primary" id="confirmLoadData">
                    <i class="fas fa-check mr-1"></i>Cargar Datos
                </button>
            </div>
        </div>
        <div class="bubble-arrow"></div>
    </div>

    <style>
        .dni-confirmation-bubble {
            position: absolute;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border: 1px solid #e0e0e0;
            min-width: 320px;
            max-width: 400px;
            z-index: 9999;
            font-size: 13px;
        }

        .bubble-content {
            padding: 0;
        }

        .bubble-header {
            background: #f8f9fa;
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            border-radius: 8px 8px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-close-bubble {
            background: none;
            border: none;
            color: #6c757d;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            cursor: pointer;
        }

        .btn-close-bubble:hover {
            background: #e9ecef;
            color: #495057;
        }

        .bubble-body {
            padding: 15px;
        }

        .client-info .info-line {
            margin-bottom: 8px;
            display: flex;
            align-items: flex-start;
        }

        .client-info .info-line:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            min-width: 80px;
            flex-shrink: 0;
        }

        .info-value {
            color: #212529;
            word-break: break-word;
        }

        .client-name {
            font-size: 16px;
            color: #212529;
        }

        .bubble-actions {
            padding: 12px 15px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .btn-bubble {
            padding: 6px 12px;
            border: 1px solid;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            transition: all 0.2s;
        }

        .btn-bubble.btn-secondary {
            background: white;
            border-color: #6c757d;
            color: #6c757d;
        }

        .btn-bubble.btn-secondary:hover {
            background: #6c757d;
            color: white;
        }

        .btn-bubble.btn-primary {
            background: #007bff;
            border-color: #007bff;
            color: white;
        }

        .btn-bubble.btn-primary:hover {
            background: #0056b3;
            border-color: #0056b3;
        }

        .bubble-arrow {
            position: absolute;
            top: -8px;
            left: 20px;
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 8px solid white;
        }

        .bubble-arrow::before {
            content: '';
            position: absolute;
            top: -1px;
            left: -8px;
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-bottom: 8px solid #e0e0e0;
        }
    </style>
@stop
