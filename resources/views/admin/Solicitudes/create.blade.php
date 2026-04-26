@extends('layouts.admin')

@section('title', 'Nuevo Préstamo')

@section('content_header')
    <h1>
        <i class="fas fa-plus-circle mr-2"></i>
        Nuevo Préstamo
    </h1>
    <div class="breadcrumb">
        <a href="{{ route('admin.prestamos.index') }}">Préstamos</a> / Nuevo
    </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Sistema de alertas personalizado -->
    <div id="alert-overlay" class="position-fixed" style="top: 0; left: 0; width: 100vw; height: 100vh; z-index: 2147483647; display: none;">
        <!-- Backdrop -->
        <div class="position-absolute w-100 h-100" style="background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);"></div>
        
        <!-- Alerta centrada -->
        <div class="d-flex align-items-center justify-content-center h-100 position-relative" style="z-index: 2147483647;">
            <div id="custom-alert" class="bg-white rounded shadow-lg" style="min-width: 400px; max-width: 500px; box-shadow: 0 25px 50px rgba(0,0,0,0.8) !important;">
                <div id="alert-header" class="p-3 rounded-top">
                    <h5 id="alert-title" class="mb-0 text-white d-flex align-items-center">
                        <i id="alert-icon" class="mr-2"></i>
                        <span id="alert-title-text">Información</span>
                    </h5>
                </div>
                <div class="p-3">
                    <p id="alert-message" class="mb-3">Mensaje</p>
                    <div class="text-right">
                        <button type="button" class="btn btn-outline-secondary mr-2" id="alert-cancel" style="display: none;">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="alert-confirm">Aceptar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Overlay para Registro de Cliente -->
    <div id="clienteSidebar" class="sidebar-overlay">
        <div class="sidebar-content">
            <div class="sidebar-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-plus me-2"></i>Registrar Nuevo Cliente
                </h5>
                <button type="button" class="btn-close-sidebar" id="closeSidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="sidebar-body">
                @php
                    $sucursales = \App\Models\Sucursal::all();
                    $departamentos = \App\Models\Departamento::all();
                    $entBancarias = \App\Models\EntidadBancaria::all();
                    $tiposCuenta = \App\Models\TipoCuenta::all();
                @endphp
                @include('admin.Clientes.create-embedded', compact('sucursales', 'departamentos', 'entBancarias', 'tiposCuenta'))
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Formulario de Nuevo Préstamo -->
        <div class="col-md-7">
            <form action="{{ route('admin.prestamos.store') }}" method="POST" enctype="multipart/form-data" id="prestamoForm" novalidate>
                @csrf
                <!-- Campo oculto para estado de préstamo anterior -->
                <input type="hidden" id="hasPreviousLoan" name="has_previous_loan" value="0">

                <!-- Información Principal -->
                <div class="card card-outline mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-clipboard-list mr-2"></i>
                            Nuevo Préstamo
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Estado y Cliente -->
                        <div class="row">
                            <!-- Estado
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Estado</label>
                                <div class="form-group">
                                    <span class="badge badge-info p-2">En Análisis</span>
                                    <input type="hidden" name="estado" value="En Análisis">
                                </div>
                            </div>
                            -->
                            <!-- Cliente -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cliente <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" 
                                           class="form-control" 
                                           id="clienteBuscador" 
                                           placeholder="Buscar por nombre o DNI..." 
                                           autocomplete="off">
                                    <input type="hidden" name="cliente_id" id="clienteIdSelected" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-primary" id="openClienteSidebar" title="Registrar nuevo cliente">
                                            <i class="fas fa-user-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <!-- Dropdown de resultados -->
                                <div id="clienteResultados" class="dropdown-menu w-100" style="display: none; max-height: 200px; overflow-y: auto;">
                                </div>
                                <div class="invalid-feedback">Por favor selecciona un cliente.</div>
                                <!--small class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    ¿No encuentras el cliente? <a href="#" id="openClienteSidebarLink">Regístralo</a>
                                </small-->
                            </div>

                            <!-- Dirección de Cobro -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Dirección de Cobro <span class="text-danger">*</span></label>
                                <select class="form-control" name="direccion_cobro_id" id="selectDireccionCobro" required>
                                    <option value="" disabled selected>Selecciona una dirección</option>
                                </select>
                                <div class="invalid-feedback">Por favor selecciona una dirección de cobro.</div>
                            </div>

                            <!-- Cuenta Cliente -->
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Cuenta Cliente</label>
                                <select name="cuenta_cliente_id" class="form-control" id="selectCuentaCliente">
                                    <option value="">Seleccione una cuenta (opcional)</option>
                                </select>
                                <div class="invalid-feedback">Por favor selecciona una cuenta de cliente.</div>
                            </div>
                        </div>

                        <!-- Información de préstamos del cliente -->
                        <div class="mt-3">
                            <div id="messageContainer" class="alert-info d-none" role="alert">
                                <i class="fas fa-info-circle mr-2"></i>
                                <span id="message">Información del cliente</span>
                            </div>
                            <div id="tableContainer" class="d-none">
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped" id="tablaPrestamos">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Cliente/Cónyuge</th>
                                                <th>Estado</th>
                                                <th>Fecha Solicitud</th>
                                                <th>Fecha Primer Pago</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Asignación de Personal -->
                        <div class="mt-4">
                            <h5 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-users mr-2"></i>
                                Asignación de Personal
                            </h5>
                            <div class="row">
                                <!-- Analista -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Analista <span class="text-danger">*</span></label>
                                    <select class="form-control" name="analista_id" required>
                                        <option value="" disabled selected>Selecciona un analista</option>
                                        @foreach (App\Models\User::role('Analista')->where('status', 1)->get() as $analista)
                                        <option value="{{ $analista->id }}">
                                            {{ $analista->codigo }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Por favor selecciona un analista.</div>
                                </div>

                                <!-- Asesor -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Asesor <span class="text-danger">*</span></label>
                                    <select class="form-control" name="asesor_id" required>
                                        <option value="" disabled selected>Selecciona un asesor</option>
                                        @foreach (App\Models\User::role('Asesor')->where('status', 1)->get() as $asesor)
                                        <option value="{{ $asesor->id }}">
                                            {{ $asesor->codigo }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Por favor selecciona un asesor.</div>
                                </div>

                                <!-- JCC -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">JCC <span class="text-danger">*</span></label>
                                    <select class="form-control" name="jcc_id" required>
                                        <option value="" disabled selected>Selecciona un JCC</option>
                                        @foreach (App\Models\User::role('JCC')->where('status', 1)->get() as $jcc)
                                        <option value="{{ $jcc->id }}">
                                            {{ $jcc->codigo }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Por favor selecciona un JCC.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Aval -->
                        <div class="mt-4">
                            <h5 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-user-shield mr-2"></i>
                                Información de Aval
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">¿Tiene Aval?</label>
                                    <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                        <label class="btn btn-outline-primary active">
                                            <input type="radio" name="tiene_aval" id="tieneAvalNo" value="0" checked> No
                                        </label>
                                        <label class="btn btn-outline-primary">
                                            <input type="radio" name="tiene_aval" id="tieneAvalSi" value="1"> Sí
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Contenedor de Aval -->
                            <div id="contenedorAval" style="display: none;" class="card bg-light mt-3">
                                <div class="card-body">
                                    <div class="row">
                                        <!-- DNI y botón Asignar -->
                                        <div class="col-md-4 mb-3">
                                            <label for="inputDni" class="form-label">DNI del Aval</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" placeholder="Ingresa el DNI" id="inputDni" name="aval_dni" maxlength="8">
                                                <div class="input-group-append">
                                                    <button class="btn btn-success" type="button" id="btnAsignar">
                                                        <i class="fas fa-check mr-1"></i>
                                                        Asignar
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="invalid-feedback">Por favor ingresa un DNI válido.</div>
                                        </div>

                                        <div class="col-md-8 mb-3">
                                            <label class="form-label">Nombre del Aval</label>
                                            <div class="form-control bg-white">
                                                <span id="nombreCliente" class="text-primary font-weight-bold">---</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Información detallada del aval -->
                                        <div class="col-md-12 mb-3" id="avalDetalles" style="display: none;">
                                            <div class="card border-0 bg-light">
                                                <div class="card-body p-3">
                                                    <h6 class="card-title text-dark mb-2">
                                                        <i class="fas fa-info-circle me-2"></i>Estado del Aval
                                                    </h6>
                                                    <div id="avalInfo"></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label for="parentesco" class="form-label">Parentesco con el Cliente</label>
                                            <input type="text" class="form-control" id="parentesco" name="parentesco" placeholder="Ej. Hermano, Madre">
                                        </div>

                                        <!-- Observaciones -->
                                        <div class="col-md-12">
                                            <label for="observaciones_aval" class="form-label">Observaciones</label>
                                            <textarea class="form-control" id="observaciones_aval" name="observaciones_aval" rows="2" placeholder="Detalles adicionales sobre el Aval"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Campo oculto para almacenar el ID del aval -->
                            <input type="hidden" id="hiddenAvalId" name="aval_id"/>
                        </div>
                    </div>
                </div>

                <!-- Configuración del Préstamo -->
                <div class="card card-outline mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cog mr-2"></i>
                            Configuración del Préstamo
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Cuenta, Fechas y Tipo de Solicitud -->
                        <div class="row">
                            <!-- Cuenta Asignada -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Cuenta Asignada <span class="text-danger">*</span></label>
                                <select class="form-control" name="cuenta_id" required>
                                    <option value="" disabled selected>Selecciona</option>
                                    @foreach($cuentas as $cuenta)
                                    <option value="{{ $cuenta->id }}">
                                        {{ $cuenta->codigo }}
                                    </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Por favor selecciona una cuenta.</div>
                            </div>
                            
                            <!-- Fecha de atención -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Fecha Atención <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="fecha_atencion" name="fecha_atencion" 
                                    value="" required>
                                <div class="invalid-feedback">Por favor selecciona una fecha de atención.</div>
                            </div>

                            <!-- Fecha primer pago -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Fecha Primer Pago <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="fecha_primer_pago" name="fecha_primer_pago" 
                                    value="" required>
                                <small class="form-text text-muted">Automáticamente 7 días después (si cae domingo, se ajusta a lunes)</small>
                                <div class="invalid-feedback">Por favor selecciona una fecha de primer pago.</div>
                            </div>

                            <!-- Tipo Solicitud -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Tipo Solicitud <span class="text-danger">*</span></label>
                                <div class="btn-group btn-group-toggle w-100" data-toggle="buttons" id="tipoSolicitudContainer">
                                    <label class="btn btn-outline-primary active">
                                        <input type="radio" name="tipo_solicitud" value="Nueva" checked> Nueva
                                    </label>
                                    <label class="btn btn-outline-primary">
                                        <input type="radio" name="tipo_solicitud" value="Renovación"> Renovación
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Plazo del Préstamo -->
                        <div class="mt-4">
                            <h5 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                Plazo del Préstamo
                            </h5>
                            <div class="mb-3">
                                <label class="form-label">Seleccione el Plazo <span class="text-danger">*</span></label>
                                <div class="btn-group btn-group-toggle flex-wrap" data-toggle="buttons">
                                    @foreach([8, 12, 15, 18, 20] as $semanas)
                                    <label class="btn btn-outline-success m-1">
                                        <input type="radio" name="plazo" value="{{ $semanas }}" required>
                                        <i class="fas fa-calendar-week mr-1"></i>
                                        {{ $semanas }} semanas
                                    </label>
                                    @endforeach
                                </div>
                                <div class="invalid-feedback">Por favor selecciona un plazo.</div>
                            </div>
                        </div>
                        
                        <!-- Montos -->
                        <div class="mt-4">
                            <h5 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-dollar-sign mr-2"></i>
                                Montos
                            </h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Cantidad Solicitada <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">S/.</span>
                                        </div>
                                        <input type="text" class="form-control number-only" placeholder="0.00" id="cantidad_solicitada" name="cantidad_solicitada" required>
                                    </div>
                                    <div class="invalid-feedback">Por favor ingresa la cantidad solicitada.</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mora <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">S/.</span>
                                        </div>
                                        <input type="text" class="form-control number-only" name="mora" id="mora" value="4.00" required>
                                    </div>
                                    <div class="invalid-feedback">Por favor ingresa la mora.</div>
                                </div>
                            </div>
                        </div>
        
                        <!-- Observaciones adicionales -->
                        <div class="mt-4">
                            <h5 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-comment-alt mr-2"></i>
                                Observaciones Adicionales
                            </h5>
                            <div class="mb-3">
                                <textarea class="form-control" rows="3" name="observaciones" placeholder="Ingrese observaciones relevantes del préstamo..."></textarea>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('admin.prestamos.index') }}" class="btn btn-secondary" id="cancelBtn">
                                <i class="fas fa-times mr-2"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save mr-2"></i>
                                Guardar Préstamo
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Tabla de Cuotas -->
        <div class="col-md-5">
            <div class="card card-outline" style="position: sticky; top: 20px;">
                <div class="card-header text-white">
                    <h3 class="card-title">
                        <i class="fas fa-table mr-2"></i>
                        Detalle de Cuotas
                    </h3>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-center">N°</th>
                                    <th>Fecha</th>
                                    <th class="text-right">Cuota</th>
                                    <th class="text-right">Interés</th>
                                    <th class="text-right">Comisión</th>
                                    <th class="text-right">IGV</th>
                                    <th class="text-right">Saldo Capital</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-calculo-body">
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-calculator fa-2x mb-2 d-block"></i>
                                        Complete los campos requeridos para calcular las cuotas
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <th colspan="2" class="text-right">Total:</th>
                                    <th id="total-cuotas" class="text-right text-success">S/. 0.00</th>
                                    <th id="total-interes" class="text-right">S/. 0.00</th>
                                    <th id="total-comision" class="text-right">S/. 0.00</th>
                                    <th id="total-igv" class="text-right">S/. 0.00</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-info-circle mr-2"></i>
                        <small>Las cuotas se actualizarán automáticamente al completar el monto, plazo y fecha de primer pago.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    /* Estilos específicos para el formulario */
    .form-label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
    }

    /* Estilos para selects nativos */
    .form-control {
        min-height: 38px;
        font-size: 14px;
    }

    .btn-group-toggle .btn {
        font-size: 13px;
        padding: 0.375rem 0.75rem;
    }
    .btn{
        height: 47px!important;
    }

    .number-only:invalid {
        border-color: var(--danger);
    }

    .number-only:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
    }

    .table-sm td, .table-sm th {
        padding: 0.3rem;
        font-size: 12px;
    }

    .card-outline {
        border-top: 3px solid var(--primary);
    }

    .invalid-feedback {
        display: none;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: var(--danger);
    }

    .is-invalid ~ .invalid-feedback {
        display: block;
    }

    .is-invalid {
        border-color: var(--danger);
    }

    /* Sistema de alertas personalizado - Z-INDEX MÁXIMO */
    #alert-overlay {
        z-index: 2147483647 !important; /* Z-index máximo posible */
    }

    #custom-alert {
        animation: alertSlideIn 0.3s ease;
        z-index: 2147483647 !important;
    }

    @keyframes alertSlideIn {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(-20px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    @keyframes alertSlideOut {
        from {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
        to {
            opacity: 0;
            transform: scale(0.9) translateY(-20px);
        }
    }

    .alert-success-header {
        background-color: var(--success) !important;
    }

    .alert-danger-header {
        background-color: var(--danger) !important;
    }

    .alert-warning-header {
        background-color: var(--warning) !important;
    }

    .alert-info-header {
        background-color: var(--info) !important;
    }

    /* Mejorar la visualización de elementos seleccionados */
    .btn-group-toggle .btn.active {
        background-color: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    .btn-outline-success.active {
        background-color: var(--success);
        border-color: var(--success);
        color: white;
    }

    /* Responsive improvements */
    @media (max-width: 768px) {
        .btn-group-toggle {
            flex-direction: column;
        }
        
        .btn-group-toggle .btn {
            border-radius: 0.25rem !important;
            margin-bottom: 0.25rem;
        }
        
        .table-responsive {
            font-size: 11px;
        }

        #custom-alert {
            margin: 1rem;
            min-width: 300px !important;
            max-width: 450px !important;
        }
    }

    /* Estilos para el sidebar deslizable de cliente */
    .sidebar-overlay {
        position: fixed;
        top: 58px;
        right: -50vw; /* Inicialmente oculto - 50% del ancho de ventana */
        width: 50vw; /* 50% del ancho de ventana */
        height: 97vh;
        background: white;
        box-shadow: -10px 0 30px rgba(0,0,0,0.3);
        z-index: 999999; /* Z-index muy alto */
        transition: right 0.4s ease-in-out;
        overflow: hidden;
    }
    
    .sidebar-overlay.active {
        right: 0; /* Mostrar sidebar */
    }
    
    .sidebar-overlay::before {
        content: '';
        position: fixed;
        top: 0;
        left: -100vw;
        width: 100vw;
        height: 100vh;
        background: rgba(0,0,0,0.6);
        opacity: 0;
        transition: opacity 0.4s ease-in-out;
        pointer-events: none;
        z-index: 999998;
    }
    
    .sidebar-overlay.active::before {
        opacity: 1;
        pointer-events: all;
    }
    
    .sidebar-content {
        height: 100vh;
        display: flex;
        flex-direction: column;
        position: relative;
        z-index: 999999;
        background: white;
    }
    
    .sidebar-header {
        background: linear-gradient(135deg, #1A3C6D, #2E5A9A);
        color: white;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 3px 15px rgba(0,0,0,0.2);
        flex-shrink: 0;
    }
    
    .sidebar-header h5 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 600;
    }
    
    .btn-close-sidebar {
        background: none;
        border: none;
        color: white;
        font-size: 1.3rem;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: 6px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    /* Asegurar que los botones de aval no interfieran con los selects */
    .btn-group-toggle .btn {
        position: relative;
        z-index: 1;
    }
    
    /* Ya no necesitamos estilos específicos de Choices.js */
    
    .btn-close-sidebar:hover {
        background: rgba(255,255,255,0.15);
        transform: scale(1.1);
    }
    
    .sidebar-body {
        flex: 1;
        overflow-y: auto;
        padding: 0; /* Sin padding para maximizar espacio */
        background: #f8f9fa;
        scrollbar-width: thin;
        scrollbar-color: #c1c1c1 #f1f1f1;
    }
    
    .sidebar-body::-webkit-scrollbar {
        width: 6px;
    }
    
    .sidebar-body::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .sidebar-body::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    
    .sidebar-body::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    .cliente-success {
        border-left: 4px solid #28a745;
        background-color: #d4edda;
        color: #155724;
        padding: 15px;
        margin: 15px;
        border-radius: 4px;
        border: 1px solid #c3e6cb;
    }
    
    /* Responsive para el sidebar */
    @media (max-width: 1200px) {
        .sidebar-overlay {
            width: 60vw; /* 60% en pantallas medianas */
            right: -60vw;
        }
    }
    
    @media (max-width: 768px) {
        .sidebar-overlay {
            width: 100vw;
            right: -100vw;
        }
        
        .sidebar-header {
            padding: 12px 15px;
        }
        
        .sidebar-header h5 {
            font-size: 1.1rem;
        }
    }
    
    /* Animación de entrada */
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .sidebar-overlay.active .sidebar-content {
        animation: slideInRight 0.3s ease-out;
    }
    
    /* Estilos para el buscador de cliente */
    #clienteResultados {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1000;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background: white;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    #clienteResultados .dropdown-item {
        padding: 0.5rem 1rem;
        cursor: pointer;
        border-bottom: 1px solid #f8f9fa;
    }
    
    #clienteResultados .dropdown-item:hover {
        background-color: #f8f9fa;
    }
    
    #clienteResultados .dropdown-item:last-child {
        border-bottom: none;
    }
    
    #clienteResultados .cliente-info {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .cliente-seleccionado {
        background-color: #e7f3ff;
        border-color: #0d6efd;
    }
</style>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ya no necesitamos Choices.js - todos los selects ahora son nativos de Bootstrap

    // Mostrar/Ocultar campos de aval
    function toggleAvalFields() {
        const tieneAvalSi = document.getElementById('tieneAvalSi');
        const contenedorAval = document.getElementById('contenedorAval');
        contenedorAval.style.display = tieneAvalSi.checked ? 'block' : 'none';
    }

    document.getElementById('tieneAvalSi').addEventListener('change', toggleAvalFields);
    document.getElementById('tieneAvalNo').addEventListener('change', toggleAvalFields);

    // Función para mostrar alertas usando sistema completamente personalizado
    function mostrarAlerta(tipo, titulo, mensaje, callback = null, cancelCallback = null) {
        const overlay = document.getElementById('alert-overlay');
        const alertHeader = document.getElementById('alert-header');
        const alertTitle = document.getElementById('alert-title-text');
        const alertMessage = document.getElementById('alert-message');
        const alertIcon = document.getElementById('alert-icon');
        const confirmBtn = document.getElementById('alert-confirm');
        const cancelBtn = document.getElementById('alert-cancel');

        // Limpiar clases anteriores del header
        alertHeader.className = 'p-3 rounded-top';
        
        // Aplicar estilo según el tipo
        alertHeader.classList.add(`alert-${tipo}-header`);
        
        // Configurar icono según el tipo
        let iconClass = 'fas fa-info-circle';
        switch(tipo) {
            case 'success': iconClass = 'fas fa-check-circle'; break;
            case 'danger': iconClass = 'fas fa-exclamation-circle'; break;
            case 'warning': iconClass = 'fas fa-exclamation-triangle'; break;
            default: iconClass = 'fas fa-info-circle';
        }
        
        alertIcon.className = iconClass + ' mr-2';
        alertTitle.textContent = titulo;
        alertMessage.textContent = mensaje;
        
        // Configurar botones
        if (callback || cancelCallback) {
            cancelBtn.style.display = 'inline-block';
            confirmBtn.textContent = 'Aceptar';
        } else {
            cancelBtn.style.display = 'none';
            confirmBtn.textContent = 'Aceptar';
        }

        // Configurar eventos (remover eventos anteriores)
        confirmBtn.onclick = null;
        cancelBtn.onclick = null;

        confirmBtn.onclick = function() {
            cerrarAlerta();
            if (callback) callback();
        };

        cancelBtn.onclick = function() {
            cerrarAlerta();
            if (cancelCallback) cancelCallback();
        };

        // Mostrar alerta con z-index máximo
        overlay.style.display = 'block';
        overlay.style.zIndex = '2147483647';
        
        // Auto-cerrar si no hay callbacks
        if (!callback && !cancelCallback) {
            setTimeout(cerrarAlerta, 3000);
        }

        // Enfocar botón principal
        setTimeout(() => confirmBtn.focus(), 100);
    }

    function cerrarAlerta() {
        const overlay = document.getElementById('alert-overlay');
        const alertBox = document.getElementById('custom-alert');
        
        // Animación de salida
        alertBox.style.animation = 'alertSlideOut 0.3s ease';
        
        setTimeout(() => {
            overlay.style.display = 'none';
            alertBox.style.animation = 'alertSlideIn 0.3s ease';
        }, 300);
    }

    // Cerrar alerta al hacer clic en el backdrop propio
    document.getElementById('alert-overlay').addEventListener('click', function(e) {
        // Solo cerrar si se hace clic en el backdrop, no en la alerta
        if (e.target === this || e.target === this.firstElementChild) {
            cerrarAlerta();
        }
    });

    // Cerrar alerta con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('alert-overlay').style.display === 'block') {
            cerrarAlerta();
        }
    });

    // Buscador de cliente - búsqueda dinámica
    const clienteBuscador = document.getElementById('clienteBuscador');
    const clienteResultados = document.getElementById('clienteResultados');
    const clienteIdSelected = document.getElementById('clienteIdSelected');
    let clienteSeleccionado = null;
    let searchTimeout;

    clienteBuscador.addEventListener('input', function() {
        const query = this.value.trim();
        
        if (query.length < 2) {
            clienteResultados.style.display = 'none';
            return;
        }

        // Debounce para evitar muchas consultas
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            buscarClientesEnBD(query);
        }, 300);
    });

    // Función para buscar clientes en la base de datos
    async function buscarClientesEnBD(query) {
        try {
            clienteResultados.innerHTML = '<div class="dropdown-item text-muted"><i class="fas fa-spinner fa-spin mr-2"></i>Buscando...</div>';
            clienteResultados.style.display = 'block';

            const response = await fetch(`{{ route('admin.clientes.buscar-prestamo') }}?q=${encodeURIComponent(query)}`);
            const data = await response.json();

            if (data.results && data.results.length > 0) {
                mostrarResultadosCliente(data.results);
            } else {
                clienteResultados.innerHTML = '<div class="dropdown-item text-muted">No se encontraron clientes disponibles</div>';
            }
        } catch (error) {
            console.error('Error al buscar clientes:', error);
            clienteResultados.innerHTML = '<div class="dropdown-item text-danger">Error al buscar clientes</div>';
        }
    }

    function mostrarResultadosCliente(resultados) {
        if (resultados.length === 0) {
            clienteResultados.innerHTML = '<div class="dropdown-item text-muted">No se encontraron clientes</div>';
        } else {
            clienteResultados.innerHTML = resultados.map(cliente => `
                <div class="dropdown-item" data-cliente-id="${cliente.id}">
                    <div class="fw-bold">${cliente.nombre}</div>
                    <div class="cliente-info">DNI: ${cliente.dni}</div>
                </div>
            `).join('');
        }
        
        clienteResultados.style.display = 'block';
        
        // Event listeners para selección
        clienteResultados.querySelectorAll('.dropdown-item[data-cliente-id]').forEach(item => {
            item.addEventListener('click', function() {
                const clienteId = this.getAttribute('data-cliente-id');
                const nombreElement = this.querySelector('.fw-bold');
                if (clienteId && nombreElement) {
                    seleccionarCliente(clienteId, nombreElement.textContent);
                }
            });
        });
    }

    function seleccionarCliente(clienteId, nombreCliente) {
        clienteIdSelected.value = clienteId;
        clienteBuscador.value = nombreCliente;
        clienteBuscador.classList.add('cliente-seleccionado');
        clienteResultados.style.display = 'none';
        clienteSeleccionado = clienteId;

        // Ejecutar las mismas funciones que antes
        validarCliente(clienteId);
        cargarDireccionesYCuentas(clienteId);
        validarPrestamoAnterior(clienteId);
    }

    // Cerrar resultados al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!clienteBuscador.contains(e.target) && !clienteResultados.contains(e.target)) {
            clienteResultados.style.display = 'none';
        }
    });

    // Limpiar selección si se borra el campo
    clienteBuscador.addEventListener('input', function() {
        if (this.value.trim() === '') {
            clienteIdSelected.value = '';
            this.classList.remove('cliente-seleccionado');
            clienteSeleccionado = null;
        }
    });

    // Validar si el cliente tiene préstamos anteriores
    async function validarPrestamoAnterior(clienteId) {
        const tipoSolicitudContainer = document.getElementById('tipoSolicitudContainer');
        const nuevaRadio = document.querySelector('input[name="tipo_solicitud"][value="Nueva"]');
        const renovacionRadio = document.querySelector('input[name="tipo_solicitud"][value="Renovación"]');
        const hasPreviousLoanInput = document.getElementById('hasPreviousLoan');

        if (!clienteId) {
            hasPreviousLoanInput.value = '0';
            nuevaRadio.checked = true;
            nuevaRadio.disabled = false;
            nuevaRadio.closest('label').classList.remove('disabled');
            renovacionRadio.closest('label').classList.remove('disabled');
            return;
        }

        try {
            const response = await fetch(`/admin/consultar-prestamos/${clienteId}`);
            const prestamosData = await response.json();

            if (prestamosData.length > 0) {
                hasPreviousLoanInput.value = '1';
                renovacionRadio.checked = true;
                nuevaRadio.disabled = true;
                nuevaRadio.closest('label').classList.add('disabled');
                renovacionRadio.closest('label').classList.remove('disabled');
                mostrarAlerta('info', 'Renovación detectada', 'Este cliente ya tiene préstamos anteriores, el tipo de solicitud se ha establecido como Renovación.');
            } else {
                hasPreviousLoanInput.value = '0';
                nuevaRadio.checked = true;
                nuevaRadio.disabled = false;
                nuevaRadio.closest('label').classList.remove('disabled');
                renovacionRadio.closest('label').classList.remove('disabled');
            }
            // Actualizar estado visual de los botones
            updateTipoSolicitudButtons();
        } catch (error) {
            console.error('Error al validar préstamos anteriores:', error);
            mostrarAlerta('danger', 'Error', 'No se pudo verificar los préstamos anteriores del cliente.');
        }
    }

    // Función para actualizar el estado visual de los botones
    function updateTipoSolicitudButtons() {
        document.querySelectorAll('input[name="tipo_solicitud"]').forEach(radio => {
            const label = radio.closest('label');
            if (radio.checked) {
                label.classList.add('active');
            } else {
                label.classList.remove('active');
            }
        });
    }

    // Función para evitar domingos
    function evitarDomingo(fecha) {
        const fechaObj = new Date(fecha.getTime()); // Crear copia para no modificar la original
        // Si es domingo (0), mover al lunes (agregar 1 día)
        if (fechaObj.getDay() === 0) {
            fechaObj.setDate(fechaObj.getDate() + 1);
        }
        return fechaObj;
    }

    // Función para formatear fecha a string YYYY-MM-DD
    function formatearFecha(fecha) {
        const dia = fecha.getDate().toString().padStart(2, '0');
        const mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
        const anio = fecha.getFullYear();
        return `${anio}-${mes}-${dia}`;
    }

    // Sincronizar fecha primer pago con fecha de atención
    const fechaAtencionInput = document.getElementById('fecha_atencion');
    const fechaPrimerPagoInput = document.getElementById('fecha_primer_pago');
    fechaAtencionInput.addEventListener('change', function() {
        if (this.value) {
            // Crear fecha evitando problemas de zona horaria
            const [año, mes, dia] = this.value.split('-').map(Number);
            const fechaAtencion = new Date(año, mes - 1, dia);
            
            let fechaPrimerPago = new Date(fechaAtencion);
            fechaPrimerPago.setDate(fechaPrimerPago.getDate() + 7);
            
            // Evitar que caiga en domingo
            fechaPrimerPago = evitarDomingo(fechaPrimerPago);
            
            fechaPrimerPagoInput.value = formatearFecha(fechaPrimerPago);
            calcularCuotas();
            // saveFormState(); // Desactivado
        }
    });

    // Calcular cuotas al cambiar monto, plazo o fecha (optimizado)
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    const calcularCuotasDebounced = debounce(calcularCuotas, 500);
    document.getElementById('cantidad_solicitada').addEventListener('input', calcularCuotasDebounced);
    document.querySelectorAll('input[name="plazo"]').forEach(radio => radio.addEventListener('change', calcularCuotas));
    
    // Event listener especial para fecha primer pago que valida domingo
    fechaPrimerPagoInput.addEventListener('change', function() {
        if (this.value) {
            // Crear fecha evitando problemas de zona horaria
            const [año, mes, dia] = this.value.split('-').map(Number);
            const fechaSeleccionada = new Date(año, mes - 1, dia);
            
            // Verificar si es domingo
            if (fechaSeleccionada.getDay() === 0) {
                const fechaCorregida = evitarDomingo(fechaSeleccionada);
                const fechaCorregidaStr = formatearFecha(fechaCorregida);
                
                mostrarAlerta('warning', 'Fecha en domingo', 
                    `La fecha seleccionada (${this.value}) es domingo. ¿Desea cambiarla al lunes (${fechaCorregidaStr})?`, 
                    () => {
                        this.value = fechaCorregidaStr;
                        calcularCuotas();
                    }, 
                    () => {
                        calcularCuotas(); // Calcular con la fecha domingo si el usuario insiste
                    }
                );
            } else {
                calcularCuotas();
            }
        }
    });

    // Validar campos numéricos
    document.querySelectorAll('.number-only').forEach(input => {
        input.addEventListener('input', function() {
            const value = this.value.replace(/[^0-9.]/g, '');
            const parts = value.split('.');
            if (parts.length > 2) {
                this.value = parts[0] + '.' + parts.slice(1).join('');
            } else {
                this.value = value;
            }
            calcularCuotasDebounced();
            // saveFormState(); // Desactivado
        });
    });

    // Función para calcular cuotas
    function calcularCuotas() {
        const cantidad = parseFloat(document.getElementById('cantidad_solicitada').value);
        const plazoElement = document.querySelector('input[name="plazo"]:checked');
        const plazo = plazoElement ? plazoElement.value : null;
        const fechaPrimerPago = fechaPrimerPagoInput.value;
        const mora = parseFloat(document.getElementById('mora').value);
        const tablaCalculoBody = document.getElementById('tabla-calculo-body');

        if (!cantidad || isNaN(cantidad) || cantidad <= 0) {
            mostrarErrorCampo('cantidad_solicitada', 'Por favor ingresa un monto válido.');
            return;
        } else {
            ocultarErrorCampo('cantidad_solicitada');
        }

        if (!plazo) {
            mostrarAlerta('warning', 'Campo requerido', 'Seleccione un plazo para el préstamo.');
            return;
        }

        if (!fechaPrimerPago) {
            mostrarErrorCampo('fecha_primer_pago', 'Seleccione una fecha de primer pago.');
            return;
        } else {
            ocultarErrorCampo('fecha_primer_pago');
        }

        if (isNaN(mora) || mora < 0) {
            mostrarErrorCampo('mora', 'Ingrese un valor de mora válido.');
            return;
        } else {
            ocultarErrorCampo('mora');
        }

        tablaCalculoBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-3">
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="spinner-border spinner-border-sm text-primary mr-2" role="status">
                            <span class="sr-only">Cargando...</span>
                        </div>
                        <span>Calculando cuotas...</span>
                    </div>
                </td>
            </tr>`;

        fetch("{{ route('admin.calcularCuotas') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                monto: cantidad,
                plazo: plazo,
                fechaPrimerPago: fechaPrimerPago,
                mora: mora
            })
        })
        .then(response => response.json())
        .then(data => {
            let cuotasHtml = '';
            let totalCuota = 0;
            let totalInteres = 0;
            let totalComision = 0;
            let totalIgv = 0;
            
            function formatMoney(value) {
                return parseFloat(value).toFixed(2);
            }
            
            if (data.cuotas && data.cuotas.length > 0) {
                // Crear fecha evitando problemas de zona horaria
                const fechaString = fechaPrimerPagoInput.value; // formato: YYYY-MM-DD
                const [año, mes, dia] = fechaString.split('-').map(Number);
                const fechaInicial = new Date(año, mes - 1, dia); // mes - 1 porque Date usa 0-11 para meses
                
                data.cuotas.forEach((cuota, index) => {
                    // Calcular la fecha de cada cuota basada en la fecha de primer pago
                    const fechaCuota = new Date(fechaInicial);
                    fechaCuota.setDate(fechaCuota.getDate() + (index * 7)); // +7 días por cada cuota
                    
                    // Evitar domingos en cada cuota
                    const fechaCuotaCorregida = evitarDomingo(fechaCuota);
                    const fechaFormateada = fechaCuotaCorregida.toLocaleDateString('es-ES');
                    
                    cuotasHtml += `
                        <tr class="border-bottom">
                            <td class="text-center">${cuota.numero}</td>
                            <td>${fechaFormateada}</td>
                            <td class="text-right">S/. ${formatMoney(cuota.cuota)}</td>
                            <td class="text-right">S/. ${formatMoney(cuota.interes)}</td>
                            <td class="text-right">S/. ${formatMoney(cuota.comision)}</td>
                            <td class="text-right">S/. ${formatMoney(cuota.igv)}</td>
                            <td class="text-right">S/. ${formatMoney(cuota.saldoCapital)}</td>
                        </tr>
                    `;
                    
                    totalCuota += parseFloat(cuota.cuota);
                    totalInteres += parseFloat(cuota.interes);
                    totalComision += parseFloat(cuota.comision);
                    totalIgv += parseFloat(cuota.igv);
                });
                
                tablaCalculoBody.innerHTML = cuotasHtml;
                
                // Actualizar los totales
                document.getElementById('total-cuotas').textContent = `S/. ${formatMoney(totalCuota)}`;
                document.getElementById('total-interes').textContent = `S/. ${formatMoney(totalInteres)}`;
                document.getElementById('total-comision').textContent = `S/. ${formatMoney(totalComision)}`;
                document.getElementById('total-igv').textContent = `S/. ${formatMoney(totalIgv)}`;
            } else {
                tablaCalculoBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">No se generaron cuotas</td></tr>';
            }
            // saveFormState(); // Desactivado
        })
        .catch(error => {
            console.error('Error:', error);
            tablaCalculoBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">Error al calcular cuotas</td></tr>';
            mostrarAlerta('danger', 'Error', 'No se pudieron calcular las cuotas.');
        });
    }

    // Funciones de validación de campos
    function mostrarErrorCampo(idCampo, mensaje) {
        const campo = document.getElementById(idCampo);
        if (campo) {
            campo.classList.add('is-invalid');
            const errorMsg = campo.parentElement.querySelector('.invalid-feedback');
            if (errorMsg) {
                errorMsg.textContent = mensaje;
            }
        }
    }

    function ocultarErrorCampo(idCampo) {
        const campo = document.getElementById(idCampo);
        if (campo) {
            campo.classList.remove('is-invalid');
        }
    }

    // Validar cliente
    async function validarCliente(clienteId) {
        const messageContainer = document.getElementById('messageContainer');
        const tableContainer = document.getElementById('tableContainer');
        const message = document.getElementById('message');
        const tableBody = document.querySelector('#tablaPrestamos tbody');

        console.log('🔍 Validando cliente ID:', clienteId);

        if (!messageContainer || !tableContainer || !message || !tableBody) {
            console.error('❌ Elementos del DOM no encontrados');
            return;
        }

        // Resetear la visualización
        messageContainer.classList.add('d-none');
        tableContainer.classList.add('d-none');
        tableBody.innerHTML = '';

        if (clienteId) {
            try {
                // Paso 1: Validar si el cliente puede solicitar préstamo
                console.log('📡 Consultando validación de préstamo...');
                const response = await fetch(`/admin/validar-prestamo-cliente/${clienteId}`);
                const data = await response.json();
                console.log('✅ Respuesta de validación:', data);

                // Guardar si hay error de validación (pero NO detener la ejecución)
                const tienePrestamoActivo = !!data.error;
                const mensajeValidacion = data.error || null;

                // Paso 2: SIEMPRE consultar préstamos del cliente (independiente de la validación)
                console.log('📡 Consultando préstamos del cliente...');
                const prestamosResponse = await fetch(`/admin/consultar-prestamos/${clienteId}`);
                const prestamosData = await prestamosResponse.json();
                console.log('✅ Préstamos recibidos:', prestamosData);
                console.log('📊 Tipo de datos:', typeof prestamosData, 'Es array:', Array.isArray(prestamosData), 'Longitud:', prestamosData?.length);

                // Verificar si hay error en la respuesta de préstamos
                if (prestamosData.error) {
                    console.error('❌ Error al consultar préstamos:', prestamosData.error);
                    messageContainer.className = 'alert alert-danger p-3';
                    message.innerHTML = `<i class="fas fa-exclamation-circle mr-2"></i>${prestamosData.error}`;
                    messageContainer.classList.remove('d-none');
                    return;
                }

                // Verificar si prestamosData es un array y tiene elementos
                if (Array.isArray(prestamosData) && prestamosData.length > 0) {
                    console.log('✅ Mostrando tabla con', prestamosData.length, 'préstamos');

                    // Mostrar advertencia si tiene préstamo activo
                    if (tienePrestamoActivo && mensajeValidacion) {
                        messageContainer.className = 'alert alert-warning p-3 mb-3';
                        message.innerHTML = `<i class="fas fa-exclamation-triangle mr-2"></i><strong>Advertencia:</strong> ${mensajeValidacion}`;
                        messageContainer.classList.remove('d-none');
                    }

                    // Mostrar tabla con préstamos
                    tableContainer.classList.remove('d-none');
                    prestamosData.forEach((prestamo, index) => {
                        console.log(`  Préstamo ${index + 1}:`, prestamo);

                        // Determinar color del badge según el estado
                        let badgeClass = 'badge-secondary';
                        if (prestamo.estado === 'Vigente') badgeClass = 'badge-success';
                        else if (prestamo.estado === 'Moroso') badgeClass = 'badge-danger';
                        else if (prestamo.estado === 'Pagado' || prestamo.estado === 'Finalizado' || prestamo.estado === 'Liquidado') badgeClass = 'badge-info';
                        else if (prestamo.estado === 'Cancelado') badgeClass = 'badge-dark';
                        else if (prestamo.estado === 'En Análisis') badgeClass = 'badge-warning';

                        // Información adicional si tiene convenio
                        let estadoInfo = `<span class="badge ${badgeClass}">${prestamo.estado || 'N/A'}</span>`;
                        if (prestamo.tiene_convenio_activo && prestamo.convenio) {
                            estadoInfo += `<br><small class="text-warning"><i class="fas fa-handshake"></i> Con convenio activo</small>`;
                        }

                        const row = `
                            <tr>
                                <td>${prestamo.tipo || 'N/A'}</td>
                                <td>${prestamo.nombre || 'N/A'}</td>
                                <td>${estadoInfo}</td>
                                <td>${prestamo.fecha_solicitud ? new Date(prestamo.fecha_solicitud).toLocaleDateString('es-ES') : 'N/A'}</td>
                                <td>${prestamo.fecha_primer_pago ? new Date(prestamo.fecha_primer_pago).toLocaleDateString('es-ES') : 'N/A'}</td>
                            </tr>
                        `;
                        tableBody.insertAdjacentHTML('beforeend', row);
                    });
                    console.log('✅ Tabla de préstamos mostrada correctamente');
                } else {
                    console.log('ℹ️ No hay préstamos para mostrar');
                    // Mostrar mensaje informativo cuando no hay préstamos
                    messageContainer.className = 'alert alert-info p-3';
                    message.innerHTML = '<i class="fas fa-info-circle mr-2"></i>No existen préstamos previos para este cliente. Esta será una solicitud nueva.';
                    messageContainer.classList.remove('d-none');
                    console.log('✅ Mensaje informativo mostrado');
                }
            } catch (error) {
                console.error('❌ Error al validar cliente:', error);
                messageContainer.className = 'alert alert-danger p-3';
                message.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Error al validar el cliente. Por favor, intente nuevamente.';
                messageContainer.classList.remove('d-none');
            }
        }
    }

    // Asignar aval
    document.getElementById('btnAsignar').addEventListener('click', function() {
        const dniInput = document.getElementById('inputDni');
        const dniValue = dniInput.value.trim();
        
        if (dniValue.length !== 8 || !/^\d+$/.test(dniValue)) {
            dniInput.classList.add('is-invalid');
            mostrarAlerta('warning', 'DNI inválido', 'El DNI debe contener 8 dígitos numéricos.');
            return;
        }

        fetch("{{ route('admin.prestamos.validarAvalAntesDeAsignar') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ aval_id: dniValue })
        })
        .then(response => response.json())
        .then(data => {
            const nombreCliente = document.getElementById('nombreCliente');
            const avalDetalles = document.getElementById('avalDetalles');
            const avalInfo = document.getElementById('avalInfo');
            
            if (data.error) {
                nombreCliente.textContent = 'No encontrado';
                avalDetalles.style.display = 'none';
                mostrarAlerta('danger', 'Error', data.error);
            } else {
                nombreCliente.textContent = data.nombreAval;
                document.getElementById('hiddenAvalId').value = dniValue;
                
                // Mostrar información detallada del aval
                let avalInfoHtml = '';
                
                // 1. Información del aval como cliente
                if (data.es_cliente && data.prestamosActivos && data.prestamosActivos.length > 0) {
                    avalInfoHtml += '<div class="mb-3"><h6 class="text-primary"><i class="fas fa-user-circle me-2"></i>Préstamos del Aval:</h6>';
                    data.prestamosActivos.forEach(prestamo => {
                        const badgeColor = prestamo.estado === 'Vigente' ? 'success' : 
                                         prestamo.estado === 'Moroso' ? 'danger' : 'warning';
                        const cuotasInfo = `${prestamo.cuotas_pagadas}/${prestamo.total_cuotas} pagadas`;
                        let estadoCuotas = '';
                        if (prestamo.cuotas_vencidas > 0) estadoCuotas += `, ${prestamo.cuotas_vencidas} vencidas`;
                        if (prestamo.cuotas_parciales > 0) estadoCuotas += `, ${prestamo.cuotas_parciales} parciales`;
                        if (prestamo.cuotas_pendientes > 0) estadoCuotas += `, ${prestamo.cuotas_pendientes} pendientes`;
                        
                        avalInfoHtml += `
                            <div class="d-flex justify-content-between align-items-center p-2 border-start border-3 border-${badgeColor} bg-light mb-2">
                                <div class="flex-grow-1">
                                    <strong>Préstamo #${prestamo.id}</strong> - S/ ${new Intl.NumberFormat('es-PE').format(prestamo.monto)}
                                    <br><small class="text-muted">${cuotasInfo}${estadoCuotas}</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="text-center">
                                        <span class="badge bg-${badgeColor}">${prestamo.estado}</span>
                                        ${(prestamo.cuotas_vencidas > 0 || prestamo.cuotas_parciales > 0) ? 
                                            `<br><small class="text-danger">
                                                ${prestamo.cuotas_vencidas > 0 ? prestamo.cuotas_vencidas + ' vencidas' : ''}
                                                ${(prestamo.cuotas_vencidas > 0 && prestamo.cuotas_parciales > 0) ? ', ' : ''}
                                                ${prestamo.cuotas_parciales > 0 ? prestamo.cuotas_parciales + ' parciales' : ''}
                                            </small>` : ''
                                        }
                                    </div>
                                    <a href="{{ route('admin.prestamos.show', '') }}/${prestamo.id}" target="_blank" 
                                       class="btn btn-sm btn-outline-primary" title="Ver préstamo">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        `;
                    });
                    avalInfoHtml += '</div>';
                }
                
                // 2. Información del cónyuge
                if (data.conyuge_nombre && data.prestamosConyuge && data.prestamosConyuge.length > 0) {
                    avalInfoHtml += `<div class="mb-3"><h6 class="text-info"><i class="fas fa-heart me-2"></i>Préstamos del Cónyuge (${data.conyuge_nombre}):</h6>`;
                    data.prestamosConyuge.forEach(prestamo => {
                        const badgeColor = prestamo.estado === 'Vigente' ? 'success' : 
                                         prestamo.estado === 'Moroso' ? 'danger' : 'warning';
                        
                        avalInfoHtml += `
                            <div class="d-flex justify-content-between align-items-center p-2 border-start border-3 border-${badgeColor} bg-light mb-2">
                                <div class="flex-grow-1">
                                    <strong>Préstamo #${prestamo.id}</strong> - S/ ${new Intl.NumberFormat('es-PE').format(prestamo.monto)}
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="text-center">
                                        <span class="badge bg-${badgeColor}">${prestamo.estado}</span>
                                        ${(prestamo.cuotas_vencidas > 0 || prestamo.cuotas_parciales > 0) ? 
                                            `<br><small class="text-danger">
                                                ${prestamo.cuotas_vencidas > 0 ? prestamo.cuotas_vencidas + ' vencidas' : ''}
                                                ${(prestamo.cuotas_vencidas > 0 && prestamo.cuotas_parciales > 0) ? ', ' : ''}
                                                ${prestamo.cuotas_parciales > 0 ? prestamo.cuotas_parciales + ' parciales' : ''}
                                            </small>` : ''
                                        }
                                    </div>
                                    <a href="{{ route('admin.prestamos.show', '') }}/${prestamo.id}" target="_blank" 
                                       class="btn btn-sm btn-outline-info" title="Ver préstamo del cónyuge">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        `;
                    });
                    avalInfoHtml += '</div>';
                }
                
                // 3. Información de préstamos que avala
                if (data.prestamosAvala && data.prestamosAvala.length > 0) {
                    avalInfoHtml += '<div class="mb-3"><h6 class="text-warning"><i class="fas fa-handshake me-2"></i>Préstamos que Avala:</h6>';
                    data.prestamosAvala.forEach(prestamo => {
                        const badgeColor = prestamo.estado === 'Vigente' ? 'success' : 
                                         prestamo.estado === 'Moroso' ? 'danger' : 'warning';
                        
                        avalInfoHtml += `
                            <div class="d-flex justify-content-between align-items-center p-2 border-start border-3 border-${badgeColor} bg-light mb-2">
                                <div class="flex-grow-1">
                                    <strong>Préstamo #${prestamo.id}</strong> de <strong>${prestamo.cliente_nombre}</strong>
                                    <br><small class="text-muted">S/ ${new Intl.NumberFormat('es-PE').format(prestamo.monto)}</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="text-center">
                                        <span class="badge bg-${badgeColor}">${prestamo.estado}</span>
                                        ${(prestamo.cuotas_vencidas > 0 || prestamo.cuotas_parciales > 0) ? 
                                            `<br><small class="text-danger">
                                                ${prestamo.cuotas_vencidas > 0 ? prestamo.cuotas_vencidas + ' vencidas' : ''}
                                                ${(prestamo.cuotas_vencidas > 0 && prestamo.cuotas_parciales > 0) ? ', ' : ''}
                                                ${prestamo.cuotas_parciales > 0 ? prestamo.cuotas_parciales + ' parciales' : ''}
                                            </small>` : ''
                                        }
                                    </div>
                                    <a href="{{ route('admin.prestamos.show', '') }}/${prestamo.id}" target="_blank" 
                                       class="btn btn-sm btn-outline-warning" title="Ver préstamo avalado">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        `;
                    });
                    avalInfoHtml += '</div>';
                }
                
                // 4. Resumen de alertas
                const alertas = [];
                if (data.tieneDeuda) alertas.push('<i class="fas fa-exclamation-triangle text-warning"></i> Tiene cuotas vencidas');
                if (data.tieneDeudaConyuge) alertas.push('<i class="fas fa-exclamation-triangle text-warning"></i> Su cónyuge tiene cuotas vencidas');
                if (data.tieneDeudaAvalados) alertas.push('<i class="fas fa-exclamation-triangle text-danger"></i> Los préstamos que avala tienen cuotas vencidas');
                
                if (alertas.length > 0) {
                    avalInfoHtml += '<div class="alert alert-warning"><h6>⚠️ Alertas:</h6>' + alertas.join('<br>') + '</div>';
                } else {
                    avalInfoHtml += '<div class="alert alert-success"><i class="fas fa-check-circle"></i> El aval no presenta observaciones</div>';
                }
                
                // Si no hay información, mostrar mensaje
                if (avalInfoHtml === '') {
                    if (data.es_cliente) {
                        avalInfoHtml = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> El aval es cliente pero no tiene préstamos activos</div>';
                    } else {
                        avalInfoHtml = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Persona registrada - No es cliente</div>';
                    }
                }
                
                avalInfo.innerHTML = avalInfoHtml;
                avalDetalles.style.display = 'block';
                
                // Mostrar alerta de éxito
                if (data.tieneDeuda || data.tieneDeudaConyuge || data.tieneDeudaAvalados) {
                    mostrarAlerta('warning', 'Aval con observaciones', 'El aval tiene observaciones. Revisa los detalles antes de continuar.');
                } else {
                    mostrarAlerta('success', 'Aval verificado', 'El aval ha sido verificado correctamente y no presenta observaciones.');
                }
            }
            // saveFormState(); // Desactivado
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('danger', 'Error', 'No se pudo verificar el aval.');
        });
    });

    // Cargar direcciones y cuentas
    function cargarDireccionesYCuentas(clienteId) {
        if (!clienteId) return;

        const urlDirecciones = "{{ route('admin.clientes.direcciones', ['clienteId' => ':clienteId']) }}".replace(':clienteId', clienteId);
        const urlCuentas = "{{ route('admin.clientes.cuentas', ['clienteId' => ':clienteId']) }}".replace(':clienteId', clienteId);
        
        const selectDireccion = document.getElementById('selectDireccionCobro');
        const selectCuenta = document.getElementById('selectCuentaCliente');
        
        fetch(urlDirecciones)
            .then(response => response.json())
            .then(data => {
                selectDireccion.innerHTML = '<option value="" disabled>Selecciona una dirección</option>';
                data.direcciones.forEach((direccion, index) => {
                    const direccionCompleta = direccion.numero ? `${direccion.direccion} N° ${direccion.numero}` : direccion.direccion;
                    const option = new Option(direccionCompleta, direccion.id, index === 0, index === 0);
                    selectDireccion.add(option);
                });
            })
            .catch(error => console.error('Error al cargar direcciones:', error));

        if (selectCuenta) {
            fetch(urlCuentas)
                .then(response => response.json())
                .then(data => {
                    selectCuenta.innerHTML = '<option value="">Seleccione una cuenta (opcional)</option>';
                    data.cuentas.forEach((cuenta, index) => {
                        let label = '';

                        // Verificar si tiene entidad bancaria (banco)
                        if (cuenta.entidad_bancaria && cuenta.entidad_bancaria.banco) {
                            // Formato: "BCP: 555555"
                            label = `${cuenta.entidad_bancaria.banco}: ${cuenta.numero_cuenta || 'S/N'}`;
                        }
                        // Verificar si tiene billetera digital (Yape, Plin, etc.)
                        else if (cuenta.billetera_digital && cuenta.billetera_digital.nombre) {
                            // Formato: "Yape: 987654321"
                            label = `${cuenta.billetera_digital.nombre}: ${cuenta.numero_cuenta || 'S/N'}`;
                        }
                        // Verificar si tiene tipo de cuenta
                        else if (cuenta.tipo_cuenta && cuenta.tipo_cuenta.tipo_cuenta) {
                            const tipoCuenta = cuenta.tipo_cuenta.tipo_cuenta.toUpperCase();
                            if (tipoCuenta === 'EFECTIVO') {
                                label = 'EFECTIVO';
                            } else {
                                label = cuenta.numero_cuenta ? `${tipoCuenta}: ${cuenta.numero_cuenta}` : tipoCuenta;
                            }
                        }
                        else {
                            // Fallback si no hay información
                            label = cuenta.numero_cuenta || 'Cuenta sin identificar';
                        }

                        const option = new Option(label, cuenta.id, index === 0, index === 0);
                        selectCuenta.add(option);
                    });
                })
                .catch(error => console.error('Error al cargar cuentas:', error));
        }
    }

    // Validación del formulario y confirmación para guardar
    document.getElementById('prestamoForm').addEventListener('submit', function(e) {
        e.preventDefault();
        let valid = true;
        
        // Validar campos requeridos
        this.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) {
                valid = false;
                mostrarErrorCampo(field.id || field.name, 'Por favor completa este campo.');
            }
        });

        if (!valid) {
            mostrarAlerta('danger', 'Formulario incompleto', 'Complete todos los campos obligatorios.');
            return;
        }

        mostrarAlerta('info', 'Confirmar guardado', '¿Seguro que desea guardar el préstamo?', () => {
            localStorage.removeItem('prestamo_form_state');
            localStorage.removeItem('prestamo_form_timestamp');
            this.submit();
        });
    });

    // AUTOGUARDADO DESACTIVADO
    function saveFormState() {
        // Función desactivada - no guardar datos del formulario
        return;
    }

    function restoreFormState() {
        // Limpiar cualquier dato guardado anteriormente
        localStorage.removeItem('prestamo_form_state');
        localStorage.removeItem('prestamo_form_timestamp');
        return; // No restaurar datos
    }

    // AUTOGUARDADO DESACTIVADO
    // setInterval(saveFormState, 5000); // Desactivado
    // document.getElementById('prestamoForm').addEventListener('input', debounce(saveFormState, 300)); // Desactivado
    // document.getElementById('prestamoForm').addEventListener('change', saveFormState); // Desactivado

    // Guardar antes de cerrar
    let isSubmitting = false;
    document.getElementById('prestamoForm').addEventListener('submit', function() {
        isSubmitting = true;
    });

    // ADVERTENCIA DE SALIDA DESACTIVADA
    // window.addEventListener('beforeunload', function(e) {
    //     saveFormState();
    //     if (localStorage.getItem('prestamo_form_state') && !isSubmitting) {
    //         e.preventDefault();
    //         e.returnValue = 'Tienes cambios no guardados. ¿Seguro que quieres salir?';
    //     }
    // });

    // Restaurar al cargar
    restoreFormState();

    // Cargar datos del cliente si viene preseleccionado
    const clienteIdFromUrl = "{{ request('cliente_id') }}";
    if (clienteIdFromUrl) {
        console.log('Cliente preseleccionado ID:', clienteIdFromUrl);
        // Buscar información del cliente preseleccionado por ID
        fetch(`{{ route('admin.clientes.buscar-prestamo') }}?q=${clienteIdFromUrl}`)
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta de búsqueda:', data);
                if (data.results && data.results.length > 0) {
                    // Buscar el cliente exacto por ID
                    const clientePreseleccionado = data.results.find(c => c.id == clienteIdFromUrl);
                    if (clientePreseleccionado) {
                        console.log('Cliente encontrado:', clientePreseleccionado);
                        seleccionarCliente(clienteIdFromUrl, clientePreseleccionado.nombre);
                        // Dar más tiempo para que se carguen las validaciones
                        setTimeout(() => {
                            calcularCuotas();
                        }, 1500);
                    } else {
                        console.warn('Cliente con ID', clienteIdFromUrl, 'no encontrado en resultados');
                    }
                } else {
                    console.warn('No se encontraron resultados para cliente ID:', clienteIdFromUrl);
                    mostrarAlerta('warning', 'Cliente no disponible', 'El cliente seleccionado no está disponible para préstamos o no se encontró.');
                }
            })
            .catch(error => {
                console.error('Error al cargar cliente preseleccionado:', error);
                mostrarAlerta('danger', 'Error', 'No se pudo cargar la información del cliente seleccionado.');
            });
    }

    // CONFIRMACIÓN DE CANCELACIÓN DESACTIVADA
    // document.getElementById('cancelBtn').addEventListener('click', function(e) {
    //     if (localStorage.getItem('prestamo_form_state')) {
    //         e.preventDefault();
    //         mostrarAlerta('warning', 'Confirmar cancelación', '¿Seguro que desea cancelar? Los datos no guardados se perderán.', () => {
    //             localStorage.removeItem('prestamo_form_state');
    //             localStorage.removeItem('prestamo_form_timestamp');
    //             window.location.href = this.href;
    //         });
    //     }
    // });

    // Función para inicializar fechas
    function inicializarFechas() {
        const fechaHoy = new Date();
        const fechaAtencionInput = document.getElementById('fecha_atencion');
        const fechaPrimerPagoInput = document.getElementById('fecha_primer_pago');
        
        // Establecer fecha de atención como hoy
        fechaAtencionInput.value = formatearFecha(fechaHoy);
        
        // Calcular fecha de primer pago (+7 días evitando domingo)
        let fechaPrimerPago = new Date(fechaHoy);
        fechaPrimerPago.setDate(fechaPrimerPago.getDate() + 7);
        fechaPrimerPago = evitarDomingo(fechaPrimerPago);
        
        fechaPrimerPagoInput.value = formatearFecha(fechaPrimerPago);
    }

    // Inicializar estados
    inicializarFechas();
    toggleAvalFields();
    updateTipoSolicitudButtons();

    // MANEJO DEL SIDEBAR DESLIZABLE DE CLIENTE

    // Función para abrir sidebar
    function openClienteSidebar() {
        console.log('Abriendo sidebar de cliente...');
        const sidebar = document.getElementById('clienteSidebar');
        if (sidebar) {
            sidebar.classList.add('active');
            document.body.style.overflow = 'hidden';
            console.log('Sidebar abierto correctamente');
        } else {
            console.error('No se encontró el elemento #clienteSidebar');
        }
    }

    // Función para cerrar sidebar
    function closeSidebar() {
        const sidebar = document.getElementById('clienteSidebar');
        if (sidebar) {
            sidebar.classList.remove('active');
            document.body.style.overflow = '';
            console.log('Sidebar cerrado');
        }
    }

    // Abrir sidebar - usar delegación de eventos para asegurar que funcione
    $(document).on('click', '#openClienteSidebar, #openClienteSidebarLink', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Click detectado en botón de sidebar');
        openClienteSidebar();
    });

    // Cerrar sidebar
    $(document).on('click', '#closeSidebar', function(e) {
        e.preventDefault();
        closeSidebar();
    });

    // Cerrar sidebar al hacer clic en el overlay
    $(document).on('click', '#clienteSidebar', function(e) {
        if (e.target === this) {
            closeSidebar();
        }
    });

    // Cerrar sidebar con tecla Escape
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#clienteSidebar').hasClass('active')) {
            closeSidebar();
        }
    });

    // Escuchar mensajes del iframe para manejar el registro exitoso de cliente
    window.addEventListener('message', function(event) {
        // Verificar que el mensaje viene del iframe correcto
        if (event.origin !== window.location.origin) return;
        
        if (event.data.type === 'clienteRegistrado') {
            const clienteData = event.data.cliente;
            const fullName = `${clienteData.persona.nombres} ${clienteData.persona.ape_pat} ${clienteData.persona.ape_mat}`;
            
            // Seleccionar el cliente automáticamente
            seleccionarCliente(clienteData.id, fullName);
            
            // Mostrar mensaje de éxito en el formulario principal
            mostrarAlerta('success', '¡Cliente registrado!', 
                'El cliente ha sido registrado correctamente y está seleccionado en el formulario.');
            
            // Cerrar sidebar
            closeSidebar();
        } else if (event.data.type === 'closeSidebar') {
            // Cerrar sidebar cuando se cancela desde el iframe
            closeSidebar();
        }
    });
});

</script>
@stop