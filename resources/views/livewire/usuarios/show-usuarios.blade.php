<div>
    <div class="table-responsive">
        @if ($usuarios->count())
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="pl-4" style="cursor: pointer;" wire:click="order('name')">
                            <i class="fas fa-user mr-2"></i>Usuario
                            @if ($sort == 'name')
                                @if ($direction == 'asc')
                                    <i class="fa-solid fa-arrow-down-a-z float-right mt-1"></i>
                                @else
                                    <i class="fa-solid fa-arrow-down-z-a float-right mt-1"></i>
                                @endif
                            @else
                                <i class="fa-solid fa-sort float-right mt-1"></i>
                            @endif
                        </th>
                        <th><i class="fas fa-id-card mr-2"></i>DNI</th>
                        <th><i class="fas fa-envelope mr-2"></i>Email</th>
                        <th><i class="fas fa-user-shield mr-2"></i>Roles</th>
                        <th><i class="fas fa-building mr-2"></i>Sucursal</th>
                        <th class="text-center"><i class="fas fa-toggle-on mr-2"></i>Estado</th>
                        <th class="text-center" width="200"><i class="fas fa-tools mr-2"></i>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usuarios as $usuario)
                        <tr>
                            <td class="pl-4 align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar mr-3">
                                        <div class="avatar-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px; border-radius: 50%; font-weight: bold;">
                                            {{ substr($usuario->nombres ?? $usuario->name, 0, 1) }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold text-dark">
                                            {{ $usuario->nombres ? $usuario->nombres . ' ' . $usuario->ape_pat . ' ' . $usuario->ape_mat : $usuario->name }}
                                        </div>
                                        <small class="text-muted">
                                            @if($usuario->codigo)
                                                Código: {{ $usuario->codigo }}
                                            @else
                                                Sin código asignado
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td class="align-middle">
                                <span class="badge badge-info">{{ $usuario->documento ?? $usuario->name }}</span>
                            </td>
                            <td class="align-middle">
                                @if($usuario->persona?->email)
                                    <a href="mailto:{{ $usuario->persona->email }}" class="text-primary">
                                        <i class="fas fa-envelope mr-1"></i>
                                        {{ $usuario->persona->email }}
                                    </a>
                                @else
                                    <span class="text-muted">Sin email</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if($usuario->roles->count() > 0)
                                    @foreach ($usuario->roles as $role)
                                        <span class="badge badge-primary mr-1 mb-1">
                                            <i class="fas fa-{{ $roleIcons[$role->name] ?? 'user' }} mr-1"></i>
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="badge badge-secondary">Sin roles</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if($usuario->sucursal_nombre)
                                    <span class="badge badge-info">{{ $usuario->sucursal_nombre }}</span>
                                @else
                                    <span class="text-muted">Sin sucursal</span>
                                @endif
                            </td>
                            <td class="align-middle text-center">
                                @if ($usuario->status == 1)
                                    <span class="badge badge-success">
                                        <i class="fas fa-check-circle mr-1"></i>Activo
                                    </span>
                                @else
                                    <span class="badge badge-danger">
                                        <i class="fas fa-times-circle mr-1"></i>Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.usuarios.edit', ['usuario' => $usuario->id]) }}"
                                       class="btn btn-sm btn-outline-primary" 
                                       title="Editar Usuario"
                                       data-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    @if ($usuario->status == 1)
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-warning btn-desactivar"
                                                data-id="{{ $usuario->id }}"
                                                data-name="{{ $usuario->nombres ? $usuario->nombres . ' ' . $usuario->ape_pat . ' ' . $usuario->ape_mat : $usuario->name }}"
                                                title="Desactivar Usuario"
                                                data-toggle="tooltip">
                                            <i class="fas fa-user-slash"></i>
                                        </button>
                                    @else
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-success btn-activar"
                                                data-id="{{ $usuario->id }}"
                                                data-name="{{ $usuario->nombres ? $usuario->nombres . ' ' . $usuario->ape_pat . ' ' . $usuario->ape_mat : $usuario->name }}"
                                                title="Activar Usuario"
                                                data-toggle="tooltip">
                                            <i class="fas fa-user-check"></i>
                                        </button>
                                    @endif
                                    
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger btn-eliminar"
                                            data-id="{{ $usuario->id }}"
                                            data-name="{{ $usuario->nombres ? $usuario->nombres . ' ' . $usuario->ape_pat . ' ' . $usuario->ape_mat : $usuario->name }}"
                                            title="Eliminar Usuario Permanentemente"
                                            data-toggle="tooltip">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-5">
                <div class="empty-state">
                    <i class="fas fa-user-slash fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No se encontraron usuarios</h4>
                    <p class="text-muted mb-4">
                        @if($search)
                            No hay usuarios que coincidan con "<strong>{{ $search }}</strong>"
                        @else
                            No hay usuarios registrados en el sistema
                        @endif
                    </p>
                    @if($search)
                        <button wire:click="$set('search', '')" class="btn btn-outline-primary">
                            <i class="fas fa-eraser mr-2"></i>Limpiar búsqueda
                        </button>
                    @else
                        <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary">
                            <i class="fas fa-user-plus mr-2"></i>Crear primer usuario
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
        // Efecto hover en las filas mejorado
        const rows = document.querySelectorAll('.table tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transition = 'all 0.3s ease';
                this.style.backgroundColor = '#f8f9fa';
                this.style.transform = 'scale(1.005)';
                this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            });
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
                this.style.transform = '';
                this.style.boxShadow = '';
            });
        });
    
        // Configurar botones de eliminar usuario PERMANENTEMENTE
        document.querySelectorAll('.btn-eliminar').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                const userId = button.getAttribute('data-id');
                const userName = button.getAttribute('data-name');
                
                Swal.fire({
                    title: '⚠️ ¡ADVERTENCIA!',
                    html: `<div class="text-left">
                        <p><strong>Esta acción eliminará PERMANENTEMENTE al usuario:</strong></p>
                        <p class="text-primary"><i class="fas fa-user mr-2"></i>${userName}</p>
                        <hr>
                        <p class="text-danger"><i class="fas fa-exclamation-triangle mr-2"></i><strong>Esta acción NO se puede deshacer</strong></p>
                        <p class="text-muted">Se eliminarán todos los datos relacionados con este usuario.</p>
                        <p class="text-info"><i class="fas fa-lightbulb mr-2"></i><em>Tip: Si solo quieres desactivar temporalmente, usa el botón amarillo.</em></p>
                    </div>`,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash mr-2"></i>Sí, eliminar permanentemente',
                    cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
                    reverseButtons: true,
                    focusCancel: true,
                    customClass: {
                        confirmButton: 'btn-danger',
                        cancelButton: 'btn-secondary'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Segundo nivel de confirmación para eliminación
                        Swal.fire({
                            title: 'Confirmación final',
                            text: 'Escribe "ELIMINAR" para confirmar la eliminación permanente',
                            input: 'text',
                            inputPlaceholder: 'Escribe: ELIMINAR',
                            showCancelButton: true,
                            confirmButtonColor: '#dc3545',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Eliminar permanentemente',
                            cancelButtonText: 'Cancelar',
                            inputValidator: (value) => {
                                if (!value || value.toUpperCase() !== 'ELIMINAR') {
                                    return 'Debes escribir "ELIMINAR" para continuar'
                                }
                            }
                        }).then((finalResult) => {
                            if (finalResult.isConfirmed) {
                                Livewire.dispatch('permanentDeleteUser', {userId: userId});
                            }
                        });
                    }
                });
            });
        });

        // Configurar botones de desactivar usuario
        document.querySelectorAll('.btn-desactivar').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                const userId = button.getAttribute('data-id');
                const userName = button.getAttribute('data-name');
                
                Swal.fire({
                    title: '¿Desactivar usuario?',
                    html: `<p>El usuario <strong>${userName}</strong> será desactivado temporalmente.</p>
                           <p class="text-muted">Podrás reactivarlo cuando lo necesites.</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-user-slash mr-2"></i>Desactivar',
                    cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch('deleteUser', {userId: userId});
                    }
                });
            });
        });

        // Configurar botones de activar usuario
        document.querySelectorAll('.btn-activar').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                const userId = button.getAttribute('data-id');
                const userName = button.getAttribute('data-name');
                
                Swal.fire({
                    title: '¿Activar usuario?',
                    html: `<p>El usuario <strong>${userName}</strong> será activado.</p>
                           <p class="text-muted">Podrá acceder nuevamente al sistema.</p>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-user-check mr-2"></i>Activar',
                    cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch('activateUser', {userId: userId});
                    }
                });
            });
        });

        // Escuchar eventos de Livewire para mostrar notificaciones
        Livewire.on('userDeleted', (response) => {
            let data = response[0]; 
            Swal.fire({
                icon: data.icon,
                title: data.title,
                text: data.text,
                showConfirmButton: true,
                timer: 3000
            });
        });

        Livewire.on('userActivated', (response) => {
            let data = response[0]; 
            Swal.fire({
                icon: data.icon,
                title: data.title,
                text: data.text,
                showConfirmButton: true,
                timer: 3000
            });
        });

        Livewire.on('userPermanentlyDeleted', (response) => {
            let data = response[0]; 
            Swal.fire({
                icon: data.icon,
                title: data.title,
                text: data.text,
                showConfirmButton: true,
                timer: 5000
            });
        });
    });
</script>