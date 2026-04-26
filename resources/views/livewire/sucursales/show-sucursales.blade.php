<div>
    <div class="card shadow-lg border-0">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Gestión de Sucursales</h3>
            <a href="{{ route('admin.sucursales.create') }}" class="btn btn-success btn-lg">
                <i class="fa-solid fa-map-location-dot mr-1"></i> Nueva Sucursal
            </a>
        </div>
        
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                <input type="text" wire:keydown="reset_page" wire:model="search" class="form-control w-50" placeholder="Buscar por nombre de sucursal">
            </div>

            <table class="table table-hover table-striped text-center shadow-sm">
                <thead class="bg-secondary text-white">
                    <tr>
                        <th>Sucursal</th>
                        <th>Departamento</th>
                        <th>Provincia</th>
                        <th>Fecha de Creación</th>
                        <th>Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sucursales as $sucursal)
                    <tr>
                        <td>{{ $sucursal->sucursal }}</td>
                        <td>{{ $sucursal->departamento->departamento }}</td>
                        <td>{{ $sucursal->provincia->provincia }}</td>
                        <td>{{ $sucursal->created_at->format('d/m/Y') }}</td>
                        <td>
                            <a href="{{ route('admin.sucursales.edit', $sucursal->id) }}" class="btn btn-sm btn-warning mx-1">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <form action="{{ route('admin.sucursales.destroy', $sucursal->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar esta sucursal?')">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                
            </div>
        </div>
    </div>
</div>