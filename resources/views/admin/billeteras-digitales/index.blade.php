@extends('layouts.admin')
@section('title', 'Gestión de Billeteras Digitales')
@section('content_header')
  <div class="d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-mobile-alt mr-2"></i>Gestión de Billeteras Digitales</h1>
      <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
          <li class="breadcrumb-item active">Billeteras Digitales</li>
      </ol>
  </div>
@stop

@section('content')
  @if (session('success'))
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
              <h3 class="card-title">Listado de Billeteras Digitales</h3>
              <a href="{{ route('admin.billeteras-digitales.create') }}" class="btn btn-primary btn-sm">
                  <i class="fas fa-plus mr-1"></i>Crear Nueva Billetera
              </a>
          </div>
      </div>
      <div class="card-body p-0">
          <div class="table-responsive">
              <table class="table table-striped table-hover mb-0">
                  <thead class="bg-light">
                      <tr>
                          <th class="pl-4" width="60"><i class="fas fa-hashtag mr-1"></i>ID</th>
                          <th><i class="fas fa-mobile-alt mr-1"></i>Nombre</th>
                          <th class="text-center" width="120"><i class="fas fa-toggle-on mr-1"></i>Estado</th>
                          <th class="text-center" width="180"><i class="fas fa-tools mr-1"></i>Acciones</th>
                      </tr>
                  </thead>
                  <tbody>
                      @forelse($billeteras as $billetera)
                          <tr>
                              <td class="pl-4 align-middle">{{ $billetera->id }}</td>
                              <td class="align-middle font-weight-medium">{{ $billetera->nombre }}</td>
                              <td class="text-center align-middle">
                                  <span class="badge {{ $billetera->status ? 'badge-success' : 'badge-danger' }}">
                                      {{ $billetera->status ? 'Activo' : 'Inactivo' }}
                                  </span>
                              </td>
                              <td class="text-center">
                                  <div class="btn-group">
                                      <a href="{{ route('admin.billeteras-digitales.edit', $billetera) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                          <i class="fas fa-edit"></i>
                                      </a>
                                      <button type="button" class="btn btn-sm btn-outline-danger" 
                                              data-toggle="modal" 
                                              data-target="#deleteModal" 
                                              data-id="{{ $billetera->id }}"
                                              data-name="{{ $billetera->nombre }}"
                                              title="Eliminar">
                                          <i class="fas fa-trash"></i>
                                      </button>
                                  </div>
                              </td>
                          </tr>
                      @empty
                          <tr>
                              <td colspan="4" class="text-center py-4 text-muted">
                                  <i class="fas fa-info-circle mr-1"></i>No hay billeteras digitales registradas actualmente
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
                  ¿Está seguro que desea eliminar la billetera digital <strong id="billeteraName"></strong>?
                  <p class="mt-2 mb-0 text-muted small">Esta acción no se puede deshacer y podría afectar a cuentas de clientes asociadas.</p>
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
              modal.find('#billeteraName').text(name);
              modal.find('#deleteForm').attr('action', '{{ route("admin.billeteras-digitales.destroy", "") }}/' + id);
          });
          
          // Efecto hover en las filas
          $('.table tbody tr').hover(
              function() { $(this).addClass('bg-light-hover'); },
              function() { $(this).removeClass('bg-light-hover'); }
          );
      });
  </script>
@stop