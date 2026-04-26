@if($logs->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th width="130">Fecha/Hora</th>
                    <th width="80">Nivel</th>
                    <th width="100">Categoría</th>
                    <th>Mensaje</th>
                    <th width="120">Ubicación</th>
                    <th width="80">Usuario</th>
                    <th width="100">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr class="log-nivel-{{ $log['nivel'] }}">
                        <td>
                            <div class="info-value small">
                                {{ $log['fecha']->format('d/m/Y') }}
                            </div>
                            <small class="text-muted">
                                {{ $log['fecha']->format('H:i:s') }}
                            </small>
                        </td>
                        <td>
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
                            <span class="badge bg-{{ $config['class'] }} badge-categoria">
                                <i class="fas {{ $config['icon'] }} me-1"></i>
                                {{ $log['nivel'] }}
                            </span>
                        </td>
                        <td>
                            @php
                                $categoriaConfig = app('App\Http\Controllers\Admin\LogsController')->getCategoriaConfig($log['categoria']);
                            @endphp
                            <span class="badge bg-{{ $categoriaConfig['color'] }} badge-categoria">
                                <i class="fas {{ $categoriaConfig['icon'] }} me-1"></i>
                                {{ ucfirst(str_replace('_', ' ', $log['categoria'])) }}
                            </span>
                        </td>
                        <td>
                            <div class="log-mensaje" title="{{ $log['mensaje'] }}">
                                {{ Str::limit($log['mensaje'], 80) }}
                            </div>
                            @if(isset($log['explicacion']))
                                <small class="text-info d-block mt-1">
                                    <i class="fas fa-lightbulb me-1"></i>{{ $log['explicacion'] }}
                                </small>
                            @endif
                            @if($log['stack_trace'])
                                <small class="text-muted d-block">
                                    <i class="fas fa-code me-1"></i>Con stack trace
                                </small>
                            @endif
                        </td>
                        <td>
                            @if($log['archivo'])
                                <div class="info-value small">{{ $log['archivo'] }}</div>
                                @if($log['linea'])
                                    <small class="text-muted">Línea {{ $log['linea'] }}</small>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if(isset($log['context']['userId']))
                                <span class="badge bg-light text-dark">
                                    <i class="fas fa-user me-1"></i>
                                    {{ $log['context']['userId'] }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <button type="button" 
                                        class="btn btn-outline-info btn-sm"
                                        onclick="verDetalle('{{ $log['id'] }}')"
                                        title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" 
                                        class="btn btn-outline-secondary btn-sm"
                                        onclick="copiarMensaje('{{ addslashes($log['mensaje']) }}')"
                                        title="Copiar mensaje">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    @if($logs->count() >= 50)
        <div class="card-footer text-center">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Mostrando los primeros {{ $logs->count() }} resultados. 
                Usa filtros más específicos para ver más registros.
            </small>
        </div>
    @endif
@else
    <!-- Sin logs -->
    <div class="text-center py-5">
        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
        <h4 class="text-success">¡Excelente!</h4>
        <p class="text-muted">No se encontraron incidencias en el período seleccionado.</p>
        <p class="text-muted">Esto significa que el sistema está funcionando correctamente.</p>
        <button type="button" class="btn btn-outline-primary" onclick="limpiarFiltros()">
            <i class="fas fa-search me-1"></i>Buscar en Otro Período
        </button>
    </div>
@endif

<script>
function copiarMensaje(mensaje) {
    navigator.clipboard.writeText(mensaje).then(function() {
        // Mostrar notificación temporal
        const toast = document.createElement('div');
        toast.className = 'alert alert-success position-fixed';
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 200px;';
        toast.innerHTML = '<i class="fas fa-check me-2"></i>Mensaje copiado al portapapeles';
        document.body.appendChild(toast);
        
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 2000);
    }).catch(function() {
        alert('Error al copiar al portapapeles');
    });
}
</script>