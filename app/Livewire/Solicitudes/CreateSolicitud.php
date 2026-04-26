<?php

namespace App\Http\Livewire\Admin\Solicitudes;

use App\Models\Cliente;
use App\Models\CuentaCliente;
use App\Models\Solicitud;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CreateSolicitud extends Component
{
    public $clientes;

    public $cuentasCliente; // Variable correctamente nombrada

    public $cliente_id;

    public $cuenta_cliente_id;
    // Otros campos...

    public function mount()
    {
        // Recuperar todos los clientes con sus relaciones necesarias
        $this->clientes = Cliente::with(['persona', 'direcciones'])->get();

        // Recuperar todas las cuentas cliente
        $this->cuentasCliente = CuentaCliente::all();
    }

    public function submit()
    {
        $this->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'cuenta_cliente_id' => 'required|exists:cuentas_cliente,id',
            // Otros campos de validación...
        ]);

        try {
            $solicitud = Solicitud::create([
                'cliente_id' => $this->cliente_id,
                'cuenta_cliente_id' => $this->cuenta_cliente_id,
                // Otros campos...
            ]);

            Log::info('Solicitud creada: ', $solicitud->toArray());

            session()->flash('success', 'Solicitud creada exitosamente.');

            // Opcional: Resetear campos o redirigir
            return redirect()->route('admin.solicitudes.index');
        } catch (\Exception $e) {
            Log::error('Error al crear solicitud: '.$e->getMessage());
            session()->flash('error', 'Error al crear la solicitud.');
        }
    }

    public function render()
    {
        return view('livewire.admin.solicitudes.create-solicitud');
    }
}
