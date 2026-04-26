<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaComision extends Model
{
    use HasFactory;

    protected $table = 'meta_comisiones';

    protected $fillable = [
        'porcentaje_minimo',
        'porcentaje_maximo',
        'monto_comision',
        'nivel',
        'estado',
        'descripcion',
    ];
}
