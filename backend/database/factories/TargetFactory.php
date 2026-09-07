<?php

namespace Database\Factories;

use App\Enums\TargetType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Target;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Target> */
class TargetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'sales_id' => (string) Str::uuid(),
            'type' => TargetType::MONTHLY,
            'period_start' => today()->startOfMonth(),
            'period_end' => today()->endOfMonth(),
            'target_value' => fake()->numberBetween(5, 30),
            'created_by' => (string) Str::uuid(),
            'updated_by' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Target $target): void {
            $sales = User::query()->find($target->sales_id)
                ?? User::factory()->sales()->create(['company_id' => $target->company_id]);
            $sales->forceFill([
                'company_id' => $target->company_id,
                'role' => UserRole::SALES,
            ])->save();
            $target->sales_id = $sales->id;

            $creator = User::query()->find($target->created_by)
                ?? User::factory()->spv()->create(['company_id' => $target->company_id]);
            $creator->forceFill([
                'company_id' => $target->company_id,
                'role' => UserRole::SPV,
            ])->save();
            $target->created_by = $creator->id;
        });
    }

    public function weekly(): static
    {
        return $this->state(fn (): array => [
            'type' => TargetType::WEEKLY,
            'period_start' => today()->startOfWeek(),
            'period_end' => today()->endOfWeek(),
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (): array => [
            'type' => TargetType::MONTHLY,
            'period_start' => today()->startOfMonth(),
            'period_end' => today()->endOfMonth(),
        ]);
    }
}
