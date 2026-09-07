<?php

namespace Database\Factories;

use App\Models\CommissionMultiplierRule;
use App\Models\CommissionSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CommissionMultiplierRule> */
class CommissionMultiplierRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => (string) Str::uuid(),
            'commission_setting_id' => CommissionSetting::factory(),
            'min_sa' => 1,
            'max_sa' => 10,
            'multiplier_value' => '1.0000',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (CommissionMultiplierRule $rule): void {
            $setting = CommissionSetting::query()->find($rule->commission_setting_id);

            if ($setting) {
                $rule->company_id = $setting->company_id;
            }
        });
    }
}
