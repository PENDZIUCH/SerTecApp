<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements CanResetPasswordContract, FilamentUser
{
    // HasPushSubscriptions (laravel-notification-channels/webpush) agrega
    // pushSubscriptions()/updatePushSubscription() - un mismo User sirve
    // tanto a tecnicos (PWA) como a supervisores/admins (Filament), cada
    // uno con sus propias suscripciones de navegador. Ver
    // App\Notifications\PushNotification y
    // App\Services\PushNotificationDispatcher para el toggle on/off por
    // evento (lookup_values, category='push_notification_events').
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SoftDeletes, CanResetPassword, HasPushSubscriptions;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar_url',
        'job_title',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin', 'administrador', 'supervisor', 'técnico']);
    }

    protected static function booted(): void
    {
        // Al desactivar un usuario (desde Filament o la API) se revocan sus tokens
        // Sanctum ya emitidos - antes seguian valiendo hasta 365 dias despues.
        static::updated(function (self $user) {
            if ($user->wasChanged('is_active') && !$user->is_active) {
                $user->tokens()->delete();
            }
        });
    }

    public function workOrdersAssigned()
    {
        return $this->hasMany(WorkOrder::class, 'assigned_tech_id');
    }

    public function workOrdersCreated()
    {
        return $this->hasMany(WorkOrder::class, 'created_by');
    }

    public function visitsAssigned()
    {
        return $this->hasMany(Visit::class, 'assigned_tech_id');
    }

    public function filesUploaded()
    {
        return $this->morphMany(CustomerFile::class, 'uploaded_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTechnicians($query)
    {
        return $query->role('technician');
    }

    public function scopeAdmins($query)
    {
        return $query->role('admin');
    }
}
