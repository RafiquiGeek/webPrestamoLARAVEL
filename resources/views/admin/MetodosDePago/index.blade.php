@extends('layouts.admin')
@section('title', 'Métodos de Pago')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-credit-card mr-2"></i>Métodos de Pago</h1>
       <ol class="breadcrumb float-sm-right">
           <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
           <li class="breadcrumb-item active">Métodos de Pago</li>
       </ol>
   </div>
@stop

@section('content')
   @if(session('success'))
       <div class="alert alert-success alert-dismissible fade show">
           <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
           <button type="button" class="close" data-dismiss="alert" aria-label="Close">
               <span aria-hidden="true">&times;</span>
           </button>
       </div>
   @endif
   
   <div class="card card-outline card-primary shadow-sm">
       <div class="card-header">
           <div class="d-flex justify-content-between align-items-center">
               <h3 class="card-title">Listado de Métodos de Pago</h3>
               <a href="{{ route('admin.metodosdepago.create') }}" class="btn btn-primary btn-sm">
                   <i class="fas fa-plus mr-1"></i>Crear Nuevo Método
               </a>
           </div>
       </div>
       <div class="card-body p-0">
           <div class="table-responsive">
               <table class="table table-striped table-hover mb-0">
                   <thead class="bg-light">
                       <tr>
                           <th class="pl-4" width="60"><i class="fas fa-hashtag mr-1"></i>ID</th>
                           <th><i class="fas fa-money-bill-wave mr-1"></i>Método de Pago</th>
                           <th class="text-center" width="120"><i class="fas fa-toggle-on mr-1"></i>Estado</th>
                           <th class="text-center" width="180"><i class="fas fa-tools mr-1"></i>Acciones</th>
                       </tr>
                   </thead>
                   <tbody>
                       @forelse($metodos as $metodo)
                           <tr>
                               <td class="pl-4 align-middle">{{ $metodo->id }}</td>
                               <td class="align-middle font-weight-medium">{{ $metodo->metodo_pago }}</td>
                               <td class="text-center align-middle">
                                   <span class="badge {{ $metodo->status ? 'badge-success' : 'badge-danger' }}">
                                       {{ $metodo->status ? 'Activo' : 'Inactivo' }}
                                   </span>
                               </td>
                               <td class="text-center">
                                   <div class="btn-group">
                                       <a href="{{ route('admin.metodosdepago.edit', $metodo) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                           <i class="fas fa-edit"></i>
                                       </a>
                                       <button type="button" class="btn btn-sm btn-outline-danger" 
                                               data-toggle="modal" 
                                               data-target="#deleteModal" 
                                               data-id="{{ $metodo->id }}"
                                               data-name="{{ $metodo->metodo_pago }}"
                                               title="Eliminar">
                                           <i class="fas fa-trash"></i>
                                       </button>
                                   </div>
                               </td>
                           </tr>
                       @empty
                           <tr>
                               <td colspan="4" class="text-center py-4 text-muted">
                                   <i class="fas fa-info-circle mr-1"></i>No hay métodos de pago registrados actualmente
                               </td>
                           </tr>
                       @endforelse
                   </tbody>
               </table>
           </div>
       </div>
       @if(isset($metodos) && method_exists($metodos, 'hasPages') && $metodos->hasPages())
           <div class="card-footer">
               {{ $metodos->links() }}
           </div>
       @endif
   </div>

   <!-- Modal de eliminación -->
   <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
       <div class="modal-dialog modal-dialog-centered" role="document">
           <div class="modal-content">
               <div class="modal-header bg-danger text-white">
                   <h5 class="modal-title" id="deleteModalLabel"><i class="fas fa-exclamation-triangle mr-2"></i>Confirmar Eliminación</h5>
                   <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                       <span aria-hidden="true">&times;</span>
                   </button>
               </div>
               <div class="modal-body">
                   ¿Está seguro que desea eliminar el método de pago <strong id="metodoName"></strong>?
                   <p class="mt-2 mb-0 text-muted small">Esta acción no se puede deshacer y podría afectar a operaciones financieras asociadas.</p>
               </div>
               <div class="modal-footer">
                   <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                   <form id="deleteForm" action="" method="POST">
                       @csrf 
                       @method('DELETE')
                       <button type="submit" class="btn btn-danger">Eliminar</button>
                   </form>
               </div>
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
       $(document).ready(function() {
           // Configurar el modal de eliminación
           $('#deleteModal').on('show.bs.modal', function (event) {
               var button = $(event.relatedTarget);
               var id = button.data('id');
               var name = button.data('name');
               
               var modal = $(this);
               modal.find('#metodoName').text(name);
               modal.find('#deleteForm').attr('action', '{{ route("admin.metodosdepago.destroy", "") }}/' + id);
           });
           
           // Efecto hover en las filas
           $('.table tbody tr').hover(
               function() { $(this).addClass('bg-light-hover'); },
               function() { $(this).removeClass('bg-light-hover'); }
           );
       });
   </script>
@stop