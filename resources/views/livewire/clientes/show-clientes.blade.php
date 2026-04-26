<div class="container-fluid px-0">
    <div class="w-100">
        <div class="card shadow-sm border-0 rounded-lg mb-4">
            <!-- Header con búsqueda y botón -->
            <div class="card-header bg-white p-3 border-0">
                <div class="row align-items-center g-3">
                    <!-- Título y Búsqueda -->
                    <div class="col-lg-3 col-md-3 mb-2 mb-lg-0">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" wire:model.live="search" class="form-control border-start-0"
                                   placeholder="Buscar por DNI, nombre o apellidos (tolerante a errores)"
                                   title="Búsqueda inteligente: encuentra coincidencias incluso con errores de escritura">
                        </div>
                    </div>
                    
                    <!-- Filtros simplificados -->
                    <div class="col-lg-6 col-md-7 mb-2 mb-lg-0">
                        <div class="d-flex justify-content-around flex-wrap gap-2">
                            <small class="text-muted align-self-center me-2">Filtros:</small>

                            <button wire:click="filtrarPorEstado('')"
                                    class="btn btn-sm {{ $estadoPrestamo === '' ? 'btn-primary' : 'btn-outline-primary' }}">
                                <i class="fas fa-list me-1"></i>Todos
                            </button>

                            <button wire:click="filtrarPorEstado('Sin préstamos')"
                                    class="btn btn-sm {{ $estadoPrestamo === 'Sin préstamos' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                                <i class="fas fa-user-times me-1"></i>Sin préstamos
                            </button>
                        </div>
                    </div>

                    <!-- Botones para móviles (visible solo en xs y sm) -->
                    <div class="col-md-2 col-12 d-block d-md-none mb-1">
                        <div class="row">
                            <div class="col-12">
                                <a href="{{ route('admin.clientes.create') }}" class="btn btn-danger w-100 btn-sm">
                                    <i class="fa-solid fa-user-plus me-1"></i>Nuevo
                                </a>
                            </div>
                            <!--div class="col-6">
                                <a href="{{ route('admin.clientes.importar') }}" class="btn btn-success w-100 btn-sm">
                                    <i class="fas fa-file-import me-1"></i>Importar
                                </a>
                            </div-->
                        </div>
                    </div>

                    <!-- Botones (visible para md y superiores) -->
                    <div class="col-md-2 col-lg-3 d-none d-lg-block text-end">
                        @if($search || $estadoPrestamo)
                            <button wire:click="clearFilters" class="btn btn-outline-secondary me-2" title="Limpiar filtros">
                                <i class="fas fa-times me-1"></i><span class="d-none d-xl-inline">Limpiar</span>
                            </button>
                        @endif
                        <!--a href="{{ route('admin.clientes.importar') }}" class="btn btn-success me-2">
                            <i class="fas fa-file-import me-1"></i><span class="d-none d-xl-inline">Importar</span> Excel
                        </a-->
                        <a href="{{ route('admin.clientes.create') }}" class="btn btn-danger">
                            <i class="fa-solid fa-user-plus me-1"></i><span class="d-none d-xl-inline">Nuevo</span> Cliente
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card Body -->
            <div class="card-body p-0">
                @if (isset($personas) && $personas->count())
                    <div class="table-responsive">
                        <table class="table table-hover border-0 mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th wire:click="order('id')" class="sortable cursor-pointer">
                                        <div class="d-flex align-items-center">
                                            <span>ID</span>
                                            <div class="sort-icon ms-1">
                                                @if ($sort == 'id')
                                                    @if ($direction == 'asc')
                                                        <i class="fas fa-sort-amount-down-alt"></i>
                                                    @else
                                                        <i class="fas fa-sort-amount-up-alt"></i>
                                                    @endif
                                                @else
                                                    <i class="fas fa-sort text-muted"></i>
                                                @endif
                                            </div>
                                        </div>
                                    </th>
                                    <th wire:click="order('documento')" class="sortable cursor-pointer">
                                        <div class="d-flex align-items-center">
                                            <span>DNI</span>
                                            <div class="sort-icon ms-1">
                                                @if ($sort == 'documento')
                                                    @if ($direction == 'asc')
                                                        <i class="fas fa-sort-numeric-down"></i>
                                                    @else
                                                        <i class="fas fa-sort-numeric-up-alt"></i>
                                                    @endif
                                                @else
                                                    <i class="fas fa-sort text-muted"></i>
                                                @endif
                                            </div>
                                        </div>
                                    </th>
                                    <th wire:click="order('nombres')" class="sortable cursor-pointer">
                                        <div class="d-flex align-items-center">
                                            <span>Nombres</span>
                                            <div class="sort-icon ms-1">
                                                @if ($sort == 'nombres')
                                                    @if ($direction == 'asc')
                                                        <i class="fas fa-sort-alpha-down"></i>
                                                    @else
                                                        <i class="fas fa-sort-alpha-up-alt"></i>
                                                    @endif
                                                @else
                                                    <i class="fas fa-sort text-muted"></i>
                                                @endif
                                            </div>
                                        </div>
                                    </th>
                                    <th wire:click="order('sucursal')" class="sortable cursor-pointer text-center">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <span>Creado por</span>
                                            <div class="sort-icon ms-1">
                                                @if ($sort == 'sucursal')
                                                    @if ($direction == 'asc')
                                                        <i class="fas fa-sort-alpha-down"></i>
                                                    @else
                                                        <i class="fas fa-sort-alpha-up-alt"></i>
                                                    @endif
                                                @else
                                                    <i class="fas fa-sort text-muted"></i>
                                                @endif
                                            </div>
                                        </div>
                                    </th>
                                    <th class="text-center">Sucursal</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Opciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($personas as $persona)
                                    <tr>
                                        <td class="align-middle">{{ $persona->id }}</td>
                                        <td class="align-middle">{{ $persona->documento }}</td>
                                        <td class="align-middle">
                                            <div class="fw-bold">{{ $persona->nombres }}</div>
                                            <div class="text-muted small">{{ $persona->ape_pat }} {{ $persona->ape_mat }}</div>
                                        </td>
                                        <td class="align-middle text-center">
                                            @if($persona->cliente && $persona->cliente->usuario)
                                                <div class="fw-bold">{{ $persona->cliente->usuario->codigo ?? ($persona->cliente->usuario->full_name ?? $persona->cliente->usuario->name) }}</div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="align-middle text-center">
                                            @if($persona->sucursal_nombre)
                                                <span class="badge badge-light" style="background-color: #e9ecef; color: #495057;">
                                                    {{ $persona->sucursal_nombre }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="align-middle text-center">
                                            @if(!$persona->cliente_id)
                                                <span class="badge badge-warning">No es cliente</span>
                                            @elseif($persona->cliente && $persona->cliente->prestamos && $persona->cliente->prestamos->count() > 0)
                                                @php
                                                    // Obtener el último préstamo
                                                    $ultimoPrestamo = $persona->cliente->prestamos->sortByDesc('created_at')->first();
                                                    // Determinar badge class según el estado de la tabla
                                                    $badgeClass = match($ultimoPrestamo->estado) {
                                                        'En Análisis' => 'badge-info',
                                                        'Aprobado' => 'badge-success',
                                                        'Por Desembolsar' => 'badge-warning',
                                                        'Vigente' => 'badge-primary',
                                                        'Vigente con Moras' => 'badge-danger',
                                                        'Moroso' => 'badge-danger',
                                                        'Nueva Solicitud' => 'badge-secondary',
                                                        'Finalizado' => 'badge-dark',
                                                        'Con Convenio' => 'badge-success',
                                                        'Cancelado' => 'badge-secondary',
                                                        'Liquidado' => 'badge-info',
                                                        'Anulado' => 'badge-secondary',
                                                        default => 'badge-light'
                                                    };
                                                @endphp
                                                <span class="badge {{ $badgeClass }}">
                                                    {{ $ultimoPrestamo->estado }}
                                                </span>
                                            @else
                                                <span class="text-muted">Sin préstamos</span>
                                            @endif
                                        </td>
                                        <td class="align-middle text-center">
                                            <div class="btn-group" role="group">
                                                @if(!$persona->cliente_id)
                                                    {{-- No es cliente: mostrar botón Completar datos --}}
                                                    <a href="{{ route('admin.clientes.create', ['persona_id' => $persona->id]) }}"
                                                       class="btn btn-sm btn-success"
                                                       title="Completar datos">
                                                        <i class="fas fa-user-plus"></i> Completar datos
                                                    </a>
                                                @else
                                                    {{-- Es cliente: mostrar botón Editar --}}
                                                    <a href="{{ route('admin.clientes.edit', ['cliente' => $persona->cliente_id]) }}"
                                                       class="btn btn-sm btn-primary"
                                                       title="Editar cliente">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    @php
                                                        // Determinar si se puede crear un nuevo préstamo
                                                        $puedeCrearPrestamo = true;
                                                        if ($persona->cliente && $persona->cliente->prestamos && $persona->cliente->prestamos->count() > 0) {
                                                            $ultimoPrestamo = $persona->cliente->prestamos->sortByDesc('created_at')->first();
                                                            $estadosFinalizados = ['Liquidado', 'Cancelado', 'Anulado'];
                                                            $puedeCrearPrestamo = in_array($ultimoPrestamo->estado, $estadosFinalizados);
                                                        }
                                                    @endphp

                                                    @if($puedeCrearPrestamo)
                                                        <a href="{{ route('admin.solicitudes.create', ['cliente_id' => $persona->cliente_id]) }}"
                                                           class="btn btn-sm btn-success"
                                                           title="Crear solicitud de préstamo">
                                                            <i class="fas fa-plus-circle"></i>
                                                        </a>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginación -->
                    @if ($personas->hasPages())
                        <div class="d-flex justify-content-between align-items-center py-3 px-3 bg-light border-top">
                            <div class="text-muted small">
                                Mostrando {{ $personas->firstItem() }} a {{ $personas->lastItem() }} de {{ $personas->total() }} registros
                            </div>
                            <div>
                                {{ $personas->links() }}
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <img src="{{ asset('img/no-data.png') }}" alt="Sin datos" class="img-fluid mb-3" style="max-width: 150px;">
                        <h5 class="text-muted">No se encontraron registros</h5>
                        <p class="text-muted">Intenta con otro término de búsqueda o crea un nuevo cliente</p>
                        <a href="{{ route('admin.clientes.create') }}" class="btn btn-primary mt-2">
                            <i class="fa-solid fa-plus-circle me-1"></i> Nuevo Cliente
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal para preguntar si desea crear préstamo -->
    <div class="modal fade" id="loanPromptModal" tabindex="-1" role="dialog" aria-labelledby="loanPromptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="loanPromptModalLabel">
                        <i class="fas fa-check-circle mr-2"></i>
                        Cliente Creado Exitosamente
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-check fa-3x text-success mb-3"></i>
                        <h6>El cliente <strong><span id="clienteNombre"></span></strong> ha sido creado correctamente.</h6>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-question-circle mr-2"></i>
                        <strong>¿Desea crear una solicitud de préstamo para este cliente?</strong>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>
                        No, continuar sin préstamo
                    </button>
                    <a href="#" id="createLoanBtn" class="btn btn-primary">
                        <i class="fas fa-plus-circle mr-1"></i>
                        Sí, crear préstamo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para mostrar etiquetas del cliente -->
    <div class="modal fade" id="etiquetasModal" tabindex="-1" role="dialog" aria-labelledby="etiquetasModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="etiquetasModalLabel">
                        <i class="fas fa-tags mr-2"></i>
                        Etiquetas del Cliente: <span id="nombreClienteModal"></span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="etiquetasContainer">
                        <!-- Las etiquetas se cargarán aquí dinámicamente -->
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando etiquetas...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('css')
        <style>
            /* Estilos generales */
            .card {
                transition: all 0.3s ease;
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
            .card-header .row {
                margin-left: 0;
                margin-right: 0;
            }
            .card-header .col-lg-3,
            .card-header .col-lg-9,
            .card-header .col-md-6,
            .card-header .col-12 {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .card:hover {
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            }
               
            .table-responsive {
                width: 100%;
            }

            .table {
                width: 100%;
                margin-bottom: 0;
                font-size: 0.95rem;
            }
            .container-fluid {
                max-width: 100% !important;
                width: 100% !important;
            }
            .table thead th {
                font-weight: 600;
                background-color: #f8f9fa;
                border-top: none;
                padding: 0.75rem 1rem;
            }

            .table tbody tr {
                border-bottom: 1px solid #f2f2f2;
            }

            .table tbody tr:last-child {
                border-bottom: none;
            }

            .sortable {
                cursor: pointer;
            }

            .sort-icon {
                display: inline-block;
                width: 16px;
            }

            .badge {
                font-weight: 500;
                padding: 0.35em 0.65em;
            }

            /* Dropdown de acciones */
            .dropdown-menu {
                min-width: 200px;
                padding: 0.5rem 0;
                border: none;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }

            .dropdown-item {
                padding: 0.5rem 1rem;
            }

            .dropdown-item:hover {
                background-color: #f8f9fa;
            }

            .dropdown-divider {
                margin: 0.25rem 0;
            }

            /* Paginación mejorada */
            .pagination {
                margin-bottom: 0;
                justify-content: center;
            }
            
            .page-item .page-link {
                border-radius: 0.375rem;
                margin: 0 0.125rem;
                padding: 0.375rem 0.75rem;
                color: #495057;
                border: 1px solid #dee2e6;
                background-color: #ffffff;
            }
            
            .page-item .page-link:hover {
                color: #435ebe;
                background-color: #e9ecef;
                border-color: #adb5bd;
            }
            
            .page-item.active .page-link {
                color: #fff;
                background-color: #435ebe;
                border-color: #435ebe;
            }
            
            .page-item.disabled .page-link {
                color: #6c757d;
                background-color: #fff;
                border-color: #dee2e6;
            }

            .page-link {
                border-radius: 0.25rem;
                margin: 0 0.125rem;
                padding: 0.375rem 0.75rem;
            }

            /* Responsive - tabla scroll horizontal */
            @media (max-width: 768px) {
                ..table-responsive {
                    width: 100%;
                }

                .table {
                    width: 100%;
                    margin-bottom: 0;
                }
                
                .badge {
                    font-size: 0.75rem;
                }
                
                .btn-sm {
                    padding: 0.25rem 0.5rem;
                }
            }

            /* Estados de colores */
            .badge.bg-primary {
                background-color: #435ebe !important;
            }
            .badge.bg-success {
                background-color: #4fbe87 !important;
            }
            .badge.bg-info {
                background-color: #3abaf4 !important;
            }
            .badge.bg-warning {
                background-color: #ffc107 !important;
            }
            .badge.bg-danger {
                background-color: #dc3545 !important;
            }
            .badge.bg-secondary {
                background-color: #6c757d !important;
            }
            
            /* Animaciones */
            .fade-in {
                animation: fadeIn 0.5s;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            /* Mejoras para diferentes dispositivos */
            @media (max-width: 576px) {
                .btn-estado {
                    font-size: 0.75rem;
                    padding: 0.25rem 0.75rem;
                }
                
                .badge {
                    font-size: 0.7rem;
                }
                
                .table td {
                    padding: 0.75rem 0.5rem;
                }
            }
            
            /* Mejoras para dispositivos medianos */
            @media (min-width: 768px) and (max-width: 992px) {
                .container-fluid {
                    padding-left: 1rem;
                    padding-right: 1rem;
                }
            }
            
            /* Soporte para modo oscuro si está habilitado en el sistema */
            @media (prefers-color-scheme: dark) {
                .table-hover tbody tr:hover {
                    background-color: rgba(255, 255, 255, 0.075) !important;
                }
            }

            /* Estilos para etiquetas */
            .etiqueta-badge {
                display: inline-block;
                padding: 0.4rem 0.8rem;
                margin: 0.2rem;
                border-radius: 0.375rem;
                font-size: 0.875rem;
                font-weight: 500;
                color: white;
                text-align: center;
                border: 1px solid transparent;
            }

            .etiquetas-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 0.75rem;
                margin-top: 1rem;
            }

            .etiqueta-item {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 0.5rem;
                padding: 1rem;
                transition: box-shadow 0.15s ease-in-out;
            }

            .etiqueta-item:hover {
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            }

            /* Mejoras de grupo de botones */
            .btn-group .btn {
                border-radius: 0;
            }

            .btn-group .btn:first-child {
                border-top-left-radius: 0.25rem;
                border-bottom-left-radius: 0.25rem;
            }

            .btn-group .btn:last-child {
                border-top-right-radius: 0.25rem;
                border-bottom-right-radius: 0.25rem;
            }

            /* Estilos para badges de estado de préstamo */
            .badge {
                display: inline-block;
                padding: 0.25em 0.6em;
                font-size: 0.75em;
                font-weight: 700;
                line-height: 1;
                text-align: center;
                white-space: nowrap;
                vertical-align: baseline;
                border-radius: 0.375rem;
                border: 1px solid transparent;
            }

            .badge-primary {
                color: #fff;
                background-color: #007bff;
                border-color: #007bff;
            }

            .badge-secondary {
                color: #fff;
                background-color: #6c757d;
                border-color: #6c757d;
            }

            .badge-success {
                color: #fff;
                background-color: #28a745;
                border-color: #28a745;
            }

            .badge-danger {
                color: #fff;
                background-color: #dc3545;
                border-color: #dc3545;
            }

            .badge-warning {
                color: #212529;
                background-color: #ffc107;
                border-color: #ffc107;
            }

            .badge-info {
                color: #fff;
                background-color: #17a2b8;
                border-color: #17a2b8;
            }

            .badge-light {
                color: #212529;
                background-color: #f8f9fa;
                border-color: #f8f9fa;
            }

            .badge-dark {
                color: #fff;
                background-color: #343a40;
                border-color: #343a40;
            }

            /* Estilos para botones de filtro */
            .btn-sm {
                font-size: 0.875rem;
                padding: 0.375rem 0.75rem;
                transition: all 0.15s ease-in-out;
            }

            .btn-sm:hover {
                transform: translateY(-1px);
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }

            .btn-sm.active,
            .btn-sm:not(.btn-outline-secondary):not(.btn-outline-info):not(.btn-outline-primary):not(.btn-outline-warning) {
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            }

            /* Gap para flex-wrap en navegadores más antiguos */
            .gap-2 > * {
                margin-right: 0.5rem;
                margin-bottom: 0.5rem;
            }

            .gap-2 > *:last-child {
                margin-right: 0;
            }

            /* Responsive para botones de filtro */
            @media (max-width: 768px) {
                .btn-sm {
                    font-size: 0.8rem;
                    padding: 0.25rem 0.5rem;
                }
                
                .gap-2 > * {
                    margin-right: 0.25rem;
                    margin-bottom: 0.25rem;
                }
            }

            /* Efectos hover para badges */
            .badge:hover {
                transform: translateY(-1px);
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                transition: all 0.15s ease-in-out;
            }

            /* Estilos responsivos para la columna de estado */
            @media (max-width: 768px) {
                .badge {
                    font-size: 0.7rem;
                    padding: 0.2em 0.5em;
                }
                
                .badge.d-block {
                    margin-bottom: 0.25rem !important;
                }
            }

            /* Estilos para el modal de confirmación de préstamo */
            #loanPromptModal .modal-header {
                border-bottom: none;
                border-radius: 0.3rem 0.3rem 0 0;
            }

            #loanPromptModal .modal-footer {
                border-top: none;
                padding: 0.75rem 1rem 1.25rem 1rem;
            }

            #loanPromptModal .btn {
                min-width: 140px;
                font-weight: 500;
            }

            #loanPromptModal .alert {
                margin-bottom: 0;
                border: none;
                background-color: rgba(13, 202, 240, 0.1);
                border-left: 4px solid #0dcaf0;
            }

            /* Animación de entrada para el modal */
            #loanPromptModal.fade.show .modal-dialog {
                animation: modalSlideIn 0.3s ease-out;
            }

            @keyframes modalSlideIn {
                from {
                    transform: translateY(-50px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
        </style>
    @endpush

    @push('js')
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Compatibilidad con Bootstrap 5
            const bootstrap = window.bootstrap || { Dropdown: null };
            
            // Inicializar dropdowns para Bootstrap 4 y 5
            inicializarDropdowns();

            // Re-inicializar los dropdowns después de actualizaciones de Livewire
            document.addEventListener('livewire:load', function () {
                Livewire.hook('message.processed', () => {
                    inicializarDropdowns();
                });
            });

            // Detectar cambios de tamaño de pantalla para ajustar la UI
            function handleResize() {
                const windowWidth = window.innerWidth;
                const tableCells = document.querySelectorAll('.table td');
                
                if (windowWidth < 576) { // Extra small devices
                    tableCells.forEach(cell => {
                        cell.classList.add('py-3'); // Aumentar padding vertical en móviles
                    });
                } else {
                    tableCells.forEach(cell => {
                        cell.classList.remove('py-3');
                    });
                }
            }
            
            // Llamar la función al cargar y cuando cambie el tamaño de la ventana
            handleResize();
            window.addEventListener('resize', handleResize);

            // Mejora de la experiencia de usuario: focus en el campo de búsqueda al cargar la página
            const searchInput = document.querySelector('input[wire\\:model\\.live="search"]');
            if (searchInput) {
                searchInput.addEventListener('focus', function() {
                    this.select(); // Selecciona todo el texto al hacer focus
                });
            }
        });

        // Función para inicializar dropdowns (compatible con Bootstrap 4 y 5)
        function inicializarDropdowns() {
            // Probar primero Bootstrap 5
            if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
                dropdownElementList.map(function (dropdownToggleEl) {
                    return new bootstrap.Dropdown(dropdownToggleEl);
                });
            } 
            // Si no está disponible Bootstrap 5, usar jQuery para Bootstrap 4
            else if (typeof jQuery !== 'undefined' && typeof jQuery.fn.dropdown !== 'undefined') {
                $('.dropdown-toggle').dropdown();
            }
        }

        // Función para mostrar etiquetas del cliente
        function mostrarEtiquetasCliente(clienteId, nombreCliente) {
            // Actualizar el nombre del cliente en el modal
            document.getElementById('nombreClienteModal').textContent = nombreCliente;
            
            // Mostrar el modal
            $('#etiquetasModal').modal('show');
            
            // Cargar las etiquetas del cliente
            const etiquetasContainer = document.getElementById('etiquetasContainer');
            etiquetasContainer.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando etiquetas...</p>
                </div>
            `;

            // Hacer petición AJAX para obtener las etiquetas del cliente
            fetch(`/admin/clientes/${clienteId}/etiquetas`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarEtiquetasEnModal(data.etiquetas);
                    } else {
                        mostrarErrorEtiquetas(data.message || 'Error al cargar las etiquetas');
                    }
                })
                .catch(error => {
                    console.error('Error al cargar etiquetas:', error);
                    mostrarErrorEtiquetas('No se pudieron cargar las etiquetas del cliente.');
                });
        }

        function mostrarEtiquetasEnModal(etiquetas) {
            const etiquetasContainer = document.getElementById('etiquetasContainer');
            
            if (etiquetas.length === 0) {
                etiquetasContainer.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Sin etiquetas</h5>
                        <p class="text-muted">Este cliente no tiene etiquetas asignadas.</p>
                    </div>
                `;
                return;
            }

            let etiquetasHtml = '<div class="etiquetas-grid">';
            
            etiquetas.forEach(etiquetaCliente => {
                const etiqueta = etiquetaCliente.etiqueta;
                etiquetasHtml += `
                    <div class="etiqueta-item">
                        <div class="d-flex align-items-center mb-2">
                            <span class="etiqueta-badge" style="background-color: ${etiqueta.color}; margin: 0;">
                                ${etiqueta.etiqueta}
                            </span>
                        </div>
                        ${etiquetaCliente.observacion ? `
                            <small class="text-muted">
                                <strong>Observación:</strong> ${etiquetaCliente.observacion}
                            </small>
                        ` : ''}
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            Asignada: ${new Date(etiquetaCliente.created_at).toLocaleDateString('es-ES')}
                        </small>
                    </div>
                `;
            });
            
            etiquetasHtml += '</div>';
            etiquetasContainer.innerHTML = etiquetasHtml;
        }

        function mostrarErrorEtiquetas(mensaje) {
            const etiquetasContainer = document.getElementById('etiquetasContainer');
            etiquetasContainer.innerHTML = `
                <div class="alert alert-warning text-center" role="alert">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <h6>No se pudieron cargar las etiquetas</h6>
                    <p class="mb-0">${mensaje}</p>
                </div>
            `;
        }

        // Hacer la función global para que sea accesible desde el onclick
        window.mostrarEtiquetasCliente = mostrarEtiquetasCliente;

        // Verificar si se debe mostrar el modal de creación de préstamo
        @if(session('show_loan_prompt'))
            // Mostrar modal después de un pequeño delay para asegurar que la página esté cargada
            setTimeout(function() {
                const clienteNombre = "{{ session('client_name') }}";
                const clienteId = "{{ session('client_id') }}";
                
                // Actualizar contenido del modal
                document.getElementById('clienteNombre').textContent = clienteNombre;
                document.getElementById('createLoanBtn').href = '{{ route("admin.solicitudes.create") }}?cliente_id=' + clienteId;
                
                // Mostrar modal
                $('#loanPromptModal').modal({
                    backdrop: 'static', // No cerrar al hacer clic fuera
                    keyboard: false     // No cerrar con Escape
                });
            }, 1000);
        @endif

        // Funciones para manejo de etiquetas
        function asignarEtiqueta(clienteId, prestamoId) {
            document.getElementById('etiquetaClienteId').value = clienteId;
            document.getElementById('etiquetaPrestamoId').value = prestamoId;
            document.getElementById('etiquetaObservacion').value = '';
            document.getElementById('modalEtiquetaTitle').innerHTML = '<i class="fas fa-tag mr-2"></i>Asignar Etiqueta';
            document.getElementById('submitEtiquetaBtn').innerHTML = '<i class="fas fa-save mr-1"></i>Asignar Etiqueta';
            
            // Limpiar selección de etiqueta
            document.getElementById('etiquetaId').value = '';
            
            $('#modalEtiqueta').modal('show');
        }

        function editarEtiqueta(clienteId, prestamoId, etiquetaId, observacion) {
            document.getElementById('etiquetaClienteId').value = clienteId;
            document.getElementById('etiquetaPrestamoId').value = prestamoId;
            document.getElementById('etiquetaId').value = etiquetaId;
            document.getElementById('etiquetaObservacion').value = observacion || '';
            document.getElementById('modalEtiquetaTitle').innerHTML = '<i class="fas fa-edit mr-2"></i>Editar Etiqueta';
            document.getElementById('submitEtiquetaBtn').innerHTML = '<i class="fas fa-save mr-1"></i>Actualizar Etiqueta';
            
            $('#modalEtiqueta').modal('show');
        }

        function submitEtiqueta() {
            const formData = new FormData();
            formData.append('cliente_id', document.getElementById('etiquetaClienteId').value);
            formData.append('prestamo_id', document.getElementById('etiquetaPrestamoId').value);
            formData.append('etiqueta_id', document.getElementById('etiquetaId').value);
            formData.append('observacion', document.getElementById('etiquetaObservacion').value);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("admin.etiquetas.asignar") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cerrar modal
                    $('#modalEtiqueta').modal('hide');
                    
                    // Mostrar mensaje de éxito
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Recargar la página para mostrar los cambios
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error al procesar la solicitud'
                });
            });
        }

        function removerEtiqueta(prestamoId) {
            Swal.fire({
                title: '¿Está seguro?',
                text: "¿Desea remover la etiqueta de este préstamo?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, remover',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('prestamo_id', prestamoId);
                    formData.append('_token', '{{ csrf_token() }}');

                    fetch('{{ route("admin.etiquetas.remover") }}', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrió un error al procesar la solicitud'
                        });
                    });
                }
            });
        }
        </script>
    @endpush

    <!-- Modal para Etiquetas -->
    <div class="modal fade" id="modalEtiqueta" tabindex="-1" role="dialog" aria-labelledby="modalEtiquetaLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalEtiquetaTitle">
                        <i class="fas fa-tag mr-2"></i>Asignar Etiqueta
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="etiquetaForm">
                        <input type="hidden" id="etiquetaClienteId">
                        <input type="hidden" id="etiquetaPrestamoId">
                        
                        <div class="form-group">
                            <label for="etiquetaId" class="font-weight-bold">
                                <i class="fas fa-tag mr-1 text-primary"></i>Etiqueta <span class="text-danger">*</span>
                            </label>
                            <select id="etiquetaId" class="form-control" required>
                                <option value="">Seleccionar etiqueta</option>
                                @foreach(\App\Models\Etiqueta::where('estado', 1)->get() as $etiqueta)
                                    <option value="{{ $etiqueta->id }}" data-color="{{ $etiqueta->color }}">
                                        {{ $etiqueta->etiqueta }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="etiquetaObservacion" class="font-weight-bold">
                                <i class="fas fa-comment mr-1 text-secondary"></i>Observación (Opcional)
                            </label>
                            <textarea id="etiquetaObservacion" class="form-control" rows="3" 
                                      placeholder="Comentarios adicionales sobre esta etiqueta..."></textarea>
                        </div>

                        <!-- Vista previa de la etiqueta -->
                        <div class="form-group">
                            <label class="font-weight-bold">
                                <i class="fas fa-eye mr-1 text-info"></i>Vista Previa
                            </label>
                            <div class="p-3 bg-light rounded border text-center">
                                <span id="etiquetaPreview" class="badge" style="display: none; padding: 8px 16px; font-size: 14px;">
                                    Etiqueta de ejemplo
                                </span>
                                <span id="etiquetaPlaceholder" class="text-muted">
                                    Seleccione una etiqueta para ver la vista previa
                                </span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="submitEtiquetaBtn" onclick="submitEtiqueta()">
                        <i class="fas fa-save mr-1"></i>Asignar Etiqueta
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Actualizar vista previa de etiqueta al cambiar selección
        document.addEventListener('DOMContentLoaded', function() {
            const etiquetaSelect = document.getElementById('etiquetaId');
            const preview = document.getElementById('etiquetaPreview');
            const placeholder = document.getElementById('etiquetaPlaceholder');

            etiquetaSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                
                if (this.value) {
                    const color = selectedOption.dataset.color;
                    const text = selectedOption.text;
                    
                    preview.style.backgroundColor = color;
                    preview.style.color = 'white';
                    preview.textContent = text;
                    preview.style.display = 'inline-block';
                    placeholder.style.display = 'none';
                } else {
                    preview.style.display = 'none';
                    placeholder.style.display = 'block';
                }
            });
        });
    </script>
</div>