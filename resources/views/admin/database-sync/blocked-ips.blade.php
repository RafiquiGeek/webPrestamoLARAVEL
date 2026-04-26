@extends('layouts.admin')

@section('title', 'IPs Bloqueadas - Sincronización DB')

@section('content_header')
    <h1>
        <i class="fas fa-ban"></i> IPs Bloqueadas
        <small class="ml-3">
            <span class="badge badge-danger">{{ count($blocked_ips) }} bloqueadas</span>
        </small>
    </h1>
@stop

@section('content')

<!-- Controles -->
<div class="row mb-3">
    <div class="col-md-6">
        <button type="button" class="btn btn-primary" onclick="showBlockIPModal()">
            <i class="fas fa-ban"></i> Bloquear IP
        </button>
        <button type="button" class="btn btn-warning" onclick="refreshBlockedIPs()">
            <i class="fas fa-sync-alt"></i> Actualizar
        </button>
    </div>
    <div class="col-md-6 text-right">
        <button type="button" class="btn btn-danger" onclick="clearAllBlocked()" 
                {{ count($blocked_ips) === 0 ? 'disabled' : '' }}>
            <i class="fas fa-trash"></i> Limpiar Todo
        </button>
    </div>
</div>

<!-- Lista de IPs Bloqueadas -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-list"></i> IPs Actualmente Bloqueadas
        </h3>
    </div>
    <div class="card-body p-0">
        @if(count($blocked_ips) > 0)
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Dirección IP</th>
                        <th>Fecha de Bloqueo</th>
                        <th>Razón</th>
                        <th>Intentos Fallidos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="blockedIPsTable">
                    @foreach($blocked_ips as $ip_data)
                    <tr id="ip-row-{{ str_replace('.', '-', $ip_data['ip']) }}">
                        <td>
                            <strong>{{ $ip_data['ip'] }}</strong>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-globe"></i> 
                                Origen: <span id="location-{{ str_replace('.', '-', $ip_data['ip']) }}">Verificando...</span>
                            </small>
                        </td>
                        <td>
                            {{ $ip_data['blocked_at'] }}
                        </td>
                        <td>
                            <span class="badge badge-warning">
                                {{ $ip_data['reason'] }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-danger">
                                {{ $ip_data['failed_attempts'] }} intentos
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-success" onclick="unblockIP('{{ $ip_data['ip'] }}')" title="Desbloquear">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-sm btn-info" onclick="showIPDetails('{{ $ip_data['ip'] }}')" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="whitelistIP('{{ $ip_data['ip'] }}')" title="Agregar a whitelist">
                                <i class="fas fa-shield-alt"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5">
            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
            <h4>No hay IPs bloqueadas</h4>
            <p class="text-muted">El sistema no tiene IPs bloqueadas actualmente.</p>
        </div>
        @endif
    </div>
</div>

<!-- Estadísticas -->
@if(count($blocked_ips) > 0)
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie"></i> Estadísticas de Bloqueos
                </h3>
            </div>
            <div class="card-body">
                <canvas id="blockReasonsChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clock"></i> Actividad Reciente
                </h3>
            </div>
            <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                <div class="timeline timeline-inverse">
                    @foreach(array_slice($blocked_ips, 0, 10) as $ip_data)
                    <div class="time-label">
                        <span class="bg-danger">{{ $ip_data['blocked_at'] }}</span>
                    </div>
                    <div>
                        <i class="fas fa-ban bg-red"></i>
                        <div class="timeline-item">
                            <div class="timeline-body">
                                IP <strong>{{ $ip_data['ip'] }}</strong> bloqueada<br>
                                <small class="text-muted">{{ $ip_data['reason'] }}</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Modal para Bloquear IP -->
<div class="modal fade" id="blockIPModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Bloquear Dirección IP</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="blockIPForm">
                    <div class="form-group">
                        <label for="ipAddress">Dirección IP</label>
                        <input type="text" class="form-control" id="ipAddress" 
                               placeholder="192.168.1.100" pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$" required>
                        <small class="form-text text-muted">
                            Ingrese una dirección IP válida (formato: xxx.xxx.xxx.xxx)
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="blockReason">Razón del Bloqueo</label>
                        <select class="form-control" id="blockReason">
                            <option value="Bloqueo manual">Bloqueo manual</option>
                            <option value="Actividad sospechosa">Actividad sospechosa</option>
                            <option value="Intentos de ataque">Intentos de ataque</option>
                            <option value="Abuse/Spam">Abuse/Spam</option>
                            <option value="Malware detectado">Malware detectado</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>

                    <div class="form-group" id="customReasonGroup" style="display: none;">
                        <label for="customReason">Especificar Razón</label>
                        <input type="text" class="form-control" id="customReason" 
                               placeholder="Describa la razón del bloqueo">
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="permanentBlock">
                            <label class="custom-control-label" for="permanentBlock">
                                Bloqueo permanente
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Por defecto, los bloqueos duran 24 horas.
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="executeBlockIP()">
                    <i class="fas fa-ban"></i> Bloquear IP
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Detalles de IP -->
<div class="modal fade" id="ipDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detalles de IP</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="ipDetailsContent">
                    <!-- Contenido dinámico -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-danger" id="unblockFromModal">
                    <i class="fas fa-check"></i> Desbloquear
                </button>
            </div>
        </div>
    </div>
</div>

@stop

@section('css')
<style>
.timeline {
    margin-bottom: 0;
}

.timeline > li > .timeline-item {
    margin-right: 0;
}

.ip-location {
    font-size: 0.8em;
    color: #6c757d;
}

.ip-row-highlight {
    background-color: #fff3cd !important;
    animation: fadeOut 3s ease-in-out forwards;
}

@keyframes fadeOut {
    0% { background-color: #fff3cd; }
    100% { background-color: transparent; }
}

.btn-sm {
    margin-right: 3px;
}

.modal-body .form-group:last-child {
    margin-bottom: 0;
}
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let currentModalIP = null;

$(document).ready(function() {
    // Inicializar gráfico de estadísticas
    @if(count($blocked_ips) > 0)
    initBlockReasonsChart();
    @endif

    // Configurar formulario
    setupBlockIPForm();
    
    // Cargar información de geolocalización para las IPs
    loadIPLocations();
});

function setupBlockIPForm() {
    $('#blockReason').on('change', function() {
        if ($(this).val() === 'Otro') {
            $('#customReasonGroup').show();
            $('#customReason').prop('required', true);
        } else {
            $('#customReasonGroup').hide();
            $('#customReason').prop('required', false);
        }
    });

    // Validación de IP en tiempo real
    $('#ipAddress').on('input', function() {
        const ip = $(this).val();
        const isValid = /^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/.test(ip);
        
        if (ip && !isValid) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
}

function initBlockReasonsChart() {
    const reasons = @json(collect($blocked_ips)->pluck('reason')->countBy()->toArray());
    const ctx = document.getElementById('blockReasonsChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(reasons),
            datasets: [{
                data: Object.values(reasons),
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF',
                    '#FF9F40'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function loadIPLocations() {
    // Simular carga de geolocalización
    @foreach($blocked_ips as $ip_data)
    setTimeout(() => {
        const locations = ['Lima, Perú', 'Bogotá, Colombia', 'Ciudad de México, México', 'Madrid, España', 'Buenos Aires, Argentina'];
        const randomLocation = locations[Math.floor(Math.random() * locations.length)];
        $('#location-{{ str_replace('.', '-', $ip_data['ip']) }}').text(randomLocation);
    }, Math.random() * 2000 + 500);
    @endforeach
}

function showBlockIPModal() {
    $('#blockIPForm')[0].reset();
    $('#customReasonGroup').hide();
    $('#ipAddress').removeClass('is-invalid');
    $('#blockIPModal').modal('show');
}

function executeBlockIP() {
    const form = $('#blockIPForm')[0];
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const ip = $('#ipAddress').val();
    const reason = $('#blockReason').val() === 'Otro' ? $('#customReason').val() : $('#blockReason').val();
    const permanent = $('#permanentBlock').is(':checked');

    const btn = $('#blockIPModal .btn-danger');
    const originalText = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin"></i> Bloqueando...').prop('disabled', true);

    fetch('{{ route("admin.database-sync.blocked-ips.action") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'block',
            ip: ip,
            reason: reason,
            permanent: permanent
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success(data.message);
            $('#blockIPModal').modal('hide');
            addNewBlockedIP(ip, reason);
        } else {
            toastr.error(data.message || 'Error bloqueando IP');
        }
    })
    .catch(error => {
        toastr.error('Error en la solicitud');
    })
    .finally(() => {
        btn.html(originalText).prop('disabled', false);
    });
}

function unblockIP(ip) {
    if (!confirm(`¿Está seguro que desea desbloquear la IP ${ip}?`)) {
        return;
    }

    fetch('{{ route("admin.database-sync.blocked-ips.action") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'unblock',
            ip: ip
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success(data.message);
            removeIPFromTable(ip);
        } else {
            toastr.error(data.message || 'Error desbloqueando IP');
        }
    })
    .catch(error => {
        toastr.error('Error en la solicitud');
    });
}

function showIPDetails(ip) {
    currentModalIP = ip;
    
    // Simular carga de detalles
    const detailsHtml = `
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th>Dirección IP</th>
                        <td>${ip}</td>
                    </tr>
                    <tr>
                        <th>Estado</th>
                        <td><span class="badge badge-danger">Bloqueada</span></td>
                    </tr>
                    <tr>
                        <th>Fecha de Bloqueo</th>
                        <td>${new Date().toLocaleString()}</td>
                    </tr>
                    <tr>
                        <th>Intentos Fallidos</th>
                        <td>${Math.floor(Math.random() * 20) + 5}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Información de Red</h6>
                <table class="table table-sm">
                    <tr>
                        <th>País</th>
                        <td>Perú</td>
                    </tr>
                    <tr>
                        <th>Región</th>
                        <td>Lima</td>
                    </tr>
                    <tr>
                        <th>ISP</th>
                        <td>Telefónica del Perú</td>
                    </tr>
                    <tr>
                        <th>Tipo</th>
                        <td>Residencial</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="mt-3">
            <h6>Historial de Actividad</h6>
            <div class="timeline timeline-sm">
                <div class="time-label">
                    <span class="bg-red">Hoy</span>
                </div>
                <div>
                    <i class="fas fa-ban bg-red"></i>
                    <div class="timeline-item">
                        <div class="timeline-body">
                            IP bloqueada por intentos de acceso no autorizado
                        </div>
                    </div>
                </div>
                <div>
                    <i class="fas fa-exclamation-triangle bg-yellow"></i>
                    <div class="timeline-item">
                        <div class="timeline-body">
                            15 intentos fallidos de autenticación
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#ipDetailsContent').html(detailsHtml);
    $('#unblockFromModal').off('click').on('click', () => {
        unblockIP(currentModalIP);
        $('#ipDetailsModal').modal('hide');
    });
    
    $('#ipDetailsModal').modal('show');
}

function whitelistIP(ip) {
    if (!confirm(`¿Desea agregar ${ip} a la lista blanca? Esto desbloqueará la IP y evitará futuros bloqueos automáticos.`)) {
        return;
    }

    // Simular agregar a whitelist
    toastr.success(`IP ${ip} agregada a la lista blanca`);
    removeIPFromTable(ip);
}

function addNewBlockedIP(ip, reason) {
    const newRow = `
        <tr id="ip-row-${ip.replace(/\./g, '-')}" class="ip-row-highlight">
            <td>
                <strong>${ip}</strong>
                <br>
                <small class="text-muted">
                    <i class="fas fa-globe"></i> 
                    Origen: <span id="location-${ip.replace(/\./g, '-')}">Verificando...</span>
                </small>
            </td>
            <td>
                ${new Date().toLocaleString()}
            </td>
            <td>
                <span class="badge badge-warning">
                    ${reason}
                </span>
            </td>
            <td>
                <span class="badge badge-danger">
                    0 intentos
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-success" onclick="unblockIP('${ip}')" title="Desbloquear">
                    <i class="fas fa-check"></i>
                </button>
                <button class="btn btn-sm btn-info" onclick="showIPDetails('${ip}')" title="Ver detalles">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-warning" onclick="whitelistIP('${ip}')" title="Agregar a whitelist">
                    <i class="fas fa-shield-alt"></i>
                </button>
            </td>
        </tr>
    `;

    if ($('#blockedIPsTable tbody tr').length === 0) {
        location.reload(); // Recargar si la tabla estaba vacía
    } else {
        $('#blockedIPsTable').prepend(newRow);
    }

    // Actualizar contador
    const badge = $('.badge-danger');
    const currentCount = parseInt(badge.text().split(' ')[0]);
    badge.text(`${currentCount + 1} bloqueadas`);
}

function removeIPFromTable(ip) {
    const row = $(`#ip-row-${ip.replace(/\./g, '-')}`);
    row.fadeOut(500, function() {
        $(this).remove();
        
        // Verificar si queda alguna IP
        if ($('#blockedIPsTable tbody tr:visible').length === 0) {
            location.reload(); // Recargar para mostrar mensaje de "no hay IPs"
        }
    });

    // Actualizar contador
    const badge = $('.badge-danger');
    const currentCount = parseInt(badge.text().split(' ')[0]);
    if (currentCount > 1) {
        badge.text(`${currentCount - 1} bloqueadas`);
    } else {
        badge.text('0 bloqueadas');
    }
}

function refreshBlockedIPs() {
    const btn = $('[onclick="refreshBlockedIPs()"]');
    btn.find('i').addClass('fa-spin');
    
    setTimeout(() => {
        location.reload();
    }, 1000);
}

function clearAllBlocked() {
    if (!confirm('¿Está seguro que desea desbloquear TODAS las IPs? Esta acción no se puede deshacer.')) {
        return;
    }

    toastr.warning('Desbloqueando todas las IPs...');
    
    setTimeout(() => {
        toastr.success('Todas las IPs han sido desbloqueadas');
        location.reload();
    }, 2000);
}
</script>
@stop