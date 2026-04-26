<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gestion extends Model
{
    use HasFactory;

    protected $table = 'gestiones';

    protected $fillable = [
        'prestamo_id',
        'nombre_cliente',
        'fecha',
        'jcc_id',
        'asesor_id',
        'observaciones',
        'estado_id',
        'compromiso_id',
        'compromiso_seguimiento_id',
        'latitud',
        'longitud',
        // Nuevos campos
        'tipo_gestion',
        'monto_cobrado',
        'tiene_pago',
        'tiene_adjuntos',
        'usuario_id',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'tiene_pago' => 'boolean',
        'tiene_adjuntos' => 'boolean',
        'monto_cobrado' => 'decimal:2',
    ];

    protected $appends = [
        'nombre_cliente',
        'dni_cliente',
    ];

    /**
     * Obtiene el préstamo al que le pertenece la gestión
     */
    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    /**
     * Obtiene el estado de la gestión
     */
    public function estadoGestion()
    {
        return $this->belongsTo(EstadoGestion::class, 'estado_id');
    }

    /**
     * Obtiene el cliente asociado a la gestión
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Obtiene el compromiso asociado a la gestión
     */
    public function compromiso()
    {
        return $this->belongsTo(Compromiso::class);
    }

    /**
     * Obtiene el compromiso al que esta gestión da seguimiento
     */
    public function compromisoSeguimiento()
    {
        return $this->belongsTo(Compromiso::class, 'compromiso_seguimiento_id');
    }

    /**
     * Obtiene el pago registrado en esta gestión
     */
    public function pago()
    {
        return $this->hasOne(PagoGestion::class);
    }

    /**
     * Obtiene los adjuntos de esta gestión
     */
    public function adjuntos()
    {
        return $this->hasMany(AdjuntoGestion::class);
    }

    /**
     * Obtiene el asesor (usuario) que registró la gestión
     */
    public function asesor()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    /**
     * Obtiene las coordenadas formateadas
     */
    public function getUbicacionAttribute()
    {
        if ($this->latitud && $this->longitud) {
            return "{$this->latitud}, {$this->longitud}";
        }

        return 'No disponible';
    }

    /**
     * Obtiene la URL de Google Maps
     */
    public function getGoogleMapsUrlAttribute()
    {
        if ($this->latitud && $this->longitud) {
            return "https://www.google.com/maps?q={$this->latitud},{$this->longitud}";
        }

        return null;
    }

    /**
     * Obtiene la URL de OpenStreetMap
     */
    public function getOpenStreetMapUrlAttribute()
    {
        if ($this->latitud && $this->longitud) {
            return "https://www.openstreetmap.org/?mlat={$this->latitud}&mlon={$this->longitud}&zoom=17";
        }

        return null;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Obtiene el nombre completo del cliente
     */
    public function getNombreClienteAttribute(): string
    {
        try {
            return trim($this->prestamo->cliente->persona->nombres . ' ' . $this->prestamo->cliente->persona->ape_pat . ' ' . $this->prestamo->cliente->persona->ape_mat);
        } catch (\Exception $e) {
            return 'Cliente ' . $this->prestamo_id;
        }
    }

    /**
     * Obtiene el DNI del cliente
     */
    public function getDniClienteAttribute(): string
    {
        try {
            return $this->prestamo->cliente->persona->documento ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Obtiene el texto del tipo de gestión
     */
    public function getTipoGestionTextoAttribute(): string
    {
        return $this->tipo_gestion === 'presencial' ? 'Presencial' : 'Virtual';
    }

    /**
     * Scope para gestiones con pago
     */
    public function scopeConPago($query)
    {
        return $query->where('tiene_pago', true);
    }

    /**
     * Scope para gestiones presenciales
     */
    public function scopePresenciales($query)
    {
        return $query->where('tipo_gestion', 'presencial');
    }

    /**
     * Scope para gestiones virtuales
     */
    public function scopeVirtuales($query)
    {
        return $query->where('tipo_gestion', 'virtual');
    }

    /**
     * Scope para gestiones principales (no de seguimiento)
     */
    public function scopePrincipales($query)
    {
        return $query->whereNull('compromiso_seguimiento_id');
    }

    /**
     * Scope para gestiones de seguimiento
     */
    public function scopeSeguimiento($query)
    {
        return $query->whereNotNull('compromiso_seguimiento_id');
    }
}
