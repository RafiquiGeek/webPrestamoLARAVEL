<?php

namespace App\Models;

use App\Enums\NivelCalificacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaCumplimiento extends Model
{
    use HasFactory;

    protected $table = 'meta_cumplimientos';

    protected $fillable = [
        'meta_id',
        'asesor_id',
        'anio',
        'mes',
        'prestamos_originados',
        'prestamos_vigentes',
        'prestamos_morosos',
        'renovaciones',
        'porcentaje_cumplimiento',
        'porcentaje_morosidad',
        'nivel_calificacion',
        'meses_consecutivos',
        'comision_base',
        'comision_final',
        'penalizado_morosidad',
        'porcentaje_morosidad_umbral',
        'fecha_calculo',
    ];

    protected $casts = [
        'nivel_calificacion' => NivelCalificacion::class,
        'fecha_calculo' => 'datetime',
        'penalizado_morosidad' => 'boolean',
    ];

    public function meta(): BelongsTo
    {
        return $this->belongsTo(Meta::class);
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }
}
