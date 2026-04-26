<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarteraAnalista extends Model
{
    use HasFactory;

    protected $table = 'carteras_analista';

    protected $fillable = [
        'analista_id',
        'prestamo_id',
        'fecha_registro',
        'estado',
    ];

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function analista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analista_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'analista_id'); // Cambia 'analista_id' por el nombre real de la columna
    }
}
