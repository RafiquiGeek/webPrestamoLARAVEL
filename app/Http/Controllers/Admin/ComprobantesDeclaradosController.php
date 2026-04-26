<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comprobante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ComprobantesDeclaradosController extends Controller
{
    /**
     * Display a listing of all declared comprobantes
     */
    public function index(Request $request)
    {
        $query = Comprobante::with(['cliente.persona', 'prestamo'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_comprobante')) {
            $query->where('tipo_comprobante', $request->tipo_comprobante);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_emision', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_emision', '<=', $request->fecha_hasta);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('numero', 'like', "%{$buscar}%")
                    ->orWhere('serie', 'like', "%{$buscar}%")
                    ->orWhereHas('cliente.persona', function ($query) use ($buscar) {
                        $query->where('nombres', 'like', "%{$buscar}%")
                            ->orWhere('ape_pat', 'like', "%{$buscar}%")
                            ->orWhere('ape_mat', 'like', "%{$buscar}%")
                            ->orWhere('documento', 'like', "%{$buscar}%");
                    });
            });
        }

        $comprobantes = $query->paginate(50);

        return view('admin.comprobantes.declarados', compact('comprobantes'));
    }

    /**
     * Show the XML content of a comprobante
     */
    public function verXml($id)
    {
        $comprobante = Comprobante::findOrFail($id);

        if (!$comprobante->xml_content) {
            return response()->json([
                'error' => 'No hay contenido XML disponible para este comprobante'
            ], 404);
        }

        return response($comprobante->xml_content, 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Download the XML file
     */
    public function descargarXml($id)
    {
        $comprobante = Comprobante::findOrFail($id);

        if (!$comprobante->xml_content) {
            abort(404, 'No hay contenido XML disponible');
        }

        $filename = $comprobante->serie . '-' . str_pad($comprobante->numero, 6, '0', STR_PAD_LEFT) . '.xml';

        return Response::make($comprobante->xml_content, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    /**
     * Download the CDR (Constancia de Recepción)
     */
    public function descargarCdr($id)
    {
        $comprobante = Comprobante::findOrFail($id);

        if (!$comprobante->cdr_zip) {
            abort(404, 'No hay CDR disponible');
        }

        // Si el CDR está en base64
        $cdrContent = base64_decode($comprobante->cdr_zip);
        $filename = 'R-' . $comprobante->serie . '-' . str_pad($comprobante->numero, 6, '0', STR_PAD_LEFT) . '.zip';

        return Response::make($cdrContent, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    /**
     * Show comprobante details
     */
    public function show($id)
    {
        $comprobante = Comprobante::with(['cliente.persona', 'prestamo', 'cuota'])
            ->findOrFail($id);

        return view('admin.comprobantes.show_detalle', compact('comprobante'));
    }

    /**
     * Export to Excel
     */
    public function exportar(Request $request)
    {
        $query = Comprobante::with(['cliente.persona', 'prestamo', 'cuota'])
            ->orderBy('created_at', 'desc');

        // Aplicar los mismos filtros que en el index
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_comprobante')) {
            $query->where('tipo_comprobante', $request->tipo_comprobante);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_emision', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_emision', '<=', $request->fecha_hasta);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('numero', 'like', "%{$buscar}%")
                    ->orWhere('serie', 'like', "%{$buscar}%")
                    ->orWhereHas('cliente.persona', function ($query) use ($buscar) {
                        $query->where('nombres', 'like', "%{$buscar}%")
                            ->orWhere('ape_pat', 'like', "%{$buscar}%")
                            ->orWhere('ape_mat', 'like', "%{$buscar}%")
                            ->orWhere('documento', 'like', "%{$buscar}%");
                    });
            });
        }

        $comprobantes = $query->get();

        return Excel::download(
            new \App\Exports\ComprobantesDeclaradosExport($comprobantes),
            'comprobantes_declarados_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Reenviar comprobante a SUNAT
     */
    public function reenviar($id)
    {
        $comprobante = Comprobante::findOrFail($id);

        try {
            $greenterService = new \App\Services\GreenterService();

            // Recrear el invoice desde los datos almacenados
            $invoice = $greenterService->createInvoice(
                [
                    'tipo_documento' => $comprobante->cliente->persona->tipo_documento,
                    'numero_documento' => $comprobante->cliente->persona->documento,
                    'razon_social' => trim($comprobante->cliente->persona->nombres . ' ' .
                                          $comprobante->cliente->persona->ape_pat . ' ' .
                                          $comprobante->cliente->persona->ape_mat),
                    'direccion' => $comprobante->cliente->persona->direccion ?? 'Lima',
                ],
                $comprobante->items,
                $comprobante->numero,
                $comprobante->serie,
                $comprobante->tipo_comprobante
            );

            $result = $greenterService->sendInvoice($invoice);

            if ($result['success']) {
                $comprobante->update([
                    'estado' => 'ENVIADO',
                    'cdr_zip' => $result['cdr'] ?? null,
                    'observaciones' => $result['message'] ?? null,
                    'mensaje_error' => null,
                    'codigo_error' => null,
                ]);

                return redirect()->back()->with('success', 'Comprobante reenviado exitosamente a SUNAT');
            } else {
                $comprobante->update([
                    'estado' => 'ERROR',
                    'mensaje_error' => $result['message'] ?? 'Error desconocido',
                    'codigo_error' => $result['code'] ?? null,
                ]);

                return redirect()->back()->with('error', 'Error al reenviar: ' . ($result['message'] ?? 'Error desconocido'));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Excepción al reenviar: ' . $e->getMessage());
        }
    }
}
