@extends('layouts.admin')

@section('title', 'Crear Compromiso')

@section('content_header')
    <h1 class="text-center font-weight-bold">Crear Nuevo Compromiso</h1>
    <p class="text-muted text-center">Rellene los campos para registrar un nuevo compromiso de pago.</p>
@stop

@section('content')
    <div class="card shadow-lg">
        <div class="card-body">
            <form action="{{ route('admin.compromisos.store') }}" method="POST">
                @csrf

                <!-- Información del préstamo -->
                <input type="hidden" name="prestamo_id" value="{{ $prestamo->id }}">
                    <div class="row">
                    <div class="form-group col-md-8">
                        <label for="cliente" class="font-weight-bold">Cliente</label>
                        <input type="text" class="form-control" 
                            value="{{ $prestamo->cliente->persona->nombres }} {{ $prestamo->cliente->persona->ape_pat }} {{ $prestamo->cliente->persona->ape_mat }}" 
                            disabled>
                    </div>
                    <!-- Estado -->
                    <div class="form-group col-md-4">
                        <label for="estado" class="font-weight-bold">Estado del Compromiso <span class="text-danger">*</span></label>
                        <select name="estado" id="estado" class="form-control" required>
                            <option value="{{ \App\Models\Compromiso::ESTADO_PENDIENTE }}" {{ old('estado') == \App\Models\Compromiso::ESTADO_PENDIENTE ? 'selected' : '' }}>Pendiente</option>
                            <option value="{{ \App\Models\Compromiso::ESTADO_PAGADO }}" {{ old('estado') == \App\Models\Compromiso::ESTADO_PAGADO ? 'selected' : '' }}>Pagado</option>
                            <option value="{{ \App\Models\Compromiso::ESTADO_POSTERGADO }}" {{ old('estado') == \App\Models\Compromiso::ESTADO_POSTERGADO ? 'selected' : '' }}>Postergado</option>
                        </select>
                        @error('estado')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                

                <div class="form-row">
                    <!-- Fecha de Compromiso -->
                    <div class="form-group col-md-4">
                        <label for="fecha" class="font-weight-bold">Fecha de Compromiso <span class="text-danger">*</span></label>
                        <input type="date" name="fecha" id="fecha" class="form-control" value="{{ old('fecha') }}" required>
                        @error('fecha')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Hora de Compromiso -->
                    <div class="form-group col-md-4">
                        <label for="hora" class="font-weight-bold">Hora de Compromiso <span class="text-danger">*</span></label>
                        <div class="d-flex">
                            <select name="hora_hh" id="hora_hh" class="form-control mr-2" required>
                                <option value="" disabled selected>HH</option>
                                @for($i = 8; $i < 20; $i++)
                                    @php
                                        $hora_formateada = str_pad($i, 2, '0', STR_PAD_LEFT);
                                    @endphp
                                    <option value="{{ $hora_formateada }}" {{ old('hora_hh') == $hora_formateada ? 'selected' : '' }}>
                                        {{ $hora_formateada }}
                                    </option>
                                @endfor
                            </select>
                            <span class="align-self-center">:</span>
                            <select name="hora_mm" id="hora_mm" class="form-control ml-2" required>
                                <option value="" disabled selected>MM</option>
                                @foreach(['00', '15', '30', '45'] as $minuto)
                                    <option value="{{ $minuto }}" {{ old('hora_mm') == $minuto ? 'selected' : '' }}>
                                        {{ $minuto }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('hora_hh')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        @error('hora_mm')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Monto -->
                    <div class="form-group col-md-4">
                        <label for="monto" class="font-weight-bold">Monto <span class="text-danger">*</span></label>
                        <input type="number" name="monto" id="monto" class="form-control" value="{{ old('monto') }}" step="0.01" min="0" placeholder="Ej. 150.00" required>
                        @error('monto')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <!-- Comentario -->
                    <div class="form-group col-md-12">
                        <label for="comentario" class="font-weight-bold">Comentario (Opcional)</label>
                        <textarea name="comentario" id="comentario" class="form-control" rows="3" placeholder="Detalles adicionales del compromiso">{{ old('comentario') }}</textarea>
                        @error('comentario')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <!-- Botones -->
                <div class="d-flex justify-content-between mt-3">
                    <a href="{{ route('admin.prestamos.show', $prestamo->id) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Compromiso</button>
                </div>
            </form>
        </div>
    </div>
@stop
