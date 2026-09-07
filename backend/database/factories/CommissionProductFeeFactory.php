<?php

namespace Database\Factories;

use App\Models\CommissionProductFee;
use App\Models\CommissionSetting;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CommissionProductFee> */
class CommissionProductFeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => (string) Str::uuid(),
            'commission_setting_id' => CommissionSetting::factory(),
            'product_id' => (string) Str::uuid(),
            'fee_amount' => fake()->randomElement([50_000, 75_000, 100_000]),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (CommissionProductFee $fee): void {
            $setting = CommissionSetting::query()->find($fee->commission_setting_id);

            if ($setting) {
                $fee->company_id = $setting->company_id;
                $product = Product::query()->find($fee->product_id)
                    ?? Product::factory()->create(['company_id' => $setting->company_id]);
                $product->forceFill(['company_id' => $setting->company_id])->save();
                $fee->product_id = $product->id;
            }
        });
    }
}
