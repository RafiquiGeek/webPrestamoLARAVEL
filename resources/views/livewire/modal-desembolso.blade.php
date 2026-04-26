<div>
    <!-- DEBUG INFO (remover en producción) -->
    @if($debug)
        <div style="position: fixed; top: 10px; right: 10px; background: yellow; padding: 10px; z-index: 9999; border-radius: 5px;">
            <strong>Modal Debug:</strong><br>
            modalOpen: {{ $modalOpen ? 'true' : 'false' }}<br>
            prestamoId: {{ $prestamoId ?? 'null' }}<br>
            loading: {{ $loading ? 'true' : 'false' }}
        </div>
    @endif

    <!-- Modal Livewire para Desembolso con Backdrop integrado -->
    @if($modalOpen)
        <!-- Backdrop -->
        <div class="modal-backdrop fade show" style="z-index: 1040;" wire:click="cerrarModal"></div>
    @endif

    <div class="modal fade @if($modalOpen) show @endif"
         id="modalDesembolsoLivewire"
         tabindex="-1"
         aria-labelledby="modalDesembolsoLabel"
         aria-hidden="@if(!$modalOpen) true @else false @endif"
         style="@if($modalOpen) display: block !important; z-index: 1050; @else display: none; @endif"
         wire:ignore.self>

        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">

                <!-- Header del Modal -->
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title d-flex align-items-center" id="modalDesembolsoLabel">
                        <i class="fas fa-hand-holding-usd me-2"></i>
                        <span>Registrar Desembolso</span>
                        @if($prestamo)
                            <small class="ms-2 opacity-75">
                                - {{ $prestamo->cliente->persona->nombres ?? 'Cliente' }}
                            </small>
                        @endif
                    </h5>
                    <button type="button"
                            class="btn-close btn-close-white"
                            wire:click="cerrarModal"
                            aria-label="Close">
                    </button>
                </div>

                <!-- Body del Modal -->
                <div class="modal-body p-4">
                    @if($prestamo)
                        <!-- Información del Préstamo -->
                        <div class="alert alert-info border-0 mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-1">
                                        <i class="fas fa-user me-1"></i>
                                        {{ $prestamo->cliente->persona->nombres }}
                                        {{ $prestamo->cliente->persona->ape_pat }}
                                    </h6>
                                    <small class="text-muted">DNI: {{ $prestamo->cliente->persona->documento }}</small>
                                </div>
                                <div class="col-md-6 text-end">
                                    <h6 class="fw-bold mb-1">
                                        <i class="fas fa-hashtag me-1"></i>
                                        Préstamo #{{ $prestamo->id }}
                                    </h6>
                                    <small class="text-muted">{{ $prestamo->created_at->format('d/m/Y') }}</small>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Formulario -->
                    <form wire:submit.prevent="confirmarDesembolso" class="needs-validation" novalidate>

                        <!-- Monto a Desembolsar -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="monto" class="form-label fw-bold">
                                    <i class="fas fa-dollar-sign text-success me-1"></i>
                                    Monto a Desembolsar
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white">S/.</span>
                                    <input type="text"
                                           class="form-control fw-bold text-end"
                                           value="{{ number_format($monto, 2) }}"
                                           readonly>
                                </div>
                            </div>

                            <!-- Fecha de Desembolso -->
                            <div class="col-md-6">
                                <label for="fecha" class="form-label fw-bold">
                                    <i class="fas fa-calendar text-primary me-1"></i>
                                    Fecha de Desembolso
                                </label>
                                <input type="date"
                                       class="form-control @error('fecha') is-invalid @enderror"
                                       wire:model.live="fecha"
                                       max="{{ date('Y-m-d') }}"
                                       required>
                                @error('fecha')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Usuario y Método de Pago -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="userId" class="form-label fw-bold">
                                    <i class="fas fa-user-tie text-info me-1"></i>
                                    Usuario que realiza el desembolso
                                </label>
                                <select class="form-select @error('userId') is-invalid @enderror"
                                        wire:model.live="userId"
                                        required>
                                    <option value="">Seleccionar usuario</option>
                                    @foreach($usuarios as $usuario)
                                        <option value="{{ $usuario->id }}">{{ $usuario->name }}</option>
                                    @endforeach
                                </select>
                                @error('userId')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-credit-card text-warning me-1"></i>
                                    Método de Pago
                                </label>
                                <div class="d-flex gap-3 mt-2">
                                    @foreach($metodosPago as $metodo)
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="radio"
                                                   wire:model.live="metodoPagoId"
                                                   value="{{ $metodo->id }}"
                                                   id="metodo{{ $metodo->id }}">
                                            <label class="form-check-label" for="metodo{{ $metodo->id }}">
                                                @if($metodo->metodo_pago == 'EFECTIVO')
                                                    <i class="fas fa-money-bill-wave text-success me-1"></i>
                                                @elseif($metodo->metodo_pago == 'TRANSFERENCIA')
                                                    <i class="fas fa-exchange-alt text-primary me-1"></i>
                                                @else
                                                    <i class="fas fa-credit-card text-info me-1"></i>
                                                @endif
                                                {{ $metodo->metodo_pago }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Número de Operación (condicional) -->
                        @if($metodoPagoId != 1)
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label for="nroOperacion" class="form-label fw-bold">
                                        <i class="fas fa-hashtag text-primary me-1"></i>
                                        Número de Operación
                                    </label>
                                    <input type="text"
                                           class="form-control @error('nroOperacion') is-invalid @enderror"
                                           wire:model.live="nroOperacion"
                                           placeholder="Ingrese el número de operación"
                                           maxlength="50">
                                    @error('nroOperacion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endif

                        <!-- Imagen de Depósito -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="imagenDeposito" class="form-label fw-bold">
                                    <i class="fas fa-image text-secondary me-1"></i>
                                    Imagen de Depósito (opcional)
                                </label>
                                <input type="file"
                                       class="form-control @error('imagenDeposito') is-invalid @enderror"
                                       wire:model="imagenDeposito"
                                       accept="image/*">
                                @error('imagenDeposito')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                <!-- Loading indicator for file upload -->
                                <div wire:loading wire:target="imagenDeposito" class="mt-2">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Subiendo...</span>
                                    </div>
                                    <small class="text-muted ms-2">Subiendo imagen...</small>
                                </div>
                            </div>

                            <!-- Preview de la imagen -->
                            <div class="col-md-6">
                                @if($imagenDeposito)
                                    <div class="text-center">
                                        <label class="form-label fw-bold text-success">
                                            <i class="fas fa-check-circle me-1"></i>
                                            Vista Previa
                                        </label>
                                        <div class="border rounded p-2">
                                            <img src="{{ $imagenDeposito->temporaryUrl() }}"
                                                 class="img-fluid rounded"
                                                 style="max-height: 100px;"
                                                 alt="Vista previa">
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Comprobante -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tieneComprobante" class="form-label fw-bold">
                                    <i class="fas fa-file-invoice text-purple me-1"></i>
                                    ¿Tendrá comprobante?
                                </label>
                                <select class="form-select @error('tieneComprobante') is-invalid @enderror"
                                        wire:model.live="tieneComprobante"
                                        required>
                                    <option value="1">
                                        <i class="fas fa-check"></i> Sí - Emitir comprobantes
                                    </option>
                                    <option value="0">
                                        <i class="fas fa-times"></i> No - Sin comprobantes
                                    </option>
                                </select>
                                @error('tieneComprobante')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 d-flex align-items-end">
                                <div class="alert alert-info mb-0 w-100 py-2">
                                    @if($tieneComprobante == 1)
                                        <i class="fas fa-info-circle me-1"></i>
                                        <small>Se podrán emitir comprobantes durante los pagos</small>
                                    @else
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        <small>No se emitirán comprobantes electrónicos</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </form>
                </div>

                <!-- Footer del Modal -->
                <div class="modal-footer bg-light">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            wire:click="cerrarModal"
                            @if($loading) disabled @endif>
                        <i class="fas fa-times me-1"></i>
                        Cancelar
                    </button>

                    <button type="button"
                            class="btn btn-success"
                            wire:click="confirmarDesembolso"
                            @if($loading) disabled @endif>
                        @if($loading)
                            <div class="spinner-border spinner-border-sm me-2" role="status">
                                <span class="visually-hidden">Procesando...</span>
                            </div>
                            Procesando...
                        @else
                            <i class="fas fa-check-circle me-1"></i>
                            Confirmar Desembolso
                        @endif
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- Estilos del modal -->
    <style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .text-purple {
        color: #6f42c1;
    }

    .modal-content {
        border-radius: 15px;
        overflow: hidden;
    }

    .form-check-input:checked {
        background-color: #28a745;
        border-color: #28a745;
    }

    .alert {
        border-radius: 10px;
    }

    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .input-group-text.bg-success {
        border-color: #28a745;
    }

    .btn {
        border-radius: 8px;
        font-weight: 500;
    }

    .modal-dialog-centered {
        min-height: calc(100% - 3.5rem);
    }

    @media (max-width: 576px) {
        .modal-dialog {
            margin: 1rem;
        }

        .modal-body {
            padding: 1rem !important;
        }
    }
    </style>

    <!-- JavaScript del modal -->
    <script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('modal-opened', () => {
            document.body.classList.add('modal-open');
            document.body.style.paddingRight = '17px';
        });

        Livewire.on('modal-closed', () => {
            document.body.classList.remove('modal-open');
            document.body.style.paddingRight = '';
        });

        Livewire.on('show-success', (message) => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: message,
                    timer: 3000,
                    showConfirmButton: false
                });
            } else {
                alert(message);
            }
        });

        Livewire.on('show-error', (message) => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            } else {
                alert('Error: ' + message);
            }
        });
    });
    </script>
</div>
