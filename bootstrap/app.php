<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AuditRequestMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
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

        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'superadmin' => SuperAdminMiddleware::class,
            'admin'      => AdminMiddleware::class,
            'audit'      => AuditRequestMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
