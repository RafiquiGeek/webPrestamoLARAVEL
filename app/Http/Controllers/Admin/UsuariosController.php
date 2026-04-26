<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Analista;
use App\Models\Asesor;
use App\Models\JCC;
use App\Models\Persona;
use App\Models\Sucursal;
use App\Models\Telefono;
use App\Models\User;
use App\Models\UserBySucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UsuariosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $usuarios = User::with(['persona', 'roles'])->get();

        return view('admin.Usuarios.index', compact('usuarios'));
    }

    /**
     * Buscar persona/cliente por DNI para registro de usuario
     */
    public function buscarPorDni($dni)
    {
        $persona = \App\Models\Persona::where('documento', $dni)->with(['telefonos', 'user'])->first();
        if ($persona) {
            return response()->json([
                'success' => true,
                'persona' => [
                    'nombres' => $persona->nombres,
                    'apellido_paterno' => $persona->ape_pat,
                    'apellido_materno' => $persona->ape_mat,
                    'telefono' => $persona->telefonos->where('tipo_telefono', 'celular')->first()->numero ?? null,
                    'email' => $persona->email,
                    'ya_usuario' => $persona->user ? true : false,
                ]
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::all();
        $sucursales = Sucursal::all();

        return view('admin.Usuarios.create', compact('roles', 'sucursales'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Log de datos recibidos para debug
        Log::info('Iniciando creación de usuario', [
            'usuario_actual' => auth()->user()->id,
            'datos_recibidos' => $request->except(['password', 'password_confirmation']),
        ]);

        // Validar los datos del formulario con reglas mejoradas
        $request->validate([
            'email' => 'nullable|email',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|same:password',
            'nombres' => 'required|string|max:255',
            'nDocumento' => 'required|string|min:8|max:8',
            'aPaterno' => 'required|string|max:255',
            'aMaterno' => 'required|string|max:255',
            'telefono' => 'required|string|max:15|min:9',
            'codigo' => 'nullable|string|max:20',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
            'sucursal_id' => 'required|array|min:1',
            'sucursal_id.*' => 'exists:sucursales,id',
        ], [
            // Mensajes de error personalizados
            'nDocumento.min' => 'El DNI debe tener exactamente 8 dígitos.',
            'nDocumento.max' => 'El DNI debe tener exactamente 8 dígitos.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'telefono.min' => 'El teléfono debe tener al menos 9 dígitos.',
            'telefono.max' => 'El teléfono no puede tener más de 15 dígitos.',
            'roles.min' => 'Debe asignar al menos un rol al usuario.',
            'sucursal_id.min' => 'Debe asignar al menos una sucursal al usuario.',
        ]);

        try {
            Log::info('Iniciando transacción para crear usuario');
            DB::beginTransaction();

            // Validar si ya existe un usuario con este DNI
            $existingUser = User::where('name', $request->nDocumento)->first();
            if ($existingUser) {
                Log::warning('Intento de crear usuario con DNI que ya tiene usuario', [
                    'dni' => $request->nDocumento,
                    'existing_user_id' => $existingUser->id,
                ]);
                DB::rollBack();

                return back()
                    ->withInput()
                    ->withErrors(['nDocumento' => 'Ya existe un usuario registrado con este DNI.']);
            }

            // Buscar si existe una persona con este DNI
            $existingPersona = Persona::where('documento', $request->nDocumento)->first();
            
            // Validar email solo si no está vacío
            if ($request->email) {
                $existingEmailUser = User::where('email', $request->email)->first();
                if ($existingEmailUser) {
                    DB::rollBack();
                    return back()
                        ->withInput()
                        ->withErrors(['email' => 'Ya existe un usuario registrado con este email.']);
                }
            }

            // Validar código solo si no está vacío
            if ($request->codigo) {
                $existingCode = User::where('codigo', $request->codigo)->first();
                if ($existingCode) {
                    DB::rollBack();

                    return back()
                        ->withInput()
                        ->withErrors(['codigo' => 'Ya existe un usuario con este código.']);
                }
            }

            // Verificar si el DNI ya existe
            if (! $existingPersona) {
                $persona = new Persona;
                $persona->documento = $request->nDocumento;
            } else {
                $persona = $existingPersona;
            }

            $persona->ape_pat = $request->aPaterno;
            $persona->ape_mat = $request->aMaterno;
            $persona->nombres = $request->nombres;
            $persona->email = $request->email;
            $persona->save();

            // Crear o actualizar tel��fono
            $telefono = Telefono::where('persona_id', $persona->id)
                ->where('numero', $request->telefono)->first();

            if (! $telefono && $request->telefono) {
                $telefono = new Telefono;
                $telefono->persona_id = $persona->id;
                $telefono->tipo_telefono = 'celular';
                $telefono->numero = $request->telefono;
                $telefono->save(); // Guardar tel��fono
            }

            // Crear el usuario
            $user = new User;
            $user->persona_id = $persona->id;
            $user->email = $request->email;
            $user->name = $request->nDocumento;  // Usar DNI como nombre de usuario
            $user->password = Hash::make($request->password);
            $user->codigo = $request->codigo;  // Guardar el campo c��digo
            $user->status = 1;  // Establecer como activo
            $user->save();

            Log::info('Usuario creado exitosamente', ['user_id' => $user->id]);

            // Asignar sucursales al usuario
            foreach ($request->sucursal_id as $sucursalId) {
                $roleId = $request->roles[0]; // Se asigna el primer rol por defecto
                UserBySucursal::create([
                    'user_id' => $user->id,
                    'sucursal_id' => $sucursalId,
                    'role_id' => $roleId,
                    'status' => 1,
                ]);
            }

            // Asignar roles al usuario
            $roleNames = Role::whereIn('id', $request->roles)->pluck('name')->toArray();
            $user->syncRoles($roleNames);

            DB::commit();

            return redirect()->route('admin.usuarios.index')
                ->with('success', 'Usuario creado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear usuario: '.$e->getMessage(), [
                'usuario_actual' => auth()->user()->id,
                'request_data' => $request->except(['password', 'password_confirmation']),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al crear usuario. Por favor, revise los datos e intente nuevamente. Detalle: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Cargar el usuario con todas las relaciones necesarias
        $usuario = User::with([
            'persona' => function ($query) {
                $query->with(['telefonos' => function ($q) {
                    $q->where('tipo_telefono', 'celular')->take(1);
                }]);
            },
            'sucursales',
            'roles',
        ])->findOrFail($id);

        // Obtener todos los roles y sucursales para los selects
        $roles = Role::all();
        $sucursales = Sucursal::all();

        // Pasar los datos a la vista
        return view('admin.Usuarios.edit', [
            'usuario' => $usuario,
            'roles' => $roles,
            'sucursales' => $sucursales,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'nombres' => 'required|string|max:255',
            'aPaterno' => 'required|string|max:255',
            'aMaterno' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id.'|unique:personas,email,'.User::find($id)->persona->id ?? 'NULL',
            'telefono' => 'required|string|max:15|min:9',
            'codigo' => 'nullable|string|max:20|unique:users,codigo,'.$id,
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,id',
            'password' => 'nullable|min:8|confirmed',
            'sucursal_id' => 'required|array|min:1',
            'sucursal_id.*' => 'exists:sucursales,id',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'ip',
        ], [
            // Mensajes de error personalizados
            'email.unique' => 'Ya existe otro usuario registrado con este email.',
            'codigo.unique' => 'Ya existe otro usuario con este código.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'telefono.min' => 'El teléfono debe tener al menos 9 dígitos.',
            'telefono.max' => 'El teléfono no puede tener más de 15 dígitos.',
            'roles.min' => 'Debe asignar al menos un rol al usuario.',
            'sucursal_id.min' => 'Debe asignar al menos una sucursal al usuario.',
        ]);

        try {
            DB::beginTransaction();

            $usuario = User::findOrFail($id);
            $persona = $usuario->persona;

            // Actualizar datos de la persona
            $persona->nombres = $request->nombres;
            $persona->ape_pat = $request->aPaterno;
            $persona->ape_mat = $request->aMaterno;
            $persona->email = $request->email;
            $persona->save();

            // Actualizar datos del usuario
            $usuario->email = $request->email;
            $usuario->name = $usuario->persona->documento; // Mantener el DNI como nombre de usuario
            $usuario->codigo = $request->codigo;

            // Actualizar tel��fono
            $telefono = Telefono::where('persona_id', $persona->id)
                ->where('tipo_telefono', 'celular')
                ->first();

            if (! $telefono) {
                $telefono = new Telefono;
                $telefono->persona_id = $persona->id;
                $telefono->tipo_telefono = 'celular';
            }

            $telefono->numero = $request->telefono;
            $telefono->save();

            // allowed_ips - Filtrar IPs vacías y eliminar espacios en blanco
            $allowedIps = $request->allowed_ips ?? [];
            $allowedIps = array_map('trim', $allowedIps); // Eliminar espacios en blanco
            $allowedIps = array_filter($allowedIps); // Eliminar valores vacíos
            $allowedIps = array_values($allowedIps); // Reindexar el array
            $usuario->allowed_ips = $allowedIps;

            // Actualizar sucursales
            $usuario->sucursales()->sync($request->sucursal_id);

            // Actualizar roles
            $roleNames = Role::whereIn('id', $request->roles)->pluck('name')->toArray();
            $usuario->syncRoles($roleNames);

            // Actualizar contrase�0�9a si se proporciona
            if ($request->filled('password')) {
                $usuario->password = Hash::make($request->password);
            }

            $usuario->save();
            DB::commit();

            return redirect()->route('admin.usuarios.index')->with('success', 'Usuario actualizado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error actualizando usuario ID {$id}: ".$e->getMessage());

            return back()->withInput()->with('error', 'Error al actualizar: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $usuario = User::findOrFail($id);
        $usuario->delete();

        // Eliminar datos de las tablas correspondientes
        Analista::where('dni', $usuario->documento)->delete();
        Asesor::where('dni', $usuario->documento)->delete();
        JCC::where('dni', $usuario->documento)->delete();

        return redirect()->route('admin.usuarios.index')->with('status', 'Usuario eliminado correctamente');
    }

    /**
     * Cambiar el estado del usuario (activar/desactivar)
     */
    public function changeStatus(string $id, $status)
    {
        try {
            $usuario = User::findOrFail($id);
            $usuario->status = $status;
            $usuario->save();

            $message = $status == 1 ? 'activado' : 'desactivado';

            return redirect()->route('admin.usuarios.index')->with('success', "Usuario {$message} correctamente");
        } catch (\Exception $e) {
            Log::error("Error cambiando estado del usuario ID {$id}: ".$e->getMessage());

            return back()->with('error', 'Error al cambiar estado del usuario');
        }
    }

    /**
     * Obtener estadísticas de usuarios para el dashboard
     */
    public function stats()
    {
        try {
            $stats = [
                'active' => User::where('status', 1)->count(),
                'inactive' => User::where('status', 0)->count(),
                'total' => User::count(),
                'new' => User::whereMonth('created_at', date('m'))
                    ->whereYear('created_at', date('Y'))
                    ->count(),
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'active' => 0,
                'inactive' => 0,
                'total' => 0,
                'new' => 0,
            ], 500);
        }
    }
}
