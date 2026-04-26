<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Iniciar sesión
     */
    public function login(Request $request): JsonResponse
    {
        try {
            \Log::info('📱 API Login attempt', [
                'email_or_codigo' => $request->email,
                'device_name' => $request->device_name,
            ]);

            $validator = Validator::make($request->all(), [
                'email' => 'required|string', // Cambiado a string para aceptar email o código
                'password' => 'required|string|min:6',
                'device_name' => 'required|string',
            ]);

            if ($validator->fails()) {
                \Log::warning('❌ API Login validation failed', [
                    'errors' => $validator->errors(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Buscar usuario por email o código (case-insensitive)
            $user = User::where(function ($query) use ($request) {
                $query->where('email', $request->email)
                    ->orWhere('codigo', strtoupper($request->email));
            })->first();

            \Log::info('🔍 Usuario encontrado', [
                'found' => $user ? 'yes' : 'no',
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'user_codigo' => $user?->codigo,
            ]);

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Las credenciales proporcionadas son incorrectas.',
                ], 401);
            }

            if ($user->status === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Su cuenta está desactivada. Contacte al administrador.',
                ], 403);
            }

            // Revocar tokens existentes del dispositivo
            $user->tokens()->where('name', $request->device_name)->delete();

            // Crear nuevo token
            $token = $user->createToken($request->device_name)->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'codigo' => $user->codigo,
                        'persona' => $user->persona,
                        'roles' => $user->getRoleNames(),
                        'permissions' => $user->getAllPermissions()->pluck('name'),
                        'sucursales' => $user->sucursales,
                        'profile_photo_url' => $user->profile_photo_url,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesión',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cerrar todas las sesiones
     */
    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $request->user()->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Todas las sesiones han sido cerradas exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesiones',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener información del usuario autenticado
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load(['persona', 'sucursales', 'roles.permissions']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'codigo' => $user->codigo,
                    'status' => $user->status,
                    'persona' => $user->persona,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'sucursales' => $user->sucursales,
                    'profile_photo_url' => $user->profile_photo_url,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del usuario',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = $request->user();

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta',
                ], 400);
            }

            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            // Revocar todos los tokens existentes por seguridad
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Contraseña cambiada exitosamente. Por favor, inicia sesión nuevamente.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar contraseña',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refrescar token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'device_name' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nombre del dispositivo requerido',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = $request->user();

            // Revocar token actual
            $request->user()->currentAccessToken()->delete();

            // Crear nuevo token
            $token = $user->createToken($request->device_name)->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Token refrescado exitosamente',
                'data' => [
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al refrescar token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    //LISTA DE USUARIOS AGRUPADOS POR ROL
    public function users(Request $request): JsonResponse
    {
        try {
            $users = User::with(['persona', 'roles'])->get();

            // Inicializar arrays para cada rol
            $asesores = [];
            $analistas = [];
            $jcc = [];
            $admins = [];
            $oficina = [];
            $contabilidad = [];
            $otros = [];

            foreach ($users as $user) {
                $userData = [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'codigo' => $user->codigo,
                ];

                $roles = $user->getRoleNames();

                // Clasificar según roles
                if ($roles->contains('Asesor')) {
                    $asesores[] = $userData;
                }
                if ($roles->contains('Analista')) {
                    $analistas[] = $userData;
                }
                if ($roles->contains('JCC')) {
                    $jcc[] = $userData;
                }
                if ($roles->contains('Admin')) {
                    $admins[] = $userData;
                }
                if ($roles->contains('Oficina')) {
                    $oficina[] = $userData;
                }
                if ($roles->contains('Contabilidad')) {
                    $contabilidad[] = $userData;
                }
                // Si no tiene ninguno de los roles anteriores
                if (
                    $roles->isEmpty() || (!$roles->contains('Asesor') && !$roles->contains('Analista') &&
                        !$roles->contains('JCC') && !$roles->contains('Admin') &&
                        !$roles->contains('Oficina') && !$roles->contains('Contabilidad'))
                ) {
                    $otros[] = $userData;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'asesores' => $asesores,
                    'analistas' => $analistas,
                    'jcc' => $jcc,
                    'admins' => $admins,
                    'oficina' => $oficina,
                    'contabilidad' => $contabilidad,
                    'otros' => $otros,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener lista de usuarios',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
