<table class="table table-hover mb-0">
    <thead class="thead-light">
        <tr>
            <th>Cliente</th>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Monto</th>
            <th>Cartera</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @if($compromisos->isEmpty())
            <tr>
                <td colspan="7" class="text-center py-4">
                    <i class="fas fa-info-circle text-info mr-1"></i> No se encontraron compromisos con los filtros seleccionados
                </td>
            </tr>
        @else
            @foreach($compromisos as $compromiso)
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-circle mr-2 bg-primary text-white">
                                {{ substr($compromiso->prestamo->cliente->persona->nombres ?? 'N', 0, 1) }}
                            </div>
                            <div>
                                <span class="font-weight-bold d-block">
                                    {{ $compromiso->prestamo->cliente->persona->nombres ?? 'N/A' }} 
                                    {{ $compromiso->prestamo->cliente->persona->ape_pat ?? '' }} 
                                    {{ $compromiso->prestamo->cliente->persona->ape_mat ?? '' }}
                                </span>
                                @if($compromiso->comentario)
                                    <small class="text-muted text-truncate d-block" style="max-width: 200px;" title="{{ $compromiso->comentario }}">
                                        <i class="fas fa-comment-dots mr-1"></i> {{ $compromiso->comentario }}
                                    </small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="mr-2">
                                <i class="far fa-calendar-alt 
                                    @if($compromiso->vencimiento_status == 'vencido')
                                        text-danger
                                    @elseif($compromiso->vencimiento_status == 'hoy')
                                        text-warning
                                    @elseif($compromiso->vencimiento_status == 'por_vencer')
                                        text-warning
                                    @else
                                        text-success
                                    @endif
                                "></i>
                            </div>
                            <div>
                                <span class="
                                    @if($compromiso->vencimiento_status == 'vencido')
                                        text-vencido
                                    @elseif($compromiso->vencimiento_status == 'hoy')
                                        text-hoy
                                    @elseif($compromiso->vencimiento_status == 'por_vencer')
                                        text-por-vencer
                                    @else
                                        text-en-plazo
                                    @endif
                                ">
                                    {{ \Carbon\Carbon::parse($compromiso->fecha_compromiso_pago)->format('d/m/Y') }}
                                </span>
                                @php
                                    $fechaCompromiso = \Carbon\Carbon::parse($compromiso->fecha_compromiso_pago);
                                    $hoy = \Carbon\Carbon::now();
                                    $diferencia = $hoy->diffInDays($fechaCompromiso, false);
                                @endphp
                                
                                @if($diferencia < 0)
                                    <small class="d-block text-danger">Vencido por {{ abs($diferencia) }} días</small>
                                @elseif($diferencia == 0)
                                    <small class="d-block text-warning">Vence hoy</small>
                                @else
                                    <small class="d-block text-muted">Faltan {{ $diferencia }} días</small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($compromiso->hora)->format('H:i') }}</td>
                    <td>
                        <span class="font-weight-bold">S/ {{ number_format($compromiso->monto, 2) }}</span>
                    </td>
                    <td>
                        <div style="max-width: 200px;">
                            @if($compromiso->jcc_activo)
                                <div class="mb-1">
                                    <span class="badge badge-secondary">JCC</span>
                                    <small class="font-weight-bold">
                                        {{ $compromiso->jcc_activo->persona->nombres ?? 'N/A' }} 
                                        {{ $compromiso->jcc_activo->persona->ape_pat ?? '' }}
                                    </small>
                                </div>
                            @endif
                            
                            @if($compromiso->asesor_activo)
                                <div class="mb-1">
                                    <span class="badge badge-info">Asesor</span>
                                    <small class="font-weight-bold">
                                        {{ $compromiso->asesor_activo->persona->nombres ?? 'N/A' }} 
                                        {{ $compromiso->asesor_activo->persona->ape_pat ?? '' }}
                                    </small>
                                </div>
                            @endif
                            
                            @if($compromiso->analista_activo)
                                <div>
                                    <span class="badge badge-primary">Analista</span>
                                    <small class="font-weight-bold">
                                        {{ $compromiso->analista_activo->persona->nombres ?? 'N/A' }} 
                                        {{ $compromiso->analista_activo->persona->ape_pat ?? '' }}
                                    </small>
                                </div>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if($compromiso->estado == \App\Models\Compromiso::ESTADO_PENDIENTE)
                            <span class="badge badge-warning">Pendiente</span>
                        @elseif($compromiso->estado == \App\Models\Compromiso::ESTADO_PAGADO)
                            <span class="badge badge-success">Pagado</span>
                        @elseif($compromiso->estado == \App\Models\Compromiso::ESTADO_POSTERGADO)
                            <span class="badge badge-danger">Postergado</span>
                        @endif
                    </td>
                    <td>
                        <div class="btn-group">
                            <!-- Crear gestión de seguimiento -->
                            <a href="{{ route('admin.gestiones.create', ['compromiso_id' => $compromiso->id]) }}" 
                               class="btn btn-sm btn-outline-warning" title="Crear gestión de seguimiento">
                                <i class="fas fa-search-plus"></i>
                            </a>
                            
                            <!-- Editar compromiso -->
                            <a href="{{ route('admin.compromisos.edit', $compromiso->id) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            
                            <!-- Eliminar compromiso -->
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="if(confirm('¿Está seguro de eliminar este compromiso?')) { 
                                        document.getElementById('delete-form-{{ $compromiso->id }}').submit(); 
                                    }" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                            <form id="delete-form-{{ $compromiso->id }}" action="{{ route('admin.compromisos.destroy', $compromiso->id) }}" method="POST" style="display: none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>

@if($compromisos->isNotEmpty())
    <div class="d-flex justify-content-center justify-content-sm-between flex-wrap p-3 border-top">
        <div class="text-muted mb-2 mb-sm-0">
            Mostrando {{ $compromisos->count() }} compromiso(s)
        </div>
    </div>
@endif