<div>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="pl-4"><i class="fas fa-university mr-1"></i>Banco</th>
                            <th><i class="fas fa-hashtag mr-1"></i>Nro de Cuenta</th>
                            <th><i class="fas fa-key mr-1"></i>Código</th>
                            <th class="text-center" width="180"><i class="fas fa-tools mr-1"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cuentas as $cuenta)
                            <tr>
                                <td class="pl-4 align-middle font-weight-medium">{{ $cuenta->entidadBancaria->banco }}</td>
                                <td class="align-middle">{{ $cuenta->nro_cuenta }}</td>
                                <td class="align-middle">{{ $cuenta->codigo }}</td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.cuentas.edit', $cuenta->id) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                data-toggle="modal" 
                                                data-target="#deleteModal" 
                                                data-id="{{ $cuenta->id }}"
                                                data-name="{{ $cuenta->codigo }} - {{ $cuenta->entidadBancaria->banco }}"
                                                title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle mr-1"></i>No hay cuentas registradas actualmente
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(method_exists($cuentas, 'hasPages') && $cuentas->hasPages())
            <div class="card-footer">
                {{ $cuentas->links() }}
            </div>
        @endif
    </div>
</div>