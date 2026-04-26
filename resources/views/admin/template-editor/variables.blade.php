@extends('layouts.admin')

@section('title', 'Variables de Plantillas')

@section('content')
<div class="container-fluid pt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow border-0">
                <div class="card-header bg-info text-white">
                    <h4 class="m-0">
                        <i class="fas fa-cogs mr-2"></i>
                        Configuraci�n de Variables de Plantillas
                    </h4>
                </div>
                <div class="card-body">
                    
                    <!-- Selector de Pr�stamo para Datos Reales -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="m-0"><i class="fas fa-database mr-1"></i> Cargar Datos Reales</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="prestamo-selector">Seleccionar Pr�stamo:</label>
                                        <select class="form-control" id="prestamo-selector">
                                            <option value="">Seleccionar pr�stamo...</option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-block" id="load-real-data" disabled>
                                        <i class="fas fa-download mr-1"></i> Cargar Valores Reales
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-block mt-2" id="clear-data">
                                        <i class="fas fa-eraser mr-1"></i> Limpiar Valores
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="m-0"><i class="fas fa-info-circle mr-1"></i> Informaci�n</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2"><strong>Funcionalidad:</strong></p>
                                    <ul class="small">
                                        <li>Selecciona un pr�stamo existente</li>
                                        <li>Los valores reales se cargar�n en todos los campos</li>
                                        <li>Podr�s editar individualmente cada variable</li>
                                        <li>Los cambios se aplicar�n en la vista previa</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuraci�n de Variables -->
                    <div class="row">
                        <!-- Cliente -->
                        <div class="col-md-6 mb-4">
                            <h5 class="text-primary"><i class="fas fa-user"></i> Variables del Cliente</h5>
                            <div class="form-group">
                                <label>Nombre Completo</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="customer_name" placeholder="Nombre del cliente">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{customer_name}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>DNI</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="customer_dni" placeholder="DNI del cliente">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{customer_dni}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Direcci�n</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="customer_address" placeholder="Direcci�n del cliente">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{customer_address}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Tel�fono</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="customer_phone" placeholder="Tel�fono del cliente">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{customer_phone}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <div class="input-group">
                                    <input type="email" class="form-control" id="customer_email" placeholder="Email del cliente">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{customer_email}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empresa -->
                        <div class="col-md-6 mb-4">
                            <h5 class="text-success"><i class="fas fa-building"></i> Variables de la Empresa</h5>
                            <div class="form-group">
                                <label>Nombre de la Empresa</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="company_name" placeholder="Nombre de la empresa">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{company_name}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>RUC</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="company_ruc" placeholder="RUC de la empresa">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{company_ruc}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Direcci�n de la Empresa</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="company_address" placeholder="Direcci�n de la empresa">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{company_address}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Nombre del Gerente</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="manager_name" placeholder="Nombre del gerente">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{manager_name}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>DNI del Gerente</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="manager_dni" placeholder="DNI del gerente">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{manager_dni}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pr�stamo -->
                        <div class="col-md-6 mb-4">
                            <h5 class="text-warning"><i class="fas fa-money-bill"></i> Variables del Préstamo</h5>
                            <div class="form-group">
                                <label>Monto del Pr�stamo</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="loan_amount" placeholder="0.00">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{loan_amount}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Monto en Letras</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="loan_amount_words" placeholder="Cantidad en letras">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{loan_amount_words}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Tasa de Inter�s (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="interest_rate" placeholder="0.00" step="0.01">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{interest_rate}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Monto de Cuota</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="installment_amount" placeholder="0.00" step="0.01">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{installment_amount}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>N�mero de Cuotas</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="total_installments" placeholder="0">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{total_installments}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>N�mero de Contrato</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="contract_number" placeholder="N�mero de contrato">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{contract_number}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fechas y Aval -->
                        <div class="col-md-6 mb-4">
                            <h5 class="text-danger"><i class="fas fa-calendar"></i> Variables de Fechas</h5>
                            <div class="form-group">
                                <label>Fecha de Desembolso</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" id="disbursement_date">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{disbursement_date}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Fecha del Contrato</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" id="contract_date">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{contract_date}}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="text-info mt-4"><i class="fas fa-handshake"></i> Variables del Aval/Fiador</h5>
                            <div class="form-group">
                                <label>Nombre del Aval</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="guarantor_name" placeholder="Nombre del aval">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{guarantor_name}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>DNI del Aval</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="guarantor_dni" placeholder="DNI del aval">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{guarantor_dni}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Tel�fono del Aval</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="guarantor_phone" placeholder="Tel�fono del aval">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{guarantor_phone}}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Direcci�n del Aval</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="guarantor_address" placeholder="Direcci�n del aval">
                                    <div class="input-group-append">
                                        <span class="badge badge-info">@{{guarantor_address}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acci�n -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="text-center">
                                <button type="button" class="btn btn-success btn-lg" id="save-variables">
                                    <i class="fas fa-save mr-1"></i> Guardar Variables
                                </button>
                                <button type="button" class="btn btn-info btn-lg ml-2" id="preview-with-variables">
                                    <i class="fas fa-eye mr-1"></i> Vista Previa con Variables
                                </button>
                                <a href="{{ route('admin.template-editor.index') }}" class="btn btn-secondary btn-lg ml-2">
                                    <i class="fas fa-arrow-left mr-1"></i> Volver al Editor
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista Previa con Variables Personalizadas</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="preview-content" style="height: 600px; overflow-y: auto;">
                <!-- Preview content will be loaded here -->
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
    .input-group-append .badge {
        font-size: 10px;
        padding: 8px 10px;
        border-radius: 0 0.25rem 0.25rem 0;
    }
    
    .card {
        transition: box-shadow 0.2s;
    }
    
    .card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .form-group label {
        font-weight: 600;
        font-size: 13px;
    }
    
    #prestamo-selector {
        height: 38px;
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
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Cargar lista de pr�stamos
    loadPrestamos();
    
    // Event handlers
    $('#prestamo-selector').change(function() {
        $('#load-real-data').prop('disabled', !$(this).val());
    });
    
    $('#load-real-data').click(function() {
        loadRealData();
    });
    
    $('#clear-data').click(function() {
        clearAllVariables();
    });
    
    $('#save-variables').click(function() {
        saveVariables();
    });
    
    $('#preview-with-variables').click(function() {
        previewWithVariables();
    });
    
    function loadPrestamos() {
        $.ajax({
            url: '{{ route("admin.template-editor.prestamos") }}',
            method: 'GET',
            success: function(response) {
                const selector = $('#prestamo-selector');
                selector.empty().append('<option value="">Seleccionar pr�stamo...</option>');
                
                if (response.success && response.prestamos.length > 0) {
                    response.prestamos.forEach(function(prestamo) {
                        selector.append(`<option value="${prestamo.id}">${prestamo.codigo} - ${prestamo.cliente_nombre} - S/ ${prestamo.cantidad_solicitada}</option>`);
                    });
                } else {
                    selector.append('<option value="" disabled>No hay pr�stamos disponibles</option>');
                }
            },
            error: function() {
                console.error('Error cargando pr�stamos');
                const selector = $('#prestamo-selector');
                selector.empty().append('<option value="" disabled>Error cargando pr�stamos</option>');
            }
        });
    }
    
    function loadRealData() {
        const prestamoId = $('#prestamo-selector').val();
        if (!prestamoId) return;
        
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
                    // Llenar todos los campos con los datos reales
                    Object.keys(response.data).forEach(key => {
                        $('#' + key).val(response.data[key]);
                    });
                    
                    Swal.fire('¡Datos Cargados!', 'Se han cargado los datos del préstamo seleccionado', 'success');
                } else {
                    Swal.fire('Error', 'No se pudieron cargar los datos del préstamo', 'error');
                }
            },
            error: function(xhr) {
                hideLoading();
                console.error('Error cargando datos:', xhr);
                Swal.fire('Error', 'Error al cargar los datos del préstamo', 'error');
            }
        });
    }
    
    function clearAllVariables() {
        $('input[type="text"], input[type="email"], input[type="number"], input[type="date"]').val('');
        Swal.fire('Limpiado', 'Todas las variables han sido limpiadas', 'info');
    }
    
    function saveVariables() {
        const variables = {};
        
        // Recopilar todos los valores
        $('input[id]').each(function() {
            variables[this.id] = $(this).val();
        });
        
        $.ajax({
            url: '{{ route("admin.template-editor.save-variables") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                variables: variables
            },
            success: function(response) {
                if (response.success) {
                    // También guardar en localStorage como backup
                    localStorage.setItem('template_variables', JSON.stringify(variables));
                    Swal.fire('¡Guardado!', 'Variables guardadas correctamente', 'success');
                } else {
                    Swal.fire('Error', response.message || 'Error al guardar variables', 'error');
                }
            },
            error: function(xhr) {
                console.error('Error guardando variables:', xhr);
                Swal.fire('Error', 'Error al guardar las variables', 'error');
            }
        });
    }
    
    function previewWithVariables() {
        const variables = {};
        
        // Recopilar todos los valores
        $('input[id]').each(function() {
            variables[this.id] = $(this).val();
        });
        
        showLoading();
        
        $.ajax({
            url: '{{ route("admin.template-editor.preview-variables") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                variables: variables
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    $('#preview-content').html(response.html);
                    $('#previewModal').modal('show');
                } else {
                    Swal.fire('Error', response.message || 'Error al generar vista previa', 'error');
                }
            },
            error: function(xhr) {
                hideLoading();
                console.error('Error generando preview:', xhr);
                Swal.fire('Error', 'Error al generar la vista previa', 'error');
            }
        });
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
    
    // Cargar variables guardadas al inicio
    const savedVariables = localStorage.getItem('template_variables');
    if (savedVariables) {
        const variables = JSON.parse(savedVariables);
        Object.keys(variables).forEach(key => {
            $('#' + key).val(variables[key]);
        });
    }
});
</script>
@stop