@extends('layouts.admin')

@section('title', 'DEBUG - Editar Mora')

@section('content_header')
    <h1>🐛 DEBUG - Editar Mora</h1>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Datos Raw de la Mora</h3>
                </div>
                <div class="card-body">
                    @if(isset($moraCuota))
                        <h4>✅ Variable $moraCuota existe</h4>
                        
                        {{-- Dump básico de datos --}}
                        <div class="alert alert-info">
                            <strong>ID de la Mora:</strong> {{ $moraCuota->id ?? 'NULL' }}<br>
                            <strong>Monto:</strong> {{ $moraCuota->monto ?? 'NULL' }}<br>
                            <strong>Fecha:</strong> {{ $moraCuota->fecha ?? 'NULL' }}<br>
                            <strong>Días Mora:</strong> {{ $moraCuota->dias_mora ?? 'NULL' }}<br>
                            <strong>Estado:</strong> {{ $moraCuota->estado ?? 'NULL' }}<br>
                            <strong>Monto Pagado:</strong> {{ $moraCuota->monto_pagado ?? 'NULL' }}
                        </div>

                        {{-- Test de relación con Cuota --}}
                        <h5>🔗 Relación con Cuota:</h5>
                        @if($moraCuota->cuota)
                            <div class="alert alert-success">
                                ✅ Cuota cargada<br>
                                <strong>Número:</strong> {{ $moraCuota->cuota->numero ?? 'NULL' }}<br>
                                <strong>Monto:</strong> {{ $moraCuota->cuota->monto ?? 'NULL' }}<br>
                                <strong>Fecha Pago:</strong> {{ $moraCuota->cuota->fecha_pago ?? 'NULL' }}
                            </div>
                        @else
                            <div class="alert alert-danger">❌ No hay cuota asociada</div>
                        @endif

                        {{-- Test de relación con Operaciones --}}
                        <h5>💰 Relación con Operaciones:</h5>
                        @if($moraCuota->operaciones && $moraCuota->operaciones->count() > 0)
                            <div class="alert alert-success">
                                ✅ {{ $moraCuota->operaciones->count() }} operación(es) encontrada(s)
                                @foreach($moraCuota->operaciones as $operacion)
                                    <br><strong>Op ID:</strong> {{ $operacion->id }}
                                    - <strong>Abono:</strong> S/ {{ number_format($operacion->abono ?? 0, 2) }}
                                    - <strong>Fecha:</strong> {{ $operacion->fecha ?? 'NULL' }}
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-warning">⚠️ No hay operaciones asociadas</div>
                        @endif

                        {{-- Test de moras relacionadas --}}
                        <h5>📋 Moras de la misma operación:</h5>
                        @php
                            $operacionPrincipal = $moraCuota->operaciones->first();
                        @endphp
                        @if($operacionPrincipal && $operacionPrincipal->morasCuota)
                            <div class="alert alert-success">
                                ✅ {{ $operacionPrincipal->morasCuota->count() }} mora(s) en la operación
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID Mora</th>
                                            <th>Cuota #</th>
                                            <th>Monto</th>
                                            <th>Días</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($operacionPrincipal->morasCuota as $mora)
                                            <tr class="{{ $mora->id == $moraCuota->id ? 'table-primary' : '' }}">
                                                <td>{{ $mora->id }}</td>
                                                <td>{{ $mora->cuota->numero ?? 'N/A' }}</td>
                                                <td>S/ {{ number_format($mora->monto ?? 0, 2) }}</td>
                                                <td>{{ $mora->dias_mora ?? 0 }}</td>
                                                <td>{{ $mora->fecha ? \Carbon\Carbon::parse($mora->fecha)->format('d/m/Y') : 'N/A' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-warning">⚠️ No se pueden cargar moras relacionadas</div>
                        @endif

                        {{-- Formulario simplificado --}}
                        <hr>
                        <h4>📝 Formulario de Edición (Simplificado)</h4>
                        <form action="{{ route('admin.moras.editar', $moraCuota->id) }}" method="POST">
                            @csrf @method('PUT')
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="monto">Monto:</label>
                                        @php
                                            $operacion = $moraCuota->operaciones->first();
                                            $montoOperacion = $operacion ? $operacion->abono : $moraCuota->monto;
                                        @endphp
                                        <input type="number" class="form-control" id="monto" name="monto" 
                                               min="0.01" step="0.01" value="{{ old('monto', $montoOperacion) }}" required>
                                        <small class="text-muted">Monto actual: S/ {{ number_format($montoOperacion, 2) }}</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fecha_operacion">Fecha:</label>
                                        @php
                                            $fechaOperacion = $moraCuota->operaciones->first()->fecha ?? now();
                                        @endphp
                                        <input type="date" class="form-control" id="fecha_operacion" name="fecha_operacion" 
                                               value="{{ old('fecha_operacion', \Carbon\Carbon::parse($fechaOperacion)->format('Y-m-d')) }}" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="justificacion">Justificación:</label>
                                <textarea class="form-control" id="justificacion" name="justificacion" rows="3" required>{{ old('justificacion') }}</textarea>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">💾 Actualizar Mora</button>
                                <a href="javascript:history.back()" class="btn btn-secondary">❌ Cancelar</a>
                            </div>
                        </form>

                    @else
                        <div class="alert alert-danger">
                            <h4>❌ ERROR: Variable $moraCuota no existe</h4>
                            <p>Los datos no están llegando correctamente al controlador o a la vista.</p>
                        </div>

                        @if(isset($mora))
                            <div class="alert alert-info">
                                <h5>✅ Se encontró variable $mora (configuración general)</h5>
                                <strong>ID:</strong> {{ $mora->id }}<br>
                                <strong>Monto:</strong> {{ $mora->monto }}%<br>
                                <strong>Status:</strong> {{ $mora->status }}
                            </div>
                        @else
                            <div class="alert alert-danger">
                                <h5>❌ Tampoco existe $mora</h5>
                                <p>No se pasaron datos desde el controlador.</p>
                            </div>
                        @endif
                    @endif

                    {{-- Debug de variables adicionales --}}
                    <hr>
                    <h5>🔍 Debug de Variables del Sistema:</h5>
                    <div class="alert alert-secondary">
                        <strong>Request URL:</strong> {{ request()->url() }}<br>
                        <strong>Route Name:</strong> {{ request()->route()->getName() ?? 'N/A' }}<br>
                        <strong>Method:</strong> {{ request()->method() }}<br>
                        <strong>User ID:</strong> {{ auth()->id() ?? 'No autenticado' }}<br>
                        <strong>Timestamp:</strong> {{ now() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
console.log('🐛 DEBUG: JavaScript cargado correctamente');
console.log('🐛 DEBUG: Bootstrap version:', typeof $);
console.log('🐛 DEBUG: AdminLTE loaded:', typeof AdminLTE);
</script>
@stop