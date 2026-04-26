@extends('layouts.admin')

@section('title', 'Importar Clientes')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-file-import text-primary"></i> Importar Clientes desde Excel</h1>
       <ol class="breadcrumb float-sm-right">
           <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Dashboard</a></li>
           <li class="breadcrumb-item"><a href="{{ route('admin.clientes.index') }}">Clientes</a></li>
           <li class="breadcrumb-item active">Importar</li>
       </ol>
   </div>
@stop

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title text-white">
                    <i class="fas fa-upload"></i> Cargar Archivo Excel
                </h3>
            </div>
            
            <form id="importForm" action="{{ route('admin.clientes.importar.procesar') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="excel_file" class="form-label">
                            <strong>Seleccionar archivo Excel (.xlsx, .xls)</strong>
                        </label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="excel_file" name="excel_file" 
                                   accept=".xlsx,.xls" required>
                            <label class="custom-file-label" for="excel_file">Elegir archivo...</label>
                        </div>
                        <small class="form-text text-muted">
                            Solo se permiten archivos Excel (.xlsx, .xls). Tamañoo máximo: 10MB
                        </small>
                    </div>

                    <div class="form-group">
                        <!-- Hidden input para asegurar que se envíe un valor -->
                        <input type="hidden" name="validar_dni" value="0">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="validar_dni" name="validar_dni" value="1" checked>
                            <label class="custom-control-label" for="validar_dni">
                                Consultar datos automáticamente por DNI (recomendado)
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Si está marcado, el sistema consultará automáticamente la fecha de nacimiento y dirección usando la API de DNI.
                        </small>
                    </div>

                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-lg" id="btnImportar">
                        <i class="fas fa-file-import"></i> Importar Clientes
                    </button>
                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#previewModal">
                        <i class="fas fa-eye"></i> Vista previa
                    </button>
                </div>
            </form>
        </div>

        <!-- Progress Card -->
        <div class="card d-none" id="progressCard">
            <div class="card-header bg-info">
                <h3 class="card-title text-white">
                    <i class="fas fa-spinner fa-spin"></i> Procesando importación...
                </h3>
            </div>
            <div class="card-body">
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%" id="progressBar">
                        0%
                    </div>
                </div>
                <div class="mt-2">
                    <small id="progressText">Iniciando importación...</small>
                </div>
            </div>
        </div>

        <!-- Results Card -->
        <div class="card d-none" id="resultsCard">
            <div class="card-header bg-success">
                <h3 class="card-title text-white">
                    <i class="fas fa-check"></i> Resultado de la importación
                </h3>
            </div>
            <div class="card-body" id="resultsBody">
                <!-- Results will be populated here -->
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title text-white">
                    <i class="fas fa-info-circle"></i> Formato del archivo Excel
                </h3>
            </div>
            <div class="card-body">
                <h5>Columnas requeridas:</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead class="thead-light">
                            <tr>
                                <th>Columna</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-danger">
                                <td><code>nDocumento</code></td>
                                <td>DNI (8 dígitos) *</td>
                            </tr>
                            <tr class="table-danger">
                                <td><code>nombres</code></td>
                                <td>Nombres completos *</td>
                            </tr>
                            <tr class="table-danger">
                                <td><code>aPaterno</code></td>
                                <td>Apellido paterno *</td>
                            </tr>
                            <tr class="table-danger">
                                <td><code>aMaterno</code></td>
                                <td>Apellido materno *</td>
                            </tr>
                            <tr class="table-danger">
                                <td><code>telefono</code></td>
                                <td>Número de teléfono *</td>
                            </tr>
                            <tr class="table-danger">
                                <td><code>tipo</code></td>
                                <td>Tipo teléfono (celular/fijo) *</td>
                            </tr>
                            <tr class="table-danger">
                                <td><code>zona</code></td>
                                <td>ID de la zona (consultar con administrador) *</td>
                            </tr>
                            <tr class="table-danger">
                                <td><code>sucursal</code></td>
                                <td>ID de la sucursal (consultar con administrador) *</td>
                            </tr>
                            <tr class="table-danger">
                                <td><code>tCuenta</code></td>
                                <td>ID tipo de cuenta *</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <small class="text-muted">* Campos obligatorios</small>
                
                <h5 class="mt-3">Datos automáticos:</h5>
                <div class="alert alert-info">
                    <ul class="mb-0">
                        <li><strong>fecha_nacimiento:</strong> Se obtiene automáticamente del DNI</li>
                        <li><strong>direccion:</strong> Se obtiene automáticamente del DNI</li> 
                        <li><strong>estado_civil:</strong> Por defecto "Soltero"</li>
                        <li><strong>departamento/provincia/distrito:</strong> Se obtiene de la sucursal especificada</li>
                    </ul>
                </div>

                <h5 class="mt-3">Valores de referencia:</h5>
                <div class="alert alert-warning">
                    <ul class="mb-0">
                        <li><strong>tCuenta:</strong> 1=Efectivo, 2=Propia, 3=Terceros</li>
                        <li><strong>tipo:</strong> celular o fijo</li>
                        <li><strong>zona/sucursal:</strong> Contactar al administrador para obtener los IDs correctos</li>
                    </ul>
                </div>

                <div class="mt-3">
                    <a href="{{ route('admin.clientes.importar.plantilla') }}" class="btn btn-success btn-block">
                        <i class="fas fa-download"></i> Descargar plantilla Excel
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-warning">
                <h3 class="card-title text-dark">
                    <i class="fas fa-exclamation-triangle"></i> Importante
                </h3>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success"></i> Los DNI deben ser únicos</li>
                    <li><i class="fas fa-check text-success"></i> El sistema validará cada DNI</li>
                    <li><i class="fas fa-check text-success"></i> Los datos se consultaron automáticamente</li>
                    <li><i class="fas fa-exclamation-triangle text-warning"></i> El proceso puede tardar varios minutos</li>
                    <li><i class="fas fa-times text-danger"></i> No cerrar la página durante el proceso</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal de vista previa -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista previa del archivo</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="previewContent">
                    <p class="text-muted">Seleccione un archivo para ver la vista previa.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .table-responsive {
        max-height: 300px;
    }
    .progress {
        height: 25px;
    }
    .custom-file-label::after {
        content: "Buscar";
    }
    .card-header.bg-primary {
        border-bottom: 3px solid #0056b3;
    }
    .card-header.bg-info {
        border-bottom: 3px solid #138496;
    }
    .card-header.bg-warning {
        border-bottom: 3px solid #d39e00;
    }
    .card-header.bg-success {
        border-bottom: 3px solid #1e7e34;
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Actualizar etiqueta del archivo seleccionado
    $('#excel_file').on('change', function() {
        var fileName = $(this)[0].files[0] ? $(this)[0].files[0].name : 'Elegir archivo...';
        $(this).next('.custom-file-label').text(fileName);
    });

    // Manejar el env�o del formulario
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!$('#excel_file')[0].files.length) {
            Swal.fire({
                icon: 'warning',
                title: 'Archivo requerido',
                text: 'Por favor seleccione un archivo Excel.'
            });
            return;
        }

        // Mostrar confirmaci�n
        Swal.fire({
            title: '¿Confirmar importación?',
            text: 'Este proceso puede tardar varios minutos dependiendo del tamaño del archivo.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, importar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#007bff'
        }).then((result) => {
            if (result.isConfirmed) {
                startImportProcess();
            }
        });
    });

    function startImportProcess() {
        // Ocultar formulario y mostrar progreso
        $('#importForm').closest('.card').addClass('d-none');
        $('#progressCard').removeClass('d-none');
        
        // Crear FormData desde el formulario
        var formData = new FormData($('#importForm')[0]);

        // Realizar petici�n AJAX
        $.ajax({
            url: '{{ route("admin.clientes.importar.procesar") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        $('#progressBar').css('width', percentComplete + '%')
                                        .text(Math.round(percentComplete) + '%');
                        $('#progressText').text('Subiendo archivo... ' + Math.round(percentComplete) + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                $('#progressCard').addClass('d-none');
                showResults(response);
            },
            error: function(xhr, status, error) {
                $('#progressCard').addClass('d-none');
                $('#importForm').closest('.card').removeClass('d-none');
                
                var message = 'Error al procesar el archivo.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error en la importación',
                    text: message
                });
            }
        });
    }

    function showResults(response) {
        $('#resultsCard').removeClass('d-none');
        
        var html = '<div class="row">';
        html += '<div class="col-md-6">';
        html += '<h5 class="text-success"><i class="fas fa-check"></i> Clientes importados exitosamente: ' + response.exitosos + '</h5>';
        html += '</div>';
        html += '<div class="col-md-6">';
        html += '<h5 class="text-danger"><i class="fas fa-times"></i> Errores encontrados: ' + response.errores + '</h5>';
        html += '</div>';
        html += '</div>';

        if (response.detalles_errores && response.detalles_errores.length > 0) {
            html += '<hr>';
            html += '<h6>Detalles de errores:</h6>';
            html += '<div class="table-responsive">';
            html += '<table class="table table-sm table-striped">';
            html += '<thead class="thead-light"><tr><th>Fila</th><th>DNI</th><th>Error</th></tr></thead>';
            html += '<tbody>';
            
            response.detalles_errores.forEach(function(error) {
                html += '<tr>';
                html += '<td>' + error.fila + '</td>';
                html += '<td>' + error.dni + '</td>';
                html += '<td class="text-danger">' + error.mensaje + '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            html += '</div>';
        }

        html += '<hr>';
        html += '<a href="{{ route("admin.clientes.index") }}" class="btn btn-primary">';
        html += '<i class="fas fa-list"></i> Ver lista de clientes';
        html += '</a>';
        
        $('#resultsBody').html(html);
    }

    // Vista previa del archivo
    $('#previewModal').on('show.bs.modal', function() {
        if ($('#excel_file')[0].files.length) {
            $('#previewContent').html('<p class="text-info"><i class="fas fa-spinner fa-spin"></i> Cargando vista previa...</p>');
            
            // Aquí podrías implementar una vista previa real del Excel
            setTimeout(function() {
                $('#previewContent').html('<p class="text-success">Archivo cargado: ' + $('#excel_file')[0].files[0].name + '</p>');
            }, 1000);
        }
    });
});
</script>
@stop