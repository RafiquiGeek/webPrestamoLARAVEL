<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

class CuotaConvenioDTO
{
    public $id;
    public $id_original;
    public $convenio_id;
    public $numero_cuota;
    public $numero;
    public $monto_cuota;
    public $monto;
    public $fecha_vencimiento;
    public $fecha_pago;
    public $monto_pagado;
    public $estado;
    public $cantidad_mora;
    public $moras;
    public $prestamo_id;
    public $prestamo;
    public $convenio;
    public $es_cuota_convenio;
    public $tipo_convenio;
    public $cuotas_prestamo;

    public function __construct($convenio, $cuotaConvenio = null)
    {
        if ($cuotaConvenio && $convenio->tipo === 'cuotas') {
            // Para convenios tipo cuotas
            $this->id = $cuotaConvenio->id;
            $this->id_original = $cuotaConvenio->id;
            $this->convenio_id = $convenio->id;
            $this->numero_cuota = $cuotaConvenio->numero_cuota;
            $this->numero = $cuotaConvenio->numero_cuota;
            $this->monto_cuota = $cuotaConvenio->monto_cuota;
            $this->monto = $cuotaConvenio->monto_cuota;
            $this->fecha_vencimiento = $cuotaConvenio->fecha_vencimiento;
            $this->fecha_pago = $cuotaConvenio->fecha_vencimiento;
            $this->monto_pagado = $cuotaConvenio->monto_pagado ?? 0;
            $this->estado = $cuotaConvenio->estado;
            $this->cantidad_mora = 0;
            $this->moras = collect();
            $this->prestamo_id = $convenio->prestamo_id;
            $this->prestamo = $convenio->prestamo;
            $this->convenio = $convenio;
            $this->es_cuota_convenio = true;
            $this->tipo_convenio = 'cuotas';
            $this->cuotas_prestamo = collect();
        } else {
            // Para convenios tipo flexible
            $this->id = null;
            $this->id_original = $convenio->id;
            $this->convenio_id = $convenio->id;
            $this->numero_cuota = 1;
            $this->numero = 1;
            $this->monto_cuota = $convenio->total_convenio;
            $this->monto = $convenio->total_convenio;
            $this->fecha_vencimiento = $convenio->fecha_inicio ?? $convenio->created_at;
            $this->fecha_pago = $this->fecha_vencimiento;
            $this->monto_pagado = 0;
            $this->estado = 3; // VENCIDO
            $this->cantidad_mora = 0;
            $this->moras = collect();
            $this->prestamo_id = $convenio->prestamo_id;
            $this->prestamo = $convenio->prestamo;
            $this->convenio = $convenio;
            $this->es_cuota_convenio = true;
            $this->tipo_convenio = 'flexible';
            $this->cuotas_prestamo = $convenio->prestamo && $convenio->prestamo->cuotas 
                ? $convenio->prestamo->cuotas 
                : collect();
        }
    }

    /**
     * Método necesario para que merge() funcione correctamente
     */
    public function getKey()
    {
        return $this->id_original;
    }

    /**
     * Método mágico para acceder a propiedades como objeto
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        return null;
    }

    /**
     * Método mágico para isset()
     */
    public function __isset($name)
    {
        return property_exists($this, $name);
    }

    /**
     * Método mágico para setear propiedades
     */
    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }
}