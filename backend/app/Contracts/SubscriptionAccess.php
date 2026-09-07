<?php

namespace App\Contracts;

use App\Models\Company;
use App\Models\Subscription;

interface SubscriptionAccess
{
    public function currentFor(Company $company): ?Subscription;

    public function latestFor(Company $company): ?Subscription;

    public function allowsMutation(?Subscription $subscription): bool;
}
