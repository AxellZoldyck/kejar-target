<?php

namespace Database\Factories;

use App\Enums\ProgressiveOverflowBehavior;
use App\Models\CommissionSetting;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommissionSetting> */
class CommissionSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'version' => 1,
            'multiplier_enabled' => false,
            'progressive_enabled' => false,
            'progressive_overflow_behavior' => ProgressiveOverflowBehavior::ZERO,
            'effective_from' => now()->startOfMonth(),
            'effective_until' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
            'effective_until' => now(),
        ]);
    }

    public function withIncentives(): static
    {
        return $this->state(fn (): array => [
            'multiplier_enabled' => true,
            'progressive_enabled' => true,
        ]);
    }
}
