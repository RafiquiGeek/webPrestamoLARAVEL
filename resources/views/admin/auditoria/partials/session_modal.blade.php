<div class="modal fade" id="sessionModal{{ $session->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-gradient-primary">
                <h4 class="modal-title text-white">
                    <i class="fas fa-info-circle"></i>
                    Detalle de Sesión - {{ $session->user->name }}
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {{-- Información general --}}
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-info">
                                <i class="fas fa-user"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Usuario</span>
                                <span class="info-box-number">{{ $session->user->name }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon bg-{{ $session->isActive() ? 'success' : 'secondary' }}">
                                <i class="fas fa-{{ $session->isActive() ? 'check-circle' : 'stop-circle' }}"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Estado</span>
                                <span class="info-box-number">{{ $session->isActive() ? 'Activa' : 'Finalizada' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Detalles de tiempo --}}
                <h5 class="mb-3">
                    <i class="fas fa-clock text-primary"></i>
                    Información de Tiempo
                </h5>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong><i class="fas fa-sign-in-alt text-success"></i> Inicio:</strong>
                        <p class="mb-0">{{ $session->login_time->format('d/m/Y H:i:s') }}</p>
                    </div>
                    <div class="col-md-4">
                        <strong><i class="fas fa-sign-out-alt text-danger"></i> Fin:</strong>
                        <p class="mb-0">
                            @if($session->logout_time)
                                {{ $session->logout_time->format('d/m/Y H:i:s') }}
                            @else
                                <span class="text-success">En curso</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-4">
                        <strong><i class="fas fa-hourglass-half text-info"></i> Duración:</strong>
                        <p class="mb-0">
                            <span class="badge badge-primary">{{ $session->duration_formatted }}</span>
                        </p>
                    </div>
                </div>

                {{-- Información técnica --}}
                <h5 class="mb-3">
                    <i class="fas fa-cog text-primary"></i>
                    Información Técnica
                </h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong><i class="fas fa-network-wired text-info"></i> IP:</strong>
                        <p class="mb-2">
                            <span class="badge badge-light">{{ $session->ip_address }}</span>
                        </p>
                        <strong><i class="fas fa-exclamation-triangle text-warning"></i> Logout forzado:</strong>
                        <p class="mb-0">
                            @if($session->forced_logout)
                                <span class="badge badge-danger">Sí</span>
                            @else
                                <span class="badge badge-success">No</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="fas fa-fingerprint text-secondary"></i> ID Sesión:</strong>
                        <p class="mb-0">
                            <small class="text-muted font-monospace">{{ $session->session_id }}</small>
                        </p>
                    </div>
                </div>

                <div class="mb-3">
                    <strong><i class="fas fa-desktop text-primary"></i> User Agent:</strong>
                    <p class="bg-light p-2 rounded">
                        <small class="text-muted">{{ $session->user_agent }}</small>
                    </p>
                </div>

                {{-- Tiempo por módulos --}}
                @if($session->moduleTimeTracking->count() > 0)
                    <hr>
                    <h5 class="mb-3">
                        <i class="fas fa-chart-bar text-primary"></i>
                        Tiempo por Módulos
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th><i class="fas fa-cube"></i> Módulo</th>
                                    <th><i class="fas fa-layer-group"></i> Sección</th>
                                    <th><i class="fas fa-clock"></i> Tiempo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($session->moduleTimeTracking->where('duration', '>', 0) as $tracking)
                                    <tr>
                                        <td>
                                            <span class="badge badge-info">{{ $tracking->module_name }}</span>
                                        </td>
                                        <td>{{ $tracking->module_section }}</td>
                                        <td>
                                            <span class="badge badge-primary">{{ $tracking->duration_formatted }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
