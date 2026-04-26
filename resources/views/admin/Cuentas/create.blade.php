@extends('layouts.admin')
@section('title', 'Crear Cuenta')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-plus-circle mr-2"></i>Crear Nueva Cuenta</h1>
       <ol class="breadcrumb float-sm-right">
           <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
           <li class="breadcrumb-item"><a href="{{ route('admin.cuentas.index') }}">Cuentas</a></li>
           <li class="breadcrumb-item active">Crear</li>
       </ol>
   </div>
@stop

@section('content')
   <div class="container-fluid pt-2">
       <div class="card card-outline card-primary shadow-sm">
           <div class="card-header">
               <h3 class="card-title">Información de la Cuenta</h3>
           </div>
           
           <form action="{{ route('admin.cuentas.store') }}" method="POST">
               @csrf
               <div class="card-body">
                   <div class="row">
                       <!-- Entidad Bancaria -->
                       <div class="col-md-4">
                           <div class="form-group">
                               <label for="entidadFinanciera" class="font-weight-bold">
                                   <i class="fas fa-university mr-1 text-gray-600"></i>Entidad Bancaria
                                   <span class="text-danger">*</span>
                               </label>
                               <select class="form-control select2 @error('entidadFinanciera') is-invalid @enderror" 
                                       id="entidadFinanciera" name="entidadFinanciera" required>
                                   <option value="">Selecciona una entidad</option>
                                   @foreach ($entBancarias as $entBancaria)
                                       <option value="{{ $entBancaria->id }}" {{ old('entidadFinanciera') == $entBancaria->id ? 'selected' : '' }}>
                                           {{ $entBancaria->banco }}
                                       </option>
                                   @endforeach
                               </select>
                               @error('entidadFinanciera')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Seleccione la entidad bancaria a la que pertenece la cuenta</small>
                           </div>
                       </div>
                       
                       <!-- Número de Cuenta -->
                       <div class="col-md-5">
                           <div class="form-group">
                               <label for="nro_cuenta" class="font-weight-bold">
                                   <i class="fas fa-hashtag mr-1 text-gray-600"></i>Número de Cuenta
                                   <span class="text-danger">*</span>
                               </label>
                               <div class="input-group">
                                   <div class="input-group-prepend">
                                       <span class="input-group-text bg-light">
                                           <i class="fas fa-sort-numeric-up text-blue"></i>
                                       </span>
                                   </div>
                                   <input type="text" class="form-control @error('nro_cuenta') is-invalid @enderror" 
                                          name="nro_cuenta" id="nro_cuenta" value="{{ old('nro_cuenta') }}" 
                                          placeholder="Ej. 123456789" required>
                                   @error('nro_cuenta')
                                       <span class="invalid-feedback">{{ $message }}</span>
                                   @enderror
                               </div>
                               <small class="form-text text-muted">Ingrese el número de cuenta sin guiones ni espacios</small>
                           </div>
                       </div>
                       
                       <!-- Código -->
                       <div class="col-md-3">
                           <div class="form-group">
                               <label for="codigo" class="font-weight-bold">
                                   <i class="fas fa-key mr-1 text-gray-600"></i>Código
                                   <span class="text-danger">*</span>
                               </label>
                               <div class="input-group">
                                   <div class="input-group-prepend">
                                       <span class="input-group-text bg-light">
                                           <i class="fas fa-code text-blue"></i>
                                       </span>
                                   </div>
                                   <input type="text" class="form-control @error('codigo') is-invalid @enderror" 
                                          name="codigo" id="codigo" value="{{ old('codigo') }}" 
                                          placeholder="Ej. ABC123" required>
                                   @error('codigo')
                                       <span class="invalid-feedback">{{ $message }}</span>
                                   @enderror
                               </div>
                               <small class="form-text text-muted">Ingrese el código único de identificación</small>
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
                               <p class="mb-0">Las cuentas creadas estarán disponibles para ser utilizadas en las transacciones financieras del sistema.</p>
                           </div>
                       </div>
                   </div>
               </div>
               
               <!-- Botones de acción -->
               <div class="card-footer bg-white">
                   <div class="d-flex justify-content-between">
                       <a href="{{ route('admin.cuentas.index') }}" class="btn btn-default">
                           <i class="fas fa-times mr-1"></i>Cancelar
                       </a>
                       <button type="submit" class="btn btn-primary">
                           <i class="fas fa-save mr-1"></i>Guardar Cuenta
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
           $('.select2').select2({
               placeholder: "Selecciona una entidad",
               allowClear: true,
               width: '100%'
           });
       });
   </script>
@stop