<?php

namespace App\Actions\Organization;

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateSalesAction
{
    public function __construct(private readonly AssignTeamMemberAction $assignMember) {}

    public function execute(User $actor, array $data): User
    {
        return DB::transaction(function () use ($actor, $data): User {
            $sales = User::query()->create([
                ...Arr::only($data, ['name', 'email', 'password']),
                'company_id' => $actor->company_id,
                'role' => UserRole::SALES,
                'is_active' => $data['is_active'] ?? true,
            ]);

            if (! empty($data['team_id'])) {
                $team = Team::query()
                    ->where('company_id', $actor->company_id)
                    ->where('is_active', true)
                    ->findOrFail($data['team_id']);
                $this->assignMember->execute($team, $sales, $actor);
            }

            return $sales;
        });
    }
}
