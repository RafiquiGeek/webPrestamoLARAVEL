@extends('layouts.admin')

@section('title', 'Nueva Categoría de Gasto')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Nueva Categoría de Gasto</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.gastos.index') }}">Gastos</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.categorias-gastos.index') }}">Categorías</a></li>
                        <li class="breadcrumb-item active">Nueva</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <form action="{{ route('admin.categorias-gastos.store') }}" method="POST">
                        @csrf
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-tag mr-1"></i>
                                    Información de la Categoría
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="nombre">Nombre *</label>
                                            <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                                                   name="nombre" id="nombre" 
                                                   value="{{ old('nombre') }}" 
                                                   maxlength="100" required
                                                   placeholder="Ej: Compras, Servicios, Honorarios">
                                            @error('nombre')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="color">Color *</label>
                                            <div class="input-group">
                                                <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror" 
                                                       name="color" id="color" 
                                                       value="{{ old('color', '#007bff') }}" required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text" id="colorPreview" style="background-color: {{ old('color', '#007bff') }}; color: white; min-width: 50px;">
                                                        <i class="fas fa-palette"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            @error('color')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="descripcion">Descripción</label>
                                    <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                              name="descripcion" id="descripcion" rows="3"
                                              placeholder="Descripción opcional de la categoría...">{{ old('descripcion') }}</textarea>
                                    @error('descripcion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" name="estado" id="estado" 
                                               value="1" {{ old('estado', '1') ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="estado">
                                            <strong>Categoría activa</strong>
                                            <small class="text-muted d-block">Las categorías inactivas no aparecerán en los formularios</small>
                                        </label>
                                    </div>
                                </div>

                                <!-- Vista previa -->
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-eye mr-1"></i> Vista previa:</h6>
                                    <span class="badge" id="badgePreview" style="background-color: {{ old('color', '#007bff') }}; color: white; font-size: 14px;">
                                        <span id="nombrePreview">{{ old('nombre', 'Nombre de la categoría') }}</span>
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-md-6">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save mr-1"></i> Crear Categoría
                                        </button>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <a href="{{ route('admin.categorias-gastos.index') }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-times mr-1"></i> Cancelar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
// Actualizar vista previa en tiempo real
document.getElementById('nombre').addEventListener('input', function() {
    const nombre = this.value || 'Nombre de la categoría';
    document.getElementById('nombrePreview').textContent = nombre;
});

document.getElementById('color').addEventListener('input', function() {
    const color = this.value;
    document.getElementById('colorPreview').style.backgroundColor = color;
    document.getElementById('badgePreview').style.backgroundColor = color;
});

// Colores predefinidos sugeridos
const coloresSugeridos = [
    '#007bff', '#28a745', '#ffc107', '#dc3545', 
    '#6f42c1', '#fd7e14', '#20c997', '#6c757d'
];

// Agregar botones de colores sugeridos
const colorInput = document.getElementById('color');
const colorGroup = colorInput.closest('.form-group');

const coloresDiv = document.createElement('div');
coloresDiv.className = 'mt-2';
coloresDiv.innerHTML = '<small class="text-muted">Colores sugeridos:</small><br>';

coloresSugeridos.forEach(color => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-sm mr-1 mt-1';
    button.style.backgroundColor = color;
    button.style.width = '30px';
    button.style.height = '30px';
    button.style.border = '2px solid #dee2e6';
    button.onclick = () => {
        colorInput.value = color;
        colorInput.dispatchEvent(new Event('input'));
    };
    coloresDiv.appendChild(button);
});

colorGroup.appendChild(coloresDiv);
</script>
@endsection