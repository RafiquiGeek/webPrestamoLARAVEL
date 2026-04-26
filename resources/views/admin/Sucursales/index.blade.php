@extends('layouts.admin')
@section('title', 'Sucursales')
@section('content_header')
  <div class="container d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-building mr-2"></i>Gestión de Sucursales</h1>
      <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
          <li class="breadcrumb-item active">Sucursales</li>
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
              <h3 class="card-title">Listado de Sucursales</h3>
              <a href="{{ route('admin.sucursales.create') }}" class="btn btn-primary btn-sm">
                  <i class="fas fa-plus mr-1"></i>Crear Nueva Sucursal
              </a>
          </div>
      </div>
      <div class="card-body p-0">
          <div class="table-responsive">
              <table class="table table-striped table-hover mb-0">
                  <thead class="bg-light">
                      <tr>
                          <th class="pl-4" width="60"><i class="fas fa-hashtag mr-1"></i>ID</th>
                          <th><i class="fas fa-building mr-1"></i>Sucursal</th>
                          <th><i class="fas fa-map-marked-alt mr-1"></i>Departamento</th>
                          <th><i class="fas fa-map-marker-alt mr-1"></i>Provincia</th>
                          <th class="text-center" width="180"><i class="fas fa-tools mr-1"></i>Acciones</th>
                      </tr>
                  </thead>
                  <tbody>
                      @forelse ($sucursales as $sucursal)
                          <tr>
                              <td class="pl-4 align-middle">{{ $sucursal->id }}</td>
                              <td class="align-middle font-weight-medium">{{ $sucursal->sucursal }}</td>
                              <td class="align-middle">{{ $sucursal->provincia->departamento->departamento }}</td>
                              <td class="align-middle">{{ $sucursal->provincia->provincia }}</td>
                              <td class="text-center">
                                  <div class="btn-group">
                                      <a href="{{ route('admin.sucursales.edit', $sucursal->id) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                          <i class="fas fa-edit"></i>
                                      </a>
                                      <button type="button" class="btn btn-sm btn-outline-danger" 
                                              data-toggle="modal" 
                                              data-target="#deleteModal" 
                                              data-id="{{ $sucursal->id }}"
                                              data-name="{{ $sucursal->sucursal }}"
                                              title="Eliminar">
                                          <i class="fas fa-trash"></i>
                                      </button>
                                  </div>
                              </td>
                          </tr>
                      @empty
                          <tr>
                              <td colspan="5" class="text-center py-4 text-muted">
                                  <i class="fas fa-info-circle mr-1"></i>No hay sucursales registradas actualmente
                              </td>
                          </tr>
                      @endforelse
                  </tbody>
              </table>
          </div>
      </div>
      @if($sucursales->hasPages())
          <div class="card-footer bg-light border-top">
              <div class="d-flex justify-content-between align-items-center py-2">
                  <div class="text-muted small">
                      Mostrando {{ $sucursales->firstItem() }} a {{ $sucursales->lastItem() }} de {{ $sucursales->total() }} resultados
                  </div>
                  <div>
                      {{ $sucursales->links('pagination::bootstrap-4') }}
                  </div>
              </div>
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
                  ¿Está seguro que desea eliminar la sucursal <strong id="sucursalName"></strong>?
                  <p class="mt-2 mb-0 text-muted small">Esta acción no se puede deshacer y podría afectar a registros relacionados en el sistema.</p>
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
      
      /* Paginación mejorada */
      .pagination {
          margin-bottom: 0;
          justify-content: center;
      }
      
      .page-item .page-link {
          border-radius: 0.375rem;
          margin: 0 0.125rem;
          padding: 0.375rem 0.75rem;
          color: #495057;
          border: 1px solid #dee2e6;
          background-color: #ffffff;
      }
      
      .page-item .page-link:hover {
          color: #435ebe;
          background-color: #e9ecef;
          border-color: #adb5bd;
      }
      
      .page-item.active .page-link {
          color: #fff;
          background-color: #435ebe;
          border-color: #435ebe;
      }
      
      .page-item.disabled .page-link {
          color: #6c757d;
          background-color: #fff;
          border-color: #dee2e6;
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
              modal.find('#sucursalName').text(name);
              modal.find('#deleteForm').attr('action', '{{ route("admin.sucursales.destroy", "") }}/' + id);
          });
          
          // Efecto hover en las filas
          $('.table tbody tr').hover(
              function() { $(this).addClass('bg-light-hover'); },
              function() { $(this).removeClass('bg-light-hover'); }
          );
      });
  </script>
@stop