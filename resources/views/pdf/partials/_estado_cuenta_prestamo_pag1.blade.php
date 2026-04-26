{{--
    Partial: Primera página del Estado de Cuenta del Préstamo
    Variables requeridas: $prestamo, $cuotas, $fondo_provisional
    Variables calculadas en el bloque php del archivo padre:
      $nombres, $dni, $direccion, $referencia, $zonaNombre, $sucursalNombre,
      $celular, $trabajo, $direccionTrabajo, $conyugeNombre, $conyugeDni, $conyugeTel,
      $avalNombre, $avalDni, $avalCel, $diaSemana, $infoCuentaCliente,
      $codigoCuentaAsignada, $equipo, $numCuotas, $montoCuota,
      $montoFondo, $fondoExonerado, $fechaDesembolso, $fechaInicio, $fechaTermino
--}}

<!-- HEADER -->
<div class="header">
    <table class="header-table">
        <tr style="width:100%; display: table-row; align-items: center;">
            <td style="width:55%;">
                <div class="header-title">ESTADO DE CUENTA</div>
            </td>
            <td class="header-meta" style="width:30%; text-align:center;">
                <span class="zone-dni" style="margin-top: 5px;margin-bottom: -5px;">{{ $diaSemana }}</span>
                <span class="zone-pill" style="margin-left:6px;margin-top: 10px;margin-bottom: -5px;">{{ $infoCuentaCliente }}</span>
            </td>
            <td style="width:15%;">
                <div class="header-meta">#{{ $prestamo->id }}</div>
            </td>
        </tr>
    </table>
</div>

<!-- PRIMARY INFO: Priority 1-5 -->
<table class="metrics-grid">
    <tr>
        <!-- Left: Name, DNI, Zone -->
        <td width="60%" style="vertical-align: top; padding-right: 5px;">
            <div style="margin-bottom: 8px;">
                <div class="highlight-name">{{ $nombres }}</div>
                <div style="margin-top: 7px;">
                    <span class="zone-dni">DNI: {{ $dni }}</span>
                    <span class="zone-pill">{{ $zonaNombre }} - {{ $sucursalNombre }}</span>
                </div>
                <div class="" style="margin-top: 3px;width: 100%;font-size: 8pt; color: #000000ff; margin-left: 8px;border-top: 1px dashed #757575ff;">
                    <span>Cel: <b>{{ $celular }}</b></span>
                </div>
                <div class="">
                    @if(!empty($prestamo->observaciones))
                        <p style="border-top: 1px dashed #757575ff; padding-left:8px;margin-top: 5px; font-style: italic; color: #333;font-size: 7.8pt;">{!! nl2br(e($prestamo->observaciones)) !!}</p>
                    @else
                        <p class="text-muted">-</p>
                    @endif
                </div>
            </div>
        </td>
        <!-- Right: Loan Amount, Important Dates -->
        <td width="40%" style="vertical-align: top;">
            <div class="highlight-amount-box">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 50%; text-align: left; vertical-align: top;">
                            <div class="section-label">MONTO PRÉSTAMO</div>
                            <div class="amount-value">S/ {{ number_format($prestamo->cantidad_solicitada ?? 0, 2) }}</div>
                        </td>
                        <td style="width: 50%; text-align: right; vertical-align: top;">
                            <div class="section-label">CTA. ASIGNADA</div>
                            <div class="amount-value">{{ $codigoCuentaAsignada }}</div>
                        </td>
                    </tr>
                </table>
                <div style="margin-top: 4px; border-top: 1px dashed #ccc; padding-top: 4px;">
                     <table style="width: 100%; font-size: 8pt; border-collapse: collapse;">
                        <tr>
                            <td class="text-black" style="width: 60%;">Cuota:</td>
                            <td style="text-align: right;"><b style="color:#d32f2f;">S/ {{ number_format($montoCuota, 2) }}</b></td>
                        </tr>
                        <tr>
                            <td class="text-black" style="width: 60%;">Fondo Provisional:</td>
                            <td style="text-align: right;">
                                @if($fondoExonerado)
                                    <b style="color: #28a745;">EXONERADO</b>
                                @else
                                    <b>S/ {{ number_format($montoFondo, 2) }}</b>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-black" style="width: 60%;">Total Préstamos:</td>
                            <td style="text-align: right;">
                                <b>{{ $prestamo->cliente->prestamos()->count() }}</b>
                            </td>
                        </tr>
                     </table>
                </div>
            </div>
        </td>
    </tr>
</table>

<!-- SECONDARY DETAILS COMPACT -->
<div class="client-card mb-2">
    <table class="details-table">
        <tr>
            <td class="details-label">DIRECCIÓN:</td>
            <td colspan="3" style="font-weight: bold;">{{ $direccion }} <span class="text-muted">({{ $referencia }})</span></td>
        </tr>
        <tr>
            <td class="details-label">TRABAJO:</td>
            <td colspan="3">
                @if(!empty($trabajo))
                    <b>{{ $trabajo }}</b>
                @endif
                @if(!empty($direccionTrabajo))
                    @if(!empty($trabajo)) - @endif
                    <span class="text-muted">{{ $direccionTrabajo }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="details-label">EQUIPO:</td>
            <td>{{ $equipo }}</td>
            <td class="details-label">PLAZO:</td>
            <td>{{ $numCuotas }} Semanas</td>
        </tr>
        <tr>
            <td class="details-label">FECHAS:</td>
            <td colspan="3">
                Desembolso: <b>{{ $fechaDesembolso }}</b> &nbsp;|&nbsp;
                Inicio: <b>{{ $fechaInicio }}</b> &nbsp;|&nbsp;
                Término: <b>{{ $fechaTermino }}</b>
            </td>
        </tr>
        @if(!empty($conyugeNombre))
        <tr>
            <td class="details-label">CÓNYUGE:</td>
            <td colspan="3">{{ $conyugeNombre }} &nbsp; <span class="text-muted">DNI: {{ $conyugeDni }} &nbsp; Telf: {{ $conyugeTel }}</span></td>
        </tr>
        @endif
        @if(!empty($avalNombre))
        <tr>
            <td class="details-label">AVAL:</td>
            <td colspan="3">
                {{ $avalNombre }} &nbsp; DNI: {{ $avalDni }} &nbsp; Cel: {{ $avalCel }}<br>
            </td>
        </tr>
        @endif
    </table>
</div>

<!-- Tabla de Cuotas del Préstamo -->
<table class="cuotas-table">
    <thead>
        <tr>
            <th>IT</th>
            <th>FECHA</th>
            <th>CAPITAL</th>
            <th>ABONO</th>
            <th>MORAS</th>
            <th>F.ABONO</th>
            <th>N-OP</th>
            <th>RECEPT</th>
            <th>*M.P</th>
        </tr>
    </thead>
    <tbody>
        @php
            $saldoCapitalInicial = $cuotas->sum('monto');
            $capitalPagadoAcumulado = 0;

            $totalAbonado = 0;
            $totalMorasPagadas = 0;
            $totalMorasPendientes = 0;
            $totalMorasCuotas = 0;

            // Calcular el TOTAL de moras NO PAGADAS del préstamo (solo PENDIENTE y PARCIAL)
            $totalMorasGlobal = 0;
            foreach ($cuotas as $cuotaTemp) {
                foreach ($cuotaTemp->moras as $mora) {
                    $estadoMora = is_object($mora->estado) ? $mora->estado->value : (int)$mora->estado;
                    if ($estadoMora === 0 || $estadoMora === 1) {
                        $totalMorasGlobal += ($mora->monto - $mora->monto_pagado);
                    }
                }
            }

            $totalMorasPagadasAcumulado = 0;
        @endphp
        @foreach($cuotas as $cuota)
        @php
            $abonado = $cuota->operaciones->where('estado', '!=', 'anulado')->sum('abono');
            $saldoCapital = max(0, $saldoCapitalInicial - $capitalPagadoAcumulado);
            $capitalPagadoAcumulado += $abonado;

            // Calcular moras NO PAGADAS de esta cuota específica (solo PENDIENTE y PARCIAL)
            $totalMorasCuota = 0;
            $montoPagadoMorasCuota = 0;
            foreach ($cuota->moras as $mora) {
                $estadoMora = is_object($mora->estado) ? $mora->estado->value : (int)$mora->estado;
                $montoPagadoMorasCuota += $mora->monto_pagado;
                if ($estadoMora === 0 || $estadoMora === 1) {
                    $montoPendienteMora = $mora->monto - $mora->monto_pagado;
                    $totalMorasCuota += $montoPendienteMora;
                }
            }

            $totalMorasPagadasAcumulado += $montoPagadoMorasCuota;
            $saldoMorasAcumulado = $totalMorasGlobal - $totalMorasPagadasAcumulado;

            $abonoFavorCuota = $cuota->saldoMoraFavor ?? 0;
            $moraNoPagadaCuota = $totalMorasCuota - $abonoFavorCuota;

            $primeraOperacion = $cuota->operaciones()
                ->where('estado', '!=', 'anulado')
                ->where('tipo_operacion', 'Pago de cuota')
                ->with('metodoDePago')
                ->orderBy('fecha', 'asc')
                ->first();

            $metodoPago = '';
            $receptor = '';
            if ($primeraOperacion && $primeraOperacion->metodoDePago) {
                $metodoPago = $primeraOperacion->metodoDePago->metodo_pago;
                $metodoUpper = strtoupper($metodoPago);
                if (str_contains($metodoUpper, 'YAPE')) {
                    $receptor = 'YAPE';
                } elseif (str_contains($metodoUpper, 'PLIN')) {
                    $receptor = 'PLIN';
                } elseif (str_contains($metodoUpper, 'EFECTIVO')) {
                    $receptor = 'EFECTIVO';
                } elseif (str_contains($metodoUpper, 'TRANSFERENCIA') || str_contains($metodoUpper, 'DEPOSITO') || str_contains($metodoUpper, 'DEPÓSITO')) {
                    if ($primeraOperacion->entidad_bancaria) {
                        $bancoNombre = strtoupper($primeraOperacion->entidad_bancaria);
                        if (str_contains($bancoNombre, 'BBVA')) $receptor = 'BBVA';
                        elseif (str_contains($bancoNombre, 'BCP')) $receptor = 'BCP';
                        elseif (str_contains($bancoNombre, 'INTERBANK')) $receptor = 'INTERBANK';
                        elseif (str_contains($bancoNombre, 'SCOTIABANK')) $receptor = 'SCOTIABANK';
                        else $receptor = substr($bancoNombre, 0, 8);
                    } else {
                        $receptor = 'BANCO';
                    }
                } else {
                    $receptor = substr($metodoUpper, 0, 8);
                }
            }

            $numeroOperacion = $primeraOperacion ? ($primeraOperacion->nro_operacion ?? $primeraOperacion->codigo) : '';
            $fechaAbono = $primeraOperacion ? \Carbon\Carbon::parse($primeraOperacion->fecha)->format('d/m/Y') : '';

            $totalAbonado += $abonado;
            $totalMorasPagadas += $montoPagadoMorasCuota;
            $totalMorasPendientes += $moraNoPagadaCuota;
            $totalMorasCuotas += $totalMorasCuota;
        @endphp
        <tr>
            <td class="td-alto">{{ $cuota->numero }}</td>
            <td class="td-alto">{{ \Carbon\Carbon::parse($cuota->fecha_pago)->format('d/m/Y') }}</td>
            <td class="td-alto">S/ {{ number_format($saldoCapital, 2) }}</td>
            <td class="td-alto">{{ $abonado > 0 ? 'S/ ' . number_format($abonado, 2) : '' }}</td>
            <td class="td-alto" style="text-align: right; padding-right: 4px; color: #000; font-weight: bold;">
                @if($montoPagadoMorasCuota != 0)
                    {{ number_format(abs($montoPagadoMorasCuota), 2) }}
                @endif
            </td>
            <td class="td-alto">{{ $fechaAbono }}</td>
            <td class="td-alto" style="width: 120px;">{{ $numeroOperacion }}</td>
            <td class="td-alto">{{ $receptor }}</td>
            <td class="td-alto" style="text-align: right; padding-right: 4px; font-weight: bold; {{ $moraNoPagadaCuota < 0 ? 'color: #000;' : 'color: #d32f2f;' }}">
                @if($moraNoPagadaCuota != 0)
                    S/. {{ number_format($moraNoPagadaCuota, 2) }}
                @endif
            </td>
        </tr>
        @endforeach
        @php
            $totalRows = 21;
            $currentRows = count($cuotas) + 1;
            $remainingRows = max(0, $totalRows - $currentRows);
        @endphp
        @for($i = 0; $i < $remainingRows; $i++)
        <tr>
            <td class="td-alto"></td>
            <td class="td-alto"></td>
            <td class="td-alto"></td>
            <td class="td-alto"></td>
            <td class="td-alto"></td>
            <td class="td-alto"></td>
            <td class="td-alto"></td>
            <td class="td-alto"></td>
            <td class="td-alto"></td>
        </tr>
        @endfor
        <!-- Fila de totales -->
        <tr style="background-color: #abd08d; font-weight: bold;">
            <td class="td-alto" colspan="3" style="text-align: right; padding-right: 5px;">TOTALES:</td>
            <td class="td-alto" style="text-align: right;">S/ {{ number_format($totalAbonado, 2) }}</td>
            <td class="td-alto" style="text-align: right; color: #000;">
                @if($totalMorasPagadas != 0)
                    {{ number_format(abs($totalMorasPagadas), 2) }}
                @endif
            </td>
            <td class="td-alto"></td>
            <td class="td-alto"></td>
            <td class="td-alto"></td>
            <td class="td-alto" style="text-align: right; font-weight: bold; {{ $totalMorasPendientes < 0 ? 'color: #000;' : 'color: #d32f2f;' }}">
                @if($totalMorasPendientes != 0)
                    S/. {{ number_format($totalMorasPendientes, 2) }}
                @endif
            </td>
        </tr>
    </tbody>
</table>

<!-- Tabla de Resumen del Préstamo -->
<table style="width: 100%; margin-top: 3px; border-collapse: collapse; font-size: 8.5pt;d-flex">
    <tr>
        <td style="width: 30%; border: 2px solid #000; padding: 3px; font-weight: bold; text-align: left; background-color: #f0f0f0;">Saldo Préstamo:</td>
        <td style="width: 19%; border: 2px solid #000; padding: 3px; text-align: center; font-weight: bold;">
            @php $saldoPrestamo = max(0, $saldoCapitalInicial - $totalAbonado); @endphp
            {{ number_format($saldoPrestamo, 2) }}
        </td>
        <td style="width: 30%; border: 2px solid #000; padding: 3px; font-weight: bold; text-align: left; background-color: #f0f0f0;">Int. Moratorio Cuotas:</td>
        <td style="width: 19%; border: 2px solid #000; padding: 3px; text-align: center; font-weight: bold;">{{ number_format($totalMorasPendientes, 2) }}</td>
    </tr>
    <tr>
        <td style="width: 30%; border: 2px solid #000; padding: 3px; font-weight: bold; text-align: left; background-color: #f0f0f0;">TOTAL DEUDA:</td>
        <td style="width: 20%; border: 2px solid #000; padding: 3px; text-align: center; font-weight: bold; background-color: #ffcccc;">
            @php $totalDeuda = $saldoPrestamo + $totalMorasPendientes; @endphp
            {{ number_format($totalDeuda, 2) }}
        </td>
        <td style="width: 30%; border: 2px solid #000; padding: 3px; font-weight: bold; text-align: left; background-color: #f0f0f0;">TOTAL COBRADO:</td>
        <td style="width: 20%; border: 2px solid #000; padding: 3px; text-align: center; font-weight: bold; background-color: #ccffcc;">{{ number_format($totalAbonado, 2) }}</td>
    </tr>
</table>
