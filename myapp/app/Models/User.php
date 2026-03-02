<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @method bool hasRole(string|array $roles, ?string $guard = null)
 * @method bool hasAnyRole(string|array $roles, ?string $guard = null)
 * @method bool hasPermissionTo(string|\Spatie\Permission\Contracts\Permission $permission, ?string $guard = null)
 * @method \Illuminate\Support\Collection getRoleNames()
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'is_active',
        'locale',
        'all_companies',
        'pin_hash',
        'pin_attempts',
        'pin_locked_until',
        'pin_set_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pin_hash',
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
            'password' => 'hashed',
            'is_active' => 'boolean',
            'all_companies' => 'boolean',
            'password_changed_at' => 'datetime',
            'pin_set_at' => 'datetime',
            'pin_locked_until' => 'datetime',
        ];
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class)->withTimestamps();
    }
}
