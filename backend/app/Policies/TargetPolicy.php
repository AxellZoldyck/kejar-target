<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Target;
use App\Models\User;

class TargetPolicy
{
    public function view(User $user, Target $target): bool
    {
        return $user->company_id === $target->company_id
            && ($user->role === UserRole::SPV || $target->sales_id === $user->id);
    }

    public function update(User $user, Target $target): bool
    {
        return $user->role === UserRole::SPV && $user->company_id === $target->company_id;
    }
}
