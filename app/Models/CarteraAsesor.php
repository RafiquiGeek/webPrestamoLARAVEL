<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarteraAsesor extends Model
{
    use HasFactory;

    protected $table = 'carteras_asesor';

    protected $fillable = [
        'asesor_id',
        'prestamo_id',
        'fecha_registro',
        'estado',
    ];

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'asesor_id'); // Cambia 'analista_id' por el nombre real de la columna
    }
}
