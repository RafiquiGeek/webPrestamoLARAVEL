@extends('layouts.admin')

@section('title', 'Diagnóstico SUNAT')

@section('content')
@php
    $configActiva = \App\Models\ConfiguracionSunat::where('activo', true)->first();
    $editUrl = $configActiva ? route('admin.configuracion-sunat.edit', $configActiva->id) : null;

    $total = max(1, (int)($data['resumen']['total_verificaciones'] ?? 0));
    $exitosas = (int)($data['resumen']['exitosas'] ?? 0);
    $advertencias = (int)($data['resumen']['advertencias'] ?? 0);
    $criticos = (int)($data['resumen']['criticos'] ?? 0);
    $score = (int) round(($exitosas / $total) * 100);

    if ($criticos > 0) {
        $heroClass = 'hero-danger';
        $heroLabel = 'Requiere atención';
        $heroIcon = 'fa-exclamation-triangle';
    } elseif ($advertencias > 0) {
        $heroClass = 'hero-warning';
        $heroLabel = 'Funcional con avisos';
        $heroIcon = 'fa-exclamation-circle';
    } else {
        $heroClass = 'hero-success';
        $heroLabel = 'Todo en orden';
        $heroIcon = 'fa-check-circle';
    }
@endphp

<div class="container-fluid diag-wrapper">

    {{-- Hero --}}
    <div class="diag-hero {{ $heroClass }}">
        <div class="diag-hero-left">
            <div class="diag-hero-icon">
                <i class="fas {{ $heroIcon }}"></i>
            </div>
            <div>
                <div class="diag-hero-eyebrow">Diagnóstico SUNAT</div>
                <h1 class="diag-hero-title">{{ $heroLabel }}</h1>
                <div class="diag-hero-sub">{{ $data['resumen']['mensaje'] }}</div>
            </div>
        </div>
        <div class="diag-hero-right">
            <div class="diag-score">
                <svg viewBox="0 0 120 120" class="diag-score-ring">
                    <circle cx="60" cy="60" r="52" class="diag-score-track" />
                    <circle cx="60" cy="60" r="52" class="diag-score-value"
                        style="stroke-dasharray: {{ round($score * 3.2672, 2) }} 326.72;" />
                </svg>
                <div class="diag-score-text">
                    <div class="diag-score-num">{{ $score }}<small>%</small></div>
                    <div class="diag-score-label">salud</div>
                </div>
            </div>
            <div class="diag-hero-actions">
                <button type="button" class="btn btn-light btn-sm" onclick="recargarDiagnostico()">
                    <i class="fas fa-sync-alt me-1"></i> Reevaluar
                </button>
                <a href="{{ route('admin.configuracion-sunat.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-cogs me-1"></i> Configuración
                </a>
            </div>
        </div>
    </div>

    {{-- Stats strip --}}
    <div class="row g-3 diag-stats">
        <div class="col-6 col-md-3">
            <div class="diag-stat stat-total">
                <div class="diag-stat-icon"><i class="fas fa-list-check"></i></div>
                <div>
                    <div class="diag-stat-num">{{ $data['resumen']['total_verificaciones'] }}</div>
                    <div class="diag-stat-label">Verificaciones</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="diag-stat stat-ok">
                <div class="diag-stat-icon"><i class="fas fa-check"></i></div>
                <div>
                    <div class="diag-stat-num">{{ $exitosas }}</div>
                    <div class="diag-stat-label">Exitosas</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="diag-stat stat-warn">
                <div class="diag-stat-icon"><i class="fas fa-triangle-exclamation"></i></div>
                <div>
                    <div class="diag-stat-num">{{ $advertencias }}</div>
                    <div class="diag-stat-label">Advertencias</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="diag-stat stat-bad">
                <div class="diag-stat-icon"><i class="fas fa-xmark"></i></div>
                <div>
                    <div class="diag-stat-num">{{ $criticos }}</div>
                    <div class="diag-stat-label">Críticos</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Datos generales --}}
    <div class="diag-panel">
        <div class="diag-panel-header">
            <i class="fas fa-building me-2 text-primary"></i>
            <span>Datos generales</span>
        </div>
        <div class="diag-panel-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="diag-kv">
                        <div class="diag-kv-label"><i class="fas fa-circle-dot me-1"></i> Estado</div>
                        <div class="diag-kv-value">
                            <span class="badge {{ $data['estado_general']['activo'] ? 'bg-success-soft' : 'bg-danger-soft' }}">
                                {{ $data['estado_general']['activo'] ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="diag-kv">
                        <div class="diag-kv-label"><i class="fas fa-globe me-1"></i> Ambiente</div>
                        <div class="diag-kv-value">
                            <span class="badge {{ ($data['estado_general']['ambiente'] ?? null) === 'produccion' ? 'bg-primary-soft' : 'bg-warning-soft' }}">
                                {{ strtoupper($data['estado_general']['ambiente'] ?? 'n/a') }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="diag-kv">
                        <div class="diag-kv-label"><i class="fas fa-id-card me-1"></i> RUC</div>
                        <div class="diag-kv-value"><strong>{{ $data['estado_general']['ruc'] ?? '—' }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bloques dinámicos --}}
    @php
        $secciones = [
            ['key' => 'configuracion', 'label' => 'Configuración', 'icon' => 'fa-cogs', 'color' => 'primary'],
            ['key' => 'certificados_permisos', 'label' => 'Certificados y firma digital', 'icon' => 'fa-certificate', 'color' => 'purple'],
            ['key' => 'conectividad', 'label' => 'Conectividad y entorno', 'icon' => 'fa-wifi', 'color' => 'info'],
        ];
    @endphp

    @foreach($secciones as $sec)
        @php $items = $data[$sec['key']] ?? []; @endphp
        @if(count($items) > 0)
            <div class="diag-panel">
                <div class="diag-panel-header">
                    <i class="fas {{ $sec['icon'] }} me-2 text-{{ $sec['color'] }}"></i>
                    <span>{{ $sec['label'] }}</span>
                    <span class="ms-auto diag-count">
                        {{ collect($items)->where('estado', true)->count() }}/{{ count($items) }}
                    </span>
                </div>
                <div class="diag-panel-body p-0">
                    <ul class="diag-list">
                        @foreach($items as $item)
                            @php
                                $ok = (bool)($item['estado'] ?? false);
                                $valor = $item['descripcion'] ?? ($item['valor'] ?? '');
                            @endphp
                            <li class="diag-item {{ $ok ? 'is-ok' : 'is-bad' }}">
                                <div class="diag-item-icon">
                                    @if($ok)
                                        <i class="fas fa-check"></i>
                                    @else
                                        <i class="fas fa-xmark"></i>
                                    @endif
                                </div>
                                <div class="diag-item-body">
                                    <div class="diag-item-name">
                                        {{ $item['nombre'] }}
                                        @if(isset($item['tiempo']))
                                            <span class="diag-item-chip">{{ $item['tiempo'] }} ms</span>
                                        @endif
                                    </div>
                                    @if($valor !== '')
                                        <div class="diag-item-desc">{{ $valor }}</div>
                                    @endif
                                </div>
                                <div class="diag-item-action">
                                    @if(! $ok && isset($item['accion']))
                                        @if(($item['accion']['tipo'] ?? '') === 'ir_editar' && $editUrl)
                                            <a href="{{ $editUrl }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit me-1"></i>{{ $item['accion']['label'] }}
                                            </a>
                                        @elseif(($item['accion']['tipo'] ?? '') !== 'ir_editar')
                                            <button type="button"
                                                    class="btn btn-sm btn-warning btn-fix"
                                                    data-accion="{{ $item['accion']['tipo'] }}"
                                                    data-requiere-input="{{ !empty($item['accion']['requiere_input']) ? '1' : '0' }}"
                                                    data-input-label="{{ $item['accion']['input_label'] ?? '' }}"
                                                    data-item-nombre="{{ $item['nombre'] }}">
                                                <i class="fas fa-wrench me-1"></i>{{ $item['accion']['label'] }}
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    @endforeach

    {{-- Test live de autenticación SOL --}}
    <div class="diag-panel diag-auth-test">
        <div class="diag-panel-header">
            <i class="fas fa-plug me-2 text-success"></i>
            <span>Prueba en vivo: Autenticación SOL contra SUNAT</span>
        </div>
        <div class="diag-panel-body">
            <p class="text-muted mb-3" style="font-size: 0.9rem;">
                Hace una llamada SOAP real al endpoint de SUNAT con tus credenciales.
                Distingue entre <strong>credenciales OK + perfil OK</strong>, <strong>perfil faltante (0111)</strong> y <strong>credenciales incorrectas (0102/0104)</strong>.
                <br><small>Puede tardar 5-15 segundos.</small>
            </p>

            <button type="button" class="btn btn-success" id="btnProbarAuth">
                <i class="fas fa-paper-plane me-1"></i> Probar autenticación con SUNAT
            </button>

            <div id="authTestResult" class="mt-3" style="display: none;"></div>
        </div>
    </div>

    {{-- Recomendaciones --}}
    @if(count($data['recomendaciones'] ?? []) > 0)
        <div class="diag-panel diag-reco">
            <div class="diag-panel-header">
                <i class="fas fa-lightbulb me-2 text-warning"></i>
                <span>Recomendaciones</span>
            </div>
            <div class="diag-panel-body">
                <ol class="diag-reco-list">
                    @foreach($data['recomendaciones'] as $recomendacion)
                        <li>{{ $recomendacion }}</li>
                    @endforeach
                </ol>
            </div>
        </div>
    @endif

    <div class="diag-footer">
        <small class="text-muted"><i class="far fa-clock me-1"></i>Última verificación: {{ now()->format('d/m/Y H:i:s') }}</small>
    </div>

</div>

{{-- Modal para inputs --}}
<div class="modal fade" id="modalFix" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content diag-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-wrench me-2"></i><span id="modalFixTitle">Reparar</span></h5>
                <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 mb-3" id="modalFixItemWrap">
                    <strong>Ítem:</strong> <span id="modalFixItem"></span>
                </div>
                <div class="form-group mb-0">
                    <label for="modalFixInput" id="modalFixInputLabel" class="form-label">Valor</label>
                    <input type="password" id="modalFixInput" class="form-control" autocomplete="off" placeholder="Ingresa el valor correcto...">
                    <small class="form-text text-muted mt-2">
                        El valor se guardará cifrado con la APP_KEY de este ambiente.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="modalFixSubmit">
                    <i class="fas fa-wrench me-1"></i> Aplicar corrección
                </button>
            </div>
        </div>
    </div>
</div>

@stop

@section('css')
<style>
    .diag-wrapper {
        max-width: 1200px;
        padding-top: 0.5rem;
    }

    /* ---- Hero ---- */
    .diag-hero {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1.5rem;
        padding: 1.75rem 2rem;
        border-radius: 16px;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 10px 30px -12px rgba(0,0,0,0.25);
        flex-wrap: wrap;
    }
    .hero-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    .hero-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    .hero-danger  { background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); }
    .diag-hero-left { display: flex; align-items: center; gap: 1.25rem; }
    .diag-hero-icon {
        width: 64px; height: 64px; border-radius: 16px;
        background: rgba(255,255,255,0.18);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.75rem;
    }
    .diag-hero-eyebrow {
        text-transform: uppercase;
        letter-spacing: 1.5px;
        font-size: 0.75rem;
        opacity: 0.85;
    }
    .diag-hero-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #fff; }
    .diag-hero-sub { font-size: 0.95rem; opacity: 0.9; margin-top: 0.25rem; }
    .diag-hero-right { display: flex; align-items: center; gap: 1.5rem; }
    .diag-hero-actions { display: flex; flex-direction: column; gap: 0.5rem; }

    /* ---- Score ring ---- */
    .diag-score {
        position: relative;
        width: 110px; height: 110px;
    }
    .diag-score-ring { transform: rotate(-90deg); width: 100%; height: 100%; }
    .diag-score-track { fill: none; stroke: rgba(255,255,255,0.25); stroke-width: 8; }
    .diag-score-value {
        fill: none; stroke: #fff; stroke-width: 8;
        stroke-linecap: round;
        transition: stroke-dasharray 0.8s ease-out;
    }
    .diag-score-text {
        position: absolute; inset: 0;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        color: #fff;
    }
    .diag-score-num { font-size: 1.6rem; font-weight: 700; line-height: 1; }
    .diag-score-num small { font-size: 0.9rem; opacity: 0.8; }
    .diag-score-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.85; }

    /* ---- Stats ---- */
    .diag-stats { margin-bottom: 1.25rem; }
    .diag-stat {
        display: flex; align-items: center; gap: 0.9rem;
        background: #fff;
        border-radius: 12px;
        padding: 1rem 1.15rem;
        box-shadow: 0 2px 6px -2px rgba(0,0,0,0.08);
        border: 1px solid #eef0f4;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .diag-stat:hover { transform: translateY(-2px); box-shadow: 0 6px 16px -4px rgba(0,0,0,0.1); }
    .diag-stat-icon {
        width: 44px; height: 44px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 1.1rem;
    }
    .diag-stat-num { font-size: 1.4rem; font-weight: 700; line-height: 1; }
    .diag-stat-label { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; margin-top: 0.25rem; }
    .stat-total .diag-stat-icon { background: #3b82f6; }
    .stat-ok    .diag-stat-icon { background: #10b981; }
    .stat-warn  .diag-stat-icon { background: #f59e0b; }
    .stat-bad   .diag-stat-icon { background: #ef4444; }

    /* ---- Panels ---- */
    .diag-panel {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        margin-bottom: 1.25rem;
        overflow: hidden;
    }
    .diag-panel-header {
        display: flex; align-items: center;
        padding: 0.9rem 1.25rem;
        font-weight: 600;
        border-bottom: 1px solid #eef0f4;
        background: #fafbfc;
    }
    .diag-count {
        font-size: 0.8rem;
        background: #eef2ff;
        color: #4f46e5;
        padding: 0.2rem 0.6rem;
        border-radius: 999px;
        font-weight: 600;
    }
    .diag-panel-body { padding: 1rem 1.25rem; }
    .diag-panel-body.p-0 { padding: 0; }
    .text-purple { color: #8b5cf6 !important; }

    /* ---- KV grid ---- */
    .diag-kv {
        background: #f9fafb;
        border-radius: 10px;
        padding: 0.85rem 1rem;
    }
    .diag-kv-label { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; margin-bottom: 0.35rem; }
    .diag-kv-value { font-size: 1rem; }
    .bg-success-soft { background: #d1fae5 !important; color: #065f46 !important; }
    .bg-danger-soft  { background: #fee2e2 !important; color: #991b1b !important; }
    .bg-primary-soft { background: #dbeafe !important; color: #1e40af !important; }
    .bg-warning-soft { background: #fef3c7 !important; color: #92400e !important; }

    /* ---- Items list ---- */
    .diag-list { list-style: none; margin: 0; padding: 0; }
    .diag-item {
        display: flex; align-items: center; gap: 1rem;
        padding: 0.95rem 1.25rem;
        border-bottom: 1px solid #f1f2f5;
        transition: background 0.15s ease;
    }
    .diag-item:last-child { border-bottom: 0; }
    .diag-item:hover { background: #fafbfc; }
    .diag-item-icon {
        flex: 0 0 auto;
        width: 32px; height: 32px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.85rem;
        color: #fff;
    }
    .diag-item.is-ok  .diag-item-icon { background: #10b981; }
    .diag-item.is-bad .diag-item-icon { background: #ef4444; }
    .diag-item.is-bad { background: rgba(254, 226, 226, 0.25); }
    .diag-item-body { flex: 1 1 auto; min-width: 0; }
    .diag-item-name { font-weight: 600; color: #1f2937; }
    .diag-item-desc { font-size: 0.88rem; color: #6b7280; margin-top: 0.2rem; word-break: break-word; }
    .diag-item-chip {
        display: inline-block;
        font-size: 0.72rem;
        background: #eef2ff;
        color: #4f46e5;
        padding: 0.1rem 0.5rem;
        border-radius: 999px;
        margin-left: 0.4rem;
        font-weight: 500;
    }
    .diag-item-action { flex: 0 0 auto; }

    /* ---- Recos ---- */
    .diag-reco-list { margin: 0; padding-left: 1.25rem; }
    .diag-reco-list li { padding: 0.35rem 0; color: #374151; }

    .diag-footer { text-align: right; padding: 0.5rem 0 1.5rem; }

    /* Modal */
    .diag-modal { border-radius: 14px; border: 0; }
    .diag-modal .modal-header { border-bottom-color: #eef0f4; }
    .diag-modal .modal-footer { border-top-color: #eef0f4; }

    @media (max-width: 768px) {
        .diag-hero { flex-direction: column; align-items: flex-start; padding: 1.25rem; }
        .diag-hero-right { width: 100%; justify-content: space-between; }
        .diag-hero-title { font-size: 1.35rem; }
        .diag-item { flex-wrap: wrap; }
        .diag-item-action { width: 100%; margin-top: 0.5rem; }
    }
</style>
@stop

@section('js')
<script>
function recargarDiagnostico() {
    location.reload();
}

(function() {
    const reparar = (accion, valor, btn) => {
        const original = btn ? btn.innerHTML : null;
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aplicando...';
        }

        return fetch('{{ route('admin.sunat.diagnostico.reparar') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ accion: accion, valor: valor || null }),
        })
        .then(async (r) => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok || !data.success) { throw new Error(data.message || ('HTTP ' + r.status)); }
            return data;
        })
        .then((data) => {
            if (typeof toastr !== 'undefined') { toastr.success(data.message); } else { alert(data.message); }
            setTimeout(() => location.reload(), 600);
        })
        .catch((err) => {
            if (typeof toastr !== 'undefined') { toastr.error(err.message); } else { alert('Error: ' + err.message); }
            if (btn) { btn.disabled = false; btn.innerHTML = original; }
        });
    };

    const openModal = () => {
        if (typeof $ !== 'undefined' && $.fn.modal) { $('#modalFix').modal('show'); return; }
        if (typeof bootstrap !== 'undefined') { new bootstrap.Modal(document.getElementById('modalFix')).show(); return; }
    };

    document.querySelectorAll('.btn-fix').forEach((btn) => {
        btn.addEventListener('click', () => {
            const accion = btn.dataset.accion;
            const requiereInput = btn.dataset.requiereInput === '1';

            if (!requiereInput) {
                if (!confirm('¿Ejecutar "' + btn.innerText.trim() + '"?')) return;
                reparar(accion, null, btn);
                return;
            }

            document.getElementById('modalFixTitle').textContent = btn.innerText.trim();
            document.getElementById('modalFixItem').textContent = btn.dataset.itemNombre || '';
            document.getElementById('modalFixInputLabel').textContent = btn.dataset.inputLabel || 'Valor';
            const input = document.getElementById('modalFixInput');
            input.value = '';

            const submitBtn = document.getElementById('modalFixSubmit');
            const newSubmit = submitBtn.cloneNode(true);
            submitBtn.parentNode.replaceChild(newSubmit, submitBtn);
            newSubmit.addEventListener('click', () => {
                const valor = input.value;
                if (!valor) { input.focus(); return; }
                reparar(accion, valor, newSubmit);
            });

            openModal();
            setTimeout(() => input.focus(), 300);
        });
    });

    // ---- Prueba en vivo contra SUNAT ----
    const btnAuth = document.getElementById('btnProbarAuth');
    const resultBox = document.getElementById('authTestResult');

    if (btnAuth) {
        btnAuth.addEventListener('click', async () => {
            const original = btnAuth.innerHTML;
            btnAuth.disabled = true;
            btnAuth.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Llamando a SUNAT...';

            resultBox.style.display = 'block';
            resultBox.innerHTML = '<div class="alert alert-info mb-0"><i class="fas fa-hourglass-half me-2"></i>Esperando respuesta de SUNAT (puede tardar 5-15s)...</div>';

            try {
                const r = await fetch('{{ route('admin.sunat.diagnostico.probar-auth') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({}),
                });
                const data = await r.json().catch(() => ({}));

                let alertCls = 'alert-secondary';
                let icon = 'fa-info-circle';
                let titulo = 'Resultado';

                switch (data.estado) {
                    case 'auth_ok':
                        alertCls = 'alert-success'; icon = 'fa-check-circle';
                        titulo = '✓ Autenticación OK + Perfil OK';
                        break;
                    case 'sin_perfil':
                        alertCls = 'alert-danger'; icon = 'fa-user-slash';
                        titulo = '✗ Falta perfil "Emitir Comprobantes Electrónicos" (código 0111)';
                        break;
                    case 'auth_invalida':
                        alertCls = 'alert-danger'; icon = 'fa-key';
                        titulo = '✗ Credenciales SOL incorrectas';
                        break;
                    case 'cred_no_desencripta':
                        alertCls = 'alert-warning'; icon = 'fa-lock';
                        titulo = 'No se pudo desencriptar sol_pass';
                        break;
                    case 'cert_faltante':
                        alertCls = 'alert-warning'; icon = 'fa-certificate';
                        titulo = 'Faltan archivos PEM del certificado';
                        break;
                    case 'config_incompleta':
                        alertCls = 'alert-warning'; icon = 'fa-exclamation';
                        titulo = 'Configuración incompleta';
                        break;
                    case 'excepcion':
                    case 'desconocido':
                    default:
                        alertCls = 'alert-warning'; icon = 'fa-question-circle';
                        titulo = 'Respuesta inesperada';
                }

                let html = '<div class="alert ' + alertCls + ' mb-0">';
                html += '<h6 class="mb-2"><i class="fas ' + icon + ' me-2"></i>' + titulo + '</h6>';
                html += '<div>' + (data.mensaje || '(sin mensaje)') + '</div>';
                if (data.codigo) {
                    html += '<div class="mt-2"><strong>Código SUNAT:</strong> <code>' + data.codigo + '</code></div>';
                }
                if (data.detalle_sunat) {
                    html += '<details class="mt-2"><summary class="text-muted" style="cursor:pointer;font-size:0.85rem;">Ver respuesta cruda de SUNAT</summary>';
                    html += '<pre class="mt-2 mb-0" style="font-size:0.8rem;background:rgba(0,0,0,0.05);padding:0.5rem;border-radius:4px;">' + (data.detalle_sunat || '') + '</pre>';
                    html += '</details>';
                }
                html += '</div>';

                resultBox.innerHTML = html;
            } catch (err) {
                resultBox.innerHTML = '<div class="alert alert-danger mb-0"><i class="fas fa-times-circle me-2"></i>Error de red: ' + err.message + '</div>';
            } finally {
                btnAuth.disabled = false;
                btnAuth.innerHTML = original;
            }
        });
    }
})();
</script>
@stop
