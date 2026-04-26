@extends('layouts.admin')
@section('title', 'Crear Sucursal')
@section('content_header')
  <div class="container d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-plus-circle mr-2"></i>Crear Nueva Sucursal</h1>
      <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="{{ route('admin.sucursales.index') }}">Sucursales</a></li>
          <li class="breadcrumb-item active">Crear</li>
      </ol>
  </div>
@stop

@section('content')
  <div class="container pt-2">
      <div class="card card-outline card-primary shadow-sm">
          <div class="card-header">
              <h3 class="card-title">Información de la Sucursal</h3>
          </div>
          
          <form action="{{ route('admin.sucursales.store') }}" method="POST">
              @csrf
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
                                         name="sucursal" id="sucursal" value="{{ old('sucursal') }}" 
                                         placeholder="Ej. Sucursal Central" required>
                                  @error('sucursal')
                                      <span class="invalid-feedback">{{ $message }}</span>
                                  @enderror
                              </div>
                              <small class="form-text text-muted">Ingrese el nombre completo de la sucursal</small>
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
                                      <option value="{{ $departamento->id }}" {{ old('departamento') == $departamento->id ? 'selected' : '' }}>
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
                              </select>
                              @error('provincia_id')
                                  <span class="invalid-feedback">{{ $message }}</span>
                              @enderror
                              <small class="form-text text-muted">Seleccione la provincia donde se encuentra la sucursal</small>
                          </div>
                      </div>
                  </div>
                  
                  <!-- Información adicional -->
                  <div class="alert alert-info mt-3">
                      <div class="d-flex">
                          <div class="mr-3">
                              <i class="fas fa-info-circle fa-lg"></i>
                          </div>
                          <div>
                              <h5 class="alert-heading font-weight-bold mb-1">Información importante</h5>
                              <p class="mb-0">Las sucursales creadas estarán disponibles para ser seleccionadas en los diferentes procesos del sistema.</p>
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
                          <i class="fas fa-save mr-1"></i>Guardar Sucursal
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
      .alert-info {
          background-color: #f8f9ff;
          border-color: #cfd4ff;
          color: #3f51b5;
      }
  </style>
@stop

@section('js')
  <script>
      $(document).ready(function() {
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
          
          // Inicializar provincias si hay un departamento seleccionado
          if ($('#departamento').val()) {
              $('#departamento').trigger('change');
          }
      });
  </script>
@stop