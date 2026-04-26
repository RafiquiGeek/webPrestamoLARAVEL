<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleTimeTracking extends Model
{
    use HasFactory;

    protected $table = 'module_time_tracking';

    protected $fillable = [
        'user_id',
        'user_session_id',
        'module_name',
        'module_section',
        'start_time',
        'end_time',
        'duration',
        'url',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userSession(): BelongsTo
    {
        return $this->belongsTo(UserSession::class);
    }

    public function getDurationFormattedAttribute(): string
    {
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function isActive(): bool
    {
        return is_null($this->end_time);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('end_time');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('end_time');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByModule($query, $moduleName)
    {
        return $query->where('module_name', $moduleName);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('start_time', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('start_time', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('start_time', now()->month)
            ->whereYear('start_time', now()->year);
    }

    public static function getTotalTimeByModule($userId, $startDate = null, $endDate = null)
    {
        $query = self::where('user_id', $userId)->completed();

        if ($startDate) {
            $query->whereDate('start_time', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('start_time', '<=', $endDate);
        }

        return $query->selectRaw('module_name, SUM(duration) as total_duration')
            ->groupBy('module_name')
            ->orderBy('total_duration', 'desc')
            ->get();
    }

    public static function getTotalTimeByDay($userId, $startDate = null, $endDate = null)
    {
        $query = self::where('user_id', $userId)->completed();

        if ($startDate) {
            $query->whereDate('start_time', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('start_time', '<=', $endDate);
        }

        return $query->selectRaw('DATE(start_time) as date, SUM(duration) as total_duration')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
    }
}
