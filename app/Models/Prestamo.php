<?php

namespace App\Models;

use App\Services\MoraService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Prestamo extends Model
{
    use HasFactory;

    protected $fillable = [
        'persona_id',
        'cliente_id',
        'direccion_cobro_id',
        'solicitud_id',
        'estado',
        'tipo_solicitud',
        'cuenta_id',
        'cuenta_cliente_id',
        'direccion_cobro_id',
        'fecha_atencion',
        'fecha_primer_pago',
        'cantidad_solicitada',
        'plazo',
        'tasa_interes',
        'igv',
        'frecuencia_pago',
        'observaciones',
        'tiene_comprobante',
        'mora',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'fecha_atencion' => 'datetime',
        'fecha_primer_pago' => 'datetime',
    ];

    /**
     * Obtiene el cliente al que le pertenece el préstamo.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Obtiene la cuenta asociada al préstamo.
     */
    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    /**
     * Obtiene el aval del préstamo.
     */
    public function aval(): HasOne
    {
        return $this->hasOne(Aval::class);
    }

    /**
     * Obtiene las carteras de JCC a las que pertenece el contrato.
     */
    public function carterasJcc(): HasMany
    {
        return $this->hasMany(CarteraJcc::class);
    }

    /**
     * Obtiene las carteras de asesor a las que pertenece el contrato.
     */
    public function carterasAsesor(): HasMany
    {
        return $this->hasMany(CarteraAsesor::class);
    }

    /**
     * Obtiene las carteras de analista a las que pertenece el contrato.
     */
    public function carterasAnalista(): HasMany
    {
        return $this->hasMany(CarteraAnalista::class);
    }

    /**
     * Obtiene el contrato del préstamo.
     */
    public function contrato(): HasOne
    {
        return $this->hasOne(Compromiso::class);
    }

    /**
     * Obtiene los compromisos generados por el contrato.
     */
    public function compromisos(): HasMany
    {
        return $this->hasMany(Compromiso::class);
    }

    /**
     * Obtiene las cuotas del préstamo.
     */
    public function cuotas(): HasMany
    {
        return $this->hasMany(Cuota::class);
    }

    /**
     * Obtiene las gestiones del préstamo.
     */
    public function gestiones(): HasMany
    {
        return $this->hasMany(Gestion::class);
    }

    /**
     * Obtiene las operaciones del préstamo.
     */
    public function operaciones(): HasMany
    {
        return $this->hasMany(Operacion::class);
    }

    /**
     * Obtiene la última operación registrada del préstamo.
     */
    public function latestOperation(): HasOne
    {
        return $this->hasOne(Operacion::class)->latestOfMany('fecha');
    }

    /**
     * Obtiene la última operación de pago de cuota del préstamo.
     */
    public function latestCuotaPayment(): HasOne
    {
        return $this->hasOne(Operacion::class)
            ->where('tipo_operacion', 'Pago de cuota')
            ->where('estado', '!=', 'anulado')
            ->latestOfMany('fecha');
    }

    /**
     * Obtiene la última operación de pago de mora del préstamo.
     */
    public function latestMoraPayment(): HasOne
    {
        return $this->hasOne(Operacion::class)
            ->where('tipo_operacion', 'Pago de mora')
            ->where('estado', '!=', 'anulado')
            ->latestOfMany('fecha');
    }

    /**
     * Obtiene las moras del préstamo a través de las cuotas.
     */
    public function moras()
    {
        return $this->hasManyThrough(MoraCuota::class, Cuota::class);
    }

    /**
     * Obtiene los convenios de pago del préstamo.
     */
    public function convenios(): HasMany
    {
        return $this->hasMany(Convenio::class);
    }

    /**
     * Obtiene la cuenta del cliente asociada al préstamo.
     */
    public function cuentaCliente(): BelongsTo
    {
        return $this->belongsTo(CuentaCliente::class, 'cuenta_cliente_id', 'id');
    }

    /**
     * Obtiene la dirección de cobro específica del préstamo.
     */
    public function direccionCobro(): BelongsTo
    {
        return $this->belongsTo(Direccion::class, 'direccion_cobro_id');
    }

    public function descuentos()
    {
        return $this->hasMany(Descuento::class, 'prestamo_id', 'id');
    }

    /**
     * Obtiene el fondo provisional del préstamo.
     */
    public function fondoProvisional(): HasOne
    {
        return $this->hasOne(FondoProvisional::class);
    }

    /**
     * Verifica y genera moras automáticamente para cuotas vencidas sin moras
     * También corrige moras mal regularizadas para cuotas pagadas tardíamente
     * Se ejecuta al cargar un préstamo para asegurar que tenga todas las moras correspondientes
     *
     * @return array Resultado de la verificación y generación
     */
    public function verificarYGenerarMoras(): array
    {
        try {
            $moraService = new MoraService;
            $hoy = Carbon::today();

            $resultados = [
                'cuotas_revisadas' => 0,
                'moras_generadas' => 0,
                'moras_corregidas' => 0,
                'detalles' => [],
            ];

            // 1. Buscar cuotas vencidas pendientes/parciales para generar moras faltantes
            $cuotasVencidas = $this->cuotas()
                ->with(['moras'])
                ->whereIn('estado', [\App\Enums\CuotaEstado::PENDIENTE, \App\Enums\CuotaEstado::PARCIAL])
                ->where('fecha_pago', '<', $hoy)
                ->get();

            foreach ($cuotasVencidas as $cuota) {
                $resultados['cuotas_revisadas']++;

                $fechaVencimiento = Carbon::parse($cuota->fecha_pago)->startOfDay();
                $diasVencidos = $fechaVencimiento->diffInDays($hoy);
                // CORRECCIÓN: Contar solo moras realmente pendientes para cuotas parciales
                $morasRealmentePendientes = $cuota->moras()
                    ->whereIn('estado', [\App\Enums\MoraCuotaEstado::PENDIENTE, \App\Enums\MoraCuotaEstado::PARCIAL])
                    ->count();

                // Verificar si faltan moras por generar
                $morasFaltantes = min(7, $diasVencidos) - $morasRealmentePendientes;

                if ($morasFaltantes > 0) {
                    $resultado = $moraService->procesarCuotaParaMoras($cuota);

                    if ($resultado['generadas'] > 0) {
                        $resultados['moras_generadas'] += $resultado['generadas'];
                        $resultados['detalles'][] = [
                            'cuota_id' => $cuota->id,
                            'cuota_numero' => $cuota->numero,
                            'accion' => 'moras_generadas',
                            'cantidad' => $resultado['generadas'],
                        ];
                    }
                }
            }

            // 2. Buscar cuotas PAGADAS/PARCIALES que tienen moras regularizadas incorrectamente
            $cuotasPagadas = $this->cuotas()
                ->with([
                    'moras',
                    'operaciones' => function ($query) {
                        $query->where('tipo_operacion', 'Pago de cuota')
                            ->where('estado', '!=', 'anulado')
                            ->orderBy('fecha', 'asc');
                    }
                ])
                ->whereIn('estado', [\App\Enums\CuotaEstado::PAGADO, \App\Enums\CuotaEstado::PARCIAL])
                ->whereHas('moras', function ($query) {
                    $query->where('estado', \App\Enums\MoraCuotaEstado::REGULARIZADA);
                })
                ->get();

            foreach ($cuotasPagadas as $cuota) {
                $resultados['cuotas_revisadas']++;

                $fechaVencimiento = Carbon::parse($cuota->fecha_pago)->startOfDay();

                // CASO A: Cuota PARCIAL - Siempre debe tener moras por estar incompleta
                if ($cuota->estado == \App\Enums\CuotaEstado::PARCIAL) {
                    // Para cuotas parciales, usar el primer pago para referencia
                    $primerPago = $cuota->operaciones->first();
                    if (!$primerPago) {
                        continue;
                    }

                    $fechaPagoReal = Carbon::parse($primerPago->fecha)->startOfDay();

                    // Para cuotas parciales, las moras deben estar PENDIENTES independientemente del tiempo de pago
                    $morasCorregidas = $this->corregirMorasCuotaParcial($cuota);

                    if ($morasCorregidas > 0) {
                        $resultados['moras_corregidas'] += $morasCorregidas;
                        $resultados['detalles'][] = [
                            'cuota_id' => $cuota->id,
                            'cuota_numero' => $cuota->numero,
                            'accion' => 'moras_corregidas_parcial',
                            'cantidad' => $morasCorregidas,
                            'fecha_pago_real' => $fechaPagoReal->format('Y-m-d'),
                        ];
                    }
                }
                // CASO B: Cuota PAGADA - Usar el ÚLTIMO pago que completó la cuota
                else {
                    // Para cuotas completadas, usar el último pago que completó la cuota
                    $ultimoPago = $cuota->operaciones->last();
                    if (!$ultimoPago) {
                        continue;
                    }

                    $fechaPagoReal = Carbon::parse($ultimoPago->fecha)->startOfDay();

                    if ($fechaPagoReal->gt($fechaVencimiento)) {
                        // Pago tardío - Corregir moras según fecha del último pago que completó la cuota
                        $diasRetraso = $fechaVencimiento->diffInDays($fechaPagoReal);
                        $morasCorregidas = $this->corregirMorasRegularizadas($cuota, $fechaPagoReal, $diasRetraso);

                        if ($morasCorregidas > 0) {
                            $resultados['moras_corregidas'] += $morasCorregidas;
                            $resultados['detalles'][] = [
                                'cuota_id' => $cuota->id,
                                'cuota_numero' => $cuota->numero,
                                'accion' => 'moras_corregidas',
                                'cantidad' => $morasCorregidas,
                                'fecha_pago_real' => $fechaPagoReal->format('Y-m-d'),
                                'dias_retraso' => $diasRetraso,
                            ];
                        }
                    } else {
                        // Pago a tiempo - Las moras deben estar regularizadas (ya está correcto)
                        \Log::info("Cuota {$cuota->numero} del préstamo {$this->id} pagada a tiempo - moras correctamente regularizadas");
                    }
                }
            }

            // 3. Buscar cuotas PAGADAS/PARCIALES que NO TIENEN moras (pero deberían tenerlas)
            $cuotasSinMoras = $this->cuotas()
                ->with([
                    'operaciones' => function ($query) {
                        $query->where('tipo_operacion', 'Pago de cuota')
                            ->where('estado', '!=', 'anulado')
                            ->orderBy('fecha', 'asc');
                    }
                ])
                ->whereIn('estado', [\App\Enums\CuotaEstado::PAGADO, \App\Enums\CuotaEstado::PARCIAL])
                ->whereDoesntHave('moras') // QUE NO TENGAN MORAS
                ->get();

            foreach ($cuotasSinMoras as $cuota) {
                $resultados['cuotas_revisadas']++;

                $fechaVencimiento = Carbon::parse($cuota->fecha_pago)->startOfDay();

                // CASO A: Cuota PARCIAL sin moras - Siempre debe generar moras
                if ($cuota->estado == \App\Enums\CuotaEstado::PARCIAL) {
                    // Las cuotas parciales siempre generan moras por estar incompletas
                    $diasVencidos = max(1, $fechaVencimiento->lt(Carbon::today()) ?
                        $fechaVencimiento->diffInDays(Carbon::today()) : 1);

                    $this->generarMorasParaCuota($cuota, $moraService, $diasVencidos, $resultados, 'parcial_sin_moras');
                }
                // CASO B: Cuota PAGADA sin moras - Usar el ÚLTIMO pago para verificar si fue tarde
                elseif ($cuota->estado == \App\Enums\CuotaEstado::PAGADO) {
                    // Para cuotas completadas, usar el último pago que completó la cuota
                    $ultimoPago = $cuota->operaciones->last();
                    if (!$ultimoPago) {
                        continue;
                    }

                    $fechaPagoReal = Carbon::parse($ultimoPago->fecha)->startOfDay();

                    if ($fechaPagoReal->gt($fechaVencimiento)) {
                        $diasRetraso = $fechaVencimiento->diffInDays($fechaPagoReal);
                        $this->generarMorasParaCuotaPagadaTarde($cuota, $moraService, $diasRetraso, $fechaPagoReal, $resultados);
                    }
                }
            }

            // Actualizar el estado del préstamo si es necesario
            if (($resultados['moras_generadas'] > 0 || $resultados['moras_corregidas'] > 0) && $this->estado == 'Vigente') {
                $this->update(['estado' => 'Moroso']);
                \Log::info("Préstamo {$this->id} marcado como Moroso por verificación automática de moras");
            }

            if ($resultados['moras_generadas'] > 0 || $resultados['moras_corregidas'] > 0) {
                \Log::info("Verificación automática de moras completada para préstamo {$this->id}", $resultados);
            }

            return $resultados;

        } catch (\Exception $e) {
            \Log::error("Error verificando y generando moras para préstamo {$this->id}: " . $e->getMessage());

            return [
                'cuotas_revisadas' => 0,
                'moras_generadas' => 0,
                'moras_corregidas' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Corrige las moras de una cuota pagada tardíamente
     * Las moras hasta la fecha de pago deben estar PENDIENTES
     * Las moras posteriores al pago deben estar REGULARIZADAS
     *
     * @param  \App\Models\Cuota  $cuota
     * @param  Carbon  $fechaPagoReal
     * @param  int  $diasRetraso
     * @return int Cantidad de moras corregidas
     */
    private function corregirMorasRegularizadas($cuota, $fechaPagoReal, $diasRetraso): int
    {
        $morasCorregidas = 0;

        // Obtener todas las moras de la cuota ordenadas por fecha
        $moras = $cuota->moras()->orderBy('fecha')->get();

        foreach ($moras as $mora) {
            $fechaMora = Carbon::parse($mora->fecha)->startOfDay();

            // Si la mora es HASTA la fecha de pago (inclusive), debe estar PENDIENTE
            if ($fechaMora->lte($fechaPagoReal)) {
                if ($mora->estado == \App\Enums\MoraCuotaEstado::REGULARIZADA) {
                    // Cambiar de REGULARIZADA a PENDIENTE
                    // IMPORTANTE: NO resetear monto_pagado si ya tiene pagos registrados
                    $montoPagado = $mora->monto_pagado ?? 0;

                    // Determinar el estado correcto basado en el monto pagado
                    if ($montoPagado >= $mora->monto) {
                        $nuevoEstado = \App\Enums\MoraCuotaEstado::PAGADO;
                    } elseif ($montoPagado > 0) {
                        $nuevoEstado = \App\Enums\MoraCuotaEstado::PARCIAL;
                    } else {
                        $nuevoEstado = \App\Enums\MoraCuotaEstado::PENDIENTE;
                    }

                    $mora->update(['estado' => $nuevoEstado]);

                    $morasCorregidas++;
                    \Log::info("Mora {$mora->id} corregida: REGULARIZADA → {$nuevoEstado->name} (cuota {$cuota->numero} pagada tardíamente)");
                }
            }
            // Si la mora es DESPUÉS de la fecha de pago, debe estar REGULARIZADA
            else {
                if ($mora->estado != \App\Enums\MoraCuotaEstado::REGULARIZADA) {
                    $mora->update(['estado' => \App\Enums\MoraCuotaEstado::REGULARIZADA]);
                    \Log::info("Mora {$mora->id} regularizada: posterior a fecha de pago (cuota {$cuota->numero})");
                }
            }
        }

        // Actualizar cantidad_mora de la cuota
        if ($morasCorregidas > 0) {
            $totalMorasPendientes = $cuota->moras()
                ->whereIn('estado', [\App\Enums\MoraCuotaEstado::PENDIENTE, \App\Enums\MoraCuotaEstado::PARCIAL])
                ->sum('monto');
            $cuota->update(['cantidad_mora' => $totalMorasPendientes]);
        }

        return $morasCorregidas;
    }

    /**
     * Corrige las moras de una cuota PARCIAL
     * Las cuotas parciales siempre deben tener moras PENDIENTES por estar incompletas
     *
     * @param  \App\Models\Cuota  $cuota
     * @return int Cantidad de moras corregidas
     */
    private function corregirMorasCuotaParcial($cuota): int
    {
        $morasCorregidas = 0;

        // Para cuotas parciales, TODAS las moras deben estar PENDIENTES
        $morasRegularizadas = $cuota->moras()
            ->where('estado', \App\Enums\MoraCuotaEstado::REGULARIZADA)
            ->get();

        foreach ($morasRegularizadas as $mora) {
            // IMPORTANTE: NO resetear monto_pagado si ya tiene pagos registrados
            $montoPagado = $mora->monto_pagado ?? 0;

            // Determinar el estado correcto basado en el monto pagado
            if ($montoPagado >= $mora->monto) {
                $nuevoEstado = \App\Enums\MoraCuotaEstado::PAGADO;
            } elseif ($montoPagado > 0) {
                $nuevoEstado = \App\Enums\MoraCuotaEstado::PARCIAL;
            } else {
                $nuevoEstado = \App\Enums\MoraCuotaEstado::PENDIENTE;
            }

            $mora->update(['estado' => $nuevoEstado]);

            $morasCorregidas++;
            \Log::info("Mora {$mora->id} corregida: REGULARIZADA → {$nuevoEstado->name} (cuota {$cuota->numero} es PARCIAL)");
        }

        // Actualizar cantidad_mora
        if ($morasCorregidas > 0) {
            $totalMorasPendientes = $cuota->moras()
                ->whereIn('estado', [\App\Enums\MoraCuotaEstado::PENDIENTE, \App\Enums\MoraCuotaEstado::PARCIAL])
                ->sum('monto');
            $cuota->update(['cantidad_mora' => $totalMorasPendientes]);
        }

        return $morasCorregidas;
    }

    /**
     * Genera moras para una cuota usando el servicio
     *
     * @param  \App\Models\Cuota  $cuota
     * @param  \App\Services\MoraService  $moraService
     * @param  int  $dias
     * @param  array  &$resultados
     * @param  string  $accion
     */
    private function generarMorasParaCuota($cuota, $moraService, $dias, &$resultados, $accion)
    {
        $estadoOriginal = $cuota->estado;
        $cuota->update(['estado' => \App\Enums\CuotaEstado::PARCIAL]);

        $resultado = $moraService->procesarCuotaParaMoras($cuota);

        $cuota->update(['estado' => $estadoOriginal]);

        if ($resultado['generadas'] > 0) {
            // Para cuotas parciales, todas las moras deben ser PENDIENTES
            if ($accion === 'parcial_sin_moras') {
                $cuota->moras()->update(['estado' => \App\Enums\MoraCuotaEstado::PENDIENTE]);
            }

            $resultados['moras_generadas'] += $resultado['generadas'];
            $resultados['detalles'][] = [
                'cuota_id' => $cuota->id,
                'cuota_numero' => $cuota->numero,
                'accion' => $accion,
                'cantidad' => $resultado['generadas'],
            ];

            \Log::info("Moras generadas para cuota {$accion}: Préstamo {$this->id}, Cuota {$cuota->numero}, {$resultado['generadas']} moras");
        }
    }

    /**
     * Genera moras para una cuota pagada tardíamente
     *
     * @param  \App\Models\Cuota  $cuota
     * @param  \App\Services\MoraService  $moraService
     * @param  int  $diasRetraso
     * @param  Carbon  $fechaPagoReal
     * @param  array  &$resultados
     */
    private function generarMorasParaCuotaPagadaTarde($cuota, $moraService, $diasRetraso, $fechaPagoReal, &$resultados)
    {
        $estadoOriginal = $cuota->estado;
        $cuota->update(['estado' => \App\Enums\CuotaEstado::PARCIAL]);

        $resultado = $moraService->procesarCuotaParaMoras($cuota);

        $cuota->update(['estado' => $estadoOriginal]);

        if ($resultado['generadas'] > 0) {
            // Configurar estados según fecha de pago
            $morasGeneradas = $cuota->moras()->orderBy('fecha')->get();

            foreach ($morasGeneradas as $mora) {
                $fechaMora = Carbon::parse($mora->fecha)->startOfDay();

                if ($fechaMora->lte($fechaPagoReal)) {
                    $mora->update(['estado' => \App\Enums\MoraCuotaEstado::PENDIENTE]);
                } else {
                    $mora->update(['estado' => \App\Enums\MoraCuotaEstado::REGULARIZADA]);
                }
            }

            // Actualizar cantidad_mora
            $totalMorasPendientes = $cuota->moras()
                ->whereIn('estado', [\App\Enums\MoraCuotaEstado::PENDIENTE, \App\Enums\MoraCuotaEstado::PARCIAL])
                ->sum('monto');
            $cuota->update(['cantidad_mora' => $totalMorasPendientes]);

            $resultados['moras_generadas'] += $resultado['generadas'];
            $resultados['detalles'][] = [
                'cuota_id' => $cuota->id,
                'cuota_numero' => $cuota->numero,
                'accion' => 'moras_generadas_tardias',
                'cantidad' => $resultado['generadas'],
                'fecha_pago_real' => $fechaPagoReal->format('Y-m-d'),
                'dias_retraso' => $diasRetraso,
            ];

            \Log::info("Moras generadas para cuota pagada tardíamente: Préstamo {$this->id}, Cuota {$cuota->numero}, {$resultado['generadas']} moras por {$diasRetraso} días de retraso");
        }
    }

    //LIQUIDACIONES
    public function liquidar(Request $request, $id)
    {
        try {
            \Log::info("Iniciando liquidación para Prestamo ID: {$id}");

            $prestamo = Prestamo::with('cuotas.operaciones')->findOrFail($id);
            $liquidacion = $prestamo->calcularLiquidacion();

            foreach ($prestamo->cuotas as $cuota) {
                if ($cuota->estado !== 'pagado') {
                    \Log::info("Actualizando cuota ID: {$cuota->id} como pagada.");

                    $cuota->update(['estado' => 'pagado']);
                }
            }

            $prestamo->update(['estado' => 'liquidado']);

            \Log::info("Préstamo liquidado exitosamente. Prestamo ID: {$id}");

            return response()->json([
                'success' => true,
                'message' => 'Préstamo liquidado exitosamente',
            ]);
        } catch (\Exception $e) {
            \Log::error("Error al liquidar el préstamo. Detalles: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'error' => 'Error al liquidar el préstamo. ' . $e->getMessage(),
            ], 500);
        }
    }

    public function operacionesGenerales()
    {
        return $this->operaciones()
            ->whereDoesntHave('cuotas');
    }

    /**
     * Obtiene los documentos asociados al préstamo.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(PrestamoDocument::class);
    }

    /**
     * Obtiene las etiquetas asociadas al préstamo a través de la tabla pivot.
     */
    public function etiquetas()
    {
        return $this->hasManyThrough(
            Etiqueta::class,
            EtiquetaCliente::class,
            'prestamo_id',  // Foreign key en etiquetas_cliente
            'id',           // Foreign key en etiquetas
            'id',           // Local key en prestamos
            'etiqueta_id'   // Local key en etiquetas_cliente
        );
    }

    /**
     * Obtiene los registros de etiquetas_cliente para este préstamo.
     */
    public function etiquetasCliente(): HasMany
    {
        return $this->hasMany(EtiquetaCliente::class);
    }

    /**
     * Accessor para obtener el número de préstamo
     * Formatea el ID del préstamo con un prefijo para usarlo en comprobantes
     */
    public function getNumeroPrestamoAttribute(): string
    {
        return 'PRES-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }
}