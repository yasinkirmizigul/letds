<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('superadmin') ? true : null;
        });
        View::composer('*', function ($view) {
            if (auth()->check()) {
                auth()->user()->loadMissing('roles.permissions');
            }
        });
        Paginator::defaultView('admin.vendor.pagination.kt');
        Blade::if('perm', function (string $slug) {
            $u = auth()->user();
            return $u && $u->is_active && $u->canAccess($slug);
        });

        Blade::if('permAny', function (...$slugs) {
            $u = auth()->user();
            if (!$u || !$u->is_active) return false;

            foreach ($slugs as $slug) {
                if ($u->canAccess((string)$slug)) return true;
            }
            return false;
        });

        Blade::if('admin', function () {
            $u = auth()->user();
            return $u && $u->canAccessAdmin();
        });
    }
}
