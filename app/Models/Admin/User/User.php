<?php

namespace App\Models\Admin\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        // is_active varsa fillable'a koymak zorunda değilsin (formdan alıyorsan koy)
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean', // varsa
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Role var mı? (slug üzerinden)
     */
    public function hasRole(string $slug): bool
    {
        $slug = trim($slug);
        if ($slug === '') return false;

        // ilişkiler yüklüyse memory'den, değilse exists
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('slug', $slug);
        }

        return $this->roles()->where('slug', $slug)->exists();
    }

    /**
     * Superadmin kontrolü
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('superadmin');
    }

    /**
     * Admin veya Superadmin
     */
    public function isAdmin(): bool
    {
        // relation loaded ise hızlı, değilse exists
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(fn ($role) => in_array($role->slug, ['admin', 'superadmin'], true));
        }

        return $this->roles()->whereIn('slug', ['admin', 'superadmin'])->exists();
    }

    /**
     * Permission slug listesi (cache)
     */
    public function permissionSlugsCached(): array
    {
        $version = Cache::get('rbac:version', 1);
        $key = "rbac:user:{$this->id}:v{$version}";

        return Cache::remember($key, now()->addHours(6), function () {
            return $this->roles()
                ->with('permissions:permissions.id,slug')
                ->get()
                ->pluck('permissions')
                ->flatten()
                ->pluck('slug')
                ->filter(fn ($s) => is_string($s) && trim($s) !== '')
                ->map(fn ($s) => trim($s))
                ->unique()
                ->values()
                ->all();
        });
    }

    public function hasPermission(string $slug): bool
    {
        $slug = trim($slug);
        if ($slug === '') return false;

        // RBAC version'a göre memo tut ki version bump sonrası aynı request'te bile stale kalmasın
        $version = Cache::get('rbac:version', 1);

        static $memo = [];
        $memoKey = "{$this->id}:v{$version}";

        if (!isset($memo[$memoKey])) {
            $memo[$memoKey] = array_flip($this->permissionSlugsCached());
        }

        return isset($memo[$memoKey][$slug]);
    }

    /**
     * Birden fazla permission'dan herhangi biri var mı?
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission((string) $permission)) {
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
        return $this->isAdmin() && (bool) $this->is_active;
    }

    /**
     * Sidebar + UI için: superadmin bypass + permission
     */
    public function canAccess(string $permission): bool
    {
        return $this->isSuperAdmin() || $this->hasPermission($permission);
    }
}
