<?php

namespace App\Http\Middleware;

use App\Contracts\CurrentTenant;
use App\Contracts\SubscriptionAccess;
use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionAllowsMutation
{
    public function __construct(
        private readonly CurrentTenant $tenant,
        private readonly SubscriptionAccess $subscriptions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $company = $this->tenant->company();

        if ($company === null) {
            throw new ApiException('Tenant belum ditentukan.', 'TENANT_REQUIRED', 403);
        }

        $subscription = $this->subscriptions->currentFor($company)
            ?? $this->subscriptions->latestFor($company);

        if (! $this->subscriptions->allowsMutation($subscription)) {
            throw new ApiException(
                'Subscription tidak aktif. Data tetap dapat dibaca, tetapi mutasi memerlukan subscription trialing atau active.',
                'SUBSCRIPTION_READ_ONLY',
                403,
            );
        }

        $request->attributes->set('subscription', $subscription);

        return $next($request);
    }
}
