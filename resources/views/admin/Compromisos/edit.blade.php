@extends('layouts.admin')

@section('title', 'Editar Compromiso')

@section('content_header')
    <h1 class="text-center font-weight-bold">Editar Compromiso</h1>
    <p class="text-muted text-center">Actualice los detalles del compromiso de pago.</p>
@stop

@section('content')
    <div class="card shadow-lg">
        <div class="card-body">
            <form action="{{ route('admin.compromisos.update', $compromiso->id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Relación con Gestión -->
                <input type="hidden" name="gestion_id" value="{{ $compromiso->gestion_id }}">

                <div class="row">
                    <!-- Fecha de Compromiso -->
                    <div class="form-group col-md-3">
                        <label for="fecha" class="font-weight-bold">Fecha de Compromiso <span class="text-danger">*</span></label>
                        <input type="date" name="fecha" id="fecha" class="form-control" value="{{ old('fecha', $compromiso->fecha_compromiso_pago) }}" required>
                        @error('fecha')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Hora de Compromiso -->
                    <div class="form-group col-md-3">
                        <label for="hora" class="font-weight-bold">Hora de Compromiso <span class="text-danger">*</span></label>
                        <div class="d-flex">
                            <select name="hora_hh" id="hora_hh" class="form-control mr-2" required>
                                <option value="" disabled>HH</option>
                                @for($i = 8; $i < 20; $i++)
                                    @php
                                        $hora_formateada = str_pad($i, 2, '0', STR_PAD_LEFT);
                                    @endphp
                                    <option value="{{ $hora_formateada }}" {{ old('hora_hh', explode(':', $compromiso->hora)[0]) == $hora_formateada ? 'selected' : '' }}>
                                        {{ $hora_formateada }}
                                    </option>
                                @endfor
                            </select>
                            <span class="align-self-center">:</span>
                            <select name="hora_mm" id="hora_mm" class="form-control ml-2" required>
                                <option value="" disabled>MM</option>
                                @foreach(['00', '15', '30', '45'] as $minuto)
                                    <option value="{{ $minuto }}" {{ old('hora_mm', explode(':', $compromiso->hora)[1]) == $minuto ? 'selected' : '' }}>
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
                    <div class="form-group col-md-3">
                        <label for="monto" class="font-weight-bold">Monto <span class="text-danger">*</span></label>
                        <input type="number" name="monto" id="monto" class="form-control" value="{{ old('monto', $compromiso->monto) }}" step="0.01" min="0" required>
                        @error('monto')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Estado -->
                    <div class="form-group col-md-3">
                        <label for="estado" class="font-weight-bold">Estado <span class="text-danger">*</span></label>
                        <select name="estado" id="estado" class="form-control" required>
                            <option value="{{ \App\Models\Compromiso::ESTADO_PENDIENTE }}" {{ old('estado', $compromiso->estado) == \App\Models\Compromiso::ESTADO_PENDIENTE ? 'selected' : '' }}>Pendiente</option>
                            <option value="{{ \App\Models\Compromiso::ESTADO_PAGADO }}" {{ old('estado', $compromiso->estado) == \App\Models\Compromiso::ESTADO_PAGADO ? 'selected' : '' }}>Pagado</option>
                            <option value="{{ \App\Models\Compromiso::ESTADO_POSTERGADO }}" {{ old('estado', $compromiso->estado) == \App\Models\Compromiso::ESTADO_POSTERGADO ? 'selected' : '' }}>Postergado</option>
                        </select>
                        @error('estado')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <!-- Comentario -->
                <div class="form-group">
                    <label for="comentario" class="font-weight-bold">Comentario (Opcional)</label>
                    <textarea name="comentario" id="comentario" class="form-control" rows="3" placeholder="Ingrese un comentario">{{ old('comentario', $compromiso->comentario) }}</textarea>
                    @error('comentario')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <!-- Botones -->
                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('admin.compromisos.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar Compromiso</button>
                </div>
            </form>
        </div>
    </div>
@stop
