<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HorarioTrabajo extends Model
{
    use HasFactory;

    protected $table = 'horarios_trabajo';

    protected $fillable = [
        'nombre',
        'descripcion_horario',
        'hora_entrada',
        'hora_salida',
        'inicio_refrigerio',
        'fin_refrigerio',
        'duracion_refrigerio_minutos',
        'tolerancia_entrada',
        'tolerancia_salida',
        'dias_laborales',
        'es_medio_tiempo',
        'es_horario_personalizado',
        'horarios_por_dia',
        'horarios_semanales',
        'tipo_horario',
        'activo',
    ];

    protected $casts = [
        'dias_laborales' => 'array',
        'es_medio_tiempo' => 'boolean',
        'es_horario_personalizado' => 'boolean',
        'horarios_por_dia' => 'array',
        'horarios_semanales' => 'array',
        'activo' => 'boolean',
    ];

    public function asignaciones()
    {
        return $this->hasMany(AsignacionAreaEmpleado::class);
    }

    public function empleados()
    {
        return $this->hasManyThrough(User::class, AsignacionAreaEmpleado::class, 'horario_trabajo_id', 'id', 'id', 'user_id')
            ->where('asignaciones_area_empleado.activo', true);
    }

    public function esHorarioLaboralHoy()
    {
        $diaSemana = Carbon::now()->dayOfWeek;

        return in_array($diaSemana, $this->dias_laborales);
    }

    public function esTardanza($horaEntrada)
    {
        // Limpiar hora_entrada si viene como datetime
        $horaEntradaLimpia = $this->hora_entrada;
        if (strpos($horaEntradaLimpia, ' ') !== false) {
            $horaEntradaLimpia = substr($horaEntradaLimpia, -8);
        }

        $horaLimite = Carbon::parse($horaEntradaLimpia)->addMinutes($this->tolerancia_entrada);

        return Carbon::parse($horaEntrada)->gt($horaLimite);
    }

    public function minutosDeRetraso($horaEntrada)
    {
        // Limpiar hora_entrada si viene como datetime
        $horaEntradaLimpia = $this->hora_entrada;
        if (strpos($horaEntradaLimpia, ' ') !== false) {
            $horaEntradaLimpia = substr($horaEntradaLimpia, -8);
        }

        $horaLimite = Carbon::parse($horaEntradaLimpia)->addMinutes($this->tolerancia_entrada);
        $entrada = Carbon::parse($horaEntrada);

        if ($entrada->gt($horaLimite)) {
            return $entrada->diffInMinutes($horaLimite);
        }

        return 0;
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function getDiasLaboralesTextAttribute()
    {
        $dias = [
            '1' => 'Lunes',
            '2' => 'Martes',
            '3' => 'Miércoles',
            '4' => 'Jueves',
            '5' => 'Viernes',
            '6' => 'Sábado',
            '0' => 'Domingo',
        ];

        return collect($this->dias_laborales)->map(function ($dia) use ($dias) {
            return $dias[$dia] ?? $dia;
        })->implode(', ');
    }

    // Constantes para tipos de horario
    const TIPO_COMPLETO = 'completo';

    const TIPO_MEDIO_TIEMPO = 'medio_tiempo';

    const TIPO_FLEXIBLE = 'flexible';

    // Métodos para tipos de horario
    public function esCompleto()
    {
        return $this->tipo_horario === self::TIPO_COMPLETO;
    }

    public function esMedioTiempo()
    {
        return $this->tipo_horario === self::TIPO_MEDIO_TIEMPO || $this->es_medio_tiempo;
    }

    public function esFlexible()
    {
        return $this->tipo_horario === self::TIPO_FLEXIBLE;
    }

    public function esHorarioPersonalizado()
    {
        return $this->es_horario_personalizado;
    }

    public function getTipoHorarioTextoAttribute()
    {
        return match ($this->tipo_horario) {
            self::TIPO_COMPLETO => 'Tiempo Completo',
            self::TIPO_MEDIO_TIEMPO => 'Medio Tiempo',
            self::TIPO_FLEXIBLE => 'Horario Flexible',
            default => 'Desconocido'
        };
    }

    // Obtener horario para un día específico
    public function obtenerHorarioParaDia($diaSemana)
    {
        // Función helper para limpiar horas
        $limpiarHora = function ($hora) {
            if (! $hora) {
                return $hora;
            }
            if (strpos($hora, ' ') !== false) {
                return substr($hora, -8);
            }

            return $hora;
        };

        // Si es horario personalizado, usar horarios_semanales
        if ($this->esHorarioPersonalizado() && ! empty($this->horarios_semanales)) {
            $horarioDia = $this->horarios_semanales[$diaSemana] ?? null;

            if ($horarioDia && isset($horarioDia['activo']) && $horarioDia['activo']) {
                return (object) [
                    'hora_entrada' => $limpiarHora($horarioDia['hora_entrada']),
                    'hora_salida' => $limpiarHora($horarioDia['hora_salida']),
                    'duracion_refrigerio_minutos' => $horarioDia['duracion_refrigerio_minutos'] ?? null,
                    'inicio_refrigerio' => $limpiarHora($horarioDia['inicio_refrigerio'] ?? null),
                    'fin_refrigerio' => $limpiarHora($horarioDia['fin_refrigerio'] ?? null),
                    'es_dia_laboral' => true,
                ];
            } else {
                // Día no laboral
                return null;
            }
        }

        // Si es horario flexible y tiene horarios por día (legacy)
        if ($this->esFlexible() && ! empty($this->horarios_por_dia)) {
            $horarioDia = $this->horarios_por_dia[$diaSemana] ?? null;

            if ($horarioDia) {
                return (object) [
                    'hora_entrada' => $limpiarHora($horarioDia['hora_entrada'] ?? $this->hora_entrada),
                    'hora_salida' => $limpiarHora($horarioDia['hora_salida'] ?? $this->hora_salida),
                    'duracion_refrigerio_minutos' => $horarioDia['duracion_refrigerio_minutos'] ?? $this->duracion_refrigerio_minutos,
                    'inicio_refrigerio' => $limpiarHora($horarioDia['inicio_refrigerio'] ?? $this->inicio_refrigerio),
                    'fin_refrigerio' => $limpiarHora($horarioDia['fin_refrigerio'] ?? $this->fin_refrigerio),
                    'es_dia_laboral' => true,
                ];
            }
        }

        // Verificar si es día laboral según días_laborales
        if (! in_array($diaSemana, $this->dias_laborales ?? [])) {
            return null; // No es día laboral
        }

        // Horario estándar
        return (object) [
            'hora_entrada' => $limpiarHora($this->hora_entrada),
            'hora_salida' => $limpiarHora($this->hora_salida),
            'duracion_refrigerio_minutos' => $this->duracion_refrigerio_minutos,
            'inicio_refrigerio' => $limpiarHora($this->inicio_refrigerio),
            'fin_refrigerio' => $limpiarHora($this->fin_refrigerio),
            'es_dia_laboral' => true,
        ];
    }

    // Verificar si hay horario especial para una fecha
    public function obtenerHorarioConEspeciales($fecha, $areaId = null)
    {
        // Verificar si hay un día especial configurado
        $diaEspecial = \App\Models\FeriadoHorarioEspecial::obtenerDiaEspecial($fecha, $areaId);

        if ($diaEspecial) {
            // Si es feriado, no hay horario
            if ($diaEspecial->esFeriado()) {
                return null;
            }

            // Si es medio día o especial, usar esos horarios
            if ($diaEspecial->esMedioDia() || $diaEspecial->esEspecial()) {
                return (object) [
                    'hora_entrada' => $diaEspecial->hora_entrada,
                    'hora_salida' => $diaEspecial->hora_salida,
                    'duracion_refrigerio_minutos' => $diaEspecial->duracion_refrigerio_minutos ?? null,
                    'inicio_refrigerio' => $diaEspecial->inicio_refrigerio,
                    'fin_refrigerio' => $diaEspecial->fin_refrigerio,
                    'es_especial' => true,
                    'tipo_especial' => $diaEspecial->tipo,
                    'nombre_especial' => $diaEspecial->nombre,
                ];
            }
        }

        // Obtener horario normal para el día
        $fechaParseada = is_string($fecha) ? $fecha : $fecha->format('Y-m-d');
        $diaSemana = Carbon::parse($fechaParseada)->dayOfWeek;
        $horarioNormal = $this->obtenerHorarioParaDia($diaSemana);
        $horarioNormal->es_especial = false;

        return $horarioNormal;
    }

    // Scopes adicionales
    public function scopeMedioTiempo($query)
    {
        return $query->where(function ($q) {
            $q->where('tipo_horario', self::TIPO_MEDIO_TIEMPO)
                ->orWhere('es_medio_tiempo', true);
        });
    }

    public function scopeCompleto($query)
    {
        return $query->where('tipo_horario', self::TIPO_COMPLETO);
    }

    public function scopeFlexible($query)
    {
        return $query->where('tipo_horario', self::TIPO_FLEXIBLE);
    }

    // Generar descripción automática del horario
    public function generarDescripcionHorario()
    {
        if ($this->esHorarioPersonalizado() && ! empty($this->horarios_semanales)) {
            return $this->generarDescripcionPersonalizada();
        }

        if (! empty($this->dias_laborales)) {
            $diasTexto = $this->getDiasLaboralesTextAttribute();
            $entrada = $this->hora_entrada ? \Carbon\Carbon::parse($this->hora_entrada)->format('H:i') : '';
            $salida = $this->hora_salida ? \Carbon\Carbon::parse($this->hora_salida)->format('H:i') : '';

            return "{$diasTexto}: {$entrada} - {$salida}";
        }

        return $this->nombre;
    }

    // Generar descripción de horario personalizado
    private function generarDescripcionPersonalizada()
    {
        $dias = [
            '1' => 'L', '2' => 'M', '3' => 'X', '4' => 'J', '5' => 'V', '6' => 'S', '0' => 'D',
        ];

        $grupos = [];
        $actualGrupo = null;
        $actualHorario = null;

        foreach ([1, 2, 3, 4, 5, 6, 0] as $dia) {
            $horario = $this->horarios_semanales[$dia] ?? null;

            if ($horario && $horario['activo']) {
                $horarioTexto = "{$horario['hora_entrada']}-{$horario['hora_salida']}";

                if ($actualHorario === $horarioTexto) {
                    $actualGrupo['dias'][] = $dias[$dia];
                } else {
                    if ($actualGrupo) {
                        $grupos[] = $actualGrupo;
                    }
                    $actualGrupo = [
                        'dias' => [$dias[$dia]],
                        'horario' => $horarioTexto,
                    ];
                    $actualHorario = $horarioTexto;
                }
            } else {
                if ($actualGrupo) {
                    $grupos[] = $actualGrupo;
                    $actualGrupo = null;
                    $actualHorario = null;
                }
            }
        }

        if ($actualGrupo) {
            $grupos[] = $actualGrupo;
        }

        $descripcion = [];
        foreach ($grupos as $grupo) {
            $diasTexto = implode('-', $grupo['dias']);
            $descripcion[] = "{$diasTexto} {$grupo['horario']}";
        }

        return implode(', ', $descripcion);
    }

    // Actualizar descripción automáticamente
    public function actualizarDescripcion()
    {
        $this->descripcion_horario = $this->generarDescripcionHorario();
        $this->save();
    }

    // Scope para horarios personalizados
    public function scopePersonalizados($query)
    {
        return $query->where('es_horario_personalizado', true);
    }
}
