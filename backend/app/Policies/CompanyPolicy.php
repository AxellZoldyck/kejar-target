<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function view(User $user, Company $company): bool
    {
        return $user->role === UserRole::SUPER_ADMIN
            || ($user->company_id !== null && $user->company_id === $company->getKey());
    }

    public function update(User $user, Company $company): bool
    {
        return $user->role === UserRole::SUPER_ADMIN
            || ($user->role === UserRole::SPV
                && $user->company_id !== null
                && $user->company_id === $company->getKey());
    }
}
