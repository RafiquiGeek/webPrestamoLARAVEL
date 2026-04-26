<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-history me-2 text-success"></i>Historial de Pagos del Convenio Flexible
                </h6>
                @if($convenio->estado === \App\Enums\ConvenioEstado::ACTIVO && $convenio->saldo_pendiente > 0)
                    <a href="{{ route('admin.convenios.flexible.pagar.form', $convenio->id) }}"
                       class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i>Registrar Pago
                    </a>
                @endif
            </div>
            <div class="card-body">
                @if($convenio->pagosFlexibles->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="small">#</th>
                                    <th class="small">Fecha</th>
                                    <th class="small text-end">Monto</th>
                                    <th class="small">Método de Pago</th>
                                    <th class="small">Registrado por</th>
                                    <th class="small">Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($convenio->pagosFlexibles as $index => $pago)
                                    <tr>
                                        <td class="small">
                                            <span class="badge bg-primary">{{ $convenio->pagosFlexibles->count() - $index }}</span>
                                        </td>
                                        <td class="small">
                                            <i class="fas fa-calendar-alt text-primary me-1"></i>
                                            {{ $pago->fecha_pago->format('d/m/Y') }}
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-success">
                                                S/. {{ number_format($pago->monto, 2) }}
                                            </span>
                                        </td>
                                        <td class="small">
                                            <span class="badge bg-info text-white">
                                                {{ $pago->metodo_pago ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="small">
                                            {{ optional($pago->usuario)->codigo ?? 'N/A' }}
                                        </td>
                                        <td class="small text-muted">
                                            {{ $pago->observaciones ?? '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2" class="text-end small">TOTAL PAGADO:</th>
                                    <th class="text-end">
                                        <span class="text-success fw-bold">
                                            S/. {{ number_format($convenio->monto_total_pagado, 2) }}
                                        </span>
                                    </th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3" style="opacity: 0.25;"></i>
                        <h6>No hay pagos registrados</h6>
                        <p class="small mb-3">Este convenio flexible aún no tiene pagos registrados.</p>
                        @if($convenio->estado === \App\Enums\ConvenioEstado::ACTIVO)
                            <a href="{{ route('admin.convenios.flexible.pagar.form', $convenio->id) }}"
                               class="btn btn-success btn-sm">
                                <i class="fas fa-plus me-1"></i>Registrar Primer Pago
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Información Adicional del Convenio Flexible -->
<div class="row mt-3">
    <div class="col-12">
        <div class="alert alert-info">
            <h6 class="alert-heading">
                <i class="fas fa-info-circle me-2"></i>Acerca de este Convenio Flexible
            </h6>
            <p class="mb-0 small">
                Este es un convenio de tipo <strong>Monto Total Flexible</strong>. No tiene cuotas predefinidas ni fechas de vencimiento.
                El cliente puede realizar pagos cuando lo desee y por el monto que pueda. No se generan moras automáticas.
            </p>
            @if($convenio->observaciones)
                <hr>
                <p class="mb-0 small">
                    <strong>Observaciones:</strong> {{ $convenio->observaciones }}
                </p>
            @endif
        </div>
    </div>
</div>
