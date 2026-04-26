/**
 * Módulo de Deudas - JavaScript
 * Maneja filtros, exportación PDF y modales de previsualización
 */

(function ($) {
    'use strict';

    // Variables globales
    let timeoutId;
    let cuotaActualId = null;

    // Verificar que jQuery esté disponible
    if (typeof $ === 'undefined') {
        console.error('jQuery no está disponible en el módulo de deudas');
        return;
    }

    $(document).ready(function () {
        console.log('=== MÓDULO DEUDAS INICIADO ===');

        // Inicializar el módulo
        initializeDeudasModule();
    });

    function initializeDeudasModule() {
        console.log('Inicializando módulo de deudas...');

        // Configurar filtros
        setupFilters();

        // Configurar exportación
        setupPDFExport();

        // Configurar eventos de tabla
        setupTableEvents();

        // Configurar modal
        setupModal();

        // Configurar filtros dependientes
        setupDependentFilters();

        console.log('Módulo de deudas inicializado correctamente');
    }

    /**
     * Función centralizada para recopilar TODOS los filtros del formulario
     * Usada por performFilter, exportPDF y exportExcel
     */
    function getAllFilters() {
        // Recolectar sucursales desde checkboxes
        const sucursalesSeleccionadas = $('input[name="sucursal_id[]"]:checked').map(function() {
            return $(this).val();
        }).get();

        console.log('🔍 Sucursales seleccionadas (checkboxes):', sucursalesSeleccionadas);

        const filtros = {
            // Filtros principales
            search: $('#search').val() || '',
            cuotas_vencidas: $('#cuotas_vencidas').val() || '',
            tramo: $('input[name="tramo[]"]:checked').map(function(){ return $(this).val(); }).get(),
            tipo: $('#tipo').val() || '',
            estado_prestamo: $('#estado_prestamo').val() || '',

            // Filtros de fecha
            vencimiento_desde: $('.datepicker-desde').val() || '',
            vencimiento_hasta: $('.datepicker-hasta').val() || '',
            fecha_dia: $('.datepicker-dia').val() || '',
            fecha_mes: $('.datepicker-mes').val() || '',
            tipo_rango_fecha: $('#tipo_rango_fecha').val() || '',

            // Filtros avanzados
            jcc_id: $('#jcc_id').val() || '',
            asesor_id: $('#asesor_id').val() || '',
            analista_id: $('#analista_id').val() || '',
            zona_id: $('#zona_id').val() || '',
            sucursal_id: sucursalesSeleccionadas.length > 0 ? sucursalesSeleccionadas : '',
            tiene_gestion: $('#tiene_gestion').val() || '',
            tiene_compromiso: $('#tiene_compromiso').val() || '',

            // Filtros de mora (si existen)
            dias_mora_min: $('#dias_mora_min').val() || '',
            dias_mora_max: $('#dias_mora_max').val() || '',
        };

        // Limpiar valores vacíos para URL más limpia
        Object.keys(filtros).forEach(key => {
            const val = filtros[key];
            if (val === '' || val === null || val === undefined || (Array.isArray(val) && val.length === 0)) {
                delete filtros[key];
            }
        });

        return filtros;
    }

    /**
     * Serializar filtros a URL query string, manejando correctamente arrays
     * Similar a jQuery.param() con traditional: true
     */
    function serializeFilters(filtros) {
        const params = [];

        Object.keys(filtros).forEach(key => {
            const value = filtros[key];

            if (Array.isArray(value)) {
                // Para arrays, agregar cada valor con el mismo nombre de parámetro
                value.forEach(item => {
                    if (item !== '' && item !== null && item !== undefined) {
                        params.push(encodeURIComponent(key + '[]') + '=' + encodeURIComponent(item));
                    }
                });
            } else if (value !== '' && value !== null && value !== undefined) {
                // Para valores simples
                params.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
            }
        });

        return params.join('&');
    }

    function setupFilters() {
        // NOTA: No hay filtrado automático al cambiar valores
        // Todos los filtros requieren presionar el botón "Buscar"
        console.log('Filtros configurados - requieren botón Buscar');

        // Configurar periodos predefinidos (solo cambia valores, no filtra)
        $('.btn-period').on('click', function () {
            const period = $(this).data('period');
            console.log('Periodo seleccionado:', period);

            const today = new Date();
            let startDate = '';
            let endDate = '';

            $('.btn-period').removeClass('active');
            $(this).addClass('active');

            const formatDate = (date) => {
                const d = new Date(date);
                let month = '' + (d.getMonth() + 1);
                let day = '' + d.getDate();
                const year = d.getFullYear();

                if (month.length < 2) month = '0' + month;
                if (day.length < 2) day = '0' + day;

                return [year, month, day].join('-');
            };

            switch (period) {
                case 'today':
                    startDate = formatDate(today);
                    endDate = formatDate(today);
                    break;
                case 'week':
                    const firstDayOfWeek = new Date(today);
                    firstDayOfWeek.setDate(today.getDate() - today.getDay());
                    const lastDayOfWeek = new Date(today);
                    lastDayOfWeek.setDate(today.getDate() - today.getDay() + 6);
                    startDate = formatDate(firstDayOfWeek);
                    endDate = formatDate(lastDayOfWeek);
                    break;
                case 'month':
                    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                    const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    startDate = formatDate(firstDayOfMonth);
                    endDate = formatDate(lastDayOfMonth);
                    break;
                case 'all':
                    startDate = '';
                    endDate = '';
                    $(this).removeClass('active');
                    break;
            }

            $('#vencimiento_desde').val(startDate);
            $('#vencimiento_hasta').val(endDate);
            // NO llamar a performFilter() - requiere presionar botón Buscar
        });

        // Botón BUSCAR - Aplica todos los filtros
        $('#aplicar-filtros').on('click', function (e) {
            e.preventDefault();
            console.log('Botón Buscar presionado');
            performFilter();
        });

        // Botón LIMPIAR - Reset y recargar
        $('#limpiar-filtros').on('click', function (e) {
            e.preventDefault();
            console.log('Limpiando filtros...');

            // Limpiar todos los inputs y selects del formulario
            $('#filter-form')[0].reset();

            // Resetear select2 si existe
            if (typeof $.fn.select2 !== 'undefined') {
                $('.select2').val('').trigger('change');
            }

            $('.btn-period').removeClass('active');

            // Recargar sin filtros
            window.location.href = window.DeudasConfig?.routes?.index || window.location.pathname;
        });
    }

    function setupPDFExport() {
        console.log('Configurando botones de exportación...');
        console.log('Botón PDF encontrado:', $('#export-pdf').length);
        console.log('Botón Excel encontrado:', $('#export-excel').length);

        // Exportación PDF
        $('#export-pdf').click(function (e) {
            e.preventDefault();
            const boton = $(this);

            console.log('=== INICIANDO EXPORTACIÓN PDF ===');

            // Verificar si hay datos para exportar
            const clienteCount = $('#tabla-deudas').find('.cliente-header').length;
            console.log('Número de clientes encontrados:', clienteCount);

            if (clienteCount === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin datos',
                    text: 'No hay datos para exportar. Aplique filtros primero.',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            // Mostrar indicador de carga
            boton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Generando PDF...');

            // Recopilar TODOS los filtros del formulario (misma lógica que performFilter)
            const filtros = getAllFilters();

            // Forzar parámetro de exportación
            filtros.export = 'pdf';

            console.log('Filtros para exportación PDF:', filtros);

            // Construir URL usando serialización correcta de arrays
            const baseUrl = window.location.pathname;
            const queryString = serializeFilters(filtros);
            const exportUrl = baseUrl + '?' + queryString;

            console.log('URL final de exportación:', exportUrl);

            try {
                const ventana = window.open(exportUrl, '_blank');

                if (!ventana || ventana.closed || typeof ventana.closed == 'undefined') {
                    console.error('Ventana bloqueada por popup blocker');

                    Swal.fire({
                        icon: 'error',
                        title: 'Ventana bloqueada',
                        text: 'Por favor, permite las ventanas emergentes y vuelve a intentarlo.',
                        confirmButtonText: 'Entendido'
                    });
                } else {
                    console.log('PDF abierto exitosamente en nueva ventana');
                }

            } catch (error) {
                console.error('Error al abrir ventana:', error);

                Swal.fire({
                    icon: 'error',
                    title: 'Error de exportación',
                    text: 'Error al generar PDF: ' + error.message,
                    confirmButtonText: 'Entendido'
                });
            }

            // Restaurar botón
            setTimeout(() => {
                boton.prop('disabled', false).html('<i class="fas fa-file-pdf mr-1"></i> Exportar PDF');
                console.log('=== EXPORTACIÓN PDF COMPLETADA ===');
            }, 4000);
        });

        // Exportación Excel
        $(document).on('click', '#export-excel', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const boton = $(this);

            console.log('=== INICIANDO EXPORTACIÓN EXCEL ===');
            console.log('Botón Excel clickeado:', boton.length);

            // Verificar si hay datos para exportar
            const clienteCount = $('#tabla-deudas').find('.cliente-header').length;
            console.log('Número de clientes encontrados:', clienteCount);

            if (clienteCount === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin datos',
                        text: 'No hay datos para exportar. Aplique filtros primero.',
                        confirmButtonText: 'Entendido'
                    });
                } else {
                    alert('No hay datos para exportar. Aplique filtros primero.');
                }
                return;
            }

            // Mostrar indicador de carga
            boton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Generando Excel...');

            // Recopilar TODOS los filtros del formulario (misma lógica que performFilter)
            const filtros = getAllFilters();

            // Forzar parámetro de exportación
            filtros.export = 'excel';

            console.log('Filtros para exportación Excel:', filtros);

            // Construir URL usando serialización correcta de arrays
            const baseUrl = window.location.pathname;
            const queryString = serializeFilters(filtros);
            const exportUrl = baseUrl + '?' + queryString;

            console.log('URL final de exportación Excel:', exportUrl);

            try {
                // Crear elemento de enlace para descarga
                const link = document.createElement('a');
                link.href = exportUrl;
                link.download = 'reporte_deudas_agrupado.xlsx';
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();

                // Limpiar
                setTimeout(() => {
                    document.body.removeChild(link);
                    console.log('Descarga de Excel iniciada');
                }, 100);

            } catch (error) {
                console.error('Error al iniciar descarga:', error);

                // Método alternativo: iframe
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = exportUrl;
                document.body.appendChild(iframe);

                // Remover el iframe después de un tiempo
                setTimeout(() => {
                    if (document.body.contains(iframe)) {
                        document.body.removeChild(iframe);
                    }
                    console.log('Descarga de Excel iniciada (método iframe)');
                }, 2000);
            }

            // Restaurar botón
            setTimeout(() => {
                boton.prop('disabled', false).html('<i class="fas fa-file-excel mr-1"></i> Exportar Excel');
                console.log('=== EXPORTACIÓN EXCEL COMPLETADA ===');
            }, 3000);
        });

        // Exportación de Tramos
        $(document).on('click', '#export-tramos', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const boton = $(this);

            console.log('=== INICIANDO EXPORTACIÓN TRAMOS ===');
            console.log('Botón Tramos clickeado:', boton.length);

            // Verificar si hay datos para exportar
            const clienteCount = $('#tabla-deudas').find('.cliente-header').length;
            console.log('Número de clientes encontrados:', clienteCount);

            if (clienteCount === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin datos',
                        text: 'No hay datos para exportar. Aplique filtros primero.',
                        confirmButtonText: 'Entendido'
                    });
                } else {
                    alert('No hay datos para exportar. Aplique filtros primero.');
                }
                return;
            }

            // Mostrar indicador de carga
            boton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Generando Tramos...');

            // Recopilar TODOS los filtros del formulario (misma lógica que performFilter)
            const filtros = getAllFilters();

            // Forzar parámetro de exportación de tramos
            filtros.export = 'tramos';

            console.log('Filtros para exportación Tramos:', filtros);

            // Construir URL usando serialización correcta de arrays
            const baseUrl = window.location.pathname;
            const queryString = serializeFilters(filtros);
            const exportUrl = baseUrl + '?' + queryString;

            console.log('URL final de exportación Tramos:', exportUrl);

            try {
                // Crear elemento de enlace para descarga
                const link = document.createElement('a');
                link.href = exportUrl;
                link.download = 'reporte_tramos.xlsx';
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();

                // Limpiar
                setTimeout(() => {
                    document.body.removeChild(link);
                    console.log('Descarga de Tramos iniciada');
                }, 100);

            } catch (error) {
                console.error('Error al iniciar descarga:', error);

                // Método alternativo: iframe
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = exportUrl;
                document.body.appendChild(iframe);

                // Remover el iframe después de un tiempo
                setTimeout(() => {
                    if (document.body.contains(iframe)) {
                        document.body.removeChild(iframe);
                    }
                    console.log('Descarga de Tramos iniciada (método iframe)');
                }, 2000);
            }

            // Restaurar botón
            setTimeout(() => {
                boton.prop('disabled', false).html('<i class="fas fa-layer-group mr-1"></i> Exportar Tramos');
                console.log('=== EXPORTACIÓN TRAMOS COMPLETADA ===');
            }, 3000);
        });
    }

    function setupTableEvents() {
        // PAGINACIÓN: Eventos de paginación AJAX
        $(document).on('click', '.pagination a', function (e) {
            e.preventDefault();
            const url = $(this).attr('href');

            if (url) {
                console.log('Navegando a página:', url);
                loadPage(url);
            }
        });

        // Eventos del acordeón - DELEGACIÓN GLOBAL
        $(document).on('click', '.cliente-header, .toggle-cliente', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const clienteId = $(this).data('cliente') || $(this).closest('[data-cliente]').data('cliente');
            console.log('=== CLICK DETECTADO ===');
            console.log('Elemento clickeado:', $(this)[0]);
            console.log('Toggle cliente:', clienteId);
            console.log('Clases del elemento:', $(this).attr('class'));

            if (clienteId) {
                toggleCliente(clienteId);
            } else {
                console.error('No se pudo obtener clienteId del elemento clickeado');
            }
        });

        // Eventos de expandir/contraer todo
        $(document).on('click', '#expand-all', function (e) {
            e.preventDefault();

            const isExpanded = $(this).hasClass('expanded');

            if (!isExpanded) {
                // Expandir todos
                $('.cuota-detalle').removeClass('d-none');
                $('.toggle-cliente i').removeClass('fa-chevron-right').addClass('fa-chevron-down');
                $('.toggle-cliente').addClass('expanded');
                $(this).addClass('expanded');
                $(this).html('<i class="fas fa-compress-arrows-alt"></i>');
            } else {
                // Contraer todos
                $('.cuota-detalle').addClass('d-none');
                $('.toggle-cliente i').removeClass('fa-chevron-down').addClass('fa-chevron-right');
                $('.toggle-cliente').removeClass('expanded');
                $(this).removeClass('expanded');
                $(this).html('<i class="fas fa-expand-arrows-alt"></i>');
            }
        });

        // Eventos de previsualización
        $(document).on('click', '.btn-ver-preview', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const cuotaId = $(this).data('cuota-id');
            console.log('Botón previsualización clickeado, cuota:', cuotaId);
            verPrevisualizacion(cuotaId);
        });

        // Eventos de descarga PDF individual
        $(document).on('click', '.btn-descargar-pdf', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const nombreCliente = $(this).data('cliente-nombre');
            const url = $(this).attr('href');

            if (confirm('¿Deseas descargar el estado de cobranza de ' + nombreCliente + '?')) {
                window.open(url, '_blank');
            }
        });
    }

    function setupModal() {
        // Eventos del modal de previsualización
        $('#btn-descargar-pdf').on('click', function () {
            if (cuotaActualId) {
                const downloadUrl = (window.DeudasConfig?.routes?.descargar || window.location.pathname.replace('/deudas', '/deudas/descargar-estado-cobranza/')) + cuotaActualId;
                console.log('Descargando PDF desde:', downloadUrl);
                window.open(downloadUrl, '_blank');
            }
        });

        $('#btn-imprimir').on('click', function () {
            const contenido = document.getElementById('contenido-preview').innerHTML;

            if (!contenido || contenido.trim() === '') {
                alert('No hay contenido para imprimir. Por favor, espera a que cargue la previsualización.');
                return;
            }

            const ventanaImpresion = window.open('', '_blank');
            ventanaImpresion.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Estado de Cobranza</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; background: white; }
                        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                        table th, table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        table th { background-color: #1e4a72; color: white; font-weight: bold; }
                        @media print { body { margin: 0; padding: 15px; } .no-print { display: none !important; } }
                    </style>
                </head>
                <body>${contenido}</body>
                </html>
            `);

            ventanaImpresion.document.close();
            ventanaImpresion.onload = function () {
                setTimeout(function () {
                    ventanaImpresion.print();
                    ventanaImpresion.close();
                }, 250);
            };
        });

        // Limpiar variables al cerrar el modal
        $('#modalPrevisualizacion').on('hidden.bs.modal', function () {
            cuotaActualId = null;
            $('#contenido-preview').empty();
            $('#modalPrevisualizacionLabel').html('<i class="fas fa-file-pdf mr-2"></i>Previsualización - Estado de Cobranza');
        });
    }

    function setupDependentFilters() {
        // Filtros dependientes - Zona → Sucursal
        // NOTA: Solo actualizan las opciones, NO aplican filtros automáticamente

        // Cuando se cambia la zona, cargar sucursales y mostrar/ocultar el campo
        $('#zona_id').change(function () {
            const zonaId = $(this).val();
            console.log('Zona seleccionada:', zonaId);

            if (zonaId && zonaId.length > 0) {
                // Mostrar campo sucursal y cargar opciones
                console.log('Cargando sucursales para zona(s):', zonaId);
                $('#sucursal-container').slideDown(200);
                loadSucursalesByZona(zonaId, false);
            } else {
                // Ocultar campo sucursal y limpiar selección
                console.log('Limpiando sucursales');
                $('#sucursal-container').slideUp(200);
                $('#sucursal_id').val(null);
                if (typeof $.fn.select2 !== 'undefined') {
                    $('#sucursal_id').trigger('change.select2');
                }
            }
        });

        // Inicializar estado del campo sucursal al cargar
        if ($('#zona_id').val()) {
            $('#sucursal-container').show();
            loadSucursalesByZona($('#zona_id').val(), false);
        } else {
            $('#sucursal-container').hide();
        }
    }

    // PAGINACIÓN: Función para cargar una página específica
    function loadPage(url) {
        console.log('Cargando página:', url);

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'html',
            beforeSend: function () {
                $('#tabla-deudas').html('<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br><small class="text-muted">Cargando datos...</small></div>');
                // Scroll suave hacia arriba
                $('html, body').animate({ scrollTop: $('#tabla-deudas').offset().top - 100 }, 400);
            },
            success: function (response) {
                console.log('Página cargada exitosamente');

                if (typeof response === 'string') {
                    $('#tabla-deudas').html(response);
                } else {
                    console.error('Respuesta AJAX inválida:', response);
                    $('#tabla-deudas').html('<div class="alert alert-warning m-3"><i class="fas fa-exclamation-triangle mr-1"></i> Error en la respuesta del servidor.</div>');
                    return;
                }

                // Actualizar contador de clientes - Leer del servidor
                const totalClientesServidor = $('#total-clientes-servidor').val();
                const clienteCount = totalClientesServidor ? parseInt(totalClientesServidor) : $('#tabla-deudas').find('.cliente-header').length;
                $('#contador-clientes').text(clienteCount);
                console.log('Total clientes (servidor):', clienteCount);
            },
            error: function (xhr) {
                console.error('Error al cargar página:', xhr);
                $('#tabla-deudas').html('<div class="alert alert-danger m-3"><i class="fas fa-exclamation-triangle mr-1"></i> Error al cargar los datos. Código: ' + xhr.status + '</div>');
            }
        });
    }

    // Función de filtrado principal - INCLUYE TODOS LOS FILTROS
    function performFilter() {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(function () {
            console.log('=== APLICANDO TODOS LOS FILTROS ===');

            // Usar función centralizada para recopilar filtros
            const filtros = getAllFilters();

            console.log('Filtros a aplicar:', filtros);

            $.ajax({
                url: window.DeudasConfig?.routes?.index || window.location.pathname,
                type: 'GET',
                data: filtros,
                dataType: 'html',
                traditional: false, // false para que jQuery agregue [] a arrays y PHP/Laravel los reciba correctamente
                beforeSend: function () {
                    $('#tabla-deudas').html('<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br><small class="text-muted">Cargando datos...</small></div>');
                    // Deshabilitar botón mientras carga
                    $('#aplicar-filtros').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Buscando...');
                },
                success: function (response) {
                    console.log('Filtros aplicados exitosamente');

                    if (typeof response === 'string') {
                        $('#tabla-deudas').html(response);
                    } else {
                        console.error('Respuesta AJAX inválida:', response);
                        $('#tabla-deudas').html('<div class="alert alert-warning m-3"><i class="fas fa-exclamation-triangle mr-1"></i> Error en la respuesta del servidor.</div>');
                        return;
                    }

                    // Actualizar contador de clientes - Leer del servidor, no contar elementos
                    const totalClientesServidor = $('#total-clientes-servidor').val();
                    const clienteCount = totalClientesServidor ? parseInt(totalClientesServidor) : $('#tabla-deudas').find('.cliente-header').length;
                    $('#contador-clientes').text(clienteCount);
                    console.log('Total clientes (servidor):', clienteCount);

                    // Actualizar URL del navegador sin recargar
                    const newUrl = window.location.pathname + '?' + $.param(filtros);
                    window.history.pushState({}, '', newUrl);
                },
                error: function (xhr) {
                    console.error('Error al aplicar filtros:', xhr);
                    $('#tabla-deudas').html('<div class="alert alert-danger m-3"><i class="fas fa-exclamation-triangle mr-1"></i> Error al cargar los datos. Código: ' + xhr.status + '</div>');
                },
                complete: function () {
                    // Restaurar botón
                    $('#aplicar-filtros').prop('disabled', false).html('<i class="fas fa-search mr-2"></i> Buscar');
                }
            });
        }, 100); // Reducido a 100ms ya que ahora es manual
    }

    // Función para toggle individual de cliente
    function toggleCliente(clienteId) {
        console.log('=== EJECUTANDO TOGGLE CLIENTE ===');
        console.log('Cliente ID:', clienteId);

        const detalles = $(`.cuota-detalle[data-cliente="${clienteId}"]`);
        const boton = $(`.toggle-cliente[data-cliente="${clienteId}"]`);
        const icono = boton.find('i');

        console.log('Detalles encontrados:', detalles.length);
        console.log('Botón encontrado:', boton.length);
        console.log('Icono encontrado:', icono.length);
        console.log('Detalles tienen clase d-none:', detalles.hasClass('d-none'));

        if (detalles.length === 0) {
            console.error('No se encontraron detalles para el cliente:', clienteId);
            return;
        }

        if (detalles.hasClass('d-none')) {
            // Mostrar detalles
            console.log('Mostrando detalles...');
            detalles.removeClass('d-none');
            icono.removeClass('fa-chevron-right').addClass('fa-chevron-down');
            boton.addClass('expanded');
        } else {
            // Ocultar detalles
            console.log('Ocultando detalles...');
            detalles.addClass('d-none');
            icono.removeClass('fa-chevron-down').addClass('fa-chevron-right');
            boton.removeClass('expanded');
        }

        console.log('=== TOGGLE COMPLETADO ===');
    }

    // Función de previsualización
    function verPrevisualizacion(cuotaId) {
        cuotaActualId = cuotaId;

        console.log('Iniciando previsualización para cuota:', cuotaId);

        // Mostrar el modal
        $('#modalPrevisualizacion').modal('show');

        // Resetear el estado del modal
        $('#loading-preview').show();
        $('#contenido-preview').hide();
        $('#error-preview').hide();
        $('#btn-descargar-pdf').prop('disabled', true);
        $('#btn-imprimir').prop('disabled', true);

        // Construir la URL correctamente
        const url = (window.DeudasConfig?.routes?.previsualizacion || window.location.pathname.replace('/deudas', '/deudas/previsualizacion-estado-cobranza/')) + cuotaId;
        console.log('URL de previsualización:', url);

        // Cargar la previsualización via AJAX
        $.ajax({
            url: url,
            type: 'GET',
            timeout: 30000,
            success: function (response) {
                console.log('Previsualización cargada exitosamente');
                $('#loading-preview').hide();
                $('#contenido-preview').html(response).show();
                $('#btn-descargar-pdf').prop('disabled', false);
                $('#btn-imprimir').prop('disabled', false);

                // Actualizar el título con información del cliente
                const clienteNombre = extraerNombreCliente(response);
                if (clienteNombre) {
                    $('#modalPrevisualizacionLabel').html(`
                        <i class="fas fa-file-pdf mr-2"></i>
                        Estado de Cobranza - ${clienteNombre}
                    `);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error al cargar previsualización:', xhr);
                $('#loading-preview').hide();
                $('#error-preview').show();
            }
        });
    }

    // Función para extraer el nombre del cliente del HTML de respuesta
    function extraerNombreCliente(html) {
        try {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const elemento = doc.querySelector('.user-name b');
            return elemento ? elemento.textContent.trim() : null;
        } catch (e) {
            console.error('Error al extraer nombre del cliente:', e);
            return null;
        }
    }

    // Funciones para filtros dependientes
    // NOTA: Ya no ejecutan performFilter automáticamente
    function loadZonasBySucursal(sucursalId, autoFilter) {
        console.log('Cargando zonas para sucursal:', sucursalId);

        const baseUrl = window.DeudasConfig?.routes?.zonasBySucursal || (window.location.pathname.replace('/deudas', '/deudas/zonas-by-sucursal'));

        $.ajax({
            url: baseUrl,
            type: 'GET',
            data: { sucursal_id: sucursalId },
            success: function (data) {
                const currentZonaId = $('#zona_id').val();
                $('#zona_id').empty();
                $('#zona_id').append('<option value="">Todas</option>');

                $.each(data, function (index, zona) {
                    $('#zona_id').append('<option value="' + zona.id + '">' + zona.nombre + '</option>');
                });

                if (currentZonaId && data.some(zona => zona.id == currentZonaId)) {
                    $('#zona_id').val(currentZonaId);
                }

                // Solo filtrar si autoFilter es true
                if (autoFilter === true) {
                    performFilter();
                }
            },
            error: function (xhr) {
                console.error('Error al cargar zonas:', xhr);
            }
        });
    }

    function loadSucursalesByZona(zonaId, autoFilter) {
        console.log('Cargando sucursales para zona(s):', zonaId);

        const baseUrl = window.DeudasConfig?.routes?.sucursalesByZona || (window.location.pathname.replace('/deudas', '/deudas/sucursales-by-zona'));

        // Preparar datos para enviar - manejar arrays y valores simples
        let requestData = {};
        if (Array.isArray(zonaId)) {
            // Si es un array, enviar cada ID con el mismo nombre de parámetro
            requestData = { 'zona_id': zonaId };
        } else if (zonaId) {
            // Si es un solo valor
            requestData = { 'zona_id': zonaId };
        }

        console.log('Datos de request:', requestData);

        $.ajax({
            url: baseUrl,
            type: 'GET',
            data: requestData,
            traditional: true, // Importante para enviar arrays correctamente
            success: function (data) {
                console.log('Sucursales recibidas:', data.length, 'sucursales');
                const currentSucursalId = $('#sucursal_id').val();
                const $sucursalSelect = $('#sucursal_id');

                // Limpiar opciones actuales
                $sucursalSelect.empty();

                // Verificar si hay sucursales
                if (data && data.length > 0) {
                    // Agregar nuevas opciones
                    $.each(data, function (index, sucursal) {
                        $sucursalSelect.append('<option value="' + sucursal.id + '">' + sucursal.sucursal + '</option>');
                    });

                    // Restaurar valor previo si existe
                    if (currentSucursalId && Array.isArray(currentSucursalId)) {
                        // Si es array, filtrar solo los que existen en las nuevas opciones
                        const validIds = currentSucursalId.filter(id =>
                            data.some(sucursal => sucursal.id == id)
                        );
                        if (validIds.length > 0) {
                            $sucursalSelect.val(validIds);
                        }
                    } else if (currentSucursalId && data.some(sucursal => sucursal.id == currentSucursalId)) {
                        $sucursalSelect.val(currentSucursalId);
                    }

                    console.log('✓ Sucursales actualizadas:', data.length);
                } else {
                    $sucursalSelect.append('<option value="">No hay sucursales para esta(s) zona(s)</option>');
                    console.log('⚠ No se encontraron sucursales para las zonas seleccionadas');
                }

                // Notificar a Select2 que las opciones han cambiado
                if (typeof $.fn.select2 !== 'undefined') {
                    $sucursalSelect.trigger('change.select2');
                }

                // Solo filtrar si autoFilter es true
                if (autoFilter === true) {
                    performFilter();
                }
            },
            error: function (xhr) {
                console.error('Error al cargar sucursales:', xhr);
                const $sucursalSelect = $('#sucursal_id');
                $sucursalSelect.empty();
                $sucursalSelect.append('<option value="">Error al cargar sucursales</option>');
                if (typeof $.fn.select2 !== 'undefined') {
                    $sucursalSelect.trigger('change.select2');
                }
            }
        });
    }

    // Exponer funciones globalmente para compatibilidad
    window.verPrevisualizacion = verPrevisualizacion;
    window.toggleCliente = toggleCliente;

})(jQuery);