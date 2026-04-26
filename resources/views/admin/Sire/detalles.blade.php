<div class="container-fluid">
    <div class="row">
        <!-- Información General -->
        <div class="col-md-6">
            <h6 class="text-primary"><i class="fas fa-file-invoice"></i> Información del Comprobante</h6>
            <table class="table table-sm table-bordered">
                <tr>
                    <th width="40%">Tipo:</th>
                    <td>
                        @php
                            $tipos = [
                                '01' => 'Factura',
                                '03' => 'Boleta',
                                '07' => 'Nota de Crédito',
                                '08' => 'Nota de Débito'
                            ];
                        @endphp
                        {{ $tipos[$comprobante->tipo_comprobante] ?? $comprobante->tipo_comprobante }}
                    </td>
                </tr>
                <tr>
                    <th>Serie - Número:</th>
                    <td><strong>{{ $comprobante->serie }}-{{ $comprobante->numero }}</strong></td>
                </tr>
                <tr>
                    <th>Fecha Emisión:</th>
                    <td>{{ $comprobante->fecha_emision->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <th>Moneda:</th>
                    <td>{{ $comprobante->moneda }}</td>
                </tr>
                <tr>
                    <th>Total:</th>
                    <td class="text-success"><strong>S/ {{ number_format($comprobante->total, 2) }}</strong></td>
                </tr>
            </table>
        </div>

        <!-- Información del Cliente -->
        <div class="col-md-6">
            <h6 class="text-primary"><i class="fas fa-user"></i> Datos del Cliente</h6>
            <table class="table table-sm table-bordered">
                <tr>
                    <th width="40%">Tipo Doc:</th>
                    <td>{{ $comprobante->cliente_tipo_doc == '6' ? 'RUC' : 'DNI' }}</td>
                </tr>
                <tr>
                    <th>Número:</th>
                    <td><strong>{{ $comprobante->cliente_numero_doc }}</strong></td>
                </tr>
                <tr>
                    <th>Razón Social:</th>
                    <td>{{ $comprobante->cliente_razon_social }}</td>
                </tr>
            </table>
        </div>
    </div>

    <hr>

    <div class="row">
        <!-- Estado y Envío -->
        <div class="col-md-6">
            <h6 class="text-primary"><i class="fas fa-tasks"></i> Estado del Comprobante</h6>
            <table class="table table-sm table-bordered">
                <tr>
                    <th width="40%">Estado:</th>
                    <td>
                        @php
                            $badges = [
                                'enviado' => 'badge-primary',
                                'aceptado' => 'badge-success',
                                'rechazado' => 'badge-danger',
                                'pendiente' => 'badge-warning'
                            ];
                            $badge = $badges[$comprobante->estado] ?? 'badge-secondary';
                        @endphp
                        <span class="badge {{ $badge }}">{{ strtoupper($comprobante->estado) }}</span>
                    </td>
                </tr>
                <tr>
                    <th>Fecha Envío:</th>
                    <td>{{ $comprobante->fecha_envio ? $comprobante->fecha_envio->format('d/m/Y H:i:s') : '-' }}</td>
                </tr>
                <tr>
                    <th>Fecha Respuesta:</th>
                    <td>{{ $comprobante->fecha_respuesta ? $comprobante->fecha_respuesta->format('d/m/Y H:i:s') : '-' }}</td>
                </tr>
            </table>
        </div>

        <!-- Respuesta SUNAT -->
        <div class="col-md-6">
            <h6 class="text-primary"><i class="fas fa-reply"></i> Respuesta SUNAT</h6>
            <table class="table table-sm table-bordered">
                <tr>
                    <th width="40%">Código:</th>
                    <td>{{ $comprobante->sunat_codigo ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Mensaje:</th>
                    <td>{{ $comprobante->sunat_mensaje ?? '-' }}</td>
                </tr>
                @if($comprobante->sunat_response)
                    <tr>
                        <th>Detalles:</th>
                        <td>
                            <button class="btn btn-sm btn-info" type="button" data-toggle="collapse" data-target="#sunat-response">
                                Ver JSON
                            </button>
                            <div class="collapse mt-2" id="sunat-response">
                                <pre class="bg-light p-2" style="max-height: 200px; overflow-y: auto;">{{ $comprobante->sunat_response }}</pre>
                            </div>
                        </td>
                    </tr>
                @endif
            </table>
        </div>
    </div>

    <hr>

    <div class="row">
        <!-- Hash y Firmas -->
        <div class="col-md-12">
            <h6 class="text-primary"><i class="fas fa-lock"></i> Seguridad y Validación</h6>
            <table class="table table-sm table-bordered">
                @if($comprobante->hash_xml)
                <tr>
                    <th width="20%">Hash XML:</th>
                    <td><code>{{ $comprobante->hash_xml }}</code></td>
                </tr>
                @endif
                @if($comprobante->cdr_hash)
                <tr>
                    <th>Hash CDR:</th>
                    <td><code>{{ $comprobante->cdr_hash }}</code></td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    <!-- Metadata (si existe) -->
    @if($comprobante->metadata)
        <hr>
        <div class="row">
            <div class="col-md-12">
                <h6 class="text-primary"><i class="fas fa-info-circle"></i> Información Adicional</h6>
                @php
                    $metadata = json_decode($comprobante->metadata, true);
                @endphp
                @if($metadata)
                    <div class="alert alert-info">
                        @if(isset($metadata['cuota_id']))
                            <p class="mb-1"><strong>Cuota ID:</strong> {{ $metadata['cuota_id'] }}</p>
                        @endif
                        @if(isset($metadata['prestamo_id']))
                            <p class="mb-1"><strong>Préstamo ID:</strong> {{ $metadata['prestamo_id'] }}</p>
                        @endif
                        @if(isset($metadata['cliente_id']))
                            <p class="mb-0"><strong>Cliente ID:</strong> {{ $metadata['cliente_id'] }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Archivos disponibles -->
    <hr>
    <div class="row">
        <div class="col-md-12">
            <h6 class="text-primary"><i class="fas fa-file-download"></i> Archivos Disponibles</h6>
            <div class="btn-group" role="group">
                @if($comprobante->xml_generado)
                    <a href="{{ route('admin.sire.descargar-xml', $comprobante->id) }}"
                       class="btn btn-success btn-sm">
                        <i class="fas fa-download"></i> XML Generado
                    </a>
                @endif
                @if($comprobante->xml_firmado)
                    <a href="{{ route('admin.sire.descargar-xml-firmado', $comprobante->id) }}"
                       class="btn btn-primary btn-sm">
                        <i class="fas fa-download"></i> XML Firmado
                    </a>
                @endif
                @if($comprobante->cdr_zip)
                    <a href="{{ route('admin.sire.descargar-cdr', $comprobante->id) }}"
                       class="btn btn-info btn-sm">
                        <i class="fas fa-file-archive"></i> CDR (Constancia)
                    </a>
                @endif
            </div>

            @if(!$comprobante->xml_generado && !$comprobante->xml_firmado && !$comprobante->cdr_zip)
                <p class="text-muted mt-2">No hay archivos disponibles para descargar.</p>
            @endif
        </div>
    </div>

    <!-- Acciones -->
    <hr>
    <div class="row">
        <div class="col-md-12 text-right">
            @if($comprobante->estado !== 'aceptado')
                <form method="POST" action="{{ route('admin.sire.reenviar', $comprobante->id) }}"
                      style="display: inline;"
                      onsubmit="return confirm('¿Está seguro de reenviar este comprobante?')">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="fas fa-redo"></i> Reenviar a SUNAT
                    </button>
                </form>
            @endif

            <a href="{{ route('admin.sire.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-times"></i> Cerrar
            </a>
        </div>
    </div>
</div>
