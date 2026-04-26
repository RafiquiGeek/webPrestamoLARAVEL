<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroAsistencia extends Model
{
    use HasFactory;

    protected $table = 'registros_asistencia';

    protected $fillable = [
        'user_id',
        'asignacion_id',
        'fecha',
        'hora_entrada',
        'hora_salida',
        'inicio_refrigerio',
        'fin_refrigerio',
        'minutos_refrigerio',
        'estado_refrigerio',
        'estado_entrada',
        'estado_salida',
        'minutos_tardanza',
        'minutos_refrigerio_extra',
        'latitud_entrada',
        'longitud_entrada',
        'latitud_salida',
        'longitud_salida',
        'latitud_inicio_refrigerio',
        'longitud_inicio_refrigerio',
        'latitud_fin_refrigerio',
        'longitud_fin_refrigerio',
        'ip_entrada',
        'ip_salida',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'latitud_entrada' => 'decimal:8',
        'longitud_entrada' => 'decimal:8',
        'latitud_salida' => 'decimal:8',
        'longitud_salida' => 'decimal:8',
        'latitud_inicio_refrigerio' => 'decimal:8',
        'longitud_inicio_refrigerio' => 'decimal:8',
        'latitud_fin_refrigerio' => 'decimal:8',
        'longitud_fin_refrigerio' => 'decimal:8',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function asignacion()
    {
        return $this->belongsTo(AsignacionAreaEmpleado::class, 'asignacion_id');
    }

    public function calcularHorasTrabajadas()
    {
        if (! $this->hora_entrada || ! $this->hora_salida) {
            return 0;
        }

        // Obtener fecha como string Y-m-d
        $fechaSolo = $this->fecha instanceof Carbon ?
                     $this->fecha->format('Y-m-d') :
                     Carbon::parse($this->fecha)->format('Y-m-d');

        // Extraer solo la parte de tiempo de las horas (por si vienen como datetime)
        $horaEntrada = $this->hora_entrada;
        $horaSalida = $this->hora_salida;

        // Si las horas vienen como datetime, extraer solo la parte de tiempo
        if (strpos($horaEntrada, ' ') !== false) {
            $horaEntrada = substr($horaEntrada, -8); // últimos 8 caracteres (HH:MM:SS)
        }
        if (strpos($horaSalida, ' ') !== false) {
            $horaSalida = substr($horaSalida, -8); // últimos 8 caracteres (HH:MM:SS)
        }

        $entrada = Carbon::parse($fechaSolo.' '.$horaEntrada);
        $salida = Carbon::parse($fechaSolo.' '.$horaSalida);

        $horasTrabajadas = $salida->diffInMinutes($entrada);

        // Restar tiempo de refrigerio si existe
        if ($this->inicio_refrigerio && $this->fin_refrigerio) {
            $inicioRef = $this->inicio_refrigerio;
            $finRef = $this->fin_refrigerio;

            // Limpiar refrigerios también
            if (strpos($inicioRef, ' ') !== false) {
                $inicioRef = substr($inicioRef, -8);
            }
            if (strpos($finRef, ' ') !== false) {
                $finRef = substr($finRef, -8);
            }

            $inicioRefrigerio = Carbon::parse($fechaSolo.' '.$inicioRef);
            $finRefrigerio = Carbon::parse($fechaSolo.' '.$finRef);
            $minutosRefrigerio = $finRefrigerio->diffInMinutes($inicioRefrigerio);
            $horasTrabajadas -= $minutosRefrigerio;
        }

        return round($horasTrabajadas / 60, 2); // Devolver en horas decimales
    }

    public function tieneAsistenciaCompleta()
    {
        return $this->hora_entrada && $this->hora_salida;
    }

    public function esTardanza()
    {
        return $this->estado_entrada === 'tardanza';
    }

    public function esFalta()
    {
        return $this->estado_entrada === 'falta';
    }

    public function getEstadoEntradaColorAttribute()
    {
        return match ($this->estado_entrada) {
            'puntual' => 'success',
            'tardanza' => 'warning',
            'falta' => 'danger',
            default => 'secondary'
        };
    }

    public function getEstadoSalidaColorAttribute()
    {
        return match ($this->estado_salida) {
            'puntual' => 'success',
            'temprano' => 'info',
            'tardio' => 'warning',
            'pendiente' => 'secondary',
            default => 'secondary'
        };
    }

    public function scopeHoy($query)
    {
        return $query->whereDate('fecha', Carbon::today());
    }

    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    public function scopeConTardanzas($query)
    {
        return $query->where('estado_entrada', 'tardanza');
    }

    public function scopeConFaltas($query)
    {
        return $query->where('estado_entrada', 'falta');
    }
}
