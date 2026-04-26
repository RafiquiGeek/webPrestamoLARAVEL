@extends('layouts.admin')

@section('title', 'Comprobantes Declarados')

@section('content_header')
    <h1><i class="fas fa-file-invoice"></i> Comprobantes Declarados a SUNAT</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Validación de Comprobantes Electrónicos</h3>
        <div class="card-tools d-flex gap-2">
            <button type="button" class="btn btn-success btn-sm mr-2" onclick="exportarComprobantes()">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </button>
            <button type="button" class="btn btn-info btn-sm" id="btnConsultarSunat">
                <i class="fas fa-cloud-download-alt"></i> Consultar en SUNAT
            </button>
        </div>
    </div>

    <div class="card-body">
        <!-- Filtros -->
        <form method="GET" action="{{ route('admin.comprobantes.declarados') }}" id="filtrosForm">
            <div class="row mb-3">
                <div class="col-md-2">
                    <label>Estado</label>
                    <select name="estado" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        <option value="PENDIENTE" {{ request('estado') == 'PENDIENTE' ? 'selected' : '' }}>Pendiente</option>
                        <option value="ENVIADO" {{ request('estado') == 'ENVIADO' ? 'selected' : '' }}>Enviado</option>
                        <option value="ERROR" {{ request('estado') == 'ERROR' ? 'selected' : '' }}>Error</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label>Tipo</label>
                    <select name="tipo_comprobante" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        <option value="01" {{ request('tipo_comprobante') == '01' ? 'selected' : '' }}>Factura</option>
                        <option value="03" {{ request('tipo_comprobante') == '03' ? 'selected' : '' }}>Boleta</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label>Desde</label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ request('fecha_desde') }}">
                </div>

                <div class="col-md-2">
                    <label>Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ request('fecha_hasta') }}">
                </div>

                <div class="col-md-3">
                    <label>Buscar</label>
                    <input type="text" name="buscar" class="form-control form-control-sm" placeholder="N°, Serie, Cliente, DNI..." value="{{ request('buscar') }}">
                </div>

                <div class="col-md-1">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-sm btn-block">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>

        <!-- Tabla de comprobantes -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="8%">Tipo</th>
                        <th width="12%">N° Comprobante</th>
                        <th width="10%">Fecha</th>
                        <th width="20%">Cliente</th>
                        <th width="10%">Documento</th>
                        <th width="8%">Total</th>
                        <th width="8%">Estado</th>
                        <th width="19%">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($comprobantes as $comprobante)
                    <tr>
                        <td>{{ $comprobante->id }}</td>
                        <td>
                            @if($comprobante->tipo_comprobante == '01')
                                <span class="badge badge-info">Factura</span>
                            @else
                                <span class="badge badge-secondary">Boleta</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $comprobante->numero_completo }}</strong>
                        </td>
                        <td>{{ $comprobante->fecha_emision->format('d/m/Y') }}</td>
                        <td>
                            @if($comprobante->cliente && $comprobante->cliente->persona)
                                {{ $comprobante->cliente->persona->nombres }}
                                {{ $comprobante->cliente->persona->ape_pat }}
                                {{ $comprobante->cliente->persona->ape_mat }}
                            @else
                                <em>Sin cliente</em>
                            @endif
                        </td>
                        <td>
                            @if($comprobante->cliente && $comprobante->cliente->persona)
                                {{ $comprobante->cliente->persona->documento }}
                            @endif
                        </td>
                        <td class="text-right">
                            <strong>S/ {{ number_format($comprobante->total, 2) }}</strong>
                        </td>
                        <td>
                            @if($comprobante->estado == 'ENVIADO')
                                <span class="badge badge-success">
                                    <i class="fas fa-check-circle"></i> Enviado
                                </span>
                            @elseif($comprobante->estado == 'PENDIENTE')
                                <span class="badge badge-warning">
                                    <i class="fas fa-clock"></i> Pendiente
                                </span>
                            @elseif($comprobante->estado == 'ERROR')
                                <span class="badge badge-danger">
                                    <i class="fas fa-exclamation-circle"></i> Error
                                </span>
                            @else
                                <span class="badge badge-secondary">{{ $comprobante->estado }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a class="btn btn-info" href="/admin/comprobantes-declarados/{{ $comprobante->id }}" title="Ver Detalle">
                                    <i class="fas fa-eye"></i>
                                </a>

                                @if($comprobante->xml_content)
                                <a href="{{ route('admin.comprobantes.descargar-xml', $comprobante->id) }}" class="btn btn-primary" title="Descargar XML">
                                    <i class="fas fa-file-code"></i>
                                </a>
                                @endif

                                @if($comprobante->cdr_zip)
                                <a href="{{ route('admin.comprobantes.descargar-cdr', $comprobante->id) }}" class="btn btn-success" title="Descargar CDR">
                                    <i class="fas fa-file-archive"></i>
                                </a>
                                @endif

                                @if($comprobante->estado == 'ERROR' || $comprobante->estado == 'PENDIENTE')
                                <button class="btn btn-warning" onclick="reenviarComprobante({{ $comprobante->id }})" title="Reenviar a SUNAT">
                                    <i class="fas fa-redo"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">
                            <em>No se encontraron comprobantes</em>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="mt-3">
            {{ $comprobantes->appends(request()->query())->links() }}
        </div>
    </div>
</div>

@stop

@section('js')
<script>
$('#btnConsultarSunat').on('click', function() {
    let params = new URLSearchParams(new FormData(document.getElementById('filtrosForm')));
    $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Consultando...');
    $.get("{{ route('admin.comprobantes.consultarSunat') }}?" + params.toString(), function(data) {
        // Aquí deberías actualizar la tabla con los resultados de SUNAT
        Swal.fire('Consulta completada', 'Se consultaron los comprobantes en SUNAT.', 'success');
        location.reload();
    }).fail(function() {
        Swal.fire('Error', 'No se pudo consultar en SUNAT.', 'error');
    }).always(() => {
        $('#btnConsultarSunat').prop('disabled', false).html('<i class="fas fa-cloud-download-alt"></i> Consultar todos en SUNAT');
    });
});
// ...eliminado verDetalle, ya no se usa modal...

function exportarComprobantes() {
    // Obtener los parámetros de filtro actuales
    const params = new URLSearchParams(new FormData(document.getElementById('filtrosForm')));
    window.location.href = '{{ route("admin.comprobantes.exportar") }}?' + params.toString();
}

function reenviarComprobante(id) {
    Swal.fire({
        title: '¿Reenviar a SUNAT?',
        text: 'Se volverá a enviar este comprobante a SUNAT',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, reenviar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Enviando...',
                text: 'Procesando el reenvío',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Hacer el request
            $.post(`/admin/comprobantes/declarados/${id}/reenviar`, {
                _token: '{{ csrf_token() }}'
            }).done(function(response) {
                Swal.fire('Éxito', 'Comprobante reenviado correctamente', 'success').then(() => {
                    location.reload();
                });
            }).fail(function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Error al reenviar', 'error');
            });
        }
    });
}

// Auto-submit al cambiar filtros
$('select[name="estado"], select[name="tipo_comprobante"]').change(function() {
    $('#filtrosForm').submit();
});
</script>
@stop

@section('css')
<style>
.table td {
    vertical-align: middle;
}
.btn-group-sm .btn {
    padding: 0.25rem 0.4rem;
}
</style>
@stop
