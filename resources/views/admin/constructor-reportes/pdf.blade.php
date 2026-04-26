<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $metadatos['titulo'] }}</title>
    <style>
        /* Reset y estilos base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        /* Header del reporte */
        .report-header {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 3px solid #007bff;
            margin-bottom: 20px;
        }

        .report-title {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }

        .report-subtitle {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 10px;
        }

        .report-meta {
            display: table;
            width: 100%;
            font-size: 8px;
            color: #6c757d;
        }

        .report-meta .meta-left,
        .report-meta .meta-right {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }

        .report-meta .meta-right {
            text-align: right;
        }

        /* Estadísticas del reporte */
        .report-stats {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .stats-grid {
            display: table;
            width: 100%;
        }

        .stat-item {
            display: table-cell;
            text-align: center;
            padding: 5px;
        }

        .stat-number {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
            display: block;
        }

        .stat-label {
            font-size: 8px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Filtros aplicados */
        .filters-section {
            margin-bottom: 20px;
        }

        .filters-title {
            font-size: 12px;
            font-weight: bold;
            color: #495057;
            margin-bottom: 8px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 3px;
        }

        .filter-item {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 3px;
            padding: 5px 8px;
            margin-bottom: 5px;
            font-size: 8px;
        }

        .filter-field {
            font-weight: bold;
            color: #856404;
        }

        /* Tabla de datos */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 8px;
        }

        .data-table th {
            background: #007bff;
            color: white;
            padding: 8px 6px;
            text-align: left;
            border: 1px solid #0056b3;
            font-weight: bold;
            font-size: 8px;
        }

        .data-table td {
            padding: 6px;
            border: 1px solid #dee2e6;
            vertical-align: top;
        }

        .data-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .data-table tbody tr:hover {
            background: #e3f2fd;
        }

        /* Tipos de datos con colores */
        .data-number {
            text-align: right;
            color: #28a745;
            font-weight: 500;
        }

        .data-date {
            color: #fd7e14;
            font-size: 7px;
        }

        .data-text {
            color: #495057;
        }

        .data-null {
            color: #6c757d;
            font-style: italic;
            text-align: center;
        }

        /* Resumen y totales */
        .summary-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }

        .summary-title {
            font-size: 12px;
            font-weight: bold;
            color: #495057;
            margin-bottom: 10px;
        }

        .summary-grid {
            display: table;
            width: 100%;
        }

        .summary-item {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 5px;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #007bff;
            display: block;
        }

        .summary-label {
            font-size: 8px;
            color: #6c757d;
        }

        /* Footer */
        .report-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            padding: 8px 15px;
            font-size: 7px;
            color: #6c757d;
        }

        .footer-left {
            float: left;
        }

        .footer-right {
            float: right;
        }

        /* Paginación */
        .page-number {
            position: fixed;
            bottom: 10px;
            right: 15px;
            font-size: 8px;
            color: #6c757d;
        }

        .page-number:before {
            content: "Página " counter(page) " de " counter(pages);
        }

        /* Saltos de página */
        .page-break {
            page-break-before: always;
        }

        /* Responsive adjustments */
        @media print {
            body {
                margin: 0;
            }
            
            .no-print {
                display: none !important;
            }
        }

        /* Gráficos (si están incluidos) */
        .chart-container {
            margin: 20px 0;
            text-align: center;
        }

        .chart-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #495057;
        }

        /* Alertas y mensajes */
        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 8px;
        }

        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }

        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }

        /* Utilidades */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .text-muted { color: #6c757d; }
        .text-primary { color: #007bff; }
        .text-success { color: #28a745; }
        .text-warning { color: #ffc107; }
        .text-danger { color: #dc3545; }

        .mb-10 { margin-bottom: 10px; }
        .mb-15 { margin-bottom: 15px; }
        .mb-20 { margin-bottom: 20px; }
    </style>
</head>
<body>
    <!-- Header del Reporte -->
    <div class="report-header">
        <div class="report-title">{{ $metadatos['titulo'] }}</div>
        <div class="report-subtitle">Reporte Personalizado del Sistema</div>
        
        <div class="report-meta">
            <div class="meta-left">
                <strong>Generado por:</strong> {{ $metadatos['usuario'] }}<br>
                <strong>Fecha:</strong> {{ $metadatos['fecha_generacion'] }}
            </div>
            <div class="meta-right">
                <strong>Registros:</strong> {{ number_format($metadatos['total_registros']) }}<br>
                <strong>Tiempo:</strong> {{ $metadatos['tiempo_ejecucion'] }}
            </div>
        </div>
    </div>

    <!-- Estadísticas del Reporte -->
    <div class="report-stats">
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-number">{{ number_format($metadatos['total_registros']) }}</span>
                <span class="stat-label">Total Registros</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">{{ count($configuracion['campos']) }}</span>
                <span class="stat-label">Campos Seleccionados</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">{{ count($configuracion['filtros'] ?? []) }}</span>
                <span class="stat-label">Filtros Aplicados</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">{{ $metadatos['tiempo_ejecucion'] }}</span>
                <span class="stat-label">Tiempo Ejecución</span>
            </div>
        </div>
    </div>

    <!-- Filtros Aplicados -->
    @if(!empty($configuracion['filtros']))
    <div class="filters-section">
        <div class="filters-title">
            <i class="icon">🔍</i> Filtros Aplicados
        </div>
        @foreach($configuracion['filtros'] as $filtro)
        <div class="filter-item">
            <span class="filter-field">{{ $filtro['campo'] }}</span>
            {{ $filtro['operador'] }} 
            <strong>{{ $filtro['valor'] }}</strong>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Advertencias -->
    @if($metadatos['total_registros'] >= ($configuracion['limite'] ?? 1000))
    <div class="alert alert-warning">
        <strong>⚠️ Advertencia:</strong> Se alcanzó el límite de {{ number_format($configuracion['limite'] ?? 1000) }} registros. 
        Puede haber más datos disponibles. Considere refinar los filtros para obtener resultados más específicos.
    </div>
    @endif

    @if($metadatos['total_registros'] == 0)
    <div class="alert alert-info">
        <strong>ℹ️ Información:</strong> No se encontraron registros que coincidan con los filtros aplicados.
    </div>
    @else

    <!-- Tabla de Datos -->
    <table class="data-table">
        <thead>
            <tr>
                @if($datos->isNotEmpty())
                    @foreach(array_keys($datos->first()->toArray()) as $campo)
                    <th>{{ ucwords(str_replace(['_', '.'], ' ', $campo)) }}</th>
                    @endforeach
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($datos as $fila)
            <tr>
                @foreach($fila->toArray() as $campo => $valor)
                <td class="{{ $this->getClassesPorTipo($valor) }}">
                    {{ $this->formatearValor($valor) }}
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Resumen Estadístico -->
    @php
        $camposNumericos = [];
        $camposFecha = [];
        
        if($datos->isNotEmpty()) {
            foreach($datos->first()->toArray() as $campo => $valor) {
                if(is_numeric($valor)) {
                    $camposNumericos[] = $campo;
                } elseif($this->esFecha($valor)) {
                    $camposFecha[] = $campo;
                }
            }
        }
    @endphp

    @if(!empty($camposNumericos))
    <div class="summary-section">
        <div class="summary-title">📊 Resumen Estadístico</div>
        <div class="summary-grid">
            @foreach(array_slice($camposNumericos, 0, 3) as $campo)
                @php
                    $valores = $datos->pluck($campo)->filter(function($v) { return is_numeric($v); });
                    $total = $valores->sum();
                    $promedio = $valores->count() > 0 ? $valores->avg() : 0;
                    $maximo = $valores->count() > 0 ? $valores->max() : 0;
                @endphp
                <div class="summary-item">
                    <span class="summary-value">{{ number_format($total, 2) }}</span>
                    <span class="summary-label">Total {{ ucwords(str_replace('_', ' ', $campo)) }}</span>
                    <br>
                    <small class="text-muted">Promedio: {{ number_format($promedio, 2) }}</small>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    @endif

    <!-- SQL Generado (para referencia técnica) -->
    @if(isset($metadatos['sql_generado']))
    <div class="summary-section">
        <div class="summary-title">💻 Consulta SQL Generada</div>
        <pre style="font-size: 7px; color: #495057; white-space: pre-wrap; word-break: break-all;">{{ $metadatos['sql_generado'] }}</pre>
    </div>
    @endif

    <!-- Footer -->
    <div class="report-footer">
        <div class="footer-left">
            Sistema de Gestión Financiera - Reporte Generado Automáticamente
        </div>
        <div class="footer-right">
            🤖 Generado con Constructor de Reportes
        </div>
    </div>

    <!-- Número de página -->
    <div class="page-number"></div>

    @php
        // Funciones auxiliares para formateo
        function formatearValor($valor) {
            if (is_null($valor) || $valor === '') {
                return '-';
            }
            
            if (is_numeric($valor)) {
                return number_format($valor, 2);
            }
            
            if ($this->esFecha($valor)) {
                try {
                    return \Carbon\Carbon::parse($valor)->format('d/m/Y H:i');
                } catch (\Exception $e) {
                    return $valor;
                }
            }
            
            return htmlspecialchars($valor);
        }
        
        function getClassesPorTipo($valor) {
            if (is_null($valor) || $valor === '') {
                return 'data-null';
            }
            
            if (is_numeric($valor)) {
                return 'data-number';
            }
            
            if ($this->esFecha($valor)) {
                return 'data-date';
            }
            
            return 'data-text';
        }
        
        function esFecha($valor) {
            if (!is_string($valor)) return false;
            
            try {
                \Carbon\Carbon::parse($valor);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
    @endphp

</body>
</html>