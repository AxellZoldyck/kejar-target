<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Contracts\CurrentTenant;
use App\Contracts\SubscriptionAccess;
use App\Contracts\SubscriptionCheckout;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Subscription\CheckoutRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class SubscriptionController extends ApiController
{
    public function show(CurrentTenant $tenant, SubscriptionAccess $subscriptions): JsonResponse
    {
        /** @var Company $company */
        $company = $tenant->company();
        $subscription = $subscriptions->currentFor($company)
            ?? $subscriptions->latestFor($company);

        if ($subscription !== null) {
            Gate::authorize('view', $subscription);
        }

        return $this->data(
            $subscription === null ? null : new SubscriptionResource($subscription),
        );
    }

    public function checkout(
        CheckoutRequest $request,
        CurrentTenant $tenant,
        SubscriptionCheckout $checkout,
    ): JsonResponse {
        /** @var Company $company */
        $company = $tenant->company();
        Gate::authorize('checkout', [Subscription::class, $company]);

        /** @var User $actor */
        $actor = $request->user();
        $result = $checkout->request(
            company: $company,
            actor: $actor,
            planCode: (string) $request->validated('plan_code', 'starter'),
        );

        return $this->data($result, 202);
    }
}
