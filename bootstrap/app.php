<?php

use App\Exceptions\AlreadyAuthenticatedException;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Sanctum SPA authentication (cookie + session) for the Angular front end.
        $middleware->statefulApi();
        $middleware->api(append: [SecurityHeaders::class]);

        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->expectsJson()) {
                throw new AlreadyAuthenticatedException;
            }

            return '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // This is a JSON-only API: never answer with HTML or redirects.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => true);

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json(['message' => __('messages.unauthenticated')], 401);
        });

        // Gate/policy denials (e.g. the "manage-content" gate) arrive here as 403.
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        });
    })->create();
