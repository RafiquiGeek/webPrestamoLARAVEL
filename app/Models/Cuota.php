<?php
namespace App\Models;
use App\Enums\CuotaEstado;
use App\Enums\MoraCuotaEstado;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Cuota extends Model
{
    use HasFactory;

    protected $fillable = [
        'prestamo_id',
        'fecha_pago',
        'numero',
        'monto',
        'pago_capital',
        'gas',
        'interes',
        'comision',
        'igv',
        'monto_pagado',
        'cantidad_mora',
        'estado',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto_pagado' => 'decimal:2',
        'cantidad_mora' => 'double',
        'gas' => 'double',
        'interes' => 'double',
        'comision' => 'double',
        'igv' => 'double',
        'estado' => CuotaEstado::class, 
    ];

    
    const ESTADO_PENDIENTE = CuotaEstado::PENDIENTE;

    const ESTADO_PARCIAL = CuotaEstado::PARCIAL;

    const ESTADO_PAGADO = CuotaEstado::PAGADO;

    const ESTADO_VENCIDO = CuotaEstado::VENCIDO;

    /**
     * Obtiene el préstamo al que le pertenece la cuota
     */
    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    /**
     * Obtiene las operaciones de la cuota
     */
    public function operaciones()
    {
        return $this->belongsToMany(Operacion::class, 'operaciones_cuota')->withTimestamps();
    }

    /**
     * Obtiene los comprobantes asociados a esta cuota
     */
    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class);
    }

    /**
     * Obtiene todas las moras asociadas a la cuota
     */
    public function moras(): HasMany
    {
        return $this->hasMany(MoraCuota::class);
    }

    /**
     * Obtiene las moras pendientes asociadas a la cuota (PENDIENTE + PARCIAL)
     * Excluye las moras completamente pagadas
     */
    public function moras_pendientes(): HasMany
    {
        return $this->moras()->whereIn('estado', [
            MoraCuotaEstado::PENDIENTE->value,
            MoraCuotaEstado::PARCIAL->value,
        ]);
    }

    /**
     * Obtiene los abonos a favor de mora para esta cuota
     */
    public function abonosMoraFavor(): HasMany
    {
        return $this->hasMany(AbonoMoraFavor::class);
    }

    /**
     * Obtiene los abonos a favor activos (con saldo disponible)
     */
    public function abonosMoraFavorActivos(): HasMany
    {
        return $this->abonosMoraFavor()
            ->where('estado', AbonoMoraFavor::ESTADO_ACTIVO)
            ->where('saldo_favor', '>', 0);
    }

    /**
     * Calcula el monto pagado de moras de esta cuota
     */
    public function getMontoPagadoMorasAttribute(): float
    {
        return $this->moras()
            ->get()
            ->sum(function ($mora) {
                return $mora->monto_pagado ?? 0;
            });
    }

    /**
     * Calcula el monto real pendiente de moras (considerando pagos parciales)
     * Excluye moras PAGADAS y REGULARIZADAS
     */
    public function getMontoPendienteMorasAttribute(): float
    {
        return $this->moras()
            ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
            ->get()
            ->sum(function ($mora) {
                $montoPagado = $mora->monto_pagado ?? 0;

                return max(0, $mora->monto - $montoPagado);
            });
    }

    /**
     * Cuenta las moras que tienen saldo pendiente
     * Excluye moras PAGADAS y REGULARIZADAS
     */
    public function getCantidadMorasPendientesAttribute(): int
    {
        return $this->moras()
            ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
            ->where(function ($query) {
                $query->where('monto_pagado', '<', \DB::raw('monto'))
                    ->orWhereNull('monto_pagado');
            })
            ->count();
    }

    /**
     * Obtener el nombre del estado
     */
    public function getEstadoNombreAttribute(): string
    {
        return match ($this->estado) {
            CuotaEstado::PENDIENTE => 'Pendiente',
            CuotaEstado::PARCIAL => 'Parcial',
            CuotaEstado::PAGADO => 'Pagado',
            CuotaEstado::VENCIDO => 'Vencida',
            default => 'Desconocido',
        };
    }

    /**
     * Obtener el color del estado para las etiquetas
     * Método unificado que considera tanto el estado como la fecha de vencimiento
     */
    public function getEstadoColorAttribute(): string
    {
        // Primero verificamos por estado
        if ($this->estado === CuotaEstado::PAGADO) {
            return 'bg-success'; // Verde para pagado
        }

        if ($this->estado === CuotaEstado::PARCIAL) {
            return 'bg-gradient-amber-to-green'; // Degradado de ámbar a verde para parcial
        }

        if ($this->estado === CuotaEstado::VENCIDO) {
            return 'bg-danger'; // Rojo para vencido
        }

        // Para PENDIENTE, evaluamos la fecha
        if ($this->estado === CuotaEstado::PENDIENTE) {
            // Si no hay fecha de pago, retornar por defecto
            if (empty($this->fecha_pago)) {
                return 'bg-secondary';
            }

            try {
                $fechaVencimiento = Carbon::parse($this->fecha_pago)->startOfDay();
                $hoy = Carbon::now()->startOfDay();

                // Si vence hoy
                if ($fechaVencimiento->isSameDay($hoy)) {
                    return 'bg-warning text-dark'; // Ámbar
                }

                // Si ya venció
                if ($fechaVencimiento->isPast()) {
                    return 'bg-danger'; // Rojo para vencido
                }

                // Si vence en el futuro
                return 'bg-light text-dark'; // Gris claro con texto oscuro para pendiente a futuro
            } catch (\Exception $e) {
                return 'bg-secondary';
            }
        }

        return 'bg-secondary'; // Por defecto
    }

    /**
     * Método para mantener compatibilidad con código existente
     * Redirige al método unificado getEstadoColorAttribute
     */
    public function getEstadoBadgeClassAttribute()
    {
        return $this->getEstadoColorAttribute();
    }

    /**
     * Obtiene un arreglo con el estado y la clase CSS para el badge
     *
     * @return array
     */
    public function getEstadoBadgeAttribute()
    {
        $estado = $this->estado_nombre;
        $class = $this->getEstadoColorAttribute();

        return ['estado' => $estado, 'class' => $class];
    }

    /**
     * Verifica si esta cuota tiene operaciones que han sido editadas
     */
    public function tieneOperacionesEditadas(): bool
    {
        return $this->operaciones()
            ->whereNotNull('editado_en')
            ->exists();
    }

    /**
     * Verifica si esta cuota tiene operaciones que han sido anuladas
     */
    public function tieneOperacionesAnuladas(): bool
    {
        return $this->operaciones()
            ->where('estado', 'anulado')
            ->exists();
    }

    /**
     * Obtiene las etiquetas de estado para mostrar en la interfaz
     */
    public function getEtiquetasEstado(): array
    {
        $etiquetas = [];

        if ($this->tieneOperacionesEditadas()) {
            $etiquetas[] = [
                'texto' => 'EDITADA',
                'clase' => 'badge badge-warning badge-sm',
                'titulo' => 'Esta cuota tiene operaciones que han sido editadas',
            ];
        }

        if ($this->tieneOperacionesAnuladas()) {
            $etiquetas[] = [
                'texto' => 'ANULADA',
                'clase' => 'badge badge-danger badge-sm',
                'titulo' => 'Esta cuota tiene operaciones que han sido anuladas',
            ];
        }

        return $etiquetas;
    }

    /**
     * Calcula el total de abonos a favor para esta cuota
     */
    public function getTotalAbonosMoraFavorAttribute(): float
    {
        return $this->abonosMoraFavor()
            ->where('estado', '!=', AbonoMoraFavor::ESTADO_ANULADO)
            ->sum('monto_abonado');
    }

    /**
     * Calcula el saldo disponible a favor para esta cuota
     */
    public function getSaldoMoraFavorAttribute(): float
    {
        return $this->abonosMoraFavor()
            ->where('estado', AbonoMoraFavor::ESTADO_ACTIVO)
            ->sum('saldo_favor');
    }

    /**
     * Calcula el monto utilizado de los abonos a favor
     */
    public function getMontoUtilizadoMoraFavorAttribute(): float
    {
        return $this->abonosMoraFavor()
            ->where('estado', '!=', AbonoMoraFavor::ESTADO_ANULADO)
            ->sum('monto_utilizado');
    }

    /**
     * Determina si esta cuota tiene abonos a favor disponibles
     */
    public function tieneAbonosMoraFavor(): bool
    {
        return $this->saldo_mora_favor > 0;
    }

    /**
     * Aplica abonos a favor a una mora recién generada
     *
     * @return float Monto aplicado del abono a favor
     */
    public function aplicarAbonosFavorAMora(MoraCuota $mora): float
    {
        $totalAplicado = 0;
        $montoMoraPendiente = $mora->monto;

        // Obtener abonos a favor con saldo disponible, ordenados por fecha
        $abonosFavor = $this->abonosMoraFavorActivos()
            ->orderBy('fecha_abono', 'asc')
            ->get();

        foreach ($abonosFavor as $abono) {
            if ($montoMoraPendiente <= 0) {
                break;
            }

            $montoAplicado = $abono->utilizarSaldoFavor($montoMoraPendiente);
            $totalAplicado += $montoAplicado;
            $montoMoraPendiente -= $montoAplicado;
        }

        // Si se aplicó algún monto, actualizar la mora
        if ($totalAplicado > 0) {
            $nuevoMontoPagado = ($mora->monto_pagado ?? 0) + $totalAplicado;

            // Determinar el nuevo estado
            if ($nuevoMontoPagado >= $mora->monto) {
                // Completamente pagada
                $nuevoEstado = \App\Enums\MoraCuotaEstado::PAGADO;
            } else {
                // Parcialmente pagada
                $nuevoEstado = \App\Enums\MoraCuotaEstado::PARCIAL;
            }

            // Actualizar la mora
            $mora->update([
                'monto_pagado' => $nuevoMontoPagado,
                'estado' => $nuevoEstado,
            ]);

            \Log::info("🎯 Mora {$mora->id} actualizada: Pagado S/{$nuevoMontoPagado} de S/{$mora->monto}, Estado: {$nuevoEstado->name}");
        }

        return $totalAplicado;
    }

    /**
     * Obtiene las moras pagadas limitadas a 7 días
     */
    public function morasPagadasLimitadas(): HasMany
    {
        return $this->hasMany(MoraCuota::class)
            ->whereNotIn('estado', [
                MoraCuotaEstado::PENDIENTE->value,
                MoraCuotaEstado::REGULARIZADA->value,
                'anulado' // Estado anulado se guarda como string
            ])
            ->selectRaw('*,
                LEAST(dias_mora, 7) as dias_mora_limitados,
                (monto / GREATEST(dias_mora, 1)) * LEAST(dias_mora, 7) as monto_maximo,
                LEAST(monto_pagado, (monto / GREATEST(dias_mora, 1)) * LEAST(dias_mora, 7)) as monto_pagado_limitado'
            );
    }

    /**
     * Obtiene las moras pendientes limitadas a 7 días
     */
    public function morasPendientesLimitadas(): HasMany
    {
        return $this->hasMany(MoraCuota::class)
            ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
            ->selectRaw('*, 
                LEAST(dias_mora, 7) as dias_mora_limitados,
                (monto / GREATEST(dias_mora, 1)) * LEAST(dias_mora, 7) as monto_maximo,
                GREATEST(0, (monto / GREATEST(dias_mora, 1)) * LEAST(dias_mora, 7) - monto_pagado) as monto_pendiente_limitado'
            );
    }

    /**
     * Calcula el monto pagado de moras limitado a 7 días
     */
    public function getMontoPagadoMorasLimitadoAttribute(): float
    {
        return $this->morasPagadasLimitadas->sum('monto_pagado_limitado');
    }

    /**
     * Calcula el monto pendiente de moras limitado a 7 días
     */
    public function getMontoPendienteMorasLimitadoAttribute(): float
    {
        return $this->morasPendientesLimitadas->sum('monto_pendiente_limitado');
    }

    public function recalcularEstado()
    {
        // Debug: Obtener operaciones encontradas para esta cuota
        $operacionesEncontradas = DB::table('operaciones_cuota')
            ->join('operaciones', 'operaciones_cuota.operacion_id', '=', 'operaciones.id')
            ->where('operaciones_cuota.cuota_id', $this->id)
            ->where('operaciones.estado', '!=', 'anulado')
            ->select('operaciones_cuota.monto_aplicado', 'operaciones.id as op_id', 'operaciones.estado')
            ->get();

        \Log::info("Debug cálculo cuota #{$this->id}", [
            'total_calculado' => $operacionesEncontradas->sum('monto_aplicado'),
            'monto_cuota' => $this->monto,
            'operaciones_encontradas' => $operacionesEncontradas->toArray(),
        ]);

        // Obtener total pagado de TODAS las operaciones válidas
        $totalPagado = $operacionesEncontradas->sum('monto_aplicado');

        $this->monto_pagado = $totalPagado;

        // Determinar estado
        $hoy = now();

        if ($totalPagado >= $this->monto) {
            $this->estado = 2; // PAGADO - Una cuota pagada completamente nunca puede estar vencida
        } elseif ($totalPagado > 0) {
            // Parcialmente pagada - verificar vencimiento
            if ($this->fecha_pago < $hoy) {
                $this->estado = 3; // VENCIDO (parcial)
            } else {
                $this->estado = 1; // PARCIAL (no vencida)
            }
        } else {
            // Sin pagos - verificar vencimiento
            if ($this->fecha_pago < $hoy) {
                $this->estado = 3; // VENCIDO
            } else {
                $this->estado = 0; // PENDIENTE
            }
        }

        return $this->save();
    }

    /**
     * ========================================
     * SCOPES PARA OPTIMIZACIÓN DE DEUDAS
     * ========================================
     */

    /**
     * Scope para cuotas con deuda (pendientes, parciales, vencidas o con moras pendientes)
     */
    public function scopeConDeuda($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('cuotas.estado', [
                CuotaEstado::PENDIENTE->value,
                CuotaEstado::PARCIAL->value,
                CuotaEstado::VENCIDO->value
            ])
            ->orWhere(function ($sub) {
                // Optimizado: usar EXISTS con raw query en vez de whereHas para evitar subconsulta lenta
                $sub->where('cuotas.estado', CuotaEstado::PAGADO->value)
                    ->whereRaw('EXISTS (SELECT 1 FROM mora_cuota WHERE mora_cuota.cuota_id = cuotas.id AND mora_cuota.estado IN (?, ?))', [
                        MoraCuotaEstado::PENDIENTE->value,
                        MoraCuotaEstado::PARCIAL->value,
                    ]);
            });
        });
    }

    /**
     * Scope optimizado con eager loading mínimo para listados
     */
    public function scopeConRelacionesOptimizadas($query)
    {
        return $query->with([
            // Cliente y persona (datos básicos)
            'prestamo:id,cliente_id,estado,saldo_restante,mora,direccion_cobro_id',
            'prestamo.cliente:id,codigo,persona_id',
            'prestamo.cliente.persona:id,nombres,ape_pat,ape_mat,documento',

            // Dirección de cobro del préstamo (prioridad)
            'prestamo.direccionCobro:id,sucursal_id,zona_id,direccion,numero,referencia',
            'prestamo.direccionCobro.sucursal:id,sucursal',
            'prestamo.direccionCobro.sucursal.zonas:id,nombre',
            'prestamo.direccionCobro.zona:id,nombre',

            // Dirección del cliente (fallback) - cargar todas para filtrar por principal en PHP
            'prestamo.cliente.persona.direcciones' => function ($q) {
                $q->select('id', 'persona_id', 'sucursal_id', 'zona_id', 'direccion', 'numero', 'referencia', 'tipo_direccion');
            },
            'prestamo.cliente.persona.direcciones.sucursal:id,sucursal',
            'prestamo.cliente.persona.direcciones.sucursal.zonas:id,nombre',
            'prestamo.cliente.persona.direcciones.zona:id,nombre',

            // Moras pendientes con cálculo optimizado
            'moras' => function ($q) {
                $q->select(
                    'id',
                    'cuota_id',
                    'monto',
                    'dias_mora',
                    'estado',
                    'monto_pagado',
                    DB::raw('(monto - COALESCE(monto_pagado, 0)) as saldo_mora')
                )
                ->whereIn('estado', [
                    MoraCuotaEstado::PENDIENTE->value,
                    MoraCuotaEstado::PARCIAL->value
                ]);
            },

            // Gestiones (solo la primera)
            'prestamo.gestiones' => function ($q) {
                $q->select('id', 'prestamo_id', 'fecha', 'observaciones', 'estado_id', 'tipo_gestion')
                  ->orderBy('fecha', 'desc')
                  ->limit(1);
            },

            // Compromisos (solo el primero)
            'prestamo.compromisos' => function ($q) {
                $q->select('id', 'prestamo_id', 'fecha_compromiso_pago', 'monto', 'estado')
                  ->orderBy('fecha_compromiso_pago', 'desc')
                  ->limit(1);
            },

            // Carteras con usuarios
            'prestamo.carterasJcc:id,prestamo_id,jcc_id,estado',
            'prestamo.carterasJcc.jcc:id,codigo,name,persona_id',
            'prestamo.carterasJcc.jcc.persona:id,nombres',

            'prestamo.carterasAsesor:id,prestamo_id,asesor_id,estado',
            'prestamo.carterasAsesor.asesor:id,codigo,name,persona_id',
            'prestamo.carterasAsesor.asesor.persona:id,nombres',

            'prestamo.carterasAnalista:id,prestamo_id,analista_id,estado',
            'prestamo.carterasAnalista.analista:id,codigo,name,persona_id',
            'prestamo.carterasAnalista.analista.persona:id,nombres',
        ]);
    }

    /**
     * Scope para filtrar por carteras (JCC, Asesor, Analista)
     */
    public function scopePorCarteras($query, $jccId = null, $asesorId = null, $analistaId = null)
    {
        if ($jccId) {
            $query->whereHas('prestamo.carterasJcc', function ($q) use ($jccId) {
                $q->where('jcc_id', $jccId)->where('estado', 1);
            });
        }

        if ($asesorId) {
            $query->whereHas('prestamo.carterasAsesor', function ($q) use ($asesorId) {
                $q->where('asesor_id', $asesorId)->where('estado', 1);
            });
        }

        if ($analistaId) {
            $query->whereHas('prestamo.carterasAnalista', function ($q) use ($analistaId) {
                $q->where('analista_id', $analistaId)->where('estado', 1);
            });
        }

        return $query;
    }

    /**
     * Scope para filtrar por ubicación (zona, sucursal)
     * Busca en AMBAS fuentes: dirección de cobro Y direcciones del cliente
     * Soporta arrays para selección múltiple
     *
     * CORRECCIÓN V2: Usa whereExists con raw SQL para máxima precisión
     */
    public function scopePorUbicacion($query, $zonaId = null, $sucursalId = null)
    {
        if ($sucursalId) {
            $sucursalIds = is_array($sucursalId) ? $sucursalId : [$sucursalId];
            $placeholders = implode(',', array_fill(0, count($sucursalIds), '?'));

            // DEBUG: Log para ver qué se está aplicando
            \Log::info('🏢 SCOPE porUbicacion ACTIVADO:', [
                'sucursalId_recibido' => $sucursalId,
                'sucursalIds_array' => $sucursalIds,
                'count' => count($sucursalIds),
                'placeholders' => $placeholders,
                'bindings' => array_merge($sucursalIds, $sucursalIds)
            ]);

            return $query->whereRaw("
                EXISTS (
                    SELECT 1 FROM prestamos p
                    WHERE p.id = cuotas.prestamo_id
                    AND (
                        -- Opción 1: Dirección de cobro en sucursales seleccionadas
                        EXISTS (
                            SELECT 1 FROM direcciones d
                            WHERE d.id = p.direccion_cobro_id
                            AND d.sucursal_id IN ($placeholders)
                        )
                        OR
                        -- Opción 2: Cliente tiene dirección principal en sucursales seleccionadas
                        EXISTS (
                            SELECT 1 FROM clientes c
                            JOIN personas per ON c.persona_id = per.id
                            JOIN direcciones d2 ON per.id = d2.persona_id
                            WHERE c.id = p.cliente_id
                            AND d2.tipo_direccion = 'principal'
                            AND d2.sucursal_id IN ($placeholders)
                        )
                    )
                )
            ", array_merge($sucursalIds, $sucursalIds));
        }

        if ($zonaId) {
            $zonaIds = is_array($zonaId) ? $zonaId : [$zonaId];
            $placeholders = implode(',', array_fill(0, count($zonaIds), '?'));

            return $query->whereRaw("
                EXISTS (
                    SELECT 1 FROM prestamos p
                    WHERE p.id = cuotas.prestamo_id
                    AND (
                        -- Opción 1: Dirección de cobro con zona directa
                        EXISTS (
                            SELECT 1 FROM direcciones d
                            WHERE d.id = p.direccion_cobro_id
                            AND d.zona_id IN ($placeholders)
                        )
                        OR
                        -- Opción 2: Dirección de cobro cuya sucursal pertenece a zona
                        EXISTS (
                            SELECT 1 FROM direcciones d
                            JOIN zona_sucursal zs ON d.sucursal_id = zs.sucursal_id
                            WHERE d.id = p.direccion_cobro_id
                            AND zs.zona_id IN ($placeholders)
                        )
                        OR
                        -- Opción 3: Cliente con dirección principal en zona directa
                        EXISTS (
                            SELECT 1 FROM clientes c
                            JOIN personas per ON c.persona_id = per.id
                            JOIN direcciones d2 ON per.id = d2.persona_id
                            WHERE c.id = p.cliente_id
                            AND d2.tipo_direccion = 'principal'
                            AND d2.zona_id IN ($placeholders)
                        )
                        OR
                        -- Opción 4: Cliente con dirección principal cuya sucursal pertenece a zona
                        EXISTS (
                            SELECT 1 FROM clientes c
                            JOIN personas per ON c.persona_id = per.id
                            JOIN direcciones d2 ON per.id = d2.persona_id
                            JOIN zona_sucursal zs ON d2.sucursal_id = zs.sucursal_id
                            WHERE c.id = p.cliente_id
                            AND d2.tipo_direccion = 'principal'
                            AND zs.zona_id IN ($placeholders)
                        )
                    )
                )
            ", array_merge($zonaIds, $zonaIds, $zonaIds, $zonaIds));
        }

        return $query;
    }

    /**
     * Scope para buscar por cliente (nombre, apellido, DNI)
     */
    public function scopeBuscarCliente($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->whereHas('prestamo.cliente.persona', function ($q) use ($search) {
            $q->where('nombres', 'like', "%{$search}%")
                ->orWhere('ape_pat', 'like', "%{$search}%")
                ->orWhere('ape_mat', 'like', "%{$search}%")
                ->orWhere('documento', 'like', "%{$search}%");
        });
    }

    /**
     * Scope para filtrar por rango de fechas de vencimiento
     */
    public function scopePorFechaVencimiento($query, $desde = null, $hasta = null)
    {
        if ($desde) {
            $query->where('fecha_pago', '>=', Carbon::parse($desde)->startOfDay());
        }

        if ($hasta) {
            $query->where('fecha_pago', '<=', Carbon::parse($hasta)->endOfDay());
        }

        return $query;
    }

    /**
     * Scope para filtrar por días de mora
     */
    public function scopePorDiasMora($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->whereHas('moras', function ($q) use ($min) {
                $q->where('dias_mora', '>=', $min);
            });
        }

        if ($max !== null) {
            $query->whereHas('moras', function ($q) use ($max) {
                $q->where('dias_mora', '<=', $max);
            });
        }

        return $query;
    }

    /**
     * Scope para excluir préstamos con convenios activos
     */
    public function scopeSinConveniosActivos($query)
    {
        // Optimizado: usar NOT IN con subquery directa en vez de whereDoesntHave anidado
        // whereDoesntHave('prestamo.convenios') genera subconsultas correlacionadas muy lentas
        return $query->whereNotIn('cuotas.prestamo_id', function ($subquery) {
            $subquery->select('prestamo_id')
                ->from('convenios')
                ->where('estado', \App\Enums\ConvenioEstado::ACTIVO->value);
        });
    }
}
