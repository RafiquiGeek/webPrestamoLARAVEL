@extends('layouts.admin')

@section('title', 'Desembolsar Préstamo')

@section('content')
<div class="container-fluid">
    <div class="py-4">
        <!-- Header -->
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('admin.prestamos.show', $prestamo->id) }}" class="btn btn-outline-secondary me-3">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h2 class="mb-1">
                    <i class="fa-solid fa-sack-dollar text-primary me-2"></i>
                    Desembolsar Préstamo N° {{ $prestamo->id }}
                </h2>
                <p class="text-muted mb-0">Registre los datos del desembolso del préstamo</p>
            </div>
        </div>

        <div class="row">
            <!-- Información del Préstamo -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header text-black">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Información del Préstamo
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Cliente</label>
                            <div class="fw-bold">
                                {{ $prestamo->cliente->persona->nombres }}
                                {{ $prestamo->cliente->persona->ape_pat }}
                                {{ $prestamo->cliente->persona->ape_mat }}
                            </div>
                            <small class="text-muted">DNI: {{ $prestamo->cliente->persona->documento }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Monto Solicitado</label>
                            <div class="h4 text-success mb-0">
                                S/. {{ number_format($prestamo->cantidad_solicitada, 2) }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Plazo</label>
                            <div>{{ $prestamo->plazo }} semanas</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Estado</label>
                            <div>
                                <span class="badge">{{ $prestamo->estado }}</span>
                            </div>
                        </div>

                        @if($prestamo->cuenta)
                        <div class="mb-0">
                            <label class="form-label fw-bold text-muted">Cuenta</label>
                            <div>{{ $prestamo->cuenta->codigo }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Formulario de Desembolso -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header text-black">
                        <h5 class="mb-0">
                            <i class="fa-solid fa-sack-dollar me-2"></i>
                            Datos del Desembolso
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('admin.prestamos.desembolsar', $prestamo->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-calendar me-1"></i>
                                        Fecha de Desembolso <span class="text-danger">*</span>
                                    </label>
                                    <input type="date"
                                           name="fecha_desembolso"
                                           class="form-control form-control-lg"
                                           value="{{ old('fecha_desembolso', date('Y-m-d')) }}"
                                           required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-credit-card me-1"></i>
                                        Método de Pago <span class="text-danger">*</span>
                                    </label>
                                    <select name="metodo_pago_id" class="form-select form-select-lg" required>
                                        <option value="">Seleccionar método...</option>
                                        @foreach($metodosDePago as $metodo)
                                            <option value="{{ $metodo->id }}"
                                                    {{ old('metodo_pago_id') == $metodo->id ? 'selected' : '' }}>
                                                {{ $metodo->metodo_pago }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-dollar-sign me-1"></i>
                                        Monto a Desembolsar <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text text-black fw-bold">S/.</span>
                                        <input type="number"
                                               name="monto"
                                               class="form-control"
                                               value="{{ old('monto', $prestamo->cantidad_solicitada) }}"
                                               step="0.01"
                                               min="0"
                                               readonly>
                                    </div>
                                    <small class="form-text text-muted">El monto coincide con el monto solicitado del préstamo</small>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-sticky-note me-1"></i>
                                        Observaciones
                                    </label>
                                    <textarea name="observaciones"
                                              class="form-control"
                                              rows="3"
                                              placeholder="Observaciones adicionales sobre el desembolso..."
                                              maxlength="500">{{ old('observaciones') }}</textarea>
                                    <small class="form-text text-muted">Máximo 500 caracteres</small>
                                </div>


                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-file-upload me-1"></i>
                                        Voucher o Comprobante
                                    </label>
                                    <input type="file"
                                           name="voucher"
                                           class="form-control"
                                           accept=".jpg,.jpeg,.png,.pdf">
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Formatos permitidos: JPG, PNG, PDF (máximo 2MB)
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de Acción -->
                            <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                                <a href="{{ route('admin.prestamos.show', $prestamo->id) }}"
                                   class="btn btn-secondary btn-lg">
                                    <i class="fas fa-times me-2"></i>
                                    Cancelar
                                </a>

                                <button type="submit"
                                        class="btn btn-success btn-lg px-4"
                                        onclick="return confirm('¿Está seguro de realizar el desembolso de S/. {{ number_format($prestamo->cantidad_solicitada, 2) }}?')">
                                    <i class="fa-solid fa-sack-dollar me-2"></i>
                                    Registrar Desembolso
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    .card {
        border: none;
        border-radius: 12px;
        overflow: hidden;
    }

    .card-header {
        border: none;
        padding: 1.25rem 1.5rem;
    }

    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }

    .btn {
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-success {
        background: linear-gradient(45deg, #28a745, #20c997);
        border: none;
    }

    .btn-success:hover {
        background: linear-gradient(45deg, #218838, #1ea080);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .input-group-text.bg-success {
        background: linear-gradient(45deg, #28a745, #20c997) !important;
        border: none;
    }

    .shadow-sm {
        box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.05) !important;
    }

    .form-check-input:checked {
        background-color: #28a745;
        border-color: #28a745;
    }

    .form-check-input:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }

    .form-check-label {
        font-weight: 500;
        color: #495057;
    }
</style>
@endsection