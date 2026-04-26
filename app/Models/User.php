<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasProfilePhoto, HasRoles, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'persona_id',
        'name',
        'email',
        'password',
        'codigo',
        'status',
        'allowed_ips',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'allowed_ips' => 'array',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    /**
     * Relación con sucursales a través de la tabla pivot users_by_sucursal
     */
    public function sucursales(): BelongsToMany
    {
        return $this->belongsToMany(Sucursal::class, 'users_by_sucursal')
            ->using(UserBySucursal::class)
            ->withPivot(['role_id', 'status'])
            ->withTimestamps();
    }

    /**
     * Relación directa con la tabla pivot (para operaciones específicas)
     */
    public function usersBySucursal(): HasMany
    {
        return $this->hasMany(UserBySucursal::class);
    }

    public function gestiones(): HasMany
    {
        return $this->hasMany(Gestion::class, 'asesor_id');
    }

    public function zonas(): BelongsToMany
    {
        return $this->belongsToMany(Zona::class, 'user_zona')
            ->withTimestamps();
    }

    public function carteraAsesor(): HasMany
    {
        return $this->hasMany(CarteraAsesor::class, 'asesor_id');
    }

    public function carteraAnalista(): HasMany
    {
        return $this->hasMany(CarteraAnalista::class, 'analista_id');
    }

    public function carteraJcc(): HasMany
    {
        return $this->hasMany(CarteraJcc::class, 'jcc_id');
    }

    public function userSessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function moduleTimeTracking(): HasMany
    {
        return $this->hasMany(ModuleTimeTracking::class);
    }

    public function userActivities(): HasMany
    {
        return $this->hasMany(UserActivity::class);
    }

    /**
     * Accesor para el nombre completo
     */
    public function getFullNameAttribute()
    {
        return optional($this->persona)->nombres.' '.
               optional($this->persona)->ape_pat.' '.
               optional($this->persona)->ape_mat;
    }

    public function metas(): HasMany
    {
        return $this->hasMany(Meta::class, 'asesor_id');
    }

    public function metaCumplimientos(): HasMany
    {
        return $this->hasMany(MetaCumplimiento::class, 'asesor_id');
    }
}
