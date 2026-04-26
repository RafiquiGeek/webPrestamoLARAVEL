@extends('layouts.admin')

@section('title', 'Códigos de Acceso')

@section('content_header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-key text-primary mr-2"></i>Códigos de Acceso</h1>
                    <p class="text-muted">Gestiona códigos de acceso para empleados</p>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.asistencia.index') }}">Asistencia</a></li>
                        <li class="breadcrumb-item active">Códigos de Acceso</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-list mr-2"></i>Lista de Códigos
                        </h3>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createCodeModal">
                            <i class="fas fa-plus mr-2"></i>Nuevo Código
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="codigosTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Código</th>
                                        <th>Descripción</th>
                                        <th>Estado</th>
                                        <th>Usos</th>
                                        <th>Expira</th>
                                        <th>Creado por</th>
                                        <th>Fecha creación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($codes as $code)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <code class="badge badge-dark p-2">{{ $code->code }}</code>
                                            </td>
                                            <td>{{ $code->description ?? 'Sin descripción' }}</td>
                                            <td>
                                                <span class="badge badge-{{ $code->status_color }}">
                                                    {{ $code->status }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $code->usage_count }}
                                                @if($code->max_usage)
                                                    / {{ $code->max_usage }}
                                                @endif
                                            </td>
                                            <td>
                                                @if($code->expires_at)
                                                    {{ $code->expires_at->format('d/m/Y H:i') }}
                                                @else
                                                    <span class="text-muted">Sin vencimiento</span>
                                                @endif
                                            </td>
                                            <td>{{ $code->creator->name ?? 'N/A' }}</td>
                                            <td>{{ $code->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-info" onclick="editCode({{ $code->id }})" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-warning" onclick="toggleStatus({{ $code->id }}, {{ $code->is_active ? 'false' : 'true' }})" title="{{ $code->is_active ? 'Desactivar' : 'Activar' }}">
                                                        <i class="fas fa-{{ $code->is_active ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteCode({{ $code->id }})" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                <i class="fas fa-key fa-2x mb-3 d-block"></i>
                                                No hay códigos de acceso registrados
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar Código -->
    <div class="modal fade" id="createCodeModal" tabindex="-1" role="dialog" aria-labelledby="createCodeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createCodeModalLabel">
                        <i class="fas fa-plus mr-2"></i><span id="modalTitle">Nuevo Código de Acceso</span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="codeForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="code">
                                        <i class="fas fa-key mr-1"></i>Código de Acceso
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="code" name="code" maxlength="10" style="font-family: monospace; letter-spacing: 1px;" placeholder="Ej: ABC123">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" onclick="generateRandomCode()" title="Generar código aleatorio">
                                                <i class="fas fa-random"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">6-10 caracteres alfanumóricos</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="description">
                                        <i class="fas fa-info-circle mr-1"></i>Descripción
                                    </label>
                                    <input type="text" class="form-control" id="description" name="description" placeholder="Ej: Código para nuevos empleados">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expires_at">
                                        <i class="fas fa-calendar mr-1"></i>Fecha de expiración
                                    </label>
                                    <input type="datetime-local" class="form-control" id="expires_at" name="expires_at">
                                    <small class="form-text text-muted">Opcional - dójalo vacóo para que no expire</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="max_usage">
                                        <i class="fas fa-sort-numeric-up mr-1"></i>Móximo de usos
                                    </label>
                                    <input type="number" class="form-control" id="max_usage" name="max_usage" min="1" placeholder="Ej: 10">
                                    <small class="form-text text-muted">Opcional - lómite de veces que se puede usar</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-users mr-1"></i>Roles permitidos
                            </label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="role_admin" name="allowed_roles[]" value="Admin">
                                        <label class="custom-control-label" for="role_admin">Administrador</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="role_supervisor" name="allowed_roles[]" value="Supervisor">
                                        <label class="custom-control-label" for="role_supervisor">Supervisor</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="role_jcc" name="allowed_roles[]" value="JCC">
                                        <label class="custom-control-label" for="role_jcc">JCC</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="role_asesor" name="allowed_roles[]" value="Asesor">
                                        <label class="custom-control-label" for="role_asesor">Asesor</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="role_analista" name="allowed_roles[]" value="Analista">
                                        <label class="custom-control-label" for="role_analista">Analista</label>
                                    </div>
                                </div>
                            </div>
                            <small class="form-text text-muted">Si no seleccionas ningón rol, el código funcionaró para cualquier usuario</small>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                                <label class="custom-control-label" for="is_active">
                                    <i class="fas fa-toggle-on mr-1"></i>Código activo
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i><span id="submitText">Guardar Código</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let editingId = null;

    $(document).ready(function() {
        $('#codigosTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            responsive: true,
            order: [[7, 'desc']]
        });
    });

    function generateRandomCode() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let code = '';
        for (let i = 0; i < 6; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        $('#code').val(code);
    }

    function editCode(id) {
        editingId = id;
        $('#modalTitle').text('Editar Código de Acceso');
        $('#submitText').text('Actualizar Código');
        
        $.get(`/admin/asistencia/codigos/${id}`, function(data) {
            $('#code').val(data.code);
            $('#description').val(data.description);
            $('#expires_at').val(data.expires_at ? data.expires_at.slice(0, 16) : '');
            $('#max_usage').val(data.max_usage);
            $('#is_active').prop('checked', data.is_active);
            
            $('input[name="allowed_roles[]"]').prop('checked', false);
            if (data.allowed_roles) {
                data.allowed_roles.forEach(role => {
                    $(`#role_${role.toLowerCase()}`).prop('checked', true);
                });
            }
            
            $('#createCodeModal').modal('show');
        }).fail(function() {
            toastr.error('Error al cargar los datos del código');
        });
    }

    function toggleStatus(id, status) {
        const action = status === 'true' ? 'activar' : 'desactivar';
        
        Swal.fire({
            title: 'óEstó seguro?',
            text: `óDesea ${action} este código de acceso?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Só, ' + action,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/asistencia/codigos/${id}/toggle`,
                    method: 'PATCH',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        is_active: status
                    },
                    success: function() {
                        location.reload();
                        toastr.success(`Código ${action}do exitosamente`);
                    },
                    error: function() {
                        toastr.error('Error al cambiar el estado del código');
                    }
                });
            }
        });
    }

    function deleteCode(id) {
        Swal.fire({
            title: 'óEstó seguro?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Só, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/asistencia/codigos/${id}`,
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function() {
                        location.reload();
                        toastr.success('Código eliminado exitosamente');
                    },
                    error: function() {
                        toastr.error('Error al eliminar el código');
                    }
                });
            }
        });
    }

    $('#createCodeModal').on('hidden.bs.modal', function() {
        editingId = null;
        $('#modalTitle').text('Nuevo Código de Acceso');
        $('#submitText').text('Guardar Código');
        $('#codeForm')[0].reset();
        $('#is_active').prop('checked', true);
        $('input[name="allowed_roles[]"]').prop('checked', false);
    });

    $('#codeForm').on('submit', function(e) {
        e.preventDefault();
        
        const url = editingId ? `/admin/asistencia/codigos/${editingId}` : '/admin/asistencia/codigos';
        const method = editingId ? 'PUT' : 'POST';
        
        if (editingId) {
            $(this).append('<input type="hidden" name="_method" value="PUT">');
        }
        
        $.ajax({
            url: url,
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                $('#createCodeModal').modal('hide');
                location.reload();
                toastr.success(response.message || 'Código guardado exitosamente');
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMsg = 'Errores de validación:\n';
                    for (const field in errors) {
                        errorMsg += `- ${errors[field][0]}\n`;
                    }
                    toastr.error(errorMsg);
                } else {
                    toastr.error('Error al guardar el código');
                }
            }
        });
    });
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
@endpush