@extends('layouts.admin')

@section('title', 'Dashboard de Facturación Electrónica')

@section('content')
<div class="container-fluid">
    <!-- Título y filtros -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        Dashboard de Facturación Electrónica
                    </h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.facturacion.dashboard') }}" class="form-inline">
                        <label class="mr-2">Período:</label>
                        <input type="date" name="fecha_inicio" class="form-control mr-2"
                               value="{{ $fechaInicio->format('Y-m-d') }}">
                        <label class="mr-2">hasta</label>
                        <input type="date" name="fecha_fin" class="form-control mr-2"
                               value="{{ $fechaFin->format('Y-m-d') }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <a href="{{ route('admin.facturacion.dashboard') }}" class="btn btn-secondary ml-2">
                            <i class="fas fa-redo"></i> Resetear
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas principales -->
    <div class="row">
        <!-- Total Comprobantes -->
        <div class="col-lg-3 col-6">
            <div class="small-box">
                <div class="inner">
                    <h3>{{ number_format($stats['total']) }}</h3>
                    <p>Total Comprobantes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
            </div>
        </div>

        <!-- Comprobantes Aceptados -->
        <div class="col-lg-3 col-6">
            <div class="small-box">
                <div class="inner">
                    <h3>{{ number_format($stats['aceptados']) }}</h3>
                    <p>Aceptados por SUNAT</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <!-- Tasa de Éxito -->
        <div class="col-lg-3 col-6">
            <div class="small-box bg-{{ $stats['tasa_exito'] >= 90 ? 'success' : ($stats['tasa_exito'] >= 70 ? 'warning' : 'danger') }}">
                <div class="inner">
                    <h3>{{ $stats['tasa_exito'] }}%</h3>
                    <p>Tasa de Éxito</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>

        <!-- Comprobantes con Error -->
        <div class="col-lg-3 col-6">
            <div class="small-box">
                <div class="inner">
                    <h3>{{ number_format($stats['con_error']) }}</h3>
                    <p>Con Errores</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Segunda fila de estadísticas -->
    <div class="row">
        <!-- Monto Total -->
        <div class="col-lg-4 col-6">
            <div class="info-box">
                <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Monto Total Facturado</span>
                    <span class="info-box-number">S/ {{ number_format($stats['monto_total'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Comprobantes Hoy -->
        <div class="col-lg-4 col-6">
            <div class="info-box">
                <span class="info-box-icon"><i class="fas fa-calendar-day"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Comprobantes Hoy</span>
                    <span class="info-box-number">{{ number_format($stats['comprobantes_hoy']) }} <small>({{ $stats['aceptados_hoy'] }} aceptados)</small></span>
                </div>
            </div>
        </div>

        <!-- Pendientes -->
        <div class="col-lg-4 col-6">
            <div class="info-box">
                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pendientes de Envío</span>
                    <span class="info-box-number">{{ number_format($stats['pendientes']) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row">
        <!-- Gráfico de comprobantes por estado -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-pie mr-2"></i>
                        Comprobantes por Estado
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="estadoChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico de comprobantes por tipo -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar mr-2"></i>
                        Comprobantes por Tipo
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="tipoChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de emisión diaria -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-area mr-2"></i>
                        Emisión Diaria de Comprobantes
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="emisionDiariaChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Reintentos pendientes -->
    @if(count($reintentosPendientes) > 0)
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-redo mr-2"></i>
                        Reintentos Programados ({{ count($reintentosPendientes) }})
                    </h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Comprobante</th>
                                <th>Cliente</th>
                                <th>Intentos</th>
                                <th>Próximo Intento</th>
                                <th>Último Error</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reintentosPendientes as $reintento)
                            <tr>
                                <td><strong>{{ $reintento['comprobante_numero'] }}</strong></td>
                                <td>{{ $reintento['cliente'] }}</td>
                                <td>
                                    <span class="badge badge-info">
                                        {{ $reintento['intentos'] }} / {{ $reintento['max_intentos'] }}
                                    </span>
                                </td>
                                <td>{{ $reintento['proximo_intento']->format('d/m/Y H:i:s') }}</td>
                                <td>
                                    <small class="text-danger">
                                        {{ Str::limit($reintento['ultimo_error'], 50) }}
                                    </small>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $reintento['estado'] === 'procesando' ? 'primary' : 'warning' }}">
                                        {{ ucfirst($reintento['estado']) }}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-xs btn-danger" onclick="cancelarReintento({{ $reintento['id'] }})">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Últimos comprobantes con error -->
    @if(count($comprobantesConError) > 0)
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        Últimos Comprobantes con Error
                    </h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Comprobante</th>
                                <th>Tipo</th>
                                <th>Cliente</th>
                                <th>Importe</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Error</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($comprobantesConError as $comprobante)
                            <tr>
                                <td><strong>{{ $comprobante['numero_completo'] }}</strong></td>
                                <td>{{ $comprobante['tipo'] }}</td>
                                <td>{{ $comprobante['cliente'] }}</td>
                                <td>S/ {{ number_format($comprobante['importe'], 2) }}</td>
                                <td>{{ $comprobante['created_at']->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="badge badge-danger">{{ $comprobante['estado'] }}</span>
                                </td>
                                <td>
                                    <small class="text-danger">
                                        <strong>{{ $comprobante['codigo_error'] }}:</strong>
                                        {{ Str::limit($comprobante['mensaje_error'], 50) }}
                                    </small>
                                </td>
                                <td>
                                    @if(!$comprobante['tiene_reintento'])
                                    <button class="btn btn-xs btn-primary" onclick="forzarReintento({{ $comprobante['id'] }})">
                                        <i class="fas fa-redo"></i> Reintentar
                                    </button>
                                    @else
                                    <span class="badge badge-info">Reintento programado</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('styles')
<style>
    .small-box {
        border-radius: 0.5rem;
        box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
    }
    .info-box {
        box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        border-radius: 0.5rem;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de comprobantes por estado
    const estadoCtx = document.getElementById('estadoChart').getContext('2d');
    const estadoData = @json($comprobantesPorEstado);

    new Chart(estadoCtx, {
        type: 'pie',
        data: {
            labels: estadoData.map(item => item.estado),
            datasets: [{
                data: estadoData.map(item => item.total),
                backgroundColor: [
                    '#28a745', // ACEPTADO - verde
                    '#dc3545', // RECHAZADO - rojo
                    '#ffc107', // PENDIENTE - amarillo
                    '#6c757d', // OTROS - gris
                ],
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Gráfico de comprobantes por tipo
    const tipoCtx = document.getElementById('tipoChart').getContext('2d');
    const tipoData = @json($comprobantesPorTipo);

    new Chart(tipoCtx, {
        type: 'bar',
        data: {
            labels: tipoData.map(item => item.nombre_tipo),
            datasets: [{
                label: 'Cantidad',
                data: tipoData.map(item => item.total),
                backgroundColor: '#007bff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Gráfico de emisión diaria
    const emisionCtx = document.getElementById('emisionDiariaChart').getContext('2d');
    const emisionData = @json($emisionDiaria);

    new Chart(emisionCtx, {
        type: 'line',
        data: {
            labels: emisionData.map(item => item.fecha),
            datasets: [
                {
                    label: 'Total',
                    data: emisionData.map(item => item.total),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    fill: true,
                },
                {
                    label: 'Aceptados',
                    data: emisionData.map(item => item.aceptados),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true,
                },
                {
                    label: 'Rechazados',
                    data: emisionData.map(item => item.rechazados),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});

// Forzar reintento
function forzarReintento(comprobanteId) {
    if (!confirm('¿Está seguro de que desea reintentar el envío de este comprobante?')) {
        return;
    }

    fetch(`/admin/facturacion/reintentar/${comprobanteId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error al procesar la solicitud: ' + error);
    });
}

// Cancelar reintento
function cancelarReintento(reintentoId) {
    if (!confirm('¿Está seguro de que desea cancelar este reintento?')) {
        return;
    }

    fetch(`/admin/facturacion/cancelar-reintento/${reintentoId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error al procesar la solicitud: ' + error);
    });
}
</script>
@endpush
