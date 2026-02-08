<?php

namespace App\Providers;

use App\Console\Commands\MakeAdminModule;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // SADECE admin tarafında eager-load (public site şişmesin)
        View::composer('*', function () {
            if (auth()->check() && (request()->is('admin') || request()->is('admin/*'))) {
                auth()->user()->loadMissing('roles.permissions', 'avatarMedia');
            }
        });

        Paginator::defaultView('admin.vendor.pagination.kt');

        Blade::if('perm', function (string $slug) {
            $u = auth()->user();
            return $u && $u->is_active && $u->canAccess($slug);
        });

        // FIX: hem @permAny('a','b') hem @permAny(['a','b']) çalışsın
        Blade::if('permAny', function (...$slugs) {
            $u = auth()->user();
            if (!$u || !$u->is_active) return false;

            if (count($slugs) === 1 && is_array($slugs[0])) {
                $slugs = $slugs[0];
            }

            foreach ($slugs as $slug) {
                $slug = trim((string) $slug);
                if ($slug !== '' && $u->canAccess($slug)) return true;
            }
            return false;
        });

        Blade::if('admin', function () {
            $u = auth()->user();
            return $u && $u->canAccessAdmin();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeAdminModule::class,
            ]);
        }
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
                return true;
            }

            // Eğer hasRole yoksa ama relation varsa:
            // if ($user->roles()->where('slug','superadmin')->exists()) return true;

            return null;
        });
    }
}
