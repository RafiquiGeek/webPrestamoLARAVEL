@extends('layouts.admin')

@section('title', 'Sesiones de Usuario')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-0">
                <i class="fas fa-users text-primary"></i>
                @if(!isset($verHistorial) || !$verHistorial)
                    Usuarios Conectados
                @else
                    Historial de Sesiones
                @endif
            </h1>
            <p class="text-muted mb-0 mt-1">
                <i class="fas fa-info-circle"></i>
                @if(!isset($verHistorial) || !$verHistorial)
                    Usuarios actualmente en línea - Monitoreo en tiempo real
                @else
                    Historial completo de todas las sesiones registradas
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            @if(!isset($verHistorial) || !$verHistorial)
                <a href="{{ route('admin.auditoria.sesiones', ['ver_historial' => 1]) }}" class="btn btn-outline-primary" title="Ver historial completo de sesiones">
                    <i class="fas fa-history"></i> Ver Historial Completo
                </a>
            @else
                <a href="{{ route('admin.auditoria.sesiones') }}" class="btn btn-outline-primary" title="Volver a usuarios conectados">
                    <i class="fas fa-users"></i> Usuarios Conectados
                </a>
            @endif
            <button class="btn btn-success" id="refreshBtn" title="Actualizar datos">
                <i class="fas fa-sync-alt"></i> Actualizar
            </button>
            <button class="btn btn-warning" id="cleanSessionsBtn" title="Cerrar sesiones abandonadas (inactivas por más de 2 horas)">
                <i class="fas fa-broom"></i> Limpiar
            </button>
            <a href="{{ route('admin.auditoria.reporte-sesiones') }}" class="btn btn-info">
                <i class="fas fa-chart-pie"></i> Reportes
            </a>
        </div>
    </div>
@stop

@section('content')
{{-- Cards de estadísticas rápidas --}}
{{-- Cards de estadísticas rápidas --}}
<div class="row mb-4">
    @if(!isset($verHistorial) || !$verHistorial)
        {{-- Vista de usuarios conectados --}}
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stats-card">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <h2 class="mb-0 font-weight-bolder text-dark">{{ $sessions->total() }}</h2>
                        <span class="text-muted text-uppercase small font-weight-bold tracking-wide">Usuarios Conectados</span>
                    </div>
                    <div class="p-3 rounded-circle bg-light text-primary d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 py-2 px-4">
                    <div class="d-flex align-items-center text-success small font-weight-bold">
                        <span class="pulse-dot mr-2"></span> En tiempo real
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6 mb-3">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stats-card">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <h2 class="mb-0 font-weight-bolder text-dark">{{ \App\Models\UserSession::whereDate('login_time', today())->count() }}</h2>
                        <span class="text-muted text-uppercase small font-weight-bold tracking-wide">Sesiones Hoy</span>
                    </div>
                    <div class="p-3 rounded-circle bg-light text-info d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-calendar-day fa-2x"></i>
                    </div>
                </div>
                 <div class="card-footer bg-white border-0 py-2 px-4">
                    <div class="d-flex align-items-center text-info small font-weight-bold">
                        <i class="fas fa-plus-circle mr-2"></i> Nuevas hoy
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6 mb-3">
             <div class="card border-0 shadow-sm h-100 overflow-hidden stats-card">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <h2 class="mb-0 font-weight-bolder text-dark">{{ \App\Models\User::whereHas('userSessions', function($q) { $q->whereNull('logout_time'); })->count() }}</h2>
                        <span class="text-muted text-uppercase small font-weight-bold tracking-wide">Usuarios Únicos</span>
                    </div>
                    <div class="p-3 rounded-circle bg-light text-success d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-fingerprint fa-2x"></i>
                    </div>
                </div>
                 <div class="card-footer bg-white border-0 py-2 px-4">
                    <div class="d-flex align-items-center text-secondary small font-weight-bold">
                        <i class="fas fa-check mr-2"></i> Activos actualmente
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Vista de historial --}}
        <div class="col-lg-3 col-md-6 mb-3">
             <div class="card border-0 shadow-sm h-100 stats-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="text-muted small text-uppercase font-weight-bold">Sesiones Activas</span>
                            <h3 class="font-weight-bold mb-0 text-dark">{{ $sessions->where('logout_time', null)->count() }}</h3>
                        </div>
                        <div class="text-success bg-light rounded p-2">
                             <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 3px;">
                        <div class="progress-bar bg-success" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100 stats-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                         <div>
                            <span class="text-muted small text-uppercase font-weight-bold">Total Sesiones</span>
                            <h3 class="font-weight-bold mb-0 text-dark">{{ $sessions->total() }}</h3>
                        </div>
                         <div class="text-info bg-light rounded p-2">
                             <i class="fas fa-list"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 3px;">
                        <div class="progress-bar bg-info" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
             <div class="card border-0 shadow-sm h-100 stats-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                         <div>
                            <span class="text-muted small text-uppercase font-weight-bold">Finalizadas</span>
                            <h3 class="font-weight-bold mb-0 text-dark">{{ $sessions->where('logout_time', '!=', null)->count() }}</h3>
                        </div>
                         <div class="text-warning bg-light rounded p-2">
                             <i class="fas fa-sign-out-alt"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 3px;">
                        <div class="progress-bar bg-warning" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
             <div class="card border-0 shadow-sm h-100 stats-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                         <div>
                            <span class="text-muted small text-uppercase font-weight-bold">Cierres Forzados</span>
                            <h3 class="font-weight-bold mb-0 text-dark">{{ $sessions->where('forced_logout', true)->count() }}</h3>
                        </div>
                         <div class="text-danger bg-light rounded p-2">
                             <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 3px;">
                        <div class="progress-bar bg-danger" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Filtros de búsqueda (solo en modo historial) --}}
@if(isset($verHistorial) && $verHistorial)
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm collapsed-card">
            <div class="card-header bg-light">
                <h3 class="card-title">
                    <i class="fas fa-filter text-primary"></i>
                    Filtros de Búsqueda Avanzada
                </h3>
                <div class="card-tools">
                    <span class="badge badge-primary mr-2" id="activeFiltersCount">0 filtros activos</span>
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Expandir/Contraer">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.auditoria.sesiones') }}" id="filterForm">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="usuario_id">
                                    <i class="fas fa-user"></i> Usuario
                                </label>
                                <select name="usuario_id" id="usuario_id" class="form-control select2">
                                    <option value="">Todos los usuarios</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('usuario_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="fecha_inicio">
                                    <i class="fas fa-calendar-alt"></i> Fecha Inicio
                                </label>
                                <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="fecha_fin">
                                    <i class="fas fa-calendar-check"></i> Fecha Fin
                                </label>
                                <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="estado">
                                    <i class="fas fa-info-circle"></i> Estado
                                </label>
                                <select name="estado" id="estado" class="form-control">
                                    <option value="">Todas las sesiones</option>
                                    <option value="activa" {{ request('estado') == 'activa' ? 'selected' : '' }}>
                                        🟢 Activas
                                    </option>
                                    <option value="finalizada" {{ request('estado') == 'finalizada' ? 'selected' : '' }}>
                                        ⚫ Finalizadas
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <div class="btn-group w-100" role="group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <a href="{{ route('admin.auditoria.sesiones') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-eraser"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@if(!isset($verHistorial) || !$verHistorial)
    {{-- Vista de usuarios conectados (Lista Simple Refinada) --}}
    <div class="card shadow-sm border-0">
        <div class="list-group list-group-flush">
            @forelse($sessions as $session)
                <div class="list-group-item p-3 session-row hover-bg-light transition-all">
                    <div class="row align-items-center">
                        {{-- Usuario --}}
                        <div class="col-md-4 mb-2 mb-md-0 d-flex align-items-center">
                            <div class="position-relative mr-3">
                                <div class="user-avatar rounded-circle d-flex align-items-center justify-content-center text-white font-weight-bold shadow-sm" 
                                     style="width: 42px; height: 42px; background: {{ $session->status == 'online' ? 'linear-gradient(45deg, #28a745, #20c997)' : 'linear-gradient(45deg, #ffc107, #fd7e14)' }};">
                                    {{ substr($session->user->name, 0, 1) }}
                                </div>
                                <span class="position-absolute border border-white rounded-circle" 
                                      style="width: 12px; height: 12px; bottom: 0; right: 0; background-color: {{ $session->status == 'online' ? '#28a745' : '#ffc107' }}; border-width: 2px !important;"
                                      title="{{ $session->status == 'online' ? 'Online' : 'Ausente' }}">
                                </span>
                            </div>
                            <div class="overflow-hidden">
                                <h6 class="mb-0 font-weight-bold text-dark text-truncate">{{ $session->user->codigo }}</h6>
                                <small class="text-{{ $session->status == 'online' ? 'success' : 'warning' }} font-weight-bold">
                                    {{ $session->status == 'online' ? 'Online ahora' : 'Inactivo recientemente' }}
                                </small>
                            </div>
                        </div>

                        {{-- Actividad --}}
                        <div class="col-md-4 mb-2 mb-md-0 border-left-md pl-md-3">
                            @if($session->last_activity)
                                <div class="d-flex flex-column">
                                    <small class="text-muted text-uppercase font-weight-bold" style="font-size: 0.7rem;">Módulo Actual</small>
                                    <span class="text-dark font-weight-bold text-truncate">{{ $session->last_activity->module_name }}</span>
                                    <small class="text-muted text-truncate">{{ $session->last_activity->module_section }}</small>
                                </div>
                            @else
                                <span class="text-muted font-italic small">Explorando el sistema...</span>
                            @endif
                        </div>

                        {{-- Tiempos y Acciones --}}
                        <div class="col-md-4 d-flex align-items-center justify-content-end">
                            <div class="text-right mr-3 d-none d-md-block">
                                <small class="text-muted d-block">Inicio: {{ $session->login_time->format('H:i') }}</small>
                                <span class="badge badge-light border">{{ $session->duration_formatted }}</span>
                            </div>
                            
                            <div class="btn-group">
                                <a href="{{ route('admin.auditoria.sesiones-usuario', $session->user_id) }}" class="btn btn-sm btn-light text-muted" title="Historial Completo" data-toggle="tooltip">
                                    <i class="fas fa-history"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-light text-primary" data-toggle="modal" data-target="#sessionModal{{ $session->id }}" title="Ver Detalles" data-toggle="tooltip">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Modal (Reutilizado) --}}
                @include('admin.auditoria.partials.session_modal', ['session' => $session])

            @empty
                <div class="text-center py-5">
                    <img src="https://cdni.iconscout.com/illustration/premium/thumb/empty-state-2130362-1800926.png" alt="No connected users" style="width: 200px; opacity: 0.5;">
                    <h5 class="text-muted mt-3">Sin usuarios connectados</h5>
                    <p class="text-muted text-small">Nadie ha iniciado sesión recientemente.</p>
                </div>
            @endforelse
        </div>
    </div>
    
    <div class="d-flex justify-content-center mt-3">
        {{ $sessions->links() }}
    </div>

@else
    {{-- Vista de Tabla Clásica para Historial --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h3 class="card-title mb-0">
                <i class="fas fa-history text-info"></i> Historial de Sesiones
            </h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap align-middle" id="sessionsTable">
                <thead class="bg-light">
                    <tr>
                        <th><i class="fas fa-user"></i> Usuario</th>
                        <th><i class="fas fa-sign-in-alt"></i> Inicio</th>
                        <th><i class="fas fa-sign-out-alt"></i> Fin</th>
                        <th><i class="fas fa-clock"></i> Duración</th>
                        <th class="text-center"><i class="fas fa-info-circle"></i> Estado</th>
                        <th><i class="fas fa-network-wired"></i> IP</th>
                        <th class="text-center"><i class="fas fa-tools"></i> Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar mr-2">
                                        <i class="fas fa-user-circle fa-2x text-secondary"></i>
                                    </div>
                                    <div>
                                        <span class="font-weight-bold">{{ $session->user->name }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $session->login_time->format('d/m/Y H:i:s') }}</td>
                            <td>
                                @if($session->logout_time)
                                    {{ $session->logout_time->format('d/m/Y H:i:s') }}
                                @else
                                    <span class="text-success font-weight-bold">Activa</span>
                                @endif
                            </td>
                            <td>{{ $session->duration_formatted }}</td>
                            <td class="text-center">
                                @if($session->isActive())
                                    <span class="badge badge-success">Activa</span>
                                @else
                                    <span class="badge badge-secondary">Finalizada</span>
                                @endif
                            </td>
                            <td>{{ $session->ip_address }}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#sessionModal{{ $session->id }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        @include('admin.auditoria.partials.session_modal', ['session' => $session])
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <h5 class="text-muted">No se encontraron sesiones</h5>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sessions->hasPages())
            <div class="card-footer clearfix bg-white">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>
@endif
@stop

@section('css')
<style>
    /* Animaciones y transiciones */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .pulse-animation {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* Cards mejorados */
    .small-box {
        border-radius: 0.5rem;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .small-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }

    .small-box .icon {
        transition: transform 0.3s ease;
    }

    .small-box:hover .icon {
        transform: scale(1.1);
    }

    /* Tabla mejorada */
    .table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .table thead th {
        border-top: none;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        color: #495057;
        padding: 1rem 0.75rem;
        vertical-align: middle;
    }

    .table tbody tr {
        transition: all 0.3s ease;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
        transform: scale(1.01);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .table td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
    }

    /* Badges mejorados */
    .badge {
        font-size: 0.8em;
        font-weight: 500;
        letter-spacing: 0.3px;
        transition: all 0.3s ease;
    }

    .badge-lg {
        font-size: 0.9em;
        padding: 0.5rem 0.75rem !important;
    }

    .badge:hover {
        transform: scale(1.05);
    }

    .badge-pill {
        border-radius: 50rem;
    }

    /* Botones mejorados */
    .btn {
        transition: all 0.3s ease;
        border-radius: 0.375rem;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .btn-group .btn {
        border-radius: 0.375rem;
    }

    /* Cards */
    .card {
        border: none;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        border-bottom: 1px solid #e3e6f0;
        padding: 1rem 1.25rem;
    }

    /* Modal mejorado */
    .modal-content {
        border-radius: 0.5rem;
        border: none;
    }

    .modal-header {
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
        padding: 1.25rem;
    }

    .modal-body {
        padding: 1.5rem;
    }

    /* Info boxes */
    .info-box {
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    /* Empty state */
    .empty-state {
        padding: 3rem 0;
    }

    /* Búsqueda rápida */
    #quickSearch {
        border-radius: 0.375rem 0 0 0.375rem;
    }

    /* User avatar */
    .user-avatar {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Form controls */
    .form-control, .select2-container--default .select2-selection--single {
        border-radius: 0.375rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* Espaciado mejorado */
    .gap-2 > * {
        margin-right: 0.5rem;
    }

    .gap-2 > *:last-child {
        margin-right: 0;
    }

    /* Monospace font */
    .font-monospace {
        font-family: 'Courier New', Courier, monospace;
    }

    /* Responsive improvements */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.85rem;
        }

        .small-box {
            margin-bottom: 1rem;
        }

        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
    }

    /* Loading state */
    .loading {
        opacity: 0.6;
        pointer-events: none;
    }

    /* Tooltips */
    .tooltip {
        font-size: 0.875rem;
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Initialize select2
    $('.select2').select2({
        theme: 'bootstrap4',
        placeholder: 'Seleccione...',
        allowClear: true
    });

    // Búsqueda rápida en tiempo real
    let searchTimeout;
    $('#quickSearch').on('keyup', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val().toLowerCase();

        searchTimeout = setTimeout(function() {
            $('#sessionsTable tbody tr.session-row').each(function() {
                const row = $(this);
                const text = row.text().toLowerCase();

                if (text.indexOf(searchTerm) > -1) {
                    row.show();
                } else {
                    row.hide();
                }
            });
        }, 300);
    });

    // Auto-refresh solo para vista de usuarios conectados (cada 30 segundos)
    @if(!isset($verHistorial) || !$verHistorial)
        let autoRefreshEnabled = true;
        const activeSessionsCount = parseInt($('#sesionesActivas').text());

        if (activeSessionsCount > 0) {
            setInterval(function() {
                if (autoRefreshEnabled) {
                    location.reload();
                }
            }, 30000); // 30 segundos
        }
    @endif

    // Botón de refresh manual
    $('#refreshBtn').on('click', function() {
        const btn = $(this);
        const icon = btn.find('i');

        // Animación de rotación
        icon.addClass('fa-spin');
        btn.prop('disabled', true);

        // Recargar página
        setTimeout(function() {
            location.reload();
        }, 500);
    });

    // Limpiar sesiones abandonadas
    $('#cleanSessionsBtn').on('click', function() {
        const btn = $(this);

        Swal.fire({
            title: '¿Cerrar sesiones abandonadas?',
            html: 'Se cerrarán automáticamente todas las sesiones inactivas por más de <strong>2 horas</strong>.<br><br>Esto ayudará a limpiar sesiones de usuarios que cerraron el navegador sin hacer logout.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-broom"></i> Sí, limpiar sesiones',
            cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch('{{ route("admin.auditoria.cerrar-sesiones-abandonadas") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ hours: 2 })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error al cerrar sesiones');
                    }
                    return response.json();
                })
                .then(data => {
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(`Error: ${error}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                const closedCount = result.value.closed_count;
                Swal.fire({
                    icon: 'success',
                    title: '¡Sesiones cerradas!',
                    html: `Se cerraron exitosamente <strong>${closedCount}</strong> sesiones abandonadas.<br><br>La página se recargará para mostrar los cambios.`,
                    timer: 3000,
                    showConfirmButton: false,
                    didClose: () => {
                        location.reload();
                    }
                });
            }
        });
    });

    // Exportar a Excel
    $('#exportBtn').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true);

        // Aquí puedes implementar la lógica de exportación
        // Por ahora solo mostramos un mensaje
        Swal.fire({
            icon: 'info',
            title: 'Exportando...',
            text: 'Esta funcionalidad estará disponible próximamente',
            timer: 2000,
            showConfirmButton: false
        });

        setTimeout(() => {
            btn.prop('disabled', false);
        }, 2000);
    });

    // Contar filtros activos
    function updateActiveFiltersCount() {
        let count = 0;

        $('#filterForm select, #filterForm input[type="date"]').each(function() {
            if ($(this).val() !== '') {
                count++;
            }
        });

        $('#activeFiltersCount').text(count + ' filtro' + (count !== 1 ? 's' : '') + ' activo' + (count !== 1 ? 's' : ''));

        if (count > 0) {
            $('#activeFiltersCount').removeClass('badge-primary').addClass('badge-success');
        } else {
            $('#activeFiltersCount').removeClass('badge-success').addClass('badge-primary');
        }
    }

    // Actualizar contador al cargar
    updateActiveFiltersCount();

    // Actualizar contador al cambiar filtros
    $('#filterForm select, #filterForm input').on('change', function() {
        updateActiveFiltersCount();
    });

    // Animación de entrada para las filas
    $('.session-row').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        }).delay(index * 50).animate({
            'opacity': '1'
        }, 300).css('transform', 'translateY(0)');
    });

    // Mejorar experiencia del modal
    $('.modal').on('show.bs.modal', function() {
        $(this).find('.modal-dialog').addClass('animate__animated animate__fadeInDown');
    });

    $('.modal').on('hide.bs.modal', function() {
        $(this).find('.modal-dialog').removeClass('animate__fadeInDown');
    });
});
</script>
@stop
