<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estado de Cuenta</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .container {
            width: 100%;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }
        
        .header h1 {
            color: #007bff;
            margin: 5px 0;
            font-size: 22px;
        }
        
        .header p {
            margin: 5px 0;
            color: #666;
        }
        
        .info-section {
            margin-bottom: 20px;
        }
        
        .info-section h2 {
            color: #007bff;
            font-size: 18px;
            margin: 0 0 10px 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .info-item {
            margin-bottom: 5px;
        }
        
        .info-item strong {
            color: #555;
            display: inline-block;
            width: 40%;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: bold;
            text-align: left;
            padding: 8px;
            font-size: 13px;
            border-bottom: 2px solid #ddd;
        }
        
        table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .summary {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        .summary h3 {
            color: #007bff;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        
        .summary-item {
            padding: 10px;
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .summary-item strong {
            display: block;
            margin-bottom: 5px;
            color: #666;
            font-size: 12px;
        }
        
        .summary-item span {
            font-size: 16px;
            color: #007bff;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            text-align: center;
        }
        
        .badge-success {
            background-color: #28a745;
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
        
        .badge-secondary {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ESTADO DE CUENTA</h1>
            <p>Fecha de emisión: {{ $fecha }}</p>
            <p>No. Referencia: EC-{{ str_pad($prestamo->id, 6, '0', STR_PAD_LEFT) }}</p>
        </div>
        
        <div class="info-section">
            <h2>Información del Cliente</h2>
            <div class="info-grid">
                <div>
                    <div class="info-item">
                        <strong>Nombre:</strong> {{ $prestamo->cliente->persona->nombres }} {{ $prestamo->cliente->persona->ape_pat }} {{ $prestamo->cliente->persona->ape_mat }}
                    </div>
                    <div class="info-item">
                        <strong>DNI:</strong> {{ $prestamo->cliente->persona->dni ?? 'No disponible' }}
                    </div>
                    <div class="info-item">
                        <strong>Teléfono:</strong> {{ $prestamo->cliente->persona->telefono ?? 'No disponible' }}
                    </div>
                </div>
                <div>
                    <div class="info-item">
                        <strong>Dirección:</strong> {{ $prestamo->cliente->persona->direccion->direccion ?? 'No disponible' }}
                    </div>
                    <div class="info-item">
                        <strong>Email:</strong> {{ $prestamo->cliente->persona->email ?? 'No disponible' }}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="info-section">
            <h2>Detalles del Préstamo</h2>
            <div class="info-grid">
                <div>
                    <div class="info-item">
                        <strong>ID Préstamo:</strong> {{ $prestamo->id }}
                    </div>
                    <div class="info-item">
                        <strong>Fecha de Inicio:</strong> {{ \Carbon\Carbon::parse($prestamo->fecha_inicio)->format('d/m/Y') }}
                    </div>
                    <div class="info-item">
                        <strong>Estado:</strong> <span class="badge badge-{{ getEstadoBadge($prestamo->estado) }}">{{ $prestamo->estado }}</span>
                    </div>
                </div>
                <div>
                    <div class="info-item">
                        <strong>Monto Solicitado:</strong> S/ {{ number_format($prestamo->cantidad_solicitada, 2) }}
                    </div>
                    <div class="info-item">
                        <strong>Saldo Pendiente:</strong> S/ {{ number_format($prestamo->saldo_restante, 2) }}
                    </div>
                    <div class="info-item">
                        <strong>Número de Cuotas:</strong> {{ $prestamo->cuotas->count() }}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="info-section">
            <h2>Historial de Cuotas</h2>
            <table>
                <thead>
                    <tr>
                        <th>No. Cuota</th>
                        <th>Fecha Pago</th>
                        <th>Monto</th>
                        <th>Monto Pagado</th>
                        <th>Fecha Último Pago</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prestamo->cuotas as $cuota)
                    <tr>
                        <td>{{ $cuota->numero }}</td>
                        <td>{{ \Carbon\Carbon::parse($cuota->fecha_pago)->format('d/m/Y') }}</td>
                        <td>S/ {{ number_format($cuota->monto, 2) }}</td>
                        <td>S/ {{ number_format($cuota->monto_pagado ?? 0, 2) }}</td>
                        <td>{{ $cuota->operaciones->count() > 0 ? \Carbon\Carbon::parse($cuota->operaciones->sortByDesc('fecha')->first()->fecha)->format('d/m/Y') : '-' }}</td>
                        <td>
                            <span class="badge badge-{{ getCuotaBadge($cuota->estado) }}">
                                {{ getEstadoCuota($cuota->estado) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="info-section">
            <h2>Historial de Pagos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Concepto</th>
                        <th>Método</th>
                        <th>Monto</th>
                        <th>Realizado Por</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prestamo->operaciones->sortByDesc('fecha') as $operacion)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($operacion->fecha)->format('d/m/Y') }}</td>
                        <td>{{ $operacion->tipo_operacion }}</td>
                        <td>{{ $operacion->metodoDePago->metodo_pago ?? 'N/A' }}</td>
                        <td>S/ {{ number_format($operacion->abono, 2) }}</td>
                        <td>{{ $operacion->user->codigo ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="summary">
            <h3>Resumen</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <strong>Total Préstamo</strong>
                    <span>S/ {{ number_format($prestamo->cantidad_solicitada, 2) }}</span>
                </div>
                <div class="summary-item">
                    <strong>Total Pagado</strong>
                    <span>S/ {{ number_format($prestamo->operaciones->sum('abono'), 2) }}</span>
                </div>
                <div class="summary-item">
                    <strong>Saldo Pendiente</strong>
                    <span>S/ {{ number_format($prestamo->saldo_restante, 2) }}</span>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Este estado de cuenta es un documento informativo y no constituye un valor oficial.</p>
            <p>Generado el {{ $fecha }} - Sistema de Gestión de Préstamos</p>
        </div>
    </div>
</body>
</html>

@php
function getEstadoBadge($estado) {
    switch ($estado) {
        case 'Vigente': return 'success';
        case 'Moroso': return 'danger';
        case 'Nueva Solicitud': return 'info';
        case 'Por Desembolsar': return 'primary';
        case 'Pagado': return 'secondary';
        case 'Cancelado': return 'dark';
        default: return 'light';
    }
}

function getCuotaBadge($estado) {
    switch ($estado) {
        case 2: return 'success';
        case 1: return 'warning';
        case 0: return 'danger';
        default: return 'secondary';
    }
}

function getEstadoCuota($estado) {
    switch ($estado) {
        case 0: return 'Pendiente';
        case 1: return 'Parcial';
        case 2: return 'Pagado';
        default: return 'Desconocido';
    }
}
@endphp