<?php

namespace App\Services;

use App\Contracts\SubscriptionAccess;
use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\Subscription;
use BackedEnum;

class EloquentSubscriptionAccess implements SubscriptionAccess
{
    public function currentFor(Company $company): ?Subscription
    {
        return Subscription::query()
            ->where('company_id', $company->getKey())
            ->where('running_slot', 'running')
            ->latest('created_at')
            ->first();
    }

    public function latestFor(Company $company): ?Subscription
    {
        return Subscription::query()
            ->where('company_id', $company->getKey())
            ->latest('created_at')
            ->first();
    }

    public function allowsMutation(?Subscription $subscription): bool
    {
        if ($subscription === null) {
            return false;
        }

        $status = $subscription->status;

        if (is_string($status)) {
            $status = SubscriptionStatus::tryFrom($status);
        } elseif ($status instanceof BackedEnum && ! $status instanceof SubscriptionStatus) {
            $status = SubscriptionStatus::tryFrom((string) $status->value);
        }

        if (! $status instanceof SubscriptionStatus || ! $status->allowsMutation()) {
            return false;
        }

        $now = now();

        if ($subscription->starts_at !== null && $subscription->starts_at->isFuture()) {
            return false;
        }

        if ($status === SubscriptionStatus::TRIALING) {
            return $subscription->trial_ends_at !== null
                && $subscription->trial_ends_at->isAfter($now);
        }

        return $subscription->ends_at === null || $subscription->ends_at->isAfter($now);
    }
}
