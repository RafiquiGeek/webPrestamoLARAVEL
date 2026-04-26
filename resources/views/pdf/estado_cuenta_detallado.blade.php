<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Cuenta - Préstamo #{{ $prestamo->id }}</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 1mm 1mm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8pt;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* Typography & Utilities */
        .text-xl { font-size: 14pt; font-weight: bold; }
        .text-lg { font-size: 11pt; font-weight: bold; }
        .text-md { font-size: 9pt; font-weight: bold; }
        .text-base { font-size: 8pt; }
        .text-sm { font-size: 7pt; color: #3b3b3bff; }
        .font-bold { font-weight: bold; }
        .text-muted { color: #535353ff; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .uppercase { text-transform: uppercase; }
        
        /* Layout */
        .w-full { width: 100%; }
        .mb-2 { margin-bottom: 5px; }
        .mb-4 { margin-bottom: 10px; }
        .p-2 { padding: 4px; }
        .border-bottom { border-bottom: 1px solid #ddd; }

        /* Header */
        /* Dompdf-compatible header (use table markup for best compatibility) */
        .header {
            width: 100%;
            margin-bottom: 10px;
        }
        .header-table {
            background-color: #abd08d;
            color: #000;
            border-radius: 4px;
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
            padding: 6px 8px;
        }
        .header-title {
            font-size: 12pt;
            font-weight: bold;
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .header-center {
            text-align: center;
            white-space: nowrap;
        }
        .header-meta {
            text-align: right;
            font-size: 12pt;
            white-space: nowrap;
            font-weight: bold;
        }

        /* Key Metrics Grid */
        .metrics-grid {
            width: 100%;
            margin-bottom: 0px;
            border-collapse: separate;
            border-spacing: 0 4px;
        }
        .metrics-grid td { vertical-align: top; }
        
        /* Main Client Info Box */
        .client-card {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 8px;
            margin-top: -10px;
        }

        .section-label {
            font-size: 6.5pt;
            text-transform: uppercase;
            color: #000;
            letter-spacing: 0.5px;
            margin-bottom: 0px;
        }

        .data-value {
            font-size: 10pt;
            font-weight: 600;
            color: #000;
        }

        /* Highlights */
        .highlight-name {
            font-size: 13pt;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 2px;
            display: block;
        }
        .highlight-dni {
            font-size: 9pt;
            color: #444444ff;
            background-color: #eee;
            padding: 1px 4px;
            border-radius: 3px;
            display: inline-block;
        }
        
        .highlight-amount-box {
            text-align: right;
            background-color: #fff9e6;
            border: 1px solid #ffeeba;
            padding: 6px 10px;
            border-radius: 6px;
        }
        .amount-value {
            font-size: 14pt;
            font-weight: 800;
            color: #d32f2f;
        }

        .badge-day {
            background-color: #2c3e50;
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 9pt;
            display: inline-block;
            margin-top: 15px;
        }

        /* Secondary Data Table */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            margin-bottom: 10px;
            color: black;
        }
        .details-table td {
            padding: 3px 5px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
            color: black;
        }
        .details-label {
            color: black;
            width: 80px;
            font-weight: normal;
        }
        
        /* Original Table Styles maintained for compatibility */
        .cuotas-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
        }
        .cuotas-table th {
            background-color: #abd08d;
            color: black;
            border: 1px solid #000;
            padding: 3px;
            font-weight: bold;
            text-align: center;
        }
        .cuotas-table td {
            border: 1px solid #666;
            padding: 2px;
            text-align: center;
        }
        .cuotas-table td.money {
            text-align: right;
            padding-right: 4px;
            color: #d32f2f;
            font-weight: bold;
        }
        
        .td-alto { height: 27.5px; } 
        .td-alto2 { height: 31px; } 
        
        .zone-pill {
            display: inline-block;
            border: 1px solid #abd08d;
            color: #3b5c22;
            background-color: #f0f7ea;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 8pt;
            font-weight: bold;
        }
        .zone-dni{
            display: inline-block;
            border: 1px solid #036b7eff;
            color: #0d335eff;
            background-color: #f2faffff;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 8pt;
            font-weight: bold;
        }

        /* Legacy support for bottom table header */
        .header-blue {
            background-color: #abd08d;
            color: #000;
            padding: 5px;
            font-weight: bold;
            text-align: center;
            border-radius: 4px;
            margin-top: 10px;
            margin-bottom: 5px;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    @php
        // Pre-calculation of data for cleaner view
        $persona = optional($prestamo->cliente->persona);
        $nombres = trim($persona->nombres . ' ' . $persona->ape_pat . ' ' . $persona->ape_mat);
        $nombres = !empty($nombres) ? $nombres : 'Sin datos';
        $dni = optional($prestamo->cliente->persona)->documento ?? 'N/A';
        
        try {
            // Usar dirección de cobro del préstamo
            $direccionCobroObj = $prestamo->direccionCobro;
            if ($direccionCobroObj) {
                $direccion = trim(collect([$direccionCobroObj->direccion, $direccionCobroObj->numero])->filter()->implode(' '));
                $referencia = $direccionCobroObj->referencia ?? '';
            } else {
                // Fallback a dirección del cliente si no hay dirección de cobro
                $direccionObj = optional($prestamo->cliente->persona->direcciones()->first());
                $direccion = $direccionObj ? trim(collect([$direccionObj->direccion, $direccionObj->numero])->filter()->implode(' ')) : 'No disponible';
                $referencia = $direccionObj ? $direccionObj->referencia : '';
            }
            
            $firstDireccion = $prestamo->cliente->persona->direcciones()->with('sucursal.zonas')->first();
            $zonas = $firstDireccion && $firstDireccion->sucursal ? $firstDireccion->sucursal->zonas : collect();
            $sucursal = $firstDireccion && $firstDireccion->sucursal ? $firstDireccion->sucursal->sucursal : null;
            
            $zonaNombre = $zonas->pluck('nombre')->implode(', ') ?: 'Sin zona';
            $sucursalNombre = $sucursal ?: 'Sin sucursal';
            
            $telefonos = $prestamo->cliente->persona->telefonos()->get();
            $telefonosFormateados = [];
            foreach ($telefonos as $tel) {
                $numero = $tel->numero;
                $extra = '';

                // Agregar comentario o tipo de teléfono
                if (!empty($tel->comentario)) {
                    $extra = ' (' . $tel->comentario . ')';
                } elseif ($tel->tipo_telefono !== 'celular') {
                    $extra = ' (' . ucfirst($tel->tipo_telefono) . ')';
                }

                $telefonosFormateados[] = $numero . $extra;
            }
            $celular = !empty($telefonosFormateados) ? implode(' | ', $telefonosFormateados) : 'No registrado';
            
            $laboral = $prestamo->cliente->laborales->first();
            $trabajo = $laboral ? ($laboral->nombre_lugar_trabajo ?? $laboral->actividad_economica ?? '') : '';
            $direccionTrabajo = $laboral ? ($laboral->direccion ?? '') : '';

            // Conyuge logic
            $conyugeCliente = $prestamo->cliente->conyuge;
            $conyugeNombre = $conyugeCliente ? trim(($conyugeCliente->persona->nombres ?? '') . ' ' . ($conyugeCliente->persona->ape_pat ?? '')) : '';
            $conyugeDni = $conyugeCliente ? ($conyugeCliente->persona->documento ?? '') : '';
            $conyugeTel = $conyugeCliente ? ($conyugeCliente->persona->telefonos()->where('tipo_telefono', 'celular')->first()->numero ?? '') : '';

             // Aval logic
            $aval = $prestamo->aval;
            $avalNombre = $aval ? trim(($aval->persona->nombres ?? '') . ' ' . ($aval->persona->ape_pat ?? '')) : '';
            $avalDni = $aval ? ($aval->persona->documento ?? '') : '';
            $avalCel = $aval ? ($aval->persona->telefonos()->where('tipo_telefono', 'celular')->first()->numero ?? '') : '';
            $avalDir = ($aval && $aval->persona && $aval->persona->direccion) ? trim($aval->persona->direccion->direccion . ' ' . $aval->persona->direccion->numero) : '';

        } catch (\Exception $e) {
            // Fallback for any breaks
            $direccion = 'Error datos';
            $zonaNombre = '-';
            $sucursalNombre = '-';
            $celular = '-';
            $trabajo = '-';
            $conyugeNombre = ''; $conyugeDni=''; $conyugeTel='';
            $avalNombre = ''; $avalDni=''; $avalCel=''; $avalDir='';
        }
        
        $diaSemana = 'N/A';
        if ($prestamo->fecha_primer_pago) {
            try {
                $diaSemana = mb_strtoupper(\Carbon\Carbon::parse($prestamo->fecha_primer_pago)->locale('es')->isoFormat('dddd'), 'UTF-8');
            } catch (\Exception $e) { $diaSemana = 'N/A'; }
        }
        
        $codigoCuenta = $prestamo->cuenta ? str_pad($prestamo->cuenta->codigo, 2, '0', STR_PAD_LEFT) : str_pad($prestamo->id, 2, '0', STR_PAD_LEFT);
        $codigoCuentaAsignada = $prestamo->cuenta ? $prestamo->cuenta->codigo : 'N/A';

        // Información de cuenta cliente (banco/billetera/efectivo)
        $infoCuentaCliente = 'EFECTIVO';
        if ($prestamo->cuentaCliente) {
            $cc = $prestamo->cuentaCliente;

            // Verificar si tiene entidad bancaria
            if ($cc->entidadBancaria && $cc->entidadBancaria->banco) {
                $banco = $cc->entidadBancaria->banco;
                $numeroCuenta = $cc->numero_cuenta ? ' - ' . $cc->numero_cuenta : '';
                $infoCuentaCliente = $banco . $numeroCuenta;
            }
            // Verificar si tiene billetera digital
            elseif ($cc->billeteraDigital && $cc->billeteraDigital->nombre) {
                $billetera = strtoupper($cc->billeteraDigital->nombre);
                $numeroCuenta = $cc->numero_cuenta ? ' - ' . $cc->numero_cuenta : '';
                $infoCuentaCliente = $billetera . $numeroCuenta;
            }
            // Si solo tiene número de cuenta sin banco/billetera
            elseif ($cc->numero_cuenta) {
                $infoCuentaCliente = $cc->numero_cuenta;
            }
            // Si tipo_cuenta_id = 1 y no tiene datos, es efectivo
            elseif ($cc->tipo_cuenta_id == 1) {
                $infoCuentaCliente = 'EFECTIVO';
            }
        }

        // Staff codes
        $jcc = optional(optional($prestamo->carterasJcc->first())->jcc)->codigo ?? '2';
        $analista = optional(optional($prestamo->carterasAnalista->first())->analista)->codigo ?? '2';
        $asesor = optional(optional($prestamo->carterasAsesor->first())->asesor)->codigo ?? '2';
        $equipo = "JCC: $jcc | Analista: $analista | Asesor: $asesor";

        // Fechas
        $desembolsoQuery = $prestamo->operaciones()->where('tipo_operacion', 'Desembolso')->first();
        $fechaDesembolso = $desembolsoQuery ? \Carbon\Carbon::parse($desembolsoQuery->fecha)->format('d/m/Y') : '';
        
        $fechaInicio = '';
        try { $fechaInicio = \Carbon\Carbon::parse($prestamo->fecha_primer_pago)->format('d/m/Y'); } catch (\Exception $e) {}
        
        $fechaTermino = '';
        if ($prestamo->cuotas->isNotEmpty()) {
            $fechaTermino = \Carbon\Carbon::parse($prestamo->cuotas->sortByDesc('fecha_pago')->first()->fecha_pago)->format('d/m/Y');
        }
        
        $numCuotas = $prestamo->cuotas->count();
        $montoCuota = $prestamo->cuotas->first()->monto ?? 0;

        $montoFondo = $fondo_provisional->monto_fondo ?? 0;
        $fondoExonerado = $fondo_provisional && $fondo_provisional->estado === 'exonerado';

    @endphp

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
                    <!--span class="text-muted" style="font-size: 7pt;">{{ $avalDir }}</span-->
                </td>
            </tr>
            @endif
            @if($prestamo->aval && $prestamo->aval->observaciones)
            <!--tr>
                <td class="details-label">OBS:</td>
                <td colspan="3" style="font-style: italic;">{{ $prestamo->aval->observaciones }}</td>
            </tr-->
            @endif
        </table>
    </div>

    <!-- Tabla de Cuotas -->
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

                // Totales de columnas
                $totalAbonado = 0;
                $totalMorasPagadas = 0;
                $totalMorasPendientes = 0;
                $totalMorasCuotas = 0;
                
                // Calcular el TOTAL de moras NO PAGADAS del préstamo (solo PENDIENTE y PARCIAL)
                $totalMorasGlobal = 0;
                foreach ($cuotas as $cuotaTemp) {
                    foreach ($cuotaTemp->moras as $mora) {
                        // Solo contar moras PENDIENTES (0) o PARCIALES (1)
                        $estadoMora = is_object($mora->estado) ? $mora->estado->value : (int)$mora->estado;
                        if ($estadoMora === 0 || $estadoMora === 1) {
                            $totalMorasGlobal += ($mora->monto - $mora->monto_pagado);
                        }
                    }
                }
                
                // Acumulador de lo pagado
                $totalMorasPagadasAcumulado = 0;
            @endphp
            @foreach($cuotas as $cuota)
            @php
                // Calcular abono total de las operaciones de esta cuota
                $abonado = $cuota->operaciones->where('estado', '!=', 'anulado')->sum('abono');

                // Calcular saldo de capital pendiente (antes de pagar esta cuota)
                $saldoCapital = max(0, $saldoCapitalInicial - $capitalPagadoAcumulado);

                // Actualizar el acumulado para la siguiente iteración
                $capitalPagadoAcumulado += $abonado;

                // Calcular moras NO PAGADAS de esta cuota específica (solo PENDIENTE y PARCIAL)
                $totalMorasCuota = 0;
                $montoPagadoMorasCuota = 0;
                foreach ($cuota->moras as $mora) {
                    $estadoMora = is_object($mora->estado) ? $mora->estado->value : (int)$mora->estado;

                    // Sumar el monto PAGADO de TODAS las moras (independientemente del estado)
                    $montoPagadoMorasCuota += $mora->monto_pagado;

                    // Solo contar moras pendientes PENDIENTES (0) o PARCIALES (1) para el saldo
                    if ($estadoMora === 0 || $estadoMora === 1) {
                        $montoPendienteMora = $mora->monto - $mora->monto_pagado;
                        $totalMorasCuota += $montoPendienteMora;
                    }
                }

                // Acumular moras pagadas
                $totalMorasPagadasAcumulado += $montoPagadoMorasCuota;

                // Saldo de moras: Total global - Pagado acumulado hasta ahora
                $saldoMorasAcumulado = $totalMorasGlobal - $totalMorasPagadasAcumulado;

                // Mora pendiente por cuota (ya calculada como moras no pagadas)
                // Restamos el abono a favor si existe
                $abonoFavorCuota = $cuota->saldoMoraFavor ?? 0;
                $moraNoPagadaCuota = $totalMorasCuota - $abonoFavorCuota;

                // Obtener la primera operación de pago para mostrar los datos
                $primeraOperacion = $cuota->operaciones()
                    ->where('estado', '!=', 'anulado')
                    ->where('tipo_operacion', 'Pago de cuota')
                    ->with('metodoDePago')
                    ->orderBy('fecha', 'asc')
                    ->first();

                // Método de pago y receptor
                $metodoPago = '';
                $receptor = '';
                if ($primeraOperacion && $primeraOperacion->metodoDePago) {
                    $metodoPago = $primeraOperacion->metodoDePago->metodo_pago;
                    $metodoUpper = strtoupper($metodoPago);

                    // Determinar el receptor según el método de pago
                    if (str_contains($metodoUpper, 'YAPE')) {
                        $receptor = 'YAPE';
                    } elseif (str_contains($metodoUpper, 'PLIN')) {
                        $receptor = 'PLIN';
                    } elseif (str_contains($metodoUpper, 'EFECTIVO')) {
                        $receptor = 'EFECTIVO';
                    } elseif (str_contains($metodoUpper, 'TRANSFERENCIA') || str_contains($metodoUpper, 'DEPOSITO') || str_contains($metodoUpper, 'DEPÓSITO')) {
                        // Si es transferencia o depósito, mostrar el banco
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

                // Número de operación
                $numeroOperacion = $primeraOperacion ? ($primeraOperacion->nro_operacion ?? $primeraOperacion->codigo) : '';

                // Fecha de abono (fecha de la operación)
                $fechaAbono = $primeraOperacion ? \Carbon\Carbon::parse($primeraOperacion->fecha)->format('d/m/Y') : '';

                // Acumular totales
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
                $currentRows = count($cuotas) + 1; // Including totals row
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

    <!-- Tabla de Resumen -->
    <table style="width: 100%; margin-top: 3px; border-collapse: collapse; font-size: 8.5pt;d-flex">
        <tr>
            <td style="width: 30%; border: 2px solid #000; padding: 3px; font-weight: bold; text-align: left; background-color: #f0f0f0;">Saldo Préstamo:</td>
            <td style="width: 19%; border: 2px solid #000; padding: 3px; text-align: center; font-weight: bold;">
                @php
                    $saldoPrestamo = max(0, $saldoCapitalInicial - $totalAbonado);
                @endphp
                {{ number_format($saldoPrestamo, 2) }}
            </td>
            <td style="width: 30%; border: 2px solid #000; padding: 3px; font-weight: bold; text-align: left; background-color: #f0f0f0;">Int. Moratorio Cuotas:</td>
            <td style="width: 19%; border: 2px solid #000; padding: 3px; text-align: center; font-weight: bold;">{{ number_format($totalMorasPendientes, 2) }}</td>
        </tr>
        <tr>
            <td style="width: 30%; border: 2px solid #000; padding: 3px; font-weight: bold; text-align: left; background-color: #f0f0f0;">TOTAL DEUDA:</td>
            <td style="width: 20%; border: 2px solid #000; padding: 3px; text-align: center; font-weight: bold; background-color: #ffcccc;">
                @php
                    $totalDeuda = $saldoPrestamo + $totalMorasPendientes;
                @endphp
                {{ number_format($totalDeuda, 2) }}
            </td>
            <td style="width: 30%; border: 2px solid #000; padding: 3px; font-weight: bold; text-align: left; background-color: #f0f0f0;">TOTAL COBRADO:</td>
            <td style="width: 20%; border: 2px solid #000; padding: 3px; text-align: center; font-weight: bold; background-color: #ccffcc;">{{ number_format($totalAbonado, 2) }}</td>
        </tr>
    </table>

    <!-- Salto de página -->
    <div style="page-break-before: always;"></div>

    <!-- Segunda tabla - Información adicional -->
    <div class="header-blue">SALDOS PENDIENTES</div>

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
            </tr>
        </thead>
        <tbody>
            @for($i = 0; $i < 31; $i++)
            <tr>
                <td class="td-alto2"></td>
                <td class="td-alto"></td>
                <td class="td-alto"></td>
                <td class="td-alto"></td>
                <td class="td-alto"></td>
                <td class="td-alto"></td>
                <td class="td-alto"></td>
                <td class="td-alto"></td>
            </tr>
            @endfor
        </tbody>
    </table>
</body>
</html>
