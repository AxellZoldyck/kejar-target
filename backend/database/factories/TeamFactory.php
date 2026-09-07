<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Team> */
class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Tim '.fake()->unique()->bothify('??-###'),
            'supervisor_id' => (string) Str::uuid(),
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Team $team): void {
            if (! $team->company_id) {
                return;
            }

            $supervisor = User::query()->find($team->supervisor_id)
                ?? User::factory()->spv()->create(['company_id' => $team->company_id]);

            $supervisor->forceFill([
                'company_id' => $team->company_id,
                'role' => UserRole::SPV,
                'is_active' => true,
            ])->save();
            $team->supervisor_id = $supervisor->id;
        });
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
