<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarteraJcc extends Model
{
    use HasFactory;

    protected $table = 'carteras_jcc';

    protected $fillable = [
        'jcc_id',
        'prestamo_id',
        'fecha_registro',
        'estado',
    ];

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function jcc(): BelongsTo
    {
        return $this->belongsTo(User::class, 'jcc_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'jcc_id'); // Cambia 'analista_id' por el nombre real de la columna
    }
}
