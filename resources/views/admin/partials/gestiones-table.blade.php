<div class="table-responsive">
    <table class="table table-hover table-striped mb-0" id="tablaGestiones">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Compromiso</th>
                <th>Asesor</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
    @if(isset($gestionesRecientes) && $gestionesRecientes instanceof \Illuminate\Support\Collection)
        @forelse($gestionesRecientes as $gestion)
            <tr data-estado="{{ $gestion->estado_id }}" class="gestion-row">
                <td>
                    {{ $gestion->prestamo->cliente->persona->nombres ?? 'N/A' }} 
                    {{ $gestion->prestamo->cliente->persona->ape_pat ?? '' }}
                </td>
                <td>{{ $gestion->fecha->format('d/m/Y H:i') }}</td>
                <td>
                    <span class="badge badge-{{ $gestion->estadoGestion->calificacion ?? 'secondary' }}">
                        {{ $gestion->estadoGestion->estado ?? 'N/A' }}
                    </span>
                </td>
                <td>
                    @if($gestion->compromiso)
                        <span class="badge badge-warning">
                            S/ {{ number_format($gestion->compromiso->monto, 2) }}
                        </span>
                    @else
                        <span class="badge badge-secondary">No</span>
                    @endif
                </td>
                <td>{{ $gestion->asesor->name ?? 'N/A' }}</td>
                <td>
                    <div class="btn-group">
                        <a href="{{ route('admin.gestiones.show', $gestion->id) }}" 
                           class="btn btn-xs btn-info" title="Ver gestión">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.gestiones.edit', $gestion->id) }}" 
                           class="btn btn-xs btn-primary" title="Editar gestión">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-3">
                    <i class="far fa-clipboard text-info fa-2x mb-2"></i>
                    <p class="text-muted">No hay gestiones recientes registradas.</p>
                </td>
            </tr>
        @endforelse
    @else
        <tr>
            <td colspan="6" class="text-center py-3">
                <i class="far fa-clipboard text-info fa-2x mb-2"></i>
                <p class="text-muted">No hay datos de gestiones disponibles.</p>
            </td>
        </tr>
    @endif
</tbody>
    </table>
</div>