@extends('layouts.admin')
@section('title', 'Feriados y Horarios Especiales')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-calendar-alt mr-2"></i>Feriados y Horarios Especiales</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.index') }}">Asistencia</a></li>
            <li class="breadcrumb-item active">Feriados y Horarios Especiales</li>
        </ol>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Botones de acción -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <a href="{{ route('admin.asistencia.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i>Volver
                            </a>
                        </div>
                        <div>
                            <button type="button" class="btn btn-info mr-2" data-toggle="modal" data-target="#modalImportarFeriados">
                                <i class="fas fa-download mr-1"></i>Importar Feriados Nacionales
                            </button>
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalCrearFeriado">
                                <i class="fas fa-plus mr-1"></i>Nuevo Día Especial
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de feriados -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list mr-2"></i>Días Especiales Configurados
                    </h3>
                </div>
                <div class="card-body">
                    @if($feriados->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th width="50">ID</th>
                                        <th>Fecha</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Área</th>
                                        <th>Horario</th>
                                        <th width="150">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($feriados as $feriado)
                                        <tr>
                                            <td>{{ $feriado->id }}</td>
                                            <td>
                                                <strong>{{ $feriado->fecha->format('d/m/Y') }}</strong><br>
                                                <small class="text-muted">{{ $feriado->fecha->locale('es')->isoFormat('dddd') }}</small>
                                            </td>
                                            <td>
                                                <strong>{{ $feriado->nombre }}</strong>
                                                @if($feriado->descripcion)
                                                    <br><small class="text-muted">{{ Str::limit($feriado->descripcion, 50) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @switch($feriado->tipo)
                                                    @case('feriado')
                                                        <span class="badge badge-danger">
                                                            <i class="fas fa-calendar-times mr-1"></i>Feriado
                                                        </span>
                                                        @break
                                                    @case('medio_dia')
                                                        <span class="badge badge-warning">
                                                            <i class="fas fa-clock mr-1"></i>Medio Día
                                                        </span>
                                                        @break
                                                    @case('especial')
                                                        <span class="badge badge-info">
                                                            <i class="fas fa-star mr-1"></i>Especial
                                                        </span>
                                                        @break
                                                @endswitch
                                            </td>
                                            <td>
                                                @if($feriado->area_laboral_id)
                                                    <span class="badge" style="background-color: {{ $feriado->areaLaboral->color }}; color: white;">
                                                        {{ $feriado->areaLaboral->nombre }}
                                                    </span>
                                                @else
                                                    <span class="badge badge-secondary">Todas las áreas</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($feriado->tipo === 'feriado')
                                                    <span class="text-muted">Sin horario (No laboral)</span>
                                                @else
                                                    <div class="small">
                                                        <i class="fas fa-sign-in-alt text-success mr-1"></i>
                                                        {{ $feriado->hora_entrada }}
                                                        <br>
                                                        <i class="fas fa-sign-out-alt text-danger mr-1"></i>
                                                        {{ $feriado->hora_salida }}
                                                        @if($feriado->inicio_refrigerio && $feriado->fin_refrigerio)
                                                            <br><small class="text-muted">
                                                                Refrigerio: {{ $feriado->inicio_refrigerio }} - {{ $feriado->fin_refrigerio }}
                                                            </small>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            onclick="eliminarFeriado({{ $feriado->id }}, '{{ $feriado->nombre }}')"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginación -->
                        <div class="d-flex justify-content-center">
                            {{ $feriados->withQueryString()->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay días especiales configurados</h5>
                            <p class="text-muted">Puedes crear nuevos días especiales o importar los feriados nacionales.</p>
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalCrearFeriado">
                                <i class="fas fa-plus mr-1"></i>Crear Primer Día Especial
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear feriado -->
<div class="modal fade" id="modalCrearFeriado" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-calendar-plus mr-2"></i>Crear Nuevo Día Especial
                </h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.asistencia.feriados-especiales.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nombre">Nombre del Día <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="fecha" name="fecha" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tipo">Tipo <span class="text-danger">*</span></label>
                                <select class="form-control" id="tipo" name="tipo" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="feriado">Feriado (No laboral)</option>
                                    <option value="medio_dia">Medio Día</option>
                                    <option value="especial">Horario Especial</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="area_laboral_id">Área Laboral</label>
                                <select class="form-control" id="area_laboral_id" name="area_laboral_id">
                                    <option value="">Todas las áreas</option>
                                    @foreach($areas as $area)
                                        <option value="{{ $area->id }}">{{ $area->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Horarios (solo para medio_dia y especial) -->
                    <div id="seccion-horarios" style="display: none;">
                        <hr>
                        <h6><i class="fas fa-clock mr-2"></i>Configuración de Horarios</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="hora_entrada">Hora de Entrada <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="hora_entrada" name="hora_entrada">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="hora_salida">Hora de Salida <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="hora_salida" name="hora_salida">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="inicio_refrigerio">Inicio Refrigerio</label>
                                    <input type="time" class="form-control" id="inicio_refrigerio" name="inicio_refrigerio">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fin_refrigerio">Fin Refrigerio</label>
                                    <input type="time" class="form-control" id="fin_refrigerio" name="fin_refrigerio">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para importar feriados -->
<div class="modal fade" id="modalImportarFeriados" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-download mr-2"></i>Importar Feriados Nacionales
                </h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.asistencia.feriados-especiales.importar') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="anio_importar">Año a Importar</label>
                        <select class="form-control" id="anio_importar" name="anio" required>
                            @for($i = date('Y'); $i <= date('Y')+2; $i++)
                                <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Feriados que se importarán:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Año Nuevo (1 enero)</li>
                            <li>Jueves y Viernes Santo (variables)</li>
                            <li>Día del Trabajador (1 mayo)</li>
                            <li>Día de la Independencia (28 julio)</li>
                            <li>Día de la Batalla de Junín (29 julio)</li>
                            <li>Día de Santa Rosa de Lima (30 agosto)</li>
                            <li>Combate de Angamos (8 octubre)</li>
                            <li>Día de Todos los Santos (1 noviembre)</li>
                            <li>Inmaculada Concepción (8 diciembre)</li>
                            <li>Navidad (25 diciembre)</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-download mr-1"></i>Importar Feriados
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Manejar cambio de tipo para mostrar/ocultar horarios
    $('#tipo').change(function() {
        const tipo = $(this).val();
        
        if (tipo === 'feriado') {
            $('#seccion-horarios').hide();
            $('#hora_entrada, #hora_salida').removeAttr('required');
        } else if (tipo === 'medio_dia' || tipo === 'especial') {
            $('#seccion-horarios').show();
            $('#hora_entrada, #hora_salida').attr('required', 'required');
        } else {
            $('#seccion-horarios').hide();
            $('#hora_entrada, #hora_salida').removeAttr('required');
        }
    });
});

function eliminarFeriado(id, nombre) {
    if (confirm(`¿Estás seguro de que deseas eliminar "${nombre}"?`)) {
        // Crear form dinámico para DELETE
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `{{ route('admin.asistencia.feriados-especiales.index') }}/${id}`;
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = '{{ csrf_token() }}';
        
        form.appendChild(methodInput);
        form.appendChild(tokenInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@stop