@extends('layouts.admin')

@section('title', 'Dashboard Ejecutivo')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="dashboard-title">
                <i class="fas fa-chart-line me-2"></i>
                Dashboard Ejecutivo
            </h1>
            <p class="dashboard-subtitle mb-0">Resumen ejecutivo y métricas clave del banco</p>
        </div>
        <div class="header-actions">
            <div class="btn-group">
                <button class="btn btn-outline-primary btn-sm" id="refreshAll" title="Actualizar todo">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="fullscreenToggle" title="Pantalla completa">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
            <div class="last-update ms-3">
                <small class="text-muted">
                    <i class="fas fa-clock me-1"></i>
                    <span id="lastUpdateTime">{{ now()->format('d/m/Y H:i') }}</span>
                </small>
            </div>
        </div>
    </div>
@stop

@section('content')
<div class="dashboard-container">
    <!-- KPIs Principales -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="kpi-section">
                <h5 class="section-title">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Indicadores Clave de Rendimiento
                </h5>
                <div class="row g-3">
                    <!-- Cartera Total -->
                    <div class="col-xl-3 col-lg-6 col-md-3">
                        <div class="kpi-card primary">
                            <div class="kpi-content">
                                <div class="kpi-icon">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <div class="kpi-info">
                                    <h3 class="kpi-value" id="carteraTotal">S/ 0.00</h3>
                                    <p class="kpi-label">Cartera Total</p>
                                    <div class="kpi-trend">
                                        <span class="trend-indicator positive">
                                            <i class="fas fa-arrow-up"></i> +5.2%
                                        </span>
                                        <small>vs mes anterior</small>
                                    </div>
                                </div>
                            </div>
                            <div class="kpi-chart">
                                <canvas id="carteraChart" height="60"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Mora -->
                    <div class="col-xl-3 col-lg-6 col-md-3">
                        <div class="kpi-card danger">
                            <div class="kpi-content">
                                <div class="kpi-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="kpi-info">
                                    <h3 class="kpi-value" id="indiceMora">0.00%</h3>
                                    <p class="kpi-label">Índice de Mora</p>
                                    <div class="kpi-trend">
                                        <span class="trend-indicator negative">
                                            <i class="fas fa-arrow-down"></i> -0.8%
                                        </span>
                                        <small>vs mes anterior</small>
                                    </div>
                                </div>
                            </div>
                            <div class="kpi-alert" id="moraAlert">
                                <span class="alert-badge">{{ $cuotasVencidas->count() ?? 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Cobranza -->
                    <div class="col-xl-3 col-lg-6 col-md-3">
                        <div class="kpi-card success">
                            <div class="kpi-content">
                                <div class="kpi-icon">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <div class="kpi-info">
                                    <h3 class="kpi-value" id="cobranzaHoy">S/ 0.00</h3>
                                    <p class="kpi-label">Cobranza Hoy</p>
                                    <div class="kpi-trend">
                                        <span class="trend-indicator positive">
                                            <i class="fas fa-arrow-up"></i> +12.5%
                                        </span>
                                        <small>vs ayer</small>
                                    </div>
                                </div>
                            </div>
                            <div class="kpi-progress">
                                <div class="progress-circle" data-percent="75">
                                    <span class="progress-text">75%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Desembolsos -->
                    <div class="col-xl-3 col-lg-6 col-md-3">
                        <div class="kpi-card info">
                            <div class="kpi-content">
                                <div class="kpi-icon">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </div>
                                <div class="kpi-info">
                                    <h3 class="kpi-value" id="desembolsosHoy">S/ 0.00</h3>
                                    <p class="kpi-label">Desembolsos Hoy</p>
                                    <div class="kpi-trend">
                                        <span class="trend-indicator positive">
                                            <i class="fas fa-arrow-up"></i> +8.3%
                                        </span>
                                        <small>vs ayer</small>
                                    </div>
                                </div>
                            </div>
                            <div class="kpi-mini-chart">
                                <canvas id="desembolsosChart" height="40"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Principal -->
    <div class="row">
        <!-- Columna Izquierda -->
        <div class="col-xl-8 col-lg-7">
            <!-- Alertas Críticas -->
            <div class="card alert-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bell text-warning me-2"></i>
                        Alertas Críticas
                        <span class="badge bg-danger ms-2" id="alertasCount">{{ $cuotasVencidas->count() ?? 0 }}</span>
                    </h5>
                    <div class="card-tools">
                        <button class="btn btn-sm btn-outline-primary" id="markAllRead">
                            <i class="fas fa-check-double"></i> Marcar leídas
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="alert-list" id="alertsList">
                        @if($cuotasVencidas->count() > 0)
                            @foreach($cuotasVencidas->take(5) as $cuota)
                                <div class="alert-item priority-high">
                                    <div class="alert-icon">
                                        <i class="fas fa-exclamation-circle text-danger"></i>
                                    </div>
                                    <div class="alert-content">
                                        <h6 class="alert-title">Cuota vencida - {{ $cuota->prestamo->cliente->persona->nombres ?? 'N/A' }}</h6>
                                        <p class="alert-description">
                                            Cuota #{{ $cuota->numero }} | Vencida hace {{ now()->diffInDays($cuota->fecha_pago) }} días
                                            | Monto: S/ {{ number_format($cuota->monto, 2) }}
                                        </p>
                                    </div>
                                    <div class="alert-actions">
                                        <a href="{{ route('admin.prestamos.show', $cuota->prestamo_id) }}" 
                                           class="btn btn-sm btn-outline-primary">Ver</a>
                                        <a href="{{ route('admin.gestiones.create', ['prestamo_id' => $cuota->prestamo_id]) }}" 
                                           class="btn btn-sm btn-outline-warning">Gestionar</a>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="alert-item no-alerts">
                                <div class="text-center py-4">
                                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                                    <h5>¡Excelente!</h5>
                                    <p class="text-muted">No hay alertas críticas en este momento</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Gráfico de Cartera -->
            <div class="card chart-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-area me-2"></i>
                        Evolución de Cartera
                    </h5>
                    <div class="card-tools">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary active" data-period="7d">7D</button>
                            <button class="btn btn-outline-primary" data-period="30d">30D</button>
                            <button class="btn btn-outline-primary" data-period="90d">90D</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="carteraEvolutionChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Compromisos del Día -->
            <div class="card commitment-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-handshake me-2"></i>
                        Compromisos del Día
                        <span class="badge bg-warning ms-2">{{ $compromisosPendientes->count() ?? 0 }}</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="commitment-timeline" id="commitmentsTimeline">
                        @if($compromisosPendientes->count() > 0)
                            @foreach($compromisosPendientes->take(5) as $compromiso)
                                <div class="timeline-item">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-header">
                                            <h6>{{ $compromiso->prestamo->cliente->persona->nombres ?? 'N/A' }}</h6>
                                            <span class="timeline-time">{{ \Carbon\Carbon::parse($compromiso->hora)->format('H:i') }}</span>
                                        </div>
                                        <p class="timeline-description">
                                            Compromiso de pago: S/ {{ number_format($compromiso->monto, 2) }}
                                        </p>
                                        <div class="timeline-actions">
                                            <button class="btn btn-sm btn-success" onclick="marcarCompromiso({{ $compromiso->id }}, 'completado')">
                                                <i class="fas fa-check"></i> Completado
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="editarCompromiso({{ $compromiso->id }})">
                                                <i class="fas fa-edit"></i> Editar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="no-commitments">
                                <div class="text-center py-4">
                                    <i class="fas fa-calendar-check text-success fa-3x mb-3"></i>
                                    <h5>Sin compromisos pendientes</h5>
                                    <p class="text-muted">No hay compromisos programados para hoy</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha -->
        <div class="col-xl-4 col-lg-5">
            <!-- Widget de Actividad Reciente -->
            <div class="card activity-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Actividad Reciente
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="activity-feed" id="activityFeed">
                        @if($gestionesRecientes->count() > 0)
                            @foreach($gestionesRecientes->take(5) as $gestion)
                                <div class="activity-item">
                                    <div class="activity-avatar">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p class="activity-text">
                                            <strong>{{ $gestion->asesor->name ?? 'Usuario' }}</strong>
                                            registró una gestión
                                        </p>
                                        <small class="activity-time">{{ $gestion->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="no-activity">
                                <div class="text-center py-3">
                                    <i class="fas fa-inbox text-muted fa-2x mb-2"></i>
                                    <p class="text-muted">Sin actividad reciente</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Estadísticas Rápidas -->
            <div class="card stats-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Estadísticas Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value">{{ $prestamosNuevos ?? 0 }}</div>
                            <div class="stat-label">Nuevos Préstamos</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">{{ $compromisosPendientes->count() ?? 0 }}</div>
                            <div class="stat-label">Compromisos Hoy</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">{{ $gestionesRecientes->count() ?? 0 }}</div>
                            <div class="stat-label">Gestiones Hoy</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">{{ $cuotasVencidas->count() ?? 0 }}</div>
                            <div class="stat-label">Cuotas Vencidas</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Estado de Préstamos -->
            <div class="card pie-chart-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Estado de Préstamos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="prestamosStatusChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.min.css">
<style>
    /* Variables CSS */
    :root {
        --primary-color: #2563eb;
        --secondary-color: #64748b;
        --success-color: #059669;
        --warning-color: #d97706;
        --danger-color: #dc2626;
        --info-color: #0891b2;
        --light-bg: #f8fafc;
        --white: #ffffff;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --border-color: #e2e8f0;
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        --radius-sm: 0.375rem;
        --radius-md: 0.5rem;
        --radius-lg: 0.75rem;
    }

    /* Reset y Base */
    body {
        font-family: 'Inter', sans-serif;
        background-color: var(--light-bg);
        color: var(--text-primary);
        line-height: 1.6;
    }

    /* Dashboard Header */
    .dashboard-title {
        font-size: 1.875rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }

    .dashboard-subtitle {
        color: var(--text-secondary);
        font-size: 0.875rem;
    }

    .header-actions {
        display: flex;
        align-items: center;
    }

    .last-update {
        font-size: 0.75rem;
    }

    /* Dashboard Container */
    .dashboard-container {
        padding: 0;
    }

    /* Section Titles */
    .section-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
    }

    /* KPI Cards */
    .kpi-section {
        background: var(--white);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
    }

    .kpi-card {
        background: var(--white);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        box-shadow: var(--shadow-md);
        border: 1px solid var(--border-color);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
        height: 140px;
    }

    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .kpi-card.primary { border-left: 4px solid var(--primary-color); }
    .kpi-card.success { border-left: 4px solid var(--success-color); }
    .kpi-card.warning { border-left: 4px solid var(--warning-color); }
    .kpi-card.danger { border-left: 4px solid var(--danger-color); }
    .kpi-card.info { border-left: 4px solid var(--info-color); }

    .kpi-content {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
    }

    .kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: var(--white);
    }

    .kpi-card.primary .kpi-icon { background: var(--primary-color); }
    .kpi-card.success .kpi-icon { background: var(--success-color); }
    .kpi-card.warning .kpi-icon { background: var(--warning-color); }
    .kpi-card.danger .kpi-icon { background: var(--danger-color); }
    .kpi-card.info .kpi-icon { background: var(--info-color); }

    .kpi-info {
        flex: 1;
    }

    .kpi-value {
        font-size: 1.875rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
        line-height: 1;
    }

    .kpi-label {
        font-size: 0.875rem;
        color: var(--text-secondary);
        margin: 0.25rem 0 0.5rem 0;
    }

    .kpi-trend {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.75rem;
    }

    .trend-indicator {
        padding: 0.125rem 0.375rem;
        border-radius: var(--radius-sm);
        font-weight: 500;
    }

    .trend-indicator.positive {
        background-color: #dcfce7;
        color: var(--success-color);
    }

    .trend-indicator.negative {
        background-color: #fef2f2;
        color: var(--danger-color);
    }

    /* KPI Alerts */
    .kpi-alert {
        position: absolute;
        top: 1rem;
        right: 1rem;
    }

    .alert-badge {
        background: var(--danger-color);
        color: var(--white);
        padding: 0.25rem 0.5rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        animation: pulse 2s infinite;
    }

    /* Progress Circle */
    .kpi-progress {
        position: absolute;
        bottom: 1rem;
        right: 1rem;
    }

    .progress-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: conic-gradient(var(--success-color) 0deg, var(--success-color) 270deg, #e5e7eb 270deg);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .progress-circle::before {
        content: '';
        width: 30px;
        height: 30px;
        background: var(--white);
        border-radius: 50%;
        position: absolute;
    }

    .progress-text {
        font-size: 0.625rem;
        font-weight: 600;
        z-index: 1;
    }

    /* Cards */
    .card {
        background: var(--white);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        transition: all 0.3s ease;
    }

    .card-header {
        background: var(--white);
        border-bottom: 1px solid var(--border-color);
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .card-body {
        padding: 1.5rem;
    }

    /* Alert Card */
    .alert-list {
        max-height: 400px;
        overflow-y: auto;
    }

    .alert-item {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
        transition: background-color 0.2s ease;
    }

    .alert-item:hover {
        background-color: #f8fafc;
    }

    .alert-item:last-child {
        border-bottom: none;
    }

    .alert-icon {
        font-size: 1.25rem;
        margin-top: 0.125rem;
    }

    .alert-content {
        flex: 1;
    }

    .alert-title {
        font-size: 0.875rem;
        font-weight: 600;
        margin: 0 0 0.25rem 0;
        color: var(--text-primary);
    }

    .alert-description {
        font-size: 0.75rem;
        color: var(--text-secondary);
        margin: 0;
    }

    .alert-actions {
        display: flex;
        gap: 0.5rem;
    }

    /* Timeline */
    .commitment-timeline {
        padding: 1rem 1.5rem;
    }

    .timeline-item {
        display: flex;
        gap: 1rem;
        padding-bottom: 1.5rem;
        position: relative;
    }

    .timeline-item:not(:last-child)::after {
        content: '';
        position: absolute;
        left: 7px;
        top: 24px;
        width: 2px;
        height: calc(100% - 16px);
        background: var(--border-color);
    }

    .timeline-marker {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: var(--warning-color);
        border: 3px solid var(--white);
        box-shadow: 0 0 0 1px var(--border-color);
        margin-top: 2px;
        flex-shrink: 0;
    }

    .timeline-content {
        flex: 1;
    }

    .timeline-header {
        display: flex;
        justify-content: between;
        align-items: center;
        margin-bottom: 0.25rem;
    }

    .timeline-header h6 {
        font-size: 0.875rem;
        font-weight: 600;
        margin: 0;
        flex: 1;
    }

    .timeline-time {
        font-size: 0.75rem;
        color: var(--text-secondary);
        font-weight: 500;
    }

    .timeline-description {
        font-size: 0.75rem;
        color: var(--text-secondary);
        margin: 0 0 0.75rem 0;
    }

    .timeline-actions {
        display: flex;
        gap: 0.5rem;
    }

    /* Activity Feed */
    .activity-feed {
        max-height: 300px;
        overflow-y: auto;
    }

    .activity-item {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.75rem 1.5rem;
        border-bottom: 1px solid var(--border-color);
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--white);
        font-size: 0.75rem;
        flex-shrink: 0;
    }

    .activity-content {
        flex: 1;
    }

    .activity-text {
        font-size: 0.75rem;
        margin: 0 0 0.25rem 0;
        color: var(--text-primary);
    }

    .activity-time {
        font-size: 0.625rem;
        color: var(--text-secondary);
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .stat-item {
        text-align: center;
        padding: 1rem;
        background: #f8fafc;
        border-radius: var(--radius-md);
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
    }

    .stat-label {
        font-size: 0.75rem;
        color: var(--text-secondary);
    }

    /* Chart Container */
    .chart-container {
        position: relative;
        height: 300px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .dashboard-title {
            font-size: 1.5rem;
        }
        
        .header-actions {
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
        }
        
        .kpi-card {
            height: auto;
            min-height: 120px;
        }
        
        .kpi-content {
            flex-direction: column;
            text-align: center;
        }
        
        .alert-item {
            flex-direction: column;
            text-align: center;
        }
        
        .timeline-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    /* Animations */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .fade-in {
        animation: fadeIn 0.3s ease-out;
    }

    /* Loading States */
    .loading {
        opacity: 0.6;
        pointer-events: none;
        position: relative;
    }

    .loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 20px;
        height: 20px;
        margin: -10px 0 0 -10px;
        border: 2px solid var(--border-color);
        border-top-color: var(--primary-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script>
$(document).ready(function() {
    // Configuración global
    const config = {
        updateInterval: 30000, // 30 segundos
        charts: {},
        data: {
            carteraTotal: 0,
            indiceMora: 0,
            cobranzaHoy: 0,
            desembolsosHoy: 0
        }
    };

    // Inicializar dashboard
    initializeDashboard();

    function initializeDashboard() {
        console.log('Initializing dashboard...');
        hideLoading(); // Ensure no loading state on start
        setupEventListeners();
        updateTimestamp();
        
        // Load data after a short delay to ensure DOM is ready
        setTimeout(function() {
            loadInitialData();
        }, 100);
    }

    // Cargar datos iniciales
    function loadInitialData() {
        showLoading();
        
        $.ajax({
            url: '{{ route("admin.dashboard.statistics") }}',
            method: 'GET',
            success: function(response) {
                console.log('Dashboard data loaded:', response);
                // Actualizar gráficos con los datos reales
                if (response.cuotasPorMes) {
                    updateChartsWithData(response);
                }
                hideLoading();
            },
            error: function(xhr, status, error) {
                console.error('Error cargando datos iniciales:', error);
                hideLoading();
            },
            complete: function() {
                // Asegurar que siempre se quita el loading
                hideLoading();
            }
        });
    }

    // Actualizar gráficos con datos reales
    function updateChartsWithData(data) {
        // Simplemente loguear por ahora para evitar errores
        console.log('Charts would be updated with:', data);
    }

    // Configurar gráficos
    function setupCharts() {
        // Gráfico de evolución de cartera
        const carteraCtx = document.getElementById('carteraEvolutionChart');
        if (carteraCtx) {
            config.charts.cartera = new Chart(carteraCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Cartera Total',
                        data: [],
                        borderColor: 'rgb(37, 99, 235)',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'S/ ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Gráfico de estado de préstamos
        const statusCtx = document.getElementById('prestamosStatusChart');
        if (statusCtx) {
            config.charts.status = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Vigente', 'Moroso', 'Nuevo', 'Pagado'],
                    datasets: [{
                        data: [0, 0, 0, 0],
                        backgroundColor: [
                            'rgb(5, 150, 105)',
                            'rgb(220, 38, 38)',
                            'rgb(37, 99, 235)',
                            'rgb(100, 116, 139)'
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
    }

    // Event listeners
    function setupEventListeners() {
        // Refresh all
        $('#refreshAll').click(function() {
            loadInitialData();
        });

        // Fullscreen toggle
        $('#fullscreenToggle').click(function() {
            toggleFullscreen();
        });

        // Mark all alerts as read
        $('#markAllRead').click(function() {
            markAllAlertsRead();
        });

        // Period buttons
        $('[data-period]').click(function() {
            $('[data-period]').removeClass('active');
            $(this).addClass('active');
            const period = $(this).data('period');
            updateChartPeriod(period);
        });
    }

    // Actualizaciones en tiempo real (temporalmente deshabilitado)
    function startRealTimeUpdates() {
        console.log('Real-time updates disabled for debugging');
        // setInterval(function() {
        //     checkForUpdates();
        // }, config.updateInterval);
    }

    function checkForUpdates() {
        $.ajax({
            url: '{{ route("admin.dashboard.check-updates") }}',
            method: 'GET',
            success: function(response) {
                if (response.hasUpdates) {
                    updateKPIs(response.kpis);
                    updateAlerts(response.alerts);
                    updateActivity(response.activity);
                    updateTimestamp();
                }
            }
        });
    }

    // Actualizar KPIs
    function updateKPIs(kpis) {
        $('#carteraTotal').text('S/ ' + kpis.carteraTotal.toLocaleString('es-PE', {minimumFractionDigits: 2}));
        $('#indiceMora').text(kpis.indiceMora.toFixed(2) + '%');
        $('#cobranzaHoy').text('S/ ' + kpis.cobranzaHoy.toLocaleString('es-PE', {minimumFractionDigits: 2}));
        $('#desembolsosHoy').text('S/ ' + kpis.desembolsosHoy.toLocaleString('es-PE', {minimumFractionDigits: 2}));
        $('#moraAlert .alert-badge').text(kpis.cuotasVencidas);
        $('#alertasCount').text(kpis.cuotasVencidas);
    }

    // Actualizar gráficos
    function updateCharts(chartData) {
        if (config.charts.cartera && chartData.cartera) {
            config.charts.cartera.data.labels = chartData.cartera.labels;
            config.charts.cartera.data.datasets[0].data = chartData.cartera.data;
            config.charts.cartera.update();
        }

        if (config.charts.status && chartData.status) {
            config.charts.status.data.datasets[0].data = chartData.status.data;
            config.charts.status.update();
        }
    }

    // Funciones de utilidad
    function showLoading() {
        $('.dashboard-container').addClass('loading');
    }

    function hideLoading() {
        $('.dashboard-container').removeClass('loading');
    }

    function updateTimestamp() {
        $('#lastUpdateTime').text(moment().format('DD/MM/YYYY HH:mm'));
    }

    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
            $('#fullscreenToggle i').removeClass('fa-expand').addClass('fa-compress');
        } else {
            document.exitFullscreen();
            $('#fullscreenToggle i').removeClass('fa-compress').addClass('fa-expand');
        }
    }

    function markAllAlertsRead() {
        // Simple client-side alert dismissal
        $('.alert-item').fadeOut();
        $('#alertasCount').text('0');
        // TODO: Implement server-side alert management if needed
    }

    // Funciones globales para compromisos
    window.marcarCompromiso = function(id, estado) {
        $.ajax({
            url: `{{ route("admin.compromisos.update", ":id") }}`.replace(':id', id),
            method: 'PUT',
            data: {
                _token: '{{ csrf_token() }}',
                estado: estado
            },
            success: function() {
                $(`.timeline-item[data-id="${id}"]`).fadeOut();
                loadInitialData(); // Refresh data
            }
        });
    };

    window.editarCompromiso = function(id) {
        window.location.href = `{{ route("admin.compromisos.edit", ":id") }}`.replace(':id', id);
    };

    // Optimizaciones de rendimiento
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Lazy loading para tablas grandes
    function setupLazyLoading() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    loadSectionData(element.dataset.section);
                    observer.unobserve(element);
                }
            });
        });

        document.querySelectorAll('[data-lazy-load]').forEach(el => {
            observer.observe(el);
        });
    }

    setupLazyLoading();
});
</script>
@stop