@extends('layouts.admin')

@section('title', 'Registrar Nuevo Gasto')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-receipt mr-2"></i>Registrar Nuevo Gasto</h1>
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.gastos.index') }}">Gastos</a></li>
            <li class="breadcrumb-item active">Nuevo</li>
        </ol>
    </div>
@stop

@section('content')
<div class="container-fluid">
            <form action="{{ route('admin.gastos.store') }}" method="POST" id="formGasto">
                @csrf
                <div class="row">
                    <div class="col-md-8">
                        <!-- Información del Gasto -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-receipt mr-1"></i>
                                    Información del Gasto
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="categoria_gasto_id">Categoría *</label>
                                            <select class="form-control @error('categoria_gasto_id') is-invalid @enderror" 
                                                    name="categoria_gasto_id" id="categoria_gasto_id" required>
                                                <option value="">Seleccione una categoría...</option>
                                                @foreach($categorias as $categoria)
                                                    <option value="{{ $categoria->id }}" 
                                                        {{ old('categoria_gasto_id') == $categoria->id ? 'selected' : '' }}>
                                                        {{ $categoria->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('categoria_gasto_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="fecha_gasto">Fecha del Gasto *</label>
                                            <input type="date" class="form-control @error('fecha_gasto') is-invalid @enderror" 
                                                   name="fecha_gasto" id="fecha_gasto" 
                                                   value="{{ old('fecha_gasto', date('Y-m-d')) }}" 
                                                   max="{{ date('Y-m-d') }}" required>
                                            @error('fecha_gasto')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="concepto">Concepto *</label>
                                            <input type="text" class="form-control @error('concepto') is-invalid @enderror" 
                                                   name="concepto" id="concepto" 
                                                   value="{{ old('concepto') }}" 
                                                   maxlength="200" required
                                                   placeholder="Ej: Compra de materiales de oficina">
                                            @error('concepto')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="monto">Monto *</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">S/</span>
                                                </div>
                                                <input type="number" class="form-control @error('monto') is-invalid @enderror" 
                                                       name="monto" id="monto" 
                                                       value="{{ old('monto') }}" 
                                                       step="0.01" min="0.01" max="999999.99" required
                                                       placeholder="0.00">
                                            </div>
                                            @error('monto')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="descripcion">Descripción</label>
                                    <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                              name="descripcion" id="descripcion" rows="3"
                                              placeholder="Descripción detallada del gasto...">{{ old('descripcion') }}</textarea>
                                    @error('descripcion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Información del Beneficiario -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-user mr-1"></i>
                                    Información del Beneficiario
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="tipo_documento">Tipo de Documento *</label>
                                            <select class="form-control @error('tipo_documento') is-invalid @enderror" 
                                                    name="tipo_documento" id="tipo_documento" required>
                                                <option value="">Seleccione...</option>
                                                <option value="DNI" {{ old('tipo_documento') == 'DNI' ? 'selected' : '' }}>DNI</option>
                                                <option value="RUC" {{ old('tipo_documento') == 'RUC' ? 'selected' : '' }}>RUC</option>
                                                <option value="CE" {{ old('tipo_documento') == 'CE' ? 'selected' : '' }}>Carné de Extranjería</option>
                                                <option value="PAS" {{ old('tipo_documento') == 'PAS' ? 'selected' : '' }}>Pasaporte</option>
                                            </select>
                                            @error('tipo_documento')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="documento_identidad">Número de Documento *</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control @error('documento_identidad') is-invalid @enderror" 
                                                       name="documento_identidad" id="documento_identidad" 
                                                       value="{{ old('documento_identidad') }}" 
                                                       maxlength="20" required>
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-primary" id="btnConsultar" onclick="consultarDocumento()">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            @error('documento_identidad')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Campos para empresa (RUC) -->
                                <div id="campos-empresa" style="display: none;">
                                    <div class="form-group">
                                        <label for="razon_social">Razón Social *</label>
                                        <input type="text" class="form-control @error('razon_social') is-invalid @enderror" 
                                               name="razon_social" id="razon_social" 
                                               value="{{ old('razon_social') }}" 
                                               maxlength="200">
                                        @error('razon_social')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Campos para persona natural -->
                                <div id="campos-persona">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="nombres">Nombres *</label>
                                                <input type="text" class="form-control @error('nombres') is-invalid @enderror" 
                                                       name="nombres" id="nombres" 
                                                       value="{{ old('nombres') }}" 
                                                       maxlength="100">
                                                @error('nombres')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="apellidos">Apellidos *</label>
                                                <input type="text" class="form-control @error('apellidos') is-invalid @enderror" 
                                                       name="apellidos" id="apellidos" 
                                                       value="{{ old('apellidos') }}" 
                                                       maxlength="100">
                                                @error('apellidos')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Información del Comprobante -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-file-invoice mr-1"></i>
                                    Comprobante de Pago
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="tipo_comprobante">Tipo de Comprobante *</label>
                                    <select class="form-control @error('tipo_comprobante') is-invalid @enderror" 
                                            name="tipo_comprobante" id="tipo_comprobante" required>
                                        <option value="">Seleccione...</option>
                                        <option value="factura" {{ old('tipo_comprobante') == 'factura' ? 'selected' : '' }}>Factura</option>
                                        <option value="boleta" {{ old('tipo_comprobante') == 'boleta' ? 'selected' : '' }}>Boleta de Venta</option>
                                        <option value="recibo_honorarios" {{ old('tipo_comprobante') == 'recibo_honorarios' ? 'selected' : '' }}>Recibo por Honorarios</option>
                                        <option value="ticket" {{ old('tipo_comprobante') == 'ticket' ? 'selected' : '' }}>Ticket</option>
                                        <option value="sin_documento" {{ old('tipo_comprobante') == 'sin_documento' ? 'selected' : '' }}>Sin Documento de Pago</option>
                                    </select>
                                    @error('tipo_comprobante')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div id="campos-comprobante">
                                    <div class="form-group">
                                        <label for="serie_comprobante">Serie</label>
                                        <input type="text" class="form-control @error('serie_comprobante') is-invalid @enderror" 
                                               name="serie_comprobante" id="serie_comprobante" 
                                               value="{{ old('serie_comprobante') }}" 
                                               maxlength="10" placeholder="Ej: F001">
                                        @error('serie_comprobante')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="numero_comprobante">Número *</label>
                                        <input type="text" class="form-control @error('numero_comprobante') is-invalid @enderror" 
                                               name="numero_comprobante" id="numero_comprobante" 
                                               value="{{ old('numero_comprobante') }}" 
                                               maxlength="20" placeholder="Ej: 001234">
                                        @error('numero_comprobante')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-comment mr-1"></i>
                                    Observaciones
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <textarea class="form-control @error('observaciones') is-invalid @enderror" 
                                              name="observaciones" id="observaciones" rows="4"
                                              placeholder="Observaciones adicionales...">{{ old('observaciones') }}</textarea>
                                    @error('observaciones')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="card">
                            <div class="card-body">
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-save mr-1"></i> Registrar Gasto
                                </button>
                                <a href="{{ route('admin.gastos.index') }}" class="btn btn-outline-secondary btn-block mt-2">
                                    <i class="fas fa-times mr-1"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection

@section('js')
<script>
let consultandoDocumento = false;

// Manejar cambio de tipo de documento
document.getElementById('tipo_documento').addEventListener('change', function() {
    const tipo = this.value;
    const camposEmpresa = document.getElementById('campos-empresa');
    const camposPersona = document.getElementById('campos-persona');
    
    if (tipo === 'RUC') {
        camposEmpresa.style.display = 'block';
        camposPersona.style.display = 'none';
        document.getElementById('razon_social').required = true;
        document.getElementById('nombres').required = false;
        document.getElementById('apellidos').required = false;
    } else {
        camposEmpresa.style.display = 'none';
        camposPersona.style.display = 'block';
        document.getElementById('razon_social').required = false;
        document.getElementById('nombres').required = true;
        document.getElementById('apellidos').required = true;
    }
    
    // Limpiar campos
    document.getElementById('documento_identidad').value = '';
    document.getElementById('razon_social').value = '';
    document.getElementById('nombres').value = '';
    document.getElementById('apellidos').value = '';
});

// Manejar cambio de tipo de comprobante
document.getElementById('tipo_comprobante').addEventListener('change', function() {
    const tipo = this.value;
    const camposComprobante = document.getElementById('campos-comprobante');
    const numeroComprobante = document.getElementById('numero_comprobante');
    
    if (tipo === 'sin_documento') {
        camposComprobante.style.display = 'none';
        numeroComprobante.required = false;
    } else {
        camposComprobante.style.display = 'block';
        numeroComprobante.required = true;
    }
});

// Consultar documento
async function consultarDocumento() {
    const tipoDocumento = document.getElementById('tipo_documento').value;
    const documento = document.getElementById('documento_identidad').value.trim();
    
    if (!tipoDocumento) {
        alert('Seleccione el tipo de documento primero');
        return;
    }
    
    if (!documento) {
        alert('Ingrese el número de documento');
        return;
    }
    
    if (consultandoDocumento) return;
    consultandoDocumento = true;
    
    const btn = document.getElementById('btnConsultar');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    try {
        const response = await fetch('{{ route('admin.gastos.consultarDocumento') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                documento: documento,
                tipo: tipoDocumento
            })
        });
        
        const data = await response.json();
        
        if (data.valid) {
            if (tipoDocumento === 'RUC') {
                document.getElementById('razon_social').value = data.data.razon_social;
            } else {
                const nombres = data.data.nombres.split(' ');
                document.getElementById('nombres').value = nombres[0] + (nombres[1] ? ' ' + nombres[1] : '');
                document.getElementById('apellidos').value = data.data.apellidos;
            }
            
            // Mostrar mensaje de éxito
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: 'Datos cargados correctamente',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                alert('Datos cargados correctamente');
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Documento no encontrado',
                    text: 'Puede continuar ingresando los datos manualmente'
                });
            } else {
                alert('Documento no encontrado. Puede continuar ingresando los datos manualmente');
            }
        }
    } catch (error) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al consultar el documento'
            });
        } else {
            alert('Error al consultar el documento');
        }
    } finally {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        consultandoDocumento = false;
    }
}

// Trigger inicial
document.getElementById('tipo_documento').dispatchEvent(new Event('change'));
document.getElementById('tipo_comprobante').dispatchEvent(new Event('change'));
</script>
@endsection