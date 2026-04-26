@extends('layouts.admin')
@section('title', 'Crear Horario de Trabajo')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-plus mr-2"></i>Crear Horario de Trabajo</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.index') }}">Asistencia</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.horarios-trabajo') }}">Horarios</a></li>
            <li class="breadcrumb-item active">Crear</li>
        </ol>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clock mr-2"></i>Nuevo Horario de Trabajo
                    </h3>
                </div>
                
                <form action="{{ route('admin.asistencia.horarios-trabajo.store') }}" method="POST" id="form-horario">
                    @csrf
                    <div class="card-body">
                        <!-- Información básica -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="nombre">Nombre del Horario <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('nombre') is-invalid @enderror" 
                                           id="nombre" 
                                           name="nombre" 
                                           value="{{ old('nombre') }}"
                                           placeholder="Ej: Horario Administrativo, Turno Mañana, Horario Flexible..."
                                           required>
                                    @error('nombre')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Horarios principales -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="hora_entrada">Hora de Entrada <span class="text-danger">*</span></label>
                                    <input type="time" 
                                           class="form-control @error('hora_entrada') is-invalid @enderror" 
                                           id="hora_entrada" 
                                           name="hora_entrada" 
                                           value="{{ old('hora_entrada', '08:00') }}"
                                           required>
                                    @error('hora_entrada')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="hora_salida">Hora de Salida <span class="text-danger">*</span></label>
                                    <input type="time" 
                                           class="form-control @error('hora_salida') is-invalid @enderror" 
                                           id="hora_salida" 
                                           name="hora_salida" 
                                           value="{{ old('hora_salida', '17:00') }}"
                                           required>
                                    @error('hora_salida')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Refrigerio -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3"><i class="fas fa-coffee text-muted mr-2"></i>Refrigerio</h5>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="duracion_refrigerio_minutos">
                                        Duración permitida <span class="text-muted">(minutos)</span>
                                    </label>
                                    <input type="number" 
                                           class="form-control @error('duracion_refrigerio_minutos') is-invalid @enderror" 
                                           id="duracion_refrigerio_minutos" 
                                           name="duracion_refrigerio_minutos" 
                                           value="{{ old('duracion_refrigerio_minutos') }}"
                                           min="1" 
                                           max="120"
                                           placeholder="30">
                                    @error('duracion_refrigerio_minutos')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="form-text text-muted">Deja vacío si no hay refrigerio</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="inicio_refrigerio">
                                        Hora sugerida <span class="text-muted">(opcional)</span>
                                    </label>
                                    <input type="time" 
                                           class="form-control @error('inicio_refrigerio') is-invalid @enderror" 
                                           id="inicio_refrigerio" 
                                           name="inicio_refrigerio" 
                                           value="{{ old('inicio_refrigerio') }}">
                                    @error('inicio_refrigerio')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="form-text text-muted">El empleado puede tomar refrigerio cuando guste</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fin_refrigerio">
                                        Fin calculado
                                    </label>
                                    <input type="time" 
                                           class="form-control bg-light" 
                                           id="fin_refrigerio" 
                                           name="fin_refrigerio" 
                                           value="{{ old('fin_refrigerio') }}"
                                           readonly>
                                    <small class="form-text text-muted">Se actualiza automáticamente</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-light border">
                                    <small class="text-muted">
                                        <i class="fas fa-lightbulb mr-1"></i>
                                        Los empleados podrán iniciar su refrigerio cuando lo necesiten. El sistema controlará que no excedan el tiempo permitido.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Tolerancias -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3"><i class="fas fa-clock text-muted mr-2"></i>Tolerancias</h5>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tolerancia_entrada">Entrada <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" 
                                               class="form-control @error('tolerancia_entrada') is-invalid @enderror" 
                                               id="tolerancia_entrada" 
                                               name="tolerancia_entrada" 
                                               value="{{ old('tolerancia_entrada', 15) }}"
                                               min="0" 
                                               max="60"
                                               required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">min</span>
                                        </div>
                                    </div>
                                    @error('tolerancia_entrada')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="form-text text-muted">Tiempo de gracia antes de marcar tardanza</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tolerancia_salida">Salida <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" 
                                               class="form-control @error('tolerancia_salida') is-invalid @enderror" 
                                               id="tolerancia_salida" 
                                               name="tolerancia_salida" 
                                               value="{{ old('tolerancia_salida', 15) }}"
                                               min="0" 
                                               max="60"
                                               required>
                                        <div class="input-group-append">
                                            <span class="input-group-text">min</span>
                                        </div>
                                    </div>
                                    @error('tolerancia_salida')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="form-text text-muted">Tiempo de gracia para la salida</small>
                                </div>
                            </div>
                        </div>

                        <!-- Tipo de horario -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3"><i class="fas fa-calendar-alt text-muted mr-2"></i>Configuración por días</h5>
                            </div>
                            <div class="col-12">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card border-0 bg-light h-100">
                                            <div class="card-body text-center">
                                                <div class="custom-control custom-radio">
                                                    <input type="radio" 
                                                           class="custom-control-input" 
                                                           id="horario_simple" 
                                                           name="tipo_configuracion" 
                                                           value="simple"
                                                           {{ old('tipo_configuracion', 'simple') === 'simple' ? 'checked' : '' }}>
                                                    <label class="custom-control-label w-100" for="horario_simple">
                                                        <i class="fas fa-clone fa-2x text-primary mb-2 d-block"></i>
                                                        <strong>Horario Simple</strong>
                                                        <p class="text-muted small mb-0">Mismo horario para todos los días seleccionados</p>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-0 bg-light h-100">
                                            <div class="card-body text-center">
                                                <div class="custom-control custom-radio">
                                                    <input type="radio" 
                                                           class="custom-control-input" 
                                                           id="horario_personalizado" 
                                                           name="tipo_configuracion" 
                                                           value="personalizado"
                                                           {{ old('tipo_configuracion') === 'personalizado' ? 'checked' : '' }}>
                                                    <label class="custom-control-label w-100" for="horario_personalizado">
                                                        <i class="fas fa-calendar-week fa-2x text-success mb-2 d-block"></i>
                                                        <strong>Horario Personalizado</strong>
                                                        <p class="text-muted small mb-0">Diferentes horarios por día (ej: L-V 8-18, S 9-12)</p>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Días laborales - Solo para horario simple -->
                        <div class="row" id="seccion-dias-simples">
                            <div class="col-12">
                                <h6 class="mb-3">Días laborales <span class="text-danger">*</span></h6>
                            </div>
                            <div class="col-12">
                                <div class="row">
                                    @php
                                        $diasSemana = [
                                            '1' => ['nombre' => 'Lunes', 'corto' => 'L'],
                                            '2' => ['nombre' => 'Martes', 'corto' => 'M'], 
                                            '3' => ['nombre' => 'Miércoles', 'corto' => 'X'],
                                            '4' => ['nombre' => 'Jueves', 'corto' => 'J'],
                                            '5' => ['nombre' => 'Viernes', 'corto' => 'V'],
                                            '6' => ['nombre' => 'Sábado', 'corto' => 'S'],
                                            '0' => ['nombre' => 'Domingo', 'corto' => 'D']
                                        ];
                                        $diasSeleccionados = old('dias_laborales', ['1', '2', '3', '4', '5']);
                                    @endphp
                                    @foreach($diasSemana as $valor => $dia)
                                        <div class="col-md-2 col-4 mb-2">
                                            <div class="card border h-100 day-selector">
                                                <div class="card-body text-center p-2">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" 
                                                               class="custom-control-input" 
                                                               id="dia_{{ $valor }}" 
                                                               name="dias_laborales[]" 
                                                               value="{{ $valor }}"
                                                               {{ in_array($valor, $diasSeleccionados) ? 'checked' : '' }}>
                                                        <label class="custom-control-label w-100" for="dia_{{ $valor }}">
                                                            <div class="day-letter">{{ $dia['corto'] }}</div>
                                                            <small>{{ $dia['nombre'] }}</small>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('dias_laborales')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted mt-2">Selecciona los días en que este horario aplica</small>
                            </div>
                        </div>

                        <!-- Horarios personalizados por día -->
                        <div class="form-group" id="seccion-horarios-personalizados" style="display: none;">
                            <label>Configuración por Días <span class="text-danger">*</span></label>
                            <div class="card">
                                <div class="card-body">
                                    @php
                                        $diasCompletos = [
                                            '1' => 'Lunes',
                                            '2' => 'Martes', 
                                            '3' => 'Miércoles',
                                            '4' => 'Jueves',
                                            '5' => 'Viernes',
                                            '6' => 'Sábado',
                                            '0' => 'Domingo'
                                        ];
                                    @endphp
                                    
                                    @foreach($diasCompletos as $valor => $nombre)
                                    <div class="row border-bottom pb-3 mb-3" id="dia-config-{{ $valor }}">
                                        <div class="col-md-2">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" 
                                                       class="custom-control-input dia-activo" 
                                                       id="dia_activo_{{ $valor }}" 
                                                       name="horarios_semanales[{{ $valor }}][activo]" 
                                                       value="1">
                                                <label class="custom-control-label font-weight-bold" for="dia_activo_{{ $valor }}">
                                                    {{ $nombre }}
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mb-0">
                                                <label class="small">Entrada</label>
                                                <input type="time" 
                                                       class="form-control form-control-sm" 
                                                       name="horarios_semanales[{{ $valor }}][hora_entrada]"
                                                       disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group mb-0">
                                                <label class="small">Salida</label>
                                                <input type="time" 
                                                       class="form-control form-control-sm" 
                                                       name="horarios_semanales[{{ $valor }}][hora_salida]"
                                                       disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="row">
                                                <div class="col-4">
                                                    <div class="form-group mb-0">
                                                        <label class="small">Refrigerio (min)</label>
                                                        <input type="number" 
                                                               class="form-control form-control-sm" 
                                                               name="horarios_semanales[{{ $valor }}][duracion_refrigerio_minutos]"
                                                               min="1" max="120"
                                                               placeholder="30"
                                                               disabled>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group mb-0">
                                                        <label class="small">Inicio Sugerido</label>
                                                        <input type="time" 
                                                               class="form-control form-control-sm" 
                                                               name="horarios_semanales[{{ $valor }}][inicio_refrigerio]"
                                                               disabled>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group mb-0">
                                                        <label class="small">Fin Calculado</label>
                                                        <input type="time" 
                                                               class="form-control form-control-sm" 
                                                               name="horarios_semanales[{{ $valor }}][fin_refrigerio]"
                                                               readonly
                                                               disabled>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <small class="text-muted">Replicar este horario en:</small><br>
                                                <button type="button" class="btn btn-sm btn-outline-success replicar-en" data-dia="{{ $valor }}" disabled>
                                                    <i class="fas fa-clone"></i> Replicar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                    
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i> 
                                            Activa los días que deseas configurar y define los horarios específicos para cada uno.
                                            Puedes copiar la configuración de un día a otros usando el botón "Copiar".
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Estado activo -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3"><i class="fas fa-toggle-on text-muted mr-2"></i>Estado</h5>
                            </div>
                            <div class="col-12">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" 
                                           class="custom-control-input" 
                                           id="activo" 
                                           name="activo" 
                                           value="1" 
                                           {{ old('activo', true) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="activo">
                                        <strong>Horario activo</strong>
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Solo los horarios activos pueden ser asignados a empleados
                                </small>
                            </div>
                        </div>

                        <!-- Vista previa -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3"><i class="fas fa-eye text-muted mr-2"></i>Vista Previa</h5>
                            </div>
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <h6 class="text-muted mb-3"><i class="fas fa-clock mr-2"></i>Horario Principal</h6>
                                                <div id="preview-horario">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span><i class="fas fa-sign-in-alt text-success mr-1"></i>Entrada:</span>
                                                        <strong id="preview-entrada">08:00</strong>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span><i class="fas fa-sign-out-alt text-danger mr-1"></i>Salida:</span>
                                                        <strong id="preview-salida">17:00</strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <h6 class="text-muted mb-3"><i class="fas fa-coffee mr-2"></i>Refrigerio</h6>
                                                <div id="preview-refrigerio">
                                                    <span class="text-muted">Sin refrigerio definido</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <h6 class="text-muted mb-3"><i class="fas fa-calendar-week mr-2"></i>Días Laborales</h6>
                                                <div id="preview-dias">
                                                    <!-- Se llenará con JavaScript -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.asistencia.horarios-trabajo') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i>Crear Horario
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
/* Espaciado entre secciones */
.row + .row {
    margin-top: 2rem;
}

/* Títulos de sección */
h5 {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.5rem;
}

/* Selectores de días */
.day-selector {
    cursor: pointer;
    transition: all 0.2s ease;
}

.day-selector:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.day-selector .custom-control-input:checked ~ .custom-control-label {
    color: #007bff;
}

.day-selector .custom-control-input:checked ~ .custom-control-label .day-letter {
    background: #007bff;
    color: white;
}

.day-letter {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 5px;
    background: #f8f9fa;
    font-weight: bold;
    font-size: 14px;
    transition: all 0.2s ease;
}

/* Tarjetas de tipo de horario */
.card.border-0.bg-light {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent !important;
}

.card.border-0.bg-light:hover {
    background-color: #f8f9fa !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.custom-radio input:checked ~ label .card {
    border-color: #007bff !important;
    background-color: #e3f2fd !important;
}

/* Campos de entrada */
.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.bg-light.form-control {
    background-color: #f8f9fa !important;
    border-color: #e9ecef;
}

/* Input groups */
.input-group-text {
    background-color: #f8f9fa;
    border-color: #ced4da;
    color: #6c757d;
    font-size: 0.9rem;
}

/* Vista previa */
#preview-horario .d-flex {
    margin-bottom: 8px;
}

.card.border {
    border-color: #dee2e6 !important;
}

/* Alertas y ayudas */
.alert-light {
    background-color: #f8f9fa;
    border-color: #e9ecef;
}

.form-text.text-muted {
    font-size: 0.8rem;
}

/* Botones */
.btn {
    border-radius: 6px;
    font-weight: 500;
}

/* Separadores visuales */
.mb-4 {
    margin-bottom: 2.5rem !important;
}

/* Iconos en títulos */
h5 i {
    width: 20px;
    text-align: center;
}

/* Hover effects para cards */
.card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}

/* Mejoras responsive */
@media (max-width: 768px) {
    .row + .row {
        margin-top: 1.5rem;
    }
    
    h5 {
        font-size: 1.1rem;
    }
}
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Manejar cambio entre horario simple y personalizado
    $('input[name="tipo_configuracion"]').change(function() {
        const tipoSeleccionado = $(this).val();
        
        if (tipoSeleccionado === 'personalizado') {
            $('#seccion-dias-simples').hide();
            $('#seccion-horarios-personalizados').show();
        } else {
            $('#seccion-dias-simples').show();
            $('#seccion-horarios-personalizados').hide();
        }
    });

    // Manejar activación/desactivación de días en horario personalizado
    $('.dia-activo').change(function() {
        const dia = $(this).attr('id').replace('dia_activo_', '');
        const isChecked = $(this).is(':checked');
        const inputs = $(`#dia-config-${dia} input[type="time"], #dia-config-${dia} input[type="number"], #dia-config-${dia} button`);
        
        if (isChecked) {
            inputs.prop('disabled', false);
        } else {
            inputs.prop('disabled', true).val('');
        }
    });

    // Funcionalidad mejorada de replicar horarios
    $('.replicar-en').click(function() {
        const diaOrigen = $(this).data('dia');
        const nombreDiaOrigen = $(`label[for="dia_activo_${diaOrigen}"]`).text().trim();
        
        // Obtener valores del día origen
        const entrada = $(`input[name="horarios_semanales[${diaOrigen}][hora_entrada]"]`).val();
        const salida = $(`input[name="horarios_semanales[${diaOrigen}][hora_salida]"]`).val();
        const duracionRef = $(`input[name="horarios_semanales[${diaOrigen}][duracion_refrigerio_minutos]"]`).val();
        const inicioRef = $(`input[name="horarios_semanales[${diaOrigen}][inicio_refrigerio]"]`).val();
        const finRef = $(`input[name="horarios_semanales[${diaOrigen}][fin_refrigerio]"]`).val();
        
        // Verificar que el día origen tenga datos
        if (!entrada || !salida) {
            alert('Primero debes configurar completamente este día antes de replicarlo.');
            return;
        }
        
        // Obtener días disponibles (que no sean el origen)
        const diasDisponibles = [];
        $('.dia-activo:checked').each(function() {
            const dia = $(this).attr('id').replace('dia_activo_', '');
            const nombre = $(this).next('label').text().trim();
            if (dia !== diaOrigen) {
                diasDisponibles.push({dia: dia, nombre: nombre});
            }
        });
        
        if (diasDisponibles.length === 0) {
            alert('No hay otros días activos para replicar el horario.');
            return;
        }
        
        // Crear modal personalizado con checkboxes
        const modalHtml = `
            <div id="replicar-modal" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-clone mr-2"></i>Replicar horario de ${nombreDiaOrigen}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted mb-3">
                                <i class="fas fa-info-circle mr-1"></i>
                                Selecciona los días donde quieres aplicar el mismo horario:
                            </p>
                            <div class="horario-origen mb-3 p-3 bg-light rounded">
                                <strong>Horario a replicar:</strong><br>
                                <span class="text-success">Entrada: ${entrada}</span> | 
                                <span class="text-danger">Salida: ${salida}</span>
                                ${duracionRef ? `<br><span class="text-info">Refrigerio: ${duracionRef} min${inicioRef ? ` (sugerido: ${inicioRef})` : ''}</span>` : ''}
                            </div>
                            <div class="dias-destino">
                                ${diasDisponibles.map(d => `
                                    <div class="custom-control custom-checkbox mb-2">
                                        <input type="checkbox" class="custom-control-input dia-destino" id="destino_${d.dia}" value="${d.dia}">
                                        <label class="custom-control-label" for="destino_${d.dia}">
                                            <strong>${d.nombre}</strong>
                                        </label>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-success" id="confirmar-replicar">
                                <i class="fas fa-clone mr-1"></i>Replicar Horario
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Eliminar modal previo si existe
        $('#replicar-modal').remove();
        
        // Agregar modal al DOM y mostrarlo
        $('body').append(modalHtml);
        $('#replicar-modal').modal('show');
        
        // Manejar confirmación
        $('#confirmar-replicar').click(function() {
            const diasSeleccionados = [];
            $('.dia-destino:checked').each(function() {
                diasSeleccionados.push($(this).val());
            });
            
            if (diasSeleccionados.length === 0) {
                alert('Selecciona al menos un día destino.');
                return;
            }
            
            // Aplicar horario a días seleccionados
            diasSeleccionados.forEach(diaDestino => {
                $(`input[name="horarios_semanales[${diaDestino}][hora_entrada]"]`).val(entrada);
                $(`input[name="horarios_semanales[${diaDestino}][hora_salida]"]`).val(salida);
                $(`input[name="horarios_semanales[${diaDestino}][duracion_refrigerio_minutos]"]`).val(duracionRef);
                $(`input[name="horarios_semanales[${diaDestino}][inicio_refrigerio]"]`).val(inicioRef);
                $(`input[name="horarios_semanales[${diaDestino}][fin_refrigerio]"]`).val(finRef);
            });
            
            $('#replicar-modal').modal('hide');
            
            // Mostrar mensaje de confirmación
            const diasNombres = diasSeleccionados.map(dia => {
                return diasDisponibles.find(d => d.dia === dia).nombre;
            }).join(', ');
            
            alert(`✅ Horario replicado exitosamente en: ${diasNombres}`);
        });
    });

    // Actualizar vista previa en tiempo real
    function actualizarVistaPrevia() {
        // Horario principal
        const entrada = $('#hora_entrada').val() || '08:00';
        const salida = $('#hora_salida').val() || '17:00';
        $('#preview-entrada').text(entrada);
        $('#preview-salida').text(salida);
        
        // Refrigerio
        const duracionRefrigerio = $('#duracion_refrigerio_minutos').val();
        const inicioRefrigerio = $('#inicio_refrigerio').val();
        const finRefrigerio = $('#fin_refrigerio').val();
        
        if (duracionRefrigerio) {
            let refrigerioHtml = `
                <div class="d-flex justify-content-between">
                    <span>Duración:</span>
                    <strong>${duracionRefrigerio} minutos</strong>
                </div>
            `;
            
            if (inicioRefrigerio) {
                refrigerioHtml += `
                    <div class="d-flex justify-content-between">
                        <span>Inicio sugerido:</span>
                        <strong>${inicioRefrigerio}</strong>
                    </div>
                `;
                
                if (finRefrigerio) {
                    refrigerioHtml += `
                        <div class="d-flex justify-content-between">
                            <span>Fin calculado:</span>
                            <strong>${finRefrigerio}</strong>
                        </div>
                    `;
                }
            } else {
                refrigerioHtml += '<div class="text-muted small">El empleado puede iniciar cuando desee</div>';
            }
            
            $('#preview-refrigerio').html(refrigerioHtml);
        } else {
            $('#preview-refrigerio').html('<span class="text-muted">Sin refrigerio definido</span>');
        }
        
        // Días laborales
        const diasSeleccionados = [];
        $('input[name="dias_laborales[]"]:checked').each(function() {
            const valor = $(this).val();
            const label = $(`label[for="dia_${valor}"]`).text();
            diasSeleccionados.push(label.substring(0, 3)); // Abreviar días
        });
        
        if (diasSeleccionados.length > 0) {
            let diasHtml = '';
            diasSeleccionados.forEach(dia => {
                diasHtml += `<span class="badge badge-success mr-1">${dia}</span>`;
            });
            $('#preview-dias').html(diasHtml);
        } else {
            $('#preview-dias').html('<span class="text-muted">Ningún día seleccionado</span>');
        }
    }
    
    // Función para calcular fin de refrigerio
    function calcularFinRefrigerio() {
        const duracion = $('#duracion_refrigerio_minutos').val();
        const inicio = $('#inicio_refrigerio').val();
        
        if (duracion && inicio) {
            // Convertir hora a minutos
            const [horas, minutos] = inicio.split(':').map(Number);
            const inicioEnMinutos = horas * 60 + minutos;
            
            // Agregar duración
            const finEnMinutos = inicioEnMinutos + parseInt(duracion);
            
            // Convertir de vuelta a formato HH:MM
            const horasFin = Math.floor(finEnMinutos / 60);
            const minutosFin = finEnMinutos % 60;
            
            const finCalculado = `${horasFin.toString().padStart(2, '0')}:${minutosFin.toString().padStart(2, '0')}`;
            $('#fin_refrigerio').val(finCalculado);
        } else {
            $('#fin_refrigerio').val('');
        }
        
        actualizarVistaPrevia();
    }
    
    // Eventos para actualizar vista previa y cálculos
    $('#hora_entrada, #hora_salida').on('change', actualizarVistaPrevia);
    $('#duracion_refrigerio_minutos, #inicio_refrigerio').on('change input', calcularFinRefrigerio);
    $('#fin_refrigerio').on('change', actualizarVistaPrevia);
    $('input[name="dias_laborales[]"]').on('change', actualizarVistaPrevia);
    
    // Inicializar vista previa
    actualizarVistaPrevia();
    
    // Validación del refrigerio
    $('#inicio_refrigerio, #fin_refrigerio').on('change', function() {
        const inicio = $('#inicio_refrigerio').val();
        const fin = $('#fin_refrigerio').val();
        
        if ((inicio && !fin) || (!inicio && fin)) {
            if (inicio && !fin) {
                $('#fin_refrigerio').attr('required', true);
            } else {
                $('#inicio_refrigerio').attr('required', true);
            }
        } else {
            $('#inicio_refrigerio, #fin_refrigerio').removeAttr('required');
        }
    });
    
    // Validación de días laborales
    $('#form-horario').on('submit', function(e) {
        const diasSeleccionados = $('input[name="dias_laborales[]"]:checked').length;
        
        if (diasSeleccionados === 0) {
            e.preventDefault();
            alert('Debes seleccionar al menos un día laboral.');
            return false;
        }
        
        return true;
    });
    
    // Horarios predefinidos
    const horariosComunes = [
        { nombre: 'Administrativo Estándar', entrada: '08:00', salida: '17:00', refrigerio_inicio: '12:00', duracion_refrigerio: 60 },
        { nombre: 'Turno Mañana', entrada: '06:00', salida: '14:00', refrigerio_inicio: '10:00', duracion_refrigerio: 30 },
        { nombre: 'Turno Tarde', entrada: '14:00', salida: '22:00', refrigerio_inicio: '18:00', duracion_refrigerio: 30 },
        { nombre: 'Medio Tiempo', entrada: '08:00', salida: '12:00', refrigerio_inicio: '', duracion_refrigerio: '' }
    ];
    
    // Agregar botones de horarios predefinidos
    let horariosHtml = `
        <div class="row mt-3">
            <div class="col-12">
                <h6 class="mb-2 text-muted"><i class="fas fa-magic mr-2"></i>Horarios predefinidos</h6>
                <div class="d-flex flex-wrap gap-2">
    `;
    
    horariosComunes.forEach((horario, index) => {
        horariosHtml += `<button type="button" class="btn btn-sm btn-outline-secondary horario-predefinido mb-2" data-horario='${JSON.stringify(horario)}'><i class="fas fa-clock mr-1"></i>${horario.nombre}</button>`;
    });
    
    horariosHtml += `
                </div>
                <small class="form-text text-muted">Haz clic en cualquier horario para aplicarlo automáticamente</small>
            </div>
        </div>
    `;
    
    $('#nombre').closest('.row').after(horariosHtml);
    
    // Evento para botones de horarios predefinidos
    $('.horario-predefinido').click(function() {
        const horario = JSON.parse($(this).attr('data-horario'));
        
        $('#nombre').val(horario.nombre);
        $('#hora_entrada').val(horario.entrada);
        $('#hora_salida').val(horario.salida);
        $('#inicio_refrigerio').val(horario.refrigerio_inicio);
        $('#duracion_refrigerio_minutos').val(horario.duracion_refrigerio);
        
        // Calcular fin de refrigerio automáticamente
        calcularFinRefrigerio();
    });
});
</script>
@stop