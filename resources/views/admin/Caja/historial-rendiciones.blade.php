@extends('layouts.admin')
@section('title', 'Historial de Rendiciones')
@section('content_header')
   <div class="d-flex justify-content-between align-items-center">
       <h1><i class="fas fa-history mr-2"></i>Historial de Rendiciones</h1>
       <ol class="breadcrumb float-sm-right">
           <li class="breadcrumb-item"><a href="{{ route('admin.caja.index') }}">Caja</a></li>
           <li class="breadcrumb-item active">Historial</li>
       </ol>
   </div>
@stop

@section('content')
<div class="container-fluid">
    <!-- Información General -->
    <div class="account-card">
        <div class="card-header">
            <h3><i class="fas fa-info-circle me-2"></i>Información General</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="info-card">
                        <div class="info-label">Total de Rendiciones</div>
                        <div class="info-value">{{ $rendiciones->total() }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-card">
                        <div class="info-label">Esta Página</div>
                        <div class="info-value">{{ $rendiciones->count() }} registros</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-card">
                        <div class="info-label">Página Actual</div>
                        <div class="info-value">{{ $rendiciones->currentPage() }} de {{ $rendiciones->lastPage() }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-card text-center">
                        <div class="info-label">Acciones</div>
                        <a href="{{ route('admin.caja.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Volver a Caja
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Historial -->
    <div class="account-card">
        <div class="card-header">
            <h3><i class="fas fa-table me-2"></i>Registro de Rendiciones</h3>
        </div>
        <div class="card-body p-0">
            @if($rendiciones->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="80">ID</th>
                                <th>Fecha y Hora</th>
                                <th>Tipo</th>
                                <th>Usuario</th>
                                <th>Rendido Por</th>
                                <th class="text-end">Monto</th>
                                <th class="text-center">PDF</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rendiciones as $rendicion)
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ is_numeric($rendicion->id) ? '#' . $rendicion->id : 'F-' . substr($rendicion->id, -6) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="info-value small">
                                            {{ \Carbon\Carbon::parse($rendicion->fecha_rendicion)->format('d/m/Y') }}
                                        </div>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($rendicion->fecha_rendicion)->format('H:i:s') }}
                                        </small>
                                    </td>
                                    <td>
                                        @php
                                            $tipoConfig = [
                                                'parcial' => ['class' => 'warning', 'text' => 'Parcial'],
                                                'completa' => ['class' => 'success', 'text' => 'Completa'],
                                                'cierre_diario' => ['class' => 'info', 'text' => 'Cierre Diario'],
                                                'R' => ['class' => 'warning', 'text' => 'Rendición'],
                                                'CD' => ['class' => 'info', 'text' => 'Cierre Diario']
                                            ];
                                            $config = $tipoConfig[$rendicion->tipo] ?? ['class' => 'secondary', 'text' => ucfirst($rendicion->tipo)];
                                        @endphp
                                        <span class="badge bg-{{ $config['class'] }}">
                                            {{ $config['text'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="info-label">{{ $rendicion->usuario_codigo ?? 'N/A' }}</div>
                                        <small class="text-muted">{{ $rendicion->usuario_nombre ?? 'Sin nombre' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $rendicion->rendidor_codigo ?? 'Sistema' }}</span>
                                    </td>
                                    <td class="text-end">
                                        @if(isset($rendicion->total_rendido) && $rendicion->total_rendido > 0)
                                            <div class="info-value">S/ {{ number_format($rendicion->total_rendido, 2) }}</div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($rendicion->pdf_path)
                                            @php
                                                $pdfUrl = asset('storage/' . $rendicion->pdf_path);
                                                $pdfExists = file_exists(storage_path('app/public/' . $rendicion->pdf_path));
                                            @endphp
                                            @if($pdfExists)
                                                <a href="{{ $pdfUrl }}" 
                                                   target="_blank" 
                                                   class="btn btn-outline-primary btn-sm"
                                                   title="Ver PDF">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            @else
                                                <span class="text-muted" title="PDF no encontrado">
                                                    <i class="fas fa-file-times"></i>
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($rendiciones->hasPages())
                <div class="card-footer">
                    {{ $rendiciones->links() }}
                </div>
                @endif
            @else
                <!-- Sin registros -->
                <div class="text-center py-5">
                    <i class="fas fa-history fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">Sin Historial</h4>
                    <p class="text-muted">No se encontraron registros de rendiciones en el sistema.</p>
                    <a href="{{ route('admin.caja.index') }}" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i>Volver a Caja
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@stop

@section('css')
<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
/* Estilos consistentes con el módulo principal */
.account-card {
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin-bottom: 1.5rem;
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

.info-card {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 8px;
    margin-bottom: 0.75rem;
    width: 100%;
}

.info-card .info-label {
    font-size: 0.75rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    font-weight: 600;
}

.info-card .info-value {
    font-size: 1rem;
    font-weight: 600;
    color: #1a1a1a;
}

.btn-outline-primary {
    border-color: #005566;
    color: #005566;
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    transition: all 0.2s;
}

.btn-outline-primary:hover {
    background-color: #005566;
    color: #ffffff;
}

.table th {
    font-weight: 600;
    color: #495057;
    font-size: 0.875rem;
}

.badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.35em 0.6em;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}
</style>
@stop