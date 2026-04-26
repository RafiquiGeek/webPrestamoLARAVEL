<?php

namespace App\Livewire\Prestamos\RegistrarPago;

use App\Models\Cuota;
use Livewire\Component;

class ShowTablaCuotas extends Component
{
    public $cuota;

    public $solicitud_id;

    public $rows = [];

    public $subtotal = 0;

    public $totalInteres = 0;

    public $isFirstRow = false;

    public $isLastRow = false;

    public $baseRowId;

    public $lastDeletedCuotaId = null;

    public function mount(Cuota $cuota)
    {
        $this->solicitud_id = $cuota->solicitud_id;
        $this->baseRowId = $cuota->id;
        $query1 = Cuota::where('solicitud_id', $cuota->solicitud_id)
            ->where('statusPago', 0)
            ->where('fecha', '<=', now())
            ->get();
        $query2 = Cuota::where('solicitud_id', $cuota->solicitud_id)
            ->where('statusPago', 0)
            ->where('numero', 1)
            ->get();

        if ($query1->isNotEmpty()) {
            $cuotas = $query1;
        } elseif ($query2->isNotEmpty()) {
            $cuotas = $query2;
        } else {
            $cuotas = collect(); // Colección vacía si no hay cuotas disponibles
        }

        foreach ($cuotas as $cuota) {
            $this->rows[] = [
                'column1' => $cuota->id,
                'column2' => $cuota->interes,
                'column3' => $cuota->fecha,
                'column4' => $cuota->cuota,
                'column5' => $cuota->numero,
            ];

            $this->cuota = $cuota;
        }

        $firstCuota = Cuota::where('solicitud_id', $this->solicitud_id)
            ->where('statusPago', '!=', 0)
            ->oldest()
            ->first();

        if ($firstCuota !== null && isset($cuota->id)) {
            $this->isFirstRow = $cuota->id === $firstCuota->id;
        }

        $lastCuota = Cuota::where('solicitud_id', $this->solicitud_id)->latest()->first();
        if ($lastCuota !== null && isset($cuota->id)) {
            $this->isLastRow = $cuota->id === $lastCuota->id;
        }

        $this->totalInteres = array_sum(array_column($this->rows, 'column2'));
        $this->subtotal = array_sum(array_column($this->rows, 'column4'));
    }

    public function render()
    {
        return view('livewire.prestamos.registrar-pago.show-tabla-cuotas', [
            'rows' => $this->rows,
            'totalInteres' => $this->totalInteres,
            'subtotal' => $this->subtotal,
        ]);
    }

    public function agregarCuota()
    {
        $nextCuotaId = $this->lastDeletedCuotaId ? $this->lastDeletedCuotaId : $this->cuota->id + 1;
        $nextCuota = Cuota::where('solicitud_id', $this->solicitud_id)->find($nextCuotaId);

        if ($nextCuota) {
            $this->cuota = $nextCuota;

            $this->rows[] = [
                'cuota_id' => $nextCuota->id,
                'column1' => $nextCuota->id,
                'column2' => $nextCuota->interes,
                'column3' => $nextCuota->fecha,
                'column4' => $nextCuota->cuota,
                'column5' => $nextCuota->numero,
            ];

            $lastCuota = Cuota::where('solicitud_id', $this->solicitud_id)->latest()->first();
            if ($lastCuota !== null) {
                $this->isLastRow = $nextCuota->id === $lastCuota->id;
            }

            $this->totalInteres = array_sum(array_column($this->rows, 'column2'));
            $this->subtotal = array_sum(array_column($this->rows, 'column4'));

            $this->dispatch('actualizarSubtotal', $this->subtotal);
            $this->lastDeletedCuotaId = null;
        }
    }

    public function eliminarCuota($id)
    {
        $cuota = Cuota::where('solicitud_id', $this->solicitud_id)->find($id);

        if ($cuota) {
            $this->lastDeletedCuotaId = $id;
            $this->rows = array_filter($this->rows, function ($row) use ($id) {
                return $row['column1'] !== $id;
            });

            $this->subtotal = array_sum(array_column($this->rows, 'column4'));
            $this->totalInteres = array_sum(array_column($this->rows, 'column2'));

            $this->dispatch('actualizarSubtotal', $this->subtotal);
        }
    }
}
