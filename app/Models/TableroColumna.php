<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TableroColumna extends Model
{
    use HasFactory;

    protected $table = 'tablero_columnas';

    protected $fillable = [
        'nombre',
        'color',
        'orden',
        'activo',
        'es_sistema',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'es_sistema' => 'boolean',
        'orden' => 'integer',
    ];

    public function tareas()
    {
        return $this->hasMany(Tarea::class, 'columna_id')->orderBy('orden');
    }

    public function tareasUsuario($userId)
    {
        return $this->tareas()->where('asignado_a', $userId);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($columna) {
            if (is_null($columna->orden)) {
                $columna->orden = static::max('orden') + 1;
            }
        });
    }

    public function scopeActivas($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }
}
