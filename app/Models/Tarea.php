<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarea extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'descripcion',
        'asignado_por',
        'asignado_a',
        'columna_id',
        'prioridad',
        'estado',
        'fecha_asignacion',
        'fecha_inicio',
        'fecha_vencimiento',
        'fecha_completado',
        'tiempo_estimado',
        'tiempo_real',
        'orden',
        'progreso',
    ];

    protected $casts = [
        'fecha_asignacion' => 'datetime',
        'fecha_inicio' => 'datetime',
        'fecha_vencimiento' => 'datetime',
        'fecha_completado' => 'datetime',
        'tiempo_estimado' => 'integer',
        'tiempo_real' => 'integer',
        'orden' => 'integer',
        'progreso' => 'integer',
    ];

    protected $appends = ['tiempo_restante', 'esta_vencida', 'prioridad_color', 'estado_color'];

    public function asignadoPor()
    {
        return $this->belongsTo(User::class, 'asignado_por');
    }

    public function asignadoA()
    {
        return $this->belongsTo(User::class, 'asignado_a');
    }

    public function columna()
    {
        return $this->belongsTo(TableroColumna::class, 'columna_id');
    }

    public function archivos()
    {
        return $this->hasMany(TareaArchivo::class);
    }

    public function comentarios()
    {
        return $this->hasMany(TareaComentario::class)->orderBy('created_at', 'desc');
    }

    public function imagenes()
    {
        return $this->archivos()->where('tipo', 'imagen');
    }

    public function documentos()
    {
        return $this->archivos()->where('tipo', 'documento');
    }

    public function getTiempoRestanteAttribute()
    {
        if (! $this->fecha_vencimiento) {
            return null;
        }

        $ahora = Carbon::now();
        $vencimiento = Carbon::parse($this->fecha_vencimiento);

        if ($ahora->gt($vencimiento)) {
            return 'Vencida';
        }

        $diff = $ahora->diff($vencimiento);

        if ($diff->days > 0) {
            return $diff->days.' días';
        } elseif ($diff->h > 0) {
            return $diff->h.' horas';
        } else {
            return $diff->i.' minutos';
        }
    }

    public function getEstaVencidaAttribute()
    {
        if (! $this->fecha_vencimiento) {
            return false;
        }

        return Carbon::now()->gt($this->fecha_vencimiento) && $this->estado !== 'completado';
    }

    public function getPrioridadColorAttribute()
    {
        return match ($this->prioridad) {
            'baja' => '#28a745',
            'media' => '#ffc107',
            'alta' => '#fd7e14',
            'urgente' => '#dc3545',
            default => '#6c757d'
        };
    }

    public function getEstadoColorAttribute()
    {
        return match ($this->estado) {
            'pendiente' => '#6c757d',
            'en_progreso' => '#007bff',
            'en_revision' => '#ffc107',
            'pausado' => '#fd7e14',
            'completado' => '#28a745',
            'cancelado' => '#dc3545',
            default => '#6c757d'
        };
    }

    public function iniciarTarea()
    {
        // Buscar la columna "En Progreso"
        $columnaEnProgreso = TableroColumna::where('nombre', 'En Progreso')->first();

        $this->update([
            'fecha_inicio' => now(),
            'estado' => 'en_progreso',
            'columna_id' => $columnaEnProgreso ? $columnaEnProgreso->id : $this->columna_id,
        ]);
    }

    public function enviarARevision()
    {
        // Buscar la columna "En Revisión"
        $columnaRevision = TableroColumna::where('nombre', 'En Revisión')->first();

        $this->update([
            'estado' => 'en_revision',
            'progreso' => 90,
            'columna_id' => $columnaRevision ? $columnaRevision->id : $this->columna_id,
        ]);
    }

    public function aprobarTarea()
    {
        // Buscar la columna "Completado"
        $columnaCompletado = TableroColumna::where('nombre', 'Completado')->first();

        $this->update([
            'fecha_completado' => now(),
            'estado' => 'completado',
            'progreso' => 100,
            'columna_id' => $columnaCompletado ? $columnaCompletado->id : $this->columna_id,
        ]);
    }

    public function actualizarTiempoReal($minutos)
    {
        $this->increment('tiempo_real', $minutos);
    }

    public function scopeVencidas($query)
    {
        return $query->whereNotNull('fecha_vencimiento')
            ->where('fecha_vencimiento', '<', now())
            ->where('estado', '!=', 'completado');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeEnProgreso($query)
    {
        return $query->where('estado', 'en_progreso');
    }

    public function scopeCompletadas($query)
    {
        return $query->where('estado', 'completado');
    }

    public function scopeDelUsuario($query, $userId)
    {
        return $query->where('asignado_a', $userId);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($tarea) {
            if (is_null($tarea->orden)) {
                $maxOrden = static::where('columna_id', $tarea->columna_id)->max('orden');
                $tarea->orden = $maxOrden + 1;
            }
        });
    }
}
