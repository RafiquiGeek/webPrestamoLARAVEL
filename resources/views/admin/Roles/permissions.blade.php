@extends('layouts.admin')

@section('title', 'Gestionar Permisos - ' . $role->name)

@section('content')
<div class="container-fluid">
    <div class="py-4">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <!-- Header del Panel de Permisos -->
        <div class="account-card">
            <div class="row card-header d-flex">
                <div class="col-md-8">
                    <h3 style="margin-bottom:10px!important;">
                        <i class="fas fa-shield-alt me-2"></i>Gestión de Permisos
                        <span class="info-value">
                            <span class="badge-status bg-primary">{{ $role->name }}</span>
                        </span>
                    </h3>
                    <span class="info-label">Configuración de accesos y permisos del sistema</span>
                    <br>
                    <span class="info-label">Total de permisos disponibles: </span>
                    <span class="info-value" style="font-weight: bold;" id="total-permissions">{{ array_sum(array_map(fn($module) => count($module['permissions']), $menuStructure)) }}</span>
                </div>
                <div class="d-flex flex-wrap gap-2 col-md-4">
                    <button type="button" class="btn btn-outline-success btn-sm" id="selectAll">
                        <i class="fas fa-check-square me-1"></i> Todo
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm" id="unselectAll">
                        <i class="fas fa-square me-1"></i> Nada
                    </button>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>
        
            <form action="{{ route('admin.roles.updatePermissions', $role) }}" method="POST" id="permissions-form">
                @csrf
                @method('PUT')
                
                <div class="card-body">
                    <div class="row">
                        <!-- Panel de Acordeones -->
                        <div class="col-lg-8 col-md-8 vertical-divider">
                            <div class="prestamo-info">
                                <h5 class="section-title"><i class="fas fa-cogs"></i> Módulos del Sistema</h5>
                                
                                <div class="accordion" id="permissionsAccordion">
                                    @foreach($menuStructure as $moduleName => $module)
                                        @php
                                            $moduleId = str_replace(' ', '', $moduleName);
                                            $modulePermissions = array_keys($module['permissions']);
                                            $existingPermissions = array_filter($modulePermissions, fn($p) => \Spatie\Permission\Models\Permission::where('name', $p)->exists());
                                            $selectedPermissions = array_intersect($existingPermissions, $rolePermissions);
                                        @endphp
                                        <div class="accordion-item mb-3" style="border-radius: 8px; overflow: hidden; border: 1px solid #e9ecef;">
                                            <h2 class="accordion-header" id="heading{{ $moduleId }}">
                                                <button class="accordion-button collapsed" type="button" 
                                                        data-bs-toggle="collapse" 
                                                        data-bs-target="#collapse{{ $moduleId }}" 
                                                        aria-expanded="false" 
                                                        aria-controls="collapse{{ $moduleId }}"
                                                        style="background: #f8f9fa; border: none; padding: 1rem 1.5rem;">
                                                    <i class="{{ $module['icon'] }} me-3" style="color: #005566;"></i>
                                                    <strong>{{ $moduleName }}</strong>
                                                    <span class="badge bg-secondary ms-auto me-3 module-counter" 
                                                          data-module="{{ $moduleId }}">{{ count($selectedPermissions) }}/{{ count($existingPermissions) }}</span>
                                                </button>
                                            </h2>
                                            <div id="collapse{{ $moduleId }}" 
                                                 class="accordion-collapse collapse" 
                                                 aria-labelledby="heading{{ $moduleId }}" 
                                                 data-bs-parent="#permissionsAccordion">
                                                <div class="accordion-body" style="padding: 1.5rem;">
                                                    <div class="row g-2">
                                                        @foreach($module['permissions'] as $permission => $description)
                                                            @php
                                                                $permissionExists = \Spatie\Permission\Models\Permission::where('name', $permission)->exists();
                                                            @endphp
                                                            @if($permissionExists)
                                                                <div class="col-md-6">
                                                                    <div class="info-card permission-card" data-module="{{ $moduleId }}">
                                                                        <div class="form-check">
                                                                            <input class="form-check-input permission-checkbox" 
                                                                                   type="checkbox" 
                                                                                   name="permissions[]" 
                                                                                   value="{{ $permission }}"
                                                                                   id="permission_{{ str_replace('.', '_', $permission) }}"
                                                                                   data-module="{{ $moduleId }}"
                                                                                   data-description="{{ $description }}"
                                                                                   {{ in_array($permission, $rolePermissions) ? 'checked' : '' }}>
                                                                            <label class="form-check-label" for="permission_{{ str_replace('.', '_', $permission) }}">
                                                                                <div class="info-label">{{ $description }}</div>
                                                                                <div class="info-value small">{{ $permission }}</div>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        
                        <!-- Panel de Preview -->
                        <div class="col-lg-4 col-md-4">
                            <div class="personal-info sticky-top" style="top: 20px;">
                                <h5 class="section-title"><i class="fas fa-eye"></i> Previsualización</h5>
                                
                                <div class="info-item mb-3">
                                    <span class="info-label">Permisos Seleccionados:</span>
                                    <span class="info-value">
                                        <span class="badge-assigned" id="selected-count">{{ count($rolePermissions) }}</span>
                                        <span class="text-muted">de</span>
                                        <span class="badge-assigned" id="total-count">{{ array_sum(array_map(fn($module) => count(array_filter(array_keys($module['permissions']), fn($p) => \Spatie\Permission\Models\Permission::where('name', $p)->exists())), $menuStructure)) }}</span>
                                    </span>
                                </div>

                                <div class="info-item mb-3">
                                    <span class="info-label">Progreso:</span>
                                    <div class="progress mt-2" style="height: 8px;">
                                        <div class="progress-bar bg-success" 
                                             role="progressbar" 
                                             id="progress-bar"
                                             style="width: {{ count($rolePermissions) > 0 ? (count($rolePermissions) / array_sum(array_map(fn($module) => count(array_filter(array_keys($module['permissions']), fn($p) => \Spatie\Permission\Models\Permission::where('name', $p)->exists())), $menuStructure))) * 100 : 0 }}%"></div>
                                    </div>
                                </div>

                                <!-- Lista de permisos seleccionados -->
                                <div class="selected-permissions-list" style="max-height: 400px; overflow-y: auto;">
                                    <h6 class="text-muted mb-2">Accesos Habilitados:</h6>
                                    <div id="permissions-preview">
                                        @foreach($rolePermissions as $permission)
                                            @php
                                                $found = false;
                                                foreach($menuStructure as $moduleName => $module) {
                                                    if(isset($module['permissions'][$permission])) {
                                                        $found = ['module' => $moduleName, 'description' => $module['permissions'][$permission], 'icon' => $module['icon']];
                                                        break;
                                                    }
                                                }
                                            @endphp
                                            @if($found)
                                                <div class="preview-item" data-permission="{{ $permission }}">
                                                    <i class="{{ $found['icon'] }} me-2" style="color: #005566; font-size: 0.8rem;"></i>
                                                    <span class="preview-module">{{ $found['module'] }}:</span>
                                                    <span class="preview-desc">{{ $found['description'] }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Botón de guardado -->
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-save me-1"></i>Guardar Permisos
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('css')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
/* Adoptando el estilo del show.blade.php */
.account-card {
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin: 0 auto 1.5rem;
}

.account-card .card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.account-card .card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0;
}

.account-card .card-body {
    padding: 1.5rem;
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
}

.section-title i {
    margin-right: 0.5rem;
    color: #005566;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f3f5;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 500;
}

.info-value {
    font-size: 0.875rem;
    color: #1a1a1a;
    font-weight: 600;
}

.badge-status {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-assigned {
    background-color: #e3f2fd;
    color: #1565c0;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.info-card {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 8px;
    margin-bottom: 0.75rem;
    width: 100%;
    transition: all 0.2s ease;
    cursor: pointer;
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.05);
    background: #e8f4f8;
}

.info-card.selected {
    background: #d4edda;
    border: 1px solid #28a745;
}

.permission-card .form-check {
    margin-bottom: 0;
}

.permission-card .form-check-label {
    cursor: pointer;
    width: 100%;
}

.permission-card .info-label {
    font-size: 0.75rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    display: block;
}

.permission-card .info-value {
    font-size: 0.7rem;
    color: #6c757d;
    font-weight: 400;
}

.vertical-divider {
    border-right: 1px solid #e9ecef;
    padding-right: 1.5rem;
}

.accordion-button {
    background: #f8f9fa !important;
    border: none !important;
    color: #1a1a1a !important;
    font-weight: 600;
}

.accordion-button:not(.collapsed) {
    background: #005566 !important;
    color: white !important;
}

.accordion-button:focus {
    box-shadow: none !important;
}

.module-counter {
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
}

/* Preview Panel */
.preview-item {
    display: flex;
    align-items: center;
    padding: 0.4rem 0.8rem;
    margin-bottom: 0.3rem;
    background: #f8f9fa;
    border-radius: 6px;
    font-size: 0.8rem;
    border-left: 3px solid #005566;
}

.preview-module {
    font-weight: 600;
    color: #005566;
    margin-right: 0.5rem;
}

.preview-desc {
    color: #6c757d;
}

.progress {
    border-radius: 10px;
    background-color: #e9ecef;
}

.progress-bar {
    border-radius: 10px;
    transition: width 0.3s ease;
}

.sticky-top {
    position: sticky;
    top: 20px;
    z-index: 1020;
}

/* Responsive */
@media (max-width: 768px) {
    .vertical-divider {
        border-right: none;
        border-top: 1px solid #e9ecef;
        padding-top: 1.5rem;
        margin-top: 1.5rem;
        padding-right: 0;
    }
    
    .account-card .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .sticky-top {
        position: relative;
        top: auto;
    }
}

/* Estados de botones */
.btn-outline-success:hover {
    background-color: #28a745;
    border-color: #28a745;
}

.btn-outline-warning:hover {
    background-color: #ffc107;
    border-color: #ffc107;
}

.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}

/* Animaciones */
.permission-card {
    transition: all 0.2s ease-in-out;
}

.permission-card:hover {
    transform: translateY(-0.1rem);
}

.accordion-collapse {
    transition: all 0.3s ease;
}
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Estructura de módulos para el preview
    const moduleStructure = @json($menuStructure);
    
    // Inicializar contadores y preview
    updateAllStats();
    
    // Seleccionar todos los permisos
    document.getElementById('selectAll').addEventListener('click', function() {
        document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            updateCardState(checkbox);
        });
        updateAllStats();
        updatePreview();
    });
    
    // Deseleccionar todos los permisos
    document.getElementById('unselectAll').addEventListener('click', function() {
        document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
            checkbox.checked = false;
            updateCardState(checkbox);
        });
        updateAllStats();
        updatePreview();
    });
    
    // Evento change para cada checkbox
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateCardState(this);
            updateModuleCounter(this.dataset.module);
            updateGlobalStats();
            updatePreview();
        });
    });
    
    // Actualizar estado visual de la tarjeta
    function updateCardState(checkbox) {
        const card = checkbox.closest('.permission-card');
        if (checkbox.checked) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
    }
    
    // Actualizar contador de módulo específico
    function updateModuleCounter(moduleId) {
        const moduleCheckboxes = document.querySelectorAll(`[data-module="${moduleId}"]`);
        const selectedCount = document.querySelectorAll(`[data-module="${moduleId}"]:checked`).length;
        const totalCount = moduleCheckboxes.length;
        
        const counter = document.querySelector(`[data-module="${moduleId}"].module-counter`);
        if (counter) {
            counter.textContent = `${selectedCount}/${totalCount}`;
            
            // Cambiar color del badge según el progreso
            counter.className = 'badge ms-auto me-3 module-counter';
            if (selectedCount === 0) {
                counter.classList.add('bg-secondary');
            } else if (selectedCount === totalCount) {
                counter.classList.add('bg-success');
            } else {
                counter.classList.add('bg-warning');
            }
        }
    }
    
    // Actualizar estadísticas globales
    function updateGlobalStats() {
        const total = document.querySelectorAll('.permission-checkbox').length;
        const selected = document.querySelectorAll('.permission-checkbox:checked').length;
        
        document.getElementById('selected-count').textContent = selected;
        document.getElementById('total-count').textContent = total;
        
        // Actualizar barra de progreso
        const percentage = total > 0 ? (selected / total) * 100 : 0;
        const progressBar = document.getElementById('progress-bar');
        progressBar.style.width = percentage + '%';
        
        // Cambiar color de la barra según el progreso
        progressBar.className = 'progress-bar';
        if (percentage === 0) {
            progressBar.classList.add('bg-secondary');
        } else if (percentage === 100) {
            progressBar.classList.add('bg-success');
        } else if (percentage >= 50) {
            progressBar.classList.add('bg-info');
        } else {
            progressBar.classList.add('bg-warning');
        }
    }
    
    // Actualizar todos los contadores
    function updateAllStats() {
        // Actualizar contadores de módulos
        const modules = [...new Set(Array.from(document.querySelectorAll('.permission-checkbox')).map(cb => cb.dataset.module))];
        modules.forEach(moduleId => updateModuleCounter(moduleId));
        
        // Actualizar estadísticas globales
        updateGlobalStats();
        
        // Actualizar estado visual de las tarjetas
        document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
            updateCardState(checkbox);
        });
    }
    
    // Actualizar preview en tiempo real
    function updatePreview() {
        const previewContainer = document.getElementById('permissions-preview');
        const selectedCheckboxes = document.querySelectorAll('.permission-checkbox:checked');
        
        // Limpiar preview actual
        previewContainer.innerHTML = '';
        
        if (selectedCheckboxes.length === 0) {
            previewContainer.innerHTML = '<div class="text-muted text-center py-3"><i class="fas fa-info-circle me-2"></i>No hay permisos seleccionados</div>';
            return;
        }
        
        // Agrupar por módulo
        const groupedPermissions = {};
        
        selectedCheckboxes.forEach(checkbox => {
            const permission = checkbox.value;
            const description = checkbox.dataset.description;
            const moduleId = checkbox.dataset.module;
            
            // Encontrar el módulo y su información
            let moduleInfo = null;
            for (const [moduleName, module] of Object.entries(moduleStructure)) {
                if (moduleName.replace(/\s/g, '') === moduleId) {
                    moduleInfo = { name: moduleName, icon: module.icon };
                    break;
                }
            }
            
            if (moduleInfo) {
                if (!groupedPermissions[moduleInfo.name]) {
                    groupedPermissions[moduleInfo.name] = {
                        icon: moduleInfo.icon,
                        permissions: []
                    };
                }
                
                groupedPermissions[moduleInfo.name].permissions.push({
                    permission: permission,
                    description: description
                });
            }
        });
        
        // Crear elementos de preview
        for (const [moduleName, moduleData] of Object.entries(groupedPermissions)) {
            moduleData.permissions.forEach(perm => {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.dataset.permission = perm.permission;
                previewItem.innerHTML = `
                    <i class="${moduleData.icon} me-2" style="color: #005566; font-size: 0.8rem;"></i>
                    <span class="preview-module">${moduleName}:</span>
                    <span class="preview-desc">${perm.description}</span>
                `;
                previewContainer.appendChild(previewItem);
            });
        }
    }
    
    // Confirmación antes de enviar
    document.getElementById('permissions-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selectedCount = document.querySelectorAll('.permission-checkbox:checked').length;
        const roleName = @json($role->name);
        
        if (selectedCount === 0) {
            if (confirm(`¿Está seguro de que desea quitar todos los permisos del rol "${roleName}"?`)) {
                this.submit();
            }
        } else {
            if (confirm(`¿Desea guardar los ${selectedCount} permisos seleccionados para el rol "${roleName}"?`)) {
                this.submit();
            }
        }
    });
    
    // Efecto visual en las tarjetas
    document.querySelectorAll('.permission-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Hacer que las tarjetas sean clickeables
    document.querySelectorAll('.permission-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.type !== 'checkbox') {
                const checkbox = this.querySelector('.permission-checkbox');
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));
            }
        });
    });
});
</script>
@stop