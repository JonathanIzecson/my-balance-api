<?php

// use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Sin token / token inválido → 401
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => __('auth.unauthenticated')], 401);
            }
        });

        // Laravel convierte ModelNotFoundException en NotFoundHttpException antes de estos callbacks.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $message = $e->getPrevious() instanceof ModelNotFoundException
                    ? __('api.resource_not_found')
                    : __('api.route_not_found');

                return response()->json(['message' => $message], 404);
            }
        });

        // Autenticado pero sin permiso (policies / abilities de Sanctum) → 403
        // Se comenta por que aun no se si se van a usar policies o abilities de Sanctum o no
        // $exceptions->render(function (AuthorizationException $e, Request $request) {
        //     if ($request->is('api/*') || $request->expectsJson()) {
        //         return response()->json([
        //             'message' => 'No autorizado. No tiene permisos para esta acción.',
        //         ], 403);
        //     }
        // });
    })->create();
