@extends('layouts.admin')
@section('title', 'Detalle de Incidencia')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-search mr-2"></i>Detalle de Incidencia</h1>
       <ol class="breadcrumb float-sm-right mb-0">
           <li class="breadcrumb-item"><a href="{{ route('admin.logs.index') }}">Logs</a></li>
           <li class="breadcrumb-item active">Detalle</li>
       </ol>
   </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Información Principal -->
    <div class="account-card mb-4">
        <div class="card-header">
            <h3><i class="fas fa-info-circle me-2"></i>Información Principal</h3>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.history.back()">
                    <i class="fas fa-arrow-left me-1"></i>Volver
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="copiarTodo()">
                    <i class="fas fa-copy me-1"></i>Copiar Todo
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="info-card">
                        <div class="info-label">Fecha y Hora</div>
                        <div class="info-value">{{ $log['fecha']->format('d/m/Y H:i:s') }}</div>
                        <small class="text-muted">{{ $log['fecha']->diffForHumans() }}</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="info-card">
                        <div class="info-label">Nivel</div>
                        @php
                            $nivelConfig = [
                                'ERROR' => ['class' => 'danger', 'icon' => 'fa-times-circle'],
                                'WARNING' => ['class' => 'warning', 'icon' => 'fa-exclamation-triangle'],
                                'CRITICAL' => ['class' => 'danger', 'icon' => 'fa-skull'],
                                'ALERT' => ['class' => 'warning', 'icon' => 'fa-bell'],
                                'EMERGENCY' => ['class' => 'danger', 'icon' => 'fa-fire']
                            ];
                            $config = $nivelConfig[$log['nivel']] ?? ['class' => 'secondary', 'icon' => 'fa-info'];
                        @endphp
                        <span class="badge bg-{{ $config['class'] }} fs-6">
                            <i class="fas {{ $config['icon'] }} me-1"></i>
                            {{ $log['nivel'] }}
                        </span>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="info-card">
                        <div class="info-label">Categoría</div>
                        @php
                            $categoriaConfig = app('App\Http\Controllers\Admin\LogsController')->getCategoriaConfig($log['categoria']);
                        @endphp
                        <span class="badge bg-{{ $categoriaConfig['color'] }} fs-6">
                            <i class="fas {{ $categoriaConfig['icon'] }} me-1"></i>
                            {{ ucfirst(str_replace('_', ' ', $log['categoria'])) }}
                        </span>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="info-card">
                        <div class="info-label">Usuario ID</div>
                        <div class="info-value">
                            @if(isset($log['context']['userId']))
                                <span class="badge bg-light text-dark">
                                    <i class="fas fa-user me-1"></i>
                                    {{ $log['context']['userId'] }}
                                </span>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-card">
                        <div class="info-label">ID de Log</div>
                        <div class="info-value small">
                            <code>{{ $log['id'] }}</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mensaje de Error -->
    <div class="account-card mb-4">
        <div class="card-header">
            <h3><i class="fas fa-exclamation-triangle me-2"></i>Mensaje de Error</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-{{ $config['class'] }} border-start border-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="alert-heading">
                            <i class="fas {{ $config['icon'] }} me-2"></i>
                            {{ $log['nivel'] }}
                        </h5>
                        <p class="mb-0 fs-6">{{ $log['mensaje'] }}</p>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                            onclick="copiarTexto('{{ addslashes($log['mensaje']) }}')">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Ubicación del Error -->
    @if($log['archivo'] || $log['linea'])
    <div class="account-card mb-4">
        <div class="card-header">
            <h3><i class="fas fa-map-marker-alt me-2"></i>Ubicación del Error</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @if($log['archivo'])
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="info-label">Archivo</div>
                            <div class="info-value">
                                <code>{{ $log['archivo'] }}</code>
                            </div>
                        </div>
                    </div>
                @endif
                @if($log['linea'])
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="info-label">Línea</div>
                            <div class="info-value">
                                <code>{{ $log['linea'] }}</code>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Stack Trace -->
    @if($log['stack_trace'])
    <div class="account-card mb-4">
        <div class="card-header">
            <h3><i class="fas fa-code me-2"></i>Stack Trace</h3>
            <button type="button" class="btn btn-outline-secondary btn-sm" 
                    onclick="copiarTexto(`{{ addslashes($log['stack_trace']) }}`)">
                <i class="fas fa-copy me-1"></i>Copiar Stack Trace
            </button>
        </div>
        <div class="card-body">
            <pre class="bg-dark text-light p-3 rounded"><code>{{ $log['stack_trace'] }}</code></pre>
        </div>
    </div>
    @endif

    <!-- Mensaje Completo -->
    <div class="account-card mb-4">
        <div class="card-header">
            <h3><i class="fas fa-file-alt me-2"></i>Mensaje Completo</h3>
            <button type="button" class="btn btn-outline-secondary btn-sm" 
                    onclick="copiarTexto(`{{ addslashes($log['mensaje_completo']) }}`)">
                <i class="fas fa-copy me-1"></i>Copiar Completo
            </button>
        </div>
        <div class="card-body">
            <div class="border rounded p-3 bg-light">
                <pre class="mb-0"><code>{{ $log['mensaje_completo'] }}</code></pre>
            </div>
        </div>
    </div>

    <!-- Logs Relacionados -->
    @if($logsRelacionados->count() > 0)
    <div class="account-card">
        <div class="card-header">
            <h3><i class="fas fa-link me-2"></i>Logs Relacionados</h3>
            <small class="text-muted">Mismo usuario en contexto similar</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Nivel</th>
                            <th>Mensaje</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logsRelacionados as $logRelacionado)
                            <tr>
                                <td>
                                    <div class="info-value small">
                                        {{ $logRelacionado['fecha']->format('d/m/Y H:i:s') }}
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $configRel = $nivelConfig[$logRelacionado['nivel']] ?? ['class' => 'secondary', 'icon' => 'fa-info'];
                                    @endphp
                                    <span class="badge bg-{{ $configRel['class'] }}">
                                        {{ $logRelacionado['nivel'] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="log-mensaje">
                                        {{ Str::limit($logRelacionado['mensaje'], 100) }}
                                    </div>
                                </td>
                                <td>
                                    <button type="button" 
                                            class="btn btn-outline-info btn-sm"
                                            onclick="verDetalle('{{ $logRelacionado['id'] }}')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@stop

@section('css')
<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
/* Estilos consistentes con el sistema */
.account-card {
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.account-card .card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.account-card .card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0;
}

.account-card .card-body {
    padding: 1.5rem;
}

.info-card {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 8px;
    margin-bottom: 0.75rem;
    width: 100%;
}

.info-card .info-label {
    font-size: 0.75rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    font-weight: 600;
}

.info-card .info-value {
    font-size: 1rem;
    font-weight: 600;
    color: #1a1a1a;
}

.log-mensaje {
    max-width: 400px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

pre {
    white-space: pre-wrap;
    word-wrap: break-word;
    font-size: 0.875rem;
}

code {
    font-size: 0.875rem;
}

.fs-6 {
    font-size: 1rem;
}
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
function copiarTexto(texto) {
    navigator.clipboard.writeText(texto).then(function() {
        mostrarNotificacion('Texto copiado al portapapeles', 'success');
    }).catch(function() {
        mostrarNotificacion('Error al copiar al portapapeles', 'danger');
    });
}

function copiarTodo() {
    const log = @json($log);
    const texto = `
DETALLE DE INCIDENCIA
=====================
Fecha: ${log.fecha}
Nivel: ${log.nivel}
Categoría: ${log.categoria}
Usuario ID: ${log.context?.userId || 'N/A'}
Archivo: ${log.archivo || 'N/A'}
Línea: ${log.linea || 'N/A'}

MENSAJE:
${log.mensaje}

MENSAJE COMPLETO:
${log.mensaje_completo}

${log.stack_trace ? 'STACK TRACE:\n' + log.stack_trace : ''}
    `;
    
    copiarTexto(texto.trim());
}

function mostrarNotificacion(mensaje, tipo) {
    const toast = document.createElement('div');
    toast.className = `alert alert-${tipo} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 250px;';
    toast.innerHTML = `<i class="fas fa-${tipo === 'success' ? 'check' : 'times'} me-2"></i>${mensaje}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (document.body.contains(toast)) {
            document.body.removeChild(toast);
        }
    }, 3000);
}

function verDetalle(logId) {
    window.location.href = '{{ route("admin.logs.detalle", "") }}/' + logId;
}
</script>
@stop