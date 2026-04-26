@extends('layouts.admin')
@section('title', 'Crear Usuario')
@section('content_header')
   <div class="container d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-user-plus mr-2"></i>Crear Nuevo Usuario</h1>
       <ol class="breadcrumb float-sm-right">
           <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
           <li class="breadcrumb-item"><a href="{{ route('admin.usuarios.index') }}">Usuarios</a></li>
           <li class="breadcrumb-item active">Crear</li>
       </ol>
   </div>
@stop

@section('content')
   <div class="container pt-2">
       @if (session('error'))
           <div class="alert alert-danger alert-dismissible fade show" role="alert">
               <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
               <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                   <span aria-hidden="true">&times;</span>
               </button>
           </div>
       @endif
       
       <form action="{{ route('admin.usuarios.store') }}" method="post" id="usuarioForm" class="needs-validation" novalidate>
           @csrf
           <div class="card card-outline card-primary shadow-sm">
               <div class="card-header">
                   <h3 class="card-title">Información del Usuario</h3>
               </div>
               
               <div class="card-body">
                   <div class="row">
                       <!-- Datos de identificación -->
                       <div class="col-12">
                           <h5 class="font-weight-bold mb-3">
                               <i class="fas fa-id-card mr-1 text-gray-600"></i>Datos de Identificación
                           </h5>
                       </div>
                       
                       <!-- Nro de documento y búsqueda de persona existente -->
                       <div class="col-md-3">
                           <div class="form-group">
                               <label for="nDocumento" class="font-weight-bold text-gray-600">
                                   Nro de documento <span class="text-danger">*</span>
                               </label>
                               <div class="input-group">
                                   <input type="number" class="form-control @error('nDocumento') is-invalid @enderror" 
                                          name="nDocumento" id="nDocumento" required value="{{ old('nDocumento') }}">
                                   <div class="input-group-append">
                                       <button type="button" class="btn btn-primary" id="consultarDNI" title="Consultar DNI">
                                           <i class="fa fa-search"></i>
                                       </button>
                                   </div>
                                   @error('nDocumento')
                                       <span class="invalid-feedback">{{ $message }}</span>
                                   @enderror
                               </div>
                               <small class="form-text text-muted">Ingrese el número de DNI (será usado como usuario para iniciar sesión)</small>
                           </div>
                       </div>
                       
                       <!-- Nombres -->
                       <div class="col-md-3">
                           <div class="form-group">
                               <label for="nombres" class="font-weight-bold text-gray-600">
                                   Nombres <span class="text-danger">*</span>
                               </label>
                               <input type="text" class="form-control @error('nombres') is-invalid @enderror" 
                                      name="nombres" id="nombres" required value="{{ old('nombres') }}">
                               @error('nombres')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Ingrese nombres completos</small>
                           </div>
                       </div>
                       
                       <!-- Apellido Paterno -->
                       <div class="col-md-3">
                           <div class="form-group">
                               <label for="aPaterno" class="font-weight-bold text-gray-600">
                                   Apellido Paterno <span class="text-danger">*</span>
                               </label>
                               <input type="text" class="form-control @error('aPaterno') is-invalid @enderror" 
                                      name="aPaterno" id="aPaterno" required value="{{ old('aPaterno') }}">
                               @error('aPaterno')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Ingrese apellido paterno</small>
                           </div>
                       </div>
                       
                       <!-- Apellido Materno -->
                       <div class="col-md-3">
                           <div class="form-group">
                               <label for="aMaterno" class="font-weight-bold text-gray-600">
                                   Apellido Materno <span class="text-danger">*</span>
                               </label>
                               <input type="text" class="form-control @error('aMaterno') is-invalid @enderror" 
                                      name="aMaterno" id="aMaterno" required value="{{ old('aMaterno') }}">
                               @error('aMaterno')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Ingrese apellido materno</small>
                           </div>
                       </div>
                       
                       <!-- Separador -->
                       <div class="col-12">
                           <hr>
                           <h5 class="font-weight-bold mb-3">
                               <i class="fas fa-address-card mr-1 text-gray-600"></i>Datos de Contacto y Acceso
                           </h5>
                       </div>
                       
                       <!-- Código -->
                       <div class="col-md-3">
                           <div class="form-group">
                               <label for="codigo" class="font-weight-bold text-gray-600">
                                   Código
                               </label>
                               <div class="input-group">
                                   <div class="input-group-prepend">
                                       <span class="input-group-text bg-light">
                                           <i class="fas fa-hashtag text-blue"></i>
                                       </span>
                                   </div>
                                   <input type="text" class="form-control @error('codigo') is-invalid @enderror" 
                                          name="codigo" id="codigo" value="{{ old('codigo') }}">
                               </div>
                               <small class="form-text text-muted">Código identificador (opcional)</small>
                           </div>
                       </div>
                       
                       <!-- Celular -->
                       <div class="col-md-3">
                           <div class="form-group">
                               <label for="telefono" class="font-weight-bold text-gray-600">
                                   Celular <span class="text-danger">*</span>
                               </label>
                               <div class="input-group">
                                   <div class="input-group-prepend">
                                       <span class="input-group-text bg-light">
                                           <i class="fas fa-mobile-alt text-blue"></i>
                                       </span>
                                   </div>
                                   <input type="number" class="form-control @error('telefono') is-invalid @enderror" 
                                          name="telefono" id="telefono" required value="{{ old('telefono') }}">
                               </div>
                               <small class="form-text text-muted">Número de contacto del usuario</small>
                           </div>
                       </div>
                       
                       <div class="col-md-3">
    <div class="form-group">
        <label for="email" class="font-weight-bold text-gray-600">
            Correo
        </label>
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text bg-light">
                    <i class="fas fa-envelope text-blue"></i>
                </span>
            </div>
            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                   name="email" id="email" value="{{ old('email') }}" autocomplete="email">
        </div>
        @error('email')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror
        <small class="form-text text-muted">Correo electrónico (opcional)</small>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const emailField = document.getElementById('email');
        if (emailField) {
            emailField.addEventListener('input', function () {
                const emailValue = this.value;
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                // Buscar el contenedor de feedback
                let feedbackElement = this.parentNode.querySelector('.invalid-feedback');
                if (!feedbackElement) {
                    // Crear elemento de feedback si no existe
                    feedbackElement = document.createElement('div');
                    feedbackElement.className = 'invalid-feedback';
                    this.parentNode.appendChild(feedbackElement);
                }

                if (!emailRegex.test(emailValue) && emailValue !== '') {
                    this.classList.add('is-invalid');
                    feedbackElement.innerHTML = 'Por favor, ingrese un correo electrónico válido.';
                    feedbackElement.style.display = 'block';
                } else {
                    this.classList.remove('is-invalid');
                    feedbackElement.style.display = 'none';
                }
            });
        }
    });
</script>

<style>
    .invalid-feedback {
        display: none;
        color: #dc3545;
        font-size: 0.875em;
    }
    .is-invalid ~ .invalid-feedback {
        display: block;
    }
</style>
                       
                       <!-- Contraseña -->
                       <div class="col-md-3">
                           <div class="form-group">
                               <label for="password" class="font-weight-bold text-gray-600">
                                   Contraseña <span class="text-danger">*</span>
                               </label>
                               <div class="input-group">
                                   <div class="input-group-prepend">
                                       <span class="input-group-text bg-light">
                                           <i class="fas fa-lock text-blue"></i>
                                       </span>
                                   </div>
                                   <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                          name="password" id="password" required autocomplete="new-password">
                               </div>
                               @error('password')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Contraseña de acceso al sistema</small>
                           </div>
                       </div>

                       <!-- Confirmar Contraseña -->
                       <div class="col-md-3">
                           <div class="form-group">
                               <label for="password_confirmation" class="font-weight-bold text-gray-600">
                                   Confirmar Contraseña <span class="text-danger">*</span>
                               </label>
                               <div class="input-group">
                                   <div class="input-group-prepend">
                                       <span class="input-group-text bg-light">
                                           <i class="fas fa-lock text-blue"></i>
                                       </span>
                                   </div>
                                   <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" 
                                          name="password_confirmation" id="password_confirmation" required autocomplete="new-password">
                               </div>
                               @error('password_confirmation')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Confirme la contraseña</small>
                           </div>
                       </div>
                       
                       <!-- Sucursales -->
                       <div class="col-md-12">
                           <div class="form-group">
                               <label for="sucursal_id" class="font-weight-bold text-gray-600">
                                   <i class="fas fa-building mr-1"></i>Sucursales <span class="text-danger">*</span>
                               </label>
                               <select name="sucursal_id[]" id="sucursal_id" class="form-control select2 @error('sucursal_id') is-invalid @enderror" multiple required>
                                   <option value="">Seleccione una o más sucursales</option>
                                   @foreach ($sucursales as $sucursal)
                                       <option value="{{ $sucursal->id }}" {{ in_array($sucursal->id, old('sucursal_id', [])) ? 'selected' : '' }}>
                                           {{ $sucursal->sucursal }}
                                       </option>
                                   @endforeach
                               </select>
                               @error('sucursal_id')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Seleccione las sucursales donde operará el usuario</small>
                           </div>
                       </div>
                       
                       <!-- Roles -->
                       <div class="col-12 mt-4">
                           <h5 class="font-weight-bold mb-3 text-center">
                               <i class="fas fa-user-tag mr-1 text-gray-600"></i>Selección de Roles
                           </h5>
                           <hr>
                           <div class="row justify-content-center">
                               @foreach ($roles as $role)
                                   <div class="col-md-3 mb-3">
                                       <div class="card h-100 border-0 shadow-sm text-center role-card" id="role_card_{{ $role->id }}">
                                           <input type="checkbox" class="form-check-input d-none" name="roles[]" id="role_{{ $role->id }}" value="{{ $role->id }}">
                                           <label for="role_{{ $role->id }}" class="d-block w-100 h-100 mb-0">
                                               <img src="{{ asset('img/'.$role->name.'.svg') }}" class="card-img-top rounded role-image" alt="{{ $role->name }}" style="height: 150px;">
                                               <div class="card-body">
                                                   <h5 class="card-title">{{ $role->name }}</h5>
                                               </div>
                                           </label>
                                       </div>
                                   </div>
                               @endforeach
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
                               <p class="mb-1">Los usuarios creados podrán acceder al sistema según los roles y permisos asignados.</p>
                               <p class="mb-0"><strong>Inicio de sesión:</strong> Los usuarios deberán usar su número de DNI como nombre de usuario para ingresar al sistema.</p>
                           </div>
                       </div>
                   </div>
               </div>
               
               <!-- Botones de acción -->
               <div class="card-footer bg-white">
                   <div class="d-flex justify-content-between">
                       <a href="{{ route('admin.usuarios.index') }}" class="btn btn-default">
                           <i class="fas fa-times mr-1"></i>Cancelar
                       </a>
                       <button type="submit" class="btn btn-primary">
                           <i class="fas fa-save mr-1"></i>Guardar Usuario
                       </button>
                   </div>
               </div>
           </div>
       </form>
   </div>
@stop

@section('css')
   <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
       .select2-container--default .select2-selection--multiple {
           border-color: #ced4da;
       }
       .select2-container--default.select2-container--focus .select2-selection--multiple {
           border-color: #80bdff;
           box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
       }
       .role-card {
           cursor: pointer;
           transition: all 0.3s ease;
           border: 2px solid transparent !important;
       }
       .role-card.selected-role {
           border-color: #007bff !important;
           background-color: rgba(0, 123, 255, 0.1);
       }
       .role-image {
           transition: transform 0.2s ease;
           padding: 10px;
       }
       .role-card:hover .role-image {
           transform: scale(1.05);
       }
   </style>
@stop

@section('js')
   <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
   <script>
       $(document).ready(function() {
           // Inicializar Select2
           $('#sucursal_id').select2({
               placeholder: "Seleccione una o más sucursales",
               allowClear: true,
               width: '100%'
           });
           
           // Consulta de DNI: primero en base de datos, luego en Reniec
           document.getElementById('consultarDNI').addEventListener('click', function() {
               let dni = document.getElementById('nDocumento').value;
               if (!dni || dni.length !== 8) {
                   Swal.fire({
                       title: 'Error',
                       text: 'Ingrese un número de DNI válido de 8 dígitos',
                       icon: 'error',
                       confirmButtonColor: '#3085d6'
                   });
                   return;
               }
               Swal.fire({
                   title: 'Consultando',
                   text: 'Buscando información del DNI en la base de datos...',
                   allowOutsideClick: false,
                   didOpen: () => {
                       Swal.showLoading();
                   }
               });
               fetch('/admin/personas/buscar/' + dni, {
                   method: 'GET',
                   headers: {
                       'Accept': 'application/json',
                       'Content-Type': 'application/json'
                   }
               })
               .then(response => response.json())
               .then(data => {
                   if (data.success && data.persona) {
                       Swal.close();
                       document.getElementById('nombres').value = data.persona.nombres;
                       document.getElementById('aPaterno').value = data.persona.apellido_paterno;
                       document.getElementById('aMaterno').value = data.persona.apellido_materno;
                       document.getElementById('telefono').value = data.persona.telefono || '';
                       document.getElementById('email').value = data.persona.email || '';
                       document.getElementById('nombres').setAttribute('readonly', true);
                       document.getElementById('aPaterno').setAttribute('readonly', true);
                       document.getElementById('aMaterno').setAttribute('readonly', true);
                       // No bloquear teléfono ni email para permitir actualizaciones
                       Swal.fire({
                           title: '¡Persona encontrada!',
                           text: 'Datos cargados desde la base de datos. Puede actualizar teléfono/email si es necesario.',
                           icon: 'success',
                           confirmButtonColor: '#3085d6'
                       });
                   } else {
                       // Si no existe en base de datos, buscar en Reniec
                       fetch('/proxy-dni/' + dni, {
                           method: 'GET',
                           headers: {
                               'Accept': 'application/json',
                               'Content-Type': 'application/json'
                           }
                       })
                       .then(response => response.json())
                       .then(data => {
                           Swal.close();
                           if (data.success) {
                               document.getElementById('nombres').value = data.data.nombres;
                               document.getElementById('aPaterno').value = data.data.apellido_paterno;
                               document.getElementById('aMaterno').value = data.data.apellido_materno;
                               document.getElementById('nombres').removeAttribute('readonly');
                               document.getElementById('aPaterno').removeAttribute('readonly');
                               document.getElementById('aMaterno').removeAttribute('readonly');
                               document.getElementById('telefono').removeAttribute('readonly');
                               document.getElementById('email').removeAttribute('readonly');
                               Swal.fire({
                                   title: '¡Éxito!',
                                   text: 'Datos cargados desde Reniec. Complete los datos faltantes.',
                                   icon: 'success',
                                   confirmButtonColor: '#3085d6'
                               });
                           } else {
                               Swal.fire({
                                   title: 'No encontrado',
                                   text: 'No se encontraron datos para el DNI proporcionado.',
                                   icon: 'info',
                                   confirmButtonColor: '#3085d6'
                               });
                           }
                       })
                       .catch(error => {
                           Swal.close();
                           console.error('Error:', error);
                           Swal.fire({
                               title: 'Error',
                               text: 'Hubo un problema al consultar el DNI en Reniec',
                               icon: 'error',
                               confirmButtonColor: '#3085d6'
                           });
                       });
                   }
               })
               .catch(error => {
                   Swal.close();
                   console.error('Error:', error);
                   Swal.fire({
                       title: 'Error',
                       text: 'Hubo un problema al consultar el DNI',
                       icon: 'error',
                       confirmButtonColor: '#3085d6'
                   });
               });
           });

           // Selección de roles con tarjetas
           document.querySelectorAll('.role-card').forEach(card => {
               card.addEventListener('click', function() {
                   const checkbox = this.querySelector('input[type="checkbox"]');
                   checkbox.checked = !checkbox.checked;
                   this.classList.toggle('selected-role', checkbox.checked);
               });
           });

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