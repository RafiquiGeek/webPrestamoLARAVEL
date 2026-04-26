@extends('layouts.admin')
@section('title', 'Crear Banco')
@section('content_header')
  <div class="d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-plus-circle mr-2"></i>Crear Nuevo Banco</h1>
      <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="{{ route('admin.bancos.index') }}">Bancos</a></li>
          <li class="breadcrumb-item active">Crear</li>
      </ol>
  </div>
@stop

@section('content')
  <div class="container-fluid pt-2">
      <div class="card card-outline card-primary shadow-sm">
          <div class="card-header">
              <h3 class="card-title">Información del Banco</h3>
          </div>
          
          <form action="{{ route('admin.bancos.store') }}" method="POST">
              @csrf
              <div class="card-body">
                  <div class="row">
                      <!-- Nombre del Banco -->
                      <div class="col-md-12">
                          <div class="form-group">
                              <label for="banco" class="font-weight-bold">
                                  <i class="fas fa-university mr-1 text-gray-600"></i>Nombre del Banco
                                  <span class="text-danger">*</span>
                              </label>
                              <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text bg-light">
                                          <i class="fas fa-pencil-alt text-blue"></i>
                                      </span>
                                  </div>
                                  <input type="text" class="form-control @error('banco') is-invalid @enderror" 
                                         name="banco" id="banco" value="{{ old('banco') }}" 
                                         placeholder="Ej. Banco XYZ" required>
                                  @error('banco')
                                      <span class="invalid-feedback">{{ $message }}</span>
                                  @enderror
                              </div>
                              <small class="form-text text-muted">Ingrese el nombre completo de la entidad bancaria</small>
                          </div>
                      </div>
                      
                      <!-- Estado -->
                      <div class="col-md-12">
                          <div class="form-group">
                              <label for="status" class="font-weight-bold">
                                  <i class="fas fa-toggle-on mr-1 text-gray-600"></i>Estado
                                  <span class="text-danger">*</span>
                              </label>
                              <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                  <option value="" disabled {{ old('status') ? '' : 'selected' }}>Seleccione un estado</option>
                                  <option value="1" {{ old('status') == '1' ? 'selected' : '' }}>Activo</option>
                                  <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactivo</option>
                              </select>
                              @error('status')
                                  <span class="invalid-feedback">{{ $message }}</span>
                              @enderror
                              <small class="form-text text-muted">Defina si el banco estará disponible para su uso</small>
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
                              <p class="mb-0">Los bancos creados estarán disponibles para ser seleccionados al registrar nuevas cuentas en el sistema.</p>
                          </div>
                      </div>
                  </div>
              </div>
              
              <!-- Botones de acción -->
              <div class="card-footer bg-white">
                  <div class="d-flex justify-content-between">
                      <a href="{{ route('admin.bancos.index') }}" class="btn btn-default">
                          <i class="fas fa-times mr-1"></i>Cancelar
                      </a>
                      <button type="submit" class="btn btn-primary">
                          <i class="fas fa-save mr-1"></i>Guardar Banco
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