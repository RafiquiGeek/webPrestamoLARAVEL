@extends('layouts.admin')

@section('title', 'Ver Configuración SUNAT')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-eye mr-2"></i>Configuración SUNAT
                @if($configuracionSunat->activo)
                    <span class="badge badge-success ml-2">ACTIVA</span>
                @endif
            </h3>
            <div class="card-tools">
                <a href="{{ route('admin.configuracion-sunat.edit', $configuracionSunat) }}" class="btn btn-warning btn-sm">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="{{ route('admin.configuracion-sunat.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Configuración SUNAT -->
            <div class="row">
                <div class="col-12">
                    <h5 class="text-primary mb-3">
                        <i class="fas fa-server mr-2"></i>Configuración SUNAT
                    </h5>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-primary"><i class="fas fa-id-card"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">RUC</span>
                            <span class="info-box-number">{{ $configuracionSunat->ruc }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-{{ $configuracionSunat->ambiente === 'produccion' ? 'success' : 'warning' }}">
                            <i class="fas fa-globe"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Ambiente</span>
                            <span class="info-box-number">{{ strtoupper($configuracionSunat->ambiente) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Usuario SOL:</label>
                        <p class="form-control-static">{{ $configuracionSunat->usuario_sol }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Certificado:</label>
                        <p class="form-control-static">
                            @if($configuracionSunat->certificado_nombre)
                                <i class="fas fa-certificate text-success mr-1"></i>
                                {{ $configuracionSunat->certificado_nombre }}
                            @else
                                <i class="fas fa-times text-danger mr-1"></i>
                                Sin certificado
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Contraseña Certificado:</label>
                        <p class="form-control-static">
                            @if($configuracionSunat->certificado_clave)
                                <i class="fas fa-lock text-success mr-1"></i>
                                Configurada
                            @else
                                <i class="fas fa-unlock text-warning mr-1"></i>
                                No configurada
                            @endif
                        </p>
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

            <div class="row mb-3">
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Razón Social:</label>
                        <p class="form-control-static">{{ $configuracionSunat->razon_social }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Nombre Comercial:</label>
                        <p class="form-control-static">{{ $configuracionSunat->nombre_comercial ?: 'No definido' }}</p>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <div class="form-group">
                        <label>Dirección:</label>
                        <p class="form-control-static">{{ $configuracionSunat->direccion }}</p>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Ubigeo:</label>
                        <p class="form-control-static">{{ $configuracionSunat->ubigeo }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Distrito:</label>
                        <p class="form-control-static">{{ $configuracionSunat->distrito }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Provincia:</label>
                        <p class="form-control-static">{{ $configuracionSunat->provincia }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Departamento:</label>
                        <p class="form-control-static">{{ $configuracionSunat->departamento }}</p>
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

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-file-invoice"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Serie Facturas</span>
                            <span class="info-box-number">{{ $configuracionSunat->serie_factura }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-receipt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Serie Boletas</span>
                            <span class="info-box-number">{{ $configuracionSunat->serie_boleta }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estado y acciones -->
            <div class="row">
                <div class="col-12">
                    @if($configuracionSunat->activo)
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle mr-2"></i>
                            <strong>Configuración Activa:</strong> Esta configuración está siendo utilizada actualmente para la facturación electrónica.
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Configuración Inactiva:</strong> Esta configuración no está activa.
                            <form action="{{ route('admin.configuracion-sunat.activar', $configuracionSunat) }}" method="POST" style="display: inline-block; margin-left: 10px;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('¿Activar esta configuración?')">
                                    <i class="fas fa-check mr-1"></i>Activar
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="card-footer">
            <div class="row">
                <div class="col-6">
                    <small class="text-muted">
                        Creado: {{ $configuracionSunat->created_at->format('d/m/Y H:i') }}
                        @if($configuracionSunat->updated_at != $configuracionSunat->created_at)
                            <br>Actualizado: {{ $configuracionSunat->updated_at->format('d/m/Y H:i') }}
                        @endif
                    </small>
                </div>
                <div class="col-6 text-right">
                    <button type="button" class="btn btn-primary btn-sm" onclick="probarConexion()">
                        <i class="fas fa-plug mr-1"></i>Probar Conexión
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .form-control-static {
        font-weight: 600;
        color: #495057;
        background: #f8f9fa;
        padding: 8px 12px;
        border-radius: 4px;
        border: 1px solid #e9ecef;
    }
    .info-box {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
</style>
@stop

@section('js')
<script>
function probarConexion() {
    const btn = $('button[onclick="probarConexion()"]');
    const originalHtml = btn.html();
    
    btn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Probando...').prop('disabled', true);
    
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