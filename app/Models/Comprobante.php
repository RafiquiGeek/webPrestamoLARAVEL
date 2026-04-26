<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comprobante extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'prestamo_id',
        'cuota_id',
        'tipo_comprobante',
        'serie',
        'numero',
        'fecha_emision',
        'moneda',
        'estado',
        'items',
        'total',
        'cdr_zip',
        'hash',
        'xml_content',
        'observaciones',
        'mensaje_error',
        'codigo_error',
        'motivo_anulacion',
        'fecha_anulacion',
        'motivo_nota',
        'comprobante_referencia_id',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'fecha_anulacion' => 'datetime',
        'items' => 'array',
        'total' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(Cuota::class);
    }

    /**
     * Reintentos de envío a SUNAT
     */
    public function reintentos(): HasMany
    {
        return $this->hasMany(ComprobanteReintento::class);
    }

    /**
     * Notas de crédito asociadas a este comprobante
     */
    public function notasCredito(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'comprobante_referencia_id');
    }

    /**
     * Comprobante original (si este es una nota de crédito/débito)
     */
    public function comprobanteReferencia(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class, 'comprobante_referencia_id');
    }

    public function getTipoComprobanteNombreAttribute()
    {
        return match ($this->tipo_comprobante) {
            '01' => 'Factura',
            '03' => 'Boleta de Venta',
            '07' => 'Nota de Crédito',
            '08' => 'Nota de Débito',
            default => 'Desconocido'
        };
    }

    public function getNumeroCompletoAttribute()
    {
        return $this->serie.'-'.str_pad($this->numero, 6, '0', STR_PAD_LEFT);
    }

    public function getEstadoBadgeAttribute()
    {
        return match ($this->estado) {
            'PENDIENTE' => 'warning',
            'ENVIADO' => 'success',
            'ERROR' => 'danger',
            default => 'secondary'
        };
    }
}
