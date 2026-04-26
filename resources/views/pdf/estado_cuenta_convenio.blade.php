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
        
        .td-alto { height: 13.5px; } 
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

    @if(!($soloConvenio ?? false))
    {{-- Primera página: misma que estado_cuenta_detallado (cálculo preciso de moras PENDIENTE/PARCIAL) --}}
    @include('pdf.partials._estado_cuenta_prestamo_pag1')

    <!-- Salto de página -->
    <div style="page-break-before: always;"></div>
    @endif

    @php
        // Detectar si es convenio flexible
        $esConvenioFlexible = $convenio->tipo === 'flexible';
    @endphp

    <!-- Segunda página: Datos de pago del convenio -->
    @php
        // Calcular totales del convenio
        $totalMontoCuotaConvenio = 0;
        $totalMontoPagadoConvenio = 0;
        $totalMorasPagadasConvenio = 0;
        $totalMorasPendientesConvenio = 0;
        $totalMorasCuotasConvenio = 0;
    @endphp

    <!-- HEADER PÁGINA 2 -->
    <div class="header">
        <table class="header-table">
            <tr style="width:100%; display: table-row; align-items: center;">
                <td style="width:55%;">
                    <div class="header-title">DATOS DE PAGO - CONVENIO {{ $esConvenioFlexible ? 'FLEXIBLE' : '' }}</div>
                </td>
                <td class="header-meta" style="width:30%; text-align:center;">
                    <span class="zone-pill">CONVENIO N° {{ $convenio->id }}</span>
                </td>
                <td style="width:15%;">
                    <div class="header-meta">#{{ $prestamo->id }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- INFORMACIÓN DEL CONVENIO -->
    <table class="metrics-grid">
        <tr>
            <!-- Left: Datos básicos -->
            <td width="60%" style="vertical-align: top; padding-right: 5px;">
                <div style="margin-bottom: 8px;">
                    <div class="highlight-name">{{ $nombres }}</div>
                    <div style="margin-top: 7px;">
                        <span class="zone-dni">DNI: {{ $dni }}</span>
                        <span class="zone-pill">{{ $zonaNombre }} - {{ $sucursalNombre }}</span>
                    </div>
                </div>
            </td>
            <!-- Right: Totales del convenio -->
            <td width="40%" style="vertical-align: top;">
                <div class="highlight-amount-box">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 50%; text-align: left; vertical-align: top;">
                                <div class="section-label">TOTAL CONVENIO</div>
                                <div class="amount-value">S/ {{ number_format($convenio->total_convenio ?? 0, 2) }}</div>
                            </td>
                            <td style="width: 50%; text-align: right; vertical-align: top;">
                                <div class="section-label">CUOTAS</div>
                                <div class="amount-value">{{ $convenio->numero_cuotas }}</div>
                            </td>
                        </tr>
                    </table>
                    <div style="margin-top: 4px; border-top: 1px dashed #ccc; padding-top: 4px;">
                         <table style="width: 100%; font-size: 8pt; border-collapse: collapse;">
                            <tr>
                                <td class="text-black" style="width: 60%;">Valor Cuota:</td>
                                <td style="text-align: right;"><b style="color:#d32f2f;">S/ {{ number_format($convenio->valor_cuota ?? 0, 2) }}</b></td>
                            </tr>
                            <tr>
                                <td class="text-black" style="width: 60%;">Progreso:</td>
                                <td style="text-align: right;">
                                    <b style="color: #28a745;">{{ number_format($convenio->porcentaje_avance ?? 0, 1) }}%</b>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-black" style="width: 60%;">Estado:</td>
                                <td style="text-align: right;">
                                    <b>{{ $convenio->estado->label() }}</b>
                                </td>
                            </tr>
                         </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- DETALLES DEL CONVENIO -->
    <div class="client-card mb-2">
        <table class="details-table">
            <tr>
                <td class="details-label">FECHA INICIO:</td>
                <td>{{ $convenio->fecha_inicio ? $convenio->fecha_inicio->format('d/m/Y') : '-' }}</td>
                <td class="details-label">FECHA FIRMA:</td>
                <td>{{ $convenio->fecha_firma ? $convenio->fecha_firma->format('d/m/Y') : '-' }}</td>
            </tr>
            <tr>
                <td class="details-label">MONTO CAPITAL:</td>
                <td><b>S/ {{ number_format($convenio->monto_capital, 2) }}</b></td>
                <td class="details-label">MONTO MORAS:</td>
                <td><b>S/ {{ number_format($convenio->monto_moras, 2) }}</b></td>
            </tr>
            <tr>
                <td class="details-label">DESCUENTO:</td>
                <td>S/ {{ number_format($convenio->descuento_moras, 2) }}</td>
                <td class="details-label">TOTAL PAGADO:</td>
                <td><b class="text-success">S/ {{ number_format($convenio->monto_total_pagado, 2) }}</b></td>
            </tr>
        </table>
    </div>

    @if($esConvenioFlexible)
    <!-- Tabla de Pagos Flexibles -->
    <table class="cuotas-table">
        <thead>
            <tr>
                <th>#</th>
                <th>FECHA PAGO</th>
                <th>MONTO</th>
                <th>MÉTODO</th>
                <th>NRO. OPERACIÓN</th>
                <th>RECEPTOR</th>
                <th>OBSERVACIONES</th>
            </tr>
        </thead>
        <tbody>
            @php
                $pagosFlexibles = $convenio->pagosFlexibles()->orderBy('fecha_pago', 'desc')->get();
                $totalPagadoFlexible = 0;
            @endphp
            @foreach($pagosFlexibles as $index => $pago)
            @php
                $totalPagadoFlexible += $pago->monto;

                // Obtener operación relacionada
                $operacion = $pago->operacion;
                $metodoPago = $operacion && $operacion->metodoDePago ? $operacion->metodoDePago->metodo_pago : $pago->metodo_pago;
                $nroOperacion = $operacion ? ($operacion->nro_operacion ?? $operacion->codigo ?? '') : '';

                // Determinar receptor
                $receptor = '';
                if ($operacion && $operacion->metodoDePago) {
                    $metodoUpper = strtoupper($metodoPago);
                    if (str_contains($metodoUpper, 'YAPE')) $receptor = 'YAPE';
                    elseif (str_contains($metodoUpper, 'PLIN')) $receptor = 'PLIN';
                    elseif (str_contains($metodoUpper, 'EFECTIVO')) $receptor = 'EFECTIVO';
                    elseif (str_contains($metodoUpper, 'TRANSFERENCIA') || str_contains($metodoUpper, 'DEPOSITO')) {
                        if ($operacion->entidad_bancaria) {
                            $bancoNombre = strtoupper($operacion->entidad_bancaria);
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
            @endphp
            <tr>
                <td class="td-alto">{{ $pagosFlexibles->count() - $index }}</td>
                <td class="td-alto">{{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') }}</td>
                <td class="td-alto">S/ {{ number_format($pago->monto, 2) }}</td>
                <td class="td-alto">{{ $metodoPago }}</td>
                <td class="td-alto">{{ $nroOperacion }}</td>
                <td class="td-alto">{{ $receptor }}</td>
                <td class="td-alto" style="font-size: 6.5pt;">{{ \Illuminate\Support\Str::limit($pago->observaciones ?? '', 20) }}</td>
            </tr>
            @endforeach
            @php
                $totalRows = 21;
                $currentRows = count($pagosFlexibles) + 1;
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
            </tr>
            @endfor
            <!-- Fila de totales -->
            <tr style="background-color: #abd08d; font-weight: bold;">
                <td class="td-alto" colspan="2" style="text-align: right; padding-right: 5px;">TOTAL PAGADO:</td>
                <td class="td-alto" style="text-align: right;">S/ {{ number_format($totalPagadoFlexible, 2) }}</td>
                <td class="td-alto" colspan="4"></td>
            </tr>
        </tbody>
    </table>

    @else
    <!-- Tabla de Cuotas del Convenio -->
    <table class="cuotas-table">
        <thead>
            <tr>
                <th>IT</th>
                <th>F.VENC</th>
                <th>CUOTA</th>
                <th>PAGADO</th>
                <th>MORAS</th>
                <th>F.PAGO</th>
                <th>SALDO</th>
                <th>ESTADO</th>
            </tr>
        </thead>
        <tbody>
            @php
                // Calcular el TOTAL de moras del convenio (todas las cuotas)
                $totalMorasGlobalPag2 = 0;
                foreach ($cuotasConvenio as $cuotaTemp) {
                    $moras = $cuotaTemp->moras ?? collect();
                    $morasActivas = $moras->filter(function($mora) {
                        $estado = is_string($mora->estado) ? $mora->estado : $mora->estado->value;
                        return !in_array($estado, ['regularizada', 'anulado']);
                    });
                    $totalMorasGlobalPag2 += $morasActivas->sum('monto');
                }
                
                // Acumulador de lo pagado en página 2
                $totalMorasPagadasAcumuladoPag2 = 0;
            @endphp
            @foreach($cuotasConvenio as $cuota)
            @php
                // Calcular abono de la cuota
                $abonado = $cuota->monto_pagado ?? 0;

                // Moras de esta cuota (solo las activas)
                $moras = $cuota->moras ?? collect();
                $morasActivas = $moras->filter(function($mora) {
                    $estado = is_string($mora->estado) ? $mora->estado : $mora->estado->value;
                    return !in_array($estado, ['regularizada', 'anulado']);
                });

                // Moras pagadas de esta cuota - Sumar de TODAS las moras (no solo activas)
                $montoPagadoMoras = $moras->sum('monto_pagado');
                $totalMorasPagadasAcumuladoPag2 += $montoPagadoMoras;
                
                // Saldo de moras: Total global - Pagado acumulado hasta ahora
                $saldoMorasAcumuladoPag2 = $totalMorasGlobalPag2 - $totalMorasPagadasAcumuladoPag2;
                
                // Mora pendiente de esta cuota específica
                $moraPendienteCuota = $morasActivas->sum(function($mora) {
                    return max(0, $mora->monto - $mora->monto_pagado);
                });
                
                // Calcular saldo a favor de moras de esta cuota
                $saldoFavorCuota = $cuota->abonosMoraFavor()->activos()->conSaldo()->sum('saldo_favor');

                // Obtener la última operación de pago para esta cuota
                $ultimaOperacion = \App\Models\Operacion::where('prestamo_id', $convenio->prestamo_id)
                    ->where('tipo_operacion', 'PAGO_CONVENIO')
                    ->where('estado', '!=', 'anulado')
                    ->where(function($query) use ($cuota) {
                        $query->where('comentario', 'LIKE', '%cuota #' . $cuota->numero_cuota . ' %')
                              ->orWhere('comentario', 'LIKE', '%cuota #' . $cuota->numero_cuota . ')%');
                    })
                    ->orderBy('fecha', 'desc')
                    ->first();

                // Fecha de pago
                $fechaPago = $ultimaOperacion ? \Carbon\Carbon::parse($ultimaOperacion->fecha)->format('d/m/Y') : '';

                // Saldo pendiente de la cuota
                $saldoPendiente = max(0, $cuota->monto_cuota - $abonado);

                // Calcular total de moras de esta cuota para mostrar individual
                $totalMorasCuotaConvenio = $morasActivas->sum('monto');

                // Acumular totales
                $totalMontoCuotaConvenio += $cuota->monto_cuota;
                $totalMontoPagadoConvenio += $abonado;
                $totalMorasPagadasConvenio += $montoPagadoMoras;
                $totalMorasPendientesConvenio += $moraPendienteCuota;
                $totalMorasCuotasConvenio += $totalMorasCuotaConvenio;
            @endphp
            <tr>
                <td class="td-alto">{{ $cuota->numero_cuota }}</td>
                <td class="td-alto">{{ $cuota->fecha_vencimiento ? \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y') : '-' }}</td>
                <td class="td-alto">S/ {{ number_format($cuota->monto_cuota, 2) }}</td>
                <td class="td-alto">{{ $abonado > 0 ? 'S/ ' . number_format($abonado, 2) : '' }}</td>
                <td class="td-alto" style="text-align: right; padding-right: 4px; font-weight: bold; color: #000;">
                    @if($montoPagadoMoras != 0)
                        {{ number_format(abs($montoPagadoMoras), 2) }}
                    @endif
                </td>
                <td class="td-alto">{{ $fechaPago }}</td>
                <td class="money td-alto">
                    @if($saldoFavorCuota > 0)
                        <span style="color: #000; font-weight: bold;">-S/. {{ number_format($saldoFavorCuota, 0) }}</span>
                    @elseif($moraPendienteCuota > 0)
                        <span style="color: #d32f2f; font-weight: bold;">S/. {{ number_format($moraPendienteCuota, 0) }}</span>
                    @endif
                </td>
                <td class="td-alto" style="font-size: 6.5pt;">
                    @php
                        // CuotaConvenio enum: 0=PENDIENTE, 1=PARCIAL, 2=PAGADO, 3=VENCIDO
                        $estadoBadge = match($cuota->estado->value) {
                            0 => 'PEND',    // PENDIENTE
                            1 => 'PARC',    // PARCIAL
                            2 => 'PAG',     // PAGADO
                            3 => 'VENC',    // VENCIDO
                            default => 'DESC'
                        };
                    @endphp
                    {{ $estadoBadge }}
                </td>
            </tr>
            @endforeach
            @php
                $totalRows = 21;
                $currentRows = count($cuotasConvenio) + 1;
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
            </tr>
            @endfor
            <!-- Fila de totales -->
            <tr style="background-color: #abd08d; font-weight: bold;">
                <td class="td-alto" colspan="2" style="text-align: right; padding-right: 5px;">TOTALES:</td>
                <td class="td-alto" style="text-align: right;">S/ {{ number_format($totalMontoCuotaConvenio, 2) }}</td>
                <td class="td-alto" style="text-align: right;">S/ {{ number_format($totalMontoPagadoConvenio, 2) }}</td>
                <td class="td-alto" style="text-align: right; font-weight: bold; color: #000;">
                    @if($totalMorasPagadasConvenio != 0)
                        {{ number_format(abs($totalMorasPagadasConvenio), 2) }}
                    @endif
                </td>
                <td class="td-alto"></td>
                <td class="money td-alto" style="text-align: right; {{ $totalMorasPendientesConvenio > 0 ? 'color: #d32f2f;' : '' }}">
                    @if($totalMorasPendientesConvenio > 0)
                        S/. {{ number_format($totalMorasPendientesConvenio, 0) }}
                    @endif
                </td>
                <td class="td-alto"></td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- Tabla de Resumen del Convenio -->
    <table style="width: 100%; margin-top: 3px; border-collapse: collapse; font-size: 8.5pt;">
        @if($esConvenioFlexible)
        <!-- Resumen para Convenio Flexible -->
        @php
            $saldoConvenioFlexible = max(0, $convenio->total_convenio - $totalPagadoFlexible);
        @endphp
        <tr>
            <td style="width: 30%; border: 2px solid #000; padding: 3px; font-weight: bold; text-align: left; background-color: #f0f0f0;">Total Convenio:</td>
            <td style="width: 19%; border: 2px solid #000; padding: 3px; text-align: center; font-weight: bold;">
                {{ number_format($convenio->total_convenio, 2) }}
            </td>
            <td style="width: 30%; border: 2px solid #000; padding: 3px; font-weight: bold; text-align: left; background-color: #f0f0f0;">Total Pagado:</td>
            <td style="width: 19%; border: 2px solid #000; padding: 3px; text-align: center; font-weight: bold; background-color: #ccffcc;">{{ number_format($totalPagadoFlexible, 2) }}</td>
        </tr>
        <tr>
            <td style="width: 30%; border: 2px solid #000; padding: 3px; font-weight: bold; text-align: left; background-color: #f0f0f0;">SALDO PENDIENTE:</td>
            <td style="width: 20%; border: 2px solid #000; padding: 3px; text-align: center; font-weight: bold; background-color: #ffcccc;">
                {{ number_format($saldoConvenioFlexible, 2) }}
            </td>
            <td style="width: 30%; border: 2px solid #000; padding: 3px; font-weight: bold; text-align: left; background-color: #f0f0f0;">Progreso:</td>
            <td style="width: 20%; border: 2px solid #000; padding: 3px; text-align: center; font-weight: bold; background-color: #d4edda;">
                @php
                    $progresoFlexible = $convenio->total_convenio > 0 ? ($totalPagadoFlexible / $convenio->total_convenio) * 100 : 0;
                @endphp
                {{ number_format($progresoFlexible, 1) }}%
            </td>
        </tr>
        @else
        <!-- Resumen para Convenio con Cuotas -->
        <tr>
            <td style="width: 30%; border: 2px solid #000; padding: 3px; font-weight: bold; text-align: left; background-color: #f0f0f0;">Saldo Convenio:</td>
            <td style="width: 19%; border: 2px solid #000; padding: 3px; text-align: center; font-weight: bold;">
                @php
                    $saldoConvenio = max(0, $totalMontoCuotaConvenio - $totalMontoPagadoConvenio);
                @endphp
                {{ number_format($saldoConvenio, 2) }}
            </td>
            <td style="width: 30%; border: 2px solid #000; padding: 3px; font-weight: bold; text-align: left; background-color: #f0f0f0;">Moras Pendientes:</td>
            <td style="width: 19%; border: 2px solid #000; padding: 3px; text-align: center; font-weight: bold;">{{ number_format($totalMorasPendientesConvenio, 2) }}</td>
        </tr>
        <tr>
            <td style="width: 30%; border: 2px solid #000; padding: 3px; font-weight: bold; text-align: left; background-color: #f0f0f0;">TOTAL DEUDA:</td>
            <td style="width: 20%; border: 2px solid #000; padding: 3px; text-align: center; font-weight: bold; background-color: #ffcccc;">
                @php
                    $totalDeudaConvenio = $saldoConvenio + $totalMorasPendientesConvenio;
                @endphp
                {{ number_format($totalDeudaConvenio, 2) }}
            </td>
            <td style="width: 30%; border: 2px solid #000; padding: 3px; font-weight: bold; text-align: left; background-color: #f0f0f0;">TOTAL COBRADO:</td>
            <td style="width: 20%; border: 2px solid #000; padding: 3px; text-align: center; font-weight: bold; background-color: #ccffcc;">{{ number_format($totalMontoPagadoConvenio, 2) }}</td>
        </tr>
        @endif
    </table>

</body>
</html>
