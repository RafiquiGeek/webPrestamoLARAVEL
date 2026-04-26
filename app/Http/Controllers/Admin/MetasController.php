<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meta;
use App\Models\MetaComision;
use App\Models\MetaCumplimiento;
use App\Models\MetaConfiguracion;
use App\Models\User;
use App\Services\MetaComisionService;
use App\Enums\NivelCalificacion;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MetasController extends Controller
{
    protected $metaService;

    public function __construct(MetaComisionService $metaService)
    {
        $this->metaService = $metaService;
    }

    public function index(Request $request)
    {
        $mes = $request->get('mes', date('n'));
        $anio = $request->get('anio', date('Y'));

        $metas = Meta::with(['asesor.persona', 'cumplimiento'])
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->get();

        $stats = [
            'total_asesores' => $metas->count(),
            'cumplen_meta' => $metas->filter(fn($m) => $m->cumplimiento && $m->cumplimiento->porcentaje_cumplimiento >= 100)->count(),
            'promedio_cumplimiento' => $metas->avg(fn($m) => $m->cumplimiento ? $m->cumplimiento->porcentaje_cumplimiento : 0),
            'total_comisiones' => $metas->sum(fn($m) => $m->cumplimiento ? $m->cumplimiento->comision_final : 0),
        ];

        $config = MetaConfiguracion::first() ?? new MetaConfiguracion(['umbral_morosidad' => 20]);

        return view('admin.metas.index', compact('metas', 'mes', 'anio', 'stats', 'config'));
    }

    public function create(Request $request)
    {
        $mes = $request->get('mes', date('n'));
        $anio = $request->get('anio', date('Y'));

        $asesores = User::role('Asesor')
            ->where('status', 1)
            ->with('persona')
            ->get();

        $metasExistentes = Meta::where('mes', $mes)
            ->where('anio', $anio)
            ->get()
            ->keyBy('asesor_id');

        // Obtener cumplimiento del mes anterior para referencia
        $prevDate = Carbon::create($anio, $mes)->subMonth();
        $metasPrevio = MetaCumplimiento::where('mes', $prevDate->month)
            ->where('anio', $prevDate->year)
            ->get()
            ->keyBy('asesor_id');

        $config = MetaConfiguracion::first() ?? new MetaConfiguracion(['umbral_morosidad' => 20]);

        return view('admin.metas.create', compact('asesores', 'mes', 'anio', 'metasExistentes', 'config', 'metasPrevio'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'mes' => 'required|integer|between:1,12',
            'anio' => 'required|integer',
            'metas' => 'required|array',
        ]);

        foreach ($request->metas as $asesorId => $data) {
            if (isset($data['cantidad']) && $data['cantidad'] !== '') {
                Meta::updateOrCreate(
                    ['asesor_id' => $asesorId, 'anio' => $request->anio, 'mes' => $request->mes],
                    [
                        'cantidad_objetivo' => $data['cantidad'], 
                        'observaciones' => $data['observaciones'] ?? null,
                        'estado' => 'pendiente'
                    ]
                );
            }
        }

        return redirect()->route('admin.metas.index', ['mes' => $request->mes, 'anio' => $request->anio])
            ->with('success', 'Metas asignadas correctamente.');
    }

    public function show(Meta $meta, Request $request)
    {
        $asesor = $meta->asesor;
        $cumplimiento = $meta->cumplimiento;

        // Filtros para la cartera mezclada
        $mes = $request->get('mes', $meta->mes);
        $anio = $request->get('anio', $meta->anio);
        $tipo = $request->get('tipo');
        $mora = $request->get('mora');
        
        $historial = MetaCumplimiento::where('asesor_id', $asesor->id)
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->take(6)
            ->get();

        $config = MetaConfiguracion::first() ?? new MetaConfiguracion(['umbral_morosidad' => 20]);

        // Obtener préstamos filtrados para la sección de cartera
        $prestamos = \App\Models\Prestamo::with(['cliente.persona', 'cuotas', 'operaciones'])
            ->whereHas('carterasAsesor', function($q) use ($asesor) {
                $q->where('asesor_id', $asesor->id)->where('estado', 1);
            })
            ->whereMonth('fecha_atencion', $mes)
            ->whereYear('fecha_atencion', $anio)
            ->whereNotIn('estado', ['Anulado', 'Rechazado'])
            ->when($tipo, function($q) use ($tipo) {
                if ($tipo === 'nuevo') {
                    $q->where('tipo_solicitud', 'like', '%Nueva%');
                } elseif ($tipo === 'renovacion') {
                    $q->where('tipo_solicitud', 'like', '%Renovación%');
                }
            })
            ->when($mora, function($q) use ($mora) {
                if ($mora === 'con_mora') {
                    $q->whereHas('cuotas', function($sq) {
                        $sq->where('cantidad_mora', '>', 0)
                          ->where('estado', '!=', \App\Enums\CuotaEstado::PAGADO->value);
                    });
                } elseif ($mora === 'sin_mora') {
                    $q->whereDoesntHave('cuotas', function($sq) {
                        $sq->where('cantidad_mora', '>', 0)
                          ->where('estado', '!=', \App\Enums\CuotaEstado::PAGADO->value);
                    });
                }
            })
            ->get();

        // Métricas dinámicas basadas en los préstamos filtrados
        $totalFiltrados = $prestamos->count();
        $morososFiltrados = $prestamos->filter(fn($p) => in_array($p->estado, ['Moroso', 'Vigente con moras']))->count();
        $renovacionesFiltradas = $prestamos->filter(fn($p) => str_contains(strtolower($p->tipo_solicitud ?? ''), 'renovación'))->count();

        $metricas = [
            'prestamos_logrados' => $totalFiltrados,
            'objetivo' => $meta->cantidad_objetivo,
            'efectividad' => $meta->cantidad_objetivo > 0 ? ($totalFiltrados / $meta->cantidad_objetivo) * 100 : 0,
            'morosos' => $morososFiltrados,
            'porcentaje_mora' => $totalFiltrados > 0 ? ($morososFiltrados / $totalFiltrados) * 100 : 0,
            'renovaciones' => $renovacionesFiltradas,
            'nuevos' => $totalFiltrados - $renovacionesFiltradas,
            'comision' => $cumplimiento?->comision_final ?? 0,
            'penalizado' => ($cumplimiento?->penalizado_morosidad ?? false),
        ];

        return view('admin.metas.show', compact('meta', 'asesor', 'cumplimiento', 'historial', 'config', 'prestamos', 'mes', 'anio', 'tipo', 'mora', 'metricas'));
    }

    public function edit(Meta $meta)
    {
        return view('admin.metas.edit', compact('meta'));
    }

    public function update(Request $request, Meta $meta)
    {
        $request->validate([
            'cantidad_objetivo' => 'required|integer|min:0',
            'estado' => 'required|string',
        ]);

        $meta->update($request->only('cantidad_objetivo', 'estado', 'observaciones'));

        return redirect()->route('admin.metas.index', ['mes' => $meta->mes, 'anio' => $meta->anio])
            ->with('success', 'Meta actualizada correctamente.');
    }

    public function comisiones()
    {
        $comisiones = MetaComision::all()->groupBy('nivel');
        $config = MetaConfiguracion::first() ?? new MetaConfiguracion(['umbral_morosidad' => 20]);
        $niveles = NivelCalificacion::cases();
        
        return view('admin.metas.comisiones', compact('comisiones', 'config', 'niveles'));
    }

    public function guardarComisiones(Request $request)
    {
        $request->validate([
            'comisiones' => 'array',
            'umbral_morosidad' => 'required|numeric|between:0,100',
        ]);

        // Guardar configuración global
        MetaConfiguracion::updateOrCreate(['id' => 1], ['umbral_morosidad' => $request->umbral_morosidad]);

        // Guardar rangos de comisiones
        if ($request->has('comisiones')) {
            MetaComision::truncate();

            foreach ($request->comisiones as $nivel => $rangos) {
                foreach ($rangos as $rango) {
                    MetaComision::create([
                        'nivel' => strtolower($nivel),
                        'porcentaje_minimo' => $rango['porcentaje_minimo'],
                        'porcentaje_maximo' => $rango['porcentaje_maximo'],
                        'monto_comision' => $rango['monto_comision'],
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Configuración de comisiones y rangos actualizada correctamente.');
    }

    public function guardarConfiguracion(Request $request)
    {
        $request->validate([
            'umbral_morosidad' => 'required|numeric|between:0,100',
        ]);

        MetaConfiguracion::updateOrCreate(['id' => 1], ['umbral_morosidad' => $request->umbral_morosidad]);

        return redirect()->back()->with('success', 'Configuración guardada.');
    }

    public function recalcular(Request $request)
    {
        $mes = $request->get('mes', date('n'));
        $anio = $request->get('anio', date('Y'));

        $this->metaService->calcularTodosLosCumplimientos($anio, $mes);

        return redirect()->back()->with('success', 'Cumplimientos recalculados correctamente.');
    }

    public function exportarCartera(User $asesor, Request $request)
    {
        $mes = $request->get('mes', date('n'));
        $anio = $request->get('anio', date('Y'));
        $tipo = $request->get('tipo');
        $mora = $request->get('mora');

        $prestamos = \App\Models\Prestamo::with(['cliente.persona', 'cuotas', 'operaciones'])
            ->whereHas('carterasAsesor', function($q) use ($asesor) {
                $q->where('asesor_id', $asesor->id)->where('estado', 1);
            })
            ->whereMonth('fecha_atencion', $mes)
            ->whereYear('fecha_atencion', $anio)
            ->whereNotIn('estado', ['Anulado', 'Rechazado'])
            ->when($tipo, function($q) use ($tipo) {
                if ($tipo === 'nuevo') {
                    $q->where('tipo_solicitud', 'like', '%Nueva%');
                } elseif ($tipo === 'renovacion') {
                    $q->where('tipo_solicitud', 'like', '%Renovación%');
                }
            })
            ->when($mora, function($q) use ($mora) {
                if ($mora === 'con_mora') {
                    $q->whereHas('cuotas', function($sq) {
                        $sq->where('cantidad_mora', '>', 0)
                          ->where('estado', '!=', \App\Enums\CuotaEstado::PAGADO->value);
                    });
                } elseif ($mora === 'sin_mora') {
                    $q->whereDoesntHave('cuotas', function($sq) {
                        $sq->where('cantidad_mora', '>', 0)
                          ->where('estado', '!=', \App\Enums\CuotaEstado::PAGADO->value);
                    });
                }
            })
            ->get();

        $filename = "cartera_asesor_{$asesor->codigo}_{$anio}_{$mes}.xlsx";

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CarteraAsesorExport($prestamos),
            $filename
        );
    }
}
