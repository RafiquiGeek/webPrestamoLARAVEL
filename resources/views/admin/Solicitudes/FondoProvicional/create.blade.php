@extends('layouts.admin')

@section('title', 'Fondo Provicional')

@section('content_header')
    <h1 class="m-0 text-dark">Nuevo Fondo Provicional</h1>
@stop

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0">
                <i class="fas fa-wallet mr-2"></i>Registrar Fondo Provisional
            </h3>
        </div>
        <form action="{{ route('admin.fondo-provisional.store') }}" method="POST">
            @csrf
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <h5><i class="icon fas fa-ban"></i> Error!</h5>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <input type="hidden" name="prestamo_id" value="{{ $prestamo->id }}">

                <!-- Información del Préstamo -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-info-circle text-info"></i> Información del Préstamo</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Cliente:</strong> {{ $prestamo->cliente->persona->nombres }} {{ $prestamo->cliente->persona->ape_pat }} {{ $prestamo->cliente->persona->ape_mat }}</p>
                                        <p class="mb-2"><strong>DNI:</strong> {{ $prestamo->cliente->persona->documento }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Préstamo ID:</strong> #{{ $prestamo->id }}</p>
                                        <p class="mb-2"><strong>Monto Capital:</strong> <span class="badge badge-primary badge-lg">S/ {{ number_format($montoCapital, 2) }}</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Campos del Formulario -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="monto_capital">Monto del Capital <span class="text-danger">*</span></label>
                            <input type="number"
                                   class="form-control @error('monto_capital') is-invalid @enderror"
                                   id="monto_capital"
                                   name="monto_capital"
                                   step="0.01"
                                   value="{{ old('monto_capital', $montoCapital) }}"
                                   readonly>
                            @error('monto_capital')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="monto_fondo">Monto Sugerido (5%) <span class="text-danger">*</span></label>
                            <input type="number"
                                   class="form-control @error('monto_fondo') is-invalid @enderror"
                                   id="monto_fondo"
                                   name="monto_fondo"
                                   step="0.01"
                                   value="{{ old('monto_fondo', $montoFondo) }}"
                                   readonly>
                            <small class="form-text text-muted">5% del capital</small>
                            @error('monto_fondo')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="monto_personalizado">Monto a Registrar <span class="text-danger">*</span></label>
                            <input type="number"
                                   class="form-control @error('monto_personalizado') is-invalid @enderror"
                                   id="monto_personalizado"
                                   name="monto_personalizado"
                                   step="0.01"
                                   min="0.01"
                                   max="{{ $montoFondo }}"
                                   value="{{ old('monto_personalizado', $montoFondo) }}"
                                   required>
                            <small class="form-text text-muted">Máximo: S/ {{ number_format($montoFondo, 2) }}</small>
                            @error('monto_personalizado')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="fecha_entrega">Fecha de Entrega <span class="text-danger">*</span></label>
                            <input type="date"
                                   class="form-control @error('fecha_entrega') is-invalid @enderror"
                                   id="fecha_entrega"
                                   name="fecha_entrega"
                                   value="{{ old('fecha_entrega', date('Y-m-d')) }}"
                                   required>
                            @error('fecha_entrega')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="observaciones">Observaciones</label>
                            <textarea class="form-control @error('observaciones') is-invalid @enderror"
                                      id="observaciones"
                                      name="observaciones"
                                      rows="3"
                                      maxlength="1000"
                                      placeholder="Ingrese observaciones adicionales (opcional)">{{ old('observaciones') }}</textarea>
                            <small class="form-text text-muted">Máximo 1000 caracteres</small>
                            @error('observaciones')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Nota:</strong> Al registrar el fondo provisional, se creará automáticamente una operación de pago asociada al préstamo.
                </div>
            </div>

            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.prestamos.show', $prestamo->id) }}" class="btn btn-secondary">
                        <i class="fas fa-times mr-1"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-1"></i>Registrar Fondo Provisional
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@stop

@section('js')
<script>
    // Calcular porcentaje en tiempo real
    document.getElementById('monto_personalizado').addEventListener('input', function() {
        const montoCapital = parseFloat(document.getElementById('monto_capital').value) || 0;
        const montoPersonalizado = parseFloat(this.value) || 0;

        if (montoCapital > 0) {
            const porcentaje = (montoPersonalizado / montoCapital) * 100;
            const helperText = this.nextElementSibling;
            if (montoPersonalizado > 0) {
                helperText.textContent = `${porcentaje.toFixed(2)}% del capital - Máximo: S/ {{ number_format($montoFondo, 2) }}`;
            } else {
                helperText.textContent = `Máximo: S/ {{ number_format($montoFondo, 2) }}`;
            }
        }
    });
</script>
@stop
