<?php

namespace App\Policies;

use App\Enums\SalesActivityStatus;
use App\Enums\UserRole;
use App\Models\SalesActivity;
use App\Models\User;

class SalesActivityPolicy
{
    public function view(User $user, SalesActivity $activity): bool
    {
        if ($user->company_id !== $activity->company_id) {
            return false;
        }

        return $user->role === UserRole::SALES
            ? $activity->sales_id === $user->id
            : $user->role === UserRole::SPV && $activity->team?->supervisor_id === $user->id;
    }

    public function update(User $user, SalesActivity $activity): bool
    {
        return $this->view($user, $activity)
            && $activity->sales_id === $user->id
            && in_array($activity->status, [SalesActivityStatus::DRAFT, SalesActivityStatus::REJECTED], true);
    }

    public function review(User $user, SalesActivity $activity): bool
    {
        return $user->role === UserRole::SPV
            && $this->view($user, $activity)
            && $activity->status === SalesActivityStatus::PENDING;
    }
}
