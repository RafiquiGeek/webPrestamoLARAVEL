<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'login_time',
        'logout_time',
        'total_duration',
        'ip_address',
        'user_agent',
        'forced_logout',
    ];

    protected $casts = [
        'login_time' => 'datetime',
        'logout_time' => 'datetime',
        'forced_logout' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moduleTimeTracking(): HasMany
    {
        return $this->hasMany(ModuleTimeTracking::class);
    }

    public function getDurationFormattedAttribute(): string
    {
        $hours = floor($this->total_duration / 3600);
        $minutes = floor(($this->total_duration % 3600) / 60);
        $seconds = $this->total_duration % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function isActive(): bool
    {
        return is_null($this->logout_time);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('logout_time');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('logout_time');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('login_time', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('login_time', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('login_time', now()->month)
            ->whereYear('login_time', now()->year);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public static function calculateTotalDuration($userId, $startDate = null, $endDate = null)
    {
        $query = self::where('user_id', $userId)->completed();

        if ($startDate) {
            $query->whereDate('login_time', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('login_time', '<=', $endDate);
        }

        return $query->sum('total_duration');
    }

    /**
     * Obtiene la última actividad registrada para esta sesión.
     */
    public function getLastActivityAttribute()
    {
        // Usa la colección ya cargada si existe para evitar N+1 queries
        if ($this->relationLoaded('moduleTimeTracking')) {
            return $this->moduleTimeTracking->sortByDesc('start_time')->first();
        }

        return $this->moduleTimeTracking()
            ->latest('start_time')
            ->first();
    }

    /**
     * Determina el estado de actividad del usuario.
     * Online: Actividad en los últimos 10 minutos.
     * Idle: Sin actividad por más de 10 minutos.
     */
    public function getStatusAttribute()
    {
        if ($this->logout_time) {
            return 'offline';
        }

        $lastActivity = $this->last_activity;

        if (!$lastActivity) {
            // Si no hay tracking, basarse en el login time
            $minutesSinceLogin = $this->login_time->diffInMinutes(now());
            return $minutesSinceLogin < 10 ? 'online' : 'idle';
        }

        $lastTime = $lastActivity->end_time ?? $lastActivity->start_time;
        // Si la última actividad registrada fue hace menos de 10 minutos
        return $lastTime->diffInMinutes(now()) < 10 ? 'online' : 'idle';
    }
}
