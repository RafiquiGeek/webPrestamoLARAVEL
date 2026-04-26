@extends('layouts.admin')

@section('title', 'Crear Gestión')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-12 text-center">
                <h1 class="font-weight-bold text-primary">Nueva Gestión de Cobranza</h1>
                <p class="text-muted">Complete la información de seguimiento y compromisos de pago</p>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <form action="{{ route('admin.gestiones.store') }}" method="POST" class="needs-validation" novalidate id="gestion-form">
            @csrf

            <!-- Campos ocultos -->
            <input type="hidden" name="prestamo_id" value="{{ $prestamo->id }}">
            <input type="hidden" name="user_id" value="{{ Auth::id() }}">
            <input type="hidden" name="latitud" id="latitud">
            <input type="hidden" name="longitud" id="longitud">

            <div class="row">
                <!-- COLUMNA IZQUIERDA: Información Principal -->
                <div class="col-lg-8">
                    <!-- Información del Cliente -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-gradient-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user-check mr-2"></i>Información del Cliente
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="client-info-display">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center">
                                            <div class="client-avatar mr-3">
                                                <i class="fas fa-user-circle fa-3x text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 text-muted">Cliente Asignado</h6>
                                                <h4 class="mb-1 text-primary font-weight-bold">
                                                    {{ $prestamo->cliente->persona->nombres }} 
                                                    {{ $prestamo->cliente->persona->ape_pat }} 
                                                    {{ $prestamo->cliente->persona->ape_mat }}
                                                </h4>
                                                <small class="text-muted">
                                                    <i class="fas fa-hashtag mr-1"></i>Préstamo ID: {{ $prestamo->id }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-right">
                                        <span class="badge badge-success badge-lg">
                                            <i class="fas fa-check mr-1"></i>Verificado
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información del Compromiso (si existe) -->
                    @if(isset($compromiso))
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-header bg-gradient-warning text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-handshake mr-2"></i>Seguimiento de Compromiso
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info border-left-info mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-info-circle text-info mr-3"></i>
                                        <div>
                                            <h6 class="mb-1 font-weight-bold">Gestión de Seguimiento</h6>
                                            <small>Esta gestión dará seguimiento al compromiso de pago del cliente.</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>ID Compromiso:</strong><br>
                                        <span class="badge badge-primary badge-lg">#{{ $compromiso->id }}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Fecha Compromiso:</strong><br>
                                        {{ \Carbon\Carbon::parse($compromiso->fecha_compromiso_pago)->format('d/m/Y') }}
                                        <small class="d-block text-muted">{{ \Carbon\Carbon::parse($compromiso->hora)->format('H:i') }}</small>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Monto Comprometido:</strong><br>
                                        <span class="text-success font-weight-bold">S/ {{ number_format($compromiso->monto, 2) }}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Estado Actual:</strong><br>
                                        @php
                                            $badgeClass = match($compromiso->estado) {
                                                0 => 'badge-warning',
                                                1 => 'badge-success', 
                                                2 => 'badge-danger',
                                                default => 'badge-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }} badge-lg">{{ $compromiso->estado_texto }}</span>
                                    </div>
                                </div>
                                
                                @if($compromiso->comentario)
                                    <div class="mt-3">
                                        <strong>Comentario del compromiso:</strong>
                                        <p class="text-muted mb-0">{{ $compromiso->comentario }}</p>
                                    </div>
                                @endif
                                
                                <!-- Campo oculto para vincular con el compromiso -->
                                <input type="hidden" name="compromiso_seguimiento_id" value="{{ $compromiso->id }}">
                            </div>
                        </div>
                    @endif

                    <!-- Detalles de la Gestión -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-gradient-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-clipboard-list mr-2"></i>Detalles de la Gestión
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Estado de Gestión - Horizontal Elegante -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-bold mb-3">
                                        Estado de Gestión <span class="text-danger">*</span>
                                    </label>
                                    <div class="estado-elegant-container">
                                        @foreach($estados as $index => $estado)
                                            <div class="estado-elegant-item">
                                                <input type="radio" name="estado_id" id="estado_id_{{ $estado->id }}" 
                                                    value="{{ $estado->id }}" 
                                                    {{ old('estado_id') == $estado->id ? 'checked' : '' }} 
                                                    required class="d-none">
                                                <label for="estado_id_{{ $estado->id }}" class="estado-elegant-label">
                                                    <span class="estado-elegant-text">{{ $estado->estado }}</span>
                                                    <span class="estado-elegant-check">
                                                        <i class="fas fa-check"></i>
                                                    </span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('estado_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Fecha de Gestión -->
                                <div class="col-md-6 mb-3">
                                    <label for="fecha" class="form-label font-weight-bold">
                                        Fecha de Gestión <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white border-right-0">
                                                <i class="far fa-calendar-alt text-primary"></i>
                                            </span>
                                        </div>
                                        <input type="date" name="fecha" id="fecha" 
                                            class="form-control border-left-0 @error('fecha') is-invalid @enderror" 
                                            value="{{ old('fecha', date('Y-m-d')) }}" required readonly>
                                    </div>
                                    @error('fecha')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div class="form-group mb-3">
                                <label for="observaciones" class="form-label font-weight-bold">
                                    Observaciones <span class="text-danger">*</span>
                                </label>
                                <textarea name="observaciones" id="observaciones" 
                                    class="form-control @error('observaciones') is-invalid @enderror" 
                                    rows="3" 
                                    placeholder="Describa los detalles de la visita, comunicación con el cliente, situación encontrada..."
                                    required>{{ old('observaciones') }}</textarea>
                                <div class="form-text">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Sea específico y detallado en sus observaciones
                                    </small>
                                </div>
                                @error('observaciones')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Compromiso de Pago - Elegante y Sobrio -->
                    <div class="card shadow-sm border-0 mb-4 compromiso-elegante">
                        <div class="card-header bg-white border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 text-dark font-weight-bold">
                                    <i class="fas fa-handshake mr-2 text-muted"></i>Compromiso de Pago
                                </h5>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" name="compromisoPago" 
                                        id="compromisoPago" value="1" {{ old('compromisoPago') ? 'checked' : '' }}>
                                    <label class="custom-control-label text-dark font-weight-normal" for="compromisoPago">
                                        Establecer compromiso
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="card-body bg-light">
                            <div class="alert alert-light border-left-info mb-4">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle text-info mr-3"></i>
                                    <div>
                                        <h6 class="mb-1 font-weight-bold text-dark">¿El cliente estableció un compromiso de pago?</h6>
                                        <small class="text-muted">Active esta opción para registrar los detalles del acuerdo de pago.</small>
                                    </div>
                                </div>
                            </div>

                            <div id="compromisoFields" style="display: {{ old('compromisoPago') ? 'block' : 'none' }};">
                                <!-- Estado del Compromiso -->
                                <div class="form-group mb-4">
                                    <label class="form-label font-weight-bold mb-3">Estado del Compromiso</label>
                                    <div class="btn-group-toggle d-flex flex-wrap" data-toggle="buttons">
                                        <label class="btn btn-outline-secondary mr-2 mb-2 flex-fill {{ old('estado', \App\Models\Compromiso::ESTADO_PENDIENTE) == \App\Models\Compromiso::ESTADO_PENDIENTE ? 'active' : '' }}">
                                            <input type="radio" name="estado" value="{{ \App\Models\Compromiso::ESTADO_PENDIENTE }}" 
                                                {{ old('estado', \App\Models\Compromiso::ESTADO_PENDIENTE) == \App\Models\Compromiso::ESTADO_PENDIENTE ? 'checked' : '' }} required>
                                            <i class="fas fa-clock mr-2"></i> Pendiente
                                        </label>
                                        <label class="btn btn-outline-secondary mr-2 mb-2 flex-fill {{ old('estado') == \App\Models\Compromiso::ESTADO_PAGADO ? 'active' : '' }}">
                                            <input type="radio" name="estado" value="{{ \App\Models\Compromiso::ESTADO_PAGADO }}" 
                                                {{ old('estado') == \App\Models\Compromiso::ESTADO_PAGADO ? 'checked' : '' }}>
                                            <i class="fas fa-check mr-2"></i> Pagado
                                        </label>
                                        <label class="btn btn-outline-secondary mb-2 flex-fill {{ old('estado') == \App\Models\Compromiso::ESTADO_POSTERGADO ? 'active' : '' }}">
                                            <input type="radio" name="estado" value="{{ \App\Models\Compromiso::ESTADO_POSTERGADO }}" 
                                                {{ old('estado') == \App\Models\Compromiso::ESTADO_POSTERGADO ? 'checked' : '' }}>
                                            <i class="fas fa-times mr-2"></i> Postergado
                                        </label>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Fecha y Hora del Compromiso -->
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_compromiso" class="form-label font-weight-bold">
                                            <i class="far fa-calendar-alt mr-1 text-muted"></i>Fecha del Compromiso
                                        </label>
                                        <input type="date" name="fecha_compromiso" id="fecha_compromiso" 
                                            class="form-control @error('fecha_compromiso') is-invalid @enderror" 
                                            value="{{ old('fecha_compromiso', date('Y-m-d')) }}"
                                            data-required-when="compromisoPago">
                                        @error('fecha_compromiso')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="hora_compromiso" class="form-label font-weight-bold">
                                            <i class="far fa-clock mr-1 text-muted"></i>Hora del Compromiso
                                        </label>
                                        <input type="time" name="hora_compromiso" id="hora_compromiso" 
                                            class="form-control @error('hora_compromiso') is-invalid @enderror"
                                            value="{{ old('hora_compromiso', '09:00') }}"
                                            data-required-when="compromisoPago">
                                        @error('hora_compromiso')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Monto del Compromiso -->
                                    <div class="col-md-4 mb-3">
                                        <label for="monto" class="form-label font-weight-bold">
                                            <i class="fas fa-dollar-sign mr-1 text-muted"></i>Monto Comprometido
                                        </label>
                                        <input type="number" name="monto" id="monto" 
                                            class="form-control @error('monto') is-invalid @enderror" 
                                            placeholder="0.00" step="0.01" min="0"
                                            value="{{ old('monto') }}"
                                            data-required-when="compromisoPago">
                                        @error('monto')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Observaciones del Compromiso -->
                                <div class="form-group mb-3">
                                    <label for="observaciones_compromiso" class="form-label font-weight-bold">
                                        <i class="fas fa-comment-alt mr-1 text-muted"></i>Detalles del Compromiso
                                    </label>
                                    <textarea name="observaciones_compromiso" id="observaciones_compromiso" 
                                        class="form-control @error('observaciones_compromiso') is-invalid @enderror" 
                                        rows="3" 
                                        placeholder="Detalles adicionales sobre el compromiso establecido, condiciones especiales, etc...">{{ old('observaciones_compromiso') }}</textarea>
                                    @error('observaciones_compromiso')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: Acciones -->
                <div class="col-lg-4">
                    <!-- Resumen y Acciones -->
                    <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                        <div class="card-header bg-gradient-dark text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-check-circle mr-2"></i>Finalizar Gestión
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="completion-checklist mb-3">
                                <div class="checklist-item" data-field="ubicacion">
                                    <i class="fas fa-circle text-muted mr-2"></i>
                                    <span>Cliente detectado</span>
                                </div>
                                <div class="checklist-item" data-field="estado_id">
                                    <i class="fas fa-circle text-muted mr-2"></i>
                                    <span>Estado de gestión</span>
                                </div>
                                <div class="checklist-item" data-field="observaciones">
                                    <i class="fas fa-circle text-muted mr-2"></i>
                                    <span>Observaciones</span>
                                </div>
                            </div>

                            <div class="alert alert-light border-left-primary">
                                <small>
                                    <i class="fas fa-lightbulb text-warning mr-1"></i>
                                    <strong>Tip:</strong> Asegúrese de completar todos los campos requeridos antes de guardar.
                                </small>
                            </div>

                            <!-- Ubicación oculta pero con indicador -->
                            <div class="location-hidden-indicator mb-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        Ubicación GPS
                                    </small>
                                    <div id="location-status-mini">
                                        <i class="fas fa-spinner fa-spin text-primary"></i>
                                    </div>
                                </div>
                                <div class="progress mt-1" style="height: 3px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 0%" id="location-progress"></div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg btn-block" id="submit-btn">
                                <i class="fas fa-save mr-2"></i>Guardar Gestión
                            </button>
                            
                            <a href="{{ route('admin.gestiones.index') }}" class="btn btn-outline-secondary btn-block mt-2">
                                <i class="fas fa-arrow-left mr-2"></i>Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@stop

@section('css')
<style>
/* Layout principal */
.container-fluid {
    max-width: 1400px;
}

/* Estilos para las tarjetas */
.card {
    border: none;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

/* Gradientes para headers */
.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
}

.bg-gradient-dark {
    background: linear-gradient(135deg, #343a40 0%, #23272b 100%);
}

/* Información del cliente */
.client-info-display {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid #007bff;
}

.client-avatar i {
    color: #007bff;
}

.badge-lg {
    padding: 8px 16px;
    font-size: 14px;
}

/* Estados elegantes horizontales */
.estado-elegant-container {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.estado-elegant-item {
    position: relative;
}

.estado-elegant-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 0;
    font-weight: 500;
    color: #495057;
}

.estado-elegant-label:hover {
    border-color: #007bff;
    background: #f8f9fa;
    color: #007bff;
    box-shadow: 0 2px 8px rgba(0,123,255,0.1);
}

.estado-elegant-item input[type="radio"]:checked + .estado-elegant-label {
    border-color: #007bff;
    background: #007bff;
    color: white;
    box-shadow: 0 2px 8px rgba(0,123,255,0.2);
}

.estado-elegant-text {
    font-size: 14px;
}

.estado-elegant-check {
    opacity: 0;
    transition: opacity 0.3s ease;
    font-size: 12px;
}

.estado-elegant-item input[type="radio"]:checked + .estado-elegant-label .estado-elegant-check {
    opacity: 1;
}

/* Compromiso elegante */
.compromiso-elegante {
    border: 2px solid #e9ecef !important;
    transition: all 0.3s ease;
}

.compromiso-elegante:hover {
    border-color: #dee2e6 !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
}

.compromiso-elegante .card-header {
    background: white !important;
    border-bottom: 1px solid #e9ecef;
}

.compromiso-elegante .card-body {
    background: #fafafa;
}

/* Alertas elegantes */
.border-left-info {
    border-left: 4px solid #17a2b8 !important;
}

/* Ubicación oculta */
.location-hidden-indicator {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

/* Checklist de completitud */
.completion-checklist {
    list-style: none;
    padding: 0;
}

.checklist-item {
    display: flex;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f1f1f1;
    transition: all 0.3s ease;
}

.checklist-item:last-child {
    border-bottom: none;
}

.checklist-item.completed {
    color: #28a745;
}

.checklist-item.completed i {
    color: #28a745;
}

/* Formularios */
.form-control {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.input-group-text {
    border-radius: 8px 0 0 8px;
    border-right: none;
}

.form-control.border-left-0 {
    border-left: none;
    border-radius: 0 8px 8px 0;
}

/* Botones */
.btn {
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-lg {
    padding: 12px 30px;
    font-size: 16px;
}

/* Botones de compromiso elegantes */
.btn-outline-secondary {
    border-color: #6c757d;
    color: #6c757d;
}

.btn-outline-secondary:hover,
.btn-outline-secondary.active {
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
}

/* Alertas */
.alert {
    border-radius: 10px;
    border: none;
}

.border-left-primary {
    border-left: 4px solid #007bff !important;
}

/* Sticky sidebar */
.sticky-top {
    position: sticky;
    z-index: 1020;
}

/* Responsive */
@media (max-width: 992px) {
    .estado-elegant-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    
    .sticky-top {
        position: relative;
        top: auto !important;
    }
}

@media (max-width: 768px) {
    .client-info-display .row {
        text-align: center;
    }
    
    .estado-elegant-container {
        grid-template-columns: 1fr;
    }
    
    .d-flex.flex-wrap .btn {
        margin-bottom: 10px;
        flex: 1 1 auto;
    }
}

/* Animaciones */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.card {
    animation: fadeIn 0.5s ease-out;
}

/* Switch personalizado */
.custom-control-input:checked ~ .custom-control-label::before {
    background-color: #007bff;
    border-color: #007bff;
}

/* Validación visual */
.form-control.is-valid {
    border-color: #28a745;
}

.form-control.is-invalid {
    border-color: #dc3545;
}

/* Tipografía elegante */
h5, h6 {
    letter-spacing: 0.5px;
}

.font-weight-bold {
    font-weight: 600 !important;
}

/* Espaciado refinado */
.card-body {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

/* Sombras sutiles */
.shadow-sm {
    box-shadow: 0 2px 4px rgba(0,0,0,0.08) !important;
}
</style>
@stop

@section('js')
<script>
// Variables globales
let locationDetected = false;

// Función para obtener ubicación (oculta)
function obtenerUbicacion() {
    const statusMini = document.getElementById('location-status-mini');
    const progressBar = document.getElementById('location-progress');
    
    if (navigator.geolocation) {
        statusMini.innerHTML = '<i class="fas fa-spinner fa-spin text-primary"></i>';
        progressBar.style.width = '30%';
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                document.getElementById('latitud').value = lat;
                document.getElementById('longitud').value = lng;
                
                statusMini.innerHTML = '<i class="fas fa-check-circle text-success"></i>';
                progressBar.style.width = '100%';
                progressBar.classList.remove('bg-primary');
                progressBar.classList.add('bg-success');
                
                locationDetected = true;
                updateChecklist();
            },
            function(error) {
                statusMini.innerHTML = '<i class="fas fa-exclamation-triangle text-warning"></i>';
                progressBar.style.width = '100%';
                progressBar.classList.remove('bg-primary');
                progressBar.classList.add('bg-warning');
                
                console.error('Error de geolocalización:', error);
                locationDetected = false;
                updateChecklist();
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000
            }
        );
    } else {
        statusMini.innerHTML = '<i class="fas fa-times-circle text-danger"></i>';
        progressBar.style.width = '100%';
        progressBar.classList.remove('bg-primary');
        progressBar.classList.add('bg-danger');
        locationDetected = false;
        updateChecklist();
    }
}

// Función para actualizar el checklist de completitud
function updateChecklist() {
    // Estado de gestión
    const estadoSelected = document.querySelector('input[name="estado_id"]:checked');
    updateChecklistItem('estado_id', !!estadoSelected);
    
    // Observaciones
    const observaciones = document.getElementById('observaciones').value.trim();
    updateChecklistItem('observaciones', observaciones.length >= 10);
    
    // Ubicación
    updateChecklistItem('ubicacion', locationDetected);
}

// Función para actualizar un item del checklist
function updateChecklistItem(field, completed) {
    const item = document.querySelector(`[data-field="${field}"]`);
    if (item) {
        if (completed) {
            item.classList.add('completed');
            item.querySelector('i').className = 'fas fa-check-circle text-success mr-2';
        } else {
            item.classList.remove('completed');
            item.querySelector('i').className = 'fas fa-circle text-muted mr-2';
        }
    }
}

// Función para validar el formulario
function validateForm() {
    const estadoId = document.querySelector('input[name="estado_id"]:checked');
    const observaciones = document.getElementById('observaciones').value.trim();
    
    if (!estadoId) {
        alert('Por favor, seleccione un estado de gestión.');
        return false;
    }
    
    if (!observaciones || observaciones.length < 10) {
        alert('Por favor, ingrese observaciones detalladas (mínimo 10 caracteres).');
        document.getElementById('observaciones').focus();
        return false;
    }
    
    if (!locationDetected) {
        alert('Por favor, espere a que se detecte la ubicación.');
        return false;
    }
    
    // Validar compromiso si está activado
    const compromisoActivado = document.getElementById('compromisoPago').checked;
    if (compromisoActivado) {
        const fechaCompromiso = document.getElementById('fecha_compromiso').value;
        const horaCompromiso = document.getElementById('hora_compromiso').value;
        const monto = document.getElementById('monto').value;
        const estadoCompromiso = document.querySelector('input[name="estado"]:checked');
        
        if (!fechaCompromiso) {
            alert('Por favor, ingrese la fecha del compromiso.');
            document.getElementById('fecha_compromiso').focus();
            return false;
        }
        
        if (!horaCompromiso) {
            alert('Por favor, ingrese la hora del compromiso.');
            document.getElementById('hora_compromiso').focus();
            return false;
        }
        
        if (!monto || parseFloat(monto) <= 0) {
            alert('Por favor, ingrese un monto válido para el compromiso.');
            document.getElementById('monto').focus();
            return false;
        }
        
        if (!estadoCompromiso) {
            alert('Por favor, seleccione el estado del compromiso.');
            return false;
        }
    }
    
    return true;
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Obtener ubicación al cargar la página
    obtenerUbicacion();
    
    // Toggle de compromiso de pago con animación elegante
    document.getElementById('compromisoPago').addEventListener('change', function() {
        const compromisoFields = document.getElementById('compromisoFields');
        const card = document.querySelector('.compromiso-elegante');
        const requiredFields = document.querySelectorAll('[data-required-when="compromisoPago"]');
        
        if (this.checked) {
            compromisoFields.style.display = 'block';
            compromisoFields.style.animation = 'fadeIn 0.5s ease-out';
            card.style.borderColor = '#007bff';
            
            // Hacer campos requeridos
            requiredFields.forEach(field => {
                field.setAttribute('required', 'required');
            });
        } else {
            compromisoFields.style.display = 'none';
            card.style.borderColor = '#e9ecef';
            
            // Quitar requeridos
            requiredFields.forEach(field => {
                field.removeAttribute('required');
                field.classList.remove('is-invalid');
            });
        }
    });
    
    // Validación en tiempo real para observaciones
    document.getElementById('observaciones').addEventListener('input', function() {
        const minLength = 10;
        if (this.value.length >= minLength) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
            if (this.value.length > 0) {
                this.classList.add('is-invalid');
            }
        }
        updateChecklist();
    });
    
    // Validación para selección de estado
    document.querySelectorAll('input[name="estado_id"]').forEach(radio => {
        radio.addEventListener('change', updateChecklist);
    });
    
    // Validación del formulario antes del envío
    document.getElementById('gestion-form').addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
        
        // Mostrar indicador de carga en el botón
        const submitBtn = document.getElementById('submit-btn');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
        submitBtn.disabled = true;
    });
    
    // Actualizar checklist inicial
    updateChecklist();
});

// Efectos elegantes para estados
document.addEventListener('click', function(e) {
    if (e.target.closest('.estado-elegant-label')) {
        const label = e.target.closest('.estado-elegant-label');
        const radio = label.querySelector('input[type="radio"]');
        
        radio.checked = true;
        updateChecklist();
    }
});

// Auto-resize para textareas
document.querySelectorAll('textarea').forEach(textarea => {
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });
});
</script>
@stop
