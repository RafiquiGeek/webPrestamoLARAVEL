<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Deudas y Moras</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .header p {
            margin-top: 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
        }
        th {
            background-color: #f2f2f2;
            text-align: left;
            padding: 5px;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            font-size: 9px;
        }
        td {
            padding: 5px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        .footer-total {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-bold {
            font-weight: bold;
        }
        .text-mora {
            color: #a71d2a;
            font-weight: bold;
        }
        .badge {
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 8px;
            color: white;
            display: inline-block;
            margin-bottom: 2px;
        }
        .badge-secondary {
            background-color: #6c757d;
        }
        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-danger {
            background-color: #dc3545;
        }
        .badge-info {
            background-color: #17a2b8;
        }
        .badge-dark {
            background-color: #343a40;
        }
        .badge-light {
            background-color: #f8f9fa;
            color: #212529;
            border: 1px solid #ddd;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Deudas y Moras</h1>
        <p>Generado el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Préstamo</th>
                <th>Cuota</th>
                <th>Vencimiento</th>
                <th>Días Mora</th>
                <th>Monto Cuota</th>
                <th>Monto Mora</th>
                <th>Total</th>
                <th>Gestión/Compromiso</th>
                <th>Cartera</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cuotas as $cuota)
                <tr>
                    <!-- Cliente -->
                    <td>
                        <strong>
                            {{ $cuota->prestamo->cliente->persona->nombres ?? 'N/A' }} 
                            {{ $cuota->prestamo->cliente->persona->ape_pat ?? '' }} 
                            {{ $cuota->prestamo->cliente->persona->ape_mat ?? '' }}
                        </strong><br>
                        <small>{{ $cuota->prestamo->cliente->codigo ?? 'Sin código' }}</small>
                    </td>
                    
                    <!-- Préstamo -->
                    <td>
                        <span class="badge badge-light">ID: {{ $cuota->prestamo->id }}</span><br>
                        <small>S/ {{ number_format($cuota->prestamo->cantidad_solicitada, 2) }}</small><br>
                        <small>{{ \Carbon\Carbon::parse($cuota->prestamo->fecha_atencion)->format('d/m/Y') }}</small>
                    </td>
                    
                    <!-- Cuota -->
                    <td>
                        <span class="badge badge-dark">Nro: {{ $cuota->numero }}</span><br>
                        @if($cuota->estado == \App\Enums\CuotaEstado::PENDIENTE)
                            <span class="badge badge-secondary">Pendiente</span>
                        @elseif($cuota->estado == \App\Enums\CuotaEstado::PARCIAL)
                            <span class="badge badge-warning">Parcial</span>
                        @endif
                    </td>
                    
                    <!-- Fecha Vencimiento -->
                    <td>
                        <span class="text-bold text-mora">
                            {{ \Carbon\Carbon::parse($cuota->fecha_pago)->format('d/m/Y') }}
                        </span>
                    </td>
                    
                    <!-- Días Mora -->
                    <td class="text-center">
                        @php
                            $diasMora = $cuota->moras->where('estado', \App\Enums\MoraCuotaEstado::PENDIENTE)->max('dias_mora');
                        @endphp
                        <span class="badge badge-danger">{{ $diasMora }} días</span>
                    </td>
                    
                    <!-- Monto Cuota -->
                    <td class="text-right">
                        S/ {{ number_format($cuota->monto, 2) }}
                    </td>
                    
                    <!-- Monto Mora -->
                    <td class="text-right">
                        @php
                            $montoMora = $cuota->moras->where('estado', \App\Enums\MoraCuotaEstado::PENDIENTE)->sum('monto');
                        @endphp
                        <span class="text-mora">S/ {{ number_format($montoMora, 2) }}</span>
                    </td>
                    
                    <!-- Total Deuda -->
                    <td class="text-right text-bold">
                        S/ {{ number_format($cuota->monto + $montoMora, 2) }}
                    </td>
                    
                    <!-- Gestión y Compromiso -->
                    <td>
                        @php
                            $totalGestiones = $cuota->prestamo->gestiones->count();
                            $ultimaGestion = $cuota->prestamo->gestiones->sortByDesc('fecha')->first();
                            
                            $compromisos = $cuota->prestamo->compromisos->where('estado', '!=', \App\Models\Compromiso::ESTADO_POSTERGADO);
                            $totalCompromisos = $compromisos->count();
                            $ultimoCompromiso = $compromisos->sortByDesc('fecha_compromiso_pago')->first();
                        @endphp
                        
                        @if($totalGestiones > 0)
                            <span class="badge badge-info">{{ $totalGestiones }} gestiones</span><br>
                            @if($ultimaGestion)
                                <small>Última: {{ \Carbon\Carbon::parse($ultimaGestion->fecha)->format('d/m/Y') }}</small>
                            @endif
                        @else
                            <span class="badge badge-secondary">Sin gestiones</span><br>
                        @endif
                        
                        @if($totalCompromisos > 0)
                            <span class="badge badge-info">{{ $totalCompromisos }} compromisos</span><br>
                            @if($ultimoCompromiso)
                                <small>Próximo: {{ \Carbon\Carbon::parse($ultimoCompromiso->fecha_compromiso_pago)->format('d/m/Y') }}</small><br>
                                <small>S/ {{ number_format($ultimoCompromiso->monto, 2) }}</small>
                            @endif
                        @else
                            <span class="badge badge-secondary">Sin compromisos</span>
                        @endif
                    </td>
                    
                    <!-- Cartera -->
                    <td>
                        @php
                            $carteraJccActiva = $cuota->prestamo->carterasJcc->where('estado', 1)->first();
                            $carteraAsesorActiva = $cuota->prestamo->carterasAsesor->where('estado', 1)->first();
                            $carteraAnalistaActiva = $cuota->prestamo->carterasAnalista->where('estado', 1)->first();
                        @endphp
                        
                        @if($carteraJccActiva)
                            <span class="badge badge-secondary">JCC</span>
                            <small>
                                {{ $carteraJccActiva->jcc->persona->nombres ?? 'N/A' }} 
                                {{ $carteraJccActiva->jcc->persona->ape_pat ?? '' }}
                            </small><br>
                        @endif
                        
                        @if($carteraAsesorActiva)
                            <span class="badge badge-info">Asesor</span>
                            <small>
                                {{ $carteraAsesorActiva->asesor->persona->nombres ?? 'N/A' }} 
                                {{ $carteraAsesorActiva->asesor->persona->ape_pat ?? '' }}
                            </small><br>
                        @endif
                        
                        @if($carteraAnalistaActiva)
                            <span class="badge badge-primary">Analista</span>
                            <small>
                                {{ $carteraAnalistaActiva->analista->persona->nombres ?? 'N/A' }} 
                                {{ $carteraAnalistaActiva->analista->persona->ape_pat ?? '' }}
                            </small>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="footer-total">
                <td colspan="5" class="text-right">TOTALES:</td>
                <td class="text-right">S/ {{ number_format($totalMonto, 2) }}</td>
                <td class="text-right">S/ {{ number_format($totalMora, 2) }}</td>
                <td class="text-right">S/ {{ number_format($totalDeuda, 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer">
        <p>© {{ date('Y') }} - Sistema de Gestión de Cobranzas</p>
        <p>Total de cuotas: {{ $cuotas->count() }} - Total deuda: S/ {{ number_format($totalDeuda, 2) }}</p>
    </div>
</body>
</html>