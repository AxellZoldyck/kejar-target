<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Commission;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Commission> */
class CommissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => (string) Str::uuid(),
            'team_id' => Team::factory(),
            'sales_id' => (string) Str::uuid(),
            'period' => today()->format('Y-m'),
            'product_fee_amount' => 100_000,
            'multiplier_value' => '1.0000',
            'progressive_incentive_amount' => 0,
            'total_amount' => 100_000,
            'formula_snapshot' => [
                'formula' => '(product_fee_total * multiplier) + progressive_total',
                'version' => 1,
                'products' => [],
            ],
            'calculation_version' => 1,
            'calculated_at' => now(),
            'locked_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Commission $commission): void {
            $team = Team::query()->find($commission->team_id);

            if ($team) {
                $commission->company_id = $team->company_id;
                $sales = User::query()->find($commission->sales_id)
                    ?? User::factory()->sales()->create(['company_id' => $team->company_id]);
                $sales->forceFill([
                    'company_id' => $team->company_id,
                    'role' => UserRole::SALES,
                ])->save();
                $commission->sales_id = $sales->id;
            }
        });
    }

    public function locked(): static
    {
        return $this->state(fn (): array => ['locked_at' => now()]);
    }
}
