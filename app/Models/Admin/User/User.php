<?php

namespace App\Models\Admin\User;

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
        'email',
        'password',
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
            'password' => 'hashed',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Superadmin kontrolü
     */
    public function isSuperAdmin(): bool
    {
        return $this->roles->contains(fn ($role) => $role->slug === 'superadmin');
    }

    /**
     * Admin veya Superadmin
     */
    public function isAdmin(): bool
    {
        return $this->roles->contains(fn ($role) => in_array($role->slug, ['admin', 'superadmin']));
    }

    /**
     * Belirli bir permission var mı?
     */
    public function hasPermission(string $permission): bool
    {
        // Superadmin her şeye sahiptir
        if ($this->isSuperAdmin()) {
            return true;
        }

        // İlişkiler yüklü değilse lazy load et
        if (!$this->relationLoaded('roles')) {
            $this->load('roles.permissions');
        }

        return $this->roles
            ->flatMap(fn ($role) => $role->permissions)
            ->contains(fn ($perm) => $perm->slug === $permission);
    }

    /**
     * Birden fazla permission'dan herhangi biri var mı?
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Admin panel erişimi
     */
    public function canAccessAdmin(): bool
    {
        return $this->isAdmin() && $this->is_active;
    }
}
