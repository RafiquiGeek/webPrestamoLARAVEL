@extends('layouts.admin')
@section('title', 'Zonas')
@section('content_header')
   <div class="container d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-map-signs mr-2"></i>Gestión de Zonas</h1>
       <ol class="breadcrumb float-sm-right">
           <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
           <li class="breadcrumb-item active">Zonas</li>
       </ol>
   </div>
@stop

@section('content')
   @if (session('info'))
       <div class="alert alert-success alert-dismissible fade show" role="alert">
           <i class="fas fa-check-circle mr-1"></i> {{ session('info') }}
           <button type="button" class="close" data-dismiss="alert" aria-label="Close">
               <span aria-hidden="true">&times;</span>
           </button>
       </div>
   @endif
   
   <div class="container card card-outline card-primary shadow-sm">
       <div class="card-header">
           <div class="d-flex justify-content-between align-items-center">
               <h3 class="card-title">Listado de Zonas</h3>
               <a href="{{ route('admin.zonas.create') }}" class="btn btn-primary btn-sm">
                   <i class="fas fa-plus mr-1"></i>Crear Nueva Zona
               </a>
           </div>
       </div>
       <div class="card-body p-0">
           <div class="table-responsive">
               <table class="table table-striped table-hover mb-0">
                   <thead class="bg-light">
                       <tr>
                           <th class="pl-4" width="60"><i class="fas fa-hashtag mr-1"></i>ID</th>
                           <th><i class="fas fa-map-marked-alt mr-1"></i>Nombre de la Zona</th>
                           <th><i class="fas fa-building mr-1"></i>Sucursales Asociadas</th>
                           <th class="text-center" width="180"><i class="fas fa-tools mr-1"></i>Acciones</th>
                       </tr>
                   </thead>
                   <tbody>
                       @forelse($zonas as $zona)
                           <tr>
                               <td class="pl-4 align-middle">{{ $zona->id }}</td>
                               <td class="align-middle font-weight-medium">{{ $zona->nombre }}</td>
                               <td class="align-middle">
                                   @foreach($zona->sucursales as $sucursal)
                                       <span class="badge badge-info">{{ $sucursal->sucursal }}</span>
                                   @endforeach
                               </td>
                               <td class="text-center">
                                   <div class="btn-group">
                                       <a href="{{ route('admin.zonas.edit', $zona->id) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                           <i class="fas fa-edit"></i>
                                       </a>
                                       <button type="button" class="btn btn-sm btn-outline-danger" 
                                               data-toggle="modal" 
                                               data-target="#deleteModal" 
                                               data-id="{{ $zona->id }}"
                                               data-name="{{ $zona->nombre }}"
                                               title="Eliminar">
                                           <i class="fas fa-trash"></i>
                                       </button>
                                   </div>
                               </td>
                           </tr>
                       @empty
                           <tr>
                               <td colspan="4" class="text-center py-4 text-muted">
                                   <i class="fas fa-info-circle mr-1"></i>No hay zonas registradas actualmente
                               </td>
                           </tr>
                       @endforelse
                   </tbody>
               </table>
           </div>
       </div>
       @if($zonas->hasPages())
           <div class="card-footer">
               {{ $zonas->links() }}
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
                   ¿Está seguro que desea eliminar la zona <strong id="zonaName"></strong>?
                   <p class="mt-2 mb-0 text-muted small">Esta acción no se puede deshacer y podría afectar a las sucursales asociadas.</p>
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
               modal.find('#zonaName').text(name);
               modal.find('#deleteForm').attr('action', '{{ route("admin.zonas.destroy", "") }}/' + id);
           });
           
           // Efecto hover en las filas
           $('.table tbody tr').hover(
               function() { $(this).addClass('bg-light-hover'); },
               function() { $(this).removeClass('bg-light-hover'); }
           );
       });
   </script>
@stop