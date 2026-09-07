<?php

namespace App\Actions\Organization;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignTeamMemberAction
{
    public function execute(Team $team, User $sales, User $actor): TeamMember
    {
        if ($team->company_id !== $actor->company_id
            || $sales->company_id !== $actor->company_id
            || ! $sales->isSales()) {
            throw ValidationException::withMessages([
                'team_id' => ['Tim atau sales tidak ditemukan pada perusahaan ini.'],
            ]);
        }

        return DB::transaction(function () use ($team, $sales, $actor): TeamMember {
            $this->closeCurrentMembership($sales, $actor);

            return TeamMember::query()->create([
                'company_id' => $actor->company_id,
                'team_id' => $team->id,
                'user_id' => $sales->id,
                'joined_at' => now(),
                'left_at' => null,
                'active_slot' => 'active',
            ]);
        });
    }

    public function unassign(User $sales, User $actor): void
    {
        if ($sales->company_id !== $actor->company_id || ! $sales->isSales()) {
            throw ValidationException::withMessages([
                'team_id' => ['Sales tidak ditemukan pada perusahaan ini.'],
            ]);
        }

        DB::transaction(fn () => $this->closeCurrentMembership($sales, $actor));
    }

    private function closeCurrentMembership(User $sales, User $actor): void
    {
        TeamMember::query()
            ->where('company_id', $actor->company_id)
            ->where('user_id', $sales->id)
            ->where('active_slot', 'active')
            ->lockForUpdate()
            ->get()
            ->each(fn (TeamMember $membership) => $membership->update([
                'left_at' => now(),
                'active_slot' => null,
            ]));
    }
}
