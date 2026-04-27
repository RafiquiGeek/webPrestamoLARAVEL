@extends('layouts.admin')

@section('title', 'Editar Configuración SUNAT')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-edit mr-2"></i>Editar Configuración SUNAT
            </h3>
        </div>
        
        <form action="{{ route('admin.configuracion-sunat.update', $configuracionSunat) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
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
                                   value="{{ old('ruc', $configuracionSunat->ruc) }}" maxlength="11" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ambiente">Ambiente *</label>
                            <select name="ambiente" id="ambiente" class="form-control" required>
                                <option value="beta" {{ old('ambiente', $configuracionSunat->ambiente) == 'beta' ? 'selected' : '' }}>Beta (Pruebas)</option>
                                <option value="produccion" {{ old('ambiente', $configuracionSunat->ambiente) == 'produccion' ? 'selected' : '' }}>Producción</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="usuario_sol">Usuario SOL *</label>
                            <input type="text" name="usuario_sol" id="usuario_sol" class="form-control" 
                                   value="{{ old('usuario_sol', $configuracionSunat->usuario_sol) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="clave_sol">Clave SOL *</label>
                            <input type="password" name="clave_sol" id="clave_sol" class="form-control" 
                                   value="{{ old('clave_sol', $configuracionSunat->clave_sol) }}" required>
                            <small class="form-text text-muted">Dejar en blanco para mantener la contraseña actual</small>
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
                                    <label for="certificado">Archivo Certificado (.pem, .p12 o .pfx)</label>
                                    <div class="custom-file">
                                        <input type="file" name="certificado" id="certificado" class="custom-file-input"
                                               accept=".pem,.p12,.pfx">
                                        <label class="custom-file-label" for="certificado">
                                            @if($configuracionSunat->certificado_nombre)
                                                {{ $configuracionSunat->certificado_nombre }}
                                            @else
                                                Seleccionar certificado...
                                            @endif
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-0">
                                    <label for="certificado_clave">Contraseña del certificado</label>
                                    <input type="password" name="certificado_clave" id="certificado_clave" class="form-control"
                                           value="{{ old('certificado_clave') }}" placeholder="Requerida para .p12 / .pfx"
                                           autocomplete="new-password">
                                    <small class="form-text text-muted">Se guardará encriptada. Solo debes llenarla si subes un certificado nuevo.</small>
                                </div>
                            </div>
                        </div>
                        @if($configuracionSunat->certificado_nombre)
                            <div class="mt-2">
                                <small class="form-text text-success">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    <strong>Certificado actual:</strong> {{ $configuracionSunat->certificado_nombre }}
                                </small>
                            </div>
                        @endif
                        <small class="form-text text-muted mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            <strong>Importante:</strong> Los certificados .p12 requieren contraseña. Dejar vacío para mantener el certificado actual.
                        </small>
                    </div>
                </div>
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
                                   value="{{ old('razon_social', $configuracionSunat->razon_social) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="nombre_comercial">Nombre Comercial</label>
                            <input type="text" name="nombre_comercial" id="nombre_comercial" class="form-control" 
                                   value="{{ old('nombre_comercial', $configuracionSunat->nombre_comercial) }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="direccion">Dirección *</label>
                            <input type="text" name="direccion" id="direccion" class="form-control" 
                                   value="{{ old('direccion', $configuracionSunat->direccion) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="ubigeo">Ubigeo *</label>
                            <input type="text" name="ubigeo" id="ubigeo" class="form-control" 
                                   value="{{ old('ubigeo', $configuracionSunat->ubigeo) }}" maxlength="6" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="distrito">Distrito *</label>
                            <input type="text" name="distrito" id="distrito" class="form-control" 
                                   value="{{ old('distrito', $configuracionSunat->distrito) }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="provincia">Provincia *</label>
                            <input type="text" name="provincia" id="provincia" class="form-control" 
                                   value="{{ old('provincia', $configuracionSunat->provincia) }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="departamento">Departamento *</label>
                            <input type="text" name="departamento" id="departamento" class="form-control" 
                                   value="{{ old('departamento', $configuracionSunat->departamento) }}" required>
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
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="serie_factura">Serie Facturas *</label>
                            <input type="text" name="serie_factura" id="serie_factura" class="form-control"
                                   value="{{ old('serie_factura', $configuracionSunat->serie_factura) }}" maxlength="4" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="numero_inicial_factura">Número Inicial *</label>
                            <input type="number" name="numero_inicial_factura" id="numero_inicial_factura" class="form-control"
                                   value="{{ old('numero_inicial_factura', $configuracionSunat->numero_inicial_factura ?? 1) }}" min="1" required>
                            <small class="form-text text-muted">Número desde donde empezar</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="serie_boleta">Serie Boletas *</label>
                            <input type="text" name="serie_boleta" id="serie_boleta" class="form-control"
                                   value="{{ old('serie_boleta', $configuracionSunat->serie_boleta) }}" maxlength="4" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="numero_inicial_boleta">Número Inicial *</label>
                            <input type="number" name="numero_inicial_boleta" id="numero_inicial_boleta" class="form-control"
                                   value="{{ old('numero_inicial_boleta', $configuracionSunat->numero_inicial_boleta ?? 1) }}" min="1" required>
                            <small class="form-text text-muted">Número desde donde empezar</small>
                        </div>
                    </div>
                </div>

                <!-- Estado de la configuración -->
                @if($configuracionSunat->activo)
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i>
                        <strong>Configuración Activa:</strong> Esta configuración está siendo utilizada actualmente para la facturación electrónica.
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Configuración Inactiva:</strong> Esta configuración no está activa. 
                        <a href="javascript:void(0)" onclick="activarConfiguracion()" class="alert-link">Hacer clic aquí para activarla</a>.
                    </div>
                @endif
            </div>
            
            <div class="card-footer">
                <div class="row">
                    <div class="col-6">
                        <a href="{{ route('admin.configuracion-sunat.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Volver
                        </a>
                    </div>
                    <div class="col-6 text-right">
                        <button type="button" class="btn btn-info mr-2" onclick="probarConexion()">
                            <i class="fas fa-plug mr-2"></i>Probar Conexión
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Actualizar Configuración
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Form oculto para activar configuración -->
<form id="activarForm" action="{{ route('admin.configuracion-sunat.activar', $configuracionSunat) }}" method="POST" style="display: none;">
    @csrf
</form>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Custom file input
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass('selected').html(fileName || 'Seleccionar certificado...');
    });

    // Validación de RUC
    $('#ruc').on('input', function() {
        let ruc = $(this).val();
        if (ruc.length === 11) {
            // Aquí podrías agregar validación de RUC
        }
    });
});

function activarConfiguracion() {
    if (confirm('¿Está seguro de activar esta configuración? La configuración activa actual se desactivará.')) {
        document.getElementById('activarForm').submit();
    }
}

function probarConexion() {
    const btn = $('button[onclick="probarConexion()"]');
    const originalHtml = btn.html();
    
    btn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Probando...').prop('disabled', true);
    
    $.ajax({
        url: '{{ route("admin.configuracion-sunat.test-conexion", $configuracionSunat) }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Conexión Exitosa',
                    text: response.message,
                    confirmButtonColor: '#28a745'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: response.message,
                    confirmButtonColor: '#dc3545'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al probar la conexión',
                confirmButtonColor: '#dc3545'
            });
        },
        complete: function() {
            btn.html(originalHtml).prop('disabled', false);
        }
    });
}
</script>
@stop