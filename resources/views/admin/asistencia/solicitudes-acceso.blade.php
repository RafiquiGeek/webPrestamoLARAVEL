@extends('layouts.admin')

@section('title', 'Solicitudes de Acceso')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-user-check mr-2"></i>Solicitudes de Acceso</h1>
        <div id="stats-badges">
            <!-- Se cargarán dinámicamente -->
        </div>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Solicitudes Pendientes -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-clock mr-2"></i>Solicitudes Pendientes de Aprobación
                        <span class="badge badge-light ml-2">{{ $pendingRequests->count() }}</span>
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-outline-dark" onclick="refreshPage()">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    @forelse($pendingRequests as $request)
                        <div class="request-item border-bottom p-3" id="request-{{ $request->id }}">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar mr-3">
                                            <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <strong>{{ $request->user_name }}</strong><br>
                                            <small class="text-muted">{{ $request->email }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 text-center">
                                    <div class="code-display">
                                        <code class="badge badge-dark p-2 copy-code" data-code="{{ $request->access_code }}" style="font-size: 16px; cursor: pointer;" title="Clic para copiar">
                                            {{ $request->access_code }}
                                        </code>
                                        <br><small class="text-muted">Clic para copiar</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted">IP Address:</small><br>
                                    <code>{{ $request->ip_address }}</code>
                                </div>
                                <div class="col-md-2">
                                    <small class="text-muted">Solicitado:</small><br>
                                    {{ $request->created_at->format('H:i:s') }}<br>
                                    <small class="text-muted">{{ $request->created_at->diffForHumans() }}</small>
                                </div>
                                <div class="col-md-1">
                                    <small class="text-muted">Expira:</small><br>
                                    <span class="countdown" data-expires="{{ $request->expires_at->timestamp }}">
                                        {{ $request->expires_at->format('H:i:s') }}
                                    </span>
                                </div>
                                <div class="col-md-2">
                                    <div class="btn-group-vertical" role="group">
                                        <button type="button" class="btn btn-success btn-sm mb-1" onclick="approveRequest({{ $request->id }})">
                                            <i class="fas fa-check mr-1"></i>Aprobar
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="denyRequest({{ $request->id }})">
                                            <i class="fas fa-times mr-1"></i>Denegar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5>No hay solicitudes pendientes</h5>
                            <p class="text-muted">Todas las solicitudes han sido procesadas.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Solicitudes Recientes del Día -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history mr-2"></i>Solicitudes de Hoy
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Código</th>
                                    <th>Estado</th>
                                    <th>IP</th>
                                    <th>Procesado por</th>
                                    <th>Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentRequests as $request)
                                    <tr>
                                        <td>
                                            <strong>{{ $request->user_name }}</strong><br>
                                            <small class="text-muted">{{ $request->email }}</small>
                                        </td>
                                        <td>
                                            <code class="badge badge-secondary copy-code" data-code="{{ $request->access_code }}" style="cursor: pointer;">
                                                {{ $request->access_code }}
                                            </code>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $request->status_color }}">
                                                {{ $request->status_text }}
                                            </span>
                                        </td>
                                        <td><code>{{ $request->ip_address }}</code></td>
                                        <td>{{ $request->approvedBy->name ?? 'N/A' }}</td>
                                        <td>{{ $request->created_at->format('H:i:s') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No hay solicitudes de hoy</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para notas del administrador -->
<div class="modal fade" id="notesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Nota</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <textarea class="form-control" id="admin_notes" rows="3" placeholder="Nota opcional del administrador..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmAction">Confirmar</button>
            </div>
        </div>
    </div>
</div>
@stop

@push('scripts')
<script>
    let currentRequestId = null;
    let currentAction = null;

    $(document).ready(function() {
        loadStats();
        startCountdowns();
        
        // Auto-refresh cada 30 segundos
        setInterval(function() {
            if ($('.request-item').length > 0) {
                loadStats();
                updateCountdowns();
            }
        }, 30000);
    });

    function loadStats() {
        $.get('/admin/asistencia/solicitudes/stats', function(data) {
            $('#stats-badges').html(`
                <div class="d-flex">
                    <span class="badge badge-warning mr-2">
                        <i class="fas fa-clock mr-1"></i>${data.pending} Pendientes
                    </span>
                    <span class="badge badge-success mr-2">
                        <i class="fas fa-check mr-1"></i>${data.today_approved} Aprobados
                    </span>
                    <span class="badge badge-danger mr-2">
                        <i class="fas fa-times mr-1"></i>${data.today_denied} Denegados
                    </span>
                    <span class="badge badge-info">
                        <i class="fas fa-sign-in-alt mr-1"></i>${data.today_used} Usados
                    </span>
                </div>
            `);
        });
    }

    function approveRequest(requestId) {
        currentRequestId = requestId;
        currentAction = 'approve';
        $('#notesModal .modal-title').text('Aprobar Solicitud');
        $('#confirmAction').text('Aprobar').removeClass('btn-danger').addClass('btn-success');
        $('#notesModal').modal('show');
    }

    function denyRequest(requestId) {
        currentRequestId = requestId;
        currentAction = 'deny';
        $('#notesModal .modal-title').text('Denegar Solicitud');
        $('#confirmAction').text('Denegar').removeClass('btn-success').addClass('btn-danger');
        $('#notesModal').modal('show');
    }

    $('#confirmAction').click(function() {
        if (!currentRequestId || !currentAction) return;

        const notes = $('#admin_notes').val();
        const url = `/admin/asistencia/solicitudes/${currentRequestId}/${currentAction}`;

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                admin_notes: notes
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $(`#request-${currentRequestId}`).fadeOut();
                    loadStats();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('Error al procesar la solicitud');
            }
        });

        $('#notesModal').modal('hide');
        $('#admin_notes').val('');
    });

    // Copiar código al portapapeles
    $(document).on('click', '.copy-code', function() {
        const code = $(this).data('code');
        navigator.clipboard.writeText(code).then(function() {
            toastr.success(`Código ${code} copiado al portapapeles`);
        });
    });

    function startCountdowns() {
        $('.countdown').each(function() {
            const element = $(this);
            const expiresAt = parseInt(element.data('expires'));
            updateCountdown(element, expiresAt);
        });
    }

    function updateCountdowns() {
        $('.countdown').each(function() {
            const element = $(this);
            const expiresAt = parseInt(element.data('expires'));
            updateCountdown(element, expiresAt);
        });
    }

    function updateCountdown(element, expiresAt) {
        const now = Math.floor(Date.now() / 1000);
        const remaining = expiresAt - now;

        if (remaining <= 0) {
            element.html('<span class="text-danger">Expirado</span>');
            element.closest('.request-item').addClass('opacity-50');
        } else {
            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;
            element.html(`${minutes}:${seconds.toString().padStart(2, '0')}`);
            
            if (remaining <= 300) { // 5 minutos
                element.addClass('text-warning');
            }
            if (remaining <= 60) { // 1 minuto
                element.removeClass('text-warning').addClass('text-danger');
            }
        }
    }

    function refreshPage() {
        location.reload();
    }
</script>
@endpush