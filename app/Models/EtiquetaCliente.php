<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtiquetaCliente extends Model
{
    use HasFactory;

    protected $table = 'etiquetas_cliente';

    protected $fillable = [
        'cliente_id',
        'prestamo_id',
        'etiqueta_id',
        'observacion',
    ];

    /**
     * Obtiene el cliente al que le pertenece el registro
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Obtiene la etiqueta a la que pertenece el registro
     */
    public function etiqueta(): BelongsTo
    {
        return $this->belongsTo(Etiqueta::class);
    }

    /**
     * Obtiene el préstamo al que pertenece el registro
     */
    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }
}
