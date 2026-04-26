<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=100px, initial-scale=1.0, user-scalable=no">
    <title>Estado de Cuenta - Préstamo {{ $prestamo->id }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        @page {
            size: A4;
            margin: 15mm;
        }

        body {
            background: #fff;
            color: #333;
            font-size: 12px;
            line-height: 1.4;
            width: 100%;
            overflow-x: hidden;
            /*padding: 15px;*/
        }

        /* Header */
        .header {
            width: 100px;
            display: flex;
            padding: 0;
            margin: 0;
            margin-bottom: 20px;
        }

        .logo_top {
            text-align: center;
            width: 100%;
        }

        .logo_top img {
            max-width: 100%;
            width: 100%;
            height: auto;
        }

        /* Hero Section */
        .hero-section {
            /*margin-bottom: 25px;*/
            width: 100%;
        }

        .hero-section .logo_top {
            text-align: center;
            width: 100%;
        }

        .hero-section .logo_top img {
            max-width: 100%;
            width: 100%;
            height: auto;
        }

        /* Info Cards */
        .info-section {
            display: block;
            margin-bottom: 25px;
            width: 970px;
            padding: 1px 20px;
        }
        .info-user {
            display: flex;
            justify-content: space-between;
            width: 955px;
            padding: 1px 20px;
            margin-bottom: -3px;
            margin-top: 5px;
        }

        .info-card {
            background: #fff;
            border: 1px solid #a5a1a1;
            border-radius: 8px;
            padding: 0px;
            margin-bottom: 15px;
            box-sizing: border-box;
            display: flex;
            justify-content: space-between;
            width: 910px;
        }

        .info-card h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1e4a72;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
        }
        .cincuenta{
            width: 98%;
            margin: 1%;
            dis
        }

        .info-row {
            display: flex;
            justify-content: space-between;
        }

        .info-label {
            font-weight: 500;
            color: #666;
        }

        .info-value {
            font-weight: 600;
            color: #333;
        }

        .status-badge {
            background: #31bed8;
            color: white;
            padding: 1px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }

        .dia-badge {
            background: #31bed8;
            color: white;
            padding: 1px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }

        .team-section h3 {
            font-size: 14px;
            font-weight: 600;
            color: #1e4a72;
            margin-bottom: 10px;
        }

        .team-members {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-top: 6px;
            width: 100%;
        }

        .team-member {
            display: flex;
            align-items: center;
            gap: 8px;
            background: white;
            padding: 6px 10px;
            border-radius: 8px;
            /*border: 1px solid #a5a1a1;*/
            flex: 1 1 calc(33.333% - 10px);
            box-sizing: border-box;
            justify-content: center;
            text-align: center;
            min-width: 140px;
        }

        .member-icon {
            width: 24px;
            height: 24px;
            color: #1e4a72;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .member-icon img { width: 24px; height: 24px; object-fit: cover; border-radius: 50%; }

        /* Table */
        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            /*box-shadow: 0 2px 8px rgba(0,0,0,0.1);*/
            width: 910px;
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table thead {
            background: #1e4a72;
            color: white;
        }

        .table th {
            padding: 12px 8px;
            text-align: center;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .table td {
            padding: 10px 8px;
            text-align: center;
            font-size: 11px;
            border-bottom: 1px solid rgb(155, 159, 163);
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .table tbody tr:nth-child(even) {
            background: #fdfdfd;
        }

        .total-row {
            background: #1e4a72 !important;
            color: white !important;
            font-weight: 600;
        }

        .total-row td {
            border-bottom: none;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #1e4a72, #2d5f8f);
            color: white;
            padding: 20px;
            margin-top: 30px;
            display: block;
            width: 100%;
            box-sizing: border-box;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-logo-icon {
            width: 30px;
            height: 30px;
            background: white;
            color: #1e4a72;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
        }

        .website {
            font-size: 14px;
            font-weight: 500;
        }
        .user-name{
            font-size: 11pt;
            text-transform: lowercase;
            text-transform: capitalize;
        }
        .right{
            text-align: right;
        }
        td{
            padding: 3px!important;
        }
        .sinborde{
            border-bottom: 0!important;
        }
        /* Print Styles */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="hero-section">
        <div class="logo_top">
            <img src="{{ asset('img/pdf/estado/head.jpg') }}">
        </div>
    </div>
    <div class="info-user">
        <div class="cincuenta">
            <p><span class="user-name">Hola, <b>{{ $prestamo->cliente->persona->nombres }} {{ $prestamo->cliente->persona->ape_pat }}</b></span></p>
        </div>
        <div class="cincuenta right">
            <p>Número de cuotas: <b><span style="color: #31bed8;">{{ $prestamo->cuotas()->where('estado', 2)->count() }}</span>/{{ $prestamo->plazo }}</b></p>
        </div>
    </div>
    <!-- Hero Section -->
    <div class="">
        <div class="logo_top">
            <img src="{{ asset('img/pdf/estado/heroc.jpg') }}">
        </div>
    </div>

    <!-- Info Section -->
    <div class="info-section">
        <!-- General Info -->
        <div class="">
            <h3 style="color: #12629d;">General</h3>
        </div>
        <div class="info-card">
            <div class="cincuenta" style="display: flex; width: 60%;">
                <div class="" style="margin-right: 10px;">
                    <div class="info-row">
                        <span class="info-label">COD Cliente:</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Apellidos y nombres:</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Dirección:</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">COD Préstamo:</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Estado:</span>
                    </div>
                </div>
                <div class="">
                    <div class="info-row">
                        <span class="info-value">000000{{ $prestamo->cuenta_cliente_id }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-value">{{ $prestamo->cliente->persona->nombres }} {{ $prestamo->cliente->persona->ape_pat }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-value">
                            @if($prestamo->direccionCobro)
                                {{ $prestamo->direccionCobro->direccion }} {{ $prestamo->direccionCobro->numero }}
                            @elseif($prestamo->cliente->persona->direcciones->first())
                                {{ $prestamo->cliente->persona->direcciones->first()->direccion }}
                            @else
                                Sin dirección
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-value">000000{{ $prestamo->id }}</span>
                    </div>
                    <div class="info-row">
                        <span class="status-badge">{{ $prestamo->estado }}</span>
                    </div>
                </div>
            </div>
            <div class="cincuenta" style="display: flex; width: 40%;">
                <div class="" style="margin-right: 10px;">
                    <div class="info-row">
                        <span class="info-label">Sucursal:</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Zona:</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Capital:</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Monto Total:</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Cuenta:</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nº de cuenta:</span>
                    </div>
                </div>
                <div class="" style="margin-right: 10px;">
                    <div class="info-row">
                        <span class="info-value">
                            <span class="info-value">
                                @if($prestamo->cliente->direccionPrincipal && $prestamo->cliente->direccionPrincipal->sucursal)
                                    {{ $prestamo->cliente->direccionPrincipal->sucursal->sucursal }}
                                @else
                                    Sin sucursal asignada
                                @endif
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-value">
                            <span class="info-value">
                                @if($prestamo->cliente->direccionPrincipal && $prestamo->cliente->direccionPrincipal->sucursal)
                                    @php
                                        $sucursal = $prestamo->cliente->direccionPrincipal->sucursal;
                                        $zona = $sucursal->zonas->first(); // Obtiene la primera zona asociada
                                    @endphp

                                    @if($zona)
                                        {{ $zona->nombre }} <!-- Muestra el nombre de la zona -->
                                    @else
                                        Sin zona asignada
                                    @endif
                                @else
                                    Sin sucursal asignada
                                @endif
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-value">S/ {{ number_format($prestamo->cantidad_solicitada, 2) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-value">S/ {{ number_format($prestamo->cuotas->sum('monto'), 2) }}</span>
                    </div>
                    @php
                        // Información de cuenta cliente (banco/billetera/efectivo)
                        $infoCuentaCliente = 'EFECTIVO';
                        $tipoCuenta = 'EFECTIVO';
                        $nombreTercero = null;
                        if ($prestamo->cuentaCliente) {
                            $cc = $prestamo->cuentaCliente;

                            // Determinar si es propia o de terceros
                            if ($cc->tipo_cuenta_id == 3) {
                                $tipoCuenta = 'TERCEROS';
                                $nombreTercero = $cc->titular_cuenta;
                            } elseif ($cc->tipo_cuenta_id == 2) {
                                $tipoCuenta = 'PROPIA';
                            }

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
                    @endphp
                    <div class="info-row">
                        <span class="info-value">{{ $tipoCuenta }}@if($tipoCuenta == 'TERCEROS' && $nombreTercero) - {{ $nombreTercero }}@endif</span>
                    </div>
                    <div class="info-row">
                        <span class="info-value">{{ $infoCuentaCliente }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Status -->
        <div class="">
            <h3 style="color: #12629d;">Estado de cuenta</h3>
        </div>
        <div class="info-card">
            <div class="cincuenta">
                <div class="info-row">
                    <span class="info-label">Tipo Solicitud:</span>
                    <span class="info-label">{{ ucfirst($prestamo->tipo_solicitud ?? 'N/A') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Cuenta asignada:</span>
                    <span class="info-value">{{ $prestamo->cuenta_id }}</span>
                </div>
            </div>
            <div class="cincuenta">
                <div class="info-row">
                    <span class="info-label">Plazo:</span>
                    <span class="info-value">{{ $prestamo->plazo }} cuotas</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Día de pago:</span>
                    <span class="dia-badge">
                        {{ ucfirst(\Carbon\Carbon::parse($prestamo->fecha_primer_pago)->locale('es')->dayName) }}
                    </span>
                </div>
            </div>
            <div class="cincuenta">
                <div class="info-row">
                    <span class="info-label">Fecha Desembolso:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($prestamo->fecha_primer_pago)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha de Término:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($prestamo->cuotas->sortByDesc('fecha_pago')->first()->fecha_pago)->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>

        <!-- Team Section -->
        <div class="">
            <h3 style="color: #12629d;">Equipo</h3>
        </div>
        <div class="">
            <div class="team-members">
                <!-- Analistas -->
                @foreach ($prestamo->carterasAnalista as $carteraAnalista)
                    @php
                        $analista = $carteraAnalista->user;
                    @endphp
                    @if($analista)
                        <div class="team-member">
                            <div class="member-icon"><img src="{{ asset('img/pdf/estado/user.jpg') }}"></div>
                            <span>Analista: <b>{{ $analista->codigo }}</b></span>
                        </div>
                    @endif
                @endforeach

                <!-- Asesores -->
                @foreach ($prestamo->carterasAsesor as $carteraAsesor)
                    @php
                        $asesor = $carteraAsesor->user;
                    @endphp
                    @if($asesor)
                        <div class="team-member">
                            <div class="member-icon"><img src="{{ asset('img/pdf/estado/user.jpg') }}"></div>
                            <span>Asesor: <b>{{ $asesor->codigo }}</b></span>
                        </div>
                    @endif
                @endforeach

                <!-- JCC -->
                @foreach ($prestamo->carterasJcc as $carteraJcc)
                    @php
                        $jcc = $carteraJcc->user;
                    @endphp
                    @if($jcc)
                        <div class="team-member">
                            <div class="member-icon"><img src="{{ asset('img/pdf/estado/user.jpg') }}"></div>
                            <span>JCC: <b>{{ $jcc->codigo }}</b></span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
        <div class="" style="margin-top: 20px;">
            <!-- Payment Schedule Table -->
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>IT</th>
                            <th>FECHA DE PAGO</th>
                            <th>CAPITAL</th>
                            <th>MONTO CUOTA</th>
                            <th>RECEPTOR</th>
                            <th>Nº OP.</th>
                            <!--th>OBSERVACIÓN</th-->
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($prestamo->cuotas as $cuota)
                            @php
                                $abonado = $cuota->operaciones->sum('abono');
                                $saldo = $cuota->monto - $abonado;
                                
                                // Obtener la primera operación para mostrar los datos
                                $primeraOperacion = $cuota->operaciones->first();
                                
                                // Determinar el método de pago en lugar del banco
                                $metodoPago = $primeraOperacion && $primeraOperacion->metodoDePago ? $primeraOperacion->metodoDePago->metodo_pago : '-';
                                
                                // Determinar el código de operación
                                $codigoOperacion = $primeraOperacion ? $primeraOperacion->codigo : '-';
                                
                                // Determinar la observación basada en el estado y los pagos
                                $observacion = '';
                                if ($cuota->estado->value == 2) { // PAGADO
                                    $observacion = 'PAGADO';
                                } elseif ($cuota->estado->value == 1) { // PARCIAL
                                    $observacion = 'PAGO PARCIAL';
                                } else { // PENDIENTE
                                    $observacion = 'PENDIENTE';
                                }
                                
                                // Si hay múltiples operaciones, agregar información adicional
                                if ($cuota->operaciones->count() > 1) {
                                    $observacion .= ' (' . $cuota->operaciones->count() . ' pagos)';
                                }
                            @endphp
                            <tr>
                                <td>{{ $cuota->numero }}</td>
                                <td>{{ \Carbon\Carbon::parse($cuota->fecha_pago)->format('d/m/Y') }}</td>
                                <td>S/ {{ number_format($prestamo->cantidad_solicitada, 2) }}</td>
                                <td>S/ {{ number_format($cuota->monto, 2) }}</td>
                                <td>{{ $metodoPago }}</td>
                                <td>{{ $codigoOperacion }}</td>
                                <!--td>{{ $observacion }}</td-->
                            </tr>
                        @endforeach
                        <tr style="margin-top: 10px!important;">
                            <td class="sinborde"></td>
                            <td class="sinborde"></td>
                            <td class="total-row sinborde" style="border-radius: 5px 0px 0 5px;">-</td>
                            <td class="total-row sinborde" style="border-radius: 0 5px 5px 0;"><strong>S/ {{ number_format($prestamo->cuotas->sum('monto'), 2) }}</strong></td>
                            <td class="sinborde"></td>
                            <td class="sinborde"></td>
                            <td class="sinborde"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="hero-section">
        <div class="logo_top">
            <img src="{{ asset('img/pdf/estado/footer.jpg') }}">
        </div>
    </div>
</body>
</html>