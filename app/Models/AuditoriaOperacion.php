<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaOperacion extends Model
{
    use HasFactory;

    protected $table = 'auditoria_operaciones';

    protected $fillable = [
        'operacion_id',
        'prestamo_id',
        'tipo_operacion',
        'accion',
        'usuario_id',
        'usuario_nombre',
        'valores_anteriores',
        'valores_nuevos',
        'operaciones_hijas_afectadas',
        'justificacion',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'valores_anteriores' => 'array',
        'valores_nuevos' => 'array',
        'operaciones_hijas_afectadas' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function operacion(): BelongsTo
    {
        return $this->belongsTo(Operacion::class);
    }

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // Métodos de utilidad
    public function getDiferenciasAttribute(): array
    {
        if (! $this->valores_anteriores) {
            return [];
        }

        $diferencias = [];
        foreach ($this->valores_nuevos as $campo => $valorNuevo) {
            $valorAnterior = $this->valores_anteriores[$campo] ?? null;
            if ($valorAnterior != $valorNuevo) {
                $diferencias[$campo] = [
                    'anterior' => $valorAnterior,
                    'nuevo' => $valorNuevo,
                ];
            }
        }

        return $diferencias;
    }

    public function getResumenCambiosAttribute(): string
    {
        $diferencias = $this->getDiferenciasAttribute();
        $cambios = [];

        foreach ($diferencias as $campo => $valores) {
            if ($campo === 'abono') {
                $cambios[] = "Monto: S/{$valores['anterior']} → S/{$valores['nuevo']}";
            } elseif ($campo === 'fecha') {
                $cambios[] = "Fecha: {$valores['anterior']} → {$valores['nuevo']}";
            } else {
                $cambios[] = "{$campo}: {$valores['anterior']} → {$valores['nuevo']}";
            }
        }

        return implode(', ', $cambios);
    }
}
