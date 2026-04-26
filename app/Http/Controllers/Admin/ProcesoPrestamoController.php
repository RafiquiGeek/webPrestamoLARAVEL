<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FondoProvisional;
use App\Models\MetodoDePago;
use App\Models\Prestamo;
use App\Models\User;
use Illuminate\Http\Request;

class ProcesoPrestamoController extends Controller
{
    /**
     * Mostrar la vista del proceso de préstamo con tres pasos
     */
    public function index($prestamoId)
    {
        $prestamo = Prestamo::with(['cliente.persona', 'operaciones'])->findOrFail($prestamoId);

        // Validar que el préstamo esté en un estado válido para el proceso
        $estadosValidos = ['Nueva Solicitud', 'Aprobado', 'Por Desembolsar', 'Vigente'];
        if (!in_array($prestamo->estado, $estadosValidos)) {
            return redirect()->route('admin.prestamos.index')
                ->with('error', 'Este préstamo no está en un estado válido para el proceso. Estado actual: ' . $prestamo->estado);
        }

        // Paso 1: Verificar si está aprobado
        $pasoAprobado = in_array($prestamo->estado, ['Aprobado', 'Por Desembolsar', 'Vigente']);

        // Paso 2: Verificar si tiene fondo provisional (cualquier estado válido)
        $fondoProvisional = FondoProvisional::where('prestamo_id', $prestamoId)
            ->whereIn('estado', [
                FondoProvisional::ESTADO_ENTREGADO,
                FondoProvisional::ESTADO_EXONERADO,
                FondoProvisional::ESTADO_RENDIDO
            ])
            ->first();
        $pasoFondoProvisional = $fondoProvisional !== null;
        $fondoExonerado = $fondoProvisional && $fondoProvisional->estado === 'exonerado';

        // Paso 3: Verificar si está desembolsado
        $operacionDesembolso = $prestamo->operaciones()
            ->where('tipo_operacion', 'Desembolso')
            ->first();
        $pasoDesembolsado = $operacionDesembolso !== null;

        // Si ya está desembolsado, cambiar estado a Por Desembolsar si no está vigente
        if ($pasoAprobado && $pasoFondoProvisional && !$pasoDesembolsado) {
            if ($prestamo->estado !== 'Por Desembolsar') {
                $prestamo->estado = 'Por Desembolsar';
                $prestamo->save();
            }
        }

        // Obtener asesores para el fondo provisional
        $asesores = User::whereHas('roles', function($query) {
            $query->where('name', 'asesor');
        })->get();

        // Calcular montos para el fondo provisional
        $montoCapital = $prestamo->cantidad_solicitada;
        $montoFondo = FondoProvisional::calcularMontoFondo($montoCapital);

        // Obtener métodos de pago para el desembolso
        $metodosDePago = MetodoDePago::where('status', 1)->get();

        return view('admin.proceso-prestamo.index', compact(
            'prestamo',
            'pasoAprobado',
            'pasoFondoProvisional',
            'pasoDesembolsado',
            'asesores',
            'fondoProvisional',
            'fondoExonerado',
            'operacionDesembolso',
            'montoCapital',
            'montoFondo',
            'metodosDePago'
        ));
    }

    /**
     * Aprobar el préstamo (Paso 1)
     */
    public function aprobar($prestamoId)
    {
        $prestamo = Prestamo::findOrFail($prestamoId);

        if ($prestamo->estado !== 'Nueva Solicitud') {
            return redirect()->back()
                ->with('error', 'Este préstamo ya ha sido aprobado o no está en estado "Nueva Solicitud".');
        }

        $prestamo->estado = 'Aprobado';
        $prestamo->save();

        return redirect()->route('admin.proceso-prestamo.index', $prestamoId)
            ->with('success', 'Préstamo aprobado exitosamente. Puedes continuar con el siguiente paso.');
    }
}
