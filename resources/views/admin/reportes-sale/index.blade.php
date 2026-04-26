@extends('layouts.admin')

@section('title', 'Dashboard de Préstamos')
@section('css')
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-oro: #FFD700;
            --color-oro-dark: #FFA500;
            --color-azul: #003366;
            --color-azul-light: #004080;
            --color-blanco: #FFFFFF;
            --color-gris-claro: #F5F5F5;
        }

        .zonificador-container {
            background: var(--color-gris-claro);
            padding: 20px;
        }

        .filters {
            background: var(--color-blanco);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 51, 102, 0.1);
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            border-top: 4px solid var(--color-oro);
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            color: var(--color-azul);
            margin-bottom: 8px;
            font-size: 13px;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            border-color: var(--color-oro);
            outline: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--color-blanco);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 51, 102, 0.1);
            position: relative;
            overflow: hidden;
            border-top: 4px solid var(--color-azul);
        }

        .stat-card.nuevas {
            border-top-color: var(--color-azul);
        }

        .stat-card.renovaciones {
            border-top-color: var(--color-oro);
        }

        .stat-card.total {
            border-top-color: var(--color-azul-light);
        }

        .stat-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 48px;
            opacity: 0.1;
        }

        .stat-card.nuevas .stat-icon {
            color: var(--color-azul);
        }

        .stat-card.renovaciones .stat-icon {
            color: var(--color-oro);
        }

        .stat-card.total .stat-icon {
            color: var(--color-azul-light);
        }

        .stat-label {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .stat-card.nuevas .stat-value {
            color: var(--color-azul);
        }

        .stat-card.renovaciones .stat-value {
            color: var(--color-oro-dark);
        }

        .stat-card.total .stat-value {
            color: var(--color-azul-light);
        }

        .stat-footer {
            font-size: 12px;
            color: #666;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: var(--color-blanco);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 51, 102, 0.1);
            border-top: 4px solid var(--color-azul);
        }

        .chart-container h3 {
            margin-bottom: 20px;
            color: var(--color-azul);
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
        }

        .chart-container h3 i {
            color: var(--color-oro);
        }

        .tables-section {
            display: grid;
            gap: 30px;
        }

        .table-container {
            background: var(--color-blanco);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 51, 102, 0.1);
            overflow-x: auto;
            border-top: 4px solid var(--color-azul);
        }

        .table-container h3 {
            margin-bottom: 20px;
            color: var(--color-azul);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
        }

        .table-container h3 i {
            color: var(--color-oro);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, var(--color-azul) 0%, var(--color-azul-light) 100%);
            color: var(--color-blanco);
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        tbody tr:hover {
            background: #f9f9f9;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-zona {
            background: rgba(255, 215, 0, 0.2);
            color: var(--color-oro-dark);
            border: 1px solid var(--color-oro);
        }

        .badge-sucursal {
            background: rgba(0, 51, 102, 0.1);
            color: var(--color-azul);
            border: 1px solid var(--color-azul-light);
        }

        .badge-rol {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-rol-admin {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        
        .badge-rol-analista {
            background: rgba(0, 123, 255, 0.15);
            color: #007bff;
            border: 1px solid #007bff;
        }
        
        .badge-rol-jcc {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
            border: 1px solid #28a745;
        }
        
        .badge-rol-asesor {
            background: rgba(255, 193, 7, 0.15);
            color: #ffc107;
            border: 1px solid #ffc107;
        }
        
        .badge-rol-sin-rol {
            background: rgba(108, 117, 125, 0.15);
            color: #6c757d;
            border: 1px solid #6c757d;
        }

        .money {
            font-weight: 600;
            color: var(--color-azul);
        }

        .rank {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 14px;
        }

        .rank.gold {
            background: linear-gradient(135deg, var(--color-oro) 0%, var(--color-oro-dark) 100%);
            color: var(--color-azul);
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.4);
        }

        .rank.silver {
            background: linear-gradient(135deg, #C0C0C0 0%, #A8A8A8 100%);
            color: var(--color-azul);
            box-shadow: 0 2px 8px rgba(192, 192, 192, 0.4);
        }

        .rank.bronze {
            background: linear-gradient(135deg, #CD7F32 0%, #B87333 100%);
            color: var(--color-blanco);
            box-shadow: 0 2px 8px rgba(205, 127, 50, 0.4);
        }

        .top-vendedor {
            background: var(--color-blanco);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 51, 102, 0.1);
            margin-bottom: 30px;
            border-top: 4px solid var(--color-oro);
        }

        .top-vendedor h3 {
            margin-bottom: 20px;
            color: var(--color-azul);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
        }

        .top-vendedor h3 i {
            color: var(--color-oro);
            font-size: 24px;
        }

        .top-vendedor-content {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .medal-icon {
            font-size: 80px;
            color: var(--color-oro);
        }

        .top-info {
            flex: 1;
        }

        .top-nombre {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-azul);
            margin-bottom: 15px;
        }

        .top-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .top-stat {
            background: var(--color-gris-claro);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid var(--color-oro);
        }

        .top-stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .top-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--color-azul);
        }

        .top-tres-section {
            background: var(--color-blanco);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 51, 102, 0.1);
            margin-bottom: 30px;
            border-top: 4px solid var(--color-azul);
        }

        .top-tres-section h3 {
            margin-bottom: 20px;
            color: var(--color-azul);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
        }

        .top-tres-section h3 i {
            color: var(--color-oro);
        }

        #loadingOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 51, 102, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        #loadingOverlay.show {
            display: flex !important;
        }

        .loading-content {
            text-align: center;
            background: var(--color-blanco);
            padding: 40px 60px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            border-top: 4px solid var(--color-oro);
        }

        .loading-content i {
            color: var(--color-azul);
            margin-bottom: 15px;
        }

        .loading-content p {
            color: var(--color-azul);
            font-weight: 600;
        }
        
        /* Nuevos estilos para acordeón de roles */
        .acordeon-rol {
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .acordeon-header {
            background: linear-gradient(135deg, var(--color-azul) 0%, var(--color-azul-light) 100%);
            color: white;
            padding: 15px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }
        
        .acordeon-header:hover {
            background: linear-gradient(135deg, var(--color-azul-light) 0%, #0055aa 100%);
        }
        
        .acordeon-header.active {
            background: linear-gradient(135deg, var(--color-oro-dark) 0%, var(--color-oro) 100%);
        }
        
        .acordeon-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 16px;
        }
        
        .acordeon-count {
            background: white;
            color: var(--color-azul);
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 8px;
        }
        
        .acordeon-icon {
            transition: transform 0.3s;
        }
        
        .acordeon-header.active .acordeon-icon {
            transform: rotate(180deg);
        }
        
        .acordeon-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background: white;
        }
        
        .acordeon-content.show {
            max-height: 5000px;
            transition: max-height 0.5s ease-in;
        }
        
        .filtros-rapidos-rol {
            background: var(--color-blanco);
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 51, 102, 0.1);
            margin-bottom: 20px;
            border-top: 4px solid var(--color-azul);
        }
        
        .filtros-rapidos-rol h5 {
            color: var(--color-azul);
            margin-bottom: 12px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .filtros-rapidos-botones {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-filtro-rol {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-filtro-rol:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-filtro-rol.active {
            background-color: var(--color-oro) !important;
            color: var(--color-azul) !important;
            font-weight: bold;
            border-color: var(--color-oro) !important;
        }
        
        .btn-filtro-todos {
            background: var(--color-gris-claro);
            color: var(--color-azul);
            border-color: #ddd;
        }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark"><i class="fas fa-chart-line mr-2" style="color: var(--color-oro);"></i> Dashboard de
                Préstamos</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Inicio</a></li>
                <li class="breadcrumb-item active">Dashboard de Préstamos</li>
            </ol>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">

        <!-- Filtros -->
        <div class="filters">
            <div class="filter-group">
                <label for="filterZona"><i class="fas fa-map-marker-alt"></i> Zona:</label>
                <select id="filterZona" onchange="actualizarSucursales(); cargarDatos();">
                    <option value="">Todas las zonas</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="filterSucursal"><i class="fas fa-building"></i> Sucursal:</label>
                <select id="filterSucursal" onchange="cargarDatos()">
                    <option value="">Todas las sucursales</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="filterRol"><i class="fas fa-user-tag"></i> Rol:</label>
                <select id="filterRol" onchange="cargarDatos()">
                    <option value="">Todos los roles</option>
                    <option value="Admin">Administrador</option>
                    <option value="Analista">Analista</option>
                    <option value="JCC">JCC</option>
                    <option value="Asesor">Asesor</option>
                    <option value="SIN ROL">Sin Rol</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="filterFechaInicio"><i class="fas fa-calendar-alt"></i> Fecha Inicio:</label>
                <input type="date" id="filterFechaInicio" onchange="cargarDatos()">
            </div>
            <div class="filter-group">
                <label for="filterFechaFin"><i class="fas fa-calendar-alt"></i> Fecha Fin:</label>
                <input type="date" id="filterFechaFin" onchange="cargarDatos()">
            </div>
            <div class="filter-group">
                <label for="filterMes"><i class="fas fa-calendar"></i> Mes:</label>
                <select id="filterMes" onchange="cargarDatos()">
                    <option value="">Todos los meses</option>
                    <option value="1">Enero</option>
                    <option value="2">Febrero</option>
                    <option value="3">Marzo</option>
                    <option value="4">Abril</option>
                    <option value="5">Mayo</option>
                    <option value="6">Junio</option>
                    <option value="7">Julio</option>
                    <option value="8">Agosto</option>
                    <option value="9">Septiembre</option>
                    <option value="10">Octubre</option>
                    <option value="11">Noviembre</option>
                    <option value="12">Diciembre</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="filterAno"><i class="fas fa-calendar-check"></i> Año:</label>
                <select id="filterAno" onchange="cargarDatos()">
                    <option value="">Todos los años</option>
                    <option value="2023">2023</option>
                    <option value="2024">2024</option>
                    <option value="2025">2025</option>
                    <option value="2026" selected>2026</option>
                </select>
            </div>
        </div>

        <!-- Tarjetas de Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card nuevas">
                <i class="fas fa-file-invoice-dollar stat-icon"></i>
                <div class="stat-label"><i class="fas fa-plus-circle"></i> Total de Nuevas</div>
                <div class="stat-value" id="totalNuevas">0</div>
                <div class="stat-footer">Préstamos nuevos</div>
            </div>
            <div class="stat-card renovaciones">
                <i class="fas fa-sync-alt stat-icon"></i>
                <div class="stat-label"><i class="fas fa-redo-alt"></i> Total de Renovaciones</div>
                <div class="stat-value" id="totalRenovaciones">0</div>
                <div class="stat-footer">Préstamos renovados</div>
            </div>
            <div class="stat-card total">
                <i class="fas fa-calculator stat-icon"></i>
                <div class="stat-label"><i class="fas fa-sigma"></i> Total de Préstamos</div>
                <div class="stat-value" id="totalGeneral">0</div>
                <div class="stat-footer">Suma total</div>
            </div>
        </div>

        <!-- Top 3 Usuarios -->
        <div class="top-tres-section">
            <h3>
                <i class="fas fa-trophy"></i>
                Top 3 Usuarios del Período
            </h3>
            <div id="topTresContainer" style="display: grid; gap: 15px;">
                <!-- Se genera dinámicamente -->
            </div>
        </div>

        <!-- Top Usuario Destacado -->
        <div class="top-vendedor">
            <h3>
                <i class="fas fa-crown"></i>
                Top Usuario del Período
            </h3>
            <div class="top-vendedor-content">
                <i class="fas fa-medal medal-icon"></i>
                <div class="top-info">
                    <div class="top-nombre" id="topNombre">-</div>
                    <div class="top-stats">
                        <div class="top-stat">
                            <div class="top-stat-label"><i class="fas fa-plus"></i> Nuevas</div>
                            <div class="top-stat-value" id="topNuevas">0</div>
                        </div>
                        <div class="top-stat">
                            <div class="top-stat-label"><i class="fas fa-redo"></i> Renovaciones</div>
                            <div class="top-stat-value" id="topRenovaciones">0</div>
                        </div>
                        <div class="top-stat">
                            <div class="top-stat-label"><i class="fas fa-hashtag"></i> Total</div>
                            <div class="top-stat-value" id="topTotal">0</div>
                        </div>
                        <div class="top-stat">
                            <div class="top-stat-label"><i class="fas fa-dollar-sign"></i> Monto</div>
                            <div class="top-stat-value" id="topMonto">S/. 0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="charts-grid">
            <div class="chart-container">
                <h3><i class="fas fa-chart-pie"></i> Distribución por Rol</h3>
                <canvas id="chartRoles"></canvas>
            </div>
            <div class="chart-container">
                <h3><i class="fas fa-chart-bar"></i> Nuevas vs Renovaciones</h3>
                <canvas id="chartComparativa"></canvas>
            </div>
        </div>

        <!-- Tabla de Usuarios con Acordeón por Rol -->
        <div class="tables-section">
            <div class="table-container">
                <h3><i class="fas fa-users"></i> Desempeño por Usuario (Agrupado por Rol)</h3>
                <div id="acordeonUsuarios">
                    <!-- Se genera dinámicamente -->
                    <div style="text-align: center; color: #999; padding: 20px;">
                        Cargando datos...
                    </div>
                </div>
            </div>

            <div class="table-container">
                <h3><i class="fas fa-map-marker-alt"></i> Resumen por Zona</h3>
                <table id="tablaZonas">
                    <thead>
                        <tr>
                            <th><i class="fas fa-map"></i> Zona</th>
                            <th><i class="fas fa-plus-circle"></i> Nuevas</th>
                            <th><i class="fas fa-sync-alt"></i> Renovaciones</th>
                            <th><i class="fas fa-hashtag"></i> Total</th>
                            <th><i class="fas fa-money-bill-wave"></i> Monto Total</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoZonas">
                        <tr>
                            <td colspan="5" style="text-align: center; color: #999;">Cargando datos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="table-container">
                <h3><i class="fas fa-building"></i> Resumen por Sucursal</h3>
                <table id="tablaSucursales">
                    <thead>
                        <tr>
                            <th><i class="fas fa-store"></i> Sucursal</th>
                            <th><i class="fas fa-map"></i> Zona</th>
                            <th><i class="fas fa-plus-circle"></i> Nuevas</th>
                            <th><i class="fas fa-sync-alt"></i> Renovaciones</th>
                            <th><i class="fas fa-hashtag"></i> Total</th>
                            <th><i class="fas fa-money-bill-wave"></i> Monto Total</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoSucursales">
                        <tr>
                            <td colspan="6" style="text-align: center; color: #999;">Cargando datos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="loading-content">
            <i class="fas fa-spinner fa-spin fa-3x"></i>
            <p class="mt-3">Cargando datos...</p>
        </div>
    </div>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Configuración de la API
        const API_BASE = `${window.location.origin}/api`;

        // Variables globales
        let zonasData = [];
        let zonasConSucursales = [];
        let chartRoles = null;
        let chartComparativa = null;
        let datosUsuariosPorRol = {}; // Almacenar datos agrupados por rol

        // Configuración de headers para las peticiones
        const getHeaders = () => {
            return {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            };
        };

        // Función para hacer fetch con manejo de sesión
        async function fetchWithAuth(url, options = {}) {
            const defaultOptions = {
                method: 'GET',
                headers: getHeaders(),
                credentials: 'same-origin'
            };

            const finalOptions = {
                ...defaultOptions,
                ...options
            };

            const response = await fetch(url, finalOptions);

            if (response.status === 401 || response.status === 419) {
                window.location.href = '/login';
                throw new Error('Sesión expirada. Redirigiendo al login...');
            }

            if (response.status === 302) {
                window.location.href = '/login';
                throw new Error('No autenticado. Redirigiendo al login...');
            }

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return response.json();
        }

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            cargarZonas();
            cargarZonasConSucursales();
            cargarDatos();
        });

        // Cargar zonas desde API
        async function cargarZonas() {
            try {
                zonasData = await fetchWithAuth(`${API_BASE}/ubicacion/zonas`);

                const selectZona = document.getElementById('filterZona');
                selectZona.innerHTML = '<option value="">Todas las zonas</option>';

                zonasData.forEach(zona => {
                    const option = document.createElement('option');
                    option.value = zona.id;
                    option.textContent = zona.nombre;
                    selectZona.appendChild(option);
                });

            } catch (error) {
                console.error('Error cargando zonas:', error);
                if (!error.message.includes('Redirigiendo')) {
                    mostrarError('Error al cargar las zonas. Por favor, recargue la página.');
                }
            }
        }

        // Cargar zonas con sucursales desde API
        async function cargarZonasConSucursales() {
            try {
                zonasConSucursales = await fetchWithAuth(`${API_BASE}/zonas-con-sucursales`);
            } catch (error) {
                console.error('Error cargando zonas con sucursales:', error);
            }
        }

        // Actualizar sucursales según zona seleccionada
        function actualizarSucursales() {
            const zonaId = document.getElementById('filterZona').value;
            const selectSucursal = document.getElementById('filterSucursal');

            selectSucursal.innerHTML = '<option value="">Todas las sucursales</option>';

            if (zonaId === '') {
                zonasConSucursales.forEach(zona => {
                    if (zona.sucursales && zona.sucursales.length > 0) {
                        const optgroup = document.createElement('optgroup');
                        optgroup.label = zona.nombre;

                        zona.sucursales.forEach(sucursal => {
                            const option = document.createElement('option');
                            option.value = sucursal.id;
                            option.textContent = sucursal.sucursal;
                            optgroup.appendChild(option);
                        });

                        selectSucursal.appendChild(optgroup);
                    }
                });
            } else {
                const zonaSeleccionada = zonasConSucursales.find(z => z.id == zonaId);
                if (zonaSeleccionada && zonaSeleccionada.sucursales) {
                    zonaSeleccionada.sucursales.forEach(sucursal => {
                        const option = document.createElement('option');
                        option.value = sucursal.id;
                        option.textContent = sucursal.sucursal;
                        selectSucursal.appendChild(option);
                    });
                }
            }
        }

        // Construir parámetros de filtro
        function construirParametros() {
            const params = new URLSearchParams();

            const zonaId = document.getElementById('filterZona').value;
            const sucursalId = document.getElementById('filterSucursal').value;
            const rol = document.getElementById('filterRol').value;
            const fechaInicio = document.getElementById('filterFechaInicio').value;
            const fechaFin = document.getElementById('filterFechaFin').value;
            const mes = document.getElementById('filterMes').value;
            const anio = document.getElementById('filterAno').value;

            if (zonaId) params.append('zona_id', zonaId);
            if (sucursalId) params.append('sucursal_id', sucursalId);
            if (rol) params.append('rol', rol);
            if (fechaInicio) params.append('fecha_inicio', fechaInicio);
            if (fechaFin) params.append('fecha_fin', fechaFin);
            if (mes) params.append('mes', mes);
            if (anio) params.append('anio', anio);

            return params.toString();
        }

        // Cargar todos los datos
        async function cargarDatos() {
            mostrarLoading(true);

            try {
                const params = construirParametros();

                const [totalData, porUsuarioData, porZonaData, porSucursalData] = await Promise.all([
                    fetchWithAuth(`${API_BASE}/report-sale/prestamos/total?${params}`),
                    fetchWithAuth(`${API_BASE}/report-sale/prestamos/por-usuario?${params}`),
                    fetchWithAuth(`${API_BASE}/report-sale/prestamos/por-zona?${params}`),
                    fetchWithAuth(`${API_BASE}/report-sale/prestamos/por-sucursal?${params}`)
                ]);

                // Guardar datos de usuarios por rol
                datosUsuariosPorRol = porUsuarioData;

                // Actualizar estadísticas
                actualizarEstadisticas(totalData);

                // Actualizar tablas
                actualizarTablaUsuariosAgrupados(porUsuarioData);
                actualizarTablaZonas(porZonaData);
                actualizarTablaSucursales(porSucursalData);

                // Actualizar Top 3 y Top Usuario (usando todos los usuarios)
                const todosLosUsuarios = obtenerTodosUsuarios(porUsuarioData);
                actualizarTopUsuarios(todosLosUsuarios);

                // Actualizar gráficos
                actualizarGraficos(porZonaData, todosLosUsuarios, porUsuarioData);

            } catch (error) {
                console.error('Error cargando datos:', error);
                if (!error.message.includes('Redirigiendo')) {
                    mostrarError('Error al cargar los datos: ' + error.message);
                }
            } finally {
                mostrarLoading(false);
            }
        }

        // Obtener todos los usuarios de todos los roles
        function obtenerTodosUsuarios(datosPorRol) {
            let todosLosUsuarios = [];
            for (const rol in datosPorRol) {
                if (Array.isArray(datosPorRol[rol])) {
                    todosLosUsuarios = todosLosUsuarios.concat(datosPorRol[rol]);
                }
            }
            // Ordenar por total de préstamos
            return todosLosUsuarios.sort((a, b) => b.total_prestamos - a.total_prestamos);
        }

        // Actualizar estadísticas principales
        function actualizarEstadisticas(data) {
            document.getElementById('totalNuevas').textContent = data.total_nuevas || 0;
            document.getElementById('totalRenovaciones').textContent = data.total_renovaciones || 0;
            document.getElementById('totalGeneral').textContent = data.total_prestamos || 0;
        }

        // Actualizar tabla de usuarios con acordeón por rol
        function actualizarTablaUsuariosAgrupados(datosPorRol) {
            const acordeonContainer = document.getElementById('acordeonUsuarios');

            if (!datosPorRol || Object.keys(datosPorRol).length === 0) {
                acordeonContainer.innerHTML = 
                    '<div style="text-align: center; color: #999; padding: 20px;">No hay datos disponibles</div>';
                return;
            }

            let html = '';
            let contadorGlobal = 0;
            
            // Obtener roles ordenados
            const roles = Object.keys(datosPorRol).sort();
            
            roles.forEach((rol, rolIndex) => {
                const usuarios = datosPorRol[rol];
                if (!Array.isArray(usuarios)) return;
                
                // Ordenar usuarios del rol por total de préstamos
                usuarios.sort((a, b) => b.total_prestamos - a.total_prestamos);
                
                const rolId = `rol-${rolIndex}`;
                const iconoRol = obtenerIconoRol(rol);
                const colorBadge = obtenerColorBadgeRol(rol);
                
                // Calcular totales por rol
                const totalRol = usuarios.reduce((sum, usuario) => sum + parseInt(usuario.total_prestamos || 0), 0);
                const nuevasRol = usuarios.reduce((sum, usuario) => sum + parseInt(usuario.total_nuevas || 0), 0);
                const renovacionesRol = usuarios.reduce((sum, usuario) => sum + parseInt(usuario.total_renovaciones || 0), 0);
                const montoRol = usuarios.reduce((sum, usuario) => sum + parseFloat(usuario.monto_total || 0), 0);
                
                html += `
                    <div class="acordeon-rol">
                        <div class="acordeon-header" onclick="toggleAcordeon('${rolId}')">
                            <div class="acordeon-title">
                                <i class="${iconoRol}"></i>
                                ${rol}
                                <span class="acordeon-count">${usuarios.length} usuario(s)</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="text-align: right; font-size: 12px; color: rgba(255,255,255,0.8);">
                                    <div>Total: <strong>${totalRol}</strong></div>
                                    <div>Monto: S/. ${montoRol.toLocaleString('es-PE', {minimumFractionDigits: 2})}</div>
                                </div>
                                <i class="fas fa-chevron-down acordeon-icon"></i>
                            </div>
                        </div>
                        <div class="acordeon-content" id="${rolId}">
                            <table style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">Rank</th>
                                        <th>Usuario</th>
                                        <th>Email</th>
                                        <th>Nuevas</th>
                                        <th>Renovaciones</th>
                                        <th>Total</th>
                                        <th>Monto Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                
                usuarios.forEach((usuario, usuarioIndex) => {
                    contadorGlobal++;
                    let rankClass = '';
                    let rankContent = '';
                    
                    if (usuarioIndex === 0) {
                        rankClass = 'rank gold';
                        rankContent = '<i class="fas fa-trophy"></i>';
                    } else if (usuarioIndex === 1) {
                        rankClass = 'rank silver';
                        rankContent = '<i class="fas fa-medal"></i>';
                    } else if (usuarioIndex === 2) {
                        rankClass = 'rank bronze';
                        rankContent = '<i class="fas fa-award"></i>';
                    } else {
                        rankContent = usuarioIndex + 1;
                    }
                    
                    html += `
                        <tr>
                            <td><div class="${rankClass}" style="margin: 0 auto;">${rankContent}</div></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="badge-rol ${colorBadge}" style="font-size: 10px;">${rol}</span>
                                    ${usuario.usuario_nombre || 'Sin nombre'}
                                </div>
                            </td>
                            <td style="font-size: 12px;">${usuario.usuario_email || '-'}</td>
                            <td class="money">${usuario.total_nuevas || 0}</td>
                            <td class="money">${usuario.total_renovaciones || 0}</td>
                            <td class="money">${usuario.total_prestamos || 0}</td>
                            <td class="money">S/. ${parseFloat(usuario.monto_total || 0).toLocaleString('es-PE', {minimumFractionDigits: 2})}</td>
                        </tr>
                    `;
                });
                
                html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            });

            acordeonContainer.innerHTML = html;
            
            // Expandir el primer acordeón por defecto
            if (roles.length > 0) {
                setTimeout(() => {
                    toggleAcordeon(`rol-0`, true);
                }, 100);
            }
        }

        // Obtener icono según el rol
        function obtenerIconoRol(rol) {
            switch(rol) {
                case 'Admin': return 'fas fa-user-shield';
                case 'Analista': return 'fas fa-user-tie';
                case 'JCC': return 'fas fa-user-cog';
                case 'Asesor': return 'fas fa-user-check';
                default: return 'fas fa-user';
            }
        }

        // Obtener color del badge según el rol
        function obtenerColorBadgeRol(rol) {
            switch(rol) {
                case 'Admin': return 'badge-rol-admin';
                case 'Analista': return 'badge-rol-analista';
                case 'JCC': return 'badge-rol-jcc';
                case 'Asesor': return 'badge-rol-asesor';
                default: return 'badge-rol-sin-rol';
            }
        }

        // Alternar acordeón
        function toggleAcordeon(id, forceOpen = false) {
            const elemento = document.getElementById(id);
            const header = elemento.previousElementSibling;
            
            if (forceOpen || elemento.style.maxHeight === '' || elemento.style.maxHeight === '0px') {
                elemento.classList.add('show');
                elemento.style.maxHeight = elemento.scrollHeight + "px";
                header.classList.add('active');
            } else {
                elemento.classList.remove('show');
                elemento.style.maxHeight = null;
                header.classList.remove('active');
            }
        }

        // Actualizar tabla de zonas
        function actualizarTablaZonas(data) {
            const tbody = document.getElementById('cuerpoZonas');

            if (!data || data.length === 0) {
                tbody.innerHTML =
                    '<tr><td colspan="5" style="text-align: center; color: #999;">No hay datos disponibles</td></tr>';
                return;
            }

            let html = '';
            data.forEach(zona => {
                html += `
                    <tr>
                        <td><span class="badge badge-zona"><i class="fas fa-map-marked-alt"></i> ${zona.zona_nombre}</span></td>
                        <td class="money">${zona.total_nuevas || 0}</td>
                        <td class="money">${zona.total_renovaciones || 0}</td>
                        <td class="money">${zona.total_prestamos || 0}</td>
                        <td class="money">S/. ${parseFloat(zona.monto_total || 0).toLocaleString('es-PE', {minimumFractionDigits: 2})}</td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        // Actualizar tabla de sucursales
        function actualizarTablaSucursales(data) {
            const tbody = document.getElementById('cuerpoSucursales');

            if (!data || data.length === 0) {
                tbody.innerHTML =
                    '<tr><td colspan="6" style="text-align: center; color: #999;">No hay datos disponibles</td></tr>';
                return;
            }

            let html = '';
            data.forEach(sucursal => {
                html += `
                    <tr>
                        <td><span class="badge badge-sucursal"><i class="fas fa-store-alt"></i> ${sucursal.sucursal_nombre}</span></td>
                        <td><span class="badge badge-zona">${sucursal.zona_nombre || '-'}</span></td>
                        <td class="money">${sucursal.total_nuevas || 0}</td>
                        <td class="money">${sucursal.total_renovaciones || 0}</td>
                        <td class="money">${sucursal.total_prestamos || 0}</td>
                        <td class="money">S/. ${parseFloat(sucursal.monto_total || 0).toLocaleString('es-PE', {minimumFractionDigits: 2})}</td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        // Actualizar Top 3 y Top Usuario
        function actualizarTopUsuarios(data) {
            if (!data || data.length === 0) {
                // Limpiar Top 3
                const topTresContainer = document.getElementById('topTresContainer');
                topTresContainer.innerHTML = '<div style="text-align: center; color: #999; padding: 20px;">No hay datos disponibles</div>';
                
                // Limpiar Top Usuario
                document.getElementById('topNombre').textContent = '-';
                document.getElementById('topNuevas').textContent = '0';
                document.getElementById('topRenovaciones').textContent = '0';
                document.getElementById('topTotal').textContent = '0';
                document.getElementById('topMonto').textContent = 'S/. 0';
                return;
            }

            // Top 3
            const topTresContainer = document.getElementById('topTresContainer');
            topTresContainer.innerHTML = '';

            const medals = [{
                    icon: 'fa-trophy',
                    color: 'var(--color-oro)',
                    bg: 'rgba(255, 215, 0, 0.15)'
                },
                {
                    icon: 'fa-medal',
                    color: '#C0C0C0',
                    bg: 'rgba(192, 192, 192, 0.15)'
                },
                {
                    icon: 'fa-award',
                    color: '#CD7F32',
                    bg: 'rgba(205, 127, 50, 0.15)'
                }
            ];

            data.slice(0, 3).forEach((usuario, index) => {
                const medal = medals[index];
                const badgeClass = obtenerColorBadgeRol(usuario.rol_nombre || 'SIN ROL');

                const topCard = document.createElement('div');
                topCard.style.cssText =
                    `background: ${medal.bg}; border-left: 5px solid ${medal.color}; padding: 20px; border-radius: 8px; display: flex; align-items: center; gap: 20px;`;
                topCard.innerHTML = `
                    <i class="fas ${medal.icon}" style="font-size: 40px; color: ${medal.color};"></i>
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: var(--color-azul); font-size: 16px;">${usuario.usuario_nombre || 'Sin nombre'}</div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                            <span class="badge-rol ${badgeClass}" style="font-size: 10px;">${usuario.rol_nombre || 'SIN ROL'}</span>
                            <div style="font-size: 12px; color: #666;">${usuario.usuario_email || ''}</div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="background: var(--color-blanco); padding: 10px; border-radius: 4px; text-align: center; border-left: 3px solid var(--color-azul);">
                                <div style="font-size: 11px; color: #666; text-transform: uppercase;"><i class="fas fa-plus"></i> Nuevas</div>
                                <div style="font-weight: 700; color: var(--color-azul); font-size: 16px;">${usuario.total_nuevas || 0}</div>
                            </div>
                            <div style="background: var(--color-blanco); padding: 10px; border-radius: 4px; text-align: center; border-left: 3px solid var(--color-oro);">
                                <div style="font-size: 11px; color: #666; text-transform: uppercase;"><i class="fas fa-redo"></i> Renovaciones</div>
                                <div style="font-weight: 700; color: var(--color-oro-dark); font-size: 16px;">${usuario.total_renovaciones || 0}</div>
                            </div>
                        </div>
                    </div>
                    <div style="text-align: center; padding: 10px;">
                        <div style="font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 4px;"><i class="fas fa-hashtag"></i> Total</div>
                        <div style="font-size: 28px; font-weight: 900; color: var(--color-azul);">${usuario.total_prestamos || 0}</div>
                    </div>
                `;
                topTresContainer.appendChild(topCard);
            });

            // Top Usuario
            const topUsuario = data[0];
            if (topUsuario) {
                document.getElementById('topNombre').textContent = topUsuario.usuario_nombre || 'Sin nombre';
                document.getElementById('topNuevas').textContent = topUsuario.total_nuevas || 0;
                document.getElementById('topRenovaciones').textContent = topUsuario.total_renovaciones || 0;
                document.getElementById('topTotal').textContent = topUsuario.total_prestamos || 0;
                document.getElementById('topMonto').textContent =
                    `S/. ${parseFloat(topUsuario.monto_total || 0).toLocaleString('es-PE', {minimumFractionDigits: 2})}`;
            }
        }

        // Actualizar gráficos
        function actualizarGraficos(zonaData, usuarioData, datosPorRol) {
            // Destruir gráficos anteriores
            if (chartRoles) chartRoles.destroy();
            if (chartComparativa) chartComparativa.destroy();

            // Colores corporativos
            const coloresRoles = ['#003366', '#004080', '#FFD700', '#FFA500', '#0055AA', '#28a745', '#dc3545'];

            // Gráfico por Roles
            if (datosPorRol && Object.keys(datosPorRol).length > 0) {
                const roles = Object.keys(datosPorRol);
                const totalesPorRol = roles.map(rol => {
                    if (Array.isArray(datosPorRol[rol])) {
                        return datosPorRol[rol].reduce((sum, usuario) => sum + parseInt(usuario.total_prestamos || 0), 0);
                    }
                    return 0;
                });

                const ctxRoles = document.getElementById('chartRoles').getContext('2d');
                chartRoles = new Chart(ctxRoles, {
                    type: 'doughnut',
                    data: {
                        labels: roles,
                        datasets: [{
                            data: totalesPorRol,
                            backgroundColor: coloresRoles.slice(0, roles.length),
                            borderColor: '#FFFFFF',
                            borderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: '#003366',
                                    font: {
                                        size: 12,
                                        weight: '600'
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Gráfico Comparativa
            if (usuarioData && usuarioData.length > 0) {
                const topUsuarios = usuarioData.slice(0, 8);
                const ctxComparativa = document.getElementById('chartComparativa').getContext('2d');
                chartComparativa = new Chart(ctxComparativa, {
                    type: 'bar',
                    data: {
                        labels: topUsuarios.map(u => u.usuario_nombre ? u.usuario_nombre.split(' ')[0] : 'Sin nombre'),
                        datasets: [{
                                label: 'Nuevas',
                                data: topUsuarios.map(u => u.total_nuevas),
                                backgroundColor: '#003366',
                                borderColor: '#003366',
                                borderWidth: 1
                            },
                            {
                                label: 'Renovaciones',
                                data: topUsuarios.map(u => u.total_renovaciones),
                                backgroundColor: '#FFD700',
                                borderColor: '#FFA500',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: '#003366',
                                    font: {
                                        weight: '600'
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 51, 102, 0.1)'
                                }
                            },
                            x: {
                                ticks: {
                                    color: '#003366',
                                    font: {
                                        weight: '600'
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: '#003366',
                                    font: {
                                        size: 12,
                                        weight: '600'
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }

        // Mostrar/ocultar loading
        function mostrarLoading(show) {
            const overlay = document.getElementById('loadingOverlay');
            if (show) {
                overlay.classList.add('show');
            } else {
                overlay.classList.remove('show');
            }
        }

        // Mostrar error
        function mostrarError(mensaje) {
            alert(mensaje);
        }
    </script>
@stop