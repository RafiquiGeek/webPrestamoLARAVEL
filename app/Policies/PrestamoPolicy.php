<?php

namespace App\Policies;

use App\Models\Prestamo;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrestamoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine si el usuario puede ver cualquier préstamo
     * Admin, Oficina, GS pueden ver todos
     * Asesor, Analista, JCC solo los de su cartera
     */
    public function viewAny(User $user): bool
    {
        // Admin, Oficina, GS pueden ver todos
        if ($this->esRolSinRestriccion($user)) {
            return true;
        }

        // Asesor, Analista, JCC pueden ver solo los de su cartera
        return $this->esRolRestringido($user);
    }

    /**
     * Determine si el usuario puede ver un préstamo específico
     */
    public function view(User $user, Prestamo $prestamo): bool
    {
        // Admin, Oficina, GS pueden ver todos
        if ($this->esRolSinRestriccion($user)) {
            return true;
        }

        // Verificar si el préstamo está en la cartera del usuario
        return $this->estaEnCartera($user, $prestamo);
    }

    /**
     * Determine si el usuario puede crear préstamos
     */
    public function create(User $user): bool
    {
        // Todos los roles autenticados pueden crear préstamos
        // La restricción de clientes se aplica en validaciones específicas
        return true;
    }

    /**
     * Determine si el usuario puede crear un préstamo para un cliente específico
     * Esta es la validación crítica para el sistema de carteras
     */
    public function createForCliente(User $user, $cliente): bool
    {
        // Admin, Oficina, GS pueden crear para cualquier cliente
        if ($this->esRolSinRestriccion($user)) {
            return true;
        }

        // Para roles restringidos (Asesor, Analista, JCC)
        if (!$this->esRolRestringido($user)) {
            return false;
        }

        // Verificar si el cliente tiene préstamos previos
        $clienteTienePrestamos = \App\Models\Prestamo::where('cliente_id', $cliente->id)->exists();

        // Si NO tiene préstamos previos, permitir (cliente nuevo)
        if (!$clienteTienePrestamos) {
            return true;
        }

        // Si tiene préstamos previos, verificar que esté en la cartera del usuario (renovación)
        return $this->clienteEstaEnCartera($user, $cliente);
    }

    /**
     * Determine si el usuario puede actualizar un préstamo
     */
    public function update(User $user, Prestamo $prestamo): bool
    {
        // Admin, Oficina, GS pueden actualizar todos
        if ($this->esRolSinRestriccion($user)) {
            return true;
        }

        // Verificar si el préstamo está en la cartera del usuario
        return $this->estaEnCartera($user, $prestamo);
    }

    /**
     * Determine si el usuario puede eliminar un préstamo
     */
    public function delete(User $user, Prestamo $prestamo): bool
    {
        // Solo Admin puede eliminar préstamos
        return $user->hasRole('Admin');
    }

    /**
     * Determine si el usuario puede desembolsar un préstamo
     */
    public function desembolsar(User $user, Prestamo $prestamo): bool
    {
        // Admin, Oficina, GS pueden desembolsar todos
        if ($this->esRolSinRestriccion($user)) {
            return true;
        }

        // Verificar si el préstamo está en la cartera del usuario
        return $this->estaEnCartera($user, $prestamo);
    }

    /**
     * Determine si el usuario puede aprobar un préstamo
     */
    public function aprobar(User $user, Prestamo $prestamo): bool
    {
        // Admin, Oficina, GS pueden aprobar todos
        if ($this->esRolSinRestriccion($user)) {
            return true;
        }

        // Verificar si el préstamo está en la cartera del usuario
        return $this->estaEnCartera($user, $prestamo);
    }

    /**
     * Determine si el usuario puede liquidar un préstamo
     */
    public function liquidar(User $user, Prestamo $prestamo): bool
    {
        // Admin, Oficina, GS pueden liquidar todos
        if ($this->esRolSinRestriccion($user)) {
            return true;
        }

        // Verificar si el préstamo está en la cartera del usuario
        return $this->estaEnCartera($user, $prestamo);
    }

    /**
     * Determine si el usuario puede registrar pagos en un préstamo
     */
    public function registrarPago(User $user, Prestamo $prestamo): bool
    {
        // Admin, Oficina, GS pueden registrar pagos en todos
        if ($this->esRolSinRestriccion($user)) {
            return true;
        }

        // Verificar si el préstamo está en la cartera del usuario
        return $this->estaEnCartera($user, $prestamo);
    }

    // ============================================
    // MÉTODOS HELPER PRIVADOS
    // ============================================

    /**
     * Verifica si el usuario tiene un rol sin restricciones
     */
    private function esRolSinRestriccion(User $user): bool
    {
        $rolesSinRestriccion = ['Admin', 'Oficina', 'GS'];

        foreach ($rolesSinRestriccion as $rol) {
            if ($user->hasRole($rol)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si el usuario tiene un rol con restricciones de cartera
     */
    private function esRolRestringido(User $user): bool
    {
        $rolesRestringidos = ['Asesor', 'Analista', 'JCC'];

        foreach ($rolesRestringidos as $rol) {
            if ($user->hasRole($rol)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si un préstamo está en la cartera del usuario
     */
    private function estaEnCartera(User $user, Prestamo $prestamo): bool
    {
        $userId = $user->id;

        if ($user->hasRole('Asesor')) {
            return $prestamo->carterasAsesor()
                ->where('asesor_id', $userId)
                ->where('estado', 1)
                ->exists();
        }

        if ($user->hasRole('Analista')) {
            return $prestamo->carterasAnalista()
                ->where('analista_id', $userId)
                ->where('estado', 1)
                ->exists();
        }

        if ($user->hasRole('JCC')) {
            return $prestamo->carterasJcc()
                ->where('jcc_id', $userId)
                ->where('estado', 1)
                ->exists();
        }

        return false;
    }

    /**
     * Verifica si un cliente está en la cartera del usuario
     * (tiene al menos un préstamo en la cartera del usuario)
     */
    private function clienteEstaEnCartera(User $user, $cliente): bool
    {
        $userId = $user->id;

        if ($user->hasRole('Asesor')) {
            return \App\Models\Prestamo::where('cliente_id', $cliente->id)
                ->whereHas('carterasAsesor', function ($q) use ($userId) {
                    $q->where('asesor_id', $userId);
                })->exists();
        }

        if ($user->hasRole('Analista')) {
            return \App\Models\Prestamo::where('cliente_id', $cliente->id)
                ->whereHas('carterasAnalista', function ($q) use ($userId) {
                    $q->where('analista_id', $userId);
                })->exists();
        }

        if ($user->hasRole('JCC')) {
            return \App\Models\Prestamo::where('cliente_id', $cliente->id)
                ->whereHas('carterasJcc', function ($q) use ($userId) {
                    $q->where('jcc_id', $userId);
                })->exists();
        }

        return false;
    }
}
