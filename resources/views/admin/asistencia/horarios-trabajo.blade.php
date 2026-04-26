@extends('layouts.admin')
@section('title', 'Horarios de Trabajo')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-clock mr-2"></i>Horarios de Trabajo</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.index') }}">Asistencia</a></li>
            <li class="breadcrumb-item active">Horarios de Trabajo</li>
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
                            <a href="{{ route('admin.asistencia.horarios-trabajo.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i>Nuevo Horario
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de horarios -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list mr-2"></i>Lista de Horarios de Trabajo
                    </h3>
                </div>
                <div class="card-body">
                    @if($horarios->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th width="50">ID</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Configuración</th>
                                        <th>Tolerancias</th>
                                        <th width="100">Estado</th>
                                        <th width="150">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($horarios as $horario)
                                        <tr>
                                            <td>{{ $horario->id }}</td>
                                            <td>
                                                <strong>{{ $horario->nombre }}</strong>
                                                @if($horario->descripcion_horario)
                                                    <br><small class="text-muted">{{ $horario->descripcion_horario }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($horario->esHorarioPersonalizado())
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-calendar-week mr-1"></i>Personalizado
                                                    </span>
                                                @else
                                                    <span class="badge badge-secondary">
                                                        <i class="fas fa-clock mr-1"></i>Simple
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($horario->esHorarioPersonalizado())
                                                    <div class="small">
                                                        @php
                                                            $diasAbrev = ['0' => 'D', '1' => 'L', '2' => 'M', '3' => 'X', '4' => 'J', '5' => 'V', '6' => 'S'];
                                                        @endphp
                                                        @foreach($horario->horarios_semanales as $dia => $config)
                                                            @if($config['activo'])
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="font-weight-bold">{{ $diasAbrev[$dia] }}:</span>
                                                                    <span>{{ $config['hora_entrada'] }}-{{ $config['hora_salida'] }}</span>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div>
                                                        <i class="fas fa-sign-in-alt text-success mr-1"></i>
                                                        {{ $horario->hora_entrada ? \Carbon\Carbon::parse($horario->hora_entrada)->format('H:i') : 'N/A' }}
                                                        <br>
                                                        <i class="fas fa-sign-out-alt text-danger mr-1"></i>
                                                        {{ $horario->hora_salida ? \Carbon\Carbon::parse($horario->hora_salida)->format('H:i') : 'N/A' }}
                                                    </div>
                                                    @if($horario->inicio_refrigerio && $horario->fin_refrigerio)
                                                        <div class="mt-1">
                                                            <small class="text-muted">
                                                                Refrigerio: {{ \Carbon\Carbon::parse($horario->inicio_refrigerio)->format('H:i') }} - 
                                                                {{ \Carbon\Carbon::parse($horario->fin_refrigerio)->format('H:i') }}
                                                            </small>
                                                        </div>
                                                    @endif
                                                    <div class="mt-1">
                                                        <small class="text-info">{{ $horario->getDiasLaboralesTextAttribute() }}</small>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <small>
                                                    <i class="fas fa-clock text-warning mr-1"></i>
                                                    {{ $horario->tolerancia_entrada }}min entrada
                                                    <br>
                                                    <i class="fas fa-clock text-info mr-1"></i>
                                                    {{ $horario->tolerancia_salida }}min salida
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $horario->activo ? 'success' : 'secondary' }}">
                                                    {{ $horario->activo ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.asistencia.horarios-trabajo.edit', $horario) }}" 
                                                       class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <form action="{{ route('admin.asistencia.horarios-trabajo.toggle', $horario) }}" 
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-{{ $horario->activo ? 'secondary' : 'success' }}"
                                                                onclick="return confirm('¿Estás seguro de {{ $horario->activo ? 'desactivar' : 'activar' }} este horario?')">
                                                            <i class="fas fa-{{ $horario->activo ? 'eye-slash' : 'eye' }}"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        @if($horarios->hasPages())
                            <div class="mt-3">
                                {{ $horarios->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No hay horarios de trabajo registrados</h4>
                            <p class="text-muted">Comienza creando tu primer horario de trabajo.</p>
                            <a href="{{ route('admin.asistencia.horarios-trabajo.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i>Crear Primer Horario
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Información adicional -->
    <div class="row">
        <div class="col-12">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle mr-2"></i>Información sobre Horarios
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-lightbulb mr-2"></i>Características:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success mr-2"></i>Horarios flexibles por días de la semana</li>
                                <li><i class="fas fa-check text-success mr-2"></i>Tolerancias personalizables para entrada y salida</li>
                                <li><i class="fas fa-check text-success mr-2"></i>Gestión de horarios de refrigerio</li>
                                <li><i class="fas fa-check text-success mr-2"></i>Detección automática de tardanzas</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-info-circle mr-2"></i>Días de la semana:</h6>
                            <div class="row">
                                <div class="col-6">
                                    <ul class="list-unstyled">
                                        <li><span class="badge badge-secondary mr-2">0</span>Domingo</li>
                                        <li><span class="badge badge-success mr-2">1</span>Lunes</li>
                                        <li><span class="badge badge-success mr-2">2</span>Martes</li>
                                        <li><span class="badge badge-success mr-2">3</span>Miércoles</li>
                                    </ul>
                                </div>
                                <div class="col-6">
                                    <ul class="list-unstyled">
                                        <li><span class="badge badge-success mr-2">4</span>Jueves</li>
                                        <li><span class="badge badge-success mr-2">5</span>Viernes</li>
                                        <li><span class="badge badge-secondary mr-2">6</span>Sábado</li>
                                    </ul>
                                </div>
                            </div>
                            <small class="text-muted">Verde = días laborales típicos, Gris = fines de semana</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
.table th {
    background-color: #f8f9fa;
    border-top: none;
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.badge {
    font-size: 0.75rem;
}

.card {
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
</style>
@stop