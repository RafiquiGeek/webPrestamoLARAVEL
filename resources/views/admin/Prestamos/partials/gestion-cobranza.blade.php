<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 text-primary">
        <i class="fas fa-tasks me-2"></i> Registro de Gestiones
    </h5>
    <a href="{{ route('admin.gestiones.create', ['prestamo_id' => $prestamo->id]) }}" class="btn btn-sm btn-success">
        <i class="fas fa-plus me-1"></i> Nueva Gestión
    </a>
</div>
<div class="table-responsive">
    <table class="table table-hover border-0">
        <thead class="bg-light">
            <tr>
                <th>ID</th>
                <th>Fecha Gestión</th>
                <th>Gestión</th>
                <th>Usuario</th>
                <th class="d-none d-lg-table-cell">Observación</th>
                <th class="d-none d-lg-table-cell">Registrado por</th>
                <th>Compromiso</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($gestiones->where('prestamo_id', $prestamo->id) as $gestion)
                <tr>
                    <td>{{ $gestion->id }}</td>
                    <td>{{ \Carbon\Carbon::parse($gestion->created_at)->format('d-m-Y') }}</td>
                    <td>
                        <span class="badge bg-primary rounded-pill px-3">
                            {{ $gestion->estadoGestion->estado }}
                        </span>
                    </td>
                    <td>
                        <span class="">
                            @if($gestion->asesor)
                                <span class="usuario-reg">
                                    {{ $gestion->asesor->name }}
                                </span>
                            @else
                                <span class="usuario-reg">
                                    No registrado
                                </span>
                            @endif
                        </span>
                    </td>
                    <td class="d-none d-lg-table-cell text-muted small">
                        {{ Str::limit($gestion->observaciones, 50) }}
                    </td>
                    <td class="d-none d-lg-table-cell text-muted small">
                        @if($gestion->asesor)
                            <span class="badge bg-info rounded-pill px-2">
                                {{ $gestion->asesor->name ?? 'No disponible' }}
                            </span>
                        @else
                            <span class="badge bg-secondary px-2">No registrado</span>
                        @endif
                    </td>
                    <td>
                        @if($gestion->compromiso)
                            <div class="d-flex flex-column">
                                <div class="mb-1">
                                    <i class="far fa-calendar-alt me-1 text-primary"></i>
                                    {{ \Carbon\Carbon::parse($gestion->compromiso->fecha_compromiso_pago)->format('d-m-Y') }}
                                </div>
                                <div class="small text-muted">
                                    <i class="far fa-clock me-1"></i>
                                    {{ $gestion->compromiso->hora }}
                                </div>
                                <div class="fw-bold text-success mt-1">
                                    S/ {{ number_format($gestion->compromiso->monto, 2) }}
                                </div>
                            </div>
                        @else
                            <span class="badge bg-secondary px-2">Sin compromiso</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="btn-group">
                            <button type="button"
                                    class="btn btn-sm btn-outline-info me-1"
                                    data-bs-toggle="modal"
                                    data-bs-target="#detalleGestionModal{{ $gestion->id }}">
                                <i class="fas fa-eye"></i>
                            </button>
                            <!--a href="{{ route('admin.gestiones.edit', $gestion->id) }}" class="btn btn-sm btn-outline-warning me-1">
                                <i class="fas fa-edit"></i>
                            </a-->
                            <form action="{{ route('admin.gestiones.destroy', $gestion->id) }}"
                                  method="POST"
                                  class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('¿Está seguro de eliminar esta gestión?')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>

                <!-- Modal de detalle -->
                <div class="modal fade" 
                    id="detalleGestionModal{{ $gestion->id }}" 
                    tabindex="-1" 
                    aria-labelledby="detalleGestionLabel{{ $gestion->id }}" 
                    aria-hidden="true">
                    <div class="modal-dialog modal-xl" style="max-width: 950px;">
                        <div class="modal-content" style="border: none; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); overflow: hidden;">
                            
                            <!-- Header del Modal -->
                            <div class="modal-header" style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); border: none; padding: 2rem;">
                                <h5 class="modal-title text-white" id="detalleGestionLabel{{ $gestion->id }}" style="font-size: 1.375rem; font-weight: 600; margin: 0;">
                                    <i class="fas fa-clipboard-list me-2"></i>
                                    Detalle de Gestión #{{ $gestion->id }}
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <!-- Cuerpo del Modal -->
                            <div class="modal-body" style="padding: 2rem; background: #f8fafc;">
                                
                                <!-- Información Principal -->
                                <div class="row g-4 mb-4">
                                    
                                    <!-- Datos de la Gestión -->
                                    <div class="col-lg-6">
                                        <div class="card h-100" style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); transition: all 0.3s ease;">
                                            <div class="card-header" style="background: transparent; border-bottom: 2px solid #4f46e5; padding: 1.5rem 1.5rem 1rem 1.5rem;">
                                                <h6 class="card-title mb-0" style="color: #4f46e5; font-weight: 600; font-size: 1.1rem;">
                                                    <i class="fas fa-tasks me-2"></i>
                                                    Información de la Gestión
                                                </h6>
                                            </div>
                                            <div class="card-body" style="padding: 1.5rem;">
                                                
                                                <!-- ID -->
                                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9;">
                                                    <span style="font-weight: 600; color: #64748b;">ID:</span>
                                                    <span style="font-weight: 500; color: #1e293b;">#{{ $gestion->id }}</span>
                                                </div>

                                                <!-- Fecha -->
                                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9;">
                                                    <span style="font-weight: 600; color: #64748b;">
                                                        <i class="fas fa-calendar me-1"></i>Fecha:
                                                    </span>
                                                    <span style="font-weight: 500; color: #1e293b;">
                                                        {{ \Carbon\Carbon::parse($gestion->created_at)->format('d/m/Y H:i:s') }}
                                                    </span>
                                                </div>

                                                <!-- Estado -->
                                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9;">
                                                    <span style="font-weight: 600; color: #64748b;">
                                                        <i class="fas fa-flag me-1"></i>Estado:
                                                    </span>
                                                    <span class="badge" style="background: #4f46e5; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem;">
                                                        {{ $gestion->estadoGestion->estado }}
                                                    </span>
                                                </div>

                                                <!-- Registrado por -->
                                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0;">
                                                    <span style="font-weight: 600; color: #64748b;">
                                                        <i class="fas fa-user me-1"></i>Registrado por:
                                                    </span>
                                                    @if($gestion->asesor)
                                                        <span class="badge" style="background: #06b6d4; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem;">
                                                            {{ $gestion->asesor->name }}
                                                        </span>
                                                    @else
                                                        <span class="badge" style="background: #64748b; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem;">
                                                            No registrado
                                                        </span>
                                                    @endif
                                                </div>

                                                <!-- Observaciones -->
                                                @if($gestion->observaciones)
                                                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid #f1f5f9;">
                                                    <h6 style="font-weight: 600; color: #1e293b; margin-bottom: 0.75rem;">
                                                        <i class="fas fa-sticky-note me-2"></i>Observaciones:
                                                    </h6>
                                                    <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; border-left: 4px solid #4f46e5;">
                                                        <p style="color: #64748b; margin: 0; line-height: 1.6;">
                                                            {{ $gestion->observaciones }}
                                                        </p>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Datos del Compromiso -->
                                    <div class="col-lg-6">
                                        <div class="card h-100" style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); transition: all 0.3s ease;">
                                            <div class="card-header" style="background: transparent; border-bottom: 2px solid #10b981; padding: 1.5rem 1.5rem 1rem 1.5rem;">
                                                <h6 class="card-title mb-0" style="color: #10b981; font-weight: 600; font-size: 1.1rem;">
                                                    <i class="fas fa-handshake me-2"></i>
                                                    Datos del Compromiso
                                                </h6>
                                            </div>
                                            <div class="card-body" style="padding: 1.5rem;">
                                                
                                                @if ($gestion->compromiso)
                                                    
                                                    <!-- Cliente -->
                                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9;">
                                                        <span style="font-weight: 600; color: #64748b;">
                                                            <i class="fas fa-user me-1"></i>Cliente:
                                                        </span>
                                                        <span style="font-weight: 500; color: #1e293b;">
                                                            {{ optional($gestion->prestamo->cliente->persona)->nombres ?? '' }} 
                                                            {{ optional($gestion->prestamo->cliente->persona)->ape_pat ?? '' }} 
                                                            {{ optional($gestion->prestamo->cliente->persona)->ape_mat ?? '' }}
                                                        </span>
                                                    </div>

                                                    <!-- Fecha de Compromiso -->
                                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9;">
                                                        <span style="font-weight: 600; color: #64748b;">
                                                            <i class="fas fa-calendar-check me-1"></i>Fecha Compromiso:
                                                        </span>
                                                        <span style="font-weight: 500; color: #1e293b;">
                                                            {{ $gestion->compromiso->fecha_compromiso_pago }}
                                                        </span>
                                                    </div>

                                                    <!-- Hora -->
                                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9;">
                                                        <span style="font-weight: 600; color: #64748b;">
                                                            <i class="fas fa-clock me-1"></i>Hora:
                                                        </span>
                                                        <span style="font-weight: 500; color: #1e293b;">
                                                            {{ $gestion->compromiso->hora }}
                                                        </span>
                                                    </div>

                                                    <!-- Monto -->
                                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0;">
                                                        <span style="font-weight: 600; color: #64748b;">
                                                            <i class="fas fa-money-bill me-1"></i>Monto:
                                                        </span>
                                                        <span style="font-weight: 700; color: #10b981; font-size: 1.1rem;">
                                                            S/ {{ number_format($gestion->compromiso->monto, 2) }}
                                                        </span>
                                                    </div>

                                                    <!-- Comentario -->
                                                    @if($gestion->compromiso->comentario)
                                                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid #f1f5f9;">
                                                        <h6 style="font-weight: 600; color: #1e293b; margin-bottom: 0.75rem;">
                                                            <i class="fas fa-comment me-2"></i>Comentario:
                                                        </h6>
                                                        <div style="background: #f0fdf4; padding: 1rem; border-radius: 8px; border-left: 4px solid #10b981;">
                                                            <p style="color: #064e3b; margin: 0; line-height: 1.6;">
                                                                {{ $gestion->compromiso->comentario }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    @endif

                                                @else
                                                    
                                                    <!-- Estado vacío -->
                                                    <div style="text-align: center; padding: 3rem 1rem; color: #64748b;">
                                                        <i class="fas fa-info-circle" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                                                        <h6 style="color: #64748b; font-weight: 500;">Sin Compromiso Registrado</h6>
                                                        <p style="color: #94a3b8; margin: 0; font-size: 0.9rem;">
                                                            No hay información de compromiso para esta gestión
                                                        </p>
                                                    </div>

                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer del Modal -->
                            <div class="modal-footer" style="background: white; border-top: 1px solid #e2e8f0; padding: 1.5rem 2rem;">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" 
                                        style="background: #64748b; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 500; transition: all 0.3s ease;">
                                    <i class="fas fa-times me-1"></i>
                                    Cerrar
                                </button>
                                <a href="{{ route('admin.gestiones.edit', $gestion->id) }}" class="btn btn-primary" 
                                style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 500; text-decoration: none; color: white; transition: all 0.3s ease;">
                                    <i class="fas fa-edit me-1"></i>
                                    Editar Gestión
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="empty-state">
                            <i class="fas fa-tasks text-muted" style="font-size: 48px;"></i>
                            <p class="mt-2 mb-0">No se encontraron gestiones registradas</p>
                            <a href="{{ route('admin.gestiones.create', ['prestamo_id' => $prestamo->id]) }}" class="btn btn-sm btn-primary mt-3">
                                <i class="fas fa-plus me-1"></i> Crear Nueva Gestión
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>