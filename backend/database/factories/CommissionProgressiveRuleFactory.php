<?php

namespace Database\Factories;

use App\Models\CommissionProgressiveRule;
use App\Models\CommissionSetting;
use App\Models\Product;
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
            'product_id' => (string) Str::uuid(),
            'sequence_number' => 1,
            'incentive_amount' => 50_000,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (CommissionProgressiveRule $rule): void {
            $setting = CommissionSetting::query()->find($rule->commission_setting_id);

            if ($setting) {
                $rule->company_id = $setting->company_id;
                $product = Product::query()->find($rule->product_id)
                    ?? Product::factory()->create(['company_id' => $setting->company_id]);
                $product->forceFill(['company_id' => $setting->company_id])->save();
                $rule->product_id = $product->id;
            }
        });
    }
}
