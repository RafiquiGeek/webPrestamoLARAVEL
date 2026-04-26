@php
$kpiConfig = [
    'total_ingresos' => [
        'icon' => 'fa-chart-line',
        'title' => 'Total Ingresos',
        'description' => 'Solo dinero cobrado'
    ],
    'efectivo_por_rendir' => [
        'icon' => 'fa-exclamation-triangle',
        'title' => 'Efectivo Pendiente',
        'description' => 'Dinero en efectivo por rendir'
    ],
    'efectivo_rendido' => [
        'icon' => 'fa-check-circle',
        'title' => 'Efectivo Rendido',
        'description' => 'Dinero en efectivo ya rendido'
    ],
    'transferencias_depositos' => [
        'icon' => 'fa-university',
        'title' => 'Transferencias/Depósitos',
        'description' => 'Pagos bancarios (no requieren rendición)'
    ],
    'yape_plin' => [
        'icon' => 'fa-mobile-alt',
        'title' => 'Yape/Plin',
        'description' => 'Pagos digitales'
    ],
    'porcentaje_rendido' => [
        'icon' => 'fa-percentage',
        'title' => 'Rendido',
        'description' => 'Porcentaje de efectivo rendido',
        'is_percentage' => true
    ]
];
@endphp

<!-- KPIs Principales -->
<div class="col-12">
    <div class="account-card">
        <div class="card-header">
            <h3><i class="fas fa-tachometer-alt me-2"></i>Indicadores de Rendición de Cuentas</h3>
        </div>
        <div class="card-body">
            <div class="row g-2">
                @foreach($kpis as $key => $value)
                    <div class="col-lg-2 col-md-2 col-6">
                        <div class="info-card">
                            <div class="info-label">
                                <i class="fas {{ $kpiConfig[$key]['icon'] }} me-1"></i>
                                {{ $kpiConfig[$key]['title'] }}
                            </div>
                            <div class="info-value">
                                @if(isset($kpiConfig[$key]['is_percentage']) && $kpiConfig[$key]['is_percentage'])
                                    {{ $value }}%
                                @else
                                    S/ {{ number_format($value, 2) }}
                                @endif
                            </div>
                            <small class="text-muted">{{ $kpiConfig[$key]['description'] }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Alerta de Rendición -->
            @if($kpis['efectivo_por_rendir'] > 0)
                <div class="alert alert-warning mt-3 mb-0" style="border-radius: 8px;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Atención:</strong> Hay <strong>S/ {{ number_format($kpis['efectivo_por_rendir'], 2) }}</strong> en efectivo pendiente de rendición.
                </div>
            @else
                <div class="alert alert-success mt-3 mb-0" style="border-radius: 8px;">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>¡Perfecto!</strong> Todo el efectivo ha sido rendido correctamente.
                </div>
            @endif
        </div>
    </div>
</div>