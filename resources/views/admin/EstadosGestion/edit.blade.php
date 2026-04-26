@extends('layouts.admin')
@section('title', 'Editar Estado de Gestión')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-edit mr-2"></i>Editar Estado de Gestión</h1>
       <ol class="breadcrumb float-sm-right">
           <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
           <li class="breadcrumb-item"><a href="{{ route('admin.estadosgestion.index') }}">Estados de Gestión</a></li>
           <li class="breadcrumb-item active">Editar</li>
       </ol>
   </div>
@stop

@section('content')
   <div class="container-fluid pt-2">
       <div class="card card-outline card-primary shadow-sm">
           <div class="card-header">
               <h3 class="card-title">Modificar Estado de Gestión</h3>
           </div>
           
           <form action="{{ route('admin.estadosgestion.update', $estadosgestion) }}" method="POST">
               @csrf
               @method('PUT')
               <div class="card-body">
                   <div class="row">
                       <!-- Campo para el estado -->
                       <div class="col-md-12">
                           <div class="form-group">
                               <label for="estado" class="font-weight-bold">
                                   <i class="fas fa-bookmark mr-1 text-gray-600"></i>Estado
                                   <span class="text-danger">*</span>
                               </label>
                               <div class="input-group">
                                   <div class="input-group-prepend">
                                       <span class="input-group-text bg-light">
                                           <i class="fas fa-pencil-alt text-blue"></i>
                                       </span>
                                   </div>
                                   <input type="text" name="estado" id="estado" class="form-control @error('estado') is-invalid @enderror"
                                          value="{{ old('estado', $estadosgestion->estado) }}" placeholder="Ej. En Proceso" required>
                                   @error('estado')
                                       <span class="invalid-feedback">{{ $message }}</span>
                                   @enderror
                               </div>
                               <small class="form-text text-muted">Nombre del estado que se mostrará en el sistema</small>
                           </div>
                       </div>
                       
                       <!-- Campo para la calificación -->
                       <div class="col-md-12">
                           <div class="form-group">
                               <label for="calificacion" class="font-weight-bold">
                                   <i class="fas fa-star mr-1 text-gray-600"></i>Calificación
                                   <span class="text-danger">*</span>
                               </label>
                               <select name="calificacion" id="calificacion" class="form-control @error('calificacion') is-invalid @enderror" required>
                                   <option value="" disabled {{ old('calificacion', $estadosgestion->calificacion) === null ? 'selected' : '' }}>
                                       Selecciona una opción
                                   </option>
                                   <option value="1" {{ old('calificacion', $estadosgestion->calificacion) == 1 ? 'selected' : '' }}>Bueno</option>
                                   <option value="0" {{ old('calificacion', $estadosgestion->calificacion) === 0 ? 'selected' : '' }}>Malo</option>
                               </select>
                               @error('calificacion')
                                   <span class="invalid-feedback">{{ $message }}</span>
                               @enderror
                               <small class="form-text text-muted">Determina si este estado representa una valoración positiva o negativa</small>
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
                               <p class="mb-0">Los estados de gestión ayudan a organizar y clasificar el seguimiento de las operaciones financieras.</p>
                           </div>
                       </div>
                   </div>
               </div>
               
               <!-- Botones de acción -->
               <div class="card-footer bg-white">
                   <div class="d-flex justify-content-between">
                       <a href="{{ route('admin.estadosgestion.index') }}" class="btn btn-default">
                           <i class="fas fa-times mr-1"></i>Cancelar
                       </a>
                       <button type="submit" class="btn btn-primary">
                           <i class="fas fa-save mr-1"></i>Actualizar Estado
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