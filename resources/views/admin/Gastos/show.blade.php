@extends('layouts.admin')

@section('title', 'Detalles del Gasto')

@section('content')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Detalles del Gasto</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.gastos.index') }}">Gastos</a></li>
                        <li class="breadcrumb-item active">Detalles</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <!-- Información Principal -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-receipt mr-1"></i>
                                Información del Gasto
                            </h3>
                            <div class="card-tools">
                                <a href="{{ route('admin.gastos.edit', $gasto->id) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit mr-1"></i> Editar
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Categoría:</strong>
                                    <p class="text-muted">
                                        <span class="badge" style="background-color: {{ $gasto->categoria->color }}; color: white;">
                                            {{ $gasto->categoria->nombre }}
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Fecha del Gasto:</strong>
                                    <p class="text-muted">{{ $gasto->fecha_gasto->format('d/m/Y') }}</p>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Concepto:</strong>
                                    <p class="text-muted">{{ $gasto->concepto }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Monto:</strong>
                                    <p class="text-muted">
                                        <span class="badge badge-success" style="font-size: 16px;">
                                            S/ {{ number_format($gasto->monto, 2) }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            @if($gasto->descripcion)
                            <div class="row">
                                <div class="col-md-12">
                                    <strong>Descripción:</strong>
                                    <p class="text-muted">{{ $gasto->descripcion }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Información del Beneficiario -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user mr-1"></i>
                                Información del Beneficiario
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Tipo de Documento:</strong>
                                    <p class="text-muted">{{ $gasto->tipo_documento_texto }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Número de Documento:</strong>
                                    <p class="text-muted">{{ $gasto->documento_identidad }}</p>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <strong>Beneficiario:</strong>
                                    <p class="text-muted">{{ $gasto->beneficiario_completo }}</p>
                                </div>
                            </div>

                            @if($gasto->tipo_documento === 'RUC' && $gasto->razon_social)
                            <div class="row">
                                <div class="col-md-12">
                                    <strong>Razón Social:</strong>
                                    <p class="text-muted">{{ $gasto->razon_social }}</p>
                                </div>
                            </div>
                            @else
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Nombres:</strong>
                                    <p class="text-muted">{{ $gasto->nombres }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Apellidos:</strong>
                                    <p class="text-muted">{{ $gasto->apellidos }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Información del Comprobante -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-file-invoice mr-1"></i>
                                Comprobante de Pago
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Tipo de Comprobante:</strong>
                                    <p class="text-muted">{{ $gasto->tipo_comprobante_texto }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Número de Comprobante:</strong>
                                    <p class="text-muted">
                                        @if($gasto->tipo_comprobante === 'sin_documento')
                                            <span class="text-muted">Sin documento</span>
                                        @else
                                            {{ $gasto->comprobante_completo }}
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if($gasto->serie_comprobante)
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Serie:</strong>
                                    <p class="text-muted">{{ $gasto->serie_comprobante }}</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Número:</strong>
                                    <p class="text-muted">{{ $gasto->numero_comprobante }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    @if($gasto->observaciones)
                    <!-- Observaciones -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-comment mr-1"></i>
                                Observaciones
                            </h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">{{ $gasto->observaciones }}</p>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="col-md-4">
                    <!-- Resumen -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle mr-1"></i>
                                Resumen
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="info-box bg-light">
                                <span class="info-box-icon" style="background-color: {{ $gasto->categoria->color }};">
                                    <i class="fas fa-receipt"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Monto Total</span>
                                    <span class="info-box-number">S/ {{ number_format($gasto->monto, 2) }}</span>
                                </div>
                            </div>

                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-calendar-alt"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Fecha del Gasto</span>
                                    <span class="info-box-number">{{ $gasto->fecha_gasto->format('d/m/Y') }}</span>
                                </div>
                            </div>

                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-tags"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Categoría</span>
                                    <span class="info-box-number" style="font-size: 14px;">{{ $gasto->categoria->nombre }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información del Registro -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user-cog mr-1"></i>
                                Información del Registro
                            </h3>
                        </div>
                        <div class="card-body">
                            <p><strong>Registrado por:</strong><br>
                            <span class="text-muted">{{ $gasto->usuario->name ?? 'N/A' }}</span></p>
                            
                            <p><strong>Fecha de registro:</strong><br>
                            <span class="text-muted">{{ $gasto->created_at->format('d/m/Y H:i:s') }}</span></p>
                            
                            @if($gasto->updated_at != $gasto->created_at)
                                <p><strong>Última actualización:</strong><br>
                                <span class="text-muted">{{ $gasto->updated_at->format('d/m/Y H:i:s') }}</span></p>
                            @endif
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-cogs mr-1"></i>
                                Acciones
                            </h3>
                        </div>
                        <div class="card-body">
                            <a href="{{ route('admin.gastos.edit', $gasto->id) }}" 
                               class="btn btn-warning btn-block">
                                <i class="fas fa-edit mr-1"></i> Editar Gasto
                            </a>
                            
                            <a href="{{ route('admin.gastos.index', ['categoria_id' => $gasto->categoria_gasto_id]) }}" 
                               class="btn btn-info btn-block">
                                <i class="fas fa-list mr-1"></i> Ver Gastos de esta Categoría
                            </a>
                            
                            <button type="button" class="btn btn-danger btn-block" 
                                    onclick="confirmarEliminacion({{ $gasto->id }})">
                                <i class="fas fa-trash mr-1"></i> Eliminar Gasto
                            </button>
                            
                            <a href="{{ route('admin.gastos.index') }}" 
                               class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-arrow-left mr-1"></i> Volver a la Lista
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar este gasto?</p>
                <div class="alert alert-warning">
                    <strong>Concepto:</strong> {{ $gasto->concepto }}<br>
                    <strong>Monto:</strong> S/ {{ number_format($gasto->monto, 2) }}<br>
                    <strong>Fecha:</strong> {{ $gasto->fecha_gasto->format('d/m/Y') }}
                </div>
                <p class="text-danger"><small>Esta acción no se puede deshacer.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form id="formEliminar" method="POST" action="{{ route('admin.gastos.destroy', $gasto->id) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash mr-1"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function confirmarEliminacion(gastoId) {
    $('#modalEliminar').modal('show');
}
</script>
@endsection