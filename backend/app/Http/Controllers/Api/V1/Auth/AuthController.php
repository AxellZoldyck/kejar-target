<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RegisterCompany;
use App\Contracts\SubscriptionAccess;
use App\Exceptions\ApiException;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends ApiController
{
    public function register(
        RegisterRequest $request,
        RegisterCompany $registerCompany,
    ): JsonResponse {
        $result = $registerCompany->execute($request->validated());

        Auth::guard('web')->login($result['user']);
        $request->session()->regenerate();

        return $this->data([
            'user' => new UserResource($result['user']),
            'company' => new CompanyResource($result['company']),
            'subscription' => new SubscriptionResource($result['subscription']),
        ], 201);
    }

    public function login(
        LoginRequest $request,
        SubscriptionAccess $subscriptions,
        AuditLogger $auditLogger,
    ): JsonResponse {
        if (! Auth::guard('web')->attempt($request->credentials())) {
            throw new ApiException('Email atau password tidak valid.', 'INVALID_CREDENTIALS', 422);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::guard('web')->user();

        if (! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw new ApiException('Akun ini tidak aktif.', 'ACCOUNT_INACTIVE', 403);
        }

        $user->loadMissing('company');
        $subscription = $user->company === null
            ? null
            : ($subscriptions->currentFor($user->company)
                ?? $subscriptions->latestFor($user->company));

        $auditLogger->record(
            event: 'auth.login',
            actor: $user,
            auditable: $user,
            metadata: [
                'ip' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ],
        );

        return $this->data([
            'user' => new UserResource($user),
            'company' => $user->company === null ? null : new CompanyResource($user->company),
            'subscription' => $subscription === null ? null : new SubscriptionResource($subscription),
        ]);
    }

    public function logout(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null) {
            $auditLogger->record(
                event: 'auth.logout',
                actor: $user,
                auditable: $user,
                metadata: ['ip' => $request->ip()],
            );
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->data(['message' => 'Logout berhasil.']);
    }
}
