@extends('layouts.admin')

@section('title', 'Perfil de Rendimiento - ' . $asesor->name)

@section('content')
<div class="container-fluid">
    <div class="row mb-3 align-items-center">
        <div class="col-sm-6">
            <h5 class="m-0 font-weight-bold text-dark">
                {{ $asesor->persona?->full_name ?? $asesor->name }}
                <span class="badge badge-pill badge-{{ $meta->nivel_calificacion->color() }} ml-2" style="font-size: 0.65rem;">
                    <i class="{{ $meta->nivel_calificacion->icono() }} mr-1"></i> {{ $meta->nivel_calificacion->label() }}
                </span>
            </h5>
            <div class="small text-muted">{{ $asesor->codigo ?? 'N/A' }} &middot; <span class="text-capitalize">{{ \Carbon\Carbon::create($meta->anio, $meta->mes)->translatedFormat('F Y') }}</span></div>
        </div>
        <div class="col-sm-6 text-right">
            <div class="btn-group shadow-sm">
                <a href="{{ route('admin.metas.index', ['anio' => $meta->anio, 'mes' => $meta->mes]) }}" class="btn btn-white border px-4">
                    <i class="fas fa-arrow-left mr-1 text-muted"></i> Volver
                </a>
                <a href="{{ route('admin.metas.edit', $meta->id) }}" class="btn btn-warning px-4">
                    <i class="fas fa-edit mr-1"></i> Editar Meta
                </a>
            </div>
        </div>
    </div>
    @php
        $isMoraAlta = $metricas['porcentaje_mora'] > ($config->umbral_morosidad ?? 20);

        $renovacionesSinMora = $prestamos->filter(function($p) {
            if (!str_contains(strtolower($p->tipo_solicitud ?? ''), 'renovación')) return false;
            $moraPendiente = $p->cuotas->where('estado', '!=', \App\Enums\CuotaEstado::PAGADO)->sum('cantidad_mora');
            return $moraPendiente <= 0;
        });
        $montoRenovacionesSinMora = $renovacionesSinMora->sum('cantidad_solicitada');
        $comisionRenovaciones = $montoRenovacionesSinMora * 0.01;
        $totalRenovaciones = $prestamos->filter(fn($p) => str_contains(strtolower($p->tipo_solicitud ?? ''), 'renovación'));
        $montoTotalRenovaciones = $totalRenovaciones->sum('cantidad_solicitada');
        $renovacionesConMora = $totalRenovaciones->count() - $renovacionesSinMora->count();
        $montoRenovacionesConMora = $montoTotalRenovaciones - $montoRenovacionesSinMora;

        $nuevosSinMora = $prestamos->filter(function($p) {
            if (!str_contains(strtolower($p->tipo_solicitud ?? ''), 'nueva')) return false;
            $moraPendiente = $p->cuotas->where('estado', '!=', \App\Enums\CuotaEstado::PAGADO)->sum('cantidad_mora');
            return $moraPendiente <= 0;
        });
        $totalNuevos = $prestamos->filter(fn($p) => str_contains(strtolower($p->tipo_solicitud ?? ''), 'nueva'));
        $montoTotalNuevos = $totalNuevos->sum('cantidad_solicitada');
        $montoNuevosSinMora = $nuevosSinMora->sum('cantidad_solicitada');
        $nuevosConMora = $totalNuevos->count() - $nuevosSinMora->count();
        $montoNuevosConMora = $montoTotalNuevos - $montoNuevosSinMora;
    @endphp

    <div class="row mb-3">
        <!-- Métricas compactas: Préstamos + Efectividad + Mora -->
        <div class="col-md-4 px-1 mb-2">
            <div class="card border-0 shadow-sm h-100 border-left-lg border-primary">
                <div class="card-body p-2">
                    <!-- Préstamos -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center">
                            <div class="metric-icon-sm bg-primary-light text-primary mr-2"><i class="fas fa-hand-holding-usd"></i></div>
                            <span class="small font-weight-bold text-black uppercase">Préstamos</span>
                        </div>
                        <div class="text-right">
                            <span class="font-weight-bold">{{ $metricas['prestamos_logrados'] }} <small class="text-muted">/ {{ $metricas['objetivo'] }}</small></span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between x-small text-muted mb-1">
                        <span><strong class="text-success">{{ $metricas['nuevos'] }}</strong> Nuevos</span>
                        <span><strong class="text-info">{{ $metricas['renovaciones'] }}</strong> Renovaciones</span>
                    </div>
                    <div class="progress mb-2" style="height: 4px; border-radius: 10px;">
                        <div class="progress-bar bg-primary" style="width: {{ min($metricas['efectividad'], 100) }}%"></div>
                    </div>
                    <hr class="my-1">
                    <!-- Efectividad -->
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center">
                            <div class="metric-icon-sm bg-info-light text-info mr-2" style="width:22px;height:22px;font-size:0.7rem;"><i class="fas fa-bullseye"></i></div>
                            <span class="x-small font-weight-bold text-muted">Efectividad</span>
                        </div>
                        <span class="font-weight-bold text-info">{{ number_format($metricas['efectividad'], 1) }}%</span>
                    </div>
                    <hr class="my-1">
                    <!-- Mora -->
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="metric-icon-sm bg-{{ $isMoraAlta ? 'danger' : 'success' }}-light text-{{ $isMoraAlta ? 'danger' : 'success' }} mr-2" style="width:22px;height:22px;font-size:0.7rem;"><i class="fas fa-exclamation-triangle"></i></div>
                            <span class="x-small font-weight-bold text-muted">Mora <span class="text-muted font-weight-normal">(lím {{ $config->umbral_morosidad ?? 20 }}%)</span></span>
                        </div>
                        <span class="font-weight-bold {{ $isMoraAlta ? 'text-danger' : 'text-success' }}">{{ number_format($metricas['porcentaje_mora'], 2) }}% <small class="font-weight-normal">({{ $metricas['morosos'] }})</small></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comisión Renovaciones -->
        <div class="col-md-4 px-1 mb-2">
            <div class="card border-0 shadow-sm h-100 card-com-renov">
                <div class="card-body p-2 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex align-items-center">
                            <div class="metric-icon-sm bg-white mr-2" style="color:#06b6d4;"><i class="fas fa-sync-alt"></i></div>
                            <span class="small font-weight-bold uppercase" style="color:#fff;">Com. Renovaciones</span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <h4 class="font-weight-bold mb-1 text-white text-right">
                            S/{{ number_format($comisionRenovaciones, 2) }}
                        </h4>
                        <div class="x-small" style="color: rgba(255,255,255,0.85);">
                            <div class="d-flex justify-content-between"><span>Desembolsado:</span> <strong class="text-white">S/{{ number_format($montoTotalRenovaciones, 0) }}</strong></div>
                            <div class="d-flex justify-content-between"><span>Sin mora ({{ $renovacionesSinMora->count() }}):</span> <strong style="color: #a3f7bf;">S/{{ number_format($montoRenovacionesSinMora, 0) }}</strong></div>
                            <div class="d-flex justify-content-between"><span>Con mora ({{ $renovacionesConMora }}):</span> <strong style="color: #ffb3b3;">S/{{ number_format($montoRenovacionesConMora, 0) }}</strong></div>
                            <div class="d-flex justify-content-between border-top mt-1 pt-1" style="border-color: rgba(255,255,255,0.3)!important;"><span>1% de S/{{ number_format($montoRenovacionesSinMora, 0) }}:</span> <strong class="text-white">S/{{ number_format($comisionRenovaciones, 2) }}</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comisión Préstamos Nuevos -->
        <div class="col-md-4 px-1 mb-2">
            <div class="card border-0 shadow-sm h-100 card-com-nuevos">
                <div class="card-body p-2 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex align-items-center">
                            <div class="metric-icon-sm bg-white mr-2" style="color:#7c3aed;"><i class="fas fa-money-bill-wave"></i></div>
                            <span class="small font-weight-bold uppercase" style="color:#fff;">Com. Nuevos</span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <h4 class="font-weight-bold mb-1 text-right {{ $metricas['penalizado'] ? 'text-white-50 text-strikethrough' : 'text-white' }}">
                            S/{{ number_format($metricas['penalizado'] ? 0 : $metricas['comision'], 2) }}
                        </h4>
                        <div class="x-small" style="color: rgba(255,255,255,0.85);">
                            <div class="d-flex justify-content-between"><span>Desembolsado:</span> <strong class="text-white">S/{{ number_format($montoTotalNuevos, 0) }}</strong></div>
                            <div class="d-flex justify-content-between"><span>Sin mora ({{ $nuevosSinMora->count() }}):</span> <strong style="color: #a3f7bf;">S/{{ number_format($montoNuevosSinMora, 0) }}</strong></div>
                            <div class="d-flex justify-content-between"><span>Con mora ({{ $nuevosConMora }}):</span> <strong style="color: #ffb3b3;">S/{{ number_format($montoNuevosConMora, 0) }}</strong></div>
                        </div>
                        @if($metricas['penalizado'])
                            <div class="x-small text-white font-weight-bold mt-1"><i class="fas fa-ban mr-1"></i> Penalizado</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección: Historial Colapsable -->
    <div class="mb-3">
        <a class="d-flex align-items-center justify-content-between text-decoration-none historial-toggle collapsed" data-toggle="collapse" href="#historialCollapse" role="button" aria-expanded="false" aria-controls="historialCollapse">
            <h6 class="font-weight-bold text-dark mb-0">
                <i class="fas fa-history text-muted mr-1"></i> Historial
                <span class="text-muted font-weight-normal" style="font-size: 0.7rem;">(Últimos 5 meses)</span>
            </h6>
            <i class="fas fa-chevron-down text-muted historial-chevron" style="font-size: 0.7rem; transition: transform 0.3s;"></i>
        </a>
        <div class="collapse mt-2" id="historialCollapse">
            <div class="row no-gutters mx-n1">
                @forelse($historial->take(5) as $h)
                    <div class="col p-1">
                        <div class="historial-item text-center p-2 rounded">
                            <div class="x-small text-muted font-weight-bold text-uppercase mb-1">{{ \Carbon\Carbon::create($h->anio, $h->mes)->translatedFormat('M Y') }}</div>
                            <span class="badge badge-{{ $h->nivel_calificacion->color() }} mb-1" style="font-size: 0.6rem; padding: 0.2em 0.5em;">
                                {{ $h->nivel_calificacion->label() }}
                            </span>
                            <div class="d-flex justify-content-center x-small text-muted" style="gap: 6px;">
                                <span><i class="fas fa-bullseye mr-1" style="font-size:0.5rem;"></i><strong>{{ number_format($h->porcentaje_cumplimiento, 0) }}%</strong></span>
                                <span><i class="fas fa-exclamation-triangle mr-1 {{ $h->porcentaje_morosidad > ($config->umbral_morosidad ?? 20) ? 'text-danger' : 'text-success' }}" style="font-size:0.5rem;"></i><strong class="{{ $h->porcentaje_morosidad > ($config->umbral_morosidad ?? 20) ? 'text-danger' : 'text-success' }}">{{ number_format($h->porcentaje_morosidad, 1) }}%</strong></span>
                            </div>
                            <div class="font-weight-bold text-primary mt-1" style="font-size: 0.8rem;">
                                S/{{ number_format($h->comision_final, 0) }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-2 text-muted x-small">No hay registros históricos.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Sección de Cartera -->
    <div class="col-lg-12 px-0">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white py-2 d-flex flex-wrap justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold mb-0 small">
                    <i class="fas fa-list-ul text-primary mr-1"></i> Cartera Desembolsada
                </h3>
                <div class="d-flex mt-1 mt-md-0">
                    <a href="{{ route('admin.metas.cartera.exportar', ['asesor' => $asesor->id, 'mes' => $mes, 'anio' => $anio, 'tipo' => $tipo, 'mora' => $mora]) }}" class="btn btn-xs btn-outline-success px-2 font-weight-bold shadow-xs">
                        <i class="fas fa-file-excel mr-1"></i> EXCEL
                    </a>
                </div>
            </div>
            <!-- Barra de Filtros Compacta -->
            <div class="bg-light px-3 py-2 border-bottom">
                <form action="{{ route('admin.metas.show', $meta->id) }}" method="GET" class="row no-gutters align-items-center">
                    <div class="col-md-2 col-6 pr-1">
                        <select name="anio" class="form-control form-control-xs custom-select-compact">
                            @for($y = date('Y'); $y >= 2024; $y--)
                                <option value="{{ $y }}" {{ $anio == $y ? 'selected' : '' }}>Año {{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2 col-6 px-1">
                        <select name="mes" class="form-control form-control-xs custom-select-compact">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ $mes == $m ? 'selected' : '' }}>
                                    {{ Str::title(\Carbon\Carbon::create(null, $m)->translatedFormat('F')) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6 px-1">
                        <select name="tipo" class="form-control form-control-xs custom-select-compact font-weight-bold">
                            <option value="">TODOS LOS TIPOS</option>
                            <option value="nuevo" {{ $tipo == 'nuevo' ? 'selected' : '' }}>SOLO NUEVOS</option>
                            <option value="renovacion" {{ $tipo == 'renovacion' ? 'selected' : '' }}>SOLO RENOVACIONES</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-6 px-1">
                        <select name="mora" class="form-control form-control-xs custom-select-compact font-weight-bold">
                            <option value="">AMBOS ESTADOS</option>
                            <option value="con_mora" {{ $mora == 'con_mora' ? 'selected' : '' }}>CON MORA</option>
                            <option value="sin_mora" {{ $mora == 'sin_mora' ? 'selected' : '' }}>SIN MORA</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-12 pl-1 d-flex">
                        <button type="submit" class="btn btn-primary btn-xs btn-block shadow-xs">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 table-portfolio-compact">
                        <thead>
                            <tr class="bg-gray-light text-muted text-uppercase x-small font-weight-bold">
                                <th class="pl-3 py-2">PRÉSTAMO</th>
                                <th>CLIENTE</th>
                                <th class="text-center">TIPO</th>
                                <th>DESEMBOLSO</th>
                                <th>1ra CUOTA</th>
                                <th class="text-center">CUOTAS</th>
                                <th>F. VENCIDA</th>
                                <th class="text-center">D. ATRASO</th>
                                <th class="pr-3 text-right">MONTO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($prestamos as $prestamo)
                            @php
                                $cuotasTotal = $prestamo->cuotas->count();
                                $cuotasPagadas = $prestamo->cuotas->where('estado', \App\Enums\CuotaEstado::PAGADO)->count();
                                $fechaDesembolso = $prestamo->operaciones->where('tipo_operacion', 'Desembolso')->first()?->fecha;
                                $primeraCuota = $prestamo->cuotas->sortBy('fecha_pago')->first()?->fecha_pago;
                                // Primera cuota no pagada (vencida)
                                $primeraCuotaNoPagada = $prestamo->cuotas->where('estado', '!=', \App\Enums\CuotaEstado::PAGADO)->sortBy('fecha_pago')->first();
                                $fechaVencida = $primeraCuotaNoPagada?->fecha_pago;
                                // Días de atraso: días desde la primera cuota no pagada hasta hoy
                                $diasAtraso = 0;
                                if ($fechaVencida) {
                                    $hoy = \Carbon\Carbon::now()->startOfDay();
                                    $fechaCuota = \Carbon\Carbon::parse($fechaVencida)->startOfDay();
                                    if ($fechaCuota->lte($hoy)) {
                                        $diasAtraso = $fechaCuota->diffInDays($hoy);
                                    }
                                }
                            @endphp
                            <tr>
                                <td class="pl-3 small">
                                    <a href="{{ route('admin.prestamos.show', $prestamo->id) }}" class="font-weight-bold text-primary">
                                        {{ $prestamo->getNumeroPrestamoAttribute() }}
                                    </a>
                                </td>
                                <td class="small">
                                    <div class="font-weight-bold text-dark">{{ $prestamo->cliente->persona->full_name }}</div>
                                    <div class="x-small text-muted">{{ $prestamo->cliente->persona->documento }}</div>
                                </td>
                                <td class="text-center">
                                    @if(str_contains(strtolower($prestamo->tipo_solicitud), 'nueva'))
                                        <span class="badgex x-small text-success font-weight-bold">N</span>
                                    @else
                                        <span class="badgex x-small text-info font-weight-bold">R</span>
                                    @endif
                                </td>
                                <td class="x-small">{{ $fechaDesembolso ? \Carbon\Carbon::parse($fechaDesembolso)->format('d/m/y') : '-' }}</td>
                                <td class="x-small">{{ $primeraCuota ? \Carbon\Carbon::parse($primeraCuota)->format('d/m/y') : '-' }}</td>
                                <td class="x-small text-center font-weight-bold">
                                    <span class="text-{{ $cuotasPagadas == $cuotasTotal ? 'success' : 'dark' }}">{{ $cuotasPagadas }}/{{ $cuotasTotal }}</span>
                                </td>
                                <td class="x-small">
                                    @if($fechaVencida)
                                        <span class="text-danger">{{ \Carbon\Carbon::parse($fechaVencida)->format('d/m/y') }}</span>
                                    @else
                                        <span class="text-success"><i class="fas fa-check"></i></span>
                                    @endif
                                </td>
                                <td class="x-small text-center">
                                    @if($diasAtraso > 0)
                                        <span class="text-danger font-weight-bold">{{ $diasAtraso }}d</span>
                                    @else
                                        <span class="text-success">0</span>
                                    @endif
                                </td>
                                <td class="pr-3 text-right font-weight-bold small">
                                    S/{{ number_format($prestamo->cantidad_solicitada, 0) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <p class="text-muted small mb-0">No se encontraron préstamos.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    :root {
        --indigo: #6610f2;
        --indigo-light: #eef2ff;
    }
    .bg-primary-gradient { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); }
    .profile-header { border-radius: 12px; }
    .card { border-radius: 10px; }
    .shadow-sm { box-shadow: 0 .125rem .25rem rgba(0,0,0,.05)!important; }
    .metric-card { transition: transform 0.2s; border-radius: 10px; }
    .metric-card:hover { transform: scale(1.02); }
    .border-left-lg { border-left-width: 4px !important; }
    .metric-icon-sm { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; }
    
    .bg-primary-light { background-color: #e7f1ff; }
    .bg-info-light { background-color: #e7f8f9; }
    .bg-success-light { background-color: #e6f4ea; }
    .bg-danger-light { background-color: #faeaea; }
    .bg-indigo-light { background-color: #f0e7ff; }
    .bg-gray-light { background-color: #f8f9fa; }
    
    .text-indigo { color: var(--indigo); }
    .x-small { font-size: 0.7rem; }
    .uppercase { text-transform: uppercase; letter-spacing: 0.5px; }
    
    .table-portfolio-compact td { vertical-align: middle; padding: 0.4rem 0.75rem !important; }
    .table-portfolio-compact tr:hover { background-color: #f1f8ff !important; }
    
    .custom-select-compact { border-radius: 6px; border: 1px solid #ced4da; height: 26px; padding: 2px 5px; font-size: 0.75rem; }
    .btn-xs { padding: 2px 8px; font-size: 0.75rem; border-radius: 4px; }
    .btn-xs i { font-size: 0.7rem; }
    
    .border-4 { border-width: 3px !important; }

    .card-com-renov { background: linear-gradient(135deg, #1e293b, #334155) !important; border-radius: 10px; border-left: 4px solid #06b6d4 !important; }
    .card-com-renov:hover { transform: scale(1.02); transition: transform 0.2s; }
    .card-com-nuevos { background: linear-gradient(135deg, #1e293b, #334155) !important; border-radius: 10px; border-left: 4px solid #7c3aed !important; }
    .card-com-nuevos:hover { transform: scale(1.02); transition: transform 0.2s; }

    .historial-toggle { cursor: pointer; padding: 6px 10px; border-radius: 8px; background: #f8f9fa; border: 1px solid #e9ecef; }
    .historial-toggle:hover { background: #e9ecef; }
    .historial-toggle:not(.collapsed) .historial-chevron { transform: rotate(180deg); }
    .historial-item { background: #f8f9fa; border: 1px solid #e9ecef; transition: all 0.2s; }
    .historial-item:hover { background: #e9ecef; border-color: #dee2e6; }

    @media (max-width: 991.98px) {
        .row.mb-3 > [class*="col-md-4"] { flex: 0 0 100%; max-width: 100%; }
    }
</style>
@stop
