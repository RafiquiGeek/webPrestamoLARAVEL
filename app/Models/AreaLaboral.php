<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaLaboral extends Model
{
    use HasFactory;

    protected $table = 'areas_laborales';

    protected $fillable = [
        'nombre',
        'descripcion',
        'color',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function asignaciones()
    {
        return $this->hasMany(AsignacionAreaEmpleado::class);
    }

    public function empleados()
    {
        return $this->belongsToMany(User::class, 'asignaciones_area_empleado')
            ->wherePivot('activo', true)
            ->withPivot(['horario_trabajo_id', 'fecha_inicio', 'fecha_fin', 'activo'])
            ->withTimestamps();
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
