@extends('layouts.admin')
@section('title', 'Vincular Préstamos')

@section('content')
<div class="container-fluid pt-2">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Vincular Préstamos a Otros Clientes</h3>
            <div class="card-tools">
                <span class="badge badge-warning mr-2">Filtrado: Persona ID 2, Cliente ID 1</span>
                <span class="badge badge-info">Total: {{ $prestamos->total() }} préstamos</span>
            </div>
        </div>
        <div class="card-body">
            <!-- Filtros de búsqueda -->
            <form method="GET" action="{{ route('admin.prestamos.vincular.index') }}" id="filtros-form">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="fecha">Buscar por fecha primer pago:</label>
                        <input type="date" name="fecha" id="fecha" class="form-control" value="{{ request('fecha') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="dni">Buscar por DNI:</label>
                        <input type="text" name="dni" id="dni" class="form-control" placeholder="Ingrese DNI" value="{{ request('dni') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="cliente_id">Buscar por ID Cliente:</label>
                        <input type="text" name="cliente_id" id="cliente_id" class="form-control" placeholder="ID del cliente" value="{{ request('cliente_id') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="cliente">Buscar por cliente:</label>
                        <input type="text" name="cliente" id="cliente" class="form-control" placeholder="Nombre del cliente" value="{{ request('cliente') }}">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <a href="{{ route('admin.prestamos.vincular.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar Filtros
                        </a>
                    </div>
                </div>
            </form>
            
            <!-- Tabla de préstamos -->
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="prestamos-table">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID Préstamo</th>
                            <th>ID Cliente</th>
                            <th>DNI Cliente</th>
                            <th>Cliente</th>
                            <th>Sucursal</th>
                            <th>Monto Primer Pago</th>
                            <th>Cantidad Solicitada</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($prestamos as $prestamo)
                        <tr>
                            <td>{{ $prestamo->id }}</td>
                            <td>{{ $prestamo->cliente_id ?? 'N/A' }}</td>
                            <td>{{ $prestamo->cliente->persona->documento ?? 'N/A' }}</td>
                            <td>{{ ($prestamo->cliente->persona->nombres ?? '') }} {{ ($prestamo->cliente->persona->ape_pat ?? '') }} {{ ($prestamo->cliente->persona->ape_mat ?? '') }}</td>
                            <td>
                                @php
                                    // Intenta obtener la sucursal desde la tabla direcciones
                                    $direccion = $prestamo->cliente->persona->direcciones()->with('sucursal')->first();
                                    $sucursal = $direccion && $direccion->sucursal ? $direccion->sucursal->sucursal : null;
                                @endphp
                                
                                @if($sucursal)
                                    <span class="badge-assigned">{{ $sucursal }}</span>
                                @else
                                    <span class="badge-unassigned">Sin asignar</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $cuotaUno = $prestamo->cuotas->where('numero', 1)->first();
                                    $totalCuotas = $prestamo->cuotas->count();
                                @endphp
                                @if($cuotaUno)
                                    <small class="text-muted">
                                        {{ $cuotaUno->fecha_pago ? $cuotaUno->fecha_pago->format('d/m/Y') : 'S/F' }}
                                        @if($cuotaUno->fecha_pago)
                                            ({{ $cuotaUno->fecha_pago->locale('es')->dayName }})
                                        @endif
                                    </small><br>
                                    <strong class="text-success">S/. {{ number_format($cuotaUno->monto, 2) }}</strong>
                                    <small class="text-info ml-1">({{ $totalCuotas }} cuotas)</small>
                                @else
                                    <span class="text-muted">Sin cuota 1</span>
                                @endif
                            </td>
                            <td>S/. {{ number_format($prestamo->cantidad_solicitada, 2) }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning btn-modificar" 
                                        data-prestamo-id="{{ $prestamo->id }}"
                                        data-cliente-nombre="{{ ($prestamo->cliente->persona->nombres ?? '') }} {{ ($prestamo->cliente->persona->ape_pat ?? '') }} {{ ($prestamo->cliente->persona->ape_mat ?? '') }}"
                                        data-fecha-primer-pago="{{ $prestamo->fecha_primer_pago ? $prestamo->fecha_primer_pago->format('d/m/Y') : '' }}"
                                        data-monto-solicitado="S/. {{ number_format($prestamo->cantidad_solicitada, 2) }}"
                                        data-monto-primer-pago="{{ $cuotaUno ? 'S/. ' . number_format($cuotaUno->monto, 2) : 'N/A' }}">
                                    <i class="fas fa-edit"></i> Modificar
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    No se encontraron préstamos para mostrar.
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            @if($prestamos->hasPages())
            <div class="row">
                <div class="col-sm-12 col-md-5">
                    <div class="dataTables_info">
                        Mostrando {{ $prestamos->firstItem() }} a {{ $prestamos->lastItem() }} de {{ $prestamos->total() }} préstamos
                    </div>
                </div>
                <div class="col-sm-12 col-md-7">
                    <div class="dataTables_paginate paging_simple_numbers">
                        <ul class="pagination justify-content-end">
                            {{-- Previous Page Link --}}
                            @if ($prestamos->onFirstPage())
                                <li class="paginate_button page-item previous disabled">
                                    <span class="page-link">Anterior</span>
                                </li>
                            @else
                                <li class="paginate_button page-item previous">
                                    <a href="{{ $prestamos->appends(request()->query())->previousPageUrl() }}" class="page-link">Anterior</a>
                                </li>
                            @endif

                            {{-- Pagination Elements --}}
                            @foreach ($prestamos->appends(request()->query())->getUrlRange(1, $prestamos->lastPage()) as $page => $url)
                                @if ($page == $prestamos->currentPage())
                                    <li class="paginate_button page-item active">
                                        <span class="page-link">{{ $page }}</span>
                                    </li>
                                @else
                                    <li class="paginate_button page-item">
                                        <a href="{{ $url }}" class="page-link">{{ $page }}</a>
                                    </li>
                                @endif
                            @endforeach

                            {{-- Next Page Link --}}
                            @if ($prestamos->hasMorePages())
                                <li class="paginate_button page-item next">
                                    <a href="{{ $prestamos->appends(request()->query())->nextPageUrl() }}" class="page-link">Siguiente</a>
                                </li>
                            @else
                                <li class="paginate_button page-item next disabled">
                                    <span class="page-link">Siguiente</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal para vincular préstamo -->
<div class="modal fade" id="vincularModal" tabindex="-1" role="dialog" aria-labelledby="vincularModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vincularModalLabel">Vincular Préstamo a Otro Cliente</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="vincularForm" method="POST" action="{{ route('admin.prestamos.vincular') }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="prestamo_id" name="prestamo_id">
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="cliente_actual">Cliente Actual:</label>
                                <input type="text" id="cliente_actual" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="fecha_primer_pago_actual">Fecha Primer Pago:</label>
                                <input type="text" id="fecha_primer_pago_actual" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="monto_primer_pago_actual">Monto Primer Pago:</label>
                                <input type="text" id="monto_primer_pago_actual" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="monto_solicitado_actual">Monto Solicitado:</label>
                                <input type="text" id="monto_solicitado_actual" class="form-control" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="buscar_personas">Buscar Persona:</label>
                        <select id="buscar_personas" name="nueva_persona_id" class="form-control" style="width: 100%;">
                            <option value="">Buscar por DNI o nombre...</option>
                        </select>
                        <small class="form-text text-muted">Busque por DNI o nombre de la persona</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Importante:</strong> Al vincular el préstamo a otro cliente, todas las cuotas, pagos y gestiones asociadas se transferirán al nuevo cliente seleccionado.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn_vincular">Vincular Préstamo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css" rel="stylesheet" />
<style>
    .table th {
        font-weight: 600;
        color: #495057;
        background-color: #f8f9fa;
    }
    
    .select2-container--bootstrap4 .select2-selection {
        height: calc(2.25rem + 2px);
    }
    
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        line-height: calc(2.25rem);
    }
    
    /* Estilos para paginación AdminLTE */
    .dataTables_info {
        padding: 8px 10px;
        color: #6c757d;
        font-size: 0.875rem;
    }
    
    .dataTables_paginate {
        margin: 0;
        white-space: nowrap;
    }
    
    .paginate_button.page-item {
        margin: 0 1px;
    }
    
    .paginate_button.page-item .page-link {
        border: 1px solid #dee2e6;
        color: #007bff;
        padding: 6px 12px;
        font-size: 0.875rem;
    }
    
    .paginate_button.page-item.active .page-link {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
    }
    
    .paginate_button.page-item.disabled .page-link {
        color: #6c757d;
        background-color: #fff;
        border-color: #dee2e6;
        cursor: not-allowed;
    }
    
    .paginate_button.page-item .page-link:hover {
        background-color: #e9ecef;
        border-color: #dee2e6;
    }
    
    .paginate_button.page-item.active .page-link:hover {
        background-color: #0056b3;
        border-color: #004085;
    }

    /* Estilos para loading */
    .btn-loading {
        pointer-events: none;
        opacity: 0.7;
    }
</style>
@stop

@section('js')
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    console.log('🚀 Documento listo');
    
    // Verificar que jQuery y dependencias estén cargadas
    if (typeof $ === 'undefined') {
        console.error('❌ jQuery no está cargado');
        return;
    }
    
    if (typeof Swal === 'undefined') {
        console.error('❌ SweetAlert2 no está cargado');
    }
    
    // =====================================================
    // INICIALIZACIÓN DE DATATABLE (SIN FUNCIONALIDADES AJAX)
    // =====================================================
    if ($.fn.DataTable) {
        $('#prestamos-table').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
            },
            "responsive": true,
            "pageLength": 25,
            "order": [[ 0, "desc" ]],
            "paging": false,
            "searching": false,
            "info": false,
            "lengthChange": false
        });
    }

    // =====================================================
    // ABRIR MODAL CON DATOS DEL PRÉSTAMO
    // =====================================================
    $(document).on('click', '.btn-modificar', function() {
        console.log('📋 Abriendo modal para modificar préstamo');
        
        var prestamoId = $(this).data('prestamo-id');
        var clienteNombre = $(this).data('cliente-nombre');
        var fechaPrimerPago = $(this).data('fecha-primer-pago');
        var montoSolicitado = $(this).data('monto-solicitado');
        var montoPrimerPago = $(this).data('monto-primer-pago');
        
        console.log('📊 Datos del préstamo:', {
            prestamoId: prestamoId,
            clienteNombre: clienteNombre,
            fechaPrimerPago: fechaPrimerPago,
            montoSolicitado: montoSolicitado,
            montoPrimerPago: montoPrimerPago
        });
        
        // Llenar los campos del modal
        $('#prestamo_id').val(prestamoId);
        $('#cliente_actual').val(clienteNombre);
        $('#fecha_primer_pago_actual').val(fechaPrimerPago);
        $('#monto_primer_pago_actual').val(montoPrimerPago || 'N/A');
        $('#monto_solicitado_actual').val(montoSolicitado);
        
        // Limpiar y reinicializar Select2
        $('#buscar_personas').val('').trigger('change');
        
        // Abrir modal
        $('#vincularModal').modal('show');
        
        // Reinicializar Select2 después de abrir el modal
        setTimeout(function() {
            if ($('#buscar_personas').hasClass('select2-hidden-accessible')) {
                $('#buscar_personas').select2('destroy');
            }
            initializeSelect2();
        }, 500);
    });

    // =====================================================
    // FUNCIÓN PARA INICIALIZAR SELECT2
    // =====================================================
    function initializeSelect2() {
        console.log('🔧 Inicializando Select2 para buscar personas...');
        
        $('#buscar_personas').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#vincularModal'),
            ajax: {
                url: "{{ route('admin.personas.buscar') }}",
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    console.log('🔍 Enviando búsqueda:', params.term);
                    return {
                        q: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function (data, params) {
                    console.log('📥 Datos recibidos de personas:', data);
                    
                    if (!data.results) {
                        console.warn('⚠️ No se encontró el campo "results" en la respuesta');
                        return { results: [] };
                    }
                    
                    return {
                        results: data.results
                    };
                },
                cache: true
            },
            placeholder: 'Buscar por DNI o nombre...',
            minimumInputLength: 2,
            allowClear: true,
            templateResult: function(persona) {
                if (persona.loading) {
                    return persona.text;
                }
                
                if (!persona.id) {
                    return persona.text;
                }
                
                return $('<div>' + persona.text + '</div>');
            },
            templateSelection: function(persona) {
                return persona.text || persona.id || 'Seleccione una persona...';
            },
            language: {
                noResults: function() {
                    return "No se encontraron personas";
                },
                searching: function() {
                    return "Buscando...";
                },
                inputTooShort: function() {
                    return "Ingrese al menos 2 caracteres";
                },
                errorLoading: function() {
                    return "Error al cargar los resultados";
                }
            }
        }).on('select2:open', function() {
            console.log('📂 Select2 abierto');
        }).on('select2:select', function(e) {
            console.log('✅ Persona seleccionada:', e.params.data);
        });
    }

    // Inicializar Select2 por primera vez
    initializeSelect2();

    // =====================================================
    // ENVÍO DEL FORMULARIO DE VINCULACIÓN
    // =====================================================
    $(document).on('submit', '#vincularForm', function(e) {
        e.preventDefault();
        console.log('📤 Enviando formulario de vinculación');
        
        if (!$('#buscar_personas').val()) {
            mostrarMensaje('warning', 'Atención', 
                         'Debe seleccionar una persona para vincular el préstamo.');
            return false;
        }
        
        var formElement = this;
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción vinculará el préstamo al nuevo cliente seleccionado. ¿Desea continuar?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, vincular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Vinculando préstamo, por favor espere.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Enviar formulario
                formElement.submit();
            }
        });
    });
    
    // =====================================================
    // FUNCIÓN AUXILIAR PARA MOSTRAR MENSAJES
    // =====================================================
    function mostrarMensaje(icon, title, text, timer = null) {
        var config = {
            icon: icon,
            title: title,
            text: text,
            showConfirmButton: !timer
        };
        
        if (timer) {
            config.timer = timer;
        }
        
        Swal.fire(config);
    }
    
    // =====================================================
    // LIMPIAR MODAL AL CERRAR
    // =====================================================
    $('#vincularModal').on('hidden.bs.modal', function() {
        console.log('🔄 Limpiando modal al cerrar');
        $('#buscar_personas').val('').trigger('change');
        $('#vincularForm')[0].reset();
    });
    
    console.log('✅ Inicialización completa');
});
</script>
@stop