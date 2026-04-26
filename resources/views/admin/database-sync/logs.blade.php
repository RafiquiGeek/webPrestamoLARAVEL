@extends('layouts.admin')

@section('title', 'Logs del Sistema - Sincronización DB')

@section('content_header')
    <h1>
        <i class="fas fa-file-alt"></i> Logs del Sistema
        <small class="ml-3">
            <span class="badge badge-info">{{ count($logs) }} entradas</span>
        </small>
    </h1>
@stop

@section('content')

<!-- Filtros -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter"></i> Filtros de Búsqueda
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" onclick="clearFilters()">
                <i class="fas fa-times"></i> Limpiar
            </button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.database-sync.logs') }}" id="filterForm">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date">Fecha</label>
                        <input type="date" class="form-control" id="date" name="date" value="{{ $date }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="level">Nivel</label>
                        <select class="form-control" id="level" name="level">
                            <option value="all" {{ $level === 'all' ? 'selected' : '' }}>Todos los niveles</option>
                            <option value="debug" {{ $level === 'debug' ? 'selected' : '' }}>Debug</option>
                            <option value="info" {{ $level === 'info' ? 'selected' : '' }}>Info</option>
                            <option value="warning" {{ $level === 'warning' ? 'selected' : '' }}>Warning</option>
                            <option value="error" {{ $level === 'error' ? 'selected' : '' }}>Error</option>
                            <option value="critical" {{ $level === 'critical' ? 'selected' : '' }}>Critical</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="search">Buscar</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="{{ $search }}" placeholder="Buscar en mensajes...">
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Fechas Disponibles -->
@if(count($available_dates) > 0)
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-calendar"></i> Fechas Disponibles
        </h3>
    </div>
    <div class="card-body">
        <div class="btn-group-toggle" data-toggle="buttons">
            @foreach($available_dates as $available_date)
            <label class="btn btn-outline-secondary btn-sm {{ $date === $available_date ? 'active' : '' }}">
                <input type="radio" name="quick_date" value="{{ $available_date }}" 
                       {{ $date === $available_date ? 'checked' : '' }}
                       onchange="quickDateFilter('{{ $available_date }}')">
                {{ \Carbon\Carbon::parse($available_date)->format('d/m/Y') }}
                @if($available_date === \Carbon\Carbon::now()->format('Y-m-d'))
                    <span class="badge badge-primary">Hoy</span>
                @endif
            </label>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- Logs -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-list"></i> Entradas de Log
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" onclick="refreshLogs()">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button type="button" class="btn btn-tool" onclick="exportLogs()">
                <i class="fas fa-download"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 600px;">
            <table class="table table-sm table-striped">
                <thead class="thead-light sticky-top">
                    <tr>
                        <th style="width: 140px;">Timestamp</th>
                        <th style="width: 80px;">Nivel</th>
                        <th>Mensaje</th>
                        <th style="width: 100px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $index => $log)
                    <tr class="log-entry {{ strtolower($log['level']) }}-row" data-index="{{ $index }}">
                        <td class="text-nowrap">
                            <small>{{ $log['timestamp'] }}</small>
                        </td>
                        <td>
                            <span class="badge badge-{{ getLevelColor($log['level']) }}">
                                {{ $log['level'] }}
                            </span>
                        </td>
                        <td>
                            <span class="log-message">{{ Str::limit($log['message'], 100) }}</span>
                            @if(strlen($log['message']) > 100)
                                <a href="#" onclick="toggleFullMessage({{ $index }})" class="text-primary">
                                    <small>[ver más]</small>
                                </a>
                            @endif
                            <div class="full-message" id="full-message-{{ $index }}" style="display: none;">
                                <hr>
                                <div class="text-sm">{{ $log['message'] }}</div>
                                @if($log['context'])
                                    <div class="mt-2">
                                        <strong>Contexto:</strong>
                                        <pre class="text-xs bg-light p-2 mt-1">{{ json_encode($log['context'], JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($log['context'])
                            <button class="btn btn-xs btn-info" onclick="showContext({{ $index }})" title="Ver contexto">
                                <i class="fas fa-info-circle"></i>
                            </button>
                            @endif
                            <button class="btn btn-xs btn-secondary" onclick="copyLogEntry({{ $index }})" title="Copiar">
                                <i class="fas fa-copy"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                No se encontraron logs para los filtros seleccionados
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if(count($logs) > 0)
    <div class="card-footer">
        <div class="row">
            <div class="col-md-6">
                <span class="text-muted">
                    Mostrando {{ count($logs) }} entradas
                    @if($search)
                        con filtro: "{{ $search }}"
                    @endif
                </span>
            </div>
            <div class="col-md-6 text-right">
                <button class="btn btn-sm btn-outline-primary" onclick="loadMoreLogs()">
                    <i class="fas fa-plus"></i> Cargar Más
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modal para Contexto -->
<div class="modal fade" id="contextModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Contexto del Log</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="contextContent">
                    <!-- Contenido dinámico -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="copyContext()">
                    <i class="fas fa-copy"></i> Copiar
                </button>
            </div>
        </div>
    </div>
</div>

@stop

@section('css')
<style>
.log-entry.critical-row {
    background-color: #f8d7da;
}

.log-entry.error-row {
    background-color: #fff3cd;
}

.log-entry.warning-row {
    background-color: #d1ecf1;
}

.log-entry.debug-row {
    background-color: #f8f9fa;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}

.table-responsive {
    overflow-x: auto;
}

.log-message {
    word-break: break-word;
}

.full-message pre {
    font-size: 0.75rem;
    max-height: 200px;
    overflow-y: auto;
}

.btn-xs {
    padding: 0.125rem 0.25rem;
    font-size: 0.75rem;
    line-height: 1.2;
    border-radius: 0.125rem;
}

.table-sm td {
    padding: 0.3rem;
    vertical-align: middle;
}

.badge {
    font-size: 0.7em;
}

/* Auto-scroll animation */
@keyframes highlight {
    0% { background-color: #fff3cd; }
    100% { background-color: transparent; }
}

.new-log-entry {
    animation: highlight 2s ease-in-out;
}
</style>
@stop

@section('js')
<script>
// Variables globales
let currentLogs = @json($logs);
let autoRefresh = false;
let refreshInterval;

$(document).ready(function() {
    // Configurar auto-refresh si estamos viendo logs de hoy
    if ('{{ $date }}' === '{{ \Carbon\Carbon::now()->format("Y-m-d") }}') {
        // startAutoRefresh();
    }
    
    // Configurar shortcuts de teclado
    setupKeyboardShortcuts();
});

function setupKeyboardShortcuts() {
    $(document).on('keydown', function(e) {
        // Ctrl + F para buscar
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            $('#search').focus();
        }
        
        // F5 para refrescar
        if (e.key === 'F5') {
            e.preventDefault();
            refreshLogs();
        }
        
        // Escape para limpiar filtros
        if (e.key === 'Escape') {
            clearFilters();
        }
    });
}

function quickDateFilter(date) {
    $('#date').val(date);
    $('#filterForm').submit();
}

function clearFilters() {
    $('#date').val('{{ \Carbon\Carbon::now()->format("Y-m-d") }}');
    $('#level').val('all');
    $('#search').val('');
    $('#filterForm').submit();
}

function refreshLogs() {
    const btn = $('[onclick="refreshLogs()"]');
    btn.addClass('fa-spin');
    
    // Recargar la página con los filtros actuales
    $('#filterForm').submit();
}

function toggleFullMessage(index) {
    const fullMessage = $(`#full-message-${index}`);
    const isVisible = fullMessage.is(':visible');
    
    if (isVisible) {
        fullMessage.slideUp();
    } else {
        fullMessage.slideDown();
    }
}

function showContext(index) {
    const log = currentLogs[index];
    
    if (!log.context) {
        toastr.warning('No hay contexto disponible para este log');
        return;
    }
    
    const contextHtml = `
        <div class="mb-3">
            <strong>Timestamp:</strong> ${log.timestamp}<br>
            <strong>Nivel:</strong> <span class="badge badge-${getLevelColorJS(log.level)}">${log.level}</span><br>
            <strong>Mensaje:</strong> ${log.message}
        </div>
        <div>
            <strong>Contexto:</strong>
            <pre class="bg-light p-3 mt-2" style="max-height: 400px; overflow-y: auto;">${JSON.stringify(log.context, null, 2)}</pre>
        </div>
    `;
    
    $('#contextContent').html(contextHtml);
    $('#contextModal').modal('show');
}

function copyLogEntry(index) {
    const log = currentLogs[index];
    
    let logText = `[${log.timestamp}] ${log.level}: ${log.message}`;
    
    if (log.context) {
        logText += '\nContexto: ' + JSON.stringify(log.context, null, 2);
    }
    
    copyToClipboard(logText);
    toastr.success('Log copiado al portapapeles');
}

function copyContext() {
    const contextPre = $('#contextContent pre');
    if (contextPre.length > 0) {
        copyToClipboard(contextPre.text());
        toastr.success('Contexto copiado al portapapeles');
    }
}

function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
    } else {
        // Fallback para navegadores que no soporten clipboard API
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
    }
}

function exportLogs() {
    const exportData = {
        timestamp: new Date().toISOString(),
        filters: {
            date: '{{ $date }}',
            level: '{{ $level }}',
            search: '{{ $search }}'
        },
        logs: currentLogs
    };
    
    const dataStr = JSON.stringify(exportData, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = `database-sync-logs-{{ $date }}.json`;
    link.click();
    
    URL.revokeObjectURL(url);
    toastr.success('Logs exportados exitosamente');
}

function loadMoreLogs() {
    toastr.info('Función de cargar más logs por implementar');
    // Aquí implementarías paginación o carga incremental
}

function startAutoRefresh() {
    if (autoRefresh) return;
    
    autoRefresh = true;
    refreshInterval = setInterval(() => {
        // Verificar si hay nuevos logs sin recargar la página completa
        checkForNewLogs();
    }, 10000); // Cada 10 segundos
    
    toastr.info('Auto-refresh activado (cada 10 segundos)');
}

function stopAutoRefresh() {
    if (!autoRefresh) return;
    
    autoRefresh = false;
    clearInterval(refreshInterval);
    toastr.info('Auto-refresh desactivado');
}

function checkForNewLogs() {
    // Esta función verificaría si hay nuevos logs sin recargar la página
    // Por ahora solo simularemos nuevos logs ocasionalmente
    
    if (Math.random() > 0.8) { // 20% de probabilidad
        // Simular nuevo log
        addNewLogEntry({
            timestamp: new Date().toLocaleTimeString(),
            level: 'INFO',
            message: 'Nueva operación de sincronización completada',
            context: null
        });
    }
}

function addNewLogEntry(log) {
    const levelColor = getLevelColorJS(log.level);
    const newRowHtml = `
        <tr class="log-entry ${log.level.toLowerCase()}-row new-log-entry" data-index="${currentLogs.length}">
            <td class="text-nowrap">
                <small>${log.timestamp}</small>
            </td>
            <td>
                <span class="badge badge-${levelColor}">
                    ${log.level}
                </span>
            </td>
            <td>
                <span class="log-message">${log.message}</span>
            </td>
            <td>
                <button class="btn btn-xs btn-secondary" onclick="copyLogEntry(${currentLogs.length})" title="Copiar">
                    <i class="fas fa-copy"></i>
                </button>
            </td>
        </tr>
    `;
    
    $('tbody').prepend(newRowHtml);
    currentLogs.unshift(log);
    
    // Remover la clase de animación después de 2 segundos
    setTimeout(() => {
        $('.new-log-entry').removeClass('new-log-entry');
    }, 2000);
}

function getLevelColorJS(level) {
    switch (level.toLowerCase()) {
        case 'critical': return 'danger';
        case 'error': return 'danger';
        case 'warning': return 'warning';
        case 'info': return 'info';
        case 'debug': return 'secondary';
        default: return 'primary';
    }
}

// Event listeners
$('#search').on('keypress', function(e) {
    if (e.which === 13) { // Enter key
        $('#filterForm').submit();
    }
});

// Auto-submit form on level change
$('#level').on('change', function() {
    $('#filterForm').submit();
});
</script>
@stop

@php
function getLevelColor($level) {
    switch (strtolower($level)) {
        case 'critical': return 'danger';
        case 'error': return 'danger';
        case 'warning': return 'warning';
        case 'info': return 'info';
        case 'debug': return 'secondary';
        default: return 'primary';
    }
}
@endphp