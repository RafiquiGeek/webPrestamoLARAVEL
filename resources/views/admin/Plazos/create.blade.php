@extends('layouts.admin')

@section('title', 'Crear Plazo')

@section('content_header')
    <div class="container d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-calendar-plus mr-2"></i>Crear Plazo</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.plazos.index') }}">Plazos</a></li>
            <li class="breadcrumb-item active">Crear</li>
        </ol>
    </div>
@stop

@section('content')
    <div class="container pt-2">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Información del Plazo</h3>
            </div>
            
            <form action="{{ route('admin.plazos.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <!-- Tiempo -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tiempo" class="font-weight-bold">
                                    <i class="fas fa-clock mr-1 text-gray-600"></i>Tiempo
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-light">
                                            <i class="fas fa-hashtag text-blue"></i>
                                        </span>
                                    </div>
                                    <input type="number" id="tiempo" name="tiempo" class="form-control @error('tiempo') is-invalid @enderror" 
                                        value="{{ old('tiempo') }}" required min="1" placeholder="Ej. 12">
                                    @error('tiempo')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">Ingrese la cantidad numérica del plazo</small>
                            </div>
                        </div>
                        
                        <!-- Unidad de Tiempo -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="unidad_tiempo" class="font-weight-bold">
                                    <i class="fas fa-hourglass-half mr-1 text-gray-600"></i>Unidad de Tiempo
                                    <span class="text-danger">*</span>
                                </label>
                                <select id="unidad_tiempo" name="unidad_tiempo" class="form-control @error('unidad_tiempo') is-invalid @enderror" required>
                                    <option value="" disabled {{ old('unidad_tiempo') ? '' : 'selected' }}>Seleccione una unidad</option>
                                    <option value="días" {{ old('unidad_tiempo') == 'días' ? 'selected' : '' }}>Días</option>
                                    <option value="semanas" {{ old('unidad_tiempo') == 'semanas' ? 'selected' : '' }}>Semanas</option>
                                    <option value="meses" {{ old('unidad_tiempo') == 'meses' ? 'selected' : '' }}>Meses</option>
                                </select>
                                @error('unidad_tiempo')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Seleccione la unidad de medida del plazo</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Vista previa del plazo -->
                    <div class="form-group">
                        <div class="alert alert-light border">
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="fas fa-eye text-primary fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="font-weight-bold mb-0">Vista previa:</h6>
                                    <p class="mb-0" id="vista-previa">--</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tasas Asociadas -->
                    <div class="form-group">
                        <label for="tasa_ids" class="font-weight-bold">
                            <i class="fas fa-percentage mr-1 text-gray-600"></i>Tasas Asociadas
                            <span class="text-danger">*</span>
                        </label>
                        <select id="tasa_ids" name="tasa_ids[]" class="choices-select @error('tasa_ids') is-invalid @enderror" multiple required>
                            @foreach($tasas as $tasa)
                                <option value="{{ $tasa->id }}" {{ in_array($tasa->id, old('tasa_ids', [])) ? 'selected' : '' }}>
                                    {{ $tasa->tipo_tasa }} - {{ $tasa->valor }}%
                                </option>
                            @endforeach
                        </select>
                        @error('tasa_ids')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">Seleccione una o más tasas que se aplicarán a este plazo</small>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.plazos.index') }}" class="btn btn-default">
                            <i class="fas fa-times mr-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Guardar Plazo
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <style>
        .choices__list--multiple .choices__item {
            background-color: #007bff;
            border-color: #006fe6;
        }
        .choices__list--multiple .choices__item.is-highlighted {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        .form-text.text-muted {
            font-size: 0.8rem;
        }
        .choices__inner {
            min-height: 40px;
        }
    </style>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar Choices.js
            new Choices(document.querySelector('.choices-select'), {
                removeItemButton: true,
                searchEnabled: true,
                placeholder: true,
                placeholderValue: 'Seleccione las tasas',
                noResultsText: 'No se encontraron resultados',
                itemSelectText: 'Presione para seleccionar',
            });
            
            // Vista previa del plazo
            const tiempoInput = document.getElementById('tiempo');
            const unidadSelect = document.getElementById('unidad_tiempo');
            const vistaPrevia = document.getElementById('vista-previa');
            
            function actualizarVistaPrevia() {
                const tiempo = tiempoInput.value || '--';
                const unidad = unidadSelect.value || '--';
                
                if (tiempo !== '--' && unidad !== '--') {
                    // Manejar singular/plural
                    let unidadTexto = unidad;
                    if (tiempo == 1) {
                        if (unidad === 'días') unidadTexto = 'día';
                        if (unidad === 'semanas') unidadTexto = 'semana';
                        if (unidad === 'meses') unidadTexto = 'mes';
                    }
                    
                    vistaPrevia.innerHTML = `<strong>${tiempo} ${unidadTexto}</strong>`;
                } else {
                    vistaPrevia.textContent = 'Complete los campos de tiempo y unidad';
                }
            }
            
            tiempoInput.addEventListener('input', actualizarVistaPrevia);
            unidadSelect.addEventListener('change', actualizarVistaPrevia);
            
            // Inicializar vista previa
            actualizarVistaPrevia();
        });
    </script>
@endsection