<?php

namespace App\Livewire\Usuarios;

use App\Models\Role;
use App\Models\User; // Asegúrate de importar el modelo Role
use Livewire\Attributes\On;
use Livewire\Component;

class ShowUsuarios extends Component
{
    public $search;

    public $sort = 'id';

    public $direction = 'desc';

    public $roles; // Nueva propiedad para almacenar los roles

    public $roleIcons = [
        'Admin' => 'user-shield',
        'Analista' => 'chart-line',
        'JCC' => 'user-tie',
        'Asesor' => 'handshake',
        // Agrega más roles e íconos según necesites
    ];

    public function mount()
    {
        // Cargar todos los roles al inicializar el componente
        $this->roles = Role::all();
    }

    public function render()
    {
        $query = User::with(['roles', 'persona']) // Cargar los roles y persona de los usuarios
            ->leftJoin('personas', 'users.persona_id', '=', 'personas.id')
            ->leftJoin('users_by_sucursal', 'users.id', '=', 'users_by_sucursal.user_id')
            ->leftJoin('sucursales', 'users_by_sucursal.sucursal_id', '=', 'sucursales.id')
            ->select(
                'users.id',
                'users.persona_id',
                'users.name',
                'users.created_at',
                'users.status',
                'users.codigo',
                'users.email as user_email',
                'personas.documento',
                'personas.nombres',
                'personas.ape_pat',
                'personas.ape_mat',
                'personas.email as persona_email',
                \DB::raw('GROUP_CONCAT(sucursales.sucursal SEPARATOR ", ") as sucursal_nombre')
            )
            ->groupBy(
                'users.id',
                'users.persona_id',
                'users.name',
                'users.created_at',
                'users.status',
                'users.codigo',
                'users.email',
                'personas.documento',
                'personas.nombres',
                'personas.ape_pat',
                'personas.ape_mat',
                'personas.email'
            );

        if ($this->search) {
            $query->having(\DB::raw('1'), '=', 1) // Agregar HAVING para permitir filtros después del GROUP BY
                ->where(function ($q) {
                    $q->where('personas.documento', 'like', '%'.$this->search.'%')
                        ->orWhere('users.email', 'like', '%'.$this->search.'%')
                        ->orWhere('personas.email', 'like', '%'.$this->search.'%')
                        ->orWhere('users.codigo', 'like', '%'.$this->search.'%')
                        ->orWhereRaw("CONCAT(personas.nombres, ' ', personas.ape_pat, ' ', personas.ape_mat) LIKE ?", ['%'.$this->search.'%']);
                });
        }

        // Ajustar el ordenamiento según el campo
        $sortField = $this->sort;
        if ($this->sort == 'name') {
            $sortField = 'personas.nombres';
        } elseif ($this->sort == 'id') {
            $sortField = 'users.id';
        }

        $usuarios = $query->orderBy($sortField, $this->direction)->get();

        return view('livewire.usuarios.show-usuarios', compact('usuarios'));
    }

    public function order($sort)
    {
        if ($this->sort == $sort) {
            $this->direction = $this->direction == 'desc' ? 'asc' : 'desc';
        } else {
            $this->sort = $sort;
            $this->direction = 'desc';
        }
    }

    #[On('deleteUser')]
    public function deleteUser($userId)
    {
        $user = User::find($userId);
        if ($user) {
            $user->status = 0;
            $user->save();
            $this->dispatch('userDeleted', ['icon' => 'success', 'title' => 'Tarea Realizada', 'text' => 'Usuario desactivado exitosamente.']);
        } else {
            $this->dispatch('userDeleted', ['icon' => 'error', 'title' => 'Tarea Fallida', 'text' => 'No se pudo desactivar al usuario.']);
        }
    }

    #[On('activateUser')]
    public function activateUser($userId)
    {
        $user = User::find($userId);
        if ($user) {
            $user->status = 1;
            $user->save();
            $this->dispatch('userActivated', ['icon' => 'success', 'title' => 'Usuario Activado', 'text' => 'El usuario ha sido activado exitosamente.']);
        } else {
            $this->dispatch('userActivated', ['icon' => 'error', 'title' => 'Error', 'text' => 'No se pudo activar al usuario.']);
        }
    }

    #[On('updateSearch')]
    public function updateSearch($search)
    {
        $this->search = $search;
    }

    #[On('refreshTableEvent')]
    public function refreshTableEvent()
    {
        $this->render();
    }

    #[On('permanentDeleteUser')]
    public function permanentDeleteUser($userId)
    {
        try {
            $user = User::find($userId);

            if (! $user) {
                $this->dispatch('userPermanentlyDeleted', [
                    'icon' => 'error',
                    'title' => 'Error',
                    'text' => 'Usuario no encontrado.',
                ]);

                return;
            }

            // Obtener nombre para el mensaje
            $userName = $user->persona
                ? $user->persona->nombres.' '.$user->persona->ape_pat.' '.$user->persona->ape_mat
                : $user->name;

            // Verificar si el usuario tiene operaciones críticas asociadas
            $hasOperations = false;

            // Puedes agregar aquí verificaciones de operaciones críticas
            // Por ejemplo, si tiene préstamos activos, pagos pendientes, etc.
            // $hasOperations = $user->prestamos()->where('estado', 'activo')->count() > 0;

            if ($hasOperations) {
                $this->dispatch('userPermanentlyDeleted', [
                    'icon' => 'error',
                    'title' => 'No se puede eliminar',
                    'text' => 'El usuario tiene operaciones activas asociadas. Desactívalo en su lugar.',
                ]);

                return;
            }

            // Eliminar datos relacionados en el orden correcto
            \DB::transaction(function () use ($user) {
                // Eliminar asignaciones de sucursales
                \App\Models\UserBySucursal::where('user_id', $user->id)->delete();

                // Eliminar datos de carteras según el rol que tenga
                $userRoles = $user->getRoleNames();

                foreach ($userRoles as $role) {
                    switch (strtolower($role)) {
                        case 'analista':
                            \DB::table('carteras_analista')->where('analista_id', $user->id)->delete();
                            break;
                        case 'asesor':
                            \DB::table('carteras_asesor')->where('asesor_id', $user->id)->delete();
                            break;
                        case 'jcc':
                            \DB::table('carteras_jcc')->where('jcc_id', $user->id)->delete();
                            break;
                    }
                }

                // Eliminar registros de asistencia si existen
                if (\Schema::hasTable('registros_asistencia')) {
                    \DB::table('registros_asistencia')->where('user_id', $user->id)->delete();
                }

                // Eliminar asignaciones de área si existen
                if (\Schema::hasTable('asignaciones_area_empleado')) {
                    \DB::table('asignaciones_area_empleado')->where('user_id', $user->id)->delete();
                }

                // Eliminar actividades del usuario
                if (\Schema::hasTable('user_activities')) {
                    \DB::table('user_activities')->where('user_id', $user->id)->delete();
                }

                // Eliminar sesiones del usuario
                if (\Schema::hasTable('user_sessions')) {
                    \DB::table('user_sessions')->where('user_id', $user->id)->delete();
                }

                // Eliminar códigos de acceso (usa created_by)
                if (\Schema::hasTable('access_codes')) {
                    \DB::table('access_codes')->where('created_by', $user->id)->delete();
                }

                // Eliminar solicitudes de login (usa email para identificar al usuario)
                if (\Schema::hasTable('login_requests')) {
                    // Buscar por email del usuario o de su persona
                    $userEmail = $user->email ?? $user->persona?->email;
                    if ($userEmail) {
                        \DB::table('login_requests')->where('email', $userEmail)->delete();
                    }
                }

                // Remover todos los roles del usuario
                $user->syncRoles([]);

                // Remover todos los permisos del usuario
                $user->syncPermissions([]);

                // Eliminar el usuario (soft delete si está habilitado, sino hard delete)
                if (method_exists($user, 'forceDelete')) {
                    $user->forceDelete(); // Eliminación permanente
                } else {
                    $user->delete(); // Soft delete
                }
            });

            $this->dispatch('userPermanentlyDeleted', [
                'icon' => 'success',
                'title' => 'Usuario Eliminado',
                'text' => "El usuario '{$userName}' ha sido eliminado permanentemente del sistema.",
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al eliminar usuario permanentemente: '.$e->getMessage());

            $this->dispatch('userPermanentlyDeleted', [
                'icon' => 'error',
                'title' => 'Error del Sistema',
                'text' => 'No se pudo eliminar el usuario. Error: '.$e->getMessage(),
            ]);
        }
    }
}
