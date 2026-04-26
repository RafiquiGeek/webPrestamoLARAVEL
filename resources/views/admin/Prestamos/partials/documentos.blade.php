<!-- Sidebar de Documentos -->
<div class="documents-sidebar" id="documentsSidebar">
    <div class="sidebar-header">
        <h5><i class="fas fa-folder-open mr-2"></i>Documentos del Préstamo</h5>
        <button type="button" class="btn-close-sidebar" id="closeSidebar">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="sidebar-content">
        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs sidebar-tabs" id="documentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="generate-tab" data-bs-toggle="tab" data-bs-target="#generate-panel" 
                        type="button" role="tab" aria-controls="generate-panel" aria-selected="false">
                    <i class="fas fa-file-contract mr-1"></i>Generar
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload-panel" 
                        type="button" role="tab" aria-controls="upload-panel" aria-selected="true">
                    <i class="fas fa-cloud-upload-alt mr-1"></i>Subir
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-panel" 
                        type="button" role="tab" aria-controls="list-panel" aria-selected="false">
                    <i class="fas fa-list mr-1"></i>Archivos
                </button>
            </li>
        </ul>
        
        <!-- Tabs Content -->
        <div class="tab-content sidebar-tab-content" id="documentTabsContent">
            <!-- Panel de Generación de Documentos -->
            <div class="tab-pane fade show active" id="generate-panel" role="tabpanel" aria-labelledby="generate-tab">
                <div class="generate-documents">
                    <h6 class="mb-3">
                        <i class="fas fa-magic mr-1"></i>Documentos Disponibles
                    </h6>
                    
                    <div class="document-generator-item mb-2" id="contrato-mutuo-section">
                        <button class="btn btn-outline-primary w-100" id="contrato-mutuo-btn"
                                onclick="generateDocument('contrato_mutuo', {{ $prestamo->id }})">
                            <i class="fas fa-file-contract mr-2"></i>Contrato de Mutuo
                        </button>
                        
                        <!-- Botones de previsualización y descarga (inicialmente ocultos) -->
                        <div id="contrato-mutuo-actions" style="display: none;" class="mt-2">
                            <div class="btn-group w-100" role="group">
                                <button class="btn btn-outline-info" onclick="previewContratoMutuo({{ $prestamo->id }})" title="Previsualizar">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-primary flex-fill" onclick="downloadContratoMutuo({{ $prestamo->id }})">
                                    <i class="fas fa-download mr-1"></i>Descargar Contrato
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="document-generator-item mb-2" id="carta-no-adeudo-section">
                        <button class="btn btn-outline-success w-100" id="carta-no-adeudo-btn"
                                onclick="generateDocument('carta_no_adeudo', {{ $prestamo->id }})">
                            <i class="fas fa-file-check mr-2"></i>Carta de No Adeudo
                        </button>
                        
                        <!-- Botones de previsualización y descarga (inicialmente ocultos) -->
                        <div id="carta-no-adeudo-actions" style="display: none;" class="mt-2">
                            <div class="btn-group w-100" role="group">
                                <button class="btn btn-outline-info" onclick="previewCartaNoAdeudo({{ $prestamo->id }})" title="Previsualizar">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success flex-fill" onclick="downloadCartaNoAdeudo({{ $prestamo->id }})">
                                    <i class="fas fa-download mr-1"></i>Descargar Carta
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <small>
                            <i class="fas fa-info-circle mr-1"></i>
                            Los documentos se generarán automáticamente con la información actual del préstamo.
                        </small>
                    </div>
                </div>
            </div>
            <!-- Panel de Subida de Documentos -->
            <div class="tab-pane fade" id="upload-panel" role="tabpanel" aria-labelledby="upload-tab">
                <form id="uploadDocumentForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="prestamo_id" value="{{ $prestamo->id }}">
                    
                    <div class="form-group mb-3">
                        <label for="document_type" class="form-label">
                            <i class="fas fa-tag mr-1"></i>Tipo de Documento
                        </label>
                        <select class="form-select" name="document_type" id="document_type" required>
                            <option value="">Seleccionar tipo...</option>
                            <option value="dni_cliente">DNI del Cliente</option>
                            <option value="recibo_ingresos">Recibo de Ingresos</option>
                            <option value="garantia">Garantía</option>
                            <option value="referencia_personal">Referencia Personal</option>
                            <option value="autorizacion">Autorización</option>
                            <option value="comprobante_pago">Comprobante de Pago</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3" id="custom_type_group" style="display: none;">
                        <label for="custom_type" class="form-label">
                            <i class="fas fa-edit mr-1"></i>Especificar Tipo
                        </label>
                        <input type="text" class="form-control" name="custom_type" id="custom_type" 
                               placeholder="Ingrese el tipo de documento">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="document_file" class="form-label">
                            <i class="fas fa-file mr-1"></i>Archivo
                        </label>
                        <input type="file" class="form-control" name="document_file" id="document_file" 
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                        <small class="form-text text-muted">
                            Formatos permitidos: PDF, DOC, DOCX, JPG, PNG (Máx. 20MB)
                        </small>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="description" class="form-label">
                            <i class="fas fa-comment mr-1"></i>Descripción (Opcional)
                        </label>
                        <textarea class="form-control" name="description" id="description" rows="3" 
                                  placeholder="Descripción del documento..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100" id="uploadBtn">
                        <i class="fas fa-upload mr-1"></i>Subir Documento
                    </button>
                </form>
                
                <!-- Progress Bar -->
                <div class="progress mt-3" id="uploadProgress" style="display: none;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%" id="progressBar"></div>
                </div>
            </div>
            <!-- Panel de Lista de Documentos -->
            <div class="tab-pane fade" id="list-panel" role="tabpanel" aria-labelledby="list-tab">
                <div class="documents-list" id="documentsList">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">
                            <i class="fas fa-archive mr-1"></i>Archivos Subidos
                        </h6>
                        <button class="btn btn-sm btn-outline-secondary" onclick="refreshDocumentsList()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    
                    <div id="documentsContainer">
                        <!-- Los documentos se cargarán aquí dinámicamente -->
                        <div class="text-center text-muted py-4" id="noDocuments">
                            <i class="fas fa-folder-open fa-2x mb-2"></i>
                            <p>No hay documentos subidos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Overlay para cerrar el sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<style>
/* Estilos del Sidebar de Documentos */
.documents-sidebar {
    position: fixed;
    top: 0;
    right: -400px;
    width: 400px;
    height: 100vh;
    background: white;
    box-shadow: -2px 0 10px rgba(0,0,0,0.1);
    z-index: 1050;
    transition: right 0.3s ease;
    overflow-y: auto;
}

.documents-sidebar.open {
    right: 0;
}

.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
    display: flex;
    justify-content: between;
    align-items: center;
    margin-top: 65px;
}

.sidebar-header h5 {
    margin: 0;
    color: #495057;
    font-weight: 600;
}

.btn-close-sidebar {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #6c757d;
    cursor: pointer;
    padding: 0;
    margin-left: auto;
}

.btn-close-sidebar:hover {
    color: #dc3545;
}

.sidebar-content {
    padding: 0;
}

.sidebar-tabs {
    border-bottom: 1px solid #dee2e6;
    padding: 0 20px;
    background: #f8f9fa;
}

.sidebar-tabs .nav-link {
    border: none;
    color: #6c757d;
    font-size: 0.9rem;
    padding: 12px 16px;
}

.sidebar-tabs .nav-link.active {
    color: #0d6efd;
    border-bottom: 2px solid #0d6efd;
    background: white;
}

.sidebar-tab-content {
    padding: 20px;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
}

.document-generator-item button {
    text-align: left;
    padding: 12px 16px;
}

.document-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}

.document-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    border-color: #007bff;
}

.document-header {
    padding: 12px 15px 8px;
    border-bottom: 1px solid #f1f3f4;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.document-type-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.document-meta {
    font-size: 0.7rem;
}

.document-body {
    padding: 12px 15px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.document-icon {
    font-size: 1.8rem;
    min-width: 35px;
    text-align: center;
}

.document-details {
    flex: 1;
    min-width: 0;
}

.document-name {
    font-weight: 600;
    color: #495057;
    font-size: 0.9rem;
    margin-bottom: 4px;
    word-break: break-word;
}

.document-description {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 6px;
    font-style: italic;
    line-height: 1.3;
}

.document-uploader {
    font-size: 0.75rem;
    color: #8e9196;
    display: flex;
    align-items: center;
    gap: 4px;
}

.document-actions {
    padding: 0 15px 12px;
    display: flex;
    gap: 6px;
    justify-content: flex-end;
}

.document-actions .btn {
    padding: 4px 8px;
    font-size: 0.8rem;
}

/* Colores específicos para tipos de documento */
.document-type-badge[data-type="dni_cliente"] {
    background: #fff3cd;
    color: #856404;
}

.document-type-badge[data-type="recibo_ingresos"] {
    background: #d1ecf1;
    color: #0c5460;
}

.document-type-badge[data-type="garantia"] {
    background: #d4edda;
    color: #155724;
}

.document-type-badge[data-type="referencia_personal"] {
    background: #e2e3e5;
    color: #383d41;
}

.document-type-badge[data-type="autorizacion"] {
    background: #fce4ec;
    color: #ad1457;
}

.document-type-badge[data-type="comprobante_pago"] {
    background: #f3e5f5;
    color: #7b1fa2;
}

.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1049;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    pointer-events: none;
}
/*
.sidebar-overlay.show {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
    display: none;
}
*/
/* Estilos para modales de previsualización */
.pdf-preview-modal .swal2-popup {
    max-width: 90vw !important;
    max-height: 90vh !important;
}

.swal2-html-container img {
    max-width: 100% !important;
    height: auto !important;
}

/* Responsive */
@media (max-width: 768px) {
    .documents-sidebar {
        width: 100%;
        right: -100%;
    }
    
    .document-card {
        margin-bottom: 12px;
    }
    
    .document-body {
        padding: 10px 12px;
    }
    
    .document-header {
        padding: 10px 12px 6px;
    }
    
    .document-actions {
        padding: 0 12px 10px;
    }
    
    .document-actions .btn {
        padding: 6px 10px;
    }
}
</style>

<script>
// Funciones JavaScript para el sidebar de documentos
document.addEventListener('DOMContentLoaded', function() {
    // Handle custom document type
    const documentTypeSelect = document.getElementById('document_type');
    if (documentTypeSelect) {
        documentTypeSelect.addEventListener('change', function() {
            const customGroup = document.getElementById('custom_type_group');
            if (this.value === 'otro') {
                customGroup.style.display = 'block';
                document.getElementById('custom_type').required = true;
            } else {
                customGroup.style.display = 'none';
                document.getElementById('custom_type').required = false;
            }
        });
    }
    
    // Handle file upload
    const uploadForm = document.getElementById('uploadDocumentForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            uploadDocument();
        });
    }
    
    // Load documents list when opening the tab
    const listTab = document.getElementById('list-tab');
    if (listTab) {
        listTab.addEventListener('shown.bs.tab', function() {
            loadDocumentsList();
        });
    }
    
    // Check carta no adeudo existence when opening generate tab
    const generateTab = document.getElementById('generate-tab');
    if (generateTab) {
        generateTab.addEventListener('shown.bs.tab', function() {
            checkCartaNoAdeudoExists({{ $prestamo->id }});
            checkContratoMutuoExists({{ $prestamo->id }});
        });
    }
    
    // Check carta no adeudo existence on initial load if generate tab is active
    if (document.getElementById('generate-panel').classList.contains('show')) {
        checkCartaNoAdeudoExists({{ $prestamo->id }});
        checkContratoMutuoExists({{ $prestamo->id }});
    }
});

function uploadDocument() {
    const form = document.getElementById('uploadDocumentForm');
    const uploadBtn = document.getElementById('uploadBtn');
    const progressContainer = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    
    // Client-side validation
    const documentType = document.getElementById('document_type').value;
    const documentFile = document.getElementById('document_file').files[0];
    const customType = document.getElementById('custom_type').value;
    
    // Validate required fields
    if (!documentType) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor seleccione el tipo de documento'
        });
        return;
    }
    
    if (documentType === 'otro' && !customType.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor especifique el tipo de documento'
        });
        return;
    }
    
    if (!documentFile) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo requerido',
            text: 'Por favor seleccione un archivo'
        });
        return;
    }
    
    // Get file extension first (needed for other validations)
    const fileExtension = documentFile.name.split('.').pop().toLowerCase();
    const allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    
    // Validate file type
    if (!allowedExtensions.includes(fileExtension)) {
        Swal.fire({
            icon: 'warning',
            title: 'Tipo de archivo no permitido',
            text: `Formatos permitidos: ${allowedExtensions.join(', ').toUpperCase()}`
        });
        return;
    }
    
    // Validate file size (50MB = 50 * 1024 * 1024 bytes)
    const maxSize = 50 * 1024 * 1024;
    if (documentFile.size > maxSize) {
        Swal.fire({
            icon: 'warning',
            title: 'Archivo muy grande',
            text: `El archivo debe ser menor a 50MB. Tamaño actual: ${(documentFile.size / 1024 / 1024).toFixed(2)}MB`
        });
        return;
    }
    
    // Check if file has content
    if (documentFile.size === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Archivo vacío',
            text: 'El archivo seleccionado está vacío o corrupto'
        });
        return;
    }
    
    // Additional validation for PDF files from scanners
    if (fileExtension === 'pdf' && documentFile.size < 1024) {
        Swal.fire({
            icon: 'warning',
            title: 'PDF sospechoso',
            text: 'El PDF parece ser muy pequeño. Verifique que el archivo se haya generado correctamente.'
        });
        return;
    }
    
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Subiendo...';
    progressContainer.style.display = 'block';
    
    // Log file information for debugging
    console.log('Archivo a subir:', {
        nombre: documentFile.name,
        tamaño: `${(documentFile.size / 1024 / 1024).toFixed(2)}MB`,
        tipo: documentFile.type,
        lastModified: new Date(documentFile.lastModified).toLocaleString()
    });
    
    // Crear FormData después de todas las validaciones
    const formData = new FormData(form);
    
    // Verificar que el archivo esté en el FormData
    console.log('FormData contents:');
    for (let [key, value] of formData.entries()) {
        if (key === 'document_file' && value instanceof File) {
            console.log(`  ${key}: File(${value.name}, ${value.size} bytes)`);
        } else {
            console.log(`  ${key}: ${value}`);
        }
    }
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    console.log('CSRF token present:', !!csrfToken);
    
    fetch('/admin/prestamos/documents/upload', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        // For 422 validation errors, we still want to parse the JSON to get error details
        if (response.status === 422) {
            return response.json().then(data => {
                let errorMessage = 'Error de validación del servidor';
                if (data.message) {
                    errorMessage = data.message;
                }
                if (data.errors) {
                    const errorDetails = Object.values(data.errors).flat().join('\n• ');
                    if (errorDetails) {
                        errorMessage += '\n\nErrores específicos:\n• ' + errorDetails;
                    }
                }
                
                // Add diagnostic information
                errorMessage += '\n\n📋 Información de diagnóstico:';
                errorMessage += `\n• Nombre: ${documentFile.name}`;
                errorMessage += `\n• Tamaño: ${(documentFile.size / 1024 / 1024).toFixed(2)}MB`;
                errorMessage += `\n• Tipo MIME: ${documentFile.type || 'no detectado'}`;
                errorMessage += `\n• Extensión: ${fileExtension}`;
                
                // Specific suggestions for PDF issues
                if (documentFile.name.toLowerCase().includes('camscanner') || 
                    errorDetails.toLowerCase().includes('document_file')) {
                    errorMessage += '\n\n💡 Sugerencias para PDFs de CamScanner:';
                    errorMessage += '\n• Intente exportar el PDF nuevamente desde CamScanner';
                    errorMessage += '\n• Use la opción "PDF de alta calidad" si está disponible';
                    errorMessage += '\n• Como alternativa, exporte como imagen JPG/PNG';
                    errorMessage += '\n• Verifique que el archivo no esté protegido con contraseña';
                }
                
                throw new Error(errorMessage);
            });
        }
        
        // Check if the response is ok
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Respuesta no es JSON válido');
        }
        return response.json();
    })
    .then(data => {
        console.log('Server response:', data);
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito!',
                text: 'Documento subido correctamente',
                timer: 2000,
                showConfirmButton: false
            });
            form.reset();
            document.getElementById('custom_type_group').style.display = 'none';
            loadDocumentsList();
        } else {
            // Show detailed error information
            let errorMessage = data.message || 'Error al subir el documento';
            if (data.errors) {
                const errorDetails = Object.values(data.errors).flat().join('\n• ');
                if (errorDetails) {
                    errorMessage += '\n\nDetalles:\n• ' + errorDetails;
                }
            }
            throw new Error(errorMessage);
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error al subir documento',
            html: error.message.replace(/\n/g, '<br>'),
            width: '500px'
        });
    })
    .finally(() => {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="fas fa-upload mr-1"></i>Subir Documento';
        progressContainer.style.display = 'none';
        progressBar.style.width = '0%';
    });
}

function generateDocument(type, prestamoId) {
    Swal.fire({
        title: 'Generando documento...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Handle different document types
    switch(type) {
        case 'carta_no_adeudo':
            generateCartaNoAdeudo(prestamoId);
            break;
        case 'contrato_mutuo':
            generateContratoMutuo(prestamoId);
            break;
        case 'cronograma_pagos':
        case 'estado_cuenta':
            // These will be implemented later
            setTimeout(() => {
                Swal.fire({
                    icon: 'info',
                    title: 'Función en desarrollo',
                    text: 'La generación de ' + type + ' estará disponible próximamente'
                });
            }, 1000);
            break;
        default:
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Tipo de documento no reconocido'
            });
    }
}

function generateCartaNoAdeudo(prestamoId) {
    const url = `/admin/prestamos/${prestamoId}/generate-carta-no-adeudo`;
    
    fetch(url, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Respuesta no es JSON válido');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Documento generado!',
                text: 'La carta de no adeudo se ha generado correctamente',
                timer: 3000,
                showConfirmButton: false
            });
            
            // Cambiar la interfaz para mostrar botones de descarga y previsualización
            updateCartaNoAdeudoInterface(true);
            
        } else {
            throw new Error(data.message || 'Error al generar el documento');
        }
    })
    .catch(error => {
        console.error('Error generating document:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error al generar documento',
            text: error.message || 'No se pudo generar la carta de no adeudo. Verifique que el préstamo esté liquidado o finalizado y sin deudas pendientes.'
        });
    });
}

function previewCartaNoAdeudo(prestamoId) {
    const previewUrl = `/admin/prestamos/${prestamoId}/preview-carta-no-adeudo`;
    window.open(previewUrl, '_blank');
}

function downloadCartaNoAdeudo(prestamoId) {
    const downloadUrl = `/admin/prestamos/${prestamoId}/download-carta-no-adeudo`;
    window.open(downloadUrl, '_blank');
}

function checkCartaNoAdeudoExists(prestamoId) {
    const checkUrl = `/admin/prestamos/${prestamoId}/check-carta-no-adeudo`;
    
    fetch(checkUrl)
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Respuesta no es JSON válido');
        }
        return response.json();
    })
    .then(data => {
        updateCartaNoAdeudoInterface(data.exists);
    })
    .catch(error => {
        console.error('Error checking carta existence:', error);
        // En caso de error, asumir que no existe
        updateCartaNoAdeudoInterface(false);
    });
}

function updateCartaNoAdeudoInterface(exists) {
    const generateBtn = document.getElementById('carta-no-adeudo-btn');
    const actionsDiv = document.getElementById('carta-no-adeudo-actions');
    
    if (exists) {
        // Ocultar botón de generar y mostrar botones de previsualizar/descargar
        generateBtn.style.display = 'none';
        actionsDiv.style.display = 'block';
    } else {
        // Mostrar botón de generar y ocultar otros botones
        generateBtn.style.display = 'block';
        actionsDiv.style.display = 'none';
    }
}

function loadDocumentsList() {
    const container = document.getElementById('documentsContainer');
    container.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
    
    fetch(`/admin/prestamos/{{ $prestamo->id }}/documents`)
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Respuesta no es JSON válido');
        }
        return response.json();
    })
    .then(data => {
        if (data.documents && data.documents.length > 0) {
            let html = '';
            data.documents.forEach(doc => {
                // Truncar nombre de archivo si es muy largo
                const truncatedName = doc.original_name.length > 25 
                    ? doc.original_name.substring(0, 22) + '...' 
                    : doc.original_name;
                
                // Determinar icono según tipo de archivo
                const fileExtension = doc.original_name.split('.').pop().toLowerCase();
                let fileIcon = 'fas fa-file';
                let fileColor = '#6c757d';
                
                switch(fileExtension) {
                    case 'pdf':
                        fileIcon = 'fas fa-file-pdf';
                        fileColor = '#dc3545';
                        break;
                    case 'doc':
                    case 'docx':
                        fileIcon = 'fas fa-file-word';
                        fileColor = '#2b5797';
                        break;
                    case 'jpg':
                    case 'jpeg':
                    case 'png':
                        fileIcon = 'fas fa-file-image';
                        fileColor = '#28a745';
                        break;
                }
                
                // Determinar si se puede previsualizar
                const canPreview = ['pdf', 'jpg', 'jpeg', 'png'].includes(fileExtension);
                
                html += `
                    <div class="document-card mb-3">
                        <div class="document-header">
                            <div class="document-type-badge">
                                <i class="fas fa-tag"></i>
                                ${doc.document_type}
                            </div>
                            <div class="document-meta">
                                <small class="text-muted">${doc.created_at} • ${doc.file_size}</small>
                            </div>
                        </div>
                        
                        <div class="document-body">
                            <div class="document-icon">
                                <i class="${fileIcon}" style="color: ${fileColor}"></i>
                            </div>
                            <div class="document-details">
                                <div class="document-name" title="${doc.original_name}">
                                    ${truncatedName}
                                </div>
                                ${doc.description ? `
                                    <div class="document-description">
                                        ${doc.description}
                                    </div>
                                ` : ''}
                                <div class="document-uploader">
                                    <i class="fas fa-user-circle"></i>
                                    ${doc.uploaded_by}
                                </div>
                            </div>
                        </div>
                        
                        <div class="document-actions">
                            ${canPreview ? `
                                <button class="btn btn-sm btn-outline-info" onclick="previewDocument(${doc.id}, '${fileExtension}')" 
                                        title="Previsualizar">
                                    <i class="fas fa-eye"></i>
                                </button>
                            ` : ''}
                            <button class="btn btn-sm btn-outline-success" onclick="downloadDocument(${doc.id})" 
                                    title="Descargar">
                                <i class="fas fa-download"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning" onclick="debugDocument(${doc.id})" 
                                    title="Debug">
                                <i class="fas fa-bug"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteDocument(${doc.id})" 
                                    title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        } else {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-folder-open fa-2x mb-2"></i>
                    <p>No hay documentos subidos</p>
                </div>
            `;
        }
    })
    .catch(error => {
        container.innerHTML = `
            <div class="text-center text-danger py-4">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                <p>Error al cargar los documentos</p>
            </div>
        `;
    });
}

function refreshDocumentsList() {
    loadDocumentsList();
}

function downloadDocument(documentId) {
    window.open(`/admin/prestamos/documents/${documentId}/download`, '_blank');
}

function debugDocument(documentId) {
    fetch(`/admin/prestamos/documents/${documentId}/debug`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Respuesta no es JSON válido');
            }
            return response.json();
        })
        .then(data => {
            console.log('Debug info:', data);
            Swal.fire({
                title: 'Información de Debug',
                html: `
                    <div style="text-align: left; font-family: monospace; font-size: 12px;">
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `,
                width: '80%',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    htmlContainer: 'text-left'
                }
            });
        })
        .catch(error => {
            console.error('Debug error:', error);
            Swal.fire('Error', 'No se pudo obtener información de debug', 'error');
        });
}

function previewDocument(documentId, fileExtension) {
    const previewUrl = `/admin/prestamos/documents/${documentId}/preview`;
    const downloadUrl = `/admin/prestamos/documents/${documentId}/download`;
    
    console.log('Preview document:', documentId, fileExtension);
    console.log('Preview URL:', previewUrl);
    
    // Buscar la información del documento en los datos cargados
    const documentInfo = findDocumentInCurrentList(documentId);
    
    if (documentInfo && documentInfo.public_url && documentInfo.file_exists) {
        console.log('Using public URL:', documentInfo.public_url);
        showPreviewModal(documentId, fileExtension, documentInfo.public_url, downloadUrl);
    } else {
        // Fallback al método original
        fetch(previewUrl, { method: 'HEAD' })
            .then(response => {
                console.log('HEAD request response:', response.status, response.statusText);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                showPreviewModal(documentId, fileExtension, previewUrl, downloadUrl);
            })
            .catch(error => {
                console.error('Error accessing preview URL:', error);
                Swal.fire({
                    title: 'Error de acceso',
                    text: 'No se puede acceder al documento: ' + error.message,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    confirmButtonText: 'Descargar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        downloadDocument(documentId);
                    }
                });
            });
    }
}

function findDocumentInCurrentList(documentId) {
    // Esta función intenta encontrar la información del documento en los datos ya cargados
    // Esto es un workaround hasta que identifiquemos el problema principal
    const documentCards = document.querySelectorAll('.document-card');
    for (let card of documentCards) {
        const previewBtn = card.querySelector(`button[onclick*="${documentId}"]`);
        if (previewBtn) {
            // Intenta extraer información del DOM
            return {
                public_url: null, // No tenemos esta info en el DOM actualmente
                file_exists: true // Asumimos que existe si está en la lista
            };
        }
    }
    return null;
}

function showPreviewModal(documentId, fileExtension, previewUrl, downloadUrl) {
    // Mostrar indicador de carga
    Swal.fire({
        title: 'Cargando vista previa...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    if (fileExtension === 'pdf') {
        // Mostrar PDF en modal
        setTimeout(() => {
            Swal.fire({
                title: 'Vista Previa del Documento PDF',
                html: `
                    <div style="width: 100%; height: 600px; position: relative;">
                        <div id="pdf-loading-${documentId}" style="text-align: center; padding: 50px;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #007bff; margin-bottom: 15px;"></i>
                            <p>Cargando documento PDF...</p>
                        </div>
                        <iframe id="pdf-iframe-${documentId}" src="${previewUrl}#toolbar=0&navpanes=0&scrollbar=0" 
                                style="width: 100%; height: 100%; border: 1px solid #ddd; border-radius: 4px; display: none;"
                                onload="handlePdfLoad(${documentId})"
                                onerror="handlePreviewError(${documentId})">
                        </iframe>
                        <div id="iframe-fallback-${documentId}" style="display: none; text-align: center; padding: 50px;">
                            <i class="fas fa-file-pdf" style="font-size: 3rem; color: #dc3545; margin-bottom: 15px;"></i>
                            <p>Vista previa no disponible en este navegador.</p>
                            <div style="margin-top: 20px;">
                                <button class="btn btn-primary mr-2" onclick="window.open('${previewUrl}', '_blank')">
                                    <i class="fas fa-external-link-alt mr-1"></i>Abrir en nueva pestaña
                                </button>
                                <button class="btn btn-success" onclick="downloadDocument(${documentId})">
                                    <i class="fas fa-download mr-1"></i>Descargar archivo
                                </button>
                            </div>
                        </div>
                    </div>
                `,
                width: '85%',
                showCloseButton: true,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Descargar',
                cancelButtonColor: '#28a745',
                customClass: {
                    container: 'pdf-preview-modal'
                },
                didOpen: () => {
                    // Fallback si el iframe no carga en 5 segundos
                    setTimeout(() => {
                        const iframe = document.getElementById(`pdf-iframe-${documentId}`);
                        const loading = document.getElementById(`pdf-loading-${documentId}`);
                        if (iframe && iframe.style.display === 'none' && loading) {
                            handlePreviewError(documentId);
                        }
                    }, 5000);
                }
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    downloadDocument(documentId);
                }
            });
        }, 500);
        
    } else if (['jpg', 'jpeg', 'png'].includes(fileExtension)) {
        // Mostrar imagen en modal
        const img = new Image();
        img.onload = function() {
            Swal.fire({
                title: 'Vista Previa de Imagen',
                html: `
                    <div style="text-align: center;">
                        <img src="${previewUrl}" 
                             style="max-width: 100%; max-height: 500px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"
                             alt="Vista previa del documento">
                    </div>
                `,
                width: '70%',
                showCloseButton: true,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Descargar',
                cancelButtonColor: '#28a745'
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    downloadDocument(documentId);
                }
            });
        };
        img.onerror = function() {
            Swal.fire({
                title: 'Error al cargar imagen',
                text: 'No se pudo cargar la vista previa de la imagen. ¿Desea descargarla?',
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: 'Descargar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    downloadDocument(documentId);
                }
            });
        };
        img.src = previewUrl;
        
    } else {
        // Para otros tipos de archivo, solo descargar
        Swal.fire({
            title: 'Vista previa no disponible',
            text: 'Este tipo de archivo no se puede previsualizar. ¿Desea descargarlo?',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Descargar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                downloadDocument(documentId);
            }
        });
    }
}

function handlePdfLoad(documentId) {
    // Ocultar loading y mostrar iframe cuando carga correctamente
    const loading = document.getElementById(`pdf-loading-${documentId}`);
    const iframe = document.getElementById(`pdf-iframe-${documentId}`);
    
    if (loading) loading.style.display = 'none';
    if (iframe) {
        iframe.style.display = 'block';
        iframe.style.opacity = '1';
    }
}

function handlePreviewError(documentId) {
    // Mostrar fallback cuando el iframe falla
    const loading = document.getElementById(`pdf-loading-${documentId}`);
    const iframe = document.getElementById(`pdf-iframe-${documentId}`);
    const fallback = document.getElementById(`iframe-fallback-${documentId}`);
    
    if (loading) loading.style.display = 'none';
    if (iframe) iframe.style.display = 'none';
    if (fallback) fallback.style.display = 'block';
}

function deleteDocument(documentId) {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/admin/prestamos/documents/${documentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Respuesta no es JSON válido');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Eliminado!', 'El documento ha sido eliminado.', 'success');
                    loadDocumentsList();
                } else {
                    throw new Error(data.message || 'Error al eliminar el documento');
                }
            })
            .catch(error => {
                Swal.fire('Error', error.message || 'Error al eliminar el documento', 'error');
            });
        }
    });
}

// Funciones específicas para el Contrato de Mutuo
function generateContratoMutuo(prestamoId) {
    const url = `/admin/prestamos/${prestamoId}/generate-contrato-mutuo`;
    
    fetch(url, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Respuesta no es JSON válido');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Documento generado!',
                text: 'El contrato de mutuo se ha generado correctamente',
                timer: 3000,
                showConfirmButton: false
            });
            
            // Cambiar la interfaz para mostrar botones de descarga y previsualización
            updateContratoMutuoInterface(true);
            
        } else {
            throw new Error(data.message || 'Error al generar el documento');
        }
    })
    .catch(error => {
        console.error('Error generating contract:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error al generar documento',
            text: error.message || 'No se pudo generar el contrato de mutuo. Verifique los datos del préstamo.'
        });
    });
}

function previewContratoMutuo(prestamoId) {
    const previewUrl = `/admin/prestamos/${prestamoId}/preview-contrato-mutuo`;
    window.open(previewUrl, '_blank');
}

function downloadContratoMutuo(prestamoId) {
    const downloadUrl = `/admin/prestamos/${prestamoId}/download-contrato-mutuo`;
    window.open(downloadUrl, '_blank');
}

function checkContratoMutuoExists(prestamoId) {
    const checkUrl = `/admin/prestamos/${prestamoId}/check-contrato-mutuo`;
    
    fetch(checkUrl)
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Respuesta no es JSON válido');
        }
        return response.json();
    })
    .then(data => {
        updateContratoMutuoInterface(data.exists);
    })
    .catch(error => {
        console.error('Error checking contract existence:', error);
        // En caso de error, asumir que no existe
        updateContratoMutuoInterface(false);
    });
}

function updateContratoMutuoInterface(exists) {
    const generateBtn = document.getElementById('contrato-mutuo-btn');
    const actionsDiv = document.getElementById('contrato-mutuo-actions');
    
    if (exists) {
        // Ocultar botón de generar y mostrar botones de previsualizar/descargar
        generateBtn.style.display = 'none';
        actionsDiv.style.display = 'block';
    } else {
        // Mostrar botón de generar y ocultar otros botones
        generateBtn.style.display = 'block';
        actionsDiv.style.display = 'none';
    }
}
</script>