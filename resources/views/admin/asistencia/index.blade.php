@extends('layouts.admin')
@section('title', 'Módulo de Asistencia')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-clock mr-2"></i>Módulo de Asistencia</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin</a></li>
            <li class="breadcrumb-item active">Asistencia</li>
        </ol>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Tarjetas de navegación -->
    <div class="row">
        <!-- Dashboard -->
        <div class="col-lg-4 col-md-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line mr-2"></i>Dashboard
                    </h3>
                </div>
                <div class="card-body">
                    <p class="card-text">Visualiza estadísticas y resumen de asistencia diaria.</p>
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.asistencia.dashboard') }}" class="btn btn-primary">
                            <i class="fas fa-eye mr-1"></i>Ver Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registro de Asistencia -->
        <div class="col-lg-4 col-md-6">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-fingerprint mr-2"></i>Registro de Asistencia
                    </h3>
                </div>
                <div class="card-body">
                    <p class="card-text">Marca tu entrada y salida diaria.</p>
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.asistencia.registro') }}" class="btn btn-success">
                            <i class="fas fa-clock mr-1"></i>Registrar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reportes -->
        <div class="col-lg-4 col-md-6">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-alt mr-2"></i>Reportes
                    </h3>
                </div>
                <div class="card-body">
                    <p class="card-text">Consulta reportes de asistencia por período y empleado.</p>
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.asistencia.reportes') }}" class="btn btn-info">
                            <i class="fas fa-search mr-1"></i>Ver Reportes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('admin.asistencia.configurar')
    <!-- Configuración (Solo Admin) -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cogs mr-2"></i>Configuración del Sistema
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Áreas Laborales -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="small-box bg-gradient-warning">
                                <div class="inner text-center">
                                    <h4><i class="fas fa-building"></i></h4>
                                    <p>Áreas Laborales</p>
                                </div>
                                <a href="{{ route('admin.asistencia.areas-laborales') }}" class="small-box-footer">
                                    Gestionar <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Horarios -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="small-box bg-gradient-success">
                                <div class="inner text-center">
                                    <h4><i class="fas fa-clock"></i></h4>
                                    <p>Horarios de Trabajo</p>
                                </div>
                                <a href="{{ route('admin.asistencia.horarios-trabajo') }}" class="small-box-footer">
                                    Gestionar <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Asignaciones -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="small-box bg-gradient-primary">
                                <div class="inner text-center">
                                    <h4><i class="fas fa-user-tie"></i></h4>
                                    <p>Asignaciones</p>
                                </div>
                                <a href="{{ route('admin.asistencia.asignaciones') }}" class="small-box-footer">
                                    Gestionar <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Configuración General -->
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="small-box bg-gradient-secondary">
                                <div class="inner text-center">
                                    <h4><i class="fas fa-cog"></i></h4>
                                    <p>Configuración</p>
                                </div>
                                <a href="#" class="small-box-footer">
                                    Próximamente <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    <!-- Información del Sistema -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card card-light">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle mr-2"></i>Información del Sistema
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-check-circle text-success mr-2"></i>Características:</h5>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-angle-right text-primary mr-2"></i>Registro automático de ubicación GPS (oculto)</li>
                                <li><i class="fas fa-angle-right text-primary mr-2"></i>Control de horarios flexibles por área</li>
                                <li><i class="fas fa-angle-right text-primary mr-2"></i>Detección automática de tardanzas</li>
                                <li><i class="fas fa-angle-right text-primary mr-2"></i>Reportes detallados por período</li>
                                <li><i class="fas fa-angle-right text-primary mr-2"></i>Gestión de refrigerios</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-shield-alt text-info mr-2"></i>Seguridad:</h5>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-angle-right text-primary mr-2"></i>Registro de IP de conexión</li>
                                <li><i class="fas fa-angle-right text-primary mr-2"></i>Geolocalización de marcado</li>
                                <li><i class="fas fa-angle-right text-primary mr-2"></i>Control de acceso por roles</li>
                                <li><i class="fas fa-angle-right text-primary mr-2"></i>Auditoría completa de movimientos</li>
                            </ul>
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
.small-box {
    border-radius: 10px;
    transition: transform 0.2s;
}

.small-box:hover {
    transform: translateY(-5px);
}

.card {
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.card-header {
    background: linear-gradient(45deg, #f8f9fa, #e9ecef);
    border-radius: 10px 10px 0 0;
}
</style>
@stop