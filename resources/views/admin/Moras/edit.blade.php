@extends('layouts.admin')

@section('title', 'Editar Pago de Moras')

@section('content_header')
    <h1><i class="fas fa-edit mr-2"></i>Editar Pago de Moras</h1>
@stop

@section('content')
<div class="container-fluid">
    @if(isset($moraCuota))
        <div class="row">
            {{-- Columna Principal --}}
            <div class="col-lg-8">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calculator"></i> Redistribuir Pago de Moras
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Operación:</strong> Editando pago que incluye la mora de la Cuota #{{ optional($moraCuota->cuota)->numero ?? 'N/A' }}
                        </div>

                        <form action="{{ route('admin.moras.editar', $moraCuota->id) }}" method="POST">
                            @csrf @method('PUT')

                            {{-- Monto Total a Distribuir --}}
                            <div class="card bg-light mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-money-bill-wave"></i> Monto de Pago a Distribuir</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="monto_total_pago">Monto Total del Pago (S/.) <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">S/</span>
                                                    </div>
                                                    @php
                                                        // El monto de la operación (lo que se pagó en total)
                                                        $operacion = $moraCuota->operaciones->first();
                                                        $montoTotalPago = $operacion ? $operacion->abono : 0;
                                                    @endphp
                                                    <input type="number" class="form-control" id="monto_total_pago" name="monto_total_pago" 
                                                           min="0" step="0.01" value="{{ old('monto_total_pago', $montoTotalPago) }}" required>
                                                </div>
                                                <small class="text-muted">Este monto se distribuirá entre todas las moras de la operación</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="fecha_pago">Fecha del Pago <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                                    </div>
                                                    @php
                                                        $fechaPago = $operacion ? $operacion->fecha : now();
                                                    @endphp
                                                    <input type="date" class="form-control" id="fecha_pago" name="fecha_pago" 
                                                           value="{{ old('fecha_pago', \Carbon\Carbon::parse($fechaPago)->format('Y-m-d')) }}" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Distribución Simulada --}}
                            <div class="card border-secondary mb-4">
                                <div class="card-header text-black">
                                    <h5 class="mb-0">
                                        <i class="fas fa-list-ol"></i> Distribución del Pago
                                        <small class="float-right">Total: <span id="total-moras">{{ $operacion ? $operacion->morasCuota->count() : 1 }}</span> mora(s)</small>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="distribucion-moras">
                                        @if($operacion && $operacion->morasCuota->count() > 0)
                                            @php
                                                $morasOperacion = $operacion->morasCuota->sortBy('cuota.numero');
                                                $montoDistribuir = $montoTotalPago;
                                                $restante = $montoDistribuir;
                                            @endphp
                                            
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>Cuota</th>
                                                            <th>Mora Base</th>
                                                            <th>Pagado Actual</th>
                                                            <th>Nuevo Pagado</th>
                                                            <th>Estado</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($morasOperacion as $mora)
                                                            @php
                                                                $montoBaseMora = $mora->monto; // Monto fijo de la mora (ej: 4 soles)
                                                                $pagadoActual = $mora->monto_pagado ?? 0;
                                                                
                                                                // Calcular cuánto se pagará de esta mora
                                                                $aPagar = min($montoBaseMora, $restante);
                                                                $restante = max(0, $restante - $aPagar);
                                                                
                                                                // Determinar estado
                                                                if ($aPagar >= $montoBaseMora) {
                                                                    $estado = 'PAGADA';
                                                                    $badgeClass = 'success';
                                                                } elseif ($aPagar > 0) {
                                                                    $estado = 'PARCIAL';
                                                                    $badgeClass = 'warning';
                                                                } else {
                                                                    $estado = 'PENDIENTE';
                                                                    $badgeClass = 'secondary';
                                                                }
                                                            @endphp
                                                            <tr class="{{ $mora->id == $moraCuota->id ? 'table-primary' : '' }}">
                                                                <td>
                                                                    <strong>#{{ $mora->cuota->numero ?? 'N/A' }}</strong>
                                                                    @if($mora->id == $moraCuota->id)
                                                                        <small class="badge badge-primary ml-1">Editando</small>
                                                                    @endif
                                                                </td>
                                                                <td><strong>S/ {{ number_format($montoBaseMora, 2) }}</strong></td>
                                                                <td class="text-muted">S/ {{ number_format($pagadoActual, 2) }}</td>
                                                                <td class="font-weight-bold text-success">S/ {{ number_format($aPagar, 2) }}</td>
                                                                <td>
                                                                    <span class="badge badge-{{ $badgeClass }}">{{ $estado }}</span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>

                                            {{-- Resumen --}}
                                            <div class="alert alert-info mt-3">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <strong>Total a Distribuir:</strong><br>
                                                        <span class="h5 text-primary">S/ <span id="monto-distribuir">{{ number_format($montoTotalPago, 2) }}</span></span>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <strong>Moras en la Operación:</strong><br>
                                                        <span class="h5 text-info">{{ $morasOperacion->count() }} mora(s)</span>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <strong>Monto Total Moras:</strong><br>
                                                        <span class="h5 text-secondary">S/ {{ number_format($morasOperacion->sum('monto'), 2) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                No se encontraron moras asociadas a esta operación.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Justificación --}}
                            <div class="form-group">
                                <label for="justificacion">Justificación del Cambio <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="justificacion" name="justificacion" rows="3" required
                                          placeholder="Explique por qué está modificando el pago de moras (ej: error en monto, corrección de fecha, etc.)">{{ old('justificacion') }}</textarea>
                                @error('justificacion')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Botones --}}
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-outline-secondary btn-block" onclick="history.back()">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-success btn-block">
                                        <i class="fas fa-save"></i> Redistribuir Pago
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Columna de Información --}}
            <div class="col-lg-4">
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-info-circle"></i> Información del Sistema
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6>💡 Cómo Funciona:</h6>
                        <ul class="text-sm p-4">
                            <li><strong>Monto base:</strong> Cada mora mantiene su valor original (máx. 4 soles)</li>
                            <li><strong>Distribución:</strong> El pago se reparte secuencialmente entre moras</li>
                            <li><strong>Estados:</strong> Completa, Parcial o Pendiente según el pago</li>
                            <li><strong>Orden:</strong> Se pagan primero las moras más antiguas</li>
                        </ul>
                        
                        <hr>
                        
                        <h6>📊 Datos de la Mora Actual:</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>ID:</strong></td>
                                <td>{{ $moraCuota->id }}</td>
                            </tr>
                            <tr>
                                <td><strong>Cuota:</strong></td>
                                <td>#{{ optional($moraCuota->cuota)->numero ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Monto Base:</strong></td>
                                <td>S/ {{ number_format($moraCuota->monto, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Pagado:</strong></td>
                                <td>S/ {{ number_format($moraCuota->monto_pagado ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Días Mora:</strong></td>
                                <td>{{ $moraCuota->dias_mora ?? 0 }} días</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- JavaScript para actualización dinámica --}}
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const montoInput = document.getElementById('monto_total_pago');
            const montoSpan = document.getElementById('monto-distribuir');
            
            if (montoInput && montoSpan) {
                montoInput.addEventListener('input', function() {
                    const monto = parseFloat(this.value) || 0;
                    montoSpan.textContent = monto.toLocaleString('es-PE', {
                        minimumFractionDigits: 2, 
                        maximumFractionDigits: 2
                    });
                    
                    // Aquí se podría agregar lógica para recalcular la distribución en tiempo real
                    console.log('Nuevo monto a distribuir:', monto);
                });
            }
        });
        </script>

    @else
        <div class="alert alert-danger">
            <h4><i class="fas fa-exclamation-triangle"></i> Error</h4>
            <p>No se encontraron datos de la mora para editar.</p>
        </div>
    @endif
</div>
@stop