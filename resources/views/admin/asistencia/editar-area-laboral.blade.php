@extends('layouts.admin')
@section('title', 'Editar Área Laboral')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-edit mr-2"></i>Editar Área Laboral</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.index') }}">Asistencia</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.areas-laborales') }}">Áreas Laborales</a></li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-building mr-2"></i>Editar: {{ $area->nombre }}
                    </h3>
                </div>
                
                <form action="{{ route('admin.asistencia.areas-laborales.update', $area) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="nombre">Nombre del Área <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('nombre') is-invalid @enderror" 
                                           id="nombre" 
                                           name="nombre" 
                                           value="{{ old('nombre', $area->nombre) }}"
                                           placeholder="Ej: Recursos Humanos, Contabilidad, Ventas..."
                                           required>
                                    @error('nombre')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="color">Color Identificativo <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="color" 
                                               class="form-control @error('color') is-invalid @enderror" 
                                               id="color" 
                                               name="color" 
                                               value="{{ old('color', $area->color) }}"
                                               required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fas fa-palette"></i>
                                            </span>
                                        </div>
                                    </div>
                                    @error('color')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Este color se usará para identificar visualmente el área
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                      id="descripcion" 
                                      name="descripcion" 
                                      rows="3"
                                      placeholder="Describe las funciones o características de esta área laboral...">{{ old('descripcion', $area->descripcion) }}</textarea>
                            @error('descripcion')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" 
                                       class="custom-control-input" 
                                       id="activo" 
                                       name="activo" 
                                       value="1" 
                                       {{ old('activo', $area->activo) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="activo">
                                    Área activa
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Solo las áreas activas pueden ser asignadas a empleados
                            </small>
                        </div>

                        <!-- Vista previa -->
                        <div class="form-group">
                            <label>Vista Previa</label>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div id="preview-color" 
                                             style="width: 30px; height: 30px; background-color: {{ $area->color }}; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px #ccc; margin-right: 15px;">
                                        </div>
                                        <div>
                                            <h5 id="preview-nombre" class="mb-1">{{ $area->nombre }}</h5>
                                            <p id="preview-descripcion" class="text-muted mb-0">{{ $area->descripcion ?: 'Sin descripción' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información del área -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-info">
                                        <i class="fas fa-calendar-plus"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Creada</span>
                                        <span class="info-box-number">{{ $area->created_at->format('d/m/Y') }}</span>
                                        <small>{{ $area->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-warning">
                                        <i class="fas fa-edit"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Última Modificación</span>
                                        <span class="info-box-number">{{ $area->updated_at->format('d/m/Y') }}</span>
                                        <small>{{ $area->updated_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.asistencia.areas-laborales') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save mr-1"></i>Actualizar Área
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Información de empleados asignados -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users mr-2"></i>Empleados Asignados
                    </h3>
                </div>
                <div class="card-body">
                    @if($area->empleados->count() > 0)
                        <div class="row">
                            @foreach($area->empleados as $empleado)
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <strong>{{ $empleado->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $empleado->codigo }}</small>
                                        </div>
                                        <span class="badge badge-success">Activo</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-user-slash fa-2x mb-2"></i>
                            <p>No hay empleados asignados a esta área</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Advertencias -->
    @if($area->empleados->count() > 0)
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle mr-2"></i>Importante</h5>
                <p class="mb-0">
                    Esta área tiene <strong>{{ $area->empleados->count() }} empleado(s)</strong> asignado(s). 
                    Si desactivas el área, los empleados no podrán registrar su asistencia hasta que sean reasignados a otra área activa.
                </p>
            </div>
        </div>
    </div>
    @endif
</div>
@stop

@section('css')
<style>
.card {
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.form-control:focus {
    border-color: #ffc107;
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
}

#preview-color {
    transition: background-color 0.3s ease;
}

.custom-control-label::before {
    border-radius: 1rem;
}

.custom-control-label::after {
    border-radius: 1rem;
}

.info-box {
    margin-bottom: 15px;
}
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Actualizar vista previa en tiempo real
    function actualizarVistaPrevia() {
        const nombre = $('#nombre').val() || 'Nombre del Área';
        const descripcion = $('#descripcion').val() || 'Sin descripción';
        const color = $('#color').val();
        
        $('#preview-nombre').text(nombre);
        $('#preview-descripcion').text(descripcion);
        $('#preview-color').css('background-color', color);
    }
    
    // Eventos para actualizar vista previa
    $('#nombre, #descripcion, #color').on('input change', actualizarVistaPrevia);
    
    // Colores predefinidos sugeridos
    const coloresSugeridos = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997', '#6c757d'];
    
    // Agregar botones de colores sugeridos
    let coloresHtml = '<div class="mt-2"><small class="text-muted">Colores sugeridos:</small><br>';
    coloresSugeridos.forEach(color => {
        coloresHtml += `<button type="button" class="btn btn-sm m-1 color-btn" style="background-color: ${color}; width: 30px; height: 30px; border-radius: 50%;" data-color="${color}"></button>`;
    });
    coloresHtml += '</div>';
    
    $('#color').parent().after(coloresHtml);
    
    // Evento para botones de colores sugeridos
    $('.color-btn').click(function() {
        const color = $(this).data('color');
        $('#color').val(color);
        actualizarVistaPrevia();
    });
});
</script>
@stop