<?php

use App\Http\Middleware\EnsureOrganizationAccess;
use App\Http\Middleware\EnsureOrganizationAiEnabled;
use App\Http\Middleware\EnsureOrganizationMember;
use App\Http\Middleware\EnsureOrganizationPermission;
use App\Http\Middleware\EnsureProjectAccess;
use App\Http\Middleware\EnsureProjectPermission;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'org.access' => EnsureOrganizationAccess::class,
            'org.member' => EnsureOrganizationMember::class,
            'org.permission' => EnsureOrganizationPermission::class,
            'org.ai' => EnsureOrganizationAiEnabled::class,
            'project.access' => EnsureProjectAccess::class,
            'project.permission' => EnsureProjectPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $exception, \Illuminate\Http\Request $request) {
            if (! $request->header('X-Inertia')) {
                return null;
            }

            $message = $exception->getMessage() ?: __('Too many requests. Please wait a moment and try again.');

            \Inertia\Inertia::flash('toast', [
                'type' => 'error',
                'message' => $message,
            ]);

            return back();
        });
    })->create();
