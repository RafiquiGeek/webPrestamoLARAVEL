<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Compromisos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
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
        }
        th {
            background-color: #f2f2f2;
            text-align: left;
            padding: 8px;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
        }
        .pendiente {
            color: #fd7e14;
        }
        .completado {
            color: #198754;
        }
        .cancelado {
            color: #dc3545;
        }
        .vencido {
            color: #a71d2a;
            font-weight: bold;
        }
        .hoy {
            color: #e0a800;
            font-weight: bold;
        }
        .por-vencer {
            color: #d95d00;
            font-weight: bold;
        }
        .en-plazo {
            color: #1a8754;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Listado de Compromisos</h1>
        <p>Generado el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Monto</th>
                <th>Estado</th>
                <th>Cartera</th>
            </tr>
        </thead>
        <tbody>
            @foreach($compromisos as $compromiso)
                <tr>
                    <td>
                        {{ $compromiso->prestamo->cliente->persona->nombres ?? 'N/A' }} 
                        {{ $compromiso->prestamo->cliente->persona->ape_pat ?? '' }} 
                        {{ $compromiso->prestamo->cliente->persona->ape_mat ?? '' }}
                    </td>
                    <td class="{{ $compromiso->vencimiento_status }}">
                        {{ \Carbon\Carbon::parse($compromiso->fecha_compromiso_pago)->format('d/m/Y') }}
                    </td>
                    <td>{{ \Carbon\Carbon::parse($compromiso->hora)->format('H:i') }}</td>
                    <td>S/ {{ number_format($compromiso->monto, 2) }}</td>
                    <td>
                        @if($compromiso->estado == \App\Models\Compromiso::ESTADO_PENDIENTE)
                            <span class="pendiente">Pendiente</span>
                        @elseif($compromiso->estado == \App\Models\Compromiso::ESTADO_PAGADO)
                            <span class="completado">Pagado</span>
                        @elseif($compromiso->estado == \App\Models\Compromiso::ESTADO_POSTERGADO)
                            <span class="cancelado">Postergado</span>
                        @endif
                    </td>
                    <td>
                        @if($compromiso->prestamo->carteraJcc)
                            <strong>JCC:</strong> {{ $compromiso->prestamo->carteraJcc->jcc->persona->nombres ?? 'N/A' }} {{ $compromiso->prestamo->carteraJcc->jcc->persona->ape_pat ?? '' }}<br>
                        @endif
                        
                        @if($compromiso->prestamo->carteraAsesor)
                            <strong>Asesor:</strong> {{ $compromiso->prestamo->carteraAsesor->asesor->persona->nombres ?? 'N/A' }} {{ $compromiso->prestamo->carteraAsesor->asesor->persona->ape_pat ?? '' }}<br>
                        @endif
                        
                        @if($compromiso->prestamo->carteraAnalista)
                            <strong>Analista:</strong> {{ $compromiso->prestamo->carteraAnalista->analista->persona->nombres ?? 'N/A' }} {{ $compromiso->prestamo->carteraAnalista->analista->persona->ape_pat ?? '' }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        <p>© {{ date('Y') }} - Sistema de Gestión de Compromisos</p>
    </div>
</body>
</html>