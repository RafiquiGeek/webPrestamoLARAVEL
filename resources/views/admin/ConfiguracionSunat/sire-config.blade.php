@extends('layouts.admin')

@section('title', 'Configuración SIRE - Conexión Directa SUNAT')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-11 mx-auto">
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-shield-alt text-primary mr-2"></i>Configuración SIRE
                    </h2>
                    <p class="text-muted mb-0">Conexión directa con SUNAT vía Greenter</p>
                </div>
                <a href="{{ route('admin.configuracion-sunat.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
            </div>

            {{-- Alertas de confirmación solo --}}
            @if(session('status'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('status') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            {{-- Estado de configuración --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-left-{{ !empty($config->usar_sire) ? 'success' : 'secondary' }}">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="fas fa-toggle-on fa-2x text-{{ !empty($config->usar_sire) ? 'success' : 'secondary' }}"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">SIRE</div>
                                    <div class="h6 mb-0">{{ !empty($config->usar_sire) ? 'Activo' : 'Inactivo' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-{{ !empty($config->sire_cert_path) ? 'success' : 'warning' }}">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="fas fa-certificate fa-2x text-{{ !empty($config->sire_cert_path) ? 'success' : 'warning' }}"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Certificado</div>
                                    <div class="h6 mb-0">{{ !empty($config->sire_cert_path) ? 'Configurado' : 'Pendiente' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-{{ !empty($config->sol_user) ? 'success' : 'warning' }}">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="fas fa-key fa-2x text-{{ !empty($config->sol_user) ? 'success' : 'warning' }}"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Usuario SOL</div>
                                    <div class="h6 mb-0">{{ !empty($config->sol_user) ? 'Configurado' : 'Pendiente' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-left-{{ !empty($config->modo_produccion) ? 'danger' : 'info' }}">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <i class="fas fa-server fa-2x text-{{ !empty($config->modo_produccion) ? 'danger' : 'info' }}"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Ambiente</div>
                                    <div class="h6 mb-0">{{ !empty($config->modo_produccion) ? 'Producción' : 'Testing' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Formulario con Tabs --}}
            <div class="card shadow">
                <div class="card-header bg-white">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#tab-general" role="tab">
                                <i class="fas fa-cog mr-1"></i>General
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tab-certificado" role="tab">
                                <i class="fas fa-certificate mr-1"></i>Certificado Digital
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tab-credenciales" role="tab">
                                <i class="fas fa-key mr-1"></i>Credenciales SOL
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tab-oauth2" role="tab">
                                <i class="fas fa-shield-alt mr-1"></i>OAuth2 API SUNAT
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tab-series" role="tab">
                                <i class="fas fa-hashtag mr-1"></i>Series y Numeración
                            </a>
                        </li>
                    </ul>
                </div>

                <form action="{{ route('admin.sire-config.save') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="tab-content">
                            {{-- Tab 1: General --}}
                            <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                                <h5 class="mb-3">Configuración General</h5>

                                {{-- Activar SIRE --}}
                                <div class="form-group">
                                    <div class="custom-control custom-switch custom-switch-lg">
                                        <input type="hidden" name="usar_sire" value="0">
                                        <input type="checkbox" class="custom-control-input" id="usar_sire"
                                               name="usar_sire" value="1"
                                               {{ old('usar_sire', $config->usar_sire ?? true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="usar_sire">
                                            <strong class="h6">Activar Integración SIRE</strong>
                                            <p class="text-muted mb-0 small">Habilita la emisión de comprobantes electrónicos vía conexión directa con SUNAT</p>
                                        </label>
                                    </div>
                                </div>

                                <hr class="my-4">

                                {{-- Ambiente --}}
                                <h6 class="mb-3">Ambiente SUNAT</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-3 border {{ old('modo_produccion', $config->modo_produccion ?? 0) == 0 ? 'border-info' : '' }}" style="cursor: pointer;" onclick="document.getElementById('ambiente_testing').click()">
                                            <div class="card-body">
                                                <div class="custom-control custom-radio">
                                                    <input type="radio" id="ambiente_testing" name="modo_produccion"
                                                           value="0" class="custom-control-input"
                                                           {{ old('modo_produccion', $config->modo_produccion ?? 0) == 0 ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="ambiente_testing">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-flask fa-2x text-info mr-3"></i>
                                                            <div>
                                                                <strong>Testing (Beta)</strong>
                                                                <p class="mb-0 small text-muted">Para pruebas y desarrollo</p>
                                                                <code class="small">api-cpe-beta.sunat.gob.pe</code>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3 border {{ old('modo_produccion', $config->modo_produccion ?? 0) == 1 ? 'border-danger' : '' }}" style="cursor: pointer;" onclick="document.getElementById('ambiente_produccion').click()">
                                            <div class="card-body">
                                                <div class="custom-control custom-radio">
                                                    <input type="radio" id="ambiente_produccion" name="modo_produccion"
                                                           value="1" class="custom-control-input"
                                                           {{ old('modo_produccion', $config->modo_produccion ?? 0) == 1 ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="ambiente_produccion">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-check-circle fa-2x text-danger mr-3"></i>
                                                            <div>
                                                                <strong>Producción</strong>
                                                                <p class="mb-0 small text-muted">Comprobantes válidos oficiales</p>
                                                                <code class="small">api-cpe.sunat.gob.pe</code>
                                                            </div>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab 2: Certificado Digital --}}
                            <div class="tab-pane fade" id="tab-certificado" role="tabpanel">
                                <h5 class="mb-3">Certificado Digital</h5>

                                @if(isset($certificadoInfo) && !empty($certificadoInfo))
                                <div class="alert {{ $certificadoInfo['esta_vigente'] ? 'alert-success' : 'alert-danger' }}">
                                    <div class="row align-items-center">
                                        <div class="col-md-1 text-center">
                                            <i class="fas {{ $certificadoInfo['esta_vigente'] ? 'fa-check-circle' : 'fa-exclamation-circle' }} fa-3x"></i>
                                        </div>
                                        <div class="col-md-11">
                                            <h6 class="mb-2">Certificado {{ $certificadoInfo['esta_vigente'] ? 'Válido' : 'Expirado' }}</h6>
                                            <div class="row small">
                                                <div class="col-md-3">
                                                    <strong>RUC:</strong> {{ $certificadoInfo['ruc'] ?? 'N/A' }}
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>CN:</strong> {{ $certificadoInfo['cn'] ?? 'N/A' }}
                                                </div>
                                                <div class="col-md-2">
                                                    <strong>Válido desde:</strong> {{ $certificadoInfo['valid_from'] ?? 'N/A' }}
                                                </div>
                                                <div class="col-md-3">
                                                    <strong>Vence:</strong> {{ $certificadoInfo['valid_to'] ?? 'N/A' }}
                                                    @if(isset($certificadoInfo['dias_restantes']))
                                                    <span class="badge {{ $certificadoInfo['dias_restantes'] > 30 ? 'badge-success' : ($certificadoInfo['dias_restantes'] > 0 ? 'badge-warning' : 'badge-danger') }} ml-1">
                                                        {{ $certificadoInfo['dias_restantes'] }} días
                                                    </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @elseif(!empty($config->sire_cert_path))
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <strong>Certificado configurado:</strong> {{ basename($config->sire_cert_path) }}
                                </div>
                                @endif

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cert_p12">
                                                <i class="fas fa-upload mr-1"></i>Archivo del Certificado (.p12 o .pfx)
                                            </label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input @error('cert_p12') is-invalid @enderror"
                                                       id="cert_p12" name="cert_p12" accept=".p12,.pfx">
                                                <label class="custom-file-label" for="cert_p12">
                                                    @if(!empty($config->sire_cert_path))
                                                        {{ basename($config->sire_cert_path) }}
                                                    @else
                                                        Seleccionar archivo...
                                                    @endif
                                                </label>
                                            </div>
                                            @if(!empty($config->sire_cert_path))
                                                <small class="text-success d-block mt-1">
                                                    <i class="fas fa-check-circle"></i> Certificado cargado
                                                </small>
                                            @endif
                                            @error('cert_p12')
                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cert_password">
                                                <i class="fas fa-lock mr-1"></i>Contraseña del Certificado
                                            </label>
                                            <div class="input-group">
                                                <input type="password"
                                                       class="form-control @error('cert_password') is-invalid @enderror"
                                                       id="cert_password" name="cert_password"
                                                       placeholder="{{ !empty($config->sire_cert_password) ? '••••••••' : 'Ingresa la contraseña' }}">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button" id="toggleCertPassword">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            @if(!empty($config->sire_cert_password))
                                                <small class="text-success d-block mt-1">
                                                    <i class="fas fa-check-circle"></i> Contraseña guardada
                                                </small>
                                            @endif
                                            <small class="text-muted d-block mt-1">
                                                Solo ingresa si subes un nuevo certificado
                                            </small>
                                            @error('cert_password')
                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info border-left-info">
                                    <strong><i class="fas fa-info-circle mr-1"></i>¿Cómo obtener el certificado?</strong>
                                    <p class="mb-0 mt-2">Solicítalo a una PSE autorizada por SUNAT como <a href="https://llama.pe" target="_blank">llama.pe</a> o <a href="https://www.ebiz.com.pe" target="_blank">eBiz Perú</a>. Descárgalo en formato .p12 o .pfx.</p>
                                </div>
                            </div>

                            {{-- Tab 3: Credenciales SOL --}}
                            <div class="tab-pane fade" id="tab-credenciales" role="tabpanel">
                                <h5 class="mb-3">Credenciales SUNAT SOL</h5>

                                <div class="alert alert-warning border-left-warning">
                                    <strong><i class="fas fa-exclamation-triangle mr-1"></i>Importante:</strong>
                                    Usa un <strong>usuario secundario</strong> SUNAT SOL, nunca el usuario principal. Créalo en SUNAT Operaciones en Línea.
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sol_user">
                                                <i class="fas fa-user mr-1"></i>Usuario SOL Secundario
                                            </label>
                                            <input type="text"
                                                   class="form-control @error('sol_user') is-invalid @enderror"
                                                   id="sol_user" name="sol_user"
                                                   value="{{ old('sol_user', $config->sol_user ?? '') }}"
                                                   placeholder="Ej: MODDATOS">
                                            @if(!empty($config->sol_user))
                                                <small class="text-success d-block mt-1">
                                                    <i class="fas fa-check-circle"></i> Usuario configurado: {{ $config->sol_user }}
                                                </small>
                                            @endif
                                            @error('sol_user')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sol_pass">
                                                <i class="fas fa-lock mr-1"></i>Contraseña SOL
                                            </label>
                                            <div class="input-group">
                                                <input type="password"
                                                       class="form-control @error('sol_pass') is-invalid @enderror"
                                                       id="sol_pass" name="sol_pass"
                                                       placeholder="{{ !empty($config->sol_pass) ? '••••••••' : 'Contraseña del usuario SOL' }}">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button" id="toggleSolPassword">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            @if(!empty($config->sol_pass))
                                                <small class="text-success d-block mt-1">
                                                    <i class="fas fa-check-circle"></i> Contraseña guardada
                                                </small>
                                            @endif
                                            <small class="text-muted d-block mt-1">
                                                Se encripta antes de guardar
                                            </small>
                                            @error('sol_pass')
                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab 4: OAuth2 API SUNAT --}}
                            <div class="tab-pane fade" id="tab-oauth2" role="tabpanel">
                                <h5 class="mb-3">Credenciales OAuth2 de la API SUNAT</h5>

                                <div class="alert alert-info border-left-info">
                                    <strong><i class="fas fa-info-circle mr-1"></i>¿Cómo obtener estas credenciales?</strong>
                                    <ol class="mb-0 mt-2">
                                        <li>Ingresa al <a href="https://e-menu.sunat.gob.pe/ci-ti-itmenu/MenuInternet.htm" target="_blank">Portal SOL de SUNAT</a></li>
                                        <li>Ve a: <strong>EMPRESAS > Credenciales de API SUNAT > Gestión Credenciales de API SUNAT</strong></li>
                                        <li>Crea una nueva aplicación (nombre: tu sistema, URL: tu dominio)</li>
                                        <li>Selecciona "MIGE RCE y RVIE - SIRE" y alcance "Web"</li>
                                        <li>Guarda el <code>ID (client_id)</code> y <code>CLAVE (client_secret)</code> generados</li>
                                    </ol>
                                </div>

                                <div class="alert alert-warning border-left-warning">
                                    <strong><i class="fas fa-exclamation-triangle mr-1"></i>Importante:</strong>
                                    Estas credenciales son <strong>OBLIGATORIAS</strong> para evitar el error "0111 - No tiene el perfil para enviar comprobantes electrónicos". Sin OAuth2, SUNAT rechazará tus comprobantes.
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sire_client_id">
                                                <i class="fas fa-fingerprint mr-1"></i>Client ID <span class="text-danger">*</span>
                                            </label>
                                            <input type="text"
                                                   class="form-control @error('sire_client_id') is-invalid @enderror"
                                                   id="sire_client_id" name="sire_client_id"
                                                   value="{{ old('sire_client_id', $config->sire_client_id ?? '') }}"
                                                   placeholder="Ej: 3cce4e15-275a-46c4-97ae-bd5a1e8221dd">
                                            @if(!empty($config->sire_client_id))
                                                <small class="text-success d-block mt-1">
                                                    <i class="fas fa-check-circle"></i> Client ID configurado
                                                </small>
                                            @else
                                                <small class="text-danger d-block mt-1">
                                                    <i class="fas fa-exclamation-circle"></i> Requerido para evitar error 0111
                                                </small>
                                            @endif
                                            @error('sire_client_id')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sire_client_secret">
                                                <i class="fas fa-lock mr-1"></i>Client Secret <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <input type="password"
                                                       class="form-control @error('sire_client_secret') is-invalid @enderror"
                                                       id="sire_client_secret" name="sire_client_secret"
                                                       placeholder="{{ !empty($config->sire_client_secret) ? '••••••••••••' : 'Ej: 1-c82fHhluDbOQgo18FW8g==' }}">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" type="button" id="toggleOAuth2Secret">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            @if(!empty($config->sire_client_secret))
                                                <small class="text-success d-block mt-1">
                                                    <i class="fas fa-check-circle"></i> Client Secret guardado (encriptado)
                                                </small>
                                            @else
                                                <small class="text-danger d-block mt-1">
                                                    <i class="fas fa-exclamation-circle"></i> Requerido para autenticación
                                                </small>
                                            @endif
                                            @error('sire_client_secret')
                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                @if(!empty($config->sire_access_token) && !empty($config->sire_token_expires_at))
                                <div class="alert alert-success">
                                    <h6 class="mb-2"><i class="fas fa-check-circle mr-1"></i>Token OAuth2 Activo</h6>
                                    <div class="row small">
                                        <div class="col-md-6">
                                            <strong>Expira el:</strong> {{ \Carbon\Carbon::parse($config->sire_token_expires_at)->format('d/m/Y H:i:s') }}
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Tiempo restante:</strong>
                                            @php
                                                $minutesRemaining = \Carbon\Carbon::parse($config->sire_token_expires_at)->diffInMinutes(now(), false);
                                            @endphp
                                            <span class="badge {{ $minutesRemaining > 0 ? 'badge-success' : 'badge-danger' }}">
                                                {{ $minutesRemaining > 0 ? $minutesRemaining . ' minutos' : 'Expirado' }}
                                            </span>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        El token se renueva automáticamente cada hora cuando envías comprobantes.
                                    </small>
                                </div>
                                @endif

                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="mb-2"><i class="fas fa-info-circle text-info mr-1"></i>¿Qué es OAuth2?</h6>
                                        <p class="small mb-0">
                                            OAuth2 es el nuevo sistema de autenticación de SUNAT para la API de comprobantes electrónicos.
                                            Reemplaza las credenciales SOL tradicionales y es obligatorio para enviar comprobantes a través de la API SIRE.
                                            El sistema genera automáticamente un token de acceso que expira cada hora y se renueva automáticamente.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Tab 5: Series y Numeración --}}
                            <div class="tab-pane fade" id="tab-series" role="tabpanel">
                                <h5 class="mb-3">Series y Numeración de Comprobantes</h5>

                                <p class="text-muted">La numeración se incrementa automáticamente con cada comprobante emitido.</p>

                                {{-- Testing --}}
                                <div class="card mb-4 border-info">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-flask mr-2"></i>Ambiente de Pruebas (Testing)
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="sire_serie_boleta_test">Serie Boletas</label>
                                                    <input type="text" class="form-control @error('sire_serie_boleta_test') is-invalid @enderror"
                                                           id="sire_serie_boleta_test" name="sire_serie_boleta_test"
                                                           value="{{ old('sire_serie_boleta_test', $config->sire_serie_boleta_test ?? 'T001') }}"
                                                           placeholder="T001" maxlength="4">
                                                    @error('sire_serie_boleta_test')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="sire_numero_boleta_test">Próximo Número</label>
                                                    <input type="number" class="form-control @error('sire_numero_boleta_test') is-invalid @enderror"
                                                           id="sire_numero_boleta_test" name="sire_numero_boleta_test"
                                                           value="{{ old('sire_numero_boleta_test', $config->sire_numero_boleta_test ?? 1) }}"
                                                           min="1">
                                                    @error('sire_numero_boleta_test')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="sire_serie_factura_test">Serie Facturas</label>
                                                    <input type="text" class="form-control @error('sire_serie_factura_test') is-invalid @enderror"
                                                           id="sire_serie_factura_test" name="sire_serie_factura_test"
                                                           value="{{ old('sire_serie_factura_test', $config->sire_serie_factura_test ?? 'T001') }}"
                                                           placeholder="T001" maxlength="4">
                                                    @error('sire_serie_factura_test')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="sire_numero_factura_test">Próximo Número</label>
                                                    <input type="number" class="form-control @error('sire_numero_factura_test') is-invalid @enderror"
                                                           id="sire_numero_factura_test" name="sire_numero_factura_test"
                                                           value="{{ old('sire_numero_factura_test', $config->sire_numero_factura_test ?? 1) }}"
                                                           min="1">
                                                    @error('sire_numero_factura_test')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Producción --}}
                                <div class="card border-success">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-check-circle mr-2"></i>Ambiente de Producción
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="sire_serie_boleta_prod">Serie Boletas</label>
                                                    <input type="text" class="form-control @error('sire_serie_boleta_prod') is-invalid @enderror"
                                                           id="sire_serie_boleta_prod" name="sire_serie_boleta_prod"
                                                           value="{{ old('sire_serie_boleta_prod', $config->sire_serie_boleta_prod ?? 'B001') }}"
                                                           placeholder="B001" maxlength="4">
                                                    @error('sire_serie_boleta_prod')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="sire_numero_boleta_prod">Próximo Número</label>
                                                    <input type="number" class="form-control @error('sire_numero_boleta_prod') is-invalid @enderror"
                                                           id="sire_numero_boleta_prod" name="sire_numero_boleta_prod"
                                                           value="{{ old('sire_numero_boleta_prod', $config->sire_numero_boleta_prod ?? 1) }}"
                                                           min="1">
                                                    @error('sire_numero_boleta_prod')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-0">
                                                    <label for="sire_serie_factura_prod">Serie Facturas</label>
                                                    <input type="text" class="form-control @error('sire_serie_factura_prod') is-invalid @enderror"
                                                           id="sire_serie_factura_prod" name="sire_serie_factura_prod"
                                                           value="{{ old('sire_serie_factura_prod', $config->sire_serie_factura_prod ?? 'F001') }}"
                                                           placeholder="F001" maxlength="4">
                                                    @error('sire_serie_factura_prod')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-0">
                                                    <label for="sire_numero_factura_prod">Próximo Número</label>
                                                    <input type="number" class="form-control @error('sire_numero_factura_prod') is-invalid @enderror"
                                                           id="sire_numero_factura_prod" name="sire_numero_factura_prod"
                                                           value="{{ old('sire_numero_factura_prod', $config->sire_numero_factura_prod ?? 1) }}"
                                                           min="1">
                                                    @error('sire_numero_factura_prod')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                @if(!empty($config->sire_cert_path) && !empty($config->sol_user))
                                <button type="button" class="btn btn-info" id="btnTestConnection">
                                    <i class="fas fa-plug mr-2"></i>Probar Conexión SUNAT
                                </button>
                                @endif
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save mr-2"></i>Guardar Configuración
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal para prueba de conexión --}}
<div class="modal fade" id="connectionTestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plug mr-2"></i>Prueba de Conexión SUNAT
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="connectionTestResult">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Probando conexión...</span>
                    </div>
                    <p class="mt-3 text-muted">Verificando conexión con SUNAT...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    .border-left-primary { border-left: 4px solid #4e73df !important; }
    .border-left-success { border-left: 4px solid #1cc88a !important; }
    .border-left-info { border-left: 4px solid #36b9cc !important; }
    .border-left-warning { border-left: 4px solid #f6c23e !important; }
    .border-left-danger { border-left: 4px solid #e74a3b !important; }
    .border-left-secondary { border-left: 4px solid #858796 !important; }

    .text-xs {
        font-size: 0.7rem;
        letter-spacing: 0.05em;
    }

    .custom-switch-lg .custom-control-label::before {
        height: 1.5rem;
        width: 2.75rem;
        border-radius: 3rem;
    }

    .custom-switch-lg .custom-control-label::after {
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 3rem;
    }

    .custom-switch-lg .custom-control-input:checked ~ .custom-control-label::after {
        transform: translateX(1.25rem);
    }

    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        padding: 0.75rem 1.25rem;
    }

    .nav-tabs .nav-link.active {
        color: #495057;
        background-color: #fff;
        border-bottom: 3px solid #4e73df;
        font-weight: 600;
    }

    .nav-tabs .nav-link:hover {
        border-color: transparent;
        color: #4e73df;
    }
</style>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Custom file input label update
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });

    // Toggle password visibility - Certificado
    $('#toggleCertPassword').on('click', function() {
        const passwordField = $('#cert_password');
        const icon = $(this).find('i');

        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Toggle password visibility - SOL
    $('#toggleSolPassword').on('click', function() {
        const passwordField = $('#sol_pass');
        const icon = $(this).find('i');

        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Toggle password visibility - OAuth2 Secret
    $('#toggleOAuth2Secret').on('click', function() {
        const passwordField = $('#sire_client_secret');
        const icon = $(this).find('i');

        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Probar conexión SUNAT
    $('#btnTestConnection').on('click', function() {
        $('#connectionTestModal').modal('show');

        $.ajax({
            url: '{{ route("admin.sire-config.test-connection") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                let html = '';

                if (response.success) {
                    html = `
                        <div class="alert alert-success border-left-success">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle fa-3x mr-3"></i>
                                <div>
                                    <h5 class="mb-1">Conexión Exitosa</h5>
                                    <p class="mb-0">${response.message}</p>
                                </div>
                            </div>
                        </div>
                        <h6 class="mt-3">Detalles de la Conexión:</h6>
                        <table class="table table-sm">
                            <tr>
                                <th width="200">Ambiente:</th>
                                <td>${response.data.environment}</td>
                            </tr>
                            <tr>
                                <th>RUC:</th>
                                <td>${response.data.ruc || 'N/A'}</td>
                            </tr>
                            <tr>
                                <th>Certificado válido:</th>
                                <td>${response.data.certificate_valid ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-danger">No</span>'}</td>
                            </tr>
                            <tr>
                                <th>Estado SUNAT:</th>
                                <td>${response.data.sunat_status}</td>
                            </tr>
                        </table>
                    `;
                } else {
                    html = `
                        <div class="alert alert-danger border-left-danger">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle fa-3x mr-3"></i>
                                <div>
                                    <h5 class="mb-1">Error de Conexión</h5>
                                    <p class="mb-0">${response.error}</p>
                                </div>
                            </div>
                        </div>
                        <h6 class="mt-3">Verifica lo siguiente:</h6>
                        <ul>
                            <li>Certificado digital válido y contraseña correcta</li>
                            <li>Credenciales SOL correctas</li>
                            <li>Conexión a internet activa</li>
                            <li>Servicios de SUNAT operativos</li>
                        </ul>
                    `;
                }

                $('#connectionTestResult').html(html);
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                let html = `
                    <div class="alert alert-danger border-left-danger">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-times-circle fa-3x mr-3"></i>
                            <div>
                                <h5 class="mb-1">Error</h5>
                                <p class="mb-0">${response?.error || 'Error al probar la conexión'}</p>
                            </div>
                        </div>
                    </div>
                `;
                $('#connectionTestResult').html(html);
            }
        });
    });
});
</script>
@endsection
