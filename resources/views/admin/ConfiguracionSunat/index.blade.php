@extends('layouts.admin')

@section('title', 'Configuración SUNAT')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-cogs mr-2"></i>Configuración SUNAT
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-info btn-sm me-2" onclick="abrirDiagnostico()">
                    <i class="fas fa-stethoscope"></i> Diagnóstico SUNAT
                </button>
                <a href="{{ route('admin.configuracion-sunat.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nueva Configuración
                </a>
            </div>
        </div>
        
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>RUC</th>
                            <th>Razón Social</th>
                            <th>Ambiente</th>
                            <th>Certificado</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($configuraciones as $config)
                            <tr class="{{ $config->activo ? 'table-success' : '' }}">
                                <td>
                                    <strong>{{ $config->ruc }}</strong>
                                    @if($config->activo)
                                        <span class="badge badge-success badge-sm ml-2">ACTIVA</span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $config->razon_social }}</div>
                                    @if($config->nombre_comercial)
                                        <small class="text-muted">{{ $config->nombre_comercial }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $config->ambiente === 'produccion' ? 'success' : 'warning' }}">
                                        {{ strtoupper($config->ambiente) }}
                                    </span>
                                </td>
                                <td>
                                    @if($config->certificado_nombre)
                                        <i class="fas fa-certificate text-success mr-1"></i>
                                        <small>{{ $config->certificado_nombre }}</small>
                                    @else
                                        <i class="fas fa-times text-danger mr-1"></i>
                                        <small class="text-muted">Sin certificado</small>
                                    @endif
                                </td>
                                <td>
                                    @if($config->activo)
                                        <span class="badge badge-success">Activa</span>
                                    @else
                                        <span class="badge badge-secondary">Inactiva</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.configuracion-sunat.show', $config) }}" 
                                           class="btn btn-info" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <a href="{{ route('admin.configuracion-sunat.edit', $config) }}" 
                                           class="btn btn-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        @if(!$config->activo)
                                            <form action="{{ route('admin.configuracion-sunat.activar', $config) }}" 
                                                  method="POST" style="display: inline-block;">
                                                @csrf
                                                <button type="submit" class="btn btn-success" 
                                                        title="Activar" onclick="return confirm('¿Activar esta configuración?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                        
                                        <button type="button" class="btn btn-primary test-conexion"
                                                title="Probar Conexión" data-id="{{ $config->id }}">
                                            <i class="fas fa-plug"></i>
                                        </button>

                                        <button type="button" class="btn btn-info diagnostico-config"
                                                title="Diagnóstico SUNAT" data-id="{{ $config->id }}">
                                            <i class="fas fa-stethoscope"></i>
                                        </button>
                                        
                                        @if(!$config->activo)
                                            <form action="{{ route('admin.configuracion-sunat.destroy', $config) }}" 
                                                  method="POST" style="display: inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger" 
                                                        title="Eliminar" onclick="return confirm('¿Eliminar esta configuración?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p>No hay configuraciones SUNAT registradas</p>
                                        <a href="{{ route('admin.configuracion-sunat.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus mr-2"></i>Crear Primera Configuración
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    .btn-group-sm > .btn {
        margin-right: 2px;
    }
    .table-success {
        background-color: rgba(40, 167, 69, 0.1) !important;
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Test conexión
    $('.test-conexion').click(function() {
        const configId = $(this).data('id');
        const btn = $(this);
        const originalHtml = btn.html();
        
        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        
        $.ajax({
            url: `/admin/configuracion-sunat/${configId}/test-conexion`,
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
    });
});

// Función para abrir diagnóstico general
function abrirDiagnostico() {
    window.open('/admin/sunat/diagnostico', '_blank');
}

// Función para diagnóstico específico (actualmente redirige al general)
$(document).on('click', '.diagnostico-config', function() {
    const configId = $(this).data('id');
    // Por ahora abre el diagnóstico general, se puede personalizar en el futuro
    window.open('/admin/sunat/diagnostico', '_blank');
});
</script>
@stop