@extends('layouts.admin')
@section('title', 'Editar Zona')
@section('content_header')
  <div class="container d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-edit mr-2"></i>Editar Zona</h1>
      <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="{{ route('admin.zonas.index') }}">Zonas</a></li>
          <li class="breadcrumb-item active">Editar</li>
      </ol>
  </div>
@stop

@section('content')
  <div class="container pt-2">
      <div class="card card-outline card-primary shadow-sm">
          <div class="card-header">
              <h3 class="card-title">Modificar Información de la Zona</h3>
          </div>
          
          <form action="{{ route('admin.zonas.update', $zona) }}" method="POST">
              @csrf
              @method('PUT')
              <div class="card-body">
                  <div class="row">
                      <!-- Nombre de la Zona -->
                      <div class="col-md-12">
                          <div class="form-group">
                              <label for="nombre" class="font-weight-bold">
                                  <i class="fas fa-map-signs mr-1 text-gray-600"></i>Nombre de la Zona
                                  <span class="text-danger">*</span>
                              </label>
                              <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text bg-light">
                                          <i class="fas fa-pencil-alt text-blue"></i>
                                      </span>
                                  </div>
                                  <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                                         name="nombre" id="nombre" value="{{ old('nombre', $zona->nombre) }}" 
                                         placeholder="Ej. Zona Norte" required>
                                  @error('nombre')
                                      <span class="invalid-feedback">{{ $message }}</span>
                                  @enderror
                              </div>
                              <small class="form-text text-muted">Actualice el nombre de la zona</small>
                          </div>
                      </div>
                      
                      <!-- Sucursales -->
                      <div class="col-md-12">
                          <div class="form-group">
                              <label for="sucursales" class="font-weight-bold">
                                  <i class="fas fa-building mr-1 text-gray-600"></i>Sucursales Asociadas
                                  <span class="text-danger">*</span>
                              </label>
                              <select name="sucursales[]" id="sucursales" 
                                      class="form-control select2 @error('sucursales') is-invalid @enderror" 
                                      multiple required>
                                  @foreach ($sucursales as $sucursal)
                                      <option value="{{ $sucursal->id }}"
                                          {{ (in_array($sucursal->id, old('sucursales', [])) || $zona->sucursales->contains($sucursal->id)) ? 'selected' : '' }}>
                                          {{ $sucursal->sucursal }}
                                      </option>
                                  @endforeach
                              </select>
                              @error('sucursales')
                                  <span class="invalid-feedback">{{ $message }}</span>
                              @enderror
                              <small class="form-text text-muted">Seleccione todas las sucursales que pertenecen a esta zona</small>
                          </div>
                      </div>
                  </div>
                  
                  <!-- Información adicional -->
                  <div class="alert alert-warning mt-3">
                      <div class="d-flex">
                          <div class="mr-3">
                              <i class="fas fa-exclamation-triangle fa-lg"></i>
                          </div>
                          <div>
                              <h5 class="alert-heading font-weight-bold mb-1">Precaución</h5>
                              <p class="mb-0">Modificar las sucursales asociadas a una zona podría afectar a la organización territorial y a los reportes del sistema.</p>
                          </div>
                      </div>
                  </div>
              </div>
              
              <!-- Botones de acción -->
              <div class="card-footer bg-white">
                  <div class="d-flex justify-content-between">
                      <a href="{{ route('admin.zonas.index') }}" class="btn btn-default">
                          <i class="fas fa-times mr-1"></i>Cancelar
                      </a>
                      <button type="submit" class="btn btn-primary">
                          <i class="fas fa-save mr-1"></i>Guardar Cambios
                      </button>
                  </div>
              </div>
          </form>
      </div>
  </div>
@stop

@section('css')
  <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
  <style>
      .form-group label {
          color: #555;
      }
      .form-text.text-muted {
          font-size: 0.8rem;
      }
      .input-group-text {
          border-color: #ced4da;
      }
      .alert-warning {
          background-color: #fff8e1;
          border-color: #ffe082;
          color: #ff8f00;
      }
      .select2-container--default .select2-selection--multiple {
          border-color: #ced4da;
      }
      .select2-container--default.select2-container--focus .select2-selection--multiple {
          border-color: #80bdff;
          box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
      }
  </style>
@stop

@section('js')
  <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
  <script>
      $(document).ready(function() {
          $('#sucursales').select2({
              placeholder: "Seleccionar sucursales",
              allowClear: true,
              width: '100%'
          });
      });
  </script>
@stop