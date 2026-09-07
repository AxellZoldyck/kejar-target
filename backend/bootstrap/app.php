<?php

use App\Exceptions\ApiException;
use App\Http\Middleware\EnsureSubscriptionAllowsMutation;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->alias([
            'user.active' => EnsureUserIsActive::class,
            'tenant' => ResolveTenant::class,
            'role' => EnsureUserHasRole::class,
            'subscription.write' => EnsureSubscriptionAllowsMutation::class,
            'subscription.writable' => EnsureSubscriptionAllowsMutation::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ApiException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->errorCode,
                'errors' => $exception->errors === [] ? (object) [] : $exception->errors,
            ], $exception->status, $exception->headers);
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'Validation failed',
                'code' => 'VALIDATION_ERROR',
                'errors' => $exception->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'Unauthenticated.',
                'code' => 'UNAUTHENTICATED',
                'errors' => (object) [],
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk tindakan ini.',
                'code' => 'FORBIDDEN',
                'errors' => (object) [],
            ], 403);
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'Resource tidak ditemukan.',
                'code' => 'NOT_FOUND',
                'errors' => (object) [],
            ], 404);
        });

        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'Sesi keamanan telah kedaluwarsa. Muat ulang lalu coba kembali.',
                'code' => 'CSRF_TOKEN_MISMATCH',
                'errors' => (object) [],
            ], 419);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $exception->getStatusCode();

            return response()->json([
                'message' => $exception->getMessage() ?: 'Permintaan tidak dapat diproses.',
                'code' => match ($status) {
                    403 => 'FORBIDDEN',
                    404 => 'NOT_FOUND',
                    409 => 'CONFLICT',
                    429 => 'TOO_MANY_REQUESTS',
                    default => 'HTTP_ERROR',
                },
                'errors' => (object) [],
            ], $status, $exception->getHeaders());
        });
    })->create();
