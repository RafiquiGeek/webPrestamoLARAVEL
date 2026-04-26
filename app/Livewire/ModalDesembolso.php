<?php

namespace App\Livewire;

use App\Models\MetodoDePago;
use App\Models\Prestamo;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ModalDesembolso extends Component
{
    use WithFileUploads;

    // Propiedades del modal
    public $modalOpen = false;

    public $loading = false;

    // Propiedad para debugging
    public $debug = true;

    // Datos del préstamo
    public $prestamo;

    public $prestamoId;

    public $monto;

    // Datos del formulario
    public $fecha;

    public $userId;

    public $metodoPagoId = 1; // Efectivo por defecto

    public $nroOperacion;

    public $imagenDeposito;

    public $tieneComprobante = 1; // Sí por defecto

    // Datos auxiliares
    public $usuarios;

    public $metodosPago;

    // Propiedades para validación
    protected $rules = [
        'fecha' => 'required|date|before_or_equal:today',
        'userId' => 'required|exists:users,id',
        'metodoPagoId' => 'required|exists:metodos_de_pago,id',
        'nroOperacion' => 'nullable|string|max:50',
        'imagenDeposito' => 'nullable|image|max:2048',
        'tieneComprobante' => 'required|in:0,1',
    ];

    protected $messages = [
        'fecha.required' => 'La fecha de desembolso es obligatoria',
        'fecha.before_or_equal' => 'La fecha no puede ser futura',
        'userId.required' => 'Debe seleccionar un usuario',
        'metodoPagoId.required' => 'Debe seleccionar un método de pago',
        'imagenDeposito.image' => 'El archivo debe ser una imagen',
        'imagenDeposito.max' => 'La imagen no debe superar 2MB',
    ];

    public function mount()
    {
        $this->usuarios = User::where('status', 1)->orderBy('name')->get();
        $this->metodosPago = MetodoDePago::where('status', 1)->get();
        $this->userId = auth()->id();
        $this->fecha = now()->format('Y-m-d');
    }

    public function updated($property)
    {
        // Validación en tiempo real
        if (in_array($property, ['fecha', 'userId', 'metodoPagoId'])) {
            $this->validateOnly($property);
        }

        // Lógica para mostrar/ocultar número de operación
        if ($property === 'metodoPagoId') {
            if ($this->metodoPagoId != 1) { // No es efectivo
                $this->addError('nroOperacion', ''); // Reset error
            } else {
                $this->nroOperacion = null;
            }
        }
    }

    #[On('abrir-modal-desembolso')]
    public function abrirModal($prestamoId)
    {
        try {
            Log::info('ModalDesembolso: Recibido evento abrir-modal-desembolso para préstamo: '.$prestamoId);

            $this->prestamo = Prestamo::with('cliente.persona')->findOrFail($prestamoId);
            $this->prestamoId = $prestamoId;
            $this->monto = $this->prestamo->cantidad_solicitada;
            $this->modalOpen = true;

            Log::info('ModalDesembolso: Modal abierto exitosamente');

            // Reset form
            $this->resetForm();

            // Emitir evento para abrir modal en frontend
            $this->dispatch('modal-opened');

            // Debug alert
            $this->dispatch('show-success', 'Modal de desembolso abierto para préstamo: '.$prestamoId);

        } catch (\Exception $e) {
            Log::error('Error abriendo modal desembolso: '.$e->getMessage());
            $this->dispatch('show-error', 'No se pudo cargar la información del préstamo: '.$e->getMessage());
        }
    }

    public function cerrarModal()
    {
        $this->modalOpen = false;
        $this->resetForm();
        $this->dispatch('modal-closed');
    }

    public function resetForm()
    {
        $this->resetErrorBag();
        $this->userId = auth()->id();
        $this->fecha = now()->format('Y-m-d');
        $this->metodoPagoId = 1;
        $this->nroOperacion = null;
        $this->imagenDeposito = null;
        $this->tieneComprobante = 1;
    }

    public function confirmarDesembolso()
    {
        $this->loading = true;

        try {
            // Validar formulario
            $validatedData = $this->validate();

            // Validaciones adicionales
            if ($this->metodoPagoId != 1 && empty($this->nroOperacion)) {
                $this->addError('nroOperacion', 'El número de operación es requerido para este método de pago');
                $this->loading = false;

                return;
            }

            // Procesar imagen si existe
            $imagenPath = null;
            if ($this->imagenDeposito) {
                $imagenPath = $this->imagenDeposito->store('depositos', 'public');
            }

            // Preparar datos para enviar
            $datosDesembolso = [
                'prestamo_id' => $this->prestamoId,
                'monto' => $this->monto,
                'fecha' => $this->fecha,
                'user_id' => $this->userId,
                'metodo_pago_id' => $this->metodoPagoId,
                'nro_operacion' => $this->nroOperacion,
                'imagen_deposito' => $imagenPath,
                'tiene_comprobante' => $this->tieneComprobante,
            ];

            // Por ahora, simular éxito y delegar el procesamiento al componente padre
            $this->dispatch('show-success', 'Modal configurado correctamente. Proceso de desembolso pendiente de implementación.');
            $this->dispatch('prestamo-desembolsado', $this->prestamoId);
            $this->cerrarModal();

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Los errores de validación se muestran automáticamente
        } catch (\Exception $e) {
            $this->dispatch('show-error', 'Error al procesar el desembolso: '.$e->getMessage());
            Log::error('Error en desembolso: '.$e->getMessage());
        }

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.modal-desembolso');
    }
}
