<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoriaGasto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'categorias_gastos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'color',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relación con gastos
    public function gastos()
    {
        return $this->hasMany(Gasto::class, 'categoria_gasto_id');
    }

    // Scope para categorías activas
    public function scopeActivas($query)
    {
        return $query->where('estado', true);
    }

    // Accessor para mostrar estado
    public function getEstadoTextoAttribute()
    {
        return $this->estado ? 'Activo' : 'Inactivo';
    }
}
