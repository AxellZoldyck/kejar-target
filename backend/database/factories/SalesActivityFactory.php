<?php

namespace Database\Factories;

use App\Enums\SalesActivityStatus;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\SalesActivity;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SalesActivity> */
class SalesActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => (string) Str::uuid(),
            'team_id' => Team::factory(),
            'sales_id' => (string) Str::uuid(),
            'product_id' => (string) Str::uuid(),
            'activity_date' => today(),
            'customer_reference' => 'CUST-'.fake()->unique()->numerify('######'),
            'notes' => fake()->optional()->sentence(),
            'evidence_path' => null,
            'status' => SalesActivityStatus::DRAFT,
            'submitted_at' => null,
            'validated_at' => null,
            'validated_by' => null,
            'rejection_reason' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (SalesActivity $activity): void {
            $team = Team::query()->find($activity->team_id);

            if (! $team) {
                return;
            }

            $activity->company_id = $team->company_id;
            $sales = User::query()->find($activity->sales_id)
                ?? User::factory()->sales()->create(['company_id' => $team->company_id]);
            $sales->forceFill([
                'company_id' => $team->company_id,
                'role' => UserRole::SALES,
            ])->save();
            $activity->sales_id = $sales->id;

            $product = Product::query()->find($activity->product_id)
                ?? Product::factory()->create(['company_id' => $team->company_id]);
            $product->forceFill(['company_id' => $team->company_id])->save();
            $activity->product_id = $product->id;

            if ($activity->validated_by) {
                $validator = User::query()->find($activity->validated_by)
                    ?? User::factory()->spv()->create(['company_id' => $team->company_id]);
                $validator->forceFill([
                    'company_id' => $team->company_id,
                    'role' => UserRole::SPV,
                ])->save();
                $activity->validated_by = $validator->id;
            }
        });
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => SalesActivityStatus::PENDING,
            'submitted_at' => now(),
        ]);
    }

    public function validated(): static
    {
        return $this->state(fn (): array => [
            'status' => SalesActivityStatus::VALIDATED,
            'submitted_at' => now()->subMinute(),
            'validated_at' => now(),
            'validated_by' => (string) Str::uuid(),
        ]);
    }

    public function rejected(string $reason = 'Bukti belum lengkap'): static
    {
        return $this->state(fn (): array => [
            'status' => SalesActivityStatus::REJECTED,
            'submitted_at' => now()->subMinute(),
            'rejection_reason' => $reason,
        ]);
    }
}
