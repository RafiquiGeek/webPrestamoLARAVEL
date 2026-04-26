<?php

namespace App\Livewire\Debug;

use App\Models\Cliente;
use App\Models\Prestamo;
use Livewire\Component;

class Cliente987Debug extends Component
{
    public $cliente;
    public $prestamo;
    public $sucursalCliente;
    public $sucursalPrestamo;
    public $sucursalesCoinciden;

    public function mount()
    {
        $this->cargarDatos();
    }

    public function cargarDatos()
    {
        // Cargar cliente 987
        $this->cliente = Cliente::with(['persona.direcciones.sucursal'])->find(987);

        // Cargar préstamo 2073
        $this->prestamo = Prestamo::with(['cliente.persona.direcciones.sucursal'])->find(2073);

        // Obtener sucursales
        $this->sucursalCliente = $this->obtenerSucursalCliente($this->cliente);
        $this->sucursalPrestamo = $this->obtenerSucursalPrestamo($this->prestamo);

        // Verificar si coinciden
        $this->sucursalesCoinciden = $this->sucursalCliente && $this->sucursalPrestamo &&
                                   $this->sucursalCliente === $this->sucursalPrestamo;

        // Log para consola del navegador
        $this->dispatch('debugDataLoaded', [
            'cliente' => $this->cliente ? $this->cliente->toArray() : null,
            'prestamo' => $this->prestamo ? $this->prestamo->toArray() : null,
            'sucursalCliente' => $this->sucursalCliente,
            'sucursalPrestamo' => $this->sucursalPrestamo,
            'sucursalesCoinciden' => $this->sucursalesCoinciden
        ]);
    }

    private function obtenerSucursalCliente($cliente)
    {
        if (!$cliente || !$cliente->persona) {
            return null;
        }

        $direccion = $cliente->persona->direcciones()->with('sucursal')->first();
        return $direccion && $direccion->sucursal ? $direccion->sucursal->sucursal : null;
    }

    private function obtenerSucursalPrestamo($prestamo)
    {
        if (!$prestamo || !$prestamo->cliente || !$prestamo->cliente->persona) {
            return null;
        }

        $direccion = $prestamo->cliente->persona->direcciones()->with('sucursal')->first();
        return $direccion && $direccion->sucursal ? $direccion->sucursal->sucursal : null;
    }

    public function render()
    {
        return view('livewire.debug.cliente987-debug');
    }
}