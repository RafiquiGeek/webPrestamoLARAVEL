@extends('layouts.admin')
@section('title', 'Crear Rol')
@section('content_header')
  <div class="container d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-plus-circle mr-2"></i>Crear Nuevo Rol</h1>
      <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li>
          <li class="breadcrumb-item active">Crear</li>
      </ol>
  </div>
@stop

@section('content')
  <div class="container pt-2">
      <div class="card card-outline card-primary shadow-sm">
          <div class="card-header">
              <h3 class="card-title">Información del Rol</h3>
          </div>
          
          <form action="{{ route('admin.roles.store') }}" method="POST">
              @csrf
              <div class="card-body">
                  <!-- Mostrar errores -->
                  @if ($errors->any())
                      <div class="alert alert-danger alert-dismissible fade show" role="alert">
                          <ul class="mb-0">
                              @foreach ($errors->all() as $error)
                                  <li>{{ $error }}</li>
                              @endforeach
                          </ul>
                          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                          </button>
                      </div>
                  @endif
                  
                  <div class="row">
                      <!-- Nombre del Rol -->
                      <div class="col-md-12">
                          <div class="form-group">
                              <label for="name" class="font-weight-bold">
                                  <i class="fas fa-user-tag mr-1 text-gray-600"></i>Nombre del Rol
                                  <span class="text-danger">*</span>
                              </label>
                              <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text bg-light">
                                          <i class="fas fa-pencil-alt text-blue"></i>
                                      </span>
                                  </div>
                                  <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                         name="name" id="name" value="{{ old('name') }}" 
                                         placeholder="Ejemplo: Administrador" required>
                                  @error('name')
                                      <span class="invalid-feedback">{{ $message }}</span>
                                  @enderror
                              </div>
                              <small class="form-text text-muted">Ingrese un nombre descriptivo para el rol</small>
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
                              <p class="mb-0">Los roles permiten asignar diferentes niveles de acceso y permisos a los usuarios del sistema.</p>
                          </div>
                      </div>
                  </div>
              </div>
              
              <!-- Botones de acción -->
              <div class="card-footer bg-white">
                  <div class="d-flex justify-content-between">
                      <a href="{{ route('admin.roles.index') }}" class="btn btn-default">
                          <i class="fas fa-times mr-1"></i>Cancelar
                      </a>
                      <button type="submit" class="btn btn-primary">
                          <i class="fas fa-save mr-1"></i>Guardar Rol
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