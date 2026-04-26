@extends('layouts.admin')
@section('title', 'Gestión de Cuentas')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-university mr-2"></i>Gestión de Cuentas</h1>
       <ol class="breadcrumb float-sm-right">
           <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
           <li class="breadcrumb-item active">Cuentas</li>
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
   
   <div class="card card-outline card-primary shadow-sm">
       <div class="card-header">
           <div class="d-flex justify-content-between align-items-center">
               <h3 class="card-title">Listado de Cuentas</h3>
               <a href="{{ route('admin.cuentas.create') }}" class="btn btn-primary btn-sm">
                   <i class="fas fa-plus mr-1"></i>Crear Nueva Cuenta
               </a>
           </div>
       </div>
       <div class="card-body p-0">
           <div class="table-responsive">
               @livewire('cuentas.show-cuentas')
           </div>
       </div>
       @if(isset($cuentas) && method_exists($cuentas, 'hasPages') && $cuentas->hasPages())
           <div class="card-footer">
               {{ $cuentas->links() }}
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
                   ¿Está seguro que desea eliminar la cuenta <strong id="cuentaName"></strong>?
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
               modal.find('#cuentaName').text(name);
               modal.find('#deleteForm').attr('action', '{{ route("admin.cuentas.destroy", "") }}/' + id);
           });
           
           // Efecto hover en las filas
           $('.table tbody tr').hover(
               function() { $(this).addClass('bg-light-hover'); },
               function() { $(this).removeClass('bg-light-hover'); }
           );
       });
   </script>
@stop