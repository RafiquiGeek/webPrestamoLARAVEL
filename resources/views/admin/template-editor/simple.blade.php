@extends('layouts.admin')

@section('title', 'Editor de Plantillas - Contrato de Mutuo')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <i class="fas fa-edit mr-2"></i>
                        Editor de Plantillas - Contrato de Mutuo
                    </h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Editar Plantilla de Contrato</h3>
                </div>
                <div class="card-body">
                    <!-- Toolbar -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="btn-toolbar" role="toolbar">
                                <div class="btn-group mr-2" role="group">
                                    <button type="button" class="btn btn-primary" id="save-template">
                                        <i class="fas fa-save"></i> Guardar
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="preview-template">
                                        <i class="fas fa-eye"></i> Vista Previa
                                    </button>
                                </div>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-secondary" id="reset-template">
                                        <i class="fas fa-undo"></i> Restablecer
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="show-backups">
                                        <i class="fas fa-history"></i> Historial
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <select class="form-control" id="prestamo-selector">
                                    <option value="">Seleccionar préstamo...</option>
                                </select>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-primary" id="load-real-data" disabled>
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

            <div class="row">
                <!-- Variables Panel -->
                <div class="col-md-3">
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-tags"></i> Variables
                            </h3>
                        </div>
                        <div class="card-body p-2" style="max-height: 500px; overflow-y: auto;"
                                    
                            <!-- Cliente -->
                            <div class="variable-group mb-3">
                                <h6 class="text-secondary mb-2">
                                    <i class="fas fa-user"></i> Cliente
                                </h6>
                                <div class="variable-item mb-1" data-variable="{{ $variables['customer_name'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['customer_name'] }}
                                        <small class="text-muted d-block">Nombre completo</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['customer_dni'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['customer_dni'] }}
                                        <small class="text-muted d-block">DNI del cliente</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['customer_address'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['customer_address'] }}
                                        <small class="text-muted d-block">Dirección</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['customer_phone'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['customer_phone'] }}
                                        <small class="text-muted d-block">Teléfono</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['customer_email'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['customer_email'] }}
                                        <small class="text-muted d-block">Email</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['customer_district'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['customer_district'] }}
                                        <small class="text-muted d-block">Distrito</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['customer_province'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['customer_province'] }}
                                        <small class="text-muted d-block">Provincia</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['customer_department'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['customer_department'] }}
                                        <small class="text-muted d-block">Departamento</small>
                                    </button>
                                </div>
                            </div>

                            <!-- Empresa -->
                            <div class="variable-group mb-3">
                                <h6 class="text-secondary mb-2">
                                    <i class="fas fa-building"></i> Empresa
                                </h6>
                                <div class="variable-item mb-1" data-variable="{{ $variables['company_name'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['company_name'] }}
                                        <small class="text-muted d-block">Nombre empresa</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['company_ruc'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['company_ruc'] }}
                                        <small class="text-muted d-block">RUC</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['company_address'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['company_address'] }}
                                        <small class="text-muted d-block">Dirección</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['manager_name'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['manager_name'] }}
                                        <small class="text-muted d-block">Gerente</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['manager_dni'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['manager_dni'] }}
                                        <small class="text-muted d-block">DNI gerente</small>
                                    </button>
                                </div>
                            </div>

                            <!-- Préstamo -->
                            <div class="variable-group mb-3">
                                <h6 class="text-secondary mb-2">
                                    <i class="fas fa-money-bill"></i> Préstamo
                                </h6>
                                <div class="variable-item mb-1" data-variable="{{ $variables['loan_amount'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['loan_amount'] }}
                                        <small class="text-muted d-block">Monto del préstamo</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['loan_amount_words'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['loan_amount_words'] }}
                                        <small class="text-muted d-block">Monto en letras</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['interest_rate'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['interest_rate'] }}
                                        <small class="text-muted d-block">Tasa de interés</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['installment_amount'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['installment_amount'] }}
                                        <small class="text-muted d-block">Monto de cuota</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['total_installments'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['total_installments'] }}
                                        <small class="text-muted d-block">Número de cuotas</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['contract_number'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['contract_number'] }}
                                        <small class="text-muted d-block">Número de contrato</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['payment_frequency'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['payment_frequency'] }}
                                        <small class="text-muted d-block">Frecuencia de pago</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['first_payment_date'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['first_payment_date'] }}
                                        <small class="text-muted d-block">Fecha primer pago</small>
                                    </button>
                                </div>
                            </div>

                            <!-- Fechas -->
                            <div class="variable-group mb-3">
                                <h6 class="text-secondary mb-2">
                                    <i class="fas fa-calendar"></i> Fechas
                                </h6>
                                <div class="variable-item mb-1" data-variable="{{ $variables['disbursement_date'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['disbursement_date'] }}
                                        <small class="text-muted d-block">Fecha desembolso</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['contract_date'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['contract_date'] }}
                                        <small class="text-muted d-block">Fecha del contrato</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['current_date'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['current_date'] }}
                                        <small class="text-muted d-block">Fecha actual</small>
                                    </button>
                                </div>
                            </div>

                            <!-- Aval -->
                            <div class="variable-group mb-3">
                                <h6 class="text-secondary mb-2">
                                    <i class="fas fa-handshake"></i> Aval/Fiador
                                </h6>
                                <div class="variable-item mb-1" data-variable="{{ $variables['guarantor_name'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['guarantor_name'] }}
                                        <small class="text-muted d-block">Nombre del aval</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['guarantor_dni'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['guarantor_dni'] }}
                                        <small class="text-muted d-block">DNI del aval</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['guarantor_phone'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['guarantor_phone'] }}
                                        <small class="text-muted d-block">Teléfono del aval</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['guarantor_address'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['guarantor_address'] }}
                                        <small class="text-muted d-block">Dirección del aval</small>
                                    </button>
                                </div>
                            </div>

                            <!-- Cronograma y Resumen -->
                            <div class="variable-group mb-3">
                                <h6 class="text-secondary mb-2">
                                    <i class="fas fa-table"></i> Cronograma
                                </h6>
                                <div class="variable-item mb-1" data-variable="{{ $variables['installment_table'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['installment_table'] }}
                                        <small class="text-muted d-block">Tabla de cuotas</small>
                                    </button>
                                </div>
                                <div class="variable-item mb-1" data-variable="{{ $variables['loan_summary'] }}">
                                    <button class="btn btn-sm btn-light btn-block text-left">
                                        {{ $variables['loan_summary'] }}
                                        <small class="text-muted d-block">Resumen del préstamo</small>
                                    </button>
                                </div>
                            </div>

                                </div>
                            </div>
                        </div>
                        
                <!-- Editor Principal -->
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-file-contract"></i> Contenido del Contrato
                            </h3>
                            <div class="card-tools">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Haz clic en las variables para insertarlas
                                </small>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div id="editor-toolbar" class="btn-toolbar border-bottom p-2" role="toolbar">
                                <div class="btn-group btn-group-sm mr-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" data-cmd="bold">
                                        <i class="fas fa-bold"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" data-cmd="italic">
                                        <i class="fas fa-italic"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" data-cmd="underline">
                                        <i class="fas fa-underline"></i>
                                    </button>
                                </div>
                                <div class="btn-group btn-group-sm mr-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" data-cmd="justifyLeft">
                                        <i class="fas fa-align-left"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" data-cmd="justifyCenter">
                                        <i class="fas fa-align-center"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" data-cmd="justifyRight">
                                        <i class="fas fa-align-right"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" data-cmd="justifyFull">
                                        <i class="fas fa-align-justify"></i>
                                    </button>
                                </div>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary" id="toggle-source">
                                        <i class="fas fa-code"></i> Código
                                    </button>
                                </div>
                            </div>
                            <div id="template-content" contenteditable="true" class="form-control border-0" style="min-height: 500px; max-height: 600px; overflow-y: auto; padding: 20px; line-height: 1.6;">
                                {!! nl2br(e($contentOnly)) !!}
                            </div>
                            <textarea id="template-source" class="form-control border-0 d-none" rows="25" style="font-family: 'Courier New', monospace; font-size: 13px;">{!! $contentOnly !!}</textarea>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista Previa del Contrato</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="preview-content" style="height: 600px; overflow-y: auto;">
                <!-- Preview content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Backups Modal -->
<div class="modal fade" id="backupsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Backups de Plantilla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="backups-table">
                        <thead>
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Tamaño</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Backups will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
/* Estilos simplificados y consistentes */
.variable-item button {
    font-size: 11px;
    padding: 5px 8px;
    border: 1px solid #dee2e6;
    transition: all 0.2s ease;
}

.variable-item button:hover {
    background-color: #e9ecef;
    border-color: #adb5bd;
    transform: translateY(-1px);
}

.variable-item button:active {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

#template-content[contenteditable="true"] {
    font-family: 'Times New Roman', serif;
    font-size: 14px;
    line-height: 1.6;
    text-align: justify;
}

#template-content[contenteditable="true"]:focus {
    outline: none;
    box-shadow: inset 0 0 5px rgba(0,123,255,0.2);
}

.editor-toolbar .btn {
    border-radius: 0;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    display: none;
}

.loading-spinner {
    background: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
}

.variable-group {
    border-left: 2px solid #dee2e6;
    padding-left: 8px;
    margin-bottom: 15px;
}

/* Responsive adjustments */
@media (max-width: 991px) {
    .btn-toolbar {
        flex-wrap: wrap;
    }

    .btn-group {
        margin-bottom: 5px;
    }
}
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    let isSourceMode = false;
    let activeEditor = $('#template-content')[0];

    // Editor toolbar functionality
    $('#editor-toolbar [data-cmd]').click(function(e) {
        e.preventDefault();
        const cmd = $(this).data('cmd');
        document.execCommand(cmd, false, null);
        activeEditor.focus();
    });

    // Toggle between visual and source mode
    $('#toggle-source').click(function() {
        if (isSourceMode) {
            // Switch to visual mode
            const sourceContent = $('#template-source').val();
            $('#template-content').html(sourceContent.replace(/\n/g, '<br>'));
            $('#template-content').show();
            $('#template-source').hide();
            $(this).html('<i class="fas fa-code"></i> Código');
            isSourceMode = false;
            activeEditor = $('#template-content')[0];
        } else {
            // Switch to source mode
            const visualContent = $('#template-content').html();
            $('#template-source').val(visualContent.replace(/<br>/g, '\n').replace(/<[^>]*>/g, ''));
            $('#template-content').hide();
            $('#template-source').show();
            $(this).html('<i class="fas fa-eye"></i> Visual');
            isSourceMode = true;
            activeEditor = $('#template-source')[0];
        }
    });

    // Track active editor
    $('#template-content, #template-source').focus(function() {
        activeEditor = this;
    });
    
    // Variable insertion functionality
    $('.variable-item button').click(function(e) {
        e.preventDefault();
        const variable = $(this).closest('.variable-item').data('variable');
        insertVariableAtCursor(variable);

        // Visual feedback
        $(this).addClass('btn-primary').removeClass('btn-light');
        setTimeout(() => {
            $(this).removeClass('btn-primary').addClass('btn-light');
        }, 300);
    });
    
    // Save template
    $('#save-template').click(function() {
        saveTemplate();
    });
    
    // Preview template
    $('#preview-template').click(function() {
        previewTemplate();
    });
    
    // Reset template
    $('#reset-template').click(function() {
        if (confirm('¿Está seguro de que desea restablecer la plantilla? Se perderán todos los cambios no guardados.')) {
            location.reload();
        }
    });
    
    // Show backups
    $('#show-backups').click(function() {
        loadBackups();
    });

    // Load prestamos for preview
    loadPrestamos();

    // Load real data when prestamo is selected
    $('#load-real-data').click(function() {
        const prestamoId = $('#prestamo-selector').val();
        if (prestamoId) {
            loadPrestamoData(prestamoId);
        }
    });

    // Enable/disable load data button based on selection
    $('#prestamo-selector').change(function() {
        $('#load-real-data').prop('disabled', !$(this).val());
    });
    
    function saveTemplate() {
        showLoading();

        // Get content from appropriate editor
        let templateContent;
        if (isSourceMode) {
            templateContent = $('#template-source').val();
        } else {
            // Convert HTML to plain text for saving, keeping variables
            const htmlContent = $('#template-content').html();
            templateContent = htmlContent
                .replace(/<span class="variable-placeholder"[^>]*>(.*?)<\/span>/g, '$1')
                .replace(/<br>/g, '\n')
                .replace(/<[^>]*>/g, '')
                .replace(/&nbsp;/g, ' ');
        }

        $.ajax({
            url: '{{ route("admin.template-editor.update") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                template_content: templateContent
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    Swal.fire('¡Éxito!', response.message, 'success');
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                hideLoading();
                let message = 'Error al guardar la plantilla';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire('Error', message, 'error');
            }
        });
    }
    
    function previewTemplate() {
        showLoading();

        // Get content from appropriate editor
        let templateContent;
        if (isSourceMode) {
            templateContent = $('#template-source').val();
        } else {
            const htmlContent = $('#template-content').html();
            templateContent = htmlContent
                .replace(/<span class="variable-placeholder"[^>]*>(.*?)<\/span>/g, '$1')
                .replace(/<br>/g, '\n')
                .replace(/<[^>]*>/g, '')
                .replace(/&nbsp;/g, ' ');
        }

        $.ajax({
            url: '{{ route("admin.template-editor.preview") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                template_content: templateContent
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    $('#preview-content').html(response.html);
                    $('#previewModal').modal('show');
                } else {
                    Swal.fire('Error', 'Error al generar la vista previa', 'error');
                }
            },
            error: function() {
                hideLoading();
                Swal.fire('Error', 'Error al generar la vista previa', 'error');
            }
        });
    }
    
    function loadBackups() {
        $.ajax({
            url: '{{ route("admin.template-editor.backups") }}',
            method: 'GET',
            success: function(response) {
                const tbody = $('#backups-table tbody');
                tbody.empty();
                
                if (response.backups.length === 0) {
                    tbody.append('<tr><td colspan="3" class="text-center">No hay backups disponibles</td></tr>');
                } else {
                    response.backups.forEach(function(backup) {
                        tbody.append(`
                            <tr>
                                <td>${backup.date}</td>
                                <td>${(backup.size / 1024).toFixed(2)} KB</td>
                                <td>
                                    <button class="btn btn-sm btn-primary restore-backup" data-backup-id="${backup.id}">
                                        <i class="fas fa-undo me-1"></i> Restaurar
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                }

                $('#backupsModal').modal('show');
            },
            error: function() {
                Swal.fire('Error', 'Error al cargar los backups', 'error');
            }
        });
    }
    
    // Restore backup
    $(document).on('click', '.restore-backup', function() {
        const backupId = $(this).data('backup-id');
        
        if (confirm('¿Está seguro de que desea restaurar este backup? Se perderán todos los cambios actuales.')) {
            $.ajax({
                url: '{{ route("admin.template-editor.restore", "") }}/' + backupId,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('¡Éxito!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error al restaurar el backup', 'error');
                }
            });
        }
    });
    
    // Insert variable at cursor position
    function insertVariableAtCursor(variable) {
        if (isSourceMode) {
            // Insert in source mode (textarea)
            const textarea = $('#template-source')[0];
            const cursorPos = textarea.selectionStart;
            const textBefore = textarea.value.substring(0, cursorPos);
            const textAfter = textarea.value.substring(cursorPos);

            let variableToInsert = variable;
            if (textBefore && !textBefore.endsWith(' ') && !textBefore.endsWith('\n')) {
                variableToInsert = ' ' + variableToInsert;
            }
            if (textAfter && !textAfter.startsWith(' ') && !textAfter.startsWith('\n')) {
                variableToInsert = variableToInsert + ' ';
            }

            const newValue = textBefore + variableToInsert + textAfter;
            $(textarea).val(newValue);

            const newCursorPos = cursorPos + variableToInsert.length;
            textarea.setSelectionRange(newCursorPos, newCursorPos);
            textarea.focus();
        } else {
            // Insert in visual mode (contenteditable)
            const editor = $('#template-content')[0];
            editor.focus();

            const selection = window.getSelection();
            const range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null;

            if (range) {
                const span = document.createElement('span');
                span.className = 'variable-placeholder';
                span.style.backgroundColor = '#e3f2fd';
                span.style.padding = '1px 3px';
                span.style.borderRadius = '3px';
                span.textContent = variable;

                range.deleteContents();
                range.insertNode(span);

                // Move cursor after the inserted variable
                range.setStartAfter(span);
                range.setEndAfter(span);
                selection.removeAllRanges();
                selection.addRange(range);
            } else {
                // Fallback: append at the end
                const span = document.createElement('span');
                span.className = 'variable-placeholder';
                span.style.backgroundColor = '#e3f2fd';
                span.style.padding = '1px 3px';
                span.style.borderRadius = '3px';
                span.textContent = ' ' + variable + ' ';
                editor.appendChild(span);
            }
        }

        // Visual feedback
        $(activeEditor).addClass('variable-modified');
        setTimeout(() => {
            $(activeEditor).removeClass('variable-modified');
        }, 1000);
    }
    
    function showLoading() {
        if ($('.loading-overlay').length === 0) {
            $('body').append(`
                <div class="loading-overlay">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                        <div>Procesando...</div>
                    </div>
                </div>
            `);
        }
        $('.loading-overlay').show();
    }
    
    function hideLoading() {
        $('.loading-overlay').hide();
    }

    function loadPrestamos() {
        $.ajax({
            url: '{{ route("admin.template-editor.prestamos") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const selector = $('#prestamo-selector');
                    selector.empty().append('<option value="">Seleccionar préstamo para previsualizar...</option>');

                    response.prestamos.forEach(function(prestamo) {
                        selector.append(`<option value="${prestamo.id}">${prestamo.codigo} - ${prestamo.cliente_nombre} (S/ ${prestamo.cantidad_solicitada})</option>`);
                    });
                }
            },
            error: function() {
                console.log('Error al cargar los préstamos');
            }
        });
    }

    function loadPrestamoData(prestamoId) {
        showLoading();

        $.ajax({
            url: '{{ route("admin.template-editor.load-data") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                prestamo_id: prestamoId
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    // Replace variables in template with real data
                    let templateContent = $('#template-content').val();

                    Object.keys(response.data).forEach(function(key) {
                        const variablePattern = new RegExp('\\{\\{' + key + '\\}\\}', 'g');
                        templateContent = templateContent.replace(variablePattern, response.data[key]);
                    });

                    $('#template-content').val(templateContent);

                    // Visual feedback
                    $('#template-content').addClass('variable-modified');
                    setTimeout(() => {
                        $('#template-content').removeClass('variable-modified');
                    }, 1000);

                    Swal.fire('¡Éxito!', 'Datos del préstamo cargados correctamente', 'success');
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                hideLoading();
                let message = 'Error al cargar los datos del préstamo';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire('Error', message, 'error');
            }
        });
    }
});
</script>
@stop