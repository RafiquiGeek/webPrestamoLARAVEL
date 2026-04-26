@extends('layouts.admin')

@section('title', 'Comprobante Electrónico')

@section('content')
<div class="container-fluid">
    @php
        // Extraer datos del comprobante y cuota
        $cuota = $comprobante->cuota;
        $prestamo = $comprobante->prestamo;
        $cliente = $comprobante->cliente;
        $persona = $cliente->persona ?? null;

        // Calcular desglose desde la cuota
        $capital = $cuota->pago_capital ?? 0;
        $interes = $cuota->interes ?? 0;
        $comision = $cuota->comision ?? 0;
        $seguro = $cuota->gas ?? 0;
        $igv = $cuota->igv ?? 0;

        // Totales
        $totalExonerado = $capital + $interes + $seguro;
        $totalGravado = $comision;
        $montoTotal = $totalExonerado + $totalGravado + $igv;

        // Tipo de comprobante
        $esFactura = $comprobante->tipo_comprobante === '01';
        $tipoComprobanteLabel = $esFactura ? 'FACTURA ELECTRÓNICA' : 'BOLETA DE VENTA ELECTRÓNICA';

        // Datos de la empresa
        $empresaRuc = $configuracion->ruc ?? '00000000000';
        $empresaRazonSocial = $configuracion->razon_social ?? 'EMPRESA';
        $empresaDireccion = $configuracion->direccion ?? '';
        $empresaTelefono = $configuracion->telefono ?? '';
        $empresaEmail = $configuracion->email ?? '';

        // Cliente datos
        $tipoDocLabel = strlen($persona->documento ?? '') === 11 ? 'RUC' : 'DNI';
        $clienteDocumento = $persona->documento ?? '';
        $clienteNombre = $cliente->nombre_completo ?? $persona->nombres ?? '';
        $clienteDireccion = $cliente->direccion ?? '';

        // Número completo
        $numeroCompleto = $comprobante->serie . '-' . str_pad($comprobante->numero, 6, '0', STR_PAD_LEFT);

        // Estado badge
        $estadoBadgeClass = match($comprobante->estado) {
            'ACEPTADO', 'aceptado' => 'success',
            'ERROR', 'error', 'rechazado' => 'danger',
            'PENDIENTE', 'pendiente' => 'warning',
            default => 'secondary'
        };
    @endphp

    {{-- Header con botones de acción --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">
            <i class="fas fa-file-invoice"></i> Comprobante Electrónico
        </h3>
        <div class="btn-group">
            <a href="{{ route('admin.comprobantes.pdf', $comprobante) }}" class="btn btn-danger" target="_blank">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            @if($comprobante->xml_content)
                <a href="{{ route('admin.comprobantes.xml', $comprobante) }}" class="btn btn-primary">
                    <i class="fas fa-file-code"></i> XML
                </a>
            @endif
            @if($comprobante->cdr_zip)
                <button type="button" class="btn btn-success" onclick="descargarCDR()">
                    <i class="fas fa-file-archive"></i> CDR
                </button>
            @endif
            <a href="{{ route('admin.comprobantes.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    {{-- Estado Badge --}}
    <div class="mb-3">
        <span class="badge badge-{{ $estadoBadgeClass }} badge-lg">
            <i class="fas fa-{{ $estadoBadgeClass === 'success' ? 'check-circle' : ($estadoBadgeClass === 'danger' ? 'times-circle' : 'clock') }}"></i>
            {{ strtoupper($comprobante->estado) }}
        </span>
        @if($comprobante->codigo_error)
            <span class="badge badge-dark ml-2">Código: {{ $comprobante->codigo_error }}</span>
        @endif
    </div>

    {{-- Contenedor principal del comprobante (estilo ticket) --}}
    <div class="comprobante-ticket">
        {{-- Encabezado con datos de la empresa y tipo de comprobante --}}
        <div class="comprobante-header">
            <div class="row">
                <div class="col-md-7">
                    <h4 class="mb-2 font-weight-bold">{{ $empresaRazonSocial }}</h4>
                    <p class="mb-1 small">
                        <strong>RUC:</strong> {{ $empresaRuc }}<br>
                        <strong>Dirección:</strong> {{ $empresaDireccion }}<br>
                        @if($empresaTelefono)
                            <strong>Teléfono:</strong> {{ $empresaTelefono }}<br>
                        @endif
                        @if($empresaEmail)
                            <strong>Email:</strong> {{ $empresaEmail }}
                        @endif
                    </p>
                </div>
                <div class="col-md-5 text-center">
                    <div class="comprobante-box">
                        <h6 class="mb-1">RUC {{ $empresaRuc }}</h6>
                        <h5 class="mb-1 font-weight-bold">{{ $tipoComprobanteLabel }}</h5>
                        <p class="mb-0 font-weight-bold" style="font-size: 1.1rem;">{{ $numeroCompleto }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Datos del Cliente --}}
        <div class="comprobante-section">
            <h6 class="section-title">
                <i class="fas fa-user"></i> DATOS DEL CLIENTE
            </h6>
            <div class="row small">
                <div class="col-md-6">
                    <strong>Documento:</strong> {{ $tipoDocLabel }} {{ $clienteDocumento }}
                </div>
                <div class="col-md-6">
                    <strong>Cliente:</strong> {{ $clienteNombre }}
                </div>
                @if($clienteDireccion)
                    <div class="col-12 mt-1">
                        <strong>Dirección:</strong> {{ $clienteDireccion }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Información del Comprobante --}}
        <div class="comprobante-section">
            <h6 class="section-title">
                <i class="fas fa-info-circle"></i> INFORMACIÓN DEL COMPROBANTE
            </h6>
            <div class="row small">
                <div class="col-md-6">
                    <strong>Fecha de Emisión:</strong> {{ $comprobante->fecha_emision->format('d/m/Y H:i:s') }}
                </div>
                <div class="col-md-6">
                    <strong>Moneda:</strong> {{ $comprobante->moneda === 'PEN' ? 'Soles (PEN)' : $comprobante->moneda }}
                </div>
                @if($prestamo)
                    <div class="col-md-6 mt-1">
                        <strong>Préstamo:</strong>
                        <a href="{{ route('admin.prestamos.show', $prestamo->id) }}">
                            {{ $prestamo->numero_prestamo }}
                        </a>
                    </div>
                @endif
                @if($cuota)
                    <div class="col-md-6 mt-1">
                        <strong>Cuota:</strong> #{{ $cuota->numero }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Detalle de Conceptos --}}
        <div class="comprobante-section">
            <h6 class="section-title">
                <i class="fas fa-list"></i> DETALLE DE CONCEPTOS
            </h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0" style="font-size: 0.9rem;">
                    <thead class="thead-dark">
                        <tr>
                            <th>Concepto</th>
                            <th class="text-center" style="width: 140px;">Afectación IGV</th>
                            <th class="text-right" style="width: 120px;">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($capital > 0)
                        <tr>
                            <td>
                                <strong>Capital</strong><br>
                                <small class="text-muted">Pago a capital - Cuota #{{ $cuota->numero }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-info badge-pill">EXONERADO</span>
                            </td>
                            <td class="text-right font-weight-bold">
                                S/ {{ number_format($capital, 2) }}
                            </td>
                        </tr>
                        @endif

                        @if($interes > 0)
                        <tr>
                            <td>
                                <strong>Interés</strong><br>
                                <small class="text-muted">Interés financiero sobre saldo - Cuota #{{ $cuota->numero }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-info badge-pill">EXONERADO</span><br>
                                <small class="text-muted" style="font-size: 0.7rem;">Ley IGV Art. 2</small>
                            </td>
                            <td class="text-right font-weight-bold">
                                S/ {{ number_format($interes, 2) }}
                            </td>
                        </tr>
                        @endif

                        @if($comision > 0)
                        <tr>
                            <td>
                                <strong>Comisión por Gestión</strong><br>
                                <small class="text-muted">Comisión administrativa - Cuota #{{ $cuota->numero }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-success badge-pill">GRAVADO</span><br>
                                <small class="text-muted" style="font-size: 0.7rem;">Base: S/ {{ number_format($comision, 2) }}</small>
                            </td>
                            <td class="text-right font-weight-bold">
                                S/ {{ number_format($comision, 2) }}
                            </td>
                        </tr>
                        @endif

                        @if($seguro > 0)
                        <tr>
                            <td>
                                <strong>Seguro</strong><br>
                                <small class="text-muted">Seguro de préstamo - Cuota #{{ $cuota->numero }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-info badge-pill">EXONERADO</span>
                            </td>
                            <td class="text-right font-weight-bold">
                                S/ {{ number_format($seguro, 2) }}
                            </td>
                        </tr>
                        @endif

                        @if($igv > 0)
                        <tr class="bg-light">
                            <td>
                                <strong>IGV (18%)</strong><br>
                                <small class="text-muted">Impuesto sobre comisión</small>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-secondary badge-pill">IMPUESTO</span>
                            </td>
                            <td class="text-right font-weight-bold">
                                S/ {{ number_format($igv, 2) }}
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Totales --}}
        <div class="comprobante-section">
            <div class="row justify-content-end">
                <div class="col-md-6 col-lg-5">
                    <div class="totales-box">
                        <table class="table table-sm mb-0" style="font-size: 0.9rem;">
                            <tr>
                                <td><strong>OP. EXONERADAS:</strong></td>
                                <td class="text-right">S/ {{ number_format($totalExonerado, 2) }}</td>
                                <td class="text-left small text-muted">(Capital + Interés{{ $seguro > 0 ? ' + Seguro' : '' }})</td>
                            </tr>
                            <tr>
                                <td><strong>OP. GRAVADAS:</strong></td>
                                <td class="text-right">S/ {{ number_format($totalGravado, 2) }}</td>
                                <td class="text-left small text-muted">(Comisión)</td>
                            </tr>
                            <tr>
                                <td><strong>IGV (18%):</strong></td>
                                <td class="text-right">S/ {{ number_format($igv, 2) }}</td>
                                <td class="text-left small text-muted">(Solo comisión)</td>
                            </tr>
                            <tr class="border-top-2 font-weight-bold" style="font-size: 1.1rem;">
                                <td><strong>IMPORTE TOTAL:</strong></td>
                                <td class="text-right">S/ {{ number_format($montoTotal, 2) }}</td>
                                <td></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Información Técnica SUNAT --}}
        @if($comprobante->xml_content || $comprobante->hash || $comprobante->cdr_zip)
        <div class="comprobante-section bg-light">
            <h6 class="section-title">
                <i class="fas fa-shield-alt"></i> INFORMACIÓN TÉCNICA SUNAT
            </h6>
            <div class="row small">
                @if($comprobante->xml_content)
                <div class="col-md-4">
                    <i class="fas fa-check text-success"></i> <strong>XML Firmado:</strong> Generado
                </div>
                @endif
                @if($comprobante->cdr_zip)
                <div class="col-md-4">
                    <i class="fas fa-check text-success"></i> <strong>CDR SUNAT:</strong> Recibido
                </div>
                @endif
                @if($comprobante->hash)
                <div class="col-md-12 mt-2">
                    <strong>Hash SHA-256:</strong>
                    <code style="font-size: 0.7rem; word-break: break-all;">{{ $comprobante->hash }}</code>
                </div>
                @endif
                @if($comprobante->sunat_mensaje)
                <div class="col-md-12 mt-2">
                    <strong>Mensaje SUNAT:</strong> {{ $comprobante->sunat_mensaje }}
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Mensaje de Error si existe --}}
        @if($comprobante->codigo_error || $comprobante->mensaje_error)
        <div class="alert alert-danger mt-3">
            <h6 class="alert-heading">
                <i class="fas fa-exclamation-triangle"></i> Error de SUNAT
            </h6>
            @if($comprobante->codigo_error)
                <p class="mb-1"><strong>Código:</strong> <code>{{ $comprobante->codigo_error }}</code></p>
            @endif
            @if($comprobante->mensaje_error)
                <p class="mb-0"><strong>Mensaje:</strong> {{ $comprobante->mensaje_error }}</p>
            @endif
            <hr>
            <button type="button" class="btn btn-warning btn-sm" onclick="reenviarComprobante()">
                <i class="fas fa-redo"></i> Reintentar Envío
            </button>
        </div>
        @endif

        {{-- Información Adicional --}}
        <div class="alert alert-info mt-3" style="font-size: 0.85rem;">
            <p class="mb-1"><i class="fas fa-info-circle"></i> <strong>Información:</strong></p>
            <ul class="mb-0 pl-3">
                <li>Este comprobante fue {{ $comprobante->xml_content ? 'enviado a SUNAT' : 'generado en el sistema' }}</li>
                @if($comprobante->cdr_zip)
                    <li>CDR (Constancia de Recepción) recibido correctamente de SUNAT</li>
                @endif
                <li>ID Interno: #{{ $comprobante->id }}</li>
                <li>Creado: {{ $comprobante->created_at->format('d/m/Y H:i:s') }}</li>
            </ul>
        </div>

        {{-- Botones de acción adicionales --}}
        <div class="text-center mt-3">
            <button type="button" class="btn btn-info" onclick="consultarEstadoSunat()">
                <i class="fas fa-sync"></i> Consultar Estado en SUNAT
            </button>
            <button type="button" class="btn btn-warning" onclick="regularizarConSunat()">
                <i class="fas fa-sync-alt"></i> Regularizar con SUNAT
            </button>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .comprobante-ticket {
        max-width: 900px;
        margin: 0 auto;
        background: white;
        border: 2px solid #333;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }

    .comprobante-header {
        padding: 20px;
        border-bottom: 2px solid #333;
        background: #f8f9fa;
    }

    .comprobante-box {
        border: 2px solid #dc3545;
        padding: 15px;
        background: white;
        border-radius: 5px;
    }

    .comprobante-box h5,
    .comprobante-box h6 {
        color: #dc3545;
        margin-bottom: 5px;
    }

    .comprobante-section {
        padding: 15px 20px;
        border-bottom: 1px solid #ddd;
    }

    .comprobante-section:last-child {
        border-bottom: none;
    }

    .section-title {
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #333;
        color: #333;
        font-weight: bold;
    }

    .totales-box {
        border: 1px solid #ddd;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 5px;
    }

    .border-top-2 {
        border-top: 2px solid #333 !important;
    }

    .badge-lg {
        padding: 0.5em 1em;
        font-size: 1em;
    }

    .badge-pill {
        font-size: 0.75rem;
        padding: 0.3em 0.7em;
    }

    @media print {
        .btn-group,
        .alert,
        .text-center.mt-3 {
            display: none !important;
        }

        .comprobante-ticket {
            border: 1px solid #000;
            box-shadow: none;
        }
    }
</style>
@stop

@section('js')
<script>
function descargarCDR() {
    window.open('{{ route("admin.comprobantes.cdr", $comprobante) }}', '_blank');
}

function consultarEstadoSunat() {
    Swal.fire({
        title: 'Consultando SUNAT...',
        text: 'Verificando estado del comprobante',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch('{{ route("admin.comprobantes.consultar-estado", $comprobante) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        if (data.success) {
            Swal.fire({
                icon: data.aceptado ? 'success' : 'info',
                title: data.aceptado ? '¡Comprobante Aceptado!' : 'Estado del Comprobante',
                html: `
                    <div class="text-left">
                        <p><strong>Estado:</strong> ${data.estado || 'No disponible'}</p>
                        ${data.codigo_respuesta ? `<p><strong>Código:</strong> ${data.codigo_respuesta}</p>` : ''}
                        ${data.descripcion ? `<p><strong>Descripción:</strong> ${data.descripcion}</p>` : ''}
                        ${data.mensaje_sunat ? `<p><strong>SUNAT:</strong> ${data.mensaje_sunat}</p>` : ''}
                        ${data.tiene_cdr ? '<p class="text-success"><i class="fas fa-check"></i> CDR recibido</p>' : '<p class="text-warning"><i class="fas fa-exclamation"></i> Sin CDR</p>'}
                    </div>
                `,
                confirmButtonText: 'Aceptar'
            }).then(() => {
                if (data.actualizado) location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: data.message
            });
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo consultar el estado'
        });
    });
}

function reenviarComprobante() {
    Swal.fire({
        title: '¿Reenviar a SUNAT?',
        text: 'Este comprobante será reenviado a SUNAT',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, reenviar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Reenviando...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route("admin.comprobantes.reenviar", $comprobante) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Enviado!',
                        html: data.message
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: data.message
                    });
                }
            });
        }
    });
}

function regularizarConSunat() {
    Swal.fire({
        title: '¿Regularizar con SUNAT?',
        html: 'Se consultará el estado real en SUNAT y se actualizará la base de datos con los datos oficiales.<br><br><strong>Esto sobrescribirá los datos actuales del sistema.</strong>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, regularizar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Consultando SUNAT...',
                html: 'Por favor espere mientras se consulta el estado real del comprobante',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route("admin.comprobantes.regularizar", $comprobante) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Regularizado!',
                        html: data.message,
                        confirmButtonText: 'Aceptar'
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al regularizar',
                        html: data.message || 'No se pudo regularizar el comprobante'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo conectar con el servidor'
                });
            });
        }
    });
}
</script>
@stop
