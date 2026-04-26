<div class="table-responsive">
    <table class="table table-hover table-striped mb-0" id="tablaCompromisos">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Fecha Compromiso</th>
                <th>Hora</th>
                <th>Monto</th>
                <th>Estado</th>
                <th>Creado por</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($compromisos) && $compromisos instanceof \Illuminate\Support\Collection)
                @forelse($compromisos as $compromiso)
                    <tr data-estado="{{ $compromiso->estado }}" class="compromiso-row">
                        <td>
                            {{ $compromiso->prestamo->cliente->persona->nombres ?? 'N/A' }} 
                            {{ $compromiso->prestamo->cliente->persona->ape_pat ?? '' }}
                        </td>
                        <td>{{ \Carbon\Carbon::parse($compromiso->fecha_compromiso_pago)->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($compromiso->hora)->format('H:i') }}</td>
                        <td>S/ {{ number_format($compromiso->monto, 2) }}</td>
                        <td>
                            @if($compromiso->estado == 0)
                                <span class="badge badge-secondary">Pendiente</span>
                            @elseif($compromiso->estado == 1)
                                <span class="badge badge-success">Completado</span>
                            @elseif($compromiso->estado == 2)
                                <span class="badge badge-danger">Cancelado</span>
                            @endif
                        </td>
                        <td>
                            @if($compromiso->gestion && $compromiso->gestion->asesor)
                                {{ $compromiso->gestion->asesor->name }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.prestamos.show', $compromiso->prestamo_id) }}" 
                                   class="btn btn-xs btn-info" title="Ver préstamo">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.compromisos.edit', $compromiso->id) }}" 
                                   class="btn btn-xs btn-primary" title="Editar compromiso">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-3">
                            <i class="far fa-handshake text-warning fa-2x mb-2"></i>
                            <p class="text-muted">No hay compromisos de pago registrados.</p>
                        </td>
                    </tr>
                @endforelse
            @else
                <tr>
                    <td colspan="7" class="text-center py-3">
                        <i class="far fa-handshake text-warning fa-2x mb-2"></i>
                        <p class="text-muted">No hay datos de compromisos disponibles.</p>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>