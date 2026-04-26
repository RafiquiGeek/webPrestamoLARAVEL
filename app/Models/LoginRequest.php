<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LoginRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'user_name',
        'access_code',
        'status',
        'user_agent',
        'ip_address',
        'expires_at',
        'approved_by',
        'approved_at',
        'used_at',
        'admin_notes',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'approved_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    public function isExpired()
    {
        return Carbon::now()->isAfter($this->expires_at);
    }

    public function isValid()
    {
        return $this->status === 'approved' && ! $this->isExpired();
    }

    public function markAsUsed()
    {
        $this->update([
            'status' => 'used',
            'used_at' => now(),
        ]);
    }

    public function approve($adminId, $notes = null)
    {
        // Usar consulta directa para evitar problemas con timestamps
        \DB::table('login_requests')
            ->where('id', $this->id)
            ->update([
                'status' => 'approved',
                'approved_by' => $adminId,
                'approved_at' => now(),
                'admin_notes' => $notes,
                'updated_at' => now(),
            ]);

        // Actualizar el modelo actual
        $this->status = 'approved';
        $this->approved_by = $adminId;
        $this->approved_at = now();
        $this->admin_notes = $notes;
    }

    public function deny($adminId, $notes = null)
    {
        $this->update([
            'status' => 'denied',
            'approved_by' => $adminId,
            'approved_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    public static function generateCode()
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (self::where('access_code', $code)
            ->where('expires_at', '>', Carbon::now()) // Solo verificar códigos no expirados
            ->whereIn('status', ['pending', 'approved', 'used']) // Incluir 'used' para evitar reutilización
            ->exists());

        return $code;
    }

    public static function createRequest($email, $userName, $userAgent, $ipAddress)
    {
        // Invalidar solo solicitudes PENDIENTES, no las aprobadas
        self::where('email', $email)
            ->where('status', 'pending') // Solo pendientes
            ->where('expires_at', '>', Carbon::now())
            ->update(['status' => 'expired']);

        $expirationTime = Carbon::now()->addMinutes(15);

        return self::create([
            'email' => $email,
            'user_name' => $userName,
            'access_code' => self::generateCode(),
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
            'expires_at' => $expirationTime,
            'status' => 'approved', // Auto-aprobar desde la creación para evitar problemas
        ]);
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'denied' => 'danger',
            'used' => 'info',
            'expired' => 'secondary',
            default => 'secondary'
        };
    }

    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'approved' => 'Aprobado',
            'denied' => 'Denegado',
            'used' => 'Usado',
            'expired' => 'Expirado',
            default => 'Desconocido'
        };
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'approved'])
            ->where('expires_at', '>', Carbon::now());
    }
}
