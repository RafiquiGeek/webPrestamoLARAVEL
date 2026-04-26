<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mora extends Model
{
    protected $casts = [
        'status' => 'integer', // Convierte automáticamente a entero
    ];

    protected $fillable = ['monto', 'status'];

    public function getStatusTextAttribute()
    {
        return $this->attributes['status'] ? 'Activo' : 'Inactivo';
    }

    public function getMontoFormateadoAttribute()
    {
        return number_format($this->monto, 2).'%';
    }

    public function prestamos()
    {
        return $this->hasMany(Prestamo::class);
    }

    public function historial()
    {
        return $this->hasMany(MoraHistory::class);
    }
}
