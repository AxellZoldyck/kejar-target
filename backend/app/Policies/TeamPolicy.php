<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $user->company_id === $team->company_id;
    }

    public function update(User $user, Team $team): bool
    {
        return $user->role === UserRole::SPV && $this->view($user, $team);
    }
}
