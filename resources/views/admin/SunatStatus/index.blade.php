@extends('adminlte::page')

@section('title', 'Estado de SUNAT')

@section('content_header')
    <h1>
        <i class="fas fa-heartbeat"></i> Monitoreo de Estado SUNAT
        <button class="btn btn-sm btn-primary ml-2" onclick="refrescarEstado()">
            <i class="fas fa-sync"></i> Refrescar
        </button>
    </h1>
@stop

@section('content')
{{-- Estado General de SUNAT --}}
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box @if($estadoSunat['disponible']) bg-success @else bg-danger @endif">
            <div class="inner">
                <h3>
                    @if($estadoSunat['estado_general'] == 'operativo')
                        <i class="fas fa-check-circle"></i>
                    @elseif($estadoSunat['estado_general'] == 'parcial')
                        <i class="fas fa-exclamation-triangle"></i>
                    @else
                        <i class="fas fa-times-circle"></i>
                    @endif
                </h3>
                <p>Estado: {{ ucfirst($estadoSunat['estado_general']) }}</p>
            </div>
            <div class="icon">
                <i class="fas fa-server"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $estadisticas['disponibilidad_24h'] }}%</h3>
                <p>Disponibilidad 24h</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $estadisticas['latencia_promedio'] ?? 'N/A' }} <small>ms</small></h3>
                <p>Latencia Promedio</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $estadisticas['incidentes_24h'] }}</h3>
                <p>Incidentes 24h</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
        </div>
    </div>
</div>

{{-- Servicios de SUNAT --}}
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-server"></i> Estado de Servicios</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th>Estado</th>
                            <th>Latencia</th>
                            <th>Código HTTP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($estadoSunat['servicios'] as $nombre => $servicio)
                        <tr>
                            <td>{{ ucfirst($nombre) }}</td>
                            <td>
                                @if($servicio['disponible'])
                                    <span class="badge badge-success">Operativo</span>
                                @else
                                    <span class="badge badge-danger">No disponible</span>
                                @endif
                            </td>
                            <td>{{ $servicio['latencia_ms'] ? $servicio['latencia_ms'] . ' ms' : 'N/A' }}</td>
                            <td>
                                <span class="badge badge-secondary">{{ $servicio['codigo_http'] ?? 'N/A' }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-invoice"></i> Comprobantes (24h)</h3>
            </div>
            <div class="card-body">
                <canvas id="comprobantesChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Reintentos Pendientes --}}
@if($reintentosPendientes->count() > 0)
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-redo"></i> Reintentos Pendientes
                    <span class="badge badge-warning">{{ $reintentosPendientes->count() }}</span>
                </h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Comprobante</th>
                            <th>Cliente</th>
                            <th>Intentos</th>
                            <th>Próximo Intento</th>
                            <th>Último Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reintentosPendientes as $reintento)
                        <tr>
                            <td>
                                <a href="{{ route('admin.comprobantes.show', $reintento->comprobante) }}">
                                    {{ $reintento->comprobante->numero_completo }}
                                </a>
                            </td>
                            <td>{{ $reintento->comprobante->cliente->persona->nombre_completo }}</td>
                            <td>{{ $reintento->intentos }} / {{ $reintento->max_intentos }}</td>
                            <td>
                                <small>{{ $reintento->proximo_intento->diffForHumans() }}</small>
                            </td>
                            <td>
                                <small class="text-danger">{{ Str::limit($reintento->ultimo_error_mensaje, 50) }}</small>
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

{{-- Comprobantes con Error Recientes --}}
@if($comprobantesError->count() > 0)
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-triangle"></i> Comprobantes con Error (últimas 24h)
                </h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Comprobante</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Error</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comprobantesError as $comprobante)
                        <tr>
                            <td>{{ $comprobante->numero_completo }}</td>
                            <td>{{ $comprobante->cliente->persona->nombre_completo }}</td>
                            <td><small>{{ $comprobante->created_at->format('d/m/Y H:i') }}</small></td>
                            <td>
                                <small class="text-danger">
                                    @if($comprobante->codigo_error)
                                        [{{ $comprobante->codigo_error }}]
                                    @endif
                                    {{ Str::limit($comprobante->mensaje_error, 40) }}
                                </small>
                            </td>
                            <td>
                                <a href="{{ route('admin.comprobantes.show', $comprobante) }}"
                                   class="btn btn-xs btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
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

@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Gráfico de Comprobantes
const ctx = document.getElementById('comprobantesChart').getContext('2d');
const comprobantesChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Exitosos', 'Errores', 'Pendientes'],
        datasets: [{
            data: [
                {{ $estadisticasComprobantes['exitosos_24h'] }},
                {{ $estadisticasComprobantes['errores_24h'] }},
                {{ $estadisticasComprobantes['pendientes_24h'] }}
            ],
            backgroundColor: [
                'rgba(40, 167, 69, 0.8)',
                'rgba(220, 53, 69, 0.8)',
                'rgba(255, 193, 7, 0.8)'
            ],
            borderWidth: 1
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

// Refrescar estado
function refrescarEstado() {
    const btn = event.target.closest('button');
    const icon = btn.querySelector('i');
    icon.classList.add('fa-spin');
    btn.disabled = true;

    fetch('{{ route("admin.sunat-status.refrescar") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error al refrescar estado');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al refrescar estado');
    })
    .finally(() => {
        icon.classList.remove('fa-spin');
        btn.disabled = false;
    });
}

// Auto-refrescar cada 5 minutos
setInterval(() => {
    location.reload();
}, 5 * 60 * 1000);
</script>
@stop

@section('css')
<style>
.small-box {
    border-radius: 0.25rem;
}
</style>
@stop
