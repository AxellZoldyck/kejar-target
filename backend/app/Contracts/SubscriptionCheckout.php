<?php

namespace App\Contracts;

use App\Models\Company;
use App\Models\User;

interface SubscriptionCheckout
{
    /**
     * Start a checkout without assuming that payment or activation succeeded.
     *
     * @return array{
     *     mode: string,
     *     status: string,
     *     plan_code: string,
     *     provider: null,
     *     checkout_url: null,
     *     message: string
     * }
     */
    public function request(Company $company, User $actor, string $planCode): array;
}
