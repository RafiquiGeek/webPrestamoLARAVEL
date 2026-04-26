@extends('layouts.admin')

@section('title', 'Configuración de Comisiones')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="font-weight-bold text-dark"><i class="fas fa-percentage text-primary mr-2"></i> Rangos de Comisiones</h1>
            <p class="text-muted mb-0">Define los umbrales de cumplimiento y bonificaciones por nivel de asesor.</p>
        </div>
        <a href="{{ route('admin.metas.index') }}" class="btn btn-outline-secondary px-4 shadow-sm">
            <i class="fas fa-chevron-left mr-1"></i> Volver al Dashboard
        </a>
    </div>
@stop

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x mr-3"></i>
                <div>
                    <strong class="d-block">¡Operación Exitosa!</strong>
                    {{ session('success') }}
                </div>
            </div>
            <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <form action="{{ route('admin.metas.comisiones.store') }}" method="POST" id="configForm">
        @csrf
        
        <!-- Configuración Global -->
        <div class="card border-0 shadow-sm mb-5 overflow-hidden" style="border-radius: 15px;">
            <div class="card-body p-4 bg-gradient-light">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mr-3 shadow-sm" style="width: 50px; height: 50px;">
                                <i class="fas fa-shield-alt fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="font-weight-bold mb-1">Regla de Protección por Morosidad</h5>
                                <p class="text-muted small mb-0">Define el límite máximo de mora permitido para calificar a cualquier bono.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 mt-3 mt-md-0 text-md-right">
                        <div class="d-inline-block text-left">
                            <label class="small font-weight-bold text-uppercase text-muted mb-1">Umbral de Morosidad</label>
                            <div class="input-group input-group-lg shadow-sm" style="max-width: 250px;">
                                <input type="number" step="0.01" name="umbral_morosidad" value="{{ $config->umbral_morosidad ?? 20 }}" class="form-control font-weight-bold text-primary border-right-0" required>
                                <div class="input-group-append">
                                    <span class="input-group-text bg-white border-left-0 text-muted font-weight-bold">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Niveles y Rangos -->
        <div class="row">
            @foreach($niveles as $nivel)
                @php $hexColor = $nivel->hexColor(); @endphp
                <div class="col-xl-6 mb-4">
                    <div class="card border-0 shadow-sm h-100 level-card" style="border-radius: 15px; border-top: 5px solid {{ $hexColor }} !important;">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
                            <h3 class="card-title font-weight-bold mb-0" style="color: {{ $hexColor }};">
                                <i class="{{ $nivel->icono() }} mr-2"></i> Nivel {{ $nivel->label() }}
                            </h3>
                            <button type="button" class="btn btn-sm btn-outline-primary border-0 rounded-circle add-row" data-nivel="{{ $nivel->value }}" title="Agregar Rango">
                                <i class="fas fa-plus-circle fa-lg"></i>
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="table-{{ $nivel->value }}">
                                    <thead class="bg-light text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                                        <tr>
                                            <th class="pl-4 py-3">Min. Cumpl. (%)</th>
                                            <th class="py-3">Max. Cumpl. (%)</th>
                                            <th class="py-3">Monto Bono (S/)</th>
                                            <th class="pr-4 py-3 text-center" style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="valign-middle">
                                        @php $ranges = $comisiones->get(strtolower($nivel->value), collect()); @endphp
                                        @forelse($ranges as $index => $comision)
                                            <tr class="animate__animated animate__fadeIn">
                                                <td class="pl-4">
                                                    <input type="number" step="0.01" name="comisiones[{{ $nivel->value }}][{{ $index }}][porcentaje_minimo]" value="{{ $comision->porcentaje_minimo }}" class="form-control border-light shadow-none bg-light-soft" required>
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" name="comisiones[{{ $nivel->value }}][{{ $index }}][porcentaje_maximo]" value="{{ $comision->porcentaje_maximo }}" class="form-control border-light shadow-none bg-light-soft" required>
                                                </td>
                                                <td>
                                                    <div class="input-group shadow-none">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text bg-transparent border-light">S/</span>
                                                        </div>
                                                        <input type="number" step="0.01" name="comisiones[{{ $nivel->value }}][{{ $index }}][monto_comision]" value="{{ $comision->monto_comision }}" class="form-control border-light shadow-none bg-light-soft font-weight-bold" required>
                                                    </div>
                                                </td>
                                                <td class="pr-4 text-center">
                                                    <button type="button" class="btn btn-sm btn-link text-danger remove-row" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="empty-state animate__animated animate__fadeIn">
                                                <td class="pl-4"><input type="number" step="0.01" name="comisiones[{{ $nivel->value }}][0][porcentaje_minimo]" value="0" class="form-control border-light bg-light-soft" required></td>
                                                <td><input type="number" step="0.01" name="comisiones[{{ $nivel->value }}][0][porcentaje_maximo]" value="100" class="form-control border-light bg-light-soft" required></td>
                                                <td>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend"><span class="input-group-text bg-transparent border-light">S/</span></div>
                                                        <input type="number" step="0.01" name="comisiones[{{ $nivel->value }}][0][monto_comision]" value="0" class="form-control border-light bg-light-soft font-weight-bold" required>
                                                    </div>
                                                </td>
                                                <td class="pr-4 text-center"><button type="button" class="btn btn-sm btn-link text-danger remove-row"><i class="fas fa-trash-alt"></i></button></td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Botón de Guardado al Final -->
        <div class="border-0 shadow-sm mt-4 p-4" style="border-radius: 15px;">
            <div class="row align-items-center">
                <div class="col-md-8 text-muted d-none d-md-block">
                    <i class="fas fa-info-circle mr-2"></i>
                    <span class="small font-weight-bold shadow-none">Asegúrate de revisar la coherencia de los rangos antes de guardar los cambios en la configuración.</span>
                </div>
                <div class="col-md-4 text-md-right text-center">
                    <button type="submit" class="btn btn-success btn-lg px-5 shadow-sm rounded-pill font-weight-bold w-100 w-md-auto">
                        <i class="fas fa-save mr-2"></i> GUARDAR CONFIGURACIÓN
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
        --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    body { background-color: #f8fafc; }
    
    .bg-light-soft { background-color: #f8fafc; }
    .bg-light-soft:focus { background-color: #fff; border-color: #3b82f6 !important; }
    
    .level-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .level-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important; }
    
    .valign-middle td { vertical-align: middle !important; padding-top: 15px !important; padding-bottom: 15px !important; }
    
    .w-md-auto { width: auto !important; }

    .form-control { border-radius: 8px; }
    .input-group-text { border-radius: 8px 0 0 8px; }
    .form-control + .input-group-append .input-group-text { border-radius: 0 8px 8px 0; }

    .btn-link:hover { text-decoration: none; transform: scale(1.1); }
    
    /* Chrome, Safari, Edge, Opera */
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }

    /* Firefox */
    input[type=number] {
      -moz-appearance: textfield;
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Agregar fila
        $('.add-row').click(function() {
            const nivel = $(this).data('nivel');
            const tbody = $('#table-' + nivel + ' tbody');
            const rowCount = tbody.find('tr').length;
            
            const newRow = `
                <tr class="animate__animated animate__fadeInLeft">
                    <td class="pl-4">
                        <input type="number" step="0.01" name="comisiones[${nivel}][${rowCount}][porcentaje_minimo]" value="0" class="form-control border-light shadow-none bg-light-soft" required>
                    </td>
                    <td>
                        <input type="number" step="0.01" name="comisiones[${nivel}][${rowCount}][porcentaje_maximo]" value="100" class="form-control border-light shadow-none bg-light-soft" required>
                    </td>
                    <td>
                        <div class="input-group shadow-none">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-transparent border-light">S/</span>
                            </div>
                            <input type="number" step="0.01" name="comisiones[${nivel}][${rowCount}][monto_comision]" value="0" class="form-control border-light shadow-none bg-light-soft font-weight-bold" required>
                        </div>
                    </td>
                    <td class="pr-4 text-center">
                        <button type="button" class="btn btn-sm btn-link text-danger remove-row" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>
            `;
            tbody.append(newRow);
        });

        // Eliminar fila con confirmación suave
        $(document).on('click', '.remove-row', function() {
            const row = $(this).closest('tr');
            const tbody = row.closest('tbody');
            
            if (tbody.find('tr').length > 1) {
                row.addClass('animate__animated animate__fadeOutRight');
                setTimeout(() => {
                    row.remove();
                }, 500);
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Acción inválida',
                    text: 'Cada nivel debe tener al menos un rango de comisión definido.',
                    confirmButtonColor: '#3085d6'
                });
            }
        });

        // Animación al guardar
        $('#configForm').on('submit', function() {
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...');
        });
    });
</script>
@stop
