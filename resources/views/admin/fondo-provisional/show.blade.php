@extends('layouts.admin')

@section('title', 'Detalle Fondo Provisional')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-12 text-center">
                <h1 class="font-weight-bold text-warning">
                    <i class="fas fa-piggy-bank mr-2"></i>Detalle del Fondo Provisional
                </h1>
                <p class="text-muted">Información completa del fondo provisional registrado</p>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Acciones -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-body text-center">
                                <div class="btn-group flex-wrap" role="group">
                                    @if($fondo->puedeSerRendido())
                                    <form action="{{ route('admin.fondo-provisional.marcar-rendido', $fondo->id) }}" 
                                          method="POST" 
                                          class="d-inline mr-2"
                                          onsubmit="return confirm('¿Está seguro de marcar este fondo como rendido?');">
                                        @csrf
                                        <input type="hidden" name="fecha_rendicion" value="{{ date('Y-m-d') }}">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check mr-1"></i>Marcar como Rendido
                                        </button>
                                    </form>
                                    @endif

                                    <a href="{{ route('admin.prestamos.show', $fondo->prestamo_id) }}" 
                                       class="btn btn-primary">
                                        <i class="fas fa-eye mr-1"></i>Ver Préstamo
                                    </a>

                                    @if($fondo->operacion)
                                    <a href="{{ route('admin.operaciones.index') }}?operacion_id={{ $fondo->operacion->id }}" 
                                       class="btn btn-info">
                                        <i class="fas fa-receipt mr-1"></i>Ver Operación
                                    </a>
                                    @endif

                                    <a href="{{ route('admin.fondo-provisional.index') }}"
                                       class="btn btn-outline-secondary">
                                        <i class="fas fa-list mr-1"></i>Todos los Fondos
                                    </a>

                                    <button onclick="window.print()" class="btn btn-outline-primary">
                                        <i class="fas fa-print mr-1"></i>Imprimir
                                    </button>

                                    @if(auth()->user()->hasRole('Admin'))
                                    <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#modalEditarFondo">
                                        <i class="fas fa-edit mr-1"></i>Editar Fondo
                                    </button>
                                    @endif
                                </div>

                                @if($fondo->estado === \App\Models\FondoProvisional::ESTADO_RENDIDO && $fondo->rendidoPor)
                                <div class="mt-3">
                                    <div class="alert alert-success">
                                        <small>
                                            <i class="fas fa-check-circle mr-1"></i>
                                            <strong>Rendido por:</strong> {{ $fondo->rendidoPor->name }} 
                                            el {{ $fondo->fecha_rendicion ? $fondo->fecha_rendicion->format('d/m/Y') : 'N/A' }}
                                        </small>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Ticket de Fondo Provisional -->
                <div class="card shadow-lg border-0 ticket-card">
                    <div class="card-header bg-gradient-warning text-white text-center">
                        <h4 class="mb-0">
                            <i class="fas fa-ticket-alt mr-2"></i>Ticket de Fondo Provisional
                        </h4>
                        <small class="text-white-50">ID: #{{ $fondo->id }}</small>
                    </div>
                    
                    <div class="card-body p-0">
                        <!-- Encabezado del Recibo -->
                        <div class="ticket-section" style="background: #f8f9fa; border-bottom: 2px solid #ffc107;">
                            <div class="text-center">
                                <h5 class="mb-2 text-dark">RECIBO DE FONDO PROVISIONAL</h5>
                                <div class="mb-2">
                                    <span class="badge badge-{{ $fondo->estado_badge_class }} badge-lg">
                                        {{ $fondo->estado_texto }}
                                    </span>
                                </div>
                                <small class="text-muted">
                                    Fecha: {{ $fondo->fecha_entrega ? $fondo->fecha_entrega->format('d/m/Y') : 'N/A' }}
                                </small>
                            </div>
                        </div>

                        <!-- Información del Cliente -->
                        <div class="ticket-section">
                            <div class="section-title">
                                <i class="fas fa-user mr-2"></i>DATOS DEL CLIENTE
                            </div>
                            <div class="ticket-row">
                                <span class="ticket-label">Cliente:</span>
                                <span class="ticket-value font-weight-bold">
                                    {{ $fondo->prestamo->cliente->persona->nombres }}
                                    {{ $fondo->prestamo->cliente->persona->ape_pat }}
                                    {{ $fondo->prestamo->cliente->persona->ape_mat }}
                                </span>
                            </div>
                            <div class="ticket-row">
                                <span class="ticket-label">DNI:</span>
                                <span class="ticket-value">{{ $fondo->prestamo->cliente->persona->documento }}</span>
                            </div>
                            <div class="ticket-row">
                                <span class="ticket-label">Préstamo N°:</span>
                                <span class="ticket-value">
                                    <a href="{{ route('admin.prestamos.show', $fondo->prestamo_id) }}" class="text-primary font-weight-bold">
                                        #{{ $fondo->prestamo_id }}
                                    </a>
                                </span>
                            </div>
                        </div>

                        <hr class="ticket-divider">

                        <!-- Detalle del Fondo -->
                        <div class="ticket-section">
                            <div class="section-title">
                                <i class="fas fa-file-invoice-dollar mr-2"></i>DETALLE DEL FONDO
                            </div>
                            <div class="ticket-row">
                                <span class="ticket-label">Monto del Préstamo:</span>
                                <span class="ticket-value">S/ {{ number_format($fondo->monto_capital, 2) }}</span>
                            </div>
                            <div class="ticket-row">
                                <span class="ticket-label">Porcentaje Fondo:</span>
                                <span class="ticket-value">{{ $fondo->porcentaje }}%</span>
                            </div>
                            <div class="ticket-row highlight-row" style="background: #fff3cd; border-left-color: #ffc107;">
                                <span class="ticket-label">Fondo Provisional:</span>
                                <span class="ticket-value text-warning font-weight-bold h5">
                                    S/ {{ number_format($fondo->monto_fondo, 2) }}
                                </span>
                            </div>
                        </div>

                        <hr class="ticket-divider">

                        <!-- Información del Asesor -->
                        <div class="ticket-section">
                            <div class="section-title">
                                <i class="fas fa-user-tie mr-2"></i>RECIBIDO POR
                            </div>
                            <div class="ticket-row">
                                <span class="ticket-label">Asesor:</span>
                                <span class="ticket-value">
                                    <span class="badge badge-info">{{ $fondo->asesor->name }}</span>
                                </span>
                            </div>
                            <div class="ticket-row">
                                <span class="ticket-label">Fecha de Entrega:</span>
                                <span class="ticket-value">{{ $fondo->fecha_entrega ? $fondo->fecha_entrega->format('d/m/Y H:i') : 'N/A' }}</span>
                            </div>
                            @if($fondo->fecha_rendicion)
                            <div class="ticket-row">
                                <span class="ticket-label">Fecha de Rendición:</span>
                                <span class="ticket-value text-success font-weight-bold">
                                    {{ $fondo->fecha_rendicion->format('d/m/Y') }}
                                </span>
                            </div>
                            @endif
                        </div>

                        @if($fondo->operacion)
                        <hr class="ticket-divider">

                        <!-- Información de la Operación -->
                        <div class="ticket-section">
                            <div class="section-title">
                                <i class="fas fa-receipt mr-2"></i>OPERACIÓN REGISTRADA
                            </div>
                            <div class="ticket-row">
                                <span class="ticket-label">Operación ID:</span>
                                <span class="ticket-value">
                                    <span class="badge badge-secondary">#{{ $fondo->operacion->id }}</span>
                                </span>
                            </div>
                            <div class="ticket-row">
                                <span class="ticket-label">Tipo:</span>
                                <span class="ticket-value">
                                    <span class="badge badge-info">{{ $fondo->operacion->tipo_operacion ?? 'N/A' }}</span>
                                </span>
                            </div>
                            <div class="ticket-row">
                                <span class="ticket-label">Método de Pago:</span>
                                <span class="ticket-value">
                                    @if($fondo->operacion->metodoDePago)
                                        <span class="badge badge-primary">
                                            <i class="fas fa-{{ $fondo->operacion->metodoDePago->metodo_pago == 'Efectivo' ? 'money-bill-wave' : ($fondo->operacion->metodoDePago->metodo_pago == 'Yape' ? 'mobile-alt' : 'credit-card') }} mr-1"></i>
                                            {{ $fondo->operacion->metodoDePago->metodo_pago }}
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">No especificado</span>
                                    @endif
                                </span>
                            </div>
                            <div class="ticket-row">
                                <span class="ticket-label">Fecha Operación:</span>
                                <span class="ticket-value">{{ $fondo->operacion->fecha ? $fondo->operacion->fecha->format('d/m/Y H:i') : 'N/A' }}</span>
                            </div>
                            @if($fondo->operacion->codigo)
                            <div class="ticket-row">
                                <span class="ticket-label">Nro. de Operación:</span>
                                <span class="ticket-value">
                                    <span class="badge badge-dark">{{ $fondo->operacion->codigo }}</span>
                                </span>
                            </div>
                            @endif
                            @if($fondo->operacion->voucher_path)
                            <div class="ticket-row">
                                <span class="ticket-label">Comprobante:</span>
                                <span class="ticket-value">
                                    <a href="{{ asset('storage/' . $fondo->operacion->voucher_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-image mr-1"></i>Ver Comprobante
                                    </a>
                                </span>
                            </div>
                            @endif
                            <div class="ticket-row highlight-row">
                                <span class="ticket-label">Monto Recibido:</span>
                                <span class="ticket-value text-success font-weight-bold h5">
                                    S/ {{ number_format($fondo->operacion->abono ?? 0, 2) }}
                                </span>
                            </div>
                            @if($fondo->operacion->estado_rendicion)
                            <div class="ticket-row">
                                <span class="ticket-label">Estado Rendición:</span>
                                <span class="ticket-value">
                                    <span class="badge badge-{{ $fondo->operacion->estado_rendicion == 'rendido' ? 'success' : 'warning' }}">
                                        {{ ucfirst($fondo->operacion->estado_rendicion) }}
                                    </span>
                                </span>
                            </div>
                            @endif
                            @if($fondo->operacion->comentario)
                            <div class="ticket-row">
                                <span class="ticket-label">Comentario:</span>
                                <span class="ticket-value text-muted small">{{ $fondo->operacion->comentario }}</span>
                            </div>
                            @endif
                            @if($fondo->operacion->voucher_path)
                            <div class="mt-3 text-center">
                                <label class="font-weight-bold text-muted d-block mb-2">
                                    <i class="fas fa-image mr-1"></i>Voucher
                                </label>
                                <a href="{{ asset('storage/' . $fondo->operacion->voucher_path) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $fondo->operacion->voucher_path) }}"
                                         alt="Voucher del fondo provisional"
                                         class="img-fluid rounded shadow-sm"
                                         style="max-height: 300px; cursor: zoom-in;">
                                </a>
                            </div>
                            @endif
                        </div>
                        @endif

                        @if($fondo->observaciones)
                        <hr class="ticket-divider">

                        <!-- Observaciones -->
                        <div class="ticket-section">
                            <div class="section-title">
                                <i class="fas fa-comment mr-2"></i>OBSERVACIONES
                            </div>
                            <div class="ticket-observaciones">
                                {{ $fondo->observaciones }}
                            </div>
                        </div>
                        @endif

                        <!-- Pie del Ticket -->
                        <div class="ticket-footer">
                            <div class="text-center">
                                <small class="text-muted">
                                    Generado el {{ now()->format('d/m/Y H:i') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Editar Fondo (Solo Admin) --}}
    @if(auth()->user()->hasRole('Admin'))
    <div class="modal fade" id="modalEditarFondo" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.fondo-provisional.update', $fondo->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Editar Fondo Provisional</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        {{-- Monto --}}
                        <div class="form-group">
                            <label for="monto_fondo"><i class="fas fa-money-bill-wave mr-1"></i>Monto del Fondo</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">S/</span>
                                </div>
                                <input type="number" class="form-control" id="monto_fondo" name="monto_fondo"
                                       value="{{ $fondo->monto_fondo }}" step="0.01" min="0" required>
                            </div>
                        </div>

                        {{-- Método de Pago --}}
                        <div class="form-group">
                            <label for="metodo_pago_id"><i class="fas fa-credit-card mr-1"></i>Método de Pago</label>
                            <select class="form-control" id="metodo_pago_id" name="metodo_pago_id" required>
                                @foreach($metodosPago as $metodo)
                                    <option value="{{ $metodo->id }}"
                                        {{ ($fondo->operacion && $fondo->operacion->metodo_pago_id == $metodo->id) ? 'selected' : '' }}>
                                        {{ $metodo->metodo_pago }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Código / Nro. Operación --}}
                        <div class="form-group">
                            <label for="codigo"><i class="fas fa-hashtag mr-1"></i>Nro. de Operación</label>
                            <input type="text" class="form-control" id="codigo" name="codigo"
                                   value="{{ $fondo->operacion->codigo ?? '' }}" placeholder="Ej: 123456">
                        </div>

                        {{-- Imagen del Voucher --}}
                        <div class="form-group">
                            <label for="voucher_imagen"><i class="fas fa-image mr-1"></i>Imagen del Voucher</label>
                            @if($fondo->operacion && $fondo->operacion->voucher_path)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . $fondo->operacion->voucher_path) }}"
                                         alt="Voucher actual" class="img-thumbnail" style="max-height: 150px;">
                                    <small class="d-block text-muted mt-1">Imagen actual. Suba una nueva para reemplazarla.</small>
                                </div>
                            @endif
                            <input type="file" class="form-control-file" id="voucher_imagen" name="voucher_imagen"
                                   accept="image/jpeg,image/png,image/jpg,image/gif">
                            <small class="form-text text-muted">Formatos: JPG, PNG, GIF. Máximo 2MB.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save mr-1"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@stop

@section('css')
<style>
    .ticket-card {
        max-width: 600px;
        margin: 0 auto;
        border: 2px solid #ffc107;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .ticket-header {
        background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        padding: 20px;
        color: white;
        text-align: center;
    }

    .ticket-divider {
        border: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, #ffc107, transparent);
        margin: 15px 0;
    }

    .ticket-section {
        padding: 15px 25px;
        border-bottom: 1px dashed #eee;
    }

    .ticket-section:last-child {
        border-bottom: none;
    }

    .section-title {
        font-weight: bold;
        color: #495057;
        margin-bottom: 15px;
        text-transform: uppercase;
        font-size: 0.9rem;
        letter-spacing: 1px;
    }

    .ticket-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px dotted #f8f9fa;
    }

    .ticket-row:last-child {
        border-bottom: none;
    }

    .highlight-row {
        background: #f8f9fa;
        margin: 10px -25px;
        padding: 12px 25px !important;
        border-left: 4px solid #28a745;
        border-bottom: none !important;
    }

    .ticket-label {
        font-weight: 600;
        color: #6c757d;
        font-size: 0.9rem;
    }

    .ticket-value {
        font-weight: 500;
        color: #495057;
        text-align: right;
        font-size: 0.9rem;
    }

    .ticket-observaciones {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #ffc107;
        font-size: 0.9rem;
        line-height: 1.5;
        color: #495057;
    }

    .ticket-footer {
        background: #f8f9fa;
        padding: 15px;
        text-align: center;
        border-top: 2px dashed #dee2e6;
    }

    .badge-sm {
        padding: 4px 8px;
        font-size: 0.75rem;
    }

    .badge-lg {
        padding: 8px 16px;
        font-size: 1rem;
        font-weight: 600;
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
    }

    .btn-group .btn {
        margin: 2px;
        border-radius: 6px;
    }

    .card {
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .btn {
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    @media (max-width: 768px) {
        .ticket-card {
            margin: 0 15px;
        }

        .ticket-section {
            padding: 15px 20px;
        }

        .ticket-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }

        .ticket-value {
            text-align: left;
            width: 100%;
        }

        .btn-group {
            flex-direction: column;
            width: 100%;
        }

        .btn-group .btn {
            width: 100%;
            margin: 2px 0;
        }
    }

    @media print {
        .ticket-card {
            box-shadow: none;
            border: 2px solid #000;
            max-width: 100%;
        }

        .btn, .card:first-child, .row.mt-4 {
            display: none !important;
        }

        .ticket-divider {
            border-top: 1px dashed #333;
        }

        .badge {
            border: 1px solid #333;
        }

        body {
            background: white !important;
        }

        .card-header {
            background: #f0f0f0 !important;
            color: #000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .highlight-row {
            background: #f5f5f5 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animación para el ticket
    const ticketCard = document.querySelector('.ticket-card');
    if (ticketCard) {
        ticketCard.style.opacity = '0';
        ticketCard.style.transform = 'translateY(30px) scale(0.95)';
        
        setTimeout(() => {
            ticketCard.style.transition = 'all 0.6s ease-out';
            ticketCard.style.opacity = '1';
            ticketCard.style.transform = 'translateY(0) scale(1)';
        }, 200);
    }

    // Animación para las secciones del ticket
    const ticketSections = document.querySelectorAll('.ticket-section');
    ticketSections.forEach((section, index) => {
        section.style.opacity = '0';
        section.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            section.style.transition = 'all 0.4s ease-out';
            section.style.opacity = '1';
            section.style.transform = 'translateX(0)';
        }, 400 + (index * 100));
    });

    // Animación para los botones de acción
    const actionCard = document.querySelector('.row.mt-4 .card');
    if (actionCard) {
        actionCard.style.opacity = '0';
        actionCard.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            actionCard.style.transition = 'all 0.5s ease-out';
            actionCard.style.opacity = '1';
            actionCard.style.transform = 'translateY(0)';
        }, 800);
    }
});
</script>
@stop