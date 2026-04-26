<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Carteras</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            color: #333;
        }
        
        .header {
            margin-bottom: 20px;
        }
        
        .header-title {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .info-col {
            display: table-cell;
            width: 33.33%;
        }
        
        .info-label {
            font-weight: bold;
            margin-right: 5px;
        }
        
        .separator {
            border-top: 1px solid #ddd;
            margin: 15px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table th, table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 9px;
        }
        
        table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .text-center {
            text-align: center;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            color: white;
        }
        
        .badge-success {
            background-color: #28a745;
        }
        
        .badge-danger {
            background-color: #dc3545;
        }
        
        .badge-info {
            background-color: #17a2b8;
        }
        
        .badge-primary {
            background-color: #007bff;
        }
        
        .badge-secondary {
            background-color: #6c757d;
        }
        
        .badge-dark {
            background-color: #343a40;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #333;
        }
        
        .page-footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            font-size: 10px;
            color: #6c757d;
            text-align: center;
        }
        
        .page-number:after {
            content: counter(page);
        }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($filtros['jcc_id']))
            <?php $jcc = $jccs->where('id', $filtros['jcc_id'])->first(); ?>
            <div class="header-title">Cartera de: JCC {{ $jcc->codigo ?: $jcc->name }}</div>
            <div>{{ optional(optional($jcc)->persona)->nombres }} {{ optional(optional($jcc)->persona)->ape_pat }} {{ optional(optional($jcc)->persona)->ape_mat }}</div>
        @elseif(!empty($filtros['asesor_id']))
            <?php $asesor = $asesores->where('id', $filtros['asesor_id'])->first(); ?>
            <div class="header-title">Cartera de: Asesor {{ $asesor->codigo ?: $asesor->name }}</div>
            <div>{{ optional(optional($asesor)->persona)->nombres }} {{ optional(optional($asesor)->persona)->ape_pat }} {{ optional(optional($asesor)->persona)->ape_mat }}</div>
        @elseif(!empty($filtros['analista_id']))
            <?php $analista = $analistas->where('id', $filtros['analista_id'])->first(); ?>
            <div class="header-title">Cartera de: Analista {{ $analista->codigo ?: $analista->name }}</div>
            <div>{{ optional(optional($analista)->persona)->nombres }} {{ optional(optional($analista)->persona)->ape_pat }} {{ optional(optional($analista)->persona)->ape_mat }}</div>
        @else
            <div class="header-title">Reporte General de Carteras</div>
        @endif
    </div>
    
    <div class="info-row">
        <div class="info-col">
            <span class="info-label">Sucursal:</span>
            @if(!empty($filtros['sucursal_id']))
                {{ $sucursales->where('id', $filtros['sucursal_id'])->first()->sucursal ?? 'No especificada' }}
            @else
                Todas las sucursales
            @endif
        </div>
        <div class="info-col">
            <span class="info-label">Zona:</span>
            @if(!empty($filtros['zona_id']))
                {{ $zonas->where('id', $filtros['zona_id'])->first()->nombre ?? 'No especificada' }}
            @else
                Todas las zonas
            @endif
        </div>
        <div class="info-col">
            <span class="info-label">Fecha:</span> {{ $fecha }}
        </div>
    </div>
    
    <div class="separator"></div>
    
    <table>
        <thead>
            <tr>
                <th class="text-center">ZONA</th>
                <th class="text-center">SUCURSAL</th>
                <th>DIRECCIÓN</th>
                <th>CLIENTE</th>
                <th>ASESOR</th>
                <th>JCC</th>
                <th>ANALISTA</th>
                <th class="text-center">ESTADO</th>
                <th class="text-center">ÚLTIMA CUOTA</th>
                <th class="text-center">C. VENCIDAS</th>
            </tr>
        </thead>
        <tbody>
            @forelse($carteras as $cartera)
                <tr>
                    <td class="text-center">{{ $cartera['zona'] }}</td>
                    <td class="text-center">{{ $cartera['sucursal'] }}</td>
                    <td>{{ $cartera['direccion'] }} </td>
                    <td>{{ $cartera['nombre_cliente'] }}</td>
                    <td>{{ $cartera['codigo_asesor'] ?: $cartera['nombre_asesor'] }}</td>
                    <td>{{ $cartera['codigo_jcc'] ?: $cartera['nombre_jcc'] }}</td>
                    <td>{{ $cartera['codigo_analista'] ?: $cartera['nombre_analista'] }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $helpers['estadoBadge']($cartera['estado_prestamo']) }}">
                            {{ $cartera['estado_prestamo'] }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $helpers['cuotaBadge']($cartera['estado_ultima_cuota']) }}">
                            {{ $cartera['estado_ultima_cuota'] }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $cartera['cuotas_vencidas'] > 0 ? 'danger' : 'success' }}">
                            {{ $cartera['cuotas_vencidas'] }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">
                        No se encontraron carteras con los filtros aplicados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="page-footer">
        <span>Sistema de Gestión de Carteras &copy; {{ date('Y') }}</span>
        <span style="float: right;">Página <span class="page-number"></span></span>
    </div>
</body>
</html>