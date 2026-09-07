<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<TeamMember> */
class TeamMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => (string) Str::uuid(),
            'team_id' => Team::factory(),
            'user_id' => (string) Str::uuid(),
            'joined_at' => now(),
            'left_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (TeamMember $membership): void {
            $team = Team::query()->find($membership->team_id);

            if ($team) {
                $membership->company_id = $team->company_id;
                $sales = User::query()->find($membership->user_id)
                    ?? User::factory()->sales()->create(['company_id' => $team->company_id]);
                $sales->forceFill([
                    'company_id' => $team->company_id,
                    'role' => UserRole::SALES,
                ])->save();
                $membership->user_id = $sales->id;
            }
        });
    }

    public function left(): static
    {
        return $this->state(fn (): array => ['left_at' => now()]);
    }
}
