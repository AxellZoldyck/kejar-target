<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use BackedEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw new ApiException('Unauthenticated.', 'UNAUTHENTICATED', 401);
        }

        $role = $user->role;
        $roleValue = $role instanceof BackedEnum ? (string) $role->value : (string) $role;

        if (! in_array($roleValue, $roles, true)) {
            throw new ApiException('Anda tidak memiliki izin untuk tindakan ini.', 'FORBIDDEN', 403);
        }

        return $next($request);
    }
}
