@extends('layouts.admin')

@section('title', 'Anular Mora')

@section('content_header')
    <h1>
        <i class="fas fa-ban mr-2 text-danger"></i>
        Anular Mora
        <small class="ml-3">
            <span class="badge badge-primary">ID: {{ $mora->id }}</span>
            <span class="badge badge-danger">ANULACIÓN</span>
        </small>
    </h1>
    <div class="breadcrumb">
        <a href="{{ route('admin.prestamos.show', $mora->cuota->prestamo->id) }}">Préstamo {{ $mora->cuota->prestamo->id }}</a> / Anular Mora
    </div>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <!-- Información de la Mora -->
            <div class="card">
                <div class="card-header bg-info">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        Información de la Mora
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>ID Mora:</strong></td>
                                    <td>{{ $mora->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Cuota:</strong></td>
                                    <td>Cuota {{ $mora->cuota->numero }} del préstamo {{ $mora->cuota->prestamo->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Cliente:</strong></td>
                                    <td>{{ $mora->cuota->prestamo->cliente->persona->nombres }} {{ $mora->cuota->prestamo->cliente->persona->apellidos }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha Mora:</strong></td>
                                    <td>{{ \Carbon\Carbon::parse($mora->fecha)->format('d/m/Y') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Días de Mora:</strong></td>
                                    <td><span class="badge bg-warning">{{ $mora->dias_mora ?? 0 }} días</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Monto:</strong></td>
                                    <td><strong class="text-danger">S/ {{ number_format($mora->monto, 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Monto Pagado:</strong></td>
                                    <td><strong class="text-success">S/ {{ number_format($mora->monto_pagado ?? 0, 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td><span class="badge {{ $mora->estado_class }}">{{ $mora->estado_nombre }}</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operaciones Relacionadas (si existen) -->
            @if($mora->operaciones->isNotEmpty())
            <div class="card">
                <div class="card-header bg-warning">
                    <h3 class="card-title">
                        <i class="fas fa-link"></i>
                        Operaciones Relacionadas que serán anuladas
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Monto</th>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mora->operaciones as $operacion)
                                <tr>
                                    <td>{{ $operacion->id }}</td>
                                    <td>{{ $operacion->tipo_operacion }}</td>
                                    <td>S/ {{ number_format($operacion->monto, 2) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($operacion->created_at)->format('d/m/Y H:i') }}</td>
                                    <td>{{ $operacion->user->name ?? '--' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-4">
            <!-- Formulario de Anulación -->
            <div class="card">
                <div class="card-header bg-danger">
                    <h3 class="card-title">
                        <i class="fas fa-ban"></i>
                        Confirmar Anulación
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>¡Atención!</strong> Esta acción no se puede deshacer.
                        @if($mora->operaciones->isNotEmpty())
                            <br><br>También se anularán {{ $mora->operaciones->count() }} operación(es) relacionada(s).
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.moras.anular', $mora->id) }}" 
                          onsubmit="return confirm('¿Estás completamente seguro de anular esta mora?')">
                        @csrf
                        @method('DELETE')

                        <div class="form-group">
                            <label for="justificacion">
                                <i class="fas fa-comment"></i>
                                Justificación <span class="text-danger">*</span>
                            </label>
                            <textarea name="justificacion" 
                                      id="justificacion" 
                                      class="form-control @error('justificacion') is-invalid @enderror" 
                                      rows="4" 
                                      placeholder="Describe detalladamente el motivo de la anulación..."
                                      required
                                      minlength="10">{{ old('justificacion') }}</textarea>
                            @error('justificacion')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">Mínimo 10 caracteres requeridos.</small>
                        </div>

                        <div class="form-group text-center">
                            <a href="{{ route('admin.prestamos.show', $mora->cuota->prestamo->id) }}" 
                               class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-danger ml-2">
                                <i class="fas fa-ban"></i> Confirmar Anulación
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Resumen -->
            <div class="card">
                <div class="card-header bg-secondary">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i>
                        Resumen de Anulación
                    </h3>
                </div>
                <div class="card-body">
                    <p><strong>Monto a anular:</strong></p>
                    <h4 class="text-danger">S/ {{ number_format($mora->monto, 2) }}</h4>
                    
                    @if($mora->monto_pagado > 0)
                    <p><strong>Monto pagado que se liberará:</strong></p>
                    <h4 class="text-success">S/ {{ number_format($mora->monto_pagado, 2) }}</h4>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@stop