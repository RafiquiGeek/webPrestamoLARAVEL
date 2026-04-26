<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoCliente extends Model
{
    use HasFactory;

    protected $table = 'documentos_cliente';

    protected $fillable = [
        'tipo_documento',
        'cliente_id',
        'ruta_archivo',
    ];

    /**
     * Obtiene el cliente al que le pertenece el documento
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
