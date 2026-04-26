<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AccessCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'is_active',
        'expires_at',
        'usage_count',
        'max_usage',
        'allowed_roles',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'allowed_roles' => 'array',
        'usage_count' => 'integer',
        'max_usage' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValid()
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && Carbon::now()->isAfter($this->expires_at)) {
            return false;
        }

        if ($this->max_usage && $this->usage_count >= $this->max_usage) {
            return false;
        }

        return true;
    }

    public function incrementUsage()
    {
        $this->increment('usage_count');

        return $this;
    }

    public function canBeUsedByRole($role)
    {
        if (! $this->allowed_roles) {
            return true;
        }

        return in_array($role, $this->allowed_roles);
    }

    public static function generateCode($length = 6)
    {
        do {
            $code = strtoupper(Str::random($length));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('max_usage')
                    ->orWhereRaw('usage_count < max_usage');
            });
    }

    public function getStatusAttribute()
    {
        if (! $this->is_active) {
            return 'Inactivo';
        }

        if ($this->expires_at && Carbon::now()->isAfter($this->expires_at)) {
            return 'Expirado';
        }

        if ($this->max_usage && $this->usage_count >= $this->max_usage) {
            return 'Agotado';
        }

        return 'Activo';
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'Activo' => 'success',
            'Inactivo' => 'secondary',
            'Expirado' => 'warning',
            'Agotado' => 'danger',
            default => 'secondary'
        };
    }
}
