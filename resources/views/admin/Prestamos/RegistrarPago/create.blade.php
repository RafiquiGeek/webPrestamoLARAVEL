@extends('layouts.admin')

@section('title', 'Registrar Pagos')

@section('content')
<div class="container-fluid payment-registration">
    <!-- Header Compacto -->
    <div class="page-header mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title h4 mb-1">Registrar Pago de Cuota</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0 mb-0 small">
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.prestamos.index') }}" class="text-primary">Préstamos</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Registrar Pago</li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('admin.prestamos.show', ['prestamo' => $prestamo->id]) }}" 
                   class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-arrow-left mr-1"></i>Volver
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.registrarpago.store') }}" method="POST" enctype="multipart/form-data" id="paymentForm" novalidate>
        @csrf
        <input type="hidden" name="prestamo_id" value="{{ $prestamo->id }}">
        <input type="hidden" name="cuota_id" id="cuota_id" value="{{ $cuota_id }}">

        <div class="row">
            <!-- Columna Izquierda: Panel Principal -->
            <div class="col-lg-8">
                <!-- Información del Cliente -->
                <div class="card shadow-sm mb-3">
                    <div class="card-body p-3">
                        <h6 class="card-title mb-3">
                            <i class="fas fa-user-circle mr-2 text-primary"></i>Información del Cliente
                        </h6>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-placeholder bg-primary text-white mr-3">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="client-info">
                                        <h6 class="mb-1">
                                            @if($prestamo && $prestamo->cliente && $prestamo->cliente->persona)
                                                {{ $prestamo->cliente->persona->nombres }} 
                                                {{ $prestamo->cliente->persona->ape_pat }} 
                                                {{ $prestamo->cliente->persona->ape_mat }}
                                            @else
                                                No disponible
                                            @endif
                                        </h6>
                                        <div class="loan-tags">
                                            <span class="badge badge-primary badge-sm">
                                                Préstamo #{{ $prestamo->id ?? 'No disponible' }}
                                            </span>
                                            <span class="badge badge-{{ $prestamo->estado == 'Vigente' ? 'success' : 'warning' }} badge-sm">
                                                {{ $prestamo->estado }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-right">
                                    <small class="text-muted d-block">Total Pendiente</small>
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">Cuotas:</small>
                                            <div class="font-weight-bold text-primary">S/ {{ number_format($totalCuotas, 2) }}</div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Moras:</small>
                                            <div class="font-weight-bold text-danger">S/ {{ number_format($totalMoras, 2) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Método de Pago y Usuario -->
                <div class="card shadow-sm mb-3">
                    <div class="card-body p-3">
                        <h6 class="card-title mb-3">
                            <i class="fas fa-credit-card mr-2 text-primary"></i>Datos del Pago
                        </h6>
                        
                        <div class="row">
                            <!-- Usuario que Registra -->
                            <div class="col-md-4 mb-3">
                                <label for="user_id" class="form-label small font-weight-bold">Usuario que Registra <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm @error('user_id') is-invalid @enderror" 
                                        id="user_id" name="user_id" required>
                                    <option value="">Seleccionar Usuario</option>
                                    @foreach($usuarios as $usuario)
                                        <option value="{{ $usuario->id }}" 
                                            {{ (old('user_id') ? old('user_id') == $usuario->id : auth()->id() == $usuario->id) ? 'selected' : '' }}>
                                            {{ $usuario->codigo }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('user_id')
                                    <div class="invalid-feedback small">{{ $message }}</div>
                                @enderror
                            </div>
                            <!-- Métodos de Pago -->
                            <div class="mb-3 col-md-8">
                                <label class="form-label small font-weight-bold">Método de Pago <span class="text-danger">*</span></label>
                                <div class="payment-methods-grid">
                                    @foreach($metodosDePago as $metodo)
                                        <div class="payment-method-option">
                                            <input type="radio" 
                                                id="metodo{{ $metodo->id }}" 
                                                name="metodoPago" 
                                                value="{{ $metodo->id }}" 
                                                required>
                                            <label for="metodo{{ $metodo->id }}" class="payment-label">
                                                @switch($metodo->metodo_pago)
                                                    @case('EFECTIVO')
                                                        <i class="fas fa-money-bill-wave text-success"></i>
                                                        @break
                                                    @case('TRANSFERENCIA')
                                                        <i class="fas fa-exchange-alt text-primary"></i>
                                                        @break
                                                    @default
                                                        <i class="fas fa-credit-card text-info"></i>
                                                @endswitch
                                                <span class="d-block small mt-1">{{ $metodo->metodo_pago }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        

                        <!-- Campos Adicionales para Transferencia/Tarjeta -->
                        <div id="payment-extra-fields" class="payment-extra-section" style="display:none;">
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label for="entidad_bancaria" class="form-label small">Entidad Bancaria</label>
                                    <select class="form-control form-control-sm"
                                            id="entidad_bancaria"
                                            name="entidad_bancaria">
                                        <option value="">Seleccionar entidad</option>
                                        <!-- Las opciones se cargarán dinámicamente con JavaScript -->
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label for="nro_operacion" class="form-label small">Número de Operación</label>
                                    <input type="text" class="form-control form-control-sm" 
                                           id="nro_operacion" 
                                           name="nro_operacion" 
                                           placeholder="Ingrese número">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label for="fecha_operacion" class="form-label small">Fecha de Operación</label>
                                    <input type="datetime-local" class="form-control form-control-sm" 
                                           id="fecha_operacion" 
                                           name="fecha_operacion" 
                                           value="{{ old('fecha_operacion', date('Y-m-d\TH:i')) }}"
                                           max="{{ date('Y-m-d\TH:i') }}"
                                           title="No se permite seleccionar fechas futuras">
                                    <small class="form-text text-muted">No se pueden registrar pagos con fecha futura</small>
                                </div>
                                <div class="col-12">
                                    <label for="voucher" class="form-label small">Comprobante (Opcional)</label>
                                    <div class="custom-file custom-file-sm">
                                        <input type="file" class="custom-file-input" 
                                               id="voucher" 
                                               name="voucher" 
                                               accept="image/*">
                                        <label class="custom-file-label" for="voucher">
                                            Seleccionar archivo
                                        </label>
                                    </div>
                                    <div id="voucher-preview-container" class="mt-2 text-center" style="display:none;">
                                        <img id="voucher-preview" 
                                             class="img-fluid rounded max-height-100" 
                                             alt="Vista previa">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Campos para Efectivo -->
                        <div id="cash-code-container" class="payment-extra-section" style="display:none;">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label for="codigo" class="form-label small">Código de Operación</label>
                                    <input type="text" class="form-control form-control-sm" 
                                        id="codigo" 
                                        name="codigo" 
                                        placeholder="Ingrese código de operación">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="fecha_codigo" class="form-label small">Fecha y Hora del Pago</label>
                                    <input type="datetime-local" class="form-control form-control-sm" 
                                        id="fecha_codigo" 
                                        name="fecha_codigo" 
                                        value="{{ old('fecha_codigo', date('Y-m-d\TH:i')) }}"
                                        max="{{ date('Y-m-d\TH:i') }}"
                                        title="No se permite seleccionar fechas futuras">
                                    <small class="form-text text-muted">No se pueden registrar pagos con fecha futura</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detalles de Cuotas y Moras -->
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <h6 class="card-title mb-3">
                            <i class="fas fa-list-alt mr-2 text-primary"></i>Detalles Pendientes
                        </h6>
                        
                        <div class="details-tabs">
                            <ul class="nav nav-pills nav-pills-sm nav-justified mb-3" id="detailsTabs">
                                <li class="nav-item">
                                    <a class="nav-link active" 
                                    id="cuotas-tab" 
                                    data-toggle="tab" 
                                    href="#cuotas-content">
                                        Cuotas <span class="badge badge-light badge-sm ml-1">
                                            {{ count($cuotasPendientes) }}
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" 
                                    id="moras-tab" 
                                    data-toggle="tab" 
                                    href="#moras-content">
                                        Moras <span class="badge badge-light badge-sm ml-1">
                                            {{ count($morasPendientes) }}
                                        </span>
                                    </a>
                                </li>
                            </ul>
                            
                            <div class="tab-content">
                                <!-- Contenido de Cuotas -->
                                <div class="tab-pane fade show active" id="cuotas-content">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th class="text-center">Cuota</th>
                                                    <th class="text-center">Vencimiento</th>
                                                    <th class="text-center">Monto</th>
                                                    <th class="text-center">Estado</th>
                                                    <th class="text-center">Progreso</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($cuotasPendientes as $cuota)
                                                @php
                                                    // Usamos el método del modelo para obtener el color correcto
                                                    $estadoBadgeClass = str_replace(['bg-', 'text-dark'], '', $cuota->estado_color);
                                                    
                                                    // Para la barra de progreso usamos la misma lógica de colores
                                                    $progressClass = $estadoBadgeClass;
                                                    
                                                    // Si es el gradiente personalizado para parcial, podemos usar warning como fallback en la barra de progreso
                                                    if ($progressClass == 'gradient-amber-to-green') {
                                                        $progressClass = 'warning';
                                                    }
                                                    
                                                    $abonoTotal = $cuota->operaciones->sum('abono');
                                                    $porcentajeAbono = $cuota->monto > 0 ? min(($abonoTotal / $cuota->monto) * 100, 100) : 0;
                                                @endphp
                                                <tr>
                                                    <td class="text-center font-weight-bold">{{ $cuota->numero }}</td>
                                                    <td class="text-center">
                                                        <small class="badge badge-soft-secondary">
                                                            {{ $cuota->fecha_pago_formateada ?? 'No disponible' }}
                                                        </small>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="font-weight-bold">
                                                            S/. {{ number_format($cuota->monto, 2) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge badge-{{ $estadoBadgeClass }} badge-sm">
                                                            {{ $cuota->estado_nombre }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex align-items-center justify-content-center">
                                                            <div class="progress flex-grow-1 mr-2" style="height: 4px; max-width: 60px;">
                                                                <div class="progress-bar bg-{{ $progressClass }}" 
                                                                    role="progressbar" 
                                                                    style="width: {{ $porcentajeAbono }}%" 
                                                                    aria-valuenow="{{ $porcentajeAbono }}" 
                                                                    aria-valuemin="0" 
                                                                    aria-valuemax="100">
                                                                </div>
                                                            </div>
                                                            <small class="text-muted">
                                                                {{ number_format($porcentajeAbono, 0) }}%
                                                            </small>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center py-3">
                                                        <div class="alert alert-info py-2 mb-0">
                                                            <i class="fas fa-info-circle mr-1"></i>
                                                            No hay cuotas pendientes
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Contenido de Moras -->
                                <div class="tab-pane fade" id="moras-content">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th class="text-center">Cuota</th>
                                                    <th class="text-center">Días Mora</th>
                                                    <th class="text-center">Monto</th>
                                                    <th class="text-center">Estado</th>
                                                    <th class="text-center">Fecha Cálculo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($morasPendientes as $mora)
                                                    @php
                                                        // Aplicamos la misma lógica de colores que en las cuotas
                                                        $estadoClass = match($mora->estado) {
                                                            'Pagado' => 'success',
                                                            'Pago parcial' => 'warning',
                                                            default => 'danger'
                                                        };
                                                        
                                                        // Para moras nuevas, calcular progreso basado en monto pendiente
                                                        $abonoTotal = 0;
                                                        $porcentajeAbono = 0;
                                                        
                                                        if (isset($mora->operaciones) && $mora->operaciones) {
                                                            $abonoTotal = $mora->operaciones->sum('abono');
                                                            $porcentajeAbono = $mora->monto > 0 ? 
                                                                            min(($abonoTotal / $mora->monto) * 100, 100) : 0;
                                                        } else if (isset($mora->monto_pendiente)) {
                                                            $porcentajeAbono = $mora->monto > 0 ? 
                                                                            min((($mora->monto - $mora->monto_pendiente) / $mora->monto) * 100, 100) : 0;
                                                        }
                                                        
                                                        // Determinar número de cuota
                                                        $numeroCuota = $mora->cuota_numero ?? ($mora->cuota ? $mora->cuota->numero : 'N/A');
                                                    @endphp
                                                    <tr class="{{ str_contains($mora->id, 'nueva_') ? 'table-warning' : '' }}">
                                                        <td class="text-center font-weight-bold">
                                                            #{{ $numeroCuota }}
                                                            @if(str_contains($mora->id, 'nueva_'))
                                                                <span class="badge badge-warning badge-sm ml-1">Nueva</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-danger">
                                                                {{ $mora->dias_mora ?? 0 }} días
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="text-danger font-weight-bold">
                                                                S/ {{ number_format($mora->monto_pendiente ?? $mora->monto, 2) }}
                                                            </span>
                                                            @if(isset($mora->monto_pendiente) && $mora->monto_pendiente < $mora->monto)
                                                                <small class="text-muted d-block">
                                                                    (Total: S/ {{ number_format($mora->monto, 2) }})
                                                                </small>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-{{ $estadoClass }} badge-sm">
                                                                @if(str_contains($mora->id, 'nueva_'))
                                                                    Calculada
                                                                @else
                                                                    @switch($mora->estado)
                                                                        @case('Pagado')
                                                                            Pagada
                                                                            @break
                                                                        @case('Pago parcial')
                                                                            Parcial
                                                                            @break
                                                                        @default
                                                                            Pendiente
                                                                    @endswitch
                                                                @endif
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <small class="text-muted">
                                                                {{ $mora->fecha_formateada ?? 'Hoy' }}
                                                            </small>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="text-center py-3">
                                                            <div class="alert alert-info py-2 mb-0">
                                                                <i class="fas fa-info-circle mr-1"></i>
                                                                No hay cuotas con mora calculada
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    @if($morasPendientes->count() > 0)
                                        <div class="alert alert-warning mt-3 mb-0">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-calculator mr-2"></i>
                                                <div>
                                                    <strong>Cálculo de Moras:</strong>
                                                    <small class="d-block">
                                                        Las moras se calculan dinámicamente basándose en la fecha de pago ingresada.
                                                        Monto: <strong>S/ {{ number_format($prestamo->mora ?? 5.00, 2) }}</strong> por día de atraso.
                                                        <br><strong>Máximo 7 días de mora por cuota.</strong>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Panel de Pago -->
            <div class="col-lg-4">
                <div class="card payment-card shadow-sm sticky-top">
                    <div class="card-header text-primary py-3">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-money-check-alt mr-2"></i>Resumen del Pago
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="mb-3">
                            <label class="form-label small font-weight-bold">
                                Monto a Abonar en Cuotas
                                @if(isset($montoCuotaPorDefecto) && $montoCuotaPorDefecto > 0)
                                    <small class="text-muted">(Esta cuota debe: S/ {{ number_format($montoCuotaPorDefecto, 2) }})</small>
                                @endif
                            </label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">S/</span>
                                </div>
                                <input type="number" 
                                    class="form-control" 
                                    id="abono_cuotas" 
                                    name="abono_cuotas" 
                                    placeholder="Monto para cuotas" 
                                    step="0.01"
                                    min="0"
                                    value="{{ old('abono_cuotas', isset($montoCuotaPorDefecto) && $montoCuotaPorDefecto > 0 ? number_format($montoCuotaPorDefecto, 2, '.', '') : '0') }}"
                                    required>
                            </div>
                            @if(isset($montoCuotaPorDefecto) && $montoCuotaPorDefecto > 0)
                                <small class="text-info">💡 Saldo pendiente de esta cuota específica</small>
                            @endif
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small font-weight-bold">
                                Monto a Abonar en Moras
                                @if(isset($montoMoraPorDefecto) && $montoMoraPorDefecto > 0)
                                    <small class="text-muted">(Mora calculada: S/ {{ number_format($montoMoraPorDefecto, 2) }})</small>
                                @endif
                            </label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">S/</span>
                                </div>
                                <input type="number" 
                                    class="form-control" 
                                    id="abono_moras" 
                                    name="abono_moras" 
                                    placeholder="Monto para moras (opcional)"
                                    step="0.01"
                                    min="0"
                                    value="{{ old('abono_moras', '0') }}">
                            </div>
                            @if(isset($montoMoraPorDefecto) && $montoMoraPorDefecto > 0)
                                <small class="text-info">💡 Mora calculada disponible: S/ {{ number_format($montoMoraPorDefecto, 2) }}</small>
                            @else
                                <small class="text-muted">Puede dejar en 0 si no hay moras que pagar</small>
                            @endif
                        </div>

                        <div class="payment-summary p-3 bg-light rounded">
                            <div class="d-flex justify-content-between mb-2 small">
                                <span>Subtotal Cuotas:</span>
                                <span id="subtotal_cuotas">S/. 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 small">
                                <span>Subtotal Moras:</span>
                                <span id="subtotal_moras">S/. 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between total-row pt-2 border-top">
                                <span class="font-weight-bold">TOTAL:</span>
                                <span id="total_pago" class="font-weight-bold text-primary">S/. 0.00</span>
                            </div>
                        </div>

                        <!-- Información sobre comprobantes -->
                        <div class="alert alert-info mt-3 mb-3 small">
                            <i class="fas fa-info-circle mr-1"></i>
                            <strong>Nota:</strong> Los comprobantes electrónicos se pueden generar desde el estado de cuenta una vez realizado el pago.
                        </div>

                        <div class="action-buttons mt-3">
                            <button type="submit" class="btn btn-primary btn-block btn-sm" id="submitPaymentBtn">
                                <i class="fas fa-save mr-2"></i>Registrar Pago
                            </button>
                            <a href="{{ route('admin.prestamos.index') }}" 
                            class="btn btn-outline-secondary btn-block btn-sm mt-2">
                                <i class="fas fa-times-circle mr-2"></i>Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('css')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --primary-color: #1e88e5;
        --secondary-color: #546e7a;
        --text-color: #37474f;
        --border-color: #e0e0e0;
        --background-color: #f5f7fa;
        --card-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    body {
        background-color: var(--background-color);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        color: var(--text-color);
        font-size: 0.9rem;
    }

    .page-title {
        color: #263238;
        font-weight: 600;
    }

    /* Tarjetas */
    .card {
        border: none;
        border-radius: 8px;
        box-shadow: var(--card-shadow);
        transition: all 0.2s ease;
        overflow: hidden;
    }

    .card-header {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    .card-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #455a64;
        margin-bottom: 0;
    }

    .card-body {
        padding: 1rem;
    }

    /* Avatar placeholder */
    .avatar-placeholder {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    /* Pills tabs más compactos */
    .nav-pills-sm .nav-link {
        border-radius: 6px;
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
        color: var(--secondary-color);
    }

    .nav-pills .nav-link.active {
        background-color: var(--primary-color);
        color: white;
    }

    /* Método de pago en grid */
    .payment-methods-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 10px;
        margin-bottom: 1rem;
    }

    .payment-method-option {
        position: relative;
    }

    .payment-method-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        cursor: pointer;
    }

    .payment-label {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 10px 8px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        background-color: white;
        transition: all 0.2s ease;
        height: 70px;
        font-size: 0.8rem;
    }

    .payment-label i {
        font-size: 1.1rem;
        margin-bottom: 4px;
    }

    .payment-method-option input[type="radio"]:checked + .payment-label {
        background-color: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    /* Secciones de campos extra */
    .payment-extra-section {
        background-color: #f8f9fa;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }

    /* Progress bar */
    .progress {
        border-radius: 10px;
        overflow: hidden;
        background-color: #e9ecef;
        height: 4px;
    }

    .progress-bar {
        border-radius: 10px;
    }

    /* Form controls más compactos */
    .form-control {
        border-radius: 6px;
        border-color: #e0e0e0;
        font-size: 0.85rem;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.15rem rgba(30, 136, 229, 0.15);
    }

    .form-control-sm {
        height: calc(1.5em + 0.4rem + 2px);
        font-size: 0.8rem;
        padding: 0.2rem 0.5rem;
    }

    .input-group-sm .input-group-text {
        padding: 0.2rem 0.5rem;
        font-size: 0.8rem;
    }

    .input-group-text {
        background-color: #f8f9fa;
        border-color: #e0e0e0;
        font-size: 0.85rem;
    }

    .custom-file-sm .custom-file-label {
        height: calc(1.5em + 0.4rem + 2px);
        padding: 0.2rem 0.5rem;
        font-size: 0.8rem;
        line-height: 1.5;
    }

    .custom-file-input:focus ~ .custom-file-label {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.15rem rgba(30, 136, 229, 0.15);
    }

    /* Botones más compactos */
    .btn {
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
        transition: all 0.2s ease;
    }

    .btn-sm {
        padding: 0.3rem 0.6rem;
        font-size: 0.8rem;
    }

    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-primary:hover {
        background-color: #1976d2;
        border-color: #1976d2;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .btn-outline-primary {
        color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-outline-primary:hover {
        background-color: var(--primary-color);
        color: white;
    }

    /* Payment summary más compacto */
    .payment-summary {
        font-size: 0.85rem;
    }

    .total-row {
        font-size: 0.95rem;
    }

    /* Badges más pequeños */
    .badge {
        font-weight: 500;
        padding: 0.25em 0.5em;
    }

    .badge-sm {
        font-size: 0.7rem;
        padding: 0.2em 0.4em;
    }

    .badge-soft-secondary {
        background-color: rgba(108, 117, 125, 0.15);
        color: #6c757d;
    }

    .badge-gradient-amber-to-green {
        background: linear-gradient(to right, #ffc107, #28a745);
        color: #fff;
    }

    /* Sticky sidebar */
    .sticky-top {
        top: 1rem;
    }

    /* Vista previa de imagen más pequeña */
    .max-height-100 {
        max-height: 100px;
    }

    /* Tablas más compactas */
    .table {
        font-size: 0.8rem;
    }

    .table-sm th,
    .table-sm td {
        padding: 0.4rem;
    }

    .thead-light th {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Form labels más pequeños */
    .form-label {
        font-size: 0.8rem;
        margin-bottom: 0.3rem;
        color: #495057;
    }

    .form-label.small {
        font-size: 0.75rem;
    }

    /* Alertas más compactas */
    .alert {
        padding: 0.5rem 0.75rem;
        font-size: 0.8rem;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        .payment-methods-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .sticky-top {
            position: relative !important;
            top: auto !important;
        }
    }

    @media (max-width: 768px) {
        .payment-methods-grid {
            grid-template-columns: 1fr;
        }
        
        .card-body {
            padding: 0.75rem;
        }
        
        .avatar-placeholder {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }
        
        .table-responsive {
            font-size: 0.75rem;
        }
    }

    @media (max-width: 576px) {
        .page-header .d-flex {
            flex-direction: column;
            align-items: flex-start !important;
        }
        
        .page-header .d-flex > div:last-child {
            margin-top: 0.5rem;
        }
        
        .client-info h6 {
            font-size: 0.9rem;
        }
        
        .loan-tags .badge {
            font-size: 0.65rem;
        }
    }

    /* Mejoras adicionales para UX */
    .form-control:invalid {
        border-color: #dc3545;
    }

    .form-control:valid {
        border-color: #28a745;
    }

    .payment-method-option:hover .payment-label {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }

    /* Loading state */
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Text utilities */
    .text-xs {
        font-size: 0.7rem;
    }

    .text-sm {
        font-size: 0.8rem;
    }

    /* Spacing utilities */
    .mb-half {
        margin-bottom: 0.25rem;
    }

    .mt-half {
        margin-top: 0.25rem;
    }

    /* Comprobante section */
    .comprobante-section {
        background-color: #f0f8ff;
        border: 1px solid #b3d9ff;
        border-radius: 6px;
        padding: 0.75rem;
        margin-top: 0.5rem;
        transition: all 0.3s ease;
    }

    .form-check-input:checked ~ .form-check-label {
        color: var(--primary-color);
        font-weight: 500;
    }
</style>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Métodos de Pago
    const paymentMethodRadios = document.querySelectorAll('input[name="metodoPago"]');
    const extraFieldsContainer = document.getElementById('payment-extra-fields');
    const cashCodeContainer = document.getElementById('cash-code-container');
    const voucherInput = document.getElementById('voucher');
    const voucherPreviewContainer = document.getElementById('voucher-preview-container');
    const voucherPreview = document.getElementById('voucher-preview');

    // Variables para totales
    const abonoCuotasInput = document.getElementById('abono_cuotas');
    const abonoMorasInput = document.getElementById('abono_moras');
    const totalPagoElement = document.getElementById('total_pago');
    const subtotalCuotasElement = document.getElementById('subtotal_cuotas');
    const subtotalMorasElement = document.getElementById('subtotal_moras');
    const totalCuotas = parseFloat(document.getElementById('total_cuotas')?.value) || parseFloat('{{ $totalCuotas }}') || 0;
    const totalMoras = parseFloat(document.getElementById('total_moras')?.value) || parseFloat('{{ $totalMoras }}') || 0;

    // Configuración de entidades bancarias
    const entidadesBancarias = {
        transferencia: [
            'BCP', 'BBVA', 'Interbank', 'Scotiabank', 'BanBif',
            'MiBanco', 'Alfin', 'Banco de Comercio',
            'Banco Pichincha', 'Banco de la Nación'
        ],
        yape_plin: [
            'Yape', 'Plin', 'Dale', 'Tunki', 'Bim', 'Lukita', 'Agora Pay'
        ]
    };

    // Función para actualizar opciones de entidad bancaria
    function actualizarEntidadesBancarias(metodoPago) {
        const entidadSelect = document.getElementById('entidad_bancaria');
        if (!entidadSelect) return;

        // Limpiar opciones existentes
        entidadSelect.innerHTML = '<option value="">Seleccionar entidad</option>';

        let opciones = [];

        // Determinar qué opciones mostrar según el método de pago
        switch(metodoPago) {
            case '2': // TRANSFERENCIA/DEPÓSITO
                opciones = entidadesBancarias.transferencia;
                break;
            case '3': // YAPE/PLIN/BILLETERAS DIGITALES
                opciones = entidadesBancarias.yape_plin;
                break;
            case '4': // Si tienes otro método para billeteras digitales
                opciones = entidadesBancarias.yape_plin;
                break;
        }

        // Agregar las opciones al select
        opciones.forEach(entidad => {
            const option = document.createElement('option');
            option.value = entidad;
            option.textContent = entidad;
            entidadSelect.appendChild(option);
        });
    }

    // Mostrar campos según el método de pago
    paymentMethodRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            // Ocultar todos los campos adicionales
            extraFieldsContainer.style.display = 'none';
            cashCodeContainer.style.display = 'none';

            // Mostrar campos según método de pago
            switch(this.value) {
                case '1': // EFECTIVO
                    cashCodeContainer.style.display = 'block';
                    break;
                case '2': // TRANSFERENCIA
                case '3': // TARJETA/YAPE/PLIN
                case '4': // Si tienes otro ID para billeteras
                    extraFieldsContainer.style.display = 'block';
                    actualizarEntidadesBancarias(this.value);
                    break;
            }
        });
    });

    // Vista previa de voucher
    if (voucherInput) {
        voucherInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    voucherPreview.src = event.target.result;
                    voucherPreviewContainer.style.display = 'block';
                    
                    // Actualizar el nombre del archivo en la etiqueta
                    const fileName = file.name;
                    const label = voucherInput.nextElementSibling;
                    label.textContent = fileName.length > 25 ? fileName.substring(0, 22) + '...' : fileName;
                };
                reader.readAsDataURL(file);
            } else {
                voucherPreviewContainer.style.display = 'none';
                voucherInput.nextElementSibling.textContent = 'Seleccionar archivo';
            }
        });
    }

    // Cálculo de totales
    function calculateTotalPayment() {
        const cuotasAmount = parseFloat(abonoCuotasInput.value) || 0;
        const morasAmount = parseFloat(abonoMorasInput.value) || 0;
        const totalPago = cuotasAmount + morasAmount;

        subtotalCuotasElement.textContent = `S/. ${cuotasAmount.toFixed(2)}`;
        subtotalMorasElement.textContent = `S/. ${morasAmount.toFixed(2)}`;
        totalPagoElement.textContent = `S/. ${totalPago.toFixed(2)}`;
        
        // Cambiar el color del total según el monto
        if (totalPago > 0) {
            totalPagoElement.classList.add('text-primary');
            totalPagoElement.classList.remove('text-muted');
        } else {
            totalPagoElement.classList.remove('text-primary');
            totalPagoElement.classList.add('text-muted');
        }
    }

    // Eventos para cálculo de totales
    if (abonoCuotasInput) abonoCuotasInput.addEventListener('input', calculateTotalPayment);
    if (abonoMorasInput) abonoMorasInput.addEventListener('input', calculateTotalPayment);

    // Ir a detalles
    window.showDetails = function(type) {
        // Seleccionar la subpestaña adecuada
        if (type === 'cuotas') {
            document.getElementById('cuotas-tab').click();
        } else if (type === 'moras') {
            document.getElementById('moras-tab').click();
        }
    };

    // Inicializar totales
    calculateTotalPayment();

    // Los comprobantes se gestionan desde el estado de cuenta

    // Validación de formulario
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        e.preventDefault();

        // Obtener valores de cuotas y moras
        const cuotasAmount = parseFloat(abonoCuotasInput.value) || 0;
        const morasAmount = parseFloat(abonoMorasInput.value) || 0;
        const totalPago = cuotasAmount + morasAmount;

        // Validar usuario que registra
        const userId = document.getElementById('user_id').value;
        if (!userId) {
            Swal.fire({
                icon: 'warning',
                title: 'Usuario Requerido',
                text: 'Debe seleccionar el usuario que registra el pago',
                confirmButtonColor: '#1e88e5'
            });
            return;
        }

        // Validaciones de montos
        if (totalPago <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Monto Inválido',
                text: 'Debe ingresar un monto mayor a cero en cuotas o moras',
                confirmButtonColor: '#1e88e5'
            });
            return;
        }

        // Validar que no se exceda el monto pendiente solo si hay un monto ingresado
        if (cuotasAmount > 0 && cuotasAmount > totalCuotas) {
            Swal.fire({
                icon: 'warning',
                title: 'Monto de Cuotas Excedido',
                text: `El monto a pagar en cuotas (S/. ${cuotasAmount.toFixed(2)}) no puede ser mayor al total pendiente (S/. ${totalCuotas.toFixed(2)})`,
                confirmButtonColor: '#1e88e5'
            });
            return;
        }

        // Nota: Ya no validamos límite de moras porque se calculan dinámicamente
        // if (morasAmount > 0 && morasAmount > totalMoras) {
        //     Swal.fire({
        //         icon: 'warning',
        //         title: 'Monto de Moras Excedido',
        //         text: `El monto a pagar en moras (S/. ${morasAmount.toFixed(2)}) no puede ser mayor al total pendiente (S/. ${totalMoras.toFixed(2)})`,
        //         confirmButtonColor: '#1e88e5'
        //     });
        //     return;
        // }

        // Validar método de pago
        const selectedPaymentMethod = document.querySelector('input[name="metodoPago"]:checked');
        if (!selectedPaymentMethod) {
            Swal.fire({
                icon: 'warning',
                title: 'Método de Pago',
                text: 'Debe seleccionar un método de pago',
                confirmButtonColor: '#1e88e5'
            });
            return;
        }

        // Validar campos adicionales según el método de pago
        const paymentMethodValue = selectedPaymentMethod.value;
        
        // Validación para método de EFECTIVO
        if (paymentMethodValue === '1') {
            const fechaCodigoEfectivo = document.getElementById('fecha_codigo').value.trim();

            // El código de operación es opcional, solo validamos la fecha
            if (!fechaCodigoEfectivo) {
            Swal.fire({
                icon: 'warning',
                title: 'Fecha del Pago',
                text: 'Debe ingresar la fecha y hora del pago en efectivo',
                confirmButtonColor: '#1e88e5'
            });
            return;
            }
        }

        // Validación para métodos de transferencia o tarjeta
        if (['2', '3'].includes(paymentMethodValue)) {
            const nroOperacion = document.getElementById('nro_operacion').value.trim();
            const fechaOperacion = document.getElementById('fecha_operacion').value;

            if (!nroOperacion) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Número de Operación',
                    text: 'Debe ingresar un número de operación',
                    confirmButtonColor: '#1e88e5'
                });
                return;
            }

            if (!fechaOperacion) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Fecha de Operación',
                    text: 'Debe seleccionar una fecha de operación',
                    confirmButtonColor: '#1e88e5'
                });
                return;
            }

            // Validar que la fecha de operación no sea futura
            const fechaOperacionDate = new Date(fechaOperacion);
            const fechaActual = new Date();
            if (fechaOperacionDate > fechaActual) {
                Swal.fire({
                    icon: 'error',
                    title: 'Fecha Inválida',
                    text: 'No se pueden registrar pagos con fecha futura. Seleccione una fecha actual o pasada.',
                    confirmButtonColor: '#1e88e5'
                });
                return;
            }
        }

        // Validar fecha del pago en efectivo (si está presente)
        const fechaCodigo = document.getElementById('fecha_codigo').value;
        if (fechaCodigo) {
            const fechaCodigoDate = new Date(fechaCodigo);
            const fechaActual = new Date();
            if (fechaCodigoDate > fechaActual) {
                Swal.fire({
                    icon: 'error',
                    title: 'Fecha Inválida',
                    text: 'No se pueden registrar pagos con fecha futura. Seleccione una fecha actual o pasada.',
                    confirmButtonColor: '#1e88e5'
                });
                return;
            }
        }

        // Obtener fecha de pago según el método
        let fechaPago = '';
        let fechaTexto = '';
        
        if (paymentMethodValue === '1') {
            // Para efectivo, usar fecha_codigo
            fechaPago = document.getElementById('fecha_codigo').value;
        } else if (['2', '3'].includes(paymentMethodValue)) {
            // Para transferencia/tarjeta, usar fecha_operacion
            fechaPago = document.getElementById('fecha_operacion').value;
        }
        
        // Formatear fecha para mostrar
        if (fechaPago) {
            const fecha = new Date(fechaPago);
            fechaTexto = fecha.toLocaleString('es-ES', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Confirmación de pago
        Swal.fire({
            title: '¿Confirmar Pago?',
            html: `
                <div class="payment-confirmation">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Cuotas:</span>
                        <span class="font-weight-bold">S/. ${cuotasAmount.toFixed(2)}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Moras:</span>
                        <span class="font-weight-bold">S/. ${morasAmount.toFixed(2)}</span>
                    </div>
                    ${fechaTexto ? `
                    <div class="d-flex justify-content-between mb-3">
                        <span>Fecha de Pago:</span>
                        <span class="font-weight-bold text-info">${fechaTexto}</span>
                    </div>` : ''}
                    <div class="d-flex justify-content-between pt-2 border-top">
                        <span class="font-weight-bold">TOTAL:</span>
                        <span class="font-weight-bold text-primary">S/. ${totalPago.toFixed(2)}</span>
                    </div>
                </div>
                <small class="text-muted mt-3 d-block">Verifique que todos los datos sean correctos</small>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1e88e5',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Confirmar Pago',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Deshabilitar botón de submit para evitar doble envío
                const submitButton = document.getElementById('submitPaymentBtn');
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Procesando...';

                // Enviar formulario
                this.submit();
            }
        });
    });
    
    // Funcionalidad para mostrar la etiqueta del archivo seleccionado
    document.querySelectorAll('.custom-file-input').forEach(inputElement => {
        inputElement.addEventListener('change', function(e){
            const fileName = this.files[0]?.name;
            const nextSibling = this.nextElementSibling;
            if (fileName) {
                nextSibling.textContent = fileName.length > 25 ? fileName.substring(0, 22) + '...' : fileName;
            } else {
                nextSibling.textContent = 'Seleccionar archivo';
            }
        });
    });
    
    // Añadir efecto de hover a las filas de tablas
    document.querySelectorAll('.table-hover tr').forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(0,0,0,0.03)';
            this.style.transition = 'background-color 0.2s ease';
        });
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });

    // Auto-focus en el campo de abono de cuotas (más útil que el usuario)
    const firstInput = document.querySelector('#abono_cuotas');
    if (firstInput) {
        firstInput.focus();
    }

    // Validación en tiempo real para montos
    [abonoCuotasInput, abonoMorasInput].forEach(input => {
        if (input) {
            input.addEventListener('blur', function() {
                const value = parseFloat(this.value) || 0;
                
                // Solo validar cuotas, las moras se calculan dinámicamente
                if (this.id === 'abono_cuotas') {
                    const maxValue = totalCuotas;
                    
                    if (value > maxValue) {
                        this.classList.add('is-invalid');
                        if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('invalid-feedback')) {
                            const feedback = document.createElement('div');
                            feedback.className = 'invalid-feedback';
                            feedback.textContent = `El monto no puede ser mayor a S/. ${maxValue.toFixed(2)}`;
                            this.parentNode.appendChild(feedback);
                        }
                    } else {
                        this.classList.remove('is-invalid');
                        const feedback = this.parentNode.querySelector('.invalid-feedback');
                        if (feedback) {
                            feedback.remove();
                        }
                    }
                } else {
                    // Para moras, solo removemos validaciones previas
                    this.classList.remove('is-invalid');
                    const feedback = this.parentNode.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.remove();
                    }
                }
            });
        }
    });
});
// En tu formulario de liquidación
function validarDescuentos() {
    const descuentoMoras = parseFloat($('#descuento_moras').val()) || 0;
    const totalMoras = parseFloat($('#total_moras_pendientes').val()) || 0;
    
    if (descuentoMoras > totalMoras) {
        alert('El descuento en moras no puede ser mayor al total de moras pendientes');
        return false;
    }
    
    return true;
}
</script>
@endsection