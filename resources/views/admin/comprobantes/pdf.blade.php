<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $comprobante->tipo_comprobante == '01' ? 'Factura' : 'Boleta' }} {{ $comprobante->serie }}-{{ str_pad($comprobante->numero, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
            size: 80mm auto; /* Auto height para ajustar al contenido */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 8px;
            line-height: 1.3;
            color: #222;
            width: 76mm;
            height: 180mm;
            padding: 3mm 2mm;
            margin: 0 auto;
            overflow: visible;
        }

        .container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0;
            overflow: visible;
            page-break-inside: avoid;
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }

        /* Header */
        .company-name {
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 2px;
            word-wrap: break-word;
            letter-spacing: 0.3px;
        }

        .company-ruc {
            font-size: 8.5px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 1px;
        }

        .company-address {
            font-size: 7.5px;
            text-align: center;
            margin-bottom: 1px;
            word-wrap: break-word;
            color: #444;
        }

        .company-web {
            font-size: 7.5px;
            text-align: center;
            margin-bottom: 2px;
            color: #444;
        }

        .separator {
            border-top: 1px dotted #999;
            margin: 2.5mm 0;
            width: 100%;
        }

        /* Document type */
        .document-type {
            font-size: 9.5px;
            font-weight: bold;
            text-align: center;
            margin: 2px 0 1px 0;
            word-wrap: break-word;
        }

        .document-number {
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }

        /* Client info */
        .info-row {
            font-size: 7.5px;
            margin-bottom: 1.5px;
            display: flex;
            width: 100%;
            line-height: 1.4;
        }

        .info-label {
            flex: 0 0 22mm;
            font-weight: 600;
            text-align: left;
            color: #333;
        }

        .info-value {
            flex: 1;
            text-align: left;
            word-wrap: break-word;
            color: #222;
        }

        /* Items table - CRÍTICO: Tabla ajustada al ancho */
        .items-table {
            width: 100%;
            font-size: 7px;
            border-collapse: collapse;
            margin: 2mm 0;
            table-layout: fixed;
            word-wrap: break-word;
            background: #fff;
        }

        .items-table th {
            padding: 2px 1px;
            border: 1px solid #ddd;
            text-align: center;
            font-size: 6.5px;
            line-height: 1.2;
            background: #f5f5f5;
            font-weight: 600;
            color: #333;
        }

        .items-table td {
            padding: 2.5px 2px;
            border: 1px solid #e5e5e5;
            text-align: center;
            font-size: 6.5px;
            line-height: 1.3;
            overflow: hidden;
            word-break: break-word;
            color: #222;
        }

        /* Ajustes específicos de columnas para evitar desborde */
        .items-table th:nth-child(1),
        .items-table td:nth-child(1) { width: 38%; text-align: left; padding-left: 2px; } /* DESCRIPCIÓN */
        .items-table th:nth-child(2),
        .items-table td:nth-child(2) { width: 12%; } /* CANT. */
        .items-table th:nth-child(3),
        .items-table td:nth-child(3) { width: 18%; } /* V. UNIT. */
        .items-table th:nth-child(4),
        .items-table td:nth-child(4) { width: 14%; } /* IGV */
        .items-table th:nth-child(5),
        .items-table td:nth-child(5) { width: 18%; font-weight: 600; } /* TOTAL */

        /* Totals */
        .total-row {
            font-size: 7.5px;
            margin: 1.5px 0;
            display: flex;
            width: 100%;
            line-height: 1.4;
        }

        .total-label {
            flex: 1;
            text-align: right;
            padding-right: 5px;
            color: #444;
            font-weight: 500;
        }

        .total-value {
            flex: 0 0 20mm;
            text-align: right;
            font-weight: 600;
            color: #222;
        }

        .total-final {
            background-color: #2c3e50;
            color: white;
            padding: 2.5px 3px;
            margin: 2.5mm 0 2mm 0;
            border-radius: 1px;
        }

        .total-final .total-label,
        .total-final .total-value {
            color: white;
            font-size: 8px;
            font-weight: 600;
        }

        /* Amount in words */
        .amount-words {
            font-size: 7px;
            text-align: left;
            margin: 1.5px 0;
            line-height: 1.4;
            word-wrap: break-word;
            color: #333;
        }

        .amount-words strong {
            font-weight: 600;
            color: #222;
        }

        /* Footer */
        .footer-info {
            font-size: 6.5px;
            text-align: center;
            margin-top: 2.5mm;
            line-height: 1.3;
            word-wrap: break-word;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
    {{-- Header --}}
    <div class="company-name">
        @if(isset($empresa['razon_social']))
            {{ strtoupper($empresa['razon_social']) }}
        @else
            GRUPO SANTIAGO PERU S.A.C.
        @endif
    </div>

    <div class="company-ruc">
        RUC: @if(isset($empresa['ruc'])){{ $empresa['ruc'] }}@else 20611373181 @endif
    </div>

    <div class="company-address">
        @if(isset($empresa['direccion']))
            {{ $empresa['direccion'] }}
        @else
            Cal. Sétima Nro. 215
        @endif
    </div>

    <div class="company-web">
        @if(isset($empresa['web']))
            {{ $empresa['web'] }}
        @else
            www.gruposantiago.pe
        @endif
    </div>

    <div class="separator"></div>

    {{-- Document Type --}}
    <div class="document-type">
        {{ $comprobante->tipo_comprobante == '01' ? 'FACTURA ELECTRÓNICA' : 'BOLETA ELECTRÓNICA' }}
    </div>

    <div class="document-number">
        {{ $comprobante->serie }}-{{ str_pad($comprobante->numero, 6, '0', STR_PAD_LEFT) }}
    </div>

    <div class="separator"></div>

    {{-- Client Information --}}
    <div class="info-row">
        <span class="info-label">Fecha</span>
        <span class="info-value">: {{ $comprobante->fecha_emision->format('d/m/Y') }} &nbsp;&nbsp;HORA: {{ $comprobante->fecha_emision->format('H:i:s') }}</span>
    </div>

    @php
        $persona = $comprobante->cliente->persona ?? null;
        $tipoDocLabel = $persona && strlen($persona->documento ?? '') === 11 ? 'RUC' : 'DNI';
        $clienteNombre = $comprobante->cliente->nombre_completo ?? ($persona ? trim(($persona->nombres ?? '') . ' ' . ($persona->ape_pat ?? '') . ' ' . ($persona->ape_mat ?? '')) : '');
        $clienteDocumento = $persona->documento ?? '';
    @endphp

    @if($persona)
    <div class="info-row">
        <span class="info-label">Cliente</span>
        <span class="info-value">: {{ $clienteNombre }}</span>
    </div>

    <div class="info-row">
        <span class="info-label">{{ $tipoDocLabel }}</span>
        <span class="info-value">: {{ $clienteDocumento }}</span>
    </div>
    @endif

    @if($comprobante->prestamo)
    <div class="info-row">
        <span class="info-label">Producto</span>
        <span class="info-value">: Crédito Personal</span>
    </div>

    <div class="info-row">
        <span class="info-label">Préstamo</span>
        <span class="info-value">: {{ $comprobante->prestamo->numero_prestamo }}</span>
    </div>
    @endif

    <div class="info-row">
        <span class="info-label">Moneda</span>
        <span class="info-value">: {{ $comprobante->moneda ?? 'PEN' }}</span>
    </div>

    <div class="separator"></div>

{{-- Items Table --}}
@php
    // Obtener datos desde la cuota relacionada (si existe)
    // IMPORTANTE: Capital e Interés son EXONERADOS (no pagan IGV)
    // Solo la Comisión es GRAVADA (paga IGV 18%)

    $capital = 0;
    $interes = 0;
    $comision = 0;
    $seguro = 0;
    $igv = 0;
    $montoTotal = 0;
    $numeroCuota = 0;

    if ($comprobante->cuota) {
        // Datos directamente desde la tabla cuotas - campos correctos
        $cuota = $comprobante->cuota;
        $numeroCuota = $cuota->numero;
        $capital = $cuota->pago_capital ?? 0;     // Campo correcto: pago_capital
        $interes = $cuota->interes ?? 0;          // Interés es EXONERADO (no paga IGV)
        $comision = $cuota->comision ?? 0;        // Comisión es GRAVADA (paga IGV)
        $seguro = $cuota->gas ?? 0;               // Campo correcto: gas (seguro es EXONERADO)
        $igv = $cuota->igv ?? 0;                  // IGV solo sobre la comisión
        $montoTotal = $cuota->monto ?? 0;         // Campo correcto: monto
    } else {
        // Fallback: usar total del comprobante
        $montoTotal = $comprobante->total ?? 0;
    }

    // Calcular totales según ley tributaria peruana:
    // - Capital: EXONERADO (Ley IGV Art. 2 - operaciones financieras)
    // - Interés: EXONERADO (Ley IGV Art. 2 - intereses de préstamos)
    // - Seguro: EXONERADO
    // - Comisión: GRAVADA con IGV 18%

    $totalExonerado = $capital + $interes + $seguro;
    $totalGravado = $comision;

    // IGV solo sobre la comisión (ya viene calculado desde la cuota)
    $totalIgv = $igv;
@endphp

    <table class="items-table">
        <thead>
            <tr>
                <th>DESCRIPCIÓN</th>
                <th>CANT.</th>
                <th>V. UNIT.</th>
                <th>IGV</th>
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @if($capital > 0)
            <tr>
                <td style="text-align: left; padding-left: 3px;">Capital - Cuota {{ $numeroCuota }}</td>
                <td>1</td>
                <td>{{ number_format($capital, 2) }}</td>
                <td style="color: #888; font-style: italic; font-size: 6px;">Exon.</td>
                <td>{{ number_format($capital, 2) }}</td>
            </tr>
            @endif

            @if($interes > 0)
            <tr>
                <td style="text-align: left; padding-left: 3px;">Interés - Cuota {{ $numeroCuota }}</td>
                <td>1</td>
                <td>{{ number_format($interes, 2) }}</td>
                <td style="color: #888; font-style: italic; font-size: 6px;">Exon.</td>
                <td>{{ number_format($interes, 2) }}</td>
            </tr>
            @endif

            @if($comision > 0)
            <tr>
                <td style="text-align: left; padding-left: 3px;">Comisión - Cuota {{ $numeroCuota }}</td>
                <td>1</td>
                <td>{{ number_format($comision, 2) }}</td>
                <td>{{ number_format($igv, 2) }}</td>
                <td>{{ number_format($comision, 2) }}</td>
            </tr>
            @endif

            @if($seguro > 0)
            <tr>
                <td style="text-align: left; padding-left: 3px;">Seguro - Cuota {{ $numeroCuota }}</td>
                <td>1</td>
                <td>{{ number_format($seguro, 2) }}</td>
                <td style="color: #888; font-style: italic; font-size: 6px;">Exon.</td>
                <td>{{ number_format($seguro, 2) }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="separator"></div>

    {{-- Totals --}}
    @if($totalExonerado > 0)
    <div class="total-row">
        <span class="total-label">OP. Exoneradas</span>
        <span class="total-value">(S/) {{ number_format($totalExonerado, 2) }}</span>
    </div>
    @endif

    @if($totalGravado > 0)
    <div class="total-row">
        <span class="total-label">OP. Gravadas (Base)</span>
        <span class="total-value">(S/) {{ number_format($totalGravado, 2) }}</span>
    </div>
    @endif

    <div class="total-row">
        <span class="total-label">IGV (18%)</span>
        <span class="total-value">(S/) {{ number_format($totalIgv, 2) }}</span>
    </div>

    <div class="total-final">
        <div class="total-row">
            <span class="total-label">TOTAL</span>
            <span class="total-value">(S/) {{ number_format($montoTotal, 2) }}</span>
        </div>
    </div>

    <div class="separator"></div>

    {{-- Amount in words --}}
    <div class="amount-words">
        <strong>SON :</strong> {{ strtoupper(NumeroALetras($montoTotal)) }}
    </div>

    <div class="amount-words">
        <strong>FORMA DE PAGO :</strong> TRANSFERENCIA
    </div>

    <div class="amount-words">
        <strong>COND. PAGO :</strong> CONTADO
    </div>

    <div class="separator"></div>

    {{-- Footer --}}
    <div class="footer-info">
        Representación impresa de la {{ $comprobante->tipo_comprobante == '01' ? 'Factura' : 'Boleta' }} Electrónica<br>
        @if($comprobante->hash)
        HASH: {{ substr($comprobante->hash, 0, 40) }}<br>
        @endif
        Este documento es válido sin firma ni sello físico.<br>
        Generado: {{ now()->format('d/m/Y H:i:s') }}
    </div>
    </div>
</body>
</html>
