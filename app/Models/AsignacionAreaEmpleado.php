<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignacionAreaEmpleado extends Model
{
    use HasFactory;

    protected $table = 'asignaciones_area_empleado';

    protected $fillable = [
        'user_id',
        'area_laboral_id',
        'horario_trabajo_id',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function areaLaboral()
    {
        return $this->belongsTo(AreaLaboral::class);
    }

    public function horarioTrabajo()
    {
        return $this->belongsTo(HorarioTrabajo::class);
    }

    public function registrosAsistencia()
    {
        return $this->hasMany(RegistroAsistencia::class, 'asignacion_id');
    }

    public function estaVigente($fecha = null)
    {
        if (! $this->activo) {
            return false;
        }

        $fecha = $fecha ? Carbon::parse($fecha) : Carbon::now();

        if ($fecha->lt($this->fecha_inicio)) {
            return false;
        }

        if ($this->fecha_fin && $fecha->gt($this->fecha_fin)) {
            return false;
        }

        return true;
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeVigentes($query, $fecha = null)
    {
        $fecha = $fecha ? Carbon::parse($fecha) : Carbon::now();

        return $query->where('activo', true)
            ->where('fecha_inicio', '<=', $fecha)
            ->where(function ($q) use ($fecha) {
                $q->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $fecha);
            });
    }
}
