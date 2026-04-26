@extends('layouts.admin')
@section('title', 'Editar Usuario')
@section('content_header')
   <div class="container d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-user-edit mr-2"></i>Editar Usuario</h1>
       <ol class="breadcrumb float-sm-right">
           <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
           <li class="breadcrumb-item"><a href="{{ route('admin.usuarios.index') }}">Usuarios</a></li>
           <li class="breadcrumb-item active">Editar</li>
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
       
       <form action="{{ route('admin.usuarios.update', $usuario->id) }}" method="post" id="usuarioForm" class="needs-validation" novalidate>
           @csrf
           @method('PUT')
           <div class="card card-outline card-primary shadow-sm">
               <div class="card-header">
                   <h3 class="card-title">Modificar Información del Usuario</h3>
               </div>
               
               <div class="card-body">
                   <div class="row">
                       <!-- Datos de identificación -->
                       <div class="col-12">
                           <h5 class="font-weight-bold mb-3">
                               <i class="fas fa-id-card mr-1 text-gray-600"></i>Datos de Identificación
                           </h5>
                       </div>
                       
                       <!-- Nro de documento -->
                       <div class="col-md-3">
                           <div class="form-group">
                               <label for="nDocumento" class="font-weight-bold text-gray-600">
                                   Nro de documento <span class="text-danger">*</span>
                               </label>
                               <input type="number" class="form-control @error('nDocumento') is-invalid @enderror" 
                                      name="nDocumento" id="nDocumento" required 
                                      value="{{ old('nDocumento', optional($usuario->persona)->documento) }}" readonly>
                               @error('nDocumento')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Número de DNI</small>
                           </div>
                       </div>
                       
                       <!-- Nombres -->
                       <div class="col-md-3">
                           <div class="form-group">
                               <label for="nombres" class="font-weight-bold text-gray-600">
                                   Nombres <span class="text-danger">*</span>
                               </label>
                               <input type="text" class="form-control @error('nombres') is-invalid @enderror" 
                                      name="nombres" id="nombres" required 
                                      value="{{ old('nombres', optional($usuario->persona)->nombres) }}">
                               @error('nombres')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Nombres completos</small>
                           </div>
                       </div>
                       
                       <!-- Apellido Paterno -->
                       <div class="col-md-3">
                           <div class="form-group">
                               <label for="aPaterno" class="font-weight-bold text-gray-600">
                                   Apellido Paterno <span class="text-danger">*</span>
                               </label>
                               <input type="text" class="form-control @error('aPaterno') is-invalid @enderror" 
                                      name="aPaterno" id="aPaterno" required 
                                      value="{{ old('aPaterno', optional($usuario->persona)->ape_pat) }}">
                               @error('aPaterno')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Apellido paterno</small>
                           </div>
                       </div>
                       
                       <!-- Apellido Materno -->
                       <div class="col-md-3">
                           <div class="form-group">
                               <label for="aMaterno" class="font-weight-bold text-gray-600">
                                   Apellido Materno <span class="text-danger">*</span>
                               </label>
                               <input type="text" class="form-control @error('aMaterno') is-invalid @enderror" 
                                      name="aMaterno" id="aMaterno" required 
                                      value="{{ old('aMaterno', optional($usuario->persona)->ape_mat) }}">
                               @error('aMaterno')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Apellido materno</small>
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
                                          name="codigo" id="codigo" 
                                          value="{{ old('codigo', $usuario->codigo) }}">
                               </div>
                               <small class="form-text text-muted">Código identificador</small>
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
                                          name="telefono" id="telefono" required 
                                          value="{{ old('telefono', optional(optional($usuario->persona)->telefonoPrincipal)->numero) }}">
                               </div>
                               @error('telefono')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Número de contacto</small>
                           </div>
                       </div>
                       
                       <!-- Correo -->
                       <div class="col-md-3">
                           <div class="form-group">
                               <label for="email" class="font-weight-bold text-gray-600">
                                   Correo <span class="text-danger">*</span>
                               </label>
                               <div class="input-group">
                                   <div class="input-group-prepend">
                                       <span class="input-group-text bg-light">
                                           <i class="fas fa-envelope text-blue"></i>
                                       </span>
                                   </div>
                                   <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                          name="email" id="email" required 
                                          value="{{ old('email', $usuario->email) }}">
                               </div>
                               @error('email')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Correo electrónico</small>
                           </div>
                       </div>
                       
                       <!-- Sucursales -->
                       <div class="col-md-3">
                           <div class="form-group">
                               <label for="sucursal_id" class="font-weight-bold text-gray-600">
                                   <i class="fas fa-building mr-1"></i>Sucursales <span class="text-danger">*</span>
                               </label>
                               <select name="sucursal_id[]" id="sucursal_id" 
                                       class="form-control select2 @error('sucursal_id') is-invalid @enderror" 
                                       multiple required>
                                   @foreach ($sucursales as $sucursal)
                                       <option value="{{ $sucursal->id }}" 
                                           {{ in_array($sucursal->id, old('sucursal_id', $usuario->sucursales->pluck('id')->toArray())) ? 'selected' : '' }}>
                                           {{ $sucursal->sucursal }}
                                       </option>
                                   @endforeach
                               </select>
                               @error('sucursal_id')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                                <small class="form-text text-muted">Sucursales asignadas</small>
                            </div>
                        </div>

                        <!-- IPs Permitidas -->
                        <div class="col-12 mt-3">
                            <hr>
                            <h5 class="font-weight-bold mb-3">
                                <i class="fas fa-network-wired mr-1 text-gray-600"></i>Restricción de Acceso (IPs)
                            </h5>
                            
                            <div class="alert alert-light border shadow-sm">
                                <small class="text-muted"><i class="fas fa-info-circle mr-1 text-info"></i> Si no especifica ninguna IP, el usuario podrá acceder desde cualquier ubicación.</small>
                            </div>
                            
                            <div id="ips_container">
                                @php
                                    $ips = old('allowed_ips', $usuario->allowed_ips ?? []);
                                @endphp
                                @if(!empty($ips))
                                    @foreach($ips as $index => $ip)
                                        @if($ip) <!-- Filter out empty ones if any -->
                                        <div class="row mb-2 ip-row fade-in">
                                            <div class="col-md-4">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text bg-light text-muted"><i class="fas fa-laptop"></i></span>
                                                    </div>
                                                    <input type="text" name="allowed_ips[]" class="form-control ip-input" placeholder="Ej: 192.168.1.1" value="{{ $ip }}" pattern="^([0-9]{1,3}\.){3}[0-9]{1,3}$">
                                                    <div class="input-group-append">
                                                        <button type="button" class="btn btn-outline-danger remove-ip"><i class="fas fa-trash-alt"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2 shadow-sm" id="add_ip_btn">
                                <i class="fas fa-plus mr-1"></i> Agregar IP Permitida
                            </button>
                        </div>
                        
                        <!-- Sección de Cambio de Contraseña -->
                        <div class="col-12">
                           <hr>
                           <h5 class="font-weight-bold mb-3">
                               <i class="fas fa-key mr-1 text-gray-600"></i>Cambio de Contraseña
                           </h5>
                       </div>
                       
                       <!-- Nueva Contraseña -->
                       <div class="col-md-6">
                           <div class="form-group">
                               <label for="password" class="font-weight-bold text-gray-600">
                                   Nueva Contraseña
                               </label>
                               <div class="input-group">
                                   <div class="input-group-prepend">
                                       <span class="input-group-text bg-light">
                                           <i class="fas fa-lock text-blue"></i>
                                       </span>
                                   </div>
                                   <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                          name="password" id="password" 
                                          placeholder="Dejar vacío para no cambiar">
                                   <div class="input-group-append">
                                       <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                           <i class="fas fa-eye" id="eyeIcon"></i>
                                       </button>
                                   </div>
                               </div>
                               @error('password')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Mínimo 8 caracteres. Dejar vacío si no desea cambiar la contraseña.</small>
                           </div>
                       </div>
                       
                       <!-- Confirmar Contraseña -->
                       <div class="col-md-6">
                           <div class="form-group">
                               <label for="password_confirmation" class="font-weight-bold text-gray-600">
                                   Confirmar Contraseña
                               </label>
                               <div class="input-group">
                                   <div class="input-group-prepend">
                                       <span class="input-group-text bg-light">
                                           <i class="fas fa-lock text-blue"></i>
                                       </span>
                                   </div>
                                   <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" 
                                          name="password_confirmation" id="password_confirmation" 
                                          placeholder="Confirmar nueva contraseña">
                                   <div class="input-group-append">
                                       <button type="button" class="btn btn-outline-secondary" id="togglePasswordConfirmation">
                                           <i class="fas fa-eye" id="eyeIconConfirmation"></i>
                                       </button>
                                   </div>
                               </div>
                               @error('password_confirmation')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Repetir la nueva contraseña para confirmar.</small>
                           </div>
                       </div>
                       
                       <!-- Alerta informativa sobre cambio de contraseña -->
                       <div class="col-12" id="passwordAlert" style="display: none;">
                           <div class="alert alert-info">
                               <div class="d-flex">
                                   <div class="mr-3">
                                       <i class="fas fa-info-circle fa-lg"></i>
                                   </div>
                                   <div>
                                       <h6 class="alert-heading font-weight-bold mb-1">Cambio de Contraseña Administrativo</h6>
                                       <p class="mb-1">Como administrador, puedes cambiar la contraseña de cualquier usuario sin conocer la contraseña actual.</p>
                                       <ul class="mb-0 small">
                                           <li>La nueva contraseña debe tener mínimo 8 caracteres</li>
                                           <li>Se requiere confirmación para evitar errores</li>
                                           <li>El cambio es inmediato una vez guardado</li>
                                           <li>Recuerda informar al usuario sobre el cambio</li>
                                       </ul>
                                   </div>
                               </div>
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
                                       <div class="card h-100 border-0 shadow-sm text-center role-card 
                                           {{ in_array($role->id, old('roles', $usuario->roles->pluck('id')->toArray())) ? 'selected-role' : '' }}" 
                                           id="role_card_{{ $role->id }}">
                                           <input type="checkbox" class="form-check-input d-none" 
                                                  name="roles[]" id="role_{{ $role->id }}" 
                                                  value="{{ $role->id }}" 
                                                  {{ in_array($role->id, old('roles', $usuario->roles->pluck('id')->toArray())) ? 'checked' : '' }}>
                                           <label for="role_{{ $role->id }}" class="d-block w-100 h-100 mb-0">
                                               <img src="{{ asset('img/'.$role->name.'.svg') }}" 
                                                    class="card-img-top rounded role-image" 
                                                    alt="{{ $role->name }}" style="height: 150px;">
                                               <div class="card-body">
                                                   <h5 class="card-title">{{ $role->name }}</h5>
                                               </div>
                                           </label>
                                       </div>
                                   </div>
                               @endforeach
                           </div>
                           @error('roles')
                               <div class="text-danger small">{{ $message }}</div>
                           @enderror
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
                               <p class="mb-0">Modificar los roles de un usuario puede afectar sus permisos en el sistema. Asegúrese de que los cambios son correctos.</p>
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
                           <i class="fas fa-save mr-1"></i>Actualizar Usuario
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
       .was-validated .form-control:invalid, .form-control.is-invalid {
           border-color: #dc3545;
           padding-right: calc(1.5em + 0.75rem);
           background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
           background-repeat: no-repeat;
           background-position: right calc(0.375em + 0.1875rem) center;
           background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
       }
       .was-validated .select2-container--default .select2-selection--multiple:invalid, 
       .select2-container--default .select2-selection--multiple.is-invalid {
           border-color: #dc3545 !important;
       }
       
       /* Estilos para sección de contraseña */
       #passwordAlert {
           animation: slideDown 0.3s ease-out;
       }
       
       @keyframes slideDown {
           from {
               opacity: 0;
               transform: translateY(-10px);
           }
           to {
               opacity: 1;
               transform: translateY(0);
           }
       }
       
       .alert-info {
           background-color: #e8f4fd;
           border-color: #b8daff;
           color: #0c5460;
       }
       
       .input-group-append .btn,
       .input-group-prepend .btn {
           border-color: #ced4da;
       }
       
       .input-group-append .btn:hover,
       .input-group-prepend .btn:hover {
           background-color: #f8f9fa;
           border-color: #6c757d;
       }
       
       /* Validación personalizada para contraseñas */
       .password-strength {
           font-size: 0.75rem;
           margin-top: 0.25rem;
       }
       
       .password-strength.weak {
           color: #dc3545;
       }
       
       .password-strength.medium {
           color: #ffc107;
       }
       
       .password-strength.strong {
           color: #28a745;
       }
   </style>
@stop

@section('js')
   <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
   <script>
       $(document).ready(function() {
           // Inicializar Select2 para sucursales
           $('#sucursal_id').select2({
               placeholder: "Seleccione una o más sucursales",
               allowClear: true,
               width: '100%'
           });
           
           // Selección de roles con tarjetas
            document.querySelectorAll('.role-card').forEach(card => {
                card.addEventListener('click', function() {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    checkbox.checked = !checkbox.checked;
                    this.classList.toggle('selected-role', checkbox.checked);
                });
            });

            // Gestión de IPs
            $('#add_ip_btn').click(function() {
                var newRow = `
                    <div class="row mb-2 ip-row fade-in">
                        <div class="col-md-4">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-light text-muted"><i class="fas fa-laptop"></i></span>
                                </div>
                                <input type="text" name="allowed_ips[]" class="form-control ip-input" placeholder="Ej: 192.168.1.1" pattern="^([0-9]{1,3}\\.){3}[0-9]{1,3}$">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-danger remove-ip"><i class="fas fa-trash-alt"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                var $row = $(newRow);
                $('#ips_container').append($row);
                $row.hide().fadeIn(300);
            });

            $(document).on('click', '.remove-ip', function() {
                $(this).closest('.ip-row').fadeOut(300, function() { $(this).remove(); });
            });
           
           // Mostrar/ocultar contraseña
           $('#togglePassword').click(function() {
               const passwordField = $('#password');
               const eyeIcon = $('#eyeIcon');
               const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
               passwordField.attr('type', type);
               eyeIcon.toggleClass('fa-eye fa-eye-slash');
           });
           
           $('#togglePasswordConfirmation').click(function() {
               const passwordField = $('#password_confirmation');
               const eyeIcon = $('#eyeIconConfirmation');
               const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
               passwordField.attr('type', type);
               eyeIcon.toggleClass('fa-eye fa-eye-slash');
           });
           
           // Mostrar alerta cuando se escriba en el campo de contraseña
           $('#password, #password_confirmation').on('input', function() {
               const passwordValue = $('#password').val();
               const confirmValue = $('#password_confirmation').val();
               
               if (passwordValue.length > 0 || confirmValue.length > 0) {
                   $('#passwordAlert').slideDown();
               } else {
                   $('#passwordAlert').slideUp();
               }
           });
           
           // Validación de confirmación de contraseña
           $('#password_confirmation').on('input', function() {
               const password = $('#password').val();
               const confirmPassword = $(this).val();
               
               if (password !== '' && confirmPassword !== '' && password !== confirmPassword) {
                   $(this).addClass('is-invalid');
                   $(this).next('.invalid-feedback').remove();
                   $(this).parent().after('<div class="invalid-feedback">Las contraseñas no coinciden</div>');
               } else {
                   $(this).removeClass('is-invalid');
                   $(this).parent().next('.invalid-feedback').remove();
               }
           });

           // Validación del formulario
           (function() {
               'use strict';
               window.addEventListener('load', function() {
                   var forms = document.getElementsByClassName('needs-validation');
                   var validation = Array.prototype.filter.call(forms, function(form) {
                       form.addEventListener('submit', function(event) {
                           // Verificar si se está cambiando la contraseña
                           const passwordField = $('#password');
                           const confirmPasswordField = $('#password_confirmation');
                           
                           if (passwordField.val() !== '' || confirmPasswordField.val() !== '') {
                               if (passwordField.val() !== confirmPasswordField.val()) {
                                   event.preventDefault();
                                   event.stopPropagation();
                                   
                                   // Mostrar error de confirmación
                                   confirmPasswordField.addClass('is-invalid');
                                   confirmPasswordField.parent().next('.invalid-feedback').remove();
                                   confirmPasswordField.parent().after('<div class="invalid-feedback">Las contraseñas no coinciden</div>');
                                   
                                   // Mostrar alerta
                                   Swal.fire({
                                       icon: 'error',
                                       title: 'Error de validación',
                                       text: 'Las contraseñas no coinciden. Por favor, verifica e intenta nuevamente.'
                                   });
                                   
                                   form.classList.add('was-validated');
                                   return;
                               }
                               
                               if (passwordField.val().length < 8) {
                                   event.preventDefault();
                                   event.stopPropagation();
                                   
                                   Swal.fire({
                                       icon: 'error',
                                       title: 'Contraseña muy corta',
                                       text: 'La contraseña debe tener al menos 8 caracteres.'
                                   });
                                   
                                   form.classList.add('was-validated');
                                   return;
                               }
                           }
                           
                           if (form.checkValidity() === false) {
                               event.preventDefault();
                               event.stopPropagation();
                           } else {
                               // Si se está cambiando la contraseña, mostrar confirmación
                               if (passwordField.val() !== '') {
                                   event.preventDefault();
                                   
                                   Swal.fire({
                                       icon: 'warning',
                                       title: '¿Cambiar contraseña?',
                                       text: 'Estás a punto de cambiar la contraseña de este usuario. ¿Estás seguro?',
                                       showCancelButton: true,
                                       confirmButtonColor: '#007bff',
                                       cancelButtonColor: '#6c757d',
                                       confirmButtonText: 'Sí, cambiar',
                                       cancelButtonText: 'Cancelar'
                                   }).then((result) => {
                                       if (result.isConfirmed) {
                                           form.submit();
                                       }
                                   });
                               } else {
                                   // No hay cambio de contraseña, enviar formulario normalmente
                                   form.submit();
                               }
                           }
                           form.classList.add('was-validated');
                       }, false);
                   });
               }, false);
           })();
       });
   </script>
@stop