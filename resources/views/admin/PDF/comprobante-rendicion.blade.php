<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Rendición - {{ $numero_rendicion }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 24px;
        }
        
        .header h2 {
            color: #6c757d;
            margin: 5px 0;
            font-size: 16px;
            font-weight: normal;
        }
        
        .info-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label, .info-value {
            display: table-cell;
            padding: 5px 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .info-label {
            font-weight: bold;
            width: 30%;
            background: #e9ecef;
        }
        
        .info-value {
            width: 70%;
        }
        
        .tipo-badge {
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            text-transform: uppercase;
        }

        .tipo-parcial { background: #ffc107; color: #000; }
        .tipo-completa { background: #28a745; }
        .tipo-cierre_diario { background: #007bff; }

        .badge-assigned {
            background: #007bff;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            display: inline-block;
            margin-right: 5px;
        }

        .badge-unassigned {
            background: #6c757d;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            display: inline-block;
        }
        
        .operations-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .operations-table th,
        .operations-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }
        
        .operations-table th {
            background: #007bff;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        
        .operations-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .amount {
            text-align: right;
            font-weight: bold;
        }
        
        .total-section {
            background: #007bff;
            color: white;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
        }
        
        .total-amount {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .signatures {
            margin-top: 50px;
            display: table;
            width: 100%;
        }
        
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 20px;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 10px;
        }
        
        .footer {
            position: fixed;
            bottom: 20px;
            right: 20px;
            font-size: 10px;
            color: #6c757d;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(0, 123, 255, 0.1);
            z-index: -1;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="watermark">RENDICIÓN</div>
    
    <div class="header">
        <h1>COMPROBANTE DE RENDICIÓN</h1>
        <h2>{{ strtoupper($numero_rendicion) }}</h2>
    </div>
    
    <div class="info-section">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Número de Rendición:</div>
                <div class="info-value">{{ $numero_rendicion }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Tipo de Rendición:</div>
                <div class="info-value">
                    <span class="tipo-badge tipo-{{ $tipo }}">
                        @switch($tipo)
                            @case('parcial')
                                RENDICIÓN PARCIAL
                                @break
                            @case('completa')
                                RENDICIÓN COMPLETA
                                @break
                            @case('cierre_diario')
                                CIERRE DIARIO
                                @break
                            @default
                                RENDICIÓN
                        @endswitch
                    </span>
                </div>
            </div>
            @if(isset($usuario))
            <div class="info-row">
                <div class="info-label">Usuario que Rinde:</div>
                <div class="info-value">{{ $usuario->codigo }} - {{ $usuario->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Zona / Sucursal:</div>
                <div class="info-value">
                    @php
                        // Obtener la dirección asociada al usuario con su sucursal
                        $direccion = $usuario->direcciones()->with('sucursal.zonas')->first();

                        // Extraer las zonas asociadas a esa sucursal
                        $zonas = $direccion && $direccion->sucursal ? $direccion->sucursal->zonas : collect();
                    @endphp

                    @if($zonas->count() > 0)
                        @foreach($zonas as $zona)
                            <span class="badge-assigned">{{ $zona->nombre }}</span>
                        @endforeach
                    @else
                        <span class="badge-unassigned">Sin asignar</span>
                    @endif
                </div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Fecha de Rendición:</div>
                <div class="info-value">{{ $fecha_rendicion }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Procesado por:</div>
                <div class="info-value">{{ $usuario_rendidor }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Total de Operaciones:</div>
                <div class="info-value">{{ $operaciones->count() }} operaciones</div>
            </div>
        </div>
    </div>
    
    <h3 style="color: #007bff; border-bottom: 1px solid #007bff; padding-bottom: 5px;">
        DETALLE DE OPERACIONES RENDIDAS
    </h3>
    
    <table class="operations-table">
        <thead>
            <tr>
                <th width="8%">ID</th>
                <th width="12%">Fecha</th>
                <th width="20%">Tipo</th>
                <th width="35%">Cliente/Observación</th>
                <th width="15%">Método Pago</th>
                <th width="10%">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($operaciones as $operacion)
            <tr>
                <td>{{ $operacion->id }}</td>
                <td>{{ \Carbon\Carbon::parse($operacion->fecha)->format('d/m/Y') }}</td>
                <td>{{ $operacion->tipo_operacion }}</td>
                <td>
                    @if($operacion->prestamo && $operacion->prestamo->cliente)
                        {{ $operacion->prestamo->cliente->persona->nombres ?? 'N/A' }} 
                        {{ $operacion->prestamo->cliente->persona->apellidos ?? '' }}
                    @else
                        {{ $operacion->comentario ?? 'Sin información' }}
                    @endif
                </td>
                <td>{{ $operacion->metodoDePago->metodo_pago ?? 'Efectivo' }}</td>
                <td class="amount">S/ {{ number_format($operacion->abono, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="total-section">
        <div style="font-size: 16px;">TOTAL RENDIDO</div>
        <div class="total-amount">S/ {{ number_format($total_rendido, 2) }}</div>
        <div style="font-size: 12px;">
            SON: {{ strtoupper(NumberFormatter::create('es', NumberFormatter::SPELLOUT)->format($total_rendido)) }} SOLES
        </div>
    </div>
    
    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <strong>IMPORTANTE:</strong> Este comprobante certifica que las operaciones detalladas han sido rendidas en efectivo. 
        El usuario queda liberado de la responsabilidad del dinero aquí especificado.
    </div>
    
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">
                <strong>USUARIO QUE RINDE</strong><br>
                @if(isset($usuario))
                    {{ $usuario->codigo }} - {{ $usuario->name }}
                @else
                    CIERRE DIARIO
                @endif
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <strong>SUPERVISOR QUE RECIBE</strong><br>
                {{ $usuario_rendidor }}
            </div>
        </div>
    </div>
    
    <div class="footer">
        Generado automáticamente el {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>