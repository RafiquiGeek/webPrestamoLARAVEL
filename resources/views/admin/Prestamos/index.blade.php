@extends('layouts.admin')
@section('title', 'Préstamos')

@section('content')
   <div class="container-fluid pt-2 p-0">
       @if (session('info'))
           <div class="alert alert-success alert-dismissible fade show" role="alert">
               <i class="fas fa-check-circle mr-1"></i> {{ session('info') }}
               <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                   <span aria-hidden="true">&times;</span>
               </button>
           </div>
       @endif
       
       <div class="card card-outline card-primary">
           <div class="card-header bg-gradient-primary text-white">
               <div class="row align-items-center">
                   <!-- Título -->
                   <div class="col-md-3">
                       <h3 class="card-title text-black mb-0">
                           <i class="fas fa-file-invoice-dollar me-2 mb-2"></i>
                           Listado de Préstamos
                       </h3>
                   </div>

                   <!-- Controles -->
                   <div class="col-md-12">
                       <div class="row align-items-center justify-content-end g-2">
                            <!-- Búsqueda -->
                            <div class="col-md-9">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fas fa-search text-primary"></i>
                                    </span>
                                    <input 
                                         style="height: 45px;"
                                         type="text"
                                         id="searchInputHeader"
                                         class="form-control border-start-0"
                                         placeholder="Buscar cliente..."
                                         wire:model="search">
                                 </div>
                             </div>

                             <!-- Botón Recalcular Comisiones -->
                             <!--div class="col-md-3">
                                <form action="{{ route('admin.prestamos.recalcular-comisiones') }}" method="POST" onsubmit="return confirm('¿Estás seguro de recalcular las comisiones? Esto actualizará los registros existentes.');">
                                    @csrf
                                    <button type="submit" class="btn btn-info btn-sm w-100 text-white fw-bold" style="height: 45px;">
                                        <i class="fas fa-sync-alt me-1"></i>
                                        Recalcular prestamos
                                    </button>
                                </form>
                             </div-->

                            <!-- Botón Nueva Solicitud -->
                            <div class="col-md-3">
                                <a href="{{ route('admin.solicitudes.create') }}"
                                    class="btn btn-warning btn-sm w-100 text-dark fw-bold" style="height: 45px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-plus-circle me-1"></i>
                                    Nueva Solicitud
                                </a>
                            </div>
                       </div>
                   </div>
               </div>
           </div>
           <div class="card-body p-0">
               @livewire('prestamos.show-prestamos')
           </div>
       </div>
   </div>
@stop

@section('css')
   <style>
       .table th {
           font-weight: 600;
           color: #495057;
       }
       .badge {
           font-size: 90%;
           font-weight: 500;
           padding: 0.35em 0.6em;
       }
       .btn-group .btn {
           margin: 0 2px;
       }
       .table td {
           vertical-align: middle;
       }
   </style>
@stop

@section('js')
   <script>
       console.log('Préstamos cargados correctamente');
   </script>
@stop