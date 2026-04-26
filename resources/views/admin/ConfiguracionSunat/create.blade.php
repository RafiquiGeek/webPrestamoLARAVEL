@extends('layouts.admin')

@section('title', 'Nueva Configuración SUNAT')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-plus mr-2"></i>Nueva Configuración SUNAT
            </h3>
        </div>
        
        <form action="{{ route('admin.configuracion-sunat.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
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

                <!-- Configuración SUNAT -->
                <div class="row">
                    <div class="col-12">
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-server mr-2"></i>Configuración SUNAT
                        </h5>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ruc">RUC *</label>
                            <input type="text" name="ruc" id="ruc" class="form-control" 
                                   value="{{ old('ruc') }}" maxlength="11" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ambiente">Ambiente *</label>
                            <select name="ambiente" id="ambiente" class="form-control" required>
                                <option value="beta" {{ old('ambiente') == 'beta' ? 'selected' : '' }}>Beta (Pruebas)</option>
                                <option value="produccion" {{ old('ambiente') == 'produccion' ? 'selected' : '' }}>Producción</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="usuario_sol">Usuario SOL *</label>
                            <input type="text" name="usuario_sol" id="usuario_sol" class="form-control" 
                                   value="{{ old('usuario_sol') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="clave_sol">Clave SOL *</label>
                            <input type="password" name="clave_sol" id="clave_sol" class="form-control" required>
                        </div>
                    </div>
                </div>

                <!-- Sección Certificado -->
                <div class="card card-outline card-warning mb-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-certificate mr-2"></i>Certificado Digital
                        </h6>
                    </div>
                    <div class="card-body bg-light">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb-0">
                                    <label for="certificado">Archivo Certificado (.pem o .p12) *</label>
                                    <div class="custom-file">
                                        <input type="file" name="certificado" id="certificado" class="custom-file-input" 
                                               accept=".pem,.p12">
                                        <label class="custom-file-label" for="certificado">Seleccionar certificado...</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-0">
                                    <label for="certificado_clave">Contraseña</label>
                                    <input type="password" name="certificado_clave" id="certificado_clave" class="form-control" 
                                           value="{{ old('certificado_clave') }}" placeholder="Solo para .p12">
                                </div>
                            </div>
                        </div>
                        <small class="form-text text-muted mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            <strong>Importante:</strong> Los certificados .p12 requieren contraseña, los .pem generalmente no.
                        </small>
                    </div>
                </div>

                <hr>

                <!-- Datos de la Empresa -->
                <div class="row">
                    <div class="col-12">
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-building mr-2"></i>Datos de la Empresa
                        </h5>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="razon_social">Razón Social *</label>
                            <input type="text" name="razon_social" id="razon_social" class="form-control" 
                                   value="{{ old('razon_social') }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="nombre_comercial">Nombre Comercial</label>
                            <input type="text" name="nombre_comercial" id="nombre_comercial" class="form-control" 
                                   value="{{ old('nombre_comercial') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="direccion">Dirección *</label>
                            <input type="text" name="direccion" id="direccion" class="form-control" 
                                   value="{{ old('direccion') }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="ubigeo">Ubigeo *</label>
                            <input type="text" name="ubigeo" id="ubigeo" class="form-control" 
                                   value="{{ old('ubigeo', '150101') }}" maxlength="6" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="distrito">Distrito *</label>
                            <input type="text" name="distrito" id="distrito" class="form-control" 
                                   value="{{ old('distrito', 'Lima') }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="provincia">Provincia *</label>
                            <input type="text" name="provincia" id="provincia" class="form-control" 
                                   value="{{ old('provincia', 'Lima') }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="departamento">Departamento *</label>
                            <input type="text" name="departamento" id="departamento" class="form-control" 
                                   value="{{ old('departamento', 'Lima') }}" required>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Series por Defecto -->
                <div class="row">
                    <div class="col-12">
                        <h5 class="text-primary mb-3">
                            <i class="fas fa-hashtag mr-2"></i>Series por Defecto
                        </h5>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="serie_factura">Serie Facturas *</label>
                            <input type="text" name="serie_factura" id="serie_factura" class="form-control" 
                                   value="{{ old('serie_factura', 'F001') }}" maxlength="4" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="serie_boleta">Serie Boletas *</label>
                            <input type="text" name="serie_boleta" id="serie_boleta" class="form-control" 
                                   value="{{ old('serie_boleta', 'B001') }}" maxlength="4" required>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-footer">
                <div class="row">
                    <div class="col-6">
                        <a href="{{ route('admin.configuracion-sunat.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Volver
                        </a>
                    </div>
                    <div class="col-6 text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Guardar Configuración
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Custom file input
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
    });

    // Validación de RUC
    $('#ruc').on('input', function() {
        let ruc = $(this).val();
        if (ruc.length === 11) {
            // Aquí podrías agregar validación de RUC
        }
    });
});
</script>
@stop