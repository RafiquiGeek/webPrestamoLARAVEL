@extends('layouts.admin')
@section('title', 'Editar Método de Pago')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-edit mr-2"></i>Editar Método de Pago</h1>
       <ol class="breadcrumb float-sm-right">
           <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
           <li class="breadcrumb-item"><a href="{{ route('admin.metodosdepago.index') }}">Métodos de Pago</a></li>
           <li class="breadcrumb-item active">Editar</li>
       </ol>
   </div>
@stop

@section('content')
   <div class="container-fluid pt-2">
       <div class="card card-outline card-primary shadow-sm">
           <div class="card-header">
               <h3 class="card-title">Modificar Método de Pago</h3>
           </div>
           
           <form action="{{ route('admin.metodosdepago.update', $metodoDePago) }}" method="POST">
               @csrf
               @method('PUT')
               <div class="card-body">
                   <div class="row">
                       <!-- Nombre del método de pago -->
                       <div class="col-md-12">
                           <div class="form-group">
                               <label for="metodo_pago" class="font-weight-bold">
                                   <i class="fas fa-money-bill-wave mr-1 text-gray-600"></i>Método de Pago
                                   <span class="text-danger">*</span>
                               </label>
                               <div class="input-group">
                                   <div class="input-group-prepend">
                                       <span class="input-group-text bg-light">
                                           <i class="fas fa-pencil-alt text-blue"></i>
                                       </span>
                                   </div>
                                   <input type="text" class="form-control @error('metodo_pago') is-invalid @enderror" 
                                          name="metodo_pago" id="metodo_pago" value="{{ old('metodo_pago', $metodoDePago->metodo_pago) }}" required>
                                   @error('metodo_pago')
                                       <span class="invalid-feedback">{{ $message }}</span>
                                   @enderror
                               </div>
                               <small class="form-text text-muted">Nombre del método de pago que se mostrará en el sistema</small>
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
                                   <option value="1" {{ old('status', $metodoDePago->status) ? 'selected' : '' }}>Activo</option>
                                   <option value="0" {{ old('status', $metodoDePago->status) ? '' : 'selected' }}>Inactivo</option>
                               </select>
                               @error('status')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Determina si este método de pago estará disponible para su uso</small>
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
                               <p class="mb-0">Los cambios en los métodos de pago afectarán a las futuras transacciones financieras.</p>
                           </div>
                       </div>
                   </div>
               </div>
               
               <!-- Botones de acción -->
               <div class="card-footer bg-white">
                   <div class="d-flex justify-content-between">
                       <a href="{{ route('admin.metodosdepago.index') }}" class="btn btn-default">
                           <i class="fas fa-times mr-1"></i>Cancelar
                       </a>
                       <button type="submit" class="btn btn-primary">
                           <i class="fas fa-save mr-1"></i>Actualizar Método
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