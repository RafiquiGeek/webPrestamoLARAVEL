@extends('layouts.admin')
@section('title', 'Editar Sucursal')
@section('content_header')
  <div class="container d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-edit mr-2"></i>Editar Sucursal</h1>
      <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="{{ route('admin.sucursales.index') }}">Sucursales</a></li>
          <li class="breadcrumb-item active">Editar</li>
      </ol>
  </div>
@stop

@section('content')
  <div class="container pt-2">
      <div class="card card-outline card-primary shadow-sm">
          <div class="card-header">
              <h3 class="card-title">Modificar Información de la Sucursal</h3>
          </div>
          
          <form action="{{ route('admin.sucursales.update', $sucursal->id) }}" method="POST">
              @csrf
              @method('PUT')
              <div class="card-body">
                  <div class="row">
                      <!-- Nombre de la Sucursal -->
                      <div class="col-md-12">
                          <div class="form-group">
                              <label for="sucursal" class="font-weight-bold">
                                  <i class="fas fa-building mr-1 text-gray-600"></i>Nombre de la Sucursal
                                  <span class="text-danger">*</span>
                              </label>
                              <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text bg-light">
                                          <i class="fas fa-pencil-alt text-blue"></i>
                                      </span>
                                  </div>
                                  <input type="text" class="form-control @error('sucursal') is-invalid @enderror" 
                                         name="sucursal" id="sucursal" value="{{ old('sucursal', $sucursal->sucursal) }}" 
                                         placeholder="Ingrese el nombre de la sucursal" required>
                                  @error('sucursal')
                                      <span class="invalid-feedback">{{ $message }}</span>
                                  @enderror
                              </div>
                              <small class="form-text text-muted">Actualice el nombre de la sucursal</small>
                          </div>
                      </div>
                      
                      <!-- Departamento -->
                      <div class="col-md-6">
                          <div class="form-group">
                              <label for="departamento" class="font-weight-bold">
                                  <i class="fas fa-map-marked-alt mr-1 text-gray-600"></i>Departamento
                                  <span class="text-danger">*</span>
                              </label>
                              <select name="departamento" id="departamento" class="form-control @error('departamento') is-invalid @enderror" required>
                                  <option value="">Seleccione un departamento</option>
                                  @foreach ($departamentos as $departamento)
                                      <option value="{{ $departamento->id }}" 
                                          {{ old('departamento', $sucursal->provincia->departamento->id) == $departamento->id ? 'selected' : '' }}>
                                          {{ $departamento->departamento }}
                                      </option>
                                  @endforeach
                              </select>
                              @error('departamento')
                                  <span class="invalid-feedback">{{ $message }}</span>
                              @enderror
                              <small class="form-text text-muted">Seleccione el departamento para cargar las provincias</small>
                          </div>
                      </div>
                      
                      <!-- Provincia -->
                      <div class="col-md-6">
                          <div class="form-group">
                              <label for="provincia" class="font-weight-bold">
                                  <i class="fas fa-map-marker-alt mr-1 text-gray-600"></i>Provincia
                                  <span class="text-danger">*</span>
                              </label>
                              <select name="provincia_id" id="provincia" class="form-control @error('provincia_id') is-invalid @enderror" required>
                                  <option value="">Seleccione una provincia</option>
                                  @foreach ($provincias as $provincia)
                                      <option value="{{ $provincia->id }}" 
                                          {{ old('provincia_id', $sucursal->provincia->id) == $provincia->id ? 'selected' : '' }}>
                                          {{ $provincia->provincia }}
                                      </option>
                                  @endforeach
                              </select>
                              @error('provincia_id')
                                  <span class="invalid-feedback">{{ $message }}</span>
                              @enderror
                              <small class="form-text text-muted">Seleccione la provincia donde se encuentra la sucursal</small>
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
                              <p class="mb-0">Modificar los datos de una sucursal podría afectar a registros asociados en el sistema. Asegúrese de revisar el impacto antes de guardar los cambios.</p>
                          </div>
                      </div>
                  </div>
              </div>
              
              <!-- Botones de acción -->
              <div class="card-footer bg-white">
                  <div class="d-flex justify-content-between">
                      <a href="{{ route('admin.sucursales.index') }}" class="btn btn-default">
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
  </style>
@stop

@section('js')
  <script>
      $(document).ready(function() {
          // Cargar provincias dinámicamente al cambiar el departamento
          $('#departamento').change(function() {
              var departamentoId = $(this).val();
              if (departamentoId) {
                  $.ajax({
                      url: `/api/departamento/${departamentoId}/provincias`,
                      type: 'GET',
                      success: function(data) {
                          $('#provincia').empty().append('<option value="">Seleccione una provincia</option>');
                          $.each(data, function(key, value) {
                              $('#provincia').append('<option value="' + value.id + '">' + value.nombre + '</option>');
                          });
                      },
                      error: function() {
                          console.error("Error al cargar las provincias");
                      }
                  });
              } else {
                  $('#provincia').empty().append('<option value="">Seleccione una provincia</option>');
              }
          });
      });
  </script>
@stop