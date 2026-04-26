<div class="table-responsive">
    <table class="table table-hover table-striped mb-0" id="tablaPrestamos">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Código</th>
                <th>Monto</th>
                <th>Estado</th>
                <th>Fecha Registro</th>
                <th>Carteras</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($prestamos) && $prestamos instanceof \Illuminate\Support\Collection)
                @forelse($prestamos as $prestamo)
                    <tr data-estado="{{ $prestamo->estado }}" class="prestamo-row">
                        <td>
                            {{ $prestamo->cliente->persona->nombres ?? 'N/A' }} 
                            {{ $prestamo->cliente->persona->ape_pat ?? '' }}
                        </td>
                        <td>{{ $prestamo->codigo }}</td>
                        <td>S/ {{ number_format($prestamo->cantidad_solicitada, 2) }}</td>
                        <td>
                            @if($prestamo->estado == 'Nuevo')
                                <span class="badge badge-primary">Nuevo</span>
                            @elseif($prestamo->estado == 'Aprobado')
                                <span class="badge badge-success">Aprobado</span>
                            @elseif($prestamo->estado == 'Rechazado')
                                <span class="badge badge-danger">Rechazado</span>
                            @elseif($prestamo->estado == 'Evaluacion')
                                <span class="badge badge-warning">En Evaluación</span>
                            @elseif($prestamo->estado == 'Finalizado')
                                <span class="badge badge-secondary">Finalizado</span>
                            @else
                                <span class="badge badge-info">{{ $prestamo->estado }}</span>
                            @endif
                        </td>
                        <td>{{ $prestamo->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="d-flex flex-column">
                                @php
                                    $carteraJcc = $prestamo->carterasJcc()->where('estado', 1)->first();
                                    $carteraAsesor = $prestamo->carterasAsesor()->where('estado', 1)->first();
                                    $carteraAnalista = $prestamo->carterasAnalista()->where('estado', 1)->first();
                                @endphp
                                
                                @if($carteraJcc)
                                    <span class="badge badge-pill badge-light">
                                        JCC: {{ $carteraJcc->jcc->persona->nombres ?? 'N/A' }}
                                    </span>
                                @endif
                                
                                @if($carteraAsesor)
                                    <span class="badge badge-pill badge-light mt-1">
                                        Asesor: {{ $carteraAsesor->asesor->persona->nombres ?? 'N/A' }}
                                    </span>
                                @endif
                                
                                @if($carteraAnalista)
                                    <span class="badge badge-pill badge-light mt-1">
                                        Analista: {{ $carteraAnalista->analista->persona->nombres ?? 'N/A' }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.prestamos.show', $prestamo->id) }}" 
                                   class="btn btn-xs btn-info" title="Ver préstamo">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.prestamos.edit', $prestamo->id) }}" 
                                   class="btn btn-xs btn-primary" title="Editar préstamo">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-3">
                            <i class="far fa-file-alt text-success fa-2x mb-2"></i>
                            <p class="text-muted">No hay préstamos nuevos o recientes.</p>
                        </td>
                    </tr>
                @endforelse
            @else
                <tr>
                    <td colspan="7" class="text-center py-3">
                        <i class="far fa-file-alt text-success fa-2x mb-2"></i>
                        <p class="text-muted">No hay datos de préstamos disponibles.</p>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>