@extends('layouts.admin')

@section('title', 'Editar Convenio #' . $convenio->id)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card" style="background: var(--bg-primary); border: 1px solid var(--border-primary); box-shadow: var(--shadow-sm);">
                <div class="card-header" style="background: var(--bg-secondary); border-bottom: 1px solid var(--border-primary);">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="card-title mb-0" style="color: var(--text-primary);">
                                <i class="fas fa-edit me-2" style="color: var(--text-secondary);"></i>Editar Convenio #{{ $convenio->id }}
                            </h3>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb bg-transparent p-0 mb-0 small">
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('admin.convenios.index') }}" style="color: var(--text-secondary);">Convenios</a>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('admin.convenios.show', $convenio->id) }}" style="color: var(--text-secondary);">
                                            Convenio #{{ $convenio->id }}
                                        </a>
                                    </li>
                                    <li class="breadcrumb-item active" style="color: var(--text-secondary);" aria-current="page">Editar</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="{{ route('admin.convenios.show', $convenio->id) }}" 
                               class="btn btn-sm" style="border: 1px solid var(--border-primary); color: var(--text-secondary); background: var(--bg-primary);">
                                <i class="fas fa-arrow-left me-2"></i>Volver
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body" style="background: var(--bg-secondary);">
                    @if(session('success'))
                        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('admin.convenios.update', $convenio->id) }}" method="POST" id="editConvenioForm">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <!-- Columna Izquierda: Información del Cliente y Convenio -->
                            <div class="col-md-8">
                                <!-- Información del Cliente y Préstamo -->
                                <div class="card mb-4" style="background: var(--bg-primary); border: 1px solid var(--border-primary); box-shadow: var(--shadow-sm);">
                                    <div class="card-header" style="background: var(--bg-primary); border-bottom: 1px solid var(--border-primary);">
                                        <h6 class="mb-0" style="color: var(--text-primary);">
                                            <i class="fas fa-user me-2" style="color: var(--text-secondary);"></i>Información del Cliente
                                        </h6>
                                    </div>
                                    <div class="card-body" style="background: var(--bg-primary);">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle p-2 me-3" style="background: var(--primary);">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1" style="color: var(--text-primary);">
                                                    {{ $convenio->prestamo->cliente->persona->nombres }} 
                                                    {{ $convenio->prestamo->cliente->persona->ape_pat }} 
                                                    {{ $convenio->prestamo->cliente->persona->ape_mat }}
                                                </h6>
                                                <div>
                                                    <span class="badge me-1" style="background: var(--primary); color: white;">
                                                        Préstamo #{{ $convenio->prestamo->id }}
                                                    </span>
                                                    <span class="badge me-1" style="background: var(--warning); color: white;">
                                                        {{ $convenio->prestamo->estado }}
                                                    </span>
                                                    <span class="badge" style="background: var(--gray-700); color: white;">
                                                        {{ $convenio->estado->label() }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Datos del Convenio -->
                                <div class="card mb-4" style="background: var(--bg-primary); border: 1px solid var(--border-primary); box-shadow: var(--shadow-sm);">
                                    <div class="card-header" style="background: var(--bg-primary); border-bottom: 1px solid var(--border-primary);">
                                        <h6 class="mb-0" style="color: var(--text-primary);">
                                            <i class="fas fa-calculator me-2" style="color: var(--text-secondary);"></i>Datos del Convenio
                                        </h6>
                                    </div>
                                    <div class="card-body" style="background: var(--bg-primary);">
                                        <div class="row">
                                            <!-- Montos (Solo Lectura) -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-medium" style="color: var(--text-primary);">Monto Capital</label>
                                                <div class="input-group">
                                                    <span class="input-group-text" style="background: var(--gray-700); color: white; border: 1px solid var(--border-primary);">S/.</span>
                                                    <input type="text" class="form-control" style="border: 1px solid var(--border-primary); border-left: 0; background: var(--bg-secondary); color: var(--text-primary);" 
                                                           value="{{ number_format($convenio->monto_capital, 2) }}" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-medium" style="color: var(--text-primary);">Monto Moras</label>
                                                <div class="input-group">
                                                    <span class="input-group-text" style="background: var(--gray-700); color: white; border: 1px solid var(--border-primary);">S/.</span>
                                                    <input type="text" class="form-control" style="border: 1px solid var(--border-primary); border-left: 0; background: var(--bg-secondary); color: var(--text-primary);" 
                                                           value="{{ number_format($convenio->monto_moras, 2) }}" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-medium" style="color: var(--text-primary);">Descuento Moras</label>
                                                <div class="input-group">
                                                    <span class="input-group-text" style="background: var(--gray-700); color: white; border: 1px solid var(--border-primary);">S/.</span>
                                                    <input type="text" class="form-control" style="border: 1px solid var(--border-primary); border-left: 0; background: var(--bg-secondary); color: var(--text-primary);" 
                                                           value="{{ number_format($convenio->descuento_moras, 2) }}" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-medium" style="color: var(--text-primary);">Total Convenio</label>
                                                <div class="input-group">
                                                    <span class="input-group-text" style="background: var(--success); color: white; border: 1px solid var(--border-primary);">S/.</span>
                                                    <input type="text" class="form-control fw-bold" style="border: 1px solid var(--border-primary); border-left: 0; background: var(--bg-secondary); color: var(--text-primary);" 
                                                           id="total_convenio" value="{{ number_format($convenio->total_convenio, 2) }}" readonly>
                                                </div>
                                            </div>

                                            <!-- Campos Editables -->
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label fw-medium" style="color: var(--text-primary);">
                                                    Plazo de Pago *
                                                </label>
                                                <div class="row g-2">
                                                    @foreach($opcionesCuotas as $opcion)
                                                        <div class="col-4">
                                                            <input type="radio" 
                                                                   id="cuotas{{ $opcion }}_edit" 
                                                                   name="numero_cuotas" 
                                                                   value="{{ $opcion }}" 
                                                                   class="btn-check"
                                                                   {{ (old('numero_cuotas', $convenio->numero_cuotas) == $opcion) ? 'checked' : '' }}
                                                                   onchange="calcularNuevoValorCuota(); mostrarAlertaCambios();"
                                                                   required>
                                                            <label for="cuotas{{ $opcion }}_edit" class="btn btn-plazo w-100 text-center py-3" style="border: 2px solid var(--border-primary); color: var(--text-secondary); background: var(--bg-primary); transition: all 0.3s ease;">
                                                                <div class="fw-bold fs-5" style="color: var(--primary);">{{ $opcion }}</div>
                                                                <small class="d-block">cuotas semanales</small>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @error('numero_cuotas')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="form-text" style="color: var(--text-secondary);">
                                                    Frecuencia: Pagos semanales (cada 7 días)
                                                </small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-medium" style="color: var(--text-primary);">
                                                    Nuevo Valor por Cuota
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text" style="background: var(--info); color: white; border: 1px solid var(--border-primary);">S/.</span>
                                                    <input type="text" 
                                                           class="form-control fw-bold" 
                                                           style="border: 1px solid var(--border-primary); border-left: 0; background: var(--bg-primary); color: var(--primary);" 
                                                           id="nuevo_valor_cuota"
                                                           value="{{ number_format($convenio->valor_cuota, 2) }}" 
                                                           readonly>
                                                </div>
                                                <small class="form-text" style="color: var(--text-secondary);">
                                                    Se calcula automáticamente al cambiar el número de cuotas
                                                </small>
                                            </div>

                                            <!-- Fechas Editables -->
                                            <div class="col-md-6 mb-3">
                                                <label for="fecha_inicio" class="form-label fw-medium" style="color: var(--text-primary);">
                                                    Fecha de Inicio *
                                                </label>
                                                <input type="date" 
                                                       class="form-control @error('fecha_inicio') is-invalid @enderror" 
                                                       style="border: 1px solid var(--border-primary); background: var(--bg-primary); color: var(--text-primary);" 
                                                       id="fecha_inicio" 
                                                       name="fecha_inicio" 
                                                       value="{{ old('fecha_inicio', $convenio->fecha_inicio->format('Y-m-d')) }}"
                                                       onchange="mostrarAlertaCambios();"
                                                       required>
                                                @error('fecha_inicio')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="fecha_firma" class="form-label fw-medium" style="color: var(--text-primary);">
                                                    Fecha de Firma *
                                                </label>
                                                <input type="date" 
                                                       class="form-control @error('fecha_firma') is-invalid @enderror" 
                                                       style="border: 1px solid var(--border-primary); background: var(--bg-primary); color: var(--text-primary);" 
                                                       id="fecha_firma" 
                                                       name="fecha_firma" 
                                                       value="{{ old('fecha_firma', $convenio->fecha_firma->format('Y-m-d')) }}"
                                                       onchange="mostrarAlertaCambios();"
                                                       max="{{ date('Y-m-d') }}"
                                                       required>
                                                @error('fecha_firma')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Alerta de Cambios -->
                                            <div class="col-12" id="cambios-alert" style="display: none;">
                                                <div class="alert alert-warning border-0 shadow-sm" role="alert">
                                                    <h6 class="alert-heading">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>¡Atención!
                                                    </h6>
                                                    <p class="mb-2">Los cambios en el plazo de pago o fechas afectarán:</p>
                                                    <ul class="mb-0">
                                                        <li><strong>Cuotas semanales:</strong> Se regenerarán automáticamente con el nuevo plazo</li>
                                                        <li><strong>Pagos realizados:</strong> Se mantendrán y redistribuirán según corresponda</li>
                                                        <li><strong>Fechas de vencimiento:</strong> Se recalcularán semanalmente desde la nueva fecha de inicio</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna Derecha: Campos Editables y Controles -->
                            <div class="col-md-4">
                                <!-- Observaciones Editables -->
                                <div class="card mb-4" style="background: var(--bg-primary); border: 1px solid var(--border-primary); box-shadow: var(--shadow-sm);">
                                    <div class="card-header" style="background: var(--gray-700); color: white;">
                                        <h6 class="mb-0">
                                            <i class="fas fa-edit me-2"></i>Observaciones
                                        </h6>
                                    </div>
                                    <div class="card-body" style="background: var(--bg-primary);">
                                        <div class="mb-3">
                                            <textarea class="form-control @error('observaciones') is-invalid @enderror" 
                                                      style="border: 1px solid var(--border-primary); background: var(--bg-primary); color: var(--text-primary);" 
                                                      id="observaciones" 
                                                      name="observaciones" 
                                                      rows="6" 
                                                      maxlength="1000"
                                                      placeholder="Ingrese observaciones sobre el convenio...">{{ old('observaciones', $convenio->observaciones) }}</textarea>
                                            @error('observaciones')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text char-counter" style="color: var(--text-secondary);">
                                                0/1000 caracteres
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información de Estado -->
                                <div class="card mb-4" style="background: var(--bg-primary); border: 1px solid var(--border-primary); box-shadow: var(--shadow-sm);">
                                    <div class="card-header" style="background: var(--info); color: white;">
                                        <h6 class="mb-0">
                                            <i class="fas fa-info-circle me-2"></i>Estado Actual
                                        </h6>
                                    </div>
                                    <div class="card-body" style="background: var(--bg-primary);">
                                        <div class="mb-3">
                                            <div class="p-2 rounded text-center" style="background: var(--bg-secondary); border: 1px solid var(--border-primary);">
                                                <span class="badge bg-{{ $convenio->estado === \App\Enums\ConvenioEstado::ACTIVO ? 'success' : 
                                                    ($convenio->estado === \App\Enums\ConvenioEstado::CUMPLIDO ? 'primary' : 'secondary') }}">
                                                    <i class="fas fa-{{ $convenio->estado === \App\Enums\ConvenioEstado::ACTIVO ? 'check-circle' : 
                                                        ($convenio->estado === \App\Enums\ConvenioEstado::CUMPLIDO ? 'star' : 'times-circle') }} me-1"></i>
                                                    {{ $convenio->estado->label() }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Resumen de Cuotas -->
                                        @php
                                            $cuotasPagadas = $convenio->cuotasConvenio()->where('estado', \App\Enums\CuotaConvenio::PAGADO)->count();
                                            $cuotasParciales = $convenio->cuotasConvenio()->where('estado', \App\Enums\CuotaConvenio::PARCIAL)->count();
                                            $cuotasPendientes = $convenio->cuotasConvenio()->whereIn('estado', [\App\Enums\CuotaConvenio::PENDIENTE, \App\Enums\CuotaConvenio::VENCIDO])->count();
                                        @endphp
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <div class="text-center p-2 rounded" style="background: var(--success); color: white;">
                                                    <div class="fw-bold">{{ $cuotasPagadas }}</div>
                                                    <small>Pagadas</small>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="text-center p-2 rounded" style="background: var(--info); color: white;">
                                                    <div class="fw-bold">{{ $cuotasParciales }}</div>
                                                    <small>Parciales</small>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="text-center p-2 rounded" style="background: var(--warning); color: white;">
                                                    <div class="fw-bold">{{ $cuotasPendientes }}</div>
                                                    <small>Pendientes</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones de Acción -->
                                <div class="card mb-4" style="background: var(--bg-primary); border: 1px solid var(--border-primary); box-shadow: var(--shadow-sm);">
                                    <div class="card-body text-center" style="background: var(--bg-primary);">
                                        <button type="submit" class="btn mb-2 w-100" style="background: var(--gray-700); color: white; border: none;">
                                            <i class="fas fa-save me-2"></i>Guardar Cambios
                                        </button>
                                        <a href="{{ route('admin.convenios.show', $convenio->id) }}" 
                                           class="btn w-100" style="border: 1px solid var(--border-primary); color: var(--text-secondary); background: var(--bg-secondary);">
                                            <i class="fas fa-times me-2"></i>Cancelar
                                        </a>
                                    </div>
                                </div>

                                <!-- Información Adicional -->
                                <div class="card" style="background: var(--bg-primary); border: 1px solid var(--border-primary); box-shadow: var(--shadow-sm);">
                                    <div class="card-header" style="background: var(--warning); color: white;">
                                        <h6 class="mb-0">
                                            <i class="fas fa-exclamation-circle me-2"></i>Importante
                                        </h6>
                                    </div>
                                    <div class="card-body" style="background: var(--bg-primary);">
                                        <small style="color: var(--text-secondary);">
                                            <ul class="mb-0 ps-3">
                                                <li><strong>Plazo de pago:</strong> Cambiará el número de cuotas semanales y el valor de cada cuota automáticamente</li>
                                                <li><strong>Fecha de inicio:</strong> Recalculará todas las fechas de vencimiento (semanalmente)</li>
                                                <li><strong>Fecha de firma:</strong> Solo afecta el registro histórico</li>
                                                <li><strong>Pagos realizados:</strong> Se mantienen y redistribuyen según las nuevas cuotas</li>
                                            </ul>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para botones de plazo */
.btn-check {
    display: none !important;
}

.btn-plazo {
    transition: all 0.3s ease !important;
    border-radius: 8px !important;
}

.btn-plazo:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
    border-color: var(--primary) !important;
}

.btn-check:checked + .btn-plazo {
    background: var(--primary) !important;
    border-color: var(--primary) !important;
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important;
}

.btn-check:checked + .btn-plazo .fw-bold {
    color: white !important;
}

.btn-check:checked + .btn-plazo small {
    color: rgba(255,255,255,0.9) !important;
}
</style>

<script>
// Variables iniciales
const totalConvenio = {{ $convenio->total_convenio }};
const numeroCuotasOriginal = {{ $convenio->numero_cuotas }};
const fechaInicioOriginal = '{{ $convenio->fecha_inicio->format('Y-m-d') }}';
const fechaFirmaOriginal = '{{ $convenio->fecha_firma->format('Y-m-d') }}';

function calcularNuevoValorCuota() {
    const cuotasRadio = document.querySelector('input[name="numero_cuotas"]:checked');
    const numeroCuotas = cuotasRadio ? parseInt(cuotasRadio.value) : 0;
    
    if (numeroCuotas > 0) {
        const nuevoValorCuota = totalConvenio / numeroCuotas;
        document.getElementById('nuevo_valor_cuota').value = nuevoValorCuota.toFixed(2);
    }
}

function mostrarAlertaCambios() {
    const cuotasRadio = document.querySelector('input[name="numero_cuotas"]:checked');
    const numeroCuotasActual = cuotasRadio ? parseInt(cuotasRadio.value) : 0;
    const fechaInicioActual = document.getElementById('fecha_inicio').value;
    const fechaFirmaActual = document.getElementById('fecha_firma').value;
    
    const hayChanges = (numeroCuotasActual !== numeroCuotasOriginal) || 
                      (fechaInicioActual !== fechaInicioOriginal) || 
                      (fechaFirmaActual !== fechaFirmaOriginal);
    
    const alertDiv = document.getElementById('cambios-alert');
    alertDiv.style.display = hayChanges ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    // Contador de caracteres para observaciones
    const observacionesTextarea = document.getElementById('observaciones');
    const charCounter = document.querySelector('.char-counter');
    
    function updateCharCounter() {
        const currentLength = observacionesTextarea.value.length;
        const maxLength = 1000;
        const remaining = maxLength - currentLength;
        
        charCounter.textContent = `${currentLength}/${maxLength} caracteres`;
        charCounter.style.color = remaining < 50 ? 'var(--danger)' : 'var(--text-secondary)';
    }
    
    if (observacionesTextarea && charCounter) {
        observacionesTextarea.addEventListener('input', updateCharCounter);
        updateCharCounter(); // Llamada inicial
    }

    // Verificar cambios iniciales
    mostrarAlertaCambios();

    // Confirmación antes de guardar
    document.getElementById('editConvenioForm').addEventListener('submit', function(e) {
        const cuotasRadio = document.querySelector('input[name="numero_cuotas"]:checked');
        const numeroCuotasActual = cuotasRadio ? parseInt(cuotasRadio.value) : 0;
        const fechaInicioActual = document.getElementById('fecha_inicio').value;
        
        let mensaje = '¿Está seguro de que desea guardar los cambios realizados al convenio?';
        
        if (numeroCuotasActual !== numeroCuotasOriginal) {
            const nuevoValor = parseFloat(document.getElementById('nuevo_valor_cuota').value);
            mensaje += `\n\n• Plazo de pago: ${numeroCuotasOriginal} → ${numeroCuotasActual} cuotas semanales`;
            mensaje += `\n• Valor por cuota: S/ ${(totalConvenio/numeroCuotasOriginal).toFixed(2)} → S/ ${nuevoValor.toFixed(2)}`;
        }
        
        if (fechaInicioActual !== fechaInicioOriginal) {
            mensaje += `\n• Se recalcularán todas las fechas de vencimiento`;
        }
        
        return confirm(mensaje);
    });
});
</script>

@endsection