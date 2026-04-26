<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class PerfilController extends Controller
{
    /**
     * Obtener perfil del usuario
     */
    public function perfil(): JsonResponse
    {
        try {
            $user = auth()->user()->load([
                'persona',
                'sucursales',
                'roles.permissions',
            ]);

            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'codigo' => $user->codigo,
                'status' => $user->status,
                'profile_photo_url' => $user->profile_photo_url,
                'persona' => $user->persona,
                'sucursales' => $user->sucursales,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el perfil',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar perfil del usuario
     */
    public function actualizarPerfil(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255|unique:users,email,'.auth()->id(),
                'nombres' => 'sometimes|string|max:255',
                'apellidos' => 'sometimes|string|max:255',
                'dni' => 'sometimes|string|size:8|unique:personas,dni,'.(auth()->user()->persona_id ?? 'NULL'),
                'telefono' => 'sometimes|string|max:20',
                'direccion' => 'sometimes|string|max:500',
                'fecha_nacimiento' => 'sometimes|date|before:today',
                'genero' => 'sometimes|in:M,F',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = auth()->user();

            // Actualizar datos del usuario
            $userUpdates = $request->only(['name', 'email']);
            if (! empty($userUpdates)) {
                $user->update($userUpdates);
            }

            // Actualizar datos de persona si existen
            $personaUpdates = $request->only([
                'nombres', 'apellidos', 'dni', 'telefono',
                'direccion', 'fecha_nacimiento', 'genero',
            ]);

            if (! empty($personaUpdates) && $user->persona) {
                $user->persona->update($personaUpdates);
            } elseif (! empty($personaUpdates) && ! $user->persona) {
                // Crear persona si no existe
                $persona = Persona::create($personaUpdates);
                $user->update(['persona_id' => $persona->id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado exitosamente',
                'data' => $user->fresh(['persona']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Subir foto de perfil
     */
    public function subirFotoPerfil(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo de imagen inválido',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = auth()->user();

            // Eliminar foto anterior si existe
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            // Subir nueva foto
            $path = $request->file('photo')->store('profile-photos', 'public');

            $user->update([
                'profile_photo_path' => $path,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Foto de perfil actualizada exitosamente',
                'data' => [
                    'profile_photo_path' => $path,
                    'profile_photo_url' => $user->fresh()->profile_photo_url,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir la foto de perfil',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar foto de perfil
     */
    public function eliminarFotoPerfil(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (! $user->profile_photo_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay foto de perfil para eliminar',
                ], 400);
            }

            // Eliminar archivo
            Storage::disk('public')->delete($user->profile_photo_path);

            // Actualizar usuario
            $user->update([
                'profile_photo_path' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Foto de perfil eliminada exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la foto de perfil',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarContrasena(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = auth()->user();

            // Verificar contraseña actual
            if (! Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta',
                ], 400);
            }

            // Verificar que la nueva contraseña sea diferente
            if (Hash::check($request->new_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La nueva contraseña debe ser diferente a la actual',
                ], 400);
            }

            // Actualizar contraseña
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            // Opcional: revocar todos los tokens por seguridad
            if ($request->boolean('revoke_all_tokens', false)) {
                $user->tokens()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Contraseña cambiada exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar la contraseña',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener configuraciones de notificaciones
     */
    public function configuracionNotificaciones(): JsonResponse
    {
        try {
            $user = auth()->user();

            // Aquí puedes implementar un sistema de configuraciones
            // Por ahora devolvemos configuraciones por defecto
            $configuraciones = [
                'email_notifications' => true,
                'push_notifications' => true,
                'sms_notifications' => false,
                'notif_nuevos_prestamos' => true,
                'notif_pagos_recibidos' => true,
                'notif_gestiones_vencidas' => true,
                'notif_compromisos_hoy' => true,
                'notif_fondos_aprobados' => true,
            ];

            return response()->json([
                'success' => true,
                'data' => $configuraciones,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener configuraciones de notificaciones',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar configuraciones de notificaciones
     */
    public function actualizarConfiguracionNotificaciones(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email_notifications' => 'boolean',
                'push_notifications' => 'boolean',
                'sms_notifications' => 'boolean',
                'notif_nuevos_prestamos' => 'boolean',
                'notif_pagos_recibidos' => 'boolean',
                'notif_gestiones_vencidas' => 'boolean',
                'notif_compromisos_hoy' => 'boolean',
                'notif_fondos_aprobados' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = auth()->user();

            // Aquí puedes implementar el guardado real de las configuraciones
            // Por ejemplo, en una tabla user_settings o como JSON en el usuario

            return response()->json([
                'success' => true,
                'message' => 'Configuraciones de notificaciones actualizadas exitosamente',
                'data' => $request->all(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar configuraciones de notificaciones',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener resumen de actividad del usuario
     */
    public function resumenActividad(): JsonResponse
    {
        try {
            $user = auth()->user();
            $hoy = now()->format('Y-m-d');
            $inicioMes = now()->startOfMonth();

            // Estadísticas básicas del usuario
            $resumen = [
                'gestiones_hoy' => $user->gestiones()->whereDate('fecha_gestion', $hoy)->count(),
                'gestiones_mes' => $user->gestiones()->whereBetween('fecha_gestion', [$inicioMes, now()])->count(),
                'compromisos_pendientes' => $user->compromisos()->where('estado', 'pendiente')->count(),
                'compromisos_hoy' => $user->compromisos()->whereDate('fecha_compromiso', $hoy)->count(),
                'fondos_pendientes' => $user->fondosProvisionales()->whereIn('estado', ['pendiente', 'aprobado'])->count(),
                'prestamos_gestionados' => $user->prestamos()->count(),
                'pagos_registrados_hoy' => $user->operaciones()->where('tipo', 'pago')->whereDate('fecha', $hoy)->count(),
                'ultimo_login' => $user->last_login_at ?? $user->created_at,
                'total_tokens_activos' => $user->tokens()->count(),
            ];

            // Actividad reciente (últimas 5 acciones)
            $actividadReciente = collect([
                // Gestiones recientes
                ...$user->gestiones()->with('prestamo.cliente.persona')
                    ->latest('fecha_gestion')
                    ->limit(3)
                    ->get()
                    ->map(function ($gestion) {
                        return [
                            'tipo' => 'gestion',
                            'descripcion' => 'Gestión a '.($gestion->prestamo->cliente->persona->nombres ?? 'Cliente'),
                            'fecha' => $gestion->fecha_gestion,
                            'icono' => 'phone',
                        ];
                    }),

                // Pagos recientes
                ...$user->operaciones()->with('prestamo.cliente.persona')
                    ->where('tipo', 'pago')
                    ->latest('fecha')
                    ->limit(2)
                    ->get()
                    ->map(function ($pago) {
                        return [
                            'tipo' => 'pago',
                            'descripcion' => 'Pago registrado: S/ '.number_format($pago->monto, 2),
                            'fecha' => $pago->fecha,
                            'icono' => 'dollar-sign',
                        ];
                    }),
            ])->sortByDesc('fecha')->take(5)->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'resumen_estadisticas' => $resumen,
                    'actividad_reciente' => $actividadReciente,
                    'fecha_consulta' => now()->format('Y-m-d H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen de actividad',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener sesiones activas
     */
    public function sesionesActivas(): JsonResponse
    {
        try {
            $user = auth()->user();
            $tokens = $user->tokens()->get();

            $sesiones = $tokens->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'last_used_at' => $token->last_used_at,
                    'created_at' => $token->created_at,
                    'is_current' => $token->id === auth()->user()->currentAccessToken()->id,
                    'abilities' => $token->abilities,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_sesiones' => $sesiones->count(),
                    'sesiones' => $sesiones,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener sesiones activas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Revocar sesión específica
     */
    public function revocarSesion(Request $request, $tokenId): JsonResponse
    {
        try {
            $user = auth()->user();
            $token = $user->tokens()->find($tokenId);

            if (! $token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesión no encontrada',
                ], 404);
            }

            // No permitir revocar la sesión actual
            if ($token->id === auth()->user()->currentAccessToken()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes revocar tu sesión actual',
                ], 400);
            }

            $token->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sesión revocada exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al revocar la sesión',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
