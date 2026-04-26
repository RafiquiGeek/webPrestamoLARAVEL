<?php

namespace App\Models;

use App\Enums\NivelCalificacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Meta extends Model
{
    use HasFactory;

    protected $fillable = [
        'asesor_id',
        'anio',
        'mes',
        'cantidad_objetivo',
        'estado',
        'observaciones',
    ];

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function cumplimiento(): HasOne
    {
        return $this->hasOne(MetaCumplimiento::class, 'meta_id');
    }

    // Accessors para facilitar el acceso en las vistas
    public function getNivelCalificacionAttribute()
    {
        return $this->cumplimiento?->nivel_calificacion ?? NivelCalificacion::NINGUNO;
    }

    public function getPorcentajeCumplimientoAttribute()
    {
        return $this->cumplimiento?->porcentaje_cumplimiento ?? 0;
    }

    public function getPorcentajeMoraAttribute()
    {
        return $this->cumplimiento?->porcentaje_morosidad ?? 0;
    }

    public function getComisionCalculadaAttribute()
    {
        return $this->cumplimiento?->comision_final ?? 0;
    }

    public function getCantidadLogradaAttribute()
    {
        return $this->cumplimiento?->prestamos_originados ?? 0;
    }
}
