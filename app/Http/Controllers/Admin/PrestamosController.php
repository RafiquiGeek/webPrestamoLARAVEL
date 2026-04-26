<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConvenioEstado;
use App\Enums\CuotaEstado;
use App\Enums\DescuentoEstado;
use App\Enums\MoraCuotaEstado;
use App\Http\Controllers\Controller;
use App\Models\ApiConfig;
use App\Models\Aval;
use App\Models\CarteraAnalista;
use App\Models\CarteraAsesor;
use App\Models\CarteraJcc;
use App\Models\Cliente;
use App\Models\Cuenta;
use App\Models\CuentaCliente;
use App\Models\Cuota;
use App\Models\Descuento;
use App\Models\Direccion;
use App\Models\Gestion;
use App\Models\MetodoDePago;
use App\Models\Mora;
use App\Models\MoraCuota;
use App\Models\Operacion;
use App\Models\OperacionCuota;
use App\Models\Persona;
use App\Models\Plazo;
use App\Models\Prestamo;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\MoraService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PrestamosController extends Controller
{

    public function index(Request $request)
    {
        $search = $request->input('search');
        $estado = $request->input('estado');
        $sucursal_id = $request->input('sucursal_id');
        $perPage = $request->input('per_page', 25);

        $prestamos = Prestamo::query()
            ->with([
                'cliente.persona.direcciones.sucursal',
                'etiquetas',
                'convenios',
            ])
            // FILTRO POR CARTERA: Solo para roles Asesor, Analista y JCC
            ->when($this->debeAplicarFiltroCartera(), function ($queryBuilder) {
                $userId = auth()->id();

                // IMPORTANTE: Si el usuario tiene múltiples roles, buscar en TODAS sus carteras
                // Usamos OR para que muestre préstamos de cualquier cartera donde esté asignado
                $queryBuilder->where(function ($subQuery) use ($userId) {
                    $hasConditions = false;

                    if (auth()->user()->hasRole('Asesor')) {
                        $subQuery->orWhereHas('carterasAsesor', function ($q) use ($userId) {
                            $q->where('asesor_id', $userId);
                        });
                        $hasConditions = true;
                    }
                    if (auth()->user()->hasRole('Analista')) {
                        $subQuery->orWhereHas('carterasAnalista', function ($q) use ($userId) {
                            $q->where('analista_id', $userId);
                        });
                        $hasConditions = true;
                    }
                    if (auth()->user()->hasRole('JCC')) {
                        $subQuery->orWhereHas('carterasJcc', function ($q) use ($userId) {
                            $q->where('jcc_id', $userId);
                        });
                        $hasConditions = true;
                    }

                    // Si no tiene ningún rol restringido, no aplicar filtro
                    if (!$hasConditions) {
                        $subQuery->whereRaw('1=1');
                    }
                });
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhereHas('cliente.persona', function ($query) use ($search) {
                            $query->where('nombres', 'like', "%{$search}%")
                                ->orWhere('ape_pat', 'like', "%{$search}%")
                                ->orWhere('ape_mat', 'like', "%{$search}%")
                                ->orWhere('documento', 'like', "%{$search}%");
                        });
                });
            })
            ->when($estado, function ($query, $estado) {
                if ($estado === 'Con Convenio') {
                    $query->whereHas('convenios', function ($convenioQuery) {
                        $convenioQuery->where('estado', ConvenioEstado::ACTIVO);
                    });
                } else {
                    $query->where('estado', $estado);
                }
            })
            ->when($sucursal_id, function ($query, $sucursal_id) {
                $query->whereHas('cliente', function ($clienteQuery) use ($sucursal_id) {
                    $clienteQuery->whereHas('persona.direcciones', function ($direccionQuery) use ($sucursal_id) {
                        $direccionQuery->where('sucursal_id', $sucursal_id)
                            ->where('estado', 1);
                    });
                });
            })
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        $sucursales = Sucursal::orderBy('sucursal')->get();

        $contadores = [
            'todos' => Prestamo::count(),
            'nueva_solicitud' => Prestamo::where('estado', 'Nueva Solicitud')->count(),
            'aprobado' => Prestamo::where('estado', 'Aprobado')->count(),
            'por_desembolsar' => Prestamo::where('estado', 'Por Desembolsar')->count(),
            'vigente' => Prestamo::where('estado', 'Vigente')->count(),
            'moroso' => Prestamo::where('estado', 'Moroso')->count(),
            'liquidado' => Prestamo::where('estado', 'Liquidado')->count(),
            'finalizado' => Prestamo::where('estado', 'Finalizado')->count(),
            'con_convenio' => Prestamo::whereHas('convenios', function ($query) {
                $query->where('estado', ConvenioEstado::ACTIVO);
            })->count(),
        ];

        if ($request->ajax()) {
            return view('admin.prestamos.partials.prestamos-table', compact('prestamos'))->render();
        }

        return view('admin.Prestamos.index', compact('prestamos', 'sucursales', 'contadores'));
    }

    public function mostrarDesembolso($id)
    {
        $prestamo = Prestamo::with([
            'cliente.persona',
            'cuenta',
        ])->findOrFail($id);

        if ($prestamo->estado !== 'Por Desembolsar') {
            return redirect()->route('admin.prestamos.show', $id)
                ->with('error', 'El préstamo no está en estado "Por Desembolsar".');
        }

        $metodosDePago = MetodoDePago::where('status', 1)->get();

        return view('admin.Prestamos.desembolso', compact('prestamo', 'metodosDePago'));
    }

    public function desembolsar(Request $request, $id)
    {
        try {
            $request->validate([
                'fecha_desembolso' => 'required|date',
                'metodo_pago_id' => 'required|exists:metodos_de_pago,id',
                'monto' => 'required|numeric|min:0',
                'observaciones' => 'nullable|string|max:500',
                'voucher' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
                'emitir_comprobante' => 'nullable|boolean',
            ]);

            $prestamo = Prestamo::with('cliente')->findOrFail($id);

            // VALIDACIÓN DE PERMISOS: Solo puede desembolsar si está en su cartera
            // Si tiene múltiples roles, buscar en TODAS sus carteras (OR)
            if ($this->debeAplicarFiltroCartera()) {
                $userId = auth()->id();
                $puedeDesembolsar = false;

                // Verificar en TODAS las carteras donde el usuario tenga roles asignados
                if (auth()->user()->hasRole('Asesor')) {
                    $puedeDesembolsar = $puedeDesembolsar || $prestamo->carterasAsesor()->where('asesor_id', $userId)->where('estado', 1)->exists();
                }
                if (auth()->user()->hasRole('Analista')) {
                    $puedeDesembolsar = $puedeDesembolsar || $prestamo->carterasAnalista()->where('analista_id', $userId)->where('estado', 1)->exists();
                }
                if (auth()->user()->hasRole('JCC')) {
                    $puedeDesembolsar = $puedeDesembolsar || $prestamo->carterasJcc()->where('jcc_id', $userId)->where('estado', 1)->exists();
                }

                if (!$puedeDesembolsar) {
                    return redirect()->route('admin.prestamos.show', $id)
                        ->with('error', 'No tienes permiso para desembolsar este préstamo. Solo puedes desembolsar préstamos de tu cartera.');
                }
            }

            if ($prestamo->estado !== 'Por Desembolsar') {
                return redirect()->route('admin.prestamos.show', $id)
                    ->with('error', 'El préstamo no está en estado "Por Desembolsar".');
            }

            if (! $prestamo->cliente_id || ! $prestamo->cliente) {
                return redirect()->route('admin.prestamos.show', $id)
                    ->with('error', 'El préstamo no tiene un cliente asociado válido.');
            }

            $voucherPath = null;
            if ($request->hasFile('voucher')) {
                $voucherPath = $request->file('voucher')->store('desembolsos', 'public');
            }

            $operacion = new Operacion;
            $operacion->prestamo_id = $prestamo->id;
            $operacion->cliente_id = $prestamo->cliente_id;
            $operacion->tipo_operacion = 'Desembolso';
            $operacion->fecha = $request->fecha_desembolso;
            $operacion->abono = $request->monto;
            $operacion->metodo_pago_id = $request->metodo_pago_id;
            $operacion->comentario = $request->observaciones;
            $operacion->voucher_path = $voucherPath;
            $operacion->user_id = auth()->id();
            $operacion->estado_rendicion = 'pendiente';

            $operacion->save();

            $prestamo->estado = 'Vigente';
            $prestamo->fecha_atencion = $request->fecha_desembolso; 
            $prestamo->save();

            $mensaje = 'Préstamo desembolsado exitosamente.';

            if ($request->has('desde_proceso') || $request->header('referer') && strpos($request->header('referer'), 'proceso-prestamo') !== false) {
                return redirect()->route('admin.proceso-prestamo.index', $id)
                    ->with('success', $mensaje);
            }

            return redirect()->route('admin.prestamos.show', $id)
                ->with('success', $mensaje);

        } catch (\Exception $e) {
            Log::error('Error al desembolsar préstamo: '.$e->getMessage());

            return redirect()->route('admin.prestamos.show', $id)
                ->with('error', 'Error al procesar el desembolso: '.$e->getMessage());
        }
    }

    public function aprobar(Request $request, $id)
    {
        try {
            $request->validate([
                'fecha_atencion' => 'required|date',
                'comentarios' => 'nullable|string|max:500',
            ]);

            $prestamo = Prestamo::findOrFail($id);

            // VALIDACIÓN DE PERMISOS: Solo puede aprobar si está en su cartera
            // Si tiene múltiples roles, buscar en TODAS sus carteras (OR)
            if ($this->debeAplicarFiltroCartera()) {
                $userId = auth()->id();
                $puedeAprobar = false;

                // Verificar en TODAS las carteras donde el usuario tenga roles asignados
                if (auth()->user()->hasRole('Asesor')) {
                    $puedeAprobar = $puedeAprobar || $prestamo->carterasAsesor()->where('asesor_id', $userId)->where('estado', 1)->exists();
                }
                if (auth()->user()->hasRole('Analista')) {
                    $puedeAprobar = $puedeAprobar || $prestamo->carterasAnalista()->where('analista_id', $userId)->where('estado', 1)->exists();
                }
                if (auth()->user()->hasRole('JCC')) {
                    $puedeAprobar = $puedeAprobar || $prestamo->carterasJcc()->where('jcc_id', $userId)->where('estado', 1)->exists();
                }

                if (!$puedeAprobar) {
                    return redirect()->route('admin.prestamos.show', $id)
                        ->with('error', 'No tienes permiso para aprobar este préstamo. Solo puedes aprobar préstamos de tu cartera.');
                }
            }

            if ($prestamo->estado !== 'Nueva Solicitud') {
                return redirect()->route('admin.prestamos.show', $id)
                    ->with('error', 'El préstamo no está en estado "Nueva Solicitud".');
            }

            $prestamo->update([
                'estado' => 'Por Desembolsar',
                'fecha_atencion' => $request->fecha_atencion,
                'aprobado_por' => auth()->id(),
                'comentarios_aprobacion' => $request->comentarios,
            ]);

            Log::info("Préstamo {$id} aprobado por usuario ".auth()->id());

            return redirect()->route('admin.prestamos.show', $id)
                ->with('success', 'Préstamo aprobado exitosamente. Ahora está listo para desembolso.');

        } catch (\Exception $e) {
            Log::error('Error al aprobar préstamo: '.$e->getMessage());

            return redirect()->route('admin.prestamos.show', $id)
                ->with('error', 'Error al procesar la aprobación: '.$e->getMessage());
        }
    }

    public function create()
    {
        try {
            $clientesFiltrados = Cliente::with(['persona.direcciones'])
                ->whereDoesntHave('prestamos', function ($query) {
                    $query->whereIn('estado', [
                        'En Análisis',
                        'Aprobado',
                        'Por Desembolsar',
                        'Vigente',
                        'Moroso',
                    ]);
                })
                ->get();

            $clientes = $clientesFiltrados;

            $cuentas = Cuenta::all();

            $analistas = User::role('Analista')->get();
            $asesores = User::role('Asesor')->get();
            $jccs = User::role('JCC')->get();

            $todasMoras = Mora::all();
            $moras = $todasMoras->filter(function ($mora) {
                return (string) $mora->status === '1';
            });

            $sucursales = \App\Models\Sucursal::all();
            $departamentos = \App\Models\Departamento::all();
            $entBancarias = \App\Models\EntidadBancaria::all();
            $tiposCuenta = \App\Models\TipoCuenta::all();

            return view('admin.Solicitudes.create', compact(
                'clientes',
                'cuentas',
                'analistas',
                'asesores',
                'jccs',
                'moras',
                'sucursales',
                'departamentos',
                'entBancarias',
                'tiposCuenta'
            ));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ocurrió un error al cargar el formulario. Contacte al administrador.');
        }
    }

    public function obtenerCuentas($clienteId)
    {
        try {
            $cuentas = CuentaCliente::where('cliente_id', $clienteId)->get();

            return response()->json(['cuentas' => $cuentas]);
        } catch (\Exception $e) {
            Log::error('Error al obtener las cuentas: '.$e->getMessage());

            return response()->json(['message' => 'Error al obtener las cuentas'], 500);
        }
    }

    public function obtenerDirecciones($clienteId)
    {
        try {
            $cliente = Cliente::with('persona.direcciones')->findOrFail($clienteId);
            Log::info('Cliente encontrado:', ['cliente' => $cliente]);

            return response()->json(['direcciones' => $cliente->persona->direcciones]);
        } catch (\Exception $e) {
            Log::error('Error al obtener las direcciones: '.$e->getMessage());

            return response()->json(['message' => 'Error al obtener las direcciones', 'error' => $e->getMessage()], 500);
        }
    }

    public function calcularCuotas(Request $request)
    {
        try {
            $validated = $request->validate([
                'monto' => 'required|numeric|min:0',
                'plazo' => 'required|integer|in:8,12,15,18,20', 
                'fechaPrimerPago' => 'required|date',
                'mora' => 'required|numeric|min:0',
            ]);

            $montoSolicitado = $validated['monto'];
            $plazo = $validated['plazo'];
            $fechaPrimerPago = Carbon::parse($validated['fechaPrimerPago']);
            $mora = $validated['mora'];

            Log::info('Calculando cuotas', [
                'monto' => $montoSolicitado,
                'plazo' => $plazo,
                'fechaPrimerPago' => $fechaPrimerPago->format('Y-m-d'),
            ]);

            if ($plazo == 8) {
                $resultado = $this->calcularCuotas8Semanas($montoSolicitado, $fechaPrimerPago);
            } else {
                $resultado = $this->calcularCuotasInterno($montoSolicitado, $plazo, $fechaPrimerPago);
            }

            Log::info('Cálculo de cuotas completado', [
                'total' => $resultado['total'] ?? 0,
                'num_cuotas' => count($resultado['cuotas'] ?? []),
            ]);

            return response()->json($resultado);
        } catch (\Exception $e) {
            Log::error('Error al calcular cuotas: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error al calcular las cuotas: '.$e->getMessage(),
            ], 500);
        }
    }

    public function calcularCuotas8Semanas($montoSolicitado, $fechaPrimerPago)
    {
        Log::info('Calculando cuotas para 8 semanas', [
            'montoSolicitado' => $montoSolicitado,
            'fechaPrimerPago' => $fechaPrimerPago,
        ]);

        $tasaSemanal = 0.0138;
        $comision = 0.0424;

        $fechaInicio = Carbon::parse($fechaPrimerPago);
        $factorTotal = 1.3;
        $proporcionCuotas = 7 / 6;
        $totalPagado = $montoSolicitado * $factorTotal;
        $cuotaUltimasCuatro = $totalPagado / (4 * ($proporcionCuotas + 1));
        $cuotaPrimerasCuatro = $cuotaUltimasCuatro * $proporcionCuotas;
        $cuotaPrimerasCuatro = round($cuotaPrimerasCuatro, 2);
        $cuotaUltimasCuatro = round($cuotaUltimasCuatro, 2);
        $saldoCapital = $montoSolicitado;
        $cuotas = [];
        $totalPagoCapital = 0;
        $totalInteres = 0;
        $totalComision = 0;
        $totalIGV = 0;

        for ($i = 1; $i <= 8; $i++) {
            $cuotaActual = ($i <= 4) ? $cuotaPrimerasCuatro : $cuotaUltimasCuatro;
            $interesCuota = round($tasaSemanal * $saldoCapital, 2);
            $comisionBruta = round($saldoCapital * $comision, 2);
            $igvCuota = round($comisionBruta * (0.18 / 1.18), 2);
            $comisionNeta = round($comisionBruta - $igvCuota, 2);
            $pagoCapital = round($cuotaActual - $interesCuota - $comisionBruta, 2);
            if ($pagoCapital <= 0) {
                $pagoCapital = max(round($saldoCapital * 0.1, 2), 1);
                $cuotaActual = round($pagoCapital + $interesCuota + $comisionNeta + $igvCuota, 2);
            }
            if ($i == 8 && abs($saldoCapital - $pagoCapital) > 0.01) {
                $diferencia = $saldoCapital - $pagoCapital;
                $pagoCapital = $saldoCapital;
            }
            $saldoCapital = max(0, round($saldoCapital - $pagoCapital, 2));
            $fechaPago = $fechaInicio->copy()->addWeeks($i - 1)->format('Y-m-d');
            $cuotas[] = [
                'numero' => $i,
                'fecha_pago' => $fechaPago,
                'cuota' => $cuotaActual,
                'pagoCapital' => $pagoCapital,
                'interes' => $interesCuota,
                'comision' => $comisionNeta,
                'comision_bruta' => $comisionBruta,
                'igv' => $igvCuota,
                'saldoCapital' => $saldoCapital,
            ];
            $totalPagoCapital += $pagoCapital;
            $totalInteres += $interesCuota;
            $totalComision += $comisionNeta;
            $totalIGV += $igvCuota;
        }

        if (abs($totalPagoCapital - $montoSolicitado) > 0.01) {
            $diferenciaCapital = $montoSolicitado - $totalPagoCapital;
            $cuotasAjustables = 7;
            $ajustePorCuota = round($diferenciaCapital / $cuotasAjustables, 2);
            $ajusteAcumulado = 0;

            for ($i = 0; $i < $cuotasAjustables; $i++) {
                $ajusteActual = ($i == $cuotasAjustables - 1)
                    ? ($diferenciaCapital - $ajusteAcumulado)
                    : $ajustePorCuota;

                $cuotas[$i]['pagoCapital'] += $ajusteActual;
                $cuotas[$i]['cuota'] = round(
                    $cuotas[$i]['pagoCapital'] +
                    $cuotas[$i]['interes'] +
                    $cuotas[$i]['comision'] +
                    $cuotas[$i]['igv'],
                    2
                );

                $ajusteAcumulado += $ajusteActual;
            }
            $saldoCapital = $montoSolicitado;
            for ($i = 0; $i < count($cuotas); $i++) {
                $saldoCapital = max(0, round($saldoCapital - $cuotas[$i]['pagoCapital'], 2));
                $cuotas[$i]['saldoCapital'] = $saldoCapital;
            }

            $totalPagoCapital = $montoSolicitado;
        }

        $sumatoriaCuotas = array_sum(array_column($cuotas, 'cuota'));

        return [
            'montoSolicitado' => $montoSolicitado,
            'plazo' => 8,
            'tasaSemanal' => $tasaSemanal * 100,
            'tasaComision' => $comision * 100,
            'total' => round($sumatoriaCuotas, 2),
            'valorCuota1_4' => $cuotaPrimerasCuatro,
            'valorCuota5_8' => $cuotaUltimasCuatro,
            'cuotas' => $cuotas,
            'sumatoriaCuotas' => round($sumatoriaCuotas, 2),
            'resumen' => [
                'totalPagoCapital' => round($totalPagoCapital, 2),
                'totalInteres' => round($totalInteres, 2),
                'totalComision' => round($totalComision, 2),
                'totalIGV' => round($totalIGV, 2),
                'totalPagado' => round($sumatoriaCuotas, 2),
            ],
        ];
    }

    /**
     * Redondea la cuota a valores más limpios y presentables
     */
    private function redondearCuotaLimpia($cuota)
    {
        // Si la cuota está muy cerca de un valor entero (±0.50), redondear al entero
        $cuotaRedondeada = round($cuota);
        $diferencia = abs($cuota - $cuotaRedondeada);

        // Si la diferencia es menor a 0.50, usar el valor entero
        if ($diferencia <= 0.50) {
            return (float) $cuotaRedondeada;
        }

        // Si no, redondear a 2 decimales normalmente
        return round($cuota, 2);
    }

    public function calcularCuotasInterno($montoSolicitado, $plazo, Carbon $fechaInicio)
    {
        // Definir parámetros por plazo según tabla de referencia
        $parametros = [
            12 => [
                'tasa_interes' => 0.01441,    // 1.44% - Interés semanal
                'tasa_comision' => 0.0467,   // 4.67% - Comisión bruta (incluye IGV)
                'cuota_factor' => null,      // Se calcula con PMT
            ],
            15 => [
                'tasa_interes' => 0.01441,    // 1.44% - Interés semanal
                'tasa_comision' => 0.04113,   // 4.11% - Comisión bruta (incluye IGV)
                'cuota_factor' => 1.5,       // Factor fijo: Total a pagar = Monto × 1.5
            ],
            18 => [
                'tasa_interes' => 0.01441,    // 1.44% - Interés semanal
                'tasa_comision' => 0.03224,   // 3.22% - Comisión bruta (incluye IGV)
                'cuota_factor' => 1.5,       // Factor fijo: Total a pagar = Monto × 1.5
            ],
            20 => [
                'tasa_interes' => 0.01441,    // 1.44% - Interés semanal
                'tasa_comision' => 0.02775,   // 2.77% - Comisión bruta (incluye IGV)
                'cuota_factor' => 1.5,       // Factor fijo: Total a pagar = Monto × 1.5
            ],
        ];

        if (! array_key_exists($plazo, $parametros)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'plazo' => 'Plazo no válido.',
            ]);
        }

        $params = $parametros[$plazo];
        $tasaInteres = $params['tasa_interes'];
        $tasaComision = $params['tasa_comision'];
        $cuotaFactor = $params['cuota_factor'];

        // Calcular la cuota fija con redondeo limpio
        if ($cuotaFactor !== null) {
            // Para plazos con factor fijo (ej: 15, 18, 20 semanas)
            $totalPagarExacto = $montoSolicitado * $cuotaFactor;
            $cuotaCalculada = $totalPagarExacto / $plazo;

            // Redondear a 2 decimales para que el total sea exacto
            $cuotaFija = round($cuotaCalculada, 2);
        } else {
            // Para plazos con PMT (12 semanas)
            $tasaTotalEfectiva = $tasaInteres + $tasaComision;
            $factor = $tasaTotalEfectiva * pow(1 + $tasaTotalEfectiva, $plazo) / (pow(1 + $tasaTotalEfectiva, $plazo) - 1);
            $cuotaCalculada = $montoSolicitado * $factor;
            $cuotaFija = $this->redondearCuotaLimpia($cuotaCalculada);
            $totalPagarExacto = $cuotaFija * $plazo;
        }

        // Generar tabla de amortización
        $saldoCapital = $montoSolicitado;
        $cuotas = [];
        $totalPagoCapital = 0;
        $totalInteres = 0;
        $totalComision = 0;
        $totalIGV = 0;

        for ($i = 1; $i <= $plazo; $i++) {
            // MÉTODO CORRECTO:
            // - Interés = Saldo Capital × 1.44%
            //   * Primera cuota: Saldo Capital = Monto del préstamo
            //   * Siguientes: Saldo Capital = Saldo de la cuota anterior
            // - Comisión = (Saldo Capital × 4.67%) / 1.18
            //   * Primera cuota: Saldo Capital = Monto del préstamo
            //   * Siguientes: Saldo Capital = Saldo de la cuota anterior
            // - IGV = Comisión × 0.18
            // - Pago Capital = Cuota - Interés - Comisión - IGV
            // - Saldo Capital = Saldo anterior - Pago capital

            // Calcular INTERÉS sobre el SALDO CAPITAL:
            // Primera cuota: sobre el monto del préstamo
            // Siguientes: sobre el saldo capital de la cuota anterior
            if ($i == 1) {
                $interesCuota = round($montoSolicitado * $tasaInteres, 2);
            } else {
                $interesCuota = round($saldoCapital * $tasaInteres, 2);
            }

            // Calcular COMISIÓN (sin IGV):
            // Comisión = (Tasa Comisión × Saldo Capital) / 1.18
            // Primera cuota: sobre el monto del préstamo
            // Siguientes: sobre el saldo capital de la cuota anterior
            if ($i == 1) {
                $comisionCuota = round(($tasaComision * $montoSolicitado) / 1.18, 2);
            } else {
                $comisionCuota = round(($tasaComision * $saldoCapital) / 1.18, 2);
            }

            // Calcular IGV sobre la comisión base
            $igvCuota = round($comisionCuota * 0.18, 2);

            // Calcular PAGO DE CAPITAL = Cuota Fija - Interés - Comisión - IGV
            $pagoCapital = round($cuotaFija - $interesCuota - $comisionCuota - $igvCuota, 2);

            // Ajuste para la última cuota: el pago de capital debe ser exactamente el saldo restante
            if ($i == $plazo) {
                $pagoCapital = $saldoCapital;
            }

            // Actualizar el saldo capital para la próxima iteración
            $saldoCapital = round($saldoCapital - $pagoCapital, 2);

            // Asegurar que no sea negativo
            if ($saldoCapital < 0) {
                $saldoCapital = 0;
            }

            // La cuota es FIJA (ya calculada antes del loop)
            $cuotaTotal = $cuotaFija;

            // Calcular fecha de pago
            $fechaPago = $fechaInicio->copy()->addWeeks($i - 1)->format('Y-m-d');

            // Agregar la cuota a la lista
            $cuotas[] = [
                'numero' => $i,
                'fecha_pago' => $fechaPago,
                'cuota' => $cuotaTotal,
                'pagoCapital' => $pagoCapital,
                'interes' => $interesCuota,
                'comision' => $comisionCuota,
                'comision_bruta' => $comisionCuota + $igvCuota,
                'igv' => $igvCuota,
                'saldoCapital' => $saldoCapital,
            ];

            // Actualizar totales
            $totalPagoCapital += $pagoCapital;
            $totalInteres += $interesCuota;
            $totalComision += $comisionCuota;
            $totalIGV += $igvCuota;
        }

        // Suma total de todos los pagos (cuota fija × número de cuotas)
        $totalPagar = $cuotaFija * $plazo;

        return [
            'montoSolicitado' => $montoSolicitado,
            'plazo' => $plazo,
            'tasaSemanal' => round(($tasaInteres + $tasaComision) * 100, 2),
            'tasaInteresBase' => round($tasaInteres * 100, 2),
            'tasaComisionBase' => round($tasaComision * 100, 2),
            'total' => round($totalPagar, 2),
            'valorCuota' => $cuotaFija,
            'cuotas' => $cuotas,
            'resumen' => [
                'totalPagoCapital' => round($totalPagoCapital, 2),
                'totalInteres' => round($totalInteres, 2),
                'totalComision' => round($totalComision, 2),
                'totalIGV' => round($totalIGV, 2),
                'totalPagado' => $totalPagar,
            ],
        ];
    }

    public function store(Request $request)
    {
        Log::info('=== LLEGÓ AL MÉTODO STORE DE PRESTAMOSCONTROLLER ===');
        Log::info('Datos recibidos:', $request->all());

        // Validación de datos - MEJORADA PARA AVAL CON VALIDACIÓN CONDICIONAL
        $rules = [
            'estado' => 'nullable|string|in:En Análisis,Aprobado,Finalizado',
            'cliente_id' => 'required|exists:clientes,id',
            'cuenta_id' => 'required|exists:cuentas,id',
            'direccion_cobro_id' => 'nullable|exists:direcciones,id',
            'tipo_solicitud' => 'required|string',
            'fecha_atencion' => 'required|date',
            'fecha_primer_pago' => 'required|date',
            'cantidad_solicitada' => 'required|numeric|min:1',
            'cuenta_cliente_id' => 'nullable|exists:cuentas_cliente,id',
            'plazo' => 'required|integer|in:8,12,15,18,20',
            'mora' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
            'frecuencia_pago' => 'nullable|string',
            'analista_id' => 'required|exists:users,id',
            'asesor_id' => 'required|exists:users,id',
            'jcc_id' => 'required|exists:users,id',

            // Campo base de aval
            'tiene_aval' => 'nullable|string|in:0,1',
        ];

        // Validación condicional de campos de aval
        if ($request->input('tiene_aval') === '1') {
            Log::info('Validando campos de aval porque tiene_aval = 1');

            $rules['aval_dni'] = 'required|string|size:8|regex:/^[0-9]{8}$/';
            $rules['aval_id'] = 'required|string|size:8';
            $rules['parentesco'] = 'nullable|string|max:255';
            $rules['observaciones_aval'] = 'nullable|string|max:500';
        } else {
            Log::info('NO validando campos de aval porque tiene_aval != 1');

            // Campos opcionales cuando no tiene aval
            $rules['aval_dni'] = 'nullable|string';
            $rules['aval_id'] = 'nullable|string';
            $rules['parentesco'] = 'nullable|string|max:255';
            $rules['observaciones_aval'] = 'nullable|string|max:500';
        }

        $validatedData = $request->validate($rules);

        Log::info('=== VALIDACIÓN COMPLETADA EXITOSAMENTE ===');

        // Obtener cliente
        $cliente = Cliente::findOrFail($validatedData['cliente_id']);

        // VALIDACIÓN DE RESTRICCIONES POR CARTERA
        // Solo para roles: Asesor, Analista y JCC
        if ($this->debeAplicarFiltroCartera()) {
            $userId = auth()->id();

            // Verificar si el cliente tiene préstamos previos
            $clienteTienePrestamos = Prestamo::where('cliente_id', $cliente->id)->exists();

            if ($clienteTienePrestamos) {
                // Es una RENOVACIÓN - Validar que el cliente esté en la cartera del usuario
                // Si tiene múltiples roles, buscar en TODAS sus carteras (OR)
                $clienteEnCartera = false;

                // Verificar en TODAS las carteras donde el usuario tenga roles asignados
                if (auth()->user()->hasRole('Asesor')) {
                    $clienteEnCartera = $clienteEnCartera || Prestamo::where('cliente_id', $cliente->id)
                        ->whereHas('carterasAsesor', function ($q) use ($userId) {
                            $q->where('asesor_id', $userId);
                        })->exists();
                }
                if (auth()->user()->hasRole('Analista')) {
                    $clienteEnCartera = $clienteEnCartera || Prestamo::where('cliente_id', $cliente->id)
                        ->whereHas('carterasAnalista', function ($q) use ($userId) {
                            $q->where('analista_id', $userId);
                        })->exists();
                }
                if (auth()->user()->hasRole('JCC')) {
                    $clienteEnCartera = $clienteEnCartera || Prestamo::where('cliente_id', $cliente->id)
                        ->whereHas('carterasJcc', function ($q) use ($userId) {
                            $q->where('jcc_id', $userId);
                        })->exists();
                }

                if (!$clienteEnCartera) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', '❌ No puedes crear préstamos para este cliente. El cliente ya tiene préstamos previos y no está en tu cartera.')
                        ->with('status', 'Error de permisos');
                }

                Log::info('✅ Validación de cartera aprobada: Cliente en cartera del usuario', [
                    'user_id' => $userId,
                    'cliente_id' => $cliente->id,
                ]);
            } else {
                // Es un cliente NUEVO - Permitir crear préstamo
                Log::info('✅ Cliente nuevo sin préstamos previos - Permitido', [
                    'user_id' => $userId,
                    'cliente_id' => $cliente->id,
                ]);
            }
        }

        // Asignar frecuencia de pago por defecto
        $validatedData['frecuencia_pago'] = $validatedData['frecuencia_pago'] ?? 'semanal';

        try {
            DB::beginTransaction();

            // Crear el préstamo
            $prestamo = Prestamo::create([
                'cliente_id' => $validatedData['cliente_id'],
                'direccion_cobro_id' => $validatedData['direccion_cobro_id'] ?? null,
                'estado' => 'Nueva Solicitud',
                'tipo_solicitud' => $validatedData['tipo_solicitud'],
                'cuenta_id' => $validatedData['cuenta_id'],
                'cuenta_cliente_id' => $validatedData['cuenta_cliente_id'] ?? null,
                'fecha_atencion' => $validatedData['fecha_atencion'],
                'fecha_primer_pago' => $validatedData['fecha_primer_pago'],
                'cantidad_solicitada' => $validatedData['cantidad_solicitada'],
                'plazo' => $validatedData['plazo'],
                'mora' => $validatedData['mora'],
                'frecuencia_pago' => $validatedData['frecuencia_pago'],
                'observaciones' => $validatedData['observaciones'],
            ]);

            Log::info('Préstamo creado con ID: '.$prestamo->id);

            // MANEJO DE AVAL MEJORADO CON VALIDACIÓN ROBUSTA
            if (isset($validatedData['tiene_aval']) && $validatedData['tiene_aval'] === '1') {
                Log::info('Procesando aval para el préstamo', [
                    'tiene_aval' => $validatedData['tiene_aval'],
                    'aval_dni' => $validatedData['aval_dni'] ?? 'NO PROPORCIONADO',
                    'aval_id' => $validatedData['aval_id'] ?? 'NO PROPORCIONADO',
                ]);

                // Validar que se hayan proporcionado los datos del aval
                if (empty($validatedData['aval_id']) || empty($validatedData['aval_dni'])) {
                    throw new \Exception('Se requieren los datos del aval (DNI y ID) cuando se selecciona "Tiene Aval".');
                }

                // Verificar que el DNI y el aval_id coincidan
                if ($validatedData['aval_dni'] !== $validatedData['aval_id']) {
                    throw new \Exception('El DNI del aval no coincide con el ID proporcionado.');
                }

                // Buscar la persona por DNI
                $personaAval = Persona::where('documento', $validatedData['aval_dni'])->first();

                if (! $personaAval) {
                    throw new \Exception('No se encontró la persona con el DNI proporcionado para el aval: '.$validatedData['aval_dni']);
                }

                // Verificar si la persona es un cliente (opcional para validaciones adicionales)
                $clienteAval = Cliente::where('persona_id', $personaAval->id)->first();

                // Si es cliente, hacer validaciones adicionales de préstamos activos
                if ($clienteAval) {
                    $prestamosActivos = $clienteAval->prestamos()->where('estado', '!=', 'Finalizado')->count();
                    Log::info('Aval es cliente con préstamos activos', [
                        'prestamos_activos' => $prestamosActivos,
                        'dni_aval' => $personaAval->documento,
                    ]);
                    // Aquí podrían agregarse más validaciones si es necesario
                } else {
                    Log::info('Aval es persona pero no cliente', [
                        'dni_aval' => $personaAval->documento,
                        'nombres' => $personaAval->nombres,
                    ]);
                }

                // Crear el registro de aval (funciona tanto para clientes como personas)
                $aval = Aval::create([
                    'prestamo_id' => $prestamo->id,
                    'persona_id' => $personaAval->id,
                    'parentesco' => $validatedData['parentesco'] ?? null,
                    'observaciones' => $validatedData['observaciones_aval'] ?? null,
                ]);

                Log::info('Aval creado correctamente', [
                    'prestamo_id' => $prestamo->id,
                    'aval_id' => $aval->id,
                    'persona_id' => $personaAval->id,
                    'dni_aval' => $personaAval->documento,
                    'nombre_aval' => $personaAval->nombres.' '.$personaAval->ape_pat.' '.$personaAval->ape_mat,
                ]);
            } else {
                Log::info('No se procesó aval para este préstamo', [
                    'tiene_aval' => $validatedData['tiene_aval'] ?? 'NULL',
                    'motivo' => 'El campo tiene_aval no es igual a "1"',
                ]);

                // Asegurar que no haya datos residuales de aval en la validación
                $validatedData['aval_dni'] = null;
                $validatedData['aval_id'] = null;
                $validatedData['parentesco'] = null;
                $validatedData['observaciones_aval'] = null;
            }

            // Calcular las cuotas
            if ($validatedData['plazo'] == 8) {
                $resultadoCuotas = $this->calcularCuotas8Semanas(
                    $validatedData['cantidad_solicitada'],
                    Carbon::parse($validatedData['fecha_primer_pago'])
                );
            } else {
                $resultadoCuotas = $this->calcularCuotasInterno(
                    $validatedData['cantidad_solicitada'],
                    $validatedData['plazo'],
                    Carbon::parse($validatedData['fecha_primer_pago'])
                );
            }

            // Verificar que el resultado contiene las cuotas
            if (! isset($resultadoCuotas['cuotas']) || ! is_array($resultadoCuotas['cuotas'])) {
                throw new \Exception('Error al calcular las cuotas.');
            }

            // Guardar las cuotas
            foreach ($resultadoCuotas['cuotas'] as $cuotaData) {
                Cuota::create([
                    'prestamo_id' => $prestamo->id,
                    'fecha_pago' => $cuotaData['fecha_pago'],
                    'numero' => $cuotaData['numero'],
                    'monto' => $cuotaData['cuota'],
                    'pago_capital' => $cuotaData['pagoCapital'] ?? null,
                    'interes' => $cuotaData['interes'] ?? null,
                    'comision' => $cuotaData['comision'] ?? null,
                    'igv' => $cuotaData['igv'] ?? null,
                    'cantidad_mora' => 0,
                    'estado' => 0, // Estado pendiente
                ]);
            }

            // Crear asignaciones de cartera
            CarteraAnalista::create([
                'prestamo_id' => $prestamo->id,
                'analista_id' => $validatedData['analista_id'],
                'fecha_registro' => now(),
                'estado' => 1,
            ]);

            CarteraAsesor::create([
                'prestamo_id' => $prestamo->id,
                'asesor_id' => $validatedData['asesor_id'],
                'fecha_registro' => now(),
                'estado' => 1,
            ]);

            CarteraJcc::create([
                'prestamo_id' => $prestamo->id,
                'jcc_id' => $validatedData['jcc_id'],
                'fecha_registro' => now(),
                'estado' => 1,
            ]);

            // Generar moras retroactivas para préstamos con fechas pasadas
            $this->generarMorasRetroactivas($prestamo);

            DB::commit();

            Log::info('Préstamo creado exitosamente con ID: '.$prestamo->id);

            return redirect()->route('admin.prestamos.index')->with('success', 'Préstamo creado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear préstamo: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());

            return redirect()->back()->withErrors(['error' => 'Error: '.$e->getMessage()])->withInput();
        }
    }

    /**
     * Muestra la ventana independiente de liquidación
     */
    public function mostrarVentanaLiquidacion($prestamo_id)
    {
        try {
            Log::info("Intentando abrir ventana de liquidación para préstamo ID: {$prestamo_id}");
            $prestamo = Prestamo::with(['cuotas', 'cuotas.moras', 'cliente.persona'])->findOrFail($prestamo_id);
            
            // Validar que el préstamo no esté ya liquidado
            if ($prestamo->estado === 'Liquidado' || $prestamo->estado === 'Finalizado' || $prestamo->estado === 'Pagado') {
                return redirect()->route('admin.prestamos.show', $prestamo_id)
                    ->with('warning', 'Este préstamo ya está liquidado. No se puede liquidar nuevamente.');
            }
            
            // Obtener cuotas pendientes (no pagadas o pagadas parcialmente)
            $cuotasPendientes = $prestamo->cuotas()
                ->where('estado', '!=', CuotaEstado::PAGADO->value)
                ->orWhereNull('estado')
                ->get();

            // Calcular saldos pendientes por cuota y filtrar las que realmente necesitan liquidación
            $totalCuotas = 0;
            $cuotasParaLiquidacion = collect();

            foreach ($cuotasPendientes as $cuota) {
                // El monto de la cuota ya incluye todo (capital + interés + comisión + igv)
                $montoTotalCuota = $cuota->monto;

                // Calcular el monto realmente pagado - Método 1: OperacionCuota
                $montoPagado = OperacionCuota::where('cuota_id', $cuota->id)
                    ->whereHas('operacion', function ($query) {
                        $query->where('estado', '!=', 'anulado');
                    })
                    ->sum('monto_aplicado');

                // Método 2: Si no hay datos en operaciones_cuota, usar relación directa
                if ($montoPagado == 0) {
                    $montoPagado = $cuota->operaciones()
                        ->where('estado', '!=', 'anulado')
                        ->sum('abono');
                }

                // Método 3: Fallback al campo monto_pagado de la tabla cuotas
                if ($montoPagado == 0 && $cuota->monto_pagado > 0) {
                    $montoPagado = $cuota->monto_pagado;
                }

                // Debug: Log para verificar cálculos
                Log::info("Liquidación - Cuota #{$cuota->id}: Monto total: {$montoTotalCuota}, Pagado: {$montoPagado}, Estado: ".($cuota->estado ? $cuota->estado->value : 'null'));

                // Actualizar el monto_pagado en el objeto para mostrarlo en la vista
                $cuota->monto_pagado = $montoPagado;

                $saldoPendiente = max(0, $montoTotalCuota - $montoPagado);

                // Solo incluir cuotas con saldo pendiente mayor a 0
                if ($saldoPendiente > 0) {
                    // Agregar el saldo pendiente como atributo temporal para usar en la vista
                    $cuota->saldo_pendiente = $saldoPendiente;
                    $cuotasParaLiquidacion->push($cuota);
                    $totalCuotas += $saldoPendiente;
                } else {
                    Log::info("Cuota #{$cuota->id} excluida de liquidación - saldo: {$saldoPendiente}");
                }
            }

            // Usar las cuotas filtradas
            $cuotasPendientes = $cuotasParaLiquidacion;

            // Obtener moras pendientes (solo PENDIENTE y PARCIAL, excluir REGULARIZADA y PAGADO)
            $morasPendientes = collect();
            foreach ($prestamo->cuotas as $cuota) {
                $moras = $cuota->moras()
                    ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                    ->get();
                $morasPendientes = $morasPendientes->merge($moras);
            }

            // Calcular total considerando pagos parciales (saldo pendiente)
            $totalMoras = $morasPendientes->sum(function($mora) {
                return $mora->saldo; // Usa el accessor que calcula monto - monto_pagado
            });

            // Obtener métodos de pago activos
            $metodosDePago = MetodoDePago::where('status', 1)->get();

            return view('admin.Prestamos.liquidacion-ventana', compact(
                'prestamo',
                'cuotasPendientes',
                'morasPendientes',
                'totalCuotas',
                'totalMoras',
                'metodosDePago'
            ));

        } catch (\Exception $e) {
            Log::error('Error al cargar ventana de liquidación: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error al cargar la ventana de liquidación');
        }
    }

    public function storeAval(Request $request)
    {
        $validatedData = $request->validate([
            'prestamo_id' => 'required|exists:prestamos,id',
            'persona_id' => 'required|exists:personas,id',
            'parentesco' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ]);

        try {
            // Crear el aval
            $aval = Aval::create([
                'prestamo_id' => $validatedData['prestamo_id'],
                'persona_id' => $validatedData['persona_id'],
                'parentesco' => $validatedData['parentesco'],
                'observaciones' => $validatedData['observaciones'],
            ]);

            // Devolver la respuesta con el ID del aval
            return response()->json([
                'success' => 'Aval asignado correctamente.',
                'avalId' => $aval->id,  // Devolver el ID del aval recién creado
                'nombreAval' => $aval->persona->nombres.' '.$aval->persona->ape_pat.' '.$aval->persona->ape_mat,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al asignar el aval: '.$e->getMessage());

            return response()->json(['error' => 'Error al asignar el aval.'], 500);
        }
    }

    public function validarPrestamosAntesDeCrear(Request $request)
    {
        $cliente = Cliente::findOrFail($request->cliente_id);

        // Validar si el cliente tiene préstamos activos en los estados restringidos
        $prestamosActivos = $cliente->prestamos()->whereIn('estado', ['Nueva Solicitud', 'Por Desembolsar', 'Vigente', 'Moroso'])->exists();
        if ($prestamosActivos) {
            return response()->json(['error' => 'El cliente tiene un préstamo activo en un estado restringido. No puede solicitar un nuevo préstamo.']);
        }

        // Validar si el aval tiene más de dos préstamos activos
        if ($request->has('aval_id')) {
            $aval = Persona::findOrFail($request->aval_id);
            $prestamosAval = $aval->prestamos()->where('estado', '!=', 'Finalizado')->count();
            if ($prestamosAval >= 2) {
                return response()->json(['error' => 'El aval tiene más de dos préstamos activos. No puede ser asignado a este préstamo.']);
            }
        }

        // Validar si el cónyuge tiene préstamos impagos o más de dos avalados
        if ($cliente->conyuge) {
            $conyuge = $cliente->conyuge;
            $prestamosImpagos = $conyuge->prestamos()->where('estado', '!=', 'Finalizado')->exists();
            if ($prestamosImpagos) {
                return response()->json(['error' => 'El cónyuge del cliente tiene préstamos impagos. No puede solicitar un nuevo préstamo.']);
            }

            $cantidadAvalados = $conyuge->avales()->count();
            if ($cantidadAvalados >= 2) {
                return response()->json(['error' => 'El cónyuge del cliente tiene más de dos avalados. No puede solicitar un nuevo préstamo.']);
            }
        }

        return response()->json(['success' => 'El cliente puede solicitar el préstamo.']);
    }

    public function validarAvalAntesDeAsignar(Request $request)
    {
        $avalId = $request->aval_id;

        // Paso 1: Buscar en la tabla personas
        $persona = Persona::where('documento', $avalId)->first();

        // Paso 2: Si no existe, consultar API y registrar automáticamente
        if (! $persona) {
            try {
                // Obtener configuración de API desde la base de datos
                $url = ApiConfig::getValue('dni_api_url', 'https://api.factiliza.com/v1/dni/info/{dni}');
                $token = ApiConfig::getValue('dni_api_token', 'ece92d9d2a2bb54717373740c3180e722cf7a94b5501ed01a263e21e64a4b6a7');

                if (! $url || ! $token) {
                    return response()->json(['error' => 'No hay configuración API disponible para validar DNI.']);
                }

                $finalUrl = str_replace('{dni}', $avalId, $url);
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$token,
                    'Accept' => 'application/json',
                ])->timeout(10)->get($finalUrl);

                if (! $response->successful() || ! $response->json()) {
                    return response()->json(['error' => 'No se pudo obtener información del DNI desde la API.']);
                }

                $data = $response->json();

                // Log para debug - ver qué datos devuelve la API
                Log::info("Respuesta de API DNI para {$avalId}", ['data' => $data]);

                // Validar diferentes estructuras de respuesta de API
                $nombres = null;
                $apellidoPaterno = null;
                $apellidoMaterno = null;
                $fechaNacimiento = null;

                // Estructura 1: nombres, apellidoPaterno, apellidoMaterno
                if (isset($data['nombres']) && isset($data['apellidoPaterno']) && isset($data['apellidoMaterno'])) {
                    $nombres = $data['nombres'];
                    $apellidoPaterno = $data['apellidoPaterno'];
                    $apellidoMaterno = $data['apellidoMaterno'];
                    $fechaNacimiento = $data['fechaNacimiento'] ?? null;
                }
                // Estructura 2: name, paternal_surname, maternal_surname
                elseif (isset($data['name']) && isset($data['paternal_surname']) && isset($data['maternal_surname'])) {
                    $nombres = $data['name'];
                    $apellidoPaterno = $data['paternal_surname'];
                    $apellidoMaterno = $data['maternal_surname'];
                    $fechaNacimiento = $data['birth_date'] ?? null;
                }
                // Estructura 3: primer_nombre, apellido_paterno, apellido_materno
                elseif (isset($data['primer_nombre']) && isset($data['apellido_paterno']) && isset($data['apellido_materno'])) {
                    $nombres = $data['primer_nombre'];
                    $apellidoPaterno = $data['apellido_paterno'];
                    $apellidoMaterno = $data['apellido_materno'];
                    $fechaNacimiento = $data['fecha_nacimiento'] ?? null;
                }
                // Estructura 4: response con data anidada (formato original)
                elseif (isset($data['success']) && $data['success'] && isset($data['data'])) {
                    $nestedData = $data['data'];
                    // Estructura 4a: data -> data -> nombres
                    if (isset($nestedData['data']) && isset($nestedData['data']['nombres']) && isset($nestedData['data']['apellido_paterno']) && isset($nestedData['data']['apellido_materno'])) {
                        $deepData = $nestedData['data'];
                        $nombres = $deepData['nombres'];
                        $apellidoPaterno = $deepData['apellido_paterno'];
                        $apellidoMaterno = $deepData['apellido_materno'];
                        $fechaNacimiento = $deepData['fecha_nacimiento'] ?? null;
                    }
                    // Estructura 4b: data -> nombres directo
                    elseif (isset($nestedData['nombres']) && isset($nestedData['apellidoPaterno']) && isset($nestedData['apellidoMaterno'])) {
                        $nombres = $nestedData['nombres'];
                        $apellidoPaterno = $nestedData['apellidoPaterno'];
                        $apellidoMaterno = $nestedData['apellidoMaterno'];
                        $fechaNacimiento = $nestedData['fechaNacimiento'] ?? null;
                    }
                    // Estructura 4c: data -> nombres con underscores
                    elseif (isset($nestedData['nombres']) && isset($nestedData['apellido_paterno']) && isset($nestedData['apellido_materno'])) {
                        $nombres = $nestedData['nombres'];
                        $apellidoPaterno = $nestedData['apellido_paterno'];
                        $apellidoMaterno = $nestedData['apellido_materno'];
                        $fechaNacimiento = $nestedData['fecha_nacimiento'] ?? null;
                    }
                } else {
                    return response()->json([
                        'error' => 'Los datos obtenidos de la API no tienen el formato esperado.',
                        'debug_data' => $data,
                    ]);
                }

                if (! $nombres || ! $apellidoPaterno || ! $apellidoMaterno) {
                    return response()->json([
                        'error' => 'Los datos obtenidos de la API están incompletos.',
                        'debug_data' => $data,
                    ]);
                }

                // Convertir fecha de nacimiento si existe
                $fechaNacimientoFormatted = null;
                if ($fechaNacimiento) {
                    try {
                        // Convertir formato DD/MM/YYYY a YYYY-MM-DD
                        if (strpos($fechaNacimiento, '/') !== false) {
                            $fechaParts = explode('/', $fechaNacimiento);
                            if (count($fechaParts) === 3) {
                                $fechaNacimientoFormatted = $fechaParts[2].'-'.str_pad($fechaParts[1], 2, '0', STR_PAD_LEFT).'-'.str_pad($fechaParts[0], 2, '0', STR_PAD_LEFT);
                            }
                        }
                        // Si ya está en formato YYYY-MM-DD, mantenerlo
                        elseif (strpos($fechaNacimiento, '-') !== false) {
                            $fechaNacimientoFormatted = $fechaNacimiento;
                        }
                    } catch (\Exception $e) {
                        // Si hay error en conversión, dejar como null
                        Log::warning("Error al convertir fecha de nacimiento: {$fechaNacimiento}");
                        $fechaNacimientoFormatted = null;
                    }
                }

                // Registrar automáticamente en la tabla personas
                $persona = Persona::create([
                    'documento' => $avalId,
                    'nombres' => $nombres,
                    'ape_pat' => $apellidoPaterno,
                    'ape_mat' => $apellidoMaterno,
                    'fecha_nacimiento' => $fechaNacimientoFormatted,
                ]);

                Log::info('Persona registrada automáticamente desde API', [
                    'dni' => $avalId,
                    'nombres' => $nombres,
                    'apellidos' => $apellidoPaterno.' '.$apellidoMaterno,
                ]);

            } catch (\Exception $e) {
                Log::error("Error al consultar API y registrar persona con DNI {$avalId}: ".$e->getMessage());

                return response()->json(['error' => 'Error al validar DNI: '.$e->getMessage()]);
            }
        }

        // Verificar si esta persona es un cliente
        $cliente = Cliente::where('persona_id', $persona->id)->first();

        if (! $cliente) {
            // Crear cliente mínimo si la persona no es cliente (solo registro como cliente vinculado a persona)
            try {
                $cliente = Cliente::create([
                    'persona_id' => $persona->id,
                    'codigo' => 'AUTO_'.$persona->id.'_'.time(),
                    'observaciones' => 'Cliente creado automáticamente al asignar aval',
                    'carga_familiar' => 0,
                ]);

                Log::info('Cliente creado automáticamente para persona al asignar aval: ID '.$cliente->id.' (persona '.$persona->id.')');
            } catch (\Exception $e) {
                Log::error('Error al crear cliente automáticamente: '.$e->getMessage());
                return response()->json(['error' => 'No se pudo crear el cliente asociado a la persona.'], 500);
            }
        }

        // Verificar los préstamos del cliente
        $prestamosCliente = $cliente->prestamos()->where('estado', '!=', 'Finalizado')->get();

        $prestamosActivos = [];
        $tieneDeuda = false;
        $detallesDeuda = [];

        foreach ($prestamosCliente as $prestamo) {
            $cuotasVencidas = $prestamo->cuotas()->where('estado', CuotaEstado::VENCIDO->value)->count();
            $cuotasPendientes = $prestamo->cuotas()->where('estado', CuotaEstado::PENDIENTE->value)->count();
            $cuotasParciales = $prestamo->cuotas()->where('estado', CuotaEstado::PARCIAL->value)->count();
            $totalCuotas = $prestamo->cuotas()->count();
            $cuotasPagadas = $prestamo->cuotas()->where('estado', CuotaEstado::PAGADO->value)->count();

            $prestamosActivos[] = [
                'id' => $prestamo->id,
                'estado' => $prestamo->estado,
                'monto' => $prestamo->monto,
                'cuotas_vencidas' => $cuotasVencidas,
                'cuotas_pendientes' => $cuotasPendientes,
                'cuotas_parciales' => $cuotasParciales,
                'total_cuotas' => $totalCuotas,
                'cuotas_pagadas' => $cuotasPagadas,
                'fecha_desembolso' => $prestamo->fecha_desembolso,
            ];

            if ($cuotasVencidas > 0 || $cuotasParciales > 0) {
                $tieneDeuda = true;
                $alertas = [];
                if ($cuotasVencidas > 0) {
                    $alertas[] = "{$cuotasVencidas} vencidas";
                }
                if ($cuotasParciales > 0) {
                    $alertas[] = "{$cuotasParciales} parciales";
                }
                $detallesDeuda[] = "Préstamo #{$prestamo->id}: ".implode(', ', $alertas)." de {$totalCuotas} total";
            }
        }

        // Verificar el cónyuge (si existe)
        $conyugeData = $cliente->conyuge;
        $prestamosConyuge = [];
        $tieneDeudaConyuge = false;
        $detallesConyuge = [];

        if ($conyugeData && $conyugeData->persona) {
            $clienteConyuge = Cliente::where('persona_id', $conyugeData->persona->id)->first();
            if ($clienteConyuge) {
                $prestamosConyugeData = $clienteConyuge->prestamos()->where('estado', '!=', 'Finalizado')->get();
                foreach ($prestamosConyugeData as $prestamo) {
                    $cuotasVencidas = $prestamo->cuotas()->where('estado', CuotaEstado::VENCIDO->value)->count();
                    $cuotasParciales = $prestamo->cuotas()->where('estado', CuotaEstado::PARCIAL->value)->count();
                    $totalCuotas = $prestamo->cuotas()->count();

                    $prestamosConyuge[] = [
                        'id' => $prestamo->id,
                        'estado' => $prestamo->estado,
                        'monto' => $prestamo->monto,
                        'cuotas_vencidas' => $cuotasVencidas,
                        'cuotas_parciales' => $cuotasParciales,
                        'total_cuotas' => $totalCuotas,
                    ];

                    if ($cuotasVencidas > 0 || $cuotasParciales > 0) {
                        $tieneDeudaConyuge = true;
                        $alertas = [];
                        if ($cuotasVencidas > 0) {
                            $alertas[] = "{$cuotasVencidas} vencidas";
                        }
                        if ($cuotasParciales > 0) {
                            $alertas[] = "{$cuotasParciales} parciales";
                        }
                        $detallesConyuge[] = "Préstamo #{$prestamo->id}: ".implode(', ', $alertas)." de {$totalCuotas} total";
                    }
                }
            }
        }

        // Verificar los avalados
        $avales = Aval::where('persona_id', $persona->id)->with('prestamo.cliente.persona')->get();
        $prestamosAvala = [];
        $tieneDeudaAvalados = false;
        $detallesAvalados = [];

        foreach ($avales as $aval) {
            if ($aval->prestamo && $aval->prestamo->estado !== 'Finalizado') {
                $prestamo = $aval->prestamo;
                $cuotasVencidas = $prestamo->cuotas()->where('estado', CuotaEstado::VENCIDO->value)->count();
                $cuotasParciales = $prestamo->cuotas()->where('estado', CuotaEstado::PARCIAL->value)->count();
                $totalCuotas = $prestamo->cuotas()->count();

                $prestamosAvala[] = [
                    'id' => $prestamo->id,
                    'estado' => $prestamo->estado,
                    'monto' => $prestamo->monto,
                    'cliente_nombre' => $prestamo->cliente->persona->nombres.' '.$prestamo->cliente->persona->ape_pat,
                    'cuotas_vencidas' => $cuotasVencidas,
                    'cuotas_parciales' => $cuotasParciales,
                    'total_cuotas' => $totalCuotas,
                ];

                if ($cuotasVencidas > 0 || $cuotasParciales > 0) {
                    $tieneDeudaAvalados = true;
                    $alertas = [];
                    if ($cuotasVencidas > 0) {
                        $alertas[] = "{$cuotasVencidas} vencidas";
                    }
                    if ($cuotasParciales > 0) {
                        $alertas[] = "{$cuotasParciales} parciales";
                    }
                    $detallesAvalados[] = "Avala préstamo #{$prestamo->id} de {$prestamo->cliente->persona->nombres}: ".implode(', ', $alertas);
                }
            }
        }

        // Preparar la respuesta con los resultados
        $data = [
            'success' => 'El cliente puede ser asignado como aval.',
            'nombreAval' => $persona->nombres.' '.$persona->ape_pat.' '.$persona->ape_mat,
            'es_cliente' => true,
            'persona_id' => $persona->id,
            'prestamosActivos' => $prestamosActivos,
            'prestamosConyuge' => $prestamosConyuge,
            'prestamosAvala' => $prestamosAvala,
            'conyuge_nombre' => $conyugeData && $conyugeData->persona ? $conyugeData->persona->nombres.' '.$conyugeData->persona->ape_pat : null,
            'tieneDeuda' => $tieneDeuda,
            'detallesDeuda' => $detallesDeuda,
            'tieneDeudaConyuge' => $tieneDeudaConyuge,
            'detallesConyuge' => $detallesConyuge,
            'tieneDeudaAvalados' => $tieneDeudaAvalados,
            'detallesAvalados' => $detallesAvalados,
        ];

        return response()->json($data);
    }

    public function asignarAval(Request $request, $prestamo = null)
    {
        // Obtener la persona (aval) por el DNI
        $persona = Persona::where('documento', $request->aval_id)->first();
        if (! $persona) {
            return response()->json(['error' => 'No se encontró a la persona con ese DNI.'], 404);
        }

        // Obtener el cliente relacionado con esa persona
        $cliente = Cliente::where('persona_id', $persona->id)->first();
        if (! $cliente) {
            return response()->json(['error' => 'La persona no es un cliente.'], 404);
        }

        // 1. Verificar que el aval no tenga un préstamo activo con cuotas atrasadas
        $prestamosAval = $cliente->prestamos()->where('estado', '!=', 'Finalizado')->get();
        foreach ($prestamosAval as $prestamo) {
            $cuotasAtrasadas = $prestamo->cuotas()->where('estado', '!=', 'Pagado')->get();
            if ($cuotasAtrasadas->isNotEmpty()) {
                return response()->json(['error' => 'El aval tiene un préstamo con cuotas atrasadas.'], 400);
            }
        }

        // 2. Verificar que el cónyuge del aval no tenga préstamos con deudas
        if ($cliente->conyuge) {
            $conyuge = $cliente->conyuge;
            $prestamosConyuge = $conyuge->prestamos()->where('estado', '!=', 'Finalizado')->get();
            foreach ($prestamosConyuge as $prestamoConyuge) {
                $cuotasAtrasadasConyuge = $prestamoConyuge->cuotas()->where('estado', '!=', 'Pagado')->get();
                if ($cuotasAtrasadasConyuge->isNotEmpty()) {
                    return response()->json(['error' => 'El cónyuge del aval tiene préstamos con cuotas atrasadas.'], 400);
                }
            }
        }

        // 3. Verificar que el aval no tenga más de dos avalados
        $cantidadAvalados = Aval::where('persona_id', $persona->id)->count();
        if ($cantidadAvalados >= 2) {
            return response()->json(['error' => 'El aval tiene más de dos avalados.'], 400);
        }

        // 4. Verificar que los avalados no tengan préstamos con deudas vencidas o impagas
        $avales = Aval::where('persona_id', $persona->id)->get();
        foreach ($avales as $aval) {
            $prestamosAvalado = $aval->prestamo()->where('estado', '!=', 'Finalizado')->get();
            foreach ($prestamosAvalado as $prestamoAvalado) {
                $cuotasAtrasadasAvalado = $prestamoAvalado->cuotas()->where('estado', '!=', 'Pagado')->get();
                if ($cuotasAtrasadasAvalado->isNotEmpty()) {
                    return response()->json(['error' => 'El avalado tiene préstamos con cuotas atrasadas.'], 400);
                }
            }
        }

        // Si todas las condiciones se cumplen, asignamos el aval
        try {
            // Obtener el préstamo y asignar el aval
            $prestamoId = $request->prestamo_id ?? $prestamo;
            $prestamo = Prestamo::findOrFail($prestamoId);
            $aval = new Aval;
            $aval->prestamo_id = $prestamo->id;
            $aval->persona_id = $persona->id;   // Asignar la persona como aval
            $aval->parentesco = $request->parentesco;  // Capturar el parentesco
            $aval->observaciones = $request->observaciones;  // Capturar las observaciones
            $aval->save();

            return response()->json(['success' => 'Aval asignado correctamente.']);
        } catch (\Exception $e) {
            Log::error('Error al asignar aval: '.$e->getMessage());

            return response()->json(['error' => 'Hubo un error al asignar el aval.'], 500);
        }
    }

    /**
     * Mostrar vista separada para asignar aval (GET)
     */
    public function mostrarAsignarAval($id)
    {
        $prestamo = Prestamo::findOrFail($id);
        return view('admin.Prestamos.asignar-aval', compact('prestamo'));
    }

    /**
     * Valida si el cliente, el aval o el cónyuge cumplen con las restricciones para solicitar un nuevo préstamo.
     */
    private function validarRestriccionesParaPrestamo(Cliente $cliente, $avalId = null)
    {
        // Validación si el cliente tiene préstamos activos en los estados restringidos
        $prestamosActivos = $cliente->prestamos()->whereIn('estado', ['Nueva Solicitud', 'Por Desembolsar', 'Vigente', 'Moroso'])->exists();
        if ($prestamosActivos) {
            return 'El cliente tiene un préstamo activo en estado restringido. No puede solicitar un nuevo préstamo.';
        }

        // Validación del aval
        if ($avalId) {
            $aval = Persona::findOrFail($avalId);
            $prestamosAval = $aval->prestamos()->where('estado', '!=', 'Finalizado')->count();
            if ($prestamosAval >= 2) {
                return 'El aval tiene más de dos préstamos activos. No puede ser asignado a este préstamo.';
            }
        }

        // Verificar el cónyuge
        if ($cliente->conyuge) {
            $conyuge = $cliente->conyuge;
            $prestamosImpagos = $conyuge->prestamos()->where('estado', '!=', 'Finalizado')->exists();
            if ($prestamosImpagos) {
                return 'El cónyuge del cliente tiene préstamos impagos. No puede solicitar un nuevo préstamo.';
            }

            $cantidadAvalados = $conyuge->avales()->count();
            if ($cantidadAvalados >= 2) {
                return 'El cónyuge del cliente tiene más de dos avalados. No puede solicitar un nuevo préstamo.';
            }
        }

        return null; // No hay problemas, todo está permitido
    }

    /**
     * Consulta información sobre un aval basado en su DNI.
     */
    public function consultarAval(Request $request)
    {
        $request->validate(['nDocumento' => 'required']);

        try {
            $dni = $request->nDocumento;
            $persona = Persona::where('documento', $dni)->first();

            if (! $persona) {
                return response()->json(['message' => 'Aval no encontrado'], 404);
            }

            $cliente = Cliente::where('persona_id', $persona->id)->first();
            $prestamosCliente = $cliente ? $cliente->prestamos : collect();
            $avales = Aval::where('persona_id', $persona->id)->with('prestamo')->get();

            // Verificar condiciones
            $tieneDeuda = $prestamosCliente->contains(function ($prestamo) {
                return $prestamo->estado !== 'Finalizado';
            });

            $avalesAlgunDeudor = $avales->contains(function ($aval) {
                return $aval->prestamo && $aval->prestamo->estado !== 'Finalizado';
            });

            return response()->json([
                'persona' => [
                    'nombres' => $persona->nombres,
                    'ape_pat' => $persona->ape_pat,
                    'ape_mat' => $persona->ape_mat,
                ],
                'prestamos' => $prestamosCliente->map(function ($prestamo) {
                    return [
                        'id' => $prestamo->id,
                        'tipo' => $prestamo->tipo,
                        'estado' => $prestamo->estado,
                        'fecha_solicitud' => $prestamo->fecha_solicitud->format('Y-m-d'),
                        'fecha_primer_pago' => $prestamo->fecha_primer_pago ? $prestamo->fecha_primer_pago->format('Y-m-d') : 'N/A',
                    ];
                }),
                'avales' => $avales->map(function ($aval) {
                    return [
                        'nombre' => $aval->prestamo->cliente->persona->nombres.' '.$aval->prestamo->cliente->persona->ape_pat.' '.$aval->prestamo->cliente->persona->ape_mat,
                        'estado_prestamo' => $aval->prestamo->estado,
                        'etiquetas' => $aval->prestamo->cliente->etiquetasCliente->pluck('etiqueta')->toArray(),
                    ];
                }),
                'tieneDeuda' => $tieneDeuda,
                'avalesAlgunDeudor' => $avalesAlgunDeudor,
                'numAvalados' => $avales->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al consultar aval: '.$e->getMessage());

            return response()->json(['message' => 'Error en el servidor'], 500);
        }
    }

    /**
     * Obtiene el valor de una cuota específica de un préstamo.
     */
    protected function obtenerValorCuota(Prestamo $prestamo)
    {
        return $prestamo->cuotas()->first();
    }

    /**
     * Muestra un préstamo específico.
     */
    public function show(Request $request, $id)
    {
        try {
            Log::info('Método show llamado con ID: '.$id);

            $prestamo = Prestamo::with([
                'cliente.persona.direcciones',
                'cliente.sucursal.zonas',
                'cuotas.operaciones.metodoDePago',
                'cuotas.moras',
                'cuotas.moras_pendientes',
                'operaciones',
                'carterasAnalista.analista.persona',
                'carterasJcc.jcc.persona',
                'carterasAsesor.asesor.persona',
                'convenios',
            ])->findOrFail($id);

            // VALIDACIÓN DE PERMISOS: Verificar si el usuario puede ver este préstamo
            // Si tiene múltiples roles, buscar en TODAS sus carteras (OR)
            if ($this->debeAplicarFiltroCartera()) {
                $userId = auth()->id();
                $puedeVer = false;

                // Verificar en TODAS las carteras donde el usuario tenga roles asignados
                if (auth()->user()->hasRole('Asesor')) {
                    $puedeVer = $puedeVer || $prestamo->carterasAsesor()
                        ->where('asesor_id', $userId)
                        ->where('estado', 1)
                        ->exists();
                }

                if (auth()->user()->hasRole('Analista')) {
                    $puedeVer = $puedeVer || $prestamo->carterasAnalista()
                        ->where('analista_id', $userId)
                        ->where('estado', 1)
                        ->exists();
                }

                if (auth()->user()->hasRole('JCC')) {
                    $puedeVer = $puedeVer || $prestamo->carterasJcc()
                        ->where('jcc_id', $userId)
                        ->where('estado', 1)
                        ->exists();
                }

                if (!$puedeVer) {
                    Log::warning("Acceso denegado al préstamo {$id} para usuario " . auth()->id());
                    abort(403, 'No tienes permiso para ver este préstamo. Solo puedes ver préstamos de tu cartera.');
                }
            }

            $cuotas = $prestamo->cuotas;

            $operacionesGenerales = Operacion::where('prestamo_id', $prestamo->id)
                ->whereNull('operacion_general_id')
                ->with([
                    'operacionesRelacionadas.cuotas',
                    'operacionesRelacionadas.morasCuota.cuota',
                    'morasCuota.cuota', // Cargar también las moras de la operación general
                    'metodoDePago',
                    'user',
                ])
                ->get();

            $gestiones = Gestion::with([
                'estadoGestion',
                'compromiso',
                'prestamo.cliente.persona',
                'asesor',
            ])->where('prestamo_id', $id)
                ->get();

            $metodosDePago = MetodoDePago::all();

            $tieneComprobantes = $prestamo->operaciones()->whereNotNull('voucher_path')->exists();

            // === NUEVO: Buscar fondo provisional relacionado ===
            $fondo_provisional = \App\Models\FondoProvisional::where('prestamo_id', $prestamo->id)->first();

            // === CALCULAR Y ACTUALIZAR ESTADO REAL EN BD ===
            // Al cargar la vista, recalculamos y actualizamos el estado en BD
            // para mantener la consistencia de los datos
            $estadoController = new EstadoPrestamoController();
            $resultadoEstado = $estadoController->calcularYActualizarEstado(
                $prestamo,
                true, // ✅ SÍ actualizar BD automáticamente
                'show_view' // Origen: visualización
            );
            
            $estadoCalculado = $resultadoEstado['estado_calculado'];
            $estadoBD = $resultadoEstado['estado_anterior'];
            
            // Si hubo cambio, se loggeó automáticamente en el controlador
            if ($resultadoEstado['fue_actualizado']) {
                // Recargar el préstamo para reflejar el cambio
                $prestamo->refresh();
            }

            // === GENERAR MORAS FALTANTES (solo crear, NO regularizar) ===
            // Verificar si hay cuotas vencidas sin moras generadas
            // IMPORTANTE: Solo genera moras faltantes, NO regulariza existentes
            try {
                $morasGeneradas = $this->generarMorasFaltantes($prestamo);
                
                if ($morasGeneradas > 0) {
                    Log::info("Moras faltantes generadas al ver préstamo {$id}", [
                        'moras_generadas' => $morasGeneradas,
                    ]);
                    
                    // Recargar las cuotas y moras después de la generación
                    $prestamo->load(['cuotas.moras_pendientes']);
                }
            } catch (\Exception $e) {
                Log::warning("Error al verificar moras para préstamo {$id}: ".$e->getMessage());
            }

            Log::info('Detalle del préstamo cargado correctamente', [
                'prestamo_id' => $id,
                'cliente_id' => $prestamo->cliente->id,
                'estado_bd' => $estadoBD,
                'estado_calculado' => $estadoCalculado,
                'cuotas_count' => $cuotas->count(),
                'operaciones_generales_count' => $operacionesGenerales->count(),
                'gestiones_count' => $gestiones->count(),
                'tiene_comprobantes' => $tieneComprobantes,
            ]);

            // Pasar el estado calculado a la vista
            return view('admin.Prestamos.show', compact(
                'prestamo',
                'cuotas',
                'operacionesGenerales',
                'gestiones',
                'metodosDePago',
                'tieneComprobantes',
                'fondo_provisional',
                'estadoCalculado',  // Estado calculado en tiempo real
                'estadoBD'           // Estado en BD (puede ser diferente)
            ));
        } catch (\Exception $e) {
            Log::error("Error al cargar detalle del préstamo {$id}: ".$e->getMessage());

            return redirect()->route('admin.prestamos.index')
                ->with('error', 'Error al cargar el préstamo: '.$e->getMessage());
        }
    }

    /**
     * Toggle de comprobantes SUNAT para un préstamo específico
     */
    public function toggleComprobantes(Request $request, $id)
    {
        try {
            Log::info('Toggle comprobantes solicitado', [
                'prestamo_id' => $id,
                'request_data' => $request->all(),
                'user_id' => auth()->id(),
            ]);

            $prestamo = Prestamo::findOrFail($id);
            $activado = $request->input('activado', false);

            Log::info('Préstamo encontrado', [
                'prestamo_id' => $id,
                'tiene_comprobante_actual' => $prestamo->tiene_comprobante,
                'activado_solicitado' => $activado,
            ]);

            // Verificar si ya se emitieron comprobantes para este préstamo
            $yaSeEmitieronComprobantes = DB::table('comprobantes')
                ->where('prestamo_id', $id)
                ->where('estado', '!=', 'PENDIENTE')
                ->exists();

            Log::info('Verificación de comprobantes', [
                'prestamo_id' => $id,
                'ya_se_emitieron_comprobantes' => $yaSeEmitieronComprobantes,
                'puede_desactivar' => ! $yaSeEmitieronComprobantes || $activado,
            ]);

            // Si ya se emitieron comprobantes y se quiere desactivar, no permitir
            if ($yaSeEmitieronComprobantes && ! $activado) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede desactivar la emisión de comprobantes porque ya se han emitido comprobantes SUNAT para este préstamo.',
                ]);
            }

            // Actualizar el campo
            $prestamo->update(['tiene_comprobante' => $activado]);

            if ($activado) {
                $mensaje = $yaSeEmitieronComprobantes ?
                    'Emisión de comprobantes SUNAT activada. Los nuevos pagos generarán comprobantes electrónicos.' :
                    'Emisión de comprobantes SUNAT activada para este préstamo.';
            } else {
                $mensaje = 'Emisión de comprobantes SUNAT desactivada para este préstamo.';
            }

            Log::info("Toggle comprobantes préstamo {$id}", [
                'prestamo_id' => $id,
                'activado' => $activado,
                'ya_emitidos' => $yaSeEmitieronComprobantes,
            ]);

            return response()->json([
                'success' => true,
                'message' => $mensaje,
            ]);

        } catch (\Exception $e) {
            Log::error("Error al cambiar estado de comprobantes para préstamo {$id}: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la configuración de comprobantes.',
            ], 500);
        }
    }

    /**
     * Edita un préstamo.
     */
    public function edit($id)
    {
        try {
            // Cargar el préstamo junto con las relaciones necesarias
            $prestamo = Prestamo::with(['cliente.persona', 'cuenta', 'cuentaCliente', 'carterasJcc', 'carterasAsesor', 'carterasAnalista'])->findOrFail($id);

            // VALIDACIÓN DE PERMISOS: Solo puede editar si está en su cartera
            // Si tiene múltiples roles, buscar en TODAS sus carteras (OR)
            if ($this->debeAplicarFiltroCartera()) {
                $userId = auth()->id();
                $puedeEditar = false;

                // Verificar en TODAS las carteras donde el usuario tenga roles asignados
                if (auth()->user()->hasRole('Asesor')) {
                    $puedeEditar = $puedeEditar || $prestamo->carterasAsesor()->where('asesor_id', $userId)->where('estado', 1)->exists();
                }
                if (auth()->user()->hasRole('Analista')) {
                    $puedeEditar = $puedeEditar || $prestamo->carterasAnalista()->where('analista_id', $userId)->where('estado', 1)->exists();
                }
                if (auth()->user()->hasRole('JCC')) {
                    $puedeEditar = $puedeEditar || $prestamo->carterasJcc()->where('jcc_id', $userId)->where('estado', 1)->exists();
                }

                if (!$puedeEditar) {
                    return redirect()->route('admin.prestamos.index')
                        ->with('error', 'No tienes permiso para editar este préstamo. Solo puedes editar préstamos de tu cartera.');
                }
            }

            // Verificar si el préstamo está en estado editable (antes del desembolso)
            $estadosEditables = ['Nueva Solicitud', 'En Análisis', 'Por Desembolsar'];
            if (! in_array($prestamo->estado, $estadosEditables)) {
                return redirect()->route('admin.prestamos.index')
                    ->withErrors(['error' => 'Solo se pueden editar préstamos en estado "Nueva Solicitud", "En Análisis" o "Por Desembolsar".']);
            }

            // Cargar datos adicionales necesarios para el formulario de edición
            $clientes = Cliente::with(['persona', 'persona.direcciones'])->get();
            $cuentas = Cuenta::all();
            $cuentasCliente = CuentaCliente::all();
            $asesores = User::role('Asesor')->get();
            $analistas = User::role('Analista')->get();
            $jccs = User::role('Jcc')->get();
            $plazos = Plazo::all();
            $direcciones = Direccion::all();

            // Obtener asignaciones actuales
            $jccActual = $prestamo->carterasJcc->first()?->jcc_id;
            $asesorActual = $prestamo->carterasAsesor->first()?->asesor_id;
            $analistaActual = $prestamo->carterasAnalista->first()?->analista_id;

            // Log de éxito al cargar los datos para la edición
            Log::info('Datos de edición del préstamo cargados correctamente', [
                'prestamo_id' => $id,
                'cliente_id' => $prestamo->cliente_id,
                'estado' => $prestamo->estado,
            ]);

            return view('admin.Prestamos.edit', compact(
                'prestamo',
                'clientes',
                'cuentas',
                'cuentasCliente',
                'asesores',
                'analistas',
                'jccs',
                'plazos',
                'direcciones',
                'jccActual',
                'asesorActual',
                'analistaActual'
            ));
        } catch (\Exception $e) {
            Log::error('Error al cargar los datos de edición del préstamo: '.$e->getMessage());

            return redirect()->route('admin.prestamos.index')->withErrors(['error' => 'Error al cargar los datos para la edición del préstamo.']);
        }
    }

    /**
     * Actualiza un préstamo existente.
     */
    public function update(Request $request, $id)
    {
        // Validar los datos de entrada
        $validatedData = $request->validate([
            'fecha_atencion' => 'required|date',
            'fecha_primer_pago' => 'required|date',
            'cantidad_solicitada' => 'required|numeric|min:1',
            'plazo' => 'required|integer|in:8,12,15,18,20',
            'cuenta_cliente_id' => 'nullable|exists:cuentas_cliente,id',
            'jcc_id' => 'required|exists:users,id',
            'asesor_id' => 'required|exists:users,id',
            'analista_id' => 'required|exists:users,id',
            'tasa_interes' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Buscar el préstamo por su ID
            $prestamo = Prestamo::with(['cuotas', 'carterasJcc', 'carterasAsesor', 'carterasAnalista'])->findOrFail($id);

            // VALIDACIÓN DE PERMISOS: Solo puede actualizar si está en su cartera
            // Si tiene múltiples roles, buscar en TODAS sus carteras (OR)
            if ($this->debeAplicarFiltroCartera()) {
                $userId = auth()->id();
                $puedeActualizar = false;

                // Verificar en TODAS las carteras donde el usuario tenga roles asignados
                if (auth()->user()->hasRole('Asesor')) {
                    $puedeActualizar = $puedeActualizar || $prestamo->carterasAsesor()->where('asesor_id', $userId)->where('estado', 1)->exists();
                }
                if (auth()->user()->hasRole('Analista')) {
                    $puedeActualizar = $puedeActualizar || $prestamo->carterasAnalista()->where('analista_id', $userId)->where('estado', 1)->exists();
                }
                if (auth()->user()->hasRole('JCC')) {
                    $puedeActualizar = $puedeActualizar || $prestamo->carterasJcc()->where('jcc_id', $userId)->where('estado', 1)->exists();
                }

                if (!$puedeActualizar) {
                    DB::rollback();
                    return redirect()->back()->withErrors(['error' => 'No tienes permiso para actualizar este préstamo. Solo puedes actualizar préstamos de tu cartera.']);
                }
            }

            // Verificar que el préstamo esté en estado editable
            $estadosEditables = ['Nueva Solicitud', 'En Análisis', 'Por Desembolsar'];
            if (! in_array($prestamo->estado, $estadosEditables)) {
                return redirect()->back()->withErrors(['error' => 'Solo se pueden editar préstamos en estado "Nueva Solicitud", "En Análisis" o "Por Desembolsar".']);
            }

            // Verificar si cambió el monto o plazo para recalcular cuotas
            $montoChanged = $prestamo->cantidad_solicitada != $validatedData['cantidad_solicitada'];
            $plazoChanged = $prestamo->plazo != $validatedData['plazo'];
            $tasaChanged = $prestamo->tasa_interes != $validatedData['tasa_interes'];

            // Actualizar datos básicos del préstamo
            $prestamo->update([
                'fecha_atencion' => $validatedData['fecha_atencion'],
                'fecha_primer_pago' => $validatedData['fecha_primer_pago'],
                'cantidad_solicitada' => $validatedData['cantidad_solicitada'],
                'plazo' => $validatedData['plazo'],
                'cuenta_cliente_id' => $validatedData['cuenta_cliente_id'],
                'tasa_interes' => $validatedData['tasa_interes'],
                'observaciones' => $validatedData['observaciones'],
            ]);

            // Si cambió monto, plazo o tasa, recalcular cuotas
            if ($montoChanged || $plazoChanged || $tasaChanged) {
                // Eliminar cuotas existentes
                $prestamo->cuotas()->delete();

                // Recalcular cuotas con los nuevos valores usando calcularCuotasInterno
                $fechaInicio = Carbon::parse($validatedData['fecha_primer_pago']);
                $resultadoCuotas = $this->calcularCuotasInterno(
                    $prestamo->monto,
                    $prestamo->plazo,
                    $fechaInicio
                );

                foreach ($resultadoCuotas['cuotas'] as $cuotaData) {
                    Cuota::create([
                        'prestamo_id' => $prestamo->id,
                        'fecha_pago' => $cuotaData['fecha_pago'],
                        'numero' => $cuotaData['numero'],
                        'monto' => $cuotaData['cuota'],
                        'pago_capital' => $cuotaData['pagoCapital'] ?? null,
                        'interes' => $cuotaData['interes'] ?? null,
                        'comision' => $cuotaData['comision'] ?? null,
                        'igv' => $cuotaData['igv'] ?? null,
                        'cantidad_mora' => 0,
                        'estado' => 0, // Estado pendiente
                    ]);
                }
            }

            // Actualizar asignaciones de cartera si cambiaron
            if ($prestamo->carterasJcc->first()?->jcc_id != $validatedData['jcc_id']) {
                $prestamo->carterasJcc()->update(['estado' => 0]);
                CarteraJcc::create([
                    'prestamo_id' => $prestamo->id,
                    'jcc_id' => $validatedData['jcc_id'],
                    'fecha_registro' => now(),
                    'estado' => 1,
                ]);
            }

            if ($prestamo->carterasAsesor->first()?->asesor_id != $validatedData['asesor_id']) {
                $prestamo->carterasAsesor()->update(['estado' => 0]);
                CarteraAsesor::create([
                    'prestamo_id' => $prestamo->id,
                    'asesor_id' => $validatedData['asesor_id'],
                    'fecha_registro' => now(),
                    'estado' => 1,
                ]);
            }

            if ($prestamo->carterasAnalista->first()?->analista_id != $validatedData['analista_id']) {
                $prestamo->carterasAnalista()->update(['estado' => 0]);
                CarteraAnalista::create([
                    'prestamo_id' => $prestamo->id,
                    'analista_id' => $validatedData['analista_id'],
                    'fecha_registro' => now(),
                    'estado' => 1,
                ]);
            }

            DB::commit();

            // Log de éxito en la actualización
            Log::info('Préstamo actualizado correctamente', [
                'prestamo_id' => $id,
                'cambios' => [
                    'monto_changed' => $montoChanged,
                    'plazo_changed' => $plazoChanged,
                    'tasa_changed' => $tasaChanged,
                ],
            ]);

            return redirect()->route('admin.prestamos.index')->with('success', 'Préstamo actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollback();
            // Log de error si ocurre algún problema
            Log::error('Error al actualizar el préstamo: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Error al actualizar el préstamo: '.$e->getMessage()]);
        }
    }

    //PDF
    public function estadoCuentaPreviewHtml($id)
    {
        // Obtener el préstamo correspondiente
        $prestamo = Prestamo::findOrFail($id);

        // Devolver la vista renderizada sin generar PDF
        // Permitir visualización en iframe desde el mismo origen
        return response()
            ->view('pdf.estado_cuenta', compact('prestamo'))
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    /**
     * Generar PDF del Estado de Cuenta con los mismos datos del partial
     * Este método replica exactamente los datos mostrados en la vista estado-cuenta.blade.php
     */
    public function estadoCuentaPDF($id)
    {
        try {
            // Obtener el préstamo con todas las relaciones necesarias (igual que en show())
            $prestamo = Prestamo::with([
                'cliente.persona.direcciones.sucursal.zonas',
                'cliente.persona.direccion',
                'cliente.persona.telefonos',
                'cliente.laborales',
                'cliente.conyuge.persona.telefonos',
                'cliente.cuentasCliente.entidadBancaria',
                'cliente.cuentasCliente.billeteraDigital',
                'aval.persona.direccion',
                'aval.persona.direcciones',
                'aval.persona.telefonos',
                'cuotas.operaciones.metodoDePago',
                'cuotas.operaciones.operacionGeneral',
                'cuotas.moras',
                'cuotas.moras_pendientes',
                'cuotas.abonosMoraFavor',
                'operaciones',
                'carterasAnalista.user.persona',
                'carterasJcc.user.persona',
                'carterasAsesor.user.persona',
                'convenios',
                'cuenta.entidadBancaria',
            ])->findOrFail($id);

            // Obtener las cuotas ordenadas con moras
            $cuotas = $prestamo->cuotas()->with('moras')->orderBy('numero')->get();

            // Buscar fondo provisional
            $fondo_provisional = \App\Models\FondoProvisional::where('prestamo_id', $prestamo->id)->first();

            // Calcular el estado calculado (igual que en show)
            $estadoController = new EstadoPrestamoController();
            $resultadoEstado = $estadoController->calcularYActualizarEstado(
                $prestamo,
                false, // NO actualizar BD, solo calcular
                'pdf_generation' // Origen: generación PDF
            );
            
            $estadoCalculado = $resultadoEstado['estado_calculado'];
            $estadoBD = $resultadoEstado['estado_anterior'];

            // Generar el PDF usando una vista específica para impresión
            $pdf = Pdf::loadView('pdf.estado_cuenta_detallado', compact(
                'prestamo',
                'cuotas',
                'fondo_provisional',
                'estadoCalculado',
                'estadoBD'
            ));

            $pdf->setPaper('A5', 'portrait');
            $pdf->setOptions([
                'dpi' => 150,
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'enable_php' => true,
            ]);

            // Retornar el PDF para descarga/impresión
            return $pdf->stream('estado_cuenta_prestamo_'.$prestamo->id.'_'.date('Y-m-d').'.pdf');

        } catch (\Exception $e) {
            Log::error('Error al generar PDF estado de cuenta: '.$e->getMessage(), [
                'prestamo_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Error al generar el PDF: '.$e->getMessage());
        }
    }

    public function estadoCuentaPreview($id)
    {
        try {
            // Obtener el préstamo con todas las relaciones necesarias
            $prestamo = Prestamo::with([
                'cliente.persona.direcciones.sucursal.zonas',
                'cuotas.operaciones.metodoDePago',
                'cuotas.moras_pendientes',
                'carterasAnalista.user',
                'carterasJcc.user',
                'carterasAsesor.user',
            ])->findOrFail($id);

            $cuotas = $prestamo->cuotas()->orderBy('numero')->get();

            $totalCapital = $cuotas->sum('monto');
            $totalAbonos = $cuotas->sum(fn ($cuota) => $cuota->operaciones()->sum('abono'));
            $totalInteres = $cuotas->sum('interes');
            $totalComision = $cuotas->sum('comision');
            $totalIgv = $cuotas->sum('igv');
            $totalMoras = $cuotas->sum(fn ($cuota) => $cuota->moras_pendientes->sum('monto'));

            $pdf = Pdf::loadView('pdf.estado_cuenta', compact(
                'prestamo',
                'cuotas',
                'totalCapital',
                'totalAbonos',
                'totalInteres',
                'totalComision',
                'totalIgv',
                'totalMoras'
            ));

            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'dpi' => 150,
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'enable_php' => true,
            ]);

            // Stream el PDF para visualización en el navegador (iframe)
            // Permitir visualización en iframe desde el mismo origen
            return response($pdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="estado_cuenta_prestamo_'.$id.'.pdf"')
                ->header('X-Frame-Options', 'SAMEORIGIN')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');

        } catch (\Exception $e) {
            Log::error('Error al generar preview estado de cuenta: '.$e->getMessage());
            return response()->json(['error' => 'Error al generar el documento'], 500);
        }
    }

    public function descargarEstadoCuenta($id)
    {
        try {
            // Mismo código que arriba pero con download en lugar de stream
            $prestamo = Prestamo::with([
                'cliente.persona.direcciones.sucursal.zonas',
                'cuotas.operaciones.metodoDePago',
                'cuotas.moras_pendientes',
                'carterasAnalista.user',
                'carterasJcc.user',
                'carterasAsesor.user',
            ])->findOrFail($id);

            $cuotas = $prestamo->cuotas()->orderBy('numero')->get();

            $totalCapital = $cuotas->sum('monto');
            $totalAbonos = $cuotas->sum(fn ($cuota) => $cuota->operaciones()->sum('abono'));
            $totalInteres = $cuotas->sum('interes');
            $totalComision = $cuotas->sum('comision');
            $totalIgv = $cuotas->sum('igv');
            $totalMoras = $cuotas->sum(fn ($cuota) => $cuota->moras_pendientes->sum('monto'));

            $pdf = Pdf::loadView('pdf.estado_cuenta', compact(
                'prestamo',
                'cuotas',
                'totalCapital',
                'totalAbonos',
                'totalInteres',
                'totalComision',
                'totalIgv',
                'totalMoras'
            ));

            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'dpi' => 150,
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'enable_php' => true,
            ]);

            // Descargar el PDF
            return $pdf->download("estado_cuenta_prestamo_{$id}.pdf");

        } catch (\Exception $e) {
            Log::error('Error al descargar estado de cuenta: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error al generar el documento');
        }
    }

    /**
     * Generar estado de cuenta para compartir por WhatsApp
     * Guarda el PDF temporalmente y retorna la URL pública
     */
    public function generarEstadoCuentaParaCompartir($id)
    {
        try {
            // Obtener el préstamo con todas las relaciones necesarias
            $prestamo = Prestamo::with([
                'cliente.persona.direcciones.sucursal.zonas',
                'cuotas.operaciones.metodoDePago',
                'cuotas.moras_pendientes',
                'carterasAnalista.user',
                'carterasJcc.user',
                'carterasAsesor.user',
            ])->findOrFail($id);

            $cuotas = $prestamo->cuotas()->orderBy('numero')->get();

            $totalCapital = $cuotas->sum('monto');
            $totalAbonos = $cuotas->sum(fn ($cuota) => $cuota->operaciones()->sum('abono'));
            $totalInteres = $cuotas->sum('interes');
            $totalComision = $cuotas->sum('comision');
            $totalIgv = $cuotas->sum('igv');
            $totalMoras = $cuotas->sum(fn ($cuota) => $cuota->moras_pendientes->sum('monto'));

            // Generar el PDF
            $pdf = Pdf::loadView('pdf.estado_cuenta', compact(
                'prestamo',
                'cuotas',
                'totalCapital',
                'totalAbonos',
                'totalInteres',
                'totalComision',
                'totalIgv',
                'totalMoras'
            ));

            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'dpi' => 150,
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'enable_php' => true,
            ]);

            // Crear directorio temporal si no existe
            $tempDir = storage_path('app/public/temp/estados-cuenta');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Generar nombre único para el archivo
            $fileName = 'estado_cuenta_' . $id . '_' . time() . '.pdf';
            $filePath = $tempDir . '/' . $fileName;

            // Guardar el PDF
            $pdf->save($filePath);

            // Generar URL pública
            $publicUrl = url('storage/temp/estados-cuenta/' . $fileName);

            // Información del cliente para WhatsApp
            $clienteNombre = $prestamo->cliente->persona->nombres . ' ' .
                           $prestamo->cliente->persona->ape_pat . ' ' .
                           $prestamo->cliente->persona->ape_mat;

            $clienteTelefono = $prestamo->cliente->persona->telefono ?? '';

            // Mensaje personalizado para WhatsApp
            $mensaje = "🏦 *Estado de Cuenta - Préstamo #{$id}*%0A%0A" .
                      "👤 Cliente: {$clienteNombre}%0A" .
                      "💰 Monto: S/ " . number_format($prestamo->cantidad_solicitada, 2) . "%0A" .
                      "📊 Estado: {$prestamo->estado}%0A%0A" .
                      "📄 Documento completo:%0A{$publicUrl}%0A%0A" .
                      "_Generado el " . now()->format('d/m/Y H:i') . "_";

            return response()->json([
                'success' => true,
                'url' => $publicUrl,
                'whatsapp_url' => "https://wa.me/{$clienteTelefono}?text={$mensaje}",
                'mensaje' => urldecode($mensaje),
                'telefono' => $clienteTelefono,
                'file_path' => $filePath,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al generar estado de cuenta para compartir: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el documento: ' . $e->getMessage()
            ], 500);
        }
    }

    public function validarPrestamoCliente($clienteId)
    {
        $cliente = Cliente::find($clienteId);

        if (! $cliente) {
            return response()->json(['error' => 'Cliente no encontrado.'], 404);
        }

        // Obtener préstamos activos del cliente
        $prestamosActivos = $cliente->prestamos()
            ->whereNotIn('estado', ['Anulado', 'Pagado', 'Cancelado', 'Finalizado', 'Liquidado'])
            ->get();

        // Si no hay préstamos activos, el cliente puede solicitar un nuevo préstamo
        if ($prestamosActivos->isEmpty()) {
            return response()->json(['success' => 'El cliente puede solicitar un préstamo.'], 200);
        }

        // Verificar si algún préstamo activo tiene convenio activo
        $tieneConvenioActivo = false;
        foreach ($prestamosActivos as $prestamo) {
            $conveniosActivos = $prestamo->convenios()
                ->where('estado', \App\Enums\ConvenioEstado::ACTIVO->value)
                ->exists();

            if ($conveniosActivos) {
                $tieneConvenioActivo = true;
                break;
            }
        }

        // Si tiene convenio activo, no puede solicitar un nuevo préstamo
        if ($tieneConvenioActivo) {
            return response()->json([
                'error' => 'El cliente tiene un convenio de pago activo. No puede solicitar un nuevo préstamo hasta finalizar el convenio.'
            ], 400);
        }

        // Si tiene préstamos activos sin convenio, indicar el estado del préstamo
        $estadoPrestamo = $prestamosActivos->first()->estado;
        return response()->json([
            'error' => "El cliente tiene un préstamo activo en estado: {$estadoPrestamo}. No puede solicitar un nuevo préstamo."
        ], 400);
    }

    public function consultarPrestamos($clienteId)
    {
        try {
            $cliente = Cliente::find($clienteId);

            if (! $cliente) {
                return response()->json(['error' => 'Cliente no encontrado.'], 404);
            }

            // Obtener los préstamos del cliente excluyendo los de estado "Anulado"
            $prestamos = $cliente->prestamos()
                ->where('estado', '!=', 'Anulado')
                ->orderBy('created_at', 'desc')
                ->get();

            if ($prestamos->isEmpty()) {
                return response()->json([], 200); // Devolver array vacío en vez de objeto con mensaje
            }

            // Formatear los datos de los préstamos para el frontend
            $prestamosFormateados = $prestamos->map(function ($prestamo) use ($cliente) {
                // Verificar si tiene convenio activo
                $convenioActivo = $prestamo->convenios()
                    ->where('estado', \App\Enums\ConvenioEstado::ACTIVO->value)
                    ->first();

                return [
                    'id' => $prestamo->id,
                    'tipo' => $prestamo->tipo_solicitud ?? 'N/A',
                    'nombre' => $cliente->persona->nombre_completo ?? 'N/A',
                    'estado' => $prestamo->estado,
                    'fecha_solicitud' => $prestamo->fecha_atencion,
                    'fecha_primer_pago' => $prestamo->fecha_primer_pago,
                    'cantidad_solicitada' => $prestamo->cantidad_solicitada,
                    'plazo' => $prestamo->plazo,
                    'tiene_convenio_activo' => $convenioActivo ? true : false,
                    'convenio' => $convenioActivo ? [
                        'id' => $convenioActivo->id,
                        'fecha_inicio' => $convenioActivo->fecha_inicio,
                        'total_convenio' => $convenioActivo->total_convenio,
                        'numero_cuotas' => $convenioActivo->numero_cuotas,
                        'valor_cuota' => $convenioActivo->valor_cuota,
                    ] : null,
                ];
            });

            return response()->json($prestamosFormateados);
        } catch (\Exception $e) {
            Log::error('Error al consultar préstamos del cliente: '.$e->getMessage());

            return response()->json(['error' => 'Error al consultar los préstamos del cliente.'], 500);
        }
    }

    public function getMonto(Prestamo $prestamo)
    {
        if ($prestamo->cantidad_solicitada === null) {
            return response()->json([
                'success' => false,
                'message' => 'El monto solicitado no está disponible para este préstamo.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'monto' => $prestamo->cantidad_solicitada,
        ]);
    }

    public function getMetodosPago()
    {
        try {
            $metodos = DB::table('metodos_de_pago')
                ->where('status', 1) // Solo métodos activos
                ->select('id', 'metodo_pago')
                ->get();

            return response()->json([
                'success' => true,
                'metodos_pago' => $metodos,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener métodos de pago: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera moras retroactivas para préstamos creados con fechas pasadas
     * Solo procesa cuotas que ya están vencidas al momento de crear el préstamo
     */
    private function generarMorasRetroactivas(Prestamo $prestamo): void
    {
        try {
            $hoy = Carbon::today();
            $fechaCreacionPrestamo = Carbon::parse($prestamo->created_at)->startOfDay();

            Log::info("Verificando moras retroactivas para préstamo {$prestamo->id} - Creado: {$fechaCreacionPrestamo->format('Y-m-d')}, Hoy: {$hoy->format('Y-m-d')}");

            // Obtener cuotas vencidas al momento de crear el préstamo
            $cuotasVencidas = $prestamo->cuotas()
                ->where('fecha_pago', '<', $hoy)
                ->where('estado', 0) // Solo cuotas PENDIENTES
                ->get();

            if ($cuotasVencidas->count() === 0) {
                Log::info("No hay cuotas vencidas para generar moras retroactivas en préstamo {$prestamo->id}");

                return;
            }

            Log::info("Generando moras retroactivas para {$cuotasVencidas->count()} cuotas vencidas del préstamo {$prestamo->id}");

            $morasGeneradas = 0;
            $moraDiaria = $prestamo->mora ?? 4.00; // Usar mora específica del préstamo

            foreach ($cuotasVencidas as $cuota) {
                $fechaVencimiento = Carbon::parse($cuota->fecha_pago)->startOfDay();
                $diasVencidos = $fechaVencimiento->diffInDays($hoy);

                // Generar hasta 7 moras máximo por cuota
                $morasAGenerar = min($diasVencidos, 7);

                Log::info("Cuota {$cuota->id} vencida hace {$diasVencidos} días - Generando {$morasAGenerar} moras");

                for ($dia = 1; $dia <= $morasAGenerar; $dia++) {
                    $fechaMora = $fechaVencimiento->copy()->addDays($dia);

                    // Solo generar moras hasta hoy (no futuras)
                    if ($fechaMora <= $hoy) {
                        MoraCuota::create([
                            'cuota_id' => $cuota->id,
                            'fecha' => $fechaMora,
                            'dias_mora' => $dia,
                            'monto' => $moraDiaria,
                            'estado' => MoraCuotaEstado::PENDIENTE->value,
                            'monto_pagado' => 0,
                        ]);

                        $morasGeneradas++;
                    }
                }

                // Actualizar cantidad_mora en la cuota
                $totalMorasCuota = $cuota->moras()->sum('monto');
                $cuota->update(['cantidad_mora' => $totalMorasCuota]);
            }

            Log::info("Moras retroactivas generadas: {$morasGeneradas} para préstamo {$prestamo->id}");

        } catch (\Exception $e) {
            Log::error("Error generando moras retroactivas para préstamo {$prestamo->id}: ".$e->getMessage());
            // No lanzar excepción para no interrumpir la creación del préstamo
        }
    }

    /**
     * Muestra la vista para vincular préstamos a otros clientes.
     */
    public function vincularPrestamos(Request $request)
    {
        try {
            // Consulta filtrada con la cuota número 1
            $query = Prestamo::select('prestamos.*')
                ->leftJoin('clientes', 'prestamos.cliente_id', '=', 'clientes.id')
                ->leftJoin('personas', 'clientes.persona_id', '=', 'personas.id')
                ->with(['cliente.persona.direcciones.sucursal', 'cuotas'])
                ->where('clientes.persona_id', 2)
                ->where('prestamos.cliente_id', 1)
                ->orderBy('prestamos.id', 'desc');

            Log::info('Total préstamos: '.Prestamo::count());
            Log::info('Préstamos con joins: '.$query->count());

            // Aplicar filtros si existen
            if ($request->filled('fecha')) {
                $query->whereDate('fecha_primer_pago', $request->fecha);
            }

            if ($request->filled('dni')) {
                $query->whereHas('cliente.persona', function ($q) use ($request) {
                    $q->where('documento', 'like', '%'.$request->dni.'%');
                });
            }

            if ($request->filled('cliente_id')) {
                $query->where('cliente_id', $request->cliente_id);
            }

            if ($request->filled('cliente')) {
                $query->whereHas('cliente.persona', function ($q) use ($request) {
                    $q->where('nombres', 'like', '%'.$request->cliente.'%')
                        ->orWhere('apellido_paterno', 'like', '%'.$request->cliente.'%')
                        ->orWhere('apellido_materno', 'like', '%'.$request->cliente.'%');
                });
            }

            $prestamos = $query->paginate(25);

            Log::info('Préstamos encontrados para vincular: '.$prestamos->count());

            return view('admin.Prestamos.vincularprestamos', compact('prestamos'));

        } catch (\Exception $e) {
            Log::error('Error al cargar vista de vincular préstamos: '.$e->getMessage());
            Log::error('Trace: '.$e->getTraceAsString());

            // Devolver vista con array vacío en caso de error
            $prestamos = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25);

            return view('admin.Prestamos.vincularprestamos', compact('prestamos'))
                ->with('error', 'Error al cargar los préstamos: '.$e->getMessage());
        }
    }

    /**
     * Vincula un préstamo a otro cliente.
     */
    public function vincular(Request $request)
    {
        $request->validate([
            'prestamo_id' => 'required|exists:prestamos,id',
            'nueva_persona_id' => 'required|exists:personas,id',
        ]);

        DB::beginTransaction();

        try {
            $prestamo = Prestamo::findOrFail($request->prestamo_id);
            $nuevaPersona = Persona::findOrFail($request->nueva_persona_id);
            $clienteAnterior = $prestamo->cliente;

            // Buscar o crear cliente para la nueva persona
            $nuevoCliente = Cliente::where('persona_id', $nuevaPersona->id)->first();

            if (! $nuevoCliente) {
                // Crear cliente automáticamente si no existe
                $nuevoCliente = Cliente::create([
                    'persona_id' => $nuevaPersona->id,
                    'codigo' => 'AUTO_'.$nuevaPersona->id.'_'.time(),
                    'observaciones' => 'Cliente creado automáticamente para vinculación de préstamo',
                    'carga_familiar' => 0,
                ]);

                Log::info('✅ Cliente creado automáticamente: ID '.$nuevoCliente->id.' para persona ID '.$nuevaPersona->id);
            }

            // Verificar que no sea el mismo cliente
            if ($prestamo->cliente_id == $nuevoCliente->id) {
                return back()->with('error', 'El préstamo ya pertenece a esta persona.');
            }

            // Actualizar el préstamo con el nuevo cliente
            $prestamo->update([
                'cliente_id' => $nuevoCliente->id,
            ]);

            // Las cuotas, operaciones y gestiones se vinculan automáticamente
            // al nuevo cliente a través de la relación con el préstamo

            // Log de la operación
            Log::info("Préstamo {$prestamo->id} vinculado del cliente {$clienteAnterior->persona->nombres} {$clienteAnterior->persona->ape_pat} a la persona {$nuevaPersona->nombres} {$nuevaPersona->ape_pat} (Cliente ID: {$nuevoCliente->id})");

            DB::commit();

            return back()->with('success', 'Préstamo vinculado exitosamente a '.$nuevaPersona->nombres.' '.$nuevaPersona->ape_pat.' (Cliente creado automáticamente).');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al vincular préstamo: '.$e->getMessage());

            return back()->with('error', 'Error al vincular el préstamo: '.$e->getMessage());
        }
    }

    /**
     * Regularizar moras de un préstamo individual según fechas reales de pago
     */
    public function regularizarMoras($id)
    {
        try {
            $prestamo = Prestamo::findOrFail($id);

            $moraService = new MoraService;
            $resultados = $moraService->regularizarMorasPrestamoIndividual($prestamo->id);

            return response()->json([
                'success' => true,
                'message' => 'Regularización completada exitosamente',
                'resultados' => $resultados,
            ]);

        } catch (\Exception $e) {
            \Log::error("Error regularizando moras del préstamo {$id}: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al regularizar las moras: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * MÉTODO DEPRECADO: Usar EstadoPrestamoController::calcularYActualizarEstado() en su lugar
     * 
     * Este método ha sido movido a EstadoPrestamoController para centralizar
     * toda la lógica de cálculo de estado y evitar duplicación de código.
     * 
     * @deprecated Usar EstadoPrestamoController en su lugar
     */
    private function calcularEstadoReal($prestamo)
    {
        // Redirigir al controlador centralizado
        $estadoController = new EstadoPrestamoController();
        return $estadoController->obtenerEstadoCalculado($prestamo);
    }

    /**
     * Resetea todos los pagos de un préstamo, eliminando operaciones y restableciendo estados
     */
    public function resetPayments(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $prestamo = Prestamo::with(['cuotas.moras', 'cuotas.abonosMoraFavor', 'operaciones'])->findOrFail($id);

            // Contadores para el reporte
            $operacionesEliminadas = 0;
            $cuotasReseteadas = 0;
            $morasEliminadas = 0;
            $abonosFavorEliminados = 0;

            // 1. Eliminar TODAS las operaciones EXCEPTO el desembolso
            $operacionesPago = $prestamo->operaciones()
                ->where('tipo_operacion', '!=', 'Desembolso')
                ->get();

            foreach ($operacionesPago as $operacion) {
                // Eliminar registros relacionados en operaciones_cuota
                DB::table('operaciones_cuota')->where('operacion_id', $operacion->id)->delete();

                // Eliminar registros relacionados en operacion_mora
                DB::table('operacion_mora')->where('operacion_id', $operacion->id)->delete();

                // IMPORTANTE: NO eliminar comprobantes electrónicos
                // Los comprobantes ya fueron enviados a SUNAT y deben conservarse por obligación tributaria
                // Solo se elimina la operación, pero el comprobante queda como registro histórico

                $operacion->delete();
                $operacionesEliminadas++;
            }

            // 2. Eliminar convenios y sus cuotas asociadas
            $conveniosEliminados = 0;
            foreach ($prestamo->convenios as $convenio) {
                // Eliminar cuotas del convenio
                $convenio->cuotas()->delete();
                $convenio->delete();
                $conveniosEliminados++;
            }

            // 3. Resetear estados de cuotas, eliminar moras y abonos a favor
            foreach ($prestamo->cuotas as $cuota) {
                // Eliminar todas las moras asociadas a esta cuota
                $morasCount = $cuota->moras()->count();
                $cuota->moras()->delete();
                $morasEliminadas += $morasCount;

                // Eliminar todos los abonos a favor asociados a esta cuota
                $abonosFavorCount = $cuota->abonosMoraFavor()->count();
                $cuota->abonosMoraFavor()->delete();
                $abonosFavorEliminados += $abonosFavorCount;

                // Resetear estado de cuota completamente a como estaba antes de cualquier pago
                $cuota->update([
                    'estado' => CuotaEstado::PENDIENTE,  // Estado: pendiente de pago
                    'monto_pagado' => 0.00,              // Sin pagos realizados
                    'cantidad_mora' => 0,                 // Sin mora acumulada
                ]);
                $cuotasReseteadas++;
            }

            // 4. Actualizar estado del préstamo usando el controlador centralizado
            $estadoController = new EstadoPrestamoController();
            $estadoController->calcularYActualizarEstado($prestamo, true, 'reset_payments');

            // 5. Regenerar moras para cuotas vencidas
            $morasRegeneradas = $this->regenerarMoras($prestamo);

            DB::commit();

            Log::info('Pagos reseteados exitosamente', [
                'prestamo_id' => $id,
                'operaciones_eliminadas' => $operacionesEliminadas,
                'convenios_eliminados' => $conveniosEliminados,
                'cuotas_reseteadas' => $cuotasReseteadas,
                'moras_eliminadas' => $morasEliminadas,
                'abonos_favor_eliminados' => $abonosFavorEliminados,
                'moras_regeneradas' => $morasRegeneradas,
            ]);

            $message = "Pagos reseteados exitosamente. {$operacionesEliminadas} operaciones eliminadas";
            if ($conveniosEliminados > 0) {
                $message .= ", {$conveniosEliminados} convenios eliminados";
            }
            $message .= ", {$cuotasReseteadas} cuotas reseteadas, {$morasEliminadas} moras eliminadas, {$abonosFavorEliminados} abonos a favor eliminados";
            if ($morasRegeneradas > 0) {
                $message .= ", {$morasRegeneradas} moras regeneradas para cuotas vencidas";
            }
            $message .= ".";

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'operaciones_eliminadas' => $operacionesEliminadas,
                    'convenios_eliminados' => $conveniosEliminados,
                    'cuotas_reseteadas' => $cuotasReseteadas,
                    'moras_eliminadas' => $morasEliminadas,
                    'abonos_favor_eliminados' => $abonosFavorEliminados,
                    'moras_regeneradas' => $morasRegeneradas,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al resetear pagos del préstamo', [
                'prestamo_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al resetear los pagos: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Regenera moras para cuotas vencidas después del reset
     *
     * @param Prestamo $prestamo
     * @return int Cantidad total de moras regeneradas
     */
    private function regenerarMoras(Prestamo $prestamo): int
    {
        $hoy = Carbon::today();
        $moraService = app(MoraService::class);
        $totalMorasRegeneradas = 0;

        foreach ($prestamo->cuotas as $cuota) {
            $fechaPago = Carbon::parse($cuota->fecha_pago);

            // Si la cuota está vencida y no está pagada
            if ($fechaPago->lt($hoy) && $cuota->estado !== CuotaEstado::PAGADO) {
                // NO cambiar el estado a VENCIDO - debe quedarse en PENDIENTE (0)
                // Las moras indican que está vencida, no el estado de la cuota

                // Usar el método del servicio para procesar moras de la cuota
                try {
                    $resultado = $moraService->procesarCuotaParaMoras($cuota);

                    if ($resultado['generadas'] > 0) {
                        $totalMorasRegeneradas += $resultado['generadas'];
                        Log::info("Regeneradas {$resultado['generadas']} moras para cuota {$cuota->id} (estado: PENDIENTE)");
                    }
                } catch (\Exception $e) {
                    Log::error("Error al regenerar moras para cuota {$cuota->id}: ".$e->getMessage());
                }
            }
        }

        return $totalMorasRegeneradas;
    }

    /**
     * Genera solo las moras faltantes para cuotas vencidas
     * NO regulariza moras existentes, solo crea las que faltan
     * 
     * @param Prestamo $prestamo
     * @return int Cantidad de moras generadas
     */
    private function generarMorasFaltantes(Prestamo $prestamo): int
    {
        $hoy = Carbon::today();
        $totalMorasGeneradas = 0;

        // Solo procesar cuotas vencidas que no estén pagadas
        $cuotasVencidas = $prestamo->cuotas()
            ->whereIn('estado', [CuotaEstado::PENDIENTE, CuotaEstado::PARCIAL, CuotaEstado::VENCIDO])
            ->where('fecha_pago', '<', $hoy)
            ->get();

        foreach ($cuotasVencidas as $cuota) {
            $fechaVencimiento = Carbon::parse($cuota->fecha_pago)->startOfDay();
            $diasVencidos = $fechaVencimiento->diffInDays($hoy);
            
            // Verificar cuántas moras PENDIENTES/PARCIALES existen (igual que moras_pendientes scope)
            $morasPendientes = $cuota->moras()
                ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                ->count();
            
            // Calcular cuántas moras deberían existir (máximo 7)
            $morasEsperadas = min($diasVencidos, 7);
            
            // Si faltan moras por generar
            if ($morasPendientes < $morasEsperadas) {
                $morasFaltantes = $morasEsperadas - $morasPendientes;
                $moraDiaria = $prestamo->mora ?? 4.00;
                
                // Generar solo las moras que faltan
                for ($i = 0; $i < $morasFaltantes; $i++) {
                    $diaMora = $morasPendientes + $i + 1;
                    $fechaMora = $fechaVencimiento->copy()->addDays($diaMora);
                    
                    // Verificar que no exista ya una mora en esta fecha
                    $moraExistente = $cuota->moras()
                        ->where('fecha', $fechaMora->format('Y-m-d'))
                        ->exists();
                    
                    if (!$moraExistente && $fechaMora->lte($hoy)) {
                        MoraCuota::create([
                            'cuota_id' => $cuota->id,
                            'fecha' => $fechaMora->format('Y-m-d'),
                            'dias_mora' => $diaMora,
                            'monto' => $moraDiaria,
                            'estado' => MoraCuotaEstado::PENDIENTE,
                            'monto_pagado' => 0,
                        ]);
                        
                        $totalMorasGeneradas++;
                        
                        Log::debug("Mora generada: Cuota {$cuota->id}, Día {$diaMora}, Fecha {$fechaMora->format('Y-m-d')}");
                    }
                }
                
                // Actualizar cantidad_mora en la cuota
                $totalMorasCuota = $cuota->moras()->sum('monto');
                $cuota->update(['cantidad_mora' => $totalMorasCuota]);
            }
        }

        return $totalMorasGeneradas;
    }

    public function recalcularComisiones(Request $request)
    {
        try {
            DB::beginTransaction();

            // Filtrar préstamos con plazos válidos
            $prestamos = Prestamo::whereIn('plazo', [12, 15, 18, 20])->get();

            $countPrestamos = 0;
            $countCuotas = 0;
            $errores = [];

            foreach ($prestamos as $prestamo) {
                try {
                    // Obtener cuotas del préstamo ordenadas
                    $cuotas = Cuota::where('prestamo_id', $prestamo->id)
                        ->orderBy('numero', 'ASC')
                        ->get();

                    if ($cuotas->isEmpty()) {
                        continue;
                    }

                    // RECALCULAR COMPLETAMENTE usando calcularCuotasInterno
                    $fechaInicio = Carbon::parse($cuotas->first()->fecha_pago);

                    // Llamar a calcularCuotasInterno para obtener los valores correctos
                    $resultadoCalculo = $this->calcularCuotasInterno(
                        $prestamo->monto,
                        $prestamo->plazo,
                        $fechaInicio
                    );

                    if (!isset($resultadoCalculo['cuotas']) || empty($resultadoCalculo['cuotas'])) {
                        $errores[] = "Préstamo {$prestamo->id}: Error al calcular cuotas";
                        continue;
                    }

                    // Log del recálculo
                    Log::info("🔄 Recalculando préstamo {$prestamo->id}", [
                        'monto' => $prestamo->monto,
                        'plazo' => $prestamo->plazo,
                        'cuotas_count' => count($resultadoCalculo['cuotas']),
                        'valor_cuota' => $resultadoCalculo['valorCuota'] ?? 0,
                        'total' => $resultadoCalculo['total'] ?? 0,
                    ]);

                    // Actualizar cada cuota con los valores recalculados
                    foreach ($cuotas as $index => $cuota) {
                        if (!isset($resultadoCalculo['cuotas'][$index])) {
                            continue;
                        }

                        $nuevosValores = $resultadoCalculo['cuotas'][$index];

                        // Log antes de actualizar
                        Log::debug("Cuota {$cuota->numero}: Antes [monto:{$cuota->monto}, capital:{$cuota->pago_capital}, igv:{$cuota->igv}] → Después [monto:{$nuevosValores['cuota']}, capital:{$nuevosValores['pagoCapital']}, igv:{$nuevosValores['igv']}]");

                        // Actualizar TODOS los valores financieros
                        $cuota->monto = $nuevosValores['cuota'];
                        $cuota->pago_capital = $nuevosValores['pagoCapital'];
                        $cuota->interes = $nuevosValores['interes'];
                        $cuota->comision = $nuevosValores['comision'];
                        $cuota->igv = $nuevosValores['igv'];
                        // Nota: saldo_capital no existe en la tabla cuotas
                        // Preservar: estado, monto_pagado, cantidad_mora, fecha_pago, numero

                        $cuota->save();
                        $countCuotas++;
                    }

                    $countPrestamos++;
                    Log::info("✅ Préstamo {$prestamo->id} recalculado: {$countCuotas} cuotas actualizadas");

                } catch (\Exception $e) {
                    $errores[] = "Préstamo {$prestamo->id}: {$e->getMessage()}";
                    Log::error("Error recalculando préstamo {$prestamo->id}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            DB::commit();

            $mensaje = "✅ Recalculadas {$countCuotas} cuotas de {$countPrestamos} préstamos.";
            if (!empty($errores)) {
                $mensaje .= " ⚠️ Errores: " . implode(', ', array_slice($errores, 0, 5));
            }

            return redirect()->back()->with('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al recalcular comisiones: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al recalcular comisiones: ' . $e->getMessage());
        }
    }

    /**
     * Determina si se debe aplicar filtro de cartera según el rol del usuario
     * Solo se aplica a: Asesor, Analista y JCC
     * Roles sin restricción: Admin, Oficina, GS, etc.
     */
    /**
     * Actualizar personal asignado (Analista, JCC, Asesor) via AJAX - Solo Admin
     */
    public function actualizarPersonal(Request $request, $id)
    {
        if (!auth()->user()->hasRole('Admin')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'tipo' => 'required|in:analista,jcc,asesor',
            'user_id' => 'required|exists:users,id',
        ]);

        $prestamo = Prestamo::findOrFail($id);
        $tipo = $request->tipo;
        $userId = $request->user_id;

        DB::beginTransaction();
        try {
            switch ($tipo) {
                case 'jcc':
                    if ($prestamo->carterasJcc()->where('estado', 1)->first()?->jcc_id != $userId) {
                        $prestamo->carterasJcc()->update(['estado' => 0]);
                        CarteraJcc::create([
                            'prestamo_id' => $prestamo->id,
                            'jcc_id' => $userId,
                            'fecha_registro' => now(),
                            'estado' => 1,
                        ]);
                    }
                    break;
                case 'asesor':
                    if ($prestamo->carterasAsesor()->where('estado', 1)->first()?->asesor_id != $userId) {
                        $prestamo->carterasAsesor()->update(['estado' => 0]);
                        CarteraAsesor::create([
                            'prestamo_id' => $prestamo->id,
                            'asesor_id' => $userId,
                            'fecha_registro' => now(),
                            'estado' => 1,
                        ]);
                    }
                    break;
                case 'analista':
                    if ($prestamo->carterasAnalista()->where('estado', 1)->first()?->analista_id != $userId) {
                        $prestamo->carterasAnalista()->update(['estado' => 0]);
                        CarteraAnalista::create([
                            'prestamo_id' => $prestamo->id,
                            'analista_id' => $userId,
                            'fecha_registro' => now(),
                            'estado' => 1,
                        ]);
                    }
                    break;
            }

            DB::commit();

            $user = User::find($userId);
            return response()->json([
                'success' => true,
                'codigo' => $user->codigo ?? $user->name,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar personal asignado', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error al actualizar'], 500);
        }
    }

    public function actualizarZonaSucursal(Request $request, $id)
    {
        if (!auth()->user()->hasRole('Admin')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'zona_id' => 'required|exists:zonas,id',
            'sucursal_id' => 'required|exists:sucursales,id',
        ]);

        $prestamo = Prestamo::findOrFail($id);
        $direccion = $prestamo->cliente->persona->direcciones()->first();

        if (!$direccion) {
            return response()->json(['error' => 'No se encontró dirección del cliente'], 404);
        }

        try {
            $direccion->update([
                'zona_id' => $request->zona_id,
                'sucursal_id' => $request->sucursal_id,
            ]);

            $zona = \App\Models\Zona::find($request->zona_id);
            $sucursal = \App\Models\Sucursal::find($request->sucursal_id);

            return response()->json([
                'success' => true,
                'zona_nombre' => $zona->nombre,
                'sucursal_nombre' => $sucursal->sucursal,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar zona/sucursal', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error al actualizar'], 500);
        }
    }

    public function actualizarCuenta(Request $request, $id)
    {
        if (!auth()->user()->hasRole('Admin')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'cuenta_id' => 'required|exists:cuentas,id',
        ]);

        $prestamo = Prestamo::findOrFail($id);

        try {
            $prestamo->update(['cuenta_id' => $request->cuenta_id]);
            $cuenta = \App\Models\Cuenta::find($request->cuenta_id);

            return response()->json([
                'success' => true,
                'codigo' => $cuenta->codigo,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar cuenta', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error al actualizar'], 500);
        }
    }

    private function debeAplicarFiltroCartera(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        // Si el usuario es Admin, NO aplicar filtro de cartera (acceso completo)
        if (auth()->user()->hasRole('Admin')) {
            return false;
        }

        $rolesRestringidos = ['Asesor', 'Analista', 'JCC'];

        foreach ($rolesRestringidos as $rol) {
            if (auth()->user()->hasRole($rol)) {
                return true;
            }
        }

        return false;
    }
}