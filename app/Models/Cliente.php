<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'persona_id',
        'observaciones',
        'carga_familiar',
        'ocupacion',
        'ingresos_mensuales',
        'estado',
        'fecha_registro',
        'user_id',
        'created_by',
        'informacion_adicional',
    ];

    protected $casts = [
        'informacion_adicional' => 'array',
        'fecha_registro' => 'datetime',
        'ingresos_mensuales' => 'decimal:2',
        'carga_familiar' => 'integer',
    ];

    /**
     * Asignar automáticamente el `user_id` y `created_by` del usuario autenticado al crear el cliente.
     */
    protected static function booted()
    {
        static::creating(function (self $cliente) {
            try {
                if (empty($cliente->user_id) && auth()->check()) {
                    $cliente->user_id = auth()->id();
                }
                if (empty($cliente->created_by) && auth()->check()) {
                    $cliente->created_by = auth()->id();
                }
            } catch (\Exception $e) {
                // En contextos sin auth (seeds, jobs) no hacemos nada
            }
        });
    }

    /**
     * Obtener la persona a la que corresponde el cliente.
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    /**
     * Obtiene el cónyuge del cliente.
     */
    public function conyuge()
    {
        return $this->hasOne(Conyuge::class);
    }

    /**
     * Obtiene las etiquetas del cliente.
     */
    public function etiquetasCliente(): HasMany
    {
        return $this->hasMany(EtiquetaCliente::class);
    }

    /**
     * Obtiene los documentos (archivos) subidos para cada cliente.
     */
    public function documentosCliente(): HasMany
    {
        return $this->hasMany(DocumentoCliente::class);
    }

    /**
     * Obtiene los datos laborales del cliente.
     */
    public function laborales(): HasMany
    {
        return $this->hasMany(Laboral::class);
    }

    /**
     * Obtiene las cuentas del cliente.
     */
    public function cuentasCliente(): HasMany
    {
        return $this->hasMany(CuentaCliente::class);
    }

    /**
     * Obtiene los préstamos del cliente.
     */
    public function prestamos()
    {
        return $this->hasMany(Prestamo::class);
    }

    /**
     * Obtiene las operaciones del cliente.
     */
    public function operaciones(): HasMany
    {
        return $this->hasMany(Operacion::class);
    }
    /**
     * Obtiene el usuario responsable del cliente (relación directa).
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Obtiene la sucursal del cliente a través de su dirección principal.
     * @deprecated Use sucursalDirecta() instead for direct relationship
     */
    public function sucursal()
    {
        return $this->hasOneThrough(
            Sucursal::class,
            Direccion::class,
            'persona_id', // Foreign key on direcciones table
            'id', // Foreign key on sucursals table
            'persona_id', // Local key on clientes table
            'sucursal_id' // Local key on direcciones table
        )->where('direcciones.estado', 1)->latest('direcciones.created_at');
    }

    /**
     * Obtiene el JCC a través de los préstamos del cliente
     */
    public function jcc()
    {
        return $this->hasOneThrough(
            User::class,
            Prestamo::class,
            'cliente_id', // Foreign key on prestamos table
            'id', // Foreign key on users table
            'id', // Local key on clientes table
            'id' // Local key on prestamos table
        )->whereHas('carterasJcc');
    }

    /**
     * Obtiene el asesor a través de los préstamos del cliente
     */
    public function asesor()
    {
        return $this->hasOneThrough(
            User::class,
            Prestamo::class,
            'cliente_id', // Foreign key on prestamos table
            'id', // Foreign key on users table
            'id', // Local key on clientes table
            'id' // Local key on prestamos table
        )->whereHas('carterasAsesor');
    }

    /**
     * Obtiene el analista a través de los préstamos del cliente
     */
    public function analista()
    {
        return $this->hasOneThrough(
            User::class,
            Prestamo::class,
            'cliente_id', // Foreign key on prestamos table
            'id', // Foreign key on users table
            'id', // Local key on clientes table
            'id' // Local key on prestamos table
        )->whereHas('carterasAnalista');
    }

    /**
     * Obtiene todos los JCCs asociados a través de las carteras de préstamos
     */
    public function jccs()
    {
        return $this->hasManyThrough(
            User::class,
            CarteraJcc::class,
            'prestamo_id', // Foreign key on carteras_jcc table
            'id', // Foreign key on users table
            'id', // Local key on clientes table
            'user_id' // Local key on carteras_jcc table
        )->via('prestamos');
    }

    /**
     * Obtiene todos los asesores asociados a través de las carteras de préstamos
     */
    public function asesores()
    {
        return $this->hasManyThrough(
            User::class,
            CarteraAsesor::class,
            'prestamo_id', // Foreign key on carteras_asesor table
            'id', // Foreign key on users table
            'id', // Local key on clientes table
            'user_id' // Local key on carteras_asesor table
        )->via('prestamos');
    }

    /**
     * Obtiene todos los analistas asociados a través de las carteras de préstamos
     */
    public function analistas()
    {
        return $this->hasManyThrough(
            User::class,
            CarteraAnalista::class,
            'prestamo_id', // Foreign key on carteras_analista table
            'id', // Foreign key on users table
            'id', // Local key on clientes table
            'user_id' // Local key on carteras_analista table
        )->via('prestamos');
    }

    /**
     * Obtiene el JCC principal (primer JCC encontrado)
     */
    public function jccPrincipal()
    {
        return $this->jccs()->first();
    }

    /**
     * Obtiene el asesor principal (primer asesor encontrado)
     */
    public function asesorPrincipal()
    {
        return $this->asesores()->first();
    }

    /**
     * Obtiene el analista principal (primer analista encontrado)
     */
    public function analistaPrincipal()
    {
        return $this->analistas()->first();
    }

    // Modelo Cliente (cuando hay muchas direcciones)
    public function direcciones()
    {
        return $this->hasMany(Direccion::class, 'persona_id');
    }

    // En App\Models\Cliente
    public function direccionPrincipal()
    {
        return $this->hasOneThrough(
            Direccion::class,
            Persona::class,
            'id', // Foreign key on Persona table
            'persona_id', // Foreign key on Direccion table
            'persona_id', // Local key on Cliente table
            'id' // Local key on Persona table
        )->where('estado', 1); // Si tienes un campo para marcar dirección principal
    }

    /**
     * Obtiene las carteras JCC a través de los préstamos
     */
    public function carterasJcc()
    {
        return $this->hasManyThrough(
            CarteraJcc::class,
            Prestamo::class,
            'cliente_id', // Foreign key on prestamos table
            'prestamo_id', // Foreign key on carteras_jcc table
            'id', // Local key on clientes table
            'id' // Local key on prestamos table
        );
    }

    /**
     * Obtiene las carteras asesor a través de los préstamos
     */
    public function carterasAsesor()
    {
        return $this->hasManyThrough(
            CarteraAsesor::class,
            Prestamo::class,
            'cliente_id', // Foreign key on prestamos table
            'prestamo_id', // Foreign key on carteras_asesor table
            'id', // Local key on clientes table
            'id' // Local key on prestamos table
        );
    }

    /**
     * Obtiene las carteras analista a través de los préstamos
     */
    public function carterasAnalista()
    {
        return $this->hasManyThrough(
            CarteraAnalista::class,
            Prestamo::class,
            'cliente_id', // Foreign key on prestamos table
            'prestamo_id', // Foreign key on carteras_analista table
            'id', // Local key on clientes table
            'id' // Local key on prestamos table
        );
    }

    /**
     * Accessor para obtener el tipo de documento desde la persona relacionada
     * Para comprobantes SUNAT: 1=DNI, 6=RUC, 4=Carnet Extranjería, 7=Pasaporte
     */
    public function getTipoDocumentoAttribute(): ?string
    {
        // Por defecto, asumimos DNI (tipo 1) si tiene persona con documento
        if ($this->persona && $this->persona->documento) {
            $longitud = strlen($this->persona->documento);

            // RUC tiene 11 dígitos
            if ($longitud === 11) {
                return '6';
            }
            // DNI tiene 8 dígitos
            if ($longitud === 8) {
                return '1';
            }
            // Otros casos
            return '1'; // Por defecto DNI
        }

        return null;
    }

    /**
     * Accessor para obtener el número de documento desde la persona relacionada
     */
    public function getNumeroDocumentoAttribute(): ?string
    {
        return $this->persona?->documento;
    }

    /**
     * Accessor para obtener el nombre completo desde la persona relacionada
     */
    public function getNombreCompletoAttribute(): ?string
    {
        if ($this->persona) {
            $nombres = trim($this->persona->nombres ?? '');
            $apePat = trim($this->persona->ape_pat ?? '');
            $apeMat = trim($this->persona->ape_mat ?? '');

            return trim("$nombres $apePat $apeMat");
        }

        return null;
    }

    /**
     * Accessor para obtener la dirección desde la persona relacionada
     */
    public function getDireccionAttribute(): ?string
    {
        // Intentar obtener la dirección principal desde las direcciones de la persona
        if ($this->persona) {
            $direccion = Direccion::where('persona_id', $this->persona->id)
                ->where('estado', 1)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($direccion) {
                return $direccion->direccion_completa ?? $direccion->direccion ?? null;
            }
        }

        return null;
    }
}