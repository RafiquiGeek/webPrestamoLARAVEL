<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-bug me-2"></i>
                        Debug Cliente 987 - Préstamo 2073
                    </h4>
                    <small class="text-white-50">Verificación de sucursales y comparación de datos</small>
                </div>

                <div class="card-body">
                    <!-- Información del Cliente 987 -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user me-2"></i>
                                        Cliente 987
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($cliente)
                                        <div class="row">
                                            <div class="col-sm-4"><strong>ID:</strong></div>
                                            <div class="col-sm-8">{{ $cliente->id }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-4"><strong>Código:</strong></div>
                                            <div class="col-sm-8">{{ $cliente->codigo }}</div>
                                        </div>
                                        @if($cliente->persona)
                                            <div class="row">
                                                <div class="col-sm-4"><strong>Nombre:</strong></div>
                                                <div class="col-sm-8">{{ $cliente->persona->nombres }} {{ $cliente->persona->ape_pat }} {{ $cliente->persona->ape_mat }}</div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-4"><strong>DNI:</strong></div>
                                                <div class="col-sm-8">{{ $cliente->persona->documento }}</div>
                                            </div>
                                        @endif
                                        <hr>
                                        <div class="row">
                                            <div class="col-sm-4"><strong>Sucursal:</strong></div>
                                            <div class="col-sm-8">
                                                @if($sucursalCliente)
                                                    <span class="badge bg-success fs-6 px-3 py-2">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        {{ $sucursalCliente }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning fs-6 px-3 py-2">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        Sin asignar
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Cliente 987 no encontrado en la base de datos
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Información del Préstamo 2073 -->
                        <div class="col-md-6">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-file-invoice-dollar me-2"></i>
                                        Préstamo 2073
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($prestamo)
                                        <div class="row">
                                            <div class="col-sm-4"><strong>ID:</strong></div>
                                            <div class="col-sm-8">{{ $prestamo->id }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-4"><strong>Cliente ID:</strong></div>
                                            <div class="col-sm-8">{{ $prestamo->cliente_id }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-4"><strong>Estado:</strong></div>
                                            <div class="col-sm-8">
                                                <span class="badge bg-secondary">{{ $prestamo->estado }}</span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-4"><strong>Monto:</strong></div>
                                            <div class="col-sm-8">S/ {{ number_format($prestamo->cantidad_solicitada, 2) }}</div>
                                        </div>
                                        <hr>
                                        <div class="row">
                                            <div class="col-sm-4"><strong>Sucursal:</strong></div>
                                            <div class="col-sm-8">
                                                @if($sucursalPrestamo)
                                                    <span class="badge bg-success fs-6 px-3 py-2">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        {{ $sucursalPrestamo }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning fs-6 px-3 py-2">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        Sin asignar
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Préstamo 2073 no encontrado en la base de datos
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Comparación de Sucursales -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card {{ $sucursalesCoinciden ? 'border-success' : 'border-danger' }}">
                                <div class="card-header {{ $sucursalesCoinciden ? 'bg-success' : 'bg-danger' }} text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-balance-scale me-2"></i>
                                        Comparación de Sucursales
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-5">
                                            <div class="p-3 border rounded">
                                                <h6 class="text-muted mb-2">Cliente 987</h6>
                                                <span class="badge fs-5 px-4 py-3 {{ $sucursalCliente ? 'bg-primary' : 'bg-warning' }}">
                                                    {{ $sucursalCliente ?: 'SIN ASIGNAR' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-2 d-flex align-items-center justify-content-center">
                                            @if($sucursalesCoinciden)
                                                <div class="text-center">
                                                    <i class="fas fa-equals fa-3x text-success"></i>
                                                    <div class="mt-2">
                                                        <span class="badge bg-success fs-6 px-3 py-2">
                                                            <i class="fas fa-check-circle me-1"></i>
                                                            COINCIDEN
                                                        </span>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="text-center">
                                                    <i class="fas fa-not-equal fa-3x text-danger"></i>
                                                    <div class="mt-2">
                                                        <span class="badge bg-danger fs-6 px-3 py-2">
                                                            <i class="fas fa-times-circle me-1"></i>
                                                            DIFERENTES
                                                        </span>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="col-md-5">
                                            <div class="p-3 border rounded">
                                                <h6 class="text-muted mb-2">Préstamo 2073</h6>
                                                <span class="badge fs-5 px-4 py-3 {{ $sucursalPrestamo ? 'bg-primary' : 'bg-warning' }}">
                                                    {{ $sucursalPrestamo ?: 'SIN ASIGNAR' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    @if(!$sucursalesCoinciden)
                                        <div class="alert alert-warning mt-4">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>¡Atención!</strong> Las sucursales del cliente y el préstamo son diferentes.
                                            Esto puede indicar un problema de sincronización de datos.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de Debug Técnico -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-dark text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-terminal me-2"></i>
                                        Información Técnica (Consola del Navegador)
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-2">
                                        Los datos completos se han enviado a la consola del navegador para análisis técnico.
                                        Presiona F12 → Console para ver los detalles.
                                    </p>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="console.log('Debug Data:', {!! json_encode([$cliente, $prestamo, $sucursalCliente, $sucursalPrestamo, $sucursalesCoinciden]) !!})">
                                        <i class="fas fa-code me-1"></i>
                                        Mostrar en Consola
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:init', function() {
    Livewire.on('debugDataLoaded', (data) => {
        console.log('🔍 DEBUG CLIENTE 987 - PRÉSTAMO 2073');
        console.log('=====================================');
        console.log('Cliente 987:', data.cliente);
        console.log('Préstamo 2073:', data.prestamo);
        console.log('Sucursal Cliente:', data.sucursalCliente);
        console.log('Sucursal Préstamo:', data.sucursalPrestamo);
        console.log('Sucursales Coinciden:', data.sucursalesCoinciden);
        console.log('=====================================');

        if (data.sucursalesCoinciden) {
            console.log('✅ RESULTADO: Las sucursales coinciden correctamente');
        } else {
            console.log('❌ RESULTADO: Las sucursales son diferentes - posible problema de sincronización');
        }
    });
});
</script>