<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TableroColumna;
use App\Models\Tarea;
use App\Models\TareaArchivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TareasController extends Controller
{
    public function index()
    {
        return view('admin.tareas.index');
    }

    public function actualizarOrden(Request $request)
    {
        $request->validate([
            'tarea_id' => 'required|exists:tareas,id',
            'columna_id' => 'required|exists:tablero_columnas,id',
            'nuevo_orden' => 'required|integer|min:0',
        ]);

        $tarea = Tarea::findOrFail($request->tarea_id);

        if (! Auth::user()->hasRole('Admin') && $tarea->asignado_a != Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $tarea->columna_id = $request->columna_id;
        $tarea->orden = $request->nuevo_orden;
        $tarea->save();

        Tarea::where('columna_id', $request->columna_id)
            ->where('id', '!=', $tarea->id)
            ->where('orden', '>=', $request->nuevo_orden)
            ->increment('orden');

        return response()->json(['success' => true]);
    }

    public function subirArchivo(Request $request)
    {
        $request->validate([
            'tarea_id' => 'required|exists:tareas,id',
            'archivo' => 'required|file|max:10240',
        ]);

        $tarea = Tarea::findOrFail($request->tarea_id);

        if (! Auth::user()->hasRole('Admin') && $tarea->asignado_a != Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $archivo = $request->file('archivo');
        $nombre = time().'_'.$archivo->getClientOriginalName();
        $ruta = $archivo->storeAs('tareas/'.$tarea->id, $nombre, 'public');

        $tareaArchivo = TareaArchivo::create([
            'tarea_id' => $tarea->id,
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'ruta' => $ruta,
            'tipo_mime' => $archivo->getMimeType(),
            'tamaño' => $archivo->getSize(),
            'subido_por' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'archivo' => $tareaArchivo->load('usuario'),
        ]);
    }

    public function eliminarArchivo($id)
    {
        $archivo = TareaArchivo::findOrFail($id);
        $tarea = $archivo->tarea;

        if (! Auth::user()->hasRole('Admin') && $tarea->asignado_a != Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $archivo->delete();

        return response()->json(['success' => true]);
    }

    public function crearColumna(Request $request)
    {
        if (! Auth::user()->hasRole('Admin')) {
            return response()->json(['error' => 'Solo administradores pueden crear columnas'], 403);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'color' => 'required|string|max:7',
        ]);

        $columna = TableroColumna::create([
            'nombre' => $request->nombre,
            'color' => $request->color,
        ]);

        return response()->json([
            'success' => true,
            'columna' => $columna,
        ]);
    }

    public function actualizarColumna(Request $request, $id)
    {
        if (! Auth::user()->hasRole('Admin')) {
            return response()->json(['error' => 'Solo administradores pueden editar columnas'], 403);
        }

        $columna = TableroColumna::findOrFail($id);

        if ($columna->es_sistema) {
            return response()->json(['error' => 'No se pueden editar columnas del sistema'], 403);
        }

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'color' => 'sometimes|string|max:7',
            'activo' => 'sometimes|boolean',
        ]);

        $columna->update($request->only(['nombre', 'color', 'activo']));

        return response()->json([
            'success' => true,
            'columna' => $columna,
        ]);
    }

    public function eliminarColumna($id)
    {
        if (! Auth::user()->hasRole('Admin')) {
            return response()->json(['error' => 'Solo administradores pueden eliminar columnas'], 403);
        }

        $columna = TableroColumna::findOrFail($id);

        if ($columna->es_sistema) {
            return response()->json(['error' => 'No se pueden eliminar columnas del sistema'], 403);
        }

        if ($columna->tareas()->count() > 0) {
            return response()->json(['error' => 'No se puede eliminar una columna con tareas'], 400);
        }

        $columna->delete();

        return response()->json(['success' => true]);
    }
}
