<div>
    <div class="container-fluid px-0">
        <!-- BÚSQUEDA CON AUTOCOMPLETADO - Mantenida para funcionalidad -->
        <div class="bg-white" style="display: none;">
            <div class="row align-items-center mb-4">
                <div class="col-md-12">
                    <div class="input-group position-relative">
                        <input type="text"
                            id="searchInput"
                            wire:model="search"
                            class="form-control"
                            placeholder="Buscar cliente por nombre o DNI..."
                            autocomplete="off">
                        @if($cliente_seleccionado)
                            <button type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    wire:click="$set('cliente_seleccionado', null)"
                                    title="Limpiar filtro de cliente">
                                <i class="fas fa-times"></i>
                            </button>
                        @endif
                        <!-- Dropdown de autocompletado -->
                        <div id="autocompleteDropdown"
                             class="position-absolute w-100 bg-white border rounded shadow-sm"
                             style="top: 100%; z-index: 1050; display: none; max-height: 300px; overflow-y: auto;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtro de Estado - Desktop -->
        <div class="desktop-filters mb-3">
            <div class="estado-tabs d-flex flex-nowrap overflow-auto pb-2">
                <button type="button" class="btn btn-estado {{ $search == '' ? 'active' : '' }}" wire:click="updateSearch('')" onclick="scheduleDropdownReinit()">
                    <span class="d-inline-block">Todos</span>
                    <span class="badge bg-secondary ms-1">{{ $cant_todos }}</span>
                </button>
                <button type="button" class="btn btn-estado {{ $search == 'Nueva Solicitud' ? 'active' : '' }}" wire:click="updateSearch('Nueva Solicitud')" onclick="scheduleDropdownReinit()">
                    <span class="d-inline-block">Nuevas</span>
                    <span class="badge bg-primary ms-1">{{ $cant_nueva_solicitud }}</span>
                </button>
                <button type="button" class="btn btn-estado {{ $search == 'Por Desembolsar' ? 'active' : '' }}" wire:click="updateSearch('Por Desembolsar')" onclick="scheduleDropdownReinit()">
                    <span class="d-inline-block">Por Desemb</span>
                    <span class="badge bg-info ms-1">{{ $cant_por_desembolsar }}</span>
                </button>
                <button type="button" class="btn btn-estado {{ $search == 'Vigente' ? 'active' : '' }}" wire:click="updateSearch('Vigente')" onclick="scheduleDropdownReinit()">
                    <span class="d-inline-block">Vigente</span>
                    <span class="badge bg-success ms-1">{{ $cant_vigente }}</span>
                </button>
                <button type="button" class="btn btn-estado {{ $search == 'Moroso' ? 'active' : '' }}" wire:click="updateSearch('Moroso')" onclick="scheduleDropdownReinit()">
                    <span class="d-inline-block">Moroso</span>
                    <span class="badge bg-warning ms-1">{{ $cant_moroso }}</span>
                </button>
                <button type="button" class="btn btn-estado {{ $search == 'Con Convenio' ? 'active' : '' }}" wire:click="updateSearch('Con Convenio')" onclick="scheduleDropdownReinit()">
                    <span class="d-inline-block">Con Convenio</span>
                    <span class="badge bg-info ms-1">{{ $cant_con_convenio ?? 0 }}</span>
                </button>
                <button type="button" class="btn btn-estado {{ $search == 'Liquidado' ? 'active' : '' }}" wire:click="updateSearch('Liquidado')" onclick="scheduleDropdownReinit()">
                    <span class="d-inline-block">Liquidado</span>
                    <span class="badge bg-secondary ms-1">{{ $cant_liquidado }}</span>
                </button>
                <button type="button" class="btn btn-estado {{ $search == 'Liquidado Sin Préstamo Activo' ? 'active' : '' }}" wire:click="updateSearch('Liquidado Sin Préstamo Activo')" onclick="scheduleDropdownReinit()" title="Préstamos LIQUIDADOS de clientes sin préstamos activos (Vigente, Moroso, Con Convenio, Por Desembolsar, Nueva Solicitud)">
                    <span class="d-inline-block">Cliente Libre</span>
                    <span class="badge bg-success ms-1">{{ $cant_liquidado_sin_prestamo_activo ?? 0 }}</span>
                </button>
                <button type="button" class="btn btn-estado {{ $search == 'Rechazado' ? 'active' : '' }}" wire:click="updateSearch('Rechazado')" onclick="scheduleDropdownReinit()">
                    <span class="d-inline-block">Rechazado</span>
                    <span class="badge bg-danger ms-1">{{ $cant_rechazado ?? 0 }}</span>
                </button>
                <button type="button" class="btn btn-estado {{ $search == 'Cancelado' ? 'active' : '' }}" wire:click="updateSearch('Cancelado')" onclick="scheduleDropdownReinit()">
                    <span class="d-inline-block">Anulado</span>
                    <span class="badge bg-danger ms-1">{{ $cant_cancelado ?? 0 }}</span>
                </button>
                <button type="button" class="btn btn-estado {{ $search == 'Finalizado' ? 'active' : '' }}" wire:click="updateSearch('Finalizado')" onclick="scheduleDropdownReinit()">
                    <span class="d-inline-block">Finalizado</span>
                    <span class="badge bg-dark ms-1">{{ $cant_finalizado }}</span>
                </button>
                <button type="button"
                        class="btn {{ $soloConFacturacion ? 'btn-success' : 'btn-outline-secondary' }} btn-sm"
                        wire:click="toggleSoloConFacturacion"
                        title="{{ $soloConFacturacion ? 'Mostrar todos los préstamos' : 'Mostrar solo préstamos con facturación' }}">
                    <i class="fas {{ $soloConFacturacion ? 'fa-receipt' : 'fa-file-invoice' }}"></i>
                    {{ $soloConFacturacion ? 'Con Facturación' : 'Solo con Facturación' }}
                    @if($soloConFacturacion)
                        <i class="fas fa-check ms-1"></i>
                    @endif
                </button>
                <button type="button"
                        class="btn btn-outline-primary btn-sm"
                        onclick="abrirModalConfigColumnas()"
                        title="Configurar columnas visibles">
                    <i class="fas fa-columns"></i>
                    Columnas
                </button>
            </div>
        </div>

        <!-- Mobile - Botón para abrir sidebar -->
        <div class="mobile-filters mb-3">
            <button type="button" class="btn btn-primary w-100 d-flex align-items-center justify-content-between" onclick="openFilterSidebar()">
                <div class="d-flex align-items-center">
                    <i class="fas fa-filter me-2"></i>
                    <span>Filtros y Búsqueda</span>
                    @php
                        $activeFilters = 0;
                        if($search && $search != '') $activeFilters++;
                        if($zona_id) $activeFilters++;
                        if($sucursal_id) $activeFilters++;
                        if($dia_pago) $activeFilters++;
                        if($fecha_pago) $activeFilters++;
                    @endphp
                    @if($activeFilters > 0)
                        <span class="badge bg-warning text-dark ms-2 pulse-badge">{{ $activeFilters }}</span>
                    @endif
                </div>
                <i class="fas fa-chevron-right"></i>
            </button>
            @if($activeFilters > 0)
                <small class="text-muted d-block mt-1 text-center">
                    <i class="fas fa-info-circle me-1"></i>{{ $activeFilters }} filtro(s) aplicado(s)
                </small>
            @endif
        </div>

        <!-- Filtros - Solo Desktop -->
        <div class="desktop-date-filters bg-light border-top p-3">
            <div class="row g-3 align-items-end">
                <!-- Filtro de Zona -->
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">🗺️ Zona:</label>
                    <select class="form-select form-select-sm" wire:model.live="zona_id" style="height: 51px; border-radius: 7px;  border-color: #b1b1b1; padding: 10px;">
                        <option value="">Todas las zonas</option>
                        @foreach($zonas ?? [] as $zona)
                            <option value="{{ $zona->id }}">{{ $zona->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro de Sucursal -->
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">🏢 Sucursal:</label>
                    <select class="form-select form-select-sm" wire:model.live="sucursal_id" style="height: 51px; border-radius: 7px;  border-color: #b1b1b1; padding: 10px;">
                        <option value="">Todas las sucursales</option>
                        @foreach($sucursales ?? [] as $sucursal)
                            <option value="{{ $sucursal->id }}">{{ $sucursal->sucursal }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro de Día de Pago -->
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">📅 Día de Pago:</label>
                    <select class="form-select form-select-sm" wire:model.live="dia_pago" style="height: 51px; border-radius: 7px;  border-color: #b1b1b1; padding: 10px;">
                        <option value="">Todos los días</option>
                        <option value="Lunes">Lunes</option>
                        <option value="Martes">Martes</option>
                        <option value="Miércoles">Miércoles</option>
                        <option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option>
                        <option value="Sábado">Sábado</option>
                    </select>
                </div>

                <!-- Filtro de Fecha de Pago -->
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">📆 Fecha de Pago:</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="fecha_pago" style="height: 51px; border-radius: 7px; border-color: #b1b1b1; padding: 10px;">
                </div>

                <div class="col-md-3">
                    @if($sucursal_id || $dia_pago || $fecha_pago)
                        <div class="d-flex gap-2 flex-wrap">
                            @if($sucursal_id)
                                <button type="button"
                                        class="btn btn-outline-secondary btn-sm"
                                        wire:click="$set('sucursal_id', '')"
                                        title="Limpiar filtro de sucursal">
                                    <i class="fas fa-times me-1"></i>Sucursal
                                </button>
                            @endif
                            @if($dia_pago)
                                <button type="button"
                                        class="btn btn-outline-secondary btn-sm"
                                        wire:click="$set('dia_pago', '')"
                                        title="Limpiar filtro de día">
                                    <i class="fas fa-times me-1"></i>Día
                                </button>
                            @endif
                            @if($fecha_pago)
                                <button type="button"
                                        class="btn btn-outline-secondary btn-sm"
                                        wire:click="$set('fecha_pago', '')"
                                        title="Limpiar fecha de pago">
                                    <i class="fas fa-times me-1"></i>Fecha
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body p-0">

                @if ($prestamos->count())
                    <div class="table-responsive">
                        <table class="table table-hover border-0 mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th wire:click="order('id')" class="sortable cursor-pointer ">
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
                                    <th wire:click="order('cliente.persona.nombres')" class="sortable cursor-pointer">
                                        <div class="d-flex align-items-center">
                                            <span>Nombres</span>
                                            <div class="sort-icon ms-1">
                                                @if ($sort == 'cliente.persona.nombres')
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
                                    @if($mostrarDni)
                                    <th wire:click="order('cliente.persona.documento')" class="ocultar ocultar-tablet sortable cursor-pointer ">
                                        <div class="d-flex align-items-center">
                                            <span>DNI</span>
                                            <div class="sort-icon ms-1">
                                                @if ($sort == 'cliente.persona.documento')
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
                                    @endif
                                    @if($mostrarSucursal)
                                    <th class="ocultar">
                                        <div class="d-flex align-items-center">
                                            <span>Sucursal</span>
                                        </div>
                                    </th>
                                    @endif
                                    <!--th wire:click="order('cantidad_solicitada')" class="ocultar sortable cursor-pointer ">
                                        <div class="d-flex align-items-center">
                                            <span>Monto Solicitado</span>
                                            <div class="sort-icon ms-1">
                                                @if ($sort == 'cantidad_solicitada')
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
                                    </th-->
                                    @if($mostrarTipoSolicitud)
                                    <th class="ocultar">Tipo Solicitud</th>
                                    @endif
                                    @if($mostrarEstado)
                                    <th class="ocultar">Estado</th>
                                    @endif
                                    @if($mostrarEtiquetas)
                                    <th class="ocultar ocultar-tablet">Etiquetas</th>
                                    @endif
                                    @if($mostrarFechaCreacion)
                                    <th wire:click="order('created_at')" class="sortable cursor-pointer ocultar-tablet">
                                        <div class="d-flex align-items-center">
                                            <span>Fecha Creación</span>
                                            <div class="sort-icon ms-1">
                                                @if ($sort == 'created_at')
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
                                    @endif
                                    @if($mostrarFechaFinalizacion)
                                    <th class="ocultar-tablet">
                                            <span>Fecha Finalización</span>
                                    </th>
                                    @endif
                                    <th class="ocultar">
                                        <span>Día de Pago</span>
                                    </th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($prestamos as $prestamo)
                                    <tr>
                                        <td class="align-middle ">{{ $prestamo->id }}</td>
                                        <td class="align-middle">
                                            <div class="fw-bold">{{ $prestamo->cliente->persona->nombres }}</div>
                                            <div class="text-muted small">{{ $prestamo->cliente->persona->ape_pat }} {{ $prestamo->cliente->persona->ape_mat }}</div>
                                            <!-- Mostrar DNI en tablet/móvil como subtítulo -->
                                            <div class="text-muted small mostrar-tablet">DNI: {{ $prestamo->cliente->persona->documento }}</div>
                                        </td>
                                        @if($mostrarDni)
                                        <td class="ocultar ocultar-tablet align-middle ">{{ $prestamo->cliente->persona->documento }}</td>
                                        @endif
                                        @if($mostrarSucursal)
                                        <td class="ocultar align-middle ">
                                            <div class="info-item">
                                                <span class="info-value">
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
                                                </span>
                                            </div>
                                        </td>
                                        @endif
                                        <!--td class="ocultar align-middle ">
                                            <div class="info-card">
                                                <div class="info-value">S/. {{ number_format($prestamo->cantidad_solicitada, 2) }}</div>
                                            </div>
                                        </td-->
                                        @if($mostrarTipoSolicitud)
                                        <td class="ocultar align-middle ">
                                            @if($prestamo->tipo_solicitud == 'Renovación')
                                                <span class="badge bg-warning text-dark rounded-pill px-2 py-1">
                                                    <i class="fas fa-redo me-1"></i> Renovación
                                                </span>
                                            @elseif($prestamo->tipo_solicitud == 'Nuevo')
                                                <span class="badge bg-success text-white rounded-pill px-2 py-1">
                                                    <i class="fas fa-plus me-1"></i> Nuevo
                                                </span>
                                            @else
                                                <span class="badge bg-light text-dark rounded-pill px-2 py-1">
                                                    {{ $prestamo->tipo_solicitud ?? 'N/D' }}
                                                </span>
                                            @endif
                                        </td>
                                        @endif
                                        @if($mostrarEstado)
                                        <td class="ocultar align-middle">
                                            @php
                                                // Verificar si el préstamo tiene un convenio activo
                                                $tieneConvenioActivo = $prestamo->convenios &&
                                                    $prestamo->convenios->where('estado', \App\Enums\ConvenioEstado::ACTIVO)->count() > 0;
                                                // Obtener motivo de rechazo si el estado es Rechazado
                                                $motivoRechazo = ($prestamo->estado === 'Rechazado' || $prestamo->estado === 'rechazado')
                                                    ? $prestamo->observaciones
                                                    : null;
                                            @endphp
                                            <x-prestamo-estado-badge
                                                :estado="$prestamo->estado"
                                                :tieneConvenio="$tieneConvenioActivo"
                                                :motivoRechazo="$motivoRechazo" />
                                        </td>
                                        @endif
                                        @if($mostrarEtiquetas)
                                        <td class="ocultar ocultar-tablet align-middle ">
                                            @php
                                                // Obtener todas las etiquetas del préstamo sin restricción de estado
                                                $etiquetasCliente = \App\Models\EtiquetaCliente::where('prestamo_id', $prestamo->id)
                                                    ->with('etiqueta')
                                                    ->get();
                                            @endphp

                                            <div class="etiquetas-container">
                                                @if($etiquetasCliente->count() > 0)
                                                    <div class="d-flex flex-wrap gap-1 mb-1">
                                                        @foreach($etiquetasCliente as $etiquetaCliente)
                                                            <span class="badge rounded-pill px-2 py-1"
                                                                  style="background-color: {{ $etiquetaCliente->etiqueta->color }}; color: {{ $etiquetaCliente->etiqueta->color === '#FFFFFF' || $etiquetaCliente->etiqueta->color === '#ffffff' ? '#000000' : '#FFFFFF' }};"
                                                                  title="Etiqueta: {{ $etiquetaCliente->etiqueta->etiqueta }}">
                                                                <i class="fas fa-tag me-1" style="font-size: 0.7rem;"></i>
                                                                {{ Str::limit($etiquetaCliente->etiqueta->etiqueta, 15) }}
                                                            </span>
                                                        @endforeach
                                                    </div>

                                                    <button type="button" class="btn btn-outline-primary btn-xs"
                                                            onclick="abrirModalEtiqueta({{ $prestamo->id }}, {{ $prestamo->cliente_id }}, '{{ addslashes($prestamo->cliente->persona->nombres . ' ' . $prestamo->cliente->persona->ape_pat . ' ' . $prestamo->cliente->persona->ape_mat) }}')"
                                                            title="Gestionar etiquetas">
                                                        <i class="fas fa-edit" style="font-size: 0.7rem;"></i>
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                                            onclick="abrirModalEtiqueta({{ $prestamo->id }}, {{ $prestamo->cliente_id }}, '{{ addslashes($prestamo->cliente->persona->nombres . ' ' . $prestamo->cliente->persona->ape_pat . ' ' . $prestamo->cliente->persona->ape_mat) }}')"
                                                            title="Asignar primera etiqueta">
                                                        <i class="fas fa-tag me-1"></i>
                                                        <span class="d-none d-lg-inline">Asignar</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                        @endif
                                        @if($mostrarFechaCreacion)
                                        <td class="align-middle ocultar-tablet">
                                            <div class="fw-bold">{{ \Carbon\Carbon::parse($prestamo->created_at)->format('d/m/Y') }}</div>
                                            <div class="text-muted small">{{ \Carbon\Carbon::parse($prestamo->created_at)->format('H:i') }}</div>
                                        </td>
                                        @endif
                                        @if($mostrarFechaFinalizacion)
                                        <td class="align-middle text-center ocultar-tablet">
                                            @php
                                                // Mostrar fecha de finalización solo para estados finales
                                                $estadosFinales = ['Liquidado', 'Finalizado', 'Cancelado'];
                                            @endphp
                                            @if(in_array($prestamo->estado, $estadosFinales))
                                                <div class="fw-bold">{{ \Carbon\Carbon::parse($prestamo->updated_at)->format('d/m/Y') }}</div>
                                                <div class="text-muted small">{{ \Carbon\Carbon::parse($prestamo->updated_at)->format('H:i') }}</div>
                                                @if($prestamo->estado == 'Liquidado')
                                                    <div class="badge bg-success mt-1">Liquidado</div>
                                                @elseif($prestamo->estado == 'Finalizado')
                                                    <div class="badge bg-dark mt-1">Finalizado</div>
                                                @elseif($prestamo->estado == 'Cancelado')
                                                    <div class="badge bg-danger mt-1">Cancelado</div>
                                                @endif
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        @endif
                                        <td class="ocultar align-middle text-center">
                                            @if($prestamo->fecha_primer_pago)
                                                @php
                                                    $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                                                    $diaSemana = $diasSemana[\Carbon\Carbon::parse($prestamo->fecha_primer_pago)->dayOfWeek];
                                                @endphp
                                                <span class="badge bg-info">{{ $diaSemana }}</span>
                                                <div class="text-muted small mt-1">{{ \Carbon\Carbon::parse($prestamo->fecha_primer_pago)->format('d/m/Y') }}</div>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td class="align-middle text-center">
                                            <x-prestamo-acciones :prestamo="$prestamo" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Filtro de Paginación -->
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">📋 Resultados:</label>
                        <select class="form-select form-select-sm" wire:model.live="perPage" style="height: 51px; border-radius: 7px;  border-color: #b1b1b1; padding: 10px;">
                            <option value="10">10 resultados</option>
                            <option value="25">25 resultados</option>
                            <option value="50">50 resultados</option>
                            <option value="100">100 resultados</option>
                        </select>
                    </div>

                    <!-- Paginación -->
                    @if($prestamos->hasPages())
                        <div class="d-flex justify-content-between align-items-center py-3 px-3 bg-light border-top">
                            <div class="text-muted small">
                                Mostrando {{ $prestamos->firstItem() }} a {{ $prestamos->lastItem() }} de {{ $prestamos->total() }} resultados
                            </div>
                            <div>
                                {{ $prestamos->links() }}
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <img src="{{ asset('img/no-data.png') }}" alt="Sin datos" class="img-fluid mb-3" style="max-width: 150px;">
                        <h5 class="text-muted">No se encontraron préstamos</h5>
                        <p class="text-muted">Intenta con otros filtros o crea una nueva solicitud</p>
                        <a href="{{ route('admin.solicitudes.create') }}" class="btn btn-primary mt-2">
                            <i class="fa-solid fa-plus-circle me-1"></i> Nueva Solicitud
                        </a>
                    </div>
                @endif
        </div>

        <!-- Modal para Asignar Etiquetas -->
    <div class="modal fade" id="modalAsignarEtiqueta" tabindex="-1" aria-labelledby="modalAsignarEtiquetaLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalEtiquetaTitle">
                        <i class="fas fa-tag me-2 mr-2"></i>Asignar Etiqueta
                    </h5>
                    <button type="button" class="close" onclick="cerrarModalEtiqueta()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="etiquetaForm">
                        <input type="hidden" id="etiquetaClienteId">
                        <input type="hidden" id="etiquetaPrestamoId">
                        
                        <div class="mb-3">
                            <label for="etiquetaId" class="form-label fw-bold">
                                <i class="fas fa-tag me-1 text-primary"></i>Etiqueta <span class="text-danger">*</span>
                            </label>
                            <select id="etiquetaId" class="form-select" required>
                                <option value="">Seleccionar etiqueta</option>
                                @php
                                    try {
                                        $etiquetasDisponibles = \App\Models\Etiqueta::where('estado', 1)->get();
                                    } catch (\Exception $e) {
                                        $etiquetasDisponibles = collect();
                                    }
                                @endphp
                                @forelse($etiquetasDisponibles as $etiqueta)
                                    <option value="{{ $etiqueta->id }}" data-color="{{ $etiqueta->color }}">
                                        {{ $etiqueta->etiqueta }}
                                    </option>
                                @empty
                                    <option value="">No hay etiquetas disponibles</option>
                                @endforelse
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="etiquetaObservacion" class="form-label fw-bold">
                                <i class="fas fa-comment me-1 text-secondary"></i>Observación (Opcional)
                            </label>
                            <textarea id="etiquetaObservacion" class="form-control" rows="3" 
                                      placeholder="Comentarios adicionales sobre esta etiqueta..."></textarea>
                        </div>
                        
                        <!-- Vista previa de la etiqueta -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-eye me-1 text-info"></i>Vista Previa
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
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalEtiqueta()">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="submitEtiquetaBtn" onclick="submitEtiqueta()">
                        <i class="fas fa-save mr-1"></i> Asignar Etiqueta
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Rechazar Préstamo -->
    <div class="modal fade" id="modalRechazarPrestamo" tabindex="-1" aria-labelledby="modalRechazarPrestamoLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-times-circle me-2"></i>Rechazar Préstamo
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" onclick="cerrarModalRechazar()">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Atención:</strong> El préstamo será marcado como rechazado y no se podrá procesar.
                    </div>
                    <form id="rechazarForm">
                        <input type="hidden" id="rechazarPrestamoId">

                        <div class="mb-3">
                            <label for="motivoRechazo" class="form-label fw-bold">
                                <i class="fas fa-comment me-1 text-danger"></i>Motivo del Rechazo <span class="text-danger">*</span>
                            </label>
                            <textarea id="motivoRechazo" class="form-control" rows="4"
                                      placeholder="Ingrese el motivo por el cual se rechaza este préstamo..."
                                      required
                                      maxlength="1000"></textarea>
                            <small class="text-muted">Máximo 1000 caracteres. <span id="charCount">0/1000</span></small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalRechazar()">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="submitRechazarBtn" onclick="confirmarRechazo()">
                        <i class="fas fa-times-circle me-1"></i> Rechazar Préstamo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Configurar Columnas -->
    <div class="modal fade" id="modalConfigColumnas" tabindex="-1" role="dialog" aria-labelledby="modalConfigColumnasLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalConfigColumnasLabel">
                        <i class="fas fa-columns me-2"></i>Configurar Columnas Visibles
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Selecciona las columnas que deseas mostrar en la tabla</p>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="checkDni" wire:model.live="mostrarDni">
                        <label class="form-check-label" for="checkDni">
                            <i class="fas fa-id-card me-1 text-primary"></i> DNI
                        </label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="checkSucursal" wire:model.live="mostrarSucursal">
                        <label class="form-check-label" for="checkSucursal">
                            <i class="fas fa-building me-1 text-primary"></i> Sucursal
                        </label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="checkTipoSolicitud" wire:model.live="mostrarTipoSolicitud">
                        <label class="form-check-label" for="checkTipoSolicitud">
                            <i class="fas fa-file-alt me-1 text-primary"></i> Tipo Solicitud
                        </label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="checkEstado" wire:model.live="mostrarEstado">
                        <label class="form-check-label" for="checkEstado">
                            <i class="fas fa-info-circle me-1 text-primary"></i> Estado
                        </label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="checkEtiquetas" wire:model.live="mostrarEtiquetas">
                        <label class="form-check-label" for="checkEtiquetas">
                            <i class="fas fa-tags me-1 text-primary"></i> Etiquetas
                        </label>
                    </div>
                    <hr>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="checkFechaCreacion" wire:model.live="mostrarFechaCreacion">
                        <label class="form-check-label" for="checkFechaCreacion">
                            <i class="fas fa-calendar-plus me-1 text-success"></i> Fecha Creación
                        </label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="checkFechaDesembolso" wire:model.live="mostrarFechaDesembolso">
                        <label class="form-check-label" for="checkFechaDesembolso">
                            <i class="fas fa-money-bill-wave me-1 text-success"></i> Fecha Desembolso
                        </label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="checkFechaFinalizacion" wire:model.live="mostrarFechaFinalizacion">
                        <label class="form-check-label" for="checkFechaFinalizacion">
                            <i class="fas fa-calendar-check me-1 text-success"></i> Fecha Finalización
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                    <button type="button" class="btn btn-success" wire:click="guardarPreferenciasColumnas" onclick="cerrarModalConfigColumnas()">
                        <i class="fas fa-save me-1"></i> Guardar Preferencias
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Móvil para Filtros -->
    <div id="filterSidebar" class="filter-sidebar">
        <div class="filter-sidebar-header">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>
                Filtros
            </h5>
            <button type="button" class="btn-close-sidebar" onclick="closeFilterSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="filter-sidebar-body">
            <!-- Búsqueda con Autocompletado -->
            <div class="filter-section">
                <h6 class="filter-section-title">
                    <i class="fas fa-search me-2 text-primary"></i>
                    Búsqueda de Cliente
                </h6>
                <div class="position-relative">
                    <input type="text"
                           id="searchInputMobile"
                           class="form-control"
                           placeholder="Buscar cliente por nombre o DNI..."
                           autocomplete="off">
                    @if($cliente_seleccionado)
                        <button type="button"
                                class="btn btn-outline-secondary btn-sm position-absolute top-50 end-0 translate-middle-y me-2"
                                wire:click="$set('cliente_seleccionado', null)"
                                title="Limpiar filtro de cliente">
                            <i class="fas fa-times"></i>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Filtro de Estados -->
            <div class="filter-section">
                <h6 class="filter-section-title">
                    <i class="fas fa-flag me-2 text-success"></i>
                    Estado del Préstamo
                </h6>
                <div class="estado-filter-mobile">
                    <button type="button" class="filter-option {{ $search == '' ? 'active' : '' }}" wire:click="updateSearch('')" onclick="setTimeout(closeFilterSidebar, 300)">
                        <span class="filter-icon"><i class="fas fa-list"></i></span>
                        <span class="filter-text">Todos</span>
                        <span class="filter-badge">{{ $cant_todos }}</span>
                    </button>
                    <button type="button" class="filter-option {{ $search == 'Nueva Solicitud' ? 'active' : '' }}" wire:click="updateSearch('Nueva Solicitud')" onclick="setTimeout(closeFilterSidebar, 300)">
                        <span class="filter-icon"><i class="fas fa-plus-circle"></i></span>
                        <span class="filter-text">Nuevas</span>
                        <span class="filter-badge">{{ $cant_nueva_solicitud }}</span>
                    </button>
                    <!--button type="button" class="filter-option {{ $search == 'Aprobado' ? 'active' : '' }}" wire:click="updateSearch('Aprobado')" onclick="setTimeout(closeFilterSidebar, 300)">
                        <span class="filter-icon"><i class="fas fa-check-circle"></i></span>
                        <span class="filter-text">Aprobado</span>
                        <span class="filter-badge">{{ $cant_aprobado }}</span>
                    </button>
                    <button type="button" class="filter-option {{ $search == 'Por Desembolsar' ? 'active' : '' }}" wire:click="updateSearch('Por Desembolsar')" onclick="setTimeout(closeFilterSidebar, 300)">
                        <span class="filter-icon"><i class="fas fa-money-bill-wave"></i></span>
                        <span class="filter-text">Por Desembolsar</span>
                        <span class="filter-badge">{{ $cant_por_desembolsar }}</span>
                    </button-->
                    <button type="button" class="filter-option {{ $search == 'Vigente' ? 'active' : '' }}" wire:click="updateSearch('Vigente')" onclick="setTimeout(closeFilterSidebar, 300)">
                        <span class="filter-icon"><i class="fas fa-play-circle"></i></span>
                        <span class="filter-text">Vigente</span>
                        <span class="filter-badge">{{ $cant_vigente }}</span>
                    </button>
                    <button type="button" class="filter-option {{ $search == 'Moroso' ? 'active' : '' }}" wire:click="updateSearch('Moroso')" onclick="setTimeout(closeFilterSidebar, 300)">
                        <span class="filter-icon"><i class="fas fa-exclamation-triangle"></i></span>
                        <span class="filter-text">Moroso</span>
                        <span class="filter-badge">{{ $cant_moroso }}</span>
                    </button>
                    <button type="button" class="filter-option {{ $search == 'Con Convenio' ? 'active' : '' }}" wire:click="updateSearch('Con Convenio')" onclick="setTimeout(closeFilterSidebar, 300)">
                        <span class="filter-icon"><i class="fas fa-handshake"></i></span>
                        <span class="filter-text">Con Convenio</span>
                        <span class="filter-badge">{{ $cant_con_convenio ?? 0 }}</span>
                    </button>
                    <button type="button" class="filter-option {{ $search == 'Liquidado' ? 'active' : '' }}" wire:click="updateSearch('Liquidado')" onclick="setTimeout(closeFilterSidebar, 300)">
                        <span class="filter-icon"><i class="fas fa-check-double"></i></span>
                        <span class="filter-text">Liquidado</span>
                        <span class="filter-badge">{{ $cant_liquidado }}</span>
                    </button>
                    <button type="button" class="filter-option {{ $search == 'Liquidado Sin Préstamo Activo' ? 'active' : '' }}" wire:click="updateSearch('Liquidado Sin Préstamo Activo')" onclick="setTimeout(closeFilterSidebar, 300)">
                        <span class="filter-icon"><i class="fas fa-user-check"></i></span>
                        <span class="filter-text">Cliente Libre</span>
                        <span class="filter-badge">{{ $cant_liquidado_sin_prestamo_activo ?? 0 }}</span>
                    </button>
                    <button type="button" class="filter-option {{ $search == 'Cancelado' ? 'active' : '' }}" wire:click="updateSearch('Cancelado')" onclick="setTimeout(closeFilterSidebar, 300)">
                        <span class="filter-icon"><i class="fas fa-times-circle"></i></span>
                        <span class="filter-text">Anulado</span>
                        <span class="filter-badge">{{ $cant_cancelado ?? 0 }}</span>
                    </button>
                    <button type="button" class="filter-option {{ $search == 'Finalizado' ? 'active' : '' }}" wire:click="updateSearch('Finalizado')" onclick="setTimeout(closeFilterSidebar, 300)">
                        <span class="filter-icon"><i class="fas fa-flag-checkered"></i></span>
                        <span class="filter-text">Finalizado</span>
                        <span class="filter-badge">{{ $cant_finalizado }}</span>
                    </button>
                </div>
            </div>

            <!-- Filtro de Zona -->
            <div class="filter-section">
                <h6 class="filter-section-title">
                    <i class="fas fa-map-marked-alt me-2 text-primary"></i>
                    Zona
                </h6>
                <select class="form-select" wire:model.live="zona_id">
                    <option value="">Todas las zonas</option>
                    @foreach($zonas ?? [] as $zona)
                        <option value="{{ $zona->id }}">{{ $zona->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro de Sucursal -->
            <div class="filter-section">
                <h6 class="filter-section-title">
                    <i class="fas fa-building me-2 text-info"></i>
                    Sucursal
                </h6>
                <select class="form-select" wire:model.live="sucursal_id">
                    <option value="">Todas las sucursales</option>
                    @foreach($sucursales ?? [] as $sucursal)
                        <option value="{{ $sucursal->id }}">{{ $sucursal->sucursal }}</option>
                    @endforeach
                </select>
                @if($zona_id)
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-info-circle me-1"></i>Mostrando sucursales de la zona seleccionada
                    </small>
                @endif
            </div>

            <!-- Filtro de Día de Pago -->
            <div class="filter-section">
                <h6 class="filter-section-title">
                    <i class="fas fa-calendar-day me-2 text-warning"></i>
                    Día de Pago
                </h6>
                <select class="form-select" wire:model.live="dia_pago">
                    <option value="">Todos los días</option>
                    <option value="Lunes">Lunes</option>
                    <option value="Martes">Martes</option>
                    <option value="Miércoles">Miércoles</option>
                    <option value="Jueves">Jueves</option>
                    <option value="Viernes">Viernes</option>
                    <option value="Sábado">Sábado</option>
                    <option value="Domingo">Domingo</option>
                </select>
                <small class="text-muted mt-2 d-block">
                    <i class="fas fa-info-circle me-1"></i>Filtra por día de pago (solo préstamos activos)
                </small>
            </div>

            <!-- Filtro de Fecha de Pago -->
            <div class="filter-section">
                <h6 class="filter-section-title">
                    <i class="fas fa-calendar-check me-2 text-primary"></i>
                    Fecha de Pago
                </h6>
                <input type="date" class="form-control" wire:model.live="fecha_pago">
                <small class="text-muted mt-2 d-block">
                    <i class="fas fa-info-circle me-1"></i>Filtra por fecha de primer pago del préstamo
                </small>
            </div>

            <!-- Paginación -->
            <div class="filter-section">
                <h6 class="filter-section-title">
                    <i class="fas fa-list me-2 text-secondary"></i>
                    Resultados por Página
                </h6>
                <select class="form-select" wire:model.live="perPage">
                    <option value="10">10 resultados</option>
                    <option value="25">25 resultados</option>
                    <option value="50">50 resultados</option>
                    <option value="100">100 resultados</option>
                </select>
            </div>

            <!-- Filtro de Facturación -->
            <div class="filter-section">
                <h6 class="filter-section-title">
                    <i class="fas fa-file-invoice me-2 text-info"></i>
                    Facturación
                </h6>
                <div class="form-check form-switch">
                    <input class="form-check-input"
                           type="checkbox"
                           id="facturacionSwitch"
                           wire:model.live="soloConFacturacion">
                    <label class="form-check-label" for="facturacionSwitch">
                        {{ $soloConFacturacion ? 'Solo con facturación activada' : 'Mostrar todos los préstamos' }}
                    </label>
                </div>
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Filtra préstamos que tienen la opción de comprobante habilitada
                </small>
            </div>
        </div>

        <div class="filter-sidebar-footer">
            <button type="button" class="btn btn-success w-100" onclick="closeFilterSidebar()">
                <i class="fas fa-check me-2"></i>
                Aplicar Filtros
            </button>
        </div>
    </div>

    <!-- Overlay del Sidebar -->
    <div id="filterSidebarOverlay" class="filter-sidebar-overlay" onclick="closeFilterSidebar()"></div>

    <style>
    /* ========== SIDEBAR MÓVIL PARA FILTROS ========== */
    .filter-sidebar {
        position: fixed;
        top: 0;
        right: -100%;
        width: 320px;
        height: 100vh;
        background: white;
        box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
        z-index: 9999;
        transition: right 0.3s ease;
        display: flex;
        flex-direction: column;
    }

    .filter-sidebar.show {
        right: 0;
    }

    .filter-sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9998;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .filter-sidebar-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .filter-sidebar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
        background: #f8f9fa;
    }

    .filter-sidebar-header h5 {
        color: #495057;
        font-weight: 600;
        flex: 1;
    }

    .btn-close-sidebar {
        background: none;
        border: none;
        font-size: 1.2rem;
        color: #6c757d;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 0.25rem;
        transition: all 0.2s ease;
    }

    .btn-close-sidebar:hover {
        background: #e9ecef;
        color: #495057;
    }

    .filter-sidebar-body {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }

    .filter-sidebar-footer {
        padding: 1rem;
        border-top: 1px solid #e9ecef;
        background: #f8f9fa;
    }

    .filter-section {
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f1f3f4;
    }

    .filter-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .filter-section-title {
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        color: #495057;
        display: flex;
        align-items: center;
    }

    .estado-filter-mobile {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .filter-option {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: left;
        width: 100%;
    }

    .filter-option:hover {
        background: #e9ecef;
        border-color: #d3d9df;
    }

    .filter-option.active {
        background: #435ebe;
        border-color: #435ebe;
        color: white;
    }

    .filter-option .filter-icon {
        width: 20px;
        margin-right: 0.75rem;
        text-align: center;
    }

    .filter-option .filter-text {
        flex: 1;
        font-weight: 500;
    }

    .filter-option .filter-badge {
        background: rgba(108, 117, 125, 0.1);
        color: #6c757d;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 600;
        min-width: 30px;
        text-align: center;
    }

    .filter-option.active .filter-badge {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }

    /* ========== RESPONSIVE ADJUSTMENTS ========== */

    /* Mobile filters - se muestran solo en pantallas pequeñas */
    .mobile-filters {
        display: block;
    }

    /* Desktop filters - se muestran solo en pantallas grandes */
    .desktop-filters {
        display: none;
    }

    .desktop-date-filters {
        display: none;
    }

    @media (min-width: 992px) {
        .mobile-filters {
            display: none !important;
        }

        .desktop-filters {
            display: block !important;
        }

        .desktop-date-filters {
            display: block !important;
        }

        .filter-sidebar {
            display: none !important;
        }

        .filter-sidebar-overlay {
            display: none !important;
        }
    }

    @media (max-width: 576px) {
        .filter-sidebar {
            width: 100%;
            right: -100vw;
        }
    }

    /* ========== DROPDOWN CSS PURO PARA MORAS ========== */
    .mora-dropdown {
        position: relative;
        display: inline-block;
    }
    li{
        list-style: none!important;
    }
    .mora-dropdown-btn {
        background-color: #ffc107;
        border-color: #ffc107;
        color: #212529;
        cursor: pointer;
    }

    .mora-dropdown-btn:hover {
        background-color: #ffca2c;
        border-color: #ffc720;
    }

    .mora-dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1000;
        min-width: 220px;
        background-color: #fff;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        opacity: 0;
        visibility: hidden;
        margin-top: 5px;
    }

    .mora-dropdown:hover .mora-dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .mora-dropdown-menu a {
        display: block;
        padding: 10px 15px;
        color: #495057;
        text-decoration: none;
        font-size: 14px;
    }

    .mora-dropdown-menu a:hover {
        background-color: #f8f9fa;
        color: #495057;
        text-decoration: none;
    }

    .mora-dropdown-menu .dropdown-divider {
        height: 0;
        margin: 8px 0;
        overflow: hidden;
        border-top: 1px solid #dee2e6;
    }

    .mora-dropdown-menu .dropdown-info {
        display: block;
        padding: 8px 15px;
        color: #6c757d;
        font-size: 12px;
        line-height: 1.4;
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
        margin-top: 5px;
    }

    /* Estilos generales */
    .card {
        /*box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);*/
    }

    .card:hover {
        /*box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);*/
    }

    /* Tabs de estados */
    .estado-tabs {
        gap: 0.5rem;
        padding-bottom: 0.25rem;
        -ms-overflow-style: none; /* para IE y Edge */
        scrollbar-width: none; /* para Firefox */
    }

    .estado-tabs::-webkit-scrollbar {
        display: none; /* para Chrome, Safari y Opera */
    }

    .btn-estado {
        /*border-radius: 20px;*/
        font-size: 9pt;
        padding: 0px 10px;
        white-space: nowrap;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        color: #495057;
        min-width: fit-content;
    }

    .btn-estado:hover {
        background-color: #e9ecef;
    }

    .btn-estado.active {
        background-color: #435ebe;
        color: white;
        border-color: #435ebe;
    }

    .btn-estado .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }

    /* Tabla mejorada */
    .table {
        font-size: 0.95rem;
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
        color: #fff;
    }

    /* Custom dropdown styles */
    .dropdown-custom {
        position: relative;
        display: inline-block;
    }

    .dropdown-menu-custom {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1050;
        min-width: 100%;
        /*padding: 0.5rem 0;*/
        /*margin: 0.125rem 0 0;*/
        background-color: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 0.375rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        display: none;
        max-height: 300px;
        overflow-y: auto;
        width: 170px;
    }

    .dropdown-menu-custom.show {
        display: block;
    }

    .dropdown-menu-custom .dropdown-item {
        display: flex;
        align-items: center;
        /*justify-content: space-between;*/
        /*padding: 0.75rem 1rem;*/
        color: #495057;
        text-decoration: none;
        font-size: 0.875rem;
        border: none;
        background: none;
        width: 100%;
        transition: all 0.2s ease;
    }

    .dropdown-menu-custom .dropdown-item:hover {
        background-color: #f8f9fa;
        color: #495057;
        text-decoration: none;
    }

    .dropdown-menu-custom .dropdown-item.active {
        background-color: #435ebe;
        color: white;
    }

    .dropdown-menu-custom .dropdown-item.active .badge {
        background-color: rgba(255, 255, 255, 0.2) !important;
        color: white !important;
    }

    /* Mobile responsive dropdown button */
    .dropdown-custom .btn {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        border-radius: 0.375rem;
        text-align: left;
        position: relative;
    }

    .dropdown-custom .btn .fas.fa-chevron-down,
    .dropdown-custom .btn .fas.fa-chevron-up {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        transition: transform 0.2s ease;
    }

    /* Dropdown de acciones mejorado */
    .dropdown-menu {
        min-width: 200px;
        padding: 0.5rem 0;
        border: 1px solid #e3e6f0;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        background-color: #ffffff;
        border-radius: 0.5rem;
        z-index: 1050;
    }

    .dropdown-toggle {
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .dropdown-toggle:focus {
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(67, 94, 190, 0.25);
    }

    .dropdown-toggle:hover {
        transform: translateY(0px);
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
    }

    .dropdown-item {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        border-radius: 0;
        display: flex;
        align-items: center;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
        color: #495057;
        transform: translateX(0px);
    }

    .dropdown-item:active {
        background-color: #e9ecef;
    }

    .dropdown-item.text-success:hover {
        background-color: #d4edda;
        color: #155724;
    }

    .dropdown-item.text-danger:hover {
        background-color: #f8d7da;
        color: #721c24;
    }

    .dropdown-item.text-warning:hover {
        background-color: #fff3cd;
        color: #856404;
    }

    .dropdown-item.fw-bold {
        font-weight: 600;
    }

    .dropdown-divider {
        margin: 0.5rem 0;
        border-top: 1px solid #e3e6f0;
    }

    /* Iconos en dropdown */
    .dropdown-item i {
        width: 16px;
        text-align: center;
        flex-shrink: 0;
    }

    /* Estilos para columna de etiquetas */
    .etiquetas-container {
        min-width: 120px;
        max-width: 200px;
    }

    .etiquetas-container .badge {
        font-size: 0.7rem;
        font-weight: 500;
        border: 1px solid rgba(0,0,0,0.1);
        text-shadow: none;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .btn-xs {
        padding: 0.15rem 0.3rem;
        font-size: 0.7rem;
        line-height: 1.2;
        border-radius: 0.2rem;
    }

    .etiquetas-container .btn-xs {
        margin-top: 2px;
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

    /* Responsive para paginación */
    @media (max-width: 576px) {
        .pagination {
            font-size: 0.875rem;
        }
        
        .page-item .page-link {
            padding: 0.25rem 0.5rem;
        }
    }

    /* Responsive para dropdowns de acciones */
    @media (max-width: 768px) {
        .dropdown-menu {
            min-width: 180px;
            font-size: 0.8rem;
        }
        
        .dropdown-item {
            padding: 5px!important;
        }
        
        .dropdown-toggle {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        
        .dropdown-toggle .fas {
            font-size: 0.75rem;
        }
    }

    @media (max-width: 576px) {
        .dropdown-menu {
            min-width: 160px;
            right: 0 !important;
            left: auto !important;
        }
        
        .dropdown-item {
            padding: 0.4rem 0.6rem;
            font-size: 0.75rem;
        }
        
        .dropdown-item i {
            width: 14px;
            font-size: 0.7rem;
        }
        
        /* Responsive para columna de etiquetas */
        .etiquetas-container {
            min-width: 100px;
            max-width: 140px;
        }
        
        .etiquetas-container .badge {
            font-size: 0.6rem;
            padding: 0.2rem 0.4rem;
        }
        
        .etiquetas-container .btn-sm {
            font-size: 0.65rem;
            padding: 0.2rem 0.4rem;
        }
    }

    /* Responsive para tablets pequeños */
    @media (max-width: 768px) and (min-width: 577px) {
        .etiquetas-container {
            min-width: 110px;
            max-width: 160px;
        }
        
        .etiquetas-container .badge {
            font-size: 0.65rem;
        }
    }

    /* Modal personalizado */
    .modal-content {
        border-radius: 0.5rem;
    }

    .modal-header, .modal-footer {
        border: none;
    }
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* Responsive - tabla scroll horizontal */
    @media (max-width: 768px) {
        .table-responsive {
            border-radius: 0.5rem;
        }
        
        .badge {
            font-size: 0.75rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
        }

        /* Hacer el menú de acciones más visible en tablet */
        .dropdown-custom .btn {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
            min-width: 90px;
        }

        .dropdown-menu-custom {
            width: auto;
            min-width: 150px;
        }

        .dropdown-menu-custom .dropdown-item {
            font-size: 0.8rem;
            padding: 0.5rem 0.75rem;
        }

        /* Asegurar que la columna de acciones tenga ancho fijo */
        .table td:last-child,
        .table th:last-child {
            min-width: 100px;
            width: 100px;
            position: sticky;
            right: 0;
            background-color: white;
            z-index: 10;
        }

        /* Sombra para columna sticky */
        .table td:last-child::before,
        .table th:last-child::before {
            content: '';
            position: absolute;
            left: -10px;
            top: 0;
            bottom: 0;
            width: 10px;
            background: linear-gradient(to right, transparent, rgba(0,0,0,0.05));
        }
    }

    /* Responsive móvil */
    @media (max-width: 576px) {
        /* Botón de acciones compacto pero visible */
        .dropdown-custom .btn {
            padding: 0.4rem 0.6rem;
            font-size: 0.75rem;
            min-width: 80px;
        }

        .dropdown-custom .btn i.fa-cog {
            font-size: 0.9rem;
        }

        /* Menú dropdown más grande en móvil para fácil toque */
        .dropdown-menu-custom {
            width: 160px;
            right: auto;
            left: auto;
            transform: translateX(-50px);
        }

        .dropdown-menu-custom .dropdown-item {
            font-size: 0.85rem;
            padding: 0.65rem 0.75rem;
            min-height: 40px; /* Área de toque más grande */
        }

        .dropdown-menu-custom .dropdown-item i {
            font-size: 1rem;
            width: 20px;
        }

        /* Columna de acciones sticky en móvil */
        .table td:last-child,
        .table th:last-child {
            min-width: 85px;
            width: 85px;
            position: sticky;
            right: 0;
            background-color: white;
            z-index: 20;
            box-shadow: -2px 0 4px rgba(0,0,0,0.1);
        }

        /* Hacer la tabla más compacta */
        .table td,
        .table th {
            padding: 0.5rem 0.25rem;
            font-size: 0.8rem;
        }

        /* Badges más pequeños */
        .badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
        }
    }

    /* Para pantallas muy pequeñas */
    @media (max-width: 400px) {
        .dropdown-custom .btn {
            padding: 0.35rem 0.5rem;
            font-size: 0.7rem;
            min-width: 75px;
        }

        .dropdown-menu-custom {
            width: 150px;
            transform: translateX(-60px);
        }

        .dropdown-menu-custom .dropdown-item {
            font-size: 0.8rem;
            padding: 0.6rem 0.5rem;
        }

        .table td:last-child,
        .table th:last-child {
            min-width: 80px;
            width: 80px;
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
    
    /* Ocultar columnas en tablet */
    .mostrar-tablet {
        display: none !important;
    }

    @media (max-width: 1024px) {
        .ocultar-tablet {
            display: none !important;
        }
        .mostrar-tablet {
            display: block !important;
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

    /* Date Filters Floating Popup */
    .date-filters-popup {
        position: absolute;
        top: 100%;
        right: 0;
        z-index: 1050;
        min-width: 500px;
        max-width: 600px;
        background: white;
        border: 1px solid #e3e6f0;
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        margin-top: 0.25rem;
        animation: fadeInDown 0.3s ease-out;
    }

    .date-filters-popup .card-body {
        padding: 1.5rem;
        max-height: 400px;
        overflow-y: auto;
    }

    .date-filters-close {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background: none;
        border: none;
        font-size: 1rem;
        color: #6c757d;
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .date-filters-close:hover {
        background-color: #f8f9fa;
        color: #dc3545;
        transform: scale(1.1);
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .date-filters-popup {
            min-width: 350px;
            right: -50px;
        }
    }

    @media (max-width: 576px) {
        .date-filters-popup {
            min-width: 300px;
            right: -100px;
        }
    }

    /* Estilos para las tarjetas de filtros */
    .card.shadow-sm {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        border: 1px solid #e3e6f0 !important;
    }

    /* Estilos para input groups */
    .input-group-text {
        background-color: #f8f9fa;
        border-color: #e3e6f0;
        color: #6c757d;
    }

    .form-control {
        border-color: #e3e6f0;
    }

    .form-control:focus {
        border-color: #435ebe;
        box-shadow: 0 0 0 0.2rem rgba(67, 94, 190, 0.25);
    }

    /* Badges mejorados */
    .badge.bg-primary {
        background-color: #435ebe !important;
    }

    .badge.bg-success {
        background-color: #28a745 !important;
    }

    .badge.bg-secondary {
        background-color: #6c757d !important;
    }

    /* Separador HR mejorado */
    hr {
        border-top: 2px solid #e3e6f0;
        margin: 1.5rem 0;
        opacity: 0.5;
    }

    /* Botones de filtro mejorados */
    .btn-outline-secondary:hover {
        background-color: #6c757d;
        border-color: #6c757d;
        color: #fff;
    }

    .btn-outline-danger:hover {
        background-color: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }

    /* Indicadores de filtro activo */
    .badge[style*="font-size: 0.7rem;"] {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            opacity: 1;
        }
        50% {
            opacity: 0.7;
        }
        100% {
            opacity: 1;
        }
    }

    /* Responsive mejorado para filtros */
    @media (max-width: 992px) {
        .col-md-5 {
            margin-bottom: 1rem;
        }
        
        .col-md-2 {
            margin-top: 1rem;
        }
        
        .d-flex.align-items-end {
            align-items: start !important;
            justify-content: center;
        }
    }

    @media (max-width: 768px) {
        .filtros-fecha-header {
            padding: 0.5rem 0.75rem;
            margin: -0.5rem -0.75rem;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        .input-group-text {
            display: none;
        }
        
        .form-control {
            border-radius: 0.375rem !important;
        }
        .ocultar{
            display: none!important;
        }
    }

    /* Estilos para las columnas de fecha en la tabla */
    .table .badge {
        font-size: 0.75rem;
        padding: 0.375em 0.75em;
        font-weight: 500;
    }

    .table .text-muted.small {
        font-size: 0.8rem;
        line-height: 1;
    }

    /* Mejoras para tabla responsive */
    .table-responsive {
        position: relative;
        min-height: 200px;
    }

    .table {
        min-width: 800px;
    }

    @media (max-width: 1024px) {
        .table {
            min-width: auto;
        }
    }

    @media (max-width: 768px) {
        .table {
            min-width: 500px;
            font-size: 0.875rem;
        }

        .table thead th {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
        }

        .table tbody td {
            padding: 0.5rem 0.75rem;
        }
    }

    @media (max-width: 576px) {
        .table {
            min-width: 600px;
            font-size: 0.8rem;
        }

        .table thead th {
            padding: 0.4rem 0.5rem;
            font-size: 0.75rem;
        }

        .table tbody td {
            padding: 0.4rem 0.5rem;
        }
    }

    /* Indicador de scroll en tabla */
    .table-responsive::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 30px;
        background: linear-gradient(to right, transparent, rgba(0,0,0,0.05));
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.3s;
    }

    @media (max-width: 768px) {
        .table-responsive::after {
            opacity: 1;
        }
    }

    /* Badge pulsante para filtros activos */
    .pulse-badge {
        animation: pulse-animation 2s ease-in-out infinite;
    }

    @keyframes pulse-animation {
        0%, 100% {
            transform: scale(1);
            opacity: 1;
        }
        50% {
            transform: scale(1.1);
            opacity: 0.8;
        }
    }

    /* Soporte para gap en navegadores antiguos */
    .gap-2 {
        gap: 0.5rem !important;
    }

    /* Mejoras de accesibilidad */
    .form-control:focus,
    .form-select:focus {
        border-color: #435ebe;
        box-shadow: 0 0 0 0.2rem rgba(67, 94, 190, 0.25);
        outline: none;
    }

    /* Transiciones suaves */
    .btn,
    .form-control,
    .form-select,
    .badge {
        transition: all 0.2s ease-in-out;
    }

    /* Feedback visual en hover */
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .btn:active {
        transform: translateY(0);
    }

    /* Mejora en filtros móviles */
    .mobile-filters .btn {
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
    }

    /* Indicador de estado de filtros */
    .filter-status-indicator {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #28a745;
        margin-right: 0.5rem;
        animation: blink 2s infinite;
    }

    @keyframes blink {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.3;
        }
    }
</style>

<script>
// Función para abrir el modal de configuración de columnas
function abrirModalConfigColumnas() {
    console.log('Abriendo modal de configuración de columnas');
    const modalElement = document.getElementById('modalConfigColumnas');

    if (!modalElement) {
        console.error('Modal no encontrado');
        return;
    }

    try {
        // Primero intentar con jQuery (más común en AdminLTE)
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $(modalElement).modal('show');
            console.log('Modal abierto con jQuery');
        }
        // Si no, intentar con Bootstrap 5
        else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            console.log('Modal abierto con Bootstrap 5');
        }
        else {
            console.error('Bootstrap o jQuery no disponibles');
            alert('Error: No se puede abrir el modal. Recarga la página e intenta de nuevo.');
        }
    } catch (e) {
        console.error('Error al abrir modal:', e);
        alert('Error al abrir el modal: ' + e.message);
    }
}

// Función para cerrar el modal de configuración de columnas
function cerrarModalConfigColumnas() {
    const modalElement = document.getElementById('modalConfigColumnas');

    try {
        // Primero intentar con jQuery (más común en AdminLTE)
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $(modalElement).modal('hide');
            console.log('Modal cerrado con jQuery');
        }
        // Si no, intentar con Bootstrap 5
        else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            let modal = null;
            if (typeof bootstrap.Modal.getInstance === 'function') {
                modal = bootstrap.Modal.getInstance(modalElement);
            }
            if (!modal) {
                modal = new bootstrap.Modal(modalElement);
            }
            modal.hide();
            console.log('Modal cerrado con Bootstrap 5');
        }
    } catch (e) {
        console.error('Error al cerrar modal:', e);
        // Forzar ocultación con clases CSS
        modalElement.classList.remove('show');
        modalElement.style.display = 'none';
        document.body.classList.remove('modal-open');
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }
}

// Listener de Livewire para mostrar notificación cuando se guardan las preferencias
document.addEventListener('livewire:init', function() {
    Livewire.on('preferenciasGuardadas', (event) => {
        console.log('Evento preferenciasGuardadas recibido', event);

        // Mostrar notificación
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: event[0].icon || 'success',
                title: event[0].title || 'Preferencias Guardadas',
                text: event[0].text || 'Tus preferencias se han guardado correctamente.',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            alert(event[0].text || 'Preferencias guardadas correctamente');
        }
    });
});

// Custom dropdown functionality - define globally first
window.toggleCustomDropdown = function(button) {
    console.log('toggleCustomDropdown called', button);
    const dropdown = button.nextElementSibling;
    if (!dropdown) {
        console.error('No dropdown menu found');
        return;
    }
    
    const isVisible = dropdown.classList.contains('show');
    
    // Close all other custom dropdowns first
    document.querySelectorAll('.dropdown-menu-custom.show').forEach(menu => {
        if (menu !== dropdown) {
            menu.classList.remove('show');
        }
    });
    
    // Toggle current dropdown
    if (!isVisible) {
        dropdown.classList.add('show');
        console.log('Dropdown opened');
        // Rotate chevron
        const chevron = button.querySelector('.fa-chevron-down, .fa-chevron-up');
        if (chevron) {
            chevron.classList.remove('fa-chevron-down');
            chevron.classList.add('fa-chevron-up');
        }
    } else {
        dropdown.classList.remove('show');
        console.log('Dropdown closed');
        const chevron = button.querySelector('.fa-chevron-down, .fa-chevron-up');
        if (chevron) {
            chevron.classList.remove('fa-chevron-up');
            chevron.classList.add('fa-chevron-down');
        }
    }
};

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown-custom')) {
        document.querySelectorAll('.dropdown-menu-custom.show').forEach(menu => {
            menu.classList.remove('show');
        });
        // Reset all chevrons
        document.querySelectorAll('.dropdown-custom .fa-chevron-up').forEach(chevron => {
            chevron.classList.remove('fa-chevron-up');
            chevron.classList.add('fa-chevron-down');
        });
    }
});

// Handle mobile dropdown selection
document.addEventListener('click', function(event) {
    if (event.target.closest('.dropdown-menu-custom .dropdown-item')) {
        // Close the dropdown after selection
        const dropdown = event.target.closest('.dropdown-custom');
        if (dropdown) {
            const menu = dropdown.querySelector('.dropdown-menu-custom');
            const chevron = dropdown.querySelector('.fa-chevron-up, .fa-chevron-down');

            if (menu) menu.classList.remove('show');
            if (chevron) {
                chevron.classList.remove('fa-chevron-up');
                chevron.classList.add('fa-chevron-down');
            }
        }
    }
});

// Protección contra selectores vacíos (Bootstrap)
const originalQuerySelector = document.querySelector;
document.querySelector = function(selector) {
    if (!selector || selector === '#' || selector === '') {
        // Retornar null silenciosamente para selectores vacíos de Bootstrap
        return null;
    }
    return originalQuerySelector.call(this, selector);
};

document.addEventListener('DOMContentLoaded', function() {
    // Ensure our custom dropdown function is available
    if (typeof window.toggleCustomDropdown !== 'function') {
        console.error('toggleCustomDropdown not found, redefining...');
        window.toggleCustomDropdown = function(button) {
            console.log('toggleCustomDropdown called (backup definition)', button);
            const dropdown = button.nextElementSibling;
            if (!dropdown) {
                console.error('No dropdown menu found');
                return;
            }
            
            const isVisible = dropdown.classList.contains('show');
            
            // Close all other custom dropdowns first
            document.querySelectorAll('.dropdown-menu-custom.show').forEach(menu => {
                if (menu !== dropdown) {
                    menu.classList.remove('show');
                }
            });
            
            // Toggle current dropdown
            if (!isVisible) {
                dropdown.classList.add('show');
                console.log('Dropdown opened');
                const chevron = button.querySelector('.fa-chevron-down, .fa-chevron-up');
                if (chevron) {
                    chevron.classList.remove('fa-chevron-down');
                    chevron.classList.add('fa-chevron-up');
                }
            } else {
                dropdown.classList.remove('show');
                console.log('Dropdown closed');
                const chevron = button.querySelector('.fa-chevron-down, .fa-chevron-up');
                if (chevron) {
                    chevron.classList.remove('fa-chevron-up');
                    chevron.classList.add('fa-chevron-down');
                }
            }
        };
    }

    // Ensure scheduleDropdownReinit is available
    if (typeof window.scheduleDropdownReinit !== 'function') {
        window.scheduleDropdownReinit = function() {
            console.log('Filter applied - custom dropdowns ready');
        };
    }

    // Compatibilidad con Bootstrap 5
    const bootstrap = window.bootstrap || {};
    
    // Bootstrap 5 detectado

    // Modal de desembolso optimizado - Ahora manejado por Livewire
    // Ya no se requiere inicialización Bootstrap manual

    // Función para esperar a que Livewire esté listo
    function waitForLivewire(callback) {
        if (window.Livewire) {
            callback();
        } else {
            setTimeout(() => waitForLivewire(callback), 100);
        }
    }

    // Definir confirmarAccion en el ámbito global para que sea accesible desde HTML
    window.confirmarAccion = function(accion, prestamoId) {
        console.log("Confirmando acción:", accion, "para préstamo:", prestamoId);

        // Si la acción es liquidar, abrir la ventana de liquidación directamente
        if (accion === 'liquidar') {
            const url = `/admin/prestamos/${prestamoId}/liquidacion-ventana`;
            window.open(url, '_blank', 'width=1400,height=900,scrollbars=yes,resizable=yes');
            return;
        }

        let mensaje = '';
        let titulo = '';

        switch(accion) {
            case 'aprobar':
                titulo = '¿Aprobar este préstamo?';
                mensaje = 'Al aprobar el préstamo cambiará a estado "Por Desembolsar".';
                break;
            case 'anular':
                titulo = '¿Anular este préstamo?';
                mensaje = 'Esta acción no se puede deshacer.';
                break;
            default:
                titulo = '¿Confirmar esta acción?';
                mensaje = 'Se cambiará el estado del préstamo.';
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: titulo,
                text: mensaje,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, confirmar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    ejecutarAccionPrestamo(accion, prestamoId);
                }
            });
        } else {
            if (confirm(titulo + "\n" + mensaje)) {
                ejecutarAccionPrestamo(accion, prestamoId);
            }
        }
    };

    // Función global para confirmar eliminación de préstamo
    window.confirmarEliminar = function(prestamoId) {
        console.log("Confirmando eliminación de préstamo:", prestamoId);

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Eliminar préstamo completamente?',
                html: '<strong>⚠️ ATENCIÓN:</strong><br>Esta acción eliminará:<br>• El préstamo<br>• Todas las cuotas<br>• Todas las moras<br>• Todas las operaciones relacionadas<br><br><strong>Esta acción NO se puede deshacer.</strong>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar definitivamente',
                cancelButtonText: 'Cancelar',
                dangerMode: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Segundo nivel de confirmación
                    Swal.fire({
                        title: '¿Está completamente seguro?',
                        text: 'Una vez eliminado, no podrá recuperar esta información.',
                        icon: 'error',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((secondResult) => {
                        if (secondResult.isConfirmed) {
                            ejecutarEliminacionPrestamo(prestamoId);
                        }
                    });
                }
            });
        } else {
            if (confirm('¿Eliminar préstamo completamente?\n\nEsta acción eliminará el préstamo, cuotas, moras y operaciones.\nEsta acción NO se puede deshacer.\n\n¿Continuar?')) {
                if (confirm('¿Está completamente seguro? Esta acción es irreversible.')) {
                    ejecutarEliminacionPrestamo(prestamoId);
                }
            }
        }
    };

    // Función interna para ejecutar eliminación con Livewire
    function ejecutarEliminacionPrestamo(prestamoId) {
        console.log("Ejecutando eliminación de préstamo ID:", prestamoId);

        if (!window.Livewire) {
            console.error("Livewire no está disponible");
            alert("Error: No se pudo conectar con el sistema. Por favor, recargue la página.");
            return;
        }

        try {
            // Intentar varias formas de comunicarse con Livewire
            if (typeof window.Livewire.emit === 'function') {
                console.log("Usando Livewire.emit para eliminar");
                window.Livewire.emit('eliminarPrestamo', prestamoId);
                return;
            }

            // Método para buscar componente por ID y llamar al método
            const componentElement = document.querySelector('[wire\\:id]');
            if (componentElement) {
                const componentId = componentElement.getAttribute('wire:id');
                console.log("Componente Livewire encontrado:", componentId);

                if (typeof window.Livewire.find === 'function') {
                    const component = window.Livewire.find(componentId);
                    if (component && typeof component.call === 'function') {
                        console.log("Llamando a método eliminarPrestamo del componente");
                        component.call('eliminarPrestamo', prestamoId);
                        return;
                    }
                }
            }

            // Método para Livewire 3
            if (typeof window.Livewire.dispatch === 'function') {
                console.log("Usando Livewire.dispatch para eliminar");
                window.Livewire.dispatch('eliminarPrestamo', {
                    prestamoId: prestamoId
                });
                return;
            }

            throw new Error("No se encontró un método compatible para comunicarse con Livewire");

        } catch (error) {
            console.error("Error al ejecutar eliminación:", error);
            alert("Error: No se pudo procesar la eliminación. Detalles: " + error.message);
        }
    }

    // Función interna para ejecutar la acción con Livewire
    function ejecutarAccionPrestamo(accion, prestamoId) {
        console.log("Ejecutando acción:", accion, "para préstamo ID:", prestamoId);
        
        if (!window.Livewire) {
            console.error("Livewire no está disponible");
            alert("Error: No se pudo conectar con el sistema. Por favor, recargue la página.");
            return;
        }
        
        // Intentar varias formas de comunicarse con Livewire
        try {
            // Método 1: Usar directamente el evento 'cambiarEstadoPrestamo'
            if (typeof window.Livewire.emit === 'function') {
                console.log("Usando Livewire.emit para cambiar estado");
                window.Livewire.emit('cambiarEstadoPrestamo', prestamoId, accion);
                return;
            }
            
            // Método 2: Buscar componente por ID y llamar al método
            const componentElement = document.querySelector('[wire\\:id]');
            if (componentElement) {
                const componentId = componentElement.getAttribute('wire:id');
                console.log("Componente Livewire encontrado:", componentId);
                
                if (typeof window.Livewire.find === 'function') {
                    const component = window.Livewire.find(componentId);
                    if (component && typeof component.call === 'function') {
                        console.log("Llamando a método del componente");
                        component.call('cambiarEstadoPrestamo', prestamoId, accion);
                        return;
                    }
                }
            }
            
            // Método 3: Para Livewire 3
            if (typeof window.Livewire.dispatch === 'function') {
                console.log("Usando Livewire.dispatch");
                window.Livewire.dispatch('cambiarEstadoPrestamo', { 
                    prestamoId: prestamoId, 
                    accion: accion 
                });
                return;
            }
            
            // Si llegamos aquí, ningún método funcionó
            throw new Error("No se encontró un método compatible para comunicarse con Livewire");
            
        } catch (error) {
            console.error("Error al ejecutar acción:", error);
            alert("Error: No se pudo procesar la acción. Detalles: " + error.message);
        }
    }

    // Inicializar dropdowns de Bootstrap - Versión simplificada
    function inicializarDropdowns() {
        // Evitar múltiples inicializaciones
        if (window.dropdownsInitialized) {
            return;
        }
        
        // Intentar inicializar dropdowns con Bootstrap 5 o fallback
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        
        if (window.bootstrap && window.bootstrap.Dropdown) {
            // Bootstrap 5
            dropdownToggles.forEach((toggle, index) => {
                try {
                    // Verificar si ya tiene instancia de Bootstrap
                    if (toggle._bootstrap_dropdown_instance) {
                        return; // Ya inicializado
                    }
                    
                    // Crear nueva instancia
                    new window.bootstrap.Dropdown(toggle);
                    
                    // Marcar como inicializado para evitar duplicados
                    toggle._bootstrap_dropdown_instance = true;
                } catch (e) {
                    console.error(`Error dropdown ${index}:`, e);
                }
            });
            
            window.dropdownsInitialized = true;
        } else if (typeof $ !== 'undefined' && $.fn && $.fn.dropdown) {
            // Bootstrap 4 con jQuery como fallback
            try {
                $('.dropdown-toggle').dropdown();
                window.dropdownsInitialized = true;
            } catch (e) {
                console.error("Error con jQuery dropdown:", e);
            }
        } else {
            console.warn("Bootstrap no disponible");
        }
    }
    
    // Función global para cerrar dropdowns al hacer click fuera
    function configurarCierreDropdowns() {
        if (!window.globalDropdownClickListenerAdded) {
            document.addEventListener('click', function(e) {
                // Verificar si el click fue fuera de cualquier dropdown
                const clickedDropdown = e.target.closest('.dropdown');
                
                if (!clickedDropdown) {
                    // Para Bootstrap 5/4 nativos
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                    });
                    
                    // Para fallback manual
                    document.querySelectorAll('.dropdown.show').forEach(dropdown => {
                        dropdown.classList.remove('show');
                    });
                    
                    // Cerrar dropdowns de Bootstrap usando API
                    document.querySelectorAll('.dropdown-toggle[aria-expanded="true"]').forEach(toggle => {
                        toggle.setAttribute('aria-expanded', 'false');
                        const menu = toggle.nextElementSibling;
                        if (menu) {
                            menu.classList.remove('show');
                        }
                    });
                }
            }, { capture: true });
            
            window.globalDropdownClickListenerAdded = true;
        }
    }
    
    // Configurar cierre global de dropdowns
    configurarCierreDropdowns();

    // Re-inicializar componentes tras actualizaciones de Livewire
    waitForLivewire(() => {
        // Modal de desembolso ahora es manejado por componente Livewire separado
        // No requiere re-inicialización manual
        
        // Fallback con MutationObserver
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // Solo reinicializar si se agregaron nodos que contienen dropdowns
                    const hasDropdowns = Array.from(mutation.addedNodes).some(node => 
                        node.nodeType === 1 && (
                            node.querySelector && node.querySelector('.dropdown-toggle') ||
                            node.classList && node.classList.contains('dropdown-toggle')
                        )
                    );
                    // DOM cambió con dropdowns detectados
                }
            });
        });
        
        // Observar cambios en el contenedor principal
        const tableContainer = document.querySelector('tbody');
        if (tableContainer) {
            observer.observe(tableContainer, {
                childList: true,
                subtree: true
            });
        }
        
        // Modal de desembolso optimizado - no requiere inicialización manual
        
        // Ensure date filters popup stays closed after Livewire updates
        const filtrosPanel = document.getElementById('filtrosFecha');
        const toggleIcon = document.getElementById('filtros-toggle-icon');
        if (filtrosPanel && toggleIcon) {
            filtrosPanel.style.display = 'none';
            toggleIcon.classList.remove('fa-chevron-up');
            toggleIcon.classList.add('fa-chevron-down');
        }
        
    });

    // Mostrar u ocultar el campo de número de operación
    const metodoPagoRadios = document.querySelectorAll('input[name="metodo_pago_id"]');
    const operacionContainer = document.getElementById('operacion_container');
    if (operacionContainer) {
        metodoPagoRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                operacionContainer.style.display = this.value === '2' ? 'block' : 'none';
                const nroOperacion = document.getElementById('nro_operacion');
                if (nroOperacion) {
                    if (this.value === '2') {
                        nroOperacion.setAttribute('required', 'required');
                    } else {
                        nroOperacion.removeAttribute('required');
                    }
                }
            });
        });
    }

    // Previsualización de la imagen
    const fileInput = document.getElementById('imagen_deposito');
    if (fileInput) {
        fileInput.addEventListener('change', (event) => {
            const preview = document.getElementById('previewImagen');
            if (preview) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.src = '#';
                    preview.style.display = 'none';
                }
            }
        });
    }

    // Confirmar desembolso (botón del modal)
    const confirmButton = document.getElementById('confirmarDesembolso');
    if (confirmButton) {
        confirmButton.addEventListener('click', function() {
            // Validar formulario
            const form = document.getElementById('desembolsoForm');
            if (!form || !form.checkValidity()) {
                if (form) form.classList.add('was-validated');
                return;
            }
            
            // Mostrar indicador de carga
            const swalLoading = typeof Swal !== 'undefined';
            if (swalLoading) {
                Swal.fire({
                    title: 'Procesando',
                    text: 'Registrando el desembolso...',
                    icon: 'info',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            } else {
                confirmButton.disabled = true;
                confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            }
            
            // Preparar y enviar datos
            const formData = new FormData(form);
            const prestamoId = formData.get('prestamo_id');
            console.log('Iniciando desembolso para préstamo ID:', prestamoId);
            
            // Obtener token CSRF para la petición
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) {
                console.error("No se encontró token CSRF");
                alert("Error: No se pudo obtener token de seguridad. Recargue la página.");
                return;
            }

            fetch('/admin/admin/operaciones/desembolsar', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
            })
            .then(response => {
                console.log('Response Status:', response.status);
                // Capturar respuestas no exitosas
                if (!response.ok) {
                    return response.text().then(text => { 
                        throw new Error(`HTTP ${response.status}: ${text}`); 
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Response Data:', data);
                
                // Ocultar indicador de carga
                if (swalLoading) {
                    Swal.close();
                } else {
                    confirmButton.disabled = false;
                    confirmButton.innerHTML = '<i class="fas fa-check-circle me-1"></i> Confirmar Desembolso';
                }
                
                if (data.success) {
                    cerrarModal();
                    mostrarExito('El desembolso se ha registrado correctamente.');
                    
                    // Notificar a Livewire
                    waitForLivewire(() => {
                        if (window.Livewire) {
                            try {
                                // Intentar diferentes métodos de Livewire según la versión
                                if (typeof window.Livewire.emit === 'function') {
                                    console.log('Usando Livewire.emit');
                                    window.Livewire.emit('desembolsarPrestamo', prestamoId);
                                } 
                                else if (typeof window.Livewire.dispatch === 'function') {
                                    console.log('Usando Livewire.dispatch');
                                    window.Livewire.dispatch('desembolsarPrestamo', { prestamoId: prestamoId });
                                }
                                else {
                                    throw new Error('Método de comunicación Livewire no encontrado');
                                }
                            } catch (error) {
                                console.error('Error al comunicar con Livewire:', error);
                                console.warn('Recargando página como fallback');
                                setTimeout(() => location.reload(), 1500);
                            }
                        } else {
                            console.warn('Livewire no disponible, recargando página');
                            setTimeout(() => location.reload(), 1500);
                        }
                    });
                } else {
                    // Mostrar mensaje de error si hay problemas
                    mostrarError(data.message || 'Hubo un problema al registrar el desembolso.');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                
                // Restaurar UI y mostrar error
                if (swalLoading) {
                    Swal.close();
                } else {
                    confirmButton.disabled = false;
                    confirmButton.innerHTML = '<i class="fas fa-check-circle me-1"></i> Confirmar Desembolso';
                }
                
                // Mostrar el error
                mostrarError(`No se pudo realizar el desembolso: ${error.message}`);
            });
        });
    }

    // Funciones de notificación
    function mostrarExito(mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: '¡Operación exitosa!',
                text: mensaje,
                timer: 3000,
                showConfirmButton: false
            });
        } else {
            alert(mensaje);
        }
    }

    function mostrarError(mensaje) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensaje,
                confirmButtonColor: '#3085d6'
            });
        } else {
            alert('Error: ' + mensaje);
        }
    }

    // Mejorar experiencia de usuario en el campo de búsqueda
    const searchInput = document.querySelector('input[wire\\:model\\.live="search"]');
    if (searchInput) {
        searchInput.addEventListener('focus', function() {
            this.select();
        });
    }

    // Ajustar UI según tamaño de pantalla
    function handleResize() {
        const windowWidth = window.innerWidth;
        const tableCells = document.querySelectorAll('.table td');
        tableCells.forEach(cell => {
            cell.classList.toggle('py-3', windowWidth < 576);
        });
    }
    handleResize();
    window.addEventListener('resize', handleResize);

    // Detectar botones de desembolso
    document.querySelectorAll('[wire\\:click^="mostrarModalDesembolso"]').forEach(boton => {
        console.log("Botón de desembolso encontrado:", boton);
    });

    // Functions for floating date filters popup
    window.toggleDateFilters = function() {
        const popup = document.getElementById('filtrosFecha');
        const toggleIcon = document.getElementById('filtros-toggle-icon');
        
        if (!popup || !toggleIcon) return;
        
        const isVisible = popup.style.display === 'block';
        
        if (isVisible) {
            closeDateFilters();
        } else {
            openDateFilters();
        }
    };

    window.openDateFilters = function() {
        const popup = document.getElementById('filtrosFecha');
        const toggleIcon = document.getElementById('filtros-toggle-icon');
        
        if (!popup || !toggleIcon) return;
        
        // Close all other dropdowns
        document.querySelectorAll('.dropdown-menu-custom.show').forEach(menu => {
            menu.classList.remove('show');
        });
        
        popup.style.display = 'block';
        toggleIcon.classList.remove('fa-chevron-down');
        toggleIcon.classList.add('fa-chevron-up');
        
        console.log('Date filters popup opened');
    };

    window.closeDateFilters = function() {
        const popup = document.getElementById('filtrosFecha');
        const toggleIcon = document.getElementById('filtros-toggle-icon');
        
        if (!popup || !toggleIcon) return;
        
        popup.style.display = 'none';
        toggleIcon.classList.remove('fa-chevron-up');
        toggleIcon.classList.add('fa-chevron-down');
        
        console.log('Date filters popup closed');
    };

    // Auto-close when clicking outside
    document.addEventListener('click', function(event) {
        const popup = document.getElementById('filtrosFecha');
        const toggleBtn = document.getElementById('filtros-toggle-btn');
        
        if (popup && popup.style.display === 'block') {
            if (!popup.contains(event.target) && !toggleBtn.contains(event.target)) {
                closeDateFilters();
            }
        }
    });

    // Auto-close when end date is selected
    function setupAutoCloseOnDateSelection() {
        const endDateInputs = ['fecha_creacion_hasta', 'fecha_desembolso_hasta'];
        
        endDateInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('change', function() {
                    if (this.value) {
                        console.log('End date selected, auto-closing popup in 1 second');
                        setTimeout(() => {
                            closeDateFilters();
                        }, 1000);
                    }
                });
            }
        });
    }

    // Initialize auto-close functionality
    document.addEventListener('DOMContentLoaded', function() {
        setupAutoCloseOnDateSelection();
    });

    // Reinitialize auto-close after Livewire updates
    if (typeof Livewire !== 'undefined' && typeof Livewire.on === 'function') {
        Livewire.on('component.updated', () => {
            setTimeout(() => {
                setupAutoCloseOnDateSelection();
            }, 100);
        });
    }

    // Simplified - no need for complex Bootstrap reinitialization with custom dropdowns

    // Custom dropdowns don't need initialization - they work immediately


    // Function for filter buttons - custom dropdowns work automatically
    window.scheduleDropdownReinit = function() {
        console.log('Filter applied - custom dropdowns ready');
    };

    // Initialize date filters popup state
    const filtrosPanel = document.getElementById('filtrosFecha');
    const toggleIcon = document.getElementById('filtros-toggle-icon');
    if (filtrosPanel && toggleIcon) {
        // Ensure popup is hidden by default
        filtrosPanel.style.display = 'none';
        toggleIcon.classList.remove('fa-chevron-up');
        toggleIcon.classList.add('fa-chevron-down');
    }

    // Escuchar eventos de Livewire
    waitForLivewire(() => {
        Livewire.on('mostrarModalDesembolso', (prestamoId) => {
            console.log("Evento mostrarModalDesembolso recibido para préstamo:", prestamoId);
            
            fetch(`/admin/admin/prestamos/${prestamoId}/monto`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('prestamoId').value = prestamoId;
                    document.getElementById('monto').value = parseFloat(data.monto).toFixed(2);
                    document.getElementById('fecha').value = new Date().toISOString().split('T')[0];
                    
                    // Mostrar modal según versión de Bootstrap
                    if (bootstrap.Modal && typeof desembolsoModal.show === 'function') {
                        desembolsoModal.show();
                    } else if (typeof $ !== 'undefined' && typeof desembolsoModal.modal === 'function') {
                        desembolsoModal.modal('show');
                    } else {
                        // Alternativa manual si nada funciona
                        document.getElementById('desembolsoModal')?.classList.add('show');
                        document.body.classList.add('modal-open');
                    }
                } else {
                    mostrarError('No se pudo cargar el monto del préstamo.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error al obtener datos del préstamo.');
            });
        });

        Livewire.on('estadoPrestamoCambiado', (event) => {
            console.log("Evento estadoPrestamoCambiado recibido:", event);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: event[0].icon,
                    title: event[0].title,
                    text: event[0].text,
                    showConfirmButton: true
                });
            } else {
                alert(event[0].text);
            }
        });

        Livewire.on('prestamoDesembolsado', (event) => {
            console.log("Evento prestamoDesembolsado recibido:", event);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: event[0].icon,
                    title: event[0].title,
                    text: event[0].text,
                    showConfirmButton: true
                });
            } else {
                alert(event[0].text);
            }
        });

        Livewire.on('morasMasivasGeneradas', (event) => {
            console.log("Evento morasMasivasGeneradas recibido:", event);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: event[0].icon,
                    title: event[0].title,
                    html: event[0].text.replace(/\n/g, '<br>'),
                    showConfirmButton: true,
                    confirmButtonText: 'Entendido',
                    width: '500px'
                });
            } else {
                alert(event[0].text);
            }
        });

        // Evento para regularización de moras
        Livewire.on('morasRegularizadas', (event) => {
            console.log("Evento morasRegularizadas recibido:", event);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: event[0].icon,
                    title: event[0].title,
                    html: event[0].text.replace(/\n/g, '<br>'),
                    showConfirmButton: true,
                    confirmButtonText: 'Entendido',
                    width: '500px',
                    footer: event[0].resultados?.moras_regularizadas > 0 || event[0].resultados?.moras_ajustadas > 0 
                        ? '<small class="text-muted">Los cambios se reflejarán al recargar la página</small>' 
                        : null
                });
            } else {
                alert(event[0].text);
            }
        });
        
        // Evento para regularización de estados de préstamos
        Livewire.on('estadosRegularizados', (event) => {
            console.log("Evento estadosRegularizados recibido:", event);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: event[0].icon,
                    title: event[0].title,
                    html: event[0].text.replace(/\n/g, '<br>'),
                    showConfirmButton: true,
                    confirmButtonText: 'Entendido',
                    width: '500px'
                });
            } else {
                alert(event[0].text);
            }
        });

        // Evento para recálculo de cuotas de préstamos
        Livewire.on('cuotasRecalculadas', (event) => {
            console.log("Evento cuotasRecalculadas recibido:", event);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: event[0].icon,
                    title: event[0].title,
                    html: event[0].text.replace(/\n/g, '<br>'),
                    showConfirmButton: true,
                    confirmButtonText: 'Entendido',
                    width: '500px',
                    footer: '<small class="text-muted">Las cuotas han sido recalculadas con el nuevo método de interés y comisión</small>'
                });
            } else {
                alert(event[0].text);
            }
        });
    });

    // Script inicializado
    
    // Inicializar dropdowns al final de la carga
    inicializarDropdowns();
    
    // Actualizar vista previa de etiqueta
    const etiquetaSelect = document.getElementById('etiquetaId');
    if (etiquetaSelect) {
        etiquetaSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const preview = document.getElementById('etiquetaPreview');
            const placeholder = document.getElementById('etiquetaPlaceholder');
            
            if (this.value && selectedOption) {
                const color = selectedOption.getAttribute('data-color');
                const text = selectedOption.textContent;
                
                preview.textContent = text;
                preview.style.backgroundColor = color;
                preview.style.color = (color === '#FFFFFF' || color === '#ffffff') ? '#000000' : '#FFFFFF';
                preview.style.display = 'inline-block';
                placeholder.style.display = 'none';
            } else {
                preview.style.display = 'none';
                placeholder.style.display = 'block';
            }
        });
    }
});

// FUNCIÓN GLOBAL PARA MODAL DE ETIQUETAS
window.abrirModalEtiqueta = function(prestamoId, clienteId, nombreCliente) {
    // Llenar los campos del modal
    document.getElementById('etiquetaClienteId').value = clienteId;
    document.getElementById('etiquetaPrestamoId').value = prestamoId;
    document.getElementById('etiquetaObservacion').value = '';
    document.getElementById('modalEtiquetaTitle').innerHTML = '<i class="fas fa-tag mr-2"></i>Asignar Etiqueta a ' + nombreCliente;
    
    // Limpiar selección
    document.getElementById('etiquetaId').value = '';
    
    // Mostrar modal
    var modal = document.getElementById('modalAsignarEtiqueta');
    modal.style.display = 'block';
    modal.classList.add('show');
    modal.style.paddingRight = '17px';
    
    // Crear backdrop
    var backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    backdrop.id = 'modal-backdrop-etiqueta';
    document.body.appendChild(backdrop);
    
    document.body.classList.add('modal-open');
    document.body.style.paddingRight = '17px';
};

window.cerrarModalEtiqueta = function() {
    var modal = document.getElementById('modalAsignarEtiqueta');
    var backdrop = document.getElementById('modal-backdrop-etiqueta');
    
    modal.style.display = 'none';
    modal.classList.remove('show');
    modal.style.paddingRight = '';
    
    if (backdrop) {
        backdrop.remove();
    }
    
    document.body.classList.remove('modal-open');
    document.body.style.paddingRight = '';
};


    function submitEtiqueta() {
        const etiquetaId = document.getElementById('etiquetaId').value;
        if (!etiquetaId) {
            alert('Por favor seleccione una etiqueta');
            return;
        }

        const formData = new FormData();
        formData.append('cliente_id', document.getElementById('etiquetaClienteId').value);
        formData.append('prestamo_id', document.getElementById('etiquetaPrestamoId').value);
        formData.append('etiqueta_id', etiquetaId);
        formData.append('observacion', document.getElementById('etiquetaObservacion').value);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));

        fetch('{{ route("admin.etiquetas.asignar") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cerrar modal usando nuestra función
                cerrarModalEtiqueta();
                
                // Mostrar mensaje de éxito
                alert('Etiqueta asignada exitosamente!');
                
                // Recargar la página para mostrar los cambios
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ocurrió un error al procesar la solicitud');
        });
    }

// FUNCIONES PARA MODAL DE RECHAZAR PRÉSTAMO
window.abrirModalRechazar = function(prestamoId) {
    document.getElementById('rechazarPrestamoId').value = prestamoId;
    document.getElementById('motivoRechazo').value = '';
    document.getElementById('charCount').textContent = '0/1000';

    var modal = document.getElementById('modalRechazarPrestamo');
    modal.style.display = 'block';
    modal.classList.add('show');
    modal.style.paddingRight = '17px';

    var backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    backdrop.id = 'modal-backdrop-rechazar';
    document.body.appendChild(backdrop);

    document.body.classList.add('modal-open');
    document.body.style.paddingRight = '17px';
};

window.cerrarModalRechazar = function() {
    var modal = document.getElementById('modalRechazarPrestamo');
    var backdrop = document.getElementById('modal-backdrop-rechazar');

    modal.style.display = 'none';
    modal.classList.remove('show');
    modal.style.paddingRight = '';

    if (backdrop) {
        backdrop.remove();
    }

    document.body.classList.remove('modal-open');
    document.body.style.paddingRight = '';
};

function confirmarRechazo() {
    const prestamoId = document.getElementById('rechazarPrestamoId').value;
    const motivo = document.getElementById('motivoRechazo').value.trim();

    if (!motivo) {
        alert('Por favor ingrese el motivo del rechazo');
        return;
    }

    if (motivo.length > 1000) {
        alert('El motivo no puede exceder los 1000 caracteres');
        return;
    }

    const submitBtn = document.getElementById('submitRechazarBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Rechazando...';

    // Llamar al método Livewire
    if (window.Livewire) {
        try {
            const component = window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
            if (component) {
                component.call('rechazarPrestamo', prestamoId, motivo);
            }
        } catch (error) {
            console.error('Error al rechazar préstamo:', error);
            alert('Error al procesar el rechazo');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-times-circle me-1"></i> Rechazar Préstamo';
        }
    }
}

// Contador de caracteres para el motivo de rechazo
document.addEventListener('DOMContentLoaded', function() {
    const motivoTextarea = document.getElementById('motivoRechazo');
    if (motivoTextarea) {
        motivoTextarea.addEventListener('input', function() {
            const count = this.value.length;
            document.getElementById('charCount').textContent = count + '/1000';
        });
    }
});

// Listener para el evento de rechazo exitoso
document.addEventListener('livewire:initialized', function () {
    Livewire.on('prestamoRechazado', function (event) {
        const submitBtn = document.getElementById('submitRechazarBtn');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-times-circle me-1"></i> Rechazar Préstamo';

        cerrarModalRechazar();

        // Mostrar notificación con SweetAlert
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: event[0].icon,
                title: event[0].title,
                text: event[0].text,
                confirmButtonText: 'Aceptar'
            }).then(() => {
                window.location.reload();
            });
        } else {
            alert(event[0].text);
            window.location.reload();
        }
    });
});

// AUTOCOMPLETADO INTELIGENTE PARA BÚSQUEDA DE CLIENTES
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchInputHeader = document.getElementById('searchInputHeader');
    const dropdown = document.getElementById('autocompleteDropdown');
    let searchTimeout;

    // Función para manejar el input de búsqueda
    function handleSearchInput(inputElement) {
        if (!inputElement || !dropdown) return;

        inputElement.addEventListener('input', function() {
            const termino = this.value.trim();

            // Sincronizar todos los inputs
            const searchInputMobile = document.getElementById('searchInputMobile');

            if (inputElement === searchInput) {
                if (searchInputHeader) searchInputHeader.value = this.value;
                if (searchInputMobile) searchInputMobile.value = this.value;
            } else if (inputElement === searchInputHeader) {
                if (searchInput) searchInput.value = this.value;
                if (searchInputMobile) searchInputMobile.value = this.value;
            } else if (inputElement === searchInputMobile) {
                if (searchInput) searchInput.value = this.value;
                if (searchInputHeader) searchInputHeader.value = this.value;
            }

            // Actualizar modelo de Livewire
            if (window.Livewire) {
                const component = window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
                if (component) {
                    component.set('search', this.value);
                }
            }

            // Limpiar timeout anterior
            clearTimeout(searchTimeout);

            if (termino.length < 2) {
                dropdown.style.display = 'none';
                return;
            }

            // Debounce: esperar 300ms antes de buscar
            searchTimeout = setTimeout(() => {
                buscarClientesAjax(termino);
            }, 300);
        });
    }

    // Configurar ambos inputs
    handleSearchInput(searchInput);
    handleSearchInput(searchInputHeader);

    // Configurar input móvil del sidebar
    const searchInputMobile = document.getElementById('searchInputMobile');
    handleSearchInput(searchInputMobile);

    // Cerrar dropdown al hacer clic fuera
    document.addEventListener('click', function(e) {
        const isSearchInput = searchInput && searchInput.contains(e.target);
        const isSearchInputHeader = searchInputHeader && searchInputHeader.contains(e.target);
        const isDropdown = dropdown && dropdown.contains(e.target);

        if (!isSearchInput && !isSearchInputHeader && !isDropdown) {
            dropdown.style.display = 'none';
        }
    });

    function buscarClientesAjax(termino) {
        fetch(`/admin/api/buscar-clientes?q=${encodeURIComponent(termino)}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            mostrarResultadosAutocompletado(data);
        })
        .catch(error => {
            console.error('Error en búsqueda:', error);
            dropdown.style.display = 'none';
        });
    }

    function mostrarResultadosAutocompletado(clientes) {
        if (!clientes || clientes.length === 0) {
            dropdown.innerHTML = '<div class="p-2 text-muted">No se encontraron clientes</div>';
            dropdown.style.display = 'block';
            return;
        }

        const html = clientes.map(cliente => `
            <div class="dropdown-item-autocomplete p-2 border-bottom cursor-pointer hover:bg-light"
                 onclick="seleccionarCliente(${cliente.id}, '${cliente.nombre_completo.replace(/'/g, "\\'")}')">
                <div class="fw-bold">${cliente.nombre_completo}</div>
                <div class="text-muted small">DNI: ${cliente.documento}</div>
            </div>
        `).join('');

        dropdown.innerHTML = html;
        dropdown.style.display = 'block';
    }

    // Función global para seleccionar cliente
    window.seleccionarCliente = function(clienteId, nombreCompleto) {
        // Actualizar Livewire
        if (window.Livewire) {
            const component = window.Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id'));
            if (component) {
                component.call('filtrarPorCliente', clienteId, nombreCompleto);
            }
        }

        // Ocultar dropdown
        dropdown.style.display = 'none';

        // Actualizar todos los inputs
        if (searchInput) searchInput.value = nombreCompleto;
        if (searchInputHeader) searchInputHeader.value = nombreCompleto;
        if (searchInputMobile) searchInputMobile.value = nombreCompleto;
    };

    // ========== FUNCIONES DEL SIDEBAR MÓVIL ==========
    window.openFilterSidebar = function() {
        const sidebar = document.getElementById('filterSidebar');
        const overlay = document.getElementById('filterSidebarOverlay');

        if (sidebar && overlay) {
            sidebar.classList.add('show');
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeFilterSidebar = function() {
        const sidebar = document.getElementById('filterSidebar');
        const overlay = document.getElementById('filterSidebarOverlay');

        if (sidebar && overlay) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    };

    // Cerrar sidebar con tecla Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeFilterSidebar();
        }
    });
});
</script>
</div>