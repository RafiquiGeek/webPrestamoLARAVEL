@extends('layouts.admin')
@section('title', 'Rendición de Usuario - ' . $usuario->codigo)
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-hand-holding-usd mr-2"></i>Rendición de Usuario: {{ $usuario->codigo }}</h1>
       <ol class="breadcrumb float-sm-right">
           <li class="breadcrumb-item"><a href="{{ route('admin.caja.index') }}">Caja</a></li>
           <li class="breadcrumb-item active">Rendición Usuario</li>
       </ol>
   </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Información del Usuario -->
    <div class="account-card">
        <!--div class="card-header">
            <h3><i class="fas fa-user me-2"></i>Información del Usuario</h3>
        </div-->
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="info-card">
                        <div class="info-label">Código</div>
                        <div class="info-value">{{ $usuario->codigo }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-card">
                        <div class="info-label">Nombre</div>
                        <div class="info-value">{{ $usuario->name }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-card">
                        <div class="info-label">Email</div>
                        <div class="info-value small">{{ $usuario->email }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-card">
                        <div class="info-label">Total Pendiente</div>
                        <div class="info-value text-warning">S/ {{ number_format($totalPendiente, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($operaciones->count() > 0)
        <!-- Formulario de Rendición -->
        <form id="form-rendicion" action="{{ route('admin.caja.procesarRendicionParcial') }}" method="POST">
            @csrf
            <input type="hidden" name="user_id" value="{{ $usuario->id }}">

            <!-- Tabla de Operaciones -->
            <div class="account-card">
                <div class="card-header">
                    <h3><i class="fas fa-table me-2"></i>Operaciones en Efectivo Pendientes</h3>
                    <div class="d-flex align-items-center gap-3">
                        <label class="mb-0">
                            <input type="checkbox" id="select-all" class="me-2">Seleccionar Todas
                        </label>
                        <span class="badge bg-warning" id="contador-seleccionadas">0 seleccionadas</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">
                                        <i class="fas fa-check-square"></i>
                                    </th>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Tipo Operación</th>
                                    <th>Cliente</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($operaciones as $operacion)
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" 
                                                   name="operaciones_seleccionadas[]" 
                                                   value="{{ $operacion->id }}"
                                                   class="operacion-checkbox"
                                                   data-monto="{{ $operacion->abono }}">
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">{{ $operacion->id }}</span>
                                        </td>
                                        <td>
                                            <div class="info-value small">{{ \Carbon\Carbon::parse($operacion->fecha)->format('d/m/Y') }}</div>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($operacion->fecha)->format('H:i') }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">
                                                {{ $operacion->tipo_operacion }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($operacion->prestamo && $operacion->prestamo->cliente)
                                                <div class="info-label">{{ $operacion->prestamo->cliente->persona->nombres ?? 'N/A' }}</div>
                                                <small class="text-muted">{{ $operacion->prestamo->cliente->persona->apellidos ?? '' }}</small>
                                            @else
                                                <span class="text-muted">Sin cliente</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="info-value">S/ {{ number_format($operacion->abono, 2) }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning">Pendiente</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5" class="text-end">Total Seleccionado:</th>
                                    <th class="text-end">
                                        <div class="info-value" id="total-seleccionado">S/ 0.00</div>
                                    </th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="w-100 d-flex p-4" style="justify-content: flex-end;">
                    <button type="submit" class="btn btn-outline-primary btn-sm mr-4" id="btn-rendir-seleccionadas" disabled>
                        <i class="fas fa-check me-1"></i>Rendir Seleccionadas
                    </button>

                    <a href="{{ route('admin.caja.rendirTodoUsuario', $usuario->id) }}" 
                        class="btn btn-outline-primary btn-sm btn-rendir-todo">
                        <i class="fas fa-hand-holding-usd me-1"></i>Rendir Todo
                    </a>
                </div>
                
                </div>
            </div>
            <!-- Acciones 
            <div class="account-card">
                <div class="card-header">
                    <h3><i class="fas fa-tasks me-2"></i>Acciones de Rendición</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-card text-center">
                                <div class="info-label">
                                    <i class="fas fa-list-check me-1"></i>Rendición Parcial
                                </div>
                                <div class="info-value mb-2">Seleccionar operaciones</div>
                                <button type="submit" class="btn btn-outline-primary btn-sm" id="btn-rendir-seleccionadas" disabled>
                                    <i class="fas fa-check me-1"></i>Rendir Seleccionadas
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card text-center">
                                <div class="info-label">
                                    <i class="fas fa-money-bill-wave me-1"></i>Rendición Completa
                                </div>
                                <div class="info-value mb-2">S/ {{ number_format($totalPendiente, 2) }}</div>
                                <a href="{{ route('admin.caja.rendirTodoUsuario', $usuario->id) }}" 
                                   class="btn btn-outline-primary btn-sm btn-rendir-todo">
                                    <i class="fas fa-hand-holding-usd me-1"></i>Rendir Todo
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>-->
        </form>
    @else
        <!-- Sin operaciones -->
        <div class="account-card">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h4 class="text-success">¡Perfecto!</h4>
                <p class="text-muted">Este usuario no tiene operaciones en efectivo pendientes de rendición.</p>
                <a href="{{ route('admin.caja.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i>Volver a Caja
                </a>
            </div>
        </div>
    @endif
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    let totalPendiente = {{ $totalPendiente }};
    
    // Manejar selección individual
    $('.operacion-checkbox').on('change', function() {
        actualizarTotales();
        actualizarBotones();
    });
    
    // Manejar seleccionar todas
    $('#select-all').on('change', function() {
        $('.operacion-checkbox').prop('checked', $(this).is(':checked'));
        actualizarTotales();
        actualizarBotones();
    });
    
    function actualizarTotales() {
        let totalSeleccionado = 0;
        let cantidadSeleccionada = 0;
        
        $('.operacion-checkbox:checked').each(function() {
            totalSeleccionado += parseFloat($(this).data('monto'));
            cantidadSeleccionada++;
        });
        
        $('#total-seleccionado').text('S/ ' + totalSeleccionado.toFixed(2));
        $('#contador-seleccionadas').text(cantidadSeleccionada + ' seleccionadas');
        
        // Actualizar el checkbox "seleccionar todas"
        let totalCheckboxes = $('.operacion-checkbox').length;
        $('#select-all').prop('checked', cantidadSeleccionada === totalCheckboxes && totalCheckboxes > 0);
    }
    
    function actualizarBotones() {
        let haySeleccionadas = $('.operacion-checkbox:checked').length > 0;
        $('#btn-rendir-seleccionadas').prop('disabled', !haySeleccionadas);
    }
    
    // Confirmar rendición completa
    $('.btn-rendir-todo').on('click', function(e) {
        e.preventDefault();
        let url = $(this).attr('href');
        
        if (confirm(`¿Está seguro de rendir TODAS las operaciones pendientes por un total de S/ ${totalPendiente.toFixed(2)}?`)) {
            window.location.href = url;
        }
    });
    
    // Confirmar rendición parcial
    $('#form-rendicion').on('submit', function(e) {
        let cantidadSeleccionada = $('.operacion-checkbox:checked').length;
        let totalSeleccionado = 0;
        
        $('.operacion-checkbox:checked').each(function() {
            totalSeleccionado += parseFloat($(this).data('monto'));
        });
        
        if (cantidadSeleccionada === 0) {
            e.preventDefault();
            alert('Debe seleccionar al menos una operación para rendir.');
            return false;
        }
        
        if (!confirm(`¿Está seguro de rendir ${cantidadSeleccionada} operaciones por un total de S/ ${totalSeleccionado.toFixed(2)}?`)) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@stop

@section('css')
<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
/* Estilos consistentes con el módulo principal */
.account-card {
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.account-card .card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.account-card .card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0;
}

.account-card .card-body {
    padding: 1.5rem;
}

.info-card {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 8px;
    margin-bottom: 0.75rem;
    width: 100%;
}

.info-card .info-label {
    font-size: 0.75rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    font-weight: 600;
}

.info-card .info-value {
    font-size: 1rem;
    font-weight: 600;
    color: #1a1a1a;
}

.btn-outline-primary {
    border-color: #005566;
    color: #005566;
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: all 0.2s;
}

.btn-outline-primary:hover {
    background-color: #005566;
    color: #ffffff;
}

.operacion-checkbox {
    transform: scale(1.2);
}

.table th {
    font-weight: 600;
    color: #495057;
    font-size: 0.875rem;
}

.badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.35em 0.6em;
}

#contador-seleccionadas {
    font-size: 0.875rem;
}
</style>
@stop