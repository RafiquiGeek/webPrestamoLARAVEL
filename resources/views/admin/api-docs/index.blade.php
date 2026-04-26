@extends('layouts.admin')

@section('title', 'Documentación API Móvil')

@section('content')
<div class="container-fluid">
    <div class="py-4">
        <!-- Header -->
        <div class="account-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-2">
                        <i class="fas fa-mobile-alt me-2"></i>Documentación API Móvil
                        <span class="badge bg-success ms-2">v1.0</span>
                    </h3>
                    <p class="text-muted mb-0">API RESTful para aplicaciones móviles del sistema financiero</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.api-docs.testing') }}" class="btn btn-primary">
                        <i class="fas fa-vial me-1"></i>Testing API
                    </a>
                    <a href="/api-docs" target="_blank" class="btn btn-outline-info">
                        <i class="fas fa-external-link-alt me-1"></i>Ver en Nueva Pestaña
                    </a>
                </div>
            </div>

            <div class="card-body">
                <!-- Información de acceso rápido -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="info-card text-center">
                            <div class="info-icon">
                                <i class="fas fa-link text-primary"></i>
                            </div>
                            <h6>Base URL</h6>
                            <p class="small"><code>{{ config('app.url') }}/api</code></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-card text-center">
                            <div class="info-icon">
                                <i class="fas fa-key text-success"></i>
                            </div>
                            <h6>Autenticación</h6>
                            <p class="small">Bearer Token (Sanctum)</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-card text-center">
                            <div class="info-icon">
                                <i class="fas fa-code text-info"></i>
                            </div>
                            <h6>Formato</h6>
                            <p class="small">JSON Request/Response</p>
                        </div>
                    </div>
                </div>

                <!-- Endpoints principales -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5><i class="fas fa-list me-2"></i>Endpoints Principales</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="endpoint-group">
                                    <h6 class="text-primary">🔐 Autenticación</h6>
                                    <ul class="list-unstyled">
                                        <li><span class="badge bg-success">POST</span> <code>/auth/login</code></li>
                                        <li><span class="badge bg-danger">POST</span> <code>/auth/logout</code></li>
                                        <li><span class="badge bg-info">GET</span> <code>/auth/me</code></li>
                                    </ul>
                                </div>
                                <div class="endpoint-group">
                                    <h6 class="text-primary">💰 Préstamos</h6>
                                    <ul class="list-unstyled">
                                        <li><span class="badge bg-info">GET</span> <code>/solicitudes</code></li>
                                        <li><span class="badge bg-success">POST</span> <code>/solicitudes</code></li>
                                        <li><span class="badge bg-success">POST</span> <code>/solicitudes/calcular</code></li>
                                    </ul>
                                </div>
                                <div class="endpoint-group">
                                    <h6 class="text-primary">💳 Pagos</h6>
                                    <ul class="list-unstyled">
                                        <li><span class="badge bg-success">POST</span> <code>/pagos</code></li>
                                        <li><span class="badge bg-info">GET</span> <code>/pagos/prestamo/{id}</code></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="endpoint-group">
                                    <h6 class="text-primary">📊 Estados de Cuenta</h6>
                                    <ul class="list-unstyled">
                                        <li><span class="badge bg-info">GET</span> <code>/estados-cuenta/prestamo/{id}</code></li>
                                        <li><span class="badge bg-info">GET</span> <code>/estados-cuenta/resumen-cartera</code></li>
                                    </ul>
                                </div>
                                <div class="endpoint-group">
                                    <h6 class="text-primary">📞 Gestiones</h6>
                                    <ul class="list-unstyled">
                                        <li><span class="badge bg-info">GET</span> <code>/gestiones</code></li>
                                        <li><span class="badge bg-success">POST</span> <code>/gestiones</code></li>
                                    </ul>
                                </div>
                                <div class="endpoint-group">
                                    <h6 class="text-primary">🕐 Asistencia</h6>
                                    <ul class="list-unstyled">
                                        <li><span class="badge bg-success">POST</span> <code>/asistencia/entrada</code></li>
                                        <li><span class="badge bg-success">POST</span> <code>/asistencia/salida</code></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Documentación completa -->
                <div class="documentation-content">
                    <h5><i class="fas fa-book me-2"></i>Documentación Completa</h5>
                    @if($documentation)
                        <div class="markdown-content" style="max-height: 600px; overflow-y: auto; border: 1px solid #e9ecef; padding: 1rem; border-radius: 0.375rem;">
                            <pre style="white-space: pre-wrap; font-family: 'Segoe UI', sans-serif; line-height: 1.6;">{{ $documentation }}</pre>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No se pudo cargar la documentación. Archivo API_DOCUMENTATION.md no encontrado.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
.account-card {
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin: 0 auto 1.5rem;
}

.card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 1rem 1.5rem;
}

.card-body {
    padding: 1.5rem;
}

.info-card {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border: 1px solid #e9ecef;
}

.info-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.endpoint-group {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.endpoint-group h6 {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.5rem;
    margin-bottom: 0.75rem;
}

.endpoint-group ul li {
    padding: 0.25rem 0;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
}

.endpoint-group .badge {
    width: 50px;
    text-align: center;
    margin-right: 0.5rem;
}

.documentation-content {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid #e9ecef;
}

.markdown-content {
    background: #f8f9fa;
    font-size: 0.875rem;
}

.badge {
    font-size: 0.7rem;
}

code {
    background: #e9ecef;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}
</style>
@stop