@php
use App\Enums\CuotaEstado;
@endphp<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cronograma de Pagos</title>
<style>
@page { 
    size: A4 portrait; 
    margin: 10mm; 
}
body { 
    font-family: DejaVu Sans, sans-serif; 
    font-size: 8.5pt; 
    margin: 0; 
    padding: 0; 
    width: 100%;
    line-height: 1.2;
    color: #000;
}
.header { 
    width: 100%; 
    margin-bottom: 20pt; 
}
.header-table {
    width: 100%;
    border-collapse: collapse;
}
.header-table td {
    vertical-align: middle;
}
.header h1 {
    font-size: 16pt;
    font-weight: bold;
    margin: 5pt 0 0 0;
    color: #000;
}
.section {
    margin-bottom: 12pt;
    border: 0.5pt solid #ddd;
    border-radius: 2pt;
    overflow: hidden;
}
.section-title {
    background-color: #f5f5f5;
    padding: 4pt 8pt;
    font-weight: bold;
    font-size: 8.5pt;
    text-transform: uppercase;
    border-bottom: 0.5pt solid #ddd;
    color: #000;
}
.section-content {
    padding: 6pt;
}
.info-table {
    width: 100%;
    border-collapse: collapse;
}
.info-table td {
    padding: 2pt 0;
    vertical-align: top;
}
.label-cell {
    width: 90pt;
    color: #000;
    font-weight: normal;
}
.value-cell {
    font-weight: bold;
}
.team-table {
    width: 100%;
    border-collapse: collapse;
}
.team-table td {
    width: 33.33%;
    padding: 4pt;
    border: 0.2pt solid #ddd;
}
.team-label {
    font-size: 7pt;
    color: #686868ff;
    margin-bottom: 2pt;
    display: block;
}
.team-value {
    font-weight: bold;
    font-size: 9pt;
}
.main-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 5pt;
}
.main-table th {
    background-color: #000;
    color: white;
    padding: 5pt 3pt;
    font-size: 7.5pt;
    text-transform: uppercase;
    text-align: center;
}
.main-table td {
    padding: 4pt 3pt;
    border-bottom: 0.2pt solid #ddd;
    font-size: 7.5pt;
    text-align: center;
}
.main-table .total-row td {
    background-color: #f9f9f9;
    font-weight: bold;
    font-size: 8pt;
    border-top: 1pt solid #000;
}
.disclaimer {
    font-size: 7pt;
    text-align: justify;
    line-height: 1.3;
    color: #000;
    margin-top: 10pt;
}
.highlight-row { background-color: #fcfcfc; }
.page-break { page-break-after: always; }
</style>
</head>
<body>
<div class="container">
@if(isset($datosConsolidados) && $datosConsolidados && $datosConsolidados->count() > 0)
@foreach($datosConsolidados as $index => $clienteData)

<!-- Header -->
<div class="header">
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <div style="font-size: 8pt; color: #666; margin-bottom: 4pt;">
                    FECHA IMPRESIÓN: <strong>{{ date('d/m/Y') }}</strong> | 
                    USUARIO: <strong>{{ auth()->user()->codigo ?? 'SISTEMA' }}</strong>
                </div>
                <h1>NOTIFICACIÓN DE MOROSIDAD</h1>
                @php
                    $esConvenio = false;
                    if(isset($clienteData['cuotas']) && $clienteData['cuotas']->isNotEmpty()) {
                        $primeraCuota = $clienteData['cuotas']->first();
                        // Verificar si es cuota de convenio directamente
                        if(isset($primeraCuota->es_cuota_convenio) && $primeraCuota->es_cuota_convenio) {
                            $esConvenio = true;
                        } else {
                            // Verificar si el préstamo tiene convenio activo
                            $prestamo = $primeraCuota->prestamo;
                            if($prestamo && $prestamo->convenios->where('estado', \App\Enums\ConvenioEstado::ACTIVO->value)->isNotEmpty()) {
                                $esConvenio = true;
                            }
                        }
                    }
                @endphp
                @if($esConvenio)
                    <span style="background-color: #f59e0b; color: #fff; padding: 2pt 8pt; font-size: 8pt; border-radius: 3pt; font-weight: bold;">CONVENIO</span>
                @endif
            </td>
            <td style="width: 30%; text-align: right;">
                <div style="color: #000000ff; font-size: 8.5pt; font-weight: bold; line-height: 1.1;">
                    GRUPO SANTIAGO PERÚ S.A.C.<br>
                    <span style="font-size: 7.5pt; color: #333;">RUC: 20613371891</span><br>
                    <span style="font-size: 7pt; font-weight: normal; color: #666;">CASA DE PRÉSTAMO Y EMPEÑO</span>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- Datos del cliente -->
<div class="section">
    <div class="section-title">Información del Cliente y Crédito
        @php
            $tramoCalculado = 0;
            if(isset($clienteData['cuotas']) && $clienteData['cuotas']->isNotEmpty()) {
                $cuotasOrdenadas = $clienteData['cuotas']->sortBy('fecha_pago');
                $primeraCuotaNoPagada = $cuotasOrdenadas->first(function ($c) {
                    $estadoValor = $c->estado instanceof \BackedEnum ? $c->estado->value : (int) $c->estado;
                    return in_array($estadoValor, [0, 1, 3]);
                });
                if ($primeraCuotaNoPagada) {
                    $diasAtrasoTramo = \Carbon\Carbon::parse($primeraCuotaNoPagada->fecha_pago)->startOfDay()->diffInDays(now()->startOfDay(), false);
                    if ($diasAtrasoTramo <= 6) $tramoCalculado = 0;
                    elseif ($diasAtrasoTramo <= 14) $tramoCalculado = 1;
                    elseif ($diasAtrasoTramo <= 21) $tramoCalculado = 2;
                    elseif ($diasAtrasoTramo <= 30) $tramoCalculado = 3;
                    else $tramoCalculado = 4;
                }
            }
        @endphp
        <span style="float: right; font-size: 8pt;">TRAMO: {{ $tramoCalculado }}</span>
    </div>
    
    <div class="section-content">
        <table class="info-table">
            <tr>
                <td style="width: 50%;">
                    <table class="info-table">
                        <tr>
                            <td class="label-cell">Crédito Nro</td>
                            <td style="width: 5pt;">:</td>
                            <td class="value-cell">{{ $clienteData['cuotas']->first()->prestamo_id ?? $clienteData['cuotas']->first()->prestamo->id ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="label-cell">Nombre del cliente</td>
                            <td>:</td>
                            <td class="value-cell">{{ $clienteData['cliente']->persona->nombres ?? 'N/A' }} {{ $clienteData['cliente']->persona->ape_pat ?? '' }} {{ $clienteData['cliente']->persona->ape_mat ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="label-cell">Aval Solidario</td>
                            <td>:</td>
                            <td class="value-cell">
                                @php
                                    $prestamoAval = isset($clienteData['cuotas']) && $clienteData['cuotas']->isNotEmpty() ? $clienteData['cuotas']->first()->prestamo : null;
                                    $aval = $prestamoAval ? $prestamoAval->aval : null;
                                @endphp
                                @if($aval && $aval->persona)
                                    {{ strtoupper(trim(($aval->persona->nombres ?? '') . ' ' . ($aval->persona->ape_pat ?? '') . ' ' . ($aval->persona->ape_mat ?? ''))) }}
                                @else
                                    Sin Aval
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="label-cell">Producto</td>
                            <td>:</td>
                            <td class="value-cell">Crédito Personal</td>
                        </tr>
                        <tr>
                            <td class="label-cell">Periodicidad</td>
                            <td>:</td>
                            <td class="value-cell">Semanal</td>
                        </tr>
                        <tr>
                            <td class="label-cell">F. Desembolso</td>
                            <td>:</td>
                            <td class="value-cell">
                                @php
                                    $fechaDesembolso = null;
                                    if(isset($clienteData['cuotas']) && $clienteData['cuotas']->isNotEmpty()) {
                                        $prestamo = $clienteData['cuotas']->first()->prestamo;
                                        // Buscar la operación de desembolso
                                        $operacionDesembolso = $prestamo->operaciones()
                                            ->where('tipo_operacion', 'Desembolso')
                                            ->where('estado', '!=', 'anulado')
                                            ->orderBy('created_at', 'asc')
                                            ->first();
                                        if ($operacionDesembolso) {
                                            $fechaDesembolso = $operacionDesembolso->created_at;
                                        }
                                    }
                                @endphp
                                {{ $fechaDesembolso ? \Carbon\Carbon::parse($fechaDesembolso)->format('d/m/Y') : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="label-cell">Sucursal</td>
                            <td>:</td>
                            <td class="value-cell">{{ $clienteData['sucursal'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="label-cell">Día de Pago</td>
                            <td>:</td>
                            <td class="value-cell">
                                @php
                                    $diaSemana = 'N/A';
                                    try {
                                        $primeraCuotaDP = isset($clienteData['cuotas']) && $clienteData['cuotas']->isNotEmpty() ? $clienteData['cuotas']->first() : null;
                                        if ($primeraCuotaDP && $primeraCuotaDP->fecha_pago) {
                                            $diaSemana = mb_strtoupper(\Carbon\Carbon::parse($primeraCuotaDP->fecha_pago)->locale('es')->isoFormat('dddd'), 'UTF-8');
                                        }
                                    } catch (\Exception $e) { $diaSemana = 'N/A'; }
                                @endphp
                                {{ $diaSemana }}
                            </td>
                        </tr>
                        <tr>
                            <td class="label-cell">Avaló</td>
                            <td>:</td>
                            <td class="value-cell">
                                @php
                                    $vecesAvalo = isset($clienteData['cliente']->persona)
                                        ? \App\Models\Aval::where('persona_id', $clienteData['cliente']->persona->id)->count()
                                        : 0;
                                @endphp
                                {{ $vecesAvalo }} {{ $vecesAvalo === 1 ? 'vez' : 'veces' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="label-cell">Estado</td>
                            <td>:</td>
                            @php
                                $estado = $clienteData['estado'] ?? 'N/A';
                                $estadoAbrev = match($estado) {
                                    'ACTIVO' => 'VI. ACTIVO',
                                    'INACTIVO' => 'VI. INACTIVO',
                                    'EN MORA/ACTIVA' => 'M. ACTIVO',
                                    'EN MORA/INACTIVA' => 'M. INACTIVO',
                                    'CREDITO VENCIDO/ACTIVO' => 'VE. ACTIVO',
                                    'CREDITO VENCIDO/INACTIVO' => 'VE. INACTIVO',
                                    default => $estado,
                                };
                                $estadoColor = match($estado) {
                                    'ACTIVO' => '#28a745',
                                    'INACTIVO' => '#6c757d',
                                    'EN MORA/ACTIVA' => '#e67e22',
                                    'EN MORA/INACTIVA' => '#e67e22',
                                    'CREDITO VENCIDO/ACTIVO' => '#dc3545',
                                    'CREDITO VENCIDO/INACTIVO' => '#dc3545',
                                    default => '#000',
                                };
                            @endphp
                            <td class="value-cell" style="font-size: 10pt; color: {{ $estadoColor }}; font-weight: bold;">{{ $estadoAbrev }}</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 50%; padding-left: 10pt;">
                    <table class="info-table">
                        <tr>
                            <td class="label-cell">Dirección</td>
                            <td style="width: 5pt;">:</td>
                            <td class="value-cell">{{ $clienteData['direccion'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="label-cell">Referencia</td>
                            <td>:</td>
                            <td class="value-cell">{{ $clienteData['referencia'] ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="label-cell">Cuotas por Pagar</td>
                            <td>:</td>
                            <td class="value-cell">
                                @if(isset($clienteData['cuotas']) && $clienteData['cuotas']->isNotEmpty())
                                    @php
                                        $prestamo = $clienteData['cuotas']->first()->prestamo;
                                        $cuotasPagadas = $prestamo->cuotas()->where('estado', 2)->count();
                                        $totalCuotas = $prestamo->cuotas()->count();
                                    @endphp
                                    {{ $cuotasPagadas }} de {{ $totalCuotas }}
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="label-cell">Interés / Comisión</td>
                            <td>:</td>
                            <td class="value-cell">
                                @php
                                    $prestamo = isset($clienteData['cuotas']) && $clienteData['cuotas']->isNotEmpty() ? $clienteData['cuotas']->first()->prestamo : null;
                                    $reglasInteres = [
                                        8 => ['tasa' => 1.38, 'com' => 3.29],
                                        12 => ['tasa' => 1.38, 'com' => 4.73],
                                        15 => ['tasa' => 1.38, 'com' => 4.18],
                                        18 => ['tasa' => 1.38, 'com' => 3.36],
                                        20 => ['tasa' => 1.38, 'com' => 2.84]
                                    ];
                                    $tasa = $prestamo && isset($reglasInteres[$prestamo->plazo]) ? $reglasInteres[$prestamo->plazo]['tasa'] : 1.38;
                                    $com = $prestamo && isset($reglasInteres[$prestamo->plazo]) ? $reglasInteres[$prestamo->plazo]['com'] : 3.29;
                                @endphp
                                {{ number_format($tasa, 2) }}% / {{ number_format($com, 2) }}%
                            </td>
                        </tr>
                        <tr>
                            <td class="label-cell">Monto Total a Pagar</td>
                            <td>:</td>
                            <td class="value-cell" style="font-size: 10pt; color: #000;">S/ {{ number_format($clienteData['deuda_total'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="label-cell">Fecha Inicio</td>
                            <td>:</td>
                            <td class="value-cell" style="font-size: 10pt; color: #000;">{{ $clienteData['fecha_inicio'] ?? '---' }}</td>
                        </tr>
                        <tr>
                            <td class="label-cell">Fecha Fin</td>
                            <td>:</td>
                            <td class="value-cell" style="font-size: 10pt; color: #000;">{{ $clienteData['fecha_fin'] ?? '---' }}</td>
                        </tr>
                        <tr>
                            <td class="label-cell">M.P.A</td>
                            <td>:</td>
                            <td class="value-cell" style="font-size: 10pt; color: #000;">S/ {{ number_format($clienteData['mora_acumulada_anterior'] ?? 0, 2) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- Datos del equipo -->
<div class="section">
    <div class="section-title">Personal Responsable</div>
    <div class="section-content" style="padding: 0;">
        <table class="team-table">
            <tr>
                <td>
                    <span class="team-label">JEC DE CAMPO (JCC)</span>
                    <span class="team-value">{{ $clienteData['jcc']->codigo ?? '---' }}</span>
                </td>
                <td>
                    <span class="team-label">ANALISTA DE CRÉDITO</span>
                    <span class="team-value">{{ $clienteData['analista']->codigo ?? '---' }}</span>
                </td>
                <td>
                    <span class="team-label">ASESOR DE COBRANZA</span>
                    <span class="team-value">{{ $clienteData['asesor']->codigo ?? '---' }}</span>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="section">
    <div class="section-title">Detalle de Cuotas Pendientes</div>
    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 15%;">Vencimiento</th>
                <th style="width: 14%;">Cuota</th>
                <th style="width: 14%;">Interés</th>
                <th style="width: 14%;">Amortiza.</th>
                <th style="width: 12%;">Mora</th>
                <th style="width: 12%;">Total</th>
                <th style="width: 14%;">Saldo Cap.</th>
            </tr>
        </thead>
        <tbody>
            @if($clienteData['cuotas'] && $clienteData['cuotas']->count() > 0)
                @php
                    $totalAmortizacion = 0;
                    $totalIntereses = 0;
                    $totalCuota = 0;
                    $totalMora = 0;
                    $totalGeneral = 0;

                    $prestamo = $clienteData['cuotas']->first()->prestamo;
                    $sumaTotal = \DB::table('cuotas')->where('prestamo_id', $prestamo->id)->sum('monto');
                    $sumaPagadas = \DB::table('cuotas')->where('prestamo_id', $prestamo->id)->where('estado', 2)->sum('monto');
                    $saldoCapitalBase = $sumaTotal - $sumaPagadas;
                    $cuotasAcumuladasPDF = 0;
                    $moraAcumuladaAnterior = $clienteData['mora_acumulada_anterior'] ?? 0;
                @endphp

                @foreach($clienteData['cuotas'] as $cuota)
                    @php
                        $intereses = $cuota->interes;
                        $comision = $cuota->comision;
                        $amortizacion = $cuota->monto - ($intereses + $comision);
                        $cuotasAcumuladasPDF += $cuota->monto;
                        $saldoCapital = $saldoCapitalBase - $cuotasAcumuladasPDF;
                        $totalDeudaCuota = $cuota->monto + ($cuota->moras_calculadas ?? 0);

                        $totalGeneral += $totalDeudaCuota;
                        $totalAmortizacion += $amortizacion;
                        $totalIntereses += $intereses;
                        $totalCuota += $cuota->monto;
                        $totalMora += ($cuota->moras_calculadas ?? 0);
                    @endphp
                    <tr @if($loop->even) class="highlight-row" @endif>
                        <td>{{ $cuota->numero }}</td>
                        <td>{{ $cuota->fecha_pago ? \Carbon\Carbon::parse($cuota->fecha_pago)->format('d/m/y') : 'N/A' }}</td>
                        <td>{{ number_format($cuota->monto ?? 0, 2) }}</td>
                        <td>{{ number_format($intereses, 2) }}</td>
                        <td>{{ number_format($amortizacion, 2) }}</td>
                        <td>{{ number_format($cuota->moras_calculadas ?? 0, 2) }}</td>
                        <td>{{ number_format($totalDeudaCuota, 2) }}</td>
                        <td>{{ number_format($saldoCapital, 2) }}</td>
                    </tr>
                @endforeach

                <tr class="total-row">
                    <td colspan="2">TOTALES</td>
                    <td>{{ number_format($totalCuota, 2) }}</td>
                    <td>{{ number_format($totalIntereses, 2) }}</td>
                    <td>{{ number_format($totalAmortizacion, 2) }}</td>
                    <td>{{ number_format($totalMora, 2) }}</td>
                    <td>{{ number_format($totalGeneral, 2) }}</td>
                    <td>---</td>
                </tr>
            @else
                <tr>
                    <td colspan="8" style="padding: 20pt; color: #999;">No se registran cuotas vencidas para este cliente.</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<!-- Disclaimer legal-->
<div class="disclaimer">
    Documento oficial emitido por GRUPO SANTIAGO PERÚ S.A.C. (RUC 20613371891), empresa formalmente constituida y registrada ante SUNAT. Esta cobranza corresponde a una operación crediticia legítima, sustentada en contrato suscrito y regulada por la Resolución SBS N° 02896-2023 y la Ley N° 29571 – Código de Protección y Defensa del Consumidor.
    Su presentación acredita actividad financiera formal, verificable y amparada por ley.
</div>
<!-- Footer -->

@if(!$loop->last)
<div class="page-break"></div>
@endif

@endforeach
@else
<!-- Sin datos -->
<div class="header">
    <div class="header-left">
        <h1>Cronograma de pagos</h1>
    </div>
    <div class="header-right">
        @php
        $logoPath = public_path('img/pdf/estado/logo-deudas.png');
        @endphp
        @if(file_exists($logoPath))
        <img src="{{ $logoPath }}" style="height: 35px;">
        @else
        <div style="background:#1e4a72; color:white; padding:8px; font-size:10px; font-weight:bold;">
            Grupo<br>Santiago
        </div>
        @endif
    </div>
</div>
<div style="text-align: center; padding: 30px;">
    <h3 style="font-size: 14px;">No se encontraron datos para generar el cronograma</h3>
    <p style="font-size: 10px;">Verifique los filtros aplicados o los datos del cliente.</p>
</div>
@endif
</div>
</body>
</html>