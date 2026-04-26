@php
    $cliente = $prestamo->cliente;
    
    // Lógica de asignación
    $ultimoPrestamoConJcc = $cliente->prestamos->sortByDesc('created_at')->first(function($p) { return $p->carterasJcc->count() > 0; });
    $jccActual = $ultimoPrestamoConJcc ? $ultimoPrestamoConJcc->carterasJcc->first() : null;

    $ultimoPrestamoConAsesor = $cliente->prestamos->sortByDesc('created_at')->first(function($p) { return $p->carterasAsesor->count() > 0; });
    $asesorActual = $ultimoPrestamoConAsesor ? $ultimoPrestamoConAsesor->carterasAsesor->first() : null;

    $ultimoPrestamoConAnalista = $cliente->prestamos->sortByDesc('created_at')->first(function($p) { return $p->carterasAnalista->count() > 0; });
    $analistaActual = $ultimoPrestamoConAnalista ? $ultimoPrestamoConAnalista->carterasAnalista->first() : null;
    
    // Obtener teléfonos del cliente
    $telefonos = $cliente->persona->telefonos ?? collect();
    $telefonoPrincipal = $telefonos->first();
    $telefonoSecundario = $telefonos->skip(1)->first();
    
    // Obtener dirección de cobro del préstamo
    $direccionCobro = $prestamo->direccionCobro;
    
    // Obtener datos laborales
    $laboralPrincipal = $cliente->laborales->first();
    
    // Obtener cuenta del cliente para este préstamo
    $cuentaPrestamo = $prestamo->cuentaCliente;
    
    // Obtener zona/sucursal
    $sucursalActual = $direccionCobro ? $direccionCobro->sucursal : null;
    $zonaActual = $direccionCobro ? $direccionCobro->zona : null;
@endphp

<style>
    /* Estilos mejorados para sidebar cliente */
    .sidebar-cliente-modal .modal-dialog {
        max-width: 480px;
        margin-right: 0;
        margin-left: auto;
        height: 100%;
        margin-top: 0;
        transition: transform 0.3s ease-out;
    }
    
    .sidebar-cliente-modal .modal-content {
        border: none;
        border-radius: 0;
        height: 100%;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    }
    
    .sidebar-cliente-modal .modal-header {
        background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
        border: none;
        padding: 1.25rem 1.5rem;
        padding-top: 3.5rem; /* Espacio adicional arriba */
        position: relative;
        overflow: hidden;
    }
    
    .sidebar-cliente-modal .modal-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    }
    
    .sidebar-cliente-modal .modal-body {
        padding: 0;
        overflow-y: auto;
    }
    
    /* Profile Card */
    .client-profile-card {
        background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
        padding: 1rem;
        color: white;
        position: relative;
    }
    
    .client-profile-card .profile-avatar {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        border: 4px solid rgba(255,255,255,0.3);
        object-fit: cover;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        transition: transform 0.3s ease;
    }
    
    .client-profile-card .profile-avatar:hover {
        transform: scale(1.05);
    }
    
    .client-profile-card .client-name {
        font-size: 1.35rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .client-profile-card .client-document {
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.3);
        padding: 0.35rem 1rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    /* Quick Actions */
    .quick-actions {
        display: flex;
        gap: 0.5rem;
        padding: 1rem 1.5rem;
        background: white;
        border-bottom: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    
    .quick-action-btn {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.35rem;
        padding: 0.75rem 0.5rem;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    
    .quick-action-btn:hover {
        background: #2563eb;
        color: white;
        border-color: #2563eb;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37,99,235,0.3);
    }
    
    .quick-action-btn i {
        font-size: 1.1rem;
    }
    
    /* Section Cards */
    .sidebar-section {
        padding: 1rem 1.5rem;
    }
    
    .section-title {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #64748b;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .section-title i {
        font-size: 0.85rem;
        color: #2563eb;
    }
    
    .info-card {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        border: 1px solid #e2e8f0;
    }
    
    /* Staff Grid */
    .staff-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .staff-item {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 10px;
        padding: 0.875rem;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    
    .staff-item:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transform: translateY(-1px);
    }
    
    .staff-item .staff-role {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
        margin-bottom: 0.25rem;
    }
    
    .staff-item .staff-name {
        font-size: 0.85rem;
        font-weight: 600;
        color: #1e293b;
    }
    
    .staff-item.unassigned .staff-name {
        color: #94a3b8;
        font-style: italic;
    }
    
    /* Info Grid */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }
    
    .info-item {
        text-align: center;
        padding: 0.75rem;
        background: #f8fafc;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
    }
    
    .info-item .info-label {
        font-size: 0.6rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
        margin-bottom: 0.25rem;
    }
    
    .info-item .info-value {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e293b;
    }
    
    /* Contact Cards */
    .contact-row {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .contact-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .contact-row:first-child {
        padding-top: 0;
    }
    
    .contact-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    
    .contact-icon.phone {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .contact-icon.location {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
    }
    
    .contact-icon.work {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
    }
    
    .contact-content {
        flex: 1;
        min-width: 0;
    }
    
    .contact-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
    }
    
    .contact-value {
        font-size: 0.9rem;
        font-weight: 600;
        color: #1e293b;
        word-break: break-word;
    }
    
    .contact-sub {
        font-size: 0.75rem;
        color: #64748b;
    }
    
    /* Bank Accounts */
    .bank-account-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        background: #f8fafc;
        border-radius: 10px;
        margin-bottom: 0.5rem;
        border: 1px solid #e2e8f0;
    }
    
    .bank-account-item:last-child {
        margin-bottom: 0;
    }
    
    .bank-logo {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: linear-gradient(135deg, #1e3a5f 0%, #3b82f6 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    
    .bank-logo.wallet {
        background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
    }
    
    .bank-logo.cash {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    
    .bank-details {
        flex: 1;
        min-width: 0;
    }
    
    .bank-name {
        font-size: 0.85rem;
        font-weight: 600;
        color: #1e293b;
    }
    
    .bank-number {
        font-size: 0.8rem;
        font-family: 'Courier New', monospace;
        color: #64748b;
        letter-spacing: 0.5px;
    }
    
    .bank-type {
        font-size: 0.65rem;
        padding: 0.25rem 0.6rem;
        border-radius: 50px;
        font-weight: 600;
    }
    
    .bank-type.own {
        background: #dcfce7;
        color: #166534;
    }
    
    .bank-type.third {
        background: #fef3c7;
        color: #92400e;
    }

    /* Documents */
    .document-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .document-item:hover {
        background: #f8fafc;
    }

    .document-item:last-child {
        border-bottom: none;
    }

    .document-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .document-icon.pdf {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        color: white;
    }

    .document-icon.image {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .document-icon.file {
        background: linear-gradient(135deg, #64748b 0%, #475569 100%);
        color: white;
    }

    .document-details {
        flex: 1;
        min-width: 0;
    }

    .document-name {
        font-size: 0.85rem;
        font-weight: 600;
        color: #1e293b;
    }

    .document-filename {
        font-size: 0.75rem;
        color: #94a3b8;
        font-family: 'Courier New', monospace;
    }

    /* Document Preview Modal */
    .document-preview-modal .modal-dialog {
        max-width: 90vw;
        max-height: 90vh;
        margin: 2rem auto;
    }

    .document-preview-modal .modal-content {
        height: 85vh;
    }

    .document-preview-modal .modal-body {
        padding: 0;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #1e293b;
    }

    .document-preview-modal .modal-body img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .document-preview-modal .modal-body iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    /* Loan History */
    .loan-history-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border-radius: 10px;
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
    }
    
    .loan-history-item:hover {
        background: #f1f5f9;
    }
    
    .loan-number {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.75rem;
        color: #4f46e5;
        flex-shrink: 0;
    }
    
    .loan-details {
        flex: 1;
        min-width: 0;
    }
    
    .loan-amount {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e293b;
    }
    
    .loan-date {
        font-size: 0.75rem;
        color: #94a3b8;
    }
    
    .loan-status {
        font-size: 0.65rem;
        padding: 0.3rem 0.7rem;
        border-radius: 50px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .loan-status.vigente { background: #dbeafe; color: #1d4ed8; }
    .loan-status.liquidado, .loan-status.pagado { background: #dcfce7; color: #166534; }
    .loan-status.moroso, .loan-status.mora { background: #fee2e2; color: #dc2626; }
    .loan-status.pendiente { background: #fef3c7; color: #92400e; }
    .loan-status.default { background: #f1f5f9; color: #64748b; }
    
    /* Aval Card */
    .aval-card {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border: 1px solid #bfdbfe;
        border-left: 4px solid #2563eb;
        border-radius: 12px;
        padding: 1rem;
    }
    
    .aval-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    
    .aval-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.25rem;
    }
    
    .aval-name {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
    }
    
    .aval-relation {
        font-size: 0.75rem;
        color: #64748b;
    }
    
    .aval-info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }
    
    .aval-info-item {
        background: white;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
    }
    
    /* Empty States */
    .empty-state {
        text-align: center;
        padding: 2rem 1rem;
        color: #94a3b8;
    }
    
    .empty-state i {
        font-size: 2.5rem;
        margin-bottom: 0.75rem;
        opacity: 0.5;
    }
    
    .empty-state-text {
        font-size: 0.85rem;
    }
    
    /* Animation for modal */
    .sidebar-cliente-modal.fade .modal-dialog {
        transform: translateX(100%);
    }
    
    .sidebar-cliente-modal.show .modal-dialog {
        transform: translateX(0);
    }
</style>

<div class="modal fade right sidebar-cliente-modal" id="sidebarCliente" tabindex="-1" aria-labelledby="sidebarClienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            {{-- Header con perfil del cliente --}}
            <div class="client-profile-card">
                <button type="button" class="btn-close btn-close-white position-absolute" style="top: 3rem; right: 1rem; opacity: 0.8;" data-bs-dismiss="modal" aria-label="Close"></button>
                
                <div class="d-flex align-items-center gap-3">
                    @if ($cliente->persona->imagen)
                        <img src="{{ asset('img/clientes_img/' . $cliente->persona->imagen) }}" class="profile-avatar" alt="Foto del cliente">
                    @else
                        <img src="{{ asset('/img/no-data.png') }}" class="profile-avatar" alt="Sin foto">
                    @endif
                    <div>
                        <h4 class="client-name">{{ $cliente->persona->nombres }} {{ $cliente->persona->ape_pat }}</h4>
                        <p class="mb-2 opacity-75" style="font-size: 0.9rem;">{{ $cliente->persona->ape_mat }}</p>
                        <span class="client-document">
                            <i class="fas fa-id-card"></i>
                            {{ $cliente->persona->documento }}
                        </span>
                    </div>
                </div>
            </div>
            
            {{-- Acciones rápidas - Solo Editar y WhatsApp --}}
            <div class="quick-actions">
                <a href="{{ route('admin.clientes.edit', $cliente->id) }}" class="quick-action-btn">
                    <i class="fas fa-user-edit"></i>
                    Editar
                </a>
                @if($telefonoPrincipal)
                <a href="https://wa.me/51{{ $telefonoPrincipal->numero }}" target="_blank" class="quick-action-btn">
                    <i class="fab fa-whatsapp"></i>
                    WhatsApp
                </a>
                @endif
            </div>
            
            <div class="modal-body">
                {{-- Personal Asignado - Solo códigos --}}
                <div class="sidebar-section">
                    <div class="section-title">
                        <i class="fas fa-users"></i>
                        Personal Asignado
                    </div>
                    <div class="staff-grid">
                        <div class="staff-item {{ !$analistaActual ? 'unassigned' : '' }}">
                            <div class="staff-role">Analista</div>
                            <div class="staff-name">{{ $analistaActual ? ($analistaActual->user->codigo ?? 'S/C') : 'Sin asignar' }}</div>
                        </div>
                        <div class="staff-item {{ !$jccActual ? 'unassigned' : '' }}">
                            <div class="staff-role">JCC</div>
                            <div class="staff-name">{{ $jccActual ? ($jccActual->user->codigo ?? 'S/C') : 'Sin asignar' }}</div>
                        </div>
                        <div class="staff-item {{ !$asesorActual ? 'unassigned' : '' }}">
                            <div class="staff-role">Asesor</div>
                            <div class="staff-name">{{ $asesorActual ? ($asesorActual->user->codigo ?? 'S/C') : 'Sin asignar' }}</div>
                        </div>
                        <div class="staff-item">
                            <div class="staff-role">Zona / Sucursal</div>
                            <div class="staff-name">{{ ($zonaActual->nombre ?? 'N/A') }} / {{ ($sucursalActual->sucursal ?? 'N/A') }}</div>
                        </div>
                    </div>
                </div>
                
                {{-- Datos Personales --}}
                <div class="sidebar-section">
                    <div class="section-title">
                        <i class="fas fa-user"></i>
                        Datos Personales
                    </div>
                    <div class="info-card">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Edad</div>
                                <div class="info-value">{{ $cliente->persona->edad ?? '-' }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Estado Civil</div>
                                <div class="info-value" style="font-size: 0.8rem;">{{ Str::limit($cliente->persona->estado_civil ?? '-', 10) }}</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Carga Fam.</div>
                                <div class="info-value">{{ $cliente->carga_familiar ?? '0' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Contacto --}}
                <div class="sidebar-section">
                    <div class="section-title">
                        <i class="fas fa-phone-alt"></i>
                        Contacto
                    </div>
                    <div class="info-card">
                        @if($telefonoPrincipal)
                        <div class="contact-row">
                            <div class="contact-icon phone">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="contact-content">
                                <div class="contact-label">Celular Principal</div>
                                <div class="contact-value">{{ $telefonoPrincipal->numero }}</div>
                            </div>
                            <a href="tel:{{ $telefonoPrincipal->numero }}" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                <i class="fas fa-phone"></i>
                            </a>
                        </div>
                        @endif
                        
                        @if($telefonoSecundario)
                        <div class="contact-row">
                            <div class="contact-icon phone" style="background: linear-gradient(135deg, #64748b 0%, #475569 100%);">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-content">
                                <div class="contact-label">Teléfono Secundario</div>
                                <div class="contact-value">{{ $telefonoSecundario->numero }}</div>
                            </div>
                        </div>
                        @endif
                        
                        {{-- Dirección de Cobro del Préstamo --}}
                        @if($direccionCobro)
                        <div class="contact-row">
                            <div class="contact-icon location">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-content">
                                <div class="contact-label">Dirección de Cobro</div>
                                <div class="contact-value">{{ $direccionCobro->direccion }} {{ $direccionCobro->numero ? '- ' . $direccionCobro->numero : '' }}</div>
                                @if($direccionCobro->distrito)
                                <div class="contact-sub">{{ $direccionCobro->distrito->distrito ?? '' }}, {{ $direccionCobro->distrito->provincia->provincia ?? '' }}</div>
                                @endif
                            </div>
                        </div>
                        @else
                        <div class="contact-row">
                            <div class="contact-icon location">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-content">
                                <div class="contact-label">Dirección de Cobro</div>
                                <div class="contact-value text-muted">No registrada para este préstamo</div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                
                {{-- Datos Laborales --}}
                <div class="sidebar-section">
                    <div class="section-title">
                        <i class="fas fa-briefcase"></i>
                        Datos Laborales
                    </div>
                    <div class="info-card">
                        @if($laboralPrincipal)
                        <div class="contact-row">
                            <div class="contact-icon work">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="contact-content">
                                <div class="contact-label">Lugar de Trabajo</div>
                                <div class="contact-value">{{ $laboralPrincipal->nombre_lugar_trabajo ?? 'No especificado' }}</div>
                                @if($laboralPrincipal->cargo)
                                <div class="contact-sub"><i class="fas fa-user-tie me-1"></i>{{ $laboralPrincipal->cargo }}</div>
                                @endif
                            </div>
                        </div>
                        @if($laboralPrincipal->actividad_economica)
                        <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                            <span class="text-muted" style="font-size: 0.75rem;">Actividad:</span>
                            <span class="fw-semibold" style="font-size: 0.85rem;">{{ $laboralPrincipal->actividad_economica }}</span>
                        </div>
                        @endif
                        @else
                        <div class="empty-state py-3">
                            <i class="fas fa-briefcase d-block" style="font-size: 1.5rem;"></i>
                            <span class="empty-state-text">Sin datos laborales</span>
                        </div>
                        @endif
                    </div>
                </div>
                
                {{-- Cuenta del Préstamo (Bancaria, Billetera o Efectivo) --}}
                <div class="sidebar-section">
                    <div class="section-title">
                        <i class="fas fa-university"></i>
                        Cuenta de Desembolso
                    </div>
                    @if($cuentaPrestamo)
                        @php
                            $tipoCuenta = $cuentaPrestamo->tipo_cuenta_id ?? 2;
                            $entidad = $cuentaPrestamo->entidad;
                            $esDigital = $entidad && in_array($entidad->banco ?? '', ['Yape', 'Plin', 'Dale', 'Tunki', 'Bim']);
                        @endphp
                        <div class="bank-account-item">
                            @if($esDigital)
                            <div class="bank-logo wallet">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="bank-details">
                                <div class="bank-name">{{ $entidad->banco ?? 'Billetera Digital' }}</div>
                                <div class="bank-number">{{ $cuentaPrestamo->numero_cuenta ?? '-' }}</div>
                            </div>
                            <span class="bank-type {{ $tipoCuenta == 2 ? 'own' : 'third' }}">
                                {{ $tipoCuenta == 2 ? 'Propia' : 'Terceros' }}
                            </span>
                            @else
                            <div class="bank-logo">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="bank-details">
                                <div class="bank-name">{{ $entidad->banco ?? 'Banco' }}</div>
                                <div class="bank-number">{{ $cuentaPrestamo->numero_cuenta ?? '-' }}</div>
                            </div>
                            <span class="bank-type {{ $tipoCuenta == 2 ? 'own' : 'third' }}">
                                {{ $tipoCuenta == 2 ? 'Propia' : 'Terceros' }}
                            </span>
                            @endif
                        </div>
                        @if($tipoCuenta == 3 && $cuentaPrestamo->titular)
                        <div class="mt-2 px-2">
                            <small class="text-muted">Titular: <strong>{{ $cuentaPrestamo->titular }}</strong></small>
                        </div>
                        @endif
                    @else
                    <div class="bank-account-item">
                        <div class="bank-logo cash">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="bank-details">
                            <div class="bank-name">Efectivo</div>
                            <div class="bank-number">Pago en efectivo</div>
                        </div>
                        <span class="bank-type own">Directo</span>
                    </div>
                    @endif
                </div>

                {{-- Documentos del Cliente --}}
                @php
                    $documentos = $cliente->documentosCliente ?? collect();
                @endphp

                @if($documentos->count() > 0)
                <div class="sidebar-section">
                    <div class="section-title">
                        <i class="fas fa-file-alt"></i>
                        Documentos
                        <span class="badge bg-secondary ms-auto" style="font-size: 0.7rem;">{{ $documentos->count() }}</span>
                    </div>
                    <div class="info-card p-0 overflow-hidden">
                        @foreach($documentos as $doc)
                        @php
                            $extension = pathinfo($doc->ruta_archivo, PATHINFO_EXTENSION);
                            $isPdf = strtolower($extension) === 'pdf';
                            $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            $fileName = basename($doc->ruta_archivo);
                        @endphp
                        <div class="document-item">
                            <div class="document-icon {{ $isPdf ? 'pdf' : ($isImage ? 'image' : 'file') }}">
                                <i class="fas fa-{{ $isPdf ? 'file-pdf' : ($isImage ? 'file-image' : 'file-alt') }}"></i>
                            </div>
                            <div class="document-details">
                                <div class="document-name">{{ $doc->tipo_documento ?? 'Documento' }}</div>
                                <div class="document-filename">{{ Str::limit($fileName, 30) }}</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                    onclick="previewClientDocument('{{ asset('files/client_files/' . $doc->ruta_archivo) }}', '{{ $doc->tipo_documento }}', {{ $isPdf ? 'true' : 'false' }})">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Historial de Préstamos --}}
                <div class="sidebar-section">
                    <div class="section-title">
                        <i class="fas fa-history"></i>
                        Historial de Préstamos
                        <span class="badge bg-primary ms-auto" style="font-size: 0.7rem;">{{ $cliente->prestamos->count() }}</span>
                    </div>
                    <div class="info-card p-0 overflow-hidden">
                        @if($cliente->prestamos->count() > 0)
                            @foreach($cliente->prestamos->sortByDesc('created_at')->take(5) as $p)
                            <a href="{{ route('admin.prestamos.show', $p->id) }}" class="loan-history-item">
                                <div class="loan-number">#{{ $p->id }}</div>
                                <div class="loan-details">
                                    <div class="loan-amount">S/ {{ number_format($p->cantidad_solicitada ?? $p->monto, 2) }}</div>
                                    <div class="loan-date">{{ $p->fecha_atencion ? $p->fecha_atencion->format('d/m/Y') : $p->created_at->format('d/m/Y') }}</div>
                                </div>
                                @php
                                    $statusClass = match(strtolower($p->estado ?? '')) {
                                        'vigente' => 'vigente',
                                        'liquidado', 'pagado' => 'liquidado',
                                        'moroso', 'vigente con moras' => 'moroso',
                                        'en análisis', 'pendiente' => 'pendiente',
                                        default => 'default'
                                    };
                                @endphp
                                <span class="loan-status {{ $statusClass }}">{{ $p->estado }}</span>
                            </a>
                            @endforeach
                            
                            @if($cliente->prestamos->count() > 5)
                            <div class="text-center p-2 bg-light border-top">
                                <small class="text-muted">Mostrando últimos 5 de {{ $cliente->prestamos->count() }}</small>
                            </div>
                            @endif
                        @else
                            <div class="empty-state">
                                <i class="fas fa-file-invoice-dollar"></i>
                                <div class="empty-state-text">Sin historial de préstamos</div>
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- Avales --}}
                @php
                    $avalesDelCliente = \App\Models\Aval::whereHas('prestamo', function($q) use ($cliente) {
                        $q->where('cliente_id', $cliente->id);
                    })->with('persona.telefonos')->get();
                @endphp
                
                @if($avalesDelCliente->count() > 0)
                <div class="sidebar-section">
                    <div class="section-title">
                        <i class="fas fa-user-shield"></i>
                        Avales Registrados
                        <span class="badge bg-info ms-auto" style="font-size: 0.7rem;">{{ $avalesDelCliente->count() }}</span>
                    </div>
                    @foreach($avalesDelCliente->take(2) as $aval)
                    <div class="aval-card mb-2">
                        <div class="aval-header">
                            <div class="aval-avatar">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div>
                                <div class="aval-name">{{ $aval->persona->nombres ?? '' }} {{ $aval->persona->ape_pat ?? '' }}</div>
                                <div class="aval-relation">{{ $aval->parentesco ?? 'Aval' }} · Préstamo #{{ $aval->prestamo_id }}</div>
                            </div>
                        </div>
                        <div class="aval-info-grid">
                            <div class="aval-info-item">
                                <div class="contact-label">DNI</div>
                                <div class="contact-value" style="font-size: 0.85rem;">{{ $aval->persona->documento ?? '-' }}</div>
                            </div>
                            <div class="aval-info-item">
                                <div class="contact-label">Teléfono</div>
                                <div class="contact-value" style="font-size: 0.85rem;">{{ $aval->persona->telefonos->first()->numero ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
                
                {{-- Espaciado final --}}
                <div style="height: 2rem;"></div>
            </div>
        </div>
    </div>
</div>

{{-- Modal de Previsualización de Documentos Cliente --}}
<div class="modal fade document-preview-modal" id="clientDocumentPreviewModal" tabindex="-1" aria-labelledby="clientDocumentPreviewLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="clientDocumentPreviewLabel">
                    <i class="fas fa-file-alt me-2"></i>
                    <span id="clientDocumentTitle">Documento</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="clientDocumentPreviewContent">
                <!-- Aquí se cargará el contenido del documento -->
            </div>
            <div class="modal-footer bg-light">
                <button type="button" id="clientDownloadDocumentBtn" class="btn btn-primary" onclick="window.open(this.dataset.url, '_blank')">
                    <i class="fas fa-download me-2"></i>Descargar
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function previewClientDocument(url, title, isPdf) {
    // Actualizar título
    document.getElementById('clientDocumentTitle').textContent = title || 'Documento';

    // Actualizar botón de descarga usando data-url
    const downloadBtn = document.getElementById('clientDownloadDocumentBtn');
    downloadBtn.dataset.url = url;

    // Limpiar contenido previo
    const contentContainer = document.getElementById('clientDocumentPreviewContent');
    contentContainer.innerHTML = '';

    if (isPdf) {
        // Mostrar PDF en iframe
        const iframe = document.createElement('iframe');
        iframe.src = url;
        iframe.style.width = '100%';
        iframe.style.height = '100%';
        iframe.style.border = 'none';
        contentContainer.appendChild(iframe);
    } else {
        // Mostrar imagen
        const img = document.createElement('img');
        img.src = url;
        img.alt = title;
        img.className = 'img-fluid';
        img.style.maxWidth = '100%';
        img.style.maxHeight = '100%';
        img.style.objectFit = 'contain';
        contentContainer.appendChild(img);
    }

    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('clientDocumentPreviewModal'));
    modal.show();
}
</script>
