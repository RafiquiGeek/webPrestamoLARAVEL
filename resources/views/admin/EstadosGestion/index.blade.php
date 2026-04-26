@extends('layouts.admin')
@section('title', 'Gestión de Estados')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-tasks mr-2"></i>Estados de Gestión</h1>
       <ol class="breadcrumb float-sm-right">
           <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
           <li class="breadcrumb-item active">Estados de Gestión</li>
       </ol>
   </div>
@stop

@section('content')
   @if(session('success'))
       <div class="alert alert-success alert-dismissible fade show" role="alert">
           <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
           <button type="button" class="close" data-dismiss="alert" aria-label="Close">
               <span aria-hidden="true">&times;</span>
           </button>
       </div>
   @endif
   
   <div class="card card-outline card-primary shadow-sm">
       <div class="card-header">
           <div class="d-flex justify-content-between align-items-center">
               <h3 class="card-title">Listado de Estados de Gestión</h3>
               <a href="{{ route('admin.estadosgestion.create') }}" class="btn btn-primary btn-sm">
                   <i class="fas fa-plus mr-1"></i>Crear Nuevo Estado
               </a>
           </div>
       </div>
       <div class="card-body p-0">
           <div class="table-responsive">
               <table class="table table-striped table-hover mb-0">
                   <thead class="bg-light">
                       <tr>
                           <th class="pl-4" width="60"><i class="fas fa-hashtag mr-1"></i>ID</th>
                           <th><i class="fas fa-bookmark mr-1"></i>Estado</th>
                           <th class="text-center" width="140"><i class="fas fa-star mr-1"></i>Calificación</th>
                           <th class="text-center" width="180"><i class="fas fa-tools mr-1"></i>Acciones</th>
                       </tr>
                   </thead>
                   <tbody>
                       @forelse($estados as $estado)
                           <tr>
                               <td class="pl-4 align-middle">{{ $estado->id }}</td>
                               <td class="align-middle font-weight-medium">{{ $estado->estado }}</td>
                               <td class="text-center align-middle">
                                   @if($estado->calificacion === 1)
                                       <span class="badge badge-success">Bueno</span>
                                   @elseif($estado->calificacion === 0)
                                       <span class="badge badge-danger">Malo</span>
                                   @else
                                       <span class="badge badge-secondary">N/A</span>
                                   @endif
                               </td>
                               <td class="text-center">
                                   <div class="btn-group">
                                       <a href="{{ route('admin.estadosgestion.edit', $estado) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                           <i class="fas fa-edit"></i>
                                       </a>
                                       <button type="button" class="btn btn-sm btn-outline-danger" 
                                               data-toggle="modal" 
                                               data-target="#deleteModal" 
                                               data-id="{{ $estado->id }}"
                                               data-name="{{ $estado->estado }}"
                                               title="Eliminar">
                                           <i class="fas fa-trash"></i>
                                       </button>
                                   </div>
                               </td>
                           </tr>
                       @empty
                           <tr>
                               <td colspan="4" class="text-center py-4 text-muted">
                                   <i class="fas fa-info-circle mr-1"></i>No hay estados de gestión registrados actualmente
                               </td>
                           </tr>
                       @endforelse
                   </tbody>
               </table>
           </div>
       </div>
       @if(isset($estados) && method_exists($estados, 'hasPages') && $estados->hasPages())
           <div class="card-footer">
               {{ $estados->links() }}
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
                   ¿Está seguro que desea eliminar el estado <strong id="estadoName"></strong>?
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
               modal.find('#estadoName').text(name);
               modal.find('#deleteForm').attr('action', '{{ route("admin.estadosgestion.destroy", "") }}/' + id);
           });
           
           // Efecto hover en las filas
           $('.table tbody tr').hover(
               function() { $(this).addClass('bg-light-hover'); },
               function() { $(this).removeClass('bg-light-hover'); }
           );
       });
   </script>
@stop