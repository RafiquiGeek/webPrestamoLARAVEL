@extends('layouts.admin')
@section('title', 'Gestión de Usuarios')
@section('content_header')
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">
            <i class="fas fa-users text-primary mr-2"></i>
            Gestión de Usuarios
          </h1>
          <p class="text-muted mb-0">Administra usuarios del sistema, roles y permisos</p>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}"><i class="fas fa-home"></i> Dashboard</a></li>
            <li class="breadcrumb-item active">Usuarios</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
@stop

@section('content')
<div class="container-fluid">
  <!-- Alertas mejoradas -->
  @if (session('info') || session('success'))
      <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
          <i class="fas fa-check-circle mr-2"></i>
          <strong>¡Éxito!</strong> {{ session('info') ?? session('success') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
          </button>
      </div>
  @endif

  @if (session('error'))
      <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
          <i class="fas fa-exclamation-triangle mr-2"></i>
          <strong>¡Error!</strong> {{ session('error') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
          </button>
      </div>
  @endif

  <!-- Tarjeta principal con diseño mejorado -->
  <div class="card card-outline card-primary shadow">
      <div class="card-header bg-white">
          <div class="row align-items-center">
              <div class="col-md-6">
                  <h3 class="card-title mb-0">
                      <i class="fas fa-list mr-2 text-primary"></i>
                      Lista de Usuarios
                  </h3>
                  <p class="text-muted mb-0 mt-1" style="font-size: 0.9em;">
                      Gestiona los usuarios registrados en el sistema
                  </p>
              </div>
              <div class="col-md-6 text-right">
                  <!-- Botón mejorado de nuevo usuario -->
                  <a href="{{ route('admin.usuarios.create') }}" 
                     class="btn btn-primary btn-lg shadow-sm" 
                     style="border-radius: 25px; padding: 8px 24px;">
                      <i class="fas fa-user-plus mr-2"></i>
                      Nuevo Usuario
                  </a>
              </div>
          </div>
      </div>
      
      <!-- Cuerpo de la tarjeta con filtros -->
      <div class="card-body">
          <!-- Barra de búsqueda y filtros mejorada -->
          <div class="row mb-4">
              <div class="col-md-8">
                  <div class="input-group shadow-sm">
                      <div class="input-group-prepend">
                          <span class="input-group-text bg-light border-right-0">
                              <i class="fas fa-search text-muted"></i>
                          </span>
                      </div>
                      <input type="text" 
                             id="search-input"
                             class="form-control border-left-0" 
                             placeholder="Buscar por nombre, DNI, email..." 
                             onkeyup="handleSearch(this.value)"
                             onfocus="this.style.borderColor='#007bff'"
                             onblur="this.style.borderColor='#ced4da'">
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="row">
                      <div class="col-6">
                          <button class="btn btn-outline-secondary btn-block" onclick="clearSearch()">
                              <i class="fas fa-eraser mr-1"></i> Limpiar
                          </button>
                      </div>
                      <div class="col-6">
                          <button class="btn btn-outline-info btn-block" onclick="refreshTable()">
                              <i class="fas fa-sync-alt mr-1"></i> Actualizar
                          </button>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Estadísticas rápidas -->
          <div class="row mb-4">
              <div class="col-md-3">
                  <div class="info-box bg-gradient-success">
                      <span class="info-box-icon"><i class="fas fa-user-check"></i></span>
                      <div class="info-box-content">
                          <span class="info-box-text">Usuarios Activos</span>
                          <span class="info-box-number" id="active-users-count">{{ \App\Models\User::where('status', 1)->count() }}</span>
                      </div>
                  </div>
              </div>
              <div class="col-md-3">
                  <div class="info-box bg-gradient-danger">
                      <span class="info-box-icon"><i class="fas fa-user-times"></i></span>
                      <div class="info-box-content">
                          <span class="info-box-text">Usuarios Inactivos</span>
                          <span class="info-box-number" id="inactive-users-count">{{ \App\Models\User::where('status', 0)->count() }}</span>
                      </div>
                  </div>
              </div>
              <div class="col-md-3">
                  <div class="info-box bg-gradient-info">
                      <span class="info-box-icon"><i class="fas fa-users"></i></span>
                      <div class="info-box-content">
                          <span class="info-box-text">Total Usuarios</span>
                          <span class="info-box-number" id="total-users-count">{{ \App\Models\User::count() }}</span>
                      </div>
                  </div>
              </div>
              <div class="col-md-3">
                  <div class="info-box bg-gradient-warning">
                      <span class="info-box-icon"><i class="fas fa-user-plus"></i></span>
                      <div class="info-box-content">
                          <span class="info-box-text">Nuevos (Este Mes)</span>
                          <span class="info-box-number" id="new-users-count">{{ \App\Models\User::whereMonth('created_at', date('m'))->count() }}</span>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Tabla de usuarios -->
          <div class="table-wrapper">
              @livewire('usuarios.show-usuarios')
          </div>
      </div>
  </div>
</div>
@stop

@section('css')
<style>
    /* Mejoras visuales generales */
    .content-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 0 0 15px 15px;
        margin-bottom: 20px;
    }
    
    .content-header h1 {
        color: white !important;
    }
    
    .content-header .breadcrumb-item a {
        color: rgba(255,255,255,0.8) !important;
    }
    
    .content-header .breadcrumb-item.active {
        color: white !important;
    }

    /* Tarjetas de información */
    .info-box {
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        border-radius: 10px;
        transition: transform 0.3s ease;
    }
    
    .info-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    /* Tabla mejorada */
    .table-wrapper {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    
    .table th {
        font-weight: 600;
        color: #495057;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: none;
        text-transform: uppercase;
        font-size: 0.85em;
        letter-spacing: 0.5px;
    }
    
    .table td {
        vertical-align: middle;
        border-top: 1px solid #f1f3f4;
        padding: 15px 10px;
    }
    
    .table tbody tr {
        transition: all 0.3s ease;
    }
    
    .table tbody tr:hover {
        background-color: #f8f9fa !important;
        transform: scale(1.01);
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    /* Badges mejorados */
    .badge {
        font-size: 90%;
        font-weight: 500;
        padding: 0.5em 0.8em;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-success {
        background: linear-gradient(135deg, #28a745, #20c997) !important;
    }
    
    .badge-danger {
        background: linear-gradient(135deg, #dc3545, #e74c3c) !important;
    }
    
    .badge-secondary {
        background: linear-gradient(135deg, #6c757d, #495057) !important;
    }

    /* Botones mejorados */
    .btn-group .btn {
        margin: 0 2px;
        border-radius: 20px !important;
        padding: 8px 12px;
        transition: all 0.3s ease;
    }
    
    .btn-group .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    .btn-outline-primary:hover {
        background: linear-gradient(135deg, #007bff, #0056b3) !important;
        border-color: #007bff !important;
    }
    
    .btn-outline-danger:hover {
        background: linear-gradient(135deg, #dc3545, #c82333) !important;
        border-color: #dc3545 !important;
    }
    
    .btn-outline-success:hover {
        background: linear-gradient(135deg, #28a745, #218838) !important;
        border-color: #28a745 !important;
    }

    /* Input de búsqueda */
    .input-group {
        border-radius: 25px;
        overflow: hidden;
    }
    
    .input-group .form-control:focus {
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        border-color: #80bdff;
    }

    /* Alertas mejoradas */
    .alert {
        border: none;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    
    .alert-success {
        background: linear-gradient(135deg, #d1edff, #a8e6cf);
        color: #155724;
    }
    
    .alert-danger {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        color: #721c24;
    }

    /* Animaciones */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .card {
        animation: fadeInUp 0.6s ease-out;
    }

    /* Responsive mejoras */
    @media (max-width: 768px) {
        .info-box {
            margin-bottom: 15px;
        }
        
        .table-responsive {
            border-radius: 10px;
        }
        
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
    }
</style>
@stop

@section('js')
<script>
    // Variable para debounce
    let searchTimeout;
    
    // Función para manejar la búsqueda con debounce
    function handleSearch(value) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            Livewire.dispatch('updateSearch', { search: value });
        }, 300); // Esperar 300ms después de que el usuario termine de escribir
    }
    
    // Funciones para los botones de búsqueda
    function clearSearch() {
        document.getElementById('search-input').value = '';
        Livewire.dispatch('updateSearch', { search: '' });
    }
    
    function refreshTable() {
        // Refrescar el componente Livewire
        Livewire.dispatch('refreshTableEvent');
        
        // Actualizar contadores
        fetch('{{ route("admin.usuarios.stats") }}')
            .then(response => response.json())
            .then(data => {
                document.getElementById('active-users-count').textContent = data.active || '0';
                document.getElementById('inactive-users-count').textContent = data.inactive || '0';
                document.getElementById('total-users-count').textContent = data.total || '0';
                document.getElementById('new-users-count').textContent = data.new || '0';
            })
            .catch(error => console.log('Error actualizando estadísticas'));
    }
    
    // Animaciones al cargar
    document.addEventListener('DOMContentLoaded', function() {
        // Efecto de aparición escalonada para las tarjetas de información
        const infoBoxes = document.querySelectorAll('.info-box');
        infoBoxes.forEach((box, index) => {
            setTimeout(() => {
                box.style.opacity = '0';
                box.style.transform = 'translateY(20px)';
                box.style.transition = 'all 0.6s ease';
                
                setTimeout(() => {
                    box.style.opacity = '1';
                    box.style.transform = 'translateY(0)';
                }, 100);
            }, index * 150);
        });
    });
</script>
@stop