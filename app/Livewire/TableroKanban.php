<?php

namespace App\Livewire;

use App\Models\TableroColumna;
use App\Models\Tarea;
use App\Models\TareaArchivo;
use App\Models\TareaComentario;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class TableroKanban extends Component
{
    use WithFileUploads;

    public $showCreateModal = false;

    public $showEditModal = false;

    public $showViewModal = false;

    public $showColumnModal = false;

    public $tareaSeleccionadaId = null;

    public $columnaSeleccionadaId = null;

    public $titulo = '';

    public $descripcion = '';

    public $asignado_a = '';

    public $prioridad = 'media';

    public $fecha_vencimiento = '';

    public $tiempo_estimado = '';

    public $archivos = [];

    public $nuevosArchivos = [];

    public $archivosContador = 0;

    public $nuevoComentario = '';

    public $nombreColumna = '';

    public $colorColumna = '#6c757d';

    public $filtroEstado = '';

    public $filtroPrioridad = '';

    public $filtroUsuario = '';

    public $busqueda = '';

    protected $listeners = [
        'tareaMovida' => 'moverTarea',
        'refreshTablero' => '$refresh',
        'eliminarArchivo' => 'eliminarArchivo',
    ];

    protected $rules = [
        'titulo' => 'required|min:3',
        'asignado_a' => 'required|exists:users,id',
        'prioridad' => 'required|in:baja,media,alta,urgente',
        'descripcion' => 'nullable|string',
        'fecha_vencimiento' => 'nullable|date',
        'tiempo_estimado' => 'nullable|numeric|min:0.1',
        'nuevosArchivos.*' => 'nullable|file|max:10240|mimes:jpeg,jpg,png,gif,pdf,doc,docx,xls,xlsx,txt',
    ];

    public function mount()
    {
        // No necesitamos cargar datos en mount para evitar problemas de serialización
    }

    public function getColumnasProperty()
    {
        return TableroColumna::activas()->get();
    }

    public function getTareasProperty()
    {
        $query = Tarea::with(['asignadoPor', 'asignadoA', 'columna', 'archivos']);

        if (! Auth::user()->hasRole('Admin')) {
            $query->where('asignado_a', Auth::id());
        }

        if ($this->filtroEstado) {
            $query->where('estado', $this->filtroEstado);
        }

        if ($this->filtroPrioridad) {
            $query->where('prioridad', $this->filtroPrioridad);
        }

        if ($this->filtroUsuario && Auth::user()->hasRole('Admin')) {
            $query->where('asignado_a', $this->filtroUsuario);
        }

        if ($this->busqueda) {
            $query->where(function ($q) {
                $q->where('titulo', 'like', '%'.$this->busqueda.'%')
                    ->orWhere('descripcion', 'like', '%'.$this->busqueda.'%');
            });
        }

        return $query->orderBy('orden')->get()->groupBy('columna_id');
    }

    public function getUsuariosProperty()
    {
        return User::orderBy('name')->get();
    }

    public function getTareaSeleccionadaProperty()
    {
        return $this->tareaSeleccionadaId ? Tarea::with(['asignadoPor', 'asignadoA', 'archivos.usuario', 'comentarios.usuario'])
            ->find($this->tareaSeleccionadaId) : null;
    }

    public function getColumnaSeleccionadaProperty()
    {
        return $this->columnaSeleccionadaId ? TableroColumna::find($this->columnaSeleccionadaId) : null;
    }

    public function abrirCrearTarea($columnaId = null)
    {
        $this->reset(['titulo', 'descripcion', 'asignado_a', 'prioridad', 'fecha_vencimiento', 'tiempo_estimado', 'nuevosArchivos', 'archivosContador']);

        // Resetear también todos los modales
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showViewModal = false;

        $this->prioridad = 'media'; // Valor por defecto
        $this->inicializarArchivos();

        $this->columnaSeleccionadaId = $columnaId;

        $this->showCreateModal = true;
    }

    public function inicializarArchivos()
    {
        $this->nuevosArchivos = [];
        $this->archivosContador = 0;
        $this->agregarCampoArchivo();
    }

    public function agregarCampoArchivo()
    {
        $this->nuevosArchivos[$this->archivosContador] = null;
        $this->archivosContador++;
    }

    public function removerCampoArchivo($index)
    {
        unset($this->nuevosArchivos[$index]);

        // Si no quedan campos, agregar uno por defecto
        if (empty($this->nuevosArchivos)) {
            $this->agregarCampoArchivo();
        }
    }

    public function crearTarea()
    {
        $this->validate();

        $columnaId = $this->columnaSeleccionadaId ?: $this->columnas->first()->id;

        $tarea = Tarea::create([
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'asignado_por' => Auth::id(),
            'asignado_a' => $this->asignado_a,
            'columna_id' => $columnaId,
            'prioridad' => $this->prioridad,
            'fecha_asignacion' => now(),
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'tiempo_estimado' => $this->tiempo_estimado,
            'estado' => 'pendiente',
        ]);

        $this->procesarArchivos($tarea);

        $this->cerrarModales();

        $this->dispatch('swal:success', [
            'title' => 'Tarea creada',
            'text' => 'La tarea se ha creado exitosamente',
        ]);
    }

    public function verTarea($tareaId)
    {
        $tarea = Tarea::findOrFail($tareaId);

        if (! Auth::user()->hasRole('Admin') && $tarea->asignado_a != Auth::id()) {
            return;
        }

        $this->tareaSeleccionadaId = $tareaId;
        $this->showViewModal = true;
    }

    public function editarTarea($tareaId)
    {
        $tarea = Tarea::findOrFail($tareaId);

        if (! Auth::user()->hasRole('Admin') && $tarea->asignado_a != Auth::id()) {
            return;
        }

        $this->tareaSeleccionadaId = $tareaId;
        $this->titulo = $tarea->titulo;
        $this->descripcion = $tarea->descripcion;
        $this->asignado_a = $tarea->asignado_a;
        $this->prioridad = $tarea->prioridad;
        $this->fecha_vencimiento = $tarea->fecha_vencimiento ? $tarea->fecha_vencimiento->format('Y-m-d\TH:i') : '';
        $this->tiempo_estimado = $tarea->tiempo_estimado;

        $this->inicializarArchivos();

        $this->showEditModal = true;
    }

    public function actualizarTarea()
    {
        $this->validate();

        $tarea = Tarea::findOrFail($this->tareaSeleccionadaId);

        $tarea->update([
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'asignado_a' => $this->asignado_a,
            'prioridad' => $this->prioridad,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'tiempo_estimado' => $this->tiempo_estimado,
        ]);

        $this->procesarArchivos($tarea);

        $this->cerrarModales();

        $this->dispatch('swal:success', [
            'title' => 'Tarea actualizada',
            'text' => 'La tarea se ha actualizado exitosamente',
        ]);
    }

    public function eliminarTarea($tareaId)
    {
        $tarea = Tarea::findOrFail($tareaId);

        if (! Auth::user()->hasRole('Admin') && $tarea->asignado_a != Auth::id()) {
            return;
        }

        $tarea->delete();

        $this->dispatch('swal:success', [
            'title' => 'Tarea eliminada',
            'text' => 'La tarea se ha eliminado exitosamente',
        ]);
    }

    public function iniciarTarea($tareaId)
    {
        $tarea = Tarea::findOrFail($tareaId);

        if ($tarea->asignado_a != Auth::id() && ! Auth::user()->hasRole('Admin')) {
            return;
        }

        $tarea->iniciarTarea();

        $this->dispatch('swal:success', [
            'title' => 'Tarea iniciada',
            'text' => 'La tarea se ha iniciado y movido a "En Progreso"',
        ]);
    }

    public function enviarARevision($tareaId)
    {
        $tarea = Tarea::findOrFail($tareaId);

        if ($tarea->asignado_a != Auth::id() && ! Auth::user()->hasRole('Admin')) {
            return;
        }

        $tarea->enviarARevision();

        $this->dispatch('swal:success', [
            'title' => 'Tarea enviada a revisión',
            'text' => 'La tarea se ha enviado a revisión',
        ]);
    }

    public function aprobarTarea($tareaId)
    {
        $tarea = Tarea::findOrFail($tareaId);

        if (! Auth::user()->hasRole('Admin')) {
            return;
        }

        $tarea->aprobarTarea();

        $this->dispatch('swal:success', [
            'title' => 'Tarea aprobada',
            'text' => 'La tarea ha sido aprobada y completada',
        ]);
    }

    public function actualizarProgreso($tareaId, $progreso)
    {
        $tarea = Tarea::findOrFail($tareaId);

        if ($tarea->asignado_a != Auth::id() && ! Auth::user()->hasRole('Admin')) {
            return;
        }

        $tarea->update(['progreso' => $progreso]);

    }

    public function moverTarea($tareaId, $columnaId, $nuevoOrden)
    {
        $tarea = Tarea::findOrFail($tareaId);

        if (! Auth::user()->hasRole('Admin') && $tarea->asignado_a != Auth::id()) {
            return;
        }

        $columnaAnterior = $tarea->columna_id;

        $tarea->columna_id = $columnaId;
        $tarea->orden = $nuevoOrden;
        $tarea->save();

        Tarea::where('columna_id', $columnaId)
            ->where('id', '!=', $tarea->id)
            ->where('orden', '>=', $nuevoOrden)
            ->increment('orden');

        if ($columnaAnterior != $columnaId) {
            Tarea::where('columna_id', $columnaAnterior)
                ->where('orden', '>', $tarea->orden)
                ->decrement('orden');
        }

    }

    public function agregarComentario()
    {
        if (! $this->nuevoComentario || ! $this->tareaSeleccionadaId) {
            return;
        }

        TareaComentario::create([
            'tarea_id' => $this->tareaSeleccionadaId,
            'user_id' => Auth::id(),
            'comentario' => $this->nuevoComentario,
        ]);

        $this->nuevoComentario = '';
    }

    protected function procesarArchivos($tarea)
    {
        if ($this->nuevosArchivos) {
            foreach ($this->nuevosArchivos as $archivo) {
                // Solo procesar archivos que no sean null
                if ($archivo) {
                    $nombre = time().'_'.$archivo->getClientOriginalName();
                    $ruta = $archivo->storeAs('tareas/'.$tarea->id, $nombre, 'public');

                    TareaArchivo::create([
                        'tarea_id' => $tarea->id,
                        'nombre_archivo' => $archivo->getClientOriginalName(),
                        'ruta' => $ruta,
                        'tipo_mime' => $archivo->getMimeType(),
                        'tamaño' => $archivo->getSize(),
                        'subido_por' => Auth::id(),
                    ]);
                }
            }
        }
    }

    public function eliminarArchivo($archivoId)
    {
        $archivo = TareaArchivo::findOrFail($archivoId);

        if (! Auth::user()->hasRole('Admin') && $archivo->tarea->asignado_a != Auth::id()) {
            return;
        }

        $archivo->delete();
    }

    public function abrirModalColumna()
    {
        if (! Auth::user()->hasRole('Admin')) {
            return;
        }

        $this->reset(['nombreColumna', 'colorColumna']);
        $this->showColumnModal = true;
    }

    public function crearColumna()
    {
        if (! Auth::user()->hasRole('Admin')) {
            return;
        }

        $this->validate([
            'nombreColumna' => 'required|min:2|max:255',
            'colorColumna' => 'required',
        ]);

        TableroColumna::create([
            'nombre' => $this->nombreColumna,
            'color' => $this->colorColumna,
        ]);

        $this->cerrarModales();

        $this->dispatch('swal:success', [
            'title' => 'Columna creada',
            'text' => 'La columna se ha creado exitosamente',
        ]);
    }

    public function eliminarColumna($columnaId)
    {
        if (! Auth::user()->hasRole('Admin')) {
            return;
        }

        $columna = TableroColumna::findOrFail($columnaId);

        if ($columna->es_sistema) {
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text' => 'No se pueden eliminar columnas del sistema',
            ]);

            return;
        }

        if ($columna->tareas()->count() > 0) {
            $this->dispatch('swal:error', [
                'title' => 'Error',
                'text' => 'No se puede eliminar una columna con tareas',
            ]);

            return;
        }

        $columna->delete();

        $this->dispatch('swal:success', [
            'title' => 'Columna eliminada',
            'text' => 'La columna se ha eliminado exitosamente',
        ]);
    }

    public function cerrarModales()
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showViewModal = false;
        $this->showColumnModal = false;

        // Limpiar IDs cuando se cierran los modales
        if (! $this->showViewModal && ! $this->showEditModal) {
            $this->tareaSeleccionadaId = null;
        }

        if (! $this->showCreateModal) {
            $this->columnaSeleccionadaId = null;
        }
    }

    public function render()
    {
        return view('livewire.tablero-kanban');
    }
}
