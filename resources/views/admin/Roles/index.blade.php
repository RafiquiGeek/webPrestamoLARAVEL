@extends('layouts.admin')
@section('title', 'Roles')
@section('content_header')
  <div class="container d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-users-cog mr-2"></i>Gestión de Roles</h1>
      <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
          <li class="breadcrumb-item active">Roles</li>
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
  
  @if (session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
          </button>
      </div>
  @endif
  
  <div class="container card card-outline card-primary shadow-sm">
      <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
              <h3 class="card-title">Listado de Roles</h3>
              <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">
                  <i class="fas fa-plus mr-1"></i>Crear Nuevo Rol
              </a>
          </div>
      </div>
      <div class="card-body p-0">
          <div class="table-responsive">
              <table class="table table-striped table-hover mb-0">
                  <thead class="bg-light">
                      <tr>
                          <th class="pl-4" width="60"><i class="fas fa-hashtag mr-1"></i>ID</th>
                          <th><i class="fas fa-user-tag mr-1"></i>Nombre del Rol</th>
                          <th class="text-center" width="220"><i class="fas fa-tools mr-1"></i>Acciones</th>
                      </tr>
                  </thead>
                  <tbody>
                      @forelse ($roles as $role)
                          <tr>
                              <td class="pl-4 align-middle">{{ $role->id }}</td>
                              <td class="align-middle font-weight-medium">{{ $role->name }}</td>
                              <td class="text-center">
                                  <div class="btn-group">
                                      <a href="{{ route('admin.roles.permissions', $role) }}" class="btn btn-sm btn-outline-success" title="Gestionar Permisos">
                                          <i class="fas fa-key"></i>
                                      </a>
                                      <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                          <i class="fas fa-edit"></i>
                                      </a>
                                      <button type="button" class="btn btn-sm btn-outline-danger" 
                                              data-toggle="modal" 
                                              data-target="#deleteModal" 
                                              data-id="{{ $role->id }}"
                                              data-name="{{ $role->name }}"
                                              title="Eliminar">
                                          <i class="fas fa-trash"></i>
                                      </button>
                                  </div>
                              </td>
                          </tr>
                      @empty
                          <tr>
                              <td colspan="3" class="text-center py-4 text-muted">
                                  <i class="fas fa-info-circle mr-1"></i>No hay roles registrados actualmente
                              </td>
                          </tr>
                      @endforelse
                  </tbody>
              </table>
          </div>
      </div>
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
                  ¿Está seguro que desea eliminar el rol <strong id="roleName"></strong>?
                  <p class="mt-2 mb-0 text-muted small">Esta acción no se puede deshacer y podría afectar a los usuarios asignados a este rol.</p>
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
              modal.find('#roleName').text(name);
              modal.find('#deleteForm').attr('action', '{{ route("admin.roles.destroy", "") }}/' + id);
          });
          
          // Efecto hover en las filas
          $('.table tbody tr').hover(
              function() { $(this).addClass('bg-light-hover'); },
              function() { $(this).removeClass('bg-light-hover'); }
          );
      });
  </script>
@stop