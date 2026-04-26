@extends('layouts.admin')

@section('title', 'Zonificador de Tramos')
@section('css')
    {{-- Bootstrap Datepicker CSS --}}
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <style>
        .zonificador-container {
            background: #f4f6f9;
            padding: 20px;
        }

        .zonificador-header {
            background: white;
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .zonificador-header .form-inline {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .zonificador-header label {
            margin-right: 5px;
            font-weight: 500;
        }

        .zonificador-content {
            display: grid;
            grid-template-columns: 300px 1fr 280px;
            gap: 20px;
        }

        /* Panel Clientes */
        .clientes-panel {
            background: white;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            height: calc(100vh - 220px);
            display: flex;
            flex-direction: column;
        }

        .clientes-header {
            background: #28a745;
            color: white;
            padding: 12px 15px;
            border-radius: 5px 5px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .clientes-header h5 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
        }

        .clientes-filtros {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .clientes-lista {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .cliente-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .cliente-item:hover {
            background: #f8f9fa;
            border-color: #007bff;
        }

        .cliente-item.selected {
            background: #e3f2fd;
            border-color: #2196F3;
        }

        .cliente-item input[type="checkbox"] {
            margin-right: 10px;
        }

        .cliente-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #6c757d;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .cliente-info {
            flex: 1;
        }

        .cliente-nombre {
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 2px;
        }

        .cliente-direccion {
            font-size: 11px;
            color: #6c757d;
        }

        .clientes-footer {
            padding: 15px;
            border-top: 1px solid #e0e0e0;
        }

        /* Panel Mapa */
        .mapa-panel {
            background: white;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            height: calc(100vh - 220px);
            position: relative;
        }

        #map {
            width: 100%;
            height: 100%;
            border-radius: 5px;
        }

        .map-controls {
            position: absolute;
            top: 10px;
            right: 60px;
            z-index: 5;
            background: white;
            padding: 10px;
            border-radius: 4px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        }

        .map-controls button {
            display: block;
            width: 100%;
            margin-bottom: 5px;
            white-space: nowrap;
        }

        .map-controls button:last-child {
            margin-bottom: 0;
        }

        /* Panel Asignar Ruta */
        .asignar-panel {
            background: white;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            height: calc(100vh - 220px);
            display: flex;
            flex-direction: column;
        }

        .asignar-header {
            padding: 15px;
            border-bottom: 2px solid #007bff;
        }

        .asignar-header h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .asignar-content {
            padding: 15px;
            flex: 1;
            overflow-y: auto;
        }

        .form-group label {
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .rutas-info {
            margin-top: 20px;
        }

        .ruta-item {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 10px;
        }

        .ruta-header {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .ruta-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #007bff;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .ruta-nombre {
            font-weight: 600;
            font-size: 13px;
        }

        .ruta-detalles {
            font-size: 12px;
            color: #6c757d;
            margin-left: 42px;
        }

        .ruta-detalles div {
            margin-bottom: 3px;
        }

        .btn-crear-ruta {
            width: 100%;
            margin-top: auto;
        }

        /* Info Window personalizado */
        .gm-style .gm-style-iw-c {
            padding: 12px !important;
        }

        .info-content h6 {
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: 600;
        }

        .info-content p {
            margin: 4px 0;
            font-size: 12px;
        }

        /* Botones de tramos */
        .btn-group-toggle .btn.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        /* Loading overlay */
        #loadingOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        #loadingOverlay.show {
            display: flex !important;
        }

        .loading-content {
            text-align: center;
            background: white;
            padding: 30px 50px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .loading-content i {
            color: #007bff;
            margin-bottom: 15px;
        }

        /* Datepicker styles */
        .datepicker {
            z-index: 9999 !important;
            padding: 8px !important;
            font-size: 12px !important;
            max-width: 280px !important;
        }

        .datepicker table {
            font-size: 12px !important;
        }

        .datepicker table tr td,
        .datepicker table tr th {
            width: 30px !important;
            height: 30px !important;
            padding: 4px !important;
            font-size: 11px !important;
        }

        
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark"><i class="fas fa-map-marked-alt mr-2"></i> Zonificador de Tramos</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Inicio</a></li>
                <li class="breadcrumb-item active">Zonificador de Tramos</li>
            </ol>
        </div>
    </div>
@stop

@section('content')
    <div class="zonificador-container">
        <!-- Header con filtros -->
        <div class="zonificador-header">
            <form id="filterForm" class="form-inline">

                <div class="form-group">
                    <label for="tipo_consulta">Tipo consulta:</label>
                    <select class="form-control form-control-sm ml-2" id="tipo_consulta" name="tipo_consulta">
                        <option value="ambos">Ambos</option>
                        <option value="prestamos">Solo Préstamos</option>
                        <option value="convenios">Solo Convenios</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tipo_rango_fecha">Rango Fecha:</label>
                    <select class="form-control form-control-sm ml-2" id="tipo_rango_fecha" name="tipo_rango_fecha">
                        <option value="">Todos</option>
                        <option value="dia">Por día</option>
                        <option value="mes">Por mes</option>
                        <option value="entre_fechas">Entre fechas</option>
                    </select>
                </div>

                <div id="fecha_dia" class="form-group ml-3" style="display: none;">
                    <label for="fecha_dia_input">Fecha:</label>
                    <input type="text" class="form-control form-control-sm ml-2 datepicker-dia" id="fecha_dia_input"
                        name="fecha_dia" placeholder="dd/mm/yyyy" autocomplete="off">
                </div>

                <div id="fecha_mes" class="form-group ml-3" style="display: none;">
                    <label for="fecha_mes_input">Mes:</label>
                    <input type="text" class="form-control form-control-sm ml-2 datepicker-mes" id="fecha_mes_input"
                        name="fecha_mes" placeholder="mm/yyyy" autocomplete="off">
                </div>

                <div id="fecha_entre" style="display: none;">
                    <div class="form-group ml-3">
                        <label for="fecha_desde">Desde:</label>
                        <input type="text" class="form-control form-control-sm ml-2 datepicker-desde" id="fecha_desde"
                            name="fecha_desde" placeholder="dd/mm/yyyy" autocomplete="off">
                    </div>
                    <div class="form-group ml-3">
                        <label for="fecha_hasta">Hasta:</label>
                        <input type="text" class="form-control form-control-sm ml-2 datepicker-hasta" id="fecha_hasta"
                            name="fecha_hasta" placeholder="dd/mm/yyyy" autocomplete="off">
                    </div>
                </div>

                <div class="form-group ml-3">
                    <label for="tramo_filter">Tramo:</label>
                    <select class="form-control form-control-sm ml-2" id="tramo_filter" name="tramo_filter">
                        <option value="">Todos</option>
                        <option value="0">T0 (0-6 días)</option>
                        <option value="1">T1 (7-14 días)</option>
                        <option value="2">T2 (15-21 días)</option>
                        <option value="3">T3 (22-30 días)</option>
                        <option value="4">T4 (31+ días)</option>
                        <option value="5">Mora</option>
                    </select>
                </div>

                <div class="form-group ml-3">
                    <label for="zona_filter">Zona:</label>
                    <select class="form-control form-control-sm ml-2" id="zona_filter" name="zona_id">
                        <option value="">Todas</option>
                        @foreach ($zonas as $zona)
                            <option value="{{ $zona->id }}">{{ $zona->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Estados Crediticios -->
                <div class="form-group ml-3">
                    <label class="d-block text-muted small mb-1">Estados Crediticios:</label>
                    <div class="btn-group-toggle d-flex flex-wrap" data-toggle="buttons">
                        <label class="btn btn-outline-success btn-sm mr-2 mb-2 estado-btn">
                            <input type="checkbox" name="estado[]" value="ACTIVO"> ACTIVO
                        </label>
                        <label class="btn btn-outline-secondary btn-sm mr-2 mb-2 estado-btn">
                            <input type="checkbox" name="estado[]" value="INACTIVO"> INACTIVO
                        </label>
                        <label class="btn btn-outline-warning btn-sm mr-2 mb-2 estado-btn">
                            <input type="checkbox" name="estado[]" value="EN MORA/ACTIVA"> EN MORA/ACTIVA
                        </label>
                        <label class="btn btn-outline-warning btn-sm mr-2 mb-2 estado-btn">
                            <input type="checkbox" name="estado[]" value="EN MORA/INACTIVA"> EN MORA/INACTIVA
                        </label>
                        <label class="btn btn-outline-danger btn-sm mr-2 mb-2 estado-btn">
                            <input type="checkbox" name="estado[]" value="CREDITO VENCIDO/ACTIVO"> CREDITO VENCIDO/ACTIVO
                        </label>
                        <label class="btn btn-outline-danger btn-sm mr-2 mb-2 estado-btn">
                            <input type="checkbox" name="estado[]" value="CREDITO VENCIDO/INACTIVO"> CREDITO
                            VENCIDO/INACTIVO
                        </label>
                    </div>
                </div>

                <button type="button" class="btn btn-primary btn-sm ml-auto" id="btnBuscar">
                    <i class="fas fa-search"></i> Buscar
                </button>

                <button type="reset" class="btn btn-outline-secondary btn-sm ml-2" id="btnLimpiar">
                    <i class="fas fa-eraser"></i> Limpiar
                </button>
            </form>
        </div>

        <!-- Contenido principal: 3 columnas -->
        <div class="zonificador-content">
            <!-- Panel Izquierdo: Clientes Encontrados -->
            <div class="clientes-panel">
                <div class="clientes-header">
                    <h5>Clientes: <span id="totalClientes">0</span></h5>
                    <span class="badge badge-light" id="tramoActualBadge">Sin filtro</span>
                </div>

                <div class="clientes-filtros">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="filtroAtendidos">
                        <label class="custom-control-label" for="filtroAtendidos">Filtrar atendidos</label>
                    </div>
                </div>

                <div class="clientes-lista" id="clientesLista">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-search fa-3x mb-3"></i>
                        <p>Use los filtros para buscar clientes</p>
                    </div>
                </div>

                <div class="clientes-footer">
                    <button type="button" class="btn btn-primary btn-block" id="btnSeleccionar">
                        <i class="fas fa-check"></i> Seleccionar
                    </button>
                </div>
            </div>

            <!-- Panel Central: Mapa -->
            <div class="mapa-panel">
                <div class="map-controls">
                    <button type="button" class="btn btn-sm btn-info" id="btnDrawPolygon">
                        <i class="fas fa-draw-polygon"></i> Dibujar Zona
                    </button>
                    <button type="button" class="btn btn-sm btn-warning" id="btnClearPolygons">
                        <i class="fas fa-trash"></i> Limpiar Zonas
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" id="btnCenterMap">
                        <i class="fas fa-crosshairs"></i> Centrar
                    </button>
                </div>
                <div id="map"></div>
            </div>

            <!-- Panel Derecho: Asignar Ruta -->
            <div class="asignar-panel">
                <div class="asignar-header">
                    <h5>Asignar Ruta</h5>
                    <small class="text-muted">Clientes Seleccionados: <span id="clientesSeleccionados">0</span></small>
                </div>

                <div class="asignar-content">
                    <div class="form-group">
                        <label for="personal">
                            <i class="fas fa-user"></i> Personal
                        </label>
                        <select class="form-control" id="personal">
                            <option value="">Seleccionar personal</option>
                            @foreach ($usuarios as $usuario)
                                @php
                                    $nombreCompleto = '';
                                    if ($usuario->persona) {
                                        $nombreCompleto = trim(
                                            ($usuario->persona->nombres ?? '') .
                                                ' ' .
                                                ($usuario->persona->ape_pat ?? '') .
                                                ' ' .
                                                ($usuario->persona->ape_mat ?? ''),
                                        );
                                    }
                                    $nombreMostrar = $nombreCompleto ?: $usuario->name;
                                @endphp
                                <option value="{{ $usuario->id }}">
                                    {{ $nombreMostrar }} ({{ $usuario->codigo ?? 'Sin código' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="detalles">
                            <i class="fas fa-route"></i> Detalles de Ruta
                        </label>
                        <input type="text" class="form-control" id="detalles"
                            placeholder="Ej: Ruta Mañana - Zona Norte">
                    </div>

                    <div class="rutas-info" id="rutasInfo">
                        <p class="text-muted text-center">No hay clientes seleccionados</p>
                    </div>

                    <button type="button" class="btn btn-primary btn-crear-ruta mt-3" id="btnCrearRuta">
                        <i class="fas fa-plus"></i> Crear Ruta
                    </button>
                </div>
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
    {{-- Bootstrap Datepicker JS --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.es.min.js">
    </script>

    <script>
        // Variables globales
        let map;
        let markers = [];
        let polygons = [];
        let drawingManager;
        let selectedClientes = [];
        let infoWindow;
        let clientesData = [];

        // Función de inicialización del mapa
        function initMap() {
            // Centro en Lima, Perú
            const center = {
                lat: -12.0464,
                lng: -77.0428
            };

            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 12,
                center: center,
                mapTypeControl: true,
                fullscreenControl: true,
                streetViewControl: true,
                zoomControl: true
            });

            infoWindow = new google.maps.InfoWindow();

            drawingManager = new google.maps.drawing.DrawingManager({
                drawingMode: null,
                drawingControl: false,
                polygonOptions: {
                    fillColor: '#4285F4',
                    fillOpacity: 0.3,
                    strokeWeight: 2,
                    strokeColor: '#4285F4',
                    clickable: true,
                    editable: true,
                    draggable: true
                }
            });
            drawingManager.setMap(map);

            google.maps.event.addListener(drawingManager, 'polygoncomplete', function(polygon) {
                polygons.push(polygon);
                drawingManager.setDrawingMode(null);
                document.getElementById('btnDrawPolygon').classList.remove('active');
                selectClientsInPolygon(polygon);
            });

            initEventListeners();
            initDatepickers();
        }

        // Inicializar datepickers
        function initDatepickers() {
            $('.datepicker-dia').datepicker({
                format: 'dd/mm/yyyy',
                language: 'es',
                autoclose: true,
                todayHighlight: true,
                clearBtn: true,
                orientation: 'bottom auto'
            });

            $('.datepicker-mes').datepicker({
                format: 'mm/yyyy',
                language: 'es',
                autoclose: true,
                todayHighlight: true,
                clearBtn: true,
                minViewMode: 'months',
                orientation: 'bottom auto'
            });

            $('.datepicker-desde').datepicker({
                format: 'dd/mm/yyyy',
                language: 'es',
                autoclose: true,
                todayHighlight: true,
                clearBtn: true,
                orientation: 'bottom auto'
            }).on('changeDate', function(e) {
                $('.datepicker-hasta').datepicker('setStartDate', e.date);
            });

            $('.datepicker-hasta').datepicker({
                format: 'dd/mm/yyyy',
                language: 'es',
                autoclose: true,
                todayHighlight: true,
                clearBtn: true,
                orientation: 'bottom auto'
            }).on('changeDate', function(e) {
                $('.datepicker-desde').datepicker('setEndDate', e.date);
            });
        }

        // Manejo del tipo de rango de fecha
        $('#tipo_rango_fecha').on('change', function() {
            const tipoRango = $(this).val();
            $('#fecha_dia, #fecha_mes, #fecha_entre').hide();

            if (tipoRango === 'dia') {
                $('#fecha_dia').show();
            } else if (tipoRango === 'mes') {
                $('#fecha_mes').show();
            } else if (tipoRango === 'entre_fechas') {
                $('#fecha_entre').show();
            }
        });

        // Buscar datos
        function buscarDatos() {
            const formData = new FormData(document.getElementById('filterForm'));
            const params = {};

            // Convertir FormData a objeto
            for (let [key, value] of formData.entries()) {
                if (value) params[key] = value;
            }

            // Agregar tramo si está seleccionado
            if ($('#tramo_filter').val()) {
                params.tramos = [$('#tramo_filter').val()];
            }

            $('#loadingOverlay').addClass('show');

            $.ajax({
                url: '{{ route('admin.zonificador-tramos.data') }}',
                method: 'POST',
                data: params,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        clientesData = response.data;
                        renderClientes(clientesData);
                        createMarkers(clientesData);
                        $('#totalClientes').text(clientesData.length);

                        // Actualizar badge del tramo
                        const tramoTexto = $('#tramo_filter option:selected').text();
                        $('#tramoActualBadge').text(tramoTexto);
                    }
                },
                error: function(xhr) {
                    console.error('Error:', xhr);
                    alert('Error al cargar los datos');
                },
                complete: function() {
                    $('#loadingOverlay').removeClass('show');
                }
            });
        }

        // Renderizar lista de clientes
        function renderClientes(clientes) {
            const lista = $('#clientesLista');
            lista.empty();

            if (clientes.length === 0) {
                lista.html(`
            <div class="text-center text-muted py-5">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>No se encontraron clientes</p>
            </div>
        `);
                return;
            }

            clientes.forEach(cliente => {
                const iniciales = cliente.nombre.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                const item = `
            <div class="cliente-item" data-id="${cliente.id}">
                <input type="checkbox" class="cliente-checkbox" data-id="${cliente.id}">
                <div class="cliente-avatar">${iniciales}</div>
                <div class="cliente-info">
                    <div class="cliente-nombre">${cliente.nombre}</div>
                    <div class="cliente-direccion">${cliente.direccion || 'Sin dirección'} - S/ ${cliente.deudaReal.toFixed(2)}</div>
                </div>
            </div>
        `;
                lista.append(item);
            });

            // Agregar event listeners a los checkboxes
            $('.cliente-checkbox').on('change', updateSelectedClientes);
        }

        // Crear marcadores en el mapa
        function createMarkers(clientes) {
            // Limpiar marcadores existentes
            markers.forEach(marker => marker.setMap(null));
            markers = [];

            clientes.forEach(cliente => {
                // Solo crear marcador si tiene coordenadas válidas
                if (cliente.latitud && cliente.longitud) {
                    const marker = new google.maps.Marker({
                        position: {
                            lat: parseFloat(cliente.latitud),
                            lng: parseFloat(cliente.longitud)
                        },
                        map: map,
                        title: cliente.nombre,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 8,
                            fillColor: getColorByTramo(cliente.tramo),
                            fillOpacity: 1,
                            strokeColor: '#ffffff',
                            strokeWeight: 2
                        },
                        clienteData: cliente
                    });

                    marker.addListener('click', function() {
                        showInfoWindow(marker, cliente);
                    });

                    markers.push(marker);
                }
            });

            // Ajustar vista del mapa para mostrar todos los marcadores
            if (markers.length > 0) {
                const bounds = new google.maps.LatLngBounds();
                markers.forEach(marker => bounds.extend(marker.getPosition()));
                map.fitBounds(bounds);
            }
        }

        // Obtener color según tramo
        function getColorByTramo(tramo) {
            const colores = {
                0: '#4CAF50', // Verde - T0
                1: '#FFC107', // Amarillo - T1
                2: '#FF9800', // Naranja - T2
                3: '#FF5722', // Rojo - T3
                4: '#F44336', // Rojo oscuro - T4
                5: '#9C27B0' // Púrpura - Mora
            };
            return colores[tramo] || '#4285F4';
        }

        // Mostrar ventana de información
        function showInfoWindow(marker, cliente) {
            const content = `
        <div class="info-content">
            <h6>${cliente.nombre}</h6>
            <p><strong>DNI:</strong> ${cliente.dni}</p>
            <p><strong>Dirección:</strong> ${cliente.direccion || 'N/A'}</p>
            <p><strong>Deuda:</strong> S/ ${cliente.deudaReal.toFixed(2)}</p>
            <p><strong>Tramo:</strong> T${cliente.tramo} (${cliente.diasAtraso} días)</p>
            <button class="btn btn-sm btn-primary mt-2" onclick="toggleCliente(${cliente.id})">
                <i class="fas fa-check"></i> Seleccionar
            </button>
        </div>
    `;
            infoWindow.setContent(content);
            infoWindow.open(map, marker);
        }

        // Seleccionar clientes dentro de un polígono
        function selectClientsInPolygon(polygon) {
            clientesData.forEach(cliente => {
                if (cliente.latitud && cliente.longitud) {
                    const point = new google.maps.LatLng(parseFloat(cliente.latitud), parseFloat(cliente.longitud));

                    if (google.maps.geometry.poly.containsLocation(point, polygon)) {
                        const checkbox = $(`.cliente-checkbox[data-id="${cliente.id}"]`);
                        if (checkbox.length && !checkbox.is(':checked')) {
                            checkbox.prop('checked', true);
                        }
                    }
                }
            });
            updateSelectedClientes();
        }

        // Toggle selección de cliente
        function toggleCliente(clienteId) {
            const checkbox = $(`.cliente-checkbox[data-id="${clienteId}"]`);
            if (checkbox.length) {
                checkbox.prop('checked', !checkbox.is(':checked'));
                updateSelectedClientes();
            }
        }

        // Actualizar clientes seleccionados
        function updateSelectedClientes() {
            selectedClientes = [];

            $('.cliente-checkbox:checked').each(function() {
                const id = parseInt($(this).data('id'));
                const cliente = clientesData.find(c => c.id === id);
                if (cliente) {
                    selectedClientes.push(cliente);

                    // Actualizar estilo
                    $(this).closest('.cliente-item').addClass('selected');

                    // Cambiar color del marcador
                    const marker = markers.find(m => m.clienteData.id === id);
                    if (marker) {
                        marker.setIcon({
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 10,
                            fillColor: '#FF5722',
                            fillOpacity: 1,
                            strokeColor: '#ffffff',
                            strokeWeight: 3
                        });
                    }
                }
            });

            // Remover selección
            $('.cliente-checkbox:not(:checked)').each(function() {
                $(this).closest('.cliente-item').removeClass('selected');

                const id = parseInt($(this).data('id'));
                const marker = markers.find(m => m.clienteData.id === id);
                if (marker) {
                    const cliente = marker.clienteData;
                    marker.setIcon({
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 8,
                        fillColor: getColorByTramo(cliente.tramo),
                        fillOpacity: 1,
                        strokeColor: '#ffffff',
                        strokeWeight: 2
                    });
                }
            });

            $('#clientesSeleccionados').text(selectedClientes.length);
            updateRutasPanel();
        }

        // Actualizar panel de rutas
        function updateRutasPanel() {
            const panel = $('#rutasInfo');
            panel.empty();

            if (selectedClientes.length === 0) {
                panel.html('<p class="text-muted text-center">No hay clientes seleccionados</p>');
                return;
            }

            selectedClientes.forEach((cliente, index) => {
                const iniciales = cliente.nombre.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                const item = `
            <div class="ruta-item">
                <div class="ruta-header">
                    <div class="ruta-avatar">${iniciales}</div>
                    <div class="ruta-nombre">${cliente.nombre}</div>
                </div>
                <div class="ruta-detalles">
                    <div>${cliente.direccion || 'Sin dirección'}</div>
                    <div><strong>Orden:</strong> ${index + 1} <span class="ml-3"><strong>Deuda:</strong> S/ ${cliente.deudaReal.toFixed(2)}</span></div>
                    <div><strong>Tramo:</strong> T${cliente.tramo} (${cliente.diasAtraso} días de atraso)</div>
                </div>
            </div>
        `;
                panel.append(item);
            });
        }

        // Inicializar event listeners
        function initEventListeners() {
            $('#btnBuscar').on('click', buscarDatos);

            $('#btnLimpiar').on('click', function() {
                $('#filterForm')[0].reset();
                $('#fecha_dia, #fecha_mes, #fecha_entre').hide();
                clientesData = [];
                selectedClientes = [];
                renderClientes([]);
                markers.forEach(m => m.setMap(null));
                markers = [];
                $('#totalClientes').text('0');
                $('#tramoActualBadge').text('Sin filtro');
                updateRutasPanel();
            });

            $('#btnSeleccionar').on('click', function() {
                if (selectedClientes.length > 0) {
                    alert(`${selectedClientes.length} clientes seleccionados para asignar ruta`);
                } else {
                    alert('Seleccione al menos un cliente');
                }
            });

            $('#btnDrawPolygon').on('click', function() {
                if (drawingManager.getDrawingMode()) {
                    drawingManager.setDrawingMode(null);
                    $(this).removeClass('active');
                } else {
                    drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYGON);
                    $(this).addClass('active');
                }
            });

            $('#btnClearPolygons').on('click', function() {
                polygons.forEach(polygon => polygon.setMap(null));
                polygons = [];
            });

            $('#btnCenterMap').on('click', function() {
                if (markers.length > 0) {
                    const bounds = new google.maps.LatLngBounds();
                    markers.forEach(marker => bounds.extend(marker.getPosition()));
                    map.fitBounds(bounds);
                } else {
                    map.setCenter({
                        lat: -12.0464,
                        lng: -77.0428
                    });
                    map.setZoom(12);
                }
            });

            $('#btnCrearRuta').on('click', function() {
                const personal = $('#personal').val();
                const detalles = $('#detalles').val();

                if (!personal) {
                    alert('Seleccione un personal');
                    return;
                }

                if (selectedClientes.length === 0) {
                    alert('Seleccione al menos un cliente');
                    return;
                }

                // Aquí enviarías los datos al backend
                console.log({
                    personal,
                    detalles,
                    clientes: selectedClientes
                });

                alert(`Ruta creada exitosamente con ${selectedClientes.length} clientes`);
            });
        }

        window.initMap = initMap;
        window.toggleCliente = toggleCliente;
    </script>

    <!-- Cargar Google Maps API -->
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBHwPMi4c9dgvdSCm1rNGaLYLRp0AX8UWY&libraries=drawing,geometry&callback=initMap">
    </script>
@stop
