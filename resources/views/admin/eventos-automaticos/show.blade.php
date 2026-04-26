@extends('layouts.admin')

@section('title', 'Detalle de Evento Automático')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-robot me-2"></i>
                        Detalle de Evento Automático #{{ $evento->id }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.eventos-automaticos.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="30%">Fecha/Hora:</th>
                                    <td>{{ $evento->created_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Evento:</th>
                                    <td>
                                        <span class="badge bg-info">{{ $evento->evento }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Categoría:</th>
                                    <td>
                                        <span class="badge bg-secondary">{{ $evento->categoria }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Resultado:</th>
                                    <td>
                                        @if($evento->resultado === 'exitoso')
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Exitoso
                                            </span>
                                        @elseif($evento->resultado === 'fallido')
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times"></i> Fallido
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock"></i> Procesando
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tiempo de Procesamiento:</th>
                                    <td>
                                        @if($evento->tiempo_procesamiento)
                                            {{ number_format($evento->tiempo_procesamiento, 2) }} ms
                                        @else
                                            No disponible
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="30%">Préstamo:</th>
                                    <td>
                                        @if($evento->prestamo)
                                            <a href="{{ route('admin.prestamos.show', $evento->prestamo->id) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-file-invoice-dollar"></i>
                                                Ver Préstamo #{{ $evento->prestamo->id }}
                                            </a>
                                        @else
                                            No aplicable
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Cuota:</th>
                                    <td>
                                        @if($evento->cuota)
                                            Cuota #{{ $evento->cuota->numero }}
                                        @else
                                            No aplicable
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Operación:</th>
                                    <td>
                                        @if($evento->operacion)
                                            Operación #{{ $evento->operacion->id }}
                                        @else
                                            No aplicable
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Usuario:</th>
                                    <td>
                                        @if($evento->usuario)
                                            {{ $evento->usuario->name }}
                                        @else
                                            Sistema Automático
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Datos Antes del Evento</h5>
                            @if($evento->datos_antes)
                                <div class="bg-light p-3 rounded">
                                    <pre><code>{{ json_encode($evento->datos_antes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                </div>
                            @else
                                <p class="text-muted">No hay datos anteriores registrados</p>
                            @endif
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Datos Después del Evento</h5>
                            @if($evento->datos_despues)
                                <div class="bg-light p-3 rounded">
                                    <pre><code>{{ json_encode($evento->datos_despues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                </div>
                            @else
                                <p class="text-muted">No hay datos posteriores registrados</p>
                            @endif
                        </div>
                    </div>

                    @if($evento->mensaje_error)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>Mensaje de Error</h5>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    {{ $evento->mensaje_error }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection