<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'short_name',
        'email',
        'password',
        'phone',
        'is_admin',
        'can_view_catalog',
        'can_add',
        'can_remove',
        'can_orders',
        'can_settings',
        'can_settings_categories',
        'can_settings_suppliers',
        'can_settings_company',
        'can_settings_users',
        'can_settings_export',
        'can_settings_other',
        'can_delete_orders',
        'show_action_column',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'can_view_catalog' => 'boolean',
            'can_add' => 'boolean',
            'can_remove' => 'boolean',
            'can_orders' => 'boolean',
            'can_settings' => 'boolean',
            'can_settings_categories' => 'boolean',
            'can_settings_suppliers' => 'boolean',
            'can_settings_company' => 'boolean',
            'can_settings_users' => 'boolean',
            'can_settings_export' => 'boolean',
            'can_settings_other' => 'boolean',
        ];
    }

    /**
     * Boot method - automatyczne ustawienia dla adminów
     */
    protected static function boot()
    {
        parent::boot();

        // Przed zapisaniem nowego użytkownika
        static::creating(function ($user) {
            // admin@admin.com zawsze ma określone dane
            if (strtolower($user->email) === 'admin@admin.com') {
                $user->first_name = 'Admin';
                $user->last_name = 'Admin';
                $user->short_name = 'AdmAdm';
            }
        });

        // Przed aktualizacją użytkownika
        static::updating(function ($user) {
            // admin@admin.com zawsze ma określone dane (hasło nie zmieniamy)
            if (strtolower($user->email) === 'admin@admin.com') {
                $user->first_name = 'Admin';
                $user->last_name = 'Admin';
                $user->short_name = 'AdmAdm';
            }
        });
    }
}
