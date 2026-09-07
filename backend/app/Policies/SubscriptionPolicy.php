<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function view(User $user, Subscription $subscription): bool
    {
        return $user->role === UserRole::SUPER_ADMIN
            || ($user->role === UserRole::SPV
                && $user->company_id !== null
                && $user->company_id === $subscription->company_id);
    }

    public function checkout(User $user, Company $company): bool
    {
        return $user->role === UserRole::SPV
            && $user->company_id !== null
            && $user->company_id === $company->getKey();
    }
}
