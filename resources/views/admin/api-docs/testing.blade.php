@extends('layouts.admin')

@section('title', 'Testing API Móvil')

@section('content')
<div class="container-fluid">
    <div class="py-4">
        <!-- Header -->
        <div class="account-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-2">
                        <i class="fas fa-vial me-2"></i>Testing API Móvil
                        <span class="badge bg-info ms-2">Entorno de Pruebas</span>
                    </h3>
                    <p class="text-muted mb-0">Herramienta para probar endpoints de la API</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.api-docs.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver a Docs
                    </a>
                    <button type="button" class="btn btn-success" id="authBtn">
                        <i class="fas fa-key me-1"></i>Autenticar
                    </button>
                </div>
            </div>

            <div class="card-body">
                <!-- Panel de configuración -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="config-panel">
                            <h6><i class="fas fa-cog me-2"></i>Configuración</h6>
                            <div class="mb-3">
                                <label for="apiUrl" class="form-label">URL Base API</label>
                                <input type="text" class="form-control" id="apiUrl" value="{{ $apiUrl }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="authToken" class="form-label">Token de Autenticación</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="authToken" placeholder="Bearer token...">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleToken">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Obtenido mediante /auth/login</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="status-panel">
                            <h6><i class="fas fa-wifi me-2"></i>Estado de Conexión</h6>
                            <div id="connectionStatus" class="alert alert-secondary">
                                <i class="fas fa-circle text-secondary me-2"></i>No conectado
                            </div>
                            <div id="lastResponse" class="small text-muted">
                                Sin respuestas recientes
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel de testing -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="endpoints-panel">
                            <h6><i class="fas fa-list me-2"></i>Endpoints Disponibles</h6>
                            <div class="accordion" id="endpointsAccordion">
                                @foreach($endpoints as $category => $categoryEndpoints)
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading{{ $loop->index }}">
                                            <button class="accordion-button collapsed" type="button" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#collapse{{ $loop->index }}" 
                                                    aria-expanded="false">
                                                {{ $category }}
                                            </button>
                                        </h2>
                                        <div id="collapse{{ $loop->index }}" 
                                             class="accordion-collapse collapse" 
                                             data-bs-parent="#endpointsAccordion">
                                            <div class="accordion-body p-2">
                                                @foreach($categoryEndpoints as $endpoint => $description)
                                                    <div class="endpoint-item" 
                                                         data-endpoint="{{ $endpoint }}"
                                                         data-description="{{ $description }}">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <span class="badge bg-{{ strpos($endpoint, 'GET') !== false ? 'info' : 'success' }}">
                                                                    {{ explode(' ', $endpoint)[0] }}
                                                                </span>
                                                                <code class="small">{{ explode(' ', $endpoint)[1] ?? '' }}</code>
                                                            </div>
                                                            <button class="btn btn-sm btn-outline-primary test-endpoint">
                                                                <i class="fas fa-play"></i>
                                                            </button>
                                                        </div>
                                                        <div class="small text-muted mt-1">{{ $description }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="testing-panel">
                            <h6><i class="fas fa-terminal me-2"></i>Panel de Pruebas</h6>
                            
                            <!-- Request Panel -->
                            <div class="request-panel mb-3">
                                <div class="d-flex gap-2 mb-2">
                                    <select class="form-select" id="requestMethod" style="width: auto;">
                                        <option value="GET">GET</option>
                                        <option value="POST">POST</option>
                                        <option value="PUT">PUT</option>
                                        <option value="DELETE">DELETE</option>
                                    </select>
                                    <input type="text" class="form-control" id="requestUrl" placeholder="/auth/login">
                                    <button class="btn btn-primary" id="sendRequest">
                                        <i class="fas fa-paper-plane me-1"></i>Enviar
                                    </button>
                                </div>
                                
                                <div class="mb-2">
                                    <label class="form-label small">Headers</label>
                                    <textarea class="form-control" id="requestHeaders" rows="2" placeholder='{"Content-Type": "application/json"}'></textarea>
                                </div>
                                
                                <div class="mb-2">
                                    <label class="form-label small">Body (JSON)</label>
                                    <textarea class="form-control" id="requestBody" rows="4" placeholder='{"email": "admin@ejemplo.com", "password": "password"}'></textarea>
                                </div>
                            </div>

                            <!-- Response Panel -->
                            <div class="response-panel">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label small mb-0">Respuesta</label>
                                    <div>
                                        <span id="responseStatus" class="badge bg-secondary">Esperando...</span>
                                        <span id="responseTime" class="small text-muted ms-2"></span>
                                    </div>
                                </div>
                                <div id="responseContent" class="response-content">
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Selecciona un endpoint o envía una petición para ver la respuesta
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.account-card {
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 1rem 1.5rem;
}

.config-panel, .status-panel, .endpoints-panel, .testing-panel {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    height: fit-content;
}

.endpoint-item {
    padding: 0.5rem;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    margin-bottom: 0.5rem;
    background: white;
    cursor: pointer;
}

.endpoint-item:hover {
    background: #f0f0f0;
    border-color: #007bff;
}

.request-panel, .response-panel {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.response-content {
    background: #2d3748;
    color: #e2e8f0;
    padding: 1rem;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    max-height: 400px;
    overflow-y: auto;
}

.accordion-button {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
}

.badge {
    font-size: 0.7rem;
}

code {
    background: #e9ecef;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.8rem;
}
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiUrl = document.getElementById('apiUrl').value;
    let authToken = '';

    // Toggle token visibility
    document.getElementById('toggleToken').addEventListener('click', function() {
        const tokenInput = document.getElementById('authToken');
        const icon = this.querySelector('i');
        
        if (tokenInput.type === 'password') {
            tokenInput.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            tokenInput.type = 'password';
            icon.className = 'fas fa-eye';
        }
    });

    // Authentication
    document.getElementById('authBtn').addEventListener('click', function() {
        // Pre-fill login request
        document.getElementById('requestMethod').value = 'POST';
        document.getElementById('requestUrl').value = '/auth/login';
        document.getElementById('requestHeaders').value = JSON.stringify({
            "Content-Type": "application/json",
            "Accept": "application/json"
        }, null, 2);
        document.getElementById('requestBody').value = JSON.stringify({
            "email": "admin@example.com",
            "password": "password",
            "device_name": "API Tester"
        }, null, 2);
    });

    // Test endpoint buttons
    document.querySelectorAll('.test-endpoint').forEach(button => {
        button.addEventListener('click', function() {
            const item = this.closest('.endpoint-item');
            const endpoint = item.dataset.endpoint;
            const [method, url] = endpoint.split(' ');
            
            document.getElementById('requestMethod').value = method;
            document.getElementById('requestUrl').value = url;
            
            // Set headers
            const headers = {
                "Content-Type": "application/json",
                "Accept": "application/json"
            };
            
            if (authToken) {
                headers["Authorization"] = `Bearer ${authToken}`;
            }
            
            document.getElementById('requestHeaders').value = JSON.stringify(headers, null, 2);
            
            // Clear body for GET requests
            if (method === 'GET') {
                document.getElementById('requestBody').value = '';
            }
        });
    });

    // Send request
    document.getElementById('sendRequest').addEventListener('click', async function() {
        const method = document.getElementById('requestMethod').value;
        const url = document.getElementById('requestUrl').value;
        const headersText = document.getElementById('requestHeaders').value;
        const bodyText = document.getElementById('requestBody').value;
        
        try {
            const headers = headersText ? JSON.parse(headersText) : {};
            const requestOptions = {
                method: method,
                headers: headers
            };
            
            if (method !== 'GET' && bodyText) {
                requestOptions.body = bodyText;
            }
            
            updateStatus('Enviando petición...', 'warning');
            const startTime = Date.now();
            
            const response = await fetch(apiUrl + url, requestOptions);
            const responseTime = Date.now() - startTime;
            
            const responseText = await response.text();
            let responseData;
            
            try {
                responseData = JSON.parse(responseText);
            } catch (e) {
                responseData = responseText;
            }
            
            // Update status
            const statusClass = response.ok ? 'success' : 'danger';
            updateStatus(`${response.status} ${response.statusText}`, statusClass);
            document.getElementById('responseTime').textContent = `${responseTime}ms`;
            
            // Update response content
            document.getElementById('responseContent').innerHTML = 
                `<pre>${JSON.stringify(responseData, null, 2)}</pre>`;
            
            // Save token if login successful
            if (url.includes('/auth/login') && response.ok && responseData.data && responseData.data.token) {
                authToken = responseData.data.token;
                document.getElementById('authToken').value = authToken;
                updateConnectionStatus('Autenticado', 'success');
            }
            
        } catch (error) {
            updateStatus('Error de conexión', 'danger');
            document.getElementById('responseContent').innerHTML = 
                `<div class="text-danger">Error: ${error.message}</div>`;
        }
    });

    function updateStatus(text, type) {
        const statusElement = document.getElementById('responseStatus');
        statusElement.textContent = text;
        statusElement.className = `badge bg-${type}`;
    }

    function updateConnectionStatus(text, type) {
        const statusElement = document.getElementById('connectionStatus');
        statusElement.innerHTML = `<i class="fas fa-circle text-${type} me-2"></i>${text}`;
        statusElement.className = `alert alert-${type}`;
    }
});
</script>
@stop