@section('content')
<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h4>Paso <span id="step-title">1</span></h4>
        </div>
        <div class="card-body">
            <form id="multi-step-form" action="{{ route('form.save') }}" method="POST">
                @csrf

                <!-- Paso 1: Datos del Cliente -->
                <div id="step-1" class="form-step">
                    <h5>Datos del Cliente</h5>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="nDocumento">Numero de documento</label>
                                <input type="number" class="form-control" name="nDocumento" id="nDocumento" value="{{ $cliente->documento }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="nombres">Nombres</label>
                                <input type="text" class="form-control" name="nombres" id="nombres" value="{{ $cliente->nombres }}">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="aPaterno">Apellido Paterno</label>
                                <input type="text" class="form-control" name="aPaterno" id="aPaterno" value="{{ $cliente->ape_pat }}">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="aMaterno">Apellido Materno</label>
                                <input type="text" class="form-control" name="aMaterno" id="aMaterno" value="{{ $cliente->ape_mat }}">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="telefono">Telefono</label>
                                <input type="number" class="form-control" name="telefono" id="telefono" value="{{ $cliente->telefono }}">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary next-step">Guardar y Continuar</button>
                </div>

                <!-- Paso 2: Datos de Residencia del Cliente -->
                <div id="step-2" class="form-step d-none">
                    <h5>Datos de Residencia del Cliente</h5>
                    <div class="row">
                        <div class="col-3">
                            <div class="form-group">
                                <label for="departamento">Departamento</label>
                                <select class="form-control" id="departamento" name="departamento">
                                    <option value="" {{ old('sucursal', $cliente->departamento) == '' ? ' selected' : '' }}>Selecciona</option>
                                    @foreach ($departamentos as $departamento)
                                        <option value="{{ $departamento->id }}" {{ old('sucursal', $cliente->departamento) == $departamento->id ? ' selected' : '' }}>{{ $departamento->departamento }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="form-group">
                                <label for="provincia">Provincia</label>
                                <select class="form-control" id="provincia" name="provincia">
                                    <option value="" {{ old('sucursal', $cliente->provincia) == '' ? ' selected' : '' }}>Selecciona</option>
                                    @foreach ($provincias as $provincia)
                                        <option value="{{ $provincia->id }}" {{ old('sucursal', $cliente->provincia) == $provincia->id ? ' selected' : '' }}>{{ $provincia->provincia }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-2">
                            <div class="form-group">
                                <label for="distrito">Distrito</label>
                                <select class="form-control" id="distrito" name="distrito">
                                    <option value="" {{ old('sucursal', $cliente->distrito) == '' ? ' selected' : '' }}>Selecciona</option>
                                    @foreach ($distritos as $distrito)
                                        <option value="{{ $distrito->id }}" {{ old('distrito', $cliente->distrito) == $distrito->id ? ' selected' : '' }}>{{ $distrito->distrito }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-2">
                            <div class="form-group">
                                <label for="zona">Zona</label>
                                <select class="form-control" id="zona" name="zona">
                                    <option value="" {{ old('sucursal', $cliente->zona) == '' ? ' selected' : '' }}>Selecciona</option>
                                    @foreach ($zonas as $zona)
                                        <option value="{{ $zona->id }}" {{ old('sucursal', $cliente->zona) == $zona->id ? ' selected' : '' }}>{{ $zona->zona }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-2">
                            <div class="form-group">
                                <label for="tZona">Tipo de Zona</label>
                                <div class="input-group mb-3">
                                    <select class="custom-select" id="tZona" name="tZona">
                                        <option value="" {{ old('sucursal', $cliente->zona) == '' ? ' selected' : '' }}>Selecciona</option>
                                        @foreach ($zonas as $zona)
                                            <option value="{{ $zona->id }}" {{ old('sucursal', $cliente->zona) == $zona->id ? ' selected' : '' }}>{{ $zona->tipo }}</option>
                                        @endforeach
                                    </select>
                                    <div class="input-group-append">
                                        <a data-toggle="modal" data-target="#agregarZona-modal" class="btn btn-outline-info"><i class="fa-solid fa-circle-plus"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="nLotes">Numero de lote</label>
                                <input type="text" class="form-control" name="nLotes" id="nLotes" value="{{ $cliente->nlote }}">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="direcDomicilio">Direccion de domicilio</label>
                                <input type="text" class="form-control" name="direcDomicilio" id="direcDomicilio" value="{{ $cliente->direccion }}">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="referDomiciliaria">Referencia domiciliaria</label>
                                <input type="text" class="form-control" name="referDomiciliaria" id="referDomiciliaria" value="{{ $cliente->referencia }}">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary next-step">Guardar y Continuar</button>
                </div>

                <!-- Paso 3: Datos Bancarios del Cliente -->
                <div id="step-3" class="form-step d-none">
                    <h5>Datos Bancarios del Cliente</h5>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="tCuenta">Tipo de Cuenta</label>
                                <select class="form-control" id="tCuenta" name="tCuenta">
                                    <option value="" {{ old('tCuenta', $cliente->tipoCuenta) == '' ? ' selected' : '' }}>Selecciona</option>
                                    @foreach ($tipoCuentas as $tipoCuenta)
                                        <option value="{{ $tipoCuenta->id }}" {{ old('tCuenta', $cliente->tipoCuenta) == $tipoCuenta->id ? ' selected' : '' }}>{{ $tipoCuenta->tipo }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="aval">Aval</label>
                                <select class="form-control" id="aval" name="aval">
                                    @if($cliente->aval == '0')
                                        <option value="0" selected>No</option>
                                        <option value="1">Si</option>
                                    @else
                                        <option value="0">No</option>
                                        <option value="1" selected>Si</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-12 mt-2" id="finanzas-section" hidden>
                            <h3 class="text-secondary"><strong>FINANZAS</strong></h3>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="entidadFinanciera">Entidad Financiera</label>
                                        <select class="form-control" id="entidadFinanciera" name="entidadFinanciera">
                                            <option value="" {{ old('sucursal', $cliente->entidad) == '' ? ' selected' : '' }}>Selecciona</option>
                                            @foreach ($entBancarias as $entBancaria)
                                                <option value="{{ $entBancaria->id }}" {{ old('sucursal', $cliente->entidad) == $entBancaria->id ? ' selected' : '' }}>{{ $entBancaria->banco }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="f_nCuenta">Numero de cuenta</label>
                                        <input type="number" class="form-control" name="f_nCuenta" id="f_nCuenta" value="{{ $cliente->cuentafi }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mt-2" id="cuenta-terceros-section" hidden>
                            <h3 class="text-secondary"><strong>CUENTA TERCEROS</strong></h3>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="ct_eFinanciera">Entidad Financiera</label>
                                        <select class="form-control" id="ct_eFinanciera" name="ct_eFinanciera">
                                            <option value="" {{ old('sucursal', $cliente->entidadter) == '' ? ' selected' : '' }}>Selecciona</option>
                                            @foreach ($entBancarias as $entBancaria)
                                                <option value="{{ $entBancaria->id }}" {{ old('sucursal', $cliente->entidadter) == $entBancaria->id ? ' selected' : '' }}>{{ $entBancaria->banco }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="ct_nCuenta">Numero de cuenta</label>
                                        <input type="number" class="form-control" name="ct_nCuenta" id="ct_nCuenta" value="{{ $cliente->cuentater }}">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="ct_Titular">Titular</label>
                                        <input type="text" class="form-control" name="ct_Titular" id="ct_Titular" value="{{ $cliente->titularter }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary next-step">Guardar y Continuar</button>
                </div>

                <!-- Paso 4: Datos del Cliente -->
                <div id="step-4" class="form-step d-none">
                    <h5>Datos del Cliente</h5>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" name="fecha_nacimiento" id="fecha_nacimiento" value="{{ $cliente->fecha_nacimiento }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="edad">Edad</label>
                                <input type="number" class="form-control" name="edad" id="edad" value="{{ $cliente->edad }}" readonly>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="estado_civil">Estado Civil</label>
                                <select class="form-control" id="estado_civil" name="estado_civil">
                                    <option value="Soltero" {{ old('estado_civil', $cliente->estado_civil) == 'Soltero' ? ' selected' : '' }}>Soltero</option>
                                    <option value="Casado" {{ old('estado_civil', $cliente->estado_civil) == 'Casado' ? ' selected' : '' }}>Casado</option>
                                    <option value="Conviviente" {{ old('estado_civil', $cliente->estado_civil) == 'Conviviente' ? ' selected' : '' }}>Conviviente</option>
                                    <option value="Divorciado" {{ old('estado_civil', $cliente->estado_civil) == 'Divorciado' ? ' selected' : '' }}>Divorciado</option>
                                    <option value="Viudo" {{ old('estado_civil', $cliente->estado_civil) == 'Viudo' ? ' selected' : '' }}>Viudo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="carga_familiar">Carga Familiar</label>
                                <input type="number" class="form-control" name="carga_familiar" id="carga_familiar" value="{{ $cliente->carga_familiar }}">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary next-step">Guardar y Continuar</button>
                </div>

                <!-- Paso 5: Datos de Contacto del Cliente -->
                <div id="step-5" class="form-step d-none">
                    <h5>Datos de Contacto del Cliente</h5>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="celular2">Celular 2</label>
                                <input type="text" class="form-control" name="celular2" id="celular2" value="{{ $cliente->celular2 }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" name="email" id="email" value="{{ $cliente->email }}">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary next-step">Guardar y Continuar</button>
                </div>

                <!-- Paso 6: Datos del Domicilio del Cliente -->
                <div id="step-6" class="form-step d-none">
                    <h5>Datos del Domicilio del Cliente</h5>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="material_inmueble">Material de Inmueble</label>
                                <select class="form-control" id="material_inmueble" name="material_inmueble">
                                    <option value="Propia" {{ old('material_inmueble', $cliente->material_inmueble) == 'Propia' ? ' selected' : '' }}>Propia</option>
                                    <option value="Familiar" {{ old('material_inmueble', $cliente->material_inmueble) == 'Familiar' ? ' selected' : '' }}>Familiar</option>
                                    <option value="Alquilada" {{ old('material_inmueble', $cliente->material_inmueble) == 'Alquilada' ? ' selected' : '' }}>Alquilada</option>
                                    <option value="Otros" {{ old('material_inmueble', $cliente->material_inmueble) == 'Otros' ? ' selected' : '' }}>Otros</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="tiempo_residencia">Tiempo de Residencia</label>
                                <input type="number" class="form-control" name="tiempo_residencia" id="tiempo_residencia" value="{{ $cliente->tiempo_residencia }}">
                                <select class="form-control mt-2" id="tipo_residencia" name="tipo_residencia">
                                    <option value="meses" {{ old('tipo_residencia', $cliente->tipo_residencia) == 'meses' ? ' selected' : '' }}>Meses</option>
                                    <option value="años" {{ old('tipo_residencia', $cliente->tipo_residencia) == 'años' ? ' selected' : '' }}>Años</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="nombre_propietario">Nombre del Propietario</label>
                                <input type="text" class="form-control" name="nombre_propietario" id="nombre_propietario" value="{{ $cliente->nombre_propietario }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="telefono_propietario">Teléfono del Propietario</label>
                                <input type="text" class="form-control" name="telefono_propietario" id="telefono_propietario" value="{{ $cliente->telefono_propietario }}">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary next-step">Guardar y Continuar</button>
                </div>

                <!-- Paso 7: Datos de Trabajo del Cliente -->
                <div id="step-7" class="form-step d-none">
                    <h5>Datos de Trabajo del Cliente</h5>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="actividad_economica">Actividad Económica</label>
                                <select class="form-control" id="actividad_economica" name="actividad_economica">
                                    <option value="Dependiente" {{ old('actividad_economica', $cliente->actividad_economica) == 'Dependiente' ? ' selected' : '' }}>Dependiente</option>
                                    <option value="Independiente" {{ old('actividad_economica', $cliente->actividad_economica) == 'Independiente' ? ' selected' : '' }}>Independiente</option>
                                    <option value="Casa" {{ old('actividad_economica', $cliente->actividad_economica) == 'Casa' ? ' selected' : '' }}>Casa</option>
                                    <option value="Otros" {{ old('actividad_economica', $cliente->actividad_economica) == 'Otros' ? ' selected' : '' }}>Otros</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="nombre_lugar_trabajo">Nombre del Lugar de Trabajo</label>
                                <input type="text" class="form-control" name="nombre_lugar_trabajo" id="nombre_lugar_trabajo" value="{{ $cliente->nombre_lugar_trabajo }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="desempeno_cargo">Desempeño o Cargo</label>
                                <input type="text" class="form-control" name="desempeno_cargo" id="desempeno_cargo" value="{{ $cliente->desempeno_cargo }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="direccion_trabajo">Dirección del Trabajo</label>
                                <input type="text" class="form-control" name="direccion_trabajo" id="direccion_trabajo" value="{{ $cliente->direccion_trabajo }}">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary next-step">Guardar y Continuar</button>
                </div>

                <!-- Paso 8: Datos del Cónyuge del Solicitante -->
                <div id="step-8" class="form-step d-none">
                    <h5>Datos del Cónyuge del Solicitante</h5>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="conyuge_dni">DNI</label>
                                <input type="text" class="form-control" name="conyuge_dni" id="conyuge_dni" value="{{ $cliente->conyuge_dni }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="conyuge_nombres">Apellidos y Nombres</label>
                                <input type="text" class="form-control" name="conyuge_nombres" id="conyuge_nombres" value="{{ $cliente->conyuge_nombres }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="conyuge_telefono">Teléfono</label>
                                <input type="text" class="form-control" name="conyuge_telefono" id="conyuge_telefono" value="{{ $cliente->conyuge_telefono }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="conyuge_actividad">Actividad a la que se Dedica</label>
                                <input type="text" class="form-control" name="conyuge_actividad" id="conyuge_actividad" value="{{ $cliente->conyuge_actividad }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="conyuge_direccion_trabajo">Dirección de Trabajo</label>
                                <input type="text" class="form-control" name="conyuge_direccion_trabajo" id="conyuge_direccion_trabajo" value="{{ $cliente->conyuge_direccion_trabajo }}">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary next-step">Guardar y Continuar</button>
                </div>

                <!-- Paso 9: Datos del Aval -->
                <div id="step-9" class="form-step d-none">
                    <h5>Datos del Aval</h5>
                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label for="av_nDocumento">Numero de documento</label>
                                <input type="number" class="form-control" name="av_nDocumento" id="av_nDocumento" value="{{ $cliente->documentoav }}">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="av_nombres">Nombres</label>
                                <input type="text" class="form-control" name="av_nombres" id="av_nombres" value="{{ $cliente->nombresav }}">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="av_aPaterno">Apellido Paterno</label>
                                <input type="text" class="form-control" name="av_aPaterno" id="av_aPaterno" value="{{ $cliente->ape_patav }}">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="av_aMaterno">Apellido Materno</label>
                                <input type="text" class="form-control" name="av_aMaterno" id="av_aMaterno" value="{{ $cliente->ape_matav }}">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="av_direcDomicilio">Direccion de domicilio</label>
                                <input type="text" class="form-control" name="av_direcDomicilio" id="av_direcDomicilio" value="{{ $cliente->direccionav }}">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label for="av_telefono">Telefono</label>
                                <input type="number" class="form-control" name="av_telefono" id="av_telefono" value="{{ $cliente->celularav }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="aval_dni">DNI</label>
                                <input type="text" class="form-control" name="aval_dni" id="aval_dni" value="{{ $cliente->aval_dni }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="aval_estado_civil">Estado Civil</label>
                                <select class="form-control" id="aval_estado_civil" name="aval_estado_civil">
                                    <option value="Soltero" {{ old('aval_estado_civil', $cliente->aval_estado_civil) == 'Soltero' ? ' selected' : '' }}>Soltero</option>
                                    <option value="Casado" {{ old('aval_estado_civil', $cliente->aval_estado_civil) == 'Casado' ? ' selected' : '' }}>Casado</option>
                                    <option value="Conviviente" {{ old('aval_estado_civil', $cliente->aval_estado_civil) == 'Conviviente' ? ' selected' : '' }}>Conviviente</option>
                                    <option value="Divorciado" {{ old('aval_estado_civil', $cliente->aval_estado_civil) == 'Divorciado' ? ' selected' : '' }}>Divorciado</option>
                                    <option value="Viudo" {{ old('aval_estado_civil', $cliente->aval_estado_civil) == 'Viudo' ? ' selected' : '' }}>Viudo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="aval_fecha_nacimiento">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" name="aval_fecha_nacimiento" id="aval_fecha_nacimiento" value="{{ $cliente->aval_fecha_nacimiento }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="aval_edad">Edad</label>
                                <input type="number" class="form-control" name="aval_edad" id="aval_edad" value="{{ $cliente->aval_edad }}" readonly>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="aval_carga_familiar">Carga Familiar</label>
                                <input type="number" class="form-control" name="aval_carga_familiar" id="aval_carga_familiar" value="{{ $cliente->aval_carga_familiar }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="aval_parentesco">Parentesco</label>
                                <input type="text" class="form-control" name="aval_parentesco" id="aval_parentesco" value="{{ $cliente->aval_parentesco }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="aval_telefono">Teléfono</label>
                                <input type="text" class="form-control" name="aval_telefono" id="aval_telefono" value="{{ $cliente->aval_telefono }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="aval_celular">Celular</label>
                                <input type="text" class="form-control" name="aval_celular" id="aval_celular" value="{{ $cliente->aval_celular }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="aval_email">Email</label>
                                <input type="email" class="form-control" name="aval_email" id="aval_email" value="{{ $cliente->aval_email }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="aval_direccion">Dirección</label>
                                <input type="text" class="form-control" name="aval_direccion" id="aval_direccion" value="{{ $cliente->aval_direccion }}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="aval_referencia">Referencia</label>
                                <input type="text" class="form-control" name="aval_referencia" id="aval_referencia" value="{{ $cliente->aval_referencia }}">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Guardar y Finalizar</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let currentStep = 1;
        const totalSteps = document.querySelectorAll('.form-step').length;
        const stepTitle = document.getElementById('step-title');

        document.querySelectorAll('.next-step').forEach(button => {
            button.addEventListener('click', function () {
                const currentFormStep = document.getElementById(`step-${currentStep}`);
                currentStep++;
                if (currentStep <= totalSteps) {
                    currentFormStep.classList.add('d-none');
                    document.getElementById(`step-${currentStep}`).classList.remove('d-none');
                    stepTitle.textContent = currentStep;
                }
            });
        });
    });
</script>
@endsection
