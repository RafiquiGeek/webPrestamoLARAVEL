<div>
    {{-- Barra de filtros y acciones --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <input type="text" wire:model.live="busqueda" class="form-control" placeholder="Buscar tareas...">
                </div>
                <div class="col-md-2">
                    <select wire:model.live="filtroEstado" class="form-control">
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="en_progreso">En Progreso</option>
                        <option value="en_revision">En Revisión</option>
                        <option value="pausado">Pausado</option>
                        <option value="completado">Completado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="filtroPrioridad" class="form-control">
                        <option value="">Todas las prioridades</option>
                        <option value="baja">Baja</option>
                        <option value="media">Media</option>
                        <option value="alta">Alta</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
                @if(auth()->user()->hasRole('Admin'))
                <div class="col-md-2">
                    <select wire:model.live="filtroUsuario" class="form-control">
                        <option value="">Todos los usuarios</option>
                        @foreach($this->usuarios as $usuario)
                            <option value="{{ $usuario->id }}">{{ $usuario->codigo }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3 text-right">
                    <button wire:click="abrirCrearTarea" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Tarea
                    </button>
                    @if(auth()->user()->hasRole('Admin'))
                    <button wire:click="abrirModalColumna" class="btn btn-success">
                        <i class="fas fa-columns"></i> Nueva Columna
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Tablero Kanban --}}
    <div class="kanban-board">
        @foreach($this->columnas as $columna)
        <div class="kanban-column">
            <div class="kanban-column-header" style="border-color: {{ $columna->color }}">
                <div class="kanban-column-title" style="color: {{ $columna->color }}">
                    {{ $columna->nombre }}
                    <span class="badge badge-secondary">
                        {{ isset($this->tareas[$columna->id]) ? count($this->tareas[$columna->id]) : 0 }}
                    </span>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-link" data-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="#" wire:click="abrirCrearTarea({{ $columna->id }})">
                            <i class="fas fa-plus"></i> Agregar tarea
                        </a>
                        @if(auth()->user()->hasRole('Admin') && !$columna->es_sistema)
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="#"
                           onclick="confirmarEliminarColumna({{ $columna->id }})">
                            <i class="fas fa-trash"></i> Eliminar columna
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="kanban-cards" data-columna-id="{{ $columna->id }}">
                @if(isset($this->tareas[$columna->id]))
                    @foreach($this->tareas[$columna->id] as $tarea)
                    <div class="kanban-card" data-tarea-id="{{ $tarea->id }}"
                         style="border-color: {{ $tarea->prioridad_color }}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0" style="font-size: 0.95rem;">{{ $tarea->titulo }}</h6>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link p-0" data-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item" href="#" wire:click="verTarea({{ $tarea->id }})">
                                        <i class="fas fa-eye"></i> Ver detalles
                                    </a>
                                    <a class="dropdown-item" href="#" wire:click="editarTarea({{ $tarea->id }})">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    @if($tarea->estado == 'pendiente')
                                    <a class="dropdown-item" href="#" wire:click="iniciarTarea({{ $tarea->id }})">
                                        <i class="fas fa-play"></i> Iniciar
                                    </a>
                                    @endif
                                    @if($tarea->estado == 'en_progreso')
                                    <a class="dropdown-item" href="#" wire:click="enviarARevision({{ $tarea->id }})">
                                        <i class="fas fa-eye"></i> Para Revisión
                                    </a>
                                    @endif
                                    @if($tarea->estado == 'en_revision' && auth()->user()->hasRole('Admin'))
                                    <a class="dropdown-item" href="#" wire:click="aprobarTarea({{ $tarea->id }})">
                                        <i class="fas fa-check-circle"></i> Aprobar
                                    </a>
                                    @endif
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="#"
                                       onclick="confirmarEliminarTarea({{ $tarea->id }})">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </a>
                                </div>
                            </div>
                        </div>

                        @if($tarea->descripcion)
                        <p class="text-muted small mb-2">{{ Str::limit($tarea->descripcion, 100) }}</p>
                        @endif

                        <div class="mb-2">
                            <span class="task-priority priority-{{ $tarea->prioridad }}">
                                {{ $tarea->prioridad }}
                            </span>
                            @if($tarea->esta_vencida)
                            <span class="badge badge-danger ml-1">Vencida</span>
                            @endif
                        </div>

                        @if($tarea->progreso > 0)
                        <div class="progress-bar-custom">
                            <div class="progress-bar-fill bg-success" style="width: {{ $tarea->progreso }}%"></div>
                        </div>
                        @endif

                        @if($tarea->archivos->count() > 0)
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-paperclip"></i> {{ $tarea->archivos->count() }} archivo(s)
                            </small>
                        </div>
                        @endif

                        <div class="task-meta">
                            <div class="d-flex align-items-center">
                                <div class="task-avatar">
                                    {{ substr($tarea->asignadoA->name, 0, 2) }}
                                </div>
                                <small class="ml-2">{{ $tarea->asignadoA->name }}</small>
                            </div>
                            @if($tarea->fecha_vencimiento)
                            <small>
                                <i class="far fa-clock"></i> {{ $tarea->tiempo_restante }}
                            </small>
                            @endif
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>

            <button wire:click="abrirCrearTarea({{ $columna->id }})" class="add-task-btn mt-2">
                <i class="fas fa-plus"></i> Agregar tarea
            </button>
        </div>
        @endforeach
    </div>

    {{-- Modal Crear/Editar Tarea --}}
    @if($showCreateModal || $showEditModal)
    <div class="modal-backdrop fade show"></div>
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ $showEditModal ? 'Editar Tarea' : 'Nueva Tarea' }}
                    </h5>
                    <button type="button" class="close" wire:click="$set('showCreateModal', false); $set('showEditModal', false)" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit="{{ $showEditModal ? 'actualizarTarea' : 'crearTarea' }}">
                        <div class="form-group">
                            <label>Título *</label>
                            <input type="text" wire:model="titulo" class="form-control @error('titulo') is-invalid @enderror">
                            @error('titulo') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea wire:model="descripcion" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Asignar a *</label>
                                    <select wire:model="asignado_a" class="form-control @error('asignado_a') is-invalid @enderror">
                                        <option value="">Seleccionar usuario</option>
                                        @foreach($this->usuarios as $usuario)
                                            <option value="{{ $usuario->id }}">{{ $usuario->codigo }}</option>
                                        @endforeach
                                    </select>
                                    @error('asignado_a') <span class="invalid-feedback">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Prioridad *</label>
                                    <select wire:model="prioridad" class="form-control">
                                        <option value="baja">Baja</option>
                                        <option value="media">Media</option>
                                        <option value="alta">Alta</option>
                                        <option value="urgente">Urgente</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fecha de vencimiento</label>
                                    <input type="datetime-local" wire:model="fecha_vencimiento" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tiempo estimado (horas)</label>
                                    <input type="number" wire:model="tiempo_estimado" class="form-control" min="0.1" step="0.1" placeholder="Ej: 2.5">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Archivos adjuntos</label>
                            <div id="archivos-container">
                                @if($nuevosArchivos)
                                    @foreach($nuevosArchivos as $index => $archivo)
                                        <div class="archivo-item mb-2" wire:key="archivo-{{ $index }}">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <input type="file"
                                                           wire:model="nuevosArchivos.{{ $index }}"
                                                           class="form-control"
                                                           accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                                                </div>
                                                <button type="button"
                                                        class="btn btn-sm btn-danger ml-2"
                                                        wire:click="removerCampoArchivo({{ $index }})"
                                                        @if(count($nuevosArchivos) <= 1) disabled @endif>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            @error("nuevosArchivos.{$index}")
                                                <span class="text-danger small">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            <button type="button"
                                    class="btn btn-sm btn-success mt-2"
                                    wire:click="agregarCampoArchivo">
                                <i class="fas fa-plus"></i> Agregar otro archivo
                            </button>
                            <small class="text-muted d-block mt-1">
                                Formatos soportados: imágenes, PDF, documentos de Office. Máximo 10MB por archivo.
                            </small>
                        </div>

                        <div class="text-right">
                            <button type="button" class="btn btn-secondary"
                                    wire:click="cerrarModales">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                {{ $showEditModal ? 'Actualizar' : 'Crear' }} Tarea
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Ver Detalles de Tarea --}}
    @if($this->tareaSeleccionada && $showViewModal)
    <div class="modal-backdrop fade show"></div>
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $this->tareaSeleccionada->titulo }}</h5>
                    <button type="button" class="close" wire:click="$set('showViewModal', false)">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <strong>Descripción:</strong>
                                <p>{{ $this->tareaSeleccionada->descripcion ?: 'Sin descripción' }}</p>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Estado:</strong>
                                    <span class="badge" style="background-color: {{ $this->tareaSeleccionada->estado_color }}">
                                        {{ ucfirst($this->tareaSeleccionada->estado) }}
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Prioridad:</strong>
                                    <span class="task-priority priority-{{ $this->tareaSeleccionada->prioridad }}">
                                        {{ $this->tareaSeleccionada->prioridad }}
                                    </span>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Asignado por:</strong> {{ $this->tareaSeleccionada->asignadoPor->name }}
                                </div>
                                <div class="col-md-6">
                                    <strong>Asignado a:</strong> {{ $this->tareaSeleccionada->asignadoA->name }}
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Fecha de asignación:</strong>
                                    {{ $this->tareaSeleccionada->fecha_asignacion->format('d/m/Y H:i') }}
                                </div>
                                <div class="col-md-6">
                                    @if($this->tareaSeleccionada->fecha_vencimiento)
                                    <strong>Fecha de vencimiento:</strong>
                                    {{ $this->tareaSeleccionada->fecha_vencimiento->format('d/m/Y H:i') }}
                                    @endif
                                </div>
                            </div>

                            @if($this->tareaSeleccionada->fecha_inicio)
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Fecha de inicio:</strong>
                                    {{ $this->tareaSeleccionada->fecha_inicio->format('d/m/Y H:i') }}
                                </div>
                                <div class="col-md-6">
                                    @if($this->tareaSeleccionada->fecha_completado)
                                    <strong>Fecha completado:</strong>
                                    {{ $this->tareaSeleccionada->fecha_completado->format('d/m/Y H:i') }}
                                    @endif
                                </div>
                            </div>
                            @endif

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    @if($this->tareaSeleccionada->tiempo_estimado)
                                    <strong>Tiempo estimado:</strong>
                                    {{ $this->tareaSeleccionada->tiempo_estimado }} horas
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    @if($this->tareaSeleccionada->tiempo_real)
                                    <strong>Tiempo real:</strong>
                                    {{ $this->tareaSeleccionada->tiempo_real }} horas
                                    @endif
                                </div>
                            </div>

                            @if($this->tareaSeleccionada->progreso > 0)
                            <div class="mb-3">
                                <strong>Progreso:</strong>
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: {{ $this->tareaSeleccionada->progreso }}%">
                                        {{ $this->tareaSeleccionada->progreso }}%
                                    </div>
                                </div>
                            </div>
                            @endif

                            {{-- Archivos adjuntos --}}
                            @if($this->tareaSeleccionada->archivos->count() > 0)
                            <div class="mb-3">
                                <strong>Archivos adjuntos:</strong>
                                <div class="file-preview mt-2">
                                    @foreach($this->tareaSeleccionada->archivos as $archivo)
                                    <div class="file-item">
                                        @if($archivo->es_imagen)
                                        <a href="{{ $archivo->url }}" data-fancybox="gallery"
                                           data-caption="{{ $archivo->nombre_archivo }}">
                                            <img src="{{ $archivo->url }}" alt="{{ $archivo->nombre_archivo }}">
                                        </a>
                                        @else
                                        <a href="{{ $archivo->url }}" target="_blank" class="file-icon">
                                            <i class="fas fa-file fa-2x"></i>
                                        </a>
                                        @endif
                                        <button wire:click="eliminarArchivo({{ $archivo->id }})"
                                                class="btn btn-sm btn-danger position-absolute"
                                                style="top: -5px; right: -5px; padding: 2px 6px;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="col-md-4">
                            {{-- Comentarios --}}
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Comentarios</h6>
                                </div>
                                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                    @forelse($this->tareaSeleccionada->comentarios as $comentario)
                                    <div class="comment-box">
                                        <div class="d-flex justify-content-between">
                                            <span class="comment-author">{{ $comentario->usuario->name }}</span>
                                            <span class="comment-time">
                                                {{ $comentario->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                        <div class="comment-text">{{ $comentario->comentario }}</div>
                                    </div>
                                    @empty
                                    <p class="text-muted">No hay comentarios aún</p>
                                    @endforelse
                                </div>
                                <div class="card-footer">
                                    <div class="input-group">
                                        <input type="text" wire:model="nuevoComentario"
                                               class="form-control" placeholder="Escribir comentario..."
                                               wire:keydown.enter="agregarComentario">
                                        <div class="input-group-append">
                                            <button wire:click="agregarComentario" class="btn btn-primary">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showViewModal', false)">
                        Cerrar
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="editarTarea({{ $this->tareaSeleccionada->id }})">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal Crear Columna --}}
    @if($showColumnModal)
    <div class="modal-backdrop fade show"></div>
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Columna</h5>
                    <button type="button" class="close" wire:click="$set('showColumnModal', false)">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit="crearColumna">
                        <div class="form-group">
                            <label>Nombre de la columna *</label>
                            <input type="text" wire:model="nombreColumna"
                                   class="form-control @error('nombreColumna') is-invalid @enderror">
                            @error('nombreColumna') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label>Color</label>
                            <input type="color" wire:model="colorColumna" class="form-control" style="height: 45px;">
                        </div>

                        <div class="text-right">
                            <button type="button" class="btn btn-secondary" wire:click="$set('showColumnModal', false)">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-success">
                                Crear Columna
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

@push('js')
<script>
    function confirmarEliminarTarea(tareaId) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede revertir",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.eliminarTarea(tareaId);
            }
        });
    }

    function confirmarEliminarColumna(columnaId) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede revertir",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.eliminarColumna(columnaId);
            }
        });
    }
</script>
@endpush