@extends('layouts.admin')

@section('title', 'Diagnóstico SUNAT')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0">
                <i class="fas fa-stethoscope mr-2"></i>Diagnóstico SUNAT
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-light btn-sm" onclick="recargarDiagnostico()">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>
                <a href="{{ route('admin.configuracion-sunat.index') }}" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Estado General -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-server me-2"></i>
                        Estado General
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-circle {{ $data['estado_general']['activo'] ? 'text-success' : 'text-danger' }} me-2"></i>
                                <span>Estado: <strong>{{ $data['estado_general']['activo'] ? 'ACTIVO' : 'INACTIVO' }}</strong></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-globe {{ $data['estado_general']['ambiente'] === 'produccion' ? 'text-success' : 'text-warning' }} me-2"></i>
                                <span>Ambiente: <strong>{{ strtoupper($data['estado_general']['ambiente'] ?? 'NO CONFIGURADO') }}</strong></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-id-card text-info me-2"></i>
                                <span>RUC: <strong>{{ $data['estado_general']['ruc'] ?? 'NO CONFIGURADO' }}</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        Configuración SUNAT
                    </h6>
                </div>
                <div class="card-body">
                    @foreach($data['configuracion'] as $item)
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <i class="fas {{ $item['estado'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} me-2"></i>
                                {{ $item['nombre'] }}
                            </div>
                            <div class="col-md-6">
                                <span class="badge bg-{{ $item['estado'] ? 'success' : 'danger' }}">
                                    {{ $item['valor'] ?? ($item['estado'] ? 'Configurado' : 'No configurado') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Certificados y Permisos -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-certificate me-2"></i>
                        Certificados y Permisos
                    </h6>
                </div>
                <div class="card-body">
                    @foreach($data['certificados_permisos'] as $item)
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <i class="fas {{ $item['estado'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} me-2"></i>
                                {{ $item['nombre'] }}
                            </div>
                            <div class="col-md-6">
                                <span class="badge bg-{{ $item['estado'] ? 'success' : 'danger' }}">
                                    {{ $item['descripcion'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Conectividad -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-wifi me-2"></i>
                        Conectividad
                    </h6>
                </div>
                <div class="card-body">
                    @foreach($data['conectividad'] as $item)
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <i class="fas {{ $item['estado'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} me-2"></i>
                                {{ $item['nombre'] }}
                            </div>
                            <div class="col-md-6">
                                <span class="badge bg-{{ $item['estado'] ? 'success' : 'danger' }}">
                                    {{ $item['descripcion'] }}
                                </span>
                                @if(isset($item['tiempo']))
                                    <small class="text-muted d-block">{{ $item['tiempo'] }}ms</small>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Resumen y Recomendaciones -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Resumen y Recomendaciones
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        $alertClass = 'success';
                        $alertIcon = 'fa-check-circle';

                        if ($data['resumen']['criticos'] > 0) {
                            $alertClass = 'danger';
                            $alertIcon = 'fa-exclamation-triangle';
                        } elseif ($data['resumen']['advertencias'] > 0) {
                            $alertClass = 'warning';
                            $alertIcon = 'fa-exclamation-circle';
                        }
                    @endphp

                    <div class="alert alert-{{ $alertClass }}">
                        <h6 class="alert-heading">
                            <i class="fas {{ $alertIcon }} me-2"></i>
                            {{ $data['resumen']['mensaje'] }}
                        </h6>
                        <p class="mb-0">
                            <strong>Estado:</strong> {{ $data['resumen']['total_verificaciones'] }} verificaciones realizadas -
                            {{ $data['resumen']['exitosas'] }} exitosas, {{ $data['resumen']['advertencias'] }} advertencias, {{ $data['resumen']['criticos'] }} críticos
                        </p>
                    </div>

                    @if(count($data['recomendaciones']) > 0)
                        <h6 class="mt-3">Recomendaciones:</h6>
                        <ul class="list-group">
                            @foreach($data['recomendaciones'] as $recomendacion)
                                <li class="list-group-item d-flex align-items-start">
                                    <i class="fas fa-arrow-right text-primary me-2 mt-1"></i>
                                    <span>{{ $recomendacion }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-footer">
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        Última verificación: {{ now()->format('d/m/Y H:i:s') }}
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <a href="{{ route('admin.configuracion-sunat.index') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-cogs me-2"></i>
                        Ir a Configuración SUNAT
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .card-header.bg-primary {
        color: white !important;
    }
    .card-header.bg-primary .card-tools .btn-light {
        border-color: rgba(255,255,255,0.3);
    }
    .list-group-item {
        border: none;
        padding: 0.5rem 0;
    }
    .alert-heading {
        margin-bottom: 0.5rem;
    }
</style>
@stop

@section('js')
<script>
function recargarDiagnostico() {
    location.reload();
}
</script>
@stop