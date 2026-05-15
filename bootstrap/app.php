<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AjaxRedirectResponseMiddleware;
use App\Http\Middleware\AuditRequestMiddleware;
use App\Http\Middleware\EnsureActiveMemberSession;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\SiteLocaleMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/admin/auth.php',
            __DIR__.'/../routes/web.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware(['web', 'auth', 'admin', 'audit'])
                ->prefix('admin')
                ->as('admin.')
                ->group(base_path('routes/admin/index.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('member/*') || $request->is('randevu-al')) {
                return route('member.login');
            }

            return route('login');
        });

        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->is('member/*') || $request->is('randevu-al')) {
                return route('member.appointments.index');
            }

            return route('admin.dashboard');
        });

        $middleware->web(append: [
            AjaxRedirectResponseMiddleware::class,
        ]);

        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'superadmin' => SuperAdminMiddleware::class,
            'admin'      => AdminMiddleware::class,
            'audit'      => AuditRequestMiddleware::class,
            'site.locale' => SiteLocaleMiddleware::class,
            'member.active' => EnsureActiveMemberSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
