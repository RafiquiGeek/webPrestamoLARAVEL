@extends('layouts.admin')

@section('title', 'Configuración de Marca')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">Configuración de Marca</h1>
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Personalizar Logo y Nombre del Sitio</h3>
                </div>
                <form action="{{ route('admin.brand.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="form-group mb-3">
                            <label for="site_name" class="form-label">Nombre del Sitio</label>
                            <input type="text" 
                                   class="form-control @error('site_name') is-invalid @enderror" 
                                   id="site_name" 
                                   name="site_name" 
                                   value="{{ old('site_name', $brandConfig['site_name'] ?? 'Banking') }}"
                                   placeholder="Ingrese el nombre del sitio">
                            @error('site_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="logo" class="form-label">Logo del Sitio</label>
                            <input type="file" 
                                   class="form-control @error('logo') is-invalid @enderror" 
                                   id="logo" 
                                   name="logo"
                                   accept=".svg,.png,.jpg,.jpeg">
                            <div class="form-text">Formatos permitidos: SVG, PNG, JPG. Tamaño máximo: 2MB</div>
                            @error('logo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if(isset($brandConfig['logo_path']) && $brandConfig['logo_path'])
                            <div class="form-group mb-3">
                                <label class="form-label">Logo Actual</label>
                                <div class="current-logo">
                                    <img src="{{ asset('storage/' . $brandConfig['logo_path']) }}" 
                                         alt="Logo actual" 
                                         style="max-height: 60px; max-width: 200px; object-fit: contain;">
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Configuración
                        </button>
                        <a href="{{ route('admin.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Vista Previa</h3>
                </div>
                <div class="card-body">
                    <div class="preview-brand" style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
                        <div class="brand-preview d-flex align-items-center">
                            @if(isset($brandConfig['logo_path']) && $brandConfig['logo_path'])
                                <img src="{{ asset('storage/' . $brandConfig['logo_path']) }}" 
                                     alt="Logo" 
                                     style="height: 40px; width: 40px; object-fit: contain; margin-right: 10px;">
                            @else
                                <div style="height: 40px; width: 40px; background: #007bff; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin-right: 10px; font-weight: bold;">
                                    {{ substr($brandConfig['site_name'] ?? 'B', 0, 1) }}
                                </div>
                            @endif
                            <span style="font-weight: 500; color: #333;">{{ $brandConfig['site_name'] ?? 'Banking' }}</span>
                        </div>
                    </div>
                    <small class="text-muted">Así se verá en la barra de navegación</small>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .preview-brand {
        min-height: 60px;
        display: flex;
        align-items: center;
    }
    .current-logo img {
        border: 1px solid #ddd;
        padding: 10px;
        border-radius: 5px;
        background: #f8f9fa;
    }
</style>
@stop