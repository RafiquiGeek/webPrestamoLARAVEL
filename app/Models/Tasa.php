<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tasa extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_tasa',
        'valor',
        'status',
    ];

    public function plazos()
    {
        return $this->belongsToMany(Plazo::class, 'plazos_by_tasas', 'tasa_id', 'plazo_id');
    }

    public function history()
    {
        return $this->hasMany(TasaHistory::class);
    }
}
