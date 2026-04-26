@extends('layouts.admin')
@section('title', 'Sin Asignación de Área')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-exclamation-triangle mr-2"></i>Sin Asignación de Área</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.index') }}">Asistencia</a></li>
            <li class="breadcrumb-item active">Sin Asignación</li>
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
                        <i class="fas fa-info-circle mr-2"></i>Información Importante
                    </h3>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-user-times fa-5x text-warning mb-3"></i>
                        <h3>No tienes una asignación de área activa</h3>
                    </div>
                    
                    <div class="alert alert-warning" role="alert">
                        <h5><i class="fas fa-exclamation-triangle mr-2"></i>¿Qué significa esto?</h5>
                        <p class="mb-0">
                            Para poder registrar tu asistencia, necesitas estar asignado a un área laboral 
                            con un horario de trabajo específico. Actualmente no tienes ninguna asignación activa.
                        </p>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-user-tie"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tu Información</span>
                                    <span class="info-box-number">{{ auth()->user()->name }}</span>
                                    <small>{{ auth()->user()->codigo }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-calendar-alt"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Fecha Actual</span>
                                    <span class="info-box-number">{{ \Carbon\Carbon::now()->format('d/m/Y') }}</span>
                                    <small>{{ \Carbon\Carbon::now()->format('H:i:s') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5><i class="fas fa-question-circle mr-2"></i>¿Qué puedes hacer?</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <i class="fas fa-phone fa-2x text-primary mb-2"></i>
                                        <h6>Contacta a tu Supervisor</h6>
                                        <p class="small">Comunícate con tu supervisor directo para solicitar tu asignación de área.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <i class="fas fa-envelope fa-2x text-success mb-2"></i>
                                        <h6>Contacta a Recursos Humanos</h6>
                                        <p class="small">Envía un correo o llama al departamento de RRHH para resolver tu situación.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <i class="fas fa-cogs fa-2x text-warning mb-2"></i>
                                        <h6>Contacta al Administrador</h6>
                                        <p class="small">Si eres nuevo, el administrador del sistema puede asignarte un área.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <div class="btn-group" role="group">
                            <a href="{{ route('admin.asistencia.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i>Volver al Módulo
                            </a>
                            <a href="{{ route('admin.index') }}" class="btn btn-primary">
                                <i class="fas fa-home mr-1"></i>Ir al Dashboard
                            </a>
                            <button type="button" class="btn btn-info" onclick="location.reload()">
                                <i class="fas fa-sync-alt mr-1"></i>Actualizar Página
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información adicional para administradores -->
    @can('admin.asistencia.configurar')
    <div class="row justify-content-center mt-4">
        <div class="col-md-8">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-shield mr-2"></i>Panel de Administrador
                    </h3>
                </div>
                <div class="card-body">
                    <p><strong>Como administrador, puedes resolver esta situación:</strong></p>
                    <div class="row">
                        <div class="col-md-6">
                            <a href="{{ route('admin.asistencia.asignaciones.create') }}" class="btn btn-success btn-block">
                                <i class="fas fa-plus mr-1"></i>Crear Asignación para este Usuario
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('admin.asistencia.asignaciones') }}" class="btn btn-primary btn-block">
                                <i class="fas fa-list mr-1"></i>Ver Todas las Asignaciones
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan
</div>
@stop

@section('css')
<style>
.info-box {
    margin-bottom: 15px;
}

.card {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 10px;
}

.btn-group .btn {
    margin: 0 2px;
}

.fa-5x {
    font-size: 5em;
}
</style>
@stop