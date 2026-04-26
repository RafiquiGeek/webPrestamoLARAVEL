@extends('layouts.admin')

@section('title', 'Crear Etiqueta')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-12 text-center">
                <h1 class="font-weight-bold text-success">
                    <i class="fas fa-plus-circle mr-2"></i>Crear Nueva Etiqueta
                </h1>
                <p class="text-muted">Crear una nueva etiqueta para clasificar clientes</p>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <form action="{{ route('admin.etiquetas.store') }}" method="POST" class="needs-validation" novalidate>
            @csrf

            <div class="row">
                <!-- COLUMNA IZQUIERDA: Formulario Principal -->
                <div class="col-lg-8">
                    <!-- Información de la Etiqueta -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-gradient-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-tag mr-2"></i>Datos de la Etiqueta
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Nombre de la Etiqueta -->
                                <div class="col-md-6 mb-3">
                                    <label for="etiqueta" class="form-label font-weight-bold">
                                        <i class="fas fa-tag mr-1 text-success"></i>
                                        Nombre de la Etiqueta <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           name="etiqueta" 
                                           id="etiqueta" 
                                           class="form-control @error('etiqueta') is-invalid @enderror" 
                                           value="{{ old('etiqueta') }}" 
                                           placeholder="Ej: Buen Pagador, Cliente VIP, etc."
                                           required>
                                    @error('etiqueta')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Color de la Etiqueta -->
                                <div class="col-md-6 mb-3">
                                    <label for="color" class="form-label font-weight-bold">
                                        <i class="fas fa-palette mr-1 text-warning"></i>
                                        Color <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="color" 
                                               name="color" 
                                               id="color" 
                                               class="form-control @error('color') is-invalid @enderror" 
                                               value="{{ old('color', '#007bff') }}" 
                                               required>
                                        <div class="input-group-append">
                                            <span class="input-group-text" id="colorDisplay" style="background-color: {{ old('color', '#007bff') }}; color: white; min-width: 100px;">
                                                Vista Previa
                                            </span>
                                        </div>
                                    </div>
                                    @error('color')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <!-- Estado -->
                                <div class="col-md-6 mb-3">
                                    <label for="estado" class="form-label font-weight-bold">
                                        <i class="fas fa-toggle-on mr-1 text-info"></i>
                                        Estado <span class="text-danger">*</span>
                                    </label>
                                    <select name="estado" id="estado" class="form-control @error('estado') is-invalid @enderror" required>
                                        <option value="">Seleccionar estado</option>
                                        <option value="1" {{ old('estado') == '1' ? 'selected' : '' }}>Activa</option>
                                        <option value="0" {{ old('estado') == '0' ? 'selected' : '' }}>Inactiva</option>
                                    </select>
                                    @error('estado')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Vista Previa de la Etiqueta -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-bold">
                                        <i class="fas fa-eye mr-1 text-secondary"></i>
                                        Vista Previa de la Etiqueta
                                    </label>
                                    <div class="p-3 bg-light rounded border">
                                        <span id="etiquetaPreview" class="badge px-3 py-2" style="background-color: #007bff; color: white; font-size: 14px;">
                                            Etiqueta de Ejemplo
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: Información y Acciones -->
                <div class="col-lg-4">
                    <!-- Información del Sistema -->
                    <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                        <div class="card-header bg-gradient-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle mr-2"></i>Información
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info border-left-info mb-3">
                                <h6 class="font-weight-bold mb-2">
                                    <i class="fas fa-lightbulb mr-1"></i>¿Qué son las Etiquetas?
                                </h6>
                                <small>
                                    Las etiquetas permiten clasificar y organizar a los clientes según sus 
                                    características de pago, comportamiento crediticio o cualquier otro criterio 
                                    relevante para la gestión.
                                </small>
                            </div>

                            <div class="info-block mb-3">
                                <h6 class="font-weight-bold text-muted mb-2">Usos Comunes:</h6>
                                <ul class="list-unstyled small">
                                    <li><i class="fas fa-check text-success mr-1"></i> Clasificar por comportamiento de pago</li>
                                    <li><i class="fas fa-check text-success mr-1"></i> Identificar clientes VIP</li>
                                    <li><i class="fas fa-check text-success mr-1"></i> Marcar clientes en riesgo</li>
                                    <li><i class="fas fa-check text-success mr-1"></i> Categorizar por tipo de producto</li>
                                </ul>
                            </div>

                            <div class="info-block mb-3">
                                <h6 class="font-weight-bold text-muted mb-2">Colores Sugeridos:</h6>
                                <div class="row">
                                    <div class="col-6 mb-2">
                                        <button type="button" class="btn btn-sm btn-block color-preset" style="background-color: #28a745; color: white;" data-color="#28a745">
                                            Positivo
                                        </button>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <button type="button" class="btn btn-sm btn-block color-preset" style="background-color: #dc3545; color: white;" data-color="#dc3545">
                                            Negativo
                                        </button>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <button type="button" class="btn btn-sm btn-block color-preset" style="background-color: #ffc107; color: black;" data-color="#ffc107">
                                            Advertencia
                                        </button>
                                    </div>
                                    <div class="col-6 mb-2">
                                        <button type="button" class="btn btn-sm btn-block color-preset" style="background-color: #17a2b8; color: white;" data-color="#17a2b8">
                                            Información
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de Acción -->
                            <div class="mt-4">
                                <button type="submit" class="btn btn-success btn-lg btn-block">
                                    <i class="fas fa-save mr-2"></i>Crear Etiqueta
                                </button>
                                
                                <a href="{{ route('admin.etiquetas.index') }}" 
                                   class="btn btn-outline-secondary btn-block mt-2">
                                    <i class="fas fa-arrow-left mr-2"></i>Volver a la Lista
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@stop

@section('css')
<style>
    .bg-gradient-success {
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
    }
    
    .bg-gradient-info {
        background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
    }
    
    .border-left-info {
        border-left: 4px solid #17a2b8 !important;
    }
    
    .card {
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .form-control {
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40,167,69,0.25);
    }
    
    .btn {
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .sticky-top {
        position: sticky;
        z-index: 1020;
    }
    
    .color-preset {
        border: 2px solid transparent;
        transition: all 0.3s ease;
    }
    
    .color-preset:hover {
        border-color: #333;
        transform: scale(1.05);
    }
    
    #etiquetaPreview {
        transition: all 0.3s ease;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    @media (max-width: 992px) {
        .sticky-top {
            position: relative;
            top: auto !important;
        }
    }
</style>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const colorInput = document.getElementById('color');
    const colorDisplay = document.getElementById('colorDisplay');
    const etiquetaInput = document.getElementById('etiqueta');
    const etiquetaPreview = document.getElementById('etiquetaPreview');
    
    // Actualizar vista previa del color
    function updateColorPreview() {
        const color = colorInput.value;
        colorDisplay.style.backgroundColor = color;
        etiquetaPreview.style.backgroundColor = color;
        
        // Calcular color de texto basado en luminosidad
        const rgb = hexToRgb(color);
        const luminance = (0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b) / 255;
        const textColor = luminance > 0.5 ? '#000000' : '#ffffff';
        
        colorDisplay.style.color = textColor;
        etiquetaPreview.style.color = textColor;
    }
    
    // Actualizar vista previa del texto
    function updateTextPreview() {
        const text = etiquetaInput.value || 'Etiqueta de Ejemplo';
        etiquetaPreview.textContent = text;
    }
    
    // Convertir hex a RGB
    function hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    }
    
    // Event listeners
    colorInput.addEventListener('input', updateColorPreview);
    etiquetaInput.addEventListener('input', updateTextPreview);
    
    // Colores predefinidos
    document.querySelectorAll('.color-preset').forEach(button => {
        button.addEventListener('click', function() {
            colorInput.value = this.dataset.color;
            updateColorPreview();
        });
    });
    
    // Validación del formulario
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    });
    
    // Inicializar vista previa
    updateColorPreview();
    updateTextPreview();
});
</script>
@stop