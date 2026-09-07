<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subscription> */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'plan_code' => 'starter',
            'status' => SubscriptionStatus::TRIALING,
            'trial_ends_at' => now()->addDays(3),
            'starts_at' => now(),
            'ends_at' => null,
            'provider' => null,
            'provider_subscription_id' => null,
        ];
    }

    public function trialing(): static
    {
        return $this->state(fn (): array => [
            'status' => SubscriptionStatus::TRIALING,
            'trial_ends_at' => now()->addDays(3),
            'starts_at' => now(),
            'ends_at' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => SubscriptionStatus::ACTIVE,
            'trial_ends_at' => null,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn (): array => [
            'status' => SubscriptionStatus::PAST_DUE,
            'trial_ends_at' => null,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => SubscriptionStatus::EXPIRED,
            'trial_ends_at' => now()->subDay(),
            'starts_at' => now()->subDays(4),
            'ends_at' => now()->subDay(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => SubscriptionStatus::CANCELLED,
            'ends_at' => now(),
        ]);
    }
}
