<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Persona extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombres',
        'ape_pat',
        'ape_mat',
        'imagen',
        'documento',
        'fecha_nacimiento',
        'email',
        'estado_civil',
    ];

    protected $appends = [
        'edad',
        'photo_url',
        'telefono_principal',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function direcciones(): HasMany
    {
        return $this->hasMany(Direccion::class);
    }

    public function telefonos(): HasMany
    {
        return $this->hasMany(Telefono::class);
    }

    public function getTelefonoPrincipalAttribute()
    {
        return $this->telefonos ? $this->telefonos->where('tipo_telefono', 'celular')->first() : null;
    }

    public function conyuge(): HasOne
    {
        return $this->hasOne(Conyuge::class);
    }

    public function aval(): HasMany
    {
        return $this->hasMany(Aval::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function direccion(): HasOne
    {
        return $this->hasOne(Direccion::class);
    }

    protected function edad(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => isset($attributes['fecha_nacimiento'])
                ? Carbon::parse($attributes['fecha_nacimiento'])->age
                : null
        );
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['imagen'] && \Storage::disk('public')->exists('img/clientes_img/'.$attributes['imagen'])
                ? \Storage::disk('public')->url('img/clientes_img/'.$attributes['imagen'])
                : \Storage::disk('public')->url('img/clientes_img/userDefaultPhoto.png')
        );
    }

    /**
     * Accesor para el nombre completo
     */
    public function getFullNameAttribute()
    {
        return $this->nombres.' '.$this->ape_pat.' '.$this->ape_mat;
    }
}
