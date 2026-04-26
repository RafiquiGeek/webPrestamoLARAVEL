<!-- Formulario embebido de cliente -->
<form action="{{ route('admin.clientes.store') }}" method="POST" enctype="multipart/form-data" id="clienteEmbeddedForm">
    @csrf
    <div class="card shadow-sm border-0" style="margin: 0; border-radius: 0;">
        <div class="card-body bg-light" style="padding: 15px; border-radius: 0; overflow-y: auto;">
            <!-- Foto del Cliente -->
            <div class="row justify-content-center mb-3">
                <div class="col-12 text-center">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center bg-light" id="img-embedded" style="height: 80px; width: 80px; border: 2px solid #dee2e6;">
                        <i class="fas fa-user fa-2x text-muted"></i>
                    </div>
                    <div class="form-group mt-2">
                        <label for="file-embedded" class="btn btn-sm btn-outline-primary"><i class="fa fa-upload mr-1"></i> Subir Foto</label>
                        <input id="file-embedded" type="file" name="file" style="display: none;" accept="image/*">
                    </div>
                </div>
            </div>

            <!-- Identidad -->
            <div class="row mb-3">
                <div class="col-12">
                    <h6 class="font-weight-bold text-muted">Identidad</h6>
                    <hr style="border-color: #2E5A9A; margin: 8px 0;">
                </div>
                <div class="col-md-3">
                    <div class="form-group position-relative">
                        <label for="nDocumento-embedded">Número de DNI *</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="nDocumento" id="nDocumento-embedded" placeholder="Ingrese el DNI" required maxlength="8">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" onclick="consultarDNIEmbedded()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="invalid-feedback">Ingrese un DNI válido de 8 dígitos</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="nombres-embedded">Nombres *</label>
                        <input type="text" class="form-control" name="nombres" id="nombres-embedded" required>
                        <div class="invalid-feedback">Este campo es requerido</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="aPaterno-embedded">Apellido Paterno *</label>
                        <input type="text" class="form-control" name="aPaterno" id="aPaterno-embedded" required>
                        <div class="invalid-feedback">Este campo es requerido</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="aMaterno-embedded">Apellido Materno *</label>
                        <input type="text" class="form-control" name="aMaterno" id="aMaterno-embedded" required>
                        <div class="invalid-feedback">Este campo es requerido</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="fecha_nacimiento-embedded">Fecha de Nacimiento *</label>
                        <input type="date" class="form-control" name="fecha_nacimiento" id="fecha_nacimiento-embedded" required>
                        <div class="invalid-feedback">Este campo es requerido</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="estado_civil-embedded">Estado Civil *</label>
                        <select class="form-control" id="estado_civil-embedded" name="estado_civil" required>
                            <option value="">Seleccione...</option>
                            <option value="Soltero">Soltero</option>
                            <option value="Casado">Casado</option>
                            <option value="Conviviente">Conviviente</option>
                            <option value="Divorciado">Divorciado</option>
                            <option value="Viudo">Viudo</option>
                        </select>
                        <div class="invalid-feedback">Este campo es requerido</div>
                    </div>
                </div>
            </div>

            <!-- Ubicación -->
            <div class="row mb-3">
                <div class="col-12">
                    <h6 class="font-weight-bold text-muted">Ubicación</h6>
                    <hr style="border-color: #2E5A9A; margin: 8px 0;">
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="sucursal-embedded">Sucursal *</label>
                        @php
                            // Debug temporal
                            if (!isset($sucursales)) {
                                echo "<!-- Debug: Variable sucursales no está definida -->";
                            } else {
                                echo "<!-- Debug: Variable sucursales definida, count: " . $sucursales->count() . " -->";
                            }
                        @endphp
                        <select class="form-control" id="sucursal-embedded" name="sucursal" required>
                            <option value="">Seleccione...</option>
                            @if(isset($sucursales) && $sucursales->count() > 0)
                                @foreach($sucursales as $sucursal)
                                    <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                                @endforeach
                            @else
                                <option value="">No hay sucursales disponibles</option>
                            @endif
                        </select>
                        <div class="invalid-feedback">Este campo es requerido</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="departamento-embedded">Departamento *</label>
                        <select class="form-control" id="departamento-embedded" name="departamento" required>
                            <option value="">Seleccione...</option>
                            @if(isset($departamentos) && $departamentos->count() > 0)
                                @foreach($departamentos as $departamento)
                                    <option value="{{ $departamento->id }}">{{ $departamento->departamento }}</option>
                                @endforeach
                            @else
                                <option value="">No hay departamentos disponibles</option>
                            @endif
                        </select>
                        <div class="invalid-feedback">Este campo es requerido</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="provincia-embedded">Provincia *</label>
                        <select class="form-control" id="provincia-embedded" name="provincia" required>
                            <option value="">Seleccione departamento primero...</option>
                        </select>
                        <div class="invalid-feedback">Este campo es requerido</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="distrito-embedded">Distrito *</label>
                        <select class="form-control" id="distrito-embedded" name="distrito" required>
                            <option value="">Seleccione provincia primero...</option>
                        </select>
                        <div class="invalid-feedback">Este campo es requerido</div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="direcDomicilio-embedded">Dirección *</label>
                        <textarea class="form-control" name="direcDomicilio" id="direcDomicilio-embedded" rows="2" placeholder="Ingrese la dirección completa" required></textarea>
                        <div class="invalid-feedback">Este campo es requerido</div>
                    </div>
                </div>
            </div>

            <!-- Información Financiera -->
            <div class="row mb-3">
                <div class="col-12">
                    <h6 class="font-weight-bold text-muted">Información Financiera</h6>
                    <hr style="border-color: #2E5A9A; margin: 8px 0;">
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="tCuenta-embedded">Tipo de Cuenta *</label>
                        <select class="form-control" id="tCuenta-embedded" name="tCuenta" required>
                            <option value="">Seleccione...</option>
                            @if(isset($tiposCuenta) && $tiposCuenta->count() > 0)
                                @foreach($tiposCuenta as $tipo)
                                    <option value="{{ $tipo->id }}">{{ $tipo->tipo_cuenta }}</option>
                                @endforeach
                            @else
                                <option value="">No hay tipos de cuenta disponibles</option>
                            @endif
                        </select>
                        <div class="invalid-feedback">Este campo es requerido</div>
                    </div>
                </div>
                <div class="col-md-6" id="entidad-container-embedded" style="display: none;">
                    <div class="form-group">
                        <label for="entidadFinanciera-embedded">Entidad Financiera</label>
                        <select class="form-control" id="entidadFinanciera-embedded" name="entidadFinanciera">
                            <option value="">Seleccione...</option>
                            @if(isset($entBancarias) && $entBancarias->count() > 0)
                                @foreach($entBancarias as $entidad)
                                    <option value="{{ $entidad->id }}">{{ $entidad->nombre }}</option>
                                @endforeach
                            @else
                                <option value="">No hay entidades bancarias disponibles</option>
                            @endif
                        </select>
                    </div>
                </div>
                <div class="col-md-6" id="numero-cuenta-container-embedded" style="display: none;">
                    <div class="form-group">
                        <label for="f_nCuenta-embedded">Número de Cuenta</label>
                        <input type="text" class="form-control" name="f_nCuenta" id="f_nCuenta-embedded" placeholder="Número de cuenta">
                    </div>
                </div>
                <div class="col-md-6" id="titular-container-embedded" style="display: none;">
                    <div class="form-group">
                        <label for="titular-embedded">Titular de la Cuenta</label>
                        <input type="text" class="form-control" name="titular" id="titular-embedded" placeholder="Nombre del titular">
                    </div>
                </div>
            </div>

            <!-- Teléfonos -->
            <div class="row mb-3">
                <div class="col-12">
                    <h6 class="font-weight-bold text-muted">Teléfonos</h6>
                    <hr style="border-color: #2E5A9A; margin: 8px 0;">
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="telefono-0-embedded">Teléfono</label>
                        <input type="text" class="form-control" name="telefono[]" id="telefono-0-embedded" placeholder="Número de teléfono" maxlength="9">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="tipo-0-embedded">Tipo</label>
                        <select class="form-control" name="tipo[]" id="tipo-0-embedded">
                            <option value="celular">Celular</option>
                            <option value="casa">Casa</option>
                            <option value="trabajo">Trabajo</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="comentario-0-embedded">Comentario</label>
                        <input type="text" class="form-control" name="comentario[]" id="comentario-0-embedded" placeholder="Opcional">
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="row mt-4">
                <div class="col-12 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" onclick="cerrarSidebarEmbedded()">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-1"></i> Guardar Cliente
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
/* Estilos específicos para el formulario embebido */
#clienteEmbeddedForm .form-group {
    margin-bottom: 1rem;
}

#clienteEmbeddedForm .form-control {
    font-size: 0.9rem;
    padding: 0.375rem 0.75rem;
    background-color: #fff !important;
    color: #495057 !important;
}

#clienteEmbeddedForm select option {
    color: #495057 !important;
    background-color: #fff !important;
    padding: 8px 12px;
}

#clienteEmbeddedForm label {
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.3rem;
}

#clienteEmbeddedForm h6 {
    font-size: 1rem;
    margin-bottom: 0.5rem;
}

#clienteEmbeddedForm .btn {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

#clienteEmbeddedForm .card-body {
    scrollbar-width: thin;
    scrollbar-color: #c1c1c1 #f1f1f1;
}

#clienteEmbeddedForm .card-body::-webkit-scrollbar {
    width: 6px;
}

#clienteEmbeddedForm .card-body::-webkit-scrollbar-track {
    background: #f1f1f1;
}

#clienteEmbeddedForm .card-body::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

#clienteEmbeddedForm .card-body::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<script>
// Variables globales para el formulario embebido
let consultandoDNIEmbedded = false;

// Función para consultar DNI en formulario embebido
async function consultarDNIEmbedded() {
    const dniInput = document.getElementById('nDocumento-embedded');
    const dni = dniInput.value.trim();
    
    if (dni.length !== 8 || !/^\d+$/.test(dni)) {
        mostrarErrorEmbedded('DNI debe tener 8 dígitos numéricos');
        return;
    }
    
    if (consultandoDNIEmbedded) return;
    consultandoDNIEmbedded = true;
    
    const button = dniInput.nextElementSibling.querySelector('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    try {
        const response = await fetch(`/admin/consultar-dni`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ nDocumento: dni })
        });
        
        const data = await response.json();
        
        if (data.valid) {
            document.getElementById('nombres-embedded').value = data.data.nombres || '';
            document.getElementById('aPaterno-embedded').value = data.data.apellido_paterno || '';
            document.getElementById('aMaterno-embedded').value = data.data.apellido_materno || '';
            mostrarExitoEmbedded('Datos cargados correctamente');
        } else {
            if (data.error === 'already_registered') {
                mostrarErrorEmbedded('Este DNI ya está registrado en el sistema');
            } else {
                mostrarErrorEmbedded('No se encontraron datos para este DNI');
            }
        }
    } catch (error) {
        mostrarErrorEmbedded('Error al consultar el DNI');
    } finally {
        button.innerHTML = originalText;
        button.disabled = false;
        consultandoDNIEmbedded = false;
    }
}

// Funciones de utilidad para el formulario embebido
function mostrarErrorEmbedded(mensaje) {
    // Mostrar error usando el sistema de alertas del formulario principal
    if (typeof mostrarAlerta === 'function') {
        mostrarAlerta('danger', 'Error', mensaje);
    } else {
        alert('Error: ' + mensaje);
    }
}

function mostrarExitoEmbedded(mensaje) {
    if (typeof mostrarAlerta === 'function') {
        mostrarAlerta('success', 'Éxito', mensaje);
    }
}

function cerrarSidebarEmbedded() {
    // Enviar mensaje al padre para cerrar el sidebar
    window.parent.postMessage({ type: 'closeSidebar' }, window.location.origin);
}

// Manejar cambio de tipo de cuenta
document.getElementById('tCuenta-embedded').addEventListener('change', function() {
    const valor = this.value;
    const entidadContainer = document.getElementById('entidad-container-embedded');
    const numeroCuentaContainer = document.getElementById('numero-cuenta-container-embedded');
    const titularContainer = document.getElementById('titular-container-embedded');
    
    if (valor > 1) { // No es efectivo
        entidadContainer.style.display = 'block';
        numeroCuentaContainer.style.display = 'block';
        document.getElementById('entidadFinanciera-embedded').required = true;
        document.getElementById('f_nCuenta-embedded').required = true;
        
        if (valor == 3) { // Cuenta de terceros
            titularContainer.style.display = 'block';
            document.getElementById('titular-embedded').required = true;
        } else {
            titularContainer.style.display = 'none';
            document.getElementById('titular-embedded').required = false;
        }
    } else { // Efectivo
        entidadContainer.style.display = 'none';
        numeroCuentaContainer.style.display = 'none';
        titularContainer.style.display = 'none';
        document.getElementById('entidadFinanciera-embedded').required = false;
        document.getElementById('f_nCuenta-embedded').required = false;
        document.getElementById('titular-embedded').required = false;
    }
});

// Manejar cambio de departamento para cargar provincias
document.getElementById('departamento-embedded').addEventListener('change', function() {
    const departamentoId = this.value;
    const provinciaSelect = document.getElementById('provincia-embedded');
    const distritoSelect = document.getElementById('distrito-embedded');
    
    // Limpiar selects dependientes
    provinciaSelect.innerHTML = '<option value="">Cargando...</option>';
    distritoSelect.innerHTML = '<option value="">Seleccione provincia primero...</option>';
    
    if (departamentoId) {
        fetch(`/api/departamento/${departamentoId}/provincias`)
            .then(response => response.json())
            .then(provincias => {
                provinciaSelect.innerHTML = '<option value="">Seleccione...</option>';
                provincias.forEach(provincia => {
                    provinciaSelect.innerHTML += `<option value="${provincia.id}">${provincia.nombre ?? 'Sin nombre'}</option>`;
                });
            })
            .catch(error => {
                provinciaSelect.innerHTML = '<option value="">Error al cargar</option>';
            });
    } else {
        provinciaSelect.innerHTML = '<option value="">Seleccione departamento primero...</option>';
    }
});

// Manejar cambio de provincia para cargar distritos
document.getElementById('provincia-embedded').addEventListener('change', function() {
    const provinciaId = this.value;
    const distritoSelect = document.getElementById('distrito-embedded');
    
    distritoSelect.innerHTML = '<option value="">Cargando...</option>';
    
    if (provinciaId) {
        fetch(`/api/provincia/${provinciaId}/distritos`)
            .then(response => response.json())
            .then(distritos => {
                distritoSelect.innerHTML = '<option value="">Seleccione...</option>';
                distritos.forEach(distrito => {
                    distritoSelect.innerHTML += `<option value="${distrito.id}">${distrito.nombre ?? 'Sin nombre'}</option>`;
                });
            })
            .catch(error => {
                distritoSelect.innerHTML = '<option value="">Error al cargar</option>';
            });
    } else {
        distritoSelect.innerHTML = '<option value="">Seleccione provincia primero...</option>';
    }
});

// Calcular edad automáticamente
document.getElementById('fecha_nacimiento-embedded').addEventListener('change', function() {
    const fechaNacimiento = new Date(this.value);
    const hoy = new Date();
    let edad = hoy.getFullYear() - fechaNacimiento.getFullYear();
    const diferenciaMeses = hoy.getMonth() - fechaNacimiento.getMonth();
    
    if (diferenciaMeses < 0 || (diferenciaMeses === 0 && hoy.getDate() < fechaNacimiento.getDate())) {
        edad--;
    }
    
    document.getElementById('edad-embedded').value = edad;
});

// Validación del formulario
document.getElementById('clienteEmbeddedForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validar campos requeridos
    const requiredFields = this.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        mostrarErrorEmbedded('Por favor complete todos los campos obligatorios');
        return;
    }
    
    // Enviar formulario
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...';
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            mostrarExitoEmbedded('Cliente registrado correctamente');
            // Enviar mensaje al padre con los datos del cliente
            setTimeout(() => {
                window.parent.postMessage({
                    type: 'clienteRegistrado',
                    cliente: data.cliente
                }, window.location.origin);
            }, 1000);
        } else {
            mostrarErrorEmbedded(data.message || 'Error al registrar el cliente');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarErrorEmbedded('Error al procesar la solicitud');
    })
    .finally(() => {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-save mr-1"></i> Guardar Cliente';
    });
});

// Manejar subida de foto
document.getElementById('file-embedded').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const imgContainer = document.getElementById('img-embedded');
            imgContainer.innerHTML = `<img src="${e.target.result}" class="rounded-circle" alt="userPhoto" style="height: 80px; width: 80px; object-fit: cover;">`;
        };
        reader.readAsDataURL(file);
    }
});
</script>