<?php

namespace Database\Factories;

use App\Models\CommissionProgressiveRule;
use App\Models\CommissionSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CommissionProgressiveRule> */
class CommissionProgressiveRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => (string) Str::uuid(),
            'commission_setting_id' => CommissionSetting::factory(),
            'min_sa' => 1,
            'max_sa' => 1,
            'incentive_amount' => 50_000,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (CommissionProgressiveRule $rule): void {
            $setting = CommissionSetting::query()->find($rule->commission_setting_id);

            if ($setting) {
                $rule->company_id = $setting->company_id;
            }
        });
    }
}
