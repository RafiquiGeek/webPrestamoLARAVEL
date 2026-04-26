@extends('layouts.admin')

@section('title', 'Buscar Comprobante en SUNAT')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-search mr-2"></i>Buscar Comprobante en SUNAT
                    </h3>
                    <a href="{{ route('admin.configuracion-sunat.api-config') }}" class="btn btn-light btn-sm" title="Configurar API SUNAT">
                        <i class="fas fa-cog mr-1"></i>Configurar API
                    </a>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>¿Para qué sirve esta herramienta?</strong><br>
                        Te permite verificar si un comprobante ya existe en SUNAT antes de emitirlo,
                        evitando errores por números duplicados.
                    </div>

                    <form id="formBuscarComprobante">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tipo_comprobante">Tipo de Comprobante *</label>
                                    <select class="form-control" id="tipo_comprobante" name="tipo_comprobante" required>
                                        <option value="">Seleccione...</option>
                                        <option value="01">01 - Factura</option>
                                        <option value="03">03 - Boleta</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="serie">Serie *</label>
                                    <input type="text" class="form-control" id="serie" name="serie"
                                           placeholder="Ej: B001" maxlength="4" required>
                                    <small class="form-text text-muted">4 caracteres</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="numero">Número *</label>
                                    <input type="number" class="form-control" id="numero" name="numero"
                                           placeholder="Ej: 1" min="1" required>
                                    <small class="form-text text-muted">Sin ceros a la izquierda</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="metodo_consulta">Método de Consulta *</label>
                                    <select class="form-control" id="metodo_consulta" name="metodo_consulta" required>
                                        <option value="api">API REST (Recomendado)</option>
                                        <option value="certificado">Certificado Digital (SOAP)</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> API no requiere certificado
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-search mr-2"></i>Buscar en SUNAT
                                </button>
                                <a href="{{ route('admin.comprobantes.index') }}" class="btn btn-info btn-lg">
                                    <i class="fas fa-file-invoice mr-2"></i>Ver Comprobantes Emitidos
                                </a>
                                <a href="{{ route('admin.configuracion-sunat.index') }}" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-arrow-left mr-2"></i>Volver
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Resultado de la búsqueda -->
            <div id="resultado" class="card mt-4" style="display: none;">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-file-invoice mr-2"></i>Resultado de la Búsqueda
                    </h4>
                </div>
                <div class="card-body" id="resultadoContenido">
                    <!-- Se llenará dinámicamente con JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .resultado-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    .resultado-texto {
        font-size: 1.2rem;
        margin-bottom: 1rem;
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    $('#formBuscarComprobante').on('submit', function(e) {
        e.preventDefault();

        const tipo = $('#tipo_comprobante').val();
        const serie = $('#serie').val().toUpperCase();
        const numero = $('#numero').val();
        const metodo = $('#metodo_consulta').val();

        if (!tipo || !serie || !numero) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Por favor complete todos los campos'
            });
            return;
        }

        // Mostrar loading con información del método
        const metodoTexto = metodo === 'api' ? 'API REST' : 'Certificado Digital';
        Swal.fire({
            title: 'Consultando SUNAT...',
            html: `Buscando comprobante <strong>${serie}-${numero.padStart(8, '0')}</strong><br><small>Método: ${metodoTexto}</small>`,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Hacer la consulta
        $.ajax({
            url: '{{ route("admin.configuracion-sunat.consultar-comprobante") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                tipo_comprobante: tipo,
                serie: serie,
                numero: numero,
                metodo_consulta: metodo
            },
            success: function(response) {
                Swal.close();

                if (response.success && response.data) {
                    mostrarResultado(response.data);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'No se pudo consultar el comprobante'
                    });
                }
            },
            error: function(xhr) {
                Swal.close();

                let mensaje = 'Error al consultar SUNAT';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    mensaje = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: mensaje
                });
            }
        });
    });

    function mostrarResultado(data) {
        let html = '';
        let colorCard = 'border-info';
        let iconClass = 'fas fa-question-circle text-info';
        let titulo = 'Resultado';

        if (data.existe === true) {
            // Comprobante EXISTE en SUNAT
            colorCard = 'border-success';
            iconClass = 'fas fa-check-circle text-success';
            titulo = '✓ Comprobante Encontrado';

            html = `
                <div class="text-center">
                    <i class="${iconClass} resultado-icon"></i>
                    <h4 class="text-success">Comprobante ${data.identificador} EXISTE en SUNAT</h4>
                    <p class="resultado-texto">${data.mensaje}</p>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>¡Atención!</strong> Este número ya está usado. No puedes emitir un comprobante con este número.
                    </div>

                    ${data.codigo ? `<p><strong>Código:</strong> ${data.codigo}</p>` : ''}
                    ${data.descripcion ? `<p><strong>Descripción:</strong> ${data.descripcion}</p>` : ''}
                    ${data.estado ? `<p><strong>Estado:</strong> <span class="badge badge-success">${data.estado}</span></p>` : ''}
                </div>
            `;
        } else if (data.existe === false) {
            // Comprobante NO EXISTE - Disponible para usar
            colorCard = 'border-primary';
            iconClass = 'fas fa-thumbs-up text-primary';
            titulo = '✓ Número Disponible';

            html = `
                <div class="text-center">
                    <i class="${iconClass} resultado-icon"></i>
                    <h4 class="text-primary">Comprobante ${data.identificador} NO existe en SUNAT</h4>
                    <p class="resultado-texto">${data.mensaje}</p>

                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i>
                        <strong>¡Perfecto!</strong> Este número está disponible. Puedes emitir el comprobante.
                    </div>
                </div>
            `;
        } else {
            // Error en la consulta
            colorCard = 'border-danger';
            iconClass = 'fas fa-times-circle text-danger';
            titulo = '✗ Error en la Consulta';

            html = `
                <div class="text-center">
                    <i class="${iconClass} resultado-icon"></i>
                    <h4 class="text-danger">Error al consultar SUNAT</h4>
                    <p class="resultado-texto">${data.mensaje}</p>

                    ${data.codigo_error ? `<p><strong>Código de Error:</strong> ${data.codigo_error}</p>` : ''}

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Esto puede deberse a problemas de conexión o certificado no registrado en SUNAT.
                    </div>
                </div>
            `;
        }

        $('#resultado').removeClass('border-success border-danger border-info border-primary').addClass(colorCard);
        $('#resultado .card-title').html(`<i class="${iconClass} mr-2"></i>${titulo}`);
        $('#resultadoContenido').html(html);
        $('#resultado').slideDown();

        // Scroll al resultado
        $('html, body').animate({
            scrollTop: $('#resultado').offset().top - 100
        }, 500);
    }
});
</script>
@stop
