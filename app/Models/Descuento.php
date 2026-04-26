<?php

namespace App\Models;

use App\Enums\DescuentoEstado;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; // Importar el Enum correcto

class Descuento extends Model
{
    use HasFactory;

    protected $fillable = [
        'prestamo_id',
        'monto',
        'estado',
    ];

    protected $casts = [
        'estado' => DescuentoEstado::class,
    ];

    // Definición de constantes para los estados
    const ESTADO_PENDIENTE = DescuentoEstado::PENDIENTE;

    const ESTADO_APLICADO = DescuentoEstado::APLICADO;
}
