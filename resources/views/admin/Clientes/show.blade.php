@extends('layouts.admin')

@section('title', 'Nuevo Cliente')

@section('content_header')
    <h1 class="m-0 text-dark">Ver cliente</h1>
@stop

@section('content')

<!--------------------------DATA INICIO ---------------------------------------->
<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h5><span id="step-title">1</span></h5>
            <ul id="step-bullets" class="list-inline">
                <!-- Bullets will be generated here -->
            </ul>
        </div>
        <div class="card-body">
            <div id="step-1" class="form-step" data-step-name="Cliente">
                <div class="col-12 mb-3">
                    <div class="form-group">
                        <div class="text-center">
                            @if ($cliente->persona->imagen)
                                <img src="{{ asset('img/clientes_img/' . $cliente->persona->imagen) }}" class="rounded mx-auto d-block" id="img" alt="userPhoto" style="height: 150px;">
                            @else
                                <img src="{{ asset('/img/no-data.png') }}" class="rounded mx-auto d-block" id="img" alt="userPhoto" style="height: 150px;">
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex justify-content-center mb-4">
                        <div class="form-group mr-1">
                            <div class="adjuntar-foto">
                                <label for="file" class="btn btn-info"><i class="fa fa-upload mr-1"></i>Seleccionar foto</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!----------------------TITULO------------------------>
                    <div class="col-12">
                        <h5 style="margin-bottom: -15px;font-weight: bold; color: #18458a;text-transform:uppercase;">Asignación</h5> <hr>
                    </div>
                    <!----------------------FIN------------------------>
                    <div class="col-3">
                        <div class="form-group">
                            <label for="sucursal">Sucursal</label>
                            <p>{{ $cliente->sucursal->sucursal ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label for="jcc">JCC</label>
                            @php
                                $ultimoPrestamoConJcc = $cliente->prestamos->sortByDesc('created_at')->first(function($prestamo) {
                                    return $prestamo->carterasJcc->count() > 0;
                                });
                                $jccActual = $ultimoPrestamoConJcc ? $ultimoPrestamoConJcc->carterasJcc->first() : null;
                            @endphp
                            <p>
                                @if($jccActual)
                                    {{ $jccActual->user->codigo ?? 'N/A' }} - {{ $jccActual->user->persona->nombres ?? $jccActual->user->name ?? 'N/A' }}
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label for="asesorCredito">Asesor de Credito</label>
                            @php
                                $ultimoPrestamoConAsesor = $cliente->prestamos->sortByDesc('created_at')->first(function($prestamo) {
                                    return $prestamo->carterasAsesor->count() > 0;
                                });
                                $asesorActual = $ultimoPrestamoConAsesor ? $ultimoPrestamoConAsesor->carterasAsesor->first() : null;
                            @endphp
                            <p>
                                @if($asesorActual)
                                    {{ $asesorActual->user->codigo ?? 'N/A' }} - {{ $asesorActual->user->persona->nombres ?? $asesorActual->user->name ?? 'N/A' }}
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label for="analista">Analista</label>
                            @php
                                $ultimoPrestamoConAnalista = $cliente->prestamos->sortByDesc('created_at')->first(function($prestamo) {
                                    return $prestamo->carterasAnalista->count() > 0;
                                });
                                $analistaActual = $ultimoPrestamoConAnalista ? $ultimoPrestamoConAnalista->carterasAnalista->first() : null;
                            @endphp
                            <p>
                                @if($analistaActual)
                                    {{ $analistaActual->user->codigo ?? 'N/A' }} - {{ $analistaActual->user->persona->nombres ?? $analistaActual->user->name ?? 'N/A' }}
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <!----------------------TITULO------------------------>
                <div class="col-12">
                    <h5 style="margin-bottom: -15px;font-weight: bold; color: #18458a;text-transform:uppercase;">Datos Personales</h5> <hr>
                </div>
                <!----------------------FIN------------------------>
                <div class="row">
                    <div class="col-3">
                        <div class="form-group">
                            <label for="nDocumento">Número de DNI</label>
                            <p>{{ $cliente->persona->documento ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="col-3">
                        <div class="form-group">
                            <label for="nombres">Nombres</label>
                            <p>{{ $cliente->persona->nombres ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label for="aPaterno">Apellido Paterno</label>
                            <p>{{ $cliente->persona->ape_pat ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label for="aMaterno">Apellido Materno</label>
                            <p>{{ $cliente->persona->ape_mat ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="form-group">
                            <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                            <p>{{ $cliente->persona->fecha_nacimiento ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-1">
                        <div class="form-group">
                            <label for="edad">Edad</label>
                            <p>{{ $cliente->persona->edad ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="form-group">
                            <label for="estado_civil">Estado Civil</label>
                            <p>{{ $cliente->persona->estado_civil ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="form-group">
                            <label for="celular2">Celular 1</label>
                            <p>{{ $cliente->celular2 }}</p>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="form-group">
                            <label for="telefono">Celular 2</label>
                            <p>{{ $cliente->telefono }}</p>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <p>{{ $cliente->persona->email ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <hr>
                </div>

                <hr>
                <!-- Datos Bancarios del Cliente -->
                <!----------------------TITULO------------------------>
                <div class="col-12">
                    <h5 style="margin-bottom: -15px;font-weight: bold; color: #18458a;text-transform:uppercase;">Datos familiares / Conyuge</h5> <hr>
                </div>
                <!----------------------FIN------------------------>
                <div class="row">
                    <div class="col-2">
                        <div class="form-group">
                            <label for="carga_familiar">Carga Familiar</label>
                            <p>{{ $cliente->carga_familiar }}</p>
                        </div>
                    </div>
                    <div class="col-3">
                        <label for="conyuge_dni" class="mr-2">DNI</label>
                        <p>{{ $cliente->conyuge_dni }}</p>
                    </div>
                    <div class="col-5">
                        <div class="form-group">
                            <label for="conyuge_nombres">Apellidos y Nombres</label>
                            <p>{{ $cliente->conyuge_nombres }}</p>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="form-group">
                            <label for="conyuge_telefono">Teléfono</label>
                            <p>{{ $cliente->conyuge_telefono }}</p>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="form-group">
                            <label for="conyuge_actividad">Oficio / profesión</label>
                            <p>{{ $cliente->conyuge_actividad }}</p>
                        </div>
                    </div>
                    <div class="col-5">
                        <div class="form-group">
                            <label for="conyuge_direccion_trabajo">Dirección de Trabajo</label>
                            <p>{{ $cliente->conyuge_direccion_trabajo }}</p>
                        </div>
                    </div>
                    <div class="col-5">
                        <div class="form-group">
                            <label for="ref_conyuge_direccion_trabajo">Referencia</label>
                            <p>{{ $cliente->ref_conyuge_direccion_trabajo }}</p>
                        </div>
                    </div>
                </div>
                <hr>
            </div>
            <!-- Paso 2: Datos Bancarios -->
            <div id="step-2" class="form-step d-none" data-step-name="Datos Bancarios">
                <!----------------------TITULO------------------------>
                <div class="col-12">
                    <h5 style="margin-bottom: -15px;font-weight: bold; color: #18458a;text-transform:uppercase;">DATOS BANCARIOS</h5> <hr>
                </div>
                <!----------------------FIN------------------------>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="tCuenta">Tipo de Cuenta</label>
                            <p>{{ $cliente->tipo_cuenta_id == 1 ? 'Cuenta Propia' : 'Cuenta de Terceros' }}</p>
                        </div>
                    </div>
                    <div class="col-12 mt-2" id="finanzas-section" hidden>
                        <h3 class="text-secondary"><strong>FINANZAS</strong></h3>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="entidadter">Entidad Financiera - {{$cliente->entidadter}}</label>
                                    <p>{{ $cliente->entidadter->banco ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="f_nCuenta">Nro. de cuenta</label>
                                    <p>{{ $cliente->cuentafi }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-2" id="cuenta-terceros-section" hidden>
                        <h3 class="text-secondary"><strong>CUENTA TERCEROS</strong></h3>
                        <div class="row">
                            <div class="col-2">
                                <div class="form-group">
                                    <label for="ct_eFinanciera">Entidad Financiera</label>
                                    <p>{{ $cliente->ct_eFinanciera->banco ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="ct_nCuenta">Nro de cuenta</label>
                                    <p>{{ $cliente->cuentater }}</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="ct_Titular">Titular</label>
                                    <p>{{ $cliente->titularter }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row justify-content-between">
                    <button type="button" class="btn btn-secondary prev-step">Regresar</button>
                    <button type="button" class="btn btn-primary next-step">Guardar y Continuar</button>
                </div>
            </div>
            <!-- Paso 3: Datos de Residencia -->
            <div id="step-3" class="form-step d-none" data-step-name="Residencia">
                <!----------------------TITULO------------------------>
                <div class="col-12">
                    <h5 style="margin-bottom: -15px;font-weight: bold; color: #18458a;text-transform:uppercase;">RESIDENCIA</h5> <hr>
                </div>
                <!----------------------FIN------------------------>
                <div class="row">
                    <div class="col-3">
                        <div class="form-group">
                            <label for="departamento">Departamento</label>
                            <p>{{ $cliente->departamento->departamento ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label for="provincia">Provincia</label>
                            <p>{{ $cliente->provincia->provincia ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="form-group">
                            <label for="distrito">Distrito</label>
                            <p>{{ $cliente->distrito->distrito ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="form-group">
                            <label for="zona">Zona</label>
                            <p>{{ $cliente->zona->zona ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="form-group">
                            <label for="tZona">Tipo de Zona</label>
                            <p>{{ $cliente->tZona->tipo ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label for="direccion">Dirección de domicilio</label>
                            <p>{{ $cliente->direccion }}</p>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label for="nLotes">Número / Lote</label>
                            <p>{{ $cliente->nlote }}</p>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label for="referencia">Referencia domiciliaria</label>
                            <p>{{ $cliente->referencia }}</p>
                        </div>
                    </div>
                    <hr>
                </div>
                <!----------------------TITULO------------------------>
                <div class="col-12">
                    <h5 style="margin-bottom: -15px;font-weight: bold; color: #18458a;text-transform:uppercase;">DOMICILIO</h5> <hr>
                </div>
                <!----------------------FIN------------------------>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label for="material_inmueble">Material de Inmueble</label>
                            <p>{{ $cliente->material_inmueble }}</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label for="cantPisos">Cantidad de pisos</label>
                            <p>{{ $cliente->cantPisos }}</p>
                        </div>
                    </div>
                    <div class="col-2">
                        <div class="form-group">
                            <label for="material_inmueble">Titular Domicilio</label>
                            <p>{{ $cliente->material_inmueble }}</p>
                        </div>
                    </div>
                    <div class="col-3">
                        <label for="tiempo_residencia">Tiempo de Residencia</label>
                        <div class="form-group d-flex">
                            <p>{{ $cliente->tiempo_residencia }} {{ $cliente->tipo_residencia }}</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label for="nombre_propietario">Nombre del Propietario</label>
                            <p>{{ $cliente->nombre_propietario }}</p>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label for="telefono_propietario">Teléfono del Propietario</label>
                            <p>{{ $cliente->telefono_propietario }}</p>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row justify-content-between">
                    <button type="button" class="btn btn-secondary prev-step">Regresar</button>
                    <button type="button" class="btn btn-primary next-step">Guardar y Continuar</button>
                </div>
            </div>
            <!-- Paso 4: Datos de Trabajo del Cliente -->
            <div id="step-4" class="form-step d-none" data-step-name="Laborales">
                <!----------------------TITULO------------------------>
                <div class="col-12">
                    <h5 style="margin-bottom: -15px;font-weight: bold; color: #18458a;text-transform:uppercase;">DATOS LABORALES</h5> <hr>
                </div>
                <!----------------------FIN------------------------>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label for="actividad_economica">Actividad Económica</label>
                            <p>{{ $cliente->actividad_economica }}</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label for="nombre_lugar_trabajo">Nombre del Lugar de Trabajo</label>
                            <p>{{ $cliente->nombre_lugar_trabajo }}</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label for="desempeno_cargo">Desempeño o Cargo</label>
                            <p>{{ $cliente->desempeno_cargo }}</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label for="direccion_trabajo">Dirección del Trabajo</label>
                            <p>{{ $cliente->direccion_trabajo }}</p>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row justify-content-between">
                    <button type="button" class="btn btn-secondary prev-step">Regresar</button>
                    <button type="button" class="btn btn-primary next-step">Guardar y Continuar</button>
                </div>
            </div>
            <!-- Paso 5: Datos del Aval -->
            <div id="step-5" class="form-step d-none" data-step-name="Aval">
                <div class="col-12">
                    <h5 class="text-blue mt-2" style="margin-bottom: -15px;"><strong>Datos Aval</strong></h5>
                    <hr>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="aval">Aval</label>
                        <p>{{ $cliente->aval == 1 ? 'Si' : 'No' }}</p>
                    </div>
                </div>
                <div class="col-12 mt-2" id="aval-section" {{ $cliente->aval == 1 ? '' : 'hidden' }}>
                    <h3 class="text-secondary"><strong>AVAL</strong></h3>
                    <div class="row">
                        <div class="col-3">
                            <div class="form-group">
                                <label for="documentoav">Nro de documento</label>
                                <p>{{ $cliente->documentoav }}</p>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <label for="nombresav">Nombres</label>
                                <p>{{ $cliente->nombresav }}</p>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <label for="ape_patav">Apellido Paterno</label>
                                <p>{{ $cliente->ape_patav }}</p>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <label for="ape_matav">Apellido Materno</label>
                                <p>{{ $cliente->ape_matav }}</p>
                            </div>
                        </div>
                        <div class="col-2">
                            <div class="form-group">
                                <label for="aval_fecha_nacimiento">Fecha de Nacimiento</label>
                                <p>{{ $cliente->aval_fecha_nacimiento }}</p>
                            </div>
                        </div>
                        <div class="col-2">
                            <div class="form-group">
                                <label for="edad">Edad</label>
                                <p>{{ $cliente->aval_edad }}</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="aval_estado_civil">Estado Civil</label>
                                <p>{{ $cliente->aval_estado_civil }}</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="aval_parentesco">Parentesco</label>
                                <p>{{ $cliente->aval_parentesco }}</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="direccionav">Direccion de domicilio</label>
                                <p>{{ $cliente->direccionav }}</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="aval_referencia">Referencia</label>
                                <p>{{ $cliente->aval_referencia }}</p>
                            </div>
                        </div>
                        <div class="col-2">
                            <div class="form-group">
                                <label for="celularav">Teléfono</label>
                                <p>{{ $cliente->celularav }}</p>
                            </div>
                        </div>
                        <div class="col-2">
                            <div class="form-group">
                                <label for="aval_telefono">Celular</label>
                                <p>{{ $cliente->aval_telefono }}</p>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="observ">Observaciones</label>
                                <p>{{ $cliente->observ }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row justify-content-between">
                    <button type="button" class="btn btn-secondary prev-step">Regresar</button>
                    <button type="submit" class="btn btn-success">Guardar y Finalizar</button>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
