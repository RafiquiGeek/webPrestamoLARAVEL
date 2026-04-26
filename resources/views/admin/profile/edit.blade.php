@extends('layouts.admin')
@section('title', 'Mi Perfil')
@section('content_header')
   <div class="container d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-user-edit mr-2"></i>Mi Perfil</h1>
       <ol class="breadcrumb float-sm-right">
           <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Dashboard</a></li>
           <li class="breadcrumb-item active">Mi Perfil</li>
       </ol>
   </div>
@stop

@section('content')
   <div class="container pt-2">
       @if (session('success'))
           <div class="alert alert-success alert-dismissible fade show" role="alert">
               <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
               <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                   <span aria-hidden="true">&times;</span>
               </button>
           </div>
       @endif

       @if (session('error'))
           <div class="alert alert-danger alert-dismissible fade show" role="alert">
               <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
               <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                   <span aria-hidden="true">&times;</span>
               </button>
           </div>
       @endif
       
       <form action="{{ route('admin.profile.update') }}" method="post" id="profileForm" class="needs-validation" novalidate>
           @csrf
           @method('PUT')
           <div class="card card-outline card-primary shadow-sm">
               <div class="card-header">
                   <h3 class="card-title">
                       <i class="fas fa-user mr-2"></i>Información Personal
                   </h3>
               </div>
               
               <div class="card-body">
                   <div class="row">
                       <!-- Documento de identidad (readonly) -->
                       <div class="col-md-6">
                           <div class="form-group">
                               <label for="documento" class="font-weight-bold text-gray-600">
                                   <i class="fas fa-id-card mr-1"></i>Documento de Identidad
                               </label>
                               <input type="text" class="form-control" id="documento" 
                                      value="{{ optional($user->persona)->documento ?? 'No disponible' }}" 
                                      readonly>
                               <small class="form-text text-muted">Este campo no se puede modificar</small>
                           </div>
                       </div>

                       <!-- Email -->
                       <div class="col-md-6">
                           <div class="form-group">
                               <label for="email" class="font-weight-bold text-gray-600">
                                   <i class="fas fa-envelope mr-1"></i>Correo Electrónico <span class="text-danger">*</span>
                               </label>
                               <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                      name="email" id="email" required 
                                      value="{{ old('email', $user->email) }}">
                               @error('email')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Usado para iniciar sesión</small>
                           </div>
                       </div>
                       
                       <!-- Nombres -->
                       <div class="col-md-4">
                           <div class="form-group">
                               <label for="nombres" class="font-weight-bold text-gray-600">
                                   <i class="fas fa-user mr-1"></i>Nombres <span class="text-danger">*</span>
                               </label>
                               <input type="text" class="form-control @error('nombres') is-invalid @enderror" 
                                      name="nombres" id="nombres" required 
                                      value="{{ old('nombres', optional($user->persona)->nombres) }}">
                               @error('nombres')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                           </div>
                       </div>
                       
                       <!-- Apellido Paterno -->
                       <div class="col-md-4">
                           <div class="form-group">
                               <label for="ape_pat" class="font-weight-bold text-gray-600">
                                   <i class="fas fa-user mr-1"></i>Apellido Paterno <span class="text-danger">*</span>
                               </label>
                               <input type="text" class="form-control @error('ape_pat') is-invalid @enderror" 
                                      name="ape_pat" id="ape_pat" required 
                                      value="{{ old('ape_pat', optional($user->persona)->ape_pat) }}">
                               @error('ape_pat')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                           </div>
                       </div>
                       
                       <!-- Apellido Materno -->
                       <div class="col-md-4">
                           <div class="form-group">
                               <label for="ape_mat" class="font-weight-bold text-gray-600">
                                   <i class="fas fa-user mr-1"></i>Apellido Materno <span class="text-danger">*</span>
                               </label>
                               <input type="text" class="form-control @error('ape_mat') is-invalid @enderror" 
                                      name="ape_mat" id="ape_mat" required 
                                      value="{{ old('ape_mat', optional($user->persona)->ape_mat) }}">
                               @error('ape_mat')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                           </div>
                       </div>

                       <!-- Teléfono -->
                       <div class="col-md-6">
                           <div class="form-group">
                               <label for="telefono" class="font-weight-bold text-gray-600">
                                   <i class="fas fa-mobile-alt mr-1"></i>Teléfono <span class="text-danger">*</span>
                               </label>
                               <input type="text" class="form-control @error('telefono') is-invalid @enderror" 
                                      name="telefono" id="telefono" required maxlength="15"
                                      value="{{ old('telefono', optional(optional($user->persona)->telefono_principal)->numero) }}">
                               @error('telefono')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Número de contacto</small>
                           </div>
                       </div>

                       <!-- Código de usuario -->
                       <div class="col-md-6">
                           <div class="form-group">
                               <label for="codigo" class="font-weight-bold text-gray-600">
                                   <i class="fas fa-hashtag mr-1"></i>Código de Usuario
                               </label>
                               <input type="text" class="form-control" id="codigo" 
                                      value="{{ $user->codigo ?? 'No asignado' }}" readonly>
                               <small class="form-text text-muted">Asignado por el administrador</small>
                           </div>
                       </div>
                   </div>

                   <!-- Información de roles y sucursales (solo lectura) -->
                   <div class="row">
                       <div class="col-12">
                           <hr>
                           <h5 class="font-weight-bold mb-3">
                               <i class="fas fa-info-circle mr-1 text-gray-600"></i>Información del Sistema
                           </h5>
                       </div>

                       <div class="col-md-6">
                           <div class="form-group">
                               <label class="font-weight-bold text-gray-600">
                                   <i class="fas fa-user-tag mr-1"></i>Roles Asignados
                               </label>
                               <div class="bg-light p-2 rounded">
                                   @if($user->roles->count() > 0)
                                       @foreach($user->roles as $role)
                                           <span class="badge badge-primary mr-1">{{ $role->name }}</span>
                                       @endforeach
                                   @else
                                       <span class="text-muted">Sin roles asignados</span>
                                   @endif
                               </div>
                           </div>
                       </div>

                       <div class="col-md-6">
                           <div class="form-group">
                               <label class="font-weight-bold text-gray-600">
                                   <i class="fas fa-building mr-1"></i>Sucursales Asignadas
                               </label>
                               <div class="bg-light p-2 rounded">
                                   @if($user->sucursales && $user->sucursales->count() > 0)
                                       @foreach($user->sucursales as $sucursal)
                                           <span class="badge badge-info mr-1">{{ $sucursal->sucursal }}</span>
                                       @endforeach
                                   @else
                                       <span class="text-muted">Sin sucursales asignadas</span>
                                   @endif
                               </div>
                           </div>
                       </div>
                   </div>
               </div>
               
               <!-- Botones de acción -->
               <div class="card-footer bg-white">
                   <div class="d-flex justify-content-between">
                       <a href="{{ route('admin.index') }}" class="btn btn-default">
                           <i class="fas fa-arrow-left mr-1"></i>Volver al Dashboard
                       </a>
                       <div>
                           <a href="{{ route('admin.password.change') }}" class="btn btn-warning mr-2">
                               <i class="fas fa-lock mr-1"></i>Cambiar Contraseña
                           </a>
                           <button type="submit" class="btn btn-primary">
                               <i class="fas fa-save mr-1"></i>Actualizar Perfil
                           </button>
                       </div>
                   </div>
               </div>
           </div>
       </form>
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
       .badge {
           font-size: 0.85em;
       }
       .was-validated .form-control:invalid, .form-control.is-invalid {
           border-color: #dc3545;
           padding-right: calc(1.5em + 0.75rem);
           background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
           background-repeat: no-repeat;
           background-position: right calc(0.375em + 0.1875rem) center;
           background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
       }
   </style>
@stop

@section('js')
   <script>
       $(document).ready(function() {
           // Validación del formulario
           (function() {
               'use strict';
               window.addEventListener('load', function() {
                   var forms = document.getElementsByClassName('needs-validation');
                   var validation = Array.prototype.filter.call(forms, function(form) {
                       form.addEventListener('submit', function(event) {
                           if (form.checkValidity() === false) {
                               event.preventDefault();
                               event.stopPropagation();
                           }
                           form.classList.add('was-validated');
                       }, false);
                   });
               }, false);
           })();
       });
   </script>
@stop