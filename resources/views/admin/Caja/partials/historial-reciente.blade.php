@if($rendiciones->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th width="80">ID</th>
                    <th>Fecha/Hora</th>
                    <th>Tipo</th>
                    <th>Usuario</th>
                    <th class="text-end">Monto</th>
                    <th class="text-center" width="80">PDF</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rendiciones as $rendicion)
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark">
                                {{ is_numeric($rendicion->id) ? '#' . $rendicion->id : 'F-' . substr($rendicion->id, -6) }}
                            </span>
                        </td>
                        <td>
                            <div class="info-value small">
                                {{ \Carbon\Carbon::parse($rendicion->fecha_rendicion)->format('d/m/Y') }}
                            </div>
                            <small class="text-muted">
                                {{ \Carbon\Carbon::parse($rendicion->fecha_rendicion)->format('H:i') }}
                            </small>
                        </td>
                        <td>
                            @php
                                $tipoConfig = [
                                    'parcial' => ['class' => 'warning', 'text' => 'Parcial'],
                                    'completa' => ['class' => 'success', 'text' => 'Completa'],
                                    'cierre_diario' => ['class' => 'info', 'text' => 'Cierre'],
                                    'R' => ['class' => 'warning', 'text' => 'Rendición'],
                                    'CD' => ['class' => 'info', 'text' => 'Cierre']
                                ];
                                $config = $tipoConfig[$rendicion->tipo] ?? ['class' => 'secondary', 'text' => ucfirst($rendicion->tipo)];
                            @endphp
                            <span class="badge bg-{{ $config['class'] }}">
                                {{ $config['text'] }}
                            </span>
                        </td>
                        <td>
                            <div class="info-label">{{ $rendicion->usuario_codigo ?? 'N/A' }}</div>
                            <small class="text-muted">{{ Str::limit($rendicion->usuario_nombre ?? 'Sin nombre', 15) }}</small>
                        </td>
                        <td class="text-end">
                            @if(isset($rendicion->total_rendido) && $rendicion->total_rendido > 0)
                                <div class="info-value small">S/ {{ number_format($rendicion->total_rendido, 2) }}</div>
                            @else
                                <span class="text-muted small">N/A</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($rendicion->pdf_path)
                                @php
                                    $pdfUrl = asset('storage/' . $rendicion->pdf_path);
                                    $pdfExists = file_exists(storage_path('app/public/' . $rendicion->pdf_path));
                                @endphp
                                @if($pdfExists)
                                    <a href="{{ $pdfUrl }}" 
                                       target="_blank" 
                                       class="btn btn-outline-primary btn-sm"
                                       title="Ver PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                @else
                                    <span class="text-muted" title="PDF no encontrado">
                                        <i class="fas fa-file-times"></i>
                                    </span>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <!-- Mostrar mensaje si hay más registros -->
    <div class="text-center mt-3">
        <small class="text-muted">Mostrando las últimas 5 rendiciones.</small>
        <a href="{{ route('admin.caja.historialRendiciones') }}" class="btn btn-link btn-sm p-0 ml-2">
            Ver historial completo <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
@else
    <!-- Sin registros recientes -->
    <div class="text-center py-4">
        <i class="fas fa-history fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Sin Historial Reciente</h5>
        <p class="text-muted mb-0">No se encontraron rendiciones recientes en el sistema.</p>
        <small class="text-muted">Las rendiciones aparecerán aquí una vez que se procesen.</small>
    </div>
@endif