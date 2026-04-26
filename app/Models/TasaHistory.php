<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TasaHistory extends Model
{
    use HasFactory;

    protected $table = 'tasas_history';

    protected $fillable = [
        'tasa_id',
        'tipo_tasa_anterior',
        'valor_anterior',
        'status_anterior',
        'tipo_tasa_nuevo',
        'valor_nuevo',
        'status_nuevo',
        'user_id',
        'accion',
    ];

    public function tasa()
    {
        return $this->belongsTo(Tasa::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
