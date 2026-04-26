<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SireHistorial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sire_historial';

    protected $fillable = [
        'tipo_comprobante', 'serie', 'numero', 'fecha_emision', 'moneda', 'total',
        'cliente_tipo_doc', 'cliente_numero_doc', 'cliente_razon_social', 'estado',
        'fecha_envio', 'fecha_respuesta', 'sunat_codigo', 'sunat_mensaje',
        'xml_generado', 'xml_firmado', 'hash_xml', 'cdr_zip', 'cdr_hash',
        'sunat_response', 'origen_sistema', 'metadata', 'intentos',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_envio' => 'datetime',
        'fecha_respuesta' => 'datetime',
        'total' => 'decimal:2',
    ];

    public static function ultimos($cantidad = 20)
    {
        return static::orderBy('created_at', 'desc')->take($cantidad)->get();
    }
}
