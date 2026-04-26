<!--resources\views\pdf\estado_cobranza.blade.php-->
@php
use App\Enums\CuotaEstado;
$totalMoras = $prestamo->cuotas->sum('cantidad_mora');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=100px, initial-scale=1.0, user-scalable=no">
    <title>Estado de Cuenta - Préstamo {{ $prestamo->id }}</title>
    <!-- Importar fuente Poppins desde Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body style="margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', 'DejaVu Sans', Arial, sans-serif; background: #fff; color: #333; font-size: 9pt; line-height: 1.4; width: 100%; overflow-x: hidden; padding: 15px;">
    <!-- Header -->
    <div style="width: 100%;">
        <div style="text-align: center; width: 100%;">
            @php
                $headerPath = public_path('img/pdf/estado/headc.png');
                $headerExists = file_exists($headerPath);
                $headerBase64 = '';
                if($headerExists) {
                    $imageData = base64_encode(file_get_contents($headerPath));
                    $headerBase64 = 'data:image/png;base64,' . $imageData;
                }
            @endphp
            @if($headerExists)
                <img style="max-width: 100%; width: 100%; height: auto;" src="{{ $headerBase64 }}">
            @else
                <div style="width:100%; height:60px; background:#1e4a72; color:white; text-align:center; line-height:60px; font-weight:bold; font-size: 9pt;">HEADER</div>
            @endif
        </div>
    </div>

    <!-- Info User Section -->
    <div style="display: table; width: 100%; margin-top: 7px; margin-bottom: 7px;padding-left:15px; padding-right:15px;">
        <div style="display: table-cell; vertical-align: middle; width: 70%;">
            <span style="font-size: 9pt; color: #333; margin-bottom: 15px;">
                @foreach ($prestamo->carterasJcc as $carteraJcc)
                    @php $jcc = $carteraJcc->user; @endphp
                    @if($jcc)
                        JCC:<b style="color: #07497d; font-weight: 700;"> {{ $jcc->codigo }}</b>
                    @endif
                @endforeach
            </span>
            
            <span style="margin-top: 10px; padding-bottom: 10px;border: 2px solid #000; font-weight: 800; font-size: 15pt; height: 35px; width: 45px; text-align: center; line-height: 31px; display: inline-block; vertical-align: middle; margin-left: 15px; margin-right: 15px;">
                {{ $prestamo->cuenta_id }}
            </span>
            
            <span style="font-size: 9pt; color: #333;margin-bottom: 15px;">
                @foreach ($prestamo->carterasAnalista as $carteraAnalista)
                    @php $analista = $carteraAnalista->user; @endphp
                    @if($analista)
                        AUT: <b style="color: #07497d; font-weight: 700;">{{ $analista->codigo }}</b>
                    @endif
                @endforeach
            </span>
        </div>
        <div style="display: table-cell; vertical-align: middle; text-align: right; width: 30%;">
            <span style="color: #1f1f1fff; font-size: 9pt;">Número de cuotas: <b><span style="color: #31bed8;">{{ $prestamo->cuotas()->where('estado', 2)->count() }}</span>/<span style="color: #07497d">{{ $prestamo->plazo }}</span></b></span>
        </div>
    </div>

    <!-- Hero Section -->
    <div style="width: 100%; margin-bottom: 20px;">
        <div style="text-align: center; width: 100%;">
            @php
                $bannerPath = public_path('img/pdf/estado/ban.png');
                $bannerExists = file_exists($bannerPath);
                $bannerBase64 = '';
                if($bannerExists) {
                    $imageData = base64_encode(file_get_contents($bannerPath));
                    $bannerBase64 = 'data:image/png;base64,' . $imageData;
                }
            @endphp
            @if($bannerExists)
                <img style="max-width: 100%; width: 100%; height: auto;" src="{{ $bannerBase64 }}">
            @else
                <div style="width:100%; height:60px; background:#1e4a72; color:white; text-align:center; line-height:60px; font-weight:bold; font-size: 9pt;">BANNER</div>
            @endif
        </div>
    </div>

    <!-- Info Section -->
    <div style="width: 100%; margin-top: -10px; margin-bottom: 25px;padding-left:15px; padding-right:15px;">
        <!-- General Info -->
        <div style="margin-bottom: 0px;">
            <p style="color: #000; font-weight: 700; font-size: 9pt; margin: 0;">Datos del cliente</p>
        </div>
        
        <!-- Cliente Data Table -->
        <div style="border-bottom: 1px solid #a1a1a1ff; padding: 10px 0; margin-bottom: 1px;margin-top: -10px;">
            <table style="width: 100%; border-collapse: collapse;font-size: 9pt;">
                <tr>
                    <td style="width: 20%; vertical-align: top; border: none;">
                        <span style="font-weight: 500;font-size: 8pt;">Apellidos y nombres:</span>
                    </td>
                    <td style="width: 50%; vertical-align: top; border: none;">
                        <span style="font-weight: 600;font-size: 8pt;">{{ $prestamo->cliente->persona->nombres }} {{ $prestamo->cliente->persona->ape_pat }}</span>
                    </td>
                    <td style="width: 10%; vertical-align: top; border: none;">
                        <span style="font-weight: 500;font-size: 8pt;">DNI:</span>
                    </td>
                    <td style="width: 20%; vertical-align: top; border: none;">
                        <span style="font-weight: 600;font-size: 8pt;">{{ optional($prestamo->cliente->persona)->documento ?? 'No disponible' }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; border: none;">
                        <span style="font-weight: 500;font-size: 9pt;">Dirección:</span>
                    </td>
                    <td style="vertical-align: top; border: none;" colspan="3">
                        <span style="font-weight: 600;font-size: 9pt;">
                            @foreach($prestamo->cliente->persona->direcciones as $direccion)
                                {{ $direccion->direccion }}
                            @endforeach
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; border: none;">
                        <span style="font-weight: 500; color: #666;font-size: 9pt;">Celular:</span>
                    </td>
                    <td style="vertical-align: top; border: none;" colspan="3">
                        <span style="font-weight: 600; color: #333;font-size: 9pt;">
                            @foreach($prestamo->cliente->persona->telefonos as $telefono)
                                {{ $telefono->numero }}
                            @endforeach
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Observaciones -->
        <div style="padding-left: 10px;font-size: 9pt;border-bottom: 1px solid #a1a1a1ff; padding: 10px 0; margin-bottom: 1px;margin-top: -5px;">
            <div style="margin-bottom: 5px; width: 100%;">
                <span style="font-weight: 500; font-size: 9pt;">Observaciones: </span>
                <span style="font-weight: 600; font-size: 9pt;">{{ $prestamo->observaciones }}</span>
            </div>
            <div>
                <span style="font-weight: 500; font-size: 9pt;">Tipo Solicitud: </span>
                <span style="font-weight: 600; font-size: 9pt;">{{ $prestamo->tipo_solicitud }}</span>
            </div>
        </div>

        <!-- Datos de cobranza title -->
        <div style="margin-top: 10px;margin-bottom: 4px;">
            <p style="color: #000; font-weight: 700; font-size: 9pt; margin: 0;">Datos de cobranza</p>
        </div>
        
        <!-- Payment Schedule Table -->
        <div style="margin-bottom: 25px;">
            <!-- Table Container -->
            <div style="width: 100%; border-radius: 10px; overflow: hidden;">
                
                <!-- Header Row -->
                <div style="display: table; width: 100%; background: #1e4a72; color: white;">
                    <div style="display: table-cell; padding: 6px 4px; text-align: center; font-size: 9pt; font-weight: 600; text-transform: uppercase; width: 8%; border-right: 1px solid rgba(255,255,255,0.3);">IT</div>
                    <div style="display: table-cell; padding: 6px 4px; text-align: center; font-size: 9pt; font-weight: 600; text-transform: uppercase; width: 15%; border-right: 1px solid rgba(255,255,255,0.3);">FECHA DE PAGO</div>
                    <div style="display: table-cell; padding: 6px 4px; text-align: center; font-size: 9pt; font-weight: 600; text-transform: uppercase; width: 15%; border-right: 1px solid rgba(255,255,255,0.3);">CAPITAL</div>
                    <div style="display: table-cell; padding: 6px 4px; text-align: center; font-size: 9pt; font-weight: 600; text-transform: uppercase; width: 15%; border-right: 1px solid rgba(255,255,255,0.3);">MONTO CUOTA</div>
                    <div style="display: table-cell; padding: 6px 4px; text-align: center; font-size: 9pt; font-weight: 600; text-transform: uppercase; width: 17%; border-right: 1px solid rgba(255,255,255,0.3);">RECEPTOR</div>
                    <div style="display: table-cell; padding: 6px 4px; text-align: center; font-size: 9pt; font-weight: 600; text-transform: uppercase; width: 10%; border-right: 1px solid rgba(255,255,255,0.3);">Nº OP.</div>
                    <div style="display: table-cell; padding: 6px 4px; text-align: center; font-size: 9pt; font-weight: 600; text-transform: uppercase; width: 20%;">OBSERVACIÓN</div>
                </div>
                
                <!-- Data Rows -->
                @php $rowCount = 0; @endphp
                @foreach ($prestamo->cuotas as $cuota)
                    @if ($cuota->estado->value == CuotaEstado::VENCIDO->value || $cuota->estado->value == CuotaEstado::PARCIAL->value || $cuota->estado->value == CuotaEstado::PENDIENTE->value)
                        @php
                            $rowCount++;
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
                            $rowBgColor = '#ffffff';
                            $textColor = '#333';
                            
                            if ($cuota->estado->value == CuotaEstado::PAGADO->value) { // PAGADO
                                $observacion = 'PAGADO';
                                $rowBgColor = '#fff';
                                $textColor = '#2d5a2d';
                            } elseif ($cuota->estado->value == CuotaEstado::PARCIAL->value) { // PARCIAL
                                $observacion = 'PAGO PARCIAL';
                                $rowBgColor = '#fff3cd';
                                $textColor = '#856404';
                            } elseif ($cuota->estado->value == CuotaEstado::VENCIDO->value) { // VENCIDO
                                $observacion = 'VENCIDO';
                                $rowBgColor = '#f8d7da';
                                $textColor = '#721c24';
                            } else { // PENDIENTE
                                $observacion = 'PENDIENTE';
                                $rowBgColor = '#fff';
                                $textColor = '#0c5460';
                            }
                            
                            // Si hay múltiples operaciones, agregar información adicional
                            if ($cuota->operaciones->count() > 1) {
                                $observacion .= ' (' . $cuota->operaciones->count() . ' pagos)';
                            }
                            
                            // Alternar color de fondo para filas pares/impares si no hay estado específico
                            if ($cuota->estado->value == CuotaEstado::PENDIENTE->value) {
                                $textColor = '#333';
                            }
                        @endphp
                        
                        <div style="display: table; width: 100%; background: {{ $rowBgColor }}; color: {{ $textColor }}; border-bottom: 1px solid #7faad1;">
                            <div style="display: table-cell; padding: 10px 8px; text-align: center; font-size: 9pt; width: 8%; font-weight: 600;">{{ $cuota->numero }}</div>
                            <div style="display: table-cell; padding: 10px 8px; text-align: center; font-size: 9pt; width: 15%;">{{ \Carbon\Carbon::parse($cuota->fecha_pago)->format('d/m/Y') }}</div>
                            <div style="display: table-cell; padding: 10px 8px; text-align: center; font-size: 9pt; width: 15%; font-weight: 600;">S/ {{ number_format($prestamo->cantidad_solicitada, 2) }}</div>
                            <div style="display: table-cell; padding: 10px 8px; text-align: center; font-size: 9pt; width: 15%; font-weight: 600;">S/ {{ number_format($cuota->monto, 2) }}</div>
                            <div style="display: table-cell; padding: 10px 8px; text-align: center; font-size: 9pt; width: 17%;">{{ $metodoPago }}</div>
                            <div style="display: table-cell; padding: 10px 8px; text-align: center; font-size: 9pt; width: 10%;">{{ $codigoOperacion }}</div>
                            <div style="display: table-cell; padding: 10px 8px; text-align: center; font-size: 9pt; width: 20%; font-weight: 600;">{{ $observacion }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- Resumen title -->
        <div style="margin-bottom: 15px;">
            <p style="color: #000; font-weight: 700; font-size: 9pt; margin: 0;">Resumen</p>
        </div>
        
        <!-- Resumen Cards usando tabla -->
        @php
            // Cargar las tres imágenes diferentes
            $coinPath = public_path('img/pdf/estado/coin.png');
            $coinExists = file_exists($coinPath);
            $coinBase64 = '';
            if($coinExists) {
                $imageData = base64_encode(file_get_contents($coinPath));
                $coinBase64 = 'data:image/png;base64,' . $imageData;
            }

            $handPath = public_path('img/pdf/estado/hand.png');
            $handExists = file_exists($handPath);
            $handBase64 = '';
            if($handExists) {
                $imageData = base64_encode(file_get_contents($handPath));
                $handBase64 = 'data:image/png;base64,' . $imageData;
            }

            $tickPath = public_path('img/pdf/estado/tick.png');
            $tickExists = file_exists($tickPath);
            $tickBase64 = '';
            if($tickExists) {
                $imageData = base64_encode(file_get_contents($tickPath));
                $tickBase64 = 'data:image/png;base64,' . $imageData;
            }
        @endphp
        
        <table style="width: 100%; border-collapse: separate; border-spacing: 10px;">
            <tr>
                <!-- Saldo Préstamo -->
                <td style="background: #21b8ed; color: white; padding: 20px; border-radius: 15px; text-align: left; width: 33.33%; vertical-align: middle;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 60px; vertical-align: middle; border: none;">
                                @if($tickExists)
                                    <img style="height: 50px; width: 50px;" src="{{ $coinBase64 }}">
                                @else
                                    <div style="width:50px; height:50px; background:white; border-radius:50%; color:#21b8ed; text-align:center; line-height:50px; font-size:30px;">S/</div>
                                @endif
                            </td>
                            <td style="vertical-align: middle; border: none;">
                                <div style="font-size: 18pt; font-weight: 800; margin-bottom: 2px;">{{ number_format($prestamo->saldo_restante, 2) }}</div>
                                <div style="font-size: 9pt; font-weight: 800;">SOLES</div>
                                <div style="font-size: 9pt;">Saldo Préstamo</div>
                            </td>
                        </tr>
                    </table>
                </td>

                <!-- Interés Moratorio -->
                <td style="background: #878785; color: white; padding: 20px; border-radius: 15px; text-align: left; width: 33.33%; vertical-align: middle;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 60px; vertical-align: middle; border: none;">
                                @if($tickExists)
                                    <img style="height: 50px; width: 50px;" src="{{ $handBase64 }}">
                                @else
                                    <div style="width:50px; height:50px; background:white; border-radius:50%; color:#878785; text-align:center; line-height:50px; font-size:30px;">S/</div>
                                @endif
                            </td>
                            <td style="vertical-align: middle; border: none;">
                                <div style="font-size: 18pt; font-weight: 800; margin-bottom: 2px;">{{ number_format($totalMoras, 2) }}</div>
                                <div style="font-size: 9pt; font-weight: 800;">SOLES</div>
                                <div style="font-size: 9pt;">Interés Moratorio Cuotas</div>
                            </td>
                        </tr>
                    </table>
                </td>

                <!-- Total Deuda -->
                <td style="background: #e20612; color: white; padding: 20px; border-radius: 15px; text-align: left; width: 33.33%; vertical-align: middle;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="width: 60px; vertical-align: middle; border: none;">
                                @if($tickExists)
                                    <img style="height: 50px; width: 50px;" src="{{ $tickBase64 }}">
                                @else
                                    <div style="width:50px; height:50px; background:white; border-radius:50%; color:#e20612; text-align:center; line-height:50px; font-size:30px;">S/</div>
                                @endif
                            </td>
                            <td style="vertical-align: middle; border: none;">
                                <div style="font-size: 18pt; font-weight: 800; margin-bottom: 2px;">{{ number_format($prestamo->saldo_restante + $totalMoras, 2) }}</div>
                                <div style="font-size: 9pt; font-weight: 800;">SOLES</div>
                                <div style="font-size: 9pt;">TOTAL DEUDA</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div style="width: 100%; margin-top: 30px;">
        <div style="text-align: center; width: 100%;">
            @php
                $footerPath = public_path('img/pdf/estado/foot.png');
                $footerExists = file_exists($footerPath);
                $footerBase64 = '';
                if($footerExists) {
                    $imageData = base64_encode(file_get_contents($footerPath));
                    $footerBase64 = 'data:image/png;base64,' . $imageData;
                }
            @endphp
            @if($footerExists)
                <img style="max-width: 100%; width: 100%; height: auto;" src="{{ $footerBase64 }}">
            @else
                <div style="width:100%; height:60px; background:#1e4a72; color:white; text-align:center; line-height:60px; font-weight:bold; font-size: 9pt;">FOOTER</div>
            @endif
        </div>
    </div>
</body>
</html>