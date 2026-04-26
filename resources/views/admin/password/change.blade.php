@extends('layouts.admin')
@section('title', 'Cambiar Contraseña')
@section('content_header')
   <div class="container d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-lock mr-2"></i>Cambiar Contraseña</h1>
       <ol class="breadcrumb float-sm-right">
           <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Dashboard</a></li>
           <li class="breadcrumb-item active">Cambiar Contraseña</li>
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
       
       <div class="row justify-content-center">
           <div class="col-md-8">
               <form action="{{ route('admin.password.update') }}" method="post" id="passwordForm" class="needs-validation" novalidate>
                   @csrf
                   @method('PUT')
                   <div class="card card-outline card-warning shadow-sm">
                       <div class="card-header">
                           <h3 class="card-title">
                               <i class="fas fa-shield-alt mr-2"></i>Seguridad de la Cuenta
                           </h3>
                       </div>
                       
                       <div class="card-body">
                           <!-- Información del usuario -->
                           <div class="alert alert-info">
                               <div class="d-flex align-items-center">
                                   <i class="fas fa-info-circle fa-2x mr-3"></i>
                                   <div>
                                       <h5 class="alert-heading mb-1">Usuario: {{ Auth::user()->name }}</h5>
                                       <p class="mb-0">Email: {{ Auth::user()->email }}</p>
                                   </div>
                               </div>
                           </div>

                           <div class="row">
                               <!-- Contraseña actual -->
                               <div class="col-12">
                                   <div class="form-group">
                                       <label for="current_password" class="font-weight-bold text-gray-600">
                                           <i class="fas fa-key mr-1"></i>Contraseña Actual <span class="text-danger">*</span>
                                       </label>
                                       <div class="input-group">
                                           <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                                                  name="current_password" id="current_password" required
                                                  placeholder="Ingrese su contraseña actual">
                                           <div class="input-group-append">
                                               <button class="btn btn-outline-secondary" type="button" id="toggleCurrentPassword">
                                                   <i class="fas fa-eye" id="currentPasswordIcon"></i>
                                               </button>
                                           </div>
                                       </div>
                                       @error('current_password')
                                           <span class="invalid-feedback">{{ $message }}</span>
                                       @enderror
                                       <small class="form-text text-muted">Debe ingresar su contraseña actual para confirmar el cambio</small>
                                   </div>
                               </div>

                               <!-- Nueva contraseña -->
                               <div class="col-md-6">
                                   <div class="form-group">
                                       <label for="password" class="font-weight-bold text-gray-600">
                                           <i class="fas fa-lock mr-1"></i>Nueva Contraseña <span class="text-danger">*</span>
                                       </label>
                                       <div class="input-group">
                                           <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                                  name="password" id="password" required minlength="6"
                                                  placeholder="Mínimo 6 caracteres">
                                           <div class="input-group-append">
                                               <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                   <i class="fas fa-eye" id="passwordIcon"></i>
                                               </button>
                                           </div>
                                       </div>
                                       @error('password')
                                           <span class="invalid-feedback">{{ $message }}</span>
                                       @enderror
                                       <div id="passwordStrength" class="mt-1"></div>
                                   </div>
                               </div>

                               <!-- Confirmar contraseña -->
                               <div class="col-md-6">
                                   <div class="form-group">
                                       <label for="password_confirmation" class="font-weight-bold text-gray-600">
                                           <i class="fas fa-lock mr-1"></i>Confirmar Nueva Contraseña <span class="text-danger">*</span>
                                       </label>
                                       <div class="input-group">
                                           <input type="password" class="form-control" 
                                                  name="password_confirmation" id="password_confirmation" required
                                                  placeholder="Repita la nueva contraseña">
                                           <div class="input-group-append">
                                               <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirmation">
                                                   <i class="fas fa-eye" id="passwordConfirmationIcon"></i>
                                               </button>
                                           </div>
                                       </div>
                                       <small class="form-text text-muted">Debe coincidir con la nueva contraseña</small>
                                       <div id="passwordMatch" class="mt-1"></div>
                                   </div>
                               </div>
                           </div>

                           <!-- Recomendaciones de seguridad -->
                           <div class="alert alert-warning mt-3">
                               <h6 class="alert-heading">
                                   <i class="fas fa-shield-alt mr-1"></i>Recomendaciones de Seguridad
                               </h6>
                               <ul class="mb-0 small">
                                   <li>Use al menos 8 caracteres</li>
                                   <li>Combine letras mayúsculas y minúsculas</li>
                                   <li>Incluya números y símbolos especiales</li>
                                   <li>No use información personal obvia</li>
                                   <li>No reutilice contraseñas anteriores</li>
                               </ul>
                           </div>
                       </div>
                       
                       <!-- Botones de acción -->
                       <div class="card-footer bg-white">
                           <div class="d-flex justify-content-between">
                               <a href="{{ route('admin.profile.edit') }}" class="btn btn-default">
                                   <i class="fas fa-arrow-left mr-1"></i>Volver al Perfil
                               </a>
                               <button type="submit" class="btn btn-warning" id="submitBtn" disabled>
                                   <i class="fas fa-save mr-1"></i>Cambiar Contraseña
                               </button>
                           </div>
                       </div>
                   </div>
               </form>
           </div>
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
       .password-strength {
           height: 5px;
           border-radius: 3px;
           margin-top: 5px;
       }
       .strength-weak { background-color: #dc3545; }
       .strength-medium { background-color: #ffc107; }
       .strength-strong { background-color: #28a745; }
       .was-validated .form-control:invalid, .form-control.is-invalid {
           border-color: #dc3545;
       }
       .match-success { color: #28a745; }
       .match-error { color: #dc3545; }
   </style>
@stop

@section('js')
   <script>
       $(document).ready(function() {
           // Toggle password visibility
           $('#toggleCurrentPassword').click(function() {
               const password = $('#current_password');
               const icon = $('#currentPasswordIcon');
               if (password.attr('type') === 'password') {
                   password.attr('type', 'text');
                   icon.removeClass('fa-eye').addClass('fa-eye-slash');
               } else {
                   password.attr('type', 'password');
                   icon.removeClass('fa-eye-slash').addClass('fa-eye');
               }
           });

           $('#togglePassword').click(function() {
               const password = $('#password');
               const icon = $('#passwordIcon');
               if (password.attr('type') === 'password') {
                   password.attr('type', 'text');
                   icon.removeClass('fa-eye').addClass('fa-eye-slash');
               } else {
                   password.attr('type', 'password');
                   icon.removeClass('fa-eye-slash').addClass('fa-eye');
               }
           });

           $('#togglePasswordConfirmation').click(function() {
               const password = $('#password_confirmation');
               const icon = $('#passwordConfirmationIcon');
               if (password.attr('type') === 'password') {
                   password.attr('type', 'text');
                   icon.removeClass('fa-eye').addClass('fa-eye-slash');
               } else {
                   password.attr('type', 'password');
                   icon.removeClass('fa-eye-slash').addClass('fa-eye');
               }
           });

           // Password strength checker
           $('#password').on('input', function() {
               const password = $(this).val();
               const strength = checkPasswordStrength(password);
               updatePasswordStrength(strength);
               checkPasswordsMatch();
           });

           // Password confirmation checker
           $('#password_confirmation').on('input', function() {
               checkPasswordsMatch();
           });

           function checkPasswordStrength(password) {
               let score = 0;
               if (password.length >= 8) score++;
               if (/[a-z]/.test(password)) score++;
               if (/[A-Z]/.test(password)) score++;
               if (/[0-9]/.test(password)) score++;
               if (/[^A-Za-z0-9]/.test(password)) score++;
               
               if (score < 3) return 'weak';
               if (score < 5) return 'medium';
               return 'strong';
           }

           function updatePasswordStrength(strength) {
               const strengthDiv = $('#passwordStrength');
               strengthDiv.html('');
               
               if ($('#password').val().length > 0) {
                   let strengthText = '';
                   let strengthClass = '';
                   
                   switch(strength) {
                       case 'weak':
                           strengthText = 'Débil';
                           strengthClass = 'strength-weak';
                           break;
                       case 'medium':
                           strengthText = 'Media';
                           strengthClass = 'strength-medium';
                           break;
                       case 'strong':
                           strengthText = 'Fuerte';
                           strengthClass = 'strength-strong';
                           break;
                   }
                   
                   strengthDiv.html(`
                       <small>Fortaleza: ${strengthText}</small>
                       <div class="password-strength ${strengthClass}"></div>
                   `);
               }
           }

           function checkPasswordsMatch() {
               const password = $('#password').val();
               const confirmation = $('#password_confirmation').val();
               const matchDiv = $('#passwordMatch');
               const submitBtn = $('#submitBtn');
               
               if (confirmation.length > 0) {
                   if (password === confirmation) {
                       matchDiv.html('<small class="match-success"><i class="fas fa-check mr-1"></i>Las contraseñas coinciden</small>');
                       if (password.length >= 6) {
                           submitBtn.prop('disabled', false);
                       }
                   } else {
                       matchDiv.html('<small class="match-error"><i class="fas fa-times mr-1"></i>Las contraseñas no coinciden</small>');
                       submitBtn.prop('disabled', true);
                   }
               } else {
                   matchDiv.html('');
                   submitBtn.prop('disabled', true);
               }
           }

           // Form validation
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